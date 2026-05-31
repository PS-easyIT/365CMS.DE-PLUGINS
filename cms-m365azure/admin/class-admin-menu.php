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
    private const PAGE_SLUG = 'm365azure';

    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'M365 Azure',
            'M365 Azure',
            'manage_options',
            self::PAGE_SLUG,
            [self::class, 'dispatch'],
            '☁️'
        );
    }

    public static function dispatch(): void
    {
        $callbacks = [
            self::PAGE_SLUG => [CMS_M365Azure_Admin_Pages::class, 'render_dispatch'],
        ];

        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbacks, self::PAGE_SLUG, self::PAGE_SLUG);
            return;
        }

        $callback = $callbacks[self::PAGE_SLUG] ?? null;
        if (is_callable($callback)) {
            call_user_func($callback);
            return;
        }

        echo '<div class="alert alert-error" role="alert">Admin-Seite ist derzeit nicht verfügbar.</div>';
    }
}
