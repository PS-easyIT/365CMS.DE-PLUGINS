<?php
/**
 * CMS Booking – Installer
 *
 * Erstellt DB-Tabellen, Seeds und Migrationen.
 *
 * @package CMS_Booking
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Booking_Installer
{
    public static function install(): void
    {
        try {
            self::create_tables();
            self::seed_defaults();
            self::store_db_version(CMS_BOOKING_DB_VERSION);
        } catch (\Throwable $e) {
            self::log_install_error($e);
        }
    }

    public static function maybe_install(): void
    {
        try {
            $stored = self::get_stored_version();
            if ($stored === CMS_BOOKING_DB_VERSION) {
                return;
            }
            if ($stored === '0') {
                self::install();
            } else {
                self::create_tables();
                self::store_db_version(CMS_BOOKING_DB_VERSION);
            }
        } catch (\Throwable $e) {
            self::log_install_error($e);
        }
    }

    public static function uninstall(): void
    {
        if (!class_exists('CMS\Database')) {
            return;
        }
        $db  = \CMS\Database::instance();
        $pdo = $db->getPdo();
        $p   = $db->getPrefix();

        $tables = [
            'booking_meta',
            'bookings',
            'booking_availability',
            'booking_services',
            'booking_providers',
            'booking_settings',
        ];
        foreach ($tables as $t) {
            $pdo->exec("DROP TABLE IF EXISTS {$p}{$t}");
        }

        try {
            $stmt = $db->prepare("DELETE FROM {$p}settings WHERE setting_key = ?");
            $stmt->execute(['booking_db_version']);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /* ================================================================== */
    /*  Tabellen                                                           */
    /* ================================================================== */

    private static function create_tables(): void
    {
        if (!class_exists('CMS\Database')) {
            return;
        }

        $db  = \CMS\Database::instance();
        $pdo = $db->getPdo();
        $p   = $db->getPrefix();

        // 1. Anbieter (Provider) – universell: experts, speakers, companies …
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}booking_providers (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id         INT UNSIGNED  DEFAULT NULL  COMMENT 'FK → users.id  (NULL = externer Anbieter)',
            source_plugin   VARCHAR(50)   NOT NULL      COMMENT 'z.B. cms-experts, cms-speakers, cms-events',
            source_id       INT UNSIGNED  NOT NULL      COMMENT 'PK aus der Quell-Tabelle',
            display_name    VARCHAR(255)  NOT NULL,
            slug            VARCHAR(150)  NOT NULL,
            email           VARCHAR(255)  DEFAULT NULL,
            phone           VARCHAR(50)   DEFAULT NULL,
            avatar_url      VARCHAR(500)  DEFAULT NULL,
            bio             TEXT          DEFAULT NULL,
            contact_form_id INT UNSIGNED  DEFAULT NULL  COMMENT 'FK → contact_forms.id – optionales Kontakt-Formular',
            timezone        VARCHAR(80)   NOT NULL DEFAULT 'Europe/Berlin',
            currency        VARCHAR(10)   NOT NULL DEFAULT 'EUR',
            status          VARCHAR(20)   NOT NULL DEFAULT 'active',
            settings_json   JSON          DEFAULT NULL,
            created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_source (source_plugin, source_id),
            UNIQUE KEY idx_slug   (slug),
            INDEX idx_user   (user_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 2. Dienstleistungen / buchbare Leistungen
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}booking_services (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            provider_id     INT UNSIGNED  NOT NULL,
            title           VARCHAR(255)  NOT NULL,
            slug            VARCHAR(150)  NOT NULL,
            description     TEXT          DEFAULT NULL,
            duration_min    INT UNSIGNED  NOT NULL DEFAULT 60   COMMENT 'Dauer in Minuten',
            buffer_min      INT UNSIGNED  NOT NULL DEFAULT 15   COMMENT 'Puffer danach',
            max_bookings    INT UNSIGNED  NOT NULL DEFAULT 1    COMMENT 'max. parallele Buchungen',
            price_cents     INT UNSIGNED  NOT NULL DEFAULT 0    COMMENT 'Preis in Cent (0 = kostenlos)',
            location_type   VARCHAR(30)   NOT NULL DEFAULT 'online' COMMENT 'online|onsite|hybrid',
            meeting_url     VARCHAR(500)  DEFAULT NULL          COMMENT 'Zoom / Teams Link',
            booking_type    VARCHAR(30)   NOT NULL DEFAULT 'confirmation' COMMENT 'confirmation|instant|request',
            contact_template VARCHAR(50)  DEFAULT NULL          COMMENT 'Kontakt-Template aus cms-contact',
            status          VARCHAR(20)   NOT NULL DEFAULT 'active',
            sort_order      INT UNSIGNED  NOT NULL DEFAULT 0,
            settings_json   JSON          DEFAULT NULL,
            created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_provider (provider_id),
            INDEX idx_status   (status),
            UNIQUE KEY idx_provider_slug (provider_id, slug),
            CONSTRAINT fk_service_provider FOREIGN KEY (provider_id)
                REFERENCES {$p}booking_providers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 3. Verfügbarkeiten (Wochenplan + Ausnahmen)
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}booking_availability (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            provider_id     INT UNSIGNED  NOT NULL,
            day_of_week     TINYINT UNSIGNED DEFAULT NULL COMMENT '0=Mo … 6=So, NULL=spezifisches Datum',
            specific_date   DATE          DEFAULT NULL   COMMENT 'Für Einzeltage / Urlaub',
            start_time      TIME          NOT NULL,
            end_time        TIME          NOT NULL,
            is_blocked      TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1 = nicht buchbar (Urlaub)',
            created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_provider (provider_id),
            INDEX idx_day      (day_of_week),
            INDEX idx_date     (specific_date),
            CONSTRAINT fk_avail_provider FOREIGN KEY (provider_id)
                REFERENCES {$p}booking_providers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 4. Buchungen
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}bookings (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            provider_id     INT UNSIGNED  NOT NULL,
            service_id      INT UNSIGNED  DEFAULT NULL,
            user_id         INT UNSIGNED  DEFAULT NULL  COMMENT 'FK buchender Nutzer',
            contact_submission_id INT UNSIGNED DEFAULT NULL COMMENT 'FK → contact_submissions.id',
            customer_name   VARCHAR(255)  NOT NULL,
            customer_email  VARCHAR(255)  NOT NULL,
            customer_phone  VARCHAR(50)   DEFAULT NULL,
            booking_date    DATE          NOT NULL,
            start_time      TIME          NOT NULL,
            end_time        TIME          NOT NULL,
            duration_min    INT UNSIGNED  NOT NULL DEFAULT 60,
            location_type   VARCHAR(30)   NOT NULL DEFAULT 'online',
            meeting_url     VARCHAR(500)  DEFAULT NULL,
            price_cents     INT UNSIGNED  NOT NULL DEFAULT 0,
            currency        VARCHAR(10)   NOT NULL DEFAULT 'EUR',
            status          VARCHAR(30)   NOT NULL DEFAULT 'pending'
                            COMMENT 'pending|confirmed|cancelled|completed|no_show',
            notes           TEXT          DEFAULT NULL,
            internal_notes  TEXT          DEFAULT NULL,
            ical_uid        VARCHAR(255)  DEFAULT NULL,
            reminder_sent   TINYINT(1)    NOT NULL DEFAULT 0,
            cancelled_at    TIMESTAMP     NULL DEFAULT NULL,
            confirmed_at    TIMESTAMP     NULL DEFAULT NULL,
            completed_at    TIMESTAMP     NULL DEFAULT NULL,
            created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_provider   (provider_id),
            INDEX idx_service    (service_id),
            INDEX idx_user       (user_id),
            INDEX idx_date       (booking_date),
            INDEX idx_status     (status),
            INDEX idx_email      (customer_email),
            INDEX idx_contact_sub (contact_submission_id),
            CONSTRAINT fk_booking_provider FOREIGN KEY (provider_id)
                REFERENCES {$p}booking_providers(id) ON DELETE CASCADE,
            CONSTRAINT fk_booking_service  FOREIGN KEY (service_id)
                REFERENCES {$p}booking_services(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 5. Booking-Meta (flexibel)
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}booking_meta (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            booking_id      INT UNSIGNED  NOT NULL,
            meta_key        VARCHAR(100)  NOT NULL,
            meta_value      TEXT          DEFAULT NULL,
            UNIQUE KEY idx_bm_unique (booking_id, meta_key),
            INDEX idx_booking (booking_id),
            INDEX idx_key     (meta_key),
            CONSTRAINT fk_bm_booking FOREIGN KEY (booking_id)
                REFERENCES {$p}bookings(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 6. Plugin-Einstellungen
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}booking_settings (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key     VARCHAR(100)  NOT NULL,
            setting_value   TEXT          DEFAULT NULL,
            UNIQUE KEY idx_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /* ================================================================== */
    /*  Seeds                                                              */
    /* ================================================================== */

    private static function seed_defaults(): void
    {
        $db = \CMS\Database::instance();
        $p  = $db->getPrefix();

        $defaults = [
            'admin_email'          => '',
            'from_name'            => defined('SITE_NAME') ? SITE_NAME : '365CMS',
            'from_email'           => '',
            'default_duration'     => '60',
            'default_buffer'       => '15',
            'default_timezone'     => 'Europe/Berlin',
            'default_currency'     => 'EUR',
            'booking_advance_min'  => '1',
            'booking_advance_max'  => '90',
            'cancellation_hours'   => '24',
            'auto_confirm'         => '0',
            'send_reminders'       => '1',
            'reminder_hours'       => '24',
            'primary_color'        => '#3b82f6',
        ];

        $stmt = $db->prepare(
            "INSERT IGNORE INTO {$p}booking_settings (setting_key, setting_value) VALUES (?, ?)"
        );
        foreach ($defaults as $k => $v) {
            $stmt->execute([$k, $v]);
        }
    }

    /* ================================================================== */
    /*  Versions-Helfer                                                    */
    /* ================================================================== */

    private static function get_stored_version(): string
    {
        try {
            $db   = \CMS\Database::instance();
            $stmt = $db->prepare(
                "SELECT setting_value FROM {$db->getPrefix()}settings WHERE setting_key = ?"
            );
            $stmt->execute(['booking_db_version']);
            return (string) ($stmt->fetchColumn() ?: '0');
        } catch (\Throwable $e) {
            return '0';
        }
    }

    private static function store_db_version(string $version): void
    {
        try {
            $db = \CMS\Database::instance();
            $p  = $db->getPrefix();
            $db->prepare(
                "INSERT INTO {$p}settings (setting_key, setting_value)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            )->execute(['booking_db_version', $version]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private static function log_install_error(\Throwable $e): void
    {
        error_log('CMS Booking installer skipped: ' . $e->getMessage());
    }
}
