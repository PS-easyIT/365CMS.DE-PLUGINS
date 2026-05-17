<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * View: Profil-Generator – 6 Tabs
 *
 * @var string               $tab
 * @var array<string,string> $tabs
 * @var int                  $id
 * @var object|null          $profile
 * @var array<object>        $tasks
 * @var array<object>        $requirements
 * @var array<object>        $profileSkills
 * @var array<int>           $benefitIds
 * @var array<string,array>  $requirementItems  Gruppierte Anforderungs-Liste
 * @var array<object>        $categories
 * @var array<string,array>  $allSkills          Gruppiert
 * @var array<string,array>  $allBenefits        Gruppiert
 * @var array<object>        $textModules
 * @var array<object>        $companies
 * @var string               $companiesJson      JSON-Map id→Adressdaten
 * @var array<int>           $companyDefaultBenefitIds
 * @var string               $notice
 * @var string               $error
 */

$baseUrl = $id > 0
    ? '/admin/plugins/jpg-dashboard/jpg-generator?id=' . $id
    : '/admin/plugins/jpg-dashboard/jpg-generator';

$nonce   = CMS_JPG_Admin_Pages::nonce('jpg_generator_save');

// Hilfsfunktionen
$v = fn(string $field, string $default = '') => htmlspecialchars((string) ($profile->$field ?? $default), ENT_QUOTES);
?>

<div class="admin-page-header">
    <div>
        <h2><?php echo $id > 0 ? '✏️ Profil bearbeiten' : '➕ Neues Profil erstellen'; ?></h2>
        <p><?php echo $id > 0 ? 'ID #' . $id . ' – ' . htmlspecialchars($profile->title ?? '') : 'Schritt für Schritt zum fertigen Job-Profil'; ?></p>
    </div>
    <div class="header-actions">
        <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-dashboard'); ?>"
           class="btn btn-secondary">↩️ Dashboard</a>
        <?php if ($id > 0 && ($profile->status ?? '') !== 'published'): ?>
        <form method="post" style="display:inline;">
            <input type="hidden" name="<?php echo esc_attr(CMS_JPG_Admin_Pages::NONCE_FIELD_PUBLIC); ?>" value="<?php echo esc_attr($nonce); ?>">
            <input type="hidden" name="_jpg_action" value="publish">
            <button type="submit" class="btn btn-primary">🚀 Veröffentlichen</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo htmlspecialchars($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($tab !== 'basic' && $id === 0): ?>
<div class="alert" style="background:#dbeafe;color:#1e40af;border-left:4px solid #3b82f6;">
    ℹ️ Bitte zuerst die <a href="?tab=basic">Basisdaten</a> speichern.
</div>
<?php endif; ?>

<!-- Tab-Navigation -->
<div class="jpg-tabs">
    <?php foreach ($tabs as $key => $label):
        $disabled = ($key !== 'basic' && $id === 0) ? ' jpg-tab--disabled' : '';
        $href = ($key !== 'basic' && $id === 0)
            ? '#'
            : esc_url('?' . ($id > 0 ? 'id=' . $id . '&' : '') . 'tab=' . $key);
    ?>
    <a href="<?php echo $href; ?>" class="jpg-tab<?php echo $tab === $key ? ' active' : ''; ?><?php echo $disabled; ?>">
        <?php
        $icons = ['basic'=>'¹1️⃣','tasks'=>'¹2️⃣','requirements'=>'¹3️⃣','benefits'=>'¹4️⃣','skills'=>'¹5️⃣','review'=>'¹6️⃣'];
        echo ($icons[$key] ?? '') . ' ' . esc_html($label);
        ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;">

<!-- ═══════════════════════════════════════════════ TAB 1: BASISDATEN ══ -->
<?php if ($tab === 'basic'): ?>
<form method="post" class="admin-form">
    <input type="hidden" name="_jpg_nonce"  value="<?php echo esc_attr($nonce); ?>">
    <input type="hidden" name="_jpg_action" value="save_basic">

    <h3>📋 Grundinformationen</h3>

    <div class="form-group">
        <label class="form-label" for="jpg-title">
            Stellentitel <span style="color:#ef4444;">*</span>
        </label>
        <input type="text" id="jpg-title" name="title" class="form-control"
               value="<?php echo $v('title'); ?>" required maxlength="255"
               placeholder="z.B. Senior PHP-Entwickler (m/w/d)">
        <small class="form-text">Zwischen 10 und 150 Zeichen empfohlen.</small>
        <div class="jpg-char-counter" data-target="jpg-title" data-min="10" data-max="150"></div>
    </div>

    <!-- Phase 14.2: Inline-Slug-Editor -->
    <div class="form-group">
        <label class="form-label" for="jpg-slug">URL-Slug</label>
        <div style="display:flex;gap:.5rem;align-items:center;">
            <span style="color:#64748b;font-size:.875rem;white-space:nowrap;">/jobs/</span>
            <input type="text" id="jpg-slug" name="slug" class="form-control"
                   value="<?php echo $v('slug'); ?>"
                   pattern="[a-z0-9\-]+" maxlength="100"
                   placeholder="automatisch aus Titel generiert">
        </div>
        <small class="form-text">Optional. Leer lassen = automatisch generiert.</small>
    </div>

    <?php /* Phase 6.1 – cms-companies Firmen-Dropdown */ ?>
    <?php if (!empty($companies)): ?>
    <div class="form-group">
        <label class="form-label" for="jpg-company">
            Unternehmen
            <small class="form-text" style="display:inline;margin-left:.4rem;">via cms-companies</small>
        </label>
        <select id="jpg-company" name="company_id" class="form-control"
                onchange="jpgFillCompanyData(this.value)">
            <option value="">— Kein Unternehmen zugeordnet —</option>
            <?php foreach ($companies as $comp): ?>
            <option value="<?php echo (int)$comp->id; ?>"
                    <?php echo (int)($profile->company_id ?? 0) === (int)$comp->id ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($comp->name); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <small class="form-text">Verknüpft diesen Job mit einem Firmenprofil. Adressdaten werden automatisch übernommen.</small>
        <div id="jpg-company-info" style="display:none;margin-top:.4rem;padding:.45rem .75rem;background:#eff6ff;border-left:3px solid #3b82f6;border-radius:0 4px 4px 0;font-size:.8rem;color:#1e40af;"></div>
    </div>
    <script>
    var jpgCompanyData = <?php echo $companiesJson ?? '{}'; ?>;
    function jpgFillCompanyData(id) {
        var d = jpgCompanyData[id];
        var info = document.getElementById('jpg-company-info');
        if (!d || !id) {
            if (info) info.style.display = 'none';
            return;
        }
        // Standort-Feld auto-befüllen (nur wenn leer oder Platzhalter)
        var locField = document.getElementById('jpg-location');
        if (locField && locField.value === '') {
            var loc = [d.zip, d.city, d.country].filter(Boolean).join(', ');
            if (loc) locField.value = loc;
        }
        // Info-Box anzeigen
        if (info) {
            var parts = [];
            if (d.city)    parts.push('📍 ' + [d.zip, d.city].filter(Boolean).join(' '));
            if (d.country) parts.push('🌍 ' + d.country);
            if (d.phone)   parts.push('📞 ' + d.phone);
            if (d.website) parts.push('🌐 ' + d.website);
            info.replaceChildren();
            parts.forEach(function (part, index) {
                if (index > 0) {
                    info.appendChild(document.createTextNode(' | '));
                }
                var span = document.createElement('span');
                span.textContent = part;
                info.appendChild(span);
            });
            info.style.display = parts.length ? 'block' : 'none';
        }
    }
    // Beim Laden vorhandene Firma anzeigen
    document.addEventListener('DOMContentLoaded', function() {
        var sel = document.getElementById('jpg-company');
        if (sel && sel.value) jpgFillCompanyData(sel.value);
    });
    </script>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div class="form-group">
            <label class="form-label" for="jpg-category">Job-Kategorie</label>
            <select id="jpg-category" name="job_category_id" class="form-control">
                <option value="">— Kategorie wählen —</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo (int)$cat->id; ?>"
                        <?php echo (int)($profile->job_category_id ?? 0) === (int)$cat->id ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat->name); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="jpg-status">Status</label>
            <select id="jpg-status" name="status" class="form-control">
                <?php foreach (['draft'=>'📝 Entwurf','published'=>'✅ Veröffentlicht','archived'=>'📦 Archiviert'] as $val => $lbl): ?>
                <option value="<?php echo $val; ?>" <?php echo ($profile->status ?? 'draft') === $val ? 'selected' : ''; ?>>
                    <?php echo $lbl; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Phase 9: Privates Profil -->
    <div class="form-group">
        <label class="checkbox-label" style="display:flex;align-items:center;gap:.75rem;cursor:pointer;">
            <input type="checkbox" name="is_private" value="1"
                   <?php echo ($profile->is_private ?? 0) ? 'checked' : ''; ?>>
            <div>
                <div style="font-weight:500;">🔒 Privates Profil (Admin sieht es nur mit »Private anzeigen«)</div>
                <div style="font-size:.82rem;color:#64748b;">Das Profil wird im Dashboard ausgeblendet, solange der Filter nicht aktiv ist.</div>
            </div>
        </label>
    </div>

    <!-- Öffentliche Job-Listing-Seite -->
    <div class="form-group">
        <label class="checkbox-label" style="display:flex;align-items:center;gap:.75rem;cursor:pointer;">
            <input type="checkbox" name="show_in_listing" value="1"
                   <?php echo ($profile->show_in_listing ?? 1) ? 'checked' : ''; ?>>
            <div>
                <div style="font-weight:500;">🌐 In öffentlicher Stellenliste anzeigen (<code>/jobs</code>)</div>
                <div style="font-size:.82rem;color:#64748b;">Das Profil erscheint auf der öffentlichen Karriere-Seite für Bewerber.</div>
            </div>
        </label>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div class="form-group">
            <label class="form-label" for="jpg-employment">Beschäftigungsart</label>
            <select id="jpg-employment" name="employment_type" class="form-control">
                <?php foreach (['fulltime'=>'Vollzeit','parttime'=>'Teilzeit','freelance'=>'Freiberuflich','internship'=>'Praktikum','mini'=>'Minijob'] as $val => $lbl): ?>
                <option value="<?php echo $val; ?>" <?php echo ($profile->employment_type ?? 'fulltime') === $val ? 'selected' : ''; ?>>
                    <?php echo $lbl; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="jpg-remote">Remote-Option</label>
            <select id="jpg-remote" name="remote_option" class="form-control">
                <?php foreach (['onsite'=>'Vor Ort','hybrid'=>'Hybrid','remote'=>'Remote'] as $val => $lbl): ?>
                <option value="<?php echo $val; ?>" <?php echo ($profile->remote_option ?? 'onsite') === $val ? 'selected' : ''; ?>>
                    <?php echo $lbl; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div class="form-group">
            <label class="form-label" for="jpg-location">Standort</label>
            <input type="text" id="jpg-location" name="location" class="form-control"
                   value="<?php echo $v('location'); ?>"
                   placeholder="z.B. Berlin, München oder Remote">
        </div>

        <div class="form-group">
            <label class="form-label" for="jpg-level">Erfahrungslevel</label>
            <select id="jpg-level" name="experience_level" class="form-control">
                <?php foreach (['entry'=>'Berufseinsteiger','junior'=>'Junior (1–2J)','mid'=>'Mid-Level (3–5J)','senior'=>'Senior (5+J)','lead'=>'Lead / Principal','executive'=>'Executive'] as $val => $lbl): ?>
                <option value="<?php echo $val; ?>" <?php echo ($profile->experience_level ?? 'mid') === $val ? 'selected' : ''; ?>>
                    <?php echo $lbl; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr 120px;gap:1.25rem;">
        <div class="form-group">
            <label class="form-label" for="jpg-salary-min">Gehalt von (€)</label>
            <input type="number" id="jpg-salary-min" name="salary_min" class="form-control"
                   value="<?php echo esc_attr($profile->salary_min ?? ''); ?>"
                   min="0" max="999999" step="100" placeholder="z.B. 45000">
        </div>
        <div class="form-group">
            <label class="form-label" for="jpg-salary-max">Gehalt bis (€)</label>
            <input type="number" id="jpg-salary-max" name="salary_max" class="form-control"
                   value="<?php echo esc_attr($profile->salary_max ?? ''); ?>"
                   min="0" max="999999" step="100" placeholder="z.B. 65000">
        </div>
        <div class="form-group">
            <label class="form-label" for="jpg-currency">Währung</label>
            <select id="jpg-currency" name="salary_currency" class="form-control">
                <?php foreach (['EUR', 'CHF', 'USD'] as $cur): ?>
                <option value="<?php echo $cur; ?>" <?php echo ($profile->salary_currency ?? 'EUR') === $cur ? 'selected' : ''; ?>>
                    <?php echo $cur; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="jpg-summary">Kurzbeschreibung</label>
        <?php if (!empty($textModules)): ?>
        <details style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:.5rem;">
            <summary style="padding:.5rem .75rem;font-size:.82rem;font-weight:600;color:#475569;cursor:pointer;">
                📝 Textbaustein einfügen
            </summary>
            <div style="padding:.5rem .75rem .75rem;display:flex;flex-wrap:wrap;gap:.35rem;">
                <?php foreach ($textModules as $tm): ?>
                <button type="button" class="btn btn-sm btn-outline" style="font-size:.78rem;"
                        onclick="document.getElementById('jpg-summary').value=<?php echo htmlspecialchars(json_encode($tm->content), ENT_QUOTES); ?>;document.getElementById('jpg-summary').dispatchEvent(new Event('input'));"
                        title="<?php echo htmlspecialchars($tm->content, ENT_QUOTES); ?>">
                    <?php echo htmlspecialchars($tm->category . ': ' . $tm->title); ?>
                </button>
                <?php endforeach; ?>
            </div>
        </details>
        <?php endif; ?>
        <textarea id="jpg-summary" name="summary" class="form-control"
                  style="height:120px;resize:vertical;" maxlength="500"
                  placeholder="1–3 Sätze, die sofort Interesse wecken…"><?php echo $v('summary'); ?></textarea>
        <small class="form-text">Empfohlen: 80–150 Zeichen</small>
        <div class="jpg-char-counter" data-target="jpg-summary" data-min="80" data-max="150" data-max-warn="200"></div>
    </div>

    <div class="form-group">
        <label class="form-label" for="jpg-description">Ausführliche Beschreibung</label>
        <?php if (!empty($textModules)): ?>
        <details style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:.5rem;">
            <summary style="padding:.5rem .75rem;font-size:.82rem;font-weight:600;color:#475569;cursor:pointer;">
                📝 Textbaustein einfügen
            </summary>
            <div style="padding:.5rem .75rem .75rem;display:flex;flex-wrap:wrap;gap:.35rem;">
                <?php foreach ($textModules as $tm): ?>
                <button type="button" class="btn btn-sm btn-outline" style="font-size:.78rem;"
                        onclick="var ta=document.getElementById('jpg-description');var s=ta.selectionStart;ta.value=ta.value.substring(0,s)+<?php echo htmlspecialchars(json_encode($tm->content), ENT_QUOTES); ?>+ta.value.substring(ta.selectionEnd);ta.focus();"
                        title="<?php echo htmlspecialchars($tm->content, ENT_QUOTES); ?>">
                    <?php echo htmlspecialchars($tm->category . ': ' . $tm->title); ?>
                </button>
                <?php endforeach; ?>
            </div>
        </details>
        <?php endif; ?>
        <textarea id="jpg-description" name="description"
                  class="form-control jpg-rich-editor"
                  style="min-height:250px;resize:vertical;"><?php echo $v('description'); ?></textarea>
        <small class="form-text">Über das Unternehmen, die Unternehmenskultur, weitere Details. Kann HTML enthalten.</small>
    </div>

    <div style="background:#f8fafc;border-radius:8px;padding:1rem 1.25rem;margin-bottom:1rem;border:1px solid #e2e8f0;">
        <h4 style="margin:0 0 .5rem;font-size:.95rem;">📚 Bibliothek-Schnellzugriff</h4>
        <p style="color:#64748b;font-size:.82rem;margin:0 0 .75rem;">Ergänze dein Profil mit Inhalten aus der Bibliothek – Benefits, Skills & Anforderungen werden in den jeweiligen Tabs zugewiesen.</p>
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
            <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-libraries?tab=text-modules'); ?>"
               class="btn btn-secondary btn-sm" target="_blank">📝 Textbausteine</a>
            <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-libraries?tab=skill-matrix'); ?>"
               class="btn btn-secondary btn-sm" target="_blank">🏷️ Skill-Matrix</a>
            <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-libraries?tab=benefit-catalog'); ?>"
               class="btn btn-secondary btn-sm" target="_blank">🎁 Benefit-Katalog</a>
            <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-libraries?tab=requirement-items'); ?>"
               class="btn btn-secondary btn-sm" target="_blank">📋 Anforderungs-Liste</a>
            <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-libraries?tab=job-categories'); ?>"
               class="btn btn-secondary btn-sm" target="_blank">🗂️ Job-Kategorien</a>
        </div>
    </div>

    <div class="form-actions-card admin-card" style="margin-top:1rem;">
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Basisdaten speichern</button>
            <span class="form-actions__hint" style="color:#64748b;font-size:.875rem;">
                Pflichtfeld: Stellentitel <span style="color:#ef4444;">*</span>
            </span>
        </div>
    </div>
</form>

<!-- ═══════════════════════════════════════════════ TAB 2: AUFGABEN ══ -->
<?php elseif ($tab === 'tasks'): ?>
<form method="post" class="admin-form" id="jpgTasksForm">
    <input type="hidden" name="_jpg_nonce"  value="<?php echo esc_attr($nonce); ?>">
    <input type="hidden" name="_jpg_action" value="save_tasks">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem;">
        <h3>📌 Aufgaben & Verantwortlichkeiten</h3>
        <button type="button" class="btn btn-secondary btn-sm" onclick="jpgAddTask()">➕ Aufgabe hinzufügen</button>
    </div>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">
        Mindestens <strong>3 Aufgaben</strong> erforderlich. Ziehen und ablegen (Drag &amp; Drop) zum Sortieren.
    </p>

    <?php if (!empty($textModules)): ?>
    <details style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:.75rem;">
        <summary style="padding:.5rem .75rem;font-size:.82rem;font-weight:600;color:#475569;cursor:pointer;">
            📝 Textbaustein als Aufgabe einfügen
        </summary>
        <div style="padding:.5rem .75rem .75rem;display:flex;flex-wrap:wrap;gap:.35rem;">
            <?php foreach ($textModules as $tm): ?>
            <button type="button" class="btn btn-sm btn-outline" style="font-size:.78rem;"
                    onclick="jpgAddTaskFromModule(<?php echo htmlspecialchars(json_encode($tm->content), ENT_QUOTES); ?>)"
                    title="<?php echo htmlspecialchars($tm->content, ENT_QUOTES); ?>">
                <?php echo htmlspecialchars($tm->category . ': ' . $tm->title); ?>
            </button>
            <?php endforeach; ?>
        </div>
    </details>
    <?php endif; ?>

    <div id="jpgTaskList" class="jpg-sortable-list">
        <?php if (empty($tasks)): ?>
        <div class="jpg-task-item" data-index="0">
            <span class="jpg-drag-handle" draggable="true" title="Ziehen zum Sortieren">⠿</span>
            <input type="text" name="tasks[]" class="form-control" placeholder="Aufgabenbeschreibung eingeben…" style="flex:1;">
            <button type="button" class="btn btn-sm btn-danger" onclick="jpgRemoveTask(this)" title="Löschen">✕</button>
        </div>
        <?php else: ?>
        <?php foreach ($tasks as $i => $task): ?>
        <div class="jpg-task-item" data-index="<?php echo $i; ?>">
            <span class="jpg-drag-handle" draggable="true" title="Ziehen zum Sortieren">⠿</span>
            <input type="text" name="tasks[]" class="form-control"
                   value="<?php echo htmlspecialchars($task->task_text, ENT_QUOTES); ?>"
                   placeholder="Aufgabenbeschreibung eingeben…" style="flex:1;">
            <button type="button" class="btn btn-sm btn-danger" onclick="jpgRemoveTask(this)" title="Löschen">✕</button>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="form-actions-card admin-card" style="margin-top:1rem;">
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Aufgaben speichern</button>
            <span style="color:#64748b;font-size:.875rem;">Mindestens 3 Einträge erforderlich</span>
        </div>
    </div>
</form>

<!-- ═══════════════════════════════════════════════ TAB 3: ANFORDERUNGEN ══ -->
<?php elseif ($tab === 'requirements'): ?>

<!-- Datalist für Autocomplete aus Skill-Matrix -->
<datalist id="jpgSkillDatalist">
    <?php foreach ($allSkills as $groupName => $skills): ?>
    <?php foreach ($skills as $s): ?>
    <option value="<?php echo htmlspecialchars($s->skill_name ?? '', ENT_QUOTES); ?>">
    <?php endforeach; ?>
    <?php endforeach; ?>
</datalist>

<form method="post" class="admin-form" id="jpgReqForm">
    <input type="hidden" name="_jpg_nonce"  value="<?php echo esc_attr($nonce); ?>">
    <input type="hidden" name="_jpg_action" value="save_requirements">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem;">
        <h3>📋 Anforderungen</h3>
        <button type="button" class="btn btn-secondary btn-sm" onclick="jpgAddReq()">➕ Freie Anforderung</button>
    </div>

    <!-- ── Schnellauswahl aus Skill-Matrix ── -->
    <?php if (!empty($allSkills)): ?>
    <div class="admin-card" style="margin-bottom:1rem;padding:1rem 1.25rem;background:#f8fafc;border-left:4px solid var(--admin-primary);">
        <p style="font-size:.875rem;color:#475569;margin:0 0 .75rem;font-weight:600;">
            🏷️ Aus Skill-Matrix hinzufügen
        </p>
        <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
            <select id="jpgSkillPicker" class="form-control" style="flex:1;min-width:200px;">
                <option value="">— Skill wählen —</option>
                <?php foreach ($allSkills as $groupName => $skills): ?>
                <optgroup label="<?php echo htmlspecialchars($groupName, ENT_QUOTES); ?>">
                    <?php foreach ($skills as $s): ?>
                    <option value="<?php echo htmlspecialchars($s->skill_name ?? '', ENT_QUOTES); ?>">
                        <?php echo htmlspecialchars($s->skill_name ?? '', ENT_QUOTES); ?>
                    </option>
                    <?php endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
            </select>
            <select id="jpgSkillPickerType" class="form-control" style="width:160px;flex-shrink:0;">
                <option value="must">🔴 Pflicht</option>
                <option value="nice">🔵 Wünschenswert</option>
            </select>
            <button type="button" class="btn btn-primary btn-sm" onclick="jpgAddSkillReq()">➕ Übernehmen</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Schnellauswahl aus Anforderungs-Liste ── -->
    <?php if (!empty($requirementItems)): ?>
    <div class="admin-card" style="margin-bottom:1rem;padding:1rem 1.25rem;background:#f8fafc;border-left:4px solid #10b981;">
        <p style="font-size:.875rem;color:#475569;margin:0 0 .75rem;font-weight:600;">
            📋 Aus Anforderungs-Katalog hinzufügen
        </p>
        <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
            <select id="jpgReqItemPicker" class="form-control" style="flex:1;min-width:200px;"
                    onchange="jpgSyncReqType(this)">
                <option value="">— Anforderung wählen —</option>
                <?php foreach ($requirementItems as $groupName => $items): ?>
                <optgroup label="<?php echo htmlspecialchars($groupName, ENT_QUOTES); ?>">
                    <?php foreach ($items as $ri):
                        $rtMap = ['must'=>'🔴 Pflicht','nice'=>'🔵 Wünsch','optional'=>'🟢 Optional'];
                        $rtLabel = $rtMap[$ri->req_type ?? 'must'] ?? $ri->req_type;
                    ?>
                    <option value="<?php echo htmlspecialchars(($ri->icon ?? '') . ($ri->icon ? ' ' : '') . $ri->title, ENT_QUOTES); ?>"
                            data-req-type="<?php echo htmlspecialchars($ri->req_type ?? 'must', ENT_QUOTES); ?>">
                        <?php echo htmlspecialchars(($ri->icon ?? '') . ' ' . $ri->title . ' [' . $rtLabel . ']', ENT_QUOTES); ?>
                    </option>
                    <?php endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
            </select>
            <select id="jpgReqItemPickerType" class="form-control" style="width:160px;flex-shrink:0;">
                <option value="must">🔴 Pflicht</option>
                <option value="nice">🔵 Wünschenswert</option>
                <option value="optional">🟢 Optional</option>
            </select>
            <button type="button" class="btn btn-primary btn-sm" onclick="jpgAddReqItem()">➕ Übernehmen</button>
        </div>
    </div>
    <?php endif; ?>

    <div id="jpgReqList">
        <?php if (empty($requirements)): ?>
        <div class="jpg-req-item" style="display:flex;gap:.75rem;align-items:center;margin-bottom:.5rem;">
            <select name="req_type[]" class="form-control" style="width:130px;flex-shrink:0;">
                <option value="must">🔴 Pflicht</option>
                <option value="nice">🔵 Wünschenswert</option>
                <option value="optional">🟢 Optional</option>
            </select>
            <input type="text" name="req_text[]" list="jpgSkillDatalist" class="form-control"
                   placeholder="Anforderung eingeben oder Skill wählen…" style="flex:1;">
            <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.jpg-req-item').remove()">✕</button>
        </div>
        <?php else: ?>
        <?php foreach ($requirements as $i => $req): ?>
        <div class="jpg-req-item" style="display:flex;gap:.75rem;align-items:center;margin-bottom:.5rem;">
            <select name="req_type[]" class="form-control" style="width:130px;flex-shrink:0;">
                <option value="must"<?php echo $req->req_type === 'must' ? ' selected' : ''; ?>>🔴 Pflicht</option>
                <option value="nice"<?php echo $req->req_type === 'nice' ? ' selected' : ''; ?>>🔵 Wünschenswert</option>
                <option value="optional"<?php echo ($req->req_type ?? '') === 'optional' ? ' selected' : ''; ?>>🟢 Optional</option>
            </select>
            <input type="text" name="req_text[]" list="jpgSkillDatalist" class="form-control"
                   value="<?php echo htmlspecialchars($req->req_text, ENT_QUOTES); ?>"
                   placeholder="Anforderung eingeben oder Skill wählen…" style="flex:1;">
            <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.jpg-req-item').remove()">✕</button>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="form-actions-card admin-card" style="margin-top:1rem;">
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Anforderungen speichern</button>
        </div>
    </div>
</form>

<!-- ═══════════════════════════════════════════════ TAB 4: BENEFITS ══ -->
<?php elseif ($tab === 'benefits'): ?>
<form method="post" class="admin-form">
    <input type="hidden" name="_jpg_nonce"  value="<?php echo esc_attr($nonce); ?>">
    <input type="hidden" name="_jpg_action" value="save_benefits">

    <h3>🎁 Benefits auswählen</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem;">
        Klicke auf Benefits um sie dem Profil hinzuzufügen oder zu entfernen.
    </p>

    <?php if (!empty($companyDefaultBenefitIds)): ?>
    <div class="alert" style="background:#d1fae5;color:#065f46;border-left:4px solid #10b981;border-radius:0 6px 6px 0;margin-bottom:1rem;">
        🏢 <strong>Unternehmens-Benefits vorausgewählt.</strong>
        Die Standard-Benefits des verknüpften Unternehmens wurden markiert.
        Du kannst sie jederzeit anpassen.
    </div>
    <?php endif; ?>

    <?php foreach ($allBenefits as $groupName => $benefits): ?>
    <div class="admin-card" style="margin-bottom:1rem;padding:1rem 1.25rem;">
        <h4 style="margin:0 0 .75rem;cursor:pointer;display:flex;justify-content:space-between;align-items:center;"
            onclick="jpgToggleGroup(this)">
            <span><?php echo htmlspecialchars($groupName); ?></span>
            <span class="jpg-toggle-icon" style="font-size:.875rem;color:#94a3b8;">▼</span>
        </h4>
        <div class="jpg-grp-body" style="display:flex;flex-wrap:wrap;gap:.5rem;">
            <?php foreach ($benefits as $b): ?>
            <?php
                $bidInt  = (int)$b->id;
                // Aktiv wenn explizit gesetzt ODER (Profil neu & Unternehmens-Default)
                $checked = in_array($bidInt, $benefitIds)
                        || (empty($benefitIds) && in_array($bidInt, $companyDefaultBenefitIds));
            ?>
            <label class="jpg-benefit-toggle<?php echo $checked ? ' selected' : ''; ?>"
                   style="cursor:pointer;user-select:none;">
                <input type="checkbox" name="benefit_ids[]"
                       value="<?php echo $bidInt; ?>"
                       <?php echo $checked ? 'checked' : ''; ?>
                       style="display:none;"
                       onchange="this.closest('.jpg-benefit-toggle').classList.toggle('selected',this.checked)">
                <?php echo htmlspecialchars($b->icon . ' ' . $b->title); ?>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($allBenefits)): ?>
    <div class="empty-state">
        <p style="font-size:2rem;">📭</p>
        <p>Noch keine Benefits im Katalog.</p>
        <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-libraries?tab=benefit-catalog'); ?>"
           class="btn btn-secondary">📚 Zum Benefit-Katalog</a>
    </div>
    <?php endif; ?>

    <div class="form-actions-card admin-card" style="margin-top:1rem;">
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Benefits speichern</button>
            <a href="<?php echo esc_url($baseUrl . '&tab=skills'); ?>"
               class="btn btn-secondary">➡️ Weiter zu Skills</a>
        </div>
    </div>
</form>

<!-- ═══════════════════════════════════════════════ TAB 5: SKILLS ══ -->
<?php elseif ($tab === 'skills'): ?>
<form method="post" class="admin-form">
    <input type="hidden" name="_jpg_nonce"  value="<?php echo esc_attr($nonce); ?>">
    <input type="hidden" name="_jpg_action" value="save_skills">

    <h3>🏷️ Skills zuweisen</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem;">
        Wähle relevante Skills aus der Skill-Matrix und lege optional das Level fest.
    </p>

    <?php
    // Vorhandene Skill-IDs und Level ermitteln
    $profileSkillMap = [];
    foreach ($profileSkills as $ps) {
        $profileSkillMap[(int)$ps->skill_id] = $ps->level ?? 'intermediate';
    }
    ?>

    <?php foreach ($allSkills as $groupName => $skills): ?>
    <div class="admin-card" style="margin-bottom:1rem;padding:1rem 1.25rem;">
        <h4 style="margin:0 0 .75rem;"><?php echo htmlspecialchars($groupName); ?></h4>
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
            <?php foreach ($skills as $sk): ?>
            <?php
                $skillId  = (int)$sk->id;
                $selected = isset($profileSkillMap[$skillId]);
                $level    = $profileSkillMap[$skillId] ?? 'intermediate';
            ?>
            <label class="jpg-benefit-toggle<?php echo $selected ? ' selected' : ''; ?>"
                   style="cursor:pointer;user-select:none;">
                <input type="checkbox" name="skill_ids[]"
                       value="<?php echo $skillId; ?>"
                       <?php echo $selected ? 'checked' : ''; ?>
                       style="display:none;"
                       onchange="this.closest('.jpg-benefit-toggle').classList.toggle('selected',this.checked);this.nextElementSibling.style.display=this.checked?'inline-block':'none';">
                <?php echo htmlspecialchars($sk->skill_name); ?>
                <select name="skill_level_<?php echo $skillId; ?>"
                        style="display:<?php echo $selected ? 'inline-block' : 'none'; ?>;font-size:.72rem;padding:.1rem .2rem;margin-left:.25rem;border-radius:4px;border:1px solid #cbd5e1;"
                        onclick="event.stopPropagation();">
                    <option value="basic"<?php echo $level === 'basic' ? ' selected' : ''; ?>>Basis</option>
                    <option value="intermediate"<?php echo $level === 'intermediate' ? ' selected' : ''; ?>>Mittel</option>
                    <option value="advanced"<?php echo $level === 'advanced' ? ' selected' : ''; ?>>Fortgeschritten</option>
                    <option value="expert"<?php echo $level === 'expert' ? ' selected' : ''; ?>>Experte</option>
                </select>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($allSkills)): ?>
    <div class="empty-state">
        <p style="font-size:2rem;">📭</p>
        <p>Noch keine Skills in der Skill-Matrix.</p>
        <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-libraries?tab=skill-matrix'); ?>"
           class="btn btn-secondary">📚 Zur Skill-Matrix</a>
    </div>
    <?php endif; ?>

    <div class="form-actions-card admin-card" style="margin-top:1rem;">
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Skills speichern</button>
        </div>
    </div>
</form>

<!-- ═══════════════════════════════════════════════ TAB 6: REVIEW & EXPORT ══ -->
<?php elseif ($tab === 'review'): ?>

<h3>🔍 Review & Export</h3>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
    <!-- Checkliste -->
    <div>
        <h4>✅ Vollständigkeits-Check</h4>
        <?php
        $checks = [
            'Titel vorhanden'      => !empty($profile->title ?? ''),
            'Kategorie gewählt'    => !empty($profile->job_category_id ?? ''),
            'Kurzbeschreibung'     => !empty($profile->summary ?? ''),
            'Mindestens 3 Aufgaben'=> count($tasks) >= 3,
            'Anforderungen'        => count($requirements) >= 1,
            'Benefits'             => count($benefitIds) >= 1,
            'Skills'               => count($profileSkills) >= 1,
        ];
        foreach ($checks as $label => $ok): ?>
        <div style="display:flex;align-items:center;gap:.5rem;padding:.3rem 0;">
            <span style="font-size:1.1rem;"><?php echo $ok ? '✅' : '❌'; ?></span>
            <span style="<?php echo $ok ? '' : 'color:#ef4444;'; ?>"><?php echo $label; ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Export-Buttons -->
    <div>
        <h4>📤 Export-Optionen</h4>
        <div style="display:flex;flex-direction:column;gap:.75rem;">
            <button type="button" class="btn btn-secondary"
                    onclick="jpgPreviewHtml(<?php echo $id; ?>)">
                👁️ HTML-Vorschau
            </button>
            <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-generator?_jpg_export=json&id=' . $id . '&_jpg_nonce=' . $nonce); ?>"
               class="btn btn-secondary" target="_blank">
                📄 Als JSON exportieren
            </a>
        </div>
    </div>
</div>

<!-- Schnellübersicht -->
<h4>📊 Profil-Zusammenfassung</h4>
<div class="info-grid">
    <div class="info-card">
        <h4>Basisdaten</h4>
        <ul class="info-list">
            <li><strong>Titel:</strong> <?php echo htmlspecialchars($profile->title ?? '—'); ?></li>
            <li><strong>Standort:</strong> <?php echo htmlspecialchars($profile->location ?? '—'); ?></li>
            <li><strong>Status:</strong> <?php echo htmlspecialchars($profile->status ?? '—'); ?></li>
            <li><strong>Aufgaben:</strong> <?php echo count($tasks); ?></li>
            <li><strong>Anforderungen:</strong> <?php echo count($requirements); ?></li>
            <li><strong>Benefits:</strong> <?php echo count($benefitIds); ?></li>
        </ul>
    </div>
</div>

<!-- Preview-Iframe -->
<div id="jpgPreviewContainer" style="display:none;margin-top:1.5rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem;">
        <h4 style="margin:0;">👁️ HTML-Vorschau</h4>
        <button type="button" class="btn btn-secondary btn-sm"
                onclick="document.getElementById('jpgPreviewContainer').style.display='none'">✕ Schließen</button>
    </div>
    <iframe id="jpgPreviewFrame"
            style="width:100%;height:600px;border:1px solid #e2e8f0;border-radius:8px;"></iframe>
</div>
<?php endif; ?>

</div><!-- /.admin-card -->

<script>
function jpgMakeRemoveButton(label, onClick) {
    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-sm btn-danger';
    button.textContent = label || '✕';
    button.addEventListener('click', onClick || function () {
        var row = button.closest('.jpg-req-item, .jpg-task-item');
        if (row) row.remove();
    });
    return button;
}

function jpgMakeReqTypeSelect(selectedType, includeOptional) {
    var select = document.createElement('select');
    select.name = 'req_type[]';
    select.className = 'form-control';
    select.style.cssText = 'width:130px;flex-shrink:0;';
    var options = [['must', '🔴 Pflicht'], ['nice', '🔵 Wünschenswert']];
    if (includeOptional) {
        options.push(['optional', '🟢 Optional']);
    }
    options.forEach(function (optionData) {
        var option = document.createElement('option');
        option.value = optionData[0];
        option.textContent = optionData[1];
        option.selected = optionData[0] === selectedType;
        select.appendChild(option);
    });
    return select;
}

function jpgMakeTextInput(name, placeholder, value, listId) {
    var input = document.createElement('input');
    input.type = 'text';
    input.name = name;
    input.className = 'form-control';
    input.placeholder = placeholder;
    input.style.flex = '1';
    if (value) input.value = String(value);
    if (listId) input.setAttribute('list', listId);
    return input;
}

function jpgAppendTaskRow(list, value) {
    var idx  = list.querySelectorAll('.jpg-task-item').length;
    var div  = document.createElement('div');
    div.className   = 'jpg-task-item';
    div.dataset.index = String(idx);
    var handle = document.createElement('span');
    handle.className = 'jpg-drag-handle';
    handle.draggable = true;
    handle.title = 'Ziehen zum Sortieren';
    handle.textContent = '⠿';
    var input = jpgMakeTextInput('tasks[]', 'Aufgabenbeschreibung eingeben…', value || '');
    var remove = jpgMakeRemoveButton('✕', function () { jpgRemoveTask(remove); });
    remove.title = 'Löschen';
    div.append(handle, input, remove);
    list.appendChild(div);
    return input;
}

function jpgAppendReqRow(list, selectedType, value, includeOptional) {
    var div = document.createElement('div');
    div.className = 'jpg-req-item';
    div.style.cssText = 'display:flex;gap:.75rem;align-items:center;margin-bottom:.5rem;';
    div.append(
        jpgMakeReqTypeSelect(selectedType || 'must', includeOptional),
        jpgMakeTextInput('req_text[]', includeOptional ? 'Anforderung eingeben…' : 'Anforderung eingeben oder Skill wählen…', value || '', 'jpgSkillDatalist'),
        jpgMakeRemoveButton('✕', function () { div.remove(); })
    );
    list.appendChild(div);
    return div.querySelector('input[type="text"]');
}

// ── Aufgaben (Tab 2) ───────────────────────────────────────────────────────
function jpgAddTask() {
    var list = document.getElementById('jpgTaskList');
    if (!list) return;
    jpgAppendTaskRow(list, '').focus();
    jpgInitDnd();
}
function jpgRemoveTask(btn) {
    var items = document.querySelectorAll('.jpg-task-item');
    if (items.length <= 1) { alert('Mindestens eine Aufgabe muss vorhanden sein.'); return; }
    btn.closest('.jpg-task-item').remove();
}

// ── Anforderungen (Tab 3) ─────────────────────────────────────────────────
function jpgAddReq() {
    var list = document.getElementById('jpgReqList');
    if (!list) return;
    jpgAppendReqRow(list, 'must', '', true).focus();
}

function jpgAddSkillReq() {
    var picker = document.getElementById('jpgSkillPicker');
    var typePicker = document.getElementById('jpgSkillPickerType');
    if (!picker || !picker.value) return;
    var list = document.getElementById('jpgReqList');
    if (!list) return;
    var type = typePicker ? typePicker.value : 'must';
    jpgAppendReqRow(list, type, picker.value, false);
    picker.value = '';
}

function jpgSyncReqType(sel) {
    var tp = document.getElementById('jpgReqItemPickerType');
    if (!tp) return;
    var opt = sel.options[sel.selectedIndex];
    var rt  = opt ? (opt.dataset.reqType || 'must') : 'must';
    tp.value = rt;
}
function jpgAddReqItem() {
    var picker     = document.getElementById('jpgReqItemPicker');
    var typePicker = document.getElementById('jpgReqItemPickerType');
    if (!picker || !picker.value) return;
    var list = document.getElementById('jpgReqList');
    if (!list) return;
    var opt  = picker.options[picker.selectedIndex];
    var type = (opt && opt.dataset.reqType) ? opt.dataset.reqType : (typePicker ? typePicker.value : 'must');
    jpgAppendReqRow(list, type, picker.value, true);
    picker.value = '';
}

function jpgAddTaskFromModule(content) {
    var list = document.getElementById('jpgTaskList');
    if (!list) return;
    jpgAppendTaskRow(list, String(content || ''));
    jpgInitDnd();
}

// ── Drag & Drop-Sortierung ────────────────────────────────────────────────
var jpgDragSrc = null;

function jpgInitDnd() {
    document.querySelectorAll('.jpg-task-item').forEach(function(item) {
        item.querySelector('.jpg-drag-handle').addEventListener('dragstart', function(e) {
            jpgDragSrc = item;
            e.dataTransfer.effectAllowed = 'move';
        });
        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            item.style.borderTop = '2px solid var(--admin-primary)';
        });
        item.addEventListener('dragleave', function() {
            item.style.borderTop = '';
        });
        item.addEventListener('drop', function(e) {
            e.preventDefault();
            item.style.borderTop = '';
            if (jpgDragSrc && jpgDragSrc !== item) {
                var list = item.parentNode;
                list.insertBefore(jpgDragSrc, item);
            }
        });
        item.addEventListener('dragend', function() {
            item.style.borderTop = '';
        });
    });
}
document.addEventListener('DOMContentLoaded', jpgInitDnd);

// ── Echtzeit Character-Counter ────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.jpg-char-counter').forEach(function(counter) {
        var targetId = counter.dataset.target;
        var min      = parseInt(counter.dataset.min  || '0',  10);
        var max      = parseInt(counter.dataset.max  || '200',10);
        var maxWarn  = parseInt(counter.dataset.maxWarn || '0', 10);
        var target   = document.getElementById(targetId);
        if (!target) return;

        counter.style.cssText = 'font-size:.78rem;margin-top:.25rem;font-weight:500;';

        function update() {
            var len  = target.value.length;
            var html = len + ' / ' + max + ' Zeichen';
            counter.textContent = html;
            if (len < min) {
                counter.style.color = '#ef4444'; // zu kurz
            } else if (maxWarn > 0 && len > maxWarn) {
                counter.style.color = '#f59e0b'; // zu lang
            } else if (len >= min && len <= max) {
                counter.style.color = '#10b981'; // perfekt
            } else {
                counter.style.color = '#94a3b8';
            }
        }
        target.addEventListener('input', update);
        update();
    });
});

// ── Kategorie-Wechsel: Warnung wenn Benefits-Tab ohne Speichern gewechselt ──
(function () {
    var catSelect  = document.getElementById('jpg-category');
    var benefitTab = document.querySelector('.jpg-tab[href*="tab=benefits"]');
    var skillTab   = document.querySelector('.jpg-tab[href*="tab=skills"]');

    if (!catSelect) return; // nicht auf Tab 1 = kein select vorhanden

    var originalCatId = catSelect.value;
    var categoryChanged = false;

    catSelect.addEventListener('change', function () {
        categoryChanged = (catSelect.value !== originalCatId);

        // Vorhandene Warnung entfernen
        var existing = document.getElementById('jpg-cat-change-hint');
        if (existing) existing.remove();

        if (!categoryChanged) return;

        // Gelben Hinweisblock einfügen (nach dem select)
        var hint = document.createElement('div');
        hint.id = 'jpg-cat-change-hint';
        hint.style.cssText = [
            'background:#fef3c7',
            'color:#92400e',
            'border-left:4px solid #f59e0b',
            'border-radius:0 6px 6px 0',
            'padding:.6rem .9rem',
            'font-size:.83rem',
            'font-weight:500',
            'margin-top:.35rem',
        ].join(';');
        hint.append(
            document.createTextNode('⚠️ Kategorie geändert – '),
            Object.assign(document.createElement('strong'), { textContent: 'Basisdaten speichern' }),
            document.createTextNode(', damit Kategorie-Benefits im Benefits-Tab korrekt vorausgewählt werden.')
        );
        catSelect.closest('.form-group').appendChild(hint);

        // Benefits-Tab orangefarben markieren
        if (benefitTab) benefitTab.style.outline = '2px solid #f59e0b';
        if (skillTab)   skillTab.style.outline   = '2px solid #f59e0b';
    });

    // Wechsel zum Benefits-Tab abfangen (nur wenn ungespeichert)
    if (benefitTab) {
        benefitTab.addEventListener('click', function (e) {
            if (!categoryChanged) return;
            e.preventDefault();
            // Toast anzeigen
            if (typeof jpgShowToast === 'function') {
                jpgShowToast('⚠️ Bitte zuerst Basisdaten speichern, damit die Kategorie-Benefits korrekt geladen werden.', 'warn');
            }
        });
    }
    if (skillTab) {
        skillTab.addEventListener('click', function (e) {
            if (!categoryChanged) return;
            e.preventDefault();
            if (typeof jpgShowToast === 'function') {
                jpgShowToast('⚠️ Bitte zuerst Basisdaten speichern, damit die Kategorie-Benefits korrekt geladen werden.', 'warn');
            }
        });
    }
})();

// ── HTML-Vorschau (Tab 5) ─────────────────────────────────────────────────
function jpgPreviewHtml(id) {
    var container = document.getElementById('jpgPreviewContainer');
    var frame     = document.getElementById('jpgPreviewFrame');
    if (!container || !frame) return;

    var formData = new FormData();
    formData.append('_jpg_nonce',   '<?php echo esc_js($nonce); ?>');
    formData.append('profile_id',   id);
    formData.append('_jpg_action',  'preview');

    fetch(window.location.href, { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data && data.html) {
                frame.srcdoc = data.html;
                container.style.display = 'block';
                container.scrollIntoView({ behavior: 'smooth' });
            } else {
                alert('Vorschau konnte nicht geladen werden.');
            }
        })
        .catch(function(e) { alert('Fehler: ' + e.message); });
}

/**
 * Klappt eine Gruppe in der Benefit-/Anforderungs-Ansicht ein oder aus.
 * @param {HTMLElement} header  – Das angeklickte h4 / Gruppen-Element
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
