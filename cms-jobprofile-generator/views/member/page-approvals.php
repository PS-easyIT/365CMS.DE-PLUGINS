<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
/**
 * Member-View: Genehmigungen (Approver-Ansicht)
 *
 * @var array<object> $pendingProfiles   Ausstehende Profile (gefiltert nach Benutzerrolle)
 * @var array<object> $wfSteps           Konfigurierte Workflow-Stufen
 * @var string        $csrf              CSRF-Token (Action: member_approvals)
 * @var string        $notice
 * @var string        $error
 * @var string        $baseUrl
 */
$esc = fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
?>

<!-- Page-Header -->
<div style="margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.75rem;">
    <div>
        <h2 style="margin:0 0 .25rem;font-size:1.375rem;font-weight:700;color:#1e293b;">✅ Genehmigungen</h2>
        <p style="margin:0;color:#64748b;font-size:.875rem;">Ausstehende Stellenanzeigen prüfen und entscheiden.</p>
    </div>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-success" style="margin-bottom:1rem;">✅ <?php echo $esc($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error" style="margin-bottom:1rem;">❌ <?php echo $esc($error); ?></div>
<?php endif; ?>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;">
    <div class="admin-card" style="margin-bottom:0;text-align:center;padding:1.25rem;">
        <div style="font-size:2rem;font-weight:700;color:#3b82f6;"><?php echo count($pendingProfiles); ?></div>
        <div style="font-size:.85rem;color:#64748b;">Auf mich wartend</div>
    </div>
    <div class="admin-card" style="margin-bottom:0;text-align:center;padding:1.25rem;">
        <div style="font-size:2rem;font-weight:700;color:#10b981;"><?php echo count($wfSteps); ?></div>
        <div style="font-size:.85rem;color:#64748b;">Workflow-Stufen</div>
    </div>
</div>

<!-- Tabelle: Ausstehende Genehmigungen -->
<div class="admin-card">
    <h3>⏳ Ausstehende Genehmigungen</h3>

    <?php if (empty($pendingProfiles)): ?>
    <div class="empty-state" style="padding:2rem 0;text-align:center;">
        <p style="font-size:2.5rem;margin:0;">🎉</p>
        <p><strong>Keine ausstehenden Genehmigungen</strong></p>
        <p style="color:#64748b;font-size:.875rem;">Alle für dich relevanten Stellenanzeigen wurden bereits bearbeitet.</p>
    </div>
    <?php else: ?>
    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Stellentitel</th>
                    <th>Eingereicht von</th>
                    <th>Aktuelle Stufe</th>
                    <th>Workflow-Fortschritt</th>
                    <th>Eingereicht am</th>
                    <th style="min-width:180px;text-align:center;">Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pendingProfiles as $p):
                $wfStep = (int)($p->workflow_step ?? 1);
                $stepCount = count($wfSteps);
                $submittedAt = !empty($p->workflow_submitted_at)
                    ? date('d.m.Y H:i', strtotime($p->workflow_submitted_at))
                    : date('d.m.Y', strtotime($p->created_at ?? 'now'));
                // Genehmiger-Rolle der aktuellen Stufe
                $curStep    = null;
                foreach ($wfSteps as $ws) {
                    if ((int)$ws->sort_order === $wfStep) { $curStep = $ws; break; }
                }
                $roleLabel = !empty($curStep->approver_role) ? $esc($curStep->approver_role) : '–';
                $stepName  = !empty($curStep->step_name)     ? $esc($curStep->step_name)     : "Stufe {$wfStep}";
            ?>
            <tr>
                <td>
                    <div style="font-weight:600;color:#1e293b;"><?php echo $esc($p->title ?? '–'); ?></div>
                    <div style="font-size:.8rem;color:#94a3b8;">#<?php echo (int)$p->id; ?></div>
                </td>
                <td style="color:#475569;font-size:.875rem;"><?php echo $esc($p->author_name ?? $p->created_by_name ?? '–'); ?></td>
                <td>
                    <span class="status-badge inactive" style="font-size:.78rem;">
                        ⏳ <?php echo $stepName; ?>
                    </span>
                </td>
                <td>
                    <?php
                    // Dot-Progress-Visualisierung
                    echo '<div style="display:flex;align-items:center;gap:.2rem;">';
                    for ($s = 1; $s <= $stepCount; $s++) {
                        $done = $s < $wfStep;
                        $curr = $s === $wfStep;
                        $dotC = $done ? '#10b981' : ($curr ? '#3b82f6' : '#e2e8f0');
                        $dotS = $done ? '#fff'    : ($curr ? '#fff'    : '#94a3b8');
                        echo '<div title="Stufe ' . $s . '" style="width:1.4rem;height:1.4rem;border-radius:50%;'
                            . 'background:' . $dotC . ';display:flex;align-items:center;justify-content:center;'
                            . 'font-size:.65rem;font-weight:700;color:' . $dotS . ';">'
                            . ($done ? '✓' : $s) . '</div>';
                        if ($s < $stepCount) {
                            echo '<div style="width:.8rem;height:1px;background:#e2e8f0;"></div>';
                        }
                    }
                    echo '</div>';
                    ?>
                </td>
                <td style="color:#64748b;font-size:.875rem;"><?php echo $esc($submittedAt); ?></td>
                <td>
                    <div style="display:flex;gap:.4rem;justify-content:center;flex-wrap:wrap;">
                        <button type="button"
                                class="btn btn-sm btn-primary"
                                onclick="jpgMemberApprovalModal(<?php echo (int)$p->id; ?>, '<?php echo addslashes($p->title ?? ''); ?>', 'approve')">
                            ✅ Genehmigen
                        </button>
                        <button type="button"
                                class="btn btn-sm btn-danger"
                                onclick="jpgMemberApprovalModal(<?php echo (int)$p->id; ?>, '<?php echo addslashes($p->title ?? ''); ?>', 'reject')">
                            ❌ Ablehnen
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div><!-- /.users-table-container -->
    <?php endif; ?>
</div><!-- /.admin-card -->
<!-- ══ Status meiner eigenen Profile ═══════════════════════════════════════ -->
<?php if (!empty($ownerProfiles ?? [])): ?>
<div class="admin-card" style="margin-top:1.5rem;">
    <h3>📊 Status meiner Stellenanzeigen im Workflow</h3>
    <div class="users-table-container">
        <table class="users-table">
            <thead><tr>
                <th>Stellentitel</th>
                <th>Firma</th>
                <th>Workflow-Status</th>
                <th>Workflow-Stufe</th>
                <th>Erstellt</th>
                <th>Aktion</th>
            </tr></thead>
            <tbody>
            <?php
            $statusLabels = [
                'pending'  => ['⏳', 'In Prüfung',    '#fef3c7', '#92400e'],
                'approved' => ['✅', 'Genehmigt',     '#d1fae5', '#065f46'],
                'rejected' => ['❌', 'Abgelehnt',     '#fee2e2', '#991b1b'],
                'none'     => ['📝', 'Kein Workflow', '#f1f5f9', '#64748b'],
            ];
            foreach ($ownerProfiles ?? [] as $op):
                $wfStatus = (string)($op->workflow_status ?? 'none');
                [$icon, $label, $bg, $fg] = $statusLabels[$wfStatus] ?? ['❓', $wfStatus, '#f1f5f9', '#64748b'];
            ?>
            <tr>
                <td style="font-weight:600;">
                    <a href="<?php echo $esc($baseUrl . '?action=edit&id=' . (int)$op->id); ?>"
                       style="color:var(--admin-primary);">
                        <?php echo $esc($op->title ?? '—'); ?>
                    </a>
                </td>
                <td style="font-size:.82rem;color:#64748b;">
                    <?php echo $esc($op->company_name ?? '—'); ?>
                </td>
                <td>
                    <span style="display:inline-block;padding:.2rem .6rem;border-radius:20px;font-size:.78rem;font-weight:600;background:<?php echo $bg; ?>;color:<?php echo $fg; ?>;">
                        <?php echo $icon; ?> <?php echo $label; ?>
                    </span>
                </td>
                <td style="font-size:.82rem;color:#64748b;">
                    <?php echo $op->workflow_step > 0 ? ('Stufe ' . (int)$op->workflow_step) : '—'; ?>
                </td>
                <td style="font-size:.82rem;color:#64748b;">
                    <?php echo $esc(date('d.m.Y', strtotime($op->created_at ?? 'now'))); ?>
                </td>
                <td>
                    <a href="<?php echo $esc($baseUrl . '?action=edit&id=' . (int)$op->id); ?>"
                       class="btn btn-sm btn-secondary">✏️ Bearbeiten</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<!-- ══ MODAL: Genehmigen / Ablehnen ══════════════════════════════════════ -->
<div id="jpgMemberApprModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;
     display:none;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;width:100%;max-width:520px;
                padding:1.75rem;margin:1rem;box-shadow:0 8px 32px rgba(0,0,0,.18);">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.25rem;">
            <h3 id="jpgMembApprTitle" style="margin:0;font-size:1.1rem;color:#1e293b;">Entscheidung</h3>
            <button type="button" onclick="jpgCloseMemberApprModal()"
                    style="background:none;border:none;font-size:1.5rem;color:#94a3b8;cursor:pointer;line-height:1;">×</button>
        </div>
        <p id="jpgMembApprDesc" style="margin:0 0 1rem;color:#475569;font-size:.875rem;"></p>

        <form method="post" id="jpgMemberApprForm">
            <input type="hidden" name="_jpg_csrf"       value="<?php echo $esc($csrf); ?>">
            <input type="hidden" name="approval_action" id="jpgMembApprAction" value="">
            <input type="hidden" name="profile_id"      id="jpgMembApprProfileId" value="">

            <div class="form-group">
                <label class="form-label" for="jpgMembApprNote">
                    Begründung / Notiz
                    <span style="color:#64748b;font-size:.8rem;font-weight:400;">(optional, wird im Verlauf gespeichert)</span>
                </label>
                <textarea id="jpgMembApprNote" name="note" class="form-control"
                          style="min-height:90px;resize:vertical;"
                          placeholder="Kurze Begründung für deine Entscheidung…"></textarea>
            </div>

            <div style="display:flex;gap:.6rem;justify-content:flex-end;margin-top:1rem;flex-wrap:wrap;">
                <button type="button" class="btn btn-secondary" onclick="jpgCloseMemberApprModal()">Abbrechen</button>
                <button type="submit" id="jpgMembApprSubmit" class="btn btn-primary">Bestätigen</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('jpgMemberApprModal');

    window.jpgMemberApprovalModal = function (profileId, title, action) {
        document.getElementById('jpgMembApprProfileId').value = profileId;
        document.getElementById('jpgMembApprAction').value    = action;
        var isApprove = action === 'approve';
        document.getElementById('jpgMembApprTitle').textContent =
            isApprove ? '✅ Stellenanzeige genehmigen' : '❌ Stellenanzeige ablehnen';
        document.getElementById('jpgMembApprDesc').textContent  =
            (isApprove ? 'Genehmigung für: ' : 'Ablehnung für: ') + '"' + title + '"';
        var btn = document.getElementById('jpgMembApprSubmit');
        btn.className    = isApprove ? 'btn btn-primary' : 'btn btn-danger';
        btn.textContent  = isApprove ? '✅ Jetzt genehmigen' : '❌ Ablehnen bestätigen';
        document.getElementById('jpgMembApprNote').value = '';
        modal.style.display = 'flex';
    };

    window.jpgCloseMemberApprModal = function () {
        modal.style.display = 'none';
    };

    // Schließen per Klick außerhalb
    modal.addEventListener('click', function (e) {
        if (e.target === modal) jpgCloseMemberApprModal();
    });
})();
</script>
