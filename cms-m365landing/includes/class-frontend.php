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

        $router->addRoute('GET', '/' . $this->route_slug(), function (): void {
            $this->render_landing();
        });

        if ($this->is_domain_landing_request()) {
            $router->addRoute('GET', '/', function (): void {
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
        $latestPosts = [];

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

        $path = $this->request_path();
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

        $this->requestPathCache = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');

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

        return $base . '/' . rawurlencode($this->route_slug());
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
