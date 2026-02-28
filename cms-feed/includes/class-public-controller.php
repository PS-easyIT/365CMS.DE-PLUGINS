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
        $slug = $s['archive_slug'] ?? 'feeds';

        // Hauptarchiv: /feeds
        $router->addRoute('GET', '/' . $slug, [$this, 'route_archive']);

        // Kategorieseite: /feeds/{category-slug}
        $router->addRoute('GET', '/' . $slug . '/:catSlug', [$this, 'route_category']);
    }

    /**
     * Router-Callback: Hauptarchiv.
     */
    public function route_archive(): void
    {
        $this->render_archive();
    }

    /**
     * Router-Callback: Bereichsseite.
     */
    public function route_category(string $catSlug = ''): void
    {
        $db       = CMS_Feed_Database::instance();
        $category = $db->get_category_by_slug($catSlug);

        if ($category && (int) $category['is_public']) {
            $this->render_category($category);
        } else {
            http_response_code(404);
            \CMS\ThemeManager::instance()->render('404');
        }
    }

    /**
     * Hauptarchiv rendern (alle Kategorien).
     */
    private function render_archive(): void
    {
        $db         = CMS_Feed_Database::instance();
        $s          = $db->get_settings();
        $categories = $db->get_public_categories();

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(4, min(100, (int) ($s['per_page'] ?? 20)));
        $offset  = ($page - 1) * $perPage;
        $search  = sanitize_text_field($_GET['q'] ?? '');

        $filters = [];
        if ($search) {
            $filters['search'] = $search;
        }

        $total = $db->count_items($filters);
        $pages = (int) ceil($total / $perPage);
        $items = $db->get_items($filters, $offset, $perPage);

        $tpl = CMS_Feed_Template_Loader::instance();
        $tpl->render_template('archive-feed', [
            'settings'   => $s,
            'categories' => $categories,
            'items'      => $items,
            'search'     => $search,
            'page'       => $page,
            'pages'      => $pages,
            'total'      => $total,
        ]);
    }

    /**
     * Kategorieseite rendern.
     */
    private function render_category(array $category): void
    {
        $db = CMS_Feed_Database::instance();
        $s  = $db->get_settings();

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(4, min(100, (int) ($category['items_per_page'] ?: ($s['per_page'] ?? 20))));
        $offset  = ($page - 1) * $perPage;
        $search  = sanitize_text_field($_GET['q'] ?? '');

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
        ]);
    }
}
