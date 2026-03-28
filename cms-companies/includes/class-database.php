<?php
/**
 * Database Manager für CMS Companies
 *
 * @package CMS_Companies
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Companies_Database
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
        // Constructor
    }

    /**
     * Erstellt alle Datenbank-Tabellen
     */
    public function create_tables(): void
    {
        $db = CMS\Database::instance();
        $pdo = $db->getPdo();
        $prefix = $db->prefix();

        try {
            // Main companies table
            $sql = "CREATE TABLE IF NOT EXISTS {$prefix}companies (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED DEFAULT NULL COMMENT 'Verknüpfung zu CMS User',
                name VARCHAR(255) NOT NULL,
                email VARCHAR(150) NOT NULL,
                phone VARCHAR(50) DEFAULT NULL,
                industry VARCHAR(150) DEFAULT NULL,
                company_size VARCHAR(50) DEFAULT NULL,
                description TEXT DEFAULT NULL,
                logo_url VARCHAR(500) DEFAULT NULL,
                website VARCHAR(500) DEFAULT NULL,
                location_city VARCHAR(100) DEFAULT NULL,
                location_zip VARCHAR(20) DEFAULT NULL,
                location_country VARCHAR(100) DEFAULT NULL,
                founded_year INT DEFAULT NULL,
                employee_count INT DEFAULT NULL,
                is_partner BOOLEAN DEFAULT FALSE,
                is_top_partner BOOLEAN DEFAULT FALSE,
                is_sponsor BOOLEAN DEFAULT FALSE,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_status (status),
                INDEX idx_email (email),
                INDEX idx_industry (industry),
                INDEX idx_location_city (location_city),
                INDEX idx_partner (is_partner, is_top_partner, is_sponsor)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);

            // Company-Expert relationship (Many-to-Many)
            $sql = "CREATE TABLE IF NOT EXISTS {$prefix}company_experts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id INT UNSIGNED NOT NULL,
                expert_id INT UNSIGNED NOT NULL,
                role VARCHAR(150) DEFAULT NULL,
                start_date DATE DEFAULT NULL,
                end_date DATE DEFAULT NULL,
                is_current BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES {$prefix}companies(id) ON DELETE CASCADE,
                INDEX idx_company (company_id),
                INDEX idx_expert (expert_id),
                INDEX idx_current (is_current),
                UNIQUE KEY unique_company_expert (company_id, expert_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);

            // Company meta table
            $sql = "CREATE TABLE IF NOT EXISTS {$prefix}company_meta (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id INT UNSIGNED NOT NULL,
                meta_key VARCHAR(255) NOT NULL,
                meta_value LONGTEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES {$prefix}companies(id) ON DELETE CASCADE,
                INDEX idx_company (company_id),
                INDEX idx_meta_key (meta_key),
                INDEX idx_company_key (company_id, meta_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);

            // Plugin Settings table
            $sql = "CREATE TABLE IF NOT EXISTS {$prefix}company_plugin_settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(255) NOT NULL UNIQUE,
                setting_value LONGTEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $pdo->exec($sql);

            // Industries / Branchen
            $sql = "CREATE TABLE IF NOT EXISTS {$prefix}company_industries (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                slug VARCHAR(150) NOT NULL,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $pdo->exec($sql);

            // Company Tag Presets / Merkmale
            $sql = "CREATE TABLE IF NOT EXISTS {$prefix}company_tag_presets (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tag_name VARCHAR(150) NOT NULL,
                tag_type VARCHAR(50) NOT NULL DEFAULT 'general',
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $pdo->exec($sql);

        } catch (\PDOException $e) {
            error_log('CMS Companies Database Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Holt eine Firma nach ID
     */
    public function get_company(int $id): ?object
    {
        $db = CMS\Database::instance();
        $stmt = $db->prepare("SELECT * FROM {$db->prefix()}companies WHERE id = ?");
        $stmt->execute([$id]);
        
        $company = $stmt->fetch();
        return $company ?: null;
    }

    /**
     * Gibt die Gesamtzahl der Firmen zurück (ohne LIMIT/OFFSET)
     */
    public function get_companies_count(array $args = []): int
    {
        $db     = CMS\Database::instance();
        $where  = [];
        $params = [];

        if (isset($args['status'])) {
            if ($args['status'] === 'any') {
                $where[] = "status != 'deleted'";
            } else {
                $where[]  = 'status = ?';
                $params[] = $args['status'];
            }
        } else {
            $where[]  = 'status = ?';
            $params[] = 'active';
        }
        if (isset($args['industry'])) {
            $where[]  = 'industry = ?';
            $params[] = $args['industry'];
        }
        if (isset($args['city'])) {
            $where[]  = 'location_city = ?';
            $params[] = $args['city'];
        }
        if (isset($args['is_partner'])) {
            $where[]  = 'is_partner = ?';
            $params[] = $args['is_partner'] ? 1 : 0;
        }
        if (!empty($args['partner'])) {
            match($args['partner']) {
                'sponsor'     => ($where[] = 'is_sponsor = 1'),
                'top_partner' => ($where[] = 'is_top_partner = 1'),
                'partner'     => ($where[] = 'is_partner = 1'),
                default       => null,
            };
        }
        if (!empty($args['q'])) {
            $where[]  = '(name LIKE ? OR description LIKE ? OR location_city LIKE ?)';
            $like     = '%' . $args['q'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($args['user_id'])) {
            $where[]  = 'user_id = ?';
            $params[] = (int) $args['user_id'];
        }

        $where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$db->prefix()}companies {$where_clause}");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Holt alle Firmen mit Filter
     */
    public function get_companies(array $args = []): array
    {
        $db = CMS\Database::instance();
        $where = [];
        $params = [];

        // Status Filter
        if (isset($args['status'])) {
            if ($args['status'] === 'any') {
                // Alle außer gelöscht (für Admin-Ansicht)
                $where[] = "status != 'deleted'";
            } else {
                $where[] = 'status = ?';
                $params[] = $args['status'];
            }
        } else {
            $where[] = 'status = ?';
            $params[] = 'active';
        }

        // Industry Filter
        if (isset($args['industry'])) {
            $where[] = 'industry = ?';
            $params[] = $args['industry'];
        }

        // Location Filter
        if (isset($args['city'])) {
            $where[] = 'location_city = ?';
            $params[] = $args['city'];
        }

        // Partner Filter
        if (isset($args['is_partner'])) {
            $where[] = 'is_partner = ?';
            $params[] = $args['is_partner'] ? 1 : 0;
        }
        if (!empty($args['partner'])) {
            match($args['partner']) {
                'sponsor'     => ($where[] = 'is_sponsor = 1'),
                'top_partner' => ($where[] = 'is_top_partner = 1'),
                'partner'     => ($where[] = 'is_partner = 1'),
                default       => null,
            };
        }

        if (!empty($args['q'])) {
            $where[]  = '(name LIKE ? OR description LIKE ? OR location_city LIKE ?)';
            $like     = '%' . $args['q'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($args['user_id'])) {
            $where[]  = 'user_id = ?';
            $params[] = (int) $args['user_id'];
        }

        $where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $limit = isset($args['limit']) ? max(1, min(200, (int) $args['limit'])) : 50;
        $offset = isset($args['offset']) ? max(0, (int) $args['offset']) : 0;

        $sql = "SELECT * FROM {$db->prefix()}companies 
                {$where_clause} 
                ORDER BY is_sponsor DESC, is_top_partner DESC, is_partner DESC, name ASC 
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Speichert eine Firma
     */
    public function save_company(array $data): int
    {
        $db = CMS\Database::instance();
        $company_id = (int)($data['id'] ?? 0);

        if ($company_id > 0 && !CMS\Auth::instance()->isAdmin()) {
            $current_user_id = (int) (CMS\Auth::instance()->currentUser()?->id ?? 0);
            if ($current_user_id <= 0) {
                return 0;
            }

            $owner_stmt = $db->prepare("SELECT user_id FROM {$db->prefix()}companies WHERE id = ? LIMIT 1");
            $owner_stmt->execute([$company_id]);
            $owner_id = (int) ($owner_stmt->fetchColumn() ?: 0);

            if ($owner_id <= 0 || $owner_id !== $current_user_id) {
                return 0;
            }
        }
        
        if ($company_id > 0) {
            return $this->update_company($company_id, $data);
        } else {
            return $this->insert_company($data);
        }
    }

    /**
     * Fügt eine neue Firma hinzu
     */
    private function insert_company(array $data): int
    {
        $db = CMS\Database::instance();
        
        $result = $db->insert('companies', [
            'user_id' => $data['user_id'] ?? null,
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? null,
            'industry' => $data['industry'] ?? null,
            'company_size' => $data['company_size'] ?? null,
            'description' => $data['description'] ?? null,
            'logo_url' => $data['logo_url'] ?? null,
            'website' => $data['website'] ?? null,
            'location_city' => $data['location_city'] ?? null,
            'location_zip' => $data['location_zip'] ?? null,
            'location_country' => $data['location_country'] ?? null,
            'founded_year' => $data['founded_year'] ?? null,
            'employee_count' => $data['employee_count'] ?? null,
            'is_partner' => isset($data['is_partner']) ? ($data['is_partner'] ? 1 : 0) : 0,
            'is_top_partner' => isset($data['is_top_partner']) ? ($data['is_top_partner'] ? 1 : 0) : 0,
            'is_sponsor' => isset($data['is_sponsor']) ? ($data['is_sponsor'] ? 1 : 0) : 0,
            'status' => $data['status'] ?? 'active',
        ]);

        if ($result) {
            $company_id = (int) $db->getPdo()->lastInsertId();
            
            // Fire Hook
            CMS\Hooks::doAction('company_created', $company_id);
            
            return $company_id;
        }
        
        return 0;
    }

    /**
     * Aktualisiert eine Firma
     */
    private function update_company(int $id, array $data): int
    {
        $db = CMS\Database::instance();
        
        $update_data = [];
        $allowed_fields = [
            'name', 'email', 'phone', 'industry', 'company_size',
            'description', 'logo_url', 'website',
            'location_city', 'location_zip', 'location_country',
            'founded_year', 'employee_count',
            'is_partner', 'is_top_partner', 'is_sponsor', 'status'
        ];

        foreach ($allowed_fields as $field) {
            if (array_key_exists($field, $data)) {
                $update_data[$field] = $data[$field];
            }
        }

        if (empty($update_data)) {
            return $id;
        }

        $result = $db->update('companies', $update_data, ['id' => $id]);

        if ($result === false) {
            error_log('CMS_Companies update_company failed for id=' . $id . ': ' . $db->last_error);
            return 0;
        }

        return $id;
    }

    /**
     * Verknüpft Experten mit Firma
     */
    public function assign_expert(int $company_id, int $expert_id, ?string $role = null, bool $is_current = true): bool
    {
        $db = CMS\Database::instance();
        
        // Prüfe ob Verknüpfung existiert
        $stmt = $db->prepare("SELECT id FROM {$db->prefix()}company_experts WHERE company_id = ? AND expert_id = ?");
        $stmt->execute([$company_id, $expert_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update
            $result = $db->update('company_experts', 
                [
                    'role' => $role,
                    'is_current' => $is_current ? 1 : 0
                ],
                ['id' => $existing->id]
            );
        } else {
            // Insert
            $result = $db->insert('company_experts', [
                'company_id' => $company_id,
                'expert_id' => $expert_id,
                'role' => $role,
                'is_current' => $is_current ? 1 : 0,
            ]);
        }

        if ($result !== false) {
            CMS\Hooks::doAction('company_expert_assigned', $company_id, $expert_id);
            return true;
        }

        return false;
    }

    /**
     * Holt alle Experten einer Firma
     */
    public function get_company_experts(int $company_id, bool $current_only = true): array
    {
        $db = CMS\Database::instance();
        
        $where = "ce.company_id = ?";
        $params = [$company_id];

        if ($current_only) {
            $where .= " AND ce.is_current = 1";
        }

        $sql = "SELECT e.*, ce.role, ce.start_date, ce.end_date, ce.is_current 
                FROM {$db->prefix()}experts e
                INNER JOIN {$db->prefix()}company_experts ce ON e.id = ce.expert_id
                WHERE {$where}
                ORDER BY ce.is_current DESC, ce.start_date DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    /**
     * Gibt alle aktiven Experten zurück (für die Admin-Zuweisung).
     */
    public function get_available_experts(): array
    {
        $db = CMS\Database::instance();
        try {
            $stmt = $db->prepare(
                "SELECT id, first_name, last_name, position, status
                 FROM {$db->prefix()}experts
                 WHERE status = 'active'
                 ORDER BY last_name, first_name"
            );
            $stmt->execute([]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('CMS_Companies get_available_experts: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Speichert Company Meta
     */
    public function save_meta(int $company_id, string $key, $value): bool
    {
        $db = CMS\Database::instance();
        
        // Prüfe ob Meta existiert
        $stmt = $db->prepare("SELECT id FROM {$db->prefix()}company_meta WHERE company_id = ? AND meta_key = ?");
        $stmt->execute([$company_id, $key]);
        $existing = $stmt->fetch();

        if ($existing) {
            return $db->update('company_meta', 
                ['meta_value' => is_array($value) ? json_encode($value) : $value],
                ['id' => $existing->id]
            ) !== false;
        } else {
            return $db->insert('company_meta', [
                'company_id' => $company_id,
                'meta_key' => $key,
                'meta_value' => is_array($value) ? json_encode($value) : $value,
            ]) !== false;
        }
    }

    /**
     * Holt Company Meta
     */
    public function get_meta(int $company_id, string $key, $default = null)
    {
        $db = CMS\Database::instance();
        
        $stmt = $db->prepare("SELECT meta_value FROM {$db->prefix()}company_meta WHERE company_id = ? AND meta_key = ?");
        $stmt->execute([$company_id, $key]);
        $result = $stmt->fetchColumn();

        if ($result === false) {
            return $default;
        }

        $decoded = json_decode($result, true);
        return $decoded !== null ? $decoded : $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Settings
    // ─────────────────────────────────────────────────────────────────────────

    public function get_settings(): array
    {
        $db = CMS\Database::instance();
        try {
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM {$db->prefix()}company_plugin_settings");
            $stmt->execute([]);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $out[$row->setting_key] = $row->setting_value;
        }
        return $out;
    }

    public function get_setting(string $key, $default = null)
    {
        $all = $this->get_settings();
        return $all[$key] ?? $default;
    }

    public function save_setting(string $key, $value): void
    {
        $db = CMS\Database::instance();
        try {
            $stmt = $db->prepare(
                "INSERT INTO {$db->prefix()}company_plugin_settings (setting_key, setting_value)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            );
            $stmt->execute([$key, $value]);
        } catch (\Throwable $e) {
            // Tabelle fehlt → einmalig anlegen und erneut versuchen
            try {
                $this->create_tables();
                $stmt = $db->prepare(
                    "INSERT INTO {$db->prefix()}company_plugin_settings (setting_key, setting_value)
                     VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
                );
                $stmt->execute([$key, $value]);
            } catch (\Throwable $e2) {
                error_log('CMS Companies save_setting failed: ' . $e2->getMessage());
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Branchen / Industries
    // ─────────────────────────────────────────────────────────────────────────

    /** Gibt alle Branchen aus DB zurück; fällt auf eingebaute Liste zurück falls leer */
    public function get_all_industries(): array
    {
        $db = CMS\Database::instance();
        try {
            $stmt = $db->prepare("SELECT * FROM {$db->prefix()}company_industries ORDER BY sort_order ASC, name ASC");
            $stmt->execute([]);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Throwable $e) {
            $rows = [];
        }
        if (!empty($rows)) {
            return $rows;
        }
        // Eingebaute Fallback-Liste als Pseudo-Objekte
        $defaults = [
            'it'           => 'IT & Software',
            'consulting'   => 'Beratung & Consulting',
            'finance'      => 'Finanz & Banking',
            'manufacturing'=> 'Produktion & Fertigung',
            'healthcare'   => 'Gesundheitswesen',
            'retail'       => 'Handel & E-Commerce',
            'logistics'    => 'Logistik & Transport',
            'energy'       => 'Energie & Versorgung',
            'education'    => 'Bildung & Forschung',
            'media'        => 'Medien & Kommunikation',
            'real_estate'  => 'Immobilien',
            'automotive'   => 'Automobil',
            'telecom'      => 'Telekommunikation',
            'other'        => 'Sonstiges',
        ];
        $out = [];
        $i = 1;
        foreach ($defaults as $slug => $name) {
            $obj = new \stdClass();
            $obj->id         = 0;   // 0 = built-in, not deletable
            $obj->name       = $name;
            $obj->slug       = $slug;
            $obj->sort_order = $i++;
            $out[] = $obj;
        }
        return $out;
    }

    public function add_industry(string $name): int
    {
        $db  = CMS\Database::instance();
        $map  = ['ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss','Ä'=>'ae','Ö'=>'oe','Ü'=>'ue'];
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', str_replace(array_keys($map), array_values($map), $name)));
        $slug = trim($slug, '-') ?: 'branche';
        $db->insert('company_industries', [
            'name' => $name,
            'slug' => $slug,
        ]);
        return (int)$db->getPdo()->lastInsertId();
    }

    public function delete_industry(int $id): bool
    {
        $db = CMS\Database::instance();
        return $db->delete('company_industries', ['id' => $id]) !== false;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tag-Vorlagen / Merkmale
    // ─────────────────────────────────────────────────────────────────────────

    public function get_tag_presets(?string $type = null): array
    {
        $db = CMS\Database::instance();
        try {
            if ($type !== null) {
                $stmt = $db->prepare("SELECT * FROM {$db->prefix()}company_tag_presets WHERE tag_type = ? ORDER BY sort_order ASC, tag_name ASC");
                $stmt->execute([$type]);
            } else {
                $stmt = $db->prepare("SELECT * FROM {$db->prefix()}company_tag_presets ORDER BY tag_type ASC, sort_order ASC, tag_name ASC");
                $stmt->execute([]);
            }
            return $stmt->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function add_tag_preset(string $name, string $type): int
    {
        $db = CMS\Database::instance();
        $db->insert('company_tag_presets', [
            'tag_name' => $name,
            'tag_type' => $type,
        ]);
        return (int)$db->getPdo()->lastInsertId();
    }

    public function delete_tag_preset(int $id): bool
    {
        $db = CMS\Database::instance();
        return $db->delete('company_tag_presets', ['id' => $id]) !== false;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Status-Änderung
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Setzt den Status eines Unternehmens.
     *
     * @param int    $id     Company-ID
     * @param string $status Erlaubte Werte: active, inactive, pending, deleted
     */
    public function set_company_status(int $id, string $status): bool
    {
        $allowed = ['active', 'inactive', 'pending', 'deleted'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $db = CMS\Database::instance();
        return $db->update('companies', ['status' => $status], ['id' => $id]) !== false;
    }
}
