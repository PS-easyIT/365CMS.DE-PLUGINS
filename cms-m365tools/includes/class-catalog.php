<?php
/**
 * CMS M365 Tools – JSON-Kataloge.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Catalog
{
    /**
     * @return array<string,mixed>
     */
    public static function rules(): array
    {
        return self::load_json('shared_mailbox_rules.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function license_matrix(): array
    {
        return self::load_json('mailbox_license_matrix.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function scenarios(): array
    {
        return self::load_json('shared_mailbox_scenarios.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function pricing(): array
    {
        $pricing = self::load_json('pricing.json');
        $catalog = self::m365_package_price_catalog();
        $products = is_array($pricing['products'] ?? null) ? $pricing['products'] : [];

        foreach (self::catalog_packages($catalog) as $package) {
            $slug = (string) ($package['slug'] ?? '');
            $price = self::package_tier_price($package, 'public');
            if ($slug === '' || $price === null) {
                continue;
            }

            $products[$slug] = $price;
        }

        if ($products !== []) {
            $pricing['products'] = $products;
        }

        $pricing['currency'] = (string) ($catalog['meta']['currency'] ?? ($pricing['currency'] ?? 'EUR'));
        $pricing['price_date'] = (string) ($catalog['meta']['price_date'] ?? ($pricing['price_date'] ?? '2026-03-17'));
        $pricing['review_note'] = 'Paketpreise werden bevorzugt aus dem CMS M365 License Seed-Katalog übernommen und können global in M365 Tools übersteuert werden.';
        $pricing['defaults']['reference_user_mailbox_monthly'] = $products['m365-business-standard'] ?? ($pricing['defaults']['reference_user_mailbox_monthly'] ?? 10.80);
        $pricing['defaults']['shared_license_monthly'] = $products['exchange-online-plan-2'] ?? ($pricing['defaults']['shared_license_monthly'] ?? 6.90);

        return $pricing;
    }

    /**
     * @return array<string,mixed>
     */
    public static function copilot_eligibility_matrix(): array
    {
        return self::load_json('copilot_eligibility_matrix.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function copilot_prerequisites(): array
    {
        return self::load_json('copilot_technical_prerequisites.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function copilot_upgrade_paths(): array
    {
        return self::load_json('license_upgrade_paths.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function copilot_pricing(): array
    {
        return self::load_json('copilot_pricing.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function copilot_readiness_rules(): array
    {
        return self::load_json('copilot_readiness_rules.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function roi_assumptions(): array
    {
        return self::load_json('roi_assumptions.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function persona_roi_presets(): array
    {
        return self::load_json('persona_roi_presets.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function plan_comparison_feature_matrix(): array
    {
        return self::load_json('plan_comparison_feature_matrix.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function plan_comparison_badges(): array
    {
        return self::load_json('plan_comparison_badges.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function plan_comparison_notes(): array
    {
        return self::load_json('plan_comparison_notes.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function commitment_pricing(): array
    {
        $pricing = self::load_json('commitment_pricing.json');
        $catalog = self::m365_package_price_catalog();
        $plans = [];
        $sourcePlans = is_array($pricing['plans'] ?? null) ? $pricing['plans'] : [];
        $existingPlans = [];

        foreach ($sourcePlans as $plan) {
            if (is_array($plan) && !empty($plan['slug'])) {
                $existingPlans[(string) $plan['slug']] = $plan;
            }
        }

        $termOptions = class_exists('CMS_M365CALCULATOR_Settings')
            ? CMS_M365CALCULATOR_Settings::global_options('terms')
            : [];
        $annualMonthlyUplift = self::option_float($termOptions, 'annual-monthly-uplift-percent', 5.0, 0.0, 200.0);
        $monthlyUplift = self::option_float($termOptions, 'monthly-uplift-percent', 20.0, 0.0, 200.0);

        foreach (self::catalog_packages($catalog) as $package) {
            $slug = (string) ($package['slug'] ?? '');
            $price = self::package_tier_price($package, 'public');
            if ($slug === '' || $price === null || $price < 0) {
                continue;
            }

            $existing = is_array($existingPlans[$slug] ?? null) ? $existingPlans[$slug] : [];
            $isEnterprise = str_contains($slug, '-e3') || str_contains($slug, '-e5') || str_contains($slug, 'enterprise');
            $plans[] = [
                'slug' => $slug,
                'name' => (string) ($package['name'] ?? ($existing['name'] ?? $slug)),
                'family' => (string) ($package['category'] ?? ($existing['family'] ?? 'M365')),
                'max_users' => $existing['max_users'] ?? (str_contains($slug, 'business') ? 300 : null),
                'annual_price_month' => round($price, 2),
                'annual_monthly_price_month' => round($price * (1 + ($annualMonthlyUplift / 100)), 2),
                'monthly_price_month' => round($price * (1 + ($monthlyUplift / 100)), 2),
                'annual_monthly_uplift_percent' => $annualMonthlyUplift,
                'monthly_uplift_percent' => $monthlyUplift,
                'terms' => $isEnterprise ? ['P1M', 'P1Y', 'P3Y'] : ['P1M', 'P1Y'],
                'supports_three_year' => $isEnterprise,
                'three_year_min_users' => $isEnterprise ? (int) ($existing['three_year_min_users'] ?? 100) : null,
                'source_note' => (string) ($package['source_note'] ?? ($existing['source_note'] ?? 'Aus dem zentralen M365-Paketkatalog abgeleitet.')),
                'sort_order' => (int) ($package['sort_order'] ?? 9999),
            ];
        }

        if ($plans !== []) {
            usort($plans, static fn(array $left, array $right): int => ((int) ($left['sort_order'] ?? 0)) <=> ((int) ($right['sort_order'] ?? 0)));
            $pricing['plans'] = $plans;
        }

        $pricing['meta']['currency'] = (string) ($catalog['meta']['currency'] ?? ($pricing['meta']['currency'] ?? 'EUR'));
        $pricing['meta']['source_checked'] = (string) ($catalog['meta']['price_date'] ?? ($pricing['meta']['source_checked'] ?? '2026-03-17'));
        $pricing['meta']['price_basis'] = 'Aus dem CMS M365 License Paketkatalog abgeleitete Public-Referenzpreise mit zentralen Laufzeitannahmen.';

        return $pricing;
    }

    /**
     * @return array<string,mixed>
     */
    public static function m365_package_price_catalog(): array
    {
        $packages = self::m365lic_package_seeds();
        $source = 'cms-m365lic';

        if ($packages === []) {
            $packages = self::fallback_package_seeds();
            $source = 'cms-m365tools';
        }

        $packages = self::apply_global_package_overrides($packages);

        usort($packages, static function (array $left, array $right): int {
            $leftOrder = (int) ($left['sort_order'] ?? 9999);
            $rightOrder = (int) ($right['sort_order'] ?? 9999);
            if ($leftOrder !== $rightOrder) {
                return $leftOrder <=> $rightOrder;
            }

            return strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
        });

        return [
            'meta' => [
                'source_plugin' => $source,
                'price_date' => '2026-03-17',
                'currency' => 'EUR',
                'tiers' => ['public', 'member', 'group'],
                'note' => 'Public-, Member- und Spezialpreise aus dem M365LIC Seed-Katalog; globale M365-Tools-Overrides haben Vorrang.',
            ],
            'billing_options' => self::m365lic_billing_options(),
            'packages' => $packages,
        ];
    }

    public static function package_price_option_key(string $slug, string $tier): string
    {
        return 'pkg-' . self::clean_key($slug) . '-' . self::clean_key($tier) . '-eur';
    }

    /**
     * @return array<string,mixed>
     */
    public static function commitment_assumptions(): array
    {
        return self::load_json('commitment_assumptions.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function commitment_channel_notes(): array
    {
        return self::load_json('commitment_channel_notes.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function archive_mailbox_plans(): array
    {
        return self::load_json('archive_mailbox_plans.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function archive_mailbox_assumptions(): array
    {
        return self::load_json('archive_mailbox_assumptions.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function addon_configurator_addons(): array
    {
        return self::load_json('addon_configurator_addons.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function addon_overlap_rules(): array
    {
        return self::load_json('addon_overlap_rules.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function offer_matrix_rules(): array
    {
        return self::load_json('offer_matrix_validation_rules.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function license_assignment_risk_rules(): array
    {
        return self::load_json('license_assignment_risk_rules.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function consumption_modules(): array
    {
        return self::load_json('consumption_modules.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function sharepoint_storage_rules(): array
    {
        return self::load_json('sharepoint_storage_rules.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function onedrive_quota_presets(): array
    {
        return self::load_json('onedrive_quota_presets.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function exchange_storage_rules(): array
    {
        return self::load_json('exchange_storage_rules.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function storage_growth_assumptions(): array
    {
        return self::load_json('storage_growth_assumptions.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function microsoft_backup_baseline(): array
    {
        return self::load_json('microsoft_backup_baseline.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function backup_providers(): array
    {
        return self::load_json('backup_providers.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function backup_comparison_rules(): array
    {
        return self::load_json('backup_comparison_rules.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function google_workspace_plans(): array
    {
        return self::load_json('google_workspace_plans.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function m365_target_plans(): array
    {
        return self::load_json('m365_target_plans.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function workspace_to_m365_mapping(): array
    {
        return self::load_json('workspace_to_m365_mapping.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function migration_defaults(): array
    {
        return self::load_json('migration_defaults.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function power_platform_products(): array
    {
        return self::load_json('power_platform_products.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function power_platform_use_cases(): array
    {
        return self::load_json('power_platform_use_cases.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function power_platform_connector_rules(): array
    {
        return self::load_json('power_platform_connector_rules.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function power_platform_capacity_catalog(): array
    {
        return self::load_json('power_platform_capacity_catalog.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function power_platform_governance_rules(): array
    {
        return self::load_json('power_platform_governance_rules.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function license_advisor_plans(): array
    {
        return self::load_json('license_advisor_plans.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function license_advisor_addons(): array
    {
        return self::load_json('license_advisor_addons.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function license_advisor_feature_matrix(): array
    {
        return self::load_json('license_advisor_feature_matrix.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function license_advisor_persona_presets(): array
    {
        return self::load_json('license_advisor_persona_presets.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function license_advisor_commercial_rules(): array
    {
        return self::load_json('license_advisor_commercial_rules.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function ai_product_catalog(): array
    {
        return self::load_json('ai_product_catalog.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function ai_use_case_matrix(): array
    {
        return self::load_json('ai_use_case_matrix.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function ai_dynamic_offers(): array
    {
        return self::load_json('ai_dynamic_offers.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function copilot_pilot_sizes(): array
    {
        return self::load_json('copilot_pilot_sizes.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function copilot_rollout_templates(): array
    {
        return self::load_json('copilot_rollout_templates.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function copilot_readiness_checklist(): array
    {
        return self::load_json('copilot_readiness_checklist.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function frontline_user_type_matrix(): array
    {
        return self::load_json('frontline_user_type_matrix.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function frontline_plan_matrix(): array
    {
        return self::load_json('frontline_plan_matrix.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function frontline_industry_presets(): array
    {
        return self::load_json('frontline_industry_presets.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function exchange_online_plans(): array
    {
        return self::load_json('exchange_online_plans.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function onprem_exchange_cost_defaults(): array
    {
        return self::load_json('onprem_exchange_cost_defaults.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function exchange_migration_velocity(): array
    {
        return self::load_json('exchange_migration_velocity.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function teams_phone_base_eligibility(): array
    {
        return self::load_json('teams_phone_base_eligibility.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function teams_pstn_model_rules(): array
    {
        return self::load_json('teams_pstn_model_rules.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function teams_country_availability(): array
    {
        return self::load_json('teams_country_availability.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function teams_voice_providers(): array
    {
        return self::load_json('teams_voice_providers.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function teams_direct_routing_requirements(): array
    {
        return self::load_json('teams_direct_routing_requirements.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function teams_phone_cost_assumptions(): array
    {
        return self::load_json('teams_phone_cost_assumptions.json');
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
    public static function license_audit_checklist(): array
    {
        return self::load_json('license_audit_checklist.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function m365_best_practice_catalog(): array
    {
        return self::load_json('m365_best_practice_catalog.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function license_audit_deeplinks(): array
    {
        return self::load_json('license_audit_deeplinks.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function audit_pdf_template(): array
    {
        return self::load_json('audit_pdf_template.json');
    }

    /**
     * @return array<string,mixed>
     */
    private static function load_json(string $file): array
    {
        static $cache = [];

        $file = basename($file);

        if (isset($cache[$file])) {
            return $cache[$file];
        }

        $path = CMS_M365CALCULATOR_PLUGIN_DIR . 'data/' . $file;
        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        $size = filesize($path);
        if ($size === false || $size > 2_097_152) {
            error_log('CMS M365 Tools rejected catalog file: ' . $file);

            return [];
        }

        $json = file_get_contents($path);
        if (!is_string($json) || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('CMS M365 Tools invalid JSON in catalog file ' . $file . ': ' . json_last_error_msg());
        }

        $cache[$file] = is_array($decoded) ? $decoded : [];

        return $cache[$file];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function catalog_packages(array $catalog): array
    {
        $packages = is_array($catalog['packages'] ?? null) ? $catalog['packages'] : [];

        return array_values(array_filter($packages, static fn(mixed $package): bool => is_array($package)));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function m365lic_package_seeds(): array
    {
        if (!self::ensure_m365lic_catalog_loaded() || !method_exists('CMS_M365LIC_Catalog', 'package_seeds')) {
            return [];
        }

        try {
            $packages = \CMS_M365LIC_Catalog::package_seeds();
        } catch (\Throwable $e) {
            return [];
        }

        return is_array($packages) ? array_values(array_filter($packages, static fn(mixed $package): bool => is_array($package))) : [];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private static function m365lic_billing_options(): array
    {
        if (self::ensure_m365lic_catalog_loaded() && method_exists('CMS_M365LIC_Catalog', 'billing_options')) {
            try {
                $options = \CMS_M365LIC_Catalog::billing_options();
                if (is_array($options) && $options !== []) {
                    return $options;
                }
            } catch (\Throwable $e) {
                // Fallback below.
            }
        }

        return [
            'annual_upfront' => ['key' => 'annual_upfront', 'label' => '1 Jahr · jährliche Zahlung', 'short_label' => 'Jahr / jährlich', 'multiplier' => 1.00],
            'annual_monthly' => ['key' => 'annual_monthly', 'label' => '1 Jahr · monatliche Zahlung (+5%)', 'short_label' => 'Jahr / monatlich', 'multiplier' => 1.05],
            'monthly_flex' => ['key' => 'monthly_flex', 'label' => '1 Monat · monatlich (+20%)', 'short_label' => 'Monat / flexibel', 'multiplier' => 1.20],
        ];
    }

    private static function ensure_m365lic_catalog_loaded(): bool
    {
        if (class_exists('CMS_M365LIC_Catalog')) {
            return true;
        }

        $isActive = defined('CMS_M365LIC_VERSION');
        if (!$isActive && class_exists('CMS\\PluginManager') && method_exists('CMS\\PluginManager', 'instance')) {
            try {
                $manager = \CMS\PluginManager::instance();
                $isActive = method_exists($manager, 'isPluginActive') && $manager->isPluginActive('cms-m365lic');
            } catch (\Throwable $e) {
                $isActive = false;
            }
        }

        if (!$isActive) {
            return false;
        }

        $candidates = [
            dirname(CMS_M365CALCULATOR_PLUGIN_DIR) . '/cms-m365lic/includes/class-catalog.php',
            (defined('ABSPATH') ? ABSPATH : dirname(CMS_M365CALCULATOR_PLUGIN_DIR, 2) . '/') . 'plugins/cms-m365lic/includes/class-catalog.php',
        ];

        foreach ($candidates as $file) {
            if (is_string($file) && self::is_safe_catalog_include($file)) {
                require_once $file;
                break;
            }
        }

        return class_exists('CMS_M365LIC_Catalog');
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function fallback_package_seeds(): array
    {
        $pricing = self::load_json('pricing.json');
        $products = is_array($pricing['products'] ?? null) ? $pricing['products'] : [];
        $packages = [];
        $order = 10;

        foreach ($products as $slug => $price) {
            $packages[] = [
                'slug' => (string) $slug,
                'name' => ucwords(str_replace('-', ' ', (string) $slug)),
                'kind' => str_contains((string) $slug, 'defender') || str_contains((string) $slug, 'archiving') ? 'addon' : 'base',
                'category' => 'm365',
                'pricing_basis' => 'per_user',
                'public_price' => is_numeric($price) ? (float) $price : null,
                'member_price' => is_numeric($price) ? (float) $price : null,
                'group_price' => is_numeric($price) ? (float) $price : null,
                'currency' => (string) ($pricing['currency'] ?? 'EUR'),
                'source_note' => 'Fallback aus dem lokalen M365-Tools-Preiskatalog.',
                'sort_order' => $order,
                'is_active' => 1,
            ];
            $order += 10;
        }

        return $packages;
    }

    /**
     * @param array<int,array<string,mixed>> $packages
     * @return array<int,array<string,mixed>>
     */
    private static function apply_global_package_overrides(array $packages): array
    {
        if (!class_exists('CMS_M365CALCULATOR_Settings')) {
            return $packages;
        }

        $baseOptions = CMS_M365CALCULATOR_Settings::global_options('base-packages');
        $addonOptions = CMS_M365CALCULATOR_Settings::global_options('addons');

        foreach ($packages as &$package) {
            $slug = (string) ($package['slug'] ?? '');
            if ($slug === '') {
                continue;
            }

            $options = (string) ($package['kind'] ?? 'base') === 'addon' ? $addonOptions : $baseOptions;
            foreach (['public' => 'public_price', 'member' => 'member_price', 'group' => 'group_price'] as $tier => $field) {
                $optionKey = self::package_price_option_key($slug, $tier);
                if (isset($options[$optionKey]) && is_numeric($options[$optionKey])) {
                    $package[$field] = round((float) $options[$optionKey], 2);
                }
            }
        }
        unset($package);

        return $packages;
    }

    private static function package_tier_price(array $package, string $tier): ?float
    {
        $field = match ($tier) {
            'member' => 'member_price',
            'group' => 'group_price',
            default => 'public_price',
        };

        $value = $package[$field] ?? null;
        if ($value === null || $value === '') {
            $value = $package['public_price'] ?? null;
        }

        return is_numeric($value) ? round((float) $value, 2) : null;
    }

    /**
     * @param array<string,string> $options
     */
    private static function option_float(array $options, string $key, float $default, float $min, float $max): float
    {
        $value = $options[$key] ?? $options[str_replace('-', '_', $key)] ?? null;
        $number = is_numeric($value) ? (float) $value : $default;

        return max($min, min($max, $number));
    }

    private static function clean_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($value)), '-');
    }

    private static function is_safe_catalog_include(string $path): bool
    {
        if (!is_file($path) || !is_readable($path)) {
            return false;
        }

        $realPath = realpath($path);
        if (!is_string($realPath) || $realPath === '') {
            return false;
        }

        $normalized = str_replace('\\', '/', strtolower($realPath));

        return str_ends_with($normalized, '/cms-m365lic/includes/class-catalog.php');
    }
}
