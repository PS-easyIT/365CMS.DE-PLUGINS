<?php
/**
 * CMS M365 Tools – M365 Backup-Kosten-Rechner.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Backup_Cost_Calculator
{
    /**
     * @return array<string,mixed>
     */
    public static function default_input(): array
    {
        $rules = CMS_M365CALCULATOR_Catalog::backup_comparison_rules();
        $defaults = is_array($rules['defaults'] ?? null) ? $rules['defaults'] : [];

        return [
            'users' => (int) ($defaults['users'] ?? 100),
            'exchange_gb' => (float) ($defaults['exchange_gb'] ?? 900),
            'onedrive_gb' => (float) ($defaults['onedrive_gb'] ?? 1200),
            'sharepoint_gb' => (float) ($defaults['sharepoint_gb'] ?? 1800),
            'teams_gb' => (float) ($defaults['teams_gb'] ?? 250),
            'deleted_versioned_gb' => (float) ($defaults['deleted_versioned_gb'] ?? 300),
            'protection_percent' => (int) ($defaults['protection_percent'] ?? 80),
            'growth_percent' => (float) ($defaults['growth_percent'] ?? 10),
            'retention_months' => (int) ($defaults['retention_months'] ?? 12),
            'restore_depth' => (string) ($defaults['restore_depth'] ?? 'advanced'),
            'trust_priority' => (string) ($defaults['trust_priority'] ?? 'high'),
            'operational_preference' => (string) ($defaults['operational_preference'] ?? 'first_party'),
            'workloads' => self::string_list($defaults['workloads'] ?? ['exchange', 'onedrive', 'sharepoint']),
            'providers' => self::string_list($defaults['providers'] ?? ['microsoft-365-backup']),
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_input(array $source): array
    {
        $input = self::default_input();
        $input['users'] = max(1, min(500000, (int) ($source['users'] ?? $input['users'])));
        $input['exchange_gb'] = self::bounded_float($source['exchange_gb'] ?? $input['exchange_gb'], 0, 5000000);
        $input['onedrive_gb'] = self::bounded_float($source['onedrive_gb'] ?? $input['onedrive_gb'], 0, 5000000);
        $input['sharepoint_gb'] = self::bounded_float($source['sharepoint_gb'] ?? $input['sharepoint_gb'], 0, 5000000);
        $input['teams_gb'] = self::bounded_float($source['teams_gb'] ?? $input['teams_gb'], 0, 5000000);
        $input['deleted_versioned_gb'] = self::bounded_float($source['deleted_versioned_gb'] ?? $input['deleted_versioned_gb'], 0, 5000000);
        $input['protection_percent'] = max(1, min(100, (int) ($source['protection_percent'] ?? $input['protection_percent'])));
        $input['growth_percent'] = self::bounded_float($source['growth_percent'] ?? $input['growth_percent'], 0, 500);
        $input['retention_months'] = max(1, min(240, (int) ($source['retention_months'] ?? $input['retention_months'])));
        $input['restore_depth'] = self::enum((string) ($source['restore_depth'] ?? $input['restore_depth']), ['basic', 'standard', 'advanced'], 'advanced');
        $input['trust_priority'] = self::enum((string) ($source['trust_priority'] ?? $input['trust_priority']), ['normal', 'high'], 'high');
        $input['operational_preference'] = self::enum((string) ($source['operational_preference'] ?? $input['operational_preference']), ['first_party', 'partner', 'neutral'], 'first_party');

        $workloads = self::string_list($source['workloads'] ?? []);
        $allowedWorkloads = array_keys(self::workload_options());
        $workloads = array_values(array_intersect($workloads, $allowedWorkloads));
        if ($workloads !== []) {
            $input['workloads'] = $workloads;
        }

        $providers = self::string_list($source['providers'] ?? []);
        $allowedProviders = array_keys(self::provider_options());
        $providers = array_values(array_intersect($providers, $allowedProviders));
        if ($providers !== []) {
            $input['providers'] = $providers;
        }

        return $input;
    }

    /**
     * @return array<string,string>
     */
    public static function workload_options(): array
    {
        $rules = CMS_M365CALCULATOR_Catalog::backup_comparison_rules();
        $labels = is_array($rules['workload_labels'] ?? null) ? $rules['workload_labels'] : [];

        return [
            'exchange' => (string) ($labels['exchange'] ?? 'Exchange'),
            'onedrive' => (string) ($labels['onedrive'] ?? 'OneDrive'),
            'sharepoint' => (string) ($labels['sharepoint'] ?? 'SharePoint'),
            'teams' => (string) ($labels['teams'] ?? 'Teams-Dateien'),
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function provider_options(): array
    {
        $providers = self::providers_index();
        $options = [];
        foreach ($providers as $slug => $provider) {
            $options[$slug] = (string) ($provider['name'] ?? $slug);
        }

        return $options;
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function evaluate(array $input): array
    {
        $input = self::normalize_input($input);
        $baseline = CMS_M365CALCULATOR_Catalog::microsoft_backup_baseline();
        $rules = CMS_M365CALCULATOR_Catalog::backup_comparison_rules();
        $providerCatalog = CMS_M365CALCULATOR_Catalog::backup_providers();
        $baseline['source_checked'] = (string) ($baseline['meta']['source_checked'] ?? $rules['meta']['source_checked'] ?? '2026-05-17');
        $providers = self::providers_index($providerCatalog);
        $selectedProviderKeys = self::string_list($input['providers'] ?? []);
        $selectedProviders = [];
        foreach ($selectedProviderKeys as $key) {
            if (is_array($providers[$key] ?? null)) {
                $selectedProviders[$key] = $providers[$key];
            }
        }
        if ($selectedProviders === [] && is_array($providers['microsoft-365-backup'] ?? null)) {
            $selectedProviders['microsoft-365-backup'] = $providers['microsoft-365-backup'];
        }

        $storage = self::calculate_protected_storage($input);
        $weights = is_array($rules['weights'] ?? null) ? $rules['weights'] : [];
        $providerResults = [];
        foreach ($selectedProviders as $provider) {
            $providerResults[] = self::normalize_backup_provider_pricing($provider, $input, $storage, $weights);
        }

        $providerResults = self::rank_backup_provider_results($providerResults);
        $microsoft = self::find_result($providerResults, 'microsoft-365-backup');
        $cheapest = self::cheapest_result($providerResults);
        $strongest = $providerResults[0] ?? [];
        $closest = self::closest_to_microsoft($providerResults);
        $recommendation = self::compare_backup_scenarios($input, $providerResults, $rules, $microsoft, $cheapest);

        return [
            'input' => $input,
            'storage' => $storage,
            'microsoft' => $microsoft,
            'providers' => $providerResults,
            'kpis' => [
                'cheapest' => $cheapest,
                'strongest' => $strongest,
                'closest_to_microsoft' => $closest,
            ],
            'recommendation' => $recommendation,
            'workload_options' => self::workload_options(),
            'provider_options' => self::provider_options(),
            'restore_depth_options' => is_array($rules['restore_depth_options'] ?? null) ? $rules['restore_depth_options'] : [],
            'trust_priority_options' => is_array($rules['trust_priority_options'] ?? null) ? $rules['trust_priority_options'] : [],
            'operational_preference_options' => is_array($rules['operational_preference_options'] ?? null) ? $rules['operational_preference_options'] : [],
            'faq' => self::faq($rules),
            'baseline' => $baseline,
            'meta' => [
                'source_checked' => (string) ($baseline['meta']['source_checked'] ?? $rules['meta']['source_checked'] ?? '2026-05-17'),
                'price_basis' => (string) ($baseline['meta']['price_basis'] ?? ''),
                'provider_policy' => (string) ($providerCatalog['meta']['data_policy'] ?? ''),
            ],
            'sources' => is_array($baseline['meta']['sources'] ?? null) ? array_values(array_map('strval', $baseline['meta']['sources'])) : [],
        ];
    }

    public static function render_backup_cost_page(): string
    {
        return CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-backup-cost-calculator.php';
    }

    /**
     * @return array<string,mixed>
     */
    public static function load_microsoft_backup_baseline(): array
    {
        return CMS_M365CALCULATOR_Catalog::microsoft_backup_baseline();
    }

    /**
     * @return array<string,mixed>
     */
    public static function load_backup_provider_catalog(): array
    {
        return CMS_M365CALCULATOR_Catalog::backup_providers();
    }

    /**
     * @param array<string,mixed> $provider
     * @param array<string,mixed> $input
     * @param array<string,mixed> $storage
     * @param array<string,mixed> $weights
     * @return array<string,mixed>
     */
    public static function normalize_backup_provider_pricing(array $provider, array $input, array $storage, array $weights = []): array
    {
        $billingType = (string) ($provider['billing_type'] ?? 'manual');
        $unitPrice = (float) ($provider['unit_price'] ?? 0);
        $protectedGb = (float) ($storage['protected_gb'] ?? 0);
        $users = (int) ($input['users'] ?? 1);
        $monthly = 0.0;

        if ($billingType === 'per_gb') {
            $monthly = $protectedGb * $unitPrice;
        } elseif ($billingType === 'per_tb') {
            $monthly = ($protectedGb / 1024) * $unitPrice;
        } elseif ($billingType === 'per_user') {
            $monthly = $users * $unitPrice;
        } elseif ($billingType === 'per_tenant') {
            $monthly = $unitPrice;
        }

        $coverage = self::coverage_score($provider, self::string_list($input['workloads'] ?? []));
        $retention = self::retention_score($provider, (int) ($input['retention_months'] ?? 12));
        $restore = self::restore_score($provider, (string) ($input['restore_depth'] ?? 'advanced'));
        $trust = self::trust_score($provider, (string) ($input['trust_priority'] ?? 'high'), (string) ($input['operational_preference'] ?? 'first_party'));
        $costWeight = (float) ($weights['cost'] ?? 25);
        $score = ($coverage['ratio'] * (float) ($weights['coverage'] ?? 30))
            + ($retention['ratio'] * (float) ($weights['retention'] ?? 18))
            + ($restore['ratio'] * (float) ($weights['restore'] ?? 15))
            + ($trust['ratio'] * (float) ($weights['trust'] ?? 12))
            + ($monthly > 0 ? $costWeight : 0);

        return [
            'slug' => (string) ($provider['slug'] ?? ''),
            'name' => (string) ($provider['name'] ?? ''),
            'short' => (string) ($provider['short'] ?? $provider['name'] ?? ''),
            'data_source' => (string) ($provider['data_source'] ?? 'manual'),
            'billing_type' => $billingType,
            'unit_price' => $unitPrice,
            'currency' => (string) ($provider['currency'] ?? 'USD'),
            'monthly' => $monthly,
            'yearly' => $monthly * 12,
            'projected_monthly' => self::projected_monthly($provider, $input, $storage),
            'coverage' => $coverage,
            'retention' => $retention,
            'restore' => $restore,
            'trust' => $trust,
            'score' => round($score, 1),
            'strengths' => self::text_list($provider['strengths'] ?? []),
            'limits' => self::text_list($provider['limits'] ?? []),
            'source_note' => (string) ($provider['source_note'] ?? ''),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $providerResults
     * @return array<int,array<string,mixed>>
     */
    public static function rank_backup_provider_results(array $providerResults): array
    {
        $cheapestMonthly = null;
        foreach ($providerResults as $result) {
            $monthly = (float) ($result['monthly'] ?? 0);
            if ($monthly <= 0) {
                continue;
            }
            $cheapestMonthly = $cheapestMonthly === null ? $monthly : min($cheapestMonthly, $monthly);
        }

        foreach ($providerResults as $index => $result) {
            $monthly = (float) ($result['monthly'] ?? 0);
            if ($cheapestMonthly !== null && $monthly > 0) {
                $providerResults[$index]['score'] = round((float) ($result['score'] ?? 0) + min(25, ($cheapestMonthly / $monthly) * 25), 1);
            }
        }

        usort($providerResults, static function (array $left, array $right): int {
            $score = ((float) ($right['score'] ?? 0)) <=> ((float) ($left['score'] ?? 0));
            if ($score !== 0) {
                return $score;
            }

            return ((float) ($left['monthly'] ?? 0)) <=> ((float) ($right['monthly'] ?? 0));
        });

        return $providerResults;
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    private static function calculate_protected_storage(array $input): array
    {
        $workloads = self::string_list($input['workloads'] ?? []);
        $workloadGb = [
            'exchange' => (float) ($input['exchange_gb'] ?? 0),
            'onedrive' => (float) ($input['onedrive_gb'] ?? 0),
            'sharepoint' => (float) ($input['sharepoint_gb'] ?? 0),
            'teams' => (float) ($input['teams_gb'] ?? 0),
        ];
        $selectedGb = 0.0;
        foreach ($workloads as $workload) {
            $selectedGb += (float) ($workloadGb[$workload] ?? 0);
        }

        $rawGb = $selectedGb + (float) ($input['deleted_versioned_gb'] ?? 0);
        $protectedGb = $rawGb * ((float) ($input['protection_percent'] ?? 100) / 100);
        $projectedGb = $protectedGb * (1 + ((float) ($input['growth_percent'] ?? 0) / 100));

        return [
            'selected_workload_gb' => $selectedGb,
            'deleted_versioned_gb' => (float) ($input['deleted_versioned_gb'] ?? 0),
            'raw_gb' => $rawGb,
            'protected_gb' => $protectedGb,
            'projected_gb' => $projectedGb,
            'protection_percent' => (int) ($input['protection_percent'] ?? 100),
            'growth_percent' => (float) ($input['growth_percent'] ?? 0),
            'workload_gb' => $workloadGb,
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<int,array<string,mixed>> $results
     * @param array<string,mixed> $rules
     * @param array<string,mixed> $microsoft
     * @param array<string,mixed> $cheapest
     * @return array<string,string>
     */
    public static function compare_backup_scenarios(array $input, array $results, array $rules, array $microsoft, array $cheapest): array
    {
        $recommendations = is_array($rules['recommendations'] ?? null) ? $rules['recommendations'] : [];
        $msCoverage = (float) ($microsoft['coverage']['ratio'] ?? 0);
        $msRetention = (float) ($microsoft['retention']['ratio'] ?? 0);
        $msMonthly = (float) ($microsoft['monthly'] ?? 0);
        $cheapestMonthly = (float) ($cheapest['monthly'] ?? 0);
        $best = $results[0] ?? [];
        $bestCoverage = (float) ($best['coverage']['ratio'] ?? 0);
        $selectedTeams = in_array('teams', self::string_list($input['workloads'] ?? []), true);
        $retentionGap = (int) ($input['retention_months'] ?? 12) > 12;

        $key = 'partner_check';
        if ($results === [] || $bestCoverage < 0.6) {
            $key = 'incomplete';
        } elseif (($selectedTeams || $retentionGap) && $bestCoverage >= 0.95) {
            $key = ((string) ($input['trust_priority'] ?? 'high') === 'high') ? 'hybrid' : 'partner_better';
        } elseif ($msCoverage >= 0.95 && $msRetention >= 1.0 && ($cheapestMonthly <= 0 || $msMonthly <= $cheapestMonthly * 1.25 || (string) ($input['trust_priority'] ?? '') === 'high')) {
            $key = 'microsoft_sufficient';
        } elseif ((string) ($cheapest['slug'] ?? '') !== 'microsoft-365-backup' && $cheapestMonthly > 0 && $msMonthly > 0 && $cheapestMonthly <= $msMonthly * 0.85) {
            $key = 'partner_better';
        }

        $entry = is_array($recommendations[$key] ?? null) ? $recommendations[$key] : [];

        return [
            'key' => $key,
            'label' => (string) ($entry['label'] ?? 'Backup-Szenario prüfen'),
            'text' => (string) ($entry['text'] ?? 'Die Vergleichswerte sollten fachlich geprüft werden.'),
        ];
    }

    /**
     * @param array<string,mixed>|null $catalog
     * @return array<string,array<string,mixed>>
     */
    private static function providers_index(?array $catalog = null): array
    {
        $catalog ??= CMS_M365CALCULATOR_Catalog::backup_providers();
        $providers = is_array($catalog['providers'] ?? null) ? $catalog['providers'] : [];
        $indexed = [];
        foreach ($providers as $provider) {
            if (!is_array($provider) || empty($provider['slug'])) {
                continue;
            }
            $indexed[(string) $provider['slug']] = $provider;
        }

        return $indexed;
    }

    /**
     * @param array<string,mixed> $provider
     * @param array<int,string> $workloads
     * @return array<string,mixed>
     */
    private static function coverage_score(array $provider, array $workloads): array
    {
        $matrix = is_array($provider['workloads'] ?? null) ? $provider['workloads'] : [];
        $covered = 0.0;
        $details = [];
        foreach ($workloads as $workload) {
            $status = (string) ($matrix[$workload] ?? 'none');
            $covered += $status === 'full' ? 1.0 : ($status === 'partial' ? 0.5 : 0.0);
            $details[$workload] = $status;
        }

        $total = max(1, count($workloads));
        $ratio = $covered / $total;

        return [
            'ratio' => min(1.0, $ratio),
            'label' => $ratio >= 0.95 ? 'vollständig' : ($ratio >= 0.6 ? 'teilweise' : 'lückenhaft'),
            'details' => $details,
        ];
    }

    /**
     * @param array<string,mixed> $provider
     * @return array<string,mixed>
     */
    private static function retention_score(array $provider, int $requiredMonths): array
    {
        $months = max(0, (int) ($provider['retention_months'] ?? 0));
        $ratio = $requiredMonths <= 0 ? 1.0 : min(1.0, $months / $requiredMonths);

        return [
            'ratio' => $ratio,
            'months' => $months,
            'label' => $months >= $requiredMonths ? $months . ' Monate' : $months . ' Monate reichen nicht vollständig',
        ];
    }

    /**
     * @param array<string,mixed> $provider
     * @return array<string,mixed>
     */
    private static function restore_score(array $provider, string $required): array
    {
        $levels = ['basic' => 1, 'standard' => 2, 'advanced' => 3];
        $providerLevel = $levels[(string) ($provider['restore_level'] ?? 'basic')] ?? 1;
        $requiredLevel = $levels[$required] ?? 3;
        $ratio = min(1.0, $providerLevel / max(1, $requiredLevel));

        return [
            'ratio' => $ratio,
            'label' => array_search($providerLevel, $levels, true) ?: 'basic',
        ];
    }

    /**
     * @param array<string,mixed> $provider
     * @return array<string,mixed>
     */
    private static function trust_score(array $provider, string $priority, string $operation): array
    {
        $trust = (string) ($provider['trust_boundary'] ?? 'partner_controlled');
        $ops = (string) ($provider['operational_model'] ?? 'partner_console');
        $score = 0.65;

        if ($trust === 'microsoft') {
            $score = $priority === 'high' ? 1.0 : 0.9;
        } elseif ($priority === 'normal') {
            $score = 0.8;
        }

        if ($operation === 'first_party' && $ops === 'microsoft_admin_center') {
            $score += 0.1;
        } elseif ($operation === 'partner' && $ops === 'partner_console') {
            $score += 0.1;
        }

        return [
            'ratio' => min(1.0, $score),
            'label' => $trust === 'microsoft' ? 'Microsoft-nah' : 'Partnergesteuert',
        ];
    }

    /**
     * @param array<string,mixed> $provider
     * @param array<string,mixed> $input
     * @param array<string,mixed> $storage
     */
    private static function projected_monthly(array $provider, array $input, array $storage): float
    {
        $billingType = (string) ($provider['billing_type'] ?? 'manual');
        $unitPrice = (float) ($provider['unit_price'] ?? 0);
        if ($billingType === 'per_gb') {
            return (float) ($storage['projected_gb'] ?? 0) * $unitPrice;
        }

        if ($billingType === 'per_tb') {
            return ((float) ($storage['projected_gb'] ?? 0) / 1024) * $unitPrice;
        }

        if ($billingType === 'per_user') {
            return (int) ($input['users'] ?? 1) * $unitPrice;
        }

        return $unitPrice;
    }

    /**
     * @param array<int,array<string,mixed>> $results
     * @return array<string,mixed>
     */
    private static function cheapest_result(array $results): array
    {
        $cheapest = [];
        foreach ($results as $result) {
            if ((float) ($result['monthly'] ?? 0) <= 0) {
                continue;
            }
            if ($cheapest === [] || (float) $result['monthly'] < (float) ($cheapest['monthly'] ?? 0)) {
                $cheapest = $result;
            }
        }

        return $cheapest;
    }

    /**
     * @param array<int,array<string,mixed>> $results
     * @return array<string,mixed>
     */
    private static function closest_to_microsoft(array $results): array
    {
        foreach ($results as $result) {
            if ((string) ($result['slug'] ?? '') === 'microsoft-365-backup') {
                return $result;
            }
        }

        return $results[0] ?? [];
    }

    /**
     * @param array<int,array<string,mixed>> $results
     * @return array<string,mixed>
     */
    private static function find_result(array $results, string $slug): array
    {
        foreach ($results as $result) {
            if ((string) ($result['slug'] ?? '') === $slug) {
                return $result;
            }
        }

        return [];
    }

    /**
     * @param array<string,mixed> $rules
     * @return array<int,array<string,string>>
     */
    private static function faq(array $rules): array
    {
        $faq = [];
        foreach ((array) ($rules['faq'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $faq[] = [
                'question' => trim(strip_tags((string) ($item['question'] ?? ''))),
                'answer' => trim(strip_tags((string) ($item['answer'] ?? ''))),
            ];
        }

        return $faq;
    }

    private static function bounded_float(mixed $value, float $min, float $max): float
    {
        $number = is_numeric($value) ? (float) $value : $min;
        return max($min, min($max, $number));
    }

    private static function enum(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    /**
     * @return array<int,string>
     */
    private static function string_list(mixed $value): array
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        return array_values(array_unique(array_filter(array_map(static function (mixed $item): string {
            return trim((string) preg_replace('/[^a-z0-9_.-]+/i', '-', strtolower((string) $item)), '-');
        }, $value))));
    }

    /**
     * @return array<int,string>
     */
    private static function text_list(mixed $value): array
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        return array_values(array_filter(array_map(static function (mixed $item): string {
            return trim(strip_tags((string) $item));
        }, $value)));
    }
}