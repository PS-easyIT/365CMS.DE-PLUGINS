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

    /** Admin-Basis-URL für alle Kontakt-Seiten */
    public const ADMIN_BASE_URL = '/admin/plugins/contact/contact';

    /**
     * Zentrale Dispatch: leitet anhand ?section= an den richtigen Trait weiter.
     *
     * Output-Buffering erlaubt header()-Redirects in POST-Handlern,
     * auch wenn renderAdminLayoutStart() bereits HTML ausgegeben hat.
     */
    public static function render_dispatch(): void
    {
        ob_start();
        $section = sanitize_text_field($_GET['section'] ?? 'dashboard');

        self::load_admin_menu();
        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart(self::get_page_title($section), 'contact');
        }

        self::enqueue_admin_assets();

        match ($section) {
            'forms'       => self::render_forms(),
            'submissions' => self::render_submissions(),
            'settings'    => self::render_settings(),
            default       => self::render_dashboard(),
        };

        self::enqueue_admin_scripts();
        if (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }

        ob_end_flush();
    }

    /**
     * URL-Helper für Contact-Admin-Seiten
     */
    public static function admin_url(string $section = 'dashboard', array $params = []): string
    {
        $url = self::ADMIN_BASE_URL . '?section=' . urlencode($section);
        foreach ($params as $k => $v) {
            $url .= '&' . urlencode($k) . '=' . urlencode((string) $v);
        }
        return $url;
    }

    // ── Gemeinsame Hilfsmethoden ──────────────────────────────────────────────

    /**
     * Admin-CSS und -JS einbinden (einmal pro Request)
     */
    protected static function enqueue_admin_assets(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $css = CMS_CONTACT_PLUGIN_DIR . 'assets/css/contact-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_CONTACT_PLUGIN_URL . 'assets/css/contact-admin.css', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($css) . '">' . "\n";
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
     * Titel je Abschnitt für das Admin-Layout.
     */
    protected static function get_page_title(string $section): string
    {
        return match ($section) {
            'forms' => 'Kontaktformulare',
            'submissions' => 'Kontakt-Nachrichten',
            'settings' => 'Kontakt-Einstellungen',
            default => 'Kontakt',
        };
    }

    /**
     * Admin-JS am Seitenende einbinden
     */
    protected static function enqueue_admin_scripts(): void
    {
        $js = CMS_CONTACT_PLUGIN_DIR . 'assets/js/contact-admin.js';
        if (file_exists($js)) {
            echo '<script src="'
                . htmlspecialchars(CMS_CONTACT_PLUGIN_URL . 'assets/js/contact-admin.js', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($js) . '" defer></script>' . "\n";
        }
    }

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
