<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * View: Dashboard – 5 Tabs
 *
 * Variablen aus CMS_JPG_Admin_Pages::render_dashboard():
 * @var array<string,int>    $stats
 * @var array<object>        $profiles
 * @var int                  $total
 * @var string               $tab
 * @var array<string,string> $tabs
 * @var int                  $companiesCount  Anzahl aktiver Unternehmen
 */

$totalAll       = array_sum($stats);
$companiesCount = (int) ($companiesCount ?? 0);
$statDef  = [
    'draft'     => ['📝', 'Entwürfe'],
    'published' => ['✅', 'Veröffentlicht'],
    'archived'  => ['📦', 'Archiviert'],
];
?>
<div class="admin-page-header">
    <div>
        <h2>📊 Dashboard</h2>
        <p>Gesamtübersicht aller Job-Profile und Aktivitäten</p>
    </div>
    <div class="header-actions">
        <?php /* Phase 9: Privacy-Toggle */ $spPrivate = $showPrivate ?? false; ?>
        <a href="?tab=<?php echo esc_attr($tab ?? 'recent'); ?><?php echo $spPrivate ? '' : '&show_private=1'; ?>"
           class="btn btn-secondary btn-sm" title="<?php echo $spPrivate ? 'Private ausblenden' : 'Private anzeigen'; ?>"
           style="display:inline-flex;align-items:center;gap:.4rem;">
            <?php echo $spPrivate ? '🔒 Private ausblenden' : '👁️ Private anzeigen'; ?>
        </a>
        <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-generator?tab=basic'); ?>"
           class="btn btn-primary">➕ Neues Profil</a>
    </div>
</div>

<!-- Subscription Limit-Warnung -->
<?php CMS_JPG_Admin_Pages::render_limit_warning_public(); ?>

<!-- Stat-Cards -->
<div class="dashboard-grid" style="margin-bottom:1.5rem;">
    <?php foreach ($statDef as $key => [$icon, $label]): ?>
    <div class="stat-card">
        <div style="font-size:1.75rem;margin-bottom:.25rem;"><?php echo $icon; ?></div>
        <div class="stat-number"><?php echo number_format($stats[$key] ?? 0); ?></div>
        <div class="stat-label"><?php echo $label; ?></div>
    </div>
    <?php endforeach; ?>
    <div class="stat-card">
        <div style="font-size:1.75rem;margin-bottom:.25rem;">📋</div>
        <div class="stat-number"><?php echo $totalAll; ?></div>
        <div class="stat-label">Gesamt</div>
    </div>
</div>

<!-- Schnellzugriff -->
<div class="admin-card" style="padding:1rem 1.25rem;margin-bottom:1.5rem;">
    <h3 style="margin:0 0 .75rem;font-size:.95rem;color:#475569;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">
        ⚡ Schnellzugriff
    </h3>
    <div style="display:flex;flex-wrap:wrap;gap:.6rem;">
        <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-companies'); ?>"
           class="btn btn-secondary btn-sm"
           style="display:inline-flex;align-items:center;gap:.4rem;">
            🏢 Unternehmens-Übersicht
            <?php if ($companiesCount > 0): ?>
            <span style="background:#eff6ff;color:#3b82f6;padding:.1rem .45rem;border-radius:10px;font-size:.72rem;font-weight:700;">
                <?php echo $companiesCount; ?>
            </span>
            <?php endif; ?>
        </a>
        <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-generator?tab=basic'); ?>"
           class="btn btn-secondary btn-sm">📝 Neues Profil</a>
        <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-libraries?tab=benefit-catalog'); ?>"
           class="btn btn-secondary btn-sm">🎁 Benefit-Katalog</a>
        <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-approvals'); ?>"
           class="btn btn-secondary btn-sm">✅ Genehmigungen</a>
        <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-workflow'); ?>"
           class="btn btn-secondary btn-sm">🔄 Workflow-Editor</a>
        <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-settings'); ?>"
           class="btn btn-secondary btn-sm">⚙️ Einstellungen</a>
    </div>
</div>

<!-- Tab-Navigation -->
<div class="jpg-tabs">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="?tab=<?php echo esc_attr($key); ?>"
       class="jpg-tab<?php echo $tab === $key ? ' active' : ''; ?>">
        <?php echo esc_html($label); ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;">

    <?php if ($tab === 'statistics'): ?>
    <h3>📈 Status-Verteilung</h3>
    <div style="max-width:480px;">
        <?php foreach ($statDef as $key => [$icon, $label]): ?>
        <?php $cnt = $stats[$key] ?? 0; $pct = $totalAll > 0 ? round($cnt / $totalAll * 100) : 0; ?>
        <div style="display:flex;align-items:center;gap:.75rem;margin:.75rem 0;">
            <div style="width:140px;font-size:.875rem;color:#475569;"><?php echo $icon . ' ' . $label; ?></div>
            <div style="flex:1;background:#f1f5f9;border-radius:4px;height:22px;overflow:hidden;">
                <div style="width:<?php echo $pct; ?>%;background:var(--admin-primary);height:100%;transition:width .4s;"></div>
            </div>
            <div style="width:36px;font-weight:700;font-size:.9rem;text-align:right;"><?php echo $cnt; ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php elseif (empty($profiles)): ?>
    <div class="empty-state">
        <p style="font-size:2.5rem;margin:0;">📭</p>
        <p><strong>Noch keine Profile in dieser Ansicht</strong></p>
        <p style="color:#64748b;">Erstelle dein erstes Job-Profil über den Button oben rechts.</p>
        <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-generator?tab=basic'); ?>"
           class="btn btn-primary" style="margin-top:1rem;">➕ Jetzt erstellen</a>
    </div>

    <?php else: ?>
    <h3><?php echo match($tab) {
        'drafts'    => '📝 Entwürfe',
        'published' => '✅ Veröffentlichte Profile',
        'archived'  => '📦 Archivierte Profile',
        default     => '🗂️ Zuletzt bearbeitet',
    }; ?></h3>
    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Titel</th><th>Kategorie</th><th>Typ</th><th>Status</th><th>Erstellt</th><th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $empTypes = ['fulltime'=>'Vollzeit','parttime'=>'Teilzeit','freelance'=>'Freelance','internship'=>'Praktikum','mini'=>'Minijob'];
            foreach ($profiles as $profile):
                $editUrl = '/admin/plugins/jpg-dashboard/jpg-generator?id=' . (int)$profile->id;
            ?>
            <tr>
                <td><a href="<?php echo esc_url($editUrl); ?>" style="font-weight:600;color:var(--admin-primary);">
                    <?php echo esc_html($profile->title); ?>
                </a></td>
                <td><?php echo esc_html($profile->category_name ?? '—'); ?></td>
                <td style="font-size:.82rem;color:#64748b;">
                    <?php echo esc_html($empTypes[$profile->employment_type] ?? $profile->employment_type); ?>
                </td>
                <td>
                    <span class="status-badge <?php echo $profile->status === 'published' ? 'active' : 'inactive'; ?>">
                        <?php echo match($profile->status){'published'=>'Veröffentlicht','draft'=>'Entwurf','archived'=>'Archiviert',default=>esc_html($profile->status)}; ?>
                    </span>
                </td>
                <td style="font-size:.82rem;color:#64748b;">
                    <?php echo esc_html(date('d.m.Y', strtotime($profile->created_at))); ?>
                </td>
                <td>
                    <div style="display:flex;gap:.35rem;">
                        <a href="<?php echo esc_url($editUrl); ?>" class="btn btn-sm btn-secondary" title="Bearbeiten">✏️</a>
                        <!-- Phase 14.2: 1-Click Duplizierer -->
                        <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-generator?action=duplicate&id=' . (int)$profile->id); ?>"
                           class="btn btn-sm btn-secondary" title="Als Entwurf duplizieren"
                           onclick="return confirm('Stelle als Entwurf duplizieren?')">🔁</a>
                        <button class="btn btn-sm btn-danger"
                                onclick="jpgConfirmDelete(<?php echo (int)$profile->id; ?>)" title="Löschen">🗑️</button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Lösch-Modal -->
<div id="jpgDeleteModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:420px;">
        <div class="modal-header">
            <h3>🗑️ Profil löschen</h3>
            <button class="modal-close"
                    onclick="document.getElementById('jpgDeleteModal').style.display='none'">&times;</button>
        </div>
        <div class="modal-body">
            <p>Soll dieses Profil wirklich unwiderruflich gelöscht werden?<br>
            Diese Aktion kann nicht rückgängig gemacht werden.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary"
                    onclick="document.getElementById('jpgDeleteModal').style.display='none'">Abbrechen</button>
            <a id="jpgDeleteConfirm" href="#" class="btn btn-danger">🗑️ Ja, löschen</a>
        </div>
    </div>
</div>
<script>
function jpgConfirmDelete(id) {
    document.getElementById('jpgDeleteConfirm').href =
        '/admin/plugins/jpg-dashboard/jpg-generator?_jpg_action=delete&id=' + id;
    document.getElementById('jpgDeleteModal').style.display = 'flex';
}
window.addEventListener('click', function(e) {
    var m = document.getElementById('jpgDeleteModal');
    if (m && e.target === m) m.style.display = 'none';
});
</script>
