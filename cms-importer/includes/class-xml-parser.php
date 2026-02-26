<?php
/**
 * WordPress WXR-XML-Parser
 *
 * Parst WordPress Extended RSS (WXR) Export-Dateien und gibt
 * strukturierte Daten für den Import zurück.
 *
 * @package CMS_Importer
 * @since   1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Parsed WordPress WXR-Exportdateien (XML).
 */
class CMS_Importer_XML_Parser
{
    /** WXR-Namespaces */
    private const NS_WP      = 'http://wordpress.org/export/1.2/';
    private const NS_CONTENT = 'http://purl.org/rss/1.0/modules/content/';
    private const NS_EXCERPT = 'http://wordpress.org/export/1.2/excerpt/';
    private const NS_DC      = 'http://purl.org/dc/elements/1.1/';

    /** Maximale Dateigröße (50 MB) */
    private const MAX_FILE_SIZE = 52_428_800;

    /**
     * Parst eine WXR-XML-Datei und gibt die Daten als Array zurück.
     *
     * @param  string $file_path Absoluter Pfad zur XML-Datei
     * @return array{
     *   site:    array,
     *   authors: array,
     *   posts:   array,
     *   pages:   array,
     *   others:  array,
     *   errors:  string[]
     * }
     */
    public function parse(string $file_path): array
    {
        $result = [
            'site'    => [],
            'authors' => [],
            'posts'   => [],
            'pages'   => [],
            'others'  => [],
            'errors'  => [],
        ];

        // ── Datei-Sicherheitschecks ────────────────────────────────────────
        if (!file_exists($file_path)) {
            $result['errors'][] = 'Datei nicht gefunden: ' . $file_path;
            return $result;
        }

        if (!is_readable($file_path)) {
            $result['errors'][] = 'Datei nicht lesbar: ' . $file_path;
            return $result;
        }

        $file_size = filesize($file_path);
        if ($file_size === false || $file_size > self::MAX_FILE_SIZE) {
            $result['errors'][] = 'Datei zu groß (max. 50 MB). Größe: ' . round(($file_size ?: 0) / 1048576, 2) . ' MB';
            return $result;
        }

        // ── XML-Laden ──────────────────────────────────────────────────────
        // C-09: XXE-Schutz – keine externen Entities, kein Netzwerkzugriff
        // PHP 8.0+ deaktiviert externe Entities standardmäßig; für PHP < 8 explizit:
        if (PHP_VERSION_ID < 80000) {
            /** @phpstan-ignore-next-line */
            libxml_disable_entity_loader(true); // @deprecated seit PHP 8.0
        }

        $prev_errors = libxml_use_internal_errors(true);
        // LIBXML_NONET   → blockiert Netzwerkzugriffe (XXE via HTTP/FTP)
        // LIBXML_NOCDATA → wandelt CDATA in Textknoten (kein Raw-XML-Injection)
        // LIBXML_DTDATTR → DTD-Attribute nicht laden
        // LIBXML_NOENT   wird NICHT gesetzt – verhindert rekursive Entity-Expansion (Billion Laughs)
        $xml = simplexml_load_file(
            $file_path,
            'SimpleXMLElement',
            LIBXML_NOCDATA | LIBXML_NONET | LIBXML_DTDATTR
        );
        $xml_errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($prev_errors);

        if ($xml === false) {
            foreach ($xml_errors as $err) {
                $result['errors'][] = 'XML-Fehler (Zeile ' . $err->line . '): ' . trim($err->message);
            }
            return $result;
        }

        // ── Namespace-Register ─────────────────────────────────────────────
        $xml->registerXPathNamespace('wp',      self::NS_WP);
        $xml->registerXPathNamespace('content', self::NS_CONTENT);
        $xml->registerXPathNamespace('excerpt', self::NS_EXCERPT);
        $xml->registerXPathNamespace('dc',      self::NS_DC);

        $channel = $xml->channel;

        // ── Site-Info ──────────────────────────────────────────────────────
        $result['site'] = [
            'title'        => (string) ($channel->title ?? ''),
            'link'         => (string) ($channel->link ?? ''),
            'description'  => (string) ($channel->description ?? ''),
            'language'     => (string) ($channel->language ?? ''),
            'wxr_version'  => (string) ($channel->children(self::NS_WP)->wxr_version ?? ''),
            'base_site_url'=> (string) ($channel->children(self::NS_WP)->base_site_url ?? ''),
        ];

        // ── Autoren ────────────────────────────────────────────────────────
        $result['authors'] = $this->parse_authors($channel, self::NS_WP);

        // ── Items (Posts, Pages, sonstige CPTs) ───────────────────────────
        foreach ($channel->item as $item) {
            $parsed = $this->parse_item($item);
            if (empty($parsed)) {
                continue;
            }

            switch ($parsed['post_type']) {
                case 'post':
                    $result['posts'][] = $parsed;
                    break;
                case 'page':
                    $result['pages'][] = $parsed;
                    break;
                default:
                    $result['others'][] = $parsed;
                    break;
            }
        }

        return $result;
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Private Helper
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Parst alle <wp:author>-Knoten aus dem Channel.
     */
    private function parse_authors(\SimpleXMLElement $channel, string $ns): array
    {
        $authors = [];
        foreach ($channel->children($ns)->author as $author) {
            $login = (string) $author->author_login;
            if ($login === '') {
                continue;
            }
            $authors[$login] = [
                'wp_id'        => (string) $author->author_id,
                'login'        => $login,
                'email'        => (string) $author->author_email,
                'display_name' => (string) $author->author_display_name,
                'first_name'   => (string) $author->author_first_name,
                'last_name'    => (string) $author->author_last_name,
            ];
        }
        return $authors;
    }

    /**
     * Parst ein einzelnes <item>-Element.
     *
     * @return array|null null = überspringen (Attachment, Nav-Menü etc.)
     */
    private function parse_item(\SimpleXMLElement $item): ?array
    {
        $wp = $item->children(self::NS_WP);

        $post_type   = (string) $wp->post_type;
        $post_status = (string) $wp->status;

        // Attachments und nav_menu_items grundsätzlich überspringen
        if (in_array($post_type, ['attachment', 'nav_menu_item', 'custom_css', 'user_request'], true)) {
            return null;
        }

        // Gelöschte/Auto-Draft-Einträge überspringen
        if (in_array($post_status, ['auto-draft', 'inherit'], true)) {
            return null;
        }

        $content_ns = $item->children(self::NS_CONTENT);
        $excerpt_ns = $item->children(self::NS_EXCERPT);
        $dc_ns      = $item->children(self::NS_DC);

        // ── Basis-Felder ───────────────────────────────────────────────────
        $raw_content = (string) ($content_ns->encoded ?? '');

        $parsed = [
            'wp_id'         => (int)    (string) $wp->post_id,
            'title'         => (string) $item->title,
            'slug'          => (string) $wp->post_name,
            'link'          => (string) $item->link,
            'content'       => $raw_content,
            'excerpt'       => (string) ($excerpt_ns->encoded ?? ''),
            'author_login'  => (string) ($dc_ns->creator ?? ''),
            'post_type'     => $post_type,
            'post_status'   => $post_status,
            'date'          => (string) $wp->post_date,
            'date_gmt'      => (string) $wp->post_date_gmt,
            'modified'      => (string) $wp->post_modified,
            'modified_gmt'  => (string) $wp->post_modified_gmt,
            'parent_id'     => (int)    (string) $wp->post_parent,
            'menu_order'    => (int)    (string) $wp->menu_order,
            'comment_status'=> (string) $wp->comment_status,
            'ping_status'   => (string) $wp->ping_status,
            'is_sticky'     => (bool)   ((string) $wp->is_sticky === '1'),
            // Kategorien & Tags
            'categories'    => [],
            'tags'          => [],
            // Alle Meta-Felder roh
            'meta'          => [],
            // Gemappte CMS-Felder (aus Meta extrahiert)
            'meta_title'       => '',
            'meta_description' => '',
            'featured_image'   => '',
            // Bild-URLs aus Content extrahiert
            'image_urls'       => $this->extract_image_urls($raw_content),
        ];

        // ── Kategorien & Tags ──────────────────────────────────────────────
        foreach ($item->category as $cat) {
            $domain = (string) $cat->attributes()->domain;
            $value  = (string) $cat;
            if ($value === '') {
                continue;
            }
            if ($domain === 'post_tag') {
                $parsed['tags'][] = $value;
            } else {
                $parsed['categories'][] = $value;
            }
        }

        // ── Post-Meta ──────────────────────────────────────────────────────
        foreach ($wp->postmeta as $meta) {
            $key   = (string) $meta->meta_key;
            $value = (string) $meta->meta_value;

            // Interne WP-Keys ohne Nutzen rausfiltern
            if ($this->is_internal_wp_meta($key)) {
                continue;
            }

            $parsed['meta'][$key] = $value;
        }

        // ── Bekannte Meta-Keys direkt mappen ──────────────────────────────
        $parsed = $this->map_known_meta($parsed);

        return $parsed;
    }

    /**
     * Mappt bekannte WordPress-Meta-Keys auf CMS-Felder.
     * Gemappte Keys werden aus dem rohen meta-Array entfernt.
     */
    private function map_known_meta(array $parsed): array
    {
        $mapping = [
            // SEO-Titel
            '_yoast_wpseo_title'        => 'meta_title',
            'rank_math_title'           => 'meta_title',
            '_seopress_titles_title'    => 'meta_title',
            // SEO-Beschreibung
            '_yoast_wpseo_metadesc'     => 'meta_description',
            'rank_math_description'     => 'meta_description',
            '_seopress_titles_desc'     => 'meta_description',
            // Featured Image (bleibt URL/Pfad aus anderem Meta)
            '_thumbnail_id'             => '_thumbnail_id', // Sonderbehandlung
        ];

        foreach ($mapping as $wp_key => $cms_field) {
            if (!isset($parsed['meta'][$wp_key])) {
                continue;
            }

            if ($wp_key === '_thumbnail_id') {
                // Wir merken uns die WP-Attachment-ID als Hinweis
                $parsed['featured_image_wp_id'] = (int) $parsed['meta'][$wp_key];
                unset($parsed['meta'][$wp_key]);
                continue;
            }

            // Nur übernehmen wenn Zielfeld noch leer ist
            if ($parsed[$cms_field] === '' && $parsed['meta'][$wp_key] !== '') {
                $parsed[$cms_field] = $parsed['meta'][$wp_key];
            }
            unset($parsed['meta'][$wp_key]);
        }

        return $parsed;
    }

    /**
     * Extrahiert alle absoluten Bild-URLs aus HTML-Content.
     *
     * Erkennt <img src="...">, background-image: url(...) und WP-Block-Syntax.
     *
     * @param  string   $content HTML-Inhalt (content:encoded)
     * @return string[]          Unique-Liste absoluter Bild-URLs
     */
    public function extract_image_urls(string $content): array
    {
        if ($content === '') {
            return [];
        }

        $urls = [];

        // <img src="..."> und <img src='...'>
        if (preg_match_all('/<img[^>]+src=["\']([^"\'>\s]+)["\'][^>]*>/i', $content, $m)) {
            foreach ($m[1] as $u) {
                $urls[] = trim($u);
            }
        }

        // WordPress-Block-Syntax: "url":"https://..."  (JSON inside HTML comments)
        if (preg_match_all('/"url"\s*:\s*"(https?:\/\/[^"]+\.(?:jpg|jpeg|png|gif|webp|svg)[^"]*)"/i', $content, $m)) {
            foreach ($m[1] as $u) {
                $urls[] = trim($u);
            }
        }

        // Nur absolute URLs behalten, Query-String entfernen für Dateiname-Zwecke (URL selbst bleibt voll)
        $urls = array_values(array_unique(array_filter($urls, static function (string $u): bool {
            return filter_var($u, FILTER_VALIDATE_URL) !== false
                && str_starts_with($u, 'http');
        })));

        return $urls;
    }

    /**
     * Prüft ob ein Meta-Key ein interner WordPress-Systemkey ist,
     * der für den Import irrelevant ist.
     */
    private function is_internal_wp_meta(string $key): bool
    {
        // Rein technische WP-Interna und Plugin-Cache-Keys ausblenden
        $internal_prefixes = [
            '_edit_lock',
            '_edit_last',
            '_oembed_',
            '_pingme',
            '_encloseme',
            '_wp_old_slug',
            '_wp_old_date',
        ];

        foreach ($internal_prefixes as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
