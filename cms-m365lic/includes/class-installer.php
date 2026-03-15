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
        $storedVersion = self::get_stored_version();
        if ($storedVersion === CMS_M365LIC_DB_VERSION) {
            return;
        }

        self::create_tables();
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

        try {
            $stmt = $db->prepare("DELETE FROM {$p}settings WHERE setting_key = ?");
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
            $db   = \CMS\Database::instance();
            $stmt = $db->prepare("SELECT setting_value FROM {$db->getPrefix()}settings WHERE setting_key = ?");
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
            $p  = $db->getPrefix();
            $db->prepare(
                "INSERT INTO {$p}settings (setting_key, setting_value)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            )->execute(['m365lic_db_version', $version]);
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
