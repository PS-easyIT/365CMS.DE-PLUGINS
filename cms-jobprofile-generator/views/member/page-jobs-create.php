<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * Member-View: Neue Stellenanzeige erstellen (5-Tab-Wizard)
 *
 * @var array<object>               $categories    Job-Kategorien
 * @var array<string,array<object>> $allBenefits   Gruppierte Benefits
 * @var array<object>               $tasks         Aufgaben (leer bei Neu)
 * @var array<object>               $requirements  Anforderungen (leer bei Neu)
 * @var int[]                       $benefitIds    Benefit-IDs (leer bei Neu)
 * @var array<object>               $workflowSteps Workflow-Stufen
 * @var string                      $csrf
 * @var string                      $baseUrl
 * @var string                      $notice
 * @var string                      $error
 */

$esc      = fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
$baseUrl  = $baseUrl ?? '/member/jobs';
$allBenefits  = $allBenefits  ?? [];
$tasks        = $tasks        ?? [];
$requirements = $requirements ?? [];
$benefitIds   = $benefitIds   ?? [];
$createPrefill = is_array($createPrefill ?? null) ? $createPrefill : [];
$formData = array_merge([
    'title' => '',
    'slug' => '',
    'job_category_id' => 0,
    'location' => '',
    'employment_type' => 'Vollzeit',
    'remote_option' => 'none',
    'experience_level' => 'mid',
    'salary_min' => '',
    'salary_max' => '',
    'summary' => '',
    'description' => '',
], $createPrefill);

$escapedFormData = [
    'title' => htmlspecialchars((string) ($formData['title'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'slug' => htmlspecialchars((string) ($formData['slug'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'location' => htmlspecialchars((string) ($formData['location'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'salary_min' => htmlspecialchars((string) ($formData['salary_min'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'salary_max' => htmlspecialchars((string) ($formData['salary_max'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'summary' => htmlspecialchars((string) ($formData['summary'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'description' => htmlspecialchars((string) ($formData['description'] ?? ''), ENT_QUOTES, 'UTF-8'),
];
?>

<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo $esc($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo $esc($error); ?></div>
<?php endif; ?>

<!-- ══ TAB-NAVIGATION ═══════════════════════════════════════════════════════ -->
<div class="tabs" style="margin-bottom:1.5rem;display:flex;gap:.3rem;border-bottom:2px solid #e2e8f0;flex-wrap:wrap;">
    <button class="tab-btn active" onclick="switchTab('tab-basis',this)" type="button">📋 Basisdaten</button>
    <button class="tab-btn" onclick="switchTab('tab-tasks',this)" type="button">🔧 Aufgaben</button>
    <button class="tab-btn" onclick="switchTab('tab-req',this)" type="button">🎯 Anforderungen</button>
    <button class="tab-btn" onclick="switchTab('tab-benefits',this)" type="button">🎁 Benefits</button>
    <button class="tab-btn" onclick="switchTab('tab-workflow',this)" type="button">🔄 Workflow</button>
</div>

<form method="POST" id="jpgCreateForm">
    <input type="hidden" name="_jpg_csrf" value="<?php echo $esc($csrf); ?>">

    <!-- ══ TAB 1: BASISDATEN ════════════════════════════════════════════════ -->
    <div id="tab-basis" class="tab-content active">
        <div class="admin-card">
            <h3>📋 Basisdaten</h3>

            <div class="form-group">
                <label class="form-label" for="create_title">
                    Stellentitel <span style="color:#ef4444;">*</span>
                </label>
                <input type="text" id="create_title" name="title" class="form-control"
                       placeholder="z. B. Senior Backend-Entwickler (m/w/d)" required maxlength="255"
                      value="<?php echo $escapedFormData['title']; ?>">
            </div>

            <!-- Phase 14.2: Inline-Slug-Editor -->
            <div class="form-group">
                <label class="form-label" for="create_slug">URL-Slug</label>
                <div style="display:flex;gap:.5rem;align-items:center;">
                    <span style="color:#64748b;font-size:.875rem;white-space:nowrap;">/jobs/</span>
                    <input type="text" id="create_slug" name="slug" class="form-control"
                              value="<?php echo $escapedFormData['slug']; ?>"
                           pattern="[a-z0-9\-]+" maxlength="100"
                           placeholder="automatisch aus Titel generiert">
                </div>
                <small class="form-text">Optional. Leer lassen = automatisch generiert.</small>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="create_category">Kategorie</label>
                    <select id="create_category" name="job_category_id" class="form-control">
                        <option value="">— Keine Kategorie —</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int)$cat->id; ?>"
                            <?php echo ($formData['job_category_id'] === (int)$cat->id) ? 'selected' : ''; ?>>
                            <?php echo $esc($cat->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="create_location">Standort</label>
                    <input type="text" id="create_location" name="location" class="form-control"
                           placeholder="z. B. Berlin, München, Remote"
                              value="<?php echo $escapedFormData['location']; ?>">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="create_employment">Anstellungsart</label>
                    <select id="create_employment" name="employment_type" class="form-control">
                        <?php foreach (['Vollzeit','Teilzeit','Freelance','Praktikum','Ausbildung'] as $et): ?>
                        <option value="<?php echo $esc($et); ?>"
                            <?php echo ($formData['employment_type'] === $et) ? 'selected' : ''; ?>>
                            <?php echo $esc($et); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="create_remote">Remote-Option</label>
                    <select id="create_remote" name="remote_option" class="form-control">
                        <?php foreach (['none' => 'Vor Ort', 'hybrid' => 'Hybrid', 'full' => 'Vollständig Remote'] as $val => $label): ?>
                        <option value="<?php echo $esc($val); ?>"
                            <?php echo ($formData['remote_option'] === $val) ? 'selected' : ''; ?>>
                            <?php echo $esc($label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Level / Erfahrung</label>
                    <select name="experience_level" class="form-control">
                        <?php foreach (['junior' => 'Junior','mid' => 'Mid-Level','senior' => 'Senior','lead' => 'Lead/Principal'] as $val => $label): ?>
                        <option value="<?php echo $esc($val); ?>"
                            <?php echo ($formData['experience_level'] === $val) ? 'selected' : ''; ?>>
                            <?php echo $esc($label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="create_sal_min">Gehalt ab (€/Jahr, optional)</label>
                    <input type="number" id="create_sal_min" name="salary_min" class="form-control"
                           placeholder="55000" min="0" step="500"
                              value="<?php echo $escapedFormData['salary_min']; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="create_sal_max">Gehalt bis (€/Jahr, optional)</label>
                    <input type="number" id="create_sal_max" name="salary_max" class="form-control"
                           placeholder="70000" min="0" step="500"
                              value="<?php echo $escapedFormData['salary_max']; ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="create_summary">
                    Kurzbeschreibung <span id="summaryCount" style="color:#94a3b8;font-size:.8rem;">(0/300)</span>
                </label>
                <textarea id="create_summary" name="summary" class="form-control"
                          rows="3" maxlength="300"
                          placeholder="Kurze Beschreibung der Stelle (max. 300 Zeichen)…"
                          oninput="updateCreateCount('create_summary','summaryCount',300)"><?php echo $escapedFormData['summary']; ?></textarea>
                <small class="form-text">Wird in der Listenansicht angezeigt.</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="create_desc">Ausführliche Beschreibung</label>
                <textarea id="create_desc" name="description" class="form-control"
                          style="min-height:180px;resize:vertical;"
                          placeholder="Beschreibe die Stelle ausführlich: Aufgabenumfeld, Team, Technologien…"><?php echo $escapedFormData['description']; ?></textarea>
                <small class="form-text">HTML ist erlaubt (&lt;p&gt;, &lt;ul&gt;, &lt;strong&gt;, &lt;em&gt;).</small>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:1rem;">
            <button type="button" class="btn btn-primary"
                    onclick="switchTab('tab-tasks',document.querySelectorAll('.tab-btn')[1])">Weiter: Aufgaben →</button>
        </div>
    </div><!-- /#tab-basis -->

    <!-- ══ TAB 2: AUFGABEN ══════════════════════════════════════════════════ -->
    <div id="tab-tasks" class="tab-content">
        <div class="admin-card">
            <h3>🔧 Aufgaben & Tätigkeiten</h3>
            <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">
                Mindestens 3 konkrete Aufgaben beschreiben. Per Drag &amp; Drop sortierbar.
            </p>

            <div id="jpgCreateTaskList" style="margin-bottom:.75rem;">
                <?php if (!empty($tasks)): ?>
                <?php foreach ($tasks as $task): ?>
                <div class="jpg-task-item" draggable="true"
                     style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;background:#f8fafc;border-radius:6px;padding:.35rem .5rem;">
                    <span style="cursor:grab;color:#94a3b8;flex:0 0 auto;">⠿</span>
                    <input type="text" name="tasks[]" class="form-control" style="flex:1;"
                           value="<?php echo $esc(is_object($task) ? $task->task_text : (string)$task); ?>">
                    <button type="button" onclick="this.closest('.jpg-task-item').remove()"
                            style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;padding:.15rem .3rem;">✕</button>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="jpg-task-item" draggable="true"
                     style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;background:#f8fafc;border-radius:6px;padding:.35rem .5rem;">
                    <span style="cursor:grab;color:#94a3b8;flex:0 0 auto;">⠿</span>
                    <input type="text" name="tasks[]" class="form-control" style="flex:1;"
                           placeholder="Aufgabe <?php echo $i + 1; ?> beschreiben…">
                    <button type="button" onclick="this.closest('.jpg-task-item').remove()"
                            style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;padding:.15rem .3rem;">✕</button>
                </div>
                <?php endfor; ?>
                <?php endif; ?>
            </div>

            <button type="button" class="btn btn-secondary btn-sm" onclick="jpgAddCreateTask()">
                ➕ Aufgabe hinzufügen
            </button>
        </div>

        <div style="display:flex;justify-content:space-between;gap:.75rem;margin-top:1rem;">
            <button type="button" class="btn btn-secondary"
                    onclick="switchTab('tab-basis',document.querySelectorAll('.tab-btn')[0])">← Zurück</button>
            <button type="button" class="btn btn-primary"
                    onclick="switchTab('tab-req',document.querySelectorAll('.tab-btn')[2])">Weiter: Anforderungen →</button>
        </div>
    </div><!-- /#tab-tasks -->

    <!-- ══ TAB 3: ANFORDERUNGEN ═════════════════════════════════════════════ -->
    <div id="tab-req" class="tab-content">
        <div class="admin-card">
            <h3>🎯 Anforderungsprofil</h3>
            <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">
                <strong>Pflicht:</strong> unverzichtbare Qualifikationen. <strong>Wünschenswert:</strong> Bonus-Kriterien.
            </p>

            <div id="jpgCreateReqList">
                <?php if (!empty($requirements)): ?>
                <?php foreach ($requirements as $req): ?>
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;">
                    <input type="text" name="req_text[]" class="form-control" style="flex:1;"
                           value="<?php echo $esc($req->req_text ?? ''); ?>">
                    <select name="req_type[]" class="form-control" style="width:auto;min-width:130px;">
                        <option value="must" <?php echo (($req->req_type ?? '') === 'must') ? 'selected' : ''; ?>>Pflicht</option>
                        <option value="nice" <?php echo (($req->req_type ?? '') === 'nice') ? 'selected' : ''; ?>>Wünschenswert</option>
                    </select>
                    <button type="button" onclick="this.parentElement.remove()"
                            style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;">✕</button>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;">
                    <input type="text" name="req_text[]" class="form-control" style="flex:1;"
                           placeholder="z. B. 3 Jahre Python-Erfahrung">
                    <select name="req_type[]" class="form-control" style="width:auto;min-width:130px;">
                        <option value="must">Pflicht</option>
                        <option value="nice">Wünschenswert</option>
                    </select>
                    <button type="button" onclick="this.parentElement.remove()"
                            style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;">✕</button>
                </div>
                <?php endif; ?>
            </div>

            <button type="button" class="btn btn-secondary btn-sm" style="margin-top:.5rem;"
                    onclick="jpgAddCreateReq()">➕ Anforderung hinzufügen</button>
        </div>

        <div style="display:flex;justify-content:space-between;gap:.75rem;margin-top:1rem;">
            <button type="button" class="btn btn-secondary"
                    onclick="switchTab('tab-tasks',document.querySelectorAll('.tab-btn')[1])">← Zurück</button>
            <button type="button" class="btn btn-primary"
                    onclick="switchTab('tab-benefits',document.querySelectorAll('.tab-btn')[3])">Weiter: Benefits →</button>
        </div>
    </div><!-- /#tab-req -->

    <!-- ══ TAB 4: BENEFITS ══════════════════════════════════════════════════ -->
    <div id="tab-benefits" class="tab-content">
        <div class="admin-card">
            <h3>🎁 Benefits & Vorteile</h3>
            <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem;">
                Wähle alle Benefits aus, die für diese Stelle zutreffen.
            </p>

            <?php if (empty($allBenefits)): ?>
            <div class="empty-state">
                <p style="font-size:2rem;margin:0;">📦</p>
                <p><strong>Noch keine Benefits konfiguriert</strong></p>
                <p style="color:#64748b;font-size:.875rem;">Ein Administrator muss erst Benefits anlegen.</p>
            </div>
            <?php else: ?>
            <?php foreach ($allBenefits as $group => $items): ?>
            <div style="margin-bottom:1.5rem;">
                <div style="font-size:.82rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#64748b;margin-bottom:.6rem;">
                    <?php echo $esc($group ?: 'Allgemein'); ?>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.4rem;">
                    <?php foreach ($items as $benefit): ?>
                    <label style="display:flex;align-items:center;gap:.5rem;padding:.4rem .6rem;border-radius:6px;cursor:pointer;border:1px solid #e2e8f0;transition:background .15s;"
                           onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background=''">
                        <input type="checkbox" name="benefit_ids[]"
                               value="<?php echo (int)$benefit->id; ?>"
                               <?php echo in_array((int)$benefit->id, $benefitIds, true) ? 'checked' : ''; ?>
                               style="accent-color:var(--admin-primary);">
                        <span style="font-size:.875rem;">
                            <?php echo !empty($benefit->icon) ? $esc($benefit->icon) . ' ' : ''; ?><?php echo $esc($benefit->title); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="display:flex;justify-content:space-between;gap:.75rem;margin-top:1rem;">
            <button type="button" class="btn btn-secondary"
                    onclick="switchTab('tab-req',document.querySelectorAll('.tab-btn')[2])">← Zurück</button>
            <button type="button" class="btn btn-primary"
                    onclick="switchTab('tab-workflow',document.querySelectorAll('.tab-btn')[4])">Weiter: Workflow →</button>
        </div>
    </div><!-- /#tab-benefits -->

    <!-- ══ TAB 5: WORKFLOW & EINREICHEN ══════════════════════════════════════ -->
    <div id="tab-workflow" class="tab-content">

        <?php
        $wfSteps = $workflowSteps ?? [];
        if (count($wfSteps) > 0): ?>
        <div class="admin-card">
            <h3>🔄 Genehmigungsprozess</h3>
            <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem;">
                Nach dem Speichern kannst du die Stellenanzeige zur Genehmigung einreichen.
                Sie durchläuft folgenden Prozess:
            </p>

            <div style="display:flex;flex-wrap:nowrap;overflow-x:auto;gap:0;align-items:flex-start;margin-bottom:1.5rem;padding:.5rem 0;">
                <div style="display:flex;flex-direction:column;align-items:center;gap:.3rem;min-width:90px;flex:1;">
                    <div style="width:2.25rem;height:2.25rem;border-radius:50%;background:#e2e8f0;
                                display:flex;align-items:center;justify-content:center;
                                font-size:.85rem;font-weight:700;color:#64748b;">E</div>
                    <div style="font-size:.75rem;text-align:center;color:#64748b;max-width:80px;line-height:1.3;">Entwurf</div>
                </div>
                <div style="align-self:flex-start;margin-top:1rem;color:#e2e8f0;font-size:.9rem;padding:0 .2rem;">→</div>

                <?php foreach ($wfSteps as $i => $step): ?>
                <div style="display:flex;flex-direction:column;align-items:center;gap:.3rem;min-width:90px;flex:1;">
                    <div style="width:2.25rem;height:2.25rem;border-radius:50%;background:#3b82f6;
                                display:flex;align-items:center;justify-content:center;
                                font-size:.8rem;font-weight:700;color:#fff;">
                        <?php echo (int)$step->sort_order; ?>
                    </div>
                    <div style="font-size:.75rem;text-align:center;color:#1e40af;max-width:80px;line-height:1.3;">
                        <?php echo $esc($step->step_name); ?>
                    </div>
                </div>
                <?php if ($i < count($wfSteps) - 1): ?>
                <div style="align-self:flex-start;margin-top:1rem;color:#bfdbfe;font-size:.9rem;padding:0 .2rem;">→</div>
                <?php endif; ?>
                <?php endforeach; ?>

                <div style="align-self:flex-start;margin-top:1rem;color:#e2e8f0;font-size:.9rem;padding:0 .2rem;">→</div>
                <div style="display:flex;flex-direction:column;align-items:center;gap:.3rem;min-width:90px;flex:1;">
                    <div style="width:2.25rem;height:2.25rem;border-radius:50%;background:#10b981;
                                display:flex;align-items:center;justify-content:center;
                                font-size:.85rem;font-weight:700;color:#fff;">✓</div>
                    <div style="font-size:.75rem;text-align:center;color:#065f46;max-width:80px;line-height:1.3;">Veröffentlicht</div>
                </div>
            </div>

            <div class="alert" style="background:#eff6ff;color:#1e40af;border-left:4px solid #3b82f6;">
                ℹ️ Nach dem <strong>Speichern</strong> als Entwurf, kannst du die Anzeige aus der Bearbeitungsansicht heraus zur Genehmigung einreichen.
            </div>
        </div>
        <?php else: ?>
        <div class="admin-card">
            <h3>🔄 Genehmigungsprozess</h3>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;">
                <p style="margin:0;color:#64748b;font-size:.875rem;">
                    ℹ️ Es ist noch kein Genehmigungsworkflow konfiguriert. Die Anzeige wird als Entwurf gespeichert.
                </p>
            </div>
        </div>
        <?php endif; ?>

        <div class="admin-card" style="background:#eff6ff;border:1px solid #bfdbfe;">
            <h3>💾 Jetzt speichern</h3>
            <p style="color:#475569;font-size:.875rem;margin-bottom:1rem;">
                Die Stellenanzeige wird als <strong>Entwurf</strong> gespeichert.
                Du kannst sie danach jederzeit bearbeiten und zur Genehmigung einreichen.
            </p>
            <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary" style="font-size:1rem;padding:.875rem 2rem;">
                    💾 Als Entwurf speichern
                </button>
                <a href="<?php echo $esc($baseUrl); ?>" class="btn btn-secondary">↩️ Abbrechen</a>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-start;margin-top:1rem;">
            <button type="button" class="btn btn-secondary"
                    onclick="switchTab('tab-benefits',document.querySelectorAll('.tab-btn')[3])">← Zurück</button>
        </div>
    </div><!-- /#tab-workflow -->

</form>

<style>
.tab-btn {
    padding: .5rem 1rem;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: 600;
    font-size: .875rem;
    color: #64748b;
    transition: color .15s, border-color .15s;
}
.tab-btn.active,
.tab-btn:hover {
    color: var(--admin-primary, #3b82f6);
    border-bottom-color: var(--admin-primary, #3b82f6);
}
.tab-content { display: none; }
.tab-content.active { display: block; }
</style>
<script>
function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(function(t) { t.classList.remove('active'); });
    document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
    document.getElementById(tabId).classList.add('active');
    if (btn) btn.classList.add('active');
}

function jpgAddCreateTask() {
    var item = document.createElement('div');
    item.className  = 'jpg-task-item';
    item.draggable  = true;
    item.style.cssText = 'display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;background:#f8fafc;border-radius:6px;padding:.35rem .5rem;';
    item.innerHTML  = '<span style="cursor:grab;color:#94a3b8;flex:0 0 auto;">⠿</span>'
        + '<input type="text" name="tasks[]" class="form-control" style="flex:1;" placeholder="Aufgabe beschreiben…">'
        + '<button type="button" onclick="this.closest(\'.jpg-task-item\').remove()" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;padding:.15rem .3rem;">✕</button>';
    document.getElementById('jpgCreateTaskList').appendChild(item);
    item.querySelector('input').focus();
    jpgInitCreateDrag();
}

function jpgAddCreateReq() {
    var row = document.createElement('div');
    row.style.cssText = 'display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;';
    row.innerHTML = '<input type="text" name="req_text[]" class="form-control" style="flex:1;" placeholder="Anforderung…">'
        + '<select name="req_type[]" class="form-control" style="width:auto;min-width:130px;">'
        + '<option value="must">Pflicht</option><option value="nice">Wünschenswert</option></select>'
        + '<button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;">✕</button>';
    document.getElementById('jpgCreateReqList').appendChild(row);
    row.querySelector('input').focus();
}

function updateCreateCount(fieldId, countId, max) {
    var len = document.getElementById(fieldId).value.length;
    var el  = document.getElementById(countId);
    if (el) el.textContent = '(' + len + '/' + max + ')';
}

function jpgInitCreateDrag() {
    var list = document.getElementById('jpgCreateTaskList');
    if (!list) return;
    var dragging = null;
    list.querySelectorAll('.jpg-task-item').forEach(function(item) {
        item.removeEventListener('dragstart', item._ds);
        item.removeEventListener('dragend',   item._de);
        item.removeEventListener('dragover',  item._do);
        item._ds = function() { dragging = item; item.style.opacity = '.5'; };
        item._de = function() { item.style.opacity = '1'; dragging = null; };
        item._do = function(e) {
            e.preventDefault();
            if (dragging && dragging !== item) {
                var mid = item.getBoundingClientRect().top + item.getBoundingClientRect().height / 2;
                if (e.clientY < mid) list.insertBefore(dragging, item);
                else list.insertBefore(dragging, item.nextSibling);
            }
        };
        item.addEventListener('dragstart', item._ds);
        item.addEventListener('dragend',   item._de);
        item.addEventListener('dragover',  item._do);
    });
}
document.addEventListener('DOMContentLoaded', jpgInitCreateDrag);
</script>
