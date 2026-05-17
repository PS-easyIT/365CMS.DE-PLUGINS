<?php
/**
 * CMS Forum – Admin Pages (Trait-Shell)
 *
 * Zentrale Klasse, die alle Admin-Traits zusammenführt
 * und gemeinsame Hilfsmethoden bereitstellt.
 *
 * @package CMS_Forum
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Traits laden
$modulesDir = __DIR__ . '/modules/';
foreach ([
    'trait-page-dashboard.php',
    'trait-page-categories.php',
    'trait-page-forums.php',
    'trait-page-threads.php',
    'trait-page-users.php',
    'trait-page-ranks.php',
    'trait-page-permissions.php',
    'trait-page-reports.php',
    'trait-page-settings.php',
] as $traitFile) {
    if (file_exists($modulesDir . $traitFile)) {
        require_once $modulesDir . $traitFile;
    }
}

final class CMS_Forum_Admin_Pages
{
    use CMS_Forum_Page_Dashboard_Trait;
    use CMS_Forum_Page_Categories_Trait;
    use CMS_Forum_Page_Forums_Trait;
    use CMS_Forum_Page_Threads_Trait;
    use CMS_Forum_Page_Users_Trait;
    use CMS_Forum_Page_Ranks_Trait;
    use CMS_Forum_Page_Permissions_Trait;
    use CMS_Forum_Page_Reports_Trait;
    use CMS_Forum_Page_Settings_Trait;

    // ── Gemeinsame Hilfsmethoden ────────────────────────────────────

    /**
     * Admin-Zugangs-Check.
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
     * CSRF-Token generieren.
     */
    protected static function generate_nonce(string $action): string
    {
        return \CMS\Security::instance()->generateToken($action);
    }

    /**
     * CSRF-Token prüfen.
     */
    protected static function verify_nonce(string $action): bool
    {
        return \CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), $action);
    }

    /**
     * Output-Escaping.
     */
    protected static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Admin-CSS einmalig laden.
     */
    protected static function enqueue_admin_assets(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $css = CMS_FORUM_DIR . 'assets/css/cms-forum-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_FORUM_URL . 'assets/css/cms-forum-admin.css?v=' . filemtime($css), ENT_QUOTES, 'UTF-8')
                . '">' . "\n";
        }
    }

    /**
     * Admin-Menü/Layout-Funktionen laden.
     */
    protected static function load_admin_menu(): void
    {
        $menuFile = ABSPATH . 'admin/partials/admin-menu.php';
        if (file_exists($menuFile) && !function_exists('renderAdminLayoutStart')) {
            require_once $menuFile;
        }
    }

    /**
     * Einheitliches Admin-Layout nach CMS-Experts-Muster.
     */
    protected static function render_admin_page(string $title, callable $renderer): void
    {
        self::load_admin_menu();
        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, 'forum-dashboard');
        }

        self::enqueue_admin_assets();
        $renderer();

        if (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
    }

    /**
     * Settings-Wert lesen.
     */
    protected static function get_setting(string $key, string $default = ''): string
    {
        try {
            $db   = \CMS\Database::instance();
            $p    = $db->prefix();
            $stmt = $db->prepare("SELECT setting_value FROM {$p}cmsforum_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return $val !== false ? (string) $val : $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Settings-Wert speichern.
     */
    protected static function save_setting(string $key, string $value): void
    {
        $db = \CMS\Database::instance();
        $p  = $db->prefix();

        $stmt = $db->prepare(
            "INSERT INTO {$p}cmsforum_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        $stmt->execute([$key, $value]);
    }

    /**
     * Datenbank-Objekt.
     */
    protected static function db(): \CMS\Database
    {
        return \CMS\Database::instance();
    }

    /**
     * Tabellen-Präfix.
     */
    protected static function prefix(): string
    {
        return self::db()->prefix();
    }
}
