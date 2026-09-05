<?php
/**
 * CMS M365 Copilot – Settings storage and sanitizing.
 *
 * @package CMS_M365Copilot
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365Copilot_Settings
{
    /** @var array<string,string>|null */
    private static ?array $cache = null;

    /** @return array<string,string> */
    public static function defaults(): array
    {
        return [
            'route_slug' => 'microsoft-365-copilot',
            'header_image_url' => '',
            'header_image_alt' => 'Microsoft 365 Copilot Beratung',
            'header_title' => 'Microsoft 365 Copilot produktiv einführen',
            'header_intro' => 'Strategie, Lizenzierung, Datenschutz und Enablement für Unternehmen, die Copilot sicher und wirtschaftlich nutzen möchten.',
            'header_cta_label' => 'Copilot Beratung anfragen',
            'header_cta_url' => '/kontakt',
            'header_layout' => '1',
            'cards_section_aria_label' => 'Copilot Bereiche',
            'service_enabled' => '1',
            'service_section_label' => '365CMS Service',
            'service_section_aria_label' => 'Dienstleistungsband',
            'service_text' => 'Wir unterstützen bei Copilot Readiness, Lizenzwahl, Governance, Datenschutz und Einführung in Fachbereichen.',
            'service_logo_url' => '',
            'service_logo_alt' => 'Beratungspartner',
            'service_cta_label' => 'Kontakt aufnehmen',
            'service_cta_url' => '/kontakt',
            'service_layout' => '1',
            'card_1_title' => 'Copilot Lizenz-Seite',
            'card_1_text' => 'Lizenzoptionen, Voraussetzungen und passende Kaufpfade für Microsoft 365 Copilot.',
            'card_1_image_url' => '',
            'card_1_image_alt' => 'Lizenzübersicht',
            'card_1_url' => '/m365-lizenzberater',
            'card_2_title' => 'Copilot Informationen',
            'card_2_text' => 'Einordnung, Nutzen, Einführungsszenarien und Best Practices für Entscheider.',
            'card_2_image_url' => '',
            'card_2_image_alt' => 'Copilot Informationen',
            'card_2_url' => '/copilot-lizenz-check',
            'card_3_title' => 'Copilot Datenschutz & Sicherheit',
            'card_3_text' => 'Governance, Berechtigungen, Datenzugriff und Compliance vor dem Rollout prüfen.',
            'card_3_image_url' => '',
            'card_3_image_alt' => 'Datenschutz und Sicherheit',
            'card_3_url' => '/m365-lizenz-audit-checkliste',
            'cards_link_label' => 'Mehr erfahren →',
            'service_cards_show' => '1',
            'service_cards_aria_label' => 'Dienstleistungsinformationen',
            'service_cards_section_label' => 'Dienstleistungen',
            'service_cards_title' => 'Unsere Dienstleistungsbausteine',
            'service_cards_intro' => 'Diese Leistungen begleiten euch von Readiness über Umsetzung bis Governance.',
            'service_info_1_title' => 'Readiness & Assessment',
            'service_info_1_text' => 'Wir analysieren Ausgangslage, Lizenzen, Datenzugriffe und organisatorische Voraussetzungen für Copilot.',
            'service_info_2_title' => 'Einführung & Enablement',
            'service_info_2_text' => 'Wir begleiten Pilotgruppen, definieren Use-Cases und schulen Fachbereiche für produktiven Alltagseinsatz.',
            'service_info_3_title' => 'Governance & Betrieb',
            'service_info_3_text' => 'Wir etablieren Rollen, Prozesse und Leitplanken für Datenschutz, Sicherheit und nachhaltigen Betrieb.',
            'posts_show' => '1',
            'posts_section_label' => 'Aktuelle Beiträge',
            'posts_title' => 'Aktuelles zu Microsoft Copilot',
            'posts_intro' => 'Neueste Beiträge, Einordnungen und Praxisnotizen aus der Kategorie Microsoft Copilot.',
            'posts_category' => 'Microsoft Copilot',
            'posts_count' => '3',
            'posts_readmore_label' => 'Weiter lesen →',
            'posts_read_aria_prefix' => 'Beitrag lesen:',
            'posts_empty_title' => 'Keine Beiträge gefunden',
            'posts_empty_text' => 'Bitte Kategorie oder Beitragsanzahl in den Einstellungen prüfen.',
            'layout_content_max_width' => '1200',
            'layout_padding_left' => '24',
            'layout_padding_right' => '24',
            'layout_padding_top' => '32',
            'layout_padding_bottom' => '48',
            'layout_section_gap' => '36',
            'layout_header_offset' => '0',
            'layout_footer_offset' => '0',
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
            error_log('CMS M365 Copilot settings load failed: ' . $e->getMessage());
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

        CMS_M365Copilot_Installer::ensure_for_admin_save();
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
            if (!array_key_exists($key, $posted)) {
                continue;
            }

            $raw = (string) ($posted[$key] ?? $default);
            if (in_array($key, ['header_layout', 'service_layout'], true)) {
                $settings[$key] = in_array($raw, ['1', '2', '3'], true) ? $raw : $default;
                continue;
            }
            if (in_array($key, ['service_enabled', 'service_cards_show', 'posts_show'], true)) {
                $settings[$key] = !empty($posted[$key]) ? '1' : '0';
                continue;
            }
            if ($key === 'route_slug') {
                $settings[$key] = self::slug($raw, $default);
                continue;
            }
            if (str_ends_with($key, '_url')) {
                $settings[$key] = str_contains($key, 'image_url') || str_contains($key, 'logo_url') ? self::public_image_url($raw) : self::public_url($raw);
                continue;
            }
            if (str_starts_with($key, 'layout_')) {
                $settings[$key] = (string) self::number($raw, self::number_default($key), self::number_min($key), self::number_max($key));
                continue;
            }
            if ($key === 'posts_count') {
                $settings[$key] = (string) self::number($raw, 3, 1, 12);
                continue;
            }
            $settings[$key] = self::limit(self::long_text($raw), self::text_limit($key));
        }

        return $settings;
    }

    /** @return array<int,array<string,mixed>> */
    public static function post_categories(): array
    {
        if (!class_exists('CMS\\Database')) {
            return [];
        }

        try {
            $db = \CMS\Database::instance();
            $prefix = self::prefix($db);
            $stmt = $db->prepare("SELECT id, name, slug, parent_id FROM {$prefix}post_categories ORDER BY COALESCE(parent_id, 0), name ASC");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('CMS M365 Copilot categories load failed: ' . $e->getMessage());
            return [];
        }
    }

    public static function public_url(string $value): string
    {
        $value = trim(strip_tags($value));
        if ($value === '') {
            return '';
        }
        if ((str_starts_with($value, '/') || str_starts_with($value, '#')) && !str_starts_with($value, '//') && preg_match('#^[A-Za-z0-9/_?&=.%\#+:;,@~\-]*$#', $value) === 1) {
            return $value;
        }
        if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
            $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
            return in_array($scheme, ['http', 'https'], true) ? $value : '';
        }
        return '';
    }

    public static function public_image_url(string $value): string
    {
        $value = trim(str_replace('\\', '/', strip_tags($value)));
        $value = (string) preg_replace('/[\x00-\x1F\x7F]+/u', '', $value);
        if ($value === '') {
            return '';
        }
        if (preg_match('#^media-file(?:\?|$)#i', $value) === 1 || preg_match('#^(?:uploads|images/importer|importer|media)(?:/|$)#i', $value) === 1) {
            $value = '/' . ltrim($value, '/');
        }
        $value = str_replace(' ', '%20', $value);
        if (str_starts_with($value, '/') && !str_starts_with($value, '//')) {
            $path = rawurldecode((string) (parse_url($value, PHP_URL_PATH) ?: $value));
            return str_contains($path, '..') || preg_match('#[<>`"\']#', $path) === 1 ? '' : $value;
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

    public static function slug(string $value, string $fallback): string
    {
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strip_tags($value)), '-'));
        return $slug !== '' ? $slug : $fallback;
    }

    private static function table_name(\CMS\Database $db): string
    {
        return self::prefix($db) . 'm365copilot_settings';
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

    private static function number(string $value, int $default, int $min, int $max): int
    {
        $number = trim($value) !== '' ? (int) $value : $default;
        return max($min, min($max, $number));
    }

    private static function number_default(string $key): int
    {
        return (int) (self::defaults()[$key] ?? 0);
    }

    private static function number_min(string $key): int
    {
        return match ($key) {
            'layout_content_max_width' => 720,
            default => 0,
        };
    }

    private static function number_max(string $key): int
    {
        return match ($key) {
            'layout_content_max_width' => 1800,
            'layout_padding_top', 'layout_padding_bottom', 'layout_section_gap', 'layout_header_offset', 'layout_footer_offset' => 160,
            default => 96,
        };
    }

    private static function text_limit(string $key): int
    {
        return str_contains($key, '_intro') || str_contains($key, '_text') ? 600 : 255;
    }

    private static function limit(string $value, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }
}
