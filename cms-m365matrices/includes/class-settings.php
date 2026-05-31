<?php
/**
 * CMS M365 Matrixen – gemeinsame Matrix-Optionen.
 *
 * @package CMS_M365MATRICES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365MATRICES_Settings
{
    private const GLOBAL_MODULE_KEY = 'global';

    /** @var array<string,array<string,string>> */
    private static array $optionCache = [];

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
            self::log_error('load options failed for ' . $cacheKey . ': ' . $e->getMessage());
            return [];
        }
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
        $pdo = $db->getPdo();
        $ownsTransaction = !$pdo->inTransaction();
        $delete = $db->prepare("DELETE FROM {$quotedTable} WHERE module_key = ? AND option_group = ?");
        $insert = $db->prepare("INSERT INTO {$quotedTable} (module_key, option_group, option_key, option_value, value_type) VALUES (?, ?, ?, ?, ?)");

        try {
            if ($ownsTransaction) {
                $pdo->beginTransaction();
            }

            $delete->execute([$key, $group]);

            foreach ($options as $optionKey => $value) {
                $cleanOptionKey = self::clean_key((string) $optionKey);
                if ($cleanOptionKey === '') {
                    continue;
                }

                $insert->execute([$key, $group, $cleanOptionKey, self::limit_text((string) $value, 2000), 'string']);
            }

            if ($ownsTransaction) {
                $pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            self::log_error('save options failed for ' . $key . '|' . $group . ': ' . $e->getMessage());
            throw $e;
        }

        self::clear_option_cache($key, $group);
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

    private static function log_error(string $message): void
    {
        error_log('CMS M365 Matrixen settings: ' . $message);
    }
}