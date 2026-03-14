<?php
/**
 * Plugin Name: CMS Promos
 * Plugin URI: https://365network.de/cms-promos
 * Description: Promo- und Kampagnenverwaltung für Banner, Teaserflächen, Platzierungen und klickbare Aktionsboxen im 365CMS.
 * Version: 1.1.0
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_Promos
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CMS_PROMOS_VERSION', '1.1.0');
define('CMS_PROMOS_DB_VERSION', '1.1.0');
define('CMS_PROMOS_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_PROMOS_PLUGIN_URL', '/plugins/cms-promos/');

final class CMS_Promos
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
            CMS_PROMOS_PLUGIN_DIR . 'includes/class-installer.php',
            CMS_PROMOS_PLUGIN_DIR . 'includes/class-repository.php',
            CMS_PROMOS_PLUGIN_DIR . 'includes/class-public-controller.php',
            CMS_PROMOS_PLUGIN_DIR . 'admin/class-admin-menu.php',
            CMS_PROMOS_PLUGIN_DIR . 'admin/class-admin-pages.php',
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
        \CMS\Hooks::addAction('cms_admin_menu', [CMS_Promos_Admin_Menu::class, 'register'], 10);
        \CMS\Hooks::addAction('register_routes', [CMS_Promos_Public_Controller::instance(), 'register_routes'], 10);
        \CMS\Hooks::addAction('head', [$this, 'enqueue_styles'], 20);
    }

    public function init_plugin(): void
    {
        CMS_Promos_Installer::maybe_install();
        CMS_Promos_Repository::instance();
        CMS_Promos_Public_Controller::instance();
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin !== 'cms-promos') {
            return;
        }

        CMS_Promos_Installer::install();
    }

    public function on_uninstall(string $plugin): void
    {
        if ($plugin !== 'cms-promos') {
            return;
        }
    }

    public function enqueue_styles(): void
    {
        $css = CMS_PROMOS_PLUGIN_DIR . 'assets/css/promos-public.css';
        if (!file_exists($css)) {
            return;
        }

        echo '<link rel="stylesheet" href="'
            . htmlspecialchars(CMS_PROMOS_PLUGIN_URL . 'assets/css/promos-public.css?v=' . filemtime($css), ENT_QUOTES, 'UTF-8')
            . '">' . "\n";
    }
}

CMS_Promos::instance();
