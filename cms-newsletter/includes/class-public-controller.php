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
        $router->addRoute('POST', '/newsletter/subscribe', [$this, 'handle_subscribe']);
        $router->addRoute('GET', '/newsletter/unsubscribe/:token', [$this, 'unsubscribe_page']);
    }

    public function archive_page(): void
    {
        $repository = CMS_Newsletter_Repository::instance();
        $settings = $repository->get_settings();
        $stats = $repository->get_dashboard_stats();
        $campaigns = $repository->get_recent_campaigns(3);
        $notice = (string) ($_GET['newsletter_notice'] ?? '');
        $message = match ($notice) {
            'subscribed' => 'Danke! Deine Anmeldung wurde gespeichert.',
            'double-opt-in' => 'Fast geschafft – prüfe bitte dein Postfach zur Bestätigung.',
            'exists' => 'Diese E-Mail-Adresse ist bereits im Newsletter erfasst.',
            'invalid' => 'Bitte gib eine gültige E-Mail-Adresse ein.',
            'unsubscribed' => 'Du wurdest erfolgreich vom Newsletter abgemeldet.',
            'rate-limit' => 'Bitte warte kurz, bevor du dich erneut anmeldest.',
            default => '',
        };
        $messageType = in_array($notice, ['invalid', 'rate-limit'], true) ? 'error' : 'success';
        $csrfToken = class_exists('CMS\\Security') ? \CMS\Security::instance()->generateToken('newsletter_subscribe') : '';
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
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            $this->redirect_with_notice('invalid');
        }

        $security = class_exists('CMS\\Security') ? \CMS\Security::instance() : null;
        if ($this->isHoneypotFilled($_POST)) {
            $this->redirect_with_notice('subscribed');
        }

        if ($this->record_and_check_rate_limit()) {
            $this->redirect_with_notice('rate-limit');
        }

        if ($security === null || !$security->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'newsletter_subscribe')) {
            $this->redirect_with_notice('invalid');
            exit;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect_with_notice('invalid');
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
            $this->redirect_with_notice('exists');
        }
        if (!($result['success'] ?? false)) {
            $this->log_error('subscribe_failed', ['error' => (string) ($result['error'] ?? 'unknown')]);
            $this->redirect_with_notice('invalid');
        }

        $this->redirect_with_notice(!empty($settings['require_double_opt_in']) ? 'double-opt-in' : 'subscribed');
    }

    public function unsubscribe_page(string $token): void
    {
        $token = $this->normalize_unsubscribe_token($token);
        if ($token === '') {
            $this->redirect_with_notice('invalid');
        }

        CMS_Newsletter_Repository::instance()->unsubscribe_by_token($token);
        $this->redirect_with_notice('unsubscribed');
    }

    private function redirect_with_notice(string $notice): void
    {
        $allowed = ['subscribed', 'double-opt-in', 'exists', 'invalid', 'unsubscribed', 'rate-limit'];
        $notice = in_array($notice, $allowed, true) ? $notice : 'invalid';
        $siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
        header('Location: ' . $siteUrl . '/newsletter?newsletter_notice=' . rawurlencode($notice), true, 303);
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
}
