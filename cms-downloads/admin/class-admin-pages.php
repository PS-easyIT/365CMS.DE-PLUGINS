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
    public const PLUGIN_SLUG = 'downloads-dashboard';
    public const PAGE_DASHBOARD = 'downloads-dashboard';
    public const PAGE_DOWNLOADS = 'downloads-items';
    public const PAGE_CATEGORIES = 'downloads-categories';
    public const PAGE_SETTINGS = 'downloads-settings';

    /**
     * Central admin dispatcher for all plugin admin pages.
     */
    public static function dispatch(): void
    {
        self::check_access();
        self::handle_post();
        self::enqueue_admin_assets();

        $callbackMap = [
            self::PAGE_DASHBOARD => [self::class, 'render_dashboard'],
            self::PAGE_DOWNLOADS => [self::class, 'render_downloads'],
            self::PAGE_CATEGORIES => [self::class, 'render_categories'],
            self::PAGE_SETTINGS => [self::class, 'render_settings'],
        ];

        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbackMap, self::PAGE_DASHBOARD, self::PLUGIN_SLUG);
            return;
        }

        $activeSlug = self::current_page_slug();
        $resolvedSlug = array_key_exists($activeSlug, $callbackMap) ? $activeSlug : self::PAGE_DASHBOARD;
        $callback = $callbackMap[$resolvedSlug] ?? null;

        if (!is_callable($callback)) {
            self::render_dispatch_error();
            return;
        }

        call_user_func($callback);
    }

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
            $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $download = is_int($editId) && $editId > 0 ? $repo->get_download($editId) : null;
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
            $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $category = is_int($editId) && $editId > 0 ? $repo->get_category_by_id($editId) : null;
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
        $activeSlug = self::current_page_slug();
        if (function_exists('cms_plugin_admin_layout_start')) {
            cms_plugin_admin_layout_start($title, $activeSlug);
        } else {
            self::load_admin_menu();
            if (function_exists('renderAdminLayoutStart')) {
                renderAdminLayoutStart($title, $activeSlug);
            }
        }

        $renderer();

        if (function_exists('cms_plugin_admin_layout_end')) {
            cms_plugin_admin_layout_end();
        } elseif (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
    }

    private static function handle_post(): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            return;
        }

        if (!self::can_manage_plugin()) {
            self::store_notice(false, 'Keine Berechtigung für diese Aktion.');
            self::redirect_back(self::current_page_slug(), self::resolve_edit_id_from_request());
        }

        if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'downloads_admin')) {
            self::store_notice(false, 'Sicherheitscheck fehlgeschlagen.');
            self::redirect_back(self::current_page_slug(), self::resolve_edit_id_from_request());
        }

        $repo = CMS_Downloads_Repository::instance();
        $action = self::sanitize_action((string) ($_POST['action'] ?? ''));

        $result = match ($action) {
            'save_download' => $repo->save_download($_POST, $_FILES),
            'delete_download' => $repo->delete_download((int) ($_POST['download_id'] ?? 0)),
            'save_category' => $repo->save_category($_POST),
            'delete_category' => $repo->delete_category((int) ($_POST['category_id'] ?? 0)),
            'save_settings' => $repo->save_settings($_POST),
            default => ['success' => false, 'error' => 'Unbekannte Aktion.'],
        };

        self::store_notice((bool) ($result['success'] ?? false), (string) ($result['message'] ?? $result['error'] ?? '')); 
        self::redirect_back(self::current_page_slug(), self::resolve_edit_id_from_request());
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
        if (!self::can_manage_plugin()) {
            if (!headers_sent()) {
                header('Location: ' . SITE_URL);
            }
            exit;
        }
    }

    private static function store_notice(bool $success, string $message): void
    {
        $cleanMessage = trim(strip_tags($message));
        if ($cleanMessage === '') {
            $cleanMessage = $success ? 'Aktion erfolgreich ausgeführt.' : 'Die Aktion konnte nicht abgeschlossen werden.';
        }

        $_SESSION['downloads_admin_notice'] = [
            'type' => $success ? 'success' : 'error',
            'message' => $cleanMessage,
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

    private static function redirect_back(string $pageSlug, int $editId = 0): void
    {
        $pageSlug = self::sanitize_page_slug($pageSlug);
        $location = SITE_URL . '/admin/plugins/' . self::PLUGIN_SLUG . '/' . $pageSlug;

        if (
            $editId > 0
            && in_array($pageSlug, [self::PAGE_DOWNLOADS, self::PAGE_CATEGORIES], true)
        ) {
            $location .= '?edit=' . $editId;
        }

        header('Location: ' . $location);
        exit;
    }

    private static function current_page_slug(): string
    {
        $requested = (string) ($_REQUEST['page'] ?? self::PAGE_DASHBOARD);

        return self::sanitize_page_slug($requested);
    }

    private static function sanitize_page_slug(string $slug): string
    {
        if (function_exists('cms_plugin_admin_normalize_slug')) {
            $normalized = cms_plugin_admin_normalize_slug($slug);
        } else {
            $normalized = strtolower(trim($slug));
            $normalized = (string) preg_replace('/[^a-z0-9_-]+/', '-', $normalized);
            $normalized = trim($normalized, '-');
        }

        $allowed = [
            self::PAGE_DASHBOARD,
            self::PAGE_DOWNLOADS,
            self::PAGE_CATEGORIES,
            self::PAGE_SETTINGS,
        ];

        return in_array($normalized, $allowed, true) ? $normalized : self::PAGE_DASHBOARD;
    }

    private static function resolve_edit_id_from_request(): int
    {
        $editId = filter_input(INPUT_POST, '_edit_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return is_int($editId) && $editId > 0 ? $editId : 0;
    }

    private static function can_manage_plugin(): bool
    {
        if (!class_exists(Auth::class) || !method_exists(Auth::class, 'instance')) {
            return false;
        }

        $auth = Auth::instance();
        if (!is_object($auth) || !method_exists($auth, 'isAdmin') || !$auth->isAdmin()) {
            return false;
        }

        if (function_exists('current_user_can') && !current_user_can('manage_options')) {
            return false;
        }

        return true;
    }

    private static function sanitize_action(string $action): string
    {
        $action = strtolower(trim($action));
        $action = (string) preg_replace('/[^a-z_]+/', '', $action);
        return $action;
    }

    private static function render_dispatch_error(): void
    {
        if (function_exists('cms_plugin_admin_layout_start')) {
            cms_plugin_admin_layout_start('Downloads', self::PLUGIN_SLUG);
        } else {
            self::load_admin_menu();
            if (function_exists('renderAdminLayoutStart')) {
                renderAdminLayoutStart('Downloads', self::PLUGIN_SLUG);
            }
        }

        if (function_exists('cms_plugin_admin_emit_notice')) {
            cms_plugin_admin_emit_notice(
                'Die angeforderte Admin-Seite ist derzeit nicht verfuegbar. Bitte pruefen Sie die Plugin-Konfiguration.',
                'error',
                'downloads admin dispatcher fallback missing callback'
            );
        } else {
            echo '<div class="alert alert-error" role="alert">Die angeforderte Admin-Seite ist derzeit nicht verfuegbar. Bitte pruefen Sie die Plugin-Konfiguration.</div>';
        }

        if (function_exists('cms_plugin_admin_layout_end')) {
            cms_plugin_admin_layout_end();
        } elseif (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
    }
}
