<?php
/**
 * CMS Forum – Post Model
 *
 * @package CMS_Forum\Models
 */

declare(strict_types=1);

namespace CMS_Forum\Models;

if (!defined('ABSPATH')) {
    exit;
}

final class Post
{
    public const string TABLE    = 'cmsforum_posts';
    public const int    PER_PAGE = 15;

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
     * Beiträge eines Threads laden (mit User-Daten, paginiert).
     *
     * @return array<int, object>
     */
    public function findByThread(int $threadId, int $offset = 0, int $limit = self::PER_PAGE): array
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "SELECT po.*, u.username, u.email AS user_email,
                    um.post_count, um.custom_title, um.signature, um.location,
                    r.name AS rank_name, r.color AS rank_color
             FROM {$this->table()} po
             LEFT JOIN {$p}users u ON u.id = po.user_id
             LEFT JOIN {$p}cmsforum_user_meta um ON um.user_id = po.user_id
             LEFT JOIN {$p}cmsforum_ranks r ON r.id = um.rank_id
             WHERE po.thread_id = ? AND po.is_deleted = 0 AND po.is_approved = 1
             ORDER BY po.created_at ASC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$threadId, $limit, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Gesamtanzahl Beiträge in einem Thread.
     */
    public function countByThread(int $threadId): int
    {
        $stmt = $this->db()->prepare(
            "SELECT COUNT(*) FROM {$this->table()} WHERE thread_id = ? AND is_deleted = 0 AND is_approved = 1"
        );
        $stmt->execute([$threadId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Einzelnen Beitrag per ID laden.
     */
    public function findById(int $id): ?object
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "SELECT po.*, u.username, u.email AS user_email
             FROM {$this->table()} po
             LEFT JOIN {$p}users u ON u.id = po.user_id
             WHERE po.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /**
     * Neuen Beitrag erstellen.
     */
    public function create(array $data): int
    {
        $stmt = $this->db()->prepare(
            "INSERT INTO {$this->table()} (thread_id, user_id, content, content_html, ip_address, is_first_post, is_approved)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            (int) $data['thread_id'],
            (int) $data['user_id'],
            $data['content'],
            $data['content_html'] ?? null,
            $data['ip_address'] ?? null,
            (int) ($data['is_first_post'] ?? 0),
            (int) ($data['is_approved'] ?? 1),
        ]);
        return (int) $this->db()->getPdo()->lastInsertId();
    }

    /**
     * Beitrag bearbeiten.
     */
    public function update(int $id, string $content, string $contentHtml, int $editedBy): bool
    {
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET content = ?, content_html = ?, edit_count = edit_count + 1,
             edited_by = ?, edited_at = NOW(), updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$content, $contentHtml, $editedBy, $id]);
    }

    /**
     * Beitrag soft-deleten.
     */
    public function softDelete(int $id): bool
    {
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET is_deleted = 1, updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    /**
     * Beitrag wiederherstellen.
     */
    public function restore(int $id): bool
    {
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET is_deleted = 0, updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    /**
     * Like-Zähler aktualisieren.
     */
    public function refreshLikeCount(int $postId): void
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET like_count = (SELECT COUNT(*) FROM {$p}cmsforum_likes WHERE post_id = ?) WHERE id = ?"
        );
        $stmt->execute([$postId, $postId]);
    }

    /**
     * Neueste Beiträge eines Benutzers laden.
     *
     * @return array<int, object>
     */
    public function findByUser(int $userId, int $offset = 0, int $limit = 20): array
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "SELECT po.*, t.title AS thread_title, t.slug AS thread_slug, t.forum_id
             FROM {$this->table()} po
             LEFT JOIN {$p}cmsforum_threads t ON t.id = po.thread_id
             WHERE po.user_id = ? AND po.is_deleted = 0
             ORDER BY po.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Beiträge zur Moderation (nicht genehmigt).
     *
     * @return array<int, object>
     */
    public function findUnapproved(int $offset = 0, int $limit = 20): array
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "SELECT po.*, u.username, t.title AS thread_title
             FROM {$this->table()} po
             LEFT JOIN {$p}users u ON u.id = po.user_id
             LEFT JOIN {$p}cmsforum_threads t ON t.id = po.thread_id
             WHERE po.is_approved = 0 AND po.is_deleted = 0
             ORDER BY po.created_at ASC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Beitrag genehmigen (Moderation).
     */
    public function approve(int $id): bool
    {
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET is_approved = 1, updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    /**
     * Gesamtanzahl Beiträge.
     */
    public function countAll(): int
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM {$this->table()} WHERE is_deleted = 0");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Beiträge heute.
     */
    public function countToday(): int
    {
        $stmt = $this->db()->prepare(
            "SELECT COUNT(*) FROM {$this->table()} WHERE is_deleted = 0 AND DATE(created_at) = CURDATE()"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}
