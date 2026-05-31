<?php
/**
 * CMS Forum – Admin Menu
 *
 * Registriert alle Admin-Menüpunkte für das Forum-Plugin.
 *
 * @package CMS_Forum
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Forum_Admin_Menu
{
    /**
     * Menüpunkte registrieren.
     */
    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'Forum',
            '365CMS | Forum',
            'manage_options',
            'forum-dashboard',
            [CMS_Forum_Admin_Pages::class, 'render_dashboard'],
            '💬',
            40
        );

        add_submenu_page(
            'forum-dashboard',
            'Dashboard',
            '📊 Dashboard',
            'manage_options',
            'forum-dashboard',
            [CMS_Forum_Admin_Pages::class, 'render_dashboard']
        );

        add_submenu_page(
            'forum-dashboard',
            'Kategorien',
            '🗂️ Kategorien',
            'manage_options',
            'forum-categories',
            [CMS_Forum_Admin_Pages::class, 'render_categories']
        );

        add_submenu_page(
            'forum-dashboard',
            'Foren',
            '📁 Foren',
            'manage_options',
            'forum-forums',
            [CMS_Forum_Admin_Pages::class, 'render_forums']
        );

        add_submenu_page(
            'forum-dashboard',
            'Threads',
            '📝 Threads',
            'manage_options',
            'forum-threads',
            [CMS_Forum_Admin_Pages::class, 'render_threads']
        );

        add_submenu_page(
            'forum-dashboard',
            'Benutzer',
            '👥 Benutzer',
            'manage_options',
            'forum-users',
            [CMS_Forum_Admin_Pages::class, 'render_users']
        );

        add_submenu_page(
            'forum-dashboard',
            'Ränge',
            '🏅 Ränge',
            'manage_options',
            'forum-ranks',
            [CMS_Forum_Admin_Pages::class, 'render_ranks']
        );

        add_submenu_page(
            'forum-dashboard',
            'Berechtigungen',
            '🔒 Berechtigungen',
            'manage_options',
            'forum-permissions',
            [CMS_Forum_Admin_Pages::class, 'render_permissions']
        );

        add_submenu_page(
            'forum-dashboard',
            'Meldungen',
            '🚩 Meldungen',
            'manage_options',
            'forum-reports',
            [CMS_Forum_Admin_Pages::class, 'render_reports']
        );

        add_submenu_page(
            'forum-dashboard',
            'Einstellungen',
            '⚙️ Einstellungen',
            'manage_options',
            'forum-settings',
            [CMS_Forum_Admin_Pages::class, 'render_settings']
        );
    }
}
