<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>
<?php $editing = is_array($category); ?>

<?php
$activeCategories = count(array_filter($categories, static fn(array $item): bool => ($item['status'] ?? 'inactive') === 'active'));
$categoryDownloads = array_sum(array_map(static fn(array $item): int => (int) ($item['download_count'] ?? 0), $categories));
?>

<div class="dl-admin-shell">

<div class="admin-page-header">
    <div>
        <h2>🗂️ Download-Kategorien</h2>
        <p>Kategorien strukturieren das öffentliche Archiv und die Admin-Verwaltung.</p>
    </div>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-<?php echo ($notice['type'] ?? '') === 'success' ? 'success' : 'error'; ?>">
    <?php echo htmlspecialchars((string) ($notice['message'] ?? '')); ?>
</div>
<?php endif; ?>

<div class="dl-card-grid">
    <div class="dl-info-card">
        <span class="dl-info-card__eyebrow">Kategorien gesamt</span>
        <span class="dl-info-card__value"><?php echo number_format(count($categories)); ?></span>
        <span class="dl-info-card__text">Gruppen für saubere Archiv-Navigation und Verwaltung.</span>
    </div>
    <div class="dl-info-card">
        <span class="dl-info-card__eyebrow">Aktiv</span>
        <span class="dl-info-card__value"><?php echo number_format($activeCategories); ?></span>
        <span class="dl-info-card__text">Im Frontend nutzbare Kategorien.</span>
    </div>
    <div class="dl-info-card">
        <span class="dl-info-card__eyebrow">Zugeordnete Downloads</span>
        <span class="dl-info-card__value"><?php echo number_format($categoryDownloads); ?></span>
        <span class="dl-info-card__text">Dateien, die bereits in Kategorien einsortiert sind.</span>
    </div>
</div>

<div class="dl-admin-grid-2">
    <div class="admin-card">
        <div class="dl-panel-header">
            <div>
                <h3><?php echo $editing ? '✏️ Kategorie bearbeiten' : '➕ Neue Kategorie'; ?></h3>
                <p>Icons, Slugs und Sortierung für ein übersichtliches Archiv verwalten.</p>
            </div>
        </div>
        <form method="post" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="save_category">
            <input type="hidden" name="category_id" value="<?php echo (int) ($category['id'] ?? 0); ?>">
            <input type="hidden" name="_edit_id" value="<?php echo (int) ($category['id'] ?? 0); ?>">

            <div class="form-group">
                <label class="form-label">Name <span class="dl-required">*</span></label>
                <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars((string) ($category['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="dl-admin-form-grid">
                <div class="form-group">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars((string) ($category['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Icon / Emoji</label>
                    <input type="text" name="icon" class="form-control" value="<?php echo htmlspecialchars((string) ($category['icon'] ?? '📁'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Beschreibung</label>
                <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars((string) ($category['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div class="dl-admin-form-grid">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?php echo ($category['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Aktiv</option>
                        <option value="inactive" <?php echo ($category['status'] ?? 'active') === 'inactive' ? 'selected' : ''; ?>>Inaktiv</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Sortierung</label>
                    <input type="number" name="sort_order" class="form-control" min="0" value="<?php echo (int) ($category['sort_order'] ?? 0); ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">💾 Kategorie speichern</button>
        </form>
    </div>

    <div class="dl-side-stack">
        <div class="admin-card">
            <div class="dl-panel-header">
                <div>
                    <h3>📚 Vorhandene Kategorien</h3>
                    <p>Pflege bestehende Archivgruppen und prüfe ihre Nutzung.</p>
                </div>
            </div>
            <?php if (empty($categories)): ?>
                <div class="empty-state">
                    <p class="dl-empty-icon">🗂️</p>
                    <p><strong>Noch keine Kategorien vorhanden</strong></p>
                </div>
            <?php else: ?>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead><tr><th>Name</th><th>Status</th><th>Downloads</th><th>Aktionen</th></tr></thead>
                        <tbody>
                        <?php foreach ($categories as $item): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars((string) ($item['icon'] ?? '📁')); ?>
                                    <strong><?php echo htmlspecialchars((string) $item['name']); ?></strong>
                                    <div class="dl-admin-muted"><?php echo htmlspecialchars((string) ($item['slug'] ?? '')); ?></div>
                                </td>
                                <td><span class="status-badge <?php echo ($item['status'] ?? 'active') === 'active' ? 'active' : 'inactive'; ?>"><?php echo htmlspecialchars((string) ($item['status'] ?? 'active')); ?></span></td>
                                <td><?php echo number_format((int) ($item['download_count'] ?? 0)); ?></td>
                                <td>
                                    <div class="dl-admin-row-actions">
                                        <a href="?edit=<?php echo (int) $item['id']; ?>" class="btn btn-secondary btn-sm">✏️</a>
                                        <form method="post" class="dl-inline-form">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="delete_category">
                                            <input type="hidden" name="category_id" value="<?php echo (int) $item['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
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

        <div class="dl-note-card">
            <span class="dl-note-card__eyebrow">Organisation</span>
            <span class="dl-note-card__title">Kategorien sind dein Frontend-Navi</span>
            <span class="dl-note-card__text">Saubere Slugs und eindeutige Icons helfen Besuchern, Downloads schneller zu finden und geben dem Archiv mehr Struktur.</span>
        </div>
    </div>
</div>

</div>
