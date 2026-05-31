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
    private const ROOT_SLUG = 'm365matrices-dashboard';

    public static function register(): void
    {
        if (!function_exists('add_menu_page') || !function_exists('add_submenu_page')) {
            return;
        }

        $pages = CMS_M365MATRICES_Admin_Pages::class;

        add_menu_page(
            'M365 Matrixen',
            'M365 Matrixen',
            'manage_options',
            self::ROOT_SLUG,
            [$pages, 'render_dispatch'],
            '📚'
        );

        add_submenu_page(
            self::ROOT_SLUG,
            'M365 Matrixen – Übersicht',
            '📊 Übersicht',
            'manage_options',
            self::ROOT_SLUG,
            [$pages, 'render_dispatch']
        );

        add_submenu_page(
            self::ROOT_SLUG,
            'M365 Matrixen – Lizenzmatrix',
            '📊 Lizenzmatrix',
            'manage_options',
            'm365matrices-suite',
            [$pages, 'render_dispatch']
        );

        add_submenu_page(
            self::ROOT_SLUG,
            'M365 Matrixen – Add-on-Matrix',
            '➕ Add-on-Matrix',
            'manage_options',
            'm365matrices-addon',
            [$pages, 'render_dispatch']
        );

        add_submenu_page(
            self::ROOT_SLUG,
            'M365 Matrixen – Copilot-Matrix',
            '🤖 Copilot-Matrix',
            'manage_options',
            'm365matrices-copilot',
            [$pages, 'render_dispatch']
        );

        add_submenu_page(
            self::ROOT_SLUG,
            'M365 Matrixen – Inhaltsverzeichnis',
            '🧭 Inhaltsverzeichnis',
            'manage_options',
            'm365matrices-toc',
            [$pages, 'render_dispatch']
        );

        add_submenu_page(
            self::ROOT_SLUG,
            'M365 Matrixen – Design',
            '🎨 Design',
            'manage_options',
            'm365matrices-design',
            [$pages, 'render_dispatch']
        );
    }
}