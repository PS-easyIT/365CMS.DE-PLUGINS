<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin-Menü Registrierung – 5 Hauptpunkte
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
class CMS_JPG_Admin_Menu
{
    public static function register(): void
    {
        $callback = [CMS_JPG_Admin_Pages::class, 'dispatch_admin_page'];

        // Hauptmenü
        add_menu_page(
            'Job Profile Generator',
            'Job Profile',
            'manage_options',
            'jpg-dashboard',
            $callback,
            ''
        );

        // Untermenüs
        add_submenu_page(
            'jpg-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'jpg-dashboard',
            $callback
        );

        add_submenu_page(
            'jpg-dashboard',
            'Stellenanzeigen',
            'Stellenanzeigen',
            'manage_options',
            'jpg-generator',
            $callback
        );

        add_submenu_page(
            'jpg-dashboard',
            'Bibliotheken',
            'Bibliotheken',
            'manage_options',
            'jpg-libraries',
            $callback
        );

        add_submenu_page(
            'jpg-dashboard',
            'Vorlagen & Design',
            'Vorlagen & Design',
            'manage_options',
            'jpg-design',
            $callback
        );

        add_submenu_page(
            'jpg-dashboard',
            'Workflow-Editor',
            'Workflow-Editor',
            'manage_options',
            'jpg-workflow',
            $callback
        );

        add_submenu_page(
            'jpg-dashboard',
            'Genehmigungen',
            'Genehmigungen',
            'manage_options',
            'jpg-approvals',
            $callback
        );

        add_submenu_page(
            'jpg-dashboard',
            'Unternehmens-Übersicht',
            'Unternehmens-Übersicht',
            'manage_options',
            'jpg-companies',
            $callback
        );

        add_submenu_page(
            'jpg-dashboard',
            'Benutzer & Mandanten',
            'Benutzer & Mandanten',
            'manage_options',
            'jpg-users',
            $callback
        );

        add_submenu_page(
            'jpg-dashboard',
            'Abosystem & Pakete',
            'Abosystem',
            'manage_options',
            'jpg-subscription',
            $callback
        );

        add_submenu_page(
            'jpg-dashboard',
            'Public Design',
            'Public Design',
            'manage_options',
            'jpg-public-design',
            $callback
        );

        add_submenu_page(
            'jpg-dashboard',
            'Einstellungen',
            'Einstellungen',
            'manage_options',
            'jpg-settings',
            $callback
        );
    }
}
