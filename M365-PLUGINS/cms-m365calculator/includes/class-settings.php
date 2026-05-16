<?php
/**
 * CMS M365 Calculator – module settings.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Settings
{
    /**
     * @param array<string,array<string,mixed>> $tools
     * @return array<string,array<string,mixed>>
     */
    public static function apply_to_tools(array $tools): array
    {
        $settings = self::module_settings();

        foreach ($tools as $key => $tool) {
            $moduleKey = (string) ($tool['key'] ?? $key);
            $moduleSettings = $settings[$moduleKey] ?? [];

            if (isset($moduleSettings['is_enabled']) && (int) $moduleSettings['is_enabled'] === 0) {
                unset($tools[$key]);
                continue;
            }

            if (!empty($moduleSettings['status_override'])) {
                $tools[$key]['status'] = (string) $moduleSettings['status_override'];
            }

            if (isset($moduleSettings['priority_override']) && $moduleSettings['priority_override'] !== null) {
                $tools[$key]['priority'] = max(0, min(1000, (int) $moduleSettings['priority_override']));
            }

            if (!empty($moduleSettings['title_override'])) {
                $tools[$key]['title'] = self::limit_text((string) $moduleSettings['title_override'], 90);
            }

            if (!empty($moduleSettings['description_override'])) {
                $tools[$key]['description'] = self::limit_text((string) $moduleSettings['description_override'], 140);
            }
        }

        return $tools;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function module_settings(): array
    {
        if (!class_exists('CMS\\Database')) {
            return [];
        }

        try {
            $db = \CMS\Database::instance();
            $table = self::table_name($db);
            $stmt = $db->getPdo()->query("SELECT module_key, is_enabled, status_override, priority_override, title_override, description_override FROM {$table}");
            $rows = $stmt !== false ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
            $settings = [];

            foreach ($rows as $row) {
                $key = self::clean_key((string) ($row['module_key'] ?? ''));
                if ($key === '') {
                    continue;
                }

                $settings[$key] = [
                    'module_key' => $key,
                    'is_enabled' => (int) ($row['is_enabled'] ?? 1),
                    'status_override' => self::normalize_status((string) ($row['status_override'] ?? '')),
                    'priority_override' => $row['priority_override'] !== null ? (int) $row['priority_override'] : null,
                    'title_override' => trim(strip_tags((string) ($row['title_override'] ?? ''))),
                    'description_override' => trim(strip_tags((string) ($row['description_override'] ?? ''))),
                ];
            }

            return $settings;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param array<string,array<string,mixed>> $tools
     * @return array<string,array<string,mixed>>
     */
    public static function settings_for_admin(array $tools): array
    {
        $stored = self::module_settings();
        $settings = [];

        foreach ($tools as $key => $tool) {
            $moduleKey = self::clean_key((string) ($tool['key'] ?? $key));
            if ($moduleKey === '') {
                continue;
            }

            $row = $stored[$moduleKey] ?? [];
            $settings[$moduleKey] = [
                'module_key' => $moduleKey,
                'is_enabled' => array_key_exists('is_enabled', $row) ? (int) $row['is_enabled'] : 1,
                'status_override' => (string) ($row['status_override'] ?? ($tool['status'] ?? 'live')),
                'priority_override' => (int) ($row['priority_override'] ?? ($tool['priority'] ?? 100)),
                'title_override' => (string) ($row['title_override'] ?? ''),
                'description_override' => (string) ($row['description_override'] ?? ''),
            ];
        }

        return $settings;
    }

    /**
     * @param array<string,mixed> $posted
     */
    public static function save_module_settings(array $posted): void
    {
        if (!class_exists('CMS\\Database')) {
            return;
        }

        $modules = is_array($posted['modules'] ?? null) ? $posted['modules'] : [];
        $db = \CMS\Database::instance();
        $table = self::table_name($db);

        $sql = "INSERT INTO {$table} (module_key, is_enabled, status_override, priority_override, title_override, description_override)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    is_enabled = VALUES(is_enabled),
                    status_override = VALUES(status_override),
                    priority_override = VALUES(priority_override),
                    title_override = VALUES(title_override),
                    description_override = VALUES(description_override)";
        $stmt = $db->prepare($sql);

        foreach ($modules as $moduleKey => $values) {
            if (!is_array($values)) {
                continue;
            }

            $key = self::clean_key((string) $moduleKey);
            if ($key === '') {
                continue;
            }

            $enabled = !empty($values['is_enabled']) ? 1 : 0;
            $status = self::normalize_status((string) ($values['status_override'] ?? 'live')) ?: 'live';
            $priority = max(0, min(1000, (int) ($values['priority_override'] ?? 100)));
            $title = self::limit_text(trim(strip_tags((string) ($values['title_override'] ?? ''))), 90);
            $description = self::limit_text(trim(strip_tags((string) ($values['description_override'] ?? ''))), 140);

            $stmt->execute([$key, $enabled, $status, $priority, $title !== '' ? $title : null, $description !== '' ? $description : null]);
        }
    }

    private static function table_name(\CMS\Database $db): string
    {
        $prefix = method_exists($db, 'getPrefix') ? $db->getPrefix() : (method_exists($db, 'prefix') ? $db->prefix() : 'cms_');

        return $prefix . 'm365calculator_module_settings';
    }

    private static function normalize_status(string $status): string
    {
        $status = strtolower(trim($status));

        return in_array($status, ['live', 'beta', 'soon'], true) ? $status : '';
    }

    private static function clean_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($value)), '-');
    }

    private static function limit_text(string $value, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }

        return substr($value, 0, $length);
    }
}
