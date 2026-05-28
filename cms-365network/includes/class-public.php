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
            'hub_settings' => CMS_365NETWORK_Database::instance()->get_hub_settings(),
            'areas' => $this->build_area_cards($settings),
            'events' => $this->fetch_upcoming_events((int) ($settings['sidebar_events_count'] ?? 3)),
            'speakers' => $this->fetch_random_speakers((int) ($settings['random_speakers_count'] ?? 1)),
            'companies' => $this->fetch_random_companies((int) ($settings['random_companies_count'] ?? 1)),
            'experts' => $this->fetch_random_experts((int) ($settings['random_experts_count'] ?? 1)),
            'stats' => $this->fetch_stats(),
            'current_host' => $this->current_host(),
        ];
        $data['toolbox_tools'] = $this->fetch_toolbox_links((int) ($data['hub_settings']['hub_toolbox_limit'] ?? 12));

        $bufferLevel = ob_get_level();
        try {
            CMS\ThemeManager::instance()->getHeader(['title' => (string) ($data['hub_settings']['hub_hero_title'] ?? $settings['landing_title'] ?? '365NETWORK')]);
            $template = CMS_365NETWORK_PLUGIN_DIR . 'templates/landing.php';
            if (is_file($template)) {
                $settings = is_array($data['settings'] ?? null) ? $data['settings'] : [];
                $hubSettings = is_array($data['hub_settings'] ?? null) ? $data['hub_settings'] : [];
                $areas = is_array($data['areas'] ?? null) ? $data['areas'] : [];
                $events = is_array($data['events'] ?? null) ? $data['events'] : [];
                $speakers = is_array($data['speakers'] ?? null) ? $data['speakers'] : [];
                $companies = is_array($data['companies'] ?? null) ? $data['companies'] : [];
                $experts = is_array($data['experts'] ?? null) ? $data['experts'] : [];
                $toolboxTools = is_array($data['toolbox_tools'] ?? null) ? $data['toolbox_tools'] : [];
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
        if (function_exists('cms_enqueue_style')) {
            cms_enqueue_style('cms-365network-public', CMS_365NETWORK_PLUGIN_URL . 'assets/css/style.css', [], $version);
            return;
        }

        echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_365NETWORK_PLUGIN_URL . 'assets/css/style.css?v=' . $version, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }

    public function output_dynamic_styles(): void
    {
        if (!$this->is_landing_path_request()) {
            return;
        }

        $settings = $this->settings();
        $hubSettings = CMS_365NETWORK_Database::instance()->get_hub_settings();
        $vars = [
            '--hub-gold' => $this->hex_color((string) ($settings['primary_color'] ?? ''), '#e6a817'),
            '--hub-gold-hover' => $this->hex_color((string) ($settings['accent_color'] ?? ''), '#c48f0f'),
            '--hub-page-bg' => $this->hex_color((string) ($settings['background_color'] ?? ''), '#e8ecf0'),
            '--hub-card-bg' => $this->hex_color((string) ($settings['surface_color'] ?? ''), '#ffffff'),
            '--hub-text' => $this->hex_color((string) ($settings['text_color'] ?? ''), '#1a2e4a'),
            '--hub-navy' => $this->hex_color((string) ($settings['text_color'] ?? ''), '#1a2e4a'),
            '--hub-muted' => $this->hex_color((string) ($settings['muted_color'] ?? ''), '#5a6a7a'),
            '--hub-border' => $this->hex_color((string) ($settings['border_color'] ?? ''), '#dce3ec'),
            '--hub-radius' => $this->clamp_int($settings['card_radius'] ?? 8, 0, 40) . 'px',
            '--n365-primary' => $this->hex_color((string) ($settings['primary_color'] ?? ''), '#e6a817'),
            '--n365-accent' => $this->hex_color((string) ($settings['accent_color'] ?? ''), '#e6a817'),
            '--n365-bg' => $this->hex_color((string) ($settings['background_color'] ?? ''), '#e8ecf0'),
            '--n365-surface' => $this->hex_color((string) ($settings['surface_color'] ?? ''), '#ffffff'),
            '--n365-text' => $this->hex_color((string) ($settings['text_color'] ?? ''), '#1a2e4a'),
            '--n365-muted' => $this->hex_color((string) ($settings['muted_color'] ?? ''), '#5a6a7a'),
            '--n365-border' => $this->hex_color((string) ($settings['border_color'] ?? ''), '#dce3ec'),
            '--n365-max' => $this->clamp_int($settings['content_width'] ?? 1180, 920, 1500) . 'px',
            '--n365-radius' => $this->clamp_int($settings['card_radius'] ?? 24, 0, 40) . 'px',
            '--n365-gap' => $this->clamp_int($settings['section_gap'] ?? 28, 16, 80) . 'px',
            '--n365-featured-bg' => $this->hex_color((string) ($hubSettings['hub_featured_bg_color'] ?? ''), '#ffffff'),
            '--n365-featured-text' => $this->hex_color((string) ($hubSettings['hub_featured_text_color'] ?? ''), '#1a2e4a'),
            '--n365-featured-accent' => $this->hex_color((string) ($hubSettings['hub_featured_accent_color'] ?? ''), '#e6a817'),
            '--n365-featured-radius' => $this->clamp_int($hubSettings['hub_featured_radius'] ?? 8, 0, 40) . 'px',
            '--n365-hero-bg' => $this->hex_color((string) ($hubSettings['hub_hero_bg_color'] ?? ''), '#ffffff'),
            '--n365-hero-text' => $this->hex_color((string) ($hubSettings['hub_hero_text_color'] ?? ''), '#1a2e4a'),
            '--n365-hero-accent' => $this->hex_color((string) ($hubSettings['hub_hero_accent_color'] ?? ''), '#e6a817'),
            '--n365-hero-radius' => $this->clamp_int($hubSettings['hub_hero_radius'] ?? 8, 0, 40) . 'px',
            '--n365-stats-bg' => $this->hex_color((string) ($hubSettings['hub_stats_bg_color'] ?? ''), '#f0f3f7'),
            '--n365-stats-text' => $this->hex_color((string) ($hubSettings['hub_stats_text_color'] ?? ''), '#1a2e4a'),
            '--n365-stats-accent' => $this->hex_color((string) ($hubSettings['hub_stats_accent_color'] ?? ''), '#e6a817'),
            '--n365-stats-radius' => $this->clamp_int($hubSettings['hub_stats_radius'] ?? 8, 0, 40) . 'px',
            '--n365-band-bg' => $this->hex_color((string) ($hubSettings['hub_band_bg_color'] ?? ''), '#ffffff'),
            '--n365-band-text' => $this->hex_color((string) ($hubSettings['hub_band_text_color'] ?? ''), '#1a2e4a'),
            '--n365-band-accent' => $this->hex_color((string) ($hubSettings['hub_band_accent_color'] ?? ''), '#e6a817'),
            '--n365-band-radius' => $this->clamp_int($hubSettings['hub_band_radius'] ?? 8, 0, 40) . 'px',
            '--n365-areas-bg' => $this->hex_color((string) ($hubSettings['hub_areas_bg_color'] ?? ''), '#ffffff'),
            '--n365-areas-text' => $this->hex_color((string) ($hubSettings['hub_areas_text_color'] ?? ''), '#1a2e4a'),
            '--n365-areas-accent' => $this->hex_color((string) ($hubSettings['hub_areas_accent_color'] ?? ''), '#e6a817'),
            '--n365-areas-radius' => $this->clamp_int($hubSettings['hub_areas_radius'] ?? 10, 0, 40) . 'px',
            '--n365-toolbox-bg' => $this->hex_color((string) ($hubSettings['hub_toolbox_bg_color'] ?? ''), '#ffffff'),
            '--n365-toolbox-text' => $this->hex_color((string) ($hubSettings['hub_toolbox_text_color'] ?? ''), '#1a2e4a'),
            '--n365-toolbox-accent' => $this->hex_color((string) ($hubSettings['hub_toolbox_accent_color'] ?? ''), '#e6a817'),
            '--n365-toolbox-radius' => $this->clamp_int($hubSettings['hub_toolbox_radius'] ?? 8, 0, 40) . 'px',
        ];

        $css = '.cms-network-hub-wrap.n365-landing{';
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
        if ($limit === 0 || !$this->is_integration_available('cms-events', 'CMS_Events', 'events')) {
            return [];
        }

        try {
            $db = CMS\Database::instance();
            $prefix = $db->prefix();
            $params = [];
            $statusWhere = $this->status_filter_sql('events', ['published', 'active'], $params);
            $sql = sprintf("SELECT id, title, event_date, event_time, city, location, image_url, category
                FROM {$prefix}events
                WHERE {$statusWhere} AND (event_date >= CURDATE() OR (end_date IS NOT NULL AND end_date >= CURDATE()))
                ORDER BY event_date ASC, event_time ASC
                LIMIT %d", $limit);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $this->with_entity_urls(array_map([$this, 'object_to_array'], $stmt->fetchAll(\PDO::FETCH_OBJ) ?: []), 'event');
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK fetch events failed: ' . $e->getMessage());
            return [];
        }
    }

    private function fetch_random_speakers(int $limit): array
    {
        $limit = $this->clamp_int($limit, 0, 4);
        if ($limit === 0 || !$this->is_integration_available('cms-speakers', 'CMS_Speakers', 'speakers')) {
            return [];
        }

        try {
            $db = CMS\Database::instance();
            $prefix = $db->prefix();
            $offset = $this->random_offset('speakers', ['active'], $limit);
            $params = [];
            $statusWhere = $this->status_filter_sql('speakers', ['active'], $params);
            $sql = sprintf("SELECT id, first_name, last_name, position, company, photo_url, location_city
                FROM {$prefix}speakers
                WHERE {$statusWhere}
                ORDER BY id ASC
                LIMIT %d OFFSET %d", $limit, $offset);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $this->with_entity_urls(array_map([$this, 'object_to_array'], $stmt->fetchAll(\PDO::FETCH_OBJ) ?: []), 'speaker');
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK fetch speakers failed: ' . $e->getMessage());
            return [];
        }
    }

    private function fetch_random_companies(int $limit): array
    {
        $limit = $this->clamp_int($limit, 0, 4);
        if ($limit === 0 || !$this->is_integration_available('cms-companies', 'CMS_Companies', 'companies')) {
            return [];
        }

        try {
            $db = CMS\Database::instance();
            $prefix = $db->prefix();
            $offset = $this->random_offset('companies', ['active'], $limit);
            $params = [];
            $statusWhere = $this->status_filter_sql('companies', ['active'], $params);
            $sql = sprintf("SELECT id, name, industry, logo_url, location_city, is_partner, is_top_partner
                FROM {$prefix}companies
                WHERE {$statusWhere}
                ORDER BY id ASC
                LIMIT %d OFFSET %d", $limit, $offset);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $this->with_entity_urls(array_map([$this, 'object_to_array'], $stmt->fetchAll(\PDO::FETCH_OBJ) ?: []), 'company');
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK fetch companies failed: ' . $e->getMessage());
            return [];
        }
    }

    private function fetch_random_experts(int $limit): array
    {
        $limit = $this->clamp_int($limit, 0, 4);
        if ($limit === 0 || !$this->is_integration_available('cms-experts', 'CMS_Experts', 'experts')) {
            return [];
        }

        try {
            $db = CMS\Database::instance();
            $prefix = $db->prefix();
            $offset = $this->random_offset('experts', ['active'], $limit);
            $params = [];
            $statusWhere = $this->status_filter_sql('experts', ['active'], $params);
            $sql = sprintf("SELECT id, first_name, last_name, position, company, photo_url, location_city
                FROM {$prefix}experts
                WHERE {$statusWhere}
                ORDER BY id ASC
                LIMIT %d OFFSET %d", $limit, $offset);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $this->with_entity_urls(array_map([$this, 'object_to_array'], $stmt->fetchAll(\PDO::FETCH_OBJ) ?: []), 'expert');
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK fetch experts failed: ' . $e->getMessage());
            return [];
        }
    }

    private function fetch_toolbox_links(int $limit): array
    {
        $limit = $this->clamp_int($limit, 1, 50);
        if (!$this->is_m365_toolbox_active()) {
            return [];
        }

        $resolvedTable = $this->resolve_table_name('m365toolbox_links');
        if ($resolvedTable === '') {
            return [];
        }

        try {
            $db = CMS\Database::instance();
            $stmt = $db->prepare("SELECT label, url, icon, description
                FROM `{$resolvedTable}`
                WHERE status = ? AND show_on_hub = ?
                ORDER BY sort_order ASC, id ASC
                LIMIT {$limit}");
            $stmt->execute(['active', 1]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK fetch toolbox links failed: ' . $e->getMessage());
            return [];
        }

        $tools = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            $url = $this->safe_url((string) ($row['url'] ?? ''));
            if ($label === '' || $url === '#') {
                continue;
            }

            $tools[] = [
                'label' => $label,
                'url' => $url,
                'icon' => $this->toolbox_icon_class((string) ($row['icon'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
            ];
        }

        return $tools;
    }

    private function toolbox_icon_class(string $icon): string
    {
        $icon = strtolower(trim($icon));
        if ($icon === '') {
            return 'ti-link';
        }

        if (!str_starts_with($icon, 'ti-')) {
            $icon = 'ti-' . $icon;
        }

        return preg_match('/^ti-[a-z0-9-]+$/', $icon) === 1 ? $icon : 'ti-link';
    }

    private function fetch_stats(): array
    {
        return [
            'events' => $this->count_events_stat(),
            'speakers' => $this->count_speakers_stat(),
            'companies' => $this->count_companies_stat(),
            'experts' => $this->count_experts_stat(),
        ];
    }

    private function count_events_stat(): int
    {
        $count = $this->count_via_plugin_api('cms-events', 'CMS_Events_Database', 'count_events', [['status' => 'published'], ['status' => 'active'], []]);
        return $count > 0 ? $count : $this->count_public_rows('events', ['published', 'active', 'completed']);
    }

    private function count_speakers_stat(): int
    {
        $count = $this->count_via_plugin_api('cms-speakers', 'CMS_Speakers_Database', 'count_speakers', [['status' => 'active'], ['status' => null]]);
        return $count > 0 ? $count : $this->count_public_rows('speakers', ['active']);
    }

    private function count_companies_stat(): int
    {
        $count = $this->count_via_plugin_api('cms-companies', 'CMS_Companies_Database', 'get_companies_count', [['status' => 'active'], ['status' => 'any']]);
        return $count > 0 ? $count : $this->count_public_rows('companies', ['active']);
    }

    private function count_experts_stat(): int
    {
        $count = $this->count_via_plugin_api('cms-experts', 'CMS_Experts_Database', 'countExperts', ['active', '']);
        return $count > 0 ? $count : $this->count_public_rows('experts', ['active']);
    }

    /**
     * @param array<int,mixed> $argumentVariants
     */
    private function count_via_plugin_api(string $slug, string $className, string $method, array $argumentVariants): int
    {
        if (!$this->load_integration_database($slug, $className) || !method_exists($className, $method)) {
            return 0;
        }

        try {
            $instance = $className::instance();
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK stats: ' . $className . '::instance failed: ' . $e->getMessage());
            return 0;
        }

        foreach ($argumentVariants as $argument) {
            try {
                $count = is_array($argument)
                    ? (int) $instance->{$method}($argument)
                    : (int) $instance->{$method}((string) $argument);
                if ($count > 0) {
                    return $count;
                }
            } catch (\Throwable $e) {
                error_log('CMS 365NETWORK stats: ' . $className . '::' . $method . ' failed: ' . $e->getMessage());
            }
        }

        return 0;
    }

    private function load_integration_database(string $slug, string $className): bool
    {
        if (class_exists($className, false)) {
            return true;
        }

        $baseDir = dirname(CMS_365NETWORK_PLUGIN_DIR);
        $path = $baseDir . '/' . $slug . '/includes/class-database.php';
        if (is_file($path)) {
            require_once $path;
        }

        return class_exists($className, false);
    }

    private function count_public_rows(string $table, array $preferredStatuses): int
    {
        $resolvedTable = $this->resolve_table_name($table);
        if ($resolvedTable === '') {
            $resolvedTable = $this->default_table_name($table);
        }

        if ($resolvedTable === '') {
            return 0;
        }

        try {
            $db = CMS\Database::instance();
            $params = [];
            $where = $this->status_filter_sql($table, $preferredStatuses, $params);
            $stmt = $db->prepare("SELECT COUNT(*) FROM `{$resolvedTable}` WHERE {$where}");
            $stmt->execute($params);
            $count = max(0, (int) $stmt->fetchColumn());
            if ($count > 0 || !$this->column_exists($table, 'status')) {
                return $count;
            }

            $stmt = $db->prepare("SELECT COUNT(*) FROM `{$resolvedTable}` WHERE status <> ?");
            $stmt->execute(['deleted']);
            return max(0, (int) $stmt->fetchColumn());
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK count ' . $table . ' failed: ' . $e->getMessage());
            return 0;
        }
    }

    private function random_offset(string $table, array $statuses, int $limit): int
    {
        if (!$this->table_exists($table)) {
            return 0;
        }

        try {
            $db = CMS\Database::instance();
            $prefix = $db->prefix();
            $params = [];
            $where = $this->status_filter_sql($table, $statuses, $params);
            $stmt = $db->prepare("SELECT COUNT(*) FROM {$prefix}{$table} WHERE {$where}");
            $stmt->execute($params);
            $count = max(0, (int) $stmt->fetchColumn());
            if ($count <= $limit) {
                return 0;
            }

            return random_int(0, $count - $limit);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function status_filter_sql(string $table, array $statuses, array &$params): string
    {
        if (!$this->column_exists($table, 'status')) {
            return '1=1';
        }

        $statuses = array_values(array_unique(array_filter(array_map(
            static fn(mixed $status): string => strtolower(trim((string) $status)),
            $statuses
        ), static fn(string $status): bool => preg_match('/^[a-z0-9_-]+$/', $status) === 1)));

        if ($statuses === []) {
            return '1=1';
        }

        foreach ($statuses as $status) {
            $params[] = $status;
        }

        return 'status IN (' . implode(', ', array_fill(0, count($statuses), '?')) . ')';
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
        return $this->resolve_table_name($table) !== '';
    }

    private function column_exists(string $table, string $column): bool
    {
        if (preg_match('/^[a-z0-9_]+$/', $table) !== 1 || preg_match('/^[a-z0-9_]+$/', $column) !== 1) {
            return false;
        }

        try {
            $db = CMS\Database::instance();
            $resolvedTable = $this->resolve_table_name($table);
            if ($resolvedTable === '') {
                return false;
            }

            $stmt = $db->prepare(
                'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
            );
            $stmt->execute([$resolvedTable, $column]);
            return (bool) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function resolve_table_name(string $table): string
    {
        if (preg_match('/^[a-z0-9_]+$/', $table) !== 1) {
            return '';
        }

        try {
            $db = CMS\Database::instance();
            $prefix = $db->prefix();
            $candidates = array_values(array_unique([$prefix . $table, $table]));
            foreach ($candidates as $candidate) {
                if (preg_match('/^[a-zA-Z0-9_]+$/', $candidate) !== 1) {
                    continue;
                }

                $stmt = $db->prepare(
                    'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1'
                );
                $stmt->execute([$candidate]);
                $found = $stmt->fetchColumn();
                if (is_string($found) && $found !== '') {
                    return $found;
                }
            }
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK resolve table ' . $table . ' failed: ' . $e->getMessage());
        }

        return '';
    }

    private function default_table_name(string $table): string
    {
        if (preg_match('/^[a-z0-9_]+$/', $table) !== 1) {
            return '';
        }

        try {
            $candidate = CMS\Database::instance()->prefix() . $table;
            return preg_match('/^[a-zA-Z0-9_]+$/', $candidate) === 1 ? $candidate : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function is_integration_available(string $slug, string $className, string $table): bool
    {
        unset($slug, $className);

        return $this->table_exists($table);
    }

    private function is_plugin_active(string $slug): bool
    {
        try {
            return class_exists('CMS\\PluginManager') && CMS\PluginManager::instance()->isPluginActive($slug);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function is_m365_toolbox_active(): bool
    {
        $slugs = ['m365toolbox', 'cms-m365toolbox', 'cms-m365tools'];

        foreach ($slugs as $slug) {
            try {
                if (function_exists('cms_plugin_active') && cms_plugin_active($slug)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Continue with PluginManager fallback.
            }

            if ($this->is_plugin_active($slug)) {
                return true;
            }
        }

        return false;
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
