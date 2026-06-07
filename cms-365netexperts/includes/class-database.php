<?php
/**
 * Database Manager für CMS Experts
 *
 * @package CMS_Experts
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Experts_Database
{
    private static ?self $instance = null;
    private const MAX_LIST_LIMIT = 200;
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
        // Constructor
    }

    private function normalizeLimit(mixed $limit, int $default = 50): int
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

    private function logError(string $context, \Throwable $error): void
    {
        error_log('CMS Experts Database [' . $context . ']: ' . $error->getMessage());
    }

    /**
     * Erstellt alle Datenbank-Tabellen
     */
    public function create_tables(): void
    {
        try {
            $db = CMS\Database::instance();
            $pdo = $db->getPdo();
            $prefix = $db->prefix();

            // Main experts table
            $sql = "CREATE TABLE IF NOT EXISTS {$prefix}experts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED DEFAULT NULL COMMENT 'Verknüpfung zu CMS User',
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL,
                phone VARCHAR(50) DEFAULT NULL,
                mobile VARCHAR(50) DEFAULT NULL,
                position VARCHAR(255) DEFAULT NULL,
                company VARCHAR(255) DEFAULT NULL,
                biography TEXT DEFAULT NULL,
                photo_url VARCHAR(500) DEFAULT NULL,
                location_city VARCHAR(100) DEFAULT NULL,
                location_zip VARCHAR(20) DEFAULT NULL,
                location_country VARCHAR(100) DEFAULT NULL,
                hourly_rate DECIMAL(10,2) DEFAULT NULL,
                daily_rate DECIMAL(10,2) DEFAULT NULL,
                availability VARCHAR(50) DEFAULT 'available',
                experience_years INT DEFAULT 0,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_status (status),
                INDEX idx_email (email),
                INDEX idx_availability (availability),
                INDEX idx_location_city (location_city)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);

            // Expert skills table (für Taxonomie-Zuordnung)
            $sql = "CREATE TABLE IF NOT EXISTS {$prefix}expert_skills (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                expert_id INT UNSIGNED NOT NULL,
                skill_name VARCHAR(150) NOT NULL,
                skill_level VARCHAR(50) DEFAULT 'intermediate',
                years_experience INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (expert_id) REFERENCES {$prefix}experts(id) ON DELETE CASCADE,
                INDEX idx_expert (expert_id),
                INDEX idx_skill (skill_name),
                INDEX idx_level (skill_level)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);

            // Expert meta table (für flexible Zusatzdaten)
            $sql = "CREATE TABLE IF NOT EXISTS {$prefix}expert_meta (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                expert_id INT UNSIGNED NOT NULL,
                meta_key VARCHAR(255) NOT NULL,
                meta_value LONGTEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (expert_id) REFERENCES {$prefix}experts(id) ON DELETE CASCADE,
                INDEX idx_expert (expert_id),
                INDEX idx_meta_key (meta_key),
                INDEX idx_expert_key (expert_id, meta_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);

            // Expert certifications table
            $sql = "CREATE TABLE IF NOT EXISTS {$prefix}expert_certifications (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                expert_id INT UNSIGNED NOT NULL,
                cert_name VARCHAR(255) NOT NULL,
                cert_issuer VARCHAR(255) DEFAULT NULL,
                cert_date DATE DEFAULT NULL,
                cert_expiry DATE DEFAULT NULL,
                cert_url VARCHAR(500) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (expert_id) REFERENCES {$prefix}experts(id) ON DELETE CASCADE,
                INDEX idx_expert (expert_id),
                INDEX idx_date (cert_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);

            // Expert projects table
            $sql = "CREATE TABLE IF NOT EXISTS {$prefix}expert_projects (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                expert_id INT UNSIGNED NOT NULL,
                project_name VARCHAR(255) NOT NULL,
                project_description TEXT,
                project_role VARCHAR(150) DEFAULT NULL,
                project_start DATE DEFAULT NULL,
                project_end DATE DEFAULT NULL,
                project_url VARCHAR(500) DEFAULT NULL,
                technologies TEXT COMMENT 'JSON array of technologies',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (expert_id) REFERENCES {$prefix}experts(id) ON DELETE CASCADE,
                INDEX idx_expert (expert_id),
                INDEX idx_start (project_start)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);

            // Expert education table
            $sql = "CREATE TABLE IF NOT EXISTS {$prefix}expert_education (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                expert_id INT UNSIGNED NOT NULL,
                degree VARCHAR(255) NOT NULL,
                institution VARCHAR(255) NOT NULL,
                field_of_study VARCHAR(255) DEFAULT NULL,
                start_year INT DEFAULT NULL,
                end_year INT DEFAULT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (expert_id) REFERENCES {$prefix}experts(id) ON DELETE CASCADE,
                INDEX idx_expert (expert_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);

            // Skill-Type Spalte migrieren (für bestehende Installs)
            try {
                $pdo->exec("ALTER TABLE {$prefix}expert_skills ADD COLUMN skill_type VARCHAR(30) NOT NULL DEFAULT 'general' AFTER skill_level");
                $pdo->exec("ALTER TABLE {$prefix}expert_skills ADD INDEX idx_type (skill_type)");
            } catch (\PDOException $e) {
                // Spalte existiert bereits – ignorieren
            }

            $this->seed_default_experts();

        } catch (\PDOException $e) {
            $this->logError('create_tables', $e);
        }
    }

    private function seed_default_experts(): void
    {
        $db = CMS\Database::instance();

        try {
            $countStmt = $db->prepare("SELECT COUNT(*) FROM {$db->prefix()}experts");
            $countStmt->execute([]);
            if ((int) $countStmt->fetchColumn() > 0) {
                return;
            }

            $rows = $this->read_default_experts_rows();
            if ($rows === []) {
                $rows = $this->default_experts();
            }

            $seedIndex = 0;
            foreach ($rows as $row) {
                $seedIndex++;
                $firstName = trim((string) ($row['first_name'] ?? ''));
                $lastName = trim((string) ($row['last_name'] ?? ''));
                if ($firstName === '' || $lastName === '') {
                    continue;
                }

                $email = trim((string) ($row['email'] ?? ''));
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $email = 'expert+' . $seedIndex . '@seed.local';
                }

                $expertId = $this->insert_expert([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'position' => trim((string) ($row['position'] ?? '')) ?: null,
                    'company' => trim((string) ($row['company'] ?? '')) ?: null,
                    'biography' => trim((string) ($row['biography'] ?? '')) ?: null,
                    'location_city' => trim((string) ($row['city'] ?? '')) ?: null,
                    'location_country' => trim((string) ($row['country'] ?? '')) ?: 'Deutschland',
                    'hourly_rate' => is_numeric($row['hourly_rate'] ?? null) ? (float) $row['hourly_rate'] : null,
                    'daily_rate' => is_numeric($row['daily_rate'] ?? null) ? (float) $row['daily_rate'] : null,
                    'availability' => in_array((string) ($row['availability'] ?? 'available'), ['available', 'limited', 'booked'], true)
                        ? (string) $row['availability']
                        : 'available',
                    'experience_years' => max(0, (int) ($row['experience_years'] ?? 0)),
                    'status' => in_array((string) ($row['status'] ?? 'active'), ['active', 'inactive', 'pending', 'deleted'], true)
                        ? (string) $row['status']
                        : 'active',
                ]);

                if ($expertId <= 0) {
                    continue;
                }

                $this->save_expert_skills($expertId, [
                    'general' => $this->csv_list_to_array((string) ($row['skills_general'] ?? '')),
                    'tech' => $this->csv_list_to_array((string) ($row['skills_tech'] ?? '')),
                    'soft' => $this->csv_list_to_array((string) ($row['skills_soft'] ?? '')),
                ]);

                $website = trim((string) ($row['website'] ?? ''));
                if ($website !== '') {
                    $this->save_meta($expertId, 'website', $website);
                }

                $awards = trim((string) ($row['awards'] ?? ''));
                if ($awards !== '') {
                    $this->save_meta($expertId, 'awards', $awards);
                }

                $certifications = trim((string) ($row['certifications'] ?? ''));
                if ($certifications !== '') {
                    $this->save_meta($expertId, 'certifications', $certifications);
                }
            }
        } catch (\Throwable $e) {
            $this->logError('seed_default_experts', $e);
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function read_default_experts_rows(): array
    {
        $candidates = [
            dirname(__DIR__) . '/defaults/experts.csv',
        ];

        $file = '';
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $file = $candidate;
                break;
            }
        }

        if ($file === '') {
            return [];
        }

        $content = @file_get_contents($file);
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\n|\r/', $content) ?: [];
        if ($lines === []) {
            return [];
        }

        $headerLine = trim((string) array_shift($lines));
        if ($headerLine === '') {
            return [];
        }

        $headersRaw = str_getcsv($headerLine, ';') ?: [];
        $headers = array_map(fn(string $value): string => $this->normalize_seed_header($value), $headersRaw);
        if ($headers === []) {
            return [];
        }

        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $values = str_getcsv($line, ';') ?: [];
            if ($values === []) {
                continue;
            }

            $row = [];
            foreach ($headers as $idx => $key) {
                if ($key === '') {
                    continue;
                }

                $row[$key] = trim((string) ($values[$idx] ?? ''));
            }

            if (($row['first_name'] ?? '') === '' || ($row['last_name'] ?? '') === '') {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function normalize_seed_header(string $header): string
    {
        $header = trim($header);
        if ($header === '') {
            return '';
        }

        $header = function_exists('mb_strtolower')
            ? mb_strtolower($header, 'UTF-8')
            : strtolower($header);
        $header = str_replace(['ä', 'ö', 'ü', 'ß', '/'], ['ae', 'oe', 'ue', 'ss', '_'], $header);
        $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? '';

        return trim($header, '_');
    }

    private function csv_list_to_array(string $value): array
    {
        $parts = array_map(
            static fn(string $item): string => trim($item),
            explode(',', $value)
        );

        return array_values(array_filter($parts, static fn(string $item): bool => $item !== ''));
    }

    private function default_experts(): array
    {
        return [
            ['first_name' => 'Adam', 'last_name' => 'Bien', 'company' => 'adam-bien.com', 'position' => 'Java / Cloud Native Consultant', 'biography' => 'Konferenzsprecher mit Fokus auf Java, Cloud Native und moderne Software-Architekturen.', 'website' => 'https://adam-bien.com/', 'city' => '', 'country' => 'Deutschland', 'availability' => 'available', 'experience_years' => 0, 'hourly_rate' => '', 'daily_rate' => '', 'skills_general' => 'Java, Cloud Native, Software Architecture', 'skills_tech' => 'Java, Jakarta EE, Microservices, REST API', 'skills_soft' => 'Kommunikation, Präsentation', 'awards' => 'Java Champion', 'certifications' => '', 'status' => 'active'],
            ['first_name' => 'Heike', 'last_name' => 'Hagemeier', 'company' => 'BMI', 'position' => 'Cybersecurity Research', 'biography' => 'Speakerin aus dem OmniSecure-Kontext mit Schwerpunkt auf Cybersecurity Research und Public-Sector-Sicherheit.', 'website' => 'https://www.omnisecure.berlin/', 'city' => '', 'country' => 'Deutschland', 'availability' => 'available', 'experience_years' => 0, 'hourly_rate' => '', 'daily_rate' => '', 'skills_general' => 'Cybersecurity, Research', 'skills_tech' => 'Security, Digital Identity', 'skills_soft' => 'Analytisches Denken', 'awards' => 'Research Speaker', 'certifications' => '', 'status' => 'active'],
            ['first_name' => 'Hartje', 'last_name' => 'Bruns', 'company' => 'Governikus', 'position' => 'Digital Identity Specialist', 'biography' => 'Experte für digitale Identitäten, eID und vertrauenswürdige Verwaltungsprozesse.', 'website' => 'https://www.governikus.de/', 'city' => '', 'country' => 'Deutschland', 'availability' => 'available', 'experience_years' => 0, 'hourly_rate' => '', 'daily_rate' => '', 'skills_general' => 'Digital Identity, eID, Public Sector IT', 'skills_tech' => 'Security, eID', 'skills_soft' => 'Kommunikation', 'awards' => 'Digital Identity Speaker', 'certifications' => '', 'status' => 'active'],
            ['first_name' => 'Tobias', 'last_name' => 'Fehenberger', 'company' => 'Adva Network Security', 'position' => 'Quantum-Safe Cryptography', 'biography' => 'Spezialist für quantum-safe Kryptographie und sichere Netzwerkinfrastrukturen.', 'website' => 'https://www.omnisecure.berlin/', 'city' => '', 'country' => 'Deutschland', 'availability' => 'available', 'experience_years' => 0, 'hourly_rate' => '', 'daily_rate' => '', 'skills_general' => 'Cryptography, Security', 'skills_tech' => 'Security, Networking, Quantum', 'skills_soft' => 'Analytisches Denken', 'awards' => 'Security Speaker', 'certifications' => '', 'status' => 'active'],
            ['first_name' => 'Stefan', 'last_name' => 'Finkbeiner', 'company' => 'Bosch Sensortec', 'position' => 'Sensor Technology / IoT', 'biography' => 'Speaker aus dem Embedded- und IoT-Umfeld mit Fokus auf Sensorik und vernetzte Systeme.', 'website' => 'https://www.embedded-world.de/', 'city' => '', 'country' => 'Deutschland', 'availability' => 'available', 'experience_years' => 0, 'hourly_rate' => '', 'daily_rate' => '', 'skills_general' => 'IoT, Embedded Systems', 'skills_tech' => 'IoT, Embedded Systems, Sensors', 'skills_soft' => 'Präsentation', 'awards' => 'Embedded Speaker', 'certifications' => '', 'status' => 'active'],
            ['first_name' => 'Matthias', 'last_name' => 'Blatz', 'company' => 'Heidelberg iT Management', 'position' => 'AI / Data Center', 'biography' => 'Verbindet AI- und Rechenzentrums-Themen in Business- und Infrastruktur-Kontexten.', 'website' => 'https://www.ai-conference.de/', 'city' => '', 'country' => 'Deutschland', 'availability' => 'available', 'experience_years' => 0, 'hourly_rate' => '', 'daily_rate' => '', 'skills_general' => 'AI, Data Center', 'skills_tech' => 'AI, Infrastructure, Cloud', 'skills_soft' => 'Beratung', 'awards' => 'AI Conference Speaker', 'certifications' => '', 'status' => 'active'],
            ['first_name' => 'Moritz', 'last_name' => 'Mayer', 'company' => 'A1 Digital', 'position' => 'IT Security Solutions', 'biography' => 'Security-Praktiker mit Fokus auf IT-Sicherheitslösungen und Enterprise Security.', 'website' => 'https://www.heise.de/secit', 'city' => '', 'country' => 'Deutschland', 'availability' => 'available', 'experience_years' => 0, 'hourly_rate' => '', 'daily_rate' => '', 'skills_general' => 'Cybersecurity, IT Security', 'skills_tech' => 'Security, Cloud', 'skills_soft' => 'Kommunikation', 'awards' => 'secIT Speaker', 'certifications' => '', 'status' => 'active'],
            ['first_name' => 'Detlef', 'last_name' => 'Bäumer', 'company' => 'PICTURE GmbH', 'position' => 'Prozessmanagement / Change', 'biography' => 'Experte für digitale Verwaltung, Prozessmanagement und organisatorische Transformation.', 'website' => 'https://kommdigitale.de/', 'city' => '', 'country' => 'Deutschland', 'availability' => 'available', 'experience_years' => 0, 'hourly_rate' => '', 'daily_rate' => '', 'skills_general' => 'Digital Transformation, Change', 'skills_tech' => 'Process Management, eGovernment', 'skills_soft' => 'Change Management', 'awards' => 'Public Sector Speaker', 'certifications' => '', 'status' => 'active'],
        ];
    }

    /**
     * Holt einen Experten nach ID
     */
    public function get_expert(int $id): ?object
    {
        $db = CMS\Database::instance();
        $stmt = $db->prepare("SELECT * FROM {$db->prefix()}experts WHERE id = ?");
        $stmt->execute([$id]);
        
        $expert = $stmt->fetch();
        return $expert ?: null;
    }

    /**
     * Holt alle Experten mit Filter
     */
    public function get_experts(array $args = []): array
    {
        $db = CMS\Database::instance();
        $where = [];
        $params = [];

        // Status Filter
        if (isset($args['status'])) {
            $where[] = 'status = ?';
            $params[] = $args['status'];
        } else {
            $where[] = 'status = ?';
            $params[] = 'active';
        }

        // Availability Filter
        if (isset($args['availability'])) {
            $where[] = 'availability = ?';
            $params[] = $args['availability'];
        }

        // Location Filter
        if (isset($args['city'])) {
            $where[] = 'location_city = ?';
            $params[] = $args['city'];
        }

        if (!empty($args['search'])) {
            $where[] = '(first_name LIKE ? OR last_name LIKE ? OR position LIKE ? OR company LIKE ? OR location_city LIKE ?)';
            $term = '%' . mb_substr(trim((string) $args['search']), 0, 120) . '%';
            array_push($params, $term, $term, $term, $term, $term);
        }

        $where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $limit = $this->normalizeLimit($args['limit'] ?? 50, 50);
        $offset = $this->normalizeOffset($args['offset'] ?? 0);

        $sql = "SELECT * FROM {$db->prefix()}experts 
                {$where_clause} 
                ORDER BY created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    /**
     * Speichert einen Experten
     */
    public function save_expert(array $data): int
    {
        $db = CMS\Database::instance();
        $expert_id = (int)($data['id'] ?? 0);

        if ($expert_id > 0 && !CMS\Auth::instance()->isAdmin()) {
            $current_user_id = (int) (CMS\Auth::instance()->currentUser()?->id ?? 0);
            if ($current_user_id <= 0) {
                return 0;
            }

            $owner_stmt = $db->prepare("SELECT user_id FROM {$db->prefix()}experts WHERE id = ? LIMIT 1");
            $owner_stmt->execute([$expert_id]);
            $owner_id = (int) ($owner_stmt->fetchColumn() ?: 0);

            if ($owner_id <= 0 || $owner_id !== $current_user_id) {
                return 0;
            }
        }
        
        if ($expert_id > 0) {
            // Update
            return $this->update_expert($expert_id, $data);
        } else {
            // Insert
            return $this->insert_expert($data);
        }
    }

    /**
     * Fügt einen neuen Experten hinzu
     */
    private function insert_expert(array $data): int
    {
        $db = CMS\Database::instance();
        
        $result = $db->insert('experts', [
            'user_id' => $data['user_id'] ?? null,
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'position' => $data['position'] ?? null,
            'company' => $data['company'] ?? null,
            'biography' => $data['biography'] ?? null,
            'photo_url' => $data['photo_url'] ?? null,
            'location_city' => $data['location_city'] ?? null,
            'location_zip' => $data['location_zip'] ?? null,
            'location_country' => $data['location_country'] ?? null,
            'hourly_rate' => $data['hourly_rate'] ?? null,
            'daily_rate' => $data['daily_rate'] ?? null,
            'availability' => $data['availability'] ?? 'available',
            'experience_years' => $data['experience_years'] ?? 0,
            'status' => $data['status'] ?? 'active',
        ]);

        if ($result) {
            $expert_id = (int) $db->getPdo()->lastInsertId();
            
            // Fire Hook
            CMS\Hooks::doAction('expert_registered', $expert_id);
            
            return $expert_id;
        }
        
        return 0;
    }

    /**
     * Aktualisiert einen Experten
     */
    private function update_expert(int $id, array $data): int
    {
        $db = CMS\Database::instance();
        
        $update_data = [];
        $allowed_fields = [
            'first_name', 'last_name', 'email', 'phone', 'mobile',
            'position', 'company', 'biography', 'photo_url',
            'location_city', 'location_zip', 'location_country',
            'hourly_rate', 'daily_rate', 'availability', 
            'experience_years', 'status'
        ];

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }

        if (empty($update_data)) {
            return $id;
        }

        $db->update('experts', $update_data, ['id' => $id]);
        
        // Fire Hook
        CMS\Hooks::doAction('expert_updated', $id);
        
        return $id;
    }

    /**
     * Löscht einen Experten
     */
    public function delete_expert(int $id): bool
    {
        $db = CMS\Database::instance();
        
        // Soft Delete - setze status auf 'deleted'
        $result = $db->update('experts', 
            ['status' => 'deleted'], 
            ['id' => $id]
        );

        if ($result) {
            // Fire Hook
            CMS\Hooks::doAction('expert_deleted', $id);
            return true;
        }

        return false;
    }

    /**
     * Speichert Expert Meta
     */
    public function save_meta(int $expert_id, string $key, $value): bool
    {
        $db = CMS\Database::instance();
        
        // Prüfe ob Meta existiert
        $stmt = $db->prepare("SELECT id FROM {$db->prefix()}expert_meta WHERE expert_id = ? AND meta_key = ?");
        $stmt->execute([$expert_id, $key]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update
            return $db->update('expert_meta', 
                ['meta_value' => is_array($value) ? json_encode($value) : $value],
                ['id' => $existing->id]
            ) !== false;
        } else {
            // Insert
            return $db->insert('expert_meta', [
                'expert_id' => $expert_id,
                'meta_key' => $key,
                'meta_value' => is_array($value) ? json_encode($value) : $value,
            ]) !== false;
        }
    }

    /**
     * Holt Expert Meta
     */
    public function get_meta(int $expert_id, string $key, $default = null)
    {
        $db = CMS\Database::instance();
        
        $stmt = $db->prepare("SELECT meta_value FROM {$db->prefix()}expert_meta WHERE expert_id = ? AND meta_key = ?");
        $stmt->execute([$expert_id, $key]);
        $result = $stmt->fetchColumn();

        if ($result === false) {
            return $default;
        }

        // Try to decode JSON
        $decoded = json_decode($result, true);
        return $decoded !== null ? $decoded : $result;
    }

    /**
     * Holt alle Meta-Felder eines Experten als Key-Value-Array
     */
    public function get_all_meta(int $expert_id): array
    {
        $db  = CMS\Database::instance();
        $rows = $db->get_results(
            "SELECT meta_key, meta_value FROM {$db->prefix()}expert_meta WHERE expert_id = ?",
            [$expert_id]
        );
        $meta = [];
        foreach ($rows as $row) {
            $meta[$row->meta_key] = $row->meta_value;
        }
        return $meta;
    }

    /**
     * Speichert Skills für einen Experten (ersetzt alle bestehenden des Typs)
     *
     * @param int    $expert_id
     * @param array  $skills_by_type  ['general'=>['PHP','Docker'], 'tech'=>['JS'], 'soft'=>['Teamwork']]
     */
    public function save_expert_skills(int $expert_id, array $skills_by_type): void
    {
        $db     = CMS\Database::instance();
        $prefix = $db->prefix();

        // Alle alten Skills löschen
        $db->execute("DELETE FROM {$prefix}expert_skills WHERE expert_id = ?", [$expert_id]);

        foreach ($skills_by_type as $type => $names) {
            $type = in_array($type, ['general', 'tech', 'soft'], true) ? $type : 'general';
            foreach ($names as $skill_name) {
                $skill_name = mb_substr(trim(strip_tags((string) $skill_name)), 0, 120);
                if ($skill_name === '') {
                    continue;
                }
                $db->insert('expert_skills', [
                    'expert_id'        => $expert_id,
                    'skill_name'       => $skill_name,
                    'skill_level'      => 'intermediate',
                    'skill_type'       => $type,
                    'years_experience' => 0,
                ]);
            }
        }
    }

    /**
     * Holt alle Skills eines Experten, gruppiert nach Typ
     *
     * @return array ['general'=>['PHP','Docker'], 'tech'=>['...'], 'soft'=>['...']]
     */
    public function get_expert_skills_grouped(int $expert_id): array
    {
        $db      = CMS\Database::instance();
        $grouped = ['general' => [], 'tech' => [], 'soft' => []];
        try {
            $rows = $db->get_results(
                "SELECT skill_name, skill_type FROM {$db->prefix()}expert_skills WHERE expert_id = ? ORDER BY skill_name ASC",
                [$expert_id]
            );
            foreach ($rows as $row) {
                $type = in_array($row->skill_type, ['general', 'tech', 'soft'], true) ? $row->skill_type : 'general';
                $grouped[$type][] = $row->skill_name;
            }
        } catch (\Throwable $e) {
            // skill_type-Spalte fehlt noch (Migration ausstehend) – fallback ohne Typ-Trennung
            try {
                $rows = $db->get_results(
                    "SELECT skill_name FROM {$db->prefix()}expert_skills WHERE expert_id = ? ORDER BY skill_name ASC",
                    [$expert_id]
                );
                foreach ($rows as $row) {
                    $grouped['general'][] = $row->skill_name;
                }
                // Migration direkt nachholen
                $this->maybe_migrate_skill_type();
            } catch (\Throwable $inner) {
                $this->logError('get_expert_skills_grouped_fallback', $inner);
            }
        }
        return $grouped;
    }

    /**
     * Nachträgliche Migration: Spalte skill_type hinzufügen falls fehlend
     */
    public function maybe_migrate_skill_type(): void
    {
        $db     = CMS\Database::instance();
        $pdo    = $db->getPdo();
        $prefix = $db->prefix();
        try {
            $pdo->exec("ALTER TABLE {$prefix}expert_skills ADD COLUMN skill_type VARCHAR(30) NOT NULL DEFAULT 'general' AFTER skill_level");
            $pdo->exec("ALTER TABLE {$prefix}expert_skills ADD INDEX idx_type (skill_type)");
        } catch (\PDOException $e) {
            // Spalte existiert bereits oder kein Fehler – ignorieren
        }
    }

    /**
     * Holt alle Experten (inkl. pending/inactive), optional nach Status gefiltert.
     * Status 'all' oder kein Status → alle außer 'deleted'.
     */
    public function get_experts_all(array $args = []): array
    {
        $db     = CMS\Database::instance();
        $where  = ["status != 'deleted'"];
        $params = [];

        if (isset($args['status']) && $args['status'] !== 'all' && $args['status'] !== '') {
            $where[] = 'status = ?';
            $params[] = $args['status'];
        }

        if (!empty($args['user_id'])) {
            $where[] = 'user_id = ?';
            $params[] = (int) $args['user_id'];
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);
        $limit  = $this->normalizeLimit($args['limit'] ?? 200, 200);
        $offset = $this->normalizeOffset($args['offset'] ?? 0);

        // Pending-Profile zuerst
        $sql = "SELECT * FROM {$db->prefix()}experts
                {$where_clause}
                ORDER BY FIELD(status, 'pending', 'inactive', 'active'), created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Setzt den Status eines Experten direkt (z.B. pending → active)
     */
    public function set_expert_status(int $id, string $status): bool
    {
        $allowed = ['active', 'inactive', 'pending', 'deleted'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $db = CMS\Database::instance();
        return $db->update('experts', ['status' => $status], ['id' => $id]) !== false;
    }

    // ═══ Plugin-Einstellungen ════════════════════════════════════════════════

    private function maybe_create_settings_table(): void
    {
        $db     = CMS\Database::instance();
        $pdo    = $db->getPdo();
        $prefix = $db->prefix();
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}expert_plugin_settings (
                setting_key   VARCHAR(100) NOT NULL PRIMARY KEY,
                setting_value TEXT,
                updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\PDOException $e) {
            $this->logError('maybe_create_settings_table', $e);
        }
    }

    public function get_plugin_setting(string $key, $default = null)
    {
        $this->maybe_create_settings_table();
        if ($this->settingsCache !== null && array_key_exists($key, $this->settingsCache)) {
            return $this->settingsCache[$key];
        }
        $db = CMS\Database::instance();
        try {
            $row = $db->get_row(
                "SELECT setting_value FROM {$db->prefix()}expert_plugin_settings WHERE setting_key = ?",
                [$key]
            );
            $value = $row ? $row->setting_value : $default;
            if ($this->settingsCache !== null && $row) {
                $this->settingsCache[$key] = $row->setting_value;
            }
            return $value;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public function save_plugin_settings(array $settings): void
    {
        $this->maybe_create_settings_table();
        $this->settingsCache = null;
        $db     = CMS\Database::instance();
        $pdo    = $db->getPdo();
        $prefix = $db->prefix();
        $stmt   = $pdo->prepare(
            "INSERT INTO {$prefix}expert_plugin_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        foreach ($settings as $key => $value) {
            try {
                $stmt->execute([(string)$key, (string)$value]);
            } catch (\PDOException $e) {
                $this->logError('save_plugin_settings:' . (string) $key, $e);
            }
        }
    }

    public function get_all_plugin_settings(): array
    {
        if ($this->settingsCache !== null) {
            return $this->settingsCache;
        }

        $this->maybe_create_settings_table();
        $db   = CMS\Database::instance();
        $out  = [];
        try {
            $rows = $db->get_results(
                "SELECT setting_key, setting_value FROM {$db->prefix()}expert_plugin_settings"
            );
            foreach ($rows as $r) {
                $out[$r->setting_key] = $r->setting_value;
            }
        } catch (\Throwable $e) { /* silent */ }

        $this->settingsCache = $out;
        return $out;
    }

    /**
     * Speichert Spezialisierungen (Fachrichtungen) für einen Experten
     *
     * @param int   $expert_id
     * @param int[] $spec_ids   Array von Specialization-IDs
     */
    public function save_expert_specializations(int $expert_id, array $spec_ids): void
    {
        $db     = CMS\Database::instance();
        $prefix = $db->prefix();

        // Alte Zuordnungen löschen
        $db->execute("DELETE FROM {$prefix}expert_specialization_rel WHERE expert_id = ?", [$expert_id]);

        foreach (array_values($spec_ids) as $i => $spec_id) {
            $spec_id = (int) $spec_id;
            if ($spec_id <= 0) {
                continue;
            }
            try {
                $db->execute(
                    "INSERT IGNORE INTO {$prefix}expert_specialization_rel (expert_id, specialization_id, is_primary) VALUES (?, ?, ?)",
                    [$expert_id, $spec_id, $i === 0 ? 1 : 0]
                );
            } catch (\PDOException $e) {
                $this->logError('save_expert_specializations', $e);
            }
        }
    }

    /**
     * Holt die IDs der Spezialisierungen eines Experten
     *
     * @return int[]
     */
    public function get_expert_specialization_ids(int $expert_id): array
    {
        $db = CMS\Database::instance();
        return array_map('intval', $db->get_col(
            "SELECT specialization_id FROM {$db->prefix()}expert_specialization_rel WHERE expert_id = ? ORDER BY is_primary DESC",
            [$expert_id]
        ));
    }

    // ═══ Zertifikate ════════════════════════════════════════════════════════════

    /**
     * Speichert Zertifikate (ersetzt alle bestehenden des Experten)
     * $items = [['name'=>…,'issuer'=>…,'date'=>…,'expiry'=>…,'url'=>…], …]
     */
    public function save_expert_certifications(int $expert_id, array $items): void
    {
        $db     = CMS\Database::instance();
        $prefix = $db->prefix();
        $db->execute("DELETE FROM {$prefix}expert_certifications WHERE expert_id = ?", [$expert_id]);
        foreach ($items as $item) {
            $name = mb_substr(trim(strip_tags((string) ($item['cert_name'] ?? ''))), 0, 255);
            if ($name === '') continue;
            $db->insert('expert_certifications', [
                'expert_id'   => $expert_id,
                'cert_name'   => $name,
                'cert_issuer' => mb_substr(trim(strip_tags((string) ($item['cert_issuer'] ?? ''))), 0, 255) ?: null,
                'cert_date'   => $this->normalizeDate($item['cert_date'] ?? null),
                'cert_expiry' => $this->normalizeDate($item['cert_expiry'] ?? null),
                'cert_url'    => $this->normalizePublicUrl($item['cert_url'] ?? null),
            ]);
        }
    }

    /**
     * Einzelnes Zertifikat löschen
     */
    public function delete_certification(int $id, int $expert_id): bool
    {
        $db = CMS\Database::instance();
        return $db->execute(
            "DELETE FROM {$db->prefix()}expert_certifications WHERE id = ? AND expert_id = ?",
            [$id, $expert_id]
        ) !== false;
    }

    // ═══ Projekte ════════════════════════════════════════════════════════════════

    /**
     * Speichert Projekte (ersetzt alle bestehenden des Experten)
     */
    public function save_expert_projects(int $expert_id, array $items): void
    {
        $db     = CMS\Database::instance();
        $prefix = $db->prefix();
        $db->execute("DELETE FROM {$prefix}expert_projects WHERE expert_id = ?", [$expert_id]);
        foreach ($items as $item) {
            $name = mb_substr(trim(strip_tags((string) ($item['project_name'] ?? ''))), 0, 255);
            if ($name === '') continue;
            $db->insert('expert_projects', [
                'expert_id'           => $expert_id,
                'project_name'        => $name,
                'project_description' => mb_substr(trim(strip_tags((string) ($item['project_description'] ?? ''))), 0, 2000) ?: null,
                'project_role'        => mb_substr(trim(strip_tags((string) ($item['project_role'] ?? ''))), 0, 150) ?: null,
                'project_start'       => $this->normalizeDate($item['project_start'] ?? null),
                'project_end'         => $this->normalizeDate($item['project_end'] ?? null),
                'project_url'         => $this->normalizePublicUrl($item['project_url'] ?? null),
                'technologies'        => !empty($item['technologies'])
                    ? (is_array($item['technologies']) ? json_encode($item['technologies']) : $item['technologies'])
                    : null,
            ]);
        }
    }

    /**
     * Einzelnes Projekt löschen
     */
    public function delete_project(int $id, int $expert_id): bool
    {
        $db = CMS\Database::instance();
        return $db->execute(
            "DELETE FROM {$db->prefix()}expert_projects WHERE id = ? AND expert_id = ?",
            [$id, $expert_id]
        ) !== false;
    }

    // ═══ Ausbildung ══════════════════════════════════════════════════════════════

    /**
     * Speichert Ausbildungseinträge (ersetzt alle bestehenden des Experten)
     */
    public function save_expert_education(int $expert_id, array $items): void
    {
        $db     = CMS\Database::instance();
        $prefix = $db->prefix();
        $db->execute("DELETE FROM {$prefix}expert_education WHERE expert_id = ?", [$expert_id]);
        foreach ($items as $item) {
            $degree = trim($item['degree'] ?? '');
            $inst   = trim($item['institution'] ?? '');
            if ($degree === '' && $inst === '') continue;
            $db->insert('expert_education', [
                'expert_id'      => $expert_id,
                'degree'         => $degree ?: '—',
                'institution'    => $inst   ?: '—',
                'field_of_study' => trim($item['field_of_study'] ?? '') ?: null,
                'start_year'     => !empty($item['start_year']) ? (int)$item['start_year'] : null,
                'end_year'       => !empty($item['end_year'])   ? (int)$item['end_year']   : null,
                'description'    => trim($item['description'] ?? '') ?: null,
            ]);
        }
    }

    /**
     * Einzelnen Ausbildungseintrag löschen
     */
    public function delete_education(int $id, int $expert_id): bool
    {
        $db = CMS\Database::instance();
        return $db->execute(
            "DELETE FROM {$db->prefix()}expert_education WHERE id = ? AND expert_id = ?",
            [$id, $expert_id]
        ) !== false;
    }

    /**
     * Zählt alle nicht-gelöschten Experten.
     */
    public function countExperts(string $status = ''): int
    {
        $db     = CMS\Database::instance();
        $where  = ["status != 'deleted'"];
        $params = [];
        if ($status !== '') {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        $row = $db->get_row(
            "SELECT COUNT(*) AS cnt FROM {$db->prefix()}experts WHERE " . implode(' AND ', $where),
            $params
        );
        return $row ? (int)$row->cnt : 0;
    }

    /**
     * Alias für get_experts_all() – camelCase-kompatible Variante für
     * Member-Dashboard-Integration.
     *
     * @param array $args  Unterstützt: limit, offset, status
     */
    public function getExperts(array $args = []): array
    {
        return $this->get_experts_all($args);
    }

    /**
     * Generiert den öffentlichen Slug eines Experten.
     * Format: vorname-nachname-{id}  (z.B. "max-mustermann-42")
     *
     * @param object $expert  Experten-Objekt mit first_name, last_name, id
     * @return string
     */
    public static function generate_slug(object $expert): string
    {
        $normalize = static function (string $s): string {
            $s = mb_strtolower($s, 'UTF-8');
            $s = str_replace(
                ['ä', 'ö', 'ü', 'ß', 'à', 'á', 'â', 'ã', 'å',
                 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï',
                 'ò', 'ó', 'ô', 'õ', 'ø', 'ù', 'ú', 'û',
                 'ý', 'ÿ', 'ñ', 'ç'],
                ['ae','oe','ue','ss','a', 'a', 'a', 'a', 'a',
                 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i',
                 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u',
                 'y', 'y', 'n', 'c'],
                $s
            );
            $s = preg_replace('/[^a-z0-9]+/', '-', $s);
            return trim($s, '-');
        };

        $first = $normalize($expert->first_name ?? '') ?: 'experte';
        $last  = $normalize($expert->last_name  ?? '') ?: 'unbekannt';

        return $first . '-' . $last . '-' . (int)$expert->id;
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));
        return checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
    }

    private function normalizePublicUrl(mixed $value): ?string
    {
        if (function_exists('cms_experts_public_url')) {
            $url = cms_experts_public_url((string) $value);
            return $url !== '' ? $url : null;
        }

        return null;
    }
}
