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
        $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM {$this->prefix}m365landing_settings");
        $stmt->execute();
        $settings = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $settings[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }

        return $settings;
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

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
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
        $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}m365landing_cards WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

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
        $stats = [];
        foreach (['matrix', 'areas', 'tools'] as $section) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->prefix}m365landing_cards WHERE section = ?");
            $stmt->execute([$section]);
            $stats[$section] = (int) $stmt->fetchColumn();
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->prefix}m365landing_cards WHERE is_active = 1");
        $stmt->execute();
        $stats['active_cards'] = (int) $stmt->fetchColumn();

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

    private function resolve_prefix(object $db): string
    {
        if (method_exists($db, 'getPrefix')) {
            return (string) $db->getPrefix();
        }

        if (method_exists($db, 'prefix')) {
            return (string) $db->prefix();
        }

        return 'cms_';
    }
}
