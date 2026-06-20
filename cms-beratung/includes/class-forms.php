<?php
/**
 * CMS Beratung – frontend consultation form handling.
 *
 * @package CMS_Beratung
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Beratung_Forms
{
    /** @param array<string,mixed> $page @return array{success:bool,message:string} */
    public static function handle_submission(array $page): array
    {
        $settings = CMS_Beratung_Settings::all();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || (string) ($_POST['beratung_form_action'] ?? '') !== 'submit_request') {
            return ['success' => false, 'message' => ''];
        }

        if (!class_exists('CMS\\Security') || !\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'beratung_form_' . (int) ($page['id'] ?? 0))) {
            return ['success' => false, 'message' => (string) $settings['error_message']];
        }

        if (($settings['honeypot_enabled'] ?? '1') === '1' && trim((string) ($_POST['website'] ?? '')) !== '') {
            $_POST['is_spam'] = '1';
        }

        $email = trim((string) ($_POST['sender_email'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        $consent = !empty($_POST['consent']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '' || !$consent) {
            return ['success' => false, 'message' => (string) $settings['error_message']];
        }

        $data = [
            'landingpage_id' => (int) ($page['id'] ?? 0),
            'sender_name' => (string) ($_POST['sender_name'] ?? ''),
            'sender_email' => $email,
            'phone' => (string) ($_POST['phone'] ?? ''),
            'company' => (string) ($_POST['company'] ?? ''),
            'topic' => (string) ($_POST['topic'] ?? ''),
            'message' => $message,
            'consent' => $consent,
            'copy_to_sender' => !empty($_POST['copy_to_sender']),
            'is_spam' => !empty($_POST['is_spam']),
        ];

        if (($settings['form_storage_enabled'] ?? '1') === '1') {
            CMS_Beratung_Storage::instance()->create_submission($data);
        }

        if (empty($data['is_spam'])) {
            self::send_notification($page, $data, $settings);
        }

        return ['success' => true, 'message' => (string) $settings['success_message']];
    }

    /** @param array<string,mixed> $page @param array<string,mixed> $data @param array<string,string> $settings */
    private static function send_notification(array $page, array $data, array $settings): void
    {
        $recipient = (string) ($settings['contact_recipient_email'] ?? '');
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $subject = trim((string) ($settings['contact_subject_prefix'] ?? '[CMS Beratung]')) . ' ' . ((string) ($data['topic'] ?? '') ?: 'Neue Beratungsanfrage');
        $body = "Neue Beratungsanfrage über: " . (string) ($page['public_title'] ?? 'CMS Beratung') . "\n\n"
            . "Name: " . (string) ($data['sender_name'] ?? '') . "\n"
            . "E-Mail: " . (string) ($data['sender_email'] ?? '') . "\n"
            . "Telefon: " . (string) ($data['phone'] ?? '') . "\n"
            . "Unternehmen: " . (string) ($data['company'] ?? '') . "\n"
            . "Thema: " . (string) ($data['topic'] ?? '') . "\n\n"
            . (string) ($data['message'] ?? '');

        if (function_exists('cms_mail')) {
            cms_mail($recipient, $subject, $body);
            return;
        }

        if (function_exists('mail')) {
            @mail($recipient, $subject, $body);
        }
    }
}
