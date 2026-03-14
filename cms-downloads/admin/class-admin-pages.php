<?php
/**
 * @package CMS_Downloads
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

final class CMS_Downloads_Admin_Pages
{
    public static function render_dashboard(): void
    {
        self::render_with_layout('Downloads', static function (): void {
            $repo = CMS_Downloads_Repository::instance();
            $stats = $repo->get_dashboard_stats();
            $downloads = array_slice($repo->get_downloads(), 0, 8);
            $categories = $repo->get_categories();
            $csrfToken = Security::instance()->generateToken('downloads_admin');
            $notice = self::pull_notice();

            include CMS_DOWNLOADS_PLUGIN_DIR . 'admin/views/page-dashboard.php';
        });
    }

    public static function render_downloads(): void
    {
        self::render_with_layout('Downloads', static function (): void {
            $repo = CMS_Downloads_Repository::instance();
            $downloads = $repo->get_downloads();
            $categories = $repo->get_categories();
            $download = isset($_GET['edit']) ? $repo->get_download((int) $_GET['edit']) : null;
            $typeTemplates = $repo->get_type_templates();
            $csrfToken = Security::instance()->generateToken('downloads_admin');
            $notice = self::pull_notice();

            include CMS_DOWNLOADS_PLUGIN_DIR . 'admin/views/page-downloads.php';
        });
    }

    public static function render_categories(): void
    {
        self::render_with_layout('Download-Kategorien', static function (): void {
            $repo = CMS_Downloads_Repository::instance();
            $categories = $repo->get_categories();
            $category = isset($_GET['edit']) ? $repo->get_category_by_id((int) $_GET['edit']) : null;
            $csrfToken = Security::instance()->generateToken('downloads_admin');
            $notice = self::pull_notice();

            include CMS_DOWNLOADS_PLUGIN_DIR . 'admin/views/page-categories.php';
        });
    }

    public static function render_settings(): void
    {
        self::render_with_layout('Download-Einstellungen', static function (): void {
            $repo = CMS_Downloads_Repository::instance();
            $settings = $repo->get_settings();
            $csrfToken = Security::instance()->generateToken('downloads_admin');
            $notice = self::pull_notice();

            include CMS_DOWNLOADS_PLUGIN_DIR . 'admin/views/page-settings.php';
        });
    }

    private static function render_with_layout(string $title, callable $renderer): void
    {
        self::check_access();
        self::handle_post();
        self::load_admin_menu();

        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, 'downloads-dashboard');
        }

        self::enqueue_admin_assets();
        $renderer();

        if (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
    }

    private static function handle_post(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'downloads_admin')) {
            self::store_notice(false, 'Sicherheitscheck fehlgeschlagen.');
            self::redirect_back();
        }

        $repo = CMS_Downloads_Repository::instance();
        $action = (string) ($_POST['action'] ?? '');

        $result = match ($action) {
            'save_download' => $repo->save_download($_POST, $_FILES),
            'delete_download' => $repo->delete_download((int) ($_POST['download_id'] ?? 0)),
            'save_category' => $repo->save_category($_POST),
            'delete_category' => $repo->delete_category((int) ($_POST['category_id'] ?? 0)),
            'save_settings' => $repo->save_settings($_POST),
            default => ['success' => false, 'error' => 'Unbekannte Aktion.'],
        };

        self::store_notice((bool) ($result['success'] ?? false), (string) ($result['message'] ?? $result['error'] ?? '')); 
        self::redirect_back();
    }

    private static function enqueue_admin_assets(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $css = CMS_DOWNLOADS_PLUGIN_DIR . 'assets/css/downloads-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_DOWNLOADS_PLUGIN_URL . 'assets/css/downloads-admin.css?v=' . filemtime($css), ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
    }

    private static function load_admin_menu(): void
    {
        $menuFile = ABSPATH . 'admin/partials/admin-menu.php';
        if (file_exists($menuFile) && !function_exists('renderAdminLayoutStart')) {
            require_once $menuFile;
        }
    }

    private static function check_access(): void
    {
        if (!Auth::instance()->isAdmin()) {
            header('Location: ' . SITE_URL);
            exit;
        }
    }

    private static function store_notice(bool $success, string $message): void
    {
        $_SESSION['downloads_admin_notice'] = [
            'type' => $success ? 'success' : 'error',
            'message' => $message,
        ];
    }

    private static function pull_notice(): ?array
    {
        if (empty($_SESSION['downloads_admin_notice']) || !is_array($_SESSION['downloads_admin_notice'])) {
            return null;
        }

        $notice = $_SESSION['downloads_admin_notice'];
        unset($_SESSION['downloads_admin_notice']);

        return $notice;
    }

    private static function redirect_back(): void
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $url = SITE_URL . ($path !== null && $path !== false && $path !== '' ? $path : '/admin/plugins/downloads-dashboard/downloads-dashboard');

        if (!empty($_POST['_edit_id'])) {
            $url .= '?edit=' . (int) $_POST['_edit_id'];
        }

        header('Location: ' . $url);
        exit;
    }
}
