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
    private const SPECIAL_SECTION_SLUG = 'm365-license-special';
    private const MAX_REQUIREMENT_ROWS = 25;

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

        $registry->register([
            'plugin' => 'cms-m365lic',
            'slug' => self::MEMBER_SECTION_SLUG,
            'label' => 'M365 Lizenzberater',
            'icon' => '🧮',
            'category' => 'plugins',
            'priority' => 35,
            'dashboard_widget' => false,
            'render_callback' => function (object $user, array $params): void {
                $this->render_calculator('GET', self::SCOPE_MEMBER, true);
            },
            'post_callback' => function (object $user, array $params): void {
                $this->render_calculator('POST', self::SCOPE_MEMBER, true);
            },
        ]);

        if (!CMS_M365LIC_Repository::instance()->current_user_has_special_access()) {
            return;
        }

        $registry->register([
            'plugin' => 'cms-m365lic',
            'slug' => self::SPECIAL_SECTION_SLUG,
            'label' => 'M365 Spezialpreise',
            'icon' => '🔐',
            'category' => 'plugins',
            'priority' => 36,
            'dashboard_widget' => false,
            'render_callback' => function (object $user, array $params): void {
                $this->render_calculator('GET', self::SCOPE_SPECIAL, true);
            },
            'post_callback' => function (object $user, array $params): void {
                $this->render_calculator('POST', self::SCOPE_SPECIAL, true);
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
        $notice = '';
        $error = '';
        $limitInfo = null;
        $pricingContext = $this->build_access_context($scope, $settings);
        $selectedBilling = $repo->resolve_billing_cycle(null, (string) ($pricingContext['tier'] ?? 'public'), $settings);
        $viewContext = $this->build_view_context($pricingContext, $embedded, $settings, $selectedBilling);

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
                            (string) ($selectedBilling['key'] ?? 'annual_upfront')
                        );
                        $notice = 'Die Auswertung wurde erfolgreich erstellt.';
                    }
                }
            }
        }

        $viewContext = $this->build_view_context($pricingContext, $embedded, $settings, $selectedBilling);
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
        $pricingContext = $this->build_access_context($scope, $settings, true);
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
            (string) ($billingContext['key'] ?? 'annual_upfront')
        );
        $html = CMS_M365LIC_Pdf_Export::render_html($evaluation, $normalizedRequirements, $settings, $pricingContext, $billingContext);
        CMS_M365LIC_Pdf_Export::stream_pdf($html, 'm365-lizenz-auswertung');
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

        return str_starts_with($requestPath, 'member/plugin/' . self::MEMBER_SECTION_SLUG)
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
    private function build_view_context(array $pricingContext, bool $embedded, array $settings, array $billingContext): array
    {
        $scope = (string) ($pricingContext['scope'] ?? self::SCOPE_PUBLIC);

        return match ($scope) {
            self::SCOPE_MEMBER => [
                'scope' => self::SCOPE_MEMBER,
                'embedded' => $embedded,
                'title' => 'Microsoft 365 Lizenzberater',
                'intro' => 'Microsoft 365 Lizenzberater – findet in wenigen Schritten die passende Lizenz für deinen Bedarf.',
                'summary_label' => 'Mitgliederpreise',
                'billing_label' => (string) ($billingContext['label'] ?? ''),
            ],
            self::SCOPE_SPECIAL => [
                'scope' => self::SCOPE_SPECIAL,
                'embedded' => $embedded,
                'title' => 'Microsoft 365 Spezialpreise',
                'intro' => 'Geschützter Spezialbereich für Rahmenkonditionen, Partnerpreise und individuelle Gruppenmodelle.',
                'summary_label' => (string) ($pricingContext['label'] ?? $settings['default_group_label'] ?? 'Spezialpreise'),
                'billing_label' => (string) ($billingContext['label'] ?? ''),
            ],
            default => [
                'scope' => self::SCOPE_PUBLIC,
                'embedded' => $embedded,
                'title' => (string) ($settings['page_title'] ?? 'Microsoft 365 Lizenzberater'),
                'intro' => (string) ($settings['page_intro'] ?? ''),
                'summary_label' => 'Öffentliche Preise',
                'billing_label' => (string) ($billingContext['label'] ?? ''),
            ],
        };
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
