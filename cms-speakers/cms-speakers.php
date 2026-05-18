<?php
/**
 * Plugin Name: CMS Speakers
 * Plugin URI: https://365network.de/cms-speakers
 * Description: Verwaltung von Speaker-Profilen mit Card-Ansicht, Detail seiten, Topics und Presentations
 * Version: 3.0.1
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_Speakers
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Plugin Constants
define('CMS_SPEAKERS_VERSION', '3.0.1');
define('CMS_SPEAKERS_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_SPEAKERS_PLUGIN_URL', '/plugins/cms-speakers/');
define('CMS_SPEAKERS_TEXT_DOMAIN', 'cms-speakers');

/**
 * Hauptklasse für CMS Speakers Plugin
 *
 * @since 1.0.0
 */
final class CMS_Speakers
{
    private static ?self $instance = null;
    private bool $components_bootstrapped = false;

    private string $version = '3.0.1';
    private string $plugin_dir;
    private string $plugin_url;
    private string $text_domain = 'cms-speakers';

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->plugin_dir = CMS_SPEAKERS_PLUGIN_DIR;
        $this->plugin_url = CMS_SPEAKERS_PLUGIN_URL;

        $this->load_dependencies();
        $this->init_hooks();
        if ($this->can_bootstrap_components()) {
            $this->bootstrap_components();
        }
    }

    private function load_dependencies(): void
    {
        $includes_dir = $this->plugin_dir . 'includes/';

        $files = [
            'class-database.php',
            'class-post-type.php',
            'class-meta-boxes.php',
            'class-template-loader.php',
            'class-shortcode.php',
            'class-admin.php',
            'class-member-dashboard.php',
        ];

        foreach ($files as $file) {
            $filepath = $includes_dir . $file;
            if (file_exists($filepath)) {
                require_once $filepath;
            } else {
                if (defined('CMS_DEBUG') && CMS_DEBUG) {
                    error_log("[CMS Speakers] Datei nicht gefunden: {$file}");
                }
            }
        }
    }

    private function can_bootstrap_components(): bool
    {
        return class_exists('CMS\\Hooks') && class_exists('CMS\\Database');
    }

    private function bootstrap_components(): void
    {
        if ($this->components_bootstrapped) {
            return;
        }

        $this->components_bootstrapped = true;

        foreach (['CMS_Speakers_Database', 'CMS_Speakers_Post_Type', 'CMS_Speakers_Meta_Boxes', 'CMS_Speakers_Template_Loader', 'CMS_Speakers_Shortcode', 'CMS_Speakers_Admin', 'CMS_Speakers_Member_Dashboard'] as $class) {
            if (class_exists($class)) {
                $class::instance();
            }
        }
    }

    private function init_hooks(): void
    {
        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::addAction('cms_init', [$this, 'init_plugin'], 10);
            CMS\Hooks::addAction('plugin_activated', [$this, 'on_activation'], 10);
            CMS\Hooks::addAction('head', [$this, 'enqueue_styles'], 10);
            CMS\Hooks::addAction('body_end', [$this, 'enqueue_scripts'], 10);
        }
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin !== 'cms-speakers') {
            return;
        }

        if (class_exists('CMS\\Database') && class_exists('CMS_Speakers_Database')) {
            CMS_Speakers_Database::instance()->create_tables();
        }

        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::doAction('speaker_created');
        }
    }

    public function init_plugin(): void
    {
        if (!$this->can_bootstrap_components()) {
            return;
        }

        $this->ensure_schema();
        $this->bootstrap_components();
    }

    private function ensure_schema(): void
    {
        if (!class_exists('CMS_Speakers_Database')) {
            return;
        }

        $db = CMS_Speakers_Database::instance();
        $settings = method_exists($db, 'get_settings') ? $db->get_settings() : [];
        $schema_version = '3.0.1';
        if (($settings['schema_version'] ?? '') === $schema_version) {
            return;
        }

        try {
            $db->create_tables();
            if (method_exists($db, 'save_settings')) {
                $db->save_settings(['schema_version' => $schema_version]);
            }
        } catch (\Throwable $e) {
            error_log('CMS Speakers DB: ' . $e->getMessage());
        }
    }

    public function enqueue_styles(): void
    {
        $this->enqueue_style_file('plugin-base.css');
        $this->enqueue_style_file('style.css');
        $this->enqueue_style_file('single.css');
    }

    private function enqueue_style_file(string $file): void
    {
        $css_file = $this->plugin_dir . 'assets/css/' . $file;
        if (!file_exists($css_file)) {
            return;
        }

        $href = $this->plugin_url . 'assets/css/' . $file . '?v=' . (string) filemtime($css_file);
        echo '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }

    public function enqueue_scripts(): void
    {
        $js_file = $this->plugin_dir . 'assets/js/script.js';
        if (file_exists($js_file)) {
            $js_url = $this->plugin_url . 'assets/js/script.js';
            $js_version = (string) filemtime($js_file);
            echo '<script src="' . htmlspecialchars($js_url . '?v=' . $js_version, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
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

// Plugin initialisieren
CMS_Speakers::instance();
