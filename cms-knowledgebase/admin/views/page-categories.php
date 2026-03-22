<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="kb-admin-shell">
    <div class="admin-page-header">
        <div>
            <h2>🗂️ Knowledgebase-Kategorien</h2>
            <p>Verwalte Kategorienamen, Sortierung und die sichtbaren Bereiche für Glossar und Knowledgebase.</p>
        </div>
        <div class="header-actions">
            <a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-entry-editor" class="btn btn-primary btn-sm">➕ Neuer Eintrag</a>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-<?php echo htmlspecialchars((string) ($notice['type'] ?? 'success'), ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars((string) ($notice['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-grid kb-admin-dashboard-grid kb-admin-dashboard-grid--compact">
        <div class="stat-card"><div class="stat-icon">🗂️</div><div class="stat-number"><?php echo number_format(count($categories)); ?></div><div class="stat-label">Kategorien</div></div>
        <div class="stat-card"><div class="stat-icon">📚</div><div class="stat-number"><?php echo number_format(array_sum(array_map(static fn(array $item): int => (int) ($item['entry_count'] ?? 0), $categories))); ?></div><div class="stat-label">Zugeordnete Einträge</div></div>
        <div class="stat-card"><div class="stat-icon">↕️</div><div class="stat-number"><?php echo number_format(count(array_filter($categories, static fn(array $item): bool => ((int) ($item['sort_order'] ?? 0)) > 0))); ?></div><div class="stat-label">Mit Sortierung</div></div>
    </div>

    <div class="kb-admin-grid kb-admin-grid--wide">
        <div class="admin-card kb-form-card kb-form-card--single">
            <div class="kb-panel-header">
                <div>
                    <h3><?php echo $categoryItem !== null ? 'Kategorie bearbeiten' : 'Neue Kategorie anlegen'; ?></h3>
                    <p>Der Name wird direkt für bestehende KB-Einträge übernommen und in den öffentlichen Filter-Buttons angezeigt.</p>
                </div>
            </div>

            <div class="kb-editor-help kb-editor-help--soft" aria-label="Kategorie-Hinweise">
                <span class="kb-editor-help__chip">Filter-Button im Frontend</span>
                <span class="kb-editor-help__chip">Umbenennen aktualisiert Einträge</span>
                <span class="kb-editor-help__chip">Sortierung steuert Reihenfolge</span>
            </div>

            <form method="post" class="kb-settings-form admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="save_category">
                <input type="hidden" name="category_id" value="<?php echo (int) ($categoryItem['id'] ?? 0); ?>">

                <div class="kb-form-grid">
                    <label>
                        <span>Kategoriename</span>
                        <input type="text" name="category_name" value="<?php echo htmlspecialchars((string) ($categoryItem['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </label>
                    <label>
                        <span>Sortierung</span>
                        <input type="number" name="sort_order" min="0" max="9999" value="<?php echo (int) ($categoryItem['sort_order'] ?? 0); ?>">
                    </label>
                </div>

                <div class="kb-form-actions kb-form-grid__full">
                    <button type="submit" class="btn btn-primary">💾 Kategorie speichern</button>
                    <?php if ($categoryItem !== null): ?>
                        <a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-categories" class="btn btn-secondary">Neu beginnen</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="admin-card">
            <div class="kb-panel-header">
                <div>
                    <h3>📋 Vorhandene Kategorien</h3>
                    <p>Direkt bearbeiten, sortieren oder löschen. Beim Umbenennen werden vorhandene KB-Einträge automatisch mitgezogen.</p>
                </div>
            </div>

            <?php if (empty($categories)): ?>
                <div class="empty-state">
                    <p class="kb-empty-icon">🗃️</p>
                    <p><strong>Noch keine Kategorien vorhanden</strong></p>
                    <p class="kb-empty-text">Lege die ersten Kategorien an oder speichere Einträge mit Kategoriebezug.</p>
                </div>
            <?php else: ?>
                <div class="kb-list-table-wrap">
                    <table class="kb-list-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Sortierung</th>
                                <th>Einträge</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) ($item['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="kb-table-secondary">Sichtbar als Filter in Knowledgebase und Glossar</div>
                                    </td>
                                    <td><code><?php echo htmlspecialchars((string) ($item['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                                    <td><?php echo (int) ($item['sort_order'] ?? 0); ?></td>
                                    <td><span class="kb-count-badge"><?php echo number_format((int) ($item['entry_count'] ?? 0)); ?> Einträge</span></td>
                                    <td>
                                        <div class="kb-action-row">
                                            <a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-categories?edit=<?php echo (int) ($item['id'] ?? 0); ?>" class="btn btn-secondary btn-sm">Bearbeiten</a>
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="delete_category">
                                                <input type="hidden" name="category_id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
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
</div>
