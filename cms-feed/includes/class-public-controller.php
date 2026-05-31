<?php
/**
 * Public Controller für CMS Feed
 *
 * Registriert Frontend-Routen und rendert die öffentlichen Feed-Seiten.
 *
 * @package CMS_Feed
 * @since   1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('CMS_Feed_Public_Controller', false)) {
    return;
}

final class CMS_Feed_Public_Controller
{
    private static ?self $instance = null;
    private const SHARED_PUBLIC_I18N = '/shared/public/plugin-public-i18n.php';

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->load_shared_public_i18n();
        // Routen werden über register_routes() vom Hauptplugin registriert
    }

    /**
     * Routen am Router registrieren (aufgerufen aus CMS_Feed::register_routes).
     */
    public function register_routes($router): void
    {
        $slug = 'feeds';
        try {
            $db   = CMS_Feed_Database::instance();
            $s    = $db->get_settings();
            $slug = $this->sanitize_slug((string) ($s['archive_slug'] ?? 'feeds'));
        } catch (\Throwable $e) {
            CMS_Feed_Error_Handler::instance()->log_exception('CMS Feed: Public-Routen konnten Einstellungen nicht laden.', $e, 'error', [
                'scope' => 'public.register_routes',
            ]);
        }

        if ($slug === '' || $slug === 'feed') {
            $slug = 'feeds';
        }

        // Hauptarchiv (DE default): /feeds (oder konfigurierter konfliktfreier Archiv-Slug)
        $router->addRoute('GET', '/' . $slug, [$this, 'route_archive']);
        $router->addRoute('GET', '/' . $slug . '/embed', [$this, 'route_whitelabel']);

        // Englische Public-Variante
        $router->addRoute('GET', '/en/' . $slug, [$this, 'route_archive']);
        $router->addRoute('GET', '/en/' . $slug . '/embed', [$this, 'route_whitelabel']);

        // Öffentliche Bereiche immer unter /feed/{category-slug} (DE default)
        $router->addRoute('GET', '/feed/:catSlug', [$this, 'route_category']);
        // Englische Bereichsseite
        $router->addRoute('GET', '/en/feed/:catSlug', [$this, 'route_category']);

        // Rückwärtskompatibler Alias für bestehende Archiv-Links + EN-Variante
        $router->addRoute('GET', '/' . $slug . '/:catSlug', [$this, 'route_category']);
        $router->addRoute('GET', '/en/' . $slug . '/:catSlug', [$this, 'route_category']);
    }

    /**
     * Prüft, ob ein Request-Pfad zu einer Plugin-Public-Route gehört.
     */
    public function matches_public_path(string $path): bool
    {
        $normalizedPath = $this->normalize_runtime_path($path);
        if ($normalizedPath === '') {
            return false;
        }
        $pathWithoutLang = $this->path_without_lang($normalizedPath);
        $path = '/' . trim($pathWithoutLang, '/');

        static $cachedArchiveSlug = null;
        if (!is_string($cachedArchiveSlug)) {
            $cachedArchiveSlug = 'feeds';
            try {
                $db               = CMS_Feed_Database::instance();
                $s                = $db->get_settings();
                $cachedArchiveSlug = $this->sanitize_slug((string) ($s['archive_slug'] ?? 'feeds'));
            } catch (\Throwable $e) {
                CMS_Feed_Error_Handler::instance()->log_exception('CMS Feed: Public-Pfadpruefung konnte Einstellungen nicht laden.', $e, 'warning', [
                    'scope' => 'public.path_detect',
                ]);
            }
        }

        $slug = $cachedArchiveSlug;

        if ($slug === '' || $slug === 'feed') {
            $slug = 'feeds';
        }

        $archivePath = '/' . trim($slug, '/');
        if ($path === $archivePath || $path === $archivePath . '/embed') {
            return true;
        }

        if (str_starts_with($path, '/feed/')) {
            return true;
        }

        return str_starts_with($path, $archivePath . '/');
    }

    /**
     * Router-Callback: Hauptarchiv.
     */
    public function route_archive(): void
    {
        $lang = $this->resolve_request_language();
        try {
            if (!$this->has_feed_access()) {
                $this->render_consent_required($lang);
                return;
            }

            $this->render_archive('archive-feed', $lang);
        } catch (\Throwable $e) {
            $this->render_public_error(
                500,
                $lang === 'en' ? 'Feed archive could not be loaded.' : 'Feed-Archiv konnte nicht geladen werden.',
                $lang === 'en'
                    ? 'The feed archive is currently unavailable. Please try again later.'
                    : 'Das Feed-Archiv konnte aktuell nicht geladen werden. Bitte versuche es später erneut.',
                $e,
                'public.archive'
            );
        }
    }

    /**
     * Router-Callback: Whitelabel/Embed-Archiv.
     */
    public function route_whitelabel(): void
    {
        $lang = $this->resolve_request_language();
        try {
            if (!$this->has_feed_access()) {
                $this->render_consent_required($lang);
                return;
            }

            $this->render_archive('whitelabel-feed', $lang);
        } catch (\Throwable $e) {
            $this->render_public_error(
                500,
                $lang === 'en' ? 'Feed embed could not be loaded.' : 'Feed-Embed konnte nicht geladen werden.',
                $lang === 'en'
                    ? 'The feed embed view is currently unavailable. Please try again later.'
                    : 'Die Feed-Embed-Ansicht konnte aktuell nicht geladen werden. Bitte versuche es später erneut.',
                $e,
                'public.embed'
            );
        }
    }

    /**
     * Router-Callback: Bereichsseite.
     */
    public function route_category(string $catSlug = ''): void
    {
        $lang = $this->resolve_request_language();
        try {
            if (!$this->has_feed_access()) {
                $this->render_consent_required($lang);
                return;
            }

            $db       = CMS_Feed_Database::instance();
            $category = $db->get_category_by_slug($this->sanitize_slug($catSlug));

            if ($category && (int) $category['is_public']) {
                $this->render_category($category, $lang);
                return;
            }

            $this->render_not_found($lang);
        } catch (\Throwable $e) {
            $this->render_public_error(
                500,
                $lang === 'en' ? 'Feed section could not be loaded.' : 'Feed-Bereich konnte nicht geladen werden.',
                $lang === 'en'
                    ? 'The feed section is currently unavailable. Please try again later.'
                    : 'Der Feed-Bereich konnte aktuell nicht geladen werden. Bitte versuche es später erneut.',
                $e,
                'public.category'
            );
        }
    }

    /**
     * Hauptarchiv rendern (alle Kategorien).
     */
    private function render_archive(string $template = 'archive-feed', string $lang = 'de'): void
    {
        $db         = CMS_Feed_Database::instance();
        $s          = $db->get_settings();
        $categories = $db->get_public_categories();

        $page    = max(1, min(999, $this->get_query_int('page', 1)));
        $perPage = max(4, min(100, (int) ($s['per_page'] ?? 20)));
        $offset  = ($page - 1) * $perPage;
        $search  = cms_feed_substr(trim(strip_tags($this->get_query_string('q'))), 0, 120);

        $filters = [];
        if ($search) {
            $filters['search'] = $search;
        }

        $total = $db->count_items($filters);
        $pages = (int) ceil($total / $perPage);
        $items = $db->get_items($filters, $offset, $perPage);

        $localizedSettings = $s;
        $localizedSettings['archive_title'] = $this->i18n_value(
            $s,
            'archive_title',
            $lang,
            (string) ($s['archive_title'] ?? 'Feed-Übersicht')
        );
        $localizedSettings['archive_description'] = $this->i18n_value(
            $s,
            'archive_description',
            $lang,
            (string) ($s['archive_description'] ?? '')
        );

        $tpl = CMS_Feed_Template_Loader::instance();
        $tpl->render_template($template, [
            'settings'   => $localizedSettings,
            'categories' => $categories,
            'items'      => $items,
            'search'     => $search,
            'page'       => $page,
            'pages'      => $pages,
            'total'      => $total,
            'archivePath' => $this->get_archive_path($s, $lang),
            'publicCategoryBasePath' => $this->localized_public_path('/feed', $lang),
            'lang' => $lang,
        ]);
    }

    /**
     * Kategorieseite rendern.
     */
    private function render_category(array $category, string $lang = 'de'): void
    {
        $db = CMS_Feed_Database::instance();
        $s  = $db->get_settings();

        $page    = max(1, min(999, $this->get_query_int('page', 1)));
        $perPage = max(4, min(100, (int) ($category['items_per_page'] ?: ($s['per_page'] ?? 20))));
        $offset  = ($page - 1) * $perPage;
        $search  = cms_feed_substr(trim(strip_tags($this->get_query_string('q'))), 0, 120);

        $filters = ['category_id' => (int) $category['id']];
        if ($search) {
            $filters['search'] = $search;
        }

        $total    = $db->count_items($filters);
        $pages    = (int) ceil($total / $perPage);
        $items    = $db->get_items($filters, $offset, $perPage);
        $channels = $db->get_channels((int) $category['id']);
        $localizedCategory = $category;
        $localizedCategory['name'] = $this->i18n_value(
            $category,
            'name',
            $lang,
            (string) ($category['name'] ?? '')
        );
        $localizedCategory['description'] = $this->i18n_value(
            $category,
            'description',
            $lang,
            (string) ($category['description'] ?? '')
        );

        $tpl = CMS_Feed_Template_Loader::instance();
        $tpl->render_template('archive-category', [
            'settings' => $s,
            'category' => $localizedCategory,
            'channels' => $channels,
            'items'    => $items,
            'search'   => $search,
            'page'     => $page,
            'pages'    => $pages,
            'total'    => $total,
            'archivePath' => $this->get_archive_path($s, $lang),
            'publicCategoryPath' => $this->localized_public_path('/feed/' . rawurlencode($this->sanitize_slug((string) ($category['slug'] ?? ''))), $lang),
            'lang' => $lang,
        ]);
    }

    private function get_archive_path(array $settings, string $lang = 'de'): string
    {
        $slug = $this->sanitize_slug((string) ($settings['archive_slug'] ?? 'feeds'));
        if ($slug === '' || $slug === 'feed') {
            $slug = 'feeds';
        }

        return $this->localized_public_path('/' . $slug, $lang);
    }

    private function sanitize_slug(string $slug): string
    {
        return preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($slug, '/'))) ?: '';
    }

    private function get_query_int(string $key, int $default = 0): int
    {
        $value = $_GET[$key] ?? $default;

        return is_scalar($value) ? (int) $value : $default;
    }

    private function get_query_string(string $key, string $default = ''): string
    {
        $value = $_GET[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    private function has_feed_access(): bool
    {
        if (!class_exists('CMS_Feed')) {
            return true;
        }

        return CMS_Feed::instance()->has_public_feed_consent();
    }

    private function render_consent_required(string $lang = 'de'): void
    {
        http_response_code(403);

        $tpl = CMS_Feed_Template_Loader::instance();
        $tpl->render_template('consent-required', [
            'preferencesUrl' => $this->localized_public_path('/cookie-einstellungen', $lang),
            'homeUrl' => $this->localized_public_path('/', $lang),
            'lang' => $lang,
        ]);
    }

    private function render_not_found(string $lang = 'de'): void
    {
        CMS_Feed_Error_Handler::instance()->render_not_found(
            $lang === 'en' ? 'Feed section not found.' : 'Feed-Bereich nicht gefunden.',
            null,
            [
            'scope' => 'public.category.not_found',
            ]
        );
    }

    private function render_public_error(int $statusCode, string $title, string $message, \Throwable $exception, string $scope): void
    {
        CMS_Feed_Error_Handler::instance()->render_error_page($statusCode, $title, $message, $exception, [
            'scope' => $scope,
        ]);
    }

    private function load_shared_public_i18n(): void
    {
        if (
            function_exists('cms_plugin_public_language')
            && function_exists('cms_plugin_public_path_without_lang')
            && function_exists('cms_plugin_public_localized_path')
        ) {
            return;
        }

        $helperPath = dirname(CMS_FEED_PLUGIN_DIR) . self::SHARED_PUBLIC_I18N;
        if (is_file($helperPath)) {
            require_once $helperPath;
        }
    }

    private function resolve_request_language(): string
    {
        $requestPath = $this->normalize_runtime_path((string) ($_SERVER['REQUEST_URI'] ?? '/'));

        if (function_exists('cms_plugin_public_language')) {
            return cms_plugin_public_language($requestPath);
        }

        return str_starts_with($requestPath, 'en/') || $requestPath === 'en' ? 'en' : 'de';
    }

    private function path_without_lang(string $normalizedPath): string
    {
        if (function_exists('cms_plugin_public_path_without_lang')) {
            return cms_plugin_public_path_without_lang($normalizedPath);
        }

        $trimmed = trim($normalizedPath, '/');
        if ($trimmed === 'en') {
            return '';
        }
        if (str_starts_with($trimmed, 'en/')) {
            return substr($trimmed, 3) ?: '';
        }

        return $trimmed;
    }

    private function localized_public_path(string $path, string $lang): string
    {
        if (function_exists('cms_plugin_public_localized_path')) {
            return cms_plugin_public_localized_path($path, $lang);
        }

        $normalized = trim((string) preg_replace('#/+#', '/', $path), '/');
        if ($lang === 'en') {
            return $normalized === '' ? '/en' : '/en/' . $normalized;
        }

        return $normalized === '' ? '/' : '/' . $normalized;
    }

    private function normalize_runtime_path(string $path): string
    {
        $parsed = parse_url($path, PHP_URL_PATH) ?: '';
        $normalized = (string) preg_replace('#/+#', '/', $parsed);

        return trim($normalized, '/');
    }

    /**
     * @param array<string,mixed> $values
     */
    private function i18n_value(array $values, string $key, string $lang, string $fallback = ''): string
    {
        if (function_exists('cms_plugin_public_i18n_value')) {
            return cms_plugin_public_i18n_value($values, $key, $lang, $fallback);
        }

        if ($lang === 'en' && isset($values[$key . '_en']) && (string) $values[$key . '_en'] !== '') {
            return (string) $values[$key . '_en'];
        }

        if (isset($values[$key]) && (string) $values[$key] !== '') {
            return (string) $values[$key];
        }

        return $fallback;
    }
}
