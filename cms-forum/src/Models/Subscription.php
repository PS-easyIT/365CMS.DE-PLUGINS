<?php
/**
 * CMS Forum – Subscription Model
 *
 * Thread- und Forum-Abonnements.
 *
 * @package CMS_Forum\Models
 */

declare(strict_types=1);

namespace CMS_Forum\Models;

if (!defined('ABSPATH')) {
    exit;
}

final class Subscription
{
    public const string TABLE = 'cmsforum_subscriptions';

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
     * Prüfe ob Benutzer ein Ziel abonniert hat.
     */
    public function isSubscribed(int $userId, string $targetType, int $targetId): bool
    {
        $stmt = $this->db()->prepare(
            "SELECT COUNT(*) FROM {$this->table()} WHERE user_id = ? AND target_type = ? AND target_id = ?"
        );
        $stmt->execute([$userId, $targetType, $targetId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Abonnement hinzufügen.
     */
    public function subscribe(int $userId, string $targetType, int $targetId, bool $notifyEmail = true): bool
    {
        if ($this->isSubscribed($userId, $targetType, $targetId)) {
            return true;
        }

        $stmt = $this->db()->prepare(
            "INSERT INTO {$this->table()} (user_id, target_type, target_id, notify_email) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$userId, $targetType, $targetId, (int) $notifyEmail]);
    }

    /**
     * Abonnement entfernen.
     */
    public function unsubscribe(int $userId, string $targetType, int $targetId): bool
    {
        $stmt = $this->db()->prepare(
            "DELETE FROM {$this->table()} WHERE user_id = ? AND target_type = ? AND target_id = ?"
        );
        return $stmt->execute([$userId, $targetType, $targetId]);
    }

    /**
     * Alle Abonnenten eines Ziels laden (für Benachrichtigungen).
     *
     * @return array<int, object>
     */
    public function findSubscribers(string $targetType, int $targetId): array
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "SELECT s.*, u.username, u.email
             FROM {$this->table()} s
             LEFT JOIN {$p}users u ON u.id = s.user_id
             WHERE s.target_type = ? AND s.target_id = ? AND s.notify_email = 1"
        );
        $stmt->execute([$targetType, $targetId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Alle Abos eines Benutzers laden.
     *
     * @return array<int, object>
     */
    public function findByUser(int $userId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM {$this->table()} WHERE user_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
