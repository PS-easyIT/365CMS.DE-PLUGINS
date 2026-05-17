<?php
/**
 * CMS M365 Tools – On-Premise Exchange zu Exchange Online ROI.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Exchange_Online_ROI_Calculator
{
    /**
     * @return array<string,mixed>
     */
    public static function default_input(): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::onprem_exchange_cost_defaults();
        $defaults = is_array($catalog['defaults'] ?? null) ? $catalog['defaults'] : [];

        return array_merge([
            'mailboxes' => 250,
            'average_mailbox_gb' => 18,
            'servers' => 2,
            'server_hardware_cost' => 28000,
            'exchange_windows_license_cost' => 12000,
            'storage_backup_monthly' => 950,
            'power_hosting_monthly' => 420,
            'maintenance_monthly' => 650,
            'admin_hours_monthly' => 18,
            'admin_hourly_rate' => 95,
            'ha_dr_monthly' => 700,
            'monitoring_tools_monthly' => 350,
            'certificates_annual' => 450,
            'hardware_refresh_due_months' => 12,
            'migration_cost' => 18000,
            'coexistence_months' => 3,
            'analysis_years' => 5,
            'cloud_plan' => 'exchange_online_p2',
            'migration_method' => 'hybrid',
            'data_quality' => 'estimated',
            'archive_compliance_required' => true,
            'hybrid_required' => true,
            'org_wants_fast_exit' => false,
            'public_folders_or_legacy_apps' => false,
            'backup_reduction_monthly' => 350,
        ], $defaults);
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_input(array $source): array
    {
        $defaults = self::default_input();
        $plans = CMS_M365CALCULATOR_Catalog::exchange_online_plans();
        $planKeys = array_keys(is_array($plans['plans'] ?? null) ? $plans['plans'] : []);
        $velocity = CMS_M365CALCULATOR_Catalog::exchange_migration_velocity();
        $methodKeys = array_keys(is_array($velocity['methods'] ?? null) ? $velocity['methods'] : []);
        $costDefaults = CMS_M365CALCULATOR_Catalog::onprem_exchange_cost_defaults();
        $qualityKeys = array_keys(is_array($costDefaults['data_quality'] ?? null) ? $costDefaults['data_quality'] : []);
        $refreshKeys = array_keys(is_array($costDefaults['refresh_windows'] ?? null) ? $costDefaults['refresh_windows'] : []);

        return [
            'mailboxes' => self::int_value($source['mailboxes'] ?? $defaults['mailboxes'], 1, 500000),
            'average_mailbox_gb' => self::float_value($source['average_mailbox_gb'] ?? $defaults['average_mailbox_gb'], 0.1, 1000.0),
            'servers' => self::int_value($source['servers'] ?? $defaults['servers'], 0, 500),
            'server_hardware_cost' => self::money_value($source['server_hardware_cost'] ?? $defaults['server_hardware_cost'], 0, 100000000),
            'exchange_windows_license_cost' => self::money_value($source['exchange_windows_license_cost'] ?? $defaults['exchange_windows_license_cost'], 0, 100000000),
            'storage_backup_monthly' => self::money_value($source['storage_backup_monthly'] ?? $defaults['storage_backup_monthly'], 0, 10000000),
            'power_hosting_monthly' => self::money_value($source['power_hosting_monthly'] ?? $defaults['power_hosting_monthly'], 0, 10000000),
            'maintenance_monthly' => self::money_value($source['maintenance_monthly'] ?? $defaults['maintenance_monthly'], 0, 10000000),
            'admin_hours_monthly' => self::float_value($source['admin_hours_monthly'] ?? $defaults['admin_hours_monthly'], 0, 100000),
            'admin_hourly_rate' => self::money_value($source['admin_hourly_rate'] ?? $defaults['admin_hourly_rate'], 0, 10000),
            'ha_dr_monthly' => self::money_value($source['ha_dr_monthly'] ?? $defaults['ha_dr_monthly'], 0, 10000000),
            'monitoring_tools_monthly' => self::money_value($source['monitoring_tools_monthly'] ?? $defaults['monitoring_tools_monthly'], 0, 10000000),
            'certificates_annual' => self::money_value($source['certificates_annual'] ?? $defaults['certificates_annual'], 0, 10000000),
            'hardware_refresh_due_months' => (int) self::choice((string) ($source['hardware_refresh_due_months'] ?? $defaults['hardware_refresh_due_months']), $refreshKeys, (string) $defaults['hardware_refresh_due_months']),
            'migration_cost' => self::money_value($source['migration_cost'] ?? $defaults['migration_cost'], 0, 100000000),
            'coexistence_months' => self::int_value($source['coexistence_months'] ?? $defaults['coexistence_months'], 0, 120),
            'analysis_years' => self::int_value($source['analysis_years'] ?? $defaults['analysis_years'], 3, 5),
            'cloud_plan' => self::choice((string) ($source['cloud_plan'] ?? $defaults['cloud_plan']), $planKeys, (string) $defaults['cloud_plan']),
            'migration_method' => self::choice((string) ($source['migration_method'] ?? $defaults['migration_method']), $methodKeys, (string) $defaults['migration_method']),
            'data_quality' => self::choice((string) ($source['data_quality'] ?? $defaults['data_quality']), $qualityKeys, (string) $defaults['data_quality']),
            'archive_compliance_required' => self::bool_value($source['archive_compliance_required'] ?? $defaults['archive_compliance_required']),
            'hybrid_required' => self::bool_value($source['hybrid_required'] ?? $defaults['hybrid_required']),
            'org_wants_fast_exit' => self::bool_value($source['org_wants_fast_exit'] ?? $defaults['org_wants_fast_exit']),
            'public_folders_or_legacy_apps' => self::bool_value($source['public_folders_or_legacy_apps'] ?? $defaults['public_folders_or_legacy_apps']),
            'backup_reduction_monthly' => self::money_value($source['backup_reduction_monthly'] ?? $defaults['backup_reduction_monthly'], 0, 10000000),
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function cloud_plan_options(): array
    {
        $plans = CMS_M365CALCULATOR_Catalog::exchange_online_plans();
        return self::labels_from_items(is_array($plans['plans'] ?? null) ? $plans['plans'] : []);
    }

    /**
     * @return array<string,string>
     */
    public static function migration_method_options(): array
    {
        $velocity = CMS_M365CALCULATOR_Catalog::exchange_migration_velocity();
        return self::labels_from_items(is_array($velocity['methods'] ?? null) ? $velocity['methods'] : []);
    }

    /**
     * @return array<string,string>
     */
    public static function data_quality_options(): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::onprem_exchange_cost_defaults();
        return self::labels_from_items(is_array($catalog['data_quality'] ?? null) ? $catalog['data_quality'] : []);
    }

    /**
     * @return array<string,string>
     */
    public static function refresh_window_options(): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::onprem_exchange_cost_defaults();
        return self::labels_from_items(is_array($catalog['refresh_windows'] ?? null) ? $catalog['refresh_windows'] : []);
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function evaluate(array $input): array
    {
        $input = self::normalize_input($input);
        $plans = CMS_M365CALCULATOR_Catalog::exchange_online_plans();
        $defaults = CMS_M365CALCULATOR_Catalog::onprem_exchange_cost_defaults();
        $velocity = CMS_M365CALCULATOR_Catalog::exchange_migration_velocity();

        $onprem = self::calculate_exchange_onprem_costs($input, $defaults);
        $cloud = self::calculate_exchange_online_costs($input, $plans, $velocity);
        $breakEven = self::calculate_exchange_migration_break_even($input, $onprem, $cloud);
        $migration = self::estimate_migration($input, $velocity);
        $score = self::score_exchange_cloud_business_case($input, $onprem, $cloud, $breakEven, $migration, $defaults, $velocity);
        $recommendation = self::build_recommendation($input, $breakEven, $score, $defaults, $migration);

        return [
            'input' => $input,
            'onprem' => $onprem,
            'cloud' => $cloud,
            'break_even' => $breakEven,
            'migration' => $migration,
            'score' => $score,
            'recommendation' => $recommendation,
            'chart_rows' => self::build_chart_rows($breakEven),
            'warnings' => self::build_warnings($input, $score, $migration),
            'management_summary' => self::build_management_summary($recommendation, $breakEven, $score),
            'plan_rows' => self::build_plan_rows($input, $plans),
            'cloud_plan_options' => self::cloud_plan_options(),
            'migration_method_options' => self::migration_method_options(),
            'data_quality_options' => self::data_quality_options(),
            'refresh_window_options' => self::refresh_window_options(),
            'sources' => array_values(array_unique(array_merge(
                array_map('strval', (array) ($plans['meta']['sources'] ?? [])),
                array_map('strval', (array) ($velocity['meta']['sources'] ?? []))
            ))),
            'meta' => [
                'source_checked' => (string) ($plans['meta']['source_checked'] ?? $velocity['meta']['source_checked'] ?? '2026-05-16'),
                'price_basis' => (string) ($plans['meta']['price_basis'] ?? 'Preisannahmen vor Angebotserstellung prüfen.'),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $defaults
     * @return array<string,mixed>
     */
    public static function calculate_exchange_onprem_costs(array $input, array $defaults = []): array
    {
        $monthlyAdmin = (float) $input['admin_hours_monthly'] * (float) $input['admin_hourly_rate'];
        $monthly = (float) $input['storage_backup_monthly']
            + (float) $input['power_hosting_monthly']
            + (float) $input['maintenance_monthly']
            + $monthlyAdmin
            + (float) $input['ha_dr_monthly']
            + (float) $input['monitoring_tools_monthly']
            + ((float) $input['certificates_annual'] / 12);
        $refreshCost = (float) $input['server_hardware_cost'] + (float) $input['exchange_windows_license_cost'];
        $refreshDue = (int) $input['hardware_refresh_due_months'];

        return [
            'monthly_admin' => $monthlyAdmin,
            'monthly_run' => $monthly,
            'refresh_cost' => $refreshCost,
            'refresh_due_months' => $refreshDue,
            'cost_36' => self::onprem_cost_at_month($monthly, $refreshCost, $refreshDue, 36),
            'cost_60' => self::onprem_cost_at_month($monthly, $refreshCost, $refreshDue, 60),
            'cost_per_mailbox_month' => $monthly / max(1, (int) $input['mailboxes']),
            'components' => [
                'Storage, Backup und Wartung' => (float) $input['storage_backup_monthly'] + (float) $input['maintenance_monthly'],
                'Strom, Hosting oder Housing' => (float) $input['power_hosting_monthly'],
                'Administration' => $monthlyAdmin,
                'HA, DR und Monitoring' => (float) $input['ha_dr_monthly'] + (float) $input['monitoring_tools_monthly'],
                'Zertifikate pro Monat' => (float) $input['certificates_annual'] / 12,
            ],
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $plans
     * @param array<string,mixed> $velocity
     * @return array<string,mixed>
     */
    public static function calculate_exchange_online_costs(array $input, array $plans, array $velocity = []): array
    {
        $planItems = is_array($plans['plans'] ?? null) ? $plans['plans'] : [];
        $addons = is_array($plans['addons'] ?? null) ? $plans['addons'] : [];
        $rules = is_array($plans['cloud_cost_rules'] ?? null) ? $plans['cloud_cost_rules'] : [];
        $plan = is_array($planItems[(string) $input['cloud_plan']] ?? null) ? $planItems[(string) $input['cloud_plan']] : [];
        $mailboxes = (int) $input['mailboxes'];
        $licenseMonthly = (float) ($plan['price_month'] ?? 0) * $mailboxes;
        $needsArchiveAddon = !empty($input['archive_compliance_required']) && empty($plan['auto_expanding_archive']);
        $archiveAddonMonthly = $needsArchiveAddon ? (float) ($addons['exchange_online_archiving']['price_month'] ?? 0) * $mailboxes : 0.0;
        $toolingMonthly = (string) $input['migration_method'] === 'third_party'
            ? (float) ($addons['migration_tooling']['price_month_per_mailbox'] ?? 0) * $mailboxes
            : 0.0;
        $retainedToolsMonthly = (float) $input['backup_reduction_monthly'];
        $hybridMonthly = (!empty($input['hybrid_required']) || (string) $input['migration_method'] === 'hybrid')
            ? (float) ($rules['hybrid_overhead_monthly_base'] ?? 0) + ((float) ($rules['hybrid_overhead_per_mailbox'] ?? 0) * $mailboxes)
            : 0.0;
        $coexistenceMonths = (int) $input['coexistence_months'];
        $parallelFactor = (float) ($rules['parallel_run_license_factor'] ?? 0.0);
        $parallelRunCost = ($licenseMonthly + $archiveAddonMonthly) * $parallelFactor * $coexistenceMonths;
        $migrationCost = (float) $input['migration_cost'];
        $ongoingMonthly = $licenseMonthly + $archiveAddonMonthly + $toolingMonthly + $retainedToolsMonthly;
        $oneTimeAndTransition = $migrationCost + ($hybridMonthly * $coexistenceMonths) + $parallelRunCost;

        return [
            'plan_label' => (string) ($plan['label'] ?? $input['cloud_plan']),
            'license_monthly' => $licenseMonthly,
            'archive_addon_monthly' => $archiveAddonMonthly,
            'tooling_monthly' => $toolingMonthly,
            'retained_tools_monthly' => $retainedToolsMonthly,
            'hybrid_monthly' => $hybridMonthly,
            'parallel_run_cost' => $parallelRunCost,
            'migration_cost' => $migrationCost,
            'one_time_and_transition' => $oneTimeAndTransition,
            'ongoing_monthly' => $ongoingMonthly,
            'cost_36' => $oneTimeAndTransition + ($ongoingMonthly * 36),
            'cost_60' => $oneTimeAndTransition + ($ongoingMonthly * 60),
            'cost_per_mailbox_month' => $ongoingMonthly / max(1, $mailboxes),
            'needs_archive_addon' => $needsArchiveAddon,
            'components' => [
                'Exchange Online Lizenzen' => $licenseMonthly,
                'Archiv oder Compliance Add-on' => $archiveAddonMonthly,
                'Migrations-/Koexistenztools' => $toolingMonthly,
                'Cloud Backup-/Toolkosten' => $retainedToolsMonthly,
                'Hybridbetrieb während Koexistenz' => $hybridMonthly,
            ],
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $onprem
     * @param array<string,mixed> $cloud
     * @return array<string,mixed>
     */
    public static function calculate_exchange_migration_break_even(array $input, array $onprem, array $cloud): array
    {
        $rows = [];
        $breakEvenMonth = null;
        $onpremMonthly = (float) ($onprem['monthly_run'] ?? 0);
        $refreshCost = (float) ($onprem['refresh_cost'] ?? 0);
        $refreshDue = (int) ($onprem['refresh_due_months'] ?? 9999);
        $cloudMonthly = (float) ($cloud['ongoing_monthly'] ?? 0);
        $cloudStart = (float) ($cloud['one_time_and_transition'] ?? 0);

        for ($month = 0; $month <= 60; $month++) {
            $onpremCost = self::onprem_cost_at_month($onpremMonthly, $refreshCost, $refreshDue, $month);
            $cloudCost = $cloudStart + ($cloudMonthly * $month);
            $savings = $onpremCost - $cloudCost;
            if ($month > 0 && $breakEvenMonth === null && $savings >= 0) {
                $breakEvenMonth = $month;
            }
            $rows[$month] = [
                'month' => $month,
                'onprem' => $onpremCost,
                'cloud' => $cloudCost,
                'savings' => $savings,
            ];
        }

        return [
            'month' => $breakEvenMonth,
            'rows' => $rows,
            'savings_36' => (float) ($onprem['cost_36'] ?? 0) - (float) ($cloud['cost_36'] ?? 0),
            'savings_60' => (float) ($onprem['cost_60'] ?? 0) - (float) ($cloud['cost_60'] ?? 0),
            'delta_monthly' => $onpremMonthly - $cloudMonthly,
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $velocity
     * @return array<string,mixed>
     */
    private static function estimate_migration(array $input, array $velocity): array
    {
        $methods = is_array($velocity['methods'] ?? null) ? $velocity['methods'] : [];
        $method = is_array($methods[(string) $input['migration_method']] ?? null) ? $methods[(string) $input['migration_method']] : [];
        $factors = is_array($velocity['planning_factors'] ?? null) ? $velocity['planning_factors'] : [];
        $profile = self::mailbox_size_profile((float) $input['average_mailbox_gb'], $velocity);
        $riskBase = (int) ($method['risk_base'] ?? 35);
        $batchSize = (!empty($input['public_folders_or_legacy_apps']) || !empty($input['hybrid_required']))
            ? (int) ($factors['high_complexity_batch_size'] ?? 50)
            : (int) ($factors['default_batch_size'] ?? 100);
        $batchSize = max(10, $batchSize);
        $batches = (int) ceil((int) $input['mailboxes'] / $batchSize);
        $queueFactor = 1 + (((float) ($factors['queue_buffer_percent'] ?? 20) + (float) ($factors['network_risk_percent'] ?? 15)) / 100);
        $batchFactor = max(1.0, sqrt((float) $batches));
        $minDays = (int) ceil((float) ($profile['min_days'] ?? 1) * $batchFactor);
        $maxDays = (int) ceil((float) ($profile['max_days'] ?? 3) * $batchFactor * $queueFactor);
        $suggestedMethod = self::suggest_migration_method($input);

        return [
            'method_label' => (string) ($method['label'] ?? $input['migration_method']),
            'method_guidance' => (string) ($method['guidance'] ?? ''),
            'suggested_method' => $suggestedMethod,
            'suggested_method_label' => (string) ($methods[$suggestedMethod]['label'] ?? $suggestedMethod),
            'profile_label' => (string) ($profile['label'] ?? 'Standard'),
            'batch_size' => $batchSize,
            'batches' => $batches,
            'min_days' => $minDays,
            'max_days' => max($minDays, $maxDays),
            'risk_base' => $riskBase,
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $onprem
     * @param array<string,mixed> $cloud
     * @param array<string,mixed> $breakEven
     * @param array<string,mixed> $migration
     * @param array<string,mixed> $defaults
     * @param array<string,mixed> $velocity
     * @return array<string,mixed>
     */
    public static function score_exchange_cloud_business_case(array $input, array $onprem, array $cloud, array $breakEven, array $migration, array $defaults, array $velocity): array
    {
        $quality = is_array($defaults['data_quality'][(string) $input['data_quality']] ?? null) ? $defaults['data_quality'][(string) $input['data_quality']] : [];
        $refresh = is_array($defaults['refresh_windows'][(string) $input['hardware_refresh_due_months']] ?? null) ? $defaults['refresh_windows'][(string) $input['hardware_refresh_due_months']] : [];
        $risk = (int) ($migration['risk_base'] ?? 35) + (int) ($quality['risk_delta'] ?? 0);
        $risk += !empty($input['hybrid_required']) ? 10 : 0;
        $risk += !empty($input['public_folders_or_legacy_apps']) ? 18 : 0;
        $risk += (int) $input['mailboxes'] > 2000 ? 10 : 0;
        $risk += (float) $input['average_mailbox_gb'] > 50 ? 8 : 0;
        $risk = self::clamp_score($risk);

        $roiScore = 35 + (int) ($refresh['roi_boost'] ?? 0);
        $roiScore += (float) ($breakEven['savings_60'] ?? 0) > 0 ? 18 : -10;
        $roiScore += (float) ($breakEven['delta_monthly'] ?? 0) > 0 ? 12 : -6;
        $roiScore += !empty($input['archive_compliance_required']) ? 8 : 0;
        $roiScore -= $risk >= 72 ? 14 : 0;
        $roiScore = self::clamp_score($roiScore);

        $qualitative = 35;
        $qualitative += !empty($input['archive_compliance_required']) ? 15 : 0;
        $qualitative += (int) $input['hardware_refresh_due_months'] <= 12 ? 16 : 6;
        $qualitative += !empty($input['hybrid_required']) ? 8 : 0;
        $qualitative += (float) ($onprem['monthly_admin'] ?? 0) > (float) ($cloud['license_monthly'] ?? 0) * 0.2 ? 12 : 4;
        $qualitative = self::clamp_score($qualitative);

        return [
            'risk_score' => $risk,
            'roi_score' => $roiScore,
            'qualitative_score' => $qualitative,
            'confidence' => (string) ($quality['confidence'] ?? 'mittel'),
            'data_quality_label' => (string) ($quality['label'] ?? $input['data_quality']),
            'refresh_label' => (string) ($refresh['label'] ?? 'Refresh prüfen'),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $breakEven
     * @param array<string,mixed> $score
     * @param array<string,mixed> $defaults
     * @param array<string,mixed> $migration
     * @return array<string,mixed>
     */
    private static function build_recommendation(array $input, array $breakEven, array $score, array $defaults, array $migration): array
    {
        $thresholds = is_array($defaults['thresholds'] ?? null) ? $defaults['thresholds'] : [];
        $categories = is_array($defaults['recommendation_categories'] ?? null) ? $defaults['recommendation_categories'] : [];
        $month = is_int($breakEven['month'] ?? null) ? (int) $breakEven['month'] : null;
        $category = 'strategic_cloud';

        if ((string) $input['data_quality'] === 'rough' && (int) ($score['risk_score'] ?? 0) >= (int) ($thresholds['low_confidence_risk'] ?? 75)) {
            $category = 'insufficient_data';
        } elseif ((!empty($input['public_folders_or_legacy_apps']) || ((string) ($migration['suggested_method'] ?? '') === 'hybrid' && (string) $input['migration_method'] !== 'hybrid')) && (int) ($score['risk_score'] ?? 0) >= 60) {
            $category = 'hybrid_review';
        } elseif ($month !== null && $month <= (int) ($thresholds['fast_break_even_months'] ?? 18)) {
            $category = 'fast_break_even';
        } elseif ($month !== null && $month <= (int) ($thresholds['midterm_break_even_months'] ?? 42)) {
            $category = 'midterm_break_even';
        } elseif (!empty($input['hybrid_required']) && (int) ($score['risk_score'] ?? 0) >= (int) ($thresholds['high_risk_score'] ?? 72)) {
            $category = 'hybrid_review';
        }

        $meta = is_array($categories[$category] ?? null) ? $categories[$category] : [];

        return [
            'category' => $category,
            'label' => (string) ($meta['label'] ?? $category),
            'tone' => (string) ($meta['tone'] ?? 'info'),
            'summary' => (string) ($meta['summary'] ?? ''),
            'score' => max((int) ($score['roi_score'] ?? 0), (int) ($score['qualitative_score'] ?? 0)),
        ];
    }

    public static function render_exchange_online_roi_page(): string
    {
        return CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-exchange-online-roi.php';
    }

    /**
     * @param array<string,mixed> $recommendation
     * @param array<string,mixed> $breakEven
     * @param array<string,mixed> $score
     * @return array<int,string>
     */
    private static function build_management_summary(array $recommendation, array $breakEven, array $score): array
    {
        $month = is_int($breakEven['month'] ?? null) ? (int) $breakEven['month'] : null;
        $summary = [];
        $summary[] = (string) ($recommendation['summary'] ?? 'Der ROI wurde auf Vollkostenbasis bewertet.');
        $summary[] = $month === null
            ? 'Innerhalb von 60 Monaten entsteht kein rein rechnerischer Break-Even; qualitative Modernisierungsvorteile separat bewerten.'
            : 'Der rechnerische Break-Even wird nach rund ' . $month . ' Monaten erreicht.';
        $summary[] = 'Datenqualität: ' . (string) ($score['confidence'] ?? 'mittel') . ', Risikowert: ' . (int) ($score['risk_score'] ?? 0) . '%.';

        return $summary;
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $score
     * @param array<string,mixed> $migration
     * @return array<int,string>
     */
    private static function build_warnings(array $input, array $score, array $migration): array
    {
        $velocity = CMS_M365CALCULATOR_Catalog::exchange_migration_velocity();
        $messages = is_array($velocity['risk_messages'] ?? null) ? $velocity['risk_messages'] : [];
        $warnings = [];
        if ((int) ($score['risk_score'] ?? 0) >= 65) {
            $warnings[] = 'Das Projektprofil ist komplex; Pilotmove, Netzwerkprüfung und realistische Batches einplanen.';
        }
        if (!empty($input['hybrid_required']) || (string) $input['migration_method'] === 'hybrid') {
            $warnings[] = (string) ($messages['hybrid'] ?? 'Hybrid benötigt zusätzliche Vorarbeiten.');
        }
        if (!empty($input['public_folders_or_legacy_apps'])) {
            $warnings[] = (string) ($messages['public_folders'] ?? 'Legacy-Abhängigkeiten separat prüfen.');
        }
        if ((float) $input['average_mailbox_gb'] > 50) {
            $warnings[] = (string) ($messages['large_mailboxes'] ?? 'Große Mailboxen benötigen Zusatzpuffer.');
        }
        $warnings[] = (string) ($messages['queue'] ?? 'Queue-Zeiten können Migrationsfenster verlängern.');
        $warnings[] = (string) ($messages['network'] ?? 'Netzwerk und Quellsystem beeinflussen die Dauer.');

        return array_values(array_unique(array_filter($warnings)));
    }

    /**
     * @param array<string,mixed> $breakEven
     * @return array<int,array<string,mixed>>
     */
    private static function build_chart_rows(array $breakEven): array
    {
        $rows = is_array($breakEven['rows'] ?? null) ? $breakEven['rows'] : [];
        $points = [0, 6, 12, 24, 36, 48, 60];
        $chart = [];
        foreach ($points as $point) {
            if (!is_array($rows[$point] ?? null)) {
                continue;
            }
            $chart[] = $rows[$point];
        }

        return $chart;
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $plans
     * @return array<int,array<string,mixed>>
     */
    private static function build_plan_rows(array $input, array $plans): array
    {
        $items = is_array($plans['plans'] ?? null) ? $plans['plans'] : [];
        $rows = [];
        foreach ($items as $key => $item) {
            if (!is_array($item)) {
                continue;
            }
            $price = (float) ($item['price_month'] ?? 0);
            $rows[] = [
                'key' => (string) $key,
                'label' => (string) ($item['label'] ?? $key),
                'price_month' => $price,
                'group_month' => $price * (int) $input['mailboxes'],
                'mailbox_gb' => (int) ($item['mailbox_gb'] ?? 0),
                'archive_gb' => (int) ($item['archive_gb'] ?? 0),
                'best_for' => (string) ($item['best_for'] ?? ''),
                'limits' => array_values(array_map('strval', (array) ($item['limits'] ?? []))),
            ];
        }

        return $rows;
    }

    /**
     * @param array<string,mixed> $velocity
     * @return array<string,mixed>
     */
    private static function mailbox_size_profile(float $averageMailboxGb, array $velocity): array
    {
        $profiles = is_array($velocity['mailbox_size_profiles'] ?? null) ? $velocity['mailbox_size_profiles'] : [];
        foreach ($profiles as $profile) {
            if (!is_array($profile)) {
                continue;
            }
            if ($averageMailboxGb >= (float) ($profile['min_gb'] ?? 0) && $averageMailboxGb < (float) ($profile['max_gb'] ?? 100000)) {
                return $profile;
            }
        }

        return ['label' => 'Standard', 'min_days' => 2, 'max_days' => 6];
    }

    /**
     * @param array<string,mixed> $input
     */
    private static function suggest_migration_method(array $input): string
    {
        if (!empty($input['hybrid_required']) || !empty($input['public_folders_or_legacy_apps']) || (int) $input['mailboxes'] > 2000) {
            return 'hybrid';
        }
        if ((int) $input['mailboxes'] <= 150 && !empty($input['org_wants_fast_exit'])) {
            return 'cutover';
        }
        if ((int) $input['mailboxes'] <= 2000) {
            return 'staged';
        }

        return 'hybrid';
    }

    private static function onprem_cost_at_month(float $monthly, float $refreshCost, int $refreshDue, int $month): float
    {
        $refresh = $refreshDue <= $month ? $refreshCost : 0.0;
        return ($monthly * $month) + $refresh;
    }

    /**
     * @param array<string,mixed> $items
     * @return array<string,string>
     */
    private static function labels_from_items(array $items): array
    {
        $labels = [];
        foreach ($items as $key => $item) {
            if (is_array($item)) {
                $labels[(string) $key] = (string) ($item['label'] ?? $item['short'] ?? $key);
            }
        }

        return $labels;
    }

    /**
     * @param array<int,string> $allowed
     */
    private static function choice(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private static function bool_value(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private static function int_value(mixed $value, int $min, int $max): int
    {
        return max($min, min($max, (int) $value));
    }

    private static function float_value(mixed $value, float $min, float $max): float
    {
        $normalized = str_replace(',', '.', (string) $value);
        $float = is_numeric($normalized) ? (float) $normalized : $min;
        return max($min, min($max, $float));
    }

    private static function money_value(mixed $value, float $min, float $max): float
    {
        return round(self::float_value($value, $min, $max), 2);
    }

    private static function clamp_score(int $score): int
    {
        return max(0, min(100, $score));
    }
}
