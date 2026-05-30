<?php
/**
 * CMS M365 Tools – Frontline Worker Lizenz-Eignung-Check.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Frontline_Worker_Check
{
    /**
     * @return array<string,mixed>
     */
    public static function default_input(): array
    {
        return [
            'preset' => 'retail',
            'role' => 'retail_associate',
            'device_model' => 'shared_device',
            'users' => 100,
            'baseline_plan' => 'business_premium',
            'direct_customer_contact' => true,
            'deskless_mobile' => true,
            'shift_based' => true,
            'teams_chat_shifts_tasks' => true,
            'mail_required' => false,
            'sharepoint_onedrive_viva' => true,
            'power_platform_required' => false,
            'desktop_apps_required' => false,
            'high_security_compliance' => false,
            'identity_device_controls_ready' => true,
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_input(array $source): array
    {
        $presets = CMS_M365CALCULATOR_Catalog::frontline_industry_presets();
        $presetItems = is_array($presets['presets'] ?? null) ? $presets['presets'] : [];
        $userTypes = CMS_M365CALCULATOR_Catalog::frontline_user_type_matrix();
        $roles = is_array($userTypes['roles'] ?? null) ? $userTypes['roles'] : [];
        $devices = is_array($userTypes['device_models'] ?? null) ? $userTypes['device_models'] : [];
        $plans = CMS_M365CALCULATOR_Catalog::frontline_plan_matrix();
        $planItems = is_array($plans['plans'] ?? null) ? $plans['plans'] : [];

        $defaults = self::default_input();
        $presetKey = self::choice((string) ($source['preset'] ?? $defaults['preset']), array_keys($presetItems), (string) $defaults['preset']);
        $preset = is_array($presetItems[$presetKey] ?? null) ? $presetItems[$presetKey] : [];
        $base = array_merge($defaults, $preset);

        return [
            'preset' => $presetKey,
            'role' => self::choice((string) ($source['role'] ?? $base['role']), array_keys($roles), (string) $base['role']),
            'device_model' => self::choice((string) ($source['device_model'] ?? $base['device_model']), array_keys($devices), (string) $base['device_model']),
            'users' => max(1, min(500000, (int) ($source['users'] ?? $base['users']))),
            'baseline_plan' => self::choice((string) ($source['baseline_plan'] ?? $base['baseline_plan']), array_keys($planItems), (string) $base['baseline_plan']),
            'direct_customer_contact' => self::bool_value($source['direct_customer_contact'] ?? $base['direct_customer_contact']),
            'deskless_mobile' => self::bool_value($source['deskless_mobile'] ?? $base['deskless_mobile']),
            'shift_based' => self::bool_value($source['shift_based'] ?? $base['shift_based']),
            'teams_chat_shifts_tasks' => self::bool_value($source['teams_chat_shifts_tasks'] ?? $base['teams_chat_shifts_tasks']),
            'mail_required' => self::bool_value($source['mail_required'] ?? $base['mail_required']),
            'sharepoint_onedrive_viva' => self::bool_value($source['sharepoint_onedrive_viva'] ?? $base['sharepoint_onedrive_viva']),
            'power_platform_required' => self::bool_value($source['power_platform_required'] ?? $base['power_platform_required']),
            'desktop_apps_required' => self::bool_value($source['desktop_apps_required'] ?? $base['desktop_apps_required']),
            'high_security_compliance' => self::bool_value($source['high_security_compliance'] ?? $base['high_security_compliance']),
            'identity_device_controls_ready' => self::bool_value($source['identity_device_controls_ready'] ?? $base['identity_device_controls_ready']),
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function role_options(): array
    {
        $matrix = CMS_M365CALCULATOR_Catalog::frontline_user_type_matrix();
        $roles = is_array($matrix['roles'] ?? null) ? $matrix['roles'] : [];

        return self::labels_from_items($roles);
    }

    /**
     * @return array<string,string>
     */
    public static function device_options(): array
    {
        $matrix = CMS_M365CALCULATOR_Catalog::frontline_user_type_matrix();
        $devices = is_array($matrix['device_models'] ?? null) ? $matrix['device_models'] : [];

        return self::labels_from_items($devices);
    }

    /**
     * @return array<string,string>
     */
    public static function preset_options(): array
    {
        $presets = CMS_M365CALCULATOR_Catalog::frontline_industry_presets();
        $items = is_array($presets['presets'] ?? null) ? $presets['presets'] : [];

        return self::labels_from_items($items);
    }

    /**
     * @return array<string,string>
     */
    public static function baseline_plan_options(): array
    {
        $plans = CMS_M365CALCULATOR_Catalog::frontline_plan_matrix();
        $items = is_array($plans['plans'] ?? null) ? $plans['plans'] : [];

        return self::labels_from_items($items);
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function evaluate(array $input): array
    {
        $input = self::normalize_input($input);
        $userTypes = CMS_M365CALCULATOR_Catalog::frontline_user_type_matrix();
        $planMatrix = CMS_M365CALCULATOR_Catalog::frontline_plan_matrix();
        $presets = CMS_M365CALCULATOR_Catalog::frontline_industry_presets();

        $fit = self::score_frontline_worker_fit($input, $userTypes, $planMatrix);
        $recommendation = self::build_frontline_recommendation($input, $fit, $planMatrix);
        $savings = self::calculate_frontline_savings($input, $recommendation, $planMatrix);

        return [
            'input' => $input,
            'fit' => $fit,
            'recommendation' => $recommendation,
            'savings' => $savings,
            'warnings' => self::build_warnings($input, $fit, $recommendation),
            'guidance' => self::build_guidance($input, $userTypes),
            'plan_rows' => self::build_plan_rows($input, $planMatrix),
            'role_options' => self::role_options(),
            'device_options' => self::device_options(),
            'preset_options' => self::preset_options(),
            'baseline_plan_options' => self::baseline_plan_options(),
            'preset_cards' => self::build_preset_cards($presets),
            'sources' => array_values(array_unique(array_merge(
                array_map('strval', (array) ($userTypes['meta']['sources'] ?? [])),
                array_map('strval', (array) ($planMatrix['meta']['sources'] ?? []))
            ))),
            'meta' => [
                'source_checked' => (string) ($planMatrix['meta']['source_checked'] ?? $userTypes['meta']['source_checked'] ?? '2026-05-16'),
                'price_basis' => (string) ($planMatrix['meta']['price_basis'] ?? 'Preisannahmen vor Beschaffung prüfen.'),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $userTypes
     * @param array<string,mixed> $planMatrix
     * @return array<string,mixed>
     */
    public static function score_frontline_worker_fit(array $input, array $userTypes, array $planMatrix): array
    {
        $roles = is_array($userTypes['roles'] ?? null) ? $userTypes['roles'] : [];
        $devices = is_array($userTypes['device_models'] ?? null) ? $userTypes['device_models'] : [];
        $rules = is_array($planMatrix['feature_rules'] ?? null) ? $planMatrix['feature_rules'] : [];
        $role = is_array($roles[(string) $input['role']] ?? null) ? $roles[(string) $input['role']] : [];
        $device = is_array($devices[(string) $input['device_model']] ?? null) ? $devices[(string) $input['device_model']] : [];

        $frontlineScore = (int) ($role['frontline_base'] ?? 40) + (int) ($device['frontline_delta'] ?? 0);
        $frontlineScore += !empty($input['direct_customer_contact']) ? 8 : -2;
        $frontlineScore += !empty($input['deskless_mobile']) ? 18 : -16;
        $frontlineScore += !empty($input['shift_based']) ? 14 : -2;
        $frontlineScore += !empty($input['desktop_apps_required']) ? -34 : 8;

        $f1Score = 32 + (int) round($frontlineScore * 0.42) + (int) ($device['f1_fit_delta'] ?? 0);
        $f3Score = 38 + (int) round($frontlineScore * 0.36) + (int) ($device['f3_fit_delta'] ?? 0);
        $enterpriseScore = 20 + (!empty($input['desktop_apps_required']) ? 34 : 0) + (!empty($input['high_security_compliance']) ? 16 : 0);
        $riskScore = (int) ($device['risk_delta'] ?? 0);
        $appComplexity = 0;
        $ruleMessages = [];

        foreach ($rules as $key => $rule) {
            if (!is_array($rule) || empty($input[(string) $key])) {
                continue;
            }
            $f1Score += (int) ($rule['f1_delta'] ?? 0);
            $f3Score += (int) ($rule['f3_delta'] ?? 0);
            $enterpriseScore += (int) ($rule['enterprise_delta'] ?? 0);
            $ruleMessages[] = (string) ($rule['message'] ?? $rule['label'] ?? $key);
            $appComplexity += match ((string) $key) {
                'power_platform_required' => 24,
                'desktop_apps_required' => 36,
                'high_security_compliance' => 20,
                'sharepoint_onedrive_viva' => 10,
                'mail_required' => 8,
                default => 5,
            };
        }

        if (empty($input['identity_device_controls_ready'])) {
            $riskScore += 18;
            $f1Score -= 10;
            $f3Score -= 6;
        }
        if ((string) $input['device_model'] === 'kiosk_terminal') {
            $riskScore += 18;
        }
        if ((string) $input['role'] === 'information_worker') {
            $enterpriseScore += 30;
            $f1Score -= 28;
            $f3Score -= 16;
        }

        $frontlineScore = self::clamp_score($frontlineScore);
        $f1Score = self::clamp_score($f1Score);
        $f3Score = self::clamp_score($f3Score);
        $enterpriseScore = self::clamp_score($enterpriseScore);
        $riskScore = self::clamp_score($riskScore);
        $appComplexity = self::clamp_score($appComplexity);

        return [
            'frontline_score' => $frontlineScore,
            'f1_score' => $f1Score,
            'f3_score' => $f3Score,
            'enterprise_score' => $enterpriseScore,
            'risk_score' => $riskScore,
            'app_complexity' => $appComplexity,
            'user_type' => self::classify_user_type($frontlineScore, $userTypes),
            'rule_messages' => array_values(array_unique($ruleMessages)),
            'role' => $role,
            'device' => $device,
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $recommendation
     * @param array<string,mixed> $planMatrix
     * @return array<string,mixed>
     */
    public static function calculate_frontline_savings(array $input, array $recommendation, array $planMatrix): array
    {
        $plans = is_array($planMatrix['plans'] ?? null) ? $planMatrix['plans'] : [];
        $baselineKey = (string) $input['baseline_plan'];
        $targetKey = (string) ($recommendation['plan_key'] ?? $baselineKey);
        $baselinePrice = (float) ($plans[$baselineKey]['price_month'] ?? 0);
        $targetPrice = (float) ($plans[$targetKey]['price_month'] ?? $baselinePrice);
        $users = (int) $input['users'];
        $monthly = max(0.0, ($baselinePrice - $targetPrice) * $users);

        return [
            'baseline_plan' => $plans[$baselineKey]['label'] ?? $baselineKey,
            'target_plan' => $plans[$targetKey]['label'] ?? $targetKey,
            'baseline_price' => $baselinePrice,
            'target_price' => $targetPrice,
            'monthly' => $monthly,
            'annual' => $monthly * 12,
            'per_user' => max(0.0, $baselinePrice - $targetPrice),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $fit
     * @param array<string,mixed> $planMatrix
     * @return array<string,mixed>
     */
    public static function build_frontline_recommendation(array $input, array $fit, array $planMatrix): array
    {
        $thresholds = is_array($planMatrix['thresholds'] ?? null) ? $planMatrix['thresholds'] : [];
        $categories = is_array($planMatrix['recommendation_categories'] ?? null) ? $planMatrix['recommendation_categories'] : [];
        $category = 'mixed_model';
        $planKey = 'f3';
        $reason = 'Die Nutzergruppe zeigt Frontline-Merkmale, hat aber Anforderungen, die je Rolle gemischt werden sollten.';

        if ((int) $fit['risk_score'] >= (int) ($thresholds['manual_risk'] ?? 72)) {
            $category = 'manual_review';
            $planKey = (string) $input['baseline_plan'];
            $reason = 'Geräte-, Identitäts- oder Schutzanforderungen sind zu kritisch für eine einfache pauschale Umstellung.';
        } elseif (!empty($input['desktop_apps_required']) || (int) $fit['enterprise_score'] >= (int) ($thresholds['enterprise_min'] ?? 62) || (string) $fit['user_type'] === 'information_worker') {
            $category = 'enterprise_required';
            $planKey = in_array((string) $input['baseline_plan'], ['e5', 'e3', 'business_premium'], true) ? (string) $input['baseline_plan'] : 'e3';
            $reason = 'Desktop-, Dokument-, Security- oder klassische Office-Anforderungen sprechen gegen ein reines Frontline-Downgrade.';
        } elseif ((int) $fit['f1_score'] >= (int) ($thresholds['f1_min'] ?? 72) && empty($input['mail_required']) && empty($input['power_platform_required'])) {
            $category = 'f1_fit';
            $planKey = 'f1';
            $reason = 'Mobile, schichtbasierte Nutzung mit geringer App-Komplexität passt gut zu F1.';
        } elseif ((int) $fit['f3_score'] >= (int) ($thresholds['f3_min'] ?? 68)) {
            $category = 'f3_fit';
            $planKey = 'f3';
            $reason = 'Das Profil ist klar Frontline-orientiert, benötigt aber mehr Apps, Prozesse oder Mail als ein sehr leichtes F1-Szenario.';
        } elseif ((int) $fit['frontline_score'] >= (int) ($thresholds['mixed_min'] ?? 54)) {
            $category = 'mixed_model';
            $planKey = 'f3';
        } else {
            $category = 'manual_review';
            $planKey = (string) $input['baseline_plan'];
            $reason = 'Die Angaben ergeben kein klares Frontline-Profil. Eine Detailprüfung verhindert falsche Lizenzannahmen.';
        }

        $categoryMeta = is_array($categories[$category] ?? null) ? $categories[$category] : [];
        $bestScore = max((int) $fit['f1_score'], (int) $fit['f3_score'], (int) $fit['enterprise_score']);

        return [
            'category' => $category,
            'label' => (string) ($categoryMeta['label'] ?? $category),
            'tone' => (string) ($categoryMeta['tone'] ?? 'info'),
            'plan_key' => $planKey,
            'score' => $bestScore,
            'reason' => $reason,
            'user_type_label' => match ((string) $fit['user_type']) {
                'frontline' => 'echter Frontline User',
                'mixed' => 'Mischrolle',
                default => 'klassischer Information Worker',
            },
        ];
    }

    public static function render_frontline_check_page(): string
    {
        return CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-frontline-worker-check.php';
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $fit
     * @param array<string,mixed> $recommendation
     * @return array<int,string>
     */
    private static function build_warnings(array $input, array $fit, array $recommendation): array
    {
        $warnings = [];
        if (!empty($input['desktop_apps_required'])) {
            $warnings[] = 'Desktop-Apps und dokumentenzentrierte Arbeit sprechen gegen eine einfache Frontline-Umstellung.';
        }
        if (!empty($input['power_platform_required']) && (string) ($recommendation['plan_key'] ?? '') === 'f1') {
            $warnings[] = 'Power Apps oder Power Automate sprechen eher für F3 als F1.';
        }
        if ((string) $input['device_model'] === 'kiosk_terminal') {
            $warnings[] = 'Kiosk- oder Terminalmodelle sind nur für eng begrenzte Aufgaben geeignet und sollten nicht als Standard für Zusammenarbeit geplant werden.';
        }
        if (empty($input['identity_device_controls_ready'])) {
            $warnings[] = 'Identität, Geräteverwaltung und Zugriffskontrollen sollten vor einer Umstellung geklärt werden.';
        }
        if (!empty($input['high_security_compliance']) && (string) ($recommendation['category'] ?? '') !== 'enterprise_required') {
            $warnings[] = 'Erhöhter Schutz- oder Compliance-Bedarf kann zusätzliche Pläne oder Add-ons erforderlich machen.';
        }
        if ((int) ($fit['risk_score'] ?? 0) >= 70) {
            $warnings[] = 'Das Risikoprofil ist hoch; Lizenz, Geräte- und Schutzkonzept gemeinsam prüfen.';
        }

        return array_values(array_unique($warnings));
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $userTypes
     * @return array<int,string>
     */
    private static function build_guidance(array $input, array $userTypes): array
    {
        $devices = is_array($userTypes['device_models'] ?? null) ? $userTypes['device_models'] : [];
        $device = is_array($devices[(string) $input['device_model']] ?? null) ? $devices[(string) $input['device_model']] : [];
        $guidance = [];
        if (!empty($device['guidance'])) {
            $guidance[] = (string) $device['guidance'];
        }
        if (!empty($device['security_guidance'])) {
            $guidance[] = (string) $device['security_guidance'];
        }
        if (!empty($input['shift_based'])) {
            $guidance[] = 'Schichtbasierte Teams profitieren von Shifts, Aufgaben, gezielter Kommunikation und einfachen mobilen Abläufen.';
        }
        if (!empty($input['deskless_mobile'])) {
            $guidance[] = 'Mobile und deskless Nutzung ist ein starkes Signal für Frontline-Lizenzierung.';
        }

        return array_values(array_unique($guidance));
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $planMatrix
     * @return array<int,array<string,mixed>>
     */
    private static function build_plan_rows(array $input, array $planMatrix): array
    {
        $plans = is_array($planMatrix['plans'] ?? null) ? $planMatrix['plans'] : [];
        $rows = [];
        foreach ($plans as $key => $plan) {
            if (!is_array($plan)) {
                continue;
            }
            $price = (float) ($plan['price_month'] ?? 0);
            $rows[] = [
                'key' => (string) $key,
                'label' => (string) ($plan['label'] ?? $key),
                'short' => (string) ($plan['short'] ?? $key),
                'category' => (string) ($plan['category'] ?? ''),
                'price_month' => $price,
                'group_month' => $price * (int) $input['users'],
                'best_for' => (string) ($plan['best_for'] ?? ''),
                'limits' => array_values(array_map('strval', (array) ($plan['limits'] ?? []))),
            ];
        }

        return $rows;
    }

    /**
     * @param array<string,mixed> $presets
     * @return array<int,array<string,string>>
     */
    private static function build_preset_cards(array $presets): array
    {
        $items = is_array($presets['presets'] ?? null) ? $presets['presets'] : [];
        $cards = [];
        foreach ($items as $key => $item) {
            if (!is_array($item)) {
                continue;
            }
            $cards[] = [
                'key' => (string) $key,
                'label' => (string) ($item['label'] ?? $key),
                'summary' => (string) ($item['summary'] ?? ''),
            ];
        }

        return $cards;
    }

    /**
     * @param array<string,mixed> $matrix
     */
    private static function classify_user_type(int $frontlineScore, array $matrix): string
    {
        $classification = is_array($matrix['classification'] ?? null) ? $matrix['classification'] : [];
        if ($frontlineScore >= (int) ($classification['frontline_min'] ?? 70)) {
            return 'frontline';
        }
        if ($frontlineScore >= (int) ($classification['mixed_min'] ?? 45)) {
            return 'mixed';
        }

        return 'information_worker';
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
}
