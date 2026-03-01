<?php
/**
 * CMS Forum – Rank Model
 *
 * @package CMS_Forum\Models
 */

declare(strict_types=1);

namespace CMS_Forum\Models;

if (!defined('ABSPATH')) {
    exit;
}

final class Rank
{
    public const string TABLE = 'cmsforum_ranks';

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
     * Alle Ränge sortiert laden.
     *
     * @return array<int, object>
     */
    public function findAll(): array
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->table()} ORDER BY min_posts ASC, sort_order ASC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Rang per ID laden.
     */
    public function findById(int $id): ?object
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->table()} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /**
     * Passenden Rang für eine Beitragszahl ermitteln.
     */
    public function findForPostCount(int $postCount): ?object
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM {$this->table()} WHERE is_special = 0 AND min_posts <= ? ORDER BY min_posts DESC LIMIT 1"
        );
        $stmt->execute([$postCount]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /**
     * Neuen Rang erstellen.
     */
    public function create(array $data): int
    {
        $stmt = $this->db()->prepare(
            "INSERT INTO {$this->table()} (name, min_posts, color, icon, is_special, sort_order) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['name'],
            (int) ($data['min_posts'] ?? 0),
            $data['color'] ?? null,
            $data['icon'] ?? null,
            (int) ($data['is_special'] ?? 0),
            (int) ($data['sort_order'] ?? 0),
        ]);
        return (int) $this->db()->getPdo()->lastInsertId();
    }

    /**
     * Rang aktualisieren.
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET name = ?, min_posts = ?, color = ?, icon = ?, is_special = ?, sort_order = ? WHERE id = ?"
        );
        return $stmt->execute([
            $data['name'],
            (int) ($data['min_posts'] ?? 0),
            $data['color'] ?? null,
            $data['icon'] ?? null,
            (int) ($data['is_special'] ?? 0),
            (int) ($data['sort_order'] ?? 0),
            $id,
        ]);
    }

    /**
     * Rang löschen.
     */
    public function delete(int $id): bool
    {
        // Rang von Benutzern entfernen
        $metaTable = $this->db()->prefix() . UserMeta::TABLE;
        $this->db()->prepare("UPDATE {$metaTable} SET rank_id = NULL WHERE rank_id = ?")->execute([$id]);

        $stmt = $this->db()->prepare("DELETE FROM {$this->table()} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
