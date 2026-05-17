<?php
/**
 * CMS M365 Tools – Admin Pages.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Admin_Pages
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    public static function render_dashboard(): void
    {
        self::render_with_layout('M365 Tools', 'm365tools-dashboard', static function (): void {
            self::instance()->render_dashboard_page();
        });
    }

    public static function render_plugin_settings(): void
    {
        self::render_with_layout('M365 Tools – Zentrale Einstellungen', 'm365tools-settings', static function (): void {
            self::instance()->render_global_settings_page('settings');
        });
    }

    public static function render_package_prices(): void
    {
        self::render_with_layout('M365 Tools – Paketpreise', 'm365tools-package-prices', static function (): void {
            self::instance()->render_global_settings_page('package-prices');
        });
    }

    public static function render_subscription_prices(): void
    {
        self::render_with_layout('M365 Tools – Abopreise & Laufzeiten', 'm365tools-subscription-prices', static function (): void {
            self::instance()->render_global_settings_page('subscription-prices');
        });
    }

    public static function render_module_settings(string $moduleKey): void
    {
        $key = self::clean_key($moduleKey);
        $tool = CMS_M365CALCULATOR_Tool_Registry::tools(false)[$key] ?? null;
        $title = is_array($tool) ? (string) ($tool['title'] ?? $key) : 'Modul';

        self::render_with_layout($title . ' – Einstellungen', 'm365tools-module-' . $key, static function () use ($key): void {
            self::instance()->render_module_settings_page($key);
        });
    }

    private static function render_with_layout(string $title, string $slug, callable $renderer): void
    {
        self::check_access();
        self::load_admin_menu();

        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, $slug);
        }

        self::enqueue_admin_assets();
        echo '<div class="m365calculator-admin-shell">';
        $renderer();
        echo '</div>';

        if (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
    }

    private static function check_access(): void
    {
        if (!class_exists('CMS\\Auth') || !\CMS\Auth::instance()->isAdmin()) {
            header('Location: ' . (defined('SITE_URL') ? SITE_URL : '/'));
            exit;
        }
    }

    private static function load_admin_menu(): void
    {
        $menuFile = ABSPATH . 'admin/partials/admin-menu.php';
        if (file_exists($menuFile) && !function_exists('renderAdminLayoutStart')) {
            require_once $menuFile;
        }
    }

    private static function enqueue_admin_assets(): void
    {
        $css = CMS_M365CALCULATOR_PLUGIN_DIR . 'assets/css/m365calculator-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_M365CALCULATOR_PLUGIN_URL . 'assets/css/m365calculator-admin.css', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    public function render_dashboard_page(): void
    {
        $notice = '';
        $error = '';
        $csrfToken = class_exists('CMS\\Security')
            ? \CMS\Security::instance()->generateToken('m365tools_admin_modules')
            : '';

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'save_module_settings') {
            if (class_exists('CMS\\Security') && !\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'm365tools_admin_modules')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                CMS_M365CALCULATOR_Settings::save_module_settings($_POST);
                $notice = 'Moduleinstellungen gespeichert.';
            }
        }

        $allTools = CMS_M365CALCULATOR_Tool_Registry::ordered_tools(false);
        $tools = CMS_M365CALCULATOR_Tool_Registry::ordered_tools(true);
        $moduleSettings = CMS_M365CALCULATOR_Settings::settings_for_admin(CMS_M365CALCULATOR_Tool_Registry::tools(false));
        $rules = CMS_M365CALCULATOR_Catalog::rules();
        $licenseMatrix = CMS_M365CALCULATOR_Catalog::license_matrix();
        ?>
        <div class="admin-page-header">
            <div>
                <h2>🧮 M365 Tools</h2>
                <p>Modulare Toolbox für Lizenz-, Kosten- und Governance-Rechner.</p>
            </div>
            <div class="header-actions">
                <a href="/m365-tools" class="btn btn-primary" target="_blank" rel="noopener noreferrer">👁️ Toolbox öffnen</a>
            </div>
        </div>

        <?php if ($notice !== ''): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
        <div class="alert alert-error">❌ <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="dashboard-grid m365calculator-admin-grid">
            <div class="stat-card">
                <div class="stat-icon">🧩</div>
                <div class="stat-number"><?php echo (int) count($allTools); ?></div>
                <div class="stat-label">Module</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?php echo (int) count(array_filter($tools, static fn(array $tool): bool => ($tool['status'] ?? '') === 'live')); ?></div>
                <div class="stat-label">Aktiv</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📏</div>
                <div class="stat-number"><?php echo (int) ($rules['limits']['max_unlicensed_storage_gb'] ?? 50); ?> GB</div>
                <div class="stat-label">Lizenzfrei</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-number"><?php echo (int) count($licenseMatrix); ?></div>
                <div class="stat-label">Lizenzprofile</div>
            </div>
        </div>

        <div class="admin-card">
            <h3>📋 Modulübersicht</h3>
            <p class="m365calculator-admin-muted">Steuere je Modul Sichtbarkeit, Status, Reihenfolge und öffentliche Texte. Leere Textfelder nutzen den Registry-Standard.</p>
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="save_module_settings">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="users-table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Modul</th>
                            <th>Aktiv</th>
                            <th>Status</th>
                            <th>Sortierung</th>
                            <th>URL</th>
                            <th>Beschreibung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allTools as $tool): ?>
                        <?php
                        $moduleKey = (string) ($tool['key'] ?? '');
                        $settings = $moduleSettings[$moduleKey] ?? [];
                        $statusValue = (string) ($settings['status_override'] ?? ($tool['status'] ?? 'live'));
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars((string) ($tool['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <input type="hidden" name="modules[<?php echo htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8'); ?>][module_key]" value="<?php echo htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8'); ?>">
                                <label class="m365calculator-admin-stack">
                                    Titel-Override
                                    <input class="m365calculator-admin-control" type="text" maxlength="90" name="modules[<?php echo htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8'); ?>][title_override]" value="<?php echo htmlspecialchars((string) ($settings['title_override'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars((string) ($tool['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </label>
                            </td>
                            <td>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="modules[<?php echo htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8'); ?>][is_enabled]" value="1"<?php echo (int) ($settings['is_enabled'] ?? 1) === 1 ? ' checked' : ''; ?>>
                                    Sichtbar
                                </label>
                            </td>
                            <td>
                                <select class="m365calculator-admin-control" name="modules[<?php echo htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8'); ?>][status_override]">
                                    <?php foreach (['live' => 'Live', 'beta' => 'Beta', 'soon' => 'Bald'] as $status => $label): ?>
                                    <option value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $statusValue === $status ? ' selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input class="m365calculator-admin-control m365calculator-admin-priority" type="number" min="0" max="1000" name="modules[<?php echo htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8'); ?>][priority_override]" value="<?php echo (int) ($settings['priority_override'] ?? ($tool['priority'] ?? 100)); ?>"></td>
                            <td><code><?php echo htmlspecialchars((string) ($tool['url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td>
                                <textarea class="m365calculator-admin-control" rows="3" maxlength="140" name="modules[<?php echo htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8'); ?>][description_override]" placeholder="<?php echo htmlspecialchars((string) ($tool['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($settings['description_override'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
                <button type="submit" class="btn btn-primary">💾 Moduleinstellungen speichern</button>
            </form>
        </div>
        <?php
    }

    public function render_module_settings_page(string $moduleKey): void
    {
        $moduleKey = self::clean_key($moduleKey);
        $tool = CMS_M365CALCULATOR_Tool_Registry::tools(false)[$moduleKey] ?? null;
        if (!is_array($tool)) {
            echo '<div class="admin-card"><h3>⚠️ Modul nicht gefunden</h3><p>Dieses Modul ist nicht in der Registry vorhanden.</p></div>';
            return;
        }

        $notice = '';
        $error = '';
        $tabs = CMS_M365CALCULATOR_Admin_Module_Config::tabs_for($tool);
        $activeTab = self::normalize_tab((string) ($_GET['tab'] ?? $_POST['settings_group'] ?? 'overview'), $tabs);
        $csrfAction = 'm365tools_module_' . $moduleKey;
        $csrfToken = class_exists('CMS\\Security')
            ? \CMS\Security::instance()->generateToken($csrfAction)
            : '';

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (class_exists('CMS\\Security') && !\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', $csrfAction)) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $action = (string) ($_POST['action'] ?? '');
                if ($action === 'save_module_display') {
                    CMS_M365CALCULATOR_Settings::save_single_module_settings($moduleKey, $_POST);
                    $notice = 'Anzeigeeinstellungen gespeichert.';
                    $activeTab = 'display';
                } elseif ($action === 'save_module_options') {
                    $activeTab = self::normalize_tab((string) ($_POST['settings_group'] ?? $activeTab), $tabs);
                    $fields = CMS_M365CALCULATOR_Admin_Module_Config::fields_for($tool, $activeTab);
                    CMS_M365CALCULATOR_Settings::save_module_options($moduleKey, $activeTab, self::sanitize_module_options($fields, $_POST));
                    $notice = 'Moduleinstellungen gespeichert.';
                }
            }
        }

        $moduleSettings = CMS_M365CALCULATOR_Settings::settings_for_admin([$moduleKey => $tool])[$moduleKey] ?? [];
        $tabOptions = CMS_M365CALCULATOR_Settings::module_options($moduleKey, $activeTab);
        $fields = CMS_M365CALCULATOR_Admin_Module_Config::fields_for($tool, $activeTab);
        $moduleAdminUrl = '/admin/plugins/m365tools-dashboard/m365tools-module-' . rawurlencode($moduleKey);

        include CMS_M365CALCULATOR_PLUGIN_DIR . 'admin/views/page-module-settings.php';
    }

    public function render_global_settings_page(string $area): void
    {
        $area = self::clean_key($area);
        $tabs = self::global_tabs_for($area);
        $defaultTab = self::default_global_tab($area);
        $activeTab = self::normalize_tab((string) ($_GET['tab'] ?? $_POST['settings_group'] ?? $defaultTab), $tabs, $defaultTab);
        $csrfAction = 'm365tools_global_' . $area;
        $csrfToken = class_exists('CMS\\Security')
            ? \CMS\Security::instance()->generateToken($csrfAction)
            : '';
        $notice = '';
        $error = '';

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (class_exists('CMS\\Security') && !\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', $csrfAction)) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } elseif ((string) ($_POST['action'] ?? '') === 'save_global_options') {
                $activeTab = self::normalize_tab((string) ($_POST['settings_group'] ?? $activeTab), $tabs, $defaultTab);
                $fields = self::global_fields_for($area, $activeTab);
                CMS_M365CALCULATOR_Settings::save_global_options($activeTab, self::sanitize_module_options($fields, $_POST));
                $notice = 'Globale Einstellungen gespeichert.';
            }
        }

        $fields = self::global_fields_for($area, $activeTab);
        $tabOptions = CMS_M365CALCULATOR_Settings::global_options($activeTab);
        $pageMeta = self::global_page_meta($area);
        $pageTitle = $pageMeta['title'];
        $pageDescription = $pageMeta['description'];
        $baseAdminUrl = '/admin/plugins/m365tools-dashboard/' . self::global_slug_for($area);

        include CMS_M365CALCULATOR_PLUGIN_DIR . 'admin/views/page-global-settings.php';
    }

    /**
     * @param array<string,string> $tabs
     */
    private static function normalize_tab(string $tab, array $tabs, string $fallback = 'overview'): string
    {
        $tab = self::clean_key($tab);

        if (isset($tabs[$tab])) {
            return $tab;
        }

        if (isset($tabs[$fallback])) {
            return $fallback;
        }

        $first = array_key_first($tabs);

        return is_string($first) ? $first : 'overview';
    }

    /**
     * @return array<string,string>
     */
    private static function global_tabs_for(string $area): array
    {
        return match ($area) {
            'package-prices' => [
                'base-packages' => '📦 M365-Pakete',
                'addons' => '➕ Add-ons',
                'price-rules' => '🧮 Preisregeln',
            ],
            'subscription-prices' => [
                'terms' => '🔁 Laufzeiten',
                'commitment' => '📅 Commitments',
                'billing' => '🧾 Abrechnung',
            ],
            default => [
                'general' => '⚙️ Allgemein',
                'review' => '🧭 Review & Quellen',
                'workflow' => '🔁 Workflow',
                'system' => '🧾 System',
            ],
        };
    }

    private static function default_global_tab(string $area): string
    {
        return match ($area) {
            'package-prices' => 'base-packages',
            'subscription-prices' => 'terms',
            default => 'general',
        };
    }

    /**
     * @return array<string,string>
     */
    private static function global_page_meta(string $area): array
    {
        return match ($area) {
            'package-prices' => [
                'title' => '💶 Paketpreise',
                'description' => 'Globale Paket- und Add-on-Preise als zentrale Basis für alle Module pflegen.',
            ],
            'subscription-prices' => [
                'title' => '🔁 Abopreise & Laufzeiten',
                'description' => 'Laufzeit-, Renewal- und Abrechnungsannahmen zentral für alle Rechner steuern.',
            ],
            default => [
                'title' => '⚙️ Zentrale Einstellungen',
                'description' => 'Pluginweite Defaults, Quellen-Reviews und Betriebsregeln an einer Stelle verwalten.',
            ],
        };
    }

    private static function global_slug_for(string $area): string
    {
        return match ($area) {
            'package-prices' => 'm365tools-package-prices',
            'subscription-prices' => 'm365tools-subscription-prices',
            default => 'm365tools-settings',
        };
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function global_fields_for(string $area, string $tab): array
    {
        if ($area === 'package-prices') {
            return self::package_price_fields($tab);
        }

        if ($area === 'subscription-prices') {
            return self::subscription_price_fields($tab);
        }

        return self::plugin_setting_fields($tab);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function plugin_setting_fields(string $tab): array
    {
        return match ($tab) {
            'review' => [
                self::text('last_global_review_date', 'Letzter Quellenabgleich', date('Y-m-d'), 'Datum des letzten fachlichen All-Module-Reviews.'),
                self::select('primary_source_profile', 'Primäres Quellenprofil', 'microsoft_learn', [
                    'microsoft_learn' => 'Microsoft Learn / Service Description',
                    'admin_center' => 'Microsoft Admin Center / Reports',
                    'partner_contract' => 'CSP-, Partner- oder Vertragsdaten',
                    'mixed' => 'Gemischte Quellenbasis',
                ], 'Zentrale Einordnung für Quellen- und Preisstände.'),
                self::number('default_review_interval_days', 'Standard-Review-Intervall in Tagen', '90', 7, 730, 1, 'Empfohlener Rhythmus für Preis-, Sicherheits- und Performance-Reviews.'),
                self::select('endpoint_update_cycle', 'Endpoint-Pflege', 'version_hourly', [
                    'manual_monthly' => 'Monatliche manuelle Prüfung',
                    'version_hourly' => 'Versionsprüfung stündlich, Daten nur bei Änderung',
                    'partner_managed' => 'Durch SD-WAN/Partnerlösung gepflegt',
                ], 'Angelehnt an Microsofts Endpoint-Webservice-Empfehlung.'),
                self::select('usage_report_window', 'Standard-Reportfenster', '90', [
                    '7' => '7 Tage',
                    '30' => '30 Tage',
                    '90' => '90 Tage',
                    '180' => '180 Tage',
                ], 'Standardfenster für Nutzungs- und Plausibilitätsberichte.'),
                self::textarea('review_note', 'Interne Review-Notiz', '', 'Kurznotiz zu Quellen, Learn-Updates oder Preislistenabgleich.'),
            ],
            'workflow' => [
                self::select('default_owner_role', 'Standard-Owner', 'license_manager', [
                    'license_manager' => 'Lizenzmanagement',
                    'finance' => 'Finanzen/Einkauf',
                    'it_ops' => 'IT Operations',
                    'security' => 'Security/Compliance',
                    'admin' => 'CMS Admin',
                ], 'Pluginweite Standardverantwortung.'),
                self::select('default_publication_mode', 'Veröffentlichungsmodus', 'reviewed', [
                    'reviewed' => 'Geprüfte Änderungen veröffentlichen',
                    'draft_first' => 'Änderungen zuerst intern vorbereiten',
                    'locked' => 'Nur zentrale Pflege erlauben',
                ], 'Grundlogik für fachliche Änderungen.'),
                self::checkbox('require_price_owner_note', 'Preisnotiz bei Änderungen erwarten', '1', 'Markiert Preisänderungen intern als begründungspflichtig.'),
                self::checkbox('prefer_group_license_review', 'Gruppenbasierte Lizenzprüfung bevorzugen', '1', 'Erinnert Admins an gruppenbasierte Zuweisungen und Fehlerlisten.'),
                self::checkbox('prefer_pilot_rollouts', 'Pilot- und Phasenrollouts bevorzugen', '1', 'Standardannahme für Copilot, Conditional Access und größere Lizenzänderungen.'),
                self::textarea('workflow_note', 'Workflow-Notiz', '', 'Interne Hinweise zu Freigaben, Rollen und Review-Ablauf.'),
            ],
            'system' => [
                self::text('plugin_version_reference', 'Versionsreferenz', CMS_M365CALCULATOR_VERSION, 'Aktuelle Pluginversion als Dokumentationsanker.'),
                self::text('docs_root', 'Dokumentationspfad', 'M365-PLUGINS/DOC', 'Verschobener zentraler Dokumentationsordner.'),
                self::text('public_hub_url', 'Public Hub URL', '/m365-tools', 'Öffentliche Landingpage der Toolbox.'),
                self::text('support_contact', 'Interner Ansprechpartner', '', 'Optionaler Kontakt für Pflege und Eskalation.'),
                self::textarea('system_note', 'Systemhinweis', '', 'Technischer Hinweis zu Deployment, Datenpflege oder Abhängigkeiten.'),
            ],
            default => [
                self::select('currency', 'Standardwährung', 'EUR', ['EUR' => 'EUR', 'CHF' => 'CHF', 'USD' => 'USD', 'GBP' => 'GBP'], 'Gilt als Default für zentrale Preisbereiche.'),
                self::text('market', 'Markt / Region', 'DE', 'Marktkennzeichen für Preis- und Quellenannahmen.'),
                self::checkbox('show_global_source_hints', 'Quellenhinweise standardmäßig anzeigen', '1', 'Default für gepflegte Quellenhinweise in Modulen.'),
                self::checkbox('enable_public_landing_checks', 'Best-Practice-Kompass auf Landingpage anzeigen', '1', 'Steuert den sichtbaren Review-Kontext auf der Toolbox-Landingpage.'),
                self::number('default_admin_buffer_percent', 'Standard-Betriebspuffer in %', '10', 0, 200, 0.1, 'Globaler Puffer für Betriebs-, Review- oder Beschaffungskosten.'),
                self::textarea('general_note', 'Allgemeine Notiz', '', 'Interne Notiz zur Gesamt-Toolbox.'),
            ],
        };
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function package_price_fields(string $tab): array
    {
        return match ($tab) {
            'addons' => [
                self::number('exchange_online_plan_1_eur', 'Exchange Online Plan 1', '3.50', 0, 1000, 0.01, 'Globaler Referenzpreis pro Nutzer/Monat.'),
                self::number('exchange_online_plan_2_eur', 'Exchange Online Plan 2', '6.90', 0, 1000, 0.01, 'Globaler Referenzpreis pro Nutzer/Monat.'),
                self::number('defender_office_365_p1_eur', 'Defender for Office 365 Plan 1', '1.73', 0, 1000, 0.01, 'Globaler Add-on-Referenzpreis.'),
                self::number('defender_office_365_p2_eur', 'Defender for Office 365 Plan 2', '4.30', 0, 1000, 0.01, 'Globaler Add-on-Referenzpreis.'),
                self::number('teams_phone_standard_eur', 'Teams Phone Standard', '8.70', 0, 1000, 0.01, 'Telefonie-Add-on ohne Verbrauchsminuten.'),
                self::number('m365_copilot_eur', 'Microsoft 365 Copilot Add-on', '28.10', 0, 1000, 0.01, 'Globaler Copilot-Referenzpreis pro Nutzer/Monat.'),
                self::number('m365_backup_per_gb_eur', 'Microsoft 365 Backup pro GB', '0.15', 0, 100, 0.001, 'PAYG-Annahme pro geschütztem GB/Monat.'),
            ],
            'price-rules' => [
                self::number('default_price_adjustment_percent', 'Globale Preisanpassung in %', '0', -80, 300, 0.1, 'Aufschlag oder Rabatt auf zentrale Referenzpreise.'),
                self::number('partner_discount_percent', 'Partner-/Rahmenvertragsrabatt in %', '0', 0, 90, 0.1, 'Optionaler zentraler Rabatt vor Modulberechnung.'),
                self::number('risk_buffer_percent', 'Budgetpuffer in %', '10', 0, 200, 0.1, 'Puffer für Preisereignisse, Wechselkurse oder Packaging-Änderungen.'),
                self::select('price_source_type', 'Preisquelle', 'reference_json', [
                    'reference_json' => 'Gepflegte JSON-Referenz',
                    'csp_export' => 'CSP-/Partner-Export',
                    'contract' => 'Rahmenvertrag',
                    'manual' => 'Manuelle Annahme',
                ], 'Kennzeichnet die bevorzugte globale Preisquelle.'),
                self::text('price_source_date', 'Preisstand', date('Y-m-d'), 'Datum des letzten Preisabgleichs.'),
                self::textarea('price_source_note', 'Preisnotiz', '', 'Interne Notiz zu Preislisten, Vertragsständen oder Abweichungen.'),
            ],
            default => [
                self::number('m365_business_basic_eur', 'Microsoft 365 Business Basic', '5.20', 0, 1000, 0.01, 'Globaler Paketpreis pro Nutzer/Monat.'),
                self::number('m365_business_standard_eur', 'Microsoft 365 Business Standard', '10.80', 0, 1000, 0.01, 'Globaler Paketpreis pro Nutzer/Monat.'),
                self::number('m365_business_premium_eur', 'Microsoft 365 Business Premium', '19.10', 0, 1000, 0.01, 'Globaler Paketpreis pro Nutzer/Monat.'),
                self::number('office_365_e3_eur', 'Office 365 E3', '23.20', 0, 1000, 0.01, 'Globaler Paketpreis pro Nutzer/Monat.'),
                self::number('m365_e3_eur', 'Microsoft 365 E3', '34.90', 0, 1000, 0.01, 'Globaler Paketpreis pro Nutzer/Monat.'),
                self::number('m365_e5_eur', 'Microsoft 365 E5', '57.00', 0, 1000, 0.01, 'Globaler Paketpreis pro Nutzer/Monat.'),
            ],
        };
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function subscription_price_fields(string $tab): array
    {
        return match ($tab) {
            'commitment' => [
                self::number('stable_core_target_percent', 'Stabiler Kernbestand in %', '80', 0, 100, 0.1, 'Zielwert für Seats, die langfristig gebunden werden können.'),
                self::number('seasonal_buffer_percent', 'Flexibler Saison-/Projektpuffer in %', '20', 0, 100, 0.1, 'Anteil für Monatslaufzeit oder kurzfristige Anpassung.'),
                self::number('renewal_review_days', 'Renewal-Review vor Ablauf in Tagen', '90', 1, 365, 1, 'Vorlauf für Preis-, Seat- und Paketprüfung.'),
                self::number('license_group_batch_limit', 'Gruppen je Lizenzvorgang', '20', 1, 100, 1, 'Admin-Center-Richtwert für gruppenbasierte Aktionen.'),
                self::checkbox('track_assignment_issues', 'Zuweisungsprobleme nachhalten', '1', 'Erinnert an Auswertung der Fehler- und Problemübersicht.'),
                self::textarea('commitment_note', 'Commitment-Notiz', '', 'Interne Notiz zu Kernbestand, Projektspitzen und Renewal-Fenstern.'),
            ],
            'billing' => [
                self::select('default_billing_model', 'Standard-Abrechnungsmodell', 'monthly_per_user', [
                    'monthly_per_user' => 'Monatlich pro Nutzer',
                    'annual_paid_monthly' => 'Jährlich gebunden, monatlich bezahlt',
                    'annual_prepaid' => 'Jährlich im Voraus',
                    'pay_as_you_go' => 'Verbrauchsbasiert',
                ], 'Default für Rechnerszenarien.'),
                self::select('rounding_mode', 'Rundung', 'commercial_2_decimals', [
                    'commercial_2_decimals' => 'Kaufmännisch auf 2 Stellen',
                    'ceil_cent' => 'Cent aufrunden',
                    'whole_euro' => 'Volle Euro anzeigen',
                ], 'Darstellung globaler Preisannahmen.'),
                self::select('tax_handling', 'Steuerdarstellung', 'net', [
                    'net' => 'Netto',
                    'gross' => 'Brutto',
                    'both' => 'Netto und Brutto',
                ], 'Standarddarstellung für Admin-Kalkulationen.'),
                self::number('tax_percent', 'Steuersatz in %', '19', 0, 100, 0.1, 'Optionaler Steuersatz für interne Vergleiche.'),
                self::textarea('billing_note', 'Abrechnungsnotiz', '', 'Hinweis zu PAYG, CSP, Rahmenvertrag oder interner Kostenstelle.'),
            ],
            default => [
                self::number('monthly_uplift_percent', 'Monatslaufzeit-Aufschlag in %', '20', 0, 200, 0.1, 'Globaler Standardaufschlag für flexible Laufzeit.'),
                self::number('annual_discount_percent', 'Jahresbindungs-Rabatt in %', '0', 0, 90, 0.1, 'Optionaler Rabatt für Jahresbindung.'),
                self::number('three_year_discount_percent', 'Dreijahres-Rabatt in %', '0', 0, 90, 0.1, 'Optionaler Rabatt für geeignete Enterprise-Szenarien.'),
                self::number('three_year_min_users', 'Mindestmenge Dreijahrespfad', '100', 0, 1000000, 1, 'Interner Richtwert für P3Y-Prüfung.'),
                self::select('default_term', 'Standardlaufzeit', 'P1Y', [
                    'P1M' => 'Monatlich',
                    'P1Y' => 'Jährlich',
                    'P3Y' => 'Drei Jahre',
                ], 'Default für Rechner, wenn kein Szenario abweicht.'),
                self::textarea('term_note', 'Laufzeitnotiz', '', 'Interne Notiz zu Annahmen, Kanal oder Beschaffungsregeln.'),
            ],
        };
    }

    /**
     * @return array<string,mixed>
     */
    private static function text(string $key, string $label, string $default, string $help): array
    {
        return ['key' => $key, 'label' => $label, 'type' => 'text', 'default' => $default, 'help' => $help];
    }

    /**
     * @return array<string,mixed>
     */
    private static function textarea(string $key, string $label, string $default, string $help): array
    {
        return ['key' => $key, 'label' => $label, 'type' => 'textarea', 'default' => $default, 'help' => $help];
    }

    /**
     * @param array<string,string> $options
     * @return array<string,mixed>
     */
    private static function select(string $key, string $label, string $default, array $options, string $help): array
    {
        return ['key' => $key, 'label' => $label, 'type' => 'select', 'default' => $default, 'options' => $options, 'help' => $help];
    }

    /**
     * @return array<string,mixed>
     */
    private static function checkbox(string $key, string $label, string $default, string $help): array
    {
        return ['key' => $key, 'label' => $label, 'type' => 'checkbox', 'default' => $default, 'help' => $help];
    }

    /**
     * @return array<string,mixed>
     */
    private static function number(string $key, string $label, string $default, float $min, float $max, float $step, string $help): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'type' => 'number',
            'default' => $default,
            'min' => $min,
            'max' => $max,
            'step' => $step,
            'help' => $help,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $fields
     * @param array<string,mixed> $posted
     * @return array<string,string>
     */
    private static function sanitize_module_options(array $fields, array $posted): array
    {
        $options = [];

        foreach ($fields as $field) {
            $key = self::clean_key((string) ($field['key'] ?? ''));
            if ($key === '') {
                continue;
            }

            $type = (string) ($field['type'] ?? 'text');
            if ($type === 'checkbox') {
                $options[$key] = !empty($posted[$key]) ? '1' : '0';
                continue;
            }

            $raw = (string) ($posted[$key] ?? ($field['default'] ?? ''));
            if ($type === 'number') {
                $number = is_numeric($raw) ? (float) $raw : (float) ($field['default'] ?? 0);
                $min = (float) ($field['min'] ?? -1000000);
                $max = (float) ($field['max'] ?? 1000000);
                $formatted = rtrim(rtrim((string) max($min, min($max, $number)), '0'), '.');
                $options[$key] = $formatted !== '' ? $formatted : '0';
                continue;
            }

            if ($type === 'select') {
                $allowed = is_array($field['options'] ?? null) ? array_keys($field['options']) : [];
                $options[$key] = in_array($raw, $allowed, true) ? $raw : (string) ($field['default'] ?? '');
                continue;
            }

            $limit = $type === 'textarea' ? 2000 : 255;
            $options[$key] = self::limit_text(trim(strip_tags($raw)), $limit);
        }

        return $options;
    }

    private static function clean_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($value)), '-');
    }

    private static function limit_text(string $value, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }

        return substr($value, 0, $length);
    }
}
