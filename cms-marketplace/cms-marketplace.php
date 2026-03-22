<?php
/**
 * Plugin Name: 365CMS Marketplace
 * Description: Zentrale Marketplace-Verwaltung für Plugins und Themes mit ZIP-Paketen und JSON-Feeds unter /marketplace.
 * Version: 1.0.0
 * Author: 365 Network
 * Requires CMS: 2.6.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (defined('CMS_MARKETPLACE_BOOTSTRAPPED')) {
    return;
}

define('CMS_MARKETPLACE_BOOTSTRAPPED', true);
defined('CMS_MARKETPLACE_VERSION') || define('CMS_MARKETPLACE_VERSION', '1.0.0');
defined('CMS_MARKETPLACE_PLUGIN_DIR') || define('CMS_MARKETPLACE_PLUGIN_DIR', __DIR__ . DIRECTORY_SEPARATOR);
defined('CMS_MARKETPLACE_PLUGIN_URL') || define('CMS_MARKETPLACE_PLUGIN_URL', '/plugins/cms-marketplace/');

require_once CMS_MARKETPLACE_PLUGIN_DIR . 'includes/class-marketplace-repository.php';
require_once CMS_MARKETPLACE_PLUGIN_DIR . 'includes/class-marketplace-service.php';
require_once CMS_MARKETPLACE_PLUGIN_DIR . 'includes/class-marketplace-admin.php';
require_once CMS_MARKETPLACE_PLUGIN_DIR . 'includes/class-marketplace-public.php';

final class CMS_Marketplace
{
    private static ?self $instance = null;
    private CMS_Marketplace_Admin $admin;
    private CMS_Marketplace_Public $public;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $service = new CMS_Marketplace_Service(new CMS_Marketplace_Repository());
        $this->admin = new CMS_Marketplace_Admin($service);
        $this->public = new CMS_Marketplace_Public($service);
        $this->registerHooks();
    }

    private function registerHooks(): void
    {
        if (!class_exists('CMS\Hooks')) {
            return;
        }

        CMS\Hooks::addAction('cms_init', [$this, 'boot'], 10);
        CMS\Hooks::addAction('register_routes', [$this->public, 'registerRoutes'], 20);
        CMS\Hooks::addAction('plugin_activated', [$this, 'handleActivation'], 10);
        CMS\Hooks::addAction('cms_admin_menu', [$this->admin, 'registerPages'], 20);
        CMS\Hooks::addAction('admin_head', [$this->admin, 'enqueueStyles'], 10);
    }

    public function boot(): void
    {
        try {
            if ($this->public->isCurrentRequest()) {
                $this->admin->boot();
                return;
            }

            if ($this->admin->isCurrentRequest()) {
                $this->admin->boot();
            }
        } catch (\Throwable $e) {
            error_log('cms-marketplace boot error: ' . $e->getMessage());
        }
    }

    public function handleActivation(string $plugin): void
    {
        if ($plugin !== 'cms-marketplace') {
            return;
        }

        try {
            $this->admin->boot();
        } catch (\Throwable $e) {
            error_log('cms-marketplace activation error: ' . $e->getMessage());
        }
    }
}

if (class_exists('CMS\Hooks')) {
    CMS\Hooks::addAction('plugins_loaded', static function (): void {
        CMS_Marketplace::instance();
    });
} else {
    CMS_Marketplace::instance();
}
