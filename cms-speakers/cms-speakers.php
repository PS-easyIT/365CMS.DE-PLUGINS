<?php
/**
 * Plugin Name: CMS Speakers
 * Plugin URI: https://365network.de/cms-speakers
 * Description: Verwaltung von Speaker-Profilen mit Card-Ansicht, Detailseiten, Topics und Presentations
 * Version: 3.0.19
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
defined('CMS_SPEAKERS_VERSION') || define('CMS_SPEAKERS_VERSION', '3.0.19');
defined('CMS_SPEAKERS_PLUGIN_DIR') || define('CMS_SPEAKERS_PLUGIN_DIR', dirname(__FILE__) . '/');
defined('CMS_SPEAKERS_PLUGIN_URL') || define('CMS_SPEAKERS_PLUGIN_URL', '/plugins/cms-speakers/');
defined('CMS_SPEAKERS_TEXT_DOMAIN') || define('CMS_SPEAKERS_TEXT_DOMAIN', 'cms-speakers');

/**
 * Hauptklasse für CMS Speakers Plugin
 *
 * @since 1.0.0
 */
if (!class_exists('CMS_Speakers', false)) {
final class CMS_Speakers
{
    private static ?self $instance = null;
    private bool $components_bootstrapped = false;

    private string $version = '3.0.19';
    private string $plugin_dir;
    private string $plugin_url;
    private string $text_domain = 'cms-speakers';
    private ?string $request_path_cache = null;

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
        $this->bootstrap_hook_components();
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

    private function bootstrap_hook_components(): void
    {
        if (!class_exists('CMS\\Hooks')) {
            return;
        }

        foreach (['CMS_Speakers_Post_Type', 'CMS_Speakers_Meta_Boxes', 'CMS_Speakers_Template_Loader', 'CMS_Speakers_Shortcode', 'CMS_Speakers_Admin', 'CMS_Speakers_Member_Dashboard'] as $class) {
            if (class_exists($class, false)) {
                $class::instance();
            }
        }
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
            CMS\Hooks::addAction('plugin_deactivated', [$this, 'on_deactivation'], 10);
            CMS\Hooks::addAction('plugin_uninstalled', [$this, 'on_uninstall'], 10);
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
            try {
                CMS_Speakers_Database::instance()->create_tables();
            } catch (\Throwable $e) {
                error_log('CMS Speakers activation skipped: ' . $e->getMessage());
            }
        }

        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::doAction('cms_speakers_activated');
        }
    }

    public function on_deactivation(string $plugin): void
    {
        if ($plugin !== 'cms-speakers') {
            return;
        }

        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::doAction('cms_speakers_deactivated');
        }
    }

    public function on_uninstall(string $plugin): void
    {
        if ($plugin !== 'cms-speakers' || !class_exists('CMS_Speakers_Database')) {
            return;
        }

        try {
            CMS_Speakers_Database::instance()->drop_tables();
        } catch (\Throwable $e) {
            error_log('CMS Speakers uninstall skipped: ' . $e->getMessage());
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
        $schema_version = '3.0.3';
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
        if (!$this->is_speaker_frontend_route()) {
            return;
        }

        $this->enqueue_style_file('plugin-base.css');
        $this->enqueue_style_file('style.css');
        if ($this->is_speaker_detail_route()) {
            $this->enqueue_style_file('single.css');
        }
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
        if (!$this->is_speaker_frontend_route()) {
            return;
        }

        $js_file = $this->plugin_dir . 'assets/js/script.js';
        if (file_exists($js_file)) {
            $js_url = $this->plugin_url . 'assets/js/script.js';
            $js_version = (string) filemtime($js_file);
            echo '<script src="' . htmlspecialchars($js_url . '?v=' . $js_version, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
        }
    }

    private function is_speaker_frontend_route(): bool
    {
        $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($requestMethod, ['GET', 'HEAD'], true)) {
            return false;
        }

        $path = $this->get_request_path();
        if ($path === '' || str_starts_with($path, '/admin/')) {
            return false;
        }

        return $path === '/speakers' || str_starts_with($path, '/speakers/');
    }

    private function is_speaker_detail_route(): bool
    {
        $path = rtrim($this->get_request_path(), '/');

        return preg_match('#^/speakers/[^/]+$#', $path) === 1;
    }

    private function get_request_path(): string
    {
        if ($this->request_path_cache !== null) {
            return $this->request_path_cache;
        }

        $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $this->request_path_cache = $path !== '' ? $path : '/';

        return $this->request_path_cache;
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
}

// Plugin initialisieren
if (class_exists('CMS_Speakers', false)) {
    CMS_Speakers::instance();
}
