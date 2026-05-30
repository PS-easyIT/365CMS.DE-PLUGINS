<?php
/**
 * Plugin Name: CMS M365 Landing
 * Plugin URI: https://365network.de/cms-m365landing
 * Description: Zentrale, vollständig steuerbare Landingpage für M365-Matrixen, Azure Services, Tutorials und M365 Tools.
 * Version: 1.0.6
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_M365Landing
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

defined('CMS_M365LANDING_VERSION') || define('CMS_M365LANDING_VERSION', '1.0.6');
defined('CMS_M365LANDING_DB_VERSION') || define('CMS_M365LANDING_DB_VERSION', '1.0.2');
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
        foreach ([
            'includes/class-installer.php',
            'includes/class-repository.php',
            'includes/class-frontend.php',
            'admin/class-admin-pages.php',
            'admin/class-admin-menu.php',
        ] as $file) {
            $path = CMS_M365LANDING_PLUGIN_DIR . $file;
            if (file_exists($path)) {
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
        \CMS\Hooks::addAction('cms_admin_menu', [CMS_M365Landing_Admin_Menu::class, 'register'], 10);
        \CMS\Hooks::addAction('register_routes', [CMS_M365Landing_Frontend::class, 'instance'], 10);
    }

    public function init_plugin(): void
    {
        CMS_M365Landing_Installer::maybe_install();
        CMS_M365Landing_Frontend::instance();
    }

    public function on_activation(string $pluginSlug): void
    {
        if ($pluginSlug !== 'cms-m365landing') {
            return;
        }

        CMS_M365Landing_Installer::install();
        CMS_M365Landing_Frontend::instance();
    }
}

CMS_M365Landing::instance();
