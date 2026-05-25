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

final class CMS_Feed_Public_Controller
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        // Routen werden über register_routes() vom Hauptplugin registriert
    }

    /**
     * Routen am Router registrieren (aufgerufen aus CMS_Feed::register_routes).
     */
    public function register_routes($router): void
    {
        $db   = CMS_Feed_Database::instance();
        $s    = $db->get_settings();
        $slug = $this->sanitize_slug((string) ($s['archive_slug'] ?? 'feeds'));
        if ($slug === '' || $slug === 'feed') {
            $slug = 'feeds';
        }

        // Hauptarchiv: /feeds (oder konfigurierter konfliktfreier Archiv-Slug)
        $router->addRoute('GET', '/' . $slug, [$this, 'route_archive']);
        $router->addRoute('GET', '/' . $slug . '/embed', [$this, 'route_whitelabel']);

        // Öffentliche Bereiche immer unter /feed/{category-slug}
        $router->addRoute('GET', '/feed/:catSlug', [$this, 'route_category']);

        // Rückwärtskompatibler Alias für bestehende Archiv-Links
        $router->addRoute('GET', '/' . $slug . '/:catSlug', [$this, 'route_category']);
    }

    /**
     * Router-Callback: Hauptarchiv.
     */
    public function route_archive(): void
    {
        try {
            if (!$this->has_feed_access()) {
                $this->render_consent_required();
                return;
            }

            $this->render_archive();
        } catch (\Throwable $e) {
            $this->render_public_error(500, 'Feed-Archiv konnte nicht geladen werden.', $e);
        }
    }

    /**
     * Router-Callback: Whitelabel/Embed-Archiv.
     */
    public function route_whitelabel(): void
    {
        try {
            if (!$this->has_feed_access()) {
                $this->render_consent_required();
                return;
            }

            $this->render_archive('whitelabel-feed');
        } catch (\Throwable $e) {
            $this->render_public_error(500, 'Feed-Embed konnte nicht geladen werden.', $e);
        }
    }

    /**
     * Router-Callback: Bereichsseite.
     */
    public function route_category(string $catSlug = ''): void
    {
        try {
            if (!$this->has_feed_access()) {
                $this->render_consent_required();
                return;
            }

            $db       = CMS_Feed_Database::instance();
            $category = $db->get_category_by_slug($this->sanitize_slug($catSlug));

            if ($category && (int) $category['is_public']) {
                $this->render_category($category);
                return;
            }

            $this->render_not_found();
        } catch (\Throwable $e) {
            $this->render_public_error(500, 'Feed-Bereich konnte nicht geladen werden.', $e);
        }
    }

    /**
     * Hauptarchiv rendern (alle Kategorien).
     */
    private function render_archive(string $template = 'archive-feed'): void
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

        $tpl = CMS_Feed_Template_Loader::instance();
        $tpl->render_template($template, [
            'settings'   => $s,
            'categories' => $categories,
            'items'      => $items,
            'search'     => $search,
            'page'       => $page,
            'pages'      => $pages,
            'total'      => $total,
            'archivePath' => $this->get_archive_path($s),
            'publicCategoryBasePath' => '/feed',
        ]);
    }

    /**
     * Kategorieseite rendern.
     */
    private function render_category(array $category): void
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

        $tpl = CMS_Feed_Template_Loader::instance();
        $tpl->render_template('archive-category', [
            'settings' => $s,
            'category' => $category,
            'channels' => $channels,
            'items'    => $items,
            'search'   => $search,
            'page'     => $page,
            'pages'    => $pages,
            'total'    => $total,
            'archivePath' => $this->get_archive_path($s),
            'publicCategoryPath' => '/feed/' . rawurlencode($this->sanitize_slug((string) ($category['slug'] ?? ''))),
        ]);
    }

    private function get_archive_path(array $settings): string
    {
        $slug = $this->sanitize_slug((string) ($settings['archive_slug'] ?? 'feeds'));
        if ($slug === '' || $slug === 'feed') {
            $slug = 'feeds';
        }

        return '/' . $slug;
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

    private function render_consent_required(): void
    {
        http_response_code(403);

        $tpl = CMS_Feed_Template_Loader::instance();
        $tpl->render_template('consent-required', [
            'preferencesUrl' => SITE_URL . '/cookie-einstellungen',
            'homeUrl' => SITE_URL,
        ]);
    }

    private function render_not_found(): void
    {
        http_response_code(404);

        try {
            \CMS\ThemeManager::instance()->render('404');
        } catch (\Throwable $e) {
            error_log('CMS Feed: 404 rendering failed – ' . $e->getMessage());
            $this->render_plain_error(404, 'Feed-Bereich nicht gefunden.');
        }
    }

    private function render_public_error(int $statusCode, string $message, \Throwable $exception): void
    {
        http_response_code($statusCode);
        error_log('CMS Feed Public: ' . $message . ' – ' . $exception->getMessage());

        try {
            \CMS\ThemeManager::instance()->getHeader(['title' => 'Feed-Fehler']);
            echo '<main class="fd-main"><section class="fd-empty" role="alert">';
            echo '<p class="fd-empty__text">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
            echo '</section></main>';
            \CMS\ThemeManager::instance()->getFooter();
        } catch (\Throwable) {
            $this->render_plain_error($statusCode, $message);
        }
    }

    private function render_plain_error(int $statusCode, string $message): void
    {
        http_response_code($statusCode);
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }

        echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Feed-Fehler</title></head><body>';
        echo '<h1>' . (int) $statusCode . '</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '</body></html>';
    }
}
