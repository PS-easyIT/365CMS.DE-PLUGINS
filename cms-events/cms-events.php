<?php
/**
 * Plugin Name: CMS Events
 * Plugin URI: https://365network.de/cms-events
 * Description: Verwaltung von Events mit Speakeranbindung, Veranstaltern aus cms-companies und voller Metaverwaltung
 * Version: 1.0.1
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_Events
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

define('CMS_EVENTS_VERSION', '1.0.1');
define('CMS_EVENTS_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_EVENTS_PLUGIN_URL', '/plugins/cms-events/');

final class CMS_Events {
    private static ?self $instance = null;
    private string $version = '1.0.1';
    private string $plugin_dir;
    private string $plugin_url;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        $this->plugin_dir = CMS_EVENTS_PLUGIN_DIR;
        $this->plugin_url = CMS_EVENTS_PLUGIN_URL;
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies(): void {
        $includes = $this->plugin_dir . 'includes/';
        foreach (['class-database.php', 'class-post-type.php', 'class-meta-boxes.php', 'class-template-loader.php', 'class-shortcode.php', 'class-admin.php', 'class-member-dashboard.php'] as $file) {
            if (file_exists($includes . $file)) require_once $includes . $file;
        }
    }

    private function init_hooks(): void {
        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::addAction('cms_init', [$this, 'init_plugin'], 10);
            CMS\Hooks::addAction('plugin_activated', [$this, 'on_activation'], 10);
            CMS\Hooks::addAction('head', [$this, 'enqueue_styles'], 10);
            CMS\Hooks::addAction('body_end', [$this, 'enqueue_scripts'], 10);
        }
    }

    public function on_activation(string $plugin): void {
        if ($plugin === 'cms-events' && class_exists('CMS_Events_Database')) {
            CMS_Events_Database::instance()->create_tables();
            if (class_exists('CMS\Hooks')) CMS\Hooks::doAction('event_created');
        }
    }

    public function init_plugin(): void {
        foreach (['CMS_Events_Database', 'CMS_Events_Post_Type', 'CMS_Events_Meta_Boxes', 'CMS_Events_Template_Loader', 'CMS_Events_Shortcode', 'CMS_Events_Admin'] as $class) {
            if (class_exists($class)) $class::instance();
        }
    }

    public function enqueue_styles(): void {
        $css = $this->plugin_dir . 'assets/css/style.css';
        if (file_exists($css)) echo '<link rel="stylesheet" href="' . $this->plugin_url . 'assets/css/style.css?v=' . filemtime($css) . '">' . "\n";
        $single_css = $this->plugin_dir . 'assets/css/single.css';
        if (file_exists($single_css)) echo '<link rel="stylesheet" href="' . $this->plugin_url . 'assets/css/single.css?v=' . filemtime($single_css) . '">' . "\n";
    }

    public function enqueue_scripts(): void {
        $js = $this->plugin_dir . 'assets/js/script.js';
        if (file_exists($js)) echo '<script src="' . $this->plugin_url . 'assets/js/script.js?v=' . filemtime($js) . '" defer></script>' . "\n";
    }

    public function get_version(): string { return $this->version; }
    public function get_plugin_dir(): string { return $this->plugin_dir; }
    public function get_plugin_url(): string { return $this->plugin_url; }
}
CMS_Events::instance();
