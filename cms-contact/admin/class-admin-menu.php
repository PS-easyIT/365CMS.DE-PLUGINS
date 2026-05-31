<?php
/**
 * CMS Contact – Admin Menu
 *
 * @package CMS_Contact
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Contact_Admin_Menu
{
    public static function register(): void
    {
        self::load_admin_menu_helpers();

        if (!function_exists('add_menu_page')) {
            return;
        }

        $dispatch = [CMS_Contact_Admin_Pages::class, 'render_dispatch'];

        add_menu_page(
            'Kontakt',
            '365CMS | Kontakt',
            'manage_options',
            CMS_Contact_Admin_Pages::MENU_SLUG,
            $dispatch,
            '📬',
            35
        );

        if (!function_exists('add_submenu_page')) {
            return;
        }

        foreach (CMS_Contact_Admin_Pages::get_menu_pages() as $page) {
            add_submenu_page(
                CMS_Contact_Admin_Pages::MENU_SLUG,
                $page['title'],
                $page['menu_title'],
                'manage_options',
                $page['slug'],
                $dispatch
            );
        }
    }

    private static function load_admin_menu_helpers(): void
    {
        if (function_exists('add_menu_page') && function_exists('renderAdminLayoutStart')) {
            return;
        }

        $menuFiles = [
            ABSPATH . 'includes/functions/admin-menu.php',
            ABSPATH . 'CMS/includes/functions/admin-menu.php',
        ];

        foreach ($menuFiles as $menuFile) {
            if (is_file($menuFile)) {
                require_once $menuFile;
                if (function_exists('add_menu_page')) {
                    return;
                }
            }
        }
    }
}
