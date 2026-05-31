<?php
/**
 * CMS M365 Tools – Installer.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Installer
{
    public static function install(): void
    {
        self::run_safely();
    }

    public static function maybe_install(): void
    {
        self::run_safely();
    }

    public static function ensure_for_admin_save(): void
    {
        self::create_tables();
    }

    private static function run_safely(): void
    {
        try {
            self::create_tables();
        } catch (\Throwable $e) {
            self::log_install_error($e);
        }
    }

    private static function create_tables(): void
    {
        if (!class_exists('CMS\\Database')) {
            return;
        }

        $db = \CMS\Database::instance();
        $pdo = $db->getPdo();
        $prefix = method_exists($db, 'getPrefix') ? $db->getPrefix() : (method_exists($db, 'prefix') ? $db->prefix() : 'cms_');

        $newTable = self::validated_table_name($prefix . 'm365tools_module_settings');
        $oldTable = self::validated_table_name($prefix . 'm365calculator_module_settings');
        $optionTable = self::validated_table_name($prefix . 'm365tools_module_options');
        $quotedNewTable = self::quote_identifier($newTable);

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$quotedNewTable} (
            id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            module_key           VARCHAR(64)  NOT NULL,
            is_enabled           TINYINT(1)   NOT NULL DEFAULT 1,
            status_override      VARCHAR(20)  DEFAULT NULL,
            priority_override    INT UNSIGNED DEFAULT NULL,
            title_override       VARCHAR(190) DEFAULT NULL,
            description_override TEXT         DEFAULT NULL,
            updated_at           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_module_key (module_key),
            INDEX idx_enabled (is_enabled)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::ensure_settings_columns($pdo, $newTable);
        self::create_module_options_table($pdo, $optionTable);
        self::migrate_legacy_settings($pdo, $newTable, $oldTable);
    }

    private static function create_module_options_table(\PDO $pdo, string $table): void
    {
        $quotedTable = self::quote_identifier($table);
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$quotedTable} (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            module_key    VARCHAR(64)  NOT NULL,
            option_group  VARCHAR(48)  NOT NULL,
            option_key    VARCHAR(64)  NOT NULL,
            option_value  TEXT         DEFAULT NULL,
            value_type    VARCHAR(20)  NOT NULL DEFAULT 'string',
            updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_module_option (module_key, option_group, option_key),
            INDEX idx_module_group (module_key, option_group)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $columns = [
            'module_key' => 'ADD COLUMN module_key VARCHAR(64) NOT NULL DEFAULT ' . "''" . ' AFTER id',
            'option_group' => 'ADD COLUMN option_group VARCHAR(48) NOT NULL DEFAULT ' . "'general'" . ' AFTER module_key',
            'option_key' => 'ADD COLUMN option_key VARCHAR(64) NOT NULL DEFAULT ' . "''" . ' AFTER option_group',
            'option_value' => 'ADD COLUMN option_value TEXT DEFAULT NULL AFTER option_key',
            'value_type' => 'ADD COLUMN value_type VARCHAR(20) NOT NULL DEFAULT ' . "'string'" . ' AFTER option_value',
            'updated_at' => 'ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER value_type',
        ];

        foreach ($columns as $column => $alterSql) {
            if (self::column_exists($pdo, $table, $column)) {
                continue;
            }

            $pdo->exec('ALTER TABLE ' . self::quote_identifier($table) . ' ' . $alterSql);
        }
    }

    private static function ensure_settings_columns(\PDO $pdo, string $table): void
    {
        $columns = [
            'module_key' => 'ADD COLUMN module_key VARCHAR(64) NOT NULL DEFAULT ' . "''" . ' AFTER id',
            'is_enabled' => 'ADD COLUMN is_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER module_key',
            'status_override' => 'ADD COLUMN status_override VARCHAR(20) DEFAULT NULL AFTER is_enabled',
            'priority_override' => 'ADD COLUMN priority_override INT UNSIGNED DEFAULT NULL AFTER status_override',
            'title_override' => 'ADD COLUMN title_override VARCHAR(190) DEFAULT NULL AFTER priority_override',
            'description_override' => 'ADD COLUMN description_override TEXT DEFAULT NULL AFTER title_override',
            'updated_at' => 'ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER description_override',
        ];

        foreach ($columns as $column => $alterSql) {
            if (self::column_exists($pdo, $table, $column)) {
                continue;
            }

            $pdo->exec('ALTER TABLE ' . self::quote_identifier($table) . ' ' . $alterSql);
        }
    }

    private static function migrate_legacy_settings(\PDO $pdo, string $newTable, string $oldTable): void
    {
        if (!self::table_exists($pdo, $oldTable)) {
            return;
        }

        $legacyColumns = self::table_columns($pdo, $oldTable);
        if (!isset($legacyColumns['module_key'])) {
            return;
        }

        $selectColumns = [
            self::select_column_or_default($legacyColumns, 'module_key', "''"),
            self::select_column_or_default($legacyColumns, 'is_enabled', '1'),
            self::select_column_or_default($legacyColumns, 'status_override', 'NULL'),
            self::select_column_or_default($legacyColumns, 'priority_override', 'NULL'),
            self::select_column_or_default($legacyColumns, 'title_override', 'NULL'),
            self::select_column_or_default($legacyColumns, 'description_override', 'NULL'),
        ];

        $pdo->exec('INSERT INTO ' . self::quote_identifier($newTable) . ' (module_key, is_enabled, status_override, priority_override, title_override, description_override)
            SELECT ' . implode(', ', $selectColumns) . '
            FROM ' . self::quote_identifier($oldTable) . '
            ON DUPLICATE KEY UPDATE
                is_enabled = VALUES(is_enabled),
                status_override = VALUES(status_override),
                priority_override = VALUES(priority_override),
                title_override = VALUES(title_override),
                description_override = VALUES(description_override)');
    }

    private static function column_exists(\PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare('SELECT 1
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            LIMIT 1');
        if (!$stmt instanceof \PDOStatement) {
            throw new \RuntimeException('Failed to prepare INFORMATION_SCHEMA column query.');
        }
        $stmt->execute([$table, $column]);

        return $stmt->fetchColumn() !== false;
    }

    private static function table_exists(\PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare('SELECT 1
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
            LIMIT 1');
        if (!$stmt instanceof \PDOStatement) {
            throw new \RuntimeException('Failed to prepare INFORMATION_SCHEMA table query.');
        }
        $stmt->execute([$table]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * @return array<string,bool>
     */
    private static function table_columns(\PDO $pdo, string $table): array
    {
        $columns = [];
        $stmt = $pdo->query('SHOW COLUMNS FROM ' . self::quote_identifier($table));
        if ($stmt === false) {
            return $columns;
        }

        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $field = strtolower((string) ($row['Field'] ?? ''));
            if ($field !== '') {
                $columns[$field] = true;
            }
        }

        return $columns;
    }

    /**
     * @param array<string,bool> $availableColumns
     */
    private static function select_column_or_default(array $availableColumns, string $column, string $defaultSql): string
    {
        return isset($availableColumns[strtolower($column)]) ? self::quote_identifier($column) : $defaultSql . ' AS ' . self::quote_identifier($column);
    }

    private static function quote_identifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private static function validated_table_name(string $table): string
    {
        if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
            throw new \RuntimeException('Invalid table identifier configured.');
        }

        return $table;
    }

    private static function log_install_error(\Throwable $e): void
    {
        error_log('CMS M365 Tools installer skipped: ' . $e->getMessage());
    }
}
