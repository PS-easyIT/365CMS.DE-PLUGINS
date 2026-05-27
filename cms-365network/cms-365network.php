<?php
/**
 * Plugin Name: CMS 365NETWORK
 * Plugin URI: https://365network.de/cms-365network
 * Description: Domainbasierte 365network-HubSite/Landingpage mit konfigurierbarem Layout für Events, Speaker, Firmen und Experten.
 * Version: 1.0.1
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_365NETWORK
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CMS_365NETWORK_VERSION', '1.0.1');
define('CMS_365NETWORK_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_365NETWORK_PLUGIN_URL', '/plugins/cms-365network/');

if (!class_exists('CMS_365NETWORK', false)) {
    final class CMS_365NETWORK
    {
        private static ?self $instance = null;
        private string $version = CMS_365NETWORK_VERSION;

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
                'includes/class-database.php',
                'includes/class-admin.php',
                'includes/class-public.php',
            ];

            foreach ($files as $file) {
                $path = CMS_365NETWORK_PLUGIN_DIR . $file;
                if (is_file($path)) {
                    require_once $path;
                }
            }
        }

        private function init_hooks(): void
        {
            CMS\Hooks::addAction('cms_init', [$this, 'init_plugin'], 10);
            CMS\Hooks::addAction('plugin_activated', [$this, 'on_activation'], 10);
        }

        public function init_plugin(): void
        {
            if (class_exists('CMS_365NETWORK_Admin', false)) {
                CMS_365NETWORK_Admin::instance();
            }

            if (class_exists('CMS_365NETWORK_Public', false)) {
                CMS_365NETWORK_Public::instance();
            }
        }

        public function on_activation(string $pluginSlug): void
        {
            if ($pluginSlug !== 'cms-365network') {
                return;
            }

            if (class_exists('CMS_365NETWORK_Database', false)) {
                CMS_365NETWORK_Database::instance()->create_tables();
            }
        }

        public function version(): string
        {
            return $this->version;
        }
    }
}

CMS_365NETWORK::instance();
