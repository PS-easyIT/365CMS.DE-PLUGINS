<?php
/**
 * CMS Forum – Thread Model
 *
 * @package CMS_Forum\Models
 */

declare(strict_types=1);

namespace CMS_Forum\Models;

if (!defined('ABSPATH')) {
    exit;
}

final class Thread
{
    public const string TABLE      = 'cmsforum_threads';
    public const int    PER_PAGE   = 20;
    public const array  STATUSES   = ['open', 'closed', 'deleted'];
    public const array  TYPES      = ['normal', 'sticky', 'announcement'];

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
     * Threads eines Forums laden (mit Paginierung).
     * Gepinnte Threads zuerst, dann nach letztem Beitrag sortiert.
     *
     * @return array<int, object>
     */
    public function findByForum(int $forumId, int $offset = 0, int $limit = self::PER_PAGE): array
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "SELECT t.*, u.username, u.email AS user_email,
                    lp.username AS last_poster_name
             FROM {$this->table()} t
             LEFT JOIN {$p}users u ON u.id = t.user_id
             LEFT JOIN {$p}users lp ON lp.id = t.last_poster_id
             WHERE t.forum_id = ? AND t.status != 'deleted'
             ORDER BY t.is_pinned DESC, t.last_post_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$forumId, $limit, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Gesamtanzahl Threads in einem Forum.
     */
    public function countByForum(int $forumId): int
    {
        $stmt = $this->db()->prepare(
            "SELECT COUNT(*) FROM {$this->table()} WHERE forum_id = ? AND status != 'deleted'"
        );
        $stmt->execute([$forumId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Thread per ID laden.
     */
    public function findById(int $id): ?object
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "SELECT t.*, u.username, u.email AS user_email
             FROM {$this->table()} t
             LEFT JOIN {$p}users u ON u.id = t.user_id
             WHERE t.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /**
     * Ähnliche Threads anhand Titel für Dubletten-Hinweis.
     *
     * @return array<int, object>
     */
    public function findSimilarByTitle(int $forumId, string $title, int $excludeThreadId = 0, int $limit = 5): array
    {
        $title = mb_substr(trim($title), 0, 200);
        if ($title === '') {
            return [];
        }

        $limit = max(1, min(10, $limit));
        $term = '%' . $title . '%';
        $sql = "SELECT id, title, status, reply_count, view_count, last_post_at
                FROM {$this->table()}
                WHERE forum_id = ? AND status != 'deleted' AND title LIKE ?";
        $params = [$forumId, $term];

        if ($excludeThreadId > 0) {
            $sql .= " AND id != ?";
            $params[] = $excludeThreadId;
        }

        $sql .= " ORDER BY is_pinned DESC, last_post_at DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Neuen Thread erstellen.
     */
    public function create(array $data): int
    {
        $stmt = $this->db()->prepare(
            "INSERT INTO {$this->table()} (forum_id, user_id, title, slug, type, status, prefix, is_pinned, is_locked, has_poll, last_post_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            (int) $data['forum_id'],
            (int) $data['user_id'],
            $data['title'],
            $data['slug'],
            $data['type'] ?? 'normal',
            $data['status'] ?? 'open',
            $data['prefix'] ?? null,
            (int) ($data['is_pinned'] ?? 0),
            (int) ($data['is_locked'] ?? 0),
            (int) ($data['has_poll'] ?? 0),
        ]);
        return (int) $this->db()->getPdo()->lastInsertId();
    }

    /**
     * Thread aktualisieren.
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];

        foreach (['title', 'slug', 'type', 'status', 'prefix', 'forum_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }
        foreach (['is_pinned', 'is_locked', 'has_poll'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = (int) $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = NOW()";
        $values[] = $id;

        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET " . implode(', ', $fields) . " WHERE id = ?"
        );
        return $stmt->execute($values);
    }

    /**
     * Thread soft-deleten.
     */
    public function softDelete(int $id): bool
    {
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET status = 'deleted', updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    /**
     * View-Counter erhöhen.
     */
    public function incrementViews(int $id): void
    {
        $stmt = $this->db()->prepare("UPDATE {$this->table()} SET view_count = view_count + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    /**
     * Zähler aktualisieren nach neuem Beitrag.
     */
    public function refreshCounters(int $threadId): void
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET
                reply_count = (SELECT COUNT(*) - 1 FROM {$p}cmsforum_posts WHERE thread_id = ? AND is_deleted = 0),
                last_post_id = (SELECT id FROM {$p}cmsforum_posts WHERE thread_id = ? AND is_deleted = 0 ORDER BY created_at DESC LIMIT 1),
                last_post_at = (SELECT created_at FROM {$p}cmsforum_posts WHERE thread_id = ? AND is_deleted = 0 ORDER BY created_at DESC LIMIT 1),
                last_poster_id = (SELECT user_id FROM {$p}cmsforum_posts WHERE thread_id = ? AND is_deleted = 0 ORDER BY created_at DESC LIMIT 1)
            WHERE id = ?"
        );
        $stmt->execute([$threadId, $threadId, $threadId, $threadId, $threadId]);
    }

    public function markAcceptedPost(int $threadId, int $postId): bool
    {
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET accepted_post_id = ?, accepted_at = NOW(), updated_at = NOW() WHERE id = ?"
        );

        return $stmt->execute([$postId, $threadId]);
    }

    public function clearAcceptedPost(int $threadId): bool
    {
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET accepted_post_id = NULL, accepted_at = NULL, updated_at = NOW() WHERE id = ?"
        );

        return $stmt->execute([$threadId]);
    }

    /**
     * Neueste Threads global laden.
     *
     * @return array<int, object>
     */
    public function findLatest(int $limit = 10): array
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "SELECT t.*, u.username, f.name AS forum_name, f.slug AS forum_slug
             FROM {$this->table()} t
             LEFT JOIN {$p}users u ON u.id = t.user_id
             LEFT JOIN {$p}cmsforum_forums f ON f.id = t.forum_id
             WHERE t.status != 'deleted'
             ORDER BY t.last_post_at DESC
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Threads eines Benutzers laden.
     *
     * @return array<int, object>
     */
    public function findByUser(int $userId, int $offset = 0, int $limit = 20): array
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM {$this->table()} WHERE user_id = ? AND status != 'deleted' ORDER BY created_at DESC LIMIT ? OFFSET ?"
        );
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Gesamtanzahl Threads.
     */
    public function countAll(): int
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM {$this->table()} WHERE status != 'deleted'");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}
