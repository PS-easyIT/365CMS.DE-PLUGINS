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
    public static function render_dashboard(): void
    {
        self::render_with_layout('Promos', static function (): void {
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
        self::render_with_layout('Promos verwalten', static function (): void {
            $repository = CMS_Promos_Repository::instance();
            $promos = $repository->get_promos();
            $promo = isset($_GET['edit']) ? $repository->get_promo((int) $_GET['edit']) : null;
            $placements = $repository->get_placements();
            $csrfToken = Security::instance()->generateToken('promos_admin');
            $notice = self::pull_notice();

            include CMS_PROMOS_PLUGIN_DIR . 'admin/views/page-promos.php';
        });
    }

    public static function render_placements(): void
    {
        self::render_with_layout('Promo-Platzierungen', static function (): void {
            $repository = CMS_Promos_Repository::instance();
            $placements = $repository->get_placements();
            $placement = isset($_GET['edit']) ? $repository->get_placement((int) $_GET['edit']) : null;
            $hookOptions = $repository->get_theme_hook_options();
            $csrfToken = Security::instance()->generateToken('promos_admin');
            $notice = self::pull_notice();

            include CMS_PROMOS_PLUGIN_DIR . 'admin/views/page-placements.php';
        });
    }

    public static function render_settings(): void
    {
        self::render_with_layout('Promo-Einstellungen', static function (): void {
            $repository = CMS_Promos_Repository::instance();
            $settings = $repository->get_settings();
            $csrfToken = Security::instance()->generateToken('promos_admin');
            $notice = self::pull_notice();

            include CMS_PROMOS_PLUGIN_DIR . 'admin/views/page-settings.php';
        });
    }

    private static function render_with_layout(string $title, callable $renderer): void
    {
        self::check_access();
        self::handle_post();
        self::load_admin_menu();

        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, 'promos-dashboard');
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

        if (!Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'promos_admin')) {
            self::store_notice(false, 'Sicherheitscheck fehlgeschlagen.');
            self::redirect_back();
        }

        $repository = CMS_Promos_Repository::instance();
        $action = (string) ($_POST['action'] ?? '');
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
            header('Location: ' . SITE_URL, true, 303);
            exit;
        }
    }

    private static function store_notice(bool $success, string $message): void
    {
        $_SESSION['promos_admin_notice'] = ['type' => $success ? 'success' : 'error', 'message' => $message];
    }

    private static function pull_notice(): ?array
    {
        if (empty($_SESSION['promos_admin_notice']) || !is_array($_SESSION['promos_admin_notice'])) {
            return null;
        }
        $notice = $_SESSION['promos_admin_notice'];
        unset($_SESSION['promos_admin_notice']);
        return $notice;
    }

    private static function redirect_back(): void
    {
        header('Location: ' . SITE_URL . '/admin/plugins/promos-dashboard/promos-dashboard', true, 303);
        exit;
    }
}
