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
    private const MENU_SLUG = 'm365adminsites-dashboard';

    /**
     * @return array<string,string>
     */
    public static function page_titles(): array
    {
        return [
            self::MENU_SLUG => 'M365 Adminsites',
            'm365adminsites-content' => 'Inhalte & Texte',
            'm365adminsites-settings' => 'Anzeige & Design',
            'm365adminsites-help' => 'Hinweise',
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
            [CMS_M365ADMINSITES_Admin_Pages::class, 'render_dispatcher'],
            '🧭'
        );

        if (!function_exists('add_submenu_page')) {
            return;
        }

        foreach (self::page_titles() as $slug => $title) {
            add_submenu_page(
                self::MENU_SLUG,
                $title,
                $title,
                self::CAPABILITY,
                $slug,
                [CMS_M365ADMINSITES_Admin_Pages::class, 'render_dispatcher']
            );
        }
    }
}
