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
        // Hauptmenü
        add_menu_page(
            'Job Profile Generator',
            '📄 Job Profile',
            'manage_options',
            'jpg-dashboard',
            [CMS_JPG_Admin_Pages::class, 'render_dashboard'],
            ''
        );

        // Untermenüs
        add_submenu_page(
            'jpg-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'jpg-dashboard',
            [CMS_JPG_Admin_Pages::class, 'render_dashboard']
        );

        add_submenu_page(
            'jpg-dashboard',
            'Stellenanzeigen',
            '📄 Stellenanzeigen',
            'manage_options',
            'jpg-generator',
            [CMS_JPG_Admin_Pages::class, 'render_generator']
        );

        add_submenu_page(
            'jpg-dashboard',
            'Bibliotheken',
            '📚 Bibliotheken',
            'manage_options',
            'jpg-libraries',
            [CMS_JPG_Admin_Pages::class, 'render_libraries']
        );

        add_submenu_page(
            'jpg-dashboard',
            'Vorlagen & Design',
            '🎨 Vorlagen & Design',
            'manage_options',
            'jpg-design',
            [CMS_JPG_Admin_Pages::class, 'render_design']
        );

        add_submenu_page(
            'jpg-dashboard',
            'Workflow-Editor',
            '🔄 Workflow-Editor',
            'manage_options',
            'jpg-workflow',
            [CMS_JPG_Admin_Pages::class, 'render_workflow']
        );

        add_submenu_page(
            'jpg-dashboard',
            'Genehmigungen',
            '✅ Genehmigungen',
            'manage_options',
            'jpg-approvals',
            [CMS_JPG_Admin_Pages::class, 'render_approvals']
        );

        add_submenu_page(
            'jpg-dashboard',
            'Unternehmens-Übersicht',
            '🏢 Unternehmens-Übersicht',
            'manage_options',
            'jpg-companies',
            [CMS_JPG_Admin_Pages::class, 'render_company_overview']
        );

        add_submenu_page(
            'jpg-dashboard',
            'Benutzer & Mandanten',
            '👥 Benutzer & Mandanten',
            'manage_options',
            'jpg-users',
            [CMS_JPG_Admin_Pages::class, 'render_users']
        );

        add_submenu_page(
            'jpg-dashboard',
            'Abosystem & Pakete',
            '📦 Abosystem',
            'manage_options',
            'jpg-subscription',
            [CMS_JPG_Admin_Pages::class, 'render_subscription']
        );

        add_submenu_page(
            'jpg-dashboard',
            'Public Design',
            '🌐 Public Design',
            'manage_options',
            'jpg-public-design',
            [CMS_JPG_Admin_Pages::class, 'render_public_design']
        );

        add_submenu_page(
            'jpg-dashboard',
            'Einstellungen',
            '⚙️ Einstellungen',
            'manage_options',
            'jpg-settings',
            [CMS_JPG_Admin_Pages::class, 'render_settings']
        );
    }
}
