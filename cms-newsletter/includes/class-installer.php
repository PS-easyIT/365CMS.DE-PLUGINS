<?php
/**
 * @package CMS_Newsletter
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Newsletter_Installer
{
    public static function install(): void
    {
        if (!class_exists('CMS\\Database')) {
            return;
        }

        $db = \CMS\Database::instance();
        $prefix = $db->getPrefix();

        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}newsletter_subscribers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL,
            first_name VARCHAR(120) DEFAULT '',
            last_name VARCHAR(120) DEFAULT '',
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            source VARCHAR(50) NOT NULL DEFAULT 'admin',
            segment_slug VARCHAR(120) DEFAULT '',
            optin_token VARCHAR(120) DEFAULT '',
            confirmed_at DATETIME NULL,
            last_sent_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_email (email),
            KEY idx_status (status),
            KEY idx_segment (segment_slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}newsletter_templates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(180) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            content_html LONGTEXT NULL,
            content_text LONGTEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}newsletter_campaigns (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            template_id INT UNSIGNED DEFAULT NULL,
            name VARCHAR(180) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            preview_text VARCHAR(255) DEFAULT '',
            segment_slug VARCHAR(120) DEFAULT '',
            status VARCHAR(30) NOT NULL DEFAULT 'draft',
            scheduled_at DATETIME NULL,
            sent_at DATETIME NULL,
            recipient_count INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_status (status),
            KEY idx_template (template_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}newsletter_sends (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT UNSIGNED NOT NULL,
            subscriber_id INT UNSIGNED NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'queued',
            opened_at DATETIME NULL,
            clicked_at DATETIME NULL,
            last_error TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_campaign (campaign_id),
            KEY idx_subscriber (subscriber_id),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}newsletter_settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(120) NOT NULL,
            setting_value LONGTEXT NULL,
            UNIQUE KEY uniq_setting_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::seed_defaults();
    }

    public static function maybe_install(): void
    {
        self::install();
    }

    public static function uninstall(): void
    {
        // Bewusst keine automatische Datenlöschung.
    }

    private static function seed_defaults(): void
    {
        CMS_Newsletter_Repository::instance()->seed_defaults();
    }
}
