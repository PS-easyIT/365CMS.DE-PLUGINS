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
        return self::load_json('pricing.json');
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
    public static function readonly_suite_matrix(): array
    {
        return self::load_json('readonly_suite_matrix.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function readonly_addon_matrix(): array
    {
        return self::load_json('readonly_addon_matrix.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function commitment_pricing(): array
    {
        return self::load_json('commitment_pricing.json');
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
        $path = CMS_M365CALCULATOR_PLUGIN_DIR . 'data/' . $file;
        if (!file_exists($path)) {
            return [];
        }

        $json = file_get_contents($path);
        if (!is_string($json) || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }
}
