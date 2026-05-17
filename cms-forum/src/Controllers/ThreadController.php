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
            \CMS\ThemeManager::instance()->getHeader();
            echo '<main class="cmsforum"><div class="cmsforum-error"><h2>Zugriff verweigert</h2></div></main>';
            \CMS\ThemeManager::instance()->getFooter();
            return;
        }

        // View-Counter
        Thread::instance()->incrementViews($threadId);

        // Beiträge mit Paginierung
        $page    = max(1, min(999, (int) ($_GET['page'] ?? 1)));
        $perPage = max(1, min(100, $this->getSetting('posts_per_page', 15)));
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
            'pageTitle'    => htmlspecialchars((string) $thread->title, ENT_QUOTES, 'UTF-8'),
        ];

        extract($viewData, EXTR_SKIP);
        \CMS\ThemeManager::instance()->getHeader();
        include CMS_FORUM_DIR . 'views/frontend/thread-show.php';
        \CMS\ThemeManager::instance()->getFooter();
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
            if (!\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'forum_new_thread')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                // Flood-Control
                $wait = FloodControl::instance()->checkThread($userId);
                if ($wait > 0) {
                    $error = "Bitte warte noch {$wait} Sekunden, bevor du einen neuen Thread erstellst.";
                } else {
                    $title   = mb_substr(sanitize_text_field((string) ($_POST['title'] ?? '')), 0, 200);
                    $content = mb_substr(trim((string) ($_POST['content'] ?? '')), 0, 50000);
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
                                'ip_address'   => filter_var((string) ($_SERVER['REMOTE_ADDR'] ?? ''), FILTER_VALIDATE_IP) ?: '',
                            ]);

                            // Umfrage erstellen falls vorhanden
                            if (!empty($_POST['poll_question']) && !empty($_POST['poll_options'])) {
                                $this->createPoll($threadId, (string) $_POST['poll_question'], (array) $_POST['poll_options'], (string) ($_POST['poll_multi'] ?? '0'));
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

                            header('Location: ' . rtrim((string) SITE_URL, '/') . '/forum/thread/' . $threadId, true, 303);
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
            'pageTitle' => 'Neuer Thread in ' . htmlspecialchars((string) $forum->name, ENT_QUOTES, 'UTF-8'),
        ];

        extract($viewData, EXTR_SKIP);
        \CMS\ThemeManager::instance()->getHeader();
        include CMS_FORUM_DIR . 'views/frontend/thread-create.php';
        \CMS\ThemeManager::instance()->getFooter();
    }

    /**
     * Abonnement per AJAX toggeln.
     */
    public function toggleSubscription(): void
    {
        $this->sendJsonHeaders();
        $auth = \CMS\Auth::instance();

        if (!$auth->isLoggedIn()) {
            http_response_code(401);
            $this->sendJson(['success' => false, 'error' => 'Nicht eingeloggt.'], 401);
            exit;
        }

        if (!\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'forum_subscribe')) {
            $this->sendJson(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.'], 403);
            exit;
        }

        $type   = in_array($_POST['type'] ?? '', ['thread', 'forum'], true) ? $_POST['type'] : null;
        $itemId = (int) ($_POST['item_id'] ?? 0);

        if (!$type || $itemId <= 0) {
            $this->sendJson(['success' => false, 'error' => 'Ungültige Anfrage.'], 400);
            exit;
        }

        $userId = (int)$auth->currentUser()->id;
        $sub    = Subscription::instance();

        if ($sub->isSubscribed($userId, $type, $itemId)) {
            $sub->unsubscribe($userId, $type, $itemId);
            $this->sendJson(['success' => true, 'subscribed' => false]);
        } else {
            $sub->subscribe($userId, $type, $itemId);
            $this->sendJson(['success' => true, 'subscribed' => true]);
        }
        exit;
    }

    /**
     * Umfrage-Abstimmung per AJAX.
     */
    public function pollVote(): void
    {
        $this->sendJsonHeaders();
        $auth = \CMS\Auth::instance();

        if (!$auth->isLoggedIn()) {
            $this->sendJson(['success' => false, 'error' => 'Nicht eingeloggt.'], 401);
            exit;
        }

        if (!\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'forum_poll_vote')) {
            $this->sendJson(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.'], 403);
            exit;
        }

        $pollId    = (int) ($_POST['poll_id'] ?? 0);
        $optionIds = array_values(array_unique(array_filter(
            array_map('intval', (array) ($_POST['option_ids'] ?? [])),
            static fn (int $id): bool => $id > 0
        )));
        $optionIds = array_slice($optionIds, 0, 10);

        if ($pollId <= 0 || empty($optionIds)) {
            $this->sendJson(['success' => false, 'error' => 'Ungültige Anfrage.'], 400);
            exit;
        }

        $userId = (int)$auth->currentUser()->id;
        $poll   = Poll::instance();

        if ($poll->hasVoted($pollId, $userId)) {
            $this->sendJson(['success' => false, 'error' => 'Du hast bereits abgestimmt.']);
            exit;
        }

        foreach ($optionIds as $optId) {
            $poll->vote($pollId, $optId, $userId);
        }

        $this->sendJson(['success' => true]);
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

        if (!\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'forum_reply')) {
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

        $content = mb_substr(trim((string) ($_POST['content'] ?? '')), 0, 50000);
        if (empty($content)) {
            return ['Bitte gib einen Beitrag ein.', null];
        }

        $postId = Post::instance()->create([
            'thread_id'  => $threadId,
            'user_id'    => $userId,
            'content'    => $content,
            'ip_address' => filter_var((string) ($_SERVER['REMOTE_ADDR'] ?? ''), FILTER_VALIDATE_IP) ?: '',
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
    private function createPoll(int $threadId, string $question, array $options, string $multiChoice): void
    {
        $question = mb_substr(sanitize_text_field($question), 0, 255);
        if (empty($question)) {
            return;
        }

        $optionLines = array_values(array_filter(array_map(
            static fn (mixed $option): string => mb_substr(sanitize_text_field((string) $option), 0, 200),
            $options
        )));
        $optionLines = array_slice($optionLines, 0, 10);
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
                Poll::instance()->addOption($pollId, $text, $i);
            }
        }
    }

    private function sendJsonHeaders(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
    }

    private function sendJson(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
