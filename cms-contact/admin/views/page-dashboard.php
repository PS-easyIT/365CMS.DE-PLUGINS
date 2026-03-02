<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<?php include CMS_CONTACT_PLUGIN_DIR . 'admin/views/partial-section-nav.php'; ?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>📬 Kontakt – Dashboard</h2>
        <p>Übersicht aller Kontaktformulare und eingehenden Nachrichten</p>
    </div>
    <div class="header-actions">
        <a href="?section=submissions&status=unread" class="btn btn-secondary btn-sm">📩 Ungelesen</a>
        <a href="?section=forms&action=new" class="btn btn-primary">➕ Neues Formular</a>
    </div>
</div>

<!-- Stat-Cards -->
<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-number"><?php echo count($allForms); ?></div>
        <div class="stat-label">Formulare</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📩</div>
        <div class="stat-number"><?php echo number_format($globalStats['total']); ?></div>
        <div class="stat-label">Nachrichten gesamt</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🔔</div>
        <div class="stat-number" style="color:<?php echo $globalStats['unread'] > 0 ? '#ef4444' : '#3b82f6'; ?>">
            <?php echo number_format($globalStats['unread']); ?>
        </div>
        <div class="stat-label">Ungelesen</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📈</div>
        <div class="stat-number"><?php echo number_format($globalStats['last_7_days']); ?></div>
        <div class="stat-label">Letzte 7 Tage</div>
    </div>
</div>

<!-- Schnellzugriff -->
<div class="admin-card" style="padding:1rem 1.25rem;margin-bottom:1.5rem;">
    <h3 style="margin:0 0 .75rem;font-size:.85rem;color:#475569;font-weight:700;text-transform:uppercase;letter-spacing:.05em;">⚡ Schnellzugriff</h3>
    <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
        <a href="?section=forms&action=new" class="btn btn-secondary btn-sm">➕ Neues Formular</a>
        <a href="?section=submissions" class="btn btn-secondary btn-sm">📩 Alle Nachrichten</a>
        <a href="?section=settings" class="btn btn-secondary btn-sm">⚙️ Einstellungen</a>
        <?php if ($globalStats['unread'] > 0): ?>
        <a href="?section=submissions&status=unread" class="btn btn-secondary btn-sm">
            🔔 Ungelesene
            <span style="background:#fee2e2;color:#991b1b;padding:.1rem .4rem;border-radius:10px;font-size:.7rem;font-weight:700;margin-left:.2rem;">
                <?php echo $globalStats['unread']; ?>
            </span>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Formulare-Übersicht -->
<div class="admin-card">
    <h3>📋 Aktive Formulare</h3>

    <?php if (empty($allForms)): ?>
    <div class="empty-state">
        <p style="font-size:2.5rem;margin:0;">📭</p>
        <p><strong>Noch keine Formulare vorhanden</strong></p>
        <p style="color:#64748b;font-size:.875rem;">Erstelle dein erstes Kontaktformular über den Button oben.</p>
        <a href="?section=forms&action=new" class="btn btn-primary" style="margin-top:1rem;">➕ Jetzt erstellen</a>
    </div>
    <?php else: ?>
    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Formular</th>
                    <th>Slug</th>
                    <th>Template</th>
                    <th>Nachrichten</th>
                    <th>Ungelesen</th>
                    <th>Status</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allForms as $f):
                $stats = $formStats[$f['id']] ?? ['total' => 0, 'unread' => 0];
                $tplList = CMS_Contact_Forms::get_available_templates();
                $tplName = $tplList[$f['template']]['name'] ?? $f['template'];
            ?>
            <tr>
                <td>
                    <a href="?section=forms&action=edit&id=<?php echo (int)$f['id']; ?>" style="font-weight:600;color:var(--admin-primary);">
                        <?php echo htmlspecialchars($f['title']); ?>
                    </a>
                </td>
                <td><code>/contact/<?php echo htmlspecialchars($f['slug']); ?></code></td>
                <td><?php echo htmlspecialchars($tplName); ?></td>
                <td><?php echo number_format($stats['total']); ?></td>
                <td>
                    <?php if ($stats['unread'] > 0): ?>
                    <span style="background:#fee2e2;color:#991b1b;padding:.15rem .5rem;border-radius:10px;font-size:.8rem;font-weight:600;">
                        <?php echo $stats['unread']; ?>
                    </span>
                    <?php else: ?>
                    <span style="color:#94a3b8;">0</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="status-badge <?php echo $f['status'] === 'active' ? 'active' : 'inactive'; ?>">
                        <?php echo $f['status'] === 'active' ? '✅ Aktiv' : '⏸️ Inaktiv'; ?>
                    </span>
                </td>
                <td>
                    <div style="display:flex;gap:.4rem;">
                        <a href="?section=forms&action=fields&id=<?php echo (int)$f['id']; ?>" class="btn btn-sm btn-secondary" title="Felder bearbeiten">📝</a>
                        <a href="?section=forms&action=edit&id=<?php echo (int)$f['id']; ?>" class="btn btn-sm btn-secondary" title="Bearbeiten">✏️</a>
                        <a href="/contact/<?php echo htmlspecialchars($f['slug']); ?>" target="_blank" class="btn btn-sm btn-secondary" title="Vorschau">👁️</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Letzte Nachrichten -->
<?php if (!empty($recentSubmissions)): ?>
<div class="admin-card">
    <h3>📩 Letzte Nachrichten</h3>
    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Absender</th>
                    <th>Betreff</th>
                    <th>Formular</th>
                    <th>Datum</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentSubmissions as $sub): ?>
            <tr>
                <td>
                    <a href="?section=submissions&action=view&id=<?php echo (int)$sub['id']; ?>" style="font-weight:<?php echo $sub['status'] === 'unread' ? '700' : '400'; ?>;color:var(--admin-primary);">
                        <?php echo htmlspecialchars($sub['sender_name'] ?: $sub['sender_email'] ?: 'Unbekannt'); ?>
                    </a>
                </td>
                <td><?php echo htmlspecialchars(mb_strimwidth($sub['subject'] ?: $sub['message'] ?: '-', 0, 60, '…')); ?></td>
                <td><?php echo htmlspecialchars($sub['form_title'] ?? '-'); ?></td>
                <td><?php echo date('d.m.Y H:i', strtotime($sub['created_at'])); ?></td>
                <td>
                    <?php
                    $badgeClass = match ($sub['status']) {
                        'unread'   => 'style="background:#fee2e2;color:#991b1b;"',
                        'read'     => 'style="background:#dbeafe;color:#1e40af;"',
                        'replied'  => 'style="background:#d1fae5;color:#065f46;"',
                        'archived' => 'style="background:#f1f5f9;color:#64748b;"',
                        default    => '',
                    };
                    $statusLabel = match ($sub['status']) {
                        'unread'   => '🔴 Ungelesen',
                        'read'     => '🔵 Gelesen',
                        'replied'  => '✅ Beantwortet',
                        'archived' => '📦 Archiviert',
                        default    => $sub['status'],
                    };
                    ?>
                    <span class="status-badge" <?php echo $badgeClass; ?>>
                        <?php echo $statusLabel; ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div style="text-align:center;margin-top:1rem;">
        <a href="?section=submissions" class="btn btn-secondary btn-sm">📩 Alle Nachrichten anzeigen</a>
    </div>
</div>
<?php endif; ?>
