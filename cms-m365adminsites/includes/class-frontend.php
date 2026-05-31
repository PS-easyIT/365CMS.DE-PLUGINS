<?php
/**
 * CMS M365 Adminsites – Public Routes.
 *
 * @package CMS_M365ADMINSITES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365ADMINSITES_Frontend
{
    private static ?self $instance = null;
    private ?string $requestPathCache = null;

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
        $router->addRoute('GET', CMS_M365ADMINSITES_Settings::route(), function (): void {
            $this->render_archive();
        });
        $router->addRoute('GET', '/m365-admin-sites', function (): void {
            $this->render_archive();
        });
        $router->addRoute('GET', '/m365-admin-portale', function (): void {
            $this->render_archive();
        });
    }

    public function filter_body_class(mixed $bodyClass): string
    {
        $classes = preg_split('/\s+/', trim((string) $bodyClass), -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($classes)) {
            $classes = [];
        }

        if ($this->is_adminsites_request()) {
            $classes[] = 'm365adminsites-theme-embed';
        }

        return implode(' ', array_values(array_unique($classes)));
    }

    public function enqueue_public_styles(): void
    {
        if (!$this->should_load_assets()) {
            return;
        }

        $asset = 'assets/css/style.css';
        $path = CMS_M365ADMINSITES_PLUGIN_DIR . $asset;
        if (!file_exists($path)) {
            return;
        }

        echo '<link rel="stylesheet" href="'
            . htmlspecialchars(CMS_M365ADMINSITES_PLUGIN_URL . $asset, ENT_QUOTES, 'UTF-8')
            . '?v=' . filemtime($path) . '">' . "\n";
    }

    public function output_public_design_tokens(): void
    {
        if (!$this->should_load_assets()) {
            return;
        }

        $vars = [
            '--mas-bg' => CMS_M365ADMINSITES_Settings::color('color_page_background', '#edf1f6'),
            '--mas-surface' => CMS_M365ADMINSITES_Settings::color('color_surface', '#ffffff'),
            '--mas-text' => CMS_M365ADMINSITES_Settings::color('color_text', '#1e293b'),
            '--mas-muted' => CMS_M365ADMINSITES_Settings::color('color_muted', '#64748b'),
            '--mas-border' => CMS_M365ADMINSITES_Settings::color('color_border', '#dbe4ef'),
            '--mas-accent' => CMS_M365ADMINSITES_Settings::color('color_accent', '#1e3a5f'),
            '--mas-button-bg' => CMS_M365ADMINSITES_Settings::color('color_button_bg', '#1e3a5f'),
            '--mas-button-text' => CMS_M365ADMINSITES_Settings::color('color_button_text', '#ffffff'),
            '--mas-radius' => CMS_M365ADMINSITES_Settings::int('border_radius', 10, 0, 24) . 'px',
            '--mas-image-h' => CMS_M365ADMINSITES_Settings::int('image_height', 72, 48, 240) . 'px',
            '--mas-card-image-h' => CMS_M365ADMINSITES_Settings::int('card_image_height', 118, 64, 260) . 'px',
            '--mas-spacing-top' => CMS_M365ADMINSITES_Settings::int('content_spacing_top', 25, 0, 160) . 'px',
            '--mas-spacing-bottom' => CMS_M365ADMINSITES_Settings::int('content_spacing_bottom', 50, 0, 200) . 'px',
            '--mas-content-pad-y' => CMS_M365ADMINSITES_Settings::int('content_padding_y', 0, 0, 80) . 'px',
            '--mas-content-pad-x' => CMS_M365ADMINSITES_Settings::int('content_padding_x', 24, 0, 80) . 'px',
            '--mas-section-gap' => CMS_M365ADMINSITES_Settings::int('section_gap', 16, 0, 80) . 'px',
            '--mas-sidebar-min-h' => CMS_M365ADMINSITES_Settings::int('sidebar_min_height', 196, 120, 520) . 'px',
            '--mas-sidebar-image-h' => CMS_M365ADMINSITES_Settings::int('sidebar_image_height', 118, 0, 320) . 'px',
        ];

        echo '<style id="cms-m365adminsites-public-design">' . "\n";
        echo ':root, body.m365adminsites-theme-embed {' . "\n";
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

        $path = CMS_M365ADMINSITES_PLUGIN_DIR . 'assets/js/public.js';
        if (!file_exists($path)) {
            return;
        }

        echo '<script src="'
            . htmlspecialchars(CMS_M365ADMINSITES_PLUGIN_URL . 'assets/js/public.js', ENT_QUOTES, 'UTF-8')
            . '?v=' . filemtime($path) . '" defer></script>' . "\n";
    }

    private function render_archive(): void
    {
        if (!CMS_M365ADMINSITES_Settings::bool('page_enabled', true)) {
            $this->render_404();
            return;
        }

        CMS_M365ADMINSITES_Installer::maybe_install();

        $repo = CMS_M365ADMINSITES_Repository::instance();
        $settings = CMS_M365ADMINSITES_Settings::all();
        $category = CMS_M365ADMINSITES_Repository::slugify((string) ($_GET['category'] ?? ''));
        if ($category === 'site') {
            $category = '';
        }
        $q = trim(strip_tags((string) ($_GET['q'] ?? '')));
        $view = (string) ($settings['default_view'] ?? 'cards');
        if (!in_array($view, ['cards', 'table', 'both'], true)) {
            $view = 'cards';
        }
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $perPage = CMS_M365ADMINSITES_Settings::int('items_per_page', 140, 12, 500);
        $payload = $repo->sites(['public' => true, 'category' => $category, 'q' => $q], $perPage, ($page - 1) * $perPage);
        $items = $payload['items'];
        $total = $payload['total'];
        $categories = $repo->categories(true);
        $totalPages = max(1, (int) ceil($total / $perPage));

        $this->set_seo(
            (string) ($settings['page_title'] ?? 'MS365 | Admin Sites & Portale'),
            (string) ($settings['page_intro'] ?? '')
        );

        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getHeader(['title' => (string) ($settings['page_title'] ?? 'MS365 | Admin Sites & Portale')]);
        }

        include CMS_M365ADMINSITES_PLUGIN_DIR . 'templates/page-adminsites.php';

        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getFooter();
        }

        exit;
    }

    private function is_adminsites_request(): bool
    {
        return $this->path_matches(CMS_M365ADMINSITES_Settings::route()) || $this->path_matches('/m365-admin-sites') || $this->path_matches('/m365-admin-portale');
    }

    private function should_load_assets(): bool
    {
        return $this->is_adminsites_request();
    }

    private function path_matches(string $route): bool
    {
        $path = $this->normalized_request_path();
        $routePath = trim($route, '/');
        return $path === $routePath || str_ends_with($path, '/' . $routePath);
    }

    private function normalized_request_path(): string
    {
        if ($this->requestPathCache !== null) {
            return $this->requestPathCache;
        }

        $this->requestPathCache = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');
        return $this->requestPathCache;
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
            // SEO darf die Public-Ausgabe nicht blockieren.
        }
    }

    private function render_404(): void
    {
        http_response_code(404);
        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getHeader(['title' => 'Nicht gefunden']);
        }
        echo '<main class="phinit-plugin mas-page"><section class="phinit-empty-state"><p class="phinit-empty-state__title">Adminsites nicht verfügbar</p></section></main>';
        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getFooter();
        }
        exit;
    }
}
