<?php
/**
 * CMS M365 Azure – Admin Menu.
 *
 * @package CMS_M365Azure
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365Azure_Admin_Menu
{
    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'M365 Azure',
            'M365 Azure',
            'manage_options',
            'm365azure',
            [CMS_M365Azure_Admin_Pages::class, 'render_dispatch'],
            '☁️'
        );
    }
}
