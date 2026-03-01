<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Datenbank-Installation & Migration
 *
 * Erstellt alle Tabellen für den Job Profile Generator.
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
class CMS_JPG_Installer
{
    // ── Installation ─────────────────────────────────────────────────────────

    public static function install(): void
    {
        self::create_tables();
        self::maybe_alter_tables();
        self::seed_system_data();
        self::extend_subscription_plans();
        self::store_db_version();
    }

    public static function maybe_install(): void
    {
        try {
            $db  = \CMS\Database::instance();
            $p   = $db->prefix();
            $pdo = $db->getPdo();

            $stmt   = $pdo->query(
                "SELECT COUNT(*) FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                 AND table_name = '{$p}jpg_profiles'"
            );
            $exists = (int) $stmt->fetchColumn();

            if (!$exists) {
                self::install();
                return;
            }

            $current = self::get_stored_version();
            if ($current === '0' || version_compare($current, JPG_DB_VERSION, '<')) {
                self::install();
            }
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Installer::maybe_install() error: ' . $e->getMessage());
        }
    }

    private static function get_stored_version(): string
    {
        try {
            $db  = \CMS\Database::instance();
            $row = $db->get_row(
                "SELECT option_value FROM {$db->getPrefix()}settings
                 WHERE option_name = 'jpg_db_version'",
                []
            );
            return $row ? (string) $row->option_value : '0';
        } catch (\Throwable $e) {
            return '0';
        }
    }

    private static function store_db_version(): void
    {
        try {
            $db = \CMS\Database::instance();
            $p  = $db->getPrefix();
            $pdo = $db->getPdo();
            $pdo->exec(
                "INSERT INTO {$p}settings (option_name, option_value)
                 VALUES ('jpg_db_version', '" . JPG_DB_VERSION . "')
                 ON DUPLICATE KEY UPDATE option_value = '" . JPG_DB_VERSION . "'"
            );
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Installer::store_db_version() error: ' . $e->getMessage());
        }
    }

    // ── Subscription-Integration ──────────────────────────────────────────────

    /**
     * Erweitert die Core-Tabelle subscription_plans um Plugin-spezifische Spalten.
     * Verwendet ALTER TABLE ... ADD COLUMN IF NOT EXISTS-Logik.
     *
     * @since 0.0.1
     */
    private static function extend_subscription_plans(): void
    {
        try {
            $db  = \CMS\Database::instance();
            $p   = $db->getPrefix();
            $pdo = $db->getPdo();

            // Prüfe ob subscription_plans-Tabelle existiert
            $stmt = $pdo->query(
                "SELECT COUNT(*) FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                 AND table_name = '{$p}subscription_plans'"
            );
            if ((int) $stmt->fetchColumn() === 0) {
                return; // Subscription-System nicht installiert
            }

            // Spalten die wir brauchen: [column_name => column_definition]
            $columns = [
                'limit_job_profiles'       => "INT DEFAULT -1 COMMENT 'Job-Profile Limit: -1 = unbegrenzt'",
                'plugin_job_profile_generator' => "BOOLEAN DEFAULT 1 COMMENT 'Zugriff auf Job Profile Generator'",
                'feature_whitelabel_jobs'  => "BOOLEAN DEFAULT 0 COMMENT 'Whitelabel Job-Seiten'",
            ];

            // feature_custom_branding existiert bereits im Core, nicht nochmal anlegen

            foreach ($columns as $colName => $colDef) {
                $check = $pdo->prepare(
                    "SELECT COUNT(*) FROM information_schema.columns
                     WHERE table_schema = DATABASE()
                     AND table_name = ?
                     AND column_name = ?"
                );
                $check->execute(["{$p}subscription_plans", $colName]);

                if ((int) $check->fetchColumn() === 0) {
                    $pdo->exec("ALTER TABLE {$p}subscription_plans ADD COLUMN {$colName} {$colDef}");
                }
            }
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Installer::extend_subscription_plans() error: ' . $e->getMessage());
        }
    }

    // ── Tabellen ──────────────────────────────────────────────────────────────

    /**
     * Idempotente ALTER TABLE Migrationen für bestehende Installationen.
     * Wird sowohl beim Erst-Install als auch beim Update ausgeführt.
     * Sichere Logik: prüft pro Spalte via information_schema, ob sie existiert.
     *
     * @since 0.9.3
     */
    private static function maybe_alter_tables(): void
    {
        try {
            $db  = \CMS\Database::instance();
            $p   = $db->getPrefix();
            $pdo = $db->getPdo();

            $helper = static function (
                \PDO $pdo, string $table, string $column, string $definition
            ): void {
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM information_schema.columns
                     WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?"
                );
                $stmt->execute([$table, $column]);
                if ((int) $stmt->fetchColumn() === 0) {
                    $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
                }
            };

            // ── jpg_applications: Telefonnummer des Bewerbers ─────────────────
            $helper(
                $pdo, "{$p}jpg_applications", 'applicant_phone',
                "VARCHAR(50) DEFAULT NULL COMMENT 'Telefonnummer des Bewerbers (optional)'"
            );

            // ── jpg_applications: user_id – Verknüpfung mit registriertem Benutzer ──
            $helper(
                $pdo, "{$p}jpg_applications", 'user_id',
                "INT UNSIGNED DEFAULT NULL COMMENT 'Verknüpfter CMS-Benutzer' AFTER job_id"
            );
            // Index für user_id
            try {
                $idxCheck = $pdo->prepare(
                    "SELECT COUNT(*) FROM information_schema.statistics
                     WHERE table_schema = DATABASE() AND table_name = ? AND index_name = 'idx_user'"
                );
                $idxCheck->execute(["{$p}jpg_applications"]);
                if ((int) $idxCheck->fetchColumn() === 0) {
                    $pdo->exec("ALTER TABLE {$p}jpg_applications ADD KEY idx_user (user_id)");
                }
            } catch (\Throwable $e) { /* Index existiert möglicherweise bereits */ }

            // ── jpg_company_settings: E-Mail-Template-Spalten ─────────────────
            // Stellt sicher, dass ALL jobs_page_* Spalten existieren (ältere Installationen)
            $csFields = [
                'jobs_page_title'            => "VARCHAR(255) NOT NULL DEFAULT ''",
                'jobs_page_intro'            => "TEXT NULL",
                'jobs_page_contact_email'    => "VARCHAR(255) NOT NULL DEFAULT ''",
                'jobs_page_show_salary'      => "TINYINT(1) NOT NULL DEFAULT 1",
                'jobs_page_enabled'          => "TINYINT(1) NOT NULL DEFAULT 1",
                'email_tpl_accepted_subject' => "VARCHAR(500) DEFAULT NULL",
                'email_tpl_accepted_body'    => "TEXT DEFAULT NULL",
                'email_tpl_rejected_subject' => "VARCHAR(500) DEFAULT NULL",
                'email_tpl_rejected_body'    => "TEXT DEFAULT NULL",
                'email_sender_name'          => "VARCHAR(255) DEFAULT NULL",
            ];
            foreach ($csFields as $col => $def) {
                $helper($pdo, "{$p}jpg_company_settings", $col, $def);
            }

        } catch (\Throwable $e) {
            error_log('CMS_JPG_Installer::maybe_alter_tables() error: ' . $e->getMessage());
        }
    }

    private static function create_tables(): void
    {
        $db  = \CMS\Database::instance();
        $p   = $db->getPrefix();
        $pdo = $db->getPdo();

        $charset = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

        // Haupt-Profile-Tabelle
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_profiles (
            id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title           VARCHAR(255) NOT NULL DEFAULT '',
            slug            VARCHAR(255) NOT NULL DEFAULT '',
            job_category_id INT UNSIGNED DEFAULT NULL,
            status          ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
            summary         TEXT,
            description     LONGTEXT,
            location        VARCHAR(255) DEFAULT '',
            employment_type ENUM('fulltime','parttime','freelance','internship','mini') NOT NULL DEFAULT 'fulltime',
            experience_level ENUM('entry','junior','mid','senior','lead','executive') NOT NULL DEFAULT 'mid',
            salary_min      DECIMAL(10,2) UNSIGNED DEFAULT NULL,
            salary_max      DECIMAL(10,2) UNSIGNED DEFAULT NULL,
            salary_currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
            remote_option   ENUM('onsite','hybrid','remote') NOT NULL DEFAULT 'onsite',
            created_by      INT UNSIGNED NOT NULL DEFAULT 0,
            updated_by      INT UNSIGNED NOT NULL DEFAULT 0,
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            published_at    DATETIME DEFAULT NULL,
            archived_at     DATETIME DEFAULT NULL,
            sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            views           INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uk_slug (slug),
            KEY idx_status (status),
            KEY idx_category (job_category_id)
        ) ENGINE=InnoDB {$charset};");

        // Aufgaben (Tasks)
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_profile_tasks (
            id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            profile_id INT UNSIGNED NOT NULL,
            task_text  VARCHAR(500) NOT NULL DEFAULT '',
            sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_profile (profile_id)
        ) ENGINE=InnoDB {$charset};");

        // Anforderungen (Requirements)
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_profile_requirements (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            profile_id  INT UNSIGNED NOT NULL,
            req_text    VARCHAR(500) NOT NULL DEFAULT '',
            req_type    ENUM('must','nice','optional') NOT NULL DEFAULT 'must',
            sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_profile (profile_id)
        ) ENGINE=InnoDB {$charset};");

        // Benefits (Verknüpfung Profile ↔ Benefit-Katalog)
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_profile_benefits (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            profile_id  INT UNSIGNED NOT NULL,
            benefit_id  INT UNSIGNED NOT NULL,
            sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uk_profile_benefit (profile_id, benefit_id),
            KEY idx_profile (profile_id)
        ) ENGINE=InnoDB {$charset};");

        // Textbausteine
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_text_modules (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            category    VARCHAR(100) NOT NULL DEFAULT 'general',
            title       VARCHAR(255) NOT NULL DEFAULT '',
            content     TEXT NOT NULL,
            tags        VARCHAR(500) DEFAULT '',
            usage_count INT UNSIGNED NOT NULL DEFAULT 0,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_category (category)
        ) ENGINE=InnoDB {$charset};");

        // Skill-Matrix
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_skills (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_name  VARCHAR(100) NOT NULL DEFAULT '',
            skill_name  VARCHAR(255) NOT NULL DEFAULT '',
            description VARCHAR(500) DEFAULT '',
            sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_group (group_name)
        ) ENGINE=InnoDB {$charset};");

        // Profil ↔ Skills Verknüpfung
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_profile_skills (
            id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            profile_id INT UNSIGNED NOT NULL,
            skill_id   INT UNSIGNED NOT NULL,
            level      ENUM('basic','intermediate','advanced','expert') NOT NULL DEFAULT 'intermediate',
            sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uk_profile_skill (profile_id, skill_id)
        ) ENGINE=InnoDB {$charset};");

        // Benefit-Katalog
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_benefits (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_name  VARCHAR(100) NOT NULL DEFAULT '',
            title       VARCHAR(255) NOT NULL DEFAULT '',
            description VARCHAR(500) DEFAULT '',
            icon        VARCHAR(10)  DEFAULT '✓',
            sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            active      TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY idx_group (group_name),
            KEY idx_active (active)
        ) ENGINE=InnoDB {$charset};");

        // Job-Kategorien
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_job_categories (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name        VARCHAR(255) NOT NULL DEFAULT '',
            slug        VARCHAR(255) NOT NULL DEFAULT '',
            description VARCHAR(500) DEFAULT '',
            color       VARCHAR(7)   NOT NULL DEFAULT '#3b82f6',
            sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            active      TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY uk_slug (slug)
        ) ENGINE=InnoDB {$charset};");

        // Kategorie-Benefits (Vererbungsebene 2)
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_category_benefits (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            category_id INT UNSIGNED NOT NULL,
            benefit_id  INT UNSIGNED NOT NULL,
            sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uk_cat_benefit (category_id, benefit_id),
            KEY idx_category (category_id)
        ) ENGINE=InnoDB {$charset};");

        // PDF/Web-Templates
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_templates (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            type        ENUM('pdf','web','email') NOT NULL DEFAULT 'web',
            name        VARCHAR(255) NOT NULL DEFAULT '',
            content     LONGTEXT,
            css         TEXT,
            is_default  TINYINT(1) NOT NULL DEFAULT 0,
            active      TINYINT(1) NOT NULL DEFAULT 1,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_type (type)
        ) ENGINE=InnoDB {$charset};");

        // Einstellungen
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_settings (
            id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            setting_key   VARCHAR(100) NOT NULL DEFAULT '',
            setting_value LONGTEXT,
            PRIMARY KEY (id),
            UNIQUE KEY uk_key (setting_key)
        ) ENGINE=InnoDB {$charset};");

        // Statistiken / Logs
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_stats (
            id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            profile_id INT UNSIGNED NOT NULL,
            action     VARCHAR(50) NOT NULL DEFAULT '',
            user_id    INT UNSIGNED DEFAULT NULL,
            ip_hash    VARCHAR(64) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_profile (profile_id),
            KEY idx_action (action),
            KEY idx_date (created_at)
        ) ENGINE=InnoDB {$charset};");

        // Bewerbungen (Applications) – Member-Bereich
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_applications (
            id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_id          INT UNSIGNED NOT NULL,
            user_id         INT UNSIGNED DEFAULT NULL COMMENT 'Verknüpfter CMS-Benutzer',
            applicant_name  VARCHAR(255) NOT NULL DEFAULT '',
            applicant_email VARCHAR(255) NOT NULL DEFAULT '',
            cover_letter    TEXT,
            cv_file_path    VARCHAR(500) DEFAULT NULL,
            cv_file_token   VARCHAR(64)  DEFAULT NULL COMMENT 'Sicherer Download-Token',
            applicant_phone VARCHAR(50)  DEFAULT NULL COMMENT 'Telefonnummer des Bewerbers (optional)',
            status          ENUM('new','reviewing','accepted','rejected') NOT NULL DEFAULT 'new',
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_job (job_id),
            KEY idx_user (user_id),
            KEY idx_status (status),
            KEY idx_token (cv_file_token)
        ) ENGINE=InnoDB {$charset};");

        // ── Workflow: Schritt-Definitionen ────────────────────────────────────
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_workflow_steps (
            id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
            sort_order         SMALLINT UNSIGNED NOT NULL DEFAULT 10,
            step_name          VARCHAR(255) NOT NULL DEFAULT '',
            approver_role      VARCHAR(100) NOT NULL DEFAULT 'admin',
            allow_self_approve TINYINT(1)   NOT NULL DEFAULT 0
                               COMMENT '1 = Member kann diesen Schritt selbst abschließen',
            notification_email VARCHAR(255) DEFAULT NULL
                               COMMENT 'Optionale E-Mail-Adresse für Benachrichtigungen',
            active             TINYINT(1)   NOT NULL DEFAULT 1,
            created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_order (sort_order),
            KEY idx_active (active)
        ) ENGINE=InnoDB {$charset};");

        // ── Workflow: History / Audit-Log ─────────────────────────────────────
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_workflow_history (
            id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            profile_id INT UNSIGNED NOT NULL,
            step_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            action     ENUM('submitted','approved','rejected','reset') NOT NULL DEFAULT 'submitted',
            actor_id   INT UNSIGNED NOT NULL DEFAULT 0,
            note       TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_profile (profile_id),
            KEY idx_actor (actor_id),
            KEY idx_date (created_at)
        ) ENGINE=InnoDB {$charset};");

        // Anforderungs-Liste (eigener Katalog – unabhängig von Skill-Matrix)
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_requirement_items (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_name  VARCHAR(100) NOT NULL DEFAULT '',
            title       VARCHAR(255) NOT NULL DEFAULT '',
            sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_group (group_name)
        ) ENGINE=InnoDB {$charset};");

        // Unternehmens-Standard-Benefits (cms-companies Integration)
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_company_default_benefits (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id  INT UNSIGNED NOT NULL,
            benefit_id  INT UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uk_company_benefit (company_id, benefit_id),
            KEY idx_company (company_id)
        ) ENGINE=InnoDB {$charset};");

        // ── Phase 9: Team-Genehmiger (Mandanten-eigene Workflow-Approver) ─────
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_team_approvers (
            id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id       INT UNSIGNED NOT NULL COMMENT 'Firma aus cms_companies',
            approver_user_id INT UNSIGNED NOT NULL COMMENT 'CMS-User der als Genehmiger agiert',
            created_by       INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Wer hat diesen Eintrag angelegt',
            created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_company_approver (company_id, approver_user_id),
            KEY idx_company (company_id),
            KEY idx_approver (approver_user_id)
        ) ENGINE=InnoDB {$charset};");

        // ── Abteilungen (Departments) je Firma ─────────────────────────────────
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_departments (
            id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id   INT UNSIGNED NOT NULL COMMENT 'cms_companies.id',
            name         VARCHAR(255) NOT NULL DEFAULT '',
            description  VARCHAR(500) DEFAULT '',
            sort_order   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_by   INT UNSIGNED NOT NULL DEFAULT 0,
            created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_company (company_id)
        ) ENGINE=InnoDB {$charset};");

        // Abteilung ↔ Benefit-Katalog (Vererbung)
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_department_benefits (
            id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            department_id INT UNSIGNED NOT NULL,
            benefit_id    INT UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uk_dept_benefit (department_id, benefit_id),
            KEY idx_dept (department_id)
        ) ENGINE=InnoDB {$charset};");

        // Abteilung ↔ Anforderungs-Einträge (Vererbung)
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_department_requirements (
            id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            department_id INT UNSIGNED NOT NULL,
            req_item_id   INT UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uk_dept_req (department_id, req_item_id),
            KEY idx_dept (department_id)
        ) ENGINE=InnoDB {$charset};");

        // Firmen-Einstellungen (jobs_page_url etc.)
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_company_settings (
            company_id                  INT UNSIGNED NOT NULL,
            jobs_page_url               VARCHAR(500) NOT NULL DEFAULT '',
            jobs_page_title             VARCHAR(255) NOT NULL DEFAULT '',
            jobs_page_intro             TEXT,
            jobs_page_contact_email     VARCHAR(255) NOT NULL DEFAULT '',
            jobs_page_show_salary       TINYINT(1) NOT NULL DEFAULT 1,
            jobs_page_enabled           TINYINT(1) NOT NULL DEFAULT 1,
            email_tpl_accepted_subject  VARCHAR(500) DEFAULT NULL COMMENT 'Betreff der Zusage-Mail',
            email_tpl_accepted_body     TEXT         DEFAULT NULL COMMENT 'Text der Zusage-Mail (Platzhalter: {name},{stelle},{firma})',
            email_tpl_rejected_subject  VARCHAR(500) DEFAULT NULL COMMENT 'Betreff der Absage-Mail',
            email_tpl_rejected_body     TEXT         DEFAULT NULL COMMENT 'Text der Absage-Mail',
            email_sender_name           VARCHAR(255) DEFAULT NULL COMMENT 'Absender-Name in Bewerbungs-Mails',
            updated_at                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (company_id)
        ) ENGINE=InnoDB {$charset};");

        // ── Phase 9: Admin-Zugriffs-Log (DSGVO-Audit) ─────────────────────────
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}jpg_admin_access_log (
            id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
            admin_user_id     INT UNSIGNED NOT NULL,
            target_company_id INT UNSIGNED DEFAULT NULL COMMENT 'Firma die eingesehen wurde',
            target_profile_id INT UNSIGNED DEFAULT NULL COMMENT 'Profil das eingesehen wurde',
            action            VARCHAR(50) NOT NULL DEFAULT 'view'
                              COMMENT 'view_company|view_profile|edit_company|edit_profile',
            ip_addr           VARCHAR(45) DEFAULT NULL,
            created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_admin (admin_user_id),
            KEY idx_company (target_company_id),
            KEY idx_date (created_at)
        ) ENGINE=InnoDB {$charset};");

        // ── Workflow-Spalten zu jpg_profiles hinzufügen (idempotent) ─────────
        self::maybe_add_columns($pdo, $p);
    }

    /**
     * Fügt fehlende Spalten zu jpg_profiles hinzu (ALTER TABLE idempotent per IF NOT EXISTS-Logik).
     */
    private static function maybe_add_columns(\PDO $pdo, string $p): void
    {
        $columnsToAdd = [
            'workflow_status'       => "VARCHAR(50) NOT NULL DEFAULT 'none' COMMENT 'none|pending|approved|rejected'",
            'workflow_step'         => "SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'sort_order des aktuellen Workflow-Schritts'",
            'company_id'            => "INT UNSIGNED DEFAULT NULL COMMENT 'Verknüpfte Firma aus cms_companies'",
            'excluded_benefit_ids'  => "TEXT DEFAULT NULL COMMENT 'JSON-Array von Benefit-IDs die für diesen Job deaktiviert sind'",
            'is_private'            => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Phase 9: Admin-Blindheits-Modus – 1 = Profil in Admin-Übersicht standardmäßig verborgen'",
            'show_in_listing'       => "TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'In öffentlicher Job-Listing-Seite (/jobs) anzeigen – 0 = verborgen'",
            'department_id'         => "INT UNSIGNED DEFAULT NULL COMMENT 'Verknüpfte Abteilung aus jpg_departments'",
        ];

        foreach ($columnsToAdd as $column => $definition) {
            try {
                $check = $pdo->prepare(
                    "SELECT COUNT(*) FROM information_schema.columns
                     WHERE table_schema = DATABASE()
                       AND table_name   = ?
                       AND column_name  = ?"
                );
                $check->execute(["{$p}jpg_profiles", $column]);
                if ((int) $check->fetchColumn() === 0) {
                    $pdo->exec("ALTER TABLE {$p}jpg_profiles ADD COLUMN {$column} {$definition}");
                }
            } catch (\Throwable $e) {
                error_log("CMS_JPG_Installer::maybe_add_columns() [{$column}]: " . $e->getMessage());
            }
        }

        // Spalten für jpg_requirement_items erweitern (req_type, description, icon)
        $reqColumns = [
            'req_type'    => "ENUM('must','nice','optional') NOT NULL DEFAULT 'must' COMMENT 'Pflicht|Wünschenswert|Optional'",
            'description' => "VARCHAR(500) DEFAULT '' COMMENT 'Optionale Beschreibung'",
            'icon'        => "VARCHAR(10)  DEFAULT '' COMMENT 'Emoji-Icon'",
        ];
        foreach ($reqColumns as $column => $definition) {
            try {
                $check = $pdo->prepare(
                    "SELECT COUNT(*) FROM information_schema.columns
                     WHERE table_schema = DATABASE()
                       AND table_name   = ?
                       AND column_name  = ?"
                );
                $check->execute(["{$p}jpg_requirement_items", $column]);
                if ((int) $check->fetchColumn() === 0) {
                    $pdo->exec("ALTER TABLE {$p}jpg_requirement_items ADD COLUMN {$column} {$definition}");
                }
            } catch (\Throwable $e) {
                error_log("CMS_JPG_Installer::maybe_add_columns() [req_items.{$column}]: " . $e->getMessage());
            }
        }

        // jpg_profile_requirements.req_type: ENUM auf 'optional' erweitern
        try {
            $check = $pdo->query(
                "SELECT COLUMN_TYPE FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name   = '{$p}jpg_profile_requirements'
                   AND column_name  = 'req_type'"
            );
            $colType = $check ? (string) $check->fetchColumn() : '';
            if ($colType !== '' && stripos($colType, 'optional') === false) {
                $pdo->exec(
                    "ALTER TABLE {$p}jpg_profile_requirements
                     MODIFY COLUMN req_type ENUM('must','nice','optional') NOT NULL DEFAULT 'must'"
                );
            }
        } catch (\Throwable $e) {
            error_log("CMS_JPG_Installer::maybe_add_columns() [profile_requirements.req_type]: " . $e->getMessage());
        }

        // Spalten für jpg_company_settings erweitern (v0.9.1 / DB_VERSION 6)
        $companySettingsCols = [
            'jobs_page_title'         => "VARCHAR(200) NOT NULL DEFAULT '' COMMENT 'Seitentitel der öffentlichen Jobs-Seite'",
            'jobs_page_intro'         => "TEXT COMMENT 'Einleitungstext der öffentlichen Jobs-Seite'",
            'jobs_page_contact_email' => "VARCHAR(200) NOT NULL DEFAULT '' COMMENT 'Kontakt-E-Mail auf Jobs-Seite'",
            'jobs_page_show_salary'   => "TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Gehalt auf Jobs-Seite anzeigen'",
            'jobs_page_enabled'       => "TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Interne Jobs-Seite (/jobs) aktiv'",
        ];
        foreach ($companySettingsCols as $column => $definition) {
            try {
                $check = $pdo->prepare(
                    "SELECT COUNT(*) FROM information_schema.columns
                     WHERE table_schema = DATABASE()
                       AND table_name   = ?
                       AND column_name  = ?"
                );
                $check->execute(["{$p}jpg_company_settings", $column]);
                if ((int) $check->fetchColumn() === 0) {
                    $pdo->exec("ALTER TABLE {$p}jpg_company_settings ADD COLUMN {$column} {$definition}");
                }
            } catch (\Throwable $e) {
                error_log("CMS_JPG_Installer::maybe_add_columns() [company_settings.{$column}]: " . $e->getMessage());
            }
        }
    }

    // ── Seed-Daten ────────────────────────────────────────────────────────────

    private static function seed_system_data(): void
    {
        try {
            $db  = \CMS\Database::instance();
            $p   = $db->getPrefix();
            $pdo = $db->getPdo();

            $countCat = (int) $pdo->query("SELECT COUNT(*) FROM {$p}jpg_job_categories")->fetchColumn();
            if ($countCat === 0) {
                self::seed_categories($pdo, $p);
            }

            $countBen = (int) $pdo->query("SELECT COUNT(*) FROM {$p}jpg_benefits")->fetchColumn();
            if ($countBen === 0) {
                self::seed_benefits($pdo, $p);
            }

            $countSkills = (int) $pdo->query("SELECT COUNT(*) FROM {$p}jpg_skills")->fetchColumn();
            if ($countSkills === 0) {
                self::seed_skills($pdo, $p);
            }

            $countTm = (int) $pdo->query("SELECT COUNT(*) FROM {$p}jpg_text_modules")->fetchColumn();
            if ($countTm === 0) {
                self::seed_text_modules($pdo, $p);
            }

            $countTpl = (int) $pdo->query("SELECT COUNT(*) FROM {$p}jpg_templates")->fetchColumn();
            if ($countTpl === 0) {
                self::seed_templates($pdo, $p);
            }

            $countReq = (int) $pdo->query("SELECT COUNT(*) FROM {$p}jpg_requirement_items")->fetchColumn();
            if ($countReq === 0) {
                self::seed_requirement_items($pdo, $p);
            }

        } catch (\Throwable $e) {
            error_log('CMS_JPG_Installer::seed_system_data() error: ' . $e->getMessage());
        }
    }

    // ── Kategorie-Seed ────────────────────────────────────────────────────────

    private static function seed_categories(\PDO $pdo, string $p): void
    {
        $categories = [
            // Farbe: blau=IT, amber=Marketing, grün=Vertrieb, indigo=Finanzen,
            //        pink=HR, teal=Ingenieur, rot=Gesundheit, lila=Recht,
            //        orange=Bau, cyan=Logistik, ...
            ['IT & Software',                    'it-software',                '#3b82f6'],
            ['Marketing & Kommunikation',         'marketing-kommunikation',   '#f59e0b'],
            ['Vertrieb & Kundenservice',          'vertrieb-kundenservice',    '#10b981'],
            ['Finanzen & Controlling',            'finanzen-controlling',      '#6366f1'],
            ['Personal & HR',                    'personal-hr',               '#ec4899'],
            ['Ingenieurwesen & Technik',          'ingenieurwesen-technik',    '#14b8a6'],
            ['Gesundheit & Medizin',              'gesundheit-medizin',        '#ef4444'],
            ['Recht & Notariat',                 'recht-notariat',            '#8b5cf6'],
            ['Bauwesen & Architektur',            'bauwesen-architektur',      '#f97316'],
            ['Straßenbau & Tiefbau',             'strassenbau-tiefbau',       '#78716c'],
            ['Transport & Logistik',              'transport-logistik',        '#06b6d4'],
            ['Banken & Finanzdienstleistungen',   'banken-finanzdienstleistungen', '#4f46e5'],
            ['Versicherungen',                   'versicherungen',            '#7c3aed'],
            ['Immobilien & Facility Management', 'immobilien-facility',       '#0d9488'],
            ['Maschinenbau & Anlagenbau',         'maschinenbau-anlagenbau',   '#d97706'],
            ['Automobil & Mobilität',             'automobil-mobilitaet',      '#1d4ed8'],
            ['Energie & Umwelt',                 'energie-umwelt',            '#16a34a'],
            ['Pharma & Biotechnologie',           'pharma-biotechnologie',     '#db2777'],
            ['Handwerk & Montage',               'handwerk-montage',          '#92400e'],
            ['Einzelhandel & E-Commerce',         'einzelhandel-ecommerce',    '#059669'],
            ['Gastronomie & Hotellerie',          'gastronomie-hotellerie',    '#b45309'],
            ['Bildung & Forschung',               'bildung-forschung',         '#0369a1'],
            ['Medien & Kreativwirtschaft',         'medien-kreativwirtschaft',  '#9333ea'],
            ['Telekommunikation',                 'telekommunikation',         '#0284c7'],
            ['Öffentlicher Dienst & Verwaltung', 'oeffentlicher-dienst',      '#475569'],
            ['Soziales & Non-Profit',             'soziales-nonprofit',        '#0f766e'],
            ['Lebensmittel & FMCG',              'lebensmittel-fmcg',         '#65a30d'],
            ['Luft- & Raumfahrt',                'luft-raumfahrt',            '#1e40af'],
            ['Land- & Forstwirtschaft',           'land-forstwirtschaft',      '#4d7c0f'],
            ['Sicherheit & Ordnung',              'sicherheit-ordnung',        '#be123c'],
        ];

        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO {$p}jpg_job_categories (name, slug, color, sort_order, active)
             VALUES (?, ?, ?, ?, 1)"
        );
        foreach ($categories as $i => [$name, $slug, $color]) {
            $stmt->execute([$name, $slug, $color, $i * 10]);
        }
    }

    // ── Benefit-Seed ──────────────────────────────────────────────────────────

    private static function seed_benefits(\PDO $pdo, string $p): void
    {
        $benefits = [
            // ─ Vergütung (14) ─
            ['Vergütung',       'Wettbewerbsfähiges Gehalt',         '💰'],
            ['Vergütung',       '13. Monatsgehalt',                  '💰'],
            ['Vergütung',       'Leistungsbonus / Prämien',          '🏆'],
            ['Vergütung',       'Provisionsmodell',                  '📈'],
            ['Vergütung',       'Gewinnbeteiligung',                 '💹'],
            ['Vergütung',       'Inflationsausgleich',               '📊'],
            ['Vergütung',       'Urlaubs- & Weihnachtsgeld',         '🎄'],
            ['Vergütung',       'Mitarbeiteraktien / ESOP',          '📊'],
            ['Vergütung',       'Sign-On Bonus',                     '🎁'],
            ['Vergütung',       'Jubiläumsprämien',                  '🏅'],
            ['Vergütung',       'Sonderzahlungen / Boni',            '💵'],
            ['Vergütung',       'Steuerfreie Sachbezüge',            '🎫'],
            ['Vergütung',       'Umzugskostenzuschuss',              '📦'],
            ['Vergütung',       'Mitarbeiterdarlehen',               '🏦'],
            // ─ Mobilität (11) ─
            ['Mobilität',       'Firmenwagen auch privat nutzbar',   '🚗'],
            ['Mobilität',       'JobRad / Fahrradleasing',           '🚴'],
            ['Mobilität',       'Deutschlandticket / ÖPNV-Zuschuss', '🚌'],
            ['Mobilität',       'Kostenloser Parkplatz',             '🅿️'],
            ['Mobilität',       'Fahrtkostenzuschuss',               '🚉'],
            ['Mobilität',       'Car-Allowance',                     '💳'],
            ['Mobilität',       'BahnCard 50 / 100',                 '🚄'],
            ['Mobilität',       'E-Auto Ladesäulen am Standort',     '⚡'],
            ['Mobilität',       'Poolfahrzeuge',                     '🚐'],
            ['Mobilität',       'E-Scooter Zuschuss',                '🛴'],
            ['Mobilität',       'Shuttleservice',                    '🚐'],
            // ─ Work-Life (16) ─
            ['Work-Life',       'Flexible Arbeitszeiten',            '⏰'],
            ['Work-Life',       'Home-Office Möglichkeit',           '🏠'],
            ['Work-Life',       'Vollständiges Remote-Work',         '💻'],
            ['Work-Life',       '30 Tage Urlaub',                    '🏖️'],
            ['Work-Life',       'Sabbatical möglich',                '🌍'],
            ['Work-Life',       '4-Tage-Woche',                      '📅'],
            ['Work-Life',       'Kernarbeitszeiten ohne Pflicht',    '🕐'],
            ['Work-Life',       'Gleitzeit',                         '⏱️'],
            ['Work-Life',       'Vertrauensarbeitszeit',             '🤝'],
            ['Work-Life',       'Work from Anywhere',                '🌎'],
            ['Work-Life',       'Geburtstag frei',                   '🎂'],
            ['Work-Life',       'Bildungsurlaub',                    '📖'],
            ['Work-Life',       'Sonderurlaub Ehrenamt',             '🤲'],
            ['Work-Life',       'Freizeitausgleich',                 '⚖️'],
            ['Work-Life',       'Flexible Teilzeitmodelle',          '🕑'],
            ['Work-Life',       'Angehörigenpflege-Freistellung',    '👨‍👩‍👧'],
            // ─ Entwicklung (13) ─
            ['Entwicklung',     'Weiterbildungsbudget (jährlich)',    '📚'],
            ['Entwicklung',     'Interne Schulungen & Workshops',    '🎓'],
            ['Entwicklung',     'Regelmäßige Feedbackgespräche',     '💬'],
            ['Entwicklung',     'Mentoring-Programm',                '🎯'],
            ['Entwicklung',     'Zertifizierungsunterstützung',      '🏅'],
            ['Entwicklung',     'Karrierepfad & Aufstiegschancen',   '📈'],
            ['Entwicklung',     'E-Learning Zugang',                 '💻'],
            ['Entwicklung',     'Sprachkurse',                       '🗣️'],
            ['Entwicklung',     'Fachmessen & Konferenzen',          '🎪'],
            ['Entwicklung',     'Fachbücher-Flatrate',               '📕'],
            ['Entwicklung',     'Innovationsprojekte',               '💡'],
            ['Entwicklung',     'Hackathons',                        '🖥️'],
            ['Entwicklung',     'Job-Rotation',                      '🔄'],
            // ─ Gesundheit (12) ─
            ['Gesundheit',      'Betriebliche Altersvorsorge',       '🛡️'],
            ['Gesundheit',      'Betriebliche Krankenversicherung',  '🏥'],
            ['Gesundheit',      'Betriebssport / Fitnessstudio',     '🏋️'],
            ['Gesundheit',      'Gesundheitsvorsorge & Check-ups',   '🩺'],
            ['Gesundheit',      'Mental-Health-Angebote',            '🧠'],
            ['Gesundheit',      'Bildschirmarbeitsplatzbrille',      '👓'],
            ['Gesundheit',      'Yoga / Meditation',                 '🧘'],
            ['Gesundheit',      'Laufevents & Firmensport',          '🏃'],
            ['Gesundheit',      'Impfangebote',                      '💉'],
            ['Gesundheit',      'Ergonomie-Beratung',                '🪑'],
            ['Gesundheit',      'Betriebsarzt',                      '🩻'],
            ['Gesundheit',      'Massagen am Arbeitsplatz',          '💆'],
            // ─ Soziales (14) ─
            ['Soziales',        'Teamevents & Ausflüge',             '🎉'],
            ['Soziales',        'Betriebskantine / Essenszuschuss',  '🍽️'],
            ['Soziales',        'Obstkorb & Getränke',               '🍎'],
            ['Soziales',        'Betriebskindergarten / -zuschuss',  '👶'],
            ['Soziales',        'Mitarbeiterrabatte',                '🛍️'],
            ['Soziales',        'Firmenevents & Weihnachtsfeier',    '🥂'],
            ['Soziales',        'After-Work-Events',                 '🍻'],
            ['Soziales',        'Familienfest',                      '👨‍👩‍👧‍👦'],
            ['Soziales',        'Gemeinsames Frühstück',             '🥐'],
            ['Soziales',        'Volunteer Days',                    '🤝'],
            ['Soziales',        'Diversity-Initiativen',             '🌈'],
            ['Soziales',        'Hunde im Büro erlaubt',             '🐕'],
            ['Soziales',        'Spielezimmer / Loungebereich',      '🎮'],
            ['Soziales',        'Buddy-Programm Onboarding',         '🤝'],
            // ─ Ausstattung (14) ─
            ['Ausstattung',     'Modernes Arbeitsumfeld',            '🏢'],
            ['Ausstattung',     'Neueste Hardware & Software',       '💻'],
            ['Ausstattung',     'Smartphone zur Privatnutzung',      '📱'],
            ['Ausstattung',     'Ergonomischer Arbeitsplatz',        '🪑'],
            ['Ausstattung',     'Klimatisierte Büros',               '❄️'],
            ['Ausstattung',     'Dachterrasse / Außenbereich',       '🌿'],
            ['Ausstattung',     'Ruheräume / Nap-Pods',              '😴'],
            ['Ausstattung',     'Barista / Kaffeebar',               '☕'],
            ['Ausstattung',     'Freie Gerätewahl (Mac/Windows)',    '🖥️'],
            ['Ausstattung',     'Tablet zur Privatnutzung',          '📲'],
            ['Ausstattung',     'Höhenverstellbare Tische',          '📐'],
            ['Ausstattung',     'Noise-Cancelling Headphones',       '🎧'],
            ['Ausstattung',     'Home-Office Budget',                '💰'],
            ['Ausstattung',     'Internet-Zuschuss',                 '🌐'],
        ];

        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO {$p}jpg_benefits (group_name, title, icon, sort_order, active)
             VALUES (?, ?, ?, ?, 1)"
        );
        foreach ($benefits as $i => [$group, $title, $icon]) {
            $stmt->execute([$group, $title, $icon, $i * 10]);
        }
    }

    // ── Skill-Seed ────────────────────────────────────────────────────────────

    private static function seed_skills(\PDO $pdo, string $p): void
    {
        $skills = [
            // ─ IT & Software ─
            ['IT – Entwicklung',       'PHP 8+ (Laravel, Symfony)'],
            ['IT – Entwicklung',       'JavaScript / TypeScript'],
            ['IT – Entwicklung',       'Python'],
            ['IT – Entwicklung',       'Java / Kotlin'],
            ['IT – Entwicklung',       'C# / .NET'],
            ['IT – Entwicklung',       'Go / Rust'],
            ['IT – Entwicklung',       'SQL & Datenbankdesign'],
            ['IT – Entwicklung',       'REST-API / GraphQL'],
            ['IT – Entwicklung',       'NoSQL (MongoDB, Redis)'],
            ['IT – Entwicklung',       'gRPC & Message Queues'],
            ['IT – Web & Frontend',    'React / Angular / Vue.js'],
            ['IT – Web & Frontend',    'HTML5 / CSS3 / SASS / Tailwind'],
            ['IT – Web & Frontend',    'Node.js / Next.js'],
            ['IT – Mobile',            'Swift / Objective-C (iOS)'],
            ['IT – Mobile',            'Flutter / Dart / React Native'],
            ['IT – Cloud & Infra',     'AWS / Azure / GCP'],
            ['IT – Cloud & Infra',     'Docker / Kubernetes'],
            ['IT – Cloud & Infra',     'CI/CD (GitHub Actions, Jenkins)'],
            ['IT – Cloud & Infra',     'Linux / Bash-Scripting'],
            ['IT – Cloud & Infra',     'Netzwerk & Firewall'],
            ['IT – Cloud & Infra',     'Infrastructure as Code (Terraform, Pulumi)'],
            ['IT – Cloud & Infra',     'Windows Server / Active Directory'],
            ['IT – Daten & KI',        'Generative AI / LLMs'],
            ['IT – Daten & KI',        'Data Engineering (Spark, Kafka)'],
            ['IT – Daten & KI',        'BI & Reporting (Power BI, Tableau)'],
            ['IT – Daten & KI',        'Machine Learning / KI-Frameworks'],
            ['IT – Methodik',          'Agile / Scrum / Kanban'],
            ['IT – Methodik',          'DevOps & GitOps'],
            ['IT – Methodik',          'Clean Code / Code-Reviews'],
            ['IT – Methodik',          'Microservices-Architektur'],
            ['IT – Sicherheit',        'IT-Security / OWASP'],
            ['IT – Sicherheit',        'Penetration Testing'],
            ['IT – Sicherheit',        'DSGVO / Datenschutz'],
            ['IT – Sicherheit',        'IAM / Identity Management'],
            ['IT – Sicherheit',        'ISO 27001 / BSI Grundschutz'],
            // ─ Bauwesen & Architektur ─
            ['Bauwesen – Planung',     'AutoCAD / CAD-Software'],
            ['Bauwesen – Planung',     'BIM (Revit, Allplan, ArchiCAD)'],
            ['Bauwesen – Planung',     'HOAI (Honorarordnung)'],
            ['Bauwesen – Planung',     'VOB / VOL (Vergabeordnung)'],
            ['Bauwesen – Planung',     'Bauleitung & Projektsteuerung'],
            ['Bauwesen – Planung',     'Statik & Tragwerksplanung'],
            ['Bauwesen – Planung',     'TGA-Planung'],
            ['Bauwesen – Kalkulation', 'Kostenplanung & Kalkulation'],
            ['Bauwesen – Kalkulation', 'GAEB / STLB-Bau'],
            ['Bauwesen – Kalkulation', 'Ausschreibung & Vergabe'],
            ['Bauwesen – Kalkulation', 'ORCA AVA'],
            ['Bauwesen – Normen',      'DIN-Normen (Hochbau)'],
            ['Bauwesen – Normen',      'Brandschutz & Sicherheit'],
            ['Bauwesen – Normen',      'Energieeffizienz / EnEV / GEG'],
            ['Bauwesen – Normen',      'SiGeKo (Sicherheitskoordination)'],
            // ─ Straßenbau & Tiefbau ─
            ['Straßenbau – Planung',   'Straßenplanung & Trassenführung'],
            ['Straßenbau – Planung',   'Vermessungstechnik (Totalstation, GNSS)'],
            ['Straßenbau – Planung',   'ASB-Straße (Anweisung Straßenbestandsdaten)'],
            ['Straßenbau – Planung',   'VESTRA INFRA / SoundPlan'],
            ['Straßenbau – Normen',    'ZTV Asphalt / ZTV Beton'],
            ['Straßenbau – Normen',    'RASt / RAL (Straßengestaltung)'],
            ['Straßenbau – Normen',    'Tiefbau DIN 18300 ff.'],
            ['Straßenbau – Betrieb',   'Bauleitplanung & BVWP'],
            ['Straßenbau – Betrieb',   'Lärmschutzplanung'],
            // ─ Transport & Logistik ─
            ['Logistik – Disposition', 'Disposition & Tourenplanung'],
            ['Logistik – Disposition', 'Frachtkosten-Kalkulation'],
            ['Logistik – Disposition', 'Multimodaler Transport (LKW/Bahn/Schiff)'],
            ['Logistik – Systeme',     'SAP TM / SAP EWM'],
            ['Logistik – Systeme',     'Warehouse-Management-Systeme (WMS)'],
            ['Logistik – Systeme',     'TMS (Transportmanagement-Software)'],
            ['Logistik – Systeme',     'SAP MM'],
            ['Logistik – Normen',      'Gefahrgut ADR / IATA / IMDG'],
            ['Logistik – Normen',      'Zoll & Außenhandel (ATLAS)'],
            ['Logistik – Normen',      'Zollabwicklung Import / Export'],
            ['Logistik – Normen',      'ISO 9001 / Lean Logistics'],
            // ─ Finanzen & Controlling ─
            ['Finanzen – Analyse',     'Kreditanalyse & Risikobeurteilung'],
            ['Finanzen – Analyse',     'Bilanzanalyse & Due Diligence'],
            ['Finanzen – Analyse',     'Kapitalmarkt & Wertpapierhandel'],
            ['Finanzen – Analyse',     'Jahres- & Konzernabschluss (HGB / IFRS)'],
            ['Finanzen – Analyse',     'Debitoren- & Kreditorenbuchhaltung'],
            ['Finanzen – Analyse',     'Budgetierung & Forecasting'],
            ['Finanzen – Regulierung', 'KYC / AML (Geldwäscheprävention)'],
            ['Finanzen – Regulierung', 'MaRisk / Basel III/IV'],
            ['Finanzen – Regulierung', 'MiFID II / DSGVO'],
            ['Finanzen – Systeme',     'Bloomberg / Reuters Refinitiv'],
            ['Finanzen – Systeme',     'SAP FI/CO / DATEV / Lexware'],
            ['Finanzen – Systeme',     'Finanzbuchhaltung (HGB / IFRS)'],
            // ─ Recht & Notariat ─
            ['Recht – Gebiete',        'Vertragsrecht & AGB'],
            ['Recht – Gebiete',        'Arbeitsrecht & Betriebsverfassungsrecht'],
            ['Recht – Gebiete',        'Gesellschaftsrecht (GmbH, AG, M&A)'],
            ['Recht – Gebiete',        'Baurecht & Vergaberecht'],
            ['Recht – Gebiete',        'Datenschutzrecht (DSGVO)'],
            ['Recht – Gebiete',        'Strafrecht / OWiG'],
            ['Recht – Gebiete',        'IT-Recht & Intellectual Property'],
            ['Recht – Praxis',         'Prozessführung vor Gericht'],
            ['Recht – Praxis',         'Vertragsgestaltung & -prüfung'],
            ['Recht – Praxis',         'Compliance-Management'],
            ['Recht – Systeme',        'beA (besonderes elektronisches Anwaltspostfach)'],
            ['Recht – Systeme',        'RA-MICRO / DATEV-Kanzlei-Rechnungswesen'],
            // ─ Maschinenbau & Anlagenbau ─
            ['Maschinenbau – Planung', 'CAD / CAM (CATIA, SolidWorks, NX, Inventor)'],
            ['Maschinenbau – Planung', 'FEM-Simulation (ANSYS, Abaqus)'],
            ['Maschinenbau – Planung', 'Strömungssimulation CFD'],
            ['Maschinenbau – Planung', 'Hydraulik & Pneumatik'],
            ['Maschinenbau – Betrieb', 'SPS-Programmierung (Siemens, Beckhoff, CODESYS)'],
            ['Maschinenbau – Betrieb', 'Robotik & Automatisierungstechnik'],
            ['Maschinenbau – Betrieb', 'Predictive Maintenance / IIoT'],
            ['Maschinenbau – Normen',  'CE-Kennzeichnung & Maschinenrichtlinie'],
            ['Maschinenbau – Normen',  'ISO 9001 / IATF 16949'],
            ['Maschinenbau – Normen',  'Six Sigma (Green / Black Belt)'],
            // ─ Energie & Umwelt ─
            ['Energie',                'Photovoltaik & Solarthermie'],
            ['Energie',                'Windenergie & Offshore'],
            ['Energie',                'Energiemanagement (ISO 50001)'],
            ['Energie',                'Netzbetrieb & Energiehandel'],
            ['Energie',                'Power-to-X & Wasserstoff'],
            ['Energie',                'ESG-Reporting & Nachhaltigkeitsmanagement'],
            ['Umwelt',                 'Umweltrecht & Genehmigungsverfahren'],
            ['Umwelt',                 'Abfallwirtschaft & Kreislaufwirtschaft'],
            ['Umwelt',                 'Umweltgutachten & UVP'],
            // ─ Automobil & Mobilität ─
            ['Automobil – Technik',    'Fahrzeugelektrik & CAN-Bus / LIN / FlexRay'],
            ['Automobil – Technik',    'AUTOSAR & Embedded Systems'],
            ['Automobil – Technik',    'ADAS / Autonomous Driving'],
            ['Automobil – Technik',    'Sensorik & Messtechnik'],
            ['Automobil – Prozesse',   'APQP / FMEA / PPAP / Control Plan'],
            ['Automobil – Prozesse',   'VDA / IATF 16949'],
            ['Automobil – Prozesse',   'Lean Manufacturing / Kaizen'],
            // ─ Personalwesen (NEU) ─
            ['Personalwesen',          'Active Sourcing & Recruiting'],
            ['Personalwesen',          'Employer Branding'],
            ['Personalwesen',          'Talent Management & Personalentwicklung'],
            ['Personalwesen',          'Payroll & Entgeltabrechnung'],
            ['Personalwesen',          'HR-Systeme (SAP HCM, Personio, Workday)'],
            ['Personalwesen',          'Arbeitsrecht & Betriebsratsarbeit'],
            // ─ Marketing & Sales (NEU) ─
            ['Marketing & Sales',      'SEO / SEA / Performance-Marketing'],
            ['Marketing & Sales',      'Social-Media-Management'],
            ['Marketing & Sales',      'Content Marketing & Strategie'],
            ['Marketing & Sales',      'B2B-Vertrieb / Key-Account-Management'],
            ['Marketing & Sales',      'CRM-Systeme (Salesforce, HubSpot)'],
            ['Marketing & Sales',      'E-Commerce / Onlineshop-Systeme'],
            // ─ Medizin & Pflege (NEU) ─
            ['Medizin & Pflege',       'Pflegedokumentation & -planung'],
            ['Medizin & Pflege',       'ICD-10 / OPS / DRG-Kodierung'],
            ['Medizin & Pflege',       'KIS / ORBIS / iMedOne'],
            ['Medizin & Pflege',       'Hygiene- & Qualitätsmanagement'],
            ['Medizin & Pflege',       'Intensivpflege & Beatmungstechnik'],
            // ─ Handwerk & Produktion (NEU) ─
            ['Handwerk & Produktion',  'CNC-Programmierung & -Bedienung'],
            ['Handwerk & Produktion',  'Schweißtechnik (WIG, MAG, MIG)'],
            ['Handwerk & Produktion',  'Elektroinstallation & Schaltschrankbau'],
            ['Handwerk & Produktion',  'Mechatronik'],
            ['Handwerk & Produktion',  'Lean Production / REFA'],
            // ─ Gastronomie & Hotellerie ─
            ['Gastronomie',            'Küchentechnik & HACCP'],
            ['Gastronomie',            'Kalkulation & Wareneinsatz'],
            ['Gastronomie',            'Kassenführung & POS-Systeme'],
            ['Gastronomie',            'Eventorganisation & Catering'],
            ['Gastronomie',            'Weinkunde & Sommelier'],
            ['Gastronomie',            'F&B Management'],
            ['Gastronomie',            'Front Office / Fidelio / Opera'],
            // ─ Soft Skills ─
            ['Soft Skills',            'Teamfähigkeit & Kooperation'],
            ['Soft Skills',            'Kommunikationsstärke'],
            ['Soft Skills',            'Eigenverantwortliches Arbeiten'],
            ['Soft Skills',            'Analytisches Denkvermögen'],
            ['Soft Skills',            'Problemlösungskompetenz'],
            ['Soft Skills',            'Belastbarkeit & Resilienz'],
            ['Soft Skills',            'Kundenorientierung'],
            ['Soft Skills',            'Präsentation & Moderation'],
            ['Soft Skills',            'Proaktivität & Hands-on-Mentalität'],
            ['Soft Skills',            'Empathie & Einfühlungsvermögen'],
            ['Soft Skills',            'Interkulturelle Kompetenz'],
            // ─ Methodik ─
            ['Methodik',               'Projektmanagement (klassisch & agil)'],
            ['Methodik',               'PRINCE2 / IPMA / PMP'],
            ['Methodik',               'MS Office / Google Workspace'],
            ['Methodik',               'Prozessoptimierung / Six Sigma'],
            ['Methodik',               'OKR (Objectives & Key Results)'],
            ['Methodik',               'Design Thinking'],
            ['Methodik',               'Qualitätsmanagement (QM)'],
            ['Methodik',               'Reporting & Controlling'],
            ['Methodik',               'Notion / Confluence / Jira'],
            ['Methodik',               'Slack / Microsoft Teams'],
            // ─ Sprachen ─
            ['Sprachen',               'Deutsch (Muttersprache)'],
            ['Sprachen',               'Deutsch (C1 / C2)'],
            ['Sprachen',               'Englisch (Muttersprache)'],
            ['Sprachen',               'Englisch (C1 / C2)'],
            ['Sprachen',               'Englisch (B1 / B2)'],
            ['Sprachen',               'Französisch (B1 / B2)'],
            ['Sprachen',               'Französisch (C1 / C2)'],
            ['Sprachen',               'Spanisch (B1 / B2)'],
            ['Sprachen',               'Italienisch (B1 / B2)'],
            ['Sprachen',               'Chinesisch / HSK'],
        ];

        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO {$p}jpg_skills (group_name, skill_name, sort_order)
             VALUES (?, ?, ?)"
        );
        foreach ($skills as $i => [$group, $name]) {
            $stmt->execute([$group, $name, $i * 10]);
        }
    }

    // ── Textbaustein-Seed ──────────────────────────────────────────────────────

    private static function seed_text_modules(\PDO $pdo, string $p): void
    {
        $modules = [
            ['Einleitung', 'Unternehmensvorstellung (IT)',
             'Wir sind ein innovatives Technologieunternehmen mit {MITARBEITER} Mitarbeitern an {STANDORTE} Standorten. Als digitaler Vordenker entwickeln wir maßgeschneiderte Softwarelösungen für mittelständische und große Unternehmen.',
             'it,software,einleitung'],
            ['Einleitung', 'Unternehmensvorstellung (Bau)',
             'Unser Unternehmen steht seit über {JAHRE} Jahren für Qualität, Verlässlichkeit und Innovation im Bauwesen. Als etablierter Generalunternehmer realisieren wir Projekte von der Planung bis zur schlüsselfertigen Übergabe.',
             'bau,architektur,einleitung'],
            ['Einleitung', 'Unternehmensvorstellung (Logistik)',
             'Als einer der führenden Logistikdienstleister Deutschlands bewegen wir täglich tausende Sendungen zuverlässig ans Ziel. Mit modernster IT-Infrastruktur und einem engagierten Team bieten wir Full-Service-Logistik aus einer Hand.',
             'logistik,transport,einleitung'],
            ['Einleitung', 'Unternehmensvorstellung (Bank)',
             'Als regional verwurzeltes Kreditinstitut mit über {FILIALEN} Filialen betreuen wir Privat- und Firmenkunden mit individuellen Finanzlösungen. Vertrauen, Nähe und wirtschaftliche Stärke prägen unser tägliches Handeln.',
             'bank,finanzen,einleitung'],
            ['Einleitung', 'Unternehmensvorstellung (Kanzlei)',
             'Unsere Kanzlei bietet umfassende Rechtsberatung in den Bereichen {RECHTSGEBIETE} mit einem erfahrenen Team aus Fachanwälten und Notaren. Wir vertreten Mandanten deutschlandweit und vor europäischen Gerichten.',
             'recht,kanzlei,einleitung'],
            ['Aufgaben',   'Backend-Entwickler Aufgaben',
             'Sie entwickeln und warten skalierbare Backend-Dienste (PHP/Python/Go), entwerfen Datenbankmodelle und APIs, führen Code-Reviews durch, arbeiten in einem agilen Team nach Scrum und tragen zur technischen Architektur bei.',
             'it,backend,aufgaben'],
            ['Aufgaben',   'Bauleiter Aufgaben',
             'Sie leiten eigenverantwortlich Bauprojekte von der Ausführungsplanung bis zur Abnahme, koordinieren Nachunternehmer, überwachen Kosten/Terminplan/Qualität, erstellen Aufmaße und Abrechnungen und führen Baubesprechungen durch.',
             'bau,bauleiter,aufgaben'],
            ['Aufgaben',   'Disponent Transport Aufgaben',
             'Sie planen und disponieren nationale/internationale Transporte, optimieren Touren und Auslastungen, koordinieren Fahrer und externe Dienstleister, bearbeiten Frachtpapiere und stellen die fristgerechte Zustellung sicher.',
             'logistik,disposition,aufgaben'],
            ['Einleitung', 'Energieunternehmen Vorstellung',
             'Wir gestalten die Energiewende aktiv mit. Als Betreiber von Wind-, Solar- und Speicherprojekten mit einer installierten Leistung von über {MW} MW suchen wir engagierte Fachleute, die mit uns die Zukunft der Energie bauen.',
             'energie,umwelt,einleitung'],
            ['Abschluss',  'Bewerbungsaufforderung (allgemein)',
             'Interesse geweckt? Dann freuen wir uns auf Ihre aussagekräftige Bewerbung mit Gehaltsvorstellung und frühestmöglichem Eintrittstermin. Senden Sie Ihre Unterlagen an hr@{DOMAIN} oder bewerben Sie sich direkt online.',
             'abschluss,bewerbung'],
            ['Abschluss',  'Bewerbungsaufforderung (Bau)',
             'Sie möchten an spannenden Projekten mitwirken? Dann senden Sie uns Ihre Bewerbung mit Lichtbild, Zeugnissen und Angabe Ihrer Gehaltsvorstellung. Wir freuen uns auf ein persönliches Gespräch bei uns auf der Baustelle oder im Büro.',
             'abschluss,bau,bewerbung'],
        ];

        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO {$p}jpg_text_modules (category, title, content, tags)
             VALUES (?, ?, ?, ?)"
        );
        foreach ($modules as [$cat, $title, $content, $tags]) {
            $stmt->execute([$cat, $title, $content, $tags]);
        }
    }

    // ── Template-Seed ─────────────────────────────────────────────────────────

    private static function seed_templates(\PDO $pdo, string $p): void
    {
        $pdfModern = '<div class="jpg-pdf-modern">
  <header class="pdf-header">
    <div class="pdf-logo-area"><div class="pdf-company">{COMPANY}</div></div>
    <div class="pdf-title-area">
      <h1>{TITLE}</h1>
      <div class="pdf-meta">
        <span>📍 {LOCATION}</span>
        <span>💼 {EMPLOYMENT_TYPE}</span>
        <span>🕐 {EXPERIENCE_LEVEL}</span>
      </div>
    </div>
  </header>
  <div class="pdf-body two-col">
    <main class="pdf-main">
      <section><h2>Über die Stelle</h2><p>{SUMMARY}</p></section>
      <section><h2>Ihre Aufgaben</h2><ul>{TASKS}</ul></section>
      <section><h2>Ihr Profil</h2>
        <h3>Muss-Kriterien</h3><ul>{REQUIREMENTS_MUST}</ul>
        <h3>Wünschenswert</h3><ul>{REQUIREMENTS_NICE}</ul>
      </section>
    </main>
    <aside class="pdf-sidebar">
      <section><h2>Benefits</h2><ul class="benefits-list">{BENEFITS}</ul></section>
      <section><h2>Skills</h2><ul class="skills-list">{SKILLS}</ul></section>
      {SALARY_BLOCK}
    </aside>
  </div>
  <footer class="pdf-footer">{FOOTER_TEXT}</footer>
</div>';

        $pdfModernCss = '
.jpg-pdf-modern { font-family: {FONT_BODY}; color: #1e293b; max-width: 860px; margin: 0 auto; }
.pdf-header { background: {PRIMARY_COLOR}; color: #fff; padding: 1.5rem 2rem; display: flex; gap: 2rem; align-items: center; border-radius: 8px 8px 0 0; }
.pdf-company { font-size: 1.2rem; font-weight: 700; opacity: .85; }
.pdf-title-area h1 { margin: 0 0 .4rem; font-size: 1.6rem; font-family: {FONT_HEADING}; }
.pdf-meta { display: flex; gap: 1rem; font-size: .85rem; opacity: .9; }
.pdf-body.two-col { display: grid; grid-template-columns: 1fr 260px; gap: 0; }
.pdf-main { padding: 1.5rem 2rem; }
.pdf-sidebar { background: #f8fafc; padding: 1.5rem; border-left: 3px solid {PRIMARY_COLOR}; }
.pdf-main section, .pdf-sidebar section { margin-bottom: 1.2rem; }
.pdf-main h2, .pdf-sidebar h2 { font-size: 1rem; color: {PRIMARY_COLOR}; border-bottom: 2px solid {PRIMARY_COLOR}; padding-bottom: .2rem; margin-bottom: .5rem; }
.pdf-main h3 { font-size: .9rem; color: #475569; margin: .5rem 0 .25rem; }
.pdf-main ul, .pdf-sidebar ul { margin: 0; padding-left: 1.2rem; }
.pdf-main li, .pdf-sidebar li { margin-bottom: .25rem; font-size: .9rem; line-height: 1.5; }
.benefits-list li { list-style: none; padding-left: 0; }
.pdf-footer { background: {SECONDARY_COLOR}; color: #fff; font-size: .75rem; text-align: center; padding: .75rem; border-radius: 0 0 8px 8px; }';

        $pdfClassic = '<div class="jpg-pdf-classic">
  <header class="classic-header">
    <h1>{TITLE}</h1>
    <p class="classic-subtitle">{COMPANY} · {LOCATION} · {EMPLOYMENT_TYPE}</p>
    <hr>
  </header>
  <section class="classic-summary"><p>{SUMMARY}</p></section>
  <section>
    <h2>Aufgabengebiet</h2>
    <ul>{TASKS}</ul>
  </section>
  <section>
    <h2>Anforderungsprofil</h2>
    <table class="req-table"><tbody>
      <tr><th>Muss</th><td><ul>{REQUIREMENTS_MUST}</ul></td></tr>
      <tr><th>Wunsch</th><td><ul>{REQUIREMENTS_NICE}</ul></td></tr>
    </tbody></table>
  </section>
  <section>
    <h2>Wir bieten</h2>
    <ul class="benefits-inline">{BENEFITS}</ul>
  </section>
  {SALARY_BLOCK}
  <footer class="classic-footer">{FOOTER_TEXT}</footer>
</div>';

        $pdfClassicCss = '
.jpg-pdf-classic { font-family: {FONT_BODY}; color: #1e293b; max-width: 800px; margin: 0 auto; padding: 2rem; }
.classic-header h1 { font-size: 1.8rem; font-family: {FONT_HEADING}; margin: 0 0 .4rem; color: {PRIMARY_COLOR}; }
.classic-subtitle { color: #64748b; font-size: .9rem; margin: 0 0 .75rem; }
.classic-header hr { border: none; border-top: 3px solid {PRIMARY_COLOR}; margin-bottom: 1.25rem; }
.classic-summary { font-size: .95rem; margin-bottom: 1.5rem; line-height: 1.7; }
section { margin-bottom: 1.5rem; }
h2 { font-size: 1.1rem; color: {PRIMARY_COLOR}; border-left: 4px solid {PRIMARY_COLOR}; padding-left: .6rem; margin-bottom: .6rem; }
ul { padding-left: 1.4rem; } li { margin-bottom: .2rem; font-size: .9rem; line-height: 1.6; }
.req-table { width: 100%; border-collapse: collapse; font-size: .9rem; }
.req-table th { width: 80px; background: #f8fafc; padding: .5rem .75rem; text-align: left; font-weight: 600; color: #475569; vertical-align: top; }
.req-table td { padding: .5rem; }
.benefits-inline { column-count: 2; column-gap: 1.5rem; }
.classic-footer { margin-top: 2rem; padding-top: .75rem; border-top: 1px solid #e2e8f0; font-size: .75rem; color: #94a3b8; text-align: center; }';

        $webCard = '<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{TITLE}</title>
<style>{CUSTOM_CSS}</style></head>
<body>
<div class="job-card">
  <div class="job-card-header">
    <div><h1>{TITLE}</h1>
    <p class="job-meta"><span>📍 {LOCATION}</span><span>💼 {EMPLOYMENT_TYPE}</span><span>⭐ {EXPERIENCE_LEVEL}</span></p>
    </div>
    <div class="apply-box">{SALARY_INLINE}<a href="#apply" class="btn-apply">Jetzt bewerben</a></div>
  </div>
  <div class="job-card-body">
    <div class="job-section"><h2>Über die Stelle</h2><p>{SUMMARY}</p></div>
    <div class="job-two-col">
      <div>
        <div class="job-section"><h2>Ihre Aufgaben</h2><ul>{TASKS}</ul></div>
        <div class="job-section"><h2>Ihr Profil</h2>
          <h3>Anforderungen</h3><ul>{REQUIREMENTS_MUST}</ul>
          <h3>Von Vorteil</h3><ul>{REQUIREMENTS_NICE}</ul>
        </div>
      </div>
      <div class="job-aside">
        <div class="job-benefits-box"><h2>Das bieten wir</h2><ul>{BENEFITS}</ul></div>
        <div class="job-skills-box"><h2>Gefragt Skills</h2><div class="skill-tags">{SKILLS_TAGS}</div></div>
      </div>
    </div>
  </div>
  <div id="apply" class="apply-section">
    <h2>Jetzt bewerben</h2>
    <p>{FOOTER_TEXT}</p>
  </div>
</div></body></html>';

        $webCardCss = '
*{box-sizing:border-box;margin:0;padding:0}
body{background:#f1f5f9;font-family:{FONT_BODY};color:#1e293b}
.job-card{max-width:900px;margin:2rem auto;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden}
.job-card-header{background:{PRIMARY_COLOR};color:#fff;padding:2rem;display:flex;justify-content:space-between;align-items:flex-start;gap:1rem}
.job-card-header h1{font-size:1.6rem;font-family:{FONT_HEADING};margin-bottom:.5rem}
.job-meta{display:flex;gap:1rem;font-size:.85rem;opacity:.9;flex-wrap:wrap}
.apply-box{text-align:right;min-width:160px}
.btn-apply{display:inline-block;background:#fff;color:{PRIMARY_COLOR};padding:.625rem 1.25rem;border-radius:8px;font-weight:700;text-decoration:none;font-size:.9rem;margin-top:.75rem;white-space:nowrap}
.salary-inline{font-size:.85rem;opacity:.9;margin-bottom:.25rem}
.job-card-body{padding:2rem}
.job-two-col{display:grid;grid-template-columns:1fr 280px;gap:1.5rem;margin-top:1.5rem}
.job-section{margin-bottom:1.5rem}
.job-section h2{font-size:1rem;color:{PRIMARY_COLOR};font-weight:700;margin-bottom:.5rem;padding-bottom:.25rem;border-bottom:2px solid {PRIMARY_COLOR}}
.job-section h3{font-size:.875rem;font-weight:600;color:#475569;margin:.75rem 0 .25rem}
.job-section ul{padding-left:1.25rem}.job-section li{margin-bottom:.25rem;font-size:.9rem;line-height:1.6}
.job-aside{display:flex;flex-direction:column;gap:1rem}
.job-benefits-box,.job-skills-box{background:#f8fafc;border-radius:8px;padding:1rem;border:1px solid #e2e8f0}
.job-benefits-box h2,.job-skills-box h2{font-size:.9rem;color:{PRIMARY_COLOR};font-weight:700;margin-bottom:.5rem}
.job-benefits-box li{list-style:none;font-size:.85rem;padding:.2rem 0}
.skill-tags{display:flex;flex-wrap:wrap;gap:.35rem}
.skill-tag{background:{PRIMARY_COLOR}1a;color:{PRIMARY_COLOR};font-size:.78rem;padding:.2rem .55rem;border-radius:12px;font-weight:500}
.apply-section{background:#f8fafc;padding:1.5rem 2rem;border-top:3px solid {PRIMARY_COLOR}}
.apply-section h2{color:{PRIMARY_COLOR};margin-bottom:.5rem}
.apply-section p{font-size:.9rem;color:#475569;line-height:1.7}
@media(max-width:640px){.job-two-col{grid-template-columns:1fr}.job-card-header{flex-direction:column}}';

        $emailTpl = '<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><style>
body{background:#f1f5f9;font-family:Arial,sans-serif;color:#1e293b}
.email-wrap{max-width:600px;margin:2rem auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.07)}
.email-header{background:{PRIMARY_COLOR};color:#fff;padding:1.5rem;text-align:center}
.email-header h1{font-size:1.4rem;margin:0 0 .4rem}
.email-header p{font-size:.85rem;opacity:.85;margin:0}
.email-body{padding:1.5rem}
.email-body h2{font-size:1rem;color:{PRIMARY_COLOR};margin:1rem 0 .4rem}
.email-body ul{padding-left:1.2rem;margin:.25rem 0}.email-body li{font-size:.9rem;margin-bottom:.2rem;line-height:1.55}
.email-body p{font-size:.9rem;line-height:1.7;margin-bottom:.75rem;color:#475569}
.cta-row{text-align:center;margin:1.5rem 0}
.btn-cta{display:inline-block;background:{PRIMARY_COLOR};color:#fff;padding:.75rem 2rem;border-radius:8px;text-decoration:none;font-weight:700;font-size:.95rem}
.email-footer{background:#f8fafc;padding:.75rem;text-align:center;font-size:.75rem;color:#94a3b8;border-top:1px solid #e2e8f0}
</style></head>
<body><div class="email-wrap">
  <div class="email-header"><h1>{TITLE}</h1><p>📍 {LOCATION} · 💼 {EMPLOYMENT_TYPE}</p></div>
  <div class="email-body">
    <p>{SUMMARY}</p>
    <h2>Ihre Aufgaben (Auszug)</h2><ul>{TASKS_SHORT}</ul>
    <h2>Wir bieten</h2><ul>{BENEFITS_SHORT}</ul>
    <div class="cta-row"><a href="#apply" class="btn-cta">Jetzt bewerben →</a></div>
    <p style="font-size:.8rem;color:#94a3b8;">{FOOTER_TEXT}</p>
  </div>
  <div class="email-footer">{COMPANY} · {LOCATION}</div>
</div></body></html>';

        $templates = [
            ['pdf', 'Modern (Zweispaltig)',   $pdfModern,   $pdfModernCss,  1],
            ['pdf', 'Klassisch (Tabellarisch)', $pdfClassic, $pdfClassicCss, 0],
            ['web', 'Job-Card (Responsive)',  $webCard,     $webCardCss,    1],
            ['email','Standard E-Mail',       $emailTpl,    '',             1],
        ];

        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO {$p}jpg_templates (type, name, content, css, is_default, active)
             VALUES (?, ?, ?, ?, ?, 1)"
        );
        foreach ($templates as [$type, $name, $content, $css, $isDefault]) {
            $stmt->execute([$type, $name, $content, $css, $isDefault]);
        }
    }

    // ── Branchen-Paket (admin-getriggert) ─────────────────────────────────────

    public static function seed_industry_package(array $industries): int
    {
        $count = 0;
        try {
            $db  = \CMS\Database::instance();
            $p   = $db->getPrefix();
            $pdo = $db->getPdo();

            $skillMap = [
                'it' => [
                    ['IT – Daten & KI',       'Machine Learning / KI-Frameworks'],
                    ['IT – Daten & KI',       'Data Engineering (Spark, Kafka)'],
                    ['IT – Daten & KI',       'BI & Reporting (Power BI, Tableau)'],
                    ['IT – Daten & KI',       'MLOps & Modell-Deployment'],
                    ['IT – Daten & KI',       'LLM / Prompt Engineering'],
                    ['IT – Web',              'React / Vue.js / Angular'],
                    ['IT – Web',              'Node.js / Next.js'],
                    ['IT – Web',              'GraphQL & WebSockets'],
                    ['IT – Sicherheit',       'Zero Trust Architecture'],
                    ['IT – Sicherheit',       'SOC & SIEM (Splunk, Sentinel)'],
                ],
                'bau' => [
                    ['Bauwesen – Digital',    'Digital Twin / GIS-Planung'],
                    ['Bauwesen – Digital',    'Drohnen-Vermessung / Photogrammetrie'],
                    ['Bauwesen – Normen',     'LEED / DGNB (Nachhaltigkeitszertifizierung)'],
                    ['Bauwesen – Normen',     'Wärmeschutz GEG / Passivhaus-Standard'],
                    ['Bauwesen – Betrieb',    'Terminplanung (MS Project / ASTA PowerProject)'],
                    ['Bauwesen – Betrieb',    'Nachtragskalkulation & Claimmanagement'],
                    ['Bauwesen – Betrieb',    'Schlussrechnung & Aufmaß nach VOB/C'],
                ],
                'strassenbau' => [
                    ['Straßenbau – Digital',  'VESTRA INFRA / Card/1 / TOPsurv'],
                    ['Straßenbau – Normen',   'RIN / RAA / RAL (Straßenplanung)'],
                    ['Straßenbau – Normen',   'Lärm-Berechnungen nach RLS-19'],
                    ['Straßenbau – Normen',   'Entwurfsplanung nach RE 2012'],
                    ['Straßenbau – Betrieb',  'Straßenerhaltungsmanagement (PAVEMENT)'],
                    ['Straßenbau – Betrieb',  'Bauüberwachung Infrastrukturprojekte'],
                    ['Straßenbau – Betrieb',  'STLB-Bau Leistungsverzeichnis Tiefbau'],
                ],
                'logistik' => [
                    ['Logistik – E-Commerce',  'E-Commerce-Fulfillment & Retourenmanagement'],
                    ['Logistik – E-Commerce',  'Same-Day-Delivery & Expresslogistik'],
                    ['Logistik – Technik',     'IoT / Track & Trace (RFID, GPS-Telematik)'],
                    ['Logistik – Technik',     'Warehouse-Robotik (AutoStore, AMR, AGV)'],
                    ['Logistik – Technik',     'Inventory-Optimierung & S&OP-Planung'],
                    ['Logistik – Normen',      'SQAS / GDP (Good Distribution Practice)'],
                ],
                'banking' => [
                    ['Banking – Digital',      'Open Banking & PSD2/PSD3-APIs'],
                    ['Banking – Digital',      'Core-Banking-Transformation (Temenos, Mambu)'],
                    ['Banking – Digital',      'RegTech & SupTech-Lösungen'],
                    ['Banking – Analyse',      'Stresstesting (EBA / Fed)'],
                    ['Banking – Analyse',      'Derivate & strukturierte Produkte'],
                    ['Banking – Regulierung',  'ESG-Regulierung (EU-Taxonomie, SFDR)'],
                    ['Banking – Regulierung',  'DORA (Digital Operational Resilience Act)'],
                ],
                'recht' => [
                    ['Recht – Gebiete',        'Medizinrecht & Apothekenrecht'],
                    ['Recht – Gebiete',        'Insolvenzrecht & Restrukturierung'],
                    ['Recht – Gebiete',        'Miet- & Wohnungseigentumsrecht'],
                    ['Recht – Gebiete',        'Kartell- & Wettbewerbsrecht'],
                    ['Recht – Digital',        'Legal Tech & Dokumentenautomation'],
                    ['Recht – Digital',        'e-Discovery & digitale Beweismittel'],
                    ['Recht – Digital',        'Legal Operations & ALM-Software'],
                ],
                'maschinenbau' => [
                    ['Maschinenbau – Digital',  'Digital Engineering / PLM (Windchill, Enovia)'],
                    ['Maschinenbau – Digital',  'Additive Fertigung / 3D-Druck (SLS, SLA, FDM)'],
                    ['Maschinenbau – Digital',  'Digitaler Zwilling & Simulation'],
                    ['Maschinenbau – Normen',   'Druckgeräterichtlinie PED 2014/68/EU'],
                    ['Maschinenbau – Normen',   'Funktionale Sicherheit IEC 61508 / ISO 13849'],
                    ['Maschinenbau – Normen',   'Ex-Schutz ATEX / IECEx'],
                ],
                'automobil' => [
                    ['Automobil – E-Mobilität',  'Hochvolt-Technologie & HV-Sicherheit'],
                    ['Automobil – E-Mobilität',  'Ladeinfrastruktur & V2G'],
                    ['Automobil – E-Mobilität',  'Batteriesystem & BMS-Entwicklung'],
                    ['Automobil – Software',      'AUTOSAR Classic & Adaptive Platform'],
                    ['Automobil – Software',      'Cybersecurity ISO/SAE 21434'],
                    ['Automobil – Software',      'SOTIF ISO 21448 / Safetyvalidierung'],
                    ['Automobil – Software',      'Autonomous Driving L3/L4 (ROS, Apollo)'],
                ],
                'energie' => [
                    ['Energie – Grid',           'Smart Grid & AMI (Advanced Metering Infrastructure)'],
                    ['Energie – Grid',           'Netzstabilisierung & Lastmanagement'],
                    ['Energie – Grid',           'Energiespeicher (BESS / Power-to-Gas)'],
                    ['Energie – Regulierung',    'EnWG / EWG / Netzentgeltregulierung'],
                    ['Energie – Regulierung',    'EU-Emissionshandel (ETS)'],
                    ['Energie – Regulierung',    'Genehmigungsverfahren nach BImSchG'],
                ],
                'pharma' => [
                    ['Pharma – Qualität',       'GMP (EU Good Manufacturing Practice)'],
                    ['Pharma – Qualität',       'Validierung & Qualifizierung (IQ/OQ/PQ, CSV)'],
                    ['Pharma – Qualität',       'QMS (ICH Q10 / ISO 9001)'],
                    ['Pharma – Regulierung',     'Regulatory Affairs (BfArM, EMA, FDA)'],
                    ['Pharma – Regulierung',     'Klinische Studien Phasen I–IV'],
                    ['Pharma – Regulierung',     'AMG / AMWHV / MDR (Medizinprodukte)'],
                ],
                'gastronomie' => [
                    ['Gastronomie – Führung',   'Personalplanung & Schichtmanagement'],
                    ['Gastronomie – Führung',   'Hygienemanagement (HACCP, IFS Food)'],
                    ['Gastronomie – Führung',   'Lebensmittelrecht & Allergenkennzeichnung'],
                    ['Gastronomie – Küche',     'Mol. Küche / Fine Dining Konzepte'],
                    ['Gastronomie – Digital',    'POS-Systeme (Lightspeed, Orderbird)'],
                    ['Gastronomie – Digital',    'Delivery-Plattformen (Lieferando, UberEats)'],
                ],
                'handel' => [
                    ['Handel – E-Commerce',      'Shop-Systeme (Shopify, Magento, WooCommerce)'],
                    ['Handel – E-Commerce',      'Performance-Marketing (SEA, Meta-Ads, TikTok)'],
                    ['Handel – E-Commerce',      'CRO & A/B-Tests (Optimizely, VWO)'],
                    ['Handel – E-Commerce',      'Marktplätze (Amazon Vendor, Otto, Zalando)'],
                    ['Handel – Stationär',      'Category-Management & Space Planning'],
                    ['Handel – Stationär',      'Filialsteuerung & Loss-Prevention'],
                ],
                'bildung' => [
                    ['Bildung – Lehre',           'Didaktik & Lernzielkontrolle'],
                    ['Bildung – Lehre',           'E-Learning / LMS (Moodle, ILIAS, Canvas)'],
                    ['Bildung – Lehre',           'Inklusionspädagogik & Differentierung'],
                    ['Bildung – Forschung',       'Drittmittelakquise & Förderanträge (DFG, EU)'],
                    ['Bildung – Forschung',       'Wissenschaftliches Schreiben & Publishing'],
                    ['Bildung – Forschung',       'Statistik (SPSS / R / Python)'],
                ],
                'medien' => [
                    ['Medien – Produktion',       'Video-Produktion & Schnitt (Premiere, DaVinci)'],
                    ['Medien – Produktion',       'Motion Graphics (After Effects, Cinema 4D)'],
                    ['Medien – Produktion',       'Podcast & Audio-Produktion'],
                    ['Medien – Design',           'UI/UX Design (Figma, Sketch, Adobe XD)'],
                    ['Medien – Design',           'Brand Identity & Corporate Design'],
                    ['Medien – Content',          'SEO & Content-Strategie'],
                    ['Medien – Content',          'Social-Media-Management & Creator Economy'],
                ],
                'oeffentlich' => [
                    ['Öff. Dienst – Recht',      'Beamtenrecht & Tarifrecht (TVöD / TV-L)'],
                    ['Öff. Dienst – Recht',      'Kommunalrecht & Gemeindeordnung'],
                    ['Öff. Dienst – Recht',      'Vergaberecht (UVgO, VgV, VOB/A)'],
                    ['Öff. Dienst – Prozesse',    'eGovernment & OZG-Umsetzung'],
                    ['Öff. Dienst – Prozesse',    'Bauleitplanung & Flächennutzungsplan (BauGB)'],
                    ['Öff. Dienst – Prozesse',    'Haushaltskonsolidierung & doppische Buchführung'],
                ],
                'soziales' => [
                    ['Soziales – Betreuung',      'Sozialpädagogik & Sozialarbeit (BTHG / SGB IX)'],
                    ['Soziales – Betreuung',      'Krisenintervention & Deeskalation'],
                    ['Soziales – Betreuung',      'Arbeit mit suchterkrankten Menschen'],
                    ['Soziales – Management',     'Sozialmanagement & QM (DIN ISO 9001)'],
                    ['Soziales – Management',     'Fördermittelmanagement & EU-Fonds'],
                    ['Soziales – Management',     'Lobbyarbeit & Advocacy'],
                ],
            ];

            $textModuleMap = [
                'bau' => [
                    ['Einleitung','Bauunternehmen (Tiefbau)',
                     'Als regional verwurzeltes Bauunternehmen mit über {JAHRE} Jahren Erfahrung realisieren wir anspruchsvolle Tiefbau-, Straßenbau- und Infrastrukturprojekte. Zuverlässigkeit, Qualität und Teamgeist sind unsere Werte.',
                     'bau,tiefbau,einleitung'],
                    ['Aufgaben', 'Polier / Baukolonnenführer',
                     'Sie führen eigenverantwortlich eine Baukolonne von {MITARBEITER} Personen, koordinieren den Materialeingang, sichern die Arbeitssicherheit nach DGUV und dokumentieren den Baufortschritt täglich.',
                     'bau,polier,aufgaben'],
                ],
                'logistik' => [
                    ['Einleitung','Logistik-Dienstleister (KEP)',
                     'Als wachsendes KEP-Unternehmen mit tägliche {SENDUNGEN} Sendungen suchen wir engagierte Mitarbeitende, die unsere Qualitätsstandards mitgestalten.',
                     'logistik,kep,einleitung'],
                    ['Aufgaben','Schichtleiter Lager',
                     'Sie leiten eine Lager-Schicht mit {MITARBEITER} Mitarbeitenden, steuern das operative Tagesgeschäft, kontrollieren KPIs (Pick-Rate, Fehlerquote), arbeiten mit dem WMS und berichten an die Lagerleitung.',
                     'logistik,lager,schichtleiter,aufgaben'],
                ],
                'banking' => [
                    ['Einleitung','Sparkasse / Volksbank',
                     'Als regional verankerte Sparkasse betreuen wir {KUNDEN} Privat- und Firmenkunden in der Region {REGION}. Wir stehen für Zuverlässigkeit, Nähe und nachhaltiges Wirtschaften.',
                     'bank,sparkasse,einleitung'],
                ],
                'recht' => [
                    ['Einleitung','Rechtsanwaltskanzlei (Boutique)',
                     'Unsere auf {RECHTSGEBIET} spezialisierte Boutique-Kanzlei bietet hochqualifizierte Rechtsberatung für Unternehmen und Privatpersonen. Wir zeichnen uns durch Fachtiefe und persönliche Mandantenbetreuung aus.',
                     'recht,kanzlei,boutique,einleitung'],
                ],
                'it' => [
                    ['Einleitung','SaaS-Startup',
                     'Wir sind ein schnell wachsendes SaaS-Unternehmen mit {KUNDEN} B2B-Kunden in {LAENDER} Ländern. Unser Team baut täglich an der Plattform der Zukunft – mit Herzblut und tech-first Mentalität.',
                     'it,saas,startup,einleitung'],
                    ['Aufgaben','Senior Cloud Engineer',
                     'Sie entwerfen skalierbare Cloud-Architekturen (AWS/Azure), automatisieren Deployments via Terraform & GitHub Actions, betreiben Kubernetes-Cluster, definieren Security-Policies und mentoren Junior-Engineers.',
                     'it,cloud,devops,aufgaben'],
                ],
                'energie' => [
                    ['Einleitung','Stadtwerk / Regionalversorger',
                     'Als Stadtwerk der Region {REGION} versorgen wir über {KUNDEN} Haushalte und Unternehmen mit Strom, Gas, Wärme und Wasser. Die Energiewende gestalten wir aktiv durch eigene PV- und Windprojekte.',
                     'energie,stadtwerk,einleitung'],
                ],
                'automobil' => [
                    ['Einleitung','OEM / Automobilzulieferer',
                     'Wir sind ein Tier-1-Zulieferer mit {MITARBEITER} Mitarbeitenden an {STANDORTE} Standorten weltweit. Für unsere E-Mobilitäts-Sparte suchen wir Talente, die die Fahrzeuge von übermorgen entwickeln.',
                     'automobil,oem,zulieferer,einleitung'],
                ],
            ];

            $stmtSkill = $pdo->prepare(
                "INSERT IGNORE INTO {$p}jpg_skills (group_name, skill_name, sort_order) VALUES (?, ?, ?)"
            );
            foreach ($industries as $industry) {
                foreach (($skillMap[$industry] ?? []) as $i => [$group, $name]) {
                    $stmtSkill->execute([$group, $name, ($i + 1) * 10]);
                    $count += (int) ($stmtSkill->rowCount() > 0);
                }
            }

            $stmtTm = $pdo->prepare(
                "INSERT IGNORE INTO {$p}jpg_text_modules (category, title, content, tags) VALUES (?, ?, ?, ?)"
            );
            foreach ($industries as $industry) {
                foreach (($textModuleMap[$industry] ?? []) as [$cat, $title, $content, $tags]) {
                    $stmtTm->execute([$cat, $title, $content, $tags]);
                    $count += (int) ($stmtTm->rowCount() > 0);
                }
            }
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Installer::seed_industry_package() error: ' . $e->getMessage());
        }

        return $count;
    }

    // ── Deinstallation ────────────────────────────────────────────────────────

    /**
     * Entfernt alle Plugin-Daten vollständig:
     * – Alle jpg_*-Tabellen werden gedroppt
     * – Subscription-Plan-Spalten werden entfernt (wenn CMS-kompatibel)
     * – Plugin-Einstellung `jpg_db_version` wird gelöscht
     *
     * @since 0.4.0
     */
    public static function uninstall(): void
    {
        try {
            $db  = \CMS\Database::instance();
            $pdo = $db->getPdo();
            $p   = $db->getPrefix();
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Installer::uninstall() – DB init failed: ' . $e->getMessage());
            return;
        }

        // Alle Plugin-Tabellen (Reihenfolge: Abhängigkeiten zuerst löschen)
        $tables = [
            'jpg_workflow_history',
            'jpg_workflow_steps',
            'jpg_applications',
            'jpg_stats',
            'jpg_profile_benefits',
            'jpg_profile_skills',
            'jpg_profile_tasks',
            'jpg_profile_requirements',
            'jpg_category_benefits',
            'jpg_profile_benefits',
            'jpg_skills',
            'jpg_text_modules',
            'jpg_templates',
            'jpg_job_categories',
            'jpg_benefits',
            'jpg_profiles',
            'jpg_settings',
            // Phase 4–9 tables
            'jpg_requirement_items',
            'jpg_company_default_benefits',
            'jpg_team_approvers',
            'jpg_departments',
            'jpg_department_benefits',
            'jpg_department_requirements',
            'jpg_company_settings',
            'jpg_admin_access_log',
        ];

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (array_unique($tables) as $table) {
            try {
                $pdo->exec("DROP TABLE IF EXISTS {$p}{$table}");
            } catch (\Throwable $e) {
                error_log("CMS_JPG_Installer::uninstall() – DROP {$table}: " . $e->getMessage());
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        // Subscription-Plan-Spalten entfernen (nur wenn Tabelle existiert)
        $subscriptionCols = [
            'limit_job_profiles',
            'feature_whitelabel_jobs',
            'feature_custom_branding',
        ];
        foreach ($subscriptionCols as $col) {
            try {
                $check = $pdo->prepare(
                    "SELECT COUNT(*) FROM information_schema.columns
                     WHERE table_schema = DATABASE()
                       AND table_name   = ?
                       AND column_name  = ?"
                );
                $check->execute(["{$p}subscription_plans", $col]);
                if ((int) $check->fetchColumn() > 0) {
                    $pdo->exec("ALTER TABLE {$p}subscription_plans DROP COLUMN {$col}");
                }
            } catch (\Throwable $e) {
                error_log("CMS_JPG_Installer::uninstall() – DROP COLUMN {$col}: " . $e->getMessage());
            }
        }
    }

    // ── Anforderungs-Katalog Seed ─────────────────────────────────────────────

    private static function seed_requirement_items(\PDO $pdo, string $p): void
    {
        $items = [
            // [group_name, title, req_type, icon, sort_order]
            // IT & Programmierung
            ['IT & Programmierung',           'PHP 8+ Kenntnisse',                  'must',     '💻',  10],
            ['IT & Programmierung',           'JavaScript / TypeScript',             'must',     '💻',  20],
            ['IT & Programmierung',           'Python',                              'nice',     '🐍',  30],
            ['IT & Programmierung',           'SQL-Datenbanken (MySQL, PostgreSQL)', 'must',     '🗄️', 40],
            ['IT & Programmierung',           'REST-API Entwicklung',                'must',     '🔌',  50],
            ['IT & Programmierung',           'Git / Versionskontrolle',             'must',     '📦',  60],
            ['IT & Programmierung',           'Docker / Container',                  'nice',     '🐳',  70],
            ['IT & Programmierung',           'Cloud (AWS / Azure / GCP)',           'nice',     '☁️',  80],
            ['IT & Programmierung',           'Linux-Systemkenntnisse',              'nice',     '🐧',  90],
            ['IT & Programmierung',           'CI/CD-Pipelines (GitHub Actions)',    'optional', '⚙️', 100],
            // Sprachen
            ['Sprachen',                      'Deutsch (fließend / C1+)',            'must',     '🇩🇪', 110],
            ['Sprachen',                      'Englisch (Business, B2+)',            'must',     '🇬🇧', 120],
            ['Sprachen',                      'Englisch (fließend / C1+)',           'nice',     '🇬🇧', 130],
            ['Sprachen',                      'Weitere Fremdsprache',                'optional', '🌍', 140],
            // Soft Skills
            ['Soft Skills',                   'Teamfähigkeit',                       'must',     '🤝',  150],
            ['Soft Skills',                   'Eigeninitiative',                     'must',     '💪',  160],
            ['Soft Skills',                   'Kommunikationsstärke',                'must',     '💬',  170],
            ['Soft Skills',                   'Problemlösungskompetenz',             'must',     '🧩',  180],
            ['Soft Skills',                   'Strukturierte Arbeitsweise',          'must',     '📋',  190],
            ['Soft Skills',                   'Zuverlässigkeit',                     'must',     '✅',  200],
            ['Soft Skills',                   'Flexibilität',                        'nice',     '🔄',  210],
            ['Soft Skills',                   'Kreativität',                         'nice',     '💡',  220],
            ['Soft Skills',                   'Belastbarkeit',                       'must',     '🧱',  230],
            ['Soft Skills',                   'Kundenorientierung',                  'must',     '😊',  240],
            // Ausbildung & Qualifikation
            ['Ausbildung & Qualifikation',    'Abgeschlossenes Studium (Informatik o.ä.)',  'must',     '🎓', 250],
            ['Ausbildung & Qualifikation',    'Abgeschlossene Berufsausbildung',             'must',     '🏫', 260],
            ['Ausbildung & Qualifikation',    'Abgeschlossenes BWL-Studium',                 'must',     '📚', 270],
            ['Ausbildung & Qualifikation',    'FH-Abschluss oder Abitur ausreichend',        'nice',     '📜', 280],
            ['Ausbildung & Qualifikation',    'Relevante Zertifizierungen (z.B. AWS, Scrum)',  'nice',   '🏅', 290],
            ['Ausbildung & Qualifikation',    'Weiterbildung / Selbststudium anerkannt',     'optional', '📖', 300],
            // Kaufmännisch
            ['Kaufmännisch',                  'Buchhaltungskenntnisse (DATEV / SAP)',  'must',   '📊',  310],
            ['Kaufmännisch',                  'Erfahrung in Auftragsabwicklung',       'must',   '📄',  320],
            ['Kaufmännisch',                  'Controlling-Kenntnisse',                'nice',   '📈',  330],
            ['Kaufmännisch',                  'Erfahrung im Einkauf / Beschaffung',    'nice',   '🛒',  340],
            ['Kaufmännisch',                  'Rechnungsstellung & Faktura',           'must',   '🧾',  350],
            // Tools & Software
            ['Tools & Software',              'Microsoft Office (Excel, Word, Outlook)',  'must',  '🖥️', 360],
            ['Tools & Software',              'Erweiterte Excel-Kenntnisse (Pivot, Makros)', 'nice', '📊', 370],
            ['Tools & Software',              'CRM-Systeme (Salesforce, HubSpot)',      'nice',   '📱',  380],
            ['Tools & Software',              'SAP-Kenntnisse',                         'nice',   '💼',  390],
            ['Tools & Software',              'JIRA / Confluence',                      'nice',   '📌',  400],
            ['Tools & Software',              'Grafik / Design (Figma, Adobe Suite)',   'nice',   '🎨',  410],
            // Führung & Projektmanagement
            ['Führung & Projektmanagement',   'Erste Führungserfahrung',              'nice',     '👔',  420],
            ['Führung & Projektmanagement',   'Erfahrene Führungskraft',              'must',     '🏆',  430],
            ['Führung & Projektmanagement',   'Projektmanagement (Scrum, Kanban)',     'must',     '📅',  440],
            ['Führung & Projektmanagement',   'Budgetverantwortung',                  'nice',     '💰',  450],
            ['Führung & Projektmanagement',   'Erfahrung in agilen Methoden',         'nice',     '♻️', 460],
            // Marketing & Vertrieb
            ['Marketing & Vertrieb',          'B2B-Vertriebserfahrung',               'must',     '🤝',  470],
            ['Marketing & Vertrieb',          'Online-Marketing / SEO / SEA',         'must',     '📣',  480],
            ['Marketing & Vertrieb',          'Content-Marketing',                    'nice',     '✍️', 490],
            ['Marketing & Vertrieb',          'Social-Media-Kenntnisse',              'nice',     '📱',  500],
            ['Marketing & Vertrieb',          'Key-Account-Management',               'must',     '🎯',  510],
            ['Marketing & Vertrieb',          'Erfahrung mit Ausschreibungen/Tender', 'nice',     '📝',  520],
            // Persönlichkeit
            ['Persönlichkeit',                'Selbstständiges Arbeiten',             'must',     '🏠',  530],
            ['Persönlichkeit',                'Hands-on-Mentalität',                  'must',     '🔧',  540],
            ['Persönlichkeit',                'Analytisches Denkvermögen',            'must',     '🔍',  550],
            ['Persönlichkeit',                'Unternehmerisches Denken',             'nice',     '💡',  560],
            ['Persönlichkeit',                'Reisebereitschaft',                    'nice',     '✈️', 570],
            ['Persönlichkeit',                'Führerschein Klasse B',               'optional', '🚗',  580],
        ];

        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO {$p}jpg_requirement_items
                (group_name, title, req_type, icon, sort_order)
             VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($items as [$group, $title, $type, $icon, $sort]) {
            $stmt->execute([$group, $title, $type, $icon, $sort]);
        }
    }
}
