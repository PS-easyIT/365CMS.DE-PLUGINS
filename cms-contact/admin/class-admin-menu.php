<?php
/**
 * CMS Contact – Admin Menu
 *
 * @package CMS_Contact
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Contact_Admin_Menu
{
    /**
     * Menüpunkte registrieren
     */
    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'Kontakt',
            'Kontakt',
            'manage_options',
            'contact-dashboard',
            [CMS_Contact_Admin_Pages::class, 'render_dashboard'],
            '📬',
            35
        );

        add_submenu_page(
            'contact-dashboard',
            'Dashboard',
            '📊 Dashboard',
            'manage_options',
            'contact-dashboard',
            [CMS_Contact_Admin_Pages::class, 'render_dashboard']
        );

        add_submenu_page(
            'contact-dashboard',
            'Formulare',
            '📋 Formulare',
            'manage_options',
            'contact-forms',
            [CMS_Contact_Admin_Pages::class, 'render_forms']
        );

        add_submenu_page(
            'contact-dashboard',
            'Nachrichten',
            '📩 Nachrichten',
            'manage_options',
            'contact-submissions',
            [CMS_Contact_Admin_Pages::class, 'render_submissions']
        );

        add_submenu_page(
            'contact-dashboard',
            'Einstellungen',
            '⚙️ Einstellungen',
            'manage_options',
            'contact-settings',
            [CMS_Contact_Admin_Pages::class, 'render_settings']
        );
    }
}
