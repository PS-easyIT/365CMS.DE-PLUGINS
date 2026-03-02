<?php
/**
 * CMS Forum – Datenbank-Installation & Migration
 *
 * Erstellt alle 16 Tabellen des Forum-Plugins und führt
 * Schema-Migrationen bei Versions-Updates durch.
 *
 * @package CMS_Forum\Includes
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Forum_Database
{
    private static ?self $instance = null;

    public static function instance(): static
    {
        return static::$instance ??= new static();
    }

    private function __construct() {}

    /**
     * Vollständige Installation: Tabellen + Seed-Daten.
     */
    public function install(): void
    {
        $this->create_tables();
        $this->seed_default_data();
        $this->store_db_version(CMS_FORUM_DB_VERSION);
    }

    /**
     * Deinstallation: Alle Plugin-Tabellen entfernen.
     */
    public function uninstall(): void
    {
        $db  = \CMS\Database::instance();
        $pdo = $db->getPdo();
        $p   = $db->prefix();

        $tables = [
            'cmsforum_poll_votes',
            'cmsforum_poll_options',
            'cmsforum_polls',
            'cmsforum_likes',
            'cmsforum_reports',
            'cmsforum_mod_log',
            'cmsforum_read_tracking',
            'cmsforum_subscriptions',
            'cmsforum_attachments',
            'cmsforum_posts',
            'cmsforum_threads',
            'cmsforum_permissions',
            'cmsforum_ranks',
            'cmsforum_user_meta',
            'cmsforum_forums',
            'cmsforum_categories',
            'cmsforum_settings',
        ];

        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS {$p}{$table}");
        }

        // Versionsinfo entfernen
        $stmt = $db->prepare("DELETE FROM {$p}settings WHERE option_name = ?");
        $stmt->execute(['cmsforum_db_version']);
    }

    /**
     * Prüft ob ein Upgrade nötig ist und führt Migrationen aus.
     */
    public function maybe_upgrade(): void
    {
        $current = $this->get_db_version();

        if ($current === CMS_FORUM_DB_VERSION) {
            return;
        }

        if (empty($current)) {
            $this->install();
            return;
        }

        // Migrationen
        if (version_compare($current, '1.0.1', '<')) {
            $this->migrate_to_101();
        }

        $this->store_db_version(CMS_FORUM_DB_VERSION);
    }

    /**
     * Alle 16 + 1 (Settings) Tabellen erstellen.
     */
    private function create_tables(): void
    {
        $db  = \CMS\Database::instance();
        $pdo = $db->getPdo();
        $p   = $db->prefix();

        // ── Kategorien ──
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}cmsforum_categories (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name        VARCHAR(100) NOT NULL,
            slug        VARCHAR(110) NOT NULL,
            description TEXT         DEFAULT NULL,
            sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
            is_active   TINYINT(1)   NOT NULL DEFAULT 1,
            created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_slug (slug),
            INDEX idx_sort (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Foren ──
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}cmsforum_forums (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id   INT UNSIGNED NOT NULL,
            parent_id     INT UNSIGNED DEFAULT NULL,
            name          VARCHAR(150) NOT NULL,
            slug          VARCHAR(160) NOT NULL,
            description   TEXT         DEFAULT NULL,
            icon          VARCHAR(50)  DEFAULT NULL,
            sort_order    INT UNSIGNED NOT NULL DEFAULT 0,
            is_active     TINYINT(1)   NOT NULL DEFAULT 1,
            is_locked     TINYINT(1)   NOT NULL DEFAULT 0,
            thread_count  INT UNSIGNED NOT NULL DEFAULT 0,
            post_count    INT UNSIGNED NOT NULL DEFAULT 0,
            last_post_id  INT UNSIGNED DEFAULT NULL,
            last_post_at  DATETIME     DEFAULT NULL,
            created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_slug (slug),
            INDEX idx_category (category_id),
            INDEX idx_parent (parent_id),
            INDEX idx_sort (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Threads ──
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}cmsforum_threads (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            forum_id      INT UNSIGNED NOT NULL,
            user_id       INT UNSIGNED NOT NULL,
            title         VARCHAR(200) NOT NULL,
            slug          VARCHAR(210) NOT NULL,
            type          VARCHAR(20)  NOT NULL DEFAULT 'normal',
            status        VARCHAR(20)  NOT NULL DEFAULT 'open',
            prefix        VARCHAR(30)  DEFAULT NULL,
            is_pinned     TINYINT(1)   NOT NULL DEFAULT 0,
            is_locked     TINYINT(1)   NOT NULL DEFAULT 0,
            has_poll      TINYINT(1)   NOT NULL DEFAULT 0,
            view_count    INT UNSIGNED NOT NULL DEFAULT 0,
            reply_count   INT UNSIGNED NOT NULL DEFAULT 0,
            last_post_id  INT UNSIGNED DEFAULT NULL,
            last_post_at  DATETIME     DEFAULT NULL,
            last_poster_id INT UNSIGNED DEFAULT NULL,
            created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_forum_status (forum_id, status),
            INDEX idx_last_post (last_post_at),
            INDEX idx_user (user_id),
            INDEX idx_pinned (is_pinned, last_post_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Beiträge ──
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}cmsforum_posts (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            thread_id     INT UNSIGNED NOT NULL,
            user_id       INT UNSIGNED NOT NULL,
            content       LONGTEXT     NOT NULL,
            content_html  LONGTEXT     DEFAULT NULL,
            ip_address    VARCHAR(45)  DEFAULT NULL,
            is_first_post TINYINT(1)   NOT NULL DEFAULT 0,
            is_deleted    TINYINT(1)   NOT NULL DEFAULT 0,
            is_approved   TINYINT(1)   NOT NULL DEFAULT 1,
            edit_count    INT UNSIGNED NOT NULL DEFAULT 0,
            edited_by     INT UNSIGNED DEFAULT NULL,
            edited_at     DATETIME     DEFAULT NULL,
            like_count    INT UNSIGNED NOT NULL DEFAULT 0,
            created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_thread (thread_id, created_at),
            INDEX idx_user (user_id),
            INDEX idx_deleted (is_deleted)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── User-Meta (Forum-spezifisch) ──
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}cmsforum_user_meta (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id       INT UNSIGNED NOT NULL,
            post_count    INT UNSIGNED NOT NULL DEFAULT 0,
            thread_count  INT UNSIGNED NOT NULL DEFAULT 0,
            likes_received INT UNSIGNED NOT NULL DEFAULT 0,
            likes_given    INT UNSIGNED NOT NULL DEFAULT 0,
            rank_id       INT UNSIGNED DEFAULT NULL,
            custom_title  VARCHAR(100) DEFAULT NULL,
            signature     TEXT         DEFAULT NULL,
            location      VARCHAR(100) DEFAULT NULL,
            website       VARCHAR(255) DEFAULT NULL,
            is_banned     TINYINT(1)   NOT NULL DEFAULT 0,
            ban_reason    TEXT         DEFAULT NULL,
            ban_expires   DATETIME     DEFAULT NULL,
            last_active   DATETIME     DEFAULT NULL,
            created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_user (user_id),
            INDEX idx_rank (rank_id),
            INDEX idx_posts (post_count)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Ränge ──
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}cmsforum_ranks (
            id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name           VARCHAR(80)  NOT NULL,
            min_posts      INT UNSIGNED NOT NULL DEFAULT 0,
            color          VARCHAR(20)  DEFAULT NULL,
            icon           VARCHAR(50)  DEFAULT NULL,
            is_special     TINYINT(1)   NOT NULL DEFAULT 0,
            sort_order     INT UNSIGNED NOT NULL DEFAULT 0,
            created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_min_posts (min_posts)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Berechtigungen ──
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}cmsforum_permissions (
            id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            forum_id       INT UNSIGNED NOT NULL,
            group_type     VARCHAR(30)  NOT NULL DEFAULT 'member',
            can_read       TINYINT(1)   NOT NULL DEFAULT 1,
            can_post       TINYINT(1)   NOT NULL DEFAULT 1,
            can_create     TINYINT(1)   NOT NULL DEFAULT 1,
            can_edit_own   TINYINT(1)   NOT NULL DEFAULT 1,
            can_delete_own TINYINT(1)   NOT NULL DEFAULT 0,
            can_upload     TINYINT(1)   NOT NULL DEFAULT 1,
            can_vote       TINYINT(1)   NOT NULL DEFAULT 1,
            can_moderate   TINYINT(1)   NOT NULL DEFAULT 0,
            created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            updated_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_forum_group (forum_id, group_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Abonnements ──
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}cmsforum_subscriptions (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id       INT UNSIGNED NOT NULL,
            target_type   VARCHAR(20)  NOT NULL,
            target_id     INT UNSIGNED NOT NULL,
            notify_email  TINYINT(1)   NOT NULL DEFAULT 1,
            created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_subscription (user_id, target_type, target_id),
            INDEX idx_target (target_type, target_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Anhänge ──
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}cmsforum_attachments (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id       INT UNSIGNED NOT NULL,
            user_id       INT UNSIGNED NOT NULL,
            filename      VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            mime_type     VARCHAR(100) NOT NULL,
            file_size     INT UNSIGNED NOT NULL DEFAULT 0,
            download_count INT UNSIGNED NOT NULL DEFAULT 0,
            created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_post (post_id),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Umfragen ──
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}cmsforum_polls (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            thread_id     INT UNSIGNED NOT NULL,
            question      VARCHAR(255) NOT NULL,
            max_choices   TINYINT UNSIGNED NOT NULL DEFAULT 1,
            is_public     TINYINT(1)   NOT NULL DEFAULT 1,
            ends_at       DATETIME     DEFAULT NULL,
            vote_count    INT UNSIGNED NOT NULL DEFAULT 0,
            created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_thread (thread_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Umfrage-Optionen ──
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}cmsforum_poll_options (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            poll_id       INT UNSIGNED NOT NULL,
            text          VARCHAR(200) NOT NULL,
            vote_count    INT UNSIGNED NOT NULL DEFAULT 0,
            sort_order    TINYINT UNSIGNED NOT NULL DEFAULT 0,
            INDEX idx_poll (poll_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Umfrage-Stimmen ──
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}cmsforum_poll_votes (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            poll_id       INT UNSIGNED NOT NULL,
            option_id     INT UNSIGNED NOT NULL,
            user_id       INT UNSIGNED NOT NULL,
            created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_vote (poll_id, option_id, user_id),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Likes / Danke ──
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}cmsforum_likes (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id       INT UNSIGNED NOT NULL,
            user_id       INT UNSIGNED NOT NULL,
            created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_like (post_id, user_id),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Meldungen ──
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}cmsforum_reports (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id       INT UNSIGNED NOT NULL,
            user_id       INT UNSIGNED NOT NULL,
            reason        VARCHAR(50)  NOT NULL DEFAULT 'other',
            description   TEXT         DEFAULT NULL,
            status        VARCHAR(20)  NOT NULL DEFAULT 'open',
            handled_by    INT UNSIGNED DEFAULT NULL,
            handled_at    DATETIME     DEFAULT NULL,
            created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_post (post_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Moderationsprotokoll ──
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}cmsforum_mod_log (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            moderator_id  INT UNSIGNED NOT NULL,
            action        VARCHAR(50)  NOT NULL,
            target_type   VARCHAR(30)  NOT NULL,
            target_id     INT UNSIGNED NOT NULL,
            details       TEXT         DEFAULT NULL,
            ip_address    VARCHAR(45)  DEFAULT NULL,
            created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_moderator (moderator_id),
            INDEX idx_action (action),
            INDEX idx_target (target_type, target_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Gelesen-Tracking ──
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}cmsforum_read_tracking (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id       INT UNSIGNED NOT NULL,
            thread_id     INT UNSIGNED NOT NULL,
            last_read_at  DATETIME     NOT NULL,
            UNIQUE KEY uk_user_thread (user_id, thread_id),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── Plugin-Einstellungen ──
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}cmsforum_settings (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key   VARCHAR(100) NOT NULL,
            setting_value TEXT         DEFAULT NULL,
            UNIQUE KEY uk_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /**
     * Standard-Daten einfügen.
     */
    private function seed_default_data(): void
    {
        $db  = \CMS\Database::instance();
        $pdo = $db->getPdo();
        $p   = $db->prefix();

        // Standard-Einstellungen aus Konfigurationsdatei laden
        $defaults = require CMS_FORUM_DIR . 'config/config.php';
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM {$p}cmsforum_settings WHERE setting_key = ?");
        $stmtInsert = $db->prepare("INSERT INTO {$p}cmsforum_settings (setting_key, setting_value) VALUES (?, ?)");

        foreach ($defaults as $key => $value) {
            $stmtCheck->execute([$key]);
            if ((int) $stmtCheck->fetchColumn() === 0) {
                $stmtInsert->execute([$key, $value]);
            }
        }

        // Standard-Kategorie
        $stmtCheck->execute(['_seed_complete']);
        if ((int) $stmtCheck->fetchColumn() === 0) {
            $pdo->exec("INSERT IGNORE INTO {$p}cmsforum_categories (name, slug, description, sort_order) VALUES
                ('Allgemein', 'allgemein', 'Allgemeine Diskussionen', 1)");

            // Standard-Forum
            $pdo->exec("INSERT IGNORE INTO {$p}cmsforum_forums (category_id, name, slug, description, sort_order) VALUES
                (1, 'Allgemeine Diskussion', 'allgemeine-diskussion', 'Alles was nicht in andere Foren passt.', 1),
                (1, 'Vorstellungsrunde', 'vorstellungsrunde', 'Stelle dich der Community vor!', 2)");

            // Standard-Ränge
            $pdo->exec("INSERT IGNORE INTO {$p}cmsforum_ranks (name, min_posts, color, sort_order) VALUES
                ('Neuling',        0,    '#94a3b8', 1),
                ('Mitglied',       10,   '#3b82f6', 2),
                ('Aktives Mitglied', 50, '#10b981', 3),
                ('Stammgast',      200,  '#8b5cf6', 4),
                ('Experte',        500,  '#f59e0b', 5),
                ('Veteran',        1000, '#ef4444', 6)");

            $stmtInsert->execute(['_seed_complete', '1']);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Migrations                                                         */
    /* ------------------------------------------------------------------ */

    /**
     * v1.0.1 – Spalten like_received/like_given → likes_received/likes_given umbenennen.
     */
    private function migrate_to_101(): void
    {
        $db  = \CMS\Database::instance();
        $pdo = $db->getPdo();
        $tbl = $db->prefix() . 'cmsforum_user_meta';

        $cols = $pdo->query("SHOW COLUMNS FROM `{$tbl}` LIKE 'like_received'")->fetchAll();
        if (!empty($cols)) {
            $pdo->exec("ALTER TABLE `{$tbl}`
                CHANGE `like_received` `likes_received` INT UNSIGNED NOT NULL DEFAULT 0,
                CHANGE `like_given`    `likes_given`    INT UNSIGNED NOT NULL DEFAULT 0");
        }
    }

    /**
     * Aktuelle DB-Version aus der Settings-Tabelle lesen.
     */
    private function get_db_version(): string
    {
        try {
            $db   = \CMS\Database::instance();
            $p    = $db->prefix();
            $stmt = $db->prepare("SELECT option_value FROM {$p}settings WHERE option_name = ?");
            $stmt->execute(['cmsforum_db_version']);
            return $stmt->fetchColumn() ?: '';
        } catch (\PDOException) {
            return '';
        }
    }

    /**
     * DB-Version in die globale Settings-Tabelle schreiben.
     */
    private function store_db_version(string $version): void
    {
        $db = \CMS\Database::instance();
        $p  = $db->prefix();

        $stmt = $db->prepare("SELECT COUNT(*) FROM {$p}settings WHERE option_name = ?");
        $stmt->execute(['cmsforum_db_version']);

        if ((int) $stmt->fetchColumn() > 0) {
            $update = $db->prepare("UPDATE {$p}settings SET option_value = ? WHERE option_name = ?");
            $update->execute([$version, 'cmsforum_db_version']);
        } else {
            $insert = $db->prepare("INSERT INTO {$p}settings (option_name, option_value) VALUES (?, ?)");
            $insert->execute(['cmsforum_db_version', $version]);
        }
    }
}
