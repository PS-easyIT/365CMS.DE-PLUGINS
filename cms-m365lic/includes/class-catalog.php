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
            'intune' => ['label' => 'Intune / Geräteverwaltung', 'group' => 'security', 'description' => 'MDM/MAM, Richtlinien und Endpoint-Verwaltung.', 'base' => true],
            'defender' => ['label' => 'Defender / Sicherheit', 'group' => 'security', 'description' => 'Erweiterte Sicherheits- und Schutzfunktionen.', 'base' => true],
            'archive' => ['label' => 'Archiv / Compliance', 'group' => 'security', 'description' => 'Archivierung, erweiterte Compliance und Aufbewahrung.', 'base' => true],
            'phone_system' => ['label' => 'Telefonie / Phone System', 'group' => 'addons', 'description' => 'Telefonie-nahe Teams-Funktionen und Rufsteuerung.', 'base' => false],
            'audio_conf' => ['label' => 'Audiokonferenzen', 'group' => 'addons', 'description' => 'Einwahl in Meetings via Telefon.', 'base' => false],
            'power_bi' => ['label' => 'Power BI Pro', 'group' => 'addons', 'description' => 'Interaktive Reports, Freigaben und Dashboards.', 'base' => false],
            'visio' => ['label' => 'Visio', 'group' => 'addons', 'description' => 'Diagramme, Netzpläne und Prozessdesign.', 'base' => false],
            'project' => ['label' => 'Project', 'group' => 'addons', 'description' => 'Projektplanung, Ressourcen und Roadmaps.', 'base' => false],
            'planner' => ['label' => 'Planner Premium / Plan 1', 'group' => 'addons', 'description' => 'Planung, Aufgaben und Work-Management.', 'base' => false],
            'automation' => ['label' => 'Power Automate Premium', 'group' => 'addons', 'description' => 'Premium-Workflows und Automationen.', 'base' => false],
            'teams_premium' => ['label' => 'Teams Premium', 'group' => 'addons', 'description' => 'Erweiterte Meeting-, Webinar- und Schutzfunktionen.', 'base' => false],
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
                'note' => 'Basispreis laut öffentlicher Jahresbindung.',
            ],
            'annual_monthly' => [
                'key' => 'annual_monthly',
                'label' => '1 Jahr · monatliche Zahlung (+5%)',
                'short_label' => 'Jahr / monatlich',
                'multiplier' => 1.05,
                'note' => 'Jahresbindung mit monatlicher Zahlung, 5% Aufschlag.',
            ],
            'monthly_flex' => [
                'key' => 'monthly_flex',
                'label' => '1 Monat · monatlich (+20%)',
                'short_label' => 'Monat / flexibel',
                'multiplier' => 1.20,
                'note' => 'Flexible Monatslaufzeit, 20% Aufschlag.',
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
            'default_group_key' => 'partner',
            'default_group_label' => 'Partner / Spezialgruppe',
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
            'show_missing_price_hint' => '1',
            'show_source_notes' => '1',
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
            ['slug' => 'power-bi-pro', 'name' => 'Power BI Pro', 'kind' => 'addon', 'category' => 'power-platform', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Pro-BI-Lizenz für Reports und Freigaben.', 'features' => ['power_bi'], 'tags' => ['bi'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Optional für Reporting-Power-User.', 'sort_order' => 250, 'is_active' => 1],
            ['slug' => 'visio-plan-1', 'name' => 'Visio Plan 1', 'kind' => 'addon', 'category' => 'productivity', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Browserbasierte Visio-Funktionen für Diagramme und Visualisierung.', 'features' => ['visio'], 'tags' => ['visio'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Visio Plan 1 ist eigenständige Copilot-Voraussetzung.', 'sort_order' => 255, 'is_active' => 1],
            ['slug' => 'visio-plan-2', 'name' => 'Visio Plan 2', 'kind' => 'addon', 'category' => 'productivity', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Visio für Diagramme und technische Visualisierung.', 'features' => ['visio'], 'tags' => ['visio'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Optional für Diagramm-User.', 'sort_order' => 260, 'is_active' => 1],
            ['slug' => 'planner-plan-1', 'name' => 'Microsoft Planner Plan 1', 'kind' => 'addon', 'category' => 'productivity', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Planner/Project Plan 1 für Work-Management.', 'features' => ['planner'], 'tags' => ['planner'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Optional für Planungs-Szenarien.', 'sort_order' => 270, 'is_active' => 1],
            ['slug' => 'project-plan-3', 'name' => 'Microsoft Project Plan 3', 'kind' => 'addon', 'category' => 'productivity', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Projektplanung und Ressourcenmanagement.', 'features' => ['project'], 'tags' => ['project'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Optional für PMO/Projektleiter.', 'sort_order' => 280, 'is_active' => 1],
            ['slug' => 'project-online-essentials', 'name' => 'Project Online Essentials', 'kind' => 'addon', 'category' => 'productivity', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Leichter Project-Zugang für Teammitglieder und Projektbeteiligte.', 'features' => ['project'], 'tags' => ['project'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Project Online Essentials ist von Microsoft als Copilot-Voraussetzung aufgeführt.', 'sort_order' => 285, 'is_active' => 1],
            ['slug' => 'project-plan-5', 'name' => 'Microsoft Project Plan 5', 'kind' => 'addon', 'category' => 'productivity', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Erweiterte Project-Funktionen für Portfolio- und Ressourcensteuerung.', 'features' => ['project'], 'tags' => ['project'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Project Plan 5 ist eigenständige Copilot-Voraussetzung.', 'sort_order' => 288, 'is_active' => 1],
            ['slug' => 'power-automate-premium', 'name' => 'Power Automate Premium', 'kind' => 'addon', 'category' => 'power-platform', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Premium-Konnektoren und erweiterte Automatisierung.', 'features' => ['automation'], 'tags' => ['power-platform', 'automation'], 'prerequisite_tags' => [], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Optional für Automatisierungs-Szenarien.', 'sort_order' => 290, 'is_active' => 1],
            ['slug' => 'exchange-online-archiving', 'name' => 'Exchange Online Archiving', 'kind' => 'addon', 'category' => 'exchange', 'audience' => 'knowledge', 'pricing_basis' => 'per_user', 'description' => 'Archivierungs-Add-on für Exchange-Postfächer und Compliance-Anforderungen.', 'features' => ['archive'], 'tags' => ['exchange', 'archive'], 'prerequisite_tags' => ['exchange'], 'public_price' => null, 'member_price' => null, 'group_price' => null, 'currency' => 'USD', 'pricing_note' => '', 'source_note' => 'Nützlich für Mail-only- und Compliance-Szenarien.', 'sort_order' => 292, 'is_active' => 1],
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
        $yearly = 'EUR-Basispreis bei Jahresbindung / jährlicher Zahlung. Monatliche Jahreszahlung = +5%, Monatslaufzeit = +20%.';

        $perUser = static fn(float $price, string $source): array => [
            'public' => $price,
            'member' => $price,
            'group' => $price,
            'currency' => 'EUR',
            'pricing_note' => $yearly,
            'source_note' => self::normalize_source_note_currency($source),
            'pricing_basis' => 'per_user',
        ];

        $flat = static fn(float $price, string $source): array => [
            'public' => $price,
            'member' => $price,
            'group' => $price,
            'currency' => 'EUR',
            'pricing_note' => 'Fixpreis pro Monat/Tenant in EUR. Jahreszahlung = Basis, monatliche Jahreszahlung +5%, Monatslaufzeit +20%.',
            'source_note' => self::normalize_source_note_currency($source),
            'pricing_basis' => 'flat_monthly',
        ];

        return [
            'exchange-online-kiosk' => $perUser(2.00, 'Öffentliche Microsoft-/Partner-Snippets für Exchange Online Kiosk: 2 USD pro Benutzer/Monat bei Jahresbindung.'),
            'exchange-online-plan-1' => $perUser(4.00, 'Microsoft Exchange Online Pricing Snippet: Exchange Online Plan 1 startet bei 4 USD pro Benutzer/Monat, paid yearly.'),
            'exchange-online-plan-2' => $perUser(8.00, 'Microsoft Exchange Online Pricing Snippet: Exchange Online Plan 2 startet bei 8 USD pro Benutzer/Monat, paid yearly.'),
            'teams-essentials' => $perUser(4.00, 'Öffentliche Microsoft-Teams-Preisübersichten für Teams Essentials: 4 USD pro Benutzer/Monat, paid yearly.'),
            'sharepoint-plan-1' => $perUser(5.00, 'Microsoft SharePoint Pricing Snippet: SharePoint Plan 1 startet bei 5 USD pro Benutzer/Monat, paid yearly.'),
            'sharepoint-kiosk' => $perUser(4.00, 'Kiosk-Referenzwert aus öffentlichen Microsoft-/Partnervergleichen für leichte SharePoint/Frontline-Szenarien.'),
            'sharepoint-plan-2' => $perUser(10.00, 'Öffentliche SharePoint-Planvergleiche: Plan 2 typischer Listenwert 10 USD pro Benutzer/Monat.'),
            'onedrive-plan-1' => $perUser(5.00, 'Öffentliche Microsoft-/Partner-Preisübersichten: OneDrive Plan 1 typischer Listenwert 5 USD pro Benutzer/Monat.'),
            'onedrive-plan-2' => $perUser(10.00, 'Öffentliche OneDrive-Preisübersichten: OneDrive Plan 2 typischer Listenwert 10 USD pro Benutzer/Monat.'),
            'm365-apps-business' => $perUser(8.25, 'Microsoft 365 Pricing Updates Snippet: Apps for Business 8.25 USD pro Benutzer/Monat.'),
            'm365-apps-enterprise' => $perUser(12.00, 'Microsoft 365 Apps for enterprise unterstützt Shared Computer Activation für RDS-/Terminalserver-Szenarien und startet öffentlich bei 12 USD pro Benutzer/Monat.'),
            'm365-business-basic' => $perUser(6.00, 'Microsoft 365 Pricing Updates Snippet: Business Basic 6 USD pro Benutzer/Monat.'),
            'm365-business-standard' => $perUser(12.50, 'Microsoft 365 Pricing Updates Snippet: Business Standard 12.50 USD pro Benutzer/Monat.'),
            'm365-business-premium' => $perUser(22.00, 'Microsoft 365 Business Premium 22 USD pro Benutzer/Monat; Shared Computer Activation für kleinere RDS-/Terminalserver-Szenarien ist möglich.'),
            'office-365-e1' => $perUser(10.00, 'Microsoft 365 Pricing Updates Snippet: Office 365 E1 10 USD pro Benutzer/Monat.'),
            'office-365-e3' => $perUser(23.00, 'Office 365 E3 23 USD pro Benutzer/Monat; geeignet für Terminalserver-/RDS-Betrieb mit Shared Computer Activation.'),
            'office-365-e5' => $perUser(38.00, 'Office 365 E5 38 USD pro Benutzer/Monat; geeignet für Terminalserver-/RDS-Betrieb mit Shared Computer Activation.'),
            'm365-e3' => $perUser(36.00, 'Microsoft 365 E3 36 USD pro Benutzer/Monat; geeignet für Terminalserver-/RDS-Betrieb mit Shared Computer Activation.'),
            'm365-e5' => $perUser(57.00, 'Microsoft 365 E5 57 USD pro Benutzer/Monat; geeignet für Terminalserver-/RDS-Betrieb mit Shared Computer Activation.'),
            'm365-f1' => $perUser(2.25, 'Microsoft 365 Pricing Updates Snippet: Microsoft 365 F1 2.25 USD pro Benutzer/Monat.'),
            'm365-f3' => $perUser(8.00, 'Microsoft 365 Pricing Updates Snippet: Microsoft 365 F3 8 USD pro Benutzer/Monat.'),
            'office-365-f3' => $perUser(8.00, 'Öffentliche Frontline-/F3-Preisübersichten: Office 365 F3 8 USD pro Benutzer/Monat.'),
            'teams-enterprise' => $perUser(5.25, 'Öffentliche Teams-Preisübersichten für No-Teams-Suiten: Teams Enterprise Add-on 5.25 USD pro Benutzer/Monat.'),
            'teams-eea' => $perUser(5.25, 'EEA-Teamlizenz als Referenzwert analog Microsoft Teams Enterprise Add-on laut öffentlichen Preisübersichten.'),
            'copilot-chat-included' => $perUser(0.00, 'Microsoft Copilot Pricing: Copilot Chat für berechtigte Entra-ID-Nutzer ohne Zusatzkosten.'),
            'm365-copilot-business' => $perUser(30.00, 'Microsoft 365 Copilot Pricing Snippets: Copilot Add-on 30 USD pro Benutzer/Monat.'),
            'm365-copilot' => $perUser(30.00, 'Microsoft 365 Copilot Pricing Snippets: Copilot Add-on 30 USD pro Benutzer/Monat.'),
            'copilot-studio' => $flat(200.00, 'Microsoft Copilot Studio Pricing: Prepaid Pack 25.000 Credits für 200 USD pro Monat pro Tenant.'),
            'security-copilot' => $flat(2920.00, 'Öffentliche Security Copilot Preis-/SCU-Snippets: 1 SCU ca. 2.920 USD pro Monat.'),
            'teams-premium' => $perUser(10.00, 'Öffentliche Microsoft-Teams-Premium-Snippets: 10 USD pro Benutzer/Monat.'),
            'audio-conferencing' => $perUser(4.00, 'Öffentliche Microsoft-/Partner-Preisübersichten für Audio Conferencing: 4 USD pro Benutzer/Monat.'),
            'teams-phone' => $perUser(10.00, 'Öffentliche Teams Phone Standard Preisübersichten: 10 USD pro Benutzer/Monat.'),
            'power-bi-pro' => $perUser(14.00, 'Microsoft Power BI Pricing Snippet: Power BI Pro 14 USD pro Benutzer/Monat, paid yearly.'),
            'visio-plan-1' => $perUser(5.00, 'Microsoft Visio Pricing Snippet: Visio Plan 1 5 USD pro Benutzer/Monat.'),
            'visio-plan-2' => $perUser(15.00, 'Microsoft Visio Pricing Snippet: Visio Plan 2 15 USD pro Benutzer/Monat.'),
            'planner-plan-1' => $perUser(10.00, 'Öffentliche Microsoft Planner / Project Pricing Snippets: Planner Plan 1 10 USD pro Benutzer/Monat.'),
            'project-plan-3' => $perUser(30.00, 'Öffentliche Microsoft Project Pricing Snippets: Project Plan 3 30 USD pro Benutzer/Monat.'),
            'project-online-essentials' => $perUser(7.00, 'Öffentliche CSP-/Partner-Snippets: Project Online Essentials 7 USD pro Benutzer/Monat.'),
            'project-plan-5' => $perUser(55.00, 'Öffentliche Microsoft Project Pricing Snippets: Project Plan 5 55 USD pro Benutzer/Monat.'),
            'power-automate-premium' => $perUser(15.00, 'Microsoft Power Automate Pricing Snippet: Premium 15 USD pro Benutzer/Monat.'),
            'exchange-online-archiving' => $perUser(3.00, 'Öffentliche Exchange Online Archiving Preisübersichten: typischer Listenwert 3 USD pro Benutzer/Monat.'),
        ];
    }

    private static function normalize_source_note_currency(string $source): string
    {
        $normalized = str_replace(
            [' USD ', ' USD', 'usd', 'paid yearly'],
            [' EUR ', ' EUR', 'EUR', 'bei Jahresbindung'],
            $source
        );

        if (stripos($normalized, 'EUR-Basispreis') === false) {
            $normalized .= ' Im Plugin als EUR-Basispreis gepflegt.';
        }

        return $normalized;
    }
}
