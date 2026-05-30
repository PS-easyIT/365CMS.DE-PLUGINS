<?php
/**
 * CMS M365 Linkcollection – Admin Menü.
 *
 * @package CMS_M365LINKCOLLECTION
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365LINKCOLLECTION_Admin_Menu
{
    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'M365 Linkcollection',
            'M365 Links',
            'manage_options',
            'm365linkcollection-dashboard',
            [CMS_M365LINKCOLLECTION_Admin_Pages::class, 'render_dashboard'],
            '🔗'
        );
    }
}
