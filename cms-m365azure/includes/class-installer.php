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
            gallery_images MEDIUMTEXT DEFAULT NULL,
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

        self::upgrade_category_gallery_schema($db, $prefix);
        self::seed_settings($db, $prefix);
        self::seed_content($db, $prefix);
        self::upgrade_compute_content($db, $prefix);
        self::upgrade_compute_description_mapping($db, $prefix);
        self::upgrade_storage_content($db, $prefix);
        self::upgrade_database_content($db, $prefix);
        self::upgrade_ai_ml_content($db, $prefix);
        self::upgrade_devops_content($db, $prefix);
        self::upgrade_network_security_content($db, $prefix);
        self::upgrade_integration_communication_content($db, $prefix);
        self::upgrade_iot_mixed_reality_content($db, $prefix);
        self::upgrade_analytics_big_data_content($db, $prefix);
        self::upgrade_hybrid_multicloud_content($db, $prefix);
        self::upgrade_management_governance_content($db, $prefix);
        if (class_exists('CMS_M365Azure_Catalog_Expansion')) {
            CMS_M365Azure_Catalog_Expansion::apply($db, $prefix);
        }
        self::normalize_literal_newlines($db, $prefix);
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
            'hero_primary_button_text' => 'M365 Lizenzmatrix öffnen',
            'hero_primary_button_url' => '/m365-lizenzmatrix',
            'hero_secondary_button_text' => 'M365 AddOn-Übersicht öffnen',
            'hero_secondary_button_url' => '/m365-addon-matrix',
            'hero_cta_button_text' => 'Azure-Beratung anfragen',
            'hero_cta_button_url' => '/kontakt',
            'table_service_label' => 'Dienst',
            'table_description_label' => 'Beschreibung',
            'table_features_label' => 'Wichtige Hinweise',
            'table_use_cases_label' => 'Typische Einsatzszenarien',
            'table_links_label' => 'Links',
            'docs_link_label' => 'Dokumentation',
            'pricing_link_label' => 'Preise',
            'empty_value_label' => '—',
            'note_title' => 'Hinweise zu Azure Services',
            'note_items' => "Die Übersicht nutzt die administrativ gepflegten Diensttexte, Hinweise, Einsatzszenarien und Links aus CMS M365 Azure.\nVerfügbarkeit, Preise und technische Voraussetzungen bitte vor Projektstart über die hinterlegten Microsoft-Links prüfen.",
            'source_title' => 'Quellenstand',
            'source_intro' => 'Die Quellenliste basiert auf den aktuell hinterlegten Dokumentations- und Preislinks der angezeigten Azure-Dienste.',
            'source_details_label' => 'Quellen anzeigen',
            'show_hero' => '1',
            'show_hero_actions' => '1',
            'show_toc' => '1',
            'toc_title' => 'Inhaltsverzeichnis',
            'toc_columns' => '3',
            'toc_nowrap' => '1',
            'show_category_intro' => '1',
            'show_service_images' => '1',
            'show_service_subtitles' => '1',
            'show_description' => '1',
            'show_service_links' => '1',
            'show_feature_lists' => '1',
            'show_use_cases' => '1',
            'show_notes_section' => '1',
            'show_info_note' => '1',
            'show_sources_card' => '1',
            'card_image_position' => 'left',
            'layout_max_width' => '1180',
            'layout_padding_x' => '0',
            'layout_padding_top' => '25',
            'card_image_width' => '72',
            'design_primary_color' => '#2563eb',
            'design_accent_color' => '#f59e0b',
            'design_background_color' => '#ffffff',
            'design_surface_color' => '#ffffff',
            'design_text_color' => '#1e293b',
            'design_muted_color' => '#64748b',
            'design_border_color' => '#e2e8f0',
            'design_border_radius' => '10',
            'design_toc_font_size' => '13',
            'design_table_font_size' => '14',
            'design_link_font_size' => '12',
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
            ['compute', 'virtual-machines', 'Virtual Machines', 'Windows- und Linux-Server mit voller Betriebssystemkontrolle.', 'Azure Virtual Machines stellt skalierbare Windows- und Linux-Server bereit, wenn du Betriebssystem, Software und Infrastrukturdetails selbst steuern musst.', 'Azure Virtual Machines stellt skalierbare Windows- und Linux-Server bereit, wenn du Betriebssystem, installierte Software oder spezielle VM-Größen selbst steuern musst. Du wählst Region, VM-Familie, Datenträger, Netzwerk und Verfügbarkeitsmodell passend zur Workload. Der Dienst ist sinnvoll für Migrationen und Spezialsoftware, bringt aber weiterhin Verantwortung für Patches, OS-Härtung, Backup und Betrieb mit.', 'VM-Größen sind je Region und Zone unterschiedlich verfügbar; SKU, Kontingent und tatsächliche Kapazität vor Projektstart prüfen\nManaged Disks, Public IPs, Bandbreite/Egress, Backups und Lizenzen separat kalkulieren\nFür produktive Systeme Availability Zones, Availability Sets, VM Scale Sets oder Site Recovery bewusst planen\nTemporärer lokaler Speicher ist nicht dauerhaft und eignet sich nur für Cache oder temporäre Daten\nVM-Größenfamilie nach Workload wählen: General Purpose, Compute, Memory, Storage, GPU oder HPC', 'Lift-and-Shift bestehender Server und Fachanwendungen\nWindows- oder Linux-Workloads mit OS-Zugriff\nDatenbank-, SAP-, GPU- oder HPC-nahe Spezialworkloads\nEntwicklungs-, Test- und Schulungsumgebungen', 'https://learn.microsoft.com/de-de/azure/virtual-machines/overview', 'https://azure.microsoft.com/de-de/pricing/details/virtual-machines/windows/', 10],
            ['compute', 'azure-kubernetes-service', 'Azure Kubernetes Service (AKS)', 'Managed Kubernetes für produktive Containerplattformen.', 'AKS ist Microsofts verwalteter Kubernetes-Dienst für containerisierte Anwendungen und Plattformen.', 'AKS ist Microsofts verwalteter Kubernetes-Dienst für containerisierte Anwendungen und Plattformen. Azure übernimmt Control-Plane-Betrieb, Wartung und Integrationen, während du Workloads, Knotenpools, Netzwerk, Identität, Richtlinien und Release-Prozesse steuerst. AKS passt, wenn dein Team Kubernetes-Funktionen, Portabilität und klare Plattformstandards braucht, statt nur einen einfachen Container-Host.', 'Free eher für Tests ohne SLA; Standard für produktive Workloads mit SLA; Premium für Long-Term Support planen\nKosten entstehen vor allem durch Knoten-VMs, Storage, Netzwerk, Cluster-Tier und ggf. AKS Automatic\nKubernetes-Minor-Versionen können beim Upgrade nicht übersprungen werden\nVor Upgrades Compute-Quota und verfügbare Zielversionen prüfen\nAzure Linux 2.0 Knotenimages nicht neu einplanen; Migration auf unterstützte Versionen oder AzureLinux3 vorbereiten', 'Microservices- und Plattform-Engineering-Umgebungen\nModernisierung containerisierter Bestandsanwendungen\nCI/CD- und GitOps-basierte Deployments\nWindows- und Linux-Container in einem Kubernetes-Betriebsmodell', 'https://learn.microsoft.com/de-de/azure/aks/what-is-aks', 'https://azure.microsoft.com/de-de/pricing/details/kubernetes-service/', 20],
            ['compute', 'azure-functions', 'Azure Functions', 'Event-getriebener Code ohne eigenen Serverbetrieb.', 'Azure Functions führt ereignisgesteuerten Code aus, ohne dass du eigene Server betreiben musst.', 'Azure Functions ist eine serverlose Lösung für kleine, ereignisgesteuerte Codeeinheiten, die über Trigger und Bindings mit HTTP, Timern, Storage, Queues, Event Hubs, Service Bus und weiteren Diensten verbunden werden. Du konzentrierst dich auf die Geschäftslogik; Azure übernimmt Hosting, Skalierung und Laufzeitumgebung. Der passende Hostingplan entscheidet über Kaltstart, Netzwerkzugriff, Timeout, Skalierung und Abrechnung.', 'Flex Consumption für neue serverlose Apps bevorzugen; klassischer Consumption-Plan ist veraltet beziehungsweise eingeschränkt\nLinux Consumption wird am 30. September 2028 eingestellt; Functions v3 auf Linux Consumption läuft nach dem 30. September 2026 nicht mehr\nHTTP-getriggerte Funktionen haben ein Antwortlimit von 230 Sekunden; lange Verarbeitung asynchron auslagern\nPlanwahl beeinflusst Kaltstart, VNet, Timeout, Skalierung, Slots und Kostenmodell\nStorage Account, Monitoring, Ausführungen, GB-Sekunden und Always-ready Instanzen in der Kalkulation berücksichtigen', 'Webhooks und leichte APIs\nZeitgesteuerte Automatisierung und Datenbereinigung\nQueue-, Event-Hub- und Service-Bus-Verarbeitung\nServerlose Workflows mit Durable Functions', 'https://learn.microsoft.com/de-de/azure/azure-functions/functions-overview', 'https://azure.microsoft.com/de-de/pricing/details/functions/', 30],
            ['compute', 'container-apps', 'Azure Container Apps', 'Serverlose Container ohne eigenen Kubernetes-Betrieb.', 'Azure Container Apps betreibt containerisierte APIs, Worker, Jobs und Microservices serverlos.', 'Azure Container Apps ist eine serverlose Plattform für containerisierte APIs, Worker, Jobs und Microservices. Du bringst Containerimages mit; Azure übernimmt viele Infrastruktur-, Ingress-, Revisions-, Skalierungs- und Betriebsdetails. Der Dienst basiert auf Kubernetes-nahen Konzepten und Open-Source-Technologien wie KEDA, Dapr und Envoy, ohne dass du die Kubernetes-API direkt betreibst.', 'Skalierung über HTTP, TCP oder KEDA; Scale-to-zero ist möglich, aber nicht bei CPU-/Memory-basierten Regeln\nOhne Ingress brauchst du minReplicas ab 1 oder eine eigene Skalierungsregel, sonst kann die App auf null bleiben\nKein direkter Kubernetes-API-Zugriff; für vollständige Clusterkontrolle AKS prüfen\nRevisionen, Traffic-Splitting, Secrets, Managed Identity, Registry, VNet und Logging früh planen\nKosten entstehen je nach Plan durch aktive Ressourcen, Leerlaufreplikate, Anforderungen und ggf. Dedicated Workload Profiles', 'APIs und Web-Backends als Container\nBackground Worker und ereignisgetriebene Verarbeitung\nMicroservices mit Dapr und Service Discovery\nScheduled, manuelle oder eventbasierte Container Apps Jobs', 'https://learn.microsoft.com/de-de/azure/container-apps/overview', 'https://azure.microsoft.com/de-de/pricing/details/container-apps/', 40],
            ['storage', 'blob-storage', 'Azure Blob Storage', 'Objektspeicher für unstrukturierte Daten.', 'Azure Blob Storage speichert große Mengen unstrukturierter Daten wie Medien, Dokumente, Backups, Logs und Data-Lake-Rohdaten.', 'Azure Blob Storage ist Microsofts hochskalierbarer Objektspeicher für unstrukturierte Text- und Binärdaten. Der Dienst eignet sich für Browser-ausgelieferte Medien, verteilten Dateizugriff, Streaming, Logdaten, Backup, Archivierung und Analytics-Daten. Zugriff ist per HTTP/HTTPS, REST, SDKs, SFTP oder NFS 3.0 möglich; mit Data Lake Storage Gen2 kann Blob Storage auch als Big-Data-Dateisystem genutzt werden.', 'Zugriffsebenen Hot, Cool, Cold, Archive und Smart Tier passend zu Nutzung und Aufbewahrung wählen\nCool, Cold und Archive haben niedrigere Speicherkosten, aber höhere Zugriffs-/Abrufkosten und Mindestaufbewahrungen\nArchive ist offline; Rehydration auf eine Online-Ebene kann bis zu 15 Stunden dauern\nLifecycle Management verschiebt oder löscht Blobs regelbasiert nach Erstellungs-, Änderungs- oder Zugriffszeit\nSchutzoptionen wie Soft Delete, Versioning, Snapshots, Point-in-Time Restore, Immutability und Azure Backup einplanen', 'Medien- und Dokumentenbibliotheken für Websites oder Portale\nBackup, Disaster Recovery und langfristige Archivierung\nData-Lake-Rohdaten für Analytics- und KI-Plattformen\nLog-, Telemetrie- und Exportdaten aus Anwendungen\nSFTP- oder NFS-basierter Datenaustausch über Storage Accounts', 'https://learn.microsoft.com/de-de/azure/storage/blobs/storage-blobs-overview', 'https://azure.microsoft.com/de-de/pricing/details/storage/blobs/', 10],
            ['storage', 'azure-files', 'Azure Files', 'Serverlose Dateifreigaben über SMB und NFS.', 'Azure Files stellt vollständig verwaltete Dateifreigaben bereit, die Windows-, Linux- und macOS-Clients gleichzeitig nutzen können.', 'Azure Files bietet serverlose Dateifreigaben in Azure, die über SMB, NFS und die Azure Files REST API erreichbar sind. Der Dienst kann klassische File-Server oder NAS-Systeme ersetzen, Hybrid-Szenarien mit Azure File Sync unterstützen und Lift-and-Shift-Anwendungen einen vertrauten Dateipfad bereitstellen. Je nach Workload wählst du SMB oder NFS, SSD oder HDD, Redundanz, Identität, Netzwerkzugriff und Abrechnungsmodell.', 'SMB- und NFS-Protokolle verfügbar; eine einzelne Freigabe unterstützt nicht beide Protokolle gleichzeitig\nSMB unterstützt identitätsbasierte Authentifizierung über AD DS, Microsoft Entra Domain Services oder Microsoft Entra Kerberos\nPort 445 und NFS-Netzwerkzugriff früh prüfen; für On-Prem-Zugriff oft VPN, ExpressRoute oder Private Endpoint nötig\nSSD für niedrige Latenz und I/O-intensive Workloads, HDD für kostengünstige allgemeine Dateifreigaben\nAzure File Sync kann SMB-Freigaben zentralisieren und lokale Windows Server als Cache nutzen', 'Ersatz oder Ergänzung lokaler File-Server und NAS-Systeme\nLift-and-Shift-Anwendungen mit gemeinsamem Dateispeicher\nFSLogix-Profile und Benutzerdateien in Azure Virtual Desktop\nGemeinsame Konfigurations-, Diagnose- und Tool-Freigaben für Cloud-Apps\nHybrid-Standorte mit lokalem Cache über Azure File Sync', 'https://learn.microsoft.com/de-de/azure/storage/files/storage-files-introduction', 'https://azure.microsoft.com/de-de/pricing/details/storage/files/', 20],
            ['storage', 'disk-storage', 'Azure Disk Storage', 'Blockspeicher für virtuelle Maschinen.', 'Azure Disk Storage stellt verwalteten Blockspeicher für Azure-VMs bereit – von kostengünstigen Standard-Datenträgern bis Ultra Disk.', 'Azure Managed Disks sind von Azure verwaltete Blockspeichervolumes für virtuelle Maschinen. Du wählst Datenträgertyp, Größe, Performance, Redundanz und Verschlüsselungsoptionen; Azure übernimmt Bereitstellung, Replikation und Integration in VM-Verfügbarkeit. Je nach Workload stehen Ultra Disk, Premium SSD v2, Premium SSD, Standard SSD und Standard HDD für Daten-, OS- und Spezialworkloads zur Verfügung.', 'Fünf Datenträgertypen: Ultra Disk, Premium SSD v2, Premium SSD, Standard SSD und Standard HDD\nUltra Disk und Premium SSD v2 erlauben getrennte Anpassung von Kapazität, IOPS und Durchsatz, sind aber nicht als OS-Datenträger nutzbar\nManaged Disks nutzen standardmäßig serverseitige Verschlüsselung mit AES-256; kundenseitig verwaltete Schlüssel und Hostverschlüsselung sind möglich\nSnapshots, Images, Azure Backup, Wiederherstellungspunkte und Azure Site Recovery für Backup/DR planen\nKosten hängen von Typ, bereitgestellter Größe, IOPS/Durchsatz, Snapshots, Transaktionen, Shared Disks und Egress ab', 'Datenbank-VMs mit SQL Server, Oracle, SAP HANA oder MongoDB\nPersistente Datenlaufwerke für geschäftskritische IaaS-Anwendungen\nCluster-Szenarien mit Shared Disks und Failover-Software\nDev/Test-, Web- und wenig genutzte Workloads mit Standard SSD oder HDD\nHochleistungs-Blockstorage für transaktionsintensive Workloads', 'https://learn.microsoft.com/de-de/azure/virtual-machines/managed-disks-overview', 'https://azure.microsoft.com/de-de/pricing/details/managed-disks/', 30],
            ['datenbanken', 'azure-sql-database', 'Azure SQL-Datenbank', 'Vollständig verwaltete relationale SQL-Datenbank.', 'Azure SQL-Datenbank ist eine vollständig verwaltete PaaS-Datenbank für moderne Anwendungen, die SQL Server-Kompatibilität, automatische Wartung und integrierte Hochverfügbarkeit brauchen.', 'Azure SQL-Datenbank ist eine vollständig verwaltete relationale PaaS-Datenbank auf Basis der SQL Server Engine. Microsoft übernimmt Patching, Backups, Hochverfügbarkeit, Monitoring-Grundlagen und Plattformwartung, während du Schema, Datenmodell, Sicherheit, Performance und Kosten steuerst. Für neue Workloads sind vCore-Modelle mit General Purpose, Business Critical und Hyperscale relevant; Single Databases, Elastic Pools und Serverless oder Provisioned Compute müssen passend zu Lastprofil und Mandantenmodell gewählt werden.', 'vCore-Modell empfohlen; DTU nur bei einfachen vorkonfigurierten Ressourcenkategorien prüfen\nHyperscale ist für viele Business-Workloads die empfohlene Ebene und skaliert Speicher deutlich größer als klassische Ebenen\nServerless eignet sich für variable Einzel-Datenbanken; Provisioned Compute für planbare Dauerlast\nZonenredundanz, Failovergruppen, aktive Georeplikation und Point-in-Time Restore früh nach RTO/RPO planen\nCompute, Speicher, Backup-Aufbewahrung, Replikate, Egress und ggf. Lizenzvorteile separat kalkulieren', 'Cloudnative Web- und Geschäftsanwendungen mit relationalem Datenmodell\nSaaS-Anwendungen mit Single Databases oder Elastic Pools\nSQL Server Modernisierung ohne eigenen Serverbetrieb\nTransaktionssysteme mit hohen Verfügbarkeits- und Sicherheitsanforderungen\nRead-Scale-, Reporting- und Geo-DR-Szenarien mit Replikaten', 'https://learn.microsoft.com/de-de/azure/azure-sql/database/', 'https://azure.microsoft.com/de-de/pricing/details/azure-sql-database/single/', 10],
            ['datenbanken', 'cosmos-db', 'Azure Cosmos DB', 'Global verteilte NoSQL- und Vektordatenbank.', 'Azure Cosmos DB ist eine vollständig verwaltete, global verteilbare NoSQL- und Vektordatenbank für Anwendungen mit niedriger Latenz, elastischer Skalierung und flexiblen Datenmodellen.', 'Azure Cosmos DB unterstützt Betriebsdatenmodelle wie Dokument, Schlüsselwert, Graph, Tabelle und Vektor sowie APIs wie NoSQL, MongoDB, Cassandra, Gremlin und Table. Der Dienst ist auf niedrige Latenz, globale Verteilung, automatische Indizierung, Multi-Region-Lesen und -Schreiben sowie skalierbare Durchsatzmodelle ausgelegt. Kosten und Architektur hängen stark von Partitionierung, Request Units, Konsistenzmodell, Regionen, Speicher, Sicherung und gewähltem Compute- oder Durchsatzmodell ab.', 'RU/s sind zentrale Kapazitäts- und Kosteneinheit; Itemgröße, Indexierung, Abfragen und Konsistenz beeinflussen Verbrauch\nProvisioned Throughput, Autoscale und Serverless bewusst nach Lastprofil wählen\nGlobale Verteilung repliziert Durchsatz und Speicher pro Region; Multi-Region-Write erhöht Verfügbarkeit und Kosten\nPartitionsschlüssel früh sauber modellieren, weil er Skalierung, Hot Partitions und Abfragekosten prägt\nNicht ideal für stark relationale OLTP-Modelle oder klassische OLAP-Analysen; dafür eher Azure SQL oder Analytics-Plattform prüfen', 'Globale Web-, Commerce-, Gaming- und Personalisierungsanwendungen\nIoT-/Telemetrie-Daten mit hohem Schreibdurchsatz\nKI-/RAG-Anwendungen mit operativen Daten und Vektorsuche\nEvent-getriebene Architekturen mit Change Feed\nHochverfügbare Anwendungen mit Multi-Region-Lesen oder -Schreiben', 'https://learn.microsoft.com/de-de/azure/cosmos-db/', 'https://azure.microsoft.com/de-de/pricing/details/cosmos-db/', 20],
            ['datenbanken', 'postgresql', 'Azure Database for PostgreSQL', 'Verwalteter PostgreSQL Flexible Server.', 'Azure Database for PostgreSQL stellt PostgreSQL als vollständig verwalteten Flexible Server bereit, mit Kontrolle über Compute, Speicher, Wartungsfenster, Hochverfügbarkeit und Sicherheit.', 'Azure Database for PostgreSQL – Flexible Server ist Microsofts verwalteter PostgreSQL-Dienst auf Basis der Community-Version. Du behältst PostgreSQL-Kompatibilität, Konfigurationsparameter und Erweiterungen, während Azure Patching, automatische Backups, Verschlüsselung, Monitoring-Integration und Betriebsfunktionen bereitstellt. Für produktive Workloads sollten Compute-Tier, Speicher/IOPS, Wartungsfenster, private Netzwerkanbindung, HA-Modell und Backup-/DR-Strategie bewusst festgelegt werden.', 'Flexible Server ist der relevante Standard; Single-Server-Workloads auf Flexible Server migrieren\nCompute-Tiers Burstable, General Purpose und Memory Optimized passend zur Last wählen\nBurstable eher für Dev/Test oder unregelmäßige geringe Last; für 24/7-Produktion General Purpose oder Memory Optimized bevorzugen\nZonenredundante HA benötigt General Purpose oder Memory Optimized und erzeugt Kosten für Standby-Ressourcen\nAutomatische Backups standardmäßig 7 Tage, bis 35 Tage konfigurierbar; langfristige Sicherung und Geo-DR separat planen', 'PostgreSQL-Web-Backends und Fachanwendungen\nModernisierung bestehender PostgreSQL-Workloads aus On-Premises, VM oder anderen Clouds\nDatenbank für Open-Source-Stacks, PHP-, Python-, Node.js- und .NET-Anwendungen\nProduktionsdatenbanken mit privaten Netzwerken, HA und automatischen Backups\nKI-nahe Anwendungen mit PostgreSQL-Erweiterungen, Vektor- oder Suchszenarien', 'https://learn.microsoft.com/de-de/azure/postgresql/flexible-server/overview', 'https://azure.microsoft.com/de-de/pricing/details/postgresql/flexible-server/', 30],
            ['ki-machine-learning', 'azure-ai-foundry', 'Microsoft Foundry', 'Plattform für KI-Apps, Modelle, Agents und Governance.', 'Microsoft Foundry ist die Azure-Plattform zum Erstellen, Evaluieren, Bereitstellen und Betreiben generativer KI-Apps, Agenten und Modelllösungen mit Modellkatalog, Tools, Observability und Governance.', 'Microsoft Foundry bündelt Modellkatalog, Azure OpenAI-/Foundry-Modelle, Agent Service, Foundry Tools, Evaluation, Tracing/Observability und Guardrails in Projekten unter einer Foundry-Ressource. Teams können Prompts, RAG, Agents und Modellbereitstellungen entwickeln, überwachen und governancenah betreiben. Die Plattform ist kein einzelner Pauschaldienst: Kosten, Verfügbarkeit und Datenverarbeitung hängen von den verwendeten Modellen, Deploymenttypen, Tools, Regionen und abhängigen Azure-Diensten ab.', 'Neues Ressourcenmodell mit Foundry-Ressource und Projekten; ältere Azure AI Foundry/Azure AI Studio-Bezeichnungen können noch in Doku und Portalen auftauchen\nModell-, Agent- und Tool-Verfügbarkeit variiert je Region, Modell, Deploymenttyp und Kontingent; Zielregion vor Produktivstart prüfen\nKosten entstehen über genutzte Modelle, Azure OpenAI, Foundry Tools, Agenten, Evaluation, Monitoring und abhängige Ressourcen; Preisrechner dienstweise verwenden\nFoundry-RBAC-Rollen wurden umbenannt; Rollen-IDs und Kernberechtigungen bleiben laut Microsoft erhalten\nUpgrade von Azure OpenAI auf Foundry ist opt-in und behält Endpunkt, Keys und Konfigurationen, hat aber Einschränkungen bei CMK, Private Link/DNS und Feature-/Regionverfügbarkeit', 'Enterprise-Copilots und KI-Agenten mit Governance\nRAG-Anwendungen mit Evaluierung und Observability\nModellkatalog-, Prompt- und Deployment-Management\nKI-Plattform für mehrere Teams, Projekte und Kostenstellen\nPrototyping bis Produktionsbetrieb generativer KI-Lösungen', 'https://learn.microsoft.com/de-de/azure/foundry/what-is-foundry', 'https://azure.microsoft.com/de-de/pricing/details/microsoft-foundry/', 10],
            ['ki-machine-learning', 'azure-openai', 'Azure OpenAI Service', 'Generative OpenAI-Modelle mit Azure-Governance.', 'Azure OpenAI Service stellt OpenAI-Modelle in Azure bereit – von Chat, Reasoning, Embeddings und Multimodalität bis Audio, Bild/Video und agentsnahe APIs – mit Enterprise-Sicherheit, Quotas und Azure-Integration.', 'Azure OpenAI Service beziehungsweise Azure OpenAI in Microsoft Foundry Models bietet Zugriff auf von Azure gehostete OpenAI-Modelle für Text, Code, Reasoning, Embeddings, Bild, Audio und Realtime-Szenarien. Modelle werden als Deployments bereitgestellt; Bereitstellungstypen wie Global Standard, Data Zone, Regional, Provisioned und Batch bestimmen Datenverarbeitung, Latenz, Durchsatz und Kosten. Für produktive Lösungen sind Modellversionen, Content Filter, Quotas, Rate Limits, Datenzonen, Monitoring und Kostensteuerung zentrale Architekturentscheidungen.', 'Modellverfügbarkeit, Kontextlängen und Features variieren nach Region, Deploymenttyp und Modellversion; Vorschau-Modelle nicht ungeprüft produktiv nutzen\nTPM/RPM-Kontingente gelten pro Abonnement, Region, Modell und Deploymenttyp; 429-Fehler trotz scheinbar freiem Tokenbudget einplanen\nDeploymenttypen Global, Data Zone, Regional, Provisioned und Batch unterscheiden Datenverarbeitung, SLA, Latenz, Durchsatz und Preis\nContent Filtering, Prompt Shields, geschütztes Material, PII- und Abuse-Monitoring in App-Design und Fehlerbehandlung berücksichtigen\nKosten entstehen token-, batch-, PTU-, Fine-Tuning-, Tool- oder Audio/Bild/Video-spezifisch; Modellmix und Caching/Batches aktiv optimieren', 'Chatbots und interne Wissensassistenten\nText-, Code-, Bild-, Audio- und Realtime-Generierung\nRAG mit Azure AI Search und Unternehmensdaten\nAutomatisierung mit Function Calling, Tools und Agents\nEmbedding-Pipelines, Klassifikation, Extraktion und Zusammenfassung', 'https://learn.microsoft.com/de-de/azure/foundry/foundry-models/concepts/models-sold-directly-by-azure?pivots=azure-openai', 'https://azure.microsoft.com/de-de/pricing/details/azure-openai/', 20],
            ['ki-machine-learning', 'azure-ai-search', 'Azure AI Search', 'Such- und Retrieval-Schicht für Apps, Agents und RAG.', 'Azure AI Search ist ein vollständig verwalteter Such- und Retrieval-Dienst für Volltext-, Vektor-, Hybrid-, semantische und agentische Suche über Unternehmensdaten.', 'Azure AI Search verbindet Unternehmensdaten mit klassischen Suchanwendungen, Chatbots und generativen KI-Lösungen. Der Dienst indexiert JSON-Dokumente aus Push- oder Pull-Pipelines, unterstützt Volltextsuche, Vektorsuche, Hybridsuche, semantische Rangfolge, KI-Anreicherung und agentischen Abruf für komplexe RAG-Szenarien. Für sichere Enterprise-Lösungen sind Indexdesign, Chunking, Vektorisierung, SKU/Suchunits, regionale Featureverfügbarkeit, Private Link, Entra ID/RBAC und Security Trimming entscheidend.', 'Volltext-, Vektor-, Hybrid-, multimodale und semantische Suche; Vektorsuche selbst ist kostenlos, Embeddings/KI-Anreicherung können extra kosten\nSemantischer Ranker rerankt nur die Top-50-Ergebnisse und erzeugt keine neuen Inhalte; Captions/Answers stammen wortgetreu aus dem Index\nAgentic Retrieval nutzt Wissensquellen, Knowledge Bases und optional LLM-gestützte Query-Planung; Abrechnung kann Search- und Modellkosten kombinieren\nGrenzwerte hängen stark von SKU, Region, Erstellungsdatum, Partitionen, Replikaten und Vektorquoten ab; ältere Dienste ggf. upgraden oder neu erstellen\nTLS, AES-256, Datenresidenz, Private Link, Entra ID/RBAC, CMK und Security Trimming für geschützte Inhalte einplanen', 'Enterprise Search für Portale, Apps und Intranets\nRAG-Grounding für Copilots, Agents und Chatbots\nDokumenten-, SharePoint-, Blob-, Cosmos-DB- und OneLake-Suche\nVektor- und Hybridsuche über Wissensdatenbanken\nSicherheitsgetrimmter Zugriff auf vertrauliche Inhalte', 'https://learn.microsoft.com/de-de/azure/search/search-what-is-azure-search', 'https://azure.microsoft.com/de-de/pricing/details/search/', 30],
            ['devops-tools', 'azure-devops', 'Azure DevOps', 'Planung, Code, CI/CD, Tests und Pakete in einer Plattform.', 'Azure DevOps bündelt Boards, Repos, Pipelines, Test Plans, Artifacts und Dashboards für den Software-Lifecycle von Planung bis Deployment.', 'Azure DevOps ist eine integrierte Entwicklungsplattform für Enterprise-Teams, die Arbeit planen, Quellcode verwalten, Builds automatisieren, Releases steuern, Tests nachverfolgen und Pakete verteilen müssen. Azure Boards, Repos, Pipelines, Test Plans und Artifacts greifen ineinander, bleiben aber einzeln nutzbar. Für regulierte Umgebungen sind Organisationsgeographie, Microsoft Entra ID, Berechtigungen, Branch Policies, Pipeline-Sicherheit und Paralleljobs die zentralen Planungsgrößen.', 'Azure DevOps Services speichert Kundendaten grundsätzlich in der gewählten Geographie; Token-Daten liegen laut Microsoft in den USA, macOS-Agenten können Daten in ein GitHub-Rechenzentrum in den USA übertragen\nÖffentliche Projekte werden eingestellt: neue öffentliche Projekte sind nicht mehr möglich, bestehende werden 2027 in private Projekte konvertiert\nPipeline-Kapazität hängt von Paralleljobs ab; kostenlose Kontingente können bei neuen Organisationen nicht automatisch aktiv sein und müssen ggf. beantragt werden\nBasic enthält die ersten 5 Benutzer kostenlos; Test Plans, zusätzliche Paralleljobs, Artifacts-Speicher über 2 GiB und GitHub Advanced Security werden separat bewertet\nFür Automatisierung Microsoft Entra OAuth, Dienstprinzipale oder verwaltete Identitäten bevorzugen; PATs nur kontrolliert und mit Richtlinien nutzen', 'CI/CD für Azure, Multicloud und On-Premises mit Genehmigungen\nAgile Planung, Backlogs, Boards und Release-Transparenz\nPrivate Git-Repositories mit Pull Requests und Branch Policies\nPaketfeeds für NuGet, npm, Maven, Python und interne Komponenten\nManuelle und explorative Tests mit Rückverfolgbarkeit zu Anforderungen', 'https://learn.microsoft.com/de-de/azure/devops/user-guide/what-is-azure-devops?view=azure-devops', 'https://azure.microsoft.com/de-de/pricing/details/devops/azure-devops-services/', 10],
            ['devops-tools', 'dev-box', 'Microsoft Dev Box', 'Vorkonfigurierte Cloud-Workstations für Entwicklerteams.', 'Microsoft Dev Box stellt vorkonfigurierte Cloud-Entwicklungsarbeitsplätze über Dev Center, Projekte und Pools bereit; Microsoft empfiehlt für neue virtualisierte Entwicklerumgebungen inzwischen Windows 365.', 'Microsoft Dev Box gibt Entwicklern über ein Portal Zugriff auf vorkonfigurierte Windows-Cloud-Workstations, die aus Dev Box-Pools mit definiertem Image, Compute, Speicher und Netzwerk entstehen. Plattformteams steuern Dev Center, Projekte, Pools, Kataloge, Image-Definitionen, Netzwerke und Rollen; die Dev Boxes werden über Microsoft Intune verwaltet und über Azure Virtual Desktop-Konnektivität erreicht. Der Dienst ist weiterhin unterstützt, befindet sich laut Microsoft aber im Wartungsmodus ohne geplante neue Features, daher sollte Windows 365 für neue strategische Entwickler-Cloudumgebungen geprüft werden.', 'Stand/Hinweis: Microsoft Dev Box ist im Wartungsmodus; für neue virtualisierte Entwicklerumgebungen nennt Microsoft Windows 365 als empfohlenen Pfad\nBenutzer benötigen passende Windows Enterprise-, Microsoft Intune- und Microsoft Entra ID P1-Lizenzen; viele Microsoft 365-Pläne enthalten diese Voraussetzungen\nGeschäfts- und Schulkonten werden unterstützt; Gastzugriff über Microsoft Entra B2B wurde eingestellt\nDie Netzwerkverbindung bestimmt die Hosting-Region: Microsoft-gehostet für reine Cloud-Szenarien, Azure-Netzwerkverbindung für eigenes VNet, Hybrid Join oder Zugriff auf Unternehmensressourcen\nAbrechnung kombiniert Lizenzvoraussetzungen, Speicher pro Dev Box und aktive Compute-Stunden bis zum monatlichen Maximalpreis; Autostopp und Ruhezustand konsequent nutzen', 'Standardisierte Entwicklerumgebungen für neue Mitarbeitende und Projektteams\nIsolierte Workstations für Auftragnehmer, sensible Repositories oder Kundensysteme\nRegionale Cloud-Workstations für verteilte Entwicklerteams mit niedrigerer Latenz\nMehrere getrennte Arbeitsumgebungen pro Entwickler für parallele Projekte\nReproduzierbare Toolchains über Image-Definitionen, Kataloge und Intune-Richtlinien', 'https://learn.microsoft.com/de-de/azure/dev-box/overview-what-is-microsoft-dev-box', 'https://azure.microsoft.com/de-de/pricing/details/dev-box/', 20],
            ['netzwerk-sicherheit', 'virtual-network', 'Azure Virtual Network', 'Private Netzwerkgrundlage für Azure- und Hybrid-Architekturen.', 'Azure Virtual Network bildet private Netzwerkbereiche für Azure-Ressourcen, Subnetze, Routing, Peering, Private Link, Dienstendpunkte und Hybridkonnektivität.', 'Azure Virtual Network ist die private Netzwerkbasis in Azure. Du definierst IP-Adressräume, Subnetze, Routing, Netzwerksicherheitsgruppen und Verbindungen zu anderen VNets, Azure-Diensten oder lokalen Netzwerken. Der Dienst selbst ist kostenlos, aber Peering, ausgehender Datenverkehr, Flow Logs, Private Link, Gateways und verbundene Dienste können kostenrelevant werden.', 'VNet selbst ist kostenlos; Peering wird für eingehenden und ausgehenden Datenverkehr berechnet\nSubnetze früh planen: kleinster Bereich /29, Azure reserviert mehrere Adressen pro Subnetz\nPeering nutzt das Microsoft-Backbone, ersetzt aber keine saubere IP-Adressplanung und keine Firewall-Strategie\nNSG-Regeln sind zustandsbehaftet; Standardregeln bleiben vorhanden und werden über Prioritäten überschrieben\nNSG Flow Logs werden abgelöst; für neue Projekte VNet Flow Logs mit Network Watcher planen', 'Landing Zones und Hub-Spoke-Netzwerke\nApp-, Datenbank- und Plattformsegmentierung über Subnetze und NSGs\nHybridkonnektivität über VPN Gateway oder ExpressRoute\nPrivate Endpoints und Dienstendpunkte für PaaS-Zugriff\nNetzwerkflussanalyse mit VNet Flow Logs und SIEM-Anbindung', 'https://learn.microsoft.com/de-de/azure/virtual-network/virtual-networks-overview', 'https://azure.microsoft.com/de-de/pricing/details/virtual-network/', 10],
            ['netzwerk-sicherheit', 'azure-firewall', 'Azure Firewall', 'Cloudnative Firewall für zentrale Netzwerk- und Egress-Kontrolle.', 'Azure Firewall ist ein verwalteter, zustandsbehafteter Firewall-Dienst für Azure Virtual Network, Hub-Spoke-Architekturen und kontrollierten Nord-Süd- sowie Ost-West-Datenverkehr.', 'Azure Firewall schützt virtuelle Netzwerke mit zentralen Regeln, Threat Intelligence, Protokollierung und integrierter Hochverfügbarkeit. Basic, Standard und Premium unterscheiden sich deutlich bei Durchsatz, DNS Proxy, FQDN-/Webkategorie-Filterung, TLS Inspection, IDPS und URL Filtering. Für produktive Architekturen musst du SKU, Routing, AzureFirewallSubnet, SNAT-Kapazität, Logging und Kosten vorab sauber dimensionieren.', 'SKUs bewusst wählen: Basic für kleine Szenarien, Standard für L3-L7-Filterung, Premium für TLS Inspection, IDPS und URL Filtering\nAzureFirewallSubnet mindestens /26 planen; Netzwerksicherheitsgruppen auf diesem Subnetz werden nicht unterstützt\nHub-Spoke pro Region bevorzugen; globales Peering über Regionen kann Latenz, Performance und Kosten verschlechtern\nDNAT mit erzwungenem Tunneling und IPv6 sind laut aktuellen Hinweisen nicht unterstützt\nPreise bestehen aus Bereitstellungsstunden, verarbeitetem Datenvolumen und optionalen Kapazitätseinheiten', 'Zentraler Internet-Egress für Azure-Workloads\nHub-Spoke-Segmentierung zwischen VNets und Subnetzen\nRegulierte Umgebungen mit zentralen Firewall Policies und Logs\nThreat-Intelligence-basierte Warnung oder Blockierung\nPremium-Szenarien mit TLS Inspection, IDPS und URL Filtering', 'https://learn.microsoft.com/de-de/azure/firewall/overview', 'https://azure.microsoft.com/de-de/pricing/details/azure-firewall/', 20],
            ['netzwerk-sicherheit', 'key-vault', 'Azure Key Vault', 'Secrets, Schlüssel und Zertifikate sicher verwalten.', 'Azure Key Vault schützt Secrets, kryptografische Schlüssel und Zertifikate zentral, trennt sensible Werte vom Code und integriert sich mit Microsoft Entra ID, Azure RBAC, Private Link, Monitoring und Managed HSM.', 'Azure Key Vault speichert und verwaltet Secrets, kryptografische Schlüssel und Zertifikate für Anwendungen und Plattformdienste. Für normale Vaults kannst du software- oder HSM-geschützte Schlüssel, Secrets und Zertifikate verwenden; Azure Key Vault Managed HSM ist ein Single-Tenant-HSM-Dienst für HSM-geschützte Schlüssel. Plane Identitäten, Azure RBAC, Netzwerksicherheit, Soft Delete, Purge Protection, Rotation, Monitoring und Throttling, bevor Anwendungen produktiv abhängig werden.', 'Managed Identities und Azure RBAC bevorzugen; Access Policies sind Legacy und können Contributor-Risiken erzeugen\nEin Vault pro Anwendung, Region und Umgebung planen; Objektbereich-Rollen nur in Sonderfällen nutzen\nSoft Delete ist für neue Vaults standardmäßig aktiv; Purge Protection für produktive Verschlüsselungsszenarien aktivieren\nFirewall, Private Endpoint oder deaktivierter öffentlicher Zugriff schützen die Datenebene; Azure DevOps ist kein pauschal vertrauenswürdiger Dienst\nTransaktionslimits, Versionen, Backupgrenzen und Rotation beachten; Key Vault nicht als allgemeinen Konfigurations- oder Kundendatenspeicher verwenden', 'App-Secrets ohne Geheimnisse im Code oder in CI/CD-Variablen\nTLS-Zertifikatslebenszyklus und automatische Erneuerung\nCustomer-managed keys, BYOK und Verschlüsselung ruhender Daten\nGeheimnis- und Schlüsselrotation mit kontrolliertem Rollout\nHSM-/Compliance-Szenarien mit Azure Key Vault Managed HSM', 'https://learn.microsoft.com/de-de/azure/key-vault/general/overview', 'https://azure.microsoft.com/de-de/pricing/details/key-vault/', 30],
            ['integration-kommunikation', 'api-management', 'Azure API Management', 'API-Gateways, Policies und Developer Portal für interne und externe APIs.', 'Azure API Management veröffentlicht, schützt und überwacht APIs über ein verwaltetes Gateway, Policies, Produkte, Abonnements und ein Developer Portal.', 'Azure API Management ist eine hybride Multi-Cloud-Plattform für interne und externe APIs. Du stellst APIs über Gateways bereit, steuerst Authentifizierung, Quotas, Rate Limits, Transformationen und Caching per Policies und gibst Entwicklerteams ein Portal für Dokumentation und Abonnements. Der Dienst passt, wenn APIs nicht nur erreichbar, sondern kontrolliert, messbar und produktfähig betrieben werden müssen.', 'Developer-Tarif ist nicht für Produktion gedacht und hat keine SLA\nConsumption ist serverlos und nutzungsbasiert, unterstützt aber nicht alle Funktionen der dedizierten Tarife\nVNet, Private Endpoints, Multi-Region, Availability Zones, Self-hosted Gateway und Workspaces hängen vom Tarif ab\nPolicies können Authentifizierung, Ratenbegrenzung, Transformation, Caching, Logging und Backend-Routing direkt im Gateway erzwingen\nAPI-, Operationen-, Produkt-, Abonnement- und Portalgrenzen je Tarif vor Migration prüfen', 'Partner- und Kunden-APIs sicher veröffentlichen\nMicroservice-Gateways mit zentralen Policies betreiben\nLegacy-Backends über moderne REST- oder SOAP-APIs kapseln\nAPI-Produkte mit Abonnements, Quotas und Developer Portal anbieten', 'https://learn.microsoft.com/de-de/azure/api-management/api-management-key-concepts', 'https://azure.microsoft.com/de-de/pricing/details/api-management/', 10],
            ['integration-kommunikation', 'logic-apps', 'Azure Logic Apps', 'Workflows, Connectoren und B2B-Integration ohne eigenen Orchestrator.', 'Azure Logic Apps automatisiert Geschäftsprozesse und Integrationen über Trigger, Aktionen, integrierte Connectoren und verwaltete Connectoren.', 'Azure Logic Apps ist eine Cloudplattform für Workflows, mit der du SaaS-, Azure-, On-Premises- und B2B-Systeme ohne eigenen Orchestrator verbindest. Ein Workflow startet mit einem Trigger und führt Aktionen aus, zum Beispiel Genehmigungen, Dateiverarbeitung, EDI/XML, API-Aufrufe oder Nachrichtenübergaben. Consumption und Standard unterscheiden sich deutlich bei Hosting, Netzwerk, Datenhaltung, Skalierung und Kostenmodell.', 'Consumption rechnet pro Ausführung ab und enthält pro Ressource einen Workflow; Standard nutzt einen Workflow Service Plan und kann mehrere stateful oder stateless Workflows enthalten\nStandard bietet mehr Kontrolle für VNet, Private Endpoints und regionale Datenhaltung; verwaltete Connectoren werden separat als Azure-Dienst betrieben\nGrenzen wie 500 Aktionen pro Workflow, 90 Tage Run History bei stateful Workflows und kurze stateless Laufzeiten einplanen\nHTTP-Timeouts und Nachrichtengrößen begrenzen synchrone Integrationen; lange Prozesse asynchron oder eventbasiert modellieren\nConnector-Typen, Integrationskonten, Storage und Netzwerkanbindung können Zusatzkosten verursachen', 'Genehmigungs- und Eskalationsprozesse über Microsoft 365, Dynamics und Drittanbieter\nDaten-Synchronisation zwischen SaaS, Azure-Diensten und On-Premises-Systemen\nB2B-Integration mit EDI, XML und Integrationskonten\nEventbasierte Automatisierung rund um Dateien, Tickets, APIs und Nachrichten', 'https://learn.microsoft.com/de-de/azure/logic-apps/logic-apps-overview', 'https://azure.microsoft.com/de-de/pricing/details/logic-apps/', 20],
            ['integration-kommunikation', 'service-bus', 'Azure Service Bus', 'Zuverlässiges Enterprise Messaging mit Queues und Topics.', 'Azure Service Bus ist ein vollständig verwalteter Enterprise-Nachrichtenbroker für entkoppelte Anwendungen, Warteschlangen und Publish/Subscribe-Kommunikation.', 'Azure Service Bus entkoppelt Anwendungen über Queues, Topics und Subscriptions, damit Sender und Empfänger unabhängig voneinander arbeiten können. Der Dienst bietet Funktionen wie Dead Letter Queues, Sessions, Duplicate Detection, Transaktionen, geplante Nachrichten und Filterregeln. Er ist sinnvoll, wenn Geschäftsnachrichten zuverlässig verarbeitet werden müssen und Consumer mit Wiederholungen, Sperren und möglichen Duplikaten umgehen können.', 'Basic unterstützt keine Topics, Transaktionen, Sessions, Duplicate Detection oder Forwarding; für Enterprise Messaging meist Standard oder Premium prüfen\nPremium bietet isolierte Ressourcen über Messaging Units, Netzwerksicherheitsfunktionen, CMK und größere AMQP-Nachrichten bis 100 MB\nStandard und Basic sind bei einzelnen Nachrichten typischerweise auf 256 KB begrenzt; große Payloads besser in Storage ablegen und Referenzen senden\nPeek-Lock arbeitet mit mindestens einmaliger Zustellung; Consumer müssen idempotent sein und Duplikate sauber behandeln\nAlte SDKs und das SBMP-Protokoll werden am 30. September 2026 eingestellt; auf aktuelle Azure SDKs und AMQP migrieren', 'Auftrags-, Bestell- und Zahlungsprozesse asynchron absichern\nFachsysteme über Queues ohne direkte Kopplung verbinden\nPub/Sub-Verteilung von Ereignissen an mehrere Backend-Systeme\nFehlerhafte Nachrichten über Dead Letter Queues analysieren und erneut verarbeiten', 'https://learn.microsoft.com/de-de/azure/service-bus-messaging/service-bus-messaging-overview', 'https://azure.microsoft.com/de-de/pricing/details/service-bus/', 30],
            ['iot-mixed-reality', 'iot-hub', 'Azure IoT Hub', 'Sichere Gerätekommunikation und Flottensteuerung im Azure-IoT-Backend.', 'Azure IoT Hub ist ein verwalteter zentraler Nachrichtenhub für sichere Geräte-zu-Cloud- und Cloud-zu-Gerät-Kommunikation in IoT-Lösungen.', 'Azure IoT Hub verbindet IoT-Geräte, Edge-Komponenten und Backend-Anwendungen sicher und skalierbar. Der Dienst unterstützt Geräteidentitäten, SAS- oder X.509-Authentifizierung, Geräte- und Modulzwillinge, direkte Methoden, Dateiuploads, Nachrichtenrouting und Monitoring. Er eignet sich für Geräteflotten, bei denen Telemetrie, Befehle, Konfiguration und Routing zuverlässig zusammengeführt werden müssen.', 'Basic unterstützt Geräteidentität, Geräte-zu-Cloud und Routing, aber keine Cloud-to-device-Kommunikation, Device Twins, IoT Edge und Gerätemanagement; für bidirektionale Flotten Standard wählen\nGeräteauthentifizierung über SAS oder X.509 planen; für Massenbereitstellung Device Provisioning Service einbeziehen\nPro Hub maximal 1.000.000 Geräte oder Module; pro Abonnement 50 IoT Hubs und ein Free Hub beachten\nD2C-Nachrichten maximal 256 KB, C2D maximal 64 KB; Routing und Zielendpunkte auf Durchsatz dimensionieren\nNach Microsoft werden Kundendaten nicht außerhalb der Geografie der bereitgestellten Dienstinstanz gespeichert; Region für DACH/DSGVO bewusst wählen', 'Maschinen-, Sensor- und Anlagenflotten sicher anbinden\nTelemetrie in Event Hubs, Storage, Service Bus, Cosmos DB oder Analytics-Dienste routen\nGerätekonfiguration über Device Twins und direkte Methoden steuern\nIndustrie-, Gebäude- und Field-Service-Szenarien mit IoT Edge erweitern', 'https://learn.microsoft.com/de-de/azure/iot-hub/iot-concepts-and-iot-hub', 'https://azure.microsoft.com/de-de/pricing/details/iot-hub/', 10],
            ['iot-mixed-reality', 'digital-twins', 'Azure Digital Twins', 'Digitale Graphmodelle für Gebäude, Anlagen, Prozesse und Umgebungen.', 'Azure Digital Twins modelliert reale Umgebungen als Zwillingsgraph aus DTDL-Modellen, digitalen Zwillingen, Beziehungen, Events und Abfragen.', 'Azure Digital Twins ist ein PaaS-Dienst für digitale Modelle ganzer Umgebungen wie Gebäude, Fabriken, Energieverteilnetze oder Anlagen. Du definierst Modelle mit DTDL, erstellst daraus digitale Zwillinge und verbindest sie über Beziehungen zu einem Graphen, der Livezustände und Kontext abbildet. Daten kommen häufig aus Azure IoT Hub, Geschäftssystemen oder APIs und werden über Abfragen, Ereignisrouten, Azure Functions, Event Hubs, Event Grid, Service Bus oder Azure Data Explorer weiterverarbeitet.', 'Modelle werden in DTDL definiert; DTDL v3 ist empfohlen, aber Azure Digital Twins Explorer unterstützt v3 nur eingeschränkt\nDTDL-Befehle sowie writable, minMultiplicity und maxMultiplicity werden von Azure Digital Twins nicht erzwungen\nInstanzlimits wie 2.000.000 Twins, 20.000.000 Beziehungen und 10.000 Modelle sowie 32 KB Twin-Payload beachten\nEreignisrouten unterstützen Event Hubs, Event Grid und Service Bus; Dead Lettering muss explizit eingerichtet werden\nKosten entstehen über Nachrichten, Vorgänge und Abfrageeinheiten; Graph-Abfragen und Routenaufkommen vorab modellieren', 'Smart Buildings mit Räumen, Etagen, Sensoren und Anlagen modellieren\nFertigungs- und Anlagenzustände im Kontext von Beziehungen analysieren\nIoT-Hub-Telemetrie mit Geschäftsobjekten und Standortdaten verbinden\nBetriebsdaten über Event Routes und Azure Data Explorer historisieren', 'https://learn.microsoft.com/de-de/azure/digital-twins/overview', 'https://azure.microsoft.com/de-de/pricing/details/digital-twins/', 20],
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
        foreach ($services as $service) {
            $content = null;
            if (count($service) === 11) {
                [$catSlug, $slug, $title, $subtitle, $summary, $content, $features, $useCases, $docsUrl, $pricingUrl, $order] = $service;
            } else {
                [$catSlug, $slug, $title, $subtitle, $summary, $features, $useCases, $docsUrl, $pricingUrl, $order] = $service;
                $content = $summary;
            }

            if (!isset($catIds[$catSlug])) {
                continue;
            }
            $subtitle = self::normalize_newlines((string) $subtitle);
            $summary = self::normalize_newlines((string) $summary);
            $content = self::normalize_newlines((string) $content);
            $features = self::normalize_newlines((string) $features);
            $useCases = self::normalize_newlines((string) $useCases);
            $svcStmt->execute([$catIds[$catSlug], $slug, $title, $subtitle, $summary, $content, $features, $useCases, $docsUrl, $pricingUrl, $order]);
        }
    }

    private static function normalize_newlines(string $value): string
    {
        return str_replace(["\\r\\n", "\\n", "\\r"], ["\n", "\n", "\n"], $value);
    }

    private static function upgrade_category_gallery_schema(object $db, string $prefix): void
    {
        if (self::column_exists($db, $prefix . 'm365azure_categories', 'gallery_images')) {
            return;
        }

        $pdo = $db->getPdo();
        try {
            $pdo->exec("ALTER TABLE {$prefix}m365azure_categories ADD COLUMN gallery_images MEDIUMTEXT DEFAULT NULL AFTER intro");
        } catch (\Throwable $e) {
            if (stripos($e->getMessage(), 'Duplicate column') === false && stripos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    }

    private static function column_exists(object $db, string $table, string $column): bool
    {
        $database = '';
        try {
            $databaseStmt = $db->prepare('SELECT DATABASE()');
            $databaseStmt->execute();
            $database = (string) ($databaseStmt->fetchColumn() ?: '');
        } catch (\Throwable) {
            $database = '';
        }

        if ($database !== '') {
            try {
                $stmt = $db->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
                $stmt->execute([$database, $table, $column]);
                return (int) $stmt->fetchColumn() > 0;
            } catch (\Throwable) {
                // Fallback auf SHOW COLUMNS, falls INFORMATION_SCHEMA nicht verfügbar ist.
            }
        }

        try {
            $safeTable = str_replace('`', '``', $table);
            $safeColumn = str_replace("'", "''", $column);
            $stmt = $db->prepare("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
            $stmt->execute();
            return (bool) $stmt->fetch();
        } catch (\Throwable) {
            return false;
        }
    }

    private static function upgrade_compute_content(object $db, string $prefix): void
    {
        $markerKey = 'content_compute_seed_version';
        $markerVersion = '2026-05-30-compute-v2';

        $markerStmt = $db->prepare("SELECT setting_value FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $markerStmt->execute([$markerKey]);
        if ((string) ($markerStmt->fetchColumn() ?: '') === $markerVersion) {
            return;
        }

        $updates = [
            'virtual-machines' => [
                'old' => [
                    'subtitle' => 'Windows- und Linux-VMs in Sekunden bereitstellen.',
                    'summary' => ['Ideal für Lift-and-Shift, klassische Server-Workloads, Testumgebungen und Spezialsoftware mit Betriebssystemzugriff.', 'Azure Virtual Machines stellt skalierbare Compute-Ressourcen für Windows und Linux bereit, wenn du Betriebssystem, installierte Software oder spezielle VM-Größen selbst steuern musst. Geeignet für klassische Server-Workloads, Migrationen und Anwendungen mit festen Laufzeitvorgaben; Patch-, OS- und Applikationsbetrieb bleiben jedoch in deiner Verantwortung.'],
                    'content' => ['Ideal für Lift-and-Shift, klassische Server-Workloads, Testumgebungen und Spezialsoftware mit Betriebssystemzugriff.', 'Mit VMs bekommst du IaaS-Compute inklusive Auswahl aus VM-Familien für allgemeine, compute-, speicher-, storage-, GPU- und HPC-Workloads. Plane Region, Größe, Datenträger, Netzwerk, Verfügbarkeit und Kontingente frühzeitig; Managed Disks, Public IPs, ausgehender Traffic und Betriebssystemlizenzen können separat kostenrelevant sein. Für Hochverfügbarkeit nutzt du Availability Zones, VM Scale Sets und Backup-/Recovery-Strategien.'],
                    'features' => ["Flexible Größen und Images\nWindows und Linux\nSkalierung mit VM Scale Sets", "Volle Kontrolle über Betriebssystem, Laufzeit und installierte Software\nViele VM-Familien für General Purpose, Compute, Memory, Storage, GPU und HPC\nHochverfügbarkeit über Availability Zones und VM Scale Sets planbar\nManaged Disks, Netzwerk, Lizenzen und Egress separat kalkulieren\nKontingente und regionale Größenverfügbarkeit vor Projektstart prüfen"],
                    'use_cases' => "Legacy-Anwendungen\nEntwicklungs- und Testsysteme\nRechenintensive Workloads",
                    'docs_url' => 'https://learn.microsoft.com/de-de/azure/virtual-machines/',
                    'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/virtual-machines/windows/',
                ],
                'new' => [
                    'subtitle' => 'Windows- und Linux-Server mit voller Betriebssystemkontrolle.',
                    'summary' => 'Azure Virtual Machines stellt skalierbare Compute-Ressourcen für Windows und Linux bereit, wenn du Betriebssystem, installierte Software oder spezielle VM-Größen selbst steuern musst. Azure bietet Größenfamilien für General Purpose, Compute-, Memory-, Storage-, GPU- und HPC-Workloads; du wählst Region, Verfügbarkeit, Datenträger und Netzwerk selbst. Der Dienst ist stark, wenn du maximale Kontrolle brauchst – Patch-, OS-, Sicherheits- und Applikationsbetrieb bleiben aber bei dir.',
                    'content' => 'Plane VM-Größe, Region, Verfügbarkeitszone, Datenträger, Netzwerk und Kontingente frühzeitig, weil nicht jede SKU in jeder Region oder Zone verfügbar ist und Kapazität separat zum genehmigten Kontingent geprüft wird. Neben der VM-Laufzeit können Managed Disks, Public IPs, Bandbreite/Egress, Backups sowie Windows-, SQL- oder Drittanbieter-Lizenzen kostenrelevant sein. Für produktive Workloads brauchst du ein klares HA-/DR-Konzept mit Availability Zones, VM Scale Sets, Load Balancer, Backup und optional Azure Site Recovery.',
                    'features' => "Volle Kontrolle über Betriebssystem, Laufzeit und installierte Software\nViele VM-Familien für General Purpose, Compute, Memory, Storage, GPU und HPC\nRegionale SKU-Verfügbarkeit, Kontingente und Kapazität vor Projektstart prüfen\nManaged Disks, Netzwerk, Public IPs, Backup, Egress und Lizenzen separat kalkulieren\nHochverfügbarkeit über Availability Zones, Availability Sets oder VM Scale Sets planen",
                    'use_cases' => "Lift-and-Shift bestehender Server und Fachanwendungen\nWindows- oder Linux-Workloads mit OS-Zugriff\nDatenbank-, SAP-, GPU- oder HPC-nahe Spezialworkloads\nEntwicklungs-, Test- und Schulungsumgebungen\nHybrid- oder Datacenter-Erweiterung über virtuelle Netzwerke",
                    'docs_url' => 'https://learn.microsoft.com/de-de/azure/virtual-machines/overview',
                    'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/virtual-machines/windows/',
                ],
            ],
            'azure-kubernetes-service' => [
                'old' => [
                    'subtitle' => 'Verwaltetes Kubernetes für containerisierte Anwendungen.',
                    'summary' => ['AKS reduziert den Betriebsaufwand für Kubernetes-Cluster und eignet sich für Microservices, Plattform-Teams und skalierende Container-Workloads.', 'AKS ist ein verwalteter Kubernetes-Dienst für containerisierte Anwendungen, bei dem Azure zentrale Clusteraufgaben wie Control Plane, Integritätsüberwachung und Wartung übernimmt. Du behältst Kontrolle über Knotenpools, Workloads, Netzwerk, Identität und Betriebsmodell und kannst zwischen Standard- und stärker verwalteten Automatic-Ansätzen wählen.'],
                    'content' => ['AKS reduziert den Betriebsaufwand für Kubernetes-Cluster und eignet sich für Microservices, Plattform-Teams und skalierende Container-Workloads.', 'AKS passt, wenn Teams Kubernetes-Funktionen, Portabilität und Plattformstandards brauchen: Microservices, sichere DevOps, Windows/Linux-Container, ML/Streaming oder mehrere Knotenpools. Plane Kubernetes-Versionen, Node-Images, Resource Reservations, Netzwerk, Monitoring, Policy, Skalierung und Cluster-Tarif bewusst; produktive Workloads benötigen meist SLA-/Standard- oder Premium-Optionen und verursachen Kosten für Knoten, Storage, Netzwerk und ggf. Control Plane.'],
                    'features' => ["Managed Kubernetes\nCluster-Skalierung\nIntegration mit Azure Monitor und Container Registry", "Verwaltete Kubernetes Control Plane mit Azure-Integration\nKnotenpools für unterschiedliche VM-Größen, Betriebssysteme und Workloads\nAutoscaling über Cluster Autoscaler, Horizontal Pod Autoscaler und KEDA-Szenarien\nIntegration mit Entra ID, Azure Policy, Azure Monitor und Container Registry\nKnotenimages und Kubernetes-Versionen aktiv warten; veraltete Images nicht neu einplanen"],
                    'use_cases' => "Microservices\nPlattform Engineering\nCloudnative Anwendungen",
                    'docs_url' => 'https://learn.microsoft.com/de-de/azure/aks/',
                    'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/kubernetes-service/',
                ],
                'new' => [
                    'subtitle' => 'Managed Kubernetes für produktive Containerplattformen.',
                    'summary' => 'AKS ist ein verwalteter Kubernetes-Dienst für containerisierte Anwendungen, bei dem Azure zentrale Clusteraufgaben wie Control Plane, Integritätsüberwachung und Wartung übernimmt. Du behältst Kontrolle über Workloads, Knotenpools, Netzwerk, Identität, Richtlinien und das Betriebsmodell. AKS eignet sich, wenn Teams Kubernetes-Funktionen, Portabilität, Plattformstandards und Integration mit Azure-Diensten brauchen.',
                    'content' => 'Wähle den passenden Betriebsmodus und Tarif bewusst: Free eignet sich eher für Tests ohne SLA, Standard für produktive Workloads mit SLA und Premium für längeren Kubernetes-Support. Kosten entstehen vor allem durch Knoten-VMs, Storage, Netzwerk, ggf. Clusterverwaltung und bei AKS Automatic zusätzlich durch die stärker verwaltete Plattform. Wichtig: Azure Linux 2.0-Knotenimages erhalten ab 30. November 2025 keine Sicherheitsupdates mehr und werden ab 31. März 2026 entfernt; plane rechtzeitig ein Upgrade auf unterstützte Kubernetes-Versionen oder AzureLinux3.',
                    'features' => "Verwaltete Kubernetes Control Plane mit Azure-Integration\nKnotenpools für unterschiedliche VM-Größen, Betriebssysteme und Workloads\nAutomatic oder Standard je nach gewünschtem Kontroll- und Betriebsgrad wählen\nIntegration mit Entra ID, Azure Policy, Azure Monitor, Container Registry und Netzwerkfeatures\nKubernetes-Versionen und Node-Images aktiv warten; Azure Linux 2.0 nicht neu einplanen",
                    'use_cases' => "Microservices- und Plattform-Engineering-Umgebungen\nModernisierung containerisierter Bestandsanwendungen\nCI/CD- und GitOps-basierte Deployments\nWindows- und Linux-Container in einem Kubernetes-Betriebsmodell\nSkalierende APIs, Datenstreaming- oder ML-Workloads",
                    'docs_url' => 'https://learn.microsoft.com/de-de/azure/aks/what-is-aks',
                    'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/kubernetes-service/',
                ],
            ],
            'azure-functions' => [
                'old' => [
                    'subtitle' => 'Ereignisgesteuerte serverlose Funktionen.',
                    'summary' => ['Azure Functions führt Code auf Abruf aus, ohne dass Server verwaltet werden müssen – passend für Automatisierung, APIs und Event-Verarbeitung.', 'Azure Functions führt kleine, ereignisgesteuerte Codeeinheiten aus und verbindet sie über Trigger und Bindings mit Azure-Diensten, APIs, Queues, Datenbanken und Zeitplänen. Du konzentrierst dich auf den Code; Hostingplan, Laufzeit, Skalierung und Netzwerkanforderungen bestimmen Kosten, Performance und Betriebsgrenzen.'],
                    'content' => ['Azure Functions führt Code auf Abruf aus, ohne dass Server verwaltet werden müssen – passend für Automatisierung, APIs und Event-Verarbeitung.', 'Für neue serverlose Apps ist Flex Consumption die moderne Standardwahl mit Pay-as-you-go, schneller Skalierung und VNet-Integration; Premium eignet sich bei warmen Instanzen, längeren Laufzeiten, planbarerer Performance und VNet-Bedarf. Plane Timeouts, Kaltstartverhalten, Speicher, Storage Account, Monitoring und Sprache/Laufzeit aktiv ein; lange HTTP-Verarbeitung sollte asynchron oder mit Durable Functions modelliert werden.'],
                    'features' => ["Serverless Runtime\nTrigger für HTTP, Timer, Queue und Events\nSkalierung nach Bedarf", "Trigger und Bindings für HTTP, Timer, Storage, Queues, Event Hubs, Service Bus und mehr\nFlex Consumption für neue serverlose Apps bevorzugen; klassischer Consumption-Plan ist eingeschränkt/veraltet\nPremium-Plan bietet Always-ready Instanzen, VNet-Integration und weniger Kaltstart-Risiko\nAbrechnung je nach Plan über Ausführungen, Ressourcenverbrauch oder bereitgestellte Instanzen\nMonitoring mit Azure Monitor und Application Insights einplanen"],
                    'use_cases' => "Automatisierung\nWebhook-Backends\nEvent Processing",
                    'docs_url' => 'https://learn.microsoft.com/de-de/azure/azure-functions/',
                    'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/functions/',
                ],
                'new' => [
                    'subtitle' => 'Event-getriebener Code ohne eigenen Serverbetrieb.',
                    'summary' => 'Azure Functions ist eine serverlose Lösung für kleine, ereignisgesteuerte Codeeinheiten, die über Trigger und Bindings mit HTTP, Timern, Storage, Queues, Event Hubs, Service Bus und weiteren Diensten verbunden werden. Du konzentrierst dich auf die Geschäftslogik; Azure übernimmt Hosting, Skalierung und Laufzeitumgebung. Der passende Hostingplan entscheidet über Kaltstart, Netzwerkzugriff, Timeout, Skalierung und Abrechnung.',
                    'content' => 'Für neue serverlose Apps ist Flex Consumption die empfohlene Standardwahl mit schneller ereignisgesteuerter Skalierung, VNet-Integration und Pay-as-you-go. Der klassische Consumption-Plan ist für neue Apps nur noch eingeschränkt sinnvoll; Linux im Consumption-Plan wird zum 30. September 2028 eingestellt und Functions v3 auf Linux Consumption läuft nach dem 30. September 2026 nicht mehr. Beachte außerdem das HTTP-Limit von 230 Sekunden für Antworten und verschiebe längere Verarbeitung in Queues, Durable Functions oder asynchrone Muster.',
                    'features' => "Trigger und Bindings für HTTP, Timer, Storage, Queues, Event Hubs, Service Bus und mehr\nFlex Consumption für neue serverlose Apps bevorzugen\nPremium-Plan bietet Always-ready Instanzen, VNet-Integration und weniger Kaltstart-Risiko\nHTTP-Antworten sind trotz längerer Funktionslaufzeiten auf 230 Sekunden begrenzt\nStorage Account, Monitoring, Ausführungen, GB-Sekunden und Always-ready Instanzen kostenrelevant einplanen",
                    'use_cases' => "Webhooks und leichte APIs\nZeitgesteuerte Automatisierung und Datenbereinigung\nQueue-, Event-Hub- und Service-Bus-Verarbeitung\nDatei-Upload- und Datenbankänderungsreaktionen\nServerlose Workflows mit Durable Functions",
                    'docs_url' => 'https://learn.microsoft.com/de-de/azure/azure-functions/functions-overview',
                    'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/functions/',
                ],
            ],
            'container-apps' => [
                'old' => [
                    'subtitle' => 'Serverlose Container für Apps und Microservices.',
                    'summary' => ['Container Apps kombiniert Containerbetrieb mit serverloser Skalierung und eignet sich für APIs, Worker und Dapr-basierte Microservices.', 'Azure Container Apps ist eine serverlose Plattform für containerisierte APIs, Worker, Jobs und Microservices. Du bringst Containerimages mit, Azure übernimmt Infrastruktur, Ingress, Revisionen, Skalierung und optionale Dapr-Integration deutlich stärker als bei einem eigenen Kubernetes-Cluster.'],
                    'content' => ['Container Apps kombiniert Containerbetrieb mit serverloser Skalierung und eignet sich für APIs, Worker und Dapr-basierte Microservices.', 'Container Apps skaliert anhand von HTTP/TCP, CPU/Memory oder KEDA-unterstützten Ereignisquellen und kann bei passenden Regeln bis auf null Replikate herunterfahren. Es eignet sich besonders, wenn du Container-Flexibilität brauchst, aber keinen AKS-Betrieb verantworten willst; plane Revisionsmodell, Secrets, Managed Identity, Registry, VNet, Logging, Mindestreplikate und Kosten für aktive bzw. Leerlauf-Replikate.'],
                    'features' => ["Container ohne Clusterbetrieb\nScale-to-zero möglich\nDapr-Integration", "Serverlose Containerplattform mit HTTPS/TCP-Ingress und Revisionsmodell\nAutomatische Skalierung über KEDA; Scale-to-zero möglich, außer bei bestimmten Regeln wie CPU/Memory\nDapr-APIs für Service Invocation, Pub/Sub, State, Bindings, Secrets und Configuration verfügbar\nContainer aus öffentlichen oder privaten Registries inklusive Azure Container Registry\nKosten nach Plan, Ressourcen, Anforderungen und ggf. Leerlaufreplikaten kalkulieren"],
                    'use_cases' => "APIs\nBackground Worker\nEvent-getriebene Microservices",
                    'docs_url' => 'https://learn.microsoft.com/de-de/azure/container-apps/',
                    'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/container-apps/',
                ],
                'new' => [
                    'subtitle' => 'Serverlose Container ohne eigenen Kubernetes-Betrieb.',
                    'summary' => 'Azure Container Apps ist eine serverlose Plattform für containerisierte APIs, Worker, Jobs und Microservices. Du bringst Containerimages mit; Azure übernimmt viele Infrastruktur-, Ingress-, Revisions-, Skalierungs- und Betriebsdetails. Der Dienst basiert auf Kubernetes-nahen Konzepten und Open-Source-Technologien wie KEDA, Dapr und Envoy, ohne dass du die Kubernetes-API direkt betreibst.',
                    'content' => 'Container Apps skaliert über HTTP/TCP-Regeln oder KEDA-unterstützte Ereignisquellen und kann bei passenden Regeln bis auf null Replikate herunterfahren; CPU- oder Memory-basierte Skalierung eignet sich nicht für Scale-to-zero. Wenn du direkten Zugriff auf Kubernetes-API, Control Plane oder vollständige Clusterkonfiguration brauchst, ist AKS die passendere Plattform. Plane Revisionen, Traffic-Splitting, Secrets, Managed Identity, Registry-Zugriff, VNet, Logging, Mindest-/Maximalreplikate und Kosten für aktive oder leerlaufende Replikate.',
                    'features' => "Serverlose Containerplattform mit HTTPS/TCP-Ingress, Revisionen und Traffic-Splitting\nAutomatische Skalierung über KEDA; Scale-to-zero möglich, aber nicht bei CPU-/Memory-Regeln\nDapr-APIs für Service Invocation, Pub/Sub, State, Bindings, Secrets und Configuration verfügbar\nKein direkter Kubernetes-API-Zugriff; bei vollem Clusterbetrieb AKS prüfen\nKosten nach Plan, Ressourcen, Anforderungen und ggf. Leerlaufreplikaten kalkulieren",
                    'use_cases' => "APIs und Web-Backends als Container\nBackground Worker und ereignisgetriebene Verarbeitung\nMicroservices mit Dapr und Service Discovery\nScheduled, manuelle oder eventbasierte Container Apps Jobs\nContainerisierte Azure Functions oder kleine Plattformbausteine",
                    'docs_url' => 'https://learn.microsoft.com/de-de/azure/container-apps/overview',
                    'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/container-apps/',
                ],
            ],
        ];

        $fields = ['subtitle', 'summary', 'content', 'features', 'use_cases', 'docs_url', 'pricing_url'];
        $select = $db->prepare("SELECT id, subtitle, summary, content, features, use_cases, docs_url, pricing_url FROM {$prefix}m365azure_services WHERE slug = ?");
        $update = $db->prepare("UPDATE {$prefix}m365azure_services SET subtitle = ?, summary = ?, content = ?, features = ?, use_cases = ?, docs_url = ?, pricing_url = ? WHERE id = ?");

        foreach ($updates as $slug => $data) {
            $select->execute([$slug]);
            $row = $select->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                continue;
            }

            $values = [];
            $changed = false;
            foreach ($fields as $field) {
                $current = self::normalize_newlines((string) ($row[$field] ?? ''));
                $oldValues = (array) ($data['old'][$field] ?? '');
                $oldValues = array_map(static function ($value): string {
                    return self::normalize_newlines((string) $value);
                }, $oldValues);
                if (trim($current) === '' || in_array($current, $oldValues, true)) {
                    $values[$field] = self::normalize_newlines((string) ($data['new'][$field] ?? ''));
                    $changed = true;
                } else {
                    $values[$field] = $current;
                }
            }

            if ($changed) {
                $update->execute([
                    $values['subtitle'],
                    $values['summary'],
                    $values['content'],
                    $values['features'],
                    $values['use_cases'],
                    $values['docs_url'],
                    $values['pricing_url'],
                    (int) $row['id'],
                ]);
            }
        }

        $exists = $db->prepare("SELECT id FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $exists->execute([$markerKey]);
        if ($exists->fetch()) {
            $stmt = $db->prepare("UPDATE {$prefix}m365azure_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$markerVersion, $markerKey]);
        } else {
            $stmt = $db->prepare("INSERT INTO {$prefix}m365azure_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$markerKey, $markerVersion]);
        }
    }

    private static function upgrade_compute_description_mapping(object $db, string $prefix): void
    {
        $markerKey = 'content_compute_description_version';
        $markerVersion = '2026-05-30-compute-v3';

        $markerStmt = $db->prepare("SELECT setting_value FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $markerStmt->execute([$markerKey]);
        if ((string) ($markerStmt->fetchColumn() ?: '') === $markerVersion) {
            return;
        }

        $updates = [
            'virtual-machines' => [
                'summary' => 'Azure Virtual Machines stellt skalierbare Windows- und Linux-Server bereit, wenn du Betriebssystem, Software und Infrastrukturdetails selbst steuern musst.',
                'known_summary' => [
                    'Ideal für Lift-and-Shift, klassische Server-Workloads, Testumgebungen und Spezialsoftware mit Betriebssystemzugriff.',
                    'Azure Virtual Machines stellt skalierbare Compute-Ressourcen für Windows und Linux bereit, wenn du Betriebssystem, installierte Software oder spezielle VM-Größen selbst steuern musst. Geeignet für klassische Server-Workloads, Migrationen und Anwendungen mit festen Laufzeitvorgaben; Patch-, OS- und Applikationsbetrieb bleiben jedoch in deiner Verantwortung.',
                    'Azure Virtual Machines stellt skalierbare Compute-Ressourcen für Windows und Linux bereit, wenn du Betriebssystem, installierte Software oder spezielle VM-Größen selbst steuern musst. Azure bietet Größenfamilien für General Purpose, Compute-, Memory-, Storage-, GPU- und HPC-Workloads; du wählst Region, Verfügbarkeit, Datenträger und Netzwerk selbst. Der Dienst ist stark, wenn du maximale Kontrolle brauchst – Patch-, OS-, Sicherheits- und Applikationsbetrieb bleiben aber bei dir.',
                ],
                'content' => 'Azure Virtual Machines stellt skalierbare Windows- und Linux-Server bereit, wenn du Betriebssystem, installierte Software oder spezielle VM-Größen selbst steuern musst. Du wählst Region, VM-Familie, Datenträger, Netzwerk und Verfügbarkeitsmodell passend zur Workload. Der Dienst ist sinnvoll für Migrationen und Spezialsoftware, bringt aber weiterhin Verantwortung für Patches, OS-Härtung, Backup und Betrieb mit.',
                'features' => "VM-Größen sind je Region und Zone unterschiedlich verfügbar; SKU, Kontingent und tatsächliche Kapazität vor Projektstart prüfen\nManaged Disks, Public IPs, Bandbreite/Egress, Backups und Lizenzen separat kalkulieren\nFür produktive Systeme Availability Zones, Availability Sets, VM Scale Sets oder Site Recovery bewusst planen\nTemporärer lokaler Speicher ist nicht dauerhaft und eignet sich nur für Cache oder temporäre Daten\nVM-Größenfamilie nach Workload wählen: General Purpose, Compute, Memory, Storage, GPU oder HPC",
                'use_cases' => "Lift-and-Shift bestehender Server und Fachanwendungen\nWindows- oder Linux-Workloads mit OS-Zugriff\nDatenbank-, SAP-, GPU- oder HPC-nahe Spezialworkloads\nEntwicklungs-, Test- und Schulungsumgebungen",
                'known_content' => [
                    'Ideal für Lift-and-Shift, klassische Server-Workloads, Testumgebungen und Spezialsoftware mit Betriebssystemzugriff.',
                    'Mit VMs bekommst du IaaS-Compute inklusive Auswahl aus VM-Familien für allgemeine, compute-, speicher-, storage-, GPU- und HPC-Workloads. Plane Region, Größe, Datenträger, Netzwerk, Verfügbarkeit und Kontingente frühzeitig; Managed Disks, Public IPs, ausgehender Traffic und Betriebssystemlizenzen können separat kostenrelevant sein. Für Hochverfügbarkeit nutzt du Availability Zones, VM Scale Sets und Backup-/Recovery-Strategien.',
                    'Plane VM-Größe, Region, Verfügbarkeitszone, Datenträger, Netzwerk und Kontingente frühzeitig, weil nicht jede SKU in jeder Region oder Zone verfügbar ist und Kapazität separat zum genehmigten Kontingent geprüft wird. Neben der VM-Laufzeit können Managed Disks, Public IPs, Bandbreite/Egress, Backups sowie Windows-, SQL- oder Drittanbieter-Lizenzen kostenrelevant sein. Für produktive Workloads brauchst du ein klares HA-/DR-Konzept mit Availability Zones, VM Scale Sets, Load Balancer, Backup und optional Azure Site Recovery.',
                ],
                'known_features' => [
                    "Flexible Größen und Images\nWindows und Linux\nSkalierung mit VM Scale Sets",
                    "Volle Kontrolle über Betriebssystem, Laufzeit und installierte Software\nViele VM-Familien für General Purpose, Compute, Memory, Storage, GPU und HPC\nHochverfügbarkeit über Availability Zones und VM Scale Sets planbar\nManaged Disks, Netzwerk, Lizenzen und Egress separat kalkulieren\nKontingente und regionale Größenverfügbarkeit vor Projektstart prüfen",
                    "Volle Kontrolle über Betriebssystem, Laufzeit und installierte Software\nViele VM-Familien für General Purpose, Compute, Memory, Storage, GPU und HPC\nRegionale SKU-Verfügbarkeit, Kontingente und Kapazität vor Projektstart prüfen\nManaged Disks, Netzwerk, Public IPs, Backup, Egress und Lizenzen separat kalkulieren\nHochverfügbarkeit über Availability Zones, Availability Sets oder VM Scale Sets planen",
                ],
                'known_use_cases' => [
                    "Legacy-Anwendungen\nEntwicklungs- und Testsysteme\nRechenintensive Workloads",
                    "Lift-and-Shift bestehender Server und Fachanwendungen\nWindows- oder Linux-Workloads mit OS-Zugriff\nDatenbank-, SAP-, GPU- oder HPC-nahe Spezialworkloads\nEntwicklungs-, Test- und Schulungsumgebungen\nHybrid- oder Datacenter-Erweiterung über virtuelle Netzwerke",
                ],
            ],
            'azure-kubernetes-service' => [
                'summary' => 'AKS ist Microsofts verwalteter Kubernetes-Dienst für containerisierte Anwendungen und Plattformen.',
                'known_summary' => [
                    'AKS reduziert den Betriebsaufwand für Kubernetes-Cluster und eignet sich für Microservices, Plattform-Teams und skalierende Container-Workloads.',
                    'AKS ist ein verwalteter Kubernetes-Dienst für containerisierte Anwendungen, bei dem Azure zentrale Clusteraufgaben wie Control Plane, Integritätsüberwachung und Wartung übernimmt. Du behältst Kontrolle über Knotenpools, Workloads, Netzwerk, Identität und Betriebsmodell und kannst zwischen Standard- und stärker verwalteten Automatic-Ansätzen wählen.',
                    'AKS ist ein verwalteter Kubernetes-Dienst für containerisierte Anwendungen, bei dem Azure zentrale Clusteraufgaben wie Control Plane, Integritätsüberwachung und Wartung übernimmt. Du behältst Kontrolle über Workloads, Knotenpools, Netzwerk, Identität, Richtlinien und das Betriebsmodell. AKS eignet sich, wenn Teams Kubernetes-Funktionen, Portabilität, Plattformstandards und Integration mit Azure-Diensten brauchen.',
                ],
                'content' => 'AKS ist Microsofts verwalteter Kubernetes-Dienst für containerisierte Anwendungen und Plattformen. Azure übernimmt Control-Plane-Betrieb, Wartung und Integrationen, während du Workloads, Knotenpools, Netzwerk, Identität, Richtlinien und Release-Prozesse steuerst. AKS passt, wenn dein Team Kubernetes-Funktionen, Portabilität und klare Plattformstandards braucht, statt nur einen einfachen Container-Host.',
                'features' => "Free eher für Tests ohne SLA; Standard für produktive Workloads mit SLA; Premium für Long-Term Support planen\nKosten entstehen vor allem durch Knoten-VMs, Storage, Netzwerk, Cluster-Tier und ggf. AKS Automatic\nKubernetes-Minor-Versionen können beim Upgrade nicht übersprungen werden\nVor Upgrades Compute-Quota und verfügbare Zielversionen prüfen\nAzure Linux 2.0 Knotenimages nicht neu einplanen; Migration auf unterstützte Versionen oder AzureLinux3 vorbereiten",
                'use_cases' => "Microservices- und Plattform-Engineering-Umgebungen\nModernisierung containerisierter Bestandsanwendungen\nCI/CD- und GitOps-basierte Deployments\nWindows- und Linux-Container in einem Kubernetes-Betriebsmodell",
                'known_content' => [
                    'AKS reduziert den Betriebsaufwand für Kubernetes-Cluster und eignet sich für Microservices, Plattform-Teams und skalierende Container-Workloads.',
                    'AKS passt, wenn Teams Kubernetes-Funktionen, Portabilität und Plattformstandards brauchen: Microservices, sichere DevOps, Windows/Linux-Container, ML/Streaming oder mehrere Knotenpools. Plane Kubernetes-Versionen, Node-Images, Resource Reservations, Netzwerk, Monitoring, Policy, Skalierung und Cluster-Tarif bewusst; produktive Workloads benötigen meist SLA-/Standard- oder Premium-Optionen und verursachen Kosten für Knoten, Storage, Netzwerk und ggf. Control Plane.',
                    'Wähle den passenden Betriebsmodus und Tarif bewusst: Free eignet sich eher für Tests ohne SLA, Standard für produktive Workloads mit SLA und Premium für längeren Kubernetes-Support. Kosten entstehen vor allem durch Knoten-VMs, Storage, Netzwerk, ggf. Clusterverwaltung und bei AKS Automatic zusätzlich durch die stärker verwaltete Plattform. Wichtig: Azure Linux 2.0-Knotenimages erhalten ab 30. November 2025 keine Sicherheitsupdates mehr und werden ab 31. März 2026 entfernt; plane rechtzeitig ein Upgrade auf unterstützte Kubernetes-Versionen oder AzureLinux3.',
                ],
                'known_features' => [
                    "Managed Kubernetes\nCluster-Skalierung\nIntegration mit Azure Monitor und Container Registry",
                    "Verwaltete Kubernetes Control Plane mit Azure-Integration\nKnotenpools für unterschiedliche VM-Größen, Betriebssysteme und Workloads\nAutoscaling über Cluster Autoscaler, Horizontal Pod Autoscaler und KEDA-Szenarien\nIntegration mit Entra ID, Azure Policy, Azure Monitor und Container Registry\nKnotenimages und Kubernetes-Versionen aktiv warten; veraltete Images nicht neu einplanen",
                    "Verwaltete Kubernetes Control Plane mit Azure-Integration\nKnotenpools für unterschiedliche VM-Größen, Betriebssysteme und Workloads\nAutomatic oder Standard je nach gewünschtem Kontroll- und Betriebsgrad wählen\nIntegration mit Entra ID, Azure Policy, Azure Monitor, Container Registry und Netzwerkfeatures\nKubernetes-Versionen und Node-Images aktiv warten; Azure Linux 2.0 nicht neu einplanen",
                ],
                'known_use_cases' => [
                    "Microservices\nPlattform Engineering\nCloudnative Anwendungen",
                    "Microservices- und Plattform-Engineering-Umgebungen\nModernisierung containerisierter Bestandsanwendungen\nCI/CD- und GitOps-basierte Deployments\nWindows- und Linux-Container in einem Kubernetes-Betriebsmodell\nSkalierende APIs, Datenstreaming- oder ML-Workloads",
                ],
            ],
            'azure-functions' => [
                'summary' => 'Azure Functions führt ereignisgesteuerten Code aus, ohne dass du eigene Server betreiben musst.',
                'known_summary' => [
                    'Azure Functions führt Code auf Abruf aus, ohne dass Server verwaltet werden müssen – passend für Automatisierung, APIs und Event-Verarbeitung.',
                    'Azure Functions führt kleine, ereignisgesteuerte Codeeinheiten aus und verbindet sie über Trigger und Bindings mit Azure-Diensten, APIs, Queues, Datenbanken und Zeitplänen. Du konzentrierst dich auf den Code; Hostingplan, Laufzeit, Skalierung und Netzwerkanforderungen bestimmen Kosten, Performance und Betriebsgrenzen.',
                    'Azure Functions ist eine serverlose Lösung für kleine, ereignisgesteuerte Codeeinheiten, die über Trigger und Bindings mit HTTP, Timern, Storage, Queues, Event Hubs, Service Bus und weiteren Diensten verbunden werden. Du konzentrierst dich auf die Geschäftslogik; Azure übernimmt Hosting, Skalierung und Laufzeitumgebung. Der passende Hostingplan entscheidet über Kaltstart, Netzwerkzugriff, Timeout, Skalierung und Abrechnung.',
                ],
                'content' => 'Azure Functions ist eine serverlose Lösung für kleine, ereignisgesteuerte Codeeinheiten, die über Trigger und Bindings mit HTTP, Timern, Storage, Queues, Event Hubs, Service Bus und weiteren Diensten verbunden werden. Du konzentrierst dich auf die Geschäftslogik; Azure übernimmt Hosting, Skalierung und Laufzeitumgebung. Der passende Hostingplan entscheidet über Kaltstart, Netzwerkzugriff, Timeout, Skalierung und Abrechnung.',
                'features' => "Flex Consumption für neue serverlose Apps bevorzugen; klassischer Consumption-Plan ist veraltet beziehungsweise eingeschränkt\nLinux Consumption wird am 30. September 2028 eingestellt; Functions v3 auf Linux Consumption läuft nach dem 30. September 2026 nicht mehr\nHTTP-getriggerte Funktionen haben ein Antwortlimit von 230 Sekunden; lange Verarbeitung asynchron auslagern\nPlanwahl beeinflusst Kaltstart, VNet, Timeout, Skalierung, Slots und Kostenmodell\nStorage Account, Monitoring, Ausführungen, GB-Sekunden und Always-ready Instanzen in der Kalkulation berücksichtigen",
                'use_cases' => "Webhooks und leichte APIs\nZeitgesteuerte Automatisierung und Datenbereinigung\nQueue-, Event-Hub- und Service-Bus-Verarbeitung\nServerlose Workflows mit Durable Functions",
                'known_content' => [
                    'Azure Functions führt Code auf Abruf aus, ohne dass Server verwaltet werden müssen – passend für Automatisierung, APIs und Event-Verarbeitung.',
                    'Für neue serverlose Apps ist Flex Consumption die moderne Standardwahl mit Pay-as-you-go, schneller Skalierung und VNet-Integration; Premium eignet sich bei warmen Instanzen, längeren Laufzeiten, planbarerer Performance und VNet-Bedarf. Plane Timeouts, Kaltstartverhalten, Speicher, Storage Account, Monitoring und Sprache/Laufzeit aktiv ein; lange HTTP-Verarbeitung sollte asynchron oder mit Durable Functions modelliert werden.',
                    'Für neue serverlose Apps ist Flex Consumption die empfohlene Standardwahl mit schneller ereignisgesteuerter Skalierung, VNet-Integration und Pay-as-you-go. Der klassische Consumption-Plan ist für neue Apps nur noch eingeschränkt sinnvoll; Linux im Consumption-Plan wird zum 30. September 2028 eingestellt und Functions v3 auf Linux Consumption läuft nach dem 30. September 2026 nicht mehr. Beachte außerdem das HTTP-Limit von 230 Sekunden für Antworten und verschiebe längere Verarbeitung in Queues, Durable Functions oder asynchrone Muster.',
                ],
                'known_features' => [
                    "Serverless Runtime\nTrigger für HTTP, Timer, Queue und Events\nSkalierung nach Bedarf",
                    "Trigger und Bindings für HTTP, Timer, Storage, Queues, Event Hubs, Service Bus und mehr\nFlex Consumption für neue serverlose Apps bevorzugen; klassischer Consumption-Plan ist eingeschränkt/veraltet\nPremium-Plan bietet Always-ready Instanzen, VNet-Integration und weniger Kaltstart-Risiko\nAbrechnung je nach Plan über Ausführungen, Ressourcenverbrauch oder bereitgestellte Instanzen\nMonitoring mit Azure Monitor und Application Insights einplanen",
                    "Trigger und Bindings für HTTP, Timer, Storage, Queues, Event Hubs, Service Bus und mehr\nFlex Consumption für neue serverlose Apps bevorzugen\nPremium-Plan bietet Always-ready Instanzen, VNet-Integration und weniger Kaltstart-Risiko\nHTTP-Antworten sind trotz längerer Funktionslaufzeiten auf 230 Sekunden begrenzt\nStorage Account, Monitoring, Ausführungen, GB-Sekunden und Always-ready Instanzen kostenrelevant einplanen",
                ],
                'known_use_cases' => [
                    "Automatisierung\nWebhook-Backends\nEvent Processing",
                    "Webhooks und leichte APIs\nZeitgesteuerte Automatisierung und Datenbereinigung\nQueue-, Event-Hub- und Service-Bus-Verarbeitung\nDatei-Upload- und Datenbankänderungsreaktionen\nServerlose Workflows mit Durable Functions",
                ],
            ],
            'container-apps' => [
                'summary' => 'Azure Container Apps betreibt containerisierte APIs, Worker, Jobs und Microservices serverlos.',
                'known_summary' => [
                    'Container Apps kombiniert Containerbetrieb mit serverloser Skalierung und eignet sich für APIs, Worker und Dapr-basierte Microservices.',
                    'Azure Container Apps ist eine serverlose Plattform für containerisierte APIs, Worker, Jobs und Microservices. Du bringst Containerimages mit, Azure übernimmt Infrastruktur, Ingress, Revisionen, Skalierung und optionale Dapr-Integration deutlich stärker als bei einem eigenen Kubernetes-Cluster.',
                    'Azure Container Apps ist eine serverlose Plattform für containerisierte APIs, Worker, Jobs und Microservices. Du bringst Containerimages mit; Azure übernimmt viele Infrastruktur-, Ingress-, Revisions-, Skalierungs- und Betriebsdetails. Der Dienst basiert auf Kubernetes-nahen Konzepten und Open-Source-Technologien wie KEDA, Dapr und Envoy, ohne dass du die Kubernetes-API direkt betreibst.',
                ],
                'content' => 'Azure Container Apps ist eine serverlose Plattform für containerisierte APIs, Worker, Jobs und Microservices. Du bringst Containerimages mit; Azure übernimmt viele Infrastruktur-, Ingress-, Revisions-, Skalierungs- und Betriebsdetails. Der Dienst basiert auf Kubernetes-nahen Konzepten und Open-Source-Technologien wie KEDA, Dapr und Envoy, ohne dass du die Kubernetes-API direkt betreibst.',
                'features' => "Skalierung über HTTP, TCP oder KEDA; Scale-to-zero ist möglich, aber nicht bei CPU-/Memory-basierten Regeln\nOhne Ingress brauchst du minReplicas ab 1 oder eine eigene Skalierungsregel, sonst kann die App auf null bleiben\nKein direkter Kubernetes-API-Zugriff; für vollständige Clusterkontrolle AKS prüfen\nRevisionen, Traffic-Splitting, Secrets, Managed Identity, Registry, VNet und Logging früh planen\nKosten entstehen je nach Plan durch aktive Ressourcen, Leerlaufreplikate, Anforderungen und ggf. Dedicated Workload Profiles",
                'use_cases' => "APIs und Web-Backends als Container\nBackground Worker und ereignisgetriebene Verarbeitung\nMicroservices mit Dapr und Service Discovery\nScheduled, manuelle oder eventbasierte Container Apps Jobs",
                'known_content' => [
                    'Container Apps kombiniert Containerbetrieb mit serverloser Skalierung und eignet sich für APIs, Worker und Dapr-basierte Microservices.',
                    'Container Apps skaliert anhand von HTTP/TCP, CPU/Memory oder KEDA-unterstützten Ereignisquellen und kann bei passenden Regeln bis auf null Replikate herunterfahren. Es eignet sich besonders, wenn du Container-Flexibilität brauchst, aber keinen AKS-Betrieb verantworten willst; plane Revisionsmodell, Secrets, Managed Identity, Registry, VNet, Logging, Mindestreplikate und Kosten für aktive bzw. Leerlauf-Replikate.',
                    'Container Apps skaliert über HTTP/TCP-Regeln oder KEDA-unterstützte Ereignisquellen und kann bei passenden Regeln bis auf null Replikate herunterfahren; CPU- oder Memory-basierte Skalierung eignet sich nicht für Scale-to-zero. Wenn du direkten Zugriff auf Kubernetes-API, Control Plane oder vollständige Clusterkonfiguration brauchst, ist AKS die passendere Plattform. Plane Revisionen, Traffic-Splitting, Secrets, Managed Identity, Registry-Zugriff, VNet, Logging, Mindest-/Maximalreplikate und Kosten für aktive oder leerlaufende Replikate.',
                ],
                'known_features' => [
                    "Container ohne Clusterbetrieb\nScale-to-zero möglich\nDapr-Integration",
                    "Serverlose Containerplattform mit HTTPS/TCP-Ingress und Revisionsmodell\nAutomatische Skalierung über KEDA; Scale-to-zero möglich, außer bei bestimmten Regeln wie CPU/Memory\nDapr-APIs für Service Invocation, Pub/Sub, State, Bindings, Secrets und Configuration verfügbar\nContainer aus öffentlichen oder privaten Registries inklusive Azure Container Registry\nKosten nach Plan, Ressourcen, Anforderungen und ggf. Leerlaufreplikaten kalkulieren",
                    "Serverlose Containerplattform mit HTTPS/TCP-Ingress, Revisionen und Traffic-Splitting\nAutomatische Skalierung über KEDA; Scale-to-zero möglich, aber nicht bei CPU-/Memory-Regeln\nDapr-APIs für Service Invocation, Pub/Sub, State, Bindings, Secrets und Configuration verfügbar\nKein direkter Kubernetes-API-Zugriff; bei vollem Clusterbetrieb AKS prüfen\nKosten nach Plan, Ressourcen, Anforderungen und ggf. Leerlaufreplikaten kalkulieren",
                ],
                'known_use_cases' => [
                    "APIs\nBackground Worker\nEvent-getriebene Microservices",
                    "APIs und Web-Backends als Container\nBackground Worker und ereignisgetriebene Verarbeitung\nMicroservices mit Dapr und Service Discovery\nScheduled, manuelle oder eventbasierte Container Apps Jobs\nContainerisierte Azure Functions oder kleine Plattformbausteine",
                ],
            ],
        ];

        $select = $db->prepare("SELECT id, summary, content, features, use_cases FROM {$prefix}m365azure_services WHERE slug = ?");
        $update = $db->prepare("UPDATE {$prefix}m365azure_services SET summary = ?, content = ?, features = ?, use_cases = ? WHERE id = ?");

        foreach ($updates as $slug => $data) {
            $select->execute([$slug]);
            $row = $select->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                continue;
            }

            $summary = self::value_if_known((string) ($row['summary'] ?? ''), (array) $data['known_summary'], (string) $data['summary']);
            $content = self::value_if_known((string) ($row['content'] ?? ''), (array) $data['known_content'], (string) $data['content']);
            $features = self::value_if_known((string) ($row['features'] ?? ''), (array) $data['known_features'], (string) $data['features']);
            $useCases = self::value_if_known((string) ($row['use_cases'] ?? ''), (array) $data['known_use_cases'], (string) $data['use_cases']);

            if (
                $summary === (string) ($row['summary'] ?? '')
                && $content === (string) ($row['content'] ?? '')
                && $features === (string) ($row['features'] ?? '')
                && $useCases === (string) ($row['use_cases'] ?? '')
            ) {
                continue;
            }

            $update->execute([$summary, $content, $features, $useCases, (int) $row['id']]);
        }

        $exists = $db->prepare("SELECT id FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $exists->execute([$markerKey]);
        if ($exists->fetch()) {
            $stmt = $db->prepare("UPDATE {$prefix}m365azure_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$markerVersion, $markerKey]);
        } else {
            $stmt = $db->prepare("INSERT INTO {$prefix}m365azure_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$markerKey, $markerVersion]);
        }
    }

    private static function upgrade_storage_content(object $db, string $prefix): void
    {
        $markerKey = 'content_storage_seed_version';
        $markerVersion = '2026-05-30-storage-v1';

        $markerStmt = $db->prepare("SELECT setting_value FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $markerStmt->execute([$markerKey]);
        if ((string) ($markerStmt->fetchColumn() ?: '') === $markerVersion) {
            return;
        }

        $updates = [
            'blob-storage' => [
                'subtitle' => 'Objektspeicher für unstrukturierte Daten.',
                'summary' => 'Azure Blob Storage speichert große Mengen unstrukturierter Daten wie Medien, Dokumente, Backups, Logs und Data-Lake-Rohdaten.',
                'content' => 'Azure Blob Storage ist Microsofts hochskalierbarer Objektspeicher für unstrukturierte Text- und Binärdaten. Der Dienst eignet sich für Browser-ausgelieferte Medien, verteilten Dateizugriff, Streaming, Logdaten, Backup, Archivierung und Analytics-Daten. Zugriff ist per HTTP/HTTPS, REST, SDKs, SFTP oder NFS 3.0 möglich; mit Data Lake Storage Gen2 kann Blob Storage auch als Big-Data-Dateisystem genutzt werden.',
                'features' => "Zugriffsebenen Hot, Cool, Cold, Archive und Smart Tier passend zu Nutzung und Aufbewahrung wählen\nCool, Cold und Archive haben niedrigere Speicherkosten, aber höhere Zugriffs-/Abrufkosten und Mindestaufbewahrungen\nArchive ist offline; Rehydration auf eine Online-Ebene kann bis zu 15 Stunden dauern\nLifecycle Management verschiebt oder löscht Blobs regelbasiert nach Erstellungs-, Änderungs- oder Zugriffszeit\nSchutzoptionen wie Soft Delete, Versioning, Snapshots, Point-in-Time Restore, Immutability und Azure Backup einplanen",
                'use_cases' => "Medien- und Dokumentenbibliotheken für Websites oder Portale\nBackup, Disaster Recovery und langfristige Archivierung\nData-Lake-Rohdaten für Analytics- und KI-Plattformen\nLog-, Telemetrie- und Exportdaten aus Anwendungen\nSFTP- oder NFS-basierter Datenaustausch über Storage Accounts",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/storage/blobs/storage-blobs-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/storage/blobs/',
                'known' => [
                    'summary' => ['Blob Storage speichert Bilder, Videos, Backups, Logs und Data-Lake-Dateien hochskalierbar und sicher.'],
                    'content' => ['Blob Storage speichert Bilder, Videos, Backups, Logs und Data-Lake-Dateien hochskalierbar und sicher.'],
                    'features' => ["Hot/Cool/Archive Tiers\nLifecycle Management\nStarke Integration in Analytics und Backup"],
                    'use_cases' => ["Medienbibliotheken\nBackups\nData Lake Rohdaten"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/storage/blobs/'],
                ],
            ],
            'azure-files' => [
                'subtitle' => 'Serverlose Dateifreigaben über SMB und NFS.',
                'summary' => 'Azure Files stellt vollständig verwaltete Dateifreigaben bereit, die Windows-, Linux- und macOS-Clients gleichzeitig nutzen können.',
                'content' => 'Azure Files bietet serverlose Dateifreigaben in Azure, die über SMB, NFS und die Azure Files REST API erreichbar sind. Der Dienst kann klassische File-Server oder NAS-Systeme ersetzen, Hybrid-Szenarien mit Azure File Sync unterstützen und Lift-and-Shift-Anwendungen einen vertrauten Dateipfad bereitstellen. Je nach Workload wählst du SMB oder NFS, SSD oder HDD, Redundanz, Identität, Netzwerkzugriff und Abrechnungsmodell.',
                'features' => "SMB- und NFS-Protokolle verfügbar; eine einzelne Freigabe unterstützt nicht beide Protokolle gleichzeitig\nSMB unterstützt identitätsbasierte Authentifizierung über AD DS, Microsoft Entra Domain Services oder Microsoft Entra Kerberos\nPort 445 und NFS-Netzwerkzugriff früh prüfen; für On-Prem-Zugriff oft VPN, ExpressRoute oder Private Endpoint nötig\nSSD für niedrige Latenz und I/O-intensive Workloads, HDD für kostengünstige allgemeine Dateifreigaben\nAzure File Sync kann SMB-Freigaben zentralisieren und lokale Windows Server als Cache nutzen",
                'use_cases' => "Ersatz oder Ergänzung lokaler File-Server und NAS-Systeme\nLift-and-Shift-Anwendungen mit gemeinsamem Dateispeicher\nFSLogix-Profile und Benutzerdateien in Azure Virtual Desktop\nGemeinsame Konfigurations-, Diagnose- und Tool-Freigaben für Cloud-Apps\nHybrid-Standorte mit lokalem Cache über Azure File Sync",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/storage/files/storage-files-introduction',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/storage/files/',
                'known' => [
                    'summary' => ['Azure Files ersetzt oder erweitert klassische File-Server und lässt sich in Windows-, Linux- und Hybridumgebungen einbinden.'],
                    'content' => ['Azure Files ersetzt oder erweitert klassische File-Server und lässt sich in Windows-, Linux- und Hybridumgebungen einbinden.'],
                    'features' => ["SMB/NFS-Freigaben\nAzure File Sync\nIntegration mit Entra-Identitäten"],
                    'use_cases' => ["File-Server-Ablösung\nLift-and-Shift\nGemeinsame App-Dateien"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/storage/files/'],
                ],
            ],
            'disk-storage' => [
                'subtitle' => 'Blockspeicher für virtuelle Maschinen.',
                'summary' => 'Azure Disk Storage stellt verwalteten Blockspeicher für Azure-VMs bereit – von kostengünstigen Standard-Datenträgern bis Ultra Disk.',
                'content' => 'Azure Managed Disks sind von Azure verwaltete Blockspeichervolumes für virtuelle Maschinen. Du wählst Datenträgertyp, Größe, Performance, Redundanz und Verschlüsselungsoptionen; Azure übernimmt Bereitstellung, Replikation und Integration in VM-Verfügbarkeit. Je nach Workload stehen Ultra Disk, Premium SSD v2, Premium SSD, Standard SSD und Standard HDD für Daten-, OS- und Spezialworkloads zur Verfügung.',
                'features' => "Fünf Datenträgertypen: Ultra Disk, Premium SSD v2, Premium SSD, Standard SSD und Standard HDD\nUltra Disk und Premium SSD v2 erlauben getrennte Anpassung von Kapazität, IOPS und Durchsatz, sind aber nicht als OS-Datenträger nutzbar\nManaged Disks nutzen standardmäßig serverseitige Verschlüsselung mit AES-256; kundenseitig verwaltete Schlüssel und Hostverschlüsselung sind möglich\nSnapshots, Images, Azure Backup, Wiederherstellungspunkte und Azure Site Recovery für Backup/DR planen\nKosten hängen von Typ, bereitgestellter Größe, IOPS/Durchsatz, Snapshots, Transaktionen, Shared Disks und Egress ab",
                'use_cases' => "Datenbank-VMs mit SQL Server, Oracle, SAP HANA oder MongoDB\nPersistente Datenlaufwerke für geschäftskritische IaaS-Anwendungen\nCluster-Szenarien mit Shared Disks und Failover-Software\nDev/Test-, Web- und wenig genutzte Workloads mit Standard SSD oder HDD\nHochleistungs-Blockstorage für transaktionsintensive Workloads",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/virtual-machines/managed-disks-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/managed-disks/',
                'known' => [
                    'summary' => ['Managed Disks bieten performanten und dauerhaften Speicher für VM-Workloads – von Standard bis Ultra Disk.'],
                    'content' => ['Managed Disks bieten performanten und dauerhaften Speicher für VM-Workloads – von Standard bis Ultra Disk.'],
                    'features' => ["Managed Disks\nPremium SSD und Ultra Disk\nSnapshots und Verschlüsselung"],
                    'use_cases' => ["Datenbank-VMs\nSAP-Workloads\nEnterprise-Anwendungen"],
                ],
            ],
        ];

        $fields = ['subtitle', 'summary', 'content', 'features', 'use_cases', 'docs_url', 'pricing_url'];
        $select = $db->prepare("SELECT id, subtitle, summary, content, features, use_cases, docs_url, pricing_url FROM {$prefix}m365azure_services WHERE slug = ?");
        $update = $db->prepare("UPDATE {$prefix}m365azure_services SET subtitle = ?, summary = ?, content = ?, features = ?, use_cases = ?, docs_url = ?, pricing_url = ? WHERE id = ?");

        foreach ($updates as $slug => $data) {
            $select->execute([$slug]);
            $row = $select->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                continue;
            }

            $values = [];
            foreach ($fields as $field) {
                $known = (array) ($data['known'][$field] ?? []);
                $values[$field] = self::value_if_known((string) ($row[$field] ?? ''), $known, (string) $data[$field]);
            }

            if (
                $values['subtitle'] === (string) ($row['subtitle'] ?? '')
                && $values['summary'] === (string) ($row['summary'] ?? '')
                && $values['content'] === (string) ($row['content'] ?? '')
                && $values['features'] === (string) ($row['features'] ?? '')
                && $values['use_cases'] === (string) ($row['use_cases'] ?? '')
                && $values['docs_url'] === (string) ($row['docs_url'] ?? '')
                && $values['pricing_url'] === (string) ($row['pricing_url'] ?? '')
            ) {
                continue;
            }

            $update->execute([
                $values['subtitle'],
                $values['summary'],
                $values['content'],
                $values['features'],
                $values['use_cases'],
                $values['docs_url'],
                $values['pricing_url'],
                (int) $row['id'],
            ]);
        }

        $exists = $db->prepare("SELECT id FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $exists->execute([$markerKey]);
        if ($exists->fetch()) {
            $stmt = $db->prepare("UPDATE {$prefix}m365azure_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$markerVersion, $markerKey]);
        } else {
            $stmt = $db->prepare("INSERT INTO {$prefix}m365azure_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$markerKey, $markerVersion]);
        }
    }

    private static function upgrade_database_content(object $db, string $prefix): void
    {
        $markerKey = 'content_database_seed_version';
        $markerVersion = '2026-05-30-database-v1';

        $markerStmt = $db->prepare("SELECT setting_value FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $markerStmt->execute([$markerKey]);
        if ((string) ($markerStmt->fetchColumn() ?: '') === $markerVersion) {
            return;
        }

        $updates = [
            'azure-sql-database' => [
                'subtitle' => 'Vollständig verwaltete relationale SQL-Datenbank.',
                'summary' => 'Azure SQL-Datenbank ist eine vollständig verwaltete PaaS-Datenbank für moderne Anwendungen, die SQL Server-Kompatibilität, automatische Wartung und integrierte Hochverfügbarkeit brauchen.',
                'content' => 'Azure SQL-Datenbank ist eine vollständig verwaltete relationale PaaS-Datenbank auf Basis der SQL Server Engine. Microsoft übernimmt Patching, Backups, Hochverfügbarkeit, Monitoring-Grundlagen und Plattformwartung, während du Schema, Datenmodell, Sicherheit, Performance und Kosten steuerst. Für neue Workloads sind vCore-Modelle mit General Purpose, Business Critical und Hyperscale relevant; Single Databases, Elastic Pools und Serverless oder Provisioned Compute müssen passend zu Lastprofil und Mandantenmodell gewählt werden.',
                'features' => "vCore-Modell empfohlen; DTU nur bei einfachen vorkonfigurierten Ressourcenkategorien prüfen\nHyperscale ist für viele Business-Workloads die empfohlene Ebene und skaliert Speicher deutlich größer als klassische Ebenen\nServerless eignet sich für variable Einzel-Datenbanken; Provisioned Compute für planbare Dauerlast\nZonenredundanz, Failovergruppen, aktive Georeplikation und Point-in-Time Restore früh nach RTO/RPO planen\nCompute, Speicher, Backup-Aufbewahrung, Replikate, Egress und ggf. Lizenzvorteile separat kalkulieren",
                'use_cases' => "Cloudnative Web- und Geschäftsanwendungen mit relationalem Datenmodell\nSaaS-Anwendungen mit Single Databases oder Elastic Pools\nSQL Server Modernisierung ohne eigenen Serverbetrieb\nTransaktionssysteme mit hohen Verfügbarkeits- und Sicherheitsanforderungen\nRead-Scale-, Reporting- und Geo-DR-Szenarien mit Replikaten",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/azure-sql/database/',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/azure-sql-database/single/',
                'known' => [
                    'summary' => ['Azure SQL-Datenbank eignet sich für moderne Apps, die SQL Server-Kompatibilität, hohe Verfügbarkeit und automatische Verwaltung benötigen.'],
                    'content' => ['Azure SQL-Datenbank eignet sich für moderne Apps, die SQL Server-Kompatibilität, hohe Verfügbarkeit und automatische Verwaltung benötigen.'],
                    'features' => ["Automatische Patches\nHohe Verfügbarkeit\nSkalierbare Leistungsebenen"],
                    'use_cases' => ["Web-Apps\nGeschäftsanwendungen\nSaaS-Datenbanken"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/azure-sql/database/'],
                ],
            ],
            'cosmos-db' => [
                'subtitle' => 'Global verteilte NoSQL- und Vektordatenbank.',
                'summary' => 'Azure Cosmos DB ist eine vollständig verwaltete, global verteilbare NoSQL- und Vektordatenbank für Anwendungen mit niedriger Latenz, elastischer Skalierung und flexiblen Datenmodellen.',
                'content' => 'Azure Cosmos DB unterstützt Betriebsdatenmodelle wie Dokument, Schlüsselwert, Graph, Tabelle und Vektor sowie APIs wie NoSQL, MongoDB, Cassandra, Gremlin und Table. Der Dienst ist auf niedrige Latenz, globale Verteilung, automatische Indizierung, Multi-Region-Lesen und -Schreiben sowie skalierbare Durchsatzmodelle ausgelegt. Kosten und Architektur hängen stark von Partitionierung, Request Units, Konsistenzmodell, Regionen, Speicher, Sicherung und gewähltem Compute- oder Durchsatzmodell ab.',
                'features' => "RU/s sind zentrale Kapazitäts- und Kosteneinheit; Itemgröße, Indexierung, Abfragen und Konsistenz beeinflussen Verbrauch\nProvisioned Throughput, Autoscale und Serverless bewusst nach Lastprofil wählen\nGlobale Verteilung repliziert Durchsatz und Speicher pro Region; Multi-Region-Write erhöht Verfügbarkeit und Kosten\nPartitionsschlüssel früh sauber modellieren, weil er Skalierung, Hot Partitions und Abfragekosten prägt\nNicht ideal für stark relationale OLTP-Modelle oder klassische OLAP-Analysen; dafür eher Azure SQL oder Analytics-Plattform prüfen",
                'use_cases' => "Globale Web-, Commerce-, Gaming- und Personalisierungsanwendungen\nIoT-/Telemetrie-Daten mit hohem Schreibdurchsatz\nKI-/RAG-Anwendungen mit operativen Daten und Vektorsuche\nEvent-getriebene Architekturen mit Change Feed\nHochverfügbare Anwendungen mit Multi-Region-Lesen oder -Schreiben",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/cosmos-db/',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/cosmos-db/',
                'known' => [
                    'subtitle' => ['Global verteilte NoSQL-Datenbank.'],
                    'summary' => ['Cosmos DB bietet niedrige Latenz, globale Replikation und mehrere APIs für moderne, verteilte Anwendungen.'],
                    'content' => ['Cosmos DB bietet niedrige Latenz, globale Replikation und mehrere APIs für moderne, verteilte Anwendungen.'],
                    'features' => ["Globale Verteilung\nMehrere APIs\nVektor- und KI-Szenarien"],
                    'use_cases' => ["Personalisierung\nIoT-Daten\nGlobale Apps"],
                ],
            ],
            'postgresql' => [
                'subtitle' => 'Verwalteter PostgreSQL Flexible Server.',
                'summary' => 'Azure Database for PostgreSQL stellt PostgreSQL als vollständig verwalteten Flexible Server bereit, mit Kontrolle über Compute, Speicher, Wartungsfenster, Hochverfügbarkeit und Sicherheit.',
                'content' => 'Azure Database for PostgreSQL – Flexible Server ist Microsofts verwalteter PostgreSQL-Dienst auf Basis der Community-Version. Du behältst PostgreSQL-Kompatibilität, Konfigurationsparameter und Erweiterungen, während Azure Patching, automatische Backups, Verschlüsselung, Monitoring-Integration und Betriebsfunktionen bereitstellt. Für produktive Workloads sollten Compute-Tier, Speicher/IOPS, Wartungsfenster, private Netzwerkanbindung, HA-Modell und Backup-/DR-Strategie bewusst festgelegt werden.',
                'features' => "Flexible Server ist der relevante Standard; Single-Server-Workloads auf Flexible Server migrieren\nCompute-Tiers Burstable, General Purpose und Memory Optimized passend zur Last wählen\nBurstable eher für Dev/Test oder unregelmäßige geringe Last; für 24/7-Produktion General Purpose oder Memory Optimized bevorzugen\nZonenredundante HA benötigt General Purpose oder Memory Optimized und erzeugt Kosten für Standby-Ressourcen\nAutomatische Backups standardmäßig 7 Tage, bis 35 Tage konfigurierbar; langfristige Sicherung und Geo-DR separat planen",
                'use_cases' => "PostgreSQL-Web-Backends und Fachanwendungen\nModernisierung bestehender PostgreSQL-Workloads aus On-Premises, VM oder anderen Clouds\nDatenbank für Open-Source-Stacks, PHP-, Python-, Node.js- und .NET-Anwendungen\nProduktionsdatenbanken mit privaten Netzwerken, HA und automatischen Backups\nKI-nahe Anwendungen mit PostgreSQL-Erweiterungen, Vektor- oder Suchszenarien",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/postgresql/flexible-server/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/postgresql/flexible-server/',
                'known' => [
                    'subtitle' => ['Verwaltete PostgreSQL-Datenbank.'],
                    'summary' => ['Der Dienst modernisiert PostgreSQL-Workloads mit automatischer Verwaltung, Skalierung und Sicherheitsfunktionen.'],
                    'content' => ['Der Dienst modernisiert PostgreSQL-Workloads mit automatischer Verwaltung, Skalierung und Sicherheitsfunktionen.'],
                    'features' => ["Flexible Server\nBackups und Hochverfügbarkeit\nOpen-Source-Kompatibilität"],
                    'use_cases' => ["Web-Backends\nData Apps\nKI-nahe Datenhaltung"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/postgresql/'],
                ],
            ],
        ];

        $fields = ['subtitle', 'summary', 'content', 'features', 'use_cases', 'docs_url', 'pricing_url'];
        $select = $db->prepare("SELECT id, subtitle, summary, content, features, use_cases, docs_url, pricing_url FROM {$prefix}m365azure_services WHERE slug = ?");
        $update = $db->prepare("UPDATE {$prefix}m365azure_services SET subtitle = ?, summary = ?, content = ?, features = ?, use_cases = ?, docs_url = ?, pricing_url = ? WHERE id = ?");

        foreach ($updates as $slug => $data) {
            $select->execute([$slug]);
            $row = $select->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                continue;
            }

            $values = [];
            foreach ($fields as $field) {
                $known = (array) ($data['known'][$field] ?? []);
                $values[$field] = self::value_if_known((string) ($row[$field] ?? ''), $known, (string) $data[$field]);
            }

            if (
                $values['subtitle'] === (string) ($row['subtitle'] ?? '')
                && $values['summary'] === (string) ($row['summary'] ?? '')
                && $values['content'] === (string) ($row['content'] ?? '')
                && $values['features'] === (string) ($row['features'] ?? '')
                && $values['use_cases'] === (string) ($row['use_cases'] ?? '')
                && $values['docs_url'] === (string) ($row['docs_url'] ?? '')
                && $values['pricing_url'] === (string) ($row['pricing_url'] ?? '')
            ) {
                continue;
            }

            $update->execute([
                $values['subtitle'],
                $values['summary'],
                $values['content'],
                $values['features'],
                $values['use_cases'],
                $values['docs_url'],
                $values['pricing_url'],
                (int) $row['id'],
            ]);
        }

        $exists = $db->prepare("SELECT id FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $exists->execute([$markerKey]);
        if ($exists->fetch()) {
            $stmt = $db->prepare("UPDATE {$prefix}m365azure_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$markerVersion, $markerKey]);
        } else {
            $stmt = $db->prepare("INSERT INTO {$prefix}m365azure_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$markerKey, $markerVersion]);
        }
    }

    private static function upgrade_ai_ml_content(object $db, string $prefix): void
    {
        $markerKey = 'content_ai_ml_seed_version';
        $markerVersion = '2026-05-30-ai-ml-v1';

        $markerStmt = $db->prepare("SELECT setting_value FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $markerStmt->execute([$markerKey]);
        if ((string) ($markerStmt->fetchColumn() ?: '') === $markerVersion) {
            return;
        }

        $updates = [
            'azure-ai-foundry' => [
                'subtitle' => 'Plattform für KI-Apps, Modelle, Agents und Governance.',
                'summary' => 'Microsoft Foundry ist die Azure-Plattform zum Erstellen, Evaluieren, Bereitstellen und Betreiben generativer KI-Apps, Agenten und Modelllösungen mit Modellkatalog, Tools, Observability und Governance.',
                'content' => 'Microsoft Foundry bündelt Modellkatalog, Azure OpenAI-/Foundry-Modelle, Agent Service, Foundry Tools, Evaluation, Tracing/Observability und Guardrails in Projekten unter einer Foundry-Ressource. Teams können Prompts, RAG, Agents und Modellbereitstellungen entwickeln, überwachen und governancenah betreiben. Die Plattform ist kein einzelner Pauschaldienst: Kosten, Verfügbarkeit und Datenverarbeitung hängen von den verwendeten Modellen, Deploymenttypen, Tools, Regionen und abhängigen Azure-Diensten ab.',
                'features' => "Neues Ressourcenmodell mit Foundry-Ressource und Projekten; ältere Azure AI Foundry/Azure AI Studio-Bezeichnungen können noch in Doku und Portalen auftauchen\nModell-, Agent- und Tool-Verfügbarkeit variiert je Region, Modell, Deploymenttyp und Kontingent; Zielregion vor Produktivstart prüfen\nKosten entstehen über genutzte Modelle, Azure OpenAI, Foundry Tools, Agenten, Evaluation, Monitoring und abhängige Ressourcen; Preisrechner dienstweise verwenden\nFoundry-RBAC-Rollen wurden umbenannt; Rollen-IDs und Kernberechtigungen bleiben laut Microsoft erhalten\nUpgrade von Azure OpenAI auf Foundry ist opt-in und behält Endpunkt, Keys und Konfigurationen, hat aber Einschränkungen bei CMK, Private Link/DNS und Feature-/Regionverfügbarkeit",
                'use_cases' => "Enterprise-Copilots und KI-Agenten mit Governance\nRAG-Anwendungen mit Evaluierung und Observability\nModellkatalog-, Prompt- und Deployment-Management\nKI-Plattform für mehrere Teams, Projekte und Kostenstellen\nPrototyping bis Produktionsbetrieb generativer KI-Lösungen",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/foundry/what-is-foundry',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/microsoft-foundry/',
                'known' => [
                    'subtitle' => ['Plattform für KI-Apps, Modelle und Agenten.'],
                    'summary' => ['Microsoft Foundry bündelt Modelle, Tools, Sicherheit, Observability und Agentenentwicklung für produktive KI-Lösungen.'],
                    'content' => ['Microsoft Foundry bündelt Modelle, Tools, Sicherheit, Observability und Agentenentwicklung für produktive KI-Lösungen.'],
                    'features' => ["Modellkatalog\nAgentenentwicklung\nGovernance und Monitoring"],
                    'use_cases' => ["KI-Agenten\nRAG-Anwendungen\nEnterprise Copilots"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/ai-foundry/', 'https://learn.microsoft.com/de-de/azure/foundry/'],
                    'pricing_url' => ['https://azure.microsoft.com/de-de/pricing/details/ai-foundry/'],
                ],
            ],
            'azure-openai' => [
                'subtitle' => 'Generative OpenAI-Modelle mit Azure-Governance.',
                'summary' => 'Azure OpenAI Service stellt OpenAI-Modelle in Azure bereit – von Chat, Reasoning, Embeddings und Multimodalität bis Audio, Bild/Video und agentsnahe APIs – mit Enterprise-Sicherheit, Quotas und Azure-Integration.',
                'content' => 'Azure OpenAI Service beziehungsweise Azure OpenAI in Microsoft Foundry Models bietet Zugriff auf von Azure gehostete OpenAI-Modelle für Text, Code, Reasoning, Embeddings, Bild, Audio und Realtime-Szenarien. Modelle werden als Deployments bereitgestellt; Bereitstellungstypen wie Global Standard, Data Zone, Regional, Provisioned und Batch bestimmen Datenverarbeitung, Latenz, Durchsatz und Kosten. Für produktive Lösungen sind Modellversionen, Content Filter, Quotas, Rate Limits, Datenzonen, Monitoring und Kostensteuerung zentrale Architekturentscheidungen.',
                'features' => "Modellverfügbarkeit, Kontextlängen und Features variieren nach Region, Deploymenttyp und Modellversion; Vorschau-Modelle nicht ungeprüft produktiv nutzen\nTPM/RPM-Kontingente gelten pro Abonnement, Region, Modell und Deploymenttyp; 429-Fehler trotz scheinbar freiem Tokenbudget einplanen\nDeploymenttypen Global, Data Zone, Regional, Provisioned und Batch unterscheiden Datenverarbeitung, SLA, Latenz, Durchsatz und Preis\nContent Filtering, Prompt Shields, geschütztes Material, PII- und Abuse-Monitoring in App-Design und Fehlerbehandlung berücksichtigen\nKosten entstehen token-, batch-, PTU-, Fine-Tuning-, Tool- oder Audio/Bild/Video-spezifisch; Modellmix und Caching/Batches aktiv optimieren",
                'use_cases' => "Chatbots und interne Wissensassistenten\nText-, Code-, Bild-, Audio- und Realtime-Generierung\nRAG mit Azure AI Search und Unternehmensdaten\nAutomatisierung mit Function Calling, Tools und Agents\nEmbedding-Pipelines, Klassifikation, Extraktion und Zusammenfassung",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/foundry/foundry-models/concepts/models-sold-directly-by-azure?pivots=azure-openai',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/azure-openai/',
                'known' => [
                    'subtitle' => ['Fortschrittliche Sprach- und Codemodelle in Azure.'],
                    'summary' => ['Azure OpenAI ermöglicht generative KI mit Enterprise-Sicherheit, Datenschutz und Integration in Azure-Datenquellen.'],
                    'content' => ['Azure OpenAI ermöglicht generative KI mit Enterprise-Sicherheit, Datenschutz und Integration in Azure-Datenquellen.'],
                    'features' => ["GPT-Modelle\nEnterprise-Security\nIntegration in Foundry"],
                    'use_cases' => ["Chatbots\nContent-Erstellung\nCode-Assistenz"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/ai-services/openai/', 'https://learn.microsoft.com/de-de/azure/ai-services/openai/overview'],
                    'pricing_url' => ['https://azure.microsoft.com/de-de/pricing/details/cognitive-services/openai-service/'],
                ],
            ],
            'azure-ai-search' => [
                'subtitle' => 'Such- und Retrieval-Schicht für Apps, Agents und RAG.',
                'summary' => 'Azure AI Search ist ein vollständig verwalteter Such- und Retrieval-Dienst für Volltext-, Vektor-, Hybrid-, semantische und agentische Suche über Unternehmensdaten.',
                'content' => 'Azure AI Search verbindet Unternehmensdaten mit klassischen Suchanwendungen, Chatbots und generativen KI-Lösungen. Der Dienst indexiert JSON-Dokumente aus Push- oder Pull-Pipelines, unterstützt Volltextsuche, Vektorsuche, Hybridsuche, semantische Rangfolge, KI-Anreicherung und agentischen Abruf für komplexe RAG-Szenarien. Für sichere Enterprise-Lösungen sind Indexdesign, Chunking, Vektorisierung, SKU/Suchunits, regionale Featureverfügbarkeit, Private Link, Entra ID/RBAC und Security Trimming entscheidend.',
                'features' => "Volltext-, Vektor-, Hybrid-, multimodale und semantische Suche; Vektorsuche selbst ist kostenlos, Embeddings/KI-Anreicherung können extra kosten\nSemantischer Ranker rerankt nur die Top-50-Ergebnisse und erzeugt keine neuen Inhalte; Captions/Answers stammen wortgetreu aus dem Index\nAgentic Retrieval nutzt Wissensquellen, Knowledge Bases und optional LLM-gestützte Query-Planung; Abrechnung kann Search- und Modellkosten kombinieren\nGrenzwerte hängen stark von SKU, Region, Erstellungsdatum, Partitionen, Replikaten und Vektorquoten ab; ältere Dienste ggf. upgraden oder neu erstellen\nTLS, AES-256, Datenresidenz, Private Link, Entra ID/RBAC, CMK und Security Trimming für geschützte Inhalte einplanen",
                'use_cases' => "Enterprise Search für Portale, Apps und Intranets\nRAG-Grounding für Copilots, Agents und Chatbots\nDokumenten-, SharePoint-, Blob-, Cosmos-DB- und OneLake-Suche\nVektor- und Hybridsuche über Wissensdatenbanken\nSicherheitsgetrimmter Zugriff auf vertrauliche Inhalte",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/search/search-what-is-azure-search',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/search/',
                'known' => [
                    'summary' => ['Azure AI Search verbindet Datenquellen mit Volltextsuche, Vektorsuche und Retrieval-Augmented-Generation-Szenarien.'],
                    'content' => ['Azure AI Search verbindet Datenquellen mit Volltextsuche, Vektorsuche und Retrieval-Augmented-Generation-Szenarien.'],
                    'features' => ["Vektorsuche\nIndexierung\nRAG-Pipelines"],
                    'use_cases' => ["Wissenssuche\nDokumentenportale\nCopilot-Datenbasis"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/search/'],
                ],
            ],
        ];

        $fields = ['subtitle', 'summary', 'content', 'features', 'use_cases', 'docs_url', 'pricing_url'];
        $select = $db->prepare("SELECT id, subtitle, summary, content, features, use_cases, docs_url, pricing_url FROM {$prefix}m365azure_services WHERE slug = ?");
        $update = $db->prepare("UPDATE {$prefix}m365azure_services SET subtitle = ?, summary = ?, content = ?, features = ?, use_cases = ?, docs_url = ?, pricing_url = ? WHERE id = ?");

        foreach ($updates as $slug => $data) {
            $select->execute([$slug]);
            $row = $select->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                continue;
            }

            $values = [];
            foreach ($fields as $field) {
                $known = (array) ($data['known'][$field] ?? []);
                $values[$field] = self::value_if_known((string) ($row[$field] ?? ''), $known, (string) $data[$field]);
            }

            if (
                $values['subtitle'] === (string) ($row['subtitle'] ?? '')
                && $values['summary'] === (string) ($row['summary'] ?? '')
                && $values['content'] === (string) ($row['content'] ?? '')
                && $values['features'] === (string) ($row['features'] ?? '')
                && $values['use_cases'] === (string) ($row['use_cases'] ?? '')
                && $values['docs_url'] === (string) ($row['docs_url'] ?? '')
                && $values['pricing_url'] === (string) ($row['pricing_url'] ?? '')
            ) {
                continue;
            }

            $update->execute([
                $values['subtitle'],
                $values['summary'],
                $values['content'],
                $values['features'],
                $values['use_cases'],
                $values['docs_url'],
                $values['pricing_url'],
                (int) $row['id'],
            ]);
        }

        $exists = $db->prepare("SELECT id FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $exists->execute([$markerKey]);
        if ($exists->fetch()) {
            $stmt = $db->prepare("UPDATE {$prefix}m365azure_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$markerVersion, $markerKey]);
        } else {
            $stmt = $db->prepare("INSERT INTO {$prefix}m365azure_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$markerKey, $markerVersion]);
        }
    }

    private static function upgrade_devops_content(object $db, string $prefix): void
    {
        $markerKey = 'content_devops_seed_version';
        $markerVersion = '2026-05-30-devops-v1';

        $markerStmt = $db->prepare("SELECT setting_value FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $markerStmt->execute([$markerKey]);
        if ((string) ($markerStmt->fetchColumn() ?: '') === $markerVersion) {
            return;
        }

        $updates = [
            'azure-devops' => [
                'subtitle' => 'Planung, Code, CI/CD, Tests und Pakete in einer Plattform.',
                'summary' => 'Azure DevOps bündelt Boards, Repos, Pipelines, Test Plans, Artifacts und Dashboards für den Software-Lifecycle von Planung bis Deployment.',
                'content' => 'Azure DevOps ist eine integrierte Entwicklungsplattform für Enterprise-Teams, die Arbeit planen, Quellcode verwalten, Builds automatisieren, Releases steuern, Tests nachverfolgen und Pakete verteilen müssen. Azure Boards, Repos, Pipelines, Test Plans und Artifacts greifen ineinander, bleiben aber einzeln nutzbar. Für regulierte Umgebungen sind Organisationsgeographie, Microsoft Entra ID, Berechtigungen, Branch Policies, Pipeline-Sicherheit und Paralleljobs die zentralen Planungsgrößen.',
                'features' => "Azure DevOps Services speichert Kundendaten grundsätzlich in der gewählten Geographie; Token-Daten liegen laut Microsoft in den USA, macOS-Agenten können Daten in ein GitHub-Rechenzentrum in den USA übertragen\nÖffentliche Projekte werden eingestellt: neue öffentliche Projekte sind nicht mehr möglich, bestehende werden 2027 in private Projekte konvertiert\nPipeline-Kapazität hängt von Paralleljobs ab; kostenlose Kontingente können bei neuen Organisationen nicht automatisch aktiv sein und müssen ggf. beantragt werden\nBasic enthält die ersten 5 Benutzer kostenlos; Test Plans, zusätzliche Paralleljobs, Artifacts-Speicher über 2 GiB und GitHub Advanced Security werden separat bewertet\nFür Automatisierung Microsoft Entra OAuth, Dienstprinzipale oder verwaltete Identitäten bevorzugen; PATs nur kontrolliert und mit Richtlinien nutzen",
                'use_cases' => "CI/CD für Azure, Multicloud und On-Premises mit Genehmigungen\nAgile Planung, Backlogs, Boards und Release-Transparenz\nPrivate Git-Repositories mit Pull Requests und Branch Policies\nPaketfeeds für NuGet, npm, Maven, Python und interne Komponenten\nManuelle und explorative Tests mit Rückverfolgbarkeit zu Anforderungen",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/devops/user-guide/what-is-azure-devops?view=azure-devops',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/devops/azure-devops-services/',
                'known' => [
                    'subtitle' => ['Boards, Repos, Pipelines, Tests und Artefakte.'],
                    'summary' => ['Azure DevOps unterstützt Teams bei Planung, Codeverwaltung, CI/CD und Qualitätssicherung.'],
                    'content' => ['Azure DevOps unterstützt Teams bei Planung, Codeverwaltung, CI/CD und Qualitätssicherung.'],
                    'features' => ["Boards und Repos\nPipelines\nTest Plans und Artifacts"],
                    'use_cases' => ["CI/CD\nAgile Planung\nEnterprise DevOps"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/devops/'],
                ],
            ],
            'dev-box' => [
                'subtitle' => 'Vorkonfigurierte Cloud-Workstations für Entwicklerteams.',
                'summary' => 'Microsoft Dev Box stellt vorkonfigurierte Cloud-Entwicklungsarbeitsplätze über Dev Center, Projekte und Pools bereit; Microsoft empfiehlt für neue virtualisierte Entwicklerumgebungen inzwischen Windows 365.',
                'content' => 'Microsoft Dev Box gibt Entwicklern über ein Portal Zugriff auf vorkonfigurierte Windows-Cloud-Workstations, die aus Dev Box-Pools mit definiertem Image, Compute, Speicher und Netzwerk entstehen. Plattformteams steuern Dev Center, Projekte, Pools, Kataloge, Image-Definitionen, Netzwerke und Rollen; die Dev Boxes werden über Microsoft Intune verwaltet und über Azure Virtual Desktop-Konnektivität erreicht. Der Dienst ist weiterhin unterstützt, befindet sich laut Microsoft aber im Wartungsmodus ohne geplante neue Features, daher sollte Windows 365 für neue strategische Entwickler-Cloudumgebungen geprüft werden.',
                'features' => "Stand/Hinweis: Microsoft Dev Box ist im Wartungsmodus; für neue virtualisierte Entwicklerumgebungen nennt Microsoft Windows 365 als empfohlenen Pfad\nBenutzer benötigen passende Windows Enterprise-, Microsoft Intune- und Microsoft Entra ID P1-Lizenzen; viele Microsoft 365-Pläne enthalten diese Voraussetzungen\nGeschäfts- und Schulkonten werden unterstützt; Gastzugriff über Microsoft Entra B2B wurde eingestellt\nDie Netzwerkverbindung bestimmt die Hosting-Region: Microsoft-gehostet für reine Cloud-Szenarien, Azure-Netzwerkverbindung für eigenes VNet, Hybrid Join oder Zugriff auf Unternehmensressourcen\nAbrechnung kombiniert Lizenzvoraussetzungen, Speicher pro Dev Box und aktive Compute-Stunden bis zum monatlichen Maximalpreis; Autostopp und Ruhezustand konsequent nutzen",
                'use_cases' => "Standardisierte Entwicklerumgebungen für neue Mitarbeitende und Projektteams\nIsolierte Workstations für Auftragnehmer, sensible Repositories oder Kundensysteme\nRegionale Cloud-Workstations für verteilte Entwicklerteams mit niedrigerer Latenz\nMehrere getrennte Arbeitsumgebungen pro Entwickler für parallele Projekte\nReproduzierbare Toolchains über Image-Definitionen, Kataloge und Intune-Richtlinien",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/dev-box/overview-what-is-microsoft-dev-box',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/dev-box/',
                'known' => [
                    'subtitle' => ['Cloudbasierte Entwicklungsarbeitsplätze.'],
                    'summary' => ['Dev Box stellt vorkonfigurierte, sichere Entwicklungsumgebungen bereit, damit Teams schneller starten und konsistent arbeiten.'],
                    'content' => ['Dev Box stellt vorkonfigurierte, sichere Entwicklungsumgebungen bereit, damit Teams schneller starten und konsistent arbeiten.'],
                    'features' => ["Ready-to-code Umgebungen\nZentrale Verwaltung\nSkalierbare Entwicklerplätze"],
                    'use_cases' => ["Onboarding\nStandardisierte Entwicklungsumgebungen\nRemote Development"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/dev-box/'],
                ],
            ],
        ];

        $fields = ['subtitle', 'summary', 'content', 'features', 'use_cases', 'docs_url', 'pricing_url'];
        $select = $db->prepare("SELECT id, subtitle, summary, content, features, use_cases, docs_url, pricing_url FROM {$prefix}m365azure_services WHERE slug = ?");
        $update = $db->prepare("UPDATE {$prefix}m365azure_services SET subtitle = ?, summary = ?, content = ?, features = ?, use_cases = ?, docs_url = ?, pricing_url = ? WHERE id = ?");

        foreach ($updates as $slug => $data) {
            $select->execute([$slug]);
            $row = $select->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                continue;
            }

            $values = [];
            foreach ($fields as $field) {
                $known = (array) ($data['known'][$field] ?? []);
                $values[$field] = self::value_if_known((string) ($row[$field] ?? ''), $known, (string) $data[$field]);
            }

            if (
                $values['subtitle'] === (string) ($row['subtitle'] ?? '')
                && $values['summary'] === (string) ($row['summary'] ?? '')
                && $values['content'] === (string) ($row['content'] ?? '')
                && $values['features'] === (string) ($row['features'] ?? '')
                && $values['use_cases'] === (string) ($row['use_cases'] ?? '')
                && $values['docs_url'] === (string) ($row['docs_url'] ?? '')
                && $values['pricing_url'] === (string) ($row['pricing_url'] ?? '')
            ) {
                continue;
            }

            $update->execute([
                $values['subtitle'],
                $values['summary'],
                $values['content'],
                $values['features'],
                $values['use_cases'],
                $values['docs_url'],
                $values['pricing_url'],
                (int) $row['id'],
            ]);
        }

        $exists = $db->prepare("SELECT id FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $exists->execute([$markerKey]);
        if ($exists->fetch()) {
            $stmt = $db->prepare("UPDATE {$prefix}m365azure_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$markerVersion, $markerKey]);
        } else {
            $stmt = $db->prepare("INSERT INTO {$prefix}m365azure_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$markerKey, $markerVersion]);
        }
    }

    private static function upgrade_network_security_content(object $db, string $prefix): void
    {
        $markerKey = 'content_network_security_seed_version';
        $markerVersion = '2026-05-30-network-security-v1';

        $markerStmt = $db->prepare("SELECT setting_value FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $markerStmt->execute([$markerKey]);
        if ((string) ($markerStmt->fetchColumn() ?: '') === $markerVersion) {
            return;
        }

        $updates = [
            'virtual-network' => [
                'subtitle' => 'Private Netzwerkgrundlage für Azure- und Hybrid-Architekturen.',
                'summary' => 'Azure Virtual Network bildet private Netzwerkbereiche für Azure-Ressourcen, Subnetze, Routing, Peering, Private Link, Dienstendpunkte und Hybridkonnektivität.',
                'content' => 'Azure Virtual Network ist die private Netzwerkbasis in Azure. Du definierst IP-Adressräume, Subnetze, Routing, Netzwerksicherheitsgruppen und Verbindungen zu anderen VNets, Azure-Diensten oder lokalen Netzwerken. Der Dienst selbst ist kostenlos, aber Peering, ausgehender Datenverkehr, Flow Logs, Private Link, Gateways und verbundene Dienste können kostenrelevant werden.',
                'features' => "VNet selbst ist kostenlos; Peering wird für eingehenden und ausgehenden Datenverkehr berechnet\nSubnetze früh planen: kleinster Bereich /29, Azure reserviert mehrere Adressen pro Subnetz\nPeering nutzt das Microsoft-Backbone, ersetzt aber keine saubere IP-Adressplanung und keine Firewall-Strategie\nNSG-Regeln sind zustandsbehaftet; Standardregeln bleiben vorhanden und werden über Prioritäten überschrieben\nNSG Flow Logs werden abgelöst; für neue Projekte VNet Flow Logs mit Network Watcher planen",
                'use_cases' => "Landing Zones und Hub-Spoke-Netzwerke\nApp-, Datenbank- und Plattformsegmentierung über Subnetze und NSGs\nHybridkonnektivität über VPN Gateway oder ExpressRoute\nPrivate Endpoints und Dienstendpunkte für PaaS-Zugriff\nNetzwerkflussanalyse mit VNet Flow Logs und SIEM-Anbindung",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/virtual-network/virtual-networks-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/virtual-network/',
                'known' => [
                    'subtitle' => ['Private Netzwerkgrundlage in Azure.'],
                    'summary' => ['Virtual Network verbindet Ressourcen sicher miteinander und bildet die Basis für Subnetze, Routing, Peering und Hybridkonnektivität.'],
                    'content' => ['Virtual Network verbindet Ressourcen sicher miteinander und bildet die Basis für Subnetze, Routing, Peering und Hybridkonnektivität.'],
                    'features' => ["Subnetze und Peering\nPrivate IP-Kommunikation\nNetzwerksicherheitsgruppen"],
                    'use_cases' => ["Landing Zones\nApp-Netzwerke\nHybrid-Topologien"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/virtual-network/'],
                ],
            ],
            'azure-firewall' => [
                'subtitle' => 'Cloudnative Firewall für zentrale Netzwerk- und Egress-Kontrolle.',
                'summary' => 'Azure Firewall ist ein verwalteter, zustandsbehafteter Firewall-Dienst für Azure Virtual Network, Hub-Spoke-Architekturen und kontrollierten Nord-Süd- sowie Ost-West-Datenverkehr.',
                'content' => 'Azure Firewall schützt virtuelle Netzwerke mit zentralen Regeln, Threat Intelligence, Protokollierung und integrierter Hochverfügbarkeit. Basic, Standard und Premium unterscheiden sich deutlich bei Durchsatz, DNS Proxy, FQDN-/Webkategorie-Filterung, TLS Inspection, IDPS und URL Filtering. Für produktive Architekturen musst du SKU, Routing, AzureFirewallSubnet, SNAT-Kapazität, Logging und Kosten vorab sauber dimensionieren.',
                'features' => "SKUs bewusst wählen: Basic für kleine Szenarien, Standard für L3-L7-Filterung, Premium für TLS Inspection, IDPS und URL Filtering\nAzureFirewallSubnet mindestens /26 planen; Netzwerksicherheitsgruppen auf diesem Subnetz werden nicht unterstützt\nHub-Spoke pro Region bevorzugen; globales Peering über Regionen kann Latenz, Performance und Kosten verschlechtern\nDNAT mit erzwungenem Tunneling und IPv6 sind laut aktuellen Hinweisen nicht unterstützt\nPreise bestehen aus Bereitstellungsstunden, verarbeitetem Datenvolumen und optionalen Kapazitätseinheiten",
                'use_cases' => "Zentraler Internet-Egress für Azure-Workloads\nHub-Spoke-Segmentierung zwischen VNets und Subnetzen\nRegulierte Umgebungen mit zentralen Firewall Policies und Logs\nThreat-Intelligence-basierte Warnung oder Blockierung\nPremium-Szenarien mit TLS Inspection, IDPS und URL Filtering",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/firewall/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/azure-firewall/',
                'known' => [
                    'subtitle' => ['Cloudnative Netzwerk-Firewall.'],
                    'summary' => ['Azure Firewall schützt virtuelle Netzwerke mit zentralen Regeln, Protokollierung und integrierter Hochverfügbarkeit.'],
                    'content' => ['Azure Firewall schützt virtuelle Netzwerke mit zentralen Regeln, Protokollierung und integrierter Hochverfügbarkeit.'],
                    'features' => ["Zentrale Policies\nThreat Intelligence\nHochverfügbarkeit"],
                    'use_cases' => ["Hub-Spoke-Netze\nEgress-Kontrolle\nSegmentierung"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/firewall/'],
                ],
            ],
            'key-vault' => [
                'subtitle' => 'Secrets, Schlüssel und Zertifikate sicher verwalten.',
                'summary' => 'Azure Key Vault schützt Secrets, kryptografische Schlüssel und Zertifikate zentral, trennt sensible Werte vom Code und integriert sich mit Microsoft Entra ID, Azure RBAC, Private Link, Monitoring und Managed HSM.',
                'content' => 'Azure Key Vault speichert und verwaltet Secrets, kryptografische Schlüssel und Zertifikate für Anwendungen und Plattformdienste. Für normale Vaults kannst du software- oder HSM-geschützte Schlüssel, Secrets und Zertifikate verwenden; Azure Key Vault Managed HSM ist ein Single-Tenant-HSM-Dienst für HSM-geschützte Schlüssel. Plane Identitäten, Azure RBAC, Netzwerksicherheit, Soft Delete, Purge Protection, Rotation, Monitoring und Throttling, bevor Anwendungen produktiv abhängig werden.',
                'features' => "Managed Identities und Azure RBAC bevorzugen; Access Policies sind Legacy und können Contributor-Risiken erzeugen\nEin Vault pro Anwendung, Region und Umgebung planen; Objektbereich-Rollen nur in Sonderfällen nutzen\nSoft Delete ist für neue Vaults standardmäßig aktiv; Purge Protection für produktive Verschlüsselungsszenarien aktivieren\nFirewall, Private Endpoint oder deaktivierter öffentlicher Zugriff schützen die Datenebene; Azure DevOps ist kein pauschal vertrauenswürdiger Dienst\nTransaktionslimits, Versionen, Backupgrenzen und Rotation beachten; Key Vault nicht als allgemeinen Konfigurations- oder Kundendatenspeicher verwenden",
                'use_cases' => "App-Secrets ohne Geheimnisse im Code oder in CI/CD-Variablen\nTLS-Zertifikatslebenszyklus und automatische Erneuerung\nCustomer-managed keys, BYOK und Verschlüsselung ruhender Daten\nGeheimnis- und Schlüsselrotation mit kontrolliertem Rollout\nHSM-/Compliance-Szenarien mit Azure Key Vault Managed HSM",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/key-vault/general/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/key-vault/',
                'known' => [
                    'subtitle' => ['Schlüssel, Zertifikate und Geheimnisse verwalten.'],
                    'summary' => ['Key Vault schützt Secrets und kryptografische Schlüssel und trennt sensible Werte sauber vom Anwendungscode.'],
                    'content' => ['Key Vault schützt Secrets und kryptografische Schlüssel und trennt sensible Werte sauber vom Anwendungscode.'],
                    'features' => ["Secrets und Zertifikate\nManaged HSM Optionen\nRBAC und Auditing"],
                    'use_cases' => ["App-Secrets\nZertifikatsverwaltung\nSchlüsselrotation"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/key-vault/'],
                ],
            ],
        ];

        $fields = ['subtitle', 'summary', 'content', 'features', 'use_cases', 'docs_url', 'pricing_url'];
        $select = $db->prepare("SELECT id, subtitle, summary, content, features, use_cases, docs_url, pricing_url FROM {$prefix}m365azure_services WHERE slug = ?");
        $update = $db->prepare("UPDATE {$prefix}m365azure_services SET subtitle = ?, summary = ?, content = ?, features = ?, use_cases = ?, docs_url = ?, pricing_url = ? WHERE id = ?");

        foreach ($updates as $slug => $data) {
            $select->execute([$slug]);
            $row = $select->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                continue;
            }

            $values = [];
            foreach ($fields as $field) {
                $known = (array) ($data['known'][$field] ?? []);
                $values[$field] = self::value_if_known((string) ($row[$field] ?? ''), $known, (string) $data[$field]);
            }

            if (
                $values['subtitle'] === (string) ($row['subtitle'] ?? '')
                && $values['summary'] === (string) ($row['summary'] ?? '')
                && $values['content'] === (string) ($row['content'] ?? '')
                && $values['features'] === (string) ($row['features'] ?? '')
                && $values['use_cases'] === (string) ($row['use_cases'] ?? '')
                && $values['docs_url'] === (string) ($row['docs_url'] ?? '')
                && $values['pricing_url'] === (string) ($row['pricing_url'] ?? '')
            ) {
                continue;
            }

            $update->execute([
                $values['subtitle'],
                $values['summary'],
                $values['content'],
                $values['features'],
                $values['use_cases'],
                $values['docs_url'],
                $values['pricing_url'],
                (int) $row['id'],
            ]);
        }

        $exists = $db->prepare("SELECT id FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $exists->execute([$markerKey]);
        if ($exists->fetch()) {
            $stmt = $db->prepare("UPDATE {$prefix}m365azure_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$markerVersion, $markerKey]);
        } else {
            $stmt = $db->prepare("INSERT INTO {$prefix}m365azure_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$markerKey, $markerVersion]);
        }
    }

    private static function upgrade_integration_communication_content(object $db, string $prefix): void
    {
        $markerKey = 'content_integration_communication_seed_version';
        $markerVersion = '2026-05-30-integration-communication-v1';

        $markerStmt = $db->prepare("SELECT setting_value FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $markerStmt->execute([$markerKey]);
        if ((string) ($markerStmt->fetchColumn() ?: '') === $markerVersion) {
            return;
        }

        self::apply_service_content_updates($db, $prefix, [
            'api-management' => [
                'title' => 'Azure API Management',
                'subtitle' => 'API-Gateways, Policies und Developer Portal für interne und externe APIs.',
                'summary' => 'Azure API Management veröffentlicht, schützt und überwacht APIs über ein verwaltetes Gateway, Policies, Produkte, Abonnements und ein Developer Portal.',
                'content' => 'Azure API Management ist eine hybride Multi-Cloud-Plattform für interne und externe APIs. Du stellst APIs über Gateways bereit, steuerst Authentifizierung, Quotas, Rate Limits, Transformationen und Caching per Policies und gibst Entwicklerteams ein Portal für Dokumentation und Abonnements. Der Dienst passt, wenn APIs nicht nur erreichbar, sondern kontrolliert, messbar und produktfähig betrieben werden müssen.',
                'features' => "Developer-Tarif ist nicht für Produktion gedacht und hat keine SLA\nConsumption ist serverlos und nutzungsbasiert, unterstützt aber nicht alle Funktionen der dedizierten Tarife\nVNet, Private Endpoints, Multi-Region, Availability Zones, Self-hosted Gateway und Workspaces hängen vom Tarif ab\nPolicies können Authentifizierung, Ratenbegrenzung, Transformation, Caching, Logging und Backend-Routing direkt im Gateway erzwingen\nAPI-, Operationen-, Produkt-, Abonnement- und Portalgrenzen je Tarif vor Migration prüfen",
                'use_cases' => "Partner- und Kunden-APIs sicher veröffentlichen\nMicroservice-Gateways mit zentralen Policies betreiben\nLegacy-Backends über moderne REST- oder SOAP-APIs kapseln\nAPI-Produkte mit Abonnements, Quotas und Developer Portal anbieten",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/api-management/api-management-key-concepts',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/api-management/',
                'known' => [
                    'title' => ['API Management'],
                    'subtitle' => ['APIs sicher veröffentlichen und verwalten.'],
                    'summary' => ['API Management bietet Gateway, Policies, Developer Portal und Monitoring für interne und externe APIs.'],
                    'content' => ['API Management bietet Gateway, Policies, Developer Portal und Monitoring für interne und externe APIs.'],
                    'features' => ["API Gateway\nRate Limits und Policies\nDeveloper Portal"],
                    'use_cases' => ["Partner-APIs\nMicroservice-Gateways\nAPI-Produkte"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/api-management/'],
                ],
            ],
            'logic-apps' => [
                'title' => 'Azure Logic Apps',
                'subtitle' => 'Workflows, Connectoren und B2B-Integration ohne eigenen Orchestrator.',
                'summary' => 'Azure Logic Apps automatisiert Geschäftsprozesse und Integrationen über Trigger, Aktionen, integrierte Connectoren und verwaltete Connectoren.',
                'content' => 'Azure Logic Apps ist eine Cloudplattform für Workflows, mit der du SaaS-, Azure-, On-Premises- und B2B-Systeme ohne eigenen Orchestrator verbindest. Ein Workflow startet mit einem Trigger und führt Aktionen aus, zum Beispiel Genehmigungen, Dateiverarbeitung, EDI/XML, API-Aufrufe oder Nachrichtenübergaben. Consumption und Standard unterscheiden sich deutlich bei Hosting, Netzwerk, Datenhaltung, Skalierung und Kostenmodell.',
                'features' => "Consumption rechnet pro Ausführung ab und enthält pro Ressource einen Workflow; Standard nutzt einen Workflow Service Plan und kann mehrere stateful oder stateless Workflows enthalten\nStandard bietet mehr Kontrolle für VNet, Private Endpoints und regionale Datenhaltung; verwaltete Connectoren werden separat als Azure-Dienst betrieben\nGrenzen wie 500 Aktionen pro Workflow, 90 Tage Run History bei stateful Workflows und kurze stateless Laufzeiten einplanen\nHTTP-Timeouts und Nachrichtengrößen begrenzen synchrone Integrationen; lange Prozesse asynchron oder eventbasiert modellieren\nConnector-Typen, Integrationskonten, Storage und Netzwerkanbindung können Zusatzkosten verursachen",
                'use_cases' => "Genehmigungs- und Eskalationsprozesse über Microsoft 365, Dynamics und Drittanbieter\nDaten-Synchronisation zwischen SaaS, Azure-Diensten und On-Premises-Systemen\nB2B-Integration mit EDI, XML und Integrationskonten\nEventbasierte Automatisierung rund um Dateien, Tickets, APIs und Nachrichten",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/logic-apps/logic-apps-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/logic-apps/',
                'known' => [
                    'title' => ['Logic Apps'],
                    'subtitle' => ['Designerbasierte Workflows und Integration.'],
                    'summary' => ['Logic Apps automatisiert Geschäftsprozesse und verbindet Cloud- und On-Premises-Systeme über zahlreiche Connectoren.'],
                    'content' => ['Logic Apps automatisiert Geschäftsprozesse und verbindet Cloud- und On-Premises-Systeme über zahlreiche Connectoren.'],
                    'features' => ["Visuelle Workflows\nViele Connectoren\nB2B- und Enterprise-Integration"],
                    'use_cases' => ["Genehmigungsprozesse\nDaten-Synchronisation\nSystemintegration"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/logic-apps/'],
                ],
            ],
            'service-bus' => [
                'title' => 'Azure Service Bus',
                'subtitle' => 'Zuverlässiges Enterprise Messaging mit Queues und Topics.',
                'summary' => 'Azure Service Bus ist ein vollständig verwalteter Enterprise-Nachrichtenbroker für entkoppelte Anwendungen, Warteschlangen und Publish/Subscribe-Kommunikation.',
                'content' => 'Azure Service Bus entkoppelt Anwendungen über Queues, Topics und Subscriptions, damit Sender und Empfänger unabhängig voneinander arbeiten können. Der Dienst bietet Funktionen wie Dead Letter Queues, Sessions, Duplicate Detection, Transaktionen, geplante Nachrichten und Filterregeln. Er ist sinnvoll, wenn Geschäftsnachrichten zuverlässig verarbeitet werden müssen und Consumer mit Wiederholungen, Sperren und möglichen Duplikaten umgehen können.',
                'features' => "Basic unterstützt keine Topics, Transaktionen, Sessions, Duplicate Detection oder Forwarding; für Enterprise Messaging meist Standard oder Premium prüfen\nPremium bietet isolierte Ressourcen über Messaging Units, Netzwerksicherheitsfunktionen, CMK und größere AMQP-Nachrichten bis 100 MB\nStandard und Basic sind bei einzelnen Nachrichten typischerweise auf 256 KB begrenzt; große Payloads besser in Storage ablegen und Referenzen senden\nPeek-Lock arbeitet mit mindestens einmaliger Zustellung; Consumer müssen idempotent sein und Duplikate sauber behandeln\nAlte SDKs und das SBMP-Protokoll werden am 30. September 2026 eingestellt; auf aktuelle Azure SDKs und AMQP migrieren",
                'use_cases' => "Auftrags-, Bestell- und Zahlungsprozesse asynchron absichern\nFachsysteme über Queues ohne direkte Kopplung verbinden\nPub/Sub-Verteilung von Ereignissen an mehrere Backend-Systeme\nFehlerhafte Nachrichten über Dead Letter Queues analysieren und erneut verarbeiten",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/service-bus-messaging/service-bus-messaging-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/service-bus/',
                'known' => [
                    'title' => ['Service Bus'],
                    'subtitle' => ['Enterprise Messaging zwischen Systemen.'],
                    'summary' => ['Service Bus entkoppelt Anwendungen über Queues und Topics und sorgt für zuverlässige asynchrone Kommunikation.'],
                    'content' => ['Service Bus entkoppelt Anwendungen über Queues und Topics und sorgt für zuverlässige asynchrone Kommunikation.'],
                    'features' => ["Queues und Topics\nDead-Lettering\nTransaktionen"],
                    'use_cases' => ["Auftragsverarbeitung\nSystementkopplung\nEnterprise Messaging"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/service-bus-messaging/'],
                ],
            ],
        ]);

        self::upsert_marker($db, $prefix, $markerKey, $markerVersion);
    }

    private static function upgrade_iot_mixed_reality_content(object $db, string $prefix): void
    {
        $markerKey = 'content_iot_mixed_reality_seed_version';
        $markerVersion = '2026-05-30-iot-mixed-reality-v1';

        $markerStmt = $db->prepare("SELECT setting_value FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $markerStmt->execute([$markerKey]);
        if ((string) ($markerStmt->fetchColumn() ?: '') === $markerVersion) {
            return;
        }

        self::apply_service_content_updates($db, $prefix, [
            'iot-hub' => [
                'title' => 'Azure IoT Hub',
                'subtitle' => 'Sichere Gerätekommunikation und Flottensteuerung im Azure-IoT-Backend.',
                'summary' => 'Azure IoT Hub ist ein verwalteter zentraler Nachrichtenhub für sichere Geräte-zu-Cloud- und Cloud-zu-Gerät-Kommunikation in IoT-Lösungen.',
                'content' => 'Azure IoT Hub verbindet IoT-Geräte, Edge-Komponenten und Backend-Anwendungen sicher und skalierbar. Der Dienst unterstützt Geräteidentitäten, SAS- oder X.509-Authentifizierung, Geräte- und Modulzwillinge, direkte Methoden, Dateiuploads, Nachrichtenrouting und Monitoring. Er eignet sich für Geräteflotten, bei denen Telemetrie, Befehle, Konfiguration und Routing zuverlässig zusammengeführt werden müssen.',
                'features' => "Basic unterstützt Geräteidentität, Geräte-zu-Cloud und Routing, aber keine Cloud-to-device-Kommunikation, Device Twins, IoT Edge und Gerätemanagement; für bidirektionale Flotten Standard wählen\nGeräteauthentifizierung über SAS oder X.509 planen; für Massenbereitstellung Device Provisioning Service einbeziehen\nPro Hub maximal 1.000.000 Geräte oder Module; pro Abonnement 50 IoT Hubs und ein Free Hub beachten\nD2C-Nachrichten maximal 256 KB, C2D maximal 64 KB; Routing und Zielendpunkte auf Durchsatz dimensionieren\nNach Microsoft werden Kundendaten nicht außerhalb der Geografie der bereitgestellten Dienstinstanz gespeichert; Region für DACH/DSGVO bewusst wählen",
                'use_cases' => "Maschinen-, Sensor- und Anlagenflotten sicher anbinden\nTelemetrie in Event Hubs, Storage, Service Bus, Cosmos DB oder Analytics-Dienste routen\nGerätekonfiguration über Device Twins und direkte Methoden steuern\nIndustrie-, Gebäude- und Field-Service-Szenarien mit IoT Edge erweitern",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/iot-hub/iot-concepts-and-iot-hub',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/iot-hub/',
                'known' => [
                    'subtitle' => ['Geräte sicher verbinden und verwalten.'],
                    'summary' => ['IoT Hub ist die zentrale Plattform für bidirektionale Kommunikation mit IoT-Geräten und Gerätemanagement.'],
                    'content' => ['IoT Hub ist die zentrale Plattform für bidirektionale Kommunikation mit IoT-Geräten und Gerätemanagement.'],
                    'features' => ["Geräteidentitäten\nCloud-to-device Messaging\nMonitoring"],
                    'use_cases' => ["Industrie 4.0\nTelemetrie\nGeräteflotten"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/iot-hub/'],
                ],
            ],
            'digital-twins' => [
                'title' => 'Azure Digital Twins',
                'subtitle' => 'Digitale Graphmodelle für Gebäude, Anlagen, Prozesse und Umgebungen.',
                'summary' => 'Azure Digital Twins modelliert reale Umgebungen als Zwillingsgraph aus DTDL-Modellen, digitalen Zwillingen, Beziehungen, Events und Abfragen.',
                'content' => 'Azure Digital Twins ist ein PaaS-Dienst für digitale Modelle ganzer Umgebungen wie Gebäude, Fabriken, Energieverteilnetze oder Anlagen. Du definierst Modelle mit DTDL, erstellst daraus digitale Zwillinge und verbindest sie über Beziehungen zu einem Graphen, der Livezustände und Kontext abbildet. Daten kommen häufig aus Azure IoT Hub, Geschäftssystemen oder APIs und werden über Abfragen, Ereignisrouten, Azure Functions, Event Hubs, Event Grid, Service Bus oder Azure Data Explorer weiterverarbeitet.',
                'features' => "Modelle werden in DTDL definiert; DTDL v3 ist empfohlen, aber Azure Digital Twins Explorer unterstützt v3 nur eingeschränkt\nDTDL-Befehle sowie writable, minMultiplicity und maxMultiplicity werden von Azure Digital Twins nicht erzwungen\nInstanzlimits wie 2.000.000 Twins, 20.000.000 Beziehungen und 10.000 Modelle sowie 32 KB Twin-Payload beachten\nEreignisrouten unterstützen Event Hubs, Event Grid und Service Bus; Dead Lettering muss explizit eingerichtet werden\nKosten entstehen über Nachrichten, Vorgänge und Abfrageeinheiten; Graph-Abfragen und Routenaufkommen vorab modellieren",
                'use_cases' => "Smart Buildings mit Räumen, Etagen, Sensoren und Anlagen modellieren\nFertigungs- und Anlagenzustände im Kontext von Beziehungen analysieren\nIoT-Hub-Telemetrie mit Geschäftsobjekten und Standortdaten verbinden\nBetriebsdaten über Event Routes und Azure Data Explorer historisieren",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/digital-twins/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/digital-twins/',
                'known' => [
                    'subtitle' => ['Digitale Abbilder realer Umgebungen.'],
                    'summary' => ['Digital Twins modelliert Gebäude, Anlagen, Prozesse und Beziehungen, um Zustände und Simulationen nutzbar zu machen.'],
                    'content' => ['Digital Twins modelliert Gebäude, Anlagen, Prozesse und Beziehungen, um Zustände und Simulationen nutzbar zu machen.'],
                    'features' => ["Graphbasierte Modelle\nLive-Daten-Integration\nRaum- und Anlagenmodellierung"],
                    'use_cases' => ["Smart Buildings\nFertigung\nFacility Management"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/digital-twins/'],
                ],
            ],
        ]);

        self::upsert_marker($db, $prefix, $markerKey, $markerVersion);
    }

    private static function upgrade_analytics_big_data_content(object $db, string $prefix): void
    {
        $markerKey = 'content_analytics_big_data_seed_version';
        $markerVersion = '2026-05-30-analytics-big-data-v1';

        $markerStmt = $db->prepare("SELECT setting_value FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $markerStmt->execute([$markerKey]);
        if ((string) ($markerStmt->fetchColumn() ?: '') === $markerVersion) {
            return;
        }

        self::apply_service_content_updates($db, $prefix, [
            'synapse-analytics' => [
                'title' => 'Azure Synapse Analytics',
                'subtitle' => 'Integrierte Analytics-Plattform für SQL, Spark, Pipelines und Data Lake.',
                'summary' => 'Azure Synapse Analytics verbindet Enterprise Data Warehousing, Big-Data-Verarbeitung, serverlose SQL-Abfragen, Spark und Datenintegration in einem Arbeitsbereich.',
                'content' => 'Azure Synapse Analytics ist ein integrierter Analysedienst für Data Warehousing, Data-Lake-Abfragen, Spark-basierte Datenverarbeitung und ETL/ELT-Pipelines. Du kannst Daten über dedizierte SQL-Pools mit reservierter Leistung oder serverlose SQL-Endpunkte für ad-hoc-Analysen abfragen. Synapse Studio bündelt Entwicklung, Monitoring, Zugriffskontrolle und Integration mit Power BI, Azure Machine Learning und weiteren Azure-Diensten.',
                'features' => "Dedizierte SQL-Pools, serverlose SQL-Pools, Spark-Pools und Pipelines haben unterschiedliche Abrechnungs- und Betriebsmodelle\nDedizierte SQL-Pools verursachen Compute-Kosten, solange sie laufen; Pausieren, Skalieren und reservierte Kapazität bewusst planen\nServerlose SQL-Abfragen werden nach verarbeiteten Daten berechnet; Dateiformate, Partitionierung und Abfragefilter beeinflussen Kosten stark\nSpark-Pools brauchen passende Node-Größen, Auto-Scale, Auto-Pause und Bibliotheksverwaltung, sonst entstehen unnötige Laufzeitkosten\nData Explorer in Synapse wird in der Doku weiterhin als Vorschau beschrieben; für produktive Protokoll- und Zeitreihenanalyse Status und Zielarchitektur prüfen",
                'use_cases' => "Enterprise Data Warehouse mit SQL-Pools und Power-BI-Anbindung\nAd-hoc-Analyse von Parquet-, CSV-, JSON- oder Delta-Daten im Data Lake\nETL- und ELT-Orchestrierung über Synapse-Pipelines und Data-Factory-Engine\nSpark-basierte Datenaufbereitung, Feature Engineering und ML-nahe Verarbeitung",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/synapse-analytics/overview-what-is',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/synapse-analytics/',
                'known' => [
                    'subtitle' => ['Analytics-Plattform für Data Warehousing und Big Data.'],
                    'summary' => ['Synapse verbindet Data Warehousing, Spark, Pipelines und Analysefunktionen für End-to-End-Datenplattformen.'],
                    'content' => ['Synapse verbindet Data Warehousing, Spark, Pipelines und Analysefunktionen für End-to-End-Datenplattformen.'],
                    'features' => ["SQL und Spark\nData Integration\nEnterprise Analytics"],
                    'use_cases' => ["Data Warehousing\nBI-Plattformen\nBig-Data-Auswertung"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/synapse-analytics/'],
                ],
            ],
            'data-factory' => [
                'title' => 'Azure Data Factory',
                'subtitle' => 'Verwaltete Datenintegration, Pipeline-Orchestrierung und Hybrid-ETL.',
                'summary' => 'Azure Data Factory erstellt, plant und überwacht Datenpipelines für ETL, ELT, Datenbewegung und Transformation über Cloud-, SaaS- und On-Premises-Quellen hinweg.',
                'content' => 'Azure Data Factory ist ein verwalteter Dienst für Datenintegration und Pipeline-Orchestrierung. Du verbindest Quellen über verknüpfte Dienste, bewegst Daten mit Copy-Aktivitäten, transformierst sie über Mapping Data Flows oder externe Compute-Dienste und steuerst Abläufe über Trigger, Parameter und Monitoring. Der Dienst eignet sich besonders für hybride Datenplattformen, bei denen Cloud- und On-Premises-Daten zuverlässig operationalisiert werden müssen.',
                'features' => "Stand/Hinweis: Microsoft nennt Data Factory in Microsoft Fabric als nächste Generation; neue Integrationsarchitekturen sollten Fabric Data Factory mitprüfen\nPipelines, Aktivitäten, Trigger, Datasets, verknüpfte Dienste und Integration Runtimes sauber trennen, sonst werden Betrieb und Migration schnell unübersichtlich\nSelf-hosted Integration Runtime ist für lokale oder private Quellen nötig und muss gepatcht, überwacht und hochverfügbar geplant werden\nKosten entstehen durch Orchestrierung, Aktivitätsausführung, Integration-Runtime-Stunden, Data-Flow-vCore-Stunden, Debugging und Monitoring-Vorgänge\nBei Migration zu Fabric ändern sich Architekturdetails wie Verbindungen, Datasets, globale Parameter, Identität, Zeitpläne und nicht unterstützte Aktivitäten",
                'use_cases' => "Tägliche oder ereignisbasierte ETL-/ELT-Pipelines aus ERP, SQL, SaaS und Dateien\nDatenmigrationen zwischen On-Premises, Azure Storage, Synapse, SQL und Lakehouse-Zielen\nHybrid-Datenintegration über Self-hosted Integration Runtime und private Netzwerke\nBetrieblich überwachbare Ladeprozesse mit Alerting, Parametern und CI/CD",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/data-factory/introduction',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/data-factory/',
                'known' => [
                    'subtitle' => ['Datenintegration und Pipeline-Orchestrierung.'],
                    'summary' => ['Data Factory verschiebt und transformiert Daten aus vielen Quellen und automatisiert Datenpipelines.'],
                    'content' => ['Data Factory verschiebt und transformiert Daten aus vielen Quellen und automatisiert Datenpipelines.'],
                    'features' => ["Pipelines\nConnectoren\nMonitoring"],
                    'use_cases' => ["ETL/ELT\nMigration\nDatenplattformen"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/data-factory/'],
                ],
            ],
            'databricks' => [
                'title' => 'Azure Databricks',
                'subtitle' => 'Lakehouse-, Analytics- und KI-Plattform auf Databricks in Azure.',
                'summary' => 'Azure Databricks ist eine verwaltete Databricks-Plattform in Azure für Data Engineering, Lakehouse-Architekturen, Streaming, SQL Analytics, Machine Learning und KI-Workloads.',
                'content' => 'Azure Databricks kombiniert die Databricks Data Intelligence Platform mit Azure-Integration für Identität, Abrechnung, Netzwerk und Speicher. Teams nutzen Notebooks, Jobs, SQL Warehouses, Delta Lake, Unity Catalog, MLflow, Lakeflow-Pipelines und serverlose oder klassische Compute-Optionen für Daten- und KI-Plattformen. Der Dienst ist stark, wenn Data Engineers, Analysten und Data Scientists gemeinsam auf einem governed Lakehouse arbeiten sollen.',
                'features' => "Stand/Hinweis: Azure Databricks Standard-Tier läuft aus; neue Standard-Workspaces sind ab 1. April 2026 nicht mehr vorgesehen und bestehende Standard-Workspaces müssen bis 1. Oktober 2026 auf Premium wechseln\nKosten setzen sich aus Azure-VMs beziehungsweise serverlosem Compute und Databricks Units zusammen; Jobs, All-Purpose, SQL, ML und serverlose Workloads getrennt kalkulieren\nUnity Catalog für Governance, Berechtigungen, Lineage und sichere Datenfreigabe früh einplanen; Premium-Funktionen und Compliance-Add-ons können kostenrelevant sein\nVNet Injection, Private Link, Managed Identities, Storage-Credentials und Exfiltration-Schutz vor Produktivstart klären\nCluster Policies, Auto-Termination, Jobs Compute, Serverless und Reserved/Commit-Optionen aktiv nutzen, sonst laufen Kosten schnell aus dem Ruder",
                'use_cases' => "Lakehouse-Plattform für Data Engineering, BI und Data Science\nBatch- und Streaming-Pipelines mit Delta Lake, Auto Loader und Lakeflow\nSQL Analytics und Dashboards auf Data-Lake-Daten\nML-/KI-Training, Feature Engineering, MLflow und generative KI auf Unternehmensdaten",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/databricks/introduction/',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/databricks/',
                'known' => [
                    'subtitle' => ['Apache Spark-basierte Daten- und KI-Plattform.'],
                    'summary' => ['Azure Databricks unterstützt kollaborative Datenentwicklung, Lakehouse-Architekturen und ML/AI-Workloads.'],
                    'content' => ['Azure Databricks unterstützt kollaborative Datenentwicklung, Lakehouse-Architekturen und ML/AI-Workloads.'],
                    'features' => ["Spark-Plattform\nLakehouse\nML-Workflows"],
                    'use_cases' => ["Data Engineering\nKI-Training\nStreaming Analytics"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/databricks/'],
                ],
            ],
        ]);

        self::upsert_marker($db, $prefix, $markerKey, $markerVersion);
    }

    private static function upgrade_hybrid_multicloud_content(object $db, string $prefix): void
    {
        $markerKey = 'content_hybrid_multicloud_seed_version';
        $markerVersion = '2026-05-30-hybrid-multicloud-v1';

        $markerStmt = $db->prepare("SELECT setting_value FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $markerStmt->execute([$markerKey]);
        if ((string) ($markerStmt->fetchColumn() ?: '') === $markerVersion) {
            return;
        }

        self::apply_service_content_updates($db, $prefix, [
            'azure-arc' => [
                'title' => 'Azure Arc',
                'subtitle' => 'Azure-Governance und Management für On-Premises, Edge und Multicloud.',
                'summary' => 'Azure Arc projiziert Server, Kubernetes-Cluster, SQL Server, Datendienste und bestimmte VM-Plattformen außerhalb von Azure in Azure Resource Manager.',
                'content' => 'Azure Arc erweitert Azure-Management, Governance und Sicherheitsfunktionen auf lokale Rechenzentren, Edge-Standorte und andere Clouds. Ressourcen werden als Azure-Ressourcen sichtbar und können über Azure Portal, Azure Policy, Azure Monitor, Defender for Cloud, Tags, RBAC, Resource Graph, Erweiterungen und Automatisierung verwaltet werden. Damit eignet sich Arc für konsistente Betriebsstandards, ohne alle Workloads direkt nach Azure zu migrieren.',
                'features' => "Die Arc-Steuerungsebene für Inventar, Organisation, RBAC, Tags und viele Verwaltungsfunktionen ist kostenlos; aktivierte Azure-Dienste wie Monitor, Defender, Sentinel, Update Manager oder Policy-Gastkonfiguration werden separat berechnet\nConnected Machine Agent, ausgehende Konnektivität, Identität, Proxy, Firewallfreigaben und Netzwerkanforderungen vor Rollout prüfen\nStand/Hinweis: Der indirekt verbundene Modus für Arc-fähige Datendienste wird laut Microsoft ab September 2025 eingestellt\nWindows Server Pay-as-you-go, SQL Server Pay-as-you-go und Extended Security Updates über Arc können Lizenz- und Kostenmodell verändern\nFür DACH/DSGVO die gewählte Azure-Region, Log-Workspace-Region, Defender/Sentinel-Datenflüsse und Aufbewahrung bewusst festlegen",
                'use_cases' => "Zentrales Inventar und Governance für Windows-/Linux-Server in Rechenzentren, Filialen und anderen Clouds\nPatch-, Sicherheits- und Compliance-Management über Azure Policy, Update Manager und Defender for Cloud\nKubernetes-Cluster über GitOps, Policy und Cluster-Erweiterungen einheitlich betreiben\nSQL Server außerhalb von Azure inventarisieren, lizenzieren, überwachen und über Arc absichern",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/azure-arc/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/azure-arc/',
                'known' => [
                    'subtitle' => ['Azure-Management für hybride und Multicloud-Ressourcen.'],
                    'summary' => ['Azure Arc erweitert Azure-Management, Governance und Sicherheit auf lokale Server, Kubernetes und andere Clouds.'],
                    'content' => ['Azure Arc erweitert Azure-Management, Governance und Sicherheit auf lokale Server, Kubernetes und andere Clouds.'],
                    'features' => ["Server und Kubernetes überall verwalten\nPolicy und Governance\nHybrid Operations"],
                    'use_cases' => ["Hybrid Cloud\nMulticloud Governance\nEdge-Standorte"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/azure-arc/'],
                ],
            ],
            'azure-local' => [
                'title' => 'Azure Local',
                'subtitle' => 'Azure-Infrastruktur für eigene Standorte, Edge und souveräne Workloads.',
                'summary' => 'Azure Local bringt Azure-Verwaltung, virtuelle Maschinen, Container und ausgewählte Azure-Dienste auf validierte oder kompatible kundeneigene Infrastruktur.',
                'content' => 'Azure Local ist Microsofts verteilte Infrastrukturlösung für lokale, Edge- und souveräne Standorte. Du betreibst Workloads auf eigener Hardware, verwaltest Infrastruktur und Workloads aber cloudnah über Azure Arc, Azure Portal, Azure CLI, ARM-Vorlagen und integrierte Dienste wie Azure Monitor, Azure Policy und Defender for Cloud. Der Dienst passt, wenn Daten, Latenz, Verfügbarkeit oder Standortvorgaben gegen eine reine Public-Cloud-Bereitstellung sprechen.',
                'features' => "Azure Local wird pro physischem Kern der lokalen Computer abgerechnet; nach Registrierung gibt es laut Preisseite eine kostenlose 60-Tage-Testphase\nEin Azure-Abonnement ist für Einrichtung und cloudverbundene Verwaltung erforderlich; validierte Partnerhardware oder kompatible Hardware nach Azure-Local-Katalog einplanen\nAzure Kubernetes Service aktiviert durch Azure Arc ist in Azure Local ab Version 2402 ohne zusätzliche AKS-Gebühr enthalten, verbrauchsbasierte Azure-Dienste können trotzdem kostenpflichtig sein\nWindows-Server-Gastlizenzierung, Azure-Hybridvorteil und optionale Workloads getrennt prüfen, weil Hostdienstgebühr, Gastrechte und Zusatzdienste unterschiedlich wirken\nFür getrennte Betriebsmodi, SAN-attach, sehr große Multi-Rack-Szenarien oder lokal gehostete Control Plane empfiehlt Microsoft direkte Abstimmung mit dem Account-Team",
                'use_cases' => "Produktions-, Logistik- oder Filialstandorte mit niedriger Latenz und lokalem Weiterbetrieb bei WAN-Ausfall\nRegulierte Workloads, bei denen Daten lokal gehalten und trotzdem zentral verwaltet werden sollen\nEdge-KI-Inferenz, industrielle Qualitätssicherung und lokale Datenvorverarbeitung\nModernisierung lokaler Virtualisierung und Containerplattformen mit Azure-Managementmodell",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/azure-local/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/azure-local/',
                'known' => [
                    'subtitle' => ['Azure-nahe Infrastruktur an verteilten Standorten.'],
                    'summary' => ['Azure Local bringt Azure-Funktionen in lokale und Edge-Umgebungen für Workloads mit Standort- oder Latenzanforderungen.'],
                    'content' => ['Azure Local bringt Azure-Funktionen in lokale und Edge-Umgebungen für Workloads mit Standort- oder Latenzanforderungen.'],
                    'features' => ["Lokale Workloads\nCloudverbundene Verwaltung\nEdge-Szenarien"],
                    'use_cases' => ["Filialen\nIndustrie-Edge\nRegulierte Workloads"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/azure-local/'],
                ],
            ],
        ]);

        self::upsert_marker($db, $prefix, $markerKey, $markerVersion);
    }

    private static function upgrade_management_governance_content(object $db, string $prefix): void
    {
        $markerKey = 'content_management_governance_seed_version';
        $markerVersion = '2026-05-30-management-governance-v1';

        $markerStmt = $db->prepare("SELECT setting_value FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $markerStmt->execute([$markerKey]);
        if ((string) ($markerStmt->fetchColumn() ?: '') === $markerVersion) {
            return;
        }

        self::apply_service_content_updates($db, $prefix, [
            'azure-monitor' => [
                'title' => 'Azure Monitor',
                'subtitle' => 'Zentrale Observability für Azure-, Hybrid-, App- und Infrastruktur-Telemetrie.',
                'summary' => 'Azure Monitor sammelt, analysiert und visualisiert Metriken, Logs, Traces und Events aus Cloud- und Hybridumgebungen und löst daraus Warnungen oder Betriebsaktionen aus.',
                'content' => 'Azure Monitor ist Microsofts zentrale Observability-Plattform für Anwendungen, Infrastruktur, Netzwerke, Kubernetes, virtuelle Maschinen und hybride Ressourcen. Der Dienst bündelt Metriken, Logs, Traces und Events in Azure Monitor- und Log-Analytics-Arbeitsbereichen, macht sie über Dashboards, Workbooks, Grafana, Metrik-Explorer und KQL-Abfragen auswertbar und reagiert über Alerts, Action Groups, Autoscale und Insights. Für produktive Umgebungen sind Datenquellen, Arbeitsbereichsstrategie, Datenaufbewahrung, Alert-Design und Kostenkontrolle genauso wichtig wie die eigentliche technische Aktivierung.',
                'features' => "Azure Monitor nutzt getrennte Plattformen für Log Analytics-Arbeitsbereiche mit KQL und Azure-Monitor-Arbeitsbereiche für Prometheus/OpenTelemetry-Metriken; Arbeitsbereichstyp und Abfragesprache bewusst wählen\nKosten entstehen vor allem durch Log-Erfassung, Aufbewahrung, Export, Plattformprotokollstreaming, Warnungen, Webtests und einzelne Zusatzfeatures; Standardmetriken und Aktivitätsprotokolle sind grundsätzlich ohne Zusatzkosten verfügbar\nAnalytics-, Basic- und Auxiliary-Protokollpläne unterscheiden sich bei Kosten, Aufbewahrung, Abfragefunktionen und Alerting; Tabellenstrategie vor großem Rollout planen\nAzure Monitor Agent und Data Collection Rules bevorzugen, um VM- und Serverdaten granular zu filtern und doppelte Datensammlung zu vermeiden\nFür Kostenkontrolle Sampling, Datenfilterung, Tageslimits mit Warnungen, Arbeitsbereichseinblicke, Advisor-Empfehlungen und passende Retention konsequent nutzen",
                'use_cases' => "Zentrales Betriebsmonitoring für Azure-Ressourcen, VMs, AKS, Netzwerke und hybride Systeme\nApplication Performance Monitoring mit Application Insights, OpenTelemetry, Traces, Abhängigkeiten und Fehlerraten\nKQL-basierte Fehleranalyse in Log Analytics, Workbooks und Dashboards für Operations-Teams\nAlerting, Eskalation, Autoscale und Automatisierung über Action Groups und Metrik-/Logregeln\nKosten- und Datenvolumensteuerung für Log-Analytics-, Sentinel- und Application-Insights-Workspaces",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/azure-monitor/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/monitor/',
                'known' => [
                    'subtitle' => ['Monitoring für Anwendungen, Infrastruktur und Netzwerke.'],
                    'summary' => ['Azure Monitor sammelt Metriken, Logs und Traces und macht Betrieb, Verfügbarkeit und Performance sichtbar.'],
                    'content' => ['Azure Monitor sammelt Metriken, Logs und Traces und macht Betrieb, Verfügbarkeit und Performance sichtbar.'],
                    'features' => ["Metriken und Logs\nAlerts\nApplication Insights"],
                    'use_cases' => ["Betriebsmonitoring\nPerformance-Analyse\nSLA-Überwachung"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/azure-monitor/'],
                ],
            ],
            'azure-policy' => [
                'title' => 'Azure Policy',
                'subtitle' => 'Governance, Compliance und Ressourcenstandards per Richtlinie erzwingen.',
                'summary' => 'Azure Policy bewertet Azure-Ressourcen gegen definierte Geschäftsregeln, fasst Regeln in Initiativen zusammen und kann nicht konforme Ressourcen auditieren, blockieren, ändern oder remediieren.',
                'content' => 'Azure Policy hilft dir, organisatorische Standards in Azure konsistent umzusetzen. Richtliniendefinitionen beschreiben in JSON, welche Ressourceneigenschaften erlaubt, erforderlich oder zu korrigieren sind; Initiativen bündeln mehrere Definitionen zu einem Governance-Ziel. Zuweisungen gelten auf Verwaltungsgruppen, Abonnements, Ressourcengruppen oder einzelnen Ressourcen. Je nach Effekt kann Azure Policy nur auditieren, Bereitstellungen verweigern, Tags oder Einstellungen ändern, verwandte Ressourcen bereitstellen oder vorhandene Ressourcen über Remediation Tasks korrigieren.',
                'features' => "Mit audit/auditIfNotExists starten und deny, modify oder deployIfNotExists erst nach Auswirkungsprüfung scharf schalten, damit Automatisierungen und Deployments nicht unerwartet brechen\nInitiativen vereinfachen Landing-Zone-, Sicherheits-, Tagging-, Regionen- und Compliance-Standards; Richtlinien als Code versionieren und reviewed ausrollen\nCompliance wird bei Ressourcenerstellung/-änderung, Policy-Änderungen und regelmäßig etwa alle 24 Stunden neu bewertet\nRemediation für modify und deployIfNotExists benötigt eine verwaltete Identität mit passenden RBAC-Rollen; bei SDK/IaC-Zuweisungen Berechtigungen manuell prüfen\nAzure Policy für Azure-Ressourcen ist gebührenfrei; Azure Automanage-/Maschinenkonfiguration für Server kann separat pro registriertem Server berechnet werden",
                'use_cases' => "Landing-Zones mit erlaubten Regionen, Ressourcentypen, SKUs, Tags und Diagnoseeinstellungen standardisieren\nCompliance-Dashboard für Managementgruppen, Abonnements und Ressourcengruppen bereitstellen\nNicht konforme Ressourcen erkennen, Berichte erstellen und Remediation Tasks ausführen\nKosten-Governance durch Pflicht-Tags, erlaubte SKUs und Budget-/Monitoring-nahe Standards unterstützen\nHybrid- und Multicloud-Governance über Azure Arc und Maschinenkonfiguration erweitern",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/governance/policy/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/azure-policy/',
                'known' => [
                    'subtitle' => ['Governance und Standards automatisch durchsetzen.'],
                    'summary' => ['Azure Policy prüft und erzwingt Regeln für Ressourcen, um Compliance und Architekturstandards sicherzustellen.'],
                    'content' => ['Azure Policy prüft und erzwingt Regeln für Ressourcen, um Compliance und Architekturstandards sicherzustellen.'],
                    'features' => ["Policy-Definitionen\nCompliance-Auswertung\nRemediation"],
                    'use_cases' => ["Landing Zones\nCompliance\nGovernance"],
                    'docs_url' => ['https://learn.microsoft.com/de-de/azure/governance/policy/'],
                ],
            ],
            'cost-management' => [
                'title' => 'Microsoft Cost Management',
                'subtitle' => 'FinOps-Werkzeuge für Kostenanalyse, Budgets, Exporte und Optimierung.',
                'summary' => 'Microsoft Cost Management zeigt Cloudkosten über unterstützte Abrechnungsbereiche, Abonnements, Ressourcengruppen und Tags, warnt bei Budgets und stellt Daten für FinOps-Prozesse bereit.',
                'content' => 'Microsoft Cost Management ist die FinOps- und Kostensteuerungsschicht für Microsoft-Cloud-Ausgaben. Du analysierst Kosten nach Bereichen, Diensten, Ressourcengruppen, Regionen, Tags oder eigenen Filtern, richtest Budgets und Warnungen ein, exportierst Kostendetails in Storage oder externe Systeme und nutzt Empfehlungen aus Advisor, Reservierungen, Savings Plans und Hybridvorteil zur Optimierung. Die Daten sind betriebsnah, aber während des laufenden Monats geschätzt und hängen von Abrechnungsmodell, Dienstmeldung, Tags und Datenaktualisierung ab.',
                'features' => "Microsoft Cost Management ist ohne zusätzliche Kosten verfügbar; die eigentlichen Azure-, Marketplace-, Reservierungs-, Savings-Plan- und Support-/Steuerpositionen folgen den jeweiligen Abrechnungsregeln\nKosten des laufenden Monats sind Schätzwerte und können sich bis zur Rechnungsstellung ändern; EA- und MCA-Daten sind meist nach 8 bis 24 Stunden verfügbar, nutzungsbasierte Abonnements können bis zu 72 Stunden benötigen\nTags werden nicht automatisch von Ressourcengruppen geerbt, gelten nicht rückwirkend und erscheinen erst nach Datenaktualisierung; Taggingstrategie und ggf. Tagvererbung früh planen\nKostenanalyse zeigt im Portal typischerweise die letzten 13 Monate, während Daten länger aufbewahrt und für ältere Zeiträume per Export/API benötigt werden können\nBudgets, Anomalieerkennung, geplante Warnungen, Exporte, Kostenanalyse, Advisor-Empfehlungen, Reservierungen und Savings Plans gemeinsam als FinOps-Prozess betreiben",
                'use_cases' => "Kostenanalyse nach Abonnement, Ressourcengruppe, Dienst, Region, Tag, Kostenstelle oder Projekt\nBudgetwarnungen und geplante Kostenberichte für Fachbereiche, Plattformteams und Finanzen\nChargeback und Showback über Tags, Abrechnungsprofile, Rechnungsabschnitte und Kostenzuteilung\nAutomatisierte Exporte für BI, Data Warehouse, ERP, Ticketsysteme oder interne FinOps-Dashboards\nOptimierung über Reservierungen, Azure Savings Plans, Azure Hybrid Benefit, Advisor-Empfehlungen und Rechte-Sizing",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/cost-management-billing/cost-management-billing-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/products/cost-management/',
                'known' => [
                    'subtitle' => ['Cloudkosten überwachen und optimieren.'],
                    'summary' => ['Cost Management schafft Transparenz über Budgets, Kostenstellen und Optimierungspotenziale in Azure.'],
                    'content' => ['Cost Management schafft Transparenz über Budgets, Kostenstellen und Optimierungspotenziale in Azure.'],
                    'features' => ["Budgets\nKostenanalyse\nExports und Empfehlungen"],
                    'use_cases' => ["FinOps\nKostenkontrolle\nBudgetüberwachung"],
                ],
            ],
        ]);

        self::upsert_marker($db, $prefix, $markerKey, $markerVersion);
    }

    /** @param array<string,array<string,mixed>> $updates */
    private static function apply_service_content_updates(object $db, string $prefix, array $updates): void
    {
        $fields = ['title', 'subtitle', 'summary', 'content', 'features', 'use_cases', 'docs_url', 'pricing_url'];
        $select = $db->prepare("SELECT id, title, subtitle, summary, content, features, use_cases, docs_url, pricing_url FROM {$prefix}m365azure_services WHERE slug = ?");
        $update = $db->prepare("UPDATE {$prefix}m365azure_services SET title = ?, subtitle = ?, summary = ?, content = ?, features = ?, use_cases = ?, docs_url = ?, pricing_url = ? WHERE id = ?");

        foreach ($updates as $slug => $data) {
            $select->execute([$slug]);
            $row = $select->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                continue;
            }

            $values = [];
            foreach ($fields as $field) {
                $known = (array) ($data['known'][$field] ?? []);
                $values[$field] = self::value_if_known((string) ($row[$field] ?? ''), $known, (string) ($data[$field] ?? $row[$field] ?? ''));
            }

            if (
                $values['title'] === (string) ($row['title'] ?? '')
                && $values['subtitle'] === (string) ($row['subtitle'] ?? '')
                && $values['summary'] === (string) ($row['summary'] ?? '')
                && $values['content'] === (string) ($row['content'] ?? '')
                && $values['features'] === (string) ($row['features'] ?? '')
                && $values['use_cases'] === (string) ($row['use_cases'] ?? '')
                && $values['docs_url'] === (string) ($row['docs_url'] ?? '')
                && $values['pricing_url'] === (string) ($row['pricing_url'] ?? '')
            ) {
                continue;
            }

            $update->execute([
                $values['title'],
                $values['subtitle'],
                $values['summary'],
                $values['content'],
                $values['features'],
                $values['use_cases'],
                $values['docs_url'],
                $values['pricing_url'],
                (int) $row['id'],
            ]);
        }
    }

    private static function upsert_marker(object $db, string $prefix, string $markerKey, string $markerVersion): void
    {
        $exists = $db->prepare("SELECT id FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $exists->execute([$markerKey]);
        if ($exists->fetch()) {
            $stmt = $db->prepare("UPDATE {$prefix}m365azure_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$markerVersion, $markerKey]);
        } else {
            $stmt = $db->prepare("INSERT INTO {$prefix}m365azure_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$markerKey, $markerVersion]);
        }
    }

    /** @param array<int,string> $knownValues */
    private static function value_if_known(string $current, array $knownValues, string $replacement): string
    {
        $normalizedCurrent = self::normalize_newlines($current);
        if (trim($normalizedCurrent) === '') {
            return self::normalize_newlines($replacement);
        }

        foreach ($knownValues as $knownValue) {
            if ($normalizedCurrent === self::normalize_newlines((string) $knownValue)) {
                return self::normalize_newlines($replacement);
            }
        }

        return $current;
    }

    private static function normalize_literal_newlines(object $db, string $prefix): void
    {
        $markerKey = 'content_newline_normalized_version';
        $markerVersion = '2026-05-30-v1';

        $markerStmt = $db->prepare("SELECT setting_value FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $markerStmt->execute([$markerKey]);
        if ((string) ($markerStmt->fetchColumn() ?: '') === $markerVersion) {
            return;
        }

        self::normalize_table_fields($db, $prefix . 'm365azure_categories', ['intro']);
        self::normalize_table_fields($db, $prefix . 'm365azure_services', ['summary', 'content', 'features', 'use_cases']);

        $settings = $db->prepare("SELECT id, setting_value FROM {$prefix}m365azure_settings WHERE setting_key IN (?, ?)");
        $settings->execute(['page_intro', 'seo_description']);
        $updateSetting = $db->prepare("UPDATE {$prefix}m365azure_settings SET setting_value = ? WHERE id = ?");
        foreach ($settings->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
            $current = (string) ($row['setting_value'] ?? '');
            $normalized = self::normalize_newlines($current);
            if ($normalized !== $current) {
                $updateSetting->execute([$normalized, (int) $row['id']]);
            }
        }

        $exists = $db->prepare("SELECT id FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $exists->execute([$markerKey]);
        if ($exists->fetch()) {
            $stmt = $db->prepare("UPDATE {$prefix}m365azure_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$markerVersion, $markerKey]);
        } else {
            $stmt = $db->prepare("INSERT INTO {$prefix}m365azure_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$markerKey, $markerVersion]);
        }
    }

    /** @param array<int,string> $fields */
    private static function normalize_table_fields(object $db, string $table, array $fields): void
    {
        $columns = 'id, ' . implode(', ', $fields);
        $rows = $db->prepare("SELECT {$columns} FROM {$table}");
        $rows->execute();

        foreach ($rows->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
            $updates = [];
            $params = [];
            foreach ($fields as $field) {
                $current = (string) ($row[$field] ?? '');
                $normalized = self::normalize_newlines($current);
                if ($normalized !== $current) {
                    $updates[] = $field . ' = ?';
                    $params[] = $normalized;
                }
            }

            if ($updates === []) {
                continue;
            }

            $params[] = (int) $row['id'];
            $stmt = $db->prepare("UPDATE {$table} SET " . implode(', ', $updates) . " WHERE id = ?");
            $stmt->execute($params);
        }
    }
}
