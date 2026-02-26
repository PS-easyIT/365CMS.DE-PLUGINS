<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
/**
 * Member-View: Bewerbungs-Postfach
 *
 * @var array<object> $applications
 * @var array<object> $myJobs
 * @var int           $jobId        (aktiver Filter)
 * @var string        $csrf
 */
$esc        = fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
$statusMap  = [
    'new'       => ['label'=>'Neu',          'bg'=>'#dbeafe','color'=>'#1e40af'],
    'reviewing' => ['label'=>'In Prüfung',   'bg'=>'#fef3c7','color'=>'#92400e'],
    'accepted'  => ['label'=>'Angenommen',   'bg'=>'#d1fae5','color'=>'#065f46'],
    'rejected'  => ['label'=>'Abgelehnt',    'bg'=>'#fee2e2','color'=>'#991b1b'],
];
?>
<div style="margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem;">
    <div>
        <h2 style="margin:0 0 .25rem;font-size:1.375rem;font-weight:700;color:#1e293b;">📬 Bewerbungs-Postfach</h2>
        <p style="margin:0;color:#64748b;font-size:.875rem;"><?php echo count($applications); ?> Bewerbung(en)<?php echo $jobId > 0 ? ' für diese Stelle' : ''; ?></p>
    </div>
    <?php if (!empty($myJobs)): ?>
    <div>
        <select class="form-control" style="font-size:.875rem;" onchange="window.location='/member/jobs/applications'+(this.value>0?'?job_id='+this.value:'')">
            <option value="0" <?php echo $jobId === 0 ? 'selected' : ''; ?>>Alle Stellen</option>
            <?php foreach ($myJobs as $j): ?>
            <option value="<?php echo (int)$j->id; ?>" <?php echo $jobId === (int)$j->id ? 'selected' : ''; ?>>
                <?php echo $esc($j->title); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
</div>

<?php if (empty($applications)): ?>
<div style="text-align:center;padding:3rem;background:#fff;border:1px solid #e2e8f0;border-radius:10px;">
    <p style="font-size:2.5rem;margin:0;">📭</p>
    <p><strong>Noch keine Bewerbungen eingegangen</strong></p>
    <p style="color:#64748b;font-size:.875rem;">Sobald sich jemand auf deine Stellen bewirbt, erscheinen die Bewerbungen hier.</p>
</div>
<?php else: ?>
<div style="overflow-x:auto;background:#fff;border:1px solid #e2e8f0;border-radius:10px;">
    <table style="width:100%;border-collapse:collapse;" id="jpgAppTable">
        <thead>
            <tr style="background:#f8fafc;">
                <th style="padding:1rem;text-align:left;font-size:.875rem;font-weight:600;color:#475569;">Bewerber</th>
                <th style="padding:1rem;text-align:left;font-size:.875rem;font-weight:600;color:#475569;">Stelle</th>
                <th style="padding:1rem;text-align:left;font-size:.875rem;font-weight:600;color:#475569;">Datum</th>
                <th style="padding:1rem;text-align:center;font-size:.875rem;font-weight:600;color:#475569;">Status</th>
                <th style="padding:1rem;text-align:center;font-size:.875rem;font-weight:600;color:#475569;">Aktionen</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($applications as $app): ?>
        <?php
        $st   = $statusMap[$app->status] ?? ['label'=>$esc($app->status),'bg'=>'#f1f5f9','color'=>'#475569'];
        $date = date('d.m.Y', strtotime($app->created_at));
        ?>
        <tr style="border-top:1px solid #f1f5f9;" data-app-id="<?php echo (int)$app->id; ?>">
            <td style="padding:1rem;">
                <strong><?php echo $esc($app->applicant_name); ?></strong>
                <div style="font-size:.8rem;color:#94a3b8;"><?php echo $esc($app->applicant_email); ?></div>
            </td>
            <td style="padding:1rem;font-size:.875rem;color:#475569;"><?php echo $esc($app->job_title); ?></td>
            <td style="padding:1rem;font-size:.875rem;color:#64748b;"><?php echo $esc($date); ?></td>
            <td style="padding:1rem;text-align:center;">
                <span class="jpg-app-status-badge"
                      style="background:<?php echo $st['bg']; ?>;color:<?php echo $st['color']; ?>;border-radius:4px;padding:.2rem .6rem;font-size:.8rem;font-weight:600;">
                    <?php echo $st['label']; ?>
                </span>
            </td>
            <td style="padding:1rem;text-align:center;">
                <div style="display:flex;gap:.35rem;justify-content:center;flex-wrap:wrap;">
                    <!-- Status-Änderung -->
                    <select class="form-control jpg-status-select"
                            style="font-size:.8rem;padding:.25rem .5rem;min-width:120px;"
                            data-app-id="<?php echo (int)$app->id; ?>"
                            data-csrf="<?php echo $esc($csrf); ?>">
                        <option value="new"       <?php echo $app->status === 'new' ? 'selected' : ''; ?>>Neu</option>
                        <option value="reviewing" <?php echo $app->status === 'reviewing' ? 'selected' : ''; ?>>In Prüfung</option>
                        <option value="accepted"  <?php echo $app->status === 'accepted' ? 'selected' : ''; ?>>Angenommen</option>
                        <option value="rejected"  <?php echo $app->status === 'rejected' ? 'selected' : ''; ?>>Abgelehnt</option>
                    </select>
                    <?php if (!empty($app->cv_file_token)): ?>
                    <a href="/member/jobs/download/<?php echo $esc($app->cv_file_token); ?>"
                       class="btn btn-sm btn-secondary" title="CV herunterladen">📎 CV</a>
                    <?php endif; ?>
                    <?php if (!empty($app->cover_letter)): ?>
                    <button class="btn btn-sm btn-secondary"
                            onclick="jpgShowLetter(<?php echo htmlspecialchars(json_encode($app->cover_letter), ENT_QUOTES); ?>)"
                            title="Anschreiben lesen">📄</button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Cover-Letter-Modal -->
<div id="jpgLetterModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;max-width:600px;width:90%;max-height:80vh;overflow-y:auto;padding:2rem;position:relative;">
        <button onclick="document.getElementById('jpgLetterModal').style.display='none'"
                style="position:absolute;top:1rem;right:1rem;background:none;border:none;font-size:1.5rem;cursor:pointer;color:#64748b;">&times;</button>
        <h3 style="margin-top:0;">📄 Anschreiben</h3>
        <div id="jpgLetterContent" style="color:#1e293b;line-height:1.65;"></div>
    </div>
</div>

<script>
// Status-Änderung per AJAX
document.querySelectorAll('.jpg-status-select').forEach(function(sel) {
    sel.addEventListener('change', async function() {
        var appId  = this.dataset.appId;
        var status = this.value;
        var csrf   = this.dataset.csrf;
        var row    = this.closest('tr');

        var fd = new FormData();
        fd.append('application_id', appId);
        fd.append('status',         status);
        fd.append('_jpg_csrf',      csrf);

        try {
            var res  = await fetch('/member/jobs/applications/status', { method: 'POST', body: fd });
            var data = await res.json();
            if (data.success) {
                var colors = {
                    new:       { bg: '#dbeafe', color: '#1e40af', label: 'Neu' },
                    reviewing: { bg: '#fef3c7', color: '#92400e', label: 'In Prüfung' },
                    accepted:  { bg: '#d1fae5', color: '#065f46', label: 'Angenommen' },
                    rejected:  { bg: '#fee2e2', color: '#991b1b', label: 'Abgelehnt' },
                };
                var c = colors[status] || { bg: '#f1f5f9', color: '#475569', label: status };
                var badge = row.querySelector('.jpg-app-status-badge');
                if (badge) {
                    badge.style.background = c.bg;
                    badge.style.color      = c.color;
                    badge.textContent      = c.label;
                }
            } else {
                alert('Fehler: ' + (data.error || 'Unbekannt'));
                this.value = this.dataset.prev || 'new';
            }
        } catch(e) {
            alert('Netzwerkfehler: ' + e.message);
        }
    });
    sel.addEventListener('focus', function() { this.dataset.prev = this.value; });
});

function jpgShowLetter(html) {
    document.getElementById('jpgLetterContent').innerHTML = html;
    document.getElementById('jpgLetterModal').style.display = 'flex';
}
window.addEventListener('click', function(e) {
    var m = document.getElementById('jpgLetterModal');
    if (e.target === m) m.style.display = 'none';
});
</script>
