<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<?php include CMS_CONTACT_PLUGIN_DIR . 'admin/views/partial-section-nav.php'; ?>

<?php
$activeForms = count(array_filter($allForms, static fn(array $form): bool => ($form['status'] ?? 'inactive') === 'active'));
$inactiveForms = max(0, count($allForms) - $activeForms);
?>

<div class="contact-admin-shell">

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
        <div class="stat-number <?php echo $globalStats['unread'] > 0 ? 'contact-stat-number--alert' : 'contact-stat-number--info'; ?>">
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

<div class="contact-card-grid">
    <div class="contact-mini-card">
        <span class="contact-mini-card__label">Aktive Formulare</span>
        <span class="contact-mini-card__value"><?php echo number_format($activeForms); ?></span>
        <span class="contact-mini-card__text">Formulare, die aktuell öffentlich erreichbar sind.</span>
    </div>
    <div class="contact-mini-card">
        <span class="contact-mini-card__label">Pausiert</span>
        <span class="contact-mini-card__value"><?php echo number_format($inactiveForms); ?></span>
        <span class="contact-mini-card__text">Inaktive Formulare für spätere Nutzung oder Tests.</span>
    </div>
    <div class="contact-mini-card">
        <span class="contact-mini-card__label">Benachrichtigungen</span>
        <span class="contact-mini-card__value"><?php echo $globalStats['unread'] > 0 ? 'Offen' : 'Sauber'; ?></span>
        <span class="contact-mini-card__text"><?php echo $globalStats['unread'] > 0 ? number_format($globalStats['unread']) . ' ungelesene Nachrichten warten.' : 'Keine ungelesenen Nachrichten im Postfach.'; ?></span>
    </div>
</div>

<div class="contact-action-grid">
    <a href="?section=forms&action=new" class="contact-action-card">
        <span class="contact-action-card__icon">➕</span>
        <span>
            <span class="contact-action-card__eyebrow">Schnellzugriff</span>
            <span class="contact-action-card__title">Neues Formular anlegen</span>
            <span class="contact-action-card__text">Lege neue Kontakt-, Support- oder Anfrageformulare an.</span>
        </span>
    </a>
    <a href="?section=submissions" class="contact-action-card">
        <span class="contact-action-card__icon">📩</span>
        <span>
            <span class="contact-action-card__eyebrow">Postfach</span>
            <span class="contact-action-card__title">Nachrichten prüfen</span>
            <span class="contact-action-card__text">Alle Einsendungen zentral filtern, prüfen und beantworten.</span>
        </span>
    </a>
    <a href="?section=settings" class="contact-action-card">
        <span class="contact-action-card__icon">⚙️</span>
        <span>
            <span class="contact-action-card__eyebrow">Plugin-Setup</span>
            <span class="contact-action-card__title">Globale Einstellungen</span>
            <span class="contact-action-card__text">Farben, Versand und Bereinigung zentral steuern.</span>
        </span>
    </a>
    <?php if ($globalStats['unread'] > 0): ?>
    <a href="?section=submissions&status=unread" class="contact-action-card">
        <span class="contact-action-card__icon">🔔</span>
        <span>
            <span class="contact-action-card__eyebrow">Priorität</span>
            <span class="contact-action-card__title">Ungelesene öffnen</span>
            <span class="contact-action-card__text"><?php echo number_format($globalStats['unread']); ?> Nachricht<?php echo (int) $globalStats['unread'] === 1 ? '' : 'en'; ?> warten auf Sichtung.</span>
        </span>
    </a>
    <?php endif; ?>
</div>

<div class="contact-split-grid">
<div class="admin-card">
    <div class="contact-panel-header">
        <div>
            <h3>📋 Aktive Formulare</h3>
            <p>Deine wichtigsten Formulare mit Status, Template und Nachrichtenlage.</p>
        </div>
        <a href="?section=forms" class="btn btn-secondary btn-sm">Alle Formulare</a>
    </div>

    <?php if (empty($allForms)): ?>
    <div class="empty-state">
        <p class="dl-empty-icon">📭</p>
        <p><strong>Noch keine Formulare vorhanden</strong></p>
        <p class="contact-empty-state__text">Erstelle dein erstes Kontaktformular über den Button oben.</p>
        <a href="?section=forms&action=new" class="btn btn-primary contact-empty-state__cta">➕ Jetzt erstellen</a>
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
                    <a href="?section=forms&action=edit&id=<?php echo (int)$f['id']; ?>" class="dl-admin-link">
                        <?php echo htmlspecialchars($f['title']); ?>
                    </a>
                    <div class="contact-table-meta">Template: <?php echo htmlspecialchars($tplName); ?></div>
                </td>
                <td><span class="contact-code">/contact/<?php echo htmlspecialchars($f['slug']); ?></span></td>
                <td><?php echo htmlspecialchars($tplName); ?></td>
                <td><?php echo number_format($stats['total']); ?></td>
                <td>
                    <?php if ($stats['unread'] > 0): ?>
                    <span class="contact-count-badge">
                        <?php echo $stats['unread']; ?>
                    </span>
                    <?php else: ?>
                    <span class="contact-count-zero">0</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="status-badge <?php echo $f['status'] === 'active' ? 'active' : 'inactive'; ?>">
                        <?php echo $f['status'] === 'active' ? '✅ Aktiv' : '⏸️ Inaktiv'; ?>
                    </span>
                </td>
                <td>
                    <div class="contact-inline-actions">
                        <a href="?section=forms&action=fields&id=<?php echo (int)$f['id']; ?>" class="btn btn-sm btn-secondary" title="Felder bearbeiten">📝</a>
                        <a href="?section=forms&action=edit&id=<?php echo (int)$f['id']; ?>" class="btn btn-sm btn-secondary" title="Bearbeiten">✏️</a>
                        <a href="/contact/<?php echo htmlspecialchars($f['slug']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-secondary" title="Vorschau">👁️</a>
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
<div class="admin-card">
    <div class="contact-panel-header">
        <div>
            <h3>📩 Letzte Nachrichten</h3>
            <p>Die jüngsten Einsendungen direkt aus dem Dashboard heraus im Blick behalten.</p>
        </div>
        <a href="?section=submissions" class="btn btn-secondary btn-sm">Nachrichten öffnen</a>
    </div>
    <?php if (!empty($recentSubmissions)): ?>
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
                    <a href="?section=submissions&action=view&id=<?php echo (int)$sub['id']; ?>" class="dl-admin-link <?php echo $sub['status'] === 'unread' ? 'contact-link--strong' : 'contact-link--normal'; ?>">
                        <?php echo htmlspecialchars($sub['sender_name'] ?: $sub['sender_email'] ?: 'Unbekannt'); ?>
                    </a>
                </td>
                <td><?php echo htmlspecialchars(mb_strimwidth($sub['subject'] ?: $sub['message'] ?: '-', 0, 60, '…')); ?></td>
                <td><?php echo htmlspecialchars($sub['form_title'] ?? '-'); ?></td>
                <td><?php echo date('d.m.Y H:i', strtotime($sub['created_at'])); ?></td>
                <td>
                    <?php
                    $badgeClass = match ($sub['status']) {
                        'unread'   => 'contact-status-badge--unread',
                        'read'     => 'contact-status-badge--read',
                        'replied'  => 'contact-status-badge--replied',
                        'archived' => 'contact-status-badge--archived',
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
                    <span class="status-badge <?php echo $badgeClass; ?>">
                        <?php echo $statusLabel; ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="contact-empty-card">
        <p class="dl-empty-icon">📭</p>
        <p><strong>Noch keine Nachrichten eingegangen</strong></p>
        <p class="contact-muted-text">Sobald Einsendungen eingehen, erscheint hier deine Live-Übersicht.</p>
    </div>
    <?php endif; ?>
</div>
</div>

</div>
