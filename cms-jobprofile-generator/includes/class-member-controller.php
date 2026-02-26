<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Member-Bereich Controller – Job Profile Generator
 *
 * Klinkt sich via Hooks in den 365CMS Member-Bereich ein:
 * – Sidebar-Menüpunkt „Stellenanzeigen"
 * – Dashboard-Kachel „Aktive Jobs"
 * – Dashboard-Widget „Letzte Bewerbungseingänge"
 * – Routen /member/jobs/*
 * – Benachrichtigungs-Präferenzen
 *
 * Sicherheitsregeln (strikt):
 * – Auth-Guard in jeder öffentlichen Methode
 * – Data-Silo: jede SQL-Abfrage enthält WHERE user_id = current user
 * – CSRF-Token bei jedem Formular und AJAX-Request
 * – Input via sanitize_*() / getPost()
 * – Output via htmlspecialchars()
 *
 * @since   0.1.0
 * @package CMS_JobProfileGenerator
 */
class CMS_JPG_Member_Controller
{
    private static ?self $instance = null;

    /** @var \CMS\Auth */
    private \CMS\Auth $auth;

    /** @var \CMS\Database */
    private \CMS\Database $db;

    /** @var string */
    private string $p;

    /** @var int|null */
    private ?int $userId = null;

    public static function instance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->auth = \CMS\Auth::instance();
        $this->db   = \CMS\Database::instance();
        $this->p    = $this->db->getPrefix();

        $this->register_hooks();
        $this->register_routes();
    }

    // ── Hooks ────────────────────────────────────────────────────────────────

    private function register_hooks(): void
    {
        if (!class_exists('CMS\\Hooks')) {
            return;
        }

        // Hinweis: Menüpunkt, Dashboard-Kachel und Widget werden ausschließlich
        // über PluginDashboardRegistry registriert (member_dashboard_init weiter unten).
        // Die Legacy-Hooks member_menu_items / member_dashboard_stats / member_dashboard_widgets
        // sind deaktiviert, um doppelte Einträge zu vermeiden.

        // Benachrichtigungs-Einstellungen
        \CMS\Hooks::addFilter('member_notification_settings_sections', [$this, 'add_notification_section'], 10);
        \CMS\Hooks::addFilter('member_notification_preferences', [$this, 'save_notification_preferences'], 10, 2);

        // DSGVO: Datenexport (Art. 20) & Account-Löschung (Art. 17)
        \CMS\Hooks::addFilter('cms_member_data_export_requested', [$this, 'handle_data_export'], 10, 2);
        \CMS\Hooks::addAction('cms_member_account_deletion_requested', [$this, 'handle_account_deletion'], 10);

        // PluginDashboardRegistry – korrekte Einbindung ins Member-Layout
        \CMS\Hooks::addAction('member_dashboard_init', [$this, 'register_via_plugin_dashboard'], 10);

        // Aktiv-Zustand der Menüpunkte für /member/jobs/* Standalone-Routen korrigieren
        // (muss nach filterMenuItems Prio 20 laufen, daher Prio 30)
        \CMS\Hooks::addFilter('member_menu_items', [$this, 'fix_active_menu_states'], 30);

        // Admin-CSS für Plugin-Seiten im Member-Bereich bereitstellen
        // (plugin-section.php feuert diesen Hook im <head>)
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
        $router->addRoute('GET',  '/member/jobs/download/:token',      [$this, 'download_file']);
        $router->addRoute('GET',  '/member/jobs/settings',             [$this, 'render_settings']);
        $router->addRoute('POST', '/member/jobs/settings',             [$this, 'render_settings']);
        // Phase 14.2: 1-Click Duplizierer
        $router->addRoute('GET',  '/member/jobs/duplicate/:id',        [$this, 'render_duplicate']);
        // Phase 13.2: Bulk-Aktion (POST auf Listenseite)
        $router->addRoute('POST', '/member/jobs',                      [$this, 'render_list']);
    }

    // ── Auth-Guard ────────────────────────────────────────────────────────────

    private function require_auth(): void
    {
        if (!$this->auth->isLoggedIn()) {
            header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/member/jobs'));
            exit;
        }
        $this->userId = method_exists($this->auth, 'getUserId') ? (int) $this->auth->getUserId() : 0;
    }

    // ── CSRF-Helfer ───────────────────────────────────────────────────────────

    private function generate_token(string $action): string
    {
        if (class_exists('CMS\\Security')) {
            return \CMS\Security::instance()->generateToken('member_jpg_' . $action);
        }
        return bin2hex(random_bytes(16));
    }

    private function verify_token(string $action): bool
    {
        $token = $_POST['_jpg_csrf'] ?? '';
        if (class_exists('CMS\\Security')) {
            return \CMS\Security::instance()->verifyToken($token, 'member_jpg_' . $action);
        }
        return !empty($token); // Fallback
    }

    // ── Eingabe-Helfer ────────────────────────────────────────────────────────

    /**
     * @param  'text'|'html'|'int'|'url'|'email' $type
     * @return string|int
     */
    private function getPost(string $key, string $type = 'text'): string|int
    {
        $raw = $_POST[$key] ?? '';
        return match ($type) {
            'int'   => (int) $raw,
            'email' => (string) filter_var($raw, FILTER_VALIDATE_EMAIL),
            'url'   => (string) filter_var($raw, FILTER_VALIDATE_URL),
            'html'  => (string) $raw,  // SunEditor-Inhalte; sanitizeHtml() in Logik anwenden
            default => sanitize_text_field((string) $raw),
        };
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
                // Admins sehen alle Profile
                $published = (int) $this->db->get_var(
                    "SELECT COUNT(*) FROM {$this->p}jpg_profiles WHERE status = 'published'", []
                );
                $total = (int) $this->db->get_var(
                    "SELECT COUNT(*) FROM {$this->p}jpg_profiles WHERE status != 'trash'", []
                );
            } else {
                // Members: eigene ODER der eigenen Firma zugewiesene Profile
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

        // Genehmigungs-Bereich: Admins immer, Genehmiger wenn sie für Workflow-Schritte zugewiesen
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
                'slug'     => 'member_job_approvals',
                'label'    => 'Genehmigungen',
                'icon'     => '✅',
                'url'      => '/member/jobs/approvals',
                'active'   => str_starts_with($uri, '/member/jobs/approvals'),
                'category' => 'plugins',
            ];
        }

        // Bewerbungen – für alle eingeloggten Nutzer (mit eigenen Jobs)
        if ($currentUid > 0) {
            $items[] = [
                'slug'     => 'member_job_applications',
                'label'    => 'Bewerbungen',
                'icon'     => '📬',
                'url'      => '/member/jobs/applications',
                'active'   => str_starts_with($uri, '/member/jobs/applications'),
                'category' => 'plugins',
            ];
        }

        // Einstellungen (Firmenprofil) – für alle eingeloggten Nutzer
        if ($currentUid > 0) {
            $items[] = [
                'slug'     => 'member_job_settings',
                'label'    => 'Einstellungen',
                'icon'     => '⚙️',
                'url'      => '/member/jobs/settings',
                'active'   => str_starts_with($uri, '/member/jobs/settings'),
                'category' => 'plugins',
            ];
        }

        // Admin-exklusive Bereiche: Bibliotheken + Vorlagen (→ Inline-Modus)
        if ($isAdmin) {
            $items[] = [
                'slug'     => 'member_job_libraries',
                'label'    => 'Bibliotheken',
                'icon'     => '📚',
                'url'      => '/member/plugin/member-job-libraries',
                'active'   => str_contains($uri, 'member-job-libraries'),
                'category' => 'plugins',
            ];
            $items[] = [
                'slug'     => 'member_job_templates',
                'label'    => 'Vorlagen',
                'icon'     => '🎨',
                'url'      => '/member/plugin/member-job-templates',
                'active'   => str_contains($uri, 'member-job-templates'),
                'category' => 'plugins',
            ];
        }

        return $items;
    }

    /**
     * Korrigiert den `active`-Zustand der Plugin-Sidebar-Items wenn Standalone-Routen
     * (/member/jobs/*) genutzt werden statt der Registry-Routen (/member/plugin/*).
     *
     * Wird als `member_menu_items`-Filter bei Priorität 30 ausgeführt
     * (nach PluginDashboardRegistry::filterMenuItems Prio 20).
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

        $isApprovals    = str_starts_with($uri, '/member/jobs/approvals');
        $isApplications = str_starts_with($uri, '/member/jobs/applications');
        $isSettings     = str_starts_with($uri, '/member/jobs/settings');
        $isMainJobs     = !$isApprovals && !$isApplications && !$isSettings;

        foreach ($items as &$item) {
            switch ($item['slug'] ?? '') {
                case 'plugin_member-jobs':
                    $item['active'] = $isMainJobs;
                    break;
                case 'plugin_member-job-approvals':
                    $item['active'] = $isApprovals;
                    break;
                case 'plugin_member-job-applications':
                    $item['active'] = $isApplications;
                    break;
                case 'plugin_member-job-settings':
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
        $uid   = method_exists($this->auth, 'getUserId') ? (int) $this->auth->getUserId() : 0;
        $pref  = $uid > 0 ? $this->get_user_pref($uid, 'notify_applications', '1') : '1';

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

    // ── DSGVO-Hooks ───────────────────────────────────────────────────────────

    /**
     * DSGVO Art. 20 – Datenexport (Hook: cms_member_data_export_requested).
     *
     * Gibt alle Job-Profile und eingehenden Bewerbungen des Users als strukturiertes
     * Array zurück, das durch den CMS-Core in das ZIP-Exportarchiv aufgenommen wird.
     *
     * @param  array<string, mixed> $exportData Bisheriger Export-Datensatz aus anderen Hooks
     * @param  int                  $userId     ID des anfragenden Users
     * @return array<string, mixed> Angereicherter Export-Datensatz
     */
    public function handle_data_export(array $exportData, int $userId): array
    {
        try {
            $db = $this->db;
            $p  = $this->p;

            // Alle eigenen Job-Profile
            $profiles = $db->get_results(
                "SELECT id, title, slug, status, workflow_status, location,
                        employment_type, summary, created_at, updated_at
                 FROM {$p}jpg_profiles
                 WHERE created_by = ? AND status != 'trash'
                 ORDER BY created_at DESC",
                [$userId]
            ) ?: [];

            $exportProfiles = [];
            foreach ($profiles as $profile) {
                // Aufgaben & Anforderungen mitexportieren
                $tasks = $db->get_results(
                    "SELECT task_text, sort_order FROM {$p}jpg_profile_tasks
                     WHERE profile_id = ? ORDER BY sort_order",
                    [(int) $profile->id]
                ) ?: [];
                $requirements = $db->get_results(
                    "SELECT req_text, req_type FROM {$p}jpg_profile_requirements
                     WHERE profile_id = ? ORDER BY sort_order",
                    [(int) $profile->id]
                ) ?: [];

                // Anonymisierte Bewerbungen zu diesem Job
                $applications = $db->get_results(
                    "SELECT applicant_name, applicant_email, status, created_at
                     FROM {$p}jpg_applications WHERE job_id = ?
                     ORDER BY created_at DESC",
                    [(int) $profile->id]
                ) ?: [];

                $exportProfiles[] = [
                    'id'           => (int) $profile->id,
                    'title'        => $profile->title,
                    'slug'         => $profile->slug,
                    'status'       => $profile->status,
                    'location'     => $profile->location,
                    'summary'      => $profile->summary,
                    'created_at'   => $profile->created_at,
                    'updated_at'   => $profile->updated_at,
                    'tasks'        => array_map(fn($t): array => ['text' => $t->task_text], $tasks),
                    'requirements' => array_map(fn($r): array => ['text' => $r->req_text, 'type' => $r->req_type], $requirements),
                    'applications' => array_map(fn($a): array => [
                        'applicant_name'  => $a->applicant_name,
                        'applicant_email' => $a->applicant_email,
                        'status'          => $a->status,
                        'date'            => $a->created_at,
                    ], $applications),
                ];
            }

            $exportData['job_profiles'] = [
                '_info'     => 'Eigene Stellenanzeigen und eingegangene Bewerbungen',
                '_exported' => date('c'),
                'count'     => count($exportProfiles),
                'profiles'  => $exportProfiles,
            ];
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Member_Controller::handle_data_export() error: ' . $e->getMessage());
        }

        return $exportData;
    }

    /**
     * DSGVO Art. 17 – Account-Löschung (Hook: cms_member_account_deletion_requested).
     *
     * Setzt alle Job-Profile auf `trash`, anonymisiert Bewerbungsdaten und
     * löscht hochgeladene CV-Dateien physisch.
     *
     * @param int $userId ID des zu löschenden Users
     */
    public function handle_account_deletion(int $userId): void
    {
        try {
            $db = $this->db;
            $p  = $this->p;

            // 1. Alle Job-Profile auf trash setzen
            $db->execute(
                "UPDATE {$p}jpg_profiles SET status = 'trash', updated_at = NOW() WHERE created_by = ?",
                [$userId]
            );

            // 2. Profil-IDs ermitteln (für CV-Dateilöschung)
            $profileIds = $db->get_results(
                "SELECT id FROM {$p}jpg_profiles WHERE created_by = ?",
                [$userId]
            ) ?: [];

            foreach ($profileIds as $row) {
                $pid = (int) $row->id;

                // CV-Dateipfade laden
                $cvFiles = $db->get_results(
                    "SELECT cv_file_path FROM {$p}jpg_applications WHERE job_id = ? AND cv_file_path IS NOT NULL",
                    [$pid]
                ) ?: [];

                // 3. CV-Dateien physisch löschen
                foreach ($cvFiles as $cv) {
                    if (!empty($cv->cv_file_path) && file_exists($cv->cv_file_path)) {
                        @unlink($cv->cv_file_path);
                    }
                }

                // 4. Bewerbungsdaten anonymisieren (DSGVO-konform: keine Löschung, aber keine PII)
                $db->execute(
                    "UPDATE {$p}jpg_applications
                     SET applicant_name  = '[gelöscht]',
                         applicant_email = '[gelöscht]',
                         applicant_phone = NULL,
                         cover_letter    = '[Inhalt gemäß DSGVO gelöscht]',
                         cv_file_path    = NULL,
                         cv_file_token   = NULL,
                         updated_at      = NOW()
                     WHERE job_id = ?",
                    [$pid]
                );
            }

            error_log("CMS_JPG_Member_Controller: DSGVO-Löschung für User {$userId} abgeschlossen.");
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Member_Controller::handle_account_deletion() error: ' . $e->getMessage());
        }
    }

    // ── Routen-Handler ────────────────────────────────────────────────────────

    /**
     * GET /member/jobs – Meine Stellenanzeigen
     */
    public function render_list(): void
    {
        $this->require_auth();

        // Abo-Limit prüfen
        $canCreate = !function_exists('user_can_create_resource') || user_can_create_resource('job_profiles');

        // Phase 13.2: Bulk-Aktionen verarbeiten
        $bulkNotice = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
            if ($this->verify_token('list_action')) {
                $bulkAction = sanitize_key($_POST['bulk_action'] ?? '');
                $rawIds     = array_filter(array_map('intval', (array)($_POST['profile_ids'] ?? [])));
                if (!empty($rawIds)) {
                    $ph = implode(',', array_fill(0, count($rawIds), '?'));
                    try {
                        if ($bulkAction === 'delete') {
                            $this->db->query(
                                "DELETE FROM {$this->p}jpg_profiles WHERE id IN ({$ph}) AND created_by = ?",
                                [...$rawIds, $this->userId]
                            );
                            $bulkNotice = count($rawIds) . ' Stelle(n) gelöscht.';
                        } elseif ($bulkAction === 'archive') {
                            $this->db->query(
                                "UPDATE {$this->p}jpg_profiles SET status = 'archived' WHERE id IN ({$ph}) AND created_by = ?",
                                [...$rawIds, $this->userId]
                            );
                            $bulkNotice = count($rawIds) . ' Stelle(n) archiviert.';
                        }
                    } catch (\Throwable $e) { /* ignore */ }
                }
            }
        }

        try {
            $profiles = $this->db->get_results(
                "SELECT id, title, status, workflow_status, workflow_step,
                        location, employment_type, views, created_at, slug
                 FROM {$this->p}jpg_profiles
                 WHERE created_by = ?
                 ORDER BY created_at DESC",
                [$this->userId]
            ) ?: [];
        } catch (\Throwable $e) {
            $profiles = [];
        }

        // Phase 12.1: Bewerbungs-Zähler pro Stelle (letzte 30 Tage)
        $appCountMap = [];
        $appTrend7   = [];
        try {
            $rows = $this->db->get_results(
                "SELECT a.job_id, COUNT(*) AS cnt
                 FROM {$this->p}jpg_applications a
                 INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                 WHERE p.created_by = ? AND a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY a.job_id",
                [$this->userId]
            ) ?: [];
            foreach ($rows as $r) {
                $appCountMap[(int)$r->job_id] = (int)$r->cnt;
            }
            // Trend letzte 7 Tage
            $t7rows = $this->db->get_results(
                "SELECT DATE(a.created_at) AS d, COUNT(*) AS cnt
                 FROM {$this->p}jpg_applications a
                 INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                 WHERE p.created_by = ? AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY DATE(a.created_at) ORDER BY d ASC",
                [$this->userId]
            ) ?: [];
            foreach ($t7rows as $r) {
                $appTrend7[$r->d] = (int)$r->cnt;
            }
        } catch (\Throwable $e) { /* Analytics optional */ }

        // Phase 12.2: Abo-Kontingent
        $quotaUsed  = count($profiles);
        $quotaLimit = function_exists('get_user_resource_limit') ? (int)get_user_resource_limit('job_profiles') : -1;
        $totalViews = array_sum(array_column($profiles, 'views'));
        $totalApps  = array_sum($appCountMap);

        $csrf = $this->generate_token('list_action');
        $this->render_with_layout('views/member/page-jobs-list.php',
            compact('profiles', 'canCreate', 'csrf', 'bulkNotice',
                    'appCountMap', 'appTrend7', 'quotaUsed', 'quotaLimit', 'totalViews', 'totalApps'));
    }

    /**
     * GET+POST /member/jobs/create – Neues Profil erstellen
     */
    public function render_create(): void
    {
        $this->require_auth();

        // Feature-Gate: Limit prüfen
        if (function_exists('user_can_create_resource') && !user_can_create_resource('job_profiles')) {
            $this->render_limit_error();
            return;
        }

        $notice = '';
        $error  = '';
        $newId  = 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verify_token('create')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                [$notice, $error, $newId] = $this->save_profile_post(0);
                if ($newId > 0 && empty($error)) {
                    header('Location: /member/jobs/edit/' . $newId . '?created=1');
                    exit;
                }
            }
        }

        $categories    = [];
        $allBenefits   = [];
        $workflowSteps = [];
        $tasks         = [];
        $requirements  = [];
        $benefitIds    = [];
        try {
            $categories    = CMS_JPG_JobCategories::instance()->get_all();
            $allBenefits   = CMS_JPG_BenefitsCatalog::instance()->get_grouped();
            $workflowSteps = class_exists('CMS_JPG_Workflow')
                ? CMS_JPG_Workflow::instance()->get_steps()
                : [];
        } catch (\Throwable $e) {
            $categories = [];
        }

        $csrf = $this->generate_token('create');
        $this->render_with_layout('views/member/page-jobs-create.php',
            compact('categories', 'allBenefits', 'tasks', 'requirements', 'benefitIds', 'workflowSteps', 'csrf', 'notice', 'error'));
    }

    /**
     * GET+POST /member/jobs/edit/:id – Profil bearbeiten
     */
    public function render_edit(string $id): void
    {
        $this->require_auth();

        $profileId = (int) $id;
        $profile   = $this->load_own_profile($profileId);

        if (!$profile) {
            http_response_code(404);
            $this->render_with_layout('views/member/page-jobs-list.php', [
                'profiles'  => [],
                'canCreate' => true,
                'csrf'      => $this->generate_token('list_action'),
                'error'     => 'Profil nicht gefunden oder kein Zugriff.',
            ]);
            return;
        }

        $notice = !empty($_GET['created']) ? 'Profil angelegt – jetzt vervollständigen!' : '';
        $error  = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verify_token('edit')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                [$notice, $error] = $this->save_profile_post($profileId);
                $profile = $this->load_own_profile($profileId);
            }
        }

        $categories     = CMS_JPG_JobCategories::instance()->get_all();
        $allBenefits    = CMS_JPG_BenefitsCatalog::instance()->get_grouped();
        $tasks          = CMS_JPG_Profiles::instance()->get_tasks($profileId);
        $requirements   = CMS_JPG_Profiles::instance()->get_requirements($profileId);
        $benefitIds     = CMS_JPG_Profiles::instance()->get_benefit_ids($profileId);
        $workflowSteps  = class_exists('CMS_JPG_Workflow') ? CMS_JPG_Workflow::instance()->get_steps() : [];
        $workflowHistory= class_exists('CMS_JPG_Workflow') ? CMS_JPG_Workflow::instance()->get_history($profileId) : [];

        $csrf   = $this->generate_token('edit');
        $wfCsrf = $this->generate_token('workflow_submit');
        $this->render_with_layout('views/member/page-jobs-edit.php',
            compact('profile', 'categories', 'allBenefits', 'tasks', 'requirements',
                    'benefitIds', 'workflowSteps', 'workflowHistory', 'csrf', 'wfCsrf', 'notice', 'error'));
    }

    /**
     * GET /member/jobs/applications – Bewerbungs-Postfach
     */
    public function render_applications(): void
    {
        $this->require_auth();

        $jobId = (int) ($_GET['job_id'] ?? 0);

        try {
            // Nur Bewerbungen zu eigenen Jobs laden (data silo)
            $sql = "SELECT a.id, a.applicant_name, a.applicant_email, a.cover_letter,
                           a.cv_file_token, a.status, a.created_at,
                           p.title AS job_title, p.id AS job_id
                    FROM {$this->p}jpg_applications a
                    INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                    WHERE p.created_by = ?";
            $params = [$this->userId];

            if ($jobId > 0) {
                $sql    .= ' AND a.job_id = ?';
                $params[] = $jobId;
            }

            $sql .= ' ORDER BY a.created_at DESC';

            $applications = $this->db->get_results($sql, $params) ?: [];
        } catch (\Throwable $e) {
            $applications = [];
        }

        // Eigene Jobs für Filter-Dropdown
        try {
            $myJobs = $this->db->get_results(
                "SELECT id, title FROM {$this->p}jpg_profiles
                 WHERE created_by = ? AND status = 'published'
                 ORDER BY title ASC",
                [$this->userId]
            ) ?: [];
        } catch (\Throwable $e) {
            $myJobs = [];
        }

        $csrf = $this->generate_token('app_status');
        $this->render_with_layout('views/member/page-jobs-applications.php',
            compact('applications', 'myJobs', 'jobId', 'csrf'));
    }

    /**
     * POST /member/jobs/applications/status – AJAX Status-Update
     */
    public function ajax_update_status(): void
    {
        $this->require_auth();

        header('Content-Type: application/json');

        if (!$this->verify_token('app_status')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.']);
            exit;
        }

        $appId     = (int) ($_POST['application_id'] ?? 0);
        $newStatus = sanitize_key($_POST['status'] ?? '');

        if ($appId <= 0 || !in_array($newStatus, ['new', 'reviewing', 'rejected', 'accepted'], true)) {
            echo json_encode(['success' => false, 'error' => 'Ungültige Parameter.']);
            exit;
        }

        // Data-Silo: prüfen ob die Bewerbung zu einem eigenen Job gehört
        try {
            $ownerCheck = $this->db->get_var(
                "SELECT a.id FROM {$this->p}jpg_applications a
                 INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                 WHERE a.id = ? AND p.created_by = ?",
                [$appId, $this->userId]
            );
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => 'Datenbankfehler.']);
            exit;
        }

        if (!$ownerCheck) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Kein Zugriff.']);
            exit;
        }

        try {
            $this->db->update(
                'jpg_applications',
                ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')],
                ['id' => $appId]
            );

            // Phase 13.1: Status-Mailer – Bewerber informieren bei Annahme/Ablehnung
            if (in_array($newStatus, ['accepted', 'rejected'], true)) {
                try {
                    $app = $this->db->get_row(
                        "SELECT a.applicant_name, a.applicant_email,
                                p.title AS job_title, u.email AS owner_email
                         FROM {$this->p}jpg_applications a
                         INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                         LEFT JOIN {$this->p}users u ON u.id = p.created_by
                         WHERE a.id = ?",
                        [$appId]
                    );
                    if ($app && filter_var($app->applicant_email ?? '', FILTER_VALIDATE_EMAIL)) {
                        $statusLabel = $newStatus === 'accepted' ? 'angenommen' : 'abgelehnt';
                        $fromEmail   = filter_var($app->owner_email ?? '', FILTER_VALIDATE_EMAIL)
                            ? $app->owner_email
                            : 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                        $subject = 'Update zu Ihrer Bewerbung: ' . ($app->job_title ?? '');
                        $body    = "Sehr geehrte(r) " . ($app->applicant_name ?? 'Bewerber(in)') . ",\r\n\r\n"
                                 . "wir möchten Sie über den aktuellen Stand Ihrer Bewerbung informieren.\r\n\r\n"
                                 . "Stelle: " . ($app->job_title ?? '') . "\r\n"
                                 . "Status: Ihre Bewerbung wurde " . $statusLabel . ".\r\n\r\n"
                                 . "Mit freundlichen Grüßen";
                        $headers = "From: " . $fromEmail . "\r\nContent-Type: text/plain; charset=UTF-8";
                        @mail($app->applicant_email, $subject, $body, $headers);
                    }
                } catch (\Throwable $e) { /* Mailer ist nicht kritisch */ }
            }

            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * GET|POST /member/jobs/approvals – Genehmigungsbereich (standalone)
     */
    public function render_approvals(): void
    {
        $this->require_auth();
        $user = method_exists($this->auth, 'currentUser') ? $this->auth->currentUser() : null;
        if ($user) {
            $this->userId = (int) $user->id;
        }
        $isAdmin = method_exists($this->auth, 'isAdmin') ? $this->auth->isAdmin() : false;
        $notice  = '';
        $error   = '';

        // Zugriff: Admins oder Workflow-Genehmiger
        if (!$isAdmin && class_exists('CMS_JPG_Workflow')) {
            $hasAccess = false;
            try {
                $wf       = CMS_JPG_Workflow::instance();
                $userRole = $user ? (string)($user->role ?? '') : '';
                foreach ($wf->get_steps() as $step) {
                    if (!empty($step->approver_role) && $step->approver_role === $userRole) {
                        $hasAccess = true;
                        break;
                    }
                }
                if (!$hasAccess) {
                    $hasAccess = !empty($wf->get_pending_for_user($this->userId));
                }
            } catch (\Throwable $e) { /* ignore */ }
            if (!$hasAccess) {
                http_response_code(403);
                echo '<p style="color:#ef4444;">Kein Zugriff auf den Genehmigungsbereich.</p>';
                return;
            }
        }

        // POST: Genehmigen / Ablehnen / Zurücksetzen
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->verify_token('member_approvals')) {
            $action    = sanitize_key($_POST['approval_action'] ?? '');
            $profileId = (int) ($_POST['profile_id'] ?? 0);
            $note      = trim(strip_tags($_POST['note'] ?? ''));
            if ($profileId > 0 && class_exists('CMS_JPG_Workflow')) {
                $wf = CMS_JPG_Workflow::instance();
                try {
                    match ($action) {
                        'approve' => ($wf->can_approve($this->userId, $profileId)
                            ? $wf->approve_step($profileId, $this->userId, $note)
                            : throw new \RuntimeException('Kein Genehmiger-Zugriff.')),
                        'reject'  => ($wf->can_approve($this->userId, $profileId)
                            ? $wf->reject_step($profileId, $this->userId, $note)
                            : throw new \RuntimeException('Kein Genehmiger-Zugriff.')),
                        'reset'   => ($isAdmin
                            ? $wf->reset_workflow($profileId)
                            : throw new \RuntimeException('Nur Admins können zurücksetzen.')),
                        default   => throw new \RuntimeException('Unbekannte Aktion.'),
                    };
                    $notice = match ($action) {
                        'approve' => 'Schritt genehmigt.',
                        'reject'  => 'Stellenanzeige abgelehnt.',
                        'reset'   => 'Workflow zurückgesetzt.',
                        default   => 'Aktion ausgeführt.',
                    };
                } catch (\Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }

        if (class_exists('CMS_JPG_Workflow')) {
            $wf = CMS_JPG_Workflow::instance();
            $pendingProfiles = $isAdmin
                ? $wf->get_all_pending()
                : $wf->get_pending_for_user($this->userId);
            $wfSteps = $wf->get_steps();
        } else {
            $pendingProfiles = [];
            $wfSteps         = [];
        }

        // Owner-Sicht: eigene Profile mit Workflow-Status
        try {
            $ownerProfiles = $this->db->get_results(
                "SELECT p.id, p.title, p.status, p.workflow_status, p.workflow_step,
                        p.created_at,
                        c.name AS company_name
                 FROM {$this->p}jpg_profiles p
                 LEFT JOIN {$this->p}companies c ON c.id = p.company_id
                 WHERE (
                     p.created_by = ?
                     OR p.company_id IN (SELECT id FROM {$this->p}companies WHERE user_id = ?)
                 )
                 AND p.workflow_status != 'none'
                 ORDER BY p.created_at DESC LIMIT 50",
                [$this->userId, $this->userId]
            ) ?: [];
        } catch (\Throwable $e) {
            $ownerProfiles = [];
        }

        $csrf    = $this->generate_token('member_approvals');
        $baseUrl = '/member/jobs';
        $this->render_with_layout('views/member/page-approvals.php', compact(
            'pendingProfiles', 'wfSteps', 'ownerProfiles', 'csrf', 'notice', 'error', 'baseUrl'
        ));
    }

    /**
     * GET /member/jobs/download/:token – Sicherer CV-Download
     *
     * Gibt eine Bewerber-Datei zurück, nachdem geprüft wurde,
     * dass der eingeloggte User der Besitzer des zugehörigen Jobs ist.
     */
    public function download_file(string $token): void
    {
        $this->require_auth();

        $token = preg_replace('/[^a-f0-9]/', '', strtolower($token));
        if (strlen($token) < 32) {
            http_response_code(400);
            exit;
        }

        try {
            $application = $this->db->get_row(
                "SELECT a.cv_file_path, a.applicant_name, p.created_by
                 FROM {$this->p}jpg_applications a
                 INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                 WHERE a.cv_file_token = ?",
                [$token]
            );
        } catch (\Throwable $e) {
            http_response_code(500);
            exit;
        }

        // Data-Silo: nur eigene Jobs
        if (!$application || (int) $application->created_by !== $this->userId) {
            http_response_code(403);
            echo 'Kein Zugriff.';
            exit;
        }

        $filePath = $application->cv_file_path;

        // Sicherstellen, dass der Pfad nicht außerhalb uploads/ liegt
        $uploadsBase = defined('UPLOADS_PATH') ? UPLOADS_PATH : (ABSPATH . 'uploads/');
        $realFile    = realpath($uploadsBase . ltrim($filePath, '/'));
        $realBase    = realpath($uploadsBase);

        if (!$realFile || !str_starts_with($realFile, (string) $realBase) || !is_file($realFile)) {
            http_response_code(404);
            exit;
        }

        $mime = mime_content_type($realFile) ?: 'application/octet-stream';
        // Nur PDF und gängige Dokument-Typen erlauben
        if (!in_array($mime, ['application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], true)) {
            http_response_code(403);
            exit;
        }

        $ext      = pathinfo($realFile, PATHINFO_EXTENSION);
        $filename = 'CV-' . preg_replace('/[^a-z0-9\-_]/i', '_', $application->applicant_name) . '.' . $ext;

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($realFile));
        header('X-Content-Type-Options: nosniff');
        readfile($realFile);
        exit;
    }

    // ── PluginDashboardRegistry ───────────────────────────────────────────────

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
        // Admin-Design-Tokens (CSS-Variablen) und Komponenten-Styles
        echo '<link rel="stylesheet" href="' . $siteUrl . '/assets/css/admin.css?v=20260222b">' . "\n";
        // Plugin-spezifische Admin-Styles (Tab-Wizard, Bibliotheken etc.)
        $cssFile = JPG_DIR . 'assets/css/jobprofile-admin.css';
        if (file_exists($cssFile)) {
            echo '<link rel="stylesheet" href="' . JPG_URL . 'assets/css/jobprofile-admin.css?v='
                . filemtime($cssFile) . '">' . "\n";
        }
    }

    /**
     * Registriert den Job-Bereich im Member-Dashboard via PluginDashboardRegistry.
     * Callback für `member_dashboard_init`.
     *
     * @param \CMS\Member\PluginDashboardRegistry $registry
     */
    public function register_via_plugin_dashboard(object $registry): void
    {
        if (!method_exists($registry, 'register')) {
            return;
        }

        $controller = $this;
        $registry->register([
            'plugin'    => 'cms-jobprofile-generator',
            'slug'      => 'member-jobs',
            'label'     => 'Stellenanzeigen',
            'icon'      => '📄',
            'category'  => 'plugins',
            'priority'  => 20,
            'capability'=> null,
            'dashboard_widget' => [
                'title'       => 'Stellenanzeigen',
                'icon'        => '📄',
                'description' => 'Erstelle, verwalte und veröffentliche deine Job-Profile – inklusive Genehmigungs-Workflow.',
                'color'       => '#3b82f6',
                'stats_callback' => [$this, 'get_dashboard_stats'],
            ],
            'render_callback' => function(object $user, array $params) use ($controller): void {
                // Sub-Routing: bevorzugt $params (URL-Segmente wie /edit/1), Fallback auf GET-Parameter
                $action     = sanitize_key($params['action'] ?? $_GET['action'] ?? 'list');
                $resourceId = (int) ($params['id'] ?? $_GET['id'] ?? 0);

                match ($action) {
                    'create'       => $controller->render_create_inline($user),
                    'edit'         => $controller->render_edit_inline((string) $resourceId, $user),
                    'applications' => $controller->render_applications_inline($user),
                    default        => $controller->render_list_inline($user),
                };
            },
        ]);

        // Gemeinsame Variablen für bedingte Einträge
        $currentUid = method_exists($this->auth, 'getUserId') ? (int) $this->auth->getUserId() : 0;
        $isAdmin    = method_exists($this->auth, 'isAdmin')   ? $this->auth->isAdmin() : false;

        // Bewerbungen: für alle eingeloggten Nutzer sichtbar
        if ($currentUid > 0) {
            $registry->register([
                'plugin'      => 'cms-jobprofile-generator',
                'slug'        => 'member-job-applications',
                'label'       => 'Bewerbungen',
                'icon'        => '📬',
                'category'    => 'plugins',
                'priority'    => 21,
                'capability'  => null,
                'parent_slug' => 'plugin_member-jobs',
                'render_callback' => function(object $user, array $params) use ($controller): void {
                    $controller->render_applications_inline($user);
                },
            ]);
        }

        // Genehmigungen: Admins immer; Genehmiger sobald ihre Rolle einem Workflow-Schritt zugewiesen ist
        $showApprovals = $isAdmin;
        if (!$showApprovals && $currentUid > 0 && class_exists('CMS_JPG_Workflow')) {
            try {
                $wf       = CMS_JPG_Workflow::instance();
                $authUser = method_exists($this->auth, 'currentUser') ? $this->auth->currentUser() : null;
                $userRole = $authUser ? (string)($authUser->role ?? '') : '';
                // Prüfen, ob die Benutzerrolle als Genehmiger in einem aktiven Schritt definiert ist
                foreach ($wf->get_steps() as $step) {
                    if (!empty($step->approver_role) && $step->approver_role === $userRole) {
                        $showApprovals = true;
                        break;
                    }
                }
                // Fallback: ausstehende Einträge prüfen
                if (!$showApprovals) {
                    $showApprovals = !empty($wf->get_pending_for_user($currentUid));
                }
            } catch (\Throwable $e) { /* Workflow-Tabelle ggf. nicht vorhanden */ }
        }
        if ($currentUid > 0 && $showApprovals) {
            $registry->register([
                'plugin'      => 'cms-jobprofile-generator',
                'slug'        => 'member-job-approvals',
                'label'       => 'Genehmigungen',
                'icon'        => '✅',
                'category'    => 'plugins',
                'priority'    => 22,
                'capability'  => null,
                'parent_slug' => 'plugin_member-jobs',
                'render_callback' => function(object $user, array $params) use ($controller): void {
                    $controller->render_approvals_inline($user);
                },
            ]);
        }

        // Bibliotheken & Vorlagen: nur für Admins im Member-Dashboard
        if ($isAdmin) {
            $registry->register([
                'plugin'      => 'cms-jobprofile-generator',
                'slug'        => 'member-job-libraries',
                'label'       => 'Bibliotheken',
                'icon'        => '📚',
                'category'    => 'plugins',
                'priority'    => 22,
                'capability'  => null,
                'parent_slug' => 'plugin_member-jobs',
                'render_callback' => function(object $user, array $params) use ($controller): void {
                    $controller->render_libraries_inline($user);
                },
            ]);
            $registry->register([
                'plugin'      => 'cms-jobprofile-generator',
                'slug'        => 'member-job-templates',
                'label'       => 'Vorlagen',
                'icon'        => '🎨',
                'category'    => 'plugins',
                'priority'    => 23,
                'capability'  => null,
                'parent_slug' => 'plugin_member-jobs',
                'render_callback' => function(object $user, array $params) use ($controller): void {
                    $controller->render_templates_inline($user);
                },
            ]);
        }

        // Einstellungen: für alle eingeloggten Mitglieder sichtbar (Firmenprofil verwalten)
        if ($currentUid > 0) {
            $registry->register([
                'plugin'      => 'cms-jobprofile-generator',
                'slug'        => 'member-job-settings',
                'label'       => 'Einstellungen',
                'icon'        => '⚙️',
                'category'    => 'plugins',
                'priority'    => 29,
                'capability'  => null,
                'parent_slug' => 'plugin_member-jobs',
                'render_callback' => function(object $user, array $params) use ($controller): void {
                    $controller->render_settings_inline($user);
                },
            ]);
        }

        // Unternehmens-Übersicht: Firmenprofil direkt sichtbar (eigener Menüpunkt)
        if ($currentUid > 0) {
            $registry->register([
                'plugin'      => 'cms-jobprofile-generator',
                'slug'        => 'member-job-company',
                'label'       => 'Unternehmens-Übersicht',
                'icon'        => '🏢',
                'category'    => 'plugins',
                'priority'    => 25,
                'capability'  => null,
                'parent_slug' => 'plugin_member-jobs',
                'render_callback' => function(object $user, array $params) use ($controller): void {
                    $controller->render_company_inline($user);
                },
            ]);
        }

        // Workflow-Status: Mandanten sehen den Status ihrer Stellen im Workflow
        if ($currentUid > 0) {
            $registry->register([
                'plugin'      => 'cms-jobprofile-generator',
                'slug'        => 'member-job-workflow',
                'label'       => 'Workflow-Status',
                'icon'        => '🔄',
                'category'    => 'plugins',
                'priority'    => 26,
                'capability'  => null,
                'parent_slug' => 'plugin_member-jobs',
                'render_callback' => function(object $user, array $params) use ($controller): void {
                    $controller->render_workflow_status_inline($user);
                },
            ]);
        }
    }

    /**
     * Inline-Render-Methoden für PluginDashboardRegistry
     * (Content ohne eigenes Layout – Registry-Wrapper liefert dieses bereits).
     */
    public function render_list_inline(object $user): void
    {
        $this->userId = (int) $user->id;
        $isAdmin      = method_exists($this->auth, 'isAdmin') ? $this->auth->isAdmin() : false;
        $canCreate    = !function_exists('user_can_create_resource') || user_can_create_resource('job_profiles');
        try {
            if ($isAdmin) {
                // Admins sehen alle Profile im System
                $profiles = $this->db->get_results(
                    "SELECT p.id, p.title, p.status, p.workflow_status, p.workflow_step,
                            p.location, p.employment_type, p.views, p.created_at, p.slug,
                            c.name AS company_name
                     FROM {$this->p}jpg_profiles p
                     LEFT JOIN {$this->p}companies c ON c.id = p.company_id
                     ORDER BY p.created_at DESC LIMIT 100",
                    []
                ) ?: [];
            } else {
                // Members: eigene ODER der eigenen Firma zugewiesene Profile
                $profiles = $this->db->get_results(
                    "SELECT p.id, p.title, p.status, p.workflow_status, p.workflow_step,
                            p.location, p.employment_type, p.views, p.created_at, p.slug,
                            c.name AS company_name
                     FROM {$this->p}jpg_profiles p
                     LEFT JOIN {$this->p}companies c ON c.id = p.company_id
                     WHERE (
                         p.created_by = ?
                         OR p.company_id IN (SELECT id FROM {$this->p}companies WHERE user_id = ?)
                     )
                     ORDER BY p.created_at DESC",
                    [$this->userId, $this->userId]
                ) ?: [];
            }
        } catch (\Throwable $e) {
            $profiles = [];
        }
        // Phase 12.1: Bewerbungs-Zähler (letzte 30 Tage)
        $appCountMap = [];
        $appTrend7   = [];
        try {
            $uid   = $isAdmin ? 0 : $this->userId;
            $rows  = $isAdmin
                ? ($this->db->get_results(
                    "SELECT a.job_id, COUNT(*) AS cnt FROM {$this->p}jpg_applications a
                     WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY a.job_id", []
                  ) ?: [])
                : ($this->db->get_results(
                    "SELECT a.job_id, COUNT(*) AS cnt FROM {$this->p}jpg_applications a
                     INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                     WHERE p.created_by = ? AND a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                     GROUP BY a.job_id", [$uid]
                  ) ?: []);
            foreach ($rows as $r) {
                $appCountMap[(int)$r->job_id] = (int)$r->cnt;
            }
            $t7q = $isAdmin
                ? "SELECT DATE(a.created_at) AS d, COUNT(*) AS cnt FROM {$this->p}jpg_applications a
                   WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                   GROUP BY DATE(a.created_at) ORDER BY d ASC"
                : "SELECT DATE(a.created_at) AS d, COUNT(*) AS cnt FROM {$this->p}jpg_applications a
                   INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                   WHERE p.created_by = ? AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                   GROUP BY DATE(a.created_at) ORDER BY d ASC";
            $t7p    = $isAdmin ? [] : [$uid];
            $t7rows = $this->db->get_results($t7q, $t7p) ?: [];
            foreach ($t7rows as $r) {
                $appTrend7[$r->d] = (int)$r->cnt;
            }
        } catch (\Throwable $e) { /* Analytics optional */ }

        $quotaUsed  = count($profiles);
        $quotaLimit = function_exists('get_user_resource_limit') ? (int)get_user_resource_limit('job_profiles') : -1;
        $totalViews = array_sum(array_column($profiles, 'views'));
        $totalApps  = array_sum($appCountMap);
        $bulkNotice = '';

        $csrf      = $this->generate_token('list_action');
        $baseUrl   = '/member/plugin/member-jobs';
        // Inline-Registry nutzt GET-Parameter für Aktionen, nicht Pfad-Segmente
        $createUrl = '/member/plugin/member-jobs?action=create';
        include JPG_DIR . 'views/member/page-jobs-list.php';
    }

    public function render_create_inline(object $user): void
    {
        $this->userId = (int) $user->id;
        $notice = '';
        $error  = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->verify_token('create')) {
            [$notice, $error, $newId] = $this->save_profile_post(0);
            if ($newId > 0 && empty($error)) {
                header('Location: /member/plugin/member-jobs?action=edit&id=' . $newId . '&created=1');
                exit;
            }
        }
        $categories    = CMS_JPG_JobCategories::instance()->get_all() ?: [];
        $allBenefits   = CMS_JPG_BenefitsCatalog::instance()->get_grouped();
        $tasks         = [];
        $requirements  = [];
        $benefitIds    = [];
        $workflowSteps = class_exists('CMS_JPG_Workflow') ? CMS_JPG_Workflow::instance()->get_steps() : [];
        $csrf          = $this->generate_token('create');
        $baseUrl       = '/member/plugin/member-jobs';
        include JPG_DIR . 'views/member/page-jobs-create.php';
    }

    public function render_edit_inline(string $id, object $user): void
    {
        $this->userId  = (int) $user->id;
        $profileId     = (int) $id;
        $profile       = $this->load_own_profile($profileId);
        $notice        = !empty($_GET['created']) ? 'Profil angelegt – jetzt vervollständigen!' : '';
        $error         = '';
        if (!$profile) {
            echo '<div class="alert alert-error">❌ Profil nicht gefunden.</div>';
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->verify_token('edit')) {
            [$notice, $error] = $this->save_profile_post($profileId);
            $profile = $this->load_own_profile($profileId);
        }
        $categories     = CMS_JPG_JobCategories::instance()->get_all();
        $allBenefits    = CMS_JPG_BenefitsCatalog::instance()->get_grouped();
        $tasks          = CMS_JPG_Profiles::instance()->get_tasks($profileId);
        $requirements   = CMS_JPG_Profiles::instance()->get_requirements($profileId);
        $benefitIds     = CMS_JPG_Profiles::instance()->get_benefit_ids($profileId);
        $workflowSteps  = class_exists('CMS_JPG_Workflow') ? CMS_JPG_Workflow::instance()->get_steps() : [];
        $workflowHistory= class_exists('CMS_JPG_Workflow') ? CMS_JPG_Workflow::instance()->get_history($profileId) : [];
        $csrf           = $this->generate_token('edit');
        $wfCsrf         = $this->generate_token('workflow_submit');
        $baseUrl        = '/member/plugin/member-jobs';
        include JPG_DIR . 'views/member/page-jobs-edit.php';
    }

    public function render_approvals_inline(object $user): void
    {
        $this->userId = (int) $user->id;
        $isAdmin      = method_exists($this->auth, 'isAdmin') ? $this->auth->isAdmin() : false;
        $notice       = '';
        $error        = '';

        // POST: Genehmigen / Ablehnen / Zurücksetzen
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->verify_token('member_approvals')) {
            $action    = sanitize_key($_POST['approval_action'] ?? '');
            $profileId = (int) ($_POST['profile_id'] ?? 0);
            $note      = trim(strip_tags($_POST['note'] ?? ''));
            if ($profileId > 0 && class_exists('CMS_JPG_Workflow')) {
                $wf = CMS_JPG_Workflow::instance();
                try {
                    match ($action) {
                        'approve' => ($wf->can_approve($this->userId, $profileId)
                            ? $wf->approve_step($profileId, $this->userId, $note)
                            : throw new \RuntimeException('Kein Genehmiger-Zugriff.')),
                        'reject'  => ($wf->can_approve($this->userId, $profileId)
                            ? $wf->reject_step($profileId, $this->userId, $note)
                            : throw new \RuntimeException('Kein Genehmiger-Zugriff.')),
                        'reset'   => ($isAdmin
                            ? $wf->reset_workflow($profileId)
                            : throw new \RuntimeException('Nur Admins können zurücksetzen.')),
                        default   => throw new \RuntimeException('Unbekannte Aktion.'),
                    };
                    $notice = match ($action) {
                        'approve' => 'Schritt genehmigt.',
                        'reject'  => 'Stellenanzeige abgelehnt.',
                        'reset'   => 'Workflow zurückgesetzt.',
                        default   => 'Aktion ausgeführt.',
                    };
                } catch (\Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }

        if (class_exists('CMS_JPG_Workflow')) {
            $wf = CMS_JPG_Workflow::instance();
            // Admins sehen alle ausstehenden Profile, Genehmiger nur ihre eigenen
            $pendingProfiles = $isAdmin
                ? $wf->get_all_pending()
                : $wf->get_pending_for_user($this->userId);
            $wfSteps = $wf->get_steps();
        } else {
            $pendingProfiles = [];
            $wfSteps         = [];
        }

        // Owner-Sicht: eigene Profile mit Workflow-Status (nicht-none)
        try {
            $ownerProfiles = $this->db->get_results(
                "SELECT p.id, p.title, p.status, p.workflow_status, p.workflow_step,
                        p.created_at,
                        c.name AS company_name
                 FROM {$this->p}jpg_profiles p
                 LEFT JOIN {$this->p}companies c ON c.id = p.company_id
                 WHERE (
                     p.created_by = ?
                     OR p.company_id IN (SELECT id FROM {$this->p}companies WHERE user_id = ?)
                 )
                 AND p.workflow_status != 'none'
                 ORDER BY p.created_at DESC LIMIT 50",
                [$this->userId, $this->userId]
            ) ?: [];
        } catch (\Throwable $e) {
            $ownerProfiles = [];
        }

        $csrf    = $this->generate_token('member_approvals');
        $baseUrl = '/member/plugin/member-jobs';
        include JPG_DIR . 'views/member/page-approvals.php';
    }

    public function render_libraries_inline(object $user): void
    {
        if (!method_exists($this->auth, 'isAdmin') || !$this->auth->isAdmin()) {
            echo '<p style="color:#ef4444;">Kein Zugriff.</p>';
            return;
        }
        $tab  = sanitize_key($_GET['tab'] ?? 'benefits');
        $tabs = [
            'benefits'   => '🌟 Benefits',
            'skills'     => '🧠 Skills',
            'categories' => '📂 Kategorien',
            'text'       => '📝 Textbausteine',
        ];
        $notice = '';
        $error  = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->verify_token('member_libraries')) {
            $action = sanitize_key($_POST['lib_action'] ?? '');
            try {
                switch ($action) {
                    case 'save_benefit':
                        CMS_JPG_BenefitsCatalog::instance()->save([
                            'group_name' => sanitize_text_field($_POST['group_name'] ?? ''),
                            'title'      => sanitize_text_field($_POST['title'] ?? ''),
                            'icon'       => sanitize_text_field($_POST['icon'] ?? ''),
                            'active'     => 1,
                        ], (int)($_POST['benefit_id'] ?? 0));
                        $notice = 'Benefit gespeichert.';
                        break;
                    case 'delete_benefit':
                        CMS_JPG_BenefitsCatalog::instance()->delete((int)($_POST['benefit_id'] ?? 0));
                        $notice = 'Benefit gelöscht.';
                        break;
                    case 'save_skill':
                        CMS_JPG_SkillMatrix::instance()->save([
                            'group_name' => sanitize_text_field($_POST['group_name'] ?? ''),
                            'skill_name' => sanitize_text_field($_POST['skill_name'] ?? ''),
                        ], (int)($_POST['skill_id'] ?? 0));
                        $notice = 'Skill gespeichert.';
                        break;
                    case 'delete_skill':
                        CMS_JPG_SkillMatrix::instance()->delete((int)($_POST['skill_id'] ?? 0));
                        $notice = 'Skill gelöscht.';
                        break;
                    case 'save_category':
                        CMS_JPG_JobCategories::instance()->save([
                            'name'      => sanitize_text_field($_POST['name'] ?? ''),
                            'slug'      => sanitize_key($_POST['slug'] ?? ''),
                            'is_active' => 1,
                        ], (int)($_POST['category_id'] ?? 0));
                        $notice = 'Kategorie gespeichert.';
                        break;
                    case 'delete_category':
                        CMS_JPG_JobCategories::instance()->delete((int)($_POST['category_id'] ?? 0));
                        $notice = 'Kategorie gelöscht.';
                        break;
                    case 'save_text_module':
                        CMS_JPG_TextModules::instance()->save([
                            'category' => sanitize_text_field($_POST['category'] ?? ''),
                            'title'    => sanitize_text_field($_POST['title'] ?? ''),
                            'content'  => strip_tags($_POST['content'] ?? '', '<p><br><strong><em><ul><ol><li>'),
                        ], (int)($_POST['module_id'] ?? 0));
                        $notice = 'Textbaustein gespeichert.';
                        break;
                    case 'delete_text_module':
                        CMS_JPG_TextModules::instance()->delete((int)($_POST['module_id'] ?? 0));
                        $notice = 'Textbaustein gelöscht.';
                        break;
                }
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $benefits   = CMS_JPG_BenefitsCatalog::instance()->get_all();
        $bGroups    = CMS_JPG_BenefitsCatalog::instance()->get_groups();
        $skills     = CMS_JPG_SkillMatrix::instance()->get_all();
        $sGroups    = CMS_JPG_SkillMatrix::instance()->get_groups();
        $categories = CMS_JPG_JobCategories::instance()->get_all();
        $textItems  = CMS_JPG_TextModules::instance()->get_list();
        $textCats   = CMS_JPG_TextModules::instance()->get_categories();
        $csrf       = $this->generate_token('member_libraries');
        include JPG_DIR . 'views/member/page-libraries.php';
    }

    public function render_templates_inline(object $user): void
    {
        if (!method_exists($this->auth, 'isAdmin') || !$this->auth->isAdmin()) {
            echo '<p style="color:#ef4444;">Kein Zugriff.</p>';
            return;
        }
        $tab   = sanitize_key($_GET['tab'] ?? 'pdf-templates');
        $tabs  = [
            'pdf-templates'   => '📄 PDF-Templates',
            'web-templates'   => '🌐 Web-Templates',
            'email-templates' => '📧 E-Mail-Templates',
        ];
        $typeMap   = ['pdf-templates' => 'pdf', 'web-templates' => 'web', 'email-templates' => 'email'];
        $type      = $typeMap[$tab] ?? null;
        $notice    = '';
        $error     = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->verify_token('member_templates')) {
            $action = sanitize_key($_POST['tpl_action'] ?? '');
            $tplId  = (int)($_POST['template_id'] ?? 0);
            try {
                if ($action === 'delete' && $tplId > 0) {
                    $this->db->delete('jpg_templates', ['id' => $tplId]);
                    $notice = 'Vorlage gelöscht.';
                } elseif (in_array($action, ['save', 'update'], true)) {
                    $tplType = $typeMap[sanitize_key($_POST['template_type'] ?? '')] ?? 'pdf';
                    $fields  = [
                        'name'       => sanitize_text_field($_POST['template_name'] ?? ''),
                        'type'       => $tplType,
                        'is_default' => isset($_POST['is_default']) ? 1 : 0,
                        'content'    => sanitize_textarea_field($_POST['template_content'] ?? ''),
                    ];
                    if (empty($fields['name'])) {
                        throw new \RuntimeException('Name ist erforderlich.');
                    }
                    if ($tplId > 0) {
                        $this->db->update('jpg_templates', $fields, ['id' => $tplId]);
                        $notice = 'Vorlage aktualisiert.';
                    } else {
                        $this->db->insert('jpg_templates', $fields);
                        $notice = 'Vorlage erstellt.';
                    }
                }
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        try {
            $templates = $type
                ? ($this->db->get_results(
                    "SELECT * FROM {$this->p}jpg_templates WHERE type = ? ORDER BY name ASC",
                    [$type]
                ) ?: [])
                : [];
        } catch (\Throwable $e) {
            $templates = [];
        }
        $csrf = $this->generate_token('member_templates');
        include JPG_DIR . 'views/member/page-templates.php';
    }

    /**
     * Route-Handler: GET/POST /member/jobs/settings (standalone, falls direkt aufgerufen)
     */
    public function render_settings(): void
    {
        $this->require_auth();
        $user = method_exists($this->auth, 'currentUser')
            ? $this->auth->currentUser()
            : (object)['id' => $this->userId];

        // Inhalt puffern, dann mit vollem Member-Layout ausgeben
        ob_start();
        $this->render_settings_inline($user, standaloneRoute: true);
        $pageContent = ob_get_clean();

        $this->output_settings_with_layout($pageContent);
    }

    /**
     * Gibt gepufferten Settings-Inhalt im vollständigen Member-Layout aus.
     * Standalone-Variante (direkte Route).
     */
    private function output_settings_with_layout(string $pageContent): void
    {
        if (!function_exists('renderMemberSidebar') || !function_exists('renderMemberSidebarStyles')) {
            $partialFile = ABSPATH . 'member/partials/member-menu.php';
            if (file_exists($partialFile)) {
                require_once $partialFile;
            }
        }

        $siteUrl  = defined('SITE_URL')  ? SITE_URL  : '';
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'CMS';

        echo '<!DOCTYPE html><html lang="de"><head>' . "\n";
        echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">' . "\n";
        echo '<title>Einstellungen – ' . htmlspecialchars($siteName) . '</title>' . "\n";
        echo '<link rel="stylesheet" href="' . $siteUrl . '/assets/css/main.css">' . "\n";
        echo '<link rel="stylesheet" href="' . $siteUrl . '/assets/css/member.css">' . "\n";
        if (function_exists('renderMemberSidebarStyles')) {
            renderMemberSidebarStyles();
        }
        echo '</head><body class="member-body">' . "\n";
        if (function_exists('renderMemberSidebar')) {
            renderMemberSidebar('member-job-settings');
        }
        echo '<div class="member-content">';

        // Page header
        echo '<div class="member-page-header">'
            . '<h2>⚙️ Firmen-Einstellungen</h2>'
            . '<p>Unternehmensprofile und Standard-Benefits verwalten.</p>'
            . '</div>';

        echo $pageContent;
        echo '</div></body></html>' . "\n";
    }

    /**
     * Inline-Render: Firmeneinstellungen für das Member-Dashboard
     * Unterstützt 2 Tabs: info (Basisdaten) | benefits (Standard-Benefits)
     *
     * @param bool $standaloneRoute true wenn direkt über /member/jobs/settings aufgerufen
     */
    public function render_settings_inline(object $user, bool $standaloneRoute = false): void
    {
        $this->userId = (int) $user->id;
        $notice  = '';
        $error   = '';
        $company = null;

        // Aktiver Tab (info | benefits | team | departments | jobs-page)
        $activeTab = in_array($_GET['tab'] ?? '', ['info', 'benefits', 'team', 'departments', 'jobs-page'], true)
            ? ($_GET['tab'])
            : 'info';

        // Aktuelle Firma des Mitglieds laden (cms-companies)
        if (class_exists('CMS\PluginManager')
            && in_array('cms-companies', \CMS\PluginManager::instance()->getActivePlugins(), true)) {
            try {
                $db      = \CMS\Database::instance();
                $p       = $db->getPrefix();
                $company = $db->get_row(
                    "SELECT id, name, email, phone, industry, company_size, description,
                            logo_url, website, location_city, location_zip, location_country,
                            founded_year, employee_count
                     FROM {$p}companies WHERE user_id = ? LIMIT 1",
                    [$this->userId]
                );
            } catch (\Throwable $e) {
                $company = null;
            }
        }

        // ── POST: Tab-spezifische Verarbeitung ─────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settingsTab = sanitize_key($_POST['settings_tab'] ?? 'info');

            if ($settingsTab === 'departments') {
                // Abteilungs-Tab speichern
                if (!$this->verify_token('member_company_departments')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } elseif ($company === null) {
                    $error = 'Kein Firmenprofil gefunden.';
                } elseif (!class_exists('CMS_JPG_Departments')) {
                    $error = 'Abteilungs-Modul nicht verfügbar.';
                } else {
                    $companyId  = (int) $company->id;
                    $deptSvc    = CMS_JPG_Departments::instance();
                    $deptAction = sanitize_key($_POST['dept_action'] ?? 'save_department');

                    switch ($deptAction) {
                        case 'delete_department':
                            $deptId = (int) ($_POST['department_id'] ?? 0);
                            if ($deptId > 0) {
                                $dept = $deptSvc->get($deptId);
                                if ($dept && (int) $dept->company_id === $companyId) {
                                    $deptSvc->delete($deptId);
                                    $notice = 'Abteilung gelöscht.';
                                } else { $error = 'Abteilung nicht gefunden.'; }
                            }
                            break;
                        case 'save_dept_benefits':
                            $deptId = (int) ($_POST['department_id'] ?? 0);
                            $dept   = $deptSvc->get($deptId);
                            if ($dept && (int) $dept->company_id === $companyId) {
                                $ids = array_filter(array_map('intval', (array) ($_POST['dept_benefit_ids'] ?? [])));
                                $deptSvc->save_benefits($deptId, $ids);
                                $notice = 'Abteilungs-Benefits gespeichert.';
                            } else { $error = 'Abteilung nicht gefunden.'; }
                            break;
                        case 'save_dept_requirements':
                            $deptId = (int) ($_POST['department_id'] ?? 0);
                            $dept   = $deptSvc->get($deptId);
                            if ($dept && (int) $dept->company_id === $companyId) {
                                $ids = array_filter(array_map('intval', (array) ($_POST['dept_req_ids'] ?? [])));
                                $deptSvc->save_requirements($deptId, $ids);
                                $notice = 'Abteilungs-Anforderungen gespeichert.';
                            } else { $error = 'Abteilung nicht gefunden.'; }
                            break;
                        default: // save_department
                            $deptName = sanitize_text_field($_POST['dept_name'] ?? '');
                            if (empty($deptName)) { $error = 'Name ist erforderlich.'; break; }
                            $deptSvc->save([
                                'company_id'  => $companyId,
                                'name'        => $deptName,
                                'description' => sanitize_text_field($_POST['dept_description'] ?? ''),
                                'sort_order'  => (int) ($_POST['dept_sort_order'] ?? 0),
                                'created_by'  => $this->userId,
                            ]);
                            $notice = 'Abteilung angelegt.';
                    }
                    $activeTab = 'departments';
                }
            } elseif ($settingsTab === 'team') {
                // Team-Genehmiger-Tab speichern (Phase 9)
                if (!$this->verify_token('member_company_team')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } elseif ($company === null) {
                    $error = 'Kein Firmenprofil gefunden.';
                } else {
                    $companyId     = (int) $company->id;
                    $teamAction    = sanitize_key($_POST['team_action'] ?? '');
                    $approverUserId = (int) ($_POST['approver_user_id'] ?? 0);
                    if (class_exists('CMS_JPG_Workflow') && $approverUserId > 0) {
                        $wf = CMS_JPG_Workflow::instance();
                        if ($teamAction === 'remove') {
                            $wf->remove_team_approver($companyId, $approverUserId, $this->userId);
                            $notice    = 'Genehmiger entfernt.';
                        } elseif ($teamAction === 'add') {
                            $ok = $wf->add_team_approver($companyId, $approverUserId, $this->userId);
                            $notice = $ok ? 'Genehmiger hinzugefügt.' : 'Genehmiger konnte nicht hinzugefügt werden (bereits vorhanden oder kein Zugriff).';
                        }
                    }
                    $activeTab = 'team';
                }
            } elseif ($settingsTab === 'benefits') {
                // Benefits-Tab speichern
                if (!$this->verify_token('member_company_benefits')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } elseif ($company === null) {
                    $error = 'Kein Firmenprofil gefunden.';
                } else {
                    $companyId = (int) $company->id;
                    $rawIds    = $_POST['benefit_ids'] ?? [];
                    $ids       = is_array($rawIds) ? array_filter(array_map('intval', $rawIds)) : [];
                    try {
                        $db  = \CMS\Database::instance();
                        $p   = $db->getPrefix();
                        $pdo = $db->getPdo();
                        // Nur eigene Firma darf angepasst werden (user_id-Prüfung)
                        $pdo->prepare(
                            "DELETE b FROM {$p}jpg_company_default_benefits b
                             INNER JOIN {$p}companies c ON c.id = b.company_id
                             WHERE b.company_id = ? AND c.user_id = ?"
                        )->execute([$companyId, $this->userId]);
                        foreach ($ids as $benefitId) {
                            if ($benefitId > 0) {
                                $pdo->prepare(
                                    "INSERT IGNORE INTO {$p}jpg_company_default_benefits
                                     (company_id, benefit_id) VALUES (?, ?)"
                                )->execute([$companyId, $benefitId]);
                            }
                        }
                        $notice    = 'Standard-Benefits wurden gespeichert.';
                        $activeTab = 'benefits';
                    } catch (\Throwable $e) {
                        $error = 'Fehler beim Speichern der Benefits: ' . $e->getMessage();
                    }
                }
            } elseif ($settingsTab === 'jobs-page') {
                // Jobs-Seite Tab speichern
                if (!$this->verify_token('member_company_departments')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } elseif ($company === null) {
                    $error = 'Kein Firmenprofil gefunden.';
                } elseif (!class_exists('CMS_JPG_Departments')) {
                    $error = 'Abteilungs-Modul nicht verfügbar.';
                } else {
                    CMS_JPG_Departments::instance()->save_company_settings((int) $company->id, [
                        'jobs_page_url'           => $_POST['jobs_page_url']           ?? '',
                        'jobs_page_title'         => $_POST['jobs_page_title']         ?? '',
                        'jobs_page_intro'         => $_POST['jobs_page_intro']         ?? '',
                        'jobs_page_contact_email' => $_POST['jobs_page_contact_email'] ?? '',
                        'jobs_page_show_salary'   => isset($_POST['jobs_page_show_salary'])  ? 1 : 0,
                        'jobs_page_enabled'       => isset($_POST['jobs_page_enabled'])      ? 1 : 0,
                    ]);
                    $notice    = 'Jobs-Seite Einstellungen gespeichert.';
                    $activeTab = 'jobs-page';
                }
            } else {
                // Info-Tab speichern
                if (!$this->verify_token('member_company_settings')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } elseif ($company === null) {
                    $error = 'Kein Firmenprofil gefunden. Bitte kontaktiere den Administrator.';
                } else {
                    $companyId     = (int) $company->id;
                    $logoUrl       = filter_var(trim($_POST['company_logo_url']    ?? ''), FILTER_VALIDATE_URL) ?: '';
                    $name          = sanitize_text_field($_POST['company_name']    ?? '');
                    $website       = filter_var(trim($_POST['company_website']     ?? ''), FILTER_VALIDATE_URL) ?: '';
                    $email         = sanitize_text_field($_POST['company_email']   ?? '');
                    $phone         = sanitize_text_field($_POST['company_phone']   ?? '');
                    $industry      = sanitize_text_field($_POST['company_industry']    ?? '');
                    $companySize   = sanitize_text_field($_POST['company_size']        ?? '');
                    $description   = strip_tags($_POST['company_description'] ?? '');
                    $city          = sanitize_text_field($_POST['company_city']        ?? '');
                    $zip           = sanitize_text_field($_POST['company_zip']         ?? '');
                    $country       = sanitize_text_field($_POST['company_country']     ?? '');
                    $foundedYear   = (int) ($_POST['company_founded_year']             ?? 0);
                    $employeeCount = (int) ($_POST['company_employee_count']           ?? 0);

                    if (empty($name)) {
                        $error = 'Unternehmensname darf nicht leer sein.';
                    } else {
                        try {
                            $db   = \CMS\Database::instance();
                            $p    = $db->getPrefix();
                            $pdo  = $db->getPdo();
                            $pdo->prepare(
                                "UPDATE {$p}companies
                                 SET name = ?, email = ?, phone = ?, website = ?, logo_url = ?,
                                     industry = ?, company_size = ?, description = ?,
                                     location_city = ?, location_zip = ?, location_country = ?,
                                     founded_year = NULLIF(?, 0), employee_count = NULLIF(?, 0)
                                 WHERE id = ? AND user_id = ?"
                            )->execute([
                                $name, $email, $phone, $website, $logoUrl,
                                $industry, $companySize, $description,
                                $city, $zip, $country,
                                $foundedYear, $employeeCount,
                                $companyId, $this->userId,
                            ]);
                            $notice = 'Firmendaten wurden gespeichert.';
                            // Aktualisiertes Objekt nachladen
                            $company = $db->get_row(
                                "SELECT id, name, email, phone, industry, company_size, description,
                                        logo_url, website, location_city, location_zip, location_country,
                                        founded_year, employee_count
                                 FROM {$p}companies WHERE id = ? AND user_id = ? LIMIT 1",
                                [$companyId, $this->userId]
                            );
                        } catch (\Throwable $e) {
                            $error = 'Fehler beim Speichern: ' . $e->getMessage();
                        }
                    }
                }
            }
        }

        // ── Benefit-Katalog & Zuweisungen ─────────────────────────────────
        $allBenefits        = [];
        $assignedBenefitIds = [];
        try {
            if (class_exists('CMS_JPG_BenefitsCatalog')) {
                $allBenefits = CMS_JPG_BenefitsCatalog::instance()->get_grouped();
            }
            if ($company !== null) {
                $db   = \CMS\Database::instance();
                $p    = $db->getPrefix();
                $rows = $db->get_results(
                    "SELECT b.benefit_id FROM {$p}jpg_company_default_benefits b
                     INNER JOIN {$p}companies c ON c.id = b.company_id
                     WHERE b.company_id = ? AND c.user_id = ?",
                    [(int) $company->id, $this->userId]
                ) ?: [];
                $assignedBenefitIds = array_map(fn($r) => (int) $r->benefit_id, $rows);
            }
        } catch (\Throwable $e) {
            // Non-fatal: Benefits nicht verfügbar
        }

        // ── Job-Statistiken zur eigenen Firma ─────────────────────────────
        $companyJobStats = ['published' => 0, 'total' => 0, 'draft' => 0, 'archived' => 0];
        if ($company !== null) {
            try {
                $row = $this->db->get_row(
                    "SELECT
                         COUNT(*) AS total,
                         SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published,
                         SUM(CASE WHEN status = 'draft'     THEN 1 ELSE 0 END) AS draft,
                         SUM(CASE WHEN status = 'archived'  THEN 1 ELSE 0 END) AS archived
                     FROM {$this->p}jpg_profiles
                     WHERE company_id = ? AND created_by = ? AND status != 'trash'",
                    [(int) $company->id, $this->userId]
                );
                if ($row) {
                    $companyJobStats = [
                        'published' => (int) ($row->published ?? 0),
                        'total'     => (int) ($row->total ?? 0),
                        'draft'     => (int) ($row->draft ?? 0),
                        'archived'  => (int) ($row->archived ?? 0),
                    ];
                }
            } catch (\Throwable $e) { /* Non-fatal */ }
        }

        // ── Team-Genehmiger (Phase 9) ──────────────────────────────────────
        $teamApprovers = [];
        if ($company !== null && class_exists('CMS_JPG_Workflow')) {
            try {
                $teamApprovers = CMS_JPG_Workflow::instance()->get_team_approvers((int) $company->id);
            } catch (\Throwable $e) { /* Non-fatal */ }
        }

        // Benutzer der selben Organisation für das Hinzufügen-Dropdown laden (alle CMS-User)
        $availableUsers = [];
        try {
            $db  = \CMS\Database::instance();
            $approverIds = array_map(fn($ta) => (int) $ta->approver_user_id, $teamApprovers);
            $approverIds[] = $this->userId; // sich selbst ausschließen
            $placeholders  = implode(',', array_fill(0, count($approverIds), '?'));
            $availableUsers = $db->get_results(
                "SELECT id, username, firstname, lastname, email
                 FROM {$db->getPrefix()}users
                 WHERE id NOT IN ({$placeholders}) AND status = 'active'
                 ORDER BY firstname ASC, username ASC
                 LIMIT 100",
                $approverIds
            ) ?: [];
        } catch (\Throwable $e) { /* Non-fatal */ }

        $csrfInfo         = $this->generate_token('member_company_settings');
        $csrfBenefits     = $this->generate_token('member_company_benefits');
        $csrfTeam         = $this->generate_token('member_company_team'); // Phase 9
        $csrfDepartments  = $this->generate_token('member_company_departments');
        $csrf             = $csrfInfo; // legacy alias

        // ── Abteilungen + Firmen-Einstellungen ────────────────────────────────
        $departments       = [];
        $allDeptBenefitIds = [];   // [dept_id => [benefit_id, ...]]
        $allDeptReqIds     = [];   // [dept_id => [req_item_id, ...]]
        $companySettings   = null;
        $allReqItems       = [];
        if ($company !== null && class_exists('CMS_JPG_Departments')) {
            try {
                $deptSvc         = CMS_JPG_Departments::instance();
                $departments     = $deptSvc->get_all((int) $company->id);
                $companySettings = $deptSvc->get_company_settings((int) $company->id);
                foreach ($departments as $dept) {
                    $did = (int) $dept->id;
                    $allDeptBenefitIds[$did] = $deptSvc->get_benefit_ids($did);
                    $allDeptReqIds[$did]     = $deptSvc->get_requirement_ids($did);
                }
            } catch (\Throwable $e) { /* Non-fatal */ }
        }
        if (class_exists('CMS_JPG_RequirementItems')) {
            try { $allReqItems = CMS_JPG_RequirementItems::instance()->get_grouped(); }
            catch (\Throwable $e) { /* Non-fatal */ }
        }

        // Inline (Registry): baseUrl → /member/plugin/member-jobs (Tab-URL: /member/plugin/member-job-settings)
        // Standalone (Route): baseUrl → /member/jobs (Tab-URL: /member/jobs/settings)
        $baseUrl      = $standaloneRoute ? '/member/jobs' : '/member/plugin/member-jobs';
        include JPG_DIR . 'views/member/page-company-settings.php';
    }

    /**
     * Unternehmens-Übersicht für Mandanten im Member-Bereich.
     */
    public function render_company_inline(object $user): void
    {
        $this->userId = (int) $user->id;
        $company      = null;
        $profiles     = [];
        $totalApps    = 0;
        $notice       = '';
        $error        = '';
        $esc          = fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);

        try {
            $p       = $this->p;
            $db      = $this->db;
            $company = $db->get_row(
                "SELECT * FROM {$p}companies WHERE user_id = ? LIMIT 1",
                [$this->userId]
            );
            if ($company) {
                $profiles = $db->get_results(
                    "SELECT id, title, status, workflow_status, views, slug
                     FROM {$p}jpg_profiles
                     WHERE company_id = ? ORDER BY created_at DESC LIMIT 50",
                    [(int)$company->id]
                ) ?: [];
                $totalApps = (int) $db->get_var(
                    "SELECT COUNT(*) FROM {$p}jpg_applications a
                     INNER JOIN {$p}jpg_profiles p ON p.id = a.job_id
                     WHERE p.company_id = ?",
                    [(int)$company->id]
                );
            }
        } catch (\Throwable $e) {
            $company = null;
        }

        include JPG_DIR . 'views/member/page-company-overview-inline.php';
    }

    /**
     * Workflow-Status aller eigenen Stellen im Member-Bereich.
     */
    public function render_workflow_status_inline(object $user): void
    {
        $this->userId = (int) $user->id;
        $isAdmin      = method_exists($this->auth, 'isAdmin') ? $this->auth->isAdmin() : false;
        $profiles     = [];
        $notice       = '';
        $error        = '';

        // POST: Workflow-Aktion (einreichen)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->verify_token('workflow_submit_member')) {
            $pId      = (int) ($_POST['profile_id'] ?? 0);
            $wfAction = sanitize_key($_POST['workflow_action'] ?? '');
            if ($pId > 0 && $wfAction === 'submit' && class_exists('CMS_JPG_Workflow')) {
                try {
                    $profile = $this->load_own_profile($pId);
                    if ($profile) {
                        CMS_JPG_Workflow::instance()->submit_for_approval($pId, $this->userId);
                        $notice = 'Stellenanzeige zur Genehmigung eingereicht.';
                    } else {
                        $error = 'Profil nicht gefunden.';
                    }
                } catch (\Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }

        try {
            $profiles = $this->db->get_results(
                "SELECT id, title, status, workflow_status, workflow_step, views
                 FROM {$this->p}jpg_profiles
                 WHERE created_by = ?
                 ORDER BY created_at DESC",
                [$this->userId]
            ) ?: [];
        } catch (\Throwable $e) {
            $profiles = [];
        }

        $csrf      = $this->generate_token('workflow_submit_member');
        $baseUrl   = '/member/plugin/member-jobs';
        include JPG_DIR . 'views/member/page-workflow-status-inline.php';
    }

    public function render_applications_inline(object $user): void
    {
        try {
            $sql    = "SELECT a.id, a.applicant_name, a.applicant_email, a.cover_letter,
                              a.cv_file_token, a.status, a.created_at,
                              p.title AS job_title, p.id AS job_id
                       FROM {$this->p}jpg_applications a
                       INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                       WHERE p.created_by = ?";
            $params = [$this->userId];
            if ($jobId > 0) { $sql .= ' AND a.job_id = ?'; $params[] = $jobId; }
            $sql .= ' ORDER BY a.created_at DESC';
            $applications = $this->db->get_results($sql, $params) ?: [];
            $myJobs = $this->db->get_results(
                "SELECT id, title FROM {$this->p}jpg_profiles
                 WHERE created_by = ? AND status = 'published' ORDER BY title ASC",
                [$this->userId]
            ) ?: [];
        } catch (\Throwable $e) {
            $applications = [];
            $myJobs       = [];
        }
        $csrf    = $this->generate_token('app_status');
        $baseUrl = '/member/plugin/member-jobs';
        include JPG_DIR . 'views/member/page-jobs-applications.php';
    }

    // ── Layout-Wrapper ────────────────────────────────────────────────────────

    /**
     * Rendert eine View eingebettet in das vollständige Member-Layout.
     * Wird für direkte Routen (/member/jobs/*) genutzt.
     *
     * @param string              $viewFile Relativer Pfad zur View (ab JPG_DIR)
     * @param array<string,mixed> $vars     Variablen für die View
     */
    private function render_with_layout(string $viewFile, array $vars = []): void
    {
        // Member-Sidebar-Partial laden falls nötig
        if (!function_exists('renderMemberSidebar') || !function_exists('renderMemberSidebarStyles')) {
            $partialFile = ABSPATH . 'member/partials/member-menu.php';
            if (file_exists($partialFile)) {
                require_once $partialFile;
            }
        }

        // View-Variablen verfügbar machen
        extract($vars, EXTR_SKIP);
        $baseUrl = '/member/jobs';

        // View-Inhalt puffern
        ob_start();
        include JPG_DIR . $viewFile;
        $pageContent = ob_get_clean();

        $siteUrl  = defined('SITE_URL')  ? SITE_URL  : '';
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'CMS';

        // Flash-Nachrichten
        $flashSuccess = $_SESSION['success'] ?? null;
        $flashError   = $_SESSION['error']   ?? null;
        unset($_SESSION['success'], $_SESSION['error']);

        echo '<!DOCTYPE html><html lang="de"><head>' . "\n";
        echo '<meta charset="UTF-8">' . "\n";
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        echo '<title>Stellenanzeigen – ' . htmlspecialchars($siteName) . '</title>' . "\n";
        echo '<link rel="stylesheet" href="' . $siteUrl . '/assets/css/main.css">' . "\n";
        echo '<link rel="stylesheet" href="' . $siteUrl . '/assets/css/member.css">' . "\n";
        if (function_exists('renderMemberSidebarStyles')) {
            renderMemberSidebarStyles();
        }
        echo '</head>' . "\n";
        echo '<body class="member-body">' . "\n";

        // PluginDashboardRegistry initialisieren damit alle Plugin-Bereiche
        // in der Sidebar erscheinen (wird für Standalone-Routen /member/jobs/* benötigt,
        // da hier kein CMS-MemberController geht, der init() sonst aufrufen würde).
        if (class_exists('CMS\\Member\\PluginDashboardRegistry')) {
            \CMS\Member\PluginDashboardRegistry::instance()->init();
        }

        if (function_exists('renderMemberSidebar')) {
            // Slug wird für Legacy-Menüpunkte noch genutzt; Plugin-Items
            // werden via PluginDashboardRegistry und fix_active_menu_states korrekt markiert.
            renderMemberSidebar('member-jobs');
        }

        echo '<div class="member-content">' . "\n";

        if ($flashSuccess) {
            echo '<div class="member-alert member-alert-success" style="margin-bottom:1.25rem;">'
                . '<span class="alert-icon">✓</span>'
                . '<span>' . htmlspecialchars($flashSuccess) . '</span></div>' . "\n";
        }
        if ($flashError) {
            echo '<div class="member-alert member-alert-error" style="margin-bottom:1.25rem;">'
                . '<span class="alert-icon">✕</span>'
                . '<span>' . htmlspecialchars($flashError) . '</span></div>' . "\n";
        }

        echo $pageContent;
        echo '</div>' . "\n"; // /.member-content
        echo '</body></html>' . "\n";
    }

    // ── Workflow-Handler ──────────────────────────────────────────────────────

    /**
     * POST /member/jobs/workflow/submit/:id – Profil zur Genehmigung einreichen
     */
    public function handle_workflow_submit(string $id): void
    {
        $this->require_auth();

        $profileId = (int) $id;

        if (!$this->verify_token('workflow_submit')) {
            $_SESSION['error'] = 'Sicherheitscheck fehlgeschlagen.';
            header('Location: /member/jobs/edit/' . $profileId);
            exit;
        }

        if (!class_exists('CMS_JPG_Workflow')) {
            $_SESSION['error'] = 'Workflow nicht verfügbar.';
            header('Location: /member/jobs/edit/' . $profileId);
            exit;
        }

        $ok = CMS_JPG_Workflow::instance()->submit_for_approval($profileId, $this->userId);
        if ($ok) {
            $_SESSION['success'] = '✅ Stellenanzeige wurde zur Genehmigung eingereicht.';
        } else {
            $_SESSION['error'] = '❌ Einreichen fehlgeschlagen. Bitte prüfe das Profil und versuche es erneut.';
        }

        header('Location: /member/jobs/edit/' . $profileId);
        exit;
    }

    // ── Profil-Speichern ─────────────────────────────────────────────────────    /** @return array{string, string, int} [notice, error, id] */
    private function save_profile_post(int $id): array
    {
        $notice = '';
        $error  = '';

        $title = $this->getPost('title');
        if (empty($title)) {
            return [$notice, 'Stellentitel ist erforderlich.', $id];
        }

        // Phase 14.2: Slug – aus Eingabe oder Title generieren
        $slugInput = sanitize_text_field($_POST['slug'] ?? '');
        $autoSlug  = $this->generate_unique_slug($slugInput ?: $title, $id);

        $data = [
            'title'           => $title,
            'slug'            => $autoSlug,
            'job_category_id' => $this->getPost('job_category_id', 'int'),
            'status'          => 'draft',  // Member erstellt immer als Entwurf
            'summary'         => $this->getPost('summary'),
            'location'        => $this->getPost('location'),
            'employment_type' => $this->getPost('employment_type'),
            'salary_min'      => $this->getPost('salary_min'),
            'salary_max'      => $this->getPost('salary_max'),
            'remote_option'   => $this->getPost('remote_option'),
            'show_in_listing' => isset($_POST['show_in_listing']) ? 1 : 0, // Sichtbar auf /jobs-Listing
            'created_by'      => $this->userId,
            'updated_by'      => $this->userId,
        ];

        // Beschreibung (SunEditor-HTML) sanitieren
        if (class_exists('CMS\\Security') && method_exists(\CMS\Security::instance(), 'sanitizeHtml')) {
            $data['description'] = \CMS\Security::instance()->sanitizeHtml($_POST['description'] ?? '');
        } else {
            // sanitizeHtml() nicht vorhanden → strip_tags mit erweiterter Erlaubnisliste
            $data['description'] = strip_tags(
                $_POST['description'] ?? '',
                '<p><br><strong><em><b><i><u><ul><ol><li><a><h1><h2><h3><h4><blockquote><img><table><tr><td><th><thead><tbody><tfoot><span><div>'
            );
        }

        try {
            // Race-Condition-Schutz: Limit nochmals DB-seitig prüfen wenn neues Profil erstellt wird
            if ($id === 0 && function_exists('user_can_create_resource')
                && !user_can_create_resource('job_profiles')) {
                return [$notice, 'Ihr Profil-Limit ist erreicht. Bitte upgraden Sie Ihren Plan.', 0];
            }
            $newId = CMS_JPG_Profiles::instance()->save($data, $id);
        } catch (\Throwable $e) {
            return [$notice, 'Speichern fehlgeschlagen: ' . $e->getMessage(), $id];
        }

        if ($newId <= 0) {
            return [$notice, 'Profil konnte nicht gespeichert werden.', $id];
        }

        // Tasks
        $rawTasks = $_POST['tasks'] ?? [];
        if (is_array($rawTasks) && !empty(array_filter($rawTasks))) {
            $tasks = array_filter(
                array_map(fn($t) => sanitize_text_field((string) $t), $rawTasks),
                fn($t) => $t !== ''
            );
            CMS_JPG_Profiles::instance()->save_tasks($newId, array_values($tasks));
        }

        // Anforderungen (req_text[] / req_type[])
        $reqTexts = $_POST['req_text'] ?? [];
        $reqTypes = $_POST['req_type'] ?? [];
        if (is_array($reqTexts)) {
            $reqs = [];
            foreach ($reqTexts as $i => $txt) {
                $txt = sanitize_text_field((string) $txt);
                if ($txt !== '') {
                    $reqs[] = [
                        'text' => $txt,
                        'type' => in_array($reqTypes[$i] ?? '', ['must', 'nice', 'optional'], true)
                                  ? $reqTypes[$i] : 'must',
                    ];
                }
            }
            CMS_JPG_Profiles::instance()->save_requirements($newId, $reqs);
        }

        // Benefits (benefit_ids[])
        $rawBenefitIds = $_POST['benefit_ids'] ?? [];
        if (is_array($rawBenefitIds)) {
            $benefitIds = array_values(array_filter(array_map('intval', $rawBenefitIds)));
            CMS_JPG_Profiles::instance()->save_benefits($newId, $benefitIds);
        }

        // Subscription-Nutzung aktualisieren
        if (function_exists('update_resource_usage')) {
            update_resource_usage('job_profiles', 1, $this->userId);
        }

        $notice = 'Profil gespeichert (Entwurf). Zur Veröffentlichung bitte Admin-Freigabe beantragen.';
        return [$notice, $error, $newId];
    }

    // ── Phase 14.2: 1-Click Duplizierer ─────────────────────────────────────

    /**
     * GET /member/jobs/duplicate/:id – Profil als Entwurf klonen.
     */
    public function render_duplicate(string $id): void
    {
        $this->require_auth();
        $newId = $this->duplicate_profile((int) $id);
        if ($newId > 0) {
            header('Location: /member/jobs/edit/' . $newId . '?duplicated=1');
        } else {
            header('Location: /member/jobs?error=duplicate');
        }
        exit;
    }

    /**
     * Dupliziert ein eigenes Profil (Entwurf). Gibt die neue ID zurück.
     */
    private function duplicate_profile(int $id): int
    {
        $src = $this->load_own_profile($id);
        if (!$src) {
            return 0;
        }
        $newTitle = $src->title . ' (Kopie)';
        $data     = [
            'title'           => $newTitle,
            'slug'            => $this->generate_unique_slug($newTitle, 0),
            'job_category_id' => $src->job_category_id ?? null,
            'status'          => 'draft',
            'summary'         => $src->summary ?? '',
            'description'     => $src->description ?? '',
            'location'        => $src->location ?? '',
            'employment_type' => $src->employment_type ?? 'fulltime',
            'experience_level'=> $src->experience_level ?? 'mid',
            'salary_min'      => $src->salary_min ?? null,
            'salary_max'      => $src->salary_max ?? null,
            'remote_option'   => $src->remote_option ?? 'onsite',
            'show_in_listing' => (int)($src->show_in_listing ?? 0),
            'is_private'      => (int)($src->is_private ?? 0),
            'created_by'      => $this->userId,
            'updated_by'      => $this->userId,
        ];
        try {
            $newId = CMS_JPG_Profiles::instance()->save($data, 0);
            if ($newId <= 0) {
                return 0;
            }
            // Tasks, Anforderungen, Benefits übernehmen
            $tasks = CMS_JPG_Profiles::instance()->get_tasks($id);
            if (!empty($tasks)) {
                CMS_JPG_Profiles::instance()->save_tasks($newId,
                    array_column((array)$tasks, 'task_text'));
            }
            $reqs = CMS_JPG_Profiles::instance()->get_requirements($id);
            if (!empty($reqs)) {
                $reqArr = array_map(fn($r): array => [
                    'text' => $r->req_text ?? '',
                    'type' => $r->req_type ?? 'must',
                ], (array)$reqs);
                CMS_JPG_Profiles::instance()->save_requirements($newId, $reqArr);
            }
            $benefitIds = CMS_JPG_Profiles::instance()->get_benefit_ids($id);
            if (!empty($benefitIds)) {
                CMS_JPG_Profiles::instance()->save_benefits($newId, $benefitIds);
            }
            return $newId;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Erzeugt einen einzigartigen URL-Slug für ein Profil.
     * Phase 14.2: Inline-Slug-Editor
     */
    private function generate_unique_slug(string $title, int $existingId): string
    {
        $base = strtolower((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
        $base = trim($base, '-');
        $base = substr($base, 0, 80);
        if ($base === '') {
            $base = 'stelle';
        }
        $slug    = $base;
        $counter = 1;
        while (true) {
            try {
                $conflict = $this->db->get_var(
                    "SELECT id FROM {$this->p}jpg_profiles WHERE slug = ? AND id != ?",
                    [$slug, $existingId]
                );
            } catch (\Throwable $e) {
                break;
            }
            if (!$conflict) {
                break;
            }
            $slug = $base . '-' . $counter;
            $counter++;
        }
        return $slug;
    }

    // ── Datenzugriff ─────────────────────────────────────────────────────────

    /**
     * Lädt ein eigenes Profil – Data-Silo sichergestellt.
     */
    private function load_own_profile(int $id): ?object
    {
        if ($id <= 0 || $this->userId === null) {
            return null;
        }
        try {
            return $this->db->get_row(
                "SELECT * FROM {$this->p}jpg_profiles
                 WHERE id = ? AND created_by = ?",
                [$id, $this->userId]
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Zeigt Fehlermeldung wenn Limit erreicht.
     */
    private function render_limit_error(): void
    {
        if (function_exists('display_upgrade_notice')) {
            display_upgrade_notice('Ihr aktuelles Abo erlaubt keine weiteren Job-Profile.');
        } else {
            http_response_code(403);
            echo '<div class="alert alert-error">❌ Limit erreicht. Bitte upgraden Sie Ihr Abo.</div>';
        }
    }

    // ── User-Meta-Helfer ─────────────────────────────────────────────────────

    private function get_user_pref(int $userId, string $key, string $default = ''): string
    {
        try {
            $val = $this->db->get_var(
                "SELECT meta_value FROM {$this->p}user_meta
                 WHERE user_id = ? AND meta_key = ?",
                [$userId, 'jpg_pref_' . $key]
            );
            return $val !== null ? (string) $val : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function set_user_pref(int $userId, string $key, string $value): void
    {
        try {
            $metaKey = 'jpg_pref_' . $key;
            $exists  = $this->db->get_var(
                "SELECT id FROM {$this->p}user_meta WHERE user_id = ? AND meta_key = ?",
                [$userId, $metaKey]
            );
            if ($exists) {
                $this->db->update(
                    'user_meta',
                    ['meta_value' => $value],
                    ['user_id' => $userId, 'meta_key' => $metaKey]
                );
            } else {
                $this->db->insert('user_meta', [
                    'user_id'    => $userId,
                    'meta_key'   => $metaKey,
                    'meta_value' => $value,
                ]);
            }
        } catch (\Throwable $e) {
            // Nicht-kritischer Fehler
        }
    }
}
