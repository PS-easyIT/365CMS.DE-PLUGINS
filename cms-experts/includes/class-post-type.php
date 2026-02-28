<?php
/**
 * Post Type Handler für CMS Experts
 *
 * @package CMS_Experts
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Experts_Post_Type
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initialisiert Hooks
     */
    private function init_hooks(): void
    {
        // Register Routes
        CMS\Hooks::addAction('register_routes', [$this, 'register_routes'], 10);
        
        // Add Header Menu
        CMS\Hooks::addAction('main_nav', [$this, 'add_menu_item'], 10);
    }

    /**
     * Registriert Routes
     */
    public function register_routes($router): void
    {
        // Frontend Routes
        $router->addRoute('GET', '/experts',        [$this, 'archive_page']);
        $router->addRoute('GET', '/experts/:slug',  [$this, 'single_page_by_slug']); // vorname-nachname-{id}
        $router->addRoute('GET', '/expert/:slug',   [$this, 'single_page_by_slug']); // Legacy

        // Admin Routes
        $router->addRoute('GET',  '/admin/experts',                  [$this, 'admin_list']);
        $router->addRoute('GET',  '/admin/experts/new',              [$this, 'admin_create']);
        $router->addRoute('POST', '/admin/experts/save',             [$this, 'admin_save']);
        $router->addRoute('GET',  '/admin/experts/edit/:id',         [$this, 'admin_edit']);
        $router->addRoute('POST', '/admin/experts/delete/:id',       [$this, 'admin_delete']);
        $router->addRoute('POST', '/admin/experts/approve/:id',      [$this, 'admin_approve']);
        $router->addRoute('POST', '/admin/experts/taxonomy/add',     [$this, 'admin_taxonomy_add']);
        $router->addRoute('POST', '/admin/experts/taxonomy/delete/:id', [$this, 'admin_taxonomy_delete']);
        $router->addRoute('POST', '/admin/experts/skillpreset/add',  [$this, 'admin_skillpreset_add']);
        $router->addRoute('POST', '/admin/experts/skillpreset/delete/:id', [$this, 'admin_skillpreset_delete']);
        $router->addRoute('POST', '/admin/experts/settings/save',    [$this, 'admin_settings_save']);
    }

    /**
     * Fügt Menü-Item hinzu
     */
    public function add_menu_item(): void
    {
        $current_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $is_active = strpos($current_path, '/experts') === 0 ? 'active' : '';
        
        echo '<a href="' . SITE_URL . '/experts" class="nav-link ' . $is_active . '">Experten</a>';
    }

    /**
     * Archive Page - Liste aller Experten
     */
    public function archive_page(): void
    {
        $themeManager = \CMS\ThemeManager::instance();
        $themeManager->getHeader();

        $db_manager  = CMS_Experts_Database::instance();
        $raw_settings = $db_manager->get_all_plugin_settings();

        // Defaults zusammenführen
        $settings = array_merge([
            'archive_title'                => 'IT-Experten Directory',
            'archive_description'          => 'Finden Sie den passenden IT-Experten für Ihr Projekt',
            'archive_per_page'             => '12',
            'archive_header_icon'          => '&#128100;',
            'archive_header_bg_from'       => '#f5ecd5',
            'archive_header_bg_to'         => '#ebe0c8',
            'archive_header_title_color'   => '#7c4700',
            'design_primary_color'         => '#5e72e4',
            'design_accent_color'          => '#8965e0',
            'design_card_style'            => 'default',
            'design_show_availability'     => '1',
            'design_show_rate'             => '0',
            'design_show_city'             => '1',
            'design_border_radius'         => '12',
            'design_grid_columns'          => 'auto',
            'design_cta_color'             => '#c2410c',
            'design_card_bg'               => '#fffdf4',
            'design_show_skills'           => '1',
            'design_show_specialization'   => '1',
        ], $raw_settings);

        // Filter aus URL
        $filter_availability = $_GET['availability'] ?? null;
        $filter_city         = $_GET['city'] ?? null;
        $page                = max(1, (int)($_GET['page'] ?? 1));
        $per_page            = max(1, (int)$settings['archive_per_page']);

        $args = [
            'status' => 'active',
            'limit'  => $per_page,
            'offset' => ($page - 1) * $per_page,
        ];

        if ($filter_availability) {
            $args['availability'] = $filter_availability;
        }
        if ($filter_city) {
            $args['city'] = $filter_city;
        }

        $experts = $db_manager->get_experts($args);

        // Bulk-Laden von Skills und Spezialisierungen für alle Experten auf einmal (kein N+1)
        if (!empty($experts)) {
            $expert_ids   = array_map(fn($e) => (int)$e->id, $experts);
            $placeholders = implode(',', array_fill(0, count($expert_ids), '?'));
            $db_raw       = CMS\Database::instance();

            // Skills
            $stmt = $db_raw->prepare(
                "SELECT expert_id, skill_name FROM {$db_raw->prefix()}expert_skills
                 WHERE expert_id IN ({$placeholders}) ORDER BY skill_name ASC"
            );
            $stmt->execute($expert_ids);
            $skills_by_expert = [];
            foreach ($stmt->fetchAll() as $row) {
                $skills_by_expert[$row->expert_id][] = $row->skill_name;
            }

            // Spezialisierungen
            $stmt2 = $db_raw->prepare(
                "SELECT r.expert_id, s.name
                 FROM {$db_raw->prefix()}expert_specialization_rel r
                 INNER JOIN {$db_raw->prefix()}expert_specializations s ON s.id = r.specialization_id
                 WHERE r.expert_id IN ({$placeholders})
                 ORDER BY r.is_primary DESC, s.name ASC"
            );
            $stmt2->execute($expert_ids);
            $specs_by_expert = [];
            foreach ($stmt2->fetchAll() as $row) {
                $specs_by_expert[$row->expert_id][] = $row->name;
            }

            // Zertifikat-Anzahl
            $stmt3 = $db_raw->prepare(
                "SELECT expert_id, COUNT(*) AS cnt FROM {$db_raw->prefix()}expert_certifications
                 WHERE expert_id IN ({$placeholders}) GROUP BY expert_id"
            );
            $stmt3->execute($expert_ids);
            $certs_count = [];
            foreach ($stmt3->fetchAll() as $row) {
                $certs_count[$row->expert_id] = (int)$row->cnt;
            }

            // Social-Meta + company_id + Badge-Meta bulk-laden
            $social_keys = ['social_linkedin', 'social_xing', 'social_github', 'social_twitter', 'social_website', 'social_gitlab', 'company_id', 'is_mvp', 'is_premium', 'custom_award'];
            $key_placeholders = implode(',', array_fill(0, count($social_keys), '?'));
            $stmt4 = $db_raw->prepare(
                "SELECT expert_id, meta_key, meta_value FROM {$db_raw->prefix()}expert_meta
                 WHERE expert_id IN ({$placeholders}) AND meta_key IN ({$key_placeholders})"
            );
            $stmt4->execute(array_merge($expert_ids, $social_keys));
            $social_by_expert = [];
            foreach ($stmt4->fetchAll() as $row) {
                $social_by_expert[$row->expert_id][$row->meta_key] = $row->meta_value;
            }

            // An Experten-Objekte anhängen
            foreach ($experts as $expert) {
                $expert->_skills          = $skills_by_expert[$expert->id] ?? [];
                $expert->_specializations = $specs_by_expert[$expert->id]  ?? [];
                $expert->_cert_count      = $certs_count[$expert->id]      ?? 0;
                $expert->_social          = $social_by_expert[$expert->id] ?? [];
            }
        }

        $template_loader = CMS_Experts_Template_Loader::instance();
        $template_loader->render_template('archive-expert', [
            'experts'      => $experts,
            'current_page' => $page,
            'per_page'     => $per_page,
            'settings'     => $settings,
            'filters'      => [
                'availability' => $filter_availability,
                'city'         => $filter_city,
            ],
        ]);

        $themeManager->getFooter();
    }

    /**
     * Experten-Detailseite – Slug-Format: vorname-nachname-{id}
     * Wird auch als Legacy-Fallback für numerische IDs verwendet.
     */
    public function single_page_by_slug(string $slug = ''): void
    {
        if ($slug === '') {
            $slug = $_GET['slug'] ?? '';
        }

        // ID aus dem Ende extrahieren: z.B. "max-mustermann-42" → 42
        $expert_id = 0;
        if (preg_match('/-?(\d+)$/', $slug, $m)) {
            $expert_id = (int)$m[1];
        }
        // Fallback: rein numerischer Slug (Legacy /experts/42)
        if ($expert_id <= 0 && ctype_digit($slug)) {
            $expert_id = (int)$slug;
        }

        if ($expert_id <= 0) {
            $this->render_404();
            return;
        }

        $db_manager = CMS_Experts_Database::instance();
        $expert     = $db_manager->get_expert($expert_id);

        if (!$expert || $expert->status !== 'active') {
            $this->render_404();
            return;
        }

        // Design-Settings laden (gleiche Defaults wie archive_page)
        $raw_settings = $db_manager->get_all_plugin_settings();
        $settings = array_merge([
            'design_primary_color'         => '#5e72e4',
            'design_accent_color'          => '#8965e0',
            'design_border_radius'         => '12',
            'design_cta_color'             => '#c2410c',
            'design_card_bg'               => '#fffdf4',
            'design_detail_header_bg'      => '#1e293b',
            'design_detail_header_color'   => '#ffffff',
            'design_detail_accent'         => '#5e72e4',
            'design_status_available_color'=> '#14532d',
            'design_status_limited_color'  => '#7c4a03',
            'design_status_booked_color'   => '#7f1d1d',
            'design_partner_color'         => '#9ca3af',
            'design_top_partner_color'     => '#d97706',
            'design_sponsor_color'         => '#7c3aed',
        ], $raw_settings);

        $themeManager = \CMS\ThemeManager::instance();
        $themeManager->getHeader();

        CMS\Hooks::doAction('expert_profile_view', $expert_id);

        $skills          = $this->get_expert_skills($expert_id);
        $certifications  = $this->get_expert_certifications($expert_id);
        $projects        = $this->get_expert_projects($expert_id);
        $education       = $this->get_expert_education($expert_id);
        $meta            = $db_manager->get_all_meta($expert_id);
        $specializations = class_exists('CMS_Experts_Taxonomies')
            ? CMS_Experts_Taxonomies::instance()->get_expert_specializations($expert_id)
            : [];
        $events          = $this->get_expert_events($expert_id);

        $template_loader = CMS_Experts_Template_Loader::instance();
        $template_loader->render_template('single-expert', [
            'expert'          => $expert,
            'skills'          => $skills,
            'certifications'  => $certifications,
            'projects'        => $projects,
            'education'       => $education,
            'meta'            => $meta,
            'specializations' => $specializations,
            'events'          => $events,
            'settings'        => $settings,
        ]);

        $themeManager->getFooter();
    }

    /**
     * Events für einen Experten aus cms_event_speakers JOIN cms_events holen.
     * Fallback: leeres Array, wenn cms-events nicht aktiv ist.
     */
    private function get_expert_events(int $expert_id): array
    {
        try {
            $db = CMS\Database::instance();
            $p  = $db->prefix();
            $stmt = $db->prepare(
                "SELECT
                    e.title                                               AS event_title,
                    e.event_date,
                    COALESCE(NULLIF(e.location,''), NULLIF(e.city,''))   AS event_location,
                    e.category                                            AS event_type,
                    CASE WHEN e.is_online = 1 THEN 'online' ELSE 'presence' END AS presence_type
                 FROM {$p}event_speakers es
                 JOIN {$p}events e ON e.id = es.event_id
                 WHERE es.speaker_id = ? AND es.speaker_type = 'expert'
                   AND e.status = 'published'
                 ORDER BY e.event_date DESC"
            );
            $stmt->execute([$expert_id]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Admin List Page
     */
    public function admin_list(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        display_resource_limit_warning('experts', 'Experten');

        $tab    = $_GET['tab']    ?? 'overview';
        $filter = $_GET['filter'] ?? 'all';

        $db  = CMS_Experts_Database::instance();
        $tax = CMS_Experts_Taxonomies::instance();

        $experts  = $db->get_experts_all($filter !== 'all' ? ['status' => $filter] : []);
        $specs    = $tax->get_specializations();
        $presets  = $tax->get_skill_presets_grouped();
        $settings = $db->get_all_plugin_settings();
        $csrf     = CMS\Security::instance()->generateToken('experts_admin');

        CMS_Experts_Admin::instance()->render_list([
            'experts'  => $experts,
            'tab'      => $tab,
            'filter'   => $filter,
            'specs'    => $specs,
            'presets'  => $presets,
            'settings' => $settings,
            'csrf'     => $csrf,
        ]);
    }

    /** Expert genehmigen (pending → active) */
    public function admin_approve(string $id_param = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }
        $expert_id  = $id_param !== '' ? (int)$id_param : (int)($_POST['id'] ?? 0);
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!CMS\Security::instance()->verifyToken($csrf_token, 'experts_admin')) {
            CMS\Router::instance()->redirect('/admin/experts?tab=overview&error=csrf');
            return;
        }
        CMS_Experts_Database::instance()->set_expert_status($expert_id, 'active');
        CMS\Router::instance()->redirect('/admin/experts?tab=overview&approved=1');
    }

    /** Fachrichtung hinzufügen */
    public function admin_taxonomy_add(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'experts_admin')) {
            CMS\Router::instance()->redirect('/admin/experts?tab=taxonomies&error=csrf');
            return;
        }
        $name      = trim($_POST['spec_name'] ?? '');
        $parent_id = (int)($_POST['parent_id'] ?? 0);
        if ($name !== '') {
            $slug = mb_strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
            $slug = trim($slug, '-') . '-' . time();
            CMS_Experts_Taxonomies::instance()->add_specialization(
                $name, $slug, null, $parent_id > 0 ? $parent_id : null
            );
        }
        CMS\Router::instance()->redirect('/admin/experts?tab=taxonomies&saved=1');
    }

    /** Fachrichtung löschen */
    public function admin_taxonomy_delete(string $id_param = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'experts_admin')) {
            CMS\Router::instance()->redirect('/admin/experts?tab=taxonomies&error=csrf');
            return;
        }
        $id = $id_param !== '' ? (int)$id_param : (int)($_POST['id'] ?? 0);
        CMS_Experts_Taxonomies::instance()->delete_specialization($id);
        CMS\Router::instance()->redirect('/admin/experts?tab=taxonomies&deleted=1');
    }

    /** Skill-Preset hinzufügen */
    public function admin_skillpreset_add(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'experts_admin')) {
            CMS\Router::instance()->redirect('/admin/experts?tab=skills&error=csrf');
            return;
        }
        $name = trim($_POST['skill_name'] ?? '');
        $type = $_POST['skill_type'] ?? 'general';
        if ($name !== '') {
            CMS_Experts_Taxonomies::instance()->save_skill_preset($name, $type);
        }
        CMS\Router::instance()->redirect('/admin/experts?tab=skills&saved=1');
    }

    /** Skill-Preset löschen */
    public function admin_skillpreset_delete(string $id_param = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'experts_admin')) {
            CMS\Router::instance()->redirect('/admin/experts?tab=skills&error=csrf');
            return;
        }
        $id = $id_param !== '' ? (int)$id_param : (int)($_POST['id'] ?? 0);
        CMS_Experts_Taxonomies::instance()->delete_skill_preset($id);
        CMS\Router::instance()->redirect('/admin/experts?tab=skills&deleted=1');
    }

    /** Plugin-Einstellungen + Design speichern */
    public function admin_settings_save(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'experts_admin')) {
            CMS\Router::instance()->redirect('/admin/experts?tab=settings&error=csrf');
            return;
        }
        $sec      = CMS\Security::instance();
        $tab      = in_array($_POST['settings_tab'] ?? '', ['settings', 'design'], true)
                    ? $_POST['settings_tab'] : 'settings';
        $allowed  = [
            // Plugin-Einstellungen
            'archive_title', 'archive_description', 'archive_per_page',
            'archive_header_icon', 'archive_header_bg_from', 'archive_header_bg_to',
            'archive_header_title_color',
            // Design Karten
            'design_primary_color', 'design_accent_color', 'design_card_style',
            'design_show_availability', 'design_show_rate', 'design_show_city',
            'design_border_radius', 'design_grid_columns',
            'design_cta_color', 'design_card_bg',
            'design_show_skills', 'design_show_specialization',
            // Design Detail-Seite
            'design_detail_header_bg', 'design_detail_header_color', 'design_detail_accent',
            'design_status_available_color', 'design_status_limited_color', 'design_status_booked_color',
            'design_partner_color', 'design_top_partner_color', 'design_sponsor_color',
        ];
        $save = [];
        foreach ($allowed as $key) {
            if (isset($_POST[$key])) {
                $save[$key] = $sec->sanitize((string)$_POST[$key], 'text');
            }
        }
        if (!empty($save)) {
            CMS_Experts_Database::instance()->save_plugin_settings($save);
        }
        CMS\Router::instance()->redirect("/admin/experts?tab={$tab}&saved=1");
    }

    /**
     * Admin Create Page
     */
    public function admin_create(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        CMS_Experts_Admin::instance()->render_form();
    }

    /**
     * Admin Edit Page
     */
    public function admin_edit(string $id_param = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        $expert_id  = $id_param !== '' ? (int)$id_param : (int)($_GET['id'] ?? 0);
        $db_manager = CMS_Experts_Database::instance();
        $expert     = $db_manager->get_expert($expert_id);

        if (!$expert) {
            CMS\Router::instance()->redirect('/admin/experts');
            return;
        }

        $certifications = $this->get_expert_certifications($expert_id);
        $projects       = $this->get_expert_projects($expert_id);
        $education      = $this->get_expert_education($expert_id);
        $meta           = $db_manager->get_all_meta($expert_id);
        $skills         = $this->get_expert_skills($expert_id);

        CMS_Experts_Admin::instance()->render_form($expert, [
            'certifications' => $certifications,
            'projects'       => $projects,
            'education'      => $education,
            'meta'           => $meta,
            'skills'         => $skills,
        ]);
    }

    /**
     * Admin Save Handler
     */
    public function admin_save(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        // CSRF Token Check – Action muss mit generateToken('expert_form') übereinstimmen
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!CMS\Security::instance()->verifyToken($csrf_token, 'expert_form')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        $expert_id = (int)($_POST['expert_id'] ?? 0);
        
        // Limit-Check nur bei Neuanlage
        if ($expert_id === 0 && !user_can_create_resource('experts')) {
            CMS\Router::instance()->redirect('/admin/experts?error=limit_reached');
            return;
        }

        $security = CMS\Security::instance();

        // ── Firmen-Auflösung ──────────────────────────────────────────────────
        // company_id: '__freelance__' | numeric string | '' (Freitext-Fallback)
        $raw_company_id = trim($_POST['company_id'] ?? '');
        $company_id_int = null; // null = nicht verändert / Freitext
        $company_name   = '';

        if ($raw_company_id === '__freelance__') {
            $company_name   = 'Selbstständig';
            $company_id_int = 0;
        } elseif ($raw_company_id !== '' && ctype_digit($raw_company_id) && (int)$raw_company_id > 0) {
            $company_id_int = (int)$raw_company_id;
            if (class_exists('CMS_Companies_Database')) {
                $co_obj       = CMS_Companies_Database::instance()->get_company($company_id_int);
                $company_name = $co_obj ? $co_obj->name : $security->sanitize($_POST['company'] ?? '', 'text');
            } else {
                $company_name = $security->sanitize($_POST['company'] ?? '', 'text');
            }
        } else {
            // Freitext (Fallback ohne Plugin oder manuell)
            $company_id_int = -1;
            $company_name   = $security->sanitize($_POST['company'] ?? '', 'text');
        }
        // ─────────────────────────────────────────────────────────────────────

        // Sanitize Input – Basisdaten
        $data = [
            'id'               => (int)($_POST['expert_id'] ?? 0),
            'first_name'       => $security->sanitize($_POST['first_name'] ?? '', 'text'),
            'last_name'        => $security->sanitize($_POST['last_name'] ?? '', 'text'),
            'email'            => $security->sanitize($_POST['email'] ?? '', 'email'),
            'phone'            => $security->sanitize($_POST['phone'] ?? '', 'text'),
            'mobile'           => $security->sanitize($_POST['mobile'] ?? '', 'text'),
            'position'         => $security->sanitize($_POST['position'] ?? '', 'text'),
            'company'          => $company_name !== '' ? $company_name : $security->sanitize($_POST['company'] ?? '', 'text'),
            'biography'        => (static function (?string $raw): string {
                                     if ($raw === null || trim($raw) === '') return '';
                                     $decoded = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                     return strip_tags($decoded, '<p><br><b><strong><em><i><u><s><del><h2><h3><h4><ul><ol><li><a><blockquote><pre><code><span><div><img><table><thead><tbody><tr><th><td><hr>');
                                 })($_POST['biography'] ?? null),
            'location_city'    => $security->sanitize($_POST['location_city'] ?? '', 'text'),
            'location_zip'     => $security->sanitize($_POST['location_zip'] ?? '', 'text'),
            'location_country' => $security->sanitize($_POST['location_country'] ?? '', 'text'),
            'hourly_rate'      => isset($_POST['hourly_rate']) && $_POST['hourly_rate'] !== '' ? (float)$_POST['hourly_rate'] : null,
            'daily_rate'       => isset($_POST['daily_rate']) && $_POST['daily_rate'] !== '' ? (float)$_POST['daily_rate'] : null,
            'availability'     => $security->sanitize($_POST['availability'] ?? 'available', 'text'),
            'experience_years' => (int)($_POST['experience_years'] ?? 0),
            'photo_url'        => $security->sanitize($_POST['photo_url'] ?? '', 'url'),
            'status'           => in_array($_POST['status'] ?? '', ['active', 'inactive', 'pending'], true)
                                    ? $_POST['status'] : 'active',
        ];

        $db_manager = CMS_Experts_Database::instance();
        $expert_id  = $db_manager->save_expert($data);

        if ($expert_id > 0) {
            // Skills speichern (komma-getrennte Tag-Werte)
            $parse_tags = static fn(string $raw): array =>
                array_values(array_filter(array_map('trim', explode(',', $raw))));

            $db_manager->save_expert_skills($expert_id, [
                'general' => $parse_tags($_POST['skills_general'] ?? ''),
                'tech'    => $parse_tags($_POST['skills_tech'] ?? ''),
                'soft'    => $parse_tags($_POST['skills_soft'] ?? ''),
            ]);

            // Fachrichtungen speichern
            $spec_ids = array_map('intval', (array)($_POST['spec_ids'] ?? []));
            $db_manager->save_expert_specializations($expert_id, $spec_ids);

            // Meta: alle erlaubten Felder speichern –––––––––––––––––––––––––––––
            // Text-Felder (single values)
            $text_meta_keys = [
                'motto', 'partner_status', 'languages', 'remote_work', 'notice_period',
                'travel_willingness', 'timezone', 'work_type', 'contact_times',
                // Konditionen
                'avail_date', 'weekly_hours', 'min_project_duration', 'max_project_duration',
                'payment_terms', 'min_booking_duration', 'travel_cost_model', 'max_travel_distance_km',
                'max_team_size', 'team_size_led', 'total_projects', 'partner_networks',
            ];
            // URL-Felder
            $url_meta_keys = [
                'social_linkedin', 'social_xing', 'social_github', 'social_twitter',
                'social_website', 'social_gitlab', 'social_stackoverflow',
                'social_youtube', 'social_blog_rss',
            ];
            // Checkbox-Felder (0 oder 1)
            $bool_meta_keys = [
                'is_certified', 'is_premium', 'is_mvp',
                'fixed_price_projects', 'time_material',
                'subcontractors_available', 'team_expansion_possible',
                'services_consulting', 'services_implementation', 'services_training',
                'services_support', 'services_audit', 'emergency_support', 'workshop_offerings',
            ];
            // JSON-Arrays (aus Repeater-Feldern oder Checkbox-Gruppen)
            $json_meta_keys = [
                'preferred_company_sizes',     // checkbox-array
                'programming_languages',       // [{name,level}]
                'frameworks',                  // [{name,level}]
                'databases',                   // [{name,level}]
                'cloud_platforms',             // [{name,level}]
                'industry_experience',         // tags (comma-string oder JSON)
                'tools_preferred',             // tags
                'testimonials',                // [{text,client_name,position,company,rating}]
                'case_studies',                // [{title,description,link}]
                'conference_talks',            // [{title,event,year,video_link}]
                'career_stations',             // [{company,position,from_date,to_date,location,achievements}]
            ];

            $raw_meta = (array)($_POST['meta'] ?? []);

            // Text-Felder
            foreach ($text_meta_keys as $key) {
                if (array_key_exists($key, $raw_meta)) {
                    $db_manager->save_meta($expert_id, $key,
                        $security->sanitize((string)($raw_meta[$key] ?? ''), 'text')
                    );
                }
            }
            // URL-Felder
            foreach ($url_meta_keys as $key) {
                if (array_key_exists($key, $raw_meta)) {
                    $db_manager->save_meta($expert_id, $key,
                        $security->sanitize((string)($raw_meta[$key] ?? ''), 'url')
                    );
                }
            }
            // Checkbox-Felder (explizit auch auf 0 setzen wenn nicht gesendet)
            foreach ($bool_meta_keys as $key) {
                $db_manager->save_meta($expert_id, $key,
                    isset($raw_meta[$key]) && $raw_meta[$key] ? '1' : '0'
                );
            }
            // JSON-Array-Felder
            foreach ($json_meta_keys as $key) {
                if (!array_key_exists($key, $raw_meta)) {
                    continue;
                }
                $val = $raw_meta[$key];
                if (is_array($val)) {
                    // Checkbox-Array (z.B. preferred_company_sizes[])
                    $val = array_values(array_map('strval', $val));
                    $db_manager->save_meta($expert_id, $key, json_encode($val, JSON_UNESCAPED_UNICODE));
                } elseif (is_string($val)) {
                    // Bereits JSON von Repeater-Feldern (kommt schon als JSON-String)
                    $decoded = json_decode($val, true);
                    if (is_array($decoded)) {
                        $db_manager->save_meta($expert_id, $key, $val); // gültig → direkt speichern
                    } else {
                        // Plain-String (Tags komma-getrennt): als JSON-Array speichern
                        $tags = array_values(array_filter(array_map('trim', explode(',', $val))));
                        $db_manager->save_meta($expert_id, $key, json_encode($tags, JSON_UNESCAPED_UNICODE));
                    }
                }
            }
            // ── Meta-Speicherung Ende ────────────────────────────────────────

            // ── Firmen-Verknüpfung speichern ─────────────────────────────────
            if ($company_id_int !== null) {
                $db_manager->save_meta($expert_id, 'company_id', (string)$company_id_int);

                // Beziehungs-Tabelle (company_experts) aktualisieren
                if ($company_id_int > 0 && class_exists('CMS_Companies_Database')) {
                    CMS_Companies_Database::instance()->assign_expert(
                        $company_id_int,
                        $expert_id,
                        $data['position'] ?: null
                    );
                }
            }
            // ─────────────────────────────────────────────────────────────────

            // ── Zertifikate speichern ────────────────────────────────────────
            if (isset($_POST['certifications']) && is_array($_POST['certifications'])) {
                $certs_clean = [];
                foreach ($_POST['certifications'] as $cert) {
                    if (empty($cert['cert_name'])) { continue; }
                    $certs_clean[] = [
                        'cert_name'   => $security->sanitize($cert['cert_name']   ?? '', 'text'),
                        'cert_issuer' => $security->sanitize($cert['cert_issuer'] ?? '', 'text'),
                        'cert_date'   => $security->sanitize($cert['cert_date']   ?? '', 'text'),
                        'cert_expiry' => $security->sanitize($cert['cert_expiry'] ?? '', 'text'),
                        'cert_url'    => $security->sanitize($cert['cert_url']    ?? '', 'url'),
                    ];
                }
                $db_manager->save_expert_certifications($expert_id, $certs_clean);
            }
            // ─────────────────────────────────────────────────────────────────

            // ── Projekte speichern ───────────────────────────────────────────
            if (isset($_POST['projects']) && is_array($_POST['projects'])) {
                $projects_clean = [];
                foreach ($_POST['projects'] as $proj) {
                    if (empty($proj['project_name'])) { continue; }
                    $projects_clean[] = [
                        'project_name'        => $security->sanitize($proj['project_name']        ?? '', 'text'),
                        'project_description' => $security->sanitize($proj['project_description'] ?? '', 'text'),
                        'project_role'        => $security->sanitize($proj['project_role']        ?? '', 'text'),
                        'project_start'       => $security->sanitize($proj['project_start']       ?? '', 'text'),
                        'project_end'         => $security->sanitize($proj['project_end']         ?? '', 'text'),
                        'project_url'         => $security->sanitize($proj['project_url']         ?? '', 'url'),
                        'technologies'        => $security->sanitize($proj['technologies']        ?? '', 'text'),
                    ];
                }
                $db_manager->save_expert_projects($expert_id, $projects_clean);
            }
            // ─────────────────────────────────────────────────────────────────

            // ── Ausbildung speichern ─────────────────────────────────────────
            if (isset($_POST['education']) && is_array($_POST['education'])) {
                $edu_clean = [];
                foreach ($_POST['education'] as $edu) {
                    if (empty($edu['degree']) && empty($edu['institution'])) { continue; }
                    $edu_clean[] = [
                        'degree'        => $security->sanitize($edu['degree']        ?? '', 'text'),
                        'institution'   => $security->sanitize($edu['institution']   ?? '', 'text'),
                        'field_of_study'=> $security->sanitize($edu['field_of_study']?? '', 'text'),
                        'start_year'    => (int)($edu['start_year'] ?? 0) ?: null,
                        'end_year'      => (int)($edu['end_year']   ?? 0) ?: null,
                        'description'   => $security->sanitize($edu['description']   ?? '', 'text'),
                    ];
                }
                $db_manager->save_expert_education($expert_id, $edu_clean);
            }
            // ─────────────────────────────────────────────────────────────────

            CMS\Router::instance()->redirect('/admin/experts/edit/' . $expert_id . '?success=1');
        } else {
            CMS\Router::instance()->redirect('/admin/experts/new?error=1');
        }
    }

    /**
     * Admin Delete Handler
     */
    public function admin_delete(string $id_param = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $expert_id = $id_param !== '' ? (int)$id_param : (int)($_GET['id'] ?? 0);

        // CSRF Token Check
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!CMS\Security::instance()->verifyToken($csrf_token)) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        $db_manager = CMS_Experts_Database::instance();
        $result = $db_manager->delete_expert($expert_id);

        if ($result) {
            CMS\Router::instance()->redirect('/admin/experts?deleted=1');
        } else {
            CMS\Router::instance()->redirect('/admin/experts?error=1');
        }
    }

    /**
     * Holt Skills für einen Experten
     */
    private function get_expert_skills(int $expert_id): array
    {
        $db = CMS\Database::instance();
        $stmt = $db->prepare("SELECT * FROM {$db->prefix()}expert_skills WHERE expert_id = ? ORDER BY skill_level DESC, skill_name ASC");
        $stmt->execute([$expert_id]);
        
        return $stmt->fetchAll();
    }

    /**
     * Holt Certifications für einen Experten
     */
    private function get_expert_certifications(int $expert_id): array
    {
        $db = CMS\Database::instance();
        $stmt = $db->prepare("SELECT * FROM {$db->prefix()}expert_certifications WHERE expert_id = ? ORDER BY cert_date DESC");
        $stmt->execute([$expert_id]);
        
        return $stmt->fetchAll();
    }

    /**
     * Holt Projects für einen Experten
     */
    private function get_expert_projects(int $expert_id): array
    {
        $db = CMS\Database::instance();
        $stmt = $db->prepare("SELECT * FROM {$db->prefix()}expert_projects WHERE expert_id = ? ORDER BY project_start DESC");
        $stmt->execute([$expert_id]);
        
        return $stmt->fetchAll();
    }

    /**
     * Holt Education für einen Experten
     */
    private function get_expert_education(int $expert_id): array
    {
        $db = CMS\Database::instance();
        $stmt = $db->prepare("SELECT * FROM {$db->prefix()}expert_education WHERE expert_id = ? ORDER BY end_year DESC");
        $stmt->execute([$expert_id]);
        
        return $stmt->fetchAll();
    }

    /**
     * Rendert 404-Seite mit Theme-Wrapper
     */
    private function render_404(): void
    {
        http_response_code(404);
        $themeManager = \CMS\ThemeManager::instance();
        $themeManager->getHeader();
        echo '<main class="site-main"><div class="container" style="padding:4rem 0;text-align:center;">';
        echo '<h1 style="font-size:3rem;color:var(--text-color,#333);">404</h1>';
        echo '<p style="font-size:1.2rem;margin:1rem 0 2rem;">Experte nicht gefunden.</p>';
        echo '<a href="' . SITE_URL . '/experts" class="btn">Zur Experten-Übersicht</a>';
        echo '</div></main>';
        $themeManager->getFooter();
    }
}
