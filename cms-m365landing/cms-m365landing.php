<?php
/**
 * Plugin Name: CMS M365 Landing
 * Plugin URI: https://365network.de/cms-m365landing
 * Description: Zentrale, vollständig steuerbare Landingpage für M365-Matrixen, Azure Services, Tutorials und M365 Tools.
 * Version: 1.0.27
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_M365Landing
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

defined('CMS_M365LANDING_VERSION') || define('CMS_M365LANDING_VERSION', '1.0.27');
defined('CMS_M365LANDING_DB_VERSION') || define('CMS_M365LANDING_DB_VERSION', '1.0.7');
defined('CMS_M365LANDING_PLUGIN_DIR') || define('CMS_M365LANDING_PLUGIN_DIR', dirname(__FILE__) . '/');
defined('CMS_M365LANDING_PLUGIN_URL') || define('CMS_M365LANDING_PLUGIN_URL', '/plugins/cms-m365landing/');

final class CMS_M365Landing
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies(): void
    {
        $sharedAdminContract = dirname(rtrim(CMS_M365LANDING_PLUGIN_DIR, '/\\')) . '/shared/admin/plugin-admin-contract.php';
        if (is_file($sharedAdminContract)) {
            require_once $sharedAdminContract;
        }
        $sharedPublicI18n = dirname(rtrim(CMS_M365LANDING_PLUGIN_DIR, '/\\')) . '/shared/public/plugin-public-i18n.php';
        if (is_file($sharedPublicI18n)) {
            require_once $sharedPublicI18n;
        }

        foreach ([
            'includes/class-installer.php',
            'includes/class-repository.php',
            'includes/class-frontend.php',
            'admin/class-admin-pages.php',
            'admin/class-admin-menu.php',
        ] as $file) {
            $path = CMS_M365LANDING_PLUGIN_DIR . $file;
            if (is_file($path)) {
                require_once $path;
            }
        }
    }

    private function init_hooks(): void
    {
        if (!class_exists('CMS\\Hooks')) {
            return;
        }

        \CMS\Hooks::addAction('cms_init', [$this, 'init_plugin'], 10);
        \CMS\Hooks::addAction('plugin_activated', [$this, 'on_activation'], 10);
        if (class_exists('CMS_M365Landing_Admin_Menu')) {
            \CMS\Hooks::addAction('cms_admin_menu', [CMS_M365Landing_Admin_Menu::class, 'register'], 10);
        }
        if (class_exists('CMS_M365Landing_Frontend')) {
            \CMS\Hooks::addAction('register_routes', [CMS_M365Landing_Frontend::class, 'instance'], 99);
        }
    }

    public function init_plugin(): void
    {
        try {
            if (class_exists('CMS_M365Landing_Installer')) {
                CMS_M365Landing_Installer::maybe_install();
            }
            if (class_exists('CMS_M365Landing_Frontend')) {
                CMS_M365Landing_Frontend::instance();
            }
        } catch (\Throwable $e) {
            self::log_exception('init_plugin_failed', $e);
        }
    }

    public function on_activation(string $pluginSlug): void
    {
        if ($pluginSlug !== 'cms-m365landing') {
            return;
        }

        if (class_exists('CMS_M365Landing_Installer')) {
            CMS_M365Landing_Installer::install();
        }
        if (class_exists('CMS_M365Landing_Frontend')) {
            CMS_M365Landing_Frontend::instance();
        }
    }

    private static function log_exception(string $context, \Throwable $e): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CMS M365 Landing [' . $context . ']: ' . $e->getMessage());
        }
    }
}

CMS_M365Landing::instance();
