<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<?php include CMS_CONTACT_PLUGIN_DIR . 'admin/views/partial-section-nav.php'; ?>

<?php
$fieldsService = CMS_Contact_Fields::instance();
$tplList = CMS_Contact_Forms::get_available_templates();
$activeCount = 0;
$totalFields = 0;
foreach ($allForms as $formItem) {
    if (($formItem['status'] ?? 'inactive') === 'active') {
        $activeCount++;
    }
    $totalFields += $fieldsService->count_by_form((int) $formItem['id']);
}
?>

<div class="contact-admin-shell">

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>📋 Kontaktformulare</h2>
        <p>Alle Kontaktformulare verwalten und neue erstellen</p>
    </div>
    <div class="header-actions">
        <a href="?section=forms&action=new" class="btn btn-primary">➕ Neues Formular</a>
    </div>
</div>

<!-- Alerts -->
<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo htmlspecialchars($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="contact-card-grid">
    <div class="contact-mini-card">
        <span class="contact-mini-card__label">Formulare gesamt</span>
        <span class="contact-mini-card__value"><?php echo number_format(count($allForms)); ?></span>
        <span class="contact-mini-card__text">Alle Kontaktformulare im Plugin.</span>
    </div>
    <div class="contact-mini-card">
        <span class="contact-mini-card__label">Aktiv</span>
        <span class="contact-mini-card__value"><?php echo number_format($activeCount); ?></span>
        <span class="contact-mini-card__text">Aktuell im Frontend sichtbare Formulare.</span>
    </div>
    <div class="contact-mini-card">
        <span class="contact-mini-card__label">Felder gesamt</span>
        <span class="contact-mini-card__value"><?php echo number_format($totalFields); ?></span>
        <span class="contact-mini-card__text">Über alle Formulare hinweg definierte Eingabefelder.</span>
    </div>
</div>

<div class="contact-action-grid">
    <a href="?section=forms&action=new" class="contact-action-card">
        <span class="contact-action-card__icon">🧩</span>
        <span>
            <span class="contact-action-card__eyebrow">Builder</span>
            <span class="contact-action-card__title">Neues Formular starten</span>
            <span class="contact-action-card__text">Mit Template, Slug und individueller Erfolgsmeldung anlegen.</span>
        </span>
    </a>
    <a href="?section=settings" class="contact-action-card">
        <span class="contact-action-card__icon">🎨</span>
        <span>
            <span class="contact-action-card__eyebrow">Design</span>
            <span class="contact-action-card__title">Standards definieren</span>
            <span class="contact-action-card__text">Fallback-Template, Farben und E-Mail-Vorgaben zentral pflegen.</span>
        </span>
    </a>
</div>

<!-- Formulare-Liste -->
<div class="admin-card">
    <div class="contact-panel-header">
        <div>
            <h3>📋 Alle Formulare</h3>
            <p>Bearbeiten, Felder öffnen oder die öffentliche Vorschau direkt testen.</p>
        </div>
    </div>

    <?php if (empty($allForms)): ?>
    <div class="empty-state">
        <p class="contact-empty-state__icon">📭</p>
        <p><strong>Noch keine Formulare vorhanden</strong></p>
        <p class="contact-empty-state__text">Erstelle dein erstes Kontaktformular.</p>
        <a href="?section=forms&action=new" class="btn btn-primary contact-empty-state__cta">➕ Jetzt erstellen</a>
    </div>
    <?php else: ?>
    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Slug / URL</th>
                    <th>Template</th>
                    <th>Felder</th>
                    <th>Status</th>
                    <th>Erstellt</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allForms as $f):
                $fieldCount = $fieldsService->count_by_form((int)$f['id']);
                $tplName = ($tplList[$f['template']]['icon'] ?? '') . ' ' . ($tplList[$f['template']]['name'] ?? $f['template']);
            ?>
            <tr>
                <td>
                    <a href="?section=forms&action=edit&id=<?php echo (int)$f['id']; ?>" class="dl-admin-link">
                        <?php echo htmlspecialchars($f['title']); ?>
                    </a>
                    <div class="contact-table-meta">Erstellt am <?php echo date('d.m.Y', strtotime($f['created_at'])); ?></div>
                </td>
                <td>
                    <span class="contact-code">/contact/<?php echo htmlspecialchars($f['slug']); ?></span>
                </td>
                <td><?php echo htmlspecialchars(($tplList[$f['template']]['icon'] ?? '') . ' ' . ($tplList[$f['template']]['name'] ?? $f['template'])); ?></td>
                <td>
                    <a href="?section=forms&action=fields&id=<?php echo (int)$f['id']; ?>" class="dl-admin-link">
                        <?php echo $fieldCount; ?> Felder
                    </a>
                </td>
                <td>
                    <span class="status-badge <?php echo $f['status'] === 'active' ? 'active' : 'inactive'; ?>">
                        <?php echo $f['status'] === 'active' ? '✅ Aktiv' : '⏸️ Inaktiv'; ?>
                    </span>
                </td>
                <td><?php echo date('d.m.Y', strtotime($f['created_at'])); ?></td>
                <td>
                    <div class="contact-inline-actions">
                        <a href="?section=forms&action=fields&id=<?php echo (int)$f['id']; ?>" class="btn btn-sm btn-secondary" title="Felder bearbeiten">📝</a>
                        <a href="?section=forms&action=edit&id=<?php echo (int)$f['id']; ?>" class="btn btn-sm btn-secondary" title="Einstellungen">✏️</a>
                        <a href="/contact/<?php echo htmlspecialchars($f['slug']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-secondary" title="Frontend-Vorschau">👁️</a>
                        <button type="button"
                                class="btn btn-sm btn-danger"
                                data-contact-open-delete-modal="deleteModal"
                                data-delete-id="<?php echo (int)$f['id']; ?>"
                                data-delete-name="<?php echo htmlspecialchars($f['title'], ENT_QUOTES); ?>"
                                title="Löschen">🗑️</button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

</div>

<!-- Lösch-Modal -->
<div id="deleteModal" class="modal contact-modal">
    <div class="modal-content contact-modal-content--compact">
        <div class="modal-header">
            <h3>🗑️ Formular löschen</h3>
            <button class="modal-close" data-close-modal="deleteModal">&times;</button>
        </div>
        <div class="modal-body">
            <p>Soll das Formular <strong data-delete-modal-name id="deleteModalName"></strong> wirklich gelöscht werden?</p>
            <p class="contact-modal-warning">⚠️ Alle zugehörigen Felder und Nachrichten werden ebenfalls gelöscht. Diese Aktion kann nicht rückgängig gemacht werden.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-close-modal="deleteModal">Abbrechen</button>
            <form method="POST" id="deleteModalForm" class="contact-inline-form">
                <input type="hidden" name="form_action" value="delete_form">
                <input type="hidden" name="id" id="deleteModalId" data-delete-modal-id>
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <button type="submit" class="btn btn-danger">🗑️ Endgültig löschen</button>
            </form>
        </div>
    </div>
</div>
