<?php
/**
 * Plugin Name: CMS NetImport
 * Plugin URI: https://365network.de/cms-netimport
 * Description: CSV-Importer für Events, Speaker, Companies und Experts mit vorbereiteten Netzwerk-Datenquellen.
 * Version: 1.2.0
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_NetImport
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CMS_NETIMPORT_VERSION', '1.2.0');
define('CMS_NETIMPORT_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_NETIMPORT_PLUGIN_URL', '/plugins/cms-netimport/');

final class CMS_NetImport
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
        if ($this->can_bootstrap_components()) {
            $this->bootstrap_components();
        }
    }

    private function load_dependencies(): void
    {
        $includes = CMS_NETIMPORT_PLUGIN_DIR . 'includes/';
        foreach (['class-importer.php', 'class-admin.php'] as $file) {
            $path = $includes . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    private function can_bootstrap_components(): bool
    {
        return class_exists('CMS\\Hooks') && class_exists('CMS\\Database');
    }

    private function bootstrap_components(): void
    {
        foreach (['CMS_NetImport_Importer', 'CMS_NetImport_Admin'] as $class) {
            if (class_exists($class)) {
                $class::instance();
            }
        }
    }

    private function init_hooks(): void
    {
        if (!class_exists('CMS\\Hooks')) {
            return;
        }

        CMS\Hooks::addAction('cms_init', [$this, 'init_plugin'], 10);
        CMS\Hooks::addAction('plugin_activated', [$this, 'on_activation'], 10);
    }

    public function init_plugin(): void
    {
        if (!$this->can_bootstrap_components()) {
            return;
        }

        $this->bootstrap_components();
        if (class_exists('CMS_NetImport_Importer')) {
            CMS_NetImport_Importer::instance()->ensure_storage();
        }
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin !== 'cms-netimport') {
            return;
        }

        if (class_exists('CMS_NetImport_Importer')) {
            CMS_NetImport_Importer::instance()->ensure_storage();
        }

        if (class_exists('CMS\\Hooks')) {
            CMS\Hooks::doAction('netimport_ready');
        }
    }
}

CMS_NetImport::instance();
