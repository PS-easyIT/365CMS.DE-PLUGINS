<?php
/**
 * Plugin Name: CMS M365 License
 * Plugin URI: https://365network.de/cms-m365license
 * Description: Microsoft-365-Lizenzberater mit Bedarfsanalyse, Paketverwaltung, PDF-Export und Publicsite im aktiven Theme.
 * Version: 1.4.0
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CMS_M365LIC_VERSION', '1.4.0');
define('CMS_M365LIC_DB_VERSION', '7');
define('CMS_M365LIC_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_M365LIC_PLUGIN_URL', '/plugins/cms-m365lic/');

final class CMS_M365LIC
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
        $inc   = CMS_M365LIC_PLUGIN_DIR . 'includes/';
        $admin = CMS_M365LIC_PLUGIN_DIR . 'admin/';

        $files = [
            $inc . 'class-catalog.php',
            $inc . 'class-installer.php',
            $inc . 'class-repository.php',
            $inc . 'class-calculator.php',
            $inc . 'class-pdf-export.php',
            $inc . 'class-frontend.php',
            $admin . 'class-admin-menu.php',
            $admin . 'class-admin-pages.php',
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
        \CMS\Hooks::addAction('plugin_uninstalled', [$this, 'on_uninstall'], 10);
        \CMS\Hooks::addAction('cms_admin_menu', [CMS_M365LIC_Admin_Menu::class, 'register'], 10);
        \CMS\Hooks::addAction('register_routes', [CMS_M365LIC_Frontend::class, 'instance'], 10);
        \CMS\Hooks::addAction('member_dashboard_init', [CMS_M365LIC_Frontend::class, 'register_member_sections'], 10);
        \CMS\Hooks::addAction('dsgvo_export_data', [$this, 'export_user_data'], 10);
        \CMS\Hooks::addAction('dsgvo_delete_data', [$this, 'delete_user_data'], 10);
    }

    public function init_plugin(): void
    {
        CMS_M365LIC_Installer::maybe_install();
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin !== 'cms-m365lic') {
            return;
        }

        CMS_M365LIC_Installer::install();
    }

    public function on_uninstall(string $plugin): void
    {
        if ($plugin !== 'cms-m365lic') {
            return;
        }

        CMS_M365LIC_Installer::uninstall();
    }

    public function export_user_data(int $userId): void
    {
        CMS_M365LIC_Repository::instance()->export_user_data($userId);
    }

    public function delete_user_data(int $userId): void
    {
        CMS_M365LIC_Repository::instance()->delete_user_data($userId);
    }
}

CMS_M365LIC::instance();
