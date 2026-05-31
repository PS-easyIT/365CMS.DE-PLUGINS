<?php
/**
 * @package CMS_Promos
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

final class CMS_Promos_Admin_Pages
{
    private const ROOT_SLUG = 'promos-dashboard';
    private const DEFAULT_PAGE = 'promos-dashboard';
    /** @var array<string,string> */
    private const NAV_ITEMS = [
        'promos-dashboard' => 'Dashboard',
        'promos-items' => 'Promos',
        'promos-placements' => 'Platzierungen',
        'promos-settings' => 'Einstellungen',
    ];

    public static function render_page_dispatcher(): void
    {
        self::require_shared_contract();
        self::queue_unknown_page_notice();

        $callbacks = [
            'promos-dashboard' => [self::class, 'render_dashboard'],
            'promos-items' => [self::class, 'render_promos'],
            'promos-placements' => [self::class, 'render_placements'],
            'promos-settings' => [self::class, 'render_settings'],
        ];

        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbacks, self::DEFAULT_PAGE, self::ROOT_SLUG);
            return;
        }

        $requested = (string) ($_GET['page'] ?? self::DEFAULT_PAGE);
        $slug = self::normalize_page_slug($requested);
        $resolved = array_key_exists($slug, $callbacks) ? $slug : self::DEFAULT_PAGE;
        $callback = $callbacks[$resolved] ?? null;
        if (is_callable($callback)) {
            call_user_func($callback);
        }
    }

    public static function render_dashboard(): void
    {
        self::render_with_layout('Promos', 'promos-dashboard', static function (): void {
            $repository = CMS_Promos_Repository::instance();
            $stats = $repository->get_dashboard_stats();
            $promos = array_slice($repository->get_promos(), 0, 6);
            $placements = $repository->get_placements();
            $settings = $repository->get_settings();
            $notice = self::pull_notice();

            include CMS_PROMOS_PLUGIN_DIR . 'admin/views/page-dashboard.php';
        });
    }

    public static function render_promos(): void
    {
        self::render_with_layout('Promos verwalten', 'promos-items', static function (): void {
            $repository = CMS_Promos_Repository::instance();
            $promos = $repository->get_promos();
            $editId = self::get_request_int($_GET, 'edit');
            $promo = $editId > 0 ? $repository->get_promo($editId) : null;
            $placements = $repository->get_placements();
            $csrfToken = Security::instance()->generateToken('promos_admin');
            $notice = self::pull_notice();

            include CMS_PROMOS_PLUGIN_DIR . 'admin/views/page-promos.php';
        });
    }

    public static function render_placements(): void
    {
        self::render_with_layout('Promo-Platzierungen', 'promos-placements', static function (): void {
            $repository = CMS_Promos_Repository::instance();
            $placements = $repository->get_placements();
            $editId = self::get_request_int($_GET, 'edit');
            $placement = $editId > 0 ? $repository->get_placement($editId) : null;
            $hookOptions = $repository->get_theme_hook_options();
            $csrfToken = Security::instance()->generateToken('promos_admin');
            $notice = self::pull_notice();

            include CMS_PROMOS_PLUGIN_DIR . 'admin/views/page-placements.php';
        });
    }

    public static function render_settings(): void
    {
        self::render_with_layout('Promo-Einstellungen', 'promos-settings', static function (): void {
            $repository = CMS_Promos_Repository::instance();
            $settings = $repository->get_settings();
            $csrfToken = Security::instance()->generateToken('promos_admin');
            $notice = self::pull_notice();

            include CMS_PROMOS_PLUGIN_DIR . 'admin/views/page-settings.php';
        });
    }

    private static function render_with_layout(string $title, string $activeSlug, callable $renderer): void
    {
        self::check_access();
        self::handle_post();
        self::require_shared_contract();

        if (function_exists('cms_plugin_admin_layout_start')) {
            cms_plugin_admin_layout_start($title, self::ROOT_SLUG);
        } elseif (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, self::ROOT_SLUG);
        }

        self::enqueue_admin_assets();
        echo '<div class="pr-admin-layout">';
        self::render_sidebar_menu($activeSlug);
        echo '<section class="pr-admin-layout__content">';
        $renderer();
        echo '</section>';
        echo '</div>';

        if (function_exists('cms_plugin_admin_layout_end')) {
            cms_plugin_admin_layout_end();
        } elseif (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
    }

    private static function handle_post(): void
    {
        self::check_access();

        $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($requestMethod !== 'POST') {
            return;
        }

        if (!Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'promos_admin')) {
            self::store_notice(false, 'Sicherheitscheck fehlgeschlagen.');
            self::redirect_back();
        }

        $repository = CMS_Promos_Repository::instance();
        $action = self::normalize_action((string) ($_POST['action'] ?? ''));
        $result = match ($action) {
            'save_promo' => $repository->save_promo($_POST),
            'delete_promo' => $repository->delete_promo((int) ($_POST['promo_id'] ?? 0)),
            'save_placement' => $repository->save_placement($_POST),
            'delete_placement' => $repository->delete_placement((int) ($_POST['placement_id'] ?? 0)),
            'save_settings' => $repository->save_settings($_POST),
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

        $css = CMS_PROMOS_PLUGIN_DIR . 'assets/css/promos-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_PROMOS_PLUGIN_URL . 'assets/css/promos-admin.css?v=' . filemtime($css), ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
    }

    private static function require_shared_contract(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $sharedContract = dirname(CMS_PROMOS_PLUGIN_DIR) . '/shared/admin/plugin-admin-contract.php';
        $sharedContractPath = realpath($sharedContract);
        $pluginBasePath = realpath(dirname(CMS_PROMOS_PLUGIN_DIR));
        $sharedContractNormalized = $sharedContractPath !== false ? str_replace('\\', '/', $sharedContractPath) : '';
        $pluginBaseNormalized = $pluginBasePath !== false ? rtrim(str_replace('\\', '/', $pluginBasePath), '/') . '/' : '';
        if ($sharedContractPath !== false && $pluginBasePath !== false && str_starts_with($sharedContractNormalized, $pluginBaseNormalized) && is_file($sharedContractPath)) {
            require_once $sharedContractPath;
            return;
        }

        $menuFile = ABSPATH . 'admin/partials/admin-menu.php';
        if (is_file($menuFile) && !function_exists('renderAdminLayoutStart')) {
            require_once $menuFile;
        }
    }

    private static function check_access(): void
    {
        if (!Auth::instance()->isAdmin()) {
            header('Location: ' . SITE_URL, true, 303);
            exit;
        }
    }

    private static function store_notice(bool $success, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION['promos_admin_notice'] = [
            'type' => $success ? 'success' : 'error',
            'message' => $message,
        ];
    }

    private static function pull_notice(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        if (empty($_SESSION['promos_admin_notice']) || !is_array($_SESSION['promos_admin_notice'])) {
            return null;
        }
        $notice = $_SESSION['promos_admin_notice'];
        unset($_SESSION['promos_admin_notice']);
        return $notice;
    }

    private static function redirect_back(): void
    {
        $page = self::get_current_page_slug();
        $target = SITE_URL . '/admin/plugins/' . self::ROOT_SLUG . '/' . rawurlencode($page);

        $edit = self::get_request_int($_POST, 'promo_id');
        if ($edit <= 0) {
            $edit = self::get_request_int($_POST, 'placement_id');
        }
        if ($edit <= 0) {
            $edit = self::get_request_int($_GET, 'edit');
        }
        if ($edit > 0) {
            $target .= '?edit=' . $edit;
        }

        header('Location: ' . $target, true, 303);
        exit;
    }

    private static function render_sidebar_menu(string $activeSlug): void
    {
        $activeSlug = self::normalize_page_slug($activeSlug);

        echo '<aside class="pr-admin-sidebar" aria-label="Promos-Untermenue">';
        echo '<nav class="pr-admin-submenu">';
        echo '<h2 class="pr-admin-submenu__title">Promos</h2>';
        echo '<ul class="pr-admin-submenu__list">';

        foreach (self::NAV_ITEMS as $slug => $label) {
            $itemClass = $slug === $activeSlug ? 'pr-admin-submenu__link is-active' : 'pr-admin-submenu__link';
            echo '<li class="pr-admin-submenu__item">';
            echo '<a class="' . $itemClass . '" href="' . htmlspecialchars(self::admin_page_url($slug), ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                . '</a>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</nav>';
        echo '</aside>';
    }

    private static function admin_page_url(string $slug): string
    {
        return SITE_URL . '/admin/plugins/' . self::ROOT_SLUG . '/' . rawurlencode(self::normalize_page_slug($slug));
    }

    private static function normalize_page_slug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = (string) preg_replace('/[^a-z0-9_-]+/', '-', $slug);
        $slug = trim($slug, '-');

        return array_key_exists($slug, self::NAV_ITEMS) ? $slug : self::DEFAULT_PAGE;
    }

    private static function get_current_page_slug(): string
    {
        return self::normalize_page_slug((string) ($_GET['page'] ?? self::DEFAULT_PAGE));
    }

    private static function normalize_action(string $action): string
    {
        $action = strtolower(trim($action));
        $action = (string) preg_replace('/[^a-z0-9_]+/', '', $action);
        return $action;
    }

    private static function get_request_int(array $source, string $key): int
    {
        if (!array_key_exists($key, $source)) {
            return 0;
        }

        return max(0, (int) $source[$key]);
    }

    private static function queue_unknown_page_notice(): void
    {
        $requested = (string) ($_GET['page'] ?? self::DEFAULT_PAGE);
        if ($requested === '' || $requested === self::DEFAULT_PAGE) {
            return;
        }

        $normalized = function_exists('cms_plugin_admin_normalize_slug')
            ? cms_plugin_admin_normalize_slug($requested)
            : self::normalize_page_slug($requested);

        if (!array_key_exists($normalized, self::NAV_ITEMS)) {
            self::store_notice(false, 'Unbekannte Admin-Unterseite, Dashboard wird angezeigt.');
        }
    }
}
