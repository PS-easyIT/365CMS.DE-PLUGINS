<?php
/**
 * CMS M365 Linkcollection – Repository.
 *
 * @package CMS_M365LINKCOLLECTION
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365LINKCOLLECTION_Repository
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
     * @return array<int,array<string,mixed>>
     */
    public function categories(bool $activeOnly = false): array
    {
        $db = $this->db();
        if ($db === null) {
            return [];
        }

        try {
            $table = self::quote_identifier($this->categories_table($db));
            $where = $activeOnly ? 'WHERE is_active = 1' : '';
            $stmt = $db->prepare("SELECT * FROM {$table} {$where} ORDER BY sort_order ASC, name ASC");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{items:array<int,array<string,mixed>>,total:int}
     */
    public function links(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $db = $this->db();
        if ($db === null) {
            return ['items' => [], 'total' => 0];
        }

        $linksTable = self::quote_identifier($this->links_table($db));
        $categoriesTable = self::quote_identifier($this->categories_table($db));
        $where = [];
        $params = [];

        if (!empty($filters['public'])) {
            $where[] = "l.status = 'active'";
            $where[] = 'c.is_active = 1';
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $where[] = 'c.slug = ?';
            $params[] = $category;
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(l.title LIKE ? OR l.subtitle LIKE ? OR l.description LIKE ? OR l.tags LIKE ? OR c.name LIKE ?)';
            $needle = '%' . $q . '%';
            array_push($params, $needle, $needle, $needle, $needle, $needle);
        }

        $featured = $filters['featured'] ?? null;
        if ($featured !== null) {
            $where[] = 'l.is_featured = ?';
            $params[] = (int) filter_var($featured, FILTER_VALIDATE_BOOLEAN);
        }

        $sqlWhere = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);

        try {
            $count = $db->prepare("SELECT COUNT(*) FROM {$linksTable} l INNER JOIN {$categoriesTable} c ON c.id = l.category_id {$sqlWhere}");
            $count->execute($params);
            $total = (int) $count->fetchColumn();

            $stmt = $db->prepare("SELECT l.*, c.name AS category_name, c.slug AS category_slug FROM {$linksTable} l INNER JOIN {$categoriesTable} c ON c.id = l.category_id {$sqlWhere} ORDER BY c.sort_order ASC, l.sort_order ASC, l.title ASC LIMIT {$limit} OFFSET {$offset}");
            $stmt->execute($params);
            $items = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            return ['items' => $this->attach_related_media($items), 'total' => $total];
        } catch (\Throwable $e) {
            return ['items' => [], 'total' => 0];
        }
    }

    /**
     * @return array<string,mixed>|null
     */
    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $db = $this->db();
        if ($db === null) {
            return null;
        }

        try {
            $table = self::quote_identifier($this->links_table($db));
            $stmt = $db->prepare("SELECT * FROM {$table} WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    public function save(array $data): int
    {
        $db = $this->db();
        if ($db === null) {
            return 0;
        }

        $id = (int) ($data['id'] ?? 0);
        $fields = $this->sanitize_link_data($data);
        $table = self::quote_identifier($this->links_table($db));

        if ($id > 0) {
            $sets = [];
            $params = [];
            foreach ($fields as $key => $value) {
                $sets[] = self::quote_identifier($key) . ' = ?';
                $params[] = $value;
            }
            $params[] = $id;
            $stmt = $db->prepare("UPDATE {$table} SET " . implode(', ', $sets) . ', updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $stmt->execute($params);
            return $id;
        }

        $columns = array_keys($fields);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $quotedColumns = implode(', ', array_map([self::class, 'quote_identifier'], $columns));
        $stmt = $db->prepare("INSERT INTO {$table} ({$quotedColumns}) VALUES ({$placeholders})");
        $stmt->execute(array_values($fields));
        return (int) $db->getPdo()->lastInsertId();
    }

    public function delete(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        $db = $this->db();
        if ($db === null) {
            return;
        }

        $table = self::quote_identifier($this->links_table($db));
        $stmt = $db->prepare("DELETE FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
    }

    /**
     * @return array<int,array{id:int,label:string}>
     */
    public function company_options(): array
    {
        if (!$this->is_plugin_active('cms-companies')) {
            return [];
        }

        $db = $this->db();
        if ($db === null) {
            return [];
        }

        try {
            $table = self::quote_identifier($this->prefix($db) . 'companies');
            $stmt = $db->prepare("SELECT id, name FROM {$table} WHERE status = 'active' ORDER BY name ASC LIMIT 300");
            $stmt->execute();
            return array_map(static fn(array $row): array => ['id' => (int) $row['id'], 'label' => (string) $row['name']], $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<int,array{id:int,label:string,slug:string}>
     */
    public function expert_options(): array
    {
        if (!$this->is_plugin_active('cms-experts')) {
            return [];
        }

        $db = $this->db();
        if ($db === null) {
            return [];
        }

        try {
            $table = self::quote_identifier($this->prefix($db) . 'experts');
            $stmt = $db->prepare("SELECT id, first_name, last_name FROM {$table} WHERE status = 'active' ORDER BY last_name ASC, first_name ASC LIMIT 300");
            $stmt->execute();
            $options = [];
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
                $id = (int) ($row['id'] ?? 0);
                $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
                $options[] = ['id' => $id, 'label' => $name, 'slug' => self::slugify($name) . '-' . $id];
            }
            return $options;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<int,array{id:int,label:string,slug:string}>
     */
    public function speaker_options(): array
    {
        if (!$this->is_plugin_active('cms-speakers')) {
            return [];
        }

        $db = $this->db();
        if ($db === null) {
            return [];
        }

        try {
            $table = self::quote_identifier($this->prefix($db) . 'speakers');
            $stmt = $db->prepare("SELECT id, first_name, last_name FROM {$table} WHERE status = 'active' ORDER BY last_name ASC, first_name ASC LIMIT 300");
            $stmt->execute();
            $options = [];
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
                $id = (int) ($row['id'] ?? 0);
                $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
                $options[] = ['id' => $id, 'label' => $name, 'slug' => self::slugify($name) . '-' . $id];
            }
            return $options;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param array<string,mixed> $link
     * @return array<int,array{label:string,url:string,type:string}>
     */
    public function related_buttons(array $link): array
    {
        $siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
        $buttons = [];

        if (CMS_M365LINKCOLLECTION_Settings::bool('show_company_buttons', true)
            && !empty($link['show_company_button'])
            && (int) ($link['company_id'] ?? 0) > 0
            && $this->is_plugin_active('cms-companies')) {
            $buttons[] = [
                'label' => CMS_M365LINKCOLLECTION_Settings::get('company_button_label', 'Company ansehen'),
                'url' => $siteUrl . '/companies/' . (int) $link['company_id'],
                'type' => 'company',
            ];
        }

        if (CMS_M365LINKCOLLECTION_Settings::bool('show_speaker_buttons', true)
            && !empty($link['show_speaker_button'])
            && (int) ($link['speaker_id'] ?? 0) > 0
            && $this->is_plugin_active('cms-speakers')) {
            $buttons[] = [
                'label' => CMS_M365LINKCOLLECTION_Settings::get('speaker_button_label', 'Speaker-Profil'),
                'url' => $siteUrl . '/speakers/' . $this->speaker_slug((int) $link['speaker_id']),
                'type' => 'speaker',
            ];
        }

        if (CMS_M365LINKCOLLECTION_Settings::bool('show_expert_buttons', true)
            && !empty($link['show_expert_button'])
            && (int) ($link['expert_id'] ?? 0) > 0
            && $this->is_plugin_active('cms-experts')) {
            $buttons[] = [
                'label' => CMS_M365LINKCOLLECTION_Settings::get('expert_button_label', 'Expert-Profil'),
                'url' => $siteUrl . '/experts/' . $this->expert_slug((int) $link['expert_id']),
                'type' => 'expert',
            ];
        }

        return $buttons;
    }

    public function expert_slug(int $expertId): string
    {
        $db = $this->db();
        if ($db === null || $expertId <= 0) {
            return (string) $expertId;
        }

        try {
            $table = self::quote_identifier($this->prefix($db) . 'experts');
            $stmt = $db->prepare("SELECT first_name, last_name FROM {$table} WHERE id = ? LIMIT 1");
            $stmt->execute([$expertId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return (string) $expertId;
            }
            $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
            return self::slugify($name) . '-' . $expertId;
        } catch (\Throwable $e) {
            return (string) $expertId;
        }
    }

    public function speaker_slug(int $speakerId): string
    {
        $db = $this->db();
        if ($db === null || $speakerId <= 0) {
            return (string) $speakerId;
        }

        try {
            $table = self::quote_identifier($this->prefix($db) . 'speakers');
            $stmt = $db->prepare("SELECT first_name, last_name FROM {$table} WHERE id = ? LIMIT 1");
            $stmt->execute([$speakerId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return (string) $speakerId;
            }
            $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
            return self::slugify($name) . '-' . $speakerId;
        } catch (\Throwable $e) {
            return (string) $speakerId;
        }
    }

    public static function slugify(string $value): string
    {
        $value = trim(strip_tags($value));
        $value = str_replace(['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß'], ['ae', 'oe', 'ue', 'ae', 'oe', 'ue', 'ss'], $value);
        $value = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $value));
        return trim($value, '-') ?: 'link';
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function sanitize_link_data(array $data): array
    {
        $categoryId = max(1, (int) ($data['category_id'] ?? 0));
        return [
            'category_id' => $categoryId,
            'title' => self::limit(strip_tags((string) ($data['title'] ?? '')), 190),
            'subtitle' => self::limit(strip_tags((string) ($data['subtitle'] ?? '')), 190),
            'url' => self::limit(filter_var((string) ($data['url'] ?? ''), FILTER_VALIDATE_URL) ? (string) $data['url'] : '', 500),
            'image_url' => self::limit((string) filter_var((string) ($data['image_url'] ?? ''), FILTER_SANITIZE_URL), 500),
            'image_alt' => self::limit(strip_tags((string) ($data['image_alt'] ?? '')), 190),
            'tags' => self::limit(strip_tags((string) ($data['tags'] ?? '')), 500),
            'company_id' => max(0, (int) ($data['company_id'] ?? 0)),
            'expert_id' => max(0, (int) ($data['expert_id'] ?? 0)),
            'speaker_id' => max(0, (int) ($data['speaker_id'] ?? 0)),
            'show_company_button' => !empty($data['show_company_button']) ? 1 : 0,
            'show_expert_button' => !empty($data['show_expert_button']) ? 1 : 0,
            'show_speaker_button' => !empty($data['show_speaker_button']) ? 1 : 0,
            'status' => in_array((string) ($data['status'] ?? 'active'), ['active', 'inactive'], true) ? (string) $data['status'] : 'active',
            'is_featured' => !empty($data['is_featured']) ? 1 : 0,
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return array<int,array<string,mixed>>
     */
    private function attach_related_media(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $speakerMedia = $this->load_speaker_media($this->collect_ids($items, 'speaker_id'));
        $expertMedia = $this->load_expert_media($this->collect_ids($items, 'expert_id'));
        $companyMedia = $this->load_company_media($this->collect_ids($items, 'company_id'));

        foreach ($items as &$item) {
            $resolved = $this->resolve_media_for_link($item, $speakerMedia, $expertMedia, $companyMedia);
            $item['resolved_image_url'] = $resolved['url'];
            $item['resolved_image_alt'] = $resolved['alt'];
            $item['resolved_image_source'] = $resolved['source'];
        }
        unset($item);

        return $items;
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return array<int,int>
     */
    private function collect_ids(array $items, string $key): array
    {
        $ids = [];
        foreach ($items as $item) {
            $id = (int) ($item[$key] ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        return array_values($ids);
    }

    /**
     * @param array<int,int> $ids
     * @return array<int,array{url:string,alt:string,source:string}>
     */
    private function load_speaker_media(array $ids): array
    {
        if ($ids === [] || !$this->is_plugin_active('cms-speakers')) {
            return [];
        }

        $db = $this->db();
        if ($db === null) {
            return [];
        }

        try {
            $table = self::quote_identifier($this->prefix($db) . 'speakers');
            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("SELECT id, first_name, last_name, photo_url FROM {$table} WHERE id IN ({$placeholders}) AND status = 'active'");
            $stmt->execute($ids);
            $media = [];
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
                $url = $this->public_media_url((string) ($row['photo_url'] ?? ''));
                if ($url === '') {
                    continue;
                }
                $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
                $media[(int) $row['id']] = ['url' => $url, 'alt' => $name, 'source' => 'speaker'];
            }
            return $media;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param array<int,int> $ids
     * @return array<int,array{url:string,alt:string,source:string}>
     */
    private function load_expert_media(array $ids): array
    {
        if ($ids === [] || !$this->is_plugin_active('cms-experts')) {
            return [];
        }

        $db = $this->db();
        if ($db === null) {
            return [];
        }

        try {
            $table = self::quote_identifier($this->prefix($db) . 'experts');
            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("SELECT id, first_name, last_name, photo_url FROM {$table} WHERE id IN ({$placeholders}) AND status = 'active'");
            $stmt->execute($ids);
            $media = [];
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
                $url = $this->public_media_url((string) ($row['photo_url'] ?? ''));
                if ($url === '') {
                    continue;
                }
                $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
                $media[(int) $row['id']] = ['url' => $url, 'alt' => $name, 'source' => 'expert'];
            }
            return $media;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param array<int,int> $ids
     * @return array<int,array{url:string,alt:string,source:string}>
     */
    private function load_company_media(array $ids): array
    {
        if ($ids === [] || !$this->is_plugin_active('cms-companies')) {
            return [];
        }

        $db = $this->db();
        if ($db === null) {
            return [];
        }

        try {
            $table = self::quote_identifier($this->prefix($db) . 'companies');
            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("SELECT id, name, logo_url FROM {$table} WHERE id IN ({$placeholders}) AND status = 'active'");
            $stmt->execute($ids);
            $media = [];
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
                $url = $this->public_media_url((string) ($row['logo_url'] ?? ''));
                if ($url === '') {
                    continue;
                }
                $media[(int) $row['id']] = ['url' => $url, 'alt' => (string) ($row['name'] ?? ''), 'source' => 'company'];
            }
            return $media;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param array<string,mixed> $item
     * @param array<int,array{url:string,alt:string,source:string}> $speakerMedia
     * @param array<int,array{url:string,alt:string,source:string}> $expertMedia
     * @param array<int,array{url:string,alt:string,source:string}> $companyMedia
     * @return array{url:string,alt:string,source:string}
     */
    private function resolve_media_for_link(array $item, array $speakerMedia, array $expertMedia, array $companyMedia): array
    {
        foreach ([
            ['id' => (int) ($item['speaker_id'] ?? 0), 'media' => $speakerMedia],
            ['id' => (int) ($item['expert_id'] ?? 0), 'media' => $expertMedia],
            ['id' => (int) ($item['company_id'] ?? 0), 'media' => $companyMedia],
        ] as $candidate) {
            $id = (int) $candidate['id'];
            $media = $candidate['media'];
            if ($id > 0 && isset($media[$id]) && $media[$id]['url'] !== '') {
                return $media[$id];
            }
        }

        $title = (string) ($item['title'] ?? '');
        $alt = trim((string) ($item['image_alt'] ?? ''));
        return [
            'url' => $this->public_media_url((string) ($item['image_url'] ?? '')),
            'alt' => $alt !== '' ? $alt : $title,
            'source' => 'entry',
        ];
    }

    private function public_media_url(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (function_exists('phinit_normalize_public_media_url')) {
            $siteUrl = defined('SITE_URL') ? (string) SITE_URL : '';
            $url = (string) phinit_normalize_public_media_url($url, false, $siteUrl);
        }

        if (str_starts_with($url, '/') || preg_match('#^(https?:)?//#i', $url) === 1) {
            return $url;
        }

        return '';
    }

    private function db(): ?\CMS\Database
    {
        return class_exists('CMS\\Database') ? \CMS\Database::instance() : null;
    }

    private function prefix(\CMS\Database $db): string
    {
        return method_exists($db, 'getPrefix') ? $db->getPrefix() : (method_exists($db, 'prefix') ? $db->prefix() : 'cms_');
    }

    public function links_table(\CMS\Database $db): string
    {
        return $this->prefix($db) . 'm365linkcollection_links';
    }

    public function categories_table(\CMS\Database $db): string
    {
        return $this->prefix($db) . 'm365linkcollection_categories';
    }

    private function is_plugin_active(string $slug): bool
    {
        if (!class_exists('CMS\\PluginManager')) {
            return false;
        }

        try {
            return \CMS\PluginManager::instance()->isPluginActive($slug);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function quote_identifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private static function limit(string $value, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }
}
