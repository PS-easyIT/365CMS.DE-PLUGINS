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

    /**
     * @param array<int, array<string, mixed>> $menuItems
     * @return array<int, array<string, mixed>>
     */
    public static function add_menu_items(array $menuItems): array
    {
        $parentSlug = CMS_M365LINKCOLLECTION_Admin_Pages::PLUGIN_SLUG;
        $adminBase = '/admin/plugins/' . $parentSlug;
        $currentPath = function_exists('cms_plugin_admin_request_path')
            ? cms_plugin_admin_request_path()
            : '/' . trim((string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? ''), '/');
        $isPluginPath = str_starts_with($currentPath, $adminBase)
            || str_starts_with($currentPath, '/admin/plugins/m365linkcollection-dashboard');
        $activeSlug = function_exists('cms_plugin_admin_active_slug')
            ? cms_plugin_admin_active_slug(CMS_M365LINKCOLLECTION_Admin_Pages::SLUG_DASHBOARD)
            : CMS_M365LINKCOLLECTION_Admin_Pages::SLUG_DASHBOARD;

        $submenus = [
            CMS_M365LINKCOLLECTION_Admin_Pages::SLUG_DASHBOARD => 'Uebersicht',
            CMS_M365LINKCOLLECTION_Admin_Pages::SLUG_ENTRIES => 'Eintraege',
            CMS_M365LINKCOLLECTION_Admin_Pages::SLUG_CONTENT => 'Inhalte',
            CMS_M365LINKCOLLECTION_Admin_Pages::SLUG_SETTINGS => 'Anzeige',
            CMS_M365LINKCOLLECTION_Admin_Pages::SLUG_HELP => 'Hinweise',
        ];

        $menuItems[] = [
            'type' => 'item',
            'slug' => $parentSlug,
            'label' => 'M365 Links',
            'icon' => '🔗',
            'url' => function_exists('cms_plugin_admin_page_path')
                ? cms_plugin_admin_page_path($parentSlug, CMS_M365LINKCOLLECTION_Admin_Pages::SLUG_DASHBOARD)
                : $adminBase . '/' . rawurlencode(CMS_M365LINKCOLLECTION_Admin_Pages::SLUG_DASHBOARD),
            'active' => $isPluginPath && (
                $activeSlug === CMS_M365LINKCOLLECTION_Admin_Pages::SLUG_DASHBOARD
                || $activeSlug === 'm365linkcollection-dashboard'
            ),
        ];

        foreach ($submenus as $slug => $label) {
            if ($slug === CMS_M365LINKCOLLECTION_Admin_Pages::SLUG_DASHBOARD) {
                continue;
            }

            $menuItems[] = [
                'type' => 'item',
                'slug' => $slug,
                'parent' => $parentSlug,
                'label' => '↳ ' . $label,
                'icon' => '',
                'url' => function_exists('cms_plugin_admin_page_path')
                    ? cms_plugin_admin_page_path($parentSlug, $slug)
                    : $adminBase . '/' . rawurlencode($slug),
                'active' => $isPluginPath && $activeSlug === $slug,
            ];
        }

        return $menuItems;
    }
}
