<?php
/**
 * @package CMS_Newsletter
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

final class CMS_Newsletter_Admin_Pages
{
    public static function render_dashboard(): void
    {
        self::render_with_layout('Newsletter', static function (): void {
            $repository = CMS_Newsletter_Repository::instance();
            $stats = $repository->get_dashboard_stats();
            $subscribers = $repository->get_recent_subscribers();
            $campaigns = $repository->get_recent_campaigns();
            $settings = $repository->get_settings();
            $notice = self::pull_notice();

            include CMS_NEWSLETTER_PLUGIN_DIR . 'admin/views/page-dashboard.php';
        });
    }

    public static function render_subscribers(): void
    {
        self::render_with_layout('Newsletter-Abonnenten', static function (): void {
            $repository = CMS_Newsletter_Repository::instance();
            $subscribers = $repository->get_subscribers();
            $subscriber = isset($_GET['edit']) ? $repository->get_subscriber((int) $_GET['edit']) : null;
            $stats = $repository->get_dashboard_stats();
            $csrfToken = Security::instance()->generateToken('newsletter_admin');
            $notice = self::pull_notice();

            include CMS_NEWSLETTER_PLUGIN_DIR . 'admin/views/page-subscribers.php';
        });
    }

    public static function render_templates(): void
    {
        self::render_with_layout('Newsletter-Templates', static function (): void {
            $repository = CMS_Newsletter_Repository::instance();
            $templates = $repository->get_templates();
            $template = isset($_GET['edit']) ? $repository->get_template((int) $_GET['edit']) : null;
            $csrfToken = Security::instance()->generateToken('newsletter_admin');
            $notice = self::pull_notice();

            include CMS_NEWSLETTER_PLUGIN_DIR . 'admin/views/page-templates.php';
        });
    }

    public static function render_campaigns(): void
    {
        self::render_with_layout('Newsletter-Kampagnen', static function (): void {
            $repository = CMS_Newsletter_Repository::instance();
            $campaigns = $repository->get_campaigns();
            $campaign = isset($_GET['edit']) ? $repository->get_campaign((int) $_GET['edit']) : null;
            $templates = $repository->get_templates();
            $csrfToken = Security::instance()->generateToken('newsletter_admin');
            $notice = self::pull_notice();

            include CMS_NEWSLETTER_PLUGIN_DIR . 'admin/views/page-campaigns.php';
        });
    }

    public static function render_settings(): void
    {
        self::render_with_layout('Newsletter-Einstellungen', static function (): void {
            $repository = CMS_Newsletter_Repository::instance();
            $settings = $repository->get_settings();
            $tab = (string) ($_GET['tab'] ?? 'general');
            $csrfToken = Security::instance()->generateToken('newsletter_admin');
            $notice = self::pull_notice();

            include CMS_NEWSLETTER_PLUGIN_DIR . 'admin/views/page-settings.php';
        });
    }

    private static function render_with_layout(string $title, callable $renderer): void
    {
        self::check_access();
        self::handle_post();
        self::load_admin_menu();

        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, 'newsletter-dashboard');
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

        if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'newsletter_admin')) {
            self::store_notice(false, 'Sicherheitscheck fehlgeschlagen.');
            self::redirect_back();
        }

        $repository = CMS_Newsletter_Repository::instance();
        $action = (string) ($_POST['action'] ?? '');
        $result = match ($action) {
            'save_subscriber' => $repository->save_subscriber($_POST),
            'delete_subscriber' => $repository->delete_subscriber((int) ($_POST['subscriber_id'] ?? 0)),
            'save_template' => $repository->save_template($_POST),
            'delete_template' => $repository->delete_template((int) ($_POST['template_id'] ?? 0)),
            'save_campaign' => $repository->save_campaign($_POST),
            'delete_campaign' => $repository->delete_campaign((int) ($_POST['campaign_id'] ?? 0)),
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

        $css = CMS_NEWSLETTER_PLUGIN_DIR . 'assets/css/newsletter-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_NEWSLETTER_PLUGIN_URL . 'assets/css/newsletter-admin.css?v=' . filemtime($css), ENT_QUOTES, 'UTF-8') . '">' . "\n";
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
        $_SESSION['newsletter_admin_notice'] = [
            'type' => $success ? 'success' : 'error',
            'message' => $message,
        ];
    }

    private static function pull_notice(): ?array
    {
        if (empty($_SESSION['newsletter_admin_notice']) || !is_array($_SESSION['newsletter_admin_notice'])) {
            return null;
        }

        $notice = $_SESSION['newsletter_admin_notice'];
        unset($_SESSION['newsletter_admin_notice']);

        return $notice;
    }

    private static function redirect_back(): void
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $url = SITE_URL . ($path !== null && $path !== false && $path !== '' ? $path : '/admin/plugins/newsletter-dashboard/newsletter-dashboard');

        if (!empty($_POST['_edit_id'])) {
            $url .= '?edit=' . (int) $_POST['_edit_id'];
        } elseif (!empty($_GET['tab'])) {
            $url .= '?tab=' . urlencode((string) $_GET['tab']);
        }

        header('Location: ' . $url);
        exit;
    }
}
