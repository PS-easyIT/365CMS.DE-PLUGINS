<?php
/**
 * Plugin Name: CMS M365 Tools
 * Plugin URI: https://365network.de/cms-m365tools
 * Description: Modulare Microsoft-365-Rechner-Toolbox mit M365LIC-Preisübernahme, Dienstleister- und Kontaktformular-CTA, Landingpage-Designer, UX-optimierter Toolsuche, zentralen Admin-Einstellungen, globalen Paket- und Abopreisbereichen, kurzen Modulmenüs, ruhigem PHINIT-Public-Design, All-Module-Best-Practice-Kompass, Power-Platform-Kosten- und Well-Architected-Review, Google-Workspace-M365-TCO-Rechner, Storage-Bedarfs-Rechner, Backup-Kosten-Rechner, Lizenz-Audit-Checkliste, Microsoft-Preiserhöhung-Tracker, Teams-Phone-Lizenz-Berater, Exchange-Online-ROI-Rechner, Frontline-Worker-Lizenz-Check, Copilot-Pilot-Phase-Rechner, AI-Pack-vs-Copilot-Pro-Vergleich, Archive-Mailbox-, Annual-vs-Monthly-Rechner, Add-On-Konfigurator, Lizenzvergleich, Lizenzberater, Copilot ROI-Rechner, Shared-Mailbox- und Copilot-Lizenz-Pflicht-Checker.
 * Version: 3.0.24
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_M365TOOLS
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

defined('CMS_M365TOOLS_VERSION') || define('CMS_M365TOOLS_VERSION', '3.0.24');
defined('CMS_M365TOOLS_PLUGIN_DIR') || define('CMS_M365TOOLS_PLUGIN_DIR', dirname(__FILE__) . '/');
defined('CMS_M365TOOLS_PLUGIN_URL') || define('CMS_M365TOOLS_PLUGIN_URL', '/plugins/cms-m365tools/');

// Legacy-Aliasse: Die internen Klassen behalten aus Kompatibilitätsgründen ihren bisherigen Präfix.
defined('CMS_M365CALCULATOR_VERSION') || define('CMS_M365CALCULATOR_VERSION', CMS_M365TOOLS_VERSION);
defined('CMS_M365CALCULATOR_PLUGIN_DIR') || define('CMS_M365CALCULATOR_PLUGIN_DIR', CMS_M365TOOLS_PLUGIN_DIR);
defined('CMS_M365CALCULATOR_PLUGIN_URL') || define('CMS_M365CALCULATOR_PLUGIN_URL', CMS_M365TOOLS_PLUGIN_URL);

if (!class_exists('CMS_M365CALCULATOR', false)) {
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
        $sharedAdmin = dirname(CMS_M365CALCULATOR_PLUGIN_DIR) . '/shared/admin/plugin-admin-contract.php';
        $sharedPublicI18n = dirname(CMS_M365CALCULATOR_PLUGIN_DIR) . '/shared/public/plugin-public-i18n.php';
        $trustedRoots = [
            rtrim(str_replace('\\', '/', CMS_M365CALCULATOR_PLUGIN_DIR), '/'),
            rtrim(str_replace('\\', '/', dirname(CMS_M365CALCULATOR_PLUGIN_DIR) . '/shared/admin'), '/'),
            rtrim(str_replace('\\', '/', dirname(CMS_M365CALCULATOR_PLUGIN_DIR) . '/shared/public'), '/'),
        ];

        $files = [
            $sharedAdmin,
            $sharedPublicI18n,
            $inc . 'class-catalog.php',
            $inc . 'class-installer.php',
            $inc . 'class-settings.php',
            $inc . 'class-icons.php',
            $inc . 'class-tool-registry.php',
            $inc . 'class-license-comparison.php',
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
            $inc . 'class-workspace-m365-tco-calculator.php',
            $inc . 'class-power-platform-cost-calculator.php',
            $inc . 'class-addon-configurator.php',
            $inc . 'class-license-advisor.php',
            $inc . 'class-shared-mailbox-calculator.php',
            $inc . 'class-copilot-license-checker.php',
            $inc . 'class-copilot-roi-calculator.php',
            $inc . 'class-frontend.php',
            $admin . 'class-admin-module-config.php',
            $admin . 'class-admin-menu.php',
            $admin . 'class-admin-pages.php',
        ];

        foreach ($files as $file) {
            if ($this->is_trusted_dependency_file($file, $trustedRoots)) {
                require_once $file;
            }
        }
    }

    /**
     * @param array<int,string> $trustedRoots
     */
    private function is_trusted_dependency_file(string $file, array $trustedRoots): bool
    {
        if (!is_file($file) || !is_readable($file)) {
            return false;
        }

        $realPath = realpath($file);
        if (!is_string($realPath) || $realPath === '') {
            return false;
        }

        $normalizedPath = str_replace('\\', '/', $realPath);
        foreach ($trustedRoots as $root) {
            if (str_starts_with($normalizedPath, $root . '/')) {
                return true;
            }
        }

        return false;
    }

    private function init_hooks(): void
    {
        if (!class_exists('CMS\\Hooks')) {
            return;
        }

        \CMS\Hooks::addAction('cms_init', [$this, 'init_plugin'], 10);
        \CMS\Hooks::addAction('plugin_activated', [$this, 'on_activation'], 10);
        \CMS\Hooks::addAction('cms_admin_menu', [CMS_M365CALCULATOR_Admin_Menu::class, 'register'], 10);
        \CMS\Hooks::addAction('register_routes', [CMS_M365CALCULATOR_Admin_Menu::class, 'register_routes'], 9);
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
}
