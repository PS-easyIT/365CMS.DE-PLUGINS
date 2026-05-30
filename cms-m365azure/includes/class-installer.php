<?php
/**
 * CMS M365 Azure – Installer.
 *
 * @package CMS_M365Azure
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365Azure_Installer
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    public static function maybe_install(): void
    {
        self::install();
    }

    public static function install(): void
    {
        if (!class_exists('CMS\\Database')) {
            return;
        }

        $db = \CMS\Database::instance();
        $pdo = $db->getPdo();
        $prefix = self::prefix($db);

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}m365azure_categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(120) NOT NULL UNIQUE,
            title VARCHAR(190) NOT NULL,
            overline VARCHAR(190) DEFAULT NULL,
            intro TEXT DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_active_order (is_active, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}m365azure_services (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id INT UNSIGNED NOT NULL,
            slug VARCHAR(140) NOT NULL UNIQUE,
            title VARCHAR(190) NOT NULL,
            subtitle VARCHAR(255) DEFAULT NULL,
            image_url VARCHAR(500) DEFAULT NULL,
            image_alt VARCHAR(255) DEFAULT NULL,
            summary TEXT DEFAULT NULL,
            content MEDIUMTEXT DEFAULT NULL,
            features MEDIUMTEXT DEFAULT NULL,
            use_cases MEDIUMTEXT DEFAULT NULL,
            docs_url VARCHAR(500) DEFAULT NULL,
            pricing_url VARCHAR(500) DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category_active_order (category_id, is_active, sort_order),
            CONSTRAINT fk_m365azure_services_category FOREIGN KEY (category_id) REFERENCES {$prefix}m365azure_categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}m365azure_settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(120) NOT NULL UNIQUE,
            setting_value MEDIUMTEXT DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::seed_settings($db, $prefix);
        self::seed_content($db, $prefix);
    }

    private static function prefix(object $db): string
    {
        if (method_exists($db, 'getPrefix')) {
            return (string) $db->getPrefix();
        }

        if (method_exists($db, 'prefix')) {
            return (string) $db->prefix();
        }

        return 'cms_';
    }

    private static function seed_settings(object $db, string $prefix): void
    {
        $defaults = [
            'route_slug' => 'azure-services',
            'page_title' => 'Microsoft Azure Services',
            'page_overline' => 'Azure Überblick',
            'page_intro' => 'Microsoft Azure stellt mehr als 200 Cloudprodukte und Dienste bereit – von Compute, Storage und Datenbanken bis zu KI, Sicherheit, Analytics und Hybrid Cloud.',
            'seo_title' => 'Microsoft Azure Services – Übersicht und Kategorien',
            'seo_description' => 'Übersicht der wichtigsten Microsoft Azure Services mit Kategorien, Einsatzbereichen, Links und steuerbarem Card-Layout.',
            'show_hero' => '1',
            'show_toc' => '1',
            'toc_title' => 'Inhaltsverzeichnis',
            'show_category_intro' => '1',
            'show_service_images' => '1',
            'show_service_links' => '1',
            'show_feature_lists' => '1',
            'show_use_cases' => '1',
            'card_image_position' => 'left',
            'layout_max_width' => '1180',
            'card_image_width' => '320',
            'design_primary_color' => '#2563eb',
            'design_accent_color' => '#f59e0b',
            'design_background_color' => '#ffffff',
            'design_surface_color' => '#ffffff',
            'design_border_radius' => '10',
        ];

        $exists = $db->prepare("SELECT id FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $insert = $db->prepare("INSERT INTO {$prefix}m365azure_settings (setting_key, setting_value) VALUES (?, ?)");

        foreach ($defaults as $key => $value) {
            $exists->execute([$key]);
            if (!$exists->fetch()) {
                $insert->execute([$key, $value]);
            }
        }
    }

    private static function seed_content(object $db, string $prefix): void
    {
        $countStmt = $db->prepare("SELECT COUNT(*) FROM {$prefix}m365azure_categories");
        $countStmt->execute();
        if ((int) $countStmt->fetchColumn() > 0) {
            return;
        }

        $categories = [
            ['compute', 'Compute', 'Rechenleistung bedarfsgerecht bereitstellen – von VMs über Kubernetes bis Serverless.', 10],
            ['storage', 'Storage', 'Sichere und skalierbare Speicherlösungen für Objekte, Dateien, Disks und Data Lakes.', 20],
            ['datenbanken', 'Datenbanken', 'Verwaltete relationale, NoSQL- und Cache-Dienste für moderne Anwendungen.', 30],
            ['ki-machine-learning', 'KI & Machine Learning', 'KI-Apps, Agenten, Machine Learning und vorgefertigte KI-APIs entwickeln.', 40],
            ['devops-tools', 'DevOps & Entwickler-Tools', 'Code, Builds, Tests und Deployments effizient planen und automatisieren.', 50],
            ['netzwerk-sicherheit', 'Netzwerk & Sicherheit', 'Cloud- und Hybrid-Netzwerke sicher verbinden, schützen und überwachen.', 60],
            ['integration-kommunikation', 'Integration & Kommunikation', 'APIs, Ereignisse, Messaging und Echtzeitkommunikation verbinden.', 70],
            ['iot-mixed-reality', 'IoT & Mixed Reality', 'Geräteflotten, Edge-Verarbeitung, digitale Zwillinge und immersive Szenarien.', 80],
            ['analytics-big-data', 'Analytics & Big Data', 'Daten integrieren, analysieren, visualisieren und für Entscheidungen nutzbar machen.', 90],
            ['hybrid-multicloud', 'Hybrid & Multicloud', 'Lokale, Edge-, Azure- und Multicloud-Ressourcen zentral steuern.', 100],
            ['management-governance', 'Management & Governance', 'Kosten, Richtlinien, Monitoring, Automatisierung und Migration kontrollieren.', 110],
        ];

        $catStmt = $db->prepare("INSERT INTO {$prefix}m365azure_categories (slug, title, overline, intro, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $catIds = [];
        foreach ($categories as [$slug, $title, $intro, $order]) {
            $catStmt->execute([$slug, $title, 'Azure Kategorie', $intro, $order]);
            $catIds[$slug] = (int) $db->lastInsertId();
        }

        $services = [
            ['compute', 'virtual-machines', 'Virtual Machines', 'Windows- und Linux-VMs in Sekunden bereitstellen.', 'Ideal für Lift-and-Shift, klassische Server-Workloads, Testumgebungen und Spezialsoftware mit Betriebssystemzugriff.', 'Flexible Größen und Images\nWindows und Linux\nSkalierung mit VM Scale Sets', 'Legacy-Anwendungen\nEntwicklungs- und Testsysteme\nRechenintensive Workloads', 'https://learn.microsoft.com/de-de/azure/virtual-machines/', 'https://azure.microsoft.com/de-de/pricing/details/virtual-machines/windows/', 10],
            ['compute', 'azure-kubernetes-service', 'Azure Kubernetes Service (AKS)', 'Verwaltetes Kubernetes für containerisierte Anwendungen.', 'AKS reduziert den Betriebsaufwand für Kubernetes-Cluster und eignet sich für Microservices, Plattform-Teams und skalierende Container-Workloads.', 'Managed Kubernetes\nCluster-Skalierung\nIntegration mit Azure Monitor und Container Registry', 'Microservices\nPlattform Engineering\nCloudnative Anwendungen', 'https://learn.microsoft.com/de-de/azure/aks/', 'https://azure.microsoft.com/de-de/pricing/details/kubernetes-service/', 20],
            ['compute', 'azure-functions', 'Azure Functions', 'Ereignisgesteuerte serverlose Funktionen.', 'Azure Functions führt Code auf Abruf aus, ohne dass Server verwaltet werden müssen – passend für Automatisierung, APIs und Event-Verarbeitung.', 'Serverless Runtime\nTrigger für HTTP, Timer, Queue und Events\nSkalierung nach Bedarf', 'Automatisierung\nWebhook-Backends\nEvent Processing', 'https://learn.microsoft.com/de-de/azure/azure-functions/', 'https://azure.microsoft.com/de-de/pricing/details/functions/', 30],
            ['compute', 'container-apps', 'Azure Container Apps', 'Serverlose Container für Apps und Microservices.', 'Container Apps kombiniert Containerbetrieb mit serverloser Skalierung und eignet sich für APIs, Worker und Dapr-basierte Microservices.', 'Container ohne Clusterbetrieb\nScale-to-zero möglich\nDapr-Integration', 'APIs\nBackground Worker\nEvent-getriebene Microservices', 'https://learn.microsoft.com/de-de/azure/container-apps/', 'https://azure.microsoft.com/de-de/pricing/details/container-apps/', 40],
            ['storage', 'blob-storage', 'Azure Blob Storage', 'Objektspeicher für unstrukturierte Daten.', 'Blob Storage speichert Bilder, Videos, Backups, Logs und Data-Lake-Dateien hochskalierbar und sicher.', 'Hot/Cool/Archive Tiers\nLifecycle Management\nStarke Integration in Analytics und Backup', 'Medienbibliotheken\nBackups\nData Lake Rohdaten', 'https://learn.microsoft.com/de-de/azure/storage/blobs/', 'https://azure.microsoft.com/de-de/pricing/details/storage/blobs/', 10],
            ['storage', 'azure-files', 'Azure Files', 'Serverlose Dateifreigaben über SMB und NFS.', 'Azure Files ersetzt oder erweitert klassische File-Server und lässt sich in Windows-, Linux- und Hybridumgebungen einbinden.', 'SMB/NFS-Freigaben\nAzure File Sync\nIntegration mit Entra-Identitäten', 'File-Server-Ablösung\nLift-and-Shift\nGemeinsame App-Dateien', 'https://learn.microsoft.com/de-de/azure/storage/files/', 'https://azure.microsoft.com/de-de/pricing/details/storage/files/', 20],
            ['storage', 'disk-storage', 'Azure Disk Storage', 'Blockspeicher für virtuelle Maschinen.', 'Managed Disks bieten performanten und dauerhaften Speicher für VM-Workloads – von Standard bis Ultra Disk.', 'Managed Disks\nPremium SSD und Ultra Disk\nSnapshots und Verschlüsselung', 'Datenbank-VMs\nSAP-Workloads\nEnterprise-Anwendungen', 'https://learn.microsoft.com/de-de/azure/virtual-machines/managed-disks-overview', 'https://azure.microsoft.com/de-de/pricing/details/managed-disks/', 30],
            ['datenbanken', 'azure-sql-database', 'Azure SQL-Datenbank', 'Vollständig verwaltete relationale SQL-Datenbank.', 'Azure SQL-Datenbank eignet sich für moderne Apps, die SQL Server-Kompatibilität, hohe Verfügbarkeit und automatische Verwaltung benötigen.', 'Automatische Patches\nHohe Verfügbarkeit\nSkalierbare Leistungsebenen', 'Web-Apps\nGeschäftsanwendungen\nSaaS-Datenbanken', 'https://learn.microsoft.com/de-de/azure/azure-sql/database/', 'https://azure.microsoft.com/de-de/pricing/details/azure-sql-database/single/', 10],
            ['datenbanken', 'cosmos-db', 'Azure Cosmos DB', 'Global verteilte NoSQL-Datenbank.', 'Cosmos DB bietet niedrige Latenz, globale Replikation und mehrere APIs für moderne, verteilte Anwendungen.', 'Globale Verteilung\nMehrere APIs\nVektor- und KI-Szenarien', 'Personalisierung\nIoT-Daten\nGlobale Apps', 'https://learn.microsoft.com/de-de/azure/cosmos-db/', 'https://azure.microsoft.com/de-de/pricing/details/cosmos-db/', 20],
            ['datenbanken', 'postgresql', 'Azure Database for PostgreSQL', 'Verwaltete PostgreSQL-Datenbank.', 'Der Dienst modernisiert PostgreSQL-Workloads mit automatischer Verwaltung, Skalierung und Sicherheitsfunktionen.', 'Flexible Server\nBackups und Hochverfügbarkeit\nOpen-Source-Kompatibilität', 'Web-Backends\nData Apps\nKI-nahe Datenhaltung', 'https://learn.microsoft.com/de-de/azure/postgresql/', 'https://azure.microsoft.com/de-de/pricing/details/postgresql/flexible-server/', 30],
            ['ki-machine-learning', 'azure-ai-foundry', 'Microsoft Foundry', 'Plattform für KI-Apps, Modelle und Agenten.', 'Microsoft Foundry bündelt Modelle, Tools, Sicherheit, Observability und Agentenentwicklung für produktive KI-Lösungen.', 'Modellkatalog\nAgentenentwicklung\nGovernance und Monitoring', 'KI-Agenten\nRAG-Anwendungen\nEnterprise Copilots', 'https://learn.microsoft.com/de-de/azure/ai-foundry/', 'https://azure.microsoft.com/de-de/pricing/details/ai-foundry/', 10],
            ['ki-machine-learning', 'azure-openai', 'Azure OpenAI Service', 'Fortschrittliche Sprach- und Codemodelle in Azure.', 'Azure OpenAI ermöglicht generative KI mit Enterprise-Sicherheit, Datenschutz und Integration in Azure-Datenquellen.', 'GPT-Modelle\nEnterprise-Security\nIntegration in Foundry', 'Chatbots\nContent-Erstellung\nCode-Assistenz', 'https://learn.microsoft.com/de-de/azure/ai-services/openai/', 'https://azure.microsoft.com/de-de/pricing/details/cognitive-services/openai-service/', 20],
            ['ki-machine-learning', 'azure-ai-search', 'Azure AI Search', 'Such- und Retrieval-Schicht für Apps und RAG.', 'Azure AI Search verbindet Datenquellen mit Volltextsuche, Vektorsuche und Retrieval-Augmented-Generation-Szenarien.', 'Vektorsuche\nIndexierung\nRAG-Pipelines', 'Wissenssuche\nDokumentenportale\nCopilot-Datenbasis', 'https://learn.microsoft.com/de-de/azure/search/', 'https://azure.microsoft.com/de-de/pricing/details/search/', 30],
            ['devops-tools', 'azure-devops', 'Azure DevOps', 'Boards, Repos, Pipelines, Tests und Artefakte.', 'Azure DevOps unterstützt Teams bei Planung, Codeverwaltung, CI/CD und Qualitätssicherung.', 'Boards und Repos\nPipelines\nTest Plans und Artifacts', 'CI/CD\nAgile Planung\nEnterprise DevOps', 'https://learn.microsoft.com/de-de/azure/devops/', 'https://azure.microsoft.com/de-de/pricing/details/devops/azure-devops-services/', 10],
            ['devops-tools', 'dev-box', 'Microsoft Dev Box', 'Cloudbasierte Entwicklungsarbeitsplätze.', 'Dev Box stellt vorkonfigurierte, sichere Entwicklungsumgebungen bereit, damit Teams schneller starten und konsistent arbeiten.', 'Ready-to-code Umgebungen\nZentrale Verwaltung\nSkalierbare Entwicklerplätze', 'Onboarding\nStandardisierte Entwicklungsumgebungen\nRemote Development', 'https://learn.microsoft.com/de-de/azure/dev-box/', 'https://azure.microsoft.com/de-de/pricing/details/dev-box/', 20],
            ['netzwerk-sicherheit', 'virtual-network', 'Azure Virtual Network', 'Private Netzwerkgrundlage in Azure.', 'Virtual Network verbindet Ressourcen sicher miteinander und bildet die Basis für Subnetze, Routing, Peering und Hybridkonnektivität.', 'Subnetze und Peering\nPrivate IP-Kommunikation\nNetzwerksicherheitsgruppen', 'Landing Zones\nApp-Netzwerke\nHybrid-Topologien', 'https://learn.microsoft.com/de-de/azure/virtual-network/', 'https://azure.microsoft.com/de-de/pricing/details/virtual-network/', 10],
            ['netzwerk-sicherheit', 'azure-firewall', 'Azure Firewall', 'Cloudnative Netzwerk-Firewall.', 'Azure Firewall schützt virtuelle Netzwerke mit zentralen Regeln, Protokollierung und integrierter Hochverfügbarkeit.', 'Zentrale Policies\nThreat Intelligence\nHochverfügbarkeit', 'Hub-Spoke-Netze\nEgress-Kontrolle\nSegmentierung', 'https://learn.microsoft.com/de-de/azure/firewall/', 'https://azure.microsoft.com/de-de/pricing/details/azure-firewall/', 20],
            ['netzwerk-sicherheit', 'key-vault', 'Azure Key Vault', 'Schlüssel, Zertifikate und Geheimnisse verwalten.', 'Key Vault schützt Secrets und kryptografische Schlüssel und trennt sensible Werte sauber vom Anwendungscode.', 'Secrets und Zertifikate\nManaged HSM Optionen\nRBAC und Auditing', 'App-Secrets\nZertifikatsverwaltung\nSchlüsselrotation', 'https://learn.microsoft.com/de-de/azure/key-vault/', 'https://azure.microsoft.com/de-de/pricing/details/key-vault/', 30],
            ['integration-kommunikation', 'api-management', 'API Management', 'APIs sicher veröffentlichen und verwalten.', 'API Management bietet Gateway, Policies, Developer Portal und Monitoring für interne und externe APIs.', 'API Gateway\nRate Limits und Policies\nDeveloper Portal', 'Partner-APIs\nMicroservice-Gateways\nAPI-Produkte', 'https://learn.microsoft.com/de-de/azure/api-management/', 'https://azure.microsoft.com/de-de/pricing/details/api-management/', 10],
            ['integration-kommunikation', 'logic-apps', 'Logic Apps', 'Designerbasierte Workflows und Integration.', 'Logic Apps automatisiert Geschäftsprozesse und verbindet Cloud- und On-Premises-Systeme über zahlreiche Connectoren.', 'Visuelle Workflows\nViele Connectoren\nB2B- und Enterprise-Integration', 'Genehmigungsprozesse\nDaten-Synchronisation\nSystemintegration', 'https://learn.microsoft.com/de-de/azure/logic-apps/', 'https://azure.microsoft.com/de-de/pricing/details/logic-apps/', 20],
            ['integration-kommunikation', 'service-bus', 'Service Bus', 'Enterprise Messaging zwischen Systemen.', 'Service Bus entkoppelt Anwendungen über Queues und Topics und sorgt für zuverlässige asynchrone Kommunikation.', 'Queues und Topics\nDead-Lettering\nTransaktionen', 'Auftragsverarbeitung\nSystementkopplung\nEnterprise Messaging', 'https://learn.microsoft.com/de-de/azure/service-bus-messaging/', 'https://azure.microsoft.com/de-de/pricing/details/service-bus/', 30],
            ['iot-mixed-reality', 'iot-hub', 'Azure IoT Hub', 'Geräte sicher verbinden und verwalten.', 'IoT Hub ist die zentrale Plattform für bidirektionale Kommunikation mit IoT-Geräten und Gerätemanagement.', 'Geräteidentitäten\nCloud-to-device Messaging\nMonitoring', 'Industrie 4.0\nTelemetrie\nGeräteflotten', 'https://learn.microsoft.com/de-de/azure/iot-hub/', 'https://azure.microsoft.com/de-de/pricing/details/iot-hub/', 10],
            ['iot-mixed-reality', 'digital-twins', 'Azure Digital Twins', 'Digitale Abbilder realer Umgebungen.', 'Digital Twins modelliert Gebäude, Anlagen, Prozesse und Beziehungen, um Zustände und Simulationen nutzbar zu machen.', 'Graphbasierte Modelle\nLive-Daten-Integration\nRaum- und Anlagenmodellierung', 'Smart Buildings\nFertigung\nFacility Management', 'https://learn.microsoft.com/de-de/azure/digital-twins/', 'https://azure.microsoft.com/de-de/pricing/details/digital-twins/', 20],
            ['analytics-big-data', 'synapse-analytics', 'Azure Synapse Analytics', 'Analytics-Plattform für Data Warehousing und Big Data.', 'Synapse verbindet Data Warehousing, Spark, Pipelines und Analysefunktionen für End-to-End-Datenplattformen.', 'SQL und Spark\nData Integration\nEnterprise Analytics', 'Data Warehousing\nBI-Plattformen\nBig-Data-Auswertung', 'https://learn.microsoft.com/de-de/azure/synapse-analytics/', 'https://azure.microsoft.com/de-de/pricing/details/synapse-analytics/', 10],
            ['analytics-big-data', 'data-factory', 'Azure Data Factory', 'Datenintegration und Pipeline-Orchestrierung.', 'Data Factory verschiebt und transformiert Daten aus vielen Quellen und automatisiert Datenpipelines.', 'Pipelines\nConnectoren\nMonitoring', 'ETL/ELT\nMigration\nDatenplattformen', 'https://learn.microsoft.com/de-de/azure/data-factory/', 'https://azure.microsoft.com/de-de/pricing/details/data-factory/', 20],
            ['analytics-big-data', 'databricks', 'Azure Databricks', 'Apache Spark-basierte Daten- und KI-Plattform.', 'Azure Databricks unterstützt kollaborative Datenentwicklung, Lakehouse-Architekturen und ML/AI-Workloads.', 'Spark-Plattform\nLakehouse\nML-Workflows', 'Data Engineering\nKI-Training\nStreaming Analytics', 'https://learn.microsoft.com/de-de/azure/databricks/', 'https://azure.microsoft.com/de-de/pricing/details/databricks/', 30],
            ['hybrid-multicloud', 'azure-arc', 'Azure Arc', 'Azure-Management für hybride und Multicloud-Ressourcen.', 'Azure Arc erweitert Azure-Management, Governance und Sicherheit auf lokale Server, Kubernetes und andere Clouds.', 'Server und Kubernetes überall verwalten\nPolicy und Governance\nHybrid Operations', 'Hybrid Cloud\nMulticloud Governance\nEdge-Standorte', 'https://learn.microsoft.com/de-de/azure/azure-arc/', 'https://azure.microsoft.com/de-de/pricing/details/azure-arc/', 10],
            ['hybrid-multicloud', 'azure-local', 'Azure Local', 'Azure-nahe Infrastruktur an verteilten Standorten.', 'Azure Local bringt Azure-Funktionen in lokale und Edge-Umgebungen für Workloads mit Standort- oder Latenzanforderungen.', 'Lokale Workloads\nCloudverbundene Verwaltung\nEdge-Szenarien', 'Filialen\nIndustrie-Edge\nRegulierte Workloads', 'https://learn.microsoft.com/de-de/azure/azure-local/', 'https://azure.microsoft.com/de-de/pricing/details/azure-local/', 20],
            ['management-governance', 'azure-monitor', 'Azure Monitor', 'Monitoring für Anwendungen, Infrastruktur und Netzwerke.', 'Azure Monitor sammelt Metriken, Logs und Traces und macht Betrieb, Verfügbarkeit und Performance sichtbar.', 'Metriken und Logs\nAlerts\nApplication Insights', 'Betriebsmonitoring\nPerformance-Analyse\nSLA-Überwachung', 'https://learn.microsoft.com/de-de/azure/azure-monitor/', 'https://azure.microsoft.com/de-de/pricing/details/monitor/', 10],
            ['management-governance', 'azure-policy', 'Azure Policy', 'Governance und Standards automatisch durchsetzen.', 'Azure Policy prüft und erzwingt Regeln für Ressourcen, um Compliance und Architekturstandards sicherzustellen.', 'Policy-Definitionen\nCompliance-Auswertung\nRemediation', 'Landing Zones\nCompliance\nGovernance', 'https://learn.microsoft.com/de-de/azure/governance/policy/', 'https://azure.microsoft.com/de-de/pricing/details/azure-policy/', 20],
            ['management-governance', 'cost-management', 'Microsoft Cost Management', 'Cloudkosten überwachen und optimieren.', 'Cost Management schafft Transparenz über Budgets, Kostenstellen und Optimierungspotenziale in Azure.', 'Budgets\nKostenanalyse\nExports und Empfehlungen', 'FinOps\nKostenkontrolle\nBudgetüberwachung', 'https://learn.microsoft.com/de-de/azure/cost-management-billing/cost-management-billing-overview', 'https://azure.microsoft.com/de-de/products/cost-management/', 30],
        ];

        $svcStmt = $db->prepare("INSERT INTO {$prefix}m365azure_services (category_id, slug, title, subtitle, summary, content, features, use_cases, docs_url, pricing_url, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        foreach ($services as [$catSlug, $slug, $title, $subtitle, $summary, $features, $useCases, $docsUrl, $pricingUrl, $order]) {
            if (!isset($catIds[$catSlug])) {
                continue;
            }
            $svcStmt->execute([$catIds[$catSlug], $slug, $title, $subtitle, $summary, $summary, $features, $useCases, $docsUrl, $pricingUrl, $order]);
        }
    }
}
