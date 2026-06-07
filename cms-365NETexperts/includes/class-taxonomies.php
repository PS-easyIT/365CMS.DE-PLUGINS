<?php
/**
 * Taxonomies für CMS Experts
 *
 * @package CMS_Experts
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Experts_Taxonomies
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
        $this->create_taxonomy_tables();
        $this->seed_default_specializations();
        $this->create_skill_presets_table();
        $this->update_skill_presets();
    }

    /**
     * Erstellt Taxonomie-Tabellen
     */
    private function create_taxonomy_tables(): void
    {
        $db = CMS\Database::instance();
        $pdo = $db->getPdo();
        $prefix = $db->prefix();

        try {
            // Fachrichtungen / Spezialisierungen
            $sql = "CREATE TABLE IF NOT EXISTS {$prefix}expert_specializations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                slug VARCHAR(150) NOT NULL UNIQUE,
                description TEXT,
                parent_id INT UNSIGNED DEFAULT NULL,
                count INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_slug (slug),
                INDEX idx_parent (parent_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $pdo->exec($sql);

            // Expert zu Spezialisierung Zuordnung
            $sql = "CREATE TABLE IF NOT EXISTS {$prefix}expert_specialization_rel (
                expert_id INT UNSIGNED NOT NULL,
                specialization_id INT UNSIGNED NOT NULL,
                is_primary BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (expert_id, specialization_id),
                FOREIGN KEY (expert_id) REFERENCES {$prefix}experts(id) ON DELETE CASCADE,
                FOREIGN KEY (specialization_id) REFERENCES {$prefix}expert_specializations(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $pdo->exec($sql);

        } catch (\PDOException $e) {
            error_log('CMS Experts Taxonomy Error: ' . $e->getMessage());
        }
    }

    /**
     * Holt alle Spezialisierungen
     */
    public function get_specializations(): array
    {
        $db = CMS\Database::instance();
        $stmt = $db->prepare("SELECT * FROM {$db->prefix()}expert_specializations ORDER BY name ASC");
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Holt Spezialisierungen für einen Experten
     */
    public function get_expert_specializations(int $expert_id): array
    {
        $db = CMS\Database::instance();
        $stmt = $db->prepare("
            SELECT s.* 
            FROM {$db->prefix()}expert_specializations s
            INNER JOIN {$db->prefix()}expert_specialization_rel r ON s.id = r.specialization_id
            WHERE r.expert_id = ?
            ORDER BY r.is_primary DESC, s.name ASC
        ");
        $stmt->execute([$expert_id]);
        
        return $stmt->fetchAll();
    }

    /**
     * Fügt Spezialisierung hinzu
     */
    public function add_specialization(string $name, string $slug, ?string $description = null, ?int $parent_id = null): int
    {
        $db = CMS\Database::instance();
        
        $result = $db->insert('expert_specializations', [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'parent_id' => $parent_id,
        ]);

        if ($result) {
            return (int) $db->getPdo()->lastInsertId();
        }
        
        return 0;
    }

    /**
     * Verknüpft Experten mit Spezialisierung
     */
    public function assign_specialization(int $expert_id, int $specialization_id, bool $is_primary = false): bool
    {
        if ($expert_id <= 0 || $specialization_id <= 0) {
            return false;
        }

        $db = CMS\Database::instance();
        
        // Wenn primary, entferne primary-Flag von anderen
        if ($is_primary) {
            $db->execute(
                "UPDATE {$db->prefix()}expert_specialization_rel SET is_primary = FALSE WHERE expert_id = ?",
                [$expert_id]
            );
        }

        return $db->insert('expert_specialization_rel', [
            'expert_id' => $expert_id,
            'specialization_id' => $specialization_id,
            'is_primary' => $is_primary ? 1 : 0,
        ]) !== false;
    }

    /**
     * Holt alle Spezialisierungen als flache Liste (Alias)
     */
    public function get_all(): array
    {
        return $this->get_specializations();
    }

    /**
     * Holt Spezialisierungs-IDs eines Experten
     *
     * @return int[]
     */
    public function get_expert_ids(int $expert_id): array
    {
        $db = CMS\Database::instance();
        $stmt = $db->prepare("
            SELECT specialization_id 
            FROM {$db->prefix()}expert_specialization_rel 
            WHERE expert_id = ? 
            ORDER BY is_primary DESC
        ");
        $stmt->execute([$expert_id]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    // ═══ Skill-Presets ══════════════════════════════════════════════════════

    private function create_skill_presets_table(): void
    {
        $db     = CMS\Database::instance();
        $pdo    = $db->getPdo();
        $prefix = $db->prefix();
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}expert_skill_presets (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                skill_name VARCHAR(150) NOT NULL,
                skill_type VARCHAR(30)  NOT NULL DEFAULT 'general',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_name_type (skill_name, skill_type),
                INDEX idx_type (skill_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $this->seed_default_skill_presets();
        } catch (\PDOException $e) {
            error_log('expert_skill_presets: ' . $e->getMessage());
        }
    }

    /**
     * Standard-Skill-Presets – vollständige Liste.
     * Wird sowohl beim Seed (leere Tabelle) als auch beim Update (INSERT IGNORE) verwendet.
     *
     * @return list<array{0: string, 1: string}>
     */
    private function get_default_presets(): array
    {
        return [
            // ── Programmierung (general) – Programmiersprachen & Grundlagen ─
            ['PHP', 'general'],
            ['Python', 'general'],
            ['Java', 'general'],
            ['C#', 'general'],
            ['JavaScript', 'general'],
            ['TypeScript', 'general'],
            ['Go', 'general'],
            ['Rust', 'general'],
            ['Swift', 'general'],
            ['Kotlin', 'general'],
            ['Ruby', 'general'],
            ['C++', 'general'],
            ['Bash/Shell', 'general'],
            ['SQL', 'general'],
            ['PowerShell', 'general'],
            ['R', 'general'],
            ['Scala', 'general'],
            ['Perl', 'general'],
            ['Dart', 'general'],
            ['HTML/CSS', 'general'],
            ['ABAP', 'general'],
            ['COBOL', 'general'],

            // ── Skills (tech) – Frameworks, Tools & Plattformen ──────────
            // Frontend
            ['React', 'tech'],
            ['Vue.js', 'tech'],
            ['Angular', 'tech'],
            ['Svelte', 'tech'],
            ['Next.js', 'tech'],
            ['Nuxt.js', 'tech'],
            ['Tailwind CSS', 'tech'],

            // Backend
            ['Node.js', 'tech'],
            ['Laravel', 'tech'],
            ['Symfony', 'tech'],
            ['Django', 'tech'],
            ['FastAPI', 'tech'],
            ['Spring Boot', 'tech'],
            ['ASP.NET', 'tech'],
            ['Express.js', 'tech'],
            ['Flask', 'tech'],

            // DevOps & Container
            ['Docker', 'tech'],
            ['Kubernetes', 'tech'],
            ['Terraform', 'tech'],
            ['Ansible', 'tech'],
            ['Puppet', 'tech'],
            ['Chef', 'tech'],
            ['CI/CD', 'tech'],
            ['Jenkins', 'tech'],
            ['GitHub Actions', 'tech'],
            ['GitLab CI', 'tech'],
            ['ArgoCD', 'tech'],

            // Cloud
            ['AWS', 'tech'],
            ['Azure', 'tech'],
            ['GCP', 'tech'],
            ['Azure DevOps', 'tech'],
            ['Azure AD / Entra ID', 'tech'],
            ['Microsoft 365', 'tech'],
            ['Microsoft Intune', 'tech'],
            ['Microsoft Exchange', 'tech'],
            ['Microsoft SharePoint', 'tech'],
            ['Microsoft Teams', 'tech'],
            ['Microsoft Power Platform', 'tech'],
            ['Microsoft Power Automate', 'tech'],
            ['Microsoft Dynamics 365', 'tech'],
            ['Microsoft SQL Server', 'tech'],
            ['Microsoft SCCM / MECM', 'tech'],
            ['Windows Server', 'tech'],
            ['Active Directory', 'tech'],
            ['Group Policy (GPO)', 'tech'],
            ['Hyper-V', 'tech'],

            // Virtualisierung & Infrastruktur
            ['VMware vSphere', 'tech'],
            ['VMware ESXi', 'tech'],
            ['VMware vCenter', 'tech'],
            ['VMware NSX', 'tech'],
            ['VMware vSAN', 'tech'],
            ['VMware Horizon', 'tech'],
            ['Nutanix', 'tech'],
            ['Nutanix AHV', 'tech'],
            ['Nutanix Prism', 'tech'],
            ['Proxmox', 'tech'],
            ['Citrix', 'tech'],
            ['Veeam Backup', 'tech'],

            // Netzwerk & Security
            ['Linux', 'tech'],
            ['Nginx', 'tech'],
            ['Apache', 'tech'],
            ['Palo Alto', 'tech'],
            ['Fortinet / FortiGate', 'tech'],
            ['Cisco', 'tech'],
            ['pfSense / OPNsense', 'tech'],
            ['SIEM / SOC', 'tech'],
            ['Zero Trust', 'tech'],
            ['VPN / IPsec', 'tech'],

            // Datenbanken
            ['MySQL', 'tech'],
            ['PostgreSQL', 'tech'],
            ['MongoDB', 'tech'],
            ['Redis', 'tech'],
            ['Elasticsearch', 'tech'],
            ['MariaDB', 'tech'],
            ['Oracle DB', 'tech'],
            ['SQLite', 'tech'],
            ['Cassandra', 'tech'],

            // API & Architektur
            ['Git', 'tech'],
            ['GraphQL', 'tech'],
            ['REST API', 'tech'],
            ['gRPC', 'tech'],
            ['Microservices', 'tech'],
            ['Event-Driven Architecture', 'tech'],
            ['RabbitMQ', 'tech'],
            ['Apache Kafka', 'tech'],

            // CMS & E-Commerce
            ['WordPress', 'tech'],
            ['Shopware', 'tech'],
            ['Magento', 'tech'],
            ['TYPO3', 'tech'],
            ['Drupal', 'tech'],
            ['WooCommerce', 'tech'],
            ['Contentful', 'tech'],

            // Data & AI
            ['TensorFlow', 'tech'],
            ['PyTorch', 'tech'],
            ['Pandas', 'tech'],
            ['Power BI', 'tech'],
            ['Tableau', 'tech'],
            ['Jupyter', 'tech'],
            ['Apache Spark', 'tech'],
            ['Databricks', 'tech'],

            // Monitoring
            ['Prometheus', 'tech'],
            ['Grafana', 'tech'],
            ['Zabbix', 'tech'],
            ['Nagios', 'tech'],
            ['Datadog', 'tech'],
            ['ELK Stack', 'tech'],

            // SAP
            ['SAP ERP', 'tech'],
            ['SAP S/4HANA', 'tech'],
            ['SAP BW', 'tech'],
            ['SAP Fiori', 'tech'],

            // Sonstiges
            ['Jira', 'tech'],
            ['Confluence', 'tech'],
            ['Slack', 'tech'],

            // ── Persönliche Stärken (soft) ───────────────────────────────
            ['Teamarbeit', 'soft'],
            ['Kommunikation', 'soft'],
            ['Eigeninitiative', 'soft'],
            ['Lernbereitschaft', 'soft'],
            ['Analytisches Denken', 'soft'],
            ['Problemlösung', 'soft'],
            ['Zeitmanagement', 'soft'],
            ['Präsentation', 'soft'],
            ['Agile Methoden', 'soft'],
            ['Scrum', 'soft'],
            ['Kanban', 'soft'],
            ['Führung', 'soft'],
            ['Kundenorientierung', 'soft'],
            ['Konfliktmanagement', 'soft'],
            ['Projektmanagement', 'soft'],
            ['Mentoring', 'soft'],
            ['Kreativität', 'soft'],
            ['Entscheidungsstärke', 'soft'],
            ['Verhandlungsführung', 'soft'],
            ['Interkulturelle Kompetenz', 'soft'],
            ['Stressresistenz', 'soft'],
            ['Selbstorganisation', 'soft'],
            ['Design Thinking', 'soft'],
            ['ITIL', 'soft'],
        ];
    }

    private function seed_default_skill_presets(): void
    {
        $db = CMS\Database::instance();
        try {
            $cnt = (int)$db->get_var("SELECT COUNT(*) FROM {$db->prefix()}expert_skill_presets");
            if ($cnt > 0) {
                return;
            }
            $this->insert_presets($this->get_default_presets());
        } catch (\Throwable $e) {
            error_log('seed_default_skill_presets: ' . $e->getMessage());
        }
    }

    /**
     * Fehlende Presets nachträglich einfügen (INSERT IGNORE).
     * Wird bei jedem Plugin-Init aufgerufen – ist idempotent.
     */
    public function update_skill_presets(): void
    {
        $db = CMS\Database::instance();
        try {
            // Prüfen ob Tabelle existiert
            $tables = $db->getPdo()
                ->query("SHOW TABLES LIKE '{$db->prefix()}expert_skill_presets'")
                ->fetchAll();
            if (empty($tables)) {
                return;
            }
            $this->insert_presets($this->get_default_presets());
        } catch (\Throwable $e) {
            error_log('update_skill_presets: ' . $e->getMessage());
        }
    }

    /**
     * INSERT IGNORE für eine Liste von Presets.
     *
     * @param list<array{0: string, 1: string}> $presets
     */
    private function insert_presets(array $presets): void
    {
        $db     = CMS\Database::instance();
        $pdo    = $db->getPdo();
        $prefix = $db->prefix();
        $stmt   = $pdo->prepare(
            "INSERT IGNORE INTO {$prefix}expert_skill_presets (skill_name, skill_type) VALUES (?, ?)"
        );
        foreach ($presets as [$name, $type]) {
            $stmt->execute([$name, $type]);
        }
    }

    /**
     * Gibt alle Skill-Presets zurück, gruppiert nach Typ
     *
     * @return array{general: list<object>, tech: list<object>, soft: list<object>}
     */
    public function get_skill_presets_grouped(): array
    {
        $db      = CMS\Database::instance();
        $grouped = ['general' => [], 'tech' => [], 'soft' => []];
        try {
            $rows = $db->get_results(
                "SELECT * FROM {$db->prefix()}expert_skill_presets ORDER BY skill_type, skill_name ASC"
            );
            foreach ($rows as $row) {
                $t = in_array($row->skill_type, ['general', 'tech', 'soft'], true)
                    ? $row->skill_type : 'general';
                $grouped[$t][] = $row;
            }
        } catch (\Throwable $e) {
            error_log('get_skill_presets_grouped: ' . $e->getMessage());
        }
        return $grouped;
    }

    public function save_skill_preset(string $name, string $type): int
    {
        $db   = CMS\Database::instance();
        $type = in_array($type, ['general', 'tech', 'soft'], true) ? $type : 'general';
        $name = trim($name);
        if ($name === '') {
            return 0;
        }
        try {
            $result = $db->insert('expert_skill_presets', [
                'skill_name' => $name,
                'skill_type' => $type,
            ]);
            return $result ? (int)$db->getPdo()->lastInsertId() : 0;
        } catch (\Throwable $e) {
            error_log('save_skill_preset: ' . $e->getMessage());
            return 0;
        }
    }

    public function delete_skill_preset(int $id): bool
    {
        $db = CMS\Database::instance();
        try {
            $db->execute("DELETE FROM {$db->prefix()}expert_skill_presets WHERE id = ?", [$id]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ═══ Spezialisierungen ══════════════════════════════════════════════════

    public function delete_specialization(int $id): bool
    {
        $db = CMS\Database::instance();
        try {
            $db->execute("DELETE FROM {$db->prefix()}expert_specializations WHERE id = ?", [$id]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Befüllt Standard-Fachrichtungen für IT-Experten (einmalig)
     */
    private function seed_default_specializations(): void
    {
        try {
            $db  = CMS\Database::instance();
            $cnt = $db->get_var("SELECT COUNT(*) FROM {$db->prefix()}expert_specializations");
            if ((int) $cnt > 0) {
                return; // Bereits befüllt
            }

            $defaults = [
                // Software-Entwicklung
                ['name' => 'Software-Entwicklung',      'slug' => 'software-entwicklung',      'parent' => null],
                ['name' => 'Web-Entwicklung',            'slug' => 'web-entwicklung',            'parent' => 'software-entwicklung'],
                ['name' => 'Mobile-Entwicklung',         'slug' => 'mobile-entwicklung',         'parent' => 'software-entwicklung'],
                ['name' => 'Backend-Entwicklung',        'slug' => 'backend-entwicklung',        'parent' => 'software-entwicklung'],
                ['name' => 'Frontend-Entwicklung',       'slug' => 'frontend-entwicklung',       'parent' => 'software-entwicklung'],
                ['name' => 'Full-Stack-Entwicklung',     'slug' => 'fullstack-entwicklung',      'parent' => 'software-entwicklung'],
                ['name' => 'Embedded Systems',           'slug' => 'embedded-systems',           'parent' => 'software-entwicklung'],
                // IT-Infrastruktur
                ['name' => 'IT-Infrastruktur',           'slug' => 'it-infrastruktur',           'parent' => null],
                ['name' => 'Cloud & DevOps',             'slug' => 'cloud-devops',               'parent' => 'it-infrastruktur'],
                ['name' => 'Netzwerk & Systeme',         'slug' => 'netzwerk-systeme',           'parent' => 'it-infrastruktur'],
                ['name' => 'IT-Security',                'slug' => 'it-security',                'parent' => 'it-infrastruktur'],
                ['name' => 'Virtualisierung',            'slug' => 'virtualisierung',            'parent' => 'it-infrastruktur'],
                // Daten & KI
                ['name' => 'Daten & KI',                 'slug' => 'daten-ki',                   'parent' => null],
                ['name' => 'Data Engineering',           'slug' => 'data-engineering',           'parent' => 'daten-ki'],
                ['name' => 'Data Science',               'slug' => 'data-science',               'parent' => 'daten-ki'],
                ['name' => 'Machine Learning / KI',      'slug' => 'ml-ki',                      'parent' => 'daten-ki'],
                ['name' => 'Business Intelligence',      'slug' => 'business-intelligence',      'parent' => 'daten-ki'],
                // Projekt & Beratung
                ['name' => 'Projektmanagement',          'slug' => 'projektmanagement',          'parent' => null],
                ['name' => 'IT-Beratung',                'slug' => 'it-beratung',                'parent' => 'projektmanagement'],
                ['name' => 'Scrum / Agile',              'slug' => 'scrum-agile',                'parent' => 'projektmanagement'],
                ['name' => 'IT-Architektur',             'slug' => 'it-architektur',             'parent' => 'projektmanagement'],
                // Design & UX
                ['name' => 'UX / UI Design',             'slug' => 'ux-ui-design',               'parent' => null],
                ['name' => 'UI-Entwicklung',             'slug' => 'ui-entwicklung',             'parent' => 'ux-ui-design'],
                ['name' => 'Usability Testing',          'slug' => 'usability-testing',          'parent' => 'ux-ui-design'],
                // IT-Service
                ['name' => 'IT-Service & Support',       'slug' => 'it-service-support',         'parent' => null],
                ['name' => 'IT-Administration',          'slug' => 'it-administration',           'parent' => 'it-service-support'],
                ['name' => 'Helpdesk / 1st-3rd Level',   'slug' => 'helpdesk',                   'parent' => 'it-service-support'],
                // ERP & SAP
                ['name' => 'ERP & SAP',                  'slug' => 'erp-sap',                    'parent' => null],
                ['name' => 'SAP Entwicklung',            'slug' => 'sap-entwicklung',            'parent' => 'erp-sap'],
                ['name' => 'SAP Beratung',               'slug' => 'sap-beratung',               'parent' => 'erp-sap'],
                ['name' => 'Microsoft Dynamics',         'slug' => 'ms-dynamics',                'parent' => 'erp-sap'],
                // Sonstiges
                ['name' => 'Sonstiges',                  'slug' => 'sonstiges',                  'parent' => null],
                ['name' => 'IT-Recht & Compliance',      'slug' => 'it-recht-compliance',        'parent' => 'sonstiges'],
                ['name' => 'IT-Ausbildung & Training',   'slug' => 'it-ausbildung',              'parent' => 'sonstiges'],
            ];

            // Zuerst Top-Level anlegen (für Foreign-Key-Auflösung)
            $slug_to_id = [];
            $two_pass   = [];

            foreach ($defaults as $spec) {
                if ($spec['parent'] === null) {
                    $id = $this->add_specialization($spec['name'], $spec['slug']);
                    if ($id > 0) {
                        $slug_to_id[$spec['slug']] = $id;
                    }
                } else {
                    $two_pass[] = $spec;
                }
            }
            // Zweiter Durchlauf: Children
            foreach ($two_pass as $spec) {
                $parent_id = $slug_to_id[$spec['parent']] ?? null;
                $id = $this->add_specialization($spec['name'], $spec['slug'], null, $parent_id);
                if ($id > 0) {
                    $slug_to_id[$spec['slug']] = $id;
                }
            }
        } catch (\Exception $e) {
            error_log('seed_default_specializations: ' . $e->getMessage());
        }
    }
}
