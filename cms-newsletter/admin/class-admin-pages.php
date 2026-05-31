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
    private const MAIN_SLUG = 'newsletter-dashboard';
    private const ADMIN_CAPABILITY = 'manage_options';
    /** @var string[] */
    private const ALLOWED_ACTIONS = [
        'save_subscriber',
        'delete_subscriber',
        'save_template',
        'delete_template',
        'save_campaign',
        'delete_campaign',
        'save_settings',
    ];

    /** @var array<string, string> */
    private const SETTINGS_SECTION_MAP = [
        'newsletter-settings-general' => 'general',
        'newsletter-settings-content' => 'content',
        'newsletter-settings-compliance' => 'compliance',
    ];

    public static function render_dashboard(): void
    {
        self::render_with_layout('Newsletter', 'newsletter-dashboard', static function (): void {
            $repository = CMS_Newsletter_Repository::instance();
            $stats = $repository->get_dashboard_stats();
            $subscribers = $repository->get_recent_subscribers();
            $campaigns = $repository->get_recent_campaigns();
            $settings = $repository->get_settings();
            $notice = self::pull_notice();
            $activeSlug = self::resolve_current_slug(self::MAIN_SLUG);

            include CMS_NEWSLETTER_PLUGIN_DIR . 'admin/views/page-dashboard.php';
        });
    }

    public static function render_subscribers(): void
    {
        self::render_with_layout('Newsletter-Abonnenten', 'newsletter-subscribers', static function (): void {
            $repository = CMS_Newsletter_Repository::instance();
            $subscribers = $repository->get_subscribers();
            $subscriber = self::read_positive_int($_GET['edit'] ?? null) > 0
                ? $repository->get_subscriber(self::read_positive_int($_GET['edit'] ?? null))
                : null;
            $stats = $repository->get_dashboard_stats();
            $csrfToken = Security::instance()->generateToken('newsletter_admin');
            $notice = self::pull_notice();
            $activeSlug = self::resolve_current_slug('newsletter-subscribers');

            include CMS_NEWSLETTER_PLUGIN_DIR . 'admin/views/page-subscribers.php';
        });
    }

    public static function render_templates(): void
    {
        self::render_with_layout('Newsletter-Templates', 'newsletter-templates', static function (): void {
            $repository = CMS_Newsletter_Repository::instance();
            $templates = $repository->get_templates();
            $template = self::read_positive_int($_GET['edit'] ?? null) > 0
                ? $repository->get_template(self::read_positive_int($_GET['edit'] ?? null))
                : null;
            $csrfToken = Security::instance()->generateToken('newsletter_admin');
            $notice = self::pull_notice();
            $activeSlug = self::resolve_current_slug('newsletter-templates');

            include CMS_NEWSLETTER_PLUGIN_DIR . 'admin/views/page-templates.php';
        });
    }

    public static function render_campaigns(): void
    {
        self::render_with_layout('Newsletter-Kampagnen', 'newsletter-campaigns', static function (): void {
            $repository = CMS_Newsletter_Repository::instance();
            $campaigns = $repository->get_campaigns();
            $campaign = self::read_positive_int($_GET['edit'] ?? null) > 0
                ? $repository->get_campaign(self::read_positive_int($_GET['edit'] ?? null))
                : null;
            $templates = $repository->get_templates();
            $csrfToken = Security::instance()->generateToken('newsletter_admin');
            $notice = self::pull_notice();
            $activeSlug = self::resolve_current_slug('newsletter-campaigns');

            include CMS_NEWSLETTER_PLUGIN_DIR . 'admin/views/page-campaigns.php';
        });
    }

    public static function render_settings_general(): void
    {
        self::render_settings_page('newsletter-settings-general');
    }

    public static function render_settings_content(): void
    {
        self::render_settings_page('newsletter-settings-content');
    }

    public static function render_settings_compliance(): void
    {
        self::render_settings_page('newsletter-settings-compliance');
    }

    public static function render_settings(): void
    {
        self::render_settings_page('newsletter-settings-general');
    }

    private static function render_settings_page(string $defaultSlug): void
    {
        self::render_with_layout('Newsletter-Einstellungen', $defaultSlug, static function () use ($defaultSlug): void {
            $repository = CMS_Newsletter_Repository::instance();
            $settings = $repository->get_settings();
            $activeSlug = self::resolve_current_slug($defaultSlug);
            $tab = self::SETTINGS_SECTION_MAP[$activeSlug] ?? self::SETTINGS_SECTION_MAP[$defaultSlug] ?? 'general';
            $csrfToken = Security::instance()->generateToken('newsletter_admin');
            $notice = self::pull_notice();

            include CMS_NEWSLETTER_PLUGIN_DIR . 'admin/views/page-settings.php';
        });
    }

    private static function render_with_layout(string $title, string $defaultSlug, callable $renderer): void
    {
        self::check_access();
        self::handle_post();
        self::load_shared_contract();

        $activeSlug = self::resolve_current_slug($defaultSlug);
        if (function_exists('cms_plugin_admin_layout_start')) {
            cms_plugin_admin_layout_start($title, $activeSlug);
        } else {
            self::load_admin_menu();
            if (function_exists('renderAdminLayoutStart')) {
                renderAdminLayoutStart($title, self::MAIN_SLUG);
            }
        }

        self::enqueue_admin_assets();
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

        if (!Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'newsletter_admin')) {
            self::store_notice(false, 'Sicherheitscheck fehlgeschlagen.');
            self::redirect_back(self::resolve_redirect_slug());
        }

        $action = (string) ($_POST['action'] ?? '');
        if (!in_array($action, self::ALLOWED_ACTIONS, true)) {
            self::store_notice(false, 'Unbekannte Aktion.');
            self::redirect_back(self::resolve_redirect_slug());
        }

        $repository = CMS_Newsletter_Repository::instance();
        try {
            $result = match ($action) {
                'save_subscriber' => $repository->save_subscriber(self::extract_subscriber_payload($_POST)),
                'delete_subscriber' => $repository->delete_subscriber(self::read_positive_int($_POST['subscriber_id'] ?? null)),
                'save_template' => $repository->save_template(self::extract_template_payload($_POST)),
                'delete_template' => $repository->delete_template(self::read_positive_int($_POST['template_id'] ?? null)),
                'save_campaign' => $repository->save_campaign(self::extract_campaign_payload($_POST)),
                'delete_campaign' => $repository->delete_campaign(self::read_positive_int($_POST['campaign_id'] ?? null)),
                'save_settings' => $repository->save_settings(self::extract_settings_payload($_POST)),
                default => ['success' => false, 'error' => 'Unbekannte Aktion.'],
            };
        } catch (\Throwable $exception) {
            self::log_error('admin_action_failed', [
                'action' => $action,
                'message' => $exception->getMessage(),
            ]);
            $result = ['success' => false, 'error' => 'Aktion konnte nicht gespeichert werden.'];
        }

        self::store_notice((bool) ($result['success'] ?? false), (string) ($result['message'] ?? $result['error'] ?? ''));
        self::redirect_back(self::resolve_redirect_slug());
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
        $resolved = realpath($menuFile);
        if ($resolved === false || !is_file($resolved) || !is_readable($resolved) || function_exists('renderAdminLayoutStart')) {
            return;
        }

        $adminRoot = realpath(ABSPATH . 'admin');
        if ($adminRoot === false || !str_starts_with($resolved, rtrim($adminRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
            return;
        }

        require_once $resolved;
    }

    private static function load_shared_contract(): void
    {
        $contractFile = dirname(__DIR__) . '/../shared/admin/plugin-admin-contract.php';
        $resolved = realpath($contractFile);
        if ($resolved !== false && is_file($resolved) && is_readable($resolved)) {
            require_once $resolved;
        }
    }

    private static function check_access(): void
    {
        $auth = Auth::instance();
        if (!$auth->isAdmin() || !self::has_required_capability($auth)) {
            header('Location: ' . SITE_URL, true, 303);
            exit;
        }
    }

    private static function store_notice(bool $success, string $message): void
    {
        self::ensure_session_started();
        $message = trim($message);
        if ($message === '') {
            $message = $success ? 'Änderung gespeichert.' : 'Aktion fehlgeschlagen.';
        }
        $_SESSION['newsletter_admin_notice'] = [
            'type' => $success ? 'success' : 'error',
            'message' => $message,
        ];
    }

    private static function pull_notice(): ?array
    {
        self::ensure_session_started();
        if (empty($_SESSION['newsletter_admin_notice']) || !is_array($_SESSION['newsletter_admin_notice'])) {
            return null;
        }

        $notice = $_SESSION['newsletter_admin_notice'];
        unset($_SESSION['newsletter_admin_notice']);

        return $notice;
    }

    private static function redirect_back(string $slug): void
    {
        $allowedSlugs = [
            'newsletter-dashboard',
            'newsletter-subscribers',
            'newsletter-templates',
            'newsletter-campaigns',
            'newsletter-settings-general',
            'newsletter-settings-content',
            'newsletter-settings-compliance',
        ];

        if (!in_array($slug, $allowedSlugs, true)) {
            $slug = self::MAIN_SLUG;
        }

        $siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
        header('Location: ' . $siteUrl . '/admin/plugins/newsletter-dashboard/' . rawurlencode($slug), true, 303);
        exit;
    }

    private static function resolve_redirect_slug(): string
    {
        $postSlug = self::sanitize_slug((string) ($_POST['redirect_slug'] ?? ''));
        if ($postSlug !== '') {
            return $postSlug;
        }

        return self::resolve_current_slug(self::MAIN_SLUG);
    }

    private static function resolve_current_slug(string $fallback): string
    {
        if (function_exists('cms_plugin_admin_active_slug')) {
            return cms_plugin_admin_active_slug($fallback);
        }

        $requested = (string) ($_GET['page'] ?? $fallback);
        $requested = self::sanitize_slug($requested);
        $fallback = self::sanitize_slug($fallback);
        return $requested !== '' ? $requested : $fallback;
    }

    private static function sanitize_slug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = (string) preg_replace('/[^a-z0-9_-]+/', '-', $slug);
        return trim($slug, '-');
    }

    private static function ensure_session_started(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }

    private static function has_required_capability(object $auth): bool
    {
        $capability = self::ADMIN_CAPABILITY;
        if (method_exists($auth, 'hasPermission')) {
            return (bool) $auth->hasPermission($capability);
        }
        if (method_exists($auth, 'can')) {
            return (bool) $auth->can($capability);
        }
        if (method_exists($auth, 'hasCapability')) {
            return (bool) $auth->hasCapability($capability);
        }

        // Fallback for installations where admin role implies all capabilities.
        return true;
    }

    private static function read_positive_int(mixed $value): int
    {
        return max(0, (int) $value);
    }

    private static function extract_subscriber_payload(array $post): array
    {
        return [
            'subscriber_id' => self::read_positive_int($post['subscriber_id'] ?? null),
            'email' => (string) ($post['email'] ?? ''),
            'first_name' => (string) ($post['first_name'] ?? ''),
            'last_name' => (string) ($post['last_name'] ?? ''),
            'status' => (string) ($post['status'] ?? ''),
            'source' => (string) ($post['source'] ?? 'admin'),
            'segment_slug' => (string) ($post['segment_slug'] ?? ''),
        ];
    }

    private static function extract_template_payload(array $post): array
    {
        return [
            'template_id' => self::read_positive_int($post['template_id'] ?? null),
            'name' => (string) ($post['name'] ?? ''),
            'subject' => (string) ($post['subject'] ?? ''),
            'content_html' => (string) ($post['content_html'] ?? ''),
            'content_text' => (string) ($post['content_text'] ?? ''),
            'status' => (string) ($post['status'] ?? ''),
        ];
    }

    private static function extract_campaign_payload(array $post): array
    {
        return [
            'campaign_id' => self::read_positive_int($post['campaign_id'] ?? null),
            'template_id' => self::read_positive_int($post['template_id'] ?? null),
            'name' => (string) ($post['name'] ?? ''),
            'subject' => (string) ($post['subject'] ?? ''),
            'preview_text' => (string) ($post['preview_text'] ?? ''),
            'segment_slug' => (string) ($post['segment_slug'] ?? ''),
            'status' => (string) ($post['status'] ?? ''),
            'scheduled_at' => (string) ($post['scheduled_at'] ?? ''),
        ];
    }

    private static function extract_settings_payload(array $post): array
    {
        return [
            'sender_name' => (string) ($post['sender_name'] ?? ''),
            'sender_email' => (string) ($post['sender_email'] ?? ''),
            'reply_to_email' => (string) ($post['reply_to_email'] ?? ''),
            'require_double_opt_in' => !empty($post['require_double_opt_in']) ? '1' : '0',
            'default_segment' => (string) ($post['default_segment'] ?? ''),
            'archive_title' => (string) ($post['archive_title'] ?? ''),
            'archive_description' => (string) ($post['archive_description'] ?? ''),
            'subscribe_intro' => (string) ($post['subscribe_intro'] ?? ''),
            'footer_note' => (string) ($post['footer_note'] ?? ''),
        ];
    }

    private static function log_error(string $event, array $context = []): void
    {
        $payload = $context !== [] ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        error_log('[cms-newsletter] ' . $event . $payload);
    }
}
