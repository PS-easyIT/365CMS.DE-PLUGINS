<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin-Seite: Dashboard
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Page_Dashboard_Trait
{
    // ── 1. DASHBOARD ─────────────────────────────────────────────────────────

    public static function render_dashboard(): void
    {
        self::check_access();

        $tab         = sanitize_key($_GET['tab'] ?? 'overview');
        $showPrivate = !empty($_GET['show_private']); // Phase 9: Privacy-Filter-Toggle

        $stats_raw  = CMS_JPG_Profiles::instance()->get_stats_summary();
        $stats      = ['draft' => 0, 'published' => 0, 'archived' => 0];
        foreach ($stats_raw as $row) {
            $stats[$row->status] = (int) $row->cnt;
        }

        $tabs = [
            'overview'   => 'Übersicht',
            'drafts'     => 'Entwürfe',
            'published'  => 'Veröffentlicht',
            'archived'   => 'Archiv',
            'statistics' => 'Statistiken',
        ];

        // Profil-Liste für den aktiven Tab (Phase 9: private Profile ausblenden sofern kein Toggle)
        $listArgs = match ($tab) {
            'drafts'    => ['status' => 'draft',    'limit' => 25, 'hide_private' => !$showPrivate],
            'published' => ['status' => 'published', 'limit' => 25, 'hide_private' => !$showPrivate],
            'archived'  => ['status' => 'archived',  'limit' => 25, 'hide_private' => !$showPrivate],
            default     => ['limit' => 5,             'hide_private' => !$showPrivate],
        };

        $profiles = CMS_JPG_Profiles::instance()->get_list($listArgs);
        $total    = CMS_JPG_Profiles::instance()->count($listArgs);

        // Unternehmensanzahl für Dashboard-Kachel
        $companiesCount = 0;
        try {
            $db  = \CMS\Database::instance();
            $p   = $db->getPrefix();
            $companiesCount = (int) $db->get_var(
                "SELECT COUNT(*) FROM {$p}companies WHERE status = 'active'", []
            );
        } catch (\Throwable $e) { /* cms-companies ggf. nicht aktiv */ }

        include JPG_DIR . 'admin/views/page-dashboard.php';
    }
}
