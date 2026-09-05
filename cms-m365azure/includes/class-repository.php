<?php
/**
 * CMS M365 Azure – Repository.
 *
 * @package CMS_M365Azure
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365Azure_Repository
{
    private static ?self $instance = null;
    private object $db;
    private string $prefix;
    /** @var array<string,string>|null */
    private ?array $settingsCache = null;
    /** @var array<string,array<int,array<string,mixed>>> */
    private array $categoriesCache = [];
    /** @var array<string,array<int,array<string,mixed>>> */
    private array $servicesCache = [];

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
        if (is_array($this->settingsCache)) {
            return $this->settingsCache;
        }

        $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM {$this->prefix}m365azure_settings");
        $stmt->execute();
        $settings = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $settings[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }

        $this->settingsCache = $settings;

        return $settings;
    }

    /** @param array<string,string> $settings */
    public function save_settings(array $settings): void
    {
        $insert = $this->db->prepare("INSERT INTO {$this->prefix}m365azure_settings (setting_key, setting_value) VALUES (?, ?)");
        $update = $this->db->prepare("UPDATE {$this->prefix}m365azure_settings SET setting_value = ? WHERE setting_key = ?");
        $existingStmt = $this->db->prepare("SELECT setting_key FROM {$this->prefix}m365azure_settings");
        $existingStmt->execute();
        $existingKeys = [];
        foreach ($existingStmt->fetchAll(\PDO::FETCH_COLUMN) ?: [] as $key) {
            $existingKeys[(string) $key] = true;
        }

        foreach ($settings as $key => $value) {
            $settingKey = (string) $key;
            $settingValue = (string) $value;
            if (isset($existingKeys[$settingKey])) {
                $update->execute([$settingValue, $settingKey]);
            } else {
                $insert->execute([$settingKey, $settingValue]);
                $existingKeys[$settingKey] = true;
            }
        }

        $this->invalidate_caches();
    }

    /** @return array<int,array<string,mixed>> */
    public function categories(bool $activeOnly = false): array
    {
        $cacheKey = $activeOnly ? '1' : '0';
        if (isset($this->categoriesCache[$cacheKey])) {
            return $this->categoriesCache[$cacheKey];
        }

        $sql = "SELECT * FROM {$this->prefix}m365azure_categories";
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, title ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $this->categoriesCache[$cacheKey] = $result;

        return $result;
    }

    /** @return array<string,mixed>|null */
    public function category(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}m365azure_categories WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /** @param array<string,mixed> $data */
    public function save_category(array $data): int
    {
        $id = (int) ($data['id'] ?? 0);
        $title = self::text((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new \InvalidArgumentException('Kategorie-Titel darf nicht leer sein.');
        }

        $values = [
            'slug' => self::slug((string) ($data['slug'] ?? $title)),
            'title' => $title,
            'overline' => self::text((string) ($data['overline'] ?? '')),
            'intro' => self::long_text((string) ($data['intro'] ?? '')),
            'gallery_images' => self::gallery_images($data['gallery_images'] ?? []),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ];

        if ($id > 0) {
            $stmt = $this->db->prepare("UPDATE {$this->prefix}m365azure_categories SET slug = ?, title = ?, overline = ?, intro = ?, gallery_images = ?, sort_order = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$values['slug'], $values['title'], $values['overline'], $values['intro'], $values['gallery_images'], $values['sort_order'], $values['is_active'], $id]);
            $this->invalidate_caches();

            return $id;
        }

        $stmt = $this->db->prepare("INSERT INTO {$this->prefix}m365azure_categories (slug, title, overline, intro, gallery_images, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$values['slug'], $values['title'], $values['overline'], $values['intro'], $values['gallery_images'], $values['sort_order'], $values['is_active']]);
        $this->invalidate_caches();

        return (int) $this->db->lastInsertId();
    }

    public function delete_category(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->prefix}m365azure_categories WHERE id = ?");
        $stmt->execute([$id]);
        $this->invalidate_caches();
    }

    /** @return array<int,array<string,mixed>> */
    public function services(?int $categoryId = null, bool $activeOnly = false): array
    {
        $cacheKey = ($categoryId !== null ? (string) max(0, $categoryId) : 'all') . ':' . ($activeOnly ? '1' : '0');
        if (isset($this->servicesCache[$cacheKey])) {
            return $this->servicesCache[$cacheKey];
        }

        $where = [];
        $params = [];
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 's.category_id = ?';
            $params[] = $categoryId;
        }
        if ($activeOnly) {
            $where[] = 's.is_active = 1';
            $where[] = 'c.is_active = 1';
        }

        $sql = "SELECT s.*, c.title AS category_title, c.slug AS category_slug
                FROM {$this->prefix}m365azure_services s
                INNER JOIN {$this->prefix}m365azure_categories c ON c.id = s.category_id";
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY c.sort_order ASC, s.sort_order ASC, s.title ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $this->servicesCache[$cacheKey] = $result;

        return $result;
    }

    /** @return array<string,mixed>|null */
    public function service(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}m365azure_services WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /** @param array<string,mixed> $data */
    public function save_service(array $data): int
    {
        $id = (int) ($data['id'] ?? 0);
        $title = self::text((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new \InvalidArgumentException('Service-Titel darf nicht leer sein.');
        }
        $categoryId = max(1, (int) ($data['category_id'] ?? 0));
        if ($this->category($categoryId) === null) {
            throw new \InvalidArgumentException('Ungültige Kategorie für Service.');
        }

        $values = [
            'category_id' => $categoryId,
            'slug' => self::slug((string) ($data['slug'] ?? $title)),
            'title' => $title,
            'subtitle' => self::text((string) ($data['subtitle'] ?? '')),
            'image_url' => self::url((string) ($data['image_url'] ?? '')),
            'image_alt' => self::text((string) ($data['image_alt'] ?? '')),
            'summary' => self::long_text((string) ($data['summary'] ?? '')),
            'content' => self::long_text((string) ($data['content'] ?? '')),
            'features' => self::long_text((string) ($data['features'] ?? '')),
            'use_cases' => self::long_text((string) ($data['use_cases'] ?? '')),
            'docs_url' => self::url((string) ($data['docs_url'] ?? '')),
            'pricing_url' => self::url((string) ($data['pricing_url'] ?? '')),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ];

        if ($id > 0) {
            $stmt = $this->db->prepare("UPDATE {$this->prefix}m365azure_services
                SET category_id = ?, slug = ?, title = ?, subtitle = ?, image_url = ?, image_alt = ?, summary = ?, content = ?, features = ?, use_cases = ?, docs_url = ?, pricing_url = ?, sort_order = ?, is_active = ?
                WHERE id = ?");
            $stmt->execute([$values['category_id'], $values['slug'], $values['title'], $values['subtitle'], $values['image_url'], $values['image_alt'], $values['summary'], $values['content'], $values['features'], $values['use_cases'], $values['docs_url'], $values['pricing_url'], $values['sort_order'], $values['is_active'], $id]);
            $this->invalidate_caches();

            return $id;
        }

        $stmt = $this->db->prepare("INSERT INTO {$this->prefix}m365azure_services (category_id, slug, title, subtitle, image_url, image_alt, summary, content, features, use_cases, docs_url, pricing_url, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$values['category_id'], $values['slug'], $values['title'], $values['subtitle'], $values['image_url'], $values['image_alt'], $values['summary'], $values['content'], $values['features'], $values['use_cases'], $values['docs_url'], $values['pricing_url'], $values['sort_order'], $values['is_active']]);
        $this->invalidate_caches();

        return (int) $this->db->lastInsertId();
    }

    public function delete_service(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->prefix}m365azure_services WHERE id = ?");
        $stmt->execute([$id]);
        $this->invalidate_caches();
    }

    /** @return array<string,int> */
    public function stats(): array
    {
        $stats = [];
        foreach (['categories' => 'm365azure_categories', 'services' => 'm365azure_services'] as $key => $table) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->prefix}{$table}");
            $stmt->execute();
            $stats[$key] = (int) $stmt->fetchColumn();
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->prefix}m365azure_services WHERE is_active = 1");
        $stmt->execute();
        $stats['active_services'] = (int) $stmt->fetchColumn();

        return $stats;
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
        return str_replace(["\\r\\n", "\\n", "\\r"], ["\n", "\n", "\n"], $value);
    }

    public static function url(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return '';
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $value : '';
    }

    public static function public_url(string $value): string
    {
        $value = trim(strip_tags($value));
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '/') && preg_match('#^/[A-Za-z0-9/_?&=.%\#+:;,@~-]*$#', $value) === 1) {
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

        if (preg_match('#^(?:uploads|media)(?:/|$)#i', $value) === 1 || preg_match('#^media-file(?:\?|$)#i', $value) === 1) {
            $value = '/' . ltrim($value, '/');
        }

        $value = str_replace(' ', '%20', $value);
        if (str_starts_with($value, '/') && !str_starts_with($value, '//') && !str_contains($value, '..')) {
            return $value;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
            return in_array($scheme, ['http', 'https'], true) ? $value : '';
        }

        return '';
    }

    public static function gallery_images(mixed $value): string
    {
        $items = self::gallery_images_list($value);
        if ($items === []) {
            return '';
        }

        return (string) json_encode($items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** @return array<int,string> */
    public static function gallery_images_list(mixed $value, int $max = 6): array
    {
        $rawItems = [];
        if (is_array($value)) {
            $rawItems = $value;
        } elseif (is_string($value)) {
            $value = trim($value);
            if ($value !== '') {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $rawItems = $decoded;
                } else {
                    $rawItems = preg_split('/\R+/', $value) ?: [];
                }
            }
        }

        $images = [];
        foreach ($rawItems as $item) {
            if (is_array($item)) {
                $item = (string) ($item['url'] ?? $item['src'] ?? '');
            }

            $url = self::public_image_url((string) $item);
            if ($url === '' || in_array($url, $images, true)) {
                continue;
            }

            $images[] = $url;
            if (count($images) >= max(1, $max)) {
                break;
            }
        }

        return $images;
    }

    public static function color(string $value, string $default): string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? strtolower($value) : $default;
    }

    public static function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $map = ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss'];
        $value = strtr($value, $map);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: 'eintrag';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'eintrag';
    }

    private function resolve_prefix(object $db): string
    {
        $prefix = '';
        if (method_exists($db, 'getPrefix')) {
            $prefix = (string) $db->getPrefix();
        } elseif (method_exists($db, 'prefix')) {
            $prefix = (string) $db->prefix();
        }

        if ($prefix !== '' && preg_match('/^[A-Za-z0-9_]+$/', $prefix) === 1) {
            return $prefix;
        }

        $this->log_error('Invalid DB prefix detected, fallback to cms_.');

        return 'cms_';
    }

    private function invalidate_caches(): void
    {
        $this->settingsCache = null;
        $this->categoriesCache = [];
        $this->servicesCache = [];
    }

    private function log_error(string $message): void
    {
        error_log('[cms-m365azure] repository :: ' . $message);
    }
}
