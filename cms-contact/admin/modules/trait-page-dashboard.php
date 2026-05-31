<?php
/**
 * CMS Contact – Admin Dashboard Trait
 *
 * @package CMS_Contact
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_Contact_Page_Dashboard_Trait
{
    /**
     * Dashboard-Seite rendern
     */
    public static function render_dashboard(): void
    {
        self::check_access();

        $csrfToken   = self::generate_nonce('contact_dashboard');
        $forms       = CMS_Contact_Forms::instance();
        $submissions = CMS_Contact_Submissions::instance();

        $allForms    = $forms->get_all();
        $globalStats = $submissions->get_global_stats();
        $trend       = $submissions->get_trend(7);
        $securityStats = $submissions->get_security_event_stats(24);
        $recentSecurityEvents = $submissions->get_recent_security_events(8);

        // Pro-Formular-Statistiken
        $formIds = array_map(static fn (array $form): int => (int) ($form['id'] ?? 0), $allForms);
        $formStats = $forms->get_stats_for_form_ids($formIds);

        // Letzte Nachrichten
        $recentSubmissions = $submissions->get_all(['is_spam' => 0], 0, 5);

        $activeSection = 'dashboard';
        include CMS_CONTACT_PLUGIN_DIR . 'admin/views/page-dashboard.php';
    }
}
