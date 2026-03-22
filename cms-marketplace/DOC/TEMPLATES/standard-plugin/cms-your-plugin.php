<?php
/**
 * Plugin Name: CMS Example Plugin
 * Plugin URI: https://365cms.de/plugins/cms-example-plugin
 * Description: Beispielvorlage für ein Standard-Plugin im 365CMS Marketplace.
 * Version: 1.0.0
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_Example_Plugin
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

defined('CMS_EXAMPLE_PLUGIN_VERSION') || define('CMS_EXAMPLE_PLUGIN_VERSION', '1.0.0');
defined('CMS_EXAMPLE_PLUGIN_DIR') || define('CMS_EXAMPLE_PLUGIN_DIR', __DIR__ . DIRECTORY_SEPARATOR);
defined('CMS_EXAMPLE_PLUGIN_URL') || define('CMS_EXAMPLE_PLUGIN_URL', '/plugins/cms-example-plugin/');

final class CMS_Example_Plugin
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->registerHooks();
    }

    private function registerHooks(): void
    {
        if (!class_exists('CMS\\Hooks')) {
            return;
        }

        \CMS\Hooks::addAction('cms_init', [$this, 'boot'], 10);
        \CMS\Hooks::addAction('plugin_activated', [$this, 'onActivation'], 10);
    }

    public function boot(): void
    {
    }

    public function onActivation(string $plugin): void
    {
        if ($plugin !== 'cms-example-plugin') {
            return;
        }
    }
}

CMS_Example_Plugin::instance();
