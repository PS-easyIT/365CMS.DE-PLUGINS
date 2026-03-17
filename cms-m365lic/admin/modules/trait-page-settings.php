<?php
/**
 * CMS M365 License – Settings Page
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_M365LIC_Page_Settings_Trait
{
    public function render_settings_page(): void
    {
        $notice = '';
        $error = '';
        $tab = sanitize_text_field($_GET['tab'] ?? 'general');
        $billingOptions = CMS_M365LIC_Catalog::billing_options();
        $settings = self::repo()->get_settings();
        $stats = self::repo()->get_statistics();
        $tableStatus = $this->get_system_table_status();
        $systemInfo = $this->get_system_info($settings, $tableStatus);
        $allEuAlternativeCategories = CMS_M365LIC_Catalog::eu_comparison_categories();
        $euAlternativeCategories = array_diff_key($allEuAlternativeCategories, ['ai_assistants' => true]);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_nonce('m365lic_settings')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $tab = sanitize_text_field($_POST['tab'] ?? $tab);
                $action = sanitize_text_field($_POST['action'] ?? 'save_settings');

                if ($action === 'repair_tables') {
                    $notice = $this->repair_plugin_tables();
                } else {
                    self::repo()->save_settings($this->build_settings_payload_for_tab($tab, $settings, $billingOptions));
                    $notice = 'Einstellungen gespeichert.';
                }

                $settings = self::repo()->get_settings();
                $stats = self::repo()->get_statistics();
                $tableStatus = $this->get_system_table_status();
                $systemInfo = $this->get_system_info($settings, $tableStatus);
            }
        }

        $csrfToken = self::generate_nonce('m365lic_settings');
        $tabs = [
            'general' => '⚙️ Allgemein',
            'design' => '🎨 Design & Sichtbarkeit',
            'alternatives' => '🔁 Alternativen',
            'limits' => '🚦 Limits',
            'export' => '📄 Export & Recht',
            'system' => '🖥️ System',
        ];
        $alternativeOffers = $this->decode_alternative_settings((string) ($settings['alternatives_json'] ?? '[]'));
        $euAlternativeOffers = $this->decode_eu_alternative_settings((string) ($settings['eu_alternatives_json'] ?? '[]'));
        $euAiAlternativeOffers = $this->decode_eu_alternative_settings((string) ($settings['eu_ai_alternatives_json'] ?? '[]'), 'ai_assistants');
        ?>
        <div class="admin-page-header">
            <div>
                <h2>⚙️ Einstellungen</h2>
                <p>Publicsite, Tageslimits, Bereichs-Defaults und PDF-Ausgabe konfigurieren.</p>
            </div>
        </div>

        <?php if ($notice !== ''): ?>
        <div class="alert alert-success">✅ <?php echo self::esc($notice); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
        <div class="alert alert-error">❌ <?php echo self::esc($error); ?></div>
        <?php endif; ?>

        <div class="m365lic-tabs">
            <?php foreach ($tabs as $key => $label): ?>
            <a href="?page=m365lic-settings&tab=<?php echo self::esc($key); ?>" class="m365lic-tab<?php echo $tab === $key ? ' active' : ''; ?>"><?php echo self::esc($label); ?></a>
            <?php endforeach; ?>
        </div>

        <div class="admin-card m365lic-tab-card m365lic-tab-card--settings">
            <form method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="tab" value="<?php echo self::esc($tab); ?>">

                <?php if ($tab === 'general'): ?>
                    <h3>⚙️ Allgemeine Einstellungen</h3>
                    <div class="m365lic-form-grid m365lic-form-grid--2">
                        <div class="form-group">
                            <label class="form-label" for="page_title">Seitentitel</label>
                            <input class="form-control" id="page_title" type="text" name="page_title" value="<?php echo self::esc((string) ($settings['page_title'] ?? '')); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="route_slug">Route-Slug</label>
                            <input class="form-control" id="route_slug" type="text" name="route_slug" value="<?php echo self::esc((string) ($settings['route_slug'] ?? '')); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="default_currency">Standardwährung</label>
                            <input type="hidden" name="default_currency" value="EUR">
                            <input class="form-control" id="default_currency" type="text" value="EUR / €" readonly>
                            <small class="m365lic-help-text">Das Plugin führt alle Paketpreise und Auswertungen konsistent in Euro.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="default_group_key">Default Spezialgruppen-Key</label>
                            <input class="form-control" id="default_group_key" type="text" name="default_group_key" value="<?php echo self::esc((string) ($settings['default_group_key'] ?? 'partner')); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="page_intro">Einleitungstext</label>
                        <textarea class="form-control" id="page_intro" name="page_intro" rows="3"><?php echo self::esc((string) ($settings['page_intro'] ?? '')); ?></textarea>
                    </div>

                    <div class="m365lic-form-grid m365lic-form-grid--2">
                        <div class="form-group">
                            <label class="form-label" for="default_group_label">Default Spezialgruppen-Label</label>
                            <input class="form-control" id="default_group_label" type="text" name="default_group_label" value="<?php echo self::esc((string) ($settings['default_group_label'] ?? 'Partner / Spezialgruppe')); ?>">
                        </div>
                    </div>

                    <div class="alert m365lic-alert-info">
                        ℹ️ Die persönlichen Member-Einstellungen für EK, Aufschläge, Logo und Report-Texte sind immer pro eingeloggtem Benutzer verfügbar – auch bei Spezialgruppen- oder Reseller-Zuweisung. Die Daten bleiben dabei strikt benutzerbezogen gespeichert.
                    </div>

                    <h3>💳 Default Abrechnung je Bereich</h3>
                    <div class="m365lic-form-grid m365lic-form-grid--3">
                        <div class="form-group">
                            <label class="form-label" for="public_default_billing_cycle">Public</label>
                            <select class="form-control" id="public_default_billing_cycle" name="public_default_billing_cycle">
                                <?php foreach ($billingOptions as $key => $option): ?>
                                <option value="<?php echo self::esc($key); ?>" <?php echo (($settings['public_default_billing_cycle'] ?? 'annual_upfront') === $key) ? 'selected' : ''; ?>><?php echo self::esc((string) $option['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="member_default_billing_cycle">Member</label>
                            <select class="form-control" id="member_default_billing_cycle" name="member_default_billing_cycle">
                                <?php foreach ($billingOptions as $key => $option): ?>
                                <option value="<?php echo self::esc($key); ?>" <?php echo (($settings['member_default_billing_cycle'] ?? 'annual_monthly') === $key) ? 'selected' : ''; ?>><?php echo self::esc((string) $option['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="group_default_billing_cycle">Spezial</label>
                            <select class="form-control" id="group_default_billing_cycle" name="group_default_billing_cycle">
                                <?php foreach ($billingOptions as $key => $option): ?>
                                <option value="<?php echo self::esc($key); ?>" <?php echo (($settings['group_default_billing_cycle'] ?? 'annual_monthly') === $key) ? 'selected' : ''; ?>><?php echo self::esc((string) $option['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php elseif ($tab === 'design'): ?>
                    <h3>🎨 Design & Sichtbarkeit</h3>
                    <div class="alert m365lic-alert-info">
                        ℹ️ Diese Optionen steuern die Public-Darstellung direkt im Lizenzberater, ohne dass du CSS-Dateien anfassen musst.
                    </div>

                    <div class="m365lic-form-grid m365lic-form-grid--3">
                        <div class="form-group">
                            <label class="form-label" for="design_primary_color">Primärfarbe</label>
                            <input class="form-control" id="design_primary_color" type="color" name="design_primary_color" value="<?php echo self::esc((string) ($settings['design_primary_color'] ?? '#2563eb')); ?>">
                            <small class="m365lic-help-text">Buttons, aktive States und Hauptakzente.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="design_primary_dark">Primärfarbe dunkel</label>
                            <input class="form-control" id="design_primary_dark" type="color" name="design_primary_dark" value="<?php echo self::esc((string) ($settings['design_primary_dark'] ?? '#1d4ed8')); ?>">
                            <small class="m365lic-help-text">Hover- und stärkere Akzentzustände.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="design_accent_color">Akzent-Hintergrund</label>
                            <input class="form-control" id="design_accent_color" type="color" name="design_accent_color" value="<?php echo self::esc((string) ($settings['design_accent_color'] ?? '#f3f7fd')); ?>">
                            <small class="m365lic-help-text">Hero- und Flächen-Tint im Public-Frontend.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="design_page_background">Seitenhintergrund</label>
                            <input class="form-control" id="design_page_background" type="color" name="design_page_background" value="<?php echo self::esc((string) ($settings['design_page_background'] ?? '#f8fafc')); ?>">
                            <small class="m365lic-help-text">Hintergrundfarbe der gesamten Public-Seite.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="design_surface_color">Kartenhintergrund</label>
                            <input class="form-control" id="design_surface_color" type="color" name="design_surface_color" value="<?php echo self::esc((string) ($settings['design_surface_color'] ?? '#ffffff')); ?>">
                            <small class="m365lic-help-text">Grundfarbe der Cards und Panels.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="design_text_color">Textfarbe</label>
                            <input class="form-control" id="design_text_color" type="color" name="design_text_color" value="<?php echo self::esc((string) ($settings['design_text_color'] ?? '#0f172a')); ?>">
                            <small class="m365lic-help-text">Primäre Textfarbe für Überschriften und Inhalte.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="design_text_muted_color">Sekundärtext</label>
                            <input class="form-control" id="design_text_muted_color" type="color" name="design_text_muted_color" value="<?php echo self::esc((string) ($settings['design_text_muted_color'] ?? '#64748b')); ?>">
                            <small class="m365lic-help-text">Hilfstexte, Beschreibungen und Meta-Infos.</small>
                        </div>
                    </div>

                    <div class="form-group m365lic-form-group--compact">
                        <label class="form-label" for="design_border_radius">Kartenradius</label>
                        <input class="form-control" id="design_border_radius" type="number" min="8" max="24" step="2" name="design_border_radius" value="<?php echo (int) ($settings['design_border_radius'] ?? 14); ?>">
                        <small class="m365lic-help-text">Steuert die Rundung der Haupt-Cards und UI-Elemente.</small>
                    </div>

                    <h3>👁️ Sichtbare Public-Bereiche</h3>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_hero_panel" value="1" <?php echo !empty($settings['show_hero_panel']) ? 'checked' : ''; ?>>
                            Tech-Checks-Panel im Hero anzeigen
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_hero_badges" value="1" <?php echo !empty($settings['show_hero_badges']) ? 'checked' : ''; ?>>
                            Hero-Badges für Preis, Bereich und Laufzeit anzeigen
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_context_summary" value="1" <?php echo !empty($settings['show_context_summary']) ? 'checked' : ''; ?>>
                            Kontext-Karte in der rechten Sidebar anzeigen
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_addon_overview" value="1" <?php echo !empty($settings['show_addon_overview']) ? 'checked' : ''; ?>>
                            Add-on-Quick-Info in der Sidebar anzeigen
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_legal_card" value="1" <?php echo !empty($settings['show_legal_card']) ? 'checked' : ''; ?>>
                            Hinweis-/Beratungskarte in der Sidebar anzeigen
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="sticky_sidebar" value="1" <?php echo !empty($settings['sticky_sidebar']) ? 'checked' : ''; ?>>
                            Sidebar auf Desktop sticky halten
                        </label>
                    </div>
                <?php elseif ($tab === 'alternatives'): ?>
                    <h3>🔁 Alternativen</h3>
                    <div class="alert m365lic-alert-info">
                        ℹ️ Hier pflegst du getrennt <strong>normale Alternativen</strong>, <strong>EU-Alternativen</strong> und <strong>europäische KI-/Copilot-Alternativen</strong>. Die EU-Daten fließen sowohl in den Public-`EU-Vergleich` als auch in die optionalen `EU-Alternativen` unter der Standard-Auswertung.
                    </div>

                    <div class="m365lic-inline-head">
                        <div>
                            <p class="m365lic-section-kicker">Normale Alternativen</p>
                            <p class="m365lic-help-text">Beispiele für Kategorien: Mail, Storage, Office, Security, Telefonie, Collaboration.</p>
                        </div>
                        <button type="button" class="btn btn-secondary" id="m365licAddAlternativeRow">➕ Alternative hinzufügen</button>
                    </div>

                    <div class="users-table-container">
                        <table class="users-table m365lic-alternatives-table" id="m365licAlternativesTable">
                            <thead>
                                <tr>
                                    <th>Aktiv</th>
                                    <th>Kategorie</th>
                                    <th>Anbieter</th>
                                    <th>Preis 1 Jahr</th>
                                    <th>Preis monatlich</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($alternativeOffers === []): ?>
                                <tr data-alternative-row>
                                    <td><input type="checkbox" name="alternatives[0][is_active]" value="1" checked></td>
                                    <td><input class="form-control" type="text" name="alternatives[0][category]" value="" placeholder="z. B. Mail"></td>
                                    <td><input class="form-control" type="text" name="alternatives[0][provider]" value="" placeholder="z. B. Google Workspace"></td>
                                    <td><input class="form-control" type="text" name="alternatives[0][annual_price]" value="" placeholder="z. B. 5,90"></td>
                                    <td><input class="form-control" type="text" name="alternatives[0][monthly_price]" value="" placeholder="z. B. 7,20"></td>
                                    <td><button type="button" class="btn m365lic-btn-ghost-danger m365lic-remove-alternative-row">Entfernen</button></td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($alternativeOffers as $index => $offer): ?>
                                    <tr data-alternative-row>
                                        <td><input type="checkbox" name="alternatives[<?php echo (int) $index; ?>][is_active]" value="1" <?php echo !empty($offer['is_active']) ? 'checked' : ''; ?>></td>
                                        <td><input class="form-control" type="text" name="alternatives[<?php echo (int) $index; ?>][category]" value="<?php echo self::esc((string) ($offer['category'] ?? '')); ?>" placeholder="z. B. Mail"></td>
                                        <td><input class="form-control" type="text" name="alternatives[<?php echo (int) $index; ?>][provider]" value="<?php echo self::esc((string) ($offer['provider'] ?? '')); ?>" placeholder="z. B. Google Workspace"></td>
                                        <td><input class="form-control" type="text" name="alternatives[<?php echo (int) $index; ?>][annual_price]" value="<?php echo self::esc((string) ($offer['annual_price'] ?? '')); ?>" placeholder="z. B. 5,90"></td>
                                        <td><input class="form-control" type="text" name="alternatives[<?php echo (int) $index; ?>][monthly_price]" value="<?php echo self::esc((string) ($offer['monthly_price'] ?? '')); ?>" placeholder="z. B. 7,20"></td>
                                        <td><button type="button" class="btn m365lic-btn-ghost-danger m365lic-remove-alternative-row">Entfernen</button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="m365lic-help-text">Wenn nur der Jahrespreis gepflegt ist, erscheint die Alternative nur bei Jahres-Laufzeiten. Wenn nur der Monatspreis gepflegt ist, erscheint sie nur bei monatlicher Laufzeit.</small>

                    <hr>

                    <div class="m365lic-inline-head">
                        <div>
                            <p class="m365lic-section-kicker">EU-Alternativen</p>
                            <p class="m365lic-help-text">Diese Anbieter werden im Public-`EU-Vergleich` und unter `EU-Alternativen anzeigen` genutzt.</p>
                        </div>
                        <button type="button" class="btn btn-secondary" id="m365licAddEuAlternativeRow">➕ EU-Alternative hinzufügen</button>
                    </div>

                    <div class="users-table-container">
                        <table class="users-table m365lic-alternatives-table" id="m365licEuAlternativesTable">
                            <thead>
                                <tr>
                                    <th>Aktiv</th>
                                    <th>EU-Kategorie</th>
                                    <th>Anbieter</th>
                                    <th>Fokus / Nutzen</th>
                                    <th>Preis 1 Jahr</th>
                                    <th>Preis monatlich</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($euAlternativeOffers === []): ?>
                                <tr data-alternative-row>
                                    <td><input type="checkbox" name="eu_alternatives[0][is_active]" value="1" checked></td>
                                    <td>
                                        <select class="form-control" name="eu_alternatives[0][category_key]">
                                            <?php foreach ($euAlternativeCategories as $categoryKey => $category): ?>
                                            <option value="<?php echo self::esc((string) $categoryKey); ?>"><?php echo self::esc((string) ($category['label'] ?? $categoryKey)); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input class="form-control" type="text" name="eu_alternatives[0][provider]" value="" placeholder="z. B. Infomaniak"></td>
                                    <td><input class="form-control" type="text" name="eu_alternatives[0][focus]" value="" placeholder="z. B. Datenschutz-Fokus"></td>
                                    <td><input class="form-control" type="text" name="eu_alternatives[0][annual_price]" value="" placeholder="z. B. 7,90"></td>
                                    <td><input class="form-control" type="text" name="eu_alternatives[0][monthly_price]" value="" placeholder="z. B. 7,90"></td>
                                    <td><button type="button" class="btn m365lic-btn-ghost-danger m365lic-remove-alternative-row">Entfernen</button></td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($euAlternativeOffers as $index => $offer): ?>
                                    <tr data-alternative-row>
                                        <td><input type="checkbox" name="eu_alternatives[<?php echo (int) $index; ?>][is_active]" value="1" <?php echo !empty($offer['is_active']) ? 'checked' : ''; ?>></td>
                                        <td>
                                            <select class="form-control" name="eu_alternatives[<?php echo (int) $index; ?>][category_key]">
                                                <?php foreach ($euAlternativeCategories as $categoryKey => $category): ?>
                                                <option value="<?php echo self::esc((string) $categoryKey); ?>" <?php echo (($offer['category_key'] ?? 'core_workspace') === $categoryKey) ? 'selected' : ''; ?>><?php echo self::esc((string) ($category['label'] ?? $categoryKey)); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><input class="form-control" type="text" name="eu_alternatives[<?php echo (int) $index; ?>][provider]" value="<?php echo self::esc((string) ($offer['provider'] ?? '')); ?>" placeholder="z. B. Infomaniak"></td>
                                        <td><input class="form-control" type="text" name="eu_alternatives[<?php echo (int) $index; ?>][focus]" value="<?php echo self::esc((string) ($offer['focus'] ?? '')); ?>" placeholder="z. B. Datenschutz-Fokus"></td>
                                        <td><input class="form-control" type="text" name="eu_alternatives[<?php echo (int) $index; ?>][annual_price]" value="<?php echo self::esc((string) ($offer['annual_price'] ?? '')); ?>" placeholder="z. B. 7,90"></td>
                                        <td><input class="form-control" type="text" name="eu_alternatives[<?php echo (int) $index; ?>][monthly_price]" value="<?php echo self::esc((string) ($offer['monthly_price'] ?? '')); ?>" placeholder="z. B. 7,90"></td>
                                        <td><button type="button" class="btn m365lic-btn-ghost-danger m365lic-remove-alternative-row">Entfernen</button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="m365lic-help-text">EU-Alternativen dürfen auch ohne Preis gespeichert werden. Im Frontend erscheinen sie dann mit Preisstatus <strong>offen</strong>.</small>

                    <hr>

                    <div class="m365lic-inline-head">
                        <div>
                            <p class="m365lic-section-kicker">Europäische KI- & Copilot-Alternativen</p>
                            <p class="m365lic-help-text">Diese Liste erscheint, sobald in einer Bedarfsgruppe Copilot-/KI-Funktionen ausgewählt wurden.</p>
                        </div>
                        <button type="button" class="btn btn-secondary" id="m365licAddEuAiAlternativeRow">➕ KI-Alternative hinzufügen</button>
                    </div>

                    <div class="users-table-container">
                        <table class="users-table m365lic-alternatives-table" id="m365licEuAiAlternativesTable">
                            <thead>
                                <tr>
                                    <th>Aktiv</th>
                                    <th>Anbieter</th>
                                    <th>Fokus / Nutzen</th>
                                    <th>Preis 1 Jahr</th>
                                    <th>Preis monatlich</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($euAiAlternativeOffers === []): ?>
                                <tr data-alternative-row>
                                    <td>
                                        <input type="checkbox" name="eu_ai_alternatives[0][is_active]" value="1" checked>
                                        <input type="hidden" name="eu_ai_alternatives[0][category_key]" value="ai_assistants">
                                    </td>
                                    <td><input class="form-control" type="text" name="eu_ai_alternatives[0][provider]" value="" placeholder="z. B. Mistral Le Chat Enterprise"></td>
                                    <td><input class="form-control" type="text" name="eu_ai_alternatives[0][focus]" value="" placeholder="z. B. Europäischer KI-Assistent für Wissensarbeit"></td>
                                    <td><input class="form-control" type="text" name="eu_ai_alternatives[0][annual_price]" value="" placeholder="optional"></td>
                                    <td><input class="form-control" type="text" name="eu_ai_alternatives[0][monthly_price]" value="" placeholder="optional"></td>
                                    <td><button type="button" class="btn m365lic-btn-ghost-danger m365lic-remove-alternative-row">Entfernen</button></td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($euAiAlternativeOffers as $index => $offer): ?>
                                    <tr data-alternative-row>
                                        <td>
                                            <input type="checkbox" name="eu_ai_alternatives[<?php echo (int) $index; ?>][is_active]" value="1" <?php echo !empty($offer['is_active']) ? 'checked' : ''; ?>>
                                            <input type="hidden" name="eu_ai_alternatives[<?php echo (int) $index; ?>][category_key]" value="ai_assistants">
                                        </td>
                                        <td><input class="form-control" type="text" name="eu_ai_alternatives[<?php echo (int) $index; ?>][provider]" value="<?php echo self::esc((string) ($offer['provider'] ?? '')); ?>" placeholder="z. B. Mistral Le Chat Enterprise"></td>
                                        <td><input class="form-control" type="text" name="eu_ai_alternatives[<?php echo (int) $index; ?>][focus]" value="<?php echo self::esc((string) ($offer['focus'] ?? '')); ?>" placeholder="z. B. Europäischer KI-Assistent für Wissensarbeit"></td>
                                        <td><input class="form-control" type="text" name="eu_ai_alternatives[<?php echo (int) $index; ?>][annual_price]" value="<?php echo self::esc((string) ($offer['annual_price'] ?? '')); ?>" placeholder="optional"></td>
                                        <td><input class="form-control" type="text" name="eu_ai_alternatives[<?php echo (int) $index; ?>][monthly_price]" value="<?php echo self::esc((string) ($offer['monthly_price'] ?? '')); ?>" placeholder="optional"></td>
                                        <td><button type="button" class="btn m365lic-btn-ghost-danger m365lic-remove-alternative-row">Entfernen</button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="m365lic-help-text">Für KI-Alternativen sind Preise optional. So können Copilot-Gegenoptionen schon sichtbar sein, auch wenn die Konditionen noch offen sind.</small>
                <?php elseif ($tab === 'limits'): ?>
                    <h3>🚦 Limits & Upsell</h3>
                    <div class="alert m365lic-alert-info">
                        ℹ️ Standardmäßig sind öffentliche Auswertungen pro Tag bewusst eng begrenzt. Mitglieder und Spezialgruppen können höhere Limits erhalten.
                    </div>
                    <div class="m365lic-form-grid m365lic-form-grid--3">
                        <div class="form-group">
                            <label class="form-label">Public Auswertungen/Tag</label>
                            <input class="form-control" type="number" min="1" name="public_daily_limit" value="<?php echo (int) ($settings['public_daily_limit'] ?? 2); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Member Auswertungen/Tag</label>
                            <input class="form-control" type="number" min="1" name="member_daily_limit" value="<?php echo (int) ($settings['member_daily_limit'] ?? 10); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Spezial Auswertungen/Tag</label>
                            <input class="form-control" type="number" min="1" name="group_daily_limit" value="<?php echo (int) ($settings['group_daily_limit'] ?? 25); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Public PDF/Tag</label>
                            <input class="form-control" type="number" min="1" name="public_pdf_daily_limit" value="<?php echo (int) ($settings['public_pdf_daily_limit'] ?? 2); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Member PDF/Tag</label>
                            <input class="form-control" type="number" min="1" name="member_pdf_daily_limit" value="<?php echo (int) ($settings['member_pdf_daily_limit'] ?? 10); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Spezial PDF/Tag</label>
                            <input class="form-control" type="number" min="1" name="group_pdf_daily_limit" value="<?php echo (int) ($settings['group_pdf_daily_limit'] ?? 25); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="upgrade_url">Upgrade-/Kontakt-URL</label>
                        <input class="form-control" id="upgrade_url" type="text" name="upgrade_url" value="<?php echo self::esc((string) ($settings['upgrade_url'] ?? '/kontakt')); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="limit_notice">Limit-Hinweistext</label>
                        <textarea class="form-control" id="limit_notice" name="limit_notice" rows="3"><?php echo self::esc((string) ($settings['limit_notice'] ?? '')); ?></textarea>
                    </div>
                <?php elseif ($tab === 'export'): ?>
                    <h3>📄 Export & Recht</h3>
                    <div class="form-group">
                        <label class="form-label" for="legal_note">Rechtlicher Hinweis</label>
                        <textarea class="form-control" id="legal_note" name="legal_note" rows="4"><?php echo self::esc((string) ($settings['legal_note'] ?? '')); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="pdf_footer">PDF-Footer</label>
                        <textarea class="form-control" id="pdf_footer" name="pdf_footer" rows="3"><?php echo self::esc((string) ($settings['pdf_footer'] ?? '')); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_missing_price_hint" value="1" <?php echo !empty($settings['show_missing_price_hint']) ? 'checked' : ''; ?>>
                            Hinweis anzeigen, wenn Preise im Paketkatalog fehlen
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_source_notes" value="1" <?php echo !empty($settings['show_source_notes']) ? 'checked' : ''; ?>>
                            Source-/Pricing-Notizen in der Auswertung anzeigen
                        </label>
                    </div>
                <?php else: ?>
                    <h3>🖥️ System-Informationen</h3>
                    <div class="m365lic-admin-grid">
                        <div class="info-card">
                            <h4>Plugin</h4>
                            <ul class="info-list">
                                <li><strong>Version:</strong> <?php echo self::esc(CMS_M365LIC_VERSION); ?></li>
                                <li><strong>DB-Version:</strong> <?php echo self::esc(CMS_M365LIC_DB_VERSION); ?></li>
                                <li><strong>Route:</strong> /<?php echo self::esc((string) ($settings['route_slug'] ?? 'm365-lizenzberater')); ?></li>
                                <li><strong>Standardwährung:</strong> <?php echo self::esc((string) ($settings['default_currency'] ?? 'EUR')); ?></li>
                                <li><strong>Öffentliche URL:</strong> <?php echo self::esc((string) ($systemInfo['public_url'] ?? '')); ?></li>
                            </ul>
                        </div>
                        <div class="info-card">
                            <h4>Katalog</h4>
                            <ul class="info-list">
                                <li><strong>Pakete gesamt:</strong> <?php echo (int) ($stats['packages_total'] ?? 0); ?></li>
                                <li><strong>Aktive Pakete:</strong> <?php echo (int) ($stats['packages_active'] ?? 0); ?></li>
                                <li><strong>Mit Preis:</strong> <?php echo (int) ($stats['packages_with_prices'] ?? 0); ?></li>
                                <li><strong>Features:</strong> <?php echo (int) ($stats['feature_total'] ?? 0); ?></li>
                                <li><strong>Presets:</strong> <?php echo (int) ($stats['preset_total'] ?? 0); ?></li>
                            </ul>
                        </div>
                        <div class="info-card">
                            <h4>Admin-Kontrolle</h4>
                            <ul class="info-list">
                                <li><strong>Member-Einstellungen:</strong> immer aktiv · pro Benutzer getrennt</li>
                                <li><strong>Spezialgruppen:</strong> <?php echo (int) ($stats['special_groups_total'] ?? 0); ?></li>
                                <li><strong>Spezialbenutzer:</strong> <?php echo (int) ($stats['special_users_total'] ?? 0); ?></li>
                                <li><strong>Profile mit EK/Branding:</strong> <?php echo (int) ($systemInfo['user_profiles_count'] ?? 0); ?></li>
                                <li><strong>Individuelle Paket-EKs:</strong> <?php echo (int) ($systemInfo['user_package_costs_count'] ?? 0); ?></li>
                            </ul>
                        </div>
                        <div class="info-card">
                            <h4>Persistenz</h4>
                            <ul class="info-list">
                                <li><strong>Settings-Einträge:</strong> <?php echo (int) ($systemInfo['settings_count'] ?? 0); ?></li>
                                <li><strong>Core-Settings-Tabelle:</strong> <?php echo self::esc((string) ($systemInfo['core_settings_table'] ?? 'n/a')); ?></li>
                                <li><strong>Core-Key-Spalte:</strong> <?php echo self::esc((string) ($systemInfo['core_settings_key'] ?? 'n/a')); ?></li>
                                <li><strong>Core-Value-Spalte:</strong> <?php echo self::esc((string) ($systemInfo['core_settings_value'] ?? 'n/a')); ?></li>
                                <li><strong>DB-Version-Key:</strong> <?php echo !empty($systemInfo['db_version_key_present']) ? 'vorhanden' : 'fehlt'; ?></li>
                            </ul>
                        </div>
                    </div>

                    <div class="admin-card m365lic-system-card">
                        <h4>🗃️ Tabellenstatus</h4>
                        <div class="users-table-container">
                            <table class="users-table">
                                <thead>
                                    <tr>
                                        <th>Tabelle</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tableStatus as $table => $exists): ?>
                                    <tr>
                                        <td><?php echo self::esc($table); ?></td>
                                        <td><span class="status-badge <?php echo $exists ? 'active' : 'danger'; ?>"><?php echo $exists ? 'Vorhanden' : 'Fehlt'; ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="m365lic-danger-note m365lic-danger-note--layout">
                            <div>
                                <strong>Reparaturfunktion</strong><br>
                                Erstellt fehlende Plugin-Tabellen neu, ergänzt Defaults ohne bestehende Admin-Werte zu überschreiben und synchronisiert den DB-Version-Key.
                            </div>
                            <button type="submit" class="btn btn-secondary" name="action" value="repair_tables">🔄 Tabellen reparieren</button>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($tab !== 'system'): ?>
                <input type="hidden" name="action" value="save_settings">
                <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    /**
     * @return array<string,bool>
     */
    private function get_system_table_status(): array
    {
        $tables = [
            'settings',
            'm365lic_packages',
            'm365lic_settings',
            'm365lic_usage_limits',
            'm365lic_special_groups',
            'm365lic_special_users',
            'm365lic_user_profiles',
            'm365lic_user_package_costs',
        ];

        $status = [];

        try {
            $db = \CMS\Database::instance();
            $prefix = $db->getPrefix();
            foreach ($tables as $table) {
                $stmt = $db->prepare(
                    'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
                );
                $stmt->execute([$prefix . $table]);
                $status[$prefix . $table] = (int) ($stmt->fetchColumn() ?: 0) > 0;
            }
        } catch (\Throwable $e) {
            foreach ($tables as $table) {
                $status[$table] = false;
            }
        }

        return $status;
    }

    /**
     * @param array<string,string> $settings
     * @param array<string,bool> $tableStatus
     * @return array<string,mixed>
     */
    private function get_system_info(array $settings, array $tableStatus): array
    {
        $info = [
            'public_url' => '/' . trim((string) ($settings['route_slug'] ?? 'm365-lizenzberater'), '/'),
            'settings_count' => 0,
            'user_profiles_count' => 0,
            'user_package_costs_count' => 0,
            'core_settings_table' => 'n/a',
            'core_settings_key' => 'n/a',
            'core_settings_value' => 'n/a',
            'db_version_key_present' => false,
            'existing_tables' => count(array_filter($tableStatus)),
            'required_tables' => count($tableStatus),
        ];

        try {
            $db = \CMS\Database::instance();
            $prefix = $db->getPrefix();

            $countStmt = $db->prepare("SELECT COUNT(*) FROM {$prefix}m365lic_settings");
            $countStmt->execute();
            $info['settings_count'] = (int) ($countStmt->fetchColumn() ?: 0);

            if (!empty($tableStatus[$prefix . 'm365lic_user_profiles'])) {
                $profileCountStmt = $db->prepare("SELECT COUNT(*) FROM {$prefix}m365lic_user_profiles");
                $profileCountStmt->execute();
                $info['user_profiles_count'] = (int) ($profileCountStmt->fetchColumn() ?: 0);
            }

            if (!empty($tableStatus[$prefix . 'm365lic_user_package_costs'])) {
                $costCountStmt = $db->prepare("SELECT COUNT(*) FROM {$prefix}m365lic_user_package_costs");
                $costCountStmt->execute();
                $info['user_package_costs_count'] = (int) ($costCountStmt->fetchColumn() ?: 0);
            }

            $columnsStmt = $db->getPdo()->query("SHOW COLUMNS FROM {$prefix}settings");
            $columns = $columnsStmt !== false
                ? array_map(static fn(array $column): string => (string) ($column['Field'] ?? ''), $columnsStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [])
                : [];

            $info['core_settings_table'] = $prefix . 'settings';
            $info['core_settings_key'] = in_array('option_name', $columns, true) ? 'option_name' : (in_array('setting_key', $columns, true) ? 'setting_key' : 'unbekannt');
            $info['core_settings_value'] = in_array('option_value', $columns, true) ? 'option_value' : (in_array('setting_value', $columns, true) ? 'setting_value' : 'unbekannt');

            if ($info['core_settings_key'] !== 'unbekannt' && $info['core_settings_value'] !== 'unbekannt') {
                $stmt = $db->prepare("SELECT {$info['core_settings_value']} FROM {$prefix}settings WHERE {$info['core_settings_key']} = ? LIMIT 1");
                $stmt->execute(['m365lic_db_version']);
                $info['db_version_key_present'] = $stmt->fetchColumn() !== false;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return $info;
    }

    /**
     * @param array<string,string> $settings
     * @param array<string,array<string,mixed>> $billingOptions
     * @return array<string,string>
     */
    private function build_settings_payload_for_tab(string $tab, array $settings, array $billingOptions): array
    {
        return match ($tab) {
            'general' => [
                'page_title' => trim((string) ($_POST['page_title'] ?? ($settings['page_title'] ?? 'Microsoft 365 Lizenzberater'))),
                'page_intro' => trim((string) ($_POST['page_intro'] ?? ($settings['page_intro'] ?? ''))),
                'route_slug' => $this->normalize_route_slug((string) ($_POST['route_slug'] ?? ($settings['route_slug'] ?? 'm365-lizenzberater'))),
                'default_currency' => 'EUR',
                'default_group_key' => trim((string) ($_POST['default_group_key'] ?? ($settings['default_group_key'] ?? 'partner'))),
                'default_group_label' => trim((string) ($_POST['default_group_label'] ?? ($settings['default_group_label'] ?? 'Partner / Spezialgruppe'))),
                'allow_member_self_service' => '1',
                'public_default_billing_cycle' => $this->normalize_billing_cycle((string) ($_POST['public_default_billing_cycle'] ?? ($settings['public_default_billing_cycle'] ?? 'annual_upfront')), $billingOptions, 'annual_upfront'),
                'member_default_billing_cycle' => $this->normalize_billing_cycle((string) ($_POST['member_default_billing_cycle'] ?? ($settings['member_default_billing_cycle'] ?? 'annual_monthly')), $billingOptions, 'annual_monthly'),
                'group_default_billing_cycle' => $this->normalize_billing_cycle((string) ($_POST['group_default_billing_cycle'] ?? ($settings['group_default_billing_cycle'] ?? 'annual_monthly')), $billingOptions, 'annual_monthly'),
            ],
            'design' => [
                'design_primary_color' => $this->normalize_hex_color((string) ($_POST['design_primary_color'] ?? ($settings['design_primary_color'] ?? '#2563eb')), '#2563eb'),
                'design_primary_dark' => $this->normalize_hex_color((string) ($_POST['design_primary_dark'] ?? ($settings['design_primary_dark'] ?? '#1d4ed8')), '#1d4ed8'),
                'design_accent_color' => $this->normalize_hex_color((string) ($_POST['design_accent_color'] ?? ($settings['design_accent_color'] ?? '#f3f7fd')), '#f3f7fd'),
                'design_page_background' => $this->normalize_hex_color((string) ($_POST['design_page_background'] ?? ($settings['design_page_background'] ?? '#f8fafc')), '#F8FAFC'),
                'design_surface_color' => $this->normalize_hex_color((string) ($_POST['design_surface_color'] ?? ($settings['design_surface_color'] ?? '#ffffff')), '#FFFFFF'),
                'design_text_color' => $this->normalize_hex_color((string) ($_POST['design_text_color'] ?? ($settings['design_text_color'] ?? '#0f172a')), '#0F172A'),
                'design_text_muted_color' => $this->normalize_hex_color((string) ($_POST['design_text_muted_color'] ?? ($settings['design_text_muted_color'] ?? '#64748b')), '#64748B'),
                'design_border_radius' => (string) max(8, min(24, (int) ($_POST['design_border_radius'] ?? ($settings['design_border_radius'] ?? 14)))),
                'show_hero_panel' => !empty($_POST['show_hero_panel']) ? '1' : '0',
                'show_hero_badges' => !empty($_POST['show_hero_badges']) ? '1' : '0',
                'show_context_summary' => !empty($_POST['show_context_summary']) ? '1' : '0',
                'show_addon_overview' => !empty($_POST['show_addon_overview']) ? '1' : '0',
                'show_legal_card' => !empty($_POST['show_legal_card']) ? '1' : '0',
                'sticky_sidebar' => !empty($_POST['sticky_sidebar']) ? '1' : '0',
            ],
            'alternatives' => [
                'alternatives_json' => json_encode($this->normalize_alternative_settings($_POST['alternatives'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'eu_alternatives_json' => json_encode($this->normalize_eu_alternative_settings($_POST['eu_alternatives'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'eu_ai_alternatives_json' => json_encode($this->normalize_eu_alternative_settings($_POST['eu_ai_alternatives'] ?? [], 'ai_assistants'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ],
            'limits' => [
                'public_daily_limit' => (string) max(1, (int) ($_POST['public_daily_limit'] ?? ($settings['public_daily_limit'] ?? 2))),
                'member_daily_limit' => (string) max(1, (int) ($_POST['member_daily_limit'] ?? ($settings['member_daily_limit'] ?? 10))),
                'group_daily_limit' => (string) max(1, (int) ($_POST['group_daily_limit'] ?? ($settings['group_daily_limit'] ?? 25))),
                'public_pdf_daily_limit' => (string) max(1, (int) ($_POST['public_pdf_daily_limit'] ?? ($settings['public_pdf_daily_limit'] ?? 2))),
                'member_pdf_daily_limit' => (string) max(1, (int) ($_POST['member_pdf_daily_limit'] ?? ($settings['member_pdf_daily_limit'] ?? 10))),
                'group_pdf_daily_limit' => (string) max(1, (int) ($_POST['group_pdf_daily_limit'] ?? ($settings['group_pdf_daily_limit'] ?? 25))),
                'upgrade_url' => $this->normalize_upgrade_url((string) ($_POST['upgrade_url'] ?? ($settings['upgrade_url'] ?? '/kontakt'))),
                'limit_notice' => trim((string) ($_POST['limit_notice'] ?? ($settings['limit_notice'] ?? ''))),
            ],
            'export' => [
                'legal_note' => trim((string) ($_POST['legal_note'] ?? ($settings['legal_note'] ?? ''))),
                'pdf_footer' => trim((string) ($_POST['pdf_footer'] ?? ($settings['pdf_footer'] ?? ''))),
                'show_missing_price_hint' => !empty($_POST['show_missing_price_hint']) ? '1' : '0',
                'show_source_notes' => !empty($_POST['show_source_notes']) ? '1' : '0',
            ],
            default => [],
        };
    }

    /**
     * @param array<string,array<string,mixed>> $billingOptions
     */
    private function normalize_billing_cycle(string $value, array $billingOptions, string $fallback): string
    {
        return array_key_exists($value, $billingOptions) ? $value : $fallback;
    }

    private function repair_plugin_tables(): string
    {
        $messages = [];

        try {
            if (
                class_exists('CMS\\Database')
                && class_exists('CMS\\SchemaManager')
                && method_exists(\CMS\Database::instance(), 'repairTables')
                && method_exists('CMS\\SchemaManager', 'getFlagFile')
            ) {
                try {
                    \CMS\Database::instance()->repairTables();
                    $messages[] = 'Core-Schema-Reparatur ausgeführt';
                } catch (\Throwable $e) {
                    $messages[] = 'Core-Schema-Reparatur nicht nötig/übersprungen';
                }
            }

            CMS_M365LIC_Installer::install();
            $messages[] = 'Plugin-Tabellen und Defaults synchronisiert';

            return implode(' · ', $messages);
        } catch (\Throwable $e) {
            return 'Tabellenreparatur gestartet, aber nicht vollständig bestätigt: ' . $e->getMessage();
        }
    }

    private function normalize_hex_color(string $value, string $fallback): string
    {
        $value = trim($value);
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? strtoupper($value) : $fallback;
    }

    private function normalize_route_slug(string $value): string
    {
        $value = trim($value);
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9\-\/]+/', '-', $value) ?: 'm365-lizenzberater';
        $value = trim($value, '/-');

        return $value !== '' ? $value : 'm365-lizenzberater';
    }

    private function normalize_upgrade_url(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '/kontakt';
        }

        if (str_starts_with($value, '/')) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_URL) ? $value : '/kontakt';
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function decode_alternative_settings(string $json): array
    {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $this->normalize_alternative_settings($decoded) : [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function decode_eu_alternative_settings(string $json, ?string $fixedCategoryKey = null): array
    {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $this->normalize_eu_alternative_settings($decoded, $fixedCategoryKey) : [];
    }

    /**
     * @param mixed $offers
     * @return array<int,array<string,mixed>>
     */
    private function normalize_alternative_settings(mixed $offers): array
    {
        if (!is_array($offers)) {
            return [];
        }

        $normalized = [];
        foreach ($offers as $offer) {
            if (!is_array($offer)) {
                continue;
            }

            $category = trim((string) ($offer['category'] ?? ''));
            $provider = trim((string) ($offer['provider'] ?? ''));
            $annualPrice = $this->normalize_nullable_money_value($offer['annual_price'] ?? null);
            $monthlyPrice = $this->normalize_nullable_money_value($offer['monthly_price'] ?? null);
            $isActive = !empty($offer['is_active']) ? 1 : 0;

            if ($category === '' || $provider === '' || ($annualPrice === null && $monthlyPrice === null)) {
                continue;
            }

            $normalized[] = [
                'category' => $category,
                'provider' => $provider,
                'annual_price' => $annualPrice,
                'monthly_price' => $monthlyPrice,
                'is_active' => $isActive,
            ];
        }

        return $normalized;
    }

    /**
     * @param mixed $offers
     * @return array<int,array<string,mixed>>
     */
    private function normalize_eu_alternative_settings(mixed $offers, ?string $fixedCategoryKey = null): array
    {
        if (!is_array($offers)) {
            return [];
        }

        $categories = CMS_M365LIC_Catalog::eu_comparison_categories();
        $normalized = [];

        foreach ($offers as $offer) {
            if (!is_array($offer)) {
                continue;
            }

            $categoryKey = $fixedCategoryKey ?? trim((string) ($offer['category_key'] ?? 'core_workspace'));
            if (!isset($categories[$categoryKey])) {
                continue;
            }

            $provider = trim((string) ($offer['provider'] ?? ''));
            $focus = trim((string) ($offer['focus'] ?? ''));
            $annualPrice = $this->normalize_nullable_money_value($offer['annual_price'] ?? null);
            $monthlyPrice = $this->normalize_nullable_money_value($offer['monthly_price'] ?? null);
            $isActive = !empty($offer['is_active']) ? 1 : 0;

            if ($provider === '') {
                continue;
            }

            $normalized[] = [
                'category_key' => $categoryKey,
                'provider' => $provider,
                'focus' => $focus,
                'annual_price' => $annualPrice,
                'monthly_price' => $monthlyPrice,
                'is_active' => $isActive,
            ];
        }

        return $normalized;
    }

    private function normalize_nullable_money_value(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return null;
        }

        $normalized = str_replace(',', '.', $stringValue);
        if (!is_numeric($normalized)) {
            return null;
        }

        return number_format((float) $normalized, 2, '.', '');
    }
}
