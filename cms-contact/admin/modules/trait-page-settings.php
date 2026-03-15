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
            $postResult = self::process_settings_post();
            $notice = $postResult['notice'] ?? '';
            $error = $postResult['error'] ?? '';
            $tab = $postResult['tab'] ?? $tab;
        }

        $csrfToken = self::generate_nonce('contact_settings');

        // Aktuelle Settings laden
        $settings = [
            'admin_email'        => self::get_setting('admin_email'),
            'from_name'          => self::get_setting('from_name', '365CMS Kontakt'),
            'from_email'         => self::get_setting('from_email'),
            'send_confirmation'  => self::get_setting('send_confirmation', '0'),
            'default_template'   => self::get_setting('default_template', 'classic'),
            'primary_color'      => self::get_setting('primary_color', '#3b82f6'),
            'border_radius'      => self::get_setting('border_radius', '8'),
        ];

        $templates = CMS_Contact_Forms::get_available_templates();

        $activeSection = 'settings';
        include CMS_CONTACT_PLUGIN_DIR . 'admin/views/page-settings.php';
    }

    /**
     * @return array{notice?: string, error?: string, tab?: string}
     */
    private static function process_settings_post(): array
    {
        if (!self::verify_nonce('contact_settings')) {
            return ['error' => 'Sicherheitscheck fehlgeschlagen.'];
        }

        $action = (string) ($_POST['settings_action'] ?? '');
        $handlers = [
            'save_settings' => 'handle_save_contact_settings_post',
            'cleanup_submissions' => 'handle_cleanup_submissions_post',
            'cleanup_spam' => 'handle_cleanup_spam_post',
        ];

        if (!isset($handlers[$action])) {
            return [];
        }

        $handler = $handlers[$action];
        return self::{$handler}();
    }

    /**
     * @return array{notice?: string, error?: string, tab?: string}
     */
    private static function handle_save_contact_settings_post(): array
    {
        self::save_setting('admin_email', filter_var($_POST['admin_email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '');
        self::save_setting('from_name', sanitize_text_field($_POST['from_name'] ?? ''));
        self::save_setting('from_email', filter_var($_POST['from_email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '');
        self::save_setting('send_confirmation', isset($_POST['send_confirmation']) ? '1' : '0');
        self::save_setting('default_template', sanitize_text_field($_POST['default_template'] ?? 'classic'));
        self::save_setting('primary_color', sanitize_text_field($_POST['primary_color'] ?? '#3b82f6'));
        self::save_setting('border_radius', (string) max(0, (int) ($_POST['border_radius'] ?? 8)));

        return ['notice' => 'Einstellungen gespeichert.'];
    }

    /**
     * @return array{notice?: string, error?: string, tab?: string}
     */
    private static function handle_cleanup_submissions_post(): array
    {
        $days = max(1, (int) ($_POST['older_than_days'] ?? 90));
        $deleted = CMS_Contact_Submissions::instance()->cleanup_old($days);

        return [
            'notice' => $deleted . ' alte Nachricht(en) gelöscht.',
            'tab' => 'cleanup',
        ];
    }

    /**
     * @return array{notice?: string, error?: string, tab?: string}
     */
    private static function handle_cleanup_spam_post(): array
    {
        $deleted = CMS_Contact_Submissions::instance()->cleanup_spam();

        return [
            'notice' => $deleted . ' Spam-Nachricht(en) gelöscht.',
            'tab' => 'cleanup',
        ];
    }
}
