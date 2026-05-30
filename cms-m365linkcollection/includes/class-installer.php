<?php
/**
 * CMS M365 Linkcollection – Installer und Seed-Daten.
 *
 * @package CMS_M365LINKCOLLECTION
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365LINKCOLLECTION_Installer
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
            self::seed_content($forceSeed);
        } catch (\Throwable $e) {
            error_log('CMS M365 Linkcollection installer skipped: ' . $e->getMessage());
        }
    }

    private static function create_tables(): void
    {
        if (!class_exists('CMS\\Database')) {
            return;
        }

        $db = \CMS\Database::instance();
        $pdo = $db->getPdo();
        $repo = CMS_M365LINKCOLLECTION_Repository::instance();
        $categoriesTable = CMS_M365LINKCOLLECTION_Repository::quote_identifier($repo->categories_table($db));
        $linksTable = CMS_M365LINKCOLLECTION_Repository::quote_identifier($repo->links_table($db));
        $settingsTable = CMS_M365LINKCOLLECTION_Settings::quote_identifier(CMS_M365LINKCOLLECTION_Settings::table_name($db));

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

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$linksTable} (
            id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id         INT UNSIGNED NOT NULL,
            title               VARCHAR(190) NOT NULL,
            subtitle            VARCHAR(190) DEFAULT NULL,
            url                 VARCHAR(500) NOT NULL,
            description         TEXT DEFAULT NULL,
            image_url           VARCHAR(500) DEFAULT NULL,
            image_alt           VARCHAR(190) DEFAULT NULL,
            tags                VARCHAR(500) DEFAULT NULL,
            company_id          INT UNSIGNED DEFAULT NULL,
            expert_id           INT UNSIGNED DEFAULT NULL,
            speaker_id          INT UNSIGNED DEFAULT NULL,
            show_company_button TINYINT(1) NOT NULL DEFAULT 0,
            show_expert_button  TINYINT(1) NOT NULL DEFAULT 0,
            show_speaker_button TINYINT(1) NOT NULL DEFAULT 0,
            status              VARCHAR(20) NOT NULL DEFAULT 'active',
            is_featured         TINYINT(1) NOT NULL DEFAULT 0,
            sort_order          INT UNSIGNED NOT NULL DEFAULT 0,
            created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category (category_id),
            INDEX idx_status (status),
            INDEX idx_featured (is_featured, status),
            INDEX idx_company (company_id),
            INDEX idx_expert (expert_id),
            INDEX idx_speaker (speaker_id),
            UNIQUE KEY idx_category_title_url (category_id, title, url(190))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::migrate_links_table($pdo, $linksTable);

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$settingsTable} (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key   VARCHAR(120) NOT NULL,
            setting_value LONGTEXT DEFAULT NULL,
            updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_setting_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private static function migrate_links_table(\PDO $pdo, string $linksTable): void
    {
        self::add_column_if_missing($pdo, $linksTable, 'speaker_id', 'INT UNSIGNED DEFAULT NULL AFTER expert_id');
        self::add_column_if_missing($pdo, $linksTable, 'show_speaker_button', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER show_expert_button');
        self::add_index_if_missing($pdo, $linksTable, 'idx_speaker', 'speaker_id');
    }

    private static function add_column_if_missing(\PDO $pdo, string $table, string $column, string $definition): void
    {
        try {
            $stmt = $pdo->query('SHOW COLUMNS FROM ' . $table . ' LIKE ' . $pdo->quote($column));
            if ($stmt !== false && $stmt->fetch()) {
                return;
            }
            $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . CMS_M365LINKCOLLECTION_Repository::quote_identifier($column) . ' ' . $definition);
        } catch (\Throwable $e) {
            // Bestehende Installationen dürfen durch optionale Migrationen nicht blockieren.
        }
    }

    private static function add_index_if_missing(\PDO $pdo, string $table, string $index, string $column): void
    {
        try {
            $stmt = $pdo->query('SHOW INDEX FROM ' . $table . ' WHERE Key_name = ' . $pdo->quote($index));
            if ($stmt !== false && $stmt->fetch()) {
                return;
            }
            $pdo->exec('ALTER TABLE ' . $table . ' ADD INDEX ' . CMS_M365LINKCOLLECTION_Repository::quote_identifier($index) . ' (' . CMS_M365LINKCOLLECTION_Repository::quote_identifier($column) . ')');
        } catch (\Throwable $e) {
            // Index existiert ggf. bereits oder DB-Rechte erlauben keine Migration.
        }
    }

    private static function seed_settings(): void
    {
        if (!class_exists('CMS\\Database')) {
            return;
        }

        $db = \CMS\Database::instance();
        $table = CMS_M365LINKCOLLECTION_Settings::quote_identifier(CMS_M365LINKCOLLECTION_Settings::table_name($db));
        $exists = $db->prepare("SELECT id FROM {$table} WHERE setting_key = ? LIMIT 1");
        $insert = $db->prepare("INSERT INTO {$table} (setting_key, setting_value) VALUES (?, ?)");

        foreach (CMS_M365LINKCOLLECTION_Settings::defaults() as $key => $value) {
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
        $repo = CMS_M365LINKCOLLECTION_Repository::instance();
        $categoriesTable = CMS_M365LINKCOLLECTION_Repository::quote_identifier($repo->categories_table($db));
        $linksTable = CMS_M365LINKCOLLECTION_Repository::quote_identifier($repo->links_table($db));

        $countStmt = $db->prepare("SELECT COUNT(*) FROM {$linksTable}");
        $countStmt->execute();
        if (!$force && (int) $countStmt->fetchColumn() > 0) {
            return;
        }

        $categories = [
            ['mvp-deutschland', 'Microsoft MVPs (Deutschland)', 'Deutschsprachige Microsoft MVPs und Community-Expertinnen und -Experten.', 10],
            ['community-news', 'Top Community & News-Sites', 'Blogs, News-Portale und Newsletter für Microsoft 365, Security und Azure.', 20],
            ['official-news', 'Offizielle Microsoft-Blogs', 'Produktteam-Blogs und offizielle Microsoft-Tech-Community-Kanäle.', 25],
            ['international-community', 'Internationale Community-Blogs', 'Internationale MVP- und Practitioner-Blogs für Intune, Entra, Windows 365 und Security.', 30],
            ['tools', 'Open Source & Community Tools', 'Werkzeuge, Skripte und Community-Projekte für Administration, Security und Governance.', 40],
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

        $findLink = $db->prepare("SELECT id FROM {$linksTable} WHERE category_id = ? AND title = ? AND url = ? LIMIT 1");
        $insertLink = $db->prepare("INSERT INTO {$linksTable} (category_id, title, subtitle, url, description, image_alt, tags, status, is_featured, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)");
        $sort = 0;
        foreach (self::seed_rows() as $row) {
            $categoryId = (int) ($categoryIds[$row['category']] ?? 0);
            if ($categoryId <= 0) {
                continue;
            }
            $findLink->execute([$categoryId, $row['title'], $row['url']]);
            if ($findLink->fetch()) {
                continue;
            }
            $sort += 10;
            $featured = in_array($row['title'], ['Frankys Web', 'Office 365 IT Pros', 'CIPP', 'M365DSC', 'Maester', 'Raphael Köllner'], true) ? 1 : 0;
            $insertLink->execute([
                $categoryId,
                $row['title'],
                $row['subtitle'],
                $row['url'],
                $row['description'],
                $row['title'],
                $row['category'],
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
mvp-deutschland	Aaron Siller	M365 / Security	https://siller.consulting
mvp-deutschland	Adrian Ritter	Modern Collaboration	https://glueckkanja.com
mvp-deutschland	Anastasios Ntaflos	M365 Copilot / Modern Collab	https://ntaflos.de
mvp-deutschland	André Krämer	.NET / Mobile (MAUI)	https://andrekraemer.de
mvp-deutschland	Anja Schröder	Teams / M365 Collab	https://lnk.bio/anjaschroeder
mvp-deutschland	Armin Berberovic	Security	https://cloudsec42.com
mvp-deutschland	Aydin Mir Mohammadi	Azure	https://bluehands.de
mvp-deutschland	Benedikt Bergmann	Business Applications	https://benediktbergmann.eu
mvp-deutschland	Benjamin Abt	Azure / .NET / AI	https://benjamin-abt.com
mvp-deutschland	Björn Zahnow	Business Applications	https://crmk.eu
mvp-deutschland	Carsten Rachfahl	Cloud & Datacenter / Azure	https://rachfahl.de
mvp-deutschland	Christian Glessner	M365 / AI / Mixed Reality	https://hololux.com
mvp-deutschland	Christoph Twiehaus	M365 / Loop	https://talkm365.net
mvp-deutschland	Damir Dobric, Dr.	AI / Azure / Regional Director	https://daenet.de
mvp-deutschland	Daniel Rohregger	M365 Copilot	https://sessionize.com/rohreggerdaniel
mvp-deutschland	Daniel Sogl	AI / Angular	https://danielsogl.gitbook.io
mvp-deutschland	Dino Bordonaro	Azure / Datacenter	https://www.bordonaro-it.com
mvp-deutschland	Elisabeth Wilke-Thissen	M365 / Office	https://wilke-thissen.de
mvp-deutschland	Eric Berg	Azure / Datacenter	https://ericberg.de
mvp-deutschland	Fabian Bader	Cyber Security	https://cloudbrothers.info
mvp-deutschland	Fabian Moritz	Copilot Studio / Azure AI	https://expertsinside.com
mvp-deutschland	Ferdi Lethen-Oellers	M365 / Power Platform	https://m365roestmeister.de
mvp-deutschland	Frank Carius	M365 / Exchange	https://msxfaq.de
mvp-deutschland	Frank Geisler	Data Platform / Fabric	https://gds-business-intelligence.de
mvp-deutschland	Gregor Reimling	Azure / Security	https://reimling.eu
mvp-deutschland	Hans Brender	OneDrive / M365	https://hansbrender.com
mvp-deutschland	Jannik Reinhard	Intune / Azure AI	https://jannikreinhard.com
mvp-deutschland	Kathrin Borchert	Power BI / Fabric	https://www.yodabi.com
mvp-deutschland	Luise Freese	Power Platform / Azure	https://www.m365princess.com
mvp-deutschland	Manfred Helber	Cloud & Datacenter	https://manfredhelber.de
mvp-deutschland	Marcel Meurer	Azure / WVD	https://blog.itprocloud.de
mvp-deutschland	Marvin Bangert	Power Platform	https://cloudkumpel.de
mvp-deutschland	Michael Greth	Copilot / M365 / Clipchamp	https://sharepoint360.de
mvp-deutschland	Michael Kaufmann	DevOps / GitHub / RD	https://michael-kaufmann.ch
mvp-deutschland	Michael Plettner	M365 / Teams / Copilot	https://in2success.de
mvp-deutschland	Nicole Wiske	Copilot / Teams	https://nicolewiske.de
mvp-deutschland	Oliver Kieselbach	Intune / Windows	https://oliverkieselbach.com
mvp-deutschland	Patrick Kelbch	Copilot / M365	https://preventech.de/
mvp-deutschland	Pauline Kolde	Dynamics 365 / CX	https://paulinekolde.info
mvp-deutschland	Philipp Bauknecht	AI / Cloud / RD	https://medialesson.de
mvp-deutschland	Raphael Köllner	Compliance / Security	https://rakoellner.de
mvp-deutschland	René Wasel	M365 / SharePoint	https://wasel365.de
mvp-deutschland	Siegfried Jagott	M365 / Messaging	https://intellity.net
mvp-deutschland	Stefan Rapp	Azure IaC	https://blog.misterazure.com
mvp-deutschland	Thomas Maier	M365 / Adoption	https://thomas-maier.me
mvp-deutschland	Thomas Pentenrieder	Azure / Dev	https://zeitplan.io
mvp-deutschland	Thomas Stensitzki	M365 / Exchange / MCT	https://granikos.eu
mvp-deutschland	Tobias Fenster	Azure / Business Apps	https://tobiasfenster.io
mvp-deutschland	Tomislav Karafilov	Business Apps / Copilot	https://tkarafilov.wordpress.com
mvp-deutschland	Ugur Koc	M365 / Security	https://ugurlabs.com
community-news	Frankys Web	Exchange / Hybrid	https://www.frankysweb.de	Die deutsche Referenz für Exchange (On-Prem & Hybrid). Frank Zöchling ist erste Anlaufstelle bei Update-Problemen und Migrationen.
community-news	Borns IT	Windows / Updates	https://www.borncity.com/blog	Günter Borns Blog ist legendär für das Aufdecken von Problemen bei Windows-Updates. Wenn es klemmt, steht es bei Born zuerst.
community-news	Dr. Windows	Microsoft News	https://www.drwindows.de	Martin Geuß liefert tägliche News. Weniger Deep-Tech, dafür hervorragend für Strategie, Hardware und Consumer-Themen.
community-news	Icewolf (CH)	Exchange / Security	https://blog.icewolf.ch	Andres Bohren liefert sehr strukturierte technische Anleitungen mit Fokus auf Exchange Hybrid, Security und PowerShell.
community-news	Call4Cloud (NL)	Intune Deep Dive	https://call4cloud.nl	Rudy Ooms findet Bugs und Registry-Hacks in Intune oft Monate bevor Microsoft sie dokumentiert. Extrem tiefgehend.
community-news	Office 365 IT Pros	M365 Analyse	https://office365itpros.com	Begleit-Blog zur O365 IT Pros eBook-Bibel. Tony Redmond analysiert Änderungen kritisch und ohne Marketing-Sprech.
community-news	Entra.News	Entra ID Newsletter	https://entra.news	Kuratiert von Merill Fernando. Wöchentlicher Newsletter zu allem Neuen rund um Entra ID.
community-news	Jeffrey Appel (NL)	Defender XDR	https://jeffreyappel.nl	Detaillierte Deep Dives zu Microsoft Defender XDR, Security-Konzepten und Entra. Sehr visuell und praxisnah.
community-news	CyberDrain	MSP Automatisierung	https://cyberdrain.com	Kelvin Tegelaar. Die Bibel für MSPs, Heimat des CIPP-Tools und vieler PowerShell-Automatisierungen.
community-news	O365 Reports	PowerShell Reports	https://o365reports.com	Hervorragende Quelle für fertige Copy&Paste PowerShell-Skripte für Reports wie inaktive User oder MFA-Status.
community-news	System Center Dudes	MECM / Intune	https://www.systemcenterdudes.com	Top-Anlaufstelle für klassisches und modernes Device Management mit vielen Schritt-für-Schritt-Guides.
community-news	The Lazy Administrator	Automation	https://www.thelazyadministrator.com	Bradley Wyatt fokussiert Automatisierung im Admin-Alltag nach dem Motto: Wie automatisiere ich meinen Job weg.
community-news	Practical 365	Teams / Exchange / SharePoint	https://practical365.com	Hochwertige Artikel verschiedener internationaler Experten zu Teams, Exchange und SharePoint.
community-news	Petri.com	Microsoft Strategie	https://petri.com	Hochwertige journalistische Aufbereitung von Microsoft-News und strategische Einordnung für IT-Entscheider.
community-news	Azure Weekly	Azure Newsletter	https://azureweekly.info	Wöchentlicher Newsletter, der Azure-Updates der Woche zusammenfasst. Pflicht für Cloud Architects.
community-news	Bleeping Computer	Security News	https://www.bleepingcomputer.com	Wenn es brennt – Ransomware, Zero-Day-Exploits in Exchange oder Windows – steht es hier sehr früh.
community-news	REBELADMIN	AD / Hybrid Identity	https://rebeladmin.com	Fokus auf klassisches Active Directory und Hybrid Identity mit guten Anleitungen für Migrationen und Troubleshooting.
official-news	Microsoft Intune Blog	Endpoint Management	https://techcommunity.microsoft.com/category/microsoftintune/blog/microsoftintuneblog	Offizieller Microsoft-Tech-Community-Blog für Intune mit monatlichen What's-New-Beiträgen, Plattformupdates und Endpoint-Management-Ankündigungen.
official-news	Microsoft Entra Blog	Identity & Access	https://techcommunity.microsoft.com/category/microsoft-entra/blog/microsoft-entra-blog	Offizieller Blog für Entra ID, Identity Governance, Conditional Access, Passkeys, Private Access und moderne Identitätssicherheit.
official-news	Microsoft 365 Blog	M365 Produktnews	https://techcommunity.microsoft.com/category/microsoft365/blog/microsoft_365blog	Offizielle Microsoft-365-Produktnews und Best Practices direkt aus der Tech Community, inklusive Admin Center, Apps, Teams, Backup und Migration.
official-news	Microsoft Security Community Blog	Security / Purview / Sentinel	https://techcommunity.microsoft.com/category/microsoft-security-product/blog/microsoft-security-blog	Offizieller Security-Community-Kanal für Microsoft Security, Purview, Sentinel, Defender, Compliance und AI-Security-Themen.
international-community	Intune Stuff	Intune / Autopilot / macOS	https://intunestuff.com	Community-Plattform rund um Microsoft Intune, Intune Suite, Autopilot, macOS Management, Copilot for Security und kuratierte MVP-Blogs.
international-community	Mike's MDM Blog	Intune / Windows 365 / AVD	https://mikemdm.de	Aktiver Blog mit praxisnahen Deep Dives zu Intune, Windows 365, Azure Virtual Desktop, Endpoint Privilege Management und PowerShell-Automatisierung.
international-community	scloud	Intune / Entra / Automation	https://scloud.work/de/startseite/	Deutschsprachiger Praxisblog von Florian Salzmann mit Fokus auf Intune-Designmuster, Entra ID, Sicherheit, Windows 365 und Remediation-Skripte.
international-community	MSEndpointMgr	Endpoint Management	https://msendpointmgr.com	Etablierte Community-Seite für Intune, Autopilot, ConfigMgr, PowerShell, Reporting und Endpoint-Tools mit starkem Autorenkollektiv.
international-community	In The Cloud 24/7	Intune / Windows 365 / Automation	https://inthecloud247.com	Peter Klapwijks Blog mit vielen praktischen Artikeln zu Intune, Windows 365, Entra ID, Logic Apps und Modern-Workplace-Automatisierung.
international-community	All Things Cloud	Intune / Security / macOS	https://allthingscloud.blog	Oktay Saris Blog über Microsoft Intune, Security, macOS Hardening, Defender, Entra ID und Enterprise Mobility aus Admin-Praxisperspektive.
international-community	Out of Office Hours	Windows / Deployment	https://oofhours.com	Michael Niehaus' technischer Blog zu Windows Deployment, Autopilot, Entra Cloud Sync, MDT-Nachfolge und Windows-Interna.
international-community	Mobile Jon's Blog	Intune / Entra / AVD	https://mobile-jon.com	Blog von Jon Towles mit aktuellen Beiträgen zu Intune, Windows Autopatch, Entra-Rollen, AVD/Windows 365 und Endpoint-Erfahrungen.
international-community	Dieter Kempeneers	Windows 365 / AVD	https://kempeneers.eu	Windows-365- und Azure-Virtual-Desktop-Blog mit Fokus auf Cloud PCs, Frontline Shared, AVD Hybrid, Windows App und Entra-Integration.
international-community	Nicky De Westelinck	Microsoft 365 / Intune	https://www.nickydewestelinck.be	Modern-Workplace-Blog mit vielen Anleitungen zu Microsoft Intune, Windows 365, Android Enterprise, Entra ID und Microsoft 365 Administration.
international-community	LeBlogModernWorkplace	Intune / M365 / Entra	https://leblogmodernworkplace.fr	Französischer Modern-Workplace-Blog mit sehr aktuellen Beiträgen zu Intune, Windows, macOS, Graph API, Entra ID, Teams und Microsoft 365.
international-community	CloudFlow	M365 / Entra / Azure	https://cloudflow.be	Blog von Maxime Guillemin zu M365, Entra ID, Azure, Intune, Graph API, PowerShell und Modern Workplace mit eigenem Intune-Toolkit.
international-community	Ugur Koc Blog	Intune / Security Copilot	https://ugurkoc.de	Microsoft-MVP-Blog mit Praxisbeiträgen zu Intune, Security Copilot, KQL, macOS/Linux Management, Entra ID und PowerShell-Skripten.
tools	CIPP	MSP Portal	https://cipp.app	CyberDrain Integrated Partner Portal. Mandantenübergreifendes Management, Standardisierung von Settings und User-Verwaltung.
tools	M365DSC	Configuration as Code	https://microsoft365dsc.com	Exportiert Tenant-Konfiguration, erkennt Drift und kann Konfigurationen zwischen Tenants klonen.
tools	Maester	Tenant Auditing	https://maester.dev	Modernes Auditing auf Pester-Basis. Testet Tenants gegen Sicherheits-Best-Practices und generiert HTML-Reports.
tools	PingCastle	On-Prem AD Audit	https://www.pingcastle.com	Scans für Active Directory, Fehlkonfigurationen, verwaiste Admins und Schwachstellen mit verständlichem Health-Report.
tools	Purple Knight	AD / Entra Security	https://www.purple-knight.com	Kostenloses Tool von Semperis für Active Directory und Entra ID mit detaillierter Sicherheits-Scorecard.
tools	ScubaGear	CISA Baselines	https://github.com/cisagov/ScubaGear	Offizielles CISA-Tool zur Prüfung von M365-Tenants gegen Sicherheitsbaselines mit HTML-Berichten.
tools	IdPowerToys	Entra Visualisierung	https://idpowertoys.merill.net	Visuelle Tools von Merill Fernando für Conditional Access Policies und Log-Files.
tools	ORCA	Defender for Office 365	https://github.com/cammurray/orca	Object Replication & Configuration Assessment prüft Defender for Office 365 Einstellungen gegen Best-Practices.
tools	Hawk	Incident Response	https://github.com/T0pCyber/hawk	PowerShell-Modul für Incident Response in M365 mit Logs, Forwarding-Rules und Tenant-Daten.
tools	PSAppDeployToolkit	Software Packaging	https://psappdeploytoolkit.com	Industriestandard für Software-Paketierung mit User-Dialogen, Prozess-Kill und Registry-Aktionen.
tools	IntuneManagement	Intune Export / Migration	https://github.com/Micke-K/IntuneManagement	Exportiert, dokumentiert und kopiert Intune-Konfigurationen und App-Pakete zwischen Tenants.
tools	AzGovViz	Azure Governance	https://github.com/JulianHayward/Azure-MG-Sub-Governance-Reporting	Azure Governance Visualizer für Policies, RBAC, Blueprints und detaillierte Diagramme/Dokumentationen.
tools	BloodHound (CE)	Attack Paths	https://github.com/SpecterOps/BloodHound	Visualisiert Angriffswege im Active Directory und zeigt Pfade vom normalen User zum Domain Admin.
tools	Soteria 365 Inspect	Tenant Security	https://github.com/soteria-security/365Inspect	Skript-Sammlung für Tenant-Sicherheitsrisiken über SharePoint, Teams, Entra und weitere Bereiche.
tools	WinGet.Pro	WinGet Enterprise	https://winget.pro	Vereinfacht die Nutzung des Windows Package Managers im Unternehmensumfeld.
tools	OSDBuilder	Windows Images	https://osdbuilder.osdeploy.com	Erstellt saubere, gepatchte Windows-ISO/WIM-Dateien inklusive Updates und Features offline per PowerShell.
tools	AADInternals	Azure AD Research	https://aadinternals.com	Tool von Dr. Nestori Syynimaa für Security-Forschung und Verständnis von Angriffspfaden in Azure AD/M365.
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
