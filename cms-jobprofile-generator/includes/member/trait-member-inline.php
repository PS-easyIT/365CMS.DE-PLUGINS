<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Member-Trait: PluginDashboardRegistry & Inline-Render-Methoden
 *
 * register_via_plugin_dashboard() – Haupt-Slot + alle Sub-Slots
 * render_list_inline()            – Job-Liste im Registry-Wrapper
 * render_create_inline()          – Neu-Formular im Registry-Wrapper
 * render_edit_inline()            – Bearbeitungs-Formular im Registry-Wrapper
 *
 * @since   0.1.0
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Member_Inline_Trait
{
    // ── PluginDashboardRegistry ───────────────────────────────────────────────

    /**
     * Registriert ALLE Job-Bereiche im Member-Dashboard via PluginDashboardRegistry.
     * Callback für `member_dashboard_init`.
     *
     * Designprinzip: Jeder Menüpunkt ist für ALLE eingeloggten Mitglieder sichtbar.
     * Fehlt die Berechtigung für den Inhalt, wird render_no_permission() angezeigt –
     * der Menüpunkt wird NICHT ausgeblendet (Transparenz-Prinzip für Mandanten).
     *
     * Reihenfolge: Stellenanzeigen → Neue Stelle → Unternehmens-Übersicht →
     *              Workflow → Genehmigungen → Bewerbungen →
     *              Bibliotheken → Vorlagen → Einstellungen
     *
     * @param \CMS\Member\PluginDashboardRegistry $registry
     */
    public function register_via_plugin_dashboard(object $registry): void
    {
        if (!method_exists($registry, 'register')) {
            return;
        }

        $controller = $this;

        // ── 1. Hauptbereich: Stellenanzeigen (Job-Liste) ─────────────────────
        $registry->register([
            'plugin'    => 'cms-jobprofile-generator',
            'slug'      => 'member-jobs',
            'label'     => 'Stellenanzeigen',
            'icon'      => '📄',
            'category'  => 'plugins',
            'priority'  => 20,
            'capability'=> null,
            'dashboard_widget' => [
                'title'          => 'Stellenanzeigen',
                'icon'           => '📄',
                'description'    => 'Erstelle, verwalte und veröffentliche deine Job-Profile – inklusive Genehmigungs-Workflow.',
                'color'          => '#3b82f6',
                'stats_callback' => [$this, 'get_dashboard_stats'],
            ],
            'render_callback' => function(object $user, array $params) use ($controller): void {
                $action     = sanitize_key($params['action'] ?? $_GET['action'] ?? 'list');
                $resourceId = (int) ($params['id'] ?? $_GET['id'] ?? 0);
                match ($action) {
                    'create' => $controller->render_create_inline($user),
                    'edit'   => $controller->render_edit_inline((string) $resourceId, $user),
                    default  => $controller->render_list_inline($user),
                };
            },
        ]);

        // ── 2. Neue Stelle ────────────────────────────────────────────────────
        $registry->register([
            'plugin'      => 'cms-jobprofile-generator',
            'slug'        => 'member-job-new',
            'label'       => 'Neue Stelle',
            'icon'        => '➕',
            'category'    => 'plugins',
            'priority'    => 21,
            'capability'  => null,
            'parent_slug' => 'plugin_member-jobs',
            'render_callback' => function(object $user, array $params) use ($controller): void {
                $controller->render_create_inline($user);
            },
        ]);

        // ── 3. Unternehmens-Übersicht (KPI-Dashboard der Firma) ──────────────
        $registry->register([
            'plugin'      => 'cms-jobprofile-generator',
            'slug'        => 'member-job-company',
            'label'       => 'Unternehmens-Übersicht',
            'icon'        => '🏢',
            'category'    => 'plugins',
            'priority'    => 22,
            'capability'  => null,
            'parent_slug' => 'plugin_member-jobs',
            'render_callback' => function(object $user, array $params) use ($controller): void {
                $controller->render_company_inline($user);
            },
        ]);

        // ── 4. Workflow (Status eigener Stellen, Einreichung zur Genehmigung) ─
        $registry->register([
            'plugin'      => 'cms-jobprofile-generator',
            'slug'        => 'member-job-workflow',
            'label'       => 'Workflow',
            'icon'        => '🔄',
            'category'    => 'plugins',
            'priority'    => 23,
            'capability'  => null,
            'parent_slug' => 'plugin_member-jobs',
            'render_callback' => function(object $user, array $params) use ($controller): void {
                $controller->render_workflow_status_inline($user);
            },
        ]);

        // ── 5. Genehmigungen (immer sichtbar; Inhalt nur für Genehmiger/Admins)
        $registry->register([
            'plugin'      => 'cms-jobprofile-generator',
            'slug'        => 'member-job-approvals',
            'label'       => 'Genehmigungen',
            'icon'        => '✅',
            'category'    => 'plugins',
            'priority'    => 24,
            'capability'  => null,
            'parent_slug' => 'plugin_member-jobs',
            'render_callback' => function(object $user, array $params) use ($controller): void {
                $controller->render_approvals_inline($user);
            },
        ]);

        // ── 6. Bewerbungen ────────────────────────────────────────────────────
        $registry->register([
            'plugin'      => 'cms-jobprofile-generator',
            'slug'        => 'member-job-applications',
            'label'       => 'Bewerbungen',
            'icon'        => '📬',
            'category'    => 'plugins',
            'priority'    => 25,
            'capability'  => null,
            'parent_slug' => 'plugin_member-jobs',
            'render_callback' => function(object $user, array $params) use ($controller): void {
                $controller->render_applications_inline($user);
            },
        ]);

        // ── 7. Bibliotheken (immer sichtbar; Inhalt nur für Admins) ──────────
        $registry->register([
            'plugin'      => 'cms-jobprofile-generator',
            'slug'        => 'member-job-libraries',
            'label'       => 'Bibliotheken',
            'icon'        => '📚',
            'category'    => 'plugins',
            'priority'    => 26,
            'capability'  => null,
            'parent_slug' => 'plugin_member-jobs',
            'render_callback' => function(object $user, array $params) use ($controller): void {
                $controller->render_libraries_inline($user);
            },
        ]);

        // ── 8. Vorlagen (immer sichtbar; Inhalt nur für Admins) ──────────────
        $registry->register([
            'plugin'      => 'cms-jobprofile-generator',
            'slug'        => 'member-job-templates',
            'label'       => 'Vorlagen',
            'icon'        => '🎨',
            'category'    => 'plugins',
            'priority'    => 27,
            'capability'  => null,
            'parent_slug' => 'plugin_member-jobs',
            'render_callback' => function(object $user, array $params) use ($controller): void {
                $controller->render_templates_inline($user);
            },
        ]);

        // ── 9. Einstellungen (Firmenprofil, Benefits, Team, Abteilungen) ──────
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

    // ── Inline-Render-Methoden für PluginDashboardRegistry ───────────────────
    // Content ohne eigenes Layout – Registry-Wrapper liefert dieses bereits.

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

    // ── Berechtigungs-Fallback ────────────────────────────────────────────────

    /**
     * Einheitliche "Keine Berechtigung"-Seite für geschützte Bereiche.
     *
     * Wird aufgerufen, wenn ein Menüpunkt immer sichtbar ist, der Inhalt aber
     * eine höhere Berechtigungsstufe erfordert (Admin, Genehmiger-Rolle …).
     * Alle render_*_inline()-Methoden rufen diese Methode auf, statt den
     * Menüpunkt ganz auszublenden – so bleibt die Menüstruktur für alle
     * Mandanten einheitlich und transparent.
     *
     * @param string $feature  Lesbare Bezeichnung des gesperrten Bereichs
     */
    public function render_no_permission(string $feature = ''): void
    {
        $featureText = $feature !== ''
            ? htmlspecialchars($feature, ENT_QUOTES)
            : 'diesen Bereich';
        ?>
        <div class="admin-card" style="text-align:center;padding:4rem 2rem;">
            <p style="font-size:4rem;margin:0 0 1rem;line-height:1;">🔒</p>
            <h3 style="margin:0 0 .5rem;color:#1e293b;font-size:1.3rem;font-weight:700;">
                Keine Berechtigung
            </h3>
            <p style="color:#64748b;font-size:.95rem;max-width:440px;margin:.5rem auto 0;">
                Du hast keinen Zugriff auf <strong><?php echo $featureText; ?></strong>.
            </p>
            <p style="color:#94a3b8;font-size:.82rem;margin-top:.85rem;">
                Wende dich an deinen Administrator, um die notwendigen Rechte zu erhalten.
            </p>
        </div>
        <?php
    }
}
