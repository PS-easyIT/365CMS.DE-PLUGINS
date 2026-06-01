<?php
/**
 * CMS M365 Adminsites – Admin Menü.
 *
 * @package CMS_M365ADMINSITES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365ADMINSITES_Admin_Menu
{
    private const CAPABILITY = 'manage_options';
    public const MENU_SLUG = 'm365adminsites-dashboard';

    /**
     * @return array<string,string>
     */
    public static function page_titles(): array
    {
        return [
            self::MENU_SLUG => 'Übersicht',
            'm365adminsites-content' => 'Inhalte & Texte',
            'm365adminsites-settings' => 'Anzeige & Design',
            'm365adminsites-help' => 'Hinweise',
        ];
    }

    /**
     * @return array<string,array{label:string,dispatch:string}>
     */
    private static function submenu_pages(): array
    {
        return [
            self::MENU_SLUG => [
                'label' => 'Übersicht',
                'dispatch' => 'dispatch_dashboard',
            ],
            'm365adminsites-content' => [
                'label' => 'Inhalte & Texte',
                'dispatch' => 'dispatch_content',
            ],
            'm365adminsites-settings' => [
                'label' => 'Anzeige & Design',
                'dispatch' => 'dispatch_settings',
            ],
            'm365adminsites-help' => [
                'label' => 'Hinweise',
                'dispatch' => 'dispatch_help',
            ],
        ];
    }

    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'M365 Adminsites',
            'M365 Adminsites',
            self::CAPABILITY,
            self::MENU_SLUG,
            [CMS_M365ADMINSITES_Admin_Pages::class, 'dispatch_dashboard'],
            '🧭'
        );

        if (!function_exists('add_submenu_page')) {
            return;
        }

        foreach (self::submenu_pages() as $slug => $page) {
            $dispatch = (string) ($page['dispatch'] ?? 'dispatch_dashboard');
            add_submenu_page(
                self::MENU_SLUG,
                'M365 Adminsites – ' . (string) ($page['label'] ?? $slug),
                (string) ($page['label'] ?? $slug),
                self::CAPABILITY,
                $slug,
                [CMS_M365ADMINSITES_Admin_Pages::class, $dispatch]
            );
        }
    }

    public static function add_menu_items(array $menuItems): array
    {
        $currentPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '');
        $normalizedPath = '/' . trim($currentPath, '/');
        $adminBase = '/admin/plugins/' . self::MENU_SLUG;
        $isPluginPath = str_starts_with($normalizedPath, $adminBase);
        $activeSlug = CMS_M365ADMINSITES_Admin_Pages::current_page_slug();

        $menuItems[] = [
            'type' => 'item',
            'slug' => self::MENU_SLUG,
            'label' => 'M365 Adminsites',
            'icon' => '🧭',
            'url' => $adminBase . '/' . rawurlencode(self::MENU_SLUG),
            'active' => $isPluginPath && $activeSlug === self::MENU_SLUG,
        ];

        foreach (self::page_titles() as $slug => $label) {
            if ($slug === self::MENU_SLUG) {
                continue;
            }

            $menuItems[] = [
                'type' => 'item',
                'slug' => $slug,
                'parent' => self::MENU_SLUG,
                'label' => '↳ ' . $label,
                'icon' => '',
                'url' => self::admin_page_path($slug),
                'active' => $isPluginPath && $activeSlug === $slug,
            ];
        }

        return $menuItems;
    }

    public static function admin_page_path(string $slug): string
    {
        $slug = preg_replace('/[^a-z0-9_-]+/i', '', $slug) ?: self::MENU_SLUG;
        if (!array_key_exists($slug, self::page_titles())) {
            $slug = self::MENU_SLUG;
        }

        return '/admin/plugins/' . self::MENU_SLUG . '/' . rawurlencode($slug);
    }
}
