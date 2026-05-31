<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<div class="nl-admin-shell">
    <div class="admin-page-header">
        <div>
            <h2>Newsletter-Abonnenten</h2>
            <p>Verwalte Leads, Segmente, Opt-In-Status und manuell angelegte Newsletter-Kontakte.</p>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
    <div class="alert alert-<?php echo ($notice['type'] ?? '') === 'success' ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars((string) ($notice['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php endif; ?>

    <div class="nl-card-grid">
        <div class="nl-info-card"><span class="nl-info-card__eyebrow">Gesamt</span><span class="nl-info-card__value"><?php echo number_format((int) ($stats['subscribers'] ?? 0)); ?></span><span class="nl-info-card__text">Alle importierten und manuell angelegten Kontakte.</span></div>
        <div class="nl-info-card"><span class="nl-info-card__eyebrow">Aktiv</span><span class="nl-info-card__value"><?php echo number_format((int) ($stats['active_subscribers'] ?? 0)); ?></span><span class="nl-info-card__text">Direkt erreichbar für Kampagnen.</span></div>
        <div class="nl-info-card"><span class="nl-info-card__eyebrow">Pending</span><span class="nl-info-card__value"><?php echo number_format((int) ($stats['pending_subscribers'] ?? 0)); ?></span><span class="nl-info-card__text">Noch nicht bestätigte Anmeldungen.</span></div>
    </div>

    <div class="nl-panel-grid nl-panel-grid--wide">
        <div class="admin-card">
            <div class="nl-panel-header">
                <div>
                    <h3>Kontaktliste</h3>
                    <p>Bearbeite Einträge direkt oder ergänze neue Leads auf der rechten Seite.</p>
                </div>
            </div>
            <?php if (empty($subscribers)): ?>
                <div class="empty-state">
                    <p class="nl-empty-icon">Keine Daten</p>
                    <p><strong>Noch keine Newsletter-Kontakte</strong></p>
                    <p class="nl-empty-text">Füge rechts den ersten Kontakt hinzu oder sammle Leads über die öffentliche Anmeldeseite.</p>
                </div>
            <?php else: ?>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead><tr><th>E-Mail</th><th>Name</th><th>Segment</th><th>Status</th><th>Aktionen</th></tr></thead>
                        <tbody>
                        <?php foreach ($subscribers as $item): ?>
                            <tr>
                                <td><a href="?edit=<?php echo (int) $item['id']; ?>" class="nl-admin-link"><?php echo htmlspecialchars((string) $item['email'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                                <td><?php echo htmlspecialchars(trim((string) (($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? ''))) ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) (($item['segment_slug'] ?? '') !== '' ? $item['segment_slug'] : 'general'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="nl-soft-badge nl-soft-badge--<?php echo htmlspecialchars((string) ($item['status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($item['status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td>
                                    <div class="nl-action-group">
                                        <a href="?edit=<?php echo (int) $item['id']; ?>" class="btn btn-sm btn-secondary">Bearbeiten</a>
                                        <form method="post">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="delete_subscriber">
                                            <input type="hidden" name="redirect_slug" value="<?php echo htmlspecialchars((string) ($activeSlug ?? 'newsletter-subscribers'), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="subscriber_id" value="<?php echo (int) $item['id']; ?>">
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
                    <h3><?php echo $subscriber ? 'Abonnent bearbeiten' : 'Abonnent anlegen'; ?></h3>
                    <p>E-Mail, Segment und Status zentral pflegen.</p>
                </div>
            </div>
            <form method="post" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="save_subscriber">
                <input type="hidden" name="redirect_slug" value="<?php echo htmlspecialchars((string) ($activeSlug ?? 'newsletter-subscribers'), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="subscriber_id" value="<?php echo (int) ($subscriber['id'] ?? 0); ?>">
                <input type="hidden" name="source" value="admin">

                <div class="form-group">
                    <label class="form-label">E-Mail-Adresse</label>
                    <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars((string) ($subscriber['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="nl-form-grid">
                    <div class="form-group">
                        <label class="form-label">Vorname</label>
                        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars((string) ($subscriber['first_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nachname</label>
                        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars((string) ($subscriber['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <div class="nl-form-grid">
                    <div class="form-group">
                        <label class="form-label">Segment</label>
                        <input type="text" name="segment_slug" class="form-control" value="<?php echo htmlspecialchars((string) ($subscriber['segment_slug'] ?? 'general'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="general, events, product">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <?php foreach (['pending' => 'Pending', 'active' => 'Aktiv', 'unsubscribed' => 'Abgemeldet', 'bounced' => 'Bounce'] as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($subscriber['status'] ?? 'pending') === $key) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Abonnent speichern</button>
            </form>
        </div>
    </div>
</div>
