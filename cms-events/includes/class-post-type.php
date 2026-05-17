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
        
        echo '<a href="' . htmlspecialchars((string) SITE_URL, ENT_QUOTES, 'UTF-8') . '/events" class="nav-link ' . htmlspecialchars($is_active, ENT_QUOTES, 'UTF-8') . '">Events</a>';
    }

    public function archive_page(): void
    {
        // Öffentliche Ansicht – keine Abo-Prüfung, Erstellung ist separat geschützt
        $db_manager = CMS_Events_Database::instance();
        $settings   = $db_manager->get_settings();

        $filter_category = $this->sanitize_text_param($_GET['category'] ?? null, 100);
        $filter_city     = $this->sanitize_text_param($_GET['city'] ?? null, 100);
        $filter_month    = $this->sanitize_month($_GET['month'] ?? null);
        $filter_online   = $this->sanitize_binary_filter($_GET['online'] ?? null);
        $when_filter     = in_array((string) ($_GET['when'] ?? ''), ['upcoming', 'past'], true) ? (string) $_GET['when'] : null;
        $search          = $this->sanitize_text_param($_GET['search'] ?? '', 120) ?? '';
        $page            = max(1, min(500, (int)($_GET['page'] ?? 1)));
        $per_page        = max(6, min(100, (int)($settings['per_page'] ?? 12)));

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

        if ($filter_category !== null) $args['category']  = $filter_category;
        if ($filter_city !== null)     $args['city']       = $filter_city;
        if ($filter_month !== null)    $args['month']      = $filter_month;
        if ($search !== '')            $args['search']     = $search;
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
        
        $month = $this->sanitize_month($_GET['month'] ?? null) ?? date('Y-m');
        $view = in_array((string) ($_GET['view'] ?? 'month'), ['month', 'week'], true) ? (string) $_GET['view'] : 'month';

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

    public function export_ical(string $id = ''): void
    {
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
        $desc_raw    = html_entity_decode($_POST['description'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $desc_raw    = \CMS\Services\EditorService::getInstance()->sanitize($desc_raw);
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
        $tab     = in_array((string) ($_GET['tab'] ?? 'overview'), ['overview', 'categories', 'tags', 'design', 'settings'], true) ? (string) $_GET['tab'] : 'overview';
        $filter  = in_array((string) ($_GET['filter'] ?? 'all'), ['all', 'upcoming', 'past', 'featured', 'online', 'draft'], true) ? (string) $_GET['filter'] : 'all';

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
        $name = $this->sanitize_required_text($_POST['category_name'] ?? '', 150);
        $icon = $this->sanitize_text_param($_POST['category_icon'] ?? '📂', 10) ?? '📂';
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
        $name = $this->sanitize_required_text($_POST['tag_name'] ?? '', 150);
        $type = in_array((string) ($_POST['tag_type'] ?? 'general'), ['general', 'special', 'format'], true) ? (string) $_POST['tag_type'] : 'general';
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
        if (!CMS\Auth::instance()->isAdmin()) { http_response_code(403); header('Content-Type: application/json; charset=utf-8'); header('X-Content-Type-Options: nosniff'); echo json_encode(['error'=>'Unauthorized'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'event_speaker')) {
            http_response_code(403); echo json_encode(['error'=>'CSRF'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
        }
        $event_id    = (int)($_POST['event_id']    ?? 0);
        $speaker_id  = (int)($_POST['speaker_id']  ?? 0);
        $speaker_type = in_array($_POST['speaker_type'] ?? '', ['speaker','expert'], true)
            ? $_POST['speaker_type'] : 'speaker';
        if ($event_id <= 0 || $speaker_id <= 0) {
            http_response_code(400); echo json_encode(['error'=>'Invalid data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
        }
        $db  = CMS_Events_Database::instance();
        $ok  = $db->assign_speaker($event_id, $speaker_id, $speaker_type, [
            'presentation_title' => $this->sanitize_text_param($_POST['presentation_title'] ?? '', 255) ?? '',
            'session_time'       => $this->sanitize_time($_POST['session_time'] ?? null),
            'role'               => $this->sanitize_text_param($_POST['role'] ?? '', 100) ?? '',
        ]);
        echo json_encode(['success' => $ok], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function admin_speaker_remove(string $id = ''): void
    {
        if (!CMS\Auth::instance()->isAdmin()) { http_response_code(403); header('Content-Type: application/json; charset=utf-8'); header('X-Content-Type-Options: nosniff'); echo json_encode(['error'=>'Unauthorized'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'event_speaker')) {
            http_response_code(403); echo json_encode(['error'=>'CSRF'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
        }
        $assignment_id = $id !== '' ? (int)$id : (int)($_POST['id'] ?? 0);
        if ($assignment_id <= 0) { http_response_code(400); echo json_encode(['error'=>'Invalid id'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
        $ok = CMS_Events_Database::instance()->remove_event_speaker($assignment_id);
        echo json_encode(['success' => $ok], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function sanitize_text_param(mixed $value, int $maxLength): ?string
    {
        $text = trim(strip_tags((string) $value));
        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, $maxLength, 'UTF-8');
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

        if ($key === 'grid_columns') {
            return in_array($raw, ['auto', '2', '3', '4'], true) ? $raw : 'auto';
        }

        if ($key === 'border_radius') {
            return (string) max(0, min(32, (int) $raw));
        }

        return mb_substr(strip_tags($raw), 0, 500, 'UTF-8');
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
