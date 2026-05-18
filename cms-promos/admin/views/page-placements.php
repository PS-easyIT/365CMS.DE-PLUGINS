<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<div class="pr-admin-shell">
    <div class="admin-page-header">
        <div>
            <h2>Promo-Platzierungen</h2>
            <p>Verwalte Einbauorte wie Hero, Sidebar, CTA-Zonen oder thematische Promo-Container.</p>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
    <div class="alert alert-<?php echo ($notice['type'] ?? '') === 'success' ? 'success' : 'error'; ?>"><?php echo htmlspecialchars((string) ($notice['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="pr-panel-grid pr-panel-grid--wide">
        <div class="admin-card">
            <div class="pr-panel-header"><div><h3>Platzierungs-Liste</h3><p>Definierte Layout-Flächen mit Kapazität und Status.</p></div></div>
            <?php if (empty($placements)): ?>
                <div class="empty-state"><p class="pr-empty-icon">Keine Daten</p><p><strong>Keine Platzierungen vorhanden</strong></p></div>
            <?php else: ?>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead><tr><th>Name</th><th>Slug</th><th>Theme-Hook</th><th>Max</th><th>Status</th><th>Aktionen</th></tr></thead>
                        <tbody>
                        <?php foreach ($placements as $item): ?>
                            <tr>
                                <td><a href="?edit=<?php echo (int) $item['id']; ?>" class="pr-admin-link"><?php echo htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                                <td><?php echo htmlspecialchars((string) $item['slug'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php echo htmlspecialchars(CMS_Promos_Repository::instance()->get_theme_hook_label((string) ($item['theme_hook'] ?? 'manual')), ENT_QUOTES, 'UTF-8'); ?>
                                    <div class="pr-table-meta">Priorität <?php echo (int) ($item['hook_priority'] ?? 10); ?></div>
                                </td>
                                <td><?php echo (int) ($item['max_items'] ?? 0); ?></td>
                                <td><span class="status-badge <?php echo ($item['status'] ?? 'active') === 'active' ? 'active' : 'inactive'; ?>"><?php echo htmlspecialchars((string) ($item['status'] ?? 'active'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td>
                                    <div class="pr-action-group">
                                        <a href="?edit=<?php echo (int) $item['id']; ?>" class="btn btn-sm btn-secondary">Bearbeiten</a>
                                        <form method="post">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="delete_placement">
                                            <input type="hidden" name="placement_id" value="<?php echo (int) $item['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Löschen</button>
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
            <div class="pr-panel-header"><div><h3><?php echo $placement ? 'Platzierung bearbeiten' : 'Platzierung anlegen'; ?></h3><p>Lege Slug, Beschreibung, Kapazität und die automatische Hook-Einbindung für Promo-Slots fest.</p></div></div>
            <form method="post" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="save_placement">
                <input type="hidden" name="placement_id" value="<?php echo (int) ($placement['id'] ?? 0); ?>">

                <div class="form-group"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars((string) ($placement['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="form-group"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars((string) ($placement['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="form-group"><label class="form-label">Beschreibung</label><textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars((string) ($placement['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                <div class="pr-form-grid">
                    <div class="form-group"><label class="form-label">Status</label><select name="status" class="form-control"><option value="active" <?php echo (($placement['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Aktiv</option><option value="inactive" <?php echo (($placement['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inaktiv</option></select></div>
                    <div class="form-group"><label class="form-label">Maximale Einträge</label><input type="number" name="max_items" class="form-control" min="1" value="<?php echo (int) ($placement['max_items'] ?? 3); ?>"></div>
                </div>
                <div class="pr-form-grid">
                    <div class="form-group">
                        <label class="form-label">Theme-Hook</label>
                        <select name="theme_hook" class="form-control">
                            <?php foreach ($hookOptions as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($placement['theme_hook'] ?? 'manual') === $key) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-help">Aktive Platzierungen werden automatisch im gewählten Theme-Hook ausgegeben.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hook-Priorität</label>
                        <input type="number" name="hook_priority" class="form-control" value="<?php echo (int) ($placement['hook_priority'] ?? 10); ?>">
                        <div class="form-help">Niedrigere Werte werden innerhalb desselben Hooks zuerst gerendert.</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Platzierung speichern</button>
            </form>
        </div>
    </div>
</div>
