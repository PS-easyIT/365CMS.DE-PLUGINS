<?php
/**
 * Plugin Name: CMS Feed
 * Plugin URI: https://365network.de/cms-feed
 * Description: RSS-Feed-Aggregator mit Kategorie-Bereichen, Public Pages, Design-Einstellungen und E-Mail-Digest
 * Version: 1.0.0
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_Feed
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CMS_FEED_VERSION',    '1.0.0');
define('CMS_FEED_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_FEED_PLUGIN_URL', '/plugins/cms-feed/');

final class CMS_Feed
{
    private static ?self $instance = null;
    private string $version = '1.0.0';
    private string $plugin_dir;
    private string $plugin_url;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->plugin_dir = CMS_FEED_PLUGIN_DIR;
        $this->plugin_url = CMS_FEED_PLUGIN_URL;
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies(): void
    {
        $includes = $this->plugin_dir . 'includes/';
        $files = [
            'class-database.php',
            'class-rss-fetcher.php',
            'class-template-loader.php',
            'class-public-controller.php',
            'class-email-digest.php',
            'class-admin.php',
        ];
        foreach ($files as $file) {
            if (file_exists($includes . $file)) {
                require_once $includes . $file;
            }
        }
    }

    private function init_hooks(): void
    {
        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::addAction('cms_init', [$this, 'init_plugin'], 10);
            CMS\Hooks::addAction('plugin_activated', [$this, 'on_activation'], 10);
            CMS\Hooks::addAction('register_routes', [$this, 'register_routes'], 10);
            CMS\Hooks::addAction('head', [$this, 'enqueue_styles'], 10);
            CMS\Hooks::addAction('body_end', [$this, 'enqueue_scripts'], 10);
        }
    }

    /**
     * Alle Plugin-Routen am Router registrieren.
     */
    public function register_routes($router): void
    {
        // Admin-Routen
        $admin = CMS_Feed_Admin::instance();
        $router->addRoute('GET',  '/admin/feeds', [$admin, 'admin_page']);
        $router->addRoute('POST', '/admin/feeds', [$admin, 'admin_page']);

        // Public-Routen
        $public = CMS_Feed_Public_Controller::instance();
        $public->register_routes($router);
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin === 'cms-feed' && class_exists('CMS_Feed_Database')) {
            CMS_Feed_Database::instance()->create_tables();
            CMS_Feed_Database::instance()->seed_defaults();
        }
    }

    public function init_plugin(): void
    {
        $classes = [
            'CMS_Feed_Database',
            'CMS_Feed_RSS_Fetcher',
            'CMS_Feed_Template_Loader',
            'CMS_Feed_Public_Controller',
            'CMS_Feed_Email_Digest',
            'CMS_Feed_Admin',
        ];
        foreach ($classes as $class) {
            if (class_exists($class)) {
                $class::instance();
            }
        }
    }

    public function enqueue_styles(): void
    {
        $css = $this->plugin_dir . 'assets/css/style.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="' . $this->plugin_url . 'assets/css/style.css?v=' . filemtime($css) . '">' . "\n";
        }
    }

    public function enqueue_scripts(): void
    {
        $js = $this->plugin_dir . 'assets/js/script.js';
        if (file_exists($js)) {
            echo '<script src="' . $this->plugin_url . 'assets/js/script.js?v=' . filemtime($js) . '" defer></script>' . "\n";
        }
    }

    public function get_version(): string
    {
        return $this->version;
    }

    public function get_plugin_dir(): string
    {
        return $this->plugin_dir;
    }

    public function get_plugin_url(): string
    {
        return $this->plugin_url;
    }
}

CMS_Feed::instance();
