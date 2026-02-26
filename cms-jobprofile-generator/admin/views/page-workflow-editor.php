<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * Admin-View: Workflow-Editor
 *
 * @var array<object> $steps    Alle Workflow-Schritte
 * @var array<string> $allRoles [name => display_name] alle CMS-Rollen
 * @var string        $notice
 * @var string        $error
 */

$nonce = CMS_JPG_Admin_Pages::nonce('jpg_workflow_save');
$esc   = fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
?>

<div class="admin-page-header">
    <div>
        <h2>🔄 Workflow-Editor</h2>
        <p>Konfiguriere den n-stufigen Genehmigungsprozess für Stellenanzeigen. Rollen werden direkt aus der Datenbank geladen.</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="openModal('modalAddStep')">➕ Neuen Schritt</button>
    </div>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo $esc($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo $esc($error); ?></div>
<?php endif; ?>

<!-- ══════════════ WORKFLOW-SCHRITTE ══════════════════════════════════════════ -->
<div class="admin-card">
    <h3>🔢 Definierte Workflow-Stufen</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem;">
        Stufen werden der Reihe nach (sort_order aufsteigend) durchlaufen. Erst nach der letzten Freigabe wird die Stelle veröffentlicht. Sind keine Stufen aktiv, liegt die Freigabe direkt beim Admin.
    </p>

    <?php if (empty($steps)): ?>
    <div class="empty-state">
        <p style="font-size:2rem;margin:0;">🔧</p>
        <p><strong>Noch keine Workflow-Stufen konfiguriert</strong></p>
        <p style="color:#64748b;font-size:.875rem;">Erstelle mindestens eine Stufe, um den Genehmigungsprozess zu aktivieren.</p>
        <button class="btn btn-primary" style="margin-top:1rem;" onclick="openModal('modalAddStep')">➕ Erste Stufe anlegen</button>
    </div>
    <?php else: ?>
    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th style="width:3rem;">Reihenf.</th>
                    <th>Stufenname</th>
                    <th>Genehmiger-Rolle</th>
                    <th style="text-align:center;">Selbst-Genehmigung</th>
                    <th style="text-align:center;">Status</th>
                    <th style="text-align:center;">Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($steps as $step): ?>
            <tr>
                <td style="font-weight:700;color:#64748b;"><?php echo (int)$step->sort_order; ?></td>
                <td>
                    <strong><?php echo $esc($step->step_name); ?></strong>
                    <?php if (!empty($step->notification_email)): ?>
                    <div style="font-size:.78rem;color:#94a3b8;">📧 <?php echo $esc($step->notification_email); ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="role-badge admin">
                        <?php echo $esc($allRoles[$step->approver_role] ?? $step->approver_role); ?>
                    </span>
                    <div style="font-size:.78rem;color:#94a3b8;"><?php echo $esc($step->approver_role); ?></div>
                </td>
                <td style="text-align:center;">
                    <?php echo (int)$step->allow_self_approve ? '✅ Ja' : '🔒 Nein'; ?>
                </td>
                <td style="text-align:center;">
                    <span class="status-badge <?php echo (int)$step->active ? 'active' : 'inactive'; ?>">
                        <?php echo (int)$step->active ? 'Aktiv' : 'Inaktiv'; ?>
                    </span>
                </td>
                <td style="text-align:center;">
                    <div style="display:flex;gap:.4rem;justify-content:center;">
                        <button class="btn btn-sm btn-secondary"
                                onclick="openEditStep(<?php echo (int)$step->id; ?>, <?php echo htmlspecialchars(json_encode([
                                    'step_name' => $step->step_name,
                                    'approver_role' => $step->approver_role,
                                    'allow_self_approve' => (int)$step->allow_self_approve,
                                    'notification_email' => $step->notification_email ?? '',
                                    'sort_order' => (int)$step->sort_order,
                                    'active' => (int)$step->active,
                                ]), ENT_QUOTES); ?>)">✏️</button>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="_jpg_nonce"   value="<?php echo $esc($nonce); ?>">
                            <input type="hidden" name="_wf_action"   value="delete_step">
                            <input type="hidden" name="step_id"      value="<?php echo (int)$step->id; ?>">
                            <button type="submit" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Schritt wirklich löschen?')">🗑️</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="alert" style="background:#dbeafe;color:#1e40af;border-left:4px solid #3b82f6;margin-top:1rem;">
        ℹ️ <strong>Ablauf:</strong> Nach Einreichen durchläuft die Stelle Stufe 1 → 2 → … Nach der letzten Genehmigung wird sie automatisch veröffentlicht.
    </div>
    <?php endif; ?>
</div>

<!-- ══════════════ HINWEIS: GENEHMIGUNGEN AUSGELAGERT ════════════════════════ -->
<div class="admin-card">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <h3 style="margin-bottom:.3rem;">✅ Ausstehende Genehmigungen</h3>
            <p style="color:#64748b;font-size:.875rem;margin:0;">
                Genehmigungsentscheidungen stehen jetzt im eigenen Bereich zur Verfügung.
            </p>
        </div>
        <a href="/admin/plugins/jpg-dashboard/jpg-approvals" class="btn btn-primary">
            ✅ Zu den Genehmigungen
        </a>
    </div>
</div>

<!-- ══════════════ INFO-BOX: ROLLEN-ÜBERSICHT ════════════════════════════════ -->
<div class="admin-card">
    <h3>🔐 Verfügbare Rollen (aus Datenbank)</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">
        Diese Rollen wurden direkt aus <code>cms_roles</code> geladen und stehen als Genehmiger zur Verfügung.
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

<!-- ════════════ MODAL: SCHRITT ANLEGEN / BEARBEITEN ═════════════════════════ -->
<div id="modalAddStep" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:550px;">
        <div class="modal-header">
            <h3 id="modalStepTitle">➕ Neue Stufe anlegen</h3>
            <button class="modal-close" onclick="closeModal('modalAddStep')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formAddStep" method="post" class="admin-form">
                <input type="hidden" name="_jpg_nonce"  value="<?php echo $esc($nonce); ?>">
                <input type="hidden" name="_wf_action"  value="save_step">
                <input type="hidden" name="step_id"     id="editStepId"  value="0">

                <div class="form-group">
                    <label class="form-label" for="step_name">
                        Stufenname <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="text" id="step_name" name="step_name" class="form-control"
                           placeholder="z. B. Teamleiter-Freigabe" required>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label" for="approver_role">Genehmiger-Rolle</label>
                        <select id="approver_role" name="approver_role" class="form-control">
                            <?php foreach ($allRoles as $roleSlug => $roleLabel): ?>
                            <option value="<?php echo $esc($roleSlug); ?>">
                                <?php echo $esc($roleLabel); ?> (<?php echo $esc($roleSlug); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text">Alle Rollen aus der Datenbank</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="sort_order">Reihenfolge</label>
                        <input type="number" id="sort_order" name="sort_order"
                               class="form-control" value="10" min="1" max="999" step="10">
                        <small class="form-text">Niedrigere Zahl = früher im Prozess</small>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="notification_email">Benachrichtigungs-E-Mail (optional)</label>
                    <input type="email" id="notification_email" name="notification_email"
                           class="form-control" placeholder="approver@beispiel.de">
                    <small class="form-text">E-Mail bei neuen Eingaben in dieser Stufe</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label" style="display:flex;align-items:center;gap:.75rem;cursor:pointer;padding:.5rem;border-radius:6px;">
                        <input type="checkbox" name="allow_self_approve" id="allow_self_approve" value="1">
                        <div>
                            <div style="font-weight:500;">Selbst-Genehmigung erlauben</div>
                            <div style="font-size:.82rem;color:#64748b;">Member kann diesen Schritt selbst abschließen (für einfache interne Freigaben)</div>
                        </div>
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label" style="display:flex;align-items:center;gap:.75rem;cursor:pointer;padding:.5rem;border-radius:6px;">
                        <input type="checkbox" name="active" id="step_active" value="1" checked>
                        <div>
                            <div style="font-weight:500;">Schritt aktiv</div>
                            <div style="font-size:.82rem;color:#64748b;">Inaktive Schritte werden im Workflow übersprungen</div>
                        </div>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modalAddStep')">Abbrechen</button>
            <button type="submit" form="formAddStep" class="btn btn-primary">💾 Speichern</button>
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

function openEditStep(id, data) {
    document.getElementById('modalStepTitle').textContent = '✏️ Schritt bearbeiten';
    document.getElementById('editStepId').value       = id;
    document.getElementById('step_name').value         = data.step_name || '';
    document.getElementById('approver_role').value     = data.approver_role || 'admin';
    document.getElementById('notification_email').value = data.notification_email || '';
    document.getElementById('sort_order').value         = data.sort_order || 10;
    document.getElementById('allow_self_approve').checked = data.allow_self_approve == 1;
    document.getElementById('step_active').checked     = data.active == 1;
    openModal('modalAddStep');
}

</script>
