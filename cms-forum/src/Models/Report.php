<?php
/**
 * CMS Forum – Report Model
 *
 * @package CMS_Forum\Models
 */

declare(strict_types=1);

namespace CMS_Forum\Models;

if (!defined('ABSPATH')) {
    exit;
}

final class Report
{
    public const string TABLE   = 'cmsforum_reports';
    public const array  REASONS = ['spam', 'offensive', 'off-topic', 'harassment', 'misinformation', 'other'];

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
        return $this->db()->prefix() . self::TABLE;
    }

    /**
     * Neue Meldung erstellen.
     */
    public function create(array $data): int
    {
        $stmt = $this->db()->prepare(
            "INSERT INTO {$this->table()} (post_id, user_id, reason, description) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            (int) $data['post_id'],
            (int) $data['user_id'],
            $data['reason'] ?? 'other',
            $data['description'] ?? null,
        ]);
        return (int) $this->db()->getPdo()->lastInsertId();
    }

    /**
     * Offene Meldungen laden.
     *
     * @return array<int, object>
     */
    public function findOpen(int $offset = 0, int $limit = 20): array
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "SELECT r.*, u.username AS reporter_name, po.content AS post_content,
                    t.title AS thread_title, t.id AS thread_id
             FROM {$this->table()} r
             LEFT JOIN {$p}users u ON u.id = r.user_id
             LEFT JOIN {$p}cmsforum_posts po ON po.id = r.post_id
             LEFT JOIN {$p}cmsforum_threads t ON t.id = po.thread_id
             WHERE r.status = 'open'
             ORDER BY r.created_at ASC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Meldung bearbeiten (schließen).
     */
    public function resolve(int $id, int $handledBy, string $status = 'resolved'): bool
    {
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET status = ?, handled_by = ?, handled_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$status, $handledBy, $id]);
    }

    /**
     * Anzahl offener Meldungen.
     */
    public function countOpen(): int
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM {$this->table()} WHERE status = 'open'");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Hat Benutzer diesen Beitrag bereits gemeldet?
     */
    public function hasReported(int $postId, int $userId): bool
    {
        $stmt = $this->db()->prepare(
            "SELECT COUNT(*) FROM {$this->table()} WHERE post_id = ? AND user_id = ?"
        );
        $stmt->execute([$postId, $userId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Meldung per ID laden.
     */
    public function findById(int $id): ?object
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->table()} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }
}
