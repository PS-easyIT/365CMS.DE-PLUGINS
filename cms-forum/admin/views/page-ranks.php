<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<?php
$specialRanks = count(array_filter($ranks, static fn($rank) => !empty($rank->is_special)));
?>

<div class="forum-admin-shell">

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>Ränge verwalten</h2>
        <p>Automatische Ränge basierend auf Beitragsanzahl</p>
    </div>
    <div class="header-actions">
        <form method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="forum_action" value="recalculate_ranks">
            <button type="submit" class="btn btn-secondary btn-sm">Alle Ränge neu berechnen</button>
        </form>
        <button class="btn btn-primary" data-forum-modal-open="createRankModal">Neuer Rang</button>
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
        <span class="forum-info-card__eyebrow">Ränge gesamt</span>
        <span class="forum-info-card__value"><?php echo number_format(count($ranks)); ?></span>
        <span class="forum-info-card__text">Automatische und manuelle Auszeichnungen im Forum.</span>
    </div>
    <div class="forum-info-card">
        <span class="forum-info-card__eyebrow">Spezialränge</span>
        <span class="forum-info-card__value"><?php echo number_format($specialRanks); ?></span>
        <span class="forum-info-card__text">Nicht automatisch vergebene Sonderrollen.</span>
    </div>
</div>

<!-- Ränge-Tabelle -->
<div class="admin-card">
    <div class="forum-panel-header">
        <div>
            <h3>Alle Ränge</h3>
            <p>Titel, Mindestbeiträge und Sonderrollen sauber verwalten.</p>
        </div>
    </div>
    <?php if (empty($ranks)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0;"></p>
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
                        <td><span class="forum-muted-text"><?php echo htmlspecialchars($r->color ?? ''); ?></span></td>
                        <td><?php echo htmlspecialchars($r->icon ?? ''); ?></td>
                        <td>
                            <?php if ($r->is_special): ?>
                                <span class="status-badge" style="background:#fef3c7;color:#92400e;">Ja</span>
                            <?php else: ?>
                                <span style="color:#94a3b8;">Nein</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="forum-inline-actions">
                                <button class="btn btn-sm btn-secondary" type="button" data-forum-modal-open="editRankModal" data-forum-fill="<?php echo htmlspecialchars((string) json_encode(['edit-rank-id' => (int) $r->id, 'edit-rank-title' => (string) $r->name, 'edit-rank-posts' => (int) $r->min_posts, 'edit-rank-css' => (string) ($r->color ?? ''), 'edit-rank-icon' => (string) ($r->icon ?? ''), 'edit-rank-special' => (bool) $r->is_special], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>"><i class="ti ti-pencil" aria-hidden="true"></i></button>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="forum_action" value="delete_rank">
                                    <input type="hidden" name="rank_id" value="<?php echo (int)$r->id; ?>">
                                    <button type="button" class="btn btn-sm btn-danger" data-forum-delete-confirm="Rang wirklich löschen?"><i class="ti ti-trash" aria-hidden="true"></i></button>
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
<div id="createRankModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Neuer Rang</h3>
            <button class="modal-close" type="button" data-forum-modal-close="createRankModal">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
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
                <button type="button" class="btn btn-secondary" data-forum-modal-close="createRankModal">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Erstellen</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editRankModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Rang bearbeiten</h3>
            <button class="modal-close" type="button" data-forum-modal-close="editRankModal">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
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
                <button type="button" class="btn btn-secondary" data-forum-modal-close="editRankModal">Abbrechen</button>
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
