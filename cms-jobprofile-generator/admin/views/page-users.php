<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * Admin-View: Benutzer & Mandanten-Verwaltung
 *
 * @var array  $users               CMS-Benutzer mit Plugin-Daten
 * @var array  $userMeta            [user_id => jpg_plugin_role]
 * @var array  $unassignedCompanies Unzugewiesene Firmen
 * @var string $nonce               CSRF-Nonce
 * @var string $notice
 * @var string $error
 */

$esc = fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);

$roleLabels = [
    ''        => ['label' => 'Standard',  'badge' => 'inactive'],
    'mandant' => ['label' => 'Mandant',   'badge' => 'member'],
    'admin'   => ['label' => 'Plugin-Admin','badge' => 'admin'],
    'blocked' => ['label' => 'Gesperrt',  'badge' => 'danger'],
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benutzer & Mandanten – <?php echo $esc(defined('SITE_NAME') ? SITE_NAME : 'CMS'); ?></title>
    <link rel="stylesheet" href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/assets/css/admin.css?v=20260222b">
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">

    <?php renderAdminSidebar('jpg-users'); ?>

    <div class="admin-content">

        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h2>👥 Benutzer & Mandanten</h2>
                <p>Weisen Sie CMS-Benutzern Plugin-Rollen und Unternehmen zu.</p>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($notice)): ?>
            <div class="alert alert-success">✅ <?php echo $esc($notice); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">❌ <?php echo $esc($error); ?></div>
        <?php endif; ?>

        <!-- Info-Card -->
        <div class="admin-card" style="background:#eff6ff;border-color:#bfdbfe;margin-bottom:1.5rem;">
            <p style="margin:0;color:#1e40af;font-size:.9rem;">
                ℹ️ <strong>Mandant</strong> = Unternehmen mit eigenem Plugin-Bereich im Member-Dashboard.
                <strong>Plugin-Admin</strong> = Vollzugriff auch auf Admin-Funktionen.
                <strong>Gesperrt</strong> = Kein Zugriff auf Plugin-Bereiche.
            </p>
        </div>

        <!-- Benutzertabelle -->
        <div class="admin-card">
            <h3>👤 Alle Benutzer (<?php echo count($users); ?>)</h3>

            <?php if (empty($users)): ?>
            <div class="empty-state">
                <p style="font-size:2rem;margin:0;">👤</p>
                <p><strong>Keine Benutzer gefunden</strong></p>
            </div>
            <?php else: ?>
            <div class="users-table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Benutzer</th>
                            <th>CMS-Rolle</th>
                            <th>Plugin-Rolle</th>
                            <th>Unternehmen</th>
                            <th>Stellen</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user):
                            $role     = $userMeta[(int)$user->id] ?? '';
                            $roleMeta = $roleLabels[$role] ?? $roleLabels[''];
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo $esc($user->display_name ?: $user->username); ?></strong><br>
                                <span style="color:#64748b;font-size:.8rem;"><?php echo $esc($user->email); ?></span>
                            </td>
                            <td>
                                <span class="role-badge <?php echo $esc($user->role ?? 'member'); ?>">
                                    <?php echo $esc(ucfirst($user->role ?? 'member')); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $esc($roleMeta['badge']); ?>">
                                    <?php echo $esc($roleMeta['label']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($user->company_name)): ?>
                                    <span>🏢 <?php echo $esc($user->company_name); ?></span>
                                <?php else: ?>
                                    <span style="color:#94a3b8;font-size:.85rem;">— keine —</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <?php echo (int)($user->job_count ?? 0); ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-secondary"
                                        onclick="jpgOpenUserModal(<?php echo (int)$user->id; ?>,
                                            '<?php echo $esc($user->display_name ?: $user->username); ?>',
                                            '<?php echo $esc($role); ?>',
                                            <?php echo (int)($user->company_id ?? 0); ?>)">
                                    ✏️ Bearbeiten
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- ══ Plugin-Rollen Übersicht ═══════════════════════════════════════ -->
        <?php
        $pluginRoles = $pluginRoles ?? (class_exists('CMS_JPG_Admin_Pages')
            ? CMS_JPG_Admin_Pages::get_plugin_roles() : []);
        ?>
        <?php if (!empty($pluginRoles)): ?>
        <div class="admin-card" style="margin-top:1rem;">
            <h3>🏷️ Plugin-Rollen</h3>
            <p style="color:#64748b;font-size:.85rem;margin-bottom:1.25rem;">
                Diese 5 Rollen sind <strong>unabhängig vom CMS-System</strong> und steuern den Zugriff auf Plugin-Bereiche.
                Vollständige Konfiguration unter
                <a href="?page=jpg-subscription" style="color:#3b82f6;">📦 Abosystem</a>.
            </p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem;">
                <?php foreach ($pluginRoles as $roleKey => $roleData): ?>
                <div style="border:1px solid #e2e8f0;border-radius:8px;padding:1rem;background:#fff;">
                    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;">
                        <span style="font-size:1.35rem;"><?php echo $roleData['icon']; ?></span>
                        <span class="status-badge <?php echo $esc($roleData['color']); ?>">
                            <?php echo $esc($roleData['label']); ?>
                        </span>
                        <code style="font-size:.7rem;color:#94a3b8;"><?php echo $esc($roleKey); ?></code>
                    </div>
                    <p style="font-size:.8rem;color:#64748b;margin:0 0 .4rem;">
                        <?php echo $esc($roleData['description']); ?>
                    </p>
                    <?php if (!empty($roleData['caps'])): ?>
                    <div style="display:flex;flex-wrap:wrap;gap:.25rem;">
                        <?php foreach ($roleData['caps'] as $cap): ?>
                        <span style="background:#eff6ff;color:#1e40af;font-size:.68rem;padding:.15rem .35rem;border-radius:3px;font-family:monospace;">
                            <?php echo $esc($cap); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.admin-content -->

    <!-- ══ Benutzer-Bearbeitungs-Modal ══════════════════════════════════════ -->
    <div id="jpgUserModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="jpgUserModalTitle">Benutzer bearbeiten</h3>
                <button class="modal-close" onclick="closeModal('jpgUserModal')">&times;</button>
            </div>
            <div class="modal-body">

                <!-- Tab-Navigation -->
                <div class="tabs" style="margin-bottom:1.25rem;">
                    <button class="tab-btn active" onclick="switchTab('jpgTabRole', this)" type="button">🏷️ Plugin-Rolle</button>
                    <button class="tab-btn" onclick="switchTab('jpgTabCompany', this)" type="button">🏢 Unternehmen</button>
                </div>

                <!-- Tab: Rolle -->
                <div id="jpgTabRole" class="tab-content active">
                    <form method="POST" id="jpgRoleForm">
                        <input type="hidden" name="_jpg_nonce" value="<?php echo $esc($nonce); ?>">
                        <input type="hidden" name="users_action" value="set_role">
                        <input type="hidden" name="target_user_id" id="jpgRoleUserId" value="">

                        <div class="form-group">
                            <label class="form-label">Plugin-Rolle</label>
                            <select name="jpg_role" id="jpgRoleSelect" class="form-control">
                                <option value="">Standard (kein spez. Zugriff)</option>
                                <option value="mandant">👥 Mandant</option>
                                <option value="admin">🔑 Plugin-Admin</option>
                                <option value="blocked">🚫 Gesperrt</option>
                            </select>
                            <small class="form-text">
                                <strong>Mandant:</strong> Kann eigene Stellen verwalten.<br>
                                <strong>Plugin-Admin:</strong> Vollzugriff (Admin-Bereich).<br>
                                <strong>Gesperrt:</strong> Plugin-Bereich nicht zugänglich.
                            </small>
                        </div>
                        <button type="submit" class="btn btn-primary">💾 Rolle speichern</button>
                    </form>
                </div>

                <!-- Tab: Unternehmen -->
                <div id="jpgTabCompany" class="tab-content">
                    <?php if (!empty($unassignedCompanies)): ?>
                    <form method="POST" style="margin-bottom:1.5rem;" id="jpgAssignCompanyForm">
                        <input type="hidden" name="_jpg_nonce" value="<?php echo $esc($nonce); ?>">
                        <input type="hidden" name="users_action" value="assign_company">
                        <input type="hidden" name="target_user_id" id="jpgCompanyUserId" value="">
                        <div class="form-group">
                            <label class="form-label">Vorhandenes Unternehmen zuweisen</label>
                            <select name="company_id" class="form-control">
                                <option value="">— Unternehmen wählen —</option>
                                <?php foreach ($unassignedCompanies as $c): ?>
                                <option value="<?php echo (int)$c->id; ?>"><?php echo $esc($c->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-secondary">🔗 Zuweisen</button>
                    </form>
                    <?php endif; ?>

                    <form method="POST" id="jpgCreateCompanyForm">
                        <input type="hidden" name="_jpg_nonce" value="<?php echo $esc($nonce); ?>">
                        <input type="hidden" name="users_action" value="create_company">
                        <input type="hidden" name="target_user_id" id="jpgNewCompanyUserId" value="">
                        <div class="form-group">
                            <label class="form-label">Neues Unternehmen anlegen & zuweisen</label>
                            <input type="text" name="new_company_name" class="form-control"
                                   placeholder="Unternehmensname" required>
                        </div>
                        <button type="submit" class="btn btn-primary">➕ Anlegen & Zuweisen</button>
                    </form>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('jpgUserModal')">Schließen</button>
            </div>
        </div>
    </div>

    <script src="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/assets/js/admin.js"></script>
    <script>
    function jpgOpenUserModal(userId, userName, currentRole, companyId) {
        document.getElementById('jpgUserModalTitle').textContent = '✏️ ' + userName;
        document.getElementById('jpgRoleUserId').value    = userId;
        document.getElementById('jpgCompanyUserId').value  = userId;
        document.getElementById('jpgNewCompanyUserId').value = userId;
        const sel = document.getElementById('jpgRoleSelect');
        if (sel) sel.value = currentRole || '';
        openModal('jpgUserModal');
    }

    function switchTab(tabId, btn) {
        document.querySelectorAll('#jpgUserModal .tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('#jpgUserModal .tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        btn.classList.add('active');
    }

    window.addEventListener('click', function(e) {
        const modal = document.getElementById('jpgUserModal');
        if (e.target === modal) closeModal('jpgUserModal');
    });
    </script>
</body>
</html>
