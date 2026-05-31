<?php
/**
 * CMS Contact – Admin Forms Trait
 *
 * Verwaltung (CRUD) der Kontaktformulare inkl. Feld-Editor.
 *
 * @package CMS_Contact
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_Contact_Page_Forms_Trait
{
    /**
     * Formulare-Seite rendern (Liste oder Editor)
     */
    public static function render_forms(): void
    {
        self::check_access();
        self::enqueue_admin_assets();

        $action = sanitize_text_field((string) ($_GET['action'] ?? 'list'));
        if (!in_array($action, ['list', 'new', 'edit', 'fields'], true)) {
            $action = 'list';
        }
        $formId = (int) ($_GET['id'] ?? 0);

        $notice = '';
        $error  = '';

        // POST-Aktionen verarbeiten
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $postResult = self::process_forms_post($formId);
            $notice = $postResult['notice'] ?? '';
            $error = $postResult['error'] ?? '';
        }

        // GET-Notices
        if (!empty($_GET['notice'])) {
            $notice = match ($_GET['notice']) {
                'created'       => 'Formular erfolgreich erstellt.',
                'deleted'       => 'Formular erfolgreich gelöscht.',
                'field_saved'   => 'Feld erfolgreich gespeichert.',
                'field_deleted' => 'Feld erfolgreich gelöscht.',
                'reordered'     => 'Feldreihenfolge aktualisiert.',
                default         => '',
            };
        }

        $csrfToken = self::generate_nonce('contact_forms');

        switch ($action) {
            case 'new':
                $templates = CMS_Contact_Forms::get_available_templates();
                $activeSection = 'forms';
                include CMS_CONTACT_PLUGIN_DIR . 'admin/views/page-form-new.php';
                break;

            case 'edit':
                $form = CMS_Contact_Forms::instance()->get_by_id($formId);
                if (!$form) {
                    $error = 'Formular nicht gefunden.';
                    $allForms = CMS_Contact_Forms::instance()->get_all();
                    $activeSection = 'forms';
                    include CMS_CONTACT_PLUGIN_DIR . 'admin/views/page-forms-list.php';
                    return;
                }
                $templates = CMS_Contact_Forms::get_available_templates();
                $activeSection = 'forms';
                include CMS_CONTACT_PLUGIN_DIR . 'admin/views/page-form-edit.php';
                break;

            case 'fields':
                $form = CMS_Contact_Forms::instance()->get_by_id($formId);
                if (!$form) {
                    $error = 'Formular nicht gefunden.';
                    $allForms = CMS_Contact_Forms::instance()->get_all();
                    $activeSection = 'forms';
                    include CMS_CONTACT_PLUGIN_DIR . 'admin/views/page-forms-list.php';
                    return;
                }
                $fields     = CMS_Contact_Fields::instance()->get_by_form($formId);
                $fieldTypes = CMS_Contact_Fields::get_field_types();
                $fieldWidths = CMS_Contact_Fields::get_field_widths();

                $editFieldId = (int) ($_GET['edit_field'] ?? 0);
                $editField   = $editFieldId > 0 ? CMS_Contact_Fields::instance()->get_by_id($editFieldId) : null;

                $activeSection = 'forms';
                include CMS_CONTACT_PLUGIN_DIR . 'admin/views/page-form-fields.php';
                break;

            default:
                $allForms = CMS_Contact_Forms::instance()->get_all();
                $activeSection = 'forms';
                include CMS_CONTACT_PLUGIN_DIR . 'admin/views/page-forms-list.php';
        }
    }

    /**
     * @return array{notice?: string, error?: string}
     */
    private static function process_forms_post(int $formId): array
    {
        $postAction = (string) ($_POST['form_action'] ?? '');
        $handlers = [
            'create_form' => 'handle_create_form_post',
            'update_form' => 'handle_update_form_post',
            'delete_form' => 'handle_delete_form_post',
            'save_field' => 'handle_save_field_post',
            'delete_field' => 'handle_delete_field_post',
            'reorder_fields' => 'handle_reorder_fields_post',
        ];

        if (!isset($handlers[$postAction])) {
            return [];
        }

        $handler = $handlers[$postAction];
        return self::{$handler}($formId);
    }

    /**
     * @return array{notice?: string, error?: string}
     */
    private static function handle_create_form_post(int $formId): array
    {
        unset($formId);
        $result = self::handle_create_form();
        if (is_int($result)) {
            self::redirect_to_admin('forms', ['action' => 'edit', 'id' => $result, 'notice' => 'created']);
        }

        return ['error' => $result];
    }

    /**
     * @return array{notice?: string, error?: string}
     */
    private static function handle_update_form_post(int $formId): array
    {
        $result = self::handle_update_form($formId);
        return $result === true
            ? ['notice' => 'Formular erfolgreich gespeichert.']
            : ['error' => $result];
    }

    /**
     * @return array{notice?: string, error?: string}
     */
    private static function handle_delete_form_post(int $formId): array
    {
        $deleteId = (int) ($_POST['id'] ?? $formId);
        $result = self::handle_delete_form($deleteId);
        if ($result === true) {
            self::redirect_to_admin('forms', ['notice' => 'deleted']);
        }

        return ['error' => $result];
    }

    /**
     * @return array{notice?: string, error?: string}
     */
    private static function handle_save_field_post(int $formId): array
    {
        $result = self::handle_save_field($formId);
        if ($result === true) {
            self::redirect_to_admin('forms', ['action' => 'fields', 'id' => $formId, 'notice' => 'field_saved']);
        }

        return ['error' => $result];
    }

    /**
     * @return array{notice?: string, error?: string}
     */
    private static function handle_delete_field_post(int $formId): array
    {
        if (!self::verify_nonce('contact_forms')) {
            return ['error' => 'Sicherheitscheck fehlgeschlagen.'];
        }

        $fieldId = (int) ($_POST['field_id'] ?? 0);
        if ($fieldId <= 0) {
            return ['error' => 'Ungültige Feld-ID.'];
        }

        $field = CMS_Contact_Fields::instance()->get_by_id($fieldId);
        if (!$field || (int) ($field['form_id'] ?? 0) !== $formId) {
            return ['error' => 'Das gewünschte Feld gehört nicht zu diesem Formular.'];
        }

        CMS_Contact_Fields::instance()->delete($fieldId);
        self::redirect_to_admin('forms', ['action' => 'fields', 'id' => $formId, 'notice' => 'field_deleted']);
    }

    /**
     * @return array{notice?: string, error?: string}
     */
    private static function handle_reorder_fields_post(int $formId): array
    {
        if (!self::verify_nonce('contact_forms')) {
            return ['error' => 'Sicherheitscheck fehlgeschlagen.'];
        }

        $orderedIds = $_POST['field_order'] ?? [];
        if (is_string($orderedIds)) {
            $orderedIds = array_filter(explode(',', $orderedIds), static fn ($value): bool => $value !== '');
        }

        if (is_array($orderedIds) && $orderedIds !== []) {
            $orderedIds = array_map('intval', $orderedIds);
            CMS_Contact_Fields::instance()->update_order($formId, $orderedIds);
        }

        self::redirect_to_admin('forms', ['action' => 'fields', 'id' => $formId, 'notice' => 'reordered']);
    }

    // ── Formular-Handler ──────────────────────────────────────────────────────

    private static function handle_create_form(): int|string
    {
        if (!self::verify_nonce('contact_forms')) {
            return 'Sicherheitscheck fehlgeschlagen.';
        }

        $title    = sanitize_text_field($_POST['title'] ?? '');
        $slug     = sanitize_text_field($_POST['slug'] ?? '');
        $template = sanitize_text_field($_POST['template'] ?? 'classic');
        $availableTemplates = array_keys(CMS_Contact_Forms::get_available_templates());

        if (empty($title)) {
            return 'Titel ist erforderlich.';
        }

        if (!in_array($template, $availableTemplates, true)) {
            return 'Das gewählte Template ist nicht verfügbar.';
        }

        $forms = CMS_Contact_Forms::instance();

        if (empty($slug)) {
            $slug = $forms->generate_slug($title);
        } else {
            $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));
        }

        if ($forms->slug_exists($slug)) {
            return 'Dieser Slug ist bereits vergeben.';
        }

        $formId = $forms->create([
            'title'           => $title,
            'slug'            => $slug,
            'template'        => $template,
            'description'     => sanitize_text_field($_POST['description'] ?? ''),
            'recipient'       => filter_var($_POST['recipient'] ?? '', FILTER_VALIDATE_EMAIL) ?: null,
            'success_message' => sanitize_text_field($_POST['success_message'] ?? 'Vielen Dank für Ihre Nachricht!'),
        ]);

        // Standard-Felder anlegen
        CMS_Contact_Fields::instance()->create_default_fields($formId);

        return $formId;
    }

    private static function handle_update_form(int $formId): true|string
    {
        if (!self::verify_nonce('contact_forms')) {
            return 'Sicherheitscheck fehlgeschlagen.';
        }

        $title = sanitize_text_field($_POST['title'] ?? '');
        if (empty($title)) {
            return 'Titel ist erforderlich.';
        }

        $slug = sanitize_text_field($_POST['slug'] ?? '');
        $template = sanitize_text_field($_POST['template'] ?? 'classic');
        $availableTemplates = array_keys(CMS_Contact_Forms::get_available_templates());
        if (!in_array($template, $availableTemplates, true)) {
            return 'Das gewählte Template ist nicht verfügbar.';
        }

        $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));
        if ($slug === '') {
            $slug = CMS_Contact_Forms::instance()->generate_slug($title);
        }

        if (CMS_Contact_Forms::instance()->slug_exists($slug, $formId)) {
            return 'Dieser Slug ist bereits vergeben.';
        }

        $ccRecipients = self::sanitize_recipient_list((string) ($_POST['cc_recipients'] ?? ''));
        if ($ccRecipients === null) {
            return 'Mindestens eine CC-E-Mail-Adresse ist ungültig.';
        }

        $redirectUrl = trim((string) ($_POST['redirect_url'] ?? ''));
        $normalizedRedirectUrl = null;
        if ($redirectUrl !== '') {
            if (function_exists('cms_normalize_redirect_target')) {
                $normalizedRedirectUrl = cms_normalize_redirect_target($redirectUrl, false);
                if ($normalizedRedirectUrl === null) {
                    return 'Weiterleitungs-URL muss eine gültige interne URL der Website sein.';
                }
            } else {
                $normalizedRedirectUrl = self::normalize_internal_redirect_path($redirectUrl);
                if ($normalizedRedirectUrl === null) {
                    return 'Weiterleitungs-URL muss eine gültige interne URL der Website sein.';
                }
            }
        }

        CMS_Contact_Forms::instance()->update($formId, [
            'title'           => $title,
            'slug'            => $slug,
            'template'        => $template,
            'description'     => sanitize_text_field($_POST['description'] ?? ''),
            'recipient'       => filter_var($_POST['recipient'] ?? '', FILTER_VALIDATE_EMAIL) ?: null,
            'cc_recipients'   => $ccRecipients,
            'subject_prefix'  => sanitize_text_field($_POST['subject_prefix'] ?? ''),
            'success_message' => sanitize_text_field($_POST['success_message'] ?? ''),
            'redirect_url'    => $normalizedRedirectUrl,
            'enable_captcha'  => (int) ($_POST['enable_captcha'] ?? 0),
            'enable_honeypot' => (int) ($_POST['enable_honeypot'] ?? 1),
            'rate_limit'      => max(0, (int) ($_POST['rate_limit'] ?? 3)),
            'status'          => in_array((string) ($_POST['status'] ?? ''), ['active', 'inactive'], true)
                ? (string) $_POST['status'] : 'active',
            'custom_css'      => CMS_Contact_Frontend::sanitize_custom_css((string) ($_POST['custom_css'] ?? '')),
        ]);

        return true;
    }

    private static function handle_delete_form(int $formId): true|string
    {
        if (!self::verify_nonce('contact_forms')) {
            return 'Sicherheitscheck fehlgeschlagen.';
        }

        if ($formId <= 0) {
            return 'Ungültige Formular-ID.';
        }

        CMS_Contact_Forms::instance()->delete($formId);
        return true;
    }

    // ── Feld-Handler ──────────────────────────────────────────────────────────

    private static function handle_save_field(int $formId): true|string
    {
        if (!self::verify_nonce('contact_forms')) {
            return 'Sicherheitscheck fehlgeschlagen.';
        }

        $fieldId = (int) ($_POST['field_id'] ?? 0);
        $fields  = CMS_Contact_Fields::instance();
        $allowedFieldTypes = array_keys(CMS_Contact_Fields::get_field_types());
        $allowedWidths = array_keys(CMS_Contact_Fields::get_field_widths());

        $fieldType = sanitize_text_field($_POST['field_type'] ?? 'text');
        if (!in_array($fieldType, $allowedFieldTypes, true)) {
            return 'Der gewählte Feldtyp ist nicht verfügbar.';
        }

        $fieldWidth = sanitize_text_field($_POST['field_width'] ?? 'full');
        if (!in_array($fieldWidth, $allowedWidths, true)) {
            return 'Die gewählte Feldbreite ist ungültig.';
        }

        $validation = sanitize_text_field($_POST['field_validation'] ?? '');
        if ($validation !== '' && !self::is_valid_custom_regex($validation)) {
            return 'Die hinterlegte Validierungsregel ist kein gültiger regulärer Ausdruck.';
        }

        $data = [
            'form_id'       => $formId,
            'field_name'    => preg_replace('/[^a-z0-9_]/', '', strtolower(sanitize_text_field($_POST['field_name'] ?? ''))),
            'field_label'   => sanitize_text_field($_POST['field_label'] ?? ''),
            'field_type'    => $fieldType,
            'placeholder'   => sanitize_text_field($_POST['placeholder'] ?? ''),
            'default_value' => sanitize_text_field($_POST['default_value'] ?? ''),
            'validation'    => $validation,
            'is_required'   => (int) ($_POST['is_required'] ?? 0),
            'field_width'   => $fieldWidth,
            'css_class'     => sanitize_text_field($_POST['css_class'] ?? ''),
            'description'   => sanitize_text_field($_POST['description'] ?? ''),
        ];

        if (empty($data['field_label'])) {
            return 'Feld-Label ist erforderlich.';
        }

        if (empty($data['field_name'])) {
            $data['field_name'] = preg_replace('/[^a-z0-9_]/', '_', strtolower($data['field_label']));
        }

        // Optionen für Select/Radio
        if (in_array($data['field_type'], ['select', 'radio'], true)) {
            $optionLabels = $_POST['option_labels'] ?? [];
            $optionValues = $_POST['option_values'] ?? [];

            // Wenn Optionen als Textarea (ein Eintrag pro Zeile) gesendet werden
            if (!is_array($optionLabels) && isset($_POST['field_options'])) {
                $lines = array_filter(array_map('trim', explode("\n", (string)$_POST['field_options'])), fn($l) => $l !== '');
                $optionLabels = $lines;
                $optionValues = $lines;
            }

            $options = [];
            if (is_array($optionLabels)) {
                foreach ($optionLabels as $i => $label) {
                    $label = trim((string) $label);
                    if ($label !== '') {
                        $options[] = [
                            'label' => $label,
                            'value' => trim((string) ($optionValues[$i] ?? $label)),
                        ];
                    }
                }
            }

            if ($options === []) {
                return 'Bitte hinterlege mindestens eine Auswahloption.';
            }

            $data['options_json'] = $options;
        }

        if ($fieldId > 0) {
            $existingField = $fields->get_by_id($fieldId);
            if (!$existingField || (int) ($existingField['form_id'] ?? 0) !== $formId) {
                return 'Das gewünschte Feld gehört nicht zu diesem Formular.';
            }
        }

        if ($fieldId > 0) {
            $fields->update($fieldId, $data);
        } else {
            $fields->create($data);
        }

        return true;
    }

    private static function sanitize_recipient_list(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $validRecipients = [];
        foreach (preg_split('/\s*,\s*/', $value) ?: [] as $recipient) {
            if ($recipient === '') {
                continue;
            }

            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                return null;
            }

            $validRecipients[] = $recipient;
        }

        return implode(', ', array_unique($validRecipients));
    }

    private static function normalize_internal_redirect_path(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (!str_starts_with($value, '/') || str_starts_with($value, '//')) {
            return null;
        }

        $path = parse_url($value, PHP_URL_PATH);
        if (!is_string($path) || $path === '' || str_contains($path, "\0")) {
            return null;
        }

        $query = parse_url($value, PHP_URL_QUERY);
        if (is_string($query) && $query !== '') {
            parse_str($query, $params);
            $query = http_build_query(is_array($params) ? $params : [], '', '&', PHP_QUERY_RFC3986);
            return $path . ($query !== '' ? '?' . $query : '');
        }

        return $path;
    }

    private static function is_valid_custom_regex(string $pattern): bool
    {
        if (mb_strlen($pattern) > 160) {
            return false;
        }

        $delimiter = $pattern[0] ?? '';
        if (!in_array($delimiter, ['/', '#', '~', '%'], true)) {
            return false;
        }

        $lastDelimiterPos = strrpos($pattern, $delimiter);
        if ($lastDelimiterPos === false || $lastDelimiterPos === 0) {
            return false;
        }

        $body = substr($pattern, 1, $lastDelimiterPos - 1);
        if ($body === '' || str_contains($body, "\0") || str_contains($body, "\n") || str_contains($body, "\r")) {
            return false;
        }

        $modifiers = substr($pattern, $lastDelimiterPos + 1);
        if ($modifiers !== '' && strspn($modifiers, 'imsxuADSUXJ') !== strlen($modifiers)) {
            return false;
        }

        if (substr_count($body, '.*') > 1 || substr_count($body, '.+') > 1) {
            return false;
        }

        return true;
    }
}
