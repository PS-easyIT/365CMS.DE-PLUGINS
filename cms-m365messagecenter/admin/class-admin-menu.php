<?php
/**
 * CMS M365 Message Center – Admin Menu.
 *
 * @package CMS_M365MessageCenter
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365MessageCenter_Admin_Menu
{
    private const BASE_SLUG = 'm365messagecenter';

    public static function register(): void
    {
        self::ensure_menu_functions();

        if (!function_exists('add_menu_page') || !function_exists('add_submenu_page')) {
            return;
        }

        add_menu_page(
            'M365 Message Center',
            'M365 Message Center',
            'manage_options',
            self::BASE_SLUG,
            [CMS_M365MessageCenter_Admin_Pages::class, 'render_dashboard'],
            '🔔'
        );

        add_submenu_page(
            self::BASE_SLUG,
            'M365 Message Center – Übersicht',
            '📋 Übersicht',
            'manage_options',
            self::BASE_SLUG,
            [CMS_M365MessageCenter_Admin_Pages::class, 'render_dashboard']
        );

        add_submenu_page(
            self::BASE_SLUG,
            'M365 Message Center – Einstellungen',
            '⚙️ Einstellungen',
            'manage_options',
            'm365messagecenter-settings',
            [CMS_M365MessageCenter_Admin_Pages::class, 'render_settings']
        );
    }

    private static function ensure_menu_functions(): void
    {
        if (function_exists('add_menu_page') && function_exists('add_submenu_page')) {
            return;
        }

        $helperFile = ABSPATH . 'includes/functions/admin-menu.php';
        if (is_file($helperFile)) {
            require_once $helperFile;
        }

        if (function_exists('cms_plugin_admin_require_layout_helpers')) {
            cms_plugin_admin_require_layout_helpers();
        }
    }
}
