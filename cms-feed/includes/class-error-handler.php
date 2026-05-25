<?php
/**
 * Zentraler Fehler-Handler für CMS Feed.
 *
 * Rendert Fehler über die nativen 365CMS-Theme-Templates (`error.php`, `404.php`)
 * und protokolliert strukturiert im CMS-Logger mit Fallback auf `error_log()`.
 *
 * @package CMS_Feed
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Feed_Error_Handler
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    /**
     * @param array<string,mixed> $context
     */
    public function log_exception(string $message, \Throwable $exception, string $level = 'error', array $context = []): void
    {
        $this->log($level, $message, ['exception' => $exception] + $context);
    }

    /**
     * @param array<string,mixed> $context
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $context = $this->with_request_context($context);

        try {
            if (class_exists('\\CMS\\Logger')) {
                $logger = \CMS\Logger::instance()->withChannel('plugin-cms-feed');
                if (method_exists($logger, $level)) {
                    $logger->{$level}($message, $context);
                    return;
                }

                $logger->error($message, $context);
                return;
            }
        } catch (\Throwable $loggingError) {
            error_log('CMS Feed Logger fallback: ' . $this->sanitize_log_text($loggingError->getMessage()));
        }

        $exception = $context['exception'] ?? null;
        $suffix = $exception instanceof \Throwable
            ? ' – ' . get_class($exception) . ': ' . $this->sanitize_log_text($exception->getMessage())
            : '';

        error_log('CMS Feed [' . $level . ']: ' . $this->sanitize_log_text($message) . $suffix);
    }

    /**
     * @param array<string,mixed> $context
     */
    public function render_error_page(int $statusCode, string $title, string $message, ?\Throwable $exception = null, array $context = []): void
    {
        $statusCode = $this->normalize_status_code($statusCode);
        $level = $statusCode >= 500 ? 'error' : 'warning';

        if ($exception instanceof \Throwable) {
            $this->log_exception($title, $exception, $level, ['status_code' => $statusCode] + $context);
        } else {
            $this->log($level, $title, ['status_code' => $statusCode] + $context);
        }

        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: text/html; charset=utf-8');
        }

        try {
            if (class_exists('\\CMS\\CacheManager') && !headers_sent()) {
                \CMS\CacheManager::instance()->sendResponseHeaders('private');
            }
        } catch (\Throwable $cacheError) {
            $this->log_exception('CMS Feed: Fehler-Cache-Header konnten nicht gesetzt werden.', $cacheError, 'warning', $context);
        }

        $data = [
            'error_code' => $statusCode,
            'error_title' => $title,
            'error_message' => $message,
        ];

        $bufferLevel = ob_get_level();
        try {
            if (class_exists('\\CMS\\ThemeManager')) {
                ob_start();
                \CMS\ThemeManager::instance()->render('error', $data);
                $rendered = (string) ob_get_clean();

                if (trim($rendered) !== '') {
                    echo $rendered;
                    return;
                }

                while (ob_get_level() > $bufferLevel) {
                    ob_end_clean();
                }
            }
        } catch (\Throwable $renderError) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            $this->log_exception('CMS Feed: Native Fehlerseite konnte nicht gerendert werden.', $renderError, 'critical', $context);
        }

        echo $this->build_fallback_error_page($statusCode, $title, $message);
    }

    /**
     * @param array<string,mixed> $context
     */
    public function render_not_found(string $message = 'Die angeforderte Feed-Seite wurde nicht gefunden.', ?\Throwable $exception = null, array $context = []): void
    {
        if ($exception instanceof \Throwable) {
            $this->log_exception('CMS Feed: 404-Fehlerseite angefordert.', $exception, 'warning', ['status_code' => 404] + $context);
        } else {
            $this->log('notice', 'CMS Feed: 404-Fehlerseite angefordert.', ['status_code' => 404] + $context);
        }

        if (!headers_sent()) {
            http_response_code(404);
        }

        $bufferLevel = ob_get_level();
        try {
            if (class_exists('\\CMS\\ThemeManager')) {
                ob_start();
                \CMS\ThemeManager::instance()->render('404');
                $rendered = (string) ob_get_clean();

                if (trim($rendered) !== '') {
                    echo $rendered;
                    return;
                }

                while (ob_get_level() > $bufferLevel) {
                    ob_end_clean();
                }
            }
        } catch (\Throwable $renderError) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            $this->log_exception('CMS Feed: Native 404-Fehlerseite konnte nicht gerendert werden.', $renderError, 'error', $context);
        }

        echo $this->build_fallback_error_page(404, 'Feed-Seite nicht gefunden', $message);
    }

    /**
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    private function with_request_context(array $context): array
    {
        return $context + [
            'plugin' => 'cms-feed',
            'request_uri' => is_scalar($_SERVER['REQUEST_URI'] ?? null) ? (string) $_SERVER['REQUEST_URI'] : '',
            'request_method' => is_scalar($_SERVER['REQUEST_METHOD'] ?? null) ? (string) $_SERVER['REQUEST_METHOD'] : '',
        ];
    }

    private function normalize_status_code(int $statusCode): int
    {
        if ($statusCode < 400 || $statusCode > 599) {
            return 500;
        }

        return $statusCode;
    }

    private function sanitize_log_text(string $value): string
    {
        $value = preg_replace('/[\r\n\t]+/', ' ', $value) ?? '';
        return cms_feed_substr($value, 0, 800);
    }

    private function build_fallback_error_page(int $statusCode, string $title, string $message): string
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $homeUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') . '/' : '/';

        return '<!DOCTYPE html>'
            . '<html lang="de"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<meta name="robots" content="noindex,nofollow">'
            . '<title>' . (int) $statusCode . ' – ' . $safeTitle . '</title>'
            . '<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;background:#f8fafc;color:#0f172a}main{max-width:720px;margin:8vh auto;padding:2rem}a{color:#2563eb}p{line-height:1.6}</style>'
            . '</head><body><main>'
            . '<p>' . (int) $statusCode . '</p>'
            . '<h1>' . $safeTitle . '</h1>'
            . '<p>' . $safeMessage . '</p>'
            . '<p><a href="' . htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') . '">Zur Startseite</a></p>'
            . '</main></body></html>';
    }
}