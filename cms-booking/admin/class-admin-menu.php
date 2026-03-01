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
     */
    public static function register(): void
    {
        if (!function_exists('register_admin_menu_item')) {
            return;
        }

        register_admin_menu_item([
            'slug'     => 'booking',
            'label'    => '📅 Buchungen',
            'icon'     => '📅',
            'url'      => '/admin/plugin-booking.php',
            'position' => 55,
            'children' => [
                [
                    'slug'  => 'booking-dashboard',
                    'label' => '📊 Dashboard',
                    'url'   => '/admin/plugin-booking.php?section=dashboard',
                ],
                [
                    'slug'  => 'booking-list',
                    'label' => '📋 Buchungen',
                    'url'   => '/admin/plugin-booking.php?section=bookings',
                ],
                [
                    'slug'  => 'booking-providers',
                    'label' => '👥 Anbieter',
                    'url'   => '/admin/plugin-booking.php?section=providers',
                ],
                [
                    'slug'  => 'booking-services',
                    'label' => '🛠️ Leistungen',
                    'url'   => '/admin/plugin-booking.php?section=services',
                ],
                [
                    'slug'  => 'booking-settings',
                    'label' => '⚙️ Einstellungen',
                    'url'   => '/admin/plugin-booking.php?section=settings',
                ],
            ],
        ]);
    }
}
