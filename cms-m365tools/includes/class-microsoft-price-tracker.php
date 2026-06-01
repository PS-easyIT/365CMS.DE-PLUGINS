<?php
/**
 * CMS M365 Tools – Microsoft-Preiserhöhung-Tracker.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Microsoft_Price_Tracker
{
    /**
     * @return array<string,mixed>
     */
    public static function default_input(): array
    {
        return [
            'product_family' => 'all',
            'region' => 'global',
            'segment' => 'commercial',
            'event_type' => 'all',
            'analysis_start' => 2023,
            'analysis_end' => 2026,
            'renewal_date' => '2026-07-15',
            'channel' => 'csp',
            'forecast_scenario' => 'neutral',
            'forecast_months' => 12,
            'chart_start_year' => 2024,
            'chart_start_amount' => 6000.0,
            'current_amount' => 0.0,
            'sku_1' => 'm365-business-basic',
            'seats_1' => 100,
            'monthly_price_1' => 5.20,
            'sku_2' => 'm365-business-standard',
            'seats_2' => 0,
            'monthly_price_2' => 10.80,
            'sku_3' => 'teams-enterprise',
            'seats_3' => 0,
            'monthly_price_3' => 8.28,
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_input(array $source): array
    {
        $defaults = self::default_input();
        $skuKeys = array_keys(self::sku_options());

        $input = [
            'product_family' => self::choice((string) ($source['product_family'] ?? $defaults['product_family']), array_keys(self::product_family_options()), (string) $defaults['product_family']),
            'region' => self::choice((string) ($source['region'] ?? $defaults['region']), array_keys(self::region_options()), (string) $defaults['region']),
            'segment' => self::choice((string) ($source['segment'] ?? $defaults['segment']), array_keys(self::segment_options()), (string) $defaults['segment']),
            'event_type' => self::choice((string) ($source['event_type'] ?? $defaults['event_type']), array_keys(self::event_type_options()), (string) $defaults['event_type']),
            'analysis_start' => max(2020, min(2035, (int) ($source['analysis_start'] ?? $defaults['analysis_start']))),
            'analysis_end' => max(2020, min(2035, (int) ($source['analysis_end'] ?? $defaults['analysis_end']))),
            'renewal_date' => self::date_value((string) ($source['renewal_date'] ?? $defaults['renewal_date']), (string) $defaults['renewal_date']),
            'channel' => self::choice((string) ($source['channel'] ?? $defaults['channel']), array_keys(self::channel_options()), (string) $defaults['channel']),
            'forecast_scenario' => self::choice((string) ($source['forecast_scenario'] ?? $defaults['forecast_scenario']), array_keys(self::forecast_scenario_options()), (string) $defaults['forecast_scenario']),
            'forecast_months' => max(0, min(60, (int) ($source['forecast_months'] ?? $defaults['forecast_months']))),
            'chart_start_year' => max(2020, min(2035, (int) ($source['chart_start_year'] ?? $defaults['chart_start_year']))),
            'chart_start_amount' => self::money_value($source['chart_start_amount'] ?? $defaults['chart_start_amount'], 0, 100000000),
            'current_amount' => self::money_value($source['current_amount'] ?? $defaults['current_amount'], 0, 100000000),
        ];

        if ((int) $input['analysis_end'] < (int) $input['analysis_start']) {
            $input['analysis_end'] = $input['analysis_start'];
        }

        for ($i = 1; $i <= 3; $i++) {
            $input['sku_' . $i] = self::choice((string) ($source['sku_' . $i] ?? $defaults['sku_' . $i]), $skuKeys, (string) $defaults['sku_' . $i]);
            $input['seats_' . $i] = max(0, min(500000, (int) ($source['seats_' . $i] ?? $defaults['seats_' . $i])));
            $input['monthly_price_' . $i] = self::money_value($source['monthly_price_' . $i] ?? $defaults['monthly_price_' . $i], 0, 100000);
        }

        return $input;
    }

    /**
     * @return array<string,string>
     */
    public static function product_family_options(): array
    {
        $mapping = CMS_M365CALCULATOR_Catalog::microsoft_inventory_mapping();

        return self::string_map($mapping['product_family_options'] ?? []);
    }

    /**
     * @return array<string,string>
     */
    public static function region_options(): array
    {
        $mapping = CMS_M365CALCULATOR_Catalog::microsoft_inventory_mapping();

        return self::string_map($mapping['region_options'] ?? []);
    }

    /**
     * @return array<string,string>
     */
    public static function segment_options(): array
    {
        $mapping = CMS_M365CALCULATOR_Catalog::microsoft_inventory_mapping();

        return self::string_map($mapping['segment_options'] ?? []);
    }

    /**
     * @return array<string,string>
     */
    public static function event_type_options(): array
    {
        $mapping = CMS_M365CALCULATOR_Catalog::microsoft_inventory_mapping();

        return self::string_map($mapping['event_type_options'] ?? []);
    }

    /**
     * @return array<string,string>
     */
    public static function channel_options(): array
    {
        $mapping = CMS_M365CALCULATOR_Catalog::microsoft_inventory_mapping();

        return self::string_map($mapping['channel_options'] ?? []);
    }

    /**
     * @return array<string,string>
     */
    public static function sku_options(): array
    {
        $skus = CMS_M365CALCULATOR_Catalog::microsoft_price_skus();
        if ($skus !== []) {
            $labels = [];
            foreach ($skus as $sku) {
                $slug = (string) ($sku['slug'] ?? '');
                if ($slug !== '' && (int) ($sku['is_active'] ?? 1) === 1) {
                    $labels[$slug] = (string) ($sku['name'] ?? $slug);
                }
            }

            if ($labels !== []) {
                return $labels;
            }
        }

        $mapping = CMS_M365CALCULATOR_Catalog::microsoft_inventory_mapping();
        $items = is_array($mapping['sku_options'] ?? null) ? $mapping['sku_options'] : [];
        $labels = [];

        foreach ($items as $key => $item) {
            if (is_array($item)) {
                $labels[(string) $key] = (string) ($item['label'] ?? $key);
            }
        }

        return $labels;
    }

    /**
     * @return array<string,mixed>
     */
    public static function price_history_dataset(): array
    {
        $skus = CMS_M365CALCULATOR_Catalog::microsoft_price_skus();
        $licenses = [];
        $dates = [];
        $defaultSlugs = ['m365-apps-business', 'm365-business-basic', 'm365-business-standard', 'm365-business-premium'];

        foreach ($skus as $sku) {
            if (!is_array($sku) || (int) ($sku['is_active'] ?? 1) !== 1) {
                continue;
            }

            $slug = (string) ($sku['slug'] ?? '');
            $history = [];
            foreach ((array) ($sku['price_history'] ?? []) as $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $date = (string) ($entry['effective_from'] ?? '');
                $prices = is_array($entry['prices_eur_net'] ?? null) ? $entry['prices_eur_net'] : [];
                $price = self::numeric_price($prices, 'annual_annual_permonth');
                if ($date === '' || $price === null) {
                    continue;
                }

                $dates[$date] = true;
                $history[] = [
                    'date' => $date,
                    'price' => $price,
                    'status' => (string) ($entry['verification_status'] ?? ''),
                ];
            }

            if ($slug === '' || $history === []) {
                continue;
            }

            $licenses[] = [
                'slug' => $slug,
                'name' => (string) ($sku['name'] ?? $slug),
                'family' => (string) ($sku['family'] ?? $sku['product_family'] ?? 'Microsoft 365'),
                'audience' => (string) ($sku['audience'] ?? 'Commercial'),
                'default_visible' => in_array($slug, $defaultSlugs, true),
                'history' => $history,
            ];
        }

        $dates = array_keys($dates);
        sort($dates);

        return [
            'licenses' => $licenses,
            'dates' => $dates,
            'default_slugs' => $defaultSlugs,
            'currency' => 'EUR',
            'price_field' => 'annual_annual_permonth',
            'storage_key' => 'm365tools-price-tracker-entries-v1',
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function forecast_scenario_options(): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::microsoft_price_forecast_rules();
        $scenarios = is_array($catalog['scenarios'] ?? null) ? $catalog['scenarios'] : [];
        $labels = [];

        foreach ($scenarios as $key => $scenario) {
            if (is_array($scenario)) {
                $labels[(string) $key] = (string) ($scenario['label'] ?? $key);
            }
        }

        return $labels ?: ['none' => 'Kein Forecast'];
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function evaluate(array $input): array
    {
        $input = self::normalize_input($input);
        $eventsCatalog = CMS_M365CALCULATOR_Catalog::microsoft_price_events();
        $changesCatalog = CMS_M365CALCULATOR_Catalog::microsoft_price_changes();
        $mapping = CMS_M365CALCULATOR_Catalog::microsoft_inventory_mapping();
        $forecastRules = CMS_M365CALCULATOR_Catalog::microsoft_price_forecast_rules();
        $skuCatalog = CMS_M365CALCULATOR_Catalog::microsoft_price_skus();
        $events = self::filter_microsoft_price_history($input, $eventsCatalog);
        $inventory = self::map_inventory_to_price_events($input, $mapping, $changesCatalog, $skuCatalog);
        $impact = self::calculate_price_change_impact($input, $inventory, $events, $changesCatalog, $skuCatalog);
        $renewal = self::calculate_renewal_price_impact($input, $impact);
        $forecast = self::build_price_change_forecast($input, $inventory, $impact, $forecastRules);
        $chartRows = self::build_visual_chart_rows($input, $inventory, $impact, $forecast);
        $recommendation = self::build_recommendation($input, $events, $impact, $renewal, $forecast);

        return [
            'input' => $input,
            'recommendation' => $recommendation,
            'inventory' => $inventory,
            'events' => $events,
            'impact' => $impact,
            'renewal' => $renewal,
            'forecast' => $forecast,
            'chart_rows' => $chartRows,
            'product_family_options' => self::product_family_options(),
            'region_options' => self::region_options(),
            'segment_options' => self::segment_options(),
            'event_type_options' => self::event_type_options(),
            'channel_options' => self::channel_options(),
            'sku_options' => self::sku_options(),
            'forecast_scenario_options' => self::forecast_scenario_options(),
            'next_steps' => self::build_next_steps($recommendation, $impact, $renewal),
            'sources' => self::sources($eventsCatalog, $changesCatalog),
            'meta' => [
                'source_checked' => (string) ($eventsCatalog['meta']['source_checked'] ?? '2026-05-17'),
                'currency_note' => (string) ($changesCatalog['meta']['currency_note'] ?? 'Listenpreise können je Land, Währung und Vertrag abweichen.'),
                'forecast_text' => (string) ($forecastRules['meta']['display_text'] ?? 'Forecast ist eine Planungsschätzung.'),
            ],
        ];
    }

    public static function render_price_tracker_page(): string
    {
        return CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-microsoft-price-tracker.php';
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $eventsCatalog
     * @return array<int,array<string,mixed>>
     */
    public static function filter_microsoft_price_history(array $input, array $eventsCatalog): array
    {
        $rawEvents = is_array($eventsCatalog['events'] ?? null) ? $eventsCatalog['events'] : [];
        $events = [];

        foreach ($rawEvents as $event) {
            if (!is_array($event)) {
                continue;
            }

            $year = self::year_from_date((string) ($event['effective_at'] ?? $event['published_at'] ?? ''));
            if ($year < (int) $input['analysis_start'] || $year > (int) $input['analysis_end']) {
                continue;
            }

            if (!self::matches_scope((string) $input['product_family'], (array) ($event['product_family'] ?? []), 'all')) {
                continue;
            }
            if (!self::matches_scope((string) $input['region'], (array) ($event['region_scope'] ?? []), 'global')) {
                continue;
            }
            if (!self::matches_scope((string) $input['segment'], (array) ($event['segment_scope'] ?? []), 'commercial')) {
                continue;
            }
            if ((string) $input['event_type'] !== 'all' && (string) ($event['event_type'] ?? '') !== (string) $input['event_type']) {
                continue;
            }

            $events[] = $event;
        }

        usort($events, static fn(array $left, array $right): int => strcmp((string) ($left['effective_at'] ?? ''), (string) ($right['effective_at'] ?? '')));

        return $events;
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $mapping
     * @param array<string,mixed> $changesCatalog
     * @param array<int,array<string,mixed>> $skuCatalog
     * @return array<string,mixed>
     */
    public static function map_inventory_to_price_events(array $input, array $mapping, array $changesCatalog, array $skuCatalog = []): array
    {
        $skuMap = self::canonical_sku_map($skuCatalog, $mapping);
        $rows = [];
        $annualCurrent = 0.0;

        for ($i = 1; $i <= 3; $i++) {
            $seats = (int) ($input['seats_' . $i] ?? 0);
            if ($seats <= 0) {
                continue;
            }

            $sku = (string) ($input['sku_' . $i] ?? '');
            $meta = is_array($skuMap[$sku] ?? null) ? $skuMap[$sku] : [];
            $monthlyPrice = (float) ($input['monthly_price_' . $i] ?? 0);
            if ($monthlyPrice <= 0) {
                $monthlyPrice = self::sku_baseline_price($sku, $skuCatalog);
            }
            if ($monthlyPrice <= 0) {
                $monthlyPrice = self::latest_known_price($sku, $changesCatalog);
            }

            $annual = $monthlyPrice * $seats * 12;
            $annualCurrent += $annual;
            $rows[] = [
                'sku' => $sku,
                'label' => (string) ($meta['label'] ?? $meta['name'] ?? $sku),
                'product_family' => (string) ($meta['product_family'] ?? 'microsoft_365'),
                'with_teams_variant' => ($meta['teams_included'] ?? null) === true || !empty($meta['with_teams_variant']),
                'no_teams_variant' => ($meta['teams_included'] ?? null) === false || !empty($meta['no_teams_variant']),
                'seats' => $seats,
                'monthly_price' => $monthlyPrice,
                'annual_current' => $annual,
                'price_history' => is_array($meta['price_history'] ?? null) ? $meta['price_history'] : [],
                'verification_status' => (string) ($meta['verification_status'] ?? ''),
            ];
        }

        if ($rows === []) {
            $rows[] = [
                'sku' => 'm365-business-basic',
                'label' => 'Microsoft 365 Business Basic',
                'product_family' => 'microsoft_365',
                'with_teams_variant' => true,
                'no_teams_variant' => false,
                'seats' => 0,
                'monthly_price' => 0.0,
                'annual_current' => 0.0,
            ];
        }

        return [
            'rows' => $rows,
            'annual_current' => $annualCurrent,
            'current_amount' => (float) ($input['current_amount'] > 0 ? $input['current_amount'] : $annualCurrent),
            'active_skus' => array_values(array_unique(array_map(static fn(array $row): string => (string) $row['sku'], $rows))),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $inventory
     * @param array<int,array<string,mixed>> $events
     * @param array<string,mixed> $changesCatalog
     * @param array<int,array<string,mixed>> $skuCatalog
     * @return array<string,mixed>
     */
    public static function calculate_price_change_impact(array $input, array $inventory, array $events, array $changesCatalog, array $skuCatalog = []): array
    {
        $rows = [];
        $monthlyDelta = 0.0;
        $annualDelta = 0.0;
        $positiveAnnualDelta = 0.0;
        $decreaseAnnualDelta = 0.0;

        foreach ((array) ($inventory['rows'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $history = self::price_history_for_sku((string) ($item['sku'] ?? ''), $skuCatalog);
            if ($history === [] && is_array($item['price_history'] ?? null)) {
                $history = (array) $item['price_history'];
            }

            $previousPrice = null;
            foreach ($history as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $effectiveFrom = (string) ($entry['effective_from'] ?? '');
                $year = self::year_from_date($effectiveFrom);
                $prices = is_array($entry['prices_eur_net'] ?? null) ? $entry['prices_eur_net'] : [];
                $newPrice = self::numeric_price($prices, 'annual_annual_permonth');
                if ($newPrice === null) {
                    continue;
                }

                if ($previousPrice === null) {
                    $previousPrice = $newPrice;
                    continue;
                }

                if ($year < (int) $input['analysis_start'] || $year > (int) $input['analysis_end']) {
                    $previousPrice = $newPrice;
                    continue;
                }
                if (!self::matches_scope((string) $input['product_family'], [(string) ($item['product_family'] ?? '')], 'all')) {
                    $previousPrice = $newPrice;
                    continue;
                }

                $basePrice = $previousPrice;
                $deltaPerUser = $newPrice - $basePrice;
                if (abs($deltaPerUser) < 0.0001 && (string) ($entry['verification_status'] ?? '') !== 'confirmed_no_change') {
                    $previousPrice = $newPrice;
                    continue;
                }

                $seats = (int) ($item['seats'] ?? 0);
                $deltaMonthly = $deltaPerUser * $seats;
                $deltaAnnual = $deltaMonthly * 12;
                $monthlyDelta += $deltaMonthly;
                $annualDelta += $deltaAnnual;
                if ($deltaAnnual >= 0) {
                    $positiveAnnualDelta += $deltaAnnual;
                } else {
                    $decreaseAnnualDelta += $deltaAnnual;
                }

                $rows[] = [
                    'event_id' => 'canonical_price_history_' . $effectiveFrom,
                    'sku' => (string) ($item['sku'] ?? ''),
                    'label' => (string) ($item['label'] ?? ''),
                    'seats' => $seats,
                    'current_price' => $basePrice,
                    'new_price' => $newPrice,
                    'delta_monthly' => $deltaMonthly,
                    'delta_annual' => $deltaAnnual,
                    'percent_change' => $basePrice > 0 ? (($newPrice - $basePrice) / $basePrice) * 100 : 0.0,
                    'effective_at' => $effectiveFrom,
                    'verification_status' => (string) ($entry['verification_status'] ?? ''),
                    'source_url' => (string) ($entry['source_url'] ?? ''),
                ];

                $previousPrice = $newPrice;
            }
        }

        return [
            'rows' => $rows,
            'monthly_delta' => $monthlyDelta,
            'annual_delta' => $annualDelta,
            'positive_annual_delta' => $positiveAnnualDelta,
            'decrease_annual_delta' => $decreaseAnnualDelta,
            'affected_skus' => array_values(array_unique(array_map(static fn(array $row): string => (string) $row['sku'], $rows))),
            'row_count' => count($rows),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $impact
     * @return array<string,mixed>
     */
    public static function calculate_renewal_price_impact(array $input, array $impact): array
    {
        $renewal = new \DateTimeImmutable((string) $input['renewal_date']);
        $mainEffective = new \DateTimeImmutable('2026-07-01');
        $today = new \DateTimeImmutable('today');
        $daysToRenewal = max(0, (int) $today->diff($renewal)->format('%r%a'));
        $appliesAtRenewal = $renewal >= $mainEffective;
        $annualDelta = (float) ($impact['annual_delta'] ?? 0);

        return [
            'renewal_date' => $renewal->format('Y-m-d'),
            'main_effective_at' => $mainEffective->format('Y-m-d'),
            'days_to_renewal' => $daysToRenewal,
            'applies_at_renewal' => $appliesAtRenewal,
            'renewal_annual_delta' => $appliesAtRenewal ? $annualDelta : 0.0,
            'future_annual_delta' => $appliesAtRenewal ? 0.0 : $annualDelta,
            'label' => $appliesAtRenewal ? 'wirkt im nächsten Renewal-Fenster' : 'wirkt nach dem angegebenen Renewal-Fenster',
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $inventory
     * @param array<string,mixed> $impact
     * @param array<string,mixed> $forecastRules
     * @return array<string,mixed>
     */
    public static function build_price_change_forecast(array $input, array $inventory, array $impact, array $forecastRules): array
    {
        $scenarios = is_array($forecastRules['scenarios'] ?? null) ? $forecastRules['scenarios'] : [];
        $scenarioKey = (string) $input['forecast_scenario'];
        $scenario = is_array($scenarios[$scenarioKey] ?? null) ? $scenarios[$scenarioKey] : [];
        $factor = (float) ($scenario['annual_factor'] ?? 0.0);
        $months = (int) $input['forecast_months'];
        $base = max(0.0, (float) ($inventory['current_amount'] ?? 0) + (float) ($impact['positive_annual_delta'] ?? 0));
        $forecastDelta = $base * $factor * ($months / 12);

        return [
            'scenario' => $scenarioKey,
            'label' => (string) ($scenario['label'] ?? 'Forecast'),
            'months' => $months,
            'annual_factor' => $factor,
            'uncertainty' => (string) ($scenario['uncertainty'] ?? 'mittel'),
            'base_amount' => $base,
            'forecast_delta' => $forecastDelta,
            'forecast_amount' => $base + $forecastDelta,
            'active' => $factor > 0 && $months > 0,
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $inventory
     * @param array<string,mixed> $impact
     * @param array<string,mixed> $forecast
     * @return array<int,array<string,mixed>>
     */
    public static function build_visual_chart_rows(array $input, array $inventory, array $impact, array $forecast): array
    {
        $timelineRows = self::build_price_history_chart_rows($input, $inventory, $forecast);
        if ($timelineRows !== []) {
            return $timelineRows;
        }

        $startAmount = max(0.0, (float) ($input['chart_start_amount'] ?? 0));
        $currentAmount = max(0.0, (float) ($inventory['current_amount'] ?? 0));
        $renewalAmount = max(0.0, $currentAmount + (float) ($impact['positive_annual_delta'] ?? 0));
        $forecastAmount = max(0.0, (float) ($forecast['forecast_amount'] ?? $renewalAmount));
        $values = [$startAmount, $currentAmount, $renewalAmount];
        if (!empty($forecast['active'])) {
            $values[] = $forecastAmount;
        }
        $max = max(1.0, max($values));

        $rows = [
            self::chart_row((int) $input['chart_start_year'] . ' Ausgangswert', $startAmount, $max, 'neutral'),
            self::chart_row((int) date('Y') . ' aktueller Stand', $currentAmount, $max, 'cost'),
            self::chart_row('nach Renewal', $renewalAmount, $max, $renewalAmount >= $currentAmount ? 'cost' : 'gain'),
        ];

        if (!empty($forecast['active'])) {
            $rows[] = self::chart_row('Forecast-Zielwert', $forecastAmount, $max, 'cost');
        }

        return $rows;
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $inventory
     * @param array<string,mixed> $forecast
     * @return array<int,array<string,mixed>>
     */
    private static function build_price_history_chart_rows(array $input, array $inventory, array $forecast): array
    {
        $dates = [];
        $items = [];

        foreach ((array) ($inventory['rows'] ?? []) as $item) {
            if (!is_array($item) || (int) ($item['seats'] ?? 0) <= 0) {
                continue;
            }

            $history = is_array($item['price_history'] ?? null) ? $item['price_history'] : [];
            if ($history === []) {
                continue;
            }

            $items[] = $item;
            foreach ($history as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $date = (string) ($entry['effective_from'] ?? '');
                $year = self::year_from_date($date);
                if ($date !== '' && $year >= (int) $input['analysis_start'] && $year <= (int) $input['analysis_end']) {
                    $dates[$date] = true;
                }
            }
        }

        $dates = array_keys($dates);
        sort($dates);
        if (count($dates) < 2 || $items === []) {
            return [];
        }

        $amounts = [];
        foreach ($dates as $date) {
            $amount = 0.0;
            foreach ($items as $item) {
                $price = self::price_at_date((array) ($item['price_history'] ?? []), $date);
                if ($price === null) {
                    $price = (float) ($item['monthly_price'] ?? 0);
                }
                $amount += $price * (int) ($item['seats'] ?? 0) * 12;
            }
            $amounts[$date] = $amount;
        }

        $max = max(1.0, max($amounts));
        $rows = [];
        $previousAmount = null;
        foreach ($amounts as $date => $amount) {
            $tone = 'neutral';
            if ($previousAmount !== null) {
                if ($amount > $previousAmount + 0.0001) {
                    $tone = 'cost';
                } elseif ($amount < $previousAmount - 0.0001) {
                    $tone = 'gain';
                }
            }

            $rows[] = self::chart_row($date . ' Zeitreihe', $amount, $max, $tone);
            $previousAmount = $amount;
        }

        if (!empty($forecast['active'])) {
            $rows[] = self::chart_row('Forecast-Zielwert', max(0.0, (float) ($forecast['forecast_amount'] ?? 0)), max($max, (float) ($forecast['forecast_amount'] ?? 0)), 'cost');
        }

        return $rows;
    }

    /**
     * @param array<string,mixed> $input
     * @param array<int,array<string,mixed>> $events
     * @param array<string,mixed> $impact
     * @param array<string,mixed> $renewal
     * @param array<string,mixed> $forecast
     * @return array<string,mixed>
     */
    private static function build_recommendation(array $input, array $events, array $impact, array $renewal, array $forecast): array
    {
        $score = 10 + (count($events) * 6) + ((int) ($impact['row_count'] ?? 0) * 10);
        $annualDelta = (float) ($impact['positive_annual_delta'] ?? 0);
        $score += $annualDelta > 0 ? min(35, (int) round($annualDelta / 1000)) : 0;
        if (!empty($renewal['applies_at_renewal'])) {
            $score += 12;
        }
        if (!empty($forecast['active'])) {
            $score += 8;
        }
        $score = self::clamp_score($score);

        if ($score >= 70) {
            $category = 'material_budget';
            $tone = 'danger';
            $label = 'Budgetrelevante Preisänderung einplanen';
            $reason = 'Mindestens ein aktiver SKU-Treffer erzeugt spürbare Mehrkosten im Renewal- oder Forecast-Fenster.';
        } elseif ($score >= 45) {
            $category = 'renewal_budget';
            $tone = 'warning';
            $label = 'Renewal-Budget vorbereiten';
            $reason = 'Die offiziellen Ereignisse passen zum gewählten Bestand. Renewal-Datum, Vertrag und Mengen sollten vorab geprüft werden.';
        } elseif ($score >= 25) {
            $category = 'structure_watch';
            $tone = 'info';
            $label = 'Struktur und SKU-Mix beobachten';
            $reason = 'Es gibt relevante Packaging-, SKU- oder Konsistenzereignisse, aber nur begrenzte direkte Budgetwirkung.';
        } else {
            $category = 'watch';
            $tone = 'success';
            $label = 'Keine starke Budgetwirkung im Filter';
            $reason = 'Für die gewählten Filter und Mengen wurde keine starke direkte Preiswirkung gefunden.';
        }

        if ((string) $input['product_family'] === 'teams' && (float) ($impact['positive_annual_delta'] ?? 0) <= 0) {
            $reason = 'Standalone Teams ist im 2026-M365-Preisupdate nicht enthalten; Teams-spezifische Ereignisse bleiben getrennt bewertet.';
        }

        return [
            'category' => $category,
            'label' => $label,
            'tone' => $tone,
            'score' => $score,
            'reason' => $reason,
            'renewal_label' => (string) ($renewal['label'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $recommendation
     * @param array<string,mixed> $impact
     * @param array<string,mixed> $renewal
     * @return array<int,string>
     */
    private static function build_next_steps(array $recommendation, array $impact, array $renewal): array
    {
        $steps = [
            'Aktive SKUs, Mengen und Vertragspreise mit der aktuellen Preisliste abgleichen.',
            'Renewal-Datum, Laufzeit und Beschaffungskanal für betroffene Produkte dokumentieren.',
            'Offizielle Ereignisse von Planungsschätzungen getrennt im Budgetmodell führen.',
        ];

        if ((float) ($impact['positive_annual_delta'] ?? 0) > 0) {
            $steps[] = 'Mehrkosten als Jahres- und Monatswirkung in die nächste Budgetrunde aufnehmen.';
        }
        if (empty($renewal['applies_at_renewal'])) {
            $steps[] = 'Nächstes späteres Renewal-Fenster zusätzlich im Kalender markieren.';
        }
        if ((string) ($recommendation['category'] ?? '') === 'material_budget') {
            $steps[] = 'Einsparoptionen durch SKU-Mix, No-Teams-Varianten, Frontline-Varianten oder Add-on-Konsolidierung prüfen.';
        }

        return $steps;
    }

    /**
     * @param array<string,mixed> $change
     * @param array<string,mixed> $input
     */
    private static function change_matches_region_segment(array $input, array $change): bool
    {
        $region = (string) ($change['region'] ?? 'global');
        $segment = (string) ($change['segment'] ?? 'commercial');
        $selectedRegion = (string) ($input['region'] ?? 'global');
        $selectedSegment = (string) ($input['segment'] ?? 'commercial');

        $regionOk = $selectedRegion === 'global' || $region === 'global' || $region === $selectedRegion;
        $segmentOk = $selectedSegment === 'commercial' || $segment === $selectedSegment;

        return $regionOk && $segmentOk;
    }

    /**
     * @param array<int,string> $scope
     */
    private static function matches_scope(string $selected, array $scope, string $globalValue): bool
    {
        $scope = array_map('strval', $scope);
        if ($selected === 'all') {
            return true;
        }
        if ($selected === $globalValue) {
            return in_array($globalValue, $scope, true) || $scope === [];
        }

        return in_array($selected, $scope, true) || in_array($globalValue, $scope, true);
    }

    /**
     * @param array<int,array<string,mixed>> $skuCatalog
     * @param array<string,mixed> $mapping
     * @return array<string,array<string,mixed>>
     */
    private static function canonical_sku_map(array $skuCatalog, array $mapping): array
    {
        $map = [];

        foreach ($skuCatalog as $sku) {
            if (!is_array($sku)) {
                continue;
            }

            $slug = (string) ($sku['slug'] ?? '');
            if ($slug === '') {
                continue;
            }

            $sku['label'] = (string) ($sku['name'] ?? $slug);
            $map[$slug] = $sku;
            foreach ((array) ($sku['legacy_skus'] ?? []) as $legacy) {
                $legacy = (string) $legacy;
                if ($legacy !== '' && !isset($map[$legacy])) {
                    $map[$legacy] = $sku;
                }
            }
        }

        foreach ((array) ($mapping['sku_options'] ?? []) as $key => $item) {
            if (is_string($key) && is_array($item) && !isset($map[$key])) {
                $map[$key] = $item;
            }
        }

        return $map;
    }

    /**
     * @param array<int,array<string,mixed>> $skuCatalog
     * @return array<int,array<string,mixed>>
     */
    private static function price_history_for_sku(string $skuSlug, array $skuCatalog): array
    {
        foreach ($skuCatalog as $sku) {
            if (!is_array($sku)) {
                continue;
            }

            $slug = (string) ($sku['slug'] ?? '');
            $aliases = array_map('strval', (array) ($sku['legacy_skus'] ?? []));
            if ($slug !== $skuSlug && !in_array($skuSlug, $aliases, true)) {
                continue;
            }

            $history = is_array($sku['price_history'] ?? null) ? $sku['price_history'] : [];
            usort($history, static fn(array $left, array $right): int => strcmp((string) ($left['effective_from'] ?? ''), (string) ($right['effective_from'] ?? '')));

            return $history;
        }

        return [];
    }

    /**
     * @param array<int,array<string,mixed>> $skuCatalog
     */
    private static function sku_baseline_price(string $skuSlug, array $skuCatalog): float
    {
        $history = self::price_history_for_sku($skuSlug, $skuCatalog);
        $price = null;

        foreach ($history as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $effectiveFrom = (string) ($entry['effective_from'] ?? '');
            if ($effectiveFrom !== '' && strcmp($effectiveFrom, '2026-06-30') > 0) {
                break;
            }

            $prices = is_array($entry['prices_eur_net'] ?? null) ? $entry['prices_eur_net'] : [];
            $candidate = self::numeric_price($prices, 'annual_annual_permonth');
            if ($candidate !== null) {
                $price = $candidate;
            }
        }

        return $price ?? 0.0;
    }

    /**
     * @param array<int,array<string,mixed>> $history
     */
    private static function price_at_date(array $history, string $date): ?float
    {
        $price = null;
        usort($history, static fn(array $left, array $right): int => strcmp((string) ($left['effective_from'] ?? ''), (string) ($right['effective_from'] ?? '')));

        foreach ($history as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $effectiveFrom = (string) ($entry['effective_from'] ?? '');
            if ($effectiveFrom !== '' && strcmp($effectiveFrom, $date) > 0) {
                break;
            }

            $prices = is_array($entry['prices_eur_net'] ?? null) ? $entry['prices_eur_net'] : [];
            $candidate = self::numeric_price($prices, 'annual_annual_permonth');
            if ($candidate !== null) {
                $price = $candidate;
            }
        }

        return $price;
    }

    /**
     * @param array<string,mixed> $prices
     */
    private static function numeric_price(array $prices, string $field): ?float
    {
        return is_numeric($prices[$field] ?? null) ? round((float) $prices[$field], 2) : null;
    }

    private static function latest_known_price(string $sku, array $changesCatalog): float
    {
        $price = 0.0;
        foreach ((array) ($changesCatalog['changes'] ?? []) as $change) {
            if (is_array($change) && (string) ($change['sku'] ?? '') === $sku) {
                $price = (float) ($change['current_price'] ?? $price);
            }
        }

        return $price;
    }

    private static function year_from_date(string $date): int
    {
        if (preg_match('/^(\d{4})-/', $date, $matches) === 1) {
            return (int) $matches[1];
        }

        return 0;
    }

    private static function date_value(string $value, string $default): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        return $default;
    }

    private static function money_value(mixed $value, float $min, float $max): float
    {
        $normalized = str_replace(',', '.', (string) $value);
        $amount = is_numeric($normalized) ? (float) $normalized : 0.0;

        return max($min, min($max, $amount));
    }

    /**
     * @param array<int,mixed>|mixed $source
     * @return array<string,string>
     */
    private static function string_map(mixed $source): array
    {
        if (!is_array($source)) {
            return [];
        }

        $map = [];
        foreach ($source as $key => $value) {
            $map[(string) $key] = (string) $value;
        }

        return $map;
    }

    /**
     * @param array<int,string> $allowed
     */
    private static function choice(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private static function clamp_score(int $score): int
    {
        return max(0, min(100, $score));
    }

    /**
     * @return array<string,mixed>
     */
    private static function chart_row(string $label, float $amount, float $max, string $tone): array
    {
        return [
            'label' => $label,
            'amount' => $amount,
            'width' => max(2, min(100, (int) round(($amount / $max) * 100))),
            'tone' => $tone,
        ];
    }

    /**
     * @param array<string,mixed> ...$catalogs
     * @return array<int,string>
     */
    private static function sources(array ...$catalogs): array
    {
        $sources = [];
        foreach ($catalogs as $catalog) {
            foreach ((array) ($catalog['meta']['sources'] ?? []) as $source) {
                $sources[] = (string) $source;
            }
        }

        return array_values(array_unique(array_filter($sources)));
    }
}
