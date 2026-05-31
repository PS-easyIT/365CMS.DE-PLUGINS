<?php
/**
 * CMS Forum – Admin Pages (Trait-Shell)
 *
 * Zentrale Klasse, die alle Admin-Traits zusammenführt
 * und gemeinsame Hilfsmethoden bereitstellt.
 *
 * @package CMS_Forum
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Shared Admin-Contract laden
$sharedContractFile = dirname(__DIR__) . '/../shared/admin/plugin-admin-contract.php';
if (is_file($sharedContractFile)) {
    require_once $sharedContractFile;
}

// Traits laden
$modulesDir = __DIR__ . '/modules/';
foreach ([
    'trait-page-dashboard.php',
    'trait-page-categories.php',
    'trait-page-forums.php',
    'trait-page-threads.php',
    'trait-page-users.php',
    'trait-page-ranks.php',
    'trait-page-permissions.php',
    'trait-page-reports.php',
    'trait-page-settings.php',
] as $traitFile) {
    if (file_exists($modulesDir . $traitFile)) {
        require_once $modulesDir . $traitFile;
    }
}

final class CMS_Forum_Admin_Pages
{
    /**
     * Submenu-Navigation für den Forum-Admin.
     *
     * @var array<string, string>
     */
    private const ADMIN_SUBMENU_ITEMS = [
        'forum-dashboard'   => '📊 Dashboard',
        'forum-categories'  => '🗂️ Kategorien',
        'forum-forums'      => '📁 Foren',
        'forum-threads'     => '📝 Threads',
        'forum-users'       => '👥 Benutzer',
        'forum-ranks'       => '🏅 Ränge',
        'forum-permissions' => '🔒 Berechtigungen',
        'forum-reports'     => '🚩 Meldungen',
        'forum-settings'    => '⚙️ Einstellungen',
    ];

    use CMS_Forum_Page_Dashboard_Trait;
    use CMS_Forum_Page_Categories_Trait;
    use CMS_Forum_Page_Forums_Trait;
    use CMS_Forum_Page_Threads_Trait;
    use CMS_Forum_Page_Users_Trait;
    use CMS_Forum_Page_Ranks_Trait;
    use CMS_Forum_Page_Permissions_Trait;
    use CMS_Forum_Page_Reports_Trait;
    use CMS_Forum_Page_Settings_Trait;

    /**
     * Zentraler Dispatcher für alle Admin-Seiten.
     */
    public static function dispatch(): void
    {
        $callbacks = self::resolve_admin_callbacks();

        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbacks, 'forum-dashboard', 'forum-dashboard');
            return;
        }

        self::dispatch_without_shared_contract($callbacks, 'forum-dashboard');
    }

    // ── Gemeinsame Hilfsmethoden ────────────────────────────────────

    /**
     * Admin-Zugangs-Check.
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
     * CSRF-Token generieren.
     */
    protected static function generate_nonce(string $action): string
    {
        return \CMS\Security::instance()->generateToken($action);
    }

    /**
     * CSRF-Token prüfen.
     */
    protected static function verify_nonce(string $action): bool
    {
        return \CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), $action);
    }

    /**
     * Output-Escaping.
     */
    protected static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Admin-CSS einmalig laden.
     */
    protected static function enqueue_admin_assets(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $css = CMS_FORUM_DIR . 'assets/css/cms-forum-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_FORUM_URL . 'assets/css/cms-forum-admin.css?v=' . filemtime($css), ENT_QUOTES, 'UTF-8')
                . '">' . "\n";
        }
    }

    /**
     * Admin-Menü/Layout-Funktionen laden.
     */
    protected static function load_admin_menu(): void
    {
        $menuFile = ABSPATH . 'admin/partials/admin-menu.php';
        if (file_exists($menuFile) && !function_exists('renderAdminLayoutStart')) {
            require_once $menuFile;
        }
    }

    /**
     * Einheitliches Admin-Layout nach CMS-Experts-Muster.
     */
    protected static function render_admin_page(string $title, callable $renderer): void
    {
        $activeSlug = function_exists('cms_plugin_admin_active_slug')
            ? cms_plugin_admin_active_slug('forum-dashboard')
            : 'forum-dashboard';

        if (function_exists('cms_plugin_admin_layout_start')) {
            cms_plugin_admin_layout_start($title, 'forum-dashboard');
        } else {
            self::load_admin_menu();
            if (function_exists('renderAdminLayoutStart')) {
                renderAdminLayoutStart($title, 'forum-dashboard');
            }
        }

        echo '<div class="forum-admin-shell-wrap">';
        self::render_admin_submenu($activeSlug);
        echo '<div class="forum-admin-shell-main">';

        self::enqueue_admin_assets();
        $renderer();

        echo '</div></div>';

        if (function_exists('cms_plugin_admin_layout_end')) {
            cms_plugin_admin_layout_end();
        } elseif (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
    }

    /**
     * Callback-Map der Forum-Admin-Seiten.
     *
     * @return array<string, callable|null>
     */
    private static function resolve_admin_callbacks(): array
    {
        return [
            'forum-dashboard'   => [self::class, 'render_dashboard'],
            'forum-categories'  => [self::class, 'render_categories'],
            'forum-forums'      => [self::class, 'render_forums'],
            'forum-threads'     => [self::class, 'render_threads'],
            'forum-users'       => [self::class, 'render_users'],
            'forum-ranks'       => [self::class, 'render_ranks'],
            'forum-permissions' => [self::class, 'render_permissions'],
            'forum-reports'     => [self::class, 'render_reports'],
            'forum-settings'    => [self::class, 'render_settings'],
        ];
    }

    /**
     * Fallback-Dispatcher ohne Shared-Contract.
     *
     * @param array<string, callable|null> $callbackMap
     */
    private static function dispatch_without_shared_contract(array $callbackMap, string $defaultSlug): void
    {
        $requestedSlug = self::normalize_admin_slug((string) ($_GET['page'] ?? $defaultSlug));
        $defaultSlug = self::normalize_admin_slug($defaultSlug);
        $resolvedSlug = array_key_exists($requestedSlug, $callbackMap) ? $requestedSlug : $defaultSlug;
        $callback = $callbackMap[$resolvedSlug] ?? null;

        if (!is_callable($callback)) {
            self::render_admin_page('Forum', static function (): void {
                echo '<div class="alert alert-error" role="alert">';
                echo 'Die angeforderte Admin-Seite ist derzeit nicht verfuegbar.';
                echo '</div>';
            });
            return;
        }

        call_user_func($callback);
    }

    /**
     * Rendert die Sidebar-Submenu-Navigation.
     */
    private static function render_admin_submenu(string $activeSlug): void
    {
        $activeSlug = self::normalize_admin_slug($activeSlug);
        echo '<aside class="forum-admin-submenu" aria-label="Forum Admin Navigation">';
        echo '<h3 class="forum-admin-submenu__title">Forum-Navigation</h3>';
        echo '<ul class="forum-admin-submenu__list">';

        foreach (self::ADMIN_SUBMENU_ITEMS as $slug => $label) {
            $isActive = $activeSlug === $slug;
            $class = $isActive ? 'forum-admin-submenu__link is-active' : 'forum-admin-submenu__link';
            $href = '?page=' . rawurlencode($slug);

            echo '<li class="forum-admin-submenu__item">';
            echo '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" class="' . $class . '"';
            echo $isActive ? ' aria-current="page">' : '>';
            echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            echo '</a>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</aside>';
    }

    /**
     * Slug-Normalisierung für Admin-Seiten.
     */
    private static function normalize_admin_slug(string $slug): string
    {
        if (function_exists('cms_plugin_admin_normalize_slug')) {
            return cms_plugin_admin_normalize_slug($slug);
        }

        $slug = strtolower(trim($slug));
        $slug = (string) preg_replace('/[^a-z0-9_-]+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Settings-Wert lesen.
     */
    protected static function get_setting(string $key, string $default = ''): string
    {
        try {
            $db   = \CMS\Database::instance();
            $p    = $db->prefix();
            $stmt = $db->prepare("SELECT setting_value FROM {$p}cmsforum_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return $val !== false ? (string) $val : $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Settings-Wert speichern.
     */
    protected static function save_setting(string $key, string $value): void
    {
        $db = \CMS\Database::instance();
        $p  = $db->prefix();

        $stmt = $db->prepare(
            "INSERT INTO {$p}cmsforum_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        $stmt->execute([$key, $value]);
    }

    /**
     * Datenbank-Objekt.
     */
    protected static function db(): \CMS\Database
    {
        return \CMS\Database::instance();
    }

    /**
     * Tabellen-Präfix.
     */
    protected static function prefix(): string
    {
        return self::db()->prefix();
    }
}
