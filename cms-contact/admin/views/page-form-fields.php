<?php declare(strict_types=1); if (!defined('ABSPATH')) exit;
$e = fn(?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$fieldTypes  = CMS_Contact_Fields::get_field_types();
$fieldWidths = CMS_Contact_Fields::get_field_widths();
?>

<?php include CMS_CONTACT_PLUGIN_DIR . 'admin/views/partial-section-nav.php'; ?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>📝 Felder: <?php echo $e($form['title']); ?></h2>
        <p>Felder per Drag & Drop sortieren, bearbeiten oder neue hinzufügen</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" type="button" id="openFieldModalBtn">➕ Feld hinzufügen</button>
        <a href="?section=forms&action=edit&id=<?php echo (int)$form['id']; ?>" class="btn btn-secondary">↩️ Zurück</a>
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
        <p class="dl-empty-icon">📭</p>
        <p><strong>Noch keine Felder definiert</strong></p>
        <p class="text-muted">Füge das erste Feld über den Button oben rechts hinzu.</p>
    </div>
    <?php else: ?>
    <div id="fieldsContainer" class="contact-field-list">
        <?php foreach ($fields as $i => $field): ?>
        <div class="contact-field-row" data-id="<?php echo (int)$field['id']; ?>" draggable="true">
            <span class="drag-handle">⠿</span>
            <div class="contact-field-main">
                <strong class="contact-field-title"><?php echo $e($field['field_label'] ?? ''); ?></strong>
                <span class="contact-field-chip contact-field-chip--type">
                    <?php echo $e($fieldTypes[$field['field_type']]['label'] ?? $field['field_type']); ?>
                </span>
                <code class="contact-field-meta-code"><?php echo $e($field['field_name']); ?></code>
                <?php if ($field['is_required']): ?>
                <span class="contact-field-chip contact-field-chip--required">● Pflichtfeld</span>
                <?php endif; ?>
                <?php if (!empty($field['is_system'])): ?>
                <span class="contact-field-chip contact-field-chip--system">🔒 System</span>
                <?php endif; ?>
            </div>
            <span class="contact-field-width"><?php echo $e($fieldWidths[$field['field_width'] ?? 'full'] ?? $field['field_width'] ?? 'full'); ?></span>
            <div class="contact-actions-tight">
                <button class="btn btn-sm btn-secondary js-contact-edit-field" type="button"
                        data-field="<?php echo htmlspecialchars((string) json_encode($field, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>">✏️</button>
                <?php if (empty($field['is_system'])): ?>
                <form method="POST" class="js-contact-delete-field-form">
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
    <form method="POST" id="orderForm" class="contact-spacing-top">
        <input type="hidden" name="form_action" value="reorder_fields">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        <input type="hidden" name="field_order" id="fieldOrderInput" value="">
        <button type="submit" class="btn btn-secondary btn-sm" id="saveOrderBtn" hidden>
            💾 Reihenfolge speichern
        </button>
    </form>
    <?php endif; ?>
</div>

<!-- Feld hinzufügen/bearbeiten Modal -->
<div id="fieldModal" class="modal contact-modal">
    <div class="modal-content contact-modal-content--wide">
        <div class="modal-header">
            <h3 id="fieldModalTitle">➕ Neues Feld</h3>
            <button class="modal-close" type="button" data-close-modal="fieldModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="fieldForm" method="POST">
                <input type="hidden" name="form_action" id="fieldFormAction" value="save_field">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="field_id" id="fieldFormId" value="">

                <div class="contact-form-grid-2-wide">
                    <div class="form-group">
                        <label class="form-label" for="field_label">Label <span class="contact-required">*</span></label>
                        <input type="text" id="field_label" name="field_label" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="field_name">Feldname <span class="contact-required">*</span></label>
                        <input type="text" id="field_name" name="field_name" class="form-control"
                               pattern="[a-z0-9_]+" placeholder="z.B. firma_name" required>
                        <small class="form-text">Nur Kleinbuchstaben, Zahlen, Unterstrich</small>
                    </div>
                </div>

                <div class="contact-form-grid-2-wide">
                    <div class="form-group">
                        <label class="form-label" for="field_type">Typ <span class="contact-required">*</span></label>
                        <select id="field_type" name="field_type" class="form-control" required>
                            <?php foreach ($fieldTypes as $key => $ft): ?>
                            <option value="<?php echo $e($key); ?>"><?php echo $ft['icon'] . ' ' . $e($ft['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="field_width">Breite</label>
                        <select id="field_width" name="field_width" class="form-control">
                            <?php foreach ($fieldWidths as $key => $fw): ?>
                            <option value="<?php echo $e($key); ?>"><?php echo $e($fw); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="field_placeholder">Platzhaltertext</label>
                    <input type="text" id="field_placeholder" name="placeholder" class="form-control">
                </div>

                <div class="form-group" id="optionsGroup">
                    <label class="form-label" for="field_options">Optionen (eine pro Zeile)</label>
                    <textarea id="field_options" name="field_options" class="form-control" rows="4"
                              placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                    <small class="form-text">Für Select und Radio: Jede Zeile wird eine Auswahloption.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="field_validation">Validierungsregel (Regex)</label>
                    <input type="text" id="field_validation" name="field_validation" class="form-control"
                           placeholder="z.B. /^\d{5}$/ für PLZ">
                    <small class="form-text">Optional: regulärer Ausdruck zur Eingabeprüfung</small>
                </div>

                <div class="contact-inline-actions">
                    <label class="checkbox-label contact-checkbox-inline">
                        <input type="checkbox" name="is_required" value="1" id="field_required">
                        Pflichtfeld
                    </label>
                    <label class="checkbox-label contact-checkbox-inline">
                        <input type="checkbox" name="is_system" value="1" id="field_system">
                        Systemfeld (nicht löschbar)
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-close-modal="fieldModal">Abbrechen</button>
            <button type="submit" form="fieldForm" class="btn btn-primary">💾 Speichern</button>
        </div>
    </div>
</div>

<!-- Lösch-Bestätigung Modal -->
<div id="deleteConfirmModal" class="modal contact-modal">
    <div class="modal-content contact-modal-content--compact">
        <div class="modal-header">
            <h3>🗑️ Feld löschen?</h3>
            <button class="modal-close" type="button" data-close-modal="deleteConfirmModal">&times;</button>
        </div>
        <div class="modal-body">
            <p>Möchtest du dieses Feld wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" type="button" data-close-modal="deleteConfirmModal">Abbrechen</button>
            <button class="btn btn-danger" type="button" id="confirmDeleteBtn">🗑️ Endgültig löschen</button>
        </div>
    </div>
</div>
