<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>
<?php $editing = is_array($download); ?>

<?php
$activeDownloads = count(array_filter($downloads, static fn(array $item): bool => ($item['status'] ?? 'inactive') === 'active'));
$featuredDownloads = count(array_filter($downloads, static fn(array $item): bool => !empty($item['is_featured'])));
$loginProtected = count(array_filter($downloads, static fn(array $item): bool => !empty($item['requires_login'])));
?>

<div class="dl-admin-shell">

<div class="admin-page-header">
    <div>
        <h2>📦 Downloads</h2>
        <p>Dateien, öffentliche Download-Links und Vorlagen zentral verwalten.</p>
    </div>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-<?php echo ($notice['type'] ?? '') === 'success' ? 'success' : 'error'; ?>">
    <?php echo htmlspecialchars((string) ($notice['message'] ?? '')); ?>
</div>
<?php endif; ?>

<div class="dl-card-grid">
    <div class="dl-info-card">
        <span class="dl-info-card__eyebrow">Downloads gesamt</span>
        <span class="dl-info-card__value"><?php echo number_format(count($downloads)); ?></span>
        <span class="dl-info-card__text">Einträge mit Datei oder externer Download-Quelle.</span>
    </div>
    <div class="dl-info-card">
        <span class="dl-info-card__eyebrow">Aktiv</span>
        <span class="dl-info-card__value"><?php echo number_format($activeDownloads); ?></span>
        <span class="dl-info-card__text">Diese Einträge sind öffentlich sichtbar.</span>
    </div>
    <div class="dl-info-card">
        <span class="dl-info-card__eyebrow">Featured</span>
        <span class="dl-info-card__value"><?php echo number_format($featuredDownloads); ?></span>
        <span class="dl-info-card__text">Hervorgehobene Inhalte für besondere Platzierung.</span>
    </div>
    <div class="dl-info-card">
        <span class="dl-info-card__eyebrow">Login-Schutz</span>
        <span class="dl-info-card__value"><?php echo number_format($loginProtected); ?></span>
        <span class="dl-info-card__text">Downloads, die nur für eingeloggte Nutzer sichtbar sind.</span>
    </div>
</div>

<div class="dl-admin-grid-2">
    <div class="admin-card">
        <div class="dl-panel-header">
            <div>
                <h3><?php echo $editing ? '✏️ Download bearbeiten' : '➕ Neuer Download'; ?></h3>
                <p>Datei, externe Quelle, Sichtbarkeit und Kategorisierung in einer kompakten Karte pflegen.</p>
            </div>
        </div>
        <form method="post" class="admin-form" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="save_download">
            <input type="hidden" name="download_id" value="<?php echo (int) ($download['id'] ?? 0); ?>">
            <input type="hidden" name="_edit_id" value="<?php echo (int) ($download['id'] ?? 0); ?>">

            <div class="form-group">
                <label class="form-label">Titel <span class="dl-required">*</span></label>
                <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars((string) ($download['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="dl-admin-form-grid">
                <div class="form-group">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars((string) ($download['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Version</label>
                    <input type="text" name="version_label" class="form-control" value="<?php echo htmlspecialchars((string) ($download['version_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="z. B. 1.0.0">
                </div>
            </div>

            <div class="dl-admin-form-grid">
                <div class="form-group">
                    <label class="form-label">Kategorie</label>
                    <select name="category_id" class="form-control">
                        <option value="0">— Ohne Kategorie —</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo (int) $category['id']; ?>" <?php echo (int) ($download['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) ($category['icon'] ?? '📁') . ' ' . $category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Template / Typ</label>
                    <select name="download_type" class="form-control">
                        <?php foreach ($typeTemplates as $typeKey => $typeConfig): ?>
                            <option value="<?php echo htmlspecialchars($typeKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($download['download_type'] ?? 'generic') === $typeKey ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) ($typeConfig['icon'] . ' ' . $typeConfig['label'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Kurzbeschreibung</label>
                <textarea name="summary" class="form-control" rows="3"><?php echo htmlspecialchars((string) ($download['summary'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Beschreibung</label>
                <textarea name="description" class="form-control" rows="6"><?php echo htmlspecialchars((string) ($download['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Datei hochladen</label>
                <input type="file" name="download_file" class="form-control" accept=".pdf,.doc,.docx,.txt,.rtf,.csv,.zip,.rar,.7z,.tar,.gz,.ppt,.pptx,.xls,.xlsx">
                <small class="form-text">Für PowerShell- oder Webprojekt-Downloads am besten ZIP-Pakete verwenden – sicherer und sauberer als rohe Skripte.</small>
            </div>

            <div class="form-group">
                <label class="form-label">Externe Download-URL</label>
                <input type="url" name="external_url" class="form-control" value="<?php echo htmlspecialchars((string) ($download['external_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://example.com/datei.zip">
            </div>

            <?php if (!empty($download['file_name'])): ?>
                <div class="alert alert-success">Aktuelle Datei: <strong><?php echo htmlspecialchars((string) $download['file_name']); ?></strong></div>
            <?php endif; ?>

            <div class="dl-admin-form-grid">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?php echo ($download['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Aktiv</option>
                        <option value="inactive" <?php echo ($download['status'] ?? 'active') === 'inactive' ? 'selected' : ''; ?>>Inaktiv</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Sortierung</label>
                    <input type="number" name="sort_order" class="form-control" min="0" value="<?php echo (int) ($download['sort_order'] ?? 0); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label"><input type="checkbox" name="requires_login" value="1" <?php echo !empty($download['requires_login']) ? 'checked' : ''; ?>> Login erforderlich</label>
                <label class="checkbox-label"><input type="checkbox" name="is_featured" value="1" <?php echo !empty($download['is_featured']) ? 'checked' : ''; ?>> Als Featured markieren</label>
            </div>

            <div class="dl-note-card dl-form-note">
                <span class="dl-note-card__eyebrow">Best Practice</span>
                <span class="dl-note-card__title">Pakete statt rohe Skripte</span>
                <span class="dl-note-card__text">PowerShell- und Webprojekt-Downloads sind als ZIP-Pakete am saubersten, sichersten und für Besucher am verständlichsten.</span>
            </div>

            <button type="submit" class="btn btn-primary">💾 Download speichern</button>
        </form>
    </div>

    <div class="dl-side-stack">
        <div class="admin-card">
            <div class="dl-panel-header">
                <div>
                    <h3>📋 Bestehende Downloads</h3>
                    <p>Schneller Überblick über alle Einträge inklusive Typ und Kategorie.</p>
                </div>
            </div>
            <?php if (empty($downloads)): ?>
                <div class="empty-state">
                    <p class="dl-empty-icon">📭</p>
                    <p><strong>Noch keine Downloads angelegt</strong></p>
                    <p class="dl-empty-text">Erstelle rechts den ersten Download-Eintrag mit Datei oder externer URL.</p>
                </div>
            <?php else: ?>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead><tr><th>Titel</th><th>Kategorie</th><th>Typ</th><th>Status</th><th>Aktionen</th></tr></thead>
                        <tbody>
                        <?php foreach ($downloads as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars((string) $item['title']); ?></strong>
                                    <div class="dl-admin-muted"><?php echo htmlspecialchars((string) ($item['file_name'] ?? $item['external_url'] ?? '')); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars((string) ($item['category_name'] ?? '—')); ?></td>
                                <td><?php echo htmlspecialchars((string) ($typeTemplates[$item['download_type']]['label'] ?? $item['download_type'])); ?></td>
                                <td><span class="status-badge <?php echo ($item['status'] ?? 'active') === 'active' ? 'active' : 'inactive'; ?>"><?php echo htmlspecialchars((string) ($item['status'] ?? 'active')); ?></span></td>
                                <td>
                                    <div class="dl-admin-row-actions">
                                        <a href="?edit=<?php echo (int) $item['id']; ?>" class="btn btn-secondary btn-sm">✏️</a>
                                        <form method="post" class="dl-inline-form">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="delete_download">
                                            <input type="hidden" name="download_id" value="<?php echo (int) $item['id']; ?>">
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

        <div class="admin-card">
            <div class="dl-panel-header">
                <div>
                    <h3>🧩 Download-Typen</h3>
                    <p>Vordefinierte Einstiegs-Typen für typische Inhalte.</p>
                </div>
            </div>
            <div class="dl-template-grid">
                <?php foreach ($typeTemplates as $typeConfig): ?>
                <div class="dl-template-card">
                    <div class="dl-template-card__icon"><?php echo htmlspecialchars((string) ($typeConfig['icon'] ?? '📦')); ?></div>
                    <p class="dl-template-card__title"><?php echo htmlspecialchars((string) ($typeConfig['label'] ?? 'Download')); ?></p>
                    <p class="dl-template-card__text"><?php echo htmlspecialchars((string) ($typeConfig['description'] ?? 'Vordefinierter Download-Typ.')); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

</div>
