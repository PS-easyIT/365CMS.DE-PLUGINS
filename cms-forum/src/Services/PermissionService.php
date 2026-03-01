<?php
/**
 * CMS Forum – Permission Service
 *
 * Zentrale Berechtigungsprüfung in der Reihenfolge:
 * 1. Ist User gebannt? → gesperrt
 * 2. Ist User Admin? → alles erlaubt
 * 3. Ist User Supermoderator? → Forum-übergreifende Rechte
 * 4. Ist User Moderator? → Forum-spezifische Rechte
 * 5. Gruppenberechtigungen → cmsforum_permissions Tabelle
 * 6. Standard-Gastrechte → config.php Defaults
 *
 * @package CMS_Forum\Services
 */

declare(strict_types=1);

namespace CMS_Forum\Services;

if (!defined('ABSPATH')) {
    exit;
}

use CMS_Forum\Models\Permission;
use CMS_Forum\Models\UserMeta;

final class PermissionService
{
    private static ?self $instance = null;

    public static function instance(): static
    {
        return static::$instance ??= new static();
    }

    private function __construct() {}

    /**
     * Kann der aktuelle Benutzer Beiträge im Forum lesen?
     */
    public function canRead(int $forumId): bool
    {
        return $this->checkPermission($forumId, 'can_read');
    }

    /**
     * Kann der aktuelle Benutzer Beiträge im Forum schreiben?
     */
    public function canPost(int $forumId): bool
    {
        return $this->checkPermission($forumId, 'can_post');
    }

    /**
     * Kann der aktuelle Benutzer neue Threads erstellen?
     */
    public function canCreateThread(int $forumId): bool
    {
        return $this->checkPermission($forumId, 'can_create');
    }

    /**
     * Kann der aktuelle Benutzer Dateien anhängen?
     */
    public function canUploadAttachment(int $forumId): bool
    {
        return $this->checkPermission($forumId, 'can_upload');
    }

    /**
     * Kann der aktuelle Benutzer dieses Forum moderieren?
     */
    public function canModerate(int $forumId): bool
    {
        return $this->checkPermission($forumId, 'can_moderate');
    }

    /**
     * Hat der aktuelle Benutzer Admin-Rechte für das Forum?
     */
    public function canAdmin(): bool
    {
        $auth = \CMS\Auth::instance();
        return $auth->isLoggedIn() && $auth->isAdmin();
    }

    /**
     * Kann der Benutzer seinen eigenen Beitrag bearbeiten?
     */
    public function canEditOwnPost(int $forumId, int $postUserId, string $postCreatedAt): bool
    {
        $auth = \CMS\Auth::instance();

        if (!$auth->isLoggedIn()) {
            return false;
        }

        $userId = $auth->getUserId();

        // Admin oder Moderator darf alles bearbeiten
        if ($auth->isAdmin() || $this->canModerate($forumId)) {
            return true;
        }

        // Nur eigene Beiträge
        if ($userId !== $postUserId) {
            return false;
        }

        // Zeitlimit prüfen
        $editMinutes = (int) $this->getSetting('members_can_edit_time', '30');
        if ($editMinutes > 0) {
            $created = new \DateTimeImmutable($postCreatedAt);
            $now     = new \DateTimeImmutable();
            $diff    = $now->getTimestamp() - $created->getTimestamp();
            if ($diff > ($editMinutes * 60)) {
                return false;
            }
        }

        return $this->checkPermission($forumId, 'can_edit_own');
    }

    /**
     * Kann der Benutzer seinen eigenen Beitrag löschen?
     */
    public function canDeleteOwnPost(int $forumId, int $postUserId): bool
    {
        $auth = \CMS\Auth::instance();
        if (!$auth->isLoggedIn()) {
            return false;
        }

        if ($auth->isAdmin() || $this->canModerate($forumId)) {
            return true;
        }

        if ($auth->getUserId() !== $postUserId) {
            return false;
        }

        return $this->checkPermission($forumId, 'can_delete_own');
    }

    /**
     * Zentrale Berechtigungsprüfung.
     */
    private function checkPermission(int $forumId, string $permissionKey): bool
    {
        $auth = \CMS\Auth::instance();

        // 1. Gebannt?
        if ($auth->isLoggedIn()) {
            $userId = $auth->getUserId();
            if (UserMeta::instance()->isBanned($userId)) {
                return false;
            }
        }

        // 2. Admin? → alles erlaubt
        if ($auth->isLoggedIn() && $auth->isAdmin()) {
            return true;
        }

        // 3./4. Moderator?
        if ($auth->isLoggedIn() && $permissionKey !== 'can_moderate') {
            $perm = Permission::instance()->findByForumAndGroup($forumId, 'moderator');
            if ($perm && $perm->can_moderate) {
                return true;
            }
        }

        // 5. Gruppenberechtigungen prüfen
        $groupType = $auth->isLoggedIn() ? 'member' : 'guest';
        $perm = Permission::instance()->findByForumAndGroup($forumId, $groupType);

        if ($perm && property_exists($perm, $permissionKey)) {
            return (bool) $perm->{$permissionKey};
        }

        // 6. Standard-Rechte aus Konfiguration
        return $this->getDefaultPermission($groupType, $permissionKey);
    }

    /**
     * Standard-Berechtigung aus Konfiguration laden.
     */
    private function getDefaultPermission(string $groupType, string $permissionKey): bool
    {
        $defaults = [
            'guest' => [
                'can_read'       => true,
                'can_post'       => false,
                'can_create'     => false,
                'can_edit_own'   => false,
                'can_delete_own' => false,
                'can_upload'     => false,
                'can_vote'       => false,
                'can_moderate'   => false,
            ],
            'member' => [
                'can_read'       => true,
                'can_post'       => true,
                'can_create'     => true,
                'can_edit_own'   => true,
                'can_delete_own' => false,
                'can_upload'     => true,
                'can_vote'       => true,
                'can_moderate'   => false,
            ],
        ];

        return $defaults[$groupType][$permissionKey] ?? false;
    }

    /**
     * Plugin-Einstellung laden.
     */
    private function getSetting(string $key, string $default = ''): string
    {
        try {
            $db = \CMS\Database::instance();
            $p  = $db->prefix();
            $stmt = $db->prepare("SELECT setting_value FROM {$p}cmsforum_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            return $stmt->fetchColumn() ?: $default;
        } catch (\PDOException) {
            return $default;
        }
    }
}
