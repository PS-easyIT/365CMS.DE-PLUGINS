<?php declare(strict_types=1); if (!defined('ABSPATH')) exit;
$e = fn(?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$statusMap = [
    'unread'   => ['label' => 'Ungelesen', 'class' => 'inactive'],
    'read'     => ['label' => 'Gelesen',   'class' => 'active'],
    'replied'  => ['label' => 'Beantw.',   'class' => 'active'],
    'archived' => ['label' => 'Archiviert','class' => 'inactive'],
    'spam'     => ['label' => 'Spam',      'class' => 'danger'],
];
?>

<?php include CMS_CONTACT_PLUGIN_DIR . 'admin/views/partial-section-nav.php'; ?>

<?php
$pageUnread = 0;
$pageSpam = 0;
$pageReplied = 0;
foreach ($submissions as $submissionItem) {
    $status = $submissionItem['status'] ?? 'unread';
    if ($status === 'unread') {
        $pageUnread++;
    }
    if ($status === 'spam') {
        $pageSpam++;
    }
    if ($status === 'replied') {
        $pageReplied++;
    }
}
?>

<div class="contact-admin-shell">

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>📩 Nachrichten</h2>
        <p><?php echo (int)$total; ?> Nachricht<?php echo $total !== 1 ? 'en' : ''; ?> insgesamt</p>
    </div>
</div>

<div class="contact-card-grid">
    <div class="contact-mini-card">
        <span class="contact-mini-card__label">Gesamt</span>
        <span class="contact-mini-card__value"><?php echo number_format((int) $total); ?></span>
        <span class="contact-mini-card__text">Nachrichten laut aktuellem Filter- und Datenstand.</span>
    </div>
    <div class="contact-mini-card">
        <span class="contact-mini-card__label">Ungelesen auf dieser Seite</span>
        <span class="contact-mini-card__value"><?php echo number_format($pageUnread); ?></span>
        <span class="contact-mini-card__text">Direkt sichtbare Nachrichten mit Priorität.</span>
    </div>
    <div class="contact-mini-card">
        <span class="contact-mini-card__label">Beantwortet</span>
        <span class="contact-mini-card__value"><?php echo number_format($pageReplied); ?></span>
        <span class="contact-mini-card__text">Bereits erledigte Konversationen in dieser Listenansicht.</span>
    </div>
    <div class="contact-mini-card">
        <span class="contact-mini-card__label">Spam</span>
        <span class="contact-mini-card__value"><?php echo number_format($pageSpam); ?></span>
        <span class="contact-mini-card__text">Auffällige Einsendungen für die schnelle Bereinigung.</span>
    </div>
</div>

<!-- Filter -->
<div class="contact-filter-card">
    <form method="GET" class="contact-filter-row">
        <input type="hidden" name="section" value="submissions">
        <div class="form-group">
            <label class="form-label" style="font-size:.8rem;">Formular</label>
            <select name="form_id" class="form-control" style="min-width:160px;">
                <option value="">Alle Formulare</option>
                <?php foreach ($forms as $f): ?>
                <option value="<?php echo (int)$f['id']; ?>" <?php echo $filterFormId === (int)$f['id'] ? 'selected' : ''; ?>>
                    <?php echo $e($f['title']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" style="font-size:.8rem;">Status</label>
            <select name="status" class="form-control" style="min-width:130px;">
                <option value="">Alle</option>
                <?php foreach ($statusMap as $key => $s): ?>
                <option value="<?php echo $key; ?>" <?php echo $filterStatus === $key ? 'selected' : ''; ?>><?php echo $s['label']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" style="font-size:.8rem;">Suche</label>
            <input type="text" name="search" class="form-control" style="min-width:180px;"
                   value="<?php echo $e($filterSearch); ?>" placeholder="Name / E-Mail ...">
        </div>
        <button type="submit" class="btn btn-secondary btn-sm">🔍 Filtern</button>
        <?php if ($filterFormId || $filterStatus || $filterSearch): ?>
        <a href="?section=submissions" class="btn btn-secondary btn-sm">✖ Zurücksetzen</a>
        <?php endif; ?>
    </form>
</div>

<!-- Bulk-Aktionen + Tabelle -->
<?php if (empty($submissions)): ?>
<div class="admin-card">
    <div class="empty-state">
        <p style="font-size:2.5rem;margin:0;">📭</p>
        <p><strong>Keine Nachrichten gefunden</strong></p>
        <p class="text-muted">Es wurden keine Nachrichten mit den gewählten Filtern gefunden.</p>
    </div>
</div>
<?php else: ?>
<form method="POST" id="bulkForm">
    <input type="hidden" name="sub_action" value="bulk_action">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

    <div class="contact-bulk-card">
        <div>
            <strong>Sammelaktionen</strong>
            <div class="contact-muted-text">Mehrere Nachrichten gleichzeitig markieren, prüfen oder löschen.</div>
        </div>
        <div class="contact-bulk-row">
        <select name="bulk" class="form-control" style="max-width:200px;">
            <option value="">Aktion wählen …</option>
            <option value="mark_read">✅ Als gelesen markieren</option>
            <option value="mark_spam">🚫 Als Spam markieren</option>
            <option value="delete">🗑️ Löschen</option>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">Ausführen</button>
        </div>
    </div>

    <div class="admin-card" style="padding:0;overflow:hidden;">
        <div class="users-table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th style="width:40px;"><input type="checkbox" id="selectAll"></th>
                        <th>Absender</th>
                        <th>Betreff</th>
                        <th>Formular</th>
                        <th>Status</th>
                        <th>Datum</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $sub): ?>
                    <?php
                        $meta = [];
                        foreach (($sub['_meta'] ?? []) as $m) {
                            $meta[$m['field_name']] = $m['field_value'];
                        }
                        $senderName  = $meta['name']    ?? $meta['sender_name'] ?? '—';
                        $senderEmail = $meta['email']   ?? $meta['sender_email'] ?? '';
                        $subject     = $meta['subject'] ?? $meta['betreff'] ?? '(kein Betreff)';
                        $st = $statusMap[$sub['status']] ?? $statusMap['unread'];
                    ?>
                    <tr style="<?php echo $sub['status'] === 'unread' ? 'font-weight:600;' : ''; ?>">
                        <td><input type="checkbox" name="submission_ids[]" value="<?php echo (int)$sub['id']; ?>"></td>
                        <td>
                            <?php echo $e($senderName); ?>
                            <?php if ($senderEmail): ?>
                            <div class="contact-table-meta"><?php echo $e($senderEmail); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $e(mb_strimwidth($subject, 0, 50, '…')); ?></td>
                        <td><span class="contact-muted-text"><?php echo $e($sub['form_title'] ?? '—'); ?></span></td>
                        <td><span class="status-badge <?php echo $st['class']; ?>"><?php echo $st['label']; ?></span></td>
                        <td style="white-space:nowrap;font-size:.85rem;"><?php echo date('d.m.Y H:i', strtotime($sub['created_at'])); ?></td>
                        <td>
                            <div class="contact-inline-actions">
                                <a href="?section=submissions&action=view&id=<?php echo (int)$sub['id']; ?>"
                                   class="btn btn-sm btn-secondary" title="Anzeigen">👁️</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<!-- Paginierung -->
<?php if ($pages > 1): ?>
<div style="display:flex;gap:.5rem;justify-content:center;margin-top:1.5rem;flex-wrap:wrap;">
    <?php if ($page > 1): ?>
    <a href="?section=submissions&paged=<?php echo $page - 1; ?>&form_id=<?php echo $filterFormId; ?>&status=<?php echo $e($filterStatus); ?>&search=<?php echo urlencode($filterSearch); ?>"
       class="btn btn-secondary btn-sm">← Zurück</a>
    <?php endif; ?>
    <span style="padding:.375rem .875rem;color:#64748b;font-size:.875rem;">
        Seite <?php echo $page; ?> von <?php echo $pages; ?>
    </span>
    <?php if ($page < $pages): ?>
    <a href="?section=submissions&paged=<?php echo $page + 1; ?>&form_id=<?php echo $filterFormId; ?>&status=<?php echo $e($filterStatus); ?>&search=<?php echo urlencode($filterSearch); ?>"
       class="btn btn-secondary btn-sm">Weiter →</a>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

</div>

<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('input[name="submission_ids[]"]').forEach(cb => cb.checked = this.checked);
});
</script>
