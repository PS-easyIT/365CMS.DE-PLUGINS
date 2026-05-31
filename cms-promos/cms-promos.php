<?php
/**
 * Plugin Name: CMS Promos
 * Plugin URI: https://365network.de/cms-promos
 * Description: Promo- und Kampagnenverwaltung für Banner, Teaserflächen, Platzierungen und klickbare Aktionsboxen im 365CMS.
 * Version: 3.0.2
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_Promos
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CMS_PROMOS_VERSION', '3.0.2');
define('CMS_PROMOS_DB_VERSION', '1.1.0');
define('CMS_PROMOS_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_PROMOS_PLUGIN_URL', '/plugins/cms-promos/');

final class CMS_Promos
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies(): void
    {
        $this->load_shared_public_i18n();

        $files = [
            CMS_PROMOS_PLUGIN_DIR . 'includes/class-installer.php',
            CMS_PROMOS_PLUGIN_DIR . 'includes/class-repository.php',
            CMS_PROMOS_PLUGIN_DIR . 'includes/class-public-controller.php',
            CMS_PROMOS_PLUGIN_DIR . 'admin/class-admin-menu.php',
            CMS_PROMOS_PLUGIN_DIR . 'admin/class-admin-pages.php',
        ];

        $pluginRoot = realpath(CMS_PROMOS_PLUGIN_DIR) ?: CMS_PROMOS_PLUGIN_DIR;
        $pluginRoot = rtrim(str_replace('\\', '/', $pluginRoot), '/') . '/';

        foreach ($files as $file) {
            $resolved = realpath($file);
            $resolvedNormalized = $resolved !== false ? str_replace('\\', '/', $resolved) : '';
            if ($resolved !== false && str_starts_with($resolvedNormalized, $pluginRoot) && is_file($resolved)) {
                require_once $resolved;
                continue;
            }

            error_log('[cms-promos] Missing dependency file: ' . $file);
        }
    }

    private function load_shared_public_i18n(): void
    {
        $sharedFile = dirname(CMS_PROMOS_PLUGIN_DIR) . '/shared/public/plugin-public-i18n.php';
        $sharedPath = realpath($sharedFile);
        $pluginBasePath = realpath(dirname(CMS_PROMOS_PLUGIN_DIR));
        $sharedNormalized = $sharedPath !== false ? str_replace('\\', '/', $sharedPath) : '';
        $pluginBaseNormalized = $pluginBasePath !== false ? rtrim(str_replace('\\', '/', $pluginBasePath), '/') . '/' : '';
        if ($sharedPath !== false && $pluginBasePath !== false && str_starts_with($sharedNormalized, $pluginBaseNormalized) && is_file($sharedPath)) {
            require_once $sharedPath;
        }
    }

    private function init_hooks(): void
    {
        if (!class_exists('CMS\\Hooks')) {
            return;
        }

        \CMS\Hooks::addAction('cms_init', [$this, 'init_plugin'], 10);
        \CMS\Hooks::addAction('cms_init', [$this, 'bootstrap_session'], 5);
        \CMS\Hooks::addAction('plugin_activated', [$this, 'on_activation'], 10);
        \CMS\Hooks::addAction('plugin_uninstalled', [$this, 'on_uninstall'], 10);
        \CMS\Hooks::addAction('cms_admin_menu', [CMS_Promos_Admin_Menu::class, 'register'], 10);
        \CMS\Hooks::addAction('register_routes', [CMS_Promos_Public_Controller::instance(), 'register_routes'], 10);
        \CMS\Hooks::addAction('head', [$this, 'enqueue_styles'], 20);
    }

    public function init_plugin(): void
    {
        CMS_Promos_Installer::maybe_install();
        CMS_Promos_Repository::instance();
        CMS_Promos_Public_Controller::instance();
    }

    public function bootstrap_session(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE || headers_sent()) {
            return;
        }

        session_start();
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin !== 'cms-promos') {
            return;
        }

        CMS_Promos_Installer::install();
    }

    public function on_uninstall(string $plugin): void
    {
        if ($plugin !== 'cms-promos') {
            return;
        }
    }

    public function enqueue_styles(): void
    {
        if (!$this->should_load_public_assets()) {
            return;
        }

        $css = CMS_PROMOS_PLUGIN_DIR . 'assets/css/promos-public.css';
        if (!file_exists($css)) {
            return;
        }

        echo '<link rel="stylesheet" href="'
            . htmlspecialchars(CMS_PROMOS_PLUGIN_URL . 'assets/css/promos-public.css?v=' . filemtime($css), ENT_QUOTES, 'UTF-8')
            . '">' . "\n";
    }

    private function is_promos_public_route(): bool
    {
        if (function_exists('cms_plugin_public_path_without_lang')) {
            $pathWithoutLang = cms_plugin_public_path_without_lang();
            return $pathWithoutLang === 'promos' || str_starts_with($pathWithoutLang, 'promos/');
        }

        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
        $path = trim(strtolower($path), '/');
        if ($path === 'en/promos' || str_starts_with($path, 'en/promos/')) {
            return true;
        }

        return $path === 'promos' || str_starts_with($path, 'promos/');
    }

    private function should_load_public_assets(): bool
    {
        if ($this->is_admin_request()) {
            return false;
        }

        return $this->is_promos_public_route();
    }

    private function is_admin_request(): bool
    {
        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
        $path = '/' . trim($path, '/');

        return $path === '/admin' || str_starts_with($path, '/admin/');
    }
}

CMS_Promos::instance();
