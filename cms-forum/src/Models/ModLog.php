<?php
/**
 * CMS Forum – ModLog Model
 *
 * Moderationsprotokoll für alle Moderation-Aktionen.
 *
 * @package CMS_Forum\Models
 */

declare(strict_types=1);

namespace CMS_Forum\Models;

if (!defined('ABSPATH')) {
    exit;
}

final class ModLog
{
    public const string TABLE = 'cmsforum_mod_log';

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
     * Aktion protokollieren.
     */
    public function log(int $moderatorId, string $action, string $targetType, int $targetId, ?string $details = null): int
    {
        $stmt = $this->db()->prepare(
            "INSERT INTO {$this->table()} (moderator_id, action, target_type, target_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $moderatorId,
            $action,
            $targetType,
            $targetId,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
        return (int) $this->db()->getPdo()->lastInsertId();
    }

    /**
     * Protokoll laden (paginiert).
     *
     * @return array<int, object>
     */
    public function findAll(int $offset = 0, int $limit = 50): array
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "SELECT ml.*, u.username AS moderator_name
             FROM {$this->table()} ml
             LEFT JOIN {$p}users u ON u.id = ml.moderator_id
             ORDER BY ml.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Protokoll nach Moderator filtern.
     *
     * @return array<int, object>
     */
    public function findByModerator(int $moderatorId, int $offset = 0, int $limit = 50): array
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "SELECT ml.*, u.username AS moderator_name
             FROM {$this->table()} ml
             LEFT JOIN {$p}users u ON u.id = ml.moderator_id
             WHERE ml.moderator_id = ?
             ORDER BY ml.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$moderatorId, $limit, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Gesamtanzahl Einträge.
     */
    public function countAll(): int
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM {$this->table()}");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}
