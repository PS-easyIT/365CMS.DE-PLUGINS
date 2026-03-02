<?php
/**
 * CMS Forum – Moderator Controller
 *
 * Thread-Moderation: Verschieben, Schließen, Pinnen, Löschen,
 * Post-Moderation: Löschen, Wiederherstellen, Freigeben.
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
use CMS_Forum\Models\ModLog;
use CMS_Forum\Models\Report;
use CMS_Forum\Services\PermissionService;

final class ModeratorController
{
    private static ?self $instance = null;

    public static function instance(): static
    {
        return static::$instance ??= new static();
    }

    private function __construct() {}

    /**
     * Thread schließen / öffnen.
     */
    public function toggleThreadLock(int $threadId): array
    {
        $thread = Thread::instance()->findById($threadId);
        if (!$thread) {
            return ['success' => false, 'error' => 'Thread nicht gefunden.'];
        }

        if (!$this->canModerate((int) $thread->forum_id)) {
            return ['success' => false, 'error' => 'Keine Berechtigung.'];
        }

        $newStatus = $thread->status === 'closed' ? 'open' : 'closed';
        Thread::instance()->update($threadId, ['status' => $newStatus]);

        $this->log('thread_' . ($newStatus === 'closed' ? 'lock' : 'unlock'), $threadId, null);

        return ['success' => true, 'status' => $newStatus];
    }

    /**
     * Thread pinnen / entpinnen.
     */
    public function toggleThreadPin(int $threadId): array
    {
        $thread = Thread::instance()->findById($threadId);
        if (!$thread) {
            return ['success' => false, 'error' => 'Thread nicht gefunden.'];
        }

        if (!$this->canModerate((int) $thread->forum_id)) {
            return ['success' => false, 'error' => 'Keine Berechtigung.'];
        }

        $newType = $thread->type === 'sticky' ? 'normal' : 'sticky';
        Thread::instance()->update($threadId, ['type' => $newType]);

        $this->log('thread_' . ($newType === 'sticky' ? 'pin' : 'unpin'), $threadId, null);

        return ['success' => true, 'type' => $newType];
    }

    /**
     * Thread soft-delete.
     */
    public function deleteThread(int $threadId): array
    {
        $thread = Thread::instance()->findById($threadId);
        if (!$thread) {
            return ['success' => false, 'error' => 'Thread nicht gefunden.'];
        }

        if (!$this->canModerate((int) $thread->forum_id)) {
            return ['success' => false, 'error' => 'Keine Berechtigung.'];
        }

        Thread::instance()->softDelete($threadId);
        Forum::instance()->refreshCounters((int) $thread->forum_id);

        $this->log('thread_delete', $threadId, null, 'Thread gelöscht: ' . $thread->title);

        return ['success' => true];
    }

    /**
     * Thread in anderes Forum verschieben.
     */
    public function moveThread(int $threadId, int $targetForumId): array
    {
        $thread = Thread::instance()->findById($threadId);
        if (!$thread) {
            return ['success' => false, 'error' => 'Thread nicht gefunden.'];
        }

        $targetForum = Forum::instance()->findById($targetForumId);
        if (!$targetForum) {
            return ['success' => false, 'error' => 'Zielforum nicht gefunden.'];
        }

        if (!$this->canModerate((int) $thread->forum_id) || !$this->canModerate($targetForumId)) {
            return ['success' => false, 'error' => 'Keine Berechtigung.'];
        }

        $oldForumId = (int) $thread->forum_id;
        Thread::instance()->update($threadId, ['forum_id' => $targetForumId]);

        // Zähler beider Foren aktualisieren
        Forum::instance()->refreshCounters($oldForumId);
        Forum::instance()->refreshCounters($targetForumId);

        $this->log('thread_move', $threadId, null, "Verschoben von Forum #{$oldForumId} nach Forum #{$targetForumId}");

        return ['success' => true];
    }

    /**
     * Post soft-delete.
     */
    public function deletePost(int $postId): array
    {
        $post = Post::instance()->findById($postId);
        if (!$post) {
            return ['success' => false, 'error' => 'Beitrag nicht gefunden.'];
        }

        $thread = Thread::instance()->findById((int) $post->thread_id);
        if (!$thread || !$this->canModerate((int) $thread->forum_id)) {
            return ['success' => false, 'error' => 'Keine Berechtigung.'];
        }

        Post::instance()->softDelete($postId);
        Thread::instance()->refreshCounters((int) $post->thread_id);
        Forum::instance()->refreshCounters((int) $thread->forum_id);

        $this->log('post_delete', (int) $post->thread_id, $postId);

        return ['success' => true];
    }

    /**
     * Post wiederherstellen.
     */
    public function restorePost(int $postId): array
    {
        $post = Post::instance()->findById($postId);
        if (!$post) {
            return ['success' => false, 'error' => 'Beitrag nicht gefunden.'];
        }

        $thread = Thread::instance()->findById((int) $post->thread_id);
        if (!$thread || !$this->canModerate((int) $thread->forum_id)) {
            return ['success' => false, 'error' => 'Keine Berechtigung.'];
        }

        Post::instance()->restore($postId);
        Thread::instance()->refreshCounters((int) $post->thread_id);
        Forum::instance()->refreshCounters((int) $thread->forum_id);

        $this->log('post_restore', (int) $post->thread_id, $postId);

        return ['success' => true];
    }

    /**
     * Beitrag freigeben (Moderation Queue).
     */
    public function approvePost(int $postId): array
    {
        $post = Post::instance()->findById($postId);
        if (!$post) {
            return ['success' => false, 'error' => 'Beitrag nicht gefunden.'];
        }

        $thread = Thread::instance()->findById((int) $post->thread_id);
        if (!$thread || !$this->canModerate((int) $thread->forum_id)) {
            return ['success' => false, 'error' => 'Keine Berechtigung.'];
        }

        Post::instance()->approve($postId);

        $this->log('post_approve', (int) $post->thread_id, $postId);

        return ['success' => true];
    }

    /**
     * Meldung bearbeiten.
     */
    public function resolveReport(int $reportId, string $resolution): array
    {
        $auth = \CMS\Auth::instance();
        if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
            return ['success' => false, 'error' => 'Keine Berechtigung.'];
        }

        Report::instance()->resolve($reportId, $auth->getUserId(), $resolution);

        return ['success' => true];
    }

    // ── Private Helfer ──────────────────────────────────────────────

    /**
     * Prüfen ob der aktuelle Benutzer Moderationsrechte hat.
     */
    private function canModerate(int $forumId): bool
    {
        $auth = \CMS\Auth::instance();
        if (!$auth->isLoggedIn()) {
            return false;
        }

        return $auth->isAdmin() || PermissionService::instance()->canModerate($forumId);
    }

    /**
     * Moderations-Log schreiben.
     */
    private function log(string $action, ?int $threadId = null, ?int $postId = null, string $details = ''): void
    {
        $auth = \CMS\Auth::instance();
        if (!$auth->isLoggedIn()) {
            return;
        }

        $targetType = $postId ? 'post' : 'thread';
        $targetId   = $postId ?? $threadId ?? 0;
        ModLog::instance()->log(
            (int) $auth->getUserId(),
            $action,
            $targetType,
            $targetId,
            !empty($details) ? $details : null
        );
    }
}
