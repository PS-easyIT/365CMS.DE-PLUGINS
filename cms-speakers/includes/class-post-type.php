<?php
/**
 * Post Type Handler fuer CMS Speakers
 * @package CMS_Speakers
 * @since 2.0.0
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class CMS_Speakers_Post_Type
{
    private static ?self $instance = null;
    public static function instance(): self
    {
        if (self::$instance === null) { self::$instance = new self(); }
        return self::$instance;
    }
    private function __construct() { $this->init_hooks(); }
    private function init_hooks(): void
    {
        CMS\Hooks::addAction('register_routes', [$this, 'register_routes'], 10);
        CMS\Hooks::addAction('main_nav', [$this, 'add_menu_item'], 10);
    }
    public function register_routes($router): void
    {
        $router->addRoute('GET',  '/speakers',              [$this, 'archive_page']);
        $router->addRoute('GET',  '/speakers/:slug',        [$this, 'single_page_by_slug']);
        $router->addRoute('GET',  '/admin/speakers',            [$this, 'admin_list']);
        $router->addRoute('GET',  '/admin/speakers/new',        [$this, 'admin_create']);
        $router->addRoute('GET',  '/admin/speakers/edit/:id',   [$this, 'admin_edit']);
        $router->addRoute('POST', '/admin/speakers/save',       [$this, 'admin_save']);
        $router->addRoute('POST', '/admin/speakers/delete/:id', [$this, 'admin_delete']);
        $router->addRoute('POST', '/admin/speakers/approve/:id', [$this, 'admin_approve']);
        $router->addRoute('POST', '/admin/speakers/event/add',        [$this, 'admin_event_add']);
        $router->addRoute('POST', '/admin/speakers/event/delete/:id', [$this, 'admin_event_delete']);
        $router->addRoute('POST', '/admin/speakers/settings/save',    [$this, 'admin_settings_save']);
    }
    public function add_menu_item(): void
    {
        $current_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $is_active = str_starts_with($current_path, '/speakers') ? 'active' : '';
        echo '<a href="' . SITE_URL . '/speakers" class="nav-link ' . $is_active . '">Speaker</a>';
    }
    public function archive_page(): void
    {
        $tm = \CMS\ThemeManager::instance();
        $tm->getHeader();
        $db = CMS_Speakers_Database::instance();
        $raw_s = $db->get_settings();
        $settings = array_merge([
            'archive_title'              => 'Speaker Directory',
            'archive_description'        => 'Finden Sie den passenden Redner für Ihr Event',
            'archive_per_page'           => '12',
            'archive_header_bg_from'     => '#6d28d9',
            'archive_header_bg_to'       => '#a855f7',
            'archive_header_title_color' => '#ffffff',
            'design_primary_color'       => '#8b5cf6',
            'design_accent_color'        => '#7c3aed',
            'design_card_bg'             => '#faf5ff',
            'design_border_radius'       => '12',
            'design_show_availability'   => '1',
            'design_show_formats'        => '1',
            'design_show_topics'         => '1',
            'design_show_city'           => '1',
            'design_grid_columns'        => 'auto',
            'detail_header_bg_from'      => '#4c1d95',
            'detail_header_bg_to'        => '#7c3aed',
        ], $raw_s);
        $filter_availability = $_GET['availability'] ?? null;
        $filter_format       = $_GET['format']       ?? null;
        $filter_city         = $_GET['city']         ?? null;
        $filter_travel       = $_GET['travel']       ?? null;
        $search              = trim($_GET['search']  ?? '');
        $page                = max(1, (int)($_GET['page'] ?? 1));
        $per_page            = max(1, (int)$settings['archive_per_page']);
        $args = ['status' => 'active', 'limit' => $per_page, 'offset' => ($page - 1) * $per_page];
        if ($filter_availability) $args['availability']  = $filter_availability;
        if ($filter_city)         $args['city']          = $filter_city;
        if ($filter_travel)       $args['travel_radius'] = $filter_travel;
        if ($search)              $args['search']        = $search;
        $speakers  = $db->get_speakers($args);
        $total     = $db->count_speakers(array_diff_key($args, array_flip(['limit','offset'])));
        $pages     = (int)ceil($total / max(1, $per_page));
        $cities    = $db->get_distinct_cities();
        if (!empty($speakers)) {
            $ids  = array_map(fn($s) => (int)$s->id, $speakers);
            $ph   = implode(',', array_fill(0, count($ids), '?'));
            $rdb  = CMS\Database::instance();
            $stmt = $rdb->prepare("SELECT speaker_id, topic_name FROM {$rdb->prefix()}speaker_topics WHERE speaker_id IN ({$ph}) ORDER BY topic_name ASC");
            $stmt->execute($ids);
            $map  = [];
            foreach ($stmt->fetchAll() as $r) { $map[$r->speaker_id][] = $r->topic_name; }
            foreach ($speakers as $s) { $s->_topics = $map[$s->id] ?? []; }
        }
        $tpl = CMS_Speakers_Template_Loader::instance();
        $tpl->render_template('archive-speaker', compact(
            'speakers','settings','page','per_page','pages','total','cities',
            'filter_availability','filter_format','filter_city','filter_travel','search'
        ));
        $tm->getFooter();
    }
    public function single_page_by_slug(string $slug = ''): void
    {
        if ($slug === '') $slug = $_GET['slug'] ?? '';
        $speaker_id = 0;
        if (preg_match('/-?(\d+)$/', $slug, $m))  $speaker_id = (int)$m[1];
        elseif (ctype_digit($slug))                $speaker_id = (int)$slug;
        if ($speaker_id <= 0) { $this->render_404(); return; }
        $db = CMS_Speakers_Database::instance();
        $speaker = $db->get_speaker($speaker_id);
        if (!$speaker) { $this->render_404(); return; }
        $db->increment_views($speaker_id);
        $topics   = $db->get_topics($speaker_id);
        // Manuelle Speaker-Events (speaker_events Tabelle)
        $events   = $db->get_events($speaker_id, false);
        // Zusätzlich: über cms-events Plugin zugewiesene Events
        $events   = array_merge($events, $this->get_speaker_cms_events($speaker_id));
        $raw_s    = $db->get_settings();
        $settings = array_merge([
            'design_primary_color'          => '#8b5cf6',
            'design_accent_color'           => '#7c3aed',
            'design_card_bg'                => '#faf5ff',
            'archive_header_bg_from'        => '#6d28d9',
            'archive_header_bg_to'          => '#a855f7',
            'archive_header_title_color'    => '#ffffff',
            'archive_header_icon'           => '🎤',
            'detail_header_bg_from'         => '#4c1d95',
            'detail_header_bg_to'           => '#7c3aed',
            'detail_header_title_color'     => '#ffffff',
            'design_border_radius'          => '12',
            'design_show_availability'      => '1',
            'design_show_mvp_badge'         => '1',
            'design_show_formats'           => '1',
            'design_show_topics'            => '1',
            // Badge-Farben – Verfügbarkeit
            'design_badge_avail_bg'         => '#d1fae5',
            'design_badge_avail_color'      => '#065f46',
            'design_badge_limited_bg'       => '#fef3c7',
            'design_badge_limited_color'    => '#92400e',
            'design_badge_booked_bg'        => '#fee2e2',
            'design_badge_booked_color'     => '#991b1b',
            // Badge-Farben – MVP & Verifiziert
            'design_badge_mvp_bg'           => 'rgba(251,191,36,0.2)',
            'design_badge_mvp_color'        => '#fbbf24',
            'design_badge_verified_bg'      => 'rgba(255,255,255,0.15)',
            'design_badge_verified_color'   => '#ffffff',
        ], $raw_s);
        $tm = \CMS\ThemeManager::instance();
        $tm->getHeader();
        CMS_Speakers_Template_Loader::instance()->render_template('single-speaker', compact('speaker','topics','events','settings'));
        $tm->getFooter();
    }
    /**
     * Holt Events aus cms_event_speakers JOIN cms_events (cms-events Plugin).
     */
    private function get_speaker_cms_events(int $speaker_id): array
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
                 WHERE es.speaker_id = ? AND es.speaker_type = 'speaker'
                   AND e.status = 'published'
                 ORDER BY e.event_date DESC"
            );
            $stmt->execute([$speaker_id]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }
    private function require_admin(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { CMS\Router::instance()->redirect('/login'); exit; }
    }
    public function admin_list(): void
    {
        $this->require_admin();
        $db     = CMS_Speakers_Database::instance();
        $tab    = $_GET['tab']    ?? 'overview';
        $filter = $_GET['filter'] ?? 'all';
        $search = trim($_GET['search'] ?? '');
        $args = ['limit' => 200, 'status' => null];
        if ($filter === 'featured')  $args['is_featured']  = 1;
        if ($filter === 'verified')  $args['is_verified']  = 1;
        if ($filter === 'available') $args['availability'] = 'available';
        if ($filter === 'pending')   $args['status'] = 'pending';
        if ($search) $args['search'] = $search;
        $speakers  = $db->get_speakers($args);
        $settings  = $db->get_settings();
        $companies = $db->get_companies_for_select();
        $csrf      = CMS\Security::instance()->generateToken('speaker_settings');
        CMS_Speakers_Admin::instance()->render_list(compact('speakers','tab','filter','search','settings','csrf','companies'));
    }
    public function admin_create(): void
    {
        $this->require_admin();
        $companies = CMS_Speakers_Database::instance()->get_companies_for_select();
        CMS_Speakers_Admin::instance()->render_form(null, [], [], $companies);
    }
    public function admin_edit(int $id = 0): void
    {
        $this->require_admin();
        if ($id <= 0) $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { CMS\Router::instance()->redirect('/admin/speakers'); return; }
        $db = CMS_Speakers_Database::instance();
        $speaker = $db->get_speaker($id);
        if (!$speaker) { CMS\Router::instance()->redirect('/admin/speakers'); return; }
        $topics    = $db->get_topics($id);
        $events    = $db->get_events($id);
        $companies = $db->get_companies_for_select();
        CMS_Speakers_Admin::instance()->render_form($speaker, $topics, $events, $companies);
    }
    public function admin_save(): void
    {
        $this->require_admin();
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'save_speaker')) {
            CMS\Router::instance()->redirect('/admin/speakers?error=csrf'); return;
        }
        $id      = (int)($_POST['speaker_id'] ?? 0);
        $formats = is_array($_POST['formats'] ?? null) ? array_map('trim', $_POST['formats']) : [];
        $langs   = array_filter(array_map('trim', explode(',', $_POST['languages'] ?? '')));

        // Feldnamen auf DB-Spaltennamen mappen
        $data = [
            'first_name'        => trim($_POST['first_name']      ?? ''),
            'last_name'         => trim($_POST['last_name']       ?? ''),
            'title'             => trim($_POST['academic_title']  ?? ''),  // DB-Spalte: title
            'gender'            => trim($_POST['gender']          ?? ''),
            'position'          => trim($_POST['position']        ?? ''),
            'company'           => trim($_POST['company']         ?? ''),
            'company_id'        => (int)($_POST['company_id']     ?? 0) ?: null,
            'email'             => filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '',
            'phone'             => trim($_POST['phone']           ?? ''),
            'website'           => filter_var(trim($_POST['website']   ?? ''), FILTER_VALIDATE_URL) ?: '',
            'linkedin'          => filter_var(trim($_POST['linkedin']  ?? ''), FILTER_VALIDATE_URL) ?: '',
            'twitter'           => trim($_POST['twitter']         ?? ''),
            'xing'              => filter_var(trim($_POST['xing']      ?? ''), FILTER_VALIDATE_URL) ?: '',
            'instagram'         => trim($_POST['instagram']       ?? ''),
            'youtube'           => filter_var(trim($_POST['youtube']   ?? ''), FILTER_VALIDATE_URL) ?: '',
            'location_city'     => trim($_POST['location_city']    ?? ''),
            'location_zip'      => trim($_POST['location_zip']     ?? ''),
            'location_country'  => trim($_POST['location_country'] ?? 'Deutschland'),
            'bio'               => class_exists('\CMS\Services\EditorService')
                                    ? \CMS\Services\EditorService::getInstance()->sanitize(
                                          html_entity_decode($_POST['bio'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8')
                                      )
                                    : ($_POST['bio'] ?? ''),
            'short_bio'         => trim(strip_tags($_POST['short_bio'] ?? '')),
            'photo_url'         => filter_var(trim($_POST['photo_url'] ?? ''), FILTER_VALIDATE_URL) ?: '',
            'formats'           => json_encode($formats),
            'languages'         => json_encode($langs),
            'target_audience'   => trim($_POST['target_audience']  ?? ''),
            'speaking_style'    => trim($_POST['speaking_style']   ?? ''),
            'awards'            => trim($_POST['awards']           ?? ''),
            'travel_radius'     => trim($_POST['travel_radius']    ?? 'national'),
            'availability'      => trim($_POST['availability']     ?? 'available'),
            'speaking_fee_min'  => (int)($_POST['speaking_fee_min'] ?? 0) ?: null,
            'speaking_fee_max'  => (int)($_POST['speaking_fee_max'] ?? 0) ?: null,
            'max_audience_size' => (int)($_POST['max_audience_size'] ?? 0) ?: null,
            'status'            => trim($_POST['status']           ?? 'active'),
            'is_featured'       => isset($_POST['is_featured']) ? 1 : 0,
            'is_verified'       => isset($_POST['is_verified'])  ? 1 : 0,
            'recognitions'      => json_encode(
                is_array($_POST['recognitions'] ?? null) ? array_map('trim', $_POST['recognitions']) : []
            ),
            'skills'            => json_encode(
                is_array($_POST['skills'] ?? null) ? array_map('trim', $_POST['skills']) : []
            ),
        ];
        $db       = CMS_Speakers_Database::instance();
        $saved_id = $db->save_speaker($data, $id);
        if ($saved_id > 0) {
            $tj = trim($_POST['topics_json'] ?? '');
            if ($tj !== '') $db->save_topics($saved_id, json_decode($tj, true) ?? []);
            CMS\Router::instance()->redirect('/admin/speakers/edit/' . $saved_id . '?saved=1');
        } else {
            CMS\Router::instance()->redirect('/admin/speakers?error=save');
        }
    }
    /** Speaker genehmigen (pending → active) */
    public function admin_approve(string $id_param = ''): void
    {
        $this->require_admin();
        $speaker_id  = $id_param !== '' ? (int)$id_param : (int)($_POST['id'] ?? 0);
        $csrf_token  = $_POST['csrf_token'] ?? '';
        if (!CMS\Security::instance()->verifyToken($csrf_token, 'speaker_settings')) {
            CMS\Router::instance()->redirect('/admin/speakers?tab=overview&error=csrf');
            return;
        }
        CMS_Speakers_Database::instance()->set_speaker_status($speaker_id, 'active');
        CMS\Router::instance()->redirect('/admin/speakers?tab=overview&approved=1');
    }

    public function admin_delete(int $id = 0): void
    {
        $this->require_admin();
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'delete_speaker')) {
            CMS\Router::instance()->redirect('/admin/speakers?error=csrf'); return;
        }
        if ($id <= 0) $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id > 0) CMS_Speakers_Database::instance()->delete_speaker($id);
        CMS\Router::instance()->redirect('/admin/speakers?deleted=1');
    }
    public function admin_event_add(): void
    {
        $this->require_admin();
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'speaker_event')) {
            $this->json_error('CSRF'); }
        $sid = (int)($_POST['speaker_id'] ?? 0);
        if ($sid <= 0) $this->json_error('Ungueltige ID');
        $d = [
            'event_title'    => trim($_POST['event_title']    ?? ''),
            'event_type'     => trim($_POST['event_type']     ?? 'keynote'),
            'event_date'     => $_POST['event_date']          ?? null,
            'event_date_end' => $_POST['event_date_end']      ?? null,
            'event_location' => trim($_POST['event_location'] ?? ''),
            'presence_type'  => trim($_POST['presence_type']  ?? 'presence'),
            'organizer_type' => trim($_POST['organizer_type'] ?? 'manual'),
            'company_id'     => (int)($_POST['company_id']    ?? 0) ?: null,
            'cms_event_id'   => (int)($_POST['cms_event_id']  ?? 0) ?: null,
            'organizer_name' => trim($_POST['organizer_name'] ?? ''),
            'topic'          => trim($_POST['topic']          ?? ''),
            'description'    => trim(strip_tags($_POST['description'] ?? '')),
            'audience_size'  => (int)($_POST['audience_size'] ?? 0) ?: null,
            'video_url'      => filter_var(trim($_POST['video_url']  ?? ''), FILTER_VALIDATE_URL) ?: '',
            'slides_url'     => filter_var(trim($_POST['slides_url'] ?? ''), FILTER_VALIDATE_URL) ?: '',
            'event_url'      => filter_var(trim($_POST['event_url']  ?? ''), FILTER_VALIDATE_URL) ?: '',
            'is_public'      => isset($_POST['is_public']) ? 1 : 0,
        ];
        // save_event erwartet speaker_id als ersten Parameter
        $ok = CMS_Speakers_Database::instance()->save_event($sid, $d);
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok]);
        exit;
    }
    public function admin_event_delete(int $id = 0): void
    {
        $this->require_admin();
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'speaker_event')) $this->json_error('CSRF');
        if ($id <= 0) $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) CMS_Speakers_Database::instance()->delete_event($id);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
    public function admin_settings_save(): void
    {
        $this->require_admin();
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'speaker_settings')) {
            CMS\Router::instance()->redirect('/admin/speakers?tab=settings&error=csrf'); return;
        }
        // Tab ermitteln – bestimmt welche Felder gespeichert werden
        $tab = (isset($_POST['_from_tab']) && in_array($_POST['_from_tab'], ['design', 'settings'], true))
            ? $_POST['_from_tab'] : 'settings';

        // Felder je Tab getrennt halten, damit ein Speichern eines Tabs
        // niemals die Einstellungen des anderen Tabs überschreibt.
        $design_text_fields = [
            'archive_header_bg_from','archive_header_bg_to','archive_header_title_color',
            'archive_header_icon',
            'design_primary_color','design_accent_color','design_card_bg',
            'design_border_radius','design_grid_columns','design_cta_label',
            'detail_header_bg_from','detail_header_bg_to','detail_header_title_color',
            'design_badge_avail_bg','design_badge_avail_color',
            'design_badge_limited_bg','design_badge_limited_color',
            'design_badge_booked_bg','design_badge_booked_color',
            'design_badge_mvp_bg','design_badge_mvp_color',
            'design_badge_verified_bg','design_badge_verified_color',
        ];
        $design_checkboxes = [
            'design_show_availability','design_show_mvp_badge',
            'design_show_formats','design_show_topics',
        ];
        $settings_text_fields = ['archive_title','archive_description','archive_per_page'];

        $settings = [];
        if ($tab === 'design') {
            foreach ($design_text_fields as $k) {
                $settings[$k] = trim($_POST[$k] ?? '');
            }
            foreach ($design_checkboxes as $k) {
                $settings[$k] = isset($_POST[$k]) ? '1' : '0';
            }
        } else {
            foreach ($settings_text_fields as $k) {
                $settings[$k] = trim($_POST[$k] ?? '');
            }
        }

        CMS_Speakers_Database::instance()->save_settings($settings);
        CMS\Router::instance()->redirect('/admin/speakers?tab=' . $tab . '&saved=1');
    }
    private function render_404(): void
    {
        http_response_code(404);
        $tm = \CMS\ThemeManager::instance();
        $tm->getHeader();
        echo '<main style="text-align:center;padding:5rem 1rem;"><div style="font-size:4rem;">🎤</div><h1 style="font-size:3rem;margin:.5rem 0;color:#6d28d9;">404</h1><h2 style="color:#1e293b;">Speaker nicht gefunden</h2><p style="color:#64748b;">Dieser Speaker existiert nicht oder wurde gelöscht.</p><a href="' . SITE_URL . '/speakers" style="display:inline-block;margin-top:1.5rem;padding:.6rem 1.5rem;background:#8b5cf6;color:#fff;border-radius:8px;text-decoration:none;">Zur Speaker-Übersicht</a></main>';
        $tm->getFooter();
    }
    private function json_error(string $msg): void
    {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }
}
