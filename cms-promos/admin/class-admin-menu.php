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

        $dispatcher = [CMS_Promos_Admin_Pages::class, 'render_page_dispatcher'];

        add_menu_page('Promos', '365CMS | Promos', 'manage_options', 'promos-dashboard', $dispatcher, 'PR');
        add_submenu_page('promos-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'promos-dashboard', $dispatcher);
        add_submenu_page('promos-dashboard', 'Promos', 'Promos', 'manage_options', 'promos-items', $dispatcher);
        add_submenu_page('promos-dashboard', 'Platzierungen', 'Platzierungen', 'manage_options', 'promos-placements', $dispatcher);
        add_submenu_page('promos-dashboard', 'Einstellungen', 'Einstellungen', 'manage_options', 'promos-settings', $dispatcher);
    }
}
