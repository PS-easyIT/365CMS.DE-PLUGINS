<?php
/**
 * CMS M365 License – Admin Menü
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365LIC_Admin_Menu
{
    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        $pages = CMS_M365LIC_Admin_Pages::class;

        add_menu_page(
            'M365 Lizenzberater',
            'M365 Lizenzen',
            'manage_options',
            'm365lic-dashboard',
            [$pages, 'render_dashboard'],
            '☁️',
            56
        );

        add_submenu_page(
            'm365lic-dashboard',
            'Dashboard',
            '📊 Dashboard',
            'manage_options',
            'm365lic-dashboard',
            [$pages, 'render_dashboard']
        );

        add_submenu_page(
            'm365lic-dashboard',
            'Pakete',
            '📦 Pakete',
            'manage_options',
            'm365lic-packages',
            [$pages, 'render_packages']
        );

        add_submenu_page(
            'm365lic-dashboard',
            'Spezialgruppen',
            '👥 Spezialgruppen',
            'manage_options',
            'm365lic-special-groups',
            [$pages, 'render_special_groups']
        );

        add_submenu_page(
            'm365lic-dashboard',
            'Spezial-User',
            '🔐 Spezial-User',
            'manage_options',
            'm365lic-special-users',
            [$pages, 'render_special_users']
        );

        add_submenu_page(
            'm365lic-dashboard',
            'Einstellungen',
            '⚙️ Einstellungen',
            'manage_options',
            'm365lic-settings',
            [$pages, 'render_settings']
        );
    }
}
