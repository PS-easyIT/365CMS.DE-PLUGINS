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

        $pages = CMS_M365LINKCOLLECTION_Admin_Pages::class;

        add_menu_page(
            'M365 Linkcollection',
            'M365 Links',
            'manage_options',
            CMS_M365LINKCOLLECTION_Admin_Pages::PLUGIN_SLUG,
            [$pages, 'render_dispatch'],
            '🔗',
            58
        );

        add_submenu_page(
            CMS_M365LINKCOLLECTION_Admin_Pages::PLUGIN_SLUG,
            'M365 Linkcollection - Dashboard',
            'Uebersicht',
            'manage_options',
            CMS_M365LINKCOLLECTION_Admin_Pages::SLUG_DASHBOARD,
            [$pages, 'render_dashboard']
        );

        add_submenu_page(
            CMS_M365LINKCOLLECTION_Admin_Pages::PLUGIN_SLUG,
            'M365 Linkcollection - Eintraege',
            'Eintraege',
            'manage_options',
            CMS_M365LINKCOLLECTION_Admin_Pages::SLUG_ENTRIES,
            [$pages, 'render_entries_page']
        );

        add_submenu_page(
            CMS_M365LINKCOLLECTION_Admin_Pages::PLUGIN_SLUG,
            'M365 Linkcollection - Inhalte',
            'Inhalte',
            'manage_options',
            CMS_M365LINKCOLLECTION_Admin_Pages::SLUG_CONTENT,
            [$pages, 'render_content_page']
        );

        add_submenu_page(
            CMS_M365LINKCOLLECTION_Admin_Pages::PLUGIN_SLUG,
            'M365 Linkcollection - Anzeige',
            'Anzeige',
            'manage_options',
            CMS_M365LINKCOLLECTION_Admin_Pages::SLUG_SETTINGS,
            [$pages, 'render_settings_page']
        );

        add_submenu_page(
            CMS_M365LINKCOLLECTION_Admin_Pages::PLUGIN_SLUG,
            'M365 Linkcollection - Hinweise',
            'Hinweise',
            'manage_options',
            CMS_M365LINKCOLLECTION_Admin_Pages::SLUG_HELP,
            [$pages, 'render_help_page']
        );

        // Backward-compatible legacy slug: avoids "Adminseite nicht gefunden".
        add_submenu_page(
            CMS_M365LINKCOLLECTION_Admin_Pages::PLUGIN_SLUG,
            'M365 Linkcollection - Legacy Dashboard',
            '',
            'manage_options',
            'm365linkcollection-dashboard',
            [$pages, 'render_dashboard']
        );
    }
}
