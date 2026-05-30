<?php
/**
 * CMS M365 Adminsites – Repository.
 *
 * @package CMS_M365ADMINSITES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365ADMINSITES_Repository
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
    public function sites(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $db = $this->db();
        if ($db === null) {
            return ['items' => [], 'total' => 0];
        }

        $sitesTable = self::quote_identifier($this->sites_table($db));
        $categoriesTable = self::quote_identifier($this->categories_table($db));
        $where = [];
        $params = [];

        if (!empty($filters['public'])) {
            $where[] = "s.status = 'active'";
            $where[] = 'c.is_active = 1';
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $where[] = 'c.slug = ?';
            $params[] = $category;
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(s.title LIKE ? OR s.subtitle LIKE ? OR s.description LIKE ? OR s.tags LIKE ? OR c.name LIKE ?)';
            $needle = '%' . $q . '%';
            array_push($params, $needle, $needle, $needle, $needle, $needle);
        }

        $featured = $filters['featured'] ?? null;
        if ($featured !== null) {
            $where[] = 's.is_featured = ?';
            $params[] = (int) filter_var($featured, FILTER_VALIDATE_BOOLEAN);
        }

        $sqlWhere = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);

        try {
            $count = $db->prepare("SELECT COUNT(*) FROM {$sitesTable} s INNER JOIN {$categoriesTable} c ON c.id = s.category_id {$sqlWhere}");
            $count->execute($params);
            $total = (int) $count->fetchColumn();

            $stmt = $db->prepare("SELECT s.*, c.name AS category_name, c.slug AS category_slug FROM {$sitesTable} s INNER JOIN {$categoriesTable} c ON c.id = s.category_id {$sqlWhere} ORDER BY c.sort_order ASC, s.sort_order ASC, s.title ASC LIMIT {$limit} OFFSET {$offset}");
            $stmt->execute($params);
            return ['items' => $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [], 'total' => $total];
        } catch (\Throwable $e) {
            return ['items' => [], 'total' => 0];
        }
    }

    /**
     * Compatibility alias for widget/template code shaped like linkcollection.
     *
     * @param array<string,mixed> $filters
     * @return array{items:array<int,array<string,mixed>>,total:int}
     */
    public function links(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        return $this->sites($filters, $limit, $offset);
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
            $table = self::quote_identifier($this->sites_table($db));
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
        $fields = $this->sanitize_site_data($data);
        $table = self::quote_identifier($this->sites_table($db));

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

        $table = self::quote_identifier($this->sites_table($db));
        $stmt = $db->prepare("DELETE FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function slugify(string $value): string
    {
        $value = trim(strip_tags($value));
        $value = str_replace(['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß'], ['ae', 'oe', 'ue', 'ae', 'oe', 'ue', 'ss'], $value);
        $value = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $value));
        return trim($value, '-') ?: 'site';
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function sanitize_site_data(array $data): array
    {
        return [
            'category_id' => max(1, (int) ($data['category_id'] ?? 0)),
            'title' => self::limit(strip_tags((string) ($data['title'] ?? '')), 190),
            'subtitle' => self::limit(strip_tags((string) ($data['subtitle'] ?? '')), 190),
            'url' => self::limit(filter_var((string) ($data['url'] ?? ''), FILTER_VALIDATE_URL) ? (string) $data['url'] : '', 500),
            'image_url' => self::limit((string) filter_var((string) ($data['image_url'] ?? ''), FILTER_SANITIZE_URL), 500),
            'image_alt' => self::limit(strip_tags((string) ($data['image_alt'] ?? '')), 190),
            'tags' => self::limit(strip_tags((string) ($data['tags'] ?? '')), 500),
            'status' => in_array((string) ($data['status'] ?? 'active'), ['active', 'inactive'], true) ? (string) $data['status'] : 'active',
            'is_featured' => !empty($data['is_featured']) ? 1 : 0,
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
        ];
    }

    public function public_media_url(string $url): string
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

    public function sites_table(\CMS\Database $db): string
    {
        return $this->prefix($db) . 'm365adminsites_sites';
    }

    public function categories_table(\CMS\Database $db): string
    {
        return $this->prefix($db) . 'm365adminsites_categories';
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
