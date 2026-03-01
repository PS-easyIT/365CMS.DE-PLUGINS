<?php
/**
 * CMS Forum – Flood Control
 *
 * Verhindert Spam durch Zeitlimitierung zwischen Beiträgen/Threads.
 *
 * @package CMS_Forum\Services
 */

declare(strict_types=1);

namespace CMS_Forum\Services;

if (!defined('ABSPATH')) {
    exit;
}

final class FloodControl
{
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

    /**
     * Prüfe ob der Benutzer einen neuen Beitrag schreiben darf.
     *
     * @return int Verbleibende Sekunden (0 = erlaubt)
     */
    public function checkPost(int $userId): int
    {
        return $this->check($userId, 'post', $this->getInterval('flood_interval_post', 30));
    }

    /**
     * Prüfe ob der Benutzer einen neuen Thread erstellen darf.
     *
     * @return int Verbleibende Sekunden (0 = erlaubt)
     */
    public function checkThread(int $userId): int
    {
        return $this->check($userId, 'thread', $this->getInterval('flood_interval_thread', 120));
    }

    /**
     * Generische Flood-Prüfung.
     */
    private function check(int $userId, string $type, int $intervalSeconds): int
    {
        if ($intervalSeconds <= 0) {
            return 0;
        }

        // Admins sind vom Flood-Control ausgenommen
        if (\CMS\Auth::instance()->isAdmin()) {
            return 0;
        }

        $p = $this->db()->prefix();

        $table = match ($type) {
            'post'   => "{$p}cmsforum_posts",
            'thread' => "{$p}cmsforum_threads",
            default  => "{$p}cmsforum_posts",
        };

        $stmt = $this->db()->prepare(
            "SELECT created_at FROM {$table} WHERE user_id = ? ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([$userId]);
        $lastCreated = $stmt->fetchColumn();

        if (!$lastCreated) {
            return 0;
        }

        $lastTime = new \DateTimeImmutable($lastCreated);
        $now      = new \DateTimeImmutable();
        $diff     = $now->getTimestamp() - $lastTime->getTimestamp();
        $remaining = $intervalSeconds - $diff;

        return max(0, $remaining);
    }

    /**
     * Flood-Intervall aus den Einstellungen.
     */
    private function getInterval(string $key, int $default): int
    {
        try {
            $p = $this->db()->prefix();
            $stmt = $this->db()->prepare("SELECT setting_value FROM {$p}cmsforum_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return $val !== false ? (int) $val : $default;
        } catch (\PDOException) {
            return $default;
        }
    }
}
