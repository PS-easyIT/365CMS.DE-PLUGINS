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
        $router->addRoute('GET',  '/admin/speakers/save',       [$this, 'admin_method_not_allowed']);
        $router->addRoute('POST', '/admin/speakers/save',       [$this, 'admin_save']);
        $router->addRoute('GET',  '/admin/speakers/delete/:id', [$this, 'admin_method_not_allowed']);
        $router->addRoute('POST', '/admin/speakers/delete/:id', [$this, 'admin_delete']);
        $router->addRoute('GET',  '/admin/speakers/approve/:id', [$this, 'admin_method_not_allowed']);
        $router->addRoute('POST', '/admin/speakers/approve/:id', [$this, 'admin_approve']);
        $router->addRoute('GET',  '/admin/speakers/event/add',        [$this, 'admin_method_not_allowed']);
        $router->addRoute('POST', '/admin/speakers/event/add',        [$this, 'admin_event_add']);
        $router->addRoute('GET',  '/admin/speakers/event/delete/:id', [$this, 'admin_method_not_allowed']);
        $router->addRoute('POST', '/admin/speakers/event/delete/:id', [$this, 'admin_event_delete']);
        $router->addRoute('GET',  '/admin/speakers/settings/save',    [$this, 'admin_method_not_allowed']);
        $router->addRoute('POST', '/admin/speakers/settings/save',    [$this, 'admin_settings_save']);
    }
    public function add_menu_item(): void
    {
        $showNavLink = false;
        $navLabel = 'Speaker';
        try {
            $settings = CMS_Speakers_Database::instance()->get_settings();
            $showNavLink = ((string) ($settings['show_nav_link'] ?? '0')) === '1';
            $navLabel = trim((string) ($settings['nav_label'] ?? $navLabel)) ?: $navLabel;
        } catch (\Throwable $e) {
            error_log('CMS Speakers main_nav setting fallback: ' . $e->getMessage());
            $showNavLink = false;
        }

        if (!$showNavLink) {
            return;
        }

        $current_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $is_active = str_starts_with($current_path, '/speakers') ? 'active' : '';
        echo '<a href="' . htmlspecialchars(SITE_URL . '/speakers', ENT_QUOTES, 'UTF-8') . '" class="nav-link ' . htmlspecialchars($is_active, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($navLabel, ENT_QUOTES, 'UTF-8') . '</a>';
    }
    public function archive_page(): void
    {
        try {
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
        $filter_availability = $this->allow_value((string) ($_GET['availability'] ?? ''), ['available', 'limited', 'booked']);
        $filter_format       = $this->allow_value((string) ($_GET['format'] ?? ''), ['keynote', 'workshop', 'panel', 'moderation', 'training', 'consulting', 'interview', 'webinar']);
        $filter_city         = $this->clean_text((string) ($_GET['city'] ?? ''), 100);
        $filter_travel       = $this->allow_value((string) ($_GET['travel'] ?? ''), ['local', 'regional', 'national', 'international', 'worldwide']);
        $search              = $this->clean_text((string) ($_GET['search'] ?? ''), 120);
        $page                = max(1, (int)($_GET['page'] ?? 1));
        $per_page            = max(1, (int)$settings['archive_per_page']);
        $args = ['status' => 'active', 'limit' => $per_page, 'offset' => ($page - 1) * $per_page];
        if ($filter_availability) $args['availability']  = $filter_availability;
        if ($filter_city)         $args['city']          = $filter_city;
        if ($filter_travel)       $args['travel_radius'] = $filter_travel;
        if ($filter_format)       $args['format']        = $filter_format;
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
        } catch (\Throwable $e) {
            $this->render_public_error($e, 'archive');
        }
    }
    public function single_page_by_slug(string $slug = ''): void
    {
        try {
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
        $related_speakers = array_slice(array_values(array_filter(
            $db->get_speakers(['status' => 'active', 'limit' => 4]),
            static fn(object $item): bool => (int) ($item->id ?? 0) !== $speaker_id
        )), 0, 3);
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
        CMS_Speakers_Template_Loader::instance()->render_template('single-speaker', compact('speaker','topics','events','settings','related_speakers'));
        $tm->getFooter();
        } catch (\Throwable $e) {
            $this->render_public_error($e, 'single');
        }
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
                    e.id                                                  AS cms_event_id,
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
        $tab    = $this->allow_value((string) ($_GET['tab'] ?? 'overview'), ['overview', 'topics', 'design', 'settings']) ?? 'overview';
        $filter = $this->allow_value((string) ($_GET['filter'] ?? 'all'), ['all', 'featured', 'verified', 'available', 'pending']) ?? 'all';
        $search = $this->clean_text((string) ($_GET['search'] ?? ''), 120);
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
        $approve_csrf = CMS\Security::instance()->generateToken('approve_speaker');
        CMS_Speakers_Admin::instance()->render_list(compact('speakers','tab','filter','search','settings','csrf','approve_csrf','companies'));
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
        if (!CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'save_speaker')) {
            CMS\Router::instance()->redirect('/admin/speakers?error=csrf'); return;
        }
        $id      = (int)($_POST['speaker_id'] ?? 0);
        $allowed_genders = ['', 'm', 'f', 'd'];
        $allowed_travel_radius = ['local', 'regional', 'national', 'international', 'worldwide'];
        $allowed_availability = ['available', 'limited', 'booked'];
        $allowed_statuses = ['active', 'inactive', 'draft', 'pending'];
        $formats = is_array($_POST['formats'] ?? null)
            ? array_values(array_filter(array_map(static fn($value) => trim((string) $value), $_POST['formats'])))
            : [];
        $langs   = array_values(array_filter(array_map('trim', explode(',', $_POST['languages'] ?? ''))));
        $gender = in_array(trim((string)($_POST['gender'] ?? '')), $allowed_genders, true) ? trim((string)($_POST['gender'] ?? '')) : '';
        $travel_radius = in_array(trim((string)($_POST['travel_radius'] ?? 'national')), $allowed_travel_radius, true)
            ? trim((string)($_POST['travel_radius'] ?? 'national'))
            : 'national';
        $availability = in_array(trim((string)($_POST['availability'] ?? 'available')), $allowed_availability, true)
            ? trim((string)($_POST['availability'] ?? 'available'))
            : 'available';
        $status = in_array(trim((string)($_POST['status'] ?? 'active')), $allowed_statuses, true)
            ? trim((string)($_POST['status'] ?? 'active'))
            : 'active';

        // Feldnamen auf DB-Spaltennamen mappen
        $data = [
            'first_name'        => $this->clean_text((string) ($_POST['first_name'] ?? ''), 100),
            'last_name'         => $this->clean_text((string) ($_POST['last_name'] ?? ''), 100),
            'title'             => $this->clean_text((string) ($_POST['academic_title'] ?? ''), 100),  // DB-Spalte: title
            'gender'            => $gender,
            'position'          => $this->clean_text((string) ($_POST['position'] ?? ''), 200),
            'company'           => $this->clean_text((string) ($_POST['company'] ?? ''), 200),
            'company_id'        => (int)($_POST['company_id']     ?? 0) ?: null,
            'email'             => filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '',
            'phone'             => preg_replace('/[^0-9+()\s.\-]/', '', (string) ($_POST['phone'] ?? '')) ?: '',
            'website'           => $this->clean_url((string) ($_POST['website'] ?? '')),
            'linkedin'          => $this->clean_url((string) ($_POST['linkedin'] ?? '')),
            'twitter'           => trim(strip_tags($_POST['twitter'] ?? '')),
            'xing'              => $this->clean_url((string) ($_POST['xing'] ?? '')),
            'instagram'         => trim(strip_tags($_POST['instagram'] ?? '')),
            'youtube'           => $this->clean_url((string) ($_POST['youtube'] ?? '')),
            'location_city'     => $this->clean_text((string) ($_POST['location_city'] ?? ''), 100),
            'location_zip'      => $this->clean_text((string) ($_POST['location_zip'] ?? ''), 20),
            'location_country'  => $this->clean_text((string) ($_POST['location_country'] ?? 'Deutschland'), 100),
            'bio'               => class_exists('\CMS\Services\EditorService')
                                    ? \CMS\Services\EditorService::getInstance()->sanitize(
                                          html_entity_decode($_POST['bio'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8')
                                      )
                                    : ($_POST['bio'] ?? ''),
            'short_bio'         => trim(strip_tags($_POST['short_bio'] ?? '')),
            'photo_url'         => $this->clean_url((string) ($_POST['photo_url'] ?? '')),
            'formats'           => json_encode($formats),
            'languages'         => json_encode($langs),
            'target_audience'   => $this->clean_text((string) ($_POST['target_audience'] ?? ''), 400),
            'speaking_style'    => $this->clean_text((string) ($_POST['speaking_style'] ?? ''), 200),
            'awards'            => $this->clean_textarea((string) ($_POST['awards'] ?? ''), 4000),
            'travel_radius'     => $travel_radius,
            'availability'      => $availability,
            'speaking_fee_min'  => (int)($_POST['speaking_fee_min'] ?? 0) ?: null,
            'speaking_fee_max'  => (int)($_POST['speaking_fee_max'] ?? 0) ?: null,
            'max_audience_size' => (int)($_POST['max_audience_size'] ?? 0) ?: null,
            'status'            => $status,
            'is_featured'       => isset($_POST['is_featured']) ? 1 : 0,
            'is_verified'       => isset($_POST['is_verified'])  ? 1 : 0,
            'recognitions'      => json_encode(
                is_array($_POST['recognitions'] ?? null)
                    ? array_values(array_filter(array_map(static fn($value) => trim((string) $value), $_POST['recognitions'])))
                    : []
            ),
            'skills'            => json_encode(
                is_array($_POST['skills'] ?? null)
                    ? array_values(array_filter(array_map(static fn($value) => trim((string) $value), $_POST['skills'])))
                    : []
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
        $csrf_token  = (string) ($_POST['csrf_token'] ?? '');
        if (!CMS\Security::instance()->verifyToken($csrf_token, 'approve_speaker')) {
            CMS\Router::instance()->redirect('/admin/speakers?tab=overview&error=csrf');
            return;
        }
        CMS_Speakers_Database::instance()->set_speaker_status($speaker_id, 'active');
        CMS\Router::instance()->redirect('/admin/speakers?tab=overview&approved=1');
    }

    public function admin_delete(int $id = 0): void
    {
        $this->require_admin();
        if (!CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'delete_speaker')) {
            CMS\Router::instance()->redirect('/admin/speakers?error=csrf'); return;
        }
        if ($id <= 0) $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id > 0) CMS_Speakers_Database::instance()->delete_speaker($id);
        CMS\Router::instance()->redirect('/admin/speakers?deleted=1');
    }
    public function admin_event_add(): void
    {
        $this->require_admin();
        if (!CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'speaker_event')) {
            $this->json_error('CSRF', 403); }
        $sid = (int)($_POST['speaker_id'] ?? 0);
        if ($sid <= 0) $this->json_error('Ungueltige ID', 422);
        $d = [
            'event_title'    => $this->clean_text((string) ($_POST['event_title'] ?? ''), 300),
            'event_type'     => $this->allow_value((string) ($_POST['event_type'] ?? 'keynote'), ['keynote', 'workshop', 'panel', 'moderation', 'interview', 'webinar', 'conference', 'training', 'other']) ?: 'keynote',
            'event_date'     => $this->normalize_date((string) ($_POST['event_date'] ?? '')),
            'event_date_end' => $this->normalize_date((string) ($_POST['event_date_end'] ?? '')),
            'event_location' => $this->clean_text((string) ($_POST['event_location'] ?? ''), 300),
            'presence_type'  => $this->allow_value((string) ($_POST['presence_type'] ?? 'presence'), ['presence', 'online', 'hybrid']) ?: 'presence',
            'organizer_type' => $this->allow_value((string) ($_POST['organizer_type'] ?? 'manual'), ['company', 'cms_event', 'manual']) ?: 'manual',
            'company_id'     => (int)($_POST['company_id']    ?? 0) ?: null,
            'cms_event_id'   => (int)($_POST['cms_event_id']  ?? 0) ?: null,
            'organizer_name' => $this->clean_text((string) ($_POST['organizer_name'] ?? ''), 300),
            'topic'          => $this->clean_text((string) ($_POST['topic'] ?? ''), 400),
            'description'    => $this->clean_textarea((string) ($_POST['description'] ?? ''), 4000),
            'audience_size'  => (int)($_POST['audience_size'] ?? 0) ?: null,
            'video_url'      => $this->clean_url((string) ($_POST['video_url'] ?? '')),
            'slides_url'     => $this->clean_url((string) ($_POST['slides_url'] ?? '')),
            'event_url'      => $this->clean_url((string) ($_POST['event_url'] ?? '')),
            'is_public'      => isset($_POST['is_public']) ? 1 : 0,
        ];
        // save_event erwartet speaker_id als ersten Parameter
        $ok = CMS_Speakers_Database::instance()->save_event($sid, $d);
        $this->json_response(['success' => $ok !== false, 'id' => $ok !== false ? (int) $ok : null], $ok !== false ? 200 : 500);
    }
    public function admin_event_delete(int $id = 0): void
    {
        $this->require_admin();
        if (!CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'speaker_event')) $this->json_error('CSRF', 403);
        if ($id <= 0) $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json_error('Ungueltige ID', 422);
        $deleted = CMS_Speakers_Database::instance()->delete_event($id);
        $this->json_response(['success' => $deleted], $deleted ? 200 : 500);
    }
    public function admin_settings_save(): void
    {
        $this->require_admin();
        if (!CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'speaker_settings')) {
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
        $settings_text_fields = ['archive_title','archive_description','archive_per_page','nav_label'];
        $settings_checkboxes = ['show_nav_link'];

        $settings = [];
        if ($tab === 'design') {
            foreach ($design_text_fields as $k) {
                $settings[$k] = $this->clean_setting((string) ($_POST[$k] ?? ''), $k);
            }
            foreach ($design_checkboxes as $k) {
                $settings[$k] = isset($_POST[$k]) ? '1' : '0';
            }
        } else {
            foreach ($settings_text_fields as $k) {
                $settings[$k] = $this->clean_setting((string) ($_POST[$k] ?? ''), $k);
            }
            foreach ($settings_checkboxes as $k) {
                $settings[$k] = isset($_POST[$k]) ? '1' : '0';
            }
            $settings['show_main_nav_item'] = $settings['show_nav_link'] ?? '0';
        }

        CMS_Speakers_Database::instance()->save_settings($settings);
        CMS\Router::instance()->redirect('/admin/speakers?tab=' . $tab . '&saved=1');
    }
    private function render_404(): void
    {
        http_response_code(404);
        $tm = \CMS\ThemeManager::instance();
        if (method_exists($tm, 'render')) {
            try {
                $tm->render('404');
                return;
            } catch (\Throwable) {
                // Fallback unten ausgeben.
            }
        }

        $tm->getHeader();
        echo '<main class="phinit-plugin sp-not-found"><h1>404</h1><h2>Speaker nicht gefunden</h2><p>Dieser Speaker existiert nicht oder wurde gelöscht.</p><a href="' . htmlspecialchars(SITE_URL . '/speakers', ENT_QUOTES, 'UTF-8') . '" class="phinit-btn phinit-btn--primary">Zur Speaker-Übersicht</a></main>';
        $tm->getFooter();
    }

    private function render_public_error(\Throwable $e, string $context): void
    {
        error_log('CMS Speakers public ' . $context . ': ' . $e->getMessage());
        http_response_code(500);

        try {
            $tm = \CMS\ThemeManager::instance();
            if (method_exists($tm, 'render')) {
                $tm->render('error', ['message' => 'Die Speaker-Seite konnte nicht geladen werden.']);
                return;
            }

            $tm->getHeader();
            echo '<main class="phinit-plugin sp-error"><h1>Fehler</h1><p>Die Speaker-Seite konnte nicht geladen werden.</p></main>';
            $tm->getFooter();
        } catch (\Throwable) {
            echo '<main class="phinit-plugin sp-error"><h1>Fehler</h1><p>Die Speaker-Seite konnte nicht geladen werden.</p></main>';
        }
    }

    public function admin_method_not_allowed(): void
    {
        $this->require_admin();
        http_response_code(405);
        header('Allow: POST');
        echo '<div class="admin-card"><h2>Methode nicht erlaubt</h2><p>Diese Aktion muss per POST ausgeführt werden.</p></div>';
    }

    private function allow_value(string $value, array $allowed): ?string
    {
        $value = trim($value);
        return in_array($value, $allowed, true) ? $value : null;
    }

    private function clean_text(string $value, int $maxLength = 255): string
    {
        return mb_substr(trim(strip_tags($value)), 0, $maxLength);
    }

    private function clean_textarea(string $value, int $maxLength = 2000): string
    {
        return mb_substr(trim(strip_tags($value)), 0, $maxLength);
    }

    private function clean_url(string $value): string
    {
        $url = trim($value);
        if ($url === '' || strlen($url) > 2048 || preg_match('/[[:cntrl:]]/', $url) === 1 || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || ($parts['user'] ?? '') !== '' || ($parts['pass'] ?? '') !== '') {
            return '';
        }

        $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));
        if ($host === '' || in_array($host, ['localhost', 'localhost.localdomain'], true) || str_ends_with($host, '.local')) {
            return '';
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP);
        if ($ip !== false && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return '';
        }

        return $url;
    }

    private function normalize_date(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date instanceof DateTimeImmutable ? $date->format('Y-m-d') : null;
    }

    private function clean_setting(string $value, string $key): string
    {
        if (str_contains($key, 'color') || str_contains($key, 'bg_from') || str_contains($key, 'bg_to')) {
            $value = trim($value);
            return preg_match('/^#[0-9a-fA-F]{3,6}$/', $value) === 1 || preg_match('/^rgba?\([0-9.,\s]+\)$/', $value) === 1 ? $value : '';
        }

        if ($key === 'archive_per_page') {
            return (string) max(3, min(100, (int) $value));
        }

        if ($key === 'design_border_radius') {
            return (string) max(0, min(32, (int) $value));
        }

        if ($key === 'design_grid_columns') {
            return $this->allow_value($value, ['auto', '2', '3', '4']) ?? 'auto';
        }

        return $this->clean_text($value, 255);
    }
    private function json_response(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function json_error(string $msg, int $status = 400): void
    {
        $this->json_response(['success' => false, 'error' => $msg], $status);
    }
}
