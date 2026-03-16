<?php
/**
 * CMS M365 License – Installer
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365LIC_Installer
{
    public static function install(): void
    {
        self::create_tables();
        CMS_M365LIC_Repository::instance()->seed_defaults(false);
        self::store_db_version(CMS_M365LIC_DB_VERSION);
    }

    public static function maybe_install(): void
    {
        $schemaHealthy = self::required_tables_exist();

        self::create_tables();

        $storedVersion = self::get_stored_version();
        if ($storedVersion === CMS_M365LIC_DB_VERSION && $schemaHealthy) {
            return;
        }

        CMS_M365LIC_Repository::instance()->seed_defaults(false);
        self::store_db_version(CMS_M365LIC_DB_VERSION);
    }

    public static function uninstall(): void
    {
        if (!class_exists('CMS\\Database')) {
            return;
        }

        $db  = \CMS\Database::instance();
        $pdo = $db->getPdo();
        $p   = $db->getPrefix();

        $tables = [
            'm365lic_special_users',
            'm365lic_usage_limits',
            'm365lic_settings',
            'm365lic_packages',
        ];

        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS {$p}{$table}");
        }

        $columns = self::resolve_core_settings_columns();
        if ($columns === null) {
            return;
        }

        try {
            $stmt = $db->prepare("DELETE FROM {$columns['table']} WHERE {$columns['key']} = ?");
            $stmt->execute(['m365lic_db_version']);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private static function create_tables(): void
    {
        if (!class_exists('CMS\\Database')) {
            return;
        }

        $db  = \CMS\Database::instance();
        $pdo = $db->getPdo();
        $p   = $db->getPrefix();

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}m365lic_packages (
            id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug                  VARCHAR(120) NOT NULL,
            name                  VARCHAR(255) NOT NULL,
            kind                  VARCHAR(20)  NOT NULL DEFAULT 'base',
            category              VARCHAR(80)  NOT NULL DEFAULT 'general',
            audience              VARCHAR(30)  NOT NULL DEFAULT 'knowledge',
            pricing_basis         VARCHAR(20)  NOT NULL DEFAULT 'per_user',
            description           TEXT         DEFAULT NULL,
            features_json         LONGTEXT     DEFAULT NULL,
            tags_json             LONGTEXT     DEFAULT NULL,
            prerequisite_tags_json LONGTEXT    DEFAULT NULL,
            public_price          DECIMAL(10,2) DEFAULT NULL,
            member_price          DECIMAL(10,2) DEFAULT NULL,
            group_price           DECIMAL(10,2) DEFAULT NULL,
            currency              VARCHAR(10)  NOT NULL DEFAULT 'EUR',
            pricing_note          VARCHAR(255) DEFAULT NULL,
            source_note           VARCHAR(255) DEFAULT NULL,
            is_active             TINYINT(1)   NOT NULL DEFAULT 1,
            sort_order            INT UNSIGNED NOT NULL DEFAULT 0,
            created_at            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            updated_at            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_slug (slug),
            INDEX idx_kind (kind),
            INDEX idx_category (category),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}m365lic_settings (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key     VARCHAR(120) NOT NULL,
            setting_value   LONGTEXT     DEFAULT NULL,
            updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_setting_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}m365lic_usage_limits (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            action_key      VARCHAR(50)  NOT NULL,
            actor_hash      CHAR(64)     NOT NULL,
            pricing_tier    VARCHAR(20)  NOT NULL DEFAULT 'public',
            date_key        CHAR(8)      NOT NULL,
            hits            INT UNSIGNED NOT NULL DEFAULT 0,
            created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_unique_usage (action_key, actor_hash, pricing_tier, date_key),
            INDEX idx_action_key (action_key),
            INDEX idx_date_key (date_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}m365lic_special_users (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id         INT UNSIGNED NOT NULL,
            group_key       VARCHAR(120) NOT NULL DEFAULT 'special',
            group_label     VARCHAR(190) NOT NULL DEFAULT 'Spezialzugang',
            note            TEXT         DEFAULT NULL,
            is_active       TINYINT(1)   NOT NULL DEFAULT 1,
            created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_user_id (user_id),
            INDEX idx_group_key (group_key),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::ensure_column_exists(
            $pdo,
            "{$p}m365lic_packages",
            'pricing_basis',
            "ALTER TABLE {$p}m365lic_packages ADD COLUMN pricing_basis VARCHAR(20) NOT NULL DEFAULT 'per_user' AFTER audience"
        );
    }

    private static function ensure_column_exists(\PDO $pdo, string $table, string $column, string $alterSql): void
    {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM {$table} LIKE " . $pdo->quote($column));
            $exists = $stmt !== false ? $stmt->fetch(\PDO::FETCH_ASSOC) : false;
            if (!$exists) {
                $pdo->exec($alterSql);
            }
        } catch (\Throwable $e) {
            // ignore migration edge cases
        }
    }

    private static function get_stored_version(): string
    {
        try {
            $db = \CMS\Database::instance();
            $columns = self::resolve_core_settings_columns();
            if ($columns === null) {
                return '0';
            }

            $stmt = $db->prepare("SELECT {$columns['value']} FROM {$columns['table']} WHERE {$columns['key']} = ?");
            $stmt->execute(['m365lic_db_version']);
            return (string) ($stmt->fetchColumn() ?: '0');
        } catch (\Throwable $e) {
            return '0';
        }
    }

    private static function store_db_version(string $version): void
    {
        try {
            $db = \CMS\Database::instance();
            $columns = self::resolve_core_settings_columns();
            if ($columns === null) {
                return;
            }

            $db->prepare(
                "INSERT INTO {$columns['table']} ({$columns['key']}, {$columns['value']})
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE {$columns['value']} = VALUES({$columns['value']})"
            )->execute(['m365lic_db_version', $version]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private static function required_tables_exist(): bool
    {
        if (!class_exists('CMS\\Database')) {
            return false;
        }

        try {
            $db = \CMS\Database::instance();
            $prefix = $db->getPrefix();
            $tables = [
                'm365lic_packages',
                'm365lic_settings',
                'm365lic_usage_limits',
                'm365lic_special_users',
            ];

            foreach ($tables as $table) {
                $stmt = $db->prepare('SHOW TABLES LIKE ?');
                $stmt->execute([$prefix . $table]);

                if (!$stmt->fetchColumn()) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array{table:string,key:string,value:string}|null
     */
    private static function resolve_core_settings_columns(): ?array
    {
        if (!class_exists('CMS\\Database')) {
            return null;
        }

        try {
            $db = \CMS\Database::instance();
            $table = $db->getPrefix() . 'settings';

            $tableStmt = $db->prepare('SHOW TABLES LIKE ?');
            $tableStmt->execute([$table]);
            if (!$tableStmt->fetchColumn()) {
                return null;
            }

            $columnsStmt = $db->getPdo()->query("SHOW COLUMNS FROM {$table}");
            $columns = $columnsStmt !== false
                ? array_map(static fn(array $column): string => (string) ($column['Field'] ?? ''), $columnsStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [])
                : [];

            $keyColumn = in_array('option_name', $columns, true)
                ? 'option_name'
                : (in_array('setting_key', $columns, true) ? 'setting_key' : null);
            $valueColumn = in_array('option_value', $columns, true)
                ? 'option_value'
                : (in_array('setting_value', $columns, true) ? 'setting_value' : null);

            if ($keyColumn === null || $valueColumn === null) {
                return null;
            }

            return [
                'table' => $table,
                'key' => $keyColumn,
                'value' => $valueColumn,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }
}
