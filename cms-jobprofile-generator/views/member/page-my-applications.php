<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
/**
 * Member-View: Meine Bewerbungen (Bewerber-Sicht)
 *
 * Zeigt dem eingeloggten User seine eigenen abgegebenen Bewerbungen mit Status-Tracking.
 *
 * @var array<object> $myApplications
 * @var string        $statusFilter
 * @var array{total: int, new: int, reviewing: int, accepted: int, rejected: int} $stats
 */
$esc = fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);

$statusMap = [
    'new'       => ['label' => 'Gesendet',    'bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => '📨'],
    'reviewing' => ['label' => 'In Prüfung',  'bg' => '#fef3c7', 'color' => '#92400e', 'icon' => '🔍'],
    'accepted'  => ['label' => 'Angenommen',   'bg' => '#d1fae5', 'color' => '#065f46', 'icon' => '✅'],
    'rejected'  => ['label' => 'Abgelehnt',    'bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => '❌'],
];

$employmentLabels = [
    'full-time'  => 'Vollzeit',
    'part-time'  => 'Teilzeit',
    'contract'   => 'Befristet',
    'freelance'  => 'Freiberuflich',
    'internship' => 'Praktikum',
    'mini-job'   => 'Minijob',
    'working-student' => 'Werkstudent',
];

$siteUrl = defined('SITE_URL') ? SITE_URL : '';
?>

<!-- ── Seiten-Header ──────────────────────────────────────────────── -->
<div style="margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem;">
    <div>
        <h2 style="margin:0 0 .25rem;font-size:1.375rem;font-weight:700;color:#1e293b;">📋 Meine Bewerbungen</h2>
        <p style="margin:0;color:#64748b;font-size:.875rem;">
            Übersicht deiner abgeschickten Bewerbungen und deren aktueller Status
        </p>
    </div>
</div>

<!-- ── Statistik-Kacheln ──────────────────────────────────────────── -->
<?php if ($stats['total'] > 0): ?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1.5rem;">
    <a href="?<?php echo isset($baseUrl) ? '' : ''; ?>"
       style="text-decoration:none;background:#fff;border:1px solid <?php echo $statusFilter === '' ? '#3b82f6' : '#e2e8f0'; ?>;border-radius:10px;padding:1rem;text-align:center;transition:all .2s;">
        <div style="font-size:1.5rem;font-weight:700;color:#1e293b;"><?php echo $stats['total']; ?></div>
        <div style="font-size:.8rem;color:#64748b;">Gesamt</div>
    </a>
    <?php foreach (['new' => 'Gesendet', 'reviewing' => 'In Prüfung', 'accepted' => 'Angenommen', 'rejected' => 'Abgelehnt'] as $key => $lbl): ?>
    <?php if ($stats[$key] > 0): ?>
    <a href="?status=<?php echo $key; ?>"
       style="text-decoration:none;background:<?php echo $statusMap[$key]['bg']; ?>;border:1px solid <?php echo $statusFilter === $key ? $statusMap[$key]['color'] : 'transparent'; ?>;border-radius:10px;padding:1rem;text-align:center;transition:all .2s;">
        <div style="font-size:1.5rem;font-weight:700;color:<?php echo $statusMap[$key]['color']; ?>;"><?php echo $stats[$key]; ?></div>
        <div style="font-size:.8rem;color:<?php echo $statusMap[$key]['color']; ?>;"><?php echo $lbl; ?></div>
    </a>
    <?php endif; ?>
    <?php endforeach; ?>
</div>

<?php if ($statusFilter !== ''): ?>
<div style="margin-bottom:1rem;">
    <a href="<?php echo isset($baseUrl) ? $baseUrl . '?action=my-applications' : '/member/jobs/my-applications'; ?>"
       style="font-size:.85rem;color:#3b82f6;text-decoration:none;">← Alle Bewerbungen anzeigen</a>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- ── Bewerbungs-Liste ──────────────────────────────────────────── -->
<?php if (empty($myApplications)): ?>
<div style="text-align:center;padding:3rem;background:#fff;border:1px solid #e2e8f0;border-radius:10px;">
    <p style="font-size:2.5rem;margin:0;">📭</p>
    <p><strong><?php echo $statusFilter !== '' ? 'Keine Bewerbungen mit diesem Status' : 'Du hast noch keine Bewerbungen abgeschickt'; ?></strong></p>
    <p style="color:#64748b;font-size:.875rem;">
        <?php if ($statusFilter !== ''): ?>
            Versuche einen anderen Filter oder zeige alle Bewerbungen an.
        <?php else: ?>
            Sobald du dich auf offene Stellen bewirbst, erscheinen deine Bewerbungen hier.
        <?php endif; ?>
    </p>
    <?php if ($statusFilter === ''): ?>
    <a href="<?php echo $siteUrl; ?>/jobs" class="btn btn-primary" style="margin-top:1rem;">🔍 Offene Stellen durchsuchen</a>
    <?php else: ?>
    <a href="<?php echo isset($baseUrl) ? $baseUrl . '?action=my-applications' : '/member/jobs/my-applications'; ?>"
       class="btn btn-secondary" style="margin-top:1rem;">← Alle anzeigen</a>
    <?php endif; ?>
</div>

<?php else: ?>

<!-- ── Karten-Layout (Responsive) ─────────────────────────────────── -->
<div style="display:flex;flex-direction:column;gap:1rem;">
    <?php foreach ($myApplications as $app): ?>
    <?php
        $st = $statusMap[$app->status] ?? ['label' => $esc($app->status), 'bg' => '#f1f5f9', 'color' => '#475569', 'icon' => '📄'];
        $dateApplied = date('d.m.Y', strtotime($app->created_at));
        $timeApplied = date('H:i', strtotime($app->created_at));
        $lastUpdate  = $app->updated_at ? date('d.m.Y H:i', strtotime($app->updated_at)) : null;
        $jobUrl      = !empty($app->job_slug) ? $siteUrl . '/jobs/' . $esc($app->job_slug) : '';
        $jobActive   = ($app->job_status ?? '') === 'published';
        $empLabel    = $employmentLabels[$app->employment_type ?? ''] ?? '';
        $location    = $app->location_city ?? '';
    ?>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:1.25rem;transition:box-shadow .2s;border-left:4px solid <?php echo $st['color']; ?>;">
        <!-- Kopfzeile: Jobtitel + Status -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.5rem;margin-bottom:.75rem;">
            <div style="flex:1;min-width:200px;">
                <?php if ($jobUrl && $jobActive): ?>
                <a href="<?php echo $jobUrl; ?>" target="_blank" rel="noopener"
                   style="font-size:1.1rem;font-weight:700;color:#1e293b;text-decoration:none;display:inline-block;">
                    <?php echo $esc($app->job_title); ?> ↗
                </a>
                <?php else: ?>
                <span style="font-size:1.1rem;font-weight:700;color:#1e293b;">
                    <?php echo $esc($app->job_title); ?>
                </span>
                <?php if (!$jobActive): ?>
                <span style="font-size:.75rem;color:#94a3b8;margin-left:.5rem;">(nicht mehr aktiv)</span>
                <?php endif; ?>
                <?php endif; ?>

                <!-- Meta-Infos -->
                <div style="display:flex;flex-wrap:wrap;gap:.75rem;margin-top:.35rem;font-size:.82rem;color:#64748b;">
                    <?php if ($location): ?>
                    <span>📍 <?php echo $esc($location); ?></span>
                    <?php endif; ?>
                    <?php if ($empLabel): ?>
                    <span>⏱️ <?php echo $esc($empLabel); ?></span>
                    <?php endif; ?>
                    <span>📅 Beworben am <?php echo $dateApplied; ?> um <?php echo $timeApplied; ?></span>
                </div>
            </div>

            <!-- Status-Badge -->
            <div>
                <span style="display:inline-flex;align-items:center;gap:.35rem;background:<?php echo $st['bg']; ?>;color:<?php echo $st['color']; ?>;padding:.35rem .75rem;border-radius:6px;font-size:.82rem;font-weight:600;">
                    <?php echo $st['icon']; ?> <?php echo $st['label']; ?>
                </span>
            </div>
        </div>

        <!-- Detail-Zeile -->
        <div style="display:flex;flex-wrap:wrap;gap:1.5rem;font-size:.85rem;color:#475569;">
            <?php if (!empty($app->cover_letter)): ?>
            <details style="flex:1;min-width:250px;">
                <summary style="cursor:pointer;color:#3b82f6;font-weight:500;">📝 Anschreiben anzeigen</summary>
                <div style="margin-top:.5rem;padding:.75rem;background:#f8fafc;border-radius:8px;white-space:pre-wrap;font-size:.84rem;color:#1e293b;max-height:200px;overflow-y:auto;">
<?php echo $esc($app->cover_letter); ?>
                </div>
            </details>
            <?php endif; ?>

            <?php if (!empty($app->cv_file_token)): ?>
            <div>
                <span style="color:#64748b;">📎 Lebenslauf:</span>
                <span style="color:#10b981;font-weight:500;">Hochgeladen</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Letztes Update -->
        <?php if ($lastUpdate && $app->status !== 'new'): ?>
        <div style="margin-top:.75rem;padding-top:.75rem;border-top:1px solid #f1f5f9;font-size:.8rem;color:#94a3b8;">
            Letztes Update: <?php echo $lastUpdate; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>
