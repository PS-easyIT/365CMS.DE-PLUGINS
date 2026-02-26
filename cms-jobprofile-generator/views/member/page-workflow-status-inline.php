<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * Member-View: Workflow-Status (Inline)
 *
 * Zeigt den Genehmigungsstatus aller eigenen Stellenanzeigen.
 *
 * @var array   $profiles   Profil-Liste mit workflow_status
 * @var string  $csrf       CSRF-Token für Einreich-Aktionen
 * @var string  $baseUrl    Basis-URL
 * @var string  $notice
 * @var string  $error
 */
$esc = fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);

$wfLabels = [
    'none'     => ['label' => 'Kein Workflow',  'class' => 'inactive', 'icon' => '📝'],
    'draft'    => ['label' => 'Entwurf',         'class' => 'inactive', 'icon' => '📄'],
    'pending'  => ['label' => 'In Genehmigung', 'class' => 'member',   'icon' => '⏳'],
    'approved' => ['label' => 'Genehmigt',       'class' => 'active',   'icon' => '✅'],
    'rejected' => ['label' => 'Abgelehnt',       'class' => 'danger',   'icon' => '❌'],
];
$statusLabels = [
    'draft'     => 'Entwurf',
    'published' => 'Aktiv',
    'archived'  => 'Archiviert',
];
?>

<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo $esc($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo $esc($error); ?></div>
<?php endif; ?>

<div class="admin-card">
    <h3>🔄 Workflow-Status meiner Stellen</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem;">
        Hier siehst du den Genehmigungsstatus deiner Stellenanzeigen.
        Im Status <strong>Entwurf</strong> kannst du Stellen zur Prüfung einreichen.
    </p>

    <?php if (empty($profiles)): ?>
    <div class="empty-state">
        <p style="font-size:1.5rem;margin:0;">📭</p>
        <p><strong>Noch keine Stellenanzeigen vorhanden</strong></p>
        <a href="<?php echo $esc($baseUrl); ?>?action=create" class="btn btn-primary" style="margin-top:1rem;">➕ Neue Stelle erstellen</a>
    </div>
    <?php else: ?>
    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Stellentitel</th>
                    <th>Veröffentlichungs-Status</th>
                    <th>Workflow-Status</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profiles as $profile):
                    $wf    = $wfLabels[$profile->workflow_status ?? 'none'] ?? $wfLabels['none'];
                    $canSubmit = in_array($profile->workflow_status ?? 'none', ['none', 'draft'], true)
                              && class_exists('CMS_JPG_Workflow');
                ?>
                <tr>
                    <td>
                        <strong><?php echo $esc($profile->title ?? ''); ?></strong>
                    </td>
                    <td>
                        <span class="status-badge <?php echo (($profile->status ?? '') === 'published') ? 'active' : 'inactive'; ?>">
                            <?php echo $esc($statusLabels[$profile->status ?? ''] ?? $profile->status ?? ''); ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge <?php echo $esc($wf['class']); ?>">
                            <?php echo $wf['icon']; ?> <?php echo $esc($wf['label']); ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:.4rem;">
                            <a href="<?php echo $esc($baseUrl); ?>/edit/<?php echo (int)$profile->id; ?>" class="btn btn-sm btn-secondary">✏️ Bearbeiten</a>
                            <?php if ($canSubmit): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="_jpg_csrf" value="<?php echo $esc($csrf); ?>">
                                <input type="hidden" name="workflow_action" value="submit">
                                <input type="hidden" name="profile_id" value="<?php echo (int)$profile->id; ?>">
                                <button type="submit" class="btn btn-sm btn-primary"
                                        onclick="return confirm('Stelle zur Genehmigung einreichen?')">
                                    📤 Einreichen
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h3>ℹ️ Workflow erklärt</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
        <?php foreach ($wfLabels as $key => $wf): ?>
        <div style="display:flex;align-items:flex-start;gap:.75rem;padding:.75rem;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;">
            <span style="font-size:1.4rem;"><?php echo $wf['icon']; ?></span>
            <div>
                <div style="font-weight:600;font-size:.9rem;color:#1e293b;"><?php echo $esc($wf['label']); ?></div>
                <div style="font-size:.8rem;color:#64748b;margin-top:.2rem;">
                    <?php echo match($key) {
                        'none'     => 'Kein Workflow konfiguriert.',
                        'draft'    => 'Entwurf – noch nicht eingereicht.',
                        'pending'  => 'Warte auf Freigabe durch einen Genehmiger.',
                        'approved' => 'Freigegeben – Stelle kann veröffentlicht werden.',
                        'rejected' => 'Abgelehnt – bitte bearbeiten und neu einreichen.',
                        default    => '',
                    }; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
