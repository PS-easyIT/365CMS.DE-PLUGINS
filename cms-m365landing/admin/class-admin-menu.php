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
    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'M365 Landing',
            'M365 Landing',
            'manage_options',
            'm365landing',
            [CMS_M365Landing_Admin_Pages::class, 'render_dispatch'],
            '🏠'
        );
    }
}
