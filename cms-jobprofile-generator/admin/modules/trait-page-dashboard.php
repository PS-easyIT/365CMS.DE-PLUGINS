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

        $qualityMonitor = self::build_jobposting_quality_monitor();
        $bottleneckAlerts = self::build_bottleneck_alerts();

        self::render_admin_view(
            'Job Profile Dashboard',
            'jpg-dashboard',
            JPG_DIR . 'admin/views/page-dashboard.php',
            compact(
                'tab',
                'showPrivate',
                'stats',
                'tabs',
                'profiles',
                'total',
                'companiesCount',
                'qualityMonitor',
                'bottleneckAlerts'
            )
        );
    }

    /**
     * Feature: JobPosting Schema Quality Monitor.
     *
     * @return array{
     *     checked:int,
     *     total_issues:int,
     *     critical:int,
     *     warning:int,
     *     items:array<int,array<string,mixed>>
     * }
     */
    private static function build_jobposting_quality_monitor(): array
    {
        $result = [
            'checked'      => 0,
            'total_issues' => 0,
            'critical'     => 0,
            'warning'      => 0,
            'items'        => [],
        ];

        try {
            $db = \CMS\Database::instance();
            $p  = $db->getPrefix();

            $profiles = $db->get_results(
                "SELECT id, title, slug, summary, description, location, remote_option,
                        salary_min, salary_max, published_at, updated_at
                   FROM {$p}jpg_profiles
                  WHERE status = 'published'
                  ORDER BY published_at DESC, created_at DESC
                  LIMIT 250",
                []
            ) ?: [];

            $result['checked'] = count($profiles);
            $staleThreshold = strtotime('-45 days');

            foreach ($profiles as $profile) {
                $issues = [];

                $slug = (string) ($profile->slug ?? '');
                if ($slug === '' || !preg_match('/^[a-z0-9\-_]+$/', $slug)) {
                    $issues[] = ['level' => 'critical', 'message' => 'Ungültiger oder fehlender Slug'];
                }

                $summary = trim((string) ($profile->summary ?? ''));
                $description = trim((string) ($profile->description ?? ''));
                if ($summary === '' && $description === '') {
                    $issues[] = ['level' => 'critical', 'message' => 'Weder Summary noch Description gepflegt'];
                }

                $isRemoteOnly = (string) ($profile->remote_option ?? '') === 'remote';
                $location = trim((string) ($profile->location ?? ''));
                if (!$isRemoteOnly && $location === '') {
                    $issues[] = ['level' => 'critical', 'message' => 'Standort fehlt für nicht-remote Stelle'];
                }

                if (empty($profile->salary_min) && empty($profile->salary_max)) {
                    $issues[] = ['level' => 'warning', 'message' => 'Keine Gehaltsangabe gepflegt'];
                }

                $publishedAt = strtotime((string) ($profile->published_at ?? ''));
                $updatedAt   = strtotime((string) ($profile->updated_at ?? ''));
                $relevantTs  = max($publishedAt ?: 0, $updatedAt ?: 0);
                if ($relevantTs > 0 && $relevantTs < $staleThreshold) {
                    $issues[] = ['level' => 'warning', 'message' => 'Stelle ist seit >45 Tagen unverändert online'];
                }

                if ($issues === []) {
                    continue;
                }

                foreach ($issues as $issue) {
                    $result['total_issues']++;
                    if ($issue['level'] === 'critical') {
                        $result['critical']++;
                    } else {
                        $result['warning']++;
                    }
                }

                $result['items'][] = [
                    'id'      => (int) ($profile->id ?? 0),
                    'title'   => (string) ($profile->title ?? ''),
                    'slug'    => $slug,
                    'issues'  => $issues,
                    'editUrl' => '/admin/plugins/jpg-dashboard/jpg-generator?id=' . (int) ($profile->id ?? 0),
                ];
            }

            usort($result['items'], static function (array $a, array $b): int {
                return count($b['issues']) <=> count($a['issues']);
            });
            $result['items'] = array_slice($result['items'], 0, 8);
        } catch (\Throwable $e) {
            // Monitor ist rein informativ – Fehler nicht eskalieren.
        }

        return $result;
    }

    /**
     * Feature: Hiring Funnel Bottleneck Alerts.
     *
     * @return array{
     *   delayed_new:int,
     *   delayed_reviewing:int,
     *   items:array<int,array<string,mixed>>
     * }
     */
    private static function build_bottleneck_alerts(): array
    {
        $result = [
            'delayed_new'       => 0,
            'delayed_reviewing' => 0,
            'items'             => [],
        ];

        try {
            $db = \CMS\Database::instance();
            $p  = $db->getPrefix();

            $rows = $db->get_results(
                "SELECT a.id, a.status, a.updated_at, a.created_at,
                        a.applicant_name, p.id AS job_id, p.title AS job_title
                   FROM {$p}jpg_applications a
             INNER JOIN {$p}jpg_profiles p ON p.id = a.job_id
                  WHERE a.status IN ('new','reviewing')
                  ORDER BY a.updated_at ASC
                  LIMIT 500",
                []
            ) ?: [];

            foreach ($rows as $row) {
                $status = (string) ($row->status ?? '');
                $lastTs = strtotime((string) ($row->updated_at ?? '')) ?: strtotime((string) ($row->created_at ?? ''));
                if (!$lastTs) {
                    continue;
                }
                $ageDays = (int) floor((time() - $lastTs) / 86400);

                $threshold = $status === 'new' ? 7 : 14;
                if ($ageDays < $threshold) {
                    continue;
                }

                if ($status === 'new') {
                    $result['delayed_new']++;
                } else {
                    $result['delayed_reviewing']++;
                }

                $result['items'][] = [
                    'application_id' => (int) ($row->id ?? 0),
                    'job_id'         => (int) ($row->job_id ?? 0),
                    'job_title'      => (string) ($row->job_title ?? ''),
                    'applicant_name' => (string) ($row->applicant_name ?? ''),
                    'status'         => $status,
                    'age_days'       => $ageDays,
                    'link'           => '/member/jobs/applications?job_id=' . (int) ($row->job_id ?? 0),
                ];
            }

            usort($result['items'], static function (array $a, array $b): int {
                return $b['age_days'] <=> $a['age_days'];
            });
            $result['items'] = array_slice($result['items'], 0, 8);
        } catch (\Throwable $e) {
            // Alerting ist rein informativ – Fehler nicht eskalieren.
        }

        return $result;
    }
}
