<?php
/**
 * Datenbank- und Seed-Manager für 365NET Experts & Companie.
 *
 * @package CMS_365NET_ExpertsAndCompanie
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_365NET_Experts_And_Companie_Database
{
    private static ?self $instance = null;

    private const SCHEMA_VERSION = '1.2.0';
    private const TABLE_EXPERTS = '365net_excomp_experts';
    private const TABLE_COMPANIES = '365net_excomp_companies';
    private const TABLE_SETTINGS = '365net_excomp_settings';

    /** @var array<string, array<int, string>> */
    private array $tableColumnsCache = [];

    /** @var array<string, bool> */
    private array $tableExistsCache = [];

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {}

    public function ensureSchema(bool $forceSeed = false): void
    {
        $this->createFallbackTables();
        $this->seedDefaults($forceSeed);
        $this->saveSetting('schema_version', self::SCHEMA_VERSION);
    }

    /** @return array{experts:string,companies:string} */
    public function getSourceTables(): array
    {
        return [
            'experts' => self::TABLE_EXPERTS,
            'companies' => self::TABLE_COMPANIES,
        ];
    }

    /** @return array{experts_seed_count:int,companies_seed_count:int} */
    public function getOverviewStats(): array
    {
        return [
            'experts_seed_count' => $this->countTableRows(self::TABLE_EXPERTS),
            'companies_seed_count' => $this->countTableRows(self::TABLE_COMPANIES),
        ];
    }

    /** @return array<string, string> */
    public function getSettings(): array
    {
        $defaults = [
            'show_nav_link' => '0',
            'nav_label' => 'Experts & Companies',
        ];

        $db = CMS\Database::instance();
        try {
            $stmt = $db->prepare('SELECT setting_key, setting_value FROM ' . $db->prefix() . self::TABLE_SETTINGS);
            $stmt->execute([]);
            foreach ($stmt->fetchAll() ?: [] as $row) {
                if (!is_object($row) || !isset($row->setting_key)) {
                    continue;
                }

                $key = (string) ($row->setting_key ?? '');
                if ($key === '') {
                    continue;
                }

                $defaults[$key] = (string) ($row->setting_value ?? '');
            }
        } catch (Throwable) {
        }

        return $defaults;
    }

    /** @param array<string, mixed> $data */
    public function saveSettings(array $data): void
    {
        $settings = $this->getSettings();
        $allowed = array_keys($settings);

        foreach ($allowed as $key) {
            $raw = (string) ($data[$key] ?? '');

            if ($key === 'show_nav_link') {
                $this->saveSetting($key, !empty($data[$key]) ? '1' : '0');
                continue;
            }

            $this->saveSetting($key, $this->cleanTextarea($raw, 120));
        }
    }

    /** @return array<int, object> */
    public function getExpertsPublic(string $search = '', string $city = '', int $limit = 24, int $offset = 0): array
    {
        return $this->getExperts($search, $city, $limit, $offset, true);
    }

    /** @return array<int, object> */
    public function getExpertsAdmin(string $search = '', int $limit = 250): array
    {
        return $this->getExperts($search, '', $limit, 0, false);
    }

    public function countExpertsPublic(string $search = '', string $city = ''): int
    {
        return $this->countRows('experts', $search, $city, true);
    }

    /** @return array<int, object> */
    public function getCompaniesPublic(string $search = '', string $city = '', int $limit = 24, int $offset = 0): array
    {
        return $this->getCompanies($search, $city, $limit, $offset, true);
    }

    /** @return array<int, object> */
    public function getCompaniesAdmin(string $search = '', int $limit = 250): array
    {
        return $this->getCompanies($search, '', $limit, 0, false);
    }

    public function countCompaniesPublic(string $search = '', string $city = ''): int
    {
        return $this->countRows('companies', $search, $city, true);
    }

    public function getExpertById(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        $table = $this->resolveTable('experts');
        $db = CMS\Database::instance();
        $stmt = $db->prepare('SELECT * FROM ' . $db->prefix() . $table . ' WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function getCompanyById(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        $table = $this->resolveTable('companies');
        $db = CMS\Database::instance();
        $stmt = $db->prepare('SELECT * FROM ' . $db->prefix() . $table . ' WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<int, object> */
    public function getAvailableSpeakers(int $limit = 300): array
    {
        if (!$this->tableExists('365net_event_speakers')) {
            return [];
        }

        $columns = $this->getExistingColumns('365net_event_speakers');
        if ($columns === []) {
            return [];
        }

        $select = $this->buildSelect($columns, [
            'id',
            'display_name',
            'slug',
            'topic',
            'status',
            'linked_expert_id',
            'linked_company_id',
        ]);

        $where = in_array('status', $columns, true) ? ' WHERE status = ?' : '';
        $params = $where !== '' ? ['published'] : [];

        $db = CMS\Database::instance();
        $limit = max(1, min(500, $limit));
        $stmt = $db->prepare('SELECT ' . $select . ' FROM ' . $db->prefix() . '365net_event_speakers' . $where . ' ORDER BY display_name ASC LIMIT ' . $limit);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public function getLinkedSpeaker(?int $speakerId): ?object
    {
        if (!$speakerId || !$this->tableExists('365net_event_speakers')) {
            return null;
        }

        $db = CMS\Database::instance();
        $stmt = $db->prepare('SELECT id, display_name, slug, topic, linked_expert_id, linked_company_id, status FROM ' . $db->prefix() . '365net_event_speakers WHERE id = ? LIMIT 1');
        $stmt->execute([$speakerId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function getSpeakerByLinkedExpert(int $expertId): ?object
    {
        if ($expertId <= 0 || !$this->tableExists('365net_event_speakers')) {
            return null;
        }

        $columns = $this->getExistingColumns('365net_event_speakers');
        if (!in_array('linked_expert_id', $columns, true)) {
            return null;
        }

        $db = CMS\Database::instance();
        $stmt = $db->prepare('SELECT id, display_name, slug, topic, linked_expert_id, linked_company_id, status FROM ' . $db->prefix() . '365net_event_speakers WHERE linked_expert_id = ? LIMIT 1');
        $stmt->execute([$expertId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<int, object> */
    public function getExpertsByLinkedCompany(int $companyId, int $limit = 4): array
    {
        if ($companyId <= 0) {
            return [];
        }

        $table = $this->resolveTable('experts');
        $columns = $this->getExistingColumns($table);
        if (!in_array('linked_company_id', $columns, true)) {
            return [];
        }

        $select = $this->buildSelect($columns, ['id', 'first_name', 'last_name', 'position', 'company', 'status']);
        $whereStatus = in_array('status', $columns, true) ? ' AND status = ?' : '';
        $params = [$companyId];
        if ($whereStatus !== '') {
            $params[] = 'active';
        }

        $db = CMS\Database::instance();
        $limit = max(1, min(20, $limit));
        $stmt = $db->prepare('SELECT ' . $select . ' FROM ' . $db->prefix() . $table . ' WHERE linked_company_id = ?' . $whereStatus . ' ORDER BY updated_at DESC LIMIT ' . $limit);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int, object> */
    public function getSpeakersByLinkedCompany(int $companyId, int $limit = 4): array
    {
        if ($companyId <= 0 || !$this->tableExists('365net_event_speakers')) {
            return [];
        }

        $columns = $this->getExistingColumns('365net_event_speakers');
        if (!in_array('linked_company_id', $columns, true)) {
            return [];
        }

        $whereStatus = in_array('status', $columns, true) ? ' AND status = ?' : '';
        $params = [$companyId];
        if ($whereStatus !== '') {
            $params[] = 'published';
        }

        $db = CMS\Database::instance();
        $limit = max(1, min(20, $limit));
        $stmt = $db->prepare('SELECT id, display_name, slug, topic, linked_expert_id, linked_company_id, status FROM ' . $db->prefix() . '365net_event_speakers WHERE linked_company_id = ?' . $whereStatus . ' ORDER BY display_name ASC LIMIT ' . $limit);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public function saveExpert(array $data): int|false
    {
        $table = $this->resolveTable('experts');
        $columns = $this->getExistingColumns($table);
        if ($columns === []) {
            return false;
        }

        $id = max(0, (int) ($data['id'] ?? 0));
        $existing = $id > 0 ? $this->getExpertById($id) : null;
        $firstName = $this->cleanText((string) ($data['first_name'] ?? ''), 120);
        $lastName = $this->cleanText((string) ($data['last_name'] ?? ''), 120);
        if ($firstName === '' || $lastName === '') {
            return false;
        }

        $linkedCompanyId = $this->cleanPositiveInt($data['linked_company_id'] ?? null);
        if ($linkedCompanyId !== null && $this->getCompanyById($linkedCompanyId) === null) {
            $linkedCompanyId = null;
        }

        $linkedSpeakerId = $this->cleanPositiveInt($data['linked_speaker_id'] ?? null);
        if ($linkedSpeakerId !== null && $this->getLinkedSpeaker($linkedSpeakerId) === null) {
            $linkedSpeakerId = null;
        }

        $payload = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'company' => $this->cleanText((string) ($data['company'] ?? ''), 255),
            'position' => $this->cleanText((string) ($data['position'] ?? ''), 255),
            'biography' => $this->cleanTextarea((string) ($data['biography'] ?? ''), 10000),
            'website' => $this->cleanUrl((string) ($data['website'] ?? '')),
            'city' => $this->cleanText((string) ($data['city'] ?? ''), 120),
            'location_city' => $this->cleanText((string) ($data['city'] ?? ''), 120),
            'country' => $this->cleanText((string) ($data['country'] ?? ''), 120),
            'availability' => $this->cleanText((string) ($data['availability'] ?? ''), 80),
            'experience_years' => $this->cleanInt($data['experience_years'] ?? null),
            'hourly_rate' => $this->cleanDecimal($data['hourly_rate'] ?? null),
            'daily_rate' => $this->cleanDecimal($data['daily_rate'] ?? null),
            'skills_general' => $this->cleanTextarea((string) ($data['skills_general'] ?? ''), 2000),
            'skills_tech' => $this->cleanTextarea((string) ($data['skills_tech'] ?? ''), 2000),
            'skills_soft' => $this->cleanTextarea((string) ($data['skills_soft'] ?? ''), 2000),
            'awards' => $this->cleanTextarea((string) ($data['awards'] ?? ''), 2000),
            'certifications' => $this->cleanTextarea((string) ($data['certifications'] ?? ''), 2000),
            'linked_company_id' => $linkedCompanyId,
            'linked_speaker_id' => $linkedSpeakerId,
            'status' => in_array((string) ($data['status'] ?? 'active'), ['active', 'inactive'], true) ? (string) $data['status'] : 'active',
        ];

        if ($linkedCompanyId !== null && trim((string) $payload['company']) === '') {
            $linkedCompany = $this->getCompanyById($linkedCompanyId);
            if ($linkedCompany !== null && trim((string) ($linkedCompany->name ?? '')) !== '') {
                $payload['company'] = $this->cleanText((string) $linkedCompany->name, 255);
            }
        }

        if ($this->wordCount((string) $payload['biography']) < 250) {
            $payload['biography'] = $this->buildLongExpertBiography([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'company' => (string) $payload['company'],
                'position' => (string) $payload['position'],
                'biography' => (string) $payload['biography'],
                'website' => (string) $payload['website'],
                'skills_general' => (string) $payload['skills_general'],
                'skills_tech' => (string) $payload['skills_tech'],
                'skills_soft' => (string) $payload['skills_soft'],
                'awards' => (string) $payload['awards'],
                'certifications' => (string) $payload['certifications'],
                'city' => (string) $payload['city'],
                'country' => (string) $payload['country'],
            ]);
        }

        $payload = $this->filterPayloadByColumns($payload, $columns);
        if ($payload === []) {
            return false;
        }

        $savedId = $this->saveRecord($table, $payload, $id);
        if ($savedId === false) {
            return false;
        }

        $previousLinkedSpeakerId = $existing !== null ? $this->cleanPositiveInt($existing->linked_speaker_id ?? null) : null;
        $this->syncSpeakerLinkForExpert((int) $savedId, $linkedSpeakerId, $linkedCompanyId, $previousLinkedSpeakerId);

        return $savedId;
    }

    public function saveCompany(array $data): int|false
    {
        $table = $this->resolveTable('companies');
        $columns = $this->getExistingColumns($table);
        if ($columns === []) {
            return false;
        }

        $id = max(0, (int) ($data['id'] ?? 0));
        $name = $this->cleanText((string) ($data['name'] ?? ''), 255);
        if ($name === '') {
            return false;
        }

        $payload = [
            'name' => $name,
            'email' => $this->cleanEmail((string) ($data['email'] ?? '')),
            'phone' => $this->cleanText((string) ($data['phone'] ?? ''), 80),
            'industry' => $this->cleanText((string) ($data['industry'] ?? ''), 255),
            'company_size' => $this->cleanText((string) ($data['company_size'] ?? ''), 120),
            'description' => $this->cleanTextarea((string) ($data['description'] ?? ''), 12000),
            'website' => $this->cleanUrl((string) ($data['website'] ?? '')),
            'city' => $this->cleanText((string) ($data['city'] ?? ''), 120),
            'location_city' => $this->cleanText((string) ($data['city'] ?? ''), 120),
            'zip' => $this->cleanText((string) ($data['zip'] ?? ''), 20),
            'country' => $this->cleanText((string) ($data['country'] ?? ''), 120),
            'founded_year' => $this->cleanInt($data['founded_year'] ?? null),
            'employee_count' => $this->cleanInt($data['employee_count'] ?? null),
            'is_partner' => !empty($data['is_partner']) ? 1 : 0,
            'is_top_partner' => !empty($data['is_top_partner']) ? 1 : 0,
            'is_sponsor' => !empty($data['is_sponsor']) ? 1 : 0,
            'status' => in_array((string) ($data['status'] ?? 'active'), ['active', 'inactive'], true) ? (string) $data['status'] : 'active',
        ];

        if ($this->wordCount((string) $payload['description']) < 250) {
            $payload['description'] = $this->buildLongCompanyDescription([
                'name' => $name,
                'industry' => (string) $payload['industry'],
                'company_size' => (string) $payload['company_size'],
                'description' => (string) $payload['description'],
                'website' => (string) $payload['website'],
                'city' => (string) $payload['city'],
                'country' => (string) $payload['country'],
                'employee_count' => (string) ($payload['employee_count'] ?? ''),
                'founded_year' => (string) ($payload['founded_year'] ?? ''),
            ]);
        }

        $payload = $this->filterPayloadByColumns($payload, $columns);
        if ($payload === []) {
            return false;
        }

        return $this->saveRecord($table, $payload, $id);
    }

    private function createFallbackTables(): void
    {
        $db = CMS\Database::instance();
        $p = $db->prefix();
        $pdo = $db->getPdo();

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}" . self::TABLE_EXPERTS . " (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            seed_key VARCHAR(80) DEFAULT NULL,
            first_name VARCHAR(120) NOT NULL,
            last_name VARCHAR(120) NOT NULL,
            company VARCHAR(255) DEFAULT NULL,
            position VARCHAR(255) DEFAULT NULL,
            biography TEXT DEFAULT NULL,
            website VARCHAR(600) DEFAULT NULL,
            city VARCHAR(120) DEFAULT NULL,
            location_city VARCHAR(120) DEFAULT NULL,
            country VARCHAR(120) DEFAULT NULL,
            availability VARCHAR(80) DEFAULT NULL,
            experience_years INT DEFAULT NULL,
            hourly_rate DECIMAL(10,2) DEFAULT NULL,
            daily_rate DECIMAL(10,2) DEFAULT NULL,
            skills_general TEXT DEFAULT NULL,
            skills_tech TEXT DEFAULT NULL,
            skills_soft TEXT DEFAULT NULL,
            awards TEXT DEFAULT NULL,
            certifications TEXT DEFAULT NULL,
            linked_company_id INT UNSIGNED DEFAULT NULL,
            linked_speaker_id INT UNSIGNED DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_seed_key (seed_key),
            INDEX idx_status (status),
            INDEX idx_last_first (last_name, first_name),
            INDEX idx_linked_company (linked_company_id),
            INDEX idx_linked_speaker (linked_speaker_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}" . self::TABLE_COMPANIES . " (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            seed_key VARCHAR(80) DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(180) DEFAULT NULL,
            phone VARCHAR(80) DEFAULT NULL,
            industry VARCHAR(255) DEFAULT NULL,
            company_size VARCHAR(120) DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            website VARCHAR(600) DEFAULT NULL,
            city VARCHAR(120) DEFAULT NULL,
            location_city VARCHAR(120) DEFAULT NULL,
            zip VARCHAR(20) DEFAULT NULL,
            country VARCHAR(120) DEFAULT NULL,
            founded_year INT DEFAULT NULL,
            employee_count INT DEFAULT NULL,
            is_partner TINYINT(1) NOT NULL DEFAULT 0,
            is_top_partner TINYINT(1) NOT NULL DEFAULT 0,
            is_sponsor TINYINT(1) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_seed_key (seed_key),
            INDEX idx_status (status),
            INDEX idx_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}" . self::TABLE_SETTINGS . " (
            setting_key VARCHAR(120) NOT NULL PRIMARY KEY,
            setting_value LONGTEXT DEFAULT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->ensureColumn(self::TABLE_EXPERTS, 'linked_company_id', 'linked_company_id INT UNSIGNED DEFAULT NULL');
        $this->ensureColumn(self::TABLE_EXPERTS, 'linked_speaker_id', 'linked_speaker_id INT UNSIGNED DEFAULT NULL');
    }

    private function seedDefaults(bool $force): void
    {
        $seedFile = CMS_365NET_EXCOMP_PLUGIN_DIR . 'defaults/seed-data.php';
        if (!is_file($seedFile)) {
            return;
        }

        /** @var array{experts?:array<int,array<string,string>>,companies?:array<int,array<string,string>>} $seed */
        $seed = require $seedFile;
        $experts = is_array($seed['experts'] ?? null) ? $seed['experts'] : [];
        $companies = is_array($seed['companies'] ?? null) ? $seed['companies'] : [];

        $this->seedExperts($experts, $force);
        $this->seedCompanies($companies, $force);
    }

    /** @param array<int, array<string, string>> $rows */
    private function seedExperts(array $rows, bool $force): void
    {
        if ($rows === []) {
            return;
        }

        $table = $this->resolveTable('experts');
        if (!$force && $this->countTableRows($table) > 0) {
            return;
        }

        $columns = $this->getExistingColumns($table);
        if ($columns === []) {
            return;
        }

        $seedKeySupported = in_array('seed_key', $columns, true);

        foreach ($rows as $row) {
            $firstName = $this->cleanText((string) ($row['first_name'] ?? ''), 120);
            $lastName = $this->cleanText((string) ($row['last_name'] ?? ''), 120);
            if ($firstName === '' || $lastName === '') {
                continue;
            }

            $payload = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'company' => $this->cleanText((string) ($row['company'] ?? ''), 255),
                'position' => $this->cleanText((string) ($row['position'] ?? ''), 255),
                'biography' => $this->buildLongExpertBiography($row),
                'website' => $this->cleanUrl((string) ($row['website'] ?? '')),
                'city' => $this->cleanText((string) ($row['city'] ?? ''), 120),
                'location_city' => $this->cleanText((string) ($row['city'] ?? ''), 120),
                'country' => $this->cleanText((string) ($row['country'] ?? ''), 120),
                'availability' => $this->cleanText((string) ($row['availability'] ?? 'available'), 80),
                'experience_years' => $this->cleanInt($row['experience_years'] ?? null),
                'hourly_rate' => $this->cleanDecimal($row['hourly_rate'] ?? null),
                'daily_rate' => $this->cleanDecimal($row['daily_rate'] ?? null),
                'skills_general' => $this->cleanTextarea((string) ($row['skills_general'] ?? ''), 2000),
                'skills_tech' => $this->cleanTextarea((string) ($row['skills_tech'] ?? ''), 2000),
                'skills_soft' => $this->cleanTextarea((string) ($row['skills_soft'] ?? ''), 2000),
                'awards' => $this->cleanTextarea((string) ($row['awards'] ?? ''), 2000),
                'certifications' => $this->cleanTextarea((string) ($row['certifications'] ?? ''), 2000),
                'status' => in_array((string) ($row['status'] ?? 'active'), ['active', 'inactive'], true) ? (string) $row['status'] : 'active',
            ];

            if ($seedKeySupported) {
                $payload['seed_key'] = 'expert-' . substr(hash('sha256', $firstName . '|' . $lastName . '|' . (string) ($row['company'] ?? '')), 0, 40);
            }

            $payload = $this->filterPayloadByColumns($payload, $columns);
            if ($payload === []) {
                continue;
            }

            $this->insertRow($table, $payload);
        }
    }

    /** @param array<int, array<string, string>> $rows */
    private function seedCompanies(array $rows, bool $force): void
    {
        if ($rows === []) {
            return;
        }

        $table = $this->resolveTable('companies');
        if (!$force && $this->countTableRows($table) > 0) {
            return;
        }

        $columns = $this->getExistingColumns($table);
        if ($columns === []) {
            return;
        }

        $seedKeySupported = in_array('seed_key', $columns, true);

        foreach ($rows as $row) {
            $name = $this->cleanText((string) ($row['name'] ?? ''), 255);
            if ($name === '') {
                continue;
            }

            $payload = [
                'name' => $name,
                'email' => $this->cleanEmail((string) ($row['email'] ?? '')),
                'phone' => $this->cleanText((string) ($row['phone'] ?? ''), 80),
                'industry' => $this->cleanText((string) ($row['industry'] ?? ''), 255),
                'company_size' => $this->cleanText((string) ($row['company_size'] ?? ''), 120),
                'description' => $this->buildLongCompanyDescription($row),
                'website' => $this->cleanUrl((string) ($row['website'] ?? '')),
                'city' => $this->cleanText((string) ($row['city'] ?? ''), 120),
                'location_city' => $this->cleanText((string) ($row['city'] ?? ''), 120),
                'zip' => $this->cleanText((string) ($row['zip'] ?? ''), 20),
                'country' => $this->cleanText((string) ($row['country'] ?? 'Deutschland'), 120),
                'founded_year' => $this->cleanInt($row['founded_year'] ?? null),
                'employee_count' => $this->cleanInt($row['employee_count'] ?? null),
                'is_partner' => !empty($row['is_partner']) ? 1 : 0,
                'is_top_partner' => !empty($row['is_top_partner']) ? 1 : 0,
                'is_sponsor' => !empty($row['is_sponsor']) ? 1 : 0,
                'status' => in_array((string) ($row['status'] ?? 'active'), ['active', 'inactive'], true) ? (string) $row['status'] : 'active',
            ];

            if ($seedKeySupported) {
                $payload['seed_key'] = 'company-' . substr(hash('sha256', $name . '|' . (string) ($row['website'] ?? '')), 0, 40);
            }

            $payload = $this->filterPayloadByColumns($payload, $columns);
            if ($payload === []) {
                continue;
            }

            $this->insertRow($table, $payload);
        }
    }

    /** @return array<int, object> */
    private function getExperts(string $search, string $city, int $limit, int $offset, bool $onlyActive): array
    {
        $table = $this->resolveTable('experts');
        $columns = $this->getExistingColumns($table);
        if ($columns === []) {
            return [];
        }

        $select = $this->buildSelect($columns, [
            'id',
            'first_name',
            'last_name',
            'company',
            'position',
            'biography',
            'website',
            'city',
            'location_city',
            'country',
            'availability',
            'skills_general',
            'skills_tech',
            'skills_soft',
            'awards',
            'certifications',
            'linked_company_id',
            'linked_speaker_id',
            'status',
            'updated_at',
            'photo_url',
        ]);

        $where = ['1=1'];
        $params = [];

        if ($onlyActive && in_array('status', $columns, true)) {
            $where[] = 'status = ?';
            $params[] = 'active';
        }

        if ($search !== '') {
            $searchable = array_values(array_filter([
                in_array('first_name', $columns, true) ? 'first_name' : null,
                in_array('last_name', $columns, true) ? 'last_name' : null,
                in_array('company', $columns, true) ? 'company' : null,
                in_array('position', $columns, true) ? 'position' : null,
                in_array('skills_general', $columns, true) ? 'skills_general' : null,
                in_array('skills_tech', $columns, true) ? 'skills_tech' : null,
            ]));

            if ($searchable !== []) {
                $searchSql = [];
                foreach ($searchable as $field) {
                    $searchSql[] = $field . ' LIKE ?';
                    $params[] = '%' . $search . '%';
                }
                $where[] = '(' . implode(' OR ', $searchSql) . ')';
            }
        }

        if ($city !== '') {
            $cityColumn = in_array('location_city', $columns, true) ? 'location_city' : (in_array('city', $columns, true) ? 'city' : '');
            if ($cityColumn !== '') {
                $where[] = $cityColumn . ' LIKE ?';
                $params[] = '%' . $city . '%';
            }
        }

        $orderBy = in_array('updated_at', $columns, true) ? 'updated_at DESC' : 'id DESC';
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);

        $db = CMS\Database::instance();
        $sql = 'SELECT ' . $select . ' FROM ' . $db->prefix() . $table . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $orderBy . ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];

        foreach ($rows as $row) {
            if (!isset($row->location_city) && isset($row->city)) {
                $row->location_city = (string) $row->city;
            }
            if (!isset($row->city) && isset($row->location_city)) {
                $row->city = (string) $row->location_city;
            }
        }

        return $rows;
    }

    /** @return array<int, object> */
    private function getCompanies(string $search, string $city, int $limit, int $offset, bool $onlyActive): array
    {
        $table = $this->resolveTable('companies');
        $columns = $this->getExistingColumns($table);
        if ($columns === []) {
            return [];
        }

        $select = $this->buildSelect($columns, [
            'id',
            'name',
            'email',
            'phone',
            'industry',
            'company_size',
            'description',
            'website',
            'city',
            'location_city',
            'zip',
            'country',
            'founded_year',
            'employee_count',
            'is_partner',
            'is_top_partner',
            'is_sponsor',
            'status',
            'updated_at',
            'logo_url',
        ]);

        $where = ['1=1'];
        $params = [];

        if ($onlyActive && in_array('status', $columns, true)) {
            $where[] = 'status = ?';
            $params[] = 'active';
        }

        if ($search !== '') {
            $searchable = array_values(array_filter([
                in_array('name', $columns, true) ? 'name' : null,
                in_array('industry', $columns, true) ? 'industry' : null,
                in_array('description', $columns, true) ? 'description' : null,
            ]));

            if ($searchable !== []) {
                $searchSql = [];
                foreach ($searchable as $field) {
                    $searchSql[] = $field . ' LIKE ?';
                    $params[] = '%' . $search . '%';
                }
                $where[] = '(' . implode(' OR ', $searchSql) . ')';
            }
        }

        if ($city !== '') {
            $cityColumn = in_array('location_city', $columns, true) ? 'location_city' : (in_array('city', $columns, true) ? 'city' : '');
            if ($cityColumn !== '') {
                $where[] = $cityColumn . ' LIKE ?';
                $params[] = '%' . $city . '%';
            }
        }

        $orderBy = 'id DESC';
        if (in_array('is_sponsor', $columns, true) || in_array('is_top_partner', $columns, true) || in_array('is_partner', $columns, true)) {
            $parts = [];
            if (in_array('is_sponsor', $columns, true)) {
                $parts[] = 'is_sponsor DESC';
            }
            if (in_array('is_top_partner', $columns, true)) {
                $parts[] = 'is_top_partner DESC';
            }
            if (in_array('is_partner', $columns, true)) {
                $parts[] = 'is_partner DESC';
            }
            if (in_array('updated_at', $columns, true)) {
                $parts[] = 'updated_at DESC';
            }
            $parts[] = in_array('name', $columns, true) ? 'name ASC' : 'id DESC';
            $orderBy = implode(', ', $parts);
        } elseif (in_array('updated_at', $columns, true)) {
            $orderBy = 'updated_at DESC';
        }

        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);

        $db = CMS\Database::instance();
        $sql = 'SELECT ' . $select . ' FROM ' . $db->prefix() . $table . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $orderBy . ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];

        foreach ($rows as $row) {
            if (!isset($row->location_city) && isset($row->city)) {
                $row->location_city = (string) $row->city;
            }
            if (!isset($row->city) && isset($row->location_city)) {
                $row->city = (string) $row->location_city;
            }
        }

        return $rows;
    }

    private function countRows(string $entity, string $search, string $city, bool $onlyActive): int
    {
        $table = $this->resolveTable($entity);
        $columns = $this->getExistingColumns($table);
        if ($columns === []) {
            return 0;
        }

        $where = ['1=1'];
        $params = [];

        if ($onlyActive && in_array('status', $columns, true)) {
            $where[] = 'status = ?';
            $params[] = 'active';
        }

        if ($search !== '') {
            $searchable = $entity === 'experts'
                ? ['first_name', 'last_name', 'company', 'position', 'skills_general', 'skills_tech']
                : ['name', 'industry', 'description'];

            $searchSql = [];
            foreach ($searchable as $field) {
                if (!in_array($field, $columns, true)) {
                    continue;
                }
                $searchSql[] = $field . ' LIKE ?';
                $params[] = '%' . $search . '%';
            }

            if ($searchSql !== []) {
                $where[] = '(' . implode(' OR ', $searchSql) . ')';
            }
        }

        if ($city !== '') {
            $cityColumn = in_array('location_city', $columns, true) ? 'location_city' : (in_array('city', $columns, true) ? 'city' : '');
            if ($cityColumn !== '') {
                $where[] = $cityColumn . ' LIKE ?';
                $params[] = '%' . $city . '%';
            }
        }

        $db = CMS\Database::instance();
        $stmt = $db->prepare('SELECT COUNT(*) AS total FROM ' . $db->prefix() . $table . ' WHERE ' . implode(' AND ', $where));
        $stmt->execute($params);
        $row = $stmt->fetch();

        return max(0, (int) ($row->total ?? 0));
    }

    private function resolveTable(string $entity): string
    {
        return $entity === 'experts' ? self::TABLE_EXPERTS : self::TABLE_COMPANIES;
    }

    /** @return array<int, string> */
    private function getExistingColumns(string $table): array
    {
        if (isset($this->tableColumnsCache[$table])) {
            return $this->tableColumnsCache[$table];
        }

        try {
            $db = CMS\Database::instance();
            $stmt = $db->prepare('SHOW COLUMNS FROM ' . $db->prefix() . $table);
            $stmt->execute([]);
            $rows = $stmt->fetchAll() ?: [];
            $columns = [];
            foreach ($rows as $row) {
                $field = is_object($row) ? ($row->Field ?? null) : ($row['Field'] ?? null);
                if (is_string($field) && $field !== '') {
                    $columns[] = $field;
                }
            }
            $this->tableColumnsCache[$table] = $columns;
            return $columns;
        } catch (Throwable) {
            $this->tableColumnsCache[$table] = [];
            return [];
        }
    }

    private function ensureColumn(string $table, string $column, string $definition): void
    {
        $columns = $this->getExistingColumns($table);
        if (in_array($column, $columns, true)) {
            return;
        }

        $db = CMS\Database::instance();
        $db->getPdo()->exec('ALTER TABLE `' . $db->prefix() . $table . '` ADD COLUMN ' . $definition);
        unset($this->tableColumnsCache[$table]);
    }

    private function tableExists(string $table): bool
    {
        if (isset($this->tableExistsCache[$table])) {
            return $this->tableExistsCache[$table];
        }

        try {
            $db = CMS\Database::instance();
            $stmt = $db->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$db->prefix() . $table]);
            $this->tableExistsCache[$table] = (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            $this->tableExistsCache[$table] = false;
        }

        return $this->tableExistsCache[$table];
    }

    /** @param array<int, string> $available */
    private function buildSelect(array $available, array $wanted): string
    {
        $fields = [];
        foreach ($wanted as $field) {
            if (in_array($field, $available, true)) {
                $fields[] = $field;
            }
        }

        if (!in_array('id', $fields, true) && in_array('id', $available, true)) {
            $fields[] = 'id';
        }

        return $fields !== [] ? implode(', ', $fields) : '*';
    }

    /** @param array<string, mixed> $payload @param array<int, string> $columns @return array<string, mixed> */
    private function filterPayloadByColumns(array $payload, array $columns): array
    {
        return array_intersect_key($payload, array_flip($columns));
    }

    /** @param array<string, mixed> $payload */
    private function saveRecord(string $table, array $payload, int $id): int|false
    {
        $db = CMS\Database::instance();

        if ($id > 0) {
            $sets = implode(', ', array_map(static fn(string $key): string => $key . ' = ?', array_keys($payload)));
            $stmt = $db->prepare('UPDATE ' . $db->prefix() . $table . ' SET ' . $sets . ' WHERE id = ?');
            $ok = $stmt->execute([...array_values($payload), $id]);
            return $ok ? $id : false;
        }

        $keys = implode(', ', array_keys($payload));
        $placeholders = implode(', ', array_fill(0, count($payload), '?'));
        $stmt = $db->prepare('INSERT INTO ' . $db->prefix() . $table . ' (' . $keys . ') VALUES (' . $placeholders . ')');
        $ok = $stmt->execute(array_values($payload));
        if (!$ok) {
            return false;
        }

        return (int) $db->getPdo()->lastInsertId();
    }

    /** @param array<string, mixed> $payload */
    private function insertRow(string $table, array $payload): void
    {
        $db = CMS\Database::instance();

        if (isset($payload['seed_key']) && $payload['seed_key'] !== '') {
            $stmtCheck = $db->prepare('SELECT id FROM ' . $db->prefix() . $table . ' WHERE seed_key = ? LIMIT 1');
            $stmtCheck->execute([(string) $payload['seed_key']]);
            if ((int) ($stmtCheck->fetchColumn() ?: 0) > 0) {
                return;
            }
        }

        $keys = implode(', ', array_keys($payload));
        $placeholders = implode(', ', array_fill(0, count($payload), '?'));
        $stmt = $db->prepare('INSERT INTO ' . $db->prefix() . $table . ' (' . $keys . ') VALUES (' . $placeholders . ')');
        $stmt->execute(array_values($payload));
    }

    private function countTableRows(string $table): int
    {
        $db = CMS\Database::instance();
        $stmt = $db->prepare('SELECT COUNT(*) FROM ' . $db->prefix() . $table);
        $stmt->execute([]);
        return max(0, (int) $stmt->fetchColumn());
    }

    private function saveSetting(string $key, string $value): void
    {
        $db = CMS\Database::instance();
        $stmt = $db->prepare('INSERT INTO ' . $db->prefix() . self::TABLE_SETTINGS . ' (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $stmt->execute([$this->cleanText($key, 120), $value]);
    }

    /** @param array<string, string> $row */
    private function buildLongExpertBiography(array $row): string
    {
        $firstName = $this->cleanText((string) ($row['first_name'] ?? ''), 120);
        $lastName = $this->cleanText((string) ($row['last_name'] ?? ''), 120);
        $fullName = trim($firstName . ' ' . $lastName);
        $company = $this->cleanText((string) ($row['company'] ?? ''), 255);
        $position = $this->cleanText((string) ($row['position'] ?? ''), 255);
        $website = $this->cleanUrl((string) ($row['website'] ?? ''));
        $baseBio = $this->cleanTextarea((string) ($row['biography'] ?? ''), 1200);
        $skillsGeneral = $this->cleanTextarea((string) ($row['skills_general'] ?? ''), 800);
        $skillsTech = $this->cleanTextarea((string) ($row['skills_tech'] ?? ''), 800);
        $skillsSoft = $this->cleanTextarea((string) ($row['skills_soft'] ?? ''), 800);
        $awards = $this->cleanTextarea((string) ($row['awards'] ?? ''), 800);
        $certifications = $this->cleanTextarea((string) ($row['certifications'] ?? ''), 800);
        $city = $this->cleanText((string) ($row['city'] ?? ''), 120);
        $country = $this->cleanText((string) ($row['country'] ?? ''), 120);
        $profileSnippet = $this->domainKnowledge($website);

        $nameSegment = $fullName !== '' ? $fullName : 'Dieses Profil';
        $companySegment = $company !== '' ? $company : 'dem jeweiligen Netzwerkumfeld';
        $positionSegment = $position !== '' ? $position : 'strategischer IT- und Digitalexpertise';

        $paragraphs = [];
        if ($baseBio !== '') {
            $paragraphs[] = $baseBio;
        }

        $paragraphs[] = $nameSegment . ' steht im 365NET-Umfeld für belastbare Praxis in ' . $positionSegment . '. Im Zusammenspiel mit ' . $companySegment . ' zeigt das Profil, wie aus technologischen Anforderungen konkrete Ergebnisse in Projekten, Programmen und operativen Teams entstehen. Die Ausrichtung ist dabei klar: fachlich saubere Entscheidungen, nachvollziehbare Kommunikation gegenüber Stakeholdern und ein konsequenter Fokus auf Umsetzbarkeit im Alltag.';

        if ($profileSnippet !== '') {
            $paragraphs[] = 'Aus dem öffentlichen Unternehmenskontext ergibt sich ein zusätzlicher Schwerpunkt: ' . $profileSnippet . ' Diese Einordnung hilft, das Profil nicht nur als Einzelperson, sondern als aktiven Teil eines größeren Ökosystems zu verstehen, in dem Sicherheit, Skalierung, Governance und nachhaltige Wirkung gemeinsam gedacht werden.';
        }

        if ($skillsGeneral !== '' || $skillsTech !== '' || $skillsSoft !== '') {
            $paragraphs[] = 'Thematisch deckt das Profil sowohl strategische als auch operative Ebenen ab. Allgemeine Schwerpunkte: ' . ($skillsGeneral !== '' ? $skillsGeneral : 'digitale Transformation und moderne Zusammenarbeit') . '. Technische Kompetenzfelder: ' . ($skillsTech !== '' ? $skillsTech : 'Cloud-Plattformen, Security-Baselines und Integrationsmuster') . '. Ergänzend bringen die Soft Skills — ' . ($skillsSoft !== '' ? $skillsSoft : 'klare Moderation, strukturierte Priorisierung und Team-orientierte Kommunikation') . ' — genau die Qualitäten mit, die in komplexen Vorhaben über den Erfolg entscheiden.';
        }

        if ($awards !== '' || $certifications !== '') {
            $paragraphs[] = 'Nachweise aus der Community und aus Weiterbildungsprogrammen unterstreichen die Professionalität des Profils. Auszeichnungen: ' . ($awards !== '' ? $awards : 'relevante Anerkennungen aus Fachnetzwerken') . '. Zertifizierungen: ' . ($certifications !== '' ? $certifications : 'kontinuierlich aktualisierte Kompetenznachweise') . '. Dadurch wird deutlich, dass die Expertise nicht statisch ist, sondern aktiv weiterentwickelt und an neue Rahmenbedingungen angepasst wird.';
        }

        $locationParts = array_values(array_filter([$city, $country], static fn(string $v): bool => $v !== ''));
        if ($locationParts !== []) {
            $paragraphs[] = 'Regional verankert in ' . implode(', ', $locationParts) . ', gleichzeitig aber mit Blick auf überregionale Anforderungen, verbindet das Profil lokale Nähe mit skalierbaren Lösungsansätzen. So können Teams vor Ort pragmatisch starten und dennoch eine Architektur aufbauen, die langfristig tragfähig bleibt — von der ersten Analyse bis zur stabilen Betriebsphase.';
        }

        $paragraphs[] = 'Im Ergebnis repräsentiert dieses Expert-Profil einen verlässlichen Beitrag für Unternehmen, öffentliche Institutionen und Projektteams, die messbare Fortschritte erzielen wollen: klar priorisierte Roadmaps, sichere technische Umsetzung, und ein Kommunikationsstil, der Fachlichkeit und Management-Perspektive zusammenführt. Genau diese Verbindung macht das Profil für die kombinierte Experts-&-Companie-Übersicht besonders wertvoll.';

        return $this->ensureMinWords(implode("\n\n", $paragraphs), 260, [
            'Zusätzlich wichtig ist die Fähigkeit, Anforderungen aus Fachabteilungen, IT-Betrieb und Führungsebene früh zusammenzuführen. Dadurch entstehen belastbare Entscheidungen mit hoher Akzeptanz, geringeren Reibungsverlusten und klaren Verantwortlichkeiten über den gesamten Lebenszyklus eines Vorhabens hinweg.',
            'Besonders in dynamischen Umfeldern zeigt sich der Mehrwert dieses Profils darin, Risiken transparent zu machen, Abhängigkeiten realistisch zu bewerten und gleichzeitig schnelle, nutzbare Zwischenergebnisse zu liefern. Das stärkt Vertrauen bei Auftraggebern und schafft verlässliche Orientierung für alle beteiligten Teams.',
        ]);
    }

    /** @param array<string, string> $row */
    private function buildLongCompanyDescription(array $row): string
    {
        $name = $this->cleanText((string) ($row['name'] ?? ''), 255);
        $industry = $this->cleanText((string) ($row['industry'] ?? ''), 255);
        $companySize = $this->cleanText((string) ($row['company_size'] ?? ''), 120);
        $description = $this->cleanTextarea((string) ($row['description'] ?? ''), 1600);
        $website = $this->cleanUrl((string) ($row['website'] ?? ''));
        $city = $this->cleanText((string) ($row['city'] ?? ''), 120);
        $country = $this->cleanText((string) ($row['country'] ?? ''), 120);
        $foundedYear = $this->cleanText((string) ($row['founded_year'] ?? ''), 10);
        $employeeCount = $this->cleanText((string) ($row['employee_count'] ?? ''), 20);
        $profileSnippet = $this->domainKnowledge($website);

        $nameSegment = $name !== '' ? $name : 'Dieses Unternehmen';
        $industrySegment = $industry !== '' ? $industry : 'digitale Plattform- und IT-Dienstleistungen';

        $paragraphs = [];
        if ($description !== '') {
            $paragraphs[] = $description;
        }

        $paragraphs[] = $nameSegment . ' ist in der kombinierten 365NET-Landschaft als relevantes Unternehmen im Bereich ' . $industrySegment . ' positioniert. Das Profil steht für die Verbindung aus technologischer Substanz, klaren Leistungsversprechen und der Fähigkeit, anspruchsvolle Transformationsprojekte in unterschiedlichen Organisationsgrößen verlässlich umzusetzen. Entscheidend ist dabei nicht nur die reine Technologieauswahl, sondern die konsequente Ausrichtung an messbaren geschäftlichen und organisatorischen Ergebnissen.';

        if ($companySize !== '' || $employeeCount !== '' || $foundedYear !== '') {
            $paragraphs[] = 'Organisatorisch zeigt das Unternehmen ein belastbares Setup: Größenklasse ' . ($companySize !== '' ? $companySize : 'professionelles Mittelstands-/Enterprise-Niveau') . ($employeeCount !== '' ? ', mit dokumentierter Teamgröße von etwa ' . $employeeCount . ' Mitarbeitenden' : '') . ($foundedYear !== '' ? ', und historischer Verankerung seit ' . $foundedYear : '') . '. Diese Eckdaten sind ein wichtiger Indikator für Lieferfähigkeit, Kontinuität und die Fähigkeit, langfristige Partnerschaften strategisch zu entwickeln.';
        }

        if ($profileSnippet !== '') {
            $paragraphs[] = 'Aus öffentlich zugänglichen Unternehmensinformationen ergibt sich zudem ein klares Leistungsprofil: ' . $profileSnippet . ' Dadurch wird sichtbar, dass das Unternehmen nicht nur einzelne Tools anbietet, sondern End-to-End-Verantwortung von Beratung und Design über Implementierung bis zum stabilen Betrieb übernehmen kann.';
        }

        $locationParts = array_values(array_filter([$city, $country], static fn(string $v): bool => $v !== ''));
        if ($locationParts !== []) {
            $paragraphs[] = 'Der Standortbezug auf ' . implode(', ', $locationParts) . ' ergänzt das Profil um regionale Nähe und direkte Erreichbarkeit. Gleichzeitig bleibt das Unternehmen anschlussfähig für überregionale oder internationale Initiativen, weil Prozesse, Kommunikationsstandards und Sicherheitsanforderungen strukturiert und skalierbar aufgesetzt sind.';
        }

        $paragraphs[] = 'Im Ergebnis entsteht ein Unternehmensprofil, das in der Experts-&-Companie-Übersicht bewusst mehr ist als ein Eintrag im Verzeichnis: Es ist ein belastbarer Anknüpfungspunkt für Kooperationen, Projektanfragen und strategische Partnerschaften. Genau darin liegt der Mehrwert dieser Darstellung — Qualität sichtbar machen, Vergleichbarkeit erhöhen und die passenden Kontakte zwischen Unternehmen und Expert:innen effizient herstellen.';

        return $this->ensureMinWords(implode("\n\n", $paragraphs), 260, [
            'Für potenzielle Partner bedeutet das vor allem Planungssicherheit: Anforderungen werden strukturiert aufgenommen, Optionen transparent bewertet und Entscheidungen nachvollziehbar dokumentiert. So können Programme schneller starten, Risiken früher adressiert und Umsetzungen dauerhaft stabil betrieben werden.',
            'Gerade in Zeiten hoher Dynamik ist diese Kombination aus fachlicher Tiefe, operativer Zuverlässigkeit und klarer Governance ein wesentlicher Erfolgsfaktor. Sie reduziert Komplexität, stärkt die Zusammenarbeit zwischen Business und IT und sorgt dafür, dass Investitionen in Technologie langfristig wirksam bleiben.',
        ]);
    }

    private function ensureMinWords(string $text, int $minWords, array $fallbackParagraphs): string
    {
        $text = trim($text);
        if ($text === '') {
            $text = 'Profilbeschreibung wird ergänzt.';
        }

        $idx = 0;
        while ($this->wordCount($text) < $minWords) {
            $append = $fallbackParagraphs[$idx % max(1, count($fallbackParagraphs))] ?? 'Dieses Profil wird kontinuierlich inhaltlich erweitert.';
            $text .= "\n\n" . $append;
            $idx++;
            if ($idx > 10) {
                break;
            }
        }

        return trim($text);
    }

    private function wordCount(string $value): int
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
        if ($value === '') {
            return 0;
        }

        $parts = preg_split('/\s+/u', $value) ?: [];
        return count(array_filter($parts, static fn(string $v): bool => trim($v) !== ''));
    }

    private function domainKnowledge(string $url): string
    {
        $domain = $this->extractDomain($url);
        if ($domain === '') {
            return '';
        }

        $knowledge = [
            'also.com' => 'ALSO positioniert sich als führender Technologieanbieter für die ITK-Branche mit Zugang zu Waren und Services von über 800 Herstellern sowie Cloud-, IoT-, KI- und Cybersecurity-Angeboten.',
            't-systems.com' => 'T-Systems beschreibt ein End-to-End-Portfolio aus Consulting, Cloud, AI, Security und Connectivity mit mehr als 25.000 Mitarbeitenden in 26 Ländern.',
            'governikus.de' => 'Governikus fokussiert digitale Verwaltung mit sicheren Identitäten, sicherer Kommunikation und sicheren Daten — inklusive rechtsverbindlicher Prozesse für den Public Sector.',
            'siemens.com' => 'Siemens adressiert Digitalisierung industrieller Wertschöpfung mit Engineering-, Simulations- und KI-Lösungen und einem starken Fokus auf skalierbare Plattformen.',
            'bosch-sensortec.com' => 'Bosch Sensortec positioniert AI-basierte Sensorlösungen für Wearables, Robotics und IoT mit breitem Portfolio von Motion- und Umweltsensorik.',
            'adesso.de' => 'adesso verbindet Business, Menschen und Technologie und betont digitale Souveränität, branchenspezifische Beratung sowie langfristige Transformationsprojekte.',
            'sap.com' => 'SAP stellt globale Unternehmenslösungen bereit und hebt die effiziente Nutzung von Geschäftsdaten, Partnerökosysteme und skalierbare Plattformprozesse hervor.',
            'softwareone.com' => 'SoftwareOne fokussiert Optimierung von Software- und Cloud-Investitionen, Daten- und AI-Kompetenz sowie messbare Effizienzsteigerung entlang der gesamten IT-Wertschöpfung.',
            'fortinet.com' => 'Fortinet beschreibt eine AI-getriebene Security-Plattform mit breitem Produktportfolio, hoher Kundenbasis und Schwerpunkt auf konvergente Netzwerk- und Sicherheitsarchitektur.',
            'netatwork.de' => 'Net at Work ist im Kontext von Messaging, Collaboration und Security als spezialisierter Integrations- und Betriebsdienstleister positioniert.',
            'ceyoniq.com' => 'Ceyoniq adressiert digitale Geschäftsprozesse und Dokumentenmanagement mit Fokus auf strukturierte, rechtskonforme Informationsflüsse.',
            'bmwgroup.com' => 'BMW Group steht für globale Produktions- und Innovationsnetzwerke rund um Mobilität, Nachhaltigkeit und datengetriebene industrielle Transformation.',
        ];

        foreach ($knowledge as $needle => $snippet) {
            if (str_contains($domain, $needle)) {
                return $snippet;
            }
        }

        return '';
    }

    private function extractDomain(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $host = parse_url(str_starts_with($url, 'http') ? $url : 'https://' . $url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return '';
        }

        return preg_replace('/^www\./i', '', strtolower($host)) ?: '';
    }

    private function cleanText(string $value, int $maxLen = 255): string
    {
        $value = trim(strip_tags($value));
        return function_exists('mb_substr') ? (string) mb_substr($value, 0, $maxLen, 'UTF-8') : substr($value, 0, $maxLen);
    }

    private function cleanTextarea(string $value, int $maxLen = 10000): string
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return function_exists('mb_substr') ? (string) mb_substr($value, 0, $maxLen, 'UTF-8') : substr($value, 0, $maxLen);
    }

    private function cleanUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (!str_starts_with($value, 'http://') && !str_starts_with($value, 'https://')) {
            $value = 'https://' . ltrim($value, '/');
        }

        return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
    }

    private function cleanEmail(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : '';
    }

    private function cleanInt(mixed $value): ?int
    {
        $string = trim((string) $value);
        if ($string === '' || preg_match('/^-?\d+$/', $string) !== 1) {
            return null;
        }

        return (int) $string;
    }

    private function cleanPositiveInt(mixed $value): ?int
    {
        $int = $this->cleanInt($value);
        return $int !== null && $int > 0 ? $int : null;
    }

    private function syncSpeakerLinkForExpert(int $expertId, ?int $speakerId, ?int $companyId, ?int $previousSpeakerId = null): void
    {
        if ($expertId <= 0 || !$this->tableExists('365net_event_speakers')) {
            return;
        }

        $speakerColumns = $this->getExistingColumns('365net_event_speakers');
        if (!in_array('linked_expert_id', $speakerColumns, true)) {
            return;
        }

        $db = CMS\Database::instance();
        $table = $db->prefix() . '365net_event_speakers';

        try {
            if ($previousSpeakerId !== null && $previousSpeakerId > 0 && $previousSpeakerId !== $speakerId) {
                if (in_array('linked_company_id', $speakerColumns, true)) {
                    $clearStmt = $db->prepare('UPDATE ' . $table . ' SET linked_expert_id = NULL, linked_company_id = NULL WHERE id = ? AND linked_expert_id = ?');
                    $clearStmt->execute([$previousSpeakerId, $expertId]);
                } else {
                    $clearStmt = $db->prepare('UPDATE ' . $table . ' SET linked_expert_id = NULL WHERE id = ? AND linked_expert_id = ?');
                    $clearStmt->execute([$previousSpeakerId, $expertId]);
                }
            }

            if ($speakerId === null || $speakerId <= 0) {
                return;
            }

            $currentSpeaker = $this->getLinkedSpeaker($speakerId);
            if ($currentSpeaker !== null) {
                $otherExpertId = $this->cleanPositiveInt($currentSpeaker->linked_expert_id ?? null);
                if ($otherExpertId !== null && $otherExpertId !== $expertId) {
                    $expertColumns = $this->getExistingColumns(self::TABLE_EXPERTS);
                    if (in_array('linked_speaker_id', $expertColumns, true)) {
                        $clearOther = $db->prepare('UPDATE ' . $db->prefix() . self::TABLE_EXPERTS . ' SET linked_speaker_id = NULL WHERE id = ? AND linked_speaker_id = ?');
                        $clearOther->execute([$otherExpertId, $speakerId]);
                    }
                }
            }

            if (in_array('linked_company_id', $speakerColumns, true)) {
                $linkStmt = $db->prepare('UPDATE ' . $table . ' SET linked_expert_id = ?, linked_company_id = ? WHERE id = ?');
                $linkStmt->execute([$expertId, $companyId, $speakerId]);
            } else {
                $linkStmt = $db->prepare('UPDATE ' . $table . ' SET linked_expert_id = ? WHERE id = ?');
                $linkStmt->execute([$expertId, $speakerId]);
            }
        } catch (Throwable) {
        }
    }

    private function cleanDecimal(mixed $value): ?float
    {
        $string = trim((string) $value);
        if ($string === '') {
            return null;
        }

        $normalized = str_replace(',', '.', $string);
        if (!is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }
}
