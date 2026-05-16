<?php
/**
 * CMS M365 Calculator – Lizenz-Vergleichstabelle Modul.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_License_Comparison
{
    private const MAX_SELECTED_PLANS = 6;

    /**
     * @return array<string,mixed>
     */
    public static function default_filters(): array
    {
        return [
            'q' => '',
            'family' => 'all',
            'features' => [],
            'selected' => ['m365-business-basic', 'm365-business-standard', 'm365-business-premium', 'm365-e3', 'm365-e5'],
            'sort' => 'price_asc',
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_filters(array $source): array
    {
        $filters = self::default_filters();
        $filters['q'] = self::limit_text(trim(strip_tags((string) ($source['q'] ?? ''))), 80);
        $filters['family'] = self::clean_key((string) ($source['family'] ?? 'all'));
        $filters['sort'] = self::enum((string) ($source['sort'] ?? 'price_asc'), ['price_asc', 'price_desc', 'name', 'security', 'copilot'], 'price_asc');

        $featureSource = is_array($source['features'] ?? null) ? $source['features'] : [];
        $allowedFeatures = ['desktop', 'copilot', 'security', 'phone', 'power', 'frontline'];
        $filters['features'] = array_values(array_intersect($allowedFeatures, array_map([self::class, 'clean_key'], $featureSource)));

        $selectedSource = is_array($source['selected'] ?? null) ? $source['selected'] : [];
        $selected = array_values(array_unique(array_filter(array_map([self::class, 'clean_key'], $selectedSource))));
        if ($selected !== []) {
            $filters['selected'] = array_slice($selected, 0, self::MAX_SELECTED_PLANS);
        }

        return $filters;
    }

    /**
     * @return array<string,mixed>
     */
    public static function load_plan_comparison_matrix(): array
    {
        return CMS_M365CALCULATOR_Catalog::plan_comparison_feature_matrix();
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public static function filter_plan_comparison(array $filters): array
    {
        $plans = self::load_plans();
        $matrix = self::load_plan_comparison_matrix();
        $features = self::feature_definitions($matrix);
        $query = strtolower((string) ($filters['q'] ?? ''));
        $family = (string) ($filters['family'] ?? 'all');
        $featureFilters = array_values(array_map('strval', $filters['features'] ?? []));
        $filtered = [];

        foreach ($plans as $plan) {
            if ($family !== 'all' && self::family_key($plan) !== $family) {
                continue;
            }

            if ($query !== '' && !self::plan_matches_query($plan, $features, $query)) {
                continue;
            }

            if (!self::plan_matches_feature_filters($plan, $features, $featureFilters)) {
                continue;
            }

            $filtered[] = self::decorate_plan($plan, $features);
        }

        return $filtered;
    }

    /**
     * @param array<int,array<string,mixed>> $plans
     * @return array<int,array<string,mixed>>
     */
    public static function sort_plan_comparison(array $plans, string $sort): array
    {
        usort($plans, static function (array $left, array $right) use ($sort): int {
            if ($sort === 'price_desc') {
                return ((float) ($right['price_month'] ?? 0)) <=> ((float) ($left['price_month'] ?? 0));
            }

            if ($sort === 'name') {
                return strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
            }

            if ($sort === 'security') {
                return ((int) ($right['_scores']['security'] ?? 0)) <=> ((int) ($left['_scores']['security'] ?? 0));
            }

            if ($sort === 'copilot') {
                return ((int) ($right['_scores']['copilot'] ?? 0)) <=> ((int) ($left['_scores']['copilot'] ?? 0));
            }

            $price = ((float) ($left['price_month'] ?? 0)) <=> ((float) ($right['price_month'] ?? 0));
            if ($price !== 0) {
                return $price;
            }

            return strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
        });

        return $plans;
    }

    /**
     * @param array<int,array<string,mixed>> $plans
     * @param array<string,array<string,mixed>> $features
     * @return array<string,mixed>
     */
    public static function highlight_plan_differences(array $plans, array $features): array
    {
        $differences = [];
        foreach ($features as $featureKey => $feature) {
            $seen = [];
            foreach ($plans as $plan) {
                $status = self::resolve_feature_status($plan, (string) $featureKey, $feature);
                $seen[$status['status']] = true;
            }
            $differences[(string) $featureKey] = count($seen) > 1;
        }

        $cheapest = null;
        foreach ($plans as $plan) {
            if ($cheapest === null || (float) ($plan['price_month'] ?? 0) < (float) ($cheapest['price_month'] ?? 0)) {
                $cheapest = $plan;
            }
        }

        return [
            'feature_differences' => $differences,
            'cheapest_slug' => is_array($cheapest) ? (string) ($cheapest['slug'] ?? '') : '',
            'selected_count' => count($plans),
        ];
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<string,array<string,mixed>> $features
     * @return array<int,array<string,string>>
     */
    public static function build_plan_badges(array $plan, array $features): array
    {
        $badgeCatalog = CMS_M365CALCULATOR_Catalog::plan_comparison_badges();
        $definitions = is_array($badgeCatalog['badges'] ?? null) ? $badgeCatalog['badges'] : [];
        $badges = [];
        $addBadge = static function (string $key) use (&$badges, $definitions): void {
            if (!is_array($definitions[$key] ?? null)) {
                return;
            }
            $badges[] = [
                'key' => $key,
                'label' => (string) ($definitions[$key]['label'] ?? $key),
                'description' => (string) ($definitions[$key]['description'] ?? ''),
            ];
        };

        $desktop = self::resolve_feature_status($plan, 'office_desktop', $features['office_desktop'] ?? []);
        $copilot = self::resolve_feature_status($plan, 'm365_copilot_addon', $features['m365_copilot_addon'] ?? []);
        $phone = self::resolve_feature_status($plan, 'teams_phone', $features['teams_phone'] ?? []);
        $premium = self::resolve_feature_status($plan, 'premium_connectors', $features['premium_connectors'] ?? []);
        $securityScore = self::score_plan($plan, $features, 'security');

        if ($desktop['status'] === 'included') {
            $addBadge('desktop_apps');
        }
        if (in_array($copilot['status'], ['included', 'addon'], true)) {
            $addBadge('copilot_base');
        }
        if ($securityScore >= 2) {
            $addBadge('security_strong');
        }
        if ($phone['status'] === 'included') {
            $addBadge('phone_included');
        } elseif ($phone['status'] === 'addon') {
            $addBadge('phone_addon');
        }
        if ($premium['status'] === 'addon') {
            $addBadge('power_addon');
        }
        if ((string) ($plan['audience'] ?? '') === 'frontline') {
            $addBadge('frontline');
        }
        if ((int) ($plan['max_users'] ?? 0) === 300) {
            $addBadge('business_limit');
        }

        return $badges;
    }

    /**
     * Kept as public module contract; rendering itself happens in CMS_M365CALCULATOR_Frontend.
     */
    public static function render_plan_comparison_page(): string
    {
        return CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-license-comparison.php';
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    public static function evaluate(array $filters): array
    {
        $matrix = self::load_plan_comparison_matrix();
        $features = self::feature_definitions($matrix);
        $groups = self::feature_groups($matrix, $features);
        $plans = self::sort_plan_comparison(self::filter_plan_comparison($filters), (string) ($filters['sort'] ?? 'price_asc'));
        $selectedPlans = self::selected_plans($plans, array_values(array_map('strval', $filters['selected'] ?? [])));
        $allPlans = self::load_plans();
        $planNotes = CMS_M365CALCULATOR_Catalog::plan_comparison_notes();
        $highlights = self::highlight_plan_differences($selectedPlans, $features);

        return [
            'filters' => $filters,
            'plans' => $plans,
            'selected_plans' => $selectedPlans,
            'features' => $features,
            'feature_groups' => $groups,
            'family_options' => self::family_options($allPlans),
            'feature_filter_options' => self::feature_filter_options(),
            'status_labels' => is_array($matrix['status_labels'] ?? null) ? $matrix['status_labels'] : [],
            'status_short_labels' => is_array($matrix['status_short_labels'] ?? null) ? $matrix['status_short_labels'] : [],
            'highlights' => $highlights,
            'notes' => is_array($planNotes['global_notes'] ?? null) ? $planNotes['global_notes'] : [],
            'plan_notes' => is_array($planNotes['plan_notes'] ?? null) ? $planNotes['plan_notes'] : [],
            'sources' => is_array($matrix['meta']['sources'] ?? null) ? $matrix['meta']['sources'] : [],
            'counts' => [
                'filtered' => count($plans),
                'selected' => count($selectedPlans),
                'all' => count($allPlans),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<string,mixed> $feature
     * @return array<string,string>
     */
    public static function feature_status(array $plan, string $featureKey, array $feature): array
    {
        return self::resolve_feature_status($plan, $featureKey, $feature);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function load_plans(): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::license_advisor_plans();
        $plans = is_array($catalog['plans'] ?? null) ? $catalog['plans'] : [];

        return array_values(array_filter($plans, static fn(mixed $plan): bool => is_array($plan) && !empty($plan['slug'])));
    }

    /**
     * @param array<string,mixed> $matrix
     * @return array<string,array<string,mixed>>
     */
    private static function feature_definitions(array $matrix): array
    {
        $features = is_array($matrix['features'] ?? null) ? $matrix['features'] : [];
        $normalized = [];
        foreach ($features as $key => $feature) {
            if (!is_array($feature)) {
                continue;
            }
            $normalized[(string) $key] = $feature;
        }

        return $normalized;
    }

    /**
     * @param array<string,mixed> $matrix
     * @param array<string,array<string,mixed>> $features
     * @return array<string,array<string,mixed>>
     */
    private static function feature_groups(array $matrix, array $features): array
    {
        $groups = [];
        $sourceGroups = is_array($matrix['feature_groups'] ?? null) ? $matrix['feature_groups'] : [];
        foreach ($sourceGroups as $group) {
            if (!is_array($group) || empty($group['key'])) {
                continue;
            }
            $groups[(string) $group['key']] = [
                'key' => (string) $group['key'],
                'label' => (string) ($group['label'] ?? $group['key']),
                'features' => [],
            ];
        }

        foreach ($features as $featureKey => $feature) {
            $groupKey = (string) ($feature['group'] ?? 'other');
            $groups[$groupKey] ??= ['key' => $groupKey, 'label' => $groupKey, 'features' => []];
            $groups[$groupKey]['features'][$featureKey] = $feature;
        }

        return $groups;
    }

    /**
     * @param array<int,array<string,mixed>> $plans
     * @param array<int,string> $selectedSlugs
     * @return array<int,array<string,mixed>>
     */
    private static function selected_plans(array $plans, array $selectedSlugs): array
    {
        $indexed = [];
        foreach ($plans as $plan) {
            $indexed[(string) ($plan['slug'] ?? '')] = $plan;
        }

        $selected = [];
        foreach ($selectedSlugs as $slug) {
            if (is_array($indexed[$slug] ?? null)) {
                $selected[] = $indexed[$slug];
            }
        }

        if ($selected === []) {
            $selected = array_slice($plans, 0, min(self::MAX_SELECTED_PLANS, max(1, count($plans))));
        }

        return array_slice($selected, 0, self::MAX_SELECTED_PLANS);
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<string,array<string,mixed>> $features
     * @return array<string,mixed>
     */
    private static function decorate_plan(array $plan, array $features): array
    {
        $plan['_badges'] = self::build_plan_badges($plan, $features);
        $plan['_scores'] = [
            'security' => self::score_plan($plan, $features, 'security'),
            'copilot' => self::score_plan($plan, $features, 'copilot'),
        ];

        return $plan;
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<string,array<string,mixed>> $features
     */
    private static function score_plan(array $plan, array $features, string $scoreType): int
    {
        $featureKeys = $scoreType === 'copilot'
            ? ['copilot_chat', 'm365_copilot_addon']
            : ['intune', 'defender_office_endpoint', 'entra_id_p1', 'entra_id_p2', 'purview_compliance', 'windows_rights'];
        $score = 0;

        foreach ($featureKeys as $featureKey) {
            if (!is_array($features[$featureKey] ?? null)) {
                continue;
            }
            $status = self::resolve_feature_status($plan, $featureKey, $features[$featureKey]);
            if ($status['status'] === 'included') {
                $score += 2;
            } elseif (in_array($status['status'], ['partial', 'addon'], true)) {
                $score += 1;
            }
        }

        return $score;
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<string,array<string,mixed>> $features
     * @param array<int,string> $featureFilters
     */
    private static function plan_matches_feature_filters(array $plan, array $features, array $featureFilters): bool
    {
        foreach ($featureFilters as $filter) {
            $matches = match ($filter) {
                'desktop' => self::has_status($plan, $features, ['office_desktop'], ['included']),
                'copilot' => self::has_status($plan, $features, ['m365_copilot_addon'], ['included', 'addon']),
                'security' => self::has_status($plan, $features, ['intune', 'defender_office_endpoint', 'entra_id_p1', 'entra_id_p2', 'purview_compliance'], ['included', 'partial']),
                'phone' => self::has_status($plan, $features, ['teams_phone'], ['included', 'addon']),
                'power' => self::has_status($plan, $features, ['power_apps_seeded', 'power_automate_seeded', 'premium_connectors'], ['included', 'partial', 'addon']),
                'frontline' => (string) ($plan['audience'] ?? '') === 'frontline',
                default => true,
            };

            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<string,array<string,mixed>> $features
     * @param array<int,string> $featureKeys
     * @param array<int,string> $allowedStatuses
     */
    private static function has_status(array $plan, array $features, array $featureKeys, array $allowedStatuses): bool
    {
        foreach ($featureKeys as $featureKey) {
            if (!is_array($features[$featureKey] ?? null)) {
                continue;
            }
            $status = self::resolve_feature_status($plan, $featureKey, $features[$featureKey]);
            if (in_array($status['status'], $allowedStatuses, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<string,array<string,mixed>> $features
     */
    private static function plan_matches_query(array $plan, array $features, string $query): bool
    {
        $haystack = strtolower(implode(' ', array_filter([
            (string) ($plan['name'] ?? ''),
            (string) ($plan['family'] ?? ''),
            (string) ($plan['audience'] ?? ''),
            (string) ($plan['source_note'] ?? ''),
            implode(' ', array_map('strval', $plan['features'] ?? [])),
            implode(' ', array_map('strval', $plan['tags'] ?? [])),
        ])));

        if (str_contains($haystack, $query)) {
            return true;
        }

        foreach ($features as $featureKey => $feature) {
            $status = self::resolve_feature_status($plan, (string) $featureKey, $feature);
            if ($status['status'] === 'not_included') {
                continue;
            }
            $featureText = strtolower((string) ($feature['label'] ?? '') . ' ' . (string) ($feature['description'] ?? '') . ' ' . (string) ($status['note'] ?? ''));
            if (str_contains($featureText, $query)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int,array<string,mixed>> $plans
     * @return array<string,string>
     */
    private static function family_options(array $plans): array
    {
        $options = ['all' => 'Alle Lizenzfamilien'];
        foreach ($plans as $plan) {
            $key = self::family_key($plan);
            if ($key === '') {
                continue;
            }
            $options[$key] = (string) ($plan['family'] ?? $key);
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);
        $all = ['all' => 'Alle Lizenzfamilien'];
        unset($options['all']);

        return $all + $options;
    }

    /**
     * @return array<string,string>
     */
    private static function feature_filter_options(): array
    {
        return [
            'desktop' => 'Desktop Apps enthalten',
            'copilot' => 'Copilot-fähige Basis',
            'security' => 'Security / Compliance stark',
            'phone' => 'Teams Phone Pfad',
            'power' => 'Power Platform relevant',
            'frontline' => 'Frontline-Pläne',
        ];
    }

    /**
     * @param array<string,mixed> $plan
     */
    private static function family_key(array $plan): string
    {
        return self::clean_key((string) ($plan['family'] ?? ''));
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<string,mixed> $feature
     * @return array<string,string>
     */
    private static function resolve_feature_status(array $plan, string $featureKey, array $feature): array
    {
        $slug = (string) ($plan['slug'] ?? '');
        $overrides = is_array($feature['overrides'] ?? null) ? $feature['overrides'] : [];
        if (isset($overrides[$slug])) {
            $override = $overrides[$slug];
            if (is_array($override)) {
                return self::status((string) ($override['status'] ?? 'not_included'), (string) ($override['note'] ?? ''));
            }
            return self::status((string) $override, '');
        }

        if (self::matches_all($plan, $feature, 'included_if_features', 'features')
            || self::matches_any($plan, $feature, 'included_if_features_any', 'features')
            || self::matches_any($plan, $feature, 'included_if_tags', 'tags')
            || self::matches_any($plan, $feature, 'included_if_terms', 'terms')
            || self::matches_any($plan, $feature, 'included_if_audience', 'audience')) {
            return self::status('included', (string) ($feature['included_note'] ?? 'Im Plan enthalten.'));
        }

        if (self::matches_all($plan, $feature, 'partial_if_features', 'features')
            || self::matches_any($plan, $feature, 'partial_if_features_any', 'features')
            || self::matches_any($plan, $feature, 'partial_if_tags', 'tags')
            || self::matches_any($plan, $feature, 'partial_if_terms', 'terms')) {
            return self::status('partial', (string) ($feature['partial_note'] ?? 'Teilweise enthalten oder eingeschränkt nutzbar.'));
        }

        if (($feature['addon_by_default'] ?? false) === true
            || self::matches_any($plan, $feature, 'addon_if_features', 'features')
            || self::matches_any($plan, $feature, 'addon_if_tags', 'tags')) {
            return self::status('addon', (string) ($feature['default_note'] ?? 'Als Add-on oder separater Dienst zu planen.'));
        }

        if (isset($feature['missing_status'])) {
            return self::status((string) $feature['missing_status'], (string) ($feature['missing_note'] ?? 'Voraussetzung prüfen.'));
        }

        return self::status('not_included', (string) ($feature['not_included_note'] ?? 'Nicht enthalten.'));
    }

    /**
     * @return array<string,string>
     */
    private static function status(string $status, string $note): array
    {
        $allowed = ['included', 'partial', 'addon', 'prerequisite', 'not_included'];
        return [
            'status' => in_array($status, $allowed, true) ? $status : 'not_included',
            'note' => $note,
        ];
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<string,mixed> $feature
     */
    private static function matches_all(array $plan, array $feature, string $ruleKey, string $planKey): bool
    {
        $needles = array_values(array_map('strval', is_array($feature[$ruleKey] ?? null) ? $feature[$ruleKey] : []));
        if ($needles === []) {
            return false;
        }

        $haystack = self::plan_values($plan, $planKey);
        return count(array_intersect($needles, $haystack)) === count($needles);
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<string,mixed> $feature
     */
    private static function matches_any(array $plan, array $feature, string $ruleKey, string $planKey): bool
    {
        $needles = array_values(array_map('strval', is_array($feature[$ruleKey] ?? null) ? $feature[$ruleKey] : []));
        if ($needles === []) {
            return false;
        }

        return array_intersect($needles, self::plan_values($plan, $planKey)) !== [];
    }

    /**
     * @param array<string,mixed> $plan
     * @return array<int,string>
     */
    private static function plan_values(array $plan, string $key): array
    {
        if ($key === 'audience') {
            return [(string) ($plan['audience'] ?? '')];
        }

        return array_values(array_map('strval', is_array($plan[$key] ?? null) ? $plan[$key] : []));
    }

    private static function enum(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private static function clean_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($value)), '-');
    }

    private static function limit_text(string $value, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }

        return substr($value, 0, $length);
    }
}
