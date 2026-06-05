<?php
/**
 * CMS M365 Price Tracker – Admin menu.
 *
 * @package CMS_M365PRICETRACKER
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365PRICETRACKER_Admin_Menu
{
    public const PAGE_SLUG = 'm365price-tracker';

    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'M365 Price Tracker',
            'M365 Preise',
            'manage_options',
            self::PAGE_SLUG,
            [CMS_M365PRICETRACKER_Admin_Pages::class, 'render_dashboard'],
            '📈',
            59
        );
    }

    public static function register_routes(mixed $router = null): void
    {
        if (!is_object($router) || !function_exists('cms_plugin_admin_register_routes')) {
            return;
        }

        cms_plugin_admin_register_routes($router, self::PAGE_SLUG, [
            self::PAGE_SLUG => [CMS_M365PRICETRACKER_Admin_Pages::class, 'render_dashboard'],
        ], ['cms-m365price-tracker', 'm365price-tracker', 'm365price']);
    }
}
