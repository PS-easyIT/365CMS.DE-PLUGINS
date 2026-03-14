<?php
/**
 * @package CMS_Downloads
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Downloads_Public_Controller
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function register_routes($router): void
    {
        $router->addRoute('GET', '/downloads', [$this, 'archive_page']);
        $router->addRoute('GET', '/downloads/category/:slug', [$this, 'archive_page']);
        $router->addRoute('GET', '/downloads/file/:slug', [$this, 'download_file']);
    }

    public function render_nav_item(): void
    {
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $active = strpos($currentPath, '/downloads') === 0 ? 'active' : '';

        echo '<a href="' . SITE_URL . '/downloads" class="nav-link ' . htmlspecialchars($active, ENT_QUOTES, 'UTF-8') . '">Downloads</a>';
    }

    public function archive_page(string $slug = ''): void
    {
        $repository = CMS_Downloads_Repository::instance();
        $settings = $repository->get_settings();
        $categories = $repository->get_categories(true);
        $currentCategory = $slug !== '' ? $repository->get_category_by_slug($slug) : null;
        $search = trim((string) ($_GET['q'] ?? ''));
        $perPage = max(6, min(120, (int) ($settings['downloads_per_page'] ?? 24)));
        $currentPage = max(1, (int) ($_GET['page'] ?? 1));

        $baseFilters = [
            'status' => 'active',
            'category_slug' => $slug,
            'search' => $search,
        ];

        $totalDownloads = $repository->count_downloads($baseFilters);
        $totalPages = max(1, (int) ceil($totalDownloads / $perPage));
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }

        $downloads = $repository->get_downloads($baseFilters + [
            'limit' => $perPage,
            'offset' => ($currentPage - 1) * $perPage,
        ]);

        $typeTemplates = $repository->get_type_templates();
        $theme = class_exists('CMS\\ThemeManager') ? \CMS\ThemeManager::instance() : null;

        if ($theme !== null) {
            $theme->getHeader();
        }

        include CMS_DOWNLOADS_PLUGIN_DIR . 'templates/archive-downloads.php';

        if ($theme !== null) {
            $theme->getFooter();
        }
    }

    public function download_file(string $slug): void
    {
        $download = CMS_Downloads_Repository::instance()->get_download_by_slug($slug);
        if ($download === null) {
            http_response_code(404);
            echo '<h1>404 – Download nicht gefunden</h1>';
            return;
        }

        if ((int) ($download['requires_login'] ?? 0) === 1 && class_exists('CMS\\Auth') && !\CMS\Auth::instance()->isLoggedIn()) {
            header('Location: ' . SITE_URL . '/login');
            exit;
        }

        CMS_Downloads_Repository::instance()->increment_download_count((int) $download['id']);

        $externalUrl = trim((string) ($download['external_url'] ?? ''));
        if ($externalUrl !== '') {
            header('Location: ' . $externalUrl, true, 302);
            exit;
        }

        $relativePath = trim((string) ($download['file_path'] ?? ''), '/\\');
        if ($relativePath === '' || !defined('UPLOAD_PATH')) {
            http_response_code(404);
            echo '<h1>404 – Datei nicht verfügbar</h1>';
            return;
        }

        $absolutePath = rtrim((string) UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            http_response_code(404);
            echo '<h1>404 – Datei nicht verfügbar</h1>';
            return;
        }

        $mime = function_exists('mime_content_type') ? (string) mime_content_type($absolutePath) : 'application/octet-stream';
        $filename = (string) ($download['file_name'] ?? basename($absolutePath));

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . rawurldecode(str_replace('%2F', '-', rawurlencode($filename))) . '"');
        header('Content-Length: ' . (string) filesize($absolutePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($absolutePath);
        exit;
    }
}
