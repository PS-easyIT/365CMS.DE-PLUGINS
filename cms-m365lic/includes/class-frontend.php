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
        $showAlternatives = !empty($_POST['show_alternatives']);
        $notice = '';
        $error = '';
        $limitInfo = null;
        $pricingContext = $this->build_access_context($scope, $settings);
        $userPricingProfile = $this->resolve_user_pricing_profile($scope);
        $selectedBilling = $repo->resolve_billing_cycle(null, (string) ($pricingContext['tier'] ?? 'public'), $settings);
        $viewContext = $this->build_view_context($pricingContext, $embedded, $settings, $selectedBilling, $userPricingProfile);

        if ($method === 'POST') {
            if (class_exists('CMS\Security') && !\CMS\Security::instance()->verifyToken($_POST['evaluation_csrf_token'] ?? '', 'm365lic_evaluate')) {
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
                        if ($showAlternatives) {
                            $alternativeOffers = $repo->get_alternative_offers_for_billing((string) ($selectedBilling['key'] ?? 'annual_upfront'));
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
        $billingOptions = CMS_M365LIC_Catalog::billing_options();
        $planProfiles = CMS_M365LIC_Catalog::eu_comparison_plan_profiles();
        $euCategories = CMS_M365LIC_Catalog::eu_comparison_categories();
        $euOffers = CMS_M365LIC_Catalog::eu_comparison_offers();
        $defaultSelection = CMS_M365LIC_Catalog::eu_comparison_default_selection();

        $selectedPlanSlug = array_key_first($planProfiles) ?: 'm365-business-premium';
        $selectedBilling = $repo->resolve_billing_cycle(null, 'public', $settings);
        $strategy = 'best_of_breed';
        $quantity = 25;
        $selectedEuProviders = $defaultSelection;
        $includedCategories = [];
        $comparisonResult = null;
        $notice = '';
        $error = '';
        $euPageTitle = 'EU-Vergleich · Microsoft 365 vs. europäische Anbieter';
        $euPageIntro = 'Vergleiche typische Microsoft-365-Pläne mit europäischen All-in-One- oder Best-of-Breed-Stacks – inklusive Summen und Preisdelta.';

        if ($method === 'POST') {
            $selectedPlanSlug = isset($planProfiles[(string) ($_POST['m365_plan_slug'] ?? '')])
                ? (string) $_POST['m365_plan_slug']
                : $selectedPlanSlug;
            $selectedBilling = $repo->resolve_billing_cycle((string) ($_POST['billing_cycle'] ?? ''), 'public', $settings);
            $strategy = in_array((string) ($_POST['eu_strategy'] ?? 'best_of_breed'), ['all_in_one', 'best_of_breed'], true)
                ? (string) $_POST['eu_strategy']
                : 'best_of_breed';
            $quantity = max(1, min(5000, (int) ($_POST['quantity'] ?? 25)));

            $postedSelection = is_array($_POST['eu_selection'] ?? null) ? $_POST['eu_selection'] : [];
            foreach ($defaultSelection as $categoryKey => $defaultSlug) {
                $candidateSlug = trim((string) ($postedSelection[$categoryKey] ?? $defaultSlug));
                $selectedEuProviders[$categoryKey] = $candidateSlug !== '' ? $candidateSlug : $defaultSlug;
            }

            $postedIncludes = is_array($_POST['eu_include'] ?? null) ? $_POST['eu_include'] : [];
            foreach (array_keys($euCategories) as $categoryKey) {
                $includedCategories[$categoryKey] = !empty($postedIncludes[$categoryKey]);
            }
        }

        $planProfile = $planProfiles[$selectedPlanSlug] ?? reset($planProfiles);
        if (!is_array($planProfile)) {
            $planProfile = [
                'label' => 'Microsoft 365 Business Premium',
                'included_categories' => ['core_workspace', 'office_productivity', 'collaboration_intranet', 'security_device_management'],
                'description' => '',
            ];
        }

        foreach (array_keys($euCategories) as $categoryKey) {
            if (!isset($includedCategories[$categoryKey])) {
                $includedCategories[$categoryKey] = $categoryKey === 'core_workspace'
                    || in_array($categoryKey, $planProfile['included_categories'] ?? [], true);
            }
        }

        $m365Package = $repo->get_package_by_slug($selectedPlanSlug);
        if ($m365Package === null) {
            $error = 'Der gewählte Microsoft-365-Plan ist im Paketkatalog derzeit nicht aktiv verfügbar.';
        } else {
            $comparisonResult = $this->build_eu_comparison_result(
                $m365Package,
                $planProfile,
                $selectedBilling,
                $quantity,
                $strategy,
                $euCategories,
                $euOffers,
                $selectedEuProviders,
                $includedCategories
            );

            if ($comparisonResult['m365']['line_total'] === null) {
                $error = 'Für den gewählten M365-Plan fehlt aktuell ein gepflegter Preis im Public-Kontext.';
            } else {
                $notice = 'Der EU-Vergleich wurde erfolgreich erstellt.';
            }
        }

        $this->set_seo($euPageTitle, $euPageIntro);
        include CMS_M365LIC_PLUGIN_DIR . 'templates/page-eu-comparison.php';
        exit;
    }

    /**
     * @param array<string,mixed> $m365Package
     * @param array<string,mixed> $planProfile
     * @param array<string,mixed> $billingContext
     * @param array<string,array<string,string>> $euCategories
     * @param array<string,array<int,array<string,mixed>>> $euOffers
     * @param array<string,string> $selectedEuProviders
     * @param array<string,bool> $includedCategories
     * @return array<string,mixed>
     */
    private function build_eu_comparison_result(
        array $m365Package,
        array $planProfile,
        array $billingContext,
        int $quantity,
        string $strategy,
        array $euCategories,
        array $euOffers,
        array $selectedEuProviders,
        array $includedCategories
    ): array {
        $repo = CMS_M365LIC_Repository::instance();
        $m365BasePrice = $repo->get_price_for_package($m365Package, self::SCOPE_PUBLIC, null, false);
        $m365UnitPrice = $repo->apply_billing_cycle($m365BasePrice, (string) ($billingContext['key'] ?? 'annual_upfront'));
        $m365LineTotal = $m365UnitPrice !== null ? round($m365UnitPrice * $quantity, 2) : null;
        $isMonthly = (string) ($billingContext['key'] ?? 'annual_upfront') === 'monthly_flex';
        $alternativeRows = [];
        $alternativeTotal = 0.0;
        $hasMissingAlternativePrice = false;

        foreach ($euCategories as $categoryKey => $categoryMeta) {
            if ($strategy === 'all_in_one' && $categoryKey !== 'core_workspace') {
                continue;
            }

            if ($strategy === 'best_of_breed' && $categoryKey !== 'core_workspace' && empty($includedCategories[$categoryKey])) {
                continue;
            }

            $offersForCategory = $euOffers[$categoryKey] ?? [];
            $selectedSlug = (string) ($selectedEuProviders[$categoryKey] ?? '');
            $selectedOffer = null;
            foreach ($offersForCategory as $offer) {
                if ((string) ($offer['slug'] ?? '') === $selectedSlug) {
                    $selectedOffer = $offer;
                    break;
                }
            }

            if ($selectedOffer === null && $offersForCategory !== []) {
                $selectedOffer = $offersForCategory[0];
            }

            if (!is_array($selectedOffer)) {
                continue;
            }

            $unitPrice = $isMonthly
                ? ((isset($selectedOffer['monthly_price']) && $selectedOffer['monthly_price'] !== null && $selectedOffer['monthly_price'] !== '') ? (float) $selectedOffer['monthly_price'] : null)
                : ((isset($selectedOffer['annual_price']) && $selectedOffer['annual_price'] !== null && $selectedOffer['annual_price'] !== '') ? (float) $selectedOffer['annual_price'] : null);
            $lineTotal = $unitPrice !== null ? round($unitPrice * $quantity, 2) : null;

            if ($lineTotal === null) {
                $hasMissingAlternativePrice = true;
            } else {
                $alternativeTotal += $lineTotal;
            }

            $alternativeRows[] = [
                'category_key' => $categoryKey,
                'category_label' => (string) ($categoryMeta['label'] ?? $categoryKey),
                'category_description' => (string) ($categoryMeta['description'] ?? ''),
                'provider' => (string) ($selectedOffer['provider'] ?? ''),
                'focus' => (string) ($selectedOffer['focus'] ?? ''),
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }

        return [
            'm365' => [
                'name' => (string) ($m365Package['name'] ?? ($planProfile['label'] ?? 'Microsoft 365')), 
                'description' => (string) ($planProfile['description'] ?? ''),
                'quantity' => $quantity,
                'pricing_basis_label' => 'pro Benutzer',
                'billing_cycle_label' => (string) ($billingContext['label'] ?? ''),
                'included_categories' => array_values(array_map(static function (string $categoryKey) use ($euCategories): string {
                    return (string) ($euCategories[$categoryKey]['label'] ?? $categoryKey);
                }, array_values(array_filter($planProfile['included_categories'] ?? [], 'is_string')))),
                'unit_price' => $m365UnitPrice,
                'line_total' => $m365LineTotal,
            ],
            'alternative' => [
                'strategy' => $strategy,
                'strategy_label' => $strategy === 'all_in_one' ? 'All-in-One Workspace' : 'Best-of-Breed Stack',
                'rows' => $alternativeRows,
                'line_total' => $hasMissingAlternativePrice ? null : round($alternativeTotal, 2),
                'has_missing_prices' => $hasMissingAlternativePrice,
            ],
            'delta' => ($m365LineTotal !== null && !$hasMissingAlternativePrice) ? round($alternativeTotal - $m365LineTotal, 2) : null,
        ];
    }

    private function export_pdf(): void
    {
        $repo = CMS_M365LIC_Repository::instance();
        $settings = $repo->get_settings();

        if (class_exists('CMS\Security') && !\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'm365lic_export')) {
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
                    'tier' => 'group',
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
                'summary_label' => (string) ($pricingContext['label'] ?? $settings['default_group_label'] ?? 'Spezialpreise'),
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

        $specialMarkupPercent = (float) ($specialUser['effective_markup_percent'] ?? $specialUser['special_markup_percent'] ?? 0);
        $profile['base_markup_percent'] = $specialMarkupPercent;
        $profile['addon_markup_percent'] = $specialMarkupPercent;
        $profile['copilot_markup_percent'] = $specialMarkupPercent;
        $profile['cost_overrides'] = [];
        $profile['special_markup_percent'] = $specialMarkupPercent;
        $profile['pricing_origin'] = 'special_group';
        $profile['partner_name'] = trim((string) ($specialUser['group_label'] ?? $profile['partner_name'] ?? ''));
        $profile['whitelabel_title'] = trim((string) ($specialUser['group_report_title'] ?? $profile['whitelabel_title'] ?? ''));
        $profile['whitelabel_intro'] = trim((string) ($specialUser['group_report_intro'] ?? $profile['whitelabel_intro'] ?? ''));

        return $profile;
    }

    /**
     * @param array<int|string,mixed> $requirements
     * @return array<int,array<string,mixed>>
     */
    private function normalize_requirements(array $requirements): array
    {
        $validFeatures = array_fill_keys(array_keys(CMS_M365LIC_Catalog::feature_definitions()), true);
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
            $label = mb_substr(trim((string) ($requirement['label'] ?? '')), 0, 120);
            $normalized[] = [
                'quantity' => max(1, (int) ($requirement['quantity'] ?? 1)),
                'label' => $label !== '' ? $label : 'Bedarfsgruppe',
                'audience' => in_array((string) ($requirement['audience'] ?? 'knowledge'), ['knowledge', 'frontline'], true)
                    ? (string) $requirement['audience']
                    : 'knowledge',
                'preset' => (string) ($requirement['preset'] ?? ''),
                'features' => $features,
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
        ];
    }
}
