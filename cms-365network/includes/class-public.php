<?php
/**
 * Public routing and data provider for CMS 365NETWORK.
 *
 * @package CMS_365NETWORK
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_365NETWORK_Public
{
    private static ?self $instance = null;
    private ?array $settingsCache = null;
    private ?bool $domainLandingRequestCache = null;
    private ?bool $landingPathRequestCache = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        CMS\Hooks::addAction('register_routes', [$this, 'register_routes'], 10);
        CMS\Hooks::addAction('head', [$this, 'enqueue_styles'], 10);
        CMS\Hooks::addAction('head', [$this, 'output_dynamic_styles'], 20);
        CMS\Hooks::addAction('head', [$this, 'output_analytics_head'], 90);
        CMS\Hooks::addAction('body_end', [$this, 'output_analytics_body_end'], 90);
    }

    public function register_routes($router): void
    {
        $settings = $this->settings();
        $routeSlug = $this->route_slug($settings);

        $router->addRoute('GET', '/', [$this, 'render_root_or_home']);
        $router->addRoute('GET', '/' . $routeSlug, [$this, 'render_landing']);
    }

    public function render_root_or_home(): void
    {
        if (!$this->is_domain_landing_request()) {
            CMS\ThemeManager::instance()->render('home');
            return;
        }

        $this->render_landing();
    }

    public function render_landing(): void
    {
        $settings = $this->settings();
        if ((string) ($settings['landing_enabled'] ?? '1') !== '1') {
            CMS\ThemeManager::instance()->render('home');
            return;
        }

        $data = [
            'settings' => $settings,
            'areas' => $this->build_area_cards($settings),
            'events' => $this->fetch_upcoming_events((int) ($settings['sidebar_events_count'] ?? 3)),
            'speakers' => $this->fetch_random_speakers((int) ($settings['random_speakers_count'] ?? 1)),
            'companies' => $this->fetch_random_companies((int) ($settings['random_companies_count'] ?? 1)),
            'experts' => $this->fetch_random_experts((int) ($settings['random_experts_count'] ?? 1)),
            'stats' => $this->fetch_stats(),
            'current_host' => $this->current_host(),
        ];

        $bufferLevel = ob_get_level();
        try {
            CMS\ThemeManager::instance()->getHeader(['title' => (string) ($settings['landing_title'] ?? '365NETWORK')]);
            $template = CMS_365NETWORK_PLUGIN_DIR . 'templates/landing.php';
            if (is_file($template)) {
                $settings = is_array($data['settings'] ?? null) ? $data['settings'] : [];
                $areas = is_array($data['areas'] ?? null) ? $data['areas'] : [];
                $events = is_array($data['events'] ?? null) ? $data['events'] : [];
                $speakers = is_array($data['speakers'] ?? null) ? $data['speakers'] : [];
                $companies = is_array($data['companies'] ?? null) ? $data['companies'] : [];
                $experts = is_array($data['experts'] ?? null) ? $data['experts'] : [];
                $stats = is_array($data['stats'] ?? null) ? $data['stats'] : [];
                $current_host = (string) ($data['current_host'] ?? '');
                include $template;
            }
            CMS\ThemeManager::instance()->getFooter();
        } catch (\Throwable $e) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            error_log('CMS 365NETWORK landing render failed: ' . $e->getMessage());
            http_response_code(500);
            echo '<!DOCTYPE html><html lang="de"><body><h1>365NETWORK</h1><p>Die Landingpage konnte aktuell nicht dargestellt werden.</p></body></html>';
            exit;
        }
    }

    public function enqueue_styles(): void
    {
        if (!$this->is_landing_path_request()) {
            return;
        }

        $css = CMS_365NETWORK_PLUGIN_DIR . 'assets/css/style.css';
        $version = is_file($css) ? (string) filemtime($css) : CMS_365NETWORK_VERSION;
        echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_365NETWORK_PLUGIN_URL . 'assets/css/style.css?v=' . $version, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }

    public function output_dynamic_styles(): void
    {
        if (!$this->is_landing_path_request()) {
            return;
        }

        $settings = $this->settings();
        $vars = [
            '--n365-primary' => $this->hex_color((string) ($settings['primary_color'] ?? '#2563eb'), '#2563eb'),
            '--n365-accent' => $this->hex_color((string) ($settings['accent_color'] ?? '#0f766e'), '#0f766e'),
            '--n365-bg' => $this->hex_color((string) ($settings['background_color'] ?? '#f8fafc'), '#f8fafc'),
            '--n365-surface' => $this->hex_color((string) ($settings['surface_color'] ?? '#ffffff'), '#ffffff'),
            '--n365-text' => $this->hex_color((string) ($settings['text_color'] ?? '#0f172a'), '#0f172a'),
            '--n365-muted' => $this->hex_color((string) ($settings['muted_color'] ?? '#64748b'), '#64748b'),
            '--n365-border' => $this->hex_color((string) ($settings['border_color'] ?? '#e2e8f0'), '#e2e8f0'),
            '--n365-max' => $this->clamp_int($settings['content_width'] ?? 1180, 920, 1500) . 'px',
            '--n365-radius' => $this->clamp_int($settings['card_radius'] ?? 24, 0, 40) . 'px',
            '--n365-gap' => $this->clamp_int($settings['section_gap'] ?? 28, 16, 80) . 'px',
        ];

        $css = '.n365-landing{';
        foreach ($vars as $name => $value) {
            $css .= $name . ':' . $value . ';';
        }
        $css .= '}';

        echo '<style id="cms-365network-vars">' . htmlspecialchars($css, ENT_NOQUOTES, 'UTF-8') . '</style>' . "\n";
    }

    public function output_analytics_head(): void
    {
        $this->output_analytics_code('head');
    }

    public function output_analytics_body_end(): void
    {
        $this->output_analytics_code('body_end');
    }

    private function output_analytics_code(string $position): void
    {
        if (!$this->is_landing_path_request()) {
            return;
        }

        $settings = $this->settings();
        if ((string) ($settings['analytics_enabled'] ?? '0') !== '1') {
            return;
        }

        $configuredPosition = in_array((string) ($settings['analytics_position'] ?? 'head'), ['head', 'body_end'], true)
            ? (string) ($settings['analytics_position'] ?? 'head')
            : 'head';
        if ($configuredPosition !== $position) {
            return;
        }

        $code = $this->analytics_code((string) ($settings['analytics_code'] ?? ''));
        if ($code === '') {
            return;
        }

        echo "\n<!-- CMS 365NETWORK Analytics: nur Landingpage -->\n";
        echo $code . "\n";
        echo "<!-- /CMS 365NETWORK Analytics -->\n";
    }

    private function build_area_cards(array $settings): array
    {
        return [
            [
                'key' => 'events',
                'icon' => 'calendar-event',
                'label' => (string) ($settings['events_card_title'] ?? 'Events'),
                'text' => (string) ($settings['events_card_text'] ?? ''),
                'url' => $this->safe_url((string) ($settings['events_card_url'] ?? '/events')),
                'stat' => 'events',
            ],
            [
                'key' => 'speakers',
                'icon' => 'microphone-2',
                'label' => (string) ($settings['speakers_card_title'] ?? 'Speaker'),
                'text' => (string) ($settings['speakers_card_text'] ?? ''),
                'url' => $this->safe_url((string) ($settings['speakers_card_url'] ?? '/speakers')),
                'stat' => 'speakers',
            ],
            [
                'key' => 'companies',
                'icon' => 'building-community',
                'label' => (string) ($settings['companies_card_title'] ?? 'Firmen'),
                'text' => (string) ($settings['companies_card_text'] ?? ''),
                'url' => $this->safe_url((string) ($settings['companies_card_url'] ?? '/companies')),
                'stat' => 'companies',
            ],
            [
                'key' => 'experts',
                'icon' => 'user-star',
                'label' => (string) ($settings['experts_card_title'] ?? 'Experten'),
                'text' => (string) ($settings['experts_card_text'] ?? ''),
                'url' => $this->safe_url((string) ($settings['experts_card_url'] ?? '/experts')),
                'stat' => 'experts',
            ],
        ];
    }

    private function fetch_upcoming_events(int $limit): array
    {
        $limit = $this->clamp_int($limit, 0, 8);
        if ($limit === 0 || !$this->is_plugin_active('cms-events') || !$this->table_exists('events')) {
            return [];
        }

        try {
            $db = CMS\Database::instance();
            $prefix = $db->prefix();
            $sql = sprintf("SELECT id, title, event_date, event_time, city, location, image_url, category
                FROM {$prefix}events
                WHERE status = ? AND (event_date >= CURDATE() OR (end_date IS NOT NULL AND end_date >= CURDATE()))
                ORDER BY event_date ASC, event_time ASC
                LIMIT %d", $limit);
            $stmt = $db->prepare($sql);
            $stmt->execute(['published']);
            return $this->with_entity_urls(array_map([$this, 'object_to_array'], $stmt->fetchAll(\PDO::FETCH_OBJ) ?: []), 'event');
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK fetch events failed: ' . $e->getMessage());
            return [];
        }
    }

    private function fetch_random_speakers(int $limit): array
    {
        $limit = $this->clamp_int($limit, 0, 4);
        if ($limit === 0 || !$this->is_plugin_active('cms-speakers') || !$this->table_exists('speakers')) {
            return [];
        }

        try {
            $db = CMS\Database::instance();
            $prefix = $db->prefix();
            $offset = $this->random_offset('speakers', 'active', $limit);
            $sql = sprintf("SELECT id, first_name, last_name, position, company, photo_url, location_city
                FROM {$prefix}speakers
                WHERE status = ?
                ORDER BY id ASC
                LIMIT %d OFFSET %d", $limit, $offset);
            $stmt = $db->prepare($sql);
            $stmt->execute(['active']);
            return $this->with_entity_urls(array_map([$this, 'object_to_array'], $stmt->fetchAll(\PDO::FETCH_OBJ) ?: []), 'speaker');
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK fetch speakers failed: ' . $e->getMessage());
            return [];
        }
    }

    private function fetch_random_companies(int $limit): array
    {
        $limit = $this->clamp_int($limit, 0, 4);
        if ($limit === 0 || !$this->is_plugin_active('cms-companies') || !$this->table_exists('companies')) {
            return [];
        }

        try {
            $db = CMS\Database::instance();
            $prefix = $db->prefix();
            $offset = $this->random_offset('companies', 'active', $limit);
            $sql = sprintf("SELECT id, name, industry, logo_url, location_city, is_partner, is_top_partner
                FROM {$prefix}companies
                WHERE status = ?
                ORDER BY id ASC
                LIMIT %d OFFSET %d", $limit, $offset);
            $stmt = $db->prepare($sql);
            $stmt->execute(['active']);
            return $this->with_entity_urls(array_map([$this, 'object_to_array'], $stmt->fetchAll(\PDO::FETCH_OBJ) ?: []), 'company');
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK fetch companies failed: ' . $e->getMessage());
            return [];
        }
    }

    private function fetch_random_experts(int $limit): array
    {
        $limit = $this->clamp_int($limit, 0, 4);
        if ($limit === 0 || !$this->is_plugin_active('cms-experts') || !$this->table_exists('experts')) {
            return [];
        }

        try {
            $db = CMS\Database::instance();
            $prefix = $db->prefix();
            $offset = $this->random_offset('experts', 'active', $limit);
            $sql = sprintf("SELECT id, first_name, last_name, position, company, photo_url, location_city
                FROM {$prefix}experts
                WHERE status = ?
                ORDER BY id ASC
                LIMIT %d OFFSET %d", $limit, $offset);
            $stmt = $db->prepare($sql);
            $stmt->execute(['active']);
            return $this->with_entity_urls(array_map([$this, 'object_to_array'], $stmt->fetchAll(\PDO::FETCH_OBJ) ?: []), 'expert');
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK fetch experts failed: ' . $e->getMessage());
            return [];
        }
    }

    private function fetch_stats(): array
    {
        return [
            'events' => $this->is_plugin_active('cms-events') ? $this->count_table_by_status('events', 'published') : 0,
            'speakers' => $this->is_plugin_active('cms-speakers') ? $this->count_table_by_status('speakers', 'active') : 0,
            'companies' => $this->is_plugin_active('cms-companies') ? $this->count_table_by_status('companies', 'active') : 0,
            'experts' => $this->is_plugin_active('cms-experts') ? $this->count_table_by_status('experts', 'active') : 0,
        ];
    }

    private function count_table_by_status(string $table, string $status): int
    {
        if (!$this->table_exists($table)) {
            return 0;
        }

        try {
            $db = CMS\Database::instance();
            $prefix = $db->prefix();
            $stmt = $db->prepare("SELECT COUNT(*) FROM {$prefix}{$table} WHERE status = ?");
            $stmt->execute([$status]);
            return max(0, (int) $stmt->fetchColumn());
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function random_offset(string $table, string $status, int $limit): int
    {
        if (!$this->table_exists($table)) {
            return 0;
        }

        try {
            $db = CMS\Database::instance();
            $prefix = $db->prefix();
            $stmt = $db->prepare("SELECT COUNT(*) FROM {$prefix}{$table} WHERE status = ?");
            $stmt->execute([$status]);
            $count = max(0, (int) $stmt->fetchColumn());
            if ($count <= $limit) {
                return 0;
            }

            return random_int(0, $count - $limit);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function with_entity_urls(array $rows, string $type): array
    {
        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $rows[$index]['url'] = $this->entity_url($type, $row);
        }

        return $rows;
    }

    private function entity_url(string $type, array $row): string
    {
        $id = (int) ($row['id'] ?? 0);
        if ($id <= 0) {
            return match ($type) {
                'event' => '/events',
                'speaker' => '/speakers',
                'company' => '/companies',
                'expert' => '/experts',
                default => '#',
            };
        }

        $object = (object) $row;
        if ($type === 'event' && function_exists('cms_event_url')) {
            return cms_event_url($object);
        }
        if ($type === 'company' && function_exists('cms_company_url')) {
            return cms_company_url($object);
        }
        if ($type === 'speaker' && class_exists('CMS_Speakers_Database') && method_exists('CMS_Speakers_Database', 'generate_slug')) {
            return $this->base_url() . '/speakers/' . CMS_Speakers_Database::generate_slug($object);
        }
        if ($type === 'expert' && class_exists('CMS_Experts_Database') && method_exists('CMS_Experts_Database', 'generate_slug')) {
            return $this->base_url() . '/experts/' . CMS_Experts_Database::generate_slug($object);
        }

        return match ($type) {
            'event' => $this->base_url() . '/event/' . $this->slug_from_parts([(string) ($row['title'] ?? 'event')], 'event') . '-' . $id,
            'speaker' => $this->base_url() . '/speakers/' . $this->slug_from_parts([(string) ($row['first_name'] ?? ''), (string) ($row['last_name'] ?? '')], 'speaker') . '-' . $id,
            'company' => $this->base_url() . '/company/' . $this->slug_from_parts([(string) ($row['name'] ?? 'company')], 'company') . '-' . $id,
            'expert' => $this->base_url() . '/experts/' . $this->slug_from_parts([(string) ($row['first_name'] ?? ''), (string) ($row['last_name'] ?? '')], 'expert') . '-' . $id,
            default => '#',
        };
    }

    private function slug_from_parts(array $parts, string $fallback): string
    {
        $value = trim(implode(' ', array_filter(array_map('trim', $parts))));
        $value = strtr($value, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue']);
        $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $value));
        return trim($slug, '-') ?: $fallback;
    }

    private function base_url(): string
    {
        return rtrim((string) SITE_URL, '/');
    }

    private function table_exists(string $table): bool
    {
        if (preg_match('/^[a-z0-9_]+$/', $table) !== 1) {
            return false;
        }

        try {
            $db = CMS\Database::instance();
            $stmt = $db->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$db->prefix() . $table]);
            return (bool) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function is_plugin_active(string $slug): bool
    {
        try {
            return class_exists('CMS\\PluginManager') && CMS\PluginManager::instance()->isPluginActive($slug);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function is_domain_landing_request(): bool
    {
        if ($this->domainLandingRequestCache !== null) {
            return $this->domainLandingRequestCache;
        }

        $settings = $this->settings();
        if ((string) ($settings['landing_enabled'] ?? '1') !== '1') {
            return $this->domainLandingRequestCache = false;
        }

        $host = $this->current_host();
        $mainHost = $this->normalize_host((string) (parse_url((string) SITE_URL, PHP_URL_HOST) ?: ''));
        if ($host === '' || $host === $mainHost) {
            return $this->domainLandingRequestCache = false;
        }

        return $this->domainLandingRequestCache = in_array($host, $this->configured_domains($settings), true);
    }

    private function is_landing_path_request(): bool
    {
        if ($this->landingPathRequestCache !== null) {
            return $this->landingPathRequestCache;
        }

        $settings = $this->settings();
        $path = '/' . trim((string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/'), '/');
        $route = '/' . $this->route_slug($settings);

        return $this->landingPathRequestCache = ($path === $route || ($path === '/' && $this->is_domain_landing_request()));
    }

    private function settings(): array
    {
        if ($this->settingsCache === null) {
            $this->settingsCache = CMS_365NETWORK_Database::instance()->get_settings();
        }

        return $this->settingsCache;
    }

    private function route_slug(array $settings): string
    {
        $route = strtolower(trim((string) ($settings['route_slug'] ?? '365network'), '/'));
        $route = trim((string) preg_replace('/[^a-z0-9-]+/i', '-', $route), '-');
        return $route !== '' ? $route : '365network';
    }

    private function configured_domains(array $settings): array
    {
        $raw = (string) ($settings['hub_domains'] ?? '');
        $parts = preg_split('/[\s,;]+/', $raw) ?: [];
        $domains = [];
        foreach ($parts as $part) {
            $domain = $this->normalize_host($part);
            if ($domain !== '' && !in_array($domain, $domains, true)) {
                $domains[] = $domain;
            }
        }

        return $domains;
    }

    private function current_host(): string
    {
        return $this->normalize_host((string) ($_SERVER['HTTP_HOST'] ?? ''));
    }

    private function normalize_host(string $host): string
    {
        $host = trim(strtolower($host));
        if ($host === '') {
            return '';
        }
        if (str_contains($host, '://')) {
            $parsedHost = parse_url($host, PHP_URL_HOST);
            $host = is_string($parsedHost) ? $parsedHost : '';
        }
        $host = preg_replace('/:\d+$/', '', $host) ?? '';
        $host = trim($host, '.');
        $host = preg_replace('/^www\./', '', $host) ?? '';
        return preg_match('/^[a-z0-9.-]+$/', $host) === 1 ? $host : '';
    }

    private function safe_url(string $value): string
    {
        $url = trim($value);
        if ($url === '') {
            return '#';
        }
        if (preg_match('/^#[A-Za-z][A-Za-z0-9_-]*$/', $url) === 1) {
            return $url;
        }
        if (str_starts_with($url, '/') && !str_starts_with($url, '//') && !str_contains($url, "\0")) {
            return $url;
        }

        $parts = parse_url($url);
        $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';
        return in_array($scheme, ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : '#';
    }

    private function hex_color(string $value, string $default): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $default;
    }

    private function analytics_code(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = str_replace("\0", '', $value);
        $value = preg_replace('/<\?(?:php)?|\?>/i', '', $value) ?? '';
        $value = trim($value);

        if (strlen($value) > 20000) {
            $value = substr($value, 0, 20000);
        }

        return $value;
    }

    private function clamp_int(mixed $value, int $min, int $max): int
    {
        return max($min, min($max, (int) $value));
    }

    private function object_to_array(object $row): array
    {
        return get_object_vars($row);
    }
}
