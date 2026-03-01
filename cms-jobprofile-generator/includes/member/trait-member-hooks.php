<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Trait: Hooks, Routen, Sidebar-Menüpunkte, Dashboard-Widgets
 *
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Member_Hooks_Trait
{
    // ── Hooks ────────────────────────────────────────────────────────────────

    private function register_hooks(): void
    {
        if (!class_exists('CMS\\Hooks')) {
            return;
        }

        // Benachrichtigungs-Einstellungen
        \CMS\Hooks::addFilter('member_notification_settings_sections', [$this, 'add_notification_section'], 10);
        \CMS\Hooks::addFilter('member_notification_preferences', [$this, 'save_notification_preferences'], 10, 2);

        // DSGVO: Datenexport (Art. 20) & Account-Löschung (Art. 17)
        \CMS\Hooks::addFilter('cms_member_data_export_requested', [$this, 'handle_data_export'], 10, 2);
        \CMS\Hooks::addAction('cms_member_account_deletion_requested', [$this, 'handle_account_deletion'], 10);

        // PluginDashboardRegistry – korrekte Einbindung ins Member-Layout
        \CMS\Hooks::addAction('member_dashboard_init', [$this, 'register_via_plugin_dashboard'], 10);

        // Aktiv-Zustand der Menüpunkte für /member/jobs/* Standalone-Routen korrigieren
        \CMS\Hooks::addFilter('member_menu_items', [$this, 'fix_active_menu_states'], 30);

        // Admin-CSS für Plugin-Seiten im Member-Bereich bereitstellen
        \CMS\Hooks::addAction('member_plugin_section_head', [$this, 'enqueue_member_section_styles'], 10, 2);
    }

    private function register_routes(): void
    {
        if (!class_exists('CMS\\Router')) {
            return;
        }
        $router = \CMS\Router::instance();
        $router->addRoute('GET',  '/member/jobs',                      [$this, 'render_list']);
        $router->addRoute('GET',  '/member/jobs/create',               [$this, 'render_create']);
        $router->addRoute('POST', '/member/jobs/create',               [$this, 'render_create']);
        $router->addRoute('GET',  '/member/jobs/edit/:id',             [$this, 'render_edit']);
        $router->addRoute('POST', '/member/jobs/edit/:id',             [$this, 'render_edit']);
        $router->addRoute('POST', '/member/jobs/workflow/submit/:id',  [$this, 'handle_workflow_submit']);
        $router->addRoute('GET',  '/member/jobs/approvals',            [$this, 'render_approvals']);
        $router->addRoute('POST', '/member/jobs/approvals',            [$this, 'render_approvals']);
        $router->addRoute('GET',  '/member/jobs/applications',         [$this, 'render_applications']);
        $router->addRoute('POST', '/member/jobs/applications/status',  [$this, 'ajax_update_status']);
        $router->addRoute('GET',  '/member/jobs/my-applications',      [$this, 'render_my_applications']);
        $router->addRoute('GET',  '/member/jobs/download/:token',      [$this, 'download_file']);
        $router->addRoute('GET',  '/member/jobs/pdf/:id',              [$this, 'download_pdf']);
        $router->addRoute('GET',  '/member/jobs/settings',             [$this, 'render_settings']);
        $router->addRoute('POST', '/member/jobs/settings',             [$this, 'render_settings']);
        $router->addRoute('GET',  '/member/jobs/duplicate/:id',        [$this, 'render_duplicate']);
        $router->addRoute('POST', '/member/jobs',                      [$this, 'render_list']);
    }

    // ── Hook-Callbacks ────────────────────────────────────────────────────────

    /**
     * Für PluginDashboardRegistry stats_callback.
     * @return array{count: int, label: string}
     */
    public function get_dashboard_stats(): array
    {
        if (!$this->auth->isLoggedIn()) {
            return ['count' => 0, 'label' => 'Noch keine Jobs'];
        }
        $uid     = method_exists($this->auth, 'getUserId') ? (int) $this->auth->getUserId() : 0;
        $isAdmin = method_exists($this->auth, 'isAdmin')   ? $this->auth->isAdmin()          : false;
        try {
            if ($isAdmin) {
                $published = (int) $this->db->get_var(
                    "SELECT COUNT(*) FROM {$this->p}jpg_profiles WHERE status = 'published'", []
                );
                $total = (int) $this->db->get_var(
                    "SELECT COUNT(*) FROM {$this->p}jpg_profiles WHERE status != 'trash'", []
                );
            } else {
                $sql = "SELECT COUNT(*) FROM {$this->p}jpg_profiles p
                         WHERE (p.created_by = ?
                            OR p.company_id IN (
                               SELECT id FROM {$this->p}companies WHERE user_id = ?
                            ))
                           AND p.status = 'published'";
                $published = (int) $this->db->get_var($sql, [$uid, $uid]);
                $sql = str_replace("AND p.status = 'published'", "AND p.status != 'trash'", $sql);
                $total     = (int) $this->db->get_var($sql, [$uid, $uid]);
            }
        } catch (\Throwable $e) {
            $published = 0;
            $total     = 0;
        }
        $label = $total > 0
            ? ($published . ' aktiv' . ($total !== $published ? ' / ' . ($total - $published) . ' Entw.' : ''))
            : 'Noch keine Jobs';
        return ['count' => $total, 'label' => $label];
    }

    /**
     * Fügt „Stellenanzeigen" in die Member-Sidebar ein.
     *
     * @param  array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public function add_menu_item(array $items): array
    {
        $isAdmin    = method_exists($this->auth, 'isAdmin')   ? $this->auth->isAdmin() : false;
        $currentUid = method_exists($this->auth, 'getUserId') ? (int) $this->auth->getUserId() : 0;
        $uri        = $_SERVER['REQUEST_URI'] ?? '';

        $isJobsArea = str_starts_with($uri, '/member/jobs') ||
                     str_contains($uri, 'member-job-libraries') ||
                     str_contains($uri, 'member-job-templates');

        $items[] = [
            'slug'     => 'member_jobs',
            'label'    => 'Stellenanzeigen',
            'icon'     => '📄',
            'url'      => '/member/jobs',
            'active'   => str_starts_with($uri, '/member/jobs') &&
                          !str_contains($uri, '/approvals') &&
                          !str_contains($uri, '/applications') &&
                          !str_contains($uri, '/settings'),
            'category' => 'plugins',
        ];

        // ── Sub-Items: alle als Kinder von member_jobs ──────────────────────
        // Stellenanzeigen → Übersicht (direkte Unterseite)
        if ($currentUid > 0) {
            $items[] = [
                'slug'        => 'member_jobs_list',
                'label'       => 'Meine Stellen',
                'icon'        => '📋',
                'url'         => '/member/jobs',
                'active'      => str_starts_with($uri, '/member/jobs') &&
                                 !str_contains($uri, '/create') &&
                                 !str_contains($uri, '/edit') &&
                                 !str_contains($uri, '/approvals') &&
                                 !str_contains($uri, '/applications') &&
                                 !str_contains($uri, '/settings'),
                'category'    => 'plugins',
                'parent_slug' => 'member_jobs',
            ];
            $items[] = [
                'slug'        => 'member_jobs_create',
                'label'       => 'Neue Stelle',
                'icon'        => '➕',
                'url'         => '/member/jobs/create',
                'active'      => str_starts_with($uri, '/member/jobs/create'),
                'category'    => 'plugins',
                'parent_slug' => 'member_jobs',
            ];
        }

        $showApprovals = $isAdmin;
        if (!$showApprovals && $currentUid > 0 && class_exists('CMS_JPG_Workflow')) {
            try {
                $wf       = CMS_JPG_Workflow::instance();
                $authUser = method_exists($this->auth, 'currentUser') ? $this->auth->currentUser() : null;
                $userRole = $authUser ? (string)($authUser->role ?? '') : '';
                foreach ($wf->get_steps() as $step) {
                    if (!empty($step->approver_role) && $step->approver_role === $userRole) {
                        $showApprovals = true;
                        break;
                    }
                }
                if (!$showApprovals) {
                    $showApprovals = !empty($wf->get_pending_for_user($currentUid));
                }
            } catch (\Throwable $e) { /* Non-fatal */ }
        }

        if ($showApprovals && $currentUid > 0) {
            $items[] = [
                'slug'        => 'member_job_approvals',
                'label'       => 'Genehmigungen',
                'icon'        => '✅',
                'url'         => '/member/jobs/approvals',
                'active'      => str_starts_with($uri, '/member/jobs/approvals'),
                'category'    => 'plugins',
                'parent_slug' => 'member_jobs',
            ];
        }

        if ($currentUid > 0) {
            $items[] = [
                'slug'        => 'member_job_applications',
                'label'       => 'Bewerbungen',
                'icon'        => '📬',
                'url'         => '/member/jobs/applications',
                'active'      => str_starts_with($uri, '/member/jobs/applications'),
                'category'    => 'plugins',
                'parent_slug' => 'member_jobs',
            ];

            // Bewerber-Dashboard: Eigene Bewerbungen (sichtbar für jeden mit Bewerbungen)
            try {
                $myAppCount = (int) $this->db->get_var(
                    "SELECT COUNT(*) FROM {$this->p}jpg_applications WHERE user_id = ?",
                    [$currentUid]
                );
            } catch (\Throwable $e) {
                $myAppCount = 0;
            }
            if ($myAppCount > 0) {
                $items[] = [
                    'slug'        => 'member_my_applications',
                    'label'       => 'Meine Bewerbungen',
                    'icon'        => '📋',
                    'url'         => '/member/jobs/my-applications',
                    'active'      => str_starts_with($uri, '/member/jobs/my-applications'),
                    'category'    => 'plugins',
                    'parent_slug' => 'member_jobs',
                    'badge'       => $myAppCount > 0 ? (string) $myAppCount : '',
                ];
            }
            $items[] = [
                'slug'        => 'member_job_settings',
                'label'       => 'Einstellungen',
                'icon'        => '⚙️',
                'url'         => '/member/jobs/settings',
                'active'      => str_starts_with($uri, '/member/jobs/settings'),
                'category'    => 'plugins',
                'parent_slug' => 'member_jobs',
            ];
        }

        if ($isAdmin) {
            $items[] = [
                'slug'        => 'member_job_libraries',
                'label'       => 'Bibliotheken',
                'icon'        => '📚',
                'url'         => '/member/plugin/member-job-libraries',
                'active'      => str_contains($uri, 'member-job-libraries'),
                'category'    => 'plugins',
                'parent_slug' => 'member_jobs',
            ];
            $items[] = [
                'slug'        => 'member_job_templates',
                'label'       => 'Vorlagen',
                'icon'        => '🎨',
                'url'         => '/member/plugin/member-job-templates',
                'active'      => str_contains($uri, 'member-job-templates'),
                'category'    => 'plugins',
                'parent_slug' => 'member_jobs',
            ];
        }

        // Stellenanzeigen → Übersicht (direkte Unterseite) - am Ende
        if ($currentUid > 0) {
            $items[] = [
                'slug'        => 'member_jobs_list',
                'label'       => 'Meine Stellen',
                'icon'        => '📋',
                'url'         => '/member/jobs',
                'active'      => str_starts_with($uri, '/member/jobs') &&
                                 !str_contains($uri, '/create') &&
                                 !str_contains($uri, '/edit') &&
                                 !str_contains($uri, '/approvals') &&
                                 !str_contains($uri, '/applications') &&
                                 !str_contains($uri, '/settings'),
                'category'    => 'plugins',
                'parent_slug' => 'member_jobs',
            ];
        }

        return $items;
    }

    /**
     * Korrigiert den `active`-Zustand der Plugin-Sidebar-Items bei Standalone-Routen.
     *
     * @param  array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public function fix_active_menu_states(array $items): array
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (!str_starts_with($uri, '/member/jobs')) {
            return $items;
        }

        $isApprovals       = str_starts_with($uri, '/member/jobs/approvals');
        $isApplications    = str_starts_with($uri, '/member/jobs/applications');
        $isMyApplications  = str_starts_with($uri, '/member/jobs/my-applications');
        $isSettings        = str_starts_with($uri, '/member/jobs/settings');
        $isCreate          = str_starts_with($uri, '/member/jobs/create');
        $isEdit            = str_starts_with($uri, '/member/jobs/edit');
        $isPdf             = str_starts_with($uri, '/member/jobs/pdf');
        $isDuplicate       = str_starts_with($uri, '/member/jobs/duplicate');
        $isWorkflow        = str_starts_with($uri, '/member/jobs/workflow');
        $isMainJobs        = !$isApprovals && !$isApplications && !$isMyApplications && !$isSettings && !$isCreate && !$isEdit
                          && !$isPdf && !$isDuplicate && !$isWorkflow;

        foreach ($items as &$item) {
            switch ($item['slug'] ?? '') {
                case 'plugin_member-jobs':
                case 'member_jobs':
                    // Parent gilt als aktiv wenn irgendeine Unterseite aktiv ist
                    $item['active'] = str_starts_with($uri, '/member/jobs');
                    break;
                case 'member_jobs_list':
                    $item['active'] = $isMainJobs;
                    break;
                case 'member_jobs_create':
                    $item['active'] = $isCreate || $isEdit;
                    break;
                case 'plugin_member-job-approvals':
                case 'member_job_approvals':
                    $item['active'] = $isApprovals;
                    break;
                case 'plugin_member-job-applications':
                case 'member_job_applications':
                    $item['active'] = $isApplications;
                    break;
                case 'plugin_member-my-applications':
                case 'member_my_applications':
                    $item['active'] = $isMyApplications;
                    break;
                case 'plugin_member-job-settings':
                case 'member_job_settings':
                    $item['active'] = $isSettings;
                    break;
            }
        }
        unset($item);

        return $items;
    }

    /**
     * Dashboard-Kachel: Anzahl aktiver Jobs des Users.
     *
     * @param  array<int, array<string, mixed>> $stats
     * @return array<int, array<string, mixed>>
     */
    public function add_dashboard_stat(array $stats): array
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
                "SELECT COUNT(*) FROM {$this->p}jpg_profiles
                 WHERE created_by = ? AND status = 'published'",
                [$uid]
            );
        } catch (\Throwable $e) {
            $count = 0;
        }

        $stats[] = [
            'label' => 'Aktive Jobs',
            'value' => $count,
            'icon'  => '📄',
            'url'   => '/member/jobs',
            'color' => '#3b82f6',
        ];
        return $stats;
    }

    /**
     * Dashboard-Widget: Letzte Bewerbungseingänge.
     *
     * @param  array<int, array<string, mixed>> $widgets
     * @return array<int, array<string, mixed>>
     */
    public function add_dashboard_widget(array $widgets): array
    {
        if (!$this->auth->isLoggedIn()) {
            return $widgets;
        }
        $uid = method_exists($this->auth, 'getUserId') ? (int) $this->auth->getUserId() : 0;
        if ($uid <= 0) {
            return $widgets;
        }

        try {
            $recent = $this->db->get_results(
                "SELECT a.id, a.applicant_name, a.status, a.created_at,
                        p.title AS job_title, p.id AS job_id
                 FROM {$this->p}jpg_applications a
                 INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                 WHERE p.created_by = ?
                 ORDER BY a.created_at DESC
                 LIMIT 5",
                [$uid]
            ) ?: [];
        } catch (\Throwable $e) {
            $recent = [];
        }

        ob_start();
        echo '<div style="font-size:.875rem;">';
        if (empty($recent)) {
            echo '<p style="color:#64748b;margin:0;">Noch keine Bewerbungen eingegangen.</p>';
        } else {
            echo '<table style="width:100%;border-collapse:collapse;">';
            echo '<thead><tr style="background:#f8fafc;">'
                . '<th style="padding:.5rem;text-align:left;font-size:.8rem;color:#475569;">Bewerber</th>'
                . '<th style="padding:.5rem;text-align:left;font-size:.8rem;color:#475569;">Stelle</th>'
                . '<th style="padding:.5rem;text-align:left;font-size:.8rem;color:#475569;">Status</th>'
                . '</tr></thead><tbody>';
            foreach ($recent as $row) {
                $statusColors = ['new' => '#dbeafe', 'reviewing' => '#fef3c7', 'rejected' => '#fee2e2', 'accepted' => '#d1fae5'];
                $statusLabels = ['new' => 'Neu', 'reviewing' => 'In Prüfung', 'rejected' => 'Abgelehnt', 'accepted' => 'Angenommen'];
                $bg    = $statusColors[$row->status] ?? '#f1f5f9';
                $label = $statusLabels[$row->status] ?? htmlspecialchars($row->status);
                echo '<tr style="border-bottom:1px solid #f1f5f9;">'
                    . '<td style="padding:.45rem .5rem;">' . htmlspecialchars($row->applicant_name) . '</td>'
                    . '<td style="padding:.45rem .5rem;"><a href="/member/jobs/applications?job_id=' . (int)$row->job_id . '" style="color:#3b82f6;">'
                    . htmlspecialchars($row->job_title) . '</a></td>'
                    . '<td style="padding:.45rem .5rem;"><span style="background:' . $bg . ';border-radius:4px;padding:.15rem .45rem;font-size:.78rem;">' . $label . '</span></td>'
                    . '</tr>';
            }
            echo '</tbody></table>';
            echo '<div style="text-align:right;margin-top:.5rem;">'
                . '<a href="/member/jobs/applications" style="font-size:.8rem;color:#3b82f6;">Alle anzeigen →</a></div>';
        }
        echo '</div>';
        $html = ob_get_clean();

        $widgets[] = [
            'title'    => '📬 Letzte Bewerbungseingänge',
            'html'     => $html,
            'priority' => 20,
        ];
        return $widgets;
    }

    /**
     * Fügt E-Mail-Toggle für Bewerbungsbenachrichtigungen ein.
     *
     * @param  array<int, array<string, mixed>> $sections
     * @return array<int, array<string, mixed>>
     */
    public function add_notification_section(array $sections): array
    {
        $uid  = method_exists($this->auth, 'getUserId') ? (int) $this->auth->getUserId() : 0;
        $pref = $uid > 0 ? $this->get_user_pref($uid, 'notify_applications', '1') : '1';

        $sections[] = [
            'title'  => '📄 Stellenanzeigen',
            'fields' => [
                [
                    'name'    => 'jpg_notify_applications',
                    'type'    => 'checkbox',
                    'label'   => 'E-Mail bei neuen Bewerbungen erhalten',
                    'checked' => $pref === '1',
                ],
            ],
        ];
        return $sections;
    }

    /**
     * Sichert Benachrichtigungs-Präferenz für Bewerbungen.
     *
     * @param  array<string, mixed> $preferences
     * @param  int                  $userId
     * @return array<string, mixed>
     */
    public function save_notification_preferences(array $preferences, int $userId): array
    {
        $preferences['jpg_notify_applications'] = isset($_POST['jpg_notify_applications']) ? '1' : '0';
        $this->set_user_pref($userId, 'notify_applications', $preferences['jpg_notify_applications']);
        return $preferences;
    }

    /**
     * Lädt Admin-CSS für Plugin-Sektionen im Member-Bereich.
     * Hook: member_plugin_section_head
     */
    public function enqueue_member_section_styles(string $slug, object $user): void
    {
        if (!str_starts_with($slug, 'member-job')) {
            return;
        }
        $siteUrl = defined('SITE_URL') ? SITE_URL : '';
        echo '<link rel="stylesheet" href="' . $siteUrl . '/assets/css/admin.css?v=20260222b">' . "\n";
        $cssFile = JPG_DIR . 'assets/css/jobprofile-admin.css';
        if (file_exists($cssFile)) {
            echo '<link rel="stylesheet" href="' . JPG_URL . 'assets/css/jobprofile-admin.css?v='
                . filemtime($cssFile) . '">' . "\n";
        }
    }
}
