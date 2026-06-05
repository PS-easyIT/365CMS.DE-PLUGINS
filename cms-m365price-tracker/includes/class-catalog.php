<?php
/**
 * CMS M365 Price Tracker – JSON-Kataloge.
 *
 * @package CMS_M365PRICETRACKER
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365PRICETRACKER_Catalog
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public static function microsoft_price_skus(): array
    {
        $catalog = self::load_json('package_price_catalog.json');
        $skus = is_array($catalog['skus'] ?? null) ? $catalog['skus'] : [];
        $result = [];

        foreach ($skus as $sku) {
            if (!is_array($sku)) {
                continue;
            }

            $slug = self::clean_key((string) ($sku['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $sku['slug'] = $slug;
            $sku['is_active'] = (int) ($sku['is_active'] ?? 1);
            $sku['price_history'] = self::normalize_sku_price_history($sku['price_history'] ?? []);
            $result[] = $sku;
        }

        usort($result, static function (array $left, array $right): int {
            $leftOrder = (int) ($left['sort_order'] ?? 9999);
            $rightOrder = (int) ($right['sort_order'] ?? 9999);
            if ($leftOrder !== $rightOrder) {
                return $leftOrder <=> $rightOrder;
            }

            return strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
        });

        return $result;
    }

    /**
     * @return array<string,mixed>
     */
    public static function microsoft_price_events(): array
    {
        return self::load_json('microsoft_price_events.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function microsoft_price_changes(): array
    {
        return self::load_json('microsoft_price_changes.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function microsoft_inventory_mapping(): array
    {
        return self::load_json('microsoft_inventory_mapping.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function microsoft_price_forecast_rules(): array
    {
        return self::load_json('microsoft_price_forecast_rules.json');
    }

    /**
     * @return array<string,mixed>
     */
    private static function load_json(string $file): array
    {
        static $cache = [];

        $file = basename($file);
        if ($file === '') {
            return [];
        }

        if (isset($cache[$file])) {
            return $cache[$file];
        }

        $paths = [
            CMS_M365PRICETRACKER_PLUGIN_DIR . 'data/' . $file,
            dirname(CMS_M365PRICETRACKER_PLUGIN_DIR) . '/cms-m365tools/data/' . $file,
        ];

        foreach ($paths as $path) {
            if (!is_file($path) || !is_readable($path)) {
                continue;
            }

            $size = filesize($path);
            if ($size === false || $size > 2_097_152) {
                error_log('CMS M365 Price Tracker rejected catalog file: ' . $file);
                continue;
            }

            $json = file_get_contents($path);
            if (!is_string($json) || trim($json) === '') {
                continue;
            }

            $decoded = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('CMS M365 Price Tracker invalid JSON in catalog file ' . $file . ': ' . json_last_error_msg());
                continue;
            }

            $cache[$file] = is_array($decoded) ? $decoded : [];

            return $cache[$file];
        }

        $cache[$file] = [];

        return $cache[$file];
    }

    /**
     * @param mixed $history
     * @return array<int,array<string,mixed>>
     */
    private static function normalize_sku_price_history(mixed $history): array
    {
        if (!is_array($history)) {
            return [];
        }

        $normalized = [];
        foreach ($history as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $entry['prices_eur_net'] = self::normalize_sku_prices($entry['prices_eur_net'] ?? []);
            $normalized[] = $entry;
        }

        usort($normalized, static fn(array $left, array $right): int => strcmp((string) ($left['effective_from'] ?? ''), (string) ($right['effective_from'] ?? '')));

        return $normalized;
    }

    /**
     * @param mixed $prices
     * @return array<string,float|null>
     */
    private static function normalize_sku_prices(mixed $prices): array
    {
        $prices = is_array($prices) ? $prices : [];
        $annual = self::first_numeric_price($prices, ['annual_annual_permonth', 'annual_monthly']);
        $monthly = is_numeric($prices['monthly_monthly'] ?? null) ? (float) $prices['monthly_monthly'] : ($annual !== null ? round($annual * 1.2, 2) : null);
        $annualTotal = is_numeric($prices['annual_annual_total'] ?? null) ? (float) $prices['annual_annual_total'] : ($annual !== null ? round($annual * 12, 2) : null);
        $triennial = is_numeric($prices['triennial_permonth'] ?? null) ? (float) $prices['triennial_permonth'] : ($annual !== null ? round($annual * 0.95, 2) : null);

        return [
            'monthly_monthly' => $monthly,
            'annual_monthly' => $annual,
            'annual_annual_permonth' => $annual,
            'annual_annual_total' => $annualTotal,
            'triennial_permonth' => $triennial,
        ];
    }

    /**
     * @param array<string,mixed> $prices
     * @param array<int,string> $fields
     */
    private static function first_numeric_price(array $prices, array $fields): ?float
    {
        foreach ($fields as $field) {
            if (is_numeric($prices[$field] ?? null)) {
                return round((float) $prices[$field], 2);
            }
        }

        return null;
    }

    private static function clean_key(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9_-]+/', '-', $key);
        $key = is_string($key) ? trim($key, '-') : '';

        return $key;
    }
}
