<?php
/**
 * CMS Booking – Admin-Pages Shell
 *
 * Lädt alle Admin-Traits und delegiert an die richtige Section.
 *
 * @package CMS_Booking
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Traits laden
$traitsDir = CMS_BOOKING_PLUGIN_DIR . 'admin/modules/';
if (is_dir($traitsDir)) {
    foreach (glob($traitsDir . 'trait-*.php') as $traitFile) {
        require_once $traitFile;
    }
}

final class CMS_Booking_Admin_Pages
{
    use CMS_Booking_Page_Dashboard_Trait;
    use CMS_Booking_Page_Bookings_Trait;
    use CMS_Booking_Page_Providers_Trait;
    use CMS_Booking_Page_Services_Trait;
    use CMS_Booking_Page_Settings_Trait;

    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct() {}

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
     * Admin-CSS einbinden (einmal pro Request)
     */
    protected static function enqueue_admin_assets(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $css = CMS_BOOKING_PLUGIN_DIR . 'assets/css/booking-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_BOOKING_PLUGIN_URL . 'assets/css/booking-admin.css', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    /**
     * Admin-JS am Seitenende einbinden
     */
    protected static function enqueue_admin_scripts(): void
    {
        $js = CMS_BOOKING_PLUGIN_DIR . 'assets/js/booking-admin.js';
        if (file_exists($js)) {
            echo '<script src="'
                . htmlspecialchars(CMS_BOOKING_PLUGIN_URL . 'assets/js/booking-admin.js', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($js) . '" defer></script>' . "\n";
        }
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

    // ── Statische Entry-Points (aufgerufen vom Router) ────────────────────────

    public static function render_dashboard(): void
    {
        self::check_access();
        self::enqueue_admin_assets();
        self::instance()->render_dashboard_page();
    }

    public static function render_bookings(): void
    {
        self::check_access();
        self::enqueue_admin_assets();
        self::instance()->render_bookings_page();
    }

    public static function render_providers(): void
    {
        self::check_access();
        self::enqueue_admin_assets();
        self::instance()->render_providers_page();
    }

    public static function render_services(): void
    {
        self::check_access();
        self::enqueue_admin_assets();
        self::instance()->render_services_page();
    }

    public static function render_settings(): void
    {
        self::check_access();
        self::enqueue_admin_assets();
        self::instance()->render_settings_page();
    }

    /**
     * Legacy-Haupt-Routing (für Direktaufrufe mit ?section=…)
     */
    public function render(): void
    {
        $section = sanitize_text_field($_GET['section'] ?? 'dashboard');

        switch ($section) {
            case 'bookings':
                $this->render_bookings_page();
                break;
            case 'providers':
                $this->render_providers_page();
                break;
            case 'services':
                $this->render_services_page();
                break;
            case 'settings':
                $this->render_settings_page();
                break;
            default:
                $this->render_dashboard_page();
                break;
        }
    }
}
