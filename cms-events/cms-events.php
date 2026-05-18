<?php
/**
 * Plugin Name: CMS Events
 * Plugin URI: https://365network.de/cms-events
 * Description: Verwaltung von Events mit Speakeranbindung, Veranstaltern aus cms-companies und voller Metaverwaltung
 * Version: 3.0.2
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_Events
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

define('CMS_EVENTS_VERSION', '3.0.2');
define('CMS_EVENTS_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_EVENTS_PLUGIN_URL', '/plugins/cms-events/');

final class CMS_Events {
    private static ?self $instance = null;
    private bool $components_bootstrapped = false;
    private string $version = '3.0.1';
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
        if ($this->can_bootstrap_components()) {
            $this->bootstrap_components();
        }
    }

    private function load_dependencies(): void {
        $includes = $this->plugin_dir . 'includes/';
        foreach (['class-database.php', 'class-post-type.php', 'class-meta-boxes.php', 'class-template-loader.php', 'class-shortcode.php', 'class-admin.php', 'class-member-dashboard.php'] as $file) {
            if (file_exists($includes . $file)) require_once $includes . $file;
        }
    }

    private function can_bootstrap_components(): bool {
        return class_exists('CMS\\Hooks') && class_exists('CMS\\Database');
    }

    private function bootstrap_components(): void {
        if ($this->components_bootstrapped) {
            return;
        }

        $this->components_bootstrapped = true;

        foreach (['CMS_Events_Database', 'CMS_Events_Post_Type', 'CMS_Events_Meta_Boxes', 'CMS_Events_Template_Loader', 'CMS_Events_Shortcode', 'CMS_Events_Admin', 'CMS_Events_Member_Dashboard'] as $class) {
            if (class_exists($class)) $class::instance();
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
        if ($plugin === 'cms-events' && class_exists('CMS\\Database') && class_exists('CMS_Events_Database')) {
            CMS_Events_Database::instance()->create_tables();
            if (class_exists('CMS\Hooks')) CMS\Hooks::doAction('event_created');
        }
    }

    public function init_plugin(): void {
        if (!$this->can_bootstrap_components()) {
            return;
        }
        $this->ensure_schema();
        $this->bootstrap_components();
    }

    private function ensure_schema(): void {
        if (!class_exists('CMS_Events_Database')) {
            return;
        }

        $db = CMS_Events_Database::instance();
        $settings = method_exists($db, 'get_settings') ? $db->get_settings() : [];
        $schema_version = '3.0.0';
        if (($settings['schema_version'] ?? '') === $schema_version) {
            return;
        }

        try {
            $db->create_tables();
            if (method_exists($db, 'save_settings')) {
                $db->save_settings(['schema_version' => $schema_version]);
            }
        } catch (\Throwable $e) {
            error_log('CMS Events: ' . $e->getMessage());
        }
    }

    public function enqueue_styles(): void {
        $this->enqueue_style_file('plugin-base.css');
        $this->enqueue_style_file('style.css');
        $this->enqueue_style_file('single.css');
    }

    private function enqueue_style_file(string $file): void {
        $css = $this->plugin_dir . 'assets/css/style.css';
        $css = $this->plugin_dir . 'assets/css/' . $file;
        if (file_exists($css)) {
            $cssVersion = (string) filemtime($css);
            $href = $this->plugin_url . 'assets/css/' . $file . '?v=' . $cssVersion;
            echo '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
    }

    public function enqueue_scripts(): void {
        $js = $this->plugin_dir . 'assets/js/script.js';
        if (file_exists($js)) {
            $jsVersion = (string) filemtime($js);
            $src = $this->plugin_url . 'assets/js/script.js?v=' . $jsVersion;
            echo '<script src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
        }
    }

    public function get_version(): string { return $this->version; }
    public function get_plugin_dir(): string { return $this->plugin_dir; }
    public function get_plugin_url(): string { return $this->plugin_url; }
}
CMS_Events::instance();
