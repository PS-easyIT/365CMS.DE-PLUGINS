<?php
/**
 * CMS M365 Price Tracker – Public Route.
 *
 * @package CMS_M365PRICETRACKER
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365PRICETRACKER_Frontend
{
    private const EN_PREFIX = '/en';
    private const PRICE_TRACKER_ROUTE = '/microsoft-preiserhoehung-tracker';

    private static ?self $instance = null;
    private bool $routesRegistered = false;
    private ?string $requestPathCache = null;
    private ?string $siteBasePathCache = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function register_routes_hook(): void
    {
        self::instance()->register_routes();
    }

    private function __construct()
    {
        if (class_exists('CMS\\Hooks')) {
            \CMS\Hooks::addFilter('body_class', [$this, 'filter_body_class'], 20);
            \CMS\Hooks::addAction('head', [$this, 'enqueue_public_styles'], 20);
            \CMS\Hooks::addAction('body_end', [$this, 'enqueue_public_scripts'], 5);
        }
    }

    public function register_routes(): void
    {
        if ($this->routesRegistered) {
            return;
        }

        if (!class_exists('CMS\\Router')) {
            return;
        }

        $router = \CMS\Router::instance();
        $router->addRoute('GET', self::PRICE_TRACKER_ROUTE, function (): void {
            $this->render_price_tracker();
        });
        $router->addRoute('GET', self::EN_PREFIX . self::PRICE_TRACKER_ROUTE, function (): void {
            $this->render_price_tracker();
        });
        $this->routesRegistered = true;
    }

    public function enqueue_public_styles(): void
    {
        if (!$this->is_price_tracker_request()) {
            return;
        }

        foreach (['plugin-base.css', 'price-tracker.css'] as $cssFile) {
            $path = CMS_M365PRICETRACKER_PLUGIN_DIR . 'assets/css/' . $cssFile;
            if (!is_file($path)) {
                continue;
            }

            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_M365PRICETRACKER_PLUGIN_URL . 'assets/css/' . $cssFile, ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($path) . '">' . "\n";
        }

        $vars = CMS_M365PRICETRACKER_Settings::css_vars();
        echo '<style id="m365price-tracker-settings">' . "\n";
        echo '.m365price-tracker-page{' . "\n";
        foreach ($vars as $name => $value) {
            echo '  ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        }
        echo '}' . "\n";
        echo '</style>' . "\n";
    }

    public function enqueue_public_scripts(): void
    {
        if (!$this->is_price_tracker_request()) {
            return;
        }

        $js = CMS_M365PRICETRACKER_PLUGIN_DIR . 'assets/js/price-tracker.js';
        if (!is_file($js)) {
            return;
        }

        echo '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js" defer></script>' . "\n";
        echo '<script src="'
            . htmlspecialchars(CMS_M365PRICETRACKER_PLUGIN_URL . 'assets/js/price-tracker.js', ENT_QUOTES, 'UTF-8')
            . '?v=' . filemtime($js) . '" defer></script>' . "\n";
    }

    public function filter_body_class(mixed $bodyClass): string
    {
        $classes = preg_split('/\s+/', trim((string) $bodyClass), -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($classes)) {
            $classes = [];
        }

        if ($this->is_price_tracker_request()) {
            $classes[] = 'm365price-tracker-theme-embed';
            $classes[] = 'm365tools-module-microsoft-price-tracker';
        }

        return implode(' ', array_values(array_unique($classes)));
    }

    private function render_price_tracker(): void
    {
        $input = CMS_M365PRICETRACKER_Microsoft_Price_Tracker::normalize_input($_GET);
        $result = CMS_M365PRICETRACKER_Microsoft_Price_Tracker::evaluate($input);

        $this->set_seo('Microsoft-Preiserhöhung-Tracker', 'Verfolgt offizielle Microsoft-Preis-, Packaging-, Renewal- und SKU-Ereignisse mit Chart.js-Preisverlauf und Budgetwirkung.');
        include CMS_M365PRICETRACKER_Microsoft_Price_Tracker::render_price_tracker_page();
        exit;
    }

    private function set_seo(string $title, string $description): void
    {
        if (!class_exists('CMS\\Hooks')) {
            return;
        }

        \CMS\Hooks::addFilter('seo_title', static fn(string $current): string => $title, 20);
        \CMS\Hooks::addFilter('seo_description', static fn(string $current): string => $description, 20);
    }

    private function is_price_tracker_request(): bool
    {
        return $this->path_matches(self::PRICE_TRACKER_ROUTE);
    }

    private function path_matches(string $route): bool
    {
        $routePath = trim($route, '/');
        if ($routePath === '') {
            return false;
        }

        if (function_exists('cms_plugin_public_path_without_lang')) {
            return cms_plugin_public_path_without_lang($this->normalized_request_path()) === $routePath;
        }

        $path = $this->normalized_request_path();
        if ($path === $routePath || $path === 'en/' . $routePath) {
            return true;
        }

        $basePath = $this->normalized_site_base_path();
        if ($basePath === '') {
            return false;
        }

        return $path === $basePath . '/' . $routePath || $path === $basePath . '/en/' . $routePath;
    }

    private function normalized_request_path(): string
    {
        if ($this->requestPathCache !== null) {
            return $this->requestPathCache;
        }

        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $this->requestPathCache = $this->normalize_path($path);

        return $this->requestPathCache;
    }

    private function normalized_site_base_path(): string
    {
        if ($this->siteBasePathCache !== null) {
            return $this->siteBasePathCache;
        }

        $base = defined('SITE_URL') ? (string) SITE_URL : '';
        $path = $base !== '' ? (string) parse_url($base, PHP_URL_PATH) : '';
        $this->siteBasePathCache = $this->normalize_path($path);

        return $this->siteBasePathCache;
    }

    private function normalize_path(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path);
        $path = is_string($path) ? trim($path, '/') : '';

        return strtolower(rawurldecode($path));
    }
}
