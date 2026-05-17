<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<?php
$openThreads = count(array_filter($threads, static fn($thread) => ($thread->status ?? '') === 'open'));
$closedThreads = count(array_filter($threads, static fn($thread) => ($thread->status ?? '') === 'closed'));
?>

<div class="forum-admin-shell">

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>📝 Thread-Verwaltung</h2>
        <p>Threads moderieren, verschieben und löschen</p>
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
        <span class="forum-info-card__eyebrow">Treffer gesamt</span>
        <span class="forum-info-card__value"><?php echo number_format((int) $total); ?></span>
        <span class="forum-info-card__text">Threads passend zum aktuellen Filter.</span>
    </div>
    <div class="forum-info-card">
        <span class="forum-info-card__eyebrow">Offen</span>
        <span class="forum-info-card__value"><?php echo number_format($openThreads); ?></span>
        <span class="forum-info-card__text">Aktiv diskutierbare Threads in dieser Ansicht.</span>
    </div>
    <div class="forum-info-card">
        <span class="forum-info-card__eyebrow">Geschlossen</span>
        <span class="forum-info-card__value"><?php echo number_format($closedThreads); ?></span>
        <span class="forum-info-card__text">Bereits moderierte oder abgeschlossene Themen.</span>
    </div>
</div>

<!-- Filter -->
<div class="forum-filter-card">
    <form method="GET" class="forum-filter-row">
        <input type="hidden" name="page" value="forum-threads">
        <div class="form-group" style="margin:0;min-width:200px;">
            <label class="form-label" style="font-size:.8rem;">Forum</label>
            <select name="forum" class="form-control">
                <option value="">Alle Foren</option>
                <?php foreach ($forums as $f): ?>
                    <option value="<?php echo (int)$f->id; ?>" <?php echo $filterForum === (int)$f->id ? 'selected' : ''; ?>><?php echo htmlspecialchars($f->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;min-width:150px;">
            <label class="form-label" style="font-size:.8rem;">Status</label>
            <select name="status" class="form-control">
                <option value="">Alle</option>
                <option value="open" <?php echo $filterStatus === 'open' ? 'selected' : ''; ?>>Offen</option>
                <option value="closed" <?php echo $filterStatus === 'closed' ? 'selected' : ''; ?>>Geschlossen</option>
                <option value="deleted" <?php echo $filterStatus === 'deleted' ? 'selected' : ''; ?>>Gelöscht</option>
            </select>
        </div>
        <div class="form-group" style="margin:0;min-width:200px;">
            <label class="form-label" style="font-size:.8rem;">Suche</label>
            <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Thread-Titel...">
        </div>
        <button type="submit" class="btn btn-secondary btn-sm">🔍 Filtern</button>
    </form>
</div>

<!-- Thread-Tabelle -->
<div class="admin-card">
    <div class="forum-panel-header">
        <div>
            <h3>📝 Threads (<?php echo $total; ?>)</h3>
            <p>Moderation, Status und Relevanz aller Diskussionen in einer Ansicht.</p>
        </div>
    </div>
    <?php if (empty($threads)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0;">📭</p>
            <p><strong>Keine Threads gefunden</strong></p>
        </div>
    <?php else: ?>
        <div class="users-table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Titel</th>
                        <th>Forum</th>
                        <th>Autor</th>
                        <th>Antworten</th>
                        <th>Aufrufe</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($threads as $t): ?>
                    <tr <?php echo $t->status === 'deleted' ? 'style="opacity:.5;"' : ''; ?>>
                        <td>
                            <?php if ($t->type === 'sticky'): ?>📌 <?php elseif ($t->type === 'announcement'): ?>📢 <?php endif; ?>
                            <?php echo htmlspecialchars($t->title); ?>
                        </td>
                        <td><span class="forum-muted-text"><?php echo htmlspecialchars($t->forum_name ?? '–'); ?></span></td>
                        <td><span class="forum-muted-text"><?php echo htmlspecialchars($t->username ?? 'Gelöscht'); ?></span></td>
                        <td><?php echo (int)$t->reply_count; ?></td>
                        <td><?php echo (int)$t->view_count; ?></td>
                        <td>
                            <span class="status-badge <?php echo $t->status === 'open' ? 'active' : 'inactive'; ?>">
                                <?php echo $t->status === 'open' ? 'Offen' : ($t->status === 'closed' ? 'Geschlossen' : 'Gelöscht'); ?>
                            </span>
                        </td>
                        <td>
                            <div class="forum-inline-actions">
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="forum_action" value="lock_thread">
                                    <input type="hidden" name="thread_id" value="<?php echo (int)$t->id; ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary" title="<?php echo $t->status === 'closed' ? 'Öffnen' : 'Schließen'; ?>">
                                        <?php echo $t->status === 'closed' ? '🔓' : '🔒'; ?>
                                    </button>
                                </form>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="forum_action" value="pin_thread">
                                    <input type="hidden" name="thread_id" value="<?php echo (int)$t->id; ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary" title="<?php echo $t->type === 'sticky' ? 'Entpinnen' : 'Pinnen'; ?>">📌</button>
                                </form>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="forum_action" value="delete_thread">
                                    <input type="hidden" name="thread_id" value="<?php echo (int)$t->id; ?>">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="openDeleteConfirm(this.closest('form'), 'Thread wirklich löschen?')">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginierung -->
        <?php if ($pages > 1): ?>
        <div class="pagination forum-inline-actions" style="justify-content:center;margin-top:1.5rem;">
            <?php if ($page > 1): ?>
                <a href="?page=forum-threads&page=<?php echo $page - 1; ?>" class="btn btn-secondary btn-sm">← Zurück</a>
            <?php endif; ?>
            <span style="padding:.375rem .875rem;color:#64748b;font-size:.875rem;">Seite <?php echo $page; ?> von <?php echo $pages; ?></span>
            <?php if ($page < $pages): ?>
                <a href="?page=forum-threads&page=<?php echo $page + 1; ?>" class="btn btn-secondary btn-sm">Weiter →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

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

<script>
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
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
window.addEventListener('click', function(e) {
    document.querySelectorAll('.modal').forEach(function(m) { if (e.target === m) closeModal(m.id); });
});
</script>

</div>
