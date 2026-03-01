<?php declare(strict_types=1); if (!defined('ABSPATH')) exit;
$e = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$fieldTypes  = CMS_Contact_Fields::get_field_types();
$fieldWidths = CMS_Contact_Fields::get_field_widths();
?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>📝 Felder: <?php echo $e($form['title']); ?></h2>
        <p>Felder per Drag & Drop sortieren, bearbeiten oder neue hinzufügen</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="openFieldModal()">➕ Feld hinzufügen</button>
        <a href="?page=contact-forms&action=edit&id=<?php echo (int)$form['id']; ?>" class="btn btn-secondary">↩️ Zurück</a>
    </div>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo $e($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo $e($error); ?></div>
<?php endif; ?>

<!-- Feld-Liste -->
<div class="admin-card">
    <h3>🗂️ Formularfelder (<?php echo count($fields); ?>)</h3>

    <?php if (empty($fields)): ?>
    <div class="empty-state">
        <p style="font-size:2.5rem;margin:0;">📭</p>
        <p><strong>Noch keine Felder definiert</strong></p>
        <p class="text-muted">Füge das erste Feld über den Button oben rechts hinzu.</p>
    </div>
    <?php else: ?>
    <div id="fieldsContainer" style="display:flex;flex-direction:column;gap:.5rem;">
        <?php foreach ($fields as $i => $field): ?>
        <div class="contact-field-row" data-id="<?php echo (int)$field['id']; ?>" draggable="true"
             style="display:flex;align-items:center;gap:1rem;padding:.75rem 1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;cursor:grab;transition:all .2s;">
            <span class="drag-handle" style="cursor:grab;font-size:1.2rem;color:#94a3b8;">⠿</span>
            <div style="flex:1;display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
                <strong style="min-width:120px;"><?php echo $e($field['label']); ?></strong>
                <span style="font-size:.8rem;color:#64748b;background:#e2e8f0;padding:.15rem .5rem;border-radius:4px;">
                    <?php echo $e($fieldTypes[$field['field_type']]['label'] ?? $field['field_type']); ?>
                </span>
                <code style="font-size:.75rem;color:#94a3b8;"><?php echo $e($field['field_name']); ?></code>
                <?php if ($field['is_required']): ?>
                <span style="font-size:.75rem;color:#ef4444;">● Pflichtfeld</span>
                <?php endif; ?>
                <?php if (!empty($field['is_system'])): ?>
                <span style="font-size:.75rem;color:#3b82f6;">🔒 System</span>
                <?php endif; ?>
            </div>
            <span style="font-size:.8rem;color:#64748b;"><?php echo $e($fieldWidths[$field['width']]['label'] ?? $field['width']); ?></span>
            <div style="display:flex;gap:.35rem;">
                <button class="btn btn-sm btn-secondary" type="button"
                        onclick='editField(<?php echo json_encode($field, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>✏️</button>
                <?php if (empty($field['is_system'])): ?>
                <form method="POST" style="display:inline;" onsubmit="return confirmDelete()">
                    <input type="hidden" name="form_action" value="delete_field">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="field_id" value="<?php echo (int)$field['id']; ?>">
                    <button class="btn btn-sm btn-danger" type="submit">🗑️</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Reihenfolge speichern -->
    <form method="POST" id="orderForm" style="margin-top:1rem;">
        <input type="hidden" name="form_action" value="reorder_fields">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        <input type="hidden" name="field_order" id="fieldOrderInput" value="">
        <button type="submit" class="btn btn-secondary btn-sm" id="saveOrderBtn" style="display:none;">
            💾 Reihenfolge speichern
        </button>
    </form>
    <?php endif; ?>
</div>

<!-- Feld hinzufügen/bearbeiten Modal -->
<div id="fieldModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:650px;">
        <div class="modal-header">
            <h3 id="fieldModalTitle">➕ Neues Feld</h3>
            <button class="modal-close" onclick="closeModal('fieldModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="fieldForm" method="POST">
                <input type="hidden" name="form_action" id="fieldFormAction" value="add_field">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="field_id" id="fieldFormId" value="">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label" for="field_label">Label <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="field_label" name="field_label" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="field_name">Feldname <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="field_name" name="field_name" class="form-control"
                               pattern="[a-z0-9_]+" placeholder="z.B. firma_name" required>
                        <small class="form-text">Nur Kleinbuchstaben, Zahlen, Unterstrich</small>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label" for="field_type">Typ <span style="color:#ef4444;">*</span></label>
                        <select id="field_type" name="field_type" class="form-control" onchange="toggleOptionsField()" required>
                            <?php foreach ($fieldTypes as $key => $ft): ?>
                            <option value="<?php echo $e($key); ?>"><?php echo $ft['icon'] . ' ' . $e($ft['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="field_width">Breite</label>
                        <select id="field_width" name="field_width" class="form-control">
                            <?php foreach ($fieldWidths as $key => $fw): ?>
                            <option value="<?php echo $e($key); ?>"><?php echo $e($fw['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="field_placeholder">Platzhaltertext</label>
                    <input type="text" id="field_placeholder" name="field_placeholder" class="form-control">
                </div>

                <div class="form-group" id="optionsGroup" style="display:none;">
                    <label class="form-label" for="field_options">Optionen (eine pro Zeile)</label>
                    <textarea id="field_options" name="field_options" class="form-control" rows="4"
                              placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                    <small class="form-text">Für Select, Radio und Checkbox: Jede Zeile wird eine Auswahloption.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="field_validation">Validierungsregel (Regex)</label>
                    <input type="text" id="field_validation" name="field_validation" class="form-control"
                           placeholder="z.B. /^\d{5}$/ für PLZ">
                    <small class="form-text">Optional: regulärer Ausdruck zur Eingabeprüfung</small>
                </div>

                <div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-top:.5rem;">
                    <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
                        <input type="checkbox" name="is_required" value="1" id="field_required">
                        Pflichtfeld
                    </label>
                    <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
                        <input type="checkbox" name="is_system" value="1" id="field_system">
                        Systemfeld (nicht löschbar)
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('fieldModal')">Abbrechen</button>
            <button type="submit" form="fieldForm" class="btn btn-primary">💾 Speichern</button>
        </div>
    </div>
</div>

<!-- Lösch-Bestätigung Modal -->
<div id="deleteConfirmModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:440px;">
        <div class="modal-header">
            <h3>🗑️ Feld löschen?</h3>
            <button class="modal-close" onclick="closeModal('deleteConfirmModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Möchtest du dieses Feld wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('deleteConfirmModal')">Abbrechen</button>
            <button class="btn btn-danger" id="confirmDeleteBtn">🗑️ Endgültig löschen</button>
        </div>
    </div>
</div>

<script>
/* Tab switching (reuse from admin.js or define locally) */
function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId)?.classList.add('active');
    btn.classList.add('active');
}

/* Toggle options field for select/radio/checkbox */
function toggleOptionsField() {
    const type = document.getElementById('field_type').value;
    const show = ['select', 'radio', 'checkbox'].includes(type);
    document.getElementById('optionsGroup').style.display = show ? 'block' : 'none';
}

/* Open modal for new field */
function openFieldModal() {
    document.getElementById('fieldModalTitle').textContent = '➕ Neues Feld';
    document.getElementById('fieldFormAction').value = 'add_field';
    document.getElementById('fieldFormId').value = '';
    document.getElementById('fieldForm').reset();
    document.getElementById('optionsGroup').style.display = 'none';
    openModal('fieldModal');
}

/* Open modal for editing */
function editField(field) {
    document.getElementById('fieldModalTitle').textContent = '✏️ Feld bearbeiten';
    document.getElementById('fieldFormAction').value = 'update_field';
    document.getElementById('fieldFormId').value = field.id;
    document.getElementById('field_label').value = field.label || '';
    document.getElementById('field_name').value = field.field_name || '';
    document.getElementById('field_type').value = field.field_type || 'text';
    document.getElementById('field_width').value = field.width || 'full';
    document.getElementById('field_placeholder').value = field.placeholder || '';
    document.getElementById('field_options').value = field.options || '';
    document.getElementById('field_validation').value = field.validation_rule || '';
    document.getElementById('field_required').checked = !!parseInt(field.is_required);
    document.getElementById('field_system').checked = !!parseInt(field.is_system);
    toggleOptionsField();
    openModal('fieldModal');
}

/* Modal open/close */
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
window.addEventListener('click', function(e) {
    document.querySelectorAll('.modal').forEach(m => { if (e.target === m) closeModal(m.id); });
});

/* Delete confirmation */
let pendingDeleteForm = null;
function confirmDelete() {
    pendingDeleteForm = event.target;
    openModal('deleteConfirmModal');
    return false;
}
document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (pendingDeleteForm) pendingDeleteForm.submit();
});

/* Drag & Drop Reorder */
(function() {
    const container = document.getElementById('fieldsContainer');
    if (!container) return;
    let dragEl = null;
    container.addEventListener('dragstart', function(e) {
        dragEl = e.target.closest('.contact-field-row');
        if (!dragEl) return;
        dragEl.style.opacity = '0.4';
        e.dataTransfer.effectAllowed = 'move';
    });
    container.addEventListener('dragend', function() {
        if (dragEl) dragEl.style.opacity = '1';
        dragEl = null;
    });
    container.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        const target = e.target.closest('.contact-field-row');
        if (target && target !== dragEl) {
            const rect = target.getBoundingClientRect();
            const mid = rect.top + rect.height / 2;
            if (e.clientY < mid) {
                container.insertBefore(dragEl, target);
            } else {
                container.insertBefore(dragEl, target.nextSibling);
            }
        }
    });
    container.addEventListener('drop', function(e) {
        e.preventDefault();
        const ids = [...container.querySelectorAll('.contact-field-row')].map(r => r.dataset.id);
        document.getElementById('fieldOrderInput').value = ids.join(',');
        document.getElementById('saveOrderBtn').style.display = 'inline-flex';
    });
})();
</script>
