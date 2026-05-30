<?php
/**
 * CMS M365 Matrixen – Admin Menü.
 *
 * @package CMS_M365MATRICES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365MATRICES_Admin_Menu
{
    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        $pages = CMS_M365MATRICES_Admin_Pages::class;

        add_menu_page(
            'M365 Matrixen',
            'M365 Matrixen',
            'manage_options',
            'm365matrices-dashboard',
            [$pages, 'render_dashboard'],
            '📚'
        );

        add_submenu_page(
            'm365matrices-dashboard',
            'M365 Matrixen – Einstellungen',
            '📚 Matrixen',
            'manage_options',
            'm365matrices-dashboard',
            [$pages, 'render_dashboard']
        );
    }
}