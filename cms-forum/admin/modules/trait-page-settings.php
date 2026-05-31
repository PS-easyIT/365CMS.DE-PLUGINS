<?php
/**
 * CMS Forum – Admin Settings Trait
 *
 * @package CMS_Forum
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_Forum_Page_Settings_Trait
{
    /**
     * Einstellungen rendern.
     */
    public static function render_settings(): void
    {
        self::check_access();
        self::render_admin_page('Forum-Einstellungen', static function (): void {
            $error   = null;
            $success = null;

            // Einstellungs-Schlüssel mit Defaults
            $settingKeys = [
                'forum_name'              => 'Community Forum',
                'threads_per_page'        => '20',
                'posts_per_page'          => '15',
                'flood_interval_post'     => '30',
                'flood_interval_thread'   => '120',
                'max_attachment_size'     => '5242880',
                'allowed_extensions'      => 'jpg,jpeg,png,gif,webp,pdf,zip',
                'max_title_length'        => '120',
                'min_post_length'         => '10',
                'max_post_length'         => '50000',
                'members_can_edit_time'   => '30',
                'enable_bbcode'           => '1',
                'enable_polls'            => '1',
                'enable_attachments'      => '1',
                'enable_likes'            => '1',
                'enable_signatures'       => '1',
                'enable_dark_mode'        => '1',
                'primary_color'           => '#3b82f6',
                'require_post_approval'   => '0',
                'guest_can_read'          => '1',
                'auto_subscribe_own'      => '1',
            ];

            // POST-Handler
            $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
            if ($requestMethod === 'POST' && isset($_POST['forum_action'])) {
                $forumAction = sanitize_key((string) ($_POST['forum_action'] ?? ''));
                if (!self::verify_nonce('forum_settings')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } else {
                    if ($forumAction === 'save_settings') {
                        foreach ($settingKeys as $key => $default) {
                            $value = self::sanitize_setting_value($key, $_POST[$key] ?? $default, $default);
                            self::save_setting($key, (string) $value);
                        }

                        // Checkboxen, die nicht gesendet werden wenn deaktiviert
                        $checkboxes = [
                            'enable_bbcode', 'enable_polls', 'enable_attachments',
                            'enable_likes', 'enable_signatures', 'enable_dark_mode',
                            'require_post_approval', 'guest_can_read', 'auto_subscribe_own',
                        ];
                        foreach ($checkboxes as $cb) {
                            self::save_setting($cb, isset($_POST[$cb]) ? '1' : '0');
                        }

                        $success = 'Einstellungen gespeichert.';
                    } elseif ($forumAction === 'recalculate_counters') {
                        $result = \CMS_Forum\Controllers\AdminController::instance()->recalculateCounters();
                        $success = $result['message'] ?? 'Zähler aktualisiert.';
                    } else {
                        $error = 'Unbekannte Aktion.';
                    }
                }
            }

            // Aktuelle Werte laden
            $settings = [];
            foreach ($settingKeys as $key => $default) {
                $settings[$key] = self::get_setting($key, $default);
            }

            $csrfToken = self::generate_nonce('forum_settings');

            include CMS_FORUM_DIR . 'admin/views/page-settings.php';
        });
    }

    /**
     * Sanitized und begrenzt Setting-Input auf sichere Werte.
     */
    private static function sanitize_setting_value(string $key, mixed $value, string $default): string
    {
        $value = is_scalar($value) ? (string) $value : $default;
        $value = trim($value);

        switch ($key) {
            case 'forum_name':
                $name = sanitize_text_field($value);
                $name = mb_substr($name, 0, 120);
                return $name !== '' ? $name : $default;

            case 'threads_per_page':
            case 'posts_per_page':
                return (string) max(5, min(100, (int) $value));

            case 'flood_interval_post':
            case 'flood_interval_thread':
                return (string) max(0, min(86400, (int) $value));

            case 'max_attachment_size':
                return (string) max(0, min(104857600, (int) $value));

            case 'max_title_length':
                return (string) max(10, min(255, (int) $value));

            case 'min_post_length':
                return (string) max(1, min(2000, (int) $value));

            case 'max_post_length':
                return (string) max(100, min(200000, (int) $value));

            case 'members_can_edit_time':
                return (string) max(0, min(10080, (int) $value));

            case 'allowed_extensions':
                $parts = preg_split('/\s*,\s*/', strtolower($value)) ?: [];
                $parts = array_filter($parts, static fn(string $part): bool => preg_match('/^[a-z0-9]{2,10}$/', $part) === 1);
                $parts = array_values(array_unique($parts));
                $parts = array_slice($parts, 0, 20);
                return !empty($parts) ? implode(',', $parts) : $default;

            case 'primary_color':
                return preg_match('/^#[0-9a-f]{6}$/i', $value) === 1 ? strtolower($value) : $default;

            default:
                return sanitize_text_field($value);
        }
    }
}
