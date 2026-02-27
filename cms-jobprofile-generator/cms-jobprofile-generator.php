<?php
declare(strict_types=1);
/**
 * Plugin Name: CMS Job Profile Generator
 * Plugin URI:  https://365network.de/cms-jobprofile-generator
 * Description: Vollständiger Job-Profil-Generator mit Bibliotheken, Vorlagen und Workflow
 * Version:     0.9.6
 * Author:      365CMS
 * Author URI:  https://365network.de
 *
 * @package CMS_JobProfileGenerator
 */

if (!defined('ABSPATH')) {
    exit;
}

// ── Konstanten ────────────────────────────────────────────────────────────────
define('JPG_VERSION',     '0.9.6');
define('JPG_DB_VERSION',  '7');
define('JPG_DIR',         dirname(__FILE__) . '/');
define('JPG_URL',         '/plugins/cms-jobprofile-generator/');
define('JPG_TEXT_DOMAIN', 'cms-jobprofile-generator');

// ── Früh-Puffer ───────────────────────────────────────────────────────────────
// Export- und Preview-Requests benötigen saubere HTTP-Header, die Theme-Ausgaben
// (z. B. <style>-Tags aus functions.php) bereits gesendet haben. ob_start() hier
// fängt alles ab; in den AJAX-Handlern wird der Buffer geleert (ob_end_clean).
if (
    (isset($_GET['_jpg_export']) && $_GET['_jpg_export'] === 'json') ||
    (isset($_POST['_jpg_action']) && $_POST['_jpg_action'] === 'preview')
) {
    ob_start();
}

/**
 * Hauptklasse – Singleton
 *
 * @since 1.0.0
 */
final class CMS_JobProfileGenerator
{
    private static ?self $instance = null;

    private string $version;
    private string $plugin_dir;
    private string $plugin_url;

    public static function instance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->version    = JPG_VERSION;
        $this->plugin_dir = JPG_DIR;
        $this->plugin_url = JPG_URL;

        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies(): void
    {
        $includes = $this->plugin_dir . 'includes/';
        $admin    = $this->plugin_dir . 'admin/';

        $files = [
            $includes . 'class-installer.php',
            $includes . 'class-workflow.php',
            $includes . 'class-profiles.php',
            $includes . 'class-text-modules.php',
            $includes . 'class-skill-matrix.php',
            $includes . 'class-benefits-catalog.php',
            $includes . 'class-job-categories.php',
            $includes . 'class-requirement-items.php',
            $includes . 'class-departments.php',
            $includes . 'class-export.php',
            $includes . 'class-frontend.php',
            $includes . 'class-member-controller.php',
            $admin    . 'class-admin-menu.php',
            $admin    . 'class-admin-pages.php',
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }

    private function init_hooks(): void
    {
        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::addAction('cms_init',          [$this, 'init_plugin'],              10);
            CMS\Hooks::addAction('plugin_activated',   [$this, 'on_activation'],            10);
            CMS\Hooks::addAction('plugin_uninstalled', [$this, 'on_uninstall'],             10);
            CMS\Hooks::addAction('cms_admin_menu',     [CMS_JPG_Admin_Menu::class, 'register'], 10);
            CMS\Hooks::addAction('register_routes',    [$this, 'register_admin_ajax_routes'],     9);
            CMS\Hooks::addAction('register_routes',    [CMS_JPG_Frontend::class, 'instance'],   10);
            CMS\Hooks::addAction('register_routes',    [CMS_JPG_Member_Controller::class, 'instance'], 11);
            CMS\Hooks::addAction('head',               [$this, 'enqueue_styles'],           20);
            CMS\Hooks::addAction('body_end',           [$this, 'enqueue_scripts'],          20);
            // Verwaiste Daten: Jobs auf Draft setzen, wenn Firma gelöscht wird
            CMS\Hooks::addAction('company_deleted',    [$this, 'on_company_deleted'],       10);
        }
    }

    /**
     * Admin-AJAX-Routen registrieren (laufen vor dem HTML-Layout-Rendering,
     * damit JSON-Antworten sauber ohne HTML-Header möglich sind).
     */
    public function register_admin_ajax_routes(): void
    {
        if (!class_exists('CMS\Router')) {
            return;
        }
        $r = \CMS\Router::instance();
        // POST /api/jpg/admin/users-action → AJAX-Handler für Benutzer & Mandanten
        $r->addRoute('POST', '/api/jpg/admin/users-action', [CMS_JPG_Admin_Pages::class, 'handle_users_ajax']);
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin === 'cms-jobprofile-generator' && class_exists('CMS_JPG_Installer')) {
            CMS_JPG_Installer::install();
        }
    }

    /**
     * Deinstallations-Handler (Hook: plugin_uninstalled).
     * Entfernt Tabellen + Abo-Spalten wenn dieses Plugin deinstalliert wird.
     */
    public function on_uninstall(string $plugin): void
    {
        if ($plugin === 'cms-jobprofile-generator' && class_exists('CMS_JPG_Installer')) {
            CMS_JPG_Installer::uninstall();
        }
    }

    /**
     * Verwaiste Daten-Handler (Hook: company_deleted).
     * Wenn eine Firma in cms-companies gelöscht wird, werden verknüpfte Jobs auf `draft` gesetzt.
     *
     * @param int $companyId ID der gelöschten Firma
     */
    public function on_company_deleted(int $companyId): void
    {
        if ($companyId <= 0 || !class_exists('CMS\Database')) {
            return;
        }
        try {
            $db = \CMS\Database::instance();
            $p  = $db->getPrefix();
            $db->execute(
                "UPDATE {$p}jpg_profiles
                 SET status = 'draft', company_id = NULL, updated_at = NOW()
                 WHERE company_id = ? AND status = 'published'",
                [$companyId]
            );
        } catch (\Throwable $e) {
            error_log('CMS_JobProfileGenerator::on_company_deleted() error: ' . $e->getMessage());
        }
    }

    public function init_plugin(): void
    {
        if (class_exists('CMS_JPG_Installer')) {
            CMS_JPG_Installer::maybe_install();
        }
    }

    public function enqueue_styles(): void
    {
        $css_file = $this->plugin_dir . 'assets/css/jobprofile-admin.css';
        if (file_exists($css_file)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars($this->plugin_url . 'assets/css/jobprofile-admin.css')
                . '?v=' . filemtime($css_file) . '">' . "\n";
        }
    }

    public function enqueue_scripts(): void
    {
        $js_file = $this->plugin_dir . 'assets/js/jobprofile-admin.js';
        if (file_exists($js_file)) {
            echo '<script src="'
                . htmlspecialchars($this->plugin_url . 'assets/js/jobprofile-admin.js')
                . '?v=' . filemtime($js_file) . '" defer></script>' . "\n";
        }
    }

    public function get_version(): string    { return $this->version; }
    public function get_plugin_dir(): string { return $this->plugin_dir; }
    public function get_plugin_url(): string { return $this->plugin_url; }
}

// ── Bootstrap ─────────────────────────────────────────────────────────────────
CMS_JobProfileGenerator::instance();
