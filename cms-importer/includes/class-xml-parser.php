<?php
/**
 * WordPress WXR-XML-Parser
 *
 * Parst WordPress Extended RSS (WXR) Export-Dateien und gibt
 * strukturierte Daten für den Import zurück.
 *
 * @package CMS_Importer
 * @since   1.1.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (defined('CMS_IMPORTER_XML_PARSER_CLASS_LOADED') || class_exists('CMS_Importer_XML_Parser', false)) {
    return;
}

define('CMS_IMPORTER_XML_PARSER_CLASS_LOADED', true);

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

    public function parse(string $file_path): array
    {
        $result = [
            'site'        => [],
            'authors'     => [],
            'attachments' => [],
            'posts'       => [],
            'pages'       => [],
            'tables'      => [],
            'others'      => [],
            'errors'      => [],
        ];

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

        if (PHP_VERSION_ID < 80000) {
            /** @phpstan-ignore-next-line */
            libxml_disable_entity_loader(true);
        }

        $prev_errors = libxml_use_internal_errors(true);
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

        $xml->registerXPathNamespace('wp', self::NS_WP);
        $xml->registerXPathNamespace('content', self::NS_CONTENT);
        $xml->registerXPathNamespace('excerpt', self::NS_EXCERPT);
        $xml->registerXPathNamespace('dc', self::NS_DC);

        $channel = $xml->channel;

        $result['site'] = [
            'title'         => (string) ($channel->title ?? ''),
            'link'          => (string) ($channel->link ?? ''),
            'description'   => (string) ($channel->description ?? ''),
            'language'      => (string) ($channel->language ?? ''),
            'wxr_version'   => (string) ($channel->children(self::NS_WP)->wxr_version ?? ''),
            'base_site_url' => (string) ($channel->children(self::NS_WP)->base_site_url ?? ''),
            'base_blog_url' => (string) ($channel->children(self::NS_WP)->base_blog_url ?? ''),
        ];

        $result['authors'] = $this->parse_authors($channel, self::NS_WP);

        foreach ($channel->item as $item) {
            $parsed = $this->parse_item($item);
            if ($parsed === null) {
                continue;
            }

            if (($parsed['kind'] ?? 'content') === 'attachment') {
                $attachment = $parsed['attachment'] ?? null;
                $wpId = (int) ($attachment['wp_id'] ?? 0);
                if (is_array($attachment) && $wpId > 0) {
                    $result['attachments'][$wpId] = $attachment;
                }
                continue;
            }

            switch ((string) ($parsed['post_type'] ?? '')) {
                case 'post':
                    $result['posts'][] = $parsed;
                    break;
                case 'page':
                    $result['pages'][] = $parsed;
                    break;
                case 'tablepress_table':
                    $result['tables'][] = $parsed;
                    break;
                default:
                    $result['others'][] = $parsed;
                    break;
            }
        }

        $result = $this->resolve_attachment_references($result);

        return $result;
    }

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

    private function parse_item(\SimpleXMLElement $item): ?array
    {
        $wp = $item->children(self::NS_WP);
        $post_type = trim((string) $wp->post_type);
        $post_status = trim((string) $wp->status);

        if ($post_type === 'attachment') {
            return $this->parse_attachment($item, $wp);
        }

        if (in_array($post_type, ['nav_menu_item', 'custom_css', 'user_request'], true)) {
            return null;
        }

        if (in_array($post_status, ['auto-draft', 'inherit'], true)) {
            return null;
        }

        $content_ns = $item->children(self::NS_CONTENT);
        $excerpt_ns = $item->children(self::NS_EXCERPT);
        $dc_ns = $item->children(self::NS_DC);
        $meta = $this->extract_meta($wp);
        $raw_content = (string) ($content_ns->encoded ?? '');
        $resolvedDate = $this->resolve_item_datetime($item, $wp, ['post_date', 'post_date_gmt'], ['pubDate']);
        $resolvedModified = $this->resolve_item_datetime($item, $wp, ['post_modified', 'post_modified_gmt'], ['pubDate'], $resolvedDate);

        $resolvedSlug = $this->resolve_item_slug($wp, $item, $post_type);

        $parsed = [
            'kind'                 => 'content',
            'wp_id'                => (int) (string) $wp->post_id,
            'title'                => trim((string) ($item->title ?? '')),
            'slug'                 => $resolvedSlug,
            'link'                 => trim((string) ($item->link ?? '')),
            'guid'                 => trim((string) ($item->guid ?? '')),
            'content'              => $raw_content,
            'excerpt'              => trim((string) ($excerpt_ns->encoded ?? '')),
            'author_login'         => trim((string) ($dc_ns->creator ?? '')),
            'post_type'            => $post_type,
            'post_status'          => $post_status,
            'date'                 => $resolvedDate,
            'date_gmt'             => trim((string) $wp->post_date_gmt),
            'modified'             => $resolvedModified,
            'modified_gmt'         => trim((string) $wp->post_modified_gmt),
            'parent_id'            => (int) (string) $wp->post_parent,
            'menu_order'           => (int) (string) $wp->menu_order,
            'comment_status'       => (string) $wp->comment_status,
            'ping_status'          => (string) $wp->ping_status,
            'is_sticky'            => ((string) $wp->is_sticky === '1'),
            'translation_priority' => '',
            'locale'               => 'de',
            'categories'           => [],
            'tags'                 => [],
            'meta'                 => $meta,
            'mapped_meta_keys'     => [],
            'meta_title'           => '',
            'meta_description'     => '',
            'featured_image'       => '',
            'featured_image_wp_id' => 0,
            'featured_image_alt'   => '',
            'featured_image_caption' => '',
            'seo'                  => $this->default_seo_payload(),
            'image_urls'           => [],
            'table'                => null,
            'legacy_table_id'      => '',
        ];

        foreach ($item->category as $cat) {
            $domain = (string) $cat->attributes()->domain;
            $nicename = trim((string) ($cat->attributes()->nicename ?? ''));
            $value = trim((string) $cat);
            if ($value === '') {
                continue;
            }

            if ($domain === 'translation_priority') {
                $parsed['translation_priority'] = $nicename !== '' ? $nicename : $value;
                continue;
            }

            if ($domain === 'post_tag') {
                $parsed['tags'][] = $value;
                continue;
            }

            $parsed['categories'][] = $value;
        }

        $parsed = $this->map_known_meta($parsed);
        $parsed['locale'] = $this->resolve_item_locale($parsed);

        if ($this->should_ignore_english_item($parsed)) {
            return null;
        }

        $parsed['image_urls'] = $this->collect_image_urls($raw_content, $parsed['meta'], $parsed['seo']);

        if ($post_type === 'tablepress_table') {
            $parsed['table'] = $this->build_table_payload($parsed);
            $parsed['legacy_table_id'] = trim((string) ($parsed['meta']['_tablepress_export_table_id'] ?? ''));
            $parsed['mapped_meta_keys'] = array_values(array_unique(array_merge(
                $parsed['mapped_meta_keys'],
                ['_tablepress_table_options', '_tablepress_table_visibility', '_tablepress_export_table_id']
            )));
        }

        return $parsed;
    }

    private function parse_attachment(\SimpleXMLElement $item, \SimpleXMLElement $wp): ?array
    {
        $meta = $this->extract_meta($wp);
        $attachmentUrl = trim((string) ($wp->attachment_url ?? ''));
        if ($attachmentUrl === '') {
            return null;
        }

        $excerptNs = $item->children(self::NS_EXCERPT);
        $contentNs = $item->children(self::NS_CONTENT);
        $resolvedDate = $this->resolve_item_datetime($item, $wp, ['post_date', 'post_date_gmt'], ['pubDate']);

        return [
            'kind' => 'attachment',
            'attachment' => [
                'wp_id'        => (int) (string) $wp->post_id,
                'title'        => trim((string) ($item->title ?? '')),
                'slug'         => $this->resolve_item_slug($wp, $item, 'attachment'),
                'link'         => trim((string) ($item->link ?? '')),
                'url'          => $attachmentUrl,
                'mime_type'    => trim((string) ($wp->post_mime_type ?? '')),
                'alt_text'     => trim((string) ($meta['_wp_attachment_image_alt'] ?? '')),
                'caption'      => trim((string) ($excerptNs->encoded ?? '')),
                'description'  => trim((string) ($contentNs->encoded ?? '')),
                'post_status'  => trim((string) $wp->status),
                'date'         => $resolvedDate,
                'meta'         => $meta,
                'filename'     => basename((string) parse_url($attachmentUrl, PHP_URL_PATH)),
            ],
        ];
    }

    /**
     * @param list<string> $wpFields
     * @param list<string> $itemFields
     */
    private function resolve_item_datetime(
        \SimpleXMLElement $item,
        \SimpleXMLElement $wp,
        array $wpFields,
        array $itemFields = [],
        string $fallback = ''
    ): string {
        foreach ($wpFields as $field) {
            $value = trim((string) ($wp->{$field} ?? ''));
            if ($this->looks_like_valid_datetime($value)) {
                return $value;
            }
        }

        foreach ($itemFields as $field) {
            $value = trim((string) ($item->{$field} ?? ''));
            if ($this->looks_like_valid_datetime($value)) {
                $timestamp = strtotime($value);
                if ($timestamp !== false) {
                    return date('Y-m-d H:i:s', $timestamp);
                }
            }
        }

        return $this->looks_like_valid_datetime($fallback) ? $fallback : '';
    }

    private function looks_like_valid_datetime(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || $value === '0000-00-00 00:00:00') {
            return false;
        }

        return strtotime($value) !== false;
    }

    private function resolve_item_slug(\SimpleXMLElement $wp, \SimpleXMLElement $item, string $postType): string
    {
        $postName = trim((string) ($wp->post_name ?? ''));
        if ($postName !== '') {
            return $postName;
        }

        $linkSlug = $this->extract_slug_from_url((string) ($item->link ?? ''));
        if ($linkSlug !== '') {
            return $linkSlug;
        }

        $guidSlug = $this->extract_slug_from_url((string) ($item->guid ?? ''));
        if ($guidSlug !== '') {
            return $guidSlug;
        }

        foreach ($wp->postmeta as $metaItem) {
            $metaKey = trim((string) ($metaItem->meta_key ?? ''));
            if ($metaKey !== '_wp_old_slug') {
                continue;
            }

            $metaValue = trim((string) ($metaItem->meta_value ?? ''));
            if ($metaValue !== '') {
                return $metaValue;
            }
        }

        return '';
    }

    private function extract_slug_from_url(string $url): string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return '';
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($path === '') {
            return '';
        }

        $segments = array_values(array_filter(explode('/', $path), static fn(string $segment): bool => $segment !== ''));
        if ($segments === []) {
            return '';
        }

        return urldecode((string) end($segments));
    }

    private function resolve_attachment_references(array $result): array
    {
        foreach (['posts', 'pages', 'others', 'tables'] as $group) {
            foreach ($result[$group] as $index => $item) {
                $attachmentId = (int) ($item['featured_image_wp_id'] ?? 0);
                if ($attachmentId <= 0 || !isset($result['attachments'][$attachmentId])) {
                    continue;
                }

                $attachment = $result['attachments'][$attachmentId];
                $attachmentUrl = trim((string) ($attachment['url'] ?? ''));
                if ($attachmentUrl === '') {
                    continue;
                }

                $result[$group][$index]['featured_image'] = $attachmentUrl;
                $result[$group][$index]['featured_image_alt'] = (string) ($attachment['alt_text'] ?? '');
                $result[$group][$index]['featured_image_caption'] = (string) ($attachment['caption'] ?? '');

                $imageUrls = $result[$group][$index]['image_urls'] ?? [];
                if (!in_array($attachmentUrl, $imageUrls, true)) {
                    array_unshift($imageUrls, $attachmentUrl);
                    $result[$group][$index]['image_urls'] = array_values(array_unique($imageUrls));
                }
            }
        }

        return $result;
    }

    private function extract_meta(\SimpleXMLElement $wp): array
    {
        $meta = [];
        foreach ($wp->postmeta as $meta_item) {
            $key = trim((string) $meta_item->meta_key);
            if ($key === '' || $this->is_internal_wp_meta($key)) {
                continue;
            }

            $meta[$key] = (string) $meta_item->meta_value;
        }

        return $meta;
    }

    private function map_known_meta(array $parsed): array
    {
        $meta = $parsed['meta'];

        $titleMatch = $this->first_non_empty_meta($meta, ['_yoast_wpseo_title', 'rank_math_title', '_seopress_titles_title', '_aioseo_title', 'aioseo_title', '_aioseop_title']);
        if ($titleMatch !== null) {
            $parsed['meta_title'] = $this->sanitize_text_value($titleMatch['value'], 255);
            $parsed['mapped_meta_keys'][] = $titleMatch['key'];
        }

        $descMatch = $this->first_non_empty_meta($meta, ['_yoast_wpseo_metadesc', 'rank_math_description', '_seopress_titles_desc', '_aioseo_description', 'aioseo_description', '_aioseop_description']);
        if ($descMatch !== null) {
            $parsed['meta_description'] = $this->sanitize_text_value($descMatch['value'], 1000);
            $parsed['mapped_meta_keys'][] = $descMatch['key'];
        }

        $thumbnailMatch = $this->first_non_empty_meta($meta, ['_thumbnail_id']);
        if ($thumbnailMatch !== null) {
            $parsed['featured_image_wp_id'] = (int) $thumbnailMatch['value'];
            $parsed['mapped_meta_keys'][] = $thumbnailMatch['key'];
        }

        $seo = $this->default_seo_payload();
        $canonicalMatch = $this->first_non_empty_meta($meta, ['_yoast_wpseo_canonical', 'rank_math_canonical_url', '_seopress_robots_canonical', '_aioseo_canonical_url', 'aioseo_canonical_url', '_aioseop_custom_link', '_aioseop_canonical_link']);
        if ($canonicalMatch !== null) {
            $seo['canonical_url'] = trim($canonicalMatch['value']);
            $parsed['mapped_meta_keys'][] = $canonicalMatch['key'];
        }

        [$robotsIndex, $robotsIndexKeys] = $this->extract_robots_index($meta);
        if ($robotsIndex !== null) {
            $seo['robots_index'] = $robotsIndex;
            $parsed['mapped_meta_keys'] = array_merge($parsed['mapped_meta_keys'], $robotsIndexKeys);
        }

        [$robotsFollow, $robotsFollowKeys] = $this->extract_robots_follow($meta);
        if ($robotsFollow !== null) {
            $seo['robots_follow'] = $robotsFollow;
            $parsed['mapped_meta_keys'] = array_merge($parsed['mapped_meta_keys'], $robotsFollowKeys);
        }

        foreach ([
            'og_title' => ['_yoast_wpseo_opengraph-title', 'rank_math_facebook_title', '_seopress_social_fb_title', '_aioseo_og_title', 'aioseo_og_title', '_aioseop_opengraph_settings_title'],
            'og_description' => ['_yoast_wpseo_opengraph-description', 'rank_math_facebook_description', '_seopress_social_fb_desc', '_aioseo_og_description', 'aioseo_og_description', '_aioseop_opengraph_settings_desc'],
            'og_image' => ['_yoast_wpseo_opengraph-image', 'rank_math_facebook_image', '_seopress_social_fb_img', '_aioseo_og_image', 'aioseo_og_image', '_aioseo_facebook_image', 'aioseo_facebook_image'],
            'og_type' => ['_aioseo_og_type', 'aioseo_og_type', '_yoast_wpseo_opengraph-type', '_seopress_social_fb_type'],
            'twitter_card' => ['_yoast_wpseo_twitter-card', 'rank_math_twitter_card_type', '_seopress_social_twitter_card', '_aioseo_twitter_card', 'aioseo_twitter_card'],
            'twitter_title' => ['_yoast_wpseo_twitter-title', 'rank_math_twitter_title', '_seopress_social_twitter_title', '_aioseo_twitter_title', 'aioseo_twitter_title'],
            'twitter_description' => ['_yoast_wpseo_twitter-description', 'rank_math_twitter_description', '_seopress_social_twitter_desc', '_aioseo_twitter_description', 'aioseo_twitter_description'],
            'twitter_image' => ['_yoast_wpseo_twitter-image', 'rank_math_twitter_image', '_seopress_social_twitter_img', '_aioseo_twitter_image', 'aioseo_twitter_image'],
            'focus_keyphrase' => ['_yoast_wpseo_focuskw', 'rank_math_focus_keyword', '_seopress_analysis_target_kw', '_aioseo_focus_keyphrase', 'aioseo_focus_keyphrase', '_aioseop_keywords'],
            'schema_type' => ['rank_math_schema_type', '_yoast_wpseo_schema_page_type', '_yoast_wpseo_schema_article_type', '_aioseo_schema_type', 'aioseo_schema_type'],
            'sitemap_priority' => ['rank_math_sitemap_priority', '_aioseo_sitemap_priority', 'aioseo_sitemap_priority'],
            'sitemap_changefreq' => ['rank_math_sitemap_changefreq', '_aioseo_sitemap_frequency', 'aioseo_sitemap_frequency'],
            'hreflang_group' => ['_wpml_translation_group'],
        ] as $seoField => $keys) {
            $match = $this->first_non_empty_meta($meta, $keys);
            if ($match === null) {
                continue;
            }

            $seo[$seoField] = $this->normalize_seo_field_value($seoField, trim($match['value']), $parsed, $seo);
            $parsed['mapped_meta_keys'][] = $match['key'];
        }

        if ($seo['canonical_url'] === '') {
            $fallbackCanonical = trim((string) ($parsed['link'] ?? ''));
            if ($fallbackCanonical !== '' && filter_var($fallbackCanonical, FILTER_VALIDATE_URL) !== false) {
                $seo['canonical_url'] = $fallbackCanonical;
            }
        }

        if ($seo['schema_type'] === '') {
            $seo['schema_type'] = $this->default_schema_type_for_post_type((string) ($parsed['post_type'] ?? ''));
        } else {
            $seo['schema_type'] = $this->normalize_schema_type($seo['schema_type'], (string) ($parsed['post_type'] ?? ''));
        }
        if ($seo['og_type'] === '') {
            $seo['og_type'] = $this->default_og_type_for_post_type((string) ($parsed['post_type'] ?? ''));
        } else {
            $seo['og_type'] = $this->normalize_og_type($seo['og_type'], (string) ($parsed['post_type'] ?? ''));
        }
        if ($seo['twitter_card'] === '') {
            $seo['twitter_card'] = $this->default_twitter_card($seo);
        } else {
            $seo['twitter_card'] = $this->normalize_twitter_card($seo['twitter_card'], $seo);
        }
        if ($seo['og_title'] === '') {
            $seo['og_title'] = $parsed['meta_title'] !== '' ? $parsed['meta_title'] : $parsed['title'];
        }
        if ($seo['twitter_title'] === '') {
            $seo['twitter_title'] = $seo['og_title'];
        }
        if ($seo['og_description'] === '') {
            $seo['og_description'] = $parsed['meta_description'];
        }
        if ($seo['twitter_description'] === '') {
            $seo['twitter_description'] = $seo['og_description'];
        }
        if ($seo['og_image'] === '' && !empty($parsed['featured_image']) && filter_var((string) $parsed['featured_image'], FILTER_VALIDATE_URL) !== false) {
            $seo['og_image'] = (string) $parsed['featured_image'];
        }
        if ($seo['twitter_image'] === '') {
            $seo['twitter_image'] = $seo['og_image'];
        }

        $seo['sitemap_priority'] = $this->normalize_sitemap_priority((string) $seo['sitemap_priority']);
        $seo['sitemap_changefreq'] = $this->normalize_sitemap_changefreq((string) $seo['sitemap_changefreq']);
        $seo['focus_keyphrase'] = $this->sanitize_text_value((string) $seo['focus_keyphrase'], 255);

        $parsed['seo'] = $seo;
        $parsed['mapped_meta_keys'] = array_values(array_unique($parsed['mapped_meta_keys']));

        return $parsed;
    }

    private function collect_image_urls(string $content, array $meta, array $seo): array
    {
        $urls = $this->extract_image_urls($content);

        foreach ($meta as $key => $value) {
            $candidateKey = strtolower($key);
            if (!str_contains($candidateKey, 'image') && !str_contains($candidateKey, 'thumbnail') && !str_contains($candidateKey, 'featured')) {
                continue;
            }

            foreach ($this->extract_urls_from_text((string) $value) as $candidateUrl) {
                if ($this->looks_like_image_url($candidateUrl)) {
                    $urls[] = $candidateUrl;
                }
            }
        }

        foreach (['og_image', 'twitter_image'] as $seoKey) {
            $candidate = trim((string) ($seo[$seoKey] ?? ''));
            if ($candidate !== '' && $this->looks_like_image_url($candidate)) {
                $urls[] = $candidate;
            }
        }

        return array_values(array_unique(array_filter($urls, static fn(string $url): bool => filter_var($url, FILTER_VALIDATE_URL) !== false)));
    }

    public function extract_image_urls(string $content): array
    {
        if ($content === '') {
            return [];
        }

        $urls = [];

        if (preg_match_all('/<img[^>]+src=["\']([^"\'>\s]+)["\'][^>]*>/i', $content, $matches)) {
            foreach ($matches[1] as $url) {
                $urls[] = trim($url);
            }
        }

        if (preg_match_all('/(?:srcset|data-src|data-lazy-src|poster)=["\']([^"\']+)["\']/i', $content, $matches)) {
            foreach ($matches[1] as $srcset) {
                foreach (preg_split('/\s*,\s*/', (string) $srcset) ?: [] as $segment) {
                    $part = trim((string) preg_replace('/\s+\d+[wx]$/', '', $segment));
                    if ($part !== '') {
                        $urls[] = $part;
                    }
                }
            }
        }

        if (preg_match_all('/url\((https?:\/\/[^)"\']+)\)/i', $content, $matches)) {
            foreach ($matches[1] as $url) {
                $urls[] = trim($url, '"\' ');
            }
        }

        if (preg_match_all('/"url"\s*:\s*"(https?:\/\/[^\"]+)"/i', $content, $matches)) {
            foreach ($matches[1] as $url) {
                $urls[] = trim($url);
            }
        }

        return array_values(array_unique(array_filter($urls, fn(string $url): bool => $this->looks_like_image_url($url))));
    }

    private function build_table_payload(array $parsed): array
    {
        $meta = $parsed['meta'];
        $rawRows = $this->decode_table_rows((string) ($parsed['content'] ?? ''));
        $options = $this->decode_structured_value((string) ($meta['_tablepress_table_options'] ?? ''));
        $visibility = $this->decode_structured_value((string) ($meta['_tablepress_table_visibility'] ?? ''));

        $tableHead = !empty($options['table_head']);
        $headerRow = $tableHead && $rawRows !== [] ? array_shift($rawRows) : [];
        $columnCount = $this->resolve_column_count($headerRow, $rawRows);

        $columns = [];
        for ($index = 0; $index < $columnCount; $index++) {
            $label = $this->normalize_table_cell((string) ($headerRow[$index] ?? ''));
            if ($label === '') {
                $label = 'Spalte ' . ($index + 1);
            }

            $columns[] = [
                'label' => $this->safe_substr($label, 0, 120),
                'type' => 'text',
            ];
        }

        $rows = [];
        foreach ($rawRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalizedRow = [];
            foreach ($columns as $index => $column) {
                $label = (string) ($column['label'] ?? ('Spalte ' . ($index + 1)));
                $normalizedRow[$label] = $this->safe_substr($this->normalize_table_cell((string) ($row[$index] ?? '')), 0, 5000);
            }
            $rows[] = $normalizedRow;
        }

        $description = trim((string) ($parsed['excerpt'] ?? ''));
        $sourceFilename = '';
        if (preg_match('/\.(csv|xlsx?|ods|tsv)$/i', $description)) {
            $sourceFilename = $description;
            $description = '';
        }

        return [
            'name' => (string) (($parsed['title'] ?? '') !== '' ? $parsed['title'] : ($sourceFilename !== '' ? $sourceFilename : 'Importierte Tabelle')),
            'slug' => (string) ($parsed['slug'] ?? ''),
            'description' => $description,
            'columns' => $columns,
            'rows' => $rows,
            'settings' => [
                'responsive' => true,
                'style_theme' => !empty($options['alternating_row_colors']) ? 'stripe' : 'default',
                'caption' => trim((string) (!empty($options['print_name']) ? ($parsed['title'] ?? '') : '')),
                'aria_label' => (string) ($parsed['title'] ?? ''),
                'allow_export_csv' => true,
                'allow_export_json' => false,
                'allow_export_excel' => false,
                'enable_search' => !empty($options['datatables_filter']),
                'enable_sorting' => !empty($options['datatables_sort']),
                'enable_pagination' => !empty($options['datatables_paginate']),
                'page_size' => max(1, (int) ($options['datatables_paginate_entries'] ?? 10)),
                'highlight_rows' => !empty($options['row_hover']),
                'custom_css' => trim((string) ($options['extra_css_classes'] ?? '')),
                'content_mode' => 'table',
                'source_post_type' => 'tablepress_table',
                'source_wp_id' => (int) ($parsed['wp_id'] ?? 0),
                'legacy_table_id' => trim((string) ($parsed['meta']['_tablepress_export_table_id'] ?? '')),
                'source_url' => (string) ($parsed['link'] ?? ''),
                'source_filename' => $sourceFilename,
                'tablepress_options' => $options,
                'tablepress_visibility' => $visibility,
            ],
        ];
    }

    private function decode_table_rows(string $content): array
    {
        $decoded = json_decode(trim($content), true);
        return is_array($decoded) ? array_values(array_filter($decoded, static fn($row): bool => is_array($row))) : [];
    }

    private function resolve_column_count(array $headerRow, array $rows): int
    {
        $count = count($headerRow);
        foreach ($rows as $row) {
            if (is_array($row)) {
                $count = max($count, count($row));
            }
        }

        return max(1, $count);
    }

    private function normalize_table_cell(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = preg_replace_callback(
            '/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is',
            static function (array $matches): string {
                $url = trim(html_entity_decode((string) ($matches[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $label = trim(strip_tags((string) ($matches[2] ?? '')));
                if ($label !== '' && $url !== '') {
                    return $label . ' (' . $url . ')';
                }

                return $label !== '' ? $label : $url;
            },
            $value
        ) ?? $value;

        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return trim($value);
    }

    private function extract_robots_index(array $meta): array
    {
        $keys = [];
        if (isset($meta['_yoast_wpseo_meta-robots-noindex'])) {
            $keys[] = '_yoast_wpseo_meta-robots-noindex';
            $value = strtolower(trim((string) $meta['_yoast_wpseo_meta-robots-noindex']));
            return [!in_array($value, ['1', 'true', 'noindex'], true), $keys];
        }

        if (isset($meta['rank_math_robots'])) {
            $keys[] = 'rank_math_robots';
            $robots = $this->normalize_robot_list((string) $meta['rank_math_robots']);
            if (in_array('noindex', $robots, true)) {
                return [false, $keys];
            }
            if (in_array('index', $robots, true)) {
                return [true, $keys];
            }
        }

        if (isset($meta['_seopress_robots_index'])) {
            $keys[] = '_seopress_robots_index';
            $value = strtolower(trim((string) $meta['_seopress_robots_index']));
            return [!in_array($value, ['1', 'true', 'noindex'], true), $keys];
        }

        foreach (['_aioseop_noindex', '_aioseo_noindex', 'aioseo_noindex'] as $key) {
            if (!isset($meta[$key])) {
                continue;
            }

            $keys[] = $key;
            $value = strtolower(trim((string) $meta[$key]));
            return [!in_array($value, ['1', 'true', 'yes', 'on', 'noindex'], true), $keys];
        }

        return [null, $keys];
    }

    private function extract_robots_follow(array $meta): array
    {
        $keys = [];
        if (isset($meta['_yoast_wpseo_meta-robots-nofollow'])) {
            $keys[] = '_yoast_wpseo_meta-robots-nofollow';
            $value = strtolower(trim((string) $meta['_yoast_wpseo_meta-robots-nofollow']));
            return [!in_array($value, ['1', 'true', 'nofollow'], true), $keys];
        }

        if (isset($meta['rank_math_robots'])) {
            $keys[] = 'rank_math_robots';
            $robots = $this->normalize_robot_list((string) $meta['rank_math_robots']);
            if (in_array('nofollow', $robots, true)) {
                return [false, $keys];
            }
            if (in_array('follow', $robots, true)) {
                return [true, $keys];
            }
        }

        if (isset($meta['_seopress_robots_follow'])) {
            $keys[] = '_seopress_robots_follow';
            $value = strtolower(trim((string) $meta['_seopress_robots_follow']));
            return [!in_array($value, ['0', 'false', 'nofollow'], true), $keys];
        }

        foreach (['_aioseop_nofollow', '_aioseo_nofollow', 'aioseo_nofollow'] as $key) {
            if (!isset($meta[$key])) {
                continue;
            }

            $keys[] = $key;
            $value = strtolower(trim((string) $meta[$key]));
            return [!in_array($value, ['1', 'true', 'yes', 'on', 'nofollow'], true), $keys];
        }

        return [null, $keys];
    }

    private function normalize_robot_list(string $value): array
    {
        $decoded = $this->decode_structured_value($value);
        if (is_array($decoded)) {
            $robots = [];
            array_walk_recursive($decoded, static function ($item) use (&$robots): void {
                if (is_string($item)) {
                    $robots[] = strtolower(trim($item));
                }
            });
            return array_values(array_unique(array_filter($robots)));
        }

        $items = preg_split('/[,|;]/', strtolower(trim($value))) ?: [];
        return array_values(array_unique(array_filter(array_map('trim', $items))));
    }

    private function decode_structured_value(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        $json = json_decode($value, true);
        if (is_array($json)) {
            return $json;
        }

        if (preg_match('/^(a|s|i|b|d|N|O):/i', $value) === 1) {
            $unserialized = @unserialize($value, ['allowed_classes' => false]);
            if (is_array($unserialized)) {
                return $unserialized;
            }
        }

        return [];
    }

    private function first_non_empty_meta(array $meta, array $keys): ?array
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $meta)) {
                continue;
            }

            $value = trim((string) $meta[$key]);
            if ($value === '') {
                continue;
            }

            return ['key' => $key, 'value' => $value];
        }

        return null;
    }

    private function normalize_seo_field_value(string $field, string $value, array $parsed, array $seo): string
    {
        return match ($field) {
            'og_title', 'twitter_title' => $this->sanitize_text_value($value, 255),
            'og_description', 'twitter_description' => $this->sanitize_text_value($value, 1000),
            'focus_keyphrase' => $this->sanitize_text_value($value, 255),
            'canonical_url' => trim($value),
            'og_image', 'twitter_image' => trim($value),
            'og_type' => $this->normalize_og_type($value, (string) ($parsed['post_type'] ?? '')),
            'twitter_card' => $this->normalize_twitter_card($value, $seo),
            'schema_type' => $this->normalize_schema_type($value, (string) ($parsed['post_type'] ?? '')),
            'sitemap_priority' => $this->normalize_sitemap_priority($value),
            'sitemap_changefreq' => $this->normalize_sitemap_changefreq($value),
            default => trim($value),
        };
    }

    private function normalize_schema_type(string $value, string $postType): string
    {
        $value = trim($value);
        if ($value === '') {
            return $this->default_schema_type_for_post_type($postType);
        }

        $normalized = strtolower(str_replace(['-', '_', ' '], '', $value));

        return match ($normalized) {
            'article', 'blogposting', 'newsarticle', 'posting' => 'Article',
            'webpage', 'page', 'website' => 'WebPage',
            'faqpage' => 'FAQPage',
            'contactpage' => 'ContactPage',
            'aboutpage' => 'AboutPage',
            default => $value,
        };
    }

    private function normalize_og_type(string $value, string $postType): string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return $this->default_og_type_for_post_type($postType);
        }

        return match ($value) {
            'post', 'article', 'blog', 'blogposting', 'newsarticle' => 'article',
            'page', 'website', 'webpage', 'site' => 'website',
            'profile', 'book', 'music.song', 'music.album', 'video.movie', 'video.episode', 'video.tv_show' => $value,
            default => $this->default_og_type_for_post_type($postType),
        };
    }

    private function normalize_twitter_card(string $value, array $seo): string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return $this->default_twitter_card($seo);
        }

        return match ($value) {
            'summary', 'summary_large_image', 'app', 'player' => $value,
            'large', 'large_image', 'summarylargeimage' => 'summary_large_image',
            default => $this->default_twitter_card($seo),
        };
    }

    private function default_twitter_card(array $seo): string
    {
        return trim((string) ($seo['og_image'] ?? '')) !== '' || trim((string) ($seo['twitter_image'] ?? '')) !== ''
            ? 'summary_large_image'
            : 'summary';
    }

    private function normalize_sitemap_priority(string $value): string
    {
        $value = str_replace(',', '.', trim($value));
        if ($value === '' || !is_numeric($value)) {
            return '';
        }

        $priority = max(0.0, min(1.0, (float) $value));
        $formatted = number_format($priority, 1, '.', '');
        return rtrim(rtrim($formatted, '0'), '.');
    }

    private function normalize_sitemap_changefreq(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        return match ($value) {
            'always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never' => $value,
            'annually', 'annual' => 'yearly',
            default => '',
        };
    }

    private function default_schema_type_for_post_type(string $postType): string
    {
        return $postType === 'post' ? 'Article' : 'WebPage';
    }

    private function default_og_type_for_post_type(string $postType): string
    {
        return $postType === 'post' ? 'article' : 'website';
    }

    private function extract_urls_from_text(string $value): array
    {
        if ($value === '') {
            return [];
        }

        if (!preg_match_all('/https?:\/\/[^\s"\'<>]+/i', $value, $matches)) {
            return [];
        }

        return array_values(array_unique(array_map(static fn(string $url): string => html_entity_decode(rtrim($url, '.,);'), ENT_QUOTES | ENT_HTML5, 'UTF-8'), $matches[0])));
    }

    private function looks_like_image_url(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $path = strtolower((string) parse_url($url, PHP_URL_PATH));
        return preg_match('/\.(jpe?g|png|gif|webp|bmp|svg|avif)(?:$|\?)/i', $path) === 1;
    }

    private function sanitize_text_value(string $value, int $maxLength): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return $this->safe_substr(trim($value), 0, $maxLength);
    }

    private function safe_substr(string $value, int $start, int $length): string
    {
        if (function_exists('mb_substr')) {
            return (string) mb_substr($value, $start, $length);
        }

        return substr($value, $start, $length);
    }

    private function default_seo_payload(): array
    {
        return [
            'canonical_url' => '',
            'robots_index' => true,
            'robots_follow' => true,
            'og_title' => '',
            'og_description' => '',
            'og_image' => '',
            'og_type' => 'article',
            'twitter_card' => 'summary_large_image',
            'twitter_title' => '',
            'twitter_description' => '',
            'twitter_image' => '',
            'focus_keyphrase' => '',
            'schema_type' => 'WebPage',
            'sitemap_priority' => '',
            'sitemap_changefreq' => '',
            'hreflang_group' => '',
        ];
    }

    private function is_internal_wp_meta(string $key): bool
    {
        foreach (['_edit_lock', '_edit_last', '_oembed_', '_pingme', '_encloseme', '_wp_old_slug', '_wp_old_date'] as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function resolve_item_locale(array $parsed): string
    {
        $translationPriority = strtolower(trim((string) ($parsed['translation_priority'] ?? '')));
        if ($translationPriority !== '' && str_ends_with($translationPriority, '-en')) {
            return 'en';
        }

        foreach ([(string) ($parsed['link'] ?? ''), (string) ($parsed['guid'] ?? '')] as $candidateUrl) {
            $locale = $this->extract_locale_from_url($candidateUrl);
            if ($locale !== '') {
                return $locale;
            }
        }

        return 'de';
    }

    private function should_ignore_english_item(array $parsed): bool
    {
        if (strtolower(trim((string) ($parsed['locale'] ?? ''))) === 'en') {
            return true;
        }

        $translationPriority = strtolower(trim((string) ($parsed['translation_priority'] ?? '')));
        if ($translationPriority !== '' && str_ends_with($translationPriority, '-en')) {
            return true;
        }

        foreach ([(string) ($parsed['link'] ?? ''), (string) ($parsed['guid'] ?? ''), (string) ($parsed['slug'] ?? '')] as $candidateUrl) {
            if ($this->extract_locale_from_url($candidateUrl) === 'en') {
                return true;
            }
        }

        return false;
    }

    private function extract_locale_from_url(string $url): string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($url === '') {
            return '';
        }

        $path = filter_var($url, FILTER_VALIDATE_URL) !== false
            ? (string) parse_url($url, PHP_URL_PATH)
            : $url;

        $segments = array_values(array_filter(
            explode('/', trim($path, '/')),
            static fn(string $segment): bool => trim($segment) !== ''
        ));

        if ($segments === []) {
            return '';
        }

        $firstSegment = strtolower((string) ($segments[0] ?? ''));
        $lastSegment = strtolower((string) ($segments[count($segments) - 1] ?? ''));

        if ($firstSegment === 'en' || $lastSegment === 'en') {
            return 'en';
        }

        return '';
    }
}
