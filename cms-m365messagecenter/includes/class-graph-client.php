<?php
/**
 * CMS M365 Message Center – Microsoft Graph client.
 *
 * @package CMS_M365MessageCenter
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365MessageCenter_Graph_Client
{
    private const MAX_RESPONSE_BYTES = 1048576;

    /** @var array<string,string> */
    private const GRAPH_BASES = [
        'global' => 'https://graph.microsoft.com/v1.0',
        'gcc_high' => 'https://graph.microsoft.us/v1.0',
        'dod' => 'https://dod-graph.microsoft.us/v1.0',
        'china' => 'https://microsoftgraph.chinacloudapi.cn/v1.0',
    ];

    /** @var array<string,string> */
    private const LOGIN_HOSTS = [
        'global' => 'login.microsoftonline.com',
        'gcc_high' => 'login.microsoftonline.us',
        'dod' => 'login.microsoftonline.us',
        'china' => 'login.chinacloudapi.cn',
    ];

    /** @param array<string,string> $settings @return array{ok:bool,messages:array<int,array<string,mixed>>,error:string} */
    public function fetch_messages(array $settings): array
    {
        $tenantId = trim((string) ($settings['graph_tenant_id'] ?? ''));
        $clientId = trim((string) ($settings['graph_client_id'] ?? ''));
        $clientSecret = trim((string) ($settings['graph_client_secret'] ?? ''));
        $cloud = $this->cloud((string) ($settings['graph_cloud'] ?? 'global'));
        $limit = max(1, min(200, (int) ($settings['fetch_limit'] ?? 100)));
        $language = $this->language((string) ($settings['graph_language'] ?? 'de-DE'));

        if (!$this->valid_tenant($tenantId) || !$this->valid_guid($clientId) || $clientSecret === '') {
            return ['ok' => false, 'messages' => [], 'error' => 'missing-or-invalid-credentials'];
        }

        $token = $this->access_token($tenantId, $clientId, $clientSecret, $cloud);
        if ($token === '') {
            return ['ok' => false, 'messages' => [], 'error' => 'token-unavailable'];
        }

        $url = self::GRAPH_BASES[$cloud] . '/admin/serviceAnnouncement/messages?$top=' . $limit;
        $payload = $this->request_json($url, 'GET', [
            'Accept: application/json',
            'Accept-Language: ' . $language . ',de;q=0.9,en;q=0.7',
            'Authorization: Bearer ' . $token,
            'Prefer: odata.maxpagesize=' . $limit,
        ]);

        if (!is_array($payload) || !is_array($payload['value'] ?? null)) {
            return ['ok' => false, 'messages' => [], 'error' => $this->last_error() ?: 'graph-load-failed'];
        }

        $messages = [];
        foreach ($payload['value'] as $row) {
            if (is_array($row)) {
                $messages[] = $row;
            }
        }

        return ['ok' => true, 'messages' => $messages, 'error' => ''];
    }

    private string $lastError = '';

    private function access_token(string $tenantId, string $clientId, string $clientSecret, string $cloud): string
    {
        $host = self::LOGIN_HOSTS[$cloud] ?? self::LOGIN_HOSTS['global'];
        $endpoint = 'https://' . $host . '/' . rawurlencode($tenantId) . '/oauth2/v2.0/token';
        $body = http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => $this->graph_scope($cloud),
            'grant_type' => 'client_credentials',
        ], '', '&', PHP_QUERY_RFC3986);

        $payload = $this->request_json($endpoint, 'POST', [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
        ], $body);

        return is_array($payload) ? trim((string) ($payload['access_token'] ?? '')) : '';
    }

    private function graph_scope(string $cloud): string
    {
        $base = self::GRAPH_BASES[$cloud] ?? self::GRAPH_BASES['global'];
        $host = (string) (parse_url($base, PHP_URL_SCHEME) . '://' . parse_url($base, PHP_URL_HOST));

        return rtrim($host, '/') . '/.default';
    }

    /** @param array<int,string> $headers @return array<string,mixed>|null */
    private function request_json(string $url, string $method = 'GET', array $headers = [], ?string $body = null): ?array
    {
        $this->lastError = '';
        $method = strtoupper($method);
        if (!$this->is_allowed_url($url)) {
            $this->lastError = 'blocked-url';
            return null;
        }

        try {
            $response = $this->http_body($url, $method, $headers, $body);
            if ($response === '') {
                return null;
            }
            $decoded = json_decode($response, true, 512, JSON_BIGINT_AS_STRING);
            if (!is_array($decoded)) {
                $this->lastError = 'invalid-json';
                return null;
            }

            return $decoded;
        } catch (\Throwable $e) {
            $this->lastError = 'request-failed';
            self::log_exception('request_json_failed', $e);
            return null;
        }
    }

    /** @param array<int,string> $headers */
    private function http_body(string $url, string $method, array $headers, ?string $body): string
    {
        if (function_exists('curl_init')) {
            return $this->curl_body($url, $method, $headers, $body);
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $body ?? '',
                'timeout' => 10,
                'ignore_errors' => true,
                'follow_location' => 0,
                'max_redirects' => 0,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $response = @file_get_contents($url, false, $context, 0, self::MAX_RESPONSE_BYTES + 1);
        if (!is_string($response) || $response === '' || strlen($response) > self::MAX_RESPONSE_BYTES) {
            $this->lastError = 'empty-or-large-response';
            return '';
        }

        $statusLine = '';
        $responseHeaders = $http_response_header ?? [];
        if (is_array($responseHeaders) && isset($responseHeaders[0]) && is_string($responseHeaders[0])) {
            $statusLine = $responseHeaders[0];
        }
        if ($statusLine === '' || preg_match('#\s2\d\d\s#', $statusLine) !== 1) {
            $this->lastError = str_contains($statusLine, ' 429 ') ? 'graph-throttled' : 'http-error';
            return '';
        }

        return $response;
    }

    /** @param array<int,string> $headers */
    private function curl_body(string $url, string $method, array $headers, ?string $body): string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            $this->lastError = 'curl-unavailable';
            return '';
        }

        $buffer = '';
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, static function ($curl, string $chunk) use (&$buffer): int {
            $buffer .= $chunk;
            if (strlen($buffer) > self::MAX_RESPONSE_BYTES) {
                return 0;
            }

            return strlen($chunk);
        });
        if ($body !== null && $body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $ok = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($ok !== true || $statusCode < 200 || $statusCode >= 300 || strlen($buffer) > self::MAX_RESPONSE_BYTES) {
            $this->lastError = $statusCode === 429 ? 'graph-throttled' : 'http-error';
            if ($curlError !== '') {
                self::log_message('curl_error: ' . $this->redact($curlError));
            }
            return '';
        }

        return $buffer;
    }

    private function is_allowed_url(string $url): bool
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($scheme !== 'https' || $host === '') {
            return false;
        }

        $allowed = array_merge(array_values(self::LOGIN_HOSTS), array_map(
            static fn(string $base): string => strtolower((string) parse_url($base, PHP_URL_HOST)),
            array_values(self::GRAPH_BASES)
        ));

        return in_array($host, array_unique($allowed), true);
    }

    private function valid_tenant(string $tenantId): bool
    {
        return preg_match('/^[a-z0-9][a-z0-9.-]{1,120}$/i', $tenantId) === 1;
    }

    private function valid_guid(string $clientId): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $clientId) === 1;
    }

    private function cloud(string $cloud): string
    {
        return array_key_exists($cloud, self::GRAPH_BASES) ? $cloud : 'global';
    }

    private function language(string $language): string
    {
        $language = trim($language);
        return preg_match('/^[a-z]{2}(?:-[A-Z]{2})?$/', $language) === 1 ? $language : 'de-DE';
    }

    private function last_error(): string
    {
        return $this->lastError;
    }

    private function redact(string $message): string
    {
        return preg_replace('/(client_secret=)[^&\s]+/i', '$1***', $message) ?? 'redacted';
    }

    private static function log_exception(string $context, \Throwable $e): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CMS M365 Message Center graph [' . $context . ']: ' . $e->getMessage());
        }
    }

    private static function log_message(string $message): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CMS M365 Message Center graph: ' . $message);
        }
    }
}
