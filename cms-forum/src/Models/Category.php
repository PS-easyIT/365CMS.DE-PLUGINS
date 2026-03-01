<?php
/**
 * CMS Forum – Category Model
 *
 * @package CMS_Forum\Models
 */

declare(strict_types=1);

namespace CMS_Forum\Models;

if (!defined('ABSPATH')) {
    exit;
}

final class Category
{
    public const string TABLE    = 'cmsforum_categories';
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
     * Alle aktiven Kategorien nach Sortierung laden.
     *
     * @return array<int, object>
     */
    public function findAllActive(): array
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM {$this->table()} WHERE is_active = 1 ORDER BY sort_order ASC, name ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Alle Kategorien laden (auch inaktive, für Admin).
     *
     * @return array<int, object>
     */
    public function findAll(): array
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM {$this->table()} ORDER BY sort_order ASC, name ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Einzelne Kategorie per ID laden.
     */
    public function findById(int $id): ?object
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->table()} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /**
     * Kategorie per Slug laden.
     */
    public function findBySlug(string $slug): ?object
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->table()} WHERE slug = ?");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /**
     * Neue Kategorie erstellen.
     */
    public function create(array $data): int
    {
        $stmt = $this->db()->prepare(
            "INSERT INTO {$this->table()} (name, slug, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['description'] ?? null,
            (int) ($data['sort_order'] ?? 0),
            (int) ($data['is_active'] ?? 1),
        ]);
        return (int) $this->db()->getPdo()->lastInsertId();
    }

    /**
     * Kategorie aktualisieren.
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET name = ?, slug = ?, description = ?, sort_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['description'] ?? null,
            (int) ($data['sort_order'] ?? 0),
            (int) ($data['is_active'] ?? 1),
            $id,
        ]);
    }

    /**
     * Kategorie löschen (und zugehörige Foren prüfen).
     */
    public function delete(int $id): bool
    {
        // Prüfe ob Foren in dieser Kategorie existieren
        $forumTable = $this->db()->prefix() . Forum::TABLE;
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM {$forumTable} WHERE category_id = ?");
        $stmt->execute([$id]);
        if ((int) $stmt->fetchColumn() > 0) {
            return false; // Foren müssen erst verschoben/gelöscht werden
        }

        $stmt = $this->db()->prepare("DELETE FROM {$this->table()} WHERE id = ?");
        return $stmt->execute([$id]);
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
     * Anzahl aktiver Kategorien.
     */
    public function countActive(): int
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM {$this->table()} WHERE is_active = 1");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}
