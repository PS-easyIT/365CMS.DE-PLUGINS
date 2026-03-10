<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Member-Trait: Firmen-Einstellungen & Inline-Widgets
 *
 * render_settings()               – Standalone Route /member/jobs/settings
 * output_settings_with_layout()   – Privater Layout-Wrapper (standalone)
 * render_settings_inline()        – Multi-Tab-Settings für PluginDashboardRegistry
 * render_company_inline()         – Unternehmens-Übersicht
 * render_workflow_status_inline() – Workflow-Status eigener Stellen
 * render_libraries_inline()       – Admin-only: Benefits/Skills/Kategorien/Textbausteine
 * render_templates_inline()       – Admin-only: PDF/Web/E-Mail-Vorlagen
 * get_user_pref() / set_user_pref() – User-Meta-Helfer für Präferenzen
 *
 * @since   0.1.0
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Member_Settings_Trait
{
    // ── Standalone Route ─────────────────────────────────────────────────────

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
        $coreMainCssUrl = function_exists('cms_asset_url')
            ? cms_asset_url('css/main.css')
            : $siteUrl . '/assets/css/main.css';
        $coreAdminCssUrl = function_exists('cms_asset_url')
            ? cms_asset_url('css/admin.css')
            : $siteUrl . '/assets/css/admin.css?v=20260222b';
        $coreMemberCssUrl = function_exists('cms_asset_url')
            ? cms_asset_url('css/member.css')
            : $siteUrl . '/assets/css/member.css';

        echo '<!DOCTYPE html><html lang="de"><head>' . "\n";
        echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">' . "\n";
        echo '<title>Einstellungen – ' . htmlspecialchars($siteName) . '</title>' . "\n";
        echo '<link rel="stylesheet" href="' . htmlspecialchars($coreMainCssUrl, ENT_QUOTES) . '">' . "\n";
        echo '<link rel="stylesheet" href="' . htmlspecialchars($coreAdminCssUrl, ENT_QUOTES) . '">' . "\n";
        echo '<link rel="stylesheet" href="' . htmlspecialchars($coreMemberCssUrl, ENT_QUOTES) . '">' . "\n";
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

    // ── Inline-Render (PluginDashboardRegistry) ───────────────────────────────

    /**
     * Inline-Render: Firmeneinstellungen für das Member-Dashboard
     * Unterstützt Tabs: info | benefits | team | departments | jobs-page
     *
     * @param bool $standaloneRoute true wenn direkt über /member/jobs/settings aufgerufen
     */
    public function render_settings_inline(object $user, bool $standaloneRoute = false): void
    {
        $this->render_back_button();
        $this->userId = (int) $user->id;
        $notice  = '';
        $error   = '';
        $company = null;

        // Aktiver Tab (info | benefits | team | departments | jobs-page | email-templates)
        $activeTab = in_array($_GET['tab'] ?? '', ['info', 'benefits', 'team', 'departments', 'jobs-page', 'email-templates'], true)
            ? ($_GET['tab'])
            : 'info';

        // Aktuelle Firma des Mitglieds laden.
        // Kein Plugin-Check nötig: die companies-Tabelle ist ein Core-Feature.
        // render_company_inline lädt auf demselben Weg – ohne CMS\PluginManager-Guard.
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

        // ── POST: Tab-spezifische Verarbeitung ─────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settingsTab = sanitize_key($_POST['settings_tab'] ?? 'info');

            if ($settingsTab === 'departments') {
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
                            $notice = $ok
                                ? 'Genehmiger hinzugefügt.'
                                : 'Genehmiger konnte nicht hinzugefügt werden (bereits vorhanden oder kein Zugriff).';
                        }
                    }
                    $activeTab = 'team';
                }
            } elseif ($settingsTab === 'benefits') {
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
                if (!$this->verify_token('member_company_settings')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } elseif ($company === null) {
                    $error = 'Kein Firmenprofil gefunden.';
                } elseif (!class_exists('CMS_JPG_Departments')) {
                    $error = 'Abteilungs-Modul nicht verfügbar.';
                } else {
                    // Bestehende Einstellungen laden, damit E-Mail-Templates nicht überschrieben werden
                    $existing = CMS_JPG_Departments::instance()->get_company_settings((int) $company->id);
                    CMS_JPG_Departments::instance()->save_company_settings((int) $company->id, [
                        'jobs_page_url'              => $_POST['jobs_page_url']           ?? '',
                        'jobs_page_title'            => $_POST['jobs_page_title']         ?? '',
                        'jobs_page_intro'            => $_POST['jobs_page_intro']         ?? '',
                        'jobs_page_contact_email'    => $_POST['jobs_page_contact_email'] ?? '',
                        'jobs_page_show_salary'      => isset($_POST['jobs_page_show_salary']) ? 1 : 0,
                        'jobs_page_enabled'          => isset($_POST['jobs_page_enabled'])     ? 1 : 0,
                        // E-Mail-Templates aus bestehenden Einstellungen bewahren
                        'email_sender_name'          => $existing->email_sender_name          ?? '',
                        'email_tpl_accepted_subject' => $existing->email_tpl_accepted_subject ?? '',
                        'email_tpl_accepted_body'    => $existing->email_tpl_accepted_body    ?? '',
                        'email_tpl_rejected_subject' => $existing->email_tpl_rejected_subject ?? '',
                        'email_tpl_rejected_body'    => $existing->email_tpl_rejected_body    ?? '',
                    ]);
                    $notice    = 'Jobs-Seite Einstellungen gespeichert.';
                    $activeTab = 'jobs-page';
                }
            } elseif ($settingsTab === 'email-templates') {
                // Phase 13.1: E-Mail-Templates speichern
                if (!$this->verify_token('member_company_email_tpl')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } elseif ($company === null) {
                    $error = 'Kein Firmenprofil gefunden.';
                } elseif (!class_exists('CMS_JPG_Departments')) {
                    $error = 'Modul nicht verfügbar.';
                } else {
                    // Bestehende Einstellungen laden um andere Felder nicht zu überschreiben
                    $existingSettings = CMS_JPG_Departments::instance()->get_company_settings((int) $company->id);
                    CMS_JPG_Departments::instance()->save_company_settings((int) $company->id, [
                        'jobs_page_url'              => $existingSettings->jobs_page_url          ?? '',
                        'jobs_page_title'            => $existingSettings->jobs_page_title        ?? '',
                        'jobs_page_intro'            => $existingSettings->jobs_page_intro        ?? '',
                        'jobs_page_contact_email'    => $existingSettings->jobs_page_contact_email ?? '',
                        'jobs_page_show_salary'      => $existingSettings->jobs_page_show_salary  ?? 1,
                        'jobs_page_enabled'          => $existingSettings->jobs_page_enabled      ?? 1,
                        'email_sender_name'          => sanitize_text_field($_POST['email_sender_name']          ?? ''),
                        'email_tpl_accepted_subject' => sanitize_text_field($_POST['email_tpl_accepted_subject'] ?? ''),
                        'email_tpl_accepted_body'    => strip_tags($_POST['email_tpl_accepted_body']             ?? ''),
                        'email_tpl_rejected_subject' => sanitize_text_field($_POST['email_tpl_rejected_subject'] ?? ''),
                        'email_tpl_rejected_body'    => strip_tags($_POST['email_tpl_rejected_body']             ?? ''),
                    ]);
                    $notice    = 'E-Mail-Vorlagen gespeichert.';
                    $activeTab = 'email-templates';
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

        // Benutzer der selben Organisation für das Hinzufügen-Dropdown laden
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
        $csrfTeam         = $this->generate_token('member_company_team');
        $csrfDepartments  = $this->generate_token('member_company_departments');
        $csrfEmailTpl     = $this->generate_token('member_company_email_tpl');
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

        // Inline: baseUrl → /member/plugin/member-jobs (Tab-URL: /member/plugin/member-job-settings)
        // Standalone: baseUrl → /member/jobs (Tab-URL: /member/jobs/settings)
        $baseUrl = $standaloneRoute ? '/member/jobs' : '/member/plugin/member-jobs';
        include JPG_DIR . 'views/member/page-company-settings.php';
    }

    /**
     * Unternehmens-Übersicht für Mandanten im Member-Bereich.
     */
    public function render_company_inline(object $user): void
    {
        $this->render_back_button();
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
        $this->render_back_button();
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

    /**
     * Admin-only: Bibliotheken (Benefits, Skills, Kategorien, Textbausteine)
     */
    public function render_libraries_inline(object $user): void
    {
        $this->render_back_button();
        if (!method_exists($this->auth, 'isAdmin') || !$this->auth->isAdmin()) {
            $this->render_no_permission('Bibliotheken');
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

    /**
     * Admin-only: Vorlagen (PDF, Web, E-Mail)
     */
    public function render_templates_inline(object $user): void
    {
        $this->render_back_button();
        if (!method_exists($this->auth, 'isAdmin') || !$this->auth->isAdmin()) {
            $this->render_no_permission('Vorlagen');
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
