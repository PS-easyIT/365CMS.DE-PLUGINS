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
            'page_title' => 'MS365 | SITES & BLOGS',
            'page_intro' => 'Kuratierte Sammlung deutschsprachiger Microsoft-365-MVPs, Community-Sites, Newsquellen und Open-Source-Tools.',
            'meta_updated' => '15.05.2026',
            'meta_author' => 'masterPhin',
            'meta_read_time' => '5 Min.',
            'show_header_meta' => '1',
            'show_category_nav' => '1',
            'show_cards' => '1',
            'show_table' => '1',
            'default_view' => 'cards',
            'items_per_page' => '120',
            'visible_columns' => 'image,title,subtitle,url,description,actions',
            'show_descriptions' => '1',
            'show_images' => '1',
            'image_height' => '96',
            'card_image_height' => '132',
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
            'expert_button_label' => 'Expert-Profil',
            'show_company_buttons' => '1',
            'show_expert_buttons' => '1',
            'external_button_label' => 'Site öffnen',
            'sidebar_enabled' => '1',
            'sidebar_title' => 'M365 Sites & Blogs',
            'sidebar_limit' => '8',
            'sidebar_rotate_seconds' => '7',
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
        return $route === '/' ? '/m365-sites-blogs' : $route;
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
