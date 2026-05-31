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
            <label class="form-label contact-filter-label">Formular</label>
            <select name="form_id" class="form-control contact-filter-select contact-filter-select--form">
                <option value="">Alle Formulare</option>
                <?php foreach ($forms as $f): ?>
                <option value="<?php echo (int)$f['id']; ?>" <?php echo $filterFormId === (int)$f['id'] ? 'selected' : ''; ?>>
                    <?php echo $e($f['title']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label contact-filter-label">Status</label>
            <select name="status" class="form-control contact-filter-select contact-filter-select--status">
                <option value="">Alle</option>
                <?php foreach ($statusMap as $key => $s): ?>
                <option value="<?php echo $e($key); ?>" <?php echo $filterStatus === $key ? 'selected' : ''; ?>><?php echo $e($s['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label contact-filter-label">Suche</label>
            <input type="text" name="search" class="form-control contact-filter-input contact-filter-input--search"
                   value="<?php echo $e($filterSearch); ?>" placeholder="Name / E-Mail / IP ...">
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
        <p class="contact-empty-state__icon">📭</p>
        <p><strong>Keine Nachrichten gefunden</strong></p>
        <p class="text-muted">Es wurden keine Nachrichten mit den gewählten Filtern gefunden.</p>
    </div>
</div>
<?php else: ?>
<form method="POST" id="bulkForm">
    <input type="hidden" name="sub_action" value="bulk_action">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

    <div class="contact-bulk-card">
        <div>
            <strong>Sammelaktionen</strong>
            <div class="contact-muted-text">Mehrere Nachrichten gleichzeitig markieren, prüfen oder löschen.</div>
        </div>
        <div class="contact-bulk-row">
            <select name="bulk" class="form-control contact-bulk-select">
                <option value="">Aktion wählen …</option>
                <option value="mark_read">✅ Als gelesen markieren</option>
                <option value="mark_spam">🚫 Als Spam markieren</option>
                <option value="delete">🗑️ Löschen</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Ausführen</button>
        </div>
    </div>

    <div class="admin-card contact-admin-card--flush">
        <div class="users-table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th class="contact-table-checkbox-col"><input type="checkbox" id="selectAll" data-contact-select-all="submission_ids[]"></th>
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
                        $senderName  = trim((string) ($sub['sender_name'] ?? '')) ?: '—';
                        $senderEmail = trim((string) ($sub['sender_email'] ?? ''));
                        $subjectRaw  = trim((string) ($sub['subject'] ?? ''));
                        $messageRaw  = trim((string) ($sub['message'] ?? ''));
                        $subject     = $subjectRaw !== '' ? $subjectRaw : '(kein Betreff)';
                        $messagePreview = $messageRaw !== ''
                            ? preg_replace('/\s+/u', ' ', $messageRaw)
                            : '';
                        $listHighlights = is_array($sub['_list_highlights'] ?? null) ? $sub['_list_highlights'] : [];
                        $metaHighlights = array_values(array_filter(
                            $listHighlights,
                            static fn(array $item): bool => ($item['type'] ?? '') !== 'form'
                        ));
                        $formHighlight = null;
                        foreach ($listHighlights as $highlightItem) {
                            if (($highlightItem['type'] ?? '') === 'form') {
                                $formHighlight = $highlightItem;
                                break;
                            }
                        }
                        $submissionStatus = (string) ($sub['status'] ?? 'unread');
                        $st = $statusMap[$submissionStatus] ?? $statusMap['unread'];
                        $markReadToken = class_exists('CMS\\Security')
                            ? \CMS\Security::instance()->generateToken('contact_submissions_mark_read_' . (int) ($sub['id'] ?? 0))
                            : '';
                    ?>
                    <tr class="<?php echo $submissionStatus === 'unread' ? 'contact-submission-row--unread' : ''; ?>">
                        <td><input type="checkbox" name="submission_ids[]" value="<?php echo (int)$sub['id']; ?>"></td>
                        <td>
                            <div class="contact-table-primary"><?php echo $e($senderName); ?></div>
                            <?php if ($senderEmail): ?>
                            <div class="contact-table-meta"><?php echo $e($senderEmail); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($metaHighlights)): ?>
                            <div class="contact-inline-meta-list">
                                <?php foreach ($metaHighlights as $highlight): ?>
                                <?php $highlightValue = (string) ($highlight['value'] ?? ''); ?>
                                <span class="contact-inline-meta-pill<?php echo ($highlight['type'] ?? '') === 'consent' ? ' contact-inline-meta-pill--consent' : ''; ?>">
                                    <span aria-hidden="true"><?php echo $e($highlight['icon'] ?? '•'); ?></span>
                                    <span class="contact-inline-meta-pill__label"><?php echo $e($highlight['label'] ?? ''); ?>:</span>
                                    <?php if (($highlight['type'] ?? '') === 'phone'): ?>
                                    <a href="tel:<?php echo $e(preg_replace('/[^+0-9]/', '', $highlightValue)); ?>" class="contact-inline-meta-pill__link">
                                        <?php echo $e($highlightValue); ?>
                                    </a>
                                    <?php else: ?>
                                    <span><?php echo $e($highlightValue); ?></span>
                                    <?php endif; ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="contact-table-primary"><?php echo $e(mb_strimwidth($subject, 0, 70, '…')); ?></div>
                            <?php if ($messagePreview !== ''): ?>
                            <div class="contact-message-preview"><?php echo $e(mb_strimwidth((string) $messagePreview, 0, 120, '…')); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="contact-form-badge">
                                <span class="contact-form-badge__label">Formular</span>
                                <span class="contact-form-badge__value"><?php echo $e($formHighlight['value'] ?? ($sub['form_title'] ?? '—')); ?></span>
                            </div>
                        </td>
                        <td><span class="status-badge <?php echo $st['class']; ?>"><?php echo $st['label']; ?></span></td>
                        <td class="contact-table-date"><?php echo date('d.m.Y H:i', strtotime($sub['created_at'])); ?></td>
                        <td>
                            <div class="contact-inline-actions">
                                <a href="?section=submissions&action=view&id=<?php echo (int)$sub['id']; ?>&mark_read=1&mark_token=<?php echo rawurlencode($markReadToken); ?>"
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
<div class="contact-pagination">
    <?php if ($page > 1): ?>
    <a href="?section=submissions&paged=<?php echo $page - 1; ?>&form_id=<?php echo $filterFormId; ?>&status=<?php echo rawurlencode((string) $filterStatus); ?>&search=<?php echo rawurlencode((string) $filterSearch); ?>"
       class="btn btn-secondary btn-sm">← Zurück</a>
    <?php endif; ?>
    <span class="contact-pagination__status">
        Seite <?php echo $page; ?> von <?php echo $pages; ?>
    </span>
    <?php if ($page < $pages): ?>
    <a href="?section=submissions&paged=<?php echo $page + 1; ?>&form_id=<?php echo $filterFormId; ?>&status=<?php echo rawurlencode((string) $filterStatus); ?>&search=<?php echo rawurlencode((string) $filterSearch); ?>"
       class="btn btn-secondary btn-sm">Weiter →</a>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

</div>
