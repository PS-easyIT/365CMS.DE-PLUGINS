<?php
/**
 * CMS Forum – Read Tracker
 *
 * Gelesen/Ungelesen-Tracking für Threads.
 *
 * @package CMS_Forum\Services
 */

declare(strict_types=1);

namespace CMS_Forum\Services;

if (!defined('ABSPATH')) {
    exit;
}

final class ReadTracker
{
    private static ?self $instance = null;

    public static function instance(): static
    {
        return static::$instance ??= new static();
    }

    private function __construct() {}

    private function db(): \CMS\Database
    {
        return \CMS\Database::instance();
    }

    private function table(): string
    {
        return $this->db()->prefix() . 'cmsforum_read_tracking';
    }

    /**
     * Thread als gelesen markieren.
     */
    public function markRead(int $userId, int $threadId): void
    {
        $stmt = $this->db()->prepare(
            "INSERT INTO {$this->table()} (user_id, thread_id, last_read_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE last_read_at = NOW()"
        );
        $stmt->execute([$userId, $threadId]);
    }

    /**
     * Prüfe ob ein Thread ungelesene Beiträge hat.
     */
    public function hasUnread(int $userId, int $threadId, string $lastPostAt): bool
    {
        $stmt = $this->db()->prepare(
            "SELECT last_read_at FROM {$this->table()} WHERE user_id = ? AND thread_id = ?"
        );
        $stmt->execute([$userId, $threadId]);
        $lastRead = $stmt->fetchColumn();

        if (!$lastRead) {
            return true; // Noch nie gelesen
        }

        return $lastPostAt > $lastRead;
    }

    /**
     * Alle Threads eines Forums mit Gelesen-Status anreichern.
     *
     * @param array<object> $threads
     * @return array<object> Threads mit zusätzlichem `is_unread`-Property
     */
    public function enrichThreads(int $userId, array $threads): array
    {
        if (empty($threads)) {
            return $threads;
        }

        $threadIds = array_map(fn($t) => (int) $t->id, $threads);
        $placeholders = implode(',', array_fill(0, count($threadIds), '?'));

        $stmt = $this->db()->prepare(
            "SELECT thread_id, last_read_at FROM {$this->table()} WHERE user_id = ? AND thread_id IN ({$placeholders})"
        );
        $stmt->execute([$userId, ...$threadIds]);
        $readMap = [];
        while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
            $readMap[(int) $row->thread_id] = $row->last_read_at;
        }

        foreach ($threads as $thread) {
            $tid = (int) $thread->id;
            $lastPostAt = $thread->last_post_at ?? $thread->created_at;

            if (!isset($readMap[$tid])) {
                $thread->is_unread = true;
            } else {
                $thread->is_unread = $lastPostAt > $readMap[$tid];
            }
        }

        return $threads;
    }

    /**
     * Alle Lesedaten eines Benutzers löschen (z.B. für "Alle als gelesen markieren").
     */
    public function markAllRead(int $userId): void
    {
        // Alle aktuellen Threads als gelesen eintragen
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "INSERT INTO {$this->table()} (user_id, thread_id, last_read_at)
             SELECT ?, id, NOW() FROM {$p}cmsforum_threads WHERE status != 'deleted'
             ON DUPLICATE KEY UPDATE last_read_at = NOW()"
        );
        $stmt->execute([$userId]);
    }
}
