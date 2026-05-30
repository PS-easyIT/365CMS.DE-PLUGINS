<?php
/**
 * CMS M365 Matrixen – Admin Pages.
 *
 * @package CMS_M365MATRICES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365MATRICES_Admin_Pages
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
        self::render_with_layout('M365 Matrixen', 'm365matrices-dashboard', static function (): void {
            self::instance()->render_settings_page();
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
        echo '<div class="m365matrices-admin-shell">';
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
        $css = CMS_M365MATRICES_PLUGIN_DIR . 'assets/css/m365matrices-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_M365MATRICES_PLUGIN_URL . 'assets/css/m365matrices-admin.css', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    private function render_settings_page(): void
    {
        CMS_M365MATRICES_Source::load_runtime();
        CMS_M365MATRICES_Installer::maybe_install();

        $tabs = [
            'matrix-suite' => '📊 Lizenzmatrix',
            'matrix-addon' => '➕ Add-on-Matrix',
            'matrix-design' => '🎨 Design',
        ];
        $activeTab = self::active_tab($tabs);
        $fields = self::fields($activeTab);
        $notice = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'save_matrix_options') {
            if (!self::verify_nonce('m365matrices_' . $activeTab)) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } elseif (!class_exists('CMS_M365CALCULATOR_Settings')) {
                $error = 'Die gemeinsame M365-Tools-Settings-Klasse ist nicht verfügbar.';
            } else {
                try {
                    CMS_M365CALCULATOR_Settings::save_global_options($activeTab, self::sanitize_options($fields, $_POST));
                    $notice = 'Matrix-Einstellungen gespeichert.';
                } catch (\Throwable $e) {
                    $error = 'Einstellungen konnten nicht gespeichert werden: ' . $e->getMessage();
                }
            }
        }

        $options = class_exists('CMS_M365CALCULATOR_Settings') ? CMS_M365CALCULATOR_Settings::global_options($activeTab) : [];
        $suiteStats = class_exists('CMS_M365CALCULATOR_ReadOnly_Matrices') ? CMS_M365CALCULATOR_ReadOnly_Matrices::suite_matrix()['counts'] ?? [] : [];
        $addonStats = class_exists('CMS_M365CALCULATOR_ReadOnly_Matrices') ? CMS_M365CALCULATOR_ReadOnly_Matrices::addon_matrix()['counts'] ?? [] : [];
        $csrfToken = self::generate_nonce('m365matrices_' . $activeTab);
        ?>
        <div class="admin-page-header">
            <div>
                <h2>📚 M365 Matrixen</h2>
                <p>Reine Lizenz- und Add-on-Matrixen mit gemeinsamer M365-Tools-Datenbasis.</p>
            </div>
            <div class="header-actions">
                <a href="/m365-lizenzmatrix" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">👁️ Lizenzmatrix</a>
                <a href="/m365-addon-matrix" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">➕ Add-ons</a>
            </div>
        </div>

        <?php if ($notice !== ''): ?>
        <div class="alert alert-success">✅ <?php echo self::esc($notice); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
        <div class="alert alert-error">❌ <?php echo self::esc($error); ?></div>
        <?php endif; ?>

        <div class="dashboard-grid m365matrices-stats">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-number"><?php echo (int) ($suiteStats['rows'] ?? 0); ?></div>
                <div class="stat-label">Lizenzmatrix-Zeilen</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-number"><?php echo (int) ($suiteStats['columns'] ?? 0); ?></div>
                <div class="stat-label">Vollpakete</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">➕</div>
                <div class="stat-number"><?php echo (int) ($addonStats['areas'] ?? 0); ?></div>
                <div class="stat-label">Add-on-Bereiche</div>
            </div>
        </div>

        <div class="m365matrices-tabs">
            <?php foreach ($tabs as $tabKey => $label): ?>
            <a href="?tab=<?php echo self::esc_attr($tabKey); ?>" class="m365matrices-tab<?php echo $activeTab === $tabKey ? ' active' : ''; ?>">
                <?php echo self::esc($label); ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="admin-card m365matrices-settings-card">
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="save_matrix_options">
                <input type="hidden" name="csrf_token" value="<?php echo self::esc_attr($csrfToken); ?>">

                <?php foreach ($fields as $field): ?>
                    <?php self::render_field($field, $options); ?>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
            </form>
        </div>
        <?php
    }

    /**
     * @param array<string,string> $tabs
     */
    private static function active_tab(array $tabs): string
    {
        $tab = preg_replace('/[^a-z0-9_-]+/i', '', (string) ($_GET['tab'] ?? 'matrix-suite')) ?: 'matrix-suite';

        return isset($tabs[$tab]) ? $tab : 'matrix-suite';
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function fields(string $tab): array
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
                self::text('matrix_addon_area_overline', 'Bereichs-Overline', 'Add-on-Bereich', 'Kleine Zeile oberhalb der Add-on-Bereichsüberschriften.'),
                self::text('matrix_addon_notes_title', 'Hinweisblock-Titel', 'Hinweise zur Add-on-Übersicht', 'Überschrift des Hinweisblocks unterhalb der Matrixbereiche.'),
                self::text('matrix_addon_sources_title', 'Quellenblock-Titel', 'Quellenstand', 'Überschrift des Quellenblocks unterhalb der Matrixbereiche.'),
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
                self::select('matrix_header_style', 'Contentheader-Stil', 'plain', ['plain' => 'Schlicht', 'surface' => 'Ruhige Fläche', 'bordered' => 'Gerahmt', 'accent' => 'Akzentkante', 'inverted' => 'Dunkel / invertiert'], 'Optik des Matrix-Contentheaders.'),
                self::select('matrix_header_alignment', 'Header-Ausrichtung', 'split', ['split' => 'Text links, Aktionen rechts', 'left' => 'Links ausgerichtet', 'center' => 'Zentriert'], 'Ausrichtung von Headertexten und Aktionen.'),
                self::select('matrix_button_layout', 'Button-Layout', 'inline', ['inline' => 'Nebeneinander', 'stacked' => 'Untereinander', 'right' => 'Rechts ausgerichtet'], 'Layout der Header- und Matrix-Aktionen.'),
                self::select('matrix_button_style', 'Button-Stil', 'default', ['default' => 'Theme-Standard', 'primary' => 'Alle Aktionen primär betonen', 'secondary' => 'Alle Aktionen ruhig darstellen', 'minimal' => 'Minimal / textnah'], 'Optische Gewichtung der Matrix-Buttons.'),
                self::text('matrix_print_button_label', 'Drucken-Button Text', 'Drucken / PDF speichern', 'Beschriftung der Druck-/PDF-Aktion auf beiden Matrixseiten.'),
                self::number('matrix_page_max_width', 'Seitenbreite in px', '1200', 760, 1800, 20, 'Maximale Breite des äußeren Matrix-Contents. Die Tabelle darf intern weiterhin scrollen.'),
                self::number('matrix_outer_padding_x', 'Seitlicher Innenabstand in px', '0', 0, 96, 4, 'Horizontaler Innenabstand des äußeren Matrix-Containers.'),
                self::number('matrix_outer_padding_top', 'Abstand oben in px', '25', 0, 120, 5, 'Abstand zwischen Theme-Header und Matrix-Content.'),
                self::number('matrix_section_gap', 'Abschnittsabstand in px', '32', 12, 96, 4, 'Vertikaler Abstand zwischen Header, Einleitung, Bereichen, Hinweisen und Quellen.'),
                self::number('matrix_header_radius', 'Header-Rundung in px', '2', 0, 2, 1, 'Maximal 2px: Rundung für flächige oder gerahmte Header.'),
                self::color('matrix_color_page_background', 'Seiten-Hintergrund', '#ffffff', 'Hintergrundfarbe des äußeren Matrix-Containers.'),
                self::color('matrix_color_surface_background', 'Flächen-Hintergrund', '#f8fafc', 'Hintergrundfarbe für Contentheader, Intro-, Hinweis- und Quellenbereiche außerhalb der Tabellen.'),
                self::color('matrix_color_text', 'Textfarbe außen', '#1e293b', 'Standard-Textfarbe außerhalb der Matrix-Tabellen.'),
                self::color('matrix_color_muted', 'Sekundärtext außen', '#64748b', 'Beschreibungstexte, Overlines und Meta-Texte außerhalb der Tabellen.'),
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
                self::text('matrix_suite_notes_title', 'Hinweisblock-Titel', 'Hinweise zur Lizenzmatrix', 'Überschrift des Hinweisblocks unterhalb der Lizenzmatrix.'),
                self::text('matrix_suite_sources_title', 'Quellenblock-Titel', 'Quellenstand', 'Überschrift des Quellenblocks unterhalb der Lizenzmatrix.'),
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
     * @return array<string,mixed>
     */
    private static function text(string $key, string $label, string $default, string $help): array
    {
        return compact('key', 'label', 'default', 'help') + ['type' => 'text'];
    }

    /**
     * @return array<string,mixed>
     */
    private static function textarea(string $key, string $label, string $default, string $help): array
    {
        return compact('key', 'label', 'default', 'help') + ['type' => 'textarea'];
    }

    /**
     * @return array<string,mixed>
     */
    private static function checkbox(string $key, string $label, string $default, string $help): array
    {
        return compact('key', 'label', 'default', 'help') + ['type' => 'checkbox'];
    }

    /**
     * @param array<string,string> $options
     * @return array<string,mixed>
     */
    private static function select(string $key, string $label, string $default, array $options, string $help): array
    {
        return compact('key', 'label', 'default', 'options', 'help') + ['type' => 'select'];
    }

    /**
     * @return array<string,mixed>
     */
    private static function number(string $key, string $label, string $default, int $min, int $max, int $step, string $help): array
    {
        return compact('key', 'label', 'default', 'min', 'max', 'step', 'help') + ['type' => 'number'];
    }

    /**
     * @return array<string,mixed>
     */
    private static function color(string $key, string $label, string $default, string $help): array
    {
        return compact('key', 'label', 'default', 'help') + ['type' => 'color'];
    }

    /**
     * @param array<string,mixed> $field
     * @param array<string,string> $options
     */
    private static function render_field(array $field, array $options): void
    {
        $key = (string) ($field['key'] ?? '');
        $type = (string) ($field['type'] ?? 'text');
        $value = (string) ($options[$key] ?? ($field['default'] ?? ''));
        ?>
        <div class="form-group m365matrices-field m365matrices-field--<?php echo self::esc_attr($type); ?>">
            <?php if ($type === 'checkbox'): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="<?php echo self::esc_attr($key); ?>" value="1" <?php echo $value === '1' ? 'checked' : ''; ?>>
                    <?php echo self::esc((string) ($field['label'] ?? $key)); ?>
                </label>
            <?php else: ?>
                <label class="form-label" for="<?php echo self::esc_attr($key); ?>"><?php echo self::esc((string) ($field['label'] ?? $key)); ?></label>
                <?php if ($type === 'textarea'): ?>
                    <textarea id="<?php echo self::esc_attr($key); ?>" name="<?php echo self::esc_attr($key); ?>" class="form-control" rows="3"><?php echo self::esc($value); ?></textarea>
                <?php elseif ($type === 'select'): ?>
                    <select id="<?php echo self::esc_attr($key); ?>" name="<?php echo self::esc_attr($key); ?>" class="form-control">
                        <?php foreach ((array) ($field['options'] ?? []) as $optionValue => $label): ?>
                        <option value="<?php echo self::esc_attr((string) $optionValue); ?>" <?php echo $value === (string) $optionValue ? 'selected' : ''; ?>><?php echo self::esc((string) $label); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($type === 'number'): ?>
                    <input id="<?php echo self::esc_attr($key); ?>" type="number" name="<?php echo self::esc_attr($key); ?>" class="form-control" value="<?php echo self::esc_attr($value); ?>" min="<?php echo (int) ($field['min'] ?? 0); ?>" max="<?php echo (int) ($field['max'] ?? 999); ?>" step="<?php echo (int) ($field['step'] ?? 1); ?>">
                <?php elseif ($type === 'color'): ?>
                    <div class="m365matrices-color-control">
                        <input id="<?php echo self::esc_attr($key); ?>" type="color" name="<?php echo self::esc_attr($key); ?>" class="form-control" value="<?php echo self::esc_attr(self::valid_color($value, (string) ($field['default'] ?? '#000000'))); ?>">
                        <input type="text" name="<?php echo self::esc_attr($key); ?>_text" class="form-control" value="<?php echo self::esc_attr(self::valid_color($value, (string) ($field['default'] ?? '#000000'))); ?>" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7" onchange="this.previousElementSibling.value=this.value">
                    </div>
                <?php else: ?>
                    <input id="<?php echo self::esc_attr($key); ?>" type="text" name="<?php echo self::esc_attr($key); ?>" class="form-control" value="<?php echo self::esc_attr($value); ?>" maxlength="500">
                <?php endif; ?>
            <?php endif; ?>
            <small class="form-text"><?php echo self::esc((string) ($field['help'] ?? '')); ?></small>
        </div>
        <?php
    }

    /**
     * @param array<int,array<string,mixed>> $fields
     * @param array<string,mixed> $posted
     * @return array<string,string>
     */
    private static function sanitize_options(array $fields, array $posted): array
    {
        $options = [];
        foreach ($fields as $field) {
            $key = (string) ($field['key'] ?? '');
            $type = (string) ($field['type'] ?? 'text');
            if ($key === '') {
                continue;
            }

            if ($type === 'checkbox') {
                $options[$key] = !empty($posted[$key]) ? '1' : '0';
                continue;
            }

            if ($type === 'number') {
                $value = (int) ($posted[$key] ?? ($field['default'] ?? 0));
                $options[$key] = (string) max((int) ($field['min'] ?? 0), min((int) ($field['max'] ?? 999), $value));
                continue;
            }

            if ($type === 'select') {
                $value = (string) ($posted[$key] ?? ($field['default'] ?? ''));
                $options[$key] = array_key_exists($value, (array) ($field['options'] ?? [])) ? $value : (string) ($field['default'] ?? '');
                continue;
            }

            if ($type === 'color') {
                $value = (string) ($posted[$key . '_text'] ?? $posted[$key] ?? ($field['default'] ?? '#000000'));
                $options[$key] = self::valid_color($value, (string) ($field['default'] ?? '#000000'));
                continue;
            }

            $limit = $type === 'textarea' ? 2000 : 500;
            $value = trim(strip_tags((string) ($posted[$key] ?? ($field['default'] ?? ''))));
            $options[$key] = self::limit_text($value, $limit);
        }

        return $options;
    }

    private static function valid_color(string $value, string $default): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $default;
    }

    private static function generate_nonce(string $action): string
    {
        return class_exists('CMS\\Security') ? \CMS\Security::instance()->generateToken($action) : bin2hex(random_bytes(16));
    }

    private static function verify_nonce(string $action): bool
    {
        return !class_exists('CMS\\Security') || \CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), $action);
    }

    private static function limit_text(string $value, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }

        return substr($value, 0, $length);
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private static function esc_attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}