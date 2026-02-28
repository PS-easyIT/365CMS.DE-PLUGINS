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

final class CMS_Feed_Database
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
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
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
        $stmt   = $db->prepare("SELECT setting_key, setting_value FROM {$prefix}feed_settings");
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        return $rows ?: [];
    }

    public function get_setting(string $key, string $default = ''): string
    {
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

    public function delete_channel(int $id): void
    {
        $db     = \CMS\Database::instance();
        $prefix = $db->prefix();
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
            error_log('CMS Feed: Insert item failed – ' . $e->getMessage());
            return false;
        }
    }

    public function get_items(array $filters = [], int $offset = 0, int $limit = 20): array
    {
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

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
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
        ];
    }
}
