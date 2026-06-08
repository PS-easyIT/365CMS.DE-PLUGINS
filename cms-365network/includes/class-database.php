<?php
/**
 * Database and settings handling for CMS 365NETWORK.
 *
 * @package CMS_365NETWORK
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_365NETWORK_Database
{
    private static ?self $instance = null;
    private ?array $settingsCache = null;
    private ?array $hubSettingsCache = null;
    private bool $hubSchemaChecked = false;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct() {}

    public function create_tables(): void
    {
        try {
            $db     = CMS\Database::instance();
            $pdo    = $db->getPdo();
            $prefix = $db->prefix();

            $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}network365_settings (
                id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key   VARCHAR(191) NOT NULL UNIQUE,
                setting_value LONGTEXT DEFAULT NULL,
                updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}network_hub_settings (
                id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key   VARCHAR(100) NOT NULL UNIQUE,
                setting_val   TEXT DEFAULT NULL,
                setting_type  ENUM('text','textarea','bool','int','color','select') DEFAULT 'text',
                section       VARCHAR(50) NOT NULL,
                label         VARCHAR(150) NOT NULL,
                sort_order    INT DEFAULT 0,
                updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_section_sort (section, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $this->ensure_hub_settings_schema($pdo, $prefix);

            $this->seed_default_settings();
            $this->seed_default_hub_settings();
            $this->ensure_toolbox_hub_index($pdo, $prefix);
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK create_tables failed: ' . $e->getMessage());
        }
    }

    public function default_settings(): array
    {
        return [
            'settings_version' => CMS_365NETWORK_VERSION,
            'landing_enabled' => '1',
            'hub_domains' => '',
            'route_slug' => '365network',
            'hero_eyebrow' => '365network Hub',
            'landing_title' => 'Events, Speaker, Firmen & Experten an einem Ort',
            'landing_subtitle' => 'Die zentrale Landingpage für Ihr Netzwerk: kuratierte Veranstaltungen, inspirierende Speaker, passende Firmen und Expertinnen und Experten.',
            'primary_button_label' => 'Bereiche entdecken',
            'primary_button_url' => '#n365-areas',
            'secondary_button_label' => 'Kontakt aufnehmen',
            'secondary_button_url' => '/kontakt',
            'featured_enabled' => '1',
            'featured_title' => '365network verbindet Menschen, Wissen und Business.',
            'featured_text' => 'Nutzen Sie eine moderne HubSite als Einstiegspunkt für Community, Partner und Fachthemen – responsiv, schnell und flexibel steuerbar.',
            'featured_image_url' => '',
            'featured_url' => '',
            'layout_variant' => 'grid-2x2',
            'content_width' => '1180',
            'card_radius' => '2',
            'section_gap' => '28',
            'primary_color' => '#e6a817',
            'accent_color' => '#e6a817',
            'background_color' => '#e8ecf0',
            'surface_color' => '#ffffff',
            'text_color' => '#1a2e4a',
            'muted_color' => '#5a6a7a',
            'border_color' => '#dce3ec',
            'show_sidebar' => '1',
            'sidebar_position' => 'right',
            'sidebar_events_count' => '3',
            'random_speakers_count' => '1',
            'random_companies_count' => '1',
            'random_experts_count' => '1',
            'preview_placement' => 'sidebar',
            'show_events_preview' => '1',
            'show_speakers_preview' => '1',
            'show_companies_preview' => '1',
            'show_experts_preview' => '1',
            'events_card_title' => 'Events',
            'events_card_text' => 'Kommende Veranstaltungen, Formate und Community-Termine.',
            'events_card_url' => '/events',
            'speakers_card_title' => 'Speaker',
            'speakers_card_text' => 'Menschen mit Bühne, Erfahrung und starken Themen.',
            'speakers_card_url' => '/event-speakers',
            'companies_card_title' => 'Firmen',
            'companies_card_text' => 'Partner, Unternehmen und Organisationen im Netzwerk.',
            'companies_card_url' => '/companies',
            'experts_card_title' => 'Experten',
            'experts_card_text' => 'Fachleute für Beratung, Projekte und Umsetzung.',
            'experts_card_url' => '/experts',
            'analytics_enabled' => '0',
            'analytics_position' => 'head',
            'analytics_code' => '',
        ];
    }

    /**
     * @return array<string,array{setting_val:string,setting_type:string,section:string,label:string,sort_order:int}>
     */
    public function default_hub_settings(): array
    {
        $rows = [
            ['hub_section_order', 'partnerband,hero,areas,next-events,spotlight,partner-columns,toolbox,featured,stats,band,posts', 'text', 'order', 'Reihenfolge der Public-Bereiche', 10],
            ['hub_featured_visible', '1', 'bool', 'featured', 'Featured-Bereich anzeigen', 10],
            ['hub_featured_style', 'auto', 'select', 'featured', 'Darstellung', 15],
            ['hub_featured_label', 'Featured', 'text', 'featured', 'Label (z.B. "Featured", "Highlight")', 20],
            ['hub_featured_title', '', 'text', 'featured', 'Überschrift', 30],
            ['hub_featured_sub', '', 'textarea', 'featured', 'Beschreibungstext', 40],
            ['hub_featured_btn_label', 'Mehr erfahren', 'text', 'featured', 'Button Beschriftung', 50],
            ['hub_featured_btn_url', '', 'text', 'featured', 'Button URL (leer = kein Button)', 60],
            ['hub_featured_image_url', '', 'text', 'featured', 'Bild-URL (leer = einspaltig ohne Bild)', 70],
            ['hub_featured_image_height', '320', 'int', 'featured', 'Bildhöhe (px)', 75],
            ['hub_featured_width', 'full', 'select', 'featured', 'Breite', 80],
            ['hub_featured_bg_color', '#ffffff', 'color', 'featured', 'Hintergrundfarbe', 90],
            ['hub_featured_text_color', '#1a2e4a', 'color', 'featured', 'Textfarbe', 100],
            ['hub_featured_accent_color', '#e6a817', 'color', 'featured', 'Akzentfarbe', 110],
            ['hub_featured_radius', '2', 'int', 'featured', 'Rundung (px, max. 2)', 120],
            ['hub_hero_label', '365NETWORK Hub', 'text', 'hero', 'Label über H1', 10],
            ['hub_hero_title', 'Finde Speaker, Experts, Unternehmen und Events', 'text', 'hero', 'Hauptüberschrift H1', 20],
            ['hub_hero_image_url', '', 'text', 'hero', 'Hero-Bild (URL oder Mediathek)', 25],
            ['hub_hero_image_mode', 'replace-title', 'select', 'hero', 'Bilddarstellung', 26],
            ['hub_hero_image_width', '250', 'int', 'hero', 'Bildbreite (px)', 27],
            ['hub_hero_image_height', '200', 'int', 'hero', 'Bildhöhe (px)', 28],
            ['hub_hero_sub', 'Ein zentraler Hub, der die Microsoft-365-Community verbindet — gebündelt, durchsuchbar, an einem Ort.', 'text', 'hero', 'Untertitel', 30],
            ['hub_hero_btn1_label', 'Suchen', 'text', 'hero', 'Button 1 Beschriftung', 40],
            ['hub_hero_btn1_url', '/365network/search', 'text', 'hero', 'Button 1 URL', 50],
            ['hub_hero_btn2_label', '', 'text', 'hero', 'Button 2 Beschriftung', 60],
            ['hub_hero_btn2_url', '', 'text', 'hero', 'Button 2 URL', 70],
            ['hub_hero_visible', '1', 'bool', 'hero', 'Hero-Bereich anzeigen', 80],
            ['hub_hero_layout', 'center', 'select', 'hero', 'Hero-Layout', 90],
            ['hub_hero_height', 'normal', 'select', 'hero', 'Höhen-Layout', 95],
            ['hub_hero_bg_color', '#0d1d33', 'color', 'hero', 'Hintergrundfarbe', 100],
            ['hub_hero_text_color', '#ffffff', 'color', 'hero', 'Textfarbe', 110],
            ['hub_hero_accent_color', '#d6951a', 'color', 'hero', 'Akzentfarbe', 120],
            ['hub_hero_radius', '0', 'int', 'hero', 'Rundung (px, max. 2)', 130],
            ['hub_hero_search_visible', '1', 'bool', 'hero', 'Hero-Suche anzeigen', 140],
            ['hub_hero_search_placeholder', 'Name, Thema oder Unternehmen suchen …', 'text', 'hero', 'Hero-Suche Placeholder', 150],
            ['hub_stats_visible', '1', 'bool', 'stats', 'Zähler-Kacheln anzeigen', 10],
            ['hub_stats_events_label', 'Events', 'text', 'stats', 'Label Events-Kachel', 15],
            ['hub_stats_events_url', '/events', 'text', 'stats', 'URL Events-Kachel', 20],
            ['hub_stats_speakers_label', 'Speaker', 'text', 'stats', 'Label Speaker-Kachel', 25],
            ['hub_stats_speakers_url', '/event-speakers', 'text', 'stats', 'URL Speaker-Kachel', 30],
            ['hub_stats_companies_label', 'Firmen', 'text', 'stats', 'Label Firmen-Kachel', 35],
            ['hub_stats_companies_url', '/firmen', 'text', 'stats', 'URL Firmen-Kachel', 40],
            ['hub_stats_experts_label', 'Experten', 'text', 'stats', 'Label Experten-Kachel', 45],
            ['hub_stats_experts_url', '/experten', 'text', 'stats', 'URL Experten-Kachel', 50],
            ['hub_stats_layout', 'grid', 'select', 'stats', 'Layout', 60],
            ['hub_stats_bg_color', '#f0f3f7', 'color', 'stats', 'Kachel-Hintergrund', 70],
            ['hub_stats_text_color', '#1a2e4a', 'color', 'stats', 'Textfarbe', 80],
            ['hub_stats_accent_color', '#e6a817', 'color', 'stats', 'Akzentfarbe', 90],
            ['hub_stats_radius', '2', 'int', 'stats', 'Rundung (px, max. 2)', 100],
            ['hub_band_visible', '1', 'bool', 'band', 'Teaser-Band anzeigen', 10],
            ['hub_band_event_visible', '1', 'bool', 'band', 'Nächstes-Event-Kachel anzeigen', 20],
            ['hub_band_search_visible', '1', 'bool', 'band', 'Such-Kachel anzeigen', 30],
            ['hub_band_event_label', 'Nächstes Event', 'text', 'band', 'Event-Kachel Label', 35],
            ['hub_band_event_cta', 'Zum Event', 'text', 'band', 'Event-Kachel CTA', 38],
            ['hub_band_search_label', 'Netzwerk durchsuchen', 'text', 'band', 'Such-Kachel Label', 39],
            ['hub_band_search_title', 'Events, Speaker, Firmen und Experten finden', 'text', 'band', 'Such-Kachel Text', 40],
            ['hub_band_search_url', '/365network/search', 'text', 'band', 'Such-Ergebnis-URL', 40],
            ['hub_band_search_param', 'q', 'text', 'band', 'Such-URL-Parameter', 50],
            ['hub_band_search_placeholder', 'Suchbegriff eingeben ...', 'text', 'band', 'Suchfeld Placeholder', 55],
            ['hub_band_layout', 'split', 'select', 'band', 'Layout', 60],
            ['hub_band_bg_color', '#ffffff', 'color', 'band', 'Kachel-Hintergrund', 70],
            ['hub_band_text_color', '#1a2e4a', 'color', 'band', 'Textfarbe', 80],
            ['hub_band_accent_color', '#e6a817', 'color', 'band', 'Akzentfarbe', 90],
            ['hub_band_radius', '2', 'int', 'band', 'Rundung (px, max. 2)', 100],
            ['hub_partnerband_visible', '1', 'bool', 'partnerband', 'Partnerband anzeigen', 10],
            ['hub_partnerband_label', 'Partner im Netzwerk', 'text', 'partnerband', 'Label', 20],
            ['hub_partnerband_limit', '4', 'int', 'partnerband', 'Maximale Partnerlogos', 30],
            ['hub_partnerband_bg_color', '#0a1626', 'color', 'partnerband', 'Hintergrundfarbe', 40],
            ['hub_partnerband_text_color', '#dfe8f2', 'color', 'partnerband', 'Textfarbe', 50],
            ['hub_partnerband_accent_color', '#d6951a', 'color', 'partnerband', 'Akzentfarbe', 60],
            ['hub_areas_visible', '1', 'bool', 'areas', 'Direkteinstieg anzeigen', 10],
            ['hub_areas_maintenance_mode', '0', 'bool', 'areas', 'Alle Bereichskarten im Wartungsmodus', 12],
            ['hub_area_card_order', 'events,speakers,experts,companies', 'text', 'areas', 'Reihenfolge der Bereichskarten', 15],
            ['hub_areas_label', 'Direkteinstieg', 'text', 'areas', 'Sektion-Label', 20],
            ['hub_areas_title', 'Vier Bereiche, ein Netzwerk', 'text', 'areas', 'Sektion-Überschrift', 30],
            ['hub_areas_layout', 'grid-2x2', 'select', 'areas', 'Layout', 35],
            ['hub_areas_card_style', 'icon-corner', 'select', 'areas', 'Kartenstil', 38],
            ['hub_area_events_visible', '1', 'bool', 'areas', 'Kachel Events anzeigen', 40],
            ['hub_area_events_maintenance', '0', 'bool', 'areas', 'Kachel Events im Wartungsmodus', 45],
            ['hub_area_events_label', 'Events', 'text', 'areas', 'Kachel Events Titel', 50],
            ['hub_area_events_desc', 'Kommende Veranstaltungen, Formate und Community-Termine.', 'textarea', 'areas', 'Kachel Events Beschreibung', 60],
            ['hub_area_events_url', '/events', 'text', 'areas', 'Kachel Events URL', 70],
            ['hub_area_events_icon', 'ti-calendar-event', 'text', 'areas', 'Kachel Events Icon (Tabler)', 80],
            ['hub_area_speakers_visible', '1', 'bool', 'areas', 'Kachel Speaker anzeigen', 90],
            ['hub_area_speakers_maintenance', '0', 'bool', 'areas', 'Kachel Speaker im Wartungsmodus', 95],
            ['hub_area_speakers_label', 'Speaker', 'text', 'areas', 'Kachel Speaker Titel', 100],
            ['hub_area_speakers_desc', 'Menschen mit Bühne, Erfahrung und starken Themen.', 'textarea', 'areas', 'Kachel Speaker Beschreibung', 110],
            ['hub_area_speakers_url', '/event-speakers', 'text', 'areas', 'Kachel Speaker URL', 120],
            ['hub_area_speakers_icon', 'ti-microphone', 'text', 'areas', 'Kachel Speaker Icon (Tabler)', 130],
            ['hub_area_companies_visible', '1', 'bool', 'areas', 'Kachel Firmen anzeigen', 140],
            ['hub_area_companies_maintenance', '0', 'bool', 'areas', 'Kachel Firmen im Wartungsmodus', 145],
            ['hub_area_companies_label', 'Firmen', 'text', 'areas', 'Kachel Firmen Titel', 150],
            ['hub_area_companies_desc', 'Partner, Unternehmen und Organisationen im Netzwerk.', 'textarea', 'areas', 'Kachel Firmen Beschreibung', 160],
            ['hub_area_companies_url', '/firmen', 'text', 'areas', 'Kachel Firmen URL', 170],
            ['hub_area_companies_icon', 'ti-building', 'text', 'areas', 'Kachel Firmen Icon (Tabler)', 180],
            ['hub_area_experts_visible', '1', 'bool', 'areas', 'Kachel Experten anzeigen', 190],
            ['hub_area_experts_maintenance', '0', 'bool', 'areas', 'Kachel Experten im Wartungsmodus', 195],
            ['hub_area_experts_label', 'Experten', 'text', 'areas', 'Kachel Experten Titel', 200],
            ['hub_area_experts_desc', 'Fachleute für Beratung, Projekte und Umsetzung.', 'textarea', 'areas', 'Kachel Experten Beschreibung', 210],
            ['hub_area_experts_url', '/experten', 'text', 'areas', 'Kachel Experten URL', 220],
            ['hub_area_experts_icon', 'ti-users', 'text', 'areas', 'Kachel Experten Icon (Tabler)', 230],
            ['hub_areas_bg_color', '#ffffff', 'color', 'areas', 'Kachel-Hintergrund', 240],
            ['hub_areas_text_color', '#1a2e4a', 'color', 'areas', 'Textfarbe', 250],
            ['hub_areas_accent_color', '#e6a817', 'color', 'areas', 'Akzentfarbe', 260],
            ['hub_areas_radius', '2', 'int', 'areas', 'Rundung (px, max. 2)', 270],
            ['hub_next_events_visible', '1', 'bool', 'next-events', 'Nächste Events anzeigen', 10],
            ['hub_next_events_title', 'Nächste Events', 'text', 'next-events', 'Überschrift', 20],
            ['hub_next_events_sub', 'Die nächsten Termine im Netzwerk.', 'text', 'next-events', 'Beschreibung', 30],
            ['hub_next_events_all_label', 'Alle Events', 'text', 'next-events', 'Alle-Events Link Label', 40],
            ['hub_next_events_all_url', '/events', 'text', 'next-events', 'Alle-Events Link URL', 50],
            ['hub_next_events_limit', '3', 'int', 'next-events', 'Anzahl Events', 60],
            ['hub_event_schema_enabled', '1', 'bool', 'next-events', 'Event-Structured-Data (JSON-LD) ausgeben', 65],
            ['hub_next_events_bg_color', '#ffffff', 'color', 'next-events', 'Karten-Hintergrund', 70],
            ['hub_next_events_text_color', '#1a2e4a', 'color', 'next-events', 'Textfarbe', 80],
            ['hub_next_events_accent_color', '#d6951a', 'color', 'next-events', 'Akzentfarbe', 90],
            ['hub_spotlight_visible', '1', 'bool', 'spotlight', 'Fokus-Rotator anzeigen', 10],
            ['hub_spotlight_title', 'Im Fokus', 'text', 'spotlight', 'Überschrift', 20],
            ['hub_spotlight_sub', 'Rotierend aus Events, Speakern, Experts und Unternehmen.', 'text', 'spotlight', 'Beschreibung', 30],
            ['hub_spotlight_limit', '7', 'int', 'spotlight', 'Maximale Fokus-Einträge', 40],
            ['hub_spotlight_autoplay', '0', 'bool', 'spotlight', 'Automatisch rotieren', 50],
            ['hub_spotlight_freshness_weight', '60', 'int', 'spotlight', 'Ranking: Gewicht Freshness (0-100)', 55],
            ['hub_spotlight_partner_weight', '25', 'int', 'spotlight', 'Ranking: Gewicht Partner-Priorität (0-100)', 56],
            ['hub_spotlight_section_weight', '15', 'int', 'spotlight', 'Ranking: Gewicht Bereichspriorität (0-100)', 57],
            ['hub_spotlight_type_priority', 'event,company,speaker,expert', 'text', 'spotlight', 'Ranking: Bereichsreihenfolge', 58],
            ['hub_spotlight_bg_color', '#ffffff', 'color', 'spotlight', 'Karten-Hintergrund', 60],
            ['hub_spotlight_text_color', '#1a2e4a', 'color', 'spotlight', 'Textfarbe', 70],
            ['hub_spotlight_accent_color', '#d6951a', 'color', 'spotlight', 'Akzentfarbe', 80],
            ['hub_partner_columns_visible', '1', 'bool', 'partner-columns', 'Partner-Spalten anzeigen', 10],
            ['hub_partner_companies_title', 'Unternehmen im Netzwerk', 'text', 'partner-columns', 'Unternehmen Überschrift', 20],
            ['hub_partner_companies_all_label', 'Alle', 'text', 'partner-columns', 'Unternehmen Link Label', 30],
            ['hub_partner_companies_all_url', '/companies', 'text', 'partner-columns', 'Unternehmen Link URL', 40],
            ['hub_partner_companies_limit', '3', 'int', 'partner-columns', 'Anzahl Unternehmen', 50],
            ['hub_partner_experts_title', 'Experts im Netzwerk', 'text', 'partner-columns', 'Experts Überschrift', 60],
            ['hub_partner_experts_all_label', 'Alle', 'text', 'partner-columns', 'Experts Link Label', 70],
            ['hub_partner_experts_all_url', '/experts', 'text', 'partner-columns', 'Experts Link URL', 80],
            ['hub_partner_experts_limit', '3', 'int', 'partner-columns', 'Anzahl Experts', 90],
            ['hub_partner_badge_label', 'Partner', 'text', 'partner-columns', 'Badge-Label', 100],
            ['hub_partner_columns_bg_color', '#ffffff', 'color', 'partner-columns', 'Karten-Hintergrund', 110],
            ['hub_partner_columns_text_color', '#1a2e4a', 'color', 'partner-columns', 'Textfarbe', 120],
            ['hub_partner_columns_accent_color', '#d6951a', 'color', 'partner-columns', 'Akzentfarbe', 130],
            ['hub_toolbox_visible', '1', 'bool', 'toolbox', 'Toolbox-Bereich anzeigen', 10],
            ['hub_toolbox_label', 'M365 Toolbox', 'text', 'toolbox', 'Sektion-Label', 20],
            ['hub_toolbox_title', 'Tools & Ressourcen', 'text', 'toolbox', 'Sektion-Überschrift', 30],
            ['hub_toolbox_all_url', '/m365-tools', 'text', 'toolbox', '"Alle Tools" Link URL', 40],
            ['hub_toolbox_all_label', 'Alle Tools ansehen', 'text', 'toolbox', '"Alle Tools" Link Beschriftung', 50],
            ['hub_toolbox_limit', '12', 'int', 'toolbox', 'Max. angezeigte Tools', 60],
            ['hub_toolbox_layout', 'grid', 'select', 'toolbox', 'Layout', 70],
            ['hub_toolbox_bg_color', '#ffffff', 'color', 'toolbox', 'Kachel-Hintergrund', 80],
            ['hub_toolbox_text_color', '#1a2e4a', 'color', 'toolbox', 'Textfarbe', 90],
            ['hub_toolbox_accent_color', '#e6a817', 'color', 'toolbox', 'Akzentfarbe', 100],
            ['hub_toolbox_radius', '2', 'int', 'toolbox', 'Rundung (px, max. 2)', 110],
            ['hub_posts_visible', '1', 'bool', 'posts', 'Beiträge-Sektion anzeigen', 10],
            ['hub_posts_title', 'Letzte Beiträge', 'text', 'posts', 'Sektion-Überschrift', 20],
            ['hub_posts_sub', 'Aktuelle Artikel und Einblicke aus dem PHINIT Blog.', 'textarea', 'posts', 'Sektion-Beschreibung', 30],
            ['hub_posts_all_label', 'Alle Beiträge', 'text', 'posts', 'Alle-Beiträge Link Label', 40],
            ['hub_posts_all_url', 'https://phinit.de/blog', 'text', 'posts', 'Blog-Übersicht URL', 50],
            ['hub_posts_limit', '6', 'int', 'posts', 'Maximale Beiträge (1–6)', 60],
            ['hub_posts_hero_button_visible', '1', 'bool', 'posts', 'Hero-Blogbutton anzeigen', 70],
            ['hub_posts_hero_button_label', 'Blog Beiträge', 'text', 'posts', 'Hero-Blogbutton Label', 80],
            ['hub_posts_style', 'separated', 'select', 'posts', 'Optische Hervorhebung', 90],
            ['hub_posts_bg_color', '#f7f9fc', 'color', 'posts', 'Hintergrundfarbe für Band/Box', 100],
            ['hub_posts_text_color', '#16202e', 'color', 'posts', 'Textfarbe im Bereich', 110],
            ['hub_posts_accent_color', '#d6951a', 'color', 'posts', 'Akzentfarbe für Hervorhebung', 120],
        ];

        $settings = [];
        foreach ($rows as [$key, $value, $type, $section, $label, $sortOrder]) {
            $settings[$key] = [
                'setting_val' => (string) $value,
                'setting_type' => (string) $type,
                'section' => (string) $section,
                'label' => (string) $label,
                'sort_order' => (int) $sortOrder,
            ];
        }

        return $settings;
    }

    public function get_settings(): array
    {
        if ($this->settingsCache !== null) {
            return $this->settingsCache;
        }

        $defaults = $this->default_settings();
        $rows = [];

        try {
            $db = CMS\Database::instance();
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM {$db->prefix()}network365_settings");
            $stmt->execute([]);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ) ?: [];
        } catch (\Throwable $e) {
            try {
                $this->create_tables();
                $db = CMS\Database::instance();
                $stmt = $db->prepare("SELECT setting_key, setting_value FROM {$db->prefix()}network365_settings");
                $stmt->execute([]);
                $rows = $stmt->fetchAll(\PDO::FETCH_OBJ) ?: [];
            } catch (\Throwable $ignored) {
                return $this->settingsCache = $defaults;
            }
        }

        $settings = [];
        foreach ($rows as $row) {
            $settings[(string) $row->setting_key] = (string) ($row->setting_value ?? '');
        }

        return $this->settingsCache = array_merge($defaults, $settings);
    }

    public function save_settings(array $settings): bool
    {
        return $this->persist_settings($settings, true);
    }

    public function get_hub_settings(): array
    {
        if ($this->hubSettingsCache !== null) {
            return $this->hubSettingsCache;
        }

        $settings = [];

        foreach ($this->default_hub_settings() as $key => $definition) {
            $settings[$key] = $this->cast_hub_value((string) $definition['setting_val'], (string) $definition['setting_type']);
        }

        try {
            $rows = $this->get_hub_setting_rows();
        } catch (\Throwable $e) {
            return $this->hubSettingsCache = $settings;
        }

        foreach ($rows as $row) {
            $key = (string) ($row['setting_key'] ?? '');
            if ($key === '') {
                continue;
            }

            $settings[$key] = $this->cast_hub_value((string) ($row['setting_val'] ?? ''), (string) ($row['setting_type'] ?? 'text'));
        }

        return $this->hubSettingsCache = $settings;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function get_hub_setting_rows(): array
    {
        try {
            $this->seed_default_hub_settings();
            $db = CMS\Database::instance();
            $stmt = $db->prepare("SELECT setting_key, setting_val, setting_type, section, label, sort_order
                FROM {$db->prefix()}network_hub_settings
                ORDER BY section, sort_order, id");
            $stmt->execute([]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $this->create_tables();
            $db = CMS\Database::instance();
            $stmt = $db->prepare("SELECT setting_key, setting_val, setting_type, section, label, sort_order
                FROM {$db->prefix()}network_hub_settings
                ORDER BY section, sort_order, id");
            $stmt->execute([]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        }
    }

    public function save_hub_settings(array $settings): bool
    {
        $this->hubSettingsCache = null;
        $allowed = array_keys($this->default_hub_settings());
        $payload = array_intersect_key($settings, array_flip($allowed));

        return $this->persist_hub_settings($payload, true);
    }

    private function persist_hub_settings(array $payload, bool $retryOnMissingTable): bool
    {
        $this->hubSettingsCache = null;

        try {
            $db = CMS\Database::instance();
            $this->ensure_hub_settings_schema($db->getPdo(), $db->prefix());
            $prefix = $db->prefix();
            $existsStmt = $db->prepare("SELECT id FROM {$prefix}network_hub_settings WHERE setting_key = ? LIMIT 1");
            $updateStmt = $db->prepare("UPDATE {$prefix}network_hub_settings
                SET setting_val = ?, setting_type = ?, section = ?, label = ?, sort_order = ?
                WHERE setting_key = ?");
            $insertStmt = $db->prepare("INSERT INTO {$prefix}network_hub_settings
                (setting_key, setting_val, setting_type, section, label, sort_order)
                VALUES (?, ?, ?, ?, ?, ?)");
            $definitions = $this->default_hub_settings();

            foreach ($payload as $key => $value) {
                $definition = $definitions[$key] ?? null;
                if ($definition === null) {
                    continue;
                }

                $existsStmt->execute([(string) $key]);
                $existingId = $existsStmt->fetchColumn();
                if ($existingId !== false) {
                    $updateStmt->execute([
                        (string) $value,
                        (string) $definition['setting_type'],
                        (string) $definition['section'],
                        (string) $definition['label'],
                        (int) $definition['sort_order'],
                        (string) $key,
                    ]);
                    continue;
                }

                $insertStmt->execute([
                    (string) $key,
                    (string) $value,
                    (string) $definition['setting_type'],
                    (string) $definition['section'],
                    (string) $definition['label'],
                    (int) $definition['sort_order'],
                ]);
            }

            if (!$this->verify_hub_payload($payload)) {
                error_log('CMS 365NETWORK save_hub_settings verification failed');
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            if ($retryOnMissingTable) {
                try {
                    $this->create_tables();
                    return $this->persist_hub_settings($payload, false);
                } catch (\Throwable $nested) {
                    error_log('CMS 365NETWORK save_hub_settings failed: ' . $nested->getMessage());
                    return false;
                }
            }

            error_log('CMS 365NETWORK save_hub_settings failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function verify_hub_payload(array $payload): bool
    {
        if ($payload === []) {
            return true;
        }

        try {
            $db = CMS\Database::instance();
            $stmt = $db->prepare("SELECT setting_val FROM {$db->prefix()}network_hub_settings WHERE setting_key = ? LIMIT 1");

            foreach ($payload as $key => $expectedValue) {
                $stmt->execute([(string) $key]);
                $actualValue = $stmt->fetchColumn();
                if ($actualValue === false || (string) $actualValue !== (string) $expectedValue) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK verify_hub_payload failed: ' . $e->getMessage());
            return false;
        }
    }

    private function persist_settings(array $settings, bool $retryOnMissingTable): bool
    {
        $this->settingsCache = null;
        $allowed = array_keys($this->default_settings());
        $payload = array_intersect_key($settings, array_flip($allowed));
        $payload['settings_version'] = CMS_365NETWORK_VERSION;

        try {
            $db = CMS\Database::instance();
            $stmt = $db->prepare(
                "INSERT INTO {$db->prefix()}network365_settings (setting_key, setting_value)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            );

            foreach ($payload as $key => $value) {
                $stmt->execute([(string) $key, (string) $value]);
            }

            return true;
        } catch (\Throwable $e) {
            if ($retryOnMissingTable) {
                try {
                    $this->create_tables();
                    return $this->persist_settings($payload, false);
                } catch (\Throwable $nested) {
                    error_log('CMS 365NETWORK save_settings failed: ' . $nested->getMessage());
                    return false;
                }
            }

            error_log('CMS 365NETWORK save_settings failed: ' . $e->getMessage());
            return false;
        }
    }

    private function seed_default_settings(): void
    {
        try {
            $db = CMS\Database::instance();
            $stmt = $db->prepare(
                "INSERT IGNORE INTO {$db->prefix()}network365_settings (setting_key, setting_value) VALUES (?, ?)"
            );

            foreach ($this->default_settings() as $key => $value) {
                $stmt->execute([(string) $key, (string) $value]);
            }
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK seed settings failed: ' . $e->getMessage());
        }
    }

    private function seed_default_hub_settings(): void
    {
        try {
            $db = CMS\Database::instance();
            $this->ensure_hub_settings_schema($db->getPdo(), $db->prefix());
            $stmt = $db->prepare(
                "INSERT IGNORE INTO {$db->prefix()}network_hub_settings
                    (setting_key, setting_val, setting_type, section, label, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            foreach ($this->default_hub_settings() as $key => $definition) {
                $stmt->execute([
                    (string) $key,
                    (string) $definition['setting_val'],
                    (string) $definition['setting_type'],
                    (string) $definition['section'],
                    (string) $definition['label'],
                    (int) $definition['sort_order'],
                ]);
            }

            $this->sync_hub_setting_metadata($db->getPdo(), $db->prefix());

            $this->migrate_legacy_hub_defaults();
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK seed hub settings failed: ' . $e->getMessage());
        }
    }

    private function sync_hub_setting_metadata(\PDO $pdo, string $prefix): void
    {
        $table = $prefix . 'network_hub_settings';
        if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
            return;
        }

        $stmt = $pdo->prepare("UPDATE `{$table}`
            SET setting_type = ?, section = ?, label = ?, sort_order = ?
            WHERE setting_key = ?");

        foreach ($this->default_hub_settings() as $key => $definition) {
            $stmt->execute([
                (string) $definition['setting_type'],
                (string) $definition['section'],
                (string) $definition['label'],
                (int) $definition['sort_order'],
                (string) $key,
            ]);
        }
    }

    private function ensure_hub_settings_schema(\PDO $pdo, string $prefix): void
    {
        if ($this->hubSchemaChecked) {
            return;
        }

        $table = $prefix . 'network_hub_settings';
        if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
            return;
        }

        try {
            $stmt = $pdo->prepare('SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
            $stmt->execute([$table, 'setting_type']);
            $columnType = strtolower((string) ($stmt->fetchColumn() ?: ''));

            if ($columnType === '') {
                return;
            }

            if (!str_contains($columnType, "'textarea'")) {
                $pdo->exec("ALTER TABLE `{$table}` MODIFY `setting_type` ENUM('text','textarea','bool','int','color','select') DEFAULT 'text'");
            }

            $this->deduplicate_hub_settings($pdo, $prefix);
            $this->ensure_hub_settings_unique_index($pdo, $prefix);

            $this->hubSchemaChecked = true;
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK hub schema migration failed: ' . $e->getMessage());
        }
    }

    private function deduplicate_hub_settings(\PDO $pdo, string $prefix): void
    {
        $table = $prefix . 'network_hub_settings';
        if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
            return;
        }

        $definitions = $this->default_hub_settings();
        $rows = $pdo->query("SELECT id, setting_key, setting_val FROM `{$table}` ORDER BY setting_key, id")->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $grouped = [];
        foreach ($rows as $row) {
            $key = (string) ($row['setting_key'] ?? '');
            if ($key === '') {
                continue;
            }

            $grouped[$key][] = $row;
        }

        $deleteStmt = $pdo->prepare("DELETE FROM `{$table}` WHERE id = ?");
        foreach ($grouped as $key => $items) {
            if (count($items) <= 1) {
                continue;
            }

            $defaultValue = isset($definitions[$key]) ? (string) ($definitions[$key]['setting_val'] ?? '') : null;
            $keepId = (int) ($items[count($items) - 1]['id'] ?? 0);

            if ($defaultValue !== null) {
                for ($index = count($items) - 1; $index >= 0; $index--) {
                    $itemValue = (string) ($items[$index]['setting_val'] ?? '');
                    if ($itemValue !== $defaultValue) {
                        $keepId = (int) ($items[$index]['id'] ?? $keepId);
                        break;
                    }
                }
            }

            foreach ($items as $item) {
                $id = (int) ($item['id'] ?? 0);
                if ($id > 0 && $id !== $keepId) {
                    $deleteStmt->execute([$id]);
                }
            }
        }
    }

    private function ensure_hub_settings_unique_index(\PDO $pdo, string $prefix): void
    {
        $table = $prefix . 'network_hub_settings';
        if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
            return;
        }

        $stmt = $pdo->prepare('SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND NON_UNIQUE = 0 LIMIT 1');
        $stmt->execute([$table, 'setting_key']);
        if ($stmt->fetchColumn()) {
            return;
        }

        $indexName = 'uniq_setting_key';
        $nameStmt = $pdo->prepare('SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1');
        $nameStmt->execute([$table, $indexName]);
        if ($nameStmt->fetchColumn()) {
            $indexName = 'uniq_n365_hub_setting_key';
        }

        $pdo->exec("ALTER TABLE `{$table}` ADD UNIQUE KEY `{$indexName}` (`setting_key`)");
    }

    private function migrate_legacy_hub_defaults(): void
    {
        try {
            $db = CMS\Database::instance();
            $migrations = [
                ['hub_band_search_url', '/365network/search', ['/suche', 'suche', '/search', 'search']],
                ['hub_toolbox_all_url', '/m365-tools', ['/m365toolbox', 'm365toolbox', '/cms-m365tools', 'cms-m365tools']],
                ['hub_area_card_order', 'events,speakers,experts,companies', ['events,speakers,companies,experts']],
                ['hub_hero_layout', 'image-right', ['left']],
            ];

            foreach ($migrations as [$key, $target, $legacyValues]) {
                $placeholders = implode(', ', array_fill(0, count($legacyValues), '?'));
                $stmt = $db->prepare("UPDATE {$db->prefix()}network_hub_settings
                    SET setting_val = ?
                    WHERE setting_key = ? AND setting_val IN ({$placeholders})");
                $stmt->execute(array_merge([(string) $target, (string) $key], array_map('strval', $legacyValues)));
            }
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK migrate hub defaults failed: ' . $e->getMessage());
        }
    }

    private function cast_hub_value(string $value, string $type): mixed
    {
        return match ($type) {
            'bool' => (bool) (int) trim($value),
            'int' => (int) $value,
            default => $value,
        };
    }

    private function ensure_toolbox_hub_index(\PDO $pdo, string $prefix): void
    {
        foreach ([$prefix . 'm365toolbox_links', 'm365toolbox_links'] as $table) {
            if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
                continue;
            }

            $stmt = $pdo->prepare('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1');
            $stmt->execute([$table]);
            if (!$stmt->fetchColumn()) {
                continue;
            }

            $cols = $pdo->prepare('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME IN (?,?,?)');
            $cols->execute([$table, 'show_on_hub', 'status', 'sort_order']);
            if (count(array_unique(array_map('strval', $cols->fetchAll(\PDO::FETCH_COLUMN) ?: []))) < 3) {
                continue;
            }

            $idx = $pdo->prepare('SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1');
            $idx->execute([$table, 'idx_toolbox_hub']);
            if ($idx->fetchColumn()) {
                return;
            }

            $pdo->exec("ALTER TABLE `{$table}` ADD INDEX idx_toolbox_hub (show_on_hub, status, sort_order)");
            return;
        }
    }
}
