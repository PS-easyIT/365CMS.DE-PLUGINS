<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<div class="forum-admin-shell">

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>🔒 Berechtigungen</h2>
        <p>Zugriffsrechte pro Forum und Benutzergruppe verwalten</p>
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
        <span class="forum-info-card__eyebrow">Foren</span>
        <span class="forum-info-card__value"><?php echo number_format(count($forums)); ?></span>
        <span class="forum-info-card__text">Für diese Foren werden Gruppenrechte gepflegt.</span>
    </div>
    <div class="forum-info-card">
        <span class="forum-info-card__eyebrow">Gruppen</span>
        <span class="forum-info-card__value"><?php echo number_format(count($groupTypes)); ?></span>
        <span class="forum-info-card__text">Gast, Mitglied und Moderator im Berechtigungsmatrix-Setup.</span>
    </div>
</div>

<?php if (empty($forums)): ?>
<div class="admin-card">
    <div class="empty-state">
        <p style="font-size:2.5rem;margin:0;">📭</p>
        <p><strong>Erstelle zuerst Foren</strong></p>
    </div>
</div>
<?php else: ?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <input type="hidden" name="forum_action" value="save_permissions">

    <?php
    $flagLabels = [
        'can_read'       => '👁️ Lesen',
        'can_post'       => '✏️ Antworten',
        'can_create'     => '➕ Thread erstellen',
        'can_edit_own'   => '📝 Eigene bearbeiten',
        'can_delete_own' => '🗑️ Eigene löschen',
        'can_upload'     => '📎 Dateien anhängen',
        'can_vote'       => '🗳️ Abstimmen',
        'can_moderate'   => '🛡️ Moderieren',
    ];
    $groupLabels = ['guest' => '🌐 Gast', 'member' => '👤 Mitglied', 'moderator' => '🛡️ Moderator'];
    ?>

    <?php foreach ($forums as $forum): ?>
    <div class="admin-card">
        <h3>📁 <?php echo htmlspecialchars($forum->name); ?></h3>
        <div class="users-table-container">
            <table class="users-table forum-permissions-matrix">
                <thead>
                    <tr>
                        <th>Berechtigung</th>
                        <?php foreach ($groupLabels as $gKey => $gLabel): ?>
                            <th style="text-align:center;"><?php echo $gLabel; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($flagLabels as $flag => $label): ?>
                    <tr>
                        <td><?php echo $label; ?></td>
                        <?php foreach ($groupTypes as $group): ?>
                        <td style="text-align:center;">
                            <?php
                            $checked = false;
                            $perm = $currentPerms[(int)$forum->id][$group] ?? null;
                            if ($perm && isset($perm->{$flag})) {
                                $checked = (bool) $perm->{$flag};
                            }
                            ?>
                            <input type="checkbox"
                                   name="permissions[<?php echo (int)$forum->id; ?>][<?php echo $group; ?>][<?php echo $flag; ?>]"
                                   value="1"
                                   <?php echo $checked ? 'checked' : ''; ?>>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="admin-card form-actions-card">
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Berechtigungen speichern</button>
            <span class="form-actions__hint">Änderungen gelten sofort für alle Benutzer</span>
        </div>
    </div>
</form>
<?php endif; ?>

</div>
