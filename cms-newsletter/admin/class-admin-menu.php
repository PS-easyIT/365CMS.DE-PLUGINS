<?php
/**
 * @package CMS_Newsletter
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Newsletter_Admin_Menu
{
    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page('Newsletter', '365CMS | Newsletter', 'manage_options', 'newsletter-dashboard', [CMS_Newsletter_Admin_Pages::class, 'render_dashboard'], 'NL');
        add_submenu_page('newsletter-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'newsletter-dashboard', [CMS_Newsletter_Admin_Pages::class, 'render_dashboard']);
        add_submenu_page('newsletter-dashboard', 'Abonnenten', 'Abonnenten', 'manage_options', 'newsletter-subscribers', [CMS_Newsletter_Admin_Pages::class, 'render_subscribers']);
        add_submenu_page('newsletter-dashboard', 'Templates', 'Templates', 'manage_options', 'newsletter-templates', [CMS_Newsletter_Admin_Pages::class, 'render_templates']);
        add_submenu_page('newsletter-dashboard', 'Kampagnen', 'Kampagnen', 'manage_options', 'newsletter-campaigns', [CMS_Newsletter_Admin_Pages::class, 'render_campaigns']);
        add_submenu_page('newsletter-dashboard', 'Einstellungen', 'Einstellungen', 'manage_options', 'newsletter-settings', [CMS_Newsletter_Admin_Pages::class, 'render_settings']);
    }
}
