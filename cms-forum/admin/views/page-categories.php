<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>🗂️ Kategorien verwalten</h2>
        <p>Forum-Kategorien erstellen und sortieren</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="openModal('createCategoryModal')">➕ Neue Kategorie</button>
    </div>
</div>

<!-- Alerts -->
<?php if (isset($success) && $success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if (isset($error) && $error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Kategorien-Liste -->
<div class="admin-card">
    <h3>🗂️ Alle Kategorien</h3>
    <?php if (empty($categories)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0;">📭</p>
            <p><strong>Noch keine Kategorien vorhanden</strong></p>
            <p class="text-muted">Erstelle die erste Kategorie über den Button oben.</p>
        </div>
    <?php else: ?>
        <div class="users-table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Sortierung</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($cat->name); ?></strong></td>
                        <td style="color:#64748b;font-size:.85rem;"><?php echo htmlspecialchars($cat->slug); ?></td>
                        <td><?php echo (int)$cat->sort_order; ?></td>
                        <td>
                            <span class="status-badge <?php echo $cat->is_active ? 'active' : 'inactive'; ?>">
                                <?php echo $cat->is_active ? 'Aktiv' : 'Inaktiv'; ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:.5rem;">
                                <button class="btn btn-sm btn-secondary" onclick="editCategory(<?php echo (int)$cat->id; ?>, '<?php echo htmlspecialchars($cat->name, ENT_QUOTES); ?>', <?php echo (int)$cat->sort_order; ?>, <?php echo $cat->is_active ? 'true' : 'false'; ?>)">✏️</button>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="forum_action" value="delete_category">
                                    <input type="hidden" name="category_id" value="<?php echo (int)$cat->id; ?>">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="openDeleteConfirm(this.closest('form'), 'Kategorie wirklich löschen?')">🗑️</button>
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

<!-- Create Modal -->
<div id="createCategoryModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Neue Kategorie</h3>
            <button class="modal-close" onclick="closeModal('createCategoryModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="forum_action" value="create_category">
                <div class="form-group">
                    <label for="cat-name" class="form-label">Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="cat-name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="cat-sort" class="form-label">Sortierung</label>
                    <input type="number" id="cat-sort" name="sort_order" class="form-control" value="0" min="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createCategoryModal')">Abbrechen</button>
                <button type="submit" class="btn btn-primary">💾 Erstellen</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editCategoryModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Kategorie bearbeiten</h3>
            <button class="modal-close" onclick="closeModal('editCategoryModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="forum_action" value="update_category">
                <input type="hidden" name="category_id" id="edit-cat-id">
                <div class="form-group">
                    <label for="edit-cat-name" class="form-label">Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="edit-cat-name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit-cat-sort" class="form-label">Sortierung</label>
                    <input type="number" id="edit-cat-sort" name="sort_order" class="form-control" value="0" min="0">
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="edit-cat-active" name="is_active" value="1"> Aktiv
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editCategoryModal')">Abbrechen</button>
                <button type="submit" class="btn btn-primary">💾 Speichern</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCategory(id, name, sort, active) {
    document.getElementById('edit-cat-id').value = id;
    document.getElementById('edit-cat-name').value = name;
    document.getElementById('edit-cat-sort').value = sort;
    document.getElementById('edit-cat-active').checked = active;
    openModal('editCategoryModal');
}
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
let _deleteConfirmForm = null;
function openDeleteConfirm(form, msg) {
    _deleteConfirmForm = form;
    document.getElementById('deleteConfirmMsg').textContent = msg;
    openModal('deleteConfirmModal');
}
function confirmDelete() {
    if (_deleteConfirmForm) _deleteConfirmForm.submit();
    closeModal('deleteConfirmModal');
}
window.addEventListener('click', function(e) {
    document.querySelectorAll('.modal').forEach(function(m) { if (e.target === m) closeModal(m.id); });
});
</script>

<!-- Delete Confirm Modal -->
<div id="deleteConfirmModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:420px;">
        <div class="modal-header">
            <h3>⚠️ Löschen bestätigen</h3>
            <button class="modal-close" onclick="closeModal('deleteConfirmModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p id="deleteConfirmMsg">Wirklich löschen?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('deleteConfirmModal')">Abbrechen</button>
            <button type="button" class="btn btn-danger" onclick="confirmDelete()">🗑️ Löschen</button>
        </div>
    </div>
</div>
