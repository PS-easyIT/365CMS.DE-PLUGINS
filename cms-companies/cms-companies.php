<?php
/**
 * Plugin Name: CMS Companies
 * Plugin URI: https://365network.de/cms-companies
 * Description: Verwaltung von Firmen-Profilen mit Experten-Zuordnung, Partner-Status und Unternehmens-Informationen
 * Version: 1.0.0
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_Companies
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Plugin Constants
define('CMS_COMPANIES_VERSION', '1.0.0');
define('CMS_COMPANIES_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_COMPANIES_PLUGIN_URL', '/plugins/cms-companies/');
define('CMS_COMPANIES_TEXT_DOMAIN', 'cms-companies');

/**
 * Hauptklasse für CMS Companies Plugin
 *
 * @since 1.0.0
 */
final class CMS_Companies
{
    private static ?self $instance = null;

    private string $version = '1.0.0';
    private string $plugin_dir;
    private string $plugin_url;
    private string $text_domain = 'cms-companies';

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->plugin_dir = CMS_COMPANIES_PLUGIN_DIR;
        $this->plugin_url = CMS_COMPANIES_PLUGIN_URL;

        $this->load_dependencies();
        $this->init_hooks();
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
        if ($plugin !== 'cms-companies') {
            return;
        }

        if (class_exists('CMS_Companies_Database')) {
            CMS_Companies_Database::instance()->create_tables();
        }

        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::doAction('company_created');
        }
    }

    public function init_plugin(): void
    {
        if (class_exists('CMS_Companies_Database')) {
            $db = CMS_Companies_Database::instance();
            // Tabellen bei jedem Init sicherstellen (CREATE IF NOT EXISTS – idempotent)
            try { $db->create_tables(); } catch (\Throwable $e) { error_log('CMS Companies: ' . $e->getMessage()); }
        }
        if (class_exists('CMS_Companies_Post_Type')) {
            CMS_Companies_Post_Type::instance();
        }
        if (class_exists('CMS_Companies_Meta_Boxes')) {
            CMS_Companies_Meta_Boxes::instance();
        }
        if (class_exists('CMS_Companies_Template_Loader')) {
            CMS_Companies_Template_Loader::instance();
        }
        if (class_exists('CMS_Companies_Shortcode')) {
            CMS_Companies_Shortcode::instance();
        }
        if (class_exists('CMS_Companies_Admin')) {
            CMS_Companies_Admin::instance();
        }
    }

    public function enqueue_styles(): void
    {
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

    public function enqueue_scripts(): void
    {
        $js_file = $this->plugin_dir . 'assets/js/script.js';
        if (file_exists($js_file)) {
            $js_url = $this->plugin_url . 'assets/js/script.js';
            $js_version = filemtime($js_file);
            echo '<script src="' . htmlspecialchars($js_url) . '?v=' . $js_version . '" defer></script>' . "\n";
        }
    }

    public function get_version(): string { return $this->version; }
    public function get_plugin_dir(): string { return $this->plugin_dir; }
    public function get_plugin_url(): string { return $this->plugin_url; }
}

CMS_Companies::instance();

/**
 * Generiert die kanonische Firmen-URL im Format /company/firmenname-id.
 *
 * @param  object $company  Firmen-Objekt mit ->name und ->id
 * @return string
 */
function cms_company_url(object $company): string {
    $slug = mb_strtolower(trim((string)$company->name), 'UTF-8');
    $slug = preg_replace('/[^\p{L}0-9]+/u', '-', $slug);
    $slug = trim((string)preg_replace('/-+/', '-', $slug), '-');
    return SITE_URL . '/company/' . $slug . '-' . (int)$company->id;
}
