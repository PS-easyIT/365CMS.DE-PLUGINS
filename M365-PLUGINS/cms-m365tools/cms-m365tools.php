<?php
/**
 * Plugin Name: CMS M365 Tools
 * Plugin URI: https://365network.de/cms-m365tools
 * Description: Modulare Microsoft-365-Rechner-Toolbox mit Storage-Bedarfs-Rechner, Backup-Kosten-Rechner, Lizenz-Audit-Checkliste, Microsoft-Preiserhöhung-Tracker, Teams-Phone-Lizenz-Berater, Exchange-Online-ROI-Rechner, Frontline-Worker-Lizenz-Check, Copilot-Pilot-Phase-Rechner, AI-Pack-vs-Copilot-Pro-Vergleich, Archive-Mailbox-, Annual-vs-Monthly-Rechner, Lizenz- und Add-on-Matrizen, Add-On-Konfigurator, Lizenzvergleich, Lizenzberater, Copilot ROI-Rechner, Shared-Mailbox- und Copilot-Lizenz-Pflicht-Checker.
 * Version: 1.19.0
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_M365TOOLS
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CMS_M365TOOLS_VERSION', '1.19.0');
define('CMS_M365TOOLS_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_M365TOOLS_PLUGIN_URL', '/plugins/cms-m365tools/');

// Legacy-Aliasse: Die internen Klassen behalten aus Kompatibilitätsgründen ihren bisherigen Präfix.
define('CMS_M365CALCULATOR_VERSION', CMS_M365TOOLS_VERSION);
define('CMS_M365CALCULATOR_PLUGIN_DIR', CMS_M365TOOLS_PLUGIN_DIR);
define('CMS_M365CALCULATOR_PLUGIN_URL', CMS_M365TOOLS_PLUGIN_URL);

final class CMS_M365CALCULATOR
{
    private const PLUGIN_SLUG = 'cms-m365tools';
    private const LEGACY_PLUGIN_SLUG = 'cms-m365' . 'cal' . 'culator';

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
        $inc = CMS_M365CALCULATOR_PLUGIN_DIR . 'includes/';
        $admin = CMS_M365CALCULATOR_PLUGIN_DIR . 'admin/';

        $files = [
            $inc . 'class-catalog.php',
            $inc . 'class-installer.php',
            $inc . 'class-settings.php',
            $inc . 'class-icons.php',
            $inc . 'class-tool-registry.php',
            $inc . 'class-license-comparison.php',
            $inc . 'class-readonly-matrices.php',
            $inc . 'class-commitment-calculator.php',
            $inc . 'class-archive-mailbox-calculator.php',
            $inc . 'class-ai-product-comparison.php',
            $inc . 'class-copilot-pilot-calculator.php',
            $inc . 'class-frontline-worker-check.php',
            $inc . 'class-exchange-online-roi-calculator.php',
            $inc . 'class-teams-phone-advisor.php',
            $inc . 'class-microsoft-price-tracker.php',
            $inc . 'class-license-audit-checklist.php',
            $inc . 'class-storage-needs-calculator.php',
            $inc . 'class-backup-cost-calculator.php',
            $inc . 'class-addon-configurator.php',
            $inc . 'class-license-advisor.php',
            $inc . 'class-shared-mailbox-calculator.php',
            $inc . 'class-copilot-license-checker.php',
            $inc . 'class-copilot-roi-calculator.php',
            $inc . 'class-frontend.php',
            $admin . 'class-admin-menu.php',
            $admin . 'class-admin-pages.php',
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }

    private function init_hooks(): void
    {
        if (!class_exists('CMS\\Hooks')) {
            return;
        }

        \CMS\Hooks::addAction('cms_init', [$this, 'init_plugin'], 10);
        \CMS\Hooks::addAction('plugin_activated', [$this, 'on_activation'], 10);
        \CMS\Hooks::addAction('cms_admin_menu', [CMS_M365CALCULATOR_Admin_Menu::class, 'register'], 10);
        \CMS\Hooks::addAction('register_routes', [CMS_M365CALCULATOR_Frontend::class, 'instance'], 10);
    }

    public function init_plugin(): void
    {
        CMS_M365CALCULATOR_Installer::maybe_install();
        CMS_M365CALCULATOR_Frontend::instance();
    }

    public function on_activation(string $plugin): void
    {
        if (!in_array($plugin, [self::PLUGIN_SLUG, self::LEGACY_PLUGIN_SLUG], true)) {
            return;
        }

        CMS_M365CALCULATOR_Installer::install();
        CMS_M365CALCULATOR_Frontend::instance();
    }
}

CMS_M365CALCULATOR::instance();
