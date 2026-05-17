<?php
/**
 * Plugin Name: CMS Downloads
 * Plugin URI: https://365network.de/cms-downloads
 * Description: Download-Management für öffentliche Dateien mit Kategorien, Templates und Frontend-Archiv.
 * Version: 3.0.1
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_Downloads
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CMS_DOWNLOADS_VERSION', '3.0.1');
define('CMS_DOWNLOADS_DB_VERSION', '1.0.0');
define('CMS_DOWNLOADS_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_DOWNLOADS_PLUGIN_URL', '/plugins/cms-downloads/');

final class CMS_Downloads
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
            CMS_DOWNLOADS_PLUGIN_DIR . 'includes/class-installer.php',
            CMS_DOWNLOADS_PLUGIN_DIR . 'includes/class-repository.php',
            CMS_DOWNLOADS_PLUGIN_DIR . 'includes/class-public-controller.php',
            CMS_DOWNLOADS_PLUGIN_DIR . 'admin/class-admin-menu.php',
            CMS_DOWNLOADS_PLUGIN_DIR . 'admin/class-admin-pages.php',
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
        \CMS\Hooks::addAction('cms_admin_menu', [CMS_Downloads_Admin_Menu::class, 'register'], 10);
        \CMS\Hooks::addAction('register_routes', [CMS_Downloads_Public_Controller::instance(), 'register_routes'], 10);
        \CMS\Hooks::addAction('main_nav', [CMS_Downloads_Public_Controller::instance(), 'render_nav_item'], 10);
        \CMS\Hooks::addAction('head', [$this, 'enqueue_styles'], 20);
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin !== 'cms-downloads') {
            return;
        }

        CMS_Downloads_Installer::install();
    }

    public function on_uninstall(string $plugin): void
    {
        if ($plugin !== 'cms-downloads') {
            return;
        }

        CMS_Downloads_Installer::uninstall();
    }

    public function init_plugin(): void
    {
        CMS_Downloads_Installer::maybe_install();
        CMS_Downloads_Repository::instance();
        CMS_Downloads_Public_Controller::instance();
    }

    public function enqueue_styles(): void
    {
        $css = CMS_DOWNLOADS_PLUGIN_DIR . 'assets/css/downloads-public.css';
        if (!file_exists($css)) {
            return;
        }

        echo '<link rel="stylesheet" href="'
            . htmlspecialchars(CMS_DOWNLOADS_PLUGIN_URL . 'assets/css/downloads-public.css?v=' . filemtime($css), ENT_QUOTES, 'UTF-8')
            . '">' . "\n";
    }
}

CMS_Downloads::instance();
