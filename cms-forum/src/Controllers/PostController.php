<?php
/**
 * CMS Forum – Post Controller
 *
 * Beitrag bearbeiten, liken, melden.
 *
 * @package CMS_Forum\Controllers
 */

declare(strict_types=1);

namespace CMS_Forum\Controllers;

if (!defined('ABSPATH')) {
    exit;
}

use CMS_Forum\Models\Post;
use CMS_Forum\Models\Thread;
use CMS_Forum\Models\Forum;
use CMS_Forum\Models\Like;
use CMS_Forum\Models\Report;
use CMS_Forum\Models\Attachment;
use CMS_Forum\Services\PermissionService;

final class PostController
{
    private static ?self $instance = null;

    public static function instance(): static
    {
        return static::$instance ??= new static();
    }

    private function __construct() {}

    /**
     * Beitrag bearbeiten.
     */
    public function edit(int $postId): void
    {
        $post = Post::instance()->findById($postId);
        if (!$post || $post->is_deleted) {
            http_response_code(404);
            \CMS\ThemeManager::instance()->render('404');
            return;
        }

        $thread = Thread::instance()->findById((int) $post->thread_id);
        $forum  = $thread ? Forum::instance()->findById((int) $thread->forum_id) : null;

        if (!$thread || !$forum) {
            http_response_code(404);
            \CMS\ThemeManager::instance()->render('404');
            return;
        }

        $auth = \CMS\Auth::instance();
        if (!$auth->isLoggedIn()) {
            header('Location: ' . rtrim((string) SITE_URL, '/') . '/login', true, 303);
            exit;
        }

        // Berechtigung prüfen
        if (!PermissionService::instance()->canEditOwnPost((int) $forum->id, (int) $post->user_id, $post->created_at)) {
            http_response_code(403);
            \CMS\ThemeManager::instance()->getHeader();
            echo '<div class="cmsforum-error"><h2>Zugriff verweigert</h2><p>Du darfst diesen Beitrag nicht bearbeiten.</p></div>';
            \CMS\ThemeManager::instance()->getFooter();
            return;
        }

        $error   = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_post') {
            if (!\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'forum_edit_post')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $content = mb_substr(trim((string) ($_POST['content'] ?? '')), 0, 50000);
                if (empty($content)) {
                    $error = 'Bitte gib einen Beitrag ein.';
                } else {
                    $parser = \CMS_Forum\Services\BBCodeParser::instance();
                    Post::instance()->update(
                        $postId,
                        $content,
                        $parser->parse($content),
                        (int)$auth->currentUser()->id
                    );

                    header('Location: ' . rtrim((string) SITE_URL, '/') . '/forum/thread/' . (int) $thread->id . '#post-' . $postId, true, 303);
                    exit;
                }
            }
        }

        $csrfToken = \CMS\Security::instance()->generateToken('forum_edit_post');

        $viewData = [
            'post'      => $post,
            'thread'    => $thread,
            'forum'     => $forum,
            'csrfToken' => $csrfToken,
            'error'     => $error,
            'pageTitle' => 'Beitrag bearbeiten',
        ];

        extract($viewData, EXTR_SKIP);
        \CMS\ThemeManager::instance()->getHeader();
        include CMS_FORUM_DIR . 'views/frontend/post-edit.php';
        \CMS\ThemeManager::instance()->getFooter();
    }

    /**
     * Like/Unlike per AJAX.
     */
    public function toggleLike(): void
    {
        $this->sendJsonHeaders();
        $auth = \CMS\Auth::instance();

        if (!$auth->isLoggedIn()) {
            $this->sendJson(['success' => false, 'error' => 'Nicht eingeloggt.'], 401);
            exit;
        }

        if (!\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'forum_like')) {
            $this->sendJson(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.'], 403);
            exit;
        }

        $postId = (int) ($_POST['post_id'] ?? 0);
        if ($postId <= 0) {
            $this->sendJson(['success' => false, 'error' => 'Ungültige Anfrage.'], 400);
            exit;
        }

        $post = Post::instance()->findById($postId);
        if (!$post || (int) ($post->is_deleted ?? 0) === 1) {
            $this->sendJson(['success' => false, 'error' => 'Beitrag nicht gefunden.'], 404);
            exit;
        }

        $userId = (int)$auth->currentUser()->id;
        $result = Like::instance()->toggle($postId, $userId);

        // Like-Zähler aktualisieren
        Post::instance()->refreshLikeCount($postId);

        echo json_encode([
            'success' => true,
            'liked'   => $result,
            'count'   => (int) (Post::instance()->findById($postId)->like_count ?? 0),
        ]);
        exit;
    }

    /**
     * Beitrag melden per AJAX.
     */
    public function report(): void
    {
        $this->sendJsonHeaders();
        $auth = \CMS\Auth::instance();

        if (!$auth->isLoggedIn()) {
            $this->sendJson(['success' => false, 'error' => 'Nicht eingeloggt.'], 401);
            exit;
        }

        if (!\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'forum_report')) {
            $this->sendJson(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.'], 403);
            exit;
        }

        $postId = (int) ($_POST['post_id'] ?? 0);
        $reason = in_array($_POST['reason'] ?? '', Report::REASONS, true) ? $_POST['reason'] : 'other';
        $detail = mb_substr(sanitize_text_field((string) ($_POST['detail'] ?? '')), 0, 500);

        if ($postId <= 0) {
            $this->sendJson(['success' => false, 'error' => 'Ungültige Anfrage.'], 400);
            exit;
        }

        $post = Post::instance()->findById($postId);
        if (!$post || (int) ($post->is_deleted ?? 0) === 1) {
            $this->sendJson(['success' => false, 'error' => 'Beitrag nicht gefunden.'], 404);
            exit;
        }

        $reportId = Report::instance()->create([
            'post_id'     => $postId,
            'user_id'     => (int)$auth->currentUser()->id,
            'reason'      => $reason,
            'description' => $detail,
        ]);

        $this->sendJson(['success' => (bool) $reportId]);
        exit;
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
}
