<?php
/**
 * CMS M365 Azure: Erweiterter Service-Katalog.
 *
 * @package CMS_M365Azure
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('CMS_M365Azure_Catalog_Expansion', false)) {
    return;
}

final class CMS_M365Azure_Catalog_Expansion
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    public static function apply(object $db, string $prefix): void
    {
        $markerKey = 'content_expanded_service_catalog_version';
        $markerVersion = '2026-05-30-expanded-v1';

        $markerStmt = $db->prepare("SELECT setting_value FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $markerStmt->execute([$markerKey]);
        if ((string) ($markerStmt->fetchColumn() ?: '') === $markerVersion) {
            return;
        }

        $categoryIds = self::ensure_categories($db, $prefix);
        self::ensure_services($db, $prefix, $categoryIds);
        self::upsert_marker($db, $prefix, $markerKey, $markerVersion);
    }

    /** @return array<string,int> */
    private static function ensure_categories(object $db, string $prefix): array
    {
        $categories = [
            'identitaet-sicherheit' => [
                'title' => 'Identität & Sicherheit',
                'intro' => 'Identitäten, Sicherheitsstatus, Bedrohungserkennung und Security Operations zentral steuern.',
                'sort_order' => 45,
            ],
            'web-content-delivery' => [
                'title' => 'Web & Content Delivery',
                'intro' => 'Webanwendungen, APIs, globale Einstiegspunkte, DNS, Zugriff und Schutz für Internet-Workloads bereitstellen.',
                'sort_order' => 55,
            ],
            'migration' => [
                'title' => 'Migration',
                'intro' => 'Server, Datenbanken, Web-Apps und Plattformen bewerten, planen und kontrolliert nach Azure migrieren.',
                'sort_order' => 105,
            ],
        ];

        $ids = [];
        $select = $db->prepare("SELECT id FROM {$prefix}m365azure_categories WHERE slug = ?");
        $insert = $db->prepare("INSERT INTO {$prefix}m365azure_categories (slug, title, overline, intro, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1)");

        foreach ($categories as $slug => $category) {
            $select->execute([$slug]);
            $id = (int) ($select->fetchColumn() ?: 0);
            if ($id === 0) {
                $insert->execute([
                    $slug,
                    (string) $category['title'],
                    'Azure Kategorie',
                    (string) $category['intro'],
                    (int) $category['sort_order'],
                ]);
                $id = (int) $db->lastInsertId();
            }

            $ids[$slug] = $id;
        }

        $existing = $db->prepare("SELECT id, slug FROM {$prefix}m365azure_categories WHERE slug IN (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $existing->execute([
            'compute',
            'storage',
            'datenbanken',
            'ki-machine-learning',
            'integration-kommunikation',
            'analytics-big-data',
            'management-governance',
            'migration',
            'web-content-delivery',
        ]);
        foreach ($existing->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
            $ids[(string) $row['slug']] = (int) $row['id'];
        }

        return $ids;
    }

    /** @param array<string,int> $categoryIds */
    private static function ensure_services(object $db, string $prefix, array $categoryIds): void
    {
        $services = self::services();
        $select = $db->prepare("SELECT id FROM {$prefix}m365azure_services WHERE slug = ?");
        $insert = $db->prepare("INSERT INTO {$prefix}m365azure_services (category_id, slug, title, subtitle, summary, content, features, use_cases, docs_url, pricing_url, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

        foreach ($services as $service) {
            $category = (string) $service['category'];
            if (!isset($categoryIds[$category])) {
                continue;
            }

            $select->execute([(string) $service['slug']]);
            if ($select->fetch()) {
                continue;
            }

            $insert->execute([
                $categoryIds[$category],
                (string) $service['slug'],
                (string) $service['title'],
                self::normalize_newlines((string) $service['subtitle']),
                self::normalize_newlines((string) $service['summary']),
                self::normalize_newlines((string) $service['content']),
                self::normalize_newlines((string) $service['features']),
                self::normalize_newlines((string) $service['use_cases']),
                (string) $service['docs_url'],
                (string) $service['pricing_url'],
                (int) $service['sort_order'],
            ]);
        }
    }

    /** @return array<int,array<string,string|int>> */
    private static function services(): array
    {
        return [
            [
                'category' => 'identitaet-sicherheit',
                'slug' => 'microsoft-entra-id',
                'title' => 'Microsoft Entra ID',
                'subtitle' => 'Cloud-Identität und Zugriffskontrolle für Benutzer, Geräte, Apps und Workloads.',
                'summary' => 'Microsoft Entra ID verwaltet Identitäten, Authentifizierung und Zugriff für Microsoft 365, Azure, SaaS-Apps und eigene Anwendungen.',
                'content' => 'Microsoft Entra ID ist der zentrale Cloud-Identitätsdienst für Benutzer, Gruppen, Geräte, Anwendungen und Workloads. Der Dienst passt, wenn Du Anmeldung, MFA, Conditional Access, Rollen, App-Registrierungen und Identitätsbetrieb über Microsoft Cloud und eigene Apps zentralisieren willst. Er ersetzt kein klassisches Active Directory für Legacy-Protokolle wie LDAP oder Kerberos, dafür brauchst Du weiter AD DS oder Microsoft Entra Domain Services. Für DACH-Umgebungen sind Mandantenregion, Protokolldaten, Gastzugriff, Adminrollen und Lizenzabdeckung vor dem Rollout sauber zu klären.',
                'features' => "SKUs Free, P1 und P2 bewusst planen; Conditional Access und viele Risiko- und Governance-Funktionen brauchen P1 oder P2\nMicrosoft 365 enthält Entra ID, aber nicht jede Microsoft 365 Lizenz enthält alle Entra Premium-Funktionen\nBreak-Glass-Konten, MFA-Methoden, Security Defaults oder Conditional Access vor produktiver Erzwingung testen\nLegacy Authentication, App-Registrierungen, Consent und privilegierte Rollen sind zentrale Angriffsflächen\nFür klassische Domänenfunktionen wie LDAP, Kerberos, GPO oder Domain Join ist Entra ID allein nicht der richtige Dienst",
                'use_cases' => "Zentraler Login für Microsoft 365, Azure und SaaS-Anwendungen\nMFA und Conditional Access für Admins, Geräte und risikobasierte Zugriffe\nApp-Registrierungen, Enterprise Apps und Single Sign-On für interne Anwendungen\nGast- und Partnerzugriff über B2B-Kollaboration\nIdentitätsbetrieb mit Rollen, Protokollen und Zugriffsauswertungen",
                'docs_url' => 'https://learn.microsoft.com/de-de/entra/fundamentals/whatis',
                'pricing_url' => 'https://www.microsoft.com/de-de/security/business/microsoft-entra-pricing',
                'sort_order' => 10,
            ],
            [
                'category' => 'identitaet-sicherheit',
                'slug' => 'defender-for-cloud',
                'title' => 'Microsoft Defender for Cloud',
                'subtitle' => 'Cloud Security Posture Management und Workload Protection für Azure, Hybrid und Multicloud.',
                'summary' => 'Microsoft Defender for Cloud bewertet Sicherheitsstatus, Empfehlungen und Bedrohungsschutz für Cloud- und Hybridressourcen.',
                'content' => 'Microsoft Defender for Cloud ist Microsofts CNAPP-Plattform für Sicherheitsstatus, DevSecOps und Workload Protection. Der Dienst passt, wenn Du Azure-, Arc-, AWS- oder GCP-Ressourcen zentral bewerten, priorisieren und mit Defender-Plänen schützen willst. Der kostenlose Foundational-CSPM-Teil liefert Inventar, Empfehlungen und Secure Score, während Defender CSPM und die Workload-Pläne erweiterte Funktionen und Schutz pro Ressourcentyp abrechnen. Für produktive Umgebungen musst Du Pläne, Auto-Provisioning, Agenten, Log Analytics und Datenregionen bewusst konfigurieren.',
                'features' => "Foundational CSPM ist kostenlos; Defender CSPM und Workload-Pläne werden zusätzlich berechnet\nDefender-Pläne gelten je nach Ressourcentyp, zum Beispiel Server, Storage, Container, Datenbanken, App Service, Key Vault oder APIs\nAktivierung kann automatisch Ressourcen registrieren; Planumfang und Ausnahmen vorab prüfen\nKostenfallen entstehen durch breite Planaktivierung, Malware Scanning, Log Analytics und Multicloud-Anbindung\nPortal- und Betriebsmodell verschiebt sich schrittweise Richtung Microsoft Defender Portal",
                'use_cases' => "Secure Score und Empfehlungen für Azure- und Arc-Ressourcen betreiben\nDefender for Servers, Storage, Container und Datenbanken gezielt aktivieren\nAngriffspfade, Schwachstellen und Fehlkonfigurationen priorisieren\nSecurity-Alerts an Sentinel, ITSM oder SOC-Prozesse anbinden\nDevSecOps-Erkenntnisse aus Repositories und Pipelines einbeziehen",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/defender-for-cloud/defender-for-cloud-introduction',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/defender-for-cloud/',
                'sort_order' => 20,
            ],
            [
                'category' => 'identitaet-sicherheit',
                'slug' => 'microsoft-sentinel',
                'title' => 'Microsoft Sentinel',
                'subtitle' => 'Cloudnatives SIEM und SOAR auf Azure Monitor und Log Analytics.',
                'summary' => 'Microsoft Sentinel sammelt Sicherheitsdaten, erkennt Bedrohungen, untersucht Incidents und automatisiert Reaktionen.',
                'content' => 'Microsoft Sentinel ist ein cloudnatives SIEM/SOAR für Security Operations über Microsoft, Azure, Multicloud, SaaS und On-Premises. Der Dienst passt, wenn Du Logs, Alerts, Threat Intelligence, Hunting, Analytics Rules, Workbooks und Playbooks zentral betreiben willst. Sentinel ist kein Ersatz für sauberes Log-Design, weil Datenquellen, Tabellen, Retention, Normalisierung und Alertqualität direkt über Kosten und Nutzen entscheiden. Neue und bestehende Umgebungen sollten den Übergang zum Microsoft Defender Portal früh einplanen.',
                'features' => "Abrechnung hängt vor allem von Datenaufnahme, Log Analytics, Retention, Archivierung und optionalen Commitment Tiers ab\nData Connectors, Analytics Rules, Watchlists, Workbooks, Hunting und Playbooks müssen aktiv kuratiert werden\nPlaybooks nutzen Azure Logic Apps und können eigene Kosten verursachen\nNach dem 31. März 2027 wird Microsoft Sentinel im Azure-Portal nicht mehr unterstützt\nDatenresidenz, Workspace-Region, Aufbewahrung und Export sind für DACH/DSGVO vorab festzulegen",
                'use_cases' => "Zentrales SIEM für Microsoft 365, Entra ID, Azure, Firewall, Endpoint und Drittanbieter\nIncident-Triage mit Analytics Rules, MITRE-Zuordnung und Entity Investigation\nThreat Hunting mit KQL, Watchlists und Threat Intelligence\nAutomatisierte Reaktion über Playbooks und ITSM-Anbindung\nMSSP- und Mehrmandantenbetrieb über Azure Lighthouse",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/sentinel/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/microsoft-sentinel/',
                'sort_order' => 30,
            ],
            [
                'category' => 'web-content-delivery',
                'slug' => 'app-service',
                'title' => 'Azure App Service',
                'subtitle' => 'Verwaltetes Hosting für Web-Apps, APIs und Container ohne eigenen Serverbetrieb.',
                'summary' => 'Azure App Service hostet Webanwendungen, REST APIs, mobile Backends und Container auf einer verwalteten PaaS-Plattform.',
                'content' => 'Azure App Service ist sinnvoll, wenn Du Web-Apps und APIs schnell betreiben willst, ohne VMs, IIS, Nginx oder Betriebssystempatching selbst zu verwalten. Der Dienst unterstützt .NET, Java, Node.js, Python, PHP sowie eigene Container auf Windows oder Linux. Er passt für klassische Web-Backends, interne Portale, APIs und viele Modernisierungen, aber nicht für Workloads mit voller OS-Kontrolle oder speziellen Kernelabhängigkeiten. Für Enterprise-Betrieb sind App Service Plan, Slots, Skalierung, VNet Integration, Zertifikate, Managed Identity und Observability entscheidend.',
                'features' => "Free und Shared sind nicht für Produktion gedacht; Basic, Standard, Premium v3/v4 und Isolated nach SLA, Netzwerk und Last wählen\nDeployment Slots sind erst ab Standard relevant und können zusätzliche App-Instanzen im Plan belegen\nVNet Integration steuert ausgehenden Zugriff; Private Endpoint und Access Restrictions für eingehenden Zugriff getrennt planen\nKosten entstehen auf Planebene, auch wenn einzelne Apps gestoppt sind\nApp Service Environment v1 und v2 sind eingestellt; alte isolierte Umgebungen auf aktuelle Modelle migrieren",
                'use_cases' => "Web-Apps und REST APIs für Fachanwendungen\nLift-and-Shift von IIS-, PHP-, Java- oder Node-Websites\nBackend für Portale, mobile Apps und Integrationen\nStaging und Blue-Green-Deployments mit Slots\nContainerisierte Webanwendungen ohne AKS-Betrieb",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/app-service/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/app-service/',
                'sort_order' => 10,
            ],
            [
                'category' => 'web-content-delivery',
                'slug' => 'static-web-apps',
                'title' => 'Azure Static Web Apps',
                'subtitle' => 'Hosting für statische Frontends mit optionalem serverlosem API-Backend.',
                'summary' => 'Azure Static Web Apps veröffentlicht statische Frontends aus GitHub oder Azure DevOps und kann APIs über Azure Functions anbinden.',
                'content' => 'Azure Static Web Apps passt für Frontends mit Angular, React, Vue, Svelte, Blazor, Hugo oder ähnlichen Frameworks, wenn die App als statische Dateien ausgeliefert wird. Die Plattform baut und veröffentlicht aus dem Repository, erzeugt Vorschauumgebungen für Pull Requests und verteilt statische Inhalte global. Für dynamische Serverlogik nutzt Du integrierte Functions oder angebundene Backends. Nicht passend ist der Dienst für klassische serverseitige Webanwendungen, lange Backendprozesse oder volle Kontrolle über Webserver und Laufzeit.',
                'features' => "Free eignet sich für kleine Projekte und Tests; Standard für Produktion, SLA, eigene Authentifizierung und höhere Kontingente\nIntegrierte verwaltete Functions sind begrenzt; bei regionalen oder größeren API-Anforderungen eigene Functions oder Backends anbinden\nBuild, Routing, Auth und Rollen werden über Repository und Konfigurationsdatei gesteuert\nBandbreite, Speicher, benutzerdefinierte Domänen und Front-Door-Optionen beeinflussen Kosten und Architektur\nServerseitiges Rendering und Framework-Sonderfälle vorab gegen aktuelle Static-Web-Apps-Unterstützung prüfen",
                'use_cases' => "Statische Websites, Landingpages und Dokumentationsportale\nSingle Page Applications mit API-Backend\nPull-Request-Vorschauumgebungen für Frontend-Teams\nBlazor WebAssembly und moderne JavaScript-Frameworks\nKleine Portale mit Authentifizierung und Rollenmodell",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/static-web-apps/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/app-service/static/',
                'sort_order' => 20,
            ],
            [
                'category' => 'web-content-delivery',
                'slug' => 'front-door',
                'title' => 'Azure Front Door',
                'subtitle' => 'Globaler Layer-7-Einstieg für Webapps, APIs, CDN, WAF und Routing.',
                'summary' => 'Azure Front Door beschleunigt und schützt globale Webanwendungen über Microsofts Edge-Netzwerk.',
                'content' => 'Azure Front Door ist der globale Einstiegspunkt für internetbasierte Web- und API-Workloads. Der Dienst kombiniert Layer-7-Routing, CDN-Funktionen, TLS, WAF, Health Probes, Regeln und globales Load Balancing über Azure- oder externe Ursprünge. Er passt, wenn Nutzer weltweit niedrige Latenz und ein einheitliches Security-Frontend brauchen. Für rein regionale interne Lastverteilung sind Application Gateway oder Load Balancer meist passender.',
                'features' => "Tarife Standard und Premium unterscheiden sich bei Private Link, WAF-Funktionen, Bot-Schutz und Sicherheitsanalysen\nAbrechnung erfolgt über Grundgebühr, Anforderungen sowie ausgehende Daten vom Edge zum Client und zum Ursprung\nWAF und Private Link sind bei Premium enthalten, CAPTCHA und weitere Add-ons separat prüfen\nOrigins, Health Probes, Caching-Regeln und TLS-Zertifikate sauber modellieren, sonst entstehen Routingfehler\nKlassische CDN- und Front-Door-Modelle nicht mehr als Zielarchitektur für neue Projekte einplanen",
                'use_cases' => "Globaler Einstieg für Webanwendungen und APIs\nCDN und Beschleunigung statischer und dynamischer Inhalte\nWAF-Schutz und Bot-Schutz am Edge\nAktiv-aktiv-Routing über mehrere Regionen oder Ursprünge\nPrivate Origin-Anbindung über Private Link im Premium-Tarif",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/frontdoor/front-door-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/frontdoor/',
                'sort_order' => 30,
            ],
            [
                'category' => 'web-content-delivery',
                'slug' => 'application-gateway',
                'title' => 'Azure Application Gateway',
                'subtitle' => 'Regionaler Layer-7-Load-Balancer mit optionaler Web Application Firewall.',
                'summary' => 'Azure Application Gateway verteilt HTTP- und HTTPS-Traffic regional und kann Webanwendungen mit WAF schützen.',
                'content' => 'Azure Application Gateway ist ein regionaler Layer-7-Load-Balancer für Webanwendungen und APIs in Azure. Er unterstützt TLS-Terminierung, URL-basiertes Routing, Hostnamen, Redirects, Session Affinity, Autoscaling und optional WAF. Der Dienst passt für regionale Hub-Spoke-, Private-App- und Ingress-Szenarien, bei denen Du mehr HTTP-Logik brauchst als ein Layer-4-Load-Balancer bietet. Für globale Edge-Beschleunigung und CDN ist Azure Front Door die bessere Einstiegsschicht.',
                'features' => "Für neue Bereitstellungen v2-SKUs nutzen; v1 bietet kein Autoscaling und weniger aktuelle Funktionen\nWAF_v2 getrennt planen, weil Regeln, Ausschlüsse, Prevention-Modus und False Positives Betrieb brauchen\nAbrechnung umfasst Gateway-Stunden, Kapazitätseinheiten und ausgehende Daten\nSubnetz, Private IP, Public IP, Zertifikate, Backend Health und DNS früh festlegen\nApplication Gateway for Containers ist ein eigenes modernes Modell für Kubernetes-nahe Szenarien",
                'use_cases' => "Regionaler HTTPS-Einstieg für interne und externe Web-Apps\nWAF-Schutz für App Service, VMs, AKS oder private Backends\nPfad- und hostbasiertes Routing für mehrere Anwendungen\nTLS-Offload und zentrale Zertifikatsverwaltung\nHub-Spoke-Application-Delivery in Landing Zones",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/application-gateway/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/application-gateway/',
                'sort_order' => 40,
            ],
            [
                'category' => 'web-content-delivery',
                'slug' => 'load-balancer',
                'title' => 'Azure Load Balancer',
                'subtitle' => 'Layer-4-Lastverteilung für TCP- und UDP-Traffic in Azure.',
                'summary' => 'Azure Load Balancer verteilt eingehenden und internen TCP- oder UDP-Traffic auf VMs, VM Scale Sets und private Backends.',
                'content' => 'Azure Load Balancer ist die richtige Wahl, wenn Du robuste Layer-4-Verteilung ohne HTTP-Features brauchst. Der Dienst arbeitet mit Frontend-IP, Backend-Pool, Health Probes und Load-Balancing-Regeln für öffentliche oder interne Szenarien. Er passt für VM-basierte Plattformen, NVAs, Datenbank-Cluster und einfache hochverfügbare Dienste. Für Webrouting, TLS-Logik oder WAF brauchst Du Application Gateway oder Front Door.',
                'features' => "Basic Load Balancer wird abgekündigt; Standard Load Balancer für neue und bestehende Zielarchitekturen verwenden\nStandard unterstützt Zonenredundanz und ist sicherer, benötigt aber explizite NSG-Regeln für eingehenden Traffic\nAbrechnung bei Standard umfasst Regeln und verarbeitete Daten, Basic war kostenlos\nHealth Probes und Floating IP bei HA-Clustern sorgfältig konfigurieren\nKein Layer-7-Routing, keine TLS-Terminierung und keine WAF-Funktion",
                'use_cases' => "Hochverfügbarkeit für VM- und VM-Scale-Set-Backends\nInterne Lastverteilung zwischen Anwendungsschichten\nTCP-/UDP-Dienste ohne HTTP-Routing\nNVA- und Firewall-HA-Designs\nCluster-Szenarien mit Floating IP und Health Probes",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/load-balancer/load-balancer-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/load-balancer/',
                'sort_order' => 50,
            ],
            [
                'category' => 'web-content-delivery',
                'slug' => 'azure-dns',
                'title' => 'Azure DNS',
                'subtitle' => 'Verwaltetes DNS-Hosting für öffentliche und private Zonen.',
                'summary' => 'Azure DNS hostet öffentliche DNS-Zonen und private DNS-Zonen für Namensauflösung in Azure-Netzwerken.',
                'content' => 'Azure DNS stellt DNS-Zonen auf Microsofts globaler Infrastruktur bereit. Du kannst öffentliche Zonen für Internetdomänen und private Zonen für Namensauflösung innerhalb virtueller Netzwerke betreiben. Der Dienst passt, wenn DNS-Zonen, Private Endpoints und Hub-Spoke-Namensauflösung zentral und IaC-fähig verwaltet werden sollen. Für komplexe hybride DNS-Szenarien brauchst Du zusätzlich DNS Private Resolver, Forwarding-Regeln und saubere On-Premises-Integration.',
                'features' => "Öffentliche und private DNS-Zonen werden getrennt geplant und abgerechnet\nAzure DNS ist kein Domain-Registrar für alle TLDs; Registrierung und Delegation separat prüfen\nPrivate DNS Zones sind zentral für Private Endpoints und VNet-Namensauflösung\nDNS Private Resolver ersetzt eigene DNS-Forwarder-VMs in vielen Hybrid-Szenarien, kostet aber separat\nTTL, Split-Horizon, Delegation und Namenskonventionen vor Migration festlegen",
                'use_cases' => "DNS-Hosting für öffentliche Azure- und Unternehmensdomänen\nPrivate Namensauflösung für Hub-Spoke-Netzwerke\nPrivate Endpoint DNS für PaaS-Dienste\nHybrid-DNS mit Forwarding zwischen Azure und On-Premises\nIaC-gesteuerte DNS-Zonen und Records",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/dns/dns-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/dns/',
                'sort_order' => 60,
            ],
            [
                'category' => 'web-content-delivery',
                'slug' => 'bastion',
                'title' => 'Azure Bastion',
                'subtitle' => 'RDP- und SSH-Zugriff auf VMs ohne öffentliche IP-Adressen.',
                'summary' => 'Azure Bastion ermöglicht browser- oder clientbasierten Zugriff auf virtuelle Maschinen über private IPs.',
                'content' => 'Azure Bastion reduziert den Bedarf an öffentlichen VM-IP-Adressen und offenen RDP- oder SSH-Ports. Der Dienst wird in einem eigenen Subnetz bereitgestellt und vermittelt Zugriff über das Azure-Portal oder native Clients. Er passt für Adminzugriffe auf Azure-VMs, Jump-Host-Ablösung und kontrollierte Betriebszugriffe. Für dauerhafte App-Verbindungen, VPN-Ersatz oder umfangreiche PAM-Prozesse ist Bastion allein nicht genug.',
                'features' => "SKUs Developer, Basic, Standard und Premium unterscheiden sich bei VNet-Support, Skalierung, nativen Clients, Session Recording und Private-only-Funktionen\nAzureBastionSubnet muss korrekt dimensioniert und exklusiv für Bastion reserviert werden\nKosten entstehen pro Bastion-Instanz und zusätzlich für ausgehende Datenübertragung\nKeine öffentlichen IPs auf Ziel-VMs nötig, aber RBAC, NSGs und Just-in-Time-Zugriffe weiter planen\nDeveloper-SKU ist für einfache Dev/Test-Szenarien gedacht und nicht für zentrale Produktion",
                'use_cases' => "Adminzugriff auf Windows- und Linux-VMs ohne Public IP\nAblösung klassischer Jump-Hosts in Azure-Netzen\nTemporärer Zugriff für Betrieb, Migration und Troubleshooting\nKontrollierter Zugriff in Hub-Spoke- oder Landing-Zone-Umgebungen\nPremium-Szenarien mit Session Recording und stärkerer Zugriffskontrolle",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/bastion/bastion-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/azure-bastion/',
                'sort_order' => 70,
            ],
            [
                'category' => 'web-content-delivery',
                'slug' => 'ddos-protection',
                'title' => 'Azure DDoS Protection',
                'subtitle' => 'Schutz vor volumetrischen Angriffen für virtuelle Netzwerke und öffentliche IPs.',
                'summary' => 'Azure DDoS Protection ergänzt den Plattformschutz um adaptive Schutzfunktionen, Telemetrie und Kostenabsicherung.',
                'content' => 'Azure DDoS Protection schützt internetexponierte Azure-Ressourcen gegen Netzwerkangriffe auf Layer 3 und Layer 4. Der Dienst passt, wenn öffentliche IPs, Web-Einstiege, Application Gateways, Firewalls oder Load Balancer geschäftskritisch sind und Angriffe messbar abgefedert werden müssen. Network Protection schützt aktivierte VNets, IP Protection schützt einzelne öffentliche IPs. Für Layer-7-Angriffe brauchst Du zusätzlich WAF über Front Door oder Application Gateway.',
                'features' => "Network Protection wird pro geschütztem VNet geplant; IP Protection pro öffentlicher IP\nDDoS Protection ersetzt keine WAF, keine Bot-Abwehr und keine App-Härtung\nKostenmodell zwischen VNet-basiertem Schutz und IP-basiertem Schutz bewusst wählen\nTelemetrie, Metriken, Alerts und Angriffsauswertungen in Azure Monitor einbinden\nDDoS Rapid Response und Kostenabsicherung sind an passende Pläne und Bedingungen gebunden",
                'use_cases' => "Schutz öffentlicher Web- und API-Einstiege\nAbsicherung von Application Gateway, Load Balancer, Firewall und Public IPs\nDDoS-Telemetrie und Alerting für SOC und Betrieb\nSchutz kritischer Landing-Zone-Hubs und DMZ-Netze\nKombination mit WAF für Layer-7-Webschutz",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/ddos-protection/ddos-protection-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/ddos-protection/',
                'sort_order' => 80,
            ],
            [
                'category' => 'compute',
                'slug' => 'container-registry',
                'title' => 'Azure Container Registry',
                'subtitle' => 'Private Registry für Containerimages, Helm-Charts und Artefakte.',
                'summary' => 'Azure Container Registry speichert und verteilt private Containerimages für AKS, Container Apps, App Service und CI/CD.',
                'content' => 'Azure Container Registry ist die verwaltete private Registry für Containerimages und OCI-Artefakte in Azure. Der Dienst passt, wenn Du Images nah an Azure-Workloads speichern, mit Microsoft Entra ID absichern und in Build- oder Deployment-Pipelines integrieren willst. Basic, Standard und Premium unterscheiden sich bei Speicher, Durchsatz, Replikation, Private Link und erweiterten Sicherheitsfunktionen. Für große Plattformen sind Retention, Image Signing, Scans, Geo-Replikation und Netzwerkzugriff zentrale Betriebsfragen.',
                'features' => "Basic für kleine Szenarien, Standard für mehr Durchsatz und Speicher, Premium für Geo-Replikation, Private Link und höhere Limits\nGeo-Replikation reduziert Pull-Latenz, erzeugt aber Speicher- und Replikationskosten je Region\nACR Tasks können Images bauen, patchen und automatisieren, verursachen je nach Nutzung zusätzliche Laufzeit\nAdmin User möglichst deaktivieren und Zugriff über Entra ID, Managed Identity oder Service Principal steuern\nImage-Aufbewahrung, Quarantäne, Content Trust oder Signierung und Defender-Scans früh einplanen",
                'use_cases' => "Private Registry für AKS, Container Apps und App Service\nCI/CD-Pipelines mit Build, Push und Deployment\nGeo-nahe Image-Verteilung für mehrere Azure-Regionen\nBasisimages zentral versionieren und aktualisieren\nOCI-Artefakte und Helm-Charts für Plattformteams verwalten",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/container-registry/container-registry-intro',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/container-registry/',
                'sort_order' => 50,
            ],
            [
                'category' => 'compute',
                'slug' => 'virtual-desktop',
                'title' => 'Azure Virtual Desktop',
                'subtitle' => 'Verwaltete Desktop- und App-Virtualisierung auf Azure.',
                'summary' => 'Azure Virtual Desktop stellt Windows-Desktops und Remote-Apps über Hostpools, Session Hosts und FSLogix bereit.',
                'content' => 'Azure Virtual Desktop ist ein DaaS-Dienst für virtuelle Desktops und Remote-Apps auf Azure-VMs. Du steuerst Hostpools, Pooled oder Personal Desktops, Images, Profile, Netzwerk, Identität und App-Bereitstellung. Der Dienst passt, wenn Du flexible Windows-Arbeitsplätze, Remote-Apps oder saisonale Kapazität auf eigener Azure-Infrastruktur betreiben willst. Wenn Du stärker produktisierte Cloud-PCs mit Benutzerlizenz und weniger Infrastruktursteuerung suchst, ist Windows 365 oft passender.',
                'features' => "Benutzer benötigen passende Windows-, Microsoft 365- oder Remote-Desktop-Lizenzen; Azure-Compute, Storage und Netzwerk kommen separat dazu\nPooled spart Kosten durch Mehrbenutzersitzungen, Personal bietet dedizierte Desktops pro Benutzer\nFSLogix-Profile brauchen performanten SMB-Speicher, oft Azure Files oder Azure NetApp Files\nAutoscale, Reserved Instances, Start VM on Connect und Image-Management sind zentrale Kostenhebel\nIdentität, Conditional Access, Netzwerk, Drucker, Teams-Optimierung und Datenresidenz früh testen",
                'use_cases' => "Remote-Arbeitsplätze für Mitarbeitende, Partner und Auftragnehmer\nPooled Desktops für Callcenter, Schulungen oder Schichtbetrieb\nRemote-Apps für einzelne Fachanwendungen\nSichere Arbeitsumgebungen für regulierte Daten\nMigration klassischer RDS- oder VDI-Umgebungen nach Azure",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/virtual-desktop/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/virtual-desktop/',
                'sort_order' => 60,
            ],
            [
                'category' => 'compute',
                'slug' => 'batch',
                'title' => 'Azure Batch',
                'subtitle' => 'Großskalige Batch- und HPC-Verarbeitung mit verwalteten Compute-Pools.',
                'summary' => 'Azure Batch plant und betreibt parallele Jobs auf Pools aus virtuellen Maschinen oder Spot-Kapazität.',
                'content' => 'Azure Batch eignet sich für rechenintensive Jobs, Simulationen, Rendering, Datenverarbeitung und HPC-nahe Workloads, die in viele Tasks zerlegbar sind. Du definierst Pools, VM-Größen, Images, Starttasks, Jobs und Tasks, während Batch die Ausführung und Skalierung koordiniert. Der Dienst ist kein interaktiver Cluster und keine dauerhafte App-Plattform. Kosten und Laufzeit hängen stark von VM-SKU, Poolgröße, Autoscale, Storage, Datenbewegung und Fehlertoleranz der Anwendung ab.',
                'features' => "Batch selbst hat keine separate Servicegebühr; berechnet werden VMs, Storage, Netzwerk und abhängige Ressourcen\nSpot- oder Low-Priority-Knoten sparen Kosten, können aber jederzeit verdrängt werden\nPools, Quotas, VM-Familien und regionale Kapazität vor großen Läufen prüfen\nJobdaten in Storage planen, weil Datenbewegung und Egress Laufzeit und Kosten prägen\nTasks müssen Wiederholungen, Teilfehler und Checkpointing sauber unterstützen",
                'use_cases' => "Rendering, Medienverarbeitung und Bildkonvertierung\nMonte-Carlo-Simulationen, Risikoanalysen und technische Berechnungen\nGenomik, Forschung und HPC-nahe Stapelverarbeitung\nParallele ETL- und Datenaufbereitungsjobs\nSkalierbare Test-, Build- oder Validierungsläufe",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/batch/batch-technical-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/batch/',
                'sort_order' => 70,
            ],
            [
                'category' => 'storage',
                'slug' => 'netapp-files',
                'title' => 'Azure NetApp Files',
                'subtitle' => 'Enterprise-NFS- und SMB-Dateispeicher mit hoher Performance.',
                'summary' => 'Azure NetApp Files stellt verwaltete NetApp-Volumes für anspruchsvolle Dateiworkloads in Azure bereit.',
                'content' => 'Azure NetApp Files ist ein Enterprise-Dateidienst für NFS- und SMB-Workloads mit niedriger Latenz und hoher Performance. Er passt für SAP, Datenbanken, VDI-Profile, HPC, Analytics und Anwendungen, die klassische NAS-Eigenschaften in Azure brauchen. Du arbeitest mit NetApp-Konten, Kapazitätspools, Volumes, Service Levels und Netzwerkdelegation. Für kleine einfache Dateifreigaben ist Azure Files meist günstiger und einfacher.',
                'features' => "Service Levels Standard, Premium und Ultra bestimmen Durchsatz pro bereitgestelltem TiB\nKapazitätspools werden mit Mindestgrößen geplant und abgerechnet, auch wenn Volumes nicht voll belegt sind\nSubnetzdelegation, regionale Verfügbarkeit und Quotas vor Projektstart prüfen\nSnapshots, Backup, Replikation und Protokollwahl NFS oder SMB gehören ins Design\nNicht jede Funktion ist in jeder Region oder für jede Protokollkombination verfügbar",
                'use_cases' => "SAP HANA, Oracle und andere performancekritische Datenworkloads\nFSLogix-Profile und Home-Laufwerke für Azure Virtual Desktop\nNFS-Dateispeicher für Linux- und HPC-Anwendungen\nSMB-Freigaben mit Enterprise-Performance\nMigration bestehender NetApp- oder NAS-Workloads nach Azure",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/azure-netapp-files/azure-netapp-files-introduction',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/netapp/',
                'sort_order' => 40,
            ],
            [
                'category' => 'storage',
                'slug' => 'backup',
                'title' => 'Azure Backup',
                'subtitle' => 'Zentrale Sicherung für Azure-, Hybrid- und Workload-Daten.',
                'summary' => 'Azure Backup schützt VMs, Dateien, SQL, SAP HANA und weitere Workloads über Recovery Services Vaults oder Backup Vaults.',
                'content' => 'Azure Backup ist der verwaltete Sicherungsdienst für Azure- und Hybrid-Workloads. Er passt, wenn Du Backups zentral steuern, Aufbewahrung definieren, Wiederherstellungen testen und lokale Backup-Infrastruktur reduzieren willst. Je nach Workload nutzt Du Recovery Services Vaults, Backup Vaults, Backup Policies, Agenten oder Workload-Erweiterungen. Backup ersetzt kein vollständiges DR-Konzept, weil RTO, Netzwerk, Abhängigkeiten und Failover-Orchestrierung separat geplant werden müssen.',
                'features' => "Abrechnung kombiniert geschützte Instanzen und genutzten Backup-Speicher je nach Workload\nVault-Redundanz, Soft Delete, Immutable Vaults, Multi-User Authorization und Private Endpoints vor Produktivstart festlegen\nAufbewahrung, tägliche Änderungsrate und langfristige Retention treiben Speicherverbrauch und Kosten\nWiederherstellungen regelmäßig testen, inklusive Datei-, VM-, SQL- und Cross-Region-Szenarien\nAzure Backup ist Sicherung, Azure Site Recovery ist DR-Orchestrierung; beides nicht verwechseln",
                'use_cases' => "Sicherung von Azure VMs und einzelnen Dateien\nBackup von Azure Files, SQL Server und SAP HANA\nLangfristige Aufbewahrung für Compliance und Ransomware-Schutz\nZentrale Backup-Policies für Landing Zones\nHybrid-Backup für ausgewählte On-Premises-Workloads",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/backup/backup-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/backup/',
                'sort_order' => 50,
            ],
            [
                'category' => 'datenbanken',
                'slug' => 'sql-managed-instance',
                'title' => 'Azure SQL Managed Instance',
                'subtitle' => 'SQL-Server-nahe PaaS-Instanz für Migrationen mit hoher Kompatibilität.',
                'summary' => 'Azure SQL Managed Instance bietet verwaltete SQL-Server-Funktionen mit breiter Kompatibilität und weniger Infrastrukturaufwand.',
                'content' => 'Azure SQL Managed Instance ist der Sweetspot, wenn Du SQL-Server-Workloads nach PaaS migrieren willst, aber mehr Instanzfunktionen brauchst als Azure SQL Database bietet. Der Dienst unterstützt viele SQL-Server-Features, automatische Patches, Backups, Hochverfügbarkeit und VNet-Isolation. Er passt für Migrationen mit SQL Agent, Cross-Database-Abfragen, Service Broker oder instanznahen Anforderungen. Für einzelne cloudnative Datenbanken ohne Instanzabhängigkeiten ist Azure SQL Database oft schlanker.',
                'features' => "Tiers General Purpose und Business Critical nach Latenz, IO, HA und Kosten wählen\nManaged Instance benötigt eigenes Subnetz und hat Netzwerk-, DNS- und Routinganforderungen\nNicht alle SQL-Server-Funktionen sind vollständig identisch; Kompatibilität mit Data Migration Assistant prüfen\nCompute, Speicher, Backup-Retention, Lizenzmodell und Azure Hybrid Benefit beeinflussen Kosten stark\nStartzeit, Skalierung und Wartungsfenster sind nicht wie bei einer selbstverwalteten VM zu behandeln",
                'use_cases' => "Migration bestehender SQL-Server-Anwendungen mit Instanzfunktionen\nModernisierung von SQL-VMs ohne kompletten App-Umbau\nGeschäftsanwendungen mit SQL Agent und mehreren Datenbanken\nPrivate PaaS-Datenbanken in VNet-nahen Architekturen\nSQL-Konsolidierung mit weniger Betrieb als auf VMs",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/azure-sql/managed-instance/sql-managed-instance-paas-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/azure-sql-managed-instance/single/',
                'sort_order' => 40,
            ],
            [
                'category' => 'datenbanken',
                'slug' => 'mysql-flexible-server',
                'title' => 'Azure Database for MySQL',
                'subtitle' => 'Verwalteter MySQL Flexible Server für Open-Source-Anwendungen.',
                'summary' => 'Azure Database for MySQL stellt MySQL als verwalteten Flexible Server mit Compute-, Storage- und Backup-Steuerung bereit.',
                'content' => 'Azure Database for MySQL Flexible Server ist der verwaltete MySQL-Dienst für Anwendungen, die MySQL-Kompatibilität ohne eigenen Datenbankserver brauchen. Azure übernimmt Plattformbetrieb, Patching, Backups, Verschlüsselung und Hochverfügbarkeitsoptionen. Der Dienst passt für Web-Backends, CMS, Fachanwendungen und Open-Source-Stacks. Für Spezialfeatures, Root-Zugriff oder stark angepasste Datenbankserver kann eine VM weiterhin nötig sein.',
                'features' => "Tiers Burstable, General Purpose und Business Critical passend zu Lastprofil und SLA wählen\nBurstable eignet sich eher für Dev/Test und kleine variable Last, nicht für dauerhaft hohe Produktion\nSpeicher, IOPS, Backup-Aufbewahrung und HA-Konfiguration treiben Kosten\nPrivate Access oder Public Access, Firewall und TLS vor App-Anbindung sauber planen\nSingle Server ist der alte Pfad; neue und migrierte Workloads auf Flexible Server ausrichten",
                'use_cases' => "MySQL-Backends für PHP-, Java-, Node- und .NET-Anwendungen\nCMS- und Commerce-Systeme mit verwaltetem Datenbankbetrieb\nMigration bestehender MySQL-Server aus VMs oder On-Premises\nProduktionsdatenbanken mit automatischen Backups und HA\nOpen-Source-Anwendungen in privaten Azure-Netzen",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/mysql/flexible-server/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/mysql/flexible-server/',
                'sort_order' => 50,
            ],
            [
                'category' => 'datenbanken',
                'slug' => 'managed-redis',
                'title' => 'Azure Cache for Redis / Azure Managed Redis',
                'subtitle' => 'In-Memory-Cache und Redis-kompatible Datenstrukturplattform für schnelle Anwendungen.',
                'summary' => 'Azure Cache for Redis und Azure Managed Redis beschleunigen Anwendungen mit In-Memory-Daten, Caching und Pub/Sub-Mustern.',
                'content' => 'Redis in Azure ist sinnvoll, wenn Anwendungen sehr schnelle Lesezugriffe, Session State, Caching, Rate Limiting oder Pub/Sub-nahe Muster brauchen. Azure Cache for Redis ist der etablierte Dienst, Azure Managed Redis ist der neuere Nachfolgepfad für viele neue Szenarien. Der Dienst ist kein Ersatz für eine dauerhafte relationale Datenbank, auch wenn Persistenz und Replikation verfügbar sein können. Architektur und Kosten hängen stark von Tier, Speichergröße, Clustering, Netzwerk, Verfügbarkeit und Datenpersistenz ab.',
                'features' => "Azure Managed Redis als Ziel für neue Planungen prüfen; bestehende Azure Cache for Redis Umgebungen nach Migrationspfad bewerten\nTiers unterscheiden sich bei SLA, Clustering, Replikation, Persistenz, Enterprise-Funktionen und Netzwerkoptionen\nCache-Größe, Eviction Policy, TTLs und Serialization bestimmen Stabilität und Kosten\nPersistenz und Replikation reduzieren Datenverlust, ersetzen aber kein Primärdatenmodell\nPrivate Link, Firewall, TLS und Entra-Integration je Dienst und Tier prüfen",
                'use_cases' => "Read-through- und Write-through-Caching für Web-Apps und APIs\nSession State und Warenkorb-Daten mit niedriger Latenz\nRate Limiting, Locks und kurzlebige Koordinationsdaten\nPub/Sub- und Event-nahe App-Muster\nBeschleunigung von Datenbank- und Suchzugriffen",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/azure-cache-for-redis/cache-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/cache/',
                'sort_order' => 60,
            ],
            [
                'category' => 'ki-machine-learning',
                'slug' => 'azure-ai-services',
                'title' => 'Azure AI Services',
                'subtitle' => 'Vortrainierte KI-APIs für Vision, Speech, Language, Dokumente, Übersetzung und Sicherheit.',
                'summary' => 'Azure AI Services bündelt vortrainierte KI-Dienste, die Du per API in Anwendungen integrierst.',
                'content' => 'Azure AI Services passt, wenn Du Funktionen wie Bilderkennung, Spracherkennung, Übersetzung, Textanalyse, Dokumentverarbeitung oder Content Safety nutzen willst, ohne eigene Modelle von Grund auf zu trainieren. Viele Dienste lassen sich einzeln oder über eine Multi-Service-Ressource bereitstellen. Der Dienst ist für produktive App-Integration geeignet, wenn Region, Datenverarbeitung, Authentifizierung, Quotas und Kosten pro Transaktion sauber geplant werden. Für eigene Modelltrainings, Pipelines und MLOps ist Azure Machine Learning passender.',
                'features' => "Multi-Service-Ressource vereinfacht Keys und Abrechnung, aber nicht jeder Dienst und jede Region passt zu jedem Szenario\nKosten entstehen meist transaktions-, seiten-, minuten-, zeichen- oder tokenbasiert je Dienst\nFree-Tiers sind für Tests hilfreich, aber nicht für produktive Last und SLA zu verplanen\nDatenresidenz, Logging, Content Safety und Responsible-AI-Anforderungen je Dienst prüfen\nQuota, Rate Limits und Modellversionen vor Rollout und Lasttest absichern",
                'use_cases' => "Dokumentenerkennung mit Azure AI Document Intelligence\nSpeech-to-Text, Text-to-Speech und Übersetzung in Apps\nBildanalyse, OCR und Moderation von Uploads\nSprach- und Textanalyse für Support, Tickets und Suche\nContent Safety für generative KI und Benutzerinhalte",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/ai-services/what-are-ai-services',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/cognitive-services/',
                'sort_order' => 40,
            ],
            [
                'category' => 'ki-machine-learning',
                'slug' => 'machine-learning',
                'title' => 'Azure Machine Learning',
                'subtitle' => 'Plattform für klassischen ML-Lifecycle, Training, MLOps und Modellbetrieb.',
                'summary' => 'Azure Machine Learning unterstützt Data Scientists und Plattformteams beim Trainieren, Registrieren, Bereitstellen und Überwachen von ML-Modellen.',
                'content' => 'Azure Machine Learning ist die Plattform für klassischen Machine-Learning-Lifecycle und MLOps in Azure. Du verwaltest Workspaces, Compute, Datastores, Environments, Jobs, Pipelines, Modellregistrierung, Endpunkte und Monitoring. Der Dienst passt, wenn eigene Modelle trainiert, reproduzierbar betrieben und in CI/CD-Prozesse integriert werden sollen. Für reine GenAI-App-Entwicklung mit Modellkatalog und Agenten ist Microsoft Foundry oft der passendere Einstieg.',
                'features' => "Kosten entstehen vor allem durch Compute, Storage, Managed Online Endpoints, Netzwerke und abhängige Dienste\nCompute Instances nicht dauerhaft laufen lassen; Auto-Shutdown und Quotas nutzen\nTraining, Batch Inference und Online Inference haben unterschiedliche Betriebs- und Kostenmodelle\nPrivate Link, Managed VNet, Datenzugriff und Identitäten früh in die Plattformarchitektur aufnehmen\nMLOps braucht Versionierung von Daten, Code, Environments, Modellen und Pipelines, sonst wird Betrieb schwer prüfbar",
                'use_cases' => "Training klassischer ML-Modelle mit skalierbarem Compute\nMLOps-Pipelines für Build, Test, Registrierung und Deployment\nBatch Scoring und Online Endpoints für Vorhersagen\nFeature Engineering und Experimenttracking für Data-Science-Teams\nGovernance von Modellen, Datenzugriff und Produktionsfreigaben",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/machine-learning/overview-what-is-azure-machine-learning',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/machine-learning/',
                'sort_order' => 50,
            ],
            [
                'category' => 'integration-kommunikation',
                'slug' => 'event-grid',
                'title' => 'Azure Event Grid',
                'subtitle' => 'Event-Routing mit Publish/Subscribe für Azure-, SaaS- und eigene Ereignisse.',
                'summary' => 'Azure Event Grid verteilt Ereignisse aus Quellen an Handler wie Functions, Logic Apps, Webhooks und Event Hubs.',
                'content' => 'Azure Event Grid ist der Event-Router für reaktive Architekturen. Er passt, wenn Systeme auf Zustandsänderungen reagieren sollen, zum Beispiel Blob erstellt, Ressource geändert oder eigenes Domänenereignis veröffentlicht. Event Grid transportiert Ereignisse, speichert aber keine langen Nachrichtenströme wie Event Hubs und ersetzt keinen Enterprise-Broker wie Service Bus. Für zuverlässige Verarbeitung musst Du Retry, Dead Lettering, Idempotenz und Event-Schema bewusst planen.',
                'features' => "Abrechnung erfolgt pro Operation, zum Beispiel Veröffentlichung, Zustellung, Filterung und Managementvorgang\nSystem Topics, Custom Topics, Domains und Partner Topics nach Mandanten- und Betriebsmodell wählen\nEvent Grid ist für Ereignisse gedacht, nicht für große Payloads oder lang laufende Streams\nDead Lettering, Retry Policy und idempotente Consumer für produktive Verarbeitung einplanen\nMQTT und Namespace-Funktionen haben eigene Limits, Preis- und Architekturdetails",
                'use_cases' => "Reaktion auf Storage-, Resource- oder App-Ereignisse\nServerlose Automatisierung mit Azure Functions und Logic Apps\nEntkopplung von Microservices über Ereignisse\nEvent-Verteilung an mehrere Subscriber\nIoT- oder MQTT-nahe Event-Ingestion über Event Grid Namespaces",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/event-grid/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/event-grid/',
                'sort_order' => 40,
            ],
            [
                'category' => 'integration-kommunikation',
                'slug' => 'event-hubs',
                'title' => 'Azure Event Hubs',
                'subtitle' => 'Streaming-Ingestion für Telemetrie, Logs und Big-Data-Ereignisse.',
                'summary' => 'Azure Event Hubs nimmt große Ereignisströme auf und stellt sie Consumergruppen für Verarbeitung und Analytics bereit.',
                'content' => 'Azure Event Hubs ist für hohe Streaming-Datenraten aus Anwendungen, Geräten, Logs und Plattformen gedacht. Der Dienst passt, wenn viele Events aufgenommen und von mehreren Consumern verarbeitet werden sollen. Er ist Kafka-kompatibel, aber kein vollständiger Kafka-Cluster mit beliebiger Brokerkontrolle. Architekturentscheidend sind Partitionen, Consumergruppen, Retention, Capture, Throughput Units, Processing Units oder Dedicated Capacity.',
                'features' => "Basic, Standard, Premium und Dedicated unterscheiden sich bei Features, Isolation, Skalierung und Kosten\nThroughput Units, Processing Units oder Capacity Units bestimmen Durchsatz und Preis je Tier\nPartitionenzahl nach Parallelität und langfristigem Wachstum wählen, weil Änderungen eingeschränkt sein können\nCapture schreibt Streams nach Storage oder Data Lake und verursacht zusätzliche Speicher- und Transaktionskosten\nConsumer müssen Checkpointing, Reihenfolge pro Partition und Wiederholungen sauber behandeln",
                'use_cases' => "Log- und Telemetrie-Ingestion für Analytics und SIEM\nIoT- und Gerätedatenströme mit hohem Durchsatz\nKafka-kompatible Event-Ingestion für Cloud-Apps\nStreaming in Azure Stream Analytics, Functions, Databricks oder Fabric\nEvent Capture für Rohdatenablage im Data Lake",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/event-hubs/event-hubs-about',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/event-hubs/',
                'sort_order' => 50,
            ],
            [
                'category' => 'integration-kommunikation',
                'slug' => 'communication-services',
                'title' => 'Azure Communication Services',
                'subtitle' => 'Kommunikations-APIs für SMS, E-Mail, Chat, Voice und Video.',
                'summary' => 'Azure Communication Services integriert Kommunikationskanäle per API in Web-, Mobile- und Geschäftsanwendungen.',
                'content' => 'Azure Communication Services ist passend, wenn Anwendungen selbst SMS, E-Mail, Chat, Telefonie oder Video bereitstellen sollen. Der Dienst liefert APIs, SDKs und Integrationen, übernimmt aber nicht automatisch komplette Contact-Center- oder Compliance-Prozesse. Du musst Identitäten, Einwilligungen, Rufnummern, Zustellbarkeit, Missbrauchsschutz und regionale Verfügbarkeit selbst planen. Kosten entstehen nutzungsbasiert je Kanal, Ziel, Dauer oder Nachricht.',
                'features' => "SMS-, Telefonie- und E-Mail-Verfügbarkeit unterscheiden sich nach Land, Nummerntyp, Regulatorik und Absenderanforderungen\nAbrechnung ist nutzungsbasiert und kanalabhängig, zum Beispiel Nachricht, Minute, Teilnehmer oder E-Mail-Volumen\nFür E-Mail sind Domains, DNS-Records, Reputation und Bounce-Verarbeitung entscheidend\nNotruf, Aufzeichnung, Datenschutz, Consent und Aufbewahrung je Szenario rechtlich prüfen\nTeams-Interoperabilität, Telefonnummern und direkte Routingoptionen vor Architekturentscheidung validieren",
                'use_cases' => "SMS-Benachrichtigungen und Verifizierungscodes\nE-Mail-Versand aus Anwendungen und Portalen\nChat, Voice und Video in Kunden- oder Supportprozessen\nTermin-, Service- und Field-Worker-Kommunikation\nIntegration von Kommunikationsfunktionen in eigene Apps",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/communication-services/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/communication-services/',
                'sort_order' => 60,
            ],
            [
                'category' => 'analytics-big-data',
                'slug' => 'microsoft-fabric',
                'title' => 'Microsoft Fabric',
                'subtitle' => 'SaaS-Analytics-Plattform mit OneLake, Workloads und Kapazitäten.',
                'summary' => 'Microsoft Fabric bündelt Data Factory, Data Engineering, Data Warehouse, Real-Time Intelligence, Data Science und Power BI auf OneLake.',
                'content' => 'Microsoft Fabric ist Microsofts SaaS-Analytics-Plattform für Datenintegration, Lakehouse, Warehouse, Echtzeitdaten, Data Science und BI. Der Dienst ist strategisch der neue Zielpfad für viele Analytics-Szenarien, die früher mit Synapse und Data Factory einzeln gebaut wurden. Fabric passt, wenn Teams eine einheitliche SaaS-Erfahrung mit OneLake, Workspaces, Kapazitäten und Governance suchen. Für tief Azure-native Spezialarchitekturen, bestehende Spark-Setups oder harte Netzwerkisolation müssen Synapse, Databricks oder Azure-Dienste weiter geprüft werden.',
                'features' => "Abrechnung erfolgt über Fabric-Kapazitäten mit F-SKUs, Power BI Kapazitäten oder Trial-Modellen je Tenant und Region\nOneLake, Shortcuts, Workspaces, Domains und Berechtigungen sauber governancenah modellieren\nNicht jede Synapse- oder Data-Factory-Funktion ist 1:1 in Fabric identisch; Migration pro Pipeline und Workload prüfen\nKapazitätsauslastung, Autoscale, Pausieren und Monitoring entscheiden über Kosten und Performance\nDatenresidenz, Tenant-Einstellungen und externe Freigaben für DACH/DSGVO vor Produktivstart klären",
                'use_cases' => "Lakehouse und Warehouse auf OneLake für BI und Analytics\nAblösung oder Ergänzung von Synapse- und Data-Factory-Strecken\nSelf-Service-BI mit zentraler Governance\nReal-Time Intelligence für Event- und Logdaten\nData Science und ML-nahe Analysen in einer SaaS-Plattform",
                'docs_url' => 'https://learn.microsoft.com/de-de/fabric/get-started/microsoft-fabric-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/microsoft-fabric/',
                'sort_order' => 40,
            ],
            [
                'category' => 'analytics-big-data',
                'slug' => 'stream-analytics',
                'title' => 'Azure Stream Analytics',
                'subtitle' => 'Echtzeit-Stream-Verarbeitung mit SQL-ähnlicher Abfragesprache.',
                'summary' => 'Azure Stream Analytics verarbeitet Datenströme aus Event Hubs, IoT Hub oder Storage in Echtzeit.',
                'content' => 'Azure Stream Analytics ist ein verwalteter Dienst für kontinuierliche Stream-Verarbeitung. Du schreibst SQL-ähnliche Abfragen, liest Daten aus Event Hubs, IoT Hub oder Blob Storage und schreibst Ergebnisse in Ziele wie SQL, Data Lake, Event Hubs, Power BI oder Functions. Der Dienst passt, wenn Echtzeitfilter, Aggregationen, Fensterfunktionen und einfache Anreicherungen ohne eigenes Streaming-Cluster benötigt werden. Für komplexe Stateful-Processing-Frameworks oder Spark-Streaming ist Databricks oder Fabric Real-Time Intelligence zu prüfen.',
                'features' => "Streaming Units bestimmen Durchsatz, Parallelität und Kosten\nPartitionierung von Input, Query und Output muss zusammenpassen, sonst skaliert der Job nicht sauber\nSpäte, ungeordnete und doppelte Events über Zeitrichtlinien und Query-Design behandeln\nReference Data eignet sich für kleine Anreicherungen, nicht für beliebig große Lookups\nKompatibilitätslevel, Funktionen und Outputs vor Migration oder CI/CD festlegen",
                'use_cases' => "IoT-Telemetrie in Echtzeit filtern und aggregieren\nAnomalien, Schwellenwerte und Alarme aus Event Hubs erkennen\nLive-Dashboards und Power-BI-Streamingdaten speisen\nDatenströme in Storage, SQL oder Data Lake schreiben\nEinfache Echtzeit-ETL ohne eigenes Cluster betreiben",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/stream-analytics/stream-analytics-introduction',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/stream-analytics/',
                'sort_order' => 50,
            ],
            [
                'category' => 'analytics-big-data',
                'slug' => 'data-explorer',
                'title' => 'Azure Data Explorer',
                'subtitle' => 'Schnelle Analyse von Log-, Telemetrie- und Zeitreihendaten mit KQL.',
                'summary' => 'Azure Data Explorer analysiert große Mengen strukturierter und halbstrukturierter Daten interaktiv mit Kusto Query Language.',
                'content' => 'Azure Data Explorer ist für schnelle Ad-hoc-Analysen über Logs, Telemetrie, Metriken, Zeitreihen und Security-Daten optimiert. Der Dienst arbeitet mit Clustern, Datenbanken, Tabellen, Ingestion-Pipelines und KQL. Er passt, wenn Daten mit hoher Schreibrate aufgenommen und sehr schnell explorativ abgefragt werden müssen. Für klassische relationale Transaktionen oder allgemeines Data Warehousing sind Azure SQL, Fabric Warehouse oder Synapse passender.',
                'features' => "Kosten entstehen vor allem durch Cluster-Compute, Storage, Markup, Ingestion und Datenaufbewahrung\nClustergröße, Autoscale, Hot Cache, Retention und Update Policies prägen Performance und Preis\nIngestion über Event Hubs, IoT Hub, Event Grid, Kafka, Storage oder Pipelines sauber entkoppeln\nKQL-Modellierung, Materialized Views und Partitionierung beeinflussen Abfragekosten stark\nDatenexport, RBAC, Private Endpoints und kundenseitig verwaltete Schlüssel für Enterprise-Betrieb prüfen",
                'use_cases' => "Log- und Telemetrieanalyse für Plattformen und Anwendungen\nZeitreihenauswertung für IoT, Industrie und Monitoring\nSecurity Hunting und KQL-basierte Untersuchungen\nClickstream-, Nutzungs- und Betriebsdaten interaktiv analysieren\nNear-Real-Time-Analytics mit hoher Ingestion-Rate",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/data-explorer/data-explorer-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/data-explorer/',
                'sort_order' => 60,
            ],
            [
                'category' => 'migration',
                'slug' => 'azure-migrate',
                'title' => 'Azure Migrate',
                'subtitle' => 'Discovery, Assessment und Migration für Server, Datenbanken und Web-Apps.',
                'summary' => 'Azure Migrate ist der zentrale Hub zur Bewertung und Migration von On-Premises-Workloads nach Azure.',
                'content' => 'Azure Migrate hilft Dir, Server, Datenbanken, Web-Apps und virtuelle Desktops zu inventarisieren, zu bewerten und nach Azure zu migrieren. Der Dienst passt für strukturierte Migrationsprogramme, weil Abhängigkeiten, Sizing, Readiness, Kostenabschätzung und Migrationswellen zentral sichtbar werden. Er ersetzt aber keine fachliche Migrationsplanung, Tests oder Zielarchitektur. Die Bewertung ist kostenlos, die Zielressourcen, Replikation, Storage, Netzwerk und Drittanbieter-Tools können Kosten erzeugen.',
                'features' => "Azure Migrate selbst ist grundsätzlich kostenlos; Zielressourcen und abhängige Dienste werden separat berechnet\nDiscovery Appliance, Credentials, Netzwerkzugriff und Datensammlung früh mit Security und Betrieb abstimmen\nAssessments sind nur so gut wie Laufzeitdaten, Abhängigkeitsanalyse und korrekte Zielannahmen\nServer-, SQL-, Web-App- und VDI-Migrationen haben unterschiedliche Tools und Grenzen\nCutover, Testmigration, Rollback, DNS, Identität und Betriebsübergabe pro Welle planen",
                'use_cases' => "Discovery und Assessment von VMware-, Hyper-V- und physischen Servern\nSizing und Kostenabschätzung für Azure-VM-Zielumgebungen\nMigrationswellen für Rechenzentrums- oder Standortablösungen\nBewertung von SQL Server und Web-Apps vor Modernisierung\nProjektsteuerung für Lift-and-Shift und Modernisierungspfade",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/migrate/migrate-services-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/azure-migrate/',
                'sort_order' => 10,
            ],
            [
                'category' => 'migration',
                'slug' => 'database-migration-service',
                'title' => 'Azure Database Migration Service',
                'subtitle' => 'Verwaltete Online- und Offline-Migration für Datenbanken nach Azure.',
                'summary' => 'Azure Database Migration Service unterstützt Datenbankmigrationen aus verschiedenen Quellen zu Azure-Datenbankdiensten.',
                'content' => 'Azure Database Migration Service ist ein verwalteter Dienst für Datenbankmigrationen mit Online- oder Offline-Ansätzen. Er passt, wenn Datenbanken nach Azure SQL, SQL Managed Instance, PostgreSQL, MySQL oder andere unterstützte Ziele migriert werden sollen und Du einen geführten Migrationsdienst brauchst. Der Dienst ersetzt nicht die Schema-, Code- und Kompatibilitätsanalyse, die vorab mit passenden Tools erfolgen muss. Bei vielen modernen Pfaden empfiehlt Microsoft inzwischen zielnative Migrationserfahrungen, deshalb solltest Du DMS und native Werkzeuge je Quelle vergleichen.',
                'features' => "Online-Migration reduziert Downtime, braucht aber Replikation, Netzwerkstabilität und sauberen Cutover\nOffline-Migration ist einfacher, erzeugt aber längere Ausfallzeit\nUnterstützte Quellen, Ziele und Features unterscheiden sich je Datenbankengine\nSchema-Konvertierung, inkompatible Features, Logins, Jobs und Berechtigungen separat behandeln\nKosten und Verfügbarkeit hängen vom gewählten DMS-Modell, Zielressourcen und Datenbewegung ab",
                'use_cases' => "SQL Server zu Azure SQL Managed Instance oder Azure SQL Database migrieren\nPostgreSQL- oder MySQL-Migrationen zu verwalteten Azure-Diensten\nMinimierung von Downtime über Online-Migration\nTestmigrationen und Cutover-Proben für kritische Datenbanken\nDatenbankmodernisierung im Rahmen größerer Azure-Migrationen",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/dms/dms-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/database-migration/',
                'sort_order' => 20,
            ],
            [
                'category' => 'management-governance',
                'slug' => 'site-recovery',
                'title' => 'Azure Site Recovery',
                'subtitle' => 'Disaster-Recovery-Orchestrierung und Replikation für VMs und Server.',
                'summary' => 'Azure Site Recovery repliziert Workloads und orchestriert Failover, Test-Failover und Failback für DR-Szenarien.',
                'content' => 'Azure Site Recovery ist der DR-Dienst für geplante und ungeplante Ausfälle von VMs und Servern. Er passt, wenn Du Replikation, Recovery-Pläne, Test-Failover, Netzwerkzuordnung und Failback zentral steuern willst. Der Dienst ist kein Backup-Ersatz, weil er Wiederanlauf und Replikation adressiert, nicht langfristige Aufbewahrung. RPO, RTO, App-Abhängigkeiten, Netzwerk, DNS, Identität und Runbooks müssen vor dem Ernstfall getestet werden.',
                'features' => "Abrechnung erfolgt pro geschützter Instanz nach kostenloser Testphase, Ziel-Compute und Storage kommen separat dazu\nRPO und RTO hängen von Workload, Bandbreite, Änderungsrate, Zielregion und Tests ab\nTest-Failover regelmäßig isoliert durchführen, sonst bleibt der DR-Plan Theorie\nNicht jede VM-, Disk-, Region- oder Plattformkombination ist unterstützt\nAzure Backup und Site Recovery gemeinsam planen, weil Sicherung und DR unterschiedliche Ziele haben",
                'use_cases' => "DR für Azure-VMs zwischen Regionen\nMigration und Schutz von VMware- oder physischen Servern\nRegelmäßige Notfalltests mit isolierten Netzwerken\nRecovery-Pläne für mehrstufige Anwendungen\nStandortablösung mit kontrolliertem Failover",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/site-recovery/site-recovery-overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/site-recovery/',
                'sort_order' => 40,
            ],
            [
                'category' => 'management-governance',
                'slug' => 'automation',
                'title' => 'Azure Automation',
                'subtitle' => 'Runbooks und Automatisierung für wiederkehrende Betriebsaufgaben.',
                'summary' => 'Azure Automation führt Runbooks aus und automatisiert Verwaltungsaufgaben über Azure, Hybrid und Drittanbieter-Systeme.',
                'content' => 'Azure Automation ist passend, wenn wiederkehrende Betriebsaufgaben als PowerShell- oder Python-Runbooks zentral laufen sollen. Der Dienst kann Azure-Ressourcen, externe APIs und Hybrid Worker ansprechen. Er ersetzt keine moderne CI/CD-Plattform und kein komplettes Konfigurationsmanagement, ist aber stark für Betrieb, Scheduling und kontrollierte Administrationsabläufe. Für Updates ist Azure Update Manager der neuere Zielpfad.',
                'features' => "Process Automation wird nach Jobausführungsminuten abgerechnet, Freikontingente und Hybrid Worker prüfen\nRun As Accounts sind Legacy; Managed Identities für neue Runbooks bevorzugen\nModule, Runtime-Versionen, Credentials, Zertifikate und Secrets aktiv pflegen\nHybrid Runbook Worker braucht Netzwerk, Agent, Identität und Monitoring\nUpdate Management in Automation wurde durch Azure Update Manager als Zielmodell abgelöst",
                'use_cases' => "Geplantes Starten, Stoppen und Skalieren von Ressourcen\nBetriebsrunbooks für Wartung, Cleanup und Reporting\nAutomatisierte Reaktion auf Alerts und Tickets\nHybrid-Automatisierung über Runbook Worker\nSichere Ausführung wiederkehrender Admin-Aufgaben",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/automation/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/automation/',
                'sort_order' => 50,
            ],
            [
                'category' => 'management-governance',
                'slug' => 'update-manager',
                'title' => 'Azure Update Manager',
                'subtitle' => 'Zentrales Patch-Management für Azure-, Arc- und Hybridserver.',
                'summary' => 'Azure Update Manager bewertet und orchestriert Betriebssystemupdates für Windows- und Linux-Server.',
                'content' => 'Azure Update Manager ist der zentrale Dienst für Patch-Assessment, Updateplanung und Wartungsfenster über Azure-VMs und Arc-fähige Server. Er passt, wenn Du Updates nach Zeitplänen, dynamischen Bereichen, Compliance-Sichten und Wartungskonfigurationen steuern willst. Der Dienst ersetzt nicht Anwendungs-Releaseprozesse oder vollständiges Vulnerability Management. Für Hybridserver müssen Azure Arc, Agenten, Netzwerk und Abrechnung sauber eingeplant werden.',
                'features' => "Azure-VMs sind im Dienst enthalten; Arc-fähige Server außerhalb Azure können pro Server berechnet werden\nMaintenance Configurations, dynamische Scopes und Reboot-Verhalten vor Rollout testen\nLinux- und Windows-Paketquellen bleiben relevant, Update Manager ersetzt keine Repository-Strategie\nAzure Automation Update Management ist nicht der Zielpfad für neue Designs\nPatch-Compliance mit Defender for Cloud, Policy und Change-Prozessen verbinden",
                'use_cases' => "Patch-Orchestrierung für Azure-VMs in Landing Zones\nZentrales Update-Management für Arc-fähige Windows- und Linux-Server\nWartungsfenster für produktive Systeme steuern\nCompliance-Reporting für Betrieb und Security\nAblösung alter Automation-Update-Management-Setups",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/update-manager/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/details/azure-arc/',
                'sort_order' => 60,
            ],
            [
                'category' => 'management-governance',
                'slug' => 'lighthouse',
                'title' => 'Azure Lighthouse',
                'subtitle' => 'Mandantenübergreifende delegierte Verwaltung für Dienstleister und zentrale Teams.',
                'summary' => 'Azure Lighthouse ermöglicht sichere Verwaltung fremder oder interner Azure-Tenants über delegierte RBAC-Zugriffe.',
                'content' => 'Azure Lighthouse ist für MSPs, Konzern-IT und zentrale Plattformteams gedacht, die mehrere Tenants oder Kundenumgebungen verwalten. Kunden delegieren Abonnements oder Ressourcengruppen über Azure Resource Manager, danach arbeiten Admins aus dem eigenen Tenant mit definierten Rollen. Der Dienst passt, wenn Betrieb ohne Gastkonten, Tenantwechsel und manuelle Einzellösungen skalieren soll. Er ersetzt keine saubere Vertrags-, Rollen- und Prozessdefinition zwischen Betreiber und Kunde.',
                'features' => "Azure Lighthouse selbst ist kostenlos; verwaltete Azure-Dienste in Kundenumgebungen bleiben kostenpflichtig\nDelegation erfolgt über ARM-Templates, Managed Service Offers oder Service Provider Registration\nRBAC-Rollen, Gruppen, PIM, JIT und Least Privilege vor Onboarding festlegen\nNicht jede Azure-Funktion unterstützt delegierte Verwaltung vollständig\nOffboarding, Audit, Activity Logs und Zuständigkeiten je Kunde dokumentieren",
                'use_cases' => "MSP-Betrieb mehrerer Kundenumgebungen aus einem Tenant\nZentrale Konzernverwaltung mehrerer Azure-Tenants\nDelegierte Security-, Monitoring- und Governance-Aufgaben\nSkalierbares Onboarding von Abonnements und Ressourcengruppen\nBetrieb ohne dauerhafte Gastadmin-Konten im Kundentenant",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/lighthouse/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/',
                'sort_order' => 70,
            ],
            [
                'category' => 'management-governance',
                'slug' => 'bicep-arm',
                'title' => 'Bicep / Azure Resource Manager',
                'subtitle' => 'Deklarative Infrastructure as Code für Azure-Ressourcen.',
                'summary' => 'Bicep und Azure Resource Manager stellen Azure-Ressourcen deklarativ über Templates, Module und What-If bereit.',
                'content' => 'Bicep ist die domänenspezifische Sprache für Azure Resource Manager und vereinfacht ARM-Templates deutlich. Der Ansatz passt, wenn Azure-Ressourcen wiederholbar, versioniert und reviewed bereitgestellt werden sollen. ARM bleibt die Bereitstellungsschicht, Bicep kompiliert in ARM-Templates und lässt sich über CLI, PowerShell, Pipelines oder GitHub Actions ausführen. Es ist kein Konfigurationsmanagement für Betriebssysteme und ersetzt keine Governance über Policy, Rollen und Landing-Zone-Standards.',
                'features' => "Bicep und ARM verursachen keine eigene Servicegebühr; bereitgestellte Ressourcen werden normal berechnet\nWhat-If vor produktiven Änderungen nutzen, aber Ergebnis trotzdem fachlich prüfen\nModule, Parameter, Outputs und Naming-Standards früh definieren\nSecrets nicht im Template speichern, sondern Key Vault, Parameter Stores oder Pipeline-Secrets nutzen\nRole Assignments, Locks, Policy Assignments und Deployment Scopes brauchen klare Berechtigungen",
                'use_cases' => "Landing-Zone- und Plattformmodule versioniert bereitstellen\nWiederholbare Deployments für Apps, Netzwerke, Datenbanken und Monitoring\nReviewbare Infrastrukturänderungen über Pull Requests\nWhat-If-Prüfung vor produktiven Deployments\nStandardisierte Umgebungen für Dev, Test und Produktion",
                'docs_url' => 'https://learn.microsoft.com/de-de/azure/azure-resource-manager/bicep/overview',
                'pricing_url' => 'https://azure.microsoft.com/de-de/pricing/',
                'sort_order' => 80,
            ],
        ];
    }

    private static function normalize_newlines(string $value): string
    {
        return str_replace(["\\r\\n", "\\n", "\\r"], ["\n", "\n", "\n"], $value);
    }

    private static function upsert_marker(object $db, string $prefix, string $markerKey, string $markerVersion): void
    {
        $exists = $db->prepare("SELECT id FROM {$prefix}m365azure_settings WHERE setting_key = ?");
        $exists->execute([$markerKey]);
        if ($exists->fetch()) {
            $stmt = $db->prepare("UPDATE {$prefix}m365azure_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$markerVersion, $markerKey]);
            return;
        }

        $stmt = $db->prepare("INSERT INTO {$prefix}m365azure_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$markerKey, $markerVersion]);
    }
}
