<?php
/**
 * Plugin Name: CMS Booking
 * Plugin URI:  https://365network.de/cms-booking
 * Description: Universelles Buchungs- und Terminverwaltungssystem – Experten, Speaker, Events, Unternehmen u. a. klinken sich ein.
 * Version:     3.0.6
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
defined('CMS_BOOKING_VERSION') || define('CMS_BOOKING_VERSION', '3.0.6');
defined('CMS_BOOKING_DB_VERSION') || define('CMS_BOOKING_DB_VERSION', '2');
defined('CMS_BOOKING_PLUGIN_DIR') || define('CMS_BOOKING_PLUGIN_DIR', dirname(__FILE__) . '/');
defined('CMS_BOOKING_PLUGIN_URL') || define('CMS_BOOKING_PLUGIN_URL', '/plugins/cms-booking/');

if (!class_exists('CMS_Booking', false)) {
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
        // Member-Dashboard
        \CMS\Hooks::addAction('member_dashboard_init', [$this, 'register_member_section'],          20);

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
            try {
                CMS_Booking_Installer::install();
            } catch (\Throwable $e) {
                error_log('cms-booking activation skipped: ' . $e->getMessage());
            }
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
            try {
                CMS_Booking_Installer::maybe_install();
            } catch (\Throwable $e) {
                error_log('cms-booking init install skipped: ' . $e->getMessage());
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Assets                                                             */
    /* ------------------------------------------------------------------ */

    public function enqueue_styles(): void
    {
        if (!$this->is_booking_public_route()) {
            return;
        }

        $css = CMS_BOOKING_PLUGIN_DIR . 'assets/css/booking-public.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_BOOKING_PLUGIN_URL . 'assets/css/booking-public.css', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    public function enqueue_scripts(): void
    {
        if (!$this->is_booking_public_route()) {
            return;
        }

        $js = CMS_BOOKING_PLUGIN_DIR . 'assets/js/booking-public.js';
        if (file_exists($js)) {
            echo '<script src="'
                . htmlspecialchars(CMS_BOOKING_PLUGIN_URL . 'assets/js/booking-public.js', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($js) . '" defer></script>' . "\n";
        }
    }

    private function is_booking_public_route(): bool
    {
        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
        $path = '/' . trim($path, '/');

        return $path === '/booking' || str_starts_with($path, '/booking/');
    }

    /* ------------------------------------------------------------------ */
    /*  Member Dashboard                                                    */
    /* ------------------------------------------------------------------ */

    public function register_member_section(\CMS\Member\PluginDashboardRegistry $registry): void
    {
        $registry->register([
            'plugin'          => 'cms-booking',
            'slug'            => 'booking',
            'label'           => 'Buchungen',
            'icon'            => '🗓️',
            'color'           => '#16a34a',
            'category'        => 'plugins',
            'priority'        => 60,
            'admin_url'       => '/admin/plugins/booking/bookings',
            'render_callback' => [$this, 'render_member_page'],
        ]);
    }

    public function render_member_page(object $user, array $params = []): void
    {
        $bookings = class_exists('CMS_Booking_Bookings')
            ? CMS_Booking_Bookings::instance()->get_by_user((int) $user->id, 0, 20)
            : [];

        $statusColors = [
            'pending'   => 'inactive',
            'confirmed' => 'active',
            'cancelled' => 'danger',
            'completed' => 'active',
        ];
        ?>
        <div class="cms-member-section">
            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <p><strong>Keine Buchungen vorhanden</strong></p>
                    <p class="text-muted">Sobald Termine gebucht wurden, erscheinen sie hier.</p>
                </div>
            <?php else: ?>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Anbieter</th>
                                <th>Datum</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $row): ?>
                            <?php $statusClass = $statusColors[$row['status'] ?? ''] ?? 'inactive'; ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['service_name'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($row['provider_name'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($row['booking_date'] ?? '—'); ?></td>
                                <td>
                                    <span class="status-badge <?php echo htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($row['status'] ?? '—'); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
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
}

CMS_Booking::instance();
