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

        // Legacy-Alias für deutschsprachige Theme-/Menülinks: /kontakt → /contact
        $router->addRoute('GET',  '/kontakt',              [$this, 'redirect_legacy_default']);
        $router->addRoute('GET',  '/kontakt/:slug',        [$this, 'redirect_legacy_form']);

        // Basis-Route: /contact (ohne Slug) – erstes aktives Formular als Fallback
        $router->addRoute('GET',  '/contact',              [$this, 'render_default_form']);
        $router->addRoute('POST', '/contact',              [$this, 'handle_default_submit']);

        // Dynamische Route: /contact/:slug
        $router->addRoute('GET',  '/contact/:slug',        [$this, 'render_form']);
        $router->addRoute('POST', '/contact/:slug',        [$this, 'handle_submit']);

        // AJAX-Submit-Endpoint
        $router->addRoute('POST', '/api/contact/:slug/submit', [$this, 'handle_ajax_submit']);
    }

    public function redirect_legacy_default(): void
    {
        $this->redirect_legacy_contact_path('/contact');
    }

    public function redirect_legacy_form(string $slug): void
    {
        $safeSlug = preg_replace('/[^a-zA-Z0-9_-]+/', '', $slug) ?? '';
        $this->redirect_legacy_contact_path('/contact/' . ltrim($safeSlug, '/'));
    }

    private function redirect_legacy_contact_path(string $targetPath): void
    {
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
        $location = $this->build_local_redirect_path($targetPath, $query);

        if (!headers_sent()) {
            $this->send_security_headers();
            header('Location: ' . $location, true, 301);
        }

        exit;
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
        $this->send_security_headers();
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
        $fieldErrors = $_SESSION['contact_field_errors'] ?? [];
        unset($_SESSION['contact_success'], $_SESSION['contact_error'], $_SESSION['contact_old'], $_SESSION['contact_field_errors']);

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
        $this->send_security_headers();
        $form = CMS_Contact_Forms::instance()->get_by_slug($slug);

        if (!$form) {
            http_response_code(404);
            return;
        }

        $result = $this->process_submission($form);

        if ($result['success']) {
            $_SESSION['contact_success'] = $form['success_message']
                ?? 'Vielen Dank für Ihre Nachricht!';

            $redirectUrl = $this->resolve_form_redirect($form, '/contact/' . rawurlencode($slug) . '?sent=1');
            if (function_exists('safe_redirect')) {
                safe_redirect($redirectUrl);
            } else {
                header('Location: ' . $redirectUrl, true, 302);
            }
        } else {
            $_SESSION['contact_error'] = $result['error'];
            $_SESSION['contact_old']   = $result['old_data'] ?? [];
            $_SESSION['contact_field_errors'] = $result['field_errors'] ?? [];
            $fallbackUrl = '/contact/' . rawurlencode($slug);
            if (function_exists('safe_redirect')) {
                safe_redirect($fallbackUrl);
            } else {
                header('Location: ' . $fallbackUrl, true, 302);
            }
        }
        exit;
    }

    /**
     * AJAX-Submit
     */
    public function handle_ajax_submit(string $slug): void
    {
        $this->send_security_headers();
        header('Content-Type: application/json; charset=utf-8');

        $form = CMS_Contact_Forms::instance()->get_by_slug($slug);

        if (!$form) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Formular nicht gefunden'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $result = $this->process_submission($form);

        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => $form['success_message'] ?? 'Vielen Dank für Ihre Nachricht!',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error'   => $result['error'],
                'errors'  => $result['field_errors'] ?? [],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
        $csrfToken = (string) ($_POST['csrf_token'] ?? '');
        if (!\CMS\Security::instance()->verifyToken($csrfToken, 'contact_' . $slug)) {
            return ['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen. Bitte laden Sie die Seite neu.'];
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
        $this->register_rate_limit_hit($formId);

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

        $ipAddress = class_exists('CMS\\Security')
            ? \CMS\Security::getClientIp()
            : (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);

        if (class_exists('CMS\\Services\\AntispamService')) {
            $antispamResult = \CMS\Services\AntispamService::getInstance()->evaluate([
                'honeypot_value' => (string) ($_POST['website_url'] ?? ''),
                'started_at' => (int) ($_POST['contact_started_at'] ?? 0),
                'email' => $senderEmail,
                'ip_address' => $ipAddress,
                'author_name' => $senderName,
                'content' => $this->build_antispam_content($subject, $message, $meta),
                'user_agent' => $userAgent,
            ]);

            if (!empty($antispamResult['rejected'])) {
                return [
                    'success' => false,
                    'error' => $this->get_antispam_error_message((string) ($antispamResult['reason'] ?? '')),
                    'old_data' => $oldData,
                ];
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
            'user_agent'   => $userAgent,
            'ip_address'   => $ipAddress,
        ], $meta);

        // E-Mail-Benachrichtigung
        $submissions->send_notification($form, [
            'sender_name'  => $senderName,
            'sender_email' => $senderEmail,
            'subject'      => $subject,
            'message'      => $message,
            'user_agent'   => $userAgent,
            'ip_address'   => $ipAddress,
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

    private function build_local_redirect_path(string $targetPath, string $rawQuery = ''): string
    {
        $targetPath = '/' . ltrim($targetPath, '/');
        $path = parse_url($targetPath, PHP_URL_PATH);
        if (!is_string($path) || !str_starts_with($path, '/contact')) {
            $path = '/contact';
        }

        $queryString = '';
        if ($rawQuery !== '') {
            parse_str($rawQuery, $queryParams);
            if (is_array($queryParams) && $queryParams !== []) {
                $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
            }
        }

        return $path . ($queryString !== '' ? '?' . $queryString : '');
    }

    private function send_security_headers(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    private function build_antispam_content(string $subject, string $message, array $meta): string
    {
        $parts = [];

        foreach ([$subject, $message] as $value) {
            $value = trim($value);
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        foreach ($meta as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $value = trim((string) $value);
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        return implode("\n", $parts);
    }

    private function get_antispam_error_message(string $reason): string
    {
        return match ($reason) {
            'minimum_time' => 'Bitte warten Sie einen Moment und senden Sie das Formular erneut.',
            'max_links' => 'Zu viele Links in der Anfrage. Bitte kürzen Sie den Inhalt und versuchen Sie es erneut.',
            default => 'Ihre Anfrage wurde aus Sicherheitsgründen blockiert. Bitte prüfen Sie Ihre Eingaben und versuchen Sie es erneut.',
        };
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
                if ($this->sanitize_public_url((string) $value) === '') {
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
        if (!empty($field['validation'])) {
            $pattern = (string) $field['validation'];
            if (@preg_match($pattern, '') === false) {
                return "{$label}: Die konfigurierte Validierungsregel ist ungültig.";
            }

            if (preg_match($pattern, (string) $value) !== 1) {
                return "{$label}: Eingabe entspricht nicht dem erwarteten Format.";
            }
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
            'email'    => mb_substr((string) (filter_var($value, FILTER_SANITIZE_EMAIL) ?: ''), 0, 254),
            'url'      => $this->sanitize_public_url((string) $value),
            'number'   => (string) (int) $value,
            'tel'      => mb_substr(trim(preg_replace('/[^0-9+\s\-\/()]/u', '', (string) $value) ?? ''), 0, 64),
            'textarea' => mb_substr(trim(strip_tags((string) $value)), 0, 5000),
            default    => mb_substr(trim(strip_tags((string) $value)), 0, 1000),
        };
    }

    private function sanitize_public_url(string $value): string
    {
        $url = trim(filter_var($value, FILTER_SANITIZE_URL) ?: '');
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme'])) {
            return '';
        }

        return in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true) ? mb_substr($url, 0, 2048) : '';
    }

    public static function sanitize_map_embed_url(string $value): string
    {
        $url = trim(filter_var($value, FILTER_SANITIZE_URL) ?: '');
        if ($url === '' || mb_strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        $allowedHosts = [
            'www.google.com',
            'maps.google.com',
            'www.google.de',
            'maps.google.de',
            'www.openstreetmap.org',
        ];

        if (!in_array($host, $allowedHosts, true)) {
            return '';
        }

        if (str_contains($host, 'google') && !str_starts_with($path, '/maps/embed')) {
            return '';
        }

        if ($host === 'www.openstreetmap.org' && $path !== '/export/embed.html') {
            return '';
        }

        return $url;
    }

    public static function render_custom_css(array $form): string
    {
        $css = self::sanitize_custom_css((string) ($form['custom_css'] ?? ''));
        if ($css === '') {
            return '';
        }

        return "<style>\n" . $css . "\n</style>\n";
    }

    public static function sanitize_custom_css(string $css): string
    {
        $css = mb_substr(trim(strip_tags($css)), 0, 4000);
        if ($css === '') {
            return '';
        }

        $css = str_ireplace(['</style', '<style'], '', $css);
        $css = preg_replace('/@import\s+/i', '', $css) ?? $css;
        $css = preg_replace('/@charset\s+[^;]+;/i', '', $css) ?? $css;
        $css = preg_replace('/expression\s*\(/i', '', $css) ?? $css;
        $css = preg_replace('/javascript\s*:/i', '', $css) ?? $css;
        $css = preg_replace('/url\s*\(\s*["\']?\s*(?:javascript|data):/i', 'url(#blocked-', $css) ?? $css;
        $css = preg_replace('/(?:behavior|-moz-binding)\s*:/i', 'blocked-property:', $css) ?? $css;
        $css = preg_replace('/(?:backdrop-filter|filter)\s*:[^;{}]+;?/i', '', $css) ?? $css;

        return trim($css);
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

        if (class_exists('CMS\\Security')) {
            $ipAddress = \CMS\Security::getClientIp();
            if (!\CMS\Security::checkDbRateLimit($ipAddress, 'contact_form_' . $formId, $maxPerHour, 3600)) {
                return false;
            }
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
        if (class_exists('CMS\\Security')) {
            \CMS\Security::recordDbRateLimitAttempt(\CMS\Security::getClientIp(), 'contact_form_' . $formId, 'contact-form');
        }

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
        unset($csrfToken);

        $name        = htmlspecialchars((string) $field['field_name'], ENT_QUOTES, 'UTF-8');
        $label       = htmlspecialchars((string) $field['field_label'], ENT_QUOTES, 'UTF-8');
        $type        = self::normalize_public_field_type((string) ($field['field_type'] ?? 'text'));
        $placeholder = htmlspecialchars((string) ($field['placeholder'] ?? ''), ENT_QUOTES, 'UTF-8');
        $required    = !empty($field['is_required']);
        $value       = htmlspecialchars((string) ($old[$field['field_name']] ?? $field['default_value'] ?? ''), ENT_QUOTES, 'UTF-8');
        $width       = $field['field_width'] ?? 'full';
        $cssClass    = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', (string) ($field['css_class'] ?? '')) ?? '';
        $description = $field['description'] ?? '';
        $hasError    = isset($errors[$field['field_name']]);
        $errorMsg    = $hasError ? htmlspecialchars((string) $errors[$field['field_name']], ENT_QUOTES, 'UTF-8') : '';
        $fieldId     = 'cf-' . preg_replace('/[^a-z0-9\-_]/i', '-', (string) ($field['field_name'] ?? 'field'));
        $hintId      = $description !== '' && $type !== 'hidden' ? $fieldId . '-hint' : '';
        $errorId     = $hasError ? $fieldId . '-error' : '';
        $describedBy = trim(implode(' ', array_filter([$hintId, $errorId])));
        $ariaInvalid = $hasError ? ' aria-invalid="true"' : '';
        $ariaRequired = $required ? ' aria-required="true"' : '';
        $ariaDescribedBy = $describedBy !== '' ? ' aria-describedby="' . htmlspecialchars($describedBy, ENT_QUOTES, 'UTF-8') . '"' : '';

        $widthClass = match ($width) {
            'half'      => 'contact-field--half',
            'third'     => 'contact-field--third',
            'two-third' => 'contact-field--two-third',
            default     => 'contact-field--full',
        };

        $errorClass = $hasError ? ' contact-field--error' : '';

        $html = "<div class=\"contact-field {$widthClass}{$errorClass} {$cssClass}\">\n";

        if ($type !== 'hidden' && $type !== 'radio') {
            $html .= "  <label for=\"cf-{$name}\" class=\"contact-label\">";
            $html .= $label;
            if ($required) {
                $html .= ' <span class="contact-required">*</span>';
            }
            $html .= "</label>\n";
        }

        switch ($type) {
            case 'textarea':
                $html .= "  <textarea id=\"cf-{$name}\" name=\"{$name}\" class=\"contact-input contact-textarea\" placeholder=\"{$placeholder}\"{$ariaInvalid}{$ariaRequired}{$ariaDescribedBy}";
                if ($required) { $html .= ' required'; }
                $html .= ">{$value}</textarea>\n";
                break;

            case 'select':
                $html .= "  <select id=\"cf-{$name}\" name=\"{$name}\" class=\"contact-input contact-select\"{$ariaInvalid}{$ariaRequired}{$ariaDescribedBy}";
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
                $html .= "  <fieldset class=\"contact-choice-group contact-radio-group\"{$ariaInvalid}{$ariaRequired}{$ariaDescribedBy}>\n";
                $html .= "    <legend class=\"contact-label\">{$label}";
                if ($required) {
                    $html .= ' <span class="contact-required">*</span>';
                }
                $html .= "</legend>\n";
                if (!empty($field['options_json'])) {
                    $options = json_decode($field['options_json'], true) ?: [];
                    foreach ($options as $i => $opt) {
                        $optVal   = htmlspecialchars($opt['value'] ?? '');
                        $optLabel = htmlspecialchars($opt['label'] ?? $opt['value'] ?? '');
                        $checked  = ($value === $optVal) ? ' checked' : '';
                        $optionId = $fieldId . '-' . $i;
                        $html .= "    <div class=\"contact-choice-item\">";
                        $html .= "<input type=\"radio\" id=\"{$optionId}\" name=\"{$name}\" value=\"{$optVal}\"{$checked}{$ariaInvalid}{$ariaRequired}{$ariaDescribedBy}";
                        if ($required && $i === 0) { $html .= ' required'; }
                        $html .= ">";
                        $html .= "<label for=\"{$optionId}\" class=\"contact-radio-label\">{$optLabel}</label></div>\n";
                    }
                }
                $html .= "  </fieldset>\n";
                break;

            case 'checkbox':
                $checked = !empty($value) ? ' checked' : '';
                $html .= "  <div class=\"contact-choice-group\">";
                $html .= "<input type=\"checkbox\" id=\"cf-{$name}\" name=\"{$name}\" value=\"1\"{$checked}{$ariaInvalid}{$ariaRequired}{$ariaDescribedBy}";
                if ($required) { $html .= ' required'; }
                $html .= ">";
                $html .= "<label for=\"cf-{$name}\" class=\"contact-checkbox-label\">" . ($placeholder !== '' ? $placeholder : $label) . "</label></div>\n";
                break;

            case 'file':
                return '';

            case 'hidden':
                $html .= "  <input type=\"hidden\" name=\"{$name}\" value=\"{$value}\">\n";
                break;

            default:
                $html .= "  <input type=\"{$type}\" id=\"cf-{$name}\" name=\"{$name}\" class=\"contact-input\" value=\"{$value}\" placeholder=\"{$placeholder}\"{$ariaInvalid}{$ariaRequired}{$ariaDescribedBy}";
                if ($required) { $html .= ' required'; }
                $html .= ">\n";
        }

        if ($hasError) {
            $html .= "  <div class=\"contact-error-msg contact-error-text\" id=\"{$errorId}\" role=\"alert\">{$errorMsg}</div>\n";
        }

        if (!empty($description) && $type !== 'hidden') {
            $html .= "  <small class=\"contact-hint\" id=\"{$hintId}\">" . htmlspecialchars($description) . "</small>\n";
        }

        $html .= "</div>\n";

        return $html;
    }

    private static function normalize_public_field_type(string $type): string
    {
        $type = strtolower(trim($type));
        $allowed = ['text', 'email', 'url', 'tel', 'number', 'date', 'textarea', 'select', 'radio', 'checkbox', 'hidden', 'file'];

        return in_array($type, $allowed, true) ? $type : 'text';
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

        if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, '/')) {
            $url = '';
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
