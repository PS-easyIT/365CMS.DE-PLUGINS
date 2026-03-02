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
            header('Location: ' . SITE_URL . '/login');
            exit;
        }

        // Berechtigung prüfen
        if (!PermissionService::instance()->canEditOwnPost((int) $forum->id, (int) $post->user_id, $post->created_at)) {
            http_response_code(403);
            echo '<div class="cmsforum-error"><h2>Zugriff verweigert</h2><p>Du darfst diesen Beitrag nicht bearbeiten.</p></div>';
            return;
        }

        $error   = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_post') {
            if (!\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'forum_edit_post')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $content = trim($_POST['content'] ?? '');
                if (empty($content)) {
                    $error = 'Bitte gib einen Beitrag ein.';
                } else {
                    $parser = \CMS_Forum\Services\BBCodeParser::instance();
                    Post::instance()->update(
                        $postId,
                        $content,
                        $parser->parse($content),
                        (int) $auth->getUserId()
                    );

                    header('Location: ' . SITE_URL . '/forum/thread/' . $thread->id . '#post-' . $postId);
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
        include CMS_FORUM_DIR . 'views/frontend/post-edit.php';
    }

    /**
     * Like/Unlike per AJAX.
     */
    public function toggleLike(): void
    {
        header('Content-Type: application/json');
        $auth = \CMS\Auth::instance();

        if (!$auth->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Nicht eingeloggt.']);
            exit;
        }

        if (!\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'forum_like')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.']);
            exit;
        }

        $postId = (int) ($_POST['post_id'] ?? 0);
        if ($postId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ungültige Anfrage.']);
            exit;
        }

        $userId = $auth->getUserId();
        $result = Like::instance()->toggle($postId, $userId);

        // Like-Zähler aktualisieren
        Post::instance()->refreshLikeCount($postId);

        echo json_encode([
            'success' => true,
            'liked'   => $result,
            'count'   => (int) Post::instance()->findById($postId)->like_count,
        ]);
        exit;
    }

    /**
     * Beitrag melden per AJAX.
     */
    public function report(): void
    {
        header('Content-Type: application/json');
        $auth = \CMS\Auth::instance();

        if (!$auth->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Nicht eingeloggt.']);
            exit;
        }

        if (!\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'forum_report')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.']);
            exit;
        }

        $postId = (int) ($_POST['post_id'] ?? 0);
        $reason = in_array($_POST['reason'] ?? '', Report::REASONS, true) ? $_POST['reason'] : 'other';
        $detail = sanitize_text_field($_POST['detail'] ?? '');

        if ($postId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ungültige Anfrage.']);
            exit;
        }

        $reportId = Report::instance()->create([
            'post_id'     => $postId,
            'user_id'     => $auth->getUserId(),
            'reason'      => $reason,
            'description' => $detail,
        ]);

        echo json_encode(['success' => (bool) $reportId]);
        exit;
    }
}
