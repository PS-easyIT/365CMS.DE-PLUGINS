<?php
/**
 * Admin dashboard and settings view for CMS M365 Price Tracker.
 *
 * @package CMS_M365PRICETRACKER
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$checked = static fn(string $key): string => (($settings[$key] ?? '0') === '1') ? ' checked' : '';
$selected = static fn(string $key, string $value): string => ((string) ($settings[$key] ?? '') === $value) ? ' selected' : '';
$tabUrl = static fn(string $key): string => '?page=' . rawurlencode(CMS_M365PRICETRACKER_Admin_Menu::PAGE_SLUG) . '&tab=' . rawurlencode($key);
$renderToggle = static function (string $key, string $label, string $help = '') use ($checked, $esc): void {
    echo '<label class="checkbox-label">';
    echo '<input type="hidden" name="' . $esc($key) . '" value="0">';
    echo '<input type="checkbox" name="' . $esc($key) . '" value="1"' . $checked($key) . '> ' . $esc($label);
    if ($help !== '') {
        echo '<small class="form-text">' . $esc($help) . '</small>';
    }
    echo '</label>';
};
$renderNumber = static function (string $key, string $label, int $min, int $max, string $help = '', int $step = 1) use ($settings, $esc): void {
    echo '<div class="form-group">';
    echo '<label class="form-label" for="' . $esc($key) . '">' . $esc($label) . '</label>';
    echo '<input type="number" id="' . $esc($key) . '" name="' . $esc($key) . '" class="form-control" value="' . (int) ($settings[$key] ?? 0) . '" min="' . (int) $min . '" max="' . (int) $max . '" step="' . (int) $step . '">';
    if ($help !== '') {
        echo '<small class="form-text">' . $esc($help) . '</small>';
    }
    echo '</div>';
};
$renderColor = static function (string $key, string $label, string $help = '') use ($settings, $esc): void {
    $value = (string) ($settings[$key] ?? '#000000');
    echo '<div class="form-group">';
    echo '<label class="form-label" for="' . $esc($key) . '">' . $esc($label) . '</label>';
    echo '<div class="m365price-admin-color-row">';
    echo '<input type="color" id="' . $esc($key) . '" name="' . $esc($key) . '" class="form-control" value="' . $esc($value) . '">';
    echo '<input type="text" name="' . $esc($key) . '_text" class="form-control" value="' . $esc($value) . '" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7" onchange="this.previousElementSibling.value=this.value">';
    echo '</div>';
    if ($help !== '') {
        echo '<small class="form-text">' . $esc($help) . '</small>';
    }
    echo '</div>';
};
?>

<div class="admin-page-header">
    <div>
        <h2>📈 M365 Price Tracker</h2>
        <p>Layout, Farben, Bereiche und Karten der Publicseite steuern.</p>
    </div>
    <div class="header-actions">
        <a href="<?php echo $esc($publicUrl); ?>" class="btn btn-primary">👁️ Publicseite öffnen</a>
    </div>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo $esc($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo $esc($error); ?></div>
<?php endif; ?>

<div class="m365price-admin-tabs">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="<?php echo $esc($tabUrl((string) $key)); ?>" class="m365price-admin-tab<?php echo $tab === $key ? ' active' : ''; ?>">
        <?php echo $esc($label); ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;">
    <?php if ($tab === 'status'): ?>
        <h3>📊 Status</h3>
        <div class="dashboard-grid">
            <?php foreach ($stats as $stat): ?>
            <div class="stat-card">
                <div class="stat-icon"><?php echo $esc($stat['icon']); ?></div>
                <div class="stat-number"><?php echo $esc($stat['value']); ?></div>
                <div class="stat-label"><?php echo $esc($stat['label']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <hr style="border:none;border-top:1px solid #f1f5f9;margin:1.5rem 0;">
        <h3>🗂️ Datenpaket</h3>
        <p style="color:#64748b;margin-top:0;">Diese Dateien werden direkt aus <code>cms-m365price-tracker/data/</code> gelesen.</p>
        <div class="users-table-container">
            <table class="users-table">
                <thead><tr><th>Datei</th><th>Status</th><th>Größe</th><th>Geändert</th></tr></thead>
                <tbody>
                    <?php foreach ($dataFiles as $file): ?>
                    <tr>
                        <td><code><?php echo $esc($file['name']); ?></code></td>
                        <td><?php echo !empty($file['exists']) ? '<span class="status-badge active">✅ vorhanden</span>' : '<span class="status-badge danger">❌ fehlt</span>'; ?></td>
                        <td><?php echo number_format((int) $file['size'], 0, ',', '.'); ?> Bytes</td>
                        <td><?php echo $esc($file['modified']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <hr style="border:none;border-top:1px solid #f1f5f9;margin:1.5rem 0;">
        <h3>ℹ️ Hinweise</h3>
        <ul class="info-list">
            <li><strong>Plugin-Version:</strong> <?php echo $esc($pluginVersion); ?></li>
            <li><strong>Public Route:</strong> <code><?php echo $esc($publicUrl); ?></code></li>
            <li><strong>Header/Footer-Abstand:</strong> Admin-Werte werden serverseitig auf maximal 25px begrenzt.</li>
        </ul>
    <?php elseif ($tab === 'layout'): ?>
        <h3>📐 Layout & Abstände</h3>
        <form method="POST" class="admin-form">
            <input type="hidden" name="action" value="save_settings">
            <input type="hidden" name="csrf_token" value="<?php echo $esc($csrfToken); ?>">

            <div class="m365price-admin-grid">
                <div class="form-group">
                    <label class="form-label" for="content_mode">Inhaltsmodus</label>
                    <select id="content_mode" name="content_mode" class="form-control">
                        <option value="full"<?php echo $selected('content_mode', 'full'); ?>>Vollständig</option>
                        <option value="compact"<?php echo $selected('content_mode', 'compact'); ?>>Kompakt</option>
                        <option value="chart_only"<?php echo $selected('content_mode', 'chart_only'); ?>>Nur Chart</option>
                        <option value="custom"<?php echo $selected('content_mode', 'custom'); ?>>Benutzerdefiniert</option>
                    </select>
                    <small class="form-text">„Nur Chart“ blendet alle Bereiche unter dem Preisverlauf aus.</small>
                </div>
                <div class="form-group">
                    <label class="form-label" for="layout_variant">Layoutvariante</label>
                    <select id="layout_variant" name="layout_variant" class="form-control">
                        <option value="sidebar"<?php echo $selected('layout_variant', 'sidebar'); ?>>Formular + rechte Ergebnis-Spalte</option>
                        <option value="single"<?php echo $selected('layout_variant', 'single'); ?>>Einspaltig</option>
                        <option value="wide"<?php echo $selected('layout_variant', 'wide'); ?>>Breit / Dashboard</option>
                    </select>
                </div>
                <?php $renderNumber('content_max_width', 'Maximale Content-Breite', 720, 1800, 'Pixelbreite des Plugin-Contents.'); ?>
                <?php $renderNumber('content_padding_x', 'Innenabstand links/rechts', 0, 120, 'Horizontaler Content-Abstand in px.'); ?>
                <?php $renderNumber('theme_header_gap', 'Abstand Theme-Header → Plugin', 0, 25, 'Maximal 25px.'); ?>
                <?php $renderNumber('theme_footer_gap', 'Abstand Plugin → Theme-Footer', 0, 25, 'Maximal 25px.'); ?>
                <?php $renderNumber('content_padding_top', 'Oberer Innenabstand', 0, 25, 'Maximal 25px.'); ?>
                <?php $renderNumber('content_padding_bottom', 'Unterer Innenabstand', 0, 25, 'Maximal 25px.'); ?>
                <?php $renderNumber('section_gap', 'Abstand zwischen Bereichen', 0, 96); ?>
                <?php $renderNumber('card_gap', 'Abstand innerhalb Karten', 0, 96); ?>
                <?php $renderNumber('card_padding', 'Karten-Innenabstand', 0, 96); ?>
                <?php $renderNumber('card_radius', 'Karten-Rundung', 0, 32); ?>
                <?php $renderNumber('chart_height', 'Chart-Höhe', 220, 620); ?>
                <div class="form-group">
                    <label class="form-label" for="filter_columns">Spalten der Auswahlbereiche</label>
                    <select id="filter_columns" name="filter_columns" class="form-control">
                        <option value="1"<?php echo $selected('filter_columns', '1'); ?>>1 Spalte</option>
                        <option value="2"<?php echo $selected('filter_columns', '2'); ?>>2 Spalten</option>
                        <option value="3"<?php echo $selected('filter_columns', '3'); ?>>3 Spalten</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">💾 Layout speichern</button>
        </form>
    <?php elseif ($tab === 'colors'): ?>
        <h3>🎨 Farben</h3>
        <form method="POST" class="admin-form">
            <input type="hidden" name="action" value="save_settings">
            <input type="hidden" name="csrf_token" value="<?php echo $esc($csrfToken); ?>">
            <div class="m365price-admin-grid">
                <?php $renderColor('primary_color', 'Primärfarbe', 'Buttons, aktive Elemente, Score-Balken.'); ?>
                <?php $renderColor('accent_color', 'Akzentfarbe', 'Diagramm- und Highlight-Farbe.'); ?>
                <?php $renderColor('surface_color', 'Karten-Hintergrund'); ?>
                <?php $renderColor('muted_surface_color', 'Abgesetzter Hintergrund'); ?>
                <?php $renderColor('text_color', 'Textfarbe'); ?>
                <?php $renderColor('muted_text_color', 'Sekundäre Textfarbe'); ?>
                <?php $renderColor('border_color', 'Rahmenfarbe'); ?>
            </div>
            <button type="submit" class="btn btn-primary">💾 Farben speichern</button>
        </form>
    <?php elseif ($tab === 'sections'): ?>
        <h3>🧩 Bereiche & Auswahlfelder</h3>
        <form method="POST" class="admin-form">
            <input type="hidden" name="action" value="save_settings">
            <input type="hidden" name="csrf_token" value="<?php echo $esc($csrfToken); ?>">
            <div class="alert" style="background:#dbeafe;color:#1e40af;border-left:4px solid #3b82f6;margin-bottom:1.25rem;">
                ℹ️ Der Inhaltsmodus „Nur Chart“ aus dem Layout-Tab überschreibt diese Bereichsauswahl im Frontend.
            </div>
            <div class="m365price-admin-check-grid">
                <?php $renderToggle('show_hero', 'Hero anzeigen'); ?>
                <?php $renderToggle('show_hero_cta', 'Hero-CTA anzeigen'); ?>
                <?php $renderToggle('show_price_history', 'Preisverlauf / Chart anzeigen'); ?>
                <?php $renderToggle('show_history_selector', 'Lizenz-Auswahl unter Chart anzeigen'); ?>
                <?php $renderToggle('show_filter_form', 'Filterformular anzeigen'); ?>
                <?php $renderToggle('show_filter_fieldset', 'Auswahlbereich Filter & Vertrag anzeigen'); ?>
                <?php $renderToggle('show_inventory_fieldset', 'Auswahlbereich Bestandspositionen anzeigen'); ?>
                <?php $renderToggle('show_forecast_fieldset', 'Auswahlbereich Chart & Forecast anzeigen'); ?>
                <?php $renderToggle('show_result_aside', 'Budget-/Ergebnis-Spalte anzeigen'); ?>
                <?php $renderToggle('show_personal_tracker', 'Persönlichen Kosten-Tracker anzeigen'); ?>
                <?php $renderToggle('show_summary_cards', 'KPI-Karten anzeigen'); ?>
                <?php $renderToggle('show_year_chart', 'Visuellen Jahresvergleich anzeigen'); ?>
                <?php $renderToggle('show_impact_cards', 'Budgetwirkung/Forecast-Karten anzeigen'); ?>
                <?php $renderToggle('show_sku_table', 'SKU-Tabelle anzeigen'); ?>
                <?php $renderToggle('show_events_table', 'Timeline-/Event-Tabelle anzeigen'); ?>
                <?php $renderToggle('show_next_steps', 'Nächste Schritte anzeigen'); ?>
                <?php $renderToggle('show_info_card', 'Info Card anzeigen'); ?>
                <?php $renderToggle('show_link_card', 'Link Card anzeigen'); ?>
            </div>
            <button type="submit" class="btn btn-primary">💾 Bereiche speichern</button>
        </form>
    <?php elseif ($tab === 'cards'): ?>
        <h3>🔗 Hero, Info Card & Link Card</h3>
        <form method="POST" class="admin-form">
            <input type="hidden" name="action" value="save_settings">
            <input type="hidden" name="csrf_token" value="<?php echo $esc($csrfToken); ?>">
            <h4>🖼️ Hero</h4>
            <div class="form-group"><label class="form-label" for="hero_title">Titel</label><input type="text" id="hero_title" name="hero_title" class="form-control" value="<?php echo $esc($settings['hero_title'] ?? ''); ?>" maxlength="160"></div>
            <div class="form-group"><label class="form-label" for="hero_intro">Intro</label><textarea id="hero_intro" name="hero_intro" class="form-control" rows="3"><?php echo $esc($settings['hero_intro'] ?? ''); ?></textarea></div>
            <div class="m365price-admin-grid">
                <div class="form-group"><label class="form-label" for="hero_cta_label">CTA-Label</label><input type="text" id="hero_cta_label" name="hero_cta_label" class="form-control" value="<?php echo $esc($settings['hero_cta_label'] ?? ''); ?>" maxlength="160"></div>
                <div class="form-group"><label class="form-label" for="hero_cta_url">CTA-Link</label><input type="text" id="hero_cta_url" name="hero_cta_url" class="form-control" value="<?php echo $esc($settings['hero_cta_url'] ?? ''); ?>" maxlength="255"></div>
            </div>
            <hr style="border:none;border-top:1px solid #f1f5f9;margin:1.5rem 0;">
            <h4>ℹ️ Info Card</h4>
            <div class="form-group"><label class="form-label" for="info_card_title">Titel</label><input type="text" id="info_card_title" name="info_card_title" class="form-control" value="<?php echo $esc($settings['info_card_title'] ?? ''); ?>" maxlength="160"></div>
            <div class="form-group"><label class="form-label" for="info_card_text">Text</label><textarea id="info_card_text" name="info_card_text" class="form-control" rows="3"><?php echo $esc($settings['info_card_text'] ?? ''); ?></textarea></div>
            <hr style="border:none;border-top:1px solid #f1f5f9;margin:1.5rem 0;">
            <h4>🔗 Link Card</h4>
            <div class="form-group"><label class="form-label" for="link_card_title">Titel</label><input type="text" id="link_card_title" name="link_card_title" class="form-control" value="<?php echo $esc($settings['link_card_title'] ?? ''); ?>" maxlength="160"></div>
            <div class="form-group"><label class="form-label" for="link_card_text">Text</label><textarea id="link_card_text" name="link_card_text" class="form-control" rows="3"><?php echo $esc($settings['link_card_text'] ?? ''); ?></textarea></div>
            <div class="m365price-admin-grid">
                <div class="form-group"><label class="form-label" for="link_card_label">Button-Label</label><input type="text" id="link_card_label" name="link_card_label" class="form-control" value="<?php echo $esc($settings['link_card_label'] ?? ''); ?>" maxlength="160"></div>
                <div class="form-group"><label class="form-label" for="link_card_url">Button-Link</label><input type="text" id="link_card_url" name="link_card_url" class="form-control" value="<?php echo $esc($settings['link_card_url'] ?? ''); ?>" maxlength="255"></div>
            </div>
            <button type="submit" class="btn btn-primary">💾 Karten speichern</button>
        </form>
    <?php endif; ?>
</div>
