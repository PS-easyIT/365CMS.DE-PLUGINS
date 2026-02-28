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

final class CMS_Events_Database
{
    private static ?self $instance = null;

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

            // Migrate + seed defaults
            $this->maybe_add_event_columns($pdo, $prefix);
            $this->maybe_seed_default_data();

        } catch (\Throwable $e) {
            error_log('CMS Events DB Error: ' . $e->getMessage());
        }
    }

    /** Fügt neue Spalten zur bestehenden events-Tabelle hinzu (idempotent). */
    private function maybe_add_event_columns(\PDO $pdo, string $prefix): void
    {
        $existing = [];
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM {$prefix}events");
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $col) {
                $existing[$col['Field']] = true;
            }
        } catch (\Throwable) {
            return;
        }

        $alterations = [
            'excerpt'           => "VARCHAR(500) DEFAULT NULL AFTER title",
            'tags'              => "TEXT DEFAULT NULL AFTER category",
            'price_type'        => "ENUM('free','paid','donation') DEFAULT 'free' AFTER registration_url",
            'price'             => "DECIMAL(10,2) DEFAULT NULL AFTER price_type",
            'price_currency'    => "VARCHAR(10) DEFAULT 'EUR' AFTER price",
            'banner_url'        => "VARCHAR(500) DEFAULT NULL AFTER image_url",
            'is_featured'       => "BOOLEAN DEFAULT FALSE AFTER is_online",
            'organizer_name'    => "VARCHAR(255) DEFAULT NULL AFTER is_featured",
            'organizer_email'   => "VARCHAR(150) DEFAULT NULL AFTER organizer_name",
            'organizer_phone'   => "VARCHAR(50) DEFAULT NULL AFTER organizer_email",
            'organizer_website' => "VARCHAR(500) DEFAULT NULL AFTER organizer_phone",
        ];

        foreach ($alterations as $column => $definition) {
            if (!isset($existing[$column])) {
                try {
                    $pdo->exec("ALTER TABLE {$prefix}events ADD COLUMN {$column} {$definition}");
                } catch (\Throwable $e) {
                    error_log("CMS Events: ALTER TABLE add {$column} failed – " . $e->getMessage());
                }
            }
        }
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
        $limit = isset($args['limit']) ? 'LIMIT ' . (int)$args['limit'] : '';
        $offset = isset($args['offset']) ? 'OFFSET ' . (int)$args['offset'] : '';

        $sql = "SELECT * FROM {$db->prefix()}events {$where_clause} {$order} {$limit} {$offset}";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function save_event(array $data): int
    {
        $db = CMS\Database::instance();
        $event_id = (int)($data['id'] ?? 0);

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

        if ($event_id > 0) {
            $db->update('events', $event_data, ['id' => $event_id]);
        } else {
            $event_data['user_id'] = CMS\Auth::instance()->currentUser()?->id ?? null;
            $insert_result = $db->insert('events', $event_data);
            if ($insert_result) {
                $event_id = (int)$insert_result;
                CMS\Hooks::doAction('event_created', $event_id);
            }
        }

        return $event_id;
    }

    public function assign_speaker(int $event_id, int $speaker_id, string $speaker_type = 'speaker', array $data = []): bool
    {
        $db = CMS\Database::instance();

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
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$event_id]);

        return $stmt->fetchAll();
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
        $db = CMS\Database::instance();

        // Falls es keine Settings-Tabelle gibt, Standardwerte zurückgeben
        try {
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM {$db->prefix()}event_settings");
            $stmt->execute();
            $rows = $stmt->fetchAll();
        } catch (\Throwable $e) {
            return $this->default_settings();
        }

        $settings = $this->default_settings();
        foreach ($rows as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }

        return $settings;
    }

    public function save_settings(array $settings): void
    {
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

    private function maybe_create_settings_table(): void
    {
        $db = CMS\Database::instance();
        $db->prepare(
            "CREATE TABLE IF NOT EXISTS {$db->prefix()}event_settings (
                id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key   VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT,
                updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        )->execute();
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
            'archive_title'           => 'Events',
            'archive_description'     => 'Aktuelle Veranstaltungen entdecken',
            'archive_slug'            => 'events',
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
            $sql   .= ' AND (title LIKE ? OR description LIKE ?)';
            $bind[] = '%' . $args['search'] . '%';
            $bind[] = '%' . $args['search'] . '%';
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
        return array_column($stmt->fetchAll(), 'city');
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
        return array_column($stmt->fetchAll(), 'category');
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
