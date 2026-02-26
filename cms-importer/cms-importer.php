<?php
/**
 * Plugin Name: CMS WordPress Importer
 * Description: Importiert WordPress WXR-Export-Dateien (XML) in die CMS Posts- und Pages-Struktur. Unbekannte Meta-Felder werden protokolliert und als Markdown-Bericht gespeichert.
 * Version:      1.0.0
 * Author:       365 Network
 * Author URI:   https://365network.de
 * Requires:     0.26.0
 *
 * @package CMS_Importer
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// ── Plugin Constants ─────────────────────────────────────────────────────────
define('CMS_IMPORTER_VERSION',    '1.0.0');
define('CMS_IMPORTER_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_IMPORTER_PLUGIN_URL', '/plugins/cms-importer/');
define('CMS_IMPORTER_TEXT_DOMAIN', 'cms-importer');

// ── Autoload ──────────────────────────────────────────────────────────────────
require_once CMS_IMPORTER_PLUGIN_DIR . 'includes/class-xml-parser.php';
require_once CMS_IMPORTER_PLUGIN_DIR . 'includes/class-importer.php';
require_once CMS_IMPORTER_PLUGIN_DIR . 'includes/class-admin.php';

/**
 * Haupt-Klasse des CMS WordPress Importer Plugins.
 *
 * @since 1.0.0
 */
final class CMS_Importer
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->init_hooks();
    }

    private function init_hooks(): void
    {
        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::addAction('cms_init',        [$this, 'init'],          10);
            CMS\Hooks::addAction('plugin_activated', [$this, 'on_activation'], 10);
            CMS\Hooks::addAction('cms_admin_menu',   [$this, 'register_admin_pages'], 20);
            CMS\Hooks::addAction('head',             [$this, 'enqueue_styles'], 10);
            CMS\Hooks::addAction('body_end',         [$this, 'enqueue_scripts'], 10);
        }
    }

    public function init(): void
    {
        // Tabellen ggf. anlegen
        CMS_Importer_DB::create_tables();
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin === 'cms-importer') {
            CMS_Importer_DB::create_tables();
        }
    }

    public function register_admin_pages(): void
    {
        CMS_Importer_Admin::instance()->register_pages();
    }

    public function enqueue_styles(): void
    {
        $css_file = CMS_IMPORTER_PLUGIN_DIR . 'assets/css/importer.css';
        if (file_exists($css_file)) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_IMPORTER_PLUGIN_URL . 'assets/css/importer.css')
                . '?v=' . filemtime($css_file) . '">' . "\n";
        }
    }

    public function enqueue_scripts(): void
    {
        $js_file = CMS_IMPORTER_PLUGIN_DIR . 'assets/js/importer.js';
        if (file_exists($js_file)) {
            echo '<script src="' . htmlspecialchars(CMS_IMPORTER_PLUGIN_URL . 'assets/js/importer.js')
                . '?v=' . filemtime($js_file) . '" defer></script>' . "\n";
        }
    }
}

// ── Start ─────────────────────────────────────────────────────────────────────
if (class_exists('CMS\Hooks')) {
    CMS\Hooks::addAction('plugins_loaded', function () {
        CMS_Importer::instance();
    });
} else {
    // Fallback: direkt starten (für Entwicklung / Test)
    CMS_Importer::instance();
}
