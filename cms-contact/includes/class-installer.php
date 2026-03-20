<?php
/**
 * CMS Contact – Installer
 *
 * Erstellt Datenbanktabellen und Seed-Daten.
 *
 * @package CMS_Contact
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Contact_Installer
{
    /**
     * Vollständige Installation (Tabellen + Seeds)
     */
    public static function install(): void
    {
        self::create_tables();
        self::seed_default_data();
        self::store_db_version(CMS_CONTACT_DB_VERSION);
    }

    /**
     * Prüft ob Installation/Migration nötig ist
     */
    public static function maybe_install(): void
    {
        $stored = self::get_stored_version();
        if ($stored === CMS_CONTACT_DB_VERSION) {
            return;
        }

        if ($stored === '0') {
            self::install();
        } else {
            self::maybe_alter_tables();
            self::store_db_version(CMS_CONTACT_DB_VERSION);
        }
    }

    /**
     * Plugin-Deinstallation
     */
    public static function uninstall(): void
    {
        if (!class_exists('CMS\Database')) {
            return;
        }

        $db  = \CMS\Database::instance();
        $pdo = $db->getPdo();
        $p   = $db->getPrefix();

        $tables = [
            'contact_submission_meta',
            'contact_submissions',
            'contact_fields',
            'contact_forms',
            'contact_settings',
        ];

        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS {$p}{$table}");
        }

        // DB-Version entfernen
        try {
            $stmt = $db->prepare("DELETE FROM {$p}settings WHERE option_name = ?");
            $stmt->execute(['contact_db_version']);
        } catch (\Throwable $e) {
            // Ignorieren falls settings-Tabelle nicht existiert
        }
    }

    // ── Tabellen ──────────────────────────────────────────────────────────────

    private static function create_tables(): void
    {
        if (!class_exists('CMS\Database')) {
            return;
        }

        $db  = \CMS\Database::instance();
        $pdo = $db->getPdo();
        $p   = $db->getPrefix();

        // 1. Kontaktformulare
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}contact_forms (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title         VARCHAR(255)  NOT NULL,
            slug          VARCHAR(100)  NOT NULL,
            template      VARCHAR(50)   NOT NULL DEFAULT 'classic',
            description   TEXT          DEFAULT NULL,
            recipient     VARCHAR(255)  DEFAULT NULL,
            cc_recipients TEXT          DEFAULT NULL,
            subject_prefix VARCHAR(100) DEFAULT NULL,
            success_message TEXT        DEFAULT NULL,
            redirect_url  VARCHAR(500)  DEFAULT NULL,
            enable_captcha TINYINT(1)   NOT NULL DEFAULT 0,
            enable_honeypot TINYINT(1)  NOT NULL DEFAULT 1,
            rate_limit    INT UNSIGNED  NOT NULL DEFAULT 3,
            status        VARCHAR(20)   NOT NULL DEFAULT 'active',
            custom_css    TEXT          DEFAULT NULL,
            settings_json JSON         DEFAULT NULL,
            created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            updated_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_slug (slug),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 2. Benutzerdefinierte Felder
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}contact_fields (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            form_id       INT UNSIGNED  NOT NULL,
            field_name    VARCHAR(100)  NOT NULL,
            field_label   VARCHAR(255)  NOT NULL,
            field_type    VARCHAR(50)   NOT NULL DEFAULT 'text',
            placeholder   VARCHAR(255)  DEFAULT NULL,
            default_value VARCHAR(500)  DEFAULT NULL,
            options_json  JSON          DEFAULT NULL,
            validation    VARCHAR(255)  DEFAULT NULL,
            is_required   TINYINT(1)    NOT NULL DEFAULT 0,
            is_system     TINYINT(1)    NOT NULL DEFAULT 0,
            field_order   INT UNSIGNED  NOT NULL DEFAULT 0,
            field_width   VARCHAR(20)   NOT NULL DEFAULT 'full',
            css_class     VARCHAR(100)  DEFAULT NULL,
            description   VARCHAR(500)  DEFAULT NULL,
            created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_form (form_id),
            INDEX idx_order (form_id, field_order),
            CONSTRAINT fk_field_form FOREIGN KEY (form_id) REFERENCES {$p}contact_forms(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 3. Eingehende Nachrichten
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}contact_submissions (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            form_id       INT UNSIGNED  NOT NULL,
            user_id       INT UNSIGNED  DEFAULT NULL,
            sender_name   VARCHAR(255)  DEFAULT NULL,
            sender_email  VARCHAR(255)  DEFAULT NULL,
            subject       VARCHAR(500)  DEFAULT NULL,
            message       TEXT          DEFAULT NULL,
            user_agent    VARCHAR(500)  DEFAULT NULL,
            status        VARCHAR(20)   NOT NULL DEFAULT 'unread',
            is_spam       TINYINT(1)    NOT NULL DEFAULT 0,
            read_at       TIMESTAMP     NULL DEFAULT NULL,
            replied_at    TIMESTAMP     NULL DEFAULT NULL,
            created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_form (form_id),
            INDEX idx_status (status),
            INDEX idx_user (user_id),
            INDEX idx_created (created_at),
            INDEX idx_spam (is_spam),
            CONSTRAINT fk_submission_form FOREIGN KEY (form_id) REFERENCES {$p}contact_forms(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 4. Meta-Daten zu Nachrichten (Key-Value)
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}contact_submission_meta (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            submission_id   INT UNSIGNED NOT NULL,
            meta_key        VARCHAR(100) NOT NULL,
            meta_value      TEXT         DEFAULT NULL,
            INDEX idx_submission (submission_id),
            INDEX idx_key (meta_key),
            CONSTRAINT fk_meta_submission FOREIGN KEY (submission_id) REFERENCES {$p}contact_submissions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 5. Plugin-Einstellungen
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}contact_settings (
            id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key    VARCHAR(100)  NOT NULL,
            setting_value  TEXT          DEFAULT NULL,
            UNIQUE KEY idx_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // ── Migrationen ───────────────────────────────────────────────────────────

    private static function maybe_alter_tables(): void
    {
        self::create_tables();
        self::seed_default_settings();
        self::remove_submission_ip_column();
    }

    private static function remove_submission_ip_column(): void
    {
        if (!class_exists('CMS\Database')) {
            return;
        }

        try {
            $db = \CMS\Database::instance();
            $p  = $db->getPrefix();
            $tableName = $p . 'contact_submissions';

            $stmt = $db->prepare(
                'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute([$tableName, 'ip_address']);

            if ($stmt->fetch()) {
                $db->getPdo()->exec("ALTER TABLE {$tableName} DROP COLUMN ip_address");
            }
        } catch (\Throwable $e) {
            error_log('CMS_Contact_Installer::remove_submission_ip_column() error: ' . $e->getMessage());
        }
    }

    // ── Seed-Daten ────────────────────────────────────────────────────────────

    private static function seed_default_data(): void
    {
        self::seed_default_settings();
        self::seed_default_form();
    }

    private static function seed_default_settings(): void
    {
        if (!class_exists('CMS\Database')) {
            return;
        }

        $db = \CMS\Database::instance();
        $p  = $db->getPrefix();

        $defaults = [
            'global_recipient'    => '',
            'from_name'           => '365CMS Kontakt',
            'from_email'          => '',
            'enable_notifications' => '1',
            'store_submissions'   => '1',
            'auto_delete_days'    => '90',
            'default_template'    => 'classic',
            'primary_color'       => '#3b82f6',
            'privacy_policy_url'  => '/datenschutz',
            'require_privacy_consent' => '1',
            'success_color'       => '#10b981',
            'error_color'         => '#ef4444',
        ];

        foreach ($defaults as $key => $value) {
            $exists = $db->prepare("SELECT id FROM {$p}contact_settings WHERE setting_key = ?");
            $exists->execute([$key]);
            if (!$exists->fetch()) {
                $db->prepare("INSERT INTO {$p}contact_settings (setting_key, setting_value) VALUES (?, ?)")
                   ->execute([$key, $value]);
            }
        }
    }

    private static function seed_default_form(): void
    {
        if (!class_exists('CMS\Database')) {
            return;
        }

        $db = \CMS\Database::instance();
        $p  = $db->getPrefix();

        // Prüfen ob bereits Formulare existieren
        $count = $db->prepare("SELECT COUNT(*) FROM {$p}contact_forms");
        $count->execute();
        if ((int) $count->fetchColumn() > 0) {
            return;
        }

        // Standard-Kontaktformular anlegen
        $db->prepare("INSERT INTO {$p}contact_forms (title, slug, template, description, success_message) VALUES (?, ?, ?, ?, ?)")
           ->execute([
               'Kontakt',
               'kontakt',
               'classic',
               'Nehmen Sie Kontakt mit uns auf',
               'Vielen Dank für Ihre Nachricht! Wir melden uns schnellstmöglich bei Ihnen.',
           ]);

        $formId = (int) $db->getPdo()->lastInsertId();

        // Standard-Felder anlegen
        $fields = [
            ['name',    'Name',       'text',     1, 1, 1, 'Ihr vollständiger Name', 'half'],
            ['email',   'E-Mail',     'email',    1, 1, 2, 'Ihre E-Mail-Adresse',    'half'],
            ['phone',   'Telefon',    'tel',      0, 0, 3, 'Ihre Telefonnummer',     'half'],
            ['subject', 'Betreff',    'text',     1, 0, 4, 'Betreff Ihrer Nachricht', 'half'],
            ['message', 'Nachricht',  'textarea', 1, 0, 5, 'Ihre Nachricht an uns',  'full'],
        ];

        $stmt = $db->prepare(
            "INSERT INTO {$p}contact_fields (form_id, field_name, field_label, field_type, is_required, is_system, field_order, placeholder, field_width)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        foreach ($fields as [$name, $label, $type, $required, $system, $order, $placeholder, $width]) {
            $stmt->execute([$formId, $name, $label, $type, $required, $system, $order, $placeholder, $width]);
        }
    }

    // ── Versions-Verwaltung ───────────────────────────────────────────────────

    private static function get_stored_version(): string
    {
        if (!class_exists('CMS\Database')) {
            return '0';
        }

        try {
            $db   = \CMS\Database::instance();
            $p    = $db->getPrefix();
            $stmt = $db->prepare("SELECT option_value FROM {$p}settings WHERE option_name = ?");
            $stmt->execute(['contact_db_version']);
            $row = $stmt->fetch();
            return $row ? (string) $row['option_value'] : '0';
        } catch (\Throwable $e) {
            return '0';
        }
    }

    private static function store_db_version(string $version): void
    {
        if (!class_exists('CMS\Database')) {
            return;
        }

        try {
            $db = \CMS\Database::instance();
            $p  = $db->getPrefix();

            $exists = $db->prepare("SELECT option_value FROM {$p}settings WHERE option_name = ?");
            $exists->execute(['contact_db_version']);

            if ($exists->fetch()) {
                $db->prepare("UPDATE {$p}settings SET option_value = ? WHERE option_name = ?")
                   ->execute([$version, 'contact_db_version']);
            } else {
                $db->prepare("INSERT INTO {$p}settings (option_name, option_value) VALUES (?, ?)")
                   ->execute(['contact_db_version', $version]);
            }
        } catch (\Throwable $e) {
            error_log('CMS_Contact_Installer::store_db_version() error: ' . $e->getMessage());
        }
    }
}
