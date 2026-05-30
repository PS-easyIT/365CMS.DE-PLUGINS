<?php
/**
 * @package CMS_Promos
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Promos_Admin_Menu
{
    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page('Promos', 'Promos', 'manage_options', 'promos-dashboard', [CMS_Promos_Admin_Pages::class, 'render_dashboard'], 'PR');
        add_submenu_page('promos-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'promos-dashboard', [CMS_Promos_Admin_Pages::class, 'render_dashboard']);
        add_submenu_page('promos-dashboard', 'Promos', 'Promos', 'manage_options', 'promos-items', [CMS_Promos_Admin_Pages::class, 'render_promos']);
        add_submenu_page('promos-dashboard', 'Platzierungen', 'Platzierungen', 'manage_options', 'promos-placements', [CMS_Promos_Admin_Pages::class, 'render_placements']);
        add_submenu_page('promos-dashboard', 'Einstellungen', 'Einstellungen', 'manage_options', 'promos-settings', [CMS_Promos_Admin_Pages::class, 'render_settings']);
    }
}
