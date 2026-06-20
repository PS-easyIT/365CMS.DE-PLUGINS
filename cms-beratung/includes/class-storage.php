<?php
/**
 * CMS Beratung – data access for landingpages, presets and submissions.
 *
 * @package CMS_Beratung
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Beratung_Storage
{
    private static ?self $instance = null;
    private ?\PDO $pdo = null;
    private string $prefix = 'cms_';

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        if (class_exists('CMS\\Database')) {
            $db = \CMS\Database::instance();
            $this->pdo = $db->getPdo();
            $this->prefix = method_exists($db, 'getPrefix') ? $db->getPrefix() : (method_exists($db, 'prefix') ? $db->prefix() : 'cms_');
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function all_landingpages(): array
    {
        if ($this->pdo === null) {
            return [];
        }

        $stmt = $this->pdo->prepare("SELECT * FROM {$this->prefix}beratung_landingpages ORDER BY updated_at DESC, id DESC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function get_landingpage(int $id): ?array
    {
        if ($this->pdo === null || $id <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM {$this->prefix}beratung_landingpages WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $this->decode_landingpage($row) : null;
    }

    public function get_landingpage_by_slug(string $slug, bool $publicOnly = true): ?array
    {
        if ($this->pdo === null || $slug === '') {
            return null;
        }

        $sql = "SELECT * FROM {$this->prefix}beratung_landingpages WHERE slug = ?";
        $params = [$slug];
        if ($publicOnly) {
            $sql .= " AND status = 'published'";
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $this->decode_landingpage($row) : null;
    }

    /** @param array<string,mixed> $data */
    public function save_landingpage(array $data): int
    {
        if ($this->pdo === null) {
            return 0;
        }

        CMS_Beratung_Installer::ensure_for_admin_save();
        $id = (int) ($data['id'] ?? 0);
        $payload = CMS_Beratung_Import_Export::sanitize_landingpage_payload($data);
        if ($payload['slug'] === '') {
            $payload['slug'] = CMS_Beratung_Settings::slug($payload['public_title'] ?: $payload['internal_title'], 'beratung');
        }
        $payload['slug'] = $this->unique_slug((string) $payload['slug'], $id);

        $columns = [
            'tenant_id', 'internal_title', 'public_title', 'slug', 'meta_title', 'meta_description', 'focus_keyword', 'status', 'template',
            'max_content_width', 'custom_design_enabled', 'use_global_settings', 'show_header', 'show_footer', 'show_breadcrumb', 'show_toc',
            'show_anchor_nav', 'noindex', 'nofollow', 'canonical_url', 'custom_css_class', 'hero_json', 'design_json', 'sections_json', 'created_by', 'updated_by',
        ];

        if ($id > 0) {
            $assignments = implode(', ', array_map(static fn(string $col): string => $col . ' = ?', array_filter($columns, static fn(string $col): bool => $col !== 'created_by')));
            $values = [];
            foreach (array_filter($columns, static fn(string $col): bool => $col !== 'created_by') as $column) {
                $values[] = $payload[$column] ?? null;
            }
            $values[] = $id;
            $stmt = $this->pdo->prepare("UPDATE {$this->prefix}beratung_landingpages SET {$assignments} WHERE id = ?");
            $stmt->execute($values);
            return $id;
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $stmt = $this->pdo->prepare("INSERT INTO {$this->prefix}beratung_landingpages (" . implode(', ', $columns) . ") VALUES ({$placeholders})");
        $values = [];
        foreach ($columns as $column) {
            $values[] = $payload[$column] ?? null;
        }
        $stmt->execute($values);
        return (int) $this->pdo->lastInsertId();
    }

    public function update_status(int $id, string $status): bool
    {
        if ($this->pdo === null || !array_key_exists($status, CMS_Beratung_Settings::statuses())) {
            return false;
        }
        $stmt = $this->pdo->prepare("UPDATE {$this->prefix}beratung_landingpages SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function duplicate_landingpage(int $id): int
    {
        $page = $this->get_landingpage($id);
        if ($page === null) {
            return 0;
        }
        unset($page['id'], $page['created_at'], $page['updated_at']);
        $page['internal_title'] = (string) $page['internal_title'] . ' Kopie';
        $page['public_title'] = (string) $page['public_title'] . ' Kopie';
        $page['slug'] = (string) $page['slug'] . '-kopie';
        $page['status'] = 'draft';
        return $this->save_landingpage($page);
    }

    public function delete_landingpage(int $id): bool
    {
        if ($this->pdo === null || $id <= 0) {
            return false;
        }
        $stmt = $this->pdo->prepare("DELETE FROM {$this->prefix}beratung_landingpages WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** @return array<int,array<string,mixed>> */
    public function all_presets(): array
    {
        if ($this->pdo === null) {
            return [];
        }
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->prefix}beratung_design_presets ORDER BY is_system DESC, name ASC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /** @param array<string,mixed> $preset */
    public function create_preset_if_missing(array $preset): void
    {
        if ($this->pdo === null) {
            return;
        }
        $slug = CMS_Beratung_Settings::slug((string) ($preset['slug'] ?? ''), 'preset');
        $exists = $this->pdo->prepare("SELECT id FROM {$this->prefix}beratung_design_presets WHERE tenant_id IS NULL AND slug = ? LIMIT 1");
        $exists->execute([$slug]);
        if ($exists->fetch()) {
            return;
        }
        $stmt = $this->pdo->prepare("INSERT INTO {$this->prefix}beratung_design_presets (tenant_id, name, slug, description, design_json, is_system) VALUES (NULL, ?, ?, ?, ?, ?)");
        $stmt->execute([
            CMS_Beratung_Settings::text((string) ($preset['name'] ?? $slug)),
            $slug,
            CMS_Beratung_Settings::text((string) ($preset['description'] ?? '')),
            (string) ($preset['design_json'] ?? '{}'),
            (int) ($preset['is_system'] ?? 0),
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    public function submissions(int $limit = 100): array
    {
        if ($this->pdo === null) {
            return [];
        }
        $limit = max(1, min(500, $limit));
        $stmt = $this->pdo->prepare("SELECT s.*, l.public_title AS landingpage_title, l.slug AS landingpage_slug FROM {$this->prefix}beratung_form_submissions s LEFT JOIN {$this->prefix}beratung_landingpages l ON l.id = s.landingpage_id ORDER BY s.created_at DESC LIMIT {$limit}");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /** @param array<string,mixed> $data */
    public function create_submission(array $data): int
    {
        if ($this->pdo === null) {
            return 0;
        }
        $stmt = $this->pdo->prepare("INSERT INTO {$this->prefix}beratung_form_submissions (landingpage_id, tenant_id, sender_name, sender_email, phone, company, topic, message, consent, copy_to_sender, payload_json, ip_address, user_agent, status, is_spam) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'unread', ?)");
        $stmt->execute([
            (int) ($data['landingpage_id'] ?? 0) ?: null,
            CMS_Beratung_Settings::text((string) ($data['sender_name'] ?? '')),
            filter_var((string) ($data['sender_email'] ?? ''), FILTER_VALIDATE_EMAIL) ? (string) $data['sender_email'] : null,
            CMS_Beratung_Settings::text((string) ($data['phone'] ?? '')),
            CMS_Beratung_Settings::text((string) ($data['company'] ?? '')),
            CMS_Beratung_Settings::text((string) ($data['topic'] ?? '')),
            CMS_Beratung_Settings::text((string) ($data['message'] ?? '')),
            !empty($data['consent']) ? 1 : 0,
            !empty($data['copy_to_sender']) ? 1 : 0,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
            !empty($data['is_spam']) ? 1 : 0,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function decode_landingpage(array $row): array
    {
        foreach (['hero_json', 'design_json', 'sections_json'] as $key) {
            $decoded = json_decode((string) ($row[$key] ?? ''), true);
            $row[str_replace('_json', '', $key)] = is_array($decoded) ? $decoded : [];
        }
        return $row;
    }

    private function unique_slug(string $slug, int $ignoreId = 0): string
    {
        if ($this->pdo === null) {
            return $slug;
        }
        $base = CMS_Beratung_Settings::slug($slug, 'beratung');
        $candidate = $base;
        $i = 2;
        while (true) {
            $stmt = $this->pdo->prepare("SELECT id FROM {$this->prefix}beratung_landingpages WHERE slug = ? AND id <> ? LIMIT 1");
            $stmt->execute([$candidate, $ignoreId]);
            if (!$stmt->fetch()) {
                return $candidate;
            }
            $candidate = $base . '-' . $i++;
        }
    }
}
