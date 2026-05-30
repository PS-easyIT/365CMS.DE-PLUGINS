<?php
/**
 * CMS M365 Matrixen – Installer für gemeinsame Options-Tabellen.
 *
 * @package CMS_M365MATRICES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365MATRICES_Installer
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
            error_log('CMS M365 Matrixen installer skipped: ' . $e->getMessage());
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
        $settingsTable = $prefix . 'm365tools_module_settings';
        $optionTable = $prefix . 'm365tools_module_options';

        $pdo->exec('CREATE TABLE IF NOT EXISTS ' . self::quote_identifier($settingsTable) . " (
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

        $pdo->exec('CREATE TABLE IF NOT EXISTS ' . self::quote_identifier($optionTable) . " (
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
    }

    private static function quote_identifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}