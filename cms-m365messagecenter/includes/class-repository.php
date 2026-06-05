<?php
/**
 * CMS M365 Message Center – Repository.
 *
 * @package CMS_M365MessageCenter
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365MessageCenter_Repository
{
    private static ?self $instance = null;
    private object $db;
    private string $prefix;

    /** @var array<string,string>|null */
    private ?array $settingsCache = null;

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
            $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM {$this->prefix}m365messagecenter_settings");
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
        $exists = $this->db->prepare("SELECT id FROM {$this->prefix}m365messagecenter_settings WHERE setting_key = ?");
        $insert = $this->db->prepare("INSERT INTO {$this->prefix}m365messagecenter_settings (setting_key, setting_value) VALUES (?, ?)");
        $update = $this->db->prepare("UPDATE {$this->prefix}m365messagecenter_settings SET setting_value = ? WHERE setting_key = ?");

        foreach ($settings as $key => $value) {
            $key = self::setting_key($key);
            if ($key === '') {
                continue;
            }
            $exists->execute([$key]);
            if ($exists->fetch()) {
                $update->execute([$value, $key]);
            } else {
                $insert->execute([$key, $value]);
            }
        }

        $this->settingsCache = null;
    }

    /** @param array<int,array<string,mixed>> $messages */
    public function replace_messages(array $messages): int
    {
        $this->db->prepare("DELETE FROM {$this->prefix}m365messagecenter_messages")->execute();
        $insert = $this->db->prepare("INSERT INTO {$this->prefix}m365messagecenter_messages
            (graph_id, title, category, severity, services_json, tags_json, is_major_change, action_required_at, start_at, end_at, last_modified_at, body_excerpt, body_content, external_url, raw_updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $count = 0;
        foreach ($messages as $message) {
            $graphId = self::text((string) ($message['id'] ?? ''), 120);
            $title = self::text((string) ($message['title'] ?? ''), 500);
            if ($graphId === '' || $title === '') {
                continue;
            }

            $services = self::string_list((array) ($message['services'] ?? []));
            $tags = self::string_list((array) ($message['tags'] ?? []));
            $details = is_array($message['details'] ?? null) ? $message['details'] : [];

            $insert->execute([
                $graphId,
                $title,
                self::text((string) ($message['category'] ?? ''), 120),
                self::text((string) ($message['severity'] ?? ''), 80),
                json_encode($services, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode($tags, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                !empty($message['isMajorChange']) ? 1 : 0,
                self::date_time((string) ($message['actionRequiredByDateTime'] ?? '')),
                self::date_time((string) ($message['startDateTime'] ?? '')),
                self::date_time((string) ($message['endDateTime'] ?? '')),
                self::date_time((string) ($message['lastModifiedDateTime'] ?? '')),
                self::excerpt_from_body($message['body']['content'] ?? ''),
                self::body_content($message['body']['content'] ?? ''),
                self::external_link($details),
                gmdate('Y-m-d H:i:s'),
            ]);
            $count++;
        }

        $this->save_settings([
            'last_fetch_at' => gmdate('Y-m-d H:i:s'),
            'last_fetch_count' => (string) $count,
            'last_fetch_error' => '',
        ]);

        return $count;
    }

    public function save_fetch_error(string $errorCode): void
    {
        $this->save_settings([
            'last_fetch_at' => gmdate('Y-m-d H:i:s'),
            'last_fetch_error' => self::text($errorCode, 80),
        ]);
    }

    /** @param array<string,mixed> $query @return array{items:array<int,array<string,mixed>>,total:int,page:int,per_page:int,pages:int,sort:string,direction:string,search:string,service:string,category:string} */
    public function public_messages(array $query): array
    {
        $perPage = max(6, min(60, (int) ($query['per_page'] ?? 24)));
        $page = max(1, (int) ($query['page'] ?? 1));
        $sort = self::public_sort((string) ($query['sort'] ?? 'last_modified'));
        $direction = strtolower((string) ($query['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = self::text((string) ($query['search'] ?? ''), 120);
        $service = self::text((string) ($query['service'] ?? ''), 120);
        $category = self::text((string) ($query['category'] ?? ''), 120);

        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(title LIKE ? OR body_excerpt LIKE ? OR graph_id LIKE ?)';
            $needle = '%' . $search . '%';
            $params[] = $needle;
            $params[] = $needle;
            $params[] = $needle;
        }
        if ($service !== '') {
            $where[] = 'services_json LIKE ?';
            $params[] = '%' . $service . '%';
        }
        if ($category !== '') {
            $where[] = 'category = ?';
            $params[] = $category;
        }

        $whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';
        $orderColumn = self::sort_column($sort);
        $offset = ($page - 1) * $perPage;

        $total = 0;
        $items = [];
        try {
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->prefix}m365messagecenter_messages{$whereSql}");
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}m365messagecenter_messages{$whereSql} ORDER BY {$orderColumn} {$direction}, id DESC LIMIT {$perPage} OFFSET {$offset}");
            $stmt->execute($params);
            $items = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            self::log_exception('public_messages_failed', $e);
        }

        foreach ($items as &$item) {
            $item['services'] = self::decode_list((string) ($item['services_json'] ?? ''));
            $item['tags'] = self::decode_list((string) ($item['tags_json'] ?? ''));
        }
        unset($item);

        $pages = max(1, (int) ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => $pages,
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
            'service' => $service,
            'category' => $category,
        ];
    }

    /** @return array<string,mixed>|null */
    public function public_message(string $graphId): ?array
    {
        $graphId = self::text(rawurldecode($graphId), 120);
        if ($graphId === '') {
            return null;
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}m365messagecenter_messages WHERE graph_id = ? LIMIT 1");
            $stmt->execute([$graphId]);
            $item = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($item)) {
                return null;
            }

            $item['services'] = self::decode_list((string) ($item['services_json'] ?? ''));
            $item['tags'] = self::decode_list((string) ($item['tags_json'] ?? ''));

            return $item;
        } catch (\Throwable $e) {
            self::log_exception('public_message_failed', $e);
            return null;
        }
    }

    /** @return array<int,string> */
    public function categories(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT DISTINCT category FROM {$this->prefix}m365messagecenter_messages WHERE category IS NOT NULL AND category <> '' ORDER BY category ASC");
            $stmt->execute();
            return array_values(array_filter(array_map('strval', $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [])));
        } catch (\Throwable $e) {
            self::log_exception('categories_failed', $e);
            return [];
        }
    }

    /** @return array<int,string> */
    public function services(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT services_json FROM {$this->prefix}m365messagecenter_messages WHERE services_json IS NOT NULL AND services_json <> ''");
            $stmt->execute();
            $services = [];
            foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [] as $json) {
                foreach (self::decode_list((string) $json) as $service) {
                    $services[$service] = $service;
                }
            }
            ksort($services, SORT_NATURAL | SORT_FLAG_CASE);
            return array_values($services);
        } catch (\Throwable $e) {
            self::log_exception('services_failed', $e);
            return [];
        }
    }

    public static function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]+/i', '-', $value) ?? '';
        return trim($value, '-_') ?: 'm365-messagecenter';
    }

    public static function text(string $value, int $max = 255): string
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? $value;
        $max = max(1, $max);

        return function_exists('mb_substr') ? mb_substr($value, 0, $max) : substr($value, 0, $max);
    }

    public static function public_sort(string $value): string
    {
        return in_array($value, ['last_modified', 'action_required', 'category', 'service', 'title'], true) ? $value : 'last_modified';
    }

    private static function sort_column(string $sort): string
    {
        return match ($sort) {
            'action_required' => 'COALESCE(action_required_at, last_modified_at, created_at)',
            'category' => 'category',
            'service' => 'services_json',
            'title' => 'title',
            default => 'COALESCE(last_modified_at, created_at)',
        };
    }

    private static function setting_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_]+/i', '', $value));
    }

    /** @param array<int,mixed> $values @return array<int,string> */
    private static function string_list(array $values): array
    {
        $list = [];
        foreach ($values as $value) {
            $text = self::text((string) $value, 160);
            if ($text !== '' && !in_array($text, $list, true)) {
                $list[] = $text;
            }
        }

        return $list;
    }

    /** @return array<int,string> */
    private static function decode_list(string $json): array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        return self::string_list($decoded);
    }

    private static function date_time(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    private static function excerpt_from_body(mixed $content): string
    {
        $text = html_entity_decode(strip_tags((string) $content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return self::text($text, 900);
    }

    private static function body_content(mixed $content): string
    {
        $text = html_entity_decode(strip_tags((string) $content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return self::text($text, 12000);
    }

    /** @param array<int,mixed> $details */
    private static function external_link(array $details): string
    {
        foreach ($details as $detail) {
            if (!is_array($detail)) {
                continue;
            }
            $name = strtolower(trim((string) ($detail['name'] ?? '')));
            $value = trim((string) ($detail['value'] ?? ''));
            if ($name !== 'externallink' || $value === '') {
                continue;
            }
            $parts = parse_url($value);
            $scheme = strtolower((string) ($parts['scheme'] ?? ''));
            if (!in_array($scheme, ['https', 'http'], true)) {
                continue;
            }
            return mb_substr($value, 0, 600);
        }

        return '';
    }

    private function resolve_prefix(object $db): string
    {
        if (method_exists($db, 'prefix')) {
            return (string) $db->prefix();
        }

        return defined('DB_PREFIX') ? (string) DB_PREFIX : 'cms_';
    }

    private static function log_exception(string $context, \Throwable $e): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CMS M365 Message Center repository [' . $context . ']: ' . $e->getMessage());
        }
    }
}
