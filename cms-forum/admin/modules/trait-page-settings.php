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
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forum_action'])) {
            if (!self::verify_nonce('forum_settings')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                if ($_POST['forum_action'] === 'save_settings') {
                    foreach ($settingKeys as $key => $default) {
                        $value = $_POST[$key] ?? $default;
                        if (is_string($value)) {
                            $value = sanitize_text_field($value);
                        }
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
                } elseif ($_POST['forum_action'] === 'recalculate_counters') {
                    $result = \CMS_Forum\Controllers\AdminController::instance()->recalculateCounters();
                    $success = $result['message'] ?? 'Zähler aktualisiert.';
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
    }
}
