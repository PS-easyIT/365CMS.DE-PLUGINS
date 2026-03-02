<?php
/**
 * CMS Booking – Admin-Menü-Registrierung
 *
 * @package CMS_Booking
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Booking_Admin_Menu
{
    /**
     * Über den Hook cms_admin_menu aufgerufen.
     * Registriert Haupt- und Untermenüpunkte via add_menu_page / add_submenu_page.
     */
    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        $pages = CMS_Booking_Admin_Pages::class;

        add_menu_page(
            'Buchungen',
            'Buchungen',
            'manage_options',
            'booking-dashboard',
            [$pages, 'render_dashboard'],
            '📅',
            55
        );

        add_submenu_page(
            'booking-dashboard',
            'Dashboard',
            '📊 Dashboard',
            'manage_options',
            'booking-dashboard',
            [$pages, 'render_dashboard']
        );

        add_submenu_page(
            'booking-dashboard',
            'Buchungen',
            '📋 Buchungen',
            'manage_options',
            'booking-bookings',
            [$pages, 'render_bookings']
        );

        add_submenu_page(
            'booking-dashboard',
            'Anbieter',
            '👥 Anbieter',
            'manage_options',
            'booking-providers',
            [$pages, 'render_providers']
        );

        add_submenu_page(
            'booking-dashboard',
            'Leistungen',
            '🛠️ Leistungen',
            'manage_options',
            'booking-services',
            [$pages, 'render_services']
        );

        add_submenu_page(
            'booking-dashboard',
            'Einstellungen',
            '⚙️ Einstellungen',
            'manage_options',
            'booking-settings',
            [$pages, 'render_settings']
        );
    }
}
