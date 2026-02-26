<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin-Seiten Renderer & POST-Handler
 *
 * Alle 5 Hauptseiten mit ihren je 5 Tabs.
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
class CMS_JPG_Admin_Pages
{
    public const  NONCE_FIELD_PUBLIC = '_jpg_nonce';
    private const NONCE_FIELD        = '_jpg_nonce';

    // ── Sicherheits-Helfer ────────────────────────────────────────────────────

    /** Öffentlicher Wrapper für Views */
    public static function nonce(string $action): string
    {
        return self::generate_nonce($action);
    }

    private static function check_access(): void
    {
        if (!class_exists('CMS\Auth') || !\CMS\Auth::instance()->isAdmin()) {
            wp_die('Zugriff verweigert.', 403);
        }
        // Subscription-basierte Zugriffsrechte
        if (function_exists('user_can_access_plugin')
            && !user_can_access_plugin('cms-jobprofile-generator')) {
            wp_die('Dieses Plugin ist in Ihrem aktuellen Abo nicht verfügbar.', 403);
        }
    }

    /**
     * Gibt HTML für Limit-Warnung aus (Dashboard).
     * @since 0.0.1
     */
    public static function render_limit_warning_public(): void
    {
        if (function_exists('display_resource_limit_warning')) {
            display_resource_limit_warning('job_profiles', 'Job-Profile');
        }
    }

    /**
     * Gibt HTML für Upgrade-Notice aus (Premium-Features).
     * @since 0.0.1
     */
    public static function render_upgrade_notice_public(string $feature, string $message = ''): void
    {
        if (function_exists('user_has_feature') && !user_has_feature($feature)) {
            if (function_exists('display_upgrade_notice')) {
                display_upgrade_notice($message ?: 'Dieses Feature ist in Ihrem aktuellen Abo nicht verfügbar.');
            }
        }
    }

    private static function verify_nonce(string $action): bool
    {
        if (!class_exists('CMS\Security')) {
            return true; // Fallback wenn Core nicht verfügbar
        }
        return \CMS\Security::instance()->verifyNonce(
            $_POST[self::NONCE_FIELD] ?? '',
            $action
        );
    }

    private static function generate_nonce(string $action): string
    {
        if (!class_exists('CMS\Security')) {
            return '';
        }
        return \CMS\Security::instance()->generateToken($action);
    }

    private static function esc(string $value): string
    {
        if (class_exists('CMS\Security')) {
            return \CMS\Security::escape($value);
        }
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // ── URL-Helfer ────────────────────────────────────────────────────────────

    /** Erzeugt die korrekte Plugin-Admin-URL für dieses Plugin */
    public static function page_url(string $page, array $params = []): string
    {
        $url = '/admin/plugins/jpg-dashboard/' . $page;
        foreach ($params as $k => $v) {
            $url .= (str_contains($url, '?') ? '&' : '?') . urlencode($k) . '=' . urlencode((string) $v);
        }
        return $url;
    }

    // ── Redirect-Helfer ───────────────────────────────────────────────────────

    private static function redirect(string $page, array $params = []): void
    {
        wp_redirect(self::page_url($page, $params));
        exit;
    }

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

        include JPG_DIR . 'admin/views/page-dashboard.php';
    }

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

                // Für JS: companies als assoziatives Objekt id→data
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

                // Standard-Benefits der zugewiesenen Firma laden
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
                    'is_private'       => isset($_POST['is_private']) ? 1 : 0, // Phase 9: Privacy-Flag
                    'show_in_listing'   => isset($_POST['show_in_listing']) ? 1 : 0, // Öffentliche Job-Listing-Seite
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

    // ── 3. BIBLIOTHEKEN ──────────────────────────────────────────────────────

    public static function render_libraries(): void
    {
        self::check_access();

        $tab    = sanitize_key($_GET['tab'] ?? 'text-modules');
        $notice = '';
        $error  = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_nonce('jpg_libraries_save')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                [$notice, $error] = self::handle_libraries_post($tab);
            }
        }

        $tabs = [
            'text-modules'      => 'Textbausteine',
            'skill-matrix'      => 'Skill-Matrix',
            'benefit-catalog'   => 'Benefit-Katalog',
            'requirement-items' => 'Anforderungs-Liste',
            'job-categories'    => 'Job-Kategorien',
            'import-export'     => 'Import/Export',
        ];

        // Daten je Tab
        $data = match ($tab) {
            'text-modules'      => ['items'   => CMS_JPG_TextModules::instance()->get_list(['limit' => 50])],
            'skill-matrix'      => ['grouped' => CMS_JPG_SkillMatrix::instance()->get_grouped()],
            'benefit-catalog'   => ['grouped' => CMS_JPG_BenefitsCatalog::instance()->get_grouped(false)],
            'requirement-items' => ['grouped' => class_exists('CMS_JPG_RequirementItems')
                ? CMS_JPG_RequirementItems::instance()->get_grouped()
                : []],
            'job-categories'    => ['items'   => CMS_JPG_JobCategories::instance()->get_all(false)],
            default             => [],
        };

        include JPG_DIR . 'admin/views/page-libraries.php';
    }

    /** @return array{string, string} */
    private static function handle_libraries_post(string $tab): array
    {
        $notice = '';
        $error  = '';
        $id     = (int) ($_POST['id'] ?? 0);
        $del    = (int) ($_POST['delete_id'] ?? 0);

        // Branchenpaket einspielen
        if (($_POST['_jpg_action'] ?? '') === 'seed_industry') {
            $industries = array_values(array_filter(array_map(
                'sanitize_key',
                (array) ($_POST['industries'] ?? [])
            )));
            if (!empty($industries)) {
                $count  = CMS_JPG_Installer::seed_industry_package($industries);
                $notice = "✅ {$count} Einträge für " . count($industries) . ' Branche(n) erfolgreich eingespielt.';
            } else {
                $error = 'Bitte mindestens eine Branche auswählen.';
            }
            return [$notice, $error];
        }

        switch ($tab) {
            case 'text-modules':
                if ($del > 0) {
                    CMS_JPG_TextModules::instance()->delete($del);
                    $notice = 'Textbaustein gelöscht.';
                    break;
                }
                if (empty(trim($_POST['title'] ?? ''))) {
                    $error = 'Titel ist erforderlich.';
                    break;
                }
                CMS_JPG_TextModules::instance()->save([
                    'category' => sanitize_text_field($_POST['category'] ?? 'general'),
                    'title'    => sanitize_text_field($_POST['title'] ?? ''),
                    'content'  => $_POST['content'] ?? '',
                    'tags'     => sanitize_text_field($_POST['tags'] ?? ''),
                ], $id);
                $notice = 'Textbaustein gespeichert.';
                break;

            case 'skill-matrix':
                if ($del > 0) {
                    CMS_JPG_SkillMatrix::instance()->delete($del);
                    $notice = 'Skill gelöscht.';
                    break;
                }
                if (empty(trim($_POST['skill_name'] ?? ''))) {
                    $error = 'Skill-Name ist erforderlich.';
                    break;
                }
                CMS_JPG_SkillMatrix::instance()->save([
                    'group_name'  => sanitize_text_field($_POST['group_name'] ?? ''),
                    'skill_name'  => sanitize_text_field($_POST['skill_name'] ?? ''),
                    'description' => sanitize_text_field($_POST['description'] ?? ''),
                    'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
                ], $id);
                $notice = 'Skill gespeichert.';
                break;

            case 'benefit-catalog':
                if ($del > 0) {
                    CMS_JPG_BenefitsCatalog::instance()->delete($del);
                    $notice = 'Benefit gelöscht.';
                    break;
                }
                if (empty(trim($_POST['title'] ?? ''))) {
                    $error = 'Titel ist erforderlich.';
                    break;
                }
                CMS_JPG_BenefitsCatalog::instance()->save([
                    'group_name'  => sanitize_text_field($_POST['group_name'] ?? ''),
                    'title'       => sanitize_text_field($_POST['title'] ?? ''),
                    'description' => sanitize_text_field($_POST['description'] ?? ''),
                    'icon'        => sanitize_text_field($_POST['icon'] ?? '✓'),
                    'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
                    'active'      => isset($_POST['active']) ? 1 : 0,
                ], $id);
                $notice = 'Benefit gespeichert.';
                break;

            case 'requirement-items':
                if ($del > 0) {
                    if (class_exists('CMS_JPG_RequirementItems')) {
                        CMS_JPG_RequirementItems::instance()->delete($del);
                    }
                    $notice = 'Eintrag gelöscht.';
                    break;
                }
                if (empty(trim($_POST['title'] ?? ''))) {
                    $error = 'Titel ist erforderlich.';
                    break;
                }
                if (class_exists('CMS_JPG_RequirementItems')) {
                    $reqType = sanitize_key($_POST['req_type'] ?? 'must');
                    $reqType = in_array($reqType, ['must','nice','optional'], true) ? $reqType : 'must';
                    CMS_JPG_RequirementItems::instance()->save([
                        'group_name'  => sanitize_text_field($_POST['group_name']  ?? ''),
                        'title'       => sanitize_text_field($_POST['title']       ?? ''),
                        'req_type'    => $reqType,
                        'description' => sanitize_text_field($_POST['description'] ?? ''),
                        'icon'        => sanitize_text_field($_POST['icon']        ?? ''),
                        'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
                    ], $id);
                }
                $notice = 'Eintrag gespeichert.';
                break;

            case 'job-categories':
                if ($del > 0) {
                    CMS_JPG_JobCategories::instance()->delete($del);
                    $notice = 'Kategorie gelöscht.';
                    break;
                }
                if (empty(trim($_POST['name'] ?? ''))) {
                    $error = 'Name ist erforderlich.';
                    break;
                }
                CMS_JPG_JobCategories::instance()->save([
                    'name'        => sanitize_text_field($_POST['name'] ?? ''),
                    'description' => sanitize_text_field($_POST['description'] ?? ''),
                    'color'       => sanitize_text_field($_POST['color'] ?? '#3b82f6'),
                    'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
                    'active'      => isset($_POST['active']) ? 1 : 0,
                ], $id);
                $notice = 'Kategorie gespeichert.';
                break;

            case 'import-export':
                if (!empty($_POST['import_json']) && !empty($_FILES['import_file']['tmp_name'])) {
                    $json  = file_get_contents($_FILES['import_file']['tmp_name']);
                    $auth  = \CMS\Auth::instance();
                    $uId   = method_exists($auth, 'getUserId') ? (int) $auth->getUserId() : 0;
                    $newId = CMS_JPG_Export::instance()->import_json($json, $uId);
                    if ($newId > 0) {
                        $notice = "Profil importiert (ID: {$newId}).";
                    } else {
                        $error = 'Import fehlgeschlagen – ungültiges JSON.';
                    }
                }
                break;
        }

        return [$notice, $error];
    }

    // ── 4. VORLAGEN & DESIGN ─────────────────────────────────────────────────

    public static function render_design(): void
    {
        self::check_access();

        $tab    = sanitize_key($_GET['tab'] ?? 'pdf-templates');
        $notice = '';
        $error  = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_nonce('jpg_design_save')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                [$notice, $error] = self::handle_design_post($tab);
            }
        }

        $tabs = [
            'pdf-templates'  => 'PDF-Templates',
            'web-templates'  => 'Web-Templates',
            'corporate'      => 'Corporate Design',
            'typography'     => 'Typografie',
            'email-templates'=> 'E-Mail-Templates',
        ];

        $db = \CMS\Database::instance();
        $p  = $db->getPrefix();

        $type = match ($tab) {
            'pdf-templates'   => 'pdf',
            'web-templates'   => 'web',
            'email-templates' => 'email',
            default           => null,
        };

        $templates = $type
            ? $db->get_results(
                "SELECT * FROM {$p}jpg_templates WHERE type = ? ORDER BY is_default DESC, name ASC",
                [$type]
            )
            : [];

        $cd_settings = self::get_cd_settings();

        include JPG_DIR . 'admin/views/page-design.php';
    }

    /** @return array{string, string} */
    private static function handle_design_post(string $tab): array
    {
        $notice = '';
        $error  = '';
        $db     = \CMS\Database::instance();
        $p      = $db->getPrefix();
        $id     = (int) ($_POST['template_id'] ?? 0);
        $del    = (int) ($_POST['delete_id'] ?? 0);

        if (in_array($tab, ['pdf-templates', 'web-templates', 'email-templates'])) {
            if ($del > 0) {
                $db->delete('jpg_templates', ['id' => $del]);
                $notice = 'Template gelöscht.';
                return [$notice, $error];
            }
            $type = match ($tab) {
                'pdf-templates'   => 'pdf',
                'web-templates'   => 'web',
                'email-templates' => 'email',
                default           => 'web',
            };
            if (empty(trim($_POST['name'] ?? ''))) {
                $error = 'Template-Name ist erforderlich.';
                return [$notice, $error];
            }
            $fields = [
                'type'       => $type,
                'name'       => sanitize_text_field($_POST['name'] ?? ''),
                'content'    => $_POST['content'] ?? '',
                'css'        => $_POST['css'] ?? '',
                'is_default' => isset($_POST['is_default']) ? 1 : 0,
                'active'     => 1,
            ];
            if ($id > 0) {
                $db->update('jpg_templates', $fields, ['id' => $id]);
            } else {
                $db->insert('jpg_templates', $fields);
            }
            $notice = 'Template gespeichert.';
        } elseif ($tab === 'corporate') {
            self::save_cd_settings([
                'primary_color'     => sanitize_text_field($_POST['primary_color'] ?? '#3b82f6'),
                'secondary_color'   => sanitize_text_field($_POST['secondary_color'] ?? '#1e293b'),
                'font_body'         => sanitize_text_field($_POST['font_body'] ?? 'Inter, sans-serif'),
                'font_heading'      => sanitize_text_field($_POST['font_heading'] ?? 'Inter, sans-serif'),
                'logo_url'          => sanitize_text_field($_POST['logo_url'] ?? ''),
                'company_name'      => sanitize_text_field($_POST['company_name'] ?? ''),
                'company_tagline'   => sanitize_text_field($_POST['company_tagline'] ?? ''),
                'footer_text'       => sanitize_text_field($_POST['footer_text'] ?? ''),
            ]);
            $notice = 'Corporate Design gespeichert.';
        }

        return [$notice, $error];
    }

    /** @return array<string, string> */
    private static function get_cd_settings(): array
    {
        $db  = \CMS\Database::instance();
        $p   = $db->getPrefix();
        $rows = $db->get_results(
            "SELECT setting_key, setting_value FROM {$p}jpg_settings WHERE setting_key LIKE 'cd_%'",
            []
        );
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }
        return $settings;
    }

    /** @param array<string, string> $data */
    private static function save_cd_settings(array $data): void
    {
        $db  = \CMS\Database::instance();
        $p   = $db->getPrefix();
        $pdo = $db->getPdo();

        foreach ($data as $key => $value) {
            $key = 'cd_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($key));
            $pdo->exec(
                "INSERT INTO {$p}jpg_settings (setting_key, setting_value)
                 VALUES ('{$key}', " . $pdo->quote((string) $value) . ")
                 ON DUPLICATE KEY UPDATE setting_value = " . $pdo->quote((string) $value)
            );
        }
    }

    // ── 5. EINSTELLUNGEN ─────────────────────────────────────────────────────

    public static function render_settings(): void
    {
        self::check_access();

        $tab    = sanitize_key($_GET['tab'] ?? 'general');
        $notice = '';
        $error  = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_nonce('jpg_settings_save')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                [$notice, $error] = self::handle_settings_post($tab);
            }
        }

        $tabs = [
            'general'       => 'Allgemein',
            'permissions'   => 'Berechtigungen',
            'workflow'      => 'Workflow',
            'notifications' => 'Benachrichtigungen',
            'system'        => 'System-Info',
            'audit-log'     => '🔍 Audit-Log', // Phase 9: DSGVO-Zugriffs-Log
            'health'        => '🏥 System-Health', // Phase 14.1: Health-Monitor
        ];

        $settings = self::get_all_plugin_settings();

        // Phase 9: Audit-Log-Daten laden
        $auditLog      = [];
        $filterCompany = 0;
        $filterOptions = [];
        if ($tab === 'audit-log' && class_exists('CMS_JPG_Workflow')) {
            $filterCompany = (int) ($_GET['filter_company'] ?? 0);
            $auditLog      = CMS_JPG_Workflow::get_audit_log(100, $filterCompany);
            // Firmen-Dropdown
            $db2 = \CMS\Database::instance();
            $p2  = $db2->getPrefix();
            $filterOptions = $db2->get_results(
                "SELECT DISTINCT c.id, c.name AS company_name
                 FROM {$p2}jpg_admin_access_log l
                 JOIN {$p2}companies c ON c.id = l.target_company_id
                 ORDER BY c.name ASC LIMIT 200",
                []
            );
        }

        // Phase 14.1: Health-Monitor Daten laden
        $healthData = [];
        if ($tab === 'health') {
            $healthData = self::get_health_data();
        }

        // Phase 14.1: POST-Handler für Health-Aktionen (Bereinigung)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'health') {
            if (self::verify_nonce('jpg_settings_save')) {
                $healthAction = sanitize_key($_POST['health_action'] ?? '');
                if ($healthAction === 'cleanup_orphans') {
                    [$notice, $error] = self::cleanup_orphan_files();
                } elseif ($healthAction === 'cleanup_expired') {
                    $days = max(30, (int)($_POST['retention_days'] ?? 90));
                    [$notice, $error] = self::cleanup_expired_applications($days);
                }
                $healthData = self::get_health_data();
            } else {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            }
        }

        include JPG_DIR . 'admin/views/page-settings.php';
    }

    /** @return array{string, string} */
    private static function handle_settings_post(string $tab): array
    {
        $notice = '';
        $error  = '';
        $db     = \CMS\Database::instance();
        $p      = $db->getPrefix();
        $pdo    = $db->getPdo();

        $allowed = [
            'general'       => ['default_status', 'profiles_per_page', 'slug_prefix'],
            'permissions'   => ['role_create', 'role_edit', 'role_delete', 'role_publish'],
            'workflow'      => ['review_required', 'approval_required', 'notify_on_publish'],
            'notifications' => ['notify_email', 'notify_on_create', 'notify_on_delete'],
        ];

        $keys = $allowed[$tab] ?? [];
        foreach ($keys as $key) {
            $value = sanitize_text_field($_POST[$key] ?? '');
            $sKey  = 'jpg_' . $key;
            $pdo->exec(
                "INSERT INTO {$p}jpg_settings (setting_key, setting_value)
                 VALUES ('{$sKey}', " . $pdo->quote($value) . ")
                 ON DUPLICATE KEY UPDATE setting_value = " . $pdo->quote($value)
            );
        }

        $notice = 'Einstellungen gespeichert.';
        return [$notice, $error];
    }

    /** @return array<string, string> */
    private static function get_all_plugin_settings(): array
    {
        $db   = \CMS\Database::instance();
        $p    = $db->getPrefix();
        $rows = $db->get_results(
            "SELECT setting_key, setting_value FROM {$p}jpg_settings",
            []
        );
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row->setting_key] = (string) $row->setting_value;
        }
        return $settings;
    }

    // ── Phase 14.1: Health-Monitor ────────────────────────────────────────────

    /**
     * Sammelt Health-Daten: verwaiste Dateien + abgelaufene Bewerbungen.
     * @return array<string, mixed>
     */
    private static function get_health_data(): array
    {
        $db      = \CMS\Database::instance();
        $p       = $db->getPrefix();
        $uploads = defined('UPLOADS_PATH') ? UPLOADS_PATH . 'jpg/' : '';

        // Verwaiste CV-Dateien
        $orphanFiles = [];
        if ($uploads !== '' && is_dir($uploads)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($uploads, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($file->isFile() && in_array(strtolower($file->getExtension()), ['pdf','doc','docx'], true)) {
                    $token = $file->getBasename('.' . $file->getExtension());
                    try {
                        $exists = $db->get_var(
                            "SELECT id FROM {$p}jpg_applications WHERE cv_file_token = ?", [$token]
                        );
                    } catch (\Throwable $e) {
                        $exists = true; // Im Zweifel nicht als verwaist markieren
                    }
                    if (!$exists) {
                        $orphanFiles[] = [
                            'path' => $file->getPathname(),
                            'size' => $file->getSize(),
                        ];
                    }
                }
            }
        }

        // Abgelaufene Bewerbungen (älter als 90 Tage)
        $expiredCount = 0;
        try {
            $expiredCount = (int)$db->get_var(
                "SELECT COUNT(*) FROM {$p}jpg_applications
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
                   AND status IN ('rejected', 'accepted')",
                []
            );
        } catch (\Throwable $e) { /* ignore */ }

        // Gesamtzahl Bewerbungen
        $totalApps = 0;
        try {
            $totalApps = (int)$db->get_var("SELECT COUNT(*) FROM {$p}jpg_applications", []);
        } catch (\Throwable $e) { /* ignore */ }

        // DB-Tabellen-Größen
        $tableSizes = [];
        try {
            $rows = $db->get_results(
                "SELECT table_name AS tbl, ROUND((data_length + index_length) / 1024, 1) AS kb
                 FROM information_schema.TABLES
                 WHERE table_schema = DATABASE() AND table_name LIKE ?
                 ORDER BY kb DESC",
                [$p . 'jpg_%']
            );
            foreach ($rows as $r) {
                $tableSizes[$r->tbl] = (float)$r->kb;
            }
        } catch (\Throwable $e) { /* ignore */ }

        return compact('orphanFiles', 'expiredCount', 'totalApps', 'tableSizes', 'uploads');
    }

    /**
     * Löscht verwaiste CV-Dateien ohne DB-Eintrag.
     * @return array{string, string}
     */
    private static function cleanup_orphan_files(): array
    {
        $data    = self::get_health_data();
        $deleted = 0;
        foreach ($data['orphanFiles'] ?? [] as $f) {
            if (is_file($f['path']) && @unlink($f['path'])) {
                $deleted++;
            }
        }
        return ["✅ {$deleted} verwaiste Datei(en) gelöscht.", ''];
    }

    /**
     * Löscht abgelaufene Bewerber-Daten (DSGVO-Bereinigung).
     * @return array{string, string}
     */
    private static function cleanup_expired_applications(int $days): array
    {
        $db = \CMS\Database::instance();
        $p  = $db->getPrefix();
        try {
            // Vor dem Löschen: CV-Dateien entfernen
            $expired = $db->get_results(
                "SELECT cv_file_path FROM {$p}jpg_applications
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                   AND status IN ('rejected', 'accepted')
                   AND cv_file_path IS NOT NULL",
                [$days]
            ) ?: [];
            foreach ($expired as $row) {
                if (!empty($row->cv_file_path) && is_file($row->cv_file_path)) {
                    @unlink($row->cv_file_path);
                }
            }
            $db->query(
                "DELETE FROM {$p}jpg_applications
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                   AND status IN ('rejected', 'accepted')",
                [$days]
            );
            $count = count($expired);
            return ["✅ {$count} abgelaufene Bewerbung(en) nach {$days} Tagen gelöscht (DSGVO).", ''];
        } catch (\Throwable $e) {
            return ['', 'Fehler: ' . $e->getMessage()];
        }
    }

    // ── 6. WORKFLOW-EDITOR ────────────────────────────────────────────────────

    public static function render_workflow(): void
    {
        self::check_access();

        $notice = '';
        $error  = '';

        // POST-Handler
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_nonce('jpg_workflow_save')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                [$notice, $error] = self::handle_workflow_post();
            }
        }

        // Alle Workflow-Schritte laden
        $steps    = class_exists('CMS_JPG_Workflow') ? CMS_JPG_Workflow::instance()->get_all_steps() : [];
        $allRoles = class_exists('CMS_JPG_Workflow') ? CMS_JPG_Workflow::get_all_cms_roles() : ['admin' => 'Admin'];

        include JPG_DIR . 'admin/views/page-workflow-editor.php';
    }

    /** @return array{string, string} [notice, error] */
    private static function handle_workflow_post(): array
    {
        $notice = '';
        $error  = '';
        $action = sanitize_key($_POST['_wf_action'] ?? '');

        switch ($action) {
            case 'save_step':
                $stepId = (int) ($_POST['step_id'] ?? 0);
                $data   = [
                    'step_name'          => sanitize_text_field($_POST['step_name'] ?? ''),
                    'approver_role'      => sanitize_key($_POST['approver_role'] ?? 'admin'),
                    'allow_self_approve' => isset($_POST['allow_self_approve']) ? 1 : 0,
                    'notification_email' => filter_var($_POST['notification_email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '',
                    'sort_order'         => (int) ($_POST['sort_order'] ?? 10),
                    'active'             => isset($_POST['active']) ? 1 : 0,
                ];
                if (empty($data['step_name'])) {
                    $error = 'Stufenname ist erforderlich.';
                    break;
                }
                $id = CMS_JPG_Workflow::instance()->save_step($data, $stepId);
                $notice = $stepId > 0 ? 'Schritt aktualisiert.' : 'Neuer Schritt angelegt.';
                break;

            case 'delete_step':
                $stepId = (int) ($_POST['step_id'] ?? 0);
                if ($stepId > 0) {
                    CMS_JPG_Workflow::instance()->delete_step($stepId);
                    $notice = 'Schritt gelöscht.';
                }
                break;

        }

        return [$notice, $error];
    }

    // ── 7. GENEHMIGUNGEN ─────────────────────────────────────────────────────

    public static function render_approvals(): void
    {
        self::check_access();

        $notice = '';
        $error  = '';

        // POST-Handler (Approve / Reject / Reset)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_nonce('jpg_workflow_save')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                [$notice, $error] = self::handle_approvals_post();
            }
        }

        $pendingProfiles = class_exists('CMS_JPG_Workflow') ? CMS_JPG_Workflow::instance()->get_all_pending() : [];
        $allRoles        = class_exists('CMS_JPG_Workflow') ? CMS_JPG_Workflow::get_all_cms_roles() : ['admin' => 'Admin'];
        $wfSteps         = class_exists('CMS_JPG_Workflow') ? CMS_JPG_Workflow::instance()->get_steps() : [];

        include JPG_DIR . 'admin/views/page-approvals.php';
    }

    /** @return array{string, string} [notice, error] */
    private static function handle_approvals_post(): array
    {
        $notice = '';
        $error  = '';
        $action = sanitize_key($_POST['_wf_action'] ?? '');
        $auth   = \CMS\Auth::instance();
        $actorId = method_exists($auth, 'getUserId') ? (int) $auth->getUserId() : 0;

        switch ($action) {
            case 'approve_profile':
                $profileId = (int) ($_POST['profile_id'] ?? 0);
                $note      = sanitize_text_field($_POST['note'] ?? '');
                if ($profileId > 0) {
                    $ok = CMS_JPG_Workflow::instance()->approve_step($profileId, $actorId, $note);
                    $notice = $ok ? 'Profil genehmigt / weitergeführt.' : 'Fehler beim Genehmigen.';
                }
                break;

            case 'reject_profile':
                $profileId = (int) ($_POST['profile_id'] ?? 0);
                $note      = sanitize_text_field($_POST['note'] ?? '');
                if ($profileId > 0) {
                    $ok = CMS_JPG_Workflow::instance()->reject_step($profileId, $actorId, $note);
                    $notice = $ok ? 'Profil abgelehnt.' : 'Fehler beim Ablehnen.';
                }
                break;

            case 'reset_profile':
                $profileId = (int) ($_POST['profile_id'] ?? 0);
                if ($profileId > 0) {
                    CMS_JPG_Workflow::instance()->reset_workflow($profileId, $actorId);
                    $notice = 'Profil zurückgesetzt auf Entwurf.';
                }
                break;
        }

        return [$notice, $error];
    }

    // ── AJAX: HTML-Preview ────────────────────────────────────────────────────

    public static function ajax_preview(): void
    {
        // Alle bisher gepufferten Theme-/Page-Ausgaben verwerfen (ob_start in Bootstrap).
        // Ohne das schlägt http_response_code() / header() fehl ("headers already sent").
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        self::check_access();
        // Das Preview-Formular nutzt denselben Nonce wie der Generator-Speichern-Button.
        if (!self::verify_nonce('jpg_generator_save')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Sicherheitscheck fehlgeschlagen.']);
            exit;
        }

        $id = (int) ($_POST['profile_id'] ?? 0);
        if ($id <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Ungültige ID.']);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'html' => CMS_JPG_Export::instance()->render_html($id),
        ]);
        exit;
    }

    // ── AJAX: JSON-Export ─────────────────────────────────────────────────────

    public static function ajax_export_json(): void
    {
        // Alle bisher gepufferten Theme-/Page-Ausgaben verwerfen (ob_start in Bootstrap).
        // Ohne das schlägt http_response_code() / header() fehl ("headers already sent").
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        self::check_access();

        // Nonce aus GET (Download-Link) oder POST (AJAX) prüfen
        $nonceValue = $_GET[self::NONCE_FIELD] ?? $_POST[self::NONCE_FIELD] ?? '';
        $nonceOk    = false;
        if (!empty($nonceValue) && class_exists('CMS\Security')) {
            $nonceOk = \CMS\Security::instance()->verifyNonce($nonceValue, 'jpg_export')
                    || \CMS\Security::instance()->verifyNonce($nonceValue, 'jpg_libraries_save')
                    || \CMS\Security::instance()->verifyNonce($nonceValue, 'jpg_generator_save');
        } elseif (empty($nonceValue) && !class_exists('CMS\Security')) {
            $nonceOk = true; // Fallback: kein Security-Core verfügbar
        }
        if (!$nonceOk) {
            http_response_code(403);
            exit;
        }

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            wp_die('Ungültige ID.', 400);
        }

        $json    = CMS_JPG_Export::instance()->export_json($id);
        $profile = CMS_JPG_Profiles::instance()->get($id);
        $slug    = $profile ? $profile->slug : 'profil-' . $id;

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $slug . '.json"');
        echo $json;
        exit;
    }

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
                        CMS_JPG_Departments::instance()->save_company_settings($cid, [
                            'jobs_page_url'           => $_POST['jobs_page_url']           ?? '',
                            'jobs_page_title'         => $_POST['jobs_page_title']         ?? '',
                            'jobs_page_intro'         => $_POST['jobs_page_intro']         ?? '',
                            'jobs_page_contact_email' => $_POST['jobs_page_contact_email'] ?? '',
                            'jobs_page_show_salary'   => isset($_POST['jobs_page_show_salary'])  ? 1 : 0,
                            'jobs_page_enabled'       => isset($_POST['jobs_page_enabled'])      ? 1 : 0,
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

        // Unternehmen laden (cms-companies)
        $companies  = [];
        $linkedUser = null; // Verknüpfter CMS-User der ausgewählten Firma
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

        // Benefit-Katalog
        $allBenefits = CMS_JPG_BenefitsCatalog::instance()->get_grouped();

        // Bestehende Zuweisungen laden (company_id → [benefit_ids])
        $assignmentsMap = [];
        // Job-Profile-Anzahl je Firma (company_id → ['published'=>n, 'total'=>n])
        $jobCountsMap = [];
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
            // Job-Profile-Statistiken pro Firma
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
        } catch (\Throwable $e) {
            // Tabelle noch nicht vorhanden – kein Fehler
        }

        $selectedCompanyId = (int) ($_GET['company'] ?? 0);

        // Verknüpften CMS-User für die ausgewählte Firma laden
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
                    } catch (\Throwable $ex) { /* Tabellen-Struktur kann variieren */ }
                    break;
                }
            }
        }

        // Phase 9: Admin-Zugriffs-Log – schreibe Eintrag wenn Firma eingesehen wird (DSGVO)
        if ($selectedCompanyId > 0 && class_exists('CMS_JPG_Workflow')) {
            $adminId = method_exists(\CMS\Auth::instance(), 'getUserId')
                ? (int) \CMS\Auth::instance()->getUserId() : 0;
            if ($adminId > 0) {
                CMS_JPG_Workflow::log_admin_access($adminId, $selectedCompanyId, 0, 'view_company');
            }
        }

        // Departments + Jobs-Settings für gewählte Firma
        $departments       = [];
        $allDeptBenefitIds = [];   // [dept_id => [benefit_id, ...]]
        $allDeptReqIds     = [];   // [dept_id => [req_item_id, ...]]
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

        $name         = sanitize_text_field($_POST['company_name']    ?? '');
        $email        = sanitize_text_field($_POST['company_email']   ?? '');
        $phone        = sanitize_text_field($_POST['company_phone']   ?? '');
        $website      = filter_var(trim($_POST['company_website']     ?? ''), FILTER_VALIDATE_URL) ?: '';
        $logoUrl      = filter_var(trim($_POST['company_logo_url']    ?? ''), FILTER_VALIDATE_URL) ?: '';
        $industry     = sanitize_text_field($_POST['company_industry']     ?? '');
        $companySize  = sanitize_text_field($_POST['company_size']         ?? '');
        $description  = strip_tags($_POST['company_description'] ?? '');
        $city         = sanitize_text_field($_POST['company_city']         ?? '');
        $zip          = sanitize_text_field($_POST['company_zip']          ?? '');
        $country      = sanitize_text_field($_POST['company_country']      ?? '');
        $foundedYear  = (int) ($_POST['company_founded_year']              ?? 0);
        $employeeCount= (int) ($_POST['company_employee_count']            ?? 0);

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
        $companyId = (int) ($_POST['company_id'] ?? 0);
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

        $rawIds  = $_POST['benefit_ids'] ?? [];
        $ids     = is_array($rawIds) ? array_map('intval', $rawIds) : [];

        try {
            $db  = \CMS\Database::instance();
            $p   = $db->getPrefix();
            $pdo = $db->getPdo();

            // Alle bisherigen Einträge dieser Firma löschen
            $pdo->prepare("DELETE FROM {$p}jpg_company_default_benefits WHERE company_id = ?")->execute([$companyId]);

            // Neue Benefits in grossblöcken einfügen
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

    // ─────────────────────────────────────────────────────────────────────────
    // ── Abosystem & Pakete ────────────────────────────────────────────────────
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Seite: Abosystem – Plugin-spezifische Limits & Features je Abo-Paket.
     */
    public static function render_subscription(): void
    {
        self::check_access();

        $db     = \CMS\Database::instance();
        $p      = $db->getPrefix();
        $nonce  = self::nonce('jpg_subscription_save');
        $notice = '';
        $error  = '';

        // ── Plugin-Rollen: 5 Standard-Definitionen ───────────────────────
        $pluginRoles = self::get_plugin_roles();

        // ── POST-Handler ─────────────────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sub_action'])) {
            if (!self::verify_nonce('jpg_subscription_save')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $action = sanitize_key($_POST['sub_action'] ?? '');

                if ($action === 'save_plan_limits') {
                    // Plugin-spezifische Limits per Plan speichern
                    $planId = (int) ($_POST['plan_id'] ?? 0);
                    if ($planId > 0) {
                        $limits = [
                            'max_profiles'        => (int) ($_POST['max_profiles'] ?? -1),
                            'max_active_profiles' => (int) ($_POST['max_active_profiles'] ?? -1),
                            'feature_workflow'    => !empty($_POST['feature_workflow']) ? 1 : 0,
                            'feature_export'      => !empty($_POST['feature_export']) ? 1 : 0,
                            'feature_analytics'   => !empty($_POST['feature_analytics']) ? 1 : 0,
                            'feature_branding'    => !empty($_POST['feature_branding']) ? 1 : 0,
                            'feature_api'         => !empty($_POST['feature_api']) ? 1 : 0,
                        ];
                        $all = self::get_plan_limits_all();
                        $all[$planId] = $limits;
                        self::save_jpg_setting('jpg_plan_limits', json_encode($all), $db, $p);
                        $notice = 'Plugin-Limits für Paket gespeichert.';
                    }
                } elseif ($action === 'save_role') {
                    // Plugin-Rolle bearbeiten (Beschreibung/Capabilities)
                    $roleKey = sanitize_key($_POST['role_key'] ?? '');
                    $desc    = strip_tags($_POST['role_desc'] ?? '');
                    $custom  = self::get_custom_role_descriptions();
                    if ($roleKey && array_key_exists($roleKey, $pluginRoles)) {
                        $custom[$roleKey] = $desc;
                        self::save_jpg_setting('jpg_role_descriptions', json_encode($custom), $db, $p);
                        $notice = 'Rollenbeschreibung aktualisiert.';
                    }
                }
            }
        }

        // ── CMS-Abopakete laden ──────────────────────────────────────────
        $plans = [];
        try {
            $plans = $db->get_results(
                "SELECT * FROM {$p}subscription_plans ORDER BY sort_order ASC, price_monthly ASC",
                []
            ) ?: [];
        } catch (\Throwable $e) { /* Tabelle existiert noch nicht */ }

        // ── Plugin-Limits laden ──────────────────────────────────────────
        $planLimitsAll = self::get_plan_limits_all();

        // ── CMS User-Rollen laden ────────────────────────────────────────
        $cmsRoles = [];
        try {
            $cmsRoles = $db->get_results(
                "SELECT DISTINCT role FROM {$p}users WHERE role IS NOT NULL AND role != '' ORDER BY role ASC",
                []
            ) ?: [];
        } catch (\Throwable $e) { /* ignore */ }

        $customRoleDescs = self::get_custom_role_descriptions();
        // Plugin-Rollen mit benutzerdefinierten Beschreibungen anreichern
        foreach ($pluginRoles as $key => &$roleData) {
            if (isset($customRoleDescs[$key])) {
                $roleData['description'] = $customRoleDescs[$key];
            }
        }
        unset($roleData);

        require JPG_DIR . 'admin/views/page-subscription.php';
    }

    /**
     * Liefert die 5 Standard-Plugin-Rollen-Definitionen.
     */
    public static function get_plugin_roles(): array
    {
        return [
            'mandant' => [
                'label'       => 'Mandant',
                'icon'        => '🏢',
                'color'       => 'member',
                'description' => 'Unternehmen-Zugang: kann eigene Stellen erstellen, bearbeiten und Bewerbungen verwalten.',
                'caps'        => ['create_profiles', 'edit_own_profiles', 'view_applications', 'manage_company'],
            ],
            'editor' => [
                'label'       => 'Redakteur',
                'icon'        => '✏️',
                'color'       => 'info',
                'description' => 'Kann Stellen erstellen und bearbeiten, aber nicht veröffentlichen oder löschen.',
                'caps'        => ['create_profiles', 'edit_own_profiles'],
            ],
            'viewer' => [
                'label'       => 'Betrachter',
                'icon'        => '👁️',
                'color'       => 'inactive',
                'description' => 'Nur-Lese-Zugriff auf eigene Profile und Bewerbungen.',
                'caps'        => ['view_own_profiles', 'view_applications'],
            ],
            'admin' => [
                'label'       => 'Plugin-Admin',
                'icon'        => '🔑',
                'color'       => 'admin',
                'description' => 'Vollzugriff auf alle Plugin-Bereiche inkl. Admin-Dashboard.',
                'caps'        => ['create_profiles', 'edit_all_profiles', 'delete_profiles', 'view_all_applications', 'manage_company', 'plugin_admin'],
            ],
            'blocked' => [
                'label'       => 'Gesperrt',
                'icon'        => '🚫',
                'color'       => 'danger',
                'description' => 'Kein Zugriff auf Plugin-Bereiche. Für temporäre oder dauerhafte Sperrung.',
                'caps'        => [],
            ],
        ];
    }

    /**
     * Lädt alle Plan-Limits aus den Plugin-Settings.
     */
    private static function get_plan_limits_all(): array
    {
        try {
            $db  = \CMS\Database::instance();
            $p   = $db->getPrefix();
            $raw = $db->get_var(
                "SELECT setting_value FROM {$p}jpg_settings WHERE setting_key = 'jpg_plan_limits' LIMIT 1",
                []
            );
            $decoded = json_decode((string)($raw ?? '{}'), true);
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Lädt benutzerdefinierte Rollenbeschreibungen.
     */
    private static function get_custom_role_descriptions(): array
    {
        try {
            $db  = \CMS\Database::instance();
            $p   = $db->getPrefix();
            $raw = $db->get_var(
                "SELECT setting_value FROM {$p}jpg_settings WHERE setting_key = 'jpg_role_descriptions' LIMIT 1",
                []
            );
            $decoded = json_decode((string)($raw ?? '{}'), true);
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Speichert einen Wert in jpg_settings (INSERT … ON DUPLICATE KEY UPDATE).
     */
    private static function save_jpg_setting(string $key, string $value, \CMS\Database $db, string $p): void
    {
        try {
            $db->execute(
                "INSERT INTO {$p}jpg_settings (setting_key, setting_value)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                [$key, $value]
            );
        } catch (\Throwable $e) {
            error_log('CMS_JPG save_jpg_setting error: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ── Benutzer & Mandanten-Verwaltung ──────────────────────────────────────
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Seite: Benutzer & Mandanten-Verwaltung.
     */
    public static function render_users(): void
    {
        self::check_access();

        $db    = \CMS\Database::instance();
        $p     = $db->getPrefix();
        $nonce = self::nonce('jpg_users_save');
        $notice = '';
        $error  = '';

        // ── POST-Handler ──────────────────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['users_action'])) {
            if (!self::verify_nonce('jpg_users_save')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $action = sanitize_key($_POST['users_action'] ?? '');
                $uid    = (int) ($_POST['target_user_id'] ?? 0);

                if ($uid > 0) {
                    switch ($action) {
                        case 'set_role':
                            $role = sanitize_key($_POST['jpg_role'] ?? '');
                            $allowed = ['mandant', 'editor', 'viewer', 'admin', 'blocked', ''];
                            if (in_array($role, $allowed, true)) {
                                self::set_user_meta($uid, 'jpg_plugin_role', $role);
                                $notice = 'Rolle gespeichert.';
                            }
                            break;
                        case 'assign_company':
                            $companyId = (int) ($_POST['company_id'] ?? 0);
                            if ($companyId > 0) {
                                try {
                                    $db->execute(
                                        "UPDATE {$p}companies SET user_id = ? WHERE id = ?",
                                        [$uid, $companyId]
                                    );
                                    $notice = 'Unternehmen zugewiesen.';
                                } catch (\Throwable $e) {
                                    $error = 'Fehler: ' . $e->getMessage();
                                }
                            }
                            break;
                        case 'create_company':
                            $companyName = sanitize_text_field($_POST['new_company_name'] ?? '');
                            if ($companyName !== '') {
                                try {
                                    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $companyName));
                                    $db->execute(
                                        "INSERT INTO {$p}companies (user_id, name, slug, status, created_at)
                                         VALUES (?, ?, ?, 'active', NOW())
                                         ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)",
                                        [$uid, $companyName, $slug]
                                    );
                                    $notice = 'Unternehmen angelegt und zugewiesen.';
                                } catch (\Throwable $e) {
                                    $error = 'Fehler: ' . $e->getMessage();
                                }
                            }
                            break;
                    }
                }
            }
        }

        // ── Daten laden ───────────────────────────────────────────────────
        // Alle CMS-Benutzer
        $users = [];
        try {
            $users = $db->get_results(
                "SELECT u.id, u.username, u.email, u.display_name, u.role, u.created_at,
                        c.id AS company_id, c.name AS company_name,
                        (SELECT COUNT(*) FROM {$p}jpg_profiles jp WHERE jp.created_by = u.id) AS job_count
                 FROM {$p}users u
                 LEFT JOIN {$p}companies c ON c.user_id = u.id
                 ORDER BY u.created_at DESC",
                []
            ) ?: [];
        } catch (\Throwable $e) {
            $error .= ' Benutzer-Abfrage: ' . $e->getMessage();
        }

        // Plugin-Rollen aus user_meta laden
        $userMeta = [];
        if (!empty($users)) {
            try {
                $ids    = implode(',', array_map(fn($u) => (int)$u->id, $users));
                $metas  = $db->get_results(
                    "SELECT user_id, meta_key, meta_value FROM {$p}user_meta
                     WHERE user_id IN ({$ids}) AND meta_key = 'jpg_plugin_role'",
                    []
                ) ?: [];
                foreach ($metas as $m) {
                    $userMeta[(int)$m->user_id] = $m->meta_value;
                }
            } catch (\Throwable $e) { /* user_meta optional */ }
        }

        // Alle Firmen ohne Benutzer-Zuweisung (für Zuweisung verfügbar)
        $unassignedCompanies = [];
        try {
            $unassignedCompanies = $db->get_results(
                "SELECT id, name FROM {$p}companies
                 WHERE user_id IS NULL OR user_id = 0
                 ORDER BY name ASC",
                []
            ) ?: [];
        } catch (\Throwable $e) { /* ignore */ }

        // Plugin-Rollen-Definitionen für die View
        $pluginRoles = self::get_plugin_roles();

        // CMS-Rollen aus users-Tabelle laden (für Info-Sektion)
        $cmsRoles = [];
        try {
            $cmsRoles = $db->get_results(
                "SELECT DISTINCT role FROM {$p}users WHERE role IS NOT NULL AND role != '' ORDER BY role ASC",
                []
            ) ?: [];
        } catch (\Throwable $e) { /* ignore */ }

        require JPG_DIR . 'admin/views/page-users.php';
    }

    /**
     * Schreibt einen user_meta-Wert für das Plugin.
     */
    private static function set_user_meta(int $userId, string $key, string $value): void
    {
        $db = \CMS\Database::instance();
        $p  = $db->getPrefix();
        try {
            $db->execute(
                "INSERT INTO {$p}user_meta (user_id, meta_key, meta_value)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)",
                [$userId, $key, $value]
            );
        } catch (\Throwable $e) {
            // ON DUPLICATE nicht supportet → UPDATE
            try {
                $existing = $db->get_var(
                    "SELECT id FROM {$p}user_meta WHERE user_id = ? AND meta_key = ?",
                    [$userId, $key]
                );
                if ($existing) {
                    $db->execute(
                        "UPDATE {$p}user_meta SET meta_value = ? WHERE user_id = ? AND meta_key = ?",
                        [$value, $userId, $key]
                    );
                } else {
                    $db->execute(
                        "INSERT INTO {$p}user_meta (user_id, meta_key, meta_value) VALUES (?, ?, ?)",
                        [$userId, $key, $value]
                    );
                }
            } catch (\Throwable $e2) { /* silent */ }
        }
    }
}

