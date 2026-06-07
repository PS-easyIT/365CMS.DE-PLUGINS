<?php
/**
 * Database Manager für CMS Events
 *
 * @package CMS_Events
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('CMS_Events_Database', false)) {
    return;
}

final class CMS_Events_Database
{
    private static ?self $instance = null;
    private ?array $settingsCache = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Tables werden bei Plugin-Aktivierung erstellt
    }

    public function create_tables(): void
    {
        try {
            $db     = CMS\Database::instance();
            $pdo    = $db->getPdo();
            $prefix = $db->prefix();

            // ── Main events table ─────────────────────────────────────────
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}events (
                id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id           INT UNSIGNED DEFAULT NULL,
                title             VARCHAR(255) NOT NULL,
                excerpt           VARCHAR(500) DEFAULT NULL,
                description       TEXT DEFAULT NULL,
                event_date        DATE NOT NULL,
                event_time        TIME DEFAULT NULL,
                end_date          DATE DEFAULT NULL,
                end_time          TIME DEFAULT NULL,
                location          VARCHAR(255) DEFAULT NULL,
                address           TEXT DEFAULT NULL,
                city              VARCHAR(100) DEFAULT NULL,
                zip               VARCHAR(20) DEFAULT NULL,
                country           VARCHAR(100) DEFAULT 'Deutschland',
                category          VARCHAR(100) DEFAULT NULL,
                tags              TEXT DEFAULT NULL COMMENT 'JSON array',
                capacity          INT UNSIGNED DEFAULT NULL,
                registration_url  VARCHAR(500) DEFAULT NULL,
                price_type        ENUM('free','paid','donation') DEFAULT 'free',
                price             DECIMAL(10,2) DEFAULT NULL,
                price_currency    VARCHAR(10) DEFAULT 'EUR',
                image_url         VARCHAR(500) DEFAULT NULL,
                banner_url        VARCHAR(500) DEFAULT NULL,
                is_online         BOOLEAN DEFAULT FALSE,
                online_url        VARCHAR(500) DEFAULT NULL,
                is_featured       BOOLEAN DEFAULT FALSE,
                organizer_name    VARCHAR(255) DEFAULT NULL,
                organizer_email   VARCHAR(150) DEFAULT NULL,
                organizer_phone   VARCHAR(50) DEFAULT NULL,
                organizer_website VARCHAR(500) DEFAULT NULL,
                status            VARCHAR(20) NOT NULL DEFAULT 'published',
                created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status   (status),
                INDEX idx_date     (event_date),
                INDEX idx_category (category),
                INDEX idx_city     (city),
                INDEX idx_featured (is_featured)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // ── Event speakers (M2M) ──────────────────────────────────────
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}event_speakers (
                id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                event_id           INT UNSIGNED NOT NULL,
                speaker_id         INT UNSIGNED NOT NULL,
                speaker_type       ENUM('speaker','expert') DEFAULT 'speaker',
                role               VARCHAR(100) DEFAULT NULL,
                presentation_title VARCHAR(255) DEFAULT NULL,
                session_time       TIME DEFAULT NULL,
                created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_event   (event_id),
                INDEX idx_speaker (speaker_id),
                INDEX idx_type    (speaker_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // ── Event meta ────────────────────────────────────────────────
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}event_meta (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                event_id   INT UNSIGNED NOT NULL,
                meta_key   VARCHAR(255) NOT NULL,
                meta_value LONGTEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_event    (event_id),
                INDEX idx_meta_key (meta_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // ── Event categories (Preset-Kategorien) ──────────────────────
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}event_categories (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name       VARCHAR(150) NOT NULL,
                slug       VARCHAR(150) NOT NULL,
                icon       VARCHAR(10) DEFAULT '📂',
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // ── Event tag presets ─────────────────────────────────────────
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}event_tag_presets (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tag_name   VARCHAR(150) NOT NULL,
                tag_type   VARCHAR(50) NOT NULL DEFAULT 'general',
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $this->create_settings_table($pdo, $prefix);

            // Migrate + seed defaults
            $this->maybe_add_event_columns($pdo, $prefix);
            $this->maybe_add_indexes($pdo, $prefix);
            $this->maybe_add_foreign_keys($pdo, $prefix);
            $this->maybe_seed_default_data();

        } catch (\Throwable $e) {
            error_log('CMS Events DB Error: ' . $e->getMessage());
        }
    }

    /** Fügt neue Spalten zur bestehenden events-Tabelle hinzu (idempotent). */
    private function maybe_add_event_columns(\PDO $pdo, string $prefix): void
    {
        $existing = $this->event_table_columns($pdo, $prefix);
        if ($existing === []) {
            error_log('CMS Events: column detection returned empty result for events table migration.');
        }

        $alterations = [
            'user_id'           => 'INT UNSIGNED DEFAULT NULL',
            'excerpt'           => 'VARCHAR(500) DEFAULT NULL',
            'description'       => 'TEXT DEFAULT NULL',
            'event_date'        => 'DATE DEFAULT NULL',
            'event_time'        => 'TIME DEFAULT NULL',
            'end_date'          => 'DATE DEFAULT NULL',
            'end_time'          => 'TIME DEFAULT NULL',
            'location'          => 'VARCHAR(255) DEFAULT NULL',
            'address'           => 'TEXT DEFAULT NULL',
            'city'              => 'VARCHAR(100) DEFAULT NULL',
            'zip'               => 'VARCHAR(20) DEFAULT NULL',
            'country'           => "VARCHAR(100) DEFAULT 'Deutschland'",
            'category'          => 'VARCHAR(100) DEFAULT NULL',
            'tags'              => 'TEXT DEFAULT NULL',
            'capacity'          => 'INT UNSIGNED DEFAULT NULL',
            'registration_url'  => 'VARCHAR(500) DEFAULT NULL',
            'price_type'        => "ENUM('free','paid','donation') DEFAULT 'free'",
            'price'             => 'DECIMAL(10,2) DEFAULT NULL',
            'price_currency'    => "VARCHAR(10) DEFAULT 'EUR'",
            'image_url'         => 'VARCHAR(500) DEFAULT NULL',
            'banner_url'        => 'VARCHAR(500) DEFAULT NULL',
            'is_online'         => 'BOOLEAN DEFAULT FALSE',
            'online_url'        => 'VARCHAR(500) DEFAULT NULL',
            'is_featured'       => 'BOOLEAN DEFAULT FALSE',
            'organizer_name'    => 'VARCHAR(255) DEFAULT NULL',
            'organizer_email'   => 'VARCHAR(150) DEFAULT NULL',
            'organizer_phone'   => 'VARCHAR(50) DEFAULT NULL',
            'organizer_website' => 'VARCHAR(500) DEFAULT NULL',
            'status'            => "VARCHAR(20) NOT NULL DEFAULT 'published'",
        ];

        foreach ($alterations as $column => $definition) {
            if (!isset($existing[$column])) {
                try {
                    $pdo->exec('ALTER TABLE ' . $this->quote_identifier($prefix . 'events') . ' ADD COLUMN ' . $this->quote_identifier($column) . ' ' . $definition);
                } catch (\Throwable $e) {
                    error_log("CMS Events: ALTER TABLE add {$column} failed – " . $e->getMessage());
                }
            }
        }
    }

    /**
     * @return array<string,bool>
     */
    private function event_table_columns(?\PDO $pdo = null, ?string $prefix = null): array
    {
        try {
            $db = CMS\Database::instance();
            $pdo ??= $db->getPdo();
            $prefix ??= $db->prefix();
            $table = $prefix . 'events';

            $columns = [];
            try {
                $stmt = $pdo->prepare(
                    'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
                );
                $stmt->execute([$table]);
                foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [] as $columnName) {
                    $columns[(string) $columnName] = true;
                }
            } catch (\Throwable $e) {
                error_log('CMS Events INFORMATION_SCHEMA column lookup failed: ' . $e->getMessage());
            }

            if ($columns !== []) {
                return $columns;
            }

            try {
                $stmt = $pdo->query('SHOW COLUMNS FROM ' . $this->quote_identifier($table));
                foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
                    $field = (string) ($row['Field'] ?? '');
                    if ($field !== '') {
                        $columns[$field] = true;
                    }
                }
            } catch (\Throwable $e) {
                error_log('CMS Events SHOW COLUMNS fallback failed: ' . $e->getMessage());
            }

            return $columns;
        } catch (\Throwable $e) {
            error_log('CMS Events event_table_columns failed: ' . $e->getMessage());
            return [];
        }
    }

    private function ensure_event_schema_for_save(): void
    {
        try {
            $db     = CMS\Database::instance();
            $pdo    = $db->getPdo();
            $prefix = $db->prefix();

            if (!$this->table_exists($pdo, $prefix . 'events')) {
                $this->create_tables();
                return;
            }

            $this->maybe_add_event_columns($pdo, $prefix);
            $this->maybe_add_indexes($pdo, $prefix);
        } catch (\Throwable $e) {
            error_log('CMS Events ensure_event_schema_for_save skipped: ' . $e->getMessage());
        }
    }

    private function create_settings_table(\PDO $pdo, string $prefix): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}event_settings (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key   VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function maybe_add_foreign_keys(\PDO $pdo, string $prefix): void
    {
        $relations = [
            [
                'table'      => $prefix . 'event_speakers',
                'column'     => 'event_id',
                'ref_table'  => $prefix . 'events',
                'ref_column' => 'id',
                'name'       => 'fk_events_speakers_event',
            ],
            [
                'table'      => $prefix . 'event_meta',
                'column'     => 'event_id',
                'ref_table'  => $prefix . 'events',
                'ref_column' => 'id',
                'name'       => 'fk_events_meta_event',
            ],
        ];

        foreach ($relations as $relation) {
            $table     = (string) $relation['table'];
            $column    = (string) $relation['column'];
            $refTable  = (string) $relation['ref_table'];
            $refColumn = (string) $relation['ref_column'];

            if (
                !$this->table_exists($pdo, $table)
                || !$this->table_exists($pdo, $refTable)
                || !$this->column_exists($pdo, $table, $column)
                || !$this->column_exists($pdo, $refTable, $refColumn)
                || $this->foreign_key_relation_exists($pdo, $table, $column, $refTable, $refColumn)
            ) {
                continue;
            }

            $constraint = $this->foreign_key_name($prefix, (string) $relation['name']);
            $sql = sprintf(
                'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s(%s) ON DELETE CASCADE',
                $this->quote_identifier($table),
                $this->quote_identifier($constraint),
                $this->quote_identifier($column),
                $this->quote_identifier($refTable),
                $this->quote_identifier($refColumn)
            );

            try {
                $pdo->exec($sql);
            } catch (\Throwable $e) {
                error_log('CMS Events foreign key skipped (' . $constraint . '): ' . $e->getMessage());
            }
        }
    }

    private function table_exists(\PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare(
            'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1'
        );
        $stmt->execute([$table]);

        return $stmt->fetchColumn() !== false;
    }

    private function column_exists(\PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
        );
        $stmt->execute([$table, $column]);

        return $stmt->fetchColumn() !== false;
    }

    private function foreign_key_relation_exists(\PDO $pdo, string $table, string $column, string $refTable, string $refColumn): bool
    {
        $stmt = $pdo->prepare(
            'SELECT CONSTRAINT_NAME
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME = ?
               AND REFERENCED_COLUMN_NAME = ?
             LIMIT 1'
        );
        $stmt->execute([$table, $column, $refTable, $refColumn]);

        return $stmt->fetchColumn() !== false;
    }

    private function foreign_key_name(string $prefix, string $baseName): string
    {
        $normalizedPrefix = trim((string) preg_replace('/[^a-zA-Z0-9_]+/', '_', $prefix), '_');
        $normalizedBase   = trim((string) preg_replace('/[^a-zA-Z0-9_]+/', '_', preg_replace('/^fk_/', '', $baseName)), '_');
        $name             = 'fk_' . ($normalizedPrefix !== '' ? $normalizedPrefix . '_' : '') . $normalizedBase;

        if (strlen($name) <= 64) {
            return $name;
        }

        return substr($name, 0, 53) . '_' . substr(hash('sha256', $name), 0, 10);
    }

    private function quote_identifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function maybe_add_indexes(\PDO $pdo, string $prefix): void
    {
        $table = $prefix . 'events';
        if (!$this->table_exists($pdo, $table) || $this->index_exists($pdo, $table, 'idx_event_date_status')) {
            return;
        }

        try {
            $pdo->exec('ALTER TABLE ' . $this->quote_identifier($table) . ' ADD INDEX idx_event_date_status (event_date, status)');
        } catch (\Throwable $e) {
            error_log('CMS Events index idx_event_date_status skipped: ' . $e->getMessage());
        }
    }

    private function index_exists(\PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare(
            'SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1'
        );
        $stmt->execute([$table, $index]);

        return $stmt->fetchColumn() !== false;
    }

    public function get_event(int $id): ?object
    {
        $db = CMS\Database::instance();
        $stmt = $db->prepare("SELECT * FROM {$db->prefix()}events WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function get_events(array $args = []): array
    {
        $db = CMS\Database::instance();
        
        $where = [];
        $params = [];

        if (!empty($args['status'])) {
            $where[] = 'status = ?';
            $params[] = $args['status'];
        }

        if (!empty($args['category'])) {
            $where[] = 'category = ?';
            $params[] = $args['category'];
        }

        if (!empty($args['city'])) {
            $where[] = 'city = ?';
            $params[] = $args['city'];
        }

        if (!empty($args['upcoming'])) {
            $where[] = 'event_date >= CURDATE()';
        }

        if (!empty($args['past'])) {
            $where[] = 'event_date < CURDATE()';
        }

        if (!empty($args['search'])) {
            $where[] = '(title LIKE ? OR description LIKE ? OR organizer_name LIKE ?)';
            $term = '%' . $args['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($args['month'])) {
            $where[] = 'DATE_FORMAT(event_date, \'%Y-%m\') = ?';
            $params[] = $args['month'];
        }

        if (!empty($args['year'])) {
            $where[] = 'YEAR(event_date) = ?';
            $params[] = (int) $args['year'];
        }

        if (!empty($args['month_number'])) {
            $where[] = 'MONTH(event_date) = ?';
            $params[] = (int) $args['month_number'];
        }

        if (!empty($args['from_month'])) {
            $where[] = 'event_date >= ?';
            $params[] = $args['from_month'];
        }

        if (isset($args['is_online'])) {
            $where[] = 'is_online = ?';
            $params[] = (int)$args['is_online'];
        }
        if (!empty($args['user_id'])) {
            $where[] = 'user_id = ?';
            $params[] = (int) $args['user_id'];
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $order = 'ORDER BY event_date ASC, event_time ASC';
        $limitValue = isset($args['limit']) ? max(1, min(200, (int) $args['limit'])) : null;
        $offsetValue = isset($args['offset']) ? max(0, (int) $args['offset']) : null;
        $limit = $limitValue !== null ? 'LIMIT ' . $limitValue : '';
        $offset = ($limitValue !== null && $offsetValue !== null) ? 'OFFSET ' . $offsetValue : '';

        $sql = "SELECT * FROM {$db->prefix()}events {$where_clause} {$order} {$limit} {$offset}";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function save_event(array $data): int
    {
        $this->ensure_event_schema_for_save();

        $db = CMS\Database::instance();
        $event_id = (int)($data['id'] ?? 0);

        if (trim((string) ($data['title'] ?? '')) === '') {
            $data['title'] = 'Unbenanntes Event';
        }
        if (trim((string) ($data['event_date'] ?? '')) === '') {
            $data['event_date'] = date('Y-m-d');
        }

        if ($event_id > 0 && !CMS\Auth::instance()->isAdmin()) {
            $current_user_id = (int) (CMS\Auth::instance()->currentUser()?->id ?? 0);
            if ($current_user_id <= 0) {
                return 0;
            }

            $owner_stmt = $db->prepare("SELECT user_id FROM {$db->prefix()}events WHERE id = ? LIMIT 1");
            $owner_stmt->execute([$event_id]);
            $owner_id = (int) ($owner_stmt->fetchColumn() ?: 0);

            if ($owner_id <= 0 || $owner_id !== $current_user_id) {
                return 0;
            }
        }

        $event_data = [
            'title'             => $data['title']             ?? '',
            'excerpt'           => $data['excerpt']           ?? null,
            'description'       => $data['description']       ?? null,
            'event_date'        => $data['event_date']        ?? null,
            'event_time'        => !empty($data['event_time'])  ? $data['event_time']  : null,
            'end_date'          => !empty($data['end_date'])    ? $data['end_date']    : null,
            'end_time'          => !empty($data['end_time'])    ? $data['end_time']    : null,
            'location'          => $data['location']          ?? null,
            'address'           => $data['address']           ?? null,
            'city'              => $data['city']              ?? null,
            'zip'               => $data['zip']               ?? null,
            'country'           => $data['country']           ?? 'Deutschland',
            'category'          => $data['category']          ?? null,
            'tags'              => isset($data['tags']) && is_array($data['tags'])
                                        ? json_encode(array_values(array_filter($data['tags'])))
                                        : ($data['tags'] ?? null),
            'capacity'          => !empty($data['capacity'])  ? (int)$data['capacity'] : null,
            'registration_url'  => $data['registration_url']  ?? null,
            'price_type'        => $data['price_type']        ?? 'free',
            'price'             => !empty($data['price'])     ? (float)$data['price'] : null,
            'price_currency'    => $data['price_currency']    ?? 'EUR',
            'image_url'         => $data['image_url']         ?? null,
            'banner_url'        => $data['banner_url']        ?? null,
            'is_online'         => !empty($data['is_online'])  ? 1 : 0,
            'online_url'        => $data['online_url']        ?? null,
            'is_featured'       => !empty($data['is_featured']) ? 1 : 0,
            'organizer_name'    => $data['organizer_name']    ?? null,
            'organizer_email'   => $data['organizer_email']   ?? null,
            'organizer_phone'   => $data['organizer_phone']   ?? null,
            'organizer_website' => $data['organizer_website'] ?? null,
            'status'            => $data['status']            ?? 'published',
        ];

        $existing_columns = $this->event_table_columns();
        if ($existing_columns !== []) {
            $event_data = array_intersect_key($event_data, $existing_columns);
        }

        if ($event_data === []) {
            error_log('CMS Events save_event aborted: no writable event columns detected.');
            return 0;
        }

        if ($event_id > 0) {
            try {
                if (!$db->update('events', $event_data, ['id' => $event_id])) {
                    error_log('CMS Events save_event update failed: ' . (string) ($db->last_error ?? 'unknown error'));
                    return $this->save_event_legacy_update($event_id, $event_data) || $this->save_event_columnwise_update($event_id, $event_data) ? $event_id : 0;
                }
            } catch (\Throwable $e) {
                error_log('CMS Events save_event update exception: ' . $e->getMessage());
                return $this->save_event_legacy_update($event_id, $event_data) || $this->save_event_columnwise_update($event_id, $event_data) ? $event_id : 0;
            }
        } else {
            $event_data['user_id'] = CMS\Auth::instance()->currentUser()?->id ?? null;
            try {
                $insert_result = $db->insert('events', $event_data);
            } catch (\Throwable $e) {
                error_log('CMS Events save_event insert exception: ' . $e->getMessage());
                return 0;
            }
            if ($insert_result) {
                $event_id = (int)$insert_result;
                CMS\Hooks::doAction('event_created', $event_id);
            } else {
                error_log('CMS Events save_event insert failed: ' . (string) ($db->last_error ?? 'unknown error'));
            }
        }

        return $event_id;
    }

    /** @param array<string,mixed> $event_data */
    private function save_event_legacy_update(int $event_id, array $event_data): bool
    {
        if ($event_id <= 0) {
            return false;
        }

        $legacy_columns = array_flip([
            'title',
            'description',
            'event_date',
            'event_time',
            'end_date',
            'end_time',
            'location',
            'address',
            'city',
            'zip',
            'country',
            'category',
            'capacity',
            'registration_url',
            'image_url',
            'is_online',
            'online_url',
            'status',
        ]);

        $data = array_intersect_key($event_data, $legacy_columns);
        $existing_columns = $this->event_table_columns();
        if ($existing_columns !== []) {
            $data = array_intersect_key($data, $existing_columns);
        }

        if (($data['status'] ?? '') === 'completed') {
            unset($data['status']);
        }

        if ($data === []) {
            return false;
        }

        try {
            $db = CMS\Database::instance();
            if ($db->update('events', $data, ['id' => $event_id])) {
                error_log('CMS Events save_event legacy update succeeded for event_id=' . $event_id);
                return true;
            }

            error_log('CMS Events save_event legacy update failed: ' . (string) ($db->last_error ?? 'unknown error'));
        } catch (\Throwable $e) {
            error_log('CMS Events save_event legacy update exception: ' . $e->getMessage());
        }

        return false;
    }

    /** @param array<string,mixed> $event_data */
    private function save_event_columnwise_update(int $event_id, array $event_data): bool
    {
        if ($event_id <= 0 || $event_data === []) {
            return false;
        }

        $existing_columns = $this->event_table_columns();
        if ($existing_columns !== []) {
            $event_data = array_intersect_key($event_data, $existing_columns);
        }

        unset($event_data['id'], $event_data['created_at'], $event_data['updated_at']);

        if ($event_data === []) {
            return false;
        }

        $savedAny = false;
        $db = CMS\Database::instance();
        foreach ($event_data as $column => $value) {
            try {
                if ($column === 'status' && !in_array((string) $value, ['published', 'draft', 'cancelled'], true)) {
                    continue;
                }

                if ($db->update('events', [$column => $value], ['id' => $event_id])) {
                    $savedAny = true;
                } else {
                    error_log('CMS Events save_event column update failed for ' . $column . ': ' . (string) ($db->last_error ?? 'unknown error'));
                }
            } catch (\Throwable $e) {
                error_log('CMS Events save_event column update exception for ' . $column . ': ' . $e->getMessage());
            }
        }

        if ($savedAny) {
            error_log('CMS Events save_event columnwise update succeeded for event_id=' . $event_id);
        }

        return $savedAny;
    }

    public function assign_speaker(int $event_id, int $speaker_id, string $speaker_type = 'speaker', array $data = []): bool
    {
        if ($event_id <= 0 || $speaker_id <= 0) {
            return false;
        }

        $db = CMS\Database::instance();

        $speaker_type = in_array($speaker_type, ['speaker', 'expert'], true) ? $speaker_type : 'speaker';

        $exists = $db->prepare("SELECT id FROM {$db->prefix()}event_speakers WHERE event_id = ? AND speaker_id = ? AND speaker_type = ? LIMIT 1");
        $exists->execute([$event_id, $speaker_id, $speaker_type]);
        if ((int) ($exists->fetchColumn() ?: 0) > 0) {
            return true;
        }

        $speaker_data = [
            'event_id' => $event_id,
            'speaker_id' => $speaker_id,
            'speaker_type' => $speaker_type,
            'role' => $data['role'] ?? null,
            'presentation_title' => $data['presentation_title'] ?? null,
            'session_time' => $data['session_time'] ?? null,
        ];

        $result = $db->insert('event_speakers', $speaker_data);
        
        if ($result !== false) {
            CMS\Hooks::doAction('event_speaker_assigned', $event_id, $speaker_id, $speaker_type);
        }

        return $result !== false;
    }

    public function get_event_speakers(int $event_id): array
    {
        $db = CMS\Database::instance();
        
        $sql = "
            SELECT es.*,
                   COALESCE(s.first_name,    e.first_name)    AS first_name,
                   COALESCE(s.last_name,     e.last_name)     AS last_name,
                   COALESCE(s.photo_url,     e.photo_url)     AS photo_url,
                   COALESCE(s.position,      e.position)      AS position,
                   COALESCE(s.short_bio,     e.biography)     AS short_bio,
                   COALESCE(s.company,       e.company)       AS company,
                   COALESCE(s.location_city, e.location_city) AS location_city,
                   CASE
                       WHEN es.speaker_type = 'speaker' THEN CONCAT(s.first_name, ' ', s.last_name)
                       WHEN es.speaker_type = 'expert'  THEN CONCAT(e.first_name, ' ', e.last_name)
                   END AS speaker_name
            FROM {$db->prefix()}event_speakers es
            LEFT JOIN {$db->prefix()}speakers s ON es.speaker_id = s.id AND es.speaker_type = 'speaker'
            LEFT JOIN {$db->prefix()}experts e  ON es.speaker_id = e.id AND es.speaker_type = 'expert'
            WHERE es.event_id = ?
            ORDER BY es.session_time ASC, es.id ASC
        ";
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([$event_id]);

            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('CMS Events get_event_speakers skipped: ' . $e->getMessage());
            return [];
        }
    }

    public function save_meta(int $event_id, string $meta_key, $meta_value): bool
    {
        $db = CMS\Database::instance();

        $stmt = $db->prepare("DELETE FROM {$db->prefix()}event_meta WHERE event_id = ? AND meta_key = ?");
        $stmt->execute([$event_id, $meta_key]);

        $result = $db->insert('event_meta', [
            'event_id' => $event_id,
            'meta_key' => $meta_key,
            'meta_value' => is_array($meta_value) ? json_encode($meta_value) : $meta_value,
        ]);

        return $result !== false;
    }

    public function get_meta(int $event_id, string $meta_key, $default = null)
    {
        $db = CMS\Database::instance();
        $stmt = $db->prepare("SELECT meta_value FROM {$db->prefix()}event_meta WHERE event_id = ? AND meta_key = ?");
        $stmt->execute([$event_id, $meta_key]);
        $result = $stmt->fetch();

        if (!$result) {
            return $default;
        }

        $value = $result->meta_value;
        $decoded = json_decode($value, true);

        return $decoded !== null ? $decoded : $value;
    }

    // ── Settings ─────────────────────────────────────────────────────

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
                foreach ($settingsService->getGroup('cms-events') as $key => $value) {
                    $settings[(string) $key] = is_scalar($value) || $value === null
                        ? (string) $value
                        : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            } catch (\Throwable $e) {
                error_log('CMS Events SettingsService getGroup failed: ' . $e->getMessage());
            }
        }

        return $this->settingsCache = $settings;
    }

    public function save_settings(array $settings): void
    {
        $this->settingsCache = null;
        $settingsService = $this->settings_service();
        if ($settingsService !== null) {
            try {
                if ($settingsService->setMany('cms-events', $settings, [], 0)) {
                    return;
                }
            } catch (\Throwable $e) {
                error_log('CMS Events SettingsService save failed: ' . $e->getMessage());
            }
        }

        $db = CMS\Database::instance();
        $this->maybe_create_settings_table();

        foreach ($settings as $key => $value) {
            $stmt = $db->prepare(
                "INSERT INTO {$db->prefix()}event_settings (setting_key, setting_value)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            );
            $stmt->execute([$key, (string)$value]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function get_legacy_settings(): array
    {
        $db = CMS\Database::instance();

        try {
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM {$db->prefix()}event_settings");
            $stmt->execute();
            $rows = $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }

        $settings = [];
        foreach ($rows as $row) {
            $key = (string) ($row->setting_key ?? '');
            if ($key === '') {
                continue;
            }
            $settings[$key] = (string) ($row->setting_value ?? '');
        }

        return $settings;
    }

    private function settings_service(): ?\CMS\Services\SettingsService
    {
        if (!class_exists('CMS\\Services\\SettingsService')) {
            return null;
        }

        try {
            return \CMS\Services\SettingsService::getInstance();
        } catch (\Throwable $e) {
            error_log('CMS Events SettingsService unavailable: ' . $e->getMessage());
            return null;
        }
    }

    private function maybe_create_settings_table(): void
    {
        $db = CMS\Database::instance();
        $this->create_settings_table($db->getPdo(), $db->prefix());
    }

    public function drop_tables(): void
    {
        error_log('CMS Events drop_tables skipped: plugin data is retained on uninstall/deactivation.');
    }

    public function delete_event(int $id): bool
    {
        $db = CMS\Database::instance();
        try {
            $stmt = $db->prepare("DELETE FROM {$db->prefix()}events WHERE id = ?");
            $stmt->execute([$id]);
            // Cascade-Delete Speaker- und Meta-Einträge
            $db->prepare("DELETE FROM {$db->prefix()}event_speakers WHERE event_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM {$db->prefix()}event_meta WHERE event_id = ?")->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            error_log('CMS Events delete_event: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Setzt den Status eines Events.
     *
     * @param int    $id     Event-ID
     * @param string $status Erlaubte Werte: published, draft, cancelled, completed
     */
    public function set_event_status(int $id, string $status): bool
    {
        $allowed = ['published', 'draft', 'cancelled', 'completed'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $db = CMS\Database::instance();
        return $db->update('events', ['status' => $status], ['id' => $id]) !== false;
    }

    // ── Event Categories ─────────────────────────────────────────────

    public function get_event_categories(): array
    {
        $db = CMS\Database::instance();
        try {
            $stmt = $db->prepare(
                "SELECT * FROM {$db->prefix()}event_categories ORDER BY sort_order ASC, name ASC"
            );
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function add_event_category(string $name, string $icon = '📂'): bool
    {
        $db   = CMS\Database::instance();
        $map  = ['ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss','Ä'=>'ae','Ö'=>'oe','Ü'=>'ue'];
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', str_replace(array_keys($map), array_values($map), $name)));
        $slug = trim($slug, '-') ?: 'kategorie';
        try {
            $result = $db->insert('event_categories', [
                'name' => $name,
                'slug' => $slug,
                'icon' => $icon ?: '📂',
            ]);
            return $result !== false;
        } catch (\Throwable $e) {
            error_log('CMS Events add_event_category: ' . $e->getMessage());
            return false;
        }
    }

    public function delete_event_category(int $id): bool
    {
        $db = CMS\Database::instance();
        try {
            $stmt = $db->prepare("DELETE FROM {$db->prefix()}event_categories WHERE id = ? AND id > 0");
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            error_log('CMS Events delete_event_category: ' . $e->getMessage());
            return false;
        }
    }

    // ── Event Tag Presets ────────────────────────────────────────────

    public function get_event_tag_presets(string $type = ''): array
    {
        $db = CMS\Database::instance();
        try {
            if ($type) {
                $stmt = $db->prepare(
                    "SELECT * FROM {$db->prefix()}event_tag_presets WHERE tag_type = ? ORDER BY sort_order ASC, tag_name ASC"
                );
                $stmt->execute([$type]);
            } else {
                $stmt = $db->prepare(
                    "SELECT * FROM {$db->prefix()}event_tag_presets ORDER BY tag_type ASC, sort_order ASC, tag_name ASC"
                );
                $stmt->execute();
            }
            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function get_event_tag_presets_grouped(): array
    {
        $all    = $this->get_event_tag_presets();
        $groups = ['general' => [], 'special' => [], 'format' => []];
        foreach ($all as $tag) {
            $t = $tag->tag_type ?? 'general';
            if (!isset($groups[$t])) $groups[$t] = [];
            $groups[$t][] = $tag;
        }
        return $groups;
    }

    public function add_event_tag_preset(string $name, string $type = 'general'): bool
    {
        $db = CMS\Database::instance();
        try {
            $result = $db->insert('event_tag_presets', [
                'tag_name' => $name,
                'tag_type' => $type,
            ]);
            return $result !== false;
        } catch (\Throwable $e) {
            error_log('CMS Events add_event_tag_preset: ' . $e->getMessage());
            return false;
        }
    }

    public function delete_event_tag_preset(int $id): bool
    {
        $db = CMS\Database::instance();
        try {
            $stmt = $db->prepare("DELETE FROM {$db->prefix()}event_tag_presets WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            error_log('CMS Events delete_event_tag_preset: ' . $e->getMessage());
            return false;
        }
    }

    private function default_settings(): array
    {
        return [
            // Archiv
            'archive_title'           => 'Veranstaltungen',
            'archive_description'     => 'Aktuelle Veranstaltungen entdecken',
            'archive_slug'            => 'events',
            'show_nav_link'           => '0',
            'nav_label'               => 'Veranstaltungen',
            'per_page'                => '12',
            'grid_columns'            => '3',
            // Header
            'archive_header_icon'     => '📅',
            // Farben
            'color_primary'           => '#3b82f6',
            'color_accent'            => '#60a5fa',
            'color_hdr_from'          => '#1d4ed8',
            'color_hdr_to'            => '#3b82f6',
            'color_hdr_title'         => '#ffffff',
            'color_card_bg'           => '#f0f7ff',
            'color_card_border'       => '#bfdbfe',
            'color_cta'               => '#1e40af',
            'color_detail_hdr_bg'     => '#0f172a',
            'color_detail_hdr_text'   => '#ffffff',
            'color_detail_accent'     => '#3b82f6',
            'color_featured_border'   => '#f59e0b',
            'color_cancelled_bg'      => '#fee2e2',
            'color_online_badge'      => '#059669',
            'color_badge_published_bg'    => '#d1fae5',
            'color_badge_published_color' => '#065f46',
            'color_badge_draft_bg'        => '#fef3c7',
            'color_badge_draft_color'     => '#92400e',
            'color_badge_cancelled_bg'    => '#fee2e2',
            'color_badge_cancelled_color' => '#991b1b',
            'color_badge_completed_bg'    => '#dbeafe',
            'color_badge_completed_color' => '#1e40af',
            'color_badge_featured_bg'     => '#fef3c7',
            'color_badge_featured_color'  => '#92400e',
            'color_badge_online_bg'       => '#d1fae5',
            'color_badge_online_color'    => '#065f46',
            // Layout
            'border_radius'           => '12',
            // Anzeige-Schalter
            'show_category'           => '1',
            'show_city'               => '1',
            'show_capacity'           => '1',
            'show_speakers'           => '1',
            'show_price'              => '1',
            'show_organizer'          => '1',
            'show_tags'               => '1',
            'show_status_badge'       => '1',
            'show_featured_badge'     => '1',
            'show_online_badge'       => '1',
            'show_date_pill'          => '1',
            'show_time_pill'          => '1',
        ];
    }

    public function remove_event_speaker(int $id): bool
    {
        $db = CMS\Database::instance();
        try {
            $stmt = $db->prepare("DELETE FROM {$db->prefix()}event_speakers WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            error_log('CMS Events remove_event_speaker: ' . $e->getMessage());
            return false;
        }
    }

    public function get_available_speakers(): array
    {
        $db  = CMS\Database::instance();
        $out = ['speakers' => [], 'experts' => []];
        try {
            $stmt = $db->prepare("SELECT id, first_name, last_name FROM {$db->prefix()}speakers WHERE status = 'active' ORDER BY last_name, first_name");
            $stmt->execute();
            $out['speakers'] = $stmt->fetchAll();
        } catch (\Throwable) {}
        try {
            $stmt = $db->prepare("SELECT id, first_name, last_name FROM {$db->prefix()}experts WHERE status = 'active' ORDER BY last_name, first_name");
            $stmt->execute();
            $out['experts'] = $stmt->fetchAll();
        } catch (\Throwable) {}
        return $out;
    }

    private function maybe_seed_default_data(): void
    {
        $db = CMS\Database::instance();
        // Kategorien nur seeden wenn Tabelle leer
        try {
            $count = $db->prepare("SELECT COUNT(*) as c FROM {$db->prefix()}event_categories");
            $count->execute();
            if ((int)($count->fetch()->c ?? 0) === 0) {
                $cats = [
                    ['Konferenz',   '🏛️'], ['Workshop',   '🛠️'], ['Webinar',    '💻'],
                    ['Meetup',      '🤝'], ['Training',   '📚'], ['Hackathon',  '💡'],
                    ['Networking',  '🌐'], ['Messe',      '🏪'], ['Seminar',    '📖'],
                    ['Podiumsdisk.','🎙️'],
                ];
                foreach ($cats as $i => [$name, $icon]) {
                    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
                    try {
                        $db->insert('event_categories', ['name' => $name, 'slug' => $slug, 'icon' => $icon, 'sort_order' => $i]);
                    } catch (\Throwable) {}
                }
            }
        } catch (\Throwable) {}
        // Tag-Presets nur seeden wenn leer
        try {
            $count = $db->prepare("SELECT COUNT(*) as c FROM {$db->prefix()}event_tag_presets");
            $count->execute();
            if ((int)($count->fetch()->c ?? 0) === 0) {
                $tags = [
                    ['Online',       'general'], ['Hybrid',       'general'], ['Präsenz',      'general'],
                    ['Kostenlos',    'general'], ['Business',     'general'], ['Startup',      'general'],
                    ['KI / AI',      'general'], ['Sustainability','general'],
                    ['Featured',     'special'], ['VIP',          'special'], ['Ausgebucht',   'special'],
                    ['Neue Termine', 'special'], ['Zertifikat',   'special'],
                    ['Keynote',      'format'],  ['Panel',        'format'],  ['Q&A',          'format'],
                    ['Demo',         'format'],  ['Pitch',        'format'],  ['Deep-Dive',    'format'],
                ];
                foreach ($tags as $i => [$name, $type]) {
                    try {
                        $db->insert('event_tag_presets', ['tag_name' => $name, 'tag_type' => $type, 'sort_order' => $i]);
                    } catch (\Throwable) {}
                }
            }
        } catch (\Throwable) {}
    }

    // ── Count / Distinct ─────────────────────────────────────────────

    public function count_events(array $args = []): int
    {
        $db   = CMS\Database::instance();
        $sql  = "SELECT COUNT(*) as cnt FROM {$db->prefix()}events WHERE 1=1";
        $bind = [];

        if (!empty($args['status'])) {
            $sql   .= ' AND status = ?';
            $bind[] = $args['status'];
        }
        if (!empty($args['city'])) {
            $sql   .= ' AND city = ?';
            $bind[] = $args['city'];
        }
        if (!empty($args['category'])) {
            $sql   .= ' AND category = ?';
            $bind[] = $args['category'];
        }
        if (!empty($args['search'])) {
            $sql   .= ' AND (title LIKE ? OR description LIKE ? OR organizer_name LIKE ?)';
            $bind[] = '%' . $args['search'] . '%';
            $bind[] = '%' . $args['search'] . '%';
            $bind[] = '%' . $args['search'] . '%';
        }
        if (!empty($args['month'])) {
            $sql   .= ' AND DATE_FORMAT(event_date, \'%Y-%m\') = ?';
            $bind[] = $args['month'];
        }
        if (!empty($args['year'])) {
            $sql   .= ' AND YEAR(event_date) = ?';
            $bind[] = (int) $args['year'];
        }
        if (!empty($args['month_number'])) {
            $sql   .= ' AND MONTH(event_date) = ?';
            $bind[] = (int) $args['month_number'];
        }
        if (!empty($args['from_month'])) {
            $sql   .= ' AND event_date >= ?';
            $bind[] = $args['from_month'];
        }
        if (!empty($args['upcoming'])) {
            $sql   .= ' AND event_date >= CURDATE()';
        }
        if (!empty($args['past'])) {
            $sql   .= ' AND event_date < CURDATE()';
        }
        if (isset($args['is_online'])) {
            $sql   .= ' AND is_online = ?';
            $bind[] = (int)$args['is_online'];
        }
        if (!empty($args['user_id'])) {
            $sql   .= ' AND user_id = ?';
            $bind[] = (int) $args['user_id'];
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($bind);
        $row = $stmt->fetch();

        return (int)($row->cnt ?? 0);
    }

    public function get_distinct_cities(): array
    {
        $db = CMS\Database::instance();
        $stmt = $db->prepare(
            "SELECT DISTINCT city FROM {$db->prefix()}events
             WHERE city IS NOT NULL AND city != '' AND status = 'published'
             ORDER BY city"
        );
        $stmt->execute();
        return array_values(array_filter(array_map('strval', $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [])));
    }

    public function get_distinct_categories(): array
    {
        $db = CMS\Database::instance();
        $stmt = $db->prepare(
            "SELECT DISTINCT category FROM {$db->prefix()}events
             WHERE category IS NOT NULL AND category != '' AND status = 'published'
             ORDER BY category"
        );
        $stmt->execute();
        return array_values(array_filter(array_map('strval', $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [])));
    }

    public function get_speaker_events(int $speaker_id): array
    {
        $db = CMS\Database::instance();
        $stmt = $db->prepare(
            "SELECT e.*, es.role, es.presentation_title, es.session_time
             FROM {$db->prefix()}events e
             JOIN {$db->prefix()}event_speakers es ON e.id = es.event_id
             WHERE es.speaker_id = ? AND es.speaker_type = 'speaker'
             ORDER BY e.event_date DESC"
        );
        $stmt->execute([$speaker_id]);
        return $stmt->fetchAll();
    }
}
