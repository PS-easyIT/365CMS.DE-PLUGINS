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

    public static function render_landing_designer(): void
    {
        self::render_with_layout('M365 Tools – Landingpage Designer', 'm365tools-landing-designer', static function (): void {
            self::instance()->render_global_settings_page('landing-designer');
        });
    }

    public static function render_readonly_matrices(): void
    {
        self::render_with_layout('M365 Tools – Read-only Matrixen', 'm365tools-readonly-matrices', static function (): void {
            self::instance()->render_global_settings_page('readonly-matrices');
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
        if ($area === 'landing-designer') {
            $tabOptions = array_merge(CMS_M365CALCULATOR_Settings::global_options('landing'), $tabOptions);
        }
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
            'landing-designer' => [
                'landing-content' => '✍️ Contentheader',
                'landing-layout' => '🧱 Layouts & Boxen',
                'landing-colors' => '🎨 Farben',
                'landing-visibility' => '👁️ Sichtbarkeit',
            ],
            'readonly-matrices' => [
                'matrix-suite' => '📊 Lizenzmatrix',
                'matrix-addon' => '➕ Add-on-Matrix',
                'matrix-design' => '🎨 Design',
            ],
            default => [
                'general' => '⚙️ Allgemein',
                'provider' => '🏢 Dienstleister & Kontakt',
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
            'landing-designer' => 'landing-content',
            'readonly-matrices' => 'matrix-suite',
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
            'landing-designer' => [
                'title' => '🎨 Landingpage Designer',
                'description' => 'Hub-Content, Layouts, Farben, Boxen und sichtbare Bereiche der Public-Landingpage gestalten.',
            ],
            'readonly-matrices' => [
                'title' => '📚 Read-only Matrixen',
                'description' => 'Lizenz- und Add-on-Matrix gemeinsam pflegen, gestalten und Außenbereiche steuern.',
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
            'landing-designer' => 'm365tools-landing-designer',
            'readonly-matrices' => 'm365tools-readonly-matrices',
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

        if ($area === 'landing-designer') {
            return self::landing_designer_fields($tab);
        }

        if ($area === 'readonly-matrices') {
            return self::readonly_matrix_fields($tab);
        }

        return self::plugin_setting_fields($tab);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function readonly_matrix_fields(string $tab): array
    {
        return match ($tab) {
            'matrix-addon' => [
                self::text('matrix_addon_overline', 'Header-Overline', 'Add-on-Matrix', 'Kleine Zeile oberhalb der Add-on-Matrix-Überschrift.'),
                self::text('matrix_addon_title', 'Header-Titel', 'Microsoft 365 Add-on-Matrix', 'Hauptüberschrift der Add-on-Matrix.'),
                self::textarea('matrix_addon_intro', 'Header-Intro', 'Öffentliche Übersicht aller Add-on-Bereiche: Exchange, SharePoint, OneDrive, Teams Phone, Copilot, Security, Power Platform und Spezialdienste.', 'Einleitungstext im Contentheader.'),
                self::text('matrix_addon_secondary_button_label', 'Sekundärbutton Text', 'Vollpaket-Matrix öffnen', 'Beschriftung des sekundären Header-Buttons.'),
                self::text('matrix_addon_secondary_button_url', 'Sekundärbutton Ziel', '/m365-lizenzmatrix', 'Interne Route oder vollständige URL.'),
                self::text('matrix_addon_tool_button_label', 'Weiterer Button Text', 'Add-On-Konfigurator öffnen', 'Beschriftung des zweiten Header-Buttons.'),
                self::text('matrix_addon_tool_button_url', 'Weiterer Button Ziel', '/m365-add-on-konfigurator', 'Interne Route oder vollständige URL.'),
                self::text('matrix_addon_result_overline', 'Matrix-Overline', 'Matrix', 'Kleine Zeile über dem Matrixbereich.'),
                self::text('matrix_addon_result_title', 'Matrix-Titel', 'Gesamtübersicht der Microsoft-365-Add-ons', 'Überschrift vor den Add-on-Bereichen.'),
                self::textarea('matrix_addon_result_intro', 'Matrix-Intro', 'Die wichtigsten Add-ons mit Größen, Voraussetzungen, Abgrenzungen und typischen Kaufgründen.', 'Beschreibung oberhalb der Add-on-Bereiche.'),
                self::text('matrix_addon_primary_button_label', 'CTA-Button Text', 'Lizenzcheck anfragen', 'Beschriftung des primären CTA-Buttons.'),
                self::text('matrix_addon_primary_button_url', 'CTA-Button Ziel', '/kontakt', 'Kontaktformular, Beratungsseite oder interne Route.'),
                self::checkbox('matrix_addon_show_hero', 'Contentheader anzeigen', '1', 'Blendet den oberen Contentheader ein.'),
                self::checkbox('matrix_addon_show_hero_buttons', 'Header-Buttons anzeigen', '1', 'Blendet die Buttons im Contentheader ein.'),
                self::checkbox('matrix_addon_show_result_header', 'Einleitungsbereich vor Matrix anzeigen', '1', 'Blendet den kurzen Matrix-Introbereich ein.'),
                self::checkbox('matrix_addon_show_print_button', 'Drucken-Button anzeigen', '1', 'Zeigt den PDF-/Drucken-Button im Introbereich.'),
                self::checkbox('matrix_addon_show_primary_cta', 'CTA-Button anzeigen', '1', 'Zeigt den Kontakt- oder Beratungsbutton im Introbereich.'),
                self::checkbox('matrix_addon_show_area_headers', 'Bereichsheader anzeigen', '1', 'Zeigt Überschrift und Beschreibung je Add-on-Bereich.'),
                self::checkbox('matrix_addon_show_package_cards', 'Paketkarten anzeigen', '1', 'Zeigt die kleinen Paketkarten oberhalb jeder Add-on-Tabelle.'),
                self::checkbox('matrix_addon_show_notes', 'Hinweise anzeigen', '1', 'Zeigt den Hinweisblock unterhalb der Matrix.'),
                self::checkbox('matrix_addon_show_sources', 'Quellenstand anzeigen', '1', 'Zeigt den Quellenblock unterhalb der Matrix.'),
            ],
            'matrix-design' => [
                self::checkbox('matrix_show_hero', 'Contentheader standardmäßig anzeigen', '1', 'Globaler Default für den Contentheader beider Matrixseiten.'),
                self::checkbox('matrix_show_hero_buttons', 'Header-Buttons standardmäßig anzeigen', '1', 'Globaler Default für Buttons im Contentheader.'),
                self::checkbox('matrix_show_result_header', 'Einleitungsbereich vor Matrix standardmäßig anzeigen', '1', 'Globaler Default für den Bereich direkt oberhalb der Matrix.'),
                self::checkbox('matrix_show_print_button', 'Drucken-Button standardmäßig anzeigen', '1', 'Globaler Default für die PDF-/Drucken-Aktion.'),
                self::checkbox('matrix_show_primary_cta', 'CTA-Button standardmäßig anzeigen', '1', 'Globaler Default für Kontakt- oder Beratungsaktionen.'),
                self::checkbox('matrix_show_notes', 'Hinweise standardmäßig anzeigen', '1', 'Globaler Default für Hinweisbereiche außerhalb der Matrix.'),
                self::checkbox('matrix_show_sources', 'Quellenstand standardmäßig anzeigen', '1', 'Globaler Default für Quellenbereiche außerhalb der Matrix.'),
                self::checkbox('matrix_show_addon_area_headers', 'Add-on-Bereichsheader standardmäßig anzeigen', '1', 'Globaler Default für Überschriften je Add-on-Bereich.'),
                self::checkbox('matrix_show_addon_package_cards', 'Add-on-Paketkarten standardmäßig anzeigen', '1', 'Globaler Default für Paketkarten oberhalb der Add-on-Tabellen.'),
                self::select('matrix_header_style', 'Contentheader-Stil', 'plain', [
                    'plain' => 'Schlicht',
                    'surface' => 'Ruhige Fläche',
                    'bordered' => 'Gerahmt',
                    'accent' => 'Akzentkante',
                    'inverted' => 'Dunkel / invertiert',
                ], 'Optik des Matrix-Contentheaders.'),
                self::select('matrix_header_alignment', 'Header-Ausrichtung', 'split', [
                    'split' => 'Text links, Aktionen rechts',
                    'left' => 'Links ausgerichtet',
                    'center' => 'Zentriert',
                ], 'Ausrichtung von Headertexten und Aktionen.'),
                self::select('matrix_button_layout', 'Button-Layout', 'inline', [
                    'inline' => 'Nebeneinander',
                    'stacked' => 'Untereinander',
                    'right' => 'Rechts ausgerichtet',
                ], 'Layout der Header- und Matrix-Aktionen.'),
                self::select('matrix_button_style', 'Button-Stil', 'default', [
                    'default' => 'Theme-Standard',
                    'primary' => 'Alle Aktionen primär betonen',
                    'secondary' => 'Alle Aktionen ruhig darstellen',
                    'minimal' => 'Minimal / textnah',
                ], 'Optische Gewichtung der Matrix-Buttons.'),
                self::number('matrix_header_radius', 'Header-Rundung in px', '8', 0, 24, 1, 'Rundung für flächige oder gerahmte Header.'),
                self::color('matrix_color_header_background', 'Header-Hintergrund', '#f8fafc', 'Hintergrundfarbe für flächige Header.'),
                self::color('matrix_color_header_text', 'Header-Text', '#1e293b', 'Textfarbe im Contentheader.'),
                self::color('matrix_color_header_muted', 'Header-Sekundärtext', '#64748b', 'Farbe für Overline und Beschreibung.'),
                self::color('matrix_color_header_border', 'Header-Rahmen', '#e2e8f0', 'Rahmen- und Akzentfarbe im Header.'),
                self::color('matrix_color_primary_button_bg', 'Primärbutton Hintergrund', '#2563eb', 'Hintergrundfarbe für primäre Matrix-Aktionen.'),
                self::color('matrix_color_primary_button_text', 'Primärbutton Text', '#ffffff', 'Textfarbe für primäre Matrix-Aktionen.'),
                self::color('matrix_color_secondary_button_bg', 'Sekundärbutton Hintergrund', '#ffffff', 'Hintergrundfarbe für sekundäre Matrix-Aktionen.'),
                self::color('matrix_color_secondary_button_text', 'Sekundärbutton Text', '#1e293b', 'Textfarbe für sekundäre Matrix-Aktionen.'),
            ],
            default => [
                self::text('matrix_suite_overline', 'Header-Overline', 'Lizenzmatrix', 'Kleine Zeile oberhalb der Lizenzmatrix-Überschrift.'),
                self::text('matrix_suite_title', 'Header-Titel', 'Microsoft 365 Lizenzmatrix – Vollpakete', 'Hauptüberschrift der Lizenzmatrix.'),
                self::textarea('matrix_suite_intro', 'Header-Intro', 'Öffentliche Gesamtübersicht der Microsoft-365-Vollpakete Business Basic, Business Standard, Business Premium, Microsoft 365 E3 und Microsoft 365 E5.', 'Einleitungstext im Contentheader.'),
                self::text('matrix_suite_secondary_button_label', 'Sekundärbutton Text', 'Interaktiven Lizenzvergleich öffnen', 'Beschriftung des sekundären Header-Buttons.'),
                self::text('matrix_suite_secondary_button_url', 'Sekundärbutton Ziel', '/m365-lizenzvergleich', 'Interne Route oder vollständige URL.'),
                self::text('matrix_suite_tool_button_label', 'Weiterer Button Text', 'Add-on-Matrix öffnen', 'Beschriftung des zweiten Header-Buttons.'),
                self::text('matrix_suite_tool_button_url', 'Weiterer Button Ziel', '/m365-addon-matrix', 'Interne Route oder vollständige URL.'),
                self::text('matrix_suite_result_overline', 'Matrix-Overline', 'Matrix', 'Kleine Zeile über der Tabelle.'),
                self::text('matrix_suite_result_title', 'Matrix-Titel', 'Gesamtübersicht der Microsoft-365-Vollpakete', 'Überschrift direkt vor der Tabelle.'),
                self::textarea('matrix_suite_result_intro', 'Matrix-Intro', 'Alle zentralen Paket-, App-, Security-, Compliance-, KI- und Beschaffungspunkte in einer Übersicht.', 'Beschreibung direkt vor der Tabelle.'),
                self::text('matrix_suite_primary_button_label', 'CTA-Button Text', 'Lizenzcheck anfragen', 'Beschriftung des primären CTA-Buttons.'),
                self::text('matrix_suite_primary_button_url', 'CTA-Button Ziel', '/kontakt', 'Kontaktformular, Beratungsseite oder interne Route.'),
                self::checkbox('matrix_suite_show_hero', 'Contentheader anzeigen', '1', 'Blendet den oberen Contentheader ein.'),
                self::checkbox('matrix_suite_show_hero_buttons', 'Header-Buttons anzeigen', '1', 'Blendet die Buttons im Contentheader ein.'),
                self::checkbox('matrix_suite_show_result_header', 'Einleitungsbereich vor Matrix anzeigen', '1', 'Blendet den kurzen Matrix-Introbereich ein.'),
                self::checkbox('matrix_suite_show_print_button', 'Drucken-Button anzeigen', '1', 'Zeigt den PDF-/Drucken-Button im Matrixbereich.'),
                self::checkbox('matrix_suite_show_primary_cta', 'CTA-Button anzeigen', '1', 'Zeigt den Kontakt- oder Beratungsbutton im Matrixbereich.'),
                self::checkbox('matrix_suite_show_notes', 'Hinweise anzeigen', '1', 'Zeigt den Hinweisblock unterhalb der Matrix.'),
                self::checkbox('matrix_suite_show_sources', 'Quellenstand anzeigen', '1', 'Zeigt den Quellenblock unterhalb der Matrix.'),
            ],
        };
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function landing_designer_fields(string $tab): array
    {
        return match ($tab) {
            'landing-layout' => [
                self::select('landing_page_layout', 'Seitenbreite', 'wide', [
                    'normal' => 'Normaler Contentbereich',
                    'wide' => 'Breit / volle Theme-Breite',
                    'boxed' => 'Gerahmter Hub',
                    'editorial' => 'Redaktionell mit großzügiger Leseführung',
                    'directory' => 'Verzeichnis-Look für viele Module',
                ], 'Legt die Grundbreite und Anmutung des Hubs fest.'),
                self::select('landing_header_layout', 'Contentheader-Layout', 'split', [
                    'split' => 'Text links, Kennzahlen rechts',
                    'stacked' => 'Untereinander',
                    'compact' => 'Kompakt',
                    'hero-card' => 'Hero-Karte mit ruhiger Fläche',
                    'editorial' => 'Editorial: schmale Textspalte',
                ], 'Layout des oberen Landingpage-Bereichs.'),
                self::select('landing_header_style', 'Contentheader-Stil', 'plain', [
                    'plain' => 'Schlicht mit Trennlinie',
                    'surface' => 'Ruhige Fläche',
                    'bordered' => 'Gerahmter Header',
                    'accent' => 'Mit dezenter Akzentkante',
                    'inverted' => 'Dunkler Header mit hellen Texten',
                ], 'Optik des Contentheaders unabhängig vom Seitenlayout.'),
                self::select('landing_header_alignment', 'Header-Ausrichtung', 'left', [
                    'left' => 'Links ausgerichtet',
                    'center' => 'Zentriert',
                    'split' => 'Split / redaktionell',
                ], 'Ausrichtung von Text und Buttons im Header.'),
                self::select('landing_button_layout', 'Header-Buttons Layout', 'inline', [
                    'inline' => 'Nebeneinander',
                    'stacked' => 'Untereinander',
                    'right' => 'Rechts ausgerichtet',
                ], 'Layout der optionalen Header-Buttons.'),
                self::select('landing_category_layout', 'Kategorie-Navigation', 'line', [
                    'line' => 'Schlichte Link-Zeile',
                    'pills' => 'Pill-Navigation',
                    'cards' => 'Kleine Navigationskarten',
                    'minimal' => 'Minimal mit reduzierten Zählern',
                ], 'Darstellung der Kategorie-Sprungmarken.'),
                self::select('landing_tool_layout', 'Toolbox-Layout', 'grid', [
                    'grid' => 'Kartenraster',
                    'compact-grid' => 'Kompaktes Kartenraster',
                    'list' => 'Listenartige Karten',
                    'directory' => 'Verzeichnis mit klaren Zeilen',
                    'feature-first' => 'Erstes Modul je Kategorie hervorgehoben',
                ], 'Darstellung der Modulboxen.'),
                self::select('landing_card_style', 'Box-Stil', 'bordered', [
                    'bordered' => 'Dezent gerahmt',
                    'quiet' => 'Ruhig / redaktionell',
                    'flat' => 'Flach ohne Schatten',
                    'accent' => 'Mit Akzentkante',
                ], 'Optische Gewichtung der Karten.'),
                self::select('landing_tool_button_style', 'Tool-Button Stil', 'link', [
                    'link' => 'Textlink mit Pfeil',
                    'primary' => 'Primärer Button',
                    'secondary' => 'Sekundärer Button',
                    'minimal' => 'Minimal ohne Pfeil',
                ], 'Darstellung der Buttons innerhalb der Modulboxen.'),
                self::select('landing_density', 'Abstände', 'comfortable', [
                    'compact' => 'Kompakt',
                    'comfortable' => 'Ausgewogen',
                    'spacious' => 'Großzügig',
                ], 'Steuert vertikale Abstände und Kartenpadding.'),
                self::number('landing_card_radius', 'Rundung der Boxen und Buttons in px', '2', 0, 2, 1, 'Maximal 2px: steuert die dezente Rundung von Karten, Contentheader und Buttons.'),
                self::number('landing_cards_min_width', 'Mindestbreite der Modulboxen in px', '320', 220, 520, 10, 'Breite der Modulboxen im Kartenraster.'),
                self::number('landing_section_gap', 'Abschnittsabstand in px', '32', 16, 96, 2, 'Vertikaler Abstand zwischen Landingpage-Abschnitten.'),
            ],
            'landing-colors' => [
                self::color('landing_color_primary', 'Primärfarbe', '#2563eb', 'Buttons, Links, aktive Zustände und dezente Akzente.'),
                self::color('landing_color_accent', 'Akzentfarbe', '#0f766e', 'Sekundärer Akzent für Kanten, Zähler oder Highlights.'),
                self::color('landing_color_background', 'Content-Hintergrund', '#ffffff', 'Grundfläche hinter Contentheader und Modulbereichen auf Landingpage und Public-Modulseiten.'),
                self::color('landing_color_surface', 'Box-Basisfarbe', '#ffffff', 'Basisfarbe für Karten und Review-Bereiche; öffentlich wird sie dezent vom Hintergrund abgesetzt.'),
                self::color('landing_color_surface_alt', 'Ruhige Fläche', '#f8fafc', 'Alternative Fläche für Hero, Fakten oder dezente Blöcke.'),
                self::color('landing_color_header_background', 'Header-Hintergrund', '#f8fafc', 'Eigene Hintergrundfarbe für gerahmte oder flächige Contentheader.'),
                self::color('landing_color_header_text', 'Header-Textfarbe', '#1e293b', 'Eigene Textfarbe im Landingpage-Header.'),
                self::color('landing_color_header_muted', 'Header-Sekundärtext', '#64748b', 'Overline und Introtext im Header.'),
                self::color('landing_color_header_border', 'Header-Rahmen', '#e2e8f0', 'Rahmen- oder Akzentkante des Contentheaders.'),
                self::color('landing_color_button_primary_bg', 'Primärbutton Hintergrund', '#2563eb', 'Hintergrund des primären Header-Buttons.'),
                self::color('landing_color_button_primary_text', 'Primärbutton Text', '#ffffff', 'Textfarbe des primären Header-Buttons.'),
                self::color('landing_color_button_secondary_bg', 'Sekundärbutton Hintergrund', '#ffffff', 'Hintergrund des sekundären Header-Buttons.'),
                self::color('landing_color_button_secondary_text', 'Sekundärbutton Text', '#1e293b', 'Textfarbe des sekundären Header-Buttons.'),
                self::color('landing_color_text', 'Textfarbe', '#1e293b', 'Primäre Textfarbe.'),
                self::color('landing_color_muted', 'Sekundärtext', '#64748b', 'Beschreibungen, Hinweise und kleine Labels.'),
                self::color('landing_color_border', 'Rahmenfarbe', '#e2e8f0', 'Borders, Trennlinien und Tabellenkanten.'),
            ],
            'landing-visibility' => [
                self::checkbox('landing_show_header_overline', 'Header-Overline anzeigen', '1', 'Zeigt die kleine Zeile über der Hauptüberschrift.'),
                self::checkbox('landing_show_header_title', 'Header-Titel anzeigen', '1', 'Zeigt die Hauptüberschrift im Contentheader.'),
                self::checkbox('landing_show_header_intro', 'Header-Intro anzeigen', '1', 'Zeigt den Einleitungstext im Contentheader.'),
                self::checkbox('landing_show_header_buttons', 'Header-Buttons anzeigen', '1', 'Zeigt primäre und sekundäre Buttons im Landingpage-Header.'),
                self::checkbox('landing_show_facts', 'Kennzahlen anzeigen', '1', 'Zeigt Module, Live-Zahl und Review-Bereiche im Header.'),
                self::checkbox('landing_show_fact_modules', 'Kennzahl Module anzeigen', '1', 'Zeigt die Anzahl aller Module.'),
                self::checkbox('landing_show_fact_live', 'Kennzahl Live anzeigen', '1', 'Zeigt die Anzahl aktiver Module.'),
                self::checkbox('landing_show_fact_reviews', 'Kennzahl Review-Bereiche anzeigen', '1', 'Zeigt die Anzahl der Review-Bereiche.'),
                self::checkbox('landing_show_category_nav', 'Kategorie-Navigation anzeigen', '1', 'Blendet die Sprungnavigation zu Kategorien ein.'),
                self::checkbox('landing_show_category_overline', 'Kategorie-Overline anzeigen', '1', 'Zeigt die kleine Kategorie-Zeile über Abschnittsüberschriften.'),
                self::checkbox('landing_show_category_counts', 'Kategorie-Zähler anzeigen', '1', 'Zeigt die Modulanzahl je Kategorie.'),
                self::checkbox('landing_show_review_panel', 'Best-Practice-Kompass anzeigen', '1', 'Zeigt den Review-Block auf der Landingpage.'),
                self::checkbox('landing_show_review_domain_summaries', 'Review-Beschreibungen anzeigen', '1', 'Zeigt die Beschreibungstexte der Review-Domänen.'),
                self::checkbox('landing_show_icons', 'Modul-Icons anzeigen', '1', 'Zeigt die Icon-Spalte in Modulboxen.'),
                self::checkbox('landing_link_card_titles', 'Modultitel verlinken', '1', 'Verlinkt den Titel jeder Modulbox zur Zielseite.'),
                self::checkbox('landing_show_tool_descriptions', 'Modulbeschreibungen anzeigen', '1', 'Zeigt die Beschreibungstexte in den Toolkarten.'),
                self::checkbox('landing_show_status_labels', 'Statuslabels anzeigen', '1', 'Zeigt Beta-/Bald-Hinweise in Modulboxen.'),
                self::checkbox('landing_show_review_chips', 'Review-Chips in Modulboxen anzeigen', '1', 'Zeigt Review-Schwerpunkte direkt in den Toolkarten.'),
                self::checkbox('landing_show_module_checks', 'Prüfpunkte in Modulboxen anzeigen', '1', 'Zeigt konkrete Prüfpunkte direkt in den Toolkarten.'),
                self::checkbox('landing_show_tool_buttons', 'Tool-Buttons anzeigen', '1', 'Zeigt den Button in jeder Modulbox.'),
                self::checkbox('landing_show_disabled_note', 'Hinweis bei inaktiven Modulen anzeigen', '1', 'Zeigt einen kurzen Hinweis, wenn ein Modul noch nicht verfügbar ist.'),
            ],
            default => [
                self::text('landing_overline', 'Header-Overline', 'Rechner & Tools', 'Kleine Zeile oberhalb der Landingpage-Hauptüberschrift.'),
                self::text('landing_title', 'Landingpage-Titel', 'M365 Tools', 'Hauptüberschrift der Toolbox-Landingpage.'),
                self::textarea('landing_intro', 'Intro-Text', 'Eine kuratierte Sammlung für Microsoft-365-Lizenzierung, Kosten, Speicher, Backup, Copilot, Telefonie, Migration und Betrieb.', 'Einleitungstext im Contentheader.'),
                self::text('landing_primary_button_label', 'Primärbutton Text', 'Alle 21 Tools durchsuchen ↓', 'Beschriftung des primären Header-Buttons.'),
                self::text('landing_primary_button_url', 'Primärbutton Ziel', '#direkteinstieg', 'Interne Route, Sprungmarke oder vollständige URL für den primären Header-Button.'),
                self::text('landing_secondary_button_label', 'Sekundärbutton Text', 'Kontakt aufnehmen', 'Beschriftung des sekundären Header-Buttons.'),
                self::text('landing_secondary_button_url', 'Sekundärbutton Ziel', '/kontakt', 'Interne Route oder vollständige URL für den sekundären Header-Button.'),
                self::text('landing_review_overline', 'Review-Overline', 'Querschnittsreview', 'Kleine Zeile oberhalb des Best-Practice-Kompasses.'),
                self::text('landing_review_title', 'Review-Titel', 'Microsoft 365 Best-Practice-Kompass', 'Fallback-Titel, falls der Katalog keinen eigenen Titel liefert.'),
                self::textarea('landing_review_intro', 'Review-Intro', '', 'Optionaler eigener Einleitungstext für den Review-Bereich.'),
                self::text('landing_open_button_label', 'Öffnen-Button Text', 'Tool öffnen', 'Text des Links in jeder Toolbox-Karte. Leer lassen für kontextuelle Texte wie Rechner starten oder Checkliste laden.'),
                self::select('landing_tool_button_target_mode', 'Tool-Button Ziel', 'tool', [
                    'tool' => 'Jeweilige Toolseite',
                    'primary' => 'Primärbutton-Ziel verwenden',
                    'secondary' => 'Sekundärbutton-Ziel verwenden',
                    'custom' => 'Eigenes globales Ziel verwenden',
                ], 'Legt fest, wohin die Buttons in den Modulboxen führen.'),
                self::text('landing_tool_button_custom_url', 'Eigenes Tool-Button Ziel', '', 'Optionales globales Ziel für alle Modulbox-Buttons.'),
            ],
        };
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function plugin_setting_fields(string $tab): array
    {
        return match ($tab) {
            'provider' => [
                self::checkbox('provider_cta_enabled', 'Dienstleister-Hinweis nach Tool-Auswertungen anzeigen', '1', 'Blendet unter Rechner- und Toolseiten einen zentral gepflegten Ansprechpartner ein.'),
                self::text('provider_name', 'Dienstleister / Anbietername', '365 Network', 'Name des Dienstleisters, der nach Tool-Aufrufen empfohlen wird.'),
                self::text('provider_headline', 'CTA-Überschrift', 'Unterstützung bei Microsoft 365 gewünscht?', 'Überschrift für den Hinweisbereich nach Tool-Auswertungen.'),
                self::textarea('provider_text', 'CTA-Text', 'Wir unterstützen bei Lizenzanalyse, Umsetzung, Governance und laufender Optimierung.', 'Kurzer Text für den zentralen Dienstleister-Hinweis.'),
                self::text('provider_button_label', 'Button-Text', 'Beratung anfragen', 'Beschriftung des primären Kontaktbuttons.'),
                self::text('provider_contact_form_url', 'Kontaktformular-URL', '/kontakt', 'Interne Route oder vollständige URL zum gewünschten Kontaktformular.'),
                self::text('provider_profile_url', 'Dienstleister-Profil / Landingpage', '', 'Optionaler Link zur Anbieter- oder Leistungsseite.'),
                self::text('provider_email', 'Kontakt-E-Mail', '', 'Optionaler Kontakt für Rückfragen.'),
                self::text('provider_phone', 'Telefon', '', 'Optional sichtbare Telefonnummer.'),
                self::select('provider_cta_style', 'Darstellung', 'quiet', [
                    'quiet' => 'Ruhig / redaktionell',
                    'boxed' => 'Kompakte Box',
                    'wide' => 'Breiter Abschlussbereich',
                ], 'Steuert die optische Gewichtung des Dienstleister-Hinweises.'),
            ],
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
            'addons' => self::package_catalog_fields('addon'),
            'price-rules' => [
                self::number('default_price_adjustment_percent', 'Globale Preisanpassung in %', '0', -80, 300, 0.1, 'Aufschlag oder Rabatt auf zentrale Referenzpreise.'),
                self::number('partner_discount_percent', 'Partner-/Rahmenvertragsrabatt in %', '0', 0, 90, 0.1, 'Optionaler zentraler Rabatt vor Modulberechnung.'),
                self::number('risk_buffer_percent', 'Budgetpuffer in %', '10', 0, 200, 0.1, 'Puffer für Preisereignisse, Wechselkurse oder Packaging-Änderungen.'),
                self::select('default_price_tier', 'Standard-Preisstufe für Rechner', 'public', [
                    'public' => 'Public',
                    'member' => 'Member',
                    'group' => 'Spezial',
                ], 'Legt fest, welche zentrale Preisstufe neue Kalkulationen bevorzugt verwenden sollen.'),
                self::select('price_source_type', 'Preisquelle', 'reference_json', [
                    'reference_json' => 'M365LIC Seed-Katalog / gepflegte Referenz',
                    'csp_export' => 'CSP-/Partner-Export',
                    'contract' => 'Rahmenvertrag',
                    'manual' => 'Manuelle Annahme',
                ], 'Kennzeichnet die bevorzugte globale Preisquelle.'),
                self::text('price_source_date', 'Preisstand', date('Y-m-d'), 'Datum des letzten Preisabgleichs.'),
                self::textarea('price_source_note', 'Preisnotiz', '', 'Interne Notiz zu Preislisten, Vertragsständen oder Abweichungen.'),
            ],
            default => self::package_catalog_fields('base'),
        };
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function package_catalog_fields(string $kind): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::m365_package_price_catalog();
        $packages = is_array($catalog['packages'] ?? null) ? $catalog['packages'] : [];
        $tiers = [
            'public' => 'Public',
            'member' => 'Member',
            'group' => 'Spezial',
        ];
        $fields = [];

        foreach ($packages as $package) {
            if (!is_array($package) || (string) ($package['kind'] ?? 'base') !== $kind) {
                continue;
            }

            $slug = (string) ($package['slug'] ?? '');
            $name = (string) ($package['name'] ?? $slug);
            if ($slug === '' || $name === '') {
                continue;
            }

            $basis = (string) ($package['pricing_basis'] ?? 'per_user') === 'flat_monthly'
                ? 'Fixpreis pro Monat'
                : 'pro Nutzer/Monat';
            $category = (string) ($package['category'] ?? 'm365');

            foreach ($tiers as $tier => $tierLabel) {
                $field = $tier === 'member' ? 'member_price' : ($tier === 'group' ? 'group_price' : 'public_price');
                $default = $package[$field] ?? $package['public_price'] ?? '0';
                $fields[] = self::number(
                    CMS_M365CALCULATOR_Catalog::package_price_option_key($slug, $tier),
                    $name . ' · ' . $tierLabel,
                    is_numeric($default) ? (string) $default : '0',
                    0,
                    100000,
                    0.01,
                    'Kategorie ' . $category . ', ' . $basis . '. Herkunft: CMS M365 License Seed-Katalog.'
                );
            }
        }

        return $fields !== [] ? $fields : [
            self::textarea('package_catalog_note', 'Kataloghinweis', '', 'Es wurden keine Pakete gefunden. Prüfe, ob CMS M365 License aktiv ist oder lokale Fallback-Preise vorhanden sind.'),
        ];
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
                self::number('annual-monthly-uplift-percent', 'Jahresbindung mit monatlicher Zahlung in %', '5', 0, 200, 0.1, 'Entspricht dem M365LIC-Standard für Jahr / monatlich.'),
                self::number('monthly_uplift_percent', 'Monatslaufzeit-Aufschlag in %', '20', 0, 200, 0.1, 'Entspricht dem M365LIC-Standard für Monat / flexibel.'),
                self::number('annual_discount_percent', 'Jährliche Zahlung Rabatt in %', '0', 0, 90, 0.1, 'Optionaler Rabatt für Jahr / jährlich gegenüber der Referenz.'),
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
    private static function color(string $key, string $label, string $default, string $help): array
    {
        return ['key' => $key, 'label' => $label, 'type' => 'color', 'default' => $default, 'help' => $help];
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
                $allowed = is_array($field['options'] ?? null) ? array_map('strval', array_keys($field['options'])) : [];
                $options[$key] = in_array($raw, $allowed, true) ? $raw : (string) ($field['default'] ?? '');
                continue;
            }

            if ($type === 'color') {
                $options[$key] = preg_match('/^#[0-9a-fA-F]{6}$/', $raw) === 1 ? strtolower($raw) : (string) ($field['default'] ?? '#000000');
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
