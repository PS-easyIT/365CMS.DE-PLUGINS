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
            'Downloads',
            'manage_options',
            'downloads-dashboard',
            [CMS_Downloads_Admin_Pages::class, 'render_dashboard'],
            '⬇️',
            56
        );

        add_submenu_page('downloads-dashboard', 'Dashboard', '📊 Dashboard', 'manage_options', 'downloads-dashboard', [CMS_Downloads_Admin_Pages::class, 'render_dashboard']);
        add_submenu_page('downloads-dashboard', 'Downloads', '📦 Downloads', 'manage_options', 'downloads-items', [CMS_Downloads_Admin_Pages::class, 'render_downloads']);
        add_submenu_page('downloads-dashboard', 'Kategorien', '🗂️ Kategorien', 'manage_options', 'downloads-categories', [CMS_Downloads_Admin_Pages::class, 'render_categories']);
        add_submenu_page('downloads-dashboard', 'Einstellungen', '⚙️ Einstellungen', 'manage_options', 'downloads-settings', [CMS_Downloads_Admin_Pages::class, 'render_settings']);
    }
}
