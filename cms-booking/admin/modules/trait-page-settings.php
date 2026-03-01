<?php
/**
 * CMS Booking – Admin Einstellungen Trait
 *
 * @package CMS_Booking
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_Booking_Page_Settings_Trait
{
    public function render_settings_page(): void
    {
        $error   = '';
        $success = '';
        $db      = \CMS\Database::instance();
        $p       = $db->getPrefix();

        // POST-Handler
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings_action'])) {
            if (!class_exists('CMS\Security') || !\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'booking_settings')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $settingKeys = [
                    'admin_email', 'from_name', 'from_email',
                    'default_duration', 'default_buffer', 'default_timezone',
                    'default_currency', 'booking_advance_min', 'booking_advance_max',
                    'cancellation_hours', 'auto_confirm', 'send_reminders',
                    'reminder_hours', 'primary_color',
                ];

                $stmt = $db->prepare(
                    "INSERT INTO {$p}booking_settings (setting_key, setting_value) VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
                );

                foreach ($settingKeys as $key) {
                    if (array_key_exists($key, $_POST)) {
                        $stmt->execute([$key, sanitize_text_field($_POST[$key])]);
                    }
                }
                $success = 'Einstellungen gespeichert.';
            }
        }

        $csrfToken = '';
        if (class_exists('CMS\Security')) {
            $csrfToken = \CMS\Security::instance()->generateToken('booking_settings');
        }

        // Alle Einstellungen laden
        $stmt = $db->prepare("SELECT setting_key, setting_value FROM {$p}booking_settings");
        $stmt->execute();
        $settings = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        include CMS_BOOKING_PLUGIN_DIR . 'admin/views/settings.php';
    }
}
