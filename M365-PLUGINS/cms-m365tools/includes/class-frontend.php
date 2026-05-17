<?php
/**
 * CMS M365 Tools – Frontend Controller.
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
    private const TEAMS_PHONE_ADVISOR_ROUTE = '/teams-phone-lizenzberater';
    private const MICROSOFT_PRICE_TRACKER_ROUTE = '/microsoft-preiserhoehung-tracker';
    private const LICENSE_AUDIT_CHECKLIST_ROUTE = '/m365-lizenz-audit-checkliste';
    private const STORAGE_NEEDS_ROUTE = '/m365-storage-bedarfsrechner';
    private const BACKUP_COST_ROUTE = '/m365-backup-kostenrechner';
    private const WORKSPACE_M365_TCO_ROUTE = '/google-workspace-zu-m365-tco';
    private const POWER_PLATFORM_COST_ROUTE = '/power-platform-kosten-kalkulator';
    private const LICENSE_ADVISOR_ROUTE = '/m365-lizenzberater';
    private const SHARED_MAILBOX_ROUTE = '/shared-mailbox-vs-lizenz';
    private const COPILOT_LICENSE_ROUTE = '/copilot-lizenz-check';
    private const COPILOT_ROI_ROUTE = '/copilot-roi-rechner';

    private static ?self $instance = null;

    /** @var array<string,string>|null */
    private ?array $moduleRouteMapCache = null;

    /** @var array<int,string>|null */
    private ?array $publicRouteCache = null;

    private ?string $requestPathCache = null;

    private ?bool $calculatorRequestCache = null;

    private ?bool $toolboxRequestCache = null;

    private ?string $currentModuleKeyCache = null;

    private bool $currentModuleKeyResolved = false;

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
            \CMS\Hooks::addAction('head', [$this, 'output_public_design_tokens'], 30);
            \CMS\Hooks::addAction('before_footer', [$this, 'render_provider_cta'], 20);
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
            $this->render_license_advisor();
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

        $router->addRoute('GET', self::TEAMS_PHONE_ADVISOR_ROUTE, function (): void {
            $this->render_teams_phone_advisor();
        });

        $router->addRoute('GET', self::MICROSOFT_PRICE_TRACKER_ROUTE, function (): void {
            $this->render_microsoft_price_tracker();
        });

        $router->addRoute('GET', self::LICENSE_AUDIT_CHECKLIST_ROUTE, function (): void {
            $this->render_license_audit_checklist();
        });

        $router->addRoute('GET', self::STORAGE_NEEDS_ROUTE, function (): void {
            $this->render_storage_needs_calculator();
        });

        $router->addRoute('GET', self::BACKUP_COST_ROUTE, function (): void {
            $this->render_backup_cost_calculator();
        });

        $router->addRoute('GET', self::WORKSPACE_M365_TCO_ROUTE, function (): void {
            $this->render_workspace_m365_tco_calculator();
        });

        $router->addRoute('GET', self::POWER_PLATFORM_COST_ROUTE, function (): void {
            $this->render_power_platform_cost_calculator();
        });

        $router->addRoute('POST', self::LICENSE_ADVISOR_ROUTE, function (): void {
            $this->redirect_current_public_path();
        });

        $router->addRoute('GET', self::SHARED_MAILBOX_ROUTE, function (): void {
            $this->render_shared_mailbox();
        });

        $router->addRoute('POST', self::SHARED_MAILBOX_ROUTE, function (): void {
            $this->redirect_current_public_path();
        });

        $router->addRoute('GET', self::COPILOT_LICENSE_ROUTE, function (): void {
            $this->render_copilot_license_check();
        });

        $router->addRoute('POST', self::COPILOT_LICENSE_ROUTE, function (): void {
            $this->redirect_current_public_path();
        });

        $router->addRoute('GET', self::COPILOT_ROI_ROUTE, function (): void {
            $this->render_copilot_roi();
        });

        $router->addRoute('POST', self::COPILOT_ROI_ROUTE, function (): void {
            $this->redirect_current_public_path();
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

    public function output_public_design_tokens(): void
    {
        if (!$this->is_calculator_request()) {
            return;
        }

        $options = [];
        if (class_exists('CMS_M365CALCULATOR_Settings')) {
            $options = array_merge(
                CMS_M365CALCULATOR_Settings::global_options('landing'),
                CMS_M365CALCULATOR_Settings::global_options('landing-layout'),
                CMS_M365CALCULATOR_Settings::global_options('landing-colors')
            );

            $moduleKey = $this->current_module_key_from_request();
            if ($moduleKey !== null) {
                $options = $this->apply_module_design_overrides($options, $moduleKey);
            }
        }

        $color = static function (array $values, string $key, string $default): string {
            $value = (string) ($values[$key] ?? $default);

            return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $default;
        };

        $number = static function (array $values, string $key, int $default, int $min, int $max): int {
            return max($min, min($max, (int) ($values[$key] ?? $default)));
        };

        $vars = [
            '--m365tools-card-radius' => $number($options, 'landing_card_radius', 2, 0, 2) . 'px',
            '--m365tools-ui-radius' => $number($options, 'landing_card_radius', 2, 0, 2) . 'px',
            '--m365tools-content-top-gap' => '25px',
            '--m365tools-card-min' => $number($options, 'landing_cards_min_width', 320, 220, 520) . 'px',
            '--m365tools-section-gap' => $number($options, 'landing_section_gap', 32, 16, 96) . 'px',
            '--m365tools-primary' => $color($options, 'landing_color_primary', '#2563eb'),
            '--m365tools-accent' => $color($options, 'landing_color_accent', '#0f766e'),
            '--m365tools-bg' => $color($options, 'landing_color_background', '#ffffff'),
            '--m365tools-surface' => $color($options, 'landing_color_surface', '#ffffff'),
            '--m365tools-surface-alt' => $color($options, 'landing_color_surface_alt', '#f8fafc'),
            '--m365tools-header-bg' => $color($options, 'landing_color_header_background', '#f8fafc'),
            '--m365tools-header-text' => $color($options, 'landing_color_header_text', '#1e293b'),
            '--m365tools-header-muted' => $color($options, 'landing_color_header_muted', '#64748b'),
            '--m365tools-header-border' => $color($options, 'landing_color_header_border', '#e2e8f0'),
            '--m365tools-button-primary-bg' => $color($options, 'landing_color_button_primary_bg', '#2563eb'),
            '--m365tools-button-primary-text' => $color($options, 'landing_color_button_primary_text', '#ffffff'),
            '--m365tools-button-secondary-bg' => $color($options, 'landing_color_button_secondary_bg', '#ffffff'),
            '--m365tools-button-secondary-text' => $color($options, 'landing_color_button_secondary_text', '#1e293b'),
            '--m365tools-text' => $color($options, 'landing_color_text', '#1e293b'),
            '--m365tools-muted' => $color($options, 'landing_color_muted', '#64748b'),
            '--m365tools-border' => $color($options, 'landing_color_border', '#e2e8f0'),
        ];

        echo '<style id="cms-m365tools-public-design">' . "\n";
        echo ':root, body.m365tools-theme-embed, body.m365calculator-theme-embed {' . "\n";
        foreach ($vars as $name => $value) {
            echo '    ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        }
        echo '}' . "\n";
        echo '</style>' . "\n";
    }

    public function enqueue_public_scripts(): void
    {
        if (!$this->is_calculator_request()) {
            return;
        }

        $jsFile = $this->is_toolbox_request()
            ? 'm365tools-landing.js'
            : 'm365calculator-public.js';
        $js = CMS_M365CALCULATOR_PLUGIN_DIR . 'assets/js/' . $jsFile;
        if (!file_exists($js)) {
            return;
        }

        echo '<script src="'
            . htmlspecialchars(CMS_M365CALCULATOR_PLUGIN_URL . 'assets/js/' . $jsFile, ENT_QUOTES, 'UTF-8')
            . '?v=' . filemtime($js) . '" defer></script>' . "\n";
    }

    public function render_provider_cta(): void
    {
        if (!$this->is_calculator_request() || $this->is_toolbox_request() || !class_exists('CMS_M365CALCULATOR_Settings')) {
            return;
        }

        $settings = CMS_M365CALCULATOR_Settings::global_options('provider');
        if ((string) ($settings['provider_cta_enabled'] ?? '1') !== '1') {
            return;
        }

        $esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $safeUrl = static function (mixed $value): string {
            $url = trim((string) $value);
            if ($url === '') {
                return '';
            }

            if (str_starts_with($url, '/') && !str_starts_with($url, '//') && !str_contains($url, "\0")) {
                return $url;
            }

            $parts = parse_url($url);
            $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';

            return in_array($scheme, ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : '';
        };

        $style = in_array((string) ($settings['provider_cta_style'] ?? 'quiet'), ['quiet', 'boxed', 'wide'], true)
            ? (string) ($settings['provider_cta_style'] ?? 'quiet')
            : 'quiet';
        $contactUrl = $safeUrl($settings['provider_contact_form_url'] ?? '/kontakt');
        $profileUrl = $safeUrl($settings['provider_profile_url'] ?? '');
        $providerName = trim((string) ($settings['provider_name'] ?? '365 Network'));
        $headline = trim((string) ($settings['provider_headline'] ?? 'Unterstützung bei Microsoft 365 gewünscht?'));
        $text = trim((string) ($settings['provider_text'] ?? 'Wir unterstützen bei Lizenzanalyse, Umsetzung, Governance und laufender Optimierung.'));
        $buttonLabel = trim((string) ($settings['provider_button_label'] ?? 'Beratung anfragen'));
        $email = trim((string) ($settings['provider_email'] ?? ''));
        $phone = trim((string) ($settings['provider_phone'] ?? ''));
        ?>
        <section class="phinit-plugin m365calc-provider-cta m365calc-provider-cta--<?php echo $esc($style); ?>" aria-labelledby="m365calc-provider-title">
            <div class="phinit-card m365calc-provider-cta__card">
                <div>
                    <p class="phinit-overline"><?php echo $esc($providerName !== '' ? $providerName : 'Dienstleister'); ?></p>
                    <h2 id="m365calc-provider-title"><?php echo $esc($headline); ?></h2>
                    <?php if ($text !== ''): ?>
                    <p class="phinit-prose"><?php echo $esc($text); ?></p>
                    <?php endif; ?>
                    <?php if ($email !== '' || $phone !== ''): ?>
                    <p class="m365calc-provider-cta__meta">
                        <?php if ($email !== ''): ?><span><?php echo $esc($email); ?></span><?php endif; ?>
                        <?php if ($phone !== ''): ?><span><?php echo $esc($phone); ?></span><?php endif; ?>
                    </p>
                    <?php endif; ?>
                </div>
                <nav class="m365calc-actions" aria-label="Kontaktmöglichkeiten">
                    <?php if ($contactUrl !== ''): ?>
                    <a class="phinit-btn phinit-btn--primary" href="<?php echo $esc($contactUrl); ?>"><?php echo $esc($buttonLabel !== '' ? $buttonLabel : 'Kontakt aufnehmen'); ?></a>
                    <?php endif; ?>
                    <?php if ($profileUrl !== ''): ?>
                    <a class="phinit-btn phinit-btn--secondary" href="<?php echo $esc($profileUrl); ?>">Mehr erfahren</a>
                    <?php endif; ?>
                </nav>
            </div>
        </section>
        <?php
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
        $classList[] = 'm365tools-theme-embed';
        $classList[] = 'm365calculator-theme-embed';
        $moduleKey = $this->current_module_key_from_request();
        if ($moduleKey !== null) {
            $classList[] = 'm365tools-module-' . preg_replace('/[^a-z0-9_-]+/i', '-', $moduleKey);
        }

        return implode(' ', array_values(array_unique($classList)));
    }

    private function render_toolbox(): void
    {
        $landingOptions = [];
        if (class_exists('CMS_M365CALCULATOR_Settings')) {
            $landingOptions = array_merge(
                CMS_M365CALCULATOR_Settings::global_options('landing'),
                CMS_M365CALCULATOR_Settings::global_options('landing-content'),
                CMS_M365CALCULATOR_Settings::global_options('landing-layout'),
                CMS_M365CALCULATOR_Settings::global_options('landing-colors'),
                CMS_M365CALCULATOR_Settings::global_options('landing-visibility')
            );
        }
        $groupedTools = CMS_M365CALCULATOR_Tool_Registry::grouped_by_category();
        $bestPracticeCatalog = CMS_M365CALCULATOR_Catalog::m365_best_practice_catalog();
        $bestPracticeMeta = is_array($bestPracticeCatalog['meta'] ?? null) ? $bestPracticeCatalog['meta'] : [];
        $bestPracticeDomains = is_array($bestPracticeCatalog['domains'] ?? null) ? $bestPracticeCatalog['domains'] : [];
        $toolReviewMap = is_array($bestPracticeCatalog['tool_domains'] ?? null) ? $bestPracticeCatalog['tool_domains'] : [];
        $toolCheckMap = is_array($bestPracticeCatalog['module_checks'] ?? null) ? $bestPracticeCatalog['module_checks'] : [];
        $seoTitle = trim((string) ($landingOptions['landing_title'] ?? 'M365 Tools'));
        $seoDescription = trim((string) ($landingOptions['landing_intro'] ?? 'Übersicht verfügbarer Microsoft-365-Rechner, Checklisten und Berechnungstools.'));
        $this->set_seo($seoTitle !== '' ? $seoTitle : 'M365 Tools', $seoDescription !== '' ? $seoDescription : 'Übersicht verfügbarer Microsoft-365-Rechner, Checklisten und Berechnungstools.');
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

    private function render_teams_phone_advisor(): void
    {
        $this->ensure_tool_available('teams-phone-advisor');

        $input = CMS_M365CALCULATOR_Teams_Phone_Advisor::normalize_input($_GET);
        $result = CMS_M365CALCULATOR_Teams_Phone_Advisor::evaluate($input);

        $this->set_seo('Teams Phone-Lizenz-Berater', 'Empfiehlt Calling Plan, Operator Connect, Direct Routing, Mischmodell oder Architektur-Review für Microsoft Teams Phone.');
        include CMS_M365CALCULATOR_Teams_Phone_Advisor::render_teams_phone_advisor_page();
        exit;
    }

    private function render_microsoft_price_tracker(): void
    {
        $this->ensure_tool_available('microsoft-price-tracker');

        $input = CMS_M365CALCULATOR_Microsoft_Price_Tracker::normalize_input($_GET);
        $result = CMS_M365CALCULATOR_Microsoft_Price_Tracker::evaluate($input);

        $this->set_seo('Microsoft-Preiserhöhung-Tracker', 'Verfolgt offizielle Microsoft-Preis-, Packaging-, Renewal- und SKU-Ereignisse mit Budgetwirkung und visuellem Jahresvergleich.');
        include CMS_M365CALCULATOR_Microsoft_Price_Tracker::render_price_tracker_page();
        exit;
    }

    private function render_license_audit_checklist(): void
    {
        $this->ensure_tool_available('license-audit-checklist');

        $input = CMS_M365CALCULATOR_License_Audit_Checklist::normalize_input($_GET);
        $result = CMS_M365CALCULATOR_License_Audit_Checklist::evaluate($input);

        $this->set_seo('M365 Lizenz-Audit-Checkliste', 'Interaktive Microsoft-365-Auditliste für Lizenzbestand, Offboarding, Shared Mailboxes, Copilot, Speicher, Backup und Renewal.');
        include CMS_M365CALCULATOR_License_Audit_Checklist::render_license_audit_page();
        exit;
    }

    private function render_storage_needs_calculator(): void
    {
        $this->ensure_tool_available('m365-storage-needs-calculator');

        $input = CMS_M365CALCULATOR_Storage_Needs_Calculator::normalize_input($_GET);
        $result = CMS_M365CALCULATOR_Storage_Needs_Calculator::evaluate($input);

        $this->set_seo('M365 Storage-Bedarfs-Rechner', 'Berechnet SharePoint-Pool, OneDrive-Quotas, Exchange-Postfächer und Archivbedarf mit Wachstum, Puffer, Cleanup-Potenzial und operativen Microsoft-Grenzen.');
        include CMS_M365CALCULATOR_Storage_Needs_Calculator::render_storage_calculator_page();
        exit;
    }

    private function render_backup_cost_calculator(): void
    {
        $this->ensure_tool_available('m365-backup-cost-calculator');

        $input = CMS_M365CALCULATOR_Backup_Cost_Calculator::normalize_input($_GET);
        $result = CMS_M365CALCULATOR_Backup_Cost_Calculator::evaluate($input);

        $this->set_seo('M365 Backup-Kosten-Rechner', 'Berechnet Microsoft-365-Backup-Kosten pro geschütztem GB und vergleicht manuell gepflegte Providerwerte nach Kosten, Workloads, Retention und Restore-Tiefe.');
        include CMS_M365CALCULATOR_Backup_Cost_Calculator::render_backup_cost_page();
        exit;
    }

    private function render_workspace_m365_tco_calculator(): void
    {
        $this->ensure_tool_available('workspace-m365-tco-calculator');

        $input = CMS_M365CALCULATOR_Workspace_M365_TCO_Calculator::normalize_input($_GET);
        $result = CMS_M365CALCULATOR_Workspace_M365_TCO_Calculator::evaluate($input);

        $this->set_seo('Google Workspace zu Microsoft 365 TCO-Rechner', 'Vergleicht Google Workspace und Microsoft 365 über Lizenzkosten, Migration, Schulung, Change-Aufwand, Parallelbetrieb, Break-even und 3-Jahres-TCO.');
        include CMS_M365CALCULATOR_Workspace_M365_TCO_Calculator::render_workspace_tco_page();
        exit;
    }

    private function render_power_platform_cost_calculator(): void
    {
        $this->ensure_tool_available('power-platform-cost-calculator');

        $input = CMS_M365CALCULATOR_Power_Platform_Cost_Calculator::normalize_input($_GET);
        $result = CMS_M365CALCULATOR_Power_Platform_Cost_Calculator::evaluate($input);

        $this->set_seo('Power Platform Kosten-Kalkulator', 'Bewertet Power Apps, Power Automate, Dataverse for Teams, Power Pages, Copilot Studio, Credits, Storage, Requests und Governance-Kostentreiber.');
        include CMS_M365CALCULATOR_Power_Platform_Cost_Calculator::render_power_platform_page();
        exit;
    }

    private function render_license_advisor(): void
    {
        $this->ensure_tool_available('m365lic');

        $input = CMS_M365CALCULATOR_License_Advisor::default_input();
        $result = null;
        $error = '';
        $notice = '';
        $personaPresets = CMS_M365CALCULATOR_License_Advisor::load_persona_presets();
        $featureOptions = CMS_M365CALCULATOR_License_Advisor::feature_options();

        if (!empty($_GET)) {
            $source = $_GET;
            $input = CMS_M365CALCULATOR_License_Advisor::normalize_input($source);
            $result = CMS_M365CALCULATOR_License_Advisor::evaluate($input);
            $notice = 'Die M365-Lizenzempfehlung wurde erstellt.';
        }

        $this->set_seo('M365 Lizenzberater', 'Empfiehlt passende Microsoft-365-Basislizenzen, Add-ons und Mischmodelle anhand konkreter Nutzergruppen und Anforderungen.');
        include CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-license-advisor.php';
        exit;
    }

    private function render_shared_mailbox(): void
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

        if (!empty($_GET)) {
            $source = $_GET;
            $input = CMS_M365CALCULATOR_Shared_Mailbox_Calculator::normalize_input($source);
            $result = CMS_M365CALCULATOR_Shared_Mailbox_Calculator::evaluate($input);
            $notice = 'Die Shared-Mailbox-Auswertung wurde erstellt.';
        }

        $this->set_seo('Shared-Mailbox vs. Lizenz-Rechner', 'Prüft, ob eine Shared Mailbox ohne Lizenz reicht, eine Zusatzlizenz nötig ist oder eine User-Mailbox bzw. Microsoft 365 Group besser passt.');
        include CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-shared-mailbox.php';
        exit;
    }

    private function render_copilot_license_check(): void
    {
        $this->ensure_tool_available('copilot-license-check');

        $input = CMS_M365CALCULATOR_Copilot_License_Checker::default_input();
        $result = null;
        $error = '';
        $notice = '';
        $planOptions = CMS_M365CALCULATOR_Copilot_License_Checker::plan_options();
        $prerequisites = CMS_M365CALCULATOR_Copilot_License_Checker::load_copilot_prerequisites();
        $upgradePaths = CMS_M365CALCULATOR_Catalog::copilot_upgrade_paths();

        if (!empty($_GET)) {
            $source = $_GET;
            $input = CMS_M365CALCULATOR_Copilot_License_Checker::normalize_input($source);
            $result = CMS_M365CALCULATOR_Copilot_License_Checker::evaluate($input);
            $notice = 'Die Copilot-Lizenzprüfung wurde erstellt.';
        }

        $this->set_seo('Copilot Lizenz-Pflicht-Checker', 'Prüft, ob eine vorhandene Microsoft-365-Basislizenz für Microsoft 365 Copilot geeignet ist und welche technischen Voraussetzungen fehlen.');
        include CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-copilot-license-check.php';
        exit;
    }

    private function render_copilot_roi(): void
    {
        $this->ensure_tool_available('copilot-roi');

        $input = CMS_M365CALCULATOR_Copilot_ROI_Calculator::default_input();
        $result = null;
        $error = '';
        $notice = '';
        $personaOptions = CMS_M365CALCULATOR_Copilot_ROI_Calculator::persona_options();
        $pricing = CMS_M365CALCULATOR_Catalog::copilot_pricing();
        $assumptions = CMS_M365CALCULATOR_Catalog::roi_assumptions();

        if (!empty($_GET)) {
            $source = $_GET;
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

    private function redirect_current_public_path(): void
    {
        $path = $this->normalized_request_path();
        if ($path === '' || preg_match('/^[a-z0-9\/_-]+$/i', $path) !== 1) {
            $path = trim(self::TOOLBOX_ROUTE, '/');
        }

        $path = '/' . $path;

        if (!headers_sent()) {
            header('Location: ' . $path, true, 303);
        }

        exit;
    }

    private function is_calculator_request(): bool
    {
        if ($this->calculatorRequestCache !== null) {
            return $this->calculatorRequestCache;
        }

        $requestPath = $this->normalized_request_path();
        if ($requestPath === '') {
            $this->calculatorRequestCache = false;

            return $this->calculatorRequestCache;
        }

        foreach ($this->all_public_routes() as $routePath) {
            if ($this->path_matches_route($requestPath, $routePath)) {
                $this->calculatorRequestCache = true;

                return $this->calculatorRequestCache;
            }
        }

        $this->calculatorRequestCache = false;

        return $this->calculatorRequestCache;
    }

    private function is_toolbox_request(): bool
    {
        if ($this->toolboxRequestCache !== null) {
            return $this->toolboxRequestCache;
        }

        $requestPath = $this->normalized_request_path();

        $this->toolboxRequestCache = $this->path_matches_route($requestPath, trim(self::TOOLBOX_ROUTE, '/'))
            || $this->path_matches_route($requestPath, trim(self::TOOLBOX_ROUTE_ALIAS, '/'));

        return $this->toolboxRequestCache;
    }

    /**
     * @param array<string,string> $options
     * @return array<string,string>
     */
    private function apply_module_design_overrides(array $options, string $moduleKey): array
    {
        $design = CMS_M365CALCULATOR_Settings::module_options($moduleKey, 'design');
        if ((string) ($design['design_override_enabled'] ?? '0') !== '1') {
            return $options;
        }

        $map = [
            'design_card_radius' => 'landing_card_radius',
            'design_section_gap' => 'landing_section_gap',
            'design_color_primary' => 'landing_color_primary',
            'design_color_accent' => 'landing_color_accent',
            'design_color_background' => 'landing_color_background',
            'design_color_surface' => 'landing_color_surface',
            'design_color_surface_alt' => 'landing_color_surface_alt',
            'design_color_header_background' => 'landing_color_header_background',
            'design_color_header_text' => 'landing_color_header_text',
            'design_color_header_muted' => 'landing_color_header_muted',
            'design_color_header_border' => 'landing_color_header_border',
            'design_color_button_primary_bg' => 'landing_color_button_primary_bg',
            'design_color_button_primary_text' => 'landing_color_button_primary_text',
            'design_color_button_secondary_bg' => 'landing_color_button_secondary_bg',
            'design_color_button_secondary_text' => 'landing_color_button_secondary_text',
            'design_color_text' => 'landing_color_text',
            'design_color_muted' => 'landing_color_muted',
            'design_color_border' => 'landing_color_border',
        ];

        foreach ($map as $source => $target) {
            if (array_key_exists($source, $design)) {
                $options[$target] = (string) $design[$source];
            }
        }

        return $options;
    }

    private function current_module_key_from_request(): ?string
    {
        if ($this->currentModuleKeyResolved) {
            return $this->currentModuleKeyCache;
        }

        $this->currentModuleKeyResolved = true;
        $requestPath = $this->normalized_request_path();
        if ($requestPath === '') {
            return null;
        }

        foreach ($this->module_route_map() as $routePath => $moduleKey) {
            if ($this->path_matches_route($requestPath, $routePath)) {
                $this->currentModuleKeyCache = $moduleKey;

                return $this->currentModuleKeyCache;
            }
        }

        return null;
    }

    /**
     * @return array<int,string>
     */
    private function all_public_routes(): array
    {
        if ($this->publicRouteCache !== null) {
            return $this->publicRouteCache;
        }

        $this->publicRouteCache = array_merge([
            trim(self::TOOLBOX_ROUTE, '/'),
            trim(self::TOOLBOX_ROUTE_ALIAS, '/'),
        ], array_keys($this->module_route_map()));

        return $this->publicRouteCache;
    }

    /**
     * @return array<string,string>
     */
    private function module_route_map(): array
    {
        if ($this->moduleRouteMapCache !== null) {
            return $this->moduleRouteMapCache;
        }

        $map = [];

        if (class_exists('CMS_M365CALCULATOR_Tool_Registry')) {
            foreach (CMS_M365CALCULATOR_Tool_Registry::tools(false) as $tool) {
                if (!is_array($tool)) {
                    continue;
                }

                $key = trim((string) ($tool['key'] ?? ''));
                $url = trim((string) ($tool['url'] ?? ''));
                $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
                if ($key !== '' && $path !== '') {
                    $map[$path] = $key;
                }
            }
        }

        $fallback = [
            trim(self::READONLY_SUITE_MATRIX_ROUTE, '/') => 'm365-lizenzmatrix',
            trim(self::LICENSE_COMPARISON_ROUTE, '/') => 'm365-lizenzvergleich',
            trim(self::READONLY_ADDON_MATRIX_ROUTE, '/') => 'm365-addon-matrix',
            trim(self::ADDON_CONFIGURATOR_ROUTE, '/') => 'm365-add-on-konfigurator',
            trim(self::COMMITMENT_ROUTE, '/') => 'm365-commitment-calculator',
            trim(self::ARCHIVE_MAILBOX_ROUTE, '/') => 'm365-archive-mailbox',
            trim(self::AI_PRODUCT_COMPARISON_ROUTE, '/') => 'ai-pack-vs-copilot-pro',
            trim(self::COPILOT_PILOT_ROUTE, '/') => 'copilot-pilot-calculator',
            trim(self::FRONTLINE_WORKER_ROUTE, '/') => 'frontline-worker-license-check',
            trim(self::EXCHANGE_ONLINE_ROI_ROUTE, '/') => 'exchange-online-roi',
            trim(self::TEAMS_PHONE_ADVISOR_ROUTE, '/') => 'teams-phone-advisor',
            trim(self::MICROSOFT_PRICE_TRACKER_ROUTE, '/') => 'microsoft-price-tracker',
            trim(self::LICENSE_AUDIT_CHECKLIST_ROUTE, '/') => 'license-audit-checklist',
            trim(self::STORAGE_NEEDS_ROUTE, '/') => 'm365-storage-needs-calculator',
            trim(self::BACKUP_COST_ROUTE, '/') => 'm365-backup-cost-calculator',
            trim(self::WORKSPACE_M365_TCO_ROUTE, '/') => 'workspace-m365-tco-calculator',
            trim(self::POWER_PLATFORM_COST_ROUTE, '/') => 'power-platform-cost-calculator',
            trim(self::LICENSE_ADVISOR_ROUTE, '/') => 'm365lic',
            trim(self::SHARED_MAILBOX_ROUTE, '/') => 'shared-mailbox',
            trim(self::COPILOT_LICENSE_ROUTE, '/') => 'copilot-license-check',
            trim(self::COPILOT_ROI_ROUTE, '/') => 'copilot-roi',
        ];

        foreach ($fallback as $route => $moduleKey) {
            $map[$route] ??= $moduleKey;
        }

        $this->moduleRouteMapCache = $map;

        return $this->moduleRouteMapCache;
    }

    private function normalized_request_path(): string
    {
        if ($this->requestPathCache !== null) {
            return $this->requestPathCache;
        }

        $this->requestPathCache = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');

        return $this->requestPathCache;
    }

    private function path_matches_route(string $requestPath, string $routePath): bool
    {
        return $requestPath === $routePath || str_ends_with($requestPath, '/' . $routePath);
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
