<?php
/**
 * CMS Booking – Admin Dashboard Trait
 *
 * @package CMS_Booking
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_Booking_Page_Dashboard_Trait
{
    public function render_dashboard_page(): void
    {
        $stats      = CMS_Booking_Bookings::instance()->get_stats();
        $providers  = CMS_Booking_Providers::instance()->count('active');
        $services   = CMS_Booking_Services::instance()->count();
        $recent     = CMS_Booking_Bookings::instance()->get_all(0, 10);
        $types      = CMS_Booking_Integration::get_provider_types();

        include CMS_BOOKING_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
}
