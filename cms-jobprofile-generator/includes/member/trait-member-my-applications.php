<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Member-Trait: Bewerber-Dashboard – Eigene Bewerbungen einsehen
 *
 * Zeigt dem eingeloggten Benutzer seine **eigenen** abgegebenen Bewerbungen
 * (im Gegensatz zu trait-member-applications.php, das dem Arbeitgeber die
 * eingehenden Bewerbungen auf seine Stellen zeigt).
 *
 * render_my_applications()        – Standalone Route /member/jobs/my-applications
 * render_my_applications_inline() – Inline für PluginDashboardRegistry
 *
 * @since   0.9.7
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Member_MyApplications_Trait
{
    /**
     * GET /member/jobs/my-applications – Eigene Bewerbungen (Bewerber-Sicht)
     */
    public function render_my_applications(): void
    {
        $this->require_auth();

        $statusFilter = sanitize_key($_GET['status'] ?? '');
        $validStatuses = ['new', 'reviewing', 'accepted', 'rejected'];

        try {
            $sql = "SELECT a.id, a.applicant_name, a.applicant_email, a.cover_letter,
                           a.cv_file_token, a.status, a.created_at, a.updated_at,
                           p.title AS job_title, p.id AS job_id, p.slug AS job_slug,
                           p.location_city, p.employment_type, p.status AS job_status
                    FROM {$this->p}jpg_applications a
                    INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                    WHERE a.user_id = ?";
            $params = [$this->userId];

            if ($statusFilter !== '' && in_array($statusFilter, $validStatuses, true)) {
                $sql     .= ' AND a.status = ?';
                $params[] = $statusFilter;
            }

            $sql .= ' ORDER BY a.created_at DESC';
            $myApplications = $this->db->get_results($sql, $params) ?: [];
        } catch (\Throwable $e) {
            $myApplications = [];
        }

        // Statistiken
        $stats = $this->get_my_application_stats();

        $this->render_with_layout('views/member/page-my-applications.php',
            compact('myApplications', 'statusFilter', 'stats'));
    }

    /**
     * Inline-Render für PluginDashboardRegistry.
     */
    public function render_my_applications_inline(object $user): void
    {
        $this->render_back_button();
        $this->userId = (int) $user->id;

        $statusFilter = sanitize_key($_GET['status'] ?? '');
        $validStatuses = ['new', 'reviewing', 'accepted', 'rejected'];

        try {
            $sql = "SELECT a.id, a.applicant_name, a.applicant_email, a.cover_letter,
                           a.cv_file_token, a.status, a.created_at, a.updated_at,
                           p.title AS job_title, p.id AS job_id, p.slug AS job_slug,
                           p.location_city, p.employment_type, p.status AS job_status
                    FROM {$this->p}jpg_applications a
                    INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                    WHERE a.user_id = ?";
            $params = [$this->userId];

            if ($statusFilter !== '' && in_array($statusFilter, $validStatuses, true)) {
                $sql     .= ' AND a.status = ?';
                $params[] = $statusFilter;
            }

            $sql .= ' ORDER BY a.created_at DESC';
            $myApplications = $this->db->get_results($sql, $params) ?: [];
        } catch (\Throwable $e) {
            $myApplications = [];
        }

        $stats   = $this->get_my_application_stats();
        $baseUrl = '/member/plugin/member-jobs';
        include JPG_DIR . 'views/member/page-my-applications.php';
    }

    /**
     * Statistiken zu den eigenen Bewerbungen.
     *
     * @return array{total: int, new: int, reviewing: int, accepted: int, rejected: int}
     */
    private function get_my_application_stats(): array
    {
        $stats = ['total' => 0, 'new' => 0, 'reviewing' => 0, 'accepted' => 0, 'rejected' => 0];
        try {
            $rows = $this->db->get_results(
                "SELECT status, COUNT(*) AS cnt
                 FROM {$this->p}jpg_applications
                 WHERE user_id = ?
                 GROUP BY status",
                [$this->userId]
            ) ?: [];
            foreach ($rows as $row) {
                $key = $row->status ?? '';
                if (isset($stats[$key])) {
                    $stats[$key] = (int) $row->cnt;
                }
                $stats['total'] += (int) $row->cnt;
            }
        } catch (\Throwable $e) { /* Non-fatal */ }
        return $stats;
    }

    /**
     * Dashboard-Widget: Meine Bewerbungen (Bewerber-Sicht).
     *
     * @param  array<int, array<string, mixed>> $widgets
     * @return array<int, array<string, mixed>>
     */
    public function add_my_applications_widget(array $widgets): array
    {
        if (!$this->auth->isLoggedIn()) {
            return $widgets;
        }
        $uid = method_exists($this->auth, 'getUserId') ? (int) $this->auth->getUserId() : 0;
        if ($uid <= 0) {
            return $widgets;
        }

        try {
            $appCount = (int) $this->db->get_var(
                "SELECT COUNT(*) FROM {$this->p}jpg_applications WHERE user_id = ?",
                [$uid]
            );
        } catch (\Throwable $e) {
            $appCount = 0;
        }

        // Nur anzeigen, wenn der User auch Bewerbungen hat
        if ($appCount <= 0) {
            return $widgets;
        }

        try {
            $recent = $this->db->get_results(
                "SELECT a.status, a.created_at, p.title AS job_title, p.id AS job_id
                 FROM {$this->p}jpg_applications a
                 INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                 WHERE a.user_id = ?
                 ORDER BY a.created_at DESC
                 LIMIT 3",
                [$uid]
            ) ?: [];
        } catch (\Throwable $e) {
            $recent = [];
        }

        ob_start();
        echo '<div style="font-size:.875rem;">';
        if (empty($recent)) {
            echo '<p style="color:#64748b;margin:0;">Du hast noch keine Bewerbungen abgeschickt.</p>';
        } else {
            $statusLabels = ['new' => 'Gesendet', 'reviewing' => 'In Prüfung', 'rejected' => 'Abgelehnt', 'accepted' => 'Angenommen'];
            $statusColors = ['new' => '#dbeafe', 'reviewing' => '#fef3c7', 'rejected' => '#fee2e2', 'accepted' => '#d1fae5'];
            echo '<table style="width:100%;border-collapse:collapse;">';
            echo '<thead><tr style="background:#f8fafc;">'
                . '<th style="padding:.5rem;text-align:left;font-size:.8rem;color:#475569;">Stelle</th>'
                . '<th style="padding:.5rem;text-align:left;font-size:.8rem;color:#475569;">Status</th>'
                . '</tr></thead><tbody>';
            foreach ($recent as $row) {
                $bg    = $statusColors[$row->status] ?? '#f1f5f9';
                $label = $statusLabels[$row->status] ?? htmlspecialchars($row->status);
                echo '<tr style="border-bottom:1px solid #f1f5f9;">'
                    . '<td style="padding:.45rem .5rem;">' . htmlspecialchars($row->job_title) . '</td>'
                    . '<td style="padding:.45rem .5rem;"><span style="background:' . $bg . ';border-radius:4px;padding:.15rem .45rem;font-size:.78rem;">' . $label . '</span></td>'
                    . '</tr>';
            }
            echo '</tbody></table>';
            echo '<div style="text-align:right;margin-top:.5rem;">'
                . '<a href="/member/jobs/my-applications" style="font-size:.8rem;color:#3b82f6;">Alle anzeigen →</a></div>';
        }
        echo '</div>';
        $html = ob_get_clean();

        $widgets[] = [
            'title'    => '📋 Meine Bewerbungen',
            'html'     => $html,
            'priority' => 25,
        ];
        return $widgets;
    }

    /**
     * Dashboard-Stat: Anzahl eigener Bewerbungen.
     *
     * @param  array<int, array<string, mixed>> $stats
     * @return array<int, array<string, mixed>>
     */
    public function add_my_applications_stat(array $stats): array
    {
        if (!$this->auth->isLoggedIn()) {
            return $stats;
        }
        $uid = method_exists($this->auth, 'getUserId') ? (int) $this->auth->getUserId() : 0;
        if ($uid <= 0) {
            return $stats;
        }

        try {
            $count = (int) $this->db->get_var(
                "SELECT COUNT(*) FROM {$this->p}jpg_applications WHERE user_id = ?",
                [$uid]
            );
        } catch (\Throwable $e) {
            $count = 0;
        }

        // Nur anzeigen wenn Bewerbungen vorhanden
        if ($count > 0) {
            $stats[] = [
                'label' => 'Meine Bewerbungen',
                'value' => $count,
                'icon'  => '📋',
                'url'   => '/member/jobs/my-applications',
                'color' => '#8b5cf6',
            ];
        }

        return $stats;
    }
}
