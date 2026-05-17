<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * Admin-View: Benutzer & Mandanten-Verwaltung
 *
 * @var array  $users               CMS-Benutzer mit Plugin-Daten
 * @var array  $userMeta            [user_id => jpg_plugin_role]
 * @var array  $unassignedCompanies Alle Firmen (mit optionalem Zuweisungsstatus)
 * @var string $nonce               CSRF-Nonce
 * @var string $notice
 * @var string $error
 */

$esc = fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);

$roleLabels = [
    ''        => ['label' => 'Standard',      'badge' => 'inactive'],
    'mandant' => ['label' => 'Mandant',       'badge' => 'member'],
    'editor'  => ['label' => 'Redakteur',     'badge' => 'inactive'],
    'viewer'  => ['label' => 'Betrachter',    'badge' => 'inactive'],
    'admin'   => ['label' => 'Plugin-Admin',  'badge' => 'admin'],
    'blocked' => ['label' => 'Gesperrt',      'badge' => 'danger'],
];
?>

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
                                <span class="status-badge <?php echo $esc($roleMeta['badge']); ?>"
                                      data-user-role-badge="<?php echo (int)$user->id; ?>">
                                    <?php echo $esc($roleMeta['label']); ?>
                                </span>
                            </td>
                            <td data-user-company-cell="<?php echo (int)$user->id; ?>">
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
        <!-- ══ CMS-Rollen Übersicht ═════════════════════════════════════ -->
        <?php
        $cmsRoles = $cmsRoles ?? [];
        ?>
        <?php if (!empty($cmsRoles)): ?>
        <div class="admin-card" style="margin-top:1rem;">
            <h3>🏠 Vorhandene CMS-Rollen</h3>
            <p style="color:#64748b;font-size:.85rem;margin-bottom:1rem;">
                Diese Rollen stammen direkt aus dem CMS-Kern (<code>users.role</code>) und können im CMS-Admin unter
                <strong>Benutzer</strong> verwaltet werden. Sie dienen hier nur als Information.
                Plugin-spezifische Berechtigungen werden über <strong>Plugin-Rollen</strong> gesteuert.
            </p>
            <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
                <?php foreach ($cmsRoles as $r): ?>
                <span class="role-badge <?php echo $esc($r->role ?? ''); ?>">
                    <?php echo $esc(ucfirst($r->role ?? '')); ?>
                </span>
                <?php endforeach; ?>
            </div>
            <p style="margin-top:.75rem;font-size:.8rem;color:#94a3b8;">
                Vollständiges Rollen-Mapping CMS → Plugin-Rolle unter
                <a href="?page=jpg-subscription" style="color:#3b82f6;">&#128230; Abosystem &amp; Pakete → CMS-Rollen</a>
            </p>
        </div>
        <?php endif; ?>

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

    <!-- ══ Benutzer-Bearbeitungs-Modal ══════════════════════════════════════ -->
    <div id="jpgUserModal" class="jpg-modal modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="jpgUserModalTitle">Benutzer bearbeiten</h3>
                <button class="modal-close" onclick="jpgCloseModal('jpgUserModal')">&times;</button>
            </div>
            <div class="modal-body">

                <!-- Inline-Feedback -->
                <div id="jpgModalFeedback" role="alert" style="display:none;margin-bottom:1rem;"></div>

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
                                <option value="mandant">🏢 Mandant</option>
                                <option value="editor">✏️ Redakteur</option>
                                <option value="viewer">👁️ Betrachter</option>
                                <option value="admin">🔑 Plugin-Admin</option>
                                <option value="blocked">🚫 Gesperrt</option>
                            </select>
                            <small class="form-text">
                                <strong>Mandant:</strong> Kann eigene Stellen erstellen &amp; Bewerbungen verwalten.<br>
                                <strong>Redakteur:</strong> Kann Stellen erstellen, aber nicht veröffentlichen.<br>
                                <strong>Betrachter:</strong> Nur-Lese-Zugriff auf eigene Profile.<br>
                                <strong>Plugin-Admin:</strong> Vollzugriff (Admin-Bereich).<br>
                                <strong>Gesperrt:</strong> Plugin-Bereich nicht zugänglich.
                            </small>
                        </div>
                        <button type="submit" class="btn btn-primary">💾 Rolle speichern</button>
                    </form>
                </div>

                <!-- Tab: Unternehmen -->
                <div id="jpgTabCompany" class="tab-content">
                    <form method="POST" style="margin-bottom:1.5rem;" id="jpgAssignCompanyForm">
                        <input type="hidden" name="_jpg_nonce" value="<?php echo $esc($nonce); ?>">
                        <input type="hidden" name="users_action" value="assign_company">
                        <input type="hidden" name="target_user_id" id="jpgCompanyUserId" value="">
                        <div class="form-group">
                            <label class="form-label">Vorhandenes Unternehmen zuweisen</label>
                            <?php if (!empty($unassignedCompanies)): ?>
                            <select name="company_id" class="form-control">
                                <option value="">— Unternehmen wählen —</option>
                                <?php foreach ($unassignedCompanies as $c): ?>
                                <option value="<?php echo (int)$c->id; ?>">
                                    <?php echo $esc($c->name);
                                    if (!empty($c->assigned_user)) {
                                        echo ' (aktuell: ' . $esc($c->assigned_user) . ')';
                                    } ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text">
                                In Klammern steht der aktuell zugewiesene Benutzer.
                                Eine Neuzuweisung hebt die frühere Zuweisung auf.
                            </small>
                            <?php else: ?>
                            <p style="color:#94a3b8;font-size:.875rem;margin:0;">
                                Noch keine Unternehmen angelegt. Nutzen Sie das Formular unten.
                            </p>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($unassignedCompanies)): ?>
                        <button type="submit" class="btn btn-secondary">🔗 Zuweisen</button>
                        <?php endif; ?>
                    </form>

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
                <button type="button" class="btn btn-secondary" onclick="jpgCloseModal('jpgUserModal')">Schließen</button>
            </div>
        </div>
    </div>

    <script>
    /* ── Modal-Helfer (inline, kein defer-Abhängigkeit) ──────────────── */
    // Werden auch in jobprofile-admin.js definiert – das defer-Script
    // lädt erst nach dem HTML-Parsing, daher hier als sofortige Fallbacks.
    function jpgOpenModal(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.classList.add('open');
        el.style.display = 'flex';
    }
    function jpgCloseModal(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.classList.remove('open');
        el.style.display = 'none';
    }
    window.addEventListener('click', function (e) {
        document.querySelectorAll('.jpg-modal').forEach(function (m) {
            if (e.target === m) jpgCloseModal(m.id);
        });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.jpg-modal.open').forEach(function (m) {
            jpgCloseModal(m.id);
        });
    });

    /* ── Konfiguration ─────────────────────────────────────────────────── */
    const JPG_USERS_AJAX_URL = '<?php echo htmlspecialchars(SITE_URL . '/api/jpg/admin/users-action', ENT_QUOTES); ?>';
    const JPG_USERS_NONCE    = '<?php echo $esc($nonce); ?>';

    const JPG_ROLE_BADGES = {
        '':        'inactive',
        'mandant': 'member',
        'editor':  'inactive',
        'viewer':  'inactive',
        'admin':   'admin',
        'blocked': 'danger'
    };

    /* ── Modal öffnen ──────────────────────────────────────────────────── */
    function jpgOpenUserModal(userId, userName, currentRole, companyId) {
        document.getElementById('jpgUserModalTitle').textContent = '✏️ ' + userName;
        document.getElementById('jpgRoleUserId').value       = userId;
        document.getElementById('jpgCompanyUserId').value    = userId;
        document.getElementById('jpgNewCompanyUserId').value = userId;

        const sel = document.getElementById('jpgRoleSelect');
        if (sel) { sel.value = currentRole || ''; }

        // Feedback zurücksetzen
        jpgResetModalFeedback();

        jpgOpenModal('jpgUserModal');
    }

    /* ── Tab umschalten ────────────────────────────────────────────────── */
    function switchTab(tabId, btn) {
        document.querySelectorAll('#jpgUserModal .tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('#jpgUserModal .tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        btn.classList.add('active');
        jpgResetModalFeedback();
    }

    /* ── Feedback-Bereich ──────────────────────────────────────────────── */
    function jpgResetModalFeedback() {
        const fb = document.getElementById('jpgModalFeedback');
        if (!fb) { return; }
        fb.style.display = 'none';
        fb.textContent   = '';
        fb.className     = 'alert';
    }

    function jpgShowModalFeedback(type, message) {
        const fb = document.getElementById('jpgModalFeedback');
        if (!fb) { return; }
        fb.className     = 'alert alert-' + type;
        fb.textContent   = (type === 'success' ? '✅ ' : '❌ ') + message;
        fb.style.display = 'block';
        fb.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /* ── AJAX-Aktion absenden ──────────────────────────────────────────── */
    async function jpgSubmitUserAction(formEl, btnEl) {
        const origText    = btnEl ? btnEl.textContent : '';
        if (btnEl) { btnEl.textContent = '⏳ …'; btnEl.disabled = true; }
        jpgResetModalFeedback();

        const fd = new FormData(formEl);
        // Nonce aus dem Seiten-Token setzen (bleibt gültig bis Seitenneuladung)
        fd.set('_jpg_nonce', JPG_USERS_NONCE);

        let ajaxOk = false;
        try {
            const res  = await fetch(JPG_USERS_AJAX_URL, { method: 'POST', body: fd });
            let data;
            try {
                data = await res.json();
            } catch (parseErr) {
                // Antwort ist kein gültiges JSON → Route nicht erreichbar, nativer Fallback
                console.warn('[JPG] AJAX-Antwort kein JSON, nativer POST-Fallback aktiv.', parseErr);
                formEl.submit();
                return;
            }

            ajaxOk = true;
            if (data.success) {
                jpgShowModalFeedback('success', data.message || 'Gespeichert.');

                // Plugin-Rolle-Badge in der Tabelle sofort aktualisieren
                if (data.role !== undefined) {
                    const uid   = formEl.querySelector('[name="target_user_id"]').value;
                    const badge = document.querySelector('[data-user-role-badge="' + uid + '"]');
                    if (badge) {
                        badge.textContent = data.role_label;
                        Object.values(JPG_ROLE_BADGES).forEach(c => badge.classList.remove(c));
                        badge.classList.add(JPG_ROLE_BADGES[data.role] || 'inactive');
                    }
                }

                // Unternehmens-Zelle in der Tabelle sofort aktualisieren
                if (data.company_name) {
                    const uid  = formEl.querySelector('[name="target_user_id"]').value;
                    const cell = document.querySelector('[data-user-company-cell="' + uid + '"]');
                    if (cell) {
                        const company = document.createElement('span');
                        company.textContent = '🏢 ' + data.company_name;
                        cell.replaceChildren(company);
                    }
                    // Neues Unternehmen soll auch in der Auswahl erscheinen → Seite neu laden
                    if (data.company_id) {
                        setTimeout(() => location.reload(), 1800);
                    }
                }
            } else {
                // Sicherheits- oder Validation-Fehler: nativen POST als Fallback anbieten
                if (data.error && data.error.indexOf('Sicherheitscheck') !== -1) {
                    jpgShowModalFeedback('error', data.error + ' → Seite wird neu geladen…');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    jpgShowModalFeedback('error', data.error || 'Ein unbekannter Fehler ist aufgetreten.');
                }
            }
        } catch (err) {
            if (!ajaxOk) {
                // Netzwerkfehler: nativer POST-Fallback (Seite lädt neu mit Meldung)
                console.warn('[JPG] AJAX-Netzwerkfehler, nativer POST-Fallback:', err.message);
                formEl.submit();
                return;
            }
            jpgShowModalFeedback('error', 'Fehler: ' + err.message);
        } finally {
            if (btnEl) { btnEl.textContent = origText; btnEl.disabled = false; }
        }
    }

    /* ── Forms abfangen ────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        ['jpgRoleForm', 'jpgAssignCompanyForm', 'jpgCreateCompanyForm'].forEach(function (id) {
            const form = document.getElementById(id);
            if (!form) { return; }
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const btn = form.querySelector('[type="submit"]');
                jpgSubmitUserAction(form, btn);
            });
        });
    });
    </script>
