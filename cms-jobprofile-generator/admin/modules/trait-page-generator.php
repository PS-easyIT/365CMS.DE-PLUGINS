<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin-Seite: Profil-Generator
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Page_Generator_Trait
{
    // ── 2. PROFIL-GENERATOR ──────────────────────────────────────────────────

    public static function render_generator(): void
    {
        self::check_access();

        // Früh-Abfang: JSON-Export-Download (Link aus Import/Export-Tab)
        if (($_GET['_jpg_export'] ?? '') === 'json') {
            self::ajax_export_json();
            return;
        }

        // Früh-Abfang: AJAX HTML-Vorschau (POST, von Tab "Review & Export")
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_jpg_action'] ?? '') === 'preview') {
            self::ajax_preview();
            return;
        }

        $tab         = sanitize_key($_GET['tab'] ?? 'basic');
        $id          = (int) ($_GET['id'] ?? 0);
        $showPrivate = !empty($_GET['show_private']); // Phase 9: Privacy-Filter
        $notice      = '';
        $error       = '';

        // POST-Handler
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_nonce('jpg_generator_save')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $action = sanitize_key($_POST['_jpg_action'] ?? 'save_basic');
                [$notice, $error, $id] = self::handle_generator_post($action, $id);
            }
        }

        $profile      = $id > 0 ? CMS_JPG_Profiles::instance()->get($id) : null;
        $tasks        = $id > 0 ? CMS_JPG_Profiles::instance()->get_tasks($id) : [];
        $requirements = $id > 0 ? CMS_JPG_Profiles::instance()->get_requirements($id) : [];
        $profileSkills= $id > 0 ? CMS_JPG_Profiles::instance()->get_skills($id) : [];
        $benefitIds   = $id > 0 ? CMS_JPG_Profiles::instance()->get_benefit_ids($id) : [];
        $categories   = CMS_JPG_JobCategories::instance()->get_all();
        $allSkills    = CMS_JPG_SkillMatrix::instance()->get_grouped();
        $allBenefits  = CMS_JPG_BenefitsCatalog::instance()->get_grouped();
        $textModules  = CMS_JPG_TextModules::instance()->get_list(['limit' => 100]);
        $requirementItems = class_exists('CMS_JPG_RequirementItems')
            ? CMS_JPG_RequirementItems::instance()->get_grouped()
            : [];

        // Phase 9: Admin-Zugriffs-Log schreiben wenn Profil geöffnet wird
        if ($id > 0 && $profile !== null && class_exists('CMS_JPG_Workflow')) {
            $adminId = method_exists(\CMS\Auth::instance(), 'getUserId')
                ? (int) \CMS\Auth::instance()->getUserId() : 0;
            if ($adminId > 0) {
                CMS_JPG_Workflow::log_admin_access(
                    $adminId,
                    (int) ($profile->company_id ?? 0),
                    $id,
                    'view_profile'
                );
            }
        }

        // Phase 6.1 – cms-companies Integration (mit Adressdaten für Auto-Fill)
        $companies     = [];
        $companiesJson = '{}';
        $companyDefaultBenefitIds = [];

        if (class_exists('CMS\PluginManager')
            && in_array('cms-companies', \CMS\PluginManager::instance()->getActivePlugins(), true)) {
            try {
                $db  = \CMS\Database::instance();
                $p   = $db->getPrefix();
                $rows = $db->get_results(
                    "SELECT id, name, location_city, location_zip, location_country, phone, website
                     FROM {$p}companies WHERE status = 'active' ORDER BY name ASC",
                    []
                ) ?: [];
                $companies = $rows;

                $compMap = [];
                foreach ($rows as $c) {
                    $compMap[(int) $c->id] = [
                        'name'     => $c->name ?? '',
                        'city'     => $c->location_city ?? '',
                        'zip'      => $c->location_zip ?? '',
                        'country'  => $c->location_country ?? '',
                        'phone'    => $c->phone ?? '',
                        'website'  => $c->website ?? '',
                    ];
                }
                $companiesJson = json_encode($compMap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

                $currentCompanyId = (int) ($profile->company_id ?? 0);
                if ($currentCompanyId > 0) {
                    $defBen = $db->get_results(
                        "SELECT benefit_id FROM {$p}jpg_company_default_benefits WHERE company_id = ?",
                        [$currentCompanyId]
                    ) ?: [];
                    $companyDefaultBenefitIds = array_map(fn($r) => (int) $r->benefit_id, $defBen);
                }
            } catch (\Throwable $e) {
                $companies = [];
            }
        }

        $tabs = [
            'basic'        => 'Basisdaten',
            'tasks'        => 'Aufgaben',
            'requirements' => 'Anforderungen',
            'benefits'     => 'Benefits',
            'skills'       => 'Skills',
            'review'       => 'Review & Export',
        ];

        include JPG_DIR . 'admin/views/page-generator.php';
    }

    /** @return array{string, string, int} [notice, error, id] */
    private static function handle_generator_post(string $action, int $id): array
    {
        $notice = '';
        $error  = '';
        $auth   = \CMS\Auth::instance();
        $userId = method_exists($auth, 'getUserId') ? (int) $auth->getUserId() : 0;

        switch ($action) {
            case 'save_basic':
                if (empty(trim($_POST['title'] ?? ''))) {
                    $error = 'Titel ist erforderlich.';
                    break;
                }
                $data = [
                    'title'            => sanitize_text_field($_POST['title'] ?? ''),
                    'company_id'       => (int) ($_POST['company_id'] ?? 0),
                    'job_category_id'  => (int) ($_POST['job_category_id'] ?? 0),
                    'status'           => sanitize_key($_POST['status'] ?? 'draft'),
                    'summary'          => sanitize_text_field($_POST['summary'] ?? ''),
                    'description'      => $_POST['description'] ?? '',
                    'location'         => sanitize_text_field($_POST['location'] ?? ''),
                    'employment_type'  => sanitize_key($_POST['employment_type'] ?? 'fulltime'),
                    'experience_level' => sanitize_key($_POST['experience_level'] ?? 'mid'),
                    'salary_min'       => $_POST['salary_min'] ?? '',
                    'salary_max'       => $_POST['salary_max'] ?? '',
                    'remote_option'    => sanitize_key($_POST['remote_option'] ?? 'onsite'),
                    'is_private'       => isset($_POST['is_private']) ? 1 : 0,
                    'show_in_listing'  => isset($_POST['show_in_listing']) ? 1 : 0,
                    'created_by'       => $userId,
                    'updated_by'       => $userId,
                ];
                $id     = CMS_JPG_Profiles::instance()->save($data, $id);
                $notice = 'Basisdaten gespeichert.';
                break;

            case 'save_tasks':
                $rawTasks = $_POST['tasks'] ?? [];
                if (!is_array($rawTasks)) {
                    $rawTasks = [];
                }
                $tasks = array_filter(
                    array_map(fn($t) => sanitize_text_field($t), $rawTasks),
                    fn($t) => $t !== ''
                );
                if (count($tasks) < 3) {
                    $error = 'Mindestens 3 Aufgaben sind erforderlich.';
                    break;
                }
                CMS_JPG_Profiles::instance()->save_tasks($id, array_values($tasks));
                $notice = 'Aufgaben gespeichert.';
                break;

            case 'save_requirements':
                $rawReqs  = $_POST['req_text'] ?? [];
                $rawTypes = $_POST['req_type'] ?? [];
                if (!is_array($rawReqs)) {
                    $rawReqs = [];
                }
                $reqs = [];
                foreach ($rawReqs as $i => $text) {
                    $text = sanitize_text_field($text);
                    if ($text === '') {
                        continue;
                    }
                    $reqs[] = [
                        'text' => $text,
                        'type' => in_array($rawTypes[$i] ?? '', ['must', 'nice', 'optional']) ? $rawTypes[$i] : 'must',
                    ];
                }
                CMS_JPG_Profiles::instance()->save_requirements($id, $reqs);
                $notice = 'Anforderungen gespeichert.';
                break;

            case 'save_benefits':
                $rawIds = $_POST['benefit_ids'] ?? [];
                if (!is_array($rawIds)) {
                    $rawIds = [];
                }
                $ids = array_map('intval', $rawIds);
                CMS_JPG_Profiles::instance()->save_benefits($id, $ids);
                $notice = 'Benefits gespeichert.';
                break;

            case 'save_skills':
                $rawSkillIds = $_POST['skill_ids'] ?? [];
                if (!is_array($rawSkillIds)) {
                    $rawSkillIds = [];
                }
                $skills = [];
                foreach ($rawSkillIds as $sid) {
                    $sid = (int)$sid;
                    if ($sid > 0) {
                        $level    = sanitize_key($_POST['skill_level_' . $sid] ?? 'intermediate');
                        $skills[] = ['skill_id' => $sid, 'level' => $level];
                    }
                }
                CMS_JPG_Profiles::instance()->save_skills($id, $skills);
                $notice = 'Skills gespeichert.';
                break;

            case 'publish':
                if ($id > 0) {
                    CMS_JPG_Profiles::instance()->save(['status' => 'published', 'updated_by' => $userId], $id);
                    $notice = 'Profil veröffentlicht.';
                }
                break;

            case 'delete':
                if ($id > 0) {
                    CMS_JPG_Profiles::instance()->delete($id);
                    self::redirect('jpg-dashboard');
                }
                break;
        }

        return [$notice, $error, $id];
    }
}
