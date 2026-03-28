<?php
/**
 * Plugin Name: CMS Experts
 * Plugin URI: https://365network.de/cms-experts
 * Description: Verwaltung von IT-Experten-Profilen mit Card-Ansicht, Detailseiten und umfangreichen Meta-Daten
 * Version: 2.0.0
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
define('CMS_EXPERTS_VERSION', '2.0.0');
define('CMS_EXPERTS_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_EXPERTS_PLUGIN_URL', '/plugins/cms-experts/');
define('CMS_EXPERTS_TEXT_DOMAIN', 'cms-experts');

/**
 * Hauptklasse für CMS Experts Plugin
 *
 * @since 1.0.0
 */
final class CMS_Experts
{
    private static ?self $instance = null;

    private string $version = '2.0.0';
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
            $filepath = $includes_dir . $file;
            if (file_exists($filepath)) {
                require_once $filepath;
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

        if (class_exists('CMS_Experts_Database')) {
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
        if (class_exists('CMS_Experts_Database')) {
            $db = CMS_Experts_Database::instance();
            $schema_version = '2.0.0';
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
        if (class_exists('CMS_Experts_Post_Type')) {
            CMS_Experts_Post_Type::instance();
        }
        if (class_exists('CMS_Experts_Meta_Boxes')) {
            CMS_Experts_Meta_Boxes::instance();
        }
        if (class_exists('CMS_Experts_Taxonomies')) {
            CMS_Experts_Taxonomies::instance();
        }
        if (class_exists('CMS_Experts_Template_Loader')) {
            CMS_Experts_Template_Loader::instance();
        }
        if (class_exists('CMS_Experts_Shortcode')) {
            CMS_Experts_Shortcode::instance();
        }
        if (class_exists('CMS_Experts_Admin')) {
            CMS_Experts_Admin::instance();
        }
    }

    /**
     * Lädt CSS Styles
     */
    public function enqueue_styles(): void
    {
        // SunEditor-Stylesheet für korrekte Formatierung von WYSIWYG-Inhalten auf Public-Seiten
        $sun_css = function_exists('cms_asset_url')
            ? cms_asset_url('suneditor/css/suneditor.min.css')
            : (defined('SITE_URL') ? SITE_URL . '/assets/suneditor/css/suneditor.min.css' : '');
        if ($sun_css) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars($sun_css) . '">' . "\n";
        }

        $css_file = $this->plugin_dir . 'assets/css/style.css';
        if (file_exists($css_file)) {
            $css_url = $this->plugin_url . 'assets/css/style.css';
            $css_version = filemtime($css_file);
            echo '<link rel="stylesheet" href="' . htmlspecialchars($css_url) . '?v=' . $css_version . '">' . "\n";
        }
        $single_css_file = $this->plugin_dir . 'assets/css/single.css';
        if (file_exists($single_css_file)) {
            $single_css_url = $this->plugin_url . 'assets/css/single.css';
            echo '<link rel="stylesheet" href="' . htmlspecialchars($single_css_url) . '?v=' . filemtime($single_css_file) . '">' . "\n";
        }
    }

    /**
     * Lädt JavaScript
     */
    public function enqueue_scripts(): void
    {
        $js_file = $this->plugin_dir . 'assets/js/script.js';
        if (file_exists($js_file)) {
            $js_url = $this->plugin_url . 'assets/js/script.js';
            $js_version = filemtime($js_file);
            echo '<script src="' . htmlspecialchars($js_url) . '?v=' . $js_version . '" defer></script>' . "\n";
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
