<?php
/**
 * CMS M365 Linkcollection – Public Routes.
 *
 * @package CMS_M365LINKCOLLECTION
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365LINKCOLLECTION_Frontend
{
    private static ?self $instance = null;
    private ?string $requestPathCache = null;
    private ?string $requestLangCache = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->register_routes();
        }

        return self::$instance;
    }

    private function __construct()
    {
        if (class_exists('CMS\\Hooks')) {
            \CMS\Hooks::addFilter('body_class', [$this, 'filter_body_class'], 20);
            \CMS\Hooks::addAction('head', [$this, 'enqueue_public_styles'], 20);
            \CMS\Hooks::addAction('head', [$this, 'output_public_design_tokens'], 30);
            \CMS\Hooks::addAction('body_end', [$this, 'enqueue_public_scripts'], 20);
        }
    }

    private function register_routes(): void
    {
        if (!class_exists('CMS\\Router')) {
            return;
        }

        $router = \CMS\Router::instance();
        $routes = [
            CMS_M365LINKCOLLECTION_Settings::route(),
            '/m365-linkcollection',
        ];

        foreach (['de', 'en'] as $lang) {
            foreach ($routes as $route) {
                $localizedRoute = $this->localized_route($route, $lang);
                if ($localizedRoute === '') {
                    continue;
                }
                $router->addRoute('GET', $localizedRoute, function (): void {
                    $this->render_archive();
                });
            }
        }
    }

    public function filter_body_class(mixed $bodyClass): string
    {
        $classes = preg_split('/\s+/', trim((string) $bodyClass), -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($classes)) {
            $classes = [];
        }

        if ($this->is_linkcollection_request()) {
            $classes[] = 'm365linkcollection-theme-embed';
        }

        return implode(' ', array_values(array_unique($classes)));
    }

    public function enqueue_public_styles(): void
    {
        if (!$this->should_load_assets()) {
            return;
        }

        foreach (['assets/css/style.css'] as $asset) {
            $path = CMS_M365LINKCOLLECTION_PLUGIN_DIR . $asset;
            if (!file_exists($path)) {
                continue;
            }
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_M365LINKCOLLECTION_PLUGIN_URL . $asset, ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($path) . '">' . "\n";
        }
    }

    public function output_public_design_tokens(): void
    {
        if (!$this->should_load_assets()) {
            return;
        }

        $vars = [
            '--mlc-bg' => CMS_M365LINKCOLLECTION_Settings::color('color_page_background', '#edf1f6'),
            '--mlc-surface' => CMS_M365LINKCOLLECTION_Settings::color('color_surface', '#ffffff'),
            '--mlc-text' => CMS_M365LINKCOLLECTION_Settings::color('color_text', '#1e293b'),
            '--mlc-muted' => CMS_M365LINKCOLLECTION_Settings::color('color_muted', '#64748b'),
            '--mlc-border' => CMS_M365LINKCOLLECTION_Settings::color('color_border', '#dbe4ef'),
            '--mlc-accent' => CMS_M365LINKCOLLECTION_Settings::color('color_accent', '#1e3a5f'),
            '--mlc-button-bg' => CMS_M365LINKCOLLECTION_Settings::color('color_button_bg', '#1e3a5f'),
            '--mlc-button-text' => CMS_M365LINKCOLLECTION_Settings::color('color_button_text', '#ffffff'),
            '--mlc-radius' => CMS_M365LINKCOLLECTION_Settings::int('border_radius', 10, 0, 24) . 'px',
            '--mlc-image-h' => CMS_M365LINKCOLLECTION_Settings::int('image_height', 96, 48, 240) . 'px',
            '--mlc-card-image-h' => CMS_M365LINKCOLLECTION_Settings::int('card_image_height', 132, 64, 260) . 'px',
            '--mlc-spacing-top' => CMS_M365LINKCOLLECTION_Settings::int('content_spacing_top', 25, 0, 160) . 'px',
            '--mlc-spacing-bottom' => CMS_M365LINKCOLLECTION_Settings::int('content_spacing_bottom', 50, 0, 200) . 'px',
            '--mlc-content-pad-y' => CMS_M365LINKCOLLECTION_Settings::int('content_padding_y', 0, 0, 80) . 'px',
            '--mlc-content-pad-x' => CMS_M365LINKCOLLECTION_Settings::int('content_padding_x', 24, 0, 80) . 'px',
            '--mlc-section-gap' => CMS_M365LINKCOLLECTION_Settings::int('section_gap', 16, 0, 80) . 'px',
            '--mlc-sidebar-min-h' => CMS_M365LINKCOLLECTION_Settings::int('sidebar_min_height', 208, 120, 520) . 'px',
            '--mlc-sidebar-image-h' => CMS_M365LINKCOLLECTION_Settings::int('sidebar_image_height', 132, 0, 320) . 'px',
        ];

        echo '<style id="cms-m365linkcollection-public-design">' . "\n";
        echo ':root, body.m365linkcollection-theme-embed {' . "\n";
        foreach ($vars as $name => $value) {
            echo '    ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        }
        echo '}' . "\n";
        echo '</style>' . "\n";
    }

    public function enqueue_public_scripts(): void
    {
        if (!$this->should_load_assets()) {
            return;
        }

        $path = CMS_M365LINKCOLLECTION_PLUGIN_DIR . 'assets/js/public.js';
        if (!file_exists($path)) {
            return;
        }

        echo '<script src="'
            . htmlspecialchars(CMS_M365LINKCOLLECTION_PLUGIN_URL . 'assets/js/public.js', ENT_QUOTES, 'UTF-8')
            . '?v=' . filemtime($path) . '" defer></script>' . "\n";
    }

    private function render_archive(): void
    {
        if (!CMS_M365LINKCOLLECTION_Settings::bool('page_enabled', true)) {
            $this->render_404();
            return;
        }

        CMS_M365LINKCOLLECTION_Installer::maybe_install();

        $repo = CMS_M365LINKCOLLECTION_Repository::instance();
        $settings = CMS_M365LINKCOLLECTION_Settings::all();
        $lang = $this->current_language();
        $category = CMS_M365LINKCOLLECTION_Repository::slugify((string) ($_GET['category'] ?? ''));
        if ($category === 'link') {
            $category = '';
        }
        $q = trim(strip_tags((string) ($_GET['q'] ?? '')));
        $view = (string) ($settings['default_view'] ?? 'cards');
        if (!in_array($view, ['cards', 'table', 'both'], true)) {
            $view = 'cards';
        }
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $perPage = CMS_M365LINKCOLLECTION_Settings::int('items_per_page', 120, 12, 500);
        $payload = $repo->links(['public' => true, 'category' => $category, 'q' => $q], $perPage, ($page - 1) * $perPage);
        $items = $payload['items'];
        $total = $payload['total'];
        $categories = $repo->categories(true);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $localizedRoute = $this->localized_route(CMS_M365LINKCOLLECTION_Settings::route(), $lang);
        $siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
        $pageUrl = $siteUrl . $localizedRoute;

        $this->set_seo(
            $this->i18n($settings, 'page_title', $lang, 'MS365 | SITES & BLOGS'),
            $this->i18n($settings, 'page_intro', $lang, '')
        );

        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getHeader(['title' => $this->i18n($settings, 'page_title', $lang, 'MS365 | SITES & BLOGS')]);
        }

        $structuredDataJson = '';
        if (CMS_M365LINKCOLLECTION_Settings::bool('show_structured_data', false)) {
            $structuredDataJson = $this->build_structured_data_json($items, $settings, $lang, $pageUrl);
        }

        $template = CMS_M365LINKCOLLECTION_PLUGIN_DIR . 'templates/page-linkcollection.php';
        if (!is_file($template) || !is_readable($template)) {
            $this->log_error('Template not found or unreadable: ' . $template);
            $this->render_404();
            return;
        }

        include $template;

        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getFooter();
        }

        exit;
    }

    private function is_linkcollection_request(): bool
    {
        return $this->path_matches(CMS_M365LINKCOLLECTION_Settings::route()) || $this->path_matches('/m365-linkcollection');
    }

    private function should_load_assets(): bool
    {
        return $this->is_linkcollection_request();
    }

    private function path_matches(string $route): bool
    {
        $normalizedRoute = trim($route, '/');
        if ($normalizedRoute === '') {
            return false;
        }

        if (function_exists('cms_plugin_public_path_without_lang')) {
            return cms_plugin_public_path_without_lang($this->normalized_request_path()) === strtolower($normalizedRoute);
        }

        return $this->normalized_request_path() === strtolower($normalizedRoute);
    }

    private function normalized_request_path(): string
    {
        if ($this->requestPathCache !== null) {
            return $this->requestPathCache;
        }

        if (function_exists('cms_plugin_public_request_path')) {
            $this->requestPathCache = cms_plugin_public_request_path();
            return $this->requestPathCache;
        }

        $this->requestPathCache = strtolower(trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/'));
        return $this->requestPathCache;
    }

    private function normalized_site_base_path(): string
    {
        $siteUrl = defined('SITE_URL') ? (string) SITE_URL : '';
        if ($siteUrl === '') {
            return '';
        }

        return trim((string) parse_url($siteUrl, PHP_URL_PATH), '/');
    }

    private function set_seo(string $title, string $description): void
    {
        if (!class_exists('CMS\\Services\\SEOService')) {
            return;
        }

        try {
            $seo = \CMS\Services\SEOService::instance();
            $seo->setTitle($title);
            $seo->setDescription($description);
        } catch (\Throwable $e) {
            $this->log_error('SEO integration failed: ' . $e->getMessage());
        }
    }

    private function render_404(): void
    {
        $lang = $this->current_language();
        $title = $lang === 'en' ? 'Not found' : 'Nicht gefunden';
        $message = $lang === 'en' ? 'Link collection not available' : 'Linkcollection nicht verfügbar';

        http_response_code(404);
        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getHeader(['title' => $title]);
        }
        echo '<main class="phinit-plugin mlc-page"><section class="phinit-empty-state"><p class="phinit-empty-state__title">'
            . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
            . '</p></section></main>';
        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getFooter();
        }
        exit;
    }

    private function current_language(): string
    {
        if ($this->requestLangCache !== null) {
            return $this->requestLangCache;
        }

        if (function_exists('cms_plugin_public_language')) {
            $this->requestLangCache = cms_plugin_public_language($this->normalized_request_path());
            return $this->requestLangCache;
        }

        $this->requestLangCache = str_starts_with($this->normalized_request_path(), 'en/') ? 'en' : 'de';
        return $this->requestLangCache;
    }

    private function localized_route(string $route, string $lang): string
    {
        $normalized = trim($route, '/');
        if ($normalized === '') {
            return '';
        }

        if (function_exists('cms_plugin_public_localized_path')) {
            return cms_plugin_public_localized_path($normalized, $lang);
        }

        return $lang === 'en' ? '/en/' . $normalized : '/' . $normalized;
    }

    /**
     * @param array<string,string> $values
     */
    private function i18n(array $values, string $key, string $lang, string $fallback = ''): string
    {
        if (function_exists('cms_plugin_public_i18n_value')) {
            return cms_plugin_public_i18n_value($values, $key, $lang, $fallback);
        }

        if ($lang === 'en' && isset($values[$key . '_en']) && $values[$key . '_en'] !== '') {
            return (string) $values[$key . '_en'];
        }

        if (isset($values[$key]) && $values[$key] !== '') {
            return (string) $values[$key];
        }

        return $fallback;
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @param array<string,string> $settings
     */
    private function build_structured_data_json(array $items, array $settings, string $lang, string $pageUrl): string
    {
        $itemList = [];
        $position = 1;

        foreach ($items as $item) {
            $url = trim((string) ($item['url'] ?? ''));
            if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false || preg_match('#^https?://#i', $url) !== 1) {
                continue;
            }

            $name = trim((string) ($item['title'] ?? ''));
            if ($name === '') {
                $name = $url;
            }

            $itemList[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $name,
                'url' => $url,
            ];
        }

        $graph = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Home',
                        'item' => defined('SITE_URL') ? rtrim((string) SITE_URL, '/') . '/' : '/',
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => $this->i18n($settings, 'page_title', $lang, 'MS365 | SITES & BLOGS'),
                        'item' => $pageUrl,
                    ],
                ],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                'name' => $this->i18n($settings, 'label_cards_heading', $lang, 'Link overview'),
                'numberOfItems' => count($itemList),
                'itemListElement' => $itemList,
            ],
        ];

        $json = json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : '';
    }

    private function log_error(string $message): void
    {
        error_log('CMS M365 Linkcollection frontend: ' . $message);
    }
}
