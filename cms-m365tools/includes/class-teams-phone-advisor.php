<?php
/**
 * CMS M365 Tools – Teams Phone-Lizenz-Berater.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Teams_Phone_Advisor
{
    /**
     * @return array<string,mixed>
     */
    public static function default_input(): array
    {
        return [
            'users' => 120,
            'countries' => 'DE',
            'base_license' => 'business_premium',
            'teams_phone_existing' => false,
            'external_pstn_required' => true,
            'keep_existing_carrier' => false,
            'operator_connect_partner' => false,
            'voice_provider' => 'microsoft_calling_plan',
            'microsoft_carrier_ok' => true,
            'pbx_analog_contact_center' => false,
            'special_routing_required' => false,
            'personal_number_required' => true,
            'audio_conferencing_required' => false,
            'teams_only' => true,
            'sbc_available' => false,
            'voice_network_knowhow' => false,
            'managed_service_preferred' => true,
            'call_volume' => 'medium',
            'many_countries' => false,
            'user_type' => 'knowledge_worker',
            'low_volume_without_did' => false,
            'resource_account_possible' => true,
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_input(array $source): array
    {
        $defaults = self::default_input();

        return [
            'users' => max(1, min(500000, (int) ($source['users'] ?? $defaults['users']))),
            'countries' => self::choice((string) ($source['countries'] ?? $defaults['countries']), array_keys(self::country_options()), (string) $defaults['countries']),
            'base_license' => self::choice((string) ($source['base_license'] ?? $defaults['base_license']), array_keys(self::base_license_options()), (string) $defaults['base_license']),
            'teams_phone_existing' => self::bool_value($source['teams_phone_existing'] ?? $defaults['teams_phone_existing']),
            'external_pstn_required' => self::bool_value($source['external_pstn_required'] ?? $defaults['external_pstn_required']),
            'keep_existing_carrier' => self::bool_value($source['keep_existing_carrier'] ?? $defaults['keep_existing_carrier']),
            'operator_connect_partner' => self::bool_value($source['operator_connect_partner'] ?? $defaults['operator_connect_partner']),
            'voice_provider' => self::choice((string) ($source['voice_provider'] ?? $defaults['voice_provider']), array_keys(self::provider_options()), (string) $defaults['voice_provider']),
            'microsoft_carrier_ok' => self::bool_value($source['microsoft_carrier_ok'] ?? $defaults['microsoft_carrier_ok']),
            'pbx_analog_contact_center' => self::bool_value($source['pbx_analog_contact_center'] ?? $defaults['pbx_analog_contact_center']),
            'special_routing_required' => self::bool_value($source['special_routing_required'] ?? $defaults['special_routing_required']),
            'personal_number_required' => self::bool_value($source['personal_number_required'] ?? $defaults['personal_number_required']),
            'audio_conferencing_required' => self::bool_value($source['audio_conferencing_required'] ?? $defaults['audio_conferencing_required']),
            'teams_only' => self::bool_value($source['teams_only'] ?? $defaults['teams_only']),
            'sbc_available' => self::bool_value($source['sbc_available'] ?? $defaults['sbc_available']),
            'voice_network_knowhow' => self::bool_value($source['voice_network_knowhow'] ?? $defaults['voice_network_knowhow']),
            'managed_service_preferred' => self::bool_value($source['managed_service_preferred'] ?? $defaults['managed_service_preferred']),
            'call_volume' => self::choice((string) ($source['call_volume'] ?? $defaults['call_volume']), array_keys(self::call_volume_options()), (string) $defaults['call_volume']),
            'many_countries' => self::bool_value($source['many_countries'] ?? $defaults['many_countries']),
            'user_type' => self::choice((string) ($source['user_type'] ?? $defaults['user_type']), array_keys(self::user_type_options()), (string) $defaults['user_type']),
            'low_volume_without_did' => self::bool_value($source['low_volume_without_did'] ?? $defaults['low_volume_without_did']),
            'resource_account_possible' => self::bool_value($source['resource_account_possible'] ?? $defaults['resource_account_possible']),
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function base_license_options(): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::teams_phone_base_eligibility();
        $plans = is_array($catalog['base_plans'] ?? null) ? $catalog['base_plans'] : [];

        return self::labels_from_items($plans);
    }

    /**
     * @return array<string,string>
     */
    public static function country_options(): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::teams_country_availability();
        $countries = is_array($catalog['countries'] ?? null) ? $catalog['countries'] : [];

        return self::labels_from_items($countries);
    }

    /**
     * @return array<string,string>
     */
    public static function provider_options(): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::teams_voice_providers();
        $providers = is_array($catalog['providers'] ?? null) ? $catalog['providers'] : [];

        return self::labels_from_items($providers);
    }

    /**
     * @return array<string,string>
     */
    public static function user_type_options(): array
    {
        return [
            'knowledge_worker' => 'Wissensarbeiter mit persönlicher Rufnummer',
            'frontline_worker' => 'Frontline Worker',
            'contact_center_agent' => 'Contact-Center- oder Service-Agent',
            'shared_area' => 'Shared Area / gemeinsam genutztes Gerät',
            'executive' => 'Executive / Assistenz / Delegation',
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function call_volume_options(): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::teams_phone_cost_assumptions();
        $volumes = is_array($catalog['volume_multipliers'] ?? null) ? $catalog['volume_multipliers'] : [];

        return self::labels_from_items($volumes);
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function evaluate(array $input): array
    {
        $input = self::normalize_input($input);
        $eligibility = CMS_M365CALCULATOR_Catalog::teams_phone_base_eligibility();
        $modelRules = CMS_M365CALCULATOR_Catalog::teams_pstn_model_rules();
        $countries = CMS_M365CALCULATOR_Catalog::teams_country_availability();
        $providers = CMS_M365CALCULATOR_Catalog::teams_voice_providers();
        $directRequirements = CMS_M365CALCULATOR_Catalog::teams_direct_routing_requirements();
        $costAssumptions = CMS_M365CALCULATOR_Catalog::teams_phone_cost_assumptions();

        $readiness = self::evaluate_teams_phone_readiness($input, $eligibility, $countries, $providers, $directRequirements);
        $scores = self::evaluate_teams_phone_scenario($input, $modelRules, $countries, $providers, $readiness);
        $recommendation = self::build_recommendation($input, $scores, $modelRules, $readiness);
        $costs = self::calculate_teams_phone_costs($input, $recommendation, $eligibility, $costAssumptions);
        $addons = self::calculate_voice_addon_needs($input, $recommendation, $eligibility, $countries, $costAssumptions);

        return [
            'input' => $input,
            'recommendation' => $recommendation,
            'readiness' => $readiness,
            'costs' => $costs,
            'addons' => $addons,
            'comparison_rows' => self::compare_teams_phone_models($scores, $modelRules, $recommendation),
            'requirement_rows' => self::build_requirement_rows($input, $directRequirements, $recommendation),
            'country' => self::item($countries, 'countries', (string) $input['countries']),
            'base_plan' => self::item($eligibility, 'base_plans', (string) $input['base_license']),
            'voice_provider' => self::item($providers, 'providers', (string) $input['voice_provider']),
            'base_license_options' => self::base_license_options(),
            'country_options' => self::country_options(),
            'provider_options' => self::provider_options(),
            'user_type_options' => self::user_type_options(),
            'call_volume_options' => self::call_volume_options(),
            'warnings' => self::build_warnings($input, $readiness, $recommendation),
            'next_steps' => self::build_next_steps($input, $recommendation, $addons),
            'sources' => self::sources($eligibility, $modelRules, $countries, $providers, $directRequirements, $costAssumptions),
            'meta' => [
                'source_checked' => (string) ($modelRules['meta']['source_checked'] ?? '2026-05-16'),
                'price_basis' => (string) ($costAssumptions['meta']['price_basis'] ?? 'Richtwerte vor Beschaffung prüfen.'),
            ],
        ];
    }

    public static function render_teams_phone_advisor_page(): string
    {
        return CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-teams-phone-advisor.php';
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $eligibility
     * @param array<string,mixed> $countries
     * @param array<string,mixed> $providers
     * @param array<string,mixed> $directRequirements
     * @return array<string,mixed>
     */
    private static function evaluate_teams_phone_readiness(array $input, array $eligibility, array $countries, array $providers, array $directRequirements): array
    {
        $basePlan = self::item($eligibility, 'base_plans', (string) $input['base_license']);
        $country = self::item($countries, 'countries', (string) $input['countries']);
        $provider = self::item($providers, 'providers', (string) $input['voice_provider']);
        $checks = [];
        $gaps = [];
        $score = 100;

        $teamsIncluded = !empty($basePlan['teams_included']);
        $teamsPhoneIncluded = !empty($basePlan['teams_phone_included']) || !empty($input['teams_phone_existing']);
        $isFrontline = (string) $input['user_type'] === 'frontline_worker' || in_array((string) $input['base_license'], ['frontline_f1', 'frontline_f3'], true);
        $callingPlanAvailable = !empty($country['calling_plan_available']);
        $providerModel = (string) ($provider['model'] ?? '');
        $directIntent = !empty($input['keep_existing_carrier']) || !empty($input['pbx_analog_contact_center']) || !empty($input['special_routing_required']) || $providerModel === 'direct_routing';
        $operatorIntent = !empty($input['operator_connect_partner']) || $providerModel === 'operator_connect';

        if (!$teamsIncluded) {
            $score -= 18;
            $gaps[] = [
                'key' => 'base_license_gap',
                'label' => 'Teams-Berechtigung ergänzen',
                'message' => 'Die Basislizenz enthält Teams nicht sicher. Teams-Zugriff vor dem Rollout ergänzen.',
                'severity' => 'warning',
            ];
        }

        if (!$teamsPhoneIncluded) {
            $score -= 12;
            $gaps[] = [
                'key' => 'teams_phone_addon_required',
                'label' => 'Teams Phone hinzufügen',
                'message' => (string) ($basePlan['teams_phone_addon'] ?? 'Teams Phone je Nutzer einplanen.'),
                'severity' => 'info',
            ];
        }

        if ($isFrontline) {
            $gaps[] = [
                'key' => 'frontline_license_path',
                'label' => 'Frontline-Pfad prüfen',
                'message' => 'Für Frontline-Nutzer die passende Teams-Phone-Frontline-Variante und Berechtigung prüfen.',
                'severity' => 'info',
            ];
        }

        if (!empty($input['external_pstn_required']) && !$callingPlanAvailable && !$operatorIntent && !$directIntent) {
            $score -= 20;
            $gaps[] = [
                'key' => 'pstn_path_missing',
                'label' => 'PSTN-Pfad festlegen',
                'message' => 'Für das gewählte Land ist kein einfacher Calling-Plan-Pfad hinterlegt. Operator Connect oder Direct Routing prüfen.',
                'severity' => 'warning',
            ];
        }

        if (($operatorIntent || $directIntent) && empty($input['teams_only'])) {
            $score -= 26;
            $gaps[] = [
                'key' => 'teams_only_required',
                'label' => 'TeamsOnly einplanen',
                'message' => 'Operator Connect und Direct Routing sollten für betroffene Nutzer mit TeamsOnly geplant werden.',
                'severity' => 'danger',
            ];
        }

        if ($directIntent && empty($input['sbc_available']) && empty($input['managed_service_preferred'])) {
            $score -= 30;
            $gaps[] = [
                'key' => 'sbc_path_missing',
                'label' => 'SBC- oder Managed-Service-Pfad fehlt',
                'message' => 'Direct Routing braucht zertifizierte SBC-Infrastruktur oder einen geeigneten gemanagten Dienst.',
                'severity' => 'danger',
            ];
        }

        if (!empty($input['audio_conferencing_required']) && empty($country['audio_conferencing_available'])) {
            $score -= 8;
            $gaps[] = [
                'key' => 'audio_conferencing_review',
                'label' => 'Audio Conferencing prüfen',
                'message' => 'Einwahl- und Dial-out-Verfügbarkeit je Land prüfen.',
                'severity' => 'info',
            ];
        }

        $checks[] = ['label' => 'Teams-Basis', 'status' => $teamsIncluded ? 'ok' : 'review'];
        $checks[] = ['label' => 'Teams Phone', 'status' => $teamsPhoneIncluded ? 'ok' : 'addon'];
        $checks[] = ['label' => 'Calling Plan im Nutzerland', 'status' => $callingPlanAvailable ? 'ok' : 'review'];
        $checks[] = ['label' => 'TeamsOnly bei Carrier-Integration', 'status' => empty($input['teams_only']) ? 'review' : 'ok'];
        $checks[] = ['label' => 'Direct-Routing-Basis', 'status' => $directIntent && empty($input['sbc_available']) && empty($input['managed_service_preferred']) ? 'review' : 'ok'];

        return [
            'score' => self::clamp_score($score),
            'gaps' => $gaps,
            'checks' => $checks,
            'flags' => [
                'teams_included' => $teamsIncluded,
                'teams_phone_included' => $teamsPhoneIncluded,
                'frontline_license_path' => $isFrontline,
                'calling_plan_available' => $callingPlanAvailable,
                'operator_intent' => $operatorIntent,
                'direct_intent' => $directIntent,
            ],
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $modelRules
     * @param array<string,mixed> $countries
     * @param array<string,mixed> $providers
     * @param array<string,mixed> $readiness
     * @return array<string,int>
     */
    private static function evaluate_teams_phone_scenario(array $input, array $modelRules, array $countries, array $providers, array $readiness): array
    {
        $models = is_array($modelRules['models'] ?? null) ? $modelRules['models'] : [];
        $rules = is_array($modelRules['scoring'] ?? null) ? $modelRules['scoring'] : [];
        $country = self::item($countries, 'countries', (string) $input['countries']);
        $provider = self::item($providers, 'providers', (string) $input['voice_provider']);
        $providerModel = (string) ($provider['model'] ?? '');
        $scores = [];

        foreach ($models as $key => $model) {
            if (!is_array($model)) {
                continue;
            }
            $scores[(string) $key] = (int) ($model['base_score'] ?? 40);
        }

        if (empty($input['external_pstn_required'])) {
            $scores['architecture_review'] += 18;
            $scores['calling_plan'] -= 18;
            $scores['operator_connect'] -= 12;
            $scores['direct_routing'] -= 10;
        }

        if (!empty($country['calling_plan_available'])) {
            $scores['calling_plan'] += (int) ($rules['calling_plan_country_bonus'] ?? 20);
        } else {
            $scores['calling_plan'] -= 24;
            $scores['direct_routing'] += 10;
            $scores['operator_connect'] += 8;
        }

        if (!empty($input['microsoft_carrier_ok'])) {
            $scores['calling_plan'] += (int) ($rules['calling_plan_microsoft_carrier_bonus'] ?? 16);
        } else {
            $scores['calling_plan'] -= 20;
            $scores['operator_connect'] += 8;
            $scores['direct_routing'] += 8;
        }

        if (!empty($input['operator_connect_partner']) || $providerModel === 'operator_connect') {
            $scores['operator_connect'] += (int) ($rules['operator_partner_bonus'] ?? 22) + (int) ($provider['score_delta'] ?? 0);
        }

        if (!empty($input['managed_service_preferred'])) {
            $scores['operator_connect'] += (int) ($rules['operator_managed_bonus'] ?? 10);
            if (!empty($input['sbc_available'])) {
                $scores['direct_routing'] += 6;
            }
        }

        if (!empty($input['keep_existing_carrier']) || $providerModel === 'direct_routing') {
            $scores['direct_routing'] += (int) ($rules['direct_existing_carrier_bonus'] ?? 18);
            $scores['calling_plan'] -= 18;
        }

        if (!empty($input['pbx_analog_contact_center'])) {
            $scores['direct_routing'] += (int) ($rules['direct_pbx_bonus'] ?? 18);
            $scores['mixed_model'] += 8;
            $scores['calling_plan'] -= 14;
        }

        if (!empty($input['special_routing_required'])) {
            $scores['direct_routing'] += (int) ($rules['direct_special_routing_bonus'] ?? 18);
            $scores['mixed_model'] += 8;
            $scores['calling_plan'] -= 12;
        }

        if (!empty($input['sbc_available'])) {
            $scores['direct_routing'] += (int) ($rules['direct_sbc_bonus'] ?? 18);
        } elseif (!empty($readiness['flags']['direct_intent'])) {
            $scores['direct_routing'] += (int) ($rules['direct_no_sbc_penalty'] ?? -30);
            $scores['architecture_review'] += 22;
        }

        if (!empty($input['voice_network_knowhow'])) {
            $scores['direct_routing'] += 10;
        } elseif (!empty($readiness['flags']['direct_intent']) && empty($input['managed_service_preferred'])) {
            $scores['architecture_review'] += 12;
        }

        if (empty($input['teams_only']) && (!empty($readiness['flags']['direct_intent']) || !empty($readiness['flags']['operator_intent']))) {
            $scores['operator_connect'] += (int) ($rules['teams_only_missing_penalty'] ?? -28);
            $scores['direct_routing'] += (int) ($rules['teams_only_missing_penalty'] ?? -28);
            $scores['architecture_review'] += 26;
        }

        if (!empty($input['many_countries']) || (string) $input['countries'] === 'OTHER') {
            $scores['mixed_model'] += (int) ($rules['many_countries_mixed_bonus'] ?? 18);
            $scores['operator_connect'] += 6;
            $scores['direct_routing'] += 8;
        }

        if (!empty($input['low_volume_without_did']) && !empty($input['resource_account_possible'])) {
            $scores['mixed_model'] += (int) ($rules['low_volume_shared_bonus'] ?? 16);
            $scores['calling_plan'] += 4;
        }

        if ((string) $input['call_volume'] === 'low' && empty($input['pbx_analog_contact_center']) && empty($input['special_routing_required']) && empty($input['keep_existing_carrier'])) {
            $scores['calling_plan'] += (int) ($rules['standard_low_complexity_bonus'] ?? 10);
        }

        if ((string) $input['user_type'] === 'contact_center_agent') {
            $scores['direct_routing'] += 12;
            $scores['operator_connect'] += 8;
            $scores['mixed_model'] += 10;
        }

        if ((int) ($readiness['score'] ?? 100) < 55) {
            $scores['architecture_review'] += 22;
        }

        foreach ($scores as $key => $score) {
            $scores[$key] = self::clamp_score($score);
        }

        arsort($scores, SORT_NUMERIC);

        return $scores;
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,int> $scores
     * @param array<string,mixed> $modelRules
     * @param array<string,mixed> $readiness
     * @return array<string,mixed>
     */
    private static function build_recommendation(array $input, array $scores, array $modelRules, array $readiness): array
    {
        $models = is_array($modelRules['models'] ?? null) ? $modelRules['models'] : [];
        $category = (string) array_key_first($scores);
        $directIntent = !empty($readiness['flags']['direct_intent']);
        $operatorIntent = !empty($readiness['flags']['operator_intent']);

        if ($directIntent && empty($input['sbc_available']) && empty($input['managed_service_preferred'])) {
            $category = 'architecture_review';
        }
        if (($operatorIntent || $directIntent) && empty($input['teams_only'])) {
            $category = 'architecture_review';
        }
        if (!empty($input['many_countries']) && in_array($category, ['calling_plan', 'operator_connect', 'direct_routing'], true) && max($scores['mixed_model'] ?? 0, 0) >= (($scores[$category] ?? 0) - 6)) {
            $category = 'mixed_model';
        }

        $model = is_array($models[$category] ?? null) ? $models[$category] : [];
        $score = (int) ($scores[$category] ?? 0);

        return [
            'category' => $category,
            'model_key' => $category,
            'label' => (string) ($model['label'] ?? 'Empfehlung prüfen'),
            'short' => (string) ($model['short'] ?? $category),
            'tone' => (string) ($model['tone'] ?? 'info'),
            'score' => $score,
            'operations' => (string) ($model['operations'] ?? 'mittel'),
            'flexibility' => (string) ($model['flexibility'] ?? 'mittel'),
            'complexity' => (int) ($model['complexity'] ?? 50),
            'reason' => self::recommendation_reason($category, $input),
            'best_for' => (string) ($model['best_for'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $recommendation
     * @param array<string,mixed> $eligibility
     * @param array<string,mixed> $costAssumptions
     * @return array<string,mixed>
     */
    private static function calculate_teams_phone_costs(array $input, array $recommendation, array $eligibility, array $costAssumptions): array
    {
        $users = (int) $input['users'];
        $licenses = is_array($costAssumptions['licenses'] ?? null) ? $costAssumptions['licenses'] : [];
        $models = is_array($costAssumptions['pstn_models'] ?? null) ? $costAssumptions['pstn_models'] : [];
        $volumes = is_array($costAssumptions['volume_multipliers'] ?? null) ? $costAssumptions['volume_multipliers'] : [];
        $basePlan = self::item($eligibility, 'base_plans', (string) $input['base_license']);
        $modelKey = (string) ($recommendation['model_key'] ?? 'architecture_review');
        $modelCosts = is_array($models[$modelKey] ?? null) ? $models[$modelKey] : [];
        $volume = is_array($volumes[(string) $input['call_volume']] ?? null) ? $volumes[(string) $input['call_volume']] : [];
        $factor = max(0.1, (float) ($volume['factor'] ?? 1.0));
        $licenseMonthly = 0.0;
        $components = [];

        if (empty($basePlan['teams_included'])) {
            $teamsStandalone = (float) ($licenses['teams_enterprise_standalone']['monthly'] ?? 0);
            $licenseMonthly += $teamsStandalone * $users;
            $components[] = ['label' => 'Teams-Ergänzung', 'monthly_low' => $teamsStandalone * $users, 'monthly_high' => $teamsStandalone * $users];
        }

        if (empty($input['teams_phone_existing']) && empty($basePlan['teams_phone_included'])) {
            $phoneKey = ((string) $input['user_type'] === 'frontline_worker' || in_array((string) $input['base_license'], ['frontline_f1', 'frontline_f3'], true)) ? 'teams_phone_frontline' : 'teams_phone_standard';
            $phoneMonthly = (float) ($licenses[$phoneKey]['monthly'] ?? 0);
            $licenseMonthly += $phoneMonthly * $users;
            $components[] = ['label' => (string) ($licenses[$phoneKey]['label'] ?? 'Teams Phone'), 'monthly_low' => $phoneMonthly * $users, 'monthly_high' => $phoneMonthly * $users];
        }

        $pstnLow = ((float) ($modelCosts['per_user_monthly_low'] ?? 0) * $factor * $users) + (float) ($modelCosts['monthly_base'] ?? 0);
        $pstnHigh = ((float) ($modelCosts['per_user_monthly_high'] ?? 0) * $factor * $users) + (float) ($modelCosts['monthly_base'] ?? 0);

        if (!empty($input['low_volume_without_did']) && !empty($input['resource_account_possible'])) {
            $discount = (float) ($costAssumptions['shared_calling']['eligible_low_volume_discount'] ?? 0);
            $pstnLow *= max(0.0, 1.0 - $discount);
            $pstnHigh *= max(0.0, 1.0 - $discount);
        }

        if (!empty($input['external_pstn_required']) && $modelKey !== 'architecture_review') {
            $components[] = ['label' => (string) ($modelCosts['label'] ?? 'PSTN-Modell'), 'monthly_low' => $pstnLow, 'monthly_high' => $pstnHigh];
        }

        $communicationCreditsMonthly = self::needs_communication_credits($input, $recommendation, $costAssumptions) ? ((float) ($licenses['communication_credits_budget']['monthly'] ?? 0) * $users) : 0.0;
        if ($communicationCreditsMonthly > 0) {
            $components[] = ['label' => 'Communication Credits Budget', 'monthly_low' => $communicationCreditsMonthly, 'monthly_high' => $communicationCreditsMonthly];
        }

        $monthlyLow = $licenseMonthly + (!empty($input['external_pstn_required']) && $modelKey !== 'architecture_review' ? $pstnLow : 0) + $communicationCreditsMonthly;
        $monthlyHigh = $licenseMonthly + (!empty($input['external_pstn_required']) && $modelKey !== 'architecture_review' ? $pstnHigh : 0) + $communicationCreditsMonthly;
        $setupOnce = ((float) ($modelCosts['setup_once_base'] ?? 0)) + ((float) ($modelCosts['setup_once_per_user'] ?? 0) * $users);

        return [
            'monthly_low' => $monthlyLow,
            'monthly_high' => $monthlyHigh,
            'annual_low' => $monthlyLow * 12,
            'annual_high' => $monthlyHigh * 12,
            'setup_once' => $setupOnce,
            'first_year_low' => ($monthlyLow * 12) + $setupOnce,
            'first_year_high' => ($monthlyHigh * 12) + $setupOnce,
            'per_user_low' => $users > 0 ? $monthlyLow / $users : 0,
            'per_user_high' => $users > 0 ? $monthlyHigh / $users : 0,
            'components' => $components,
            'note' => (string) ($modelCosts['note'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $recommendation
     * @param array<string,mixed> $eligibility
     * @param array<string,mixed> $countries
     * @param array<string,mixed> $costAssumptions
     * @return array<string,mixed>
     */
    private static function calculate_voice_addon_needs(array $input, array $recommendation, array $eligibility, array $countries, array $costAssumptions): array
    {
        $basePlan = self::item($eligibility, 'base_plans', (string) $input['base_license']);
        $country = self::item($countries, 'countries', (string) $input['countries']);
        $needsCommunicationCredits = self::needs_communication_credits($input, $recommendation, $costAssumptions);
        $sharedCalling = !empty($input['low_volume_without_did']) && !empty($input['resource_account_possible']);
        $items = [];

        if (empty($basePlan['teams_included'])) {
            $items[] = ['label' => 'Teams-Berechtigung', 'status' => 'prüfen', 'message' => 'Basis-Suite und Teams-Zugriff getrennt berücksichtigen.'];
        }
        if (empty($basePlan['teams_phone_included']) && empty($input['teams_phone_existing'])) {
            $items[] = ['label' => 'Teams Phone', 'status' => 'benötigt', 'message' => (string) ($basePlan['teams_phone_addon'] ?? 'Teams Phone hinzufügen.')];
        }
        if (!empty($input['audio_conferencing_required'])) {
            $items[] = ['label' => 'Audio Conferencing', 'status' => 'prüfen', 'message' => 'Einwahl, Dial-out und Veranstalterbedarf separat bewerten.'];
        }
        if ($needsCommunicationCredits) {
            $items[] = ['label' => 'Communication Credits', 'status' => 'empfohlen', 'message' => 'Für Überläufe, Pay-As-You-Go, Dial-out oder servicebezogene Rufnummern Budget einplanen.'];
        }
        if ($sharedCalling) {
            $items[] = ['label' => 'Shared Calling', 'status' => 'Kandidat', 'message' => 'Low-Volume-Nutzer ohne persönliche Durchwahl können über Ressourcenkonto und Auto Attendant abgebildet werden.'];
        }
        if ((string) $input['user_type'] === 'frontline_worker' || in_array((string) $input['base_license'], ['frontline_f1', 'frontline_f3'], true)) {
            $items[] = ['label' => 'Frontline-Variante', 'status' => 'prüfen', 'message' => 'Frontline-Berechtigung und passende Teams-Phone-Variante prüfen.'];
        }
        if (empty($country['calling_plan_available']) && (string) ($recommendation['model_key'] ?? '') === 'calling_plan') {
            $items[] = ['label' => 'Landesverfügbarkeit', 'status' => 'prüfen', 'message' => 'Calling Plan für das Nutzerland vor Bestellung gegenprüfen.'];
        }

        return [
            'items' => $items,
            'flags' => [
                'audio_conferencing_review' => !empty($input['audio_conferencing_required']),
                'communication_credits_recommended' => $needsCommunicationCredits,
                'shared_calling_candidate' => $sharedCalling,
                'frontline_license_path' => (string) $input['user_type'] === 'frontline_worker' || in_array((string) $input['base_license'], ['frontline_f1', 'frontline_f3'], true),
                'base_license_gap' => empty($basePlan['teams_included']),
            ],
        ];
    }

    /**
     * @param array<string,int> $scores
     * @param array<string,mixed> $modelRules
     * @param array<string,mixed> $recommendation
     * @return array<int,array<string,mixed>>
     */
    private static function compare_teams_phone_models(array $scores, array $modelRules, array $recommendation): array
    {
        $models = is_array($modelRules['models'] ?? null) ? $modelRules['models'] : [];
        $rows = [];
        foreach ($models as $key => $model) {
            if (!is_array($model)) {
                continue;
            }
            $rows[] = [
                'key' => (string) $key,
                'label' => (string) ($model['short'] ?? $model['label'] ?? $key),
                'score' => (int) ($scores[(string) $key] ?? 0),
                'operations' => (string) ($model['operations'] ?? ''),
                'flexibility' => (string) ($model['flexibility'] ?? ''),
                'best_for' => (string) ($model['best_for'] ?? ''),
                'requirements' => array_values(array_map('strval', (array) ($model['requirements'] ?? []))),
                'limits' => array_values(array_map('strval', (array) ($model['limits'] ?? []))),
                'active' => (string) ($recommendation['model_key'] ?? '') === (string) $key,
            ];
        }

        usort($rows, static fn(array $left, array $right): int => ((int) ($right['score'] ?? 0)) <=> ((int) ($left['score'] ?? 0)));

        return $rows;
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $directRequirements
     * @param array<string,mixed> $recommendation
     * @return array<int,array<string,string>>
     */
    private static function build_requirement_rows(array $input, array $directRequirements, array $recommendation): array
    {
        $requirements = is_array($directRequirements['requirements'] ?? null) ? $directRequirements['requirements'] : [];
        $rows = [
            ['label' => 'Teams-Zugriff', 'status' => 'Basis prüfen', 'message' => 'Nutzer brauchen Teams-Zugriff als Grundlage für Teams Phone.'],
            ['label' => 'Teams Phone', 'status' => !empty($input['teams_phone_existing']) ? 'vorhanden' : 'einplanen', 'message' => 'PBX- und externe Telefoniefunktionen je Nutzer lizenzieren.'],
            ['label' => 'PSTN-Anbindung', 'status' => !empty($input['external_pstn_required']) ? 'erforderlich' : 'optional', 'message' => 'Calling Plan, Operator Connect, Direct Routing oder Kombination festlegen.'],
        ];

        if (in_array((string) ($recommendation['model_key'] ?? ''), ['direct_routing', 'mixed_model', 'architecture_review'], true)) {
            foreach ($requirements as $requirement) {
                if (!is_array($requirement)) {
                    continue;
                }
                $rows[] = [
                    'label' => (string) ($requirement['label'] ?? ''),
                    'status' => (string) ($requirement['impact'] ?? 'prüfen'),
                    'message' => (string) ($requirement['message'] ?? ''),
                ];
            }
        }

        return $rows;
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $readiness
     * @param array<string,mixed> $recommendation
     * @return array<int,string>
     */
    private static function build_warnings(array $input, array $readiness, array $recommendation): array
    {
        $warnings = [];
        foreach ((array) ($readiness['gaps'] ?? []) as $gap) {
            if (is_array($gap) && in_array((string) ($gap['severity'] ?? ''), ['warning', 'danger'], true)) {
                $warnings[] = (string) ($gap['message'] ?? '');
            }
        }
        if ((string) ($recommendation['model_key'] ?? '') === 'direct_routing' && empty($input['voice_network_knowhow']) && empty($input['managed_service_preferred'])) {
            $warnings[] = 'Direct Routing braucht klare Betriebsverantwortung für SBC, Routing, Zertifikate und Carrier-Koordination.';
        }
        if (!empty($input['keep_existing_carrier']) && (string) ($recommendation['model_key'] ?? '') === 'calling_plan') {
            $warnings[] = 'Bestehende Carrier-Verträge und Kündigungsfristen vor einem Wechsel zu Microsoft Calling Plan prüfen.';
        }
        if (!empty($input['many_countries'])) {
            $warnings[] = 'Mehrländer-Szenarien je Nutzerland, Rufnummerntyp und Anbieter getrennt planen.';
        }

        return array_values(array_filter(array_unique($warnings)));
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $recommendation
     * @param array<string,mixed> $addons
     * @return array<int,string>
     */
    private static function build_next_steps(array $input, array $recommendation, array $addons): array
    {
        $steps = [
            'Nutzergruppen, Länder, Rufnummern und bestehende Carrier-Verträge erfassen.',
            'Teams-Berechtigung und Teams-Phone-Zuweisung je Persona prüfen.',
            'Zielmodell für PSTN, Nummern, Notruf und Support festlegen.',
        ];

        if ((string) ($recommendation['model_key'] ?? '') === 'calling_plan') {
            $steps[] = 'Calling-Plan-Verfügbarkeit, Nummernportierung und Minutenmodell pro Nutzerland prüfen.';
        } elseif ((string) ($recommendation['model_key'] ?? '') === 'operator_connect') {
            $steps[] = 'Operator-Connect-Partner, Länderabdeckung, Portierung und Supportprozess abstimmen.';
        } elseif ((string) ($recommendation['model_key'] ?? '') === 'direct_routing') {
            $steps[] = 'SBC, DNS, Zertifikat, Routing Policies und Betriebsmodell detailliert planen.';
        } elseif ((string) ($recommendation['model_key'] ?? '') === 'mixed_model') {
            $steps[] = 'Personas in Standard-, Carrier-, Sonderrouting- und Low-Volume-Gruppen aufteilen.';
        } else {
            $steps[] = 'Vor Entscheidung Zielarchitektur, Koexistenz, Carrier und technische Voraussetzungen klären.';
        }

        if (!empty($addons['flags']['shared_calling_candidate'])) {
            $steps[] = 'Shared Calling für Low-Volume-Nutzer ohne persönliche Durchwahl als Sparpfad bewerten.';
        }
        if (!empty($input['audio_conferencing_required'])) {
            $steps[] = 'Audio Conferencing, Dial-out und Meeting-Einwahl separat mit dem Meeting-Konzept abstimmen.';
        }

        return array_values(array_unique($steps));
    }

    /**
     * @param array<string,mixed> $input
     */
    private static function recommendation_reason(string $category, array $input): string
    {
        return match ($category) {
            'calling_plan' => 'Microsoft Calling Plan passt, weil ein standardisierter Cloud-Pfad gewünscht ist und kein bestehender Carrier zwingend erhalten bleiben muss.',
            'operator_connect' => 'Operator Connect passt, weil ein Carrier den PSTN-Betrieb übernehmen soll und die Integration über Teams Admin Center verwaltet werden kann.',
            'direct_routing' => 'Direct Routing passt, weil bestehende Carrier, Spezialrouting, PBX-, Analog- oder Contact-Center-Anbindungen relevant sind.',
            'mixed_model' => 'Ein Mischmodell passt, weil Nutzergruppen, Länder oder Low-Volume-Szenarien unterschiedlich behandelt werden sollten.',
            default => 'Vor einer Produktauswahl sollten Lizenzbasis, Koexistenz, Carrier, Nummern und technische Voraussetzungen sauber geklärt werden.',
        };
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $recommendation
     * @param array<string,mixed> $costAssumptions
     */
    private static function needs_communication_credits(array $input, array $recommendation, array $costAssumptions): bool
    {
        if (!empty($input['low_volume_without_did']) && !empty($input['resource_account_possible'])) {
            return true;
        }
        if (!empty($input['audio_conferencing_required']) && in_array((string) $input['call_volume'], ['high', 'international'], true)) {
            return true;
        }
        if ((string) $input['call_volume'] === 'international') {
            return true;
        }

        return (string) ($recommendation['model_key'] ?? '') === 'calling_plan' && (string) $input['call_volume'] === 'low';
    }

    /**
     * @param array<string,mixed> $catalog
     * @return array<string,mixed>
     */
    private static function item(array $catalog, string $group, string $key): array
    {
        $items = is_array($catalog[$group] ?? null) ? $catalog[$group] : [];

        return is_array($items[$key] ?? null) ? $items[$key] : [];
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

    private static function clamp_score(int $score): int
    {
        return max(0, min(100, $score));
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
