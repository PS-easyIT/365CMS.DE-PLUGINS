<?php
/**
 * CMS M365 Price Tracker – Installer.
 *
 * @package CMS_M365PRICETRACKER
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365PRICETRACKER_Installer
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
            error_log('CMS M365 Price Tracker installer skipped: ' . $e->getMessage());
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
        $table = self::validated_table_name($prefix . 'm365price_tracker_settings');
        $quotedTable = self::quote_identifier($table);

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$quotedTable} (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key   VARCHAR(96) NOT NULL,
            setting_value TEXT DEFAULT NULL,
            updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_setting_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
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
}
