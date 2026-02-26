<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * Admin-View: Abosystem & Pakete
 *
 * @var array  $plans            CMS-Abopakete aus subscription_plans
 * @var array  $planLimitsAll    [plan_id => [limits…]] aus Plugin-Optionen
 * @var array  $pluginRoles      5 Standard-Plugin-Rollen-Definitionen
 * @var array  $cmsRoles         CMS-Rollen aus users-Tabelle
 * @var string $nonce
 * @var string $notice
 * @var string $error
 */

$esc = fn(mixed $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
$defLimits = [
    'max_profiles'        => -1,
    'max_active_profiles' => -1,
    'feature_workflow'    => 1,
    'feature_export'      => 0,
    'feature_analytics'   => 0,
    'feature_branding'    => 0,
    'feature_api'         => 0,
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abosystem – <?php echo $esc(defined('SITE_NAME') ? SITE_NAME : 'CMS'); ?></title>
    <link rel="stylesheet" href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/assets/css/admin.css?v=20260222b">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        .plan-card { border:2px solid #e2e8f0; border-radius:10px; overflow:hidden; margin-bottom:1.5rem; }
        .plan-card-header { background:#f8fafc; padding:1rem 1.5rem; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #e2e8f0; }
        .plan-card-header h4 { margin:0; font-size:1rem; font-weight:700; color:#1e293b; }
        .plan-card-body { padding:1.5rem; }
        .limits-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        .feature-toggles { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:.5rem; margin-top:1rem; }
        .feature-toggle { display:flex; align-items:center; gap:.5rem; padding:.5rem .75rem; background:#f8fafc; border-radius:6px; border:1px solid #e2e8f0; cursor:pointer; }
        .feature-toggle input[type="checkbox"] { accent-color:#3b82f6; width:1rem; height:1rem; }
        .role-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:1rem; }
        .role-card { border:1px solid #e2e8f0; border-radius:8px; padding:1rem; background:#fff; }
        .role-card-header { display:flex; align-items:center; gap:.5rem; margin-bottom:.5rem; }
        .role-caps { display:flex; flex-wrap:wrap; gap:.3rem; margin-top:.5rem; }
        .cap-badge { background:#eff6ff; color:#1e40af; font-size:.72rem; padding:.2rem .45rem; border-radius:4px; font-family:monospace; }
        .cms-role-list { display:flex; flex-wrap:wrap; gap:.5rem; margin-top:.75rem; }
        @media (max-width:700px) { .limits-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body class="admin-body">

    <?php renderAdminSidebar('jpg-subscription'); ?>

    <div class="admin-content">

        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h2>📦 Abosystem & Pakete</h2>
                <p>Plugin-spezifische Limits und Features je CMS-Abo-Paket konfigurieren sowie Plugin-Rollen verwalten.</p>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($notice)): ?>
            <div class="alert alert-success">✅ <?php echo $esc($notice); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">❌ <?php echo $esc($error); ?></div>
        <?php endif; ?>

        <!-- ══ TAB-NAVIGATION ════════════════════════════════════════════════ -->
        <div class="tabs" style="margin-bottom:1.5rem;">
            <button class="tab-btn active" onclick="switchTab('tabPlans', this)" type="button">📦 Abo-Pakete</button>
            <button class="tab-btn" onclick="switchTab('tabRoles', this)" type="button">🏷️ Plugin-Rollen</button>
            <button class="tab-btn" onclick="switchTab('tabCmsRoles', this)" type="button">👤 CMS-Rollen</button>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- TAB 1: ABO-PAKETE                                                  -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <div id="tabPlans" class="tab-content active">

            <?php if (empty($plans)): ?>
            <div class="admin-card">
                <div class="empty-state">
                    <p style="font-size:2rem;margin:0;">📭</p>
                    <p><strong>Keine CMS-Abopakete gefunden</strong></p>
                    <p class="text-muted">
                        Die Tabelle <code>subscription_plans</code> ist leer oder das CMS-Modul
                        <code>SubscriptionManager</code> ist nicht aktiv.
                    </p>
                </div>
            </div>
            <?php else: ?>

            <div class="admin-card" style="background:#eff6ff;border-color:#bfdbfe;">
                <p style="margin:0;color:#1e40af;font-size:.9rem;">
                    ℹ️ Hier konfigurierst du <strong>Plugin-spezifische Limits</strong> pro Abo-Paket.
                    Felder mit <code>-1</code> bedeuten <em>unbegrenzt</em>.
                    <code>0</code> deaktiviert das Feature vollständig.
                </p>
            </div>

            <?php foreach ($plans as $plan):
                $pid    = (int) $plan->id;
                $limits = array_merge($defLimits, (array)($planLimitsAll[$pid] ?? []));
                $active = !empty($plan->is_active);
                $price  = (float)($plan->price_monthly ?? 0);
            ?>
            <div class="plan-card">
                <div class="plan-card-header">
                    <div>
                        <h4>
                            <?php echo $esc($plan->name); ?>
                            <?php if (!$active): ?><span class="status-badge inactive">Inaktiv</span><?php endif; ?>
                        </h4>
                        <div style="font-size:.82rem;color:#64748b;">
                            <?php echo $esc($plan->slug); ?>
                            &middot; <?php echo $price > 0 ? number_format($price, 2, ',', '.') . ' €/Monat' : 'Kostenlos'; ?>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-secondary"
                            onclick="jpgTogglePlanForm(<?php echo $pid; ?>)"
                            type="button">
                        ⚙️ Plugin-Limits konfigurieren
                    </button>
                </div>

                <!-- Collapsible Settings Form -->
                <div id="jpgPlanForm-<?php echo $pid; ?>" class="plan-card-body" style="display:none;">
                    <form method="POST">
                        <input type="hidden" name="_jpg_nonce" value="<?php echo $esc($nonce); ?>">
                        <input type="hidden" name="sub_action" value="save_plan_limits">
                        <input type="hidden" name="plan_id" value="<?php echo $pid; ?>">

                        <div class="limits-grid">
                            <div class="form-group">
                                <label class="form-label">Max. Stellenanzeigen gesamt</label>
                                <input type="number" name="max_profiles" class="form-control"
                                       value="<?php echo (int)$limits['max_profiles']; ?>"
                                       min="-1" step="1">
                                <small class="form-text">-1 = unbegrenzt, 0 = gesperrt</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Max. aktive Stellen gleichzeitig</label>
                                <input type="number" name="max_active_profiles" class="form-control"
                                       value="<?php echo (int)$limits['max_active_profiles']; ?>"
                                       min="-1" step="1">
                                <small class="form-text">Veröffentlichte Profile gleichzeitig</small>
                            </div>
                        </div>

                        <p style="font-size:.85rem;font-weight:600;color:#475569;margin:1rem 0 .5rem;">
                            🔧 Plugin-Features
                        </p>
                        <div class="feature-toggles">
                            <label class="feature-toggle">
                                <input type="checkbox" name="feature_workflow"
                                       <?php echo $limits['feature_workflow'] ? 'checked' : ''; ?>>
                                🔄 Workflow (Genehmigungsprozess)
                            </label>
                            <label class="feature-toggle">
                                <input type="checkbox" name="feature_export"
                                       <?php echo $limits['feature_export'] ? 'checked' : ''; ?>>
                                📤 Daten-Export (JSON/CSV)
                            </label>
                            <label class="feature-toggle">
                                <input type="checkbox" name="feature_analytics"
                                       <?php echo $limits['feature_analytics'] ? 'checked' : ''; ?>>
                                📊 Analytics & Statistiken
                            </label>
                            <label class="feature-toggle">
                                <input type="checkbox" name="feature_branding"
                                       <?php echo $limits['feature_branding'] ? 'checked' : ''; ?>>
                                🎨 Custom Branding / Whitelabel
                            </label>
                            <label class="feature-toggle">
                                <input type="checkbox" name="feature_api"
                                       <?php echo $limits['feature_api'] ? 'checked' : ''; ?>>
                                🔌 REST-API-Zugriff
                            </label>
                        </div>

                        <div style="margin-top:1.25rem;">
                            <button type="submit" class="btn btn-primary">💾 Limits speichern</button>
                            <button type="button" class="btn btn-secondary"
                                    onclick="jpgTogglePlanForm(<?php echo $pid; ?>)">Abbrechen</button>
                        </div>
                    </form>
                </div>

                <!-- Kurzübersicht aktueller Limits -->
                <div id="jpgPlanSummary-<?php echo $pid; ?>" class="plan-card-body" style="border-top:1px solid #f1f5f9;">
                    <div style="display:flex;flex-wrap:wrap;gap:1rem;font-size:.85rem;color:#475569;">
                        <span>📋 Max. Stellen: <strong><?php echo $limits['max_profiles'] === -1 ? '∞' : $limits['max_profiles']; ?></strong></span>
                        <span>⚡ Max. aktiv: <strong><?php echo $limits['max_active_profiles'] === -1 ? '∞' : $limits['max_active_profiles']; ?></strong></span>
                        <span>🔄 Workflow: <?php echo $limits['feature_workflow'] ? '<span class="status-badge active">Ja</span>' : '<span class="status-badge inactive">Nein</span>'; ?></span>
                        <span>📤 Export: <?php echo $limits['feature_export'] ? '<span class="status-badge active">Ja</span>' : '<span class="status-badge inactive">Nein</span>'; ?></span>
                        <span>📊 Analytics: <?php echo $limits['feature_analytics'] ? '<span class="status-badge active">Ja</span>' : '<span class="status-badge inactive">Nein</span>'; ?></span>
                        <span>🎨 Branding: <?php echo $limits['feature_branding'] ? '<span class="status-badge active">Ja</span>' : '<span class="status-badge inactive">Nein</span>'; ?></span>
                        <span>🔌 API: <?php echo $limits['feature_api'] ? '<span class="status-badge active">Ja</span>' : '<span class="status-badge inactive">Nein</span>'; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- TAB 2: PLUGIN-ROLLEN                                               -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <div id="tabRoles" class="tab-content">
            <div class="admin-card" style="background:#eff6ff;border-color:#bfdbfe;">
                <p style="margin:0;color:#1e40af;font-size:.9rem;">
                    ℹ️ Diese <strong>5 Plugin-Rollen</strong> sind unabhängig vom CMS-System.
                    Sie werden über die Benutzer-Verwaltung (👥) einzelnen CMS-Benutzern zugewiesen.
                    Die Beschreibungen können hier angepasst werden.
                </p>
            </div>

            <div class="role-grid" style="margin-top:1.25rem;">
                <?php foreach ($pluginRoles as $roleKey => $role): ?>
                <div class="role-card">
                    <div class="role-card-header">
                        <span style="font-size:1.5rem;"><?php echo $role['icon']; ?></span>
                        <div>
                            <span class="status-badge <?php echo $esc($role['color']); ?>">
                                <?php echo $esc($role['label']); ?>
                            </span>
                            <code style="font-size:.72rem;margin-left:.4rem;color:#94a3b8;"><?php echo $esc($roleKey); ?></code>
                        </div>
                    </div>
                    <p style="font-size:.84rem;color:#64748b;margin:.25rem 0 .5rem;"><?php echo $esc($role['description']); ?></p>
                    <?php if (!empty($role['caps'])): ?>
                    <div class="role-caps">
                        <?php foreach ($role['caps'] as $cap): ?>
                        <span class="cap-badge"><?php echo $esc($cap); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Beschreibung bearbeiten -->
                    <form method="POST" style="margin-top:.75rem;padding-top:.75rem;border-top:1px solid #f1f5f9;">
                        <input type="hidden" name="_jpg_nonce" value="<?php echo $esc($nonce); ?>">
                        <input type="hidden" name="sub_action" value="save_role">
                        <input type="hidden" name="role_key" value="<?php echo $esc($roleKey); ?>">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:.78rem;">Beschreibung anpassen</label>
                            <textarea name="role_desc" class="form-control"
                                      style="min-height:60px;font-size:.82rem;"
                                      rows="2"><?php echo $esc($role['description']); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-secondary"
                                style="margin-top:.5rem;">💾 Speichern</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- TAB 3: CMS-ROLLEN                                                  -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <div id="tabCmsRoles" class="tab-content">
            <div class="admin-card" style="background:#eff6ff;border-color:#bfdbfe;">
                <p style="margin:0;color:#1e40af;font-size:.9rem;">
                    ℹ️ Diese Rollen kommen aus dem <strong>CMS-Kern</strong> (<code>users.role</code>).
                    Sie können hier nicht bearbeitet werden und dienen nur zur Information.
                    Zur Plugin-Zugriffsverwaltung verwende die <strong>Plugin-Rollen</strong> (Tab oben).
                </p>
            </div>

            <div class="admin-card">
                <h3>👤 Vorhandene CMS-Rollen</h3>
                <?php if (empty($cmsRoles)): ?>
                <div class="empty-state">
                    <p>Keine Rollen gefunden.</p>
                </div>
                <?php else: ?>
                <div class="cms-role-list">
                    <?php foreach ($cmsRoles as $r): ?>
                    <span class="role-badge <?php echo $esc($r->role); ?>">
                        <?php echo $esc(ucfirst($r->role)); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <p style="margin-top:1rem;font-size:.85rem;color:#64748b;">
                    Diese Rollen werden im CMS-Admin-Bereich unter <strong>Benutzer</strong> verwaltet.
                    Plugin-spezifische Berechtigungen werden dagegen über die
                    <strong>Plugin-Rollen</strong> gesteuert (👥 Benutzer &amp; Mandanten).
                </p>
                <?php endif; ?>
            </div>

            <!-- Mapping-Übersicht: CMS-Rolle → empfohlene Plugin-Rolle -->
            <div class="admin-card">
                <h3>🔗 Empfohlenes Rollen-Mapping</h3>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>CMS-Rolle</th>
                                <th>Empfohlene Plugin-Rolle</th>
                                <th>Begründung</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="role-badge admin">admin</span></td>
                                <td><span class="status-badge admin">Plugin-Admin 🔑</span></td>
                                <td style="font-size:.84rem;color:#64748b;">Voller Systemzugriff</td>
                            </tr>
                            <tr>
                                <td><span class="role-badge member">member</span></td>
                                <td><span class="status-badge member">Mandant 🏢</span></td>
                                <td style="font-size:.84rem;color:#64748b;">Normales Mitglied mit Unternehmens-Zugang</td>
                            </tr>
                            <tr>
                                <td><span class="role-badge">editor</span></td>
                                <td><span class="status-badge inactive">Redakteur ✏️</span></td>
                                <td style="font-size:.84rem;color:#64748b;">Kann Stellen erstellen aber nicht deployen</td>
                            </tr>
                            <tr>
                                <td><span class="role-badge">viewer</span></td>
                                <td><span class="status-badge inactive">Betrachter 👁️</span></td>
                                <td style="font-size:.84rem;color:#64748b;">Nur-Lese-Zugriff</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- /.admin-content -->

    <script src="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/assets/js/admin.js"></script>
    <script>
    function jpgTogglePlanForm(pid) {
        var form    = document.getElementById('jpgPlanForm-' + pid);
        var summary = document.getElementById('jpgPlanSummary-' + pid);
        if (!form) return;
        var isVisible = form.style.display !== 'none';
        form.style.display    = isVisible ? 'none' : 'block';
        summary.style.display = isVisible ? '' : 'none';
    }
    function switchTab(tabId, btn) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        btn.classList.add('active');
    }
    </script>
</body>
</html>
