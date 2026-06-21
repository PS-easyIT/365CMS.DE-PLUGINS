<?php
/**
 * CMS Beratung – Admin menu registration.
 *
 * @package CMS_Beratung
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Beratung_Admin_Menu
{
    public static function register(): void
    {
        self::load_admin_menu_helpers();

        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'CMS Beratung',
            '365 | Beratung',
            'manage_options',
            CMS_Beratung_Admin_Pages::MENU_SLUG,
            CMS_Beratung_Admin_Pages::dispatch_callback_for_slug(CMS_Beratung_Admin_Pages::DEFAULT_PAGE_SLUG),
            '💼',
            57
        );

        if (!function_exists('add_submenu_page')) {
            return;
        }

        foreach (CMS_Beratung_Admin_Pages::get_menu_pages() as $page) {
            add_submenu_page(
                CMS_Beratung_Admin_Pages::MENU_SLUG,
                (string) $page['title'],
                (string) $page['menu_title'],
                'manage_options',
                (string) $page['slug'],
                CMS_Beratung_Admin_Pages::dispatch_callback_for_slug((string) $page['slug'])
            );
        }
    }

    /**
     * @param array<int,array<string,mixed>> $menuItems
     * @return array<int,array<string,mixed>>
     */
    public static function add_menu_items(array $menuItems): array
    {
        $parentSlug = CMS_Beratung_Admin_Pages::MENU_SLUG;
        $adminBase = '/admin/plugins/' . $parentSlug;
        $currentPath = function_exists('cms_plugin_admin_request_path')
            ? cms_plugin_admin_request_path()
            : '/' . trim((string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? ''), '/');
        $isPluginPath = str_starts_with($currentPath, $adminBase);
        $activeSlug = function_exists('cms_plugin_admin_active_slug')
            ? cms_plugin_admin_active_slug(CMS_Beratung_Admin_Pages::DEFAULT_PAGE_SLUG)
            : CMS_Beratung_Admin_Pages::DEFAULT_PAGE_SLUG;

        $menuItems[] = [
            'type' => 'item',
            'slug' => $parentSlug,
            'label' => '365 | Beratung',
            'icon' => '💼',
            'url' => self::admin_url(CMS_Beratung_Admin_Pages::DEFAULT_PAGE_SLUG),
            'active' => $isPluginPath && $activeSlug === CMS_Beratung_Admin_Pages::DEFAULT_PAGE_SLUG,
        ];

        foreach (CMS_Beratung_Admin_Pages::get_menu_pages() as $page) {
            $slug = (string) ($page['slug'] ?? '');
            if ($slug === '' || $slug === CMS_Beratung_Admin_Pages::DEFAULT_PAGE_SLUG) {
                continue;
            }
            $menuItems[] = [
                'type' => 'item',
                'slug' => $slug,
                'parent' => $parentSlug,
                'label' => '↳ ' . (string) ($page['title'] ?? $slug),
                'icon' => '',
                'url' => self::admin_url($slug),
                'active' => $isPluginPath && $activeSlug === $slug,
            ];
        }

        return $menuItems;
    }

    private static function admin_url(string $pageSlug): string
    {
        if (function_exists('cms_plugin_admin_page_path')) {
            return cms_plugin_admin_page_path(CMS_Beratung_Admin_Pages::MENU_SLUG, $pageSlug);
        }

        return '/admin/plugins/' . CMS_Beratung_Admin_Pages::MENU_SLUG . '/' . rawurlencode($pageSlug);
    }

    private static function load_admin_menu_helpers(): void
    {
        if (function_exists('add_menu_page') && function_exists('renderAdminLayoutStart')) {
            return;
        }

        foreach ([ABSPATH . 'includes/functions/admin-menu.php', ABSPATH . 'CMS/includes/functions/admin-menu.php'] as $menuFile) {
            if (is_file($menuFile)) {
                require_once $menuFile;
                if (function_exists('add_menu_page')) {
                    return;
                }
            }
        }
    }
}
