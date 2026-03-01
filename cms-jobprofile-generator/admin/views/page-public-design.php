<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * View: Public & Single Page Design-Einstellungen
 *
 * @var string               $tab
 * @var array<string,string> $tabs
 * @var array<string,string> $settings
 * @var string               $notice
 * @var string               $error
 */

$nonce = CMS_JPG_Admin_Pages::nonce('jpg_public_design_save');
$pd    = fn(string $k, string $d = '') => htmlspecialchars($settings['pd_' . $k] ?? $d, ENT_QUOTES);
?>

<div class="admin-page-header">
    <div>
        <h2>🌐 Public Design</h2>
        <p>Steuere alle visuellen Aspekte der öffentlichen Job-Seiten – Farben, Layouts, Typografie und mehr</p>
    </div>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo htmlspecialchars($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="jpg-tabs">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="?tab=<?php echo esc_attr($key); ?>"
       class="jpg-tab<?php echo $tab === $key ? ' active' : ''; ?>">
        <?php echo esc_html($label); ?>
    </a>
    <?php endforeach; ?>
</div>

<form method="post" class="admin-form" style="max-width:800px;">
    <input type="hidden" name="_jpg_nonce" value="<?php echo esc_attr($nonce); ?>">

<!-- ══════════════════════════════════════════════ TAB: FARBEN ══ -->
<?php if ($tab === 'colors'): ?>
<div class="admin-card">
    <h3>🎨 Farbschema</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem;">Definiere die Farben für alle öffentlichen Job-Seiten. Änderungen werden sofort auf allen Seiten sichtbar.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div class="form-group">
            <label class="form-label">Primärfarbe (Akzent)</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input type="color" name="primary_color" value="<?php echo $pd('primary_color', '#3b82f6'); ?>"
                       style="width:48px;height:36px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;"
                       oninput="this.nextElementSibling.value=this.value">
                <input type="text" name="primary_color" class="form-control" value="<?php echo $pd('primary_color', '#3b82f6'); ?>"
                       style="max-width:120px;font-family:monospace;"
                       oninput="this.previousElementSibling.value=this.value">
            </div>
            <small class="form-text">Header, Links, aktive Elemente, Buttons</small>
        </div>
        <div class="form-group">
            <label class="form-label">Primärfarbe dunkel (Hover)</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input type="color" name="primary_dark" value="<?php echo $pd('primary_dark', '#2563eb'); ?>"
                       style="width:48px;height:36px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;"
                       oninput="this.nextElementSibling.value=this.value">
                <input type="text" name="primary_dark" class="form-control" value="<?php echo $pd('primary_dark', '#2563eb'); ?>"
                       style="max-width:120px;font-family:monospace;" oninput="this.previousElementSibling.value=this.value">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Sekundärfarbe</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input type="color" name="secondary_color" value="<?php echo $pd('secondary_color', '#1e293b'); ?>"
                       style="width:48px;height:36px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;"
                       oninput="this.nextElementSibling.value=this.value">
                <input type="text" name="secondary_color" class="form-control" value="<?php echo $pd('secondary_color', '#1e293b'); ?>"
                       style="max-width:120px;font-family:monospace;" oninput="this.previousElementSibling.value=this.value">
            </div>
            <small class="form-text">Überschriften, sekundäre Texte</small>
        </div>
        <div class="form-group">
            <label class="form-label">Akzentfarbe</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input type="color" name="accent_color" value="<?php echo $pd('accent_color', '#10b981'); ?>"
                       style="width:48px;height:36px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;"
                       oninput="this.nextElementSibling.value=this.value">
                <input type="text" name="accent_color" class="form-control" value="<?php echo $pd('accent_color', '#10b981'); ?>"
                       style="max-width:120px;font-family:monospace;" oninput="this.previousElementSibling.value=this.value">
            </div>
            <small class="form-text">Erfolg-Badges, Gehaltsanzeige</small>
        </div>
    </div>

    <hr style="margin:1.5rem 0;border:0;border-top:1px solid #f1f5f9;">
    <h4 style="margin-bottom:1rem;">Hintergrund & Karten</h4>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div class="form-group">
            <label class="form-label">Seiten-Hintergrund</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input type="color" name="bg_color" value="<?php echo $pd('bg_color', '#f8fafc'); ?>"
                       style="width:48px;height:36px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;"
                       oninput="this.nextElementSibling.value=this.value">
                <input type="text" name="bg_color" class="form-control" value="<?php echo $pd('bg_color', '#f8fafc'); ?>"
                       style="max-width:120px;font-family:monospace;" oninput="this.previousElementSibling.value=this.value">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Karten-Hintergrund</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input type="color" name="card_bg" value="<?php echo $pd('card_bg', '#ffffff'); ?>"
                       style="width:48px;height:36px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;"
                       oninput="this.nextElementSibling.value=this.value">
                <input type="text" name="card_bg" class="form-control" value="<?php echo $pd('card_bg', '#ffffff'); ?>"
                       style="max-width:120px;font-family:monospace;" oninput="this.previousElementSibling.value=this.value">
            </div>
        </div>
    </div>

    <hr style="margin:1.5rem 0;border:0;border-top:1px solid #f1f5f9;">
    <h4 style="margin-bottom:1rem;">Text & Borders</h4>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div class="form-group">
            <label class="form-label">Textfarbe (primär)</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input type="color" name="text_color" value="<?php echo $pd('text_color', '#1e293b'); ?>"
                       style="width:48px;height:36px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;"
                       oninput="this.nextElementSibling.value=this.value">
                <input type="text" name="text_color" class="form-control" value="<?php echo $pd('text_color', '#1e293b'); ?>"
                       style="max-width:120px;font-family:monospace;" oninput="this.previousElementSibling.value=this.value">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Textfarbe (gedämpft)</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input type="color" name="text_muted" value="<?php echo $pd('text_muted', '#64748b'); ?>"
                       style="width:48px;height:36px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;"
                       oninput="this.nextElementSibling.value=this.value">
                <input type="text" name="text_muted" class="form-control" value="<?php echo $pd('text_muted', '#64748b'); ?>"
                       style="max-width:120px;font-family:monospace;" oninput="this.previousElementSibling.value=this.value">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Border-Farbe</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input type="color" name="border_color" value="<?php echo $pd('border_color', '#e2e8f0'); ?>"
                       style="width:48px;height:36px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;"
                       oninput="this.nextElementSibling.value=this.value">
                <input type="text" name="border_color" class="form-control" value="<?php echo $pd('border_color', '#e2e8f0'); ?>"
                       style="max-width:120px;font-family:monospace;" oninput="this.previousElementSibling.value=this.value">
            </div>
        </div>
    </div>

    <hr style="margin:1.5rem 0;border:0;border-top:1px solid #f1f5f9;">
    <h4 style="margin-bottom:1rem;">Header-Bereich (Single Page)</h4>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div class="form-group">
            <label class="form-label">Header-Hintergrund</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input type="color" name="header_bg" value="<?php echo $pd('header_bg', '#3b82f6'); ?>"
                       style="width:48px;height:36px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;"
                       oninput="this.nextElementSibling.value=this.value">
                <input type="text" name="header_bg" class="form-control" value="<?php echo $pd('header_bg', '#3b82f6'); ?>"
                       style="max-width:120px;font-family:monospace;" oninput="this.previousElementSibling.value=this.value">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Header-Text</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input type="color" name="header_text" value="<?php echo $pd('header_text', '#ffffff'); ?>"
                       style="width:48px;height:36px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;"
                       oninput="this.nextElementSibling.value=this.value">
                <input type="text" name="header_text" class="form-control" value="<?php echo $pd('header_text', '#ffffff'); ?>"
                       style="max-width:120px;font-family:monospace;" oninput="this.previousElementSibling.value=this.value">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Gehalt-Badge Hintergrund</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input type="color" name="salary_badge_bg" value="<?php echo $pd('salary_badge_bg', '#d1fae5'); ?>"
                       style="width:48px;height:36px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;"
                       oninput="this.nextElementSibling.value=this.value">
                <input type="text" name="salary_badge_bg" class="form-control" value="<?php echo $pd('salary_badge_bg', '#d1fae5'); ?>"
                       style="max-width:120px;font-family:monospace;" oninput="this.previousElementSibling.value=this.value">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Gehalt-Badge Text</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input type="color" name="salary_badge_text" value="<?php echo $pd('salary_badge_text', '#065f46'); ?>"
                       style="width:48px;height:36px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;"
                       oninput="this.nextElementSibling.value=this.value">
                <input type="text" name="salary_badge_text" class="form-control" value="<?php echo $pd('salary_badge_text', '#065f46'); ?>"
                       style="max-width:120px;font-family:monospace;" oninput="this.previousElementSibling.value=this.value">
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════ TAB: TYPOGRAFIE ══ -->
<?php elseif ($tab === 'typography'): ?>
<div class="admin-card">
    <h3>🔤 Typografie</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem;">Schriftarten, Größen und Gewichtungen der öffentlichen Seiten.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div class="form-group">
            <label class="form-label">Fließtext-Schrift</label>
            <select name="font_body" class="form-control">
                <?php
                $fonts = [
                    '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif' => 'System (Standard)',
                    '"Inter", sans-serif'     => 'Inter',
                    '"Open Sans", sans-serif'  => 'Open Sans',
                    '"Lato", sans-serif'       => 'Lato',
                    '"Roboto", sans-serif'     => 'Roboto',
                    '"Nunito", sans-serif'     => 'Nunito',
                    '"Poppins", sans-serif'    => 'Poppins',
                    '"Source Sans 3", sans-serif' => 'Source Sans 3',
                ];
                $current = $pd('font_body', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif');
                foreach ($fonts as $v => $l): ?>
                <option value="<?php echo htmlspecialchars($v, ENT_QUOTES); ?>" <?php echo $current === htmlspecialchars($v, ENT_QUOTES) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($l); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Überschriften-Schrift</label>
            <select name="font_heading" class="form-control">
                <?php
                $currentH = $pd('font_heading', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif');
                foreach ($fonts as $v => $l): ?>
                <option value="<?php echo htmlspecialchars($v, ENT_QUOTES); ?>" <?php echo $currentH === htmlspecialchars($v, ENT_QUOTES) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($l); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Basis-Schriftgröße (px)</label>
            <input type="number" name="font_size_base" class="form-control" value="<?php echo $pd('font_size_base', '16'); ?>" min="12" max="24" step="1">
        </div>
        <div class="form-group">
            <label class="form-label">Zeilenhöhe</label>
            <input type="number" name="line_height" class="form-control" value="<?php echo $pd('line_height', '1.6'); ?>" min="1.0" max="2.5" step="0.1">
        </div>
    </div>

    <hr style="margin:1.5rem 0;border:0;border-top:1px solid #f1f5f9;">
    <h4 style="margin-bottom:1rem;">Schriftgrößen (rem)</h4>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
        <div class="form-group">
            <label class="form-label">H1 (Stellentitel)</label>
            <input type="number" name="font_size_h1" class="form-control" value="<?php echo $pd('font_size_h1', '1.6'); ?>" min="1.0" max="3.0" step="0.05">
        </div>
        <div class="form-group">
            <label class="form-label">H2 (Sektionen)</label>
            <input type="number" name="font_size_h2" class="form-control" value="<?php echo $pd('font_size_h2', '1.1'); ?>" min="0.8" max="2.0" step="0.05">
        </div>
        <div class="form-group">
            <label class="form-label">H3 (Untertitel)</label>
            <input type="number" name="font_size_h3" class="form-control" value="<?php echo $pd('font_size_h3', '0.875'); ?>" min="0.7" max="1.5" step="0.025">
        </div>
        <div class="form-group">
            <label class="form-label">Meta-Text</label>
            <input type="number" name="font_size_meta" class="form-control" value="<?php echo $pd('font_size_meta', '0.85'); ?>" min="0.7" max="1.2" step="0.025">
        </div>
        <div class="form-group">
            <label class="form-label">Kleintext</label>
            <input type="number" name="font_size_small" class="form-control" value="<?php echo $pd('font_size_small', '0.82'); ?>" min="0.6" max="1.0" step="0.01">
        </div>
        <div class="form-group">
            <label class="form-label">Überschriften-Gewicht</label>
            <select name="heading_weight" class="form-control">
                <?php foreach ([400=>'Normal (400)', 500=>'Medium (500)', 600=>'Semibold (600)', 700=>'Bold (700)', 800=>'Extrabold (800)'] as $w => $l): ?>
                <option value="<?php echo $w; ?>" <?php echo $pd('heading_weight', '700') == $w ? 'selected' : ''; ?>>
                    <?php echo $l; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════ TAB: LAYOUT ══ -->
<?php elseif ($tab === 'layout'): ?>
<div class="admin-card">
    <h3>📐 Layout & Abstände</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem;">Globale Layout-Einstellungen für alle öffentlichen Seiten.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div class="form-group">
            <label class="form-label">Content-Maximalbreite (px) <span style="color:#ef4444;">*</span></label>
            <input type="number" name="content_max_width" class="form-control" value="<?php echo $pd('content_max_width', '900'); ?>" min="600" max="1600" step="10">
            <small class="form-text">Standard: 900px. Gilt als Fallback für Single & Liste.</small>
        </div>
        <div class="form-group">
            <label class="form-label">Sidebar-Breite (px)</label>
            <input type="number" name="sidebar_width" class="form-control" value="<?php echo $pd('sidebar_width', '280'); ?>" min="200" max="400" step="10">
            <small class="form-text">Breite der rechten Sidebar im Two-Column Layout.</small>
        </div>
        <div class="form-group">
            <label class="form-label">Karten-Border-Radius (px)</label>
            <input type="number" name="card_border_radius" class="form-control" value="<?php echo $pd('card_border_radius', '10'); ?>" min="0" max="30" step="1">
        </div>
        <div class="form-group">
            <label class="form-label">Grid-Abstand (rem)</label>
            <input type="number" name="grid_gap" class="form-control" value="<?php echo $pd('grid_gap', '1.5'); ?>" min="0.5" max="4" step="0.25">
        </div>
        <div class="form-group">
            <label class="form-label">Inhalts-Padding (rem)</label>
            <input type="number" name="content_padding" class="form-control" value="<?php echo $pd('content_padding', '2'); ?>" min="0.5" max="4" step="0.25">
            <small class="form-text">Innerer Abstand der Karten und Sektionen.</small>
        </div>
        <div class="form-group">
            <label class="form-label">Sektions-Padding (rem)</label>
            <input type="number" name="section_padding" class="form-control" value="<?php echo $pd('section_padding', '2'); ?>" min="0.5" max="6" step="0.25">
        </div>
    </div>

    <hr style="margin:1.5rem 0;border:0;border-top:1px solid #f1f5f9;">
    <h4 style="margin-bottom:1rem;">Schatten</h4>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div class="form-group">
            <label class="form-label">Karten-Schatten</label>
            <input type="text" name="card_shadow" class="form-control" value="<?php echo $pd('card_shadow', '0 4px 24px rgba(0,0,0,.08)'); ?>"
                   placeholder="0 4px 24px rgba(0,0,0,.08)">
        </div>
        <div class="form-group">
            <label class="form-label">Karten-Schatten (Hover)</label>
            <input type="text" name="card_shadow_hover" class="form-control" value="<?php echo $pd('card_shadow_hover', '0 8px 32px rgba(0,0,0,.12)'); ?>"
                   placeholder="0 8px 32px rgba(0,0,0,.12)">
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════ TAB: SINGLE PAGE ══ -->
<?php elseif ($tab === 'single'): ?>
<div class="admin-card">
    <h3>📄 Single Page Einstellungen</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem;">Layout und Darstellung der einzelnen Stellenanzeigen-Seite.</p>

    <div class="form-group">
        <label class="form-label">Layout-Vorlage <span style="color:#ef4444;">*</span></label>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-top:.5rem;">
            <?php
            $layouts = [
                'classic' => ['📄', 'Classic', 'Zweispaltig mit Header-Banner, Sidebar rechts. Das Standard-Layout.'],
                'modern'  => ['🚀', 'Modern', 'Full-Width Hero, Karten-basierte Sektionen, großzügige Abstände.'],
                'compact' => ['📰', 'Kompakt', 'Minimalistisch, zeitungsartig, schlanke Darstellung ohne Sidebar.'],
                'sidebar' => ['📐', 'Sidebar', 'Sticky Sidebar mit Bewerbungs-CTA, Content links, optimiert für Conversion.'],
            ];
            $currentLayout = $pd('single_layout', 'classic');
            foreach ($layouts as $lk => $ld): ?>
            <label style="display:flex;flex-direction:column;align-items:center;padding:1rem;border:2px solid <?php echo $currentLayout === $lk ? '#3b82f6' : '#e2e8f0'; ?>;border-radius:10px;cursor:pointer;background:<?php echo $currentLayout === $lk ? '#eff6ff' : '#fff'; ?>;transition:all .15s;"
                   onmouseover="this.style.borderColor='#3b82f6'" onmouseout="if(!this.querySelector('input').checked)this.style.borderColor='#e2e8f0'">
                <input type="radio" name="single_layout" value="<?php echo $lk; ?>"
                       <?php echo $currentLayout === $lk ? 'checked' : ''; ?>
                       style="position:absolute;opacity:0;"
                       onchange="document.querySelectorAll('[name=single_layout]').forEach(r=>{r.closest('label').style.borderColor='#e2e8f0';r.closest('label').style.background='#fff'});this.closest('label').style.borderColor='#3b82f6';this.closest('label').style.background='#eff6ff'">
                <span style="font-size:2rem;"><?php echo $ld[0]; ?></span>
                <strong style="margin-top:.5rem;font-size:.88rem;"><?php echo $ld[1]; ?></strong>
                <span style="font-size:.75rem;color:#64748b;text-align:center;margin-top:.25rem;"><?php echo $ld[2]; ?></span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <hr style="margin:1.5rem 0;border:0;border-top:1px solid #f1f5f9;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div class="form-group">
            <label class="form-label">Single Page Maximalbreite (px)</label>
            <input type="number" name="single_max_width" class="form-control" value="<?php echo $pd('single_max_width', '900'); ?>" min="600" max="1400" step="10">
        </div>
        <div class="form-group">
            <label class="form-label">Header-Stil</label>
            <select name="single_header_style" class="form-control">
                <option value="colored" <?php echo $pd('single_header_style', 'colored') === 'colored' ? 'selected' : ''; ?>>Farbiger Header (Standard)</option>
                <option value="minimal" <?php echo $pd('single_header_style') === 'minimal' ? 'selected' : ''; ?>>Minimaler Header (weiß)</option>
                <option value="gradient" <?php echo $pd('single_header_style') === 'gradient' ? 'selected' : ''; ?>>Gradient Header</option>
                <option value="image" <?php echo $pd('single_header_style') === 'image' ? 'selected' : ''; ?>>Bild-Header (aus Firma)</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Zwei-Spalten Breakpoint (px)</label>
            <input type="number" name="single_two_col_breakpoint" class="form-control" value="<?php echo $pd('single_two_col_breakpoint', '640'); ?>" min="480" max="1024" step="10">
            <small class="form-text">Ab dieser Breite wird auf eine Spalte umgeschaltet.</small>
        </div>
    </div>

    <hr style="margin:1.5rem 0;border:0;border-top:1px solid #f1f5f9;">
    <h4 style="margin-bottom:1rem;">Sichtbarkeit</h4>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
        <?php
        $toggles = [
            'single_show_salary'  => 'Gehaltsangabe anzeigen',
            'single_show_company' => 'Firmenname anzeigen',
            'single_show_team'    => 'Team-Sektion anzeigen (Experten)',
            'single_show_skills'  => 'Skills/Tags anzeigen',
            'single_show_benefits'=> 'Benefits anzeigen',
            'single_show_jsonld'  => 'JSON-LD (Google for Jobs)',
            'single_show_share'   => 'Teilen-Buttons anzeigen',
        ];
        foreach ($toggles as $tk => $tl): ?>
        <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.35rem .5rem;border-radius:6px;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
            <input type="checkbox" name="<?php echo $tk; ?>" value="1" <?php echo $pd($tk, '1') === '1' ? 'checked' : ''; ?>>
            <?php echo htmlspecialchars($tl); ?>
        </label>
        <?php endforeach; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════ TAB: LISTENANSICHT ══ -->
<?php elseif ($tab === 'list'): ?>
<div class="admin-card">
    <h3>📋 Listenansicht (/jobs)</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem;">Darstellung der öffentlichen Job-Übersichtsseite.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div class="form-group">
            <label class="form-label">Listen-Maximalbreite (px)</label>
            <input type="number" name="list_max_width" class="form-control" value="<?php echo $pd('list_max_width', '900'); ?>" min="600" max="1400" step="10">
        </div>
        <div class="form-group">
            <label class="form-label">Karten-Stil</label>
            <select name="list_card_style" class="form-control">
                <option value="horizontal" <?php echo $pd('list_card_style', 'horizontal') === 'horizontal' ? 'selected' : ''; ?>>Horizontal (Standard)</option>
                <option value="grid" <?php echo $pd('list_card_style') === 'grid' ? 'selected' : ''; ?>>Grid (Kacheln)</option>
                <option value="minimal" <?php echo $pd('list_card_style') === 'minimal' ? 'selected' : ''; ?>>Minimal (Listenform)</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Jobs pro Seite</label>
            <input type="number" name="list_per_page" class="form-control" value="<?php echo $pd('list_per_page', '20'); ?>" min="5" max="100" step="5">
        </div>
        <div class="form-group">
            <label class="form-label">Listen-Karten-Schatten</label>
            <input type="text" name="list_card_shadow" class="form-control" value="<?php echo $pd('list_card_shadow', '0 1px 3px rgba(0,0,0,.06)'); ?>">
        </div>
    </div>

    <hr style="margin:1.5rem 0;border:0;border-top:1px solid #f1f5f9;">
    <h4 style="margin-bottom:1rem;">Sichtbarkeit</h4>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
        <?php
        $listToggles = [
            'list_show_title'    => 'Seitentitel anzeigen',
            'list_show_count'    => 'Stellenanzahl anzeigen',
            'list_show_salary'   => 'Gehalt in Karten anzeigen',
            'list_show_company'  => 'Firmenname in Karten anzeigen',
            'list_show_remote'   => 'Remote-Status anzeigen',
            'list_show_category' => 'Kategorie anzeigen',
            'list_show_summary'  => 'Kurzbeschreibung anzeigen',
            'list_show_filters'  => 'Filter-Leiste anzeigen',
        ];
        foreach ($listToggles as $tk => $tl): ?>
        <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.35rem .5rem;border-radius:6px;">
            <input type="checkbox" name="<?php echo $tk; ?>" value="1" <?php echo $pd($tk, '1') === '1' ? 'checked' : ''; ?>>
            <?php echo htmlspecialchars($tl); ?>
        </label>
        <?php endforeach; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════ TAB: BUTTONS ══ -->
<?php elseif ($tab === 'buttons'): ?>
<div class="admin-card">
    <h3>🔘 Button-Einstellungen</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem;">Darstellung aller Buttons auf den öffentlichen Seiten.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div class="form-group">
            <label class="form-label">Button-Border-Radius (px)</label>
            <input type="number" name="btn_border_radius" class="form-control" value="<?php echo $pd('btn_border_radius', '8'); ?>" min="0" max="50" step="1">
        </div>
        <div class="form-group">
            <label class="form-label">Button Schriftgewicht</label>
            <select name="btn_font_weight" class="form-control">
                <?php foreach ([400=>'Normal', 500=>'Medium', 600=>'Semibold', 700=>'Bold (Standard)'] as $w => $l): ?>
                <option value="<?php echo $w; ?>" <?php echo $pd('btn_font_weight', '700') == $w ? 'selected' : ''; ?>><?php echo $l; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Button Padding X (rem)</label>
            <input type="number" name="btn_padding_x" class="form-control" value="<?php echo $pd('btn_padding_x', '2'); ?>" min="0.5" max="4" step="0.25">
        </div>
        <div class="form-group">
            <label class="form-label">Button Padding Y (rem)</label>
            <input type="number" name="btn_padding_y" class="form-control" value="<?php echo $pd('btn_padding_y', '0.75'); ?>" min="0.25" max="2" step="0.125">
        </div>
        <div class="form-group">
            <label class="form-label">Button Schriftgröße (rem)</label>
            <input type="number" name="btn_font_size" class="form-control" value="<?php echo $pd('btn_font_size', '0.95'); ?>" min="0.7" max="1.5" step="0.05">
        </div>
    </div>

    <hr style="margin:1.5rem 0;border:0;border-top:1px solid #f1f5f9;">
    <h4 style="margin-bottom:1rem;">Bewerben-Button</h4>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div class="form-group">
            <label class="form-label">Button-Text</label>
            <input type="text" name="btn_apply_text" class="form-control" value="<?php echo $pd('btn_apply_text', '📩 Jetzt bewerben'); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Button-Größe</label>
            <select name="btn_apply_size" class="form-control">
                <option value="small" <?php echo $pd('btn_apply_size') === 'small' ? 'selected' : ''; ?>>Klein</option>
                <option value="normal" <?php echo $pd('btn_apply_size', 'normal') === 'normal' ? 'selected' : ''; ?>>Normal (Standard)</option>
                <option value="large" <?php echo $pd('btn_apply_size') === 'large' ? 'selected' : ''; ?>>Groß</option>
                <option value="fullwidth" <?php echo $pd('btn_apply_size') === 'fullwidth' ? 'selected' : ''; ?>>Volle Breite</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Button-Hintergrund (leer = Primärfarbe)</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input type="color" name="btn_primary_bg" value="<?php echo $pd('btn_primary_bg', '#3b82f6'); ?>"
                       style="width:48px;height:36px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;">
                <input type="text" name="btn_primary_bg" class="form-control" value="<?php echo $pd('btn_primary_bg', ''); ?>"
                       style="max-width:120px;font-family:monospace;" placeholder="auto">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Button-Textfarbe</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input type="color" name="btn_primary_text" value="<?php echo $pd('btn_primary_text', '#ffffff'); ?>"
                       style="width:48px;height:36px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;">
                <input type="text" name="btn_primary_text" class="form-control" value="<?php echo $pd('btn_primary_text', '#ffffff'); ?>"
                       style="max-width:120px;font-family:monospace;">
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════ TAB: BEWERBUNGSMODAL ══ -->
<?php elseif ($tab === 'apply-modal'): ?>
<div class="admin-card">
    <h3>📩 Bewerbungsmodal-Einstellungen</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem;">Steuerung des Bewerbungsformulars auf den Stellenanzeigen.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div class="form-group">
            <label class="form-label">Modal-Maximalbreite (px)</label>
            <input type="number" name="modal_max_width" class="form-control" value="<?php echo $pd('modal_max_width', '580'); ?>" min="400" max="900" step="10">
        </div>
        <div class="form-group">
            <label class="form-label">Modal-Border-Radius (px)</label>
            <input type="number" name="modal_border_radius" class="form-control" value="<?php echo $pd('modal_border_radius', '12'); ?>" min="0" max="30" step="1">
        </div>
        <div class="form-group">
            <label class="form-label">Min. Anschreiben-Zeichen</label>
            <input type="number" name="modal_cover_min_chars" class="form-control" value="<?php echo $pd('modal_cover_min_chars', '20'); ?>" min="0" max="500" step="10">
        </div>
    </div>

    <hr style="margin:1.5rem 0;border:0;border-top:1px solid #f1f5f9;">
    <h4 style="margin-bottom:1rem;">Felder & Verhalten</h4>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
        <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.35rem .5rem;border-radius:6px;">
            <input type="checkbox" name="modal_show_phone" value="1" <?php echo $pd('modal_show_phone', '1') === '1' ? 'checked' : ''; ?>>
            Telefon-Feld anzeigen
        </label>
        <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.35rem .5rem;border-radius:6px;">
            <input type="checkbox" name="modal_show_cv" value="1" <?php echo $pd('modal_show_cv', '1') === '1' ? 'checked' : ''; ?>>
            CV-Upload anzeigen
        </label>
        <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.35rem .5rem;border-radius:6px;">
            <input type="checkbox" name="modal_require_cv" value="1" <?php echo $pd('modal_require_cv', '0') === '1' ? 'checked' : ''; ?>>
            CV-Upload verpflichtend
        </label>
        <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.35rem .5rem;border-radius:6px;">
            <input type="checkbox" name="modal_require_login" value="1" <?php echo $pd('modal_require_login', '1') === '1' ? 'checked' : ''; ?>>
            Login/Registrierung erzwingen
        </label>
        <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.35rem .5rem;border-radius:6px;">
            <input type="checkbox" name="modal_show_register" value="1" <?php echo $pd('modal_show_register', '1') === '1' ? 'checked' : ''; ?>>
            Inline-Registrierung anzeigen
        </label>
    </div>

    <hr style="margin:1.5rem 0;border:0;border-top:1px solid #f1f5f9;">
    <h4 style="margin-bottom:1rem;">Texte</h4>
    <div class="form-group">
        <label class="form-label">Datenschutz-URL</label>
        <input type="text" name="modal_privacy_url" class="form-control" value="<?php echo $pd('modal_privacy_url', '/datenschutz'); ?>">
    </div>
    <div class="form-group">
        <label class="form-label">Datenschutz-Hinweistext</label>
        <textarea name="modal_privacy_text" class="form-control" rows="2"><?php echo $pd('modal_privacy_text', 'Mit dem Absenden stimmst du der Verarbeitung deiner Daten gemäß unserer Datenschutzerklärung zu.'); ?></textarea>
    </div>
    <div class="form-group">
        <label class="form-label">Erfolgs-Nachricht</label>
        <input type="text" name="modal_success_text" class="form-control" value="<?php echo $pd('modal_success_text', 'Bewerbung eingereicht! Wir melden uns so schnell wie möglich.'); ?>">
    </div>
</div>

<!-- ══════════════════════════════════════════════ TAB: ERWEITERT ══ -->
<?php elseif ($tab === 'advanced'): ?>
<div class="admin-card">
    <h3>⚙️ Erweiterte Einstellungen</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem;">Benutzerdefiniertes CSS und erweiterte Optionen.</p>

    <div class="form-group">
        <label class="form-label">Benutzerdefiniertes CSS</label>
        <textarea name="custom_css" class="form-control" rows="10"
                  style="font-family:monospace;font-size:.82rem;resize:vertical;"
                  placeholder="/* Eigene CSS-Regeln für öffentliche Seiten */"><?php echo $pd('custom_css'); ?></textarea>
        <small class="form-text">Wird nach allen anderen Styles geladen. Überschreibt alle Design-Einstellungen.</small>
    </div>

    <div class="form-group">
        <label class="form-label">Zusätzlicher Head-Code</label>
        <textarea name="custom_head_code" class="form-control" rows="5"
                  style="font-family:monospace;font-size:.82rem;resize:vertical;"
                  placeholder="<!-- z.B. Google Fonts Import -->"><?php echo $pd('custom_head_code'); ?></textarea>
        <small class="form-text">Wird im &lt;head&gt; der öffentlichen Seiten eingefügt (z.B. für Google Fonts).</small>
    </div>

    <hr style="margin:1.5rem 0;border:0;border-top:1px solid #f1f5f9;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
        <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.35rem .5rem;border-radius:6px;">
            <input type="checkbox" name="disable_animations" value="1" <?php echo $pd('disable_animations', '0') === '1' ? 'checked' : ''; ?>>
            Animationen deaktivieren
        </label>
        <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.35rem .5rem;border-radius:6px;">
            <input type="checkbox" name="lazy_load_images" value="1" <?php echo $pd('lazy_load_images', '1') === '1' ? 'checked' : ''; ?>>
            Bilder lazy-laden
        </label>
        <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.35rem .5rem;border-radius:6px;">
            <input type="checkbox" name="enable_print_styles" value="1" <?php echo $pd('enable_print_styles', '1') === '1' ? 'checked' : ''; ?>>
            Druck-Styles aktivieren
        </label>
    </div>
</div>

<?php endif; ?>

<!-- ══════════════════════════════════ SAVE-BAR ══ -->
<div class="admin-card form-actions-card" style="margin-top:1rem;">
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
        <span class="form-actions__hint">Änderungen werden sofort auf den öffentlichen Seiten sichtbar</span>
    </div>
</div>

</form>
