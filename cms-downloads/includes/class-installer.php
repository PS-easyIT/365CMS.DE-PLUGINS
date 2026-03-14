<?php
/**
 * @package CMS_Downloads
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Downloads_Installer
{
    public static function install(): void
    {
        self::create_tables();
        self::seed_defaults();
        self::store_db_version(CMS_DOWNLOADS_DB_VERSION);
    }

    public static function maybe_install(): void
    {
        if (self::get_stored_version() === CMS_DOWNLOADS_DB_VERSION) {
            return;
        }

        self::create_tables();
        self::seed_defaults();
        self::store_db_version(CMS_DOWNLOADS_DB_VERSION);
    }

    public static function uninstall(): void
    {
        $db = \CMS\Database::instance();
        $pdo = $db->getPdo();
        $prefix = $db->getPrefix();

        $pdo->exec("DROP TABLE IF EXISTS {$prefix}downloads");
        $pdo->exec("DROP TABLE IF EXISTS {$prefix}download_categories");
        $pdo->exec("DROP TABLE IF EXISTS {$prefix}download_settings");

        $stmt = $db->prepare("DELETE FROM {$prefix}settings WHERE option_name = ?");
        $stmt->execute(['downloads_db_version']);
    }

    private static function create_tables(): void
    {
        $db = \CMS\Database::instance();
        $pdo = $db->getPdo();
        $prefix = $db->getPrefix();

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}download_categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL,
            description TEXT DEFAULT NULL,
            icon VARCHAR(50) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_slug (slug),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}downloads (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id INT UNSIGNED DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(190) NOT NULL,
            summary TEXT DEFAULT NULL,
            description MEDIUMTEXT DEFAULT NULL,
            file_name VARCHAR(255) DEFAULT NULL,
            file_path VARCHAR(500) DEFAULT NULL,
            file_url VARCHAR(500) DEFAULT NULL,
            external_url VARCHAR(500) DEFAULT NULL,
            file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
            file_ext VARCHAR(20) DEFAULT NULL,
            version_label VARCHAR(50) DEFAULT NULL,
            download_type VARCHAR(50) NOT NULL DEFAULT 'generic',
            requires_login TINYINT(1) NOT NULL DEFAULT 0,
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            download_count INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_slug (slug),
            INDEX idx_category (category_id),
            INDEX idx_status (status),
            INDEX idx_type (download_type),
            CONSTRAINT fk_downloads_category FOREIGN KEY (category_id) REFERENCES {$prefix}download_categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}download_settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL,
            setting_value TEXT DEFAULT NULL,
            UNIQUE KEY idx_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private static function seed_defaults(): void
    {
        $repository = CMS_Downloads_Repository::instance();

        foreach ([
            ['name' => 'PowerShell', 'slug' => 'powershell', 'description' => 'Skripte, Automationen und Admin-Werkzeuge.', 'icon' => '🖥️'],
            ['name' => 'Webprojekte', 'slug' => 'webprojekte', 'description' => 'Starter, Deployments und Web-Templates.', 'icon' => '🌐'],
            ['name' => 'Dokumente', 'slug' => 'dokumente', 'description' => 'Formulare, Whitepaper und technische Dokumente.', 'icon' => '📄'],
            ['name' => 'eBooks', 'slug' => 'ebooks', 'description' => 'Digitale Bücher, Guides und Handbücher.', 'icon' => '📚'],
            ['name' => 'Archive & Tools', 'slug' => 'archive-tools', 'description' => 'ZIP-Pakete, Toolkits und Ressourcen.', 'icon' => '🧰'],
        ] as $index => $category) {
            $repository->seed_category($category + ['sort_order' => $index + 1]);
        }

        $repository->seed_settings([
            'archive_title' => 'Downloads',
            'archive_description' => 'Öffentliche Downloads, Vorlagen und Ressourcen nach Kategorien geordnet.',
            'downloads_per_page' => '24',
            'show_search' => '1',
            'show_category_overview' => '1',
        ]);
    }

    private static function get_stored_version(): string
    {
        try {
            $db = \CMS\Database::instance();
            $prefix = $db->getPrefix();
            $stmt = $db->prepare("SELECT option_value FROM {$prefix}settings WHERE option_name = ? LIMIT 1");
            $stmt->execute(['downloads_db_version']);
            $value = $stmt->fetchColumn();

            return $value !== false ? (string) $value : '0';
        } catch (\Throwable) {
            return '0';
        }
    }

    private static function store_db_version(string $version): void
    {
        $db = \CMS\Database::instance();
        $prefix = $db->getPrefix();
        $exists = $db->prepare("SELECT option_value FROM {$prefix}settings WHERE option_name = ? LIMIT 1");
        $exists->execute(['downloads_db_version']);

        if ($exists->fetchColumn() !== false) {
            $stmt = $db->prepare("UPDATE {$prefix}settings SET option_value = ? WHERE option_name = ?");
            $stmt->execute([$version, 'downloads_db_version']);
            return;
        }

        $stmt = $db->prepare("INSERT INTO {$prefix}settings (option_name, option_value) VALUES (?, ?)");
        $stmt->execute(['downloads_db_version', $version]);
    }
}
