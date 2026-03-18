<?php declare(strict_types=1); if (!defined('ABSPATH')) exit;
$e = fn(?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$statusMap = [
    'unread'   => ['label' => 'Ungelesen',  'class' => 'inactive', 'icon' => '📬'],
    'read'     => ['label' => 'Gelesen',    'class' => 'active',   'icon' => '📖'],
    'replied'  => ['label' => 'Beantwortet','class' => 'active',   'icon' => '↩️'],
    'archived' => ['label' => 'Archiviert', 'class' => 'inactive', 'icon' => '📦'],
    'spam'     => ['label' => 'Spam',       'class' => 'danger',   'icon' => '🚫'],
];
$st = $statusMap[$submission['status']] ?? $statusMap['unread'];
?>

<?php include CMS_CONTACT_PLUGIN_DIR . 'admin/views/partial-section-nav.php'; ?>

<div class="contact-admin-shell">

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>📨 Nachricht #<?php echo (int)$submission['id']; ?></h2>
        <p>Formular-ID: <?php echo (int)($submission['form_id'] ?? 0); ?> | <?php echo date('d.m.Y H:i', strtotime($submission['created_at'])); ?></p>
    </div>
    <div class="header-actions">
        <a href="?section=submissions" class="btn btn-secondary">↩️ Zurück</a>
    </div>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo $e($notice); ?></div>
<?php endif; ?>

<div class="contact-detail-grid contact-detail-grid--wide">
    <!-- Nachrichteninhalt -->
    <div>
        <div class="admin-card">
            <div class="contact-panel-header">
                <div>
                    <h3>📝 Eingabedaten</h3>
                    <p>Alle gespeicherten Feldwerte der Anfrage kompakt dargestellt.</p>
                </div>
            </div>
            <?php if (empty($meta)): ?>
            <p class="contact-muted-text">Keine Felder gespeichert.</p>
            <?php else: ?>
            <table class="contact-field-table">
                <?php foreach ($meta as $key => $val): ?>
                <tr>
                    <td>
                        <?php echo $e(ucfirst(str_replace('_', ' ', $key))); ?>
                    </td>
                    <td>
                        <?php echo nl2br($e($val)); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Seitenleiste -->
    <div class="contact-side-stack">
        <!-- Status -->
        <div class="admin-card">
            <h3>📊 Status</h3>
            <div class="contact-inline-actions contact-status-row">
                <span class="status-badge <?php echo $st['class']; ?>"><?php echo $st['icon'] . ' ' . $st['label']; ?></span>
            </div>
            <form method="POST" class="contact-status-form">
                <input type="hidden" name="sub_action" value="update_status">
                <input type="hidden" name="id" value="<?php echo (int)$submission['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <select name="status" class="form-control contact-status-select">
                    <?php foreach ($statusMap as $key => $s): ?>
                    <option value="<?php echo $key; ?>" <?php echo $submission['status'] === $key ? 'selected' : ''; ?>>
                        <?php echo $s['icon'] . ' ' . $s['label']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Ändern</button>
            </form>
        </div>

        <!-- Metadaten -->
        <div class="admin-card">
            <h3>ℹ️ Informationen</h3>
            <ul class="contact-info-list">
                <li>
                    <strong>ID</strong> <span><?php echo (int)$submission['id']; ?></span>
                </li>
                <li>
                    <strong>Formular</strong> <span><?php echo $e($form['title'] ?? '—'); ?></span>
                </li>
                <li>
                    <strong>IP-Adresse</strong> <span><?php echo $e($submission['ip_address'] ?? '—'); ?></span>
                </li>
                <li>
                    <strong>Erstellt</strong> <span><?php echo date('d.m.Y H:i:s', strtotime($submission['created_at'])); ?></span>
                </li>
                <?php if (!empty($submission['updated_at'])): ?>
                <li>
                    <strong>Aktualisiert</strong> <span><?php echo date('d.m.Y H:i:s', strtotime($submission['updated_at'])); ?></span>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Aktionen -->
        <div class="admin-card">
            <h3>⚡ Aktionen</h3>
            <div class="contact-button-stack">
                <?php
                $senderEmail = $meta['email'] ?? $meta['sender_email'] ?? '';
                if ($senderEmail):
                ?>
                <a href="mailto:<?php echo $e($senderEmail); ?>" class="btn btn-secondary btn-sm">
                    ✉️ Absender anschreiben
                </a>
                <?php endif; ?>
                <button type="button" class="btn btn-danger btn-sm contact-modal-trigger" data-contact-open-delete-modal="deleteModal">🗑️ Nachricht löschen</button>
            </div>
        </div>
    </div>
</div>

</div>

<!-- Lösch-Modal -->
<div id="deleteModal" class="modal contact-modal">
    <div class="modal-content contact-modal-content--compact">
        <div class="modal-header">
            <h3>🗑️ Nachricht löschen?</h3>
            <button class="modal-close" data-close-modal="deleteModal">&times;</button>
        </div>
        <div class="modal-body">
            <p>Möchtest du diese Nachricht wirklich unwiderruflich löschen?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-close-modal="deleteModal">Abbrechen</button>
            <form method="POST" class="contact-inline-form">
                <input type="hidden" name="form_action" value="delete_submission">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <button type="submit" class="btn btn-danger">🗑️ Endgültig löschen</button>
            </form>
        </div>
    </div>
</div>
