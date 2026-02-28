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

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    /**
     * Alle fälligen Channels fetchen.
     */
    public function fetch_all_due(): array
    {
        $db       = CMS_Feed_Database::instance();
        $channels = $db->get_channels();
        $results  = [];

        foreach ($channels as $channel) {
            if (!(int) $channel['is_active']) {
                continue;
            }

            // Prüfen ob Fetch fällig ist
            if ($channel['last_fetched_at'] !== null) {
                $lastFetch  = strtotime($channel['last_fetched_at']);
                $intervalSec = (int) $channel['fetch_interval'] * 60;
                if (time() - $lastFetch < $intervalSec) {
                    continue;
                }
            }

            $results[$channel['id']] = $this->fetch_channel((int) $channel['id']);
        }

        return $results;
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

        try {
            $xml = $this->fetch_xml($channel['feed_url']);

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
        $context = stream_context_create([
            'http' => [
                'method'          => 'GET',
                'timeout'         => 15,
                'user_agent'      => '365CMS Feed Aggregator/' . CMS_FEED_VERSION,
                'follow_location' => 1,
                'max_redirects'   => 3,
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $content = @file_get_contents($url, false, $context);

        if ($content === false) {
            return null;
        }

        // BOM entfernen
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
        libxml_clear_errors();

        return $xml ?: null;
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
            $guid = md5((string) ($item->title ?? '') . $pubDate);
        }

        return [
            'guid'        => $this->sanitize_guid($guid),
            'title'       => $this->clean_text((string) ($item->title ?? 'Kein Titel')),
            'link'        => filter_var((string) ($item->link ?? ''), FILTER_VALIDATE_URL) ?: '',
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
            $guid = md5((string) ($entry->title ?? '') . $pubDate);
        }

        // Autor
        $author = (string) ($entry->author->name ?? '');

        // Bild
        $image = $this->extract_image_from_content($content);

        return [
            'guid'        => $this->sanitize_guid($guid),
            'title'       => $this->clean_text((string) ($entry->title ?? 'Kein Titel')),
            'link'        => filter_var($link, FILTER_VALIDATE_URL) ?: '',
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
                    return $url;
                }
            }
            if (isset($media->thumbnail)) {
                $attrs = $media->thumbnail->attributes();
                $url   = (string) ($attrs['url'] ?? '');
                if ($url) {
                    return $url;
                }
            }
        }

        // enclosure
        if (isset($item->enclosure)) {
            $attrs = $item->enclosure->attributes();
            $type  = (string) ($attrs['type'] ?? '');
            $url   = (string) ($attrs['url'] ?? '');
            if (str_starts_with($type, 'image/') && $url) {
                return $url;
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
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
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
            return (string) $xml->channel->image->url;
        }

        // Atom: icon / logo
        if (isset($xml->icon)) {
            return (string) $xml->icon;
        }
        if (isset($xml->logo)) {
            return (string) $xml->logo;
        }

        // Fallback: Favicon der Domain
        $parsed = parse_url($feedUrl);
        if (isset($parsed['scheme'], $parsed['host'])) {
            return $parsed['scheme'] . '://' . $parsed['host'] . '/favicon.ico';
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
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
        }

        // Atom
        foreach ($xml->link ?? [] as $link) {
            $attrs = $link->attributes();
            if ((string) ($attrs['rel'] ?? '') === 'alternate') {
                $url = (string) ($attrs['href'] ?? '');
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    return $url;
                }
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
}
