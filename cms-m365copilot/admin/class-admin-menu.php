<?php
/**
 * CMS M365 Copilot – Admin menu.
 *
 * @package CMS_M365Copilot
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365Copilot_Admin_Menu
{
    private const ROOT_PAGE_SLUG = 'm365copilot-settings';

    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'M365 Copilot Landing',
            'M365 Copilot',
            'manage_options',
            self::ROOT_PAGE_SLUG,
            [CMS_M365Copilot_Admin_Pages::class, 'render_settings'],
            '🤖',
            58
        );
    }

    public static function register_routes(mixed $router = null): void
    {
        if (!is_object($router) || !function_exists('cms_plugin_admin_register_routes')) {
            return;
        }

        cms_plugin_admin_register_routes($router, self::ROOT_PAGE_SLUG, [
            self::ROOT_PAGE_SLUG => [CMS_M365Copilot_Admin_Pages::class, 'render_settings'],
        ], ['cms-m365copilot', 'm365copilot']);
    }
}
