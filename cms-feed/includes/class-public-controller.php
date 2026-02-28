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
        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::addAction('cms_frontend_route', [$this, 'handle_routes'], 10);
        }
    }

    /**
     * Routen behandeln.
     */
    public function handle_routes(): void
    {
        $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '', '/');
        $db   = CMS_Feed_Database::instance();
        $s    = $db->get_settings();
        $slug = $s['archive_slug'] ?? 'feeds';

        // Hauptarchiv: /feeds
        if ($path === $slug) {
            $this->render_archive();
            exit;
        }

        // Kategorieseite: /feeds/{category-slug}
        if (str_starts_with($path, $slug . '/')) {
            $catSlug = substr($path, strlen($slug) + 1);
            $catSlug = strtok($catSlug, '/'); // Nur erstes Segment
            if ($catSlug) {
                $category = $db->get_category_by_slug($catSlug);
                if ($category && (int) $category['is_public']) {
                    $this->render_category($category);
                    exit;
                }
            }
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
