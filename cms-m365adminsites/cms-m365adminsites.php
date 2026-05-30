<?php
/**
 * Plugin Name: CMS M365 Adminsites
 * Plugin URI: https://365network.de/cms-m365adminsites
 * Description: Kuratierte Microsoft-365-, Azure-, Security-, Power-Platform- und Education-Portal-Sammlung mit konfigurierbarer Public-Übersicht.
 * Version: 1.0.1
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_M365ADMINSITES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

defined('CMS_M365ADMINSITES_VERSION') || define('CMS_M365ADMINSITES_VERSION', '1.0.1');
defined('CMS_M365ADMINSITES_PLUGIN_DIR') || define('CMS_M365ADMINSITES_PLUGIN_DIR', dirname(__FILE__) . '/');
defined('CMS_M365ADMINSITES_PLUGIN_URL') || define('CMS_M365ADMINSITES_PLUGIN_URL', '/plugins/cms-m365adminsites/');

if (!class_exists('CMS_M365ADMINSITES', false)) {
final class CMS_M365ADMINSITES
{
    private const PLUGIN_SLUG = 'cms-m365adminsites';

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
            CMS_M365ADMINSITES_PLUGIN_DIR . 'includes/class-settings.php',
            CMS_M365ADMINSITES_PLUGIN_DIR . 'includes/class-repository.php',
            CMS_M365ADMINSITES_PLUGIN_DIR . 'includes/class-installer.php',
            CMS_M365ADMINSITES_PLUGIN_DIR . 'includes/class-widget.php',
            CMS_M365ADMINSITES_PLUGIN_DIR . 'includes/class-frontend.php',
            CMS_M365ADMINSITES_PLUGIN_DIR . 'admin/class-admin-menu.php',
            CMS_M365ADMINSITES_PLUGIN_DIR . 'admin/class-admin-pages.php',
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
        \CMS\Hooks::addAction('cms_admin_menu', [CMS_M365ADMINSITES_Admin_Menu::class, 'register'], 10);
        \CMS\Hooks::addAction('register_routes', [CMS_M365ADMINSITES_Frontend::class, 'instance'], 10);
    }

    public function init_plugin(): void
    {
        CMS_M365ADMINSITES_Installer::maybe_install();
        CMS_M365ADMINSITES_Frontend::instance();
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin !== self::PLUGIN_SLUG) {
            return;
        }

        CMS_M365ADMINSITES_Installer::install();
    }
}

CMS_M365ADMINSITES::instance();
}
