<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<div class="kb-admin-shell">
    <div class="admin-page-header">
        <div>
            <h2>🧠 Knowledgebase-Einträge</h2>
            <p>Alle Knowledgebase-Einträge in einer Listenansicht mit schnellem Zugriff auf Bearbeitung, Vorschau und Löschung.</p>
        </div>
        <div class="header-actions">
            <a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-entry-editor" class="btn btn-primary btn-sm">➕ Neuer Eintrag</a>
            <a href="/kb" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">🌍 Öffentliche KB</a>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-<?php echo ($notice['type'] ?? '') === 'success' ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars((string) ($notice['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-grid kb-admin-dashboard-grid kb-admin-dashboard-grid--compact">
        <div class="stat-card"><div class="stat-icon">📚</div><div class="stat-number"><?php echo number_format(count($entries)); ?></div><div class="stat-label">Einträge geladen</div></div>
        <div class="stat-card"><div class="stat-icon">🌍</div><div class="stat-number"><?php echo number_format(count(array_filter($entries, static fn(array $item): bool => ((int) ($item['is_active'] ?? 0)) === 1))); ?></div><div class="stat-label">Aktiv im Frontend</div></div>
        <div class="stat-card"><div class="stat-icon">🏷️</div><div class="stat-number"><?php echo number_format(count(array_filter(array_map(static fn(array $item): string => trim((string) ($item['category'] ?? '')), $entries)))); ?></div><div class="stat-label">Kategorien belegt</div></div>
    </div>

    <div class="admin-card">
        <div class="kb-panel-header">
            <div>
                <h3>📚 Vorhandene Einträge</h3>
                <p>Listenansicht aller Begriffe mit Status, Kategorie und direktem Zugriff auf Editor oder öffentliche Seite.</p>
            </div>
        </div>

        <?php if (empty($entries)): ?>
            <div class="empty-state">
                <p class="kb-empty-icon">🧩</p>
                <p><strong>Noch keine Einträge vorhanden</strong></p>
                <p class="kb-empty-text">Lege den ersten Eintrag an oder importiere vorher CSV-Pakete über den Einstellungen-Tab „Import“.</p>
            </div>
        <?php else: ?>
            <div class="kb-list-table-wrap">
                <table class="kb-list-table">
                    <thead>
                        <tr>
                            <th>Titel</th>
                            <th>Keyword</th>
                            <th>Kategorie</th>
                            <th>Priorität</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <div><code><?php echo htmlspecialchars((string) $item['slug'], ENT_QUOTES, 'UTF-8'); ?></code></div>
                                </td>
                                <td><?php echo htmlspecialchars((string) $item['keyword'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($item['category'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int) ($item['priority'] ?? 100); ?></td>
                                <td>
                                    <span class="status-badge <?php echo ((int) ($item['is_active'] ?? 0) === 1) ? 'active' : 'inactive'; ?>">
                                        <?php echo ((int) ($item['is_active'] ?? 0) === 1) ? 'Aktiv' : 'Inaktiv'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="kb-action-row">
                                        <a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-entry-editor?edit=<?php echo (int) $item['id']; ?>" class="btn btn-secondary btn-sm">Bearbeiten</a>
                                        <a href="/kb/<?php echo rawurlencode((string) $item['slug']); ?>" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">Öffnen</a>
                                        <form method="post">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="delete_entry">
                                            <input type="hidden" name="entry_id" value="<?php echo (int) $item['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Löschen</button>
                                        </form>
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
