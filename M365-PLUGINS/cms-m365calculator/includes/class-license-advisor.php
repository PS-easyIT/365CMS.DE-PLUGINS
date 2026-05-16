<?php
/**
 * CMS M365 Calculator – M365 Lizenzberater Modul.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_License_Advisor
{
    private const MAX_GROUPS = 5;

    /**
     * @return array<string,mixed>
     */
    public static function default_input(): array
    {
        return [
            'company_size' => 50,
            'customer_type' => 'smb',
            'channel' => 'unknown',
            'billing_cycle' => 'annual',
            'pstn_provider' => 'third_party',
            'tenant_storage_tb' => 1.0,
            'groups' => [
                [
                    'label' => 'Knowledge Worker',
                    'quantity' => 25,
                    'persona' => 'knowledge_worker',
                    'features' => [],
                ],
            ],
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_input(array $source): array
    {
        $input = self::default_input();
        $input['company_size'] = max(1, min(500000, (int) ($source['company_size'] ?? $input['company_size'])));
        $input['customer_type'] = self::enum((string) ($source['customer_type'] ?? 'smb'), ['smb', 'midmarket', 'enterprise', 'frontline'], 'smb');
        $input['channel'] = self::enum((string) ($source['channel'] ?? 'unknown'), ['csp', 'direct', 'ea', 'unknown'], 'unknown');
        $input['billing_cycle'] = self::enum((string) ($source['billing_cycle'] ?? 'annual'), ['annual', 'annual_monthly', 'monthly', 'three_year'], 'annual');
        $input['pstn_provider'] = self::enum((string) ($source['pstn_provider'] ?? 'third_party'), ['microsoft', 'third_party', 'none'], 'third_party');
        $input['tenant_storage_tb'] = max(0.0, min(9999.0, (float) str_replace(',', '.', (string) ($source['tenant_storage_tb'] ?? 1))));

        $labels = is_array($source['group_label'] ?? null) ? $source['group_label'] : [];
        $quantities = is_array($source['group_quantity'] ?? null) ? $source['group_quantity'] : [];
        $personas = is_array($source['group_persona'] ?? null) ? $source['group_persona'] : [];
        $features = is_array($source['features'] ?? null) ? $source['features'] : [];
        $groups = [];

        for ($i = 0; $i < self::MAX_GROUPS; $i++) {
            $quantity = max(0, min(500000, (int) ($quantities[$i] ?? 0)));
            $label = trim(strip_tags((string) ($labels[$i] ?? '')));
            if ($quantity <= 0 && $label === '' && empty($features[$i])) {
                continue;
            }

            $groupFeatures = [];
            if (isset($features[$i]) && is_array($features[$i])) {
                $groupFeatures = array_values(array_unique(array_filter(array_map([self::class, 'clean_key'], $features[$i]))));
            }

            $groups[] = [
                'label' => self::limit_text($label !== '' ? $label : 'Nutzergruppe ' . ($i + 1), 80),
                'quantity' => max(1, $quantity),
                'persona' => self::clean_key((string) ($personas[$i] ?? 'knowledge_worker')),
                'features' => $groupFeatures,
            ];
        }

        if ($groups !== []) {
            $input['groups'] = array_slice($groups, 0, self::MAX_GROUPS);
        }

        return $input;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function load_license_catalog(): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::license_advisor_plans();

        return is_array($catalog['plans'] ?? null) ? $catalog['plans'] : [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function load_addon_catalog(): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::license_advisor_addons();

        return is_array($catalog['addons'] ?? null) ? $catalog['addons'] : [];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function load_persona_presets(): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::license_advisor_persona_presets();

        return is_array($catalog['presets'] ?? null) ? $catalog['presets'] : [];
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function evaluate(array $input): array
    {
        $plans = self::load_license_catalog();
        $addons = self::load_addon_catalog();
        $presets = self::load_persona_presets();
        $featureMatrix = CMS_M365CALCULATOR_Catalog::license_advisor_feature_matrix();
        $features = is_array($featureMatrix['features'] ?? null) ? $featureMatrix['features'] : [];
        $commercialRules = CMS_M365CALCULATOR_Catalog::license_advisor_commercial_rules();
        $billing = self::billing_context((string) ($input['billing_cycle'] ?? 'annual'), $commercialRules);

        $rows = [];
        $totals = [];
        $warnings = [];
        $grandMonthly = 0.0;
        $hasAddons = false;
        $hasCritical = false;
        $baseSlugs = [];

        foreach ($input['groups'] ?? [] as $index => $group) {
            if (!is_array($group)) {
                continue;
            }

            $recommendation = self::build_license_recommendation($group, $input, $plans, $addons, $presets, $features, $billing);
            $rows[] = $recommendation;

            if (!empty($recommendation['primary']['base']['slug'])) {
                $baseSlugs[(string) $recommendation['primary']['base']['slug']] = true;
            }

            foreach ($recommendation['primary']['items'] ?? [] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                self::merge_total($totals, $item);
            }

            $grandMonthly += (float) ($recommendation['primary']['monthly_total'] ?? 0);
            $hasAddons = $hasAddons || !empty($recommendation['primary']['addons']);
            $hasCritical = $hasCritical || !empty($recommendation['critical']);
            $warnings = array_merge($warnings, array_values(array_map('strval', $recommendation['warnings'] ?? [])));
        }

        $storageWarning = self::build_storage_warning($input, $plans);
        if ($storageWarning !== '') {
            $warnings[] = $storageWarning;
        }

        $status = self::resolve_overall_status($rows, count($baseSlugs), $hasAddons, $hasCritical);
        $annual = $grandMonthly * 12;

        return [
            'status' => $status,
            'rows' => $rows,
            'totals' => array_values($totals),
            'monthly_total' => round($grandMonthly, 2),
            'annual_total' => round($annual, 2),
            'three_year_total' => round($annual * 3, 2),
            'billing' => $billing,
            'warnings' => array_values(array_unique($warnings)),
            'sources' => is_array($commercialRules['sources'] ?? null) ? $commercialRules['sources'] : [],
        ];
    }

    /**
     * @param array<string,mixed> $group
     * @param array<string,mixed> $input
     * @param array<int,array<string,mixed>> $plans
     * @param array<int,array<string,mixed>> $addons
     * @param array<string,array<string,mixed>> $presets
     * @param array<string,array<string,mixed>> $featureMatrix
     * @param array<string,mixed> $billing
     * @return array<string,mixed>
     */
    public static function build_license_recommendation(array $group, array $input, array $plans, array $addons, array $presets, array $featureMatrix, array $billing): array
    {
        $requirements = self::validate_license_requirements($group, $input, $presets, $featureMatrix);
        $candidates = self::score_license_candidates($plans, $addons, $requirements, $input, $billing);
        $alternatives = self::build_alternative_scenarios($candidates);
        $primary = $candidates[0] ?? null;

        if ($primary === null) {
            return [
                'label' => (string) ($group['label'] ?? 'Nutzergruppe'),
                'quantity' => (int) ($group['quantity'] ?? 0),
                'requirements' => $requirements,
                'primary' => [
                    'base' => null,
                    'addons' => [],
                    'items' => [],
                    'monthly_total' => 0.0,
                    'fit_score' => 0,
                    'explanation' => 'Keine belastbare Auto-Empfehlung gefunden.',
                ],
                'alternatives' => [],
                'warnings' => ['Für diese Nutzergruppe ist eine manuelle Lizenzprüfung sinnvoll.'],
                'critical' => ['Keine passende Basislizenz gefunden.'],
            ];
        }

        return [
            'label' => (string) ($group['label'] ?? 'Nutzergruppe'),
            'quantity' => (int) ($group['quantity'] ?? 0),
            'requirements' => $requirements,
            'primary' => $primary,
            'alternatives' => $alternatives,
            'warnings' => array_values(array_unique(array_map('strval', $primary['warnings'] ?? []))),
            'critical' => array_values(array_unique(array_map('strval', $primary['critical'] ?? []))),
        ];
    }

    /**
     * @param array<string,mixed> $group
     * @param array<string,mixed> $input
     * @param array<string,array<string,mixed>> $presets
     * @param array<string,array<string,mixed>> $featureMatrix
     * @return array<string,mixed>
     */
    public static function validate_license_requirements(array $group, array $input, array $presets, array $featureMatrix): array
    {
        $personaKey = self::clean_key((string) ($group['persona'] ?? 'knowledge_worker'));
        $preset = $presets[$personaKey] ?? $presets['knowledge_worker'] ?? [];
        $presetFeatures = array_values(array_map('strval', $preset['features'] ?? []));
        $extraFeatures = array_values(array_map('strval', $group['features'] ?? []));
        $features = array_values(array_unique(array_filter(array_merge($presetFeatures, $extraFeatures))));
        $baseFeatures = [];
        $addonFeatures = [];

        foreach ($features as $feature) {
            if (($featureMatrix[$feature]['base'] ?? false) === true) {
                $baseFeatures[] = $feature;
            } else {
                $addonFeatures[] = $feature;
            }
        }

        return [
            'persona' => $personaKey,
            'persona_label' => (string) ($preset['label'] ?? 'Knowledge Worker'),
            'audience' => (string) ($preset['audience'] ?? 'knowledge'),
            'preferred_families' => array_values(array_map('strval', $preset['preferred_families'] ?? [])),
            'features' => $features,
            'base_features' => $baseFeatures,
            'addon_features' => $addonFeatures,
            'quantity' => max(1, (int) ($group['quantity'] ?? 1)),
            'company_size' => max(1, (int) ($input['company_size'] ?? 1)),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $plans
     * @param array<int,array<string,mixed>> $addons
     * @param array<string,mixed> $requirements
     * @param array<string,mixed> $input
     * @param array<string,mixed> $billing
     * @return array<int,array<string,mixed>>
     */
    public static function score_license_candidates(array $plans, array $addons, array $requirements, array $input, array $billing): array
    {
        $candidates = [];
        foreach ($plans as $plan) {
            if (empty($plan['slug'])) {
                continue;
            }

            $candidate = self::build_candidate($plan, $addons, $requirements, $input, $billing);
            if ($candidate === null) {
                continue;
            }

            $candidates[] = $candidate;
        }

        usort($candidates, static function (array $left, array $right): int {
            $rank = ((float) ($left['rank'] ?? 999999)) <=> ((float) ($right['rank'] ?? 999999));
            if ($rank !== 0) {
                return $rank;
            }

            return ((float) ($left['monthly_total'] ?? 999999)) <=> ((float) ($right['monthly_total'] ?? 999999));
        });

        return $candidates;
    }

    /**
     * @param array<int,array<string,mixed>> $candidates
     * @return array<int,array<string,mixed>>
     */
    public static function build_alternative_scenarios(array $candidates): array
    {
        return array_slice(array_map(static function (array $candidate): array {
            return [
                'base' => $candidate['base'],
                'addons' => $candidate['addons'],
                'monthly_total' => $candidate['monthly_total'],
                'fit_score' => $candidate['fit_score'],
                'explanation' => $candidate['explanation'],
            ];
        }, array_slice($candidates, 1, 3)), 0, 3);
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return array<string,float>
     */
    public static function calculate_license_totals(array $items): array
    {
        $monthly = 0.0;
        foreach ($items as $item) {
            $monthly += (float) ($item['line_total'] ?? 0);
        }

        return [
            'monthly' => round($monthly, 2),
            'annual' => round($monthly * 12, 2),
            'three_year' => round($monthly * 36, 2),
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function feature_options(): array
    {
        $matrix = CMS_M365CALCULATOR_Catalog::license_advisor_feature_matrix();
        $features = is_array($matrix['features'] ?? null) ? $matrix['features'] : [];
        $options = [];

        foreach ($features as $key => $definition) {
            if (!is_array($definition)) {
                continue;
            }
            $options[(string) $key] = (string) ($definition['label'] ?? $key);
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<int,array<string,mixed>> $addons
     * @param array<string,mixed> $requirements
     * @param array<string,mixed> $input
     * @param array<string,mixed> $billing
     * @return array<string,mixed>|null
     */
    private static function build_candidate(array $plan, array $addons, array $requirements, array $input, array $billing): ?array
    {
        $quantity = (int) ($requirements['quantity'] ?? 1);
        $planFeatures = array_values(array_map('strval', $plan['features'] ?? []));
        $planTags = array_values(array_map('strval', $plan['tags'] ?? []));
        $warnings = [];
        $critical = [];
        $missingBase = array_values(array_diff(array_map('strval', $requirements['base_features'] ?? []), $planFeatures));

        if (in_array('terminalserver', $requirements['features'] ?? [], true) && !in_array('shared_computer_activation', $planTags, true)) {
            return null;
        }

        if ($missingBase !== []) {
            return null;
        }

        $maxUsers = (int) ($plan['max_users'] ?? 500000);
        if ((int) ($requirements['company_size'] ?? $quantity) > $maxUsers || $quantity > $maxUsers) {
            $critical[] = 'Business-/Planlimit überschritten: ' . (string) ($plan['name'] ?? 'Plan') . ' ist bis ' . $maxUsers . ' Benutzer modelliert.';
        }

        $selectedAddons = [];
        foreach (array_map('strval', $requirements['addon_features'] ?? []) as $feature) {
            if (in_array($feature, $planFeatures, true)) {
                continue;
            }

            $addon = self::find_addon_for_feature($feature, $addons, $planTags, $planFeatures);
            if ($addon === null) {
                $critical[] = 'Für Zusatzbedarf ' . $feature . ' wurde kein kompatibles Add-on gefunden.';
                continue;
            }

            if ($feature === 'copilot_m365' && !self::has_any_tag($planTags, ['copilot_business_eligible', 'copilot_enterprise_eligible'])) {
                $critical[] = 'Copilot benötigt eine berechtigte Basislizenz; dieser Plan ist dafür nicht markiert.';
                continue;
            }

            $selectedAddons[(string) $addon['slug']] = $addon;
        }

        if (in_array('copilot_m365', $requirements['features'] ?? [], true)) {
            $warnings[] = 'Copilot benötigt zusätzlich Entra ID, Microsoft 365 Apps, OneDrive und ein primäres Exchange-Online-Postfach.';
        }

        if (in_array('phone_system', $requirements['features'] ?? [], true) && (string) ($input['pstn_provider'] ?? 'third_party') === 'microsoft') {
            $warnings[] = 'Microsoft-PSTN erfordert zusätzlich einen passenden Calling Plan; dieser wird hier als Detailprüfung markiert.';
        }

        if (array_intersect(['power_apps', 'automation'], array_map('strval', $requirements['features'] ?? [])) !== []) {
            $warnings[] = 'Power-Platform-Premium, Custom Connectoren oder On-Premises Gateways benötigen Standalone-Power-Platform-Lizenzen; seeded M365-Rechte reichen dafür nicht.';
        }

        if (in_array('resource_account', $requirements['features'] ?? [], true)) {
            $warnings[] = 'Für Auto Attendants oder Call Queues Teams Phone Resource Account Lizenzen kaufen/zuweisen; diese sind no-cost, aber verpflichtend.';
        }

        $basePrice = self::adjust_price((float) ($plan['price_month'] ?? 0), $billing);
        $items = [[
            'slug' => (string) $plan['slug'],
            'name' => (string) $plan['name'],
            'type_label' => 'Basislizenz',
            'quantity' => $quantity,
            'unit_price' => $basePrice,
            'line_total' => round($basePrice * $quantity, 2),
            'source_note' => (string) ($plan['source_note'] ?? ''),
        ]];
        $monthlyTotal = $basePrice * $quantity;

        foreach ($selectedAddons as $addon) {
            $price = isset($addon['price_month']) && $addon['price_month'] !== null ? self::adjust_price((float) $addon['price_month'], $billing) : 0.0;
            $basis = (string) ($addon['pricing_basis'] ?? 'per_user');
            $billingQuantity = $basis === 'flat_monthly' ? 1 : $quantity;
            $lineTotal = $price * $billingQuantity;
            $monthlyTotal += $lineTotal;
            $items[] = [
                'slug' => (string) $addon['slug'],
                'name' => (string) $addon['name'],
                'type_label' => 'Add-on',
                'quantity' => $billingQuantity,
                'unit_price' => $price,
                'line_total' => round($lineTotal, 2),
                'source_note' => (string) ($addon['source_note'] ?? ''),
            ];
        }

        $coveredBase = count(array_diff(array_map('strval', $requirements['base_features'] ?? []), $missingBase));
        $baseCount = max(1, count($requirements['base_features'] ?? []));
        $featureCoverage = $coveredBase / $baseCount;
        $addonPenalty = count($selectedAddons) * 3;
        $criticalPenalty = count($critical) * 40;
        $missingPenalty = count($missingBase) * 8;
        $audiencePenalty = self::audience_penalty($plan, (string) ($requirements['audience'] ?? 'knowledge'));
        $preferredBonus = in_array((string) ($plan['family'] ?? ''), array_map('strval', $requirements['preferred_families'] ?? []), true) ? 5 : 0;
        $fitScore = max(0, min(100, (int) round(($featureCoverage * 80) + $preferredBonus + 15 - $addonPenalty - $criticalPenalty - $missingPenalty - $audiencePenalty)));

        return [
            'base' => $plan,
            'addons' => array_values($selectedAddons),
            'items' => $items,
            'monthly_total' => round($monthlyTotal, 2),
            'annual_total' => round($monthlyTotal * 12, 2),
            'fit_score' => $fitScore,
            'warnings' => $warnings,
            'critical' => $critical,
            'missing_base_features' => $missingBase,
            'explanation' => self::build_candidate_explanation($plan, array_values($selectedAddons), $missingBase, $critical),
            'rank' => ($monthlyTotal * 100) + ((100 - $fitScore) * 10) + (count($critical) * 100000) + $audiencePenalty,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $addons
     * @param array<int,string> $planTags
     * @param array<int,string> $planFeatures
     * @return array<string,mixed>|null
     */
    private static function find_addon_for_feature(string $feature, array $addons, array $planTags, array $planFeatures): ?array
    {
        $matches = [];
        foreach ($addons as $addon) {
            if ((string) ($addon['feature'] ?? '') !== $feature) {
                continue;
            }

            $redundantTags = array_values(array_map('strval', $addon['redundant_if_tags'] ?? []));
            if ($redundantTags !== [] && array_intersect($redundantTags, $planTags) !== []) {
                continue;
            }

            $redundantFeatures = array_values(array_map('strval', $addon['redundant_if_features'] ?? []));
            if ($redundantFeatures !== [] && array_intersect($redundantFeatures, $planFeatures) !== []) {
                continue;
            }

            $requiredTags = array_values(array_map('strval', $addon['prerequisite_tags'] ?? []));
            if ($requiredTags !== [] && array_intersect($requiredTags, $planTags) === []) {
                continue;
            }

            $matches[] = $addon;
        }

        if ($matches === []) {
            return null;
        }

        usort($matches, static fn(array $left, array $right): int => ((float) ($left['price_month'] ?? 999999)) <=> ((float) ($right['price_month'] ?? 999999)));

        return $matches[0];
    }

    /**
     * @param array<string,mixed> $billing
     */
    private static function adjust_price(float $price, array $billing): float
    {
        return round($price * (float) ($billing['multiplier'] ?? 1.0), 2);
    }

    /**
     * @param array<string,mixed> $rules
     * @return array<string,mixed>
     */
    private static function billing_context(string $key, array $rules): array
    {
        $billing = is_array($rules['billing'] ?? null) ? $rules['billing'] : [];
        $context = is_array($billing[$key] ?? null) ? $billing[$key] : ($billing['annual'] ?? []);
        $context['key'] = $key;

        return $context;
    }

    /**
     * @param array<string,mixed> $plan
     */
    private static function audience_penalty(array $plan, string $audience): int
    {
        $planAudience = (string) ($plan['audience'] ?? 'knowledge');
        if ($audience === 'frontline' && $planAudience !== 'frontline') {
            return 15;
        }
        if ($audience === 'knowledge' && $planAudience === 'frontline') {
            return 35;
        }

        return 0;
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<int,array<string,mixed>> $addons
     * @param array<int,string> $missingBase
     * @param array<int,string> $critical
     */
    private static function build_candidate_explanation(array $plan, array $addons, array $missingBase, array $critical): string
    {
        if ($critical !== []) {
            return 'Nur mit Detailprüfung geeignet: ' . implode(' ', $critical);
        }

        $parts = [(string) ($plan['name'] ?? 'Diese Basislizenz') . ' deckt den Kernbedarf am besten ab.'];
        if ($addons !== []) {
            $parts[] = 'Zusätzlich nötig: ' . implode(', ', array_map(static fn(array $addon): string => (string) ($addon['name'] ?? ''), $addons)) . '.';
        }
        if ($missingBase !== []) {
            $parts[] = 'Nicht vollständig abgedeckte Basismerkmale: ' . implode(', ', $missingBase) . '.';
        }

        return implode(' ', $parts);
    }

    /**
     * @param array<string,array<string,mixed>> $totals
     * @param array<string,mixed> $item
     */
    private static function merge_total(array &$totals, array $item): void
    {
        $slug = (string) ($item['slug'] ?? '');
        if ($slug === '') {
            return;
        }

        if (!isset($totals[$slug])) {
            $totals[$slug] = $item;
            return;
        }

        $totals[$slug]['quantity'] = (int) ($totals[$slug]['quantity'] ?? 0) + (int) ($item['quantity'] ?? 0);
        $totals[$slug]['line_total'] = round((float) ($totals[$slug]['line_total'] ?? 0) + (float) ($item['line_total'] ?? 0), 2);
    }

    /**
     * @param array<string,mixed> $input
     * @param array<int,array<string,mixed>> $plans
     */
    private static function build_storage_warning(array $input, array $plans): string
    {
        $users = max(1, (int) ($input['company_size'] ?? 1));
        $availableTb = 1 + (($users * 10) / 1024);
        $requestedTb = (float) ($input['tenant_storage_tb'] ?? 0);

        if ($requestedTb <= $availableTb) {
            return '';
        }

        return 'SharePoint-Storage prüfen: geschätzt verfügbar sind ca. ' . number_format($availableTb, 2, ',', '.') . ' TB (1 TB + 10 GB je Lizenz), angefragt wurden ' . number_format($requestedTb, 2, ',', '.') . ' TB.';
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     */
    private static function resolve_overall_status(array $rows, int $baseCount, bool $hasAddons, bool $hasCritical): array
    {
        if ($rows === [] || $hasCritical) {
            return ['key' => 'audit', 'label' => 'Keine belastbare Auto-Empfehlung – Audit empfohlen', 'tone' => 'danger'];
        }
        if ($baseCount > 1) {
            return ['key' => 'mixed', 'label' => 'Mischmodell aus mehreren Lizenzfamilien empfohlen', 'tone' => 'warning'];
        }
        if ($hasAddons) {
            return ['key' => 'addons', 'label' => 'Basislizenz + Add-ons empfohlen', 'tone' => 'warning'];
        }

        return ['key' => 'base', 'label' => 'Basislizenz reicht aus', 'tone' => 'success'];
    }

    /**
     * @param array<int,string> $tags
     * @param array<int,string> $needles
     */
    private static function has_any_tag(array $tags, array $needles): bool
    {
        return array_intersect($tags, $needles) !== [];
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
