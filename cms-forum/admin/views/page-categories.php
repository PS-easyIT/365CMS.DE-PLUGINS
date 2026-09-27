<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<?php
$activeCategories = count(array_filter($categories, static fn($cat) => !empty($cat->is_active)));
?>

<div class="forum-admin-shell">

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>Kategorien verwalten</h2>
        <p>Forum-Kategorien erstellen und sortieren</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" data-forum-modal-open="createCategoryModal">Neue Kategorie</button>
    </div>
</div>

<!-- Alerts -->
<?php if (isset($success) && $success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if (isset($error) && $error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="forum-card-grid">
    <div class="forum-info-card">
        <span class="forum-info-card__eyebrow">Kategorien gesamt</span>
        <span class="forum-info-card__value"><?php echo number_format(count($categories)); ?></span>
        <span class="forum-info-card__text">Hauptbereiche deiner Forumstruktur.</span>
    </div>
    <div class="forum-info-card">
        <span class="forum-info-card__eyebrow">Aktiv</span>
        <span class="forum-info-card__value"><?php echo number_format($activeCategories); ?></span>
        <span class="forum-info-card__text">Derzeit sichtbare und aktive Kategorien.</span>
    </div>
</div>

<!-- Kategorien-Liste -->
<div class="admin-card">
    <div class="forum-panel-header">
        <div>
            <h3>Alle Kategorien</h3>
            <p>Kategorien anlegen, aktivieren und nach Priorität sortieren.</p>
        </div>
    </div>
    <?php if (empty($categories)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0;"></p>
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
                        <td><span class="forum-code"><?php echo htmlspecialchars($cat->slug); ?></span></td>
                        <td><?php echo (int)$cat->sort_order; ?></td>
                        <td>
                            <span class="status-badge <?php echo $cat->is_active ? 'active' : 'inactive'; ?>">
                                <?php echo $cat->is_active ? 'Aktiv' : 'Inaktiv'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="forum-inline-actions">
                                <button class="btn btn-sm btn-secondary" type="button" data-forum-modal-open="editCategoryModal" data-forum-fill="<?php echo htmlspecialchars((string) json_encode(['edit-cat-id' => (int) $cat->id, 'edit-cat-name' => (string) $cat->name, 'edit-cat-sort' => (int) $cat->sort_order, 'edit-cat-active' => (bool) $cat->is_active], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>"><i class="ti ti-pencil" aria-hidden="true"></i></button>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="forum_action" value="delete_category">
                                    <input type="hidden" name="category_id" value="<?php echo (int)$cat->id; ?>">
                                    <button type="button" class="btn btn-sm btn-danger" data-forum-delete-confirm="Kategorie wirklich löschen?"><i class="ti ti-trash" aria-hidden="true"></i></button>
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

</div>

<!-- Create Modal -->
<div id="createCategoryModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Neue Kategorie</h3>
            <button class="modal-close" type="button" data-forum-modal-close="createCategoryModal">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
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
                <button type="button" class="btn btn-secondary" data-forum-modal-close="createCategoryModal">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Erstellen</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editCategoryModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Kategorie bearbeiten</h3>
            <button class="modal-close" type="button" data-forum-modal-close="editCategoryModal">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
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
                <button type="button" class="btn btn-secondary" data-forum-modal-close="editCategoryModal">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Speichern</button>
            </div>
        </form>
    </div>
</div>


<!-- Delete Confirm Modal -->
<div id="deleteConfirmModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:420px;">
        <div class="modal-header">
            <h3>Löschen bestätigen</h3>
            <button class="modal-close" type="button" data-forum-modal-close="deleteConfirmModal">&times;</button>
        </div>
        <div class="modal-body">
            <p id="deleteConfirmMsg">Wirklich löschen?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-forum-modal-close="deleteConfirmModal">Abbrechen</button>
            <button type="button" class="btn btn-danger" data-forum-confirm-delete>Löschen</button>
        </div>
    </div>
</div>
