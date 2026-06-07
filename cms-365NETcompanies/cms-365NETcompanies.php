<?php
/**
 * Plugin Name: 365NET | Companies
 * Plugin URI: https://365network.de/cms-365NETcompanies
 * Description: Verwaltung von Firmen-Profilen mit Experten-Zuordnung, Partner-Status und Unternehmens-Informationen
 * Version: 3.0.12
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
define('CMS_COMPANIES_VERSION', '3.0.12');
define('CMS_COMPANIES_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_COMPANIES_PLUGIN_URL', '/plugins/cms-365NETcompanies/');
define('CMS_COMPANIES_TEXT_DOMAIN', 'cms-companies');

/**
 * Hauptklasse für CMS Companies Plugin
 *
 * @since 1.0.0
 */
final class CMS_Companies
{
    private static ?self $instance = null;
    private bool $components_bootstrapped = false;

    private string $version = '3.0.12';
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
        if ($this->can_bootstrap_components()) {
            $this->bootstrap_components();
        }
    }

    private function load_dependencies(): void
    {
        $files = [
            'shared/admin/plugin-admin-contract.php',
            'class-database.php',
            'class-post-type.php',
            'class-meta-boxes.php',
            'class-template-loader.php',
            'class-shortcode.php',
            'class-admin.php',
            'class-member-dashboard.php',
        ];

        foreach ($files as $file) {
            $filepath = str_contains($file, '/')
                ? $this->plugin_dir . $file
                : $this->plugin_dir . 'includes/' . $file;
            if (file_exists($filepath)) {
                require_once $filepath;
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

        foreach (['CMS_Companies_Database', 'CMS_Companies_Post_Type', 'CMS_Companies_Meta_Boxes', 'CMS_Companies_Template_Loader', 'CMS_Companies_Shortcode', 'CMS_Companies_Admin', 'CMS_Companies_Member_Dashboard'] as $class) {
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
        if ($plugin !== 'cms-365NETcompanies') {
            return;
        }

        if (class_exists('CMS\\Database') && class_exists('CMS_Companies_Database')) {
            CMS_Companies_Database::instance()->create_tables();
        }

        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::doAction('company_created');
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
        if (!class_exists('CMS_Companies_Database')) {
            return;
        }

        $db = CMS_Companies_Database::instance();
        $schema_version = '3.0.0';
        if ((string) $db->get_setting('schema_version', '') === $schema_version) {
            return;
        }

        try {
            $db->create_tables();
            $db->save_setting('schema_version', $schema_version);
        } catch (\Throwable $e) {
            error_log('CMS Companies: ' . $e->getMessage());
        }
    }

    public function enqueue_styles(): void
    {
        if (!$this->is_company_frontend_route()) {
            return;
        }

        $this->enqueue_style_file('plugin-base.css');
        $this->enqueue_style_file('style.css');

        if ($this->is_company_detail_route()) {
            $this->enqueue_style_file('single.css');
        }
    }

    private function is_company_frontend_route(): bool
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method !== 'GET' && $method !== 'HEAD') {
            return false;
        }

        $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
        $path = '/' . trim($path, '/');

        return $path === '/companies'
            || preg_match('#^/companies/(?:\d+)?$#', $path) === 1
            || preg_match('#^/company/[^/]+$#', $path) === 1;
    }

    private function is_company_detail_route(): bool
    {
        $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
        $path = '/' . trim($path, '/');

        if (preg_match('#^/company/[^/]+$#', $path) === 1) {
            return true;
        }

        return preg_match('#^/companies/\d+$#', $path) === 1;
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
        if (!$this->is_company_frontend_route()) {
            return;
        }

        $js_file = $this->plugin_dir . 'assets/js/script.js';
        if (file_exists($js_file)) {
            $js_url = $this->plugin_url . 'assets/js/script.js';
            $js_version = (string) filemtime($js_file);
            echo '<script src="' . htmlspecialchars($js_url . '?v=' . $js_version, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
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
if (!function_exists('cms_company_url')) {
    function cms_company_url(object $company): string {
        $map   = ['ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss','Ä'=>'ae','Ö'=>'oe','Ü'=>'ue'];
        $name  = str_replace(array_keys($map), array_values($map), (string)($company->name ?? ''));
        $slug  = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug  = trim($slug, '-') ?: 'company';

        return SITE_URL . '/company/' . $slug . '-' . (int) $company->id;
    }
}

/**
 * Validiert öffentliche Asset-/Profil-URLs für Logo, Website und verknüpfte Profile.
 */
if (!function_exists('cms_companies_public_url')) {
    function cms_companies_public_url(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '' || mb_strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme'])) {
            return '';
        }

        return in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true) ? $url : '';
    }
}
