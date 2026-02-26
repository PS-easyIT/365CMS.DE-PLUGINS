<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
/**
 * Member-View: Bibliotheken (Admin-only)
 *
 * @var string          $tab        Aktiver Tab-Key
 * @var array           $tabs       Tab-Map key=>label
 * @var array           $benefits   Alle Benefit-Einträge
 * @var array           $bGroups    Benefit-Gruppen
 * @var array           $skills     Alle Skills
 * @var array           $sGroups    Skill-Gruppen
 * @var array           $categories Alle Job-Kategorien
 * @var array           $textItems  Alle Textbausteine
 * @var array           $textCats   Textbaustein-Kategorien
 * @var string          $csrf       CSRF-Token
 * @var string          $notice     Erfolgsmeldung
 * @var string          $error      Fehlermeldung
 */
$esc     = fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
$baseLib = '/member/plugin/member-job-libraries';
?>
<style>
.lib-tabs{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1.5rem;border-bottom:2px solid #e2e8f0;padding-bottom:0;}
.lib-tab{padding:.55rem 1.1rem;text-decoration:none;color:#64748b;font-size:.875rem;font-weight:500;border-radius:6px 6px 0 0;border:1px solid transparent;border-bottom:none;transition:all .15s;}
.lib-tab:hover{background:#f8fafc;color:#1e293b;}
.lib-tab.active{background:#fff;color:#3b82f6;border-color:#e2e8f0;border-bottom:2px solid #fff;margin-bottom:-2px;font-weight:700;}
.lib-table{width:100%;border-collapse:collapse;}
.lib-table th{padding:.75rem 1rem;text-align:left;font-size:.8rem;font-weight:600;color:#475569;background:#f8fafc;border-bottom:1px solid #e2e8f0;}
.lib-table td{padding:.75rem 1rem;border-bottom:1px solid #f1f5f9;color:#1e293b;font-size:.875rem;vertical-align:middle;}
.lib-table tr:hover td{background:#f8fafc;}
.lib-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;}
.lib-form{background:#f8fafc;border-top:2px solid #e2e8f0;padding:1.25rem;}
.lib-form h4{margin:0 0 1rem;font-size:.95rem;color:#1e293b;}
.lib-form-row{display:grid;grid-template-columns:1fr 1fr auto;gap:.75rem;align-items:end;}
.lib-form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:.75rem;align-items:end;}
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
<div class="lib-tabs">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="<?php echo $esc($baseLib . '?tab=' . $key); ?>"
       class="lib-tab<?php echo $tab === $key ? ' active' : ''; ?>">
        <?php echo $esc($label); ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- ═══════════════════════════════════════════ TAB: BENEFITS ══ -->
<?php if ($tab === 'benefits'): ?>
<div class="lib-card">
    <?php if (empty($benefits)): ?>
    <div style="text-align:center;padding:2.5rem;color:#64748b;">
        <p style="font-size:2rem;margin:0;">📭</p>
        <p>Noch keine Benefits vorhanden.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="lib-table">
            <thead><tr><th>Gruppe</th><th>Icon</th><th>Bezeichnung</th><th style="text-align:center;">Aktionen</th></tr></thead>
            <tbody>
            <?php foreach ($benefits as $b): ?>
            <tr>
                <td><span style="font-size:.78rem;color:#64748b;"><?php echo $esc($b->group_name ?? ''); ?></span></td>
                <td style="font-size:1.2rem;"><?php echo $esc($b->icon ?? ''); ?></td>
                <td><strong><?php echo $esc($b->title ?? ''); ?></strong></td>
                <td style="text-align:center;">
                    <form method="post" action="<?php echo $esc($baseLib . '?tab=benefits'); ?>" style="display:inline;">
                        <input type="hidden" name="_jpg_csrf"  value="<?php echo $esc($csrf); ?>">
                        <input type="hidden" name="lib_action" value="delete_benefit">
                        <input type="hidden" name="benefit_id" value="<?php echo (int)$b->id; ?>">
                        <button type="submit" class="btn btn-sm" style="background:#fee2e2;color:#991b1b;border:none;border-radius:4px;padding:.3rem .6rem;cursor:pointer;"
                                onclick="return confirm('Benefit wirklich löschen?')">🗑️</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <!-- Neues Benefit hinzufügen -->
    <div class="lib-form">
        <h4>➕ Neues Benefit</h4>
        <form method="post" action="<?php echo $esc($baseLib . '?tab=benefits'); ?>">
            <input type="hidden" name="_jpg_csrf"  value="<?php echo $esc($csrf); ?>">
            <input type="hidden" name="lib_action" value="save_benefit">
            <div class="lib-form-row-3">
                <div>
                    <label style="font-size:.8rem;font-weight:600;color:#475569;display:block;margin-bottom:.35rem;">Gruppe</label>
                    <input type="text" name="group_name" class="form-control" placeholder="z.B. work-life" required style="width:100%;padding:.55rem .75rem;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.875rem;">
                </div>
                <div>
                    <label style="font-size:.8rem;font-weight:600;color:#475569;display:block;margin-bottom:.35rem;">Icon (Emoji)</label>
                    <input type="text" name="icon" class="form-control" placeholder="🏠" maxlength="4" style="width:100%;padding:.55rem .75rem;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.875rem;">
                </div>
                <div>
                    <label style="font-size:.8rem;font-weight:600;color:#475569;display:block;margin-bottom:.35rem;">Bezeichnung <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="Home Office" required style="width:100%;padding:.55rem .75rem;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.875rem;">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="white-space:nowrap;">💾 Speichern</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════ TAB: SKILLS ══ -->
<?php elseif ($tab === 'skills'): ?>
<div class="lib-card">
    <?php if (empty($skills)): ?>
    <div style="text-align:center;padding:2.5rem;color:#64748b;">
        <p style="font-size:2rem;margin:0;">📭</p>
        <p>Noch keine Skills vorhanden.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="lib-table">
            <thead><tr><th>Gruppe</th><th>Skill</th><th style="text-align:center;">Aktionen</th></tr></thead>
            <tbody>
            <?php foreach ($skills as $s): ?>
            <tr>
                <td><span style="font-size:.78rem;color:#64748b;"><?php echo $esc($s->group_name ?? ''); ?></span></td>
                <td><strong><?php echo $esc($s->skill_name ?? ''); ?></strong></td>
                <td style="text-align:center;">
                    <form method="post" action="<?php echo $esc($baseLib . '?tab=skills'); ?>" style="display:inline;">
                        <input type="hidden" name="_jpg_csrf"  value="<?php echo $esc($csrf); ?>">
                        <input type="hidden" name="lib_action" value="delete_skill">
                        <input type="hidden" name="skill_id"   value="<?php echo (int)$s->id; ?>">
                        <button type="submit" class="btn btn-sm" style="background:#fee2e2;color:#991b1b;border:none;border-radius:4px;padding:.3rem .6rem;cursor:pointer;"
                                onclick="return confirm('Skill wirklich löschen?')">🗑️</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <div class="lib-form">
        <h4>➕ Neuer Skill</h4>
        <form method="post" action="<?php echo $esc($baseLib . '?tab=skills'); ?>">
            <input type="hidden" name="_jpg_csrf"  value="<?php echo $esc($csrf); ?>">
            <input type="hidden" name="lib_action" value="save_skill">
            <div class="lib-form-row">
                <div>
                    <label style="font-size:.8rem;font-weight:600;color:#475569;display:block;margin-bottom:.35rem;">Gruppe</label>
                    <input type="text" name="group_name" class="form-control" placeholder="z.B. programming" required style="width:100%;padding:.55rem .75rem;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.875rem;">
                </div>
                <div>
                    <label style="font-size:.8rem;font-weight:600;color:#475569;display:block;margin-bottom:.35rem;">Bezeichnung <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="skill_name" class="form-control" placeholder="PHP" required style="width:100%;padding:.55rem .75rem;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.875rem;">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">💾 Speichern</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════ TAB: KATEGORIEN ══ -->
<?php elseif ($tab === 'categories'): ?>
<div class="lib-card">
    <?php if (empty($categories)): ?>
    <div style="text-align:center;padding:2.5rem;color:#64748b;">
        <p style="font-size:2rem;margin:0;">📭</p>
        <p>Noch keine Kategorien vorhanden.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="lib-table">
            <thead><tr><th>Name</th><th>Slug</th><th>Status</th><th style="text-align:center;">Aktionen</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $c): ?>
            <tr>
                <td><strong><?php echo $esc($c->name ?? ''); ?></strong></td>
                <td><code style="font-size:.78rem;background:#f1f5f9;padding:.15rem .35rem;border-radius:3px;"><?php echo $esc($c->slug ?? ''); ?></code></td>
                <td><span class="status-badge <?php echo ($c->is_active ?? 1) ? 'active' : 'inactive'; ?>"><?php echo ($c->is_active ?? 1) ? 'Aktiv' : 'Inaktiv'; ?></span></td>
                <td style="text-align:center;">
                    <form method="post" action="<?php echo $esc($baseLib . '?tab=categories'); ?>" style="display:inline;">
                        <input type="hidden" name="_jpg_csrf"    value="<?php echo $esc($csrf); ?>">
                        <input type="hidden" name="lib_action"   value="delete_category">
                        <input type="hidden" name="category_id"  value="<?php echo (int)$c->id; ?>">
                        <button type="submit" class="btn btn-sm" style="background:#fee2e2;color:#991b1b;border:none;border-radius:4px;padding:.3rem .6rem;cursor:pointer;"
                                onclick="return confirm('Kategorie wirklich löschen?')">🗑️</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <div class="lib-form">
        <h4>➕ Neue Kategorie</h4>
        <form method="post" action="<?php echo $esc($baseLib . '?tab=categories'); ?>">
            <input type="hidden" name="_jpg_csrf"  value="<?php echo $esc($csrf); ?>">
            <input type="hidden" name="lib_action" value="save_category">
            <div class="lib-form-row">
                <div>
                    <label style="font-size:.8rem;font-weight:600;color:#475569;display:block;margin-bottom:.35rem;">Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="IT & Technik" required
                           style="width:100%;padding:.55rem .75rem;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.875rem;"
                           oninput="this.form.slug.value=this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/(^-|-$)/g,'')">
                </div>
                <div>
                    <label style="font-size:.8rem;font-weight:600;color:#475569;display:block;margin-bottom:.35rem;">Slug</label>
                    <input type="text" name="slug" class="form-control" placeholder="it-technik"
                           style="width:100%;padding:.55rem .75rem;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.875rem;">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">💾 Speichern</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════ TAB: TEXTBAUSTEINE ══ -->
<?php elseif ($tab === 'text'): ?>
<div class="lib-card">
    <?php if (empty($textItems)): ?>
    <div style="text-align:center;padding:2.5rem;color:#64748b;">
        <p style="font-size:2rem;margin:0;">📭</p>
        <p>Noch keine Textbausteine vorhanden.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="lib-table">
            <thead><tr><th>Kategorie</th><th>Titel</th><th style="text-align:right;">Verwendungen</th><th style="text-align:center;">Aktionen</th></tr></thead>
            <tbody>
            <?php foreach ($textItems as $tm): ?>
            <tr>
                <td><span style="font-size:.78rem;color:#64748b;"><?php echo $esc($tm->category ?? ''); ?></span></td>
                <td>
                    <strong><?php echo $esc($tm->title ?? ''); ?></strong>
                    <?php if (!empty($tm->content)): ?>
                    <div style="font-size:.78rem;color:#94a3b8;margin-top:.1rem;max-width:340px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?php echo $esc(strip_tags($tm->content)); ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td style="text-align:right;color:#64748b;"><?php echo (int)($tm->usage_count ?? 0); ?></td>
                <td style="text-align:center;">
                    <form method="post" action="<?php echo $esc($baseLib . '?tab=text'); ?>" style="display:inline;">
                        <input type="hidden" name="_jpg_csrf"   value="<?php echo $esc($csrf); ?>">
                        <input type="hidden" name="lib_action"  value="delete_text_module">
                        <input type="hidden" name="module_id"   value="<?php echo (int)$tm->id; ?>">
                        <button type="submit" class="btn btn-sm" style="background:#fee2e2;color:#991b1b;border:none;border-radius:4px;padding:.3rem .6rem;cursor:pointer;"
                                onclick="return confirm('Textbaustein wirklich löschen?')">🗑️</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <div class="lib-form">
        <h4>➕ Neuer Textbaustein</h4>
        <form method="post" action="<?php echo $esc($baseLib . '?tab=text'); ?>">
            <input type="hidden" name="_jpg_csrf"  value="<?php echo $esc($csrf); ?>">
            <input type="hidden" name="lib_action" value="save_text_module">
            <div style="display:grid;grid-template-columns:1fr 2fr;gap:.75rem;margin-bottom:.75rem;">
                <div>
                    <label style="font-size:.8rem;font-weight:600;color:#475569;display:block;margin-bottom:.35rem;">Kategorie</label>
                    <input type="text" name="category" class="form-control"
                           placeholder="z.B. Einleitung"
                           list="textcat-list"
                           style="width:100%;padding:.55rem .75rem;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.875rem;">
                    <datalist id="textcat-list">
                        <?php foreach ($textCats as $tc): ?>
                        <option value="<?php echo $esc($tc); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div>
                    <label style="font-size:.8rem;font-weight:600;color:#475569;display:block;margin-bottom:.35rem;">Titel <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="Standard-Einleitung" required
                           style="width:100%;padding:.55rem .75rem;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.875rem;">
                </div>
            </div>
            <div style="margin-bottom:.75rem;">
                <label style="font-size:.8rem;font-weight:600;color:#475569;display:block;margin-bottom:.35rem;">Inhalt</label>
                <textarea name="content" rows="5" class="form-control"
                          style="width:100%;padding:.55rem .75rem;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.875rem;resize:vertical;"
                          placeholder="Textinhalt des Bausteins..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">💾 Textbaustein speichern</button>
        </form>
    </div>
</div>
<?php endif; ?>
