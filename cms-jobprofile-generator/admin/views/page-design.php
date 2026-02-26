<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * View: Vorlagen & Design – 5 Tabs
 *
 * @var string               $tab
 * @var array<string,string> $tabs
 * @var array<object>        $templates
 * @var array<string,string> $cd_settings
 * @var string               $notice
 * @var string               $error
 */

$nonce = CMS_JPG_Admin_Pages::nonce('jpg_design_save');

$templateTabs = ['pdf-templates', 'web-templates', 'email-templates'];
?>

<div class="admin-page-header">
    <div>
        <h2>🎨 Vorlagen & Design</h2>
        <p>PDF-Templates, Web-Templates, Corporate Design und E-Mail-Vorlagen</p>
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

<!-- ══════════════════════════════════ TEMPLATE-TABS (PDF / WEB / EMAIL) ══ -->
<?php if (in_array($tab, $templateTabs, true)): ?><style>
.jpg-tpl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:1rem;margin-top:.25rem}
.jpg-tpl-card{border:1px solid #e2e8f0;border-radius:10px;padding:1.1rem 1rem;background:#fff;display:flex;flex-direction:column;gap:.45rem;transition:box-shadow .15s,border-color .15s}
.jpg-tpl-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.1);border-color:#3b82f6}
.jpg-tpl-card.is-default{border-color:#3b82f6;background:#eff6ff}
.jpg-tpl-card-header{display:flex;gap:.35rem;flex-wrap:wrap;min-height:22px}
.tpl-badge{font-size:.72rem;padding:.15rem .45rem;border-radius:10px;font-weight:600}
.tpl-badge.default{background:#dbeafe;color:#1e40af}
.tpl-badge.inactive{background:#f1f5f9;color:#64748b}
.jpg-tpl-card-icon{font-size:2.2rem;text-align:center;margin:.15rem 0}
.jpg-tpl-card-name{font-weight:700;font-size:.93rem;color:#1e293b;text-align:center;line-height:1.3}
.jpg-tpl-card-meta{font-size:.77rem;color:#94a3b8;text-align:center}
.jpg-tpl-card-actions{display:flex;gap:.3rem;justify-content:center;margin-top:.35rem;flex-wrap:wrap}
.jpg-ph-grid{display:grid;grid-template-columns:1fr 1fr;gap:.2rem .5rem;margin-top:.4rem}
.jpg-ph-btn{background:none;border:none;padding:.2rem .3rem;text-align:left;cursor:pointer;border-radius:4px;display:flex;align-items:baseline;gap:.3rem;width:100%}
.jpg-ph-btn:hover{background:#f1f5f9}
.jpg-ph-code{font-family:monospace;background:#e2e8f0;color:#1e40af;padding:.1rem .3rem;border-radius:3px;font-size:.73rem;white-space:nowrap}
.jpg-ph-desc{color:#64748b;font-size:.73rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.jpg-tpl-form-wrap{display:none;margin-top:1rem}
.jpg-tpl-form-wrap.open{display:block}
</style>
<div style="border-radius:0 10px 10px 10px;margin-top:0;">

<div class="admin-card">
    <h3><?php echo match($tab) {
        'pdf-templates'   => '📄 PDF-Templates',
        'email-templates' => '📧 E-Mail-Templates',
        default           => '🌐 Web-Templates',
    }; ?></h3>

    <?php if (empty($templates)): ?>
    <div class="empty-state">
        <p style="font-size:2.5rem;margin:0;">📭</p>
        <p><strong>Noch keine Templates vorhanden.</strong></p>
        <p style="color:#64748b;font-size:.875rem;">Reaktiviere das Plugin einmal, um die Starter-Templates automatisch einzuspielen. Oder erstelle ein neues Template mit dem Formular rechts.</p>
    </div>
    <?php else: ?>
    <div class="jpg-tpl-grid">
        <?php foreach ($templates as $tpl): ?>
        <?php $tplData = htmlspecialchars(json_encode(['name'=>$tpl->name,'content'=>$tpl->content,'css'=>$tpl->css,'is_default'=>$tpl->is_default]), ENT_QUOTES); ?>
        <div class="jpg-tpl-card<?php echo (int)$tpl->is_default ? ' is-default' : ''; ?>">
            <div class="jpg-tpl-card-header">
                <?php if ((int)$tpl->is_default): ?><span class="tpl-badge default">⭐ Standard</span><?php endif; ?>
                <?php if (!(int)$tpl->active): ?><span class="tpl-badge inactive">Inaktiv</span><?php endif; ?>
            </div>
            <div class="jpg-tpl-card-icon"><?php echo match($tab){'pdf-templates'=>'📄','email-templates'=>'📧',default=>'🌐'}; ?></div>
            <div class="jpg-tpl-card-name"><?php echo htmlspecialchars($tpl->name); ?></div>
            <div class="jpg-tpl-card-meta"><?php echo strlen((string)$tpl->content)>10 ? number_format(strlen((string)$tpl->content)).' Zeichen' : 'Leer'; ?></div>
            <div class="jpg-tpl-card-actions">
                <button class="btn btn-sm btn-secondary"
                        onclick="jpgEditTemplate(<?php echo (int)$tpl->id; ?>, <?php echo $tplData; ?>)" title="Bearbeiten">✏️</button>
                <button class="btn btn-sm btn-outline"
                        onclick="jpgPreviewTemplate(<?php echo $tplData; ?>)" title="Vorschau">👁️</button>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="_jpg_nonce" value="<?php echo esc_attr($nonce); ?>">
                    <input type="hidden" name="delete_id"  value="<?php echo (int)$tpl->id; ?>">
                    <button type="submit" class="btn btn-sm btn-danger"
                            onclick="return confirm('Template wirklich löschen?')" title="Löschen">🗑️</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div style="margin-top:1rem;">
        <button type="button" class="btn btn-primary" id="jpgTplToggleBtn"
                onclick="document.getElementById('jpgTplFormWrap').classList.toggle('open');this.textContent=this.textContent.includes('Neues')?'↩️ Formular schließen':'➕ Neues Template';">➕ Neues Template</button>
    </div>
</div>

<div class="admin-card jpg-tpl-form-wrap" id="jpgTplFormWrap">
    <h3 id="jpgTplFormTitle">➕ Neues Template</h3>
    <form method="post" class="admin-form" id="jpgTplForm">
        <input type="hidden" name="_jpg_nonce"   value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="template_id"  value="0" id="jpgTplId">

        <div class="form-group">
            <label class="form-label">Name <span style="color:#ef4444;">*</span></label>
            <input type="text" name="name" class="form-control" id="jpgTplName" placeholder="Template-Name…" required>
        </div>

        <div class="form-group">
            <label class="form-label">Template-Inhalt</label>
            <textarea name="content" class="form-control" id="jpgTplContent"
                      style="min-height:200px;font-family:monospace;font-size:.82rem;resize:vertical;"
                      placeholder="HTML/Template-Code…"></textarea>
            <details style="background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;margin-top:.4rem;">
                <summary style="padding:.6rem .75rem;font-size:.8rem;font-weight:700;color:#475569;cursor:pointer;user-select:none;">
                    📎 Platzhalter anzeigen <span style="font-weight:400;color:#94a3b8;">(Klick = in Cursor einfügen)</span>
                </summary>
                <div class="jpg-ph-grid" style="padding:.5rem .75rem .75rem;">
                <?php foreach ([
                    '{TITLE}'=>'Stellentitel','{SUMMARY}'=>'Kurzfassung',
                    '{TASKS}'=>'Aufgaben (Liste)','{TASKS_SHORT}'=>'Aufgaben (3 Stk.)',
                    '{REQUIREMENTS}'=>'Alle Anforderungen','{REQUIREMENTS_MUST}'=>'Muss-Kriterien',
                    '{REQUIREMENTS_NICE}'=>'Wünschenswert','{BENEFITS}'=>'Benefits (Liste)',
                    '{BENEFITS_SHORT}'=>'Benefits (3 Stk.)','{SKILLS}'=>'Skills (Liste)',
                    '{SKILLS_TAGS}'=>'Skills als Tag-Chips','{LOCATION}'=>'Einsatzort',
                    '{SALARY}'=>'Gehaltsangabe','{SALARY_BLOCK}'=>'Gehalts-Sektion',
                    '{SALARY_INLINE}'=>'Gehalt kompakt','{COMPANY}'=>'Unternehmensname',
                    '{EMPLOYMENT_TYPE}'=>'Anstellungsart','{EXPERIENCE_LEVEL}'=>'Level',
                    '{FOOTER_TEXT}'=>'Footer (aus Corporate Design)',
                    '{CUSTOM_CSS}'=>'CSS (Corporate Design)',
                    '{PRIMARY_COLOR}'=>'Primärfarbe','{SECONDARY_COLOR}'=>'Sekundärfarbe',
                    '{FONT_BODY}'=>'Fließtext-Schrift','{FONT_HEADING}'=>'Überschriften-Schrift',
                ] as $ph => $desc): ?>
                <button type="button" class="jpg-ph-btn"
                        onclick="jpgInsertPlaceholder('<?php echo $ph; ?>')">
                    <span class="jpg-ph-code"><?php echo htmlspecialchars($ph); ?></span>
                    <span class="jpg-ph-desc"><?php echo htmlspecialchars($desc); ?></span>
                </button>
                <?php endforeach; ?>
                </div>
            </details>
        </div>

        <div class="form-group">
            <label class="form-label">Benutzerdefiniertes CSS</label>
            <textarea name="css" class="form-control" id="jpgTplCss"
                      style="min-height:100px;font-family:monospace;font-size:.82rem;resize:vertical;"
                      placeholder="/* Eigene CSS-Regeln… */"></textarea>
        </div>

        <div class="form-group">
            <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
                <input type="checkbox" name="is_default" value="1" id="jpgTplDefault">
                Als Standard-Template setzen
            </label>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;">💾 Template speichern</button>
        <button type="button" class="btn btn-secondary" style="width:100%;margin-top:.5rem;"
                onclick="jpgTplReset()">↩️ Zurücksetzen</button>
    </form>
</div>

<!-- ══════════════════════════════════════ TAB: CORPORATE DESIGN ══ -->
<?php elseif ($tab === 'corporate'): ?>

<?php CMS_JPG_Admin_Pages::render_upgrade_notice_public('custom_branding', 'Corporate Design / Custom Branding ist in Ihrem aktuellen Abo-Paket nicht enthalten.'); ?>

<div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;max-width:700px;">
    <h3>🏢 Corporate Design</h3>
    <form method="post" class="admin-form">
        <input type="hidden" name="_jpg_nonce" value="<?php echo esc_attr($nonce); ?>">

        <?php
        $cdGet = fn(string $key, string $default = '') => htmlspecialchars($cd_settings['cd_' . $key] ?? $default, ENT_QUOTES);
        ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
            <div class="form-group">
                <label class="form-label">Primärfarbe</label>
                <div style="display:flex;gap:.5rem;">
                    <input type="text" name="primary_color" class="form-control"
                           value="<?php echo $cdGet('primary_color', '#3b82f6'); ?>" maxlength="7">
                    <input type="color" value="<?php echo $cdGet('primary_color', '#3b82f6'); ?>"
                           oninput="this.previousElementSibling.value=this.value"
                           style="width:44px;padding:.2rem;border-radius:6px;border:2px solid #e2e8f0;cursor:pointer;">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Sekundärfarbe</label>
                <div style="display:flex;gap:.5rem;">
                    <input type="text" name="secondary_color" class="form-control"
                           value="<?php echo $cdGet('secondary_color', '#1e293b'); ?>" maxlength="7">
                    <input type="color" value="<?php echo $cdGet('secondary_color', '#1e293b'); ?>"
                           oninput="this.previousElementSibling.value=this.value"
                           style="width:44px;padding:.2rem;border-radius:6px;border:2px solid #e2e8f0;cursor:pointer;">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Unternehmensname</label>
            <input type="text" name="company_name" class="form-control"
                   value="<?php echo $cdGet('company_name'); ?>" placeholder="Ihr Unternehmensname…">
        </div>
        <div class="form-group">
            <label class="form-label">Slogan / Tagline</label>
            <input type="text" name="company_tagline" class="form-control"
                   value="<?php echo $cdGet('company_tagline'); ?>" placeholder="Ihr Unternehmens-Motto…">
        </div>
        <div class="form-group">
            <label class="form-label">Logo-URL</label>
            <input type="url" name="logo_url" class="form-control"
                   value="<?php echo $cdGet('logo_url'); ?>" placeholder="https://…">
        </div>
        <div class="form-group">
            <label class="form-label">Footer-Text</label>
            <textarea name="footer_text" class="form-control" style="height:80px;"
                      placeholder="Wird in PDF-/E-Mail-Exporten angezeigt…"><?php echo $cdGet('footer_text'); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">💾 Corporate Design speichern</button>
    </form>
</div>

<!-- ══════════════════════════════════════ TAB: TYPOGRAFIE ══ -->
<?php elseif ($tab === 'typography'): ?>

<div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;max-width:600px;">
    <h3>🔤 Typografie</h3>
    <div class="alert" style="background:#dbeafe;color:#1e40af;border-left:4px solid #3b82f6;">
        ℹ️ Typografie-Einstellungen wirken sich auf generierte PDF-Exporte und Web-Templates aus.
    </div>
    <form method="post" class="admin-form">
        <input type="hidden" name="_jpg_nonce" value="<?php echo esc_attr($nonce); ?>">

        <?php $cdGet = fn(string $key, string $default = '') => htmlspecialchars($cd_settings['cd_' . $key] ?? $default, ENT_QUOTES); ?>

        <div class="form-group">
            <label class="form-label">Schrift (Fließtext)</label>
            <select name="font_body" class="form-control">
                <?php $currentFont = $cdGet('font_body', 'Inter, sans-serif'); ?>
                <?php foreach (['Inter, sans-serif', 'Arial, sans-serif', 'Georgia, serif', 'Roboto, sans-serif', 'Open Sans, sans-serif'] as $f): ?>
                <option value="<?php echo htmlspecialchars($f, ENT_QUOTES); ?>"
                        <?php echo $currentFont === htmlspecialchars($f, ENT_QUOTES) ? 'selected' : ''; ?>>
                    <?php echo $f; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Schrift (Überschriften)</label>
            <select name="font_heading" class="form-control">
                <?php $currentHead = $cdGet('font_heading', 'Inter, sans-serif'); ?>
                <?php foreach (['Inter, sans-serif', 'Arial, sans-serif', 'Georgia, serif', 'Roboto, sans-serif', 'Open Sans, sans-serif'] as $f): ?>
                <option value="<?php echo htmlspecialchars($f, ENT_QUOTES); ?>"
                        <?php echo $currentHead === htmlspecialchars($f, ENT_QUOTES) ? 'selected' : ''; ?>>
                    <?php echo $f; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="background:#f8fafc;border-radius:8px;padding:1rem;margin-bottom:1rem;">
            <div id="jpgFontPreview">
                <div style="font-size:1.5rem;font-weight:700;margin-bottom:.25rem;">Überschrift</div>
                <div style="font-size:1rem;color:#475569;line-height:1.6;">
                    Kurzbeschreibung zur Stelle – Teamplayer mit hoher Eigenverantwortung gesucht.
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">💾 Typografie speichern</button>
    </form>
</div>

<?php endif; ?>

<!-- Template-Vorschau Modal -->
<div id="jpgTplPreviewModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:960px;width:95vw;">
        <div class="modal-header">
            <h3>👁️ Template-Vorschau</h3>
            <button class="modal-close" onclick="closeModal('jpgTplPreviewModal')">&times;</button>
        </div>
        <div class="modal-body" style="padding:0;overflow:hidden;">
            <iframe id="jpgTplPreviewFrame"
                    style="width:100%;height:560px;border:none;border-radius:0 0 10px 10px;"
                    srcdoc="<p style='padding:2rem;color:#94a3b8;font-family:sans-serif;'>Wähle ein Template zum Vorschauen.</p>"></iframe>
        </div>
    </div>
</div>

<script>
function jpgEditTemplate(id, data) {
    document.getElementById('jpgTplId').value   = id;
    document.getElementById('jpgTplName').value = data.name    || '';
    if (document.getElementById('jpgTplContent'))
        document.getElementById('jpgTplContent').value = data.content || '';
    if (document.getElementById('jpgTplCss'))
        document.getElementById('jpgTplCss').value = data.css || '';
    if (document.getElementById('jpgTplDefault'))
        document.getElementById('jpgTplDefault').checked = !!parseInt(data.is_default || '0');
    var h = document.getElementById('jpgTplFormTitle');
    if (h) h.textContent = '✏️ Template bearbeiten';
    window.scrollTo({ top: document.getElementById('jpgTplId').closest('.admin-card').offsetTop - 80, behavior: 'smooth' });
}
function jpgPreviewTemplate(data) {
    var content = data.content || '';
    var css     = data.css     || '';
    var html    = '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8">'
                + '<style>*{box-sizing:border-box}body{margin:0;padding:1.5rem;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;font-size:14px;}' + css + '</style>'
                + '</head><body>' + (content || '<p style="color:#94a3b8;padding:2rem;">Kein Inhalt vorhanden.</p>') + '</body></html>';
    document.getElementById('jpgTplPreviewFrame').srcdoc = html;
    document.getElementById('jpgTplPreviewModal').style.display = 'flex';
}
function jpgInsertPlaceholder(ph) {
    var ta = document.getElementById('jpgTplContent');
    if (!ta) return;
    var s = ta.selectionStart, e = ta.selectionEnd;
    ta.value = ta.value.substring(0, s) + ph + ta.value.substring(e);
    ta.selectionStart = ta.selectionEnd = s + ph.length;
    ta.focus();
}
function jpgTplReset() {
    document.getElementById('jpgTplId').value = '0';
    document.getElementById('jpgTplForm').reset();
    var h = document.getElementById('jpgTplFormTitle');
    if (h) h.textContent = '➕ Neues Template';
}
// Typografie-Vorschau
document.addEventListener('DOMContentLoaded', function() {
    var bodySelect = document.querySelector('select[name="font_body"]');
    var headSelect = document.querySelector('select[name="font_heading"]');
    var preview    = document.getElementById('jpgFontPreview');
    if (!bodySelect || !preview) return;
    function updatePreview() {
        var head  = preview.querySelector('div:first-child');
        var body  = preview.querySelector('div:last-child');
        if (head && headSelect) head.style.fontFamily = headSelect.value;
        if (body && bodySelect) body.style.fontFamily = bodySelect.value;
    }
    [bodySelect, headSelect].forEach(function(el) {
        if (el) el.addEventListener('change', updatePreview);
    });
    updatePreview();
});
</script>
