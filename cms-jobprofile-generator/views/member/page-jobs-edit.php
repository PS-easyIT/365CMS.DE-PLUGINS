<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
/**
 * Member-View: Stellenanzeige bearbeiten (5-Tab-Wizard)
 *
 * @var object        $profile
 * @var array<object> $categories
 * @var array<string, array<object>> $allBenefits
 * @var array<object> $tasks
 * @var array<object> $requirements
 * @var array<int>    $benefitIds
 * @var string        $csrf
 * @var string        $notice
 * @var string        $error
 * @var string        $baseUrl        Basis-URL (z. B. /member/jobs oder /member/plugin/member-jobs)
 * @var array<object> $workflowSteps  Konfigurierte Workflow-Schritte
 * @var array<object> $workflowHistory Verlaufseinträge
 * @var string        $wfCsrf         CSRF-Token für Workflow-Aktionen
 */
$esc     = fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
$v       = fn(string $field, string $default = '') => htmlspecialchars((string)($profile->$field ?? $default), ENT_QUOTES);
$baseUrl = $baseUrl ?? '/member/jobs';

$wfStatus  = $profile->workflow_status ?? 'none';
$wfStep    = (int)($profile->workflow_step ?? 0);
$wfSteps   = $workflowSteps  ?? [];
$wfHistory = $workflowHistory ?? [];
$locked    = in_array($wfStatus, ['pending', 'approved'], true);

$wfLabels = [
    'none'     => ['label' => 'Kein Workflow',  'badge' => 'inactive', 'icon' => '📝'],
    'draft'    => ['label' => 'Entwurf',         'badge' => 'inactive', 'icon' => '📄'],
    'pending'  => ['label' => 'In Genehmigung', 'badge' => 'inactive', 'icon' => '⏳'],
    'approved' => ['label' => 'Genehmigt',       'badge' => 'active',   'icon' => '✅'],
    'rejected' => ['label' => 'Abgelehnt',       'badge' => 'danger',   'icon' => '❌'],
];
$currentWf = $wfLabels[$wfStatus] ?? $wfLabels['none'];

$actionLabels = [
    'submitted' => ['icon' => '📤', 'label' => 'Eingereicht'],
    'approved'  => ['icon' => '✅', 'label' => 'Genehmigt'],
    'rejected'  => ['icon' => '❌', 'label' => 'Abgelehnt'],
    'reset'     => ['icon' => '🔄', 'label' => 'Zurückgesetzt'],
];

$expLevels  = ['entry'=>'Berufseinsteiger','junior'=>'Junior (1–3 J.)','mid'=>'Mid-Level (3–5 J.)','senior'=>'Senior (5+ J.)','lead'=>'Lead / Principal'];
$empTypes   = ['fulltime'=>'Vollzeit','parttime'=>'Teilzeit','freelance'=>'Freiberuflich','internship'=>'Praktikum','mini'=>'Minijob'];
$remOptions = ['onsite'=>'Vor Ort','hybrid'=>'Hybrid','remote'=>'Remote'];
$selExp     = $profile->experience_level ?? 'mid';
$selType    = $profile->employment_type  ?? 'fulltime';
$selRem     = $profile->remote_option    ?? 'onsite';
?>
<style>
.jpg-tab-nav{display:flex;flex-wrap:wrap;gap:.3rem;margin-bottom:1.5rem;border-bottom:2px solid #e2e8f0;padding-bottom:0;}
.jpg-tab-btn{background:none;border:none;padding:.65rem 1.1rem;font-size:.9rem;font-weight:600;color:#64748b;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;border-radius:6px 6px 0 0;transition:color .15s,border-color .15s;}
.jpg-tab-btn:hover{color:#3b82f6;}
.jpg-tab-btn.active{color:#3b82f6;border-bottom-color:#3b82f6;background:#eff6ff;}
.jpg-tab-pane{display:none;}.jpg-tab-pane.active{display:block;}
.jpg-benefit-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.5rem;}
.jpg-benefit-label{display:flex;align-items:center;gap:.6rem;padding:.5rem .75rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;font-size:.875rem;transition:background .1s;}
.jpg-benefit-label:hover{background:#eff6ff;border-color:#bfdbfe;}
.jpg-benefit-label input[type=checkbox]:checked+span{font-weight:600;color:#1e40af;}
.jpg-char-hint{font-size:.79rem;color:#94a3b8;float:right;}
</style>

<!-- Page-Header -->
<div style="margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.75rem;">
    <div>
        <h2 style="margin:0 0 .25rem;font-size:1.375rem;font-weight:700;color:#1e293b;">✏️ <?php echo $v('title'); ?></h2>
        <p style="margin:0;color:#64748b;font-size:.875rem;">
            Stellenanzeige bearbeiten
            <?php if (($profile->status ?? '') === 'published'): ?>
            &nbsp;·&nbsp;<a href="/jobs/<?php echo $v('slug'); ?>" target="_blank" style="color:#3b82f6;">👁️ Ansehen</a>
            <?php endif; ?>
            &nbsp;·&nbsp;
            <span class="status-badge <?php echo $esc($currentWf['badge']); ?>"
                  style="font-size:.75rem;">
                <?php echo $currentWf['icon'].' '.$esc($currentWf['label']); ?>
            </span>
        </p>
    </div>
    <a href="<?php echo $esc($baseUrl); ?>" class="btn btn-secondary btn-sm">↩️ Zur Übersicht</a>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-success" style="margin-bottom:1rem;">✅ <?php echo $esc($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error" style="margin-bottom:1rem;">❌ <?php echo $esc($error); ?></div>
<?php endif; ?>

<?php if ($locked): ?>
<div class="alert" style="background:#fef9c3;color:#854d0e;border-left:4px solid #eab308;margin-bottom:1rem;">
    ⏸️ Diese Stellenanzeige ist gesperrt, da sie sich aktuell im Genehmigungsprozess befindet oder bereits genehmigt wurde. Wechsle zum Tab <strong>Workflow</strong> für weitere Optionen.
</div>
<?php endif; ?>

<!-- Tab-Navigation -->
<nav class="jpg-tab-nav">
    <button type="button" class="jpg-tab-btn active" onclick="jpgEditTab('basis',this)">📋 Basisdaten</button>
    <button type="button" class="jpg-tab-btn" onclick="jpgEditTab('tasks',this)">📝 Aufgaben</button>
    <button type="button" class="jpg-tab-btn" onclick="jpgEditTab('req',this)">🎯 Anforderungen</button>
    <button type="button" class="jpg-tab-btn" onclick="jpgEditTab('benefits',this)">🎁 Benefits</button>
    <button type="button" class="jpg-tab-btn" onclick="jpgEditTab('workflow',this)">🔄 Workflow</button>
</nav>

<form method="post" class="admin-form" id="jpgEditForm">
    <input type="hidden" name="_jpg_csrf" value="<?php echo $esc($csrf); ?>">
    <input type="hidden" name="action"    value="save">

    <!-- ─── TAB 1: BASISDATEN ─── -->
    <div id="jpg-tab-basis" class="jpg-tab-pane active">

        <div class="admin-card">
            <h3>📋 Grunddaten</h3>

            <div class="form-group">
                <label class="form-label" for="jpgEditTitle">
                    Stellentitel <span style="color:#ef4444;">*</span>
                </label>
                <input type="text" id="jpgEditTitle" name="title" class="form-control"
                       value="<?php echo $v('title'); ?>" required maxlength="255"
                       <?php echo $locked ? 'readonly' : ''; ?>>
            </div>

            <!-- Phase 14.2: Inline-Slug-Editor -->
            <div class="form-group">
                <label class="form-label" for="jpgEditSlug">URL-Slug</label>
                <div style="display:flex;gap:.5rem;align-items:center;">
                    <span style="color:#64748b;font-size:.875rem;white-space:nowrap;">/jobs/</span>
                    <input type="text" id="jpgEditSlug" name="slug" class="form-control"
                           value="<?php echo $v('slug'); ?>"
                           pattern="[a-z0-9\-]+" maxlength="100"
                           placeholder="stellentitel (automatisch)"
                           <?php echo $locked ? 'readonly' : ''; ?>>
                </div>
                <small class="form-text">Nur Kleinbuchstaben, Zahlen und Bindestriche. Leer lassen = automatisch aus Titel. Ändert die öffentliche URL!</small>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label">Abteilung / Bereich</label>
                    <select name="job_category_id" class="form-control"
                            <?php echo $locked ? 'disabled' : ''; ?>>
                        <option value="0">– Keine –</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int)$cat->id; ?>"
                            <?php echo ((int)($profile->job_category_id ?? 0) === (int)$cat->id) ? 'selected' : ''; ?>>
                            <?php echo $esc($cat->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Standort</label>
                    <input type="text" name="location" class="form-control"
                           value="<?php echo $v('location'); ?>"
                           <?php echo $locked ? 'readonly' : ''; ?>>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label">Anstellungsart</label>
                    <select name="employment_type" class="form-control"
                            <?php echo $locked ? 'disabled' : ''; ?>>
                        <?php foreach ($empTypes as $k => $l): ?>
                        <option value="<?php echo $esc($k); ?>" <?php echo $selType === $k ? 'selected' : ''; ?>>
                            <?php echo $esc($l); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Remote-Option</label>
                    <select name="remote_option" class="form-control"
                            <?php echo $locked ? 'disabled' : ''; ?>>
                        <?php foreach ($remOptions as $k => $l): ?>
                        <option value="<?php echo $esc($k); ?>" <?php echo $selRem === $k ? 'selected' : ''; ?>>
                            <?php echo $esc($l); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Erfahrungslevel</label>
                    <select name="experience_level" class="form-control"
                            <?php echo $locked ? 'disabled' : ''; ?>>
                        <?php foreach ($expLevels as $k => $l): ?>
                        <option value="<?php echo $esc($k); ?>" <?php echo $selExp === $k ? 'selected' : ''; ?>>
                            <?php echo $esc($l); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label">Gehalt von (€/Jahr)</label>
                    <input type="number" name="salary_min" class="form-control"
                           value="<?php echo (int)($profile->salary_min ?? 0) ?: ''; ?>"
                           min="0" step="1000" placeholder="z. B. 40000"
                           <?php echo $locked ? 'readonly' : ''; ?>>
                </div>
                <div class="form-group">
                    <label class="form-label">Gehalt bis (€/Jahr)</label>
                    <input type="number" name="salary_max" class="form-control"
                           value="<?php echo (int)($profile->salary_max ?? 0) ?: ''; ?>"
                           min="0" step="1000" placeholder="z. B. 60000"
                           <?php echo $locked ? 'readonly' : ''; ?>>
                </div>
            </div>
        </div>

        <!-- Sichtbarkeit auf der öffentlichen Stellenliste -->
        <?php if (!$locked): ?>
        <div class="admin-card">
            <h3>🌐 Sichtbarkeit</h3>
            <div class="form-group">
                <label class="checkbox-label" style="display:flex;align-items:center;gap:.75rem;cursor:pointer;">
                    <input type="checkbox" name="show_in_listing" value="1"
                           <?php echo ($profile->show_in_listing ?? 1) ? 'checked' : ''; ?>>
                    <div>
                        <div style="font-weight:500;">In öffentlicher Stellenliste anzeigen (<code>/jobs</code>)</div>
                        <div style="font-size:.82rem;color:#64748b;">Die Stelle erscheint auf der öffentlichen Karriere-Seite und ist für Bewerber sichtbar.</div>
                    </div>
                </label>
            </div>
        </div>
        <?php endif; ?>

        <div class="admin-card">
            <h3>📄 Beschreibungen</h3>

            <div class="form-group">
                <label class="form-label">
                    Kurzbeschreibung
                    <span class="jpg-char-hint"><span id="jpgEditSumCount"><?php echo mb_strlen($profile->summary ?? ''); ?></span> / 300</span>
                </label>
                <textarea name="summary" id="jpgEditSummary" class="form-control"
                          style="min-height:90px;resize:vertical;"
                          maxlength="300"
                          oninput="document.getElementById('jpgEditSumCount').textContent=this.value.length"
                          <?php echo $locked ? 'readonly' : ''; ?>><?php echo $v('summary'); ?></textarea>
                <small class="form-text">Erscheint in Übersichtslisten und Suchergebnissen.</small>
            </div>

            <div class="form-group">
                <label class="form-label">Ausführliche Beschreibung</label>
                <textarea name="description" class="form-control"
                          style="min-height:200px;resize:vertical;"
                          <?php echo $locked ? 'readonly' : ''; ?>><?php echo $v('description'); ?></textarea>
                <small class="form-text">Vollständiger Fließtext der Stellenanzeige.</small>
            </div>
        </div>

        <?php if (!$locked): ?>
        <div class="admin-card" style="background:#f8fafc;">
            <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">💾 Speichern</button>
                <button type="button" class="btn btn-secondary" onclick="jpgEditTab('tasks',document.querySelectorAll('.jpg-tab-btn')[1])">Weiter → Aufgaben</button>
            </div>
        </div>
        <?php endif; ?>
    </div><!-- /tab-basis -->

    <!-- ─── TAB 2: AUFGABEN ─── -->
    <div id="jpg-tab-tasks" class="jpg-tab-pane">
        <div class="admin-card">
            <h3>📝 Aufgaben &amp; Verantwortlichkeiten</h3>
            <small class="form-text" style="display:block;margin-bottom:.75rem;">
                Mindestens 3 Aufgaben empfohlen. Reihenfolge per Drag&amp;Drop änderbar.
            </small>
            <div id="jpgMemberTaskList">
                <?php foreach ($tasks as $task): ?>
                <div class="jpg-task-item" draggable="true"
                     style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;background:#f8fafc;border-radius:6px;padding:.35rem .5rem;">
                    <span style="cursor:grab;color:#94a3b8;user-select:none;">⠿</span>
                    <input type="text" name="tasks[]" class="form-control"
                           value="<?php echo $esc($task->task_text); ?>"
                           style="flex:1;"
                           <?php echo $locked ? 'readonly' : ''; ?>>
                    <?php if (!$locked): ?>
                    <button type="button"
                            onclick="this.closest('.jpg-task-item').remove()"
                            style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;padding:.15rem .3rem;">✕</button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php if (empty($tasks) && !$locked):
                    for ($i = 0; $i < 3; $i++): ?>
                <div class="jpg-task-item" draggable="true"
                     style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;background:#f8fafc;border-radius:6px;padding:.35rem .5rem;">
                    <span style="cursor:grab;color:#94a3b8;user-select:none;">⠿</span>
                    <input type="text" name="tasks[]" class="form-control" style="flex:1;" placeholder="Aufgabe beschreiben…">
                    <button type="button" onclick="this.closest('.jpg-task-item').remove()"
                            style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;padding:.15rem .3rem;">✕</button>
                </div>
                    <?php endfor;
                endif; ?>
            </div>
            <?php if (!$locked): ?>
            <button type="button" class="btn btn-secondary btn-sm" style="margin-top:.5rem;"
                    onclick="jpgAddTask()">➕ Aufgabe hinzufügen</button>
            <?php endif; ?>
        </div>
        <?php if (!$locked): ?>
        <div class="admin-card" style="background:#f8fafc;">
            <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">💾 Speichern</button>
                <button type="button" class="btn btn-secondary" onclick="jpgEditTab('req',document.querySelectorAll('.jpg-tab-btn')[2])">Weiter → Anforderungen</button>
            </div>
        </div>
        <?php endif; ?>
    </div><!-- /tab-tasks -->

    <!-- ─── TAB 3: ANFORDERUNGEN ─── -->
    <div id="jpg-tab-req" class="jpg-tab-pane">
        <div class="admin-card">
            <h3>🎯 Anforderungen</h3>
            <small class="form-text" style="display:block;margin-bottom:.75rem;">
                <strong>Pflicht</strong> = unbedingt erforderlich · <strong>Wünschenswert</strong> = nice to have
            </small>
            <div id="jpgMemberReqList">
                <?php foreach ($requirements as $req): ?>
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;">
                    <input type="text" name="req_text[]" class="form-control"
                           value="<?php echo $esc($req->req_text); ?>"
                           style="flex:1;"
                           <?php echo $locked ? 'readonly' : ''; ?>>
                    <select name="req_type[]" class="form-control" style="width:auto;min-width:130px;"
                            <?php echo $locked ? 'disabled' : ''; ?>>
                        <option value="must" <?php echo ($req->req_type ?? '') === 'must' ? 'selected' : ''; ?>>Pflicht</option>
                        <option value="nice" <?php echo ($req->req_type ?? '') === 'nice' ? 'selected' : ''; ?>>Wünschenswert</option>
                        <option value="optional" <?php echo ($req->req_type ?? '') === 'optional' ? 'selected' : ''; ?>>Optional</option>
                    </select>
                    <?php if (!$locked): ?>
                    <button type="button" onclick="this.parentElement.remove()"
                            style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;">✕</button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php if (empty($requirements) && !$locked): ?>
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;">
                    <input type="text" name="req_text[]" class="form-control" style="flex:1;" placeholder="Anforderung beschreiben…">
                    <select name="req_type[]" class="form-control" style="width:auto;min-width:130px;">
                        <option value="must">Pflicht</option>
                        <option value="nice">Wünschenswert</option>
                        <option value="optional">Optional</option>
                    </select>
                    <button type="button" onclick="this.parentElement.remove()"
                            style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;">✕</button>
                </div>
                <?php endif; ?>
            </div>
            <?php if (!$locked): ?>
            <button type="button" class="btn btn-secondary btn-sm" style="margin-top:.5rem;"
                    onclick="jpgAddReq()">➕ Anforderung hinzufügen</button>
            <?php endif; ?>
        </div>
        <?php if (!$locked): ?>
        <div class="admin-card" style="background:#f8fafc;">
            <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">💾 Speichern</button>
                <button type="button" class="btn btn-secondary" onclick="jpgEditTab('benefits',document.querySelectorAll('.jpg-tab-btn')[3])">Weiter → Benefits</button>
            </div>
        </div>
        <?php endif; ?>
    </div><!-- /tab-req -->

    <!-- ─── TAB 4: BENEFITS ─── -->
    <div id="jpg-tab-benefits" class="jpg-tab-pane">
        <div class="admin-card">
            <h3>🎁 Benefits &amp; Vorteile</h3>
            <?php if (empty($allBenefits)): ?>
            <div class="empty-state" style="padding:2rem 0;text-align:center;">
                <p style="font-size:2rem;margin:0;">📭</p>
                <p style="color:#64748b;font-size:.875rem;">Noch keine Benefits angelegt. Ein Admin kann diese in den Einstellungen erstellen.</p>
            </div>
            <?php else: ?>
            <?php foreach ($allBenefits as $group => $items): ?>
            <div style="margin-bottom:1.25rem;">
                <div style="font-size:.8rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.5rem;">
                    <?php echo $esc($group); ?>
                </div>
                <div class="jpg-benefit-grid">
                    <?php foreach ($items as $benefit): ?>
                    <label class="jpg-benefit-label">
                        <input type="checkbox" name="benefit_ids[]"
                               value="<?php echo (int)$benefit->id; ?>"
                               <?php echo in_array((int)$benefit->id, $benefitIds ?? [], true) ? 'checked' : ''; ?>
                               <?php echo $locked ? 'disabled' : ''; ?>>
                        <span><?php echo $esc($benefit->title); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if (!$locked): ?>
        <div class="admin-card" style="background:#f8fafc;">
            <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">💾 Speichern</button>
                <button type="button" class="btn btn-secondary" onclick="jpgEditTab('workflow',document.querySelectorAll('.jpg-tab-btn')[4])">Weiter → Workflow</button>
            </div>
        </div>
        <?php endif; ?>
    </div><!-- /tab-benefits -->

</form><!-- /admin-form (Hauptformular endet hier) -->

<!-- ─── TAB 5: WORKFLOW (eigenes Formular für Submit-Aktion) ─── -->
<div id="jpg-tab-workflow" class="jpg-tab-pane">
    <?php if (class_exists('CMS_JPG_Workflow')): ?>

    <div class="admin-card">
        <h3>🔄 Genehmigungsprozess</h3>

        <!-- Status-Zeile -->
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap;">
            <span style="font-size:1.4rem;"><?php echo $currentWf['icon']; ?></span>
            <div>
                <div style="font-size:.8rem;color:#64748b;text-transform:uppercase;letter-spacing:.03em;font-weight:600;">Aktueller Status</div>
                <span class="status-badge <?php echo $esc($currentWf['badge']); ?>" style="font-size:.9rem;">
                    <?php echo $esc($currentWf['label']); ?>
                </span>
            </div>
            <?php if ($wfStatus === 'pending' && count($wfSteps) > 0): ?>
            <span style="color:#94a3b8;font-size:.8rem;">
                Schritt <?php echo $wfStep; ?> / <?php echo count($wfSteps); ?>
            </span>
            <?php endif; ?>
        </div>

        <!-- Schritt-Visualisierung -->
        <?php if (count($wfSteps) > 0): ?>
        <div style="margin-bottom:1.5rem;">
            <div style="font-size:.8rem;color:#64748b;font-weight:600;margin-bottom:.75rem;text-transform:uppercase;letter-spacing:.03em;">Genehmigungsschritte</div>
            <div style="display:flex;align-items:flex-start;gap:0;overflow-x:auto;padding-bottom:.5rem;">

                <!-- Startpunkt: Entwurf -->
                <div style="display:flex;flex-direction:column;align-items:center;gap:.3rem;min-width:80px;">
                    <div style="width:2rem;height:2rem;border-radius:50%;
                                background:<?php echo !in_array($wfStatus, ['pending','approved','rejected'], true) ? '#3b82f6' : '#10b981'; ?>;
                                display:flex;align-items:center;justify-content:center;
                                font-size:.8rem;font-weight:700;color:#fff;">
                        <?php echo !in_array($wfStatus, ['pending','approved','rejected'], true) ? '●' : '✓'; ?>
                    </div>
                    <div style="font-size:.72rem;text-align:center;color:#64748b;">Entwurf</div>
                </div>
                <div style="align-self:flex-start;margin-top:.85rem;color:#e2e8f0;font-size:.9rem;padding:0 .2rem;">→</div>

                <?php foreach ($wfSteps as $i => $step):
                    $sNr    = (int)$step->sort_order;
                    $sDone  = ($wfStatus === 'approved') || ($wfStatus === 'pending' && $wfStep > $sNr);
                    $sCurr  = $wfStatus === 'pending' && $wfStep === $sNr;
                    $sColor = $sDone ? '#10b981' : ($sCurr ? '#3b82f6' : '#e2e8f0');
                    $sText  = $sDone ? '#065f46' : ($sCurr ? '#1e40af' : '#94a3b8');
                ?>
                <div style="display:flex;flex-direction:column;align-items:center;gap:.3rem;min-width:90px;flex:1;">
                    <div style="width:2rem;height:2rem;border-radius:50%;background:<?php echo $sColor; ?>;
                                display:flex;align-items:center;justify-content:center;
                                font-size:.75rem;font-weight:700;color:<?php echo ($sColor === '#e2e8f0') ? '#94a3b8' : '#fff'; ?>;">
                        <?php echo $sDone ? '✓' : $sNr; ?>
                    </div>
                    <div style="font-size:.72rem;text-align:center;color:<?php echo $sText; ?>;max-width:80px;line-height:1.3;">
                        <?php echo $esc($step->step_name); ?>
                    </div>
                </div>
                <?php if ($i < count($wfSteps) - 1): ?>
                <div style="align-self:flex-start;margin-top:.85rem;color:#e2e8f0;font-size:.9rem;padding:0 .15rem;">→</div>
                <?php endif; ?>
                <?php endforeach; ?>

                <!-- Endpunkt: Veröffentlicht -->
                <div style="align-self:flex-start;margin-top:.85rem;color:#e2e8f0;font-size:.9rem;padding:0 .2rem;">→</div>
                <div style="display:flex;flex-direction:column;align-items:center;gap:.3rem;min-width:80px;">
                    <div style="width:2rem;height:2rem;border-radius:50%;
                                background:<?php echo $wfStatus === 'approved' ? '#10b981' : '#e2e8f0'; ?>;
                                display:flex;align-items:center;justify-content:center;
                                font-size:.8rem;font-weight:700;color:<?php echo $wfStatus === 'approved' ? '#fff' : '#94a3b8'; ?>;">
                        <?php echo $wfStatus === 'approved' ? '✓' : '●'; ?>
                    </div>
                    <div style="font-size:.72rem;text-align:center;color:<?php echo $wfStatus === 'approved' ? '#065f46' : '#94a3b8'; ?>;">Veröffentlicht</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Aktions-Bereich je Status -->
        <?php if (in_array($wfStatus, ['none', 'draft', ''], true) && count($wfSteps) > 0): ?>
        <form method="post"
              action="<?php echo $esc($baseUrl . '/workflow/submit/' . (int)$profile->id); ?>">
            <input type="hidden" name="_jpg_csrf" value="<?php echo $esc($wfCsrf ?? ''); ?>">
            <button type="submit" class="btn btn-primary"
                    onclick="return confirm('Stellenanzeige jetzt zur Genehmigung einreichen?')">
                📤 Zur Genehmigung einreichen
            </button>
            <small class="form-text" style="display:block;margin-top:.5rem;color:#64748b;">
                Nach dem Einreichen kannst du den Inhalt nicht mehr ändern, bis eine Entscheidung getroffen wurde.
            </small>
        </form>

        <?php elseif ($wfStatus === 'rejected'): ?>
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:1rem;margin-bottom:1rem;">
            <p style="margin:0;color:#991b1b;font-size:.875rem;">
                ❌ Deine Stellenanzeige wurde abgelehnt. Bearbeite den Inhalt (Tabs Basisdaten, Aufgaben etc.) und reiche sie danach erneut ein.
            </p>
        </div>
        <form method="post"
              action="<?php echo $esc($baseUrl . '/workflow/submit/' . (int)$profile->id); ?>">
            <input type="hidden" name="_jpg_csrf" value="<?php echo $esc($wfCsrf ?? ''); ?>">
            <button type="submit" class="btn btn-secondary">🔄 Erneut einreichen</button>
        </form>

        <?php elseif ($wfStatus === 'approved'): ?>
        <div style="background:#d1fae5;border:1px solid #6ee7b7;border-radius:8px;padding:1rem;">
            <p style="margin:0;color:#065f46;font-size:.875rem;">
                ✅ Deine Stellenanzeige wurde vollständig genehmigt und kann jetzt veröffentlicht werden.
            </p>
        </div>

        <?php elseif ($wfStatus === 'pending'): ?>
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:1rem;">
            <p style="margin:0;color:#1e40af;font-size:.875rem;">
                ⏳ Deine Stellenanzeige wird aktuell geprüft. Du wirst benachrichtigt, sobald eine Entscheidung vorliegt.
            </p>
        </div>

        <?php elseif (count($wfSteps) === 0): ?>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;">
            <p style="margin:0;color:#64748b;font-size:.875rem;">
                ℹ️ Noch keine Genehmigungsschritte konfiguriert. Ein Administrator muss erst den Workflow einrichten.
            </p>
        </div>
        <?php endif; ?>
    </div><!-- /.admin-card Workflow -->

    <!-- Verlauf -->
    <?php if (!empty($wfHistory)): ?>
    <div class="admin-card">
        <h3>📜 Genehmigungsverlauf</h3>
        <?php foreach ($wfHistory as $entry):
            $act       = $actionLabels[$entry->action] ?? ['icon' => '•', 'label' => htmlspecialchars($entry->action, ENT_QUOTES)];
            $actorName = htmlspecialchars($entry->actor_name ?? 'Unbekannt', ENT_QUOTES);
            $date      = date('d.m.Y H:i', strtotime($entry->created_at));
        ?>
        <div style="display:flex;gap:.75rem;margin-bottom:1rem;align-items:flex-start;">
            <div style="flex:0 0 auto;width:1.75rem;height:1.75rem;border-radius:50%;
                        background:#f1f5f9;display:flex;align-items:center;
                        justify-content:center;font-size:.9rem;">
                <?php echo $act['icon']; ?>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:.875rem;font-weight:600;color:#1e293b;">
                    <?php echo $act['label']; ?>
                    <span style="font-weight:400;color:#64748b;">– von <?php echo $actorName; ?></span>
                </div>
                <?php if (!empty($entry->note)): ?>
                <div style="font-size:.8rem;color:#475569;margin-top:.15rem;background:#f8fafc;padding:.4rem .6rem;border-radius:4px;">
                    <?php echo htmlspecialchars($entry->note, ENT_QUOTES); ?>
                </div>
                <?php endif; ?>
                <div style="font-size:.75rem;color:#94a3b8;margin-top:.2rem;">
                    <?php echo htmlspecialchars($date, ENT_QUOTES); ?>
                    <?php if (!empty($entry->step_order)): ?>
                    &nbsp;· Schritt <?php echo (int)$entry->step_order; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php else: // !class_exists CMS_JPG_Workflow ?>
    <div class="admin-card">
        <p style="color:#64748b;font-size:.875rem;">
            ℹ️ Das Workflow-Modul ist nicht verfügbar.
        </p>
    </div>
    <?php endif; ?>
</div><!-- /tab-workflow -->

<script>
(function () {
    // Tab-Switching
    window.jpgEditTab = function (id, btn) {
        document.querySelectorAll('.jpg-tab-pane').forEach(function (p) { p.classList.remove('active'); });
        document.querySelectorAll('.jpg-tab-btn').forEach(function (b) { b.classList.remove('active'); });
        var pane = document.getElementById('jpg-tab-' + id);
        if (pane) pane.classList.add('active');
        if (btn)  btn.classList.add('active');
    };

    // Aufgabe hinzufügen
    window.jpgAddTask = function () {
        var item = document.createElement('div');
        item.className  = 'jpg-task-item';
        item.draggable  = true;
        item.style.cssText = 'display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;background:#f8fafc;border-radius:6px;padding:.35rem .5rem;';
        item.innerHTML  =
            '<span style="cursor:grab;color:#94a3b8;user-select:none;">⠿</span>' +
            '<input type="text" name="tasks[]" class="form-control" style="flex:1;" placeholder="Aufgabe beschreiben…">' +
            '<button type="button" onclick="this.closest(\'.jpg-task-item\').remove()" ' +
            'style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;padding:.15rem .3rem;">✕</button>';
        document.getElementById('jpgMemberTaskList').appendChild(item);
        item.querySelector('input').focus();
        jpgInitEditDrag();
    };

    // Anforderung hinzufügen
    window.jpgAddReq = function () {
        var row = document.createElement('div');
        row.style.cssText = 'display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;';
        row.innerHTML =
            '<input type="text" name="req_text[]" class="form-control" style="flex:1;" placeholder="Anforderung…">' +
            '<select name="req_type[]" class="form-control" style="width:auto;min-width:130px;">' +
            '<option value="must">Pflicht</option><option value="nice">Wünschenswert</option><option value="optional">Optional</option></select>' +
            '<button type="button" onclick="this.parentElement.remove()" ' +
            'style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;">✕</button>';
        document.getElementById('jpgMemberReqList').appendChild(row);
        row.querySelector('input').focus();
    };

    // Drag & Drop für Aufgaben
    var jpgDragSrc = null;
    function jpgInitEditDrag() {
        var list  = document.getElementById('jpgMemberTaskList');
        if (!list) return;
        var items = list.querySelectorAll('.jpg-task-item');
        items.forEach(function (item) {
            item.ondragstart = function (e) {
                jpgDragSrc = item;
                e.dataTransfer.effectAllowed = 'move';
                setTimeout(function () { item.style.opacity = '0.4'; }, 0);
            };
            item.ondragend   = function () { item.style.opacity = '1'; };
            item.ondragover  = function (e) { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; return false; };
            item.ondrop      = function (e) {
                e.stopPropagation();
                if (jpgDragSrc !== item) {
                    var aHtml = jpgDragSrc.innerHTML, bHtml = item.innerHTML;
                    jpgDragSrc.innerHTML = bHtml;
                    item.innerHTML       = aHtml;
                }
                return false;
            };
        });
    }
    jpgInitEditDrag();
})();
</script>
