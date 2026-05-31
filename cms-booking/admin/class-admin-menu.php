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
    public const MENU_SLUG = 'booking';

    /**
     * Über den Hook cms_admin_menu aufgerufen.
     * Registriert Haupt- und Untermenüpunkte via add_menu_page / add_submenu_page.
     */
    public static function register(): void
    {
        self::load_admin_menu_helpers();

        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'Buchungen',
            '365NET | Buchungen',
            'manage_options',
            self::MENU_SLUG,
            [CMS_Booking_Admin_Pages::class, 'dispatch_dashboard'],
            '📅',
            55
        );

        add_submenu_page(
            self::MENU_SLUG,
            'Dashboard',
            '📊 Dashboard',
            'manage_options',
            self::MENU_SLUG,
            [CMS_Booking_Admin_Pages::class, 'dispatch_dashboard']
        );

        add_submenu_page(
            self::MENU_SLUG,
            'Buchungen',
            '📋 Buchungen',
            'manage_options',
            'bookings',
            [CMS_Booking_Admin_Pages::class, 'dispatch_bookings']
        );

        add_submenu_page(
            self::MENU_SLUG,
            'Anbieter',
            '👥 Anbieter',
            'manage_options',
            'providers',
            [CMS_Booking_Admin_Pages::class, 'dispatch_providers']
        );

        add_submenu_page(
            self::MENU_SLUG,
            'Leistungen',
            '🛠️ Leistungen',
            'manage_options',
            'services',
            [CMS_Booking_Admin_Pages::class, 'dispatch_services']
        );

        add_submenu_page(
            self::MENU_SLUG,
            'Einstellungen',
            '⚙️ Einstellungen',
            'manage_options',
            'settings',
            [CMS_Booking_Admin_Pages::class, 'dispatch_settings']
        );
    }

    private static function load_admin_menu_helpers(): void
    {
        if (function_exists('add_menu_page') && function_exists('renderAdminLayoutStart')) {
            return;
        }

        $menuFiles = [
            ABSPATH . 'includes/functions/admin-menu.php',
            ABSPATH . 'CMS/includes/functions/admin-menu.php',
        ];

        foreach ($menuFiles as $menuFile) {
            if (is_file($menuFile)) {
                require_once $menuFile;
                if (function_exists('add_menu_page')) {
                    return;
                }
            }
        }
    }
}
