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
        $activeSlug = self::resolve_active_admin_slug($slug);

        if (function_exists('cms_plugin_admin_layout_start')) {
            cms_plugin_admin_layout_start($title, $activeSlug);
        } elseif (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, $activeSlug);
        }

        self::enqueue_admin_assets();
        echo '<div class="m365calculator-admin-shell">';
        $renderer();
        echo '</div>';

        if (function_exists('cms_plugin_admin_layout_end')) {
            cms_plugin_admin_layout_end();
        } elseif (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
    }

    private static function check_access(): void
    {
        if (!self::has_admin_capability()) {
            header('Location: ' . self::safe_admin_redirect_url(), true, 302);
            exit;
        }
    }

    private static function has_admin_capability(): bool
    {
        if (function_exists('current_user_can') && !current_user_can('manage_options')) {
            return false;
        }

        if (!class_exists('CMS\\Auth')) {
            return false;
        }

        return \CMS\Auth::instance()->isAdmin();
    }

    private static function safe_admin_redirect_url(): string
    {
        $url = defined('SITE_URL') ? trim((string) SITE_URL) : '/';
        if ($url === '' || str_contains($url, "\0") || preg_match('/[\r\n]/', $url) === 1) {
            return '/';
        }

        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return $url;
        }

        $scheme = strtolower((string) (parse_url($url, PHP_URL_SCHEME) ?: ''));
        if (in_array($scheme, ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return $url;
        }

        return '/';
    }

    private static function safe_public_url(string $url): string
    {
        $url = trim($url);
        if ($url === '' || str_contains($url, "\0") || preg_match('/[\r\n]/', $url) === 1) {
            return '';
        }

        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return $url;
        }

        $scheme = strtolower((string) (parse_url($url, PHP_URL_SCHEME) ?: ''));
        if (in_array($scheme, ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return $url;
        }

        return '';
    }

    private static function enqueue_admin_assets(): void
    {
        $css = CMS_M365CALCULATOR_PLUGIN_DIR . 'assets/css/m365calculator-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_M365CALCULATOR_PLUGIN_URL . 'assets/css/m365calculator-admin.css', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($css) . '">' . "\n";
        }

        $js = CMS_M365CALCULATOR_PLUGIN_DIR . 'assets/js/m365calculator-admin.js';
        if (file_exists($js)) {
            echo '<script src="'
                . htmlspecialchars(CMS_M365CALCULATOR_PLUGIN_URL . 'assets/js/m365calculator-admin.js', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($js) . '" defer></script>' . "\n";
        }
    }

    private static function csrf_token(string $action): string
    {
        if (!class_exists('CMS\\Security')) {
            return '';
        }

        return (string) \CMS\Security::instance()->generateToken($action);
    }

    private static function verify_admin_request(string $action): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return false;
        }

        if (!self::has_admin_capability()) {
            return false;
        }

        if (!class_exists('CMS\\Security')) {
            error_log('CMS M365 Tools admin security service missing for action: ' . $action);

            return false;
        }

        return \CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), $action);
    }

    public function render_dashboard_page(): void
    {
        $notice = '';
        $error = '';
        $dispatchFallback = (string) ($_GET['m365tools_dispatch_fallback'] ?? '') === '1';
        $csrfAction = 'm365tools_admin_modules';
        $csrfToken = self::csrf_token($csrfAction);

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'save_module_settings') {
            if (!self::verify_admin_request($csrfAction)) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                if (self::run_admin_save(static function (): void {
                    CMS_M365CALCULATOR_Settings::save_module_settings($_POST);
                }, $error)) {
                    $notice = 'Moduleinstellungen gespeichert.';
                }
            }
        }

        if ($dispatchFallback && $notice === '' && $error === '') {
            $notice = 'Die angeforderte Admin-Unterseite war nicht verfügbar. Es wurde die Übersicht geladen.';
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
                            <th>Public</th>
                            <th>Beschreibung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allTools as $tool): ?>
                        <?php
                        $moduleKey = (string) ($tool['key'] ?? '');
                        $settings = $moduleSettings[$moduleKey] ?? [];
                        $statusValue = (string) ($settings['status_override'] ?? ($tool['status'] ?? 'live'));
                        $isModuleEnabled = (int) ($settings['is_enabled'] ?? 1) === 1;
                        $publicUrl = self::safe_public_url((string) ($tool['url'] ?? ''));
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
                            <td>
                                <?php if ($publicUrl !== ''): ?>
                                <a class="m365calculator-admin-url-link" href="<?php echo htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" title="Public URL als Admin öffnen">
                                    <code><?php echo htmlspecialchars((string) ($tool['url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                                </a>
                                <?php else: ?>
                                <code><?php echo htmlspecialchars((string) ($tool['url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                                <?php endif; ?>
                            </td>
                            <td class="m365calculator-admin-public-cell">
                                <?php if ($isModuleEnabled && $publicUrl !== ''): ?>
                                <a class="m365calculator-admin-public-link" href="<?php echo htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" title="Public Site öffnen" aria-label="Public Site von <?php echo htmlspecialchars((string) ($tool['title'] ?? $moduleKey), ENT_QUOTES, 'UTF-8'); ?> öffnen">↗</a>
                                <?php endif; ?>
                            </td>
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
        $csrfToken = self::csrf_token($csrfAction);

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (!self::verify_admin_request($csrfAction)) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $action = (string) ($_POST['action'] ?? '');
                if ($action === 'save_module_display') {
                    if (self::run_admin_save(static function () use ($moduleKey): void {
                        CMS_M365CALCULATOR_Settings::save_single_module_settings($moduleKey, $_POST);
                    }, $error)) {
                        $notice = 'Anzeigeeinstellungen gespeichert.';
                        $activeTab = 'display';
                    }
                } elseif ($action === 'save_module_options') {
                    $activeTab = self::normalize_tab((string) ($_POST['settings_group'] ?? $activeTab), $tabs);
                    $fields = CMS_M365CALCULATOR_Admin_Module_Config::fields_for($tool, $activeTab);
                    if (self::run_admin_save(static function () use ($moduleKey, $activeTab, $fields): void {
                        CMS_M365CALCULATOR_Settings::save_module_options($moduleKey, $activeTab, self::sanitize_module_options($fields, $_POST));
                    }, $error)) {
                        $notice = 'Moduleinstellungen gespeichert.';
                    }
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
        $csrfToken = self::csrf_token($csrfAction);
        $notice = '';
        $error = '';

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (!self::verify_admin_request($csrfAction)) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } elseif ((string) ($_POST['action'] ?? '') === 'save_global_options') {
                $activeTab = self::normalize_tab((string) ($_POST['settings_group'] ?? $activeTab), $tabs, $defaultTab);
                $fields = self::global_fields_for($area, $activeTab);
                if (self::run_admin_save(static function () use ($activeTab, $fields): void {
                    CMS_M365CALCULATOR_Settings::save_global_options($activeTab, self::sanitize_module_options($fields, $_POST));
                }, $error)) {
                    $notice = 'Globale Einstellungen gespeichert.';
                }
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

    private static function run_admin_save(callable $save, string &$error): bool
    {
        try {
            if (class_exists('CMS_M365CALCULATOR_Installer')) {
                CMS_M365CALCULATOR_Installer::ensure_for_admin_save();
            }

            $save();

            return true;
        } catch (\Throwable $e) {
            error_log('CMS M365 Tools admin save failed: ' . $e->getMessage());
            $error = 'Speichern ist fehlgeschlagen. Die Datenbanktabellen wurden geprüft. Technischer Hinweis: ' . self::admin_error_detail($e->getMessage());

            return false;
        }
    }

    private static function admin_error_detail(string $message): string
    {
        $message = trim(strip_tags($message));
        $message = preg_replace('/\s+/', ' ', $message) ?? $message;

        return self::limit_text($message !== '' ? $message : 'Unbekannter Datenbankfehler.', 220);
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
                'landing-texts' => '📝 Bereiche & Tools',
                'landing-layout' => '🧱 Layouts & Boxen',
                'landing-colors' => '🎨 Farben',
                'landing-visibility' => '👁️ Sichtbarkeit',
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

        return self::plugin_setting_fields($tab);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function landing_designer_fields(string $tab): array
    {
        return match ($tab) {
            'landing-texts' => self::landing_text_fields(),
            'landing-layout' => [
                self::select('landing_page_layout', 'Seitenbreite', 'wide', [
                    'normal' => 'Normaler Contentbereich',
                    'wide' => 'Breit / volle Theme-Breite',
                    'boxed' => 'Gerahmter Hub',
                    'editorial' => 'Redaktionell mit großzügiger Leseführung',
                    'directory' => 'Verzeichnis-Look für viele Module',
                ], 'Legt die Grundbreite und Anmutung des Hubs fest.'),
                self::number('landing_content_max_width', 'Maximale Plugin-Content-Breite in px', '0', 0, 1600, 20, '0 nutzt den Layout-Standard. Werte ab 760px begrenzen die Publicsite-Contentbreite für Übersicht und Modul-Publicseiten.'),
                self::number('landing_content_gutter', 'Horizontaler Content-Abstand links/rechts in px', '24', 0, 96, 2, 'Steuert den Innenabstand zwischen Plugin-Content und linkem/rechtem Rand. 0 rendert bündig im Theme-Contentbereich.'),
                self::number('landing_cards_per_row', 'Toolcards nebeneinander je Bereich', '3', 0, 6, 1, '0 nutzt Auto-Layout. Werte 1–6 steuern die maximale Spaltenanzahl pro Bereich; Bereiche mit weniger Karten füllen die volle Breite.'),
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
                self::select('landing_search_layout', 'Suchbereich Layout', 'stacked', [
                    'stacked' => 'Unterteilt: Suche oben, Kategorien darunter',
                    'split-search-left' => 'Card geteilt: Suche links, Kategorien rechts',
                    'split-search-right' => 'Card geteilt: Kategorien links, Suche rechts',
                ], 'Steuert die Darstellung der Suche und Kategorieauswahl.'),
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
                    'corner-icon' => 'Dreieck oben rechts mit Icon',
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
                self::text('landing_header_image_url', 'Contentheader-Bild URL', '', 'Optionales Bild im Landingpage-Contentheader. Leer lassen, wenn kein Bild angezeigt werden soll.'),
                self::text('landing_header_image_alt', 'Contentheader-Bild Alt-Text', '', 'Alternativtext für das optionale Contentheader-Bild.'),
                self::select('landing_header_image_layout', 'Contentheader-Bild Layout', 'right', [
                    'right' => 'Bild rechts neben dem Text',
                    'left' => 'Bild links neben dem Text',
                    'banner' => 'Bild als ruhiger Banner unter dem Text',
                ], 'Drei Layoutvarianten für das optionale Contentheader-Bild.'),
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
    private static function landing_text_fields(): array
    {
        $fields = [
            self::text('landing_search_title', 'Suchbereich Überschrift', 'Tools suchen und filtern', 'Überschrift oberhalb des Suchfelds.'),
            self::text('landing_search_placeholder', 'Suchfeld Platzhalter', 'Nach Tool, Thema oder Kategorie suchen …', 'Placeholder im Suchfeld.'),
            self::text('landing_search_help', 'Suchbereich Hilfetext', 'Suche und Kategorie wirken gemeinsam.', 'Kleiner Hinweis im Suchbereich.'),
            self::text('landing_search_category_label', 'Suchbereich Kategorie-Label', 'Kategorien', 'Überschrift für die Kategoriechips im Suchpanel.'),
            self::text('landing_all_categories_label', 'Alle-Kategorien Chip', 'Alle', 'Text des Chips für alle Kategorien.'),
            self::text('landing_category_overline_text', 'Bereich-Overline', 'Tool-Kategorie', 'Kleine Overline oberhalb jedes Bereichs.'),
            self::text('landing_no_results_title', 'Keine-Ergebnisse Titel', 'Keine Tools für deine Auswahl gefunden.', 'Titel der Meldung, wenn die Suche keine Treffer liefert.'),
            self::text('landing_no_results_text', 'Keine-Ergebnisse Text', 'Bitte Suchbegriff anpassen oder einen anderen Kategorie-Chip wählen.', 'Beschreibung der Keine-Ergebnisse-Meldung.'),
            self::text('landing_status_beta_label', 'Statuslabel Beta', 'Beta', 'Text für Beta-Statuslabels.'),
            self::text('landing_status_soon_label', 'Statuslabel Bald', 'Bald', 'Text für Bald-verfügbar-Statuslabels.'),
            self::textarea('landing_disabled_note_text', 'Hinweis bei inaktiven Tools', 'Dieses Modul ist vorbereitet und wird bald verfügbar.', 'Hinweistext in Toolcards ohne aktive Zielseite.'),
        ];

        if (class_exists('CMS_M365CALCULATOR_Catalog')) {
            $catalog = CMS_M365CALCULATOR_Catalog::m365_best_practice_catalog();
            $domains = is_array($catalog['domains'] ?? null) ? $catalog['domains'] : [];

            foreach ($domains as $domainKey => $domain) {
                if (!is_array($domain)) {
                    continue;
                }

                $key = self::clean_key((string) $domainKey);
                if ($key === '') {
                    continue;
                }

                $label = trim((string) ($domain['label'] ?? $domainKey));
                $fields[] = self::text('landing_review_domain_label_' . $key, 'Kompass-Card Titel: ' . $label, $label, 'Titel dieser Best-Practice-Kompass-Card und der zugehörigen Review-Chips.');
                $fields[] = self::textarea('landing_review_domain_summary_' . $key, 'Kompass-Card Text: ' . $label, (string) ($domain['summary'] ?? ''), 'Beschreibung dieser Best-Practice-Kompass-Card.');
            }
        }

        $groups = class_exists('CMS_M365CALCULATOR_Tool_Registry')
            ? CMS_M365CALCULATOR_Tool_Registry::grouped_by_category()
            : [];

        foreach ($groups as $categoryLabel => $tools) {
            if (!is_array($tools)) {
                continue;
            }

            $category = trim((string) $categoryLabel) !== '' ? trim((string) $categoryLabel) : 'Weitere Tools';
            $categorySlug = self::clean_key($category);
            $fields[] = self::text('landing_category_label_' . $categorySlug, 'Bereichstitel: ' . $category, $category, 'Titel dieses Bereichs auf der Landingpage und in der Kategorie-Navigation.');
            $fields[] = self::textarea('landing_category_summary_' . $categorySlug, 'Bereichsbeschreibung: ' . $category, self::landing_category_summary_default($categorySlug), 'Beschreibungstext direkt unter dem Bereichstitel.');

            foreach ($tools as $tool) {
                if (!is_array($tool)) {
                    continue;
                }

                $toolKey = self::clean_key((string) ($tool['key'] ?? ''));
                if ($toolKey === '') {
                    continue;
                }

                $toolTitle = trim((string) ($tool['title'] ?? $toolKey));
                $fields[] = self::text('landing_tool_title_' . $toolKey, 'Tool-Titel: ' . $toolTitle, $toolTitle, 'Titel dieser Toolcard auf der Landingpage.');
                $fields[] = self::textarea('landing_tool_description_' . $toolKey, 'Tool-Beschreibung: ' . $toolTitle, (string) ($tool['description'] ?? ''), 'Beschreibung dieser Toolcard auf der Landingpage.');
                $fields[] = self::text('landing_tool_button_label_' . $toolKey, 'Tool-Button: ' . $toolTitle, '', 'Optionaler Button-Text nur für dieses Tool. Leer = globaler Tool-Button Text.');
            }
        }

        return $fields;
    }

    private static function landing_category_summary_default(string $categorySlug): string
    {
        return match ($categorySlug) {
            'lizenzen' => 'Lizenzmodelle, Add-ons, Laufzeiten und Kostenpfade sauber vergleichen.',
            'exchange' => 'Exchange, Archivierung, Shared Mailboxes und ROI-Fragen belastbar prüfen.',
            'copilot' => 'Copilot-Szenarien, Pilotphasen und Lizenzoptionen pragmatisch bewerten.',
            'teams' => 'Telefonie, PSTN-Modelle und Teams-Phone-Optionen vergleichen.',
            'speicher' => 'SharePoint, OneDrive, Exchange und Backup-Speicherbedarf greifbar machen.',
            default => 'Weitere Microsoft-365-Tools und Hilfen.',
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
                self::select('public_detail_button_layout', 'Button-Layout auf Tool-Publicsites', 'inline', [
                    'inline' => 'Nebeneinander / Standard',
                    'stacked' => 'Untereinander',
                    'right' => 'Rechts ausgerichtet',
                    'full' => 'Volle Breite untereinander',
                ], 'Gilt nur für einzelne Tool-Publicsites und die detailseitige CTA – nicht für die Tool-Übersicht.'),
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
            $sourceNote = (string) ($package['source_note'] ?? 'Lokaler M365-Tools-Standardkatalog oder M365LIC Seed-Katalog.');

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
                    'Kategorie ' . $category . ', ' . $basis . '. Herkunft: ' . $sourceNote
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
                $options[$key] = self::format_number_option(max($min, min($max, $number)), $raw, $min, $max);
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

    private static function format_number_option(float $value, string $raw, float $min, float $max): string
    {
        $raw = trim(str_replace(',', '.', $raw));
        if ($raw !== '' && is_numeric($raw)) {
            $rawNumber = (float) $raw;
            if ($rawNumber >= $min && $rawNumber <= $max) {
                return self::normalize_number_string($raw);
            }
        }

        return self::number_to_string($value);
    }

    private static function normalize_number_string(string $value): string
    {
        $value = trim($value);
        if (stripos($value, 'e') !== false) {
            return self::number_to_string((float) $value);
        }

        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '+-');
        [$integer, $decimal] = array_pad(explode('.', $value, 2), 2, '');
        $integer = ltrim($integer, '0');
        $integer = $integer !== '' ? $integer : '0';

        if ($integer === '0' && (float) $value == 0.0) {
            $negative = false;
        }

        return ($negative ? '-' : '') . $integer . ($decimal !== '' ? '.' . $decimal : '');
    }

    private static function number_to_string(float $value): string
    {
        if (abs($value - round($value)) < 0.000000001) {
            return (string) (int) round($value);
        }

        return rtrim(rtrim(sprintf('%.12F', $value), '0'), '.');
    }

    private static function clean_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($value)), '-');
    }

    private static function resolve_active_admin_slug(string $fallback): string
    {
        $fallback = self::clean_key($fallback);
        if (function_exists('cms_plugin_admin_active_slug')) {
            return cms_plugin_admin_active_slug($fallback);
        }

        $requested = (string) ($_GET['page'] ?? $fallback);
        $requested = self::clean_key($requested);

        return $requested !== '' ? $requested : $fallback;
    }

    private static function limit_text(string $value, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }

        return substr($value, 0, $length);
    }
}
