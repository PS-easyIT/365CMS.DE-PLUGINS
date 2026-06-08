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
    private const SHARED_PUBLIC_I18N_CONTRACT = 'shared/public/plugin-public-i18n.php';
    private static ?self $instance = null;
    private ?array $settingsCache = null;
    private ?array $statsCache = null;
    private ?bool $domainLandingRequestCache = null;
    private ?bool $landingPathRequestCache = null;
    private bool $renderingPublicPage = false;
    /** @var array<string,string> */
    private array $resolvedTableCache = [];
    /** @var array<string,bool> */
    private array $columnExistsCache = [];
    /** @var array<int,array<string,mixed>> */
    private array $landingEventsForSchema = [];
    private ?string $requestLanguageCache = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->load_public_i18n_contract();
        CMS\Hooks::addAction('register_routes', [$this, 'register_routes'], 10);
        CMS\Hooks::addAction('head', [$this, 'enqueue_styles'], 10);
        CMS\Hooks::addAction('head', [$this, 'output_dynamic_styles'], 20);
        CMS\Hooks::addAction('head', [$this, 'output_event_structured_data'], 30);
        CMS\Hooks::addAction('head', [$this, 'output_analytics_head'], 90);
        CMS\Hooks::addAction('body_end', [$this, 'enqueue_scripts'], 20);
        CMS\Hooks::addAction('body_end', [$this, 'output_analytics_body_end'], 90);
        CMS\Hooks::addFilter('body_class', [$this, 'body_class'], 30);
    }

    public function body_class(string $classes): string
    {
        if (!$this->is_landing_path_request()) {
            return $classes;
        }

        $classList = preg_split('/\s+/', trim($classes)) ?: [];
        $classList = array_values(array_filter($classList, static fn(string $class): bool => $class !== ''));

        foreach (['cms-365network-public', 'cms-365network-no-theme-gap'] as $class) {
            if (!in_array($class, $classList, true)) {
                $classList[] = $class;
            }
        }

        return implode(' ', $classList);
    }

    public function register_routes($router): void
    {
        if (!is_object($router) || !method_exists($router, 'addRoute')) {
            error_log('[cms-365network] public routes could not be registered: invalid router instance');
            return;
        }

        $settings = $this->settings();
        $routeSlug = $this->route_slug($settings);
        $localizedBasePath = $this->localized_public_path($routeSlug, 'en');
        $localizedSearchPath = $this->localized_public_path($routeSlug . '/search', 'en');
        $localizedRootPath = $this->localized_public_path('', 'en');

        $router->addRoute('GET', '/', [$this, 'render_root_or_home']);
        $router->addRoute('GET', '/' . $routeSlug . '/search', [$this, 'render_search']);
        $router->addRoute('GET', '/' . $routeSlug, [$this, 'render_landing']);
        $router->addRoute('GET', $localizedRootPath, [$this, 'render_landing']);
        $router->addRoute('GET', $localizedBasePath, [$this, 'render_landing']);
        $router->addRoute('GET', $localizedSearchPath, [$this, 'render_search']);
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

        $hubSettings = CMS_365NETWORK_Database::instance()->get_hub_settings();
        $lang = $this->public_language();
        $postsLimit = (bool) ($hubSettings['hub_posts_visible'] ?? true)
            ? $this->clamp_int((int) ($hubSettings['hub_posts_limit'] ?? 6), 1, 6)
            : 0;
        $eventLimit = max(
            (int) ($settings['sidebar_events_count'] ?? 3),
            (int) ($hubSettings['hub_next_events_limit'] ?? 3),
            (int) ($hubSettings['hub_spotlight_limit'] ?? 7)
        );
        $companyLimit = max(
            (int) ($settings['random_companies_count'] ?? 1),
            (int) ($hubSettings['hub_partnerband_limit'] ?? 4),
            (int) ($hubSettings['hub_partner_companies_limit'] ?? 3),
            (int) ($hubSettings['hub_spotlight_limit'] ?? 7)
        );
        $expertLimit = max(
            (int) ($settings['random_experts_count'] ?? 1),
            (int) ($hubSettings['hub_partner_experts_limit'] ?? 3),
            (int) ($hubSettings['hub_spotlight_limit'] ?? 7)
        );
        $speakerLimit = max(
            (int) ($settings['random_speakers_count'] ?? 1),
            (int) ($hubSettings['hub_spotlight_limit'] ?? 7)
        );

        $data = [
            'settings' => $settings,
            'hub_settings' => $hubSettings,
            'areas' => $this->build_area_cards($settings),
            'events' => $this->fetch_upcoming_events($eventLimit),
            'speakers' => $this->fetch_random_speakers($speakerLimit),
            'companies' => $this->fetch_random_companies($companyLimit),
            'experts' => $this->fetch_random_experts($expertLimit),
            'partner_companies' => $this->fetch_partner_companies($companyLimit),
            'partner_experts' => $this->fetch_partner_experts($expertLimit),
            'stats' => $this->fetch_stats(),
            'current_host' => $this->current_host(),
            'network_search_url' => $this->network_search_url($settings, $lang),
            'latest_posts' => $postsLimit > 0 ? $this->fetch_latest_posts($postsLimit) : [],
            'public_lang' => $lang,
            'public_i18n' => $this->public_i18n_values(),
        ];
        $data['toolbox_tools'] = $this->fetch_toolbox_links((int) ($data['hub_settings']['hub_toolbox_limit'] ?? 12));
        $this->landingEventsForSchema = is_array($data['events']) ? $data['events'] : [];

        $bufferLevel = ob_get_level();
        $this->renderingPublicPage = true;
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
                $partnerCompanies = is_array($data['partner_companies'] ?? null) ? $data['partner_companies'] : [];
                $partnerExperts = is_array($data['partner_experts'] ?? null) ? $data['partner_experts'] : [];
                $toolboxTools = is_array($data['toolbox_tools'] ?? null) ? $data['toolbox_tools'] : [];
                $latestPosts = is_array($data['latest_posts'] ?? null) ? $data['latest_posts'] : [];
                $stats = is_array($data['stats'] ?? null) ? $data['stats'] : [];
                $current_host = (string) ($data['current_host'] ?? '');
                $networkSearchUrl = (string) ($data['network_search_url'] ?? $this->network_search_url($settings, $lang));
                $publicLang = (string) ($data['public_lang'] ?? $lang);
                $publicI18n = is_array($data['public_i18n'] ?? null) ? $data['public_i18n'] : $this->public_i18n_values();
                include $template;
            }
            CMS\ThemeManager::instance()->getFooter();
            $this->renderingPublicPage = false;
            $this->landingEventsForSchema = [];
        } catch (\Throwable $e) {
            $this->renderingPublicPage = false;
            $this->landingEventsForSchema = [];
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            error_log('CMS 365NETWORK landing render failed: ' . $e->getMessage());
            http_response_code(500);
            echo '<!DOCTYPE html><html lang="de"><body><h1>365NETWORK</h1><p>Die Landingpage konnte aktuell nicht dargestellt werden.</p></body></html>';
            exit;
        }
    }

    public function render_search(): void
    {
        $settings = $this->settings();
        if ((string) ($settings['landing_enabled'] ?? '1') !== '1') {
            CMS\ThemeManager::instance()->render('home');
            return;
        }

        $hubSettings = CMS_365NETWORK_Database::instance()->get_hub_settings();
        $lang = $this->public_language();
        $searchParam = $this->search_param($hubSettings);
        $searchQuery = $this->search_query_from_request($searchParam);
        $searchResults = strlen($searchQuery) >= 2 ? $this->search_network($searchQuery, 10) : $this->empty_search_groups();
        $searchTotal = 0;
        foreach ($searchResults as $group) {
            $searchTotal += is_array($group['items'] ?? null) ? count($group['items']) : 0;
        }

        $bufferLevel = ob_get_level();
        $this->renderingPublicPage = true;
        try {
            $titleSuffix = $searchQuery !== '' ? ': ' . $searchQuery : '';
            CMS\ThemeManager::instance()->getHeader(['title' => $this->tr('search.page_title', $lang, '365NETWORK Suche', '365NETWORK Search') . $titleSuffix]);
            $template = CMS_365NETWORK_PLUGIN_DIR . 'templates/search.php';
            if (is_file($template)) {
                $searchUrl = $this->network_search_url($settings, $lang);
                $publicLang = $lang;
                $publicI18n = $this->public_i18n_values();
                include $template;
            }
            CMS\ThemeManager::instance()->getFooter();
            $this->renderingPublicPage = false;
        } catch (\Throwable $e) {
            $this->renderingPublicPage = false;
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            error_log('CMS 365NETWORK search render failed: ' . $e->getMessage());
            http_response_code(500);
            echo '<!DOCTYPE html><html lang="de"><body><h1>365NETWORK Suche</h1><p>Die Suche konnte aktuell nicht dargestellt werden.</p></body></html>';
            exit;
        }
    }

    public function enqueue_styles(): void
    {
        if (!$this->is_public_page_request()) {
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
        if (!$this->is_public_page_request()) {
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
            '--hub-radius' => $this->clamp_int($settings['card_radius'] ?? 2, 0, 2) . 'px',
            '--n365-primary' => $this->hex_color((string) ($settings['primary_color'] ?? ''), '#e6a817'),
            '--n365-accent' => $this->hex_color((string) ($settings['accent_color'] ?? ''), '#e6a817'),
            '--n365-bg' => $this->hex_color((string) ($settings['background_color'] ?? ''), '#e8ecf0'),
            '--n365-surface' => $this->hex_color((string) ($settings['surface_color'] ?? ''), '#ffffff'),
            '--n365-text' => $this->hex_color((string) ($settings['text_color'] ?? ''), '#1a2e4a'),
            '--n365-muted' => $this->hex_color((string) ($settings['muted_color'] ?? ''), '#5a6a7a'),
            '--n365-border' => $this->hex_color((string) ($settings['border_color'] ?? ''), '#dce3ec'),
            '--n365-max' => $this->clamp_int($settings['content_width'] ?? 1180, 920, 1500) . 'px',
            '--n365-radius' => $this->clamp_int($settings['card_radius'] ?? 2, 0, 2) . 'px',
            '--n365-gap' => $this->clamp_int($settings['section_gap'] ?? 28, 16, 80) . 'px',
            '--n365-featured-bg' => $this->hex_color((string) ($hubSettings['hub_featured_bg_color'] ?? ''), '#ffffff'),
            '--n365-featured-text' => $this->hex_color((string) ($hubSettings['hub_featured_text_color'] ?? ''), '#1a2e4a'),
            '--n365-featured-accent' => $this->hex_color((string) ($hubSettings['hub_featured_accent_color'] ?? ''), '#e6a817'),
            '--n365-featured-radius' => $this->clamp_int($hubSettings['hub_featured_radius'] ?? 2, 0, 2) . 'px',
            '--n365-featured-image-height' => $this->clamp_int($hubSettings['hub_featured_image_height'] ?? 320, 120, 720) . 'px',
            '--n365-hero-bg' => $this->hex_color((string) ($hubSettings['hub_hero_bg_color'] ?? ''), '#0d1d33'),
            '--n365-hero-text' => $this->hex_color((string) ($hubSettings['hub_hero_text_color'] ?? ''), '#ffffff'),
            '--n365-hero-accent' => $this->hex_color((string) ($hubSettings['hub_hero_accent_color'] ?? ''), '#d6951a'),
            '--n365-hero-radius' => $this->clamp_int($hubSettings['hub_hero_radius'] ?? 0, 0, 2) . 'px',
            '--n365-hero-image-width' => $this->clamp_int($hubSettings['hub_hero_image_width'] ?? 250, 80, 1200) . 'px',
            '--n365-hero-image-height' => $this->clamp_int($hubSettings['hub_hero_image_height'] ?? 200, 80, 800) . 'px',
            '--n365-stats-bg' => $this->hex_color((string) ($hubSettings['hub_stats_bg_color'] ?? ''), '#f0f3f7'),
            '--n365-stats-text' => $this->hex_color((string) ($hubSettings['hub_stats_text_color'] ?? ''), '#1a2e4a'),
            '--n365-stats-accent' => $this->hex_color((string) ($hubSettings['hub_stats_accent_color'] ?? ''), '#e6a817'),
            '--n365-stats-radius' => $this->clamp_int($hubSettings['hub_stats_radius'] ?? 2, 0, 2) . 'px',
            '--n365-band-bg' => $this->hex_color((string) ($hubSettings['hub_band_bg_color'] ?? ''), '#ffffff'),
            '--n365-band-text' => $this->hex_color((string) ($hubSettings['hub_band_text_color'] ?? ''), '#1a2e4a'),
            '--n365-band-accent' => $this->hex_color((string) ($hubSettings['hub_band_accent_color'] ?? ''), '#e6a817'),
            '--n365-band-radius' => $this->clamp_int($hubSettings['hub_band_radius'] ?? 2, 0, 2) . 'px',
            '--n365-areas-bg' => $this->hex_color((string) ($hubSettings['hub_areas_bg_color'] ?? ''), '#ffffff'),
            '--n365-areas-text' => $this->hex_color((string) ($hubSettings['hub_areas_text_color'] ?? ''), '#1a2e4a'),
            '--n365-areas-accent' => $this->hex_color((string) ($hubSettings['hub_areas_accent_color'] ?? ''), '#e6a817'),
            '--n365-areas-radius' => $this->clamp_int($hubSettings['hub_areas_radius'] ?? 2, 0, 2) . 'px',
            '--n365-toolbox-bg' => $this->hex_color((string) ($hubSettings['hub_toolbox_bg_color'] ?? ''), '#ffffff'),
            '--n365-toolbox-text' => $this->hex_color((string) ($hubSettings['hub_toolbox_text_color'] ?? ''), '#1a2e4a'),
            '--n365-toolbox-accent' => $this->hex_color((string) ($hubSettings['hub_toolbox_accent_color'] ?? ''), '#e6a817'),
            '--n365-toolbox-radius' => $this->clamp_int($hubSettings['hub_toolbox_radius'] ?? 2, 0, 2) . 'px',
            '--n365-partnerband-bg' => $this->hex_color((string) ($hubSettings['hub_partnerband_bg_color'] ?? ''), '#0a1626'),
            '--n365-partnerband-text' => $this->hex_color((string) ($hubSettings['hub_partnerband_text_color'] ?? ''), '#dfe8f2'),
            '--n365-partnerband-accent' => $this->hex_color((string) ($hubSettings['hub_partnerband_accent_color'] ?? ''), '#d6951a'),
            '--n365-next-bg' => $this->hex_color((string) ($hubSettings['hub_next_events_bg_color'] ?? ''), '#ffffff'),
            '--n365-next-text' => $this->hex_color((string) ($hubSettings['hub_next_events_text_color'] ?? ''), '#1a2e4a'),
            '--n365-next-accent' => $this->hex_color((string) ($hubSettings['hub_next_events_accent_color'] ?? ''), '#d6951a'),
            '--n365-spotlight-bg' => $this->hex_color((string) ($hubSettings['hub_spotlight_bg_color'] ?? ''), '#ffffff'),
            '--n365-spotlight-text' => $this->hex_color((string) ($hubSettings['hub_spotlight_text_color'] ?? ''), '#1a2e4a'),
            '--n365-spotlight-accent' => $this->hex_color((string) ($hubSettings['hub_spotlight_accent_color'] ?? ''), '#d6951a'),
            '--n365-partner-cols-bg' => $this->hex_color((string) ($hubSettings['hub_partner_columns_bg_color'] ?? ''), '#ffffff'),
            '--n365-partner-cols-text' => $this->hex_color((string) ($hubSettings['hub_partner_columns_text_color'] ?? ''), '#1a2e4a'),
            '--n365-partner-cols-accent' => $this->hex_color((string) ($hubSettings['hub_partner_columns_accent_color'] ?? ''), '#d6951a'),
        ];

        $css = '.cms-network-hub-wrap.n365-landing{';
        foreach ($vars as $name => $value) {
            $css .= $name . ':' . $value . ';';
        }
        $css .= '}';

        echo '<style id="cms-365network-vars">' . htmlspecialchars($css, ENT_NOQUOTES, 'UTF-8') . '</style>' . "\n";
    }

    public function enqueue_scripts(): void
    {
        if (!$this->is_public_page_request()) {
            return;
        }

        $js = CMS_365NETWORK_PLUGIN_DIR . 'assets/js/public.js';
        if (!is_file($js)) {
            return;
        }

        $version = (string) filemtime($js);
        if (function_exists('cms_enqueue_script')) {
            cms_enqueue_script('cms-365network-public', CMS_365NETWORK_PLUGIN_URL . 'assets/js/public.js', [], $version, true);
            return;
        }

        echo '<script src="' . htmlspecialchars(CMS_365NETWORK_PLUGIN_URL . 'assets/js/public.js?v=' . $version, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
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
        if (!$this->is_public_page_request()) {
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
                'plugin_slug' => 'cms-365neteventsandspeaker',
                'icon' => 'calendar-event',
                'label' => (string) ($settings['events_card_title'] ?? 'Events'),
                'text' => (string) ($settings['events_card_text'] ?? ''),
                'url' => $this->safe_url((string) ($settings['events_card_url'] ?? '/events')),
                'stat' => 'events',
                'integration_active' => $this->is_area_integration_active_multi(
                    ['cms-365NETeventsandspeaker', 'cms-365neteventsandspeaker', 'cms-365NETevents', 'cms-365netevents'],
                    ['CMS_365NET_Events'],
                    ['events', '365net_events', 'event_events']
                ),
            ],
            [
                'key' => 'speakers',
                'plugin_slug' => 'cms-365neteventsandspeaker',
                'icon' => 'microphone-2',
                'label' => (string) ($settings['speakers_card_title'] ?? 'Speaker'),
                'text' => (string) ($settings['speakers_card_text'] ?? ''),
                'url' => $this->safe_url((string) ($settings['speakers_card_url'] ?? '/speakers')),
                'stat' => 'speakers',
                'integration_active' => $this->is_area_integration_active_multi(
                    ['cms-365NETeventsandspeaker', 'cms-365neteventsandspeaker', 'cms-365NETevents', 'cms-365netevents'],
                    ['CMS_365NET_Events'],
                    ['speakers', '365net_event_speakers', 'event_speakers']
                ),
            ],
            [
                'key' => 'companies',
                'plugin_slug' => 'cms-365netexpertsandcompanie',
                'icon' => 'building-community',
                'label' => (string) ($settings['companies_card_title'] ?? 'Firmen'),
                'text' => (string) ($settings['companies_card_text'] ?? ''),
                'url' => $this->safe_url((string) ($settings['companies_card_url'] ?? '/companies')),
                'stat' => 'companies',
                'integration_active' => $this->is_area_integration_active_multi(
                    ['cms-365NETexpertsandcompanie', 'cms-365netexpertsandcompanie', 'cms-companies'],
                    ['CMS_365NET_Experts_And_Companie', 'CMS_Companies'],
                    ['companies', '365net_excomp_companies', 'company']
                ),
            ],
            [
                'key' => 'experts',
                'plugin_slug' => 'cms-365netexpertsandcompanie',
                'icon' => 'user-star',
                'label' => (string) ($settings['experts_card_title'] ?? 'Experten'),
                'text' => (string) ($settings['experts_card_text'] ?? ''),
                'url' => $this->safe_url((string) ($settings['experts_card_url'] ?? '/experts')),
                'stat' => 'experts',
                'integration_active' => $this->is_area_integration_active_multi(
                    ['cms-365NETexpertsandcompanie', 'cms-365netexpertsandcompanie', 'cms-experts'],
                    ['CMS_365NET_Experts_And_Companie', 'CMS_Experts'],
                    ['experts', '365net_excomp_experts', 'expert']
                ),
            ],
        ];
    }

    private function is_area_integration_active(string $slug, string $className, string $table): bool
    {
        return $this->is_area_integration_active_multi([$slug], [$className], [$table]);
    }

    /**
     * @param array<int, string> $slugs
     * @param array<int, string> $classNames
     * @param array<int, string> $tables
     */
    private function is_area_integration_active_multi(array $slugs, array $classNames, array $tables): bool
    {
        foreach ($slugs as $slug) {
            $slug = trim($slug);
            if ($slug === '') {
                continue;
            }

            $slugCandidates = array_values(array_unique([$slug, strtolower($slug)]));
            foreach ($slugCandidates as $slugCandidate) {
                try {
                    if (function_exists('cms_plugin_active') && (bool) cms_plugin_active($slugCandidate)) {
                        return true;
                    }
                } catch (\Throwable $e) {
                    error_log('CMS 365NETWORK area plugin status via cms_plugin_active failed for ' . $slugCandidate . ': ' . $e->getMessage());
                }

                try {
                    if (class_exists('CMS\\PluginManager') && CMS\PluginManager::instance()->isPluginActive($slugCandidate)) {
                        return true;
                    }
                } catch (\Throwable $e) {
                    error_log('CMS 365NETWORK area plugin status via PluginManager failed for ' . $slugCandidate . ': ' . $e->getMessage());
                }
            }
        }

        foreach ($classNames as $className) {
            $className = trim($className);
            if ($className !== '' && class_exists($className)) {
                return true;
            }
        }

        foreach ($tables as $table) {
            if ($this->table_exists($table)) {
                return true;
            }
        }

        return false;
    }

    private function fetch_upcoming_events(int $limit): array
    {
        $limit = $this->clamp_int($limit, 0, 8);
        if ($limit === 0 || !$this->is_integration_available('cms-365neteventsandspeaker', 'CMS_365NET_Events', 'events')) {
            return [];
        }

        $eventsTable = $this->resolve_table_name('events');
        if ($eventsTable === '') {
            return [];
        }

        try {
            $db = CMS\Database::instance();
            $params = [];
            $statusWhere = $this->status_filter_sql('events', ['published', 'active'], $params);
            $params[] = $limit;
            $dateColumn = $this->column_exists('events', 'start_date') ? 'start_date' : 'event_date';
            $timeColumn = $this->column_exists('events', 'start_time') ? 'start_time' : 'event_time';
            $sql = "SELECT id, title, slug, {$dateColumn} AS event_date, end_date, {$timeColumn} AS event_time, city, location, image_url, category, categories, tags, price_class
                FROM `{$eventsTable}`
                WHERE {$statusWhere} AND ({$dateColumn} >= CURDATE() OR (end_date IS NOT NULL AND end_date >= CURDATE()))
                ORDER BY event_date ASC, event_time ASC
                LIMIT ?";
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
        if ($limit === 0 || !$this->is_integration_available('cms-365neteventsandspeaker', 'CMS_365NET_Events', 'speakers')) {
            return [];
        }

        $speakersTable = $this->resolve_table_name('speakers');
        if ($speakersTable === '') {
            return [];
        }

        try {
            $db = CMS\Database::instance();
            $offset = $this->random_offset('speakers', ['published', 'active'], $limit);
            $params = [];
            $statusWhere = $this->status_filter_sql('speakers', ['published', 'active'], $params);
            $params[] = $limit;
            $params[] = $offset;
            $sql = "SELECT id, first_name, last_name, display_name, slug, topic AS position, '' AS company, avatar_url AS photo_url, location AS location_city, categories, tags, price_class
                FROM `{$speakersTable}`
                WHERE {$statusWhere} AND first_name IS NOT NULL AND first_name <> '' AND last_name IS NOT NULL AND last_name <> ''
                ORDER BY id ASC
                LIMIT ? OFFSET ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $this->with_entity_urls(array_map([$this, 'object_to_array'], $stmt->fetchAll(\PDO::FETCH_OBJ) ?: []), 'speaker');
            return $this->shuffle_rows($rows);
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

        $companiesTable = $this->resolve_table_name('companies');
        if ($companiesTable === '') {
            return [];
        }

        try {
            $db = CMS\Database::instance();
            $offset = $this->random_offset('companies', ['active'], $limit);
            $params = [];
            $statusWhere = $this->status_filter_sql('companies', ['active', 'published'], $params);
            $params[] = $limit;
            $params[] = $offset;
            $sql = "SELECT id, name, industry, logo_url, location_city, is_partner, is_top_partner
                FROM `{$companiesTable}`
                WHERE {$statusWhere}
                ORDER BY id ASC
                LIMIT ? OFFSET ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $this->with_entity_urls(array_map([$this, 'object_to_array'], $stmt->fetchAll(\PDO::FETCH_OBJ) ?: []), 'company');
            $rows = $this->shuffle_rows($rows);
            return $this->move_random_top_partner_to_front($rows);
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

        $expertsTable = $this->resolve_table_name('experts');
        if ($expertsTable === '') {
            return [];
        }

        try {
            $db = CMS\Database::instance();
            $offset = $this->random_offset('experts', ['active'], $limit);
            $params = [];
            $statusWhere = $this->status_filter_sql('experts', ['active', 'published'], $params);
            $params[] = $limit;
            $params[] = $offset;
            $sql = "SELECT id, first_name, last_name, position, company, photo_url, location_city
                FROM `{$expertsTable}`
                WHERE {$statusWhere}
                ORDER BY id ASC
                LIMIT ? OFFSET ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $this->with_entity_urls(array_map([$this, 'object_to_array'], $stmt->fetchAll(\PDO::FETCH_OBJ) ?: []), 'expert');
            return $this->shuffle_rows($rows);
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK fetch experts failed: ' . $e->getMessage());
            return [];
        }
    }

    private function fetch_partner_companies(int $limit): array
    {
        $limit = $this->clamp_int($limit, 0, 12);
        if ($limit === 0 || !$this->is_integration_available('cms-companies', 'CMS_Companies', 'companies')) {
            return [];
        }

        $companiesTable = $this->resolve_table_name('companies');
        if ($companiesTable === '') {
            return [];
        }

        try {
            $db = CMS\Database::instance();
            $params = [];
            $statusWhere = $this->status_filter_sql('companies', ['active', 'published'], $params);
            $params[] = $limit;
            $sql = "SELECT id, name, industry, logo_url, location_city, is_partner, is_top_partner
                FROM `{$companiesTable}`
                WHERE {$statusWhere} AND (is_partner = 1 OR is_top_partner = 1)
                ORDER BY is_top_partner DESC, is_partner DESC, name ASC
                LIMIT ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = array_map([$this, 'object_to_array'], $stmt->fetchAll(\PDO::FETCH_OBJ) ?: []);
            $rows = $this->with_entity_urls($rows, 'company');
            $rows = $this->shuffle_rows($rows);
            return $this->move_random_top_partner_to_front($rows);
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK fetch partner companies failed: ' . $e->getMessage());
            return [];
        }
    }

    private function fetch_partner_experts(int $limit): array
    {
        $limit = $this->clamp_int($limit, 0, 12);
        if ($limit === 0 || !$this->is_integration_available('cms-experts', 'CMS_Experts', 'experts')) {
            return [];
        }

        $expertsTable = $this->resolve_table_name('experts');
        if ($expertsTable === '') {
            return [];
        }

        try {
            $db = CMS\Database::instance();
            $params = [];
            $statusWhere = $this->status_filter_sql('experts', ['active', 'published'], $params);
            $params[] = $limit;
            $sql = "SELECT id, first_name, last_name, position, company, photo_url, location_city
                FROM `{$expertsTable}`
                WHERE {$statusWhere}
                ORDER BY last_name ASC, first_name ASC, id ASC
                LIMIT ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = array_map([$this, 'object_to_array'], $stmt->fetchAll(\PDO::FETCH_OBJ) ?: []);
            $rows = $this->with_entity_urls($rows, 'expert');
            return $this->shuffle_rows($rows);
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK fetch partner experts failed: ' . $e->getMessage());
            return [];
        }
    }

    private function fetch_toolbox_links(int $limit): array
    {
        $limit = $this->clamp_int($limit, 1, 50);
        if (!$this->is_m365_toolbox_active()) {
            return [];
        }

        $legacyTools = $this->fetch_legacy_toolbox_links($limit);
        if ($legacyTools !== []) {
            return $legacyTools;
        }

        return $this->fetch_m365tools_registry_links($limit);
    }

    private function fetch_legacy_toolbox_links(int $limit): array
    {

        $resolvedTable = $this->resolve_table_name('m365toolbox_links');
        if ($resolvedTable === '') {
            return [];
        }

        try {
            $db = CMS\Database::instance();
            $sql = sprintf("SELECT label, url, icon, description
                FROM `{$resolvedTable}`
                WHERE status = ? AND show_on_hub = ?
                ORDER BY sort_order ASC, id ASC
                LIMIT %d", $limit);
            $stmt = $db->prepare($sql);
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

    private function fetch_m365tools_registry_links(int $limit): array
    {
        if (!class_exists('CMS_M365CALCULATOR_Tool_Registry', false) || !method_exists('CMS_M365CALCULATOR_Tool_Registry', 'ordered_tools')) {
            return [];
        }

        try {
            $rows = CMS_M365CALCULATOR_Tool_Registry::ordered_tools(true);
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK fetch m365tools registry failed: ' . $e->getMessage());
            return [];
        }

        $tools = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $status = strtolower(trim((string) ($row['status'] ?? '')));
            if (!in_array($status, ['live', 'beta'], true)) {
                continue;
            }

            $label = trim((string) ($row['title'] ?? ''));
            $url = $this->safe_url((string) ($row['url'] ?? ''));
            if ($label === '' || $url === '#') {
                continue;
            }

            $tools[] = [
                'label' => $label,
                'url' => $url,
                'icon' => $this->toolbox_registry_icon_class((string) ($row['icon'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
            ];

            if (count($tools) >= $limit) {
                break;
            }
        }

        return $tools;
    }

    private function fetch_latest_posts(int $limit = 6): array
    {
        $limit = $this->clamp_int($limit, 1, 6);
        $postsTable = $this->resolve_table_name('posts');
        if ($postsTable === '') {
            $postsTable = $this->default_table_name('posts');
        }
        if ($postsTable === '') {
            return [];
        }

        $categoriesTable = $this->resolve_table_name('post_categories');
        $categorySelect = $categoriesTable !== '' ? ', c.name AS category_name, c.slug AS category_slug' : ', NULL AS category_name, NULL AS category_slug';
        $categoryJoin = $categoriesTable !== '' ? " LEFT JOIN `{$categoriesTable}` c ON c.id = p.category_id" : '';
        $publicationWhere = function_exists('cms_post_publication_where')
            ? \cms_post_publication_where('p')
            : "p.status = 'published'";

        try {
            $stmt = CMS\Database::instance()->prepare("SELECT p.id, p.title, p.slug, p.slug_en, p.excerpt, p.content, p.published_at, p.created_at{$categorySelect}
                FROM `{$postsTable}` p{$categoryJoin}
                WHERE {$publicationWhere}
                ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                LIMIT ?");
            $stmt->execute([$limit]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK latest posts failed: ' . $e->getMessage());
            return [];
        }

        return $this->prepare_latest_posts(array_values(array_filter($rows, 'is_array')));
    }

    private function prepare_latest_posts(array $rows): array
    {
        $posts = [];
        $locale = function_exists('phinit_get_current_locale') ? (string) phinit_get_current_locale() : 'de';
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (class_exists('CMS\\Services\\ContentLocalizationService')) {
                try {
                    $row = \CMS\Services\ContentLocalizationService::getInstance()->localizePost($row, $locale);
                } catch (\Throwable $e) {
                    // Lokale Fallback-Werte verwenden.
                }
            }

            $title = trim((string) ($row['title'] ?? ''));
            $url = $this->post_url($row, $locale);
            if ($title === '' || $url === '#') {
                continue;
            }

            $dateRaw = trim((string) ($row['published_at'] ?? ($row['created_at'] ?? '')));
            $timestamp = $dateRaw !== '' ? strtotime($dateRaw) : false;
            $dateLabel = $dateRaw !== '' && function_exists('phinit_format_date')
                ? (string) phinit_format_date($dateRaw, 'long', $locale)
                : ($timestamp !== false ? date('d.m.Y', $timestamp) : '');
            $excerptSource = trim((string) ($row['excerpt'] ?? ''));
            $contentSource = trim((string) ($row['content'] ?? ''));
            $excerpt = $this->plain_excerpt($excerptSource !== '' ? $excerptSource : $contentSource, 180);
            $categoryName = trim((string) ($row['category_name'] ?? ''));
            $categorySlug = trim((string) ($row['category_slug'] ?? ''));

            $posts[] = [
                'title' => $title,
                'url' => $url,
                'excerpt' => $excerpt,
                'date_label' => $dateLabel,
                'date_iso' => $timestamp !== false ? date('Y-m-d', $timestamp) : '',
                'read_time' => $this->reading_time($contentSource !== '' ? $contentSource : $excerpt),
                'category_name' => $categoryName,
                'category_url' => $categorySlug !== '' ? rtrim((string) SITE_URL, '/') . '/category/' . rawurlencode($categorySlug) : '',
            ];

            if (count($posts) >= 6) {
                break;
            }
        }

        return $posts;
    }

    private function post_url(array $post, string $locale): string
    {
        if (class_exists('CMS\\Services\\PermalinkService')) {
            try {
                $url = \CMS\Services\PermalinkService::getInstance()->buildPostUrl($post, $locale);
                return $this->safe_url($url);
            } catch (\Throwable $e) {
                // Fallback unten verwenden.
            }
        }

        $slug = trim((string) ($post['slug'] ?? ''));
        if ($slug === '') {
            return '#';
        }

        return rtrim((string) SITE_URL, '/') . '/blog/' . rawurlencode($slug);
    }

    private function plain_excerpt(string $content, int $length = 180): string
    {
        $content = trim($content);
        if ($content === '') {
            return '';
        }

        $decoded = json_decode($content, true);
        if (is_array($decoded) && isset($decoded['blocks']) && is_array($decoded['blocks'])) {
            $parts = [];
            foreach ($decoded['blocks'] as $block) {
                if (!is_array($block) || !is_array($block['data'] ?? null)) {
                    continue;
                }
                foreach (['text', 'caption', 'message', 'title'] as $key) {
                    if (isset($block['data'][$key]) && is_string($block['data'][$key])) {
                        $parts[] = $block['data'][$key];
                    }
                }
            }
            $content = implode(' ', $parts);
        }

        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strimwidth')) {
            return mb_strimwidth($text, 0, $length, '…', 'UTF-8');
        }

        return strlen($text) > $length ? rtrim(substr($text, 0, max(0, $length - 1))) . '…' : $text;
    }

    private function reading_time(string $content): int
    {
        $plain = $this->plain_excerpt($content, 5000);
        if ($plain === '') {
            return 0;
        }

        return max(1, (int) ceil(str_word_count($plain) / 220));
    }

    private function toolbox_registry_icon_class(string $icon): string
    {
        $icon = strtolower(trim($icon));
        $map = [
            'addons' => 'ti-apps',
            'calculator' => 'ti-calculator',
            'comparison' => 'ti-columns-3',
            'copilot' => 'ti-sparkles',
            'license' => 'ti-certificate',
            'mailbox' => 'ti-mail',
            'phone' => 'ti-phone',
            'roi' => 'ti-chart-line',
            'storage' => 'ti-database',
        ];

        return $map[$icon] ?? $this->toolbox_icon_class($icon);
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
        if ($this->statsCache !== null) {
            return $this->statsCache;
        }

        return $this->statsCache = [
            'events' => $this->count_events_stat(),
            'speakers' => $this->count_speakers_stat(),
            'companies' => $this->count_companies_stat(),
            'experts' => $this->count_experts_stat(),
        ];
    }

    private function count_events_stat(): int
    {
        $count = $this->count_public_rows('events', ['published', 'active', 'completed']);
        return $count > 0 ? $count : $this->count_public_rows('events', ['published', 'active', 'completed']);
    }

    private function count_speakers_stat(): int
    {
        $count = $this->count_public_rows('speakers', ['published', 'active']);
        return $count > 0 ? $count : 0;
    }

    private function count_companies_stat(): int
    {
        $count = $this->count_via_plugin_api('cms-companies', 'CMS_Companies_Database', 'get_companies_count', [['status' => 'active'], ['status' => 'any']]);
        return $count > 0 ? $count : $this->count_public_rows('companies', ['active', 'published']);
    }

    private function count_experts_stat(): int
    {
        $count = $this->count_via_plugin_api('cms-experts', 'CMS_Experts_Database', 'countExperts', ['active', '']);
        return $count > 0 ? $count : $this->count_public_rows('experts', ['active', 'published']);
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

    private function search_network(string $query, int $limitPerType): array
    {
        $limitPerType = $this->clamp_int($limitPerType, 1, 20);

        return [
            'events' => [
                'key' => 'events',
                'label' => $this->tr('entity.events', $this->public_language(), 'Events', 'Events'),
                'icon' => 'ti-calendar-event',
                'items' => $this->search_events($query, $limitPerType),
            ],
            'speakers' => [
                'key' => 'speakers',
                'label' => $this->tr('entity.speakers', $this->public_language(), 'Speaker', 'Speakers'),
                'icon' => 'ti-microphone-2',
                'items' => $this->search_people_table('speakers', 'speaker', $this->tr('entity.speaker', $this->public_language(), 'Speaker', 'Speaker'), 'ti-microphone-2', $query, $limitPerType),
            ],
            'companies' => [
                'key' => 'companies',
                'label' => $this->tr('entity.companies', $this->public_language(), 'Firmen', 'Companies'),
                'icon' => 'ti-building-community',
                'items' => $this->search_companies($query, $limitPerType),
            ],
            'experts' => [
                'key' => 'experts',
                'label' => $this->tr('entity.experts', $this->public_language(), 'Experten', 'Experts'),
                'icon' => 'ti-user-star',
                'items' => $this->search_people_table('experts', 'expert', $this->tr('entity.expert', $this->public_language(), 'Experte', 'Expert'), 'ti-user-star', $query, $limitPerType),
            ],
        ];
    }

    private function empty_search_groups(): array
    {
        return [
            'events' => ['key' => 'events', 'label' => $this->tr('entity.events', $this->public_language(), 'Events', 'Events'), 'icon' => 'ti-calendar-event', 'items' => []],
            'speakers' => ['key' => 'speakers', 'label' => $this->tr('entity.speakers', $this->public_language(), 'Speaker', 'Speakers'), 'icon' => 'ti-microphone-2', 'items' => []],
            'companies' => ['key' => 'companies', 'label' => $this->tr('entity.companies', $this->public_language(), 'Firmen', 'Companies'), 'icon' => 'ti-building-community', 'items' => []],
            'experts' => ['key' => 'experts', 'label' => $this->tr('entity.experts', $this->public_language(), 'Experten', 'Experts'), 'icon' => 'ti-user-star', 'items' => []],
        ];
    }

    private function search_events(string $query, int $limit): array
    {
        $rows = $this->search_table(
            'events',
            ['published', 'active'],
            ['id', 'title', 'slug', 'excerpt', 'description', 'start_date', 'event_date', 'start_time', 'event_time', 'city', 'location', 'category', 'categories', 'organizer', 'organizer_name'],
            ['title', 'excerpt', 'description', 'category', 'categories', 'city', 'location', 'organizer', 'organizer_name', 'tags'],
            $query,
            $limit,
            $this->column_exists('events', 'event_date') ? 'event_date ASC, id DESC' : 'id DESC'
        );

        $items = [];
        foreach ($rows as $row) {
            $title = trim((string) ($row['title'] ?? '')) ?: $this->tr('entity.event', $this->public_language(), 'Event', 'Event');
            $date = $this->format_search_date((string) (($row['start_date'] ?? '') ?: ($row['event_date'] ?? '')), (string) (($row['start_time'] ?? '') ?: ($row['event_time'] ?? '')));
            $location = trim((string) (($row['city'] ?? '') ?: ($row['location'] ?? '')));
            $meta = implode(' · ', array_filter([$date, $location, trim((string) ($row['category'] ?? ''))]));

            $items[] = [
                'type' => 'event',
                'type_label' => $this->tr('entity.event', $this->public_language(), 'Event', 'Event'),
                'icon' => 'ti-calendar-event',
                'title' => $title,
                'excerpt' => $this->search_excerpt([(string) ($row['excerpt'] ?? ''), (string) ($row['description'] ?? ''), (string) (($row['organizer'] ?? '') ?: ($row['organizer_name'] ?? ''))]),
                'meta' => $meta,
                'url' => $this->entity_url('event', $row),
            ];
        }

        return $items;
    }

    private function search_companies(string $query, int $limit): array
    {
        $rows = $this->search_table(
            'companies',
            ['active', 'published'],
            ['id', 'name', 'industry', 'description', 'location_city', 'is_partner', 'is_top_partner'],
            ['name', 'industry', 'description', 'location_city', 'location_country'],
            $query,
            $limit,
            'name ASC, id DESC'
        );

        $items = [];
        foreach ($rows as $row) {
            $title = trim((string) ($row['name'] ?? '')) ?: $this->tr('entity.company', $this->public_language(), 'Firma', 'Company');
            $flags = [];
            if ((int) ($row['is_top_partner'] ?? 0) === 1) {
                $flags[] = $this->tr('partner.top', $this->public_language(), 'Top-Partner', 'Top Partner');
            } elseif ((int) ($row['is_partner'] ?? 0) === 1) {
                $flags[] = $this->tr('partner.label', $this->public_language(), 'Partner', 'Partner');
            }
            $meta = implode(' · ', array_filter([trim((string) ($row['industry'] ?? '')), trim((string) ($row['location_city'] ?? '')), implode(' · ', $flags)]));

            $items[] = [
                'type' => 'company',
                'type_label' => $this->tr('entity.company', $this->public_language(), 'Firma', 'Company'),
                'icon' => 'ti-building-community',
                'title' => $title,
                'excerpt' => $this->search_excerpt([(string) ($row['description'] ?? '')]),
                'meta' => $meta,
                'url' => $this->entity_url('company', $row),
            ];
        }

        return $items;
    }

    private function search_people_table(string $table, string $type, string $label, string $icon, string $query, int $limit): array
    {
        $rows = $this->search_table(
            $table,
            ['published', 'active'],
            ['id', 'first_name', 'last_name', 'display_name', 'slug', 'position', 'topic', 'company', 'location_city', 'location', 'short_bio', 'bio', 'biography', 'target_audience', 'categories', 'tags'],
            ['first_name', 'last_name', 'display_name', 'position', 'topic', 'company', 'location_city', 'location', 'short_bio', 'bio', 'biography', 'target_audience', 'categories', 'tags'],
            $query,
            $limit,
            'last_name ASC, first_name ASC, id DESC'
        );

        $items = [];
        foreach ($rows as $row) {
            $name = trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
            $title = $name !== '' ? $name : $label;
            $meta = implode(' · ', array_filter([
                trim((string) (($row['position'] ?? '') ?: ($row['topic'] ?? ''))),
                $type === 'speaker' ? '' : trim((string) ($row['company'] ?? '')),
                trim((string) (($row['location_city'] ?? '') ?: ($row['location'] ?? ''))),
            ]));

            $items[] = [
                'type' => $type,
                'type_label' => $label,
                'icon' => $icon,
                'title' => $title,
                'excerpt' => $this->search_excerpt([(string) ($row['short_bio'] ?? ''), (string) ($row['bio'] ?? ''), (string) ($row['biography'] ?? ''), (string) ($row['target_audience'] ?? '')]),
                'meta' => $meta,
                'url' => $this->entity_url($type, $row),
            ];
        }

        return $items;
    }

    private function search_table(string $table, array $statuses, array $selectColumns, array $searchColumns, string $query, int $limit, string $orderBy): array
    {
        $resolvedTable = $this->resolve_table_name($table);
        if ($resolvedTable === '') {
            return [];
        }

        $selectColumns = $this->existing_columns($table, $selectColumns);
        $searchColumns = $this->existing_columns($table, $searchColumns);
        if (!in_array('id', $selectColumns, true)) {
            array_unshift($selectColumns, 'id');
        }
        if ($searchColumns === []) {
            return [];
        }

        $params = [];
        $statusWhere = $this->status_filter_sql($table, $statuses, $params);
        $like = '%' . addcslashes($query, "\\%_") . '%';
        $likeParts = [];
        foreach ($searchColumns as $column) {
            $likeParts[] = '`' . $column . '` LIKE ? ESCAPE \'\\\\\'';
            $params[] = $like;
        }

        $orderBy = $this->safe_order_by($table, $orderBy);
        $selectSql = implode(', ', array_map(static fn(string $column): string => '`' . $column . '`', $selectColumns));
        $sql = sprintf(
            'SELECT %s FROM `%s` WHERE %s AND (%s) ORDER BY %s LIMIT %d',
            $selectSql,
            $resolvedTable,
            $statusWhere,
            implode(' OR ', $likeParts),
            $orderBy,
            $this->clamp_int($limit, 1, 20)
        );

        try {
            $stmt = CMS\Database::instance()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            return array_values(array_filter($rows, 'is_array'));
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK search ' . $table . ' failed: ' . $e->getMessage());
            return [];
        }
    }

    private function existing_columns(string $table, array $columns): array
    {
        $existing = [];
        foreach ($columns as $column) {
            $column = trim((string) $column);
            if ($column !== '' && preg_match('/^[a-z0-9_]+$/', $column) === 1 && $this->column_exists($table, $column)) {
                $existing[] = $column;
            }
        }

        return array_values(array_unique($existing));
    }

    private function safe_order_by(string $table, string $orderBy): string
    {
        $parts = array_filter(array_map('trim', explode(',', $orderBy)));
        $safe = [];
        foreach ($parts as $part) {
            if (preg_match('/^([a-z0-9_]+)\s+(ASC|DESC)$/i', $part, $matches) !== 1) {
                continue;
            }

            $column = strtolower($matches[1]);
            if ($this->column_exists($table, $column)) {
                $safe[] = '`' . $column . '` ' . strtoupper($matches[2]);
            }
        }

        return $safe !== [] ? implode(', ', $safe) : '`id` DESC';
    }

    private function search_excerpt(array $values, int $length = 180): string
    {
        foreach ($values as $value) {
            $text = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $value)));
            if ($text === '') {
                continue;
            }

            if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                return mb_strlen($text, 'UTF-8') > $length ? rtrim(mb_substr($text, 0, $length - 1, 'UTF-8')) . '…' : $text;
            }

            return strlen($text) > $length ? rtrim(substr($text, 0, $length - 1)) . '…' : $text;
        }

        return '';
    }

    private function format_search_date(string $date, string $time): string
    {
        $date = trim($date);
        if ($date === '') {
            return '';
        }

        $timestamp = strtotime($date);
        $label = $timestamp !== false ? date('d.m.Y', $timestamp) : $date;
        $time = trim($time);
        if ($time !== '') {
            $label .= ' · ' . substr($time, 0, 5) . ' Uhr';
        }

        return $label;
    }

    private function search_param(array $hubSettings): string
    {
        $param = trim((string) preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($hubSettings['hub_band_search_param'] ?? 'q')));
        return $param !== '' ? $param : 'q';
    }

    private function search_query_from_request(string $searchParam): string
    {
        $raw = $_GET[$searchParam] ?? ($_GET['q'] ?? '');
        if (is_array($raw)) {
            return '';
        }

        $query = trim(strip_tags((string) $raw));
        $query = (string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $query);
        $query = (string) preg_replace('/\s+/', ' ', $query);

        if (function_exists('mb_substr')) {
            return mb_substr($query, 0, 120, 'UTF-8');
        }

        return substr($query, 0, 120);
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
            $personWhere = $table === 'speakers' && $this->column_exists($table, 'first_name') && $this->column_exists($table, 'last_name')
                ? " AND first_name IS NOT NULL AND first_name <> '' AND last_name IS NOT NULL AND last_name <> ''"
                : '';
            $stmt = $db->prepare("SELECT COUNT(*) FROM `{$resolvedTable}` WHERE {$where}{$personWhere}");
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
        $resolvedTable = $this->resolve_table_name($table);
        if ($resolvedTable === '') {
            return 0;
        }

        try {
            $db = CMS\Database::instance();
            $params = [];
            $where = $this->status_filter_sql($table, $statuses, $params);
            $personWhere = $table === 'speakers' && $this->column_exists($table, 'first_name') && $this->column_exists($table, 'last_name')
                ? " AND first_name IS NOT NULL AND first_name <> '' AND last_name IS NOT NULL AND last_name <> ''"
                : '';
            $stmt = $db->prepare("SELECT COUNT(*) FROM `{$resolvedTable}` WHERE {$where}{$personWhere}");
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

    private function shuffle_rows(array $rows): array
    {
        if (count($rows) > 1) {
            shuffle($rows);
        }

        return array_values($rows);
    }

    private function move_random_top_partner_to_front(array $rows): array
    {
        $topPartnerIndexes = [];
        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            if ((int) ($row['is_top_partner'] ?? 0) === 1) {
                $topPartnerIndexes[] = $index;
            }
        }

        if ($topPartnerIndexes === []) {
            return $rows;
        }

        $selectedIndex = $topPartnerIndexes[array_rand($topPartnerIndexes)];
        $selected = $rows[$selectedIndex] ?? null;
        if (!is_array($selected)) {
            return $rows;
        }

        unset($rows[$selectedIndex]);
        array_unshift($rows, $selected);

        return array_values($rows);
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
                'speaker' => '/event-speakers',
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
        if ($type === 'speaker' && !empty($row['slug'])) {
            return $this->base_url() . '/event-speakers/' . rawurlencode((string) $row['slug']);
        }
        if ($type === 'expert' && class_exists('CMS_Experts_Database') && method_exists('CMS_Experts_Database', 'generate_slug')) {
            return $this->base_url() . '/experts/' . CMS_Experts_Database::generate_slug($object);
        }

        return match ($type) {
            'event' => !empty($row['slug']) ? $this->base_url() . '/events/' . rawurlencode((string) $row['slug']) : $this->base_url() . '/events',
            'speaker' => !empty($row['slug']) ? $this->base_url() . '/event-speakers/' . rawurlencode((string) $row['slug']) : $this->base_url() . '/event-speakers',
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

    private function network_search_url(array $settings, ?string $lang = null): string
    {
        $resolvedLang = $lang === 'en' ? 'en' : ($lang === 'de' ? 'de' : $this->public_language());
        return $this->network_search_url_for_lang($settings, $resolvedLang);
    }

    private function network_search_url_for_lang(array $settings, string $lang): string
    {
        return $this->localized_public_path($this->route_slug($settings) . '/search', $lang);
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

        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, $this->columnExistsCache)) {
            return $this->columnExistsCache[$cacheKey];
        }

        try {
            $db = CMS\Database::instance();
            $resolvedTable = $this->resolve_table_name($table);
            if ($resolvedTable === '') {
                return $this->columnExistsCache[$cacheKey] = false;
            }

            $stmt = $db->prepare(
                'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
            );
            $stmt->execute([$resolvedTable, $column]);
            return $this->columnExistsCache[$cacheKey] = (bool) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return $this->columnExistsCache[$cacheKey] = false;
        }
    }

    private function resolve_table_name(string $table): string
    {
        if (preg_match('/^[a-z0-9_]+$/', $table) !== 1) {
            return '';
        }

        if (array_key_exists($table, $this->resolvedTableCache)) {
            return $this->resolvedTableCache[$table];
        }

        try {
            $db = CMS\Database::instance();
            $prefix = $db->prefix();
            $aliases = [
                'events' => ['365net_events', 'events', 'event_events'],
                'speakers' => ['365net_event_speakers', 'event_speakers', 'speakers'],
                'companies' => ['365net_excomp_companies', 'companies', 'company'],
                'experts' => ['365net_excomp_experts', 'experts', 'expert'],
            ];
            $baseCandidates = $aliases[$table] ?? [$table];
            $candidates = [];
            foreach ($baseCandidates as $baseCandidate) {
                $candidates[] = $prefix . $baseCandidate;
                $candidates[] = $baseCandidate;
            }
            $candidates = array_values(array_unique($candidates));
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
                    return $this->resolvedTableCache[$table] = $found;
                }
            }
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK resolve table ' . $table . ' failed: ' . $e->getMessage());
        }

        return $this->resolvedTableCache[$table] = '';
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
            if (function_exists('cms_plugin_active')) {
                if ((bool) cms_plugin_active($slug)) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // Fallback auf PluginManager unten.
        }

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
        if ((string) ($settings['landing_enabled'] ?? '1') !== '1') {
            return $this->landingPathRequestCache = false;
        }

        $path = '/' . trim((string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/'), '/');
        $route = '/' . $this->route_slug($settings);
        $searchRoute = $route . '/search';

        return $this->landingPathRequestCache = ($path === $route || $path === $searchRoute || ($path === '/' && $this->is_domain_landing_request()));
    }

    private function is_public_page_request(): bool
    {
        return $this->renderingPublicPage || $this->is_landing_path_request();
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

    private function load_public_i18n_contract(): void
    {
        if (function_exists('cms_plugin_public_language') && function_exists('cms_plugin_public_i18n_value') && function_exists('cms_plugin_public_localized_path')) {
            return;
        }

        $path = dirname(CMS_365NETWORK_PLUGIN_DIR) . DIRECTORY_SEPARATOR . self::SHARED_PUBLIC_I18N_CONTRACT;
        if (is_file($path)) {
            require_once $path;
        }
    }

    private function public_language(): string
    {
        if ($this->requestLanguageCache !== null) {
            return $this->requestLanguageCache;
        }

        $this->load_public_i18n_contract();
        if (function_exists('cms_plugin_public_language')) {
            $lang = (string) cms_plugin_public_language();
            if ($lang === 'en') {
                return $this->requestLanguageCache = 'en';
            }
        }

        return $this->requestLanguageCache = 'de';
    }

    private function localized_public_path(string $path, string $lang): string
    {
        $this->load_public_i18n_contract();
        if (function_exists('cms_plugin_public_localized_path')) {
            return (string) cms_plugin_public_localized_path($path, $lang === 'en' ? 'en' : 'de');
        }

        $path = trim($path, '/');
        if ($lang === 'en') {
            return '/en' . ($path !== '' ? '/' . $path : '');
        }

        return '/' . $path;
    }

    /**
     * @return array<string,string>
     */
    private function public_i18n_values(): array
    {
        return [
            'search.page_title' => '365NETWORK Suche',
            'search.page_title_en' => '365NETWORK Search',
            'search.keyboard_help' => 'In den Ergebnissen mit Pfeil hoch/runter navigieren. Pos1 und Ende springen zum ersten bzw. letzten Ergebnis.',
            'search.keyboard_help_en' => 'Navigate results with Arrow Up/Down. Home and End jump to first/last result.',
            'search.live_prefix' => 'Treffer',
            'search.live_prefix_en' => 'Result',
            'search.live_of' => 'von',
            'search.live_of_en' => 'of',
            'search.overline' => '365NETWORK Suche',
            'search.overline_en' => '365NETWORK Search',
            'search.title' => 'Events, Speaker, Firmen und Experten finden',
            'search.title_en' => 'Find events, speakers, companies and experts',
            'search.intro' => 'Diese Suche ist vom globalen 365CMS getrennt und durchsucht ausschließlich die vier Netzwerk-Bereiche.',
            'search.intro_en' => 'This search is separate from global 365CMS search and only scans the four network areas.',
            'search.form_aria' => '365NETWORK durchsuchen',
            'search.form_aria_en' => 'Search 365NETWORK',
            'search.label' => 'Suchbegriff',
            'search.label_en' => 'Search term',
            'search.placeholder' => 'z. B. Azure, Copilot, Workshop',
            'search.placeholder_en' => 'e.g. Azure, Copilot, workshop',
            'search.submit' => 'Suchen',
            'search.submit_en' => 'Search',
            'search.empty.title' => 'Suchbegriff eingeben',
            'search.empty.title_en' => 'Enter a search term',
            'search.empty.text' => 'Starte mit mindestens zwei Zeichen. Angezeigt werden nur Treffer aus Events, Speakern, Firmen und Experten.',
            'search.empty.text_en' => 'Start with at least two characters. Results are limited to events, speakers, companies and experts.',
            'search.too_short.title' => 'Suchbegriff zu kurz',
            'search.too_short.title_en' => 'Search term too short',
            'search.too_short.text' => 'Bitte gib mindestens zwei Zeichen ein, damit die Netzwerk-Suche starten kann.',
            'search.too_short.text_en' => 'Please enter at least two characters to start searching the network.',
            'search.summary.aria' => 'Suchzusammenfassung',
            'search.summary.aria_en' => 'Search summary',
            'search.summary.for' => 'Treffer für',
            'search.summary.for_en' => 'results for',
            'search.no_results.title' => 'Keine Netzwerk-Treffer gefunden',
            'search.no_results.title_en' => 'No network results found',
            'search.no_results.text' => 'Versuche einen anderen Begriff oder suche allgemeiner, zum Beispiel nach Thema, Stadt, Firmenname oder Rolle.',
            'search.no_results.text_en' => 'Try another term or search more broadly, e.g. by topic, city, company name or role.',
            'search.results.aria' => '365NETWORK Suchergebnisse',
            'search.results.aria_en' => '365NETWORK search results',
            'entity.event' => 'Event',
            'entity.event_en' => 'Event',
            'entity.events' => 'Events',
            'entity.events_en' => 'Events',
            'entity.speaker' => 'Speaker',
            'entity.speaker_en' => 'Speaker',
            'entity.speakers' => 'Speaker',
            'entity.speakers_en' => 'Speakers',
            'entity.company' => 'Firma',
            'entity.company_en' => 'Company',
            'entity.companies' => 'Firmen',
            'entity.companies_en' => 'Companies',
            'entity.expert' => 'Experte',
            'entity.expert_en' => 'Expert',
            'entity.experts' => 'Experten',
            'entity.experts_en' => 'Experts',
            'partner.label' => 'Partner',
            'partner.label_en' => 'Partner',
            'partner.top' => 'Top-Partner',
            'partner.top_en' => 'Top Partner',
            'area.unavailable' => 'Bald verfügbar',
            'area.unavailable_en' => 'Coming soon',
            'spotlight.prev' => 'Zurück',
            'spotlight.prev_en' => 'Previous',
            'spotlight.next' => 'Weiter',
            'spotlight.next_en' => 'Next',
            'spotlight.cta.event' => 'Zum Event',
            'spotlight.cta.event_en' => 'View event',
            'spotlight.cta.profile' => 'Profil ansehen',
            'spotlight.cta.profile_en' => 'View profile',
        ];
    }

    private function tr(string $key, string $lang, string $fallbackDe, string $fallbackEn): string
    {
        $fallback = $lang === 'en' ? $fallbackEn : $fallbackDe;
        $values = $this->public_i18n_values();
        $this->load_public_i18n_contract();
        if (function_exists('cms_plugin_public_i18n_value')) {
            return (string) cms_plugin_public_i18n_value($values, $key, $lang, $fallback);
        }

        return $fallback;
    }

    public function output_event_structured_data(): void
    {
        if (!$this->is_public_page_request() || !$this->is_landing_path_request()) {
            return;
        }

        $hubSettings = CMS_365NETWORK_Database::instance()->get_hub_settings();
        if ((string) ($hubSettings['hub_event_schema_enabled'] ?? '1') !== '1') {
            return;
        }

        $events = $this->landingEventsForSchema !== [] ? $this->landingEventsForSchema : $this->fetch_upcoming_events(max(1, (int) ($hubSettings['hub_next_events_limit'] ?? 3)));
        if ($events === []) {
            return;
        }

        $structuredEvents = [];
        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }

            $name = trim((string) ($event['title'] ?? ''));
            $startDate = $this->schema_datetime((string) ($event['event_date'] ?? ''), (string) ($event['event_time'] ?? ''));
            if ($name === '' || $startDate === '') {
                continue;
            }

            $locationName = trim((string) (($event['location'] ?? '') ?: ($event['city'] ?? '')));
            $city = trim((string) ($event['city'] ?? ''));

            $entry = [
                '@type' => 'Event',
                'name' => $name,
                'startDate' => $startDate,
                'eventStatus' => 'https://schema.org/EventScheduled',
                'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                'url' => $this->safe_url((string) ($event['url'] ?? '#')),
            ];

            if (trim((string) ($event['end_date'] ?? '')) !== '') {
                $entry['endDate'] = $this->schema_datetime((string) ($event['end_date'] ?? ''), (string) ($event['event_time'] ?? ''));
            }

            if ($locationName !== '' || $city !== '') {
                $entry['location'] = [
                    '@type' => 'Place',
                    'name' => $locationName !== '' ? $locationName : $city,
                    'address' => [
                        '@type' => 'PostalAddress',
                        'addressLocality' => $city,
                    ],
                ];
            }

            $image = $this->safe_url((string) ($event['image_url'] ?? '#'));
            if ($image !== '#') {
                $entry['image'] = [$image];
            }

            $structuredEvents[] = $entry;
        }

        if ($structuredEvents === []) {
            return;
        }

        $json = json_encode([
            '@context' => 'https://schema.org',
            '@graph' => $structuredEvents,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (!is_string($json) || $json === '') {
            return;
        }

        echo '<script type="application/ld+json" id="cms-365network-event-schema">' . $json . '</script>' . "\n";
    }

    private function schema_datetime(string $date, string $time): string
    {
        $date = trim($date);
        if ($date === '') {
            return '';
        }

        $time = trim($time);
        if ($time === '') {
            $time = '09:00:00';
        } elseif (preg_match('/^\d{2}:\d{2}$/', $time) === 1) {
            $time .= ':00';
        }

        $timestamp = strtotime($date . ' ' . $time);
        if ($timestamp === false) {
            return '';
        }

        return gmdate('c', $timestamp);
    }
}
