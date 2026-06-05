<?php
/**
 * Plugin Name: CMS M365 Price Tracker
 * Plugin URI: https://365network.de/cms-m365price-tracker
 * Description: Eigenständiger Microsoft-365-Preis- und Renewal-Tracker mit Chart.js-Preisverlauf und lokalem Kosten-Tracker.
 * Version: 1.0.2
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_M365PRICETRACKER
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

defined('CMS_M365PRICETRACKER_VERSION') || define('CMS_M365PRICETRACKER_VERSION', '1.0.2');
defined('CMS_M365PRICETRACKER_PLUGIN_DIR') || define('CMS_M365PRICETRACKER_PLUGIN_DIR', dirname(__FILE__) . '/');
defined('CMS_M365PRICETRACKER_PLUGIN_URL') || define('CMS_M365PRICETRACKER_PLUGIN_URL', '/plugins/cms-m365price-tracker/');

if (!class_exists('CMS_M365PRICETRACKER', false)) {
final class CMS_M365PRICETRACKER
{
    private const PLUGIN_SLUG = 'cms-m365price-tracker';

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
        $sharedAdmin = dirname(CMS_M365PRICETRACKER_PLUGIN_DIR) . '/shared/admin/plugin-admin-contract.php';
        if (is_file($sharedAdmin) && is_readable($sharedAdmin)) {
            require_once $sharedAdmin;
        }

        $files = [
            dirname(CMS_M365PRICETRACKER_PLUGIN_DIR) . '/shared/public/plugin-public-i18n.php',
            CMS_M365PRICETRACKER_PLUGIN_DIR . 'includes/class-installer.php',
            CMS_M365PRICETRACKER_PLUGIN_DIR . 'includes/class-settings.php',
            CMS_M365PRICETRACKER_PLUGIN_DIR . 'includes/class-catalog.php',
            CMS_M365PRICETRACKER_PLUGIN_DIR . 'includes/class-microsoft-price-tracker.php',
            CMS_M365PRICETRACKER_PLUGIN_DIR . 'includes/class-frontend.php',
            CMS_M365PRICETRACKER_PLUGIN_DIR . 'admin/class-admin-pages.php',
            CMS_M365PRICETRACKER_PLUGIN_DIR . 'admin/class-admin-menu.php',
        ];

        foreach ($files as $file) {
            if (is_file($file) && is_readable($file)) {
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
        \CMS\Hooks::addAction('cms_admin_menu', [CMS_M365PRICETRACKER_Admin_Menu::class, 'register'], 10);
        \CMS\Hooks::addAction('register_routes', [CMS_M365PRICETRACKER_Admin_Menu::class, 'register_routes'], 9);
        \CMS\Hooks::addAction('register_routes', [CMS_M365PRICETRACKER_Frontend::class, 'register_routes_hook'], 10);
    }

    public function init_plugin(): void
    {
        CMS_M365PRICETRACKER_Installer::maybe_install();
        CMS_M365PRICETRACKER_Frontend::instance();
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin !== self::PLUGIN_SLUG) {
            return;
        }

        CMS_M365PRICETRACKER_Installer::install();
        CMS_M365PRICETRACKER_Frontend::instance();
    }
}

CMS_M365PRICETRACKER::instance();
}
