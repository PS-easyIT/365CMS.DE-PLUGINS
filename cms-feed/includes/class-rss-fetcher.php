<?php
/**
 * RSS-Feed-Fetcher für CMS Feed
 *
 * Holt RSS/Atom-Feeds, parst sie und speichert neue Items in der Datenbank.
 *
 * @package CMS_Feed
 * @since   1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Feed_RSS_Fetcher
{
    private static ?self $instance = null;
    private const FETCH_TIMEOUT = 15;
    private const MAX_REDIRECTS = 3;
    private const MAX_CHANNELS_PER_RUN = 10;
    private const ERROR_BACKOFF_SECONDS = 1800;
    private const MAX_RESPONSE_BYTES = 2097152; // 2 MB RSS/Atom reichen für kuratierte Feeds aus.

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    /**
     * Validiert Feed-URLs gegen SSRF-/interne Ziele.
     *
     * @return array{success: bool, url?: string, error?: string}
     */
    public function validate_feed_url(string $url): array
    {
        $normalizedUrl = trim($url);
        if ($normalizedUrl === '') {
            return ['success' => false, 'error' => 'Feed-URL fehlt.'];
        }

        if (!filter_var($normalizedUrl, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'error' => 'Feed-URL ist ungültig.'];
        }

        $parts = parse_url($normalizedUrl);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return ['success' => false, 'error' => 'Feed-URL muss Schema und Host enthalten.'];
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            return ['success' => false, 'error' => 'Es sind nur http- und https-Feeds erlaubt.'];
        }

        if (!empty($parts['user']) || !empty($parts['pass'])) {
            return ['success' => false, 'error' => 'Feed-URLs mit Zugangsdaten sind nicht erlaubt.'];
        }

        $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);
        if (!in_array($port, [80, 443], true)) {
            return ['success' => false, 'error' => 'Feed-URLs dürfen nur Standard-Webports 80/443 verwenden.'];
        }

        $host = (string) $parts['host'];
        if ($this->is_private_host_name($host)) {
            return ['success' => false, 'error' => 'Interne oder lokale Hosts sind nicht erlaubt.'];
        }

        $ipAddresses = $this->resolve_host_addresses($host);
        if ($ipAddresses === []) {
            return ['success' => false, 'error' => 'Feed-Host konnte nicht sicher aufgelöst werden.'];
        }

        foreach ($ipAddresses as $ipAddress) {
            if (!$this->is_public_ip_address($ipAddress)) {
                return ['success' => false, 'error' => 'Feed-Ziel zeigt auf ein internes oder reserviertes Netzwerk.'];
            }
        }

        return ['success' => true, 'url' => $normalizedUrl];
    }

    /**
     * Alle fälligen Channels fetchen.
     */
    public function fetch_all_due(): array
    {
        $db = CMS_Feed_Database::instance();
        $dueChannelIds = $this->get_due_channel_ids();
        $immediateChannelIds = array_slice($dueChannelIds, 0, self::MAX_CHANNELS_PER_RUN);
        $queuedChannelIds = array_slice($dueChannelIds, self::MAX_CHANNELS_PER_RUN);
        $results = [];

        foreach ($immediateChannelIds as $channelId) {
            $results[$channelId] = $this->fetch_channel($channelId);
        }

        $queuedCount = 0;
        if ($queuedChannelIds !== []) {
            $queuedCount = $db->add_to_fetch_queue($queuedChannelIds);
        }

        return [
            'due' => count($dueChannelIds),
            'processed' => count($immediateChannelIds),
            'queued' => $queuedCount,
            'new_items' => array_sum(array_map(
                static fn (array $result): int => (int) ($result['new_items'] ?? 0),
                $results
            )),
            'results' => $results,
        ];
    }

    public function enqueue_due_channels(int $limit = 0): int
    {
        $dueChannelIds = $this->get_due_channel_ids($limit);
        if ($dueChannelIds === []) {
            return 0;
        }

        return CMS_Feed_Database::instance()->add_to_fetch_queue($dueChannelIds);
    }

    /**
     * Einen einzelnen Channel fetchen.
     */
    public function fetch_channel(int $channelId): array
    {
        $db      = CMS_Feed_Database::instance();
        $channel = $db->get_channel($channelId);

        if (!$channel) {
            return ['success' => false, 'error' => 'Channel nicht gefunden', 'new_items' => 0];
        }

        $feedValidation = $this->validate_feed_url((string) ($channel['feed_url'] ?? ''));
        if (!$feedValidation['success']) {
            $error = $feedValidation['error'] ?? 'Feed-URL ist nicht erlaubt';
            $db->update_channel_fetch($channelId, $error, (int) ($channel['item_count'] ?? 0));
            return ['success' => false, 'error' => $error, 'new_items' => 0];
        }

        try {
            $xml = $this->fetch_xml($feedValidation['url'] ?? (string) $channel['feed_url']);

            if ($xml === null) {
                $error = 'Feed konnte nicht geladen werden';
                $db->update_channel_fetch($channelId, $error, (int) $channel['item_count']);
                return ['success' => false, 'error' => $error, 'new_items' => 0];
            }

            $items = $this->parse_feed($xml);
            $newCount = 0;
            $maxItems = (int) ($channel['max_items'] ?: 50);

            foreach (array_slice($items, 0, $maxItems) as $item) {
                $item['channel_id']  = $channelId;
                $item['category_id'] = (int) $channel['category_id'];
                if ($db->insert_item($item)) {
                    $newCount++;
                }
            }

            // Channel-Icon aus Feed übernehmen falls noch nicht gesetzt
            if (empty($channel['icon_url'])) {
                $feedIcon = $this->extract_feed_icon($xml, $channel['feed_url']);
                if ($feedIcon) {
                    $dbCore = \CMS\Database::instance();
                    $prefix = $dbCore->prefix();
                    $dbCore->prepare("UPDATE {$prefix}feed_channels SET icon_url = ? WHERE id = ?")
                        ->execute([$feedIcon, $channelId]);
                }
            }

            // Site-URL übernehmen falls noch nicht gesetzt
            if (empty($channel['site_url'])) {
                $siteUrl = $this->extract_site_url($xml);
                if ($siteUrl) {
                    $dbCore = \CMS\Database::instance();
                    $prefix = $dbCore->prefix();
                    $dbCore->prepare("UPDATE {$prefix}feed_channels SET site_url = ? WHERE id = ?")
                        ->execute([$siteUrl, $channelId]);
                }
            }

            // Item-Count aktualisieren
            $totalItems = $db->count_items(['channel_id' => $channelId]);
            $db->update_channel_fetch($channelId, null, $totalItems);

            return ['success' => true, 'error' => null, 'new_items' => $newCount];

        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $db->update_channel_fetch($channelId, $error, (int) $channel['item_count']);
            error_log("CMS Feed: Fetch error for channel {$channelId}: {$error}");
            return ['success' => false, 'error' => $error, 'new_items' => 0];
        }
    }

    /**
     * XML von URL laden.
     */
    private function fetch_xml(string $url): ?\SimpleXMLElement
    {
        $currentUrl = $url;

        for ($redirectCount = 0; $redirectCount <= self::MAX_REDIRECTS; $redirectCount++) {
            $validation = $this->validate_feed_url($currentUrl);
            if (!$validation['success']) {
                error_log('CMS Feed: Blocked feed URL "' . $currentUrl . '": ' . ($validation['error'] ?? 'Unbekannter Validierungsfehler'));
                return null;
            }

            $responseHeaders = [];
            $content = $this->fetch_raw_content($validation['url'] ?? $currentUrl, $responseHeaders);
            if ($content === null) {
                return null;
            }

            $statusCode = $this->extract_status_code($responseHeaders);
            if ($statusCode >= 300 && $statusCode < 400) {
                $redirectTarget = $this->extract_redirect_location($responseHeaders);
                if ($redirectTarget === null) {
                    error_log('CMS Feed: Redirect without Location header for ' . $currentUrl);
                    return null;
                }

                $resolvedRedirect = $this->resolve_redirect_url($validation['url'] ?? $currentUrl, $redirectTarget);
                if ($resolvedRedirect === null) {
                    error_log('CMS Feed: Invalid redirect target "' . $redirectTarget . '" for ' . $currentUrl);
                    return null;
                }

                $currentUrl = $resolvedRedirect;
                continue;
            }

            if ($statusCode >= 400) {
                error_log('CMS Feed: HTTP ' . $statusCode . ' while fetching ' . $currentUrl);
                return null;
            }

            // BOM entfernen
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
            libxml_clear_errors();

            return $xml ?: null;
        }

        error_log('CMS Feed: Too many redirects while fetching ' . $url);
        return null;
    }

    /**
     * RSS 2.0 oder Atom Feed parsen.
     */
    private function parse_feed(\SimpleXMLElement $xml): array
    {
        $items = [];

        // RSS 2.0
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $items[] = $this->parse_rss_item($item);
            }
            return $items;
        }

        // RSS 1.0 (RDF)
        if (isset($xml->item)) {
            foreach ($xml->item as $item) {
                $items[] = $this->parse_rss_item($item);
            }
            return $items;
        }

        // Atom
        $namespaces = $xml->getNamespaces(true);
        if (isset($xml->entry) || isset($namespaces[''])) {
            foreach ($xml->entry as $entry) {
                $items[] = $this->parse_atom_entry($entry, $namespaces);
            }

            // Falls Atom-Default-Namespace
            if (empty($items) && !empty($namespaces[''])) {
                $xml->registerXPathNamespace('atom', $namespaces['']);
                $entries = $xml->xpath('//atom:entry');
                if ($entries) {
                    foreach ($entries as $entry) {
                        $items[] = $this->parse_atom_entry($entry, $namespaces);
                    }
                }
            }
        }

        return $items;
    }

    /**
     * RSS-Item parsen.
     */
    private function parse_rss_item(\SimpleXMLElement $item): array
    {
        $ns = $item->getNamespaces(true);

        // Inhalt: content:encoded > description
        $content = '';
        if (isset($ns['content'])) {
            $contentNs = $item->children($ns['content']);
            if (isset($contentNs->encoded)) {
                $content = (string) $contentNs->encoded;
            }
        }

        $description = (string) ($item->description ?? '');
        if (empty($content)) {
            $content = $description;
        }

        // Bild extrahieren
        $image = $this->extract_image_from_item($item, $ns, $content);

        // Autor
        $author = '';
        if (isset($ns['dc'])) {
            $dc = $item->children($ns['dc']);
            if (isset($dc->creator)) {
                $author = (string) $dc->creator;
            }
        }
        if (empty($author)) {
            $author = (string) ($item->author ?? '');
        }

        // Datum
        $pubDate = (string) ($item->pubDate ?? '');
        if (empty($pubDate) && isset($ns['dc'])) {
            $dc = $item->children($ns['dc']);
            $pubDate = (string) ($dc->date ?? '');
        }

        // GUID
        $guid = (string) ($item->guid ?? $item->link ?? '');
        if (empty($guid)) {
            $guid = hash('sha256', (string) ($item->title ?? '') . '|' . $pubDate);
        }

        return [
            'guid'        => $this->sanitize_guid($guid),
            'title'       => $this->clean_text((string) ($item->title ?? 'Kein Titel')),
            'link'        => $this->sanitize_external_url((string) ($item->link ?? '')),
            'description' => $this->truncate_text(strip_tags($description), 500),
            'content'     => $content,
            'author'      => $this->clean_text($author),
            'image_url'   => $image,
            'pub_date'    => $this->parse_date($pubDate),
        ];
    }

    /**
     * Atom-Entry parsen.
     */
    private function parse_atom_entry(\SimpleXMLElement $entry, array $namespaces): array
    {
        // Link
        $link = '';
        foreach ($entry->link as $entryLink) {
            $attrs = $entryLink->attributes();
            $rel   = (string) ($attrs['rel'] ?? 'alternate');
            if ($rel === 'alternate' || $rel === '') {
                $link = (string) ($attrs['href'] ?? '');
                break;
            }
        }
        if (empty($link) && isset($entry->link)) {
            $attrs = $entry->link->attributes();
            $link  = (string) ($attrs['href'] ?? '');
        }

        // Inhalt
        $content     = (string) ($entry->content ?? '');
        $description = (string) ($entry->summary ?? '');
        if (empty($content)) {
            $content = $description;
        }

        // Datum
        $pubDate = (string) ($entry->published ?? $entry->updated ?? '');

        // GUID
        $guid = (string) ($entry->id ?? $link);
        if (empty($guid)) {
            $guid = hash('sha256', (string) ($entry->title ?? '') . '|' . $pubDate);
        }

        // Autor
        $author = (string) ($entry->author->name ?? '');

        // Bild
        $image = $this->extract_image_from_content($content);

        return [
            'guid'        => $this->sanitize_guid($guid),
            'title'       => $this->clean_text((string) ($entry->title ?? 'Kein Titel')),
            'link'        => $this->sanitize_external_url($link),
            'description' => $this->truncate_text(strip_tags($description ?: $content), 500),
            'content'     => $content,
            'author'      => $this->clean_text($author),
            'image_url'   => $image,
            'pub_date'    => $this->parse_date($pubDate),
        ];
    }

    /**
     * Bild aus RSS-Item extrahieren (media:content, enclosure, oder HTML).
     */
    private function extract_image_from_item(\SimpleXMLElement $item, array $ns, string $content): string
    {
        // media:content / media:thumbnail
        if (isset($ns['media'])) {
            $media = $item->children($ns['media']);
            if (isset($media->content)) {
                $attrs = $media->content->attributes();
                $url   = (string) ($attrs['url'] ?? '');
                if ($url && $this->is_image_url($url)) {
                    return $this->sanitize_external_url($url);
                }
            }
            if (isset($media->thumbnail)) {
                $attrs = $media->thumbnail->attributes();
                $url   = (string) ($attrs['url'] ?? '');
                if ($url) {
                    return $this->sanitize_external_url($url);
                }
            }
        }

        // enclosure
        if (isset($item->enclosure)) {
            $attrs = $item->enclosure->attributes();
            $type  = (string) ($attrs['type'] ?? '');
            $url   = (string) ($attrs['url'] ?? '');
            if (str_starts_with($type, 'image/') && $url) {
                return $this->sanitize_external_url($url);
            }
        }

        // Aus HTML-Inhalt
        return $this->extract_image_from_content($content);
    }

    /**
     * Erstes Bild aus HTML-Content extrahieren.
     */
    private function extract_image_from_content(string $html): string
    {
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $html, $matches)) {
            $url = $matches[1];
            return $this->sanitize_external_url($url);
        }
        return '';
    }

    /**
     * Feed-Icon/Favicon extrahieren.
     */
    private function extract_feed_icon(\SimpleXMLElement $xml, string $feedUrl): string
    {
        // RSS: image > url
        if (isset($xml->channel->image->url)) {
            return $this->sanitize_external_url((string) $xml->channel->image->url);
        }

        // Atom: icon / logo
        if (isset($xml->icon)) {
            return $this->sanitize_external_url((string) $xml->icon);
        }
        if (isset($xml->logo)) {
            return $this->sanitize_external_url((string) $xml->logo);
        }

        // Fallback: Favicon der Domain
        $parsed = parse_url($feedUrl);
        if (isset($parsed['scheme'], $parsed['host'])) {
            return $this->sanitize_external_url($parsed['scheme'] . '://' . $parsed['host'] . '/favicon.ico');
        }

        return '';
    }

    /**
     * Site-URL aus Feed extrahieren.
     */
    private function extract_site_url(\SimpleXMLElement $xml): string
    {
        // RSS
        if (isset($xml->channel->link)) {
            $url = (string) $xml->channel->link;
            return $this->sanitize_external_url($url);
        }

        // Atom
        foreach ($xml->link ?? [] as $link) {
            $attrs = $link->attributes();
            if ((string) ($attrs['rel'] ?? '') === 'alternate') {
                $url = (string) ($attrs['href'] ?? '');
                return $this->sanitize_external_url($url);
            }
        }

        return '';
    }

    // ──────────────────────────────────────────────────────────────────────
    // Hilfsfunktionen
    // ──────────────────────────────────────────────────────────────────────

    private function sanitize_guid(string $guid): string
    {
        return mb_substr(trim($guid), 0, 500);
    }

    private function clean_text(string $text): string
    {
        return trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function truncate_text(string $text, int $length): string
    {
        $text = $this->clean_text($text);
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length) . '…';
    }

    private function parse_date(string $dateStr): string
    {
        if (empty($dateStr)) {
            return date('Y-m-d H:i:s');
        }

        $ts = strtotime($dateStr);
        if ($ts === false) {
            return date('Y-m-d H:i:s');
        }

        // Zukünftige Daten auf jetzt setzen
        if ($ts > time()) {
            $ts = time();
        }

        return date('Y-m-d H:i:s', $ts);
    }

    private function is_image_url(string $url): bool
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true);
    }

    private function sanitize_external_url(string $url): string
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        if (!in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            return '';
        }

        if (!empty($parts['user']) || !empty($parts['pass'])) {
            return '';
        }

        $host = (string) $parts['host'];
        if ($this->is_private_host_name($host)) {
            return '';
        }

        $literalHost = trim($host, '[]');
        if (filter_var($literalHost, FILTER_VALIDATE_IP) && !$this->is_public_ip_address($literalHost)) {
            return '';
        }

        return $url;
    }

    /**
     * @param array<int,string> $responseHeaders
     */
    private function fetch_raw_content(string $url, array &$responseHeaders): ?string
    {
        $responseHeaders = [];

        if ($this->can_use_stream_fetch()) {
            $context = stream_context_create([
                'http' => [
                    'method'          => 'GET',
                    'timeout'         => self::FETCH_TIMEOUT,
                    'user_agent'      => '365CMS.DE Feed Aggregator/' . CMS_FEED_VERSION,
                    'follow_location' => 0,
                    'max_redirects'   => 0,
                    'ignore_errors'   => true,
                ],
                'ssl' => [
                    'verify_peer'      => true,
                    'verify_peer_name' => true,
                ],
            ]);

            $content = @file_get_contents($url, false, $context);
            $responseHeaders = $http_response_header ?? [];

            if ($content !== false || $responseHeaders !== []) {
                $body = $content === false ? '' : $content;
                if (strlen($body) > self::MAX_RESPONSE_BYTES) {
                    error_log('CMS Feed: Response too large for ' . $url);
                    return null;
                }
                return $body;
            }
        }

        $curlResult = $this->fetch_raw_content_via_curl($url);
        if ($curlResult !== null) {
            $responseHeaders = $curlResult['headers'];
            return $curlResult['body'];
        }

        error_log('CMS Feed: Request failed for ' . $url);
        return null;
    }

    private function can_use_stream_fetch(): bool
    {
        if (!function_exists('file_get_contents')) {
            return false;
        }

        $allowUrlFopen = ini_get('allow_url_fopen');
        if ($allowUrlFopen === false) {
            return false;
        }

        return !in_array(strtolower(trim((string) $allowUrlFopen)), ['0', 'off', 'false', 'no', ''], true);
    }

    /**
     * @return array{headers: array<int,string>, body: string}|null
     */
    private function fetch_raw_content_via_curl(string $url): ?array
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        $handle = curl_init($url);
        if ($handle === false) {
            return null;
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_CONNECTTIMEOUT => self::FETCH_TIMEOUT,
            CURLOPT_TIMEOUT => self::FETCH_TIMEOUT,
            CURLOPT_USERAGENT => '365CMS.DE Feed Aggregator/' . CMS_FEED_VERSION,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPGET => true,
        ]);

        if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTP') && defined('CURLPROTO_HTTPS')) {
            curl_setopt($handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        }

        $rawResponse = curl_exec($handle);
        if ($rawResponse === false) {
            error_log('CMS Feed: cURL request failed for ' . $url . ' – ' . curl_error($handle));
            curl_close($handle);
            return null;
        }

        $headerSize = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        curl_close($handle);

        $rawHeaders = substr($rawResponse, 0, $headerSize);
        $body = substr($rawResponse, $headerSize);
        if (is_string($body) && strlen($body) > self::MAX_RESPONSE_BYTES) {
            error_log('CMS Feed: cURL response too large for ' . $url);
            return null;
        }

        return [
            'headers' => $this->extract_header_lines_from_curl_response($rawHeaders),
            'body' => is_string($body) ? $body : '',
        ];
    }

    /**
     * @return array<int,string>
     */
    private function extract_header_lines_from_curl_response(string $rawHeaders): array
    {
        $rawHeaders = str_replace("\r\n", "\n", trim($rawHeaders));
        if ($rawHeaders === '') {
            return [];
        }

        $headerBlocks = preg_split("/\n\n+/", $rawHeaders);
        if (!is_array($headerBlocks) || $headerBlocks === []) {
            return [];
        }

        $finalBlock = trim((string) end($headerBlocks));
        if ($finalBlock === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode("\n", $finalBlock)), static fn (string $line): bool => $line !== ''));
    }

    /**
     * @param array<int,string> $responseHeaders
     */
    private function extract_status_code(array $responseHeaders): int
    {
        $statusLine = $responseHeaders[0] ?? '';
        if (preg_match('/\s(\d{3})\s/', $statusLine, $matches) === 1) {
            return (int) $matches[1];
        }

        return 200;
    }

    /**
     * @param array<int,string> $responseHeaders
     */
    private function extract_redirect_location(array $responseHeaders): ?string
    {
        foreach ($responseHeaders as $header) {
            if (stripos($header, 'Location:') === 0) {
                $location = trim(substr($header, 9));
                return $location !== '' ? $location : null;
            }
        }

        return null;
    }

    private function resolve_redirect_url(string $baseUrl, string $location): ?string
    {
        if ($location === '') {
            return null;
        }

        if (filter_var($location, FILTER_VALIDATE_URL)) {
            return $location;
        }

        $baseParts = parse_url($baseUrl);
        if (!is_array($baseParts) || empty($baseParts['scheme']) || empty($baseParts['host'])) {
            return null;
        }

        $scheme = (string) $baseParts['scheme'];
        $host = (string) $baseParts['host'];
        $port = isset($baseParts['port']) ? ':' . (int) $baseParts['port'] : '';

        if (str_starts_with($location, '//')) {
            return $scheme . ':' . $location;
        }

        if (str_starts_with($location, '/')) {
            return $scheme . '://' . $host . $port . $location;
        }

        $basePath = (string) ($baseParts['path'] ?? '/');
        $directory = preg_replace('~/[^/]*$~', '/', $basePath) ?: '/';

        return $scheme . '://' . $host . $port . $directory . ltrim($location, '/');
    }

    /**
     * @return array<int,string>
     */
    private function resolve_host_addresses(string $host): array
    {
        $normalizedHost = trim($host, '[]');
        if ($normalizedHost === '') {
            return [];
        }

        if (filter_var($normalizedHost, FILTER_VALIDATE_IP)) {
            return [$normalizedHost];
        }

        $asciiHost = $normalizedHost;
        if (function_exists('idn_to_ascii')) {
            $convertedHost = idn_to_ascii($normalizedHost, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if (is_string($convertedHost) && $convertedHost !== '') {
                $asciiHost = $convertedHost;
            }
        }

        $addresses = [];
        if (function_exists('dns_get_record')) {
            $dnsRecords = @dns_get_record($asciiHost, DNS_A + DNS_AAAA);
            if (is_array($dnsRecords)) {
                foreach ($dnsRecords as $record) {
                    if (!empty($record['ip']) && filter_var($record['ip'], FILTER_VALIDATE_IP)) {
                        $addresses[] = $record['ip'];
                    }
                    if (!empty($record['ipv6']) && filter_var($record['ipv6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                        $addresses[] = $record['ipv6'];
                    }
                }
            }
        }

        if ($addresses === []) {
            $ipv4Hosts = @gethostbynamel($asciiHost);
            if (is_array($ipv4Hosts)) {
                foreach ($ipv4Hosts as $ipAddress) {
                    if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        $addresses[] = $ipAddress;
                    }
                }
            }
        }

        return array_values(array_unique($addresses));
    }

    private function is_private_host_name(string $host): bool
    {
        $normalizedHost = strtolower(trim($host, '[]'));
        if ($normalizedHost === '') {
            return true;
        }

        return $normalizedHost === 'localhost'
            || str_ends_with($normalizedHost, '.localhost')
            || str_ends_with($normalizedHost, '.local')
            || str_ends_with($normalizedHost, '.internal');
    }

    private function is_public_ip_address(string $ipAddress): bool
    {
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        $normalizedIp = strtolower($ipAddress);
        if ($normalizedIp === '::1') {
            return false;
        }

        return true;
    }

    /**
     * @return array<int,int>
     */
    private function get_due_channel_ids(int $limit = 0): array
    {
        $channels = CMS_Feed_Database::instance()->get_channels();
        $dueChannelIds = [];

        foreach ($channels as $channel) {
            if (!(int) ($channel['is_active'] ?? 0)) {
                continue;
            }

            if (!$this->is_channel_due($channel)) {
                continue;
            }

            $dueChannelIds[] = (int) $channel['id'];

            if ($limit > 0 && count($dueChannelIds) >= $limit) {
                break;
            }
        }

        return $dueChannelIds;
    }

    private function is_channel_due(array $channel): bool
    {
        if (($channel['last_fetched_at'] ?? null) === null) {
            return true;
        }

        $lastFetch = strtotime((string) $channel['last_fetched_at']);
        if ($lastFetch === false) {
            return true;
        }

        $intervalSec = max(60, (int) ($channel['fetch_interval'] ?? 0) * 60);
        $requiredWait = !empty($channel['last_error'])
            ? max($intervalSec, self::ERROR_BACKOFF_SECONDS)
            : $intervalSec;

        return time() - $lastFetch >= $requiredWait;
    }
}
