<?php
/**
 * CMS M365 Message Center – Installer.
 *
 * @package CMS_M365MessageCenter
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365MessageCenter_Installer
{
    public static function maybe_install(): void
    {
        try {
            $settings = CMS_M365MessageCenter_Repository::instance()->settings();
            if (($settings['db_version'] ?? '') === CMS_M365MESSAGECENTER_DB_VERSION) {
                return;
            }
        } catch (\Throwable $e) {
            self::log_exception('maybe_install_probe_failed', $e);
        }

        self::install();
    }

    public static function install(): void
    {
        $db = \CMS\Database::instance();
        $pdo = method_exists($db, 'getPdo') ? $db->getPdo() : null;
        if (!$pdo instanceof \PDO) {
            return;
        }

        $prefix = self::resolve_prefix($db);
        self::create_tables($pdo, $prefix);
        self::migrate_tables($pdo, $prefix);
        self::seed_settings($db, $prefix);
    }

    private static function create_tables(\PDO $pdo, string $prefix): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}m365messagecenter_settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(120) NOT NULL UNIQUE,
            setting_value TEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}m365messagecenter_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            graph_id VARCHAR(120) NOT NULL,
            title VARCHAR(500) NOT NULL,
            category VARCHAR(120) NULL,
            severity VARCHAR(80) NULL,
            services_json TEXT NULL,
            tags_json TEXT NULL,
            is_major_change TINYINT(1) NOT NULL DEFAULT 0,
            action_required_at DATETIME NULL,
            start_at DATETIME NULL,
            end_at DATETIME NULL,
            last_modified_at DATETIME NULL,
            body_excerpt TEXT NULL,
            body_content MEDIUMTEXT NULL,
            external_url VARCHAR(600) NULL,
            raw_updated_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_m365messagecenter_graph_id (graph_id),
            INDEX idx_m365messagecenter_modified (last_modified_at),
            INDEX idx_m365messagecenter_action (action_required_at),
            INDEX idx_m365messagecenter_category (category),
            INDEX idx_m365messagecenter_major (is_major_change)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private static function migrate_tables(\PDO $pdo, string $prefix): void
    {
        $table = $prefix . 'm365messagecenter_messages';
        $columns = $pdo->query("SHOW COLUMNS FROM {$table} LIKE 'body_content'");
        $hasBodyContent = $columns !== false && $columns->fetch() !== false;
        if ($columns instanceof \PDOStatement) {
            $columns->closeCursor();
        }

        if (!$hasBodyContent) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN body_content MEDIUMTEXT NULL AFTER body_excerpt");
        }
    }

    private static function seed_settings(object $db, string $prefix): void
    {
        $defaults = [
            'db_version' => CMS_M365MESSAGECENTER_DB_VERSION,
            'route_slug' => 'm365-messagecenter',
            'page_overline' => 'Microsoft 365',
            'page_title' => 'M365 Message Center',
            'page_intro' => 'Aktuelle Microsoft-365-Ankündigungen aus dem Message Center – lokal zwischengespeichert, filterbar und sortierbar.',
            'empty_text' => 'Es sind noch keine Message-Center-Meldungen im lokalen Cache vorhanden.',
            'graph_tenant_id' => '',
            'graph_client_id' => '',
            'graph_client_secret' => '',
            'graph_cloud' => 'global',
            'graph_language' => 'de-DE',
            'service_filter' => '',
            'category_filter' => '',
            'default_sort' => 'last_modified',
            'default_direction' => 'desc',
            'items_per_page' => '24',
            'public_layout' => 'standard',
            'public_max_width' => '1160',
            'fetch_limit' => '100',
            'cache_ttl_minutes' => '120',
            'cron_enabled' => '1',
            'cron_hour' => '12',
            'show_detail_pages' => '1',
            'show_status_panel' => '1',
            'show_filters' => '1',
            'show_body_excerpt' => '1',
            'show_external_links' => '1',
        ];

        $exists = $db->prepare("SELECT id FROM {$prefix}m365messagecenter_settings WHERE setting_key = ?");
        $insert = $db->prepare("INSERT INTO {$prefix}m365messagecenter_settings (setting_key, setting_value) VALUES (?, ?)");
        $updateDbVersion = $db->prepare("UPDATE {$prefix}m365messagecenter_settings SET setting_value = ? WHERE setting_key = 'db_version'");

        foreach ($defaults as $key => $value) {
            $exists->execute([$key]);
            if ($exists->fetch()) {
                continue;
            }
            $insert->execute([$key, $value]);
        }

        $updateDbVersion->execute([CMS_M365MESSAGECENTER_DB_VERSION]);
    }

    private static function resolve_prefix(object $db): string
    {
        if (method_exists($db, 'prefix')) {
            return (string) $db->prefix();
        }

        return defined('DB_PREFIX') ? (string) DB_PREFIX : 'cms_';
    }

    private static function log_exception(string $context, \Throwable $e): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CMS M365 Message Center installer [' . $context . ']: ' . $e->getMessage());
        }
    }
}
