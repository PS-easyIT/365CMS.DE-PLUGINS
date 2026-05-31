<?php
/**
 * Plugin Name: CMS Newsletter
 * Plugin URI: https://365network.de/cms-newsletter
 * Description: Newsletter-Management mit Subscriber-Verwaltung, Templates, Kampagnen, Opt-In-Prozess und öffentlicher Anmeldeseite.
 * Version: 3.0.2
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_Newsletter
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CMS_NEWSLETTER_VERSION', '3.0.2');
define('CMS_NEWSLETTER_DB_VERSION', '1.0.0');
define('CMS_NEWSLETTER_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_NEWSLETTER_PLUGIN_URL', '/plugins/cms-newsletter/');

final class CMS_Newsletter
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
        $pluginRoot = realpath(CMS_NEWSLETTER_PLUGIN_DIR) ?: CMS_NEWSLETTER_PLUGIN_DIR;
        $files = [
            dirname(__DIR__) . '/shared/admin/plugin-admin-contract.php',
            dirname(__DIR__) . '/shared/public/plugin-public-i18n.php',
            CMS_NEWSLETTER_PLUGIN_DIR . 'includes/class-installer.php',
            CMS_NEWSLETTER_PLUGIN_DIR . 'includes/class-repository.php',
            CMS_NEWSLETTER_PLUGIN_DIR . 'includes/class-public-controller.php',
            CMS_NEWSLETTER_PLUGIN_DIR . 'admin/class-admin-menu.php',
            CMS_NEWSLETTER_PLUGIN_DIR . 'admin/class-admin-pages.php',
        ];

        foreach ($files as $file) {
            $this->safe_require_file($file, $pluginRoot);
        }
    }

    private function init_hooks(): void
    {
        if (!class_exists('CMS\\Hooks')) {
            return;
        }

        \CMS\Hooks::addAction('cms_init', [$this, 'init_plugin'], 10);
        \CMS\Hooks::addAction('plugin_activated', [$this, 'on_activation'], 10);
        \CMS\Hooks::addAction('plugin_uninstalled', [$this, 'on_uninstall'], 10);
        \CMS\Hooks::addAction('cms_admin_menu', [CMS_Newsletter_Admin_Menu::class, 'register'], 10);
        \CMS\Hooks::addAction('register_routes', [CMS_Newsletter_Public_Controller::instance(), 'register_routes'], 10);
        \CMS\Hooks::addAction('head', [$this, 'enqueue_styles'], 20);
    }

    public function init_plugin(): void
    {
        CMS_Newsletter_Installer::maybe_install();
        CMS_Newsletter_Repository::instance();
        CMS_Newsletter_Public_Controller::instance();
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin !== 'cms-newsletter') {
            return;
        }

        CMS_Newsletter_Installer::install();
    }

    public function on_uninstall(string $plugin): void
    {
        if ($plugin !== 'cms-newsletter') {
            return;
        }

        CMS_Newsletter_Installer::uninstall();
    }

    public function enqueue_styles(): void
    {
        if (!$this->is_newsletter_public_route()) {
            return;
        }

        $css = CMS_NEWSLETTER_PLUGIN_DIR . 'assets/css/newsletter-public.css';
        if (!file_exists($css)) {
            return;
        }

        echo '<link rel="stylesheet" href="'
            . htmlspecialchars(CMS_NEWSLETTER_PLUGIN_URL . 'assets/css/newsletter-public.css?v=' . filemtime($css), ENT_QUOTES, 'UTF-8')
            . '">' . "\n";
    }

    private function is_newsletter_public_route(): bool
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
            return false;
        }

        if (function_exists('cms_plugin_public_path_without_lang')) {
            $path = cms_plugin_public_path_without_lang();
            return $path === 'newsletter' || str_starts_with($path, 'newsletter/');
        }

        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
        $path = trim($path, '/');
        return $path === 'newsletter' || str_starts_with($path, 'newsletter/') || $path === 'en/newsletter' || str_starts_with($path, 'en/newsletter/');
    }

    private function safe_require_file(string $file, string $pluginRoot): void
    {
        $resolved = realpath($file);
        if ($resolved === false || !is_file($resolved) || !is_readable($resolved)) {
            return;
        }

        $sharedContractPath = realpath(dirname(__DIR__) . '/shared/admin/plugin-admin-contract.php');
        $sharedPublicI18nPath = realpath(dirname(__DIR__) . '/shared/public/plugin-public-i18n.php');
        $isInPlugin = str_starts_with($resolved, rtrim($pluginRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
        $isAllowedSharedContract = $sharedContractPath !== false && $resolved === $sharedContractPath;
        $isAllowedSharedPublicI18n = $sharedPublicI18nPath !== false && $resolved === $sharedPublicI18nPath;
        if (!$isInPlugin && !$isAllowedSharedContract && !$isAllowedSharedPublicI18n) {
            return;
        }

        require_once $resolved;
    }
}

CMS_Newsletter::instance();
