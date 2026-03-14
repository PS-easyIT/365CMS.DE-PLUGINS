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

        // Pro-Formular-Statistiken
        $formStats = [];
        foreach ($allForms as $form) {
            $formStats[$form['id']] = $forms->get_stats((int) $form['id']);
        }

        // Letzte Nachrichten
        $recentSubmissions = $submissions->get_all(['is_spam' => 0], 0, 5);

        $activeSection = 'dashboard';
        include CMS_CONTACT_PLUGIN_DIR . 'admin/views/page-dashboard.php';
    }
}
