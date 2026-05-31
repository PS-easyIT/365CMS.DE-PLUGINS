<?php
/**
 * @package CMS_Newsletter
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Newsletter_Public_Controller
{
    private const SUBSCRIBE_MIN_INTERVAL = 20;
    private const SUBSCRIBE_WINDOW = 3600;
    private const SUBSCRIBE_MAX_ATTEMPTS = 8;
    private const RATE_LIMIT_FILE_MAX_BYTES = 8192;

    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function register_routes($router): void
    {
        $router->addRoute('GET', '/newsletter', [$this, 'archive_page']);
        $router->addRoute('GET', '/en/newsletter', [$this, 'archive_page']);
        $router->addRoute('POST', '/newsletter/subscribe', [$this, 'handle_subscribe']);
        $router->addRoute('POST', '/en/newsletter/subscribe', [$this, 'handle_subscribe']);
        $router->addRoute('GET', '/newsletter/unsubscribe/:token', [$this, 'unsubscribe_page']);
        $router->addRoute('GET', '/en/newsletter/unsubscribe/:token', [$this, 'unsubscribe_page']);
        $router->addRoute('POST', '/newsletter/unsubscribe/:token', [$this, 'one_click_unsubscribe']);
        $router->addRoute('POST', '/en/newsletter/unsubscribe/:token', [$this, 'one_click_unsubscribe']);
    }

    public function archive_page(): void
    {
        $repository = CMS_Newsletter_Repository::instance();
        $settings = $repository->get_settings();
        $lang = $this->current_language();
        $copy = $this->get_public_copy($settings, $lang);
        $stats = $repository->get_dashboard_stats();
        $campaigns = $repository->get_recent_campaigns(3);
        $notice = (string) ($_GET['newsletter_notice'] ?? '');
        $message = $this->notice_message($notice, $lang);
        $messageType = in_array($notice, ['invalid', 'rate-limit'], true) ? 'error' : 'success';
        $csrfToken = class_exists('CMS\\Security') ? \CMS\Security::instance()->generateToken('newsletter_subscribe') : '';
        $subscribeAction = $this->localized_path('newsletter/subscribe', $lang);
        $theme = class_exists('CMS\\ThemeManager') ? \CMS\ThemeManager::instance() : null;

        if ($theme !== null) {
            $theme->getHeader();
        }

        include CMS_NEWSLETTER_PLUGIN_DIR . 'templates/archive-newsletter.php';

        if ($theme !== null) {
            $theme->getFooter();
        }
    }

    public function handle_subscribe(): void
    {
        $lang = $this->current_language();
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            $this->redirect_with_notice('invalid', $lang);
        }

        $security = class_exists('CMS\\Security') ? \CMS\Security::instance() : null;
        if ($this->isHoneypotFilled($_POST)) {
            $this->redirect_with_notice('subscribed', $lang);
        }

        if ($this->record_and_check_rate_limit()) {
            $this->redirect_with_notice('rate-limit', $lang);
        }

        if ($security === null || !$security->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'newsletter_subscribe')) {
            $this->redirect_with_notice('invalid', $lang);
            exit;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect_with_notice('invalid', $lang);
        }

        $repository = CMS_Newsletter_Repository::instance();
        $settings = $repository->get_settings();
        $status = !empty($settings['require_double_opt_in']) ? 'pending' : 'active';
        $payload = [
            'email' => $email,
            'first_name' => $this->sanitize_plain_text((string) ($_POST['first_name'] ?? '')),
            'last_name' => $this->sanitize_plain_text((string) ($_POST['last_name'] ?? '')),
            'segment_slug' => $this->sanitize_slug((string) ($_POST['segment_slug'] ?? 'general')),
            'status' => $status,
            'source' => 'public',
        ];
        $result = $repository->save_subscriber($payload);

        if (!($result['success'] ?? false) && str_contains((string) ($result['error'] ?? ''), 'bereits')) {
            $this->redirect_with_notice('exists', $lang);
        }
        if (!($result['success'] ?? false)) {
            $this->log_error('subscribe_failed', ['error' => (string) ($result['error'] ?? 'unknown')]);
            $this->redirect_with_notice('invalid', $lang);
        }

        $this->redirect_with_notice(!empty($settings['require_double_opt_in']) ? 'double-opt-in' : 'subscribed', $lang);
    }

    public function unsubscribe_page(string $token): void
    {
        $lang = $this->current_language();
        $token = $this->normalize_unsubscribe_token($token);
        if ($token === '') {
            $this->redirect_with_notice('invalid', $lang);
        }

        CMS_Newsletter_Repository::instance()->unsubscribe_by_token($token);
        $this->redirect_with_notice('unsubscribed', $lang);
    }

    public function one_click_unsubscribe(string $token): void
    {
        $token = $this->normalize_unsubscribe_token($token);
        if ($token === '') {
            $this->respond_one_click_result(false);
        }

        CMS_Newsletter_Repository::instance()->unsubscribe_by_token($token);
        $this->respond_one_click_result(true);
    }

    private function redirect_with_notice(string $notice, string $lang = 'de'): void
    {
        $allowed = ['subscribed', 'double-opt-in', 'exists', 'invalid', 'unsubscribed', 'rate-limit'];
        $notice = in_array($notice, $allowed, true) ? $notice : 'invalid';
        $siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
        $path = $this->localized_path('newsletter', $lang);
        header('Location: ' . $siteUrl . $path . '?newsletter_notice=' . rawurlencode($notice), true, 303);
        exit;
    }

    private function isHoneypotFilled(array $input): bool
    {
        return trim((string) ($input['website'] ?? '')) !== '';
    }

    private function record_and_check_rate_limit(): bool
    {
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($ip === '') {
            return false;
        }

        $directory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cms-newsletter-rate';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            return false;
        }

        $file = $directory . DIRECTORY_SEPARATOR . hash('sha256', $ip) . '.json';
        $now = time();
        $attempts = [];

        $handle = fopen($file, 'c+b');
        if (!is_resource($handle)) {
            return false;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return false;
            }

            $size = filesize($file);
            if (is_int($size) && $size > 0 && $size <= self::RATE_LIMIT_FILE_MAX_BYTES) {
                rewind($handle);
                $raw = stream_get_contents($handle, self::RATE_LIMIT_FILE_MAX_BYTES);
                $decoded = is_string($raw) ? json_decode($raw, true) : null;
                if (is_array($decoded)) {
                    $attempts = array_values(array_filter(array_map('intval', $decoded), static function (int $timestamp) use ($now): bool {
                        return $timestamp >= ($now - self::SUBSCRIBE_WINDOW);
                    }));
                }
            }

            $lastAttempt = $attempts !== [] ? max($attempts) : 0;
            if ($lastAttempt > 0 && ($now - $lastAttempt) < self::SUBSCRIBE_MIN_INTERVAL) {
                return true;
            }

            if (count($attempts) >= self::SUBSCRIBE_MAX_ATTEMPTS) {
                return true;
            }

            $attempts[] = $now;
            $json = json_encode($attempts, JSON_THROW_ON_ERROR);
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, $json);
        } catch (\Throwable) {
            return false;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        return false;
    }

    private function normalize_unsubscribe_token(string $token): string
    {
        $token = preg_replace('/[^a-f0-9]/i', '', trim($token)) ?? '';
        $length = strlen($token);
        return ($length >= 32 && $length <= 120) ? $token : '';
    }

    private function sanitize_plain_text(string $value, int $maxLength = 120): string
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('/[[:cntrl:]]/', '', $value) ?? '';
        return mb_substr($value, 0, $maxLength);
    }

    private function sanitize_slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]+/', '-', $value) ?? '';
        $value = trim($value, '-');
        if ($value === '') {
            return 'general';
        }

        return mb_substr($value, 0, 80);
    }

    private function log_error(string $event, array $context = []): void
    {
        $payload = $context !== [] ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        error_log('[cms-newsletter] ' . $event . $payload);
    }

    private function current_language(): string
    {
        if (function_exists('cms_plugin_public_language')) {
            return $this->normalize_lang(cms_plugin_public_language());
        }

        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
        return str_starts_with(trim($path, '/'), 'en/') || trim($path, '/') === 'en' ? 'en' : 'de';
    }

    private function normalize_lang(string $lang): string
    {
        return $lang === 'en' ? 'en' : 'de';
    }

    /**
     * @param array<string,mixed> $settings
     * @return array<string,string>
     */
    private function get_public_copy(array $settings, string $lang): array
    {
        $lang = $this->normalize_lang($lang);
        $i18nValue = static function (string $key, string $fallback) use ($settings, $lang): string {
            if (function_exists('cms_plugin_public_i18n_value')) {
                return cms_plugin_public_i18n_value($settings, $key, $lang, $fallback);
            }
            if ($lang === 'en') {
                $enKey = $key . '_en';
                if (!empty($settings[$enKey])) {
                    return (string) $settings[$enKey];
                }
            }
            if (!empty($settings[$key])) {
                return (string) $settings[$key];
            }
            return $fallback;
        };

        return [
            'kicker' => '365CMS Newsletter',
            'archive_title' => $i18nValue('archive_title', 'Newsletter'),
            'archive_description' => $i18nValue('archive_description', $lang === 'en'
                ? 'Stay up to date with new content, events, and product updates.'
                : 'Bleib ueber neue Inhalte, Events und Produkt-Updates auf dem Laufenden.'),
            'subscribe_intro' => $i18nValue('subscribe_intro', $lang === 'en'
                ? 'Sign up for product news, event updates, and new expert content.'
                : 'Melde dich fuer Produkt-News, Event-Hinweise und neue Fachbeitraege an.'),
            'signup_title' => $lang === 'en' ? 'Sign up now' : 'Jetzt anmelden',
            'website_label' => $lang === 'en' ? 'Website' : 'Website',
            'first_name' => $lang === 'en' ? 'First name' : 'Vorname',
            'last_name' => $lang === 'en' ? 'Last name' : 'Nachname',
            'optional' => $lang === 'en' ? 'optional' : 'optional',
            'email' => $lang === 'en' ? 'Email address' : 'E-Mail-Adresse',
            'segment' => $lang === 'en' ? 'Segment' : 'Segment',
            'submit' => $lang === 'en' ? 'Subscribe to newsletter' : 'Newsletter abonnieren',
            'footer_note' => $i18nValue('footer_note', $lang === 'en'
                ? 'You can unsubscribe anytime with one click.'
                : 'Du kannst dich jederzeit wieder mit einem Klick abmelden.'),
            'active_subscribers' => $lang === 'en' ? 'active subscribers' : 'aktive Abonnenten',
            'campaigns' => $lang === 'en' ? 'created campaigns' : 'angelegte Kampagnen',
            'templates' => $lang === 'en' ? 'available templates' : 'verfuegbare Templates',
            'what_you_get' => $lang === 'en' ? 'What you get' : 'Was du erhaeltst',
            'benefit_1' => $lang === 'en'
                ? 'Product and plugin updates from the 365CMS ecosystem'
                : 'Produkt- und Plugin-Updates aus dem 365CMS-Oekosystem',
            'benefit_2' => $lang === 'en'
                ? 'Updates about events, releases, and new features'
                : 'Hinweise zu Events, Releases und neuen Funktionen',
            'benefit_3' => $lang === 'en'
                ? 'Short, focused expert content instead of inbox floods'
                : 'Kurze, fokussierte Fachinhalte statt Inbox-Lawinen',
            'planning_title' => $lang === 'en' ? 'Current sending plan' : 'Aktuelle Versandplanung',
            'planning_empty' => $lang === 'en'
                ? 'No campaigns are publicly visible yet.'
                : 'Aktuell sind noch keine Kampagnen oeffentlich sichtbar vorbereitet.',
            'campaign_fallback' => $lang === 'en' ? 'Campaign' : 'Kampagne',
            'notice_subscribed' => $lang === 'en'
                ? 'Thanks! Your subscription has been saved.'
                : 'Danke! Deine Anmeldung wurde gespeichert.',
            'notice_double_opt_in' => $lang === 'en'
                ? 'Almost done - please check your inbox to confirm.'
                : 'Fast geschafft - pruefe bitte dein Postfach zur Bestaetigung.',
            'notice_exists' => $lang === 'en'
                ? 'This email address is already subscribed.'
                : 'Diese E-Mail-Adresse ist bereits im Newsletter erfasst.',
            'notice_invalid' => $lang === 'en'
                ? 'Please enter a valid email address.'
                : 'Bitte gib eine gueltige E-Mail-Adresse ein.',
            'notice_unsubscribed' => $lang === 'en'
                ? 'You have been unsubscribed successfully.'
                : 'Du wurdest erfolgreich vom Newsletter abgemeldet.',
            'notice_rate_limit' => $lang === 'en'
                ? 'Please wait a moment before trying again.'
                : 'Bitte warte kurz, bevor du dich erneut anmeldest.',
        ];
    }

    private function notice_message(string $notice, string $lang): string
    {
        $copy = $this->get_public_copy([], $lang);
        return match ($notice) {
            'subscribed' => $copy['notice_subscribed'],
            'double-opt-in' => $copy['notice_double_opt_in'],
            'exists' => $copy['notice_exists'],
            'invalid' => $copy['notice_invalid'],
            'unsubscribed' => $copy['notice_unsubscribed'],
            'rate-limit' => $copy['notice_rate_limit'],
            default => '',
        };
    }

    private function localized_path(string $path, string $lang): string
    {
        $lang = $this->normalize_lang($lang);
        if (function_exists('cms_plugin_public_localized_path')) {
            return cms_plugin_public_localized_path($path, $lang);
        }

        $trimmed = trim($path, '/');
        if ($trimmed === '') {
            return $lang === 'en' ? '/en' : '/';
        }

        return $lang === 'en' ? '/en/' . $trimmed : '/' . $trimmed;
    }

    private function respond_one_click_result(bool $success): void
    {
        http_response_code($success ? 200 : 400);
        header('Content-Type: text/plain; charset=utf-8');
        echo $success ? 'Unsubscribed' : 'Invalid unsubscribe token';
        exit;
    }
}
