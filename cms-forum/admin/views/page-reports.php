<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>🚩 Meldungen</h2>
        <p>Gemeldete Beiträge prüfen und bearbeiten</p>
    </div>
    <div class="header-actions">
        <a href="?page=forum-reports&status=open" class="btn btn-sm <?php echo $status === 'open' ? 'btn-primary' : 'btn-secondary'; ?>">Offen (<?php echo $openCount; ?>)</a>
        <a href="?page=forum-reports&status=resolved" class="btn btn-sm <?php echo $status === 'resolved' ? 'btn-primary' : 'btn-secondary'; ?>">Erledigt</a>
    </div>
</div>

<?php if (isset($success) && $success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if (isset($error) && $error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="admin-card">
    <h3>🚩 <?php echo $status === 'open' ? 'Offene' : 'Erledigte'; ?> Meldungen</h3>
    <?php if (empty($reports)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0;">✅</p>
            <p><strong>Keine <?php echo $status === 'open' ? 'offenen' : ''; ?> Meldungen</strong></p>
        </div>
    <?php else: ?>
        <?php foreach ($reports as $r): ?>
        <div style="border:1px solid #e2e8f0;border-radius:8px;padding:1.25rem;margin-bottom:1rem;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.75rem;">
                <div>
                    <span class="status-badge" style="background:#fee2e2;color:#991b1b;"><?php echo htmlspecialchars($r->reason); ?></span>
                    <span style="color:#64748b;font-size:.85rem;margin-left:.5rem;">
                        gemeldet von <strong><?php echo htmlspecialchars($r->reporter_name ?? 'Unbekannt'); ?></strong>
                        – <?php echo date('d.m.Y H:i', strtotime($r->created_at)); ?>
                    </span>
                </div>
                <?php if (!empty($r->thread_title)): ?>
                    <a href="<?php echo SITE_URL; ?>/forum/thread/<?php echo (int)$r->thread_id; ?>" class="btn btn-sm btn-secondary" target="_blank">📝 Zum Thread</a>
                <?php endif; ?>
            </div>

            <?php if (!empty($r->description)): ?>
                <p style="color:#475569;font-size:.9rem;margin:0 0 .75rem;"><em><?php echo htmlspecialchars($r->description); ?></em></p>
            <?php endif; ?>

            <?php if (!empty($r->post_content)): ?>
                <div style="background:#f8fafc;border-radius:6px;padding:1rem;margin-bottom:.75rem;font-size:.9rem;color:#1e293b;border-left:3px solid #e2e8f0;">
                    <?php echo htmlspecialchars(mb_substr($r->post_content, 0, 300)); ?>
                    <?php if (mb_strlen($r->post_content) > 300): ?>…<?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($r->status === 'open'): ?>
            <div style="display:flex;gap:.5rem;">
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="forum_action" value="resolve_report">
                    <input type="hidden" name="report_id" value="<?php echo (int)$r->id; ?>">
                    <input type="hidden" name="resolution" value="dismissed">
                    <button type="submit" class="btn btn-sm btn-secondary">✅ Abweisen</button>
                </form>
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="forum_action" value="delete_reported_post">
                    <input type="hidden" name="report_id" value="<?php echo (int)$r->id; ?>">
                    <input type="hidden" name="post_id" value="<?php echo (int)$r->post_id; ?>">
                    <button type="button" class="btn btn-sm btn-danger" onclick="openDeleteConfirm(this.closest('form'), 'Beitrag wirklich löschen?')">🗑️ Beitrag löschen</button>
                </form>
            </div>
            <?php else: ?>
                <p style="color:#64748b;font-size:.85rem;margin:0;">
                    Erledigt von <strong><?php echo htmlspecialchars($r->resolved_by_name ?? '–'); ?></strong>
                    <?php if (!empty($r->handled_at)): ?> am <?php echo date('d.m.Y H:i', strtotime($r->handled_at)); ?><?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
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
