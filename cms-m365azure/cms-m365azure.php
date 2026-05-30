<?php
/**
 * Plugin Name: CMS M365 Azure
 * Plugin URI:  https://365network.de/cms-m365azure
 * Description: Steuerbare Azure-Service-Übersichten mit Kategorien, Inhaltsverzeichnis und Card-Layout.
 * Version:     1.1.7
 * Author:      365 Network
 * Author URI:  https://365network.de
 *
 * @package CMS_M365Azure
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CMS_M365AZURE_VERSION', '1.1.7');
define('CMS_M365AZURE_DB_VERSION', '1');
define('CMS_M365AZURE_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_M365AZURE_PLUGIN_URL', '/plugins/cms-m365azure/');

final class CMS_M365Azure
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
        $files = [
            CMS_M365AZURE_PLUGIN_DIR . 'includes/class-installer.php',
            CMS_M365AZURE_PLUGIN_DIR . 'includes/class-repository.php',
            CMS_M365AZURE_PLUGIN_DIR . 'includes/class-frontend.php',
            CMS_M365AZURE_PLUGIN_DIR . 'admin/class-admin-menu.php',
            CMS_M365AZURE_PLUGIN_DIR . 'admin/class-admin-pages.php',
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
        \CMS\Hooks::addAction('cms_admin_menu', [CMS_M365Azure_Admin_Menu::class, 'register'], 10);
        \CMS\Hooks::addAction('register_routes', [CMS_M365Azure_Frontend::class, 'instance'], 10);
    }

    public function init_plugin(): void
    {
        if (class_exists('CMS_M365Azure_Installer')) {
            CMS_M365Azure_Installer::maybe_install();
        }
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin === 'cms-m365azure' && class_exists('CMS_M365Azure_Installer')) {
            CMS_M365Azure_Installer::install();
        }
    }
}

CMS_M365Azure::instance();
