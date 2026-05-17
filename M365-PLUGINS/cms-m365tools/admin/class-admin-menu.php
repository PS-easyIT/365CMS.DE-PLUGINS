<?php
/**
 * CMS M365 Tools – Admin Menü.
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
            'M365 Tools',
            'M365 Tools',
            'manage_options',
            'm365tools-dashboard',
            [$pages, 'render_dashboard'],
            '🧮',
            57
        );

        add_submenu_page(
            'm365tools-dashboard',
            'Dashboard',
            '📊 Dashboard',
            'manage_options',
            'm365tools-dashboard',
            [$pages, 'render_dashboard']
        );

        foreach (CMS_M365CALCULATOR_Tool_Registry::ordered_tools(false) as $tool) {
            $moduleKey = (string) ($tool['key'] ?? '');
            if ($moduleKey === '') {
                continue;
            }

            $title = (string) ($tool['title'] ?? $moduleKey);
            add_submenu_page(
                'm365tools-dashboard',
                $title . ' – Einstellungen',
                '⚙️ ' . $title,
                'manage_options',
                'm365tools-module-' . $moduleKey,
                static function () use ($moduleKey): void {
                    CMS_M365CALCULATOR_Admin_Pages::render_module_settings($moduleKey);
                }
            );
        }
    }
}
