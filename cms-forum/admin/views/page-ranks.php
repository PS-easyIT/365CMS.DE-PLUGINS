<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>🏅 Ränge verwalten</h2>
        <p>Automatische Ränge basierend auf Beitragsanzahl</p>
    </div>
    <div class="header-actions">
        <form method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="forum_action" value="recalculate_ranks">
            <button type="submit" class="btn btn-secondary btn-sm">🔄 Alle Ränge neu berechnen</button>
        </form>
        <button class="btn btn-primary" onclick="openModal('createRankModal')">➕ Neuer Rang</button>
    </div>
</div>

<?php if (isset($success) && $success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if (isset($error) && $error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Ränge-Tabelle -->
<div class="admin-card">
    <h3>🏅 Alle Ränge</h3>
    <?php if (empty($ranks)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0;">📭</p>
            <p><strong>Noch keine Ränge vorhanden</strong></p>
        </div>
    <?php else: ?>
        <div class="users-table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Titel</th>
                        <th>Min. Beiträge</th>
                        <th>CSS-Klasse</th>
                        <th>Icon</th>
                        <th>Spezialrang</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ranks as $r): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($r->name); ?></strong></td>
                        <td><?php echo (int)$r->min_posts; ?></td>
                        <td style="font-size:.85rem;color:#64748b;"><?php echo htmlspecialchars($r->color ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($r->icon ?? ''); ?></td>
                        <td>
                            <?php if ($r->is_special): ?>
                                <span class="status-badge" style="background:#fef3c7;color:#92400e;">Ja</span>
                            <?php else: ?>
                                <span style="color:#94a3b8;">Nein</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:.5rem;">
                                <button class="btn btn-sm btn-secondary" onclick="editRank(<?php echo (int)$r->id; ?>, '<?php echo htmlspecialchars($r->name, ENT_QUOTES); ?>', <?php echo (int)$r->min_posts; ?>, '<?php echo htmlspecialchars($r->color ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($r->icon ?? '', ENT_QUOTES); ?>', <?php echo $r->is_special ? 'true' : 'false'; ?>)">✏️</button>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="forum_action" value="delete_rank">
                                    <input type="hidden" name="rank_id" value="<?php echo (int)$r->id; ?>">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="openDeleteConfirm(this.closest('form'), 'Rang wirklich löschen?')">🗑️</button>
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
<div id="createRankModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Neuer Rang</h3>
            <button class="modal-close" onclick="closeModal('createRankModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="forum_action" value="create_rank">
                <div class="form-group">
                    <label class="form-label">Titel <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Mindest-Beiträge</label>
                    <input type="number" name="min_posts" class="form-control" value="0" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">CSS-Klasse</label>
                    <input type="text" name="css_class" class="form-control" placeholder="z.B. cmsforum-rank--gold">
                </div>
                <div class="form-group">
                    <label class="form-label">Icon / Emoji</label>
                    <input type="text" name="icon" class="form-control" placeholder="z.B. ⭐">
                </div>
                <div class="form-group">
                    <label class="checkbox-label"><input type="checkbox" name="is_special" value="1"> Spezialrang (nicht automatisch vergeben)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createRankModal')">Abbrechen</button>
                <button type="submit" class="btn btn-primary">💾 Erstellen</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editRankModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Rang bearbeiten</h3>
            <button class="modal-close" onclick="closeModal('editRankModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="forum_action" value="update_rank">
                <input type="hidden" name="rank_id" id="edit-rank-id">
                <div class="form-group">
                    <label class="form-label">Titel <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="edit-rank-title" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Mindest-Beiträge</label>
                    <input type="number" id="edit-rank-posts" name="min_posts" class="form-control" value="0" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">CSS-Klasse</label>
                    <input type="text" id="edit-rank-css" name="css_class" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Icon / Emoji</label>
                    <input type="text" id="edit-rank-icon" name="icon" class="form-control">
                </div>
                <div class="form-group">
                    <label class="checkbox-label"><input type="checkbox" id="edit-rank-special" name="is_special" value="1"> Spezialrang</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editRankModal')">Abbrechen</button>
                <button type="submit" class="btn btn-primary">💾 Speichern</button>
            </div>
        </form>
    </div>
</div>

<script>
function editRank(id, title, posts, css, icon, special) {
    document.getElementById('edit-rank-id').value = id;
    document.getElementById('edit-rank-title').value = title;
    document.getElementById('edit-rank-posts').value = posts;
    document.getElementById('edit-rank-css').value = css;
    document.getElementById('edit-rank-icon').value = icon;
    document.getElementById('edit-rank-special').checked = special;
    openModal('editRankModal');
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
