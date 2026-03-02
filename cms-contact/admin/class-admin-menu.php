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
     * Einzelnen Menüpunkt registrieren (keine Untermenüs).
     * Navigation innerhalb des Plugins erfolgt über Section-Nav-Tabs.
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
            'contact',
            [CMS_Contact_Admin_Pages::class, 'render_dispatch'],
            '📬',
            35
        );
    }
}
