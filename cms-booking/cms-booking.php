<?php
/**
 * Plugin Name: CMS Booking
 * Plugin URI:  https://365network.de/cms-booking
 * Description: Universelles Buchungs- und Terminverwaltungssystem – Experten, Speaker, Events, Unternehmen u. a. klinken sich ein.
 * Version:     1.0.0
 * Author:      365 Network
 * Author URI:  https://365network.de
 *
 * @package CMS_Booking
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// ── Konstanten ────────────────────────────────────────────────────────────────
define('CMS_BOOKING_VERSION',    '1.0.0');
define('CMS_BOOKING_DB_VERSION', '1');
define('CMS_BOOKING_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_BOOKING_PLUGIN_URL', '/plugins/cms-booking/');

final class CMS_Booking
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

    /* ------------------------------------------------------------------ */
    /*  Abhängigkeiten laden                                               */
    /* ------------------------------------------------------------------ */

    private function load_dependencies(): void
    {
        $inc   = CMS_BOOKING_PLUGIN_DIR . 'includes/';
        $admin = CMS_BOOKING_PLUGIN_DIR . 'admin/';

        $files = [
            $inc   . 'class-installer.php',
            $inc   . 'class-providers.php',
            $inc   . 'class-services.php',
            $inc   . 'class-availability.php',
            $inc   . 'class-bookings.php',
            $inc   . 'class-notifications.php',
            $inc   . 'class-calendar-export.php',
            $inc   . 'class-integration.php',
            $inc   . 'class-frontend.php',
            $admin . 'class-admin-menu.php',
            $admin . 'class-admin-pages.php',
        ];

        foreach ($files as $f) {
            if (file_exists($f)) {
                require_once $f;
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Hooks                                                              */
    /* ------------------------------------------------------------------ */

    private function init_hooks(): void
    {
        if (!class_exists('CMS\Hooks')) {
            return;
        }

        // Kern
        \CMS\Hooks::addAction('cms_init',            [$this, 'init_plugin'],                     10);
        \CMS\Hooks::addAction('plugin_activated',     [$this, 'on_activation'],                   10);
        \CMS\Hooks::addAction('plugin_uninstalled',   [$this, 'on_uninstall'],                    10);

        // Admin
        \CMS\Hooks::addAction('cms_admin_menu',       [CMS_Booking_Admin_Menu::class, 'register'], 10);

        // Frontend-Routen
        \CMS\Hooks::addAction('register_routes',      [CMS_Booking_Frontend::class, 'instance'],   10);

        // Assets
        \CMS\Hooks::addAction('head',                 [$this, 'enqueue_styles'],                   20);
        \CMS\Hooks::addAction('body_end',             [$this, 'enqueue_scripts'],                  20);

        // Integration – gibt anderen Plugins die Möglichkeit, sich zu registrieren
        \CMS\Hooks::addAction('cms_init',             [CMS_Booking_Integration::class, 'init'],    50);

        // DSGVO
        \CMS\Hooks::addAction('dsgvo_export_data',    [$this, 'export_user_data'],                 10);
        \CMS\Hooks::addAction('dsgvo_delete_data',    [$this, 'delete_user_data'],                 10);
    }

    /* ------------------------------------------------------------------ */
    /*  Lifecycle                                                          */
    /* ------------------------------------------------------------------ */

    public function on_activation(string $plugin): void
    {
        if ($plugin === 'cms-booking' && class_exists('CMS_Booking_Installer')) {
            CMS_Booking_Installer::install();
        }
    }

    public function on_uninstall(string $plugin): void
    {
        if ($plugin === 'cms-booking' && class_exists('CMS_Booking_Installer')) {
            CMS_Booking_Installer::uninstall();
        }
    }

    public function init_plugin(): void
    {
        if (class_exists('CMS_Booking_Installer')) {
            CMS_Booking_Installer::maybe_install();
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Assets                                                             */
    /* ------------------------------------------------------------------ */

    public function enqueue_styles(): void
    {
        $css = CMS_BOOKING_PLUGIN_DIR . 'assets/css/booking-public.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_BOOKING_PLUGIN_URL . 'assets/css/booking-public.css')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    public function enqueue_scripts(): void
    {
        $js = CMS_BOOKING_PLUGIN_DIR . 'assets/js/booking-public.js';
        if (file_exists($js)) {
            echo '<script src="'
                . htmlspecialchars(CMS_BOOKING_PLUGIN_URL . 'assets/js/booking-public.js')
                . '?v=' . filemtime($js) . '" defer></script>' . "\n";
        }
    }

    /* ------------------------------------------------------------------ */
    /*  DSGVO                                                              */
    /* ------------------------------------------------------------------ */

    public function export_user_data(int $userId): void
    {
        if (class_exists('CMS_Booking_Bookings')) {
            CMS_Booking_Bookings::instance()->export_user_data($userId);
        }
    }

    public function delete_user_data(int $userId): void
    {
        if (class_exists('CMS_Booking_Bookings')) {
            CMS_Booking_Bookings::instance()->delete_user_data($userId);
        }
    }
}

CMS_Booking::instance();
