<?php
/**
 * CMS M365 Calculator – Installer.
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
        self::create_tables();
    }

    public static function maybe_install(): void
    {
        self::create_tables();
    }

    private static function create_tables(): void
    {
        if (!class_exists('CMS\\Database')) {
            return;
        }

        $db = \CMS\Database::instance();
        $pdo = $db->getPdo();
        $prefix = method_exists($db, 'getPrefix') ? $db->getPrefix() : (method_exists($db, 'prefix') ? $db->prefix() : 'cms_');

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}m365calculator_module_settings (
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
    }
}
