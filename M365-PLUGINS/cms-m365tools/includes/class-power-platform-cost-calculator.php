<?php
/**
 * CMS M365 Tools – Power Platform Kosten-Kalkulator.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Power_Platform_Cost_Calculator
{
    /** @return array<string,mixed> */
    public static function default_input(): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::power_platform_products();
        $defaults = is_array($catalog['defaults'] ?? null) ? $catalog['defaults'] : [];

        return [
            'use_case' => (string) ($defaults['use_case'] ?? 'internal_app'),
            'users' => (int) ($defaults['users'] ?? 120),
            'makers' => (int) ($defaults['makers'] ?? 8),
            'environments' => (int) ($defaults['environments'] ?? 2),
            'apps_per_user' => (int) ($defaults['apps_per_user'] ?? 1),
            'scenario_count' => (int) ($defaults['scenario_count'] ?? 1),
            'months' => (int) ($defaults['months'] ?? 12),
            'connector_type' => (string) ($defaults['connector_type'] ?? 'standard'),
            'flow_context' => (string) ($defaults['flow_context'] ?? 'in_app'),
            'usage_pattern' => (string) ($defaults['usage_pattern'] ?? 'stable'),
            'rpa_mode' => (string) ($defaults['rpa_mode'] ?? 'none'),
            'bot_scope' => (string) ($defaults['bot_scope'] ?? 'none'),
            'website_access' => (string) ($defaults['website_access'] ?? 'none'),
            'monthly_flow_runs' => (int) ($defaults['monthly_flow_runs'] ?? 0),
            'monthly_copilot_credits' => (int) ($defaults['monthly_copilot_credits'] ?? 0),
            'api_requests_per_day' => (int) ($defaults['api_requests_per_day'] ?? 0),
            'dataverse_db_gb' => (float) ($defaults['dataverse_db_gb'] ?? 0),
            'dataverse_file_gb' => (float) ($defaults['dataverse_file_gb'] ?? 0),
            'dataverse_log_gb' => (float) ($defaults['dataverse_log_gb'] ?? 0),
            'process_mining_gb' => (float) ($defaults['process_mining_gb'] ?? 0),
            'rpa_bots' => 1,
            'authenticated_site_users' => 0,
            'anonymous_site_users' => 0,
            'dataverse_for_teams' => (int) ($defaults['dataverse_for_teams'] ?? 1),
            'teams_only' => (int) ($defaults['teams_only'] ?? 1),
            'outside_teams_use' => (int) ($defaults['outside_teams_use'] ?? 0),
            'ai_builder_required' => (int) ($defaults['ai_builder_required'] ?? 0),
            'managed_environment' => (int) ($defaults['managed_environment'] ?? 0),
            'advanced_governance' => (int) ($defaults['advanced_governance'] ?? 0),
            'azure_subscription' => (int) ($defaults['azure_subscription'] ?? 0),
            'environment_strategy' => (string) ($defaults['environment_strategy'] ?? 'dev_test_prod'),
            'data_policy_state' => (string) ($defaults['data_policy_state'] ?? 'classic'),
            'identity_controls' => (string) ($defaults['identity_controls'] ?? 'groups'),
            'secret_management' => (string) ($defaults['secret_management'] ?? 'environment_variables'),
            'alm_level' => (string) ($defaults['alm_level'] ?? 'managed_solutions'),
            'monitoring_level' => (string) ($defaults['monitoring_level'] ?? 'admin_center'),
            'performance_targets' => (string) ($defaults['performance_targets'] ?? 'numeric'),
            'data_lifecycle' => (string) ($defaults['data_lifecycle'] ?? 'classified'),
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_input(array $source): array
    {
        $input = self::default_input();
        $products = CMS_M365CALCULATOR_Catalog::power_platform_products();
        $connectorRules = CMS_M365CALCULATOR_Catalog::power_platform_connector_rules();
        $useCaseRules = CMS_M365CALCULATOR_Catalog::power_platform_use_cases();
        $options = is_array($products['options'] ?? null) ? $products['options'] : [];

        $input['use_case'] = self::enum((string) ($source['use_case'] ?? $input['use_case']), array_keys((array) ($useCaseRules['use_cases'] ?? [])), 'internal_app');
        $input['users'] = self::int_value($source['users'] ?? $input['users'], 1, 500000);
        $input['makers'] = self::int_value($source['makers'] ?? $input['makers'], 0, 500000);
        $input['environments'] = self::int_value($source['environments'] ?? $input['environments'], 1, 10000);
        $input['apps_per_user'] = self::int_value($source['apps_per_user'] ?? $input['apps_per_user'], 0, 1000);
        $input['scenario_count'] = self::int_value($source['scenario_count'] ?? $input['scenario_count'], 1, 1000);
        $input['months'] = self::enum_int((string) ($source['months'] ?? $input['months']), array_keys((array) ($options['months'] ?? [])), 12);
        $input['connector_type'] = self::enum((string) ($source['connector_type'] ?? $input['connector_type']), array_keys((array) ($connectorRules['rules'] ?? [])), 'standard');
        $input['flow_context'] = self::enum((string) ($source['flow_context'] ?? $input['flow_context']), array_keys((array) ($options['flow_context'] ?? [])), 'in_app');
        $input['usage_pattern'] = self::enum((string) ($source['usage_pattern'] ?? $input['usage_pattern']), array_keys((array) ($options['usage_pattern'] ?? [])), 'stable');
        $input['rpa_mode'] = self::enum((string) ($source['rpa_mode'] ?? $input['rpa_mode']), array_keys((array) ($options['rpa_mode'] ?? [])), 'none');
        $input['bot_scope'] = self::enum((string) ($source['bot_scope'] ?? $input['bot_scope']), array_keys((array) ($options['bot_scope'] ?? [])), 'none');
        $input['website_access'] = self::enum((string) ($source['website_access'] ?? $input['website_access']), array_keys((array) ($options['website_access'] ?? [])), 'none');
        $input['monthly_flow_runs'] = self::int_value($source['monthly_flow_runs'] ?? $input['monthly_flow_runs'], 0, 100000000);
        $input['monthly_copilot_credits'] = self::int_value($source['monthly_copilot_credits'] ?? $input['monthly_copilot_credits'], 0, 100000000);
        $input['api_requests_per_day'] = self::int_value($source['api_requests_per_day'] ?? $input['api_requests_per_day'], 0, 1000000000);
        $input['dataverse_db_gb'] = self::float_value($source['dataverse_db_gb'] ?? $input['dataverse_db_gb'], 0, 1000000);
        $input['dataverse_file_gb'] = self::float_value($source['dataverse_file_gb'] ?? $input['dataverse_file_gb'], 0, 1000000);
        $input['dataverse_log_gb'] = self::float_value($source['dataverse_log_gb'] ?? $input['dataverse_log_gb'], 0, 1000000);
        $input['process_mining_gb'] = self::float_value($source['process_mining_gb'] ?? $input['process_mining_gb'], 0, 1000000);
        $input['rpa_bots'] = self::int_value($source['rpa_bots'] ?? $input['rpa_bots'], 1, 100000);
        $input['authenticated_site_users'] = self::int_value($source['authenticated_site_users'] ?? $input['authenticated_site_users'], 0, 100000000);
        $input['anonymous_site_users'] = self::int_value($source['anonymous_site_users'] ?? $input['anonymous_site_users'], 0, 100000000);
        $input['dataverse_for_teams'] = self::bool_int($source['dataverse_for_teams'] ?? $input['dataverse_for_teams']);
        $input['teams_only'] = self::bool_int($source['teams_only'] ?? $input['teams_only']);
        $input['outside_teams_use'] = self::bool_int($source['outside_teams_use'] ?? $input['outside_teams_use']);
        $input['ai_builder_required'] = self::bool_int($source['ai_builder_required'] ?? $input['ai_builder_required']);
        $input['managed_environment'] = self::bool_int($source['managed_environment'] ?? $input['managed_environment']);
        $input['advanced_governance'] = self::bool_int($source['advanced_governance'] ?? $input['advanced_governance']);
        $input['azure_subscription'] = self::bool_int($source['azure_subscription'] ?? $input['azure_subscription']);
        $input['environment_strategy'] = self::enum((string) ($source['environment_strategy'] ?? $input['environment_strategy']), array_keys((array) ($options['environment_strategy'] ?? [])), 'dev_test_prod');
        $input['data_policy_state'] = self::enum((string) ($source['data_policy_state'] ?? $input['data_policy_state']), array_keys((array) ($options['data_policy_state'] ?? [])), 'classic');
        $input['identity_controls'] = self::enum((string) ($source['identity_controls'] ?? $input['identity_controls']), array_keys((array) ($options['identity_controls'] ?? [])), 'groups');
        $input['secret_management'] = self::enum((string) ($source['secret_management'] ?? $input['secret_management']), array_keys((array) ($options['secret_management'] ?? [])), 'environment_variables');
        $input['alm_level'] = self::enum((string) ($source['alm_level'] ?? $input['alm_level']), array_keys((array) ($options['alm_level'] ?? [])), 'managed_solutions');
        $input['monitoring_level'] = self::enum((string) ($source['monitoring_level'] ?? $input['monitoring_level']), array_keys((array) ($options['monitoring_level'] ?? [])), 'admin_center');
        $input['performance_targets'] = self::enum((string) ($source['performance_targets'] ?? $input['performance_targets']), array_keys((array) ($options['performance_targets'] ?? [])), 'numeric');
        $input['data_lifecycle'] = self::enum((string) ($source['data_lifecycle'] ?? $input['data_lifecycle']), array_keys((array) ($options['data_lifecycle'] ?? [])), 'classified');

        return $input;
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function evaluate(array $input): array
    {
        $input = self::normalize_input($input);
        $products = CMS_M365CALCULATOR_Catalog::power_platform_products();
        $useCases = CMS_M365CALCULATOR_Catalog::power_platform_use_cases();
        $connectorRules = CMS_M365CALCULATOR_Catalog::power_platform_connector_rules();
        $capacity = CMS_M365CALCULATOR_Catalog::power_platform_capacity_catalog();
        $governance = CMS_M365CALCULATOR_Catalog::power_platform_governance_rules();
        $selectedUseCase = is_array($useCases['use_cases'][$input['use_case']] ?? null) ? $useCases['use_cases'][$input['use_case']] : [];
        $connector = is_array($connectorRules['rules'][$input['connector_type']] ?? null) ? $connectorRules['rules'][$input['connector_type']] : [];
        $seeded = self::evaluate_power_platform_seeded_rights($input, $selectedUseCase, $connector);
        $dataverseFit = self::evaluate_dataverse_for_teams_fit($input, $governance);
        $costs = self::calculate_power_platform_costs($input, $products, $capacity, $seeded, $dataverseFit);
        $score = self::score($input, $connector, $seeded, $dataverseFit, $useCases);
        $bestPractices = self::evaluate_power_platform_best_practices($input, $governance);
        $recommendation = self::build_power_platform_recommendation($input, $selectedUseCase, $connector, $seeded, $dataverseFit, $costs, $useCases);

        return [
            'input' => $input,
            'use_case' => $selectedUseCase,
            'connector' => $connector,
            'seeded' => $seeded,
            'dataverse_fit' => $dataverseFit,
            'costs' => $costs,
            'score' => $score,
            'best_practices' => $bestPractices,
            'recommendation' => $recommendation,
            'warnings' => self::build_warnings($input, $connector, $seeded, $dataverseFit, $costs, $governance, $bestPractices),
            'next_steps' => self::build_next_steps($recommendation, $input, $dataverseFit, $bestPractices),
            'options' => [
                'use_case' => (array) ($products['options']['use_case'] ?? []),
                'months' => (array) ($products['options']['months'] ?? []),
                'connector_type' => (array) ($connectorRules['connector_options'] ?? []),
                'flow_context' => (array) ($products['options']['flow_context'] ?? []),
                'usage_pattern' => (array) ($products['options']['usage_pattern'] ?? []),
                'rpa_mode' => (array) ($products['options']['rpa_mode'] ?? []),
                'bot_scope' => (array) ($products['options']['bot_scope'] ?? []),
                'website_access' => (array) ($products['options']['website_access'] ?? []),
                'environment_strategy' => (array) ($products['options']['environment_strategy'] ?? []),
                'data_policy_state' => (array) ($products['options']['data_policy_state'] ?? []),
                'identity_controls' => (array) ($products['options']['identity_controls'] ?? []),
                'secret_management' => (array) ($products['options']['secret_management'] ?? []),
                'alm_level' => (array) ($products['options']['alm_level'] ?? []),
                'monitoring_level' => (array) ($products['options']['monitoring_level'] ?? []),
                'performance_targets' => (array) ($products['options']['performance_targets'] ?? []),
                'data_lifecycle' => (array) ($products['options']['data_lifecycle'] ?? []),
            ],
            'facts' => [
                'capacity' => (array) ($capacity['facts'] ?? []),
                'governance' => (array) ($governance['warnings'] ?? []),
                'dataverse_for_teams' => (array) ($governance['dataverse_for_teams'] ?? []),
                'best_practices' => (array) ($governance['best_practice_rules'] ?? []),
            ],
            'meta' => [
                'source_checked' => self::source_checked([$products, $useCases, $connectorRules, $capacity, $governance]),
                'price_basis' => (string) ($products['meta']['price_basis'] ?? $capacity['meta']['price_basis'] ?? ''),
                'currency' => (string) ($products['meta']['currency'] ?? 'USD'),
            ],
            'sources' => self::sources([$products, $useCases, $connectorRules, $capacity, $governance]),
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function validate_power_platform_input(array $source): array
    {
        $input = self::normalize_input($source);
        $warnings = [];
        if ((int) ($input['users'] ?? 0) <= 0) {
            $warnings[] = 'Mindestens ein betroffener Nutzer wird benötigt.';
        }
        if ((string) ($input['website_access'] ?? 'none') !== 'none'
            && (int) ($input['authenticated_site_users'] ?? 0) === 0
            && (int) ($input['anonymous_site_users'] ?? 0) === 0
        ) {
            $warnings[] = 'Für Power Pages sollten angemeldete oder anonyme Website-Nutzer geschätzt werden.';
        }
        if ((string) ($input['bot_scope'] ?? 'none') === 'full' && (int) ($input['monthly_copilot_credits'] ?? 0) === 0) {
            $warnings[] = 'Für volle Agent-Szenarien sollte ein monatlicher Copilot-Credit-Verbrauch geschätzt werden.';
        }

        return ['input' => $input, 'warnings' => $warnings, 'valid' => $warnings === []];
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function evaluate_power_platform_use_case(array $input): array
    {
        $input = self::normalize_input($input);
        $useCases = CMS_M365CALCULATOR_Catalog::power_platform_use_cases();
        $useCase = is_array($useCases['use_cases'][$input['use_case']] ?? null) ? $useCases['use_cases'][$input['use_case']] : [];

        return [
            'key' => (string) ($input['use_case'] ?? 'internal_app'),
            'label' => (string) ($useCase['label'] ?? 'Interne App'),
            'recommended_path' => (string) ($useCase['recommended_path'] ?? 'm365_seeded_rights'),
            'secondary_path' => (string) ($useCase['secondary_path'] ?? ''),
            'supports_seeded_rights' => (bool) ($useCase['supports_seeded_rights'] ?? false),
            'needs_capacity_model' => (bool) ($useCase['needs_capacity_model'] ?? false),
            'text' => (string) ($useCase['text'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $useCase
     * @param array<string,mixed> $connector
     * @return array<string,mixed>
     */
    public static function evaluate_power_platform_seeded_rights(array $input, array $useCase, array $connector): array
    {
        $hardBlockers = [];
        if (!(bool) ($useCase['supports_seeded_rights'] ?? false)) {
            $hardBlockers[] = 'Der gewählte Use Case ist kein typischer Microsoft-365-seeded Pfad.';
        }
        if (!(bool) ($connector['seeded_allowed'] ?? false)) {
            $hardBlockers[] = (string) ($connector['message'] ?? 'Der Connector-Typ benötigt einen Standalone-Pfad.');
        }
        if ((string) ($input['flow_context'] ?? 'in_app') === 'standalone' && (string) ($input['use_case'] ?? '') === 'workflow') {
            $hardBlockers[] = 'Eigenständige Premium-Flows brauchen eine passende Power-Automate-Lizenzierung.';
        }
        if ((int) ($input['apps_per_user'] ?? 1) > 1 || (int) ($input['scenario_count'] ?? 1) > 1) {
            $hardBlockers[] = 'Mehrere Apps oder Szenarien je Nutzer sprechen gegen eine reine Seeded-Betrachtung.';
        }
        if ((int) ($input['outside_teams_use'] ?? 0) === 1) {
            $hardBlockers[] = 'Nutzung außerhalb Teams oder außerhalb des Microsoft-365-Kontexts braucht einen erweiterten Pfad.';
        }
        if ((int) ($input['ai_builder_required'] ?? 0) === 1 || (string) ($input['bot_scope'] ?? 'none') === 'full') {
            $hardBlockers[] = 'AI- oder Agent-Szenarien benötigen Credit- und Premium-Betrachtung.';
        }
        if ((string) ($input['rpa_mode'] ?? 'none') !== 'none') {
            $hardBlockers[] = 'RPA ist kein Seeded-Standardpfad.';
        }
        if ((string) ($input['website_access'] ?? 'none') !== 'none') {
            $hardBlockers[] = 'Power Pages wird als Website- und Capacity-Modell geplant.';
        }

        return [
            'sufficient' => $hardBlockers === [],
            'blockers' => $hardBlockers,
            'summary' => $hardBlockers === []
                ? 'Seeded Rechte wirken für dieses Szenario plausibel.'
                : 'Seeded Rechte reichen für das Szenario voraussichtlich nicht aus.',
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $governance
     * @return array<string,mixed>
     */
    public static function evaluate_dataverse_for_teams_fit(array $input, array $governance): array
    {
        $rules = is_array($governance['dataverse_for_teams'] ?? null) ? $governance['dataverse_for_teams'] : [];
        $issues = [];
        $usesDvt = (int) ($input['dataverse_for_teams'] ?? 0) === 1;
        $dbGb = (float) ($input['dataverse_db_gb'] ?? 0);
        $fileGb = (float) ($input['dataverse_file_gb'] ?? 0);
        $limit = (float) ($rules['storage_gb_per_environment'] ?? 2);

        if ($usesDvt && $dbGb + $fileGb > $limit) {
            $issues[] = 'Die geplante Datenmenge überschreitet die typische Dataverse-for-Teams-Grenze je Umgebung.';
        }
        if ($usesDvt && (int) ($input['outside_teams_use'] ?? 0) === 1) {
            $issues[] = 'Nutzung außerhalb Teams erfordert den Upgrade-Pfad zu Dataverse.';
        }
        if ($usesDvt && (int) ($input['ai_builder_required'] ?? 0) === 1) {
            $issues[] = 'AI Builder wird in Dataverse for Teams nicht unterstützt.';
        }
        if ($usesDvt && in_array((string) ($input['rpa_mode'] ?? 'none'), ['attended', 'unattended', 'hosted'], true)) {
            $issues[] = 'Desktop-Flows werden in Dataverse for Teams nicht unterstützt.';
        }
        if ($usesDvt && in_array((string) ($input['connector_type'] ?? 'standard'), ['custom', 'onprem'], true)) {
            $issues[] = 'Custom- und On-Premises-Pfade passen nicht zum Dataverse-for-Teams-Sondermodell.';
        }
        if (!$usesDvt && ($dbGb > 0 || $fileGb > 0 || (int) ($input['advanced_governance'] ?? 0) === 1)) {
            $issues[] = 'Produktives Dataverse sollte mit Premium- und Governance-Modell geplant werden.';
        }

        return [
            'fits' => $issues === [],
            'uses_dataverse_for_teams' => $usesDvt,
            'issues' => $issues,
            'summary' => $issues === []
                ? 'Dataverse for Teams wirkt für den eingegebenen Umfang passend oder ist nicht kritisch.'
                : 'Dataverse for Teams ist für dieses Szenario wahrscheinlich zu eng.',
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $governance
     * @return array<string,mixed>
     */
    public static function evaluate_power_platform_best_practices(array $input, array $governance): array
    {
        $rules = is_array($governance['best_practice_rules'] ?? null) ? $governance['best_practice_rules'] : [];
        $checks = [];
        $score = 100;
        $critical = [];
        $nextSteps = [];
        $sources = [];
        $addCheck = static function (string $area, string $status, string $label, string $text, string $nextStep, string $source, int $penalty) use (&$checks, &$score, &$critical, &$nextSteps, &$sources): void {
            $checks[] = [
                'area' => $area,
                'status' => $status,
                'status_label' => self::best_practice_status_label($status),
                'label' => $label,
                'text' => $text,
                'next_step' => $nextStep,
                'source' => $source,
            ];
            $score -= $penalty;
            if ($status === 'danger') {
                $critical[] = $label . ': ' . $text;
            }
            if ($nextStep !== '') {
                $nextSteps[] = $nextStep;
            }
            if ($source !== '') {
                $sources[] = $source;
            }
        };

        $environmentStrategy = (string) ($input['environment_strategy'] ?? 'dev_test_prod');
        $environmentSource = self::best_practice_source($rules, 'alm');
        if ($environmentStrategy === 'default_only') {
            $addCheck('ALM', 'danger', 'Umgebungsstrategie zu schmal', 'Default- oder Einzelumgebungen passen nicht zu produktiven Power-Platform-Workloads.', 'Mindestens Entwicklung, Test und Produktion trennen und die Default-Umgebung kontrollieren.', $environmentSource, 25);
        } elseif ($environmentStrategy === 'dev_prod') {
            $addCheck('ALM', 'warning', 'Teststufe fehlt', 'Ohne separate Testumgebung fehlen Deployment- und End-to-End-Validierung vor Produktion.', 'Eine dedizierte Testumgebung für Lösungsexport, Import und Fachabnahme ergänzen.', $environmentSource, 12);
        } else {
            $addCheck('ALM', 'ok', 'Umgebungsstrategie tragfähig', 'Die gewählte Trennung unterstützt Entwicklung, Validierung und produktiven Betrieb.', 'Regionen und Update-Stationen bei internationalen Umgebungen vor Rollout abgleichen.', $environmentSource, 0);
        }

        $policyState = (string) ($input['data_policy_state'] ?? 'classic');
        $policySource = self::best_practice_source($rules, 'data_policy');
        $advancedPolicySource = self::best_practice_source($rules, 'advanced_connector_policy');
        $connectorType = (string) ($input['connector_type'] ?? 'standard');
        if ($policyState === 'none') {
            $addCheck('Security', 'danger', 'Datenrichtlinien fehlen', 'Connector-Nutzung ist ohne dokumentierte Guardrails schwer steuerbar und kann Datenpfade ungewollt öffnen.', 'Datenrichtlinien je Umgebung oder Umgebungsgruppe definieren und neue Connectoren bewusst klassifizieren.', $policySource, 20);
        } elseif ($policyState === 'advanced') {
            $addCheck('Security', 'warning', 'Allowlist-Pfad mit Grenzen', 'Advanced Connector Policies sind granular, gelten derzeit aber vor allem für zertifizierte Connectoren.', 'Preview-Grenzen dokumentieren und Custom- oder HTTP-Pfade zusätzlich klassisch steuern.', $advancedPolicySource, in_array($connectorType, ['custom', 'onprem'], true) ? 12 : 6);
        } elseif ($policyState === 'managed_advanced') {
            $addCheck('Security', 'ok', 'Granulare Connector-Steuerung', 'Managed Environments mit Connector-Regeln verbessern Default-Deny, Transparenz und Betriebskontrolle.', 'Wirksamkeit regelmäßig prüfen und Policy-Änderungen mit Maker-Kommunikation begleiten.', $advancedPolicySource, 0);
        } else {
            $addCheck('Security', 'ok', 'Datenrichtlinien vorhanden', 'Klassische Datenrichtlinien bilden eine belastbare Basis für Business-, Non-Business- und gesperrte Connectoren.', 'Custom Connectoren, neue Connectoren und Gültigkeitsbereiche quartalsweise überprüfen.', $policySource, 0);
        }

        $identityControls = (string) ($input['identity_controls'] ?? 'groups');
        $identitySource = self::best_practice_source($rules, 'identity');
        if ($identityControls === 'basic') {
            $addCheck('Security', 'danger', 'Zugriffe zu individuell', 'Direkte Zuweisungen erschweren least privilege, Rollenwechsel, Audits und Gastkontrolle.', 'Rollen über Entra-Gruppen modellieren und Adminrechte zeitlich begrenzen.', $identitySource, 18);
        } elseif ($identityControls === 'groups') {
            $addCheck('Security', 'warning', 'Gruppenbasis ohne starke Zusatzkontrollen', 'Gruppen reduzieren Pflegeaufwand, decken aber bedingte Zugriffe und privilegierte Rollen noch nicht vollständig ab.', 'Conditional Access, getrennte Adminrollen und Access Reviews für produktive Umgebungen ergänzen.', $identitySource, 8);
        } else {
            $addCheck('Security', 'ok', 'Identitätskontrollen reif', 'Gruppen, bedingte Zugriffe, zeitlich begrenzte Adminrollen und Reviews senken die Angriffsfläche.', 'Audit-Trail und Gastzugriffe regelmäßig gegen Rollenmodell prüfen.', $identitySource, 0);
        }

        $secretManagement = (string) ($input['secret_management'] ?? 'environment_variables');
        $secretSource = self::best_practice_source($rules, 'secrets');
        if ($secretManagement === 'embedded') {
            $addCheck('Security', 'danger', 'Zugangsdaten unsicher abgelegt', 'Geheime Werte in Apps, Flows, Konfiguration oder Builds erhöhen Leckage- und Wiederverwendungsrisiken.', 'Key Vault, Secret Store oder getrennte Umgebungsvariablen einführen und Ablage in Code/Builds prüfen.', $secretSource, 20);
        } elseif ($secretManagement === 'environment_variables') {
            $addCheck('Security', 'warning', 'Zugangsdaten getrennt, aber Rotation offen', 'Umgebungsvariablen helfen beim ALM-Transport, ersetzen aber keinen vollständigen Wechsel- und Auditprozess.', 'Rotation, Berechtigungen und Protokollierung für geheime Werte verbindlich planen.', $secretSource, 8);
        } else {
            $addCheck('Security', 'ok', 'Zugangsdaten sauber gesteuert', 'Secret Store, Rotation, Auditing und Deployment-Integration passen zu produktiven Integrationspfaden.', 'Wechselprozesse in Runbooks und Pipelines regelmäßig testen.', $secretSource, 0);
        }

        $almLevel = (string) ($input['alm_level'] ?? 'managed_solutions');
        $solutionSource = self::best_practice_source($rules, 'alm');
        if ($almLevel === 'direct_edit') {
            $addCheck('ALM', 'danger', 'Direkte Produktionsänderungen', 'Direkte Änderungen in Produktion erhöhen Ausfall-, Abhängigkeits- und Rollback-Risiken.', 'Unmanaged Entwicklung, Managed Solutions für Zielumgebungen und Freigabeprozess einführen.', $solutionSource, 22);
        } elseif ($almLevel === 'unmanaged_only') {
            $addCheck('ALM', 'warning', 'ALM-Reife begrenzt', 'Unmanaged-only-Prozesse sind für produktive Zielumgebungen schwer wartbar und schlecht rückverfolgbar.', 'Managed Solutions als Build-Artefakte und Source Control als führende Quelle etablieren.', $solutionSource, 12);
        } else {
            $addCheck('ALM', 'ok', 'Solution-Modell belastbar', 'Managed Solutions und strukturierte Umgebungen unterstützen sichere Updates und Nachvollziehbarkeit.', 'Stage-and-upgrade und klare Publisher-/Layering-Strategie für Releases nutzen.', $solutionSource, 0);
        }

        $monitoringLevel = (string) ($input['monitoring_level'] ?? 'admin_center');
        $operationsSource = self::best_practice_source($rules, 'operations');
        if ($monitoringLevel === 'none') {
            $addCheck('Betrieb', 'danger', 'Monitoring fehlt', 'Ohne Metriken, Logs und Alerts werden Engpässe, Fehlermuster und Sicherheitsereignisse spät erkannt.', 'Admin Center, Checker, Application Insights oder zentrale Log-Auswertung vor Go-live festlegen.', $operationsSource, 18);
        } elseif ($monitoringLevel === 'admin_center') {
            $addCheck('Betrieb', 'warning', 'Basis-Monitoring vorhanden', 'Admin Center und Checker helfen im Alltag, liefern aber nur begrenzt zentrale Betriebs- und Incident-Signale.', 'Für kritische Apps Application Insights, Azure Monitor oder Log Analytics einplanen.', $operationsSource, 8);
        } else {
            $addCheck('Betrieb', 'ok', 'Monitoring und Betrieb belastbar', 'Zentrale Telemetrie, Alerts und Betriebsprozesse unterstützen schnelle Analyse und kontinuierliche Verbesserung.', 'Dashboards, Alarmwege und Verantwortlichkeiten regelmäßig in Incident-Übungen prüfen.', $operationsSource, 0);
        }

        $performanceTargets = (string) ($input['performance_targets'] ?? 'numeric');
        $performanceSource = self::best_practice_source($rules, 'performance');
        if ($performanceTargets === 'none') {
            $addCheck('Performance', 'danger', 'Performance-Ziele fehlen', 'Ohne numerische Zielwerte lassen sich kritische Flows, Apps und Datenzugriffe nicht belastbar bewerten.', 'Antwortzeiten, Durchsatz, Laufzeiten und Lastspitzen je kritischem Flow definieren.', $performanceSource, 16);
        } elseif ($performanceTargets === 'rough') {
            $addCheck('Performance', 'warning', 'Performance-Ziele zu grob', 'Grobe Erwartungen reichen für saisonale Last, Connector-Limits und produktionsnahe Tests oft nicht aus.', 'Numerische Ziele und Tests in einer produktionsnahen Umgebung ergänzen.', $performanceSource, 8);
        } else {
            $addCheck('Performance', 'ok', 'Performance-Ziele messbar', 'Numerische Zielwerte und produktionsnahe Validierung unterstützen Kapazitäts- und Architekturentscheidungen.', 'Flow Checker, Solution Checker, Monitor und Lastdaten regelmäßig in Verbesserungen überführen.', $performanceSource, 0);
        }

        $dataLifecycle = (string) ($input['data_lifecycle'] ?? 'classified');
        $dataPerformanceSource = self::best_practice_source($rules, 'data_performance');
        if ($dataLifecycle === 'unclassified') {
            $addCheck('Daten', 'danger', 'Datenklassen fehlen', 'Ohne Klassifikation sind Schutzbedarf, Zugriff, Aufbewahrung und Datenmodell schwer priorisierbar.', 'Datenklassen, Schutzbedarf und Aufbewahrung für Dataverse, Dateien und Integrationen festlegen.', $dataPerformanceSource, 18);
        } elseif ($dataLifecycle === 'classified') {
            $addCheck('Daten', 'warning', 'Datenmodell noch nicht optimiert', 'Klassifikation ist vorhanden, aber Archivierung, Bereinigung und Performance-Tuning sind noch offen.', 'Serverseitige Sichten, Caching, Batch-Verarbeitung, Archivierung und Bereinigung bewerten.', $dataPerformanceSource, 6);
        } else {
            $addCheck('Daten', 'ok', 'Datenlebenszyklus geplant', 'Klassifikation, Bereinigung, Archivierung und Optimierung reduzieren Last, Kosten und Betriebsrisiken.', 'Datenwachstum und Query-Performance im Monitoring regelmäßig nachhalten.', $dataPerformanceSource, 0);
        }

        if ((int) ($input['advanced_governance'] ?? 0) === 1 && (int) ($input['managed_environment'] ?? 0) === 0) {
            $addCheck('Governance', 'danger', 'Enterprise-Kontrollen ohne Managed Environment', 'CMK, Lockbox, vNet, Sharing-Limits und erweiterte Auswertungen benötigen ein passendes Governance-Zielmodell.', 'Managed Environments und Lizenz-/Compliance-Voraussetzungen vor Architekturentscheidung prüfen.', self::best_practice_source($rules, 'managed_environment'), 16);
        }

        if (in_array($connectorType, ['custom', 'onprem'], true) && !in_array($policyState, ['classic', 'managed_advanced'], true)) {
            $addCheck('Security', 'warning', 'Custom-Connector-Pfad absichern', 'Custom- oder On-Premises-Pfade brauchen heute zusätzliche klassische Steuerung und Review der Datenwege.', 'Connector-Besitzer, Endpunkte, Datenklassen und erlaubte Umgebungen dokumentieren.', $policySource, 8);
        }

        if ($almLevel !== 'pipelines_source_control' && (int) ($input['environments'] ?? 1) > 2) {
            $addCheck('ALM', 'warning', 'Deployment-Prozess kann bremsen', 'Mehrere Umgebungen ohne CI/CD erhöhen Importzeiten, manuelle Schritte und Fehlerrisiko.', 'Source Control, Build-Artefakte, stage-and-upgrade und kleine Lösungsschichten einführen.', self::best_practice_source($rules, 'deployment_performance'), 8);
        }

        $score = max(0, min(100, $score));
        $tone = $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'danger');
        $label = $tone === 'success'
            ? 'Best-Practice-Reife gut'
            : ($tone === 'warning' ? 'Best-Practice-Reife prüfen' : 'Best-Practice-Reife kritisch');

        return [
            'score' => $score,
            'tone' => $tone,
            'label' => $label,
            'summary' => $score >= 80
                ? 'Security, ALM, Performance und Betrieb wirken für das Szenario solide vorbereitet.'
                : 'Vor produktivem Ausbau sollten die markierten Governance-, Sicherheits-, ALM- oder Performance-Punkte geklärt werden.',
            'checks' => $checks,
            'critical' => array_values(array_unique($critical)),
            'next_steps' => array_slice(array_values(array_unique($nextSteps)), 0, 8),
            'sources' => array_values(array_unique(array_filter($sources))),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $productsCatalog
     * @param array<string,mixed> $capacityCatalog
     * @param array<string,mixed> $seeded
     * @param array<string,mixed> $dataverseFit
     * @return array<string,mixed>
     */
    public static function calculate_power_platform_costs(array $input, array $productsCatalog, array $capacityCatalog, array $seeded, array $dataverseFit): array
    {
        $products = is_array($productsCatalog['products'] ?? null) ? $productsCatalog['products'] : [];
        $capacity = is_array($capacityCatalog['capacity'] ?? null) ? $capacityCatalog['capacity'] : [];
        $payg = is_array($capacityCatalog['payg'] ?? null) ? $capacityCatalog['payg'] : [];
        $items = [];
        $users = (int) ($input['users'] ?? 1);
        $appsPerUser = max(1, (int) ($input['apps_per_user'] ?? 1));
        $useCase = (string) ($input['use_case'] ?? 'internal_app');
        $connectorType = (string) ($input['connector_type'] ?? 'standard');
        $rpaMode = (string) ($input['rpa_mode'] ?? 'none');
        $websiteAccess = (string) ($input['website_access'] ?? 'none');
        $usePayg = (int) ($input['azure_subscription'] ?? 0) === 1 && (string) ($input['usage_pattern'] ?? 'stable') !== 'stable';

        if ((bool) ($seeded['sufficient'] ?? false)) {
            $items[] = self::cost_item('Microsoft 365 seeded Rechte', 1, 0, 'enthalten', 'Basisrechte im Microsoft-365- oder Teams-Kontext.');
        } elseif ($usePayg && in_array($useCase, ['internal_app', 'teams_extension'], true)) {
            $price = (float) ($payg['power_apps_per_app_active_user']['unit_price_monthly'] ?? 10);
            $items[] = self::cost_item('Power Apps PAYG', $users * $appsPerUser, $price, 'aktive Nutzer/App', 'Für schwankende Nutzung mit Azure-Abrechnung.');
        } elseif (in_array($useCase, ['internal_app', 'teams_extension'], true) && $appsPerUser <= 1 && (int) ($input['scenario_count'] ?? 1) <= 1 && $connectorType !== 'standard') {
            $items[] = self::product_item($products, 'power_apps_per_app', $users * $appsPerUser, 'Nutzer/App');
        } elseif (in_array($useCase, ['internal_app', 'teams_extension', 'citizen_development'], true) || !$dataverseFit['fits']) {
            $items[] = self::product_item($products, 'power_apps_premium', $users, 'Nutzer');
        }

        if ($useCase === 'workflow' || (string) ($input['flow_context'] ?? '') === 'standalone') {
            if ($usePayg && (int) ($input['monthly_flow_runs'] ?? 0) > 0) {
                $runPrice = $rpaMode === 'none'
                    ? (float) ($payg['power_automate_cloud_or_attended_run']['unit_price'] ?? 0.6)
                    : (float) ($payg['power_automate_unattended_or_hosted_run']['unit_price'] ?? 3);
                $items[] = self::cost_item('Power Automate PAYG Runs', (int) ($input['monthly_flow_runs'] ?? 0), $runPrice, 'Runs', 'Für seltene oder saisonale Premium-Flows.');
            } elseif ($rpaMode === 'none') {
                $items[] = self::product_item($products, 'power_automate_premium', max(1, (int) ($input['makers'] ?? 1)), 'Nutzer');
            }
        }

        if ($rpaMode === 'attended') {
            $items[] = self::product_item($products, 'power_automate_premium', max(1, (int) ($input['makers'] ?? 1)), 'Nutzer');
        } elseif ($rpaMode === 'unattended') {
            $items[] = self::product_item($products, 'power_automate_process', max(1, (int) ($input['rpa_bots'] ?? 1)), 'Bots');
        } elseif ($rpaMode === 'hosted') {
            $items[] = self::product_item($products, 'power_automate_hosted_rpa', max(1, (int) ($input['rpa_bots'] ?? 1)), 'Hosted Bots');
        }

        if ($websiteAccess !== 'none' || $useCase === 'website') {
            $authUsers = (int) ($input['authenticated_site_users'] ?? 0);
            $anonUsers = (int) ($input['anonymous_site_users'] ?? 0);
            if (in_array($websiteAccess, ['internal', 'authenticated', 'mixed'], true) || $authUsers > 0) {
                $pack = is_array($products['power_pages_authenticated_pack'] ?? null) ? $products['power_pages_authenticated_pack'] : [];
                $packs = max(1, (int) ceil(max(1, $authUsers) / max(1, (int) ($pack['pack_size'] ?? 100))));
                $items[] = self::cost_item('Power Pages angemeldete Nutzer', $packs, (float) ($pack['list_price_monthly'] ?? 200), 'Packs', 'Subscription-Kapazität je Website.');
            }
            if (in_array($websiteAccess, ['anonymous', 'mixed'], true) || $anonUsers > 0) {
                $pack = is_array($products['power_pages_anonymous_pack'] ?? null) ? $products['power_pages_anonymous_pack'] : [];
                $packs = max(1, (int) ceil(max(1, $anonUsers) / max(1, (int) ($pack['pack_size'] ?? 500))));
                $items[] = self::cost_item('Power Pages anonyme Besucher', $packs, (float) ($pack['list_price_monthly'] ?? 75), 'Packs', 'Subscription-Kapazität je Website.');
            }
        }

        if ((string) ($input['bot_scope'] ?? 'none') === 'full' || (int) ($input['monthly_copilot_credits'] ?? 0) > 0) {
            $creditPrice = (float) ($capacity['copilot_credit']['unit_price_monthly'] ?? 0.01);
            $items[] = self::cost_item('Copilot Studio Credits', (int) ($input['monthly_copilot_credits'] ?? 0), $creditPrice, 'Credits', 'PAYG-Planungswert für Agenten und AI-Verbrauch.');
        }

        $capacityItems = self::calculate_power_platform_capacity_costs($input, $capacity, $seeded);
        $items = array_merge($items, $capacityItems);
        $monthlyTotal = array_sum(array_map(static fn(array $item): float => (float) ($item['monthly'] ?? 0), $items));
        $months = (int) ($input['months'] ?? 12);

        return [
            'items' => $items,
            'monthly_total' => $monthlyTotal,
            'annual_total' => $monthlyTotal * 12,
            'period_total' => $monthlyTotal * $months,
            'months' => $months,
            'currency' => (string) ($productsCatalog['meta']['currency'] ?? 'USD'),
            'primary_cost_driver' => self::primary_cost_driver($items),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $capacity
     * @param array<string,mixed> $seeded
     * @return array<int,array<string,mixed>>
     */
    public static function calculate_power_platform_capacity_costs(array $input, array $capacity, array $seeded): array
    {
        $items = [];
        $db = (float) ($input['dataverse_db_gb'] ?? 0);
        $file = (float) ($input['dataverse_file_gb'] ?? 0);
        $log = (float) ($input['dataverse_log_gb'] ?? 0);
        if ($db > 0) {
            $items[] = self::cost_item('Dataverse Database Capacity', $db, (float) ($capacity['dataverse_database_gb']['unit_price_monthly'] ?? 40), 'GB', 'Zusätzliche Datenbankkapazität.');
        }
        if ($file > 0) {
            $items[] = self::cost_item('Dataverse File Capacity', $file, (float) ($capacity['dataverse_file_gb']['unit_price_monthly'] ?? 2), 'GB', 'Zusätzliche Dateikapazität.');
        }
        if ($log > 0) {
            $items[] = self::cost_item('Dataverse Log Capacity', $log, (float) ($capacity['dataverse_log_gb']['unit_price_monthly'] ?? 10), 'GB', 'Zusätzliche Audit- und Logkapazität.');
        }
        $processMiningGb = (float) ($input['process_mining_gb'] ?? 0);
        if ($processMiningGb > 0) {
            $packs = (int) ceil($processMiningGb / 100);
            $items[] = self::cost_item('Process Mining Capacity', $packs, (float) ($capacity['process_mining_100gb']['unit_price_monthly'] ?? 5000), '100-GB-Packs', 'Process Mining setzt Power Automate Premium voraus.');
        }
        $dailyRequests = (int) ($input['api_requests_per_day'] ?? 0);
        if ($dailyRequests > 0) {
            $perUserLimit = (bool) ($seeded['sufficient'] ?? false) ? 6000 : 40000;
            $included = max(1, (int) ($input['users'] ?? 1)) * $perUserLimit;
            if ($dailyRequests > $included) {
                $over = $dailyRequests - $included;
                $packs = (int) ceil($over / 50000);
                $items[] = self::cost_item('Power Platform Requests Add-on', $packs, (float) ($capacity['power_platform_requests_addon']['unit_price_monthly'] ?? 50), 'Packs', 'Zusätzliche Tageskontingente für hohe API-Last.');
            }
        }
        if ((int) ($input['ai_builder_required'] ?? 0) === 1 && (int) ($input['monthly_copilot_credits'] ?? 0) === 0) {
            $items[] = self::cost_item('AI Builder Kapazität prüfen', 1, 0, 'Review', 'AI Builder wird zunehmend über Copilot Credits geplant.');
        }

        return $items;
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function calculate_power_platform_credit_usage(array $input): array
    {
        $input = self::normalize_input($input);
        $capacityCatalog = CMS_M365CALCULATOR_Catalog::power_platform_capacity_catalog();
        $capacity = is_array($capacityCatalog['capacity'] ?? null) ? $capacityCatalog['capacity'] : [];
        $credits = (int) ($input['monthly_copilot_credits'] ?? 0);
        $unitPrice = (float) ($capacity['copilot_credit']['unit_price_monthly'] ?? 0.01);

        return [
            'credits' => $credits,
            'unit_price' => $unitPrice,
            'monthly' => $credits * $unitPrice,
            'annual' => $credits * $unitPrice * 12,
            'label' => 'Copilot Credits',
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $selectedUseCase
     * @param array<string,mixed> $connector
     * @param array<string,mixed> $seeded
     * @param array<string,mixed> $dataverseFit
     * @param array<string,mixed> $costs
     * @param array<string,mixed> $useCases
     * @return array<string,mixed>
     */
    public static function build_power_platform_recommendation(array $input, array $selectedUseCase, array $connector, array $seeded, array $dataverseFit, array $costs, array $useCases): array
    {
        $recommendations = is_array($useCases['recommendations'] ?? null) ? $useCases['recommendations'] : [];
        $key = 'entry_path';
        $capacityDriver = self::has_capacity_driver($input);
        $premiumConnector = !(bool) ($connector['seeded_allowed'] ?? true);
        $multiApp = (int) ($input['apps_per_user'] ?? 1) > 1 || (int) ($input['scenario_count'] ?? 1) > 1;

        if (!(bool) ($dataverseFit['fits'] ?? true) || (int) ($input['advanced_governance'] ?? 0) === 1) {
            $key = 'architecture_review';
        } elseif ($capacityDriver) {
            $key = 'capacity_required';
        } elseif ((bool) ($seeded['sufficient'] ?? false)) {
            $key = 'seeded_sufficient';
        } elseif ($premiumConnector && !$multiApp && (int) ($input['apps_per_user'] ?? 1) <= 1) {
            $key = 'entry_path';
        } else {
            $key = 'per_user_premium';
        }

        $entry = is_array($recommendations[$key] ?? null) ? $recommendations[$key] : [];

        return [
            'key' => $key,
            'label' => (string) ($entry['label'] ?? 'Power Platform Pfad prüfen'),
            'tone' => (string) ($entry['tone'] ?? 'info'),
            'text' => (string) ($entry['text'] ?? 'Das Szenario sollte anhand der Treiber bewertet werden.'),
            'use_case_text' => (string) ($selectedUseCase['text'] ?? ''),
            'monthly_total' => (float) ($costs['monthly_total'] ?? 0),
            'period_total' => (float) ($costs['period_total'] ?? 0),
            'primary_cost_driver' => (string) ($costs['primary_cost_driver'] ?? ''),
        ];
    }

    /** @return array<string,mixed> */
    public static function load_power_platform_products(): array
    {
        return CMS_M365CALCULATOR_Catalog::power_platform_products();
    }

    /** @return array<string,mixed> */
    public static function load_power_platform_use_cases(): array
    {
        return CMS_M365CALCULATOR_Catalog::power_platform_use_cases();
    }

    /** @return array<string,mixed> */
    public static function load_power_platform_connector_rules(): array
    {
        return CMS_M365CALCULATOR_Catalog::power_platform_connector_rules();
    }

    /** @return array<string,mixed> */
    public static function load_power_platform_capacity_catalog(): array
    {
        return CMS_M365CALCULATOR_Catalog::power_platform_capacity_catalog();
    }

    /** @return array<string,mixed> */
    public static function load_power_platform_governance_rules(): array
    {
        return CMS_M365CALCULATOR_Catalog::power_platform_governance_rules();
    }

    public static function render_power_platform_page(): string
    {
        return CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-power-platform-cost-calculator.php';
    }

    /**
     * @param array<string,mixed> $result
     * @return array<string,mixed>
     */
    public static function export_power_platform_pdf(array $result): array
    {
        return [
            'title' => 'Power Platform Kosten-Kalkulator',
            'recommendation' => (string) ($result['recommendation']['label'] ?? ''),
            'monthly_total' => (float) ($result['costs']['monthly_total'] ?? 0),
            'period_total' => (float) ($result['costs']['period_total'] ?? 0),
            'sections' => ['Empfehlung', 'Kostenblöcke', 'Seeded Fit', 'Dataverse for Teams', 'Best-Practice-Review', 'Warnungen', 'Nächste Schritte'],
            'delivery' => 'browser_print',
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $connector
     * @param array<string,mixed> $seeded
     * @param array<string,mixed> $dataverseFit
     * @param array<string,mixed> $useCases
     * @return array<string,mixed>
     */
    private static function score(array $input, array $connector, array $seeded, array $dataverseFit, array $useCases): array
    {
        $weights = is_array($useCases['score_weights'] ?? null) ? $useCases['score_weights'] : [];
        $points = 0;
        $reasons = [];
        $add = static function (int $value, string $reason) use (&$points, &$reasons): void {
            $points += $value;
            $reasons[] = ['points' => $value, 'reason' => $reason];
        };

        if ((bool) ($seeded['sufficient'] ?? false)) {
            $add((int) ($weights['seeded_context'] ?? 3), 'M365-/Teams-naher Standardpfad.');
        }
        if ((int) ($input['apps_per_user'] ?? 1) <= 1 && (int) ($input['scenario_count'] ?? 1) === 1) {
            $add((int) ($weights['single_app'] ?? 3), 'Ein klar abgegrenztes App-Szenario.');
        }
        if ((string) ($input['usage_pattern'] ?? '') === 'unpredictable' && (int) ($input['azure_subscription'] ?? 0) === 1) {
            $add((int) ($weights['unpredictable_payg'] ?? 2), 'Schwankende Nutzung mit Azure-Abrechnung.');
        }
        if ((int) ($input['apps_per_user'] ?? 1) > 1 || (int) ($input['makers'] ?? 0) > 20) {
            $add((int) ($weights['multi_app_premium'] ?? 2), 'Mehrere Apps oder viele Maker sprechen für Premium.');
        }
        if (self::has_capacity_driver($input)) {
            $add((int) ($weights['capacity_driver'] ?? 3), 'Capacity-, Bot-, Website- oder Credit-Treiber vorhanden.');
        }
        if (!(bool) ($connector['seeded_allowed'] ?? true)) {
            $add((int) ($weights['premium_connector_penalty'] ?? -4), 'Premium-, Custom- oder On-Premises-Treiber.');
        }
        if ((int) ($input['ai_builder_required'] ?? 0) === 1 || (int) ($input['monthly_copilot_credits'] ?? 0) > 0) {
            $add((int) ($weights['ai_complexity_penalty'] ?? -3), 'AI- und Credit-Verbrauch erhöhen die Komplexität.');
        }
        if (!(bool) ($dataverseFit['fits'] ?? true)) {
            $add((int) ($weights['dataverse_teams_penalty'] ?? -3), 'Dataverse-for-Teams-Grenzen oder Upgrade-Pfad betroffen.');
        }
        if ((int) ($input['advanced_governance'] ?? 0) === 1) {
            $add((int) ($weights['governance_penalty'] ?? -2), 'Erweiterte Governance benötigt genauere Architekturprüfung.');
        }

        return ['value' => $points, 'reasons' => $reasons];
    }

    /** @param array<string,mixed> $input */
    private static function has_capacity_driver(array $input): bool
    {
        return in_array((string) ($input['rpa_mode'] ?? 'none'), ['unattended', 'hosted'], true)
            || (string) ($input['website_access'] ?? 'none') !== 'none'
            || (string) ($input['use_case'] ?? '') === 'website'
            || (string) ($input['bot_scope'] ?? 'none') === 'full'
            || (int) ($input['monthly_copilot_credits'] ?? 0) > 0
            || (float) ($input['process_mining_gb'] ?? 0) > 0;
    }

    /**
     * @param array<string,mixed> $products
     * @return array<string,mixed>
     */
    private static function product_item(array $products, string $key, int $quantity, string $unit): array
    {
        $product = is_array($products[$key] ?? null) ? $products[$key] : [];
        return self::cost_item((string) ($product['name'] ?? $key), $quantity, (float) ($product['list_price_monthly'] ?? 0), $unit, (string) ($product['summary'] ?? ''));
    }

    /** @return array<string,mixed> */
    private static function cost_item(string $label, float $quantity, float $unitPrice, string $unit, string $note): array
    {
        return [
            'label' => $label,
            'quantity' => $quantity,
            'unit' => $unit,
            'unit_price' => $unitPrice,
            'monthly' => $quantity * $unitPrice,
            'note' => $note,
        ];
    }

    /** @param array<string,mixed> $rules */
    private static function best_practice_source(array $rules, string $key): string
    {
        return (string) ($rules[$key]['source'] ?? '');
    }

    private static function best_practice_status_label(string $status): string
    {
        return match ($status) {
            'ok' => 'passt',
            'warning' => 'prüfen',
            'danger' => 'kritisch',
            default => 'offen',
        };
    }

    /** @param array<int,array<string,mixed>> $items */
    private static function primary_cost_driver(array $items): string
    {
        usort($items, static fn(array $left, array $right): int => (float) ($right['monthly'] ?? 0) <=> (float) ($left['monthly'] ?? 0));
        return (string) ($items[0]['label'] ?? 'Keine Zusatzkosten im Modell');
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $connector
     * @param array<string,mixed> $seeded
     * @param array<string,mixed> $dataverseFit
     * @param array<string,mixed> $costs
     * @param array<string,mixed> $governance
     * @param array<string,mixed> $bestPractices
     * @return array<int,string>
     */
    private static function build_warnings(array $input, array $connector, array $seeded, array $dataverseFit, array $costs, array $governance, array $bestPractices): array
    {
        $warnings = [];
        foreach ((array) ($seeded['blockers'] ?? []) as $blocker) {
            $warnings[] = (string) $blocker;
        }
        foreach ((array) ($dataverseFit['issues'] ?? []) as $issue) {
            $warnings[] = (string) $issue;
        }
        if ((int) ($input['advanced_governance'] ?? 0) === 1) {
            $warnings[] = 'CMK, Customer Lockbox oder vNet sollten gegen E5-/Compliance-Voraussetzungen und Managed-Environments geprüft werden.';
        }
        if ((float) ($input['process_mining_gb'] ?? 0) > 0) {
            $warnings[] = 'Process Mining kann zusätzlichen Power-BI- und Capacity-Bedarf auslösen.';
        }
        if ((int) ($input['ai_builder_required'] ?? 0) === 1) {
            $warnings[] = 'AI Builder wird für neue Planungen zunehmend über Copilot Credits bewertet; bestehende AI-Builder-Add-ons separat prüfen.';
        }
        if ((int) ($input['azure_subscription'] ?? 0) === 0 && (string) ($input['usage_pattern'] ?? '') === 'unpredictable') {
            $warnings[] = 'Für PAYG-Simulationen wird eine Azure Subscription als kaufmännischer Pfad benötigt.';
        }
        if ((float) ($costs['monthly_total'] ?? 0) === 0.0 && !(bool) ($seeded['sufficient'] ?? false)) {
            $warnings[] = 'Kosten können bei Spezialverträgen oder fehlenden Mengenangaben erst nach Pflege konkreter Kapazitäten sichtbar werden.';
        }
        $governanceWarnings = is_array($governance['warnings'] ?? null) ? $governance['warnings'] : [];
        if ((int) ($input['dataverse_for_teams'] ?? 0) === 1 && !empty($governanceWarnings['dataverse_for_teams_limit'])) {
            $warnings[] = (string) $governanceWarnings['dataverse_for_teams_limit'];
        }
        foreach (array_slice((array) ($bestPractices['critical'] ?? []), 0, 4) as $critical) {
            $warnings[] = (string) $critical;
        }

        return array_values(array_unique(array_filter($warnings)));
    }

    /**
     * @param array<string,mixed> $recommendation
     * @param array<string,mixed> $input
     * @param array<string,mixed> $dataverseFit
     * @param array<string,mixed> $bestPractices
     * @return array<int,string>
     */
    private static function build_next_steps(array $recommendation, array $input, array $dataverseFit, array $bestPractices): array
    {
        $steps = [
            'Produktive Connectoren, Umgebungen und App-/Flow-Besitzer inventarisieren.',
            'Listenpreise durch CSP-, EA-, MCA- oder Vertragskonditionen ersetzen.',
            'Maker-, Endnutzer- und Capacity-Mengen vor Beschaffung gegen reale Nutzung prüfen.',
        ];
        if ((string) ($recommendation['key'] ?? '') === 'architecture_review') {
            array_unshift($steps, 'Architektur-, Governance- und Dataverse-Zielmodell vor der Lizenzentscheidung festlegen.');
        }
        if (!(bool) ($dataverseFit['fits'] ?? true)) {
            $steps[] = 'Dataverse-for-Teams-Umgebungen auf Upgrade-Pfad, Datenmenge und ALM-Anforderungen prüfen.';
        }
        if ((int) ($input['monthly_copilot_credits'] ?? 0) > 0 || (int) ($input['ai_builder_required'] ?? 0) === 1) {
            $steps[] = 'Copilot- und AI-Verbrauch monatlich schätzen und Monitoring in Power Platform Admin Center einplanen.';
        }
        foreach ((array) ($bestPractices['next_steps'] ?? []) as $bestPracticeStep) {
            $steps[] = (string) $bestPracticeStep;
        }

        return array_values(array_unique($steps));
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

    /** @param array<int,array<string,mixed>> $catalogs */
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

    /** @param array<int,string> $allowed */
    private static function enum_int(string $value, array $allowed, int $fallback): int
    {
        return in_array($value, $allowed, true) ? (int) $value : $fallback;
    }
}
