<?php
/**
 * CMS M365 Calculator – Add-On-Konfigurator Modul.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Addon_Configurator
{
    /**
     * @return array<string,mixed>
     */
    public static function default_input(): array
    {
        return [
            'base_plan' => 'm365-business-standard',
            'users' => 25,
            'billing_model' => 'annual',
            'channel' => 'commercial',
            'strategy' => 'upgrade_check',
            'addons' => ['m365-copilot', 'teams-phone-standard', 'power-apps-premium'],
            'pstn_provider' => 'none',
            'calling_plan' => 'none',
            'resource_accounts' => 0,
            'premium_connectors' => false,
            'dataverse_required' => false,
            'backup_gb' => 0,
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_input(array $source): array
    {
        $input = self::default_input();
        $input['base_plan'] = self::clean_key((string) ($source['base_plan'] ?? $input['base_plan']));
        $input['users'] = max(1, min(500000, (int) ($source['users'] ?? $input['users'])));
        $input['billing_model'] = self::enum((string) ($source['billing_model'] ?? 'annual'), ['annual', 'annual_monthly', 'monthly', 'three_year'], 'annual');
        $input['channel'] = self::enum((string) ($source['channel'] ?? 'commercial'), ['commercial', 'education', 'nonprofit', 'government'], 'commercial');
        $input['strategy'] = self::enum((string) ($source['strategy'] ?? 'upgrade_check'), ['keep_addons', 'upgrade_check'], 'upgrade_check');
        $input['pstn_provider'] = self::enum((string) ($source['pstn_provider'] ?? 'none'), ['none', 'microsoft', 'operator_connect', 'direct_routing', 'teams_phone_mobile'], 'none');
        $input['calling_plan'] = self::enum((string) ($source['calling_plan'] ?? 'none'), ['none', 'domestic', 'international', 'payg'], 'none');
        $input['resource_accounts'] = max(0, min(1000, (int) ($source['resource_accounts'] ?? 0)));
        $input['backup_gb'] = max(0, min(9999999, (int) ($source['backup_gb'] ?? 0)));
        $input['premium_connectors'] = self::truthy($source['premium_connectors'] ?? false);
        $input['dataverse_required'] = self::truthy($source['dataverse_required'] ?? false);

        $addonSource = is_array($source['addons'] ?? null) ? $source['addons'] : [];
        $addons = array_values(array_unique(array_filter(array_map([self::class, 'clean_key'], $addonSource))));
        if ($addons !== []) {
            $input['addons'] = $addons;
        }

        return $input;
    }

    /**
     * @return array<string,mixed>
     */
    public static function load_addon_catalog(): array
    {
        return CMS_M365CALCULATOR_Catalog::addon_configurator_addons();
    }

    /**
     * @param array<string,mixed> $addon
     * @param array<string,mixed> $plan
     * @param array<int,string> $selectedAddons
     * @param array<string,array<string,mixed>> $addonIndex
     * @return array<string,mixed>
     */
    public static function resolve_addon_prerequisites(array $addon, array $plan, array $selectedAddons, array $addonIndex = []): array
    {
        $missing = [];
        $planTags = self::values($plan, 'tags');
        $planFeatures = self::values($plan, 'features');
        $selectedFeatures = [];

        foreach ($selectedAddons as $slug) {
            if (!is_array($addonIndex[$slug] ?? null)) {
                continue;
            }
            $selectedFeatures[] = (string) ($addonIndex[$slug]['feature'] ?? '');
        }
        $selectedFeatures = array_values(array_filter($selectedFeatures));

        $requiredTags = self::rule_values($addon, 'prerequisite_tags_any');
        if ($requiredTags !== [] && array_intersect($requiredTags, $planTags) === []) {
            $missing[] = 'Kompatible Basislizenz mit einem dieser Tags fehlt: ' . implode(', ', $requiredTags);
        }

        $requiredSelected = self::rule_values($addon, 'requires_selected_addons_any');
        if ($requiredSelected !== [] && array_intersect($requiredSelected, $selectedAddons) === []) {
            $missing[] = 'Benötigtes Vor-Add-on fehlt: ' . implode(', ', $requiredSelected);
        }

        $requiredFeatureOrTag = self::rule_values($addon, 'requires_feature_or_tag_any');
        if ($requiredFeatureOrTag !== []) {
            $combined = array_values(array_unique(array_merge($planTags, $planFeatures, $selectedFeatures)));
            if (array_intersect($requiredFeatureOrTag, $combined) === []) {
                $missing[] = 'Benötigte Funktion oder Basisberechtigung fehlt: ' . implode(', ', $requiredFeatureOrTag);
            }
        }

        return [
            'ok' => $missing === [],
            'missing' => $missing,
        ];
    }

    /**
     * @param array<string,mixed> $addon
     * @param array<string,mixed> $plan
     * @return array<string,mixed>
     */
    public static function detect_redundant_addons(array $addon, array $plan): array
    {
        $planTags = self::values($plan, 'tags');
        $planFeatures = self::values($plan, 'features');
        $feature = (string) ($addon['feature'] ?? '');
        $redundantFeatures = self::rule_values($addon, 'redundant_if_features');
        $redundantTags = self::rule_values($addon, 'redundant_if_tags');

        if ($feature !== '' && in_array($feature, $planFeatures, true)) {
            return ['is_redundant' => true, 'reason' => 'Die Kernfunktion ist in der Basislizenz bereits enthalten.'];
        }

        if ($redundantFeatures !== [] && array_intersect($redundantFeatures, $planFeatures) !== []) {
            return ['is_redundant' => true, 'reason' => 'Die ausgewählte Funktion ist laut Planmerkmal bereits enthalten.'];
        }

        if ($redundantTags !== [] && array_intersect($redundantTags, $planTags) !== []) {
            return ['is_redundant' => true, 'reason' => 'Der Basisplan enthält diesen Add-on-Pfad bereits.'];
        }

        return ['is_redundant' => false, 'reason' => ''];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $basePlan
     * @param array<int,array<string,mixed>> $evaluatedAddons
     * @return array<string,mixed>|null
     */
    public static function compare_addons_vs_upgrade(array $input, array $basePlan, array $evaluatedAddons): ?array
    {
        $rules = CMS_M365CALCULATOR_Catalog::addon_overlap_rules();
        $recommendations = is_array($rules['upgrade_recommendations'] ?? null) ? $rules['upgrade_recommendations'] : [];
        $selectedSlugs = array_values(array_map(static fn(array $item): string => (string) ($item['slug'] ?? ''), $evaluatedAddons));
        $plans = self::plans_index();
        $baseSlug = (string) ($basePlan['slug'] ?? '');
        $users = (int) ($input['users'] ?? 1);
        $multiplier = self::billing_multiplier((string) ($input['billing_model'] ?? 'annual'));
        $currentMonthly = ((float) ($basePlan['price_month'] ?? 0) * $users * $multiplier)
            + self::monthly_addon_sum($evaluatedAddons);

        foreach ($recommendations as $rule) {
            if (!is_array($rule) || (string) ($rule['base_plan'] ?? '') !== $baseSlug) {
                continue;
            }

            $needed = self::rule_values($rule, 'addons_any');
            $hits = array_values(array_intersect($needed, $selectedSlugs));
            if (count($hits) < (int) ($rule['addons_min_count'] ?? 1)) {
                continue;
            }

            $targetSlug = (string) ($rule['target_plan'] ?? '');
            if (!is_array($plans[$targetSlug] ?? null)) {
                continue;
            }

            $target = $plans[$targetSlug];
            $upgradeMonthly = (float) ($target['price_month'] ?? 0) * $users * $multiplier;
            $delta = $currentMonthly - $upgradeMonthly;
            $shouldRecommend = $delta >= 0 || (string) ($input['strategy'] ?? '') === 'upgrade_check';
            if (!$shouldRecommend) {
                continue;
            }

            return [
                'base_plan' => $basePlan,
                'target_plan' => $target,
                'covered_addons' => $hits,
                'current_monthly' => $currentMonthly,
                'upgrade_monthly' => $upgradeMonthly,
                'monthly_delta' => $delta,
                'is_cheaper' => $delta >= 0,
                'reason' => (string) ($rule['reason'] ?? 'Upgrade statt Add-on-Stapel prüfen.'),
            ];
        }

        return null;
    }

    /**
     * @param array<string,mixed> $input
     * @param array<int,array<string,mixed>> $evaluatedAddons
     * @param array<string,mixed> $basePlan
     * @return array<string,mixed>
     */
    public static function calculate_addon_bundle_total(array $input, array $evaluatedAddons, array $basePlan): array
    {
        $users = (int) ($input['users'] ?? 1);
        $multiplier = self::billing_multiplier((string) ($input['billing_model'] ?? 'annual'));
        $baseMonthly = (float) ($basePlan['price_month'] ?? 0) * $users * $multiplier;
        $addonMonthly = self::monthly_addon_sum($evaluatedAddons);
        $perTenantMonthly = 0.0;
        $perUserMonthly = 0.0;
        $blockedMonthly = 0.0;

        foreach ($evaluatedAddons as $addon) {
            $status = (string) ($addon['status'] ?? 'blocked');
            $monthly = (float) ($addon['monthly_total'] ?? 0);
            if (in_array($status, ['blocked', 'redundant', 'separate'], true)) {
                $blockedMonthly += $monthly;
                continue;
            }

            if ((string) ($addon['billing_type'] ?? '') === 'per_tenant') {
                $perTenantMonthly += $monthly;
            } else {
                $perUserMonthly += $monthly;
            }
        }

        return [
            'base_monthly' => $baseMonthly,
            'addon_monthly' => $addonMonthly,
            'per_user_addon_monthly' => $perUserMonthly,
            'per_tenant_addon_monthly' => $perTenantMonthly,
            'blocked_or_redundant_monthly' => $blockedMonthly,
            'total_monthly' => $baseMonthly + $addonMonthly,
            'total_yearly' => ($baseMonthly + $addonMonthly) * 12,
            'multiplier' => $multiplier,
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @return array<int,array<string,mixed>>
     */
    public static function calculate_consumption_modules(array $input): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::consumption_modules();
        $modules = is_array($catalog['modules'] ?? null) ? $catalog['modules'] : [];
        $results = [];

        foreach ($modules as $module) {
            if (!is_array($module)) {
                continue;
            }
            $inputKey = (string) ($module['input_key'] ?? '');
            $quantity = $inputKey !== '' ? (float) ($input[$inputKey] ?? 0) : 0.0;
            if ($quantity <= 0) {
                continue;
            }

            $unitPrice = (float) ($module['unit_price'] ?? 0);
            $results[] = [
                'slug' => (string) ($module['slug'] ?? ''),
                'name' => (string) ($module['name'] ?? ''),
                'quantity' => $quantity,
                'unit_label' => (string) ($module['unit_label'] ?? 'Einheiten'),
                'unit_price' => $unitPrice,
                'currency' => (string) ($module['currency'] ?? 'EUR'),
                'monthly_total' => $quantity * $unitPrice,
                'source_note' => (string) ($module['source_note'] ?? ''),
            ];
        }

        return $results;
    }

    public static function render_addon_configurator_page(): string
    {
        return CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-addon-configurator.php';
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function evaluate(array $input): array
    {
        $plans = self::plans_index();
        $basePlan = is_array($plans[(string) ($input['base_plan'] ?? '')] ?? null)
            ? $plans[(string) $input['base_plan']]
            : reset($plans);
        $basePlan = is_array($basePlan) ? $basePlan : [];
        $catalog = self::load_addon_catalog();
        $addonIndex = self::addons_index($catalog);
        $selectedAddons = self::resolve_requested_addons($input, $addonIndex);
        $evaluated = [];

        foreach ($selectedAddons as $addonSlug) {
            if (!is_array($addonIndex[$addonSlug] ?? null)) {
                continue;
            }
            $evaluated[] = self::evaluate_addon($addonIndex[$addonSlug], $basePlan, $selectedAddons, $addonIndex, $input);
        }

        $consumption = self::calculate_consumption_modules($input);
        $totals = self::calculate_addon_bundle_total($input, $evaluated, $basePlan);
        $upgrade = self::compare_addons_vs_upgrade($input, $basePlan, $evaluated);
        $warnings = self::build_warnings($input, $basePlan, $selectedAddons);
        $rules = CMS_M365CALCULATOR_Catalog::addon_overlap_rules();

        return [
            'input' => $input,
            'base_plan' => $basePlan,
            'addons' => $evaluated,
            'selected_addons' => $selectedAddons,
            'addon_options' => self::addon_options($catalog),
            'plan_options' => self::plan_options(),
            'categories' => is_array($catalog['categories'] ?? null) ? $catalog['categories'] : [],
            'billing_options' => self::billing_options(),
            'totals' => $totals,
            'consumption' => $consumption,
            'upgrade' => $upgrade,
            'warnings' => array_values(array_merge($warnings, is_array($rules['global_warnings'] ?? null) ? $rules['global_warnings'] : [])),
            'sources' => array_values(array_unique(array_merge(
                is_array($rules['sources'] ?? null) ? $rules['sources'] : [],
                ['https://learn.microsoft.com/en-us/microsoft-365/backup/backup-overview']
            ))),
            'status_counts' => self::status_counts($evaluated),
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function plan_options(): array
    {
        $options = [];
        foreach (self::plans_index() as $slug => $plan) {
            $options[$slug] = (string) ($plan['name'] ?? $slug);
        }

        return $options;
    }

    /**
     * @param array<string,mixed>|null $catalog
     * @return array<string,array<int,array<string,mixed>>>
     */
    public static function addon_options(?array $catalog = null): array
    {
        $catalog ??= self::load_addon_catalog();
        $addons = is_array($catalog['addons'] ?? null) ? $catalog['addons'] : [];
        $grouped = [];

        foreach ($addons as $addon) {
            if (!is_array($addon) || empty($addon['slug'])) {
                continue;
            }
            $category = (string) ($addon['category'] ?? 'other');
            $grouped[$category] ??= [];
            $grouped[$category][] = $addon;
        }

        return $grouped;
    }

    /**
     * @param array<string,mixed> $addon
     * @param array<string,mixed> $basePlan
     * @param array<int,string> $selectedAddons
     * @param array<string,array<string,mixed>> $addonIndex
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    private static function evaluate_addon(array $addon, array $basePlan, array $selectedAddons, array $addonIndex, array $input): array
    {
        $prereq = self::resolve_addon_prerequisites($addon, $basePlan, $selectedAddons, $addonIndex);
        $redundancy = self::detect_redundant_addons($addon, $basePlan);
        $billingType = (string) ($addon['billing_type'] ?? 'per_user');
        $status = 'ok';
        $label = 'Add-on sinnvoll und kompatibel';
        $notes = [];

        if ($billingType === 'consumption' || $billingType === 'no_cost') {
            $status = 'separate';
            $label = 'Verbrauchs- oder Spezialfall separat kalkulieren';
        }

        if (!empty($redundancy['is_redundant'])) {
            $status = 'redundant';
            $label = 'Add-on redundant oder inkompatibel';
            $notes[] = (string) ($redundancy['reason'] ?? 'Bereits enthalten.');
        }

        if (empty($prereq['ok'])) {
            $status = 'blocked';
            $label = 'Add-on möglich, aber Voraussetzung fehlt';
            foreach ((array) ($prereq['missing'] ?? []) as $missing) {
                $notes[] = (string) $missing;
            }
        }

        foreach (self::rule_values($addon, 'hard_requirements') as $requirement) {
            $notes[] = 'Technische Voraussetzung prüfen: ' . $requirement;
        }

        if ((string) ($addon['slug'] ?? '') === 'teams-phone-standard' && (string) ($input['pstn_provider'] ?? 'none') === 'microsoft' && (string) ($input['calling_plan'] ?? 'none') === 'none') {
            $status = $status === 'ok' ? 'warning' : $status;
            $label = $status === 'warning' ? 'Add-on möglich, aber Zusatzhinweis nötig' : $label;
            $notes[] = 'Bei Microsoft als PSTN-Anbieter fehlt ein Calling Plan oder Pay-as-you-go-Pfad.';
        }

        $monthlyTotal = self::addon_monthly_total($addon, $status, (int) ($input['users'] ?? 1), self::billing_multiplier((string) ($input['billing_model'] ?? 'annual')));

        return $addon + [
            'status' => $status,
            'status_label' => $label,
            'notes' => array_values(array_unique(array_filter($notes))),
            'monthly_total' => $monthlyTotal,
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,array<string,mixed>> $addonIndex
     * @return array<int,string>
     */
    private static function resolve_requested_addons(array $input, array $addonIndex): array
    {
        $selected = array_values(array_unique(array_map('strval', $input['addons'] ?? [])));
        $messages = [];
        unset($messages);

        if (in_array('teams-phone-standard', $selected, true) && (string) ($input['pstn_provider'] ?? 'none') === 'microsoft') {
            $map = [
                'domestic' => 'teams-calling-plan-domestic',
                'international' => 'teams-calling-plan-international',
                'payg' => 'teams-calling-plan-payg',
            ];
            $callingPlan = (string) ($input['calling_plan'] ?? 'none');
            if (isset($map[$callingPlan])) {
                $selected[] = $map[$callingPlan];
            }
        }

        if ((int) ($input['resource_accounts'] ?? 0) > 0) {
            $selected[] = 'teams-phone-resource-account';
        }

        $needsPowerPremium = !empty($input['premium_connectors']) || !empty($input['dataverse_required']);
        if ($needsPowerPremium && array_intersect($selected, ['power-apps-premium', 'power-automate-premium']) === []) {
            $selected[] = 'power-apps-premium';
        }

        return array_values(array_unique(array_filter($selected, static fn(string $slug): bool => isset($addonIndex[$slug]))));
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private static function plans_index(): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::license_advisor_plans();
        $plans = is_array($catalog['plans'] ?? null) ? $catalog['plans'] : [];
        $indexed = [];
        foreach ($plans as $plan) {
            if (!is_array($plan) || empty($plan['slug'])) {
                continue;
            }
            $indexed[(string) $plan['slug']] = $plan;
        }

        return $indexed;
    }

    /**
     * @param array<string,mixed> $catalog
     * @return array<string,array<string,mixed>>
     */
    private static function addons_index(array $catalog): array
    {
        $addons = is_array($catalog['addons'] ?? null) ? $catalog['addons'] : [];
        $indexed = [];
        foreach ($addons as $addon) {
            if (!is_array($addon) || empty($addon['slug'])) {
                continue;
            }
            $indexed[(string) $addon['slug']] = $addon;
        }

        return $indexed;
    }

    /**
     * @param array<int,array<string,mixed>> $addons
     */
    private static function monthly_addon_sum(array $addons): float
    {
        $sum = 0.0;
        foreach ($addons as $addon) {
            if (in_array((string) ($addon['status'] ?? ''), ['blocked', 'redundant', 'separate'], true)) {
                continue;
            }
            $sum += (float) ($addon['monthly_total'] ?? 0);
        }

        return $sum;
    }

    /**
     * @param array<string,mixed> $addon
     */
    private static function addon_monthly_total(array $addon, string $status, int $users, float $multiplier): float
    {
        if (in_array($status, ['blocked', 'redundant', 'separate'], true)) {
            return 0.0;
        }

        $price = (float) ($addon['price_month'] ?? 0);
        return match ((string) ($addon['billing_type'] ?? 'per_user')) {
            'per_tenant' => $price * $multiplier,
            'no_cost', 'consumption' => 0.0,
            default => $price * $users * $multiplier,
        };
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $basePlan
     * @param array<int,string> $selectedAddons
     * @return array<int,string>
     */
    private static function build_warnings(array $input, array $basePlan, array $selectedAddons): array
    {
        $warnings = [];
        $users = (int) ($input['users'] ?? 0);
        if ((int) ($basePlan['max_users'] ?? 0) === 300 && $users > 300) {
            $warnings[] = 'Die gewählte Business-Basislizenz ist auf 300 Benutzer begrenzt; Nutzerzahl oder Zielplan prüfen.';
        }

        $terms = self::values($basePlan, 'terms');
        $termMap = ['annual' => 'P1Y', 'annual_monthly' => 'P1Y', 'monthly' => 'P1M', 'three_year' => 'P3Y'];
        $selectedTerm = $termMap[(string) ($input['billing_model'] ?? 'annual')] ?? 'P1Y';
        if ($terms !== [] && !in_array($selectedTerm, $terms, true)) {
            $warnings[] = 'Das gewählte Laufzeitmodell ist im gepflegten Plan-Katalog nicht für diese Basislizenz hinterlegt.';
        }

        if (in_array('teams-phone-standard', $selectedAddons, true) && (string) ($input['pstn_provider'] ?? 'none') === 'none') {
            $warnings[] = 'Teams Phone wurde gewählt, aber kein PSTN-Pfad. Für externe Telefonie Microsoft Calling Plan, Operator Connect, Teams Phone Mobile oder Direct Routing festlegen.';
        }

        if ((string) ($input['channel'] ?? 'commercial') !== 'commercial') {
            $warnings[] = 'Spezialsegmente wie Education, Nonprofit oder Government haben eigene Preislisten und Verfügbarkeiten.';
        }

        return $warnings;
    }

    /**
     * @param array<int,array<string,mixed>> $addons
     * @return array<string,int>
     */
    private static function status_counts(array $addons): array
    {
        $counts = ['ok' => 0, 'warning' => 0, 'blocked' => 0, 'redundant' => 0, 'separate' => 0];
        foreach ($addons as $addon) {
            $status = (string) ($addon['status'] ?? 'blocked');
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private static function billing_options(): array
    {
        $rules = CMS_M365CALCULATOR_Catalog::license_advisor_commercial_rules();
        return is_array($rules['billing'] ?? null) ? $rules['billing'] : [];
    }

    private static function billing_multiplier(string $billingModel): float
    {
        $options = self::billing_options();
        return (float) ($options[$billingModel]['multiplier'] ?? 1.0);
    }

    /**
     * @param array<string,mixed> $source
     * @return array<int,string>
     */
    private static function values(array $source, string $key): array
    {
        return array_values(array_map('strval', is_array($source[$key] ?? null) ? $source[$key] : []));
    }

    /**
     * @param array<string,mixed> $source
     * @return array<int,string>
     */
    private static function rule_values(array $source, string $key): array
    {
        return array_values(array_filter(array_map('strval', is_array($source[$key] ?? null) ? $source[$key] : [])));
    }

    private static function enum(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private static function clean_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($value)), '-');
    }

    private static function truthy(mixed $value): bool
    {
        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }
}
