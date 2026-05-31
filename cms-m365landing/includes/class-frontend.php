<?php
/**
 * CMS M365 Landing – Public frontend.
 *
 * @package CMS_M365Landing
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365Landing_Frontend
{
    private static ?self $instance = null;
    private ?string $requestPathCache = null;
    private ?bool $domainLandingRequestCache = null;
    /** @var array<string,mixed> */
    private array $currentHeadContext = [];

    public static function instance(?\CMS\Router $router = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        self::$instance->register_routes($router);

        return self::$instance;
    }

    private function __construct()
    {
        if (class_exists('CMS\\Hooks')) {
            \CMS\Hooks::addFilter('page_title', [$this, 'filter_page_title'], 20);
            \CMS\Hooks::addFilter('phinit_head_meta_data', [$this, 'filter_phinit_head_meta_data'], 20);
            \CMS\Hooks::addFilter('body_class', [$this, 'filter_body_class'], 20);
            \CMS\Hooks::addAction('head', [$this, 'output_resource_hints'], 18);
            \CMS\Hooks::addAction('head', [$this, 'enqueue_styles'], 20);
            \CMS\Hooks::addAction('head', [$this, 'output_structured_data'], 27);
            \CMS\Hooks::addAction('head', [$this, 'output_design_tokens'], 30);
        }
    }

    private function register_routes(?\CMS\Router $router = null): void
    {
        if (!class_exists('CMS\\Router')) {
            return;
        }

        $router = $router instanceof \CMS\Router ? $router : \CMS\Router::instance();
        $slug = $this->route_slug();
        $routes = [
            '/' . $slug,
            '/en/' . $slug,
        ];
        if ($this->is_domain_landing_request()) {
            $routes[] = '/';
            $routes[] = '/en';
        }

        foreach (array_values(array_unique($routes)) as $route) {
            $router->addRoute('GET', $route, function (): void {
                $this->render_landing();
            });
        }
    }

    public function enqueue_styles(): void
    {
        if (!$this->is_request()) {
            return;
        }

        foreach (array_merge($this->phinit_card_style_assets(), $this->m365_style_assets()) as $asset) {
            if (!file_exists($asset['path'])) {
                continue;
            }

            echo '<link rel="stylesheet" href="'
                . htmlspecialchars($asset['url'], ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($asset['path']) . '">' . "\n";
        }

        $css = CMS_M365LANDING_PLUGIN_DIR . 'assets/css/style.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_M365LANDING_PLUGIN_URL . 'assets/css/style.css', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    public function output_resource_hints(): void
    {
        if (!$this->is_request()) {
            return;
        }

        $settings = $this->repo()->settings();
        if ((string) ($settings['show_hero'] ?? '1') !== '1') {
            return;
        }

        $heroImageUrl = CMS_M365Landing_Repository::main_site_media_url((string) ($settings['hero_image_url'] ?? ''));
        if ($heroImageUrl === '' || str_starts_with($heroImageUrl, 'data:')) {
            return;
        }

        echo '<link rel="preload" as="image" fetchpriority="high" href="'
            . htmlspecialchars($heroImageUrl, ENT_QUOTES, 'UTF-8')
            . '">' . "\n";
    }

    public function filter_page_title(mixed $title): string
    {
        if (!$this->is_request()) {
            return (string) $title;
        }

        $headContext = $this->head_context();
        $seoTitle = trim((string) ($headContext['title'] ?? ''));

        return $seoTitle !== '' ? $seoTitle : (string) $title;
    }

    /** @param mixed $metaData @return array<string,mixed> */
    public function filter_phinit_head_meta_data(mixed $metaData, mixed $context = null): array
    {
        $metaData = is_array($metaData) ? $metaData : [];
        if (!$this->is_request()) {
            return $metaData;
        }

        $headContext = $this->head_context();
        $title = trim((string) ($headContext['title'] ?? ''));
        $description = trim((string) ($headContext['description'] ?? ''));
        $canonicalUrl = trim((string) ($headContext['canonical_url'] ?? ''));
        $imageUrl = trim((string) ($headContext['image_url'] ?? ''));

        if ($description !== '') {
            $metaData['description'] = $description;
            $metaData['og_description'] = $description;
            $metaData['twitter_description'] = $description;
        }
        if ($title !== '') {
            $metaData['og_title'] = $title;
            $metaData['twitter_title'] = $title;
        }
        if ($canonicalUrl !== '') {
            $metaData['canonical_url'] = $canonicalUrl;
            $metaData['og_url'] = $canonicalUrl;
            $metaData['canonical_self'] = true;
        }
        if ($imageUrl !== '') {
            $metaData['og_image'] = $imageUrl;
            $metaData['twitter_image'] = $imageUrl;
            $metaData['twitter_card'] = 'summary_large_image';
        }

        $metaData['robots'] = 'index,follow';
        $metaData['og_type'] = 'website';

        return $metaData;
    }

    public function output_structured_data(): void
    {
        if (!$this->is_request()) {
            return;
        }

        $headContext = $this->head_context();
        $canonicalUrl = trim((string) ($headContext['canonical_url'] ?? ''));
        $title = trim((string) ($headContext['title'] ?? ''));
        $description = trim((string) ($headContext['description'] ?? ''));
        if ($canonicalUrl === '' || $title === '') {
            return;
        }

        $siteUrl = rtrim((string) (defined('SITE_URL') ? SITE_URL : ''), '/');
        $siteName = (string) (defined('SITE_NAME') ? SITE_NAME : '365CMS');
        $locale = function_exists('phinit_get_current_locale') ? (string) phinit_get_current_locale() : 'de';
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            '@id' => $canonicalUrl . '#webpage',
            'url' => $canonicalUrl,
            'name' => $title,
            'description' => $description,
            'inLanguage' => $locale,
            'isPartOf' => [
                '@type' => 'WebSite',
                '@id' => $siteUrl . '#website',
                'url' => $siteUrl . '/',
                'name' => $siteName,
            ],
        ];

        $imageUrl = trim((string) ($headContext['image_url'] ?? ''));
        if ($imageUrl !== '') {
            $schema['primaryImageOfPage'] = [
                '@type' => 'ImageObject',
                'url' => $imageUrl,
            ];
        }

        $items = is_array($headContext['latest_posts_schema'] ?? null) ? $headContext['latest_posts_schema'] : [];
        if ($items !== []) {
            $schema['mainEntity'] = [
                '@type' => 'ItemList',
                'itemListElement' => $items,
            ];
        }

        $json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        if (is_string($json) && $json !== '') {
            echo '<script type="application/ld+json" id="cms-m365landing-schema">' . $json . '</script>' . "\n";
        }
    }

    public function output_design_tokens(): void
    {
        if (!$this->is_request()) {
            return;
        }

        $settings = $this->repo()->settings();
        $primary = CMS_M365Landing_Repository::color((string) ($settings['design_primary_color'] ?? ''), '#2563eb');
        $accent = CMS_M365Landing_Repository::color((string) ($settings['design_accent_color'] ?? ''), '#0f766e');
        $background = CMS_M365Landing_Repository::color((string) ($settings['design_background_color'] ?? ''), '#edf1f6');
        $surface = CMS_M365Landing_Repository::color((string) ($settings['design_surface_color'] ?? ''), '#ffffff');
        $surfaceAlt = CMS_M365Landing_Repository::color((string) ($settings['design_surface_alt_color'] ?? ''), '#f8fafc');
        $textColor = CMS_M365Landing_Repository::color((string) ($settings['design_text_color'] ?? ''), '#1e293b');
        $mutedColor = CMS_M365Landing_Repository::color((string) ($settings['design_muted_color'] ?? ''), '#64748b');
        $borderColor = CMS_M365Landing_Repository::color((string) ($settings['design_border_color'] ?? ''), '#e2e8f0');
        $number = static function (string $key, int $default, int $min, int $max) use ($settings): int {
            $rawValue = trim((string) ($settings[$key] ?? ''));
            $value = $rawValue !== '' ? (int) $rawValue : $default;

            return max($min, min($max, $value));
        };
        $radius = $number('design_border_radius', 10, 0, 32);
        $maxWidth = $number('layout_max_width', 1180, 720, 1800);
        $paddingX = $number('layout_padding_x', 0, 0, 80);
        $paddingTop = $number('layout_padding_top', 25, 0, 120);
        $paddingBottom = $number('layout_padding_bottom', 64, 0, 160);
        $heroImageHeight = $number('hero_image_height', 150, 80, 320);
        $iconSize = $number('card_icon_size', 42, 24, 80);
        $imageHeight = $number('card_image_height', 205, 90, 420);
        $imageWidth = $number('card_image_width', 120, 72, 220);

        echo '<style id="cms-m365landing-design">' . "\n";
        echo ':root {' . "\n";
        echo '    --m365landing-bg: ' . htmlspecialchars($background, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '}' . "\n";
        echo ':root, body.m365tools-theme-embed, body.m365calculator-theme-embed {' . "\n";
        echo '    --m365tools-card-radius: ' . (int) $radius . 'px;' . "\n";
        echo '    --m365tools-ui-radius: ' . (int) $radius . 'px;' . "\n";
        echo '    --m365tools-primary: ' . htmlspecialchars($primary, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-accent: ' . htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-bg: var(--m365landing-bg);' . "\n";
        echo '    --m365tools-surface: ' . htmlspecialchars($surface, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-surface-alt: ' . htmlspecialchars($surfaceAlt, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-header-bg: ' . htmlspecialchars($surfaceAlt, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-header-text: ' . htmlspecialchars($textColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-header-muted: ' . htmlspecialchars($mutedColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-header-border: ' . htmlspecialchars($borderColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-button-primary-bg: ' . htmlspecialchars($primary, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-button-primary-text: #ffffff;' . "\n";
        echo '    --m365tools-button-secondary-bg: ' . htmlspecialchars($surface, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-button-secondary-text: ' . htmlspecialchars($textColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-text: ' . htmlspecialchars($textColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-muted: ' . htmlspecialchars($mutedColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-border: ' . htmlspecialchars($borderColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365landing-page-max-width: ' . (int) $maxWidth . 'px;' . "\n";
        echo '    --m365landing-padding-x: ' . (int) $paddingX . 'px;' . "\n";
        echo '    --m365landing-padding-top: ' . (int) $paddingTop . 'px;' . "\n";
        echo '    --m365landing-padding-bottom: ' . (int) $paddingBottom . 'px;' . "\n";
        echo '    --m365landing-hero-image-height: ' . (int) $heroImageHeight . 'px;' . "\n";
        echo '    --m365landing-icon-size: ' . (int) $iconSize . 'px;' . "\n";
        echo '    --m365landing-image-height: ' . (int) $imageHeight . 'px;' . "\n";
        echo '    --m365landing-image-width: ' . (int) $imageWidth . 'px;' . "\n";
        echo '}' . "\n";
        echo 'body.m365tools-module-m365landing, body.m365tools-module-m365landing #page.site, body.m365tools-module-m365landing #content.site-content, body.m365tools-module-m365landing .site-content {' . "\n";
        echo '    --m365tools-bg: var(--m365landing-bg) !important;' . "\n";
        echo '    background-color: var(--m365landing-bg) !important;' . "\n";
        echo '}' . "\n";
        echo '</style>' . "\n";
    }

    public function filter_body_class(mixed $bodyClass): string
    {
        $classes = preg_split('/\s+/', trim((string) $bodyClass), -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($classes)) {
            $classes = [];
        }

        if (!$this->is_request()) {
            return implode(' ', $classes);
        }

        $classes[] = 'm365tools-theme-embed';
        $classes[] = 'm365calculator-theme-embed';
        $classes[] = 'm365tools-module-m365landing';

        return implode(' ', array_values(array_unique($classes)));
    }

    private function render_landing(): void
    {
        $repo = $this->repo();
        $settings = $repo->settings();
        $cardsBySection = $repo->cards_by_section(true);
        $isDomainLandingRequest = $this->is_domain_landing_request();
        $publicLang = $this->public_language();
        $latestPosts = [];
        $serviceHealthItems = [];
        $serviceHealthError = '';
        $messageCenterItems = [];
        $messageCenterError = '';

        if ($this->should_render_posts_section($settings, $isDomainLandingRequest)) {
            $postsLimit = (int) ($settings['posts_section_limit'] ?? 6);
            $postsLimit = in_array($postsLimit, [6, 9], true) ? $postsLimit : 6;
            $postsMode = (string) ($settings['posts_section_mode'] ?? 'category');
            if ($postsMode === 'all' && method_exists($repo, 'latest_posts')) {
                $latestPosts = $repo->latest_posts($postsLimit);
            } elseif (method_exists($repo, 'latest_posts_by_category')) {
                $latestPosts = $repo->latest_posts_by_category((int) ($settings['posts_section_category_id'] ?? 0), $postsLimit);
            }
        }

        if ((string) ($settings['show_service_health_panel'] ?? '0') === '1'
            || (string) ($settings['show_message_center_panel'] ?? '0') === '1'
        ) {
            $graphPayload = $this->fetch_graph_panels_data($settings);
            $serviceHealthItems = is_array($graphPayload['service_health_items'] ?? null) ? $graphPayload['service_health_items'] : [];
            $serviceHealthError = trim((string) ($graphPayload['service_health_error'] ?? ''));
            $messageCenterItems = is_array($graphPayload['message_center_items'] ?? null) ? $graphPayload['message_center_items'] : [];
            $messageCenterError = trim((string) ($graphPayload['message_center_error'] ?? ''));
        }

        $title = $this->setting($settings, 'seo_title', $this->setting($settings, 'page_title', 'Microsoft 365 Hub'));
        $description = $this->setting($settings, 'seo_description', $this->setting($settings, 'page_intro', 'Zentrale Übersicht für Microsoft 365 Inhalte und Tools.'));
        $this->currentHeadContext = $this->build_head_context($settings, $latestPosts, $title, $description);

        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getHeader(['title' => $title, 'description' => $description]);
        }

        include CMS_M365LANDING_PLUGIN_DIR . 'templates/landing.php';

        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getFooter();
        }

        exit;
    }

    /** @param array<string,string> $settings */
    private function setting(array $settings, string $key, string $default = ''): string
    {
        $value = trim(strip_tags((string) ($settings[$key] ?? '')));

        return $value !== '' ? $value : $default;
    }

    private function route_slug(): string
    {
        try {
            $settings = $this->repo()->settings();
            $slug = CMS_M365Landing_Repository::slug((string) ($settings['route_slug'] ?? 'm365'));
            return $slug !== '' ? $slug : 'm365';
        } catch (\Throwable $e) {
            self::log_exception('route_slug_fallback', $e);
            return 'm365';
        }
    }

    private function is_request(): bool
    {
        if ($this->is_admin_request()) {
            return false;
        }

        $path = $this->path_without_language($this->request_path());
        $slug = $this->route_slug();
        $lastSegment = $path;
        if (str_contains($path, '/')) {
            $parts = explode('/', $path);
            $lastSegment = (string) end($parts);
        }

        return $path === $slug || $lastSegment === $slug || ($path === '' && $this->is_domain_landing_request());
    }

    private function is_admin_request(): bool
    {
        $path = $this->request_path();
        if ($path === 'admin' || str_starts_with($path, 'admin/')) {
            return true;
        }

        $uri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));
        return str_contains($uri, '/admin/');
    }

    /** @param array<string,string> $settings */
    private function should_render_posts_section(array $settings, bool $isDomainLandingRequest): bool
    {
        if ((string) ($settings['show_posts_section'] ?? '0') !== '1') {
            return false;
        }

        $postsMode = (string) ($settings['posts_section_mode'] ?? 'category');
        if ($postsMode !== 'all' && (int) ($settings['posts_section_category_id'] ?? 0) <= 0) {
            return false;
        }

        if ((string) ($settings['posts_section_domain_only'] ?? '1') === '1' && !$isDomainLandingRequest) {
            return false;
        }

        return true;
    }

    private function is_domain_landing_request(): bool
    {
        if ($this->domainLandingRequestCache !== null) {
            return $this->domainLandingRequestCache;
        }

        $settings = $this->repo()->settings();
        $host = CMS_M365Landing_Repository::normalize_host((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $siteUrl = defined('SITE_URL') ? (string) SITE_URL : '';
        $mainHost = CMS_M365Landing_Repository::normalize_host((string) (parse_url($siteUrl, PHP_URL_HOST) ?: ''));
        if ($host === '' || $host === $mainHost) {
            $this->domainLandingRequestCache = false;

            return $this->domainLandingRequestCache;
        }

        $domains = CMS_M365Landing_Repository::normalize_domain_list((string) ($settings['landing_domains'] ?? ''));
        $this->domainLandingRequestCache = in_array($host, $domains, true);

        return $this->domainLandingRequestCache;
    }

    private function request_path(): string
    {
        if ($this->requestPathCache !== null) {
            return $this->requestPathCache;
        }

        if (function_exists('cms_plugin_public_request_path')) {
            $this->requestPathCache = cms_plugin_public_request_path();
            return $this->requestPathCache;
        }

        $path = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');
        $path = (string) preg_replace('#/+#', '/', $path);
        $this->requestPathCache = strtolower($path);

        return $this->requestPathCache;
    }

    private function repo(): CMS_M365Landing_Repository
    {
        if (class_exists('CMS_M365Landing_Installer')) {
            CMS_M365Landing_Installer::maybe_install();
        }

        return CMS_M365Landing_Repository::instance();
    }

    /** @param array<string,string>|null $settings @param array<int,array<string,mixed>> $latestPosts @return array<string,mixed> */
    private function head_context(?array $settings = null, array $latestPosts = []): array
    {
        if ($this->currentHeadContext !== [] && $settings === null) {
            return $this->currentHeadContext;
        }

        $settings ??= $this->repo()->settings();
        $title = $this->setting($settings, 'seo_title', $this->setting($settings, 'page_title', 'Microsoft 365 Hub'));
        $description = $this->setting($settings, 'seo_description', $this->setting($settings, 'page_intro', 'Zentrale Übersicht für Microsoft 365 Inhalte und Tools.'));

        return $this->build_head_context($settings, $latestPosts, $title, $description);
    }

    /** @param array<string,string> $settings @param array<int,array<string,mixed>> $latestPosts @return array<string,mixed> */
    private function build_head_context(array $settings, array $latestPosts, string $title, string $description): array
    {
        $imageUrl = CMS_M365Landing_Repository::main_site_media_url((string) ($settings['hero_image_url'] ?? ''));

        return [
            'title' => $title,
            'description' => $description,
            'canonical_url' => $this->canonical_url(),
            'image_url' => $imageUrl,
            'latest_posts_schema' => $this->latest_posts_schema($latestPosts),
        ];
    }

    private function canonical_url(): string
    {
        $base = rtrim((string) (defined('SITE_URL') ? SITE_URL : ''), '/');
        if ($base === '') {
            return '';
        }
        $slug = rawurlencode($this->route_slug());
        if ($this->public_language() === 'en') {
            return $base . '/en/' . $slug;
        }

        return $base . '/' . $slug;
    }

    private function public_language(): string
    {
        $path = $this->request_path();
        if (function_exists('cms_plugin_public_language')) {
            return cms_plugin_public_language($path);
        }

        return $path === 'en' || str_starts_with($path, 'en/') ? 'en' : 'de';
    }

    private function path_without_language(string $normalizedPath): string
    {
        if (function_exists('cms_plugin_public_path_without_lang')) {
            return cms_plugin_public_path_without_lang($normalizedPath);
        }

        if ($normalizedPath === 'en') {
            return '';
        }
        if (str_starts_with($normalizedPath, 'en/')) {
            return substr($normalizedPath, 3) ?: '';
        }

        return $normalizedPath;
    }

    /** @param array<string,string> $settings @return array<string,mixed> */
    private function fetch_graph_panels_data(array $settings): array
    {
        $showServiceHealth = (string) ($settings['show_service_health_panel'] ?? '0') === '1';
        $showMessageCenter = (string) ($settings['show_message_center_panel'] ?? '0') === '1';
        if (!$showServiceHealth && !$showMessageCenter) {
            return [];
        }

        $tenantId = trim((string) ($settings['graph_tenant_id'] ?? ''));
        $clientId = trim((string) ($settings['graph_client_id'] ?? ''));
        $clientSecret = trim((string) ($settings['graph_client_secret'] ?? ''));
        if ($tenantId === '' || $clientId === '' || $clientSecret === '') {
            $missingConfig = 'missing-credentials';
            return [
                'service_health_items' => [],
                'service_health_error' => $showServiceHealth ? $missingConfig : '',
                'message_center_items' => [],
                'message_center_error' => $showMessageCenter ? $missingConfig : '',
            ];
        }

        $token = $this->graph_access_token($tenantId, $clientId, $clientSecret);
        if ($token === '') {
            $authFailed = 'token-unavailable';
            return [
                'service_health_items' => [],
                'service_health_error' => $showServiceHealth ? $authFailed : '',
                'message_center_items' => [],
                'message_center_error' => $showMessageCenter ? $authFailed : '',
            ];
        }

        $result = [
            'service_health_items' => [],
            'service_health_error' => '',
            'message_center_items' => [],
            'message_center_error' => '',
        ];

        if ($showServiceHealth) {
            $maxItems = max(1, min(12, (int) ($settings['service_health_max_items'] ?? 5)));
            $issues = $this->graph_collection(
                'https://graph.microsoft.com/v1.0/admin/serviceAnnouncement/issues?$top=' . $maxItems,
                $token
            );
            if (!is_array($issues)) {
                $result['service_health_error'] = 'load-failed';
            } else {
                foreach ($issues as $issue) {
                    if (!is_array($issue)) {
                        continue;
                    }
                    $result['service_health_items'][] = [
                        'id' => trim((string) ($issue['id'] ?? '')),
                        'title' => trim((string) ($issue['title'] ?? '')),
                        'service' => trim((string) ($issue['service'] ?? '')),
                        'status' => trim((string) ($issue['status'] ?? '')),
                        'classification' => trim((string) ($issue['classification'] ?? '')),
                        'started_at' => trim((string) ($issue['startDateTime'] ?? '')),
                    ];
                }
                $result['service_health_items'] = array_slice($result['service_health_items'], 0, $maxItems);
            }
        }

        if ($showMessageCenter) {
            $maxItems = max(1, min(12, (int) ($settings['message_center_max_items'] ?? 5)));
            $messages = $this->graph_collection(
                'https://graph.microsoft.com/v1.0/admin/serviceAnnouncement/messages?$top=' . $maxItems,
                $token
            );
            if (!is_array($messages)) {
                $result['message_center_error'] = 'load-failed';
            } else {
                $filter = $this->normalize_services_filter((string) ($settings['message_center_service_filter'] ?? ''));
                foreach ($messages as $message) {
                    if (!is_array($message)) {
                        continue;
                    }
                    $services = [];
                    foreach ((array) ($message['services'] ?? []) as $service) {
                        $service = trim((string) $service);
                        if ($service !== '') {
                            $services[] = $service;
                        }
                    }

                    if ($filter !== [] && !$this->services_match_filter($services, $filter)) {
                        continue;
                    }

                    $result['message_center_items'][] = [
                        'id' => trim((string) ($message['id'] ?? '')),
                        'title' => trim((string) ($message['title'] ?? '')),
                        'category' => trim((string) ($message['category'] ?? '')),
                        'services' => $services,
                        'last_modified' => trim((string) ($message['lastModifiedDateTime'] ?? '')),
                    ];
                    if (count($result['message_center_items']) >= $maxItems) {
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /** @return array<int,array<string,mixed>>|null */
    private function graph_collection(string $url, string $token): ?array
    {
        $payload = $this->http_request_json($url, 'GET', [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ]);
        if (!is_array($payload) || !is_array($payload['value'] ?? null)) {
            return null;
        }

        return $payload['value'];
    }

    private function graph_access_token(string $tenantId, string $clientId, string $clientSecret): string
    {
        $endpoint = 'https://login.microsoftonline.com/' . rawurlencode($tenantId) . '/oauth2/v2.0/token';
        $body = http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ], '', '&', PHP_QUERY_RFC3986);
        $payload = $this->http_request_json($endpoint, 'POST', [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
        ], $body);

        return is_array($payload) ? trim((string) ($payload['access_token'] ?? '')) : '';
    }

    /** @return array<string,mixed>|null */
    private function http_request_json(string $url, string $method = 'GET', array $headers = [], ?string $body = null): ?array
    {
        $method = strtoupper($method);
        try {
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                if ($ch === false) {
                    return null;
                }
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
                curl_setopt($ch, CURLOPT_TIMEOUT, 8);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                if ($headers !== []) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                }
                if ($body !== null && $body !== '') {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                }
                $response = curl_exec($ch);
                $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if (!is_string($response) || $statusCode < 200 || $statusCode >= 300) {
                    return null;
                }

                $decoded = json_decode($response, true);
                return is_array($decoded) ? $decoded : null;
            }

            $requestHeaders = $headers;
            if ($body !== null && $body !== '' && !$this->headers_contain_content_type($requestHeaders)) {
                $requestHeaders[] = 'Content-Type: application/x-www-form-urlencoded';
            }
            $context = stream_context_create([
                'http' => [
                    'method' => $method,
                    'header' => implode("\r\n", $requestHeaders),
                    'content' => $body ?? '',
                    'timeout' => 8,
                    'ignore_errors' => true,
                ],
            ]);
            $response = @file_get_contents($url, false, $context);
            if (!is_string($response) || $response === '') {
                return null;
            }

            $statusLine = '';
            $responseHeaders = $http_response_header ?? [];
            if (is_array($responseHeaders) && isset($responseHeaders[0]) && is_string($responseHeaders[0])) {
                $statusLine = $responseHeaders[0];
            }
            if ($statusLine === '' || preg_match('#\s2\d\d\s#', $statusLine) !== 1) {
                return null;
            }

            $decoded = json_decode($response, true);
            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            self::log_exception('http_request_json_failed', $e);
            return null;
        }
    }

    /** @param array<int,string> $headers */
    private function headers_contain_content_type(array $headers): bool
    {
        foreach ($headers as $header) {
            if (stripos((string) $header, 'content-type:') === 0) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int,string> */
    private function normalize_services_filter(string $value): array
    {
        $parts = preg_split('/[\s,;|]+/', strtolower(trim($value))) ?: [];
        $services = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '' && !in_array($part, $services, true)) {
                $services[] = $part;
            }
        }

        return $services;
    }

    /** @param array<int,string> $services @param array<int,string> $filter */
    private function services_match_filter(array $services, array $filter): bool
    {
        if ($filter === []) {
            return true;
        }

        $normalizedServices = [];
        foreach ($services as $service) {
            $normalizedServices[] = strtolower(trim($service));
        }

        foreach ($normalizedServices as $service) {
            foreach ($filter as $needle) {
                if ($needle !== '' && str_contains($service, $needle)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @param array<int,array<string,mixed>> $posts @return array<int,array<string,mixed>> */
    private function latest_posts_schema(array $posts): array
    {
        $items = [];
        $position = 1;
        foreach ($posts as $post) {
            $title = trim((string) ($post['title'] ?? ''));
            $url = CMS_M365Landing_Repository::main_site_url(CMS_M365Landing_Repository::public_url((string) ($post['permalink'] ?? '')));
            if ($title === '' || $url === '') {
                continue;
            }

            $items[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $title,
                'url' => $url,
            ];
            $position++;
        }

        return $items;
    }

    /** @return array<int,array{path:string,url:string}> */
    private function phinit_card_style_assets(): array
    {
        if (!defined('CMS_PHINIT_THEME_DIR') || !defined('CMS_PHINIT_THEME_URL')) {
            return [];
        }

        $themeDir = rtrim((string) CMS_PHINIT_THEME_DIR, '/\\') . '/';
        $themeUrl = rtrim((string) CMS_PHINIT_THEME_URL, '/') . '/';
        $assets = [];
        foreach (['assets/css/content-cards.css', 'assets/css/homepage-blog.css'] as $file) {
            $assets[] = [
                'path' => $themeDir . $file,
                'url' => $themeUrl . $file,
            ];
        }

        return $assets;
    }

    /** @return array<int,array{path:string,url:string}> */
    private function m365_style_assets(): array
    {
        $assets = [];
        $toolsDir = defined('CMS_M365TOOLS_PLUGIN_DIR')
            ? rtrim((string) CMS_M365TOOLS_PLUGIN_DIR, '/\\') . '/'
            : dirname(rtrim(CMS_M365LANDING_PLUGIN_DIR, '/\\')) . '/cms-m365tools/';
        $toolsUrl = defined('CMS_M365TOOLS_PLUGIN_URL')
            ? rtrim((string) CMS_M365TOOLS_PLUGIN_URL, '/') . '/'
            : '/plugins/cms-m365tools/';

        foreach (['plugin-base.css', 'style.css'] as $file) {
            $assets[] = [
                'path' => $toolsDir . 'assets/css/' . $file,
                'url' => $toolsUrl . 'assets/css/' . $file,
            ];
        }

        $matrixDir = defined('CMS_M365MATRICES_PLUGIN_DIR')
            ? rtrim((string) CMS_M365MATRICES_PLUGIN_DIR, '/\\') . '/'
            : dirname(rtrim(CMS_M365LANDING_PLUGIN_DIR, '/\\')) . '/cms-m365matrices/';
        $matrixUrl = defined('CMS_M365MATRICES_PLUGIN_URL')
            ? rtrim((string) CMS_M365MATRICES_PLUGIN_URL, '/') . '/'
            : '/plugins/cms-m365matrices/';

        foreach (['m365calculator-public.css'] as $file) {
            $assets[] = [
                'path' => $matrixDir . 'assets/css/' . $file,
                'url' => $matrixUrl . 'assets/css/' . $file,
            ];
        }

        return $assets;
    }

    private static function log_exception(string $context, \Throwable $e): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CMS M365 Landing [' . $context . ']: ' . $e->getMessage());
        }
    }
}
