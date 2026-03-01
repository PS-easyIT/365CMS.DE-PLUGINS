<?php
/**
 * Plugin Name: CMS Contact
 * Plugin URI:  https://365network.de/cms-contact
 * Description: Kontaktformular-Plugin mit bis zu 6 Templates, benutzerdefinierten Metafeldern und mehreren Formularen unter verschiedenen Slugs
 * Version:     1.0.0
 * Author:      365 Network
 * Author URI:  https://365network.de
 *
 * @package CMS_Contact
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// ── Konstanten ────────────────────────────────────────────────────────────────
define('CMS_CONTACT_VERSION',    '1.0.0');
define('CMS_CONTACT_DB_VERSION', '1');
define('CMS_CONTACT_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_CONTACT_PLUGIN_URL', '/plugins/cms-contact/');

final class CMS_Contact
{
    private static ?self $instance = null;
    private string $version;
    private string $plugin_dir;
    private string $plugin_url;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->version    = CMS_CONTACT_VERSION;
        $this->plugin_dir = CMS_CONTACT_PLUGIN_DIR;
        $this->plugin_url = CMS_CONTACT_PLUGIN_URL;
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies(): void
    {
        $includes = $this->plugin_dir . 'includes/';
        $admin    = $this->plugin_dir . 'admin/';

        $files = [
            $includes . 'class-installer.php',
            $includes . 'class-forms.php',
            $includes . 'class-fields.php',
            $includes . 'class-submissions.php',
            $includes . 'class-frontend.php',
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
        if (!class_exists('CMS\Hooks')) {
            return;
        }

        \CMS\Hooks::addAction('cms_init',            [$this, 'init_plugin'],                10);
        \CMS\Hooks::addAction('plugin_activated',     [$this, 'on_activation'],              10);
        \CMS\Hooks::addAction('plugin_uninstalled',   [$this, 'on_uninstall'],               10);
        \CMS\Hooks::addAction('cms_admin_menu',       [CMS_Contact_Admin_Menu::class, 'register'], 10);
        \CMS\Hooks::addAction('register_routes',      [CMS_Contact_Frontend::class, 'instance'],   10);
        \CMS\Hooks::addAction('head',                 [$this, 'enqueue_styles'],             20);
        \CMS\Hooks::addAction('body_end',             [$this, 'enqueue_scripts'],            20);

        // DSGVO-Hooks
        \CMS\Hooks::addAction('dsgvo_export_data',    [$this, 'export_user_data'],           10);
        \CMS\Hooks::addAction('dsgvo_delete_data',    [$this, 'delete_user_data'],           10);
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin === 'cms-contact' && class_exists('CMS_Contact_Installer')) {
            CMS_Contact_Installer::install();
        }
    }

    public function on_uninstall(string $plugin): void
    {
        if ($plugin === 'cms-contact' && class_exists('CMS_Contact_Installer')) {
            CMS_Contact_Installer::uninstall();
        }
    }

    public function init_plugin(): void
    {
        if (class_exists('CMS_Contact_Installer')) {
            CMS_Contact_Installer::maybe_install();
        }
    }

    public function enqueue_styles(): void
    {
        $css = $this->plugin_dir . 'assets/css/contact-public.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars($this->plugin_url . 'assets/css/contact-public.css')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    public function enqueue_scripts(): void
    {
        $js = $this->plugin_dir . 'assets/js/contact-public.js';
        if (file_exists($js)) {
            echo '<script src="'
                . htmlspecialchars($this->plugin_url . 'assets/js/contact-public.js')
                . '?v=' . filemtime($js) . '" defer></script>' . "\n";
        }
    }

    /**
     * DSGVO Art. 20 – Daten-Export
     */
    public function export_user_data(int $userId): array
    {
        if (!class_exists('CMS_Contact_Submissions')) {
            return [];
        }
        return CMS_Contact_Submissions::instance()->get_user_submissions($userId);
    }

    /**
     * DSGVO Art. 17 – Datenlöschung
     */
    public function delete_user_data(int $userId): void
    {
        if (!class_exists('CMS_Contact_Submissions')) {
            return;
        }
        CMS_Contact_Submissions::instance()->delete_user_submissions($userId);
    }

    public function get_version(): string    { return $this->version; }
    public function get_plugin_dir(): string { return $this->plugin_dir; }
    public function get_plugin_url(): string { return $this->plugin_url; }
}

CMS_Contact::instance();
