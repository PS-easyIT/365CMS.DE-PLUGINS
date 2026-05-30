<?php
/**
 * CMS M365 Adminsites – Admin Menü.
 *
 * @package CMS_M365ADMINSITES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365ADMINSITES_Admin_Menu
{
    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'M365 Adminsites',
            'M365 Adminsites',
            'manage_options',
            'm365adminsites-dashboard',
            [CMS_M365ADMINSITES_Admin_Pages::class, 'render_dashboard'],
            '🧭'
        );
    }
}
