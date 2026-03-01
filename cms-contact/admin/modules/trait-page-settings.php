<?php
/**
 * CMS Contact – Admin Settings Trait
 *
 * @package CMS_Contact
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_Contact_Page_Settings_Trait
{
    /**
     * Einstellungsseite rendern
     */
    public static function render_settings(): void
    {
        self::check_access();

        $notice = '';
        $error  = '';
        $tab    = sanitize_text_field($_GET['tab'] ?? 'general');

        // POST-Handler
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_nonce('contact_settings')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $action = $_POST['settings_action'] ?? '';

                switch ($action) {
                    case 'save_general':
                        self::save_setting('global_recipient',     filter_var($_POST['global_recipient'] ?? '', FILTER_VALIDATE_EMAIL) ?: '');
                        self::save_setting('from_name',            sanitize_text_field($_POST['from_name'] ?? ''));
                        self::save_setting('from_email',           filter_var($_POST['from_email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '');
                        self::save_setting('enable_notifications', isset($_POST['enable_notifications']) ? '1' : '0');
                        self::save_setting('store_submissions',    isset($_POST['store_submissions']) ? '1' : '0');
                        self::save_setting('auto_delete_days',     (string) max(0, (int) ($_POST['auto_delete_days'] ?? 90)));
                        $notice = 'Allgemeine Einstellungen gespeichert.';
                        break;

                    case 'save_design':
                        self::save_setting('default_template', sanitize_text_field($_POST['default_template'] ?? 'classic'));
                        self::save_setting('primary_color',    sanitize_text_field($_POST['primary_color'] ?? '#3b82f6'));
                        self::save_setting('success_color',    sanitize_text_field($_POST['success_color'] ?? '#10b981'));
                        self::save_setting('error_color',      sanitize_text_field($_POST['error_color'] ?? '#ef4444'));
                        $notice = 'Design-Einstellungen gespeichert.';
                        break;

                    case 'cleanup':
                        $days = max(1, (int) ($_POST['cleanup_days'] ?? 90));
                        $deleted = CMS_Contact_Submissions::instance()->cleanup_old($days);
                        $notice = "{$deleted} alte Nachricht(en) gelöscht.";
                        break;
                }
            }
        }

        $csrfToken = self::generate_nonce('contact_settings');

        // Aktuelle Settings laden
        $settings = [
            'global_recipient'     => self::get_setting('global_recipient'),
            'from_name'            => self::get_setting('from_name', '365CMS Kontakt'),
            'from_email'           => self::get_setting('from_email'),
            'enable_notifications' => self::get_setting('enable_notifications', '1'),
            'store_submissions'    => self::get_setting('store_submissions', '1'),
            'auto_delete_days'     => self::get_setting('auto_delete_days', '90'),
            'default_template'     => self::get_setting('default_template', 'classic'),
            'primary_color'        => self::get_setting('primary_color', '#3b82f6'),
            'success_color'        => self::get_setting('success_color', '#10b981'),
            'error_color'          => self::get_setting('error_color', '#ef4444'),
        ];

        $templates = CMS_Contact_Forms::get_available_templates();

        include CMS_CONTACT_PLUGIN_DIR . 'admin/views/page-settings.php';
    }
}
