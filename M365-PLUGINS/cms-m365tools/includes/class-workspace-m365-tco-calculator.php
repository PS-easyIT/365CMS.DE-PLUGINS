<?php
/**
 * CMS M365 Tools – Google Workspace / Microsoft 365 TCO-Rechner.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Workspace_M365_TCO_Calculator
{
    /**
     * @return array<string,mixed>
     */
    public static function default_input(): array
    {
        $defaultsCatalog = CMS_M365CALCULATOR_Catalog::migration_defaults();
        $defaults = is_array($defaultsCatalog['defaults'] ?? null) ? $defaultsCatalog['defaults'] : [];

        return [
            'direction' => (string) ($defaults['direction'] ?? 'google_to_m365'),
            'users' => (int) ($defaults['users'] ?? 120),
            'analysis_months' => (int) ($defaults['analysis_months'] ?? 36),
            'workspace_plan' => (string) ($defaults['workspace_plan'] ?? 'business_standard'),
            'm365_plan' => (string) ($defaults['m365_plan'] ?? 'auto'),
            'google_addons_monthly_per_user' => (float) ($defaults['google_addons_monthly_per_user'] ?? 0),
            'm365_addons_monthly_per_user' => (float) ($defaults['m365_addons_monthly_per_user'] ?? 0),
            'migration_cost_per_user' => (float) ($defaults['migration_cost_per_user'] ?? 95),
            'training_cost_per_user' => (float) ($defaults['training_cost_per_user'] ?? 60),
            'change_cost_per_user' => (float) ($defaults['change_cost_per_user'] ?? 35),
            'project_base_cost' => (float) ($defaults['project_base_cost'] ?? 2500),
            'admin_extra_monthly' => (float) ($defaults['admin_extra_monthly'] ?? 750),
            'hypercare_months' => (int) ($defaults['hypercare_months'] ?? 2),
            'parallel_months' => (int) ($defaults['parallel_months'] ?? 2),
            'parallel_cost_percent' => (float) ($defaults['parallel_cost_percent'] ?? 50),
            'security_need' => (string) ($defaults['security_need'] ?? 'standard'),
            'storage_need' => (string) ($defaults['storage_need'] ?? 'standard'),
            'ai_need' => (string) ($defaults['ai_need'] ?? 'standard'),
            'special_controls_required' => (int) ($defaults['special_controls_required'] ?? 0),
            'google_addons_unknown' => (int) ($defaults['google_addons_unknown'] ?? 0),
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_input(array $source): array
    {
        $input = self::default_input();
        $defaultsCatalog = CMS_M365CALCULATOR_Catalog::migration_defaults();
        $workspaceCatalog = CMS_M365CALCULATOR_Catalog::google_workspace_plans();
        $m365Catalog = CMS_M365CALCULATOR_Catalog::m365_target_plans();
        $mapping = CMS_M365CALCULATOR_Catalog::workspace_to_m365_mapping();
        $limits = is_array($defaultsCatalog['limits'] ?? null) ? $defaultsCatalog['limits'] : [];
        $analysisOptions = array_map('intval', is_array($limits['analysis_month_options'] ?? null) ? $limits['analysis_month_options'] : [12, 24, 36, 48, 60]);
        $workspacePlans = array_keys(is_array($workspaceCatalog['plans'] ?? null) ? $workspaceCatalog['plans'] : []);
        $m365Plans = array_merge(['auto'], array_keys(is_array($m365Catalog['plans'] ?? null) ? $m365Catalog['plans'] : []));
        $requirementOptions = is_array($mapping['requirements'] ?? null) ? $mapping['requirements'] : [];

        $input['direction'] = self::enum((string) ($source['direction'] ?? $input['direction']), ['google_to_m365', 'm365_to_google'], 'google_to_m365');
        $input['users'] = self::int_value($source['users'] ?? $input['users'], (int) ($limits['users_min'] ?? 1), (int) ($limits['users_max'] ?? 500000));
        $selectedMonths = (int) ($source['analysis_months'] ?? $input['analysis_months']);
        $input['analysis_months'] = in_array($selectedMonths, $analysisOptions, true) ? $selectedMonths : 36;
        $input['workspace_plan'] = self::enum((string) ($source['workspace_plan'] ?? $input['workspace_plan']), $workspacePlans, 'business_standard');
        $input['m365_plan'] = self::enum((string) ($source['m365_plan'] ?? $input['m365_plan']), $m365Plans, 'auto');
        $input['google_addons_monthly_per_user'] = self::float_value($source['google_addons_monthly_per_user'] ?? $input['google_addons_monthly_per_user'], 0, (float) ($limits['cost_max'] ?? 1000000));
        $input['m365_addons_monthly_per_user'] = self::float_value($source['m365_addons_monthly_per_user'] ?? $input['m365_addons_monthly_per_user'], 0, (float) ($limits['cost_max'] ?? 1000000));
        $input['migration_cost_per_user'] = self::float_value($source['migration_cost_per_user'] ?? $input['migration_cost_per_user'], 0, (float) ($limits['cost_max'] ?? 1000000));
        $input['training_cost_per_user'] = self::float_value($source['training_cost_per_user'] ?? $input['training_cost_per_user'], 0, (float) ($limits['cost_max'] ?? 1000000));
        $input['change_cost_per_user'] = self::float_value($source['change_cost_per_user'] ?? $input['change_cost_per_user'], 0, (float) ($limits['cost_max'] ?? 1000000));
        $input['project_base_cost'] = self::float_value($source['project_base_cost'] ?? $input['project_base_cost'], 0, (float) ($limits['cost_max'] ?? 1000000));
        $input['admin_extra_monthly'] = self::float_value($source['admin_extra_monthly'] ?? $input['admin_extra_monthly'], 0, (float) ($limits['cost_max'] ?? 1000000));
        $input['hypercare_months'] = self::int_value($source['hypercare_months'] ?? $input['hypercare_months'], 0, (int) ($limits['month_max'] ?? 24));
        $input['parallel_months'] = self::int_value($source['parallel_months'] ?? $input['parallel_months'], 0, (int) ($limits['month_max'] ?? 24));
        $input['parallel_cost_percent'] = self::float_value($source['parallel_cost_percent'] ?? $input['parallel_cost_percent'], 0, (float) ($limits['percent_max'] ?? 100));
        $input['security_need'] = self::enum((string) ($source['security_need'] ?? $input['security_need']), array_keys((array) ($requirementOptions['security_need_options'] ?? [])), 'standard');
        $input['storage_need'] = self::enum((string) ($source['storage_need'] ?? $input['storage_need']), array_keys((array) ($requirementOptions['storage_need_options'] ?? [])), 'standard');
        $input['ai_need'] = self::enum((string) ($source['ai_need'] ?? $input['ai_need']), array_keys((array) ($requirementOptions['ai_need_options'] ?? [])), 'standard');
        $input['special_controls_required'] = self::bool_int($source['special_controls_required'] ?? $input['special_controls_required']);
        $input['google_addons_unknown'] = self::bool_int($source['google_addons_unknown'] ?? $input['google_addons_unknown']);

        return $input;
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function evaluate(array $input): array
    {
        $input = self::normalize_input($input);
        $workspaceCatalog = CMS_M365CALCULATOR_Catalog::google_workspace_plans();
        $m365Catalog = CMS_M365CALCULATOR_Catalog::m365_target_plans();
        $mappingCatalog = CMS_M365CALCULATOR_Catalog::workspace_to_m365_mapping();
        $defaultsCatalog = CMS_M365CALCULATOR_Catalog::migration_defaults();
        $mapping = self::map_workspace_to_m365_plans($input, $workspaceCatalog, $m365Catalog, $mappingCatalog);
        $workspacePlanKey = (string) ($mapping['workspace_plan'] ?? $input['workspace_plan']);
        $m365PlanKey = (string) ($mapping['m365_plan'] ?? 'm365-business-standard');
        $workspacePlans = is_array($workspaceCatalog['plans'] ?? null) ? $workspaceCatalog['plans'] : [];
        $m365Plans = is_array($m365Catalog['plans'] ?? null) ? $m365Catalog['plans'] : [];
        $workspacePlan = is_array($workspacePlans[$workspacePlanKey] ?? null) ? $workspacePlans[$workspacePlanKey] : [];
        $m365Plan = is_array($m365Plans[$m365PlanKey] ?? null) ? $m365Plans[$m365PlanKey] : [];
        $workspaceMonthly = self::monthly_platform_cost($workspacePlan, (int) $input['users'], (float) $input['google_addons_monthly_per_user']);
        $m365Monthly = self::monthly_platform_cost($m365Plan, (int) $input['users'], (float) $input['m365_addons_monthly_per_user']);
        $migration = self::build_workspace_migration_assumptions($input, $workspaceMonthly, $m365Monthly, $defaultsCatalog);
        $workspace = self::calculate_workspace_tco($input, $workspacePlan, $migration);
        $m365 = self::calculate_m365_tco($input, $m365Plan, $migration);
        $comparison = self::compare_workspace_m365_tco($input, $workspace, $m365, $mappingCatalog, $migration);

        return [
            'input' => $input,
            'mapping' => $mapping,
            'workspace' => $workspace,
            'm365' => $m365,
            'migration' => $migration,
            'comparison' => $comparison,
            'timeline' => self::build_timeline($input, $workspace, $m365),
            'warnings' => self::build_warnings($input, $workspace, $m365, $mapping, $defaultsCatalog),
            'next_steps' => self::build_next_steps($input, $comparison),
            'workspace_plan_options' => self::workspace_plan_options($workspaceCatalog),
            'm365_plan_options' => self::m365_plan_options($m365Catalog),
            'direction_options' => is_array($defaultsCatalog['direction_options'] ?? null) ? $defaultsCatalog['direction_options'] : [],
            'security_need_options' => (array) ($mappingCatalog['requirements']['security_need_options'] ?? []),
            'storage_need_options' => (array) ($mappingCatalog['requirements']['storage_need_options'] ?? []),
            'ai_need_options' => (array) ($mappingCatalog['requirements']['ai_need_options'] ?? []),
            'migration_facts' => is_array($defaultsCatalog['migration_facts'] ?? null) ? $defaultsCatalog['migration_facts'] : [],
            'meta' => [
                'source_checked' => self::source_checked([$workspaceCatalog, $m365Catalog, $mappingCatalog, $defaultsCatalog]),
                'price_basis' => (string) ($defaultsCatalog['meta']['price_basis'] ?? $workspaceCatalog['meta']['price_basis'] ?? ''),
                'currency' => (string) ($workspaceCatalog['meta']['currency'] ?? 'EUR'),
            ],
            'sources' => self::sources([$workspaceCatalog, $m365Catalog, $mappingCatalog, $defaultsCatalog]),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $workspaceCatalog
     * @param array<string,mixed> $m365Catalog
     * @param array<string,mixed> $mappingCatalog
     * @return array<string,mixed>
     */
    public static function map_workspace_to_m365_plans(array $input, array $workspaceCatalog, array $m365Catalog, array $mappingCatalog): array
    {
        $direction = (string) ($input['direction'] ?? 'google_to_m365');
        $workspacePlan = (string) ($input['workspace_plan'] ?? 'business_standard');
        $m365Plan = (string) ($input['m365_plan'] ?? 'auto');
        $reason = '';

        if ($direction === 'google_to_m365') {
            $map = is_array($mappingCatalog['workspace_to_m365'][$workspacePlan] ?? null) ? $mappingCatalog['workspace_to_m365'][$workspacePlan] : [];
            if ($m365Plan === 'auto') {
                $m365Plan = (string) ($map['default'] ?? 'm365-business-standard');
                if ((int) ($input['users'] ?? 0) > 300 || (int) ($input['special_controls_required'] ?? 0) === 1) {
                    $m365Plan = (string) ($map['enterprise'] ?? $m365Plan);
                }
                if ((string) ($input['security_need'] ?? '') === 'high') {
                    $m365Plan = (string) ($map['high_security'] ?? $map['security'] ?? $m365Plan);
                }
            }
            $reason = (string) ($map['reason'] ?? 'Zielplan wurde aus Plan, Nutzerzahl und Anforderungen abgeleitet.');
        } else {
            $effectiveM365 = $m365Plan === 'auto' ? 'm365-business-standard' : $m365Plan;
            $map = is_array($mappingCatalog['m365_to_workspace'][$effectiveM365] ?? null) ? $mappingCatalog['m365_to_workspace'][$effectiveM365] : [];
            if ((string) ($input['storage_need'] ?? '') === 'high') {
                $workspacePlan = (string) ($map['storage'] ?? $workspacePlan);
            } elseif ((int) ($input['users'] ?? 0) > 300 || (int) ($input['special_controls_required'] ?? 0) === 1) {
                $workspacePlan = (string) ($map['enterprise'] ?? $map['default'] ?? $workspacePlan);
            } else {
                $workspacePlan = (string) ($map['default'] ?? $workspacePlan);
            }
            $m365Plan = $effectiveM365;
            $reason = (string) ($map['reason'] ?? 'Workspace-Zielplan wurde aus Microsoft-365-Ausgangsplan und Anforderungen abgeleitet.');
        }

        $workspacePlans = is_array($workspaceCatalog['plans'] ?? null) ? $workspaceCatalog['plans'] : [];
        $m365Plans = is_array($m365Catalog['plans'] ?? null) ? $m365Catalog['plans'] : [];
        if (!is_array($workspacePlans[$workspacePlan] ?? null)) {
            $workspacePlan = 'business_standard';
        }
        if (!is_array($m365Plans[$m365Plan] ?? null)) {
            $m365Plan = 'm365-business-standard';
        }

        return [
            'direction' => $direction,
            'workspace_plan' => $workspacePlan,
            'm365_plan' => $m365Plan,
            'workspace_name' => (string) ($workspacePlans[$workspacePlan]['name'] ?? $workspacePlan),
            'm365_name' => (string) ($m365Plans[$m365Plan]['name'] ?? $m365Plan),
            'reason' => $reason,
            'is_auto_mapped' => (string) ($input['m365_plan'] ?? 'auto') === 'auto' || $direction === 'm365_to_google',
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $plan
     * @param array<string,mixed> $migration
     * @return array<string,mixed>
     */
    public static function calculate_workspace_tco(array $input, array $plan, array $migration): array
    {
        $users = (int) ($input['users'] ?? 1);
        $months = (int) ($input['analysis_months'] ?? 36);
        $addonsMonthly = $users * (float) ($input['google_addons_monthly_per_user'] ?? 0);
        $licenseMonthly = $users * (float) ($plan['monthly_price'] ?? 0);
        $monthly = $licenseMonthly + $addonsMonthly;
        $project = (string) ($input['direction'] ?? '') === 'm365_to_google' ? (float) ($migration['total_project_cost'] ?? 0) : 0.0;

        return self::platform_tco('google_workspace', $plan, $licenseMonthly, $addonsMonthly, $monthly, $months, $project);
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $plan
     * @param array<string,mixed> $migration
     * @return array<string,mixed>
     */
    public static function calculate_m365_tco(array $input, array $plan, array $migration): array
    {
        $users = (int) ($input['users'] ?? 1);
        $months = (int) ($input['analysis_months'] ?? 36);
        $addonsMonthly = $users * (float) ($input['m365_addons_monthly_per_user'] ?? 0);
        $licenseMonthly = $users * (float) ($plan['monthly_price'] ?? 0);
        $monthly = $licenseMonthly + $addonsMonthly;
        $project = (string) ($input['direction'] ?? '') === 'google_to_m365' ? (float) ($migration['total_project_cost'] ?? 0) : 0.0;

        return self::platform_tco('microsoft_365', $plan, $licenseMonthly, $addonsMonthly, $monthly, $months, $project);
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $workspace
     * @param array<string,mixed> $m365
     * @param array<string,mixed> $mappingCatalog
     * @param array<string,mixed> $migration
     * @return array<string,mixed>
     */
    public static function compare_workspace_m365_tco(array $input, array $workspace, array $m365, array $mappingCatalog, array $migration): array
    {
        $recommendations = is_array($mappingCatalog['recommendations'] ?? null) ? $mappingCatalog['recommendations'] : [];
        $workspaceTotal = (float) ($workspace['total'] ?? 0);
        $m365Total = (float) ($m365['total'] ?? 0);
        $workspaceMonthly = (float) ($workspace['monthly_total'] ?? 0);
        $m365Monthly = (float) ($m365['monthly_total'] ?? 0);
        $delta = $m365Total - $workspaceTotal;
        $deltaPercent = min($workspaceTotal, $m365Total) > 0 ? abs($delta) / min($workspaceTotal, $m365Total) * 100 : 0.0;
        $direction = (string) ($input['direction'] ?? 'google_to_m365');
        $movingToM365 = $direction === 'google_to_m365';
        $destinationMonthly = $movingToM365 ? $m365Monthly : $workspaceMonthly;
        $sourceMonthly = $movingToM365 ? $workspaceMonthly : $m365Monthly;
        $monthlySavings = $sourceMonthly - $destinationMonthly;
        $breakEvenMonths = $monthlySavings > 0 ? (int) ceil(((float) ($migration['total_project_cost'] ?? 0)) / $monthlySavings) : null;
        $key = 'similar';

        if ((int) ($input['google_addons_unknown'] ?? 0) === 1 || ((int) ($input['special_controls_required'] ?? 0) === 1 && (string) ($input['security_need'] ?? '') === 'enterprise')) {
            $key = 'manual_review';
        } elseif ($deltaPercent <= 8.0) {
            $key = 'similar';
        } elseif ($movingToM365 && $monthlySavings > 0 && $m365Total > $workspaceTotal && $breakEvenMonths !== null) {
            $key = 'delayed_break_even';
        } elseif (!$movingToM365 && $monthlySavings > 0 && $workspaceTotal > $m365Total && $breakEvenMonths !== null) {
            $key = 'delayed_break_even';
        } elseif ($delta < 0) {
            $key = 'm365_cheaper';
        } elseif ($delta > 0 && $movingToM365) {
            $key = 'google_short_term';
        } elseif ($delta > 0) {
            $key = 'google_cheaper';
        }

        $entry = is_array($recommendations[$key] ?? null) ? $recommendations[$key] : [];

        return [
            'key' => $key,
            'label' => (string) ($entry['label'] ?? 'TCO vergleichen'),
            'text' => (string) ($entry['text'] ?? 'Die Kosten liegen nah beieinander und sollten fachlich bewertet werden.'),
            'tone' => (string) ($entry['tone'] ?? 'info'),
            'winner' => $delta < 0 ? 'microsoft_365' : ($delta > 0 ? 'google_workspace' : 'tie'),
            'delta' => $delta,
            'delta_abs' => abs($delta),
            'delta_percent' => $deltaPercent,
            'monthly_delta' => $m365Monthly - $workspaceMonthly,
            'break_even_months' => $breakEvenMonths,
            'monthly_savings_if_moving' => max(0.0, $monthlySavings),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $defaultsCatalog
     * @return array<string,mixed>
     */
    public static function build_workspace_migration_assumptions(array $input, float $workspaceMonthly, float $m365Monthly, array $defaultsCatalog = []): array
    {
        $users = (int) ($input['users'] ?? 1);
        $bands = is_array($defaultsCatalog['complexity_bands'] ?? null) ? $defaultsCatalog['complexity_bands'] : [];
        $band = self::complexity_band($users, $bands);
        $factor = (float) ($band['factor'] ?? 1.0);
        $peopleCost = $users * ((float) ($input['migration_cost_per_user'] ?? 0) + (float) ($input['training_cost_per_user'] ?? 0) + (float) ($input['change_cost_per_user'] ?? 0));
        $oneTime = ((float) ($input['project_base_cost'] ?? 0) + $peopleCost) * $factor;
        $admin = (float) ($input['admin_extra_monthly'] ?? 0) * (int) ($input['hypercare_months'] ?? 0);
        $sourceMonthly = (string) ($input['direction'] ?? '') === 'google_to_m365' ? $workspaceMonthly : $m365Monthly;
        $parallel = $sourceMonthly * (int) ($input['parallel_months'] ?? 0) * ((float) ($input['parallel_cost_percent'] ?? 0) / 100);

        return [
            'complexity_key' => (string) ($band['key'] ?? 'standard'),
            'complexity_label' => (string) ($band['label'] ?? 'Standardprojekt'),
            'complexity_factor' => $factor,
            'one_time_project_cost' => $oneTime,
            'admin_extra_cost' => $admin,
            'parallel_cost' => $parallel,
            'total_project_cost' => $oneTime + $admin + $parallel,
            'parallel_months' => (int) ($input['parallel_months'] ?? 0),
            'hypercare_months' => (int) ($input['hypercare_months'] ?? 0),
            'source_monthly' => $sourceMonthly,
        ];
    }

    public static function render_workspace_tco_page(): string
    {
        return CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-workspace-m365-tco-calculator.php';
    }

    /** @return array<string,mixed> */
    public static function load_google_workspace_plans(): array
    {
        return CMS_M365CALCULATOR_Catalog::google_workspace_plans();
    }

    /** @return array<string,mixed> */
    public static function load_m365_target_plans(): array
    {
        return CMS_M365CALCULATOR_Catalog::m365_target_plans();
    }

    /** @return array<string,mixed> */
    public static function load_workspace_to_m365_mapping(): array
    {
        return CMS_M365CALCULATOR_Catalog::workspace_to_m365_mapping();
    }

    /** @return array<string,mixed> */
    public static function load_migration_defaults(): array
    {
        return CMS_M365CALCULATOR_Catalog::migration_defaults();
    }

    /**
     * @param array<string,mixed> $plan
     * @return array<string,mixed>
     */
    private static function platform_tco(string $key, array $plan, float $licenseMonthly, float $addonsMonthly, float $monthly, int $months, float $project): array
    {
        $recurring = $monthly * $months;

        return [
            'key' => $key,
            'plan_name' => (string) ($plan['name'] ?? ''),
            'plan_segment' => (string) ($plan['segment'] ?? ''),
            'unit_price' => (float) ($plan['monthly_price'] ?? 0),
            'license_monthly' => $licenseMonthly,
            'addons_monthly' => $addonsMonthly,
            'monthly_total' => $monthly,
            'recurring_total' => $recurring,
            'project_total' => $project,
            'total' => $recurring + $project,
            'storage_gb_per_user' => (float) ($plan['storage_gb_per_user'] ?? 0),
            'mailbox_gb' => (float) ($plan['mailbox_gb'] ?? 0),
            'user_limit' => (int) ($plan['user_limit'] ?? 0),
            'security_level' => (string) ($plan['security_level'] ?? ''),
            'ai_level' => (string) ($plan['ai_level'] ?? ''),
            'summary' => (string) ($plan['summary'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $plan
     */
    private static function monthly_platform_cost(array $plan, int $users, float $addonsPerUser): float
    {
        return $users * ((float) ($plan['monthly_price'] ?? 0) + $addonsPerUser);
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $workspace
     * @param array<string,mixed> $m365
     * @return array<int,array<string,mixed>>
     */
    private static function build_timeline(array $input, array $workspace, array $m365): array
    {
        $months = (int) ($input['analysis_months'] ?? 36);
        $points = array_values(array_unique(array_filter([0, 12, 24, 36, 48, 60], static fn(int $month): bool => $month <= $months)));
        if (!in_array($months, $points, true)) {
            $points[] = $months;
            sort($points);
        }
        $rows = [];
        foreach ($points as $month) {
            $rows[] = [
                'month' => $month,
                'workspace_total' => ((float) ($workspace['monthly_total'] ?? 0) * $month) + ($month > 0 ? (float) ($workspace['project_total'] ?? 0) : 0),
                'm365_total' => ((float) ($m365['monthly_total'] ?? 0) * $month) + ($month > 0 ? (float) ($m365['project_total'] ?? 0) : 0),
            ];
        }

        return $rows;
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $workspace
     * @param array<string,mixed> $m365
     * @param array<string,mixed> $mapping
     * @param array<string,mixed> $defaultsCatalog
     * @return array<int,string>
     */
    private static function build_warnings(array $input, array $workspace, array $m365, array $mapping, array $defaultsCatalog): array
    {
        $warnings = [];
        $users = (int) ($input['users'] ?? 0);
        if ($users > 300 && (int) ($workspace['user_limit'] ?? 0) === 300) {
            $warnings[] = 'Google Business Starter, Standard und Plus sind laut Google für Unternehmen bis 300 Nutzer ausgelegt.';
        }
        if ($users > 300 && (int) ($m365['user_limit'] ?? 0) === 300) {
            $warnings[] = 'Microsoft Business-Pläne sollten bei mehr als 300 Nutzern gegen Enterprise-Zielpläne gespiegelt werden.';
        }
        if ((int) ($input['google_addons_unknown'] ?? 0) === 1) {
            $warnings[] = 'Unklare Workspace-Add-ons können den Vergleich verschieben und sollten vor der Entscheidung katalogisiert werden.';
        }
        if ((int) ($input['special_controls_required'] ?? 0) === 1) {
            $warnings[] = 'Spezielle Compliance-, Admin- oder Datenresidenzanforderungen brauchen eine Funktionsprüfung je Vertrag.';
        }
        if ((string) ($input['storage_need'] ?? '') === 'high' && (float) ($m365['storage_gb_per_user'] ?? 0) < (float) ($workspace['storage_gb_per_user'] ?? 0)) {
            $warnings[] = 'Die Storage-Modelle unterscheiden sich deutlich; SharePoint-Pool, OneDrive-Quota und Workspace-Speicher sind nicht 1:1 vergleichbar.';
        }
        $mrmText = (string) ($defaultsCatalog['migration_facts']['mrm_archive_policy_review'] ?? '');
        if ($mrmText !== '' && (string) ($input['direction'] ?? '') === 'google_to_m365') {
            $warnings[] = $mrmText;
        }
        if ((bool) ($mapping['is_auto_mapped'] ?? false)) {
            $warnings[] = 'Das Planmapping wurde automatisch abgeleitet und sollte bei Angeboten fachlich bestätigt werden.';
        }

        return array_values(array_unique($warnings));
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $comparison
     * @return array<int,string>
     */
    private static function build_next_steps(array $input, array $comparison): array
    {
        $steps = [
            'Planpreise mit CSP-, EA- oder Herstellerkonditionen ersetzen.',
            'Pilotgruppe, Datenumfang, Identitätsmodell und Mailrouting vor dem Projektstart validieren.',
            'Schulung, Change-Kommunikation und Hypercare als eigene Kostenblöcke planen.',
        ];

        if ((string) ($comparison['key'] ?? '') === 'manual_review') {
            array_unshift($steps, 'Add-ons, Enterprise-Sonderfälle und Security-/Compliance-Anforderungen vor einer Entscheidung aufnehmen.');
        }
        if ((string) ($input['direction'] ?? '') === 'google_to_m365') {
            $steps[] = 'Google-Workspace-Migration in Batches planen und Abschlussfenster mit inkrementeller Synchronisierung berücksichtigen.';
        }

        return array_values(array_unique($steps));
    }

    /**
     * @param array<string,mixed> $workspaceCatalog
     * @return array<string,string>
     */
    private static function workspace_plan_options(array $workspaceCatalog): array
    {
        $options = is_array($workspaceCatalog['plan_options'] ?? null) ? $workspaceCatalog['plan_options'] : [];
        $normalized = [];
        foreach ($options as $key => $label) {
            $normalized[(string) $key] = (string) $label;
        }

        return $normalized;
    }

    /**
     * @param array<string,mixed> $m365Catalog
     * @return array<string,string>
     */
    private static function m365_plan_options(array $m365Catalog): array
    {
        $options = is_array($m365Catalog['plan_options'] ?? null) ? $m365Catalog['plan_options'] : [];
        $normalized = [];
        foreach ($options as $key => $label) {
            $normalized[(string) $key] = (string) $label;
        }

        return $normalized;
    }

    /**
     * @param array<string,mixed> $bands
     * @return array<string,mixed>
     */
    private static function complexity_band(int $users, array $bands): array
    {
        foreach ($bands as $key => $band) {
            if (!is_array($band)) {
                continue;
            }
            if ($users <= (int) ($band['max_users'] ?? PHP_INT_MAX)) {
                $band['key'] = (string) $key;
                return $band;
            }
        }

        return ['key' => 'standard', 'label' => 'Standardprojekt', 'factor' => 1.0];
    }

    /**
     * @param array<int,array<string,mixed>> $catalogs
     * @return array<int,string>
     */
    private static function sources(array $catalogs): array
    {
        $sources = [];
        foreach ($catalogs as $catalog) {
            foreach ((array) ($catalog['meta']['sources'] ?? []) as $source) {
                $sources[] = (string) $source;
            }
        }

        return array_values(array_unique(array_filter($sources)));
    }

    /**
     * @param array<int,array<string,mixed>> $catalogs
     */
    private static function source_checked(array $catalogs): string
    {
        $dates = [];
        foreach ($catalogs as $catalog) {
            $date = (string) ($catalog['meta']['source_checked'] ?? '');
            if ($date !== '') {
                $dates[] = $date;
            }
        }
        rsort($dates);

        return $dates[0] ?? '2026-05-17';
    }

    private static function int_value(mixed $value, int $min, int $max): int
    {
        $number = is_numeric($value) ? (int) $value : $min;
        return max($min, min($max, $number));
    }

    private static function float_value(mixed $value, float $min, float $max): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }
        $number = is_numeric($value) ? (float) $value : $min;

        return max($min, min($max, $number));
    }

    private static function bool_int(mixed $value): int
    {
        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true) ? 1 : 0;
    }

    /** @param array<int,string> $allowed */
    private static function enum(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
