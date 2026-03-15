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
    use CMS_M365LIC_Page_Settings_Trait;

    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
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
        self::check_access();
        self::enqueue_admin_assets();
        self::instance()->render_dashboard_page();
    }

    public static function render_packages(): void
    {
        self::check_access();
        self::enqueue_admin_assets();
        self::instance()->render_packages_page();
    }

    public static function render_settings(): void
    {
        self::check_access();
        self::enqueue_admin_assets();
        self::instance()->render_settings_page();
    }
}
