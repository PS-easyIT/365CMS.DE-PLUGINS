<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<div class="nl-admin-shell">
    <div class="admin-page-header">
        <div>
            <h2>Newsletter-Kampagnen</h2>
            <p>Plane Versandläufe, verknüpfe Templates und bereite Segment-basierte Newsletter-Wellen vor.</p>
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
                    <h3>Kampagnen-Plan</h3>
                    <p>Entwürfe, versandbereite Kampagnen und geplante Aussendungen.</p>
                </div>
            </div>
            <?php if (empty($campaigns)): ?>
                <div class="empty-state">
                    <p class="nl-empty-icon">Keine Daten</p>
                    <p><strong>Noch keine Kampagnen angelegt</strong></p>
                    <p class="nl-empty-text">Nutze Templates und Segmente, um deinen ersten Versand vorzubereiten.</p>
                </div>
            <?php else: ?>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead><tr><th>Name</th><th>Template</th><th>Segment</th><th>Status</th><th>Empfänger</th><th>Aktionen</th></tr></thead>
                        <tbody>
                        <?php foreach ($campaigns as $item): ?>
                            <tr>
                                <td><a href="?edit=<?php echo (int) $item['id']; ?>" class="nl-admin-link"><?php echo htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                                <td><?php echo htmlspecialchars((string) ($item['template_name'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) (($item['segment_slug'] ?? '') !== '' ? $item['segment_slug'] : 'alle aktiven'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="nl-soft-badge nl-soft-badge--<?php echo htmlspecialchars((string) ($item['status'] ?? 'draft'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($item['status'] ?? 'draft'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo number_format((int) ($item['recipient_count'] ?? 0)); ?></td>
                                <td>
                                    <div class="nl-action-group">
                                        <a href="?edit=<?php echo (int) $item['id']; ?>" class="btn btn-sm btn-secondary">Bearbeiten</a>
                                        <form method="post">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="delete_campaign">
                                            <input type="hidden" name="redirect_slug" value="<?php echo htmlspecialchars((string) ($activeSlug ?? 'newsletter-campaigns'), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="campaign_id" value="<?php echo (int) $item['id']; ?>">
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
                    <h3><?php echo $campaign ? 'Kampagne bearbeiten' : 'Kampagne planen'; ?></h3>
                    <p>Segment, Timing und Betreff zentral vorbereiten.</p>
                </div>
            </div>
            <form method="post" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="save_campaign">
                <input type="hidden" name="redirect_slug" value="<?php echo htmlspecialchars((string) ($activeSlug ?? 'newsletter-campaigns'), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="campaign_id" value="<?php echo (int) ($campaign['id'] ?? 0); ?>">

                <div class="form-group">
                    <label class="form-label">Kampagnenname</label>
                    <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars((string) ($campaign['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Betreff</label>
                    <input type="text" name="subject" class="form-control" required value="<?php echo htmlspecialchars((string) ($campaign['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Preview-Text</label>
                    <input type="text" name="preview_text" class="form-control" value="<?php echo htmlspecialchars((string) ($campaign['preview_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="nl-form-grid">
                    <div class="form-group">
                        <label class="form-label">Template</label>
                        <select name="template_id" class="form-control">
                            <option value="0">Ohne Template</option>
                            <?php foreach ($templates as $item): ?>
                                <option value="<?php echo (int) $item['id']; ?>" <?php echo ((int) ($campaign['template_id'] ?? 0) === (int) $item['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Segment</label>
                        <input type="text" name="segment_slug" class="form-control" value="<?php echo htmlspecialchars((string) ($campaign['segment_slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="leer = alle aktiven">
                    </div>
                </div>
                <div class="nl-form-grid">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <?php foreach (['draft' => 'Entwurf', 'ready' => 'Versandbereit', 'scheduled' => 'Geplant', 'sent' => 'Versendet'] as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($campaign['status'] ?? 'draft') === $key) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Geplanter Versand</label>
                        <?php
                        $scheduledValue = '';
                        if (!empty($campaign['scheduled_at'])) {
                            try {
                                $scheduledValue = (new DateTimeImmutable((string) $campaign['scheduled_at']))->format('Y-m-d\TH:i');
                            } catch (Throwable) {
                                $scheduledValue = '';
                            }
                        }
                        ?>
                        <input type="datetime-local" name="scheduled_at" class="form-control" value="<?php echo htmlspecialchars($scheduledValue, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Kampagne speichern</button>
            </form>
        </div>
    </div>
</div>
