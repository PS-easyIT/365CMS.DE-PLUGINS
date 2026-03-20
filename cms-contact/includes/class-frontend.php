<?php
/**
 * CMS Contact – Frontend Controller
 *
 * Registriert Routen und rendert Kontaktformulare im Frontend.
 *
 * @package CMS_Contact
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Contact_Frontend
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->register_routes();
    }

    // ── Routing ───────────────────────────────────────────────────────────────

    private function register_routes(): void
    {
        if (!class_exists('CMS\Router')) {
            return;
        }

        $router = \CMS\Router::instance();

        // Basis-Route: /contact (ohne Slug) – erstes aktives Formular als Fallback
        $router->addRoute('GET',  '/contact',              [$this, 'render_default_form']);
        $router->addRoute('POST', '/contact',              [$this, 'handle_default_submit']);

        // Dynamische Route: /contact/:slug
        $router->addRoute('GET',  '/contact/:slug',        [$this, 'render_form']);
        $router->addRoute('POST', '/contact/:slug',        [$this, 'handle_submit']);

        // AJAX-Submit-Endpoint
        $router->addRoute('POST', '/api/contact/:slug/submit', [$this, 'handle_ajax_submit']);
    }

    // ── Formular rendern ──────────────────────────────────────────────────────

    /**
     * Ermittelt den passenden Formular-Slug für /contact (ohne Slug).
     * 
     * Prüft Query-Parameter (expert, speaker, company) und sucht ein
     * passendes Booking-Template. Fallback: erstes aktives Formular.
     *
     * @return string|null Slug des Formulars oder null
     */
    private function resolve_default_slug(): ?string
    {
        $forms = CMS_Contact_Forms::instance();

        // Versuche passendes Booking-Template anhand Query-Parameter
        $templateMap = [
            'expert'  => 'booking-expert',
            'speaker' => 'booking-event',
            'company' => 'booking-service',
        ];

        foreach ($templateMap as $param => $template) {
            if (!empty($_GET[$param])) {
                // Suche ein aktives Formular mit diesem Template
                $all = $forms->get_all('active');
                foreach ($all as $f) {
                    if (($f['template'] ?? '') === $template) {
                        return $f['slug'];
                    }
                }
            }
        }

        // Fallback: erstes aktives Formular
        $all = $forms->get_all('active');
        if (!empty($all)) {
            return $all[0]['slug'];
        }

        return null;
    }

    /**
     * /contact (ohne Slug) – GET: Standard-Formular rendern
     */
    public function render_default_form(): void
    {
        $slug = $this->resolve_default_slug();
        if ($slug) {
            $this->render_form($slug);
        } else {
            http_response_code(404);
            if (function_exists('render_404')) {
                render_404();
            } else {
                echo '<h1>404 – Kein Kontaktformular vorhanden</h1>';
            }
        }
    }

    /**
     * /contact (ohne Slug) – POST: Standard-Formular verarbeiten
     */
    public function handle_default_submit(): void
    {
        $slug = $this->resolve_default_slug();
        if ($slug) {
            $this->handle_submit($slug);
        } else {
            http_response_code(404);
        }
    }

    /**
     * Kontaktformular-Seite rendern
     */
    public function render_form(string $slug): void
    {
        $form = CMS_Contact_Forms::instance()->get_by_slug($slug);

        if (!$form) {
            http_response_code(404);
            if (function_exists('render_404')) {
                render_404();
            } else {
                echo '<h1>404 – Formular nicht gefunden</h1>';
            }
            return;
        }

        $fields   = $this->filter_public_fields(CMS_Contact_Fields::instance()->get_by_form((int) $form['id']));
        $csrfToken = \CMS\Security::instance()->generateToken('contact_' . $form['slug']);
        $privacySettings = $this->get_privacy_settings();

        // Flash-Messages
        $success = $_SESSION['contact_success'] ?? null;
        $error   = $_SESSION['contact_error']   ?? null;
        $old     = $_SESSION['contact_old']     ?? [];
        unset($_SESSION['contact_success'], $_SESSION['contact_error'], $_SESSION['contact_old']);

        $template = $form['template'] ?? 'classic';
        $templateFile = CMS_CONTACT_PLUGIN_DIR . 'templates/template-' . $template . '.php';

        if (!file_exists($templateFile)) {
            $templateFile = CMS_CONTACT_PLUGIN_DIR . 'templates/template-classic.php';
        }

        // Theme-Manager für Header/Footer
        $theme = null;
        if (class_exists('CMS\ThemeManager')) {
            $theme = \CMS\ThemeManager::instance();
        }

        // SEO – Titel und Beschreibung werden direkt im Template via <title> gesetzt
        // SEOService bietet keine setTitle()/setDescription()-Methoden

        // Template laden
        include $templateFile;
    }

    // ── Formular-Verarbeitung (POST) ──────────────────────────────────────────

    /**
     * Standard-POST-Submit (mit Redirect)
     */
    public function handle_submit(string $slug): void
    {
        $form = CMS_Contact_Forms::instance()->get_by_slug($slug);

        if (!$form) {
            http_response_code(404);
            return;
        }

        $result = $this->process_submission($form);

        if ($result['success']) {
            $_SESSION['contact_success'] = $form['success_message']
                ?? 'Vielen Dank für Ihre Nachricht!';

            $redirectUrl = $this->resolve_form_redirect($form, '/contact/' . $slug . '?sent=1');
            if (function_exists('safe_redirect')) {
                safe_redirect($redirectUrl);
            } else {
                header('Location: ' . $redirectUrl, true, 302);
            }
        } else {
            $_SESSION['contact_error'] = $result['error'];
            $_SESSION['contact_old']   = $result['old_data'] ?? [];
            if (function_exists('safe_redirect')) {
                safe_redirect('/contact/' . $slug);
            } else {
                header('Location: /contact/' . $slug, true, 302);
            }
        }
        exit;
    }

    /**
     * AJAX-Submit
     */
    public function handle_ajax_submit(string $slug): void
    {
        header('Content-Type: application/json');

        $form = CMS_Contact_Forms::instance()->get_by_slug($slug);

        if (!$form) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Formular nicht gefunden']);
            exit;
        }

        $result = $this->process_submission($form);

        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => $form['success_message'] ?? 'Vielen Dank für Ihre Nachricht!',
            ]);
        } else {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error'   => $result['error'],
                'errors'  => $result['field_errors'] ?? [],
            ]);
        }
        exit;
    }

    // ── Submission-Verarbeitung ───────────────────────────────────────────────

    /**
     * Formular-Submission verarbeiten
     */
    private function process_submission(array $form): array
    {
        $formId = (int) $form['id'];
        $slug   = $form['slug'];

        // CSRF-Check
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!\CMS\Security::instance()->verifyToken($csrfToken, 'contact_' . $slug)) {
            return ['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen. Bitte laden Sie die Seite neu.'];
        }

        // Honeypot-Check
        if (!empty($form['enable_honeypot']) && !empty($_POST['website_url'])) {
            return ['success' => false, 'error' => 'Spam erkannt.'];
        }

        // CAPTCHA-Check (session-basiert)
        if (!empty($form['enable_captcha'])) {
            $answer   = (int) ($_POST['captcha_answer'] ?? 0);
            $expected = (int) ($_SESSION['captcha_expected_' . $slug] ?? -1);
            unset($_SESSION['captcha_expected_' . $slug]);
            if ($answer !== $expected || $expected < 0) {
                return ['success' => false, 'error' => 'Die Captcha-Antwort ist falsch. Bitte versuchen Sie es erneut.'];
            }
        }

        // Rate-Limiting
        if (!$this->check_rate_limit($formId, (int) ($form['rate_limit'] ?? 3))) {
            return ['success' => false, 'error' => 'Zu viele Anfragen. Bitte versuchen Sie es später erneut.'];
        }

        // Felder laden und validieren
        $fields      = $this->filter_public_fields(CMS_Contact_Fields::instance()->get_by_form($formId));
        $fieldErrors = [];
        $oldData     = [];
        $meta        = [];
        $privacySettings = $this->get_privacy_settings();

        $senderName  = '';
        $senderEmail = '';
        $subject     = '';
        $message     = '';

        $oldData['privacy_consent'] = !empty($_POST['privacy_consent']) ? '1' : '';

        foreach ($fields as $field) {
            $name  = $field['field_name'];
            $value = $_POST[$name] ?? '';

            if (is_string($value)) {
                $value = trim($value);
            }

            $oldData[$name] = $value;

            // Pflichtfeld-Prüfung
            if (!empty($field['is_required']) && ($value === '' || $value === null)) {
                $fieldErrors[$name] = htmlspecialchars($field['field_label']) . ' ist ein Pflichtfeld.';
                continue;
            }

            // Typ-spezifische Validierung
            if ($value !== '' && $value !== null) {
                $validationError = $this->validate_field($field, $value);
                if ($validationError !== null) {
                    $fieldErrors[$name] = $validationError;
                    continue;
                }
            }

            // Sanitierung
            $value = $this->sanitize_value($field['field_type'], $value);

            // System-Felder zuordnen
            switch ($name) {
                case 'name':
                    $senderName = $value;
                    break;
                case 'email':
                    $senderEmail = $value;
                    break;
                case 'subject':
                    $subject = $value;
                    break;
                case 'message':
                    $message = $value;
                    break;
                default:
                    // Benutzerdefinierte Felder als Meta speichern
                    if ($value !== '' && $value !== null) {
                        $meta[$name] = $value;
                    }
            }
        }

        // Fehler?
        if (!empty($fieldErrors)) {
            return [
                'success'      => false,
                'error'        => 'Bitte korrigieren Sie die markierten Felder.',
                'field_errors' => $fieldErrors,
                'old_data'     => $oldData,
            ];
        }

        if (!empty($privacySettings['required']) && empty($_POST['privacy_consent'])) {
            return [
                'success'  => false,
                'error'    => 'Bitte bestätigen Sie die Verarbeitung Ihrer personenbezogenen Daten und lesen Sie die Datenschutzerklärung.',
                'old_data' => $oldData,
            ];
        }

        if (!empty($privacySettings['required'])) {
            $meta['privacy_consent'] = '1';
            $meta['privacy_consent_confirmed_at'] = date('Y-m-d H:i:s');
            if (!empty($privacySettings['url'])) {
                $meta['privacy_policy_url'] = (string) $privacySettings['url'];
            }
        }

        // User-ID ermitteln
        $userId = null;
        if (class_exists('CMS\Auth') && \CMS\Auth::instance()->isLoggedIn()) {
            $currentUser = \CMS\Auth::instance()->currentUser();
            $userId = $currentUser ? (int)$currentUser->id : null;
        }

        // Submission speichern
        $submissions  = CMS_Contact_Submissions::instance();
        $submissionId = $submissions->create([
            'form_id'      => $formId,
            'user_id'      => $userId,
            'sender_name'  => $senderName,
            'sender_email' => $senderEmail,
            'subject'      => $subject,
            'message'      => $message,
            'user_agent'   => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ], $meta);

        $this->register_rate_limit_hit($formId);

        // E-Mail-Benachrichtigung
        $submissions->send_notification($form, [
            'sender_name'  => $senderName,
            'sender_email' => $senderEmail,
            'subject'      => $subject,
            'message'      => $message,
        ], $meta);

        // Bestätigungs-E-Mail
        $submissions->send_confirmation($form, [
            'sender_name'  => $senderName,
            'sender_email' => $senderEmail,
        ]);

        // Hook für Plugins
        if (class_exists('CMS\Hooks')) {
            \CMS\Hooks::doAction('contact_submitted', $submissionId, $formId, $meta);
        }

        return ['success' => true];
    }

    private function resolve_form_redirect(array $form, string $fallback): string
    {
        $redirectUrl = trim((string) ($form['redirect_url'] ?? ''));
        if ($redirectUrl === '') {
            return $fallback;
        }

        if (function_exists('cms_normalize_redirect_target')) {
            return cms_normalize_redirect_target($redirectUrl, false) ?? $fallback;
        }

        return $fallback;
    }

    private function filter_public_fields(array $fields): array
    {
        return array_values(array_filter($fields, static function (array $field): bool {
            return (string) ($field['field_type'] ?? '') !== 'file';
        }));
    }

    // ── Validierung ───────────────────────────────────────────────────────────

    /**
     * Feld-spezifische Validierung
     */
    private function validate_field(array $field, mixed $value): ?string
    {
        $label = htmlspecialchars($field['field_label']);

        switch ($field['field_type']) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "{$label}: Bitte geben Sie eine gültige E-Mail-Adresse ein.";
                }
                break;

            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    return "{$label}: Bitte geben Sie eine gültige URL ein.";
                }
                break;

            case 'tel':
                if (!preg_match('/^[\+\d\s\-\/\(\)]{6,30}$/', $value)) {
                    return "{$label}: Bitte geben Sie eine gültige Telefonnummer ein.";
                }
                break;

            case 'number':
                if (!is_numeric($value)) {
                    return "{$label}: Bitte geben Sie eine gültige Zahl ein.";
                }
                break;

            case 'date':
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    return "{$label}: Bitte geben Sie ein gültiges Datum ein.";
                }
                break;

            case 'select':
            case 'radio':
                // Optionen prüfen
                $options = [];
                if (!empty($field['options_json'])) {
                    $decoded = json_decode($field['options_json'], true);
                    if (is_array($decoded)) {
                        $options = array_column($decoded, 'value');
                    }
                }
                if (!empty($options) && !in_array($value, $options, true)) {
                    return "{$label}: Ungültige Auswahl.";
                }
                break;
        }

        // Benutzerdefinierte Validierung (Regex)
        if (!empty($field['validation']) && !preg_match($field['validation'], $value)) {
            return "{$label}: Eingabe entspricht nicht dem erwarteten Format.";
        }

        return null;
    }

    /**
     * Wert sanitieren
     */
    private function sanitize_value(string $type, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return match ($type) {
            'email'    => filter_var($value, FILTER_SANITIZE_EMAIL) ?: '',
            'url'      => filter_var($value, FILTER_SANITIZE_URL) ?: '',
            'number'   => (string) (int) $value,
            'textarea' => strip_tags((string) $value, '<br>'),
            default    => htmlspecialchars(strip_tags((string) $value), ENT_QUOTES, 'UTF-8'),
        };
    }

    // ── Rate-Limiting ─────────────────────────────────────────────────────────

    /**
     * Prüft das sessionbasierte Rate-Limit pro Formular.
     */
    private function check_rate_limit(int $formId, int $maxPerHour): bool
    {
        if ($maxPerHour <= 0) {
            return true;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return true;
        }

        $bucketKey = 'contact_rate_limit_' . $formId;
        $entries = $_SESSION[$bucketKey] ?? [];
        if (!is_array($entries)) {
            $entries = [];
        }

        $threshold = time() - 3600;
        $entries = array_values(array_filter($entries, static function ($timestamp) use ($threshold): bool {
            return is_int($timestamp) && $timestamp >= $threshold;
        }));

        $_SESSION[$bucketKey] = $entries;

        return count($entries) < $maxPerHour;
    }

    private function register_rate_limit_hit(int $formId): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $bucketKey = 'contact_rate_limit_' . $formId;
        $entries = $_SESSION[$bucketKey] ?? [];
        if (!is_array($entries)) {
            $entries = [];
        }

        $entries[] = time();
        $_SESSION[$bucketKey] = $entries;
    }

    // ── Template-Hilfsmethoden ────────────────────────────────────────────────

    /**
     * Einzelnes Formularfeld rendern (für Templates)
     */
    public static function render_field(array $field, string $csrfToken = '', array $old = [], array $errors = []): string
    {
        $name        = htmlspecialchars($field['field_name']);
        $label       = htmlspecialchars($field['field_label']);
        $type        = $field['field_type'];
        $placeholder = htmlspecialchars($field['placeholder'] ?? '');
        $required    = !empty($field['is_required']);
        $value       = htmlspecialchars($old[$field['field_name']] ?? $field['default_value'] ?? '');
        $width       = $field['field_width'] ?? 'full';
        $cssClass    = $field['css_class'] ?? '';
        $description = $field['description'] ?? '';
        $hasError    = isset($errors[$field['field_name']]);
        $errorMsg    = $hasError ? htmlspecialchars($errors[$field['field_name']]) : '';

        $widthClass = match ($width) {
            'half'      => 'contact-field--half',
            'third'     => 'contact-field--third',
            'two-third' => 'contact-field--two-third',
            default     => 'contact-field--full',
        };

        $errorClass = $hasError ? ' contact-field--error' : '';

        $html = "<div class=\"contact-field {$widthClass}{$errorClass} {$cssClass}\">\n";

        if ($type !== 'hidden') {
            $html .= "  <label for=\"cf-{$name}\" class=\"contact-label\">";
            $html .= $label;
            if ($required) {
                $html .= ' <span class="contact-required">*</span>';
            }
            $html .= "</label>\n";
        }

        switch ($type) {
            case 'textarea':
                $html .= "  <textarea id=\"cf-{$name}\" name=\"{$name}\" class=\"contact-input contact-textarea\" placeholder=\"{$placeholder}\"";
                if ($required) { $html .= ' required'; }
                $html .= ">{$value}</textarea>\n";
                break;

            case 'select':
                $html .= "  <select id=\"cf-{$name}\" name=\"{$name}\" class=\"contact-input contact-select\"";
                if ($required) { $html .= ' required'; }
                $html .= ">\n";
                $html .= "    <option value=\"\">{$placeholder}</option>\n";
                if (!empty($field['options_json'])) {
                    $options = json_decode($field['options_json'], true) ?: [];
                    foreach ($options as $opt) {
                        $optVal   = htmlspecialchars($opt['value'] ?? '');
                        $optLabel = htmlspecialchars($opt['label'] ?? $opt['value'] ?? '');
                        $selected = ($value === $optVal) ? ' selected' : '';
                        $html .= "    <option value=\"{$optVal}\"{$selected}>{$optLabel}</option>\n";
                    }
                }
                $html .= "  </select>\n";
                break;

            case 'radio':
                $html .= "  <div class=\"contact-radio-group\">\n";
                if (!empty($field['options_json'])) {
                    $options = json_decode($field['options_json'], true) ?: [];
                    foreach ($options as $i => $opt) {
                        $optVal   = htmlspecialchars($opt['value'] ?? '');
                        $optLabel = htmlspecialchars($opt['label'] ?? $opt['value'] ?? '');
                        $checked  = ($value === $optVal) ? ' checked' : '';
                        $html .= "    <label class=\"contact-radio-label\">";
                        $html .= "<input type=\"radio\" name=\"{$name}\" value=\"{$optVal}\"{$checked}";
                        if ($required && $i === 0) { $html .= ' required'; }
                        $html .= "> {$optLabel}</label>\n";
                    }
                }
                $html .= "  </div>\n";
                break;

            case 'checkbox':
                $checked = !empty($value) ? ' checked' : '';
                $html .= "  <label class=\"contact-checkbox-label\">";
                $html .= "<input type=\"checkbox\" id=\"cf-{$name}\" name=\"{$name}\" value=\"1\"{$checked}";
                if ($required) { $html .= ' required'; }
                $html .= "> {$placeholder}</label>\n";
                break;

            case 'file':
                return '';

            case 'hidden':
                $html .= "  <input type=\"hidden\" name=\"{$name}\" value=\"{$value}\">\n";
                break;

            default:
                $html .= "  <input type=\"{$type}\" id=\"cf-{$name}\" name=\"{$name}\" class=\"contact-input\" value=\"{$value}\" placeholder=\"{$placeholder}\"";
                if ($required) { $html .= ' required'; }
                $html .= ">\n";
        }

        if ($hasError) {
            $html .= "  <div class=\"contact-error-msg\">{$errorMsg}</div>\n";
        }

        if (!empty($description) && $type !== 'hidden') {
            $html .= "  <small class=\"contact-hint\">" . htmlspecialchars($description) . "</small>\n";
        }

        $html .= "</div>\n";

        return $html;
    }

    public static function render_privacy_consent(array $form = [], array $old = []): string
    {
        $settings = self::instance()->get_privacy_settings();
        if (empty($settings['required'])) {
            return '';
        }

        $checked = !empty($old['privacy_consent']) ? ' checked' : '';
        $policyUrl = htmlspecialchars((string) ($settings['url'] ?? ''), ENT_QUOTES, 'UTF-8');

        $html = "<div class=\"contact-field contact-field-full contact-privacy-field\">\n";
        $html .= "  <div class=\"contact-privacy-box\">\n";
        $html .= "    <label class=\"contact-checkbox-label contact-privacy-checkbox\">";
        $html .= "<input type=\"checkbox\" id=\"cf-privacy-consent\" name=\"privacy_consent\" value=\"1\" required{$checked}>";
        $html .= "<span class=\"contact-privacy-text\">Ich stimme der Verarbeitung meiner personenbezogenen Daten zum Zweck der Bearbeitung meiner Anfrage zu.";
        if ($policyUrl !== '') {
            $html .= " <a href=\"{$policyUrl}\" class=\"contact-privacy-link\" target=\"_blank\" rel=\"noopener noreferrer\">Datenschutzerklärung ansehen</a>.";
        }
        $html .= "</span></label>\n";
        $html .= "    <small class=\"contact-hint\">Ohne diese Bestätigung kann das Formular nicht abgesendet werden.</small>\n";
        $html .= "  </div>\n";
        $html .= "</div>\n";

        return $html;
    }

    private function get_privacy_settings(): array
    {
        $url = trim($this->get_contact_setting('privacy_policy_url', ''));
        if ($url === '') {
            if (function_exists('home_url')) {
                $url = (string) home_url('/datenschutz');
            } elseif (defined('SITE_URL')) {
                $url = rtrim((string) SITE_URL, '/') . '/datenschutz';
            }
        }

        return [
            'required' => $this->get_contact_setting('require_privacy_consent', '1') === '1',
            'url' => $url,
        ];
    }

    private function get_contact_setting(string $key, string $default = ''): string
    {
        try {
            $db = \CMS\Database::instance();
            $p  = $db->getPrefix();
            $stmt = $db->prepare("SELECT setting_value FROM {$p}contact_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $value = $stmt->fetchColumn();

            return $value !== false ? (string) $value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }
}
