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

if (class_exists('CMS_Events_Post_Type', false)) {
    return;
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

        // Explicit 405 for browser GETs against POST-only admin endpoints.
        $router->addRoute('GET', '/admin/events/save',                     [$this, 'admin_post_method_not_allowed']);
        $router->addRoute('GET', '/admin/events/delete/:id',               [$this, 'admin_post_method_not_allowed']);
        $router->addRoute('GET', '/admin/events/approve/:id',              [$this, 'admin_post_method_not_allowed']);
        $router->addRoute('GET', '/admin/events/settings/save',            [$this, 'admin_post_method_not_allowed']);
        $router->addRoute('GET', '/admin/events/category/add',             [$this, 'admin_post_method_not_allowed']);
        $router->addRoute('GET', '/admin/events/category/delete/:id',      [$this, 'admin_post_method_not_allowed']);
        $router->addRoute('GET', '/admin/events/tagpreset/add',            [$this, 'admin_post_method_not_allowed']);
        $router->addRoute('GET', '/admin/events/tagpreset/delete/:id',     [$this, 'admin_post_method_not_allowed']);
        $router->addRoute('GET', '/admin/events/speaker/add',              [$this, 'admin_post_method_not_allowed']);
        $router->addRoute('GET', '/admin/events/speaker/remove/:id',       [$this, 'admin_post_method_not_allowed']);
    }

    public function admin_post_method_not_allowed(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        header('Allow: POST');
        $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if (str_contains($path, '/speaker/')) {
            $this->json_response(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        http_response_code(405);
        echo '<!DOCTYPE html><html lang="de"><body><h1>Methode nicht erlaubt</h1><p>Dieser Endpunkt akzeptiert nur POST.</p></body></html>';
        exit;
    }

    public function add_menu_item(): void
    {
        $settings = [];
        try {
            $settings = CMS_Events_Database::instance()->get_settings();
        } catch (\Throwable $e) {
            error_log('CMS Events nav settings fallback: ' . $e->getMessage());
        }

        if ((string) ($settings['show_nav_link'] ?? '0') !== '1') {
            return;
        }

        $current_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $is_active = str_starts_with((string) $current_path, '/events') || str_starts_with((string) $current_path, '/event/') ? 'active' : '';
        $label = trim((string) ($settings['nav_label'] ?? 'Veranstaltungen'));
        if ($label === '') {
            $label = 'Veranstaltungen';
        }
        
        echo '<a href="' . htmlspecialchars((string) SITE_URL, ENT_QUOTES, 'UTF-8') . '/events" class="nav-link ' . htmlspecialchars($is_active, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
    }

    public function archive_page(): void
    {
        try {
        // Öffentliche Ansicht – keine Abo-Prüfung, Erstellung ist separat geschützt
        $db_manager = CMS_Events_Database::instance();
        $settings = [
            'archive_slug' => 'events',
            'archive_title' => 'Veranstaltungen',
            'per_page' => 12,
        ];
        try {
            $loadedSettings = $db_manager->get_settings();
            if (is_array($loadedSettings)) {
                $settings = array_merge($settings, $loadedSettings);
            }
        } catch (\Throwable $settingsError) {
            error_log('CMS Events archive settings fallback: ' . $settingsError->getMessage());
        }

        $filter_category = $this->sanitize_text_param($_GET['category'] ?? null, 100);
        $filter_city     = $this->sanitize_text_param($_GET['city'] ?? null, 100);
        if ($filter_category === '0') {
            $filter_category = null;
        }
        if ($filter_city === '0') {
            $filter_city = null;
        }
        [$filter_month, $filter_month_number, $filter_year] = $this->sanitize_archive_date_filter($_GET['month'] ?? null, $_GET['year'] ?? null);
        $filter_online   = $this->sanitize_binary_filter($_GET['online'] ?? null);
        $when_filter     = in_array((string) ($_GET['when'] ?? ''), ['upcoming', 'past'], true) ? (string) $_GET['when'] : null;
        $search          = $this->sanitize_text_param($_GET['search'] ?? '', 120) ?? '';
        $page            = max(1, min(500, (int)($_GET['page'] ?? 1)));
        $per_page        = max(6, min(100, (int)($settings['per_page'] ?? 12)));
        $default_from_month = date('Y-m-01');
        $date_filter_explicit = array_key_exists('month', $_GET) || array_key_exists('year', $_GET);
        $has_explicit_filters = $filter_category !== null
            || $filter_city !== null
            || $date_filter_explicit
            || $filter_online !== null
            || $when_filter !== null
            || $search !== '';

        $args = [
            'status' => 'published',
        ];

        if ($when_filter === 'past') {
            $args['past']     = true;
        } elseif ($when_filter === 'upcoming') {
            $args['upcoming'] = true;
        } elseif (!$date_filter_explicit) {
            $args['from_month'] = $default_from_month;
        }

        if ($filter_category !== null) $args['category']  = $filter_category;
        if ($filter_city !== null)     $args['city']       = $filter_city;
        if ($filter_month !== null)    $args['month']      = $filter_month;
        if ($filter_month === null && $filter_year !== null) $args['year'] = $filter_year;
        if ($filter_month === null && $filter_month_number !== null) $args['month_number'] = $filter_month_number;
        if ($search !== '')            $args['search']     = $search;
        if ($filter_online !== null)  $args['is_online']  = $filter_online;

        $total = 0;
        try {
            $total = $db_manager->count_events($args);
        } catch (\Throwable $countError) {
            error_log('CMS Events archive count fallback: ' . $countError->getMessage());
            $total = 0;
        }

        $pages = max(1, (int) ceil(max(0, (int) $total) / $per_page));
        $page = min($page, $pages);

        $events = [];
        try {
            $events = $db_manager->get_events($args + [
                'limit'  => $per_page,
                'offset' => ($page - 1) * $per_page,
            ]);
        } catch (\Throwable $eventsError) {
            error_log('CMS Events archive events fallback: ' . $eventsError->getMessage());
            $events = [];
        }

        $upcoming_total = 0;
        try {
            $upcoming_total = $db_manager->count_events(['status' => 'published', 'from_month' => $default_from_month]);
        } catch (\Throwable $upcomingError) {
            error_log('CMS Events archive upcoming fallback: ' . $upcomingError->getMessage());
            $upcoming_total = 0;
        }

        $categories = [];
        try {
            $categories = $db_manager->get_distinct_categories();
        } catch (\Throwable $categoriesError) {
            error_log('CMS Events archive categories fallback: ' . $categoriesError->getMessage());
            $categories = [];
        }

        $event_speakers_map = $this->get_event_speakers_map($events);
        $active_filter_params = $this->build_archive_filter_params(
            $filter_category,
            $filter_city,
            $filter_month_number,
            $filter_year,
            $filter_online,
            $when_filter,
            $search,
            $has_explicit_filters,
            $date_filter_explicit
        );

        $this->render_public_theme_template('archive-event', [
            'events'          => $events,
            'settings'        => $settings,
            'current_page'    => $page,
            'per_page'        => $per_page,
            'pages'           => $pages,
            'total'           => $total,
            'upcoming_total'  => $upcoming_total,
            'categories'      => $categories,
            'event_speakers_map' => $event_speakers_map,
            'filter_category' => $filter_category,
            'filter_city'     => $filter_city,
            'filter_month'    => $filter_month,
            'filter_month_number' => $filter_month_number,
            'filter_year'     => $filter_year,
            'filter_online'   => $filter_online,
            'when_filter'     => $when_filter,
            'search'          => $search,
            'has_active_filters' => $has_explicit_filters,
            'date_filter_explicit' => $date_filter_explicit,
            'default_from_month' => $default_from_month,
            'active_filter_params' => $active_filter_params,
        ]);
        } catch (\Throwable $e) {
            error_log('CMS Events archive hard fallback: ' . $e->getMessage());
            $this->render_public_theme_template('archive-event', [
                'events' => [],
                'settings' => [
                    'archive_slug' => 'events',
                    'archive_title' => 'Veranstaltungen',
                    'per_page' => 12,
                ],
                'current_page' => 1,
                'per_page' => 12,
                'pages' => 1,
                'total' => 0,
                'upcoming_total' => 0,
                'categories' => [],
                'event_speakers_map' => [],
                'filter_category' => null,
                'filter_city' => null,
                'filter_month' => null,
                'filter_month_number' => null,
                'filter_year' => null,
                'filter_online' => null,
                'when_filter' => null,
                'search' => '',
                'has_active_filters' => false,
                'date_filter_explicit' => false,
                'default_from_month' => date('Y-m-01'),
                'active_filter_params' => [],
            ]);
        }
    }

    public function calendar_view(): void
    {
        try {
        $db_manager = CMS_Events_Database::instance();
        $settings   = $db_manager->get_settings();
        
        $month = $this->sanitize_month($_GET['month'] ?? null) ?? date('Y-m');
        $view = in_array((string) ($_GET['view'] ?? 'month'), ['month', 'week'], true) ? (string) $_GET['view'] : 'month';

        $args = [
            'status' => 'published',
            'month' => $month,
        ];

        $events = $db_manager->get_events($args);

        $this->render_public_theme_template('calendar-view', [
            'events'   => $events,
            'month'    => $month,
            'view'     => $view,
            'settings' => $settings,
        ]);
        } catch (\Throwable $e) {
            $this->render_public_error('Der Event-Kalender konnte nicht geladen werden.', $e);
        }
    }

    public function single_page(string $id = ''): void
    {
        try {
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

        $this->render_event_detail($db_manager, $event, $event_id);
        } catch (\Throwable $e) {
            $this->render_public_error('Das Event konnte nicht geladen werden.', $e);
        }
    }

    public function single_page_by_slug(string $slug = ''): void
    {
        try {
        if ($slug === '') {
            $slug = (string) ($_GET['slug'] ?? '');
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

        $this->render_event_detail($db_manager, $event, $event_id);
        } catch (\Throwable $e) {
            $this->render_public_error('Das Event konnte nicht geladen werden.', $e);
        }
    }

    private function render_event_detail(CMS_Events_Database $db_manager, object $event, int $event_id): void
    {
        $speakers = [];
        try {
            $speakers = $db_manager->get_event_speakers($event_id);
        } catch (\Throwable $speakerError) {
            error_log('CMS Events detail speakers fallback: ' . $speakerError->getMessage());
        }

        $settings = [
            'archive_slug' => 'events',
            'archive_title' => 'Veranstaltungen',
        ];
        try {
            $loadedSettings = $db_manager->get_settings();
            if (is_array($loadedSettings)) {
                $settings = array_merge($settings, $loadedSettings);
            }
        } catch (\Throwable $settingsError) {
            error_log('CMS Events detail settings fallback: ' . $settingsError->getMessage());
        }

        $related_events = [];
        try {
            $related_events = $this->get_related_events($db_manager, $event);
        } catch (\Throwable $relatedError) {
            error_log('CMS Events detail related fallback: ' . $relatedError->getMessage());
        }

        $this->render_public_theme_template('single-event', [
            'event'          => $event,
            'speakers'       => is_array($speakers) ? $speakers : [],
            'settings'       => $settings,
            'related_events' => is_array($related_events) ? $related_events : [],
        ]);
    }

    private function get_event_speakers_map(array $events): array
    {
        $event_ids = array_values(array_unique(array_filter(array_map(
            static fn($event): int => is_object($event) ? max(0, (int) ($event->id ?? 0)) : 0,
            $events
        ))));

        if (empty($event_ids)) {
            return [];
        }

        try {
            $db = CMS\Database::instance();
            $prefix = $db->prefix();
            $placeholders = implode(',', array_fill(0, count($event_ids), '?'));
            $stmt = $db->prepare(
                "SELECT es.*,
                        COALESCE(s.first_name,    ex.first_name)    AS first_name,
                        COALESCE(s.last_name,     ex.last_name)     AS last_name,
                        COALESCE(s.photo_url,     ex.photo_url)     AS photo_url,
                        COALESCE(s.position,      ex.position)      AS position,
                        COALESCE(s.short_bio,     ex.biography)     AS short_bio,
                        COALESCE(s.company,       ex.company)       AS company,
                        COALESCE(s.location_city, ex.location_city) AS location_city,
                        CASE
                            WHEN es.speaker_type = 'speaker' THEN CONCAT(s.first_name, ' ', s.last_name)
                            WHEN es.speaker_type = 'expert'  THEN CONCAT(ex.first_name, ' ', ex.last_name)
                        END AS speaker_name
                 FROM {$prefix}event_speakers es
                 LEFT JOIN {$prefix}speakers s ON es.speaker_id = s.id AND es.speaker_type = 'speaker'
                 LEFT JOIN {$prefix}experts ex ON es.speaker_id = ex.id AND es.speaker_type = 'expert'
                 WHERE es.event_id IN ({$placeholders})
                 ORDER BY es.event_id ASC, es.session_time ASC, es.id ASC"
            );
            $stmt->execute($event_ids);

            $map = [];
            foreach ($stmt->fetchAll() as $row) {
                $event_id = (int) ($row->event_id ?? 0);
                if ($event_id > 0) {
                    $map[$event_id][] = $row;
                }
            }

            return $map;
        } catch (\Throwable $e) {
            error_log('CMS Events speaker archive preload skipped: ' . $e->getMessage());
            return [];
        }
    }

    private function get_related_events(CMS_Events_Database $db_manager, object $event): array
    {
        $args = ['status' => 'published', 'upcoming' => true, 'limit' => 4];
        $category = trim((string) ($event->category ?? ''));
        if ($category !== '') {
            $args['category'] = $category;
        }

        return array_slice(array_values(array_filter(
            $db_manager->get_events($args),
            static fn(object $item): bool => (int) ($item->id ?? 0) !== (int) ($event->id ?? 0)
        )), 0, 3);
    }

    public function export_ical(string $id = ''): void
    {
        try {
        $event_id = $id !== '' ? (int) $id : (int)($_GET['id'] ?? 0);
        
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
        } catch (\Throwable $e) {
            error_log('CMS Events iCal export error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            echo 'Kalenderdatei konnte nicht erstellt werden.';
            exit;
        }
    }

    private function generate_ical($event): void
    {
        $start_datetime = $this->create_event_datetime((string) ($event->event_date ?? ''), (string) ($event->event_time ?? '00:00:00'));
        if (!$start_datetime instanceof DateTimeImmutable) {
            http_response_code(404);
            exit;
        }

        $end_datetime = !empty($event->end_date)
            ? $this->create_event_datetime((string) $event->end_date, (string) ($event->end_time ?? '23:59:59'))
            : $start_datetime->modify('+2 hours');

        if (!$end_datetime instanceof DateTimeImmutable || $end_datetime <= $start_datetime) {
            $end_datetime = $start_datetime->modify('+2 hours');
        }

        $host = $this->get_safe_calendar_host();
        $eventId = max(0, (int) ($event->id ?? 0));

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//CMS Events//NONSGML v1.0//EN\r\n";
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= $this->format_ical_line('UID', $eventId . '@' . $host);
        $ical .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
        $ical .= "DTSTART:" . $start_datetime->format('Ymd\THis') . "\r\n";
        $ical .= "DTEND:" . $end_datetime->format('Ymd\THis') . "\r\n";
        $ical .= $this->format_ical_line('SUMMARY', (string) ($event->title ?? 'Event'));
        
        if (!empty($event->description)) {
            $ical .= $this->format_ical_line('DESCRIPTION', strip_tags((string) $event->description));
        }
        
        if (!empty($event->location)) {
            $location = (string) $event->location;
            if (!empty($event->city)) {
                $location .= ', ' . $event->city;
            }
            $ical .= $this->format_ical_line('LOCATION', $location);
        }
        
        $onlineUrl = cms_events_public_url($event->online_url ?? null);
        if ($onlineUrl !== '') {
            $ical .= $this->format_ical_line('URL', $onlineUrl);
        }
        
        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR\r\n";

        header('Content-Type: text/calendar; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="event-' . $eventId . '.ics"');
        header('Content-Length: ' . (string) strlen($ical));
        echo $ical;
        exit;
    }

    private function create_event_datetime(string $date, string $time): ?DateTimeImmutable
    {
        $date = $this->sanitize_date($date) ?? '';
        $time = $this->sanitize_time($time) ?? '00:00:00';
        if ($date === '') {
            return null;
        }

        $format = strlen($time) === 5 ? '!Y-m-d H:i' : '!Y-m-d H:i:s';
        $dt = DateTimeImmutable::createFromFormat($format, $date . ' ' . $time);

        return $dt instanceof DateTimeImmutable ? $dt : null;
    }

    private function get_safe_calendar_host(): string
    {
        $siteHost = defined('SITE_URL') ? parse_url((string) SITE_URL, PHP_URL_HOST) : null;
        $host = is_string($siteHost) ? $siteHost : (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $host = strtolower(preg_replace('/[^a-z0-9.-]/i', '', $host) ?? '');

        return $host !== '' ? $host : 'localhost';
    }

    private function format_ical_line(string $name, string $value): string
    {
        return $this->fold_ical_line($name . ':' . $this->escape_ical($value)) . "\r\n";
    }

    private function fold_ical_line(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        return rtrim(chunk_split($line, 75, "\r\n "), "\r\n ");
    }

    private function escape_ical(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(["\r\n", "\n", "\r"], "\\n", $text);
        $text = str_replace([',', ';'], ['\\,', '\\;'], $text);
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

        $csrf_token = (string) ($_POST['csrf_token'] ?? '');
        if (!CMS\Security::instance()->verifyToken($csrf_token, 'save_event')) {
            CMS\Router::instance()->redirect('/admin/events?error=csrf');
            return;
        }

        $event_id   = (int)($_POST['event_id'] ?? 0);
        $db_manager = CMS_Events_Database::instance();

        // Beschreibung: HTML-Sanitierung + Inline-Style-Attribute entfernen.
        // html_entity_decode() als Schutz: falls Entities bereits kodiert übermittelt wurden.
        $desc_raw    = html_entity_decode((string) ($_POST['description'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (class_exists('CMS\\Services\\EditorService')) {
            $desc_raw = \CMS\Services\EditorService::getInstance()->sanitize($desc_raw);
        } else {
            $desc_raw = strip_tags($desc_raw, '<p><a><strong><em><ul><ol><li><br><blockquote><h2><h3>');
        }
        $description = preg_replace('/\s+style\s*=\s*(?:"[^"]*"|\x27[^\x27]*\x27)/i', '', $desc_raw) ?? $desc_raw;

        $allowed_statuses = ['draft', 'published', 'cancelled', 'completed'];
        $allowed_price_types = ['free', 'paid', 'donation'];
        $status = in_array($_POST['status'] ?? '', $allowed_statuses, true) ? (string) $_POST['status'] : 'published';
        $price_type = in_array($_POST['price_type'] ?? '', $allowed_price_types, true) ? (string) $_POST['price_type'] : 'free';

        $data = [
            'title'             => $this->sanitize_required_text($_POST['title'] ?? '', 255),
            'excerpt'           => $this->sanitize_text_param($_POST['excerpt'] ?? '', 500) ?? '',
            'description'       => $description,
            'event_date'        => $this->sanitize_date($_POST['event_date'] ?? null),
            'event_time'        => $this->sanitize_time($_POST['event_time'] ?? null),
            'end_date'          => $this->sanitize_date($_POST['end_date'] ?? null),
            'end_time'          => $this->sanitize_time($_POST['end_time'] ?? null),
            'location'          => $this->sanitize_text_param($_POST['location'] ?? '', 255) ?? '',
            'address'           => $this->sanitize_text_param($_POST['address'] ?? '', 500) ?? '',
            'city'              => $this->sanitize_text_param($_POST['city'] ?? '', 100) ?? '',
            'zip'               => $this->sanitize_text_param($_POST['zip'] ?? '', 20) ?? '',
            'country'           => $this->sanitize_text_param($_POST['country'] ?? 'Deutschland', 100) ?? 'Deutschland',
            'category'          => $this->sanitize_text_param($_POST['category'] ?? '', 100) ?? '',
            'tags'              => isset($_POST['tags']) && is_array($_POST['tags'])
                                    ? array_values(array_filter(array_map(fn($tag) => $this->sanitize_text_param($tag, 80) ?? '', $_POST['tags'])))
                                    : [],
            'capacity'          => max(0, (int)($_POST['capacity'] ?? 0)) ?: null,
            'registration_url'  => $this->sanitize_public_url($_POST['registration_url'] ?? ''),
            'price_type'        => $price_type,
            'price'             => !empty($_POST['price']) ? max(0.0, (float)$_POST['price']) : null,
            'price_currency'    => $this->sanitize_currency($_POST['price_currency'] ?? 'EUR'),
            'image_url'         => $this->sanitize_public_url($_POST['image_url'] ?? ''),
            'banner_url'        => $this->sanitize_public_url($_POST['banner_url'] ?? ''),
            'is_online'         => isset($_POST['is_online'])   ? 1 : 0,
            'online_url'        => $this->sanitize_public_url($_POST['online_url'] ?? ''),
            'is_featured'       => isset($_POST['is_featured']) ? 1 : 0,
            'organizer_name'    => $this->sanitize_text_param($_POST['organizer_name'] ?? '', 255) ?? '',
            'organizer_email'   => filter_var(trim((string) ($_POST['organizer_email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: '',
            'organizer_phone'   => $this->sanitize_text_param($_POST['organizer_phone'] ?? '', 50) ?? '',
            'organizer_website' => $this->sanitize_public_url($_POST['organizer_website'] ?? ''),
            'status'            => $status,
        ];

        if (empty($data['title'])) {
            CMS\Router::instance()->redirect('/admin/events' . ($event_id > 0 ? '/edit/' . $event_id : '/new') . '?error=validation');
            return;
        }

        try {
            if ($event_id > 0) { $data['id'] = $event_id; }
            $event_id = $db_manager->save_event($data);
            if ($event_id <= 0) {
                CMS\Router::instance()->redirect('/admin/events' . ((int)($_POST['event_id'] ?? 0) > 0 ? '/edit/' . (int)$_POST['event_id'] : '/new') . '?error=validation');
                return;
            }
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

        if (!CMS\Security::instance()->verifyToken($csrf_token, 'approve_event')) {
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

        $csrf  = (string) ($_POST['csrf_token'] ?? '');
        $tab   = in_array((string) ($_POST['_from_tab'] ?? 'settings'), ['settings', 'design'], true) ? (string) $_POST['_from_tab'] : 'settings';
        if (!CMS\Security::instance()->verifyToken($csrf, 'event_settings')) {
            CMS\Router::instance()->redirect('/admin/events?tab=' . $tab . '&error=csrf');
            return;
        }

        // Tab-spezifische Feldgruppen: jeder Tab speichert NUR seine eigenen Felder.
        // So überschreibt "Einstellungen speichern" nie Design-Werte und umgekehrt.
        $tab_fields = [
            'settings' => [
                'text'      => ['archive_title', 'archive_description', 'archive_slug', 'nav_label', 'per_page'],
                'checkboxes'=> ['show_nav_link'],
            ],
            'design' => [
                'text'      => [
                    'grid_columns', 'archive_header_icon', 'border_radius',
                    'color_primary', 'color_accent', 'color_hdr_from', 'color_hdr_to',
                    'color_hdr_title', 'color_card_bg', 'color_card_border', 'color_cta',
                    'color_detail_hdr_bg', 'color_detail_hdr_text', 'color_detail_accent',
                    'color_featured_border', 'color_cancelled_bg', 'color_online_badge',
                    'color_badge_published_bg', 'color_badge_published_color',
                    'color_badge_draft_bg', 'color_badge_draft_color',
                    'color_badge_cancelled_bg', 'color_badge_cancelled_color',
                    'color_badge_completed_bg', 'color_badge_completed_color',
                    'color_badge_featured_bg', 'color_badge_featured_color',
                    'color_badge_online_bg', 'color_badge_online_color',
                ],
                'checkboxes'=> [
                    'show_category','show_city','show_capacity','show_speakers','show_price','show_organizer','show_tags',
                    'show_status_badge','show_featured_badge','show_online_badge','show_date_pill','show_time_pill',
                ],
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
                $settings[$key] = $this->sanitize_setting_value($key, $_POST[$key]);
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
        $tab     = $this->resolve_admin_tab();
        $filter  = in_array((string) ($_GET['filter'] ?? 'all'), ['all', 'upcoming', 'past', 'featured', 'online', 'draft'], true) ? (string) $_GET['filter'] : 'all';
        $search  = $this->sanitize_text_param($_GET['search'] ?? '', 120) ?? '';

        $events     = $tab === 'overview' ? $db->get_events(['limit' => 200]) : [];
        $categories = $db->get_event_categories();
        $tag_presets= $db->get_event_tag_presets_grouped();
        $settings   = $db->get_settings();
        $csrf       = CMS\Security::instance()->generateToken('event_settings');
        $approveCsrf= CMS\Security::instance()->generateToken('approve_event');

        CMS_Events_Admin::instance()->render_list([
            'events'      => $events,
            'tab'         => $tab,
            'filter'      => $filter,
            'search'      => $search,
            'categories'  => $categories,
            'tag_presets' => $tag_presets,
            'settings'    => $settings,
            'csrf'        => $csrf,
            'approve_csrf'=> $approveCsrf,
        ]);
    }

    private function resolve_admin_tab(): string
    {
        $allowedTabs = ['overview', 'categories', 'tags', 'design', 'settings'];
        $tabFromQuery = (string) ($_GET['tab'] ?? '');
        if (in_array($tabFromQuery, $allowedTabs, true)) {
            return $tabFromQuery;
        }

        $pageSlug = (string) ($_GET['page'] ?? '');
        if ($pageSlug !== '' && class_exists('CMS_Events_Admin', false) && method_exists('CMS_Events_Admin', 'admin_section_for_slug')) {
            $resolved = (string) CMS_Events_Admin::admin_section_for_slug($pageSlug);
            if (in_array($resolved, $allowedTabs, true)) {
                return $resolved;
            }
        }

        return 'overview';
    }

    public function admin_category_add(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { CMS\Router::instance()->redirect('/login'); return; }
        if ((string) ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { CMS\Router::instance()->redirect('/admin/events?tab=categories'); return; }
        $csrf = $_POST['csrf_token'] ?? '';
        if (!CMS\Security::instance()->verifyToken($csrf, 'event_settings')) {
            CMS\Router::instance()->redirect('/admin/events?tab=categories&error=csrf');
            return;
        }
        $name = $this->sanitize_required_text($_POST['category_name'] ?? '', 150);
        $icon = $this->sanitize_text_param($_POST['category_icon'] ?? '📂', 10) ?? '📂';
        if ($name) CMS_Events_Database::instance()->add_event_category($name, $icon);
        CMS\Router::instance()->redirect('/admin/events?tab=categories');
    }

    public function admin_category_delete(string $id = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { CMS\Router::instance()->redirect('/login'); return; }
        if ((string) ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { CMS\Router::instance()->redirect('/admin/events?tab=categories'); return; }
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
        if ((string) ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { CMS\Router::instance()->redirect('/admin/events?tab=tags'); return; }
        $csrf = $_POST['csrf_token'] ?? '';
        if (!CMS\Security::instance()->verifyToken($csrf, 'event_settings')) {
            CMS\Router::instance()->redirect('/admin/events?tab=tags&error=csrf');
            return;
        }
        $name = $this->sanitize_required_text($_POST['tag_name'] ?? '', 150);
        $type = in_array((string) ($_POST['tag_type'] ?? 'general'), ['general', 'special', 'format'], true) ? (string) $_POST['tag_type'] : 'general';
        if ($name) CMS_Events_Database::instance()->add_event_tag_preset($name, $type);
        CMS\Router::instance()->redirect('/admin/events?tab=tags');
    }

    public function admin_tagpreset_delete(string $id = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { CMS\Router::instance()->redirect('/login'); return; }
        if ((string) ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { CMS\Router::instance()->redirect('/admin/events?tab=tags'); return; }
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
        if (!CMS\Auth::instance()->isAdmin()) {
            $this->json_response(['success' => false, 'error' => 'Unauthorized'], 403);
        }
        if ((string) ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->json_response(['success' => false, 'error' => 'Method not allowed'], 405);
        }
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'event_speaker')) {
            $this->json_response(['success' => false, 'error' => 'CSRF'], 403);
        }
        $event_id    = (int)($_POST['event_id']    ?? 0);
        $speaker_id  = (int)($_POST['speaker_id']  ?? 0);
        $speakerTypeRaw = (string) ($_POST['speaker_type'] ?? '');
        $speaker_type = in_array($speakerTypeRaw, ['speaker', 'expert'], true)
            ? $speakerTypeRaw
            : 'speaker';
        if ($event_id <= 0 || $speaker_id <= 0) {
            $this->json_response(['success' => false, 'error' => 'Invalid data'], 400);
        }
        try {
            $db  = CMS_Events_Database::instance();
            $ok  = $db->assign_speaker($event_id, $speaker_id, $speaker_type, [
                'presentation_title' => $this->sanitize_text_param($_POST['presentation_title'] ?? '', 255) ?? '',
                'session_time'       => $this->sanitize_time($_POST['session_time'] ?? null),
                'role'               => $this->sanitize_text_param($_POST['role'] ?? '', 100) ?? '',
            ]);
            $this->json_response(['success' => $ok, 'error' => $ok ? null : 'Save failed'], $ok ? 200 : 422);
        } catch (\Throwable $e) {
            error_log('CMS Events speaker add error: ' . $e->getMessage());
            $this->json_response(['success' => false, 'error' => 'Server error'], 500);
        }
    }

    public function admin_speaker_remove(string $id = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            $this->json_response(['success' => false, 'error' => 'Unauthorized'], 403);
        }
        if ((string) ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->json_response(['success' => false, 'error' => 'Method not allowed'], 405);
        }
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'event_speaker')) {
            $this->json_response(['success' => false, 'error' => 'CSRF'], 403);
        }
        $assignment_id = $id !== '' ? (int)$id : (int)($_POST['id'] ?? 0);
        if ($assignment_id <= 0) {
            $this->json_response(['success' => false, 'error' => 'Invalid id'], 400);
        }
        try {
            $ok = CMS_Events_Database::instance()->remove_event_speaker($assignment_id);
            $this->json_response(['success' => $ok, 'error' => $ok ? null : 'Delete failed'], $ok ? 200 : 404);
        } catch (\Throwable $e) {
            error_log('CMS Events speaker remove error: ' . $e->getMessage());
            $this->json_response(['success' => false, 'error' => 'Server error'], 500);
        }
    }

    private function json_response(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function sanitize_text_param(mixed $value, int $maxLength): ?string
    {
        $text = trim(strip_tags((string) $value));
        if ($text === '') {
            return null;
        }

        return $this->safe_substr($text, $maxLength);
    }

    private function safe_substr(string $value, int $maxLength): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLength, 'UTF-8');
        }

        return substr($value, 0, $maxLength);
    }

    private function sanitize_required_text(mixed $value, int $maxLength): string
    {
        return $this->sanitize_text_param($value, $maxLength) ?? '';
    }

    private function sanitize_date(mixed $value): ?string
    {
        $date = trim((string) $value);
        if ($date === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));
        return checkdate($month, $day, $year) ? $date : null;
    }

    private function sanitize_time(mixed $value): ?string
    {
        $time = trim((string) $value);
        if ($time === '') {
            return null;
        }

        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time) !== 1) {
            return null;
        }

        return strlen($time) === 5 ? $time . ':00' : $time;
    }

    private function sanitize_month(mixed $value): ?string
    {
        $month = trim((string) $value);
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1 ? $month : null;
    }

    /**
     * @return array{0:?string,1:?int,2:?int}
     */
    private function sanitize_archive_date_filter(mixed $monthValue, mixed $yearValue): array
    {
        $legacyMonth = $this->sanitize_month($monthValue);
        if ($legacyMonth !== null) {
            return [
                $legacyMonth,
                (int) substr($legacyMonth, 5, 2),
                (int) substr($legacyMonth, 0, 4),
            ];
        }

        $monthRaw = trim((string) $monthValue);
        $yearRaw = trim((string) $yearValue);
        $monthNumber = ctype_digit($monthRaw) ? (int) $monthRaw : 0;
        $year = ctype_digit($yearRaw) ? (int) $yearRaw : 0;

        $monthNumber = $monthNumber >= 1 && $monthNumber <= 12 ? $monthNumber : null;
        $year = $year >= 2000 && $year <= 2100 ? $year : null;
        $month = $monthNumber !== null && $year !== null ? sprintf('%04d-%02d', $year, $monthNumber) : null;

        return [$month, $monthNumber, $year];
    }

    /**
     * @return array<string, string>
     */
    private function build_archive_filter_params(
        ?string $category,
        ?string $city,
        ?int $monthNumber,
        ?int $year,
        ?int $online,
        ?string $when,
        string $search,
        bool $hasExplicitFilters,
        bool $dateFilterExplicit
    ): array {
        if (!$hasExplicitFilters) {
            return [];
        }

        $params = [];
        if ($category !== null) {
            $params['category'] = $category;
        }
        if ($city !== null) {
            $params['city'] = $city;
        }
        if ($dateFilterExplicit) {
            $params['month'] = $monthNumber !== null ? (string) $monthNumber : '0';
            $params['year'] = $year !== null ? (string) $year : '0';
        }
        if ($online !== null) {
            $params['online'] = (string) $online;
        }
        if ($when !== null) {
            $params['when'] = $when;
        }
        if ($search !== '') {
            $params['search'] = $search;
        }

        return $params;
    }

    private function sanitize_binary_filter(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return in_array((string) $value, ['0', '1'], true) ? (int) $value : null;
    }

    private function sanitize_public_url(mixed $value): ?string
    {
        $url = cms_events_public_url($value);
        return $url !== '' ? $url : null;
    }

    private function sanitize_currency(mixed $value): string
    {
        $currency = strtoupper(preg_replace('/[^A-Z]/i', '', (string) $value) ?? '');
        return $currency !== '' ? substr($currency, 0, 10) : 'EUR';
    }

    private function sanitize_setting_value(string $key, mixed $value): string
    {
        $raw = trim((string) $value);

        if (str_starts_with($key, 'color_')) {
            return preg_match('/^#[0-9a-fA-F]{6}$/', $raw) === 1 ? $raw : '';
        }

        if ($key === 'archive_slug') {
            $slug = preg_replace('/[^a-z0-9-]+/i', '-', strtolower($raw)) ?? '';
            return trim($slug, '-') ?: 'events';
        }

        if ($key === 'per_page') {
            return (string) max(6, min(100, (int) $raw));
        }

        if ($key === 'nav_label') {
            return $this->safe_substr(strip_tags($raw), 40) ?: 'Veranstaltungen';
        }

        if ($key === 'grid_columns') {
            return in_array($raw, ['auto', '2', '3', '4'], true) ? $raw : 'auto';
        }

        if ($key === 'border_radius') {
            return (string) max(0, min(32, (int) $raw));
        }

        return $this->safe_substr(strip_tags($raw), 500);
    }

    private function render_404(): void
    {
        http_response_code(404);
        try {
            if (class_exists('CMS\\ThemeManager')) {
                CMS\ThemeManager::instance()->render('404');
                exit;
            }
        } catch (\Throwable $e) {
            error_log('CMS Events render_404 fallback: ' . $e->getMessage());
        }

        echo '<!DOCTYPE html><html lang="de"><body><h1>Event nicht gefunden</h1></body></html>';
        exit;
    }

    private function render_public_error(string $message, \Throwable $e): void
    {
        error_log('CMS Events public route error: ' . $e->getMessage());
        http_response_code(500);

        try {
            if (class_exists('CMS\\ThemeManager')) {
                CMS\ThemeManager::instance()->render('error', [
                    'error_code'    => 500,
                    'error_title'   => 'Event-Fehler',
                    'error_message' => $message,
                ]);
                exit;
            }
        } catch (\Throwable $fallbackError) {
            error_log('CMS Events public error fallback failed: ' . $fallbackError->getMessage());
        }

        echo '<!DOCTYPE html><html lang="de"><body><h1>Event-Fehler</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></body></html>';
        exit;
    }

    private function render_public_theme_template(string $template, array $data): void
    {
        $templateBufferLevel = ob_get_level();

        try {
            $themeManager = \CMS\ThemeManager::instance();
            $themeManager->getHeader($data);

            ob_start();
            CMS_Events_Template_Loader::instance()->render_template($template, $data);
            $rendered = ob_get_clean();
            if ($rendered !== false) {
                echo $rendered;
            }
        } catch (\Throwable $e) {
            while (ob_get_level() > $templateBufferLevel) {
                ob_end_clean();
            }

            error_log('CMS Events public template render fallback (' . $template . '): ' . $e->getMessage());
            if ($template === 'archive-event') {
                $events = is_array($data['events'] ?? null) ? $data['events'] : [];
                $baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';

                echo '<section class="phinit-plugin cms-events-wrap">';
                echo '<section class="cms-events-grid" aria-label="Event-Liste">';

                if ($events === []) {
                    echo '<div class="cms-events-empty phinit-empty-state" role="status" aria-live="polite">';
                    echo '<i class="ti ti-calendar-off" aria-hidden="true"></i>';
                    echo '<p class="cms-events-empty__title">Keine Events gefunden.</p>';
                    echo '</div>';
                } else {
                    foreach ($events as $event) {
                        $eventObject = is_object($event) ? $event : (is_array($event) ? (object) $event : (object) []);
                        $eventId = (int) ($eventObject->id ?? 0);
                        $title = trim((string) ($eventObject->title ?? 'Event')) ?: 'Event';
                        $eventUrl = function_exists('cms_event_url')
                            ? cms_event_url($eventObject)
                            : ($baseUrl . '/events/' . $eventId);

                        echo '<article class="phinit-card cms-events-card cms-events-card--fallback">';
                        echo '<div class="cms-events-card__body">';
                        echo '<h2 class="cms-events-card__title"><a href="' . htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</a></h2>';
                        echo '<footer class="cms-events-card__footer">';
                        echo '<a href="' . htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8') . '" class="phinit-btn phinit-btn--primary cms-events-card__button">Details</a>';
                        echo '</footer>';
                        echo '</div>';
                        echo '</article>';
                    }
                }

                echo '</section>';
                echo '</section>';
                return;
            }

            echo '<section class="phinit-plugin cms-events-wrap">'
                . '<div class="cms-events-empty phinit-empty-state" role="status" aria-live="polite">'
                . '<i class="ti ti-alert-circle" aria-hidden="true"></i>'
                . '<p class="cms-events-empty__title">Events konnten aktuell nicht dargestellt werden.</p>'
                . '</div>'
                . '</section>';
        } finally {
            try {
                \CMS\ThemeManager::instance()->getFooter($data);
            } catch (\Throwable $footerError) {
                error_log('CMS Events footer render skipped: ' . $footerError->getMessage());
                $this->render_public_footer_fallback();
            }
        }
    }

    private function render_public_footer_fallback(): void
    {
        $siteTitle = '';
        try {
            $siteTitle = \CMS\ThemeManager::instance()->getSiteTitle();
        } catch (\Throwable) {
            $siteTitle = '365CMS';
        }

        if ($siteTitle === '') {
            $siteTitle = '365CMS';
        }

        $year = date('Y');

        echo '<footer class="site-footer" role="contentinfo">'
            . '<div class="footer-bottom">'
            . '<div class="container footer-bottom-inner">'
            . '<span>&copy; ' . (int) $year . ' ' . htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8') . '</span>'
            . '</div>'
            . '</div>'
            . '</footer>';
    }
}

// ── Globaler Helper: kanonische Event-URL ─────────────────────────────────
if (!function_exists('cms_event_url')) {
    function cms_event_url(object $event): string
    {
        $map   = ['ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss','Ä'=>'ae','Ö'=>'oe','Ü'=>'ue'];
        $title = str_replace(array_keys($map), array_values($map), (string)($event->title ?? ''));
        $slug  = (string) preg_replace('/[^a-z0-9]+/i', '-', $title);
        $slug  = strtolower($slug);
        $slug  = trim($slug, '-') ?: 'event';
        return SITE_URL . '/event/' . $slug . '-' . (int)$event->id;
    }
}

if (!function_exists('cms_events_public_url')) {
    function cms_events_public_url(mixed $url): string
    {
        $url = trim((string) $url);
        if ($url === '' || strlen($url) > 1000 || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        if (!in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            return '';
        }

        if (!empty($parts['user']) || !empty($parts['pass'])) {
            return '';
        }

        return $url;
    }
}
