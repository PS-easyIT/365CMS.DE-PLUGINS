<?php
/**
 * CMS M365 Calculator – Admin Menü.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Admin_Menu
{
    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        $pages = CMS_M365CALCULATOR_Admin_Pages::class;

        add_menu_page(
            'M365 Rechner',
            'M365 Rechner',
            'manage_options',
            'm365calculator-dashboard',
            [$pages, 'render_dashboard'],
            '🧮',
            57
        );

        add_submenu_page(
            'm365calculator-dashboard',
            'Dashboard',
            '📊 Dashboard',
            'manage_options',
            'm365calculator-dashboard',
            [$pages, 'render_dashboard']
        );
    }
}
