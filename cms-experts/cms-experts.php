<?php
/**
 * Plugin Name: CMS Experts
 * Plugin URI: https://365network.de/cms-experts
 * Description: Verwaltung von IT-Experten-Profilen mit Card-Ansicht, Detailseiten und umfangreichen Meta-Daten
 * Version: 3.0.12
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_Experts
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Plugin Constants
define('CMS_EXPERTS_VERSION', '3.0.12');
define('CMS_EXPERTS_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_EXPERTS_PLUGIN_URL', '/plugins/cms-experts/');
define('CMS_EXPERTS_TEXT_DOMAIN', 'cms-experts');

if (!function_exists('cms_experts_public_url')) {
    function cms_experts_public_url(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '' || strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        if (!in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            return '';
        }

        if (!empty($parts['user']) || !empty($parts['pass'])) {
            return '';
        }

        $host = strtolower(trim((string) $parts['host'], '[]'));
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return '';
        }

        if (filter_var($host, FILTER_VALIDATE_IP) && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return '';
        }

        return $url;
    }
}

/**
 * Hauptklasse für CMS Experts Plugin
 *
 * @since 1.0.0
 */
final class CMS_Experts
{
    private static ?self $instance = null;
    private bool $components_bootstrapped = false;

    private string $version = '3.0.12';
    private string $plugin_dir;
    private string $plugin_url;
    private string $text_domain = 'cms-experts';

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->plugin_dir = CMS_EXPERTS_PLUGIN_DIR;
        $this->plugin_url = CMS_EXPERTS_PLUGIN_URL;

        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Lädt alle benötigten Dateien
     */
    private function load_dependencies(): void
    {
        $includes_dir = $this->plugin_dir . 'includes/';
        $includes_real = realpath($includes_dir);
        if ($includes_real === false || !is_dir($includes_real)) {
            return;
        }

        $files = [
            'class-database.php',
            'class-post-type.php',
            'class-meta-boxes.php',
            'class-taxonomies.php',
            'class-template-loader.php',
            'class-shortcode.php',
            'class-admin.php',
            'class-member-dashboard.php',   // Member-Dashboard-Integration
        ];

        foreach ($files as $file) {
            $filepath = $includes_real . DIRECTORY_SEPARATOR . $file;
            $real_path = realpath($filepath);
            if ($real_path !== false && str_starts_with($real_path, $includes_real . DIRECTORY_SEPARATOR) && is_file($real_path)) {
                require_once $real_path;
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

        foreach (['CMS_Experts_Database', 'CMS_Experts_Post_Type', 'CMS_Experts_Meta_Boxes', 'CMS_Experts_Taxonomies', 'CMS_Experts_Template_Loader', 'CMS_Experts_Shortcode', 'CMS_Experts_Admin', 'CMS_Experts_Member_Dashboard'] as $class) {
            if (class_exists($class)) {
                $class::instance();
            }
        }
    }

    /**
     * Registriert CMSv2 Hooks
     */
    private function init_hooks(): void
    {
        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::addAction('cms_init', [$this, 'init_plugin'], 10);
            CMS\Hooks::addAction('plugin_activated', [$this, 'on_activation'], 10);
            CMS\Hooks::addAction('head', [$this, 'enqueue_styles'], 10);
            CMS\Hooks::addAction('body_end', [$this, 'enqueue_scripts'], 10);
        }
    }

    /**
     * Aktivierungs-Handler
     */
    public function on_activation(string $plugin): void
    {
        if ($plugin !== 'cms-experts') {
            return;
        }

        if (class_exists('CMS\\Database') && class_exists('CMS_Experts_Database')) {
            CMS_Experts_Database::instance()->create_tables();
        }

        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::doAction('expert_registered');
        }
    }

    /**
     * Initialisiert das Plugin
     */
    public function init_plugin(): void
    {
        if (!$this->can_bootstrap_components()) {
            return;
        }

        if (class_exists('CMS_Experts_Database')) {
            $db = CMS_Experts_Database::instance();
            $schema_version = '3.0.0';
            $installed_schema_version = (string) $db->get_plugin_setting('schema_version', '');
            if ($installed_schema_version !== $schema_version) {
                try {
                    $db->create_tables();
                    $db->maybe_migrate_skill_type();
                    $db->save_plugin_settings(['schema_version' => $schema_version]);
                } catch (\Throwable $e) {
                    error_log('CMS Experts: ' . $e->getMessage());
                }
            }
        }
        $this->bootstrap_components();
    }

    /**
     * Lädt CSS Styles
     */
    public function enqueue_styles(): void
    {
        if (!$this->is_expert_frontend_route()) {
            return;
        }

        if ($this->is_expert_detail_route()) {
            // SunEditor-Stylesheet für korrekte Formatierung von WYSIWYG-Inhalten auf Public-Detailseiten
            $sun_css = function_exists('cms_asset_url')
                ? cms_asset_url('suneditor/css/suneditor.min.css')
                : (defined('SITE_URL') ? SITE_URL . '/assets/suneditor/css/suneditor.min.css' : '');
            if ($sun_css) {
                echo '<link rel="stylesheet" href="' . htmlspecialchars($sun_css, ENT_QUOTES, 'UTF-8') . '">' . "\n";
            }
        }

        $this->enqueue_style_file('plugin-base.css');
        $this->enqueue_style_file('style.css');
        if ($this->is_expert_detail_route()) {
            $this->enqueue_style_file('single.css');
        }
    }

    private function is_expert_frontend_route(): bool
    {
        $path = $this->current_request_path();

        return $path === '/experts'
            || str_starts_with($path, '/experts/')
            || str_starts_with($path, '/expert/');
    }

    private function is_expert_archive_route(): bool
    {
        return $this->current_request_path() === '/experts';
    }

    private function is_expert_detail_route(): bool
    {
        $path = $this->current_request_path();

        return str_starts_with($path, '/experts/') || str_starts_with($path, '/expert/');
    }

    private function current_request_path(): string
    {
        static $path = null;
        if (is_string($path)) {
            return $path;
        }

        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $path = '/' . trim((string) $requestPath, '/');
        return $path;
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

    /**
     * Lädt JavaScript
     */
    public function enqueue_scripts(): void
    {
        if (!$this->is_expert_archive_route()) {
            return;
        }

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
CMS_Experts::instance();
