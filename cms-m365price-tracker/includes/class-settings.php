<?php
/**
 * CMS M365 Price Tracker – Admin-configurable settings.
 *
 * @package CMS_M365PRICETRACKER
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365PRICETRACKER_Settings
{
    /** @var array<string,string>|null */
    private static ?array $cache = null;

    /** @return array<string,string> */
    public static function defaults(): array
    {
        return [
            'content_mode' => 'full',
            'layout_variant' => 'sidebar',
            'content_max_width' => '1220',
            'content_padding_x' => '24',
            'content_padding_top' => '25',
            'content_padding_bottom' => '25',
            'theme_header_gap' => '25',
            'theme_footer_gap' => '25',
            'section_gap' => '24',
            'card_gap' => '18',
            'card_radius' => '16',
            'card_padding' => '24',
            'chart_height' => '320',
            'filter_columns' => '2',
            'primary_color' => '#2563eb',
            'accent_color' => '#0ea5e9',
            'surface_color' => '#ffffff',
            'muted_surface_color' => '#f8fafc',
            'text_color' => '#0f172a',
            'muted_text_color' => '#64748b',
            'border_color' => '#e2e8f0',
            'show_hero' => '1',
            'show_hero_cta' => '1',
            'show_price_history' => '1',
            'show_history_selector' => '1',
            'show_filter_form' => '1',
            'show_filter_fieldset' => '1',
            'show_inventory_fieldset' => '1',
            'show_forecast_fieldset' => '1',
            'show_result_aside' => '1',
            'show_personal_tracker' => '1',
            'show_summary_cards' => '1',
            'show_year_chart' => '1',
            'show_impact_cards' => '1',
            'show_sku_table' => '1',
            'show_events_table' => '1',
            'show_next_steps' => '1',
            'show_info_card' => '1',
            'show_link_card' => '1',
            'hero_title' => 'Microsoft-Preiserhöhung-Tracker',
            'hero_intro' => 'Bewertet Microsoft-Preisereignisse, Renewal-Fenster und Budgetwirkung für deinen Lizenzbestand.',
            'hero_cta_label' => 'Budget prüfen lassen',
            'hero_cta_url' => '/kontakt',
            'info_card_title' => 'Quellenstand',
            'info_card_text' => 'Quellenprüfung, Währungshinweise und offizielle Microsoft-Links werden transparent ausgewiesen.',
            'link_card_title' => 'Budget- und Lizenzprüfung',
            'link_card_text' => 'Wir prüfen Bestand, Renewal-Fenster und Einsparpotenziale auf Basis der aktuellen Microsoft-Preislogik.',
            'link_card_label' => 'Beratung anfragen',
            'link_card_url' => '/kontakt',
        ];
    }

    /** @return array<string,string> */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return array_merge(self::defaults(), self::$cache);
        }

        if (!class_exists('CMS\\Database')) {
            return self::defaults();
        }

        try {
            $db = \CMS\Database::instance();
            $table = self::validated_table_name(self::table_name($db));
            $stmt = $db->prepare('SELECT setting_key, setting_value FROM ' . self::quote_identifier($table));
            $stmt->execute();
            $stored = [];
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $key = self::clean_key((string) ($row['setting_key'] ?? ''));
                if ($key !== '') {
                    $stored[$key] = (string) ($row['setting_value'] ?? '');
                }
            }
            self::$cache = $stored;
        } catch (\Throwable $e) {
            error_log('CMS M365 Price Tracker settings load failed: ' . $e->getMessage());
            self::$cache = [];
        }

        return array_merge(self::defaults(), self::$cache);
    }

    /** @param array<string,string> $settings */
    public static function save(array $settings): void
    {
        if (!class_exists('CMS\\Database')) {
            return;
        }

        CMS_M365PRICETRACKER_Installer::ensure_for_admin_save();
        $db = \CMS\Database::instance();
        $table = self::validated_table_name(self::table_name($db));
        $quotedTable = self::quote_identifier($table);
        $exists = $db->prepare("SELECT id FROM {$quotedTable} WHERE setting_key = ? LIMIT 1");
        $insert = $db->prepare("INSERT INTO {$quotedTable} (setting_key, setting_value) VALUES (?, ?)");
        $update = $db->prepare("UPDATE {$quotedTable} SET setting_value = ? WHERE setting_key = ?");

        foreach ($settings as $key => $value) {
            $cleanKey = self::clean_key($key);
            if ($cleanKey === '' || !array_key_exists($cleanKey, self::defaults())) {
                continue;
            }

            $exists->execute([$cleanKey]);
            if ($exists->fetch()) {
                $update->execute([$value, $cleanKey]);
            } else {
                $insert->execute([$cleanKey, $value]);
            }
        }

        self::$cache = null;
    }

    /** @param array<string,mixed> $posted @return array<string,string> */
    public static function sanitize_from_post(array $posted): array
    {
        $defaults = self::defaults();
        $settings = array_merge($defaults, self::all());
        foreach ($defaults as $key => $default) {
            if (self::is_toggle($key)) {
                if (!array_key_exists($key, $posted)) {
                    continue;
                }
                $settings[$key] = !empty($posted[$key]) ? '1' : '0';
                continue;
            }

            if (!array_key_exists($key, $posted)) {
                continue;
            }

            $raw = (string) ($posted[$key] ?? $default);
            if ($key === 'content_mode') {
                $settings[$key] = in_array($raw, ['full', 'compact', 'chart_only', 'custom'], true) ? $raw : $default;
                continue;
            }
            if ($key === 'layout_variant') {
                $settings[$key] = in_array($raw, ['sidebar', 'single', 'wide'], true) ? $raw : $default;
                continue;
            }
            if ($key === 'filter_columns') {
                $settings[$key] = in_array($raw, ['1', '2', '3'], true) ? $raw : $default;
                continue;
            }
            if (str_ends_with($key, '_color')) {
                $textRaw = (string) ($posted[$key . '_text'] ?? '');
                $settings[$key] = self::color($textRaw !== '' ? $textRaw : $raw, $default);
                continue;
            }
            if (str_ends_with($key, '_url')) {
                $settings[$key] = self::public_url($raw);
                continue;
            }
            if (self::is_number($key)) {
                $settings[$key] = (string) self::number($raw, (int) $default, self::number_min($key), self::number_max($key));
                continue;
            }

            $settings[$key] = self::limit(self::long_text($raw), self::text_limit($key));
        }

        return $settings;
    }

    /** @return array<string,string> */
    public static function css_vars(): array
    {
        $settings = self::all();
        return [
            '--m365price-content-max-width' => self::px($settings['content_max_width']),
            '--m365price-content-padding-x' => self::px($settings['content_padding_x']),
            '--m365price-content-padding-top' => self::px(min(25, (int) $settings['content_padding_top'])),
            '--m365price-content-padding-bottom' => self::px(min(25, (int) $settings['content_padding_bottom'])),
            '--m365price-theme-header-gap' => self::px(min(25, (int) $settings['theme_header_gap'])),
            '--m365price-theme-footer-gap' => self::px(min(25, (int) $settings['theme_footer_gap'])),
            '--m365price-section-gap' => self::px($settings['section_gap']),
            '--m365price-card-gap' => self::px($settings['card_gap']),
            '--m365price-card-radius' => self::px($settings['card_radius']),
            '--m365price-card-padding' => self::px($settings['card_padding']),
            '--m365price-chart-height' => self::px($settings['chart_height']),
            '--m365price-primary-color' => $settings['primary_color'],
            '--m365price-accent-color' => $settings['accent_color'],
            '--m365price-surface-color' => $settings['surface_color'],
            '--m365price-muted-surface-color' => $settings['muted_surface_color'],
            '--m365price-text-color' => $settings['text_color'],
            '--m365price-muted-text-color' => $settings['muted_text_color'],
            '--m365price-border-color' => $settings['border_color'],
        ];
    }

    public static function public_url(string $value): string
    {
        $value = trim(strip_tags($value));
        if ($value === '') {
            return '';
        }
        if ((str_starts_with($value, '/') || str_starts_with($value, '#')) && !str_starts_with($value, '//') && preg_match('#^[A-Za-z0-9/_?&=.%#+:;,@~\-]*$#', $value) === 1) {
            return $value;
        }
        if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
            $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
            return in_array($scheme, ['http', 'https'], true) ? $value : '';
        }

        return '';
    }

    public static function long_text(string $value): string
    {
        return trim(strip_tags(str_replace(["\r\n", "\r"], "\n", $value)));
    }

    private static function table_name(\CMS\Database $db): string
    {
        return self::prefix($db) . 'm365price_tracker_settings';
    }

    private static function prefix(\CMS\Database $db): string
    {
        return method_exists($db, 'getPrefix') ? $db->getPrefix() : (method_exists($db, 'prefix') ? $db->prefix() : 'cms_');
    }

    private static function clean_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_]+/i', '_', strtolower($value)), '_');
    }

    private static function quote_identifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private static function validated_table_name(string $table): string
    {
        if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
            throw new \RuntimeException('Invalid table identifier configured.');
        }

        return $table;
    }

    private static function color(string $value, string $fallback): string
    {
        $value = trim($value);
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? strtolower($value) : $fallback;
    }

    private static function number(string $value, int $default, int $min, int $max): int
    {
        $number = trim($value) !== '' ? (int) $value : $default;
        return max($min, min($max, $number));
    }

    private static function number_min(string $key): int
    {
        return match ($key) {
            'content_max_width' => 720,
            'chart_height' => 220,
            default => 0,
        };
    }

    private static function number_max(string $key): int
    {
        return match ($key) {
            'content_max_width' => 1800,
            'chart_height' => 620,
            'theme_header_gap', 'theme_footer_gap', 'content_padding_top', 'content_padding_bottom' => 25,
            'section_gap', 'card_gap', 'card_padding' => 96,
            'card_radius' => 32,
            default => 120,
        };
    }

    private static function is_number(string $key): bool
    {
        return in_array($key, [
            'content_max_width',
            'content_padding_x',
            'content_padding_top',
            'content_padding_bottom',
            'theme_header_gap',
            'theme_footer_gap',
            'section_gap',
            'card_gap',
            'card_radius',
            'card_padding',
            'chart_height',
        ], true);
    }

    private static function is_toggle(string $key): bool
    {
        return str_starts_with($key, 'show_');
    }

    private static function text_limit(string $key): int
    {
        return str_contains($key, '_intro') || str_contains($key, '_text') ? 600 : 160;
    }

    private static function limit(string $value, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }

    private static function px(string|int $value): string
    {
        return max(0, (int) $value) . 'px';
    }
}
