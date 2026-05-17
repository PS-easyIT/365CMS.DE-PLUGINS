<?php
/**
 * CMS M365 Tools – module settings.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Settings
{
    private const GLOBAL_MODULE_KEY = 'global';

    /** @var array<string,array<string,mixed>>|null */
    private static ?array $moduleSettingsCache = null;

    /** @var array<string,array<string,string>> */
    private static array $optionCache = [];

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
        if (self::$moduleSettingsCache !== null) {
            return self::$moduleSettingsCache;
        }

        if (!class_exists('CMS\\Database')) {
            return [];
        }

        try {
            $db = \CMS\Database::instance();
            $table = self::table_name($db);
            $quotedTable = self::quote_identifier($table);
            $stmt = $db->getPdo()->query("SELECT module_key, is_enabled, status_override, priority_override, title_override, description_override FROM {$quotedTable}");
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

            self::$moduleSettingsCache = $settings;

            return self::$moduleSettingsCache;
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
        $quotedTable = self::quote_identifier($table);

        $sql = "INSERT INTO {$quotedTable} (module_key, is_enabled, status_override, priority_override, title_override, description_override)
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

        self::$moduleSettingsCache = null;
    }

    /**
     * @param array<string,mixed> $values
     */
    public static function save_single_module_settings(string $moduleKey, array $values): void
    {
        $key = self::clean_key($moduleKey);
        if ($key === '') {
            return;
        }

        self::save_module_settings([
            'modules' => [
                $key => [
                    'is_enabled' => !empty($values['is_enabled']) ? '1' : '0',
                    'status_override' => (string) ($values['status_override'] ?? 'live'),
                    'priority_override' => (string) ($values['priority_override'] ?? '100'),
                    'title_override' => (string) ($values['title_override'] ?? ''),
                    'description_override' => (string) ($values['description_override'] ?? ''),
                ],
            ],
        ]);
    }

    /**
     * @return array<string,string>
     */
    public static function module_options(string $moduleKey, ?string $optionGroup = null): array
    {
        if (!class_exists('CMS\\Database')) {
            return [];
        }

        $key = self::clean_key($moduleKey);
        $group = $optionGroup !== null ? self::clean_key($optionGroup) : null;
        if ($key === '') {
            return [];
        }

        $cacheKey = self::option_cache_key($key, $group);
        if (isset(self::$optionCache[$cacheKey])) {
            return self::$optionCache[$cacheKey];
        }

        try {
            $db = \CMS\Database::instance();
            $table = self::option_table_name($db);
            $quotedTable = self::quote_identifier($table);
            $params = [$key];
            $where = 'module_key = ?';

            if ($group !== null && $group !== '') {
                $where .= ' AND option_group = ?';
                $params[] = $group;
            }

            $stmt = $db->getPdo()->prepare("SELECT option_group, option_key, option_value FROM {$quotedTable} WHERE {$where}");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $options = [];

            foreach ($rows as $row) {
                $optionKey = self::clean_key((string) ($row['option_key'] ?? ''));
                if ($optionKey === '') {
                    continue;
                }

                if ($group !== null && $group !== '') {
                    $options[$optionKey] = (string) ($row['option_value'] ?? '');
                    continue;
                }

                $optionGroup = self::clean_key((string) ($row['option_group'] ?? ''));
                $options[$optionGroup . '.' . $optionKey] = (string) ($row['option_value'] ?? '');
            }

            self::$optionCache[$cacheKey] = $options;

            return self::$optionCache[$cacheKey];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param array<string,string> $options
     */
    public static function save_module_options(string $moduleKey, string $optionGroup, array $options): void
    {
        if (!class_exists('CMS\\Database')) {
            return;
        }

        $key = self::clean_key($moduleKey);
        $group = self::clean_key($optionGroup);
        if ($key === '' || $group === '') {
            return;
        }

        $db = \CMS\Database::instance();
        $table = self::option_table_name($db);
        $quotedTable = self::quote_identifier($table);
        $sql = "INSERT INTO {$quotedTable} (module_key, option_group, option_key, option_value, value_type)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    option_value = VALUES(option_value),
                    value_type = VALUES(value_type)";
        $stmt = $db->getPdo()->prepare($sql);

        foreach ($options as $optionKey => $value) {
            $cleanOptionKey = self::clean_key((string) $optionKey);
            if ($cleanOptionKey === '') {
                continue;
            }

            $stmt->execute([$key, $group, $cleanOptionKey, self::limit_text((string) $value, 2000), 'string']);
        }

        self::clear_option_cache($key, $group);
    }

    /**
     * @return array<string,string>
     */
    public static function global_options(?string $optionGroup = null): array
    {
        return self::module_options(self::GLOBAL_MODULE_KEY, $optionGroup);
    }

    /**
     * @param array<string,string> $options
     */
    public static function save_global_options(string $optionGroup, array $options): void
    {
        self::save_module_options(self::GLOBAL_MODULE_KEY, $optionGroup, $options);
    }

    private static function table_name(\CMS\Database $db): string
    {
        $prefix = method_exists($db, 'getPrefix') ? $db->getPrefix() : (method_exists($db, 'prefix') ? $db->prefix() : 'cms_');

        return $prefix . 'm365tools_module_settings';
    }

    private static function option_table_name(\CMS\Database $db): string
    {
        $prefix = method_exists($db, 'getPrefix') ? $db->getPrefix() : (method_exists($db, 'prefix') ? $db->prefix() : 'cms_');

        return $prefix . 'm365tools_module_options';
    }

    private static function option_cache_key(string $moduleKey, ?string $group): string
    {
        return $moduleKey . '|' . (($group !== null && $group !== '') ? $group : '*');
    }

    private static function clear_option_cache(string $moduleKey, string $group): void
    {
        unset(self::$optionCache[self::option_cache_key($moduleKey, $group)]);
        unset(self::$optionCache[self::option_cache_key($moduleKey, null)]);
    }

    private static function quote_identifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
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
