<?php
/**
 * CMS M365 License – Katalog-Definitionen
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365LIC_Catalog
{
    /**
     * @return array<string,array<string,mixed>>
     */
    public static function feature_definitions(): array
    {
        return [
            'mail' => ['label' => 'Exchange / Mail', 'group' => 'core', 'description' => 'Postfach, Kalender, Kontakte und mobiler Mailzugriff.', 'base' => true],
            'teams' => ['label' => 'Microsoft Teams', 'group' => 'core', 'description' => 'Chat, Meetings, Kanäle und Zusammenarbeit.', 'base' => true],
            'office_web' => ['label' => 'Office Web Apps', 'group' => 'core', 'description' => 'Word, Excel und PowerPoint im Browser.', 'base' => true],
            'office_desktop' => ['label' => 'Office Desktop Apps', 'group' => 'core', 'description' => 'Installierbare Desktop-Apps für Office.', 'base' => true],
            'terminalserver' => ['label' => 'Terminalserver / Shared Activation', 'group' => 'core', 'description' => 'Nutzung der Office-Apps auf RDS-/Terminalservern mit Shared Computer Activation.', 'base' => true],
            'onedrive' => ['label' => 'OneDrive', 'group' => 'core', 'description' => 'Persönlicher Cloud-Speicher für Benutzer.', 'base' => true],
            'sharepoint' => ['label' => 'SharePoint / Intranet', 'group' => 'core', 'description' => 'Teamseiten, Dateien, Intranet und Dokumentenablage.', 'base' => true],
            'forms' => ['label' => 'Microsoft Forms', 'group' => 'collaboration', 'description' => 'Umfragen, Formulare und Quizze.', 'base' => true],
            'bookings' => ['label' => 'Microsoft Bookings', 'group' => 'collaboration', 'description' => 'Terminbuchungen und Self-Service-Scheduling.', 'base' => true],
            'stream' => ['label' => 'Microsoft Stream', 'group' => 'collaboration', 'description' => 'Video- und Medienbereitstellung im Microsoft-365-Kontext.', 'base' => true],
            'viva_engage' => ['label' => 'Viva Engage', 'group' => 'collaboration', 'description' => 'Communities, Social Intranet und Mitarbeiteraustausch.', 'base' => true],
            'windows_rights' => ['label' => 'Windows Enterprise Rechte', 'group' => 'security', 'description' => 'Windows-Enterprise-Berechtigungen im Microsoft-365-Kontext.', 'base' => true],
            'intune' => ['label' => 'Intune Plan 1 / Geräteverwaltung', 'group' => 'security-addon', 'description' => 'MDM/MAM, Richtlinien und Endpoint-Verwaltung – einzeln oder in Suites.', 'base' => false],
            'defender' => ['label' => 'Defender / Sicherheit', 'group' => 'security', 'description' => 'Erweiterte Sicherheits- und Schutzfunktionen.', 'base' => true],
            'archive' => ['label' => 'Archiv / Compliance', 'group' => 'security', 'description' => 'Archivierung, erweiterte Compliance und Aufbewahrung.', 'base' => true],
            'phone_system' => ['label' => 'Telefonie / Phone System', 'group' => 'addons', 'description' => 'Telefonie-nahe Teams-Funktionen und Rufsteuerung.', 'base' => false],
            'audio_conf' => ['label' => 'Audiokonferenzen', 'group' => 'addons', 'description' => 'Einwahl in Meetings via Telefon.', 'base' => false],
            'power_bi' => ['label' => 'Power BI Pro', 'group' => 'addons', 'description' => 'Interaktive Reports, Freigaben und Dashboards.', 'base' => false],
            'power_apps' => ['label' => 'Power Apps Premium', 'group' => 'addons', 'description' => 'Premium-Apps, Dataverse und erweiterte Business-App-Szenarien.', 'base' => false],
            'visio' => ['label' => 'Visio', 'group' => 'addons', 'description' => 'Diagramme, Netzpläne und Prozessdesign.', 'base' => false],
            'project' => ['label' => 'Project', 'group' => 'addons', 'description' => 'Projektplanung, Ressourcen und Roadmaps.', 'base' => false],
            'planner' => ['label' => 'Planner Premium / Plan 1', 'group' => 'addons', 'description' => 'Planung, Aufgaben und Work-Management.', 'base' => false],
            'automation' => ['label' => 'Power Automate Premium', 'group' => 'addons', 'description' => 'Premium-Workflows und Automationen.', 'base' => false],
            'teams_premium' => ['label' => 'Teams Premium', 'group' => 'addons', 'description' => 'Erweiterte Meeting-, Webinar- und Schutzfunktionen.', 'base' => false],
            'entra_id_p1' => ['label' => 'Microsoft Entra ID P1', 'group' => 'identity', 'description' => 'Erweiterte Identitäts- und Zugriffssteuerung für Entra / Azure AD.', 'base' => false],
            'entra_id_p2' => ['label' => 'Microsoft Entra ID P2', 'group' => 'identity', 'description' => 'Premium-Identitätsschutz, PIM und risikobasierte Zugriffsrichtlinien.', 'base' => false],
            'entra_governance' => ['label' => 'Microsoft Entra ID Governance', 'group' => 'identity', 'description' => 'Lifecycle, Access Reviews und Governance-Funktionen als Entra-Erweiterung.', 'base' => false],
            'entra_suite' => ['label' => 'Microsoft Entra Suite', 'group' => 'identity', 'description' => 'Entra-Zusatzbundle für erweiterte Identität, Internet Access und Private Access.', 'base' => false],
            'intune_device' => ['label' => 'Intune Device', 'group' => 'security-addon', 'description' => 'Gerätebezogene Intune-Lizenz für gemeinsam genutzte oder kioskartige Endgeräte.', 'base' => false],
            'exchange_protection' => ['label' => 'Exchange Online Protection', 'group' => 'security-addon', 'description' => 'Basisschutz für Mail-Flow, Spam und Malware im Exchange-Kontext.', 'base' => false],
            'defender_business' => ['label' => 'Microsoft Defender for Business', 'group' => 'security-addon', 'description' => 'Endpoint- und Bedrohungsschutz für kleinere und mittlere Unternehmen.', 'base' => false],
            'defender_office_p1' => ['label' => 'Defender for Office 365 Plan 1', 'group' => 'security-addon', 'description' => 'Mail-, Link- und Anhangsschutz für Microsoft 365.', 'base' => false],
            'defender_office_p2' => ['label' => 'Defender for Office 365 Plan 2', 'group' => 'security-addon', 'description' => 'Erweiterter Mailschutz mit Investigation, Simulation und Threat Explorer.', 'base' => false],
            'defender_endpoint_p1' => ['label' => 'Defender for Endpoint Plan 1', 'group' => 'security-addon', 'description' => 'Einfacher Endpunktschutz für verwaltete Geräte.', 'base' => false],
            'defender_endpoint_p2' => ['label' => 'Defender for Endpoint Plan 2', 'group' => 'security-addon', 'description' => 'Erweiterter Endpunktschutz mit EDR und automatischer Reaktion.', 'base' => false],
            'defender_identity' => ['label' => 'Defender for Identity', 'group' => 'security-addon', 'description' => 'Identitätsbasierter Bedrohungsschutz für Active Directory und Hybrid-Identitäten.', 'base' => false],
            'defender_cloud_apps' => ['label' => 'Defender for Cloud Apps', 'group' => 'security-addon', 'description' => 'Cloud App Discovery, Session Controls und CASB-Funktionen.', 'base' => false],
            'frontline' => ['label' => 'Frontline / Kiosk', 'group' => 'worker', 'description' => 'Szenario für Firstline- bzw. Frontline-Mitarbeiter.', 'base' => true],
            'copilot_chat' => ['label' => 'Copilot Chat', 'group' => 'copilot', 'description' => 'Web-/Work-basiertes Copilot Chat-Erlebnis.', 'base' => false],
            'copilot_m365' => ['label' => 'Microsoft 365 Copilot', 'group' => 'copilot', 'description' => 'Copilot in Microsoft-365-Apps als Add-on-Lizenz.', 'base' => false],
            'copilot_studio' => ['label' => 'Copilot Studio', 'group' => 'copilot', 'description' => 'Agenten/Custom-Copilots im Unternehmenskontext.', 'base' => false],
            'security_copilot' => ['label' => 'Security Copilot', 'group' => 'copilot', 'description' => 'KI-gestützte Security-Analyse und Security-Workflows.', 'base' => false],
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function presets(): array
    {
        return [
            'mail_only' => ['label' => 'Nur Mail', 'features' => ['mail'], 'audience' => 'knowledge'],
            'mail_teams' => ['label' => 'Mail + Teams', 'features' => ['mail', 'teams'], 'audience' => 'knowledge'],
            'web_mail_onedrive' => ['label' => 'Web Office + Mail + OneDrive', 'features' => ['mail', 'office_web', 'onedrive', 'sharepoint', 'forms'], 'audience' => 'knowledge'],
            'knowledge_worker' => ['label' => 'Knowledge Worker', 'features' => ['mail', 'teams', 'office_web', 'office_desktop', 'onedrive', 'sharepoint', 'forms', 'stream', 'viva_engage'], 'audience' => 'knowledge'],
            'secure_business' => ['label' => 'Knowledge Worker Secure', 'features' => ['mail', 'teams', 'office_web', 'office_desktop', 'onedrive', 'sharepoint', 'forms', 'bookings', 'stream', 'viva_engage', 'intune', 'defender'], 'audience' => 'knowledge'],
            'executive_ai' => ['label' => 'Führungskraft + Copilot', 'features' => ['mail', 'teams', 'office_web', 'office_desktop', 'onedrive', 'sharepoint', 'forms', 'bookings', 'stream', 'viva_engage', 'intune', 'defender', 'copilot_m365'], 'audience' => 'knowledge'],
            'rds_enterprise' => ['label' => 'Terminalserver / RDS', 'features' => ['mail', 'teams', 'office_web', 'office_desktop', 'terminalserver', 'onedrive', 'sharepoint'], 'audience' => 'knowledge'],
            'frontline_basic' => ['label' => 'Frontline Basic', 'features' => ['frontline', 'teams', 'office_web', 'stream', 'viva_engage'], 'audience' => 'frontline'],
            'frontline_mail' => ['label' => 'Frontline mit Mail', 'features' => ['frontline', 'mail', 'teams', 'office_web', 'stream', 'viva_engage'], 'audience' => 'frontline'],
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function billing_options(): array
    {
        return [
            'annual_upfront' => [
                'key' => 'annual_upfront',
                'label' => '1 Jahr · jährliche Zahlung',
                'short_label' => 'Jahr / jährlich',
                'multiplier' => 1.00,
                'note' => 'Stand der Preise vom 17.03.2026 – tatsächliche Preise können je nach Vertrag und Konditionen abweichen.',
            ],
            'annual_monthly' => [
                'key' => 'annual_monthly',
                'label' => '1 Jahr · monatliche Zahlung (+5%)',
                'short_label' => 'Jahr / monatlich',
                'multiplier' => 1.05,
                'note' => 'Stand der Preise vom 17.03.2026 – tatsächliche Preise können je nach Vertrag und Konditionen abweichen.',
            ],
            'monthly_flex' => [
                'key' => 'monthly_flex',
                'label' => '1 Monat · monatlich (+20%)',
                'short_label' => 'Monat / flexibel',
                'multiplier' => 1.20,
                'note' => 'Wird aus dem Jahresvertrags-Preis auf flexible Monatslaufzeit hochgerechnet.',
            ],
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function default_settings(): array
    {
        return [
            'page_title' => 'Microsoft 365 Lizenzberater',
            'page_intro' => 'Bedarf erfassen, passende Lizenzen automatisch vorschlagen und die Auswertung als PDF exportieren.',
            'route_slug' => 'm365-lizenzberater',
            'default_currency' => 'EUR',
            'design_primary_color' => '#2563eb',
            'design_primary_dark' => '#1d4ed8',
            'design_accent_color' => '#f3f7fd',
            'design_page_background' => '#f8fafc',
            'design_surface_color' => '#ffffff',
            'design_text_color' => '#0f172a',
            'design_text_muted_color' => '#64748b',
            'design_border_radius' => '14',
            'default_group_key' => 'partner',
            'default_group_label' => 'Partner / Spezialgruppe',
            'allow_member_self_service' => '1',
            'public_default_billing_cycle' => 'annual_upfront',
            'member_default_billing_cycle' => 'annual_monthly',
            'group_default_billing_cycle' => 'annual_monthly',
            'public_daily_limit' => '2',
            'member_daily_limit' => '10',
            'group_daily_limit' => '25',
            'public_pdf_daily_limit' => '2',
            'member_pdf_daily_limit' => '10',
            'group_pdf_daily_limit' => '25',
            'upgrade_url' => '/kontakt',
            'limit_notice' => 'Das Tageslimit für Auswertungen wurde erreicht. Für höhere Limits bitte Upgrade oder Spezialzugang anfragen.',
            'pdf_footer' => 'Erstellt mit dem 365CMS M365 License Plugin – Preise und Lizenzvorschläge sollten vor Abschluss kommerziell geprüft werden.',
            'legal_note' => 'Die Auswertung ist eine Empfehlung auf Basis der gepflegten Paket- und Optionsmatrix. Preise, CSP-Konditionen und Microsoft-Servicebeschreibungen sind vor Bestellung zu prüfen.',
            'show_hero_panel' => '1',
            'show_hero_badges' => '1',
            'show_context_summary' => '1',
            'show_addon_overview' => '1',
            'show_legal_card' => '1',
            'sticky_sidebar' => '1',
            'show_missing_price_hint' => '1',
            'show_source_notes' => '1',
            'alternatives_json' => json_encode(self::default_alternative_offers(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'eu_alternatives_json' => json_encode(self::default_eu_alternative_offers(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'eu_ai_alternatives_json' => json_encode(self::default_eu_ai_alternative_offers(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function default_alternative_offers(): array
    {
        return [
            // Recherche-Stand 2026-03-17 auf Basis offizieller Pricing-Seiten der Anbieter.
            ['category' => 'Identität & Sicherheit', 'provider' => 'Dropbox Advanced', 'annual_price' => '18.00', 'monthly_price' => '24.00', 'is_active' => 1],
            ['category' => 'Identität & Sicherheit', 'provider' => 'Google Workspace Business Plus', 'annual_price' => '21.10', 'monthly_price' => '25.30', 'is_active' => 1],
            ['category' => 'Identität & Sicherheit', 'provider' => 'Proton Business Suite', 'annual_price' => '12.99', 'monthly_price' => '14.99', 'is_active' => 1],
            ['category' => 'Identität & Sicherheit', 'provider' => 'Slack Business+', 'annual_price' => '15.00', 'monthly_price' => '18.00', 'is_active' => 1],
            ['category' => 'Identität & Sicherheit', 'provider' => 'Zoho Workplace Professional', 'annual_price' => '5.40', 'monthly_price' => '6.30', 'is_active' => 1],

            ['category' => 'Mail', 'provider' => 'Google Workspace Business Starter', 'annual_price' => '6.80', 'monthly_price' => '8.10', 'is_active' => 1],
            ['category' => 'Mail', 'provider' => 'Proton Mail Essentials', 'annual_price' => '6.99', 'monthly_price' => '7.99', 'is_active' => 1],
            ['category' => 'Mail', 'provider' => 'Proton Mail Professional', 'annual_price' => '9.99', 'monthly_price' => '10.99', 'is_active' => 1],
            ['category' => 'Mail', 'provider' => 'Zoho Mail Lite', 'annual_price' => '0.90', 'monthly_price' => '1.20', 'is_active' => 1],
            ['category' => 'Mail', 'provider' => 'Zoho Mail Premium', 'annual_price' => '3.60', 'monthly_price' => '4.80', 'is_active' => 1],

            ['category' => 'Office & Produktivität', 'provider' => 'Google Workspace Business Plus', 'annual_price' => '21.10', 'monthly_price' => '25.30', 'is_active' => 1],
            ['category' => 'Office & Produktivität', 'provider' => 'Google Workspace Business Standard', 'annual_price' => '13.60', 'monthly_price' => '16.20', 'is_active' => 1],
            ['category' => 'Office & Produktivität', 'provider' => 'Proton Business Suite', 'annual_price' => '12.99', 'monthly_price' => '14.99', 'is_active' => 1],
            ['category' => 'Office & Produktivität', 'provider' => 'Zoho Workplace Professional', 'annual_price' => '5.40', 'monthly_price' => '6.30', 'is_active' => 1],
            ['category' => 'Office & Produktivität', 'provider' => 'Zoho Workplace Standard', 'annual_price' => '2.70', 'monthly_price' => '3.60', 'is_active' => 1],

            ['category' => 'Projektmanagement', 'provider' => 'Asana Advanced', 'annual_price' => '24.99', 'monthly_price' => '30.49', 'is_active' => 1],
            ['category' => 'Projektmanagement', 'provider' => 'Asana Starter', 'annual_price' => '10.99', 'monthly_price' => '13.49', 'is_active' => 1],
            ['category' => 'Projektmanagement', 'provider' => 'MeisterTask Business', 'annual_price' => '24.00', 'monthly_price' => '31.00', 'is_active' => 1],
            ['category' => 'Projektmanagement', 'provider' => 'MeisterTask Pro', 'annual_price' => '13.50', 'monthly_price' => '17.50', 'is_active' => 1],
            ['category' => 'Projektmanagement', 'provider' => 'Zoho Projects Enterprise', 'annual_price' => '9.00', 'monthly_price' => '10.00', 'is_active' => 1],
            ['category' => 'Projektmanagement', 'provider' => 'Zoho Projects Premium', 'annual_price' => '4.00', 'monthly_price' => '5.00', 'is_active' => 1],

            ['category' => 'Storage & Dateien', 'provider' => 'Dropbox Advanced', 'annual_price' => '18.00', 'monthly_price' => '24.00', 'is_active' => 1],
            ['category' => 'Storage & Dateien', 'provider' => 'Dropbox Standard', 'annual_price' => '12.00', 'monthly_price' => '15.00', 'is_active' => 1],
            ['category' => 'Storage & Dateien', 'provider' => 'Google Workspace Business Standard', 'annual_price' => '13.60', 'monthly_price' => '16.20', 'is_active' => 1],
            ['category' => 'Storage & Dateien', 'provider' => 'Hetzner Storage Share NX11 (1 TB / 3 Nutzer)', 'annual_price' => '4.29', 'monthly_price' => '4.29', 'is_active' => 1],
            ['category' => 'Storage & Dateien', 'provider' => 'IONOS Managed Nextcloud 3 TB+ (25 Nutzer)', 'annual_price' => '30.00', 'monthly_price' => '30.00', 'is_active' => 1],
            ['category' => 'Storage & Dateien', 'provider' => 'Proton Business Suite', 'annual_price' => '12.99', 'monthly_price' => '14.99', 'is_active' => 1],
            ['category' => 'Storage & Dateien', 'provider' => 'Zoho Workplace Professional', 'annual_price' => '5.40', 'monthly_price' => '6.30', 'is_active' => 1],

            ['category' => 'Zusammenarbeit & Meetings', 'provider' => 'Google Workspace Business Standard', 'annual_price' => '13.60', 'monthly_price' => '16.20', 'is_active' => 1],
            ['category' => 'Zusammenarbeit & Meetings', 'provider' => 'Slack Business+', 'annual_price' => '15.00', 'monthly_price' => '18.00', 'is_active' => 1],
            ['category' => 'Zusammenarbeit & Meetings', 'provider' => 'Slack Pro', 'annual_price' => '6.75', 'monthly_price' => '8.25', 'is_active' => 1],
            ['category' => 'Zusammenarbeit & Meetings', 'provider' => 'Zoho Workplace Professional', 'annual_price' => '5.40', 'monthly_price' => '6.30', 'is_active' => 1],
            ['category' => 'Zusammenarbeit & Meetings', 'provider' => 'Zoho Workplace Standard', 'annual_price' => '2.70', 'monthly_price' => '3.60', 'is_active' => 1],
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function default_eu_alternative_offers(): array
    {
        return [
            ['category_key' => 'core_workspace', 'provider' => 'IceWarp Business (CZ)', 'focus' => 'Stärkster direkter M365-Klon, inkl. Desktop-Apps', 'annual_price' => '6.00', 'monthly_price' => '7.50', 'is_active' => 1],
            ['category_key' => 'core_workspace', 'provider' => 'Infomaniak kSuite Enterprise (CH)', 'focus' => 'Mail, Drive, Meet und Office mit starkem Datenschutz-Fokus', 'annual_price' => '7.90', 'monthly_price' => '7.90', 'is_active' => 1],
            ['category_key' => 'core_workspace', 'provider' => 'Nextcloud Hub Enterprise (DE)*', 'focus' => 'Maximale Datensouveränität über europäische Managed-Partner', 'annual_price' => '5.00', 'monthly_price' => '6.00', 'is_active' => 1],
            ['category_key' => 'core_workspace', 'provider' => 'Open-Xchange OX Cloud (DE)', 'focus' => 'Sehr stark bei Mail, Groupware und Collaboration', 'annual_price' => '3.50', 'monthly_price' => '4.50', 'is_active' => 1],
            ['category_key' => 'office_productivity', 'provider' => 'SoftMaker NX Universal (DE)', 'focus' => 'Nativ installierbare Desktop-Apps für Windows, macOS und Linux', 'annual_price' => '5.90', 'monthly_price' => '6.90', 'is_active' => 1],
            ['category_key' => 'office_productivity', 'provider' => 'Collabora Online Enterprise (UK)', 'focus' => 'Browserbasierte Office-Suite, häufig direkt mit Nextcloud kombiniert', 'annual_price' => '1.50', 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'office_productivity', 'provider' => 'OnlyOffice Workspace Enterprise (LV)', 'focus' => 'Web- und Desktop-Editoren mit hoher MS-Format-Kompatibilität', 'annual_price' => '6.00', 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'collaboration_intranet', 'provider' => 'Stackfield Enterprise (DE)', 'focus' => 'Ende-zu-Ende verschlüsselt, Chat, Aufgaben und Dateien in einer Oberfläche', 'annual_price' => '24.00', 'monthly_price' => '29.00', 'is_active' => 1],
            ['category_key' => 'collaboration_intranet', 'provider' => 'Element Enterprise (UK/FR)', 'focus' => 'Matrix-basiert, dezentral und von Behörden genutzt', 'annual_price' => '5.00', 'monthly_price' => '6.00', 'is_active' => 1],
            ['category_key' => 'collaboration_intranet', 'provider' => 'Alfaview Professional (DE)', 'focus' => 'DSGVO-konforme, hochskalierbare Videokonferenzen', 'annual_price' => '9.99', 'monthly_price' => '12.99', 'is_active' => 1],
            ['category_key' => 'security_device_management', 'provider' => 'Cortado MDM Pro (DE)', 'focus' => 'Mobile Device Management und Geräteverwaltung', 'annual_price' => '5.50', 'monthly_price' => '6.50', 'is_active' => 1],
            ['category_key' => 'security_device_management', 'provider' => 'Relution Enterprise (DE)', 'focus' => 'MDM mit starkem Fokus auf Bildung und Behörden', 'annual_price' => '4.50', 'monthly_price' => '5.50', 'is_active' => 1],
            ['category_key' => 'security_device_management', 'provider' => 'ESET Protect Advanced (SK)', 'focus' => 'Endpoint Security mit Cloud-Sandboxing', 'annual_price' => '4.20', 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'security_device_management', 'provider' => 'WithSecure Elements (FI)', 'focus' => 'Endpoint Protection und Cloud-Sicherheit', 'annual_price' => '5.00', 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'security_device_management', 'provider' => 'G DATA Endpoint Protection (DE)', 'focus' => 'Deutscher Endpoint- und Antivirus-Pionier', 'annual_price' => '3.40', 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'project_management', 'provider' => 'awork Enterprise (DE)', 'focus' => 'Modernes, visuelles Projektmanagement', 'annual_price' => '15.99', 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'project_management', 'provider' => 'MeisterTask Business (AT)', 'focus' => 'Kanban-Fokus und sehr intuitive Bedienung', 'annual_price' => '24.00', 'monthly_price' => '31.00', 'is_active' => 1],
            ['category_key' => 'project_management', 'provider' => 'OpenProject Enterprise (DE)', 'focus' => 'Klassisches und agiles Projektmanagement auf Open-Source-Basis', 'annual_price' => '5.95', 'monthly_price' => null, 'is_active' => 1],

            ['category_key' => 'endpoint_security_xdr', 'provider' => 'Bitdefender GravityZone Ultra (RO)', 'focus' => 'Tiefe XDR-Integration, starkes Machine Learning, eigene On-Prem-Management-Server möglich.', 'annual_price' => '6.50', 'monthly_price' => '6.50', 'is_active' => 1],
            ['category_key' => 'endpoint_security_xdr', 'provider' => 'ESET Protect Enterprise (SK)', 'focus' => 'Voller XDR-Umfang, MDR-Services für Konzerne, exzellente Heuristik.', 'annual_price' => '7.50', 'monthly_price' => '7.50', 'is_active' => 1],
            ['category_key' => 'endpoint_security_xdr', 'provider' => 'WithSecure Elements (FI)', 'focus' => 'Ehemals F-Secure Business. Sehr starkes Vulnerability Management und EDR für europäische Konzerne.', 'annual_price' => '5.50', 'monthly_price' => '5.50', 'is_active' => 1],
            ['category_key' => 'endpoint_security_xdr', 'provider' => 'G DATA Endpoint Protection Business (DE)', 'focus' => '„No Backdoor“-Garantie, starker Fokus auf deutsche Datenschutzstandards und KRITIS.', 'annual_price' => '4.00', 'monthly_price' => '4.00', 'is_active' => 1],
            ['category_key' => 'endpoint_security_xdr', 'provider' => 'Stormshield Endpoint Security (FR)', 'focus' => 'Airbus-Tochtergesellschaft; hochsichere Umgebungen (Militär/Behörden/KRITIS), extrem tiefe OS-Kontrolle.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],

            ['category_key' => 'mdm_uem', 'provider' => 'Matrix42 UEM (DE)', 'focus' => 'Verbindet Endpoint Management nahtlos mit ITSM und Asset-Management.', 'annual_price' => '8.00', 'monthly_price' => '8.00', 'is_active' => 1],
            ['category_key' => 'mdm_uem', 'provider' => 'Baramundi Management Suite (DE)', 'focus' => 'Sehr verbreitet im DACH-Mittelstand, verwaltet PCs, Server, Mobilgeräte und Industrie-PCs.', 'annual_price' => '5.50', 'monthly_price' => '5.50', 'is_active' => 1],
            ['category_key' => 'mdm_uem', 'provider' => 'AppTec360 Enterprise (CH/DE)', 'focus' => 'Sehr aggressives Pricing, Schweizer Server, volle Containerisierung (BYOD) für Enterprise-Kunden.', 'annual_price' => '1.45', 'monthly_price' => '1.45', 'is_active' => 1],
            ['category_key' => 'mdm_uem', 'provider' => 'Relution Enterprise (DE)', 'focus' => 'Multi-Mandantenfähigkeit, vollständige DSGVO-Konformität, Private-Cloud-Fokus.', 'annual_price' => '4.25', 'monthly_price' => '4.25', 'is_active' => 1],

            ['category_key' => 'identity_access_iam', 'provider' => 'cidaas (DE)', 'focus' => 'Führendes europäisches Cloud-IAM mit Biometrie, MFA und starkem API-Fokus.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'identity_access_iam', 'provider' => 'Pointsharp (SE)', 'focus' => 'Spezialist für hochsichere Authentifizierung, oft als Entra-ID-Ersatz in der europäischen Industrie genutzt.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'identity_access_iam', 'provider' => 'Nexus Smart ID (SE)', 'focus' => 'Fokus auf PKI, Smartcards und physische/logische Zutrittskontrolle.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'identity_access_iam', 'provider' => 'Univention (UCS) (DE)', 'focus' => 'Die Open-Source-Alternative zu Active Directory / Entra ID. Standard im Bildungs- und Behördensektor.', 'annual_price' => '2.50', 'monthly_price' => '2.50', 'is_active' => 1],

            ['category_key' => 'enterprise_project_management', 'provider' => 'Planisware (FR)', 'focus' => 'Weltweiter Top-Player für Projektportfoliomanagement, Ressourcenplanung und R&D-Steuerung.', 'annual_price' => '40.00', 'monthly_price' => '40.00', 'is_active' => 1],
            ['category_key' => 'enterprise_project_management', 'provider' => 'Projektron BCS (DE)', 'focus' => 'Komplexe Multiprojektplanung, Zeiterfassung, Ressourcenmanagement und integriertes CRM.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'enterprise_project_management', 'provider' => 'Sciforma (FR)', 'focus' => 'Stark in agiler und klassischer Projektsteuerung, fokussiert auf Strategie-Execution in Konzernen.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'enterprise_project_management', 'provider' => 'InLoox Enterprise (DE)', 'focus' => 'Integriert sich extrem tief in Outlook oder läuft als reine Web-Plattform.', 'annual_price' => '24.95', 'monthly_price' => '24.95', 'is_active' => 1],
            ['category_key' => 'enterprise_project_management', 'provider' => 'OpenProject Enterprise (DE)', 'focus' => 'Open-Source-Kern, hybrides Projektmanagement, volle Datenkontrolle auf eigenen Servern.', 'annual_price' => '5.95', 'monthly_price' => '5.95', 'is_active' => 1],

            ['category_key' => 'low_code_automation', 'provider' => 'Camunda 8 Enterprise (DE)', 'focus' => 'Weltweiter Standard für BPMN-Prozess-Orchestrierung. Skaliert bei Millionen Transaktionen.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'low_code_automation', 'provider' => 'WEBCON BPS (PL)', 'focus' => 'Enterprise-Plattform für App-Bau und Workflows, extrem flexibel bei Änderungen.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'low_code_automation', 'provider' => 'Axon Ivy (CH/AT)', 'focus' => 'Extrem starke Plattform für digitale Prozessautomatisierung und komplexe B2B-Workflows.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'low_code_automation', 'provider' => 'Simplifier (DE)', 'focus' => 'Industrial Low-Code mit Fokus auf Werker-Apps, UI-Generierung und tiefe SAP/ERP-Anbindung.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],

            ['category_key' => 'data_analysis_bi', 'provider' => 'Jedox (DE)', 'focus' => 'Fokus auf Enterprise Performance Management, Finanzplanung, Forecasting und tiefe Excel-Anbindung.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'data_analysis_bi', 'provider' => 'Bissantz (DeltaMaster) (DE)', 'focus' => 'Spezialist für hochverdichtete, visuelle Datenanalyse und Management-Dashboards.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'data_analysis_bi', 'provider' => 'Board (CH)', 'focus' => 'Kombiniert BI, Simulation und prädiktive Analysen in einer zentralen Enterprise-Plattform.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'data_analysis_bi', 'provider' => 'TARGIT (DK)', 'focus' => 'Sehr stark im Bereich BI für Handel, Produktion und Maschinenbau.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],

            ['category_key' => 'dms_archiving_compliance', 'provider' => 'ELO Digital Office (DE)', 'focus' => 'Eines der führenden ECM-Systeme Europas; revisionssichere Archivierung, Workflows und Vertragsmanagement.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'dms_archiving_compliance', 'provider' => 'DocuWare (DE)', 'focus' => 'Sehr etabliertes Cloud-DMS mit starken Automatisierungsfunktionen für Rechnungen und Personalakten.', 'annual_price' => '75.00', 'monthly_price' => '75.00', 'is_active' => 1],
            ['category_key' => 'dms_archiving_compliance', 'provider' => 'd.velop documents (DE)', 'focus' => 'Tiefgreifendes Enterprise Content Management, zertifizierte Archivierung (GoBD/Compliance).', 'annual_price' => '20.00', 'monthly_price' => '20.00', 'is_active' => 1],
            ['category_key' => 'dms_archiving_compliance', 'provider' => 'EMA (ARTEC IT) (DE)', 'focus' => 'Hochspezialisiert auf rechtssichere eDiscovery, 10-Jahres-E-Mail-Archivierung und DSGVO-Audits.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],

            ['category_key' => 'enterprise_cloud_telephony', 'provider' => 'NFON Cloudya Premium (DE)', 'focus' => 'Führende europäische Cloud-PBX, hochverfügbar, inkl. CRM-Integrationen und Callcenter-Funktionen.', 'annual_price' => '15.00', 'monthly_price' => '15.00', 'is_active' => 1],
            ['category_key' => 'enterprise_cloud_telephony', 'provider' => 'Enreach Contact / Swyx (NL/DE)', 'focus' => 'Sehr starke UCaaS-Plattform, exzellentes Call-Routing, Presence-Management und Integrationen.', 'annual_price' => '12.50', 'monthly_price' => '12.50', 'is_active' => 1],
            ['category_key' => 'enterprise_cloud_telephony', 'provider' => 'Starface Enterprise (DE)', 'focus' => 'On-Premises, VM oder Cloud. Sehr flexibel bei der SIP-Trunk-Wahl und starker Desktop-Client.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'enterprise_cloud_telephony', 'provider' => 'Dstny (BE)', 'focus' => 'Großer paneuropäischer Anbieter für Business-Kommunikation und Mobile-First-PBX.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function default_eu_ai_alternative_offers(): array
    {
        return [
            ['category_key' => 'ai_assistants', 'provider' => 'Aleph Alpha (Pharia) (DE)', 'focus' => 'B2B-/Regierungs-Fokus, absolute Datensouveränität und Air-Gapped-Betrieb in eigener Cloud möglich.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'ai_assistants', 'provider' => 'Mistral AI (La Plateforme) (FR)', 'focus' => 'Europas stärkstes LLM. Lokal oder in eigener Plattform für KI-Tools, Chatbots und Assistenten nutzbar.', 'annual_price' => null, 'monthly_price' => null, 'is_active' => 1],
            ['category_key' => 'ai_assistants', 'provider' => 'DeepL Enterprise / Pro (DE)', 'focus' => 'Goldstandard für Übersetzungen und Textoptimierung (DeepL Write), strikter Datenschutz ohne Modell-Training durch Kundendaten.', 'annual_price' => '8.99', 'monthly_price' => '8.99', 'is_active' => 1],
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function eu_comparison_plan_profiles(): array
    {
        return [
            'm365-business-basic' => [
                'label' => 'Microsoft 365 Business Basic',
                'included_categories' => ['core_workspace', 'collaboration_intranet'],
                'description' => 'Mail, Teams, Web-Apps, OneDrive und SharePoint für den Einstieg.',
            ],
            'm365-business-standard' => [
                'label' => 'Microsoft 365 Business Standard',
                'included_categories' => ['core_workspace', 'office_productivity', 'collaboration_intranet'],
                'description' => 'Business Basic plus installierbare Office-Apps.',
            ],
            'm365-business-premium' => [
                'label' => 'Microsoft 365 Business Premium',
                'included_categories' => ['core_workspace', 'office_productivity', 'collaboration_intranet', 'security_device_management'],
                'description' => 'Business Standard plus Security- und Geräteverwaltungsfunktionen.',
            ],
            'office-365-e3' => [
                'label' => 'Office 365 E3',
                'included_categories' => ['core_workspace', 'office_productivity', 'collaboration_intranet'],
                'description' => 'Enterprise-Suite mit Mail, Teams, Office und Collaboration.',
            ],
            'm365-e3' => [
                'label' => 'Microsoft 365 E3',
                'included_categories' => ['core_workspace', 'office_productivity', 'collaboration_intranet', 'security_device_management'],
                'description' => 'Office 365 E3 plus Windows-, Intune- und Security-Rechte.',
            ],
        ];
    }

    /**
     * @return array<string,array<string,string>>
     */
    public static function eu_comparison_categories(): array
    {
        return [
            'core_workspace' => [
                'label' => 'All-in-One Workspaces',
                'description' => 'Kernersatz für Mail, Kalender, Dateien, Office und Collaboration.',
            ],
            'office_productivity' => [
                'label' => 'Office & Produktivität',
                'description' => 'Alternativen für Word, Excel, PowerPoint und Desktop-/Web-Editoren.',
            ],
            'collaboration_intranet' => [
                'label' => 'Zusammenarbeit & Intranet',
                'description' => 'Chat, Videokonferenzen, Intranet und teamübergreifende Zusammenarbeit.',
            ],
            'security_device_management' => [
                'label' => 'IT-Sicherheit & Endgeräteverwaltung',
                'description' => 'Defender-/Intune-Ersatz für Endpoint Security und Geräteverwaltung.',
            ],
            'project_management' => [
                'label' => 'Projektmanagement',
                'description' => 'Planner-/Project-Ersatz für Projekte, Aufgaben und Roadmaps.',
            ],
            'endpoint_security_xdr' => [
                'label' => 'Endpoint Security & XDR',
                'description' => 'Europäische Alternativen zu Defender for Endpoint/Business und XDR-nahen Schutzstacks.',
            ],
            'mdm_uem' => [
                'label' => 'MDM & UEM',
                'description' => 'Alternativen zu Intune für Unified Endpoint Management und Geräteverwaltung.',
            ],
            'identity_access_iam' => [
                'label' => 'Identity & Access (IAM)',
                'description' => 'Alternativen zu Entra ID P1/P2/Governance für IAM, PKI und starke Authentifizierung.',
            ],
            'enterprise_project_management' => [
                'label' => 'Enterprise Projektmanagement',
                'description' => 'Europäische PPM- und Enterprise-Projektmanagement-Alternativen zu Project und Planner.',
            ],
            'low_code_automation' => [
                'label' => 'Low-Code & Automatisierung',
                'description' => 'Alternativen zu Power Apps, Power Automate und Workflow-/Prozessplattformen.',
            ],
            'data_analysis_bi' => [
                'label' => 'Datenanalyse & BI',
                'description' => 'Europäische BI- und Analyseplattformen als Alternativen zu Power BI.',
            ],
            'dms_archiving_compliance' => [
                'label' => 'DMS, Archivierung & Compliance',
                'description' => 'Alternativen zu SharePoint/Purview/Archiving für DMS, Archivierung und eDiscovery.',
            ],
            'enterprise_cloud_telephony' => [
                'label' => 'Enterprise Cloud-Telefonie',
                'description' => 'Europäische Cloud-PBX- und UCaaS-Alternativen zu Teams Phone und Calling Plans.',
            ],
            'ai_assistants' => [
                'label' => 'KI & Copilot',
                'description' => 'Europäische Alternativen für Copilot-, Chat-, Assistenz- und Agenten-Szenarien.',
            ],
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function eu_service_profiles(): array
    {
        return [
            'workplace_core' => [
                'label' => 'Workplace, Mail & Dateien',
                'description' => 'Mail, Kalender, Dateifreigaben, Teamräume und allgemeine Zusammenarbeit.',
                'categories' => ['core_workspace', 'collaboration_intranet'],
            ],
            'office_apps' => [
                'label' => 'Desktop- & Web-Apps',
                'description' => 'Office-Editoren, Browser-Apps und lokale Desktop-Anwendungen.',
                'categories' => ['office_productivity'],
            ],
            'endpoint_management' => [
                'label' => 'Intune / Geräteverwaltung',
                'description' => 'MDM, UEM, Richtlinien, BYOD, Shared Devices und Gerätekonfiguration.',
                'categories' => ['mdm_uem', 'security_device_management'],
            ],
            'identity_access' => [
                'label' => 'Identity / MFA / Zugriff',
                'description' => 'IAM, SSO, MFA, Conditional Access, PKI und Identity Governance.',
                'categories' => ['identity_access_iam'],
            ],
            'endpoint_security' => [
                'label' => 'Endpoint Security / XDR',
                'description' => 'Endpoint Protection, EDR, XDR, CASB und Security Operations.',
                'categories' => ['endpoint_security_xdr', 'security_device_management'],
            ],
            'compliance_governance' => [
                'label' => 'Compliance / Archivierung',
                'description' => 'Aufbewahrung, DMS, Archiv, eDiscovery und rechtssichere Dokumentation.',
                'categories' => ['dms_archiving_compliance'],
            ],
            'ai_assistants' => [
                'label' => 'KI / Copilot / Assistants',
                'description' => 'Assistenzsysteme, Chat-KI, Agenten und souveräne KI-Plattformen.',
                'categories' => ['ai_assistants'],
            ],
            'project_portfolio' => [
                'label' => 'Projekt- & Portfoliomanagement',
                'description' => 'Planner-, Project-, PMO- und Multiprojekt-Szenarien.',
                'categories' => ['project_management', 'enterprise_project_management'],
            ],
            'automation_low_code' => [
                'label' => 'Low-Code & Automatisierung',
                'description' => 'Workflow-Engines, App-Baukästen, BPMN und Prozessautomatisierung.',
                'categories' => ['low_code_automation'],
            ],
            'bi_analytics' => [
                'label' => 'BI / Reporting / Analyse',
                'description' => 'Dashboards, Reports, Planung und Management-Analyse.',
                'categories' => ['data_analysis_bi'],
            ],
            'telephony_voice' => [
                'label' => 'Telefonie / PBX / Voice',
                'description' => 'Cloud-Telefonie, UCaaS, Rufsteuerung und Contact-Center-nahe Szenarien.',
                'categories' => ['enterprise_cloud_telephony'],
            ],
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function eu_offer_capability_profiles(): array
    {
        return [
            'icewarp business (cz)' => [
                'service_keys' => ['workplace_core', 'office_apps'],
                'strength_note' => 'Sehr nah an einem klassischen M365-Workspace mit Mail, Groupware und Office-Fokus.',
                'limitation_note' => 'Kein echter Ersatz für Entra, Intune, XDR oder tiefe Compliance-Suiten.',
            ],
            'infomaniak ksuite enterprise (ch)' => [
                'service_keys' => ['workplace_core'],
                'strength_note' => 'Souveräner Komplett-Workspace mit starkem Datenschutz-Fokus.',
                'limitation_note' => 'Identity-, Endpoint- und Enterprise-Compliance-Funktionen brauchen meist Zusatzanbieter.',
            ],
            'nextcloud hub enterprise (de)*' => [
                'service_keys' => ['workplace_core', 'compliance_governance'],
                'strength_note' => 'Stark für Dateihoheit, Kollaboration und europäische Datenkontrolle.',
                'limitation_note' => 'Mail, Voice, IAM und MDM werden in der Regel über ergänzende Lösungen gelöst.',
            ],
            'open-xchange ox cloud (de)' => [
                'service_keys' => ['workplace_core', 'compliance_governance'],
                'strength_note' => 'Solide europäische Groupware-Basis mit starkem Mail- und Collaboration-Fokus.',
                'limitation_note' => 'Office-Apps, IAM, MDM und Security-XDR müssen meist separat ergänzt werden.',
            ],
            'softmaker nx universal (de)' => [
                'service_keys' => ['office_apps'],
                'strength_note' => 'Starke Alternative für installierbare Desktop-Office-Apps.',
            ],
            'collabora online enterprise (uk)' => [
                'service_keys' => ['office_apps'],
                'strength_note' => 'Gut für browserbasierte Office-Bearbeitung im souveränen Stack.',
            ],
            'onlyoffice workspace enterprise (lv)' => [
                'service_keys' => ['office_apps', 'workplace_core'],
                'strength_note' => 'Hohe MS-Format-Kompatibilität für Web- und Desktop-Editing.',
            ],
            'stackfield enterprise (de)' => [
                'service_keys' => ['workplace_core', 'project_portfolio', 'compliance_governance'],
                'strength_note' => 'Kombiniert Collaboration, Aufgaben und Dateien in einer europäischen Oberfläche.',
            ],
            'element enterprise (uk/fr)' => [
                'service_keys' => ['workplace_core'],
                'strength_note' => 'Sehr gut für sichere Chat- und Raum-Kommunikation.',
                'limitation_note' => 'Deckt Office, IAM, DMS und Geräteverwaltung nicht eigenständig ab.',
            ],
            'alfaview professional (de)' => [
                'service_keys' => ['workplace_core', 'telephony_voice'],
                'strength_note' => 'Starke Ergänzung für Videokonferenzen und Kommunikationsräume.',
            ],
            'cortado mdm pro (de)' => [
                'service_keys' => ['endpoint_management'],
                'strength_note' => 'Fokus auf Mobile Device Management und Richtlinien.',
            ],
            'relution enterprise (de)' => [
                'service_keys' => ['endpoint_management'],
                'strength_note' => 'Starker europäischer Intune-Ersatz für Bildung, Behörden und Private Cloud.',
            ],
            'eset protect advanced (sk)' => [
                'service_keys' => ['endpoint_security', 'endpoint_management'],
                'strength_note' => 'Kombiniert Endpoint-Schutz mit Verwaltungsnähe.',
            ],
            'withsecure elements (fi)' => [
                'service_keys' => ['endpoint_security'],
                'strength_note' => 'Starkes europäisches Security-Portfolio für Endpunkte und Cloud.',
            ],
            'g data endpoint protection (de)' => [
                'service_keys' => ['endpoint_security'],
                'strength_note' => 'Deutscher Endpoint-Security-Spezialist mit Datenschutz-Fokus.',
            ],
            'bitdefender gravityzone ultra (ro)' => [
                'service_keys' => ['endpoint_security'],
                'strength_note' => 'Starke XDR-/EDR-Abdeckung für Security-lastige Anforderungen.',
            ],
            'eset protect enterprise (sk)' => [
                'service_keys' => ['endpoint_security'],
                'strength_note' => 'Sehr gutes Fit für XDR, Hunting und Security Operations.',
            ],
            'g data endpoint protection business (de)' => [
                'service_keys' => ['endpoint_security'],
                'strength_note' => 'Gut für datenschutzsensible und deutsche Security-Szenarien.',
            ],
            'stormshield endpoint security (fr)' => [
                'service_keys' => ['endpoint_security'],
                'strength_note' => 'Geeignet für hochsichere Umgebungen und KRITIS-nahe Deployments.',
            ],
            'matrix42 uem (de)' => [
                'service_keys' => ['endpoint_management'],
                'strength_note' => 'Sehr stark für Enterprise-UEM plus ITSM-Kontext.',
            ],
            'baramundi management suite (de)' => [
                'service_keys' => ['endpoint_management'],
                'strength_note' => 'Breite Endgeräteverwaltung im DACH-Mittelstand.',
            ],
            'apptec360 enterprise (ch/de)' => [
                'service_keys' => ['endpoint_management'],
                'strength_note' => 'Preislich starke UEM-/MDM-Lösung mit europäischer Hosting-Ausrichtung.',
            ],
            'cidaas (de)' => [
                'service_keys' => ['identity_access'],
                'strength_note' => 'Cloud-IAM mit MFA, Biometrie und API-Fokus.',
            ],
            'pointsharp (se)' => [
                'service_keys' => ['identity_access'],
                'strength_note' => 'Starker Fit für MFA und hochsichere Authentifizierung.',
            ],
            'nexus smart id (se)' => [
                'service_keys' => ['identity_access'],
                'strength_note' => 'Stark bei PKI, Smartcards und komplexen Identitätsmodellen.',
            ],
            'univention (ucs) (de)' => [
                'service_keys' => ['identity_access'],
                'strength_note' => 'Europäischer AD-/Entra-Ersatz mit Open-Source-Kern.',
            ],
            'planisware (fr)' => [
                'service_keys' => ['project_portfolio'],
                'strength_note' => 'Top-Fit für PMO- und Portfolio-Management im Enterprise-Kontext.',
            ],
            'projektron bcs (de)' => [
                'service_keys' => ['project_portfolio'],
                'strength_note' => 'Sehr passend für Multiprojekt-, Ressourcen- und Professional-Services-Szenarien.',
            ],
            'sciforma (fr)' => [
                'service_keys' => ['project_portfolio'],
                'strength_note' => 'Gut für Strategie-Execution und klassisches Enterprise-PMO.',
            ],
            'inloox enterprise (de)' => [
                'service_keys' => ['project_portfolio'],
                'strength_note' => 'Hilfreich für Outlook-nahe Projektorganisation.',
            ],
            'openproject enterprise (de)' => [
                'service_keys' => ['project_portfolio'],
                'strength_note' => 'Souveräner Projektmanagement-Stack mit Open-Source-Kern.',
            ],
            'awork enterprise (de)' => [
                'service_keys' => ['project_portfolio'],
                'strength_note' => 'Visuell moderne PM-Alternative für Teams und Fachbereiche.',
            ],
            'meistertask business (at)' => [
                'service_keys' => ['project_portfolio'],
                'strength_note' => 'Sehr einfach zugängliches Work-Management und Kanban.',
            ],
            'camunda 8 enterprise (de)' => [
                'service_keys' => ['automation_low_code'],
                'strength_note' => 'Starker Fit für Prozessautomation und orchestrierte Enterprise-Workflows.',
            ],
            'webcon bps (pl)' => [
                'service_keys' => ['automation_low_code'],
                'strength_note' => 'Geeignet für flexible Formular- und Prozessplattformen.',
            ],
            'axon ivy (ch/at)' => [
                'service_keys' => ['automation_low_code'],
                'strength_note' => 'Sehr gut für komplexe B2B- und BPM-Szenarien.',
            ],
            'simplifier (de)' => [
                'service_keys' => ['automation_low_code'],
                'strength_note' => 'Starker Low-Code-Fit für industrielle Apps und ERP-nahe Workflows.',
            ],
            'jedox (de)' => [
                'service_keys' => ['bi_analytics'],
                'strength_note' => 'Starker Fit für Finanzplanung, BI und Excel-nahe Analyse.',
            ],
            'bissantz (deltamaster) (de)' => [
                'service_keys' => ['bi_analytics'],
                'strength_note' => 'Gut für Management-Dashboards und verdichtete Analysen.',
            ],
            'board (ch)' => [
                'service_keys' => ['bi_analytics'],
                'strength_note' => 'Geeignet für Planung, Simulation und Enterprise-Analyse.',
            ],
            'targit (dk)' => [
                'service_keys' => ['bi_analytics'],
                'strength_note' => 'Stark für BI in Handel, Produktion und Mittelstand.',
            ],
            'elo digital office (de)' => [
                'service_keys' => ['compliance_governance'],
                'strength_note' => 'Sehr gutes Fit für ECM, Archivierung und revisionssichere Ablage.',
            ],
            'docuware (de)' => [
                'service_keys' => ['compliance_governance'],
                'strength_note' => 'Stark für Cloud-DMS, Rechnungen, HR und strukturierte Compliance-Prozesse.',
            ],
            'd.velop documents (de)' => [
                'service_keys' => ['compliance_governance'],
                'strength_note' => 'Passend für zertifizierte Archivierung und Content Management.',
            ],
            'ema (artec it) (de)' => [
                'service_keys' => ['compliance_governance'],
                'strength_note' => 'Spezialisiert auf rechtssichere Mail-Archivierung und eDiscovery.',
            ],
            'nfon cloudya premium (de)' => [
                'service_keys' => ['telephony_voice'],
                'strength_note' => 'Starker europäischer Ersatz für Teams Phone und UCaaS.',
            ],
            'enreach contact / swyx (nl/de)' => [
                'service_keys' => ['telephony_voice'],
                'strength_note' => 'Guter Fit für Business-Kommunikation, Call-Routing und Presence.',
            ],
            'starface enterprise (de)' => [
                'service_keys' => ['telephony_voice'],
                'strength_note' => 'Flexibler PBX-Stack für Cloud, VM und On-Prem.',
            ],
            'dstny (be)' => [
                'service_keys' => ['telephony_voice'],
                'strength_note' => 'Großer paneuropäischer UCaaS-Anbieter für Voice-Workloads.',
            ],
            'aleph alpha (pharia) (de)' => [
                'service_keys' => ['ai_assistants'],
                'strength_note' => 'Souveräne europäische KI für Behörden- und Enterprise-Szenarien.',
            ],
            'mistral ai (la plateforme) (fr)' => [
                'service_keys' => ['ai_assistants'],
                'strength_note' => 'Starker Fit für generative KI, Assistants und API-basierte KI-Produkte.',
            ],
            'deepl enterprise / pro (de)' => [
                'service_keys' => ['ai_assistants'],
                'strength_note' => 'Sehr passend für Textassistenz, Übersetzung und Schreibhilfe.',
            ],
        ];
    }

    /**
     * @return array<string,array<int,array<string,mixed>>>
     */
    public static function eu_comparison_offers(): array
    {
        return [
            'core_workspace' => [
                ['slug' => 'icewarp-business', 'provider' => 'IceWarp Business (CZ)', 'annual_price' => '6.00', 'monthly_price' => '7.50', 'focus' => 'Stärkster direkter M365-Klon, inkl. Desktop-Apps'],
                ['slug' => 'infomaniak-ksuite-enterprise', 'provider' => 'Infomaniak kSuite Enterprise (CH)', 'annual_price' => '7.90', 'monthly_price' => '7.90', 'focus' => 'Mail, Drive, Meet und Office mit starkem Datenschutz-Fokus'],
                ['slug' => 'nextcloud-hub-enterprise', 'provider' => 'Nextcloud Hub Enterprise (DE)*', 'annual_price' => '5.00', 'monthly_price' => '6.00', 'focus' => 'Maximale Datensouveränität über europäische Managed-Partner'],
                ['slug' => 'open-xchange-ox-cloud', 'provider' => 'Open-Xchange OX Cloud (DE)', 'annual_price' => '3.50', 'monthly_price' => '4.50', 'focus' => 'Sehr stark bei Mail, Groupware und Collaboration'],
            ],
            'office_productivity' => [
                ['slug' => 'softmaker-nx-universal', 'provider' => 'SoftMaker NX Universal (DE)', 'annual_price' => '5.90', 'monthly_price' => '6.90', 'focus' => 'Nativ installierbare Desktop-Apps für Windows, macOS und Linux'],
                ['slug' => 'collabora-online-enterprise', 'provider' => 'Collabora Online Enterprise (UK)', 'annual_price' => '1.50', 'monthly_price' => null, 'focus' => 'Browserbasierte Office-Suite, häufig direkt mit Nextcloud kombiniert'],
                ['slug' => 'onlyoffice-workspace-enterprise', 'provider' => 'OnlyOffice Workspace Enterprise (LV)', 'annual_price' => '6.00', 'monthly_price' => null, 'focus' => 'Web- und Desktop-Editoren mit sehr hoher MS-Format-Kompatibilität'],
            ],
            'collaboration_intranet' => [
                ['slug' => 'stackfield-enterprise', 'provider' => 'Stackfield Enterprise (DE)', 'annual_price' => '24.00', 'monthly_price' => '29.00', 'focus' => 'Ende-zu-Ende verschlüsselt, Chat, Aufgaben und Dateien in einer Oberfläche'],
                ['slug' => 'element-enterprise', 'provider' => 'Element Enterprise (UK/FR)', 'annual_price' => '5.00', 'monthly_price' => '6.00', 'focus' => 'Matrix-basiert, dezentral und von Behörden genutzt'],
                ['slug' => 'alfaview-professional', 'provider' => 'Alfaview Professional (DE)', 'annual_price' => '9.99', 'monthly_price' => '12.99', 'focus' => 'DSGVO-konforme, hochskalierbare Videokonferenzen'],
            ],
            'security_device_management' => [
                ['slug' => 'cortado-mdm-pro', 'provider' => 'Cortado MDM Pro (DE)', 'annual_price' => '5.50', 'monthly_price' => '6.50', 'focus' => 'Mobile Device Management und Geräteverwaltung'],
                ['slug' => 'relution-enterprise', 'provider' => 'Relution Enterprise (DE)', 'annual_price' => '4.50', 'monthly_price' => '5.50', 'focus' => 'MDM mit starkem Fokus auf Bildung und Behörden'],
                ['slug' => 'eset-protect-advanced', 'provider' => 'ESET Protect Advanced (SK)', 'annual_price' => '4.20', 'monthly_price' => null, 'focus' => 'Endpoint Security mit Cloud-Sandboxing'],
                ['slug' => 'withsecure-elements', 'provider' => 'WithSecure Elements (FI)', 'annual_price' => '5.00', 'monthly_price' => null, 'focus' => 'Endpoint Protection und Cloud-Sicherheit, ehemals F-Secure'],
                ['slug' => 'gdata-endpoint-protection', 'provider' => 'G DATA Endpoint Protection (DE)', 'annual_price' => '3.40', 'monthly_price' => null, 'focus' => 'Deutscher Endpoint- und Antivirus-Pionier'],
            ],
            'project_management' => [
                ['slug' => 'awork-enterprise', 'provider' => 'awork Enterprise (DE)', 'annual_price' => '15.99', 'monthly_price' => null, 'focus' => 'Modernes, visuelles Projektmanagement'],
                ['slug' => 'meistertask-business', 'provider' => 'MeisterTask Business (AT)', 'annual_price' => '24.00', 'monthly_price' => '31.00', 'focus' => 'Kanban-Fokus und sehr intuitive Bedienung'],
                ['slug' => 'openproject-enterprise', 'provider' => 'OpenProject Enterprise (DE)', 'annual_price' => '5.95', 'monthly_price' => null, 'focus' => 'Klassisches und agiles Projektmanagement auf Open-Source-Basis'],
            ],
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function eu_comparison_default_selection(): array
    {
        return [
            'core_workspace' => 'infomaniak-ksuite-enterprise',
            'office_productivity' => 'softmaker-nx-universal',
            'collaboration_intranet' => 'element-enterprise',
            'security_device_management' => 'relution-enterprise',
            'project_management' => 'openproject-enterprise',
            'endpoint_security_xdr' => 'bitdefender-gravityzone-ultra-ro',
            'mdm_uem' => 'apptec360-enterprise-ch-de',
            'identity_access_iam' => 'univention-ucs-de',
            'enterprise_project_management' => 'openproject-enterprise-de',
            'low_code_automation' => 'camunda-8-enterprise-de',
            'data_analysis_bi' => 'jedox-de',
            'dms_archiving_compliance' => 'd-velop-documents-de',
            'enterprise_cloud_telephony' => 'enreach-contact-swyx-nl-de',
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function package_seeds(): array
    {
        $packages = [
            ['slug' => 'exchange-online-kiosk', 'name' => 'Exchange Online Kiosk', 'kind' => 'base', 'category' => 'exchange', 'audience' => 'frontline', 'pricing_basis' => 'per_user', 'description' => 'Kompakte Mail-Lizenz für Kiosk-/Frontline-Szenarien.', 'features' => ['mail', 'frontline'], 'tags' => ['exchange', 'frontline', 'copilot_enterprise_eligible', 'copilot_chat_eligible'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Copilot-Lizenzvoraussetzung laut Microsoft Learn berücksichtigt.', 'sort_order' => 10, 'is_active' => 1],
            ['slug' => 'exchange-online-plan-1', 'name' => 'Exchange Online Plan 1', 'kind' => 'base', 'category' => 'exchange', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Mail-only-SKU für Benutzer mit Fokus auf Exchange.', 'features' => ['mail'], 'tags' => ['exchange', 'copilot_enterprise_eligible', 'copilot_chat_eligible'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Geeignet für Mail-only-Anforderungen.', 'sort_order' => 20, 'is_active' => 1],
            ['slug' => 'exchange-online-plan-2', 'name' => 'Exchange Online Plan 2', 'kind' => 'base', 'category' => 'exchange', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Mail-Lizenz mit erweiterten Archiv- und Compliance-Funktionen.', 'features' => ['mail', 'archive'], 'tags' => ['exchange', 'copilot_enterprise_eligible', 'copilot_chat_eligible'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Für Mail + Archiv/Compliance geeignet.', 'sort_order' => 30, 'is_active' => 1],
            ['slug' => 'teams-essentials', 'name' => 'Microsoft Teams Essentials', 'kind' => 'base', 'category' => 'teams', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Teams-Fokus ohne vollwertige Office-Suite.', 'features' => ['teams'], 'tags' => ['teams', 'copilot_enterprise_eligible', 'copilot_chat_eligible'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Als Copilot-Voraussetzung laut Microsoft Learn aufgeführt.', 'sort_order' => 40, 'is_active' => 1],
            ['slug' => 'sharepoint-plan-1', 'name' => 'SharePoint Plan 1', 'kind' => 'base', 'category' => 'sharepoint', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'SharePoint/Intranet ohne Office-Apps.', 'features' => ['sharepoint', 'forms', 'stream'], 'tags' => ['sharepoint', 'copilot_enterprise_eligible', 'copilot_chat_eligible'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Für Intranet-/Dokumenten-Szenarien ohne Mail.', 'sort_order' => 50, 'is_active' => 1],
            ['slug' => 'sharepoint-kiosk', 'name' => 'SharePoint Kiosk', 'kind' => 'base', 'category' => 'sharepoint', 'audience' => 'frontline', 'pricing_basis' => 'per_user', 'description' => 'Kompakte SharePoint-/Dokumentenlizenz für Frontline- und Kiosk-Szenarien.', 'features' => ['frontline', 'sharepoint', 'stream'], 'tags' => ['sharepoint', 'frontline', 'copilot_enterprise_eligible', 'copilot_chat_eligible'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Eigenständiger SharePoint-Kiosk-Plan als Copilot-Voraussetzung.', 'sort_order' => 55, 'is_active' => 1],
            ['slug' => 'sharepoint-plan-2', 'name' => 'SharePoint Plan 2', 'kind' => 'base', 'category' => 'sharepoint', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'SharePoint mit erweiterten Compliance- und Suchfunktionen.', 'features' => ['sharepoint', 'forms', 'stream', 'archive'], 'tags' => ['sharepoint', 'compliance', 'copilot_enterprise_eligible', 'copilot_chat_eligible'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Plan 2 ergänzt SharePoint-Advanced-Funktionen und Copilot-Berechtigung.', 'sort_order' => 58, 'is_active' => 1],
            ['slug' => 'onedrive-plan-1', 'name' => 'OneDrive for work and school Plan 1', 'kind' => 'base', 'category' => 'onedrive', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'OneDrive-only-SKU für Dateiablage.', 'features' => ['onedrive', 'office_web'], 'tags' => ['onedrive', 'copilot_enterprise_eligible', 'copilot_chat_eligible'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Für File-Only-Szenarien.', 'sort_order' => 60, 'is_active' => 1],
            ['slug' => 'onedrive-plan-2', 'name' => 'OneDrive for work and school Plan 2', 'kind' => 'base', 'category' => 'onedrive', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'OneDrive-Standalone mit erweiterten Schutz- und Compliance-Optionen.', 'features' => ['onedrive', 'office_web', 'archive'], 'tags' => ['onedrive', 'compliance', 'copilot_enterprise_eligible', 'copilot_chat_eligible'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Plan 2 ist als Copilot-Grundlage separat lizenzierbar.', 'sort_order' => 65, 'is_active' => 1],
            ['slug' => 'm365-apps-business', 'name' => 'Microsoft 365 Apps for Business', 'kind' => 'base', 'category' => 'apps', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Desktop- und Web-Apps plus OneDrive für SMB.', 'features' => ['office_desktop', 'office_web', 'onedrive', 'forms', 'stream'], 'tags' => ['apps', 'smb', 'copilot_business_eligible', 'copilot_chat_eligible'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Geeignet für App-only-Szenarien im SMB-Bereich.', 'sort_order' => 70, 'is_active' => 1],
            ['slug' => 'm365-apps-enterprise', 'name' => 'Microsoft 365 Apps for enterprise', 'kind' => 'base', 'category' => 'apps', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Enterprise-Variante der Desktop- und Web-Apps mit OneDrive und Shared Computer Activation für RDS/Terminalserver.', 'features' => ['office_desktop', 'office_web', 'terminalserver', 'onedrive', 'forms', 'stream'], 'tags' => ['apps', 'enterprise', 'copilot_enterprise_eligible', 'copilot_chat_eligible', 'rds', 'shared_computer_activation'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Apps for enterprise ist direkte Copilot-Voraussetzung.', 'sort_order' => 75, 'is_active' => 1],
            ['slug' => 'm365-business-basic', 'name' => 'Microsoft 365 Business Basic', 'kind' => 'base', 'category' => 'm365-business', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Mail, Teams, Web-Apps, OneDrive und SharePoint.', 'features' => ['mail', 'teams', 'office_web', 'onedrive', 'sharepoint', 'forms', 'stream', 'viva_engage'], 'tags' => ['m365', 'business', 'smb', 'copilot_business_eligible', 'copilot_enterprise_eligible', 'copilot_chat_eligible'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Copilot Business- und Copilot-Voraussetzung laut Microsoft Learn.', 'sort_order' => 80, 'is_active' => 1],
            ['slug' => 'm365-business-standard', 'name' => 'Microsoft 365 Business Standard', 'kind' => 'base', 'category' => 'm365-business', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Business Basic plus installierbare Desktop-Apps.', 'features' => ['mail', 'teams', 'office_web', 'office_desktop', 'onedrive', 'sharepoint', 'forms', 'bookings', 'stream', 'viva_engage'], 'tags' => ['m365', 'business', 'smb', 'copilot_business_eligible', 'copilot_enterprise_eligible', 'copilot_chat_eligible'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Beliebte Knowledge-Worker-Basis.', 'sort_order' => 90, 'is_active' => 1],
            ['slug' => 'm365-business-premium', 'name' => 'Microsoft 365 Business Premium', 'kind' => 'base', 'category' => 'm365-business', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Business Standard plus Intune- und Security-Funktionen; Shared Computer Activation ist für kleine RDS-/Terminalserver-Szenarien möglich.', 'features' => ['mail', 'teams', 'office_web', 'office_desktop', 'terminalserver', 'onedrive', 'sharepoint', 'forms', 'bookings', 'stream', 'viva_engage', 'intune', 'defender'], 'tags' => ['m365', 'business', 'smb', 'security', 'copilot_business_eligible', 'copilot_enterprise_eligible', 'copilot_chat_eligible', 'rds', 'shared_computer_activation'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Empfehlung für abgesicherte SMB-Setups.', 'sort_order' => 100, 'is_active' => 1],
            ['slug' => 'office-365-e1', 'name' => 'Office 365 E1', 'kind' => 'base', 'category' => 'office-enterprise', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Enterprise Web-Suite mit Mail, Teams, OneDrive und SharePoint.', 'features' => ['mail', 'teams', 'office_web', 'onedrive', 'sharepoint', 'forms', 'stream', 'viva_engage'], 'tags' => ['office365', 'enterprise', 'copilot_enterprise_eligible', 'copilot_chat_eligible'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Enterprise-Pendant zu Web-Szenarien.', 'sort_order' => 110, 'is_active' => 1],
            ['slug' => 'office-365-e3', 'name' => 'Office 365 E3', 'kind' => 'base', 'category' => 'office-enterprise', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Enterprise-Suite mit Desktop-Apps, Shared Computer Activation für RDS und erweiterten Compliance-Features.', 'features' => ['mail', 'teams', 'office_web', 'office_desktop', 'terminalserver', 'onedrive', 'sharepoint', 'forms', 'bookings', 'stream', 'viva_engage', 'archive'], 'tags' => ['office365', 'enterprise', 'compliance', 'copilot_enterprise_eligible', 'copilot_chat_eligible', 'rds', 'shared_computer_activation'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Enterprise-Basis für Desktop + Compliance.', 'sort_order' => 120, 'is_active' => 1],
            ['slug' => 'office-365-e5', 'name' => 'Office 365 E5', 'kind' => 'base', 'category' => 'office-enterprise', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Office-Suite mit Shared Computer Activation für RDS, erweiterter Security/Compliance und Audiokonferenzen.', 'features' => ['mail', 'teams', 'office_web', 'office_desktop', 'terminalserver', 'onedrive', 'sharepoint', 'forms', 'bookings', 'stream', 'viva_engage', 'archive', 'defender', 'audio_conf', 'phone_system', 'power_bi'], 'tags' => ['office365', 'enterprise', 'security', 'copilot_enterprise_eligible', 'copilot_chat_eligible', 'rds', 'shared_computer_activation'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Für High-End Office-/Compliance-Szenarien.', 'sort_order' => 130, 'is_active' => 1],
            ['slug' => 'm365-e3', 'name' => 'Microsoft 365 E3', 'kind' => 'base', 'category' => 'm365-enterprise', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Office 365 E3 plus Windows-, Geräteverwaltungsrechte und Shared Computer Activation für RDS-/Terminalserver.', 'features' => ['mail', 'teams', 'office_web', 'office_desktop', 'terminalserver', 'onedrive', 'sharepoint', 'forms', 'bookings', 'stream', 'viva_engage', 'archive', 'windows_rights', 'intune'], 'tags' => ['m365', 'enterprise', 'security', 'copilot_enterprise_eligible', 'copilot_chat_eligible', 'rds', 'shared_computer_activation'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Enterprise-Basis für verwaltete Clients.', 'sort_order' => 140, 'is_active' => 1],
            ['slug' => 'm365-e5', 'name' => 'Microsoft 365 E5', 'kind' => 'base', 'category' => 'm365-enterprise', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Microsoft-365-Spitzensuite mit Shared Computer Activation für RDS, Security-, Compliance- und Management-Fokus.', 'features' => ['mail', 'teams', 'office_web', 'office_desktop', 'terminalserver', 'onedrive', 'sharepoint', 'forms', 'bookings', 'stream', 'viva_engage', 'archive', 'windows_rights', 'intune', 'defender', 'audio_conf', 'phone_system', 'power_bi'], 'tags' => ['m365', 'enterprise', 'security', 'premium', 'copilot_enterprise_eligible', 'copilot_chat_eligible', 'rds', 'shared_computer_activation'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Für anspruchsvolle Enterprise-Setups.', 'sort_order' => 150, 'is_active' => 1],
            ['slug' => 'm365-f1', 'name' => 'Microsoft 365 F1', 'kind' => 'base', 'category' => 'frontline', 'audience' => 'frontline', 'pricing_basis' => 'per_user', 'description' => 'Frontline-Suite für einfache Collaboration-Szenarien.', 'features' => ['frontline', 'teams', 'sharepoint', 'onedrive', 'stream', 'viva_engage'], 'tags' => ['m365', 'frontline', 'copilot_enterprise_eligible', 'copilot_chat_eligible'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Frontline-Grundpaket.', 'sort_order' => 160, 'is_active' => 1],
            ['slug' => 'm365-f3', 'name' => 'Microsoft 365 F3', 'kind' => 'base', 'category' => 'frontline', 'audience' => 'frontline', 'pricing_basis' => 'per_user', 'description' => 'Frontline-Suite mit Mail, Teams und Web-Apps.', 'features' => ['frontline', 'mail', 'teams', 'office_web', 'onedrive', 'sharepoint', 'stream', 'viva_engage'], 'tags' => ['m365', 'frontline', 'copilot_enterprise_eligible', 'copilot_chat_eligible'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Frontline mit Mail- und Datei-Fokus.', 'sort_order' => 170, 'is_active' => 1],
            ['slug' => 'office-365-f3', 'name' => 'Office 365 F3', 'kind' => 'base', 'category' => 'frontline', 'audience' => 'frontline', 'pricing_basis' => 'per_user', 'description' => 'Frontline-/Firstline-Lizenz mit Exchange Online Kiosk, Teams und Web-Apps.', 'features' => ['frontline', 'mail', 'teams', 'office_web', 'onedrive', 'sharepoint', 'stream', 'viva_engage'], 'tags' => ['office365', 'frontline', 'copilot_enterprise_eligible', 'copilot_chat_eligible'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Office 365 F3 ist eigenständige Copilot-Voraussetzung.', 'sort_order' => 175, 'is_active' => 1],
            ['slug' => 'teams-enterprise', 'name' => 'Microsoft Teams Enterprise', 'kind' => 'base', 'category' => 'teams', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Eigenständiger Teams-Enterprise-Plan als Copilot-Grundlage.', 'features' => ['teams'], 'tags' => ['teams', 'enterprise', 'copilot_enterprise_eligible', 'copilot_chat_eligible'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Teams Enterprise wird von Microsoft für Copilot genannt.', 'sort_order' => 178, 'is_active' => 1],
            ['slug' => 'teams-eea', 'name' => 'Microsoft Teams EEA', 'kind' => 'base', 'category' => 'teams', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'EEA-spezifischer Teams-Plan für europäische Bereitstellungen.', 'features' => ['teams'], 'tags' => ['teams', 'eea', 'copilot_enterprise_eligible', 'copilot_chat_eligible'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'EEA-Variante ist von Microsoft als Copilot-Basis aufgeführt.', 'sort_order' => 179, 'is_active' => 1],
            ['slug' => 'copilot-chat-included', 'name' => 'Microsoft 365 Copilot Chat', 'kind' => 'addon', 'category' => 'copilot', 'audience' => 'all', 'pricing_basis' => 'per_user', 'description' => 'Webbasierte Copilot-Chat-Option, bei berechtigten Plänen ohne Zusatzpreis verfügbar.', 'features' => ['copilot_chat'], 'tags' => ['copilot', 'included'], 'prerequisite_tags' => ['copilot_chat_eligible'], 'public_price' => 0.0, 'member_price' => 0.0, 'group_price' => 0.0, 'currency' => 'USD', 'pricing_note' => 'Bei berechtigtem Basisplan ohne Zusatzpreis.', 'source_note' => 'Laut Microsoft ohne Zusatzkosten für berechtigte Microsoft-Entra-ID-Nutzer.', 'sort_order' => 180, 'is_active' => 1],
            ['slug' => 'm365-copilot-business', 'name' => 'Microsoft 365 Copilot Business', 'kind' => 'addon', 'category' => 'copilot', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Copilot-Add-on für Microsoft 365 Business Basic/Standard/Premium und Apps for Business.', 'features' => ['copilot_m365'], 'tags' => ['copilot', 'business'], 'prerequisite_tags' => ['copilot_business_eligible'], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Voraussetzungen gemäß Microsoft Learn Copilot Licensing.', 'sort_order' => 190, 'is_active' => 1],
            ['slug' => 'm365-copilot', 'name' => 'Microsoft 365 Copilot', 'kind' => 'addon', 'category' => 'copilot', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Copilot-Add-on für Enterprise- und weitere berechtigte Pläne.', 'features' => ['copilot_m365'], 'tags' => ['copilot', 'enterprise'], 'prerequisite_tags' => ['copilot_enterprise_eligible'], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Voraussetzungen gemäß Microsoft Learn Copilot Licensing.', 'sort_order' => 200, 'is_active' => 1],
            ['slug' => 'copilot-studio', 'name' => 'Copilot Studio', 'kind' => 'addon', 'category' => 'copilot', 'audience' => 'knowledge', 'pricing_basis' => 'flat_monthly', 'description' => 'Add-on für Agenten- und Bot-Szenarien.', 'features' => ['copilot_studio'], 'tags' => ['copilot', 'studio'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Für Agent- und Erweiterungsszenarien.', 'sort_order' => 210, 'is_active' => 1],
            ['slug' => 'security-copilot', 'name' => 'Security Copilot', 'kind' => 'addon', 'category' => 'copilot', 'audience' => 'knowledge', 'pricing_basis' => 'flat_monthly', 'description' => 'Security-Copilot für SOC- und Security-Teams.', 'features' => ['security_copilot'], 'tags' => ['copilot', 'security'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Optional für Security-Betrieb.', 'sort_order' => 220, 'is_active' => 1],
            ['slug' => 'teams-premium', 'name' => 'Microsoft Teams Premium', 'kind' => 'addon', 'category' => 'teams', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Erweiterte Teams-Funktionen für Meetings und Schutz.', 'features' => ['teams_premium'], 'tags' => ['teams', 'premium'], 'prerequisite_tags' => ['teams'], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Zusatz zu Teams-Szenarien.', 'sort_order' => 230, 'is_active' => 1],
            ['slug' => 'audio-conferencing', 'name' => 'Microsoft Teams Audio Conferencing', 'kind' => 'addon', 'category' => 'teams', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Telefonische Einwahl in Meetings.', 'features' => ['audio_conf'], 'tags' => ['teams', 'audio'], 'prerequisite_tags' => ['teams'], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Für Meeting-Einwahl.', 'sort_order' => 240, 'is_active' => 1],
            ['slug' => 'teams-phone', 'name' => 'Microsoft Teams Phone Standard', 'kind' => 'addon', 'category' => 'teams', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Phone-System-Add-on für Cloud-Telefonie und Rufnummernsteuerung.', 'features' => ['phone_system'], 'tags' => ['teams', 'voice'], 'prerequisite_tags' => ['teams'], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Für Teams-Telefonie zusätzlich lizenzierbar.', 'sort_order' => 245, 'is_active' => 1],
            ['slug' => 'intune-plan-1', 'name' => 'Microsoft Intune Plan 1', 'kind' => 'addon', 'category' => 'security', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Standalone-Intune für MDM/MAM, Compliance und Richtlinien ohne Vollsuite.', 'features' => ['intune'], 'tags' => ['intune', 'security', 'device-management'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Für Basispläne ohne enthaltenes Intune oder als gezielte Ergänzung.', 'sort_order' => 247, 'is_active' => 1],
            ['slug' => 'intune-device', 'name' => 'Microsoft Intune Device', 'kind' => 'addon', 'category' => 'security', 'audience' => 'all', 'pricing_basis' => 'flat_monthly', 'description' => 'Gerätebezogene Intune-Lizenz für gemeinsam genutzte Geräte, Kioske und Spezialgeräte.', 'features' => ['intune_device'], 'tags' => ['intune', 'device'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Sinnvoll für Shared Devices, Frontdesk, Shopfloor oder Kiosk-Szenarien.', 'sort_order' => 248, 'is_active' => 1],
            ['slug' => 'power-bi-pro', 'name' => 'Power BI Pro', 'kind' => 'addon', 'category' => 'power-platform', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Pro-BI-Lizenz für Reports und Freigaben.', 'features' => ['power_bi'], 'tags' => ['bi'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Optional für Reporting-Power-User.', 'sort_order' => 250, 'is_active' => 1],
            ['slug' => 'power-apps-premium', 'name' => 'Power Apps Premium', 'kind' => 'addon', 'category' => 'power-platform', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Premium-Power-Apps-Lizenz für Dataverse, modellgesteuerte Apps und komplexe Business-Apps.', 'features' => ['power_apps'], 'tags' => ['power-platform', 'apps'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Geeignet für individuelle Fachanwendungen und Dataverse-basierte Lösungen.', 'sort_order' => 252, 'is_active' => 1],
            ['slug' => 'visio-plan-1', 'name' => 'Visio Plan 1', 'kind' => 'addon', 'category' => 'productivity', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Browserbasierte Visio-Funktionen für Diagramme und Visualisierung.', 'features' => ['visio'], 'tags' => ['visio'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Visio Plan 1 ist eigenständige Copilot-Voraussetzung.', 'sort_order' => 255, 'is_active' => 1],
            ['slug' => 'visio-plan-2', 'name' => 'Visio Plan 2', 'kind' => 'addon', 'category' => 'productivity', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Visio für Diagramme und technische Visualisierung.', 'features' => ['visio'], 'tags' => ['visio'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Optional für Diagramm-User.', 'sort_order' => 260, 'is_active' => 1],
            ['slug' => 'planner-plan-1', 'name' => 'Microsoft Planner Plan 1', 'kind' => 'addon', 'category' => 'productivity', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Planner/Project Plan 1 für Work-Management.', 'features' => ['planner'], 'tags' => ['planner'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Optional für Planungs-Szenarien.', 'sort_order' => 270, 'is_active' => 1],
            ['slug' => 'project-plan-3', 'name' => 'Microsoft Project Plan 3', 'kind' => 'addon', 'category' => 'productivity', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Projektplanung und Ressourcenmanagement.', 'features' => ['project'], 'tags' => ['project'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Optional für PMO/Projektleiter.', 'sort_order' => 280, 'is_active' => 1],
            ['slug' => 'project-online-essentials', 'name' => 'Project Online Essentials', 'kind' => 'addon', 'category' => 'productivity', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Leichter Project-Zugang für Teammitglieder und Projektbeteiligte.', 'features' => ['project'], 'tags' => ['project'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Project Online Essentials ist von Microsoft als Copilot-Voraussetzung aufgeführt.', 'sort_order' => 285, 'is_active' => 1],
            ['slug' => 'project-plan-5', 'name' => 'Microsoft Project Plan 5', 'kind' => 'addon', 'category' => 'productivity', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Erweiterte Project-Funktionen für Portfolio- und Ressourcensteuerung.', 'features' => ['project'], 'tags' => ['project'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Project Plan 5 ist eigenständige Copilot-Voraussetzung.', 'sort_order' => 288, 'is_active' => 1],
            ['slug' => 'power-automate-premium', 'name' => 'Power Automate Premium', 'kind' => 'addon', 'category' => 'power-platform', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Premium-Konnektoren und erweiterte Automatisierung.', 'features' => ['automation'], 'tags' => ['power-platform', 'automation'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Optional für Automatisierungs-Szenarien.', 'sort_order' => 290, 'is_active' => 1],
            ['slug' => 'exchange-online-archiving', 'name' => 'Exchange Online Archiving', 'kind' => 'addon', 'category' => 'exchange', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Archivierungs-Add-on für Exchange-Postfächer und Compliance-Anforderungen.', 'features' => ['archive'], 'tags' => ['exchange', 'archive'], 'prerequisite_tags' => ['exchange'], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Nützlich für Mail-only- und Compliance-Szenarien.', 'sort_order' => 292, 'is_active' => 1],
            ['slug' => 'exchange-online-protection', 'name' => 'Exchange Online Protection', 'kind' => 'addon', 'category' => 'exchange', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Basisschutz gegen Spam, Malware und Mail-Flow-Bedrohungen für Exchange-Szenarien.', 'features' => ['exchange_protection'], 'tags' => ['exchange', 'security'], 'prerequisite_tags' => ['exchange', 'm365', 'office365'], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Sinnvoll für Exchange-nahe Schutzszenarien unterhalb von Defender for Office 365.', 'sort_order' => 293, 'is_active' => 1],
            ['slug' => 'entra-id-p1', 'name' => 'Microsoft Entra ID P1', 'kind' => 'addon', 'category' => 'identity', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Identitäts-Add-on für Conditional Access, Self-Service und erweitertes Zugriffsmanagement.', 'features' => ['entra_id_p1'], 'tags' => ['identity', 'entra'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Optional für erweiterte Identitäts- und Zugriffsszenarien.', 'sort_order' => 294, 'is_active' => 1],
            ['slug' => 'entra-id-p2', 'name' => 'Microsoft Entra ID P2', 'kind' => 'addon', 'category' => 'identity', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Identitäts-Add-on mit PIM, Identity Protection und risikobasiertem Zugriff.', 'features' => ['entra_id_p2'], 'tags' => ['identity', 'entra', 'premium'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Geeignet für Zero-Trust-, PIM- und besonders schützenswerte Zugriffsmodelle.', 'sort_order' => 296, 'is_active' => 1],
            ['slug' => 'entra-id-governance', 'name' => 'Microsoft Entra ID Governance', 'kind' => 'addon', 'category' => 'identity', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Lifecycle, Access Reviews und Governance-Funktionen für Entra-Identitäten.', 'features' => ['entra_governance'], 'tags' => ['identity', 'entra', 'governance'], 'prerequisite_tags' => ['identity', 'entra'], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Ergänzt Entra-Premium-Szenarien um Governance-Workflows und Access Reviews.', 'sort_order' => 297, 'is_active' => 1],
            ['slug' => 'entra-suite', 'name' => 'Microsoft Entra Suite', 'kind' => 'addon', 'category' => 'identity', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Entra-Zusatzbundle für erweiterte Identity-, Internet- und Private-Access-Szenarien.', 'features' => ['entra_suite'], 'tags' => ['identity', 'entra', 'suite', 'premium'], 'prerequisite_tags' => ['identity', 'entra'], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Geeignet für Zero-Trust- und ZTNA-nahe Entra-Erweiterungen.', 'sort_order' => 298, 'is_active' => 1],
            ['slug' => 'defender-for-business', 'name' => 'Microsoft Defender for Business', 'kind' => 'addon', 'category' => 'security', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Sicherheits-Add-on für SMB-Endpunktschutz und Threat Detection.', 'features' => ['defender_business'], 'tags' => ['security', 'defender', 'business', 'smb'], 'prerequisite_tags' => ['business', 'smb'], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Sinnvoll für Business-Pläne ohne bereits enthaltenes erweitertes Defender-Bundle.', 'sort_order' => 299, 'is_active' => 1],
            ['slug' => 'defender-for-office-365-plan-1', 'name' => 'Microsoft Defender for Office 365 Plan 1', 'kind' => 'addon', 'category' => 'security', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Mail- und Kollaborationsschutz für Exchange, SharePoint, OneDrive und Teams.', 'features' => ['defender_office_p1'], 'tags' => ['security', 'defender', 'office365'], 'prerequisite_tags' => ['exchange', 'm365', 'office365'], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Für gezielten Schutz von Mail- und Kollaborations-Workloads.', 'sort_order' => 300, 'is_active' => 1],
            ['slug' => 'defender-for-office-365-plan-2', 'name' => 'Microsoft Defender for Office 365 Plan 2', 'kind' => 'addon', 'category' => 'security', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Erweiterter Office-365-Schutz mit Investigation, Simulation und Response-Funktionen.', 'features' => ['defender_office_p2'], 'tags' => ['security', 'defender', 'office365', 'premium'], 'prerequisite_tags' => ['exchange', 'm365', 'office365'], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Für anspruchsvollere Mail-/Collaboration-Security-Anforderungen.', 'sort_order' => 302, 'is_active' => 1],
            ['slug' => 'defender-for-endpoint-plan-1', 'name' => 'Microsoft Defender for Endpoint Plan 1', 'kind' => 'addon', 'category' => 'security', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Endpunktschutz-Add-on für Geräte ohne bereits enthaltene MDE-Rechte.', 'features' => ['defender_endpoint_p1'], 'tags' => ['security', 'defender', 'endpoint'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Erweiterter Geräteschutz als Zusatzlizenz.', 'sort_order' => 304, 'is_active' => 1],
            ['slug' => 'defender-for-endpoint-plan-2', 'name' => 'Microsoft Defender for Endpoint Plan 2', 'kind' => 'addon', 'category' => 'security', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Endpunktschutz mit EDR, Threat Hunting und automatischer Reaktion.', 'features' => ['defender_endpoint_p2'], 'tags' => ['security', 'defender', 'endpoint', 'premium'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Für erweiterten Endpunktschutz und Security-Operations-Szenarien.', 'sort_order' => 306, 'is_active' => 1],
            ['slug' => 'defender-for-identity', 'name' => 'Microsoft Defender for Identity', 'kind' => 'addon', 'category' => 'security', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Identitätsbasierter Schutz für AD- und Hybrid-Identitäten.', 'features' => ['defender_identity'], 'tags' => ['security', 'defender', 'identity'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Für Identity-Threat-Detection und Hybrid-Identity-Schutz.', 'sort_order' => 307, 'is_active' => 1],
            ['slug' => 'defender-for-cloud-apps', 'name' => 'Microsoft Defender for Cloud Apps', 'kind' => 'addon', 'category' => 'security', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'CASB- und SaaS-Schutz für Cloud-Apps, Sessions und Datenbewegungen.', 'features' => ['defender_cloud_apps'], 'tags' => ['security', 'defender', 'cloud-apps'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Geeignet für Cloud-App-Governance, CASB und Session Controls.', 'sort_order' => 308, 'is_active' => 1],
        ];

        foreach ($packages as &$package) {
            $package['currency'] = 'EUR';
        }
        unset($package);

        return self::apply_seed_pricing($packages);
    }

    /**
     * @param array<int,array<string,mixed>> $packages
     * @return array<int,array<string,mixed>>
     */
    private static function apply_seed_pricing(array $packages): array
    {
        $priceMap = self::seed_price_map();

        foreach ($packages as &$package) {
            $slug = (string) ($package['slug'] ?? '');
            if (!isset($priceMap[$slug])) {
                continue;
            }

            $price = $priceMap[$slug];
            $package['public_price'] = $price['public'];
            $package['member_price'] = $price['member'];
            $package['group_price'] = $price['group'];
            $package['currency'] = $price['currency'];
            $package['pricing_note'] = $price['pricing_note'];
            $package['source_note'] = $price['source_note'];
            $package['pricing_basis'] = $price['pricing_basis'] ?? ($package['pricing_basis'] ?? 'per_user');
        }
        unset($package);

        return $packages;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private static function seed_price_map(): array
    {
        $yearly = 'Stand der Preise vom 17.03.2026 – tatsächliche Preise können je nach Vertrag und Konditionen abweichen.';

        $perUser = static fn(float $price, string $source, ?float $groupPrice = null): array => [
            'public' => $price,
            'member' => $price,
            'group' => $groupPrice ?? $price,
            'currency' => 'EUR',
            'pricing_note' => $yearly,
            'source_note' => self::normalize_source_note_currency($source),
            'pricing_basis' => 'per_user',
        ];

        $flat = static fn(float $price, string $source, ?float $groupPrice = null): array => [
            'public' => $price,
            'member' => $price,
            'group' => $groupPrice ?? $price,
            'currency' => 'EUR',
            'pricing_note' => 'Stand der Preise vom 17.03.2026 – tatsächliche Preise können je nach Vertrag und Konditionen abweichen.',
            'source_note' => self::normalize_source_note_currency($source),
            'pricing_basis' => 'flat_monthly',
        ];

        return [
            'exchange-online-kiosk' => $perUser(3.16, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Exchange Online Kiosk 3,16 EUR pro Benutzer/Monat.', 1.47),
            'exchange-online-plan-1' => $perUser(3.50, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Exchange Online Plan 1 3,50 EUR pro Benutzer/Monat.', 2.97),
            'exchange-online-plan-2' => $perUser(6.90, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Exchange Online Plan 2 6,90 EUR pro Benutzer/Monat.', 5.85),
            'teams-essentials' => $perUser(3.50, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Teams Essentials 3,50 EUR pro Benutzer/Monat.', 2.97),
            'sharepoint-plan-1' => $perUser(3.50, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): SharePoint Plan 1 3,50 EUR pro Benutzer/Monat.', 3.65),
            'sharepoint-kiosk' => $perUser(4.00, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): SharePoint Kiosk 4,00 EUR pro Benutzer/Monat.'),
            'sharepoint-plan-2' => $perUser(9.30, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): SharePoint Plan 2 9,30 EUR pro Benutzer/Monat.', 7.38),
            'onedrive-plan-1' => $perUser(4.30, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): OneDrive Plan 1 4,30 EUR pro Benutzer/Monat.', 3.65),
            'onedrive-plan-2' => $perUser(9.50, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): OneDrive Plan 2 9,50 EUR pro Benutzer/Monat.', 7.38),
            'm365-apps-business' => $perUser(8.25, 'Microsoft 365 Pricing Updates Snippet: Apps for Business 8.25 USD pro Benutzer/Monat.', 7.72),
            'm365-apps-enterprise' => $perUser(13.20, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft 365 Apps for Enterprise 13,20 EUR pro Benutzer/Monat.', 11.20),
            'm365-business-basic' => $perUser(5.20, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft 365 Business Basic 5,20 EUR pro Benutzer/Monat.', 4.41),
            'm365-business-standard' => $perUser(10.80, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft 365 Business Standard 10,80 EUR pro Benutzer/Monat.', 9.16),
            'm365-business-premium' => $perUser(19.10, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft 365 Business Premium 19,10 EUR pro Benutzer/Monat.', 16.20),
            'office-365-e1' => $perUser(8.70, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Office 365 E1 8,70 EUR pro Benutzer/Monat.', 7.38),
            'office-365-e3' => $perUser(23.20, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Office 365 E3 23,20 EUR pro Benutzer/Monat.', 19.68),
            'office-365-e5' => $perUser(38.40, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Office 365 E5 38,40 EUR pro Benutzer/Monat.', 32.58),
            'm365-e3' => $perUser(34.90, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft 365 E3 34,90 EUR pro Benutzer/Monat.', 29.61),
            'm365-e5' => $perUser(57.00, 'Microsoft 365 E5 57 USD pro Benutzer/Monat; geeignet für Terminalserver-/RDS-Betrieb mit Shared Computer Activation.', 46.83),
            'm365-f1' => $perUser(1.90, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft 365 F1 1,90 EUR pro Benutzer/Monat.', 1.61),
            'm365-f3' => $perUser(6.90, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft 365 F3 6,90 EUR pro Benutzer/Monat.', 5.85),
            'office-365-f3' => $perUser(6.90, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Office 365 F3 6,90 EUR pro Benutzer/Monat.', 2.97),
            'teams-enterprise' => $perUser(7.40, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Teams Enterprise 7,40 EUR pro Benutzer/Monat.'),
            'teams-eea' => $perUser(7.80, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Teams EEA 7,80 EUR pro Benutzer/Monat.', 6.28),
            'copilot-chat-included' => $perUser(0.00, 'Microsoft Copilot Pricing: Copilot Chat für berechtigte Entra-ID-Nutzer ohne Zusatzkosten.'),
            'm365-copilot-business' => $perUser(18.20, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft 365 Copilot Business 18,20 EUR pro Benutzer/Monat.', 14.89),
            'm365-copilot' => $perUser(26.00, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft 365 Copilot 26,00 EUR pro Benutzer/Monat.', 24.82),
            'copilot-studio' => $flat(200.00, 'Microsoft Copilot Studio Pricing: Prepaid Pack 25.000 Credits für 200 USD pro Monat pro Tenant.', 165.41),
            'security-copilot' => $flat(2920.00, 'Öffentliche Security Copilot Preis-/SCU-Snippets: 1 SCU ca. 2.920 USD pro Monat.'),
            'teams-premium' => $perUser(8.70, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Teams Premium 8,70 EUR pro Benutzer/Monat.', 7.38),
            'audio-conferencing' => $perUser(4.00, 'Öffentliche Microsoft-/Partner-Preisübersichten für Audio Conferencing: 4 USD pro Benutzer/Monat.', 2.31),
            'teams-phone' => $perUser(8.70, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Teams Phone Standard 8,70 EUR pro Benutzer/Monat.', 7.38),
            'intune-plan-1' => $perUser(6.90, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Intune Plan 1 6,90 EUR pro Benutzer/Monat.', 5.85),
            'intune-device' => $flat(2.56, 'Öffentliche Microsoft-Intune-Preisreferenzen: Intune Device liegt bei 2,56 EUR pro Gerät/Monat bei Jahresbindung.', 1.95),
            'power-bi-pro' => $perUser(12.10, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Power BI Pro 12,10 EUR pro Benutzer/Monat.', 10.27),
            'power-apps-premium' => $perUser(17.30, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Power Apps Premium 17,30 EUR pro Benutzer/Monat.', 13.76),
            'visio-plan-1' => $perUser(4.30, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Visio Plan 1 4,30 EUR pro Benutzer/Monat.', 3.65),
            'visio-plan-2' => $perUser(13.00, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Visio Plan 2 13,00 EUR pro Benutzer/Monat.', 11.03),
            'planner-plan-1' => $perUser(8.70, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Planner Plan 1 8,70 EUR pro Benutzer/Monat.', 7.38),
            'project-plan-3' => $perUser(26.00, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Project Plan 3 26,00 EUR pro Benutzer/Monat.', 22.06),
            'project-online-essentials' => $perUser(7.00, 'Öffentliche CSP-/Partner-Snippets: Project Online Essentials 7 USD pro Benutzer/Monat.'),
            'project-plan-5' => $perUser(47.70, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Project Plan 5 47,70 EUR pro Benutzer/Monat.', 40.47),
            'power-automate-premium' => $perUser(13.00, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Power Automate Premium 13,00 EUR pro Benutzer/Monat.', 10.34),
            'exchange-online-archiving' => $perUser(3.00, 'Öffentliche Exchange Online Archiving Preisübersichten: typischer Listenwert 3 USD pro Benutzer/Monat.', 2.21),
            'exchange-online-protection' => $perUser(0.90, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Exchange Online Protection 0,90 EUR pro Benutzer/Monat.', 0.74),
            'entra-id-p1' => $perUser(5.20, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Entra ID P1 5,20 EUR pro Benutzer/Monat.', 4.41),
            'entra-id-p2' => $perUser(7.80, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Entra ID P2 7,80 EUR pro Benutzer/Monat.', 6.62),
            'entra-id-governance' => $perUser(6.10, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Entra ID Governance 6,10 EUR pro Benutzer/Monat.', 5.18),
            'entra-suite' => $perUser(10.40, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Entra Suite 10,40 EUR pro Benutzer/Monat.', 8.82),
            'defender-for-business' => $perUser(2.60, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Defender for Business 2,60 EUR pro Benutzer/Monat.', 2.21),
            'defender-for-office-365-plan-1' => $perUser(1.73, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Defender for Office 365 Plan 1 1,73 EUR pro Benutzer/Monat.', 1.47),
            'defender-for-office-365-plan-2' => $perUser(4.30, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Defender for Office 365 Plan 2 4,30 EUR pro Benutzer/Monat.', 3.65),
            'defender-for-endpoint-plan-1' => $perUser(3.50, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Defender for Endpoint Plan 1 3,50 EUR pro Benutzer/Monat.', 2.21),
            'defender-for-endpoint-plan-2' => $perUser(5.50, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Defender for Endpoint Plan 2 5,50 EUR pro Benutzer/Monat.'),
            'defender-for-identity' => $perUser(5.60, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Defender for Identity 5,60 EUR pro Benutzer/Monat.', 4.07),
            'defender-for-cloud-apps' => $perUser(3.60, 'Preis laut bereitgestellter Referenzliste des Nutzers (Stand 2026-03-17): Microsoft Defender for Cloud Apps 3,60 EUR pro Benutzer/Monat.', 2.55),
        ];
    }

    private static function normalize_source_note_currency(string $source): string
    {
        $normalized = str_replace(
            [' USD ', ' USD', 'usd', 'paid yearly'],
            [' EUR ', ' EUR', 'EUR', 'bei Jahresbindung'],
            $source
        );

        if (stripos($normalized, 'EUR-Referenzpreis') === false) {
            $normalized .= ' Im Plugin als EUR-Referenzpreis für Jahr / monatlich gepflegt.';
        }

        return $normalized;
    }
}
