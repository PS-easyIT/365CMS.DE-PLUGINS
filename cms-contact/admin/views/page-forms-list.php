<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>📋 Kontaktformulare</h2>
        <p>Alle Kontaktformulare verwalten und neue erstellen</p>
    </div>
    <div class="header-actions">
        <a href="?page=contact-forms&action=new" class="btn btn-primary">➕ Neues Formular</a>
    </div>
</div>

<!-- Alerts -->
<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo htmlspecialchars($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Formulare-Liste -->
<div class="admin-card">
    <h3>📋 Alle Formulare</h3>

    <?php if (empty($allForms)): ?>
    <div class="empty-state">
        <p style="font-size:2.5rem;margin:0;">📭</p>
        <p><strong>Noch keine Formulare vorhanden</strong></p>
        <p style="color:#64748b;font-size:.875rem;">Erstelle dein erstes Kontaktformular.</p>
        <a href="?page=contact-forms&action=new" class="btn btn-primary" style="margin-top:1rem;">➕ Jetzt erstellen</a>
    </div>
    <?php else: ?>
    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Slug / URL</th>
                    <th>Template</th>
                    <th>Felder</th>
                    <th>Status</th>
                    <th>Erstellt</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $fieldsService = CMS_Contact_Fields::instance();
            $tplList = CMS_Contact_Forms::get_available_templates();
            foreach ($allForms as $f):
                $fieldCount = $fieldsService->count_by_form((int)$f['id']);
                $tplName = $tplList[$f['template']]['icon'] ?? '' . ' ' . ($tplList[$f['template']]['name'] ?? $f['template']);
            ?>
            <tr>
                <td>
                    <a href="?page=contact-forms&action=edit&id=<?php echo (int)$f['id']; ?>" style="font-weight:600;color:var(--admin-primary);">
                        <?php echo htmlspecialchars($f['title']); ?>
                    </a>
                </td>
                <td>
                    <code style="font-size:.82rem;background:#f1f5f9;padding:.15rem .4rem;border-radius:4px;">/contact/<?php echo htmlspecialchars($f['slug']); ?></code>
                </td>
                <td><?php echo htmlspecialchars(($tplList[$f['template']]['icon'] ?? '') . ' ' . ($tplList[$f['template']]['name'] ?? $f['template'])); ?></td>
                <td>
                    <a href="?page=contact-forms&action=fields&id=<?php echo (int)$f['id']; ?>" style="color:var(--admin-primary);">
                        <?php echo $fieldCount; ?> Felder
                    </a>
                </td>
                <td>
                    <span class="status-badge <?php echo $f['status'] === 'active' ? 'active' : 'inactive'; ?>">
                        <?php echo $f['status'] === 'active' ? '✅ Aktiv' : '⏸️ Inaktiv'; ?>
                    </span>
                </td>
                <td><?php echo date('d.m.Y', strtotime($f['created_at'])); ?></td>
                <td>
                    <div style="display:flex;gap:.4rem;">
                        <a href="?page=contact-forms&action=fields&id=<?php echo (int)$f['id']; ?>" class="btn btn-sm btn-secondary" title="Felder bearbeiten">📝</a>
                        <a href="?page=contact-forms&action=edit&id=<?php echo (int)$f['id']; ?>" class="btn btn-sm btn-secondary" title="Einstellungen">✏️</a>
                        <a href="/contact/<?php echo htmlspecialchars($f['slug']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-secondary" title="Frontend-Vorschau">👁️</a>
                        <button type="button" class="btn btn-sm btn-danger" onclick="openDeleteModal(<?php echo (int)$f['id']; ?>, '<?php echo htmlspecialchars($f['title'], ENT_QUOTES); ?>')" title="Löschen">🗑️</button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Lösch-Modal -->
<div id="deleteModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3>🗑️ Formular löschen</h3>
            <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Soll das Formular <strong id="deleteModalName"></strong> wirklich gelöscht werden?</p>
            <p style="color:#ef4444;font-size:.875rem;">⚠️ Alle zugehörigen Felder und Nachrichten werden ebenfalls gelöscht. Diese Aktion kann nicht rückgängig gemacht werden.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Abbrechen</button>
            <form method="POST" id="deleteModalForm" style="display:inline;">
                <input type="hidden" name="form_action" value="delete_form">
                <input type="hidden" name="id" id="deleteModalId">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <button type="submit" class="btn btn-danger">🗑️ Endgültig löschen</button>
            </form>
        </div>
    </div>
</div>

<script>
function openDeleteModal(id, name) {
    document.getElementById('deleteModalId').value = id;
    document.getElementById('deleteModalName').textContent = name;
    openModal('deleteModal');
}
</script>
