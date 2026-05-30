<?php
/**
 * Plugin Name: CMS M365 Matrixen
 * Plugin URI: https://365network.de/cms-m365matrices
 * Description: Eigenständiges Public-Plugin für die reinen Microsoft-365-Lizenz- und Add-on-Matrixen mit gemeinsamen M365-Tools-Datenbanktabellen.
 * Version: 1.0.0
 * Author: 365 Network
 * Author URI: https://365network.de
 * Requires Plugins: cms-m365tools
 *
 * @package CMS_M365MATRICES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

defined('CMS_M365MATRICES_VERSION') || define('CMS_M365MATRICES_VERSION', '1.0.0');
defined('CMS_M365MATRICES_PLUGIN_DIR') || define('CMS_M365MATRICES_PLUGIN_DIR', dirname(__FILE__) . '/');
defined('CMS_M365MATRICES_PLUGIN_URL') || define('CMS_M365MATRICES_PLUGIN_URL', '/plugins/cms-m365matrices/');

if (!class_exists('CMS_M365MATRICES', false)) {
final class CMS_M365MATRICES
{
    private const PLUGIN_SLUG = 'cms-m365matrices';

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
        $files = [
            CMS_M365MATRICES_PLUGIN_DIR . 'includes/class-source.php',
            CMS_M365MATRICES_PLUGIN_DIR . 'includes/class-installer.php',
            CMS_M365MATRICES_PLUGIN_DIR . 'includes/class-frontend.php',
            CMS_M365MATRICES_PLUGIN_DIR . 'admin/class-admin-menu.php',
            CMS_M365MATRICES_PLUGIN_DIR . 'admin/class-admin-pages.php',
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                require_once $file;
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
        \CMS\Hooks::addAction('cms_admin_menu', [CMS_M365MATRICES_Admin_Menu::class, 'register'], 10);
        \CMS\Hooks::addAction('register_routes', [CMS_M365MATRICES_Frontend::class, 'instance'], 10);
    }

    public function init_plugin(): void
    {
        CMS_M365MATRICES_Source::load_runtime();
        CMS_M365MATRICES_Installer::maybe_install();
        CMS_M365MATRICES_Frontend::instance();
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin !== self::PLUGIN_SLUG) {
            return;
        }

        CMS_M365MATRICES_Source::load_runtime();
        CMS_M365MATRICES_Installer::install();
    }
}

CMS_M365MATRICES::instance();
}