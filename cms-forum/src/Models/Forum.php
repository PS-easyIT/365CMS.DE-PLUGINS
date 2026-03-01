<?php
/**
 * CMS Forum – Forum Model
 *
 * @package CMS_Forum\Models
 */

declare(strict_types=1);

namespace CMS_Forum\Models;

if (!defined('ABSPATH')) {
    exit;
}

final class Forum
{
    public const string TABLE    = 'cmsforum_forums';
    public const int    PER_PAGE = 20;

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
     * Alle aktiven Foren einer Kategorie laden.
     *
     * @return array<int, object>
     */
    public function findByCategory(int $categoryId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM {$this->table()} WHERE category_id = ? AND is_active = 1 ORDER BY sort_order ASC, name ASC"
        );
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Alle Foren laden (Admin).
     *
     * @return array<int, object>
     */
    public function findAll(): array
    {
        $stmt = $this->db()->prepare(
            "SELECT f.*, c.name AS category_name
             FROM {$this->table()} f
             LEFT JOIN {$this->db()->prefix()}cmsforum_categories c ON c.id = f.category_id
             ORDER BY c.sort_order ASC, f.sort_order ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Subforen eines Forums laden.
     *
     * @return array<int, object>
     */
    public function findSubforums(int $parentId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM {$this->table()} WHERE parent_id = ? AND is_active = 1 ORDER BY sort_order ASC"
        );
        $stmt->execute([$parentId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Forum per ID laden.
     */
    public function findById(int $id): ?object
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->table()} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /**
     * Forum per Slug laden.
     */
    public function findBySlug(string $slug): ?object
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->table()} WHERE slug = ? AND is_active = 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /**
     * Neues Forum erstellen.
     */
    public function create(array $data): int
    {
        $stmt = $this->db()->prepare(
            "INSERT INTO {$this->table()} (category_id, parent_id, name, slug, description, icon, sort_order, is_active, is_locked)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            (int) $data['category_id'],
            !empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            $data['name'],
            $data['slug'],
            $data['description'] ?? null,
            $data['icon'] ?? null,
            (int) ($data['sort_order'] ?? 0),
            (int) ($data['is_active'] ?? 1),
            (int) ($data['is_locked'] ?? 0),
        ]);
        return (int) $this->db()->getPdo()->lastInsertId();
    }

    /**
     * Forum aktualisieren.
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET category_id = ?, parent_id = ?, name = ?, slug = ?, description = ?,
             icon = ?, sort_order = ?, is_active = ?, is_locked = ?, updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([
            (int) $data['category_id'],
            !empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            $data['name'],
            $data['slug'],
            $data['description'] ?? null,
            $data['icon'] ?? null,
            (int) ($data['sort_order'] ?? 0),
            (int) ($data['is_active'] ?? 1),
            (int) ($data['is_locked'] ?? 0),
            $id,
        ]);
    }

    /**
     * Forum löschen.
     */
    public function delete(int $id): bool
    {
        // Prüfe ob Threads existieren
        $threadTable = $this->db()->prefix() . Thread::TABLE;
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM {$threadTable} WHERE forum_id = ?");
        $stmt->execute([$id]);
        if ((int) $stmt->fetchColumn() > 0) {
            return false;
        }

        $stmt = $this->db()->prepare("DELETE FROM {$this->table()} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Zähler aktualisieren (Thread-/Post-Count, letzter Beitrag).
     */
    public function refreshCounters(int $forumId): void
    {
        $p = $this->db()->prefix();

        // Thread-Anzahl
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET
                thread_count = (SELECT COUNT(*) FROM {$p}cmsforum_threads WHERE forum_id = ? AND status != 'deleted'),
                post_count = (
                    SELECT COUNT(*) FROM {$p}cmsforum_posts po
                    JOIN {$p}cmsforum_threads t ON t.id = po.thread_id
                    WHERE t.forum_id = ? AND po.is_deleted = 0
                ),
                last_post_id = (
                    SELECT po.id FROM {$p}cmsforum_posts po
                    JOIN {$p}cmsforum_threads t ON t.id = po.thread_id
                    WHERE t.forum_id = ? AND po.is_deleted = 0
                    ORDER BY po.created_at DESC LIMIT 1
                ),
                last_post_at = (
                    SELECT po.created_at FROM {$p}cmsforum_posts po
                    JOIN {$p}cmsforum_threads t ON t.id = po.thread_id
                    WHERE t.forum_id = ? AND po.is_deleted = 0
                    ORDER BY po.created_at DESC LIMIT 1
                )
            WHERE id = ?"
        );
        $stmt->execute([$forumId, $forumId, $forumId, $forumId, $forumId]);
    }

    /**
     * Sortierung aktualisieren.
     *
     * @param array<int, int> $order [id => sort_order]
     */
    public function updateSortOrder(array $order): void
    {
        $stmt = $this->db()->prepare("UPDATE {$this->table()} SET sort_order = ? WHERE id = ?");
        foreach ($order as $id => $position) {
            $stmt->execute([(int) $position, (int) $id]);
        }
    }

    /**
     * Gesamtanzahl Foren.
     */
    public function countAll(): int
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM {$this->table()}");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}
