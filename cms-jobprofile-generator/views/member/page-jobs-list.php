<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
/**
 * Member-View: Meine Stellenanzeigen – Liste
 *
 * @var array<object>    $profiles       Profil-Liste
 * @var bool             $canCreate      Darf neue Stellen anlegen?
 * @var string           $csrf           CSRF-Token
 * @var string           $baseUrl        Basis-URL
 * @var string|null      $createUrl      URL zum Erstellen
 * @var string|null      $error          Fehler-Meldung
 * @var string           $bulkNotice     Feedback nach Bulk-Aktion
 * @var array            $appCountMap    job_id → Bewerbungen (30 Tage)
 * @var array            $appTrend7      date → Bewerbungen (7 Tage)
 * @var int              $quotaUsed      Genutzte Stellen
 * @var int              $quotaLimit     Limit im Abo-Plan (-1 = unbegrenzt)
 * @var int              $totalViews     Gesamte Aufrufe
 * @var int              $totalApps      Bewerbungen letzte 30 Tage
 */
$esc     = fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
$baseUrl = $baseUrl ?? '/member/jobs';

// Workflow-Status-Mapping
$wfLabels = [
    'none'     => ['label' => '',              'class' => ''],
    'pending'  => ['label' => '⏳ Ausstehend',  'class' => 'inactive'],
    'approved' => ['label' => '✅ Genehmigt',   'class' => 'active'],
    'rejected' => ['label' => '❌ Abgelehnt',   'class' => 'danger'],
];

// Analytics-Variablen mit Fallback
$appCountMap = $appCountMap ?? [];
$appTrend7   = $appTrend7   ?? [];
$quotaUsed   = $quotaUsed   ?? count($profiles ?? []);
$quotaLimit  = $quotaLimit  ?? -1;
$totalViews  = $totalViews  ?? 0;
$totalApps   = $totalApps   ?? 0;
$bulkNotice  = $bulkNotice  ?? '';

// Phase 12.2: Radial-Progress Rechnung
$quotaPct = ($quotaLimit > 0) ? min(100, (int)round($quotaUsed / $quotaLimit * 100)) : 0;
$circleR  = 28; $circleC = 2 * M_PI * $circleR;
$dashVal  = round($circleC * $quotaPct / 100, 2);
$dashOff  = round($circleC, 2);

// Phase 12.1: Trend-Balken (max-Wert für Skalierung)
$maxTrend = max(1, max($appTrend7 ?: [0]));

// Trend: letzte 7 Tage als Array aufbauen
$trendDays = [];
for ($i = 6; $i >= 0; $i--) {
    $d            = date('Y-m-d', strtotime("-{$i} days"));
    $trendDays[]  = ['date' => date('d.m', strtotime($d)), 'cnt' => $appTrend7[$d] ?? 0];
}
?>

<?php if ($bulkNotice !== ''): ?>
<div class="alert alert-success" style="margin-bottom:1rem;">✅ <?php echo $esc($bulkNotice); ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="alert alert-error" style="margin-bottom:1rem;">❌ <?php echo $esc($error); ?></div>
<?php endif; ?>

<?php if (function_exists('display_resource_limit_warning')): ?>
<?php display_resource_limit_warning('job_profiles', 'Stellenanzeigen'); ?>
<?php endif; ?>

<!-- ══════════ Phase 12: Analytics + Quota ══════════ -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem;">

    <!-- Views -->
    <div class="admin-card" style="padding:1.25rem;text-align:center;margin-bottom:0;">
        <div style="font-size:1.75rem;font-weight:700;color:#3b82f6;"><?php echo number_format($totalViews); ?></div>
        <div style="font-size:.8rem;color:#64748b;margin-top:.25rem;">👁️ Gesamt-Aufrufe</div>
    </div>

    <!-- Bewerbungen (30 Tage) -->
    <div class="admin-card" style="padding:1.25rem;text-align:center;margin-bottom:0;">
        <div style="font-size:1.75rem;font-weight:700;color:#10b981;"><?php echo $totalApps; ?></div>
        <div style="font-size:.8rem;color:#64748b;margin-top:.25rem;">📬 Bewerbungen (30 Tage)</div>
        <?php if ($totalViews > 0 && $totalApps > 0): ?>
        <div style="font-size:.75rem;color:#94a3b8;margin-top:.2rem;">
            Conversion: <?php echo round($totalApps / $totalViews * 100, 1); ?>%
        </div>
        <?php endif; ?>
    </div>

    <!-- Quota-Widget -->
    <div class="admin-card" style="padding:1.25rem;text-align:center;margin-bottom:0;">
        <?php if ($quotaLimit > 0): ?>
        <div style="display:inline-block;position:relative;">
            <svg width="72" height="72" viewBox="0 0 72 72">
                <circle cx="36" cy="36" r="<?php echo $circleR; ?>"
                        fill="none" stroke="#e2e8f0" stroke-width="6"/>
                <circle cx="36" cy="36" r="<?php echo $circleR; ?>"
                        fill="none" stroke="<?php echo $quotaPct >= 90 ? '#ef4444' : '#3b82f6'; ?>" stroke-width="6"
                        stroke-dasharray="<?php echo $dashVal; ?> <?php echo $dashOff; ?>"
                        stroke-linecap="round"
                        transform="rotate(-90 36 36)"/>
                <text x="36" y="41" text-anchor="middle"
                      style="font-size:13px;font-weight:700;fill:#1e293b;"><?php echo $quotaPct; ?>%</text>
            </svg>
        </div>
        <div style="font-size:.8rem;color:#64748b;margin-top:.25rem;">
            📦 <?php echo $quotaUsed; ?> / <?php echo $quotaLimit; ?> Stellen
            <?php if ($quotaPct >= 90): ?>
            <div style="color:#ef4444;font-size:.75rem;margin-top:.15rem;">⚠️ Limit fast erreicht</div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div style="font-size:1.75rem;font-weight:700;color:#3b82f6;"><?php echo $quotaUsed; ?></div>
        <div style="font-size:.8rem;color:#64748b;margin-top:.25rem;">📦 Stellen angelegt</div>
        <?php endif; ?>
    </div>

    <!-- Bewerber-Trend 7 Tage -->
    <div class="admin-card" style="padding:1.25rem;margin-bottom:0;">
        <div style="font-size:.75rem;font-weight:600;color:#475569;margin-bottom:.6rem;">📈 Trend (7 Tage)</div>
        <div style="display:flex;align-items:flex-end;gap:3px;height:40px;">
            <?php foreach ($trendDays as $td): ?>
            <?php $barH = $maxTrend > 0 ? max(3, (int)round($td['cnt'] / $maxTrend * 40)) : 3; ?>
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;">
                <div style="width:100%;height:<?php echo $barH; ?>px;background:<?php echo $td['cnt'] > 0 ? '#3b82f6' : '#e2e8f0'; ?>;border-radius:2px;margin-top:<?php echo 40 - $barH; ?>px;" title="<?php echo $td['date']; ?>: <?php echo $td['cnt']; ?>"></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.65rem;color:#94a3b8;margin-top:.3rem;">
            <span><?php echo $trendDays[0]['date'] ?? ''; ?></span>
            <span><?php echo $trendDays[6]['date'] ?? ''; ?></span>
        </div>
    </div>
</div>

<!-- Header: Neue Stelle + Bulk-Aktion -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;">
    <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
        <?php if ($canCreate): ?>
        <a href="<?php echo $esc($createUrl ?? ($baseUrl . '/create')); ?>" class="btn btn-primary">➕ Neue Stelle</a>
        <?php elseif (function_exists('display_upgrade_notice')): ?>
        <?php display_upgrade_notice('Upgrade für weitere Stellen'); ?>
        <?php endif; ?>
    </div>
    <?php if (!empty($profiles)): ?>
    <div style="display:flex;gap:.5rem;align-items:center;" id="jpgBulkBar" style="display:none;">
        <select id="jpgBulkSelect" class="form-control" style="width:auto;padding:.375rem .75rem;font-size:.875rem;">
            <option value="">Bulk-Aktion …</option>
            <option value="archive">📦 Archivieren</option>
            <option value="delete">🗑️ Löschen</option>
        </select>
        <button type="button" class="btn btn-secondary btn-sm" onclick="jpgBulkSubmit()">Ausführen</button>
        <span id="jpgBulkCount" style="font-size:.8rem;color:#64748b;"></span>
    </div>
    <?php endif; ?>
</div>

<!-- Bulk-Formular + Tabelle -->
<form method="POST" action="<?php echo $esc($baseUrl); ?>" id="jpgBulkForm">
    <input type="hidden" name="_jpg_csrf"    value="<?php echo $esc($csrf); ?>">
    <input type="hidden" name="bulk_action" id="jpgBulkAction" value="">

<?php if (empty($profiles)): ?>
<div class="admin-card" style="text-align:center;padding:3rem;">
    <p style="font-size:2.5rem;margin:0;">📭</p>
    <p><strong>Noch keine Stellenanzeigen vorhanden</strong></p>
    <p style="color:#64748b;font-size:.875rem;">Erstelle dein erstes Job-Profil und erreiche passende Kandidaten.</p>
    <?php if ($canCreate): ?>
    <a href="<?php echo $esc($createUrl ?? ($baseUrl . '/create')); ?>" class="btn btn-primary" style="margin-top:1rem;">➕ Jetzt erstellen</a>
    <?php endif; ?>
</div>
<?php else: ?>
<div style="overflow-x:auto;background:#fff;border:1px solid #e2e8f0;border-radius:10px;">
    <table style="width:100%;border-collapse:collapse;">
        <thead>
            <tr style="background:#f8fafc;">
                <th style="padding:.75rem;width:36px;">
                    <input type="checkbox" id="jpgSelectAll" title="Alle auswählen"
                           onchange="jpgToggleAll(this)">
                </th>
                <th style="padding:1rem;text-align:left;font-size:.875rem;font-weight:600;color:#475569;">Stellentitel</th>
                <th style="padding:1rem;text-align:left;font-size:.875rem;font-weight:600;color:#475569;">Status</th>
                <th style="padding:1rem;text-align:left;font-size:.875rem;font-weight:600;color:#475569;">Freigabe</th>
                <th style="padding:1rem;text-align:left;font-size:.875rem;font-weight:600;color:#475569;">Standort</th>
                <th style="padding:1rem;text-align:right;font-size:.875rem;font-weight:600;color:#475569;">Views</th>
                <th style="padding:1rem;text-align:right;font-size:.875rem;font-weight:600;color:#475569;">Bew.</th>
                <th style="padding:1rem;text-align:center;font-size:.875rem;font-weight:600;color:#475569;">Aktionen</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($profiles as $p): ?>
        <?php
            $statusMap = [
                'draft'     => ['label' => 'Entwurf',    'class' => 'inactive'],
                'published' => ['label' => 'Aktiv',       'class' => 'active'],
                'archived'  => ['label' => 'Archiv',      'class' => 'inactive'],
                'trash'     => ['label' => 'Papierkorb',  'class' => 'inactive'],
            ];
            $st        = $statusMap[$p->status] ?? ['label' => $esc($p->status), 'class' => 'inactive'];
            $wfStatus  = $p->workflow_status ?? 'none';
            $appCnt    = $appCountMap[(int)$p->id] ?? 0;
        ?>
        <tr style="border-top:1px solid #f1f5f9;">
            <td style="padding:.75rem;text-align:center;">
                <input type="checkbox" name="profile_ids[]" value="<?php echo (int)$p->id; ?>"
                       class="jpg-bulk-cb" onchange="jpgBulkCount()">
            </td>
            <td style="padding:1rem;">
                <strong style="color:#1e293b;"><?php echo $esc($p->title); ?></strong>
                <div style="font-size:.8rem;color:#94a3b8;margin-top:.15rem;"><?php echo $esc(date('d.m.Y', strtotime($p->created_at))); ?></div>
            </td>
            <td style="padding:1rem;">
                <span class="status-badge <?php echo $st['class']; ?>"><?php echo $st['label']; ?></span>
            </td>
            <td style="padding:1rem;">
                <?php if (!empty($wfLabels[$wfStatus]['label'])): ?>
                <span class="status-badge <?php echo $wfLabels[$wfStatus]['class']; ?>">
                    <?php echo $wfLabels[$wfStatus]['label']; ?>
                </span>
                <?php else: ?>
                <span style="color:#94a3b8;font-size:.8rem;">–</span>
                <?php endif; ?>
            </td>
            <td style="padding:1rem;color:#64748b;font-size:.875rem;"><?php echo empty($p->location) ? '–' : $esc($p->location); ?></td>
            <td style="padding:1rem;text-align:right;color:#64748b;font-size:.875rem;"><?php echo (int)$p->views; ?></td>
            <td style="padding:1rem;text-align:right;">
                <?php if ($appCnt > 0): ?>
                <a href="<?php echo $esc(str_replace('/plugin/member-jobs', '/jobs', $baseUrl) . '/applications?job_id=' . (int)$p->id); ?>"
                   style="color:#10b981;font-weight:600;font-size:.875rem;text-decoration:none;">
                    <?php echo $appCnt; ?>
                </a>
                <?php else: ?>
                <span style="color:#94a3b8;font-size:.875rem;">0</span>
                <?php endif; ?>
            </td>
            <td style="padding:1rem;text-align:center;">
                <div style="display:flex;gap:.3rem;justify-content:center;flex-wrap:wrap;">
                    <?php $editUrl = str_contains($baseUrl, '?') ? $baseUrl . '&action=edit&id=' . (int)$p->id : $baseUrl . '/edit/' . (int)$p->id; ?>
                    <a href="<?php echo $esc($editUrl); ?>"
                       class="btn btn-sm btn-secondary" title="Bearbeiten">✏️</a>
                    <?php if ($p->status === 'published'): ?>
                    <a href="/jobs/<?php echo $esc($p->slug ?? ''); ?>"
                       target="_blank" class="btn btn-sm btn-secondary" title="Ansehen">👁️</a>
                    <?php endif; ?>
                    <!-- Phase 14.2: 1-Click Duplizierer -->
                    <?php $dupUrl = str_contains($baseUrl, '?') ? $baseUrl . '&action=duplicate&id=' . (int)$p->id : $baseUrl . '/duplicate/' . (int)$p->id; ?>
                    <a href="<?php echo $esc($dupUrl); ?>"
                       class="btn btn-sm btn-secondary" title="Als Entwurf duplizieren"
                       onclick="return confirm('Stelle als Entwurf duplizieren?')">🔁</a>
                    <form method="post" action="<?php echo $esc($editUrl); ?>" style="display:inline;">
                        <input type="hidden" name="_jpg_csrf" value="<?php echo $esc($csrf); ?>">
                        <input type="hidden" name="action"   value="delete">
                        <button type="submit" class="btn btn-sm btn-danger" title="Löschen"
                                onclick="return confirm('Stelle wirklich löschen?')">🗑️</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
</form>

<script>
// Phase 13.2: Bulk-Aktionen
function jpgToggleAll(cb) {
    document.querySelectorAll('.jpg-bulk-cb').forEach(c => c.checked = cb.checked);
    jpgBulkCount();
}
function jpgBulkCount() {
    const n = document.querySelectorAll('.jpg-bulk-cb:checked').length;
    const bar = document.getElementById('jpgBulkBar');
    document.getElementById('jpgBulkCount').textContent = n > 0 ? n + ' ausgewählt' : '';
    if (bar) bar.style.display = n > 0 ? 'flex' : 'none';
}
function jpgBulkSubmit() {
    const action = document.getElementById('jpgBulkSelect').value;
    const checked = document.querySelectorAll('.jpg-bulk-cb:checked').length;
    if (!action) { alert('Bitte eine Aktion wählen.'); return; }
    if (!checked) { alert('Bitte mindestens eine Stelle auswählen.'); return; }
    if (action === 'delete' && !confirm(checked + ' Stelle(n) wirklich löschen?')) return;
    document.getElementById('jpgBulkAction').value = action;
    document.getElementById('jpgBulkForm').submit();
}
</script>
