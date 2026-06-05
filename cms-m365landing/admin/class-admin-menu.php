<?php
/**
 * CMS M365 Landing – Admin Menu.
 *
 * @package CMS_M365Landing
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365Landing_Admin_Menu
{
    private const BASE_SLUG = 'm365landing';
    private const LEGACY_BASE_SLUG = 'm365landing-dashboard';

    /** @return array<string,array{title:string,menu:string}> */
    private static function menu_pages(): array
    {
        return [
            'm365landing' => [
                'title' => 'M365 Landing – Übersicht',
                'menu' => '📊 Übersicht',
            ],
            'm365landing-cards' => [
                'title' => 'M365 Landing – Karten & Bereiche',
                'menu' => '🃏 Karten & Bereiche',
            ],
            'm365landing-settings' => [
                'title' => 'M365 Landing – Inhalte & Design',
                'menu' => '⚙️ Inhalte & Design',
            ],
            'm365landing-system' => [
                'title' => 'M365 Landing – System',
                'menu' => '🖥️ System',
            ],
        ];
    }

    /** @return callable */
    private static function callback_for_slug(string $slug): callable
    {
        return match ($slug) {
            'm365landing-cards' => [CMS_M365Landing_Admin_Pages::class, 'render_cards'],
            'm365landing-settings' => [CMS_M365Landing_Admin_Pages::class, 'render_settings'],
            'm365landing-system' => [CMS_M365Landing_Admin_Pages::class, 'render_system'],
            default => [CMS_M365Landing_Admin_Pages::class, 'render_dashboard'],
        };
    }

    public static function register(): void
    {
        self::ensure_menu_functions();

        if (!function_exists('add_menu_page') || !function_exists('add_submenu_page')) {
            return;
        }

        $pagesClass = CMS_M365Landing_Admin_Pages::class;
        $menuPages = self::menu_pages();

        add_menu_page(
            'M365 Landing',
            'M365 Landing',
            'manage_options',
            self::BASE_SLUG,
            [$pagesClass, 'render_dispatch'],
            '🏠'
        );

        add_menu_page(
            'M365 Landing',
            'M365 Landing',
            'manage_options',
            self::LEGACY_BASE_SLUG,
            [$pagesClass, 'render_dispatch'],
            '🏠',
            null,
            true
        );

        foreach ($menuPages as $slug => $page) {
            add_submenu_page(
                self::BASE_SLUG,
                (string) $page['title'],
                (string) $page['menu'],
                'manage_options',
                $slug,
                self::callback_for_slug($slug)
            );

            $legacySlug = $slug === self::BASE_SLUG ? self::LEGACY_BASE_SLUG : $slug;

            add_submenu_page(
                self::LEGACY_BASE_SLUG,
                (string) $page['title'],
                (string) $page['menu'],
                'manage_options',
                $legacySlug,
                self::callback_for_slug($legacySlug)
            );
        }
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
