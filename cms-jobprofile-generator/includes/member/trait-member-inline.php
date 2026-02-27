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
    // ── Redirect-Hilfsmethode ────────────────────────────────────────────────

    /**
     * Sicherer Redirect: leert aktive Output-Buffer, setzt Location-Header und beendet
     * das Script. Dadurch werden "headers already sent"-Warnings vermieden, auch wenn
     * bereits HTML in einem ob_start()-Buffer gesammelt wurde.
     *
     * @param string $url Ziel-URL
     * @return never
     */
    private function safe_redirect(string $url): never
    {
        // Alle aktiven Output-Buffer verwerfen (nicht senden)
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Location: ' . $url, true, 302);
        exit;
    }

    // ── PluginDashboardRegistry ───────────────────────────────────────────────

    /**
     * Registriert das Job-Plugin als EINEN Menüpunkt im Member-Dashboard.
     * Callback für `member_dashboard_init`.
     *
     * Alle Sub-Bereiche werden intern über ?action=X navigiert – kein Sub-Menü
     * in der Sidebar. Standard-Action (kein Parameter): render_overview_inline().
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
                'title'          => 'Stellenanzeigen',
                'icon'           => '📄',
                'description'    => 'Erstelle, verwalte und veröffentliche deine Job-Profile – inklusive Genehmigungs-Workflow.',
                'color'          => '#3b82f6',
                'stats_callback' => [$this, 'get_dashboard_stats'],
            ],
            'render_callback' => function(object $user, array $params) use ($controller): void {
                $action     = sanitize_key($params['action'] ?? $_GET['action'] ?? '');
                $resourceId = (int) ($params['id'] ?? $_GET['id'] ?? 0);
                match ($action) {
                    'list'         => $controller->render_list_inline($user),
                    'create'       => $controller->render_create_inline($user),
                    'edit'         => $controller->render_edit_inline((string) $resourceId, $user),
                    'duplicate'    => $controller->render_duplicate_inline((string) $resourceId, $user),
                    'company'      => $controller->render_company_inline($user),
                    'workflow'     => $controller->render_workflow_status_inline($user),
                    'approvals'    => $controller->render_approvals_inline($user),
                    'applications' => $controller->render_applications_inline($user),
                    'libraries'    => $controller->render_libraries_inline($user),
                    'templates'    => $controller->render_templates_inline($user),
                    'settings'     => $controller->render_settings_inline($user),
                    default        => $controller->render_overview_inline($user),
                };
            },
        ]);
    }

    // ── Inline-Render-Methoden für PluginDashboardRegistry ───────────────────
    // Content ohne eigenes Layout – Registry-Wrapper liefert dieses bereits.

    // ── Zurück-Button ─────────────────────────────────────────────────────────

    /**
     * Gibt einen stilisierten Zurück-Button aus.
     *
     * @param string $url   Ziel-URL (Standard: Plugin-Übersicht)
     * @param string $label Button-Text
     */
    private function render_back_button(
        string $url   = '/member/plugin/member-jobs',
        string $label = '← Zurück zur Übersicht'
    ): void {
        ?>
        <div style="margin-bottom:1.25rem;">
            <a href="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"
               class="btn btn-secondary btn-sm"
               style="display:inline-flex;align-items:center;gap:.4rem;font-size:.875rem;">
                <?php echo htmlspecialchars($label, ENT_QUOTES); ?>
            </a>
        </div>
        <?php
    }

    // ── Übersichts-Dashboard ─────────────────────────────────────────────────

    /**
     * Haupt-Übersicht des Job-Plugins (Standard-Ansicht, kein ?action=-Parameter).
     * Zeigt alle 9 Sub-Bereiche als anklickbare Info-Cards mit Live-Statistik.
     */
    public function render_overview_inline(object $user): void
    {
        $this->userId = (int) $user->id;
        $isAdmin      = method_exists($this->auth, 'isAdmin') ? $this->auth->isAdmin() : false;
        $base         = '/member/plugin/member-jobs';

        // ── Live-Statistiken laden ────────────────────────────────────────────
        $stats = ['published' => 0, 'draft' => 0, 'total' => 0, 'apps' => 0, 'pending' => 0];
        try {
            if ($isAdmin) {
                $stats['total']     = (int) $this->db->get_var(
                    "SELECT COUNT(*) FROM {$this->p}jpg_profiles WHERE status != 'trash'", []
                );
                $stats['published'] = (int) $this->db->get_var(
                    "SELECT COUNT(*) FROM {$this->p}jpg_profiles WHERE status = 'published'", []
                );
                $stats['draft']     = (int) $this->db->get_var(
                    "SELECT COUNT(*) FROM {$this->p}jpg_profiles WHERE status = 'draft'", []
                );
                $stats['apps']      = (int) $this->db->get_var(
                    "SELECT COUNT(*) FROM {$this->p}jpg_applications", []
                );
                $stats['pending']   = class_exists('CMS_JPG_Workflow')
                    ? count(CMS_JPG_Workflow::instance()->get_all_pending())
                    : 0;
            } else {
                $uid = $this->userId;
                $sql = "FROM {$this->p}jpg_profiles p WHERE
                          (p.created_by = ? OR p.company_id IN
                           (SELECT id FROM {$this->p}companies WHERE user_id = ?))";
                $stats['total']     = (int) $this->db->get_var(
                    "SELECT COUNT(*) {$sql} AND p.status != 'trash'", [$uid, $uid]
                );
                $stats['published'] = (int) $this->db->get_var(
                    "SELECT COUNT(*) {$sql} AND p.status = 'published'", [$uid, $uid]
                );
                $stats['draft']     = (int) $this->db->get_var(
                    "SELECT COUNT(*) {$sql} AND p.status = 'draft'", [$uid, $uid]
                );
                $stats['apps']      = (int) $this->db->get_var(
                    "SELECT COUNT(*) FROM {$this->p}jpg_applications a
                     INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                     WHERE p.created_by = ?",
                    [$uid]
                );
                $stats['pending']   = class_exists('CMS_JPG_Workflow')
                    ? count(CMS_JPG_Workflow::instance()->get_pending_for_user($uid))
                    : 0;
            }
        } catch (\Throwable $e) { /* Statistiken optional */ }

        // ── Bereiche ─────────────────────────────────────────────────────────
        $sections = [
            [
                'action'      => 'list',
                'icon'        => '📄',
                'label'       => 'Stellenanzeigen',
                'description' => 'Alle deine Job-Profile im Überblick – bearbeiten, veröffentlichen, archivieren.',
                'color'       => '#3b82f6',
                'stat'        => $stats['total'] . ' Stelle' . ($stats['total'] !== 1 ? 'n' : ''),
                'stat_sub'    => $stats['published'] . ' aktiv',
                'cta'         => 'Alle anzeigen →',
            ],
            [
                'action'      => 'create',
                'icon'        => '➕',
                'label'       => 'Neue Stelle',
                'description' => 'Lege eine neue Stellenanzeige an und starte direkt mit dem Formular.',
                'color'       => '#10b981',
                'stat'        => '',
                'stat_sub'    => '',
                'cta'         => 'Jetzt erstellen →',
            ],
            [
                'action'      => 'company',
                'icon'        => '🏢',
                'label'       => 'Unternehmens-Übersicht',
                'description' => 'KPIs, Kennzahlen und Statistiken deines Unternehmens auf einen Blick.',
                'color'       => '#8b5cf6',
                'stat'        => '',
                'stat_sub'    => '',
                'cta'         => 'Öffnen →',
            ],
            [
                'action'      => 'workflow',
                'icon'        => '🔄',
                'label'       => 'Workflow',
                'description' => 'Status-Tracking und Einreichung eigener Stellen zur Genehmigung.',
                'color'       => '#f59e0b',
                'stat'        => $stats['pending'] > 0 ? $stats['pending'] . ' offen' : '',
                'stat_sub'    => '',
                'cta'         => 'Status ansehen →',
            ],
            [
                'action'      => 'approvals',
                'icon'        => '✅',
                'label'       => 'Genehmigungen',
                'description' => 'Freigabe-Anfragen prüfen, genehmigen oder ablehnen.',
                'color'       => '#06b6d4',
                'stat'        => $stats['pending'] . ' ausstehend',
                'stat_sub'    => '',
                'cta'         => 'Prüfen →',
            ],
            [
                'action'      => 'applications',
                'icon'        => '📬',
                'label'       => 'Bewerbungen',
                'description' => 'Alle eingegangenen Bewerbungen verwalten und herunterladen.',
                'color'       => '#ec4899',
                'stat'        => $stats['apps'] . ' Bewerbung' . ($stats['apps'] !== 1 ? 'en' : ''),
                'stat_sub'    => '',
                'cta'         => 'Anzeigen →',
            ],
            [
                'action'      => 'libraries',
                'icon'        => '📚',
                'label'       => 'Bibliotheken',
                'description' => 'Aufgaben-, Anforderungs- und Benefits-Bibliothek für alle Profile.',
                'color'       => '#64748b',
                'stat'        => '',
                'stat_sub'    => '',
                'cta'         => 'Verwalten →',
            ],
            [
                'action'      => 'templates',
                'icon'        => '🎨',
                'label'       => 'Vorlagen',
                'description' => 'Wiederverwendbare Job-Vorlagen und Textbausteine erstellen.',
                'color'       => '#7c3aed',
                'stat'        => '',
                'stat_sub'    => '',
                'cta'         => 'Vorlagen öffnen →',
            ],
            [
                'action'      => 'settings',
                'icon'        => '⚙️',
                'label'       => 'Einstellungen',
                'description' => 'Firmenprofil, Benefits, Team, Abteilungen und E-Mail-Vorlagen.',
                'color'       => '#475569',
                'stat'        => '',
                'stat_sub'    => '',
                'cta'         => 'Einrichten →',
            ],
        ];
        ?>
        <style>
        .jpg-overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1.25rem;
        }
        .jpg-overview-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1.4rem 1.5rem;
            text-decoration: none;
            color: #1e293b;
            display: flex;
            flex-direction: column;
            gap: .5rem;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            transition: box-shadow .18s, transform .18s, border-color .18s;
            position: relative;
        }
        .jpg-overview-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--card-accent, #3b82f6);
            border-radius: 10px 10px 0 0;
        }
        .jpg-overview-card:hover {
            box-shadow: 0 4px 14px rgba(0,0,0,.1);
            transform: translateY(-2px);
            border-color: var(--card-accent, #3b82f6);
            color: #1e293b;
            text-decoration: none;
        }
        .jpg-overview-card__icon  { font-size: 1.75rem; line-height: 1; }
        .jpg-overview-card__title { font-size: 1rem; font-weight: 700; color: #1e293b; }
        .jpg-overview-card__desc  { font-size: .83rem; color: #64748b; line-height: 1.45; flex: 1; }
        .jpg-overview-card__foot  { display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-top:.2rem; }
        .jpg-overview-card__stat  {
            font-size: .78rem; font-weight: 600;
            padding: .18rem .55rem; border-radius: 20px;
            background: var(--stat-bg, #f1f5f9);
            color: var(--card-accent, #475569);
        }
        .jpg-overview-card__cta   { font-size: .8rem; color: var(--card-accent, #3b82f6); font-weight: 600; white-space: nowrap; }
        </style>

        <div class="admin-card" style="padding:1.5rem 1.5rem 1.75rem;">
            <h3 style="margin-bottom:.25rem;">📄 Stellenanzeigen</h3>
            <p style="color:#64748b;font-size:.875rem;margin:0 0 1.5rem;">
                Wähle einen Bereich, um direkt loszulegen.
            </p>
            <div class="jpg-overview-grid">
                <?php foreach ($sections as $s): ?>
                    <?php
                    $accentHex = htmlspecialchars($s['color'], ENT_QUOTES);
                    $statBg    = $accentHex . '1a'; // ~10 % opacity tint
                    ?>
                    <a href="<?php echo htmlspecialchars($base . '?action=' . $s['action'], ENT_QUOTES); ?>"
                       class="jpg-overview-card"
                       style="--card-accent:<?php echo $accentHex; ?>;--stat-bg:<?php echo htmlspecialchars($statBg, ENT_QUOTES); ?>;">
                        <div class="jpg-overview-card__icon"><?php echo $s['icon']; ?></div>
                        <div class="jpg-overview-card__title"><?php echo htmlspecialchars($s['label'], ENT_QUOTES); ?></div>
                        <div class="jpg-overview-card__desc"><?php echo htmlspecialchars($s['description'], ENT_QUOTES); ?></div>
                        <div class="jpg-overview-card__foot">
                            <?php if (!empty($s['stat'])): ?>
                                <span class="jpg-overview-card__stat"><?php echo htmlspecialchars($s['stat'], ENT_QUOTES); ?></span>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            <span class="jpg-overview-card__cta"><?php echo htmlspecialchars($s['cta'], ENT_QUOTES); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public function render_list_inline(object $user): void
    {
        $this->render_back_button();
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

        $csrf      = $this->generate_token('list_action');
        $baseUrl   = '/member/plugin/member-jobs';
        // Inline-Registry nutzt GET-Parameter für Aktionen, nicht Pfad-Segmente
        $createUrl = '/member/plugin/member-jobs?action=create';
        include JPG_DIR . 'views/member/page-jobs-list.php';
    }

    public function render_create_inline(object $user): void
    {
        $this->render_back_button();
        $this->userId = (int) $user->id;
        $notice = '';
        $error  = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->verify_token('create')) {
            [$notice, $error, $newId] = $this->save_profile_post(0);
            if ($newId > 0 && empty($error)) {
                $this->safe_redirect('/member/plugin/member-jobs?action=edit&id=' . $newId . '&created=1');
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

    public function render_duplicate_inline(string $id, object $user): void
    {
        $this->userId = (int) $user->id;
        $newId = $this->duplicate_profile((int) $id);
        if ($newId > 0) {
            $this->safe_redirect('/member/plugin/member-jobs?action=edit&id=' . $newId . '&duplicated=1');
        } else {
            $this->safe_redirect('/member/plugin/member-jobs?error=duplicate');
        }
    }

    public function render_edit_inline(string $id, object $user): void
    {
        $this->render_back_button('/member/plugin/member-jobs?action=list', '← Zurück zur Liste');
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
