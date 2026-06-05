<?php
/**
 * CMS M365 Message Center – Frontend.
 *
 * @package CMS_M365MessageCenter
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365MessageCenter_Frontend
{
    private static ?self $instance = null;
    private ?string $requestPathCache = null;
    private ?bool $isRequestCache = null;

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
            \CMS\Hooks::addAction('head', [$this, 'enqueue_styles'], 20);
            \CMS\Hooks::addFilter('body_class', [$this, 'filter_body_class'], 20);
        }
    }

    private function register_routes(): void
    {
        if (!class_exists('CMS\\Router')) {
            return;
        }

        $router = \CMS\Router::instance();
        $slugs = array_values(array_unique(['m365-messagecenter', $this->route_slug()]));
        foreach ($slugs as $slug) {
            $route = '/' . trim($slug, '/');
            $router->addRoute('GET', $route . '/:messageId', function (string $messageId): void {
                $this->render_detail($messageId);
            });
            $router->addRoute('GET', '/en' . $route . '/:messageId', function (string $messageId): void {
                $this->render_detail($messageId);
            });
            $router->addRoute('GET', $route, function (): void {
                $this->render_archive();
            });
            $router->addRoute('GET', '/en' . $route, function (): void {
                $this->render_archive();
            });
        }
    }

    public function enqueue_styles(): void
    {
        if (!$this->is_request()) {
            return;
        }

        $css = CMS_M365MESSAGECENTER_PLUGIN_DIR . 'assets/css/style.css';
        if (is_file($css)) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_M365MESSAGECENTER_PLUGIN_URL . 'assets/css/style.css?v=' . filemtime($css), ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
    }

    public function filter_body_class(string $classes): string
    {
        if ($this->is_request()) {
            $classes .= ' m365messagecenter-page';
        }

        return trim($classes);
    }

    private function render_archive(): void
    {
        $repo = $this->repo();
        $settings = $repo->settings();
        $perPage = max(6, min(60, (int) ($settings['items_per_page'] ?? 24)));
        $query = [
            'page' => max(1, (int) ($_GET['mc_page'] ?? 1)),
            'per_page' => $perPage,
            'sort' => CMS_M365MessageCenter_Repository::public_sort((string) ($_GET['sort'] ?? ($settings['default_sort'] ?? 'last_modified'))),
            'direction' => strtolower((string) ($_GET['direction'] ?? ($settings['default_direction'] ?? 'desc'))) === 'asc' ? 'asc' : 'desc',
            'search' => CMS_M365MessageCenter_Repository::text((string) ($_GET['q'] ?? ''), 120),
            'service' => CMS_M365MessageCenter_Repository::text((string) ($_GET['service'] ?? ''), 120),
            'category' => CMS_M365MessageCenter_Repository::text((string) ($_GET['category'] ?? ''), 120),
        ];
        $result = $repo->public_messages($query);
        $services = $repo->services();
        $categories = $repo->categories();
        $baseUrl = '/' . $this->route_slug();
        if ($this->public_language() === 'en') {
            $baseUrl = '/en' . $baseUrl;
        }

        $title = $this->setting($settings, 'page_title', 'M365 Message Center');
        $description = $this->setting($settings, 'page_intro', 'Aktuelle Microsoft-365-Ankündigungen aus dem Message Center.');

        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getHeader(['title' => $title, 'description' => $description]);
        }

        include CMS_M365MESSAGECENTER_PLUGIN_DIR . 'templates/archive.php';

        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getFooter();
        }

        exit;
    }

    private function render_detail(string $messageId): void
    {
        $repo = $this->repo();
        $settings = $repo->settings();
        $baseUrl = '/' . $this->route_slug();
        if ($this->public_language() === 'en') {
            $baseUrl = '/en' . $baseUrl;
        }

        if ((string) ($settings['show_detail_pages'] ?? '1') !== '1') {
            if (class_exists('CMS\\Router')) {
                \CMS\Router::instance()->redirect($baseUrl);
            }
            header('Location: ' . $baseUrl);
            exit;
        }

        $item = $repo->public_message($messageId);
        if ($item === null) {
            http_response_code(404);
            if (class_exists('CMS\\ThemeManager')) {
                \CMS\ThemeManager::instance()->getHeader(['title' => 'Message Center Eintrag nicht gefunden']);
            }
            echo '<main class="phinit-plugin m365mc-archive m365mc-detail"><section class="m365mc-empty phinit-card"><h1>Eintrag nicht gefunden</h1><p>Die angeforderte Message-Center-Meldung ist im lokalen Cache nicht vorhanden.</p><p><a class="phinit-btn phinit-btn--secondary" href="/' . htmlspecialchars($this->route_slug(), ENT_QUOTES, 'UTF-8') . '">Zur Übersicht</a></p></section></main>';
            if (class_exists('CMS\\ThemeManager')) {
                \CMS\ThemeManager::instance()->getFooter();
            }
            exit;
        }

        $title = trim((string) ($item['title'] ?? '')) ?: 'M365 Message Center';
        $description = trim((string) ($item['body_excerpt'] ?? '')) ?: $this->setting($settings, 'page_intro', 'Aktuelle Microsoft-365-Ankündigung aus dem Message Center.');

        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getHeader(['title' => $title, 'description' => $description]);
        }

        include CMS_M365MESSAGECENTER_PLUGIN_DIR . 'templates/detail.php';

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

    private function is_request(): bool
    {
        if ($this->isRequestCache !== null) {
            return $this->isRequestCache;
        }

        $path = $this->path_without_language($this->request_path());
        $routeSlug = $this->route_slug();
        $this->isRequestCache = $path === $routeSlug
            || $path === 'm365-messagecenter'
            || str_starts_with($path, $routeSlug . '/')
            || str_starts_with($path, 'm365-messagecenter/');

        return $this->isRequestCache;
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

    private function route_slug(): string
    {
        try {
            $settings = $this->repo()->settings();
            return CMS_M365MessageCenter_Repository::slug((string) ($settings['route_slug'] ?? 'm365-messagecenter'));
        } catch (\Throwable $e) {
            self::log_exception('route_slug_fallback', $e);
            return 'm365-messagecenter';
        }
    }

    private function repo(): CMS_M365MessageCenter_Repository
    {
        CMS_M365MessageCenter_Installer::maybe_install();
        return CMS_M365MessageCenter_Repository::instance();
    }

    private static function log_exception(string $context, \Throwable $e): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CMS M365 Message Center frontend [' . $context . ']: ' . $e->getMessage());
        }
    }
}
