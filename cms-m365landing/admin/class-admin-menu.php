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
    private const BASE_SLUG = 'm365landing-dashboard';

    /** @return array<string,array{title:string,menu:string}> */
    private static function menu_pages(): array
    {
        return [
            'm365landing-dashboard' => [
                'title' => 'M365 Landing – Dashboard',
                'menu' => '📊 Dashboard',
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

    public static function register(): void
    {
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

        foreach ($menuPages as $slug => $page) {
            add_submenu_page(
                self::BASE_SLUG,
                (string) $page['title'],
                (string) $page['menu'],
                'manage_options',
                $slug,
                [$pagesClass, 'render_dispatch']
            );
        }
    }
}
