<?php
/**
 * Post Type Handler für CMS Companies
 *
 * @package CMS_Companies
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Companies_Post_Type
{
    private static ?self $instance = null;
    private const ALLOWED_PARTNER_FILTERS = ['sponsor', 'top_partner', 'partner'];

    private function log_error(string $context, \Throwable $e): void
    {
        error_log('CMS Companies [' . $context . ']: ' . $e->getMessage());
    }

    private function normalize_public_filter_string(mixed $value, int $maxLen = 120): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        if (class_exists('CMS\\Security')) {
            $raw = trim((string) CMS\Security::instance()->sanitize($raw, 'text'));
        }

        return mb_substr($raw, 0, $maxLen);
    }

    private function resolve_industry_filters(string $rawFilter, CMS_Companies_Database $db): array
    {
        $rawFilter = $this->normalize_public_filter_string($rawFilter, 150);
        if ($rawFilter === '') {
            return [];
        }

        $filters = [$rawFilter];
        foreach ($db->get_all_industries() as $industry) {
            $slug = trim((string) ($industry->slug ?? ''));
            $name = trim((string) ($industry->name ?? ''));
            if ($rawFilter === $slug || $rawFilter === $name) {
                if ($slug !== '') {
                    $filters[] = $slug;
                }
                if ($name !== '') {
                    $filters[] = $name;
                }
                break;
            }
        }

        return array_values(array_unique(array_filter($filters)));
    }

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

    private function init_hooks(): void
    {
        CMS\Hooks::addAction('register_routes', [$this, 'register_routes'], 10);
        CMS\Hooks::addAction('main_nav', [$this, 'add_menu_item'], 10);
    }

    public function register_routes($router): void
    {
        // Frontend Routes
        $router->addRoute('GET', '/companies', [$this, 'archive_page']);
        $router->addRoute('GET', '/companies/:id', [$this, 'single_page']);
        $router->addRoute('GET', '/company/:slug', [$this, 'single_page_by_slug']);

        // Admin Routes
        $router->addRoute('GET',  '/admin/companies',                   [$this, 'admin_list']);
        $router->addRoute('GET',  '/admin/companies/new',               [$this, 'admin_create']);
        $router->addRoute('POST', '/admin/companies/save',              [$this, 'admin_save']);
        $router->addRoute('GET',  '/admin/companies/edit/:id',          [$this, 'admin_edit']);
        $router->addRoute('POST', '/admin/companies/delete/:id',        [$this, 'admin_delete']);
        $router->addRoute('POST', '/admin/companies/approve/:id',       [$this, 'admin_approve']);
        $router->addRoute('POST', '/admin/companies/settings/save',     [$this, 'admin_settings_save']);
        $router->addRoute('POST', '/admin/companies/industry/add',      [$this, 'admin_industry_add']);
        $router->addRoute('POST', '/admin/companies/industry/delete/:id', [$this, 'admin_industry_delete']);
        $router->addRoute('POST', '/admin/companies/tagpreset/add',     [$this, 'admin_tagpreset_add']);
        $router->addRoute('POST', '/admin/companies/tagpreset/delete/:id', [$this, 'admin_tagpreset_delete']);
        $router->addRoute('POST', '/admin/companies/expert/assign',       [$this, 'admin_expert_assign']);
        $router->addRoute('POST', '/admin/companies/expert/remove',       [$this, 'admin_expert_remove']);
    }

    public function add_menu_item(): void
    {
        $settings = CMS_Companies_Database::instance()->get_settings();
        if ((string) ($settings['show_nav_link'] ?? '0') !== '1') {
            return;
        }

        $nav_label = trim((string) ($settings['nav_label'] ?? 'Unternehmen')) ?: 'Unternehmen';
        $current_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $is_active = strpos($current_path, '/companies') === 0 ? 'active' : '';
        
        echo '<a href="' . htmlspecialchars(rtrim((string) SITE_URL, '/') . '/companies', ENT_QUOTES, 'UTF-8') . '" class="nav-link ' . htmlspecialchars($is_active, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($nav_label, ENT_QUOTES, 'UTF-8') . '</a>';
    }

    public function archive_page(): void
    {
        // Öffentliche Ansicht – keine Abo-Prüfung, Erstellung ist separat geschützt
        $db_manager = CMS_Companies_Database::instance();

        $filter_industry_raw = $this->normalize_public_filter_string($_GET['industry'] ?? '', 150);
        $filter_city         = $this->normalize_public_filter_string($_GET['city'] ?? '', 100);
        $filter_partner_raw  = strtolower($this->normalize_public_filter_string($_GET['partner'] ?? '', 30));
        $filter_partner      = in_array($filter_partner_raw, self::ALLOWED_PARTNER_FILTERS, true) ? $filter_partner_raw : '';
        $filter_q            = $this->normalize_public_filter_string($_GET['q'] ?? '', 190);
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $per_page = 12;

        $args = [
            'status' => 'active',
            'limit'  => $per_page,
            'offset' => ($page - 1) * $per_page,
        ];

        $industryFilters = $this->resolve_industry_filters($filter_industry_raw, $db_manager);
        if ($industryFilters !== []) {
            $args['industry_values'] = $industryFilters;
        }
        if ($filter_city !== '') { $args['city'] = $filter_city; }
        if ($filter_partner !== '') { $args['partner'] = $filter_partner; }
        if ($filter_q !== '') { $args['q']        = $filter_q; }

        $companies   = $db_manager->get_companies($args);
        $count_args  = array_diff_key($args, array_flip(['limit', 'offset']));
        $total_count = $db_manager->get_companies_count($count_args);

        $tm = \CMS\ThemeManager::instance();
        $tm->getHeader();
        CMS_Companies_Template_Loader::instance()->render_template('archive-company', [
            'companies'    => $companies,
            'total_count'  => $total_count,
            'current_page' => $page,
            'per_page'     => $per_page,
            'filters'      => [
                'industry' => $filter_industry_raw,
                'city'     => $filter_city,
                'partner'  => $filter_partner,
                'q'        => $filter_q,
            ],
        ]);
        $tm->getFooter();
    }

    /**
     * Legacy-Route /companies/:id → 301-Redirect zur kanonischen Slug-URL.
     * Der Router übergibt den Parameter als Funktionsargument.
     */
    public function single_page(string $id_param = ''): void
    {
        $company_id = $id_param !== '' ? (int)$id_param : (int)($_GET['id'] ?? 0);

        if ($company_id <= 0) {
            $this->render_404();
            return;
        }

        $db_manager = CMS_Companies_Database::instance();
        $company    = $db_manager->get_company($company_id);

        if (!$company || $company->status !== 'active') {
            $this->render_404();
            return;
        }

        // 301-Redirect zur kanonischen Slug-URL
        if (function_exists('cms_company_url')) {
            header('Location: ' . cms_company_url($company), true, 301);
            exit;
        }

        // Fallback: direkt rendern (ohne Redirect, falls Helper fehlt)
        $this->render_single_company($company);
    }

    /**
     * Kanonische Route /company/:firmenname-id.
     * Der Router übergibt den Slug-Parameter als Funktionsargument.
     */
    public function single_page_by_slug(string $slug = ''): void
    {
        // Format: firmenname-{id} – ID aus dem letzten -Zahl-Segment extrahieren
        if (!preg_match('/-(\d+)$/', $slug, $m)) {
            $this->render_404();
            return;
        }

        $company_id = (int)$m[1];
        if ($company_id <= 0) {
            $this->render_404();
            return;
        }

        $db_manager = CMS_Companies_Database::instance();
        $company    = $db_manager->get_company($company_id);

        if (!$company || $company->status !== 'active') {
            $this->render_404();
            return;
        }

        $this->render_single_company($company);
    }

    private function render_single_company(object $company): void
    {
        $experts  = CMS_Companies_Database::instance()->get_company_experts((int)$company->id);
        $related_companies = $this->get_related_companies($company);

        $speakers = [];
        if (class_exists('CMS_Speakers_Database')) {
            try {
                $db   = \CMS\Database::instance();
                $stmt = $db->prepare(
                    "SELECT * FROM {$db->prefix()}speakers WHERE company_id = ? AND status = 'active' ORDER BY last_name, first_name"
                );
                $stmt->execute([(int)$company->id]);
                $speakers = $stmt->fetchAll();
            } catch (\Throwable $e) {
                $this->log_error('speaker_query', $e);
            }
        }

        $tm = \CMS\ThemeManager::instance();
        $tm->getHeader();
        CMS_Companies_Template_Loader::instance()->render_template('single-company', [
            'company'           => $company,
            'experts'           => $experts,
            'speakers'          => $speakers,
            'related_companies' => $related_companies,
        ]);
        $tm->getFooter();
    }

    /**
     * @return array<int, object>
     */
    private function get_related_companies(object $company): array
    {
        $company_id = (int) ($company->id ?? 0);
        if ($company_id <= 0) {
            return [];
        }

        $db = CMS\Database::instance();
        $industry = trim((string) ($company->industry ?? ''));

        try {
            if ($industry !== '') {
                $stmt = $db->prepare(
                    "SELECT * FROM {$db->prefix()}companies
                     WHERE status = 'active' AND id != ? AND industry = ?
                     ORDER BY is_sponsor DESC, is_top_partner DESC, is_partner DESC, name ASC
                     LIMIT 3"
                );
                $stmt->execute([$company_id, $industry]);
                $rows = $stmt->fetchAll();
                if (!empty($rows)) {
                    return $rows;
                }
            }

            $stmt = $db->prepare(
                "SELECT * FROM {$db->prefix()}companies
                 WHERE status = 'active' AND id != ?
                 ORDER BY is_sponsor DESC, is_top_partner DESC, is_partner DESC, name ASC
                 LIMIT 3"
            );
            $stmt->execute([$company_id]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            $this->log_error('related_companies', $e);
            return [];
        }
    }

    public function admin_list(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        $sec = CMS\Security::instance();
        $requestedView = $_GET['view'] ?? $_GET['tab'] ?? 'overview';
        $view = class_exists('CMS_Companies_Plugin_Admin_Contract')
            ? CMS_Companies_Plugin_Admin_Contract::normalize_view(is_string($requestedView) ? $requestedView : 'overview')
            : (is_string($requestedView) ? $requestedView : 'overview');

        $filter = (string) ($_GET['filter'] ?? 'all');
        if (!in_array($filter, ['all', 'sponsor', 'top', 'partner', 'pending'], true)) {
            $filter = 'all';
        }
        $search = trim($sec->sanitize($_GET['search'] ?? '', 'text'));

        $db  = CMS_Companies_Database::instance();

        // Alle nicht-gelöschten Firmen laden (aktiv UND inaktiv) für Admin-Übersicht
        $args = ['limit' => 200, 'status' => 'any'];
        if ($search !== '') {
            $args['q'] = $search;
        }
        $companies = $db->get_companies($args);

        $industries = $db->get_all_industries();
        $presets = [
            'general' => $db->get_tag_presets('general'),
            'special' => $db->get_tag_presets('special'),
            'quality' => $db->get_tag_presets('quality'),
        ];
        $settings = $db->get_settings();
        $csrf     = CMS\Security::instance()->generateToken('company_admin');

        $payload = [
            'companies'  => $companies,
            'view'       => $view,
            'filter'     => $filter,
            'search'     => $search,
            'industries' => $industries,
            'presets'    => $presets,
            'settings'   => $settings,
            'csrf'       => $csrf,
        ];

        try {
            CMS_Companies_Admin::instance()->render_list($payload);
        } catch (\Throwable $e) {
            $this->log_error('admin_list', $e);
            $payload['view'] = 'overview';
            CMS_Companies_Admin::instance()->render_list($payload);
        }
    }

    public function admin_create(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        CMS_Companies_Admin::instance()->render_form();
    }

    public function admin_edit(string $id = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        $company_id = $id !== '' ? (int)$id : (int)($_GET['id'] ?? 0);
        $db_manager = CMS_Companies_Database::instance();
        $company = $db_manager->get_company($company_id);

        if (!$company) {
            CMS\Router::instance()->redirect('/admin/companies');
            return;
        }

        CMS_Companies_Admin::instance()->render_form($company);
    }

    public function admin_save(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!CMS\Security::instance()->verifyToken($csrf_token, 'save_company')) {
            $company_id_err = (int)($_POST['company_id'] ?? 0);
            if ($company_id_err > 0) {
                CMS\Router::instance()->redirect('/admin/companies/edit/' . $company_id_err . '?error=csrf');
            } else {
                CMS\Router::instance()->redirect('/admin/companies/new?error=csrf');
            }
            return;
        }

        $sec  = CMS\Security::instance();

        // Beschreibung: HTML-Sanitierung + Inline-Style-Attribute entfernen.
        // html_entity_decode() als Schutt: falls Browser/Editor die Entities
        // bereits kodiert übermittelt hat, wird das vor dem Sanitize rückgängig gemacht.
        $desc_raw = html_entity_decode((string) ($_POST['description'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (class_exists('\CMS\Services\EditorService')) {
            $desc_raw = \CMS\Services\EditorService::getInstance()->sanitize($desc_raw);
        } else {
            $desc_raw = strip_tags($desc_raw);
        }
        $description = preg_replace('/\s+style\s*=\s*(?:"[^"]*"|\x27[^\x27]*\x27)/i', '', $desc_raw) ?? $desc_raw;
        $description = mb_substr($description, 0, 10000);

        $industryInput = $sec->sanitize($_POST['industry'] ?? '', 'text');
        $allowedIndustries = [];
        foreach (CMS_Companies_Database::instance()->get_all_industries() as $industryOption) {
            $slug = trim((string) ($industryOption->slug ?? ''));
            $name = trim((string) ($industryOption->name ?? ''));
            if ($slug !== '') {
                $allowedIndustries[] = $slug;
            }
            if ($name !== '') {
                $allowedIndustries[] = $name;
            }
        }
        $industry = in_array($industryInput, $allowedIndustries, true) ? $industryInput : '';
        $foundedYear = !empty($_POST['founded_year']) ? (int) $_POST['founded_year'] : null;
        $currentYear = (int) date('Y');
        if ($foundedYear !== null && ($foundedYear < 1800 || $foundedYear > $currentYear)) {
            $foundedYear = null;
        }
        $employeeCount = !empty($_POST['employee_count']) ? max(0, (int) $_POST['employee_count']) : null;
        $website = function_exists('cms_companies_public_url')
            ? cms_companies_public_url((string) ($_POST['website'] ?? ''))
            : (string) $sec->sanitize($_POST['website'] ?? '', 'url');
        $logoUrl = function_exists('cms_companies_public_url')
            ? cms_companies_public_url((string) ($_POST['logo_url'] ?? ''))
            : (string) $sec->sanitize($_POST['logo_url'] ?? '', 'url');

        $data = [
            'id'               => (int)($_POST['company_id'] ?? 0),
            'name'             => $sec->sanitize($_POST['name']             ?? '', 'text'),
            'email'            => $sec->sanitize($_POST['email']            ?? '', 'email'),
            'phone'            => $sec->sanitize($_POST['phone']            ?? '', 'text'),
            'industry'         => $industry,
            'company_size'     => $sec->sanitize($_POST['company_size']     ?? '', 'text'),
            'description'      => $description,
            'website'          => $website ?: null,
            'location_city'    => $sec->sanitize($_POST['location_city']    ?? '', 'text'),
            'location_zip'     => $sec->sanitize($_POST['location_zip']     ?? '', 'text'),
            'location_country' => $sec->sanitize($_POST['location_country'] ?? '', 'text'),
            'founded_year'     => $foundedYear,
            'employee_count'   => $employeeCount,
            // Checkboxen ohne Hidden-Feld: isset() prüft ob das Feld gesendet wurde
            'is_partner'       => isset($_POST['is_partner'])     ? 1 : 0,
            'is_top_partner'   => isset($_POST['is_top_partner']) ? 1 : 0,
            'is_sponsor'       => isset($_POST['is_sponsor'])     ? 1 : 0,
            'logo_url'         => $logoUrl ?: null,
            'status'           => in_array($_POST['company_status'] ?? 'active', ['active', 'inactive'], true)
                                  ? $_POST['company_status'] : 'active',
        ];

        $original_id = (int)($_POST['company_id'] ?? 0);
        $db_manager  = CMS_Companies_Database::instance();
        $company_id  = $db_manager->save_company($data);

        if ($company_id > 0) {
            CMS\Router::instance()->redirect('/admin/companies/edit/' . $company_id . '?success=1');
        } elseif ($original_id > 0) {
            // Update fehlgeschlagen – zurück zur Edit-Seite mit Fehlerhinweis
            CMS\Router::instance()->redirect('/admin/companies/edit/' . $original_id . '?error=save');
        } else {
            CMS\Router::instance()->redirect('/admin/companies/new?error=1');
        }
    }

    public function admin_delete(string $id = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $company_id = $id !== '' ? (int)$id : (int)($_GET['id'] ?? 0);
        
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!CMS\Security::instance()->verifyToken($csrf_token, 'delete_company')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        if ($company_id <= 0) {
            CMS\Router::instance()->redirect('/admin/companies?error=invalid_id');
            return;
        }

        $db = CMS\Database::instance();
        $result = $db->update('companies', ['status' => 'deleted'], ['id' => $company_id]);

        if ($result !== false) {
            CMS\Router::instance()->redirect('/admin/companies?deleted=1');
        } else {
            CMS\Router::instance()->redirect('/admin/companies?error=1');
        }
    }

    public function admin_approve(string $id = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            http_response_code(403);
            return;
        }

        $company_id = (int)$id;
        $csrf_token = $_POST['csrf_token'] ?? '';

        if (!CMS\Security::instance()->verifyToken($csrf_token, 'company_admin')) {
            CMS\Router::instance()->redirect('/admin/companies?error=csrf');
            return;
        }

        if ($company_id <= 0) {
            CMS\Router::instance()->redirect('/admin/companies?error=invalid_id');
            return;
        }

        $result = CMS_Companies_Database::instance()->set_company_status($company_id, 'active');

        if ($result) {
            CMS\Router::instance()->redirect('/admin/companies?approved=1');
        } else {
            CMS\Router::instance()->redirect('/admin/companies?error=approve');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Admin-Settings-Handler
    // ─────────────────────────────────────────────────────────────────────────

    public function admin_settings_save(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { http_response_code(403); return; }

        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'company_admin')) {
            CMS\Router::instance()->redirect('/admin/companies?view=settings&error=csrf'); return;
        }

        $view = (isset($_POST['_from_tab']) && in_array($_POST['_from_tab'], ['design', 'settings'], true))
            ? $_POST['_from_tab'] : 'settings';

        $db  = CMS_Companies_Database::instance();
        $sec = CMS\Security::instance();

        if ($view === 'design') {
            $design_text_fields = [
                'archive_header_icon', 'archive_header_bg_from', 'archive_header_bg_to',
                'archive_header_title_color',
                'design_primary_color', 'design_accent_color', 'design_card_bg',
                'design_border_radius', 'design_grid_columns', 'design_cta_color', 'design_cta_label',
                'design_detail_header_bg', 'design_detail_header_bg_to', 'design_detail_header_color', 'design_detail_accent',
                'design_partner_color', 'design_top_partner_color', 'design_sponsor_color',
                // Badge-Farben
                'design_badge_sponsor_bg', 'design_badge_sponsor_color',
                'design_badge_top_bg', 'design_badge_top_color',
                'design_badge_partner_bg', 'design_badge_partner_color',
                'design_badge_inactive_bg', 'design_badge_inactive_color',
            ];
            $design_checkboxes = [
                'design_show_industry', 'design_show_city',
                'design_show_employees', 'design_show_website',
                // Badge-Sichtbarkeit
                'design_show_sponsor_badge', 'design_show_top_partner_badge',
                'design_show_partner_badge', 'design_show_inactive_badge',
            ];
            foreach ($design_text_fields as $k) {
                $db->save_setting($k, $sec->sanitize($_POST[$k] ?? '', 'text'));
            }
            foreach ($design_checkboxes as $k) {
                $db->save_setting($k, isset($_POST[$k]) ? '1' : '0');
            }
        } else {
            foreach (['archive_title', 'archive_description', 'archive_per_page', 'nav_label'] as $k) {
                $db->save_setting($k, $sec->sanitize($_POST[$k] ?? '', 'text'));
            }
            $db->save_setting('show_nav_link', isset($_POST['show_nav_link']) ? '1' : '0');
        }

        CMS\Router::instance()->redirect('/admin/companies?view=' . $view . '&saved=1');
    }

    public function admin_industry_add(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { http_response_code(403); return; }
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'company_admin')) {
            http_response_code(403); return;
        }
        $name = trim(CMS\Security::instance()->sanitize($_POST['industry_name'] ?? '', 'text'));
        if ($name !== '') {
            CMS_Companies_Database::instance()->add_industry($name);
        }
        CMS\Router::instance()->redirect('/admin/companies?view=industries');
    }

    public function admin_industry_delete(string $id = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { http_response_code(403); return; }
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'company_admin')) {
            http_response_code(403); return;
        }
        $iid = $id !== '' ? (int)$id : (int)($_GET['id'] ?? 0);
        if ($iid > 0) {
            CMS_Companies_Database::instance()->delete_industry($iid);
        }
        CMS\Router::instance()->redirect('/admin/companies?view=industries');
    }

    public function admin_tagpreset_add(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { http_response_code(403); return; }
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'company_admin')) {
            http_response_code(403); return;
        }
        $name = trim(CMS\Security::instance()->sanitize($_POST['tag_name'] ?? '', 'text'));
        $type = in_array($_POST['tag_type'] ?? '', ['general','special','quality'], true)
                ? $_POST['tag_type'] : 'general';
        if ($name !== '') {
            CMS_Companies_Database::instance()->add_tag_preset($name, $type);
        }
        CMS\Router::instance()->redirect('/admin/companies?view=tags');
    }

    public function admin_tagpreset_delete(string $id = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { http_response_code(403); return; }
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'company_admin')) {
            http_response_code(403); return;
        }
        $tid = $id !== '' ? (int)$id : (int)($_GET['id'] ?? 0);
        if ($tid > 0) {
            CMS_Companies_Database::instance()->delete_tag_preset($tid);
        }
        CMS\Router::instance()->redirect('/admin/companies?view=tags');
    }

    public function admin_expert_assign(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { http_response_code(403); return; }
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'company_expert')) {
            CMS\Router::instance()->redirect('/admin/companies');
            return;
        }
        $company_id = (int)($_POST['company_id'] ?? 0);
        $expert_id  = (int)($_POST['expert_id']  ?? 0);
        $role       = trim((string) CMS\Security::instance()->sanitize($_POST['role'] ?? '', 'text'));
        if (mb_strlen($role) > 150) {
            $role = mb_substr($role, 0, 150);
        }
        $is_current = (int)($_POST['is_current'] ?? 1) === 1;

        if ($company_id > 0 && $expert_id > 0) {
            CMS_Companies_Database::instance()->assign_expert($company_id, $expert_id, $role, $is_current);
        }
        CMS\Router::instance()->redirect('/admin/companies/edit/' . $company_id . '#experts');
    }

    public function admin_expert_remove(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { http_response_code(403); return; }
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'company_expert')) {
            CMS\Router::instance()->redirect('/admin/companies');
            return;
        }
        $company_id = (int)($_POST['company_id'] ?? 0);
        $expert_id  = (int)($_POST['expert_id']  ?? 0);

        if ($company_id > 0 && $expert_id > 0) {
            CMS_Companies_Database::instance()->remove_expert($company_id, $expert_id);
        }
        CMS\Router::instance()->redirect('/admin/companies/edit/' . $company_id . '#experts');
    }

    private function render_404(): void
    {
        http_response_code(404);
        $tm = \CMS\ThemeManager::instance();
        $tm->getHeader();
        echo '<div class="co-not-found" style="max-width:640px;margin:4rem auto;padding:2rem;text-align:center;">';
        echo '<div style="font-size:3rem;margin-bottom:1rem">🏢</div>';
        echo '<h1 style="color:#1e293b;margin-bottom:.75rem">Unternehmen nicht gefunden</h1>';
        echo '<p style="color:#64748b;margin-bottom:1.5rem">Das gesuchte Unternehmen existiert nicht oder wurde entfernt.</p>';
        echo '<a href="' . SITE_URL . '/companies" style="display:inline-block;padding:.6rem 1.5rem;background:#4f46e5;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;">&larr; Zurück zur Übersicht</a>';
        echo '</div>';
        $tm->getFooter();
    }
}

// ── Globaler Helper: kanonische Company-URL ───────────────────────────────────────────
if (!function_exists('cms_company_url')) {
    function cms_company_url(object $company): string
    {
        $map   = ['ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss','Ä'=>'ae','Ö'=>'oe','Ü'=>'ue'];
        $name  = str_replace(array_keys($map), array_values($map), (string)($company->name ?? ''));
        $slug  = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug  = trim($slug, '-') ?: 'company';
        return SITE_URL . '/company/' . $slug . '-' . (int)$company->id;
    }
}
