<?php
/**
 * CMS Forum – Permission Model
 *
 * @package CMS_Forum\Models
 */

declare(strict_types=1);

namespace CMS_Forum\Models;

if (!defined('ABSPATH')) {
    exit;
}

final class Permission
{
    public const string TABLE = 'cmsforum_permissions';

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
     * Berechtigung per Forum und Gruppentyp laden.
     */
    public function findByForumAndGroup(int $forumId, string $groupType): ?object
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM {$this->table()} WHERE forum_id = ? AND group_type = ?"
        );
        $stmt->execute([$forumId, $groupType]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /**
     * Alle Berechtigungen eines Forums laden.
     *
     * @return array<int, object>
     */
    public function findByForum(int $forumId): array
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->table()} WHERE forum_id = ?");
        $stmt->execute([$forumId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Berechtigung setzen (Insert oder Update).
     */
    public function upsert(int $forumId, string $groupType, array $perms): bool
    {
        $existing = $this->findByForumAndGroup($forumId, $groupType);

        if ($existing) {
            $stmt = $this->db()->prepare(
                "UPDATE {$this->table()} SET can_read = ?, can_post = ?, can_create = ?, can_edit_own = ?,
                 can_delete_own = ?, can_upload = ?, can_vote = ?, can_moderate = ?, updated_at = NOW() WHERE id = ?"
            );
            return $stmt->execute([
                (int) ($perms['can_read'] ?? 1),
                (int) ($perms['can_post'] ?? 1),
                (int) ($perms['can_create'] ?? 1),
                (int) ($perms['can_edit_own'] ?? 1),
                (int) ($perms['can_delete_own'] ?? 0),
                (int) ($perms['can_upload'] ?? 1),
                (int) ($perms['can_vote'] ?? 1),
                (int) ($perms['can_moderate'] ?? 0),
                $existing->id,
            ]);
        }

        $stmt = $this->db()->prepare(
            "INSERT INTO {$this->table()} (forum_id, group_type, can_read, can_post, can_create, can_edit_own, can_delete_own, can_upload, can_vote, can_moderate)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $forumId,
            $groupType,
            (int) ($perms['can_read'] ?? 1),
            (int) ($perms['can_post'] ?? 1),
            (int) ($perms['can_create'] ?? 1),
            (int) ($perms['can_edit_own'] ?? 1),
            (int) ($perms['can_delete_own'] ?? 0),
            (int) ($perms['can_upload'] ?? 1),
            (int) ($perms['can_vote'] ?? 1),
            (int) ($perms['can_moderate'] ?? 0),
        ]);
    }

    /**
     * Alle Berechtigungen laden (für Admin-Übersicht).
     *
     * @return array<int, object>
     */
    public function findAll(): array
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "SELECT pe.*, f.name AS forum_name
             FROM {$this->table()} pe
             LEFT JOIN {$p}cmsforum_forums f ON f.id = pe.forum_id
             ORDER BY f.name ASC, pe.group_type ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
