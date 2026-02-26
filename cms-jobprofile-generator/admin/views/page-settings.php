<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * View: Einstellungen – 5 Tabs
 *
 * @var string               $tab
 * @var array<string,string> $tabs
 * @var array<string,string> $settings
 * @var string               $notice
 * @var string               $error
 */

$nonce = CMS_JPG_Admin_Pages::nonce('jpg_settings_save');
$s       = fn(string $key, string $default = '') => htmlspecialchars($settings['jpg_' . $key] ?? $default, ENT_QUOTES);
?>

<div class="admin-page-header">
    <div>
        <h2>⚙️ Einstellungen</h2>
        <p>Plugin-Konfiguration, Berechtigungen, Workflow und System-Informationen</p>
    </div>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo htmlspecialchars($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="jpg-tabs">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="?tab=<?php echo esc_attr($key); ?>"
       class="jpg-tab<?php echo $tab === $key ? ' active' : ''; ?>">
        <?php echo esc_html($label); ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;max-width:700px;">

<!-- ══════════════════════════════════════ TAB: ALLGEMEIN ══ -->
<?php if ($tab === 'general'): ?>
<h3>⚙️ Allgemeine Einstellungen</h3>
<form method="post" class="admin-form">
    <input type="hidden" name="_jpg_nonce" value="<?php echo esc_attr($nonce); ?>">

    <div class="form-group">
        <label class="form-label">Standard-Status für neue Profile</label>
        <select name="default_status" class="form-control">
            <?php $curStatus = $s('default_status', 'draft'); ?>
            <?php foreach (['draft'=>'📝 Entwurf','published'=>'✅ Veröffentlicht'] as $val => $lbl): ?>
            <option value="<?php echo $val; ?>" <?php echo $curStatus === $val ? 'selected' : ''; ?>>
                <?php echo $lbl; ?>
            </option>
            <?php endforeach; ?>
        </select>
        <small class="form-text">Standardmäßig werden neue Profile als Entwurf angelegt.</small>
    </div>

    <div class="form-group">
        <label class="form-label">Profile pro Seite (Paginierung)</label>
        <input type="number" name="profiles_per_page" class="form-control"
               value="<?php echo $s('profiles_per_page', '20'); ?>"
               min="5" max="100" step="5">
    </div>

    <div class="form-group">
        <label class="form-label">Slug-Präfix</label>
        <input type="text" name="slug_prefix" class="form-control"
               value="<?php echo $s('slug_prefix', 'stelle'); ?>"
               placeholder="stelle" maxlength="30">
        <small class="form-text">Wird dem automatisch generierten URL-Slug vorangestellt.</small>
    </div>

    <button type="submit" class="btn btn-primary">💾 Speichern</button>
</form>

<!-- ══════════════════════════════════════ TAB: BERECHTIGUNGEN ══ -->
<?php elseif ($tab === 'permissions'): ?>
<h3>🔐 Berechtigungen</h3>
<div class="alert" style="background:#dbeafe;color:#1e40af;border-left:4px solid #3b82f6;">
    ℹ️ Hier definierst du, welche CMS-Rollen welche Aktionen durchführen dürfen. Alle Rollen werden direkt aus der Datenbank (<code>cms_roles</code>) geladen – auch benutzerdefinierte Rollen.
</div>
<form method="post" class="admin-form">
    <input type="hidden" name="_jpg_nonce" value="<?php echo esc_attr($nonce); ?>">

    <?php
    // Alle Rollen dynamisch aus der Datenbank laden (inkl. benutzerdefinierter Rollen)
    $roleOptions = class_exists('CMS_JPG_Workflow')
        ? CMS_JPG_Workflow::get_all_cms_roles()
        : ['admin' => 'Admin', 'editor' => 'Redakteur', 'author' => 'Autor'];

    $permDef = [
        'role_create'   => 'Profile erstellen (Member-Bereich)',
        'role_edit'     => 'Profile bearbeiten (Member-Bereich)',
        'role_delete'   => 'Profile löschen',
        'role_publish'  => 'Profile direkt veröffentlichen (ohne Workflow)',
        'role_approve'  => 'Workflow-Freigaben erteilen',
    ];
    foreach ($permDef as $key => $label): ?>
    <div class="form-group">
        <label class="form-label"><?php echo $label; ?></label>
        <select name="<?php echo $key; ?>" class="form-control">
            <?php $curr = $s($key, 'admin'); ?>
            <?php foreach ($roleOptions as $val => $rl): ?>
            <option value="<?php echo htmlspecialchars($val, ENT_QUOTES); ?>"
                    <?php echo $curr === $val ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($rl, ENT_QUOTES); ?>
                (<?php echo htmlspecialchars($val, ENT_QUOTES); ?>)
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary">💾 Berechtigungen speichern</button>
</form>

<!-- ══════════════════════════════════════ TAB: WORKFLOW ══ -->
<?php elseif ($tab === 'workflow'): ?>
<h3>🔄 Workflow-Optionen</h3>
<form method="post" class="admin-form">
    <input type="hidden" name="_jpg_nonce" value="<?php echo esc_attr($nonce); ?>">

    <?php
    $wfDef = [
        'review_required'   => ['4-Augen-Prinzip (Review)', 'Jedes Profil muss vor Veröffentlichung reviewed werden.'],
        'approval_required' => ['Freigabe erforderlich', 'Ein Admin muss Profile vor Veröffentlichung freigeben.'],
        'notify_on_publish' => ['Benachrichtigung bei Veröffentlichung', 'Sende E-Mail wenn ein Profil veröffentlicht wird.'],
    ];
    foreach ($wfDef as $key => [$lbl, $hint]): ?>
    <div class="form-group">
        <label class="checkbox-label" style="display:flex;align-items:flex-start;gap:.75rem;cursor:pointer;padding:.5rem;border-radius:6px;hover:background:#f8fafc;">
            <input type="checkbox" name="<?php echo $key; ?>" value="1"
                   <?php echo $s($key, '0') === '1' ? 'checked' : ''; ?>
                   style="margin-top:.2rem;">
            <div>
                <div style="font-weight:500;"><?php echo $lbl; ?></div>
                <div style="font-size:.82rem;color:#64748b;"><?php echo $hint; ?></div>
            </div>
        </label>
    </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary">💾 Workflow speichern</button>
</form>

<!-- ══════════════════════════════════════ TAB: BENACHRICHTIGUNGEN ══ -->
<?php elseif ($tab === 'notifications'): ?>
<h3>🔔 Benachrichtigungen</h3>
<form method="post" class="admin-form">
    <input type="hidden" name="_jpg_nonce" value="<?php echo esc_attr($nonce); ?>">

    <div class="form-group">
        <label class="form-label">Benachrichtigungs-E-Mail</label>
        <input type="email" name="notify_email" class="form-control"
               value="<?php echo $s('notify_email'); ?>"
               placeholder="admin@beispiel.de">
        <small class="form-text">An diese Adresse werden Plugin-Benachrichtigungen gesendet.</small>
    </div>

    <div class="form-group">
        <label class="checkbox-label" style="display:flex;align-items:center;gap:.75rem;cursor:pointer;">
            <input type="checkbox" name="notify_on_create" value="1"
                   <?php echo $s('notify_on_create', '0') === '1' ? 'checked' : ''; ?>>
            <div>
                <div style="font-weight:500;">Bei Profilerstellung benachrichtigen</div>
                <div style="font-size:.82rem;color:#64748b;">E-Mail-Benachrichtigung wenn ein neues Profil angelegt wird.</div>
            </div>
        </label>
    </div>

    <div class="form-group">
        <label class="checkbox-label" style="display:flex;align-items:center;gap:.75rem;cursor:pointer;">
            <input type="checkbox" name="notify_on_delete" value="1"
                   <?php echo $s('notify_on_delete', '0') === '1' ? 'checked' : ''; ?>>
            <div>
                <div style="font-weight:500;">Bei Profillöschung benachrichtigen</div>
                <div style="font-size:.82rem;color:#64748b;">E-Mail-Benachrichtigung wenn ein Profil gelöscht wird.</div>
            </div>
        </label>
    </div>

    <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
</form>

<!-- ══════════════════════════════════════ TAB: SYSTEM-INFO ══ -->
<?php elseif ($tab === 'system'): ?>
<h3>💻 System-Information</h3>

<div class="info-grid">
    <div class="info-card">
        <h4>Plugin</h4>
        <ul class="info-list" style="list-style:none;padding:0;">
            <li><strong>Version:</strong> <?php echo JPG_VERSION; ?></li>
            <li><strong>DB-Version:</strong> <?php echo JPG_DB_VERSION; ?></li>
            <li><strong>Plugin-Dir:</strong>
                <code style="font-size:.8rem;background:#f1f5f9;padding:.1rem .4rem;border-radius:3px;">
                    <?php echo htmlspecialchars(JPG_DIR); ?>
                </code>
            </li>
        </ul>
    </div>
    <div class="info-card">
        <h4>PHP & Server</h4>
        <ul class="info-list" style="list-style:none;padding:0;">
            <li><strong>PHP-Version:</strong> <?php echo phpversion(); ?></li>
            <li><strong>Zeitzone:</strong> <?php echo htmlspecialchars(date_default_timezone_get()); ?></li>
            <li><strong>Datum/Zeit:</strong> <?php echo date('d.m.Y H:i:s'); ?></li>
        </ul>
    </div>
</div>

<div class="admin-card" style="background:#fef3c7;border-color:#fbbf24;margin-top:1rem;">
    <h4 style="color:#92400e;">🛠️ Datenbank-Tools</h4>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:.75rem;">
        <form method="post">
            <input type="hidden" name="_jpg_nonce"     value="<?php echo esc_attr($nonce); ?>">
            <input type="hidden" name="db_reinstall"   value="1">
            <button type="submit" class="btn btn-secondary"
                    onclick="return confirm('Datenbank neu installieren? Bestehende Daten bleiben erhalten.')">
                🔄 DB neu installieren
            </button>
        </form>
    </div>
</div>


<!-- ══════════════════════════════════════ TAB: AUDIT-LOG ══ -->
<?php elseif ($tab === 'audit-log'): ?>
<h3>🔎 Admin-Zugriffsprotokoll</h3>
<p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem;">
    DSGVO-Audit: wer hat wann welche Firma/Stelle im Admin eingesehen.
</p>

<?php if (!empty($filterOptions ?? [])): ?>
<form method="get" style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap;margin-bottom:1.25rem;">
    <input type="hidden" name="tab" value="audit-log">
    <div class="form-group" style="margin-bottom:0;min-width:220px;">
        <label class="form-label">Nach Firma filtern</label>
        <select name="filter_company" class="form-control">
            <option value="">— Alle Firmen —</option>
            <?php foreach ($filterOptions as $opt): ?>
            <option value="<?php echo (int)$opt->id; ?>"
                    <?php echo ($filterCompany ?? 0) === (int)$opt->id ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($opt->company_name ?? ''); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-secondary btn-sm">🔍 Filtern</button>
    <?php if (!empty($filterCompany)): ?>
    <a href="?tab=audit-log" class="btn btn-outline btn-sm">✖ Filter aufheben</a>
    <?php endif; ?>
</form>
<?php endif; ?>

<?php if (empty($auditLog ?? [])): ?>
<div class="empty-state" style="padding:2.5rem 1.5rem;text-align:center;">
    <p style="font-size:2.5rem;margin:0;">📋</p>
    <p><strong>Noch keine Protokolleinträge vorhanden</strong></p>
    <p style="color:#64748b;font-size:.875rem;">Einträge werden automatisch erzeugt, sobald ein Admin Firmen oder Profile öffnet.</p>
</div>
<?php else: ?>
<div class="users-table-container">
    <table class="users-table">
        <thead><tr>
            <th>Admin</th>
            <th>Aktion</th>
            <th>Firma</th>
            <th>Profil</th>
            <th>IP-Adresse</th>
            <th>Datum / Uhrzeit</th>
        </tr></thead>
        <tbody>
        <?php foreach ($auditLog as $entry): ?>
        <tr>
            <td>
                <?php
                $adminName = trim(htmlspecialchars(($entry->admin_firstname ?? '').' '.($entry->admin_lastname ?? '')));
                echo $adminName ?: htmlspecialchars($entry->admin_username ?? '—');
                ?>
            </td>
            <td>
                <?php
                $actionLabels = [
                    'view_company' => '🏢 Firma geöffnet',
                    'view_profile' => '👤 Profil geöffnet',
                ];
                echo htmlspecialchars($actionLabels[$entry->action ?? ''] ?? $entry->action ?? '—');
                ?>
            </td>
            <td><?php echo htmlspecialchars($entry->company_name ?? '—'); ?></td>
            <td>
                <?php if (!empty($entry->profile_title)): ?>
                    <a href="?page=jpg-generator&id=<?php echo (int)$entry->target_profile_id; ?>">
                        <?php echo htmlspecialchars($entry->profile_title); ?>
                    </a>
                <?php else: ?>
                    <span style="color:#94a3b8;">—</span>
                <?php endif; ?>
            </td>
            <td style="font-size:.82rem;color:#64748b;font-family:monospace;">
                <?php echo htmlspecialchars($entry->ip_addr ?? '—'); ?>
            </td>
            <td style="font-size:.82rem;color:#64748b;white-space:nowrap;">
                <?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($entry->created_at ?? 'now'))); ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p style="font-size:.79rem;color:#94a3b8;margin-top:.75rem;">Es werden maximal die letzten 100 Einträge angezeigt.</p>
<?php endif; ?>

<!-- ══════════════════════════════════ TAB: HEALTH-MONITOR ══ -->
<?php elseif ($tab === 'health'): ?>
<?php
$hd          = $healthData ?? [];
$orphans     = $hd['orphanFiles']  ?? [];
$expiredCnt  = (int)($hd['expiredCount'] ?? 0);
$totalApps   = (int)($hd['totalApps']    ?? 0);
$tableSizes  = $hd['tableSizes']   ?? [];
$uploadsPath = $hd['uploads']      ?? '';
?>

<div class="admin-card">
    <h3>🗂️ Verwaiste Dateien (Zombie-Check)</h3>
    <p style="color:#64748b;font-size:.875rem;">CVs im Upload-Verzeichnis ohne zugehörigen Datenbankeintrag.</p>
    <?php if (empty($orphans)): ?>
    <div class="alert alert-success">✅ Keine verwaisten Dateien gefunden.</div>
    <?php else: ?>
    <div class="alert" style="background:#fef3c7;color:#92400e;border-left:4px solid #f59e0b;">
        ⚠️ <?php echo count($orphans); ?> verwaiste Datei(en) gefunden (<?php echo round(array_sum(array_column($orphans,'size'))/1024); ?> KB).
    </div>
    <div style="overflow-x:auto;max-height:200px;overflow-y:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
        <thead><tr style="background:#f8fafc;">
            <th style="padding:.5rem 1rem;text-align:left;">Datei</th>
            <th style="padding:.5rem 1rem;text-align:right;">Größe</th>
        </tr></thead>
        <tbody>
        <?php foreach ($orphans as $of): ?>
        <tr style="border-top:1px solid #f1f5f9;">
            <td style="padding:.5rem 1rem;color:#64748b;"><?php echo htmlspecialchars(basename($of['path'])); ?></td>
            <td style="padding:.5rem 1rem;text-align:right;color:#64748b;"><?php echo round($of['size']/1024,1); ?> KB</td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <form method="POST" action="?tab=health" style="margin-top:1rem;">
        <input type="hidden" name="_jpg_nonce"     value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="health_action"  value="cleanup_orphans">
        <button type="submit" class="btn btn-danger btn-sm"
                onclick="return confirm('<?php echo count($orphans); ?> Datei(en) unwiderruflich löschen?')">
            🗑️ Verwaiste Dateien löschen
        </button>
    </form>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h3>🔐 DSGVO-Bereinigungs-Cron</h3>
    <p style="color:#64748b;font-size:.875rem;">Abgelaufene Bewerberdaten (angenommen/abgelehnt) manuell löschen.</p>
    <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;margin-bottom:.75rem;">
        <div class="stat-card" style="padding:.75rem 1.25rem;min-width:120px;">
            <div style="font-size:1.5rem;font-weight:700;color:#3b82f6;"><?php echo $totalApps; ?></div>
            <div style="font-size:.79rem;color:#64748b;">Gesamt Bewerbungen</div>
        </div>
        <div class="stat-card" style="padding:.75rem 1.25rem;min-width:120px;">
            <div style="font-size:1.5rem;font-weight:700;color:<?php echo $expiredCnt > 0 ? '#ef4444' : '#10b981'; ?>;"><?php echo $expiredCnt; ?></div>
            <div style="font-size:.79rem;color:#64748b;">Älter als 90 Tage</div>
        </div>
    </div>
    <?php if ($expiredCnt > 0): ?>
    <form method="POST" action="?tab=health">
        <input type="hidden" name="_jpg_nonce"    value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="health_action" value="cleanup_expired">
        <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
            <label class="form-label" style="margin-bottom:0;">Aufbewahrung (Tage):</label>
            <input type="number" name="retention_days" class="form-control"
                   value="90" min="30" max="730" style="width:100px;">
            <button type="submit" class="btn btn-danger btn-sm"
                    onclick="return confirm('Abgelaufene Bewerbungen unwiderruflich löschen (DSGVO)?')">
                🗑️ Jetzt bereinigen
            </button>
        </div>
    </form>
    <?php else: ?>
    <div class="alert alert-success">✅ Keine abgelaufenen Bewerbungen vorhanden.</div>
    <?php endif; ?>
</div>

<?php if (!empty($tableSizes)): ?>
<div class="admin-card">
    <h3>🗄️ Datenbank-Tabellen</h3>
    <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
        <thead><tr style="background:#f8fafc;">
            <th style="padding:.5rem 1rem;text-align:left;color:#475569;">Tabelle</th>
            <th style="padding:.5rem 1rem;text-align:right;color:#475569;">Größe (KB)</th>
        </tr></thead>
        <tbody>
        <?php foreach ($tableSizes as $tbl => $kb): ?>
        <tr style="border-top:1px solid #f1f5f9;">
            <td style="padding:.5rem 1rem;color:#64748b;"><?php echo htmlspecialchars($tbl); ?></td>
            <td style="padding:.5rem 1rem;text-align:right;color:#64748b;"><?php echo $kb; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php endif; ?>

</div>
