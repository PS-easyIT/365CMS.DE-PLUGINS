<?php
/**
 * CMS M365 License – Frontend Controller
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365LIC_Frontend
{
    private const SCOPE_PUBLIC = 'public';
    private const SCOPE_MEMBER = 'member';
    private const SCOPE_SPECIAL = 'special';
    private const MEMBER_SECTION_SLUG = 'm365-license';
    private const MEMBER_SETTINGS_SECTION_SLUG = 'm365-license-settings';
    private const SPECIAL_SECTION_SLUG = 'm365-license-special';
    private const EU_COMPARE_SUFFIX = '/eu-vergleich';
    private const MAX_REQUIREMENT_ROWS = 25;
    private const EXPORT_VARIANT_STANDARD = 'standard';
    private const EXPORT_VARIANT_WHITELABEL = 'whitelabel';
    private const EXPORT_VARIANT_PARTNER = 'partner';

    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->register_routes();
        }

        return self::$instance;
    }

    private function __construct()
    {
        if (class_exists('CMS\Hooks')) {
            \CMS\Hooks::addFilter('body_class', [$this, 'filter_body_class'], 20);
            \CMS\Hooks::addAction('head', [$this, 'output_public_theme_tokens'], 19);
            \CMS\Hooks::addAction('head', [$this, 'enqueue_public_styles'], 20);
            \CMS\Hooks::addAction('body_end', [$this, 'enqueue_public_scripts'], 20);
        }
    }

    private function register_routes(): void
    {
        if (!class_exists('CMS\Router')) {
            return;
        }

        $slug = trim(CMS_M365LIC_Repository::instance()->get_settings()['route_slug'] ?? 'm365-lizenzberater', '/');
        $router = \CMS\Router::instance();

        $router->addRoute('GET', '/' . $slug, function (): void {
            $this->render_calculator('GET', self::SCOPE_PUBLIC, false);
        });

        $router->addRoute('POST', '/' . $slug, function (): void {
            $this->render_calculator('POST', self::SCOPE_PUBLIC, false);
        });

        $router->addRoute('GET', '/' . $slug . self::EU_COMPARE_SUFFIX, function (): void {
            $this->render_eu_comparison('GET');
        });

        $router->addRoute('POST', '/' . $slug . self::EU_COMPARE_SUFFIX, function (): void {
            $this->render_eu_comparison('POST');
        });

        $router->addRoute('POST', '/api/m365lic/export', function (): void {
            $this->export_pdf();
        });
    }

    public static function register_member_sections(object $registry): void
    {
        self::instance()->attach_member_sections($registry);
    }

    private function attach_member_sections(object $registry): void
    {
        if (!method_exists($registry, 'register')) {
            return;
        }

        $hasSpecialAccess = CMS_M365LIC_Repository::instance()->current_user_has_special_access();
        $registry->register([
            'plugin' => 'cms-m365lic',
            'slug' => self::MEMBER_SECTION_SLUG,
            'label' => $hasSpecialAccess ? 'M365 Resellerpreise' : 'M365 Lizenzberater',
            'icon' => $hasSpecialAccess ? '🔐' : '🧮',
            'category' => 'plugins',
            'priority' => 35,
            'dashboard_widget' => false,
            'render_callback' => function (object $user, array $params): void {
                $this->render_calculator('GET', $hasSpecialAccess ? self::SCOPE_SPECIAL : self::SCOPE_MEMBER, true);
            },
            'post_callback' => function (object $user, array $params): void {
                $this->render_calculator('POST', $hasSpecialAccess ? self::SCOPE_SPECIAL : self::SCOPE_MEMBER, true);
            },
        ]);

        $registry->register([
            'plugin' => 'cms-m365lic',
            'slug' => self::MEMBER_SETTINGS_SECTION_SLUG,
            'label' => 'Einstellungen',
            'icon' => '↳',
            'category' => 'plugins',
            'parent_slug' => 'plugin_' . self::MEMBER_SECTION_SLUG,
            'priority' => 36,
            'dashboard_widget' => false,
            'render_callback' => function (object $user, array $params): void {
                $this->render_member_settings('GET');
            },
            'post_callback' => function (object $user, array $params): void {
                $this->render_member_settings('POST');
            },
        ]);

    }

    private function render_calculator(string $method, string $scope, bool $embedded): void
    {
        $repo = CMS_M365LIC_Repository::instance();
        $settings = $repo->get_settings();
        $packages = $repo->get_packages(false);
        $featureDefinitions = CMS_M365LIC_Catalog::feature_definitions();
        $presets = CMS_M365LIC_Catalog::presets();
        $billingOptions = CMS_M365LIC_Catalog::billing_options();

        $requirements = [$this->default_requirement_row()];
        $evaluation = null;
        $alternativeOffers = [];
        $euAlternativeOffers = [];
        $showAlternatives = !empty($_POST['show_alternatives']);
        $showEuAlternatives = !empty($_POST['show_eu_alternatives']);
        $alternativeLimit = $this->clamp_alternative_limit($_POST['alternative_limit'] ?? 1);
        $notice = '';
        $error = '';
        $limitInfo = null;
        $pricingContext = $this->build_access_context($scope, $settings);
        $allowInlineAlternatives = $scope !== self::SCOPE_PUBLIC;
        if (!$allowInlineAlternatives) {
            $showAlternatives = false;
            $showEuAlternatives = false;
            $alternativeLimit = 1;
        }
        $userPricingProfile = $this->resolve_user_pricing_profile($scope);
        $selectedBilling = $repo->resolve_billing_cycle(null, (string) ($pricingContext['tier'] ?? 'public'), $settings);
        $viewContext = $this->build_view_context($pricingContext, $embedded, $settings, $selectedBilling, $userPricingProfile);

        if ($method === 'POST') {
            if (class_exists('CMS\Security') && !\CMS\Security::instance()->verifyPersistentToken($_POST['evaluation_csrf_token'] ?? '', 'm365lic_evaluate')) {
                $error = 'Sicherheitscheck fehlgeschlagen. Bitte die Seite neu laden.';
            } else {
                $requirements = $this->parse_posted_requirements();

                if (count($requirements) > self::MAX_REQUIREMENT_ROWS) {
                    $requirements = array_slice($requirements, 0, self::MAX_REQUIREMENT_ROWS);
                    $error = 'Bitte maximal ' . self::MAX_REQUIREMENT_ROWS . ' Bedarfsgruppen gleichzeitig auswerten.';
                }

                $selectedBilling = $repo->resolve_billing_cycle(
                    (string) ($_POST['billing_cycle'] ?? ''),
                    (string) ($pricingContext['tier'] ?? 'public'),
                    $settings
                );

                if ($error === '') {
                    $limitInfo = $repo->enforce_daily_limit('evaluation', (string) ($pricingContext['tier'] ?? 'public'));

                    if (empty($limitInfo['allowed'])) {
                        $error = (string) ($limitInfo['message'] ?? 'Tageslimit erreicht.');
                    } else {
                        $evaluation = CMS_M365LIC_Calculator::evaluate(
                            $requirements,
                            $packages,
                            (string) ($pricingContext['tier'] ?? 'public'),
                            (string) ($selectedBilling['key'] ?? 'annual_upfront'),
                            $userPricingProfile,
                            true
                        );
                        if ($allowInlineAlternatives && $showAlternatives) {
                            $alternativeOffers = $this->build_standard_alternative_summary(
                                $repo->get_alternative_offers_for_billing((string) ($selectedBilling['key'] ?? 'annual_upfront')),
                                $alternativeLimit
                            );
                        }
                        if ($allowInlineAlternatives && $showEuAlternatives) {
                            $euAlternativeOffers = $this->build_standard_eu_alternative_summary(
                                $requirements,
                                $selectedBilling,
                                $alternativeLimit
                            );
                        }
                        $notice = 'Die Auswertung wurde erfolgreich erstellt.';
                    }
                }
            }
        }

        $viewContext = $this->build_view_context($pricingContext, $embedded, $settings, $selectedBilling, $userPricingProfile);
        $csrfToken = class_exists('CMS\Security')
            ? \CMS\Security::instance()->generateToken('form_guard')
            : bin2hex(random_bytes(16));
        $evaluationToken = class_exists('CMS\Security')
            ? \CMS\Security::instance()->generateToken('m365lic_evaluate')
            : bin2hex(random_bytes(16));
        $this->set_seo((string) ($settings['page_title'] ?? 'Microsoft 365 Lizenzberater'), (string) ($settings['page_intro'] ?? ''));
        include CMS_M365LIC_PLUGIN_DIR . 'templates/page-calculator.php';
        if (!$embedded) {
            exit;
        }
    }

    private function render_eu_comparison(string $method): void
    {
        $repo = CMS_M365LIC_Repository::instance();
        $settings = $repo->get_settings();
        $packages = $repo->get_packages(false);
        $featureDefinitions = CMS_M365LIC_Catalog::feature_definitions();
        $presets = CMS_M365LIC_Catalog::presets();
        $billingOptions = CMS_M365LIC_Catalog::billing_options();

        $requirements = [$this->default_requirement_row()];
        $selectedBilling = $repo->resolve_billing_cycle(null, 'public', $settings);
        $strategy = 'best_of_breed';
        $comparisonResult = null;
        $notice = '';
        $error = '';
        $limitInfo = null;
        $euPageTitle = 'EU-Vergleich · Microsoft 365 vs. europäische Anbieter';
        $euPageIntro = 'Erfasse mehrere Bedarfsgruppen wie in der Standard-Auswertung und vergleiche die empfohlene Microsoft-365-Kombination direkt mit einem europäischen Alternativ-Stack.';

        if ($method === 'POST') {
            if (class_exists('CMS\Security') && !\CMS\Security::instance()->verifyPersistentToken($_POST['evaluation_csrf_token'] ?? '', 'm365lic_evaluate')) {
                $error = 'Sicherheitscheck fehlgeschlagen. Bitte die Seite neu laden.';
            } else {
                $requirements = $this->parse_posted_requirements();

                if (count($requirements) > self::MAX_REQUIREMENT_ROWS) {
                    $requirements = array_slice($requirements, 0, self::MAX_REQUIREMENT_ROWS);
                    $error = 'Bitte maximal ' . self::MAX_REQUIREMENT_ROWS . ' Bedarfsgruppen gleichzeitig auswerten.';
                }

                $selectedBilling = $repo->resolve_billing_cycle((string) ($_POST['billing_cycle'] ?? ''), 'public', $settings);
                $strategy = in_array((string) ($_POST['eu_strategy'] ?? 'best_of_breed'), ['all_in_one', 'best_of_breed'], true)
                    ? (string) $_POST['eu_strategy']
                    : 'best_of_breed';

                if ($error === '') {
                    $limitInfo = $repo->enforce_daily_limit('evaluation', 'public');

                    if (empty($limitInfo['allowed'])) {
                        $error = (string) ($limitInfo['message'] ?? 'Tageslimit erreicht.');
                    } else {
                        $m365Evaluation = CMS_M365LIC_Calculator::evaluate(
                            $requirements,
                            $packages,
                            self::SCOPE_PUBLIC,
                            (string) ($selectedBilling['key'] ?? 'annual_upfront'),
                            null,
                            true
                        );

                        $comparisonResult = $this->build_eu_comparison_result_from_evaluation(
                            $requirements,
                            $m365Evaluation,
                            $selectedBilling,
                            $strategy
                        );
                        $notice = 'Der EU-Vergleich wurde erfolgreich erstellt.';
                    }
                }
            }
        }

        $csrfToken = class_exists('CMS\Security')
            ? \CMS\Security::instance()->generateToken('form_guard')
            : bin2hex(random_bytes(16));
        $evaluationToken = class_exists('CMS\Security')
            ? \CMS\Security::instance()->generateToken('m365lic_evaluate')
            : bin2hex(random_bytes(16));

        $this->set_seo($euPageTitle, $euPageIntro);
        include CMS_M365LIC_PLUGIN_DIR . 'templates/page-eu-comparison.php';
        exit;
    }

    /**
     * @param array<int,array<string,mixed>> $requirements
     * @param array<string,mixed> $m365Evaluation
     * @param array<string,mixed> $billingContext
     * @return array<string,mixed>
     */
    private function build_eu_comparison_result_from_evaluation(
        array $requirements,
        array $m365Evaluation,
        array $billingContext,
        string $strategy
    ): array {
        $repo = CMS_M365LIC_Repository::instance();
        $euCategories = CMS_M365LIC_Catalog::eu_comparison_categories();
        $euOffers = $repo->get_eu_comparison_offers();
        $defaultSelection = CMS_M365LIC_Catalog::eu_comparison_default_selection();
        $isMonthly = (string) ($billingContext['key'] ?? 'annual_upfront') === 'monthly_flex';

        $m365Rows = $this->sanitize_public_eu_comparison_rows(
            is_array($m365Evaluation['rows'] ?? null) ? $m365Evaluation['rows'] : []
        );

        $euRows = [];
        $euTotal = 0.0;
        $euHasMissingPrices = false;

        foreach ($requirements as $index => $requirement) {
            $requirementFeatures = array_values(array_filter(array_map('strval', $requirement['features'] ?? [])));
            $euServiceState = $this->collect_eu_service_state(is_array($requirement['eu_services'] ?? null) ? $requirement['eu_services'] : []);
            $requirementLabel = trim((string) ($requirement['label'] ?? ('Bedarfsgruppe ' . ($index + 1))));
            $requirementQuantity = max(0, (int) ($requirement['quantity'] ?? 1));
            $requiredCategories = $this->determine_eu_categories_for_requirement(
                $requirementFeatures,
                is_array($requirement['eu_services'] ?? null) ? $requirement['eu_services'] : [],
                $strategy
            );

            $items = [];
            $rowTotal = 0.0;
            $rowHasMissingPrice = false;

            foreach ($requiredCategories as $categoryKey) {
                $categoryMeta = $euCategories[$categoryKey] ?? ['label' => $categoryKey, 'description' => ''];
                $defaultSlug = (string) ($defaultSelection[$categoryKey] ?? '');
                $selectedOffer = $this->select_eu_offer_for_category(
                    $categoryKey,
                    is_array($euOffers[$categoryKey] ?? null) ? $euOffers[$categoryKey] : [],
                    $defaultSlug,
                    $euServiceState,
                    $strategy,
                    $isMonthly
                );

                if (!is_array($selectedOffer)) {
                    continue;
                }

                $coverageDetails = $this->build_eu_offer_coverage_details($categoryKey, $selectedOffer, $euServiceState);

                $unitPrice = $isMonthly
                    ? ((isset($selectedOffer['monthly_price']) && $selectedOffer['monthly_price'] !== null && $selectedOffer['monthly_price'] !== '') ? (float) $selectedOffer['monthly_price'] : null)
                    : ((isset($selectedOffer['annual_price']) && $selectedOffer['annual_price'] !== null && $selectedOffer['annual_price'] !== '') ? (float) $selectedOffer['annual_price'] : null);
                $lineTotal = $unitPrice !== null ? round($unitPrice * $requirementQuantity, 2) : null;

                if ($lineTotal === null) {
                    $rowHasMissingPrice = true;
                    $euHasMissingPrices = true;
                } else {
                    $rowTotal += $lineTotal;
                }

                $items[] = [
                    'category_key' => $categoryKey,
                    'category_label' => (string) ($categoryMeta['label'] ?? $categoryKey),
                    'category_description' => (string) ($categoryMeta['description'] ?? ''),
                    'provider' => (string) ($selectedOffer['provider'] ?? ''),
                    'focus' => (string) ($selectedOffer['focus'] ?? ''),
                    'matched_service_labels' => $coverageDetails['matched_service_labels'],
                    'missing_service_labels' => $coverageDetails['missing_service_labels'],
                    'strength_note' => $coverageDetails['strength_note'],
                    'limitation_note' => $coverageDetails['limitation_note'],
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            if (!$rowHasMissingPrice) {
                $euTotal += $rowTotal;
            }

            $euRows[] = [
                'label' => $requirementLabel !== '' ? $requirementLabel : ('Bedarfsgruppe ' . ($index + 1)),
                'quantity' => $requirementQuantity,
                'items' => $items,
                'row_total' => $rowHasMissingPrice ? null : round($rowTotal, 2),
            ];
        }

        $m365Total = array_key_exists('grand_total', $m365Evaluation) ? $m365Evaluation['grand_total'] : null;

        return [
            'm365' => [
                'rows' => $m365Rows,
                'line_total' => $m365Total,
                'has_missing_prices' => !empty($m365Evaluation['has_missing_prices']),
            ],
            'alternative' => [
                'strategy' => $strategy,
                'strategy_label' => $strategy === 'all_in_one' ? 'All-in-One Workspace' : 'Best-of-Breed Stack',
                'rows' => $euRows,
                'line_total' => $euHasMissingPrices ? null : round($euTotal, 2),
                'has_missing_prices' => $euHasMissingPrices,
            ],
            'delta' => ($m365Total !== null && !$euHasMissingPrices) ? round($euTotal - (float) $m365Total, 2) : null,
        ];
    }

    /**
     * @param array<int,string> $features
     * @param array<string,array<string,bool>> $euServices
     * @return array<int,string>
     */
    private function determine_eu_categories_for_requirement(array $features, array $euServices, string $strategy): array
    {
        $categories = ['core_workspace' => true];
        $featureCategories = [];

        foreach ($features as $featureKey) {
            foreach ($this->map_feature_to_eu_categories($featureKey) as $categoryKey) {
                $featureCategories[$categoryKey] = true;
            }
        }

        if ($strategy !== 'all_in_one') {
            foreach (array_keys($featureCategories) as $categoryKey) {
                $categories[$categoryKey] = true;
            }
        } else {
            foreach (array_keys($featureCategories) as $categoryKey) {
                if (!in_array($categoryKey, ['office_productivity', 'collaboration_intranet'], true)) {
                    $categories[$categoryKey] = true;
                }
            }
        }

        $serviceDefinitions = CMS_M365LIC_Catalog::eu_service_profiles();
        foreach ($this->collect_eu_service_state($euServices)['active'] as $serviceKey) {
            $serviceMeta = $serviceDefinitions[$serviceKey] ?? null;
            if (!is_array($serviceMeta)) {
                continue;
            }

            foreach ((array) ($serviceMeta['categories'] ?? []) as $categoryKey) {
                $categoryKey = (string) $categoryKey;
                if ($categoryKey === '') {
                    continue;
                }

                if ($strategy === 'all_in_one' && $serviceKey === 'workplace_core' && $categoryKey === 'collaboration_intranet') {
                    continue;
                }

                $categories[$categoryKey] = true;
            }
        }

        return array_keys($categories);
    }

    /**
     * @param array<string,array<string,bool>> $euServices
     * @return array{current: array<int,string>, required: array<int,string>, active: array<int,string>}
     */
    private function collect_eu_service_state(array $euServices): array
    {
        $definitions = CMS_M365LIC_Catalog::eu_service_profiles();
        $current = [];
        $required = [];
        $active = [];

        foreach (array_keys($definitions) as $serviceKey) {
            $serviceState = is_array($euServices[$serviceKey] ?? null) ? $euServices[$serviceKey] : [];
            $isCurrent = !empty($serviceState['current']);
            $isRequired = !empty($serviceState['required']);

            if ($isCurrent) {
                $current[] = $serviceKey;
            }
            if ($isRequired) {
                $required[] = $serviceKey;
            }
            if ($isCurrent || $isRequired) {
                $active[] = $serviceKey;
            }
        }

        return [
            'current' => $current,
            'required' => $required,
            'active' => $active,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $offers
     * @param array{current: array<int,string>, required: array<int,string>, active: array<int,string>} $euServiceState
     * @return array<string,mixed>|null
     */
    private function select_eu_offer_for_category(
        string $categoryKey,
        array $offers,
        string $defaultSlug,
        array $euServiceState,
        string $strategy,
        bool $isMonthly
    ): ?array {
        $offers = array_values(array_filter($offers, static fn(mixed $offer): bool => is_array($offer)));
        if ($offers === []) {
            return null;
        }

        usort($offers, function (array $left, array $right) use ($categoryKey, $defaultSlug, $euServiceState, $strategy, $isMonthly): int {
            $leftScore = $this->score_eu_offer_for_category($left, $categoryKey, $defaultSlug, $euServiceState, $strategy, $isMonthly);
            $rightScore = $this->score_eu_offer_for_category($right, $categoryKey, $defaultSlug, $euServiceState, $strategy, $isMonthly);

            foreach (['required_matches', 'current_matches', 'default_match'] as $metric) {
                if ($leftScore[$metric] !== $rightScore[$metric]) {
                    return $rightScore[$metric] <=> $leftScore[$metric];
                }
            }

            if ($leftScore['missing_required'] !== $rightScore['missing_required']) {
                return $leftScore['missing_required'] <=> $rightScore['missing_required'];
            }

            if ($leftScore['price_rank'] !== $rightScore['price_rank']) {
                return $leftScore['price_rank'] <=> $rightScore['price_rank'];
            }

            return strcmp((string) ($left['provider'] ?? ''), (string) ($right['provider'] ?? ''));
        });

        return $offers[0] ?? null;
    }

    /**
     * @param array<string,mixed> $offer
     * @param array{current: array<int,string>, required: array<int,string>, active: array<int,string>} $euServiceState
     * @return array<string,int|float>
     */
    private function score_eu_offer_for_category(
        array $offer,
        string $categoryKey,
        string $defaultSlug,
        array $euServiceState,
        string $strategy,
        bool $isMonthly
    ): array {
        $profile = $this->resolve_eu_offer_capability_profile($offer);
        $offerServices = array_values(array_filter(array_map('strval', $profile['service_keys'] ?? [])));
        $requiredServices = $this->service_keys_for_category($categoryKey, $euServiceState['required']);
        $currentServices = $this->service_keys_for_category($categoryKey, $euServiceState['current']);
        $requiredMatches = count(array_intersect($requiredServices, $offerServices));
        $currentMatches = count(array_intersect($currentServices, $offerServices));
        $missingRequired = count(array_diff($requiredServices, $offerServices));
        $price = $isMonthly
            ? ((isset($offer['monthly_price']) && $offer['monthly_price'] !== null && $offer['monthly_price'] !== '') ? (float) $offer['monthly_price'] : 999999.0)
            : ((isset($offer['annual_price']) && $offer['annual_price'] !== null && $offer['annual_price'] !== '') ? (float) $offer['annual_price'] : 999999.0);

        if ($strategy === 'all_in_one' && $categoryKey === 'core_workspace' && in_array('workplace_core', $offerServices, true)) {
            $requiredMatches += 1;
        }

        return [
            'required_matches' => $requiredMatches,
            'current_matches' => $currentMatches,
            'missing_required' => $missingRequired,
            'default_match' => (string) ($offer['slug'] ?? '') === $defaultSlug ? 1 : 0,
            'price_rank' => $price,
        ];
    }

    /**
     * @param array<string,mixed> $offer
     * @return array<string,mixed>
     */
    private function resolve_eu_offer_capability_profile(array $offer): array
    {
        $profiles = CMS_M365LIC_Catalog::eu_offer_capability_profiles();
        $providerKey = $this->normalize_provider_lookup_key((string) ($offer['provider'] ?? ''));

        return is_array($profiles[$providerKey] ?? null) ? $profiles[$providerKey] : [];
    }

    private function normalize_provider_lookup_key(string $provider): string
    {
        return trim(mb_strtolower($provider));
    }

    /**
     * @param array{current: array<int,string>, required: array<int,string>, active: array<int,string>} $euServiceState
     * @return array<string,mixed>
     */
    private function build_eu_offer_coverage_details(string $categoryKey, array $offer, array $euServiceState): array
    {
        $serviceDefinitions = CMS_M365LIC_Catalog::eu_service_profiles();
        $profile = $this->resolve_eu_offer_capability_profile($offer);
        $offerServices = array_values(array_filter(array_map('strval', $profile['service_keys'] ?? [])));
        $relevantRequired = $this->service_keys_for_category($categoryKey, $euServiceState['required']);
        $matchedServiceLabels = [];
        $missingServiceLabels = [];

        foreach ($relevantRequired as $serviceKey) {
            $label = (string) (($serviceDefinitions[$serviceKey]['label'] ?? $serviceKey));
            if (in_array($serviceKey, $offerServices, true)) {
                $matchedServiceLabels[] = $label;
            } else {
                $missingServiceLabels[] = $label;
            }
        }

        $limitationNote = trim((string) ($profile['limitation_note'] ?? ''));
        if ($missingServiceLabels !== []) {
            $generatedLimitation = 'Zusatzbedarf offen bei: ' . implode(', ', $missingServiceLabels) . '.';
            $limitationNote = $limitationNote !== '' ? $limitationNote . ' ' . $generatedLimitation : $generatedLimitation;
        }

        return [
            'matched_service_labels' => $matchedServiceLabels,
            'missing_service_labels' => $missingServiceLabels,
            'strength_note' => trim((string) ($profile['strength_note'] ?? '')),
            'limitation_note' => $limitationNote,
        ];
    }

    /**
     * @param array<int,string> $serviceKeys
     * @return array<int,string>
     */
    private function service_keys_for_category(string $categoryKey, array $serviceKeys): array
    {
        $definitions = CMS_M365LIC_Catalog::eu_service_profiles();
        $matches = [];

        foreach ($serviceKeys as $serviceKey) {
            $categories = is_array($definitions[$serviceKey]['categories'] ?? null)
                ? $definitions[$serviceKey]['categories']
                : [];

            if (in_array($categoryKey, array_map('strval', $categories), true)) {
                $matches[] = $serviceKey;
            }
        }

        return array_values(array_unique($matches));
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function sanitize_public_eu_comparison_rows(array $rows): array
    {
        $sanitizedRows = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $sanitizedItems = [];
            foreach (($row['items'] ?? []) as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $item['pricing_tier'] = 'public';
                unset(
                    $item['source_note'],
                    $item['pricing_note'],
                    $item['base_unit_price'],
                    $item['cost_base_unit_price'],
                    $item['cost_unit_price'],
                    $item['markup_percent']
                );

                $sanitizedItems[] = $item;
            }

            $row['items'] = $sanitizedItems;
            $sanitizedRows[] = $row;
        }

        return $sanitizedRows;
    }

    /**
     * @param array<int,string> $features
     * @return array<int,string>
     */
    private function determine_eu_categories_for_features(array $features, string $strategy): array
    {
        if ($strategy === 'all_in_one') {
            return ['core_workspace'];
        }

        $categories = ['core_workspace' => true];
        foreach ($features as $featureKey) {
            foreach ($this->map_feature_to_eu_categories($featureKey) as $categoryKey) {
                $categories[$categoryKey] = true;
            }
        }

        return array_keys($categories);
    }

    /**
     * @return array<int,string>
     */
    private function map_feature_to_eu_categories(string $featureKey): array
    {
        return match ($featureKey) {
            'office_web', 'office_desktop', 'terminalserver', 'visio' => ['office_productivity'],
            'copilot_chat', 'copilot_m365' => ['ai_assistants'],
            'copilot_studio' => ['ai_assistants', 'low_code_automation'],
            'security_copilot' => ['ai_assistants', 'endpoint_security_xdr'],
            'teams', 'forms', 'bookings', 'stream', 'viva_engage', 'frontline', 'teams_premium' => ['collaboration_intranet'],
            'sharepoint' => ['collaboration_intranet', 'dms_archiving_compliance'],
            'phone_system', 'audio_conf' => ['enterprise_cloud_telephony'],
            'project', 'planner' => ['project_management', 'enterprise_project_management'],
            'power_apps', 'automation' => ['low_code_automation'],
            'power_bi' => ['data_analysis_bi'],
            'intune', 'intune_device', 'windows_rights' => ['mdm_uem'],
            'archive', 'exchange_protection' => ['dms_archiving_compliance'],
            'entra_id_p1', 'entra_id_p2', 'entra_governance', 'entra_suite', 'defender_identity' => ['identity_access_iam'],
            'defender', 'defender_business', 'defender_office_p1', 'defender_office_p2', 'defender_endpoint_p1', 'defender_endpoint_p2', 'defender_cloud_apps' => ['endpoint_security_xdr'],
            'defender_endpoint_p1', 'defender_endpoint_p2', 'defender_business', 'intune', 'intune_device' => ['security_device_management'],
            default => [],
        };
    }

    private function clamp_alternative_limit(mixed $value): int
    {
        $limit = (int) $value;

        if ($limit < 1) {
            return 1;
        }

        if ($limit > 10) {
            return 10;
        }

        return $limit;
    }

    /**
     * @param array<int,array<string,mixed>> $offers
     * @return array<int,array<string,mixed>>
     */
    private function build_standard_alternative_summary(array $offers, int $limit): array
    {
        $grouped = [];

        foreach ($offers as $offer) {
            if (!is_array($offer)) {
                continue;
            }

            $category = trim((string) ($offer['category'] ?? 'Allgemein'));
            if ($category === '') {
                $category = 'Allgemein';
            }

            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'category' => $category,
                    'offers' => [],
                ];
            }

            $grouped[$category]['offers'][] = [
                'provider' => (string) ($offer['provider'] ?? ''),
                'price' => isset($offer['price']) && $offer['price'] !== '' && $offer['price'] !== null ? (float) $offer['price'] : null,
            ];
        }

        foreach ($grouped as &$group) {
            usort($group['offers'], static function (array $left, array $right): int {
                $leftPrice = $left['price'] ?? null;
                $rightPrice = $right['price'] ?? null;

                if ($leftPrice === null && $rightPrice === null) {
                    return strcmp((string) ($left['provider'] ?? ''), (string) ($right['provider'] ?? ''));
                }
                if ($leftPrice === null) {
                    return 1;
                }
                if ($rightPrice === null) {
                    return -1;
                }

                $priceComparison = ((float) $leftPrice <=> (float) $rightPrice);
                if ($priceComparison !== 0) {
                    return $priceComparison;
                }

                return strcmp((string) ($left['provider'] ?? ''), (string) ($right['provider'] ?? ''));
            });

            $group['offers'] = array_slice($group['offers'], 0, $limit);
        }
        unset($group);

        ksort($grouped);

        return array_values($grouped);
    }

    /**
     * @param array<int,array<string,mixed>> $requirements
     * @return array<int,array<string,mixed>>
     */
    private function build_standard_eu_alternative_summary(array $requirements, array $billingContext, int $limit): array
    {
        $repo = CMS_M365LIC_Repository::instance();
        $euCategories = CMS_M365LIC_Catalog::eu_comparison_categories();
        $euOffers = $repo->get_eu_comparison_offers();
        $isMonthly = (string) ($billingContext['key'] ?? 'annual_upfront') === 'monthly_flex';
        $summary = [];

        foreach ($requirements as $index => $requirement) {
            $label = trim((string) ($requirement['label'] ?? ''));
            $quantity = max(0, (int) ($requirement['quantity'] ?? 1));
            $features = array_values(array_filter(array_map('strval', $requirement['features'] ?? [])));
            $requiredCategories = $this->determine_eu_categories_for_features($features, 'best_of_breed');
            $categoryRows = [];

            foreach ($requiredCategories as $categoryKey) {
                $offersForCategory = [];
                foreach (($euOffers[$categoryKey] ?? []) as $offer) {
                    if (!is_array($offer)) {
                        continue;
                    }

                    $unitPrice = $isMonthly
                        ? ((isset($offer['monthly_price']) && $offer['monthly_price'] !== '' && $offer['monthly_price'] !== null) ? (float) $offer['monthly_price'] : null)
                        : ((isset($offer['annual_price']) && $offer['annual_price'] !== '' && $offer['annual_price'] !== null) ? (float) $offer['annual_price'] : null);

                    $offersForCategory[] = [
                        'provider' => (string) ($offer['provider'] ?? ''),
                        'focus' => (string) ($offer['focus'] ?? ''),
                        'unit_price' => $unitPrice,
                        'line_total' => $unitPrice !== null ? round($unitPrice * $quantity, 2) : null,
                    ];
                }

                usort($offersForCategory, static function (array $left, array $right): int {
                    $leftPrice = $left['unit_price'] ?? null;
                    $rightPrice = $right['unit_price'] ?? null;

                    if ($leftPrice === null && $rightPrice === null) {
                        return strcmp((string) ($left['provider'] ?? ''), (string) ($right['provider'] ?? ''));
                    }
                    if ($leftPrice === null) {
                        return 1;
                    }
                    if ($rightPrice === null) {
                        return -1;
                    }

                    $priceComparison = ((float) $leftPrice <=> (float) $rightPrice);
                    if ($priceComparison !== 0) {
                        return $priceComparison;
                    }

                    return strcmp((string) ($left['provider'] ?? ''), (string) ($right['provider'] ?? ''));
                });

                $offersForCategory = array_slice($offersForCategory, 0, $limit);
                if ($offersForCategory === []) {
                    continue;
                }

                $categoryMeta = $euCategories[$categoryKey] ?? ['label' => $categoryKey, 'description' => ''];
                $categoryRows[] = [
                    'category_key' => $categoryKey,
                    'category_label' => (string) ($categoryMeta['label'] ?? $categoryKey),
                    'category_description' => (string) ($categoryMeta['description'] ?? ''),
                    'offers' => $offersForCategory,
                ];
            }

            $summary[] = [
                'label' => $label !== '' ? $label : ('Bedarfsgruppe ' . ($index + 1)),
                'quantity' => $quantity,
                'categories' => $categoryRows,
            ];
        }

        return $summary;
    }

    private function export_pdf(): void
    {
        $repo = CMS_M365LIC_Repository::instance();
        $settings = $repo->get_settings();

        if (class_exists('CMS\Security') && !\CMS\Security::instance()->verifyPersistentToken($_POST['export_csrf_token'] ?? '', 'm365lic_export')) {
            http_response_code(403);
            echo 'Sicherheitscheck fehlgeschlagen.';
            exit;
        }

        $scope = $this->sanitize_scope($_POST['context_scope'] ?? self::SCOPE_PUBLIC);
        $variant = $this->sanitize_export_variant($_POST['export_variant'] ?? ($scope === self::SCOPE_PUBLIC ? self::EXPORT_VARIANT_STANDARD : self::EXPORT_VARIANT_WHITELABEL));
        if ($scope === self::SCOPE_PUBLIC) {
            $variant = self::EXPORT_VARIANT_STANDARD;
        }
        $pricingContext = $this->build_access_context($scope, $settings, true);
        $userPricingProfile = $this->resolve_user_pricing_profile($scope);

        if (($pricingContext['scope'] ?? self::SCOPE_PUBLIC) === self::SCOPE_SPECIAL) {
            $variant = self::EXPORT_VARIANT_WHITELABEL;
        }

        $billingContext = $repo->resolve_billing_cycle(
            (string) ($_POST['billing_cycle'] ?? ''),
            (string) ($pricingContext['tier'] ?? 'public'),
            $settings
        );
        $limitInfo = $repo->enforce_daily_limit('pdf_export', (string) ($pricingContext['tier'] ?? 'public'));
        if (empty($limitInfo['allowed'])) {
            http_response_code(429);
            echo htmlspecialchars((string) ($limitInfo['message'] ?? 'Tageslimit erreicht.'), ENT_QUOTES, 'UTF-8');
            exit;
        }

        $requirements = json_decode((string) ($_POST['requirements_json'] ?? '[]'), true);
        if (!is_array($requirements)) {
            http_response_code(422);
            echo 'Ungültige Exportdaten.';
            exit;
        }

        $normalizedRequirements = $this->normalize_requirements($requirements);
        if (count($normalizedRequirements) > self::MAX_REQUIREMENT_ROWS) {
            $normalizedRequirements = array_slice($normalizedRequirements, 0, self::MAX_REQUIREMENT_ROWS);
        }

        $evaluation = CMS_M365LIC_Calculator::evaluate(
            $normalizedRequirements,
            $repo->get_packages(false),
            (string) ($pricingContext['tier'] ?? 'public'),
            (string) ($billingContext['key'] ?? 'annual_upfront'),
            $userPricingProfile,
            $variant !== self::EXPORT_VARIANT_PARTNER
        );
        $pdfSettings = $this->build_pdf_settings($settings, $variant, $userPricingProfile, $pricingContext);
        $pdfContext = $this->build_pdf_context($variant, $userPricingProfile, $pricingContext);
        $html = CMS_M365LIC_Pdf_Export::render_html($evaluation, $normalizedRequirements, $pdfSettings, $pricingContext, $billingContext, $pdfContext);
        $filename = match ($variant) {
            self::EXPORT_VARIANT_PARTNER => 'm365-partner-report',
            self::EXPORT_VARIANT_WHITELABEL => 'm365-whitelabel-report',
            default => 'm365-lizenz-auswertung',
        };
        CMS_M365LIC_Pdf_Export::stream_pdf($html, $filename);
    }

    private function set_seo(string $title, string $description): void
    {
        if (!class_exists('CMS\Services\SEOService')) {
            return;
        }

        try {
            $seo = \CMS\Services\SEOService::instance();
            $seo->setTitle($title);
            $seo->setDescription($description);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function enqueue_public_styles(): void
    {
        if (!$this->is_calculator_request()) {
            return;
        }

        $css = CMS_M365LIC_PLUGIN_DIR . 'assets/css/m365lic-public.css';
        if (!file_exists($css)) {
            return;
        }

        echo '<link rel="stylesheet" href="'
            . htmlspecialchars(CMS_M365LIC_PLUGIN_URL . 'assets/css/m365lic-public.css', ENT_QUOTES, 'UTF-8')
            . '?v=' . filemtime($css) . '">' . "\n";
    }

    public function output_public_theme_tokens(): void
    {
        if (!$this->is_calculator_request()) {
            return;
        }

        $settings = CMS_M365LIC_Repository::instance()->get_settings();

        $primary = $this->sanitize_css_color((string) ($settings['design_primary_color'] ?? '#1e3a5f'), '#1e3a5f');
        $primaryDark = $this->sanitize_css_color((string) ($settings['design_primary_dark'] ?? '#0f2240'), '#0f2240');
        $heroBg = $this->sanitize_css_color((string) ($settings['design_accent_color'] ?? '#162030'), '#162030');
        $pageBg = $this->sanitize_css_color((string) ($settings['design_page_background'] ?? '#f1f5f9'), '#f1f5f9');
        $surface = $this->sanitize_css_color((string) ($settings['design_surface_color'] ?? '#ffffff'), '#ffffff');
        $text = $this->sanitize_css_color((string) ($settings['design_text_color'] ?? '#1e293b'), '#1e293b');
        $textMuted = $this->sanitize_css_color((string) ($settings['design_text_muted_color'] ?? '#64748b'), '#64748b');
        $radius = max(6, min(24, (int) ($settings['design_border_radius'] ?? 10)));

        echo "<style>\n:root {\n"
            . '  --m365lic-primary: ' . htmlspecialchars($primary, ENT_QUOTES, 'UTF-8') . ";\n"
            . '  --m365lic-primary-dark: ' . htmlspecialchars($primaryDark, ENT_QUOTES, 'UTF-8') . ";\n"
            . "  --m365lic-accent-gold: #e8a838;\n"
            . "  --m365lic-accent-gold-dark: #d4922a;\n"
            . "  --m365lic-accent-teal: #0d9488;\n"
            . "  --m365lic-accent-teal-light: #14b8a6;\n"
            . '  --m365lic-hero-bg: ' . htmlspecialchars($heroBg, ENT_QUOTES, 'UTF-8') . ";\n"
            . '  --m365lic-bg: ' . htmlspecialchars($pageBg, ENT_QUOTES, 'UTF-8') . ";\n"
            . '  --m365lic-surface: ' . htmlspecialchars($surface, ENT_QUOTES, 'UTF-8') . ";\n"
            . '  --m365lic-text: ' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . ";\n"
            . '  --m365lic-text-muted: ' . htmlspecialchars($textMuted, ENT_QUOTES, 'UTF-8') . ";\n"
            . '  --m365lic-radius: ' . $radius . "px;\n"
            . "}\n</style>\n";
    }

    public function enqueue_public_scripts(): void
    {
        if (!$this->is_calculator_request()) {
            return;
        }

        $js = CMS_M365LIC_PLUGIN_DIR . 'assets/js/m365lic-public.js';
        if (!file_exists($js)) {
            return;
        }

        echo '<script src="'
            . htmlspecialchars(CMS_M365LIC_PLUGIN_URL . 'assets/js/m365lic-public.js', ENT_QUOTES, 'UTF-8')
            . '?v=' . filemtime($js) . '" defer></script>' . "\n";
    }

    public function filter_body_class(mixed $bodyClass): string
    {
        $classes = trim((string) $bodyClass);

        if (!$this->is_calculator_request()) {
            return $classes;
        }

        $classList = preg_split('/\s+/', $classes, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($classList)) {
            $classList = [];
        }

        $classList[] = 'm365lic-theme-embed';

        return implode(' ', array_values(array_unique($classList)));
    }

    private function is_calculator_request(): bool
    {
        $requestPath = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');
        if ($requestPath === '') {
            return false;
        }

        $publicSlug = trim((string) (CMS_M365LIC_Repository::instance()->get_settings()['route_slug'] ?? 'm365-lizenzberater'), '/');
        if ($publicSlug !== '' && ($requestPath === $publicSlug || str_ends_with($requestPath, '/' . $publicSlug))) {
            return true;
        }

        $euComparePath = trim($publicSlug . self::EU_COMPARE_SUFFIX, '/');
        if ($publicSlug !== '' && ($requestPath === $euComparePath || str_ends_with($requestPath, '/' . $euComparePath))) {
            return true;
        }

        return str_starts_with($requestPath, 'member/plugin/' . self::MEMBER_SECTION_SLUG)
            || str_starts_with($requestPath, 'member/plugin/' . self::MEMBER_SETTINGS_SECTION_SLUG)
            || str_starts_with($requestPath, 'member/plugin/' . self::SPECIAL_SECTION_SLUG);
    }

    private function sanitize_css_color(string $value, string $fallback): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }

        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/^(rgb|rgba|hsl|hsla)\([^\)]+\)$/', $value) === 1) {
            return $value;
        }

        return $fallback;
    }

    /**
     * @param array<string,string> $settings
     * @return array<string,mixed>
     */
    private function build_access_context(string $scope, array $settings, bool $strict = false): array
    {
        $scope = $this->sanitize_scope($scope);
        $repo = CMS_M365LIC_Repository::instance();

        if ($scope !== self::SCOPE_PUBLIC && !$this->is_member_logged_in()) {
            if ($strict) {
                http_response_code(403);
                echo 'Dieser Bereich ist nur für eingeloggte 365CMS-Mitglieder verfügbar.';
                exit;
            }

            $scope = self::SCOPE_PUBLIC;
        }

        if ($scope === self::SCOPE_SPECIAL) {
            $specialUser = $repo->get_current_special_user();
            if ($specialUser === null) {
                if ($strict) {
                    http_response_code(403);
                    echo 'Dieser Spezialbereich ist nur für zugewiesene Benutzer verfügbar.';
                    exit;
                }

                $scope = self::SCOPE_MEMBER;
            } else {
                return [
                    'scope' => self::SCOPE_SPECIAL,
                    'tier' => in_array((string) ($specialUser['group_pricing_tier'] ?? 'group'), ['member', 'group'], true)
                        ? (string) $specialUser['group_pricing_tier']
                        : 'group',
                    'group_key' => trim((string) ($specialUser['group_key'] ?? $settings['default_group_key'] ?? 'partner')),
                    'label' => trim((string) ($specialUser['group_label'] ?? $settings['default_group_label'] ?? 'Spezialbereich')),
                    'special_user' => $specialUser,
                ];
            }
        }

        if ($scope === self::SCOPE_MEMBER) {
            return [
                'scope' => self::SCOPE_MEMBER,
                'tier' => 'member',
                'group_key' => '',
                'label' => 'Mitgliederbereich',
                'special_user' => null,
            ];
        }

        return [
            'scope' => self::SCOPE_PUBLIC,
            'tier' => 'public',
            'group_key' => '',
            'label' => 'Öffentlicher Bereich',
            'special_user' => null,
        ];
    }

    /**
     * @param array<string,mixed> $pricingContext
     * @param array<string,string> $settings
     * @param array<string,mixed> $billingContext
     * @return array<string,mixed>
     */
    private function build_view_context(array $pricingContext, bool $embedded, array $settings, array $billingContext, ?array $userPricingProfile = null): array
    {
        $scope = (string) ($pricingContext['scope'] ?? self::SCOPE_PUBLIC);
        $settingsUrl = '/member/plugin/' . self::MEMBER_SETTINGS_SECTION_SLUG;

        return match ($scope) {
            self::SCOPE_MEMBER => [
                'scope' => self::SCOPE_MEMBER,
                'embedded' => $embedded,
                'title' => 'Microsoft 365 Lizenzberater',
                'intro' => 'Microsoft 365 Lizenzberater – findet in wenigen Schritten die passende Lizenz für deinen Bedarf.',
                'summary_label' => 'Mitgliederpreise',
                'billing_label' => (string) ($billingContext['label'] ?? ''),
                'settings_url' => $settingsUrl,
                'user_pricing_profile' => $userPricingProfile,
            ],
            self::SCOPE_SPECIAL => [
                'scope' => self::SCOPE_SPECIAL,
                'embedded' => $embedded,
                'title' => 'Microsoft 365 Spezialpreise',
                'intro' => 'Geschützter Spezialbereich für Rahmenkonditionen, Partnerpreise und individuelle Gruppenmodelle.',
                'summary_label' => ((string) ($pricingContext['tier'] ?? 'group')) === 'member'
                    ? 'Memberpreise'
                    : 'Spezialpreise',
                'billing_label' => (string) ($billingContext['label'] ?? ''),
                'settings_url' => $settingsUrl,
                'user_pricing_profile' => $userPricingProfile,
                'special_user' => $pricingContext['special_user'] ?? null,
            ],
            default => [
                'scope' => self::SCOPE_PUBLIC,
                'embedded' => $embedded,
                'title' => (string) ($settings['page_title'] ?? 'Microsoft 365 Lizenzberater'),
                'intro' => (string) ($settings['page_intro'] ?? ''),
                'summary_label' => 'Öffentliche Preise',
                'billing_label' => (string) ($billingContext['label'] ?? ''),
                'settings_url' => '',
                'user_pricing_profile' => null,
            ],
        };
    }

    private function render_member_settings(string $method): void
    {
        if (!$this->is_member_logged_in()) {
            http_response_code(403);
            echo 'Dieser Bereich ist nur für eingeloggte 365CMS-Mitglieder verfügbar.';
            return;
        }

        $repo = CMS_M365LIC_Repository::instance();
        $userId = $repo->current_user_id();
        $packages = $repo->get_packages(false);
        $settings = $repo->get_settings();
        $profile = $repo->get_user_pricing_profile($userId);
        $specialUser = $repo->get_current_special_user();
        $profile = $this->apply_special_user_context_to_profile($profile, $specialUser, false, false);
        $notice = '';
        $error = '';

        if ($method === 'POST') {
            if (class_exists('CMS\Security') && !\CMS\Security::instance()->verifyToken($_POST['member_settings_csrf_token'] ?? '', 'm365lic_member_settings')) {
                $error = 'Sicherheitscheck fehlgeschlagen. Bitte die Seite neu laden.';
            } else {
                $repo->save_user_pricing_profile($userId, [
                    'partner_name' => trim((string) ($_POST['partner_name'] ?? '')),
                    'partner_logo_path' => $this->normalize_logo_path((string) ($_POST['partner_logo_path'] ?? '')),
                    'whitelabel_title' => trim((string) ($_POST['whitelabel_title'] ?? '')),
                    'whitelabel_intro' => trim((string) ($_POST['whitelabel_intro'] ?? '')),
                    'base_markup_percent' => $_POST['base_markup_percent'] ?? 0,
                    'addon_markup_percent' => $_POST['addon_markup_percent'] ?? 0,
                    'copilot_markup_percent' => $_POST['copilot_markup_percent'] ?? 0,
                ]);
                $repo->save_user_package_costs($userId, is_array($_POST['ek_prices'] ?? null) ? $_POST['ek_prices'] : []);
                $profile = $repo->get_user_pricing_profile($userId);
                $specialUser = $repo->get_current_special_user();
                $profile = $this->apply_special_user_context_to_profile($profile, $specialUser, false, false);
                $notice = 'Deine persönlichen EK- und Report-Einstellungen wurden gespeichert.';
            }
        }

        $csrfToken = class_exists('CMS\Security')
            ? \CMS\Security::instance()->generateToken('m365lic_member_settings')
            : bin2hex(random_bytes(16));

        $this->set_seo('M365 Lizenzberater – Einstellungen', 'Pflege eigene EKs, Aufschläge, Logos und Texte für deine persönlichen M365 Reports.');
        include CMS_M365LIC_PLUGIN_DIR . 'templates/member-settings.php';
    }

    private function sanitize_scope(mixed $scope): string
    {
        return in_array((string) $scope, [self::SCOPE_PUBLIC, self::SCOPE_MEMBER, self::SCOPE_SPECIAL], true)
            ? (string) $scope
            : self::SCOPE_PUBLIC;
    }

    private function is_member_logged_in(): bool
    {
        return class_exists('CMS\Auth')
            && method_exists('CMS\Auth', 'instance')
            && \CMS\Auth::instance()->isLoggedIn();
    }

    private function sanitize_export_variant(mixed $variant): string
    {
        return in_array((string) $variant, [self::EXPORT_VARIANT_STANDARD, self::EXPORT_VARIANT_WHITELABEL, self::EXPORT_VARIANT_PARTNER], true)
            ? (string) $variant
            : self::EXPORT_VARIANT_STANDARD;
    }

    private function normalize_logo_path(string $logoPath): string
    {
        $logoPath = trim($logoPath);
        if ($logoPath === '') {
            return '';
        }

        $path = $logoPath;
        if (preg_match('#^https?://#i', $logoPath) === 1) {
            $host = (string) parse_url($logoPath, PHP_URL_HOST);
            $currentHost = (string) ($_SERVER['HTTP_HOST'] ?? '');

            if ($host === '' || $currentHost === '' || !hash_equals(strtolower($currentHost), strtolower($host))) {
                return '';
            }

            $path = (string) (parse_url($logoPath, PHP_URL_PATH) ?: '');
        }

        $path = '/' . ltrim(str_replace('\\', '/', $path), '/');

        if ($path === '/' || str_contains($path, '..')) {
            return '';
        }

        return $path;
    }

    /**
     * @param array<string,string> $settings
     * @param array<string,mixed>|null $userPricingProfile
     * @param array<string,mixed> $pricingContext
     * @return array<string,string>
     */
    private function build_pdf_settings(array $settings, string $variant, ?array $userPricingProfile, array $pricingContext): array
    {
        if ($variant === self::EXPORT_VARIANT_WHITELABEL && is_array($userPricingProfile)) {
            $partnerName = trim((string) ($userPricingProfile['partner_name'] ?? ''));
            $whitelabelTitle = trim((string) ($userPricingProfile['whitelabel_title'] ?? ''));
            $whitelabelIntro = trim((string) ($userPricingProfile['whitelabel_intro'] ?? ''));

            $settings['page_title'] = $whitelabelTitle !== ''
                ? $whitelabelTitle
                : ($partnerName !== '' ? $partnerName . ' · Lizenzreport' : ($settings['page_title'] ?? 'Lizenzreport'));
            if ($whitelabelIntro !== '') {
                $settings['page_intro'] = $whitelabelIntro;
            }
            if ($partnerName !== '') {
                $settings['pdf_footer'] = 'Whitelabel-Report für ' . $partnerName . '. ' . (string) ($settings['pdf_footer'] ?? '');
            }
        }

        if ($variant === self::EXPORT_VARIANT_PARTNER) {
            $settings['page_title'] = 'Partner Report – Einkaufskonditionen';
            $settings['page_intro'] = 'Interner Partnerreport auf EK-Basis ohne kundenseitige Aufschläge.';
            $settings['pdf_footer'] = 'Partner Report auf EK-Basis. Nicht zur direkten Weitergabe an Endkunden vorgesehen.';
        }

        if ($variant === self::EXPORT_VARIANT_STANDARD && ($pricingContext['scope'] ?? self::SCOPE_PUBLIC) !== self::SCOPE_PUBLIC) {
            $settings['pdf_footer'] = 'Mitgliederreport mit persönlichen Konditionen. ' . (string) ($settings['pdf_footer'] ?? '');
        }

        return $settings;
    }

    /**
     * @param array<string,mixed>|null $userPricingProfile
     * @param array<string,mixed> $pricingContext
     * @return array<string,mixed>
     */
    private function build_pdf_context(string $variant, ?array $userPricingProfile, array $pricingContext): array
    {
        return [
            'variant' => $variant,
            'variant_label' => match ($variant) {
                self::EXPORT_VARIANT_WHITELABEL => 'Whitelabel Report',
                self::EXPORT_VARIANT_PARTNER => 'Partner Report',
                default => 'Standard Report',
            },
            'price_mode_label' => $variant === self::EXPORT_VARIANT_PARTNER
                ? 'EK / Partnerpreise ohne Aufschlag'
                : 'Verkaufspreise inkl. persönlicher Aufschläge',
            'partner_name' => is_array($userPricingProfile) ? trim((string) ($userPricingProfile['partner_name'] ?? '')) : '',
            'logo_path' => is_array($userPricingProfile) ? trim((string) ($userPricingProfile['partner_logo_path'] ?? '')) : '',
            'scope_label' => (string) ($pricingContext['label'] ?? ''),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function resolve_user_pricing_profile(string $scope): ?array
    {
        if ($scope === self::SCOPE_PUBLIC) {
            return null;
        }

        $repo = CMS_M365LIC_Repository::instance();
        $userId = $repo->current_user_id();
        if ($userId <= 0) {
            return null;
        }

        $profile = $repo->get_user_pricing_profile($userId);

        if ($scope !== self::SCOPE_SPECIAL) {
            return $profile;
        }

        $specialUser = $repo->get_current_special_user();
        if (!is_array($specialUser)) {
            return $profile;
        }

        return $this->apply_special_user_context_to_profile($profile, $specialUser, true, true);
    }

    /**
     * @param array<string,mixed> $profile
     * @param array<string,mixed>|null $specialUser
     * @return array<string,mixed>
     */
    private function apply_special_user_context_to_profile(
        array $profile,
        ?array $specialUser,
        bool $applySpecialMarkup = true,
        bool $clearCostOverrides = false
    ): array
    {
        if (!is_array($specialUser)) {
            return $profile;
        }

        $specialMarkupPercent = (float) ($specialUser['effective_markup_percent'] ?? $specialUser['special_markup_percent'] ?? 0);
        if ($applySpecialMarkup) {
            $profile['base_markup_percent'] = $specialMarkupPercent;
            $profile['addon_markup_percent'] = $specialMarkupPercent;
            $profile['copilot_markup_percent'] = $specialMarkupPercent;
        }
        if ($clearCostOverrides) {
            $profile['cost_overrides'] = [];
        }
        $profile['special_markup_percent'] = $specialMarkupPercent;
        $profile['pricing_origin'] = 'special_group';
        $profile['group_pricing_tier'] = (string) ($specialUser['group_pricing_tier'] ?? 'group');
        $profile['partner_name'] = trim((string) ($specialUser['group_label'] ?? $profile['partner_name'] ?? ''));
        $profile['whitelabel_title'] = trim((string) ($specialUser['group_report_title'] ?? $profile['whitelabel_title'] ?? ''));
        $profile['whitelabel_intro'] = trim((string) ($specialUser['group_report_intro'] ?? $profile['whitelabel_intro'] ?? ''));
        $profile['special_user'] = $specialUser;

        return $profile;
    }

    /**
     * @param array<int|string,mixed> $requirements
     * @return array<int,array<string,mixed>>
     */
    private function normalize_requirements(array $requirements): array
    {
        $validFeatures = array_fill_keys(array_keys(CMS_M365LIC_Catalog::feature_definitions()), true);
        $validServiceProfiles = array_fill_keys(array_keys(CMS_M365LIC_Catalog::eu_service_profiles()), true);
        $normalized = [];
        foreach ($requirements as $requirement) {
            if (!is_array($requirement)) {
                continue;
            }

            $features = [];
            $rawFeatures = $requirement['features'] ?? [];
            if (is_array($rawFeatures)) {
                foreach ($rawFeatures as $key => $value) {
                    if (is_int($key)) {
                        $featureKey = (string) $value;
                        if (isset($validFeatures[$featureKey])) {
                            $features[] = $featureKey;
                        }
                        continue;
                    }
                    if (!empty($value)) {
                        $featureKey = (string) $key;
                        if (isset($validFeatures[$featureKey])) {
                            $features[] = $featureKey;
                        }
                    }
                }
            }

            $features = array_values(array_unique(array_filter(array_map('strval', $features))));
            $euServices = [];
            $rawEuServices = $requirement['eu_services'] ?? [];
            if (is_array($rawEuServices)) {
                foreach ($rawEuServices as $serviceKey => $serviceState) {
                    $normalizedServiceKey = (string) $serviceKey;
                    if (!isset($validServiceProfiles[$normalizedServiceKey]) || !is_array($serviceState)) {
                        continue;
                    }

                    $isCurrent = !empty($serviceState['current']);
                    $isRequired = !empty($serviceState['required']);
                    if (!$isCurrent && !$isRequired) {
                        continue;
                    }

                    $euServices[$normalizedServiceKey] = [
                        'current' => $isCurrent,
                        'required' => $isRequired,
                    ];
                }
            }
            $label = mb_substr(trim((string) ($requirement['label'] ?? '')), 0, 120);
            $normalized[] = [
                'quantity' => max(0, (int) ($requirement['quantity'] ?? 1)),
                'label' => $label !== '' ? $label : 'Bedarfsgruppe',
                'audience' => in_array((string) ($requirement['audience'] ?? 'knowledge'), ['knowledge', 'frontline'], true)
                    ? (string) $requirement['audience']
                    : 'knowledge',
                'preset' => (string) ($requirement['preset'] ?? ''),
                'features' => $features,
                'eu_services' => $euServices,
            ];
        }

        return !empty($normalized) ? $normalized : [$this->default_requirement_row()];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function parse_posted_requirements(): array
    {
        $payload = $_POST['requirements_payload'] ?? '';

        if (is_string($payload) && trim($payload) !== '') {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                return $this->normalize_requirements($decoded);
            }
        }

        $raw = $_POST['requirements'] ?? [];
        return is_array($raw) ? $this->normalize_requirements($raw) : [$this->default_requirement_row()];
    }

    /**
     * @return array<string,mixed>
     */
    private function default_requirement_row(): array
    {
        return [
            'quantity' => 1,
            'label' => 'Knowledge Worker',
            'audience' => 'knowledge',
            'preset' => 'knowledge_worker',
            'features' => ['mail', 'teams', 'office_web', 'office_desktop', 'onedrive', 'sharepoint', 'forms', 'stream', 'viva_engage'],
            'eu_services' => [],
        ];
    }
}
