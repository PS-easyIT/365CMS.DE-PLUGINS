<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<?php
$activeForums = count(array_filter($forums, static fn($forum) => !empty($forum->is_active)));
$subForums = count(array_filter($forums, static fn($forum) => (int) $forum->parent_id > 0));
?>

<div class="forum-admin-shell">

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>Foren verwalten</h2>
        <p>Foren und Subforen erstellen und bearbeiten</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" data-forum-modal-open="createForumModal">Neues Forum</button>
    </div>
</div>

<?php if (isset($success) && $success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if (isset($error) && $error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="forum-card-grid">
    <div class="forum-info-card">
        <span class="forum-info-card__eyebrow">Foren gesamt</span>
        <span class="forum-info-card__value"><?php echo number_format(count($forums)); ?></span>
        <span class="forum-info-card__text">Alle Foren und Unterforen im System.</span>
    </div>
    <div class="forum-info-card">
        <span class="forum-info-card__eyebrow">Aktiv</span>
        <span class="forum-info-card__value"><?php echo number_format($activeForums); ?></span>
        <span class="forum-info-card__text">Sichtbare Foren für die Community.</span>
    </div>
    <div class="forum-info-card">
        <span class="forum-info-card__eyebrow">Unterforen</span>
        <span class="forum-info-card__value"><?php echo number_format($subForums); ?></span>
        <span class="forum-info-card__text">Hierarchische Unterbereiche innerhalb der Hauptforen.</span>
    </div>
</div>

<!-- Foren-Liste nach Kategorie -->
<?php
$grouped = [];
foreach ($forums as $f) {
    $cid = (int)$f->category_id;
    $grouped[$cid][] = $f;
}
$catMap = [];
foreach ($categories as $c) { $catMap[(int)$c->id] = $c; }
?>

<?php if (empty($forums)): ?>
<div class="admin-card">
    <div class="empty-state">
        <p style="font-size:2.5rem;margin:0;"></p>
        <p><strong>Noch keine Foren vorhanden</strong></p>
        <p class="text-muted">Erstelle zuerst Kategorien und dann Foren.</p>
    </div>
</div>
<?php else: ?>
    <?php foreach ($grouped as $catId => $catForums): ?>
    <div class="admin-card">
        <div class="forum-panel-header">
            <div>
                <h3><?php echo htmlspecialchars($catMap[$catId]->name ?? "Kategorie #{$catId}"); ?></h3>
                <p><?php echo count($catForums); ?> Forum/Foren in diesem Bereich.</p>
            </div>
        </div>
        <div class="users-table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Forum</th>
                        <th>Beschreibung</th>
                        <th>Threads</th>
                        <th>Beiträge</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($catForums as $f): ?>
                    <tr>
                        <td>
                            <?php if ((int)$f->parent_id > 0): ?><span style="color:#94a3b8;margin-right:.25rem;">↳</span><?php endif; ?>
                            <strong><?php echo htmlspecialchars($f->name); ?></strong>
                        </td>
                        <td><span class="forum-muted-text"><?php echo htmlspecialchars($f->description ?? ''); ?></span></td>
                        <td><?php echo (int)$f->thread_count; ?></td>
                        <td><?php echo (int)$f->post_count; ?></td>
                        <td>
                            <span class="status-badge <?php echo $f->is_active ? 'active' : 'inactive'; ?>">
                                <?php echo $f->is_active ? 'Aktiv' : 'Inaktiv'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="forum-inline-actions">
                                <a class="btn btn-sm btn-secondary" href="?page=forum-forums&amp;edit=<?php echo (int)$f->id; ?>" title="Bearbeiten"><i class="ti ti-pencil" aria-hidden="true"></i></a>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="forum_action" value="delete_forum">
                                    <input type="hidden" name="forum_id" value="<?php echo (int)$f->id; ?>">
                                    <button type="button" class="btn btn-sm btn-danger" data-forum-delete-confirm="Forum wirklich löschen?"><i class="ti ti-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

</div>

<!-- Create Modal -->
<div id="createForumModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Neues Forum</h3>
            <button class="modal-close" type="button" data-forum-modal-close="createForumModal">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="forum_action" value="create_forum">
                <div class="form-group">
                    <label class="form-label">Kategorie <span style="color:#ef4444;">*</span></label>
                    <select name="category_id" class="form-control" required>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo (int)$c->id; ?>"><?php echo htmlspecialchars($c->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Übergeordnetes Forum</label>
                    <select name="parent_id" class="form-control">
                        <option value="0">– Kein übergeordnetes Forum –</option>
                        <?php foreach ($forums as $pf): ?>
                            <option value="<?php echo (int)$pf->id; ?>"><?php echo htmlspecialchars($pf->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Beschreibung</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Sortierung</label>
                    <input type="number" name="sort_order" class="form-control" value="0" min="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-forum-modal-close="createForumModal">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Erstellen</button>
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
