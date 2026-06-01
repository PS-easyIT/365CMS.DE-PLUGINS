<?php
/**
 * CMS M365 Adminsites – Einstellungen.
 *
 * @package CMS_M365ADMINSITES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365ADMINSITES_Settings
{
    /** @var array<string,string>|null */
    private static ?array $cache = null;

    /**
     * @return array<string,string>
     */
    public static function defaults(): array
    {
        return [
            'page_enabled' => '1',
            'page_route' => '/m365-adminsites',
            'page_overline' => 'Microsoft Admin Portale',
            'page_overline_en' => 'Microsoft Admin Portals',
            'page_title' => 'MS365 | Admin Sites & Portale',
            'page_title_en' => 'MS365 | Admin Sites & Portals',
            'page_intro' => 'Kuratierte Übersicht wichtiger Microsoft-365-, Azure-, Security-, Power-Platform-, Lizenz- und Education-Portale für Admins.',
            'page_intro_en' => 'Curated overview of important Microsoft 365, Azure, Security, Power Platform, licensing and education portals for admins.',
            'label_filter_nav' => 'Adminsites filtern',
            'label_filter_nav_en' => 'Filter admin sites',
            'label_category_nav' => 'Kategorien',
            'label_category_nav_en' => 'Categories',
            'label_pagination_nav' => 'Seitennavigation',
            'label_pagination_nav_en' => 'Page navigation',
            'label_all_categories' => 'Alle',
            'label_all_categories_en' => 'All',
            'label_search' => 'Suchbegriff',
            'label_search_en' => 'Search term',
            'label_search_placeholder' => 'z. B. Entra, Defender, Intune, Lizenzierung',
            'label_search_placeholder_en' => 'e.g. Entra, Defender, Intune, Licensing',
            'label_search_button' => 'Suchen',
            'label_search_button_en' => 'Search',
            'label_reset_button' => 'Zurücksetzen',
            'label_reset_button_en' => 'Reset',
            'label_empty_title' => 'Keine Portale gefunden',
            'label_empty_title_en' => 'No portals found',
            'label_empty_body' => 'Bitte Filter anpassen oder die Suche zurücksetzen.',
            'label_empty_body_en' => 'Please adjust filters or reset your search.',
            'label_cards_heading' => 'Portalübersicht',
            'label_cards_heading_en' => 'Portal overview',
            'label_table_heading' => 'Tabellarische Übersicht',
            'label_table_heading_en' => 'Table overview',
            'label_table_image' => 'Bild',
            'label_table_image_en' => 'Image',
            'label_table_title' => 'Portal',
            'label_table_title_en' => 'Portal',
            'label_table_subtitle' => 'Bereich',
            'label_table_subtitle_en' => 'Area',
            'label_table_url' => 'URL',
            'label_table_url_en' => 'URL',
            'label_table_actions' => 'Aktionen',
            'label_table_actions_en' => 'Actions',
            'label_pagination_page' => 'Seite',
            'label_pagination_page_en' => 'Page',
            'label_pagination_of' => 'von',
            'label_pagination_of_en' => 'of',
            'label_pagination_prev' => '← Zurück',
            'label_pagination_prev_en' => '← Previous',
            'label_pagination_next' => 'Weiter →',
            'label_pagination_next_en' => 'Next →',
            'show_category_nav' => '1',
            'show_cards' => '1',
            'show_table' => '1',
            'default_view' => 'cards',
            'items_per_page' => '140',
            'visible_columns' => 'image,title,subtitle,url,actions',
            'show_images' => '1',
            'image_height' => '72',
            'card_image_height' => '118',
            'content_spacing_top' => '25',
            'content_spacing_bottom' => '50',
            'content_padding_y' => '0',
            'content_padding_x' => '24',
            'section_gap' => '16',
            'table_density' => 'comfortable',
            'color_page_background' => '#edf1f6',
            'color_surface' => '#ffffff',
            'color_text' => '#1e293b',
            'color_muted' => '#64748b',
            'color_border' => '#dbe4ef',
            'color_accent' => '#1e3a5f',
            'color_button_bg' => '#1e3a5f',
            'color_button_text' => '#ffffff',
            'border_radius' => '10',
            'external_button_label' => 'Portal öffnen',
            'external_button_label_en' => 'Open portal',
            'sidebar_enabled' => '1',
            'sidebar_title' => 'M365 Adminsites',
            'sidebar_title_en' => 'M365 Admin Sites',
            'sidebar_button_label' => 'Alle Portale ansehen',
            'sidebar_button_label_en' => 'View all portals',
            'sidebar_controls_label' => 'Adminsites steuern',
            'sidebar_controls_label_en' => 'Control admin sites',
            'sidebar_prev_label' => 'Vorheriges Portal anzeigen',
            'sidebar_prev_label_en' => 'Show previous portal',
            'sidebar_next_label' => 'Nächstes Portal anzeigen',
            'sidebar_next_label_en' => 'Show next portal',
            'sidebar_limit' => '8',
            'sidebar_rotate_seconds' => '7',
            'sidebar_min_height' => '196',
            'sidebar_image_height' => '118',
            'sidebar_style' => 'card',
            'sidebar_show_image' => '1',
            'sidebar_show_subtitle' => '1',
            'sidebar_show_category' => '1',
            'sidebar_placeholder_image' => '',
            'feature_ca_shortcuts_enabled' => '0',
            'feature_ca_shortcuts_title' => 'Conditional Access What-If',
            'feature_ca_shortcuts_title_en' => 'Conditional Access What-If',
            'feature_ca_shortcuts_intro' => 'Schnellzugriff auf What-If-Simulationen inklusive häufiger Admin-Szenarien.',
            'feature_ca_shortcuts_intro_en' => 'Quick access to What-If simulations including common admin scenarios.',
            'feature_ca_shortcuts_primary_label' => 'What-If Tool öffnen',
            'feature_ca_shortcuts_primary_label_en' => 'Open What-If tool',
            'feature_ca_shortcuts_identity_label' => 'Preset: Identität',
            'feature_ca_shortcuts_identity_label_en' => 'Preset: Identity',
            'feature_ca_shortcuts_identity_hint' => 'Für Benutzeranmeldung, Rollenwechsel oder MFA-Fragen.',
            'feature_ca_shortcuts_identity_hint_en' => 'For user sign-ins, role changes, or MFA checks.',
            'feature_ca_shortcuts_app_label' => 'Preset: Cloud-App',
            'feature_ca_shortcuts_app_label_en' => 'Preset: Cloud app',
            'feature_ca_shortcuts_app_hint' => 'Für app-spezifische Richtlinien, z. B. Exchange Online oder SharePoint.',
            'feature_ca_shortcuts_app_hint_en' => 'For app-specific policies, e.g. Exchange Online or SharePoint.',
            'feature_ca_shortcuts_platform_label' => 'Preset: Plattform/Gerät',
            'feature_ca_shortcuts_platform_label_en' => 'Preset: Platform/device',
            'feature_ca_shortcuts_platform_hint' => 'Für iOS, Android, Windows und Compliance-abhängige Zugriffe.',
            'feature_ca_shortcuts_platform_hint_en' => 'For iOS, Android, Windows, and compliance-bound access.',
            'feature_message_center_enabled' => '0',
            'feature_message_center_title' => 'Message Center Highlights',
            'feature_message_center_title_en' => 'Message Center Highlights',
            'feature_message_center_intro' => 'Wichtige Hinweise aus dem Message Center als kuratierte Schnellübersicht.',
            'feature_message_center_intro_en' => 'Important Message Center notices as a curated quick overview.',
            'feature_message_center_filter_all' => 'Alle',
            'feature_message_center_filter_all_en' => 'All',
            'feature_message_center_filter_label' => 'Filter',
            'feature_message_center_filter_label_en' => 'Filter',
            'feature_message_center_severity_label' => 'Priorität',
            'feature_message_center_severity_label_en' => 'Priority',
            'feature_message_center_workload_label' => 'Workload',
            'feature_message_center_workload_label_en' => 'Workload',
            'feature_message_center_open_label' => 'Im Message Center öffnen',
            'feature_message_center_open_label_en' => 'Open in Message Center',
            'feature_message_center_empty' => 'Keine Highlights für den gewählten Filter.',
            'feature_message_center_empty_en' => 'No highlights available for the selected filter.',
            'feature_message_center_max_items' => '6',
            'feature_message_center_items' => "high|Exchange Online|Neue Transport-Regelprüfung für externe Weiterleitungen.|https://admin.microsoft.com/Adminportal/Home#/MessageCenter\nmedium|Microsoft Teams|Teams-Client Update-Welle mit neuem Meeting-Layout.|https://admin.microsoft.com/Adminportal/Home#/MessageCenter\nmedium|SharePoint Online|Änderung beim Standard-Linktyp für neue Freigaben.|https://admin.microsoft.com/Adminportal/Home#/MessageCenter\nhigh|Microsoft Entra|Neue MFA-Erzwingung für ausgewählte Admin-Rollen angekündigt.|https://admin.microsoft.com/Adminportal/Home#/MessageCenter",
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $settings = self::defaults();
        if (!class_exists('CMS\\Database')) {
            self::$cache = $settings;
            return self::$cache;
        }

        try {
            $db = \CMS\Database::instance();
            $table = self::quote_identifier(self::table_name($db));
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM {$table}");
            $stmt->execute();
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $key = self::clean_key((string) ($row['setting_key'] ?? ''));
                if ($key === '') {
                    continue;
                }
                $settings[$key] = (string) ($row['setting_value'] ?? '');
            }
        } catch (\Throwable $e) {
            self::log_error('settings load failed: ' . $e->getMessage());
        }

        self::$cache = $settings;
        return self::$cache;
    }

    public static function get(string $key, ?string $default = null): string
    {
        $settings = self::all();
        return (string) ($settings[$key] ?? ($default ?? (self::defaults()[$key] ?? '')));
    }

    /**
     * @param array<string,string> $settings
     */
    public static function save(array $settings): void
    {
        if (!class_exists('CMS\\Database')) {
            return;
        }

        try {
            $allowed = array_keys(self::defaults());
            $db = \CMS\Database::instance();
            $table = self::quote_identifier(self::table_name($db));
            $stmt = $db->prepare("INSERT INTO {$table} (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP");

            foreach ($allowed as $key) {
                if (!array_key_exists($key, $settings)) {
                    continue;
                }
                $stmt->execute([$key, self::limit((string) $settings[$key], 5000)]);
            }

            self::$cache = null;
        } catch (\Throwable $e) {
            self::log_error('settings save failed: ' . $e->getMessage());
        }
    }

    public static function bool(string $key, bool $default = false): bool
    {
        return filter_var(self::get($key, $default ? '1' : '0'), FILTER_VALIDATE_BOOLEAN);
    }

    public static function int(string $key, int $default, int $min, int $max): int
    {
        return max($min, min($max, (int) self::get($key, (string) $default)));
    }

    public static function color(string $key, string $default): string
    {
        $value = trim(self::get($key, $default));
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $default;
    }

    public static function route(): string
    {
        $route = '/' . trim(self::get('page_route', '/m365-adminsites'), '/');
        if ($route === '/' || preg_match('#^/[a-z0-9/_-]+$#i', $route) !== 1) {
            return '/m365-adminsites';
        }

        return $route;
    }

    public static function table_name(\CMS\Database $db): string
    {
        $prefix = method_exists($db, 'getPrefix') ? $db->getPrefix() : (method_exists($db, 'prefix') ? $db->prefix() : 'cms_');
        return $prefix . 'm365adminsites_settings';
    }

    public static function quote_identifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private static function clean_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_\-]+/i', '_', strtolower($value)), '_');
    }

    private static function limit(string $value, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }

    private static function log_error(string $message): void
    {
        error_log('CMS M365 Adminsites settings: ' . $message);
    }
}
