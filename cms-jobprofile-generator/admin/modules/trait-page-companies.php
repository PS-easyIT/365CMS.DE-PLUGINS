<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin-Seite: Unternehmens-Übersicht (inkl. Departments & Standard-Benefits)
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Page_Companies_Trait
{
    // ── 8. UNTERNEHMENS-ÜBERSICHT ─────────────────────────────────────────────

    public static function render_company_overview(): void
    {
        self::check_access();

        $notice = '';
        $error  = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postAction = sanitize_key($_POST['post_action'] ?? 'save_benefits');
            if ($postAction === 'save_company_info') {
                if (!self::verify_nonce('jpg_company_info_save')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } else {
                    [$notice, $error] = self::handle_company_info_post();
                }
            } elseif ($postAction === 'save_jobs_url' || $postAction === 'save_jobs_page_settings') {
                if (!self::verify_nonce('jpg_company_info_save')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } else {
                    $cid = (int) ($_POST['company_id'] ?? 0);
                    if ($cid > 0 && class_exists('CMS_JPG_Departments')) {
                        // Bestehende Einstellungen laden – UPSERT schreibt alle 11 Felder;
                        // E-Mail-Templates müssen bewahrt werden.
                        $existing = CMS_JPG_Departments::instance()->get_company_settings($cid);
                        CMS_JPG_Departments::instance()->save_company_settings($cid, [
                            'jobs_page_url'           => sanitize_text_field($_POST['jobs_page_url']           ?? ''),
                            'jobs_page_title'         => sanitize_text_field($_POST['jobs_page_title']         ?? ''),
                            'jobs_page_intro'         => sanitize_text_field($_POST['jobs_page_intro']         ?? ''),
                            'jobs_page_contact_email' => filter_var($_POST['jobs_page_contact_email'] ?? '', FILTER_SANITIZE_EMAIL),
                            'jobs_page_show_salary'   => isset($_POST['jobs_page_show_salary']) ? 1 : 0,
                            'jobs_page_enabled'       => isset($_POST['jobs_page_enabled'])     ? 1 : 0,
                            // Bestehende E-Mail-Templates erhalten
                            'email_sender_name'          => $existing->email_sender_name          ?? '',
                            'email_tpl_accepted_subject' => $existing->email_tpl_accepted_subject ?? '',
                            'email_tpl_accepted_body'    => $existing->email_tpl_accepted_body    ?? '',
                            'email_tpl_rejected_subject' => $existing->email_tpl_rejected_subject ?? '',
                            'email_tpl_rejected_body'    => $existing->email_tpl_rejected_body    ?? '',
                        ]);
                        $notice = 'Jobs-Seite Einstellungen gespeichert.';
                    }
                }
            } elseif (in_array($postAction, ['save_department', 'delete_department',
                                             'save_dept_benefits', 'save_dept_requirements'], true)) {
                if (!self::verify_nonce('jpg_company_info_save')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } else {
                    [$notice, $error] = self::handle_department_post($postAction);
                }
            } else {
                if (!self::verify_nonce('jpg_company_benefits_save')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } else {
                    [$notice, $error] = self::handle_company_overview_post();
                }
            }
        }

        $companies  = [];
        $linkedUser = null;
        if (class_exists('CMS\PluginManager')
            && in_array('cms-companies', \CMS\PluginManager::instance()->getActivePlugins(), true)) {
            try {
                $db  = \CMS\Database::instance();
                $p   = $db->getPrefix();
                $companies = $db->get_results(
                    "SELECT id, name, email, phone, industry, company_size, description, logo_url,
                            website, location_city, location_zip, location_country,
                            founded_year, employee_count, is_partner, user_id
                     FROM {$p}companies WHERE status = 'active' ORDER BY name ASC",
                    []
                ) ?: [];
            } catch (\Throwable $e) {
                $companies = [];
            }
        }

        $allBenefits    = CMS_JPG_BenefitsCatalog::instance()->get_grouped();
        $assignmentsMap = [];
        $jobCountsMap   = [];
        try {
            $db   = \CMS\Database::instance();
            $p    = $db->getPrefix();
            $rows = $db->get_results(
                "SELECT company_id, benefit_id FROM {$p}jpg_company_default_benefits ORDER BY company_id ASC",
                []
            ) ?: [];
            foreach ($rows as $row) {
                $assignmentsMap[(int) $row->company_id][] = (int) $row->benefit_id;
            }
            $jobRows = $db->get_results(
                "SELECT company_id,
                        COUNT(*) AS total,
                        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published
                 FROM {$p}jpg_profiles
                 WHERE company_id > 0
                 GROUP BY company_id",
                []
            ) ?: [];
            foreach ($jobRows as $jr) {
                $jobCountsMap[(int) $jr->company_id] = [
                    'published' => (int) $jr->published,
                    'total'     => (int) $jr->total,
                ];
            }
        } catch (\Throwable $e) { /* ignore */ }

        $selectedCompanyId = (int) ($_GET['company'] ?? 0);

        if ($selectedCompanyId > 0) {
            foreach ($companies as $c) {
                if ((int) $c->id === $selectedCompanyId && !empty($c->user_id)) {
                    try {
                        $ud = \CMS\Database::instance();
                        $linkedUser = $ud->get_row(
                            "SELECT id, username, email, firstname, lastname
                             FROM {$ud->getPrefix()}users WHERE id = ? LIMIT 1",
                            [(int) $c->user_id]
                        );
                    } catch (\Throwable $ex) { /* ignore */ }
                    break;
                }
            }
        }

        if ($selectedCompanyId > 0 && class_exists('CMS_JPG_Workflow')) {
            $adminId = method_exists(\CMS\Auth::instance(), 'getUserId')
                ? (int) \CMS\Auth::instance()->getUserId() : 0;
            if ($adminId > 0) {
                CMS_JPG_Workflow::log_admin_access($adminId, $selectedCompanyId, 0, 'view_company');
            }
        }

        $departments       = [];
        $allDeptBenefitIds = [];
        $allDeptReqIds     = [];
        $companySettings   = null;
        $allReqItems       = [];
        if ($selectedCompanyId > 0 && class_exists('CMS_JPG_Departments')) {
            $deptInst        = CMS_JPG_Departments::instance();
            $departments     = $deptInst->get_all($selectedCompanyId);
            $companySettings = $deptInst->get_company_settings($selectedCompanyId);
            foreach ($departments as $dept) {
                $did = (int) $dept->id;
                $allDeptBenefitIds[$did] = $deptInst->get_benefit_ids($did);
                $allDeptReqIds[$did]     = $deptInst->get_requirement_ids($did);
            }
        }
        if (class_exists('CMS_JPG_RequirementItems')) {
            $allReqItems = CMS_JPG_RequirementItems::instance()->get_grouped();
        }

        include JPG_DIR . 'admin/views/page-company-overview.php';
    }

    /** @return array{string, string} [notice, error] */
    private static function handle_company_info_post(): array
    {
        $notice    = '';
        $error     = '';
        $companyId = (int) ($_POST['company_id'] ?? 0);

        if ($companyId <= 0) {
            $error = 'Kein Unternehmen gewählt.';
            return [$notice, $error];
        }

        $name          = sanitize_text_field($_POST['company_name']         ?? '');
        $email         = sanitize_text_field($_POST['company_email']        ?? '');
        $phone         = sanitize_text_field($_POST['company_phone']        ?? '');
        $website       = filter_var(trim($_POST['company_website']   ?? ''), FILTER_VALIDATE_URL) ?: '';
        $logoUrl       = filter_var(trim($_POST['company_logo_url']  ?? ''), FILTER_VALIDATE_URL) ?: '';
        $industry      = sanitize_text_field($_POST['company_industry']     ?? '');
        $companySize   = sanitize_text_field($_POST['company_size']         ?? '');
        $description   = strip_tags($_POST['company_description']           ?? '');
        $city          = sanitize_text_field($_POST['company_city']         ?? '');
        $zip           = sanitize_text_field($_POST['company_zip']          ?? '');
        $country       = sanitize_text_field($_POST['company_country']      ?? '');
        $foundedYear   = (int) ($_POST['company_founded_year']              ?? 0);
        $employeeCount = (int) ($_POST['company_employee_count']            ?? 0);

        if (empty($name)) {
            $error = 'Unternehmensname darf nicht leer sein.';
            return [$notice, $error];
        }

        try {
            $db  = \CMS\Database::instance();
            $p   = $db->getPrefix();
            $pdo = $db->getPdo();
            $pdo->prepare(
                "UPDATE {$p}companies
                 SET name = ?, email = ?, phone = ?, website = ?, logo_url = ?,
                     industry = ?, company_size = ?, description = ?,
                     location_city = ?, location_zip = ?, location_country = ?,
                     founded_year = NULLIF(?, 0), employee_count = NULLIF(?, 0)
                 WHERE id = ?"
            )->execute([
                $name, $email, $phone, $website, $logoUrl,
                $industry, $companySize, $description,
                $city, $zip, $country,
                $foundedYear, $employeeCount,
                $companyId,
            ]);
            $notice = 'Firmendaten wurden gespeichert.';
        } catch (\Throwable $e) {
            $error = 'Datenbankfehler: ' . $e->getMessage();
        }

        return [$notice, $error];
    }

    /** @return array{string, string} [notice, error] */
    private static function handle_department_post(string $action): array
    {
        $notice = '';
        $error  = '';
        if (!class_exists('CMS_JPG_Departments')) {
            return ['', 'Departments-Klasse nicht verfügbar.'];
        }
        $deptInst  = CMS_JPG_Departments::instance();
        $companyId = (int) ($_POST['company_id']   ?? 0);
        $deptId    = (int) ($_POST['department_id'] ?? 0);

        switch ($action) {
            case 'save_department':
                $name = sanitize_text_field($_POST['dept_name'] ?? '');
                if (empty($name)) { $error = 'Abteilungsname darf nicht leer sein.'; break; }
                $data = [
                    'company_id'  => $companyId,
                    'name'        => $name,
                    'description' => sanitize_text_field($_POST['dept_description'] ?? ''),
                    'sort_order'  => (int) ($_POST['dept_sort_order'] ?? 0),
                    'created_by'  => method_exists(\CMS\Auth::instance(), 'getUserId')
                                     ? (int)\CMS\Auth::instance()->getUserId() : 0,
                ];
                $deptInst->save($data, $deptId);
                $notice = $deptId > 0 ? 'Abteilung aktualisiert.' : 'Abteilung angelegt.';
                break;

            case 'delete_department':
                if ($deptId > 0) { $deptInst->delete($deptId); $notice = 'Abteilung gelöscht.'; }
                break;

            case 'save_dept_benefits':
                if ($deptId > 0) {
                    $ids = array_map('intval', (array) ($_POST['dept_benefit_ids'] ?? []));
                    $deptInst->save_benefits($deptId, $ids);
                    $notice = 'Abteilungs-Benefits gespeichert.';
                }
                break;

            case 'save_dept_requirements':
                if ($deptId > 0) {
                    $ids = array_map('intval', (array) ($_POST['dept_req_ids'] ?? []));
                    $deptInst->save_requirements($deptId, $ids);
                    $notice = 'Abteilungs-Anforderungen gespeichert.';
                }
                break;
        }
        return [$notice, $error];
    }

    /** @return array{string, string} [notice, error] */
    private static function handle_company_overview_post(): array
    {
        $notice    = '';
        $error     = '';
        $companyId = (int) ($_POST['company_id'] ?? 0);

        if ($companyId <= 0) {
            $error = 'Kein Unternehmen gewählt.';
            return [$notice, $error];
        }

        $rawIds = $_POST['benefit_ids'] ?? [];
        $ids    = is_array($rawIds) ? array_map('intval', $rawIds) : [];

        try {
            $db  = \CMS\Database::instance();
            $p   = $db->getPrefix();
            $pdo = $db->getPdo();
            $pdo->prepare("DELETE FROM {$p}jpg_company_default_benefits WHERE company_id = ?")->execute([$companyId]);
            foreach ($ids as $benefitId) {
                if ($benefitId > 0) {
                    $pdo->prepare(
                        "INSERT IGNORE INTO {$p}jpg_company_default_benefits (company_id, benefit_id) VALUES (?, ?)"
                    )->execute([$companyId, $benefitId]);
                }
            }
            $notice = 'Standard-Benefits für das Unternehmen gespeichert.';
        } catch (\Throwable $e) {
            $error = 'Datenbankfehler: ' . $e->getMessage();
        }

        return [$notice, $error];
    }
}
