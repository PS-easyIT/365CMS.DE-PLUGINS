<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<div class="nl-admin-shell">
    <div class="admin-page-header">
        <div>
            <h2>Newsletter-Templates</h2>
            <p>Pflege Betreff, HTML-Inhalt und Text-Varianten für wiederverwendbare Kampagnenlayouts.</p>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
    <div class="alert alert-<?php echo ($notice['type'] ?? '') === 'success' ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars((string) ($notice['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php endif; ?>

    <div class="nl-panel-grid nl-panel-grid--wide">
        <div class="admin-card">
            <div class="nl-panel-header">
                <div>
                    <h3>Verfügbare Templates</h3>
                    <p>Aktive und vorbereitete Vorlagen für deine Newsletter-Kampagnen.</p>
                </div>
            </div>
            <?php if (empty($templates)): ?>
                <div class="empty-state">
                    <p class="nl-empty-icon">Keine Daten</p>
                    <p><strong>Keine Templates vorhanden</strong></p>
                    <p class="nl-empty-text">Lege rechts dein erstes Template an.</p>
                </div>
            <?php else: ?>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead><tr><th>Name</th><th>Betreff</th><th>Status</th><th>Aktionen</th></tr></thead>
                        <tbody>
                        <?php foreach ($templates as $item): ?>
                            <tr>
                                <td><a href="?edit=<?php echo (int) $item['id']; ?>" class="nl-admin-link"><?php echo htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                                <td><?php echo htmlspecialchars((string) $item['subject'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="nl-soft-badge nl-soft-badge--<?php echo htmlspecialchars((string) ($item['status'] ?? 'draft'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($item['status'] ?? 'draft'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td>
                                    <div class="nl-action-group">
                                        <a href="?edit=<?php echo (int) $item['id']; ?>" class="btn btn-sm btn-secondary">Bearbeiten</a>
                                        <form method="post">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="delete_template">
                                            <input type="hidden" name="template_id" value="<?php echo (int) $item['id']; ?>">
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
            <div class="nl-panel-header">
                <div>
                    <h3><?php echo $template ? 'Template bearbeiten' : 'Template anlegen'; ?></h3>
                    <p>Lege wiederverwendbare HTML- und Text-Inhalte an.</p>
                </div>
            </div>
            <form method="post" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="save_template">
                <input type="hidden" name="template_id" value="<?php echo (int) ($template['id'] ?? 0); ?>">

                <div class="form-group">
                    <label class="form-label">Template-Name</label>
                    <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars((string) ($template['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Betreff</label>
                    <input type="text" name="subject" class="form-control" required value="<?php echo htmlspecialchars((string) ($template['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">HTML-Inhalt</label>
                    <textarea name="content_html" rows="10" class="form-control"><?php echo htmlspecialchars((string) ($template['content_html'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Text-Fallback</label>
                    <textarea name="content_text" rows="6" class="form-control"><?php echo htmlspecialchars((string) ($template['content_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="draft" <?php echo (($template['status'] ?? 'draft') === 'draft') ? 'selected' : ''; ?>>Entwurf</option>
                        <option value="active" <?php echo (($template['status'] ?? '') === 'active') ? 'selected' : ''; ?>>Aktiv</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Template speichern</button>
            </form>
        </div>
    </div>
</div>
