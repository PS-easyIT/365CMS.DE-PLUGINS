<?php
/**
 * CMS Forum – Thread Controller
 *
 * Thread-Erstellung, -Anzeige und Thread-bezogene Aktionen
 * (Abonnement, Umfragen).
 *
 * @package CMS_Forum\Controllers
 */

declare(strict_types=1);

namespace CMS_Forum\Controllers;

if (!defined('ABSPATH')) {
    exit;
}

use CMS_Forum\Models\Thread;
use CMS_Forum\Models\Post;
use CMS_Forum\Models\Forum;
use CMS_Forum\Models\Poll;
use CMS_Forum\Models\Subscription;
use CMS_Forum\Models\Attachment;
use CMS_Forum\Helpers\Pagination;
use CMS_Forum\Helpers\SlugHelper;
use CMS_Forum\Services\BBCodeParser;
use CMS_Forum\Services\PermissionService;
use CMS_Forum\Services\FloodControl;
use CMS_Forum\Services\NotificationService;
use CMS_Forum\Services\ReadTracker;

final class ThreadController
{
    private static ?self $instance = null;

    public static function instance(): static
    {
        return static::$instance ??= new static();
    }

    private function __construct() {}

    /**
     * Thread anzeigen mit Beiträgen.
     */
    public function show(int $threadId): void
    {
        $thread = Thread::instance()->findById($threadId);
        if (!$thread || $thread->status === 'deleted') {
            http_response_code(404);
            \CMS\ThemeManager::instance()->render('404');
            return;
        }

        $forum = Forum::instance()->findById((int) $thread->forum_id);
        if (!$forum || !PermissionService::instance()->canRead((int) $forum->id)) {
            http_response_code(403);
            echo '<div class="cmsforum-error"><h2>Zugriff verweigert</h2></div>';
            return;
        }

        // View-Counter
        Thread::instance()->incrementViews($threadId);

        // Beiträge mit Paginierung
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = $this->getSetting('posts_per_page', 15);
        $total   = Post::instance()->countByThread($threadId);
        $pag     = new Pagination($total, $page, $perPage);

        $posts = Post::instance()->findByThread($threadId, $pag->offset, $pag->perPage);

        // BBCode parsen
        $parser = BBCodeParser::instance();
        foreach ($posts as $post) {
            $post->content_html = $parser->parse($post->content);
        }

        // Als gelesen markieren
        $auth = \CMS\Auth::instance();
        if ($auth->isLoggedIn()) {
            ReadTracker::instance()->markRead((int)$auth->currentUser()->id, $threadId);
        }

        // Umfrage laden falls vorhanden
        $poll = Poll::instance()->findByThread($threadId);
        $pollOptions = $poll ? Poll::instance()->getOptions((int) $poll->id) : [];
        $userVotes = ($poll && $auth->isLoggedIn())
            ? Poll::instance()->getUserVotes((int) $poll->id, (int)$auth->currentUser()->id)
            : [];

        // Abo-Status
        $isSubscribed = $auth->isLoggedIn()
            ? Subscription::instance()->isSubscribed((int)$auth->currentUser()->id, 'thread', $threadId)
            : false;

        // Attachments laden
        $postIds = array_map(fn($p) => (int) $p->id, $posts);
        $attachments = !empty($postIds)
            ? Attachment::instance()->findByPostIds($postIds)
            : [];

        // POST: Neue Antwort
        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
            [$error, $success] = $this->handleReply($threadId, (int) $forum->id, $thread->title);
        }

        $viewData = [
            'thread'       => $thread,
            'forum'        => $forum,
            'posts'        => $posts,
            'pagination'   => $pag,
            'poll'         => $poll,
            'pollOptions'  => $pollOptions,
            'userVotes'    => $userVotes,
            'isSubscribed' => $isSubscribed,
            'attachments'  => $attachments,
            'error'        => $error,
            'success'      => $success,
            'pageTitle'    => htmlspecialchars($thread->title),
        ];

        extract($viewData, EXTR_SKIP);
        include CMS_FORUM_DIR . 'views/frontend/thread-show.php';
    }

    /**
     * Neuen Thread erstellen.
     */
    public function create(int $forumId, object $forum): void
    {
        $auth    = \CMS\Auth::instance();
        $userId  = (int)$auth->currentUser()->id;
        $error   = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_thread') {
            if (!\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'forum_new_thread')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                // Flood-Control
                $wait = FloodControl::instance()->checkThread($userId);
                if ($wait > 0) {
                    $error = "Bitte warte noch {$wait} Sekunden, bevor du einen neuen Thread erstellst.";
                } else {
                    $title   = sanitize_text_field($_POST['title'] ?? '');
                    $content = trim($_POST['content'] ?? '');
                    $type    = in_array($_POST['type'] ?? '', ['normal', 'sticky', 'announcement'], true) ? $_POST['type'] : 'normal';

                    if (empty($title)) {
                        $error = 'Bitte gib einen Titel ein.';
                    } elseif (mb_strlen($title) < 3) {
                        $error = 'Der Titel muss mindestens 3 Zeichen lang sein.';
                    } elseif (empty($content)) {
                        $error = 'Bitte gib einen Beitrag ein.';
                    } else {
                        // Nur Admins/Moderatoren dürfen sticky/announcement erstellen
                        if ($type !== 'normal' && !PermissionService::instance()->canModerate($forumId)) {
                            $type = 'normal';
                        }

                        $slug = SlugHelper::unique($title, 'cmsforum_threads');
                        $threadId = Thread::instance()->create([
                            'forum_id' => $forumId,
                            'user_id'  => $userId,
                            'title'    => $title,
                            'slug'     => $slug,
                            'type'     => $type,
                            'status'   => 'open',
                        ]);

                        if ($threadId) {
                            // Erster Beitrag
                            Post::instance()->create([
                                'thread_id'    => $threadId,
                                'user_id'      => $userId,
                                'content'      => $content,
                                'is_first_post' => 1,
                                'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? '',
                            ]);

                            // Umfrage erstellen falls vorhanden
                            if (!empty($_POST['poll_question']) && !empty($_POST['poll_options'])) {
                                $this->createPoll($threadId, $_POST['poll_question'], $_POST['poll_options'], $_POST['poll_multi'] ?? '0');
                            }

                            // Zähler aktualisieren
                            Thread::instance()->refreshCounters($threadId);
                            Forum::instance()->refreshCounters($forumId);

                            // User-Meta aktualisieren
                            \CMS_Forum\Models\UserMeta::instance()->incrementThreadCount($userId);

                            // Benachrichtigungen
                            NotificationService::instance()->notifyNewThread($forumId, $userId, $title, $threadId);

                            // Auto-Abo
                            Subscription::instance()->subscribe($userId, 'thread', $threadId);

                            header('Location: ' . SITE_URL . '/forum/thread/' . $threadId);
                            exit;
                        } else {
                            $error = 'Thread konnte nicht erstellt werden.';
                        }
                    }
                }
            }
        }

        $csrfToken = \CMS\Security::instance()->generateToken('forum_new_thread');

        $viewData = [
            'forum'     => $forum,
            'csrfToken' => $csrfToken,
            'error'     => $error,
            'pageTitle' => 'Neuer Thread in ' . htmlspecialchars($forum->name),
        ];

        extract($viewData, EXTR_SKIP);
        include CMS_FORUM_DIR . 'views/frontend/thread-create.php';
    }

    /**
     * Abonnement per AJAX toggeln.
     */
    public function toggleSubscription(): void
    {
        header('Content-Type: application/json');
        $auth = \CMS\Auth::instance();

        if (!$auth->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Nicht eingeloggt.']);
            exit;
        }

        if (!\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'forum_subscribe')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.']);
            exit;
        }

        $type   = in_array($_POST['type'] ?? '', ['thread', 'forum'], true) ? $_POST['type'] : null;
        $itemId = (int) ($_POST['item_id'] ?? 0);

        if (!$type || $itemId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ungültige Anfrage.']);
            exit;
        }

        $userId = (int)$auth->currentUser()->id;
        $sub    = Subscription::instance();

        if ($sub->isSubscribed($userId, $type, $itemId)) {
            $sub->unsubscribe($userId, $type, $itemId);
            echo json_encode(['success' => true, 'subscribed' => false]);
        } else {
            $sub->subscribe($userId, $type, $itemId);
            echo json_encode(['success' => true, 'subscribed' => true]);
        }
        exit;
    }

    /**
     * Umfrage-Abstimmung per AJAX.
     */
    public function pollVote(): void
    {
        header('Content-Type: application/json');
        $auth = \CMS\Auth::instance();

        if (!$auth->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Nicht eingeloggt.']);
            exit;
        }

        if (!\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'forum_poll_vote')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.']);
            exit;
        }

        $pollId    = (int) ($_POST['poll_id'] ?? 0);
        $optionIds = array_map('intval', (array) ($_POST['option_ids'] ?? []));

        if ($pollId <= 0 || empty($optionIds)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ungültige Anfrage.']);
            exit;
        }

        $userId = (int)$auth->currentUser()->id;
        $poll   = Poll::instance();

        if ($poll->hasVoted($pollId, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Du hast bereits abgestimmt.']);
            exit;
        }

        foreach ($optionIds as $optId) {
            $poll->vote($pollId, $optId, $userId);
        }

        echo json_encode(['success' => true]);
        exit;
    }

    // ── Private Helfer ──────────────────────────────────────────────

    /**
     * Antwort auf einen Thread verarbeiten.
     *
     * @return array{0: ?string, 1: ?string} [$error, $success]
     */
    private function handleReply(int $threadId, int $forumId, string $threadTitle): array
    {
        $auth = \CMS\Auth::instance();

        if (!$auth->isLoggedIn()) {
            return ['Du musst eingeloggt sein, um zu antworten.', null];
        }

        if (!\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'forum_reply')) {
            return ['Sicherheitscheck fehlgeschlagen.', null];
        }

        if (!PermissionService::instance()->canPost($forumId)) {
            return ['Du hast keine Berechtigung, hier zu schreiben.', null];
        }

        $userId = (int)$auth->currentUser()->id;

        // Flood-Control
        $wait = FloodControl::instance()->checkPost($userId);
        if ($wait > 0) {
            return ["Bitte warte noch {$wait} Sekunden.", null];
        }

        $content = trim($_POST['content'] ?? '');
        if (empty($content)) {
            return ['Bitte gib einen Beitrag ein.', null];
        }

        $postId = Post::instance()->create([
            'thread_id'  => $threadId,
            'user_id'    => $userId,
            'content'    => $content,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        if (!$postId) {
            return ['Beitrag konnte nicht erstellt werden.', null];
        }

        // Zähler aktualisieren
        Thread::instance()->refreshCounters($threadId);
        Forum::instance()->refreshCounters($forumId);
        \CMS_Forum\Models\UserMeta::instance()->incrementPostCount($userId);

        // Benachrichtigungen
        NotificationService::instance()->notifyNewPost($threadId, $userId, $threadTitle);

        return [null, 'Antwort wurde veröffentlicht.'];
    }

    /**
     * Umfrage erstellen.
     */
    private function createPoll(int $threadId, string $question, string $options, string $multiChoice): void
    {
        $question = sanitize_text_field($question);
        if (empty($question)) {
            return;
        }

        $optionLines = array_filter(array_map('trim', explode("\n", $options)));
        if (count($optionLines) < 2) {
            return;
        }

        $pollId = Poll::instance()->create(
            $threadId,
            $question,
            $multiChoice === '1' ? 10 : 1
        );

        if ($pollId) {
            foreach ($optionLines as $i => $text) {
                $text = sanitize_text_field($text);
                if ($text !== '') {
                    Poll::instance()->addOption($pollId, $text, $i);
                }
            }
        }
    }

    /**
     * Einstellung laden.
     */
    private function getSetting(string $key, int $default): int
    {
        try {
            $db = \CMS\Database::instance();
            $p  = $db->prefix();
            $stmt = $db->prepare("SELECT setting_value FROM {$p}cmsforum_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return $val !== false ? (int) $val : $default;
        } catch (\PDOException) {
            return $default;
        }
    }
}
