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

    /**
     * Haupt-Routing für die Admin-Seite.
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
