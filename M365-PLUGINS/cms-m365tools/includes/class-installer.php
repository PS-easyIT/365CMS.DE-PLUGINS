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

        $newTable = $prefix . 'm365tools_module_settings';
        $oldTable = $prefix . 'm365calculator_module_settings';
        $quotedNewTable = self::quote_identifier($newTable);

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$quotedNewTable} (
            id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            module_key           VARCHAR(120) NOT NULL,
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
        self::migrate_legacy_settings($pdo, $newTable, $oldTable);
    }

    private static function ensure_settings_columns(\PDO $pdo, string $table): void
    {
        $columns = [
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
        $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$oldTable]);
        if ($stmt->fetchColumn() === false) {
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
        $stmt = $pdo->prepare('SHOW COLUMNS FROM ' . self::quote_identifier($table) . ' LIKE ?');
        $stmt->execute([$column]);

        return $stmt->fetch() !== false;
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

    private static function log_install_error(\Throwable $e): void
    {
        error_log('CMS M365 Tools installer skipped: ' . $e->getMessage());
    }
}
