<?php
/**
 * CMS Forum – Like Model
 *
 * @package CMS_Forum\Models
 */

declare(strict_types=1);

namespace CMS_Forum\Models;

if (!defined('ABSPATH')) {
    exit;
}

final class Like
{
    public const string TABLE = 'cmsforum_likes';

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
     * Like hinzufügen.
     */
    public function add(int $postId, int $userId): bool
    {
        $stmt = $this->db()->prepare(
            "INSERT IGNORE INTO {$this->table()} (post_id, user_id) VALUES (?, ?)"
        );
        return $stmt->execute([$postId, $userId]);
    }

    /**
     * Like entfernen.
     */
    public function remove(int $postId, int $userId): bool
    {
        $stmt = $this->db()->prepare(
            "DELETE FROM {$this->table()} WHERE post_id = ? AND user_id = ?"
        );
        return $stmt->execute([$postId, $userId]);
    }

    /**
     * Hat Benutzer den Beitrag geliked?
     */
    public function hasLiked(int $postId, int $userId): bool
    {
        $stmt = $this->db()->prepare(
            "SELECT COUNT(*) FROM {$this->table()} WHERE post_id = ? AND user_id = ?"
        );
        $stmt->execute([$postId, $userId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Anzahl Likes eines Beitrags.
     */
    public function countByPost(int $postId): int
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM {$this->table()} WHERE post_id = ?");
        $stmt->execute([$postId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Like-Toggle: Hinzufügen oder Entfernen.
     *
     * @return bool True wenn geliked, false wenn entfernt.
     */
    public function toggle(int $postId, int $userId): bool
    {
        if ($this->hasLiked($postId, $userId)) {
            $this->remove($postId, $userId);
            return false;
        }
        $this->add($postId, $userId);
        return true;
    }
}
