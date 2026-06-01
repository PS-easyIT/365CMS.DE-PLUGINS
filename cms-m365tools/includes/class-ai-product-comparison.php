<?php
/**
 * CMS M365 Tools – AI Pack vs. Copilot Pro Vergleich.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_AI_Product_Comparison
{
    private const AI_MODULE_KEY = 'ai-pack-vs-copilot-pro';

    /**
     * @return array<string,mixed>
     */
    public static function default_input(): array
    {
        return [
            'role' => 'knowledge_worker',
            'data_scope' => 'work',
            'goal' => 'org_knowledge',
            'sensitivity' => 'internal',
            'dynamic_offer' => 'none',
            'users' => 25,
            'needs_custom_agents' => false,
            'needs_admin_governance' => true,
            'needs_in_app_experience' => true,
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_input(array $source): array
    {
        $defaults = self::default_input();
        $matrix = CMS_M365CALCULATOR_Catalog::ai_use_case_matrix();
        $offers = CMS_M365CALCULATOR_Catalog::ai_dynamic_offers();

        $roleOptions = array_keys(is_array($matrix['roles'] ?? null) ? $matrix['roles'] : []);
        $dataOptions = array_keys(is_array($matrix['data_scopes'] ?? null) ? $matrix['data_scopes'] : []);
        $goalOptions = array_keys(is_array($matrix['goals'] ?? null) ? $matrix['goals'] : []);
        $offerOptions = array_map(
            static fn(array $offer): string => (string) ($offer['slug'] ?? ''),
            array_values(array_filter(is_array($offers['offers'] ?? null) ? $offers['offers'] : [], 'is_array'))
        );

        $role = self::choice((string) ($source['role'] ?? $defaults['role']), $roleOptions, (string) $defaults['role']);
        $dataScope = self::choice((string) ($source['data_scope'] ?? $defaults['data_scope']), $dataOptions, (string) $defaults['data_scope']);
        $goal = self::choice((string) ($source['goal'] ?? $defaults['goal']), $goalOptions, (string) $defaults['goal']);
        $sensitivity = self::choice((string) ($source['sensitivity'] ?? $defaults['sensitivity']), ['public', 'internal', 'confidential'], (string) $defaults['sensitivity']);
        $dynamicOffer = self::choice((string) ($source['dynamic_offer'] ?? $defaults['dynamic_offer']), $offerOptions, (string) $defaults['dynamic_offer']);

        return [
            'role' => $role,
            'data_scope' => $dataScope,
            'goal' => $goal,
            'sensitivity' => $sensitivity,
            'dynamic_offer' => $dynamicOffer,
            'users' => max(1, min(500000, (int) ($source['users'] ?? $defaults['users']))),
            'needs_custom_agents' => self::bool_value($source['needs_custom_agents'] ?? $defaults['needs_custom_agents']),
            'needs_admin_governance' => self::bool_value($source['needs_admin_governance'] ?? $defaults['needs_admin_governance']),
            'needs_in_app_experience' => self::bool_value($source['needs_in_app_experience'] ?? $defaults['needs_in_app_experience']),
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function role_options(): array
    {
        $matrix = CMS_M365CALCULATOR_Catalog::ai_use_case_matrix();
        return is_array($matrix['roles'] ?? null) ? $matrix['roles'] : [];
    }

    /**
     * @return array<string,string>
     */
    public static function data_scope_options(): array
    {
        $matrix = CMS_M365CALCULATOR_Catalog::ai_use_case_matrix();
        return is_array($matrix['data_scopes'] ?? null) ? $matrix['data_scopes'] : [];
    }

    /**
     * @return array<string,string>
     */
    public static function goal_options(): array
    {
        $matrix = CMS_M365CALCULATOR_Catalog::ai_use_case_matrix();
        return is_array($matrix['goals'] ?? null) ? $matrix['goals'] : [];
    }

    /**
     * @return array<string,string>
     */
    public static function dynamic_offer_options(): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::ai_dynamic_offers();
        $options = [];
        foreach ((array) ($catalog['offers'] ?? []) as $offer) {
            if (!is_array($offer)) {
                continue;
            }
            $slug = (string) ($offer['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $options[$slug] = (string) ($offer['label'] ?? $slug);
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
        $productCatalog = CMS_M365CALCULATOR_Catalog::ai_product_catalog();
        $matrix = CMS_M365CALCULATOR_Catalog::ai_use_case_matrix();
        $dynamicCatalog = CMS_M365CALCULATOR_Catalog::ai_dynamic_offers();
        $products = array_values(array_filter((array) ($productCatalog['products'] ?? []), 'is_array'));
        $scoreRules = is_array($matrix['score_rules'] ?? null) ? $matrix['score_rules'] : [];
        $selectedOffer = self::selected_offer((string) $input['dynamic_offer'], $dynamicCatalog);
        $mappedOfferSlugs = array_values(array_map('strval', is_array($selectedOffer['maps_to'] ?? null) ? $selectedOffer['maps_to'] : []));
        $manualReview = !empty($selectedOffer['manual_validation_required']);
        $pricingMatrix = self::pricing_matrix($productCatalog);
        $scored = [];

        foreach ($products as $product) {
            $scored[] = self::score_product($product, $input, $scoreRules, $mappedOfferSlugs, $manualReview);
        }

        usort($scored, static fn(array $left, array $right): int => ((int) ($right['score'] ?? 0)) <=> ((int) ($left['score'] ?? 0)));

        $best = is_array($scored[0] ?? null) ? $scored[0] : [];
        $alternatives = array_slice($scored, 1, 4);
        $warnings = self::warnings($input, $best, $selectedOffer, $manualReview);
        $shortcuts = self::matching_shortcuts($input, $matrix);

        return [
            'input' => $input,
            'best' => $best,
            'alternatives' => $alternatives,
            'products' => $scored,
            'comparison_rows' => self::comparison_rows($scored),
            'selected_offer' => $selectedOffer,
            'manual_review' => $manualReview || !empty($best['manual_review']),
            'status' => self::status($best, $manualReview),
            'warnings' => $warnings,
            'shortcuts' => $shortcuts,
            'next_steps' => self::next_steps($input, $best, $manualReview),
            'pricing_matrix' => $pricingMatrix,
            'role_options' => self::role_options(),
            'data_scope_options' => self::data_scope_options(),
            'goal_options' => self::goal_options(),
            'dynamic_offer_options' => self::dynamic_offer_options(),
            'meta' => [
                'source_checked' => (string) ($productCatalog['meta']['source_checked'] ?? $matrix['meta']['source_checked'] ?? '2026-05-16'),
                'price_basis' => (string) ($productCatalog['meta']['price_basis'] ?? 'Preise und Angebotsumfang vor Bestellung prüfen.'),
                'pricing_last_verified' => (string) ($pricingMatrix['last_verified'] ?? ''),
            ],
            'sources' => array_values(array_unique(array_merge(
                array_map('strval', (array) ($productCatalog['meta']['sources'] ?? [])),
                array_map('strval', (array) ($dynamicCatalog['sources'] ?? []))
            ))),
        ];
    }

    public static function render_ai_product_comparison_page(): string
    {
        return CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-ai-product-comparison.php';
    }

    /**
     * @param array<string,mixed> $product
     * @param array<string,mixed> $input
     * @param array<string,mixed> $rules
     * @param array<int,string> $mappedOfferSlugs
     * @return array<string,mixed>
     */
    private static function score_product(array $product, array $input, array $rules, array $mappedOfferSlugs, bool $manualReview): array
    {
        $slug = (string) ($product['slug'] ?? '');
        $score = 20;
        $reasons = [];
        $penalties = [];
        $role = (string) $input['role'];
        $dataScope = (string) $input['data_scope'];
        $goal = (string) $input['goal'];
        $targetRoles = array_map('strval', (array) ($product['target_roles'] ?? []));
        $dataScopes = array_map('strval', (array) ($product['data_scopes'] ?? []));
        $goals = array_map('strval', (array) ($product['goals'] ?? []));

        if (in_array($role, $targetRoles, true)) {
            $score += (int) ($rules['role_match'] ?? 24);
            $reasons[] = 'passt zur gewählten Rolle';
        }

        if (in_array($dataScope, $dataScopes, true) || in_array('varies', $dataScopes, true)) {
            $score += (int) ($rules['data_scope_match'] ?? 30);
            $reasons[] = 'deckt die Datenquelle gut ab';
        }

        if (in_array($goal, $goals, true)) {
            $score += (int) ($rules['goal_match'] ?? 26);
            $reasons[] = 'passt zum Zielbild';
        }

        if ((string) $input['sensitivity'] !== 'public' && !in_array($slug, ['microsoft-copilot-consumer'], true)) {
            $score += (int) ($rules['sensitive_business_data_fit'] ?? 10);
        }

        if (!empty($input['needs_admin_governance']) && !in_array($slug, ['microsoft-copilot-consumer'], true)) {
            $score += (int) ($rules['admin_governance_fit'] ?? 6);
        }

        if (!empty($input['needs_custom_agents']) && in_array($slug, ['copilot-studio', 'copilot-chat', 'microsoft-365-copilot', 'dynamic-ai-pack'], true)) {
            $score += (int) ($rules['custom_agent_bonus'] ?? 16);
            $reasons[] = 'unterstützt Agent- oder Erweiterungsszenarien';
        }

        if (!empty($input['needs_in_app_experience']) && $slug === 'microsoft-365-copilot') {
            $score += 14;
            $reasons[] = 'liefert die stärkste In-App-Erfahrung in Microsoft 365';
        }

        if (in_array($slug, $mappedOfferSlugs, true)) {
            $score += (int) ($rules['dynamic_offer_selected'] ?? 8);
            $reasons[] = 'ist mit dem gewählten Angebotslabel verknüpft';
        }

        if (!empty($product['manual_review']) || $manualReview) {
            $score += (int) ($rules['manual_review_penalty'] ?? -4);
        }

        if ((string) $input['sensitivity'] !== 'public' && $slug === 'microsoft-copilot-consumer') {
            $score += (int) ($rules['consumer_sensitive_penalty'] ?? -45);
            $penalties[] = 'für sensible Unternehmensdaten nicht als Standardpfad geeignet';
        }

        if ($role === 'security' && !in_array($slug, ['security-copilot', 'microsoft-365-copilot', 'copilot-studio', 'dynamic-ai-pack'], true)) {
            $score += (int) ($rules['wrong_domain_penalty'] ?? -35);
            $penalties[] = 'nicht auf Security Operations spezialisiert';
        }

        if ($role === 'developer' && !in_array($slug, ['github-copilot', 'copilot-studio', 'dynamic-ai-pack'], true)) {
            $score += (int) ($rules['wrong_domain_penalty'] ?? -35);
            $penalties[] = 'nicht auf Developer-Workflows spezialisiert';
        }

        if ($dataScope === 'work' && $slug === 'copilot-chat' && $goal !== 'quick_research') {
            $score -= 12;
            $penalties[] = 'für vollständiges Organisationswissen meist zu schmal';
        }

        if (in_array($dataScope, ['crm', 'ticketing', 'process'], true) && $slug === 'copilot-studio') {
            $score += 12;
            $reasons[] = 'stark für eigene Quellen, Prozesse und Fachagenten';
        }

        if ($dataScope === 'web' && $goal === 'quick_research' && $slug === 'copilot-chat') {
            $score += 18;
            $reasons[] = 'schneller Einstieg für Webrecherche und Entwürfe';
        }

        if ($dataScope === 'code' && $slug === 'github-copilot') {
            $score += 22;
            $reasons[] = 'direkter Fit für Code und Developer-Tools';
        }

        if ($dataScope === 'security' && $slug === 'security-copilot') {
            $score += 24;
            $reasons[] = 'direkter Fit für Security-Signale und Incident-Kontext';
        }

        $product['score'] = max(0, min(100, $score));
        $product['fit_label'] = self::fit_label((int) $product['score']);
        $product['reasons'] = $reasons ?: ['fachlich möglich, aber nicht der stärkste Fit'];
        $product['penalties'] = $penalties;

        return $product;
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $catalog
     * @return array<string,mixed>
     */
    private static function selected_offer(string $slug, array $catalog): array
    {
        foreach ((array) ($catalog['offers'] ?? []) as $offer) {
            if (!is_array($offer)) {
                continue;
            }
            if ((string) ($offer['slug'] ?? '') === $slug) {
                return $offer;
            }
        }

        return [
            'slug' => 'none',
            'label' => 'Kein konkretes Angebotslabel',
            'maps_to' => [],
            'manual_validation_required' => false,
            'notes' => [],
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $products
     * @return array<int,array<string,mixed>>
     */
    private static function comparison_rows(array $products): array
    {
        $rows = [];
        foreach (array_slice($products, 0, 7) as $product) {
            $rows[] = [
                'name' => (string) ($product['name'] ?? ''),
                'category' => (string) ($product['category'] ?? ''),
                'data_grounding' => (string) ($product['data_grounding'] ?? ''),
                'cost_model' => (string) ($product['cost_model'] ?? ''),
                'fit_label' => (string) ($product['fit_label'] ?? ''),
                'score' => (int) ($product['score'] ?? 0),
                'manual_review' => !empty($product['manual_review']),
                'limits' => array_slice(array_map('strval', (array) ($product['limits'] ?? [])), 0, 2),
            ];
        }

        return $rows;
    }

    /**
     * @param array<string,mixed> $productCatalog
     * @return array<string,mixed>
     */
    private static function pricing_matrix(array $productCatalog): array
    {
        $matrix = is_array($productCatalog['pricing_matrix'] ?? null) ? $productCatalog['pricing_matrix'] : [];
        $tiers = array_values(array_filter((array) ($matrix['tiers'] ?? []), 'is_array'));
        $vendors = array_values(array_filter((array) ($matrix['vendors'] ?? []), 'is_array'));
        $overrides = self::pricing_matrix_overrides();

        if ($tiers === []) {
            $tiers = [
                ['key' => 'free_std', 'label' => 'Free / Std'],
                ['key' => 'pro', 'label' => 'Pro'],
                ['key' => 'pro_plus', 'label' => 'Pro+'],
                ['key' => 'team', 'label' => 'Team'],
                ['key' => 'enterprise', 'label' => 'Enterprise'],
            ];
        }

        foreach ($vendors as $vendorIndex => $vendor) {
            if (!is_array($vendor)) {
                continue;
            }

            $vendorKey = self::clean_key((string) ($vendor['key'] ?? ''));
            if ($vendorKey === '') {
                continue;
            }

            $cells = is_array($vendor['cells'] ?? null) ? $vendor['cells'] : [];

            foreach ($tiers as $tier) {
                $tierKey = self::clean_key((string) ($tier['key'] ?? ''));
                if ($tierKey === '') {
                    continue;
                }

                $cell = is_array($cells[$tierKey] ?? null) ? $cells[$tierKey] : [];
                $overrideValue = self::resolve_matrix_override_value($overrides, $vendorKey, $tierKey);

                if ($overrideValue !== '') {
                    $cell['value'] = $overrideValue;
                    $cell['verification'] = 'official';
                    $cell['last_verified'] = (string) date('Y-m-d');
                    $cell['billing_note'] = 'Preis aus zentral gepflegter Datenbank-Konfiguration.';
                }

                $cell['value'] = self::clean_matrix_text((string) ($cell['value'] ?? 'k. A.'));
                $cell['billing_note'] = self::clean_matrix_text((string) ($cell['billing_note'] ?? ''));

                if ($cell['value'] === '') {
                    $cell['value'] = 'k. A.';
                }

                if (!empty($cell['verification']) && strtolower((string) $cell['verification']) !== 'official') {
                    $cell['verification'] = 'official';
                }

                $cells[$tierKey] = $cell;
            }

            $vendors[$vendorIndex]['cells'] = $cells;
        }

        return [
            'last_verified' => (string) ($matrix['last_verified'] ?? $productCatalog['meta']['source_checked'] ?? '2026-06-01'),
            'disclaimer' => (string) ($matrix['disclaimer'] ?? 'Preise und Funktionsumfänge ändern sich häufig. Vor Kauf immer die Originalquelle prüfen.'),
            'microsoft_addon_note' => (string) ($matrix['microsoft_addon_note'] ?? ''),
            'tiers' => $tiers,
            'vendors' => $vendors,
        ];
    }

    /** @return array<string,string> */
    private static function pricing_matrix_overrides(): array
    {
        if (!class_exists('CMS_M365CALCULATOR_Settings')) {
            return [];
        }

        $modulePricing = CMS_M365CALCULATOR_Settings::module_options(self::AI_MODULE_KEY, 'pricing');
        $moduleAll = CMS_M365CALCULATOR_Settings::module_options(self::AI_MODULE_KEY);
        $globalPricing = CMS_M365CALCULATOR_Settings::global_options('pricing');
        $globalAll = CMS_M365CALCULATOR_Settings::global_options();

        return array_merge($globalAll, $globalPricing, $moduleAll, $modulePricing);
    }

    /** @param array<string,string> $overrides */
    private static function resolve_matrix_override_value(array $overrides, string $vendorKey, string $tierKey): string
    {
        $candidates = [
            'ai-price-' . $vendorKey . '-' . $tierKey,
            'ai-price-' . $vendorKey . '-' . $tierKey . '-eur',
            'ai-price-' . $vendorKey . '-' . $tierKey . '-value',
            'price-' . $vendorKey . '-' . $tierKey,
            'price-' . $vendorKey . '-' . $tierKey . '-eur',
            'pricing-matrix-' . $vendorKey . '-' . $tierKey,
        ];

        foreach ($candidates as $candidate) {
            $key = self::clean_key($candidate);
            $value = trim((string) ($overrides[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private static function clean_matrix_text(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = (string) preg_replace('/\bUNVERIFIED\b\s*:?/iu', '', $value);
        $value = trim((string) preg_replace('/\s{2,}/u', ' ', $value));

        return trim($value, " \t\n\r\0\x0B:-");
    }

    private static function clean_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($value)), '-');
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $best
     * @param array<string,mixed> $selectedOffer
     * @return array<int,string>
     */
    private static function warnings(array $input, array $best, array $selectedOffer, bool $manualReview): array
    {
        $warnings = [];
        if ($manualReview) {
            $warnings[] = 'Das gewählte Angebotslabel muss vor Bestellung gegen aktuelle Preis- und Produktunterlagen geprüft werden.';
        }
        if ((string) $input['sensitivity'] === 'confidential') {
            $warnings[] = 'Bei vertraulichen Daten Berechtigungen, Oversharing, DLP, Sensitivity Labels und Audit vor dem Rollout prüfen.';
        }
        if ((string) ($best['slug'] ?? '') === 'microsoft-365-copilot') {
            $warnings[] = 'Für Microsoft 365 Copilot müssen Basislizenz, App-Stand, Exchange-Postfach, OneDrive, Teams und Netzwerk-Readiness passen.';
        }
        if (!empty($selectedOffer['notes']) && is_array($selectedOffer['notes'])) {
            foreach (array_slice($selectedOffer['notes'], 0, 2) as $note) {
                $warnings[] = (string) $note;
            }
        }

        return array_values(array_unique($warnings));
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $matrix
     * @return array<int,array<string,string>>
     */
    private static function matching_shortcuts(array $input, array $matrix): array
    {
        $matches = [];
        foreach ((array) ($matrix['shortcuts'] ?? []) as $shortcut) {
            if (!is_array($shortcut) || !is_array($shortcut['when'] ?? null)) {
                continue;
            }
            $fits = true;
            foreach ($shortcut['when'] as $key => $value) {
                if ((string) ($input[(string) $key] ?? '') !== (string) $value) {
                    $fits = false;
                    break;
                }
            }
            if ($fits) {
                $matches[] = [
                    'prefer' => (string) ($shortcut['prefer'] ?? ''),
                    'reason' => (string) ($shortcut['reason'] ?? ''),
                ];
            }
        }

        return $matches;
    }

    /**
     * @param array<string,mixed> $best
     * @return array<string,string>
     */
    private static function status(array $best, bool $manualReview): array
    {
        $score = (int) ($best['score'] ?? 0);
        if ($manualReview || !empty($best['manual_review'])) {
            return [
                'tone' => 'warning',
                'label' => 'Empfehlung mit Angebotsprüfung',
                'summary' => 'Der fachliche Pfad ist erkennbar, das konkrete Angebotslabel muss aber aktuell validiert werden.',
            ];
        }

        if ($score >= 78) {
            return [
                'tone' => 'success',
                'label' => 'Klarer Fit',
                'summary' => 'Die Eingaben passen deutlich zum empfohlenen Produktpfad.',
            ];
        }

        return [
            'tone' => 'info',
            'label' => 'Mehrere Optionen prüfen',
            'summary' => 'Es gibt mehrere sinnvolle Optionen. Die Alternativen helfen bei der Abgrenzung.',
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $best
     * @return array<int,string>
     */
    private static function next_steps(array $input, array $best, bool $manualReview): array
    {
        $steps = [
            'Datenquellen, Rollen und Zielbild mit Fachbereich und IT bestätigen.',
            'Berechtigungen, Datenschutz, DLP, Audit und Aufbewahrung vor Pilotstart prüfen.',
        ];

        if ((string) ($best['slug'] ?? '') === 'microsoft-365-copilot') {
            $steps[] = 'Basislizenz, technische Voraussetzungen und Pilotgruppe mit dem Copilot-Lizenzchecker validieren.';
        }
        if ((string) ($best['slug'] ?? '') === 'copilot-studio') {
            $steps[] = 'Agent-Quellen, Connectoren, Dataverse- und Message-/Capacity-Modell separat kalkulieren.';
        }
        if ($manualReview) {
            $steps[] = 'Aktuelles Angebotsblatt oder Partner-Center-Preis vor Angebotserstellung gegenprüfen.';
        }
        if (!empty($input['needs_custom_agents'])) {
            $steps[] = 'Agent-Governance, Owner, Monitoring und Lifecycle für produktive Nutzung definieren.';
        }

        return array_values(array_unique($steps));
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

    private static function fit_label(int $score): string
    {
        if ($score >= 82) {
            return 'Sehr guter Fit';
        }
        if ($score >= 68) {
            return 'Guter Fit';
        }
        if ($score >= 50) {
            return 'Bedingt passend';
        }

        return 'Nur Spezialfall';
    }
}
