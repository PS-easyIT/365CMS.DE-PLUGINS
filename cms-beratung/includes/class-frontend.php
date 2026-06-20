<?php
/**
 * CMS Beratung – public routing and asset loading.
 *
 * @package CMS_Beratung
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Beratung_Frontend
{
    private static ?self $instance = null;
    private bool $routesRegistered = false;
    private ?array $currentPage = null;

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
            \CMS\Hooks::addAction('head', [$this, 'enqueue_public_assets'], 18);
            \CMS\Hooks::addAction('head', [$this, 'output_seo_head'], 19);
            \CMS\Hooks::addAction('body_end', [$this, 'enqueue_public_scripts'], 20);
            \CMS\Hooks::addFilter('body_class', [$this, 'filter_body_class'], 20);
        }
    }

    public function filter_body_class(mixed $bodyClass): string
    {
        $classes = trim((string) $bodyClass);
        if ($this->resolve_current_page() === null) {
            return $classes;
        }
        $list = preg_split('/\s+/', $classes, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($list)) {
            $list = [];
        }
        $list[] = 'cms-beratung-page';
        return implode(' ', array_values(array_unique($list)));
    }

    public function enqueue_public_assets(): void
    {
        if ($this->resolve_current_page() === null) {
            return;
        }
        $css = CMS_BERATUNG_PLUGIN_DIR . 'assets/css/frontend.css';
        if (is_file($css)) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_BERATUNG_PLUGIN_URL . 'assets/css/frontend.css', ENT_QUOTES, 'UTF-8') . '?v=' . filemtime($css) . '">' . "\n";
        }
        $extraCss = CMS_BERATUNG_PLUGIN_DIR . 'assets/css/frontend-extra.css';
        if (is_file($extraCss)) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_BERATUNG_PLUGIN_URL . 'assets/css/frontend-extra.css', ENT_QUOTES, 'UTF-8') . '?v=' . filemtime($extraCss) . '">' . "\n";
        }
        $themeSafeCss = CMS_BERATUNG_PLUGIN_DIR . 'assets/css/frontend-theme-safe.css';
        if (is_file($themeSafeCss)) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_BERATUNG_PLUGIN_URL . 'assets/css/frontend-theme-safe.css', ENT_QUOTES, 'UTF-8') . '?v=' . filemtime($themeSafeCss) . '">' . "\n";
        }
    }

    public function output_seo_head(): void
    {
        $page = $this->resolve_current_page();
        if ($page === null) {
            return;
        }
        CMS_Beratung_SEO::output_head($page);
    }

    public function enqueue_public_scripts(): void
    {
        if ($this->resolve_current_page() === null) {
            return;
        }
        $js = CMS_BERATUNG_PLUGIN_DIR . 'assets/js/frontend.js';
        if (is_file($js)) {
            echo '<script src="' . htmlspecialchars(CMS_BERATUNG_PLUGIN_URL . 'assets/js/frontend.js', ENT_QUOTES, 'UTF-8') . '?v=' . filemtime($js) . '" defer></script>' . "\n";
        }
    }

    private function register_routes(?\CMS\Router $router = null): void
    {
        if ($this->routesRegistered || !class_exists('CMS\\Router')) {
            return;
        }
        $this->routesRegistered = true;
        $router = $router instanceof \CMS\Router ? $router : \CMS\Router::instance();
        foreach (CMS_Beratung_Storage::instance()->all_landingpages() as $row) {
            if (($row['status'] ?? '') !== 'published') {
                continue;
            }
            $slug = trim((string) ($row['slug'] ?? ''), '/');
            if ($slug === '') {
                continue;
            }
            foreach (['/beratung/' . $slug, '/de/beratung/' . $slug, '/en/beratung/' . $slug] as $route) {
                $router->addRoute('GET', $route, function () use ($slug): void {
                    $this->render_page($slug);
                });
                $router->addRoute('POST', $route, function () use ($slug): void {
                    $this->render_page($slug);
                });
            }
        }
    }

    private function render_page(string $slug): void
    {
            $page = CMS_Beratung_Storage::instance()->get_landingpage_by_slug($slug, true);
        if ($page === null) {
            http_response_code(404);
            echo 'Landingpage nicht gefunden.';
            exit;
        }

        $this->currentPage = $page;
        $formResult = CMS_Beratung_Forms::handle_submission($page);

        if (!empty($page['show_header']) && class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getHeader(['title' => (string) ($page['public_title'] ?? 'CMS Beratung')]);
        }

        CMS_Beratung_Renderer::render($page, $formResult);

        if (!empty($page['show_footer']) && class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getFooter();
        }
        exit;
    }

    private function resolve_current_page(): ?array
    {
        if ($this->currentPage !== null) {
            return $this->currentPage;
        }
        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
        $path = strtolower(trim((string) preg_replace('#/+#', '/', $path), '/'));
        if (preg_match('#^(?:[a-z]{2}/)?beratung/([a-z0-9_-]+)$#', $path, $matches) !== 1) {
            return null;
        }
        $this->currentPage = CMS_Beratung_Storage::instance()->get_landingpage_by_slug((string) $matches[1], true);
        return $this->currentPage;
    }
}
