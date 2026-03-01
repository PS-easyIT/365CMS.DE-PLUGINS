<?php
/**
 * CMS Contact – Admin Pages (Trait-Shell)
 *
 * Zentrale Klasse, die alle Admin-Traits zusammenführt.
 *
 * @package CMS_Contact
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Traits laden
$modulesDir = __DIR__ . '/modules/';
foreach ([
    'trait-page-dashboard.php',
    'trait-page-forms.php',
    'trait-page-submissions.php',
    'trait-page-settings.php',
] as $traitFile) {
    if (file_exists($modulesDir . $traitFile)) {
        require_once $modulesDir . $traitFile;
    }
}

final class CMS_Contact_Admin_Pages
{
    use CMS_Contact_Page_Dashboard_Trait;
    use CMS_Contact_Page_Forms_Trait;
    use CMS_Contact_Page_Submissions_Trait;
    use CMS_Contact_Page_Settings_Trait;

    // ── Gemeinsame Hilfsmethoden ──────────────────────────────────────────────

    /**
     * Admin-Zugangs-Check
     */
    protected static function check_access(): bool
    {
        if (!class_exists('CMS\Auth') || !\CMS\Auth::instance()->isAdmin()) {
            header('Location: ' . (defined('SITE_URL') ? SITE_URL : '/'));
            exit;
        }
        return true;
    }

    /**
     * CSRF-Token generieren
     */
    protected static function generate_nonce(string $action): string
    {
        return \CMS\Security::instance()->generateToken($action);
    }

    /**
     * CSRF-Token prüfen
     */
    protected static function verify_nonce(string $action): bool
    {
        return \CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', $action);
    }

    /**
     * Output-Escaping
     */
    protected static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Settings-Wert aus Plugin-Settings lesen
     */
    protected static function get_setting(string $key, string $default = ''): string
    {
        try {
            $db   = \CMS\Database::instance();
            $p    = $db->getPrefix();
            $stmt = $db->prepare("SELECT setting_value FROM {$p}contact_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ? (string) $row['setting_value'] : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Settings-Wert speichern
     */
    protected static function save_setting(string $key, string $value): void
    {
        $db = \CMS\Database::instance();
        $p  = $db->getPrefix();

        $exists = $db->prepare("SELECT id FROM {$p}contact_settings WHERE setting_key = ?");
        $exists->execute([$key]);

        if ($exists->fetch()) {
            $db->prepare("UPDATE {$p}contact_settings SET setting_value = ? WHERE setting_key = ?")
               ->execute([$value, $key]);
        } else {
            $db->prepare("INSERT INTO {$p}contact_settings (setting_key, setting_value) VALUES (?, ?)")
               ->execute([$key, $value]);
        }
    }
}
