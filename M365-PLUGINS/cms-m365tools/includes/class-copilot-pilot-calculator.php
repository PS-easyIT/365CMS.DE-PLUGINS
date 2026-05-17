<?php
/**
 * CMS M365 Tools – Copilot Pilot-Phase-Rechner.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Copilot_Pilot_Calculator
{
    /**
     * @return array<string,mixed>
     */
    public static function default_input(): array
    {
        return [
            'org_size' => 250,
            'knowledge_worker_percent' => 60,
            'departments' => 3,
            'budget_monthly' => 1500.0,
            'pilot_goal' => 'use_cases',
            'license_price_month' => 28.10,
            'oversharing_risk' => 'medium',
            'eligible_license' => true,
            'apps_network_ready' => true,
            'data_governance_ready' => true,
            'champions_ready' => true,
            'feedback_ready' => true,
            'success_metrics_ready' => true,
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_input(array $source): array
    {
        $defaults = self::default_input();
        $checklist = CMS_M365CALCULATOR_Catalog::copilot_readiness_checklist();
        $goalWeights = is_array($checklist['goal_weights'] ?? null) ? $checklist['goal_weights'] : [];
        $oversharingLevels = is_array($checklist['oversharing_levels'] ?? null) ? $checklist['oversharing_levels'] : [];

        return [
            'org_size' => max(1, min(500000, (int) ($source['org_size'] ?? $defaults['org_size']))),
            'knowledge_worker_percent' => max(1, min(100, (int) ($source['knowledge_worker_percent'] ?? $defaults['knowledge_worker_percent']))),
            'departments' => max(1, min(50, (int) ($source['departments'] ?? $defaults['departments']))),
            'budget_monthly' => max(0.0, min(10000000.0, (float) ($source['budget_monthly'] ?? $defaults['budget_monthly']))),
            'pilot_goal' => self::choice((string) ($source['pilot_goal'] ?? $defaults['pilot_goal']), array_keys($goalWeights), (string) $defaults['pilot_goal']),
            'license_price_month' => max(0.0, min(10000.0, (float) ($source['license_price_month'] ?? $defaults['license_price_month']))),
            'oversharing_risk' => self::choice((string) ($source['oversharing_risk'] ?? $defaults['oversharing_risk']), array_keys($oversharingLevels), (string) $defaults['oversharing_risk']),
            'eligible_license' => self::bool_value($source['eligible_license'] ?? $defaults['eligible_license']),
            'apps_network_ready' => self::bool_value($source['apps_network_ready'] ?? $defaults['apps_network_ready']),
            'data_governance_ready' => self::bool_value($source['data_governance_ready'] ?? $defaults['data_governance_ready']),
            'champions_ready' => self::bool_value($source['champions_ready'] ?? $defaults['champions_ready']),
            'feedback_ready' => self::bool_value($source['feedback_ready'] ?? $defaults['feedback_ready']),
            'success_metrics_ready' => self::bool_value($source['success_metrics_ready'] ?? $defaults['success_metrics_ready']),
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function goal_options(): array
    {
        return [
            'productivity' => 'Produktivität messen',
            'use_cases' => 'Use Cases identifizieren',
            'governance' => 'Governance prüfen',
            'management_buyin' => 'Management-Buy-in erzeugen',
            'roi_validation' => 'ROI validieren',
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function oversharing_options(): array
    {
        return [
            'low' => 'niedrig',
            'medium' => 'mittel',
            'high' => 'hoch',
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function evaluate(array $input): array
    {
        $input = self::normalize_input($input);
        $sizes = CMS_M365CALCULATOR_Catalog::copilot_pilot_sizes();
        $rollouts = CMS_M365CALCULATOR_Catalog::copilot_rollout_templates();
        $checklist = CMS_M365CALCULATOR_Catalog::copilot_readiness_checklist();
        $readiness = self::score_copilot_readiness($input, $checklist);
        $stageRows = self::calculate_copilot_pilot_sizes($input, $sizes);
        $recommendation = self::score_copilot_pilot_recommendation($input, $readiness, $stageRows, $sizes, $checklist);
        $timeline = self::build_copilot_rollout_timeline((int) ($recommendation['duration_weeks'] ?? 6), $rollouts);

        return [
            'input' => $input,
            'readiness' => $readiness,
            'recommendation' => $recommendation,
            'stages' => $stageRows,
            'timeline' => $timeline,
            'goal_options' => self::goal_options(),
            'oversharing_options' => self::oversharing_options(),
            'next_steps' => self::next_steps($recommendation, $readiness),
            'preflight_checklist' => self::preflight_checklist($checklist),
            'faq' => self::faq($checklist),
            'success_metrics' => array_values(array_map('strval', (array) ($rollouts['success_metrics'] ?? []))),
            'sources' => array_values(array_unique(array_merge(
                array_map('strval', (array) ($sizes['meta']['sources'] ?? [])),
                array_map('strval', (array) ($checklist['meta']['sources'] ?? []))
            ))),
            'meta' => [
                'source_checked' => (string) ($checklist['meta']['source_checked'] ?? $sizes['meta']['source_checked'] ?? '2026-05-17'),
                'price_basis' => 'Preisannahmen sind Richtwerte und müssen vor Beschaffung im jeweiligen Vertrag geprüft werden.',
            ],
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $sizes
     * @return array<int,array<string,mixed>>
     */
    public static function calculate_copilot_pilot_sizes(array $input, array $sizes): array
    {
        $licensePrice = max(0.0, (float) ($input['license_price_month'] ?? 0));
        $budget = (float) ($input['budget_monthly'] ?? 0);
        $stages = [];

        foreach ((array) ($sizes['stages'] ?? []) as $stage) {
            if (!is_array($stage)) {
                continue;
            }
            $users = (int) ($stage['users'] ?? 0);
            $durationWeeks = (int) ($stage['duration_weeks'] ?? 4);
            $months = max(1.0, $durationWeeks / 4.0);
            $monthlyCost = $users * $licensePrice;
            $pilotCost = $monthlyCost * $months;
            $champions = max(1, (int) ceil($users * (float) ($stage['champion_ratio'] ?? 0.1)));
            $stages[] = $stage + [
                'monthly_cost' => $monthlyCost,
                'pilot_cost' => $pilotCost,
                'champions' => $champions,
                'budget_fit' => $budget <= 0.0 || $monthlyCost <= $budget,
                'budget_gap' => max(0.0, $monthlyCost - $budget),
            ];
        }

        return $stages;
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $checklist
     * @return array<string,mixed>
     */
    public static function score_copilot_readiness(array $input, array $checklist): array
    {
        $checks = is_array($checklist['checks'] ?? null) ? $checklist['checks'] : [];
        $oversharingLevels = is_array($checklist['oversharing_levels'] ?? null) ? $checklist['oversharing_levels'] : [];
        $score = 0;
        $max = 0;
        $missing = [];
        $blockers = [];

        foreach ($checks as $key => $check) {
            if (!is_array($check)) {
                continue;
            }
            $weight = (int) ($check['weight'] ?? 0);
            $max += $weight;
            if (!empty($input[(string) $key])) {
                $score += $weight;
                continue;
            }
            $item = [
                'key' => (string) $key,
                'label' => (string) ($check['label'] ?? $key),
                'message' => (string) ($check['missing_message'] ?? 'Vor Pilotstart prüfen.'),
            ];
            $missing[] = $item;
            if (!empty($check['blocker'])) {
                $blockers[] = $item;
            }
        }

        $oversharing = is_array($oversharingLevels[(string) $input['oversharing_risk']] ?? null)
            ? $oversharingLevels[(string) $input['oversharing_risk']]
            : [];
        $penalty = (int) ($oversharing['penalty'] ?? 0);
        $percent = $max > 0 ? (int) round(($score / $max) * 100) : 0;
        $percent = max(0, min(100, $percent - $penalty));

        return [
            'score' => $percent,
            'raw_score' => $score,
            'max_score' => $max,
            'missing' => $missing,
            'blockers' => $blockers,
            'oversharing' => [
                'level' => (string) $input['oversharing_risk'],
                'label' => (string) ($oversharing['label'] ?? $input['oversharing_risk']),
                'message' => (string) ($oversharing['message'] ?? 'Datenzugriffe prüfen.'),
                'penalty' => $penalty,
            ],
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $readiness
     * @param array<int,array<string,mixed>> $stages
     * @param array<string,mixed> $sizes
     * @param array<string,mixed> $checklist
     * @return array<string,mixed>
     */
    public static function score_copilot_pilot_recommendation(array $input, array $readiness, array $stages, array $sizes, array $checklist): array
    {
        $thresholds = is_array($sizes['category_thresholds'] ?? null) ? $sizes['category_thresholds'] : [];
        $categories = is_array($checklist['categories'] ?? null) ? $checklist['categories'] : [];
        $knowledgeWorkers = max(1, (int) ceil((int) $input['org_size'] * ((int) $input['knowledge_worker_percent'] / 100)));
        $goalWeights = is_array($checklist['goal_weights'] ?? null) ? $checklist['goal_weights'] : [];
        $goalWeight = (float) ($goalWeights[(string) $input['pilot_goal']] ?? 1.0);
        $baseUsers = self::base_recommended_users((int) $input['org_size'], $knowledgeWorkers, (int) $input['departments'], $goalWeight);
        $readinessScore = (int) ($readiness['score'] ?? 0);
        $category = 'department_pilot';
        $reason = 'Ein repräsentativer Abteilungs-Pilot liefert genug Feedback, ohne zu früh zu stark zu skalieren.';

        if (!empty($readiness['blockers']) && $readinessScore < (int) ($thresholds['not_ready'] ?? 45)) {
            $category = 'not_ready';
            $baseUsers = min($baseUsers, 5);
            $reason = 'Zentrale Voraussetzungen fehlen noch. Erst Readiness-Blocker schließen, dann Pilot starten.';
        } elseif ($readinessScore < (int) ($thresholds['governance_first'] ?? 60) || (string) $input['oversharing_risk'] === 'high' || empty($input['data_governance_ready'])) {
            $category = 'governance_first';
            $baseUsers = min($baseUsers, 20);
            $reason = 'Datenhygiene, Oversharing oder Guardrails sollten vor einem größeren Teilnehmerkreis stabilisiert werden.';
        } elseif ($baseUsers <= (int) ($thresholds['micro_max_users'] ?? 10)) {
            $category = 'micro_pilot';
            $reason = 'Ein kompakter Pilot reicht für ein kleines Team mit eng gefasstem Zielbild.';
        } elseif ($baseUsers <= (int) ($thresholds['department_max_users'] ?? 30)) {
            $category = 'department_pilot';
        } else {
            $category = 'cross_functional';
            $reason = 'Mehrere Rollen oder Bereiche erfordern einen funktionsübergreifenden Pilot mit belastbarerem Feedback.';
        }

        $stage = self::nearest_stage($baseUsers, $stages, (float) $input['budget_monthly'], $category);
        if ((int) ($stage['users'] ?? 0) >= 100 && $readinessScore >= 75 && (int) $input['org_size'] >= 1500) {
            $category = 'cross_functional';
            $reason = 'Für große Organisationen ist eine skalierte Pilot-Welle sinnvoll, sofern Governance und Enablement tragfähig sind.';
        }

        $categoryMeta = is_array($categories[$category] ?? null) ? $categories[$category] : [];

        return [
            'category' => $category,
            'label' => (string) ($categoryMeta['label'] ?? $category),
            'tone' => (string) ($categoryMeta['tone'] ?? 'info'),
            'reason' => $reason,
            'users' => (int) ($stage['users'] ?? $baseUsers),
            'stage_key' => (string) ($stage['key'] ?? ''),
            'stage_label' => (string) ($stage['label'] ?? 'Pilot'),
            'duration_weeks' => (int) ($stage['duration_weeks'] ?? 6),
            'champions' => (int) ($stage['champions'] ?? 1),
            'monthly_cost' => (float) ($stage['monthly_cost'] ?? 0),
            'pilot_cost' => (float) ($stage['pilot_cost'] ?? 0),
            'budget_fit' => !empty($stage['budget_fit']),
            'budget_gap' => (float) ($stage['budget_gap'] ?? 0),
            'knowledge_workers' => $knowledgeWorkers,
            'representativeness' => (string) ($stage['representativeness'] ?? 'mittel'),
            'learning_value' => (int) ($stage['learning_value'] ?? 0),
            'governance_load' => (int) ($stage['governance_load'] ?? 0),
            'stage_notes' => array_values(array_map('strval', (array) ($stage['notes'] ?? []))),
        ];
    }

    /**
     * @param array<string,mixed> $rollouts
     * @return array<string,mixed>
     */
    public static function build_copilot_rollout_timeline(int $durationWeeks, array $rollouts): array
    {
        $templates = is_array($rollouts['templates'] ?? null) ? $rollouts['templates'] : [];
        $keys = array_map('intval', array_keys($templates));
        sort($keys);
        $selected = $keys[0] ?? 4;
        foreach ($keys as $weeks) {
            if ($durationWeeks <= $weeks) {
                $selected = $weeks;
                break;
            }
            $selected = $weeks;
        }

        return is_array($templates[(string) $selected] ?? null) ? $templates[(string) $selected] : [];
    }

    public static function render_copilot_pilot_page(): string
    {
        return CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-copilot-pilot-calculator.php';
    }

    /**
     * @param array<int,array<string,mixed>> $stages
     * @return array<string,mixed>
     */
    private static function nearest_stage(int $targetUsers, array $stages, float $budget, string $category): array
    {
        if ($category === 'not_ready') {
            return is_array($stages[0] ?? null) ? $stages[0] : [];
        }

        $fallback = is_array($stages[0] ?? null) ? $stages[0] : [];
        foreach ($stages as $stage) {
            if (!is_array($stage)) {
                continue;
            }
            if ((int) ($stage['users'] ?? 0) < $targetUsers) {
                $fallback = $stage;
                continue;
            }
            if ($budget > 0 && empty($stage['budget_fit'])) {
                return $fallback;
            }
            return $stage;
        }

        return $fallback;
    }

    private static function base_recommended_users(int $orgSize, int $knowledgeWorkers, int $departments, float $goalWeight): int
    {
        $base = 5;
        if ($orgSize > 80 || $knowledgeWorkers > 40) {
            $base = 20;
        }
        if ($orgSize > 300 || $departments > 1 || $knowledgeWorkers > 120) {
            $base = 50;
        }
        if ($orgSize > 1500 || $departments >= 5 || $knowledgeWorkers > 800) {
            $base = 100;
        }
        if ($orgSize > 8000 || $knowledgeWorkers > 4000) {
            $base = 250;
        }

        return max(5, (int) round($base * $goalWeight));
    }

    /**
     * @param array<string,mixed> $recommendation
     * @param array<string,mixed> $readiness
     * @return array<int,string>
     */
    private static function next_steps(array $recommendation, array $readiness): array
    {
        $steps = [];
        if ((string) ($recommendation['category'] ?? '') === 'not_ready') {
            $steps[] = 'Readiness-Blocker schließen und Pilot danach erneut dimensionieren.';
        } elseif ((string) ($recommendation['category'] ?? '') === 'governance_first') {
            $steps[] = 'Hoch priorisierte Sites, sensible Inhalte und Freigaben vor Pilotstart bereinigen.';
        } else {
            $steps[] = 'Pilotgruppe mit Rollenmix, Champions und Fachbereichsowner bestätigen.';
        }
        $steps[] = 'Success-Kriterien, Feedbackkanal und wöchentliche Review-Termine festlegen.';
        $steps[] = 'Nutzungs-, Feedback- und Governance-Signale in einen Go/No-Go-Termin überführen.';

        foreach (array_slice((array) ($readiness['missing'] ?? []), 0, 2) as $missing) {
            if (is_array($missing)) {
                $steps[] = (string) ($missing['message'] ?? 'Offenen Readiness-Punkt prüfen.');
            }
        }

        return array_values(array_unique($steps));
    }

    /**
     * @param array<string,mixed> $checklist
     * @return array<int,array<string,string>>
     */
    private static function preflight_checklist(array $checklist): array
    {
        $items = [];
        foreach ((array) ($checklist['preflight_checklist'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = trim(strip_tags((string) ($item['title'] ?? '')));
            $text = trim(strip_tags((string) ($item['text'] ?? '')));
            if ($title === '' || $text === '') {
                continue;
            }

            $items[] = [
                'title' => $title,
                'text' => $text,
            ];
        }

        return $items;
    }

    /**
     * @param array<string,mixed> $checklist
     * @return array<int,array<string,string>>
     */
    private static function faq(array $checklist): array
    {
        $items = [];
        foreach ((array) ($checklist['faq'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $question = trim(strip_tags((string) ($item['question'] ?? '')));
            $answer = trim(strip_tags((string) ($item['answer'] ?? '')));
            if ($question === '' || $answer === '') {
                continue;
            }

            $items[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        return $items;
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
}
