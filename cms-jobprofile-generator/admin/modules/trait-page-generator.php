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
        $actionParam = sanitize_key($_GET['action'] ?? '');

        // Phase 14.2: 1-Click Duplizierer
        if ($actionParam === 'duplicate' && $id > 0) {
            $newId = self::duplicate_profile_admin($id);
            if ($newId > 0) {
                header('Location: /admin/plugins/jpg-dashboard/jpg-generator?id=' . $newId . '&duplicated=1');
            } else {
                header('Location: /admin/plugins/jpg-dashboard/jpg-dashboard?error=duplicate');
            }
            exit;
        }

        $showPrivate = !empty($_GET['show_private']); // Phase 9: Privacy-Filter
        $notice      = !empty($_GET['duplicated']) ? 'Profil erfolgreich dupliziert.' : '';
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

        self::render_admin_view(
            'Profil-Generator',
            'jpg-generator',
            JPG_DIR . 'admin/views/page-generator.php',
            compact(
                'tab',
                'id',
                'showPrivate',
                'notice',
                'error',
                'profile',
                'tasks',
                'requirements',
                'profileSkills',
                'benefitIds',
                'categories',
                'allSkills',
                'allBenefits',
                'textModules',
                'requirementItems',
                'companies',
                'companiesJson',
                'companyDefaultBenefitIds',
                'tabs'
            )
        );
    }

    private static function duplicate_profile_admin(int $id): int
    {
        $src = CMS_JPG_Profiles::instance()->get($id);
        if (!$src) {
            return 0;
        }
        $newTitle = $src->title . ' (Kopie)';
        $data     = [
            'title'            => $newTitle,
            'slug'             => self::generate_unique_slug_admin($newTitle, 0),
            'company_id'       => $src->company_id ?? 0,
            'job_category_id'  => $src->job_category_id ?? 0,
            'status'           => 'draft',
            'summary'          => $src->summary ?? '',
            'description'      => $src->description ?? '',
            'location'         => $src->location ?? '',
            'employment_type'  => $src->employment_type ?? 'fulltime',
            'experience_level' => $src->experience_level ?? 'mid',
            'salary_min'       => $src->salary_min ?? null,
            'salary_max'       => $src->salary_max ?? null,
            'remote_option'    => $src->remote_option ?? 'onsite',
            'is_private'       => $src->is_private ?? 0,
            'show_in_listing'  => $src->show_in_listing ?? 0,
            'created_by'       => method_exists(\CMS\Auth::instance(), 'getUserId') ? (int) \CMS\Auth::instance()->getUserId() : 0,
        ];

        try {
            $newId = CMS_JPG_Profiles::instance()->save($data, 0);
            if ($newId > 0) {
                // Tasks kopieren
                $tasks = CMS_JPG_Profiles::instance()->get_tasks($id);
                if (!empty($tasks)) {
                    $taskTexts = array_map(fn($t) => $t->task_text, $tasks);
                    CMS_JPG_Profiles::instance()->save_tasks($newId, $taskTexts);
                }
                // Requirements kopieren
                $reqs = CMS_JPG_Profiles::instance()->get_requirements($id);
                if (!empty($reqs)) {
                    $reqData = array_map(fn($r) => ['text' => $r->requirement_text, 'type' => $r->type], $reqs);
                    CMS_JPG_Profiles::instance()->save_requirements($newId, $reqData);
                }
                // Benefits kopieren
                $bids = CMS_JPG_Profiles::instance()->get_benefit_ids($id);
                if (!empty($bids)) {
                    CMS_JPG_Profiles::instance()->save_benefits($newId, $bids);
                }
                // Skills kopieren
                $skills = CMS_JPG_Profiles::instance()->get_skills($id);
                if (!empty($skills)) {
                    $skillData = array_map(fn($s) => ['skill_id' => $s->skill_id, 'level' => $s->level], $skills);
                    CMS_JPG_Profiles::instance()->save_skills($newId, $skillData);
                }
            }
            return $newId;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private static function generate_unique_slug_admin(string $title, int $existingId): string
    {
        $base = strtolower((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
        $base = trim($base, '-');
        $base = substr($base, 0, 80);
        if ($base === '') {
            $base = 'stelle';
        }
        $slug    = $base;
        $counter = 1;
        $db = \CMS\Database::instance();
        $p = $db->getPrefix();
        while (true) {
            try {
                $conflict = $db->get_var(
                    "SELECT id FROM {$p}jpg_profiles WHERE slug = ? AND id != ?",
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

    /** @return array{string, string, int} [notice, error, id] */
    private static function handle_generator_post(string $action, int $id): array
    {
        $notice = '';
        $error  = '';
        $auth   = \CMS\Auth::instance();
        $userId = method_exists($auth, 'getUserId') ? (int) $auth->getUserId() : 0;

        switch ($action) {
            case 'save_basic':
                $rawTitle = sanitize_text_field(trim($_POST['title'] ?? ''));
                if (empty($rawTitle)) {
                    $error = 'Titel ist erforderlich.';
                    break;
                }
                // Slug: manuell eingegeben > Eindeutigkeit prüfen; leer = auto aus Titel
                $slugInput = sanitize_key(str_replace('_', '-', strtolower($_POST['slug'] ?? '')));
                if ($slugInput === '') {
                    // Leerer String: CMS_JPG_Profiles::save() generiert Slug automatisch aus Titel+ID
                    $slugRaw = '';
                } else {
                    // Prüfen ob Wunsch-Slug bereits vergeben ist
                    $db2 = \CMS\Database::instance();
                    $p2  = $db2->getPrefix();
                    $conflict = $db2->get_var(
                        "SELECT id FROM {$p2}jpg_profiles WHERE slug = ? AND id != ?",
                        [$slugInput, $id]
                    );
                    // Wenn belegt: eindeutigen Slug generieren; sonst Wunsch-Slug verwenden
                    $slugRaw = $conflict ? self::generate_unique_slug_admin($rawTitle, $id) : $slugInput;
                }
                // HTML-Beschreibung sichern
                if (class_exists('CMS\\Security') && method_exists(\CMS\Security::instance(), 'sanitizeHtml')) {
                    $descSafe = \CMS\Security::instance()->sanitizeHtml($_POST['description'] ?? '');
                } else {
                    $descSafe = strip_tags(
                        $_POST['description'] ?? '',
                        '<p><br><strong><em><b><i><u><ul><ol><li><a><h1><h2><h3><h4><blockquote><img><table><tr><td><th><thead><tbody><tfoot><span><div>'
                    );
                }
                // Zulässige Status-Werte
                $allowedStatus = ['draft', 'published', 'archived'];
                $statusVal     = sanitize_key($_POST['status'] ?? 'draft');
                if (!in_array($statusVal, $allowedStatus, true)) {
                    $statusVal = 'draft';
                }
                $salaryMin = $_POST['salary_min'] !== '' ? (float)($_POST['salary_min'] ?? 0) : '';
                $salaryMax = $_POST['salary_max'] !== '' ? (float)($_POST['salary_max'] ?? 0) : '';
                $data = [
                    'title'            => $rawTitle,
                    'slug'             => $slugRaw,
                    'company_id'       => (int) ($_POST['company_id'] ?? 0),
                    'job_category_id'  => (int) ($_POST['job_category_id'] ?? 0),
                    'status'           => $statusVal,
                    'summary'          => sanitize_text_field($_POST['summary'] ?? ''),
                    'description'      => $descSafe,
                    'location'         => sanitize_text_field($_POST['location'] ?? ''),
                    'employment_type'  => sanitize_key($_POST['employment_type'] ?? 'fulltime'),
                    'experience_level' => sanitize_key($_POST['experience_level'] ?? 'mid'),
                    'salary_min'       => $salaryMin,
                    'salary_max'       => $salaryMax,
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
