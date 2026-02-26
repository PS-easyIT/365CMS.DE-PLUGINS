<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * View: Bibliotheken – 6 Tabs
 *
 * @var string               $tab
 * @var array<string,string> $tabs
 * @var array<mixed>         $data    Tab-spezifische Daten
 * @var string               $notice
 * @var string               $error
 */

$nonce       = CMS_JPG_Admin_Pages::nonce('jpg_libraries_save');
$exportNonce = CMS_JPG_Admin_Pages::nonce('jpg_export');
?>

<div class="admin-page-header">
    <div>
        <h2>📚 Bibliotheken</h2>
        <p>Textbausteine, Skills, Benefits und Kategorien verwalten</p>
    </div>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo htmlspecialchars($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Tab-Navigation -->
<div class="jpg-tabs">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="?tab=<?php echo esc_attr($key); ?>"
       class="jpg-tab<?php echo $tab === $key ? ' active' : ''; ?>">
        <?php echo esc_html($label); ?>
    </a>
    <?php endforeach; ?>
</div>

<div style="border-radius:0 10px 10px 10px;margin-top:0;display:grid;grid-template-columns:1fr 360px;gap:1.5rem;align-items:start;">

<!-- ═══════════════════════════════════════════════ TAB: TEXTBAUSTEINE ══ -->
<?php if ($tab === 'text-modules'): ?>

<div class="admin-card">
    <h3>📝 Textbausteine</h3>
    <?php $items = $data['items'] ?? []; ?>
    <?php if (empty($items)): ?>
    <div class="empty-state">
        <p style="font-size:2rem;">📭</p>
        <p>Noch keine Textbausteine vorhanden.</p>
    </div>
    <?php else: ?>
    <div class="users-table-container">
        <table class="users-table">
            <thead><tr><th>Kategorie</th><th>Titel</th><th>Verwendungen</th><th>Aktionen</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><span class="status-badge inactive"><?php echo htmlspecialchars($item->category); ?></span></td>
                <td><?php echo htmlspecialchars($item->title); ?></td>
                <td style="text-align:center;"><?php echo (int)$item->usage_count; ?></td>
                <td>
                    <div style="display:flex;gap:.35rem;">
                        <button class="btn btn-sm btn-secondary"
                                onclick="jpgLibEdit('tm',<?php echo (int)$item->id; ?>,<?php echo htmlspecialchars(json_encode(['category'=>$item->category,'title'=>$item->title,'tags'=>$item->tags,'content'=>$item->content]),ENT_QUOTES); ?>)">✏️</button>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="_jpg_nonce"   value="<?php echo esc_attr($nonce); ?>">
                            <input type="hidden" name="delete_id"    value="<?php echo (int)$item->id; ?>">
                            <button type="submit" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Wirklich löschen?')">🗑️</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Formular -->
<div class="admin-card">
    <h3 id="jpgLibFormTitle">➕ Neuer Textbaustein</h3>
    <form method="post" class="admin-form" id="jpgLibForm">
        <input type="hidden" name="_jpg_nonce"  value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="id"          value="0" id="jpgLibId">
        <div class="form-group">
            <label class="form-label">Kategorie</label>
            <input type="text" name="category" class="form-control" value="general" id="jpgLibCategory"
                   placeholder="z.B. Einleitung, Aufgaben…">
        </div>
        <div class="form-group">
            <label class="form-label">Titel <span style="color:#ef4444;">*</span></label>
            <input type="text" name="title" class="form-control" id="jpgLibTitle"
                   placeholder="Baustein-Bezeichnung…">
        </div>
        <div class="form-group">
            <label class="form-label">Inhalt</label>
            <textarea name="content" class="form-control jpg-rich-editor" id="jpgLibContent"
                      style="min-height:140px;"></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Tags (kommagetrennt)</label>
            <input type="text" name="tags" class="form-control" id="jpgLibTags"
                   placeholder="cms, hr, it, …">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">💾 Speichern</button>
        <button type="button" class="btn btn-secondary" style="width:100%;margin-top:.5rem;"
                onclick="jpgLibReset()">↩️ Zurücksetzen</button>
    </form>
</div>

<!-- ═══════════════════════════════════════════════ TAB: SKILL-MATRIX ══ -->
<?php elseif ($tab === 'skill-matrix'): ?>

<div class="admin-card">
    <h3>🎯 Skill-Matrix</h3>
    <?php $grouped = $data['grouped'] ?? []; ?>
    <?php if (empty($grouped)): ?>
    <div class="empty-state"><p style="font-size:2rem;">📭</p><p>Noch keine Skills vorhanden.</p></div>
    <?php else: ?>
    <?php foreach ($grouped as $group => $skills): ?>
    <div style="margin-bottom:1rem;">
        <div style="font-weight:700;color:#475569;font-size:.85rem;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.4rem;cursor:pointer;display:flex;justify-content:space-between;align-items:center;"
             onclick="jpgToggleGroup(this)">
            <span><?php echo htmlspecialchars($group); ?></span>
            <span class="jpg-toggle-icon" style="font-size:.8rem;color:#94a3b8;">▼</span>
        </div>
        <div class="jpg-grp-body">
        <?php foreach ($skills as $skill): ?>
        <div style="display:flex;align-items:center;gap:.5rem;padding:.35rem .5rem;border-radius:4px;margin-bottom:.25rem;background:#f8fafc;">
            <span style="flex:1;"><?php echo htmlspecialchars($skill->skill_name); ?></span>
            <button class="btn btn-sm btn-secondary"
                    onclick="jpgLibEdit('sk',<?php echo (int)$skill->id; ?>,<?php echo htmlspecialchars(json_encode(['group_name'=>$skill->group_name,'skill_name'=>$skill->skill_name,'description'=>$skill->description]),ENT_QUOTES); ?>)">✏️</button>
            <form method="post" style="display:inline;">
                <input type="hidden" name="_jpg_nonce" value="<?php echo esc_attr($nonce); ?>">
                <input type="hidden" name="delete_id"  value="<?php echo (int)$skill->id; ?>">
                <button type="submit" class="btn btn-sm btn-danger"
                        onclick="return confirm('Skill wirklich löschen?')">🗑️</button>
            </form>
        </div>
        <?php endforeach; ?>
        </div><!-- /.jpg-grp-body -->
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h3 id="jpgLibFormTitle">➕ Neuer Skill</h3>
    <form method="post" class="admin-form">
        <input type="hidden" name="_jpg_nonce"  value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="id"          value="0" id="jpgLibId">
        <div class="form-group">
            <label class="form-label">Gruppe <span style="color:#ef4444;">*</span></label>
            <input type="text" name="group_name" class="form-control" id="jpgLibGroup" placeholder="z.B. Technologie, Soft Skills…">
        </div>
        <div class="form-group">
            <label class="form-label">Skill-Name <span style="color:#ef4444;">*</span></label>
            <input type="text" name="skill_name" class="form-control" id="jpgLibTitle" placeholder="z.B. PHP, Teamfähigkeit…">
        </div>
        <div class="form-group">
            <label class="form-label">Beschreibung</label>
            <input type="text" name="description" class="form-control" id="jpgLibDesc" placeholder="Optional…">
        </div>
        <div class="form-group">
            <label class="form-label">Reihenfolge</label>
            <input type="number" name="sort_order" class="form-control" value="0" min="0">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">💾 Speichern</button>
    </form>
</div>

<!-- ═══════════════════════════════════════════════ TAB: BENEFIT-KATALOG ══ -->
<?php elseif ($tab === 'benefit-catalog'): ?>

<div class="admin-card">
    <h3>🎁 Benefit-Katalog</h3>
    <?php $grouped = $data['grouped'] ?? []; ?>
    <?php if (empty($grouped)): ?>
    <div class="empty-state"><p style="font-size:2rem;">📭</p><p>Noch keine Benefits vorhanden.</p></div>
    <?php else: ?>
    <?php foreach ($grouped as $group => $benefits): ?>
    <div style="margin-bottom:1rem;">
        <div style="font-weight:700;color:#475569;font-size:.85rem;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.4rem;cursor:pointer;display:flex;justify-content:space-between;align-items:center;"
             onclick="jpgToggleGroup(this)">
            <span><?php echo htmlspecialchars($group); ?></span>
            <span class="jpg-toggle-icon" style="font-size:.8rem;color:#94a3b8;">▼</span>
        </div>
        <div class="jpg-grp-body" style="display:flex;flex-wrap:wrap;gap:.5rem;">
            <?php foreach ($benefits as $b): ?>
            <div style="display:flex;align-items:center;gap:.4rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:20px;padding:.2rem .6rem .2rem .5rem;">
                <span><?php echo htmlspecialchars($b->icon . ' ' . $b->title); ?></span>
                <?php if (!(int)$b->active): ?>
                <span class="status-badge inactive" style="font-size:.7rem;">inaktiv</span>
                <?php endif; ?>
                <button class="btn btn-sm btn-secondary"
                        style="padding:.1rem .35rem;font-size:.75rem;"
                        onclick="jpgLibEdit('bc',<?php echo (int)$b->id; ?>,<?php echo htmlspecialchars(json_encode(['group_name'=>$b->group_name,'title'=>$b->title,'description'=>$b->description,'icon'=>$b->icon,'active'=>$b->active]),ENT_QUOTES); ?>)">✏️</button>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="_jpg_nonce" value="<?php echo esc_attr($nonce); ?>">
                    <input type="hidden" name="delete_id"  value="<?php echo (int)$b->id; ?>">
                    <button type="submit" class="btn btn-sm btn-danger"
                            style="padding:.1rem .35rem;font-size:.75rem;"
                            onclick="return confirm('Benefit wirklich löschen?')">🗑️</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h3>➕ Neuer Benefit</h3>
    <form method="post" class="admin-form">
        <input type="hidden" name="_jpg_nonce" value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="id"         value="0" id="jpgLibId">
        <div class="form-group">
            <label class="form-label">Gruppe <span style="color:#ef4444;">*</span></label>
            <input type="text" name="group_name" class="form-control" id="jpgLibGroup" placeholder="z.B. Vergütung, Work-Life…">
        </div>
        <div class="form-group">
            <label class="form-label">Titel <span style="color:#ef4444;">*</span></label>
            <input type="text" name="title" class="form-control" id="jpgLibTitle" placeholder="z.B. Home-Office…">
        </div>
        <div style="display:grid;grid-template-columns:1fr 80px;gap:.75rem;">
            <div class="form-group">
                <label class="form-label">Beschreibung</label>
                <input type="text" name="description" class="form-control" placeholder="Optional…">
            </div>
            <div class="form-group">
                <label class="form-label">Icon</label>
                <input type="text" name="icon" class="form-control" value="✓" maxlength="10">
            </div>
        </div>
        <div class="form-group">
            <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
                <input type="checkbox" name="active" value="1" checked>
                Aktiv
            </label>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">💾 Speichern</button>
    </form>
</div>

<!-- ═══════════════════════════════════════════════ TAB: ANFORDERUNGS-LISTE ══ -->
<?php elseif ($tab === 'requirement-items'): ?>

<div class="admin-card">
    <h3>📋 Anforderungs-Liste</h3>
    <p style="color:#64748b;font-size:.875rem;margin:0 0 1rem;">
        Eigene Anforderungs-Einträge, die im Generator neben der Skill-Matrix verwendet werden können.
    </p>
    <?php $grouped = $data['grouped'] ?? []; ?>
    <?php if (empty($grouped)): ?>
    <div class="empty-state">
        <p style="font-size:2rem;">📭</p>
        <p>Noch keine Anforderungs-Einträge vorhanden.</p>
    </div>
    <?php else: ?>
    <?php foreach ($grouped as $group => $items): ?>
    <div style="margin-bottom:1rem;">
        <div style="font-weight:700;color:#475569;font-size:.85rem;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.4rem;cursor:pointer;display:flex;justify-content:space-between;align-items:center;"
             onclick="jpgToggleGroup(this)">
            <span><?php echo htmlspecialchars($group); ?></span>
            <span class="jpg-toggle-icon" style="font-size:.8rem;color:#94a3b8;">▼</span>
        </div>
        <div class="jpg-grp-body" style="display:flex;flex-direction:column;gap:.35rem;">
            <?php foreach ($items as $ri): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:.35rem .75rem;">
                <div style="flex:1;">
                    <span style="font-size:.875rem;">
                        <?php echo htmlspecialchars(($ri->icon ?? '') . ' ' . $ri->title); ?>
                    </span>
                    <?php
                        $rtColors = ['must'=>['#fee2e2','#991b1b','Pflicht'],'nice'=>['#fef3c7','#92400e','Wünsch'],'optional'=>['#d1fae5','#065f46','Optional']];
                        $rt = $ri->req_type ?? 'must';
                        [$rtBg, $rtCol, $rtLbl] = $rtColors[$rt] ?? ['#f1f5f9','#64748b','?'];
                    ?>
                    <span style="background:<?php echo $rtBg; ?>;color:<?php echo $rtCol; ?>;padding:.1rem .45rem;border-radius:4px;font-size:.72rem;font-weight:700;margin-left:.45rem;"><?php echo $rtLbl; ?></span>
                    <?php if (!empty($ri->description)): ?>
                    <div style="font-size:.79rem;color:#64748b;margin-top:.15rem;"><?php echo htmlspecialchars($ri->description); ?></div>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:.35rem;flex-shrink:0;">
                    <button class="btn btn-sm btn-secondary"
                            style="padding:.1rem .35rem;font-size:.75rem;"
                            onclick="jpgLibEdit('ri',<?php echo (int)$ri->id; ?>,<?php echo htmlspecialchars(json_encode(['group_name'=>$ri->group_name,'title'=>$ri->title,'sort_order'=>$ri->sort_order,'req_type'=>$ri->req_type ?? 'must','description'=>$ri->description ?? '','icon'=>$ri->icon ?? '']),ENT_QUOTES); ?>)">✏️</button>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="_jpg_nonce" value="<?php echo esc_attr($nonce); ?>">
                        <input type="hidden" name="delete_id"  value="<?php echo (int)$ri->id; ?>">
                        <button type="submit" class="btn btn-sm btn-danger"
                                style="padding:.1rem .35rem;font-size:.75rem;"
                                onclick="return confirm('Eintrag wirklich löschen?')">🗑️</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h3 id="jpgLibFormTitle">➕ Neuer Eintrag</h3>
    <form method="post" class="admin-form" id="jpgLibForm">
        <input type="hidden" name="_jpg_nonce" value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="id"         value="0" id="jpgLibId">
        <div class="form-group">
            <label class="form-label">Gruppe</label>
            <input type="text" name="group_name" class="form-control" id="jpgLibGroup" placeholder="z.B. Sprachen, Hard Skills…">
        </div>
        <div class="form-group">
            <label class="form-label">Anforderung / Titel <span style="color:#ef4444;">*</span></label>
            <input type="text" name="title" class="form-control" id="jpgLibTitle" placeholder="z.B. Führerschein Klasse B…">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 2rem;gap:.75rem;">
            <div class="form-group">
                <label class="form-label">Typ</label>
                <select name="req_type" class="form-control" id="jpgLibReqType">
                    <option value="must">Pflicht</option>
                    <option value="nice">Wünsch</option>
                    <option value="optional">Optional</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Icon (Emoji)</label>
                <input type="text" name="icon" class="form-control" id="jpgLibIcon" placeholder="💻" style="font-size:1.2rem;" maxlength="10">
            </div>
            <div class="form-group">
                <label class="form-label">Reihenfolge</label>
                <input type="number" name="sort_order" class="form-control" id="jpgLibSortOrder" value="0" min="0" max="9999">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Beschreibung (optional)</label>
            <input type="text" name="description" class="form-control" id="jpgLibDesc" placeholder="Kurze erläuternde Beschreibung">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">💾 Speichern</button>
        <button type="button" class="btn btn-secondary" style="width:100%;margin-top:.5rem;"
                onclick="document.getElementById('jpgLibId').value=0;document.getElementById('jpgLibGroup').value='';document.getElementById('jpgLibTitle').value='';document.getElementById('jpgLibFormTitle').textContent='➕ Neuer Eintrag';">↩️ Zurücksetzen</button>
    </form>
</div>

<!-- ═══════════════════════════════════════════════ TAB: JOB-KATEGORIEN ══ -->
<?php elseif ($tab === 'job-categories'): ?>

<div class="admin-card">
    <h3>🏷️ Job-Kategorien</h3>
    <?php $items = $data['items'] ?? []; ?>
    <?php if (empty($items)): ?>
    <div class="empty-state"><p style="font-size:2rem;">📭</p><p>Noch keine Kategorien vorhanden.</p></div>
    <?php else: ?>
    <div class="users-table-container">
        <table class="users-table">
            <thead><tr><th>Name</th><th>Farbe</th><th>Aktiv</th><th>Aktionen</th></tr></thead>
            <tbody>
            <?php foreach ($items as $cat): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($cat->name); ?></strong></td>
                <td>
                    <span style="display:inline-block;width:16px;height:16px;background:<?php echo htmlspecialchars($cat->color); ?>;border-radius:3px;vertical-align:middle;margin-right:.4rem;"></span>
                    <?php echo htmlspecialchars($cat->color); ?>
                </td>
                <td>
                    <span class="status-badge <?php echo (int)$cat->active ? 'active' : 'inactive'; ?>">
                        <?php echo (int)$cat->active ? 'Aktiv' : 'Inaktiv'; ?>
                    </span>
                </td>
                <td>
                    <div style="display:flex;gap:.35rem;">
                        <button class="btn btn-sm btn-secondary"
                                onclick="jpgLibEdit('jc',<?php echo (int)$cat->id; ?>,<?php echo htmlspecialchars(json_encode(['name'=>$cat->name,'description'=>$cat->description,'color'=>$cat->color,'active'=>$cat->active]),ENT_QUOTES); ?>)">✏️</button>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="_jpg_nonce" value="<?php echo esc_attr($nonce); ?>">
                            <input type="hidden" name="delete_id"  value="<?php echo (int)$cat->id; ?>">
                            <button type="submit" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Kategorie wirklich löschen?')">🗑️</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h3>➕ Neue Kategorie</h3>
    <form method="post" class="admin-form">
        <input type="hidden" name="_jpg_nonce" value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="id"         value="0" id="jpgLibId">
        <div class="form-group">
            <label class="form-label">Name <span style="color:#ef4444;">*</span></label>
            <input type="text" name="name" class="form-control" id="jpgLibTitle" placeholder="z.B. IT & Software…">
        </div>
        <div class="form-group">
            <label class="form-label">Beschreibung</label>
            <input type="text" name="description" class="form-control" placeholder="Optional…">
        </div>
        <div style="display:grid;grid-template-columns:1fr 80px;gap:.75rem;">
            <div class="form-group">
                <label class="form-label">Farbe (HEX)</label>
                <input type="text" name="color" class="form-control" value="#3b82f6" maxlength="7" placeholder="#3b82f6">
            </div>
            <div class="form-group">
                <label class="form-label">&nbsp;</label>
                <input type="color" name="color_picker" value="#3b82f6"
                       oninput="this.form.color.value=this.value"
                       class="form-control" style="padding:.25rem;height:42px;">
            </div>
        </div>
        <div class="form-group">
            <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
                <input type="checkbox" name="active" value="1" checked>
                Aktiv
            </label>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">💾 Speichern</button>
        <button type="button" class="btn btn-secondary" style="width:100%;margin-top:.5rem;"
                onclick="jpgLibReset()">↩️ Zurücksetzen</button>
    </form>
</div>

<!-- ══════════════════════════════════════ Branchenpaket Seeder ══ -->
<div class="admin-card">
    <h3>🏗️ Branchenpaket einspielen</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:.75rem;">
        Skills und Textbausteine für bestimmte Branchen nachträglich hinzufügen.<br>
        <span style="color:#94a3b8;">Bereits vorhandene Einträge werden übersprungen (INSERT IGNORE).</span>
    </p>
    <form method="post" class="admin-form">
        <input type="hidden" name="_jpg_nonce"  value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="_jpg_action" value="seed_industry">
        <?php
        $industryPackages = [
            'it'          => '💻 IT & Software',
            'bau'         => '🏗️ Bauwesen & Architektur',
            'strassenbau' => '🛣️ Straßenbau & Tiefbau',
            'logistik'    => '🚚 Transport & Logistik',
            'banking'     => '🏦 Banken & Finanzdienstleistungen',
            'recht'       => '⚖️ Recht & Notariat',
            'maschinenbau'=> '⚙️ Maschinenbau & Anlagenbau',
            'automobil'   => '🚗 Automobil & Mobilität',
            'energie'     => '⚡ Energie & Umwelt',
            'pharma'      => '💊 Pharma & Biotechnologie',
            'gastronomie' => '🍽️ Gastronomie & Hotellerie',
            'handel'      => '🛒 Einzelhandel & E-Commerce',
            'bildung'     => '🎓 Bildung & Forschung',
            'medien'      => '🎬 Medien & Kreativwirtschaft',
            'oeffentlich' => '🏛️ Öffentlicher Dienst & Verwaltung',
            'soziales'    => '🤝 Soziales & Non-Profit',
        ];
        ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.3rem .5rem;">
            <?php foreach ($industryPackages as $key => $label): ?>
            <label class="checkbox-label" style="display:flex;align-items:center;gap:.4rem;cursor:pointer;padding:.3rem .4rem;border-radius:6px;font-size:.85rem;">
                <input type="checkbox" name="industries[]" value="<?php echo esc_attr($key); ?>">
                <?php echo htmlspecialchars($label); ?>
            </label>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:.5rem;margin-top:.6rem;flex-wrap:wrap;">
            <button type="button" class="btn btn-secondary btn-sm"
                    onclick="this.closest('form').querySelectorAll('input[type=checkbox]').forEach(c=>c.checked=true)">
                Alle wählen
            </button>
            <button type="button" class="btn btn-secondary btn-sm"
                    onclick="this.closest('form').querySelectorAll('input[type=checkbox]').forEach(c=>c.checked=false)">
                Keine
            </button>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:.75rem;">
            ⬇️ Ausgewählte Branchen einspielen
        </button>
    </form>
</div>

<!-- ═══════════════════════════════════════════════ TAB: IMPORT/EXPORT ══ -->
<?php elseif ($tab === 'import-export'): ?>

<div class="admin-card">
    <h3>📤 Profile exportieren</h3>
    <?php
    $allProfiles = CMS_JPG_Profiles::instance()->get_list(['status' => 'published', 'limit' => 100]);
    if (!empty($allProfiles)): ?>
    <div class="users-table-container">
        <table class="users-table">
            <thead><tr><th>Titel</th><th>Status</th><th>Export</th></tr></thead>
            <tbody>
            <?php foreach ($allProfiles as $p): ?>
            <tr>
                <td><?php echo htmlspecialchars($p->title); ?></td>
                <td><span class="status-badge active">Veröffentlicht</span></td>
                <td>
                    <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-generator?_jpg_export=json&id=' . (int)$p->id . '&_jpg_nonce=' . $exportNonce); ?>"
                       class="btn btn-sm btn-secondary" target="_blank">📄 JSON</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <p style="color:#64748b;">Keine veröffentlichten Profile vorhanden.</p>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h3>📥 Profil importieren</h3>
    <p style="color:#64748b;font-size:.875rem;">Importiert ein zuvor exportiertes JSON-Profil als Entwurf.</p>
    <form method="post" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="_jpg_nonce"    value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="import_json"   value="1">
        <div class="form-group">
            <label class="form-label">JSON-Datei <span style="color:#ef4444;">*</span></label>
            <input type="file" name="import_file" class="form-control" accept=".json" required>
        </div>
        <button type="submit" class="btn btn-primary">📥 Importieren</button>
    </form>
</div>

<?php endif; ?>
</div><!-- /grid -->

<script>
function jpgLibEdit(type, id, data) {
    var idField = document.getElementById('jpgLibId');
    if (idField) idField.value = id;
    if (type === 'tm' && data) {
        var f = document.getElementById('jpgLibCategory');
        if (f) f.value = data.category || '';
        f = document.getElementById('jpgLibTitle');
        if (f) f.value = data.title || '';
        f = document.getElementById('jpgLibTags');
        if (f) f.value = data.tags || '';
        f = document.getElementById('jpgLibContent');
        if (f) f.value = data.content || '';
        var h = document.getElementById('jpgLibFormTitle');
        if (h) h.textContent = '✏️ Textbaustein bearbeiten';
    }
    if (type === 'sk' && data) {
        var f = document.getElementById('jpgLibGroup');
        if (f) f.value = data.group_name || '';
        f = document.getElementById('jpgLibTitle');
        if (f) f.value = data.skill_name || '';
        f = document.getElementById('jpgLibDesc');
        if (f) f.value = data.description || '';
    }
    if ((type === 'jc' || type === 'bc') && data) {
        var f = document.getElementById('jpgLibTitle');
        if (f) f.value = data.name || data.title || '';
        f = document.getElementById('jpgLibGroup');
        if (f) f.value = data.group_name || '';
        var h = document.getElementById('jpgLibFormTitle');
        if (h) h.textContent = '✏️ Bearbeiten';
    }
    if (type === 'ri' && data) {
        var f = document.getElementById('jpgLibGroup');
        if (f) f.value = data.group_name || '';
        f = document.getElementById('jpgLibTitle');
        if (f) f.value = data.title || '';
        f = document.getElementById('jpgLibReqType');
        if (f) f.value = data.req_type || 'must';
        f = document.getElementById('jpgLibDesc');
        if (f) f.value = data.description || '';
        f = document.getElementById('jpgLibIcon');
        if (f) f.value = data.icon || '';
        f = document.getElementById('jpgLibSortOrder');
        if (f) f.value = data.sort_order || 0;
        var h = document.getElementById('jpgLibFormTitle');
        if (h) h.textContent = '✏️ Eintrag bearbeiten';
    }
    window.scrollTo({ top: document.getElementById('jpgLibId').closest('.admin-card').offsetTop - 80, behavior: 'smooth' });
}
function jpgLibReset() {
    var idField = document.getElementById('jpgLibId');
    if (idField) idField.value = '0';
    var form = document.getElementById('jpgLibForm');
    if (form) form.reset();
    var h = document.getElementById('jpgLibFormTitle');
    if (h) h.textContent = '➕ Neuer Textbaustein';
}

/**
 * Klappt eine Gruppe in der Bibliotheks-Ansicht ein oder aus.
 * @param {HTMLElement} header – Das angeklickte Gruppen-Header-Element
 */
function jpgToggleGroup(header) {
    var body = header.nextElementSibling;
    if (!body) return;
    var isOpen = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : '';
    var icon = header.querySelector('.jpg-toggle-icon');
    if (icon) icon.textContent = isOpen ? '\u25b6' : '\u25bc';
}
</script>
