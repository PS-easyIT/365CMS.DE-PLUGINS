<?php
/**
 * Plugin Name: CMS M365 Calculator
 * Plugin URI: https://365network.de/cms-m365calculator
 * Description: Modulare Microsoft-365-Rechner-Toolbox mit Shared-Mailbox-vs.-Lizenz-Rechner und vorbereiteter Modularchitektur.
 * Version: 1.0.1
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CMS_M365CALCULATOR_VERSION', '1.0.1');
define('CMS_M365CALCULATOR_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_M365CALCULATOR_PLUGIN_URL', '/plugins/cms-m365calculator/');

final class CMS_M365CALCULATOR
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
        $inc = CMS_M365CALCULATOR_PLUGIN_DIR . 'includes/';
        $admin = CMS_M365CALCULATOR_PLUGIN_DIR . 'admin/';

        $files = [
            $inc . 'class-catalog.php',
            $inc . 'class-icons.php',
            $inc . 'class-tool-registry.php',
            $inc . 'class-shared-mailbox-calculator.php',
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
        \CMS\Hooks::addAction('cms_admin_menu', [CMS_M365CALCULATOR_Admin_Menu::class, 'register'], 10);
        \CMS\Hooks::addAction('register_routes', [CMS_M365CALCULATOR_Frontend::class, 'instance'], 10);
    }

    public function init_plugin(): void
    {
        CMS_M365CALCULATOR_Frontend::instance();
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin !== 'cms-m365calculator') {
            return;
        }

        CMS_M365CALCULATOR_Frontend::instance();
    }
}

CMS_M365CALCULATOR::instance();
