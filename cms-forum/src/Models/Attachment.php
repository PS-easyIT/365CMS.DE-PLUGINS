<?php
/**
 * CMS Forum – Attachment Model
 *
 * @package CMS_Forum\Models
 */

declare(strict_types=1);

namespace CMS_Forum\Models;

if (!defined('ABSPATH')) {
    exit;
}

final class Attachment
{
    public const string TABLE = 'cmsforum_attachments';

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
     * Anhänge eines Beitrags laden.
     *
     * @return array<int, object>
     */
    public function findByPost(int $postId): array
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->table()} WHERE post_id = ? ORDER BY created_at ASC");
        $stmt->execute([$postId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Anhang per ID laden.
     */
    public function findById(int $id): ?object
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->table()} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /**
     * Neuen Anhang erstellen.
     */
    public function create(array $data): int
    {
        $stmt = $this->db()->prepare(
            "INSERT INTO {$this->table()} (post_id, user_id, filename, original_name, mime_type, file_size) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            (int) $data['post_id'],
            (int) $data['user_id'],
            $data['filename'],
            $data['original_name'],
            $data['mime_type'],
            (int) $data['file_size'],
        ]);
        return (int) $this->db()->getPdo()->lastInsertId();
    }

    /**
     * Anhang löschen.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db()->prepare("DELETE FROM {$this->table()} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Download-Zähler erhöhen.
     */
    public function incrementDownloads(int $id): void
    {
        $stmt = $this->db()->prepare("UPDATE {$this->table()} SET download_count = download_count + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }
}
