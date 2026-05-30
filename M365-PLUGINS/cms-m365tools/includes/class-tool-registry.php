<?php
/**
 * CMS M365 Tools – modulare Tool-Registry.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Tool_Registry
{
    /** @var array<string,array<string,mixed>> */
    private static array $tools = [];

    private static bool $bootstrapped = false;

    /**
     * Module registrieren sich über diese Methode.
     *
    * Beispiel für ein Modul:
     * CMS_M365CALCULATOR_Tool_Registry::register([
     *     'key' => 'm365lic',
     *     'title' => 'Microsoft 365 Lizenzberater',
     *     'description' => 'Ermittelt passende Microsoft-365-Lizenzen anhand konkreter Anforderungen.',
     *     'icon' => 'license',
     *     'url' => '/m365-lizenzberater',
     *     'category' => 'Lizenzen',
     *     'status' => 'beta',
     * ]);
     *
     * @param array<string,mixed> $tool
     */
    public static function register(array $tool): void
    {
        $normalized = self::normalize($tool);
        if ($normalized === []) {
            return;
        }

        self::$tools[(string) $normalized['key']] = $normalized;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function tools(bool $applySettings = true): array
    {
        self::bootstrap();

        if (class_exists('CMS\\Hooks') && method_exists('CMS\\Hooks', 'applyFilters')) {
            $filtered = \CMS\Hooks::applyFilters('m365calculator_tools', self::$tools);
            if (is_array($filtered)) {
                foreach ($filtered as $tool) {
                    if (is_array($tool)) {
                        self::register($tool);
                    }
                }
            }
        }

        return $applySettings && class_exists('CMS_M365CALCULATOR_Settings')
            ? CMS_M365CALCULATOR_Settings::apply_to_tools(self::$tools)
            : self::$tools;
    }

    /**
     * @return array<string,array<int,array<string,mixed>>>
     */
    public static function grouped_by_category(): array
    {
        $groups = [];
        foreach (self::ordered_tools() as $tool) {
            $category = (string) ($tool['category'] ?? 'Weitere Tools');
            $groups[$category] ??= [];
            $groups[$category][] = $tool;
        }

        ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);

        return $groups;
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function get(string $key): ?array
    {
        $tools = self::tools();
        return is_array($tools[$key] ?? null) ? $tools[$key] : null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function ordered_tools(bool $applySettings = true): array
    {
        $tools = array_values(self::tools($applySettings));
        $statusOrder = ['live' => 0, 'beta' => 1, 'soon' => 2];

        usort($tools, static function (array $left, array $right) use ($statusOrder): int {
            $leftStatus = $statusOrder[(string) ($left['status'] ?? 'soon')] ?? 99;
            $rightStatus = $statusOrder[(string) ($right['status'] ?? 'soon')] ?? 99;

            if ($leftStatus !== $rightStatus) {
                return $leftStatus <=> $rightStatus;
            }

            $priority = ((int) ($left['priority'] ?? 100)) <=> ((int) ($right['priority'] ?? 100));
            if ($priority !== 0) {
                return $priority;
            }

            return strcmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
        });

        return $tools;
    }

    private static function bootstrap(): void
    {
        if (self::$bootstrapped) {
            return;
        }
        self::$bootstrapped = true;

        self::register_shared_mailbox_module();
        if (!self::matrix_plugin_active()) {
            self::register_readonly_suite_matrix_module();
        }
        self::register_license_comparison_module();
        if (!self::matrix_plugin_active()) {
            self::register_readonly_addon_matrix_module();
        }
        self::register_addon_configurator_module();
        self::register_commitment_calculator_module();
        self::register_archive_mailbox_calculator_module();
        self::register_ai_product_comparison_module();
        self::register_copilot_pilot_calculator_module();
        self::register_frontline_worker_license_check_module();
        self::register_exchange_online_roi_module();
        self::register_teams_phone_advisor_module();
        self::register_microsoft_price_tracker_module();
        self::register_license_audit_checklist_module();
        self::register_storage_needs_calculator_module();
        self::register_backup_cost_calculator_module();
        self::register_workspace_m365_tco_module();
        self::register_power_platform_cost_calculator_module();
        self::register_license_advisor_module();
        self::register_copilot_license_checker_module();
        self::register_copilot_roi_module();
    }

    private static function register_addon_configurator_module(): void
    {
        self::register([
            'key' => 'm365-add-on-konfigurator',
            'title' => 'M365 Add-On-Konfigurator',
            'description' => 'Prüft Add-ons, Prerequisites, Redundanzen, Verbrauchsprodukte und Upgrade-Alternativen für Microsoft 365.',
            'icon' => 'addons',
            'url' => '/m365-add-on-konfigurator',
            'category' => 'Lizenzen',
            'status' => 'live',
            'priority' => 4,
        ]);
    }

    private static function register_commitment_calculator_module(): void
    {
        self::register([
            'key' => 'm365-commitment-calculator',
            'title' => 'Annual vs. Monthly Commitment Rechner',
            'description' => 'Vergleicht Monatslaufzeit, Jahresbindung, jährliche Abrechnung und Split-Strategie für variable Microsoft-365-Seats.',
            'icon' => 'calculator',
            'url' => '/m365-jahresvertrag-vs-monatsvertrag',
            'category' => 'Lizenzen',
            'status' => 'live',
            'priority' => 6,
        ]);
    }

    private static function register_archive_mailbox_calculator_module(): void
    {
        self::register([
            'key' => 'm365-archive-mailbox',
            'title' => 'Archive Mailbox Rechner',
            'description' => 'Prüft Archivgröße, Auto-expanding Archive, Shared-Mailbox-Sonderfälle, Hold und passende Exchange-/M365-Lizenzpfade.',
            'icon' => 'mailbox',
            'url' => '/m365-archive-mailbox-rechner',
            'category' => 'Exchange',
            'status' => 'live',
            'priority' => 8,
        ]);
    }

    private static function register_ai_product_comparison_module(): void
    {
        self::register([
            'key' => 'ai-pack-vs-copilot-pro',
            'title' => 'AI Pack vs. Copilot Pro Vergleich',
            'description' => 'Vergleicht Copilot Chat, Microsoft 365 Copilot, Spezial-Copilots, Copilot Studio und dynamische AI-Angebote nach Use Case.',
            'icon' => 'copilot',
            'url' => '/ai-pack-vs-copilot-pro',
            'category' => 'Copilot',
            'status' => 'live',
            'priority' => 12,
        ]);
    }

    private static function register_copilot_pilot_calculator_module(): void
    {
        self::register([
            'key' => 'copilot-pilot-calculator',
            'title' => 'Copilot Pilot-Phase-Rechner',
            'description' => 'Empfiehlt Pilotgröße, Dauer, Budgetrahmen, Champion-Bedarf und Governance-Schritte für Microsoft 365 Copilot.',
            'icon' => 'copilot',
            'url' => '/copilot-pilot-rechner',
            'category' => 'Copilot',
            'status' => 'live',
            'priority' => 13,
        ]);
    }

    private static function register_frontline_worker_license_check_module(): void
    {
        self::register([
            'key' => 'frontline-worker-license-check',
            'title' => 'Frontline Worker Lizenz-Eignung-Check',
            'description' => 'Prüft F1, F3, Mischmodell oder Enterprise-Bedarf für mobile, schichtbasierte und deskless Nutzergruppen.',
            'icon' => 'license',
            'url' => '/frontline-worker-lizenz-check',
            'category' => 'Lizenzen',
            'status' => 'live',
            'priority' => 7,
        ]);
    }

    private static function register_exchange_online_roi_module(): void
    {
        self::register([
            'key' => 'exchange-online-roi',
            'title' => 'On-Prem Exchange zu Exchange Online ROI',
            'description' => 'Berechnet Vollkosten, Break-even, Migrationspfad und Management-Fazit für Exchange Online.',
            'icon' => 'roi',
            'url' => '/exchange-online-roi',
            'category' => 'Exchange',
            'status' => 'live',
            'priority' => 7,
        ]);
    }

    private static function register_teams_phone_advisor_module(): void
    {
        self::register([
            'key' => 'teams-phone-advisor',
            'title' => 'Teams Phone-Lizenz-Berater',
            'description' => 'Vergleicht Calling Plan, Operator Connect, Direct Routing, Mischmodell und Sonderpfade für Teams Phone.',
            'icon' => 'phone',
            'url' => '/teams-phone-lizenzberater',
            'category' => 'Teams',
            'status' => 'live',
            'priority' => 8,
        ]);
    }

    private static function register_microsoft_price_tracker_module(): void
    {
        self::register([
            'key' => 'microsoft-price-tracker',
            'title' => 'Microsoft-Preiserhöhung-Tracker',
            'description' => 'Bewertet offizielle Microsoft-Preis-, Packaging- und Renewal-Ereignisse mit Budgetchart und Forecast-Trennung.',
            'icon' => 'roi',
            'url' => '/microsoft-preiserhoehung-tracker',
            'category' => 'Lizenzen',
            'status' => 'live',
            'priority' => 9,
        ]);
    }

    private static function register_license_audit_checklist_module(): void
    {
        self::register([
            'key' => 'license-audit-checklist',
            'title' => 'Lizenz-Audit-Checkliste',
            'description' => 'Interaktive Microsoft-365-Auditliste für Lizenzbestand, Offboarding, Shared Mailboxes, Copilot, Speicher und Renewal.',
            'icon' => 'license',
            'url' => '/m365-lizenz-audit-checkliste',
            'category' => 'Lizenzen',
            'status' => 'live',
            'priority' => 1,
        ]);
    }

    private static function register_backup_cost_calculator_module(): void
    {
        self::register([
            'key' => 'm365-backup-cost-calculator',
            'title' => 'M365 Backup-Kosten-Rechner',
            'description' => 'Berechnet Microsoft-365-Backup-Kosten pro geschütztem GB und vergleicht manuell gepflegte Providerwerte nach Kosten und Schutzgrad.',
            'icon' => 'storage',
            'url' => '/m365-backup-kostenrechner',
            'category' => 'Speicher',
            'status' => 'live',
            'priority' => 2,
        ]);
    }

    private static function register_storage_needs_calculator_module(): void
    {
        self::register([
            'key' => 'm365-storage-needs-calculator',
            'title' => 'M365 Storage-Bedarfs-Rechner',
            'description' => 'Berechnet SharePoint-Pool, OneDrive-Quotas, Exchange-Postfächer, Archivbedarf, Wachstum und Cleanup-Potenzial.',
            'icon' => 'storage',
            'url' => '/m365-storage-bedarfsrechner',
            'category' => 'Speicher',
            'status' => 'live',
            'priority' => 1,
        ]);
    }

    private static function register_workspace_m365_tco_module(): void
    {
        self::register([
            'key' => 'workspace-m365-tco-calculator',
            'title' => 'Google Workspace ↔ M365 TCO-Rechner',
            'description' => 'Vergleicht Google Workspace und Microsoft 365 über Lizenzkosten, Migration, Schulung, Change-Aufwand, Parallelbetrieb und Break-even.',
            'icon' => 'roi',
            'url' => '/google-workspace-zu-m365-tco',
            'category' => 'Migration',
            'status' => 'live',
            'priority' => 1,
        ]);
    }

    private static function register_power_platform_cost_calculator_module(): void
    {
        self::register([
            'key' => 'power-platform-cost-calculator',
            'title' => 'Power Platform Kosten-Kalkulator',
            'description' => 'Bewertet Power Apps, Power Automate, Dataverse, Power Pages, Copilot Studio, Credits, Capacity und Governance.',
            'icon' => 'addons',
            'url' => '/power-platform-kosten-kalkulator',
            'category' => 'Power Platform',
            'status' => 'live',
            'priority' => 1,
        ]);
    }

    private static function register_readonly_suite_matrix_module(): void
    {
        self::register([
            'key' => 'm365-lizenzmatrix',
            'title' => 'M365 Lizenzmatrix',
            'description' => 'Gesamtübersicht der Microsoft-365-Vollpakete von Business Basic bis Microsoft 365 E5.',
            'icon' => 'comparison',
            'url' => '/m365-lizenzmatrix',
            'category' => 'Lizenzen',
            'status' => 'live',
            'priority' => 2,
        ]);
    }

    private static function register_readonly_addon_matrix_module(): void
    {
        self::register([
            'key' => 'm365-addon-matrix',
            'title' => 'M365 Add-on-Matrix',
            'description' => 'Gesamtübersicht der Microsoft-365-Add-ons inklusive Exchange, Teams, Copilot, Intune, Entra ID, Defender, Purview und Power Platform.',
            'icon' => 'addons',
            'url' => '/m365-addon-matrix',
            'category' => 'Lizenzen',
            'status' => 'live',
            'priority' => 4,
        ]);
    }

    private static function register_license_comparison_module(): void
    {
        self::register([
            'key' => 'm365-lizenzvergleich',
            'title' => 'M365-Lizenzvergleich',
            'description' => 'Vergleicht Microsoft-365-Pläne nach Desktop Apps, Mail, Teams, Copilot, Security, Power Platform und Zusatzdiensten.',
            'icon' => 'comparison',
            'url' => '/m365-lizenzvergleich',
            'category' => 'Lizenzen',
            'status' => 'live',
            'priority' => 3,
        ]);
    }

    private static function register_copilot_roi_module(): void
    {
        self::register([
            'key' => 'copilot-roi',
            'title' => 'Copilot ROI-Rechner',
            'description' => 'Berechnet Business Case, Break-even-Minuten und Pilot- oder Rollout-Empfehlung für Microsoft 365 Copilot.',
            'icon' => 'roi',
            'url' => '/copilot-roi-rechner',
            'category' => 'Copilot',
            'status' => 'live',
            'priority' => 15,
        ]);
    }

    private static function register_license_advisor_module(): void
    {
        self::register([
            'key' => 'm365lic',
            'title' => 'M365-Lizenz-Berater',
            'description' => 'Empfiehlt Basislizenzen, Add-ons und Mischmodelle für konkrete Microsoft-365-Anforderungen.',
            'icon' => 'license',
            'url' => '/m365-lizenzberater',
            'category' => 'Lizenzen',
            'status' => 'live',
            'priority' => 5,
        ]);
    }

    private static function register_shared_mailbox_module(): void
    {
        self::register([
            'key' => 'shared-mailbox',
            'title' => 'Shared-Mailbox vs. Lizenz-Rechner',
            'description' => 'Prüft Eignung, Lizenzpflicht und Kostenannahmen für Shared Mailboxes.',
            'icon' => 'mailbox',
            'url' => '/shared-mailbox-vs-lizenz',
            'category' => 'Lizenzen',
            'status' => 'live',
            'priority' => 10,
        ]);
    }

    private static function register_copilot_license_checker_module(): void
    {
        self::register([
            'key' => 'copilot-license-check',
            'title' => 'Copilot Lizenz-Pflicht-Checker',
            'description' => 'Prüft Basislizenz, Copilot-Chat-Status und technische Readiness für Microsoft 365 Copilot.',
            'icon' => 'copilot',
            'url' => '/copilot-lizenz-check',
            'category' => 'Lizenzen',
            'status' => 'live',
            'priority' => 20,
        ]);
    }

    /**
     * @param array<string,mixed> $tool
     * @return array<string,mixed>
     */
    private static function normalize(array $tool): array
    {
        $key = self::clean_key((string) ($tool['key'] ?? ''));
        if ($key === '') {
            return [];
        }

        $status = in_array((string) ($tool['status'] ?? 'soon'), ['live', 'beta', 'soon'], true)
            ? (string) $tool['status']
            : 'soon';

        return [
            'key' => $key,
            'title' => self::limit_text(trim(strip_tags((string) ($tool['title'] ?? $key))), 90),
            'description' => self::limit_text(trim(strip_tags((string) ($tool['description'] ?? ''))), 140),
            'icon' => self::clean_key((string) ($tool['icon'] ?? 'calculator')) ?: 'calculator',
            'url' => self::sanitize_url((string) ($tool['url'] ?? '')),
            'category' => self::limit_text(trim(strip_tags((string) ($tool['category'] ?? 'Weitere Tools'))), 60),
            'status' => $status,
            'priority' => max(0, min(1000, (int) ($tool['priority'] ?? 100))),
        ];
    }

    private static function limit_text(string $value, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }

        return substr($value, 0, $length);
    }

    private static function clean_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($value)), '-');
    }

    private static function sanitize_url(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '/') && !str_starts_with($url, '//') && !str_contains($url, "\0")) {
            return $url;
        }

        $parts = parse_url($url);
        $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';

        if (in_array($scheme, ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return $url;
        }

        return '';
    }

    private static function matrix_plugin_active(): bool
    {
        if (defined('CMS_M365MATRICES_VERSION')) {
            return true;
        }

        if (!class_exists('CMS\\PluginManager')) {
            return false;
        }

        try {
            $manager = \CMS\PluginManager::instance();

            return method_exists($manager, 'isPluginActive') && $manager->isPluginActive('cms-m365matrices');
        } catch (\Throwable $e) {
            return false;
        }
    }
}
