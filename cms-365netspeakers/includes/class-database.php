<?php
/**
 * Database Handler für CMS Speakers
 *
 * @package CMS_Speakers
 * @since 2.1.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Speakers_Database
{
    private static ?self $instance = null;
    private const MAX_LIST_LIMIT = 200;
    /** @var array<string, bool> */
    private array $tableExistsCache = [];
    /** @var array<string, string>|null */
    private ?array $settingsCache = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    private function normalizeLimit(mixed $limit, int $default = 12): int
    {
        $limit = (int) $limit;
        if ($limit <= 0) {
            return $default;
        }

        return min($limit, self::MAX_LIST_LIMIT);
    }

    private function normalizeOffset(mixed $offset): int
    {
        return max(0, (int) $offset);
    }

    private function normalizeOrder(mixed $order): string
    {
        $allowed = [
            's.created_at DESC',
            's.created_at ASC',
            's.last_name ASC, s.first_name ASC',
            's.last_name DESC, s.first_name DESC',
        ];

        $order = is_string($order) ? trim($order) : '';
        return in_array($order, $allowed, true) ? $order : 's.created_at DESC';
    }

    // ═══════════════════════════════════════════════════════
    // TABELLEN ANLEGEN
    // ═══════════════════════════════════════════════════════

    public function create_tables(): void
    {
        try {
            $db  = CMS\Database::instance();
            $pdo = $db->getPdo();
            $p   = $db->prefix();

            // ── Haupt-Speakers-Tabelle ──────────────────────────
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}speakers (
                id                  INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
                user_id             INT UNSIGNED     DEFAULT NULL,
                first_name          VARCHAR(100)     NOT NULL DEFAULT '',
                last_name           VARCHAR(100)     NOT NULL DEFAULT '',
                title               VARCHAR(100)     DEFAULT NULL COMMENT 'Akad. Titel',
                gender              ENUM('','m','f','d') DEFAULT '',
                position            VARCHAR(200)     DEFAULT NULL,
                company             VARCHAR(200)     DEFAULT NULL,
                company_id          INT UNSIGNED     DEFAULT NULL COMMENT 'FK cms_companies',
                email               VARCHAR(150)     NOT NULL DEFAULT '',
                phone               VARCHAR(60)      DEFAULT NULL,
                bio                 LONGTEXT         DEFAULT NULL,
                short_bio           VARCHAR(600)     DEFAULT NULL,
                photo_url           VARCHAR(600)     DEFAULT NULL,
                location_city       VARCHAR(100)     DEFAULT NULL,
                location_zip        VARCHAR(20)      DEFAULT NULL,
                location_country    VARCHAR(100)     DEFAULT 'Deutschland',
                website             VARCHAR(600)     DEFAULT NULL,
                linkedin            VARCHAR(600)     DEFAULT NULL,
                twitter             VARCHAR(200)     DEFAULT NULL,
                xing                VARCHAR(600)     DEFAULT NULL,
                instagram           VARCHAR(200)     DEFAULT NULL,
                youtube             VARCHAR(600)     DEFAULT NULL,
                github              VARCHAR(600)     DEFAULT NULL,
                gitlab              VARCHAR(600)     DEFAULT NULL,
                languages           VARCHAR(400)     DEFAULT NULL COMMENT 'JSON-Array',
                formats             VARCHAR(400)     DEFAULT NULL COMMENT 'JSON-Array: keynote,workshop,...',
                target_audience     VARCHAR(400)     DEFAULT NULL,
                speaking_style      VARCHAR(200)     DEFAULT NULL,
                awards              TEXT             DEFAULT NULL,
                recognitions        TEXT             DEFAULT NULL COMMENT 'JSON-Array vordefinierter Auszeichnungen',
                skills              TEXT             DEFAULT NULL COMMENT 'JSON-Array Speaker-Skills',
                travel_radius       ENUM('local','regional','national','international','worldwide') DEFAULT 'national',
                max_audience_size   INT UNSIGNED     DEFAULT NULL,
                speaking_fee_min    DECIMAL(10,2)    DEFAULT NULL,
                speaking_fee_max    DECIMAL(10,2)    DEFAULT NULL,
                availability        ENUM('available','limited','booked') DEFAULT 'available',
                status              ENUM('active','inactive','draft','pending','deleted') DEFAULT 'active',
                is_featured         TINYINT(1)       DEFAULT 0,
                is_verified         TINYINT(1)       DEFAULT 0,
                profile_views       INT UNSIGNED     DEFAULT 0,
                created_at          DATETIME         DEFAULT CURRENT_TIMESTAMP,
                updated_at          DATETIME         DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status       (status),
                INDEX idx_availability (availability),
                INDEX idx_featured     (is_featured),
                INDEX idx_city         (location_city),
                INDEX idx_company_id   (company_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // ── Speaker-Themen/Topics ───────────────────────────
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}speaker_topics (
                id          INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
                speaker_id  INT UNSIGNED  NOT NULL,
                topic_name  VARCHAR(200)  NOT NULL,
                topic_desc  TEXT          DEFAULT NULL,
                sort_order  INT           DEFAULT 0,
                created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_speaker (speaker_id),
                UNIQUE KEY unique_topic (speaker_id, topic_name(100))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // ── Speaker-Events / Auftritte ──────────────────────
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}speaker_events (
                id              INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
                speaker_id      INT UNSIGNED   NOT NULL,
                event_title     VARCHAR(300)   NOT NULL,
                event_type      ENUM('keynote','workshop','panel','moderation','interview','webinar','conference','training','other') DEFAULT 'keynote',
                event_date      DATE           DEFAULT NULL,
                event_date_end  DATE           DEFAULT NULL,
                event_location  VARCHAR(300)   DEFAULT NULL,
                presence_type   ENUM('presence','online','hybrid') DEFAULT 'presence',
                organizer_type  ENUM('company','cms_event','manual') DEFAULT 'manual',
                company_id      INT UNSIGNED   DEFAULT NULL COMMENT 'FK cms_companies',
                cms_event_id    INT UNSIGNED   DEFAULT NULL COMMENT 'FK cms_events',
                organizer_name  VARCHAR(300)   DEFAULT NULL,
                topic           VARCHAR(400)   DEFAULT NULL,
                description     TEXT           DEFAULT NULL,
                audience_size   INT UNSIGNED   DEFAULT NULL,
                video_url       VARCHAR(600)   DEFAULT NULL,
                slides_url      VARCHAR(600)   DEFAULT NULL,
                event_url       VARCHAR(600)   DEFAULT NULL,
                is_public       TINYINT(1)     DEFAULT 1,
                created_at      DATETIME       DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_speaker    (speaker_id),
                INDEX idx_event_date (event_date),
                INDEX idx_company    (company_id),
                INDEX idx_cms_event  (cms_event_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // ── Plugin-Einstellungen ────────────────────────────
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}speaker_plugin_settings (
                id            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
                setting_key   VARCHAR(255)  NOT NULL UNIQUE,
                setting_value LONGTEXT      DEFAULT NULL,
                updated_at    DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // ── Lila Standard-Einstellungen (nur fehlende Schlüssel ergänzen) ──
            $defaultSettings = $this->default_settings();
            $targetVersion = '3.0.3';
            $stmtVer = $pdo->prepare(
                "SELECT setting_value FROM {$p}speaker_plugin_settings WHERE setting_key = 'settings_version' LIMIT 1"
            );
            $stmtVer->execute();
            $installedVersion = $stmtVer->fetchColumn() ?: '';

            // Nur neu hinzugekommene Schlüssel einfügen (bestehende nicht anfassen)
            $stmtSeed = $pdo->prepare(
                "INSERT IGNORE INTO {$p}speaker_plugin_settings (setting_key, setting_value) VALUES (?, ?)"
            );
            foreach ($defaultSettings as $k => $v) {
                $stmtSeed->execute([$k, $v]);
            }

            if ($installedVersion !== $targetVersion) {
                $stmtVersion = $pdo->prepare(
                    "INSERT INTO {$p}speaker_plugin_settings (setting_key, setting_value) VALUES (?, ?) "
                    . "ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
                );
                $stmtVersion->execute(['settings_version', $targetVersion]);
            }

            $this->seed_default_settings($defaultSettings);

            // ── ALTER bestehende Tabellen (Spalten ergänzen, falls nötig) ──
            $this->maybe_alter_tables($pdo, $p);
            $this->seed_default_speakers();

        } catch (\PDOException $e) {
            error_log('CMS_Speakers DB Error: ' . $e->getMessage());
        }
    }

    private function seed_default_speakers(): void
    {
        $db = CMS\Database::instance();

        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM {$db->prefix()}speakers");
            $stmt->execute([]);
            if ((int) $stmt->fetchColumn() > 0) {
                return;
            }

            $rows = array_merge(
                $this->read_default_speaker_rows('speakers.csv'),
                $this->read_default_speaker_rows('mvps.csv')
            );
            if ($rows === []) {
                return;
            }

            $seen = [];
            foreach ($rows as $row) {
                $firstName = trim((string) ($row['vorname'] ?? ''));
                $lastName = trim((string) ($row['nachname'] ?? ''));
                if ($firstName === '' || $lastName === '') {
                    continue;
                }

                $company = trim((string) ($row['firma'] ?? ''));
                $dedupeKey = $this->seed_lower($firstName . '|' . $lastName . '|' . $company);
                if (isset($seen[$dedupeKey])) {
                    continue;
                }
                $seen[$dedupeKey] = true;

                $topic = trim((string) ($row['top_in_was'] ?? ''));
                if ($topic === '') {
                    $topic = trim((string) ($row['kategorie'] ?? ''));
                }
                $award = trim((string) ($row['mvp_auszeichnung'] ?? ''));
                if ($award === '') {
                    $award = trim((string) ($row['award_typ'] ?? ''));
                }
                $certificates = trim((string) ($row['zertifikate'] ?? ''));
                $events = trim((string) ($row['event_s'] ?? ''));
                if ($events === '') {
                    $events = trim((string) ($row['typische_events'] ?? ''));
                }

                $speakerId = $this->save_speaker([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'company' => $company !== '' ? $company : null,
                    'position' => $topic !== '' ? $topic : null,
                    'email' => 'speaker+' . preg_replace('/[^a-z0-9]+/', '-', $this->seed_lower($firstName . '-' . $lastName)) . '@seed.local',
                    'bio' => $this->seed_speaker_bio($topic, $award, $certificates),
                    'short_bio' => $topic !== '' ? $this->seed_substr($topic, 0, 580) : null,
                    'website' => $this->seed_url((string) ($row['website'] ?? '')),
                    'awards' => $award !== '' ? $award : null,
                    'recognitions' => $award !== '' ? json_encode([$award], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    'skills' => $topic !== '' ? json_encode($this->seed_list($topic), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    'formats' => json_encode(['keynote', 'panel', 'conference'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'travel_radius' => 'national',
                    'availability' => 'available',
                    'status' => 'active',
                    'is_featured' => $award !== '' ? 1 : 0,
                    'is_verified' => 1,
                ]);

                if (!is_int($speakerId) || $speakerId <= 0) {
                    continue;
                }

                foreach ($this->seed_list($events) as $eventTitle) {
                    $this->save_event($speakerId, [
                        'event_title' => $eventTitle,
                        'event_type' => 'conference',
                        'presence_type' => 'presence',
                        'organizer_type' => 'manual',
                        'organizer_name' => '365 Network Default-Datensatz',
                        'topic' => $topic !== '' ? $topic : null,
                        'is_public' => 1,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            error_log('CMS_Speakers seed_default_speakers: ' . $e->getMessage());
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function read_default_speaker_rows(string $filename): array
    {
        $file = dirname(__DIR__) . '/defaults/' . $filename;
        if (!is_file($file)) {
            return [];
        }

        $content = @file_get_contents($file);
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\n|\r/', $content) ?: [];
        $headerLine = trim((string) array_shift($lines));
        if ($headerLine === '') {
            return [];
        }

        $headers = array_map(fn(string $header): string => $this->seed_header($header), str_getcsv($headerLine, ';') ?: []);
        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $values = str_getcsv($line, ';') ?: [];
            $row = [];
            foreach ($headers as $idx => $key) {
                if ($key !== '') {
                    $row[$key] = trim((string) ($values[$idx] ?? ''));
                }
            }

            if (($row['vorname'] ?? '') !== '' && ($row['nachname'] ?? '') !== '') {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function seed_header(string $header): string
    {
        $header = $this->seed_lower(trim($header));
        $header = str_replace(['ä', 'ö', 'ü', 'ß', '/', '-'], ['ae', 'oe', 'ue', 'ss', '_', '_'], $header);
        $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? '';
        return trim($header, '_');
    }

    private function seed_speaker_bio(string $topic, string $award, string $certificates): ?string
    {
        $parts = [];
        if ($topic !== '') {
            $parts[] = 'Schwerpunkte: ' . $topic;
        }
        if ($award !== '') {
            $parts[] = 'Auszeichnung: ' . $award;
        }
        if ($certificates !== '') {
            $parts[] = 'Zertifikate: ' . $certificates;
        }

        return $parts !== [] ? implode("\n", $parts) : null;
    }

    /**
     * @return array<int, string>
     */
    private function seed_list(string $value): array
    {
        $items = preg_split('/\s*[|,]\s*/', trim($value)) ?: [];
        return array_values(array_unique(array_filter(array_map(static fn(string $item): string => trim($item), $items))));
    }

    private function seed_url(string $url): ?string
    {
        $url = trim($url);
        return $url !== '' && filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    private function seed_lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    private function seed_substr(string $value, int $start, int $length): string
    {
        return function_exists('mb_substr') ? (string) mb_substr($value, $start, $length, 'UTF-8') : (string) substr($value, $start, $length);
    }

    private function maybe_alter_tables(\PDO $pdo, string $p): void
    {
        $columns = [
            "{$p}speakers" => [
                'gender'          => "ENUM('','m','f','d') DEFAULT ''",
                'target_audience' => 'VARCHAR(400) DEFAULT NULL',
                'speaking_style'  => 'VARCHAR(200) DEFAULT NULL',
                'awards'          => 'TEXT DEFAULT NULL',
                'recognitions'    => 'TEXT DEFAULT NULL COMMENT \'JSON-Array vordefinierter Auszeichnungen\'',
                'skills'          => 'TEXT DEFAULT NULL COMMENT \'JSON-Array Speaker-Skills\'',
                'github'          => 'VARCHAR(600) DEFAULT NULL',
                'gitlab'          => 'VARCHAR(600) DEFAULT NULL',
            ],
            "{$p}speaker_events" => [
                'presence_type'  => "ENUM('presence','online','hybrid') DEFAULT 'presence'",
                'cms_event_id'   => 'INT UNSIGNED DEFAULT NULL',
            ],
        ];
        foreach ($columns as $table => $cols) {
            foreach ($cols as $col => $def) {
                try {
                    if (!$this->column_exists($pdo, $table, $col)) {
                        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$def}");
                    }
                } catch (\Throwable $e) {}
            }
        }

        try {
            $pdo->exec("ALTER TABLE `{$p}speakers` MODIFY COLUMN `status` ENUM('active','inactive','draft','pending','deleted') DEFAULT 'active'");
        } catch (\Throwable $e) {}
    }

    private function column_exists(\PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function table_exists(string $table): bool
    {
        if (array_key_exists($table, $this->tableExistsCache)) {
            return $this->tableExistsCache[$table];
        }

        try {
            $stmt = CMS\Database::instance()->prepare(
                'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
            );
            $stmt->execute([$table]);
            $exists = (int) $stmt->fetchColumn() > 0;
            $this->tableExistsCache[$table] = $exists;
            return $exists;
        } catch (\Throwable) {
            $this->tableExistsCache[$table] = false;
            return false;
        }
    }

    /**
     * @return array<string, string>
     */
    private function default_settings(): array
    {
        return [
            'show_nav_link'              => '0',
            'show_main_nav_item'         => '0',
            'nav_label'                  => 'Speaker',
            'design_primary_color'       => '#8b5cf6',
            'design_accent_color'        => '#7c3aed',
            'design_card_bg'             => '#faf5ff',
            'design_border_radius'       => '12',
            'design_cta_label'           => 'Profil ansehen',
            'design_show_availability'   => '1',
            'design_show_mvp_badge'      => '1',
            'design_show_formats'        => '1',
            'design_show_topics'         => '1',
            'design_grid_columns'        => 'auto',
            'archive_title'              => 'Speaker Directory',
            'archive_description'        => 'Finden Sie den passenden Redner für Ihr Event',
            'archive_per_page'           => '12',
            'archive_header_icon'        => '🎤',
            'archive_header_bg_from'     => '#6d28d9',
            'archive_header_bg_to'       => '#a855f7',
            'archive_header_title_color' => '#ffffff',
            'detail_header_bg_from'      => '#4c1d95',
            'detail_header_bg_to'        => '#7c3aed',
            'detail_header_title_color'  => '#ffffff',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // SPEAKERS CRUD
    // ═══════════════════════════════════════════════════════

    public function get_speaker(int $id): ?object
    {
        $db = CMS\Database::instance();
        $p  = $db->prefix();
        try {
            $hasCompanies = $this->table_exists($p . 'companies');
            $companySelect = $hasCompanies ? ', c.name AS company_linked_name' : ', NULL AS company_linked_name';
            $companyJoin = $hasCompanies ? "LEFT JOIN {$p}companies c ON s.company_id = c.id" : '';
            $stmt = $db->prepare("
                SELECT s.*{$companySelect}
                FROM {$p}speakers s
                {$companyJoin}
                WHERE s.id = ? LIMIT 1");
            $stmt->execute([$id]);
            return $stmt->fetch() ?: null;
        } catch (\Throwable $e) { return null; }
    }

    public function get_speakers(array $args = []): array
    {
        $db = CMS\Database::instance();
        $p  = $db->prefix();
        try {
            $defaults = [
                'status'        => 'active',
                'availability'  => null,
                'travel_radius' => null,
                'city'          => null,
                'search'        => null,
                'is_featured'   => null,
                'is_verified'   => null,
                'format'        => null,
                'user_id'       => null,
                'limit'         => 12,
                'offset'        => 0,
                'order'         => 's.created_at DESC',
            ];
            $args   = array_merge($defaults, $args);
            $where  = ['1=1'];
            $params = [];

            if ($args['status'] !== null) {
                $where[] = 's.status = ?'; $params[] = $args['status'];
            }
            if ($args['availability']) {
                $where[] = 's.availability = ?'; $params[] = $args['availability'];
            }
            if ($args['travel_radius']) {
                $where[] = 's.travel_radius = ?'; $params[] = $args['travel_radius'];
            }
            if ($args['city']) {
                $where[] = 's.location_city LIKE ?'; $params[] = '%' . $args['city'] . '%';
            }
            if ($args['is_featured'] !== null) {
                $where[] = 's.is_featured = ?'; $params[] = (int)$args['is_featured'];
            }
            if ($args['is_verified'] !== null) {
                $where[] = 's.is_verified = ?'; $params[] = (int)$args['is_verified'];
            }
            if ($args['format']) {
                $where[] = 's.formats LIKE ?'; $params[] = '%' . $args['format'] . '%';
            }
            if ($args['search']) {
                $like = '%' . $args['search'] . '%';
                $where[] = '(s.first_name LIKE ? OR s.last_name LIKE ? OR s.position LIKE ? OR s.company LIKE ? OR s.location_city LIKE ? OR s.target_audience LIKE ?)';
                $params  = array_merge($params, [$like, $like, $like, $like, $like, $like]);
            }
            if (!empty($args['user_id'])) {
                $where[] = 's.user_id = ?'; $params[] = (int) $args['user_id'];
            }

            $whereStr = implode(' AND ', $where);
            $limit    = $this->normalizeLimit($args['limit'] ?? 12, 12);
            $offset   = $this->normalizeOffset($args['offset'] ?? 0);
            $orderBy  = $this->normalizeOrder($args['order'] ?? 's.created_at DESC');
            $hasCompanies = $this->table_exists($p . 'companies');
            $companySelect = $hasCompanies ? ', c.name AS company_linked_name' : ', NULL AS company_linked_name';
            $companyJoin = $hasCompanies ? "LEFT JOIN {$p}companies c ON s.company_id = c.id" : '';

            $stmt = $db->prepare(
                "SELECT s.*{$companySelect}
                 FROM {$p}speakers s
                 {$companyJoin}
                 WHERE {$whereStr}
                 ORDER BY {$orderBy}
                 LIMIT {$limit} OFFSET {$offset}"
            );
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('CMS_Speakers get_speakers: ' . $e->getMessage());
            return [];
        }
    }

    public function count_speakers(array $args = []): int
    {
        $db = CMS\Database::instance();
        $p  = $db->prefix();
        try {
            $defaults = ['status' => 'active', 'availability' => null, 'travel_radius' => null,
                         'city' => null, 'search' => null, 'is_featured' => null, 'is_verified' => null, 'format' => null, 'user_id' => null];
            $args   = array_merge($defaults, $args);
            $where  = ['1=1'];
            $params = [];
            if ($args['status'] !== null)    { $where[] = 's.status = ?';           $params[] = $args['status']; }
            if ($args['availability'])        { $where[] = 's.availability = ?';     $params[] = $args['availability']; }
            if ($args['travel_radius'])       { $where[] = 's.travel_radius = ?';    $params[] = $args['travel_radius']; }
            if ($args['city'])                { $where[] = 's.location_city LIKE ?'; $params[] = '%' . $args['city'] . '%'; }
            if ($args['is_featured'] !== null){ $where[] = 's.is_featured = ?';      $params[] = (int)$args['is_featured']; }
            if ($args['is_verified'] !== null){ $where[] = 's.is_verified = ?';      $params[] = (int)$args['is_verified']; }
            if ($args['format'])              { $where[] = 's.formats LIKE ?';       $params[] = '%' . $args['format'] . '%'; }
            if ($args['search']) {
                $like = '%' . $args['search'] . '%';
                $where[] = '(s.first_name LIKE ? OR s.last_name LIKE ? OR s.position LIKE ? OR s.company LIKE ? OR s.location_city LIKE ? OR s.target_audience LIKE ?)';
                $params  = array_merge($params, [$like, $like, $like, $like, $like, $like]);
            }
            if (!empty($args['user_id'])) {
                $where[] = 's.user_id = ?'; $params[] = (int) $args['user_id'];
            }
            $whereStr = implode(' AND ', $where);
            $stmt = $db->prepare("SELECT COUNT(*) FROM {$p}speakers s WHERE {$whereStr}");
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) { return 0; }
    }

    /**
     * Speaker speichern / aktualisieren.
     * Keys müssen den DB-Spalten entsprechen.
     * $id optional als zweiter Parameter oder im Array als 'id'-Key.
     */
    public function save_speaker(array $data, int $id = 0): int|false
    {
        $db = CMS\Database::instance();
        $p  = $db->prefix();
        try {
            $allowed = [
                'user_id','first_name','last_name','title','gender',
                'position','company','company_id',
                'email','phone','bio','short_bio','photo_url',
                'location_city','location_zip','location_country',
                'website','linkedin','twitter','xing','instagram','youtube','github','gitlab',
                'languages','formats','target_audience','speaking_style','awards','recognitions','skills',
                'travel_radius','max_audience_size',
                'speaking_fee_min','speaking_fee_max',
                'availability','status','is_featured','is_verified',
            ];
            $dataId = $id > 0 ? $id : (int)($data['id'] ?? 0);
            unset($data['id']);
            $data = array_intersect_key($data, array_flip($allowed));
            $data = $this->sanitize_speaker_data($data);

            if ($dataId > 0 && !CMS\Auth::instance()->isAdmin()) {
                $current_user_id = (int) (CMS\Auth::instance()->currentUser()?->id ?? 0);
                if ($current_user_id <= 0) {
                    return false;
                }

                $owner_stmt = $db->prepare("SELECT user_id FROM {$p}speakers WHERE id = ? LIMIT 1");
                $owner_stmt->execute([$dataId]);
                $owner_id = (int) ($owner_stmt->fetchColumn() ?: 0);

                if ($owner_id <= 0 || $owner_id !== $current_user_id) {
                    return false;
                }
            }

            // Leere Numerics → NULL
            foreach (['max_audience_size','speaking_fee_min','speaking_fee_max','company_id'] as $nf) {
                if (array_key_exists($nf, $data) && (string)$data[$nf] === '') {
                    $data[$nf] = null;
                }
            }

            if ($dataId > 0) {
                $set = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
                $stmt = $db->prepare("UPDATE {$p}speakers SET {$set} WHERE id = ?");
                $stmt->execute([...array_values($data), $dataId]);
                if (class_exists('CMS\\Hooks')) {
                    CMS\Hooks::doAction('speaker_updated', $dataId, $data);
                }
                return $dataId;
            } else {
                $keys   = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
                $places = implode(', ', array_fill(0, count($data), '?'));
                $stmt   = $db->prepare("INSERT INTO {$p}speakers ({$keys}) VALUES ({$places})");
                $stmt->execute(array_values($data));
                $newId = (int)$db->getPdo()->lastInsertId();
                if (class_exists('CMS\\Hooks')) {
                    CMS\Hooks::doAction('speaker_created', $newId, $data);
                }
                return $newId;
            }
        } catch (\Throwable $e) {
            error_log('CMS_Speakers save_speaker: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Setzt den Status eines Speakers direkt (z.B. pending → active)
     */
    public function set_speaker_status(int $id, string $status): bool
    {
        $allowed = ['active', 'inactive', 'pending', 'deleted'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $db = CMS\Database::instance();
        return $db->update('speakers', ['status' => $status], ['id' => $id]) !== false;
    }

    public function delete_speaker(int $id): bool
    {
        $db = CMS\Database::instance();
        try {
            $p = $db->prefix();
            $db->prepare("DELETE FROM {$p}speaker_events WHERE speaker_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM {$p}speaker_topics WHERE speaker_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM {$p}speakers WHERE id = ?")->execute([$id]);
            return true;
        } catch (\Throwable $e) { return false; }
    }

    public function increment_views(int $id): void
    {
        try {
            $db = CMS\Database::instance();
            $db->prepare("UPDATE {$db->prefix()}speakers SET profile_views = profile_views + 1 WHERE id = ?")->execute([$id]);
        } catch (\Throwable $e) {}
    }

    // ═══════════════════════════════════════════════════════
    // TOPICS
    // ═══════════════════════════════════════════════════════

    public function get_topics(int $speaker_id): array
    {
        $db = CMS\Database::instance();
        try {
            $stmt = $db->prepare("SELECT * FROM {$db->prefix()}speaker_topics WHERE speaker_id = ? ORDER BY sort_order ASC, topic_name ASC");
            $stmt->execute([$speaker_id]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public function save_topics(int $speaker_id, array $topics): void
    {
        $db = CMS\Database::instance();
        $p  = $db->prefix();
        try {
            $db->prepare("DELETE FROM {$p}speaker_topics WHERE speaker_id = ?")->execute([$speaker_id]);
            foreach ($topics as $i => $topic) {
                $name = $this->clean_text((string) ($topic['name'] ?? $topic), 200);
                if ($name === '') { continue; }
                $descRaw = is_array($topic) ? ($topic['desc'] ?? null) : null;
                $desc = $descRaw !== null ? $this->clean_textarea((string) $descRaw, 2000) : null;
                $sortOrder = is_numeric($i) ? (int) $i : 0;
                $db->prepare("INSERT INTO {$p}speaker_topics (speaker_id, topic_name, topic_desc, sort_order) VALUES (?,?,?,?)")
                   ->execute([$speaker_id, $name, $desc, $sortOrder]);
            }
        } catch (\Throwable $e) { error_log('CMS_Speakers save_topics: ' . $e->getMessage()); }
    }

    // ═══════════════════════════════════════════════════════
    // EVENTS / AUFTRITTE
    // ═══════════════════════════════════════════════════════

    public function get_events(int $speaker_id, bool $public_only = false): array
    {
        $db = CMS\Database::instance();
        $p  = $db->prefix();
        try {
            $where = 'se.speaker_id = ?';
            $params = [$speaker_id];
            if ($public_only) { $where .= ' AND se.is_public = 1'; }
            $hasCompanies = $this->table_exists($p . 'companies');
            $companySelect = $hasCompanies ? ', c.name AS company_name, c.logo_url AS company_logo' : ', NULL AS company_name, NULL AS company_logo';
            $companyJoin = $hasCompanies ? "LEFT JOIN {$p}companies c ON se.company_id = c.id AND se.organizer_type = 'company'" : '';

            $stmt = $db->prepare(
                "SELECT se.*{$companySelect}
                 FROM {$p}speaker_events se
                 {$companyJoin}
                 WHERE {$where}
                 ORDER BY se.event_date DESC, se.created_at DESC"
            );
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    /**
     * Event speichern.
     * speaker_id wird als erster Parameter übergeben.
     */
    public function save_event(int $speaker_id, array $data): int|false
    {
        $db = CMS\Database::instance();
        $p  = $db->prefix();
        try {
            $allowed = [
                'speaker_id','event_title','event_type','event_date','event_date_end',
                'event_location','presence_type','organizer_type','company_id','cms_event_id',
                'organizer_name','topic','description','audience_size',
                'video_url','slides_url','event_url','is_public',
            ];
            $data['speaker_id'] = $speaker_id;
            $id = (int)($data['id'] ?? 0);
            unset($data['id']);
            $data = array_intersect_key($data, array_flip($allowed));
            $data = $this->sanitize_event_data($data);

            // Leere Datumsfelder → NULL
            foreach (['event_date','event_date_end'] as $df) {
                if (isset($data[$df]) && trim((string)$data[$df]) === '') { $data[$df] = null; }
            }
            foreach (['company_id','cms_event_id','audience_size'] as $nf) {
                if (isset($data[$nf]) && (int)$data[$nf] === 0) { $data[$nf] = null; }
            }

            if ($id > 0) {
                $set  = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
                $stmt = $db->prepare("UPDATE {$p}speaker_events SET {$set} WHERE id = ?");
                $stmt->execute([...array_values($data), $id]);
                return $id;
            } else {
                $keys   = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
                $places = implode(', ', array_fill(0, count($data), '?'));
                $stmt   = $db->prepare("INSERT INTO {$p}speaker_events ({$keys}) VALUES ({$places})");
                $stmt->execute(array_values($data));
                return (int)$db->getPdo()->lastInsertId();
            }
        } catch (\Throwable $e) {
            error_log('CMS_Speakers save_event: ' . $e->getMessage());
            return false;
        }
    }

    public function delete_event(int $id): bool
    {
        try {
            $db = CMS\Database::instance();
            $db->prepare("DELETE FROM {$db->prefix()}speaker_events WHERE id = ?")->execute([$id]);
            return true;
        } catch (\Throwable $e) { return false; }
    }

    // ═══════════════════════════════════════════════════════
    // PLUGIN SETTINGS
    // ═══════════════════════════════════════════════════════

    public function get_settings(): array
    {
        if ($this->settingsCache !== null) {
            return $this->settingsCache;
        }

        $settings = $this->default_settings();

        foreach ($this->get_legacy_settings() as $key => $value) {
            $settings[$key] = $value;
        }

        $settingsService = $this->settings_service();
        if ($settingsService !== null) {
            try {
                foreach ($settingsService->getGroup('cms-speakers') as $key => $value) {
                    if (is_scalar($value) || $value === null) {
                        $settings[$key] = (string) $value;
                    }
                }
            } catch (\Throwable $e) {
                error_log('CMS_Speakers get_settings SettingsService: ' . $e->getMessage());
            }
        }

        $this->settingsCache = $settings;
        return $settings;
    }

    public function save_settings(array $settings): void
    {
        $normalized = [];
        foreach ($settings as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            $normalized[$key] = is_scalar($value) || $value === null ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $settingsService = $this->settings_service();
        if ($settingsService !== null) {
            try {
                if ($settingsService->setMany('cms-speakers', $normalized, [], 0)) {
                    $this->settingsCache = null;
                    return;
                }
            } catch (\Throwable $e) {
                error_log('CMS_Speakers save_settings SettingsService: ' . $e->getMessage());
            }
        }

        $db = CMS\Database::instance();
        $p  = $db->prefix();
        try {
            foreach ($normalized as $key => $value) {
                $db->prepare(
                    "INSERT INTO {$p}speaker_plugin_settings (setting_key, setting_value)
                     VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?"
                )->execute([$key, $value, $value]);
            }
            $this->settingsCache = null;
        } catch (\Throwable $e) { error_log('CMS_Speakers save_settings: ' . $e->getMessage()); }
    }

    /**
     * @return array<string, string>
     */
    private function get_legacy_settings(): array
    {
        $db = CMS\Database::instance();
        try {
            $stmt = $db->prepare(
                "SELECT setting_key, setting_value FROM {$db->prefix()}speaker_plugin_settings"
            );
            $stmt->execute([]);
            $rows = $stmt->fetchAll();
            $out  = [];
            foreach ($rows as $row) {
                $out[(string) $row->setting_key] = (string) $row->setting_value;
            }

            return $out;
        } catch (\Throwable) {
            return [];
        }
    }

    private function settings_service(): ?\CMS\Services\SettingsService
    {
        if (!class_exists('CMS\\Services\\SettingsService')) {
            return null;
        }

        try {
            return \CMS\Services\SettingsService::getInstance();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, string> $defaults
     */
    private function seed_default_settings(array $defaults): void
    {
        $settingsService = $this->settings_service();
        if ($settingsService === null) {
            return;
        }

        try {
            $current = $settingsService->getGroup('cms-speakers');
            $settingsService->setMany('cms-speakers', array_merge($defaults, $current), [], 0);
        } catch (\Throwable $e) {
            error_log('CMS_Speakers seed_default_settings: ' . $e->getMessage());
        }
    }

    public function drop_tables(): void
    {
        $db = CMS\Database::instance();
        $pdo = $db->getPdo();
        $p = $db->prefix();

        try {
            $settingsService = $this->settings_service();
            if ($settingsService !== null) {
                $keys = array_unique(array_merge(
                    array_keys($this->default_settings()),
                    array_keys($this->get_legacy_settings()),
                    array_keys($settingsService->getGroup('cms-speakers')),
                    ['schema_version', 'settings_version']
                ));
                foreach ($keys as $key) {
                    $settingsService->forget('cms-speakers', (string) $key);
                }
            }
        } catch (\Throwable $e) {
            error_log('CMS_Speakers settings cleanup: ' . $e->getMessage());
        }

        foreach (['speaker_events', 'speaker_topics', 'speaker_plugin_settings', 'speakers'] as $table) {
            $pdo->exec('DROP TABLE IF EXISTS `' . $p . $table . '`');
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitize_speaker_data(array $data): array
    {
        $textLimits = [
            'first_name' => 100,
            'last_name' => 100,
            'title' => 100,
            'position' => 200,
            'company' => 200,
            'email' => 150,
            'phone' => 60,
            'short_bio' => 600,
            'location_city' => 100,
            'location_zip' => 20,
            'location_country' => 100,
            'target_audience' => 400,
            'speaking_style' => 200,
            'twitter' => 200,
            'instagram' => 200,
        ];

        foreach ($textLimits as $field => $limit) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $data[$field] = $this->clean_text((string) $data[$field], $limit);
            }
        }

        foreach (['bio' => 20000, 'awards' => 4000] as $field => $limit) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $data[$field] = $this->clean_textarea((string) $data[$field], $limit);
            }
        }

        foreach (['photo_url', 'website', 'linkedin', 'xing', 'youtube', 'github', 'gitlab'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $data[$field] = $this->clean_url((string) $data[$field]);
            }
        }

        if (isset($data['email'])) {
            $data['email'] = filter_var((string) $data['email'], FILTER_VALIDATE_EMAIL) ?: '';
        }

        if (isset($data['phone'])) {
            $data['phone'] = preg_replace('/[^0-9+()\s.\-]/', '', (string) $data['phone']) ?: '';
        }

        $enums = [
            'gender' => ['', 'm', 'f', 'd'],
            'travel_radius' => ['local', 'regional', 'national', 'international', 'worldwide'],
            'availability' => ['available', 'limited', 'booked'],
            'status' => ['active', 'inactive', 'draft', 'pending', 'deleted'],
        ];
        foreach ($enums as $field => $allowed) {
            if (isset($data[$field]) && !in_array((string) $data[$field], $allowed, true)) {
                unset($data[$field]);
            }
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitize_event_data(array $data): array
    {
        foreach (['event_title' => 300, 'event_location' => 300, 'organizer_name' => 300, 'topic' => 400] as $field => $limit) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $data[$field] = $this->clean_text((string) $data[$field], $limit);
            }
        }

        if (array_key_exists('description', $data) && $data['description'] !== null) {
            $data['description'] = $this->clean_textarea((string) $data['description'], 4000);
        }

        foreach (['video_url', 'slides_url', 'event_url'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $data[$field] = $this->clean_url((string) $data[$field]);
            }
        }

        $enums = [
            'event_type' => ['keynote', 'workshop', 'panel', 'moderation', 'interview', 'webinar', 'conference', 'training', 'other'],
            'presence_type' => ['presence', 'online', 'hybrid'],
            'organizer_type' => ['company', 'cms_event', 'manual'],
        ];
        foreach ($enums as $field => $allowed) {
            if (isset($data[$field]) && !in_array((string) $data[$field], $allowed, true)) {
                unset($data[$field]);
            }
        }

        return $data;
    }

    private function clean_text(string $value, int $maxLength = 255): string
    {
        return mb_substr(trim(strip_tags($value)), 0, $maxLength);
    }

    private function clean_textarea(string $value, int $maxLength = 2000): string
    {
        return mb_substr(trim(strip_tags($value)), 0, $maxLength);
    }

    private function clean_url(string $value): string
    {
        $url = trim($value);
        if ($url === '' || strlen($url) > 2048 || preg_match('/[[:cntrl:]]/', $url) === 1 || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || ($parts['user'] ?? '') !== '' || ($parts['pass'] ?? '') !== '') {
            return '';
        }

        $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));
        if ($host === '' || in_array($host, ['localhost', 'localhost.localdomain'], true) || str_ends_with($host, '.local')) {
            return '';
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP);
        if ($ip !== false && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return '';
        }

        return $url;
    }

    // ═══════════════════════════════════════════════════════
    // HILFSMETHODEN
    // ═══════════════════════════════════════════════════════

    /**
     * Gibt alle active Speakers zurück (ohne Filterung) für Admin-Auswahl in Events.
     */
    public function get_all_active(): array
    {
        $db = CMS\Database::instance();
        try {
            $stmt = $db->prepare(
                "SELECT id, first_name, last_name, position, company FROM {$db->prefix()}speakers
                 WHERE status = 'active' ORDER BY last_name, first_name"
            );
            $stmt->execute([]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    /** Unternehmen aus cms_companies für Auswahllisten */
    public function get_companies_for_select(): array
    {
        $db = CMS\Database::instance();
        try {
            if (!$this->table_exists($db->prefix() . 'companies')) {
                return [];
            }

            $stmt = $db->prepare(
                "SELECT id, name, location_city FROM {$db->prefix()}companies
                 WHERE status = 'active' ORDER BY name ASC"
            );
            $stmt->execute([]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    /** Events aus cms_events für Auswahllisten (Speaker-Zuweisung) */
    public function get_cms_events_for_select(): array
    {
        $db = CMS\Database::instance();
        try {
            if (!$this->table_exists($db->prefix() . 'events')) {
                return [];
            }

            $stmt = $db->prepare(
                "SELECT id, title, event_date, location_city
                 FROM {$db->prefix()}events
                 WHERE status = 'active'
                 ORDER BY event_date DESC LIMIT 200"
            );
            $stmt->execute([]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    /** Distinkte Städte für Filter-Dropdown */
    public function get_distinct_cities(): array
    {
        $db = CMS\Database::instance();
        try {
            $stmt = $db->prepare(
                "SELECT DISTINCT location_city FROM {$db->prefix()}speakers
                 WHERE status = 'active' AND location_city IS NOT NULL AND location_city <> ''
                 ORDER BY location_city ASC"
            );
            $stmt->execute([]);
            return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'location_city');
        } catch (\Throwable $e) { return []; }
    }

    /** Slug generieren (vorname-nachname-{id}) */
    public static function generate_slug(object $speaker): string
    {
        $normalize = static function (string $s): string {
            $s = mb_strtolower($s, 'UTF-8');
            $s = str_replace(
                ['ä','ö','ü','ß','à','á','â','ã','å',
                 'è','é','ê','ë','ì','í','î','ï',
                 'ò','ó','ô','õ','ø','ù','ú','û','ý','ÿ','ñ','ç'],
                ['ae','oe','ue','ss','a','a','a','a','a',
                 'e','e','e','e','i','i','i','i',
                 'o','o','o','o','o','u','u','u','y','y','n','c'],
                $s
            );
            return trim(preg_replace('/[^a-z0-9]+/', '-', $s), '-');
        };
        $fn   = $normalize(trim($speaker->first_name ?? '')) ?: 'speaker';
        $ln   = $normalize(trim($speaker->last_name  ?? ''));
        $base = $ln !== '' ? "{$fn}-{$ln}" : $fn;
        return $base . '-' . $speaker->id;
    }
}
