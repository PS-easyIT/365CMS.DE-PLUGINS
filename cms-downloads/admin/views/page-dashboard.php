<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<?php
$featuredCount = count(array_filter($downloads, static fn(array $item): bool => !empty($item['is_featured'])));
?>

<div class="dl-admin-shell">

<div class="admin-page-header">
    <div>
        <h2>⬇️ Download-Dashboard</h2>
        <p>Übersicht über Downloads, Kategorien und Nutzung im öffentlichen Bereich.</p>
    </div>
    <div class="header-actions">
        <a href="?" class="btn btn-secondary btn-sm">🔄 Aktualisieren</a>
        <a href="/downloads" class="btn btn-primary" target="_blank" rel="noopener noreferrer">🌍 Archiv öffnen</a>
    </div>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-<?php echo ($notice['type'] ?? '') === 'success' ? 'success' : 'error'; ?>">
    <?php echo htmlspecialchars((string) ($notice['message'] ?? '')); ?>
</div>
<?php endif; ?>

<div class="dashboard-grid dl-admin-dashboard-grid">
    <div class="stat-card"><div class="stat-icon">📦</div><div class="stat-number"><?php echo number_format((int) ($stats['downloads'] ?? 0)); ?></div><div class="stat-label">Downloads gesamt</div></div>
    <div class="stat-card"><div class="stat-icon">✅</div><div class="stat-number"><?php echo number_format((int) ($stats['active_downloads'] ?? 0)); ?></div><div class="stat-label">Aktiv</div></div>
    <div class="stat-card"><div class="stat-icon">🗂️</div><div class="stat-number"><?php echo number_format((int) ($stats['categories'] ?? 0)); ?></div><div class="stat-label">Kategorien</div></div>
    <div class="stat-card"><div class="stat-icon">📥</div><div class="stat-number"><?php echo number_format((int) ($stats['downloads_total'] ?? 0)); ?></div><div class="stat-label">Downloads geladen</div></div>
</div>

<div class="dl-card-grid">
    <div class="dl-info-card">
        <span class="dl-info-card__eyebrow">Featured</span>
        <span class="dl-info-card__value"><?php echo number_format($featuredCount); ?></span>
        <span class="dl-info-card__text">Hervorgehobene Downloads mit besonderer Sichtbarkeit im Archiv.</span>
    </div>
    <div class="dl-info-card">
        <span class="dl-info-card__eyebrow">Archivstatus</span>
        <span class="dl-info-card__value"><?php echo !empty($downloads) ? 'Live' : 'Leer'; ?></span>
        <span class="dl-info-card__text"><?php echo !empty($downloads) ? 'Öffentliche Downloadseite ist mit Inhalten gefüllt.' : 'Noch keine Einträge im Archiv veröffentlicht.'; ?></span>
    </div>
</div>

<div class="dl-feature-grid">
    <a href="/admin/plugins/downloads-dashboard/downloads-items" class="dl-feature-card">
        <span class="dl-feature-card__icon">➕</span>
        <span>
            <span class="dl-feature-card__eyebrow">Verwaltung</span>
            <span class="dl-feature-card__title">Download anlegen</span>
            <span class="dl-feature-card__text">Neue Datei, externe Quelle oder Paket-Download mit wenigen Klicks anlegen.</span>
        </span>
    </a>
    <a href="/admin/plugins/downloads-dashboard/downloads-categories" class="dl-feature-card">
        <span class="dl-feature-card__icon">🗂️</span>
        <span>
            <span class="dl-feature-card__eyebrow">Struktur</span>
            <span class="dl-feature-card__title">Kategorien pflegen</span>
            <span class="dl-feature-card__text">Saubere thematische Gruppen für Archiv, Filter und Übersicht.</span>
        </span>
    </a>
    <a href="/admin/plugins/downloads-dashboard/downloads-settings" class="dl-feature-card">
        <span class="dl-feature-card__icon">⚙️</span>
        <span>
            <span class="dl-feature-card__eyebrow">Frontend</span>
            <span class="dl-feature-card__title">Archiv konfigurieren</span>
            <span class="dl-feature-card__text">Titel, Suchleiste und Kategoriedarstellung für Besucher festlegen.</span>
        </span>
    </a>
</div>

<div class="dl-panel-grid">
    <div class="admin-card">
        <div class="dl-panel-header">
            <div>
                <h3>🆕 Letzte Downloads</h3>
                <p>Die jüngsten Einträge mit Typ, Kategorie und Nutzungsstand.</p>
            </div>
            <a href="/admin/plugins/downloads-dashboard/downloads-items" class="btn btn-secondary btn-sm">Verwalten</a>
        </div>
        <?php if (empty($downloads)): ?>
            <div class="empty-state">
                <p class="dl-empty-icon">📭</p>
                <p><strong>Noch keine Downloads vorhanden</strong></p>
                <p class="dl-empty-text">Lege den ersten öffentlichen Download über die Download-Verwaltung an.</p>
            </div>
        <?php else: ?>
            <div class="users-table-container">
                <table class="users-table">
                    <thead><tr><th>Titel</th><th>Typ</th><th>Kategorie</th><th>Downloads</th></tr></thead>
                    <tbody>
                    <?php foreach ($downloads as $item): ?>
                        <tr>
                            <td><a href="/admin/plugins/downloads-dashboard/downloads-items?edit=<?php echo (int) $item['id']; ?>" class="dl-admin-link"><?php echo htmlspecialchars((string) $item['title']); ?></a></td>
                            <td><?php echo htmlspecialchars((string) $item['download_type']); ?></td>
                            <td><?php echo htmlspecialchars((string) ($item['category_name'] ?? '—')); ?></td>
                            <td><?php echo number_format((int) ($item['download_count'] ?? 0)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="dl-side-stack">
        <div class="admin-card">
            <div class="dl-panel-header">
                <div>
                    <h3>🗃️ Kategorien</h3>
                    <p>Die wichtigsten Archivgruppen mit Datei-Anzahl auf einen Blick.</p>
                </div>
            </div>
            <?php if (empty($categories)): ?>
                <div class="empty-state">
                    <p class="dl-empty-icon">🗂️</p>
                    <p><strong>Keine Kategorien vorhanden</strong></p>
                    <p class="dl-empty-text">Das Plugin kann Downloads auch ohne Kategorie verwalten, aber mit Kategorien lebt es schöner.</p>
                </div>
            <?php else: ?>
                <ul class="dl-compact-list">
                    <?php foreach ($categories as $category): ?>
                        <li>
                            <span><?php echo htmlspecialchars((string) ($category['icon'] ?? '📁')); ?> <?php echo htmlspecialchars((string) $category['name']); ?></span>
                            <span class="status-badge <?php echo ($category['status'] ?? 'active') === 'active' ? 'active' : 'inactive'; ?>">
                                <?php echo (int) ($category['download_count'] ?? 0); ?> Dateien
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="dl-note-card">
            <span class="dl-note-card__eyebrow">Vordefinierte Typen</span>
            <span class="dl-note-card__title">Templates für typische Download-Inhalte</span>
            <div class="dl-badge-stack dl-badge-stack--spaced">
                <span class="dl-soft-badge">⚡ PowerShell</span>
                <span class="dl-soft-badge">🌐 Webprojekte</span>
                <span class="dl-soft-badge">📄 Dokumente</span>
                <span class="dl-soft-badge">📚 eBooks</span>
                <span class="dl-soft-badge">🗜️ Archive</span>
            </div>
            <p class="dl-note-card__text dl-note-card__text--spaced">Die Typen helfen dir bei konsistenter Kategorisierung und verständlicher Darstellung im öffentlichen Bereich.</p>
        </div>
    </div>
</div>

</div>
