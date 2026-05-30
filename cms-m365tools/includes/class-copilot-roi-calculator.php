<?php
/**
 * CMS M365 Tools – Copilot ROI-Rechner.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Copilot_ROI_Calculator
{
    /**
     * @return array<string,mixed>
     */
    public static function default_input(): array
    {
        $assumptions = CMS_M365CALCULATOR_Catalog::roi_assumptions();
        $defaults = is_array($assumptions['defaults'] ?? null) ? $assumptions['defaults'] : [];
        $pricing = CMS_M365CALCULATOR_Catalog::copilot_pricing();
        $copilot = is_array($pricing['copilot_addon'] ?? null) ? $pricing['copilot_addon'] : [];
        $enablement = is_array($pricing['enablement'] ?? null) ? $pricing['enablement'] : [];

        return [
            'persona' => 'management',
            'rollout_mode' => (string) ($defaults['rollout_mode'] ?? 'pilot'),
            'scenario' => (string) ($defaults['scenario'] ?? 'realistic'),
            'user_count' => (int) ($defaults['user_count'] ?? 25),
            'license_price_monthly' => (float) ($copilot['monthly_per_user'] ?? 28.10),
            'hourly_cost' => (float) ($defaults['hourly_cost'] ?? 65.00),
            'minutes_saved_per_day' => (float) ($defaults['minutes_saved_per_day'] ?? 18),
            'workdays_per_month' => (int) ($defaults['workdays_per_month'] ?? 20),
            'adoption_rate' => (float) ($defaults['adoption_rate'] ?? 65),
            'ramp_up_months' => (int) ($defaults['ramp_up_months'] ?? 3),
            'one_time_enablement_cost' => (float) ($enablement['pilot_default_one_time'] ?? 2500.00),
            'monthly_enablement_cost' => (float) ($enablement['monthly_change_default'] ?? 0.00),
            'base_license_eligible' => true,
            'has_entra_account' => true,
            'has_primary_exchange_mailbox' => true,
            'm365_apps_deployed' => true,
            'onedrive_enabled' => true,
            'privacy_controls_reviewed' => true,
            'network_ready' => true,
            'teams_meeting_usecase' => true,
            'teams_transcription_enabled' => true,
            'teams_phone_pstn_usecase' => false,
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_input(array $source): array
    {
        $defaults = self::default_input();
        $assumptions = CMS_M365CALCULATOR_Catalog::roi_assumptions();
        $limits = is_array($assumptions['limits'] ?? null) ? $assumptions['limits'] : [];
        $scenarios = is_array($assumptions['scenarios'] ?? null) ? $assumptions['scenarios'] : [];
        $personas = self::persona_options();
        $rolloutMode = self::enum((string) ($source['rollout_mode'] ?? $defaults['rollout_mode']), ['pilot', 'broad'], (string) $defaults['rollout_mode']);
        $enablementDefault = (float) $defaults['one_time_enablement_cost'];

        if ($rolloutMode === 'broad') {
            $pricing = CMS_M365CALCULATOR_Catalog::copilot_pricing();
            $enablement = is_array($pricing['enablement'] ?? null) ? $pricing['enablement'] : [];
            $enablementDefault = (float) ($enablement['broad_default_one_time'] ?? $enablementDefault);
        }

        $persona = self::clean_key((string) ($source['persona'] ?? $defaults['persona']));
        if (!isset($personas[$persona])) {
            $persona = (string) $defaults['persona'];
        }

        $scenario = self::clean_key((string) ($source['scenario'] ?? $defaults['scenario']));
        if (!isset($scenarios[$scenario])) {
            $scenario = (string) $defaults['scenario'];
        }

        return [
            'persona' => $persona,
            'rollout_mode' => $rolloutMode,
            'scenario' => $scenario,
            'user_count' => self::int_value($source['user_count'] ?? $defaults['user_count'], (int) ($limits['user_count_min'] ?? 1), (int) ($limits['user_count_max'] ?? 500000)),
            'license_price_monthly' => self::float_value($source['license_price_monthly'] ?? $defaults['license_price_monthly'], 0.0, 100000.0),
            'hourly_cost' => self::float_value($source['hourly_cost'] ?? $defaults['hourly_cost'], (float) ($limits['hourly_cost_min'] ?? 1), (float) ($limits['hourly_cost_max'] ?? 1000)),
            'minutes_saved_per_day' => self::float_value($source['minutes_saved_per_day'] ?? $defaults['minutes_saved_per_day'], (float) ($limits['minutes_saved_per_day_min'] ?? 0), (float) ($limits['minutes_saved_per_day_max'] ?? 240)),
            'workdays_per_month' => self::int_value($source['workdays_per_month'] ?? $defaults['workdays_per_month'], (int) ($limits['workdays_per_month_min'] ?? 1), (int) ($limits['workdays_per_month_max'] ?? 31)),
            'adoption_rate' => self::float_value($source['adoption_rate'] ?? $defaults['adoption_rate'], (float) ($limits['adoption_rate_min'] ?? 0), (float) ($limits['adoption_rate_max'] ?? 100)),
            'ramp_up_months' => self::int_value($source['ramp_up_months'] ?? $defaults['ramp_up_months'], (int) ($limits['ramp_up_months_min'] ?? 0), (int) ($limits['ramp_up_months_max'] ?? 12)),
            'one_time_enablement_cost' => self::float_value($source['one_time_enablement_cost'] ?? $enablementDefault, 0.0, 10000000.0),
            'monthly_enablement_cost' => self::float_value($source['monthly_enablement_cost'] ?? $defaults['monthly_enablement_cost'], 0.0, 10000000.0),
            'base_license_eligible' => self::bool_value($source['base_license_eligible'] ?? false),
            'has_entra_account' => self::bool_value($source['has_entra_account'] ?? false),
            'has_primary_exchange_mailbox' => self::bool_value($source['has_primary_exchange_mailbox'] ?? false),
            'm365_apps_deployed' => self::bool_value($source['m365_apps_deployed'] ?? false),
            'onedrive_enabled' => self::bool_value($source['onedrive_enabled'] ?? false),
            'privacy_controls_reviewed' => self::bool_value($source['privacy_controls_reviewed'] ?? false),
            'network_ready' => self::bool_value($source['network_ready'] ?? false),
            'teams_meeting_usecase' => self::bool_value($source['teams_meeting_usecase'] ?? false),
            'teams_transcription_enabled' => self::bool_value($source['teams_transcription_enabled'] ?? false),
            'teams_phone_pstn_usecase' => self::bool_value($source['teams_phone_pstn_usecase'] ?? false),
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function persona_options(): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::persona_roi_presets();

        return is_array($catalog['personas'] ?? null) ? $catalog['personas'] : [];
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function evaluate(array $input): array
    {
        $input = self::normalize_input($input);
        $readiness = self::validate_copilot_readiness($input);
        $scenarioResults = [];
        $assumptions = CMS_M365CALCULATOR_Catalog::roi_assumptions();
        $scenarios = is_array($assumptions['scenarios'] ?? null) ? $assumptions['scenarios'] : [];

        foreach ($scenarios as $key => $scenario) {
            if (!is_array($scenario)) {
                continue;
            }
            $scenarioResults[(string) $key] = self::calculate_copilot_roi($input, (string) $key, $scenario);
        }

        if ($scenarioResults === []) {
            $scenarioResults['realistic'] = self::calculate_copilot_roi($input, 'realistic', []);
        }

        $selectedKey = (string) $input['scenario'];
        $selected = $scenarioResults[$selectedKey] ?? reset($scenarioResults);
        if (!is_array($selected)) {
            $selected = [];
        }

        $status = self::classify($input, $readiness, $selected, $assumptions);
        $chart = self::build_copilot_roi_chart($input, $selectedKey, $scenarioResults[$selectedKey] ?? $selected);
        $pricing = CMS_M365CALCULATOR_Catalog::copilot_pricing();
        $sources = array_values(array_unique(array_merge(
            is_array($pricing['sources'] ?? null) ? $pricing['sources'] : [],
            is_array(CMS_M365CALCULATOR_Catalog::copilot_readiness_rules()['sources'] ?? null) ? CMS_M365CALCULATOR_Catalog::copilot_readiness_rules()['sources'] : []
        )));

        return [
            'status' => $status,
            'input' => $input,
            'readiness' => $readiness,
            'selected_scenario' => $selectedKey,
            'selected' => $selected,
            'scenarios' => $scenarioResults,
            'chart' => $chart,
            'recommendation' => self::build_recommendation($input, $readiness, $selected, $status),
            'sources' => $sources,
            'source_checked_at' => (string) (($pricing['meta']['source_checked_at'] ?? '') ?: '2026-05-16'),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function validate_copilot_readiness(array $input): array
    {
        $rules = CMS_M365CALCULATOR_Catalog::copilot_readiness_rules();
        $required = is_array($rules['required'] ?? null) ? $rules['required'] : [];
        $recommended = is_array($rules['recommended'] ?? null) ? $rules['recommended'] : [];
        $special = is_array($rules['special_cases'] ?? null) ? $rules['special_cases'] : [];
        $fieldMap = [
            'base_license_eligible' => 'base_license_eligible',
            'entra_account' => 'has_entra_account',
            'primary_exchange_online_mailbox' => 'has_primary_exchange_mailbox',
            'm365_apps_deployed' => 'm365_apps_deployed',
            'onedrive_enabled' => 'onedrive_enabled',
            'privacy_controls_reviewed' => 'privacy_controls_reviewed',
            'network_ready' => 'network_ready',
            'teams_transcription_enabled' => 'teams_transcription_enabled',
        ];
        $blockers = [];
        $warnings = [];

        foreach ($required as $key => $meta) {
            $field = $fieldMap[(string) $key] ?? '';
            if ($field !== '' && empty($input[$field]) && is_array($meta)) {
                $blockers[] = self::readiness_item((string) $key, $meta, 'blocker');
            }
        }

        foreach ($recommended as $key => $meta) {
            $field = $fieldMap[(string) $key] ?? '';
            if ($field === 'teams_transcription_enabled' && empty($input['teams_meeting_usecase'])) {
                continue;
            }
            if ($field !== '' && empty($input[$field]) && is_array($meta)) {
                $warnings[] = self::readiness_item((string) $key, $meta, 'warning');
            }
        }

        if (!empty($input['teams_phone_pstn_usecase']) && is_array($special['teams_phone_pstn'] ?? null)) {
            $warnings[] = [
                'key' => 'teams_phone_pstn',
                'severity' => 'warning',
                'label' => (string) ($special['teams_phone_pstn']['label'] ?? 'Teams Phone PSTN'),
                'message' => (string) ($special['teams_phone_pstn']['warning'] ?? ''),
                'next_step' => 'Teams Phone, PSTN-Modell und Calling Plan separat kalkulieren.',
            ];
        }

        return [
            'ready' => $blockers === [],
            'production_ready' => $blockers === [] && $warnings === [],
            'blockers' => $blockers,
            'warnings' => $warnings,
            'score' => self::readiness_score($blockers, $warnings),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $scenario
     * @return array<string,mixed>
     */
    public static function calculate_copilot_roi(array $input, string $scenarioKey = 'realistic', array $scenario = []): array
    {
        $timeFactor = (float) ($scenario['time_factor'] ?? 1.0);
        $adoptionFactor = (float) ($scenario['adoption_factor'] ?? 1.0);
        $costFactor = (float) ($scenario['cost_factor'] ?? 1.0);
        $users = max(1, (int) ($input['user_count'] ?? 1));
        $effectiveAdoption = min(100.0, max(0.0, (float) ($input['adoption_rate'] ?? 0) * $adoptionFactor));
        $effectiveUsers = $users * ($effectiveAdoption / 100);
        $effectiveMinutes = max(0.0, (float) ($input['minutes_saved_per_day'] ?? 0) * $timeFactor);
        $workdays = max(1, (int) ($input['workdays_per_month'] ?? 20));
        $hourlyCost = max(0.0, (float) ($input['hourly_cost'] ?? 0));
        $monthlyProductivity = ($effectiveUsers * $effectiveMinutes / 60) * $workdays * $hourlyCost;
        $monthlyLicenseCost = $users * (float) ($input['license_price_monthly'] ?? 0) * $costFactor;
        $monthlyEnablement = (float) ($input['monthly_enablement_cost'] ?? 0) * $costFactor;
        $oneTime = (float) ($input['one_time_enablement_cost'] ?? 0) * $costFactor;
        $monthlyTotalCost = $monthlyLicenseCost + $monthlyEnablement;
        $monthlyNetRunrate = $monthlyProductivity - $monthlyTotalCost;
        $rampFactor = self::annual_ramp_factor((int) ($input['ramp_up_months'] ?? 0));
        $annualProductivity = $monthlyProductivity * 12 * $rampFactor;
        $annualCost = ($monthlyTotalCost * 12) + $oneTime;
        $annualNet = $annualProductivity - $annualCost;
        $roiPercent = $annualCost > 0 ? ($annualNet / $annualCost) * 100 : 0.0;
        $breakEvenMinutes = self::calculate_copilot_break_even_minutes($input, $scenario);
        $paybackMonths = self::calculate_copilot_payback_period($input, $monthlyProductivity, $monthlyTotalCost, $oneTime);
        $unusedPotentialRate = max(0.0, 100.0 - $effectiveAdoption);

        return [
            'key' => $scenarioKey,
            'label' => (string) ($scenario['label'] ?? ucfirst($scenarioKey)),
            'description' => (string) ($scenario['description'] ?? ''),
            'effective_users' => round($effectiveUsers, 1),
            'effective_adoption_rate' => round($effectiveAdoption, 1),
            'effective_minutes_saved' => round($effectiveMinutes, 1),
            'monthly_productivity_gain' => round($monthlyProductivity, 2),
            'monthly_license_cost' => round($monthlyLicenseCost, 2),
            'monthly_enablement_cost' => round($monthlyEnablement, 2),
            'monthly_total_cost' => round($monthlyTotalCost, 2),
            'monthly_net_runrate' => round($monthlyNetRunrate, 2),
            'annual_productivity_gain' => round($annualProductivity, 2),
            'annual_total_cost' => round($annualCost, 2),
            'annual_net_roi' => round($annualNet, 2),
            'roi_percent' => round($roiPercent, 1),
            'break_even_minutes_per_day' => round($breakEvenMinutes, 1),
            'payback_months' => $paybackMonths,
            'unused_potential_rate' => round($unusedPotentialRate, 1),
            'ramp_factor_year_one' => round($rampFactor, 3),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $scenario
     */
    public static function calculate_copilot_break_even_minutes(array $input, array $scenario = []): float
    {
        $users = max(1, (int) ($input['user_count'] ?? 1));
        $adoptionFactor = (float) ($scenario['adoption_factor'] ?? 1.0);
        $costFactor = (float) ($scenario['cost_factor'] ?? 1.0);
        $effectiveAdoption = min(100.0, max(0.0, (float) ($input['adoption_rate'] ?? 0) * $adoptionFactor));
        $adoptionRatio = max(0.01, $effectiveAdoption / 100);
        $hourlyCost = max(0.01, (float) ($input['hourly_cost'] ?? 0));
        $workdays = max(1, (int) ($input['workdays_per_month'] ?? 20));
        $monthlyUserCost = ((float) ($input['license_price_monthly'] ?? 0) * $costFactor)
            + (((float) ($input['monthly_enablement_cost'] ?? 0) * $costFactor) / $users)
            + (((float) ($input['one_time_enablement_cost'] ?? 0) * $costFactor) / 12 / $users);
        $valuePerMinuteMonth = ($hourlyCost / 60) * $workdays * $adoptionRatio;

        return $valuePerMinuteMonth > 0 ? $monthlyUserCost / $valuePerMinuteMonth : 9999.0;
    }

    public static function calculate_copilot_payback_period(array $input, float $monthlyProductivity, float $monthlyCost, float $oneTimeCost): ?float
    {
        $monthlyNetBeforeOneTime = $monthlyProductivity - $monthlyCost;
        if ($monthlyNetBeforeOneTime <= 0) {
            return null;
        }

        if ($oneTimeCost <= 0) {
            return 0.0;
        }

        return round($oneTimeCost / $monthlyNetBeforeOneTime, 1);
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $scenarioResult
     * @return array<string,mixed>
     */
    public static function build_copilot_roi_chart(array $input, string $scenarioKey, array $scenarioResult): array
    {
        $rows = [];
        $monthlyGainFull = (float) ($scenarioResult['monthly_productivity_gain'] ?? 0);
        $monthlyCost = (float) ($scenarioResult['monthly_total_cost'] ?? 0);
        $oneTime = (float) ($input['one_time_enablement_cost'] ?? 0);
        $curve = self::ramp_curve((int) ($input['ramp_up_months'] ?? 0));
        $max = max(1.0, $oneTime);
        $cumulativeGain = 0.0;
        $cumulativeCost = $oneTime;

        for ($month = 1; $month <= 12; $month++) {
            $factor = (float) ($curve[$month - 1] ?? 1.0);
            $gain = $monthlyGainFull * $factor;
            $cumulativeGain += $gain;
            $cumulativeCost += $monthlyCost;
            $net = $cumulativeGain - $cumulativeCost;
            $max = max($max, abs($cumulativeGain), abs($cumulativeCost), abs($net));
            $rows[] = [
                'month' => $month,
                'gain' => round($cumulativeGain, 2),
                'cost' => round($cumulativeCost, 2),
                'net' => round($net, 2),
                'ramp_factor' => round($factor, 2),
            ];
        }

        foreach ($rows as $index => $row) {
            $rows[$index]['gain_width'] = round(((float) $row['gain'] / $max) * 100, 1);
            $rows[$index]['cost_width'] = round(((float) $row['cost'] / $max) * 100, 1);
            $rows[$index]['net_width'] = round((abs((float) $row['net']) / $max) * 100, 1);
            $rows[$index]['net_positive'] = (float) $row['net'] >= 0;
        }

        return [
            'scenario' => $scenarioKey,
            'max_value' => round($max, 2),
            'rows' => $rows,
        ];
    }

    /**
     * @param array<string,mixed> $status
     * @return array<int,string>
     */
    private static function build_recommendation(array $input, array $readiness, array $selected, array $status): array
    {
        $steps = [];
        if (($status['key'] ?? '') === 'readiness_first') {
            foreach ($readiness['blockers'] ?? [] as $blocker) {
                if (is_array($blocker) && (string) ($blocker['next_step'] ?? '') !== '') {
                    $steps[] = (string) $blocker['next_step'];
                }
            }
            $steps[] = 'ROI nur als Potenzial betrachten, bis die Pflichtvoraussetzungen geschlossen sind.';
            return array_values(array_unique($steps));
        }

        if (($status['key'] ?? '') === 'strong_case') {
            $steps[] = 'Pilotgruppe mit klaren Champions definieren und Rollout-Backlog vorbereiten.';
            $steps[] = 'Nach vier bis acht Wochen echte Nutzungs- und Zeitgewinn-Daten gegen das Modell spiegeln.';
        } elseif (($status['key'] ?? '') === 'pilot_candidate') {
            $steps[] = 'Mit definierten Rollen starten und Erfolgskriterien pro Persona festlegen.';
            $steps[] = 'Enablement, Prompt-Schulung und Meeting-/Datei-Governance vor breitem Rollout absichern.';
        } elseif (($status['key'] ?? '') === 'borderline') {
            $steps[] = 'Annahmen reduzieren oder nur Rollen mit hohem Meeting-/Dokumentenanteil pilotieren.';
            $steps[] = 'Adoption und konkrete Use Cases vor Lizenzkauf validieren.';
        } else {
            $steps[] = 'Wirtschaftlichen Case nicht breit ausrollen; erst Zielgruppe, Adoption oder Nutzenannahmen schärfen.';
        }

        if (!empty($readiness['warnings'])) {
            $steps[] = 'Readiness-Warnungen vor produktiver Skalierung schließen.';
        }

        if ((string) ($input['rollout_mode'] ?? '') === 'broad' && (float) ($selected['effective_adoption_rate'] ?? 0) < 60) {
            $steps[] = 'Breiter Rollout bei Adoption unter 60 Prozent ist riskant; Pilot- oder Wave-Modell bevorzugen.';
        }

        return array_values(array_unique($steps));
    }

    /**
     * @param array<string,mixed> $selected
     * @param array<string,mixed> $assumptions
     * @return array<string,string>
     */
    private static function classify(array $input, array $readiness, array $selected, array $assumptions): array
    {
        if (empty($readiness['ready'])) {
            return ['key' => 'readiness_first', 'label' => 'Lizenz-/Readiness-Prüfung zuerst', 'tone' => 'info'];
        }

        $thresholds = is_array($assumptions['recommendation_thresholds'] ?? null) ? $assumptions['recommendation_thresholds'] : [];
        $roi = (float) ($selected['roi_percent'] ?? 0);
        $breakEven = (float) ($selected['break_even_minutes_per_day'] ?? 9999);
        $planned = (float) ($input['minutes_saved_per_day'] ?? 0);
        $payback = $selected['payback_months'];
        $strongRoi = (float) ($thresholds['strong_roi_percent'] ?? 75);
        $positiveRoi = (float) ($thresholds['positive_roi_percent'] ?? 15);
        $fastPayback = (float) ($thresholds['fast_payback_months'] ?? 6);
        $pilotAdoption = (float) ($thresholds['pilot_adoption_threshold'] ?? 50);
        $buffer = (float) ($thresholds['borderline_minutes_buffer'] ?? 3);

        if ($roi >= $strongRoi && $planned >= $breakEven + $buffer && ($payback === null || (float) $payback <= $fastPayback) && !empty($readiness['production_ready'])) {
            return ['key' => 'strong_case', 'label' => 'Starker Business Case', 'tone' => 'success'];
        }

        if ($roi > $positiveRoi && $planned >= $breakEven) {
            return ['key' => 'pilot_candidate', 'label' => 'Solider Pilot-Kandidat', 'tone' => 'success'];
        }

        if ($roi > -10 || (float) ($input['adoption_rate'] ?? 0) < $pilotAdoption || !empty($readiness['warnings'])) {
            return ['key' => 'borderline', 'label' => 'Grenzfall – Adoption und Enablement entscheiden', 'tone' => 'warning'];
        }

        return ['key' => 'critical', 'label' => 'Wirtschaftlich kritisch', 'tone' => 'danger'];
    }

    /**
     * @param array<string,mixed> $meta
     * @return array<string,string>
     */
    private static function readiness_item(string $key, array $meta, string $severity): array
    {
        return [
            'key' => $key,
            'severity' => $severity,
            'label' => (string) ($meta['label'] ?? $key),
            'message' => (string) ($meta['missing'] ?? ''),
            'next_step' => (string) ($meta['next_step'] ?? ''),
        ];
    }

    /**
     * @param array<int,array<string,string>> $blockers
     * @param array<int,array<string,string>> $warnings
     */
    private static function readiness_score(array $blockers, array $warnings): int
    {
        return max(0, min(100, 100 - (count($blockers) * 35) - (count($warnings) * 10)));
    }

    private static function annual_ramp_factor(int $months): float
    {
        $curve = self::ramp_curve($months);
        if ($curve === []) {
            return 1.0;
        }

        return array_sum($curve) / count($curve);
    }

    /**
     * @return array<int,float>
     */
    private static function ramp_curve(int $months): array
    {
        $assumptions = CMS_M365CALCULATOR_Catalog::roi_assumptions();
        $curves = is_array($assumptions['ramp_up_curves'] ?? null) ? $assumptions['ramp_up_curves'] : [];
        $available = array_map('intval', array_keys($curves));
        sort($available);
        $selected = 0;

        foreach ($available as $candidate) {
            if ($months <= $candidate) {
                $selected = $candidate;
                break;
            }
            $selected = $candidate;
        }

        $curve = is_array($curves[(string) $selected] ?? null) ? $curves[(string) $selected] : [];
        $normalized = array_values(array_map(static fn(mixed $value): float => max(0.0, min(1.0, (float) $value)), $curve));

        while (count($normalized) < 12) {
            $normalized[] = 1.0;
        }

        return array_slice($normalized, 0, 12);
    }

    private static function enum(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private static function bool_value(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'ja', 'on'], true);
    }

    private static function int_value(mixed $value, int $min, int $max): int
    {
        return max($min, min($max, (int) $value));
    }

    private static function float_value(mixed $value, float $min, float $max): float
    {
        $normalized = str_replace(',', '.', trim((string) $value));
        if ($normalized === '' || !is_numeric($normalized)) {
            return $min;
        }

        return max($min, min($max, round((float) $normalized, 2)));
    }

    private static function clean_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($value)), '-');
    }
}
