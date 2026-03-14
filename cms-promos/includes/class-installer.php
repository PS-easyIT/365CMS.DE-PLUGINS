<?php
/**
 * @package CMS_Promos
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Promos_Installer
{
    public static function install(): void
    {
        if (!class_exists('CMS\\Database')) {
            return;
        }

        $db = \CMS\Database::instance();
        $prefix = $db->getPrefix();

        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}promo_placements (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(180) NOT NULL,
            slug VARCHAR(120) NOT NULL,
            description TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            theme_hook VARCHAR(50) NOT NULL DEFAULT 'manual',
            hook_priority INT NOT NULL DEFAULT 10,
            max_items INT UNSIGNED NOT NULL DEFAULT 3,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}promos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            placement_id INT UNSIGNED DEFAULT NULL,
            title VARCHAR(180) NOT NULL,
            slug VARCHAR(120) NOT NULL,
            teaser VARCHAR(255) DEFAULT '',
            content_html LONGTEXT NULL,
            target_url VARCHAR(500) DEFAULT '',
            button_label VARCHAR(80) DEFAULT '',
            image_url VARCHAR(500) DEFAULT '',
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            start_at DATETIME NULL,
            end_at DATETIME NULL,
            priority INT UNSIGNED NOT NULL DEFAULT 0,
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            impression_count INT UNSIGNED NOT NULL DEFAULT 0,
            click_count INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_slug (slug),
            KEY idx_placement (placement_id),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}promo_settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(120) NOT NULL,
            setting_value LONGTEXT NULL,
            UNIQUE KEY uniq_setting_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::ensure_schema($db, $prefix);

        CMS_Promos_Repository::instance()->seed_defaults();
    }

    private static function ensure_schema(\CMS\Database $db, string $prefix): void
    {
        $themeHookColumn = $db->prepare("SHOW COLUMNS FROM {$prefix}promo_placements LIKE 'theme_hook'");
        $themeHookColumn->execute();
        if ($themeHookColumn->fetch(\PDO::FETCH_ASSOC) === false) {
            $db->query("ALTER TABLE {$prefix}promo_placements ADD COLUMN theme_hook VARCHAR(50) NOT NULL DEFAULT 'manual' AFTER status");
        }

        $hookPriorityColumn = $db->prepare("SHOW COLUMNS FROM {$prefix}promo_placements LIKE 'hook_priority'");
        $hookPriorityColumn->execute();
        if ($hookPriorityColumn->fetch(\PDO::FETCH_ASSOC) === false) {
            $db->query("ALTER TABLE {$prefix}promo_placements ADD COLUMN hook_priority INT NOT NULL DEFAULT 10 AFTER theme_hook");
        }
    }

    public static function maybe_install(): void
    {
        self::install();
    }
}
