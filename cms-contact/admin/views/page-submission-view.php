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

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">
    <!-- Nachrichteninhalt -->
    <div>
        <div class="admin-card">
            <h3>📝 Eingabedaten</h3>
            <?php if (empty($meta)): ?>
            <p style="color:#64748b;">Keine Felder gespeichert.</p>
            <?php else: ?>
            <table style="width:100%;border-collapse:collapse;">
                <?php foreach ($meta as $key => $val): ?>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:.6rem .75rem;font-weight:600;color:#475569;width:160px;vertical-align:top;">
                        <?php echo $e(ucfirst(str_replace('_', ' ', $key))); ?>
                    </td>
                    <td style="padding:.6rem .75rem;color:#1e293b;">
                        <?php echo nl2br($e($val)); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Seitenleiste -->
    <div>
        <!-- Status -->
        <div class="admin-card">
            <h3>📊 Status</h3>
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;">
                <span class="status-badge <?php echo $st['class']; ?>"><?php echo $st['icon'] . ' ' . $st['label']; ?></span>
            </div>
            <form method="POST" style="display:flex;gap:.5rem;flex-wrap:wrap;">
                <input type="hidden" name="sub_action" value="update_status">
                <input type="hidden" name="id" value="<?php echo (int)$submission['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <select name="status" class="form-control" style="flex:1;">
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
            <ul class="info-list" style="list-style:none;padding:0;margin:0;">
                <li style="padding:.4rem 0;border-bottom:1px solid #f1f5f9;">
                    <strong>ID:</strong> <?php echo (int)$submission['id']; ?>
                </li>
                <li style="padding:.4rem 0;border-bottom:1px solid #f1f5f9;">
                    <strong>Formular:</strong> <?php echo $e($form['title'] ?? '—'); ?>
                </li>
                <li style="padding:.4rem 0;border-bottom:1px solid #f1f5f9;">
                    <strong>IP-Adresse:</strong> <?php echo $e($submission['ip_address'] ?? '—'); ?>
                </li>
                <li style="padding:.4rem 0;border-bottom:1px solid #f1f5f9;">
                    <strong>Erstellt:</strong> <?php echo date('d.m.Y H:i:s', strtotime($submission['created_at'])); ?>
                </li>
                <?php if (!empty($submission['updated_at'])): ?>
                <li style="padding:.4rem 0;">
                    <strong>Aktualisiert:</strong> <?php echo date('d.m.Y H:i:s', strtotime($submission['updated_at'])); ?>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Aktionen -->
        <div class="admin-card">
            <h3>⚡ Aktionen</h3>
            <div style="display:flex;flex-direction:column;gap:.5rem;">
                <?php
                $senderEmail = $meta['email'] ?? $meta['sender_email'] ?? '';
                if ($senderEmail):
                ?>
                <a href="mailto:<?php echo $e($senderEmail); ?>" class="btn btn-secondary btn-sm">
                    ✉️ Absender anschreiben
                </a>
                <?php endif; ?>
                <button class="btn btn-danger btn-sm" onclick="openModal('deleteModal')">🗑️ Nachricht löschen</button>
            </div>
        </div>
    </div>
</div>

<!-- Lösch-Modal -->
<div id="deleteModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:440px;">
        <div class="modal-header">
            <h3>🗑️ Nachricht löschen?</h3>
            <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Möchtest du diese Nachricht wirklich unwiderruflich löschen?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('deleteModal')">Abbrechen</button>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="form_action" value="delete_submission">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <button type="submit" class="btn btn-danger">🗑️ Endgültig löschen</button>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
window.addEventListener('click', function(e) {
    document.querySelectorAll('.modal').forEach(m => { if (e.target === m) closeModal(m.id); });
});
</script>
