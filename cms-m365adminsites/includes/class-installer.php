<?php
/**
 * CMS M365 Adminsites – Installer und Seed-Daten.
 *
 * @package CMS_M365ADMINSITES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365ADMINSITES_Installer
{
    public static function install(): void
    {
        self::run_safely(true);
    }

    public static function maybe_install(): void
    {
        self::run_safely(false);
    }

    private static function run_safely(bool $forceSeed): void
    {
        try {
            self::create_tables();
            self::seed_settings();
            self::migrate_default_feature_visibility();
            self::seed_content($forceSeed);
        } catch (\Throwable $e) {
            error_log('CMS M365 Adminsites installer skipped: ' . $e->getMessage());
        }
    }

    private static function migrate_default_feature_visibility(): void
    {
        if (!class_exists('CMS\\Database')) {
            return;
        }

        $db = \CMS\Database::instance();
        $table = CMS_M365ADMINSITES_Settings::quote_identifier(CMS_M365ADMINSITES_Settings::table_name($db));
        $migrationKey = 'feature_panels_hidden_by_default_v1';

        $exists = $db->prepare("SELECT id FROM {$table} WHERE setting_key = ? LIMIT 1");
        $exists->execute([$migrationKey]);
        if ($exists->fetch()) {
            return;
        }

        $update = $db->prepare("INSERT INTO {$table} (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP");
        foreach (['feature_ca_shortcuts_enabled', 'feature_message_center_enabled'] as $featureKey) {
            $update->execute([$featureKey, '0']);
        }

        $insert = $db->prepare("INSERT INTO {$table} (setting_key, setting_value) VALUES (?, ?)");
        $insert->execute([$migrationKey, '1']);
    }

    private static function create_tables(): void
    {
        if (!class_exists('CMS\\Database')) {
            return;
        }

        $db = \CMS\Database::instance();
        $pdo = $db->getPdo();
        $repo = CMS_M365ADMINSITES_Repository::instance();
        $categoriesTable = CMS_M365ADMINSITES_Repository::quote_identifier($repo->categories_table($db));
        $sitesTable = CMS_M365ADMINSITES_Repository::quote_identifier($repo->sites_table($db));
        $settingsTable = CMS_M365ADMINSITES_Settings::quote_identifier(CMS_M365ADMINSITES_Settings::table_name($db));

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$categoriesTable} (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name        VARCHAR(190) NOT NULL,
            slug        VARCHAR(190) NOT NULL,
            description TEXT DEFAULT NULL,
            sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
            is_active   TINYINT(1) NOT NULL DEFAULT 1,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_slug (slug),
            INDEX idx_active_order (is_active, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$sitesTable} (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id INT UNSIGNED NOT NULL,
            title       VARCHAR(190) NOT NULL,
            subtitle    VARCHAR(190) DEFAULT NULL,
            url         VARCHAR(500) NOT NULL,
            description TEXT DEFAULT NULL,
            image_url   VARCHAR(500) DEFAULT NULL,
            image_alt   VARCHAR(190) DEFAULT NULL,
            tags        VARCHAR(500) DEFAULT NULL,
            status      VARCHAR(20) NOT NULL DEFAULT 'active',
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category (category_id),
            INDEX idx_status (status),
            INDEX idx_featured (is_featured, status),
            UNIQUE KEY idx_category_title_url (category_id, title, url(190))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$settingsTable} (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key   VARCHAR(120) NOT NULL,
            setting_value LONGTEXT DEFAULT NULL,
            updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_setting_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private static function seed_settings(): void
    {
        if (!class_exists('CMS\\Database')) {
            return;
        }

        $db = \CMS\Database::instance();
        $table = CMS_M365ADMINSITES_Settings::quote_identifier(CMS_M365ADMINSITES_Settings::table_name($db));
        $exists = $db->prepare("SELECT id FROM {$table} WHERE setting_key = ? LIMIT 1");
        $insert = $db->prepare("INSERT INTO {$table} (setting_key, setting_value) VALUES (?, ?)");

        foreach (CMS_M365ADMINSITES_Settings::defaults() as $key => $value) {
            $exists->execute([$key]);
            if ($exists->fetch()) {
                continue;
            }
            $insert->execute([$key, $value]);
        }
    }

    private static function seed_content(bool $force): void
    {
        if (!class_exists('CMS\\Database')) {
            return;
        }

        $db = \CMS\Database::instance();
        $repo = CMS_M365ADMINSITES_Repository::instance();
        $categoriesTable = CMS_M365ADMINSITES_Repository::quote_identifier($repo->categories_table($db));
        $sitesTable = CMS_M365ADMINSITES_Repository::quote_identifier($repo->sites_table($db));

        $countStmt = $db->prepare("SELECT COUNT(*) FROM {$sitesTable}");
        $countStmt->execute();
        if (!$force && (int) $countStmt->fetchColumn() > 0) {
            return;
        }

        $categories = [
            ['microsoft-365-admin', 'Microsoft 365 Admin', 'Zentrale Microsoft-365-, Exchange-, Teams-, SharePoint- und Apps-Admin-Portale.', 10],
            ['azure-identity', 'Azure & Identity', 'Azure-, Entra-, Cloud-Shell- und Identity-Portale für Tenant- und Ressourcenverwaltung.', 20],
            ['security-compliance', 'Sicherheit & Compliance', 'Defender-, Purview-, Compliance-, Secure-Score- und Security-Copilot-Portale.', 30],
            ['power-platform-ai', 'Power Platform & AI', 'Power Platform, Power BI, Copilot Studio und AI-Builder-/Maker-Portale.', 40],
            ['licensing-partner', 'Lizenzierung & Partner', 'Lizenz-, Abrechnungs-, Partner-, Support- und Vertragsportale.', 50],
            ['docs-learning', 'Dokumentation & Learning', 'Learn, Tech Community, Roadmap, Training und Portalverzeichnisse.', 60],
            ['account-personal', 'Microsoft Account & Personal Administration', 'Konto-, Sicherheits-, Familien-, Service- und Recovery-Portale.', 70],
            ['consumer-web', 'Consumer Web Apps', 'Microsoft-Web-Apps, die im Enterprise-Kontext für DLP und Tenant Restrictions relevant sind.', 80],
            ['education-admin', 'Education Administration', 'Education-, SDS-, Classroom-, Minecraft- und Azure-Education-Portale.', 90],
            ['health-status', 'Health / Status', 'Service-Health-, Status- und Incident-Portale für Microsoft Cloud-Dienste.', 100],
            ['developer-ai', 'Developer & AI Portals', 'Developer-, Fabric-, Azure-AI-, Teams-Dev- und Analyse-Portale.', 110],
        ];

        $categoryIds = [];
        $findCategory = $db->prepare("SELECT id FROM {$categoriesTable} WHERE slug = ? LIMIT 1");
        $insertCategory = $db->prepare("INSERT INTO {$categoriesTable} (name, slug, description, sort_order, is_active) VALUES (?, ?, ?, ?, 1)");
        foreach ($categories as [$slug, $name, $description, $sortOrder]) {
            $findCategory->execute([$slug]);
            $existing = $findCategory->fetchColumn();
            if ($existing !== false) {
                $categoryIds[$slug] = (int) $existing;
                continue;
            }
            $insertCategory->execute([$name, $slug, $description, $sortOrder]);
            $categoryIds[$slug] = (int) $db->getPdo()->lastInsertId();
        }

        $findSite = $db->prepare("SELECT id FROM {$sitesTable} WHERE category_id = ? AND title = ? AND url = ? LIMIT 1");
        $insertSite = $db->prepare("INSERT INTO {$sitesTable} (category_id, title, subtitle, url, description, image_alt, tags, status, is_featured, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)");
        $sort = 0;
        foreach (self::seed_rows() as $row) {
            $categoryId = (int) ($categoryIds[$row['category']] ?? 0);
            if ($categoryId <= 0) {
                continue;
            }
            $findSite->execute([$categoryId, $row['title'], $row['url']]);
            if ($findSite->fetch()) {
                continue;
            }
            $sort += 10;
            $featured = in_array($row['title'], ['Microsoft 365 Admin Center', 'Microsoft Entra Admin Center', 'Microsoft Defender Portal', 'Microsoft Purview', 'Power Platform Admin Center', 'MSPortals.io', 'Service Health Status'], true) ? 1 : 0;
            $insertSite->execute([
                $categoryId,
                $row['title'],
                $row['subtitle'],
                $row['url'],
                $row['description'],
                $row['title'],
                $row['category'] . ', ' . $row['subtitle'],
                $featured,
                $sort,
            ]);
        }
    }

    /**
     * @return array<int,array{category:string,title:string,subtitle:string,url:string,description:string}>
     */
    private static function seed_rows(): array
    {
        $raw = <<<'TSV'
microsoft-365-admin	Microsoft 365 Admin Center	Tenant, Benutzer, Lizenzen	https://admin.cloud.microsoft	Zentrales Microsoft-365-Adminportal für Benutzer, Gruppen, Lizenzen, Domains, Service Health, Richtlinien und Absprungpunkte zu spezialisierten Admin-Centern.
microsoft-365-admin	Microsoft 365 Apps Admin Center	Office Apps, Servicing, Health	https://config.office.com	Portal für Microsoft 365 Apps Bereitstellung, Update-Kanäle, Cloud Policies, Health-Daten und Office Deployment Configurations.
microsoft-365-admin	Exchange Admin Center	Exchange Online	https://admin.cloud.microsoft/exchange#/homepage	Modernes Exchange Admin Center für Mailflow, Empfänger, Hybrid, Rollen, Compliance-Verknüpfungen und Exchange Online Betrieb.
microsoft-365-admin	Exchange Admin Center Classic	Exchange Online alt	https://admin.exchange.microsoft.com/	Ältere Exchange-Admin-URL, hilfreich für Migrationen, Bookmarks und Vergleiche mit dem modernen Admin Center.
microsoft-365-admin	Microsoft Intune Admin Center	Endpoint Management	https://intune.microsoft.com	Cloud-Plattform für Geräteverwaltung, App-Verteilung, Compliance, Konfigurationsprofile, Autopilot und Endpoint Security.
microsoft-365-admin	Microsoft Teams Admin Center	Teams, Telefonie, Meetings	https://admin.teams.microsoft.com	Adminportal für Teams Policies, Meetings, Voice, Apps, Geräte, Benutzer, Teams Rooms und organisatorische Zusammenarbeit.
microsoft-365-admin	Teams Rooms Pro Management	Teams Rooms	https://portal.rooms.microsoft.com/	Portal für Monitoring und Verwaltung von Microsoft Teams Rooms Pro Geräten, Health, Incidents und Raumtechnik.
microsoft-365-admin	SharePoint Admin Center	SharePoint, OneDrive	https://admin.microsoft.com/sharepoint	Adminportal für SharePoint Online Sites, OneDrive, Freigaben, Migration, Policies und Inhaltsdienste.
microsoft-365-admin	Microsoft Stream	Video auf SharePoint	https://stream.office.com/	Stream-Portal für Unternehmensvideos, Meeting-Aufzeichnungen und SharePoint-basierte Videoinhalte.
microsoft-365-admin	Stream Admin Classic	Video Admin alt	https://web.microsoftstream.com/admin	Klassischer Stream-Admin-Einstieg, noch nützlich für ältere Bookmarks und Migrationskontexte.
microsoft-365-admin	Microsoft 365 Network Connectivity Test	Netzwerkqualität	https://connectivity.office.com	Messportal zur Prüfung der Microsoft-365-Netzwerkkonnektivität, Standorterkennung und Performance-Empfehlungen.
microsoft-365-admin	Teams Call Quality Dashboard	CQD, Voice Quality	https://cqd.teams.cloud.microsoft/	Call Quality Dashboard für Teams-Meetings und -Telefonie, Netzwerkqualität, Trends und Standortanalysen.
microsoft-365-admin	My Staff	Frontline Delegation	https://mystaff.microsoft.com	Portal für delegierte Frontline-Verwaltung, Kennwort-Reset und einfache Mitarbeiteraktionen.
microsoft-365-admin	My Apps	App Launcher	https://myapps.microsoft.com/	Endnutzerportal für Enterprise Apps, SSO-Anwendungen und Self-Service-Zugriff.
microsoft-365-admin	My Access	Access Reviews, Packages	https://myaccess.microsoft.com/	Self-Service-Portal für Zugriffspakete, Genehmigungen, Access Reviews und Identity Governance Abläufe.
azure-identity	Microsoft Entra Admin Center	Identity Management	https://entra.microsoft.com	Portal für Entra ID, Identity Governance, Conditional Access, PIM, External ID, App-Registrierungen und moderne Identitätssicherheit.
azure-identity	Microsoft Azure Portal	Azure Ressourcen	https://portal.azure.com	Zentrales Azure-Portal für Ressourcen, Subscriptions, Management Groups, IAM, Monitoring, Billing und Cloud-Administration.
azure-identity	Azure Preview Portal	Azure Preview	https://preview.portal.azure.com	Preview-Version des Azure-Portals für neue Portalfeatures und Vorabtests.
azure-identity	Azure Release Candidate Portal	Azure RC	https://rc.portal.azure.com	Release-Candidate-Variante des Azure-Portals für Validierung vor breiter Portalbereitstellung.
azure-identity	Azure All Services	Azure Dienste	https://portal.azure.com/#allservices	Direkter Einstieg in die Azure-All-Services-Übersicht für schnelle Navigation zu Diensten.
azure-identity	Create New Tenant	Tenant erstellen	https://account.azure.com/organization	Einstieg zur Erstellung neuer Microsoft Entra Tenants beziehungsweise Azure-Organisationen.
azure-identity	Azure Cloud Shell	Shell im Browser	https://shell.azure.com	Browserbasierte Bash- und PowerShell-Umgebung mit Azure CLI, Az PowerShell und Cloud Drive.
azure-identity	Azure Cosmos DB Explorer	NoSQL Explorer	https://cosmos.azure.com	Portal für Azure Cosmos DB Zugriff, Explorer, Connection-String Login und Datenbankverwaltung.
azure-identity	Azure Resource Explorer	ARM Ressourcen	https://resources.azure.com	Resource Explorer zur direkten Ansicht und Analyse von Azure Resource Manager Objekten.
azure-identity	Azure Backup Center	Backup Verwaltung	https://portal.azure.com/#blade/Microsoft_Azure_DataProtection/BackupCenterMenuBlade/overview	Zentraler Azure Backup Einstieg für Vaults, Jobs, Alerts und Schutzstatus.
azure-identity	Azure Monitor	Monitoring	https://portal.azure.com/#view/Microsoft_Azure_Monitoring/AzureMonitoringBrowseBlade/~/overview	Azure Monitor Einstieg für Metriken, Logs, Workbooks, Alerts und Observability.
azure-identity	Privileged Identity Management	PIM	https://portal.azure.com/#blade/Microsoft_Azure_PIMCommon/CommonMenuBlade/quickStart	Direkter Azure-PIM-Einstieg für privilegierte Rollen, Aktivierungen, Genehmigungen und Audits.
azure-identity	Microsoft Sentinel	SIEM / SOAR	https://portal.azure.com/#blade/Microsoft_Azure_Security_Insights/WorkspaceSelectorBlade	Portalstart für Microsoft Sentinel Workspaces, Incidents, Hunting und Automatisierung.
azure-identity	Azure Virtual Desktop	AVD	https://portal.azure.com/#view/Microsoft_Azure_WVD/WvdManagerMenuBlade/~/overview	Azure Virtual Desktop Verwaltung für Hostpools, Workspaces, Application Groups und Sessions.
security-compliance	Microsoft Defender Portal	Defender XDR	https://security.microsoft.com	Zentrales Security-Portal für Defender XDR, Incidents, Hunting, Exposure Management und Security Operations.
security-compliance	Microsoft Defender Multi-tenant	MTO	https://mto.security.microsoft.com/	Multi-Tenant-Ansicht für Defender, besonders relevant für MSPs und mandantenübergreifende Security Operations.
security-compliance	Microsoft Security Copilot	Security AI	https://securitycopilot.microsoft.com/	Security-Copilot-Portal für KI-gestützte Security-Analysen, Investigation und Promptbooks.
security-compliance	Defender Threat Intelligence	Threat Intel	https://security.microsoft.com/intel-explorer	Direkter Einstieg in Microsoft Defender Threat Intelligence und Intel Explorer.
security-compliance	Defender for Cloud Apps	CASB / MDCA	https://security.microsoft.com/cloudapps/policies/management	Portalbereich für Cloud App Security Policies, App Discovery, Governance und SaaS-Kontrollen.
security-compliance	Microsoft Purview	Governance, Compliance	https://purview.microsoft.com/	Purview-Portal für Data Governance, Information Protection, DLP, Records, eDiscovery und Compliance Manager.
security-compliance	Microsoft Compliance Portal	Compliance alt	https://compliance.microsoft.com	Älterer Compliance-Portal-Einstieg, weiterhin relevant für Bookmarks, Workflows und Legacy-Navigation.
security-compliance	Microsoft Secure Score	Security Score	https://security.microsoft.com/securescore	Secure-Score-Dashboard für Empfehlungen, Maßnahmen, Score-Entwicklung und Sicherheitsverbesserungen.
security-compliance	Microsoft Defender for Cloud	Cloud Security	https://portal.azure.com/#view/Microsoft_Azure_Security/SecurityMenuBlade/~/0	Azure-Portalbereich für Defender for Cloud, CSPM, Workload Protection und Secure Score.
power-platform-ai	Power Platform Admin Center	Environments, Governance	https://admin.powerplatform.microsoft.com	Adminportal für Power Platform Environments, DLP, Kapazität, Analytics, Governance und Einstellungen.
power-platform-ai	Power Apps Maker Portal	Apps erstellen	https://make.powerapps.com	Maker-Portal für Power Apps Canvas Apps, Model-driven Apps, Dataverse und Lösungen.
power-platform-ai	Power Automate Maker Portal	Flows erstellen	https://make.powerautomate.com	Maker-Portal für Cloud Flows, Desktop Flows, Prozessautomatisierung und Connectoren.
power-platform-ai	Power BI Admin Portal	BI Governance	https://app.powerbi.com/admin-portal/	Adminbereich für Power BI Tenant Settings, Workspaces, Kapazitäten, Auditing und Governance.
power-platform-ai	Power Pages Maker Portal	Websites erstellen	https://make.powerpages.microsoft.com/	Maker-Portal für Power Pages Websites, Dataverse-Integration, Templates und Site-Management.
power-platform-ai	Microsoft Copilot Studio	Agents, Bots	https://copilotstudio.microsoft.com/	Portal für Copilot Studio Agents, Conversational Bots, Topics, Knowledge und Publishing.
power-platform-ai	ISV Studio	Power Platform ISV	https://isvstudio.powerapps.com/home	Analyse- und Managementportal für ISV-Angebote im Power Platform Umfeld.
power-platform-ai	Phone Number Service Center	Telefonnummern	https://pstnsd.powerappsportals.com/	Service Center rund um Telefonnummern, PSTN, Portierung und Teams Voice Szenarien.
power-platform-ai	Azure AI Foundry	AI Projekte	https://ai.azure.com/	Portal für Azure AI Foundry Projekte, Modelle, Agents, Evaluation und moderne KI-Entwicklung.
power-platform-ai	Azure AI Content Safety Studio	Content Safety	https://contentsafety.cognitive.azure.com/	Studio für Content Safety Moderation, Text-/Bildprüfungen und Sicherheitsklassifizierung.
power-platform-ai	Azure AI Document Intelligence Studio	Document AI	https://documentintelligence.ai.azure.com/	Studio für Document Intelligence, Formularerkennung, Layoutanalyse und Extraktion.
power-platform-ai	Azure Machine Learning Studio	ML Studio	https://ml.azure.com	Portal für Azure Machine Learning Workspaces, Modelle, Pipelines und MLOps.
power-platform-ai	Azure OpenAI Studio	OpenAI	https://oai.azure.com/portal	Studio für Azure OpenAI Deployments, Playground, Prompttests und Modellzugriffe.
licensing-partner	M365 Maps	Lizenzkarten	https://m365maps.com/	Interaktive Microsoft-365-Lizenzkarten und Feature-Matrizen von Aaron Dinnage, hilfreich für Lizenzvergleiche.
licensing-partner	Volume Licensing Service Center	VLSC	https://admin.cloud.microsoft/#/subscriptions/vlnew	Einstieg für Volume Licensing und moderne Lizenzverwaltung im Microsoft 365 Admin Center.
licensing-partner	Azure Subscriptions	Subscriptions	https://account.azure.com/Subscriptions	Älterer Azure-Account-Einstieg mit Hinweis auf Azure Portal Billing und Subscription-Verwaltung.
licensing-partner	Azure Cost Management & Billing	Billing	https://portal.azure.com/#blade/Microsoft_Azure_Billing/BillingMenuBlade/Overview	Azure Portalbereich für Abrechnung, Kostenanalyse, Rechnungen, Budgets und Zahlungsmethoden.
licensing-partner	Azure Enterprise Portal	EA Portal	https://ea.azure.com	Enterprise-Agreement-Portal für EA-Abrechnung und historische Verwaltungsprozesse.
licensing-partner	Microsoft Partner Center	Partner, CSP, Marketplace	https://partner.microsoft.com	Partnerportal für AI Cloud Partner Program, CSP, Marketplace, Benefits, Incentives und Partnerverwaltung.
licensing-partner	Azure Support Request	Support	https://portal.azure.com/#create/Microsoft.Support	Direkter Einstieg zum Erstellen neuer Azure Support Requests im Azure Portal.
licensing-partner	Microsoft Product Terms	Lizenzbedingungen	https://www.microsoft.com/licensing/docs/view/Product-Terms	Offizielle Microsoft Product Terms und archivierte Online Services Terms für kommerzielle Lizenzprogramme.
licensing-partner	Microsoft FastTrack	Adoption, Migration	https://fasttrack.microsoft.com	FastTrack-Einstieg für Microsoft Cloud Adoption, Migration und App Assure Szenarien.
licensing-partner	Microsoft Services Hub	Support Services	https://serviceshub.microsoft.com	Portal für Unified/Premier Support, Assessments, Learning und Services Hub Workspaces.
licensing-partner	Volume Licensing eAgreements	eAgreements	https://eagreements.microsoft.com/	Portal für Microsoft Volume Licensing eAgreements und Vertragsprozesse.
licensing-partner	Business Center	Next Generation VL	https://businessaccount.microsoft.com	Next Generation Volume Licensing Business Center für Kunden und Partner.
docs-learning	Microsoft Learn Documentation	Docs	https://learn.microsoft.com/docs/	Technische Microsoft-Dokumentation mit Produktverzeichnis für Azure, Microsoft 365, Entra, Purview, Power Platform und mehr.
docs-learning	Microsoft Tech Community	Community	https://techcommunity.microsoft.com	Microsoft Tech Community für Blogs, Diskussionen, Events und Produktteam-Updates.
docs-learning	Microsoft 365 Roadmap	Roadmap	https://www.microsoft.com/en-us/microsoft-365/roadmap	Offizielle Microsoft-365-Roadmap für geplante, rollende und veröffentlichte Features.
docs-learning	MSPortals.io	Portalverzeichnis	https://msportals.io/	Community-Verzeichnis mit mehr als 600 Microsoft-Portalen, Kategorien und Direktlinks für Admins und Endnutzer.
docs-learning	MSPortals Admin	Admin Portals	https://msportals.io/admin	Gefilterte MSPortals-Ansicht mit über 200 Administrator-Portalen.
docs-learning	MSPortals Licensing	Licensing Links	https://msportals.io/licensing	Gefilterte MSPortals-Ansicht für Microsoft- und Azure-Lizenzressourcen.
docs-learning	Microsoft Learn Educator Center	Educator Training	https://learn.microsoft.com/training/educator-center/?source=mec	Trainingsportal für Lehrkräfte, Unterrichtsmaterialien, Zertifizierungen und Education-Lernpfade.
docs-learning	Microsoft Virtual Training Days	Training Events	https://www.microsoft.com/en-us/trainingdays	Kostenlose Microsoft Virtual Training Days für Azure, Security, Power Platform und Microsoft 365.
docs-learning	Microsoft Reactor	Developer Events	https://developer.microsoft.com/en-us/reactor/	Microsoft Reactor verbindet Entwickler, Startups und Lernende über Events und Community-Formate.
docs-learning	Microsoft Learn Profile	Learning Dashboard	https://www.microsoft.com/learning/dashboard.aspx	Profil- und Dashboard-Einstieg für Microsoft Learn, Zertifizierungen und Trainingsfortschritt.
account-personal	Microsoft Account	Kontoübersicht	https://account.microsoft.com/	Zentrale Kontoübersicht für persönliche Microsoft-Konten, Profil, Geräte, Dienste und Sicherheit.
account-personal	Microsoft Account Security	Sicherheit	https://account.microsoft.com/security	Sicherheitsbereich für persönliche Microsoft-Konten, Anmeldemethoden, Kennwort und Schutzmaßnahmen.
account-personal	Microsoft Family Safety	Familie	https://account.microsoft.com/family	Familienverwaltung für Microsoft-Konten, Mitglieder, Limits und Family Safety.
account-personal	Microsoft Account Services	Abos & Dienste	https://account.microsoft.com/services	Übersicht persönlicher Microsoft-Abonnements, Dienste und Abrechnung.
account-personal	Microsoft Account Recovery	Konto wiederherstellen	https://account.live.com/acsr	Recovery-Formular zur Wiederherstellung persönlicher Microsoft-Konten.
account-personal	Microsoft Sign-ins	Anmeldeaktivität	https://mysignins.microsoft.com	Portal für eigene Anmeldeinformationen, Security Info und Sign-in Aktivitäten im Arbeits- oder Schulkonto.
account-personal	My Security Info	MFA / SSPR	https://mysignins.microsoft.com/security-info	Direkter Einstieg für Security Info, MFA-Methoden und Self-Service Password Reset.
consumer-web	Microsoft Copilot	Consumer Copilot	https://copilot.microsoft.com	Copilot-Webportal für persönliche Nutzung, wichtig für Tenant Restrictions und Consumer-App-Abgrenzung.
consumer-web	Outlook.com	Mail Consumer	https://outlook.live.com	Consumer Outlook Web App, relevant für DLP-, Browser- und Zugriffskonzepte.
consumer-web	OneDrive Consumer	Files Consumer	https://onedrive.live.com	Consumer OneDrive Portal für persönliche Dateien und Sharing-Szenarien.
consumer-web	Teams Personal	Teams Consumer	https://teams.live.com	Consumer-Teams-Webportal für persönliche Kommunikation und Communities.
consumer-web	OneNote Web	Notizbücher	https://onenote.cloud.microsoft/	OneNote-Webportal für digitale Notizbücher, Zusammenarbeit und Copilot Notebooks.
consumer-web	Microsoft Designer	Design AI	https://designer.microsoft.com	KI-gestütztes Designer-Portal für Bilder, Designs, Vorlagen und Kreativinhalte.
consumer-web	Microsoft Create	Templates, Copilot	https://m365.cloud.microsoft/create	Create-Portal für Microsoft 365 Copilot, Vorlagen, Bilder, Designer und Clipchamp-Einstiege.
consumer-web	Microsoft Clipchamp	Video Editor	https://app.clipchamp.com	Webbasierter Videoeditor, relevant für Consumer-/Enterprise-Abgrenzung und Browserzugriffe.
consumer-web	Microsoft Loop	Arbeitsbereiche	https://loop.cloud.microsoft	Loop-Webportal für Workspaces, Loop-Komponenten, Copilot und Zusammenarbeit.
consumer-web	Windows 365	Cloud PCs	https://windows365.microsoft.com	Windows-365-Portal für Cloud PCs, Endnutzerzugriff und Verwaltungskontexte.
consumer-web	Microsoft To Do	Tasks	https://to-do.live.com	Consumer-/Business-nahe Aufgaben-App für persönliche Listen und Aufgaben.
consumer-web	Microsoft 365 Web	Office Web	https://microsoft365.com	Microsoft-365-Webeinstieg für Apps, Dateien, Copilot und Produktivität.
education-admin	School Data Sync	SDS	https://sds.edu.cloud.microsoft/	Portal für School Data Sync, automatisierte Benutzer- und Kursverwaltung in Microsoft 365 Education.
education-admin	Microsoft Education	Education Hub	https://www.microsoft.com/education	Microsoft Education Startpunkt für Schulen, Hochschulen, Produkte, Ressourcen und Angebote.
education-admin	Microsoft 365 Education	Education M365	https://www.microsoft.com/education/products/microsoft-365	Produktportal für Microsoft 365 Education Pläne, Funktionen und Einstieg.
education-admin	Education Store	Education Store	https://educationstore.microsoft.com	Microsoft Education Store beziehungsweise Store-nahe Education-Angebote und Beschaffungskontexte.
education-admin	Minecraft Education	Minecraft	https://education.minecraft.net/	Minecraft Education Portal für Unterricht, Downloads, Ressourcen und Support.
education-admin	Azure Education Hub	Azure for Education	https://azureforeducation.microsoft.com/	Education Hub für Azure, Dev Tools for Teaching, Software, Keys und studentische Angebote.
education-admin	Azure Education Software	Software / Keys	https://portal.azure.com/#view/Microsoft_Azure_Education/EducationMenuBlade/~/software	Direkter Azure-Portalbereich für Education Software und Keys.
education-admin	Azure Dev Tools for Students	Student Signup	https://azureforeducation.microsoft.com/devtools	Einstieg für Azure Dev Tools for Students, kostenlose Azure-/Developer-Ressourcen.
education-admin	Microsoft Education Support	Support	https://support.microsoft.com/home/contact/education	Kontakt- und Supportportal für Microsoft Education.
education-admin	Microsoft Reflect	Wellbeing	https://reflect.microsoft.com/	Education-App für Check-ins, Wohlbefinden und Reflexion im Schulkontext.
health-status	Service Health Status	Microsoft Cloud Status	https://status.cloud.microsoft	Öffentlicher Service-Health-Status für Microsoft 365, Azure, Power Platform und Consumer-Produkte.
health-status	Office Status Classic	Office Status alt	https://status.office.com	Klassische Office-Statusseite für historische Bookmarks und Kompatibilität.
health-status	Microsoft 365 Service Status	Service Status	https://portal.office.com/ServiceStatus	M365-Service-Statusseite als öffentlicher oder semipublic Status-Einstieg.
health-status	Microsoft 365 Admin Service Health	Admin Health	https://admin.microsoft.com/AdminPortal/Home#/servicehealth	Service-Health-Bereich im Microsoft 365 Admin Center für tenantbezogene Incidents.
health-status	Azure DevOps Status	DevOps Status	https://status.dev.azure.com	Statusseite für Azure DevOps Services.
health-status	Azure Service Health	Azure Health	https://portal.azure.com/#blade/Microsoft_Azure_Health/AzureHealthBrowseBlade/serviceIssues	Tenantbezogene Azure Service Health Incidents und Wartungen.
health-status	Azure Status	Public Azure Status	https://status.azure.com/status	Öffentliche Azure-Statusseite für globale Serviceinformationen.
developer-ai	Microsoft Fabric	Data Platform	https://fabric.microsoft.com/	Fabric-Portal für Analytics, Power BI, Data Engineering, Data Factory, Real-Time Intelligence und OneLake.
developer-ai	Developer Portal for Teams	Teams Apps	https://dev.teams.microsoft.com	Portal für Teams-App-Entwicklung, Manifeste, Apps, Bots und Veröffentlichung.
developer-ai	Azure IoT Central	IoT Apps	https://apps.azureiotcentral.com/	Portal für Azure IoT Central Anwendungen und Geräteverwaltung.
developer-ai	Bot Framework Dev Portal	Bots	https://dev.botframework.com	Entwicklerportal für Bot Framework Registrierungen, Channels und Bot-Konfiguration.
developer-ai	Microsoft AppSource	Marketplace	https://appsource.microsoft.com/	Marketplace für Business Apps, Add-ins, Teams Apps und Microsoft Cloud Lösungen.
developer-ai	Visual Studio Subscriptions	Abonnements	https://my.visualstudio.com	Portal für Visual Studio Subscriptions, Downloads, Benefits und Keys.
developer-ai	Visual Studio Subscriptions Management	VS Admin	https://manage.visualstudio.com	Verwaltungsportal für Visual Studio Subscriptions und Zuweisungen.
developer-ai	Microsoft Clarity	Analytics	https://clarity.microsoft.com/	Portal für Heatmaps, Session Recordings und Web Analytics.
developer-ai	Bing Webmaster Tools	SEO	https://www.bing.com/webmasters	Webmaster-Portal für Bing Indexierung, Sitemap, SEO Insights und Suchanalyse.
developer-ai	Visual Studio Code Web	VS Code Browser	https://www.vscode.dev/	Browserbasierte VS-Code-Variante für schnelle Datei- und Repository-Bearbeitung.
TSV;

        $rows = [];
        foreach (preg_split('/\R/', trim($raw)) ?: [] as $line) {
            $parts = explode("\t", $line, 5);
            if (count($parts) < 4) {
                continue;
            }
            $rows[] = [
                'category' => trim((string) ($parts[0] ?? '')),
                'title' => trim((string) ($parts[1] ?? '')),
                'subtitle' => trim((string) ($parts[2] ?? '')),
                'url' => trim((string) ($parts[3] ?? '')),
                'description' => trim((string) ($parts[4] ?? '')),
            ];
        }

        return $rows;
    }
}
