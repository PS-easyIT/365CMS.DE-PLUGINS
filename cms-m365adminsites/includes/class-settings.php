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
            'page_title' => 'MS365 | Admin Sites & Portale',
            'page_intro' => 'Kuratierte Übersicht wichtiger Microsoft-365-, Azure-, Security-, Power-Platform-, Lizenz- und Education-Portale für Admins.',
            'label_filter_nav' => 'Adminsites filtern',
            'label_category_nav' => 'Kategorien',
            'label_pagination_nav' => 'Seitennavigation',
            'label_all_categories' => 'Alle',
            'label_search' => 'Suchbegriff',
            'label_search_placeholder' => 'z. B. Entra, Defender, Intune, Lizenzierung',
            'label_search_button' => 'Suchen',
            'label_reset_button' => 'Zurücksetzen',
            'label_empty_title' => 'Keine Portale gefunden',
            'label_empty_body' => 'Bitte Filter anpassen oder die Suche zurücksetzen.',
            'label_cards_heading' => 'Portalübersicht',
            'label_table_heading' => 'Tabellarische Übersicht',
            'label_table_image' => 'Bild',
            'label_table_title' => 'Portal',
            'label_table_subtitle' => 'Bereich',
            'label_table_url' => 'URL',
            'label_table_actions' => 'Aktionen',
            'label_pagination_page' => 'Seite',
            'label_pagination_of' => 'von',
            'label_pagination_prev' => '← Zurück',
            'label_pagination_next' => 'Weiter →',
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
            'sidebar_enabled' => '1',
            'sidebar_title' => 'M365 Adminsites',
            'sidebar_button_label' => 'Alle Portale ansehen',
            'sidebar_controls_label' => 'Adminsites steuern',
            'sidebar_prev_label' => 'Vorheriges Portal anzeigen',
            'sidebar_next_label' => 'Nächstes Portal anzeigen',
            'sidebar_limit' => '8',
            'sidebar_rotate_seconds' => '7',
            'sidebar_min_height' => '196',
            'sidebar_image_height' => '118',
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
        $table = self::quote_identifier(self::table_name($db));
        $stmt = $db->prepare("INSERT INTO {$table} (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP");

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
        $route = '/' . trim(self::get('page_route', '/m365-adminsites'), '/');
        return $route === '/' ? '/m365-adminsites' : $route;
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
}
