<?php
/**
 * CMS M365 Linkcollection – Einstellungen.
 *
 * @package CMS_M365LINKCOLLECTION
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365LINKCOLLECTION_Settings
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
            'page_route' => '/m365-sites-blogs',
            'page_overline' => 'MS365 Linkverzeichnis',
            'page_overline_en' => 'MS365 Link Directory',
            'page_title' => 'MS365 | SITES & BLOGS',
            'page_title_en' => 'MS365 | SITES & BLOGS',
            'page_intro' => 'Kuratierte Sammlung deutschsprachiger Microsoft-365-MVPs, Community-Sites, Newsquellen und Open-Source-Tools.',
            'page_intro_en' => 'Curated collection of Microsoft 365 MVP resources, community sites, news sources, and open source tools.',
            'label_filter_nav' => 'Linkcollection filtern',
            'label_filter_nav_en' => 'Filter link collection',
            'label_category_nav' => 'Kategorien',
            'label_category_nav_en' => 'Categories',
            'label_pagination_nav' => 'Seitennavigation',
            'label_pagination_nav_en' => 'Pagination',
            'label_all_categories' => 'Alle',
            'label_all_categories_en' => 'All',
            'label_search' => 'Suchbegriff',
            'label_search_en' => 'Search term',
            'label_search_placeholder' => 'z. B. Intune, MVP, Security',
            'label_search_placeholder_en' => 'e.g. Intune, MVP, Security',
            'label_search_button' => 'Suchen',
            'label_search_button_en' => 'Search',
            'label_reset_button' => 'Zurücksetzen',
            'label_reset_button_en' => 'Reset',
            'label_empty_title' => 'Keine Links gefunden',
            'label_empty_title_en' => 'No links found',
            'label_empty_body' => 'Bitte Filter anpassen oder die Suche zurücksetzen.',
            'label_empty_body_en' => 'Adjust filters or reset your search.',
            'label_cards_heading' => 'Linkübersicht',
            'label_cards_heading_en' => 'Link overview',
            'label_table_heading' => 'Tabellarische Übersicht',
            'label_table_heading_en' => 'Table overview',
            'label_table_image' => 'Bild',
            'label_table_image_en' => 'Image',
            'label_table_title' => 'Name',
            'label_table_title_en' => 'Name',
            'label_table_subtitle' => 'Schwerpunkt',
            'label_table_subtitle_en' => 'Focus',
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
            'label_results_count' => '%d Links gefunden',
            'label_results_count_en' => '%d links found',
            'show_category_nav' => '1',
            'show_cards' => '1',
            'show_table' => '1',
            'show_structured_data' => '0',
            'accessibility_validation_mode' => '0',
            'default_view' => 'cards',
            'items_per_page' => '120',
            'visible_columns' => 'image,title,subtitle,url,actions',
            'show_images' => '1',
            'image_height' => '96',
            'card_image_height' => '132',
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
            'company_button_label' => 'Company ansehen',
            'company_button_label_en' => 'View company',
            'speaker_button_label' => 'Speaker-Profil',
            'speaker_button_label_en' => 'Speaker profile',
            'expert_button_label' => 'Expert-Profil',
            'expert_button_label_en' => 'Expert profile',
            'show_company_buttons' => '1',
            'show_speaker_buttons' => '1',
            'show_expert_buttons' => '1',
            'external_button_label' => 'Site öffnen',
            'external_button_label_en' => 'Open site',
            'sidebar_enabled' => '1',
            'sidebar_title' => 'M365 Sites & Blogs',
            'sidebar_title_en' => 'M365 Sites & Blogs',
            'sidebar_button_label' => 'Alle Links ansehen',
            'sidebar_button_label_en' => 'View all links',
            'sidebar_controls_label' => 'Linkcollection steuern',
            'sidebar_controls_label_en' => 'Control link collection',
            'sidebar_prev_label' => 'Vorherigen Link anzeigen',
            'sidebar_prev_label_en' => 'Show previous link',
            'sidebar_next_label' => 'Nächsten Link anzeigen',
            'sidebar_next_label_en' => 'Show next link',
            'sidebar_limit' => '8',
            'sidebar_rotate_seconds' => '7',
            'sidebar_min_height' => '208',
            'sidebar_image_height' => '132',
            'sidebar_style' => 'card',
            'sidebar_show_image' => '1',
            'sidebar_show_subtitle' => '1',
            'sidebar_show_category' => '1',
            'sidebar_placeholder_image' => '',
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
            $table = self::table_name($db);
            $quotedTable = self::quote_identifier($table);
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM {$quotedTable}");
            $stmt->execute();
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $key = self::clean_key((string) ($row['setting_key'] ?? ''));
                if ($key === '') {
                    continue;
                }
                $settings[$key] = (string) ($row['setting_value'] ?? '');
            }
        } catch (\Throwable $e) {
            // Defaults sind ausreichend, wenn die Tabelle noch nicht existiert.
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

        $allowed = array_keys(self::defaults());
        $db = \CMS\Database::instance();
        $table = self::table_name($db);
        $quotedTable = self::quote_identifier($table);
        $stmt = $db->prepare("INSERT INTO {$quotedTable} (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP");

        foreach ($allowed as $key) {
            if (!array_key_exists($key, $settings)) {
                continue;
            }
            $stmt->execute([$key, self::limit((string) $settings[$key], 5000)]);
        }

        self::$cache = null;
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
        $route = '/' . trim(self::get('page_route', '/m365-sites-blogs'), '/');
        $route = preg_replace('#/+#', '/', $route);
        if (!is_string($route) || preg_match('#^/[a-z0-9/_-]{1,200}$#i', $route) !== 1) {
            return '/m365-sites-blogs';
        }

        return $route;
    }

    public static function table_name(\CMS\Database $db): string
    {
        $prefix = method_exists($db, 'getPrefix') ? $db->getPrefix() : (method_exists($db, 'prefix') ? $db->prefix() : 'cms_');
        return $prefix . 'm365linkcollection_settings';
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
}
