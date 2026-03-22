<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<div class="kb-admin-shell">
    <div class="admin-page-header">
        <div>
            <h2>📚 Knowledgebase-Dashboard</h2>
            <p>Verwalte Fachbegriffe, Synonyme, Tooltip-Texte und die automatische interne Verlinkung.</p>
        </div>
        <div class="header-actions">
            <a href="/kb" class="btn btn-primary" target="_blank" rel="noopener noreferrer">🌍 KB öffnen</a>
            <a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-entries" class="btn btn-secondary btn-sm">🧠 Einträge verwalten</a>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-<?php echo ($notice['type'] ?? '') === 'success' ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars((string) ($notice['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-grid kb-admin-dashboard-grid">
        <div class="stat-card"><div class="stat-icon">🧠</div><div class="stat-number"><?php echo number_format((int) ($stats['entries'] ?? 0)); ?></div><div class="stat-label">Einträge gesamt</div></div>
        <div class="stat-card"><div class="stat-icon">✅</div><div class="stat-number"><?php echo number_format((int) ($stats['active_entries'] ?? 0)); ?></div><div class="stat-label">Aktive Begriffe</div></div>
        <div class="stat-card"><div class="stat-icon">💬</div><div class="stat-number"><?php echo number_format((int) ($stats['tooltip_entries'] ?? 0)); ?></div><div class="stat-label">Mit Tooltip</div></div>
        <div class="stat-card"><div class="stat-icon">🗂️</div><div class="stat-number"><?php echo number_format((int) ($stats['categories'] ?? 0)); ?></div><div class="stat-label">Kategorien</div></div>
    </div>

    <div class="admin-card kb-quick-actions-card">
        <div class="kb-panel-header kb-panel-header--compact">
            <div>
                <h3>⚡ Schnellzugriff</h3>
                <p>Direkte Wege zu Pflege, Einstellungen und Frontend.</p>
            </div>
        </div>
        <div class="kb-quick-actions">
            <a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-entries" class="btn btn-primary">➕ Neuer Eintrag</a>
            <a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-categories" class="btn btn-secondary btn-sm">🗂️ Kategorien</a>
            <a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-settings?tab=general" class="btn btn-secondary btn-sm">⚙️ Einstellungen</a>
            <a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-settings?tab=design" class="btn btn-secondary btn-sm">🎨 Design</a>
            <a href="/kb" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">🌍 Knowledgebase öffnen</a>
        </div>
    </div>

    <div class="kb-admin-grid">
        <div class="admin-card">
            <div class="kb-panel-header">
                <div>
                    <h3>🆕 Neueste Einträge</h3>
                    <p>Die zuletzt gepflegten Begriffe für Auto-Linking und Wissensseiten.</p>
                </div>
                <a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-entries" class="btn btn-secondary btn-sm">Alle ansehen</a>
            </div>

            <?php if (empty($entries)): ?>
                <div class="empty-state">
                    <p class="kb-empty-icon">📭</p>
                    <p><strong>Noch keine Einträge vorhanden</strong></p>
                    <p class="kb-empty-text">Lege den ersten Begriff an und verknüpfe ihn automatisch im Content.</p>
                </div>
            <?php else: ?>
                <div class="kb-entry-card-list">
                    <?php foreach ($entries as $entry): ?>
                        <article class="kb-entry-card kb-entry-card--compact">
                            <div class="kb-entry-card__main">
                                <div class="kb-entry-card__header">
                                    <h4><a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-entry-editor?edit=<?php echo (int) $entry['id']; ?>" class="kb-admin-link"><?php echo htmlspecialchars((string) $entry['title'], ENT_QUOTES, 'UTF-8'); ?></a></h4>
                                    <span class="status-badge <?php echo ((int) ($entry['is_active'] ?? 0) === 1) ? 'active' : 'inactive'; ?>"><?php echo ((int) ($entry['is_active'] ?? 0) === 1) ? 'Aktiv' : 'Inaktiv'; ?></span>
                                </div>
                                <div class="kb-meta-pills">
                                    <span class="kb-meta-pill"><strong>Keyword</strong><?php echo htmlspecialchars((string) $entry['keyword'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="kb-meta-pill"><strong>Slug</strong><code><?php echo htmlspecialchars((string) $entry['slug'], ENT_QUOTES, 'UTF-8'); ?></code></span>
                                </div>
                            </div>
                            <div class="kb-action-row kb-action-row--compact">
                                <a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-entry-editor?edit=<?php echo (int) $entry['id']; ?>" class="btn btn-secondary btn-sm">Bearbeiten</a>
                                <a href="/kb/<?php echo rawurlencode((string) $entry['slug']); ?>" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">Öffnen</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="kb-side-stack">
            <div class="kb-note-card">
                <span class="kb-note-card__eyebrow">Auto-Linking</span>
                <span class="kb-note-card__title"><?php echo ($settings['enable_autolink'] ?? '0') === '1' ? 'Aktiviert' : 'Deaktiviert'; ?></span>
                <p class="kb-note-card__text">Der bevorzugte Weg läuft über den Filter <code>content_render</code>; als Netz mit doppeltem Boden ist optionales Output-Buffering aktivierbar.</p>
            </div>

            <div class="kb-note-card">
                <span class="kb-note-card__eyebrow">Tooltip-Vorschau</span>
                <span class="kb-note-card__title"><?php echo ($settings['enable_tooltips'] ?? '0') === '1' ? 'Aktiv' : 'Aus'; ?></span>
                <p class="kb-note-card__text">Kurze Kontextinfos erscheinen bei Hover und Fokus direkt am verlinkten Begriff.</p>
            </div>

            <div class="kb-note-card">
                <span class="kb-note-card__eyebrow">Maximale Links pro Seite</span>
                <span class="kb-note-card__title"><?php echo number_format((int) ($settings['max_links_per_page'] ?? 0)); ?></span>
                <p class="kb-note-card__text">Begrenzt die automatische Verlinkung pro Seitenaufruf, damit die Ausgabe lesbar bleibt und nicht wie Lametta wirkt.</p>
            </div>
        </div>
    </div>
</div>
