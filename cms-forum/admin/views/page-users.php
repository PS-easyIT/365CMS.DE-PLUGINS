<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<?php
$bannedUsers = count(array_filter($users, static fn($user) => !empty($user->is_banned)));
?>

<div class="forum-admin-shell">

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>👥 Forum-Benutzer</h2>
        <p>Benutzer verwalten und sperren</p>
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
        <span class="forum-info-card__text">Benutzer passend zur aktuellen Suche.</span>
    </div>
    <div class="forum-info-card">
        <span class="forum-info-card__eyebrow">Gesperrt</span>
        <span class="forum-info-card__value"><?php echo number_format($bannedUsers); ?></span>
        <span class="forum-info-card__text">In der aktuellen Liste gebannte Accounts.</span>
    </div>
</div>

<!-- Suche -->
<div class="forum-filter-card">
    <form method="GET" class="forum-filter-row">
        <input type="hidden" name="page" value="forum-users">
        <div class="form-group" style="margin:0;flex:1;">
            <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Benutzername suchen...">
        </div>
        <button type="submit" class="btn btn-secondary btn-sm">🔍 Suchen</button>
    </form>
</div>

<!-- Benutzer-Tabelle -->
<div class="admin-card">
    <div class="forum-panel-header">
        <div>
            <h3>👥 Benutzer (<?php echo $total; ?>)</h3>
            <p>Benutzerstatus, Rang und Aktivität zentral im Blick behalten.</p>
        </div>
    </div>
    <?php if (empty($users)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0;">📭</p>
            <p><strong>Keine Forum-Benutzer gefunden</strong></p>
        </div>
    <?php else: ?>
        <div class="users-table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Benutzer</th>
                        <th>E-Mail</th>
                        <th>Rang</th>
                        <th>Beiträge</th>
                        <th>Threads</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($u->username); ?></strong></td>
                        <td><span class="forum-muted-text"><?php echo htmlspecialchars($u->email ?? ''); ?></span></td>
                        <td>
                            <?php if (!empty($u->rank_title)): ?>
                                <span class="status-badge" style="background:#dbeafe;color:#1e40af;"><?php echo htmlspecialchars($u->rank_title); ?></span>
                            <?php else: ?>
                                <span style="color:#94a3b8;">–</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo (int)$u->post_count; ?></td>
                        <td><?php echo (int)$u->thread_count; ?></td>
                        <td>
                            <?php if ($u->is_banned): ?>
                                <span class="status-badge" style="background:#fee2e2;color:#991b1b;">Gesperrt</span>
                            <?php else: ?>
                                <span class="status-badge active">Aktiv</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u->is_banned): ?>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="forum_action" value="unban_user">
                                    <input type="hidden" name="user_id" value="<?php echo (int)$u->user_id; ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary">🔓 Entsperren</button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-sm btn-danger" onclick="openBanModal(<?php echo (int)$u->user_id; ?>, '<?php echo htmlspecialchars($u->username, ENT_QUOTES); ?>')">🔒 Sperren</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
        <div class="pagination forum-inline-actions" style="justify-content:center;margin-top:1.5rem;">
            <?php if ($page > 1): ?>
                <a href="?page=forum-users&page=<?php echo $page - 1; ?>&q=<?php echo urlencode($search); ?>" class="btn btn-secondary btn-sm">← Zurück</a>
            <?php endif; ?>
            <span style="padding:.375rem .875rem;color:#64748b;font-size:.875rem;">Seite <?php echo $page; ?> von <?php echo $pages; ?></span>
            <?php if ($page < $pages): ?>
                <a href="?page=forum-users&page=<?php echo $page + 1; ?>&q=<?php echo urlencode($search); ?>" class="btn btn-secondary btn-sm">Weiter →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Ban Modal -->
<div id="banModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Benutzer sperren</h3>
            <button class="modal-close" onclick="closeModal('banModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="forum_action" value="ban_user">
                <input type="hidden" name="user_id" id="ban-user-id">
                <p>Benutzer <strong id="ban-username"></strong> sperren:</p>
                <div class="form-group">
                    <label class="form-label">Begründung</label>
                    <textarea name="ban_reason" class="form-control" rows="3" placeholder="Grund der Sperrung..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Ablaufdatum (leer = permanent)</label>
                    <input type="datetime-local" name="ban_expires" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('banModal')">Abbrechen</button>
                <button type="submit" class="btn btn-danger">🔒 Sperren</button>
            </div>
        </form>
    </div>
</div>

<script>
function openBanModal(userId, username) {
    document.getElementById('ban-user-id').value = userId;
    document.getElementById('ban-username').textContent = username;
    openModal('banModal');
}
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
window.addEventListener('click', function(e) {
    document.querySelectorAll('.modal').forEach(function(m) { if (e.target === m) closeModal(m.id); });
});
</script>

</div>
