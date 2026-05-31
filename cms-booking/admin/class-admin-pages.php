<?php
/**
 * CMS Booking – Admin-Pages Shell
 *
 * Lädt alle Admin-Traits und delegiert an die richtige Section.
 *
 * @package CMS_Booking
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Traits laden
$traitsDir = CMS_BOOKING_PLUGIN_DIR . 'admin/modules/';
$traitsDirReal = is_dir($traitsDir) ? realpath($traitsDir) : false;
if ($traitsDirReal !== false) {
    $traitFiles = glob($traitsDirReal . DIRECTORY_SEPARATOR . 'trait-*.php') ?: [];
    sort($traitFiles);
    foreach ($traitFiles as $traitFile) {
        $traitReal = realpath($traitFile);
        if ($traitReal === false || strpos($traitReal, $traitsDirReal . DIRECTORY_SEPARATOR) !== 0) {
            continue;
        }
        require_once $traitReal;
    }
}

final class CMS_Booking_Admin_Pages
{
    use CMS_Booking_Page_Dashboard_Trait;
    use CMS_Booking_Page_Bookings_Trait;
    use CMS_Booking_Page_Providers_Trait;
    use CMS_Booking_Page_Services_Trait;
    use CMS_Booking_Page_Settings_Trait;

    private static ?self $instance = null;
    private const DEFAULT_PAGE_SLUG = 'booking';
    private const PAGE_CALLBACKS = [
        'booking'   => 'render_dashboard',
        'bookings'  => 'render_bookings',
        'providers' => 'render_providers',
        'services'  => 'render_services',
        'settings'  => 'render_settings',
    ];

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct() {}

    // ── Gemeinsame Hilfsmethoden ──────────────────────────────────────────────

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
     * Zusätzlicher Capability-Check für zustandsverändernde Admin-Aktionen.
     */
    protected static function can_manage_admin_actions(): bool
    {
        return class_exists('CMS\Auth') && \CMS\Auth::instance()->isAdmin();
    }

    /**
     * Admin-CSS einbinden (einmal pro Request)
     */
    protected static function enqueue_admin_assets(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $css = CMS_BOOKING_PLUGIN_DIR . 'assets/css/booking-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_BOOKING_PLUGIN_URL . 'assets/css/booking-admin.css', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    /**
     * Admin-JS am Seitenende einbinden
     */
    protected static function enqueue_admin_scripts(): void
    {
        $js = CMS_BOOKING_PLUGIN_DIR . 'assets/js/booking-admin.js';
        if (file_exists($js)) {
            echo '<script src="'
                . htmlspecialchars(CMS_BOOKING_PLUGIN_URL . 'assets/js/booking-admin.js', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($js) . '" defer></script>' . "\n";
        }
    }

    /**
     * CSRF-Token generieren
     */
    protected static function generate_nonce(string $action): string
    {
        if (!class_exists('CMS\Security')) {
            return '';
        }
        return \CMS\Security::instance()->generateToken($action);
    }

    /**
     * CSRF-Token prüfen
     */
    protected static function verify_nonce(string $action): bool
    {
        if (!class_exists('CMS\Security')) {
            return false;
        }
        return \CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', $action);
    }

    /**
     * Erzeugt eine Admin-URL für die Sidebar-Subpages.
     *
     * @param array<string, string|int> $query
     */
    public static function admin_url(string $slug, array $query = []): string
    {
        $slug = self::normalize_slug($slug);
        if ($slug === '') {
            $slug = self::DEFAULT_PAGE_SLUG;
        }

        $url = '/admin/plugins/' . CMS_Booking_Admin_Menu::MENU_SLUG . '/' . rawurlencode($slug);
        if (!empty($query)) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        return $url;
    }

    /**
     * Output-Escaping
     */
    protected static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    // ── Statische Entry-Points (aufgerufen vom Router) ────────────────────────

    /**
     * Zentraler Dispatcher für add_menu_page/add_submenu_page.
     * Er nutzt den Shared-Contract inkl. Missing-Callback-Fallback.
     */
    public static function dispatch(): void
    {
        self::ensure_shared_contract_loaded();

        $callbacks = self::resolve_dispatch_callbacks();

        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbacks, self::DEFAULT_PAGE_SLUG, CMS_Booking_Admin_Menu::MENU_SLUG);
            return;
        }

        // Fallback ohne Shared Contract (defensiv).
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

    public static function dispatch_dashboard(): void
    {
        $_GET['page'] = 'booking';
        self::dispatch();
    }

    public static function dispatch_bookings(): void
    {
        $_GET['page'] = 'bookings';
        self::dispatch();
    }

    public static function dispatch_providers(): void
    {
        $_GET['page'] = 'providers';
        self::dispatch();
    }

    public static function dispatch_services(): void
    {
        $_GET['page'] = 'services';
        self::dispatch();
    }

    public static function dispatch_settings(): void
    {
        $_GET['page'] = 'settings';
        self::dispatch();
    }

    public static function render_dashboard(): void
    {
        self::render_with_layout('Buchungen', 'booking', static function (): void {
            self::instance()->render_dashboard_page();
        });
    }

    public static function render_bookings(): void
    {
        self::render_with_layout('Buchungen verwalten', 'bookings', static function (): void {
            self::instance()->render_bookings_page();
        });
    }

    public static function render_providers(): void
    {
        self::render_with_layout('Booking Anbieter', 'providers', static function (): void {
            self::instance()->render_providers_page();
        });
    }

    public static function render_services(): void
    {
        self::render_with_layout('Booking Leistungen', 'services', static function (): void {
            self::instance()->render_services_page();
        });
    }

    public static function render_settings(): void
    {
        self::render_with_layout('Booking Einstellungen', 'settings', static function (): void {
            self::instance()->render_settings_page();
        });
    }

    /**
     * Legacy-Haupt-Routing (für Direktaufrufe mit ?section=…)
     */
    public function render(): void
    {
        $legacySection = self::normalize_slug((string) ($_GET['section'] ?? self::DEFAULT_PAGE_SLUG));
        if ($legacySection === '') {
            $legacySection = self::DEFAULT_PAGE_SLUG;
        }

        $_GET['page'] = $legacySection;
        self::dispatch();
    }

    private static function render_with_layout(string $title, string $activePage, callable $renderer): void
    {
        self::check_access();
        self::ensure_shared_contract_loaded();

        $activePage = self::normalize_slug($activePage);
        if ($activePage === '') {
            $activePage = self::DEFAULT_PAGE_SLUG;
        }

        $layoutStarted = false;
        if (!headers_sent()) {
            if (function_exists('cms_plugin_admin_layout_start')) {
                cms_plugin_admin_layout_start($title, $activePage);
                $layoutStarted = true;
            } elseif (function_exists('renderAdminLayoutStart')) {
                renderAdminLayoutStart($title, $activePage);
                $layoutStarted = true;
            } else {
                self::render_core_layout_start($title, $activePage);
                $layoutStarted = true;
            }
        }

        self::enqueue_admin_assets();
        echo '<div class="booking-admin-shell">';
        $renderer();
        echo '</div>';
        self::enqueue_admin_scripts();

        if ($layoutStarted) {
            if (function_exists('cms_plugin_admin_layout_end')) {
                cms_plugin_admin_layout_end();
            } elseif (function_exists('renderAdminLayoutEnd')) {
                renderAdminLayoutEnd();
            } else {
                self::render_core_layout_end();
            }
        }
    }

    /**
     * @return array<string, callable|null>
     */
    private static function resolve_dispatch_callbacks(): array
    {
        $callbacks = [];

        foreach (self::PAGE_CALLBACKS as $slug => $method) {
            $callbacks[$slug] = is_callable([self::class, $method]) ? [self::class, $method] : null;
        }

        return $callbacks;
    }

    private static function render_missing_callback_notice(string $requestedSlug, string $resolvedSlug): void
    {
        self::render_with_layout('Booking Administration', self::DEFAULT_PAGE_SLUG, static function () use ($requestedSlug, $resolvedSlug): void {
            if (function_exists('cms_plugin_admin_emit_notice')) {
                cms_plugin_admin_emit_notice(
                    'Die angeforderte Admin-Seite ist derzeit nicht verfuegbar. Bitte pruefen Sie die Plugin-Konfiguration.',
                    'error',
                    sprintf(
                        'missing admin callback plugin=%s requested=%s resolved=%s default=%s',
                        CMS_Booking_Admin_Menu::MENU_SLUG,
                        $requestedSlug,
                        $resolvedSlug,
                        self::DEFAULT_PAGE_SLUG
                    )
                );
                return;
            }

            echo '<div class="alert alert-error" role="alert">';
            echo htmlspecialchars(
                'Die angeforderte Admin-Seite ist derzeit nicht verfuegbar. Bitte pruefen Sie die Plugin-Konfiguration.',
                ENT_QUOTES,
                'UTF-8'
            );
            echo '</div>';
        });
    }

    private static function ensure_shared_contract_loaded(): void
    {
        if (function_exists('cms_plugin_admin_dispatch_page')) {
            return;
        }

        $sharedContract = dirname(CMS_BOOKING_PLUGIN_DIR) . '/shared/admin/plugin-admin-contract.php';
        $sharedReal = realpath($sharedContract);
        $allowedRoot = realpath(dirname(CMS_BOOKING_PLUGIN_DIR) . '/shared/');
        if ($sharedReal !== false && is_file($sharedReal) && $allowedRoot !== false) {
            $allowedPrefix = rtrim($allowedRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if (strpos($sharedReal, $allowedPrefix) === 0) {
                require_once $sharedReal;
            }
        }
    }

    private static function normalize_slug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = (string) preg_replace('/[^a-z0-9_-]+/', '-', $slug);
        return trim($slug, '-');
    }

    private static function render_core_layout_start(string $title, string $activePage): void
    {
        if (!defined('ABSPATH')) {
            return;
        }

        $header = ABSPATH . 'admin/partials/header.php';
        $sidebar = ABSPATH . 'admin/partials/sidebar.php';
        if (!is_file($header) || !is_file($sidebar)) {
            return;
        }

        $pageTitle = $title;
        $pageAssets = [];
        require $header;
        require $sidebar;
    }

    private static function render_core_layout_end(): void
    {
        if (!defined('ABSPATH')) {
            return;
        }

        $footer = ABSPATH . 'admin/partials/footer.php';
        if (is_file($footer)) {
            require $footer;
        }
    }
}
