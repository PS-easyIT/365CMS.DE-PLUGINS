<?php
/**
 * CMS M365 License – Admin Pages Shell
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$traitsDir = CMS_M365LIC_PLUGIN_DIR . 'admin/modules/';
if (is_dir($traitsDir)) {
    foreach (glob($traitsDir . 'trait-*.php') as $traitFile) {
        require_once $traitFile;
    }
}

final class CMS_M365LIC_Admin_Pages
{
    use CMS_M365LIC_Page_Dashboard_Trait;
    use CMS_M365LIC_Page_Packages_Trait;
    use CMS_M365LIC_Page_Special_Groups_Trait;
    use CMS_M365LIC_Page_Special_Users_Trait;
    use CMS_M365LIC_Page_Settings_Trait;

    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    protected static function load_admin_menu(): void
    {
        $menuFile = ABSPATH . 'admin/partials/admin-menu.php';
        if (file_exists($menuFile) && !function_exists('renderAdminLayoutStart')) {
            require_once $menuFile;
        }
    }

    protected static function render_with_layout(string $title, string $slug, callable $renderer): void
    {
        self::check_access();
        self::load_admin_menu();

        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, $slug);
        }

        self::enqueue_admin_assets();
        self::enqueue_admin_scripts();
        $renderer();

        if (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
    }

    protected static function check_access(): void
    {
        if (!class_exists('CMS\\Auth') || !\CMS\Auth::instance()->isAdmin()) {
            header('Location: ' . (defined('SITE_URL') ? SITE_URL : '/'));
            exit;
        }
    }

    protected static function enqueue_admin_assets(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $css = CMS_M365LIC_PLUGIN_DIR . 'assets/css/m365lic-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_M365LIC_PLUGIN_URL . 'assets/css/m365lic-admin.css', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    protected static function enqueue_admin_scripts(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $js = CMS_M365LIC_PLUGIN_DIR . 'assets/js/m365lic-admin.js';
        if (file_exists($js)) {
            echo '<script src="'
                . htmlspecialchars(CMS_M365LIC_PLUGIN_URL . 'assets/js/m365lic-admin.js', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($js) . '" defer></script>' . "\n";
        }
    }

    protected static function generate_nonce(string $action): string
    {
        return class_exists('CMS\\Security')
            ? \CMS\Security::instance()->generateToken($action)
            : bin2hex(random_bytes(16));
    }

    protected static function verify_nonce(string $action): bool
    {
        return !class_exists('CMS\\Security')
            || \CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', $action);
    }

    protected static function repo(): CMS_M365LIC_Repository
    {
        return CMS_M365LIC_Repository::instance();
    }

    protected static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @return array<int,string>
     */
    protected static function split_comma_list(string $value): array
    {
        $parts = array_map('trim', explode(',', $value));
        return array_values(array_filter(array_map('strval', $parts)));
    }

    public static function render_dashboard(): void
    {
        self::render_with_layout('M365 Lizenzberater', 'm365lic-dashboard', static function (): void {
            self::instance()->render_dashboard_page();
        });
    }

    public static function render_packages(): void
    {
        self::render_with_layout('M365 Lizenz-Pakete', 'm365lic-packages', static function (): void {
            self::instance()->render_packages_page();
        });
    }

    public static function render_settings(): void
    {
        self::render_with_layout('M365 Lizenz-Einstellungen', 'm365lic-settings', static function (): void {
            self::instance()->render_settings_page();
        });
    }

    public static function render_special_users(): void
    {
        self::render_with_layout('M365 User', 'm365lic-special-users', static function (): void {
            self::instance()->render_special_users_page();
        });
    }

    public static function render_special_groups(): void
    {
        self::render_with_layout('M365 Gruppen', 'm365lic-special-groups', static function (): void {
            self::instance()->render_special_groups_page();
        });
    }
}
