<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<div class="pr-admin-shell">
    <div class="admin-page-header">
        <div>
            <h2>Promos verwalten</h2>
            <p>Pflege CTA-Kacheln, Banner und Kampagnenziele inklusive Priorität, Laufzeit und Tracking-Grundlage.</p>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
    <div class="alert alert-<?php echo ($notice['type'] ?? '') === 'success' ? 'success' : 'error'; ?>"><?php echo htmlspecialchars((string) ($notice['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="pr-panel-grid pr-panel-grid--wide">
        <div class="admin-card">
            <div class="pr-panel-header"><div><h3>Promo-Liste</h3><p>Alle CTA- und Promo-Elemente mit Status, Platzierung und Klickziel.</p></div></div>
            <?php if (empty($promos)): ?>
                <div class="empty-state"><p class="pr-empty-icon">Keine Daten</p><p><strong>Keine Promos vorhanden</strong></p><p class="pr-empty-text">Rechts kannst du direkt die erste Promo anlegen.</p></div>
            <?php else: ?>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead><tr><th>Titel</th><th>Platzierung</th><th>Status</th><th>Priorität</th><th>Aktionen</th></tr></thead>
                        <tbody>
                        <?php foreach ($promos as $item): ?>
                            <tr>
                                <td><a href="?edit=<?php echo (int) $item['id']; ?>" class="pr-admin-link"><?php echo htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                                <td>
                                    <?php echo htmlspecialchars((string) ($item['placement_name'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (!empty($item['placement_theme_hook'])): ?>
                                        <div class="pr-table-meta"><?php echo htmlspecialchars(CMS_Promos_Repository::instance()->get_theme_hook_label((string) $item['placement_theme_hook']), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="pr-soft-badge pr-soft-badge--<?php echo htmlspecialchars((string) ($item['status'] ?? 'draft'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($item['status'] ?? 'draft'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo (int) ($item['priority'] ?? 0); ?></td>
                                <td>
                                    <div class="pr-action-group">
                                        <a href="?edit=<?php echo (int) $item['id']; ?>" class="btn btn-sm btn-secondary">Bearbeiten</a>
                                        <form method="post">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="delete_promo">
                                            <input type="hidden" name="promo_id" value="<?php echo (int) $item['id']; ?>">
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
            <div class="pr-panel-header"><div><h3><?php echo $promo ? 'Promo bearbeiten' : 'Promo anlegen'; ?></h3><p>Lege Titel, CTA-Ziel und Platzierung in einem Schritt fest.</p></div></div>
            <form method="post" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="save_promo">
                <input type="hidden" name="promo_id" value="<?php echo (int) ($promo['id'] ?? 0); ?>">

                <div class="form-group"><label class="form-label">Titel</label><input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars((string) ($promo['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="form-group"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars((string) ($promo['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="form-group"><label class="form-label">Teaser</label><textarea name="teaser" class="form-control" rows="3"><?php echo htmlspecialchars((string) ($promo['teaser'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                <div class="form-group"><label class="form-label">Inhalt / Zusatztext</label><textarea name="content_html" class="form-control" rows="6"><?php echo htmlspecialchars((string) ($promo['content_html'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                <div class="pr-form-grid">
                    <div class="form-group"><label class="form-label">Ziel-URL</label><input type="url" name="target_url" class="form-control" value="<?php echo htmlspecialchars((string) ($promo['target_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="form-group"><label class="form-label">Button-Label</label><input type="text" name="button_label" class="form-control" value="<?php echo htmlspecialchars((string) ($promo['button_label'] ?? 'Mehr erfahren'), ENT_QUOTES, 'UTF-8'); ?>"></div>
                </div>
                <div class="pr-form-grid">
                    <div class="form-group"><label class="form-label">Bild-URL</label><input type="url" name="image_url" class="form-control" value="<?php echo htmlspecialchars((string) ($promo['image_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="form-group"><label class="form-label">Platzierung</label><select name="placement_id" class="form-control"><option value="0">Keine feste Platzierung</option><?php foreach ($placements as $placement): ?><option value="<?php echo (int) $placement['id']; ?>" <?php echo ((int) ($promo['placement_id'] ?? 0) === (int) $placement['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $placement['name'], ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars(CMS_Promos_Repository::instance()->get_theme_hook_label((string) ($placement['theme_hook'] ?? 'manual')), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
                </div>
                <div class="pr-form-grid">
                    <div class="form-group"><label class="form-label">Status</label><select name="status" class="form-control"><?php foreach (['draft' => 'Entwurf', 'active' => 'Aktiv', 'paused' => 'Pausiert', 'archived' => 'Archiviert'] as $key => $label): ?><option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($promo['status'] ?? 'draft') === $key) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label class="form-label">Priorität</label><input type="number" name="priority" class="form-control" min="0" value="<?php echo (int) ($promo['priority'] ?? 0); ?>"></div>
                </div>
                <div class="pr-form-grid">
                    <div class="form-group"><label class="form-label">Start</label><?php $startValue = !empty($promo['start_at']) ? date('Y-m-d\TH:i', strtotime((string) $promo['start_at'])) : ''; ?><input type="datetime-local" name="start_at" class="form-control" value="<?php echo htmlspecialchars($startValue, ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="form-group"><label class="form-label">Ende</label><?php $endValue = !empty($promo['end_at']) ? date('Y-m-d\TH:i', strtotime((string) $promo['end_at'])) : ''; ?><input type="datetime-local" name="end_at" class="form-control" value="<?php echo htmlspecialchars($endValue, ENT_QUOTES, 'UTF-8'); ?>"></div>
                </div>
                <label class="checkbox-label pr-checkbox-stack"><input type="checkbox" name="is_featured" value="1" <?php echo !empty($promo['is_featured']) ? 'checked' : ''; ?>> Als Featured-Promo hervorheben</label>

                <button type="submit" class="btn btn-primary">Promo speichern</button>
            </form>
        </div>
    </div>
</div>
