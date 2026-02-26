<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
/**
 * Member-View: Vorlagen (Admin-only)
 *
 * @var string   $tab        Aktiver Tab-Key
 * @var array    $tabs       Tab-Map key=>label
 * @var string   $type       Vorlagen-Typ (pdf|web|email|null)
 * @var array    $templates  Vorlagen-Objekte
 * @var string   $csrf       CSRF-Token
 * @var string   $notice     Erfolgsmeldung
 * @var string   $error      Fehlermeldung
 */
$esc     = fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
$baseTpl = '/member/plugin/member-job-templates';
$typeMap = ['pdf-templates' => 'pdf', 'web-templates' => 'web', 'email-templates' => 'email'];
$icons   = ['pdf' => '📄', 'web' => '🌐', 'email' => '📧'];
?>
<style>
.tpl-tabs{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1.5rem;border-bottom:2px solid #e2e8f0;padding-bottom:0;}
.tpl-tab{padding:.55rem 1.1rem;text-decoration:none;color:#64748b;font-size:.875rem;font-weight:500;border-radius:6px 6px 0 0;border:1px solid transparent;border-bottom:none;transition:all .15s;}
.tpl-tab:hover{background:#f8fafc;color:#1e293b;}
.tpl-tab.active{background:#fff;color:#3b82f6;border-color:#e2e8f0;border-bottom:2px solid #fff;margin-bottom:-2px;font-weight:700;}
.tpl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;margin-bottom:1.5rem;}
.tpl-card{background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;overflow:hidden;transition:box-shadow .15s,border-color .15s;}
.tpl-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.09);border-color:#3b82f6;}
.tpl-card.is-default{border-color:#3b82f6;background:#eff6ff;}
.tpl-card-head{display:flex;justify-content:space-between;align-items:flex-start;padding:.85rem 1rem .5rem;}
.tpl-card-icon{font-size:2.2rem;text-align:center;padding:.5rem 0;}
.tpl-card-name{font-weight:700;color:#1e293b;font-size:.9rem;padding:0 1rem .75rem;text-align:center;}
.tpl-card-foot{background:#f8fafc;border-top:1px solid #f1f5f9;padding:.6rem 1rem;display:flex;justify-content:space-between;align-items:center;}
.tpl-new-card{background:#f8fafc;border:2px dashed #cbd5e1;border-radius:10px;transition:all .15s;}
.tpl-new-card:hover{border-color:#3b82f6;background:#eff6ff;}
.tpl-form-wrap{background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;padding:1.5rem;margin-bottom:1.5rem;display:none;}
.tpl-form-wrap.open{display:block;}
</style>

<?php if (!empty($notice)): ?>
<div style="background:#d1fae5;color:#065f46;padding:.875rem 1rem;border-radius:8px;margin-bottom:1.25rem;border-left:4px solid #059669;">
    ✅ <?php echo $esc($notice); ?>
</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div style="background:#fee2e2;color:#991b1b;padding:.875rem 1rem;border-radius:8px;margin-bottom:1.25rem;border-left:4px solid #ef4444;">
    ❌ <?php echo $esc($error); ?>
</div>
<?php endif; ?>

<!-- Tab-Navigation -->
<div class="tpl-tabs">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="<?php echo $esc($baseTpl . '?tab=' . $key); ?>"
       class="tpl-tab<?php echo $tab === $key ? ' active' : ''; ?>">
        <?php echo $esc($label); ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Neue Vorlage Form (togglable) -->
<div class="tpl-form-wrap" id="tplFormWrap">
    <h4 style="margin:0 0 1rem;font-size:.95rem;color:#1e293b;">➕ Neue Vorlage erstellen</h4>
    <form method="post" action="<?php echo $esc($baseTpl . '?tab=' . $tab); ?>">
        <input type="hidden" name="_jpg_csrf"     value="<?php echo $esc($csrf); ?>">
        <input type="hidden" name="tpl_action"    value="save">
        <input type="hidden" name="template_type" value="<?php echo $esc($tab); ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
            <div>
                <label style="font-size:.8rem;font-weight:600;color:#475569;display:block;margin-bottom:.35rem;">
                    Name <span style="color:#ef4444;">*</span>
                </label>
                <input type="text" name="template_name" class="form-control" placeholder="Vorlage Basisdesign" required
                       style="width:100%;padding:.6rem .75rem;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.875rem;">
            </div>
            <div style="display:flex;align-items:flex-end;gap:.75rem;padding-bottom:.05rem;">
                <label style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;color:#475569;cursor:pointer;">
                    <input type="checkbox" name="is_default" value="1"
                           style="width:16px;height:16px;accent-color:#3b82f6;">
                    Als Standard setzen
                </label>
            </div>
        </div>
        <div style="margin-bottom:1rem;">
            <label style="font-size:.8rem;font-weight:600;color:#475569;display:block;margin-bottom:.35rem;">
                Inhalt / Konfiguration
            </label>
            <textarea name="template_content" rows="8" class="form-control"
                      style="width:100%;padding:.6rem .75rem;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.875rem;font-family:monospace;resize:vertical;"
                      placeholder="HTML-Inhalt, JSON-Konfiguration oder Template-Code..."></textarea>
        </div>
        <div style="display:flex;gap:.75rem;">
            <button type="submit" class="btn btn-primary">💾 Vorlage speichern</button>
            <button type="button" class="btn btn-secondary" onclick="toggleTplForm()">Abbrechen</button>
        </div>
    </form>
</div>

<!-- Button: Neue Vorlage -->
<div style="margin-bottom:1.25rem;">
    <button type="button" class="btn btn-primary" onclick="toggleTplForm()">➕ Neue Vorlage</button>
</div>

<!-- Vorlagen-Grid -->
<?php if (empty($templates)): ?>
<div style="text-align:center;padding:3rem;background:#fff;border:1.5px dashed #cbd5e1;border-radius:10px;color:#64748b;">
    <p style="font-size:2.5rem;margin:0;"><?php echo $icons[$type ?? 'pdf'] ?? '📄'; ?></p>
    <p><strong>Noch keine Vorlagen vorhanden</strong></p>
    <p style="font-size:.875rem;">Erstelle deine erste Vorlage über den Button oben.</p>
</div>
<?php else: ?>
<div class="tpl-grid">
    <?php foreach ($templates as $tpl): ?>
    <div class="tpl-card<?php echo !empty($tpl->is_default) ? ' is-default' : ''; ?>">
        <div class="tpl-card-head">
            <?php if (!empty($tpl->is_default)): ?>
            <span style="font-size:.72rem;background:#dbeafe;color:#1e40af;padding:.15rem .5rem;border-radius:8px;font-weight:700;">Standard</span>
            <?php else: ?>
            <span></span>
            <?php endif; ?>
            <!-- Edit toggle --><button type="button" onclick="toggleEdit(<?php echo (int)$tpl->id; ?>)"
                    style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:.9rem;padding:0;"
                    title="Bearbeiten">✏️</button>
        </div>
        <div class="tpl-card-icon"><?php echo $icons[$tpl->type ?? 'pdf'] ?? '📄'; ?></div>
        <div class="tpl-card-name"><?php echo $esc($tpl->name ?? ''); ?></div>

        <!-- Inline Edit Form (initially hidden) -->
        <div id="edit-<?php echo (int)$tpl->id; ?>" style="display:none;padding:0 .85rem .85rem;">
            <form method="post" action="<?php echo $esc($baseTpl . '?tab=' . $tab); ?>">
                <input type="hidden" name="_jpg_csrf"     value="<?php echo $esc($csrf); ?>">
                <input type="hidden" name="tpl_action"    value="update">
                <input type="hidden" name="template_id"   value="<?php echo (int)$tpl->id; ?>">
                <input type="hidden" name="template_type" value="<?php echo $esc($tab); ?>">
                <input type="text" name="template_name"
                       value="<?php echo $esc($tpl->name ?? ''); ?>"
                       class="form-control"
                       style="width:100%;padding:.5rem .65rem;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.8rem;margin-bottom:.5rem;">
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:#475569;margin-bottom:.5rem;cursor:pointer;">
                    <input type="checkbox" name="is_default" value="1"<?php echo !empty($tpl->is_default) ? ' checked' : ''; ?>
                           style="accent-color:#3b82f6;">
                    Standard
                </label>
                <textarea name="template_content" rows="4"
                          style="width:100%;padding:.5rem .65rem;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.78rem;font-family:monospace;resize:vertical;margin-bottom:.5rem;"><?php echo $esc($tpl->content ?? ''); ?></textarea>
                <button type="submit" class="btn btn-primary" style="width:100%;font-size:.8rem;padding:.45rem;">💾 Aktualisieren</button>
            </form>
        </div>

        <div class="tpl-card-foot">
            <?php if (empty($tpl->is_default) && !empty($tpl->id)): ?>
            <form method="post" action="<?php echo $esc($baseTpl . '?tab=' . $tab); ?>" style="display:inline;">
                <input type="hidden" name="_jpg_csrf"   value="<?php echo $esc($csrf); ?>">
                <input type="hidden" name="tpl_action"  value="save">
                <input type="hidden" name="template_id" value="<?php echo (int)$tpl->id; ?>">
                <input type="hidden" name="template_type" value="<?php echo $esc($tab); ?>">
                <input type="hidden" name="template_name" value="<?php echo $esc($tpl->name ?? ''); ?>">
                <input type="hidden" name="template_content" value="<?php echo $esc($tpl->content ?? ''); ?>">
                <input type="hidden" name="is_default" value="1">
                <button type="submit" class="btn btn-sm" style="background:#dbeafe;color:#1e40af;border:none;border-radius:4px;padding:.3rem .65rem;cursor:pointer;font-size:.78rem;">⭐ Standard</button>
            </form>
            <?php else: ?>
            <span></span>
            <?php endif; ?>
            <form method="post" action="<?php echo $esc($baseTpl . '?tab=' . $tab); ?>" style="display:inline;">
                <input type="hidden" name="_jpg_csrf"   value="<?php echo $esc($csrf); ?>">
                <input type="hidden" name="tpl_action"  value="delete">
                <input type="hidden" name="template_id" value="<?php echo (int)$tpl->id; ?>">
                <button type="submit" class="btn btn-sm"
                        style="background:#fee2e2;color:#991b1b;border:none;border-radius:4px;padding:.3rem .5rem;cursor:pointer;"
                        onclick="return confirm('Vorlage wirklich löschen?')">🗑️</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function toggleTplForm() {
    var wrap = document.getElementById('tplFormWrap');
    wrap.classList.toggle('open');
    if (wrap.classList.contains('open')) {
        wrap.scrollIntoView({behavior:'smooth', block:'start'});
    }
}
function toggleEdit(id) {
    var el = document.getElementById('edit-' + id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
