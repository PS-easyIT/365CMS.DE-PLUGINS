<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * Admin-View: Genehmigungen
 *
 * @var array<object> $pendingProfiles  Ausstehende Profile
 * @var array<string> $allRoles         [name => display_name] alle CMS-Rollen
 * @var array<object> $wfSteps          Konfigurierte Workflow-Stufen
 * @var string        $notice
 * @var string        $error
 */

$nonce = CMS_JPG_Admin_Pages::nonce('jpg_workflow_save');
$esc   = fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
?>

<!-- ══ PAGE HEADER ══════════════════════════════════════════════════════════ -->
<div class="admin-page-header">
    <div>
        <h2>✅ Genehmigungen</h2>
        <p>Ausstehende Stellenanzeigen prüfen und Entscheidungen treffen.</p>
    </div>
    <div class="header-actions">
        <?php if (count($wfSteps) === 0): ?>
        <a href="/admin/plugins/jpg-dashboard/jpg-workflow" class="btn btn-secondary">
            ⚙️ Workflow konfigurieren
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo $esc($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo $esc($error); ?></div>
<?php endif; ?>

<!-- ══ STATS ZEILE ══════════════════════════════════════════════════════════ -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem;">
    <div class="admin-card" style="margin-bottom:0;text-align:center;padding:1.25rem;">
        <div style="font-size:2rem;font-weight:700;color:var(--admin-primary);"><?php echo count($pendingProfiles); ?></div>
        <div style="font-size:.85rem;color:#64748b;">Ausstehend</div>
    </div>
    <div class="admin-card" style="margin-bottom:0;text-align:center;padding:1.25rem;">
        <div style="font-size:2rem;font-weight:700;color:#10b981;"><?php echo count($wfSteps); ?></div>
        <div style="font-size:.85rem;color:#64748b;">Workflow-Stufen</div>
    </div>
    <div class="admin-card" style="margin-bottom:0;text-align:center;padding:1.25rem;">
        <div style="font-size:2rem;font-weight:700;color:#f59e0b;"><?php echo count($allRoles); ?></div>
        <div style="font-size:.85rem;color:#64748b;">Genehmiger-Rollen</div>
    </div>
</div>

<!-- ══ PENDING TABLE ════════════════════════════════════════════════════════ -->
<div class="admin-card">
    <h3>⏳ Ausstehende Genehmigungen</h3>

    <?php if (empty($pendingProfiles)): ?>
    <div class="empty-state">
        <p style="font-size:2.5rem;margin:0;">🎉</p>
        <p><strong>Keine ausstehenden Genehmigungen</strong></p>
        <p style="color:#64748b;font-size:.875rem;">Alle eingereichten Stellenanzeigen wurden bearbeitet.</p>
    </div>
    <?php else: ?>
    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Stellentitel</th>
                    <th>Erstellt von</th>
                    <th>Aktuelle Stufe</th>
                    <th>Genehmiger-Rolle</th>
                    <th>Workflow-Fortschritt</th>
                    <th>Eingereicht am</th>
                    <th style="text-align:center;">Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pendingProfiles as $profile):
                $stepNr     = (int)($profile->workflow_step ?? 0);
                $totalSteps = count($wfSteps);
                $roleName   = $allRoles[$profile->approver_role ?? ''] ?? ($profile->approver_role ?? '–');
            ?>
            <tr>
                <td>
                    <strong><?php echo $esc($profile->title); ?></strong>
                    <div style="font-size:.775rem;color:#94a3b8;margin-top:.15rem;">#<?php echo (int)$profile->id; ?></div>
                </td>
                <td style="color:#64748b;"><?php echo $esc($profile->author_name ?? '–'); ?></td>
                <td>
                    <?php if (!empty($profile->step_name)): ?>
                    <span class="status-badge inactive"><?php echo $esc($profile->step_name); ?></span>
                    <?php else: ?>
                    <span style="color:#94a3b8;font-size:.875rem;">Kein Schritt</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="role-badge member"><?php echo $esc($roleName); ?></span>
                </td>
                <td style="min-width:130px;">
                    <?php if ($totalSteps > 0 && $stepNr > 0): ?>
                    <div style="display:flex;align-items:center;gap:.3rem;">
                        <?php for ($i = 1; $i <= $totalSteps; $i++):
                            $isDone    = $i < $stepNr;
                            $isCurrent = $i === $stepNr;
                            $dotBg     = $isDone ? '#10b981' : ($isCurrent ? '#3b82f6' : '#e2e8f0');
                        ?>
                        <div style="width:1.4rem;height:1.4rem;border-radius:50%;background:<?php echo $dotBg; ?>;
                                    display:flex;align-items:center;justify-content:center;
                                    font-size:.65rem;font-weight:700;color:#fff;">
                            <?php echo $isDone ? '✓' : $i; ?>
                        </div>
                        <?php if ($i < $totalSteps): ?>
                        <div style="width:.75rem;height:2px;background:<?php echo $isDone ? '#10b981' : '#e2e8f0'; ?>;"></div>
                        <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <div style="font-size:.75rem;color:#64748b;margin-top:.25rem;">Schritt <?php echo $stepNr; ?> / <?php echo $totalSteps; ?></div>
                    <?php else: ?>
                    <span style="color:#94a3b8;font-size:.875rem;">–</span>
                    <?php endif; ?>
                </td>
                <td style="color:#64748b;font-size:.875rem;white-space:nowrap;">
                    <?php echo $esc(date('d.m.Y H:i', strtotime($profile->created_at))); ?>
                </td>
                <td>
                    <div style="display:flex;gap:.4rem;justify-content:center;flex-wrap:wrap;">
                        <button class="btn btn-sm btn-primary"
                                onclick="openApproveModal(<?php echo (int)$profile->id; ?>, '<?php echo $esc($profile->title); ?>', 'approve')">
                            ✅ Genehmigen
                        </button>
                        <button class="btn btn-sm btn-danger"
                                onclick="openApproveModal(<?php echo (int)$profile->id; ?>, '<?php echo $esc($profile->title); ?>', 'reject')">
                            ❌ Ablehnen
                        </button>
                        <button class="btn btn-sm btn-secondary"
                                onclick="openApproveModal(<?php echo (int)$profile->id; ?>, '<?php echo $esc($profile->title); ?>', 'reset')">
                            🔄 Zurücksetzen
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ══ ROLLEN-ÜBERSICHT ══════════════════════════════════════════════════════ -->
<div class="admin-card">
    <h3>🔐 Aktive Genehmiger-Rollen</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">
        Rollen, die als Genehmiger in den konfigurierten Workflow-Stufen hinterlegt sind.
    </p>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
        <?php foreach ($allRoles as $roleSlug => $roleLabel): ?>
        <span class="role-badge <?php echo $roleSlug === 'admin' ? 'admin' : 'member'; ?>">
            <?php echo $esc($roleLabel); ?>
            <small style="opacity:.7;margin-left:.3rem;">(<?php echo $esc($roleSlug); ?>)</small>
        </span>
        <?php endforeach; ?>
    </div>
</div>

<!-- ════════════ MODAL: GENEHMIGEN / ABLEHNEN / ZURÜCKSETZEN ════════════════ -->
<div id="modalApprove" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3 id="approveModalTitle">Entscheidung</h3>
            <button class="modal-close" onclick="closeModal('modalApprove')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formApprove" method="post" class="admin-form">
                <input type="hidden" name="_jpg_nonce"  value="<?php echo $esc($nonce); ?>">
                <input type="hidden" name="_wf_action"  id="approveAction"    value="">
                <input type="hidden" name="profile_id"  id="approveProfileId" value="">

                <div class="form-group">
                    <label class="form-label" for="approval_note">Notiz / Begründung (optional)</label>
                    <textarea id="approval_note" name="note" class="form-control"
                              style="min-height:80px;"
                              placeholder="Optionale Erläuterung zur Entscheidung…"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modalApprove')">Abbrechen</button>
            <button type="submit" form="formApprove" id="approveSubmitBtn" class="btn btn-primary">Bestätigen</button>
        </div>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}
window.addEventListener('click', function(e) {
    document.querySelectorAll('.modal').forEach(function(m) {
        if (e.target === m) closeModal(m.id);
    });
});

function openApproveModal(profileId, title, action) {
    var labels = {
        approve: { title: '✅ Genehmigen', btn: 'Jetzt genehmigen', btnClass: 'btn-primary' },
        reject:  { title: '❌ Ablehnen',   btn: 'Ablehnen',        btnClass: 'btn-danger'  },
        reset:   { title: '🔄 Zurücksetzen', btn: 'Zurücksetzen',   btnClass: 'btn-secondary' },
    };
    var cfg = labels[action] || labels.approve;

    document.getElementById('approveModalTitle').textContent = cfg.title + ': ' + title;
    document.getElementById('approveAction').value    = action + '_profile';
    document.getElementById('approveProfileId').value = profileId;
    document.getElementById('approval_note').value    = '';

    var btn = document.getElementById('approveSubmitBtn');
    btn.textContent = cfg.btn;
    btn.className   = 'btn ' + cfg.btnClass;

    openModal('modalApprove');
}
</script>
