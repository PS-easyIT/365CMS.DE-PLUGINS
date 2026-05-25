<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Marketplace_Repository
{
    private \CMS\Database $db;
    private string $table;

    public function __construct()
    {
        $this->db = \CMS\Database::instance();
        $this->table = $this->db->getPrefix() . 'marketplace_items';
    }

    public function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `type` VARCHAR(20) NOT NULL,
            `slug` VARCHAR(120) NOT NULL,
            `name` VARCHAR(190) NOT NULL,
            `version` VARCHAR(50) NOT NULL,
            `author` VARCHAR(190) NOT NULL DEFAULT '',
            `description` TEXT DEFAULT NULL,
            `category` VARCHAR(120) NOT NULL DEFAULT '',
            `package_file_name` VARCHAR(190) NOT NULL DEFAULT '',
            `package_storage_path` VARCHAR(255) NOT NULL DEFAULT '',
            `package_sha256` CHAR(64) NOT NULL DEFAULT '',
            `package_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
            `homepage_url` VARCHAR(255) NOT NULL DEFAULT '',
            `docs_url` VARCHAR(255) NOT NULL DEFAULT '',
            `changelog_url` VARCHAR(255) NOT NULL DEFAULT '',
            `icon_url` VARCHAR(255) NOT NULL DEFAULT '',
            `screenshot_url` VARCHAR(255) NOT NULL DEFAULT '',
            `requires_cms` VARCHAR(30) NOT NULL DEFAULT '',
            `requires_php` VARCHAR(30) NOT NULL DEFAULT '',
            `tested_up_to` VARCHAR(30) NOT NULL DEFAULT '',
            `notes` LONGTEXT DEFAULT NULL,
            `released_on` DATE DEFAULT NULL,
            `is_paid` TINYINT(1) NOT NULL DEFAULT 0,
            `price_amount` DECIMAL(10,2) DEFAULT NULL,
            `price_currency` VARCHAR(10) NOT NULL DEFAULT 'EUR',
            `contact_form_slug` VARCHAR(255) NOT NULL DEFAULT '',
            `submission_source` VARCHAR(20) NOT NULL DEFAULT 'admin',
            `submitter_name` VARCHAR(190) NOT NULL DEFAULT '',
            `submitter_email` VARCHAR(190) NOT NULL DEFAULT '',
            `is_published` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            `published_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_type_slug_version` (`type`, `slug`, `version`),
            KEY `idx_type_published` (`type`, `is_published`),
            KEY `idx_slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
        $this->ensureColumnExists('is_paid', "TINYINT(1) NOT NULL DEFAULT 0");
        $this->ensureColumnExists('price_amount', "DECIMAL(10,2) DEFAULT NULL");
        $this->ensureColumnExists('price_currency', "VARCHAR(10) NOT NULL DEFAULT 'EUR'");
        $this->ensureColumnExists('contact_form_slug', "VARCHAR(255) NOT NULL DEFAULT ''");
        $this->ensureColumnExists('submission_source', "VARCHAR(20) NOT NULL DEFAULT 'admin'");
        $this->ensureColumnExists('submitter_name', "VARCHAR(190) NOT NULL DEFAULT ''");
        $this->ensureColumnExists('submitter_email', "VARCHAR(190) NOT NULL DEFAULT ''");
    }

    public function getAll(?string $type = null): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        $params = [];

        if ($type !== null && $type !== '') {
            $sql .= ' WHERE type = ?';
            $params[] = $type;
        }

        $sql .= ' ORDER BY updated_at DESC, id DESC';

        return array_map([$this, 'mapRow'], $this->db->get_results($sql, $params));
    }

    public function getPublishedByType(string $type): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE type = ? AND is_published = 1 ORDER BY slug ASC, version DESC, id DESC";
        return array_map([$this, 'mapRow'], $this->db->get_results($sql, [$type]));
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->get_row("SELECT * FROM `{$this->table}` WHERE id = ? LIMIT 1", [$id]);
        return $row ? $this->mapRow($row) : null;
    }

    public function findByTypeSlugVersion(string $type, string $slug, string $version): ?array
    {
        $row = $this->db->get_row(
            "SELECT * FROM `{$this->table}` WHERE type = ? AND slug = ? AND version = ? LIMIT 1",
            [$type, $slug, $version]
        );

        return $row ? $this->mapRow($row) : null;
    }

    public function save(array $data, ?int $id = null): int|false
    {
        $payload = [
            'type' => (string) ($data['type'] ?? ''),
            'slug' => (string) ($data['slug'] ?? ''),
            'name' => (string) ($data['name'] ?? ''),
            'version' => (string) ($data['version'] ?? ''),
            'author' => (string) ($data['author'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'category' => (string) ($data['category'] ?? ''),
            'package_file_name' => (string) ($data['package_file_name'] ?? ''),
            'package_storage_path' => (string) ($data['package_storage_path'] ?? ''),
            'package_sha256' => (string) ($data['package_sha256'] ?? ''),
            'package_size' => (int) ($data['package_size'] ?? 0),
            'homepage_url' => (string) ($data['homepage_url'] ?? ''),
            'docs_url' => (string) ($data['docs_url'] ?? ''),
            'changelog_url' => (string) ($data['changelog_url'] ?? ''),
            'icon_url' => (string) ($data['icon_url'] ?? ''),
            'screenshot_url' => (string) ($data['screenshot_url'] ?? ''),
            'requires_cms' => (string) ($data['requires_cms'] ?? ''),
            'requires_php' => (string) ($data['requires_php'] ?? ''),
            'tested_up_to' => (string) ($data['tested_up_to'] ?? ''),
            'notes' => (string) ($data['notes'] ?? ''),
            'released_on' => $this->normalizeDate($data['released_on'] ?? null),
            'is_paid' => !empty($data['is_paid']) ? 1 : 0,
            'price_amount' => $this->normalizeDecimal($data['price_amount'] ?? null),
            'price_currency' => $this->normalizeCurrency((string) ($data['price_currency'] ?? 'EUR')),
            'contact_form_slug' => trim((string) ($data['contact_form_slug'] ?? '')),
            'submission_source' => $this->normalizeSubmissionSource((string) ($data['submission_source'] ?? 'admin')),
            'submitter_name' => trim((string) ($data['submitter_name'] ?? '')),
            'submitter_email' => $this->normalizeEmail((string) ($data['submitter_email'] ?? '')),
            'is_published' => !empty($data['is_published']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
            'published_at' => !empty($data['is_published']) ? (string) ($data['published_at'] ?? date('Y-m-d H:i:s')) : null,
        ];

        if ($id === null) {
            $payload['created_at'] = date('Y-m-d H:i:s');
            return $this->db->insert('marketplace_items', $payload);
        }

        $existing = $this->findById($id);
        if ($existing === null) {
            return false;
        }

        if (!empty($existing['is_published']) && empty($payload['is_published'])) {
            $payload['published_at'] = null;
        }

        if (!empty($payload['is_published']) && empty($existing['published_at'])) {
            $payload['published_at'] = date('Y-m-d H:i:s');
        }

        $updated = $this->db->update('marketplace_items', $payload, ['id' => $id]);
        return $updated ? $id : false;
    }

    public function setPublished(int $id, bool $published): bool
    {
        $data = [
            'is_published' => $published ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
            'published_at' => $published ? date('Y-m-d H:i:s') : null,
        ];

        return $this->db->update('marketplace_items', $data, ['id' => $id]);
    }

    private function mapRow(object $row): array
    {
        return [
            'id' => (int) ($row->id ?? 0),
            'type' => (string) ($row->type ?? ''),
            'slug' => (string) ($row->slug ?? ''),
            'name' => (string) ($row->name ?? ''),
            'version' => (string) ($row->version ?? ''),
            'author' => (string) ($row->author ?? ''),
            'description' => (string) ($row->description ?? ''),
            'category' => (string) ($row->category ?? ''),
            'package_file_name' => (string) ($row->package_file_name ?? ''),
            'package_storage_path' => (string) ($row->package_storage_path ?? ''),
            'package_sha256' => (string) ($row->package_sha256 ?? ''),
            'package_size' => (int) ($row->package_size ?? 0),
            'homepage_url' => (string) ($row->homepage_url ?? ''),
            'docs_url' => (string) ($row->docs_url ?? ''),
            'changelog_url' => (string) ($row->changelog_url ?? ''),
            'icon_url' => (string) ($row->icon_url ?? ''),
            'screenshot_url' => (string) ($row->screenshot_url ?? ''),
            'requires_cms' => (string) ($row->requires_cms ?? ''),
            'requires_php' => (string) ($row->requires_php ?? ''),
            'tested_up_to' => (string) ($row->tested_up_to ?? ''),
            'notes' => (string) ($row->notes ?? ''),
            'released_on' => (string) ($row->released_on ?? ''),
            'is_paid' => (int) ($row->is_paid ?? 0),
            'price_amount' => isset($row->price_amount) && $row->price_amount !== null ? (string) $row->price_amount : null,
            'price_currency' => (string) ($row->price_currency ?? 'EUR'),
            'contact_form_slug' => (string) ($row->contact_form_slug ?? ''),
            'submission_source' => (string) ($row->submission_source ?? 'admin'),
            'submitter_name' => (string) ($row->submitter_name ?? ''),
            'submitter_email' => (string) ($row->submitter_email ?? ''),
            'is_published' => (int) ($row->is_published ?? 0),
            'created_at' => (string) ($row->created_at ?? ''),
            'updated_at' => (string) ($row->updated_at ?? ''),
            'published_at' => (string) ($row->published_at ?? ''),
        ];
    }

    private function ensureColumnExists(string $column, string $definition): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            throw new \InvalidArgumentException('Invalid marketplace column name.');
        }

        $existing = $this->db->get_row(
            'SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
             LIMIT 1',
            [$this->table, $column]
        );
        if ($existing !== null) {
            return;
        }

        $this->db->query("ALTER TABLE `{$this->table}` ADD COLUMN `{$column}` {$definition}");
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    private function normalizeDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = str_replace(',', '.', trim((string) $value));
        if (!is_numeric($value)) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function normalizeCurrency(string $value): string
    {
        $value = strtoupper(trim($value));
        if ($value === '') {
            return 'EUR';
        }

        return preg_replace('/[^A-Z]/', '', $value) ?: 'EUR';
    }

    private function normalizeSubmissionSource(string $value): string
    {
        $value = strtolower(trim($value));
        return in_array($value, ['admin', 'public'], true) ? $value : 'admin';
    }

    private function normalizeEmail(string $value): string
    {
        $value = strtolower(trim($value));
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : '';
    }
}
