<?php
/**
 * CMS M365 Calculator – Frontend Controller.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Frontend
{
    private const TOOLBOX_ROUTE = '/m365-tools';
    private const TOOLBOX_ROUTE_ALIAS = '/m365-rechner';
    private const READONLY_SUITE_MATRIX_ROUTE = '/m365-lizenzmatrix';
    private const LICENSE_COMPARISON_ROUTE = '/m365-lizenzvergleich';
    private const READONLY_ADDON_MATRIX_ROUTE = '/m365-addon-matrix';
    private const ADDON_CONFIGURATOR_ROUTE = '/m365-add-on-konfigurator';
    private const COMMITMENT_ROUTE = '/m365-jahresvertrag-vs-monatsvertrag';
    private const ARCHIVE_MAILBOX_ROUTE = '/m365-archive-mailbox-rechner';
    private const AI_PRODUCT_COMPARISON_ROUTE = '/ai-pack-vs-copilot-pro';
    private const COPILOT_PILOT_ROUTE = '/copilot-pilot-rechner';
    private const FRONTLINE_WORKER_ROUTE = '/frontline-worker-lizenz-check';
    private const EXCHANGE_ONLINE_ROI_ROUTE = '/exchange-online-roi';
    private const LICENSE_ADVISOR_ROUTE = '/m365-lizenzberater';
    private const SHARED_MAILBOX_ROUTE = '/shared-mailbox-vs-lizenz';
    private const COPILOT_LICENSE_ROUTE = '/copilot-lizenz-check';
    private const COPILOT_ROI_ROUTE = '/copilot-roi-rechner';

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
        if (class_exists('CMS\\Hooks')) {
            \CMS\Hooks::addFilter('body_class', [$this, 'filter_body_class'], 20);
            \CMS\Hooks::addAction('head', [$this, 'enqueue_public_styles'], 20);
            \CMS\Hooks::addAction('body_end', [$this, 'enqueue_public_scripts'], 20);
        }
    }

    private function register_routes(): void
    {
        if (!class_exists('CMS\\Router')) {
            return;
        }

        $router = \CMS\Router::instance();

        $router->addRoute('GET', self::TOOLBOX_ROUTE, function (): void {
            $this->render_toolbox();
        });

        $router->addRoute('GET', self::TOOLBOX_ROUTE_ALIAS, function (): void {
            $this->render_toolbox();
        });

        $router->addRoute('GET', self::LICENSE_ADVISOR_ROUTE, function (): void {
            $this->render_license_advisor('GET');
        });

        $router->addRoute('GET', self::LICENSE_COMPARISON_ROUTE, function (): void {
            $this->render_license_comparison();
        });

        $router->addRoute('GET', self::READONLY_SUITE_MATRIX_ROUTE, function (): void {
            $this->render_readonly_suite_matrix();
        });

        $router->addRoute('GET', self::READONLY_ADDON_MATRIX_ROUTE, function (): void {
            $this->render_readonly_addon_matrix();
        });

        $router->addRoute('GET', self::ADDON_CONFIGURATOR_ROUTE, function (): void {
            $this->render_addon_configurator();
        });

        $router->addRoute('GET', self::COMMITMENT_ROUTE, function (): void {
            $this->render_commitment_calculator();
        });

        $router->addRoute('GET', self::ARCHIVE_MAILBOX_ROUTE, function (): void {
            $this->render_archive_mailbox_calculator();
        });

        $router->addRoute('GET', self::AI_PRODUCT_COMPARISON_ROUTE, function (): void {
            $this->render_ai_product_comparison();
        });

        $router->addRoute('GET', self::COPILOT_PILOT_ROUTE, function (): void {
            $this->render_copilot_pilot_calculator();
        });

        $router->addRoute('GET', self::FRONTLINE_WORKER_ROUTE, function (): void {
            $this->render_frontline_worker_check();
        });

        $router->addRoute('GET', self::EXCHANGE_ONLINE_ROI_ROUTE, function (): void {
            $this->render_exchange_online_roi();
        });

        $router->addRoute('POST', self::LICENSE_ADVISOR_ROUTE, function (): void {
            $this->render_license_advisor('POST');
        });

        $router->addRoute('GET', self::SHARED_MAILBOX_ROUTE, function (): void {
            $this->render_shared_mailbox('GET');
        });

        $router->addRoute('POST', self::SHARED_MAILBOX_ROUTE, function (): void {
            $this->render_shared_mailbox('POST');
        });

        $router->addRoute('GET', self::COPILOT_LICENSE_ROUTE, function (): void {
            $this->render_copilot_license_check('GET');
        });

        $router->addRoute('POST', self::COPILOT_LICENSE_ROUTE, function (): void {
            $this->render_copilot_license_check('POST');
        });

        $router->addRoute('GET', self::COPILOT_ROI_ROUTE, function (): void {
            $this->render_copilot_roi('GET');
        });

        $router->addRoute('POST', self::COPILOT_ROI_ROUTE, function (): void {
            $this->render_copilot_roi('POST');
        });
    }

    public function enqueue_public_styles(): void
    {
        if (!$this->is_calculator_request()) {
            return;
        }

        $cssFiles = ['plugin-base.css', 'style.css'];
        if (!$this->is_toolbox_request()) {
            $cssFiles[] = 'm365calculator-public.css';
        }

        foreach ($cssFiles as $cssFile) {
            $path = CMS_M365CALCULATOR_PLUGIN_DIR . 'assets/css/' . $cssFile;
            if (!file_exists($path)) {
                continue;
            }

            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_M365CALCULATOR_PLUGIN_URL . 'assets/css/' . $cssFile, ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($path) . '">' . "\n";
        }
    }

    public function enqueue_public_scripts(): void
    {
        if (!$this->is_calculator_request() || $this->is_toolbox_request()) {
            return;
        }

        $js = CMS_M365CALCULATOR_PLUGIN_DIR . 'assets/js/m365calculator-public.js';
        if (!file_exists($js)) {
            return;
        }

        echo '<script src="'
            . htmlspecialchars(CMS_M365CALCULATOR_PLUGIN_URL . 'assets/js/m365calculator-public.js', ENT_QUOTES, 'UTF-8')
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
        $classList[] = 'm365calculator-theme-embed';

        return implode(' ', array_values(array_unique($classList)));
    }

    private function render_toolbox(): void
    {
        $groupedTools = CMS_M365CALCULATOR_Tool_Registry::grouped_by_category();
        $this->set_seo('M365 Rechner', 'Übersicht verfügbarer Microsoft-365-Rechner und Berechnungstools.');
        include CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/landing.php';
        exit;
    }

    private function render_license_comparison(): void
    {
        $this->ensure_tool_available('m365-lizenzvergleich');

        $filters = CMS_M365CALCULATOR_License_Comparison::normalize_filters($_GET);
        $result = CMS_M365CALCULATOR_License_Comparison::evaluate($filters);

        $this->set_seo('M365 Lizenzvergleich', 'Vergleicht Microsoft-365-Lizenzen nach Desktop Apps, Exchange, Teams, SharePoint, Copilot, Security, Power Platform und Zusatzdiensten.');
        include CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-license-comparison.php';
        exit;
    }

    private function render_readonly_suite_matrix(): void
    {
        $this->ensure_tool_available('m365-lizenzmatrix');

        $matrix = CMS_M365CALCULATOR_ReadOnly_Matrices::suite_matrix();

        $this->set_seo('M365 Lizenzmatrix', 'Gesamtübersicht der Microsoft-365-Vollpakete von Business Basic, Standard und Premium bis Microsoft 365 E3 und E5.');
        include CMS_M365CALCULATOR_ReadOnly_Matrices::render_suite_matrix_page();
        exit;
    }

    private function render_readonly_addon_matrix(): void
    {
        $this->ensure_tool_available('m365-addon-matrix');

        $matrix = CMS_M365CALCULATOR_ReadOnly_Matrices::addon_matrix();

        $this->set_seo('M365 Add-on-Matrix', 'Gesamtübersicht der Microsoft-365-Add-ons nach Exchange, SharePoint, OneDrive, Teams, Copilot, Intune, Entra ID, Defender, Purview und Power Platform.');
        include CMS_M365CALCULATOR_ReadOnly_Matrices::render_addon_matrix_page();
        exit;
    }

    private function render_addon_configurator(): void
    {
        $this->ensure_tool_available('m365-add-on-konfigurator');

        $input = CMS_M365CALCULATOR_Addon_Configurator::normalize_input($_GET);
        $result = CMS_M365CALCULATOR_Addon_Configurator::evaluate($input);

        $this->set_seo('M365 Add-On-Konfigurator', 'Prüft Microsoft-365-Add-ons nach Basislizenz, Prerequisites, Redundanzen, Upgrade-Alternativen, Teams Phone, Copilot, Power Platform und Backup-Verbrauch.');
        include CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-addon-configurator.php';
        exit;
    }

    private function render_commitment_calculator(): void
    {
        $this->ensure_tool_available('m365-commitment-calculator');

        $input = CMS_M365CALCULATOR_Commitment_Calculator::normalize_input($_GET);
        $result = CMS_M365CALCULATOR_Commitment_Calculator::evaluate($input);

        $this->set_seo('Annual vs. Monthly Commitment Rechner', 'Vergleicht Microsoft-365-Monatslaufzeit, Jahresbindung, jährliche Abrechnung und Split-Strategien für variable Seat-Planung.');
        include CMS_M365CALCULATOR_Commitment_Calculator::render_commitment_page();
        exit;
    }

    private function render_archive_mailbox_calculator(): void
    {
        $this->ensure_tool_available('m365-archive-mailbox');

        $input = CMS_M365CALCULATOR_Archive_Mailbox_Calculator::normalize_input($_GET);
        $result = CMS_M365CALCULATOR_Archive_Mailbox_Calculator::evaluate($input);

        $this->set_seo('Archive Mailbox Rechner', 'Prüft Exchange-Archivgröße, Auto-expanding Archive, Shared-Mailbox-Grenzen, Hold und passende Microsoft-365- oder Exchange-Lizenzpfade.');
        include CMS_M365CALCULATOR_Archive_Mailbox_Calculator::render_archive_mailbox_page();
        exit;
    }

    private function render_ai_product_comparison(): void
    {
        $this->ensure_tool_available('ai-pack-vs-copilot-pro');

        $input = CMS_M365CALCULATOR_AI_Product_Comparison::normalize_input($_GET);
        $result = CMS_M365CALCULATOR_AI_Product_Comparison::evaluate($input);

        $this->set_seo('AI Pack vs. Copilot Pro Vergleich', 'Vergleicht Copilot Chat, Microsoft 365 Copilot, Copilot Studio, GitHub Copilot, Security Copilot und dynamische AI-Angebote nach Use Case.');
        include CMS_M365CALCULATOR_AI_Product_Comparison::render_ai_product_comparison_page();
        exit;
    }

    private function render_copilot_pilot_calculator(): void
    {
        $this->ensure_tool_available('copilot-pilot-calculator');

        $input = CMS_M365CALCULATOR_Copilot_Pilot_Calculator::normalize_input($_GET);
        $result = CMS_M365CALCULATOR_Copilot_Pilot_Calculator::evaluate($input);

        $this->set_seo('Copilot Pilot-Phase-Rechner', 'Empfiehlt Pilotgröße, Dauer, Budgetrahmen, Champion-Bedarf und Governance-Schritte für Microsoft 365 Copilot.');
        include CMS_M365CALCULATOR_Copilot_Pilot_Calculator::render_copilot_pilot_page();
        exit;
    }

    private function render_frontline_worker_check(): void
    {
        $this->ensure_tool_available('frontline-worker-license-check');

        $input = CMS_M365CALCULATOR_Frontline_Worker_Check::normalize_input($_GET);
        $result = CMS_M365CALCULATOR_Frontline_Worker_Check::evaluate($input);

        $this->set_seo('Frontline Worker Lizenz-Eignung-Check', 'Prüft, ob Nutzergruppen realistisch mit Microsoft 365 F1 oder F3 statt Business Premium, E3 oder E5 arbeiten können.');
        include CMS_M365CALCULATOR_Frontline_Worker_Check::render_frontline_check_page();
        exit;
    }

    private function render_exchange_online_roi(): void
    {
        $this->ensure_tool_available('exchange-online-roi');

        $input = CMS_M365CALCULATOR_Exchange_Online_ROI_Calculator::normalize_input($_GET);
        $result = CMS_M365CALCULATOR_Exchange_Online_ROI_Calculator::evaluate($input);

        $this->set_seo('Exchange Online ROI-Rechner', 'Berechnet Vollkosten, Break-even und Migrationspfad für den Wechsel von lokalem Exchange zu Exchange Online.');
        include CMS_M365CALCULATOR_Exchange_Online_ROI_Calculator::render_exchange_online_roi_page();
        exit;
    }

    private function render_license_advisor(string $method): void
    {
        $this->ensure_tool_available('m365lic');

        $input = CMS_M365CALCULATOR_License_Advisor::default_input();
        $result = null;
        $error = '';
        $notice = '';
        $personaPresets = CMS_M365CALCULATOR_License_Advisor::load_persona_presets();
        $featureOptions = CMS_M365CALCULATOR_License_Advisor::feature_options();

        $source = $method === 'POST' ? $_POST : $_GET;
        if ($method === 'POST' || !empty($_GET)) {
            $input = CMS_M365CALCULATOR_License_Advisor::normalize_input($source);
            $result = CMS_M365CALCULATOR_License_Advisor::evaluate($input);
            $notice = 'Die M365-Lizenzempfehlung wurde erstellt.';
        }

        $this->set_seo('M365 Lizenzberater', 'Empfiehlt passende Microsoft-365-Basislizenzen, Add-ons und Mischmodelle anhand konkreter Nutzergruppen und Anforderungen.');
        include CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-license-advisor.php';
        exit;
    }

    private function render_shared_mailbox(string $method): void
    {
        $this->ensure_tool_available('shared-mailbox');

        $input = CMS_M365CALCULATOR_Shared_Mailbox_Calculator::default_input();
        $result = null;
        $error = '';
        $notice = '';
        $scenarios = CMS_M365CALCULATOR_Catalog::scenarios();
        $rules = CMS_M365CALCULATOR_Catalog::rules();
        $licenseMatrix = CMS_M365CALCULATOR_Catalog::license_matrix();
        $pricing = CMS_M365CALCULATOR_Catalog::pricing();

        $source = $method === 'POST' ? $_POST : $_GET;
        if ($method === 'POST' || !empty($_GET)) {
            $input = CMS_M365CALCULATOR_Shared_Mailbox_Calculator::normalize_input($source);
            $result = CMS_M365CALCULATOR_Shared_Mailbox_Calculator::evaluate($input);
            $notice = 'Die Shared-Mailbox-Auswertung wurde erstellt.';
        }

        $this->set_seo('Shared-Mailbox vs. Lizenz-Rechner', 'Prüft, ob eine Shared Mailbox ohne Lizenz reicht, eine Zusatzlizenz nötig ist oder eine User-Mailbox bzw. Microsoft 365 Group besser passt.');
        include CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-shared-mailbox.php';
        exit;
    }

    private function render_copilot_license_check(string $method): void
    {
        $this->ensure_tool_available('copilot-license-check');

        $input = CMS_M365CALCULATOR_Copilot_License_Checker::default_input();
        $result = null;
        $error = '';
        $notice = '';
        $planOptions = CMS_M365CALCULATOR_Copilot_License_Checker::plan_options();
        $prerequisites = CMS_M365CALCULATOR_Copilot_License_Checker::load_copilot_prerequisites();
        $upgradePaths = CMS_M365CALCULATOR_Catalog::copilot_upgrade_paths();

        $source = $method === 'POST' ? $_POST : $_GET;
        if ($method === 'POST' || !empty($_GET)) {
            $input = CMS_M365CALCULATOR_Copilot_License_Checker::normalize_input($source);
            $result = CMS_M365CALCULATOR_Copilot_License_Checker::evaluate($input);
            $notice = 'Die Copilot-Lizenzprüfung wurde erstellt.';
        }

        $this->set_seo('Copilot Lizenz-Pflicht-Checker', 'Prüft, ob eine vorhandene Microsoft-365-Basislizenz für Microsoft 365 Copilot geeignet ist und welche technischen Voraussetzungen fehlen.');
        include CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-copilot-license-check.php';
        exit;
    }

    private function render_copilot_roi(string $method): void
    {
        $this->ensure_tool_available('copilot-roi');

        $input = CMS_M365CALCULATOR_Copilot_ROI_Calculator::default_input();
        $result = null;
        $error = '';
        $notice = '';
        $personaOptions = CMS_M365CALCULATOR_Copilot_ROI_Calculator::persona_options();
        $pricing = CMS_M365CALCULATOR_Catalog::copilot_pricing();
        $assumptions = CMS_M365CALCULATOR_Catalog::roi_assumptions();

        $source = $method === 'POST' ? $_POST : $_GET;
        if ($method === 'POST' || !empty($_GET)) {
            $input = CMS_M365CALCULATOR_Copilot_ROI_Calculator::normalize_input($source);
            $result = CMS_M365CALCULATOR_Copilot_ROI_Calculator::evaluate($input);
            $notice = 'Der Copilot ROI wurde berechnet.';
        }

        $this->set_seo('Copilot ROI-Rechner', 'Berechnet Microsoft-365-Copilot-ROI, Break-even-Minuten, Payback und Pilot- oder Rollout-Empfehlung inklusive Lizenz- und Readiness-Gate.');
        include CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-copilot-roi.php';
        exit;
    }

    private function ensure_tool_available(string $key): void
    {
        if (CMS_M365CALCULATOR_Tool_Registry::get($key) !== null) {
            return;
        }

        $this->render_toolbox();
    }

    private function is_calculator_request(): bool
    {
        $requestPath = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');
        if ($requestPath === '') {
            return false;
        }

        return $requestPath === trim(self::TOOLBOX_ROUTE, '/')
            || $requestPath === trim(self::TOOLBOX_ROUTE_ALIAS, '/')
            || $requestPath === trim(self::READONLY_SUITE_MATRIX_ROUTE, '/')
            || $requestPath === trim(self::LICENSE_COMPARISON_ROUTE, '/')
            || $requestPath === trim(self::READONLY_ADDON_MATRIX_ROUTE, '/')
            || $requestPath === trim(self::ADDON_CONFIGURATOR_ROUTE, '/')
            || $requestPath === trim(self::COMMITMENT_ROUTE, '/')
            || $requestPath === trim(self::ARCHIVE_MAILBOX_ROUTE, '/')
            || $requestPath === trim(self::AI_PRODUCT_COMPARISON_ROUTE, '/')
            || $requestPath === trim(self::COPILOT_PILOT_ROUTE, '/')
            || $requestPath === trim(self::FRONTLINE_WORKER_ROUTE, '/')
            || $requestPath === trim(self::EXCHANGE_ONLINE_ROI_ROUTE, '/')
            || $requestPath === trim(self::LICENSE_ADVISOR_ROUTE, '/')
            || $requestPath === trim(self::SHARED_MAILBOX_ROUTE, '/')
            || $requestPath === trim(self::COPILOT_LICENSE_ROUTE, '/')
            || $requestPath === trim(self::COPILOT_ROI_ROUTE, '/')
            || str_ends_with($requestPath, self::TOOLBOX_ROUTE)
            || str_ends_with($requestPath, self::TOOLBOX_ROUTE_ALIAS)
            || str_ends_with($requestPath, self::READONLY_SUITE_MATRIX_ROUTE)
            || str_ends_with($requestPath, self::LICENSE_COMPARISON_ROUTE)
            || str_ends_with($requestPath, self::READONLY_ADDON_MATRIX_ROUTE)
            || str_ends_with($requestPath, self::ADDON_CONFIGURATOR_ROUTE)
            || str_ends_with($requestPath, self::COMMITMENT_ROUTE)
            || str_ends_with($requestPath, self::ARCHIVE_MAILBOX_ROUTE)
            || str_ends_with($requestPath, self::AI_PRODUCT_COMPARISON_ROUTE)
            || str_ends_with($requestPath, self::COPILOT_PILOT_ROUTE)
            || str_ends_with($requestPath, self::FRONTLINE_WORKER_ROUTE)
            || str_ends_with($requestPath, self::EXCHANGE_ONLINE_ROI_ROUTE)
            || str_ends_with($requestPath, self::LICENSE_ADVISOR_ROUTE)
            || str_ends_with($requestPath, self::SHARED_MAILBOX_ROUTE)
            || str_ends_with($requestPath, self::COPILOT_LICENSE_ROUTE)
            || str_ends_with($requestPath, self::COPILOT_ROI_ROUTE);
    }

    private function is_toolbox_request(): bool
    {
        $requestPath = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');

        return $requestPath === trim(self::TOOLBOX_ROUTE, '/')
            || $requestPath === trim(self::TOOLBOX_ROUTE_ALIAS, '/')
            || str_ends_with($requestPath, self::TOOLBOX_ROUTE)
            || str_ends_with($requestPath, self::TOOLBOX_ROUTE_ALIAS);
    }

    private function set_seo(string $title, string $description): void
    {
        if (!class_exists('CMS\\Services\\SEOService')) {
            return;
        }

        try {
            $seo = \CMS\Services\SEOService::instance();
            $seo->setTitle($title);
            $seo->setDescription($description);
        } catch (\Throwable $e) {
            // SEO darf den Rechner nicht blockieren.
        }
    }
}
