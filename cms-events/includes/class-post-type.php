<?php
/**
 * Post Type Handler für CMS Events
 *
 * @package CMS_Events
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Events_Post_Type
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

    private function init_hooks(): void
    {
        CMS\Hooks::addAction('register_routes', [$this, 'register_routes'], 10);
        CMS\Hooks::addAction('main_nav', [$this, 'add_menu_item'], 10);
    }

    public function register_routes($router): void
    {
        // Frontend Routes
        $router->addRoute('GET', '/events', [$this, 'archive_page']);
        $router->addRoute('GET', '/events/calendar', [$this, 'calendar_view']);
        $router->addRoute('GET', '/events/:id', [$this, 'single_page']);
        $router->addRoute('GET', '/event/:slug', [$this, 'single_page_by_slug']);
        $router->addRoute('GET', '/events/:id/ical', [$this, 'export_ical']);

        // Admin Routes
        $router->addRoute('GET',  '/admin/events',                          [$this, 'admin_list']);
        $router->addRoute('GET',  '/admin/events/new',                      [$this, 'admin_create']);
        $router->addRoute('POST', '/admin/events/save',                     [$this, 'admin_save']);
        $router->addRoute('GET',  '/admin/events/edit/:id',                 [$this, 'admin_edit']);
        $router->addRoute('POST', '/admin/events/delete/:id',               [$this, 'admin_delete']);
        $router->addRoute('POST', '/admin/events/approve/:id',              [$this, 'admin_approve']);
        $router->addRoute('POST', '/admin/events/settings/save',            [$this, 'admin_settings_save']);
        $router->addRoute('POST', '/admin/events/category/add',             [$this, 'admin_category_add']);
        $router->addRoute('POST', '/admin/events/category/delete/:id',      [$this, 'admin_category_delete']);
        $router->addRoute('POST', '/admin/events/tagpreset/add',            [$this, 'admin_tagpreset_add']);
        $router->addRoute('POST', '/admin/events/tagpreset/delete/:id',     [$this, 'admin_tagpreset_delete']);
        $router->addRoute('POST', '/admin/events/speaker/add',               [$this, 'admin_speaker_add']);
        $router->addRoute('POST', '/admin/events/speaker/remove/:id',        [$this, 'admin_speaker_remove']);
    }

    public function add_menu_item(): void
    {
        $current_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $is_active = strpos($current_path, '/events') === 0 ? 'active' : '';
        
        echo '<a href="' . SITE_URL . '/events" class="nav-link ' . $is_active . '">Events</a>';
    }

    public function archive_page(): void
    {
        // Öffentliche Ansicht – keine Abo-Prüfung, Erstellung ist separat geschützt
        $db_manager = CMS_Events_Database::instance();
        $settings   = $db_manager->get_settings();

        $filter_category = $_GET['category'] ?? null;
        $filter_city     = $_GET['city']     ?? null;
        $filter_month    = $_GET['month']    ?? null;
        $filter_online   = isset($_GET['online']) ? (int)$_GET['online'] : null;
        $when_filter     = $_GET['when']     ?? null;  // upcoming|past
        $search          = trim($_GET['search'] ?? '');
        $page            = max(1, (int)($_GET['page'] ?? 1));
        $per_page        = max(6, (int)($settings['per_page'] ?? 12));

        $args = [
            'status' => 'published',
            'limit'  => $per_page,
            'offset' => ($page - 1) * $per_page,
        ];

        if ($when_filter === 'past') {
            $args['past']     = true;
        } elseif ($when_filter === 'upcoming') {
            $args['upcoming'] = true;
        }
        // else: kein Datumsfilter → alle Events anzeigen

        if ($filter_category)         $args['category']  = $filter_category;
        if ($filter_city)             $args['city']       = $filter_city;
        if ($filter_month)            $args['month']      = $filter_month;
        if ($search)                  $args['search']     = $search;
        if ($filter_online !== null)  $args['is_online']  = $filter_online;

        $events     = $db_manager->get_events($args);
        $total      = $db_manager->count_events(array_diff_key($args, array_flip(['limit', 'offset'])));
        $pages      = max(1, (int)ceil($total / $per_page));
        $categories = $db_manager->get_distinct_categories();

        $tm = \CMS\ThemeManager::instance();
        $tm->getHeader();
        $template_loader = CMS_Events_Template_Loader::instance();
        $template_loader->render_template('archive-event', [
            'events'          => $events,
            'settings'        => $settings,
            'current_page'    => $page,
            'per_page'        => $per_page,
            'pages'           => $pages,
            'total'           => $total,
            'categories'      => $categories,
            'filter_category' => $filter_category,
            'filter_city'     => $filter_city,
            'filter_month'    => $filter_month,
            'filter_online'   => $filter_online,
            'when_filter'     => $when_filter,
            'search'          => $search,
        ]);
        $tm->getFooter();
    }

    public function calendar_view(): void
    {
        $db_manager = CMS_Events_Database::instance();
        
        $month = $_GET['month'] ?? date('Y-m');
        $view = $_GET['view'] ?? 'month'; // 'month' or 'week'

        $args = [
            'status' => 'published',
            'month' => $month,
        ];

        $events = $db_manager->get_events($args);

        $tm = \CMS\ThemeManager::instance();
        $tm->getHeader();
        $template_loader = CMS_Events_Template_Loader::instance();
        $template_loader->render_template('calendar-view', [
            'events' => $events,
            'month'  => $month,
            'view'   => $view,
        ]);
        $tm->getFooter();
    }

    public function single_page(string $id = ''): void
    {
        $event_id = $id !== '' ? (int)$id : (int)($_GET['id'] ?? 0);

        if ($event_id <= 0) {
            $this->render_404();
            return;
        }

        $db_manager = CMS_Events_Database::instance();
        $event      = $db_manager->get_event($event_id);

        if (!$event || $event->status !== 'published') {
            $this->render_404();
            return;
        }

        // 301-Redirect zur kanonischen Slug-URL
        header('Location: ' . cms_event_url($event), true, 301);
        exit;
    }

    public function single_page_by_slug(string $slug = ''): void
    {
        if ($slug === '') {
            $slug = $_GET['slug'] ?? '';
        }

        // Format: {titel}-{id} – ID aus dem letzten Zahl-Segment extrahieren
        if (!preg_match('/-(\d+)$/', $slug, $m)) {
            $this->render_404();
            return;
        }

        $event_id = (int)$m[1];
        if ($event_id <= 0) {
            $this->render_404();
            return;
        }

        $db_manager = CMS_Events_Database::instance();
        $event      = $db_manager->get_event($event_id);

        if (!$event || $event->status !== 'published') {
            $this->render_404();
            return;
        }

        $speakers = $db_manager->get_event_speakers($event_id);
        $settings = $db_manager->get_settings();

        $tm = \CMS\ThemeManager::instance();
        $tm->getHeader();
        CMS_Events_Template_Loader::instance()->render_template('single-event', [
            'event'    => $event,
            'speakers' => $speakers,
            'settings' => $settings,
        ]);
        $tm->getFooter();
    }

    public function export_ical(): void
    {
        $event_id = (int)($_GET['id'] ?? 0);
        
        if ($event_id <= 0) {
            http_response_code(404);
            exit;
        }

        $db_manager = CMS_Events_Database::instance();
        $event = $db_manager->get_event($event_id);

        if (!$event || $event->status !== 'published') {
            http_response_code(404);
            exit;
        }

        $this->generate_ical($event);
    }

    private function generate_ical($event): void
    {
        $start_datetime = new DateTime($event->event_date . ' ' . ($event->event_time ?? '00:00:00'));
        $end_datetime = $event->end_date 
            ? new DateTime($event->end_date . ' ' . ($event->end_time ?? '23:59:59'))
            : clone $start_datetime->modify('+2 hours');

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//CMS Events//NONSGML v1.0//EN\r\n";
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:" . $event->id . "@" . $_SERVER['HTTP_HOST'] . "\r\n";
        $ical .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
        $ical .= "DTSTART:" . $start_datetime->format('Ymd\THis') . "\r\n";
        $ical .= "DTEND:" . $end_datetime->format('Ymd\THis') . "\r\n";
        $ical .= "SUMMARY:" . $this->escape_ical($event->title) . "\r\n";
        
        if ($event->description) {
            $ical .= "DESCRIPTION:" . $this->escape_ical(strip_tags($event->description)) . "\r\n";
        }
        
        if ($event->location) {
            $location = $event->location;
            if ($event->city) {
                $location .= ', ' . $event->city;
            }
            $ical .= "LOCATION:" . $this->escape_ical($location) . "\r\n";
        }
        
        if ($event->online_url) {
            $ical .= "URL:" . $event->online_url . "\r\n";
        }
        
        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR\r\n";

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="event-' . $event->id . '.ics"');
        echo $ical;
        exit;
    }

    private function escape_ical(string $text): string
    {
        $text = str_replace(["\r\n", "\n", "\r"], "\\n", $text);
        $text = str_replace([',', ';', '\\'], ['\\,', '\\;', '\\\\'], $text);
        return $text;
    }

    public function admin_create(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        CMS_Events_Admin::instance()->render_form();
    }

    public function admin_edit(string $id = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        $event_id = (int)$id;

        if ($event_id <= 0) {
            CMS\Router::instance()->redirect('/admin/events');
            return;
        }

        $db_manager = CMS_Events_Database::instance();
        $event = $db_manager->get_event($event_id);

        if (!$event) {
            CMS\Router::instance()->redirect('/admin/events');
            return;
        }

        CMS_Events_Admin::instance()->render_form($event);
    }

    public function admin_save(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            CMS\Router::instance()->redirect('/admin/events');
            return;
        }

        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!CMS\Security::instance()->verifyToken($csrf_token, 'save_event')) {
            CMS\Router::instance()->redirect('/admin/events?error=csrf');
            return;
        }

        $event_id   = (int)($_POST['event_id'] ?? 0);
        $db_manager = CMS_Events_Database::instance();

        // Beschreibung: HTML-Sanitierung + Inline-Style-Attribute entfernen.
        // html_entity_decode() als Schutz: falls Entities bereits kodiert übermittelt wurden.
        $desc_raw    = html_entity_decode($_POST['description'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $desc_raw    = \CMS\Services\EditorService::getInstance()->sanitize($desc_raw);
        $description = preg_replace('/\s+style\s*=\s*(?:"[^"]*"|\x27[^\x27]*\x27)/i', '', $desc_raw) ?? $desc_raw;

        $data = [
            'title'             => trim($_POST['title'] ?? ''),
            'excerpt'           => trim($_POST['excerpt'] ?? ''),
            'description'       => $description,
            'event_date'        => $_POST['event_date']  ?? null,
            'event_time'        => $_POST['event_time']  ?? null,
            'end_date'          => $_POST['end_date']    ?? null,
            'end_time'          => $_POST['end_time']    ?? null,
            'location'          => trim($_POST['location']  ?? ''),
            'address'           => trim($_POST['address']   ?? ''),
            'city'              => trim($_POST['city']      ?? ''),
            'zip'               => trim($_POST['zip']       ?? ''),
            'country'           => trim($_POST['country']   ?? 'Deutschland'),
            'category'          => trim($_POST['category']  ?? ''),
            'tags'              => isset($_POST['tags']) && is_array($_POST['tags']) ? $_POST['tags'] : [],
            'capacity'          => (int)($_POST['capacity'] ?? 0) ?: null,
            'registration_url'  => trim($_POST['registration_url'] ?? ''),
            'price_type'        => $_POST['price_type']     ?? 'free',
            'price'             => !empty($_POST['price']) ? (float)$_POST['price'] : null,
            'price_currency'    => $_POST['price_currency']  ?? 'EUR',
            'image_url'         => trim($_POST['image_url']  ?? ''),
            'banner_url'        => trim($_POST['banner_url'] ?? ''),
            'is_online'         => isset($_POST['is_online'])   ? 1 : 0,
            'online_url'        => trim($_POST['online_url']    ?? ''),
            'is_featured'       => isset($_POST['is_featured']) ? 1 : 0,
            'organizer_name'    => trim($_POST['organizer_name']    ?? ''),
            'organizer_email'   => trim($_POST['organizer_email']   ?? ''),
            'organizer_phone'   => trim($_POST['organizer_phone']   ?? ''),
            'organizer_website' => trim($_POST['organizer_website'] ?? ''),
            'status'            => $_POST['status'] ?? 'published',
        ];

        if (empty($data['title'])) {
            CMS\Router::instance()->redirect('/admin/events' . ($event_id > 0 ? '/edit/' . $event_id : '/new') . '?error=validation');
            return;
        }

        try {
            if ($event_id > 0) { $data['id'] = $event_id; }
            $event_id = $db_manager->save_event($data);
            CMS\Router::instance()->redirect('/admin/events/edit/' . $event_id . '?success=1');
        } catch (\Throwable $e) {
            error_log('Event save error: ' . $e->getMessage());
            CMS\Router::instance()->redirect('/admin/events?error=save');
        }
    }

    public function admin_delete(string $id = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            CMS\Router::instance()->redirect('/admin/events');
            return;
        }

        $event_id   = (int)$id;
        $csrf_token = $_POST['csrf_token'] ?? '';

        if (!CMS\Security::instance()->verifyToken($csrf_token, 'delete_event')) {
            CMS\Router::instance()->redirect('/admin/events?error=csrf');
            return;
        }

        if ($event_id <= 0) {
            CMS\Router::instance()->redirect('/admin/events?error=invalid_id');
            return;
        }

        try {
            CMS_Events_Database::instance()->delete_event($event_id);
            CMS\Router::instance()->redirect('/admin/events?deleted=1');
        } catch (\Throwable $e) {
            error_log('Event delete error: ' . $e->getMessage());
            CMS\Router::instance()->redirect('/admin/events?error=delete');
        }
    }

    public function admin_approve(string $id = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            CMS\Router::instance()->redirect('/admin/events');
            return;
        }

        $event_id   = (int)$id;
        $csrf_token = $_POST['csrf_token'] ?? '';

        if (!CMS\Security::instance()->verifyToken($csrf_token, 'event_settings')) {
            CMS\Router::instance()->redirect('/admin/events?error=csrf');
            return;
        }

        if ($event_id <= 0) {
            CMS\Router::instance()->redirect('/admin/events?error=invalid_id');
            return;
        }

        $result = CMS_Events_Database::instance()->set_event_status($event_id, 'published');

        if ($result) {
            CMS\Router::instance()->redirect('/admin/events?approved=1');
        } else {
            CMS\Router::instance()->redirect('/admin/events?error=approve');
        }
    }

    public function admin_settings_save(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { CMS\Router::instance()->redirect('/login'); return; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { CMS\Router::instance()->redirect('/admin/events'); return; }

        $csrf  = $_POST['csrf_token'] ?? '';
        $tab   = $_POST['_from_tab']  ?? 'settings';
        if (!CMS\Security::instance()->verifyToken($csrf, 'event_settings')) {
            CMS\Router::instance()->redirect('/admin/events?tab=' . $tab . '&error=csrf');
            return;
        }

        // Tab-spezifische Feldgruppen: jeder Tab speichert NUR seine eigenen Felder.
        // So überschreibt "Einstellungen speichern" nie Design-Werte und umgekehrt.
        $tab_fields = [
            'settings' => [
                'text'      => ['archive_title', 'archive_description', 'archive_slug', 'per_page'],
                'checkboxes'=> [],
            ],
            'design' => [
                'text'      => [
                    'grid_columns', 'archive_header_icon', 'border_radius',
                    'color_primary', 'color_accent', 'color_hdr_from', 'color_hdr_to',
                    'color_hdr_title', 'color_card_bg', 'color_card_border', 'color_cta',
                    'color_detail_hdr_bg', 'color_detail_hdr_text', 'color_detail_accent',
                    'color_featured_border', 'color_cancelled_bg', 'color_online_badge',
                ],
                'checkboxes'=> ['show_category','show_city','show_capacity','show_speakers','show_price','show_organizer','show_tags'],
            ],
        ];

        $group  = $tab_fields[$tab] ?? null;
        if ($group === null) {
            // Unbekannter Tab → nichts speichern
            CMS\Router::instance()->redirect('/admin/events?tab=' . $tab . '&error=unknown_tab');
            return;
        }

        $settings = [];

        // Nur Text-/Zahl-Felder des aktuellen Tabs speichern
        foreach ($group['text'] as $key) {
            if (array_key_exists($key, $_POST)) {
                $settings[$key] = trim((string)$_POST[$key]);
            }
        }

        // Checkboxen des aktuellen Tabs: unchecked = '0', checked = '1'
        foreach ($group['checkboxes'] as $key) {
            $settings[$key] = isset($_POST[$key]) ? '1' : '0';
        }

        CMS_Events_Database::instance()->save_settings($settings);
        CMS\Router::instance()->redirect('/admin/events?tab=' . $tab . '&saved=1');
    }

    public function admin_list(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { CMS\Router::instance()->redirect('/login'); return; }

        $db      = CMS_Events_Database::instance();
        $tab     = $_GET['tab']    ?? 'overview';
        $filter  = $_GET['filter'] ?? 'all';

        $events     = $db->get_events(['limit' => 200]);
        $categories = $db->get_event_categories();
        $tag_presets= $db->get_event_tag_presets_grouped();
        $settings   = $db->get_settings();
        $csrf       = CMS\Security::instance()->generateToken('event_settings');

        CMS_Events_Admin::instance()->render_list([
            'events'      => $events,
            'tab'         => $tab,
            'filter'      => $filter,
            'categories'  => $categories,
            'tag_presets' => $tag_presets,
            'settings'    => $settings,
            'csrf'        => $csrf,
        ]);
    }

    public function admin_category_add(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { CMS\Router::instance()->redirect('/login'); return; }
        $csrf = $_POST['csrf_token'] ?? '';
        if (!CMS\Security::instance()->verifyToken($csrf, 'event_settings')) {
            CMS\Router::instance()->redirect('/admin/events?tab=categories&error=csrf');
            return;
        }
        $name = trim($_POST['category_name'] ?? '');
        $icon = trim($_POST['category_icon'] ?? '📂');
        if ($name) CMS_Events_Database::instance()->add_event_category($name, $icon);
        CMS\Router::instance()->redirect('/admin/events?tab=categories');
    }

    public function admin_category_delete(string $id = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { CMS\Router::instance()->redirect('/login'); return; }
        $csrf = $_POST['csrf_token'] ?? '';
        if (!CMS\Security::instance()->verifyToken($csrf, 'event_settings')) {
            CMS\Router::instance()->redirect('/admin/events?tab=categories&error=csrf');
            return;
        }
        CMS_Events_Database::instance()->delete_event_category((int)$id);
        CMS\Router::instance()->redirect('/admin/events?tab=categories');
    }

    public function admin_tagpreset_add(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { CMS\Router::instance()->redirect('/login'); return; }
        $csrf = $_POST['csrf_token'] ?? '';
        if (!CMS\Security::instance()->verifyToken($csrf, 'event_settings')) {
            CMS\Router::instance()->redirect('/admin/events?tab=tags&error=csrf');
            return;
        }
        $name = trim($_POST['tag_name'] ?? '');
        $type = trim($_POST['tag_type'] ?? 'general');
        if ($name) CMS_Events_Database::instance()->add_event_tag_preset($name, $type);
        CMS\Router::instance()->redirect('/admin/events?tab=tags');
    }

    public function admin_tagpreset_delete(string $id = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { CMS\Router::instance()->redirect('/login'); return; }
        $csrf = $_POST['csrf_token'] ?? '';
        if (!CMS\Security::instance()->verifyToken($csrf, 'event_settings')) {
            CMS\Router::instance()->redirect('/admin/events?tab=tags&error=csrf');
            return;
        }
        CMS_Events_Database::instance()->delete_event_tag_preset((int)$id);
        CMS\Router::instance()->redirect('/admin/events?tab=tags');
    }

    public function admin_speaker_add(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { http_response_code(403); echo json_encode(['error'=>'Unauthorized']); exit; }
        header('Content-Type: application/json');
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'event_speaker')) {
            http_response_code(403); echo json_encode(['error'=>'CSRF']); exit;
        }
        $event_id    = (int)($_POST['event_id']    ?? 0);
        $speaker_id  = (int)($_POST['speaker_id']  ?? 0);
        $speaker_type = in_array($_POST['speaker_type'] ?? '', ['speaker','expert'], true)
            ? $_POST['speaker_type'] : 'speaker';
        if ($event_id <= 0 || $speaker_id <= 0) {
            http_response_code(400); echo json_encode(['error'=>'Invalid data']); exit;
        }
        $db  = CMS_Events_Database::instance();
        $ok  = $db->assign_speaker($event_id, $speaker_id, $speaker_type, [
            'presentation_title' => trim($_POST['presentation_title'] ?? ''),
            'session_time'       => $_POST['session_time'] ?: null,
            'role'               => trim($_POST['role'] ?? ''),
        ]);
        echo json_encode(['success' => $ok]);
        exit;
    }

    public function admin_speaker_remove(string $id = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { http_response_code(403); echo json_encode(['error'=>'Unauthorized']); exit; }
        header('Content-Type: application/json');
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'event_speaker')) {
            http_response_code(403); echo json_encode(['error'=>'CSRF']); exit;
        }
        $assignment_id = $id !== '' ? (int)$id : (int)($_POST['id'] ?? 0);
        if ($assignment_id <= 0) { http_response_code(400); echo json_encode(['error'=>'Invalid id']); exit; }
        $ok = CMS_Events_Database::instance()->remove_event_speaker($assignment_id);
        echo json_encode(['success' => $ok]);
        exit;
    }

    private function render_404(): void
    {
        http_response_code(404);
        echo '<div class="error-404"><h1>Event nicht gefunden</h1></div>';
        exit;
    }
}

// ── Globaler Helper: kanonische Event-URL ─────────────────────────────────
if (!function_exists('cms_event_url')) {
    function cms_event_url(object $event): string
    {
        $map   = ['ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss','Ä'=>'ae','Ö'=>'oe','Ü'=>'ue'];
        $title = str_replace(array_keys($map), array_values($map), (string)($event->title ?? ''));
        $slug  = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug  = trim($slug, '-') ?: 'event';
        return SITE_URL . '/event/' . $slug . '-' . (int)$event->id;
    }
}
