<?php
/**
 * CMS Contact – Admin Pages (Trait-Shell)
 *
 * Zentrale Klasse, die alle Admin-Traits zusammenführt.
 *
 * @package CMS_Contact
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Traits laden
$modulesDir = __DIR__ . '/modules/';
foreach ([
    'trait-page-dashboard.php',
    'trait-page-forms.php',
    'trait-page-submissions.php',
    'trait-page-settings.php',
] as $traitFile) {
    if (file_exists($modulesDir . $traitFile)) {
        require_once $modulesDir . $traitFile;
    }
}

final class CMS_Contact_Admin_Pages
{
    use CMS_Contact_Page_Dashboard_Trait;
    use CMS_Contact_Page_Forms_Trait;
    use CMS_Contact_Page_Submissions_Trait;
    use CMS_Contact_Page_Settings_Trait;

    public const MENU_SLUG = 'contact';
    public const DEFAULT_PAGE_SLUG = 'contact';
    private const PAGE_SLUG_FOR_SECTION = [
        'dashboard' => 'contact',
        'forms' => 'contact-forms',
        'submissions' => 'contact-submissions',
        'settings' => 'contact-settings',
    ];
    private const PAGE_TITLES = [
        'contact' => 'Kontakt',
        'contact-forms' => 'Kontaktformulare',
        'contact-submissions' => 'Kontakt-Nachrichten',
        'contact-settings' => 'Kontakt-Einstellungen',
    ];
    private const PAGE_RENDERERS = [
        'contact' => 'render_dashboard_page',
        'contact-forms' => 'render_forms_page',
        'contact-submissions' => 'render_submissions_page',
        'contact-settings' => 'render_settings_page',
    ];

    /**
     * Zentrale Dispatch: leitet anhand ?section= an den richtigen Trait weiter.
     *
     * Output-Buffering erlaubt header()-Redirects in POST-Handlern,
     * auch wenn renderAdminLayoutStart() bereits HTML ausgegeben hat.
     */
    public static function render_dispatch(): void
    {
        self::ensure_shared_contract_loaded();

        if (function_exists('cms_plugin_admin_sync_page_from_request')) {
            cms_plugin_admin_sync_page_from_request(self::MENU_SLUG);
        }

        self::sync_legacy_section_to_page();

        $callbacks = self::resolve_dispatch_callbacks();
        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbacks, self::DEFAULT_PAGE_SLUG, self::MENU_SLUG);
            return;
        }

        $activeSlug = self::normalize_slug((string) ($_GET['page'] ?? self::DEFAULT_PAGE_SLUG));
        if ($activeSlug === '') {
            $activeSlug = self::DEFAULT_PAGE_SLUG;
        }

        $resolvedSlug = array_key_exists($activeSlug, $callbacks) ? $activeSlug : self::DEFAULT_PAGE_SLUG;
        $callback = $callbacks[$resolvedSlug] ?? null;
        if (!is_callable($callback)) {
            self::render_missing_callback_notice($activeSlug, $resolvedSlug);
            return;
        }

        call_user_func($callback);
    }

    /**
     * @return array{0:class-string,1:string}
     */
    public static function dispatch_callback_for_slug(string $slug): array
    {
        $slug = self::normalize_slug($slug);
        $map = [
            'contact' => 'dispatch_dashboard',
            'contact-forms' => 'dispatch_forms',
            'contact-submissions' => 'dispatch_submissions',
            'contact-settings' => 'dispatch_settings',
        ];

        $method = $map[$slug] ?? 'dispatch_dashboard';

        return [self::class, $method];
    }

    public static function dispatch_dashboard(): void
    {
        $_GET['page'] = 'contact';
        self::render_dispatch();
    }

    public static function dispatch_forms(): void
    {
        $_GET['page'] = 'contact-forms';
        self::render_dispatch();
    }

    public static function dispatch_submissions(): void
    {
        $_GET['page'] = 'contact-submissions';
        self::render_dispatch();
    }

    public static function dispatch_settings(): void
    {
        $_GET['page'] = 'contact-settings';
        self::render_dispatch();
    }

    /**
     * @param mixed $router
     */
    public static function register_admin_routes($router): void
    {
        self::ensure_shared_contract_loaded();

        if (!function_exists('cms_plugin_admin_register_routes')) {
            return;
        }

        cms_plugin_admin_register_routes($router, self::MENU_SLUG, self::resolve_dispatch_callbacks());
    }

    /**
     * URL-Helper für Contact-Admin-Seiten
     */
    public static function admin_url(string $section = 'dashboard', array $params = []): string
    {
        $section = self::normalize_slug($section);
        $slug = self::PAGE_SLUG_FOR_SECTION[$section] ?? self::DEFAULT_PAGE_SLUG;
        $url = '/admin/plugins/' . self::MENU_SLUG . '/' . rawurlencode($slug);

        if ($params !== []) {
            $url .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        return $url;
    }

    protected static function redirect_to_admin(string $section = 'dashboard', array $params = []): never
    {
        header('Location: ' . self::admin_url($section, $params));
        exit;
    }

    /**
     * @return array<int, array{slug:string,title:string,menu_title:string}>
     */
    public static function get_menu_pages(): array
    {
        return [
            ['slug' => 'contact', 'title' => 'Dashboard', 'menu_title' => '📊 Dashboard'],
            ['slug' => 'contact-forms', 'title' => 'Formulare', 'menu_title' => '📋 Formulare'],
            ['slug' => 'contact-submissions', 'title' => 'Nachrichten', 'menu_title' => '📩 Nachrichten'],
            ['slug' => 'contact-settings', 'title' => 'Einstellungen', 'menu_title' => '⚙️ Einstellungen'],
        ];
    }

    public static function render_dashboard_page(): void
    {
        self::render_with_layout('contact', static function (): void {
            self::render_dashboard();
        });
    }

    public static function render_forms_page(): void
    {
        self::render_with_layout('contact-forms', static function (): void {
            self::render_forms();
        });
    }

    public static function render_submissions_page(): void
    {
        self::render_with_layout('contact-submissions', static function (): void {
            self::render_submissions();
        });
    }

    public static function render_settings_page(): void
    {
        self::render_with_layout('contact-settings', static function (): void {
            self::render_settings();
        });
    }

    private static function render_with_layout(string $activePage, callable $renderer): void
    {
        $activePage = self::normalize_slug($activePage);
        if ($activePage === '') {
            $activePage = self::DEFAULT_PAGE_SLUG;
        }

        $title = self::PAGE_TITLES[$activePage] ?? self::PAGE_TITLES[self::DEFAULT_PAGE_SLUG];

        ob_start();
        if (function_exists('cms_plugin_admin_layout_start')) {
            cms_plugin_admin_layout_start($title, $activePage);
        } elseif (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, $activePage);
        }

        self::enqueue_admin_assets();
        echo '<div class="contact-admin-content">';
        $renderer();
        echo '</div>';
        self::enqueue_admin_scripts();

        if (function_exists('cms_plugin_admin_layout_end')) {
            cms_plugin_admin_layout_end();
        } elseif (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
        ob_end_flush();
    }

    // ── Gemeinsame Hilfsmethoden ──────────────────────────────────────────────

    /**
     * Admin-CSS und -JS einbinden (einmal pro Request)
     */
    protected static function enqueue_admin_assets(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $css = CMS_CONTACT_PLUGIN_DIR . 'assets/css/contact-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_CONTACT_PLUGIN_URL . 'assets/css/contact-admin.css', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    /**
     * Admin-JS am Seitenende einbinden
     */
    protected static function enqueue_admin_scripts(): void
    {
        $js = CMS_CONTACT_PLUGIN_DIR . 'assets/js/contact-admin.js';
        if (file_exists($js)) {
            echo '<script src="'
                . htmlspecialchars(CMS_CONTACT_PLUGIN_URL . 'assets/js/contact-admin.js', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($js) . '" defer></script>' . "\n";
        }
    }

    /**
     * @return array<string, callable|null>
     */
    private static function resolve_dispatch_callbacks(): array
    {
        $callbacks = [];
        foreach (self::PAGE_RENDERERS as $slug => $method) {
            $callbacks[$slug] = is_callable([self::class, $method]) ? [self::class, $method] : null;
        }

        return $callbacks;
    }

    private static function render_missing_callback_notice(string $requestedSlug, string $resolvedSlug): void
    {
        self::render_with_layout(self::DEFAULT_PAGE_SLUG, static function () use ($requestedSlug, $resolvedSlug): void {
            $message = 'Die angeforderte Admin-Seite ist derzeit nicht verfuegbar. Bitte pruefen Sie die Plugin-Konfiguration.';
            $logContext = sprintf(
                'missing admin callback plugin=%s requested=%s resolved=%s default=%s',
                self::MENU_SLUG,
                $requestedSlug,
                $resolvedSlug,
                self::DEFAULT_PAGE_SLUG
            );

            if (function_exists('cms_plugin_admin_emit_notice')) {
                cms_plugin_admin_emit_notice($message, 'error', $logContext);
                return;
            }

            error_log('[cms-plugin-admin] ' . $logContext . ' :: ' . $message);
            echo '<div class="alert alert-error" role="alert">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
        });
    }

    private static function sync_legacy_section_to_page(): void
    {
        $legacySection = self::normalize_slug((string) ($_GET['section'] ?? ''));
        if ($legacySection !== '') {
            $_GET['page'] = self::PAGE_SLUG_FOR_SECTION[$legacySection] ?? self::DEFAULT_PAGE_SLUG;
        }
    }

    private static function ensure_shared_contract_loaded(): void
    {
        if (function_exists('cms_plugin_admin_dispatch_page') && function_exists('cms_plugin_admin_layout_start')) {
            return;
        }

        $sharedContract = dirname(CMS_CONTACT_PLUGIN_DIR) . '/shared/admin/plugin-admin-contract.php';
        if (is_file($sharedContract)) {
            require_once $sharedContract;
        }
    }

    private static function normalize_slug(string $slug): string
    {
        if (function_exists('cms_plugin_admin_normalize_slug')) {
            return cms_plugin_admin_normalize_slug($slug);
        }

        $slug = strtolower(trim($slug));
        $slug = (string) preg_replace('/[^a-z0-9_-]+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Admin-Zugangs-Check
     */
    protected static function check_access(): bool
    {
        if (!class_exists('CMS\Auth') || !\CMS\Auth::instance()->isAdmin()) {
            header('Location: ' . (defined('SITE_URL') ? SITE_URL : '/'));
            exit;
        }
        return true;
    }

    /**
     * CSRF-Token generieren
     */
    protected static function generate_nonce(string $action): string
    {
        if (!class_exists('CMS\\Security')) {
            self::log_admin_error('missing security class while generating nonce', ['action' => $action]);
            return '';
        }

        try {
            return (string) \CMS\Security::instance()->generateToken($action);
        } catch (\Throwable $e) {
            self::log_admin_error('failed to generate nonce', ['action' => $action, 'error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * CSRF-Token prüfen
     */
    protected static function verify_nonce(string $action): bool
    {
        if (!class_exists('CMS\\Security')) {
            self::log_admin_error('missing security class while verifying nonce', ['action' => $action]);
            return false;
        }

        $token = is_scalar($_POST['csrf_token'] ?? null) ? (string) ($_POST['csrf_token'] ?? '') : '';
        if ($token === '') {
            return false;
        }

        try {
            return (bool) \CMS\Security::instance()->verifyToken($token, $action);
        } catch (\Throwable $e) {
            self::log_admin_error('failed to verify nonce', ['action' => $action, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Output-Escaping
     */
    protected static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Settings-Wert aus Plugin-Settings lesen
     */
    protected static function get_setting(string $key, string $default = ''): string
    {
        try {
            $db   = \CMS\Database::instance();
            $p    = $db->getPrefix();
            $stmt = $db->prepare("SELECT setting_value FROM {$p}contact_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ? (string) $row['setting_value'] : $default;
        } catch (\Throwable $e) {
            self::log_admin_error('failed to fetch contact setting', ['key' => $key, 'error' => $e->getMessage()]);
            return $default;
        }
    }

    /**
     * Settings-Wert speichern
     */
    protected static function save_setting(string $key, string $value): void
    {
        try {
            $db = \CMS\Database::instance();
            $p  = $db->getPrefix();

            $exists = $db->prepare("SELECT id FROM {$p}contact_settings WHERE setting_key = ?");
            $exists->execute([$key]);

            if ($exists->fetch()) {
                $db->prepare("UPDATE {$p}contact_settings SET setting_value = ? WHERE setting_key = ?")
                   ->execute([$value, $key]);
            } else {
                $db->prepare("INSERT INTO {$p}contact_settings (setting_key, setting_value) VALUES (?, ?)")
                   ->execute([$key, $value]);
            }
        } catch (\Throwable $e) {
            self::log_admin_error('failed to save contact setting', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @param array<string, scalar|null> $context
     */
    protected static function log_admin_error(string $message, array $context = []): void
    {
        $contextParts = [];
        foreach ($context as $key => $value) {
            if ($value === null) {
                continue;
            }
            $contextParts[] = $key . '=' . (string) $value;
        }

        $contextText = $contextParts !== [] ? ' [' . implode(' ', $contextParts) . ']' : '';
        error_log('[cms-contact][admin] ' . $message . $contextText);
    }
}
