<?php
/**
 * CMS Forum – Admin Controller
 *
 * AJAX-Endpunkte für Admin-Aktionen (wird vom Admin-Backend aufgerufen).
 * Kümmert sich um Massensperren, Rang-Verwaltung und globale Aktionen.
 *
 * @package CMS_Forum\Controllers
 */

declare(strict_types=1);

namespace CMS_Forum\Controllers;

if (!defined('ABSPATH')) {
    exit;
}

use CMS_Forum\Models\UserMeta;
use CMS_Forum\Models\Rank;
use CMS_Forum\Models\Forum;
use CMS_Forum\Models\Thread;

final class AdminController
{
    private static ?self $instance = null;

    public static function instance(): static
    {
        return static::$instance ??= new static();
    }

    private function __construct() {}

    /**
     * Benutzer sperren.
     */
    public function banUser(int $userId, string $reason = '', ?string $expiresAt = null): array
    {
        if (!$this->isAdmin()) {
            return ['success' => false, 'error' => 'Keine Berechtigung.'];
        }

        if ($userId <= 0) {
            return ['success' => false, 'error' => 'Ungültiger Benutzer.'];
        }

        $expires = null;
        if ($expiresAt !== null && trim($expiresAt) !== '') {
            try {
                $expires = new \DateTimeImmutable($expiresAt);
            } catch (\Throwable $e) {
                $this->logError('Invalid ban expiry value.', ['user_id' => $userId, 'value' => $expiresAt], $e);
                return ['success' => false, 'error' => 'Ungültiges Sperrdatum.'];
            }
        }

        UserMeta::instance()->ban($userId, $reason, $expires);

        return ['success' => true];
    }

    /**
     * Benutzer entsperren.
     */
    public function unbanUser(int $userId): array
    {
        if (!$this->isAdmin()) {
            return ['success' => false, 'error' => 'Keine Berechtigung.'];
        }

        if ($userId <= 0) {
            return ['success' => false, 'error' => 'Ungültiger Benutzer.'];
        }

        UserMeta::instance()->unban($userId);

        return ['success' => true];
    }

    /**
     * Alle Forum-Zähler neu berechnen.
     */
    public function recalculateCounters(): array
    {
        if (!$this->isAdmin()) {
            return ['success' => false, 'error' => 'Keine Berechtigung.'];
        }

        $db = \CMS\Database::instance();
        $p  = $db->prefix();

        // Alle Threads-Zähler
        $threads = $db->getPdo()->query("SELECT id FROM {$p}cmsforum_threads")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($threads as $threadId) {
            Thread::instance()->refreshCounters((int) $threadId);
        }

        // Alle Forum-Zähler
        $forums = $db->getPdo()->query("SELECT id FROM {$p}cmsforum_forums")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($forums as $forumId) {
            Forum::instance()->refreshCounters((int) $forumId);
        }

        return ['success' => true, 'message' => 'Zähler wurden neu berechnet.'];
    }

    /**
     * Rang-basierte User-Meta aktualisieren.
     */
    public function recalculateRanks(): array
    {
        if (!$this->isAdmin()) {
            return ['success' => false, 'error' => 'Keine Berechtigung.'];
        }

        $db = \CMS\Database::instance();
        $p  = $db->prefix();

        $users = $db->getPdo()->query("SELECT user_id FROM {$p}cmsforum_user_meta")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($users as $userId) {
            UserMeta::instance()->updateRank((int) $userId);
        }

        return ['success' => true, 'message' => 'Ränge wurden aktualisiert.'];
    }

    /**
     * Forum-Einstellung speichern.
     */
    public function saveSetting(string $key, string $value): array
    {
        if (!$this->isAdmin()) {
            return ['success' => false, 'error' => 'Keine Berechtigung.'];
        }

        $db = \CMS\Database::instance();
        $p  = $db->prefix();

        $stmt = $db->prepare(
            "INSERT INTO {$p}cmsforum_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        $stmt->execute([$key, $value]);

        return ['success' => true];
    }

    /**
     * Alle Einstellungen laden.
     */
    public function getSettings(): array
    {
        $db = \CMS\Database::instance();
        $p  = $db->prefix();

        $rows = $db->getPdo()->query("SELECT setting_key, setting_value FROM {$p}cmsforum_settings")->fetchAll(\PDO::FETCH_KEY_PAIR);
        return $rows ?: [];
    }

    // ── Private Helfer ──────────────────────────────────────────────

    /**
     * Admin-Check.
     */
    private function isAdmin(): bool
    {
        $auth = \CMS\Auth::instance();
        return $auth->isLoggedIn() && $auth->isAdmin();
    }

    private function logError(string $message, array $context = [], ?\Throwable $e = null): void
    {
        $payload = ['context' => $context];
        if ($e !== null) {
            $payload['exception'] = $e->getMessage();
        }

        error_log('[cms-forum][admin-controller] ' . $message . ' ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
