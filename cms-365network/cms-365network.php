<?php
/**
 * Plugin Name: 365 | Landing
 * Plugin Slug: cms-365network
 * Plugin URI: https://365network.de/cms-365network
 * Description: Domainbasierte 365network-HubSite/Landingpage mit konfigurierbarem Layout für Events, Speaker, Firmen und Experten.
 * Version: 1.0.47
 * Author: Andreas Hepp
 * Author URI: https://365network.de
 * Requires: 365CMS >= 2.0
 *
 * @package CMS_365NETWORK
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CMS_365NETWORK_VERSION', '1.0.47');
define('CMS_365NETWORK_PLUGIN_DIR', function_exists('cms_plugin_path') ? rtrim((string) cms_plugin_path('cms-365network'), '/\\') . DIRECTORY_SEPARATOR : dirname(__FILE__) . '/');
define('CMS_365NETWORK_PLUGIN_URL', function_exists('cms_plugin_url') ? rtrim((string) cms_plugin_url('cms-365network'), '/') . '/' : '/plugins/cms-365network/');
define('CMS_365NETWORK_SHARED_ADMIN_CONTRACT', dirname(CMS_365NETWORK_PLUGIN_DIR) . '/shared/admin/plugin-admin-contract.php');

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
            if (function_exists('cms_register_hook')) {
                cms_register_hook('plugin_activate', 'cms_365network', 'hub_install');
                cms_register_hook('plugin_deactivate', 'cms_365network', 'hub_uninstall');
            }

            CMS\Hooks::addAction('cms_init', [$this, 'init_plugin'], 10);
            CMS\Hooks::addAction('plugin_activated', [$this, 'on_activation'], 10);
            CMS\Hooks::addAction('plugin_deactivated', [$this, 'on_deactivation'], 10);
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
            hub_install($pluginSlug);
        }

        public function on_deactivation(string $pluginSlug): void
        {
            hub_uninstall($pluginSlug);
        }

        public function version(): string
        {
            return $this->version;
        }
    }
}

if (!function_exists('hub_install')) {
    function hub_install(string $pluginSlug = 'cms-365network'): void
    {
        if (!in_array($pluginSlug, ['cms-365network', 'cms_365network'], true)) {
            return;
        }

        if (class_exists('CMS_365NETWORK_Database', false)) {
            CMS_365NETWORK_Database::instance()->create_tables();
        }
    }
}

if (!function_exists('hub_uninstall')) {
    function hub_uninstall(string $pluginSlug = 'cms-365network'): void
    {
        if (!in_array($pluginSlug, ['cms-365network', 'cms_365network'], true)) {
            return;
        }

        if (class_exists('CMS\\Hooks')) {
            CMS\Hooks::doAction('cms_365network_deactivated', $pluginSlug);
        }
    }
}

if (!function_exists('hub_admin_page')) {
    function hub_admin_page(): void
    {
        if (class_exists('CMS_365NETWORK_Admin', false) && method_exists('CMS_365NETWORK_Admin', 'dispatch_admin_request')) {
            CMS_365NETWORK_Admin::dispatch_admin_request();
            return;
        }

        if (is_file(CMS_365NETWORK_SHARED_ADMIN_CONTRACT)) {
            require_once CMS_365NETWORK_SHARED_ADMIN_CONTRACT;
        }

        if (function_exists('cms_plugin_admin_layout_start') && function_exists('cms_plugin_admin_emit_notice') && function_exists('cms_plugin_admin_layout_end')) {
            cms_plugin_admin_layout_start('365 | Landing', 'cms-365network');
            cms_plugin_admin_emit_notice(
                'Die 365 | Landing-Adminseite konnte nicht initialisiert werden. Bitte Plugin-Dateien und Server-Log prüfen.',
                'error',
                'cms-365network admin callback missing class=CMS_365NETWORK_Admin method=dispatch_admin_request'
            );
            cms_plugin_admin_layout_end();
            return;
        }

        error_log('[cms-365network] admin callback missing class=CMS_365NETWORK_Admin method=dispatch_admin_request');
        echo '<div class="alert alert-error" role="alert">Die 365 | Landing-Adminseite konnte nicht geladen werden.</div>';
    }
}

CMS_365NETWORK::instance();
