<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * Member-View: Unternehmens-Übersicht (Inline)
 *
 * @var object|null   $company     Firmendaten oder null
 * @var array         $profiles    Zugehörige Stellenanzeigen
 * @var int           $totalApps   Gesamtzahl Bewerbungen
 * @var string        $notice
 * @var string        $error
 * @var callable      $esc
 */
$siteUrl = defined('SITE_URL') ? SITE_URL : '';
?>

<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo $esc($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo $esc($error); ?></div>
<?php endif; ?>

<?php if ($company === null): ?>
<div class="empty-state">
    <p style="font-size:2.5rem;margin:0;">🏢</p>
    <p><strong>Noch kein Firmenprofil vorhanden</strong></p>
    <p style="color:#64748b;font-size:.875rem;">
        Richten Sie zuerst Ihr Unternehmensprofil unter <strong>Einstellungen</strong> ein.
    </p>
    <a href="/member/plugin/member-job-settings" class="btn btn-primary" style="margin-top:1rem;">
        ⚙️ Zu den Einstellungen
    </a>
</div>
<?php else: ?>

<!-- ══ KPI-Kacheln ════════════════════════════════════════════════════════════ -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1.25rem;margin-bottom:1.5rem;">
    <div class="admin-card" style="text-align:center;padding:1.25rem;">
        <div style="font-size:2rem;font-weight:800;color:var(--admin-primary,#3b82f6);"><?php echo count($profiles); ?></div>
        <div style="font-size:.85rem;color:#64748b;margin-top:.25rem;">Stellenanzeigen</div>
    </div>
    <div class="admin-card" style="text-align:center;padding:1.25rem;">
        <div style="font-size:2rem;font-weight:800;color:#10b981;"><?php echo (int)$totalApps; ?></div>
        <div style="font-size:.85rem;color:#64748b;margin-top:.25rem;">Gesamt-Bewerbungen</div>
    </div>
    <div class="admin-card" style="text-align:center;padding:1.25rem;">
        <div style="font-size:2rem;font-weight:800;color:#f59e0b;">
            <?php echo count(array_filter($profiles, fn($p) => ($p->status ?? '') === 'published')); ?>
        </div>
        <div style="font-size:.85rem;color:#64748b;margin-top:.25rem;">Aktive Stellen</div>
    </div>
    <div class="admin-card" style="text-align:center;padding:1.25rem;">
        <div style="font-size:2rem;font-weight:800;color:#8b5cf6;">
            <?php echo array_sum(array_column($profiles, 'views')); ?>
        </div>
        <div style="font-size:.85rem;color:#64748b;margin-top:.25rem;">Profil-Aufrufe</div>
    </div>
</div>

<!-- ══ Firmeninfo ══════════════════════════════════════════════════════════════ -->
<div class="admin-card">
    <h3>🏢 <?php echo $esc($company->name ?? 'Mein Unternehmen'); ?></h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div>
            <?php if (!empty($company->logo_url)): ?>
            <img src="<?php echo $esc($company->logo_url); ?>" alt="Logo"
                 style="max-height:80px;max-width:200px;object-fit:contain;margin-bottom:1rem;border-radius:6px;border:1px solid #e2e8f0;">
            <?php endif; ?>
            <ul class="info-list">
                <?php if (!empty($company->email)): ?>
                <li><strong>E-Mail:</strong> <a href="mailto:<?php echo $esc($company->email); ?>"><?php echo $esc($company->email); ?></a></li>
                <?php endif; ?>
                <?php if (!empty($company->phone)): ?>
                <li><strong>Telefon:</strong> <?php echo $esc($company->phone); ?></li>
                <?php endif; ?>
                <?php if (!empty($company->website)): ?>
                <li><strong>Website:</strong> <a href="<?php echo $esc($company->website); ?>" target="_blank" rel="noopener"><?php echo $esc($company->website); ?></a></li>
                <?php endif; ?>
                <?php if (!empty($company->industry)): ?>
                <li><strong>Branche:</strong> <?php echo $esc($company->industry); ?></li>
                <?php endif; ?>
                <?php if (!empty($company->location_city)): ?>
                <li><strong>Standort:</strong> <?php echo $esc($company->location_city); ?><?php echo !empty($company->location_zip) ? ', ' . $esc($company->location_zip) : ''; ?></li>
                <?php endif; ?>
            </ul>
        </div>
        <div>
            <?php if (!empty($company->description)): ?>
            <p style="color:#475569;font-size:.9rem;line-height:1.6;"><?php echo nl2br($esc($company->description)); ?></p>
            <?php endif; ?>
            <a href="/member/plugin/member-job-settings" class="btn btn-secondary" style="margin-top:1rem;">
                ✏️ Profil bearbeiten
            </a>
        </div>
    </div>
</div>

<!-- ══ Meine Stellenanzeigen ══════════════════════════════════════════════════ -->
<div class="admin-card">
    <h3>📄 Meine Stellenanzeigen</h3>
    <?php if (empty($profiles)): ?>
    <div class="empty-state">
        <p style="font-size:1.5rem;margin:0;">📭</p>
        <p><strong>Noch keine Stellen angelegt</strong></p>
        <a href="/member/plugin/member-jobs?action=create" class="btn btn-primary" style="margin-top:1rem;">➕ Neue Stelle</a>
    </div>
    <?php else: ?>
    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Status</th>
                    <th>Aufrufe</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profiles as $profile): ?>
                <tr>
                    <td><?php echo $esc($profile->title ?? ''); ?></td>
                    <td>
                        <?php
                        $statusMap = [
                            'published' => ['label' => 'Aktiv',    'class' => 'active'],
                            'draft'     => ['label' => 'Entwurf',  'class' => 'inactive'],
                            'archived'  => ['label' => 'Archiviert','class' => 'inactive'],
                        ];
                        $s = $statusMap[$profile->status ?? ''] ?? ['label' => $esc($profile->status ?? ''), 'class' => 'inactive'];
                        ?>
                        <span class="status-badge <?php echo $s['class']; ?>"><?php echo $s['label']; ?></span>
                    </td>
                    <td><?php echo (int)($profile->views ?? 0); ?></td>
                    <td>
                        <div style="display:flex;gap:.4rem;">
                            <a href="/member/plugin/member-jobs/edit/<?php echo (int)$profile->id; ?>" class="btn btn-sm btn-secondary">✏️</a>
                            <?php if (!empty($profile->slug) && ($profile->status ?? '') === 'published'): ?>
                            <a href="<?php echo $siteUrl; ?>/jobs/<?php echo $esc($profile->slug); ?>" target="_blank" class="btn btn-sm btn-secondary">🔗</a>
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

<?php endif; // $company !== null ?>
