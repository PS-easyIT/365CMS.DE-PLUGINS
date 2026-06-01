<?php
/**
 * Plugin Name: CMS M365 Copilot
 * Plugin URI: https://365network.de/cms-m365copilot
 * Description: Admin-konfigurierbare Copilot-Landingpage im PHINIT-Stil für Beratung, Lizenzvertrieb und Microsoft-Copilot-Inhalte.
 * Version: 1.0.0
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_M365Copilot
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

defined('CMS_M365COPILOT_VERSION') || define('CMS_M365COPILOT_VERSION', '1.0.0');
defined('CMS_M365COPILOT_PLUGIN_DIR') || define('CMS_M365COPILOT_PLUGIN_DIR', dirname(__FILE__) . '/');
defined('CMS_M365COPILOT_PLUGIN_URL') || define('CMS_M365COPILOT_PLUGIN_URL', '/plugins/cms-m365copilot/');

final class CMS_M365Copilot
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
        $sharedAdmin = dirname(rtrim(CMS_M365COPILOT_PLUGIN_DIR, '/\\')) . '/shared/admin/plugin-admin-contract.php';
        if (is_file($sharedAdmin)) {
            require_once $sharedAdmin;
        }

        foreach ([
            'includes/class-installer.php',
            'includes/class-settings.php',
            'includes/class-frontend.php',
            'admin/class-admin-pages.php',
            'admin/class-admin-menu.php',
        ] as $file) {
            $path = CMS_M365COPILOT_PLUGIN_DIR . $file;
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
        \CMS\Hooks::addAction('cms_admin_menu', [CMS_M365Copilot_Admin_Menu::class, 'register'], 10);
        \CMS\Hooks::addAction('register_routes', [CMS_M365Copilot_Admin_Menu::class, 'register_routes'], 9);
        \CMS\Hooks::addAction('register_routes', [CMS_M365Copilot_Frontend::class, 'instance'], 10);
    }

    public function init_plugin(): void
    {
        CMS_M365Copilot_Installer::maybe_install();
        CMS_M365Copilot_Frontend::instance();
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin !== 'cms-m365copilot') {
            return;
        }

        CMS_M365Copilot_Installer::install();
        CMS_M365Copilot_Frontend::instance();
    }
}

CMS_M365Copilot::instance();
