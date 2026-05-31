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
            title_en VARCHAR(180) NOT NULL DEFAULT '',
            slug VARCHAR(120) NOT NULL,
            teaser VARCHAR(255) DEFAULT '',
            teaser_en VARCHAR(255) DEFAULT '',
            content_html LONGTEXT NULL,
            content_html_en LONGTEXT NULL,
            target_url VARCHAR(500) DEFAULT '',
            button_label VARCHAR(80) DEFAULT '',
            button_label_en VARCHAR(80) DEFAULT '',
            image_url VARCHAR(500) DEFAULT '',
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            start_at DATETIME NULL,
            end_at DATETIME NULL,
            priority INT UNSIGNED NOT NULL DEFAULT 0,
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            frequency_cap SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            frequency_window_hours SMALLINT UNSIGNED NOT NULL DEFAULT 24,
            utm_source VARCHAR(80) NOT NULL DEFAULT '',
            utm_medium VARCHAR(80) NOT NULL DEFAULT '',
            utm_campaign VARCHAR(120) NOT NULL DEFAULT '',
            utm_term VARCHAR(120) NOT NULL DEFAULT '',
            utm_content VARCHAR(120) NOT NULL DEFAULT '',
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
        self::ensure_column($db, "{$prefix}promo_placements", 'theme_hook', "ALTER TABLE {$prefix}promo_placements ADD COLUMN theme_hook VARCHAR(50) NOT NULL DEFAULT 'manual' AFTER status");
        self::ensure_column($db, "{$prefix}promo_placements", 'hook_priority', "ALTER TABLE {$prefix}promo_placements ADD COLUMN hook_priority INT NOT NULL DEFAULT 10 AFTER theme_hook");

        self::ensure_column($db, "{$prefix}promos", 'title_en', "ALTER TABLE {$prefix}promos ADD COLUMN title_en VARCHAR(180) NOT NULL DEFAULT '' AFTER title");
        self::ensure_column($db, "{$prefix}promos", 'teaser_en', "ALTER TABLE {$prefix}promos ADD COLUMN teaser_en VARCHAR(255) NOT NULL DEFAULT '' AFTER teaser");
        self::ensure_column($db, "{$prefix}promos", 'content_html_en', "ALTER TABLE {$prefix}promos ADD COLUMN content_html_en LONGTEXT NULL AFTER content_html");
        self::ensure_column($db, "{$prefix}promos", 'button_label_en', "ALTER TABLE {$prefix}promos ADD COLUMN button_label_en VARCHAR(80) NOT NULL DEFAULT '' AFTER button_label");
        self::ensure_column($db, "{$prefix}promos", 'frequency_cap', "ALTER TABLE {$prefix}promos ADD COLUMN frequency_cap SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER is_featured");
        self::ensure_column($db, "{$prefix}promos", 'frequency_window_hours', "ALTER TABLE {$prefix}promos ADD COLUMN frequency_window_hours SMALLINT UNSIGNED NOT NULL DEFAULT 24 AFTER frequency_cap");
        self::ensure_column($db, "{$prefix}promos", 'utm_source', "ALTER TABLE {$prefix}promos ADD COLUMN utm_source VARCHAR(80) NOT NULL DEFAULT '' AFTER frequency_window_hours");
        self::ensure_column($db, "{$prefix}promos", 'utm_medium', "ALTER TABLE {$prefix}promos ADD COLUMN utm_medium VARCHAR(80) NOT NULL DEFAULT '' AFTER utm_source");
        self::ensure_column($db, "{$prefix}promos", 'utm_campaign', "ALTER TABLE {$prefix}promos ADD COLUMN utm_campaign VARCHAR(120) NOT NULL DEFAULT '' AFTER utm_medium");
        self::ensure_column($db, "{$prefix}promos", 'utm_term', "ALTER TABLE {$prefix}promos ADD COLUMN utm_term VARCHAR(120) NOT NULL DEFAULT '' AFTER utm_campaign");
        self::ensure_column($db, "{$prefix}promos", 'utm_content', "ALTER TABLE {$prefix}promos ADD COLUMN utm_content VARCHAR(120) NOT NULL DEFAULT '' AFTER utm_term");
    }

    private static function ensure_column(\CMS\Database $db, string $table, string $column, string $ddl): void
    {
        $stmt = $db->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
        $stmt->execute([$column]);
        if ($stmt->fetch(\PDO::FETCH_ASSOC) === false) {
            $db->query($ddl);
        }
    }

    public static function maybe_install(): void
    {
        self::install();
    }
}
