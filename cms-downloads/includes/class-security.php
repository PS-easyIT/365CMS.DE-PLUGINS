<?php
/**
 * Security helpers: external redirect tokens and download rate limiting.
 *
 * @package CMS_Downloads
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Downloads_Security
{
    private const TOKEN_TTL_SECONDS = 900;

    public static function external_continue_token(int $downloadId, string $slug): array
    {
        $expires = time() + self::TOKEN_TTL_SECONDS;
        $payload = $downloadId . '|' . $slug . '|' . $expires;
        $signature = hash_hmac('sha256', $payload, self::token_secret());

        return [
            'expires' => $expires,
            'token' => $signature,
        ];
    }

    public static function verify_external_continue_token(int $downloadId, string $slug, string $token, int $expires): bool
    {
        if ($token === '' || $expires <= 0 || $expires < time()) {
            return false;
        }

        $payload = $downloadId . '|' . $slug . '|' . $expires;
        $expected = hash_hmac('sha256', $payload, self::token_secret());

        return hash_equals($expected, $token);
    }

    public static function build_external_continue_url(array $download): string
    {
        $downloadId = (int) ($download['id'] ?? 0);
        $slug = (string) ($download['slug'] ?? '');
        $tokenData = self::external_continue_token($downloadId, $slug);

        return SITE_URL . '/downloads/file/' . rawurlencode($slug)
            . '?external=continue'
            . '&expires=' . (int) $tokenData['expires']
            . '&token=' . rawurlencode((string) $tokenData['token']);
    }

    public static function is_rate_limited(array $settings): bool
    {
        if (($settings['rate_limit_enabled'] ?? '1') !== '1') {
            return false;
        }

        $maxRequests = max(5, min(500, (int) ($settings['rate_limit_max'] ?? 30)));
        $windowSeconds = max(30, min(3600, (int) ($settings['rate_limit_window'] ?? 60)));
        $ipHash = self::client_ip_hash();

        try {
            $db = \CMS\Database::instance();
            $prefix = $db->getPrefix();
            $now = time();
            $stmt = $db->prepare("SELECT request_count, window_started_at FROM {$prefix}download_rate_limits WHERE ip_hash = ? LIMIT 1");
            $stmt->execute([$ipHash]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!is_array($row)) {
                $insert = $db->prepare("INSERT INTO {$prefix}download_rate_limits (ip_hash, request_count, window_started_at) VALUES (?, 1, ?)");
                $insert->execute([$ipHash, $now]);
                return false;
            }

            $windowStarted = (int) ($row['window_started_at'] ?? 0);
            $count = (int) ($row['request_count'] ?? 0);

            if ($windowStarted <= 0 || ($now - $windowStarted) >= $windowSeconds) {
                $reset = $db->prepare("UPDATE {$prefix}download_rate_limits SET request_count = 1, window_started_at = ? WHERE ip_hash = ?");
                $reset->execute([$now, $ipHash]);
                return false;
            }

            if ($count >= $maxRequests) {
                return true;
            }

            $increment = $db->prepare("UPDATE {$prefix}download_rate_limits SET request_count = request_count + 1 WHERE ip_hash = ?");
            $increment->execute([$ipHash]);

            return false;
        } catch (\Throwable $e) {
            error_log('CMS Downloads: rate limit check failed - ' . $e->getMessage());
            return false;
        }
    }

    private static function token_secret(): string
    {
        $repository = CMS_Downloads_Repository::instance();
        $settings = $repository->get_settings();
        $secret = trim((string) ($settings['external_token_secret'] ?? ''));

        if ($secret !== '') {
            return $secret;
        }

        try {
            $secret = bin2hex(random_bytes(32));
            $repository->save_setting_value('external_token_secret', $secret);
        } catch (\Throwable) {
            $secret = hash('sha256', (string) (defined('SITE_URL') ? SITE_URL : 'cms-downloads') . '|downloads-token');
        }

        return $secret;
    }

    private static function client_ip_hash(): string
    {
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        return hash('sha256', $ip . '|' . (string) (defined('SITE_URL') ? SITE_URL : 'cms-downloads'));
    }
}
