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
    private const ADMIN_SECTIONS = ['overview', 'taxonomies', 'skills', 'design', 'settings'];

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

    private function logError(string $context, \Throwable $error): void
    {
        error_log('CMS Experts Post Type [' . $context . ']: ' . $error->getMessage());
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
        $settings = CMS_Experts_Database::instance()->get_all_plugin_settings();
        if ((string) ($settings['show_nav_link'] ?? '0') !== '1') {
            return;
        }

        $nav_label = trim((string) ($settings['nav_label'] ?? 'Experten')) ?: 'Experten';
        $current_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $is_active = strpos($current_path, '/experts') === 0 ? 'active' : '';
        
        echo '<a href="' . htmlspecialchars(rtrim((string) SITE_URL, '/') . '/experts', ENT_QUOTES, 'UTF-8') . '" class="nav-link ' . htmlspecialchars($is_active, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($nav_label, ENT_QUOTES, 'UTF-8') . '</a>';
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
            'show_nav_link'                => '0',
            'nav_label'                    => 'Experten',
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
        $rawAvailability = (string) ($_GET['availability'] ?? '');
        $filter_availability = in_array($rawAvailability, ['available', 'limited', 'booked'], true) ? $rawAvailability : null;
        $filter_city = mb_substr(trim(strip_tags((string) ($_GET['city'] ?? ''))), 0, 100);
        $filter_city = $filter_city !== '' ? $filter_city : null;
        $filter_search = mb_substr(trim(strip_tags((string) ($_GET['q'] ?? ''))), 0, 120);
        $page                = max(1, min(999, (int)($_GET['page'] ?? 1)));
        $per_page            = max(1, min(60, (int)$settings['archive_per_page']));

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
        if ($filter_search !== '') {
            $args['search'] = $filter_search;
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
                'q'            => $filter_search,
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
            $slug = (string) ($_GET['slug'] ?? '');
        }

        $slug = mb_substr(trim($slug), 0, 180);

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
        $related_experts = $this->get_related_experts($expert_id, $specializations);

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
            'related_experts' => $related_experts,
            'settings'        => $settings,
        ]);

        $themeManager->getFooter();
    }

    /**
     * Holt ähnliche Experten für die Detailseiten-Sidebar.
     */
    private function get_related_experts(int $expert_id, array $specializations): array
    {
        try {
            $db = CMS\Database::instance();
            $p  = $db->prefix();
            $specialization_ids = [];

            foreach ($specializations as $specialization) {
                $id = (int) ($specialization->id ?? 0);
                if ($id > 0) {
                    $specialization_ids[$id] = $id;
                }
            }

            if (!empty($specialization_ids)) {
                $placeholders = implode(',', array_fill(0, count($specialization_ids), '?'));
                $stmt = $db->prepare(
                    "SELECT DISTINCT e.*
                     FROM {$p}experts e
                     INNER JOIN {$p}expert_specialization_rel r ON r.expert_id = e.id
                     WHERE e.id <> ?
                       AND e.status = 'active'
                       AND r.specialization_id IN ({$placeholders})
                     ORDER BY e.updated_at DESC
                     LIMIT 3"
                );
                $stmt->execute(array_merge([$expert_id], array_values($specialization_ids)));
                $rows = $stmt->fetchAll();
                if (!empty($rows)) {
                    return $rows;
                }
            }

            $stmt = $db->prepare(
                "SELECT *
                 FROM {$p}experts
                 WHERE id <> ? AND status = 'active'
                 ORDER BY updated_at DESC
                 LIMIT 3"
            );
            $stmt->execute([$expert_id]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            $this->logError('get_related_experts', $e);
            return [];
        }
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
            $this->logError('get_expert_events', $e);
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

        $section = (string) ($_GET['section'] ?? ($_GET['tab'] ?? 'overview'));
        if (!in_array($section, self::ADMIN_SECTIONS, true)) {
            $section = 'overview';
        }
        $filter = (string) ($_GET['filter'] ?? 'all');
        if (!in_array($filter, ['all', 'active', 'inactive', 'pending'], true)) {
            $filter = 'all';
        }
        $search = mb_substr(trim(strip_tags((string) ($_GET['search'] ?? ''))), 0, 120);
        $sort   = (string)($_GET['sort'] ?? 'updated_desc');

        $db  = CMS_Experts_Database::instance();
        $tax = CMS_Experts_Taxonomies::instance();

        $filters = $filter !== 'all' ? ['status' => $filter] : [];
        $experts  = $db->get_experts_all($filters);

        // Textsuche clientseitig filtern
        if ($search !== '') {
            $q = mb_strtolower($search);
            $experts = array_filter($experts, function ($e) use ($q) {
                $haystack = mb_strtolower(
                    ($e->first_name ?? '') . ' ' . ($e->last_name ?? '') . ' '
                    . ($e->position ?? '') . ' ' . ($e->company ?? '') . ' '
                    . ($e->location_city ?? '') . ' ' . ($e->email ?? '')
                );
                return str_contains($haystack, $q);
            });
        }

        $allowedSorts = [
            'updated_desc',
            'updated_asc',
            'created_desc',
            'created_asc',
            'name_asc',
            'name_desc',
            'status_asc',
            'availability_asc',
        ];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'updated_desc';
        }

        usort($experts, static function ($a, $b) use ($sort): int {
            $normalizeName = static function (object $expert): string {
                return mb_strtolower(trim((string)($expert->first_name ?? '') . ' ' . (string)($expert->last_name ?? '')));
            };
            $toTimestamp = static function (?string $value): int {
                if ($value === null || $value === '') {
                    return 0;
                }

                $time = strtotime($value);
                return $time !== false ? $time : 0;
            };
            $statusRank = ['pending' => 0, 'active' => 1, 'inactive' => 2, 'deleted' => 3];
            $availabilityRank = ['available' => 0, 'limited' => 1, 'booked' => 2];

            return match ($sort) {
                'updated_asc' => $toTimestamp((string)($a->updated_at ?? '')) <=> $toTimestamp((string)($b->updated_at ?? '')),
                'created_desc' => $toTimestamp((string)($b->created_at ?? '')) <=> $toTimestamp((string)($a->created_at ?? '')),
                'created_asc' => $toTimestamp((string)($a->created_at ?? '')) <=> $toTimestamp((string)($b->created_at ?? '')),
                'name_asc' => $normalizeName($a) <=> $normalizeName($b),
                'name_desc' => $normalizeName($b) <=> $normalizeName($a),
                'status_asc' => ($statusRank[(string)($a->status ?? '')] ?? 99) <=> ($statusRank[(string)($b->status ?? '')] ?? 99),
                'availability_asc' => ($availabilityRank[(string)($a->availability ?? '')] ?? 99) <=> ($availabilityRank[(string)($b->availability ?? '')] ?? 99),
                default => $toTimestamp((string)($b->updated_at ?? '')) <=> $toTimestamp((string)($a->updated_at ?? '')),
            };
        });

        $specs    = $tax->get_specializations();
        $presets  = $tax->get_skill_presets_grouped();
        $settings = $db->get_all_plugin_settings();
        $csrf     = CMS\Security::instance()->generateToken('experts_admin');

        CMS_Experts_Admin::instance()->render_list([
            'experts'  => $experts,
            'section'  => $section,
            'filter'   => $filter,
            'search'   => $search,
            'sort'     => $sort,
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
        $csrf_token = (string) ($_POST['csrf_token'] ?? '');
        if (!CMS\Security::instance()->verifyToken($csrf_token, 'experts_admin')) {
            CMS\Router::instance()->redirect('/admin/experts?section=overview&error=csrf');
            return;
        }
        if ($expert_id <= 0) {
            CMS\Router::instance()->redirect('/admin/experts?section=overview&error=invalid_id');
            return;
        }
        CMS_Experts_Database::instance()->set_expert_status($expert_id, 'active');
        CMS\Router::instance()->redirect('/admin/experts?section=overview&approved=1');
    }

    /** Fachrichtung hinzufügen */
    public function admin_taxonomy_add(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }
        if (!CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'experts_admin')) {
            CMS\Router::instance()->redirect('/admin/experts?section=taxonomies&error=csrf');
            return;
        }
        $name      = mb_substr(trim(strip_tags((string) ($_POST['spec_name'] ?? ''))), 0, 120);
        $parent_id = (int)($_POST['parent_id'] ?? 0);
        if ($name !== '') {
            $slug = mb_strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
            $slug = trim($slug, '-') . '-' . time();
            CMS_Experts_Taxonomies::instance()->add_specialization(
                $name, $slug, null, $parent_id > 0 ? $parent_id : null
            );
        }
        CMS\Router::instance()->redirect('/admin/experts?section=taxonomies&saved=1');
    }

    /** Fachrichtung löschen */
    public function admin_taxonomy_delete(string $id_param = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }
        if (!CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'experts_admin')) {
            CMS\Router::instance()->redirect('/admin/experts?section=taxonomies&error=csrf');
            return;
        }
        $id = $id_param !== '' ? (int)$id_param : (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            CMS\Router::instance()->redirect('/admin/experts?section=taxonomies&error=invalid_id');
            return;
        }
        CMS_Experts_Taxonomies::instance()->delete_specialization($id);
        CMS\Router::instance()->redirect('/admin/experts?section=taxonomies&deleted=1');
    }

    /** Skill-Preset hinzufügen */
    public function admin_skillpreset_add(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }
        if (!CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'experts_admin')) {
            CMS\Router::instance()->redirect('/admin/experts?section=skills&error=csrf');
            return;
        }
        $name = mb_substr(trim(strip_tags((string) ($_POST['skill_name'] ?? ''))), 0, 120);
        $type = in_array($_POST['skill_type'] ?? '', ['general', 'tech', 'soft'], true) ? (string) $_POST['skill_type'] : 'general';
        if ($name !== '') {
            CMS_Experts_Taxonomies::instance()->save_skill_preset($name, $type);
        }
        CMS\Router::instance()->redirect('/admin/experts?section=skills&saved=1');
    }

    /** Skill-Preset löschen */
    public function admin_skillpreset_delete(string $id_param = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }
        if (!CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'experts_admin')) {
            CMS\Router::instance()->redirect('/admin/experts?section=skills&error=csrf');
            return;
        }
        $id = $id_param !== '' ? (int)$id_param : (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            CMS\Router::instance()->redirect('/admin/experts?section=skills&error=invalid_id');
            return;
        }
        CMS_Experts_Taxonomies::instance()->delete_skill_preset($id);
        CMS\Router::instance()->redirect('/admin/experts?section=skills&deleted=1');
    }

    /** Plugin-Einstellungen + Design speichern */
    public function admin_settings_save(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }
        if (!CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'experts_admin')) {
            CMS\Router::instance()->redirect('/admin/experts?section=settings&error=csrf');
            return;
        }
        $sec      = CMS\Security::instance();
        $section  = in_array($_POST['settings_tab'] ?? '', ['settings', 'design'], true)
                    ? $_POST['settings_tab'] : 'settings';
        // ── Design-Tab: Text-Felder ──
        $design_text_fields = [
            'design_primary_color', 'design_accent_color', 'design_card_style',
            'design_border_radius', 'design_grid_columns',
            'design_cta_color', 'design_card_bg',
            'archive_header_icon', 'archive_header_bg_from', 'archive_header_bg_to',
            'archive_header_title_color',
            'detail_header_bg_from', 'detail_header_bg_to', 'detail_header_title_color',
            // Badge-Farben
            'design_badge_avail_bg', 'design_badge_avail_color',
            'design_badge_limited_bg', 'design_badge_limited_color',
            'design_badge_booked_bg', 'design_badge_booked_color',
            'design_badge_partner_bg', 'design_badge_partner_color',
            'design_badge_top_partner_bg', 'design_badge_top_partner_color',
            'design_badge_mvp_bg', 'design_badge_mvp_color',
        ];

        // ── Design-Tab: Checkboxen (0/1) ──
        $design_checkboxes = [
            'design_show_availability', 'design_show_rate', 'design_show_city',
            'design_show_skills', 'design_show_specialization',
        ];

        // ── Einstellungen-Tab: Text-Felder ──
        $settings_text_fields = [
            'archive_title', 'archive_description', 'archive_per_page', 'nav_label',
        ];

        $save = [];

        if ($section === 'design') {
            foreach ($design_text_fields as $key) {
                if (isset($_POST[$key])) {
                    $save[$key] = $sec->sanitize((string)$_POST[$key], 'text');
                }
            }
            foreach ($design_checkboxes as $key) {
                $save[$key] = isset($_POST[$key]) && $_POST[$key] !== '0' ? '1' : '0';
            }
        } elseif ($section === 'settings') {
            foreach ($settings_text_fields as $key) {
                if (isset($_POST[$key])) {
                    $save[$key] = $sec->sanitize((string)$_POST[$key], 'text');
                }
            }
            $save['show_nav_link'] = isset($_POST['show_nav_link']) ? '1' : '0';
        }
        if (!empty($save)) {
            CMS_Experts_Database::instance()->save_plugin_settings($save);
        }
        CMS\Router::instance()->redirect('/admin/experts?section=' . rawurlencode((string) $section) . '&saved=1');
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
        $skills         = $db_manager->get_expert_skills_grouped($expert_id);

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
        $csrf_token = (string) ($_POST['csrf_token'] ?? '');
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
        $db_manager = CMS_Experts_Database::instance();

        // ── Firmen-Auflösung ──────────────────────────────────────────────────
        // company_id: '__freelance__' | numeric string | '' (Freitext-Fallback)
        $raw_company_id = trim((string) ($_POST['company_id'] ?? ''));
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
            'availability'     => in_array($_POST['availability'] ?? '', ['available', 'limited', 'booked'], true)
                                    ? $_POST['availability']
                                    : 'available',
            'experience_years' => (int)($_POST['experience_years'] ?? 0),
            'photo_url'        => cms_experts_public_url((string) ($_POST['photo_url'] ?? '')) ?: null,
            'status'           => in_array($_POST['status'] ?? '', ['active', 'inactive', 'pending'], true)
                                    ? $_POST['status'] : 'active',
        ];

        $expert_id = $db_manager->save_expert($data);

        if ($expert_id > 0) {
            // Skills speichern (komma-getrennte Tag-Werte)
            $parse_tags = static fn(string $raw): array =>
                array_values(array_filter(array_map(
                    static fn(string $tag): string => $security->sanitize(trim($tag), 'text'),
                    explode(',', $raw)
                )));

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
            $sanitize_meta_recursive = function ($value) use (&$sanitize_meta_recursive, $security) {
                if (is_array($value)) {
                    $sanitized = [];
                    foreach ($value as $itemKey => $itemValue) {
                        $safeKey = is_string($itemKey) ? $security->sanitize($itemKey, 'text') : (int) $itemKey;
                        $sanitized[$safeKey] = $sanitize_meta_recursive($itemValue);
                    }
                    return $sanitized;
                }

                if ($value === null) {
                    return '';
                }

                return $security->sanitize((string) $value, 'text');
            };

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
                    $db_manager->save_meta($expert_id, $key, cms_experts_public_url((string)($raw_meta[$key] ?? '')));
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
                    $safeArray = $sanitize_meta_recursive($val);
                    if (is_array($safeArray)) {
                        $db_manager->save_meta($expert_id, $key, json_encode($safeArray, JSON_UNESCAPED_UNICODE));
                    }
                } elseif (is_string($val)) {
                    // Bereits JSON von Repeater-Feldern (kommt schon als JSON-String)
                    $decoded = json_decode($val, true);
                    if (is_array($decoded)) {
                        $safeDecoded = $sanitize_meta_recursive($decoded);
                        if (is_array($safeDecoded)) {
                            $db_manager->save_meta($expert_id, $key, json_encode($safeDecoded, JSON_UNESCAPED_UNICODE));
                        }
                    } else {
                        // Plain-String (Tags komma-getrennt): als JSON-Array speichern
                        $tags = array_values(array_filter(array_map(
                            static fn(string $tag): string => $security->sanitize(trim($tag), 'text'),
                            explode(',', $val)
                        )));
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
                    if (!is_array($cert)) {
                        continue;
                    }
                    if (empty($cert['cert_name'])) { continue; }
                    $certs_clean[] = [
                        'cert_name'   => $security->sanitize($cert['cert_name']   ?? '', 'text'),
                        'cert_issuer' => $security->sanitize($cert['cert_issuer'] ?? '', 'text'),
                        'cert_date'   => $this->sanitize_date_value((string)($cert['cert_date']   ?? '')),
                        'cert_expiry' => $this->sanitize_date_value((string)($cert['cert_expiry'] ?? '')),
                        'cert_url'    => cms_experts_public_url((string)($cert['cert_url'] ?? '')),
                    ];
                }
                $db_manager->save_expert_certifications($expert_id, $certs_clean);
            }
            // ─────────────────────────────────────────────────────────────────

            // ── Projekte speichern ───────────────────────────────────────────
            if (isset($_POST['projects']) && is_array($_POST['projects'])) {
                $projects_clean = [];
                foreach ($_POST['projects'] as $proj) {
                    if (!is_array($proj)) {
                        continue;
                    }
                    if (empty($proj['project_name'])) { continue; }
                    $projects_clean[] = [
                        'project_name'        => $security->sanitize($proj['project_name']        ?? '', 'text'),
                        'project_description' => $security->sanitize($proj['project_description'] ?? '', 'text'),
                        'project_role'        => $security->sanitize($proj['project_role']        ?? '', 'text'),
                        'project_start'       => $this->sanitize_date_value((string)($proj['project_start'] ?? '')),
                        'project_end'         => $this->sanitize_date_value((string)($proj['project_end'] ?? '')),
                        'project_url'         => cms_experts_public_url((string)($proj['project_url'] ?? '')),
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
                    if (!is_array($edu)) {
                        continue;
                    }
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
            CMS\Router::instance()->redirect('/login');
            return;
        }

        $expert_id = $id_param !== '' ? (int)$id_param : (int)($_POST['id'] ?? 0);

        // CSRF Token Check
        $csrf_token = (string) ($_POST['csrf_token'] ?? '');
        if (!CMS\Security::instance()->verifyToken($csrf_token, 'experts_admin')) {
            CMS\Router::instance()->redirect('/admin/experts?section=overview&error=csrf');
            return;
        }

        if ($expert_id <= 0) {
            CMS\Router::instance()->redirect('/admin/experts?section=overview&error=invalid_id');
            return;
        }

        $db_manager = CMS_Experts_Database::instance();
        $result = $db_manager->delete_expert($expert_id);

        if ($result) {
            CMS\Router::instance()->redirect('/admin/experts?section=overview&deleted=1');
        } else {
            CMS\Router::instance()->redirect('/admin/experts?section=overview&error=delete_failed');
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
        echo '<main class="site-main"><div class="container cms-experts-not-found">';
        echo '<h1>404</h1>';
        echo '<p>Experte nicht gefunden.</p>';
        echo '<a href="' . htmlspecialchars(rtrim((string) SITE_URL, '/') . '/experts', ENT_QUOTES, 'UTF-8') . '" class="btn">Zur Experten-Übersicht</a>';
        echo '</div></main>';
        $themeManager->getFooter();
    }

    private function sanitize_date_value(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));
        return checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
    }
}
