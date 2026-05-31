<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// ── Modul-Traits laden (vor der Klassen-Deklaration) ──────────────────────────
require_once __DIR__ . '/modules/trait-page-dashboard.php';
require_once __DIR__ . '/modules/trait-page-generator.php';
require_once __DIR__ . '/modules/trait-page-libraries.php';
require_once __DIR__ . '/modules/trait-page-design.php';
require_once __DIR__ . '/modules/trait-page-settings.php';
require_once __DIR__ . '/modules/trait-page-workflow.php';
require_once __DIR__ . '/modules/trait-page-approvals.php';
require_once __DIR__ . '/modules/trait-page-companies.php';
require_once __DIR__ . '/modules/trait-page-subscription.php';
require_once __DIR__ . '/modules/trait-page-users.php';
require_once __DIR__ . '/modules/trait-page-public-design.php';

/**
 * Admin-Seiten Renderer & POST-Handler
 *
 * Alle 10 Seiten sind in separaten Traits unter admin/modules/ organisiert.
 * Diese Klasse enthält nur noch die gemeinsam genutzten Basis-Methoden.
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
class CMS_JPG_Admin_Pages
{
    // ── MODULE-TRAITS (Seiten 1-10) ───────────────────────────────────────────
    use CMS_JPG_Page_Dashboard_Trait;
    use CMS_JPG_Page_Generator_Trait;
    use CMS_JPG_Page_Libraries_Trait;
    use CMS_JPG_Page_Design_Trait;
    use CMS_JPG_Page_Settings_Trait;
    use CMS_JPG_Page_Workflow_Trait;
    use CMS_JPG_Page_Approvals_Trait;
    use CMS_JPG_Page_Companies_Trait;
    use CMS_JPG_Page_Subscription_Trait;
    use CMS_JPG_Page_Users_Trait;
    use CMS_JPG_Page_Public_Design_Trait;

    // ── Konstanten ────────────────────────────────────────────────────────────
    public const  NONCE_FIELD_PUBLIC = '_jpg_nonce';
    private const NONCE_FIELD        = '_jpg_nonce';
    private const DEFAULT_ADMIN_PAGE = 'jpg-dashboard';

    // ── Sicherheits-Helfer ────────────────────────────────────────────────────

    /** Öffentlicher Wrapper für Views */
    public static function nonce(string $action): string
    {
        return self::generate_nonce($action);
    }

    private static function check_access(): void
    {
        if (!class_exists('CMS\Auth') || !\CMS\Auth::instance()->isAdmin()) {
            wp_die('Zugriff verweigert.', 403);
        }
        // Subscription-basierte Zugriffsrechte
        if (function_exists('user_can_access_plugin')
            && !user_can_access_plugin('cms-jobprofile-generator')) {
            wp_die('Dieses Plugin ist in Ihrem aktuellen Abo nicht verfügbar.', 403);
        }
    }

    /**
     * Gibt HTML für Limit-Warnung aus (Dashboard).
     * @since 0.0.1
     */
    public static function render_limit_warning_public(): void
    {
        if (function_exists('display_resource_limit_warning')) {
            display_resource_limit_warning('job_profiles', 'Job-Profile');
        }
    }

    /**
     * Gibt HTML für Upgrade-Notice aus (Premium-Features).
     * @since 0.0.1
     */
    public static function render_upgrade_notice_public(string $feature, string $message = ''): void
    {
        if (function_exists('user_has_feature') && !user_has_feature($feature)) {
            if (function_exists('display_upgrade_notice')) {
                display_upgrade_notice($message ?: 'Dieses Feature ist in Ihrem aktuellen Abo nicht verfügbar.');
            }
        }
    }

    private static function verify_nonce(string $action): bool
    {
        if (!class_exists('CMS\Security')) {
            return true; // Fallback wenn Core nicht verfügbar
        }
        return \CMS\Security::instance()->verifyNonce(
            $_POST[self::NONCE_FIELD] ?? '',
            $action
        );
    }

    private static function generate_nonce(string $action): string
    {
        if (!class_exists('CMS\Security')) {
            return '';
        }
        return \CMS\Security::instance()->generateToken($action);
    }

    private static function esc(string $value): string
    {
        if (class_exists('CMS\Security')) {
            return \CMS\Security::escape($value);
        }
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // ── URL-Helfer ────────────────────────────────────────────────────────────

    /** Erzeugt die korrekte Plugin-Admin-URL für dieses Plugin */
    public static function page_url(string $page, array $params = []): string
    {
        $url = '/admin/plugins/jpg-dashboard/' . $page;
        foreach ($params as $k => $v) {
            $url .= (str_contains($url, '?') ? '&' : '?') . urlencode($k) . '=' . urlencode((string) $v);
        }
        return $url;
    }

    // ── Redirect-Helfer ───────────────────────────────────────────────────────

    private static function redirect(string $page, array $params = []): void
    {
        wp_redirect(self::page_url($page, $params));
        exit;
    }

    /**
     * Central admin dispatcher with shared contract fallback.
     */
    public static function dispatch_admin_page(): void
    {
        self::boot_shared_admin_contract();

        $callbackMap = [
            'jpg-dashboard'    => [self::class, 'render_dashboard'],
            'jpg-generator'    => [self::class, 'render_generator'],
            'jpg-libraries'    => [self::class, 'render_libraries'],
            'jpg-design'       => [self::class, 'render_design'],
            'jpg-workflow'     => [self::class, 'render_workflow'],
            'jpg-approvals'    => [self::class, 'render_approvals'],
            'jpg-companies'    => [self::class, 'render_company_overview'],
            'jpg-users'        => [self::class, 'render_users'],
            'jpg-subscription' => [self::class, 'render_subscription'],
            'jpg-public-design'=> [self::class, 'render_public_design'],
            'jpg-settings'     => [self::class, 'render_settings'],
        ];

        $fallbackPage = self::extract_admin_page_from_request(array_keys($callbackMap));
        if (!isset($_GET['page']) && $fallbackPage !== '') {
            $_GET['page'] = $fallbackPage;
        }

        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbackMap, self::DEFAULT_ADMIN_PAGE, self::DEFAULT_ADMIN_PAGE);
            return;
        }

        $requestedPage = sanitize_key((string) ($_GET['page'] ?? self::DEFAULT_ADMIN_PAGE));
        $resolvedPage  = isset($callbackMap[$requestedPage]) ? $requestedPage : self::DEFAULT_ADMIN_PAGE;
        $callback      = $callbackMap[$resolvedPage] ?? null;
        if (is_callable($callback)) {
            call_user_func($callback);
            return;
        }

        wp_die('Die angeforderte Admin-Seite ist nicht verfügbar.', 500);
    }

    /**
     * Shared layout renderer for admin views.
     *
     * @param array<string,mixed> $viewData
     */
    private static function render_admin_view(string $title, string $activePageSlug, string $viewFile, array $viewData = []): void
    {
        self::boot_shared_admin_contract();

        $usesSharedLayout = function_exists('cms_plugin_admin_layout_start')
            && function_exists('cms_plugin_admin_layout_end');

        if ($usesSharedLayout) {
            cms_plugin_admin_layout_start($title, $activePageSlug);
        } else {
            echo '<div class="cms-plugin-admin-layout"><div class="cms-plugin-admin-layout__content">';
        }

        extract($viewData, EXTR_SKIP);
        include $viewFile;

        if ($usesSharedLayout) {
            cms_plugin_admin_layout_end();
            return;
        }

        echo '</div></div>';
    }

    /**
     * Loads shared admin contract helpers from shared files.
     */
    private static function boot_shared_admin_contract(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $contractFile = dirname(JPG_DIR) . '/shared/admin/plugin-admin-contract.php';
        if (is_file($contractFile)) {
            require_once $contractFile;
        }
    }

    /**
     * @param array<int,string> $allowedPages
     */
    private static function extract_admin_page_from_request(array $allowedPages): string
    {
        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
        if ($path === '') {
            return '';
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        if (empty($segments)) {
            return '';
        }

        $candidate = sanitize_key((string) end($segments));
        return in_array($candidate, $allowedPages, true) ? $candidate : '';
    }
}
