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
            '365CMS | Beratung',
            'manage_options',
            CMS_Beratung_Admin_Pages::MENU_SLUG,
            CMS_Beratung_Admin_Pages::dispatch_callback_for_slug(CMS_Beratung_Admin_Pages::DEFAULT_PAGE_SLUG),
            '',
            57
        );

        if (!function_exists('add_submenu_page')) {
            return;
        }

        add_submenu_page(
            CMS_Beratung_Admin_Pages::MENU_SLUG,
            'Landingpages',
            'Landingpages',
            'manage_options',
            CMS_Beratung_Admin_Pages::MENU_SLUG,
            [CMS_Beratung_Admin_Pages::class, 'dispatch_overview']
        );

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
