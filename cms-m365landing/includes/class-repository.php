<?php
/**
 * CMS M365 Landing – Repository.
 *
 * @package CMS_M365Landing
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365Landing_Repository
{
    private static ?self $instance = null;
    private object $db;
    private string $prefix;
    /** @var array<string,string>|null */
    private ?array $settingsCache = null;
    /** @var array<int,array<string,mixed>>|null */
    private ?array $postCategoriesCache = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = \CMS\Database::instance();
        $this->prefix = $this->resolve_prefix($this->db);
    }

    /** @return array<string,string> */
    public function settings(): array
    {
        if ($this->settingsCache !== null) {
            return $this->settingsCache;
        }

        try {
            $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM {$this->prefix}m365landing_settings");
            $stmt->execute();
        } catch (\Throwable $e) {
            self::log_exception('settings_load_failed', $e);
            return [];
        }

        $settings = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $settings[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }

        $this->settingsCache = $settings;

        return $this->settingsCache;
    }

    /** @param array<string,string> $settings */
    public function save_settings(array $settings): void
    {
        $exists = $this->db->prepare("SELECT id FROM {$this->prefix}m365landing_settings WHERE setting_key = ?");
        $insert = $this->db->prepare("INSERT INTO {$this->prefix}m365landing_settings (setting_key, setting_value) VALUES (?, ?)");
        $update = $this->db->prepare("UPDATE {$this->prefix}m365landing_settings SET setting_value = ? WHERE setting_key = ?");

        foreach ($settings as $key => $value) {
            $exists->execute([$key]);
            if ($exists->fetch()) {
                $update->execute([$value, $key]);
            } else {
                $insert->execute([$key, $value]);
            }
        }

        $this->settingsCache = null;
    }

    /** @return array<int,array<string,mixed>> */
    public function cards(?string $section = null, bool $activeOnly = false): array
    {
        $where = [];
        $params = [];
        if ($section !== null && $section !== '') {
            $where[] = 'section = ?';
            $params[] = self::section($section);
        }
        if ($activeOnly) {
            $where[] = 'is_active = 1';
        }

        $sql = "SELECT * FROM {$this->prefix}m365landing_cards";
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY section ASC, sort_order ASC, title ASC';

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            self::log_exception('cards_load_failed', $e);
            return [];
        }
    }

    /** @return array<string,array<int,array<string,mixed>>> */
    public function cards_by_section(bool $activeOnly = true): array
    {
        $grouped = ['matrix' => [], 'areas' => [], 'tools' => []];
        foreach ($this->cards(null, $activeOnly) as $card) {
            $section = self::section((string) ($card['section'] ?? 'tools'));
            $grouped[$section][] = $card;
        }

        return $grouped;
    }

    /** @return array<string,mixed>|null */
    public function card(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}m365landing_cards WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            self::log_exception('card_load_failed', $e);
            return null;
        }

        return is_array($row) ? $row : null;
    }

    /** @param array<string,mixed> $data */
    public function save_card(array $data): int
    {
        $id = (int) ($data['id'] ?? 0);
        $title = self::text((string) ($data['title'] ?? ''));
        $values = [
            'section' => self::section((string) ($data['section'] ?? 'tools')),
            'slug' => self::slug((string) ($data['slug'] ?? $title)),
            'title' => $title,
            'subtitle' => self::text((string) ($data['subtitle'] ?? '')),
            'description' => self::long_text((string) ($data['description'] ?? '')),
            'icon' => self::text((string) ($data['icon'] ?? '')),
            'image_url' => self::public_image_url((string) ($data['image_url'] ?? '')),
            'image_alt' => self::text((string) ($data['image_alt'] ?? '')),
            'url' => self::public_url((string) ($data['url'] ?? '')),
            'button_label' => self::text((string) ($data['button_label'] ?? '')),
            'sort_order' => (int) ($data['sort_order'] ?? 100),
            'is_featured' => !empty($data['is_featured']) ? 1 : 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ];

        if ($values['title'] === '') {
            throw new \InvalidArgumentException('Der Kartentitel darf nicht leer sein.');
        }

        if ($id > 0) {
            $stmt = $this->db->prepare("UPDATE {$this->prefix}m365landing_cards
                SET section = ?, slug = ?, title = ?, subtitle = ?, description = ?, icon = ?, image_url = ?, image_alt = ?, url = ?, button_label = ?, sort_order = ?, is_featured = ?, is_active = ?
                WHERE id = ?");
            $stmt->execute([$values['section'], $values['slug'], $values['title'], $values['subtitle'], $values['description'], $values['icon'], $values['image_url'], $values['image_alt'], $values['url'], $values['button_label'], $values['sort_order'], $values['is_featured'], $values['is_active'], $id]);
            return $id;
        }

        $stmt = $this->db->prepare("INSERT INTO {$this->prefix}m365landing_cards (section, slug, title, subtitle, description, icon, image_url, image_alt, url, button_label, sort_order, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$values['section'], $values['slug'], $values['title'], $values['subtitle'], $values['description'], $values['icon'], $values['image_url'], $values['image_alt'], $values['url'], $values['button_label'], $values['sort_order'], $values['is_featured'], $values['is_active']]);

        return (int) $this->db->lastInsertId();
    }

    public function delete_card(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->prefix}m365landing_cards WHERE id = ?");
        $stmt->execute([$id]);
    }

    /** @return array<string,int> */
    public function stats(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT
                    SUM(CASE WHEN section = 'matrix' THEN 1 ELSE 0 END) AS matrix_count,
                    SUM(CASE WHEN section = 'areas' THEN 1 ELSE 0 END) AS areas_count,
                    SUM(CASE WHEN section = 'tools' THEN 1 ELSE 0 END) AS tools_count,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_cards
                FROM {$this->prefix}m365landing_cards");
            $stmt->execute();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return ['matrix' => 0, 'areas' => 0, 'tools' => 0, 'active_cards' => 0];
            }

            return [
                'matrix' => (int) ($row['matrix_count'] ?? 0),
                'areas' => (int) ($row['areas_count'] ?? 0),
                'tools' => (int) ($row['tools_count'] ?? 0),
                'active_cards' => (int) ($row['active_cards'] ?? 0),
            ];
        } catch (\Throwable $e) {
            self::log_exception('stats_load_failed', $e);
            return ['matrix' => 0, 'areas' => 0, 'tools' => 0, 'active_cards' => 0];
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function post_categories(): array
    {
        if ($this->postCategoriesCache !== null) {
            return $this->postCategoriesCache;
        }

        try {
            $stmt = $this->db->prepare("SELECT id, name, slug, parent_id, sort_order FROM {$this->prefix}post_categories ORDER BY COALESCE(parent_id, 0) ASC, sort_order ASC, name ASC");
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            $this->postCategoriesCache = $rows;

            return $this->postCategoriesCache;
        } catch (\Throwable $e) {
            self::log_exception('post_categories_load_failed', $e);
            return [];
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function latest_posts_by_category(int $categoryId, int $limit = 6): array
    {
        $categoryId = max(0, $categoryId);
        $limit = $this->normalize_posts_limit($limit);
        if ($categoryId <= 0) {
            return [];
        }

        $categoryIds = $this->category_ids_with_descendants($categoryId);
        if ($categoryIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));

        $publicationWhere = function_exists('cms_post_publication_where')
            ? \cms_post_publication_where('p')
            : "p.status = 'published'";

        try {
            $stmt = $this->db->prepare("SELECT p.id, p.title, p.slug, p.excerpt, p.content, p.featured_image, p.published_at, p.created_at, c.name AS category_name, c.slug AS category_slug
                FROM {$this->prefix}posts p
                LEFT JOIN {$this->prefix}post_categories c ON c.id = p.category_id
                WHERE {$publicationWhere}
                  AND (p.category_id IN ({$placeholders}) OR EXISTS (
                      SELECT 1
                      FROM {$this->prefix}post_category_rel pcr
                      WHERE pcr.post_id = p.id AND pcr.category_id IN ({$placeholders})
                  ))
                ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                LIMIT {$limit}");
            $stmt->execute(array_merge($categoryIds, $categoryIds));
            $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            self::log_exception('latest_posts_by_category_load_failed', $e);
            return [];
        }

        return $this->prepare_public_posts($posts);
    }

    /** @return array<int,array<string,mixed>> */
    public function latest_posts(int $limit = 6): array
    {
        $limit = $this->normalize_posts_limit($limit);
        $publicationWhere = function_exists('cms_post_publication_where')
            ? \cms_post_publication_where('p')
            : "p.status = 'published'";

        try {
            $stmt = $this->db->prepare("SELECT p.id, p.title, p.slug, p.excerpt, p.content, p.featured_image, p.published_at, p.created_at, c.name AS category_name, c.slug AS category_slug
                FROM {$this->prefix}posts p
                LEFT JOIN {$this->prefix}post_categories c ON c.id = p.category_id
                WHERE {$publicationWhere}
                ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                LIMIT {$limit}");
            $stmt->execute();
            $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            self::log_exception('latest_posts_load_failed', $e);
            return [];
        }

        return $this->prepare_public_posts($posts);
    }

    /** @param array<int,array<string,mixed>> $posts @return array<int,array<string,mixed>> */
    private function prepare_public_posts(array $posts): array
    {
        foreach ($posts as &$post) {
            $post['permalink'] = self::main_site_url($this->post_path($post));
            $post['featured_image'] = '';
            $excerptSource = trim((string) ($post['excerpt'] ?? ''));
            $contentSource = trim((string) ($post['content'] ?? ''));
            $post['excerpt_plain'] = self::excerpt_plain_text($excerptSource !== '' ? $excerptSource : $contentSource);
            $post['read_time'] = self::reading_time($contentSource !== '' ? $contentSource : (string) ($post['excerpt_plain'] ?? ''));
        }
        unset($post);

        return $posts;
    }

    /** @return array<int,int> */
    private function category_ids_with_descendants(int $categoryId): array
    {
        $categories = $this->post_categories();
        $childrenByParent = [];
        foreach ($categories as $category) {
            $id = (int) ($category['id'] ?? 0);
            $parentId = (int) ($category['parent_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $childrenByParent[$parentId][] = $id;
        }

        $ids = [];
        $visited = [];
        $stack = [$categoryId];
        while ($stack !== []) {
            $id = (int) array_pop($stack);
            if ($id <= 0 || isset($visited[$id])) {
                continue;
            }
            $visited[$id] = true;
            $ids[] = $id;
            foreach ($childrenByParent[$id] ?? [] as $childId) {
                $stack[] = (int) $childId;
            }
        }

        return $ids;
    }

    private function normalize_posts_limit(int $limit): int
    {
        return in_array($limit, [6, 9], true) ? $limit : 6;
    }

    public static function main_site_url(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $base = rtrim((string) (defined('SITE_URL') ? SITE_URL : ''), '/');
        if ($base === '') {
            return $url;
        }

        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return $base . $url;
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $path = (string) (parse_url($url, PHP_URL_PATH) ?: '/');
            $query = (string) (parse_url($url, PHP_URL_QUERY) ?: '');
            $fragment = (string) (parse_url($url, PHP_URL_FRAGMENT) ?: '');

            return $base . $path . ($query !== '' ? '?' . $query : '') . ($fragment !== '' ? '#' . $fragment : '');
        }

        return $url;
    }

    public static function main_site_media_url(string $value): string
    {
        $value = self::public_image_url($value);
        if ($value === '') {
            return '';
        }

        $base = rtrim((string) (defined('SITE_URL') ? SITE_URL : ''), '/');
        if ($base === '') {
            return $value;
        }

        $path = '';
        $query = '';
        $fragment = '';

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $path = (string) (parse_url($value, PHP_URL_PATH) ?: '');
            $query = (string) (parse_url($value, PHP_URL_QUERY) ?: '');
            $fragment = (string) (parse_url($value, PHP_URL_FRAGMENT) ?: '');
        } elseif (str_starts_with($value, '/') && !str_starts_with($value, '//')) {
            $path = (string) (parse_url($value, PHP_URL_PATH) ?: $value);
            $query = (string) (parse_url($value, PHP_URL_QUERY) ?: '');
            $fragment = (string) (parse_url($value, PHP_URL_FRAGMENT) ?: '');
        } else {
            return $value;
        }

        $path = '/' . ltrim($path, '/');
        if ($path === '/media-file') {
            parse_str($query, $params);
            $mediaPath = self::normalize_media_relative_path((string) ($params['path'] ?? ''));
            if ($mediaPath !== '') {
                $path = '/uploads/' . $mediaPath;
                $query = '';
                $fragment = '';
            }
        } elseif (!str_starts_with($path, '/uploads/') && preg_match('#^/(?:images/importer|importer|media)(?:/|$)#i', $path) === 1) {
            $path = '/uploads' . $path;
        }

        return $base . $path . ($query !== '' ? '?' . $query : '') . ($fragment !== '' ? '#' . $fragment : '');
    }

    public static function excerpt_plain_text(string $content): string
    {
        $content = trim($content);
        if ($content === '') {
            return '';
        }

        if (function_exists('phinit_excerpt_plain_text')) {
            try {
                return trim((string) phinit_excerpt_plain_text($content));
            } catch (\Throwable $e) {
                // Lokale Fallback-Extraktion verwenden.
            }
        }

        $decoded = json_decode($content, true);
        if (is_array($decoded) && isset($decoded['blocks']) && is_array($decoded['blocks'])) {
            $parts = [];
            foreach ($decoded['blocks'] as $block) {
                if (!is_array($block)) {
                    continue;
                }
                $data = $block['data'] ?? null;
                if (!is_array($data)) {
                    continue;
                }
                foreach (['text', 'caption', 'message', 'title'] as $key) {
                    if (!empty($data[$key]) && is_string($data[$key])) {
                        $parts[] = $data[$key];
                    }
                }
                if (!empty($data['items']) && is_array($data['items'])) {
                    foreach ($data['items'] as $item) {
                        if (is_string($item) && trim($item) !== '') {
                            $parts[] = $item;
                        }
                    }
                }
            }
            $content = implode(' ', $parts);
        } elseif (str_contains($content, '"blocks"') && (str_starts_with($content, '{') || str_starts_with($content, '['))) {
            $recovered = self::extract_malformed_editorjs_text($content);
            if ($recovered !== '') {
                $content = $recovered;
            }
        }

        $text = trim(html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return preg_replace('/\s+/u', ' ', $text) ?? '';
    }

    public static function reading_time(string $content, int $wordsPerMinute = 220): int
    {
        $plain = self::excerpt_plain_text($content);
        if ($plain === '') {
            return 0;
        }

        return max(1, (int) round(str_word_count($plain) / max(1, $wordsPerMinute)));
    }

    private static function extract_malformed_editorjs_text(string $raw): string
    {
        $parts = [];
        foreach (['text', 'caption', 'message', 'title'] as $key) {
            if (preg_match_all('/"' . preg_quote($key, '/') . '"\s*:\s*"((?:\\\\.|[^"\\\\])*)"/u', $raw, $matches)) {
                foreach ($matches[1] as $value) {
                    $decoded = json_decode('"' . $value . '"');
                    if (is_string($decoded) && trim($decoded) !== '') {
                        $parts[] = $decoded;
                    }
                }
            }
        }

        if (preg_match_all('/"items"\s*:\s*\[(.*?)\]/us', $raw, $itemGroups)) {
            foreach ($itemGroups[1] as $group) {
                if (preg_match_all('/"((?:\\\\.|[^"\\\\])*)"/u', $group, $itemMatches)) {
                    foreach ($itemMatches[1] as $value) {
                        $decoded = json_decode('"' . $value . '"');
                        if (is_string($decoded) && trim($decoded) !== '') {
                            $parts[] = $decoded;
                        }
                    }
                }
            }
        }

        $text = trim(html_entity_decode(strip_tags(implode(' ', $parts)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return preg_replace('/\s+/u', ' ', $text) ?? '';
    }

    public static function text(string $value): string
    {
        return trim(strip_tags(self::normalize_newlines($value)));
    }

    public static function long_text(string $value): string
    {
        return trim(strip_tags(self::normalize_newlines($value)));
    }

    public static function normalize_newlines(string $value): string
    {
        return str_replace(["\r\n", "\n", "\r"], ["\n", "\n", "\n"], $value);
    }

    public static function public_url(string $value): string
    {
        $value = trim(strip_tags($value));
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '/') && preg_match('#^/[A-Za-z0-9/_?&=.%#+:;,@~-]*$#', $value) === 1) {
            return $value;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
            return in_array($scheme, ['http', 'https'], true) ? $value : '';
        }

        return '';
    }

    public static function public_image_url(string $value): string
    {
        $value = trim(strip_tags($value));
        $value = str_replace('\\', '/', $value);
        $value = (string) preg_replace('/[\x00-\x1F\x7F]+/u', '', $value);
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, './')) {
            $value = substr($value, 2);
        }

        if (preg_match('#^media-file(?:\?|$)#i', $value) === 1) {
            $value = '/' . ltrim($value, '/');
        }

        if (preg_match('#^(?:uploads|images/importer|importer|media)(?:/|$)#i', $value) === 1) {
            $value = '/' . ltrim($value, '/');
        }

        $value = str_replace(' ', '%20', $value);
        if (str_starts_with($value, '/') && !str_starts_with($value, '//')) {
            $decodedPath = rawurldecode((string) (parse_url($value, PHP_URL_PATH) ?: $value));
            if (str_contains($decodedPath, '..') || preg_match('#[<>`"\']#', $decodedPath) === 1) {
                return '';
            }

            return $value;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
            return in_array($scheme, ['http', 'https'], true) ? $value : '';
        }

        return '';
    }

    private static function normalize_media_relative_path(string $path): string
    {
        $path = trim(str_replace('\\', '/', rawurldecode($path)), '/');
        $path = (string) preg_replace('#/+#', '/', $path);
        if ($path === '' || str_contains($path, '..') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $path) === 1) {
            return '';
        }

        if (str_starts_with($path, 'uploads/')) {
            $path = substr($path, strlen('uploads/'));
        }

        return $path;
    }

    /** @return array<int,string> */
    public static function normalize_domain_list(string $value): array
    {
        $parts = preg_split('/[\s,;]+/', $value) ?: [];
        $domains = [];
        foreach ($parts as $part) {
            $domain = self::normalize_host($part);
            if ($domain !== '' && !in_array($domain, $domains, true)) {
                $domains[] = $domain;
            }
        }

        return $domains;
    }

    public static function normalize_host(string $host): string
    {
        $host = trim(strtolower(strip_tags($host)));
        if ($host === '') {
            return '';
        }

        if (str_contains($host, '://')) {
            $parsedHost = parse_url($host, PHP_URL_HOST);
            $host = is_string($parsedHost) ? $parsedHost : '';
        }

        $host = preg_split('~[/?#]~', $host, 2)[0] ?? $host;

        $host = preg_replace('/:\d+$/', '', $host) ?? '';
        $host = trim($host, '.');
        $host = preg_replace('/^www\./', '', $host) ?? '';

        return preg_match('/^[a-z0-9.-]+$/', $host) === 1 ? $host : '';
    }

    public static function color(string $value, string $default): string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? strtolower($value) : $default;
    }

    public static function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = strtr($value, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: 'eintrag';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'eintrag';
    }

    public static function section(string $value): string
    {
        return in_array($value, ['matrix', 'areas', 'tools'], true) ? $value : 'tools';
    }

    /** @param array<string,mixed> $post */
    private function post_path(array $post): string
    {
        try {
            if (class_exists('CMS\\Services\\PermalinkService')) {
                return \CMS\Services\PermalinkService::getInstance()->buildPostPath($post);
            }
        } catch (\Throwable $e) {
            // Fallback unten verwenden.
        }

        $slug = self::slug((string) ($post['slug'] ?? ''));

        return '/blog/' . $slug;
    }

    private function resolve_prefix(object $db): string
    {
        $prefix = '';
        if (method_exists($db, 'getPrefix')) {
            $prefix = (string) $db->getPrefix();
        } elseif (method_exists($db, 'prefix')) {
            $prefix = (string) $db->prefix();
        }

        $prefix = trim($prefix);
        if ($prefix === '') {
            return 'cms_';
        }

        $prefix = (string) preg_replace('/[^A-Za-z0-9_]/', '', $prefix);

        return $prefix !== '' ? $prefix : 'cms_';
    }

    private static function log_exception(string $context, \Throwable $e): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CMS M365 Landing [' . $context . ']: ' . $e->getMessage());
        }
    }
}
