<?php
/**
 * Database Manager für CMS Feed
 *
 * @package CMS_Feed
 * @since   1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('CMS_Feed_Database', false)) {
    return;
}

final class CMS_Feed_Database
{
    private static ?self $instance = null;
    private const PROCESSING_TIMEOUT_MINUTES = 20;
    /** @var array<string,string>|null */
    private ?array $settingsCache = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    public function ensure_schema(): void
    {
        static $ensured = false;

        if ($ensured) {
            return;
        }

        $ensured = true;
        $this->create_tables();
        $this->migrate_current_schema();
        $this->migrate_legacy_schema();
        $this->seed_defaults();
    }

    // ──────────────────────────────────────────────────────────────────────
    // Tabellen anlegen
    // ──────────────────────────────────────────────────────────────────────

    public function create_tables(): void
    {
        $db     = \CMS\Database::instance();
        $pdo    = $db->getPdo();
        $prefix = $db->prefix();

        // ── Kategorien / Bereiche ─────────────────────────────────────────
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}feed_categories (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name            VARCHAR(150) NOT NULL,
            slug            VARCHAR(150) NOT NULL,
            description     TEXT          DEFAULT NULL,
            icon            VARCHAR(10)   DEFAULT '📰',
            is_public       TINYINT(1)    NOT NULL DEFAULT 1,
            sort_order      INT           DEFAULT 0,
            layout          VARCHAR(30)   NOT NULL DEFAULT 'grid' COMMENT 'grid|list|magazine',
            items_per_page  INT UNSIGNED  NOT NULL DEFAULT 20,
            created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_slug (slug),
            INDEX idx_public (is_public),
            INDEX idx_sort   (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Feed-Kanäle (RSS-Quellen) ─────────────────────────────────────
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}feed_channels (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id     INT UNSIGNED  NOT NULL,
            name            VARCHAR(255)  NOT NULL,
            feed_url        VARCHAR(500)  NOT NULL,
            site_url        VARCHAR(500)  DEFAULT NULL,
            description     TEXT          DEFAULT NULL,
            icon_url        VARCHAR(500)  DEFAULT NULL,
            is_active       TINYINT(1)    NOT NULL DEFAULT 1,
            fetch_interval  INT UNSIGNED  NOT NULL DEFAULT 60 COMMENT 'Minuten',
            max_items       INT UNSIGNED  NOT NULL DEFAULT 50,
            last_fetched_at TIMESTAMP     NULL DEFAULT NULL,
            last_error      TEXT          DEFAULT NULL,
            item_count      INT UNSIGNED  NOT NULL DEFAULT 0,
            created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category (category_id),
            INDEX idx_active   (is_active),
            INDEX idx_fetch    (last_fetched_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Feed-Einträge (gecachte Artikel) ──────────────────────────────
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}feed_items (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            channel_id      INT UNSIGNED  NOT NULL,
            category_id     INT UNSIGNED  NOT NULL,
            guid            VARCHAR(500)  NOT NULL,
            title           VARCHAR(500)  NOT NULL,
            link            VARCHAR(500)  NOT NULL,
            description     TEXT          DEFAULT NULL,
            content         LONGTEXT      DEFAULT NULL,
            author          VARCHAR(255)  DEFAULT NULL,
            image_url       VARCHAR(500)  DEFAULT NULL,
            pub_date        DATETIME      NOT NULL,
            is_featured     TINYINT(1)    NOT NULL DEFAULT 0,
            is_hidden       TINYINT(1)    NOT NULL DEFAULT 0,
            created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_guid_channel (guid(191), channel_id),
            INDEX idx_channel   (channel_id),
            INDEX idx_category  (category_id),
            INDEX idx_pub_date  (pub_date),
            INDEX idx_featured  (is_featured),
            INDEX idx_hidden    (is_hidden)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Einstellungen (Key-Value) ─────────────────────────────────────
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}feed_settings (
            setting_key   VARCHAR(100)  NOT NULL PRIMARY KEY,
            setting_value TEXT          DEFAULT NULL,
            updated_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── E-Mail-Digest-Konfigurationen ─────────────────────────────────
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}feed_digests (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name            VARCHAR(150)  NOT NULL,
            email           VARCHAR(255)  NOT NULL,
            category_ids    TEXT          NOT NULL COMMENT 'JSON array of category IDs',
            frequency       TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=1x, 2=2x, 3=3x, 4=4x pro Tag',
            is_active       TINYINT(1)    NOT NULL DEFAULT 1,
            last_sent_at    TIMESTAMP     NULL DEFAULT NULL,
            created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_active (is_active),
            INDEX idx_email  (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Member-Feed-Abos ────────────────────────────────────────────────
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}feed_member_subscriptions (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id         INT UNSIGNED NOT NULL,
            email           VARCHAR(255) NOT NULL,
            channel_ids     LONGTEXT     NOT NULL COMMENT 'JSON array of feed_channel IDs',
            frequency       VARCHAR(20)  NOT NULL DEFAULT 'daily' COMMENT 'daily|weekly',
            daily_mode      VARCHAR(20)  NOT NULL DEFAULT '09' COMMENT '09|15|09_15',
            weekly_day      TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Montag ... 7=Sonntag',
            weekly_time     VARCHAR(5)   NOT NULL DEFAULT '09' COMMENT '09|15',
            is_active       TINYINT(1)   NOT NULL DEFAULT 1,
            last_sent_at    DATETIME     DEFAULT NULL,
            created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user (user_id),
            INDEX idx_active (is_active),
            INDEX idx_frequency (frequency)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Legacy-Kompatibilität für ältere Theme-Versionen ───────────────
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}feed_subscriptions (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id         INT UNSIGNED NOT NULL,
            channel_id      INT UNSIGNED NOT NULL,
            feed_id         INT UNSIGNED NOT NULL,
            email           VARCHAR(255) NOT NULL,
            frequency       VARCHAR(20)  NOT NULL DEFAULT 'daily',
            daily_mode      VARCHAR(20)  NOT NULL DEFAULT '09',
            weekly_day      TINYINT UNSIGNED NOT NULL DEFAULT 1,
            weekly_time     VARCHAR(5)   NOT NULL DEFAULT '09',
            is_active       TINYINT(1)   NOT NULL DEFAULT 1,
            last_sent_at    DATETIME     DEFAULT NULL,
            created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_channel (user_id, channel_id),
            UNIQUE KEY unique_user_feed (user_id, feed_id),
            INDEX idx_user (user_id),
            INDEX idx_channel (channel_id),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Fetch-Queue (Warteschlange für Bulk-Abrufe) ───────────────────
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}feed_fetch_queue (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            channel_id      INT UNSIGNED  NOT NULL,
            status          VARCHAR(20)   NOT NULL DEFAULT 'pending' COMMENT 'pending|processing|done|failed',
            error           TEXT          DEFAULT NULL,
            created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            processed_at    TIMESTAMP     NULL DEFAULT NULL,
            INDEX idx_status     (status),
            INDEX idx_channel    (channel_id),
            INDEX idx_created    (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function migrate_current_schema(): void
    {
        $db     = \CMS\Database::instance();
        $pdo    = $db->getPdo();
        $prefix = $db->prefix();

        $categories = $prefix . 'feed_categories';
        $channels   = $prefix . 'feed_channels';
        $items      = $prefix . 'feed_items';
        $settings   = $prefix . 'feed_settings';
        $digests    = $prefix . 'feed_digests';
        $members    = $prefix . 'feed_member_subscriptions';
        $legacy     = $prefix . 'feed_subscriptions';
        $queue      = $prefix . 'feed_fetch_queue';

        $categoryColumns = $this->get_table_columns($pdo, $categories);
        $this->add_column_if_missing($pdo, $categories, $categoryColumns, 'description', 'TEXT DEFAULT NULL AFTER slug');
        $this->add_column_if_missing($pdo, $categories, $categoryColumns, 'icon', "VARCHAR(10) DEFAULT '📰' AFTER description");
        $this->add_column_if_missing($pdo, $categories, $categoryColumns, 'is_public', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER icon');
        $this->add_column_if_missing($pdo, $categories, $categoryColumns, 'sort_order', 'INT DEFAULT 0 AFTER is_public');
        $this->add_column_if_missing($pdo, $categories, $categoryColumns, 'layout', "VARCHAR(30) NOT NULL DEFAULT 'grid' AFTER sort_order");
        $this->add_column_if_missing($pdo, $categories, $categoryColumns, 'items_per_page', 'INT UNSIGNED NOT NULL DEFAULT 20 AFTER layout');
        $this->add_column_if_missing($pdo, $categories, $categoryColumns, 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER items_per_page');
        $this->add_column_if_missing($pdo, $categories, $categoryColumns, 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
        $this->add_index_if_missing($pdo, $categories, 'unique_slug', 'ADD UNIQUE KEY unique_slug (slug)');
        $this->add_index_if_missing($pdo, $categories, 'idx_public', 'ADD INDEX idx_public (is_public)');
        $this->add_index_if_missing($pdo, $categories, 'idx_sort', 'ADD INDEX idx_sort (sort_order)');

        $channelColumns = $this->get_table_columns($pdo, $channels);
        $this->add_column_if_missing($pdo, $channels, $channelColumns, 'category_id', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER id');
        $this->add_column_if_missing($pdo, $channels, $channelColumns, 'site_url', 'VARCHAR(500) DEFAULT NULL AFTER feed_url');
        $this->add_column_if_missing($pdo, $channels, $channelColumns, 'description', 'TEXT DEFAULT NULL AFTER site_url');
        $this->add_column_if_missing($pdo, $channels, $channelColumns, 'icon_url', 'VARCHAR(500) DEFAULT NULL AFTER description');
        $this->add_column_if_missing($pdo, $channels, $channelColumns, 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER icon_url');
        $this->add_column_if_missing($pdo, $channels, $channelColumns, 'fetch_interval', 'INT UNSIGNED NOT NULL DEFAULT 60 AFTER is_active');
        $this->add_column_if_missing($pdo, $channels, $channelColumns, 'max_items', 'INT UNSIGNED NOT NULL DEFAULT 50 AFTER fetch_interval');
        $this->add_column_if_missing($pdo, $channels, $channelColumns, 'last_fetched_at', 'TIMESTAMP NULL DEFAULT NULL AFTER max_items');
        $this->add_column_if_missing($pdo, $channels, $channelColumns, 'last_error', 'TEXT DEFAULT NULL AFTER last_fetched_at');
        $this->add_column_if_missing($pdo, $channels, $channelColumns, 'item_count', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER last_error');
        $this->add_column_if_missing($pdo, $channels, $channelColumns, 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER item_count');
        $this->add_column_if_missing($pdo, $channels, $channelColumns, 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
        $this->add_index_if_missing($pdo, $channels, 'idx_category', 'ADD INDEX idx_category (category_id)');
        $this->add_index_if_missing($pdo, $channels, 'idx_active', 'ADD INDEX idx_active (is_active)');
        $this->add_index_if_missing($pdo, $channels, 'idx_fetch', 'ADD INDEX idx_fetch (last_fetched_at)');

        $itemColumns = $this->get_table_columns($pdo, $items);
        $this->add_column_if_missing($pdo, $items, $itemColumns, 'channel_id', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER id');
        $this->add_column_if_missing($pdo, $items, $itemColumns, 'category_id', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER channel_id');
        $this->add_column_if_missing($pdo, $items, $itemColumns, 'content', 'LONGTEXT DEFAULT NULL AFTER description');
        $this->add_column_if_missing($pdo, $items, $itemColumns, 'author', 'VARCHAR(255) DEFAULT NULL AFTER content');
        $this->add_column_if_missing($pdo, $items, $itemColumns, 'image_url', 'VARCHAR(500) DEFAULT NULL AFTER author');
        $this->add_column_if_missing($pdo, $items, $itemColumns, 'pub_date', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER image_url');
        $this->add_column_if_missing($pdo, $items, $itemColumns, 'is_featured', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER pub_date');
        $this->add_column_if_missing($pdo, $items, $itemColumns, 'is_hidden', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER is_featured');
        $this->add_column_if_missing($pdo, $items, $itemColumns, 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER is_hidden');
        $this->add_index_if_missing($pdo, $items, 'unique_guid_channel', 'ADD UNIQUE KEY unique_guid_channel (guid(191), channel_id)');
        $this->add_index_if_missing($pdo, $items, 'idx_channel', 'ADD INDEX idx_channel (channel_id)');
        $this->add_index_if_missing($pdo, $items, 'idx_category', 'ADD INDEX idx_category (category_id)');
        $this->add_index_if_missing($pdo, $items, 'idx_pub_date', 'ADD INDEX idx_pub_date (pub_date)');
        $this->add_index_if_missing($pdo, $items, 'idx_featured', 'ADD INDEX idx_featured (is_featured)');
        $this->add_index_if_missing($pdo, $items, 'idx_hidden', 'ADD INDEX idx_hidden (is_hidden)');

        $settingColumns = $this->get_table_columns($pdo, $settings);
        $this->add_column_if_missing($pdo, $settings, $settingColumns, 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER setting_value');

        $digestColumns = $this->get_table_columns($pdo, $digests);
        $this->add_column_if_missing($pdo, $digests, $digestColumns, 'last_sent_at', 'TIMESTAMP NULL DEFAULT NULL AFTER is_active');
        $this->add_column_if_missing($pdo, $digests, $digestColumns, 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER last_sent_at');
        $this->add_column_if_missing($pdo, $digests, $digestColumns, 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
        $this->add_index_if_missing($pdo, $digests, 'idx_active', 'ADD INDEX idx_active (is_active)');
        $this->add_index_if_missing($pdo, $digests, 'idx_email', 'ADD INDEX idx_email (email)');

        $memberColumns = $this->get_table_columns($pdo, $members);
        $this->add_column_if_missing($pdo, $members, $memberColumns, 'daily_mode', "VARCHAR(20) NOT NULL DEFAULT '09' AFTER frequency");
        $this->add_column_if_missing($pdo, $members, $memberColumns, 'weekly_day', 'TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER daily_mode');
        $this->add_column_if_missing($pdo, $members, $memberColumns, 'weekly_time', "VARCHAR(5) NOT NULL DEFAULT '09' AFTER weekly_day");
        $this->add_index_if_missing($pdo, $members, 'unique_user', 'ADD UNIQUE KEY unique_user (user_id)');
        $this->add_index_if_missing($pdo, $members, 'idx_active', 'ADD INDEX idx_active (is_active)');
        $this->add_index_if_missing($pdo, $members, 'idx_frequency', 'ADD INDEX idx_frequency (frequency)');

        $legacyColumns = $this->get_table_columns($pdo, $legacy);
        $this->add_column_if_missing($pdo, $legacy, $legacyColumns, 'daily_mode', "VARCHAR(20) NOT NULL DEFAULT '09' AFTER frequency");
        $this->add_column_if_missing($pdo, $legacy, $legacyColumns, 'weekly_day', 'TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER daily_mode');
        $this->add_column_if_missing($pdo, $legacy, $legacyColumns, 'weekly_time', "VARCHAR(5) NOT NULL DEFAULT '09' AFTER weekly_day");

        $queueColumns = $this->get_table_columns($pdo, $queue);
        $this->add_column_if_missing($pdo, $queue, $queueColumns, 'error', 'TEXT DEFAULT NULL AFTER status');
        $this->add_column_if_missing($pdo, $queue, $queueColumns, 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER error');
        $this->add_column_if_missing($pdo, $queue, $queueColumns, 'processed_at', 'TIMESTAMP NULL DEFAULT NULL AFTER created_at');
        $this->add_index_if_missing($pdo, $queue, 'idx_status', 'ADD INDEX idx_status (status)');
        $this->add_index_if_missing($pdo, $queue, 'idx_channel', 'ADD INDEX idx_channel (channel_id)');
        $this->add_index_if_missing($pdo, $queue, 'idx_created', 'ADD INDEX idx_created (created_at)');

        $this->delete_orphaned_relations($pdo, $prefix);
        $this->add_foreign_key_if_missing($pdo, $channels, 'fk_feed_channels_category', "FOREIGN KEY (category_id) REFERENCES {$categories}(id) ON DELETE CASCADE");
        $this->add_foreign_key_if_missing($pdo, $items, 'fk_feed_items_channel', "FOREIGN KEY (channel_id) REFERENCES {$channels}(id) ON DELETE CASCADE");
        $this->add_foreign_key_if_missing($pdo, $items, 'fk_feed_items_category', "FOREIGN KEY (category_id) REFERENCES {$categories}(id) ON DELETE CASCADE");
        $this->add_foreign_key_if_missing($pdo, $queue, 'fk_feed_fetch_queue_channel', "FOREIGN KEY (channel_id) REFERENCES {$channels}(id) ON DELETE CASCADE");
    }

    /**
     * @return array<int,string>
     */
    private function get_table_columns(\PDO $pdo, string $table): array
    {
        try {
            $columns = $pdo->query("SHOW COLUMNS FROM {$table}")->fetchAll(\PDO::FETCH_COLUMN);
            return is_array($columns) ? array_map('strval', $columns) : [];
        } catch (\Throwable $e) {
            CMS_Feed_Error_Handler::instance()->log_exception('CMS Feed DB: Tabellenspalten konnten nicht gelesen werden.', $e, 'warning', ['table' => $table]);
            return [];
        }
    }

    private function add_column_if_missing(\PDO $pdo, string $table, array &$columns, string $column, string $definition): void
    {
        if (in_array($column, $columns, true)) {
            return;
        }

        try {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
            $columns[] = $column;
        } catch (\Throwable $e) {
            CMS_Feed_Error_Handler::instance()->log_exception('CMS Feed DB: Spalte konnte nicht ergänzt werden.', $e, 'warning', ['table' => $table, 'column' => $column]);
        }
    }

    private function add_index_if_missing(\PDO $pdo, string $table, string $indexName, string $definition): void
    {
        try {
            $stmt = $pdo->query("SHOW INDEX FROM {$table} WHERE Key_name = " . $pdo->quote($indexName));
            if ($stmt && $stmt->fetch()) {
                return;
            }

            $pdo->exec("ALTER TABLE {$table} {$definition}");
        } catch (\Throwable $e) {
            CMS_Feed_Error_Handler::instance()->log_exception('CMS Feed DB: Index konnte nicht ergänzt werden.', $e, 'warning', ['table' => $table, 'index' => $indexName]);
        }
    }

    private function add_foreign_key_if_missing(\PDO $pdo, string $table, string $constraintName, string $definition): void
    {
        try {
            $stmt = $pdo->prepare(
                'SELECT CONSTRAINT_NAME
                 FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND CONSTRAINT_NAME = ?
                   AND CONSTRAINT_TYPE = ?'
            );
            $stmt->execute([$this->strip_prefix_from_table_name($table), $constraintName, 'FOREIGN KEY']);
            if ($stmt->fetchColumn() !== false) {
                return;
            }

            $pdo->exec("ALTER TABLE {$table} ADD CONSTRAINT {$constraintName} {$definition}");
        } catch (\Throwable $e) {
            CMS_Feed_Error_Handler::instance()->log_exception('CMS Feed DB: Foreign-Key konnte nicht ergänzt werden.', $e, 'warning', ['table' => $table, 'constraint' => $constraintName]);
        }
    }

    private function strip_prefix_from_table_name(string $table): string
    {
        $parts = preg_split('/[\\\\\/]/', $table);
        return (string) end($parts);
    }

    private function delete_orphaned_relations(\PDO $pdo, string $prefix): void
    {
        $statements = [
            "DELETE q FROM {$prefix}feed_fetch_queue q LEFT JOIN {$prefix}feed_channels c ON q.channel_id = c.id WHERE c.id IS NULL",
            "DELETE i FROM {$prefix}feed_items i LEFT JOIN {$prefix}feed_channels c ON i.channel_id = c.id WHERE c.id IS NULL",
            "DELETE i FROM {$prefix}feed_items i LEFT JOIN {$prefix}feed_categories cat ON i.category_id = cat.id WHERE cat.id IS NULL",
            "DELETE c FROM {$prefix}feed_channels c LEFT JOIN {$prefix}feed_categories cat ON c.category_id = cat.id WHERE cat.id IS NULL",
        ];

        foreach ($statements as $statement) {
            try {
                $pdo->exec($statement);
            } catch (\Throwable $e) {
                CMS_Feed_Error_Handler::instance()->log_exception('CMS Feed DB: Orphan-Cleanup fehlgeschlagen.', $e, 'warning');
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Default-Werte einfügen
    // ──────────────────────────────────────────────────────────────────────

    public function seed_defaults(): void
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();

        $defaults = [
            'archive_title'       => 'Feed-Übersicht',
            'archive_description' => 'Aktuelle Nachrichten und Beiträge aus verschiedenen Quellen',
            'archive_slug'        => 'feeds',
            'per_page'            => '20',
            'color_primary'       => '#0891b2',
            'color_accent'        => '#e0f2fe',
            'color_hdr_from'      => '#0c4a6e',
            'color_hdr_to'        => '#0891b2',
            'color_hdr_title'     => '#ffffff',
            'color_card_bg'       => '#ffffff',
            'color_card_border'   => '#e2e8f0',
            'border_radius'       => '10',
            'grid_columns'        => 'auto',
            'show_source'         => '1',
            'show_date'           => '1',
            'show_image'          => '1',
            'show_excerpt'        => '1',
            'excerpt_length'      => '160',
            'open_in_new_tab'     => '1',
            'digest_from_name'    => '365 CMS Feed Digest',
            'digest_from_email'   => '',
            'digest_subject'      => 'Dein Feed-Digest – {date}',
            'digest_max_items'    => '20',
        ];

        $stmt = $db->prepare(
            "INSERT IGNORE INTO {$prefix}feed_settings (setting_key, setting_value) VALUES (?, ?)"
        );
        foreach ($defaults as $key => $value) {
            $stmt->execute([$key, $value]);
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Settings CRUD
    // ──────────────────────────────────────────────────────────────────────

    public function get_settings(): array
    {
        if ($this->settingsCache !== null) {
            return $this->settingsCache;
        }

        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare("SELECT setting_key, setting_value FROM {$prefix}feed_settings");
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        $this->settingsCache = is_array($rows) ? array_map('strval', $rows) : [];

        return $this->settingsCache;
    }

    public function get_setting(string $key, string $default = ''): string
    {
        if ($this->settingsCache !== null && array_key_exists($key, $this->settingsCache)) {
            return (string) $this->settingsCache[$key];
        }

        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare("SELECT setting_value FROM {$prefix}feed_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string) $val : $default;
    }

    public function update_settings(array $data): void
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare(
            "INSERT INTO {$prefix}feed_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        foreach ($data as $key => $value) {
            $stmt->execute([$key, $value]);
        }

        $this->settingsCache = null;
    }

    // ──────────────────────────────────────────────────────────────────────
    // Categories CRUD
    // ──────────────────────────────────────────────────────────────────────

    public function get_categories(): array
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare("SELECT * FROM {$prefix}feed_categories ORDER BY sort_order ASC, name ASC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function get_category(int $id): ?array
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare("SELECT * FROM {$prefix}feed_categories WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function get_category_by_slug(string $slug): ?array
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare("SELECT * FROM {$prefix}feed_categories WHERE slug = ?");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function get_public_categories(): array
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare("SELECT * FROM {$prefix}feed_categories WHERE is_public = 1 ORDER BY sort_order ASC, name ASC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function save_category(array $data): int
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();

        if (!empty($data['id'])) {
            $stmt = $db->prepare(
                "UPDATE {$prefix}feed_categories SET name = ?, slug = ?, description = ?, icon = ?, is_public = ?, sort_order = ?, layout = ?, items_per_page = ? WHERE id = ?"
            );
            $stmt->execute([
                $data['name'], $data['slug'], $data['description'] ?? null,
                $data['icon'] ?? '📰', (int) ($data['is_public'] ?? 1),
                (int) ($data['sort_order'] ?? 0), $data['layout'] ?? 'grid',
                (int) ($data['items_per_page'] ?? 20), (int) $data['id'],
            ]);
            return (int) $data['id'];
        }

        $stmt = $db->prepare(
            "INSERT INTO {$prefix}feed_categories (name, slug, description, icon, is_public, sort_order, layout, items_per_page) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['name'], $data['slug'], $data['description'] ?? null,
            $data['icon'] ?? '📰', (int) ($data['is_public'] ?? 1),
            (int) ($data['sort_order'] ?? 0), $data['layout'] ?? 'grid',
            (int) ($data['items_per_page'] ?? 20),
        ]);
        return (int) $db->getPdo()->lastInsertId();
    }

    public function delete_category(int $id): void
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $channelIds = $this->get_channel_ids_by_category_ids([$id]);
        $this->remove_channels_from_member_subscriptions($channelIds);

        if ($channelIds !== []) {
            $ph = implode(',', array_fill(0, count($channelIds), '?'));
            $db->prepare("DELETE FROM {$prefix}feed_fetch_queue WHERE channel_id IN ({$ph})")->execute($channelIds);
        }

        // Items und Channels löschen
        $db->prepare("DELETE FROM {$prefix}feed_items WHERE category_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM {$prefix}feed_channels WHERE category_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM {$prefix}feed_categories WHERE id = ?")->execute([$id]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Channels CRUD
    // ──────────────────────────────────────────────────────────────────────

    public function get_channels(int $categoryId = 0): array
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();

        if ($categoryId > 0) {
            $stmt = $db->prepare("SELECT c.*, cat.name AS category_name FROM {$prefix}feed_channels c LEFT JOIN {$prefix}feed_categories cat ON c.category_id = cat.id WHERE c.category_id = ? ORDER BY c.name ASC");
            $stmt->execute([$categoryId]);
        } else {
            $stmt = $db->prepare("SELECT c.*, cat.name AS category_name FROM {$prefix}feed_channels c LEFT JOIN {$prefix}feed_categories cat ON c.category_id = cat.id ORDER BY c.name ASC");
            $stmt->execute();
        }
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function get_channel(int $id): ?array
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare("SELECT * FROM {$prefix}feed_channels WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function save_channel(array $data): int
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();

        if (!empty($data['id'])) {
            $stmt = $db->prepare(
                "UPDATE {$prefix}feed_channels SET category_id = ?, name = ?, feed_url = ?, site_url = ?, description = ?, is_active = ?, fetch_interval = ?, max_items = ? WHERE id = ?"
            );
            $stmt->execute([
                (int) $data['category_id'], $data['name'], $data['feed_url'],
                $data['site_url'] ?? null, $data['description'] ?? null,
                (int) ($data['is_active'] ?? 1), (int) ($data['fetch_interval'] ?? 60),
                (int) ($data['max_items'] ?? 50), (int) $data['id'],
            ]);
            return (int) $data['id'];
        }

        $stmt = $db->prepare(
            "INSERT INTO {$prefix}feed_channels (category_id, name, feed_url, site_url, description, is_active, fetch_interval, max_items) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            (int) $data['category_id'], $data['name'], $data['feed_url'],
            $data['site_url'] ?? null, $data['description'] ?? null,
            (int) ($data['is_active'] ?? 1), (int) ($data['fetch_interval'] ?? 60),
            (int) ($data['max_items'] ?? 50),
        ]);
        return (int) $db->getPdo()->lastInsertId();
    }

    /**
     * Prüft ob eine Feed-URL bereits als Channel existiert.
     */
    public function channel_url_exists(string $feedUrl): bool
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare("SELECT COUNT(*) FROM {$prefix}feed_channels WHERE feed_url = ?");
        $stmt->execute([$feedUrl]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function delete_channel(int $id): void
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $this->remove_channels_from_member_subscriptions([$id]);
        $db->prepare("DELETE FROM {$prefix}feed_fetch_queue WHERE channel_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM {$prefix}feed_items WHERE channel_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM {$prefix}feed_channels WHERE id = ?")->execute([$id]);
    }

    public function update_channel_fetch(int $id, ?string $error = null, int $itemCount = 0): void
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare(
            "UPDATE {$prefix}feed_channels SET last_fetched_at = NOW(), last_error = ?, item_count = ? WHERE id = ?"
        );
        $stmt->execute([$error, $itemCount, $id]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Items CRUD
    // ──────────────────────────────────────────────────────────────────────

    public function insert_item(array $data): bool
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();

        try {
            $stmt = $db->prepare(
                "INSERT IGNORE INTO {$prefix}feed_items (channel_id, category_id, guid, title, link, description, content, author, image_url, pub_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                (int) $data['channel_id'], (int) $data['category_id'],
                $data['guid'], $data['title'], $data['link'],
                $data['description'] ?? null, $data['content'] ?? null,
                $data['author'] ?? null, $data['image_url'] ?? null,
                $data['pub_date'],
            ]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            CMS_Feed_Error_Handler::instance()->log_exception('CMS Feed DB: Feed-Item konnte nicht gespeichert werden.', $e, 'warning');
            return false;
        }
    }

    public function get_items(array $filters = [], int $offset = 0, int $limit = 20): array
    {
        $offset = max(0, $offset);
        $limit = max(1, $limit);
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $where  = [];
        $params = [];

        // Im Admin-Kontext auch versteckte Beiträge anzeigen
        if (empty($filters['include_hidden'])) {
            $where[] = 'i.is_hidden = 0';
        }

        if (!empty($filters['category_id'])) {
            $where[]  = 'i.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }
        if (!empty($filters['channel_id'])) {
            $where[]  = 'i.channel_id = ?';
            $params[] = (int) $filters['channel_id'];
        }
        if (!empty($filters['search'])) {
            $where[]  = '(i.title LIKE ? OR i.description LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['since'])) {
            $where[]  = 'i.pub_date >= ?';
            $params[] = $filters['since'];
        }
        if (isset($filters['is_featured'])) {
            $where[]  = 'i.is_featured = ?';
            $params[] = (int) $filters['is_featured'];
        }

        $whereClause = !empty($where) ? implode(' AND ', $where) : '1=1';
        $sql = "SELECT i.*, c.name AS channel_name, c.site_url AS channel_site_url, c.icon_url AS channel_icon_url, cat.name AS category_name, cat.slug AS category_slug
                FROM {$prefix}feed_items i
                LEFT JOIN {$prefix}feed_channels c ON i.channel_id = c.id
                LEFT JOIN {$prefix}feed_categories cat ON i.category_id = cat.id
                WHERE {$whereClause}
                ORDER BY i.pub_date DESC
                LIMIT ? OFFSET ?";

        $stmt = $db->prepare($sql);
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        $stmt->bindValue(count($params) + 1, $limit, \PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function count_items(array $filters = []): int
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $where  = [];
        $params = [];

        // Im Admin-Kontext auch versteckte Beiträge zählen
        if (empty($filters['include_hidden'])) {
            $where[] = 'i.is_hidden = 0';
        }

        if (!empty($filters['category_id'])) {
            $where[]  = 'i.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }
        if (!empty($filters['channel_id'])) {
            $where[]  = 'i.channel_id = ?';
            $params[] = (int) $filters['channel_id'];
        }
        if (!empty($filters['search'])) {
            $where[]  = '(i.title LIKE ? OR i.description LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($where) ? implode(' AND ', $where) : '1=1';
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$prefix}feed_items i WHERE {$whereClause}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function toggle_item_featured(int $id): void
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $db->prepare(
            "UPDATE {$prefix}feed_items SET is_featured = NOT is_featured WHERE id = ?"
        )->execute([$id]);
    }

    public function toggle_item_hidden(int $id): void
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $db->prepare(
            "UPDATE {$prefix}feed_items SET is_hidden = NOT is_hidden WHERE id = ?"
        )->execute([$id]);
    }

    public function delete_item(int $id): void
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $db->prepare("DELETE FROM {$prefix}feed_items WHERE id = ?")->execute([$id]);
    }

    public function cleanup_old_items(int $days = 90): int
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare(
            "DELETE FROM {$prefix}feed_items WHERE pub_date < DATE_SUB(NOW(), INTERVAL ? DAY) AND is_featured = 0"
        );
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }

    // ──────────────────────────────────────────────────────────────────────
    // Digests CRUD
    // ──────────────────────────────────────────────────────────────────────

    public function get_digests(): array
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare("SELECT * FROM {$prefix}feed_digests ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function get_digest(int $id): ?array
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare("SELECT * FROM {$prefix}feed_digests WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function save_digest(array $data): int
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();

        $catIds = is_array($data['category_ids'] ?? null) ? json_encode($data['category_ids']) : ($data['category_ids'] ?? '[]');

        if (!empty($data['id'])) {
            $stmt = $db->prepare(
                "UPDATE {$prefix}feed_digests SET name = ?, email = ?, category_ids = ?, frequency = ?, is_active = ? WHERE id = ?"
            );
            $stmt->execute([
                $data['name'], $data['email'], $catIds,
                (int) ($data['frequency'] ?? 1), (int) ($data['is_active'] ?? 1),
                (int) $data['id'],
            ]);
            return (int) $data['id'];
        }

        $stmt = $db->prepare(
            "INSERT INTO {$prefix}feed_digests (name, email, category_ids, frequency, is_active) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['name'], $data['email'], $catIds,
            (int) ($data['frequency'] ?? 1), (int) ($data['is_active'] ?? 1),
        ]);
        return (int) $db->getPdo()->lastInsertId();
    }

    public function delete_digest(int $id): void
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $db->prepare("DELETE FROM {$prefix}feed_digests WHERE id = ?")->execute([$id]);
    }

    public function update_digest_sent(int $id): void
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $db->prepare("UPDATE {$prefix}feed_digests SET last_sent_at = NOW() WHERE id = ?")->execute([$id]);
    }

    public function get_active_digests_due(): array
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare(
            "SELECT * FROM {$prefix}feed_digests WHERE is_active = 1 AND (
                last_sent_at IS NULL
                OR (frequency = 1 AND last_sent_at < DATE_SUB(NOW(), INTERVAL 24 HOUR))
                OR (frequency = 2 AND last_sent_at < DATE_SUB(NOW(), INTERVAL 12 HOUR))
                OR (frequency = 3 AND last_sent_at < DATE_SUB(NOW(), INTERVAL 8 HOUR))
                OR (frequency = 4 AND last_sent_at < DATE_SUB(NOW(), INTERVAL 6 HOUR))
            )"
        );
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function get_member_subscription(int $userId): ?array
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare("SELECT * FROM {$prefix}feed_member_subscriptions WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            return $row;
        }

        return $this->get_legacy_member_subscription($userId);
    }

    public function get_active_member_subscriptions(): array
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare(
            "SELECT *
             FROM {$prefix}feed_member_subscriptions
             WHERE is_active = 1
               AND email != ''
               AND channel_ids IS NOT NULL
               AND channel_ids != ''
               AND channel_ids != '[]'
             ORDER BY updated_at ASC"
        );
        $stmt->execute();

        $subscriptions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $excludeUserIds = array_values(array_unique(array_map(
            static fn (array $subscription): int => (int) ($subscription['user_id'] ?? 0),
            $subscriptions
        )));

        return array_merge($subscriptions, $this->get_active_legacy_member_subscriptions($excludeUserIds));
    }

    public function save_member_subscription(array $data): int
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();

        $userId = (int) ($data['user_id'] ?? 0);
        if ($userId <= 0) {
            return 0;
        }

        $channelIds = array_values(array_unique(array_filter(
            array_map('intval', (array) ($data['channel_ids'] ?? [])),
            static fn (int $channelId): bool => $channelId > 0
        )));

        $frequency  = ($data['frequency'] ?? 'daily') === 'weekly' ? 'weekly' : 'daily';
        $dailyMode  = in_array($data['daily_mode'] ?? '09', ['09', '15', '09_15'], true) ? (string) $data['daily_mode'] : '09';
        $weeklyDay  = max(1, min(7, (int) ($data['weekly_day'] ?? 1)));
        $weeklyTime = in_array($data['weekly_time'] ?? '09', ['09', '15'], true) ? (string) $data['weekly_time'] : '09';
        $isActive   = !empty($data['is_active']) ? 1 : 0;
        $email      = trim((string) ($data['email'] ?? ''));
        $channelJson = json_encode($channelIds, JSON_UNESCAPED_UNICODE) ?: '[]';

        $stmt = $db->prepare(
            "INSERT INTO {$prefix}feed_member_subscriptions
                (user_id, email, channel_ids, frequency, daily_mode, weekly_day, weekly_time, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                email = VALUES(email),
                channel_ids = VALUES(channel_ids),
                frequency = VALUES(frequency),
                daily_mode = VALUES(daily_mode),
                weekly_day = VALUES(weekly_day),
                weekly_time = VALUES(weekly_time),
                is_active = VALUES(is_active)"
        );

        $stmt->execute([
            $userId,
            $email,
            $channelJson,
            $frequency,
            $dailyMode,
            $weeklyDay,
            $weeklyTime,
            $isActive,
        ]);

        $this->sync_legacy_member_subscriptions($userId, [
            'email' => $email,
            'channel_ids' => $channelIds,
            'frequency' => $frequency,
            'daily_mode' => $dailyMode,
            'weekly_day' => $weeklyDay,
            'weekly_time' => $weeklyTime,
            'is_active' => $isActive,
        ]);

        $existing = $this->get_member_subscription($userId);

        return (int) ($existing['id'] ?? 0);
    }

    public function update_member_subscription_sent(int $id, ?string $sentAt = null): void
    {
        if ($id <= 0) {
            return;
        }

        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare(
            "UPDATE {$prefix}feed_member_subscriptions
             SET last_sent_at = ?
             WHERE id = ?"
        );
        $stmt->execute([$sentAt ?? date('Y-m-d H:i:s'), $id]);
    }

    public function mark_member_subscription_sent(array $subscription, ?string $sentAt = null): void
    {
        $sentAt ??= date('Y-m-d H:i:s');
        $source = (string) ($subscription['source'] ?? 'member');

        if ($source === 'legacy') {
            $userId = (int) ($subscription['user_id'] ?? 0);
            if ($userId <= 0) {
                return;
            }

            $db     = \CMS\Database::instance();
            $prefix = $db->prefix();
            $stmt   = $db->prepare(
                "UPDATE {$prefix}feed_subscriptions
                 SET last_sent_at = ?
                 WHERE user_id = ?"
            );
            $stmt->execute([$sentAt, $userId]);
            return;
        }

        $this->update_member_subscription_sent((int) ($subscription['id'] ?? 0), $sentAt);
    }

    public function get_member_subscription_channel_ids(array $subscription): array
    {
        $decoded = json_decode((string) ($subscription['channel_ids'] ?? '[]'), true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map('intval', $decoded),
            static fn (int $channelId): bool => $channelId > 0
        )));
    }

    public function get_recent_items_for_channels(array $channelIds, string $since, int $limit = 20): array
    {
        $limit = max(1, $limit);
        $channelIds = array_values(array_unique(array_filter(
            array_map('intval', $channelIds),
            static fn (int $channelId): bool => $channelId > 0
        )));

        if ($channelIds === []) {
            return [];
        }

        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $ph     = implode(',', array_fill(0, count($channelIds), '?'));

        $sql = "SELECT i.*, c.name AS channel_name, c.site_url AS channel_site_url, c.icon_url AS channel_icon_url,
                       cat.name AS category_name, cat.slug AS category_slug
                FROM {$prefix}feed_items i
                LEFT JOIN {$prefix}feed_channels c ON i.channel_id = c.id
                LEFT JOIN {$prefix}feed_categories cat ON i.category_id = cat.id
                WHERE i.is_hidden = 0
                  AND i.channel_id IN ({$ph})
                  AND i.pub_date >= ?
                ORDER BY i.pub_date DESC
                LIMIT ?";

        $stmt = $db->prepare($sql);
        $bindIndex = 1;
        foreach ($channelIds as $channelId) {
            $stmt->bindValue($bindIndex, $channelId, \PDO::PARAM_INT);
            $bindIndex++;
        }
        $stmt->bindValue($bindIndex, $since);
        $stmt->bindValue($bindIndex + 1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function count_member_subscriptions(): int
    {
        return count($this->get_active_member_subscriptions());
    }

    private function migrate_legacy_schema(): void
    {
        $db     = \CMS\Database::instance();
        $pdo    = $db->getPdo();
        $prefix = $db->prefix();
        $table  = $prefix . 'feed_subscriptions';

        try {
            $columns = $pdo->query("SHOW COLUMNS FROM {$table}")->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Throwable) {
            return;
        }

        $hasChannelId = in_array('channel_id', $columns, true);
        $hasFeedId    = in_array('feed_id', $columns, true);

        if (!$hasChannelId) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN channel_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER user_id");
        }

        if (!$hasFeedId) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN feed_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER channel_id");
        }

        $pdo->exec("UPDATE {$table} SET channel_id = feed_id WHERE channel_id = 0 AND feed_id > 0");
        $pdo->exec("UPDATE {$table} SET feed_id = channel_id WHERE feed_id = 0 AND channel_id > 0");

        try {
            $pdo->exec("ALTER TABLE {$table} ADD UNIQUE KEY unique_user_channel (user_id, channel_id)");
        } catch (\Throwable) {
        }

        try {
            $pdo->exec("ALTER TABLE {$table} ADD UNIQUE KEY unique_user_feed (user_id, feed_id)");
        } catch (\Throwable) {
        }

        try {
            $pdo->exec("ALTER TABLE {$table} ADD INDEX idx_channel (channel_id)");
        } catch (\Throwable) {
        }
    }

    private function get_legacy_member_subscription(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare(
            "SELECT *
             FROM {$prefix}feed_subscriptions
             WHERE user_id = ?
             ORDER BY updated_at DESC, id DESC"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->hydrate_legacy_member_subscription($rows);
    }

    private function get_active_legacy_member_subscriptions(array $excludeUserIds = []): array
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $params = [];
        $where  = [
            'is_active = 1',
            "email != ''",
        ];

        if ($excludeUserIds !== []) {
            $excludeUserIds = array_values(array_filter(array_map('intval', $excludeUserIds), static fn (int $userId): bool => $userId > 0));
            if ($excludeUserIds !== []) {
                $ph = implode(',', array_fill(0, count($excludeUserIds), '?'));
                $where[] = "user_id NOT IN ({$ph})";
                $params = array_merge($params, $excludeUserIds);
            }
        }

        $sql = "SELECT *
                FROM {$prefix}feed_subscriptions
                WHERE " . implode(' AND ', $where) . "
                ORDER BY user_id ASC, updated_at DESC, id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if ($rows === []) {
            return [];
        }

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) ($row['user_id'] ?? 0)][] = $row;
        }

        $subscriptions = [];
        foreach ($grouped as $userRows) {
            $subscription = $this->hydrate_legacy_member_subscription($userRows);
            if ($subscription !== null && !empty($subscription['is_active'])) {
                $subscriptions[] = $subscription;
            }
        }

        return $subscriptions;
    }

    private function hydrate_legacy_member_subscription(array $rows): ?array
    {
        if ($rows === []) {
            return null;
        }

        $first = $rows[0];
        $activeFeedIds = [];
        $lastSentAt = null;
        $isActive = false;

        foreach ($rows as $row) {
            if (!empty($row['is_active'])) {
                $feedId = (int) (($row['channel_id'] ?? 0) ?: ($row['feed_id'] ?? 0));
                if ($feedId > 0) {
                    $activeFeedIds[] = $feedId;
                }
                $isActive = true;
            }

            if (!empty($row['last_sent_at']) && ($lastSentAt === null || strcmp((string) $row['last_sent_at'], $lastSentAt) > 0)) {
                $lastSentAt = (string) $row['last_sent_at'];
            }
        }

        $activeFeedIds = array_values(array_unique($activeFeedIds));

        return [
            'id' => -(int) ($first['user_id'] ?? 0),
            'user_id' => (int) ($first['user_id'] ?? 0),
            'email' => (string) ($first['email'] ?? ''),
            'channel_ids' => json_encode($activeFeedIds, JSON_UNESCAPED_UNICODE) ?: '[]',
            'frequency' => (($first['frequency'] ?? 'daily') === 'weekly') ? 'weekly' : 'daily',
            'daily_mode' => in_array($first['daily_mode'] ?? '09', ['09', '15', '09_15'], true) ? (string) $first['daily_mode'] : '09',
            'weekly_day' => max(1, min(7, (int) ($first['weekly_day'] ?? 1))),
            'weekly_time' => in_array($first['weekly_time'] ?? '09', ['09', '15'], true) ? (string) $first['weekly_time'] : '09',
            'is_active' => $isActive ? 1 : 0,
            'last_sent_at' => $lastSentAt,
            'source' => 'legacy',
        ];
    }

    private function sync_legacy_member_subscriptions(int $userId, array $data): void
    {
        if ($userId <= 0) {
            return;
        }

        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $delete = $db->prepare("DELETE FROM {$prefix}feed_subscriptions WHERE user_id = ?");
        $delete->execute([$userId]);

        $channelIds = array_values(array_unique(array_filter(
            array_map('intval', (array) ($data['channel_ids'] ?? [])),
            static fn (int $channelId): bool => $channelId > 0
        )));

        if ($channelIds === []) {
            return;
        }

        $insert = $db->prepare(
            "INSERT INTO {$prefix}feed_subscriptions
                (user_id, channel_id, feed_id, email, frequency, daily_mode, weekly_day, weekly_time, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $email = trim((string) ($data['email'] ?? ''));
        $frequency = (($data['frequency'] ?? 'daily') === 'weekly') ? 'weekly' : 'daily';
        $dailyMode = in_array($data['daily_mode'] ?? '09', ['09', '15', '09_15'], true) ? (string) $data['daily_mode'] : '09';
        $weeklyDay = max(1, min(7, (int) ($data['weekly_day'] ?? 1)));
        $weeklyTime = in_array($data['weekly_time'] ?? '09', ['09', '15'], true) ? (string) $data['weekly_time'] : '09';
        $isActive = !empty($data['is_active']) ? 1 : 0;

        foreach ($channelIds as $channelId) {
            $insert->execute([$userId, $channelId, $channelId, $email, $frequency, $dailyMode, $weeklyDay, $weeklyTime, $isActive]);
        }
    }

    /**
     * @param array<int,int> $categoryIds
     * @return array<int,int>
     */
    private function get_channel_ids_by_category_ids(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique(array_filter(
            array_map('intval', $categoryIds),
            static fn (int $categoryId): bool => $categoryId > 0
        )));

        if ($categoryIds === []) {
            return [];
        }

        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $ph     = implode(',', array_fill(0, count($categoryIds), '?'));
        $stmt   = $db->prepare("SELECT id FROM {$prefix}feed_channels WHERE category_id IN ({$ph})");
        $stmt->execute($categoryIds);

        return array_values(array_unique(array_filter(
            array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: []),
            static fn (int $channelId): bool => $channelId > 0
        )));
    }

    /**
     * Entfernt gelöschte Kanäle aus persönlichen Member-Abos und Legacy-Zeilen,
     * damit Deaktivieren/Löschen keine hängenden JSON-Referenzen hinterlässt.
     *
     * @param array<int,int> $channelIds
     */
    private function remove_channels_from_member_subscriptions(array $channelIds): void
    {
        $channelIds = array_values(array_unique(array_filter(
            array_map('intval', $channelIds),
            static fn (int $channelId): bool => $channelId > 0
        )));

        if ($channelIds === []) {
            return;
        }

        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $ph     = implode(',', array_fill(0, count($channelIds), '?'));

        try {
            $db->prepare("DELETE FROM {$prefix}feed_subscriptions WHERE channel_id IN ({$ph}) OR feed_id IN ({$ph})")
                ->execute(array_merge($channelIds, $channelIds));
        } catch (\Throwable $e) {
            CMS_Feed_Error_Handler::instance()->log_exception('CMS Feed DB: Legacy-Abo-Cleanup fehlgeschlagen.', $e, 'warning');
        }

        try {
            $stmt = $db->prepare("SELECT id, channel_ids FROM {$prefix}feed_member_subscriptions");
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            CMS_Feed_Error_Handler::instance()->log_exception('CMS Feed DB: Member-Abo-Cleanup konnte Abos nicht laden.', $e, 'warning');
            return;
        }

        $update = $db->prepare(
            "UPDATE {$prefix}feed_member_subscriptions
             SET channel_ids = ?, is_active = ?
             WHERE id = ?"
        );

        foreach ($rows as $row) {
            $decoded = json_decode((string) ($row['channel_ids'] ?? '[]'), true);
            if (!is_array($decoded)) {
                continue;
            }

            $current = array_values(array_unique(array_filter(
                array_map('intval', $decoded),
                static fn (int $channelId): bool => $channelId > 0
            )));
            $remaining = array_values(array_diff($current, $channelIds));

            if ($remaining === $current) {
                continue;
            }

            $update->execute([
                json_encode($remaining, JSON_UNESCAPED_UNICODE) ?: '[]',
                $remaining !== [] ? 1 : 0,
                (int) ($row['id'] ?? 0),
            ]);
        }
    }

    public function drop_tables(): void
    {
        $db     = \CMS\Database::instance();
        $pdo    = $db->getPdo();
        $prefix = $db->prefix();

        $tables = [
            "{$prefix}feed_fetch_queue",
            "{$prefix}feed_subscriptions",
            "{$prefix}feed_member_subscriptions",
            "{$prefix}feed_digests",
            "{$prefix}feed_settings",
            "{$prefix}feed_items",
            "{$prefix}feed_channels",
            "{$prefix}feed_categories",
        ];

        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS {$table}");
            }
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Bulk-Operationen
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Mehrere Kanäle auf einmal löschen (inkl. zugehöriger Items).
     */
    public function bulk_delete_channels(array $ids): int
    {
        if (empty($ids)) return 0;

        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $ids    = array_map('intval', $ids);
        $ph     = implode(',', array_fill(0, count($ids), '?'));

        $this->remove_channels_from_member_subscriptions($ids);
        $db->prepare("DELETE FROM {$prefix}feed_items WHERE channel_id IN ({$ph})")->execute($ids);
        $db->prepare("DELETE FROM {$prefix}feed_fetch_queue WHERE channel_id IN ({$ph})")->execute($ids);
        $stmt = $db->prepare("DELETE FROM {$prefix}feed_channels WHERE id IN ({$ph})");
        $stmt->execute($ids);
        return $stmt->rowCount();
    }

    /**
     * Mehrere Bereiche auf einmal löschen (inkl. Kanäle und Items).
     */
    public function bulk_delete_categories(array $ids): int
    {
        if (empty($ids)) return 0;

        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $ids    = array_map('intval', $ids);
        $ph     = implode(',', array_fill(0, count($ids), '?'));

        $this->remove_channels_from_member_subscriptions($this->get_channel_ids_by_category_ids($ids));
        $db->prepare("DELETE FROM {$prefix}feed_items WHERE category_id IN ({$ph})")->execute($ids);
        $db->prepare("DELETE FROM {$prefix}feed_fetch_queue WHERE channel_id IN (SELECT id FROM {$prefix}feed_channels WHERE category_id IN ({$ph}))")->execute($ids);
        $db->prepare("DELETE FROM {$prefix}feed_channels WHERE category_id IN ({$ph})")->execute($ids);
        $stmt = $db->prepare("DELETE FROM {$prefix}feed_categories WHERE id IN ({$ph})");
        $stmt->execute($ids);
        return $stmt->rowCount();
    }

    /**
     * Mehrere Kanäle aktivieren oder deaktivieren.
     */
    public function bulk_toggle_channels(array $ids, bool $active): int
    {
        if (empty($ids)) return 0;

        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $ids    = array_map('intval', $ids);
        $ph     = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $db->prepare("UPDATE {$prefix}feed_channels SET is_active = ? WHERE id IN ({$ph})");
        $stmt->execute(array_merge([(int) $active], $ids));
        return $stmt->rowCount();
    }

    // ──────────────────────────────────────────────────────────────────────
    // Fetch-Queue (Warteschlange)
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Kanäle in die Abruf-Warteschlange einreihen.
     * Bereits ausstehende Einträge für denselben Kanal werden nicht doppelt angelegt.
     */
    public function add_to_fetch_queue(array $channelIds): int
    {
        if (empty($channelIds)) return 0;

        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $added  = 0;

        $this->release_stale_processing_tasks();

        $checkStmt  = $db->prepare(
            "SELECT COUNT(*) FROM {$prefix}feed_fetch_queue WHERE channel_id = ? AND status IN ('pending', 'processing')"
        );
        $insertStmt = $db->prepare(
            "INSERT INTO {$prefix}feed_fetch_queue (channel_id, status) VALUES (?, 'pending')"
        );

        foreach ($channelIds as $id) {
            $id = (int) $id;
            $checkStmt->execute([$id]);
            if ((int) $checkStmt->fetchColumn() === 0) {
                $insertStmt->execute([$id]);
                $added++;
            }
        }

        return $added;
    }

    /**
     * Nächste ausstehende Aufgaben aus der Queue holen und als 'processing' markieren.
     */
    public function get_pending_queue_tasks(int $limit = 5): array
    {
        $limit = max(1, $limit);
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();

        $this->release_stale_processing_tasks();

        $stmt = $db->prepare(
            "SELECT q.*, c.name AS channel_name, c.feed_url
             FROM {$prefix}feed_fetch_queue q
             JOIN {$prefix}feed_channels c ON q.channel_id = c.id
             WHERE q.status = 'pending'
             ORDER BY q.created_at ASC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $tasks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Direkt als processing markieren
        if (!empty($tasks)) {
            $ids = array_column($tasks, 'id');
            $ph  = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("UPDATE {$prefix}feed_fetch_queue SET status = 'processing' WHERE id IN ({$ph})")->execute($ids);
        }

        return $tasks;
    }

    /**
     * Queue-Task-Status aktualisieren.
     */
    public function update_queue_task(int $id, string $status, ?string $error = null): void
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare(
            "UPDATE {$prefix}feed_fetch_queue SET status = ?, error = ?, processed_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$status, $error, $id]);
    }

    /**
     * Alte erledigte/fehlgeschlagene Queue-Einträge aufräumen.
     */
    public function cleanup_queue(int $days = 7): int
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare(
            "DELETE FROM {$prefix}feed_fetch_queue WHERE status IN ('done', 'failed') AND processed_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }

    /**
     * Queue-Statistiken für Dashboard/System-Bereich.
     */
    public function get_queue_stats(): array
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();

        $stmt = $db->prepare(
            "SELECT status, COUNT(*) AS cnt FROM {$prefix}feed_fetch_queue GROUP BY status"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        return [
            'pending'    => (int) ($rows['pending'] ?? 0),
            'processing' => (int) ($rows['processing'] ?? 0),
            'done'       => (int) ($rows['done'] ?? 0),
            'failed'     => (int) ($rows['failed'] ?? 0),
            'total'      => array_sum(array_map('intval', $rows ?: [])),
        ];
    }

    public function release_stale_processing_tasks(int $timeoutMinutes = self::PROCESSING_TIMEOUT_MINUTES): int
    {
        $timeoutMinutes = max(5, $timeoutMinutes);
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare(
            "UPDATE {$prefix}feed_fetch_queue
             SET status = 'pending',
                 error = CASE
                     WHEN error IS NULL OR error = '' THEN 'Queue-Task nach Timeout automatisch aus processing zurück auf pending gesetzt.'
                     ELSE CONCAT(error, '\n[queue-recovery] Queue-Task nach Timeout automatisch aus processing zurück auf pending gesetzt.')
                 END
             WHERE status = 'processing'
               AND created_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)
               AND processed_at IS NULL"
        );
        $stmt->execute([$timeoutMinutes]);

        return $stmt->rowCount();
    }

    /**
     * Technische Feed-Gesundheit für Dashboard/System-Bereich.
     *
     * @return array{active:int, with_errors:int, never_fetched:int, overdue:int}
     */
    public function get_channel_health_summary(): array
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare(
            "SELECT
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN is_active = 1 AND last_error IS NOT NULL AND last_error != '' THEN 1 ELSE 0 END) AS with_errors,
                SUM(CASE WHEN is_active = 1 AND last_fetched_at IS NULL THEN 1 ELSE 0 END) AS never_fetched,
                SUM(CASE WHEN is_active = 1 AND (
                    last_fetched_at IS NULL
                    OR TIMESTAMPDIFF(MINUTE, last_fetched_at, NOW()) > GREATEST(fetch_interval * 2, 60)
                ) THEN 1 ELSE 0 END) AS overdue
             FROM {$prefix}feed_channels"
        );
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

        return [
            'active'        => (int) ($row['active'] ?? 0),
            'with_errors'   => (int) ($row['with_errors'] ?? 0),
            'never_fetched' => (int) ($row['never_fetched'] ?? 0),
            'overdue'       => (int) ($row['overdue'] ?? 0),
        ];
    }

    public function get_attention_channels(int $limit = 5): array
    {
        $limit = max(1, $limit);
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare(
            "SELECT c.*, cat.name AS category_name,
                    TIMESTAMPDIFF(MINUTE, c.last_fetched_at, NOW()) AS minutes_since_fetch
             FROM {$prefix}feed_channels c
             LEFT JOIN {$prefix}feed_categories cat ON c.category_id = cat.id
             WHERE c.is_active = 1
               AND (
                    (c.last_error IS NOT NULL AND c.last_error != '')
                    OR c.last_fetched_at IS NULL
                    OR TIMESTAMPDIFF(MINUTE, c.last_fetched_at, NOW()) > GREATEST(c.fetch_interval * 2, 60)
               )
             ORDER BY
                CASE WHEN c.last_error IS NOT NULL AND c.last_error != '' THEN 0 ELSE 1 END ASC,
                CASE WHEN c.last_fetched_at IS NULL THEN 0 ELSE 1 END ASC,
                c.last_fetched_at ASC,
                c.name ASC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Stats
    // ──────────────────────────────────────────────────────────────────────

    public function get_stats(): array
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();

        $stats = [];

        $stmt = $db->prepare("SELECT COUNT(*) FROM {$prefix}feed_categories");
        $stmt->execute();
        $stats['categories'] = (int) $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM {$prefix}feed_channels");
        $stmt->execute();
        $stats['channels'] = (int) $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM {$prefix}feed_channels WHERE is_active = 1");
        $stmt->execute();
        $stats['channels_active'] = (int) $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM {$prefix}feed_items WHERE is_hidden = 0");
        $stmt->execute();
        $stats['items'] = (int) $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM {$prefix}feed_items WHERE pub_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND is_hidden = 0");
        $stmt->execute();
        $stats['items_today'] = (int) $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM {$prefix}feed_digests WHERE is_active = 1");
        $stmt->execute();
        $stats['digests'] = (int) $stmt->fetchColumn();

        $stats['member_subscriptions'] = $this->count_member_subscriptions();

        $stmt = $db->prepare("SELECT COUNT(*) FROM {$prefix}feed_channels WHERE last_error IS NOT NULL AND last_error != ''");
        $stmt->execute();
        $stats['channels_errors'] = (int) $stmt->fetchColumn();

        return $stats;
    }

    public function get_table_names(): array
    {
        $prefix = \CMS\Database::instance()->prefix();
        return [
            "{$prefix}feed_categories",
            "{$prefix}feed_channels",
            "{$prefix}feed_items",
            "{$prefix}feed_settings",
            "{$prefix}feed_digests",
            "{$prefix}feed_member_subscriptions",
            "{$prefix}feed_subscriptions",
            "{$prefix}feed_fetch_queue",
        ];
    }
}
