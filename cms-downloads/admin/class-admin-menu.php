<?php
/**
 * @package CMS_Downloads
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Downloads_Admin_Menu
{
    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'Downloads',
            '365CMS | Downloads',
            'manage_options',
            CMS_Downloads_Admin_Pages::PAGE_DASHBOARD,
            [CMS_Downloads_Admin_Pages::class, 'dispatch'],
            '⬇️',
            56
        );

        add_submenu_page(
            CMS_Downloads_Admin_Pages::PAGE_DASHBOARD,
            'Dashboard',
            'Dashboard',
            'manage_options',
            CMS_Downloads_Admin_Pages::PAGE_DASHBOARD,
            [CMS_Downloads_Admin_Pages::class, 'dispatch']
        );
        add_submenu_page(
            CMS_Downloads_Admin_Pages::PAGE_DASHBOARD,
            'Downloads',
            'Downloads',
            'manage_options',
            CMS_Downloads_Admin_Pages::PAGE_DOWNLOADS,
            [CMS_Downloads_Admin_Pages::class, 'dispatch']
        );
        add_submenu_page(
            CMS_Downloads_Admin_Pages::PAGE_DASHBOARD,
            'Kategorien',
            'Kategorien',
            'manage_options',
            CMS_Downloads_Admin_Pages::PAGE_CATEGORIES,
            [CMS_Downloads_Admin_Pages::class, 'dispatch']
        );
        add_submenu_page(
            CMS_Downloads_Admin_Pages::PAGE_DASHBOARD,
            'Einstellungen',
            'Einstellungen',
            'manage_options',
            CMS_Downloads_Admin_Pages::PAGE_SETTINGS,
            [CMS_Downloads_Admin_Pages::class, 'dispatch']
        );
    }
}
