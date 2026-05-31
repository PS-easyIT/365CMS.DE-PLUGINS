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
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST' && isset($_POST['settings_action'])) {
            if (!self::can_manage_admin_actions()) {
                $error = 'Keine Berechtigung für diese Aktion.';
            } elseif (!class_exists('CMS\Security') || !\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'booking_settings')) {
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

                $sanitizeValue = static function (string $key): string {
                    return match ($key) {
                        'admin_email', 'from_email' => (string) (filter_var($_POST[$key] ?? '', FILTER_VALIDATE_EMAIL) ?: ''),
                        'default_duration' => (string) max(5, (int) ($_POST[$key] ?? 60)),
                        'default_buffer' => (string) max(0, (int) ($_POST[$key] ?? 15)),
                        'booking_advance_min' => (string) max(0, (int) ($_POST[$key] ?? 1)),
                        'booking_advance_max' => (string) max(1, (int) ($_POST[$key] ?? 90)),
                        'cancellation_hours' => (string) max(0, (int) ($_POST[$key] ?? 24)),
                        'reminder_hours' => (string) max(1, (int) ($_POST[$key] ?? 24)),
                        'default_timezone' => in_array((string) ($_POST[$key] ?? ''), timezone_identifiers_list(), true)
                            ? (string) $_POST[$key]
                            : 'Europe/Berlin',
                        'default_currency' => in_array((string) ($_POST[$key] ?? ''), ['EUR', 'CHF', 'USD', 'GBP'], true)
                            ? (string) $_POST[$key]
                            : 'EUR',
                        'auto_confirm', 'send_reminders' => !empty($_POST[$key]) ? '1' : '0',
                        'primary_color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST[$key] ?? '')) === 1
                            ? (string) $_POST[$key]
                            : '#3b82f6',
                        default => sanitize_text_field((string) ($_POST[$key] ?? '')),
                    };
                };

                try {
                    foreach ($settingKeys as $key) {
                        if (array_key_exists($key, $_POST) || in_array($key, ['auto_confirm', 'send_reminders'], true)) {
                            $stmt->execute([$key, $sanitizeValue($key)]);
                        }
                    }
                    $success = 'Einstellungen gespeichert.';
                } catch (\Throwable $e) {
                    $error = 'Einstellungen konnten nicht gespeichert werden.';
                }
            }
        }

        $csrfToken = '';
        if (class_exists('CMS\Security')) {
            $csrfToken = \CMS\Security::instance()->generateToken('booking_settings');
        }

        // Alle Einstellungen laden
        try {
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM {$p}booking_settings");
            $stmt->execute();
            $settings = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        } catch (\Throwable $e) {
            $settings = [];
            if ($error === '') {
                $error = 'Einstellungen konnten nicht geladen werden.';
            }
        }

        include CMS_BOOKING_PLUGIN_DIR . 'admin/views/settings.php';
    }
}
