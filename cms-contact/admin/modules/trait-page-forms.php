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

        $action = $_GET['action'] ?? 'list';
        $formId = (int) ($_GET['id'] ?? 0);

        $notice = '';
        $error  = '';

        // POST-Aktionen verarbeiten
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postAction = $_POST['form_action'] ?? '';

            switch ($postAction) {
                case 'create_form':
                    $result = self::handle_create_form();
                    if (is_int($result)) {
                        header('Location: ' . self::ADMIN_BASE_URL . '?section=forms&action=edit&id=' . $result . '&notice=created');
                        exit;
                    }
                    $error = $result;
                    break;

                case 'update_form':
                    $result = self::handle_update_form($formId);
                    if ($result === true) {
                        $notice = 'Formular erfolgreich gespeichert.';
                    } else {
                        $error = $result;
                    }
                    break;

                case 'delete_form':
                    $deleteId = (int) ($_POST['id'] ?? 0);
                    $result = self::handle_delete_form($deleteId);
                    if ($result === true) {
                        header('Location: ' . self::ADMIN_BASE_URL . '?section=forms&notice=deleted');
                        exit;
                    }
                    $error = $result;
                    break;

                case 'save_field':
                    $result = self::handle_save_field($formId);
                    if ($result === true) {
                        header('Location: ' . self::ADMIN_BASE_URL . '?section=forms&action=fields&id=' . $formId . '&notice=field_saved');
                        exit;
                    }
                    $error = $result;
                    break;

                case 'delete_field':
                    $fieldId = (int) ($_POST['field_id'] ?? 0);
                    CMS_Contact_Fields::instance()->delete($fieldId);
                    header('Location: ' . self::ADMIN_BASE_URL . '?section=forms&action=fields&id=' . $formId . '&notice=field_deleted');
                    exit;

                case 'reorder_fields':
                    $orderedIds = $_POST['field_order'] ?? [];
                    // Komma-separierter String von JS → Array umwandeln
                    if (is_string($orderedIds)) {
                        $orderedIds = array_filter(explode(',', $orderedIds), fn($v) => $v !== '');
                    }
                    if (is_array($orderedIds) && !empty($orderedIds)) {
                        $orderedIds = array_map('intval', $orderedIds);
                        CMS_Contact_Fields::instance()->update_order($formId, $orderedIds);
                    }
                    header('Location: ' . self::ADMIN_BASE_URL . '?section=forms&action=fields&id=' . $formId . '&notice=reordered');
                    exit;
            }
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

    // ── Formular-Handler ──────────────────────────────────────────────────────

    private static function handle_create_form(): int|string
    {
        if (!self::verify_nonce('contact_forms')) {
            return 'Sicherheitscheck fehlgeschlagen.';
        }

        $title    = sanitize_text_field($_POST['title'] ?? '');
        $slug     = sanitize_text_field($_POST['slug'] ?? '');
        $template = sanitize_text_field($_POST['template'] ?? 'classic');

        if (empty($title)) {
            return 'Titel ist erforderlich.';
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
        $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));

        if (CMS_Contact_Forms::instance()->slug_exists($slug, $formId)) {
            return 'Dieser Slug ist bereits vergeben.';
        }

        CMS_Contact_Forms::instance()->update($formId, [
            'title'           => $title,
            'slug'            => $slug,
            'template'        => sanitize_text_field($_POST['template'] ?? 'classic'),
            'description'     => sanitize_text_field($_POST['description'] ?? ''),
            'recipient'       => filter_var($_POST['recipient'] ?? '', FILTER_VALIDATE_EMAIL) ?: null,
            'cc_recipients'   => sanitize_text_field($_POST['cc_recipients'] ?? ''),
            'subject_prefix'  => sanitize_text_field($_POST['subject_prefix'] ?? ''),
            'success_message' => sanitize_text_field($_POST['success_message'] ?? ''),
            'redirect_url'    => filter_var($_POST['redirect_url'] ?? '', FILTER_VALIDATE_URL) ?: null,
            'enable_captcha'  => (int) ($_POST['enable_captcha'] ?? 0),
            'enable_honeypot' => (int) ($_POST['enable_honeypot'] ?? 1),
            'rate_limit'      => max(0, (int) ($_POST['rate_limit'] ?? 3)),
            'status'          => in_array($_POST['status'] ?? '', ['active', 'inactive'], true)
                ? $_POST['status'] : 'active',
            'custom_css'      => str_replace(['</style>', '<script', '</script>'], '', $_POST['custom_css'] ?? ''),
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

        $data = [
            'form_id'       => $formId,
            'field_name'    => preg_replace('/[^a-z0-9_]/', '', strtolower(sanitize_text_field($_POST['field_name'] ?? ''))),
            'field_label'   => sanitize_text_field($_POST['field_label'] ?? ''),
            'field_type'    => sanitize_text_field($_POST['field_type'] ?? 'text'),
            'placeholder'   => sanitize_text_field($_POST['placeholder'] ?? ''),
            'default_value' => sanitize_text_field($_POST['default_value'] ?? ''),
            'validation'    => sanitize_text_field($_POST['validation'] ?? ''),
            'is_required'   => (int) ($_POST['is_required'] ?? 0),
            'field_width'   => sanitize_text_field($_POST['field_width'] ?? 'full'),
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
            $data['options_json'] = $options;
        }

        if ($fieldId > 0) {
            $fields->update($fieldId, $data);
        } else {
            $fields->create($data);
        }

        return true;
    }
}
