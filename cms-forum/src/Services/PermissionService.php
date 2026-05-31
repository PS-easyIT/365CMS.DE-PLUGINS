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
    /** @var array<string, bool> */
    private array $permissionCache = [];
    /** @var array<string, string> */
    private array $settingCache = [];

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
     * Kann der aktuelle Benutzer in Umfragen/Likes abstimmen?
     */
    public function canVote(int $forumId): bool
    {
        return $this->checkPermission($forumId, 'can_vote');
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

        $userId = (int)$auth->currentUser()->id;

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

        if ((int)$auth->currentUser()->id !== $postUserId) {
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
        $currentUserId = 0;
        if ($auth->isLoggedIn()) {
            $currentUser = $auth->currentUser();
            $currentUserId = (is_object($currentUser) && isset($currentUser->id)) ? (int) $currentUser->id : 0;
        }

        $cacheKey = $forumId . ':' . $permissionKey . ':' . ($auth->isLoggedIn() ? '1' : '0') . ':' . $currentUserId . ':' . ($auth->isAdmin() ? '1' : '0');
        if (array_key_exists($cacheKey, $this->permissionCache)) {
            return $this->permissionCache[$cacheKey];
        }

        // 1. Gebannt?
        if ($auth->isLoggedIn()) {
            if (UserMeta::instance()->isBanned($currentUserId)) {
                $this->permissionCache[$cacheKey] = false;
                return false;
            }
        }

        // 2. Admin? → alles erlaubt
        if ($auth->isLoggedIn() && $auth->isAdmin()) {
            $this->permissionCache[$cacheKey] = true;
            return true;
        }

        // 3./4. Moderator?
        if ($auth->isLoggedIn() && $permissionKey !== 'can_moderate') {
            $perm = Permission::instance()->findByForumAndGroup($forumId, 'moderator');
            if ($perm && $perm->can_moderate) {
                $this->permissionCache[$cacheKey] = true;
                return true;
            }
        }

        // 5. Gruppenberechtigungen prüfen
        $groupType = $auth->isLoggedIn() ? 'member' : 'guest';
        $perm = Permission::instance()->findByForumAndGroup($forumId, $groupType);

        if ($perm && property_exists($perm, $permissionKey)) {
            $result = (bool) $perm->{$permissionKey};
            $this->permissionCache[$cacheKey] = $result;
            return $result;
        }

        // 6. Standard-Rechte aus Konfiguration
        $result = $this->getDefaultPermission($groupType, $permissionKey);
        $this->permissionCache[$cacheKey] = $result;
        return $result;
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
        if (array_key_exists($key, $this->settingCache)) {
            return $this->settingCache[$key];
        }

        try {
            $db = \CMS\Database::instance();
            $p  = $db->prefix();
            $stmt = $db->prepare("SELECT setting_value FROM {$p}cmsforum_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $value = (string) ($stmt->fetchColumn() ?: $default);
            $this->settingCache[$key] = $value;
            return $value;
        } catch (\PDOException) {
            $this->settingCache[$key] = $default;
            return $default;
        }
    }
}
