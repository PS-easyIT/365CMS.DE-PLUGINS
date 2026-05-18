<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<div class="nl-admin-shell">
    <div class="admin-page-header">
        <div>
            <h2>Newsletter-Dashboard</h2>
            <p>Zentrale Übersicht zu Listenwachstum, Templates, Kampagnen und Versandbereitschaft.</p>
        </div>
        <div class="header-actions">
            <a href="/newsletter" class="btn btn-secondary" target="_blank" rel="noopener noreferrer">Öffentliche Seite</a>
            <a href="/admin/plugins/newsletter-dashboard/newsletter-campaigns" class="btn btn-primary">Kampagne planen</a>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
    <div class="alert alert-<?php echo ($notice['type'] ?? '') === 'success' ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars((string) ($notice['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php endif; ?>

    <div class="dashboard-grid">
        <div class="stat-card"><div class="stat-icon">ALL</div><div class="stat-number"><?php echo number_format((int) ($stats['subscribers'] ?? 0)); ?></div><div class="stat-label">Abonnenten</div></div>
        <div class="stat-card"><div class="stat-icon">ACT</div><div class="stat-number"><?php echo number_format((int) ($stats['active_subscribers'] ?? 0)); ?></div><div class="stat-label">Aktiv</div></div>
        <div class="stat-card"><div class="stat-icon">PEN</div><div class="stat-number"><?php echo number_format((int) ($stats['pending_subscribers'] ?? 0)); ?></div><div class="stat-label">Pending</div></div>
        <div class="stat-card"><div class="stat-icon">TPL</div><div class="stat-number"><?php echo number_format((int) ($stats['templates'] ?? 0)); ?></div><div class="stat-label">Templates</div></div>
        <div class="stat-card"><div class="stat-icon">CMP</div><div class="stat-number"><?php echo number_format((int) ($stats['campaigns'] ?? 0)); ?></div><div class="stat-label">Kampagnen</div></div>
        <div class="stat-card"><div class="stat-icon">RDY</div><div class="stat-number"><?php echo number_format((int) ($stats['ready_campaigns'] ?? 0)); ?></div><div class="stat-label">Versandbereit</div></div>
    </div>

    <div class="nl-card-grid">
        <div class="nl-info-card">
            <span class="nl-info-card__eyebrow">Sender</span>
            <span class="nl-info-card__value"><?php echo htmlspecialchars((string) ($settings['sender_name'] ?? '365 Network'), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="nl-info-card__text"><?php echo htmlspecialchars((string) ($settings['sender_email'] ?? 'newsletter@example.com'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="nl-info-card">
            <span class="nl-info-card__eyebrow">Double Opt-In</span>
            <span class="nl-info-card__value"><?php echo !empty($settings['require_double_opt_in']) ? 'Aktiv' : 'Direkt'; ?></span>
            <span class="nl-info-card__text">Steuert, ob neue Leads zuerst bestätigt werden müssen.</span>
        </div>
        <div class="nl-info-card">
            <span class="nl-info-card__eyebrow">Send-Log</span>
            <span class="nl-info-card__value"><?php echo number_format((int) ($stats['sent_entries'] ?? 0)); ?></span>
            <span class="nl-info-card__text">Gespeicherte Versandereignisse und Reaktionen.</span>
        </div>
    </div>

    <div class="nl-feature-grid">
        <a href="/admin/plugins/newsletter-dashboard/newsletter-subscribers" class="nl-feature-card">
            <span class="nl-feature-card__icon">ABO</span>
            <span>
                <span class="nl-feature-card__eyebrow">Audience</span>
                <span class="nl-feature-card__title">Abonnenten pflegen</span>
                <span class="nl-feature-card__text">Segmente, Status und Lead-Quellen zentral verwalten.</span>
            </span>
        </a>
        <a href="/admin/plugins/newsletter-dashboard/newsletter-templates" class="nl-feature-card">
            <span class="nl-feature-card__icon">TPL</span>
            <span>
                <span class="nl-feature-card__eyebrow">Content</span>
                <span class="nl-feature-card__title">Templates vorbereiten</span>
                <span class="nl-feature-card__text">Betreff, HTML-Content und Text-Fallbacks sauber organisieren.</span>
            </span>
        </a>
        <a href="/admin/plugins/newsletter-dashboard/newsletter-campaigns" class="nl-feature-card">
            <span class="nl-feature-card__icon">KMP</span>
            <span>
                <span class="nl-feature-card__eyebrow">Versand</span>
                <span class="nl-feature-card__title">Kampagnen anlegen</span>
                <span class="nl-feature-card__text">Segment wählen, Termin setzen und Versandstatus vorbereiten.</span>
            </span>
        </a>
    </div>

    <div class="nl-panel-grid">
        <div class="admin-card">
            <div class="nl-panel-header">
                <div>
                    <h3>Letzte Abonnenten</h3>
                    <p>Die neuesten Leads inklusive Segment und Status.</p>
                </div>
                <a href="/admin/plugins/newsletter-dashboard/newsletter-subscribers" class="btn btn-secondary btn-sm">Verwalten</a>
            </div>
            <?php if (empty($subscribers)): ?>
                <div class="empty-state">
                    <p class="nl-empty-icon">Keine Daten</p>
                    <p><strong>Noch keine Abonnenten vorhanden</strong></p>
                    <p class="nl-empty-text">Lege den ersten Kontakt an oder nutze die öffentliche Newsletter-Seite.</p>
                </div>
            <?php else: ?>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead><tr><th>E-Mail</th><th>Segment</th><th>Status</th><th>Quelle</th></tr></thead>
                        <tbody>
                        <?php foreach ($subscribers as $subscriber): ?>
                            <tr>
                                <td><a href="/admin/plugins/newsletter-dashboard/newsletter-subscribers?edit=<?php echo (int) $subscriber['id']; ?>" class="nl-admin-link"><?php echo htmlspecialchars((string) $subscriber['email'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                                <td><?php echo htmlspecialchars((string) ($subscriber['segment_slug'] ?: 'general'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="status-badge <?php echo ($subscriber['status'] ?? '') === 'active' ? 'active' : 'inactive'; ?>"><?php echo htmlspecialchars((string) $subscriber['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo htmlspecialchars((string) ($subscriber['source'] ?? 'admin'), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="nl-side-stack">
            <div class="admin-card">
                <div class="nl-panel-header">
                    <div>
                        <h3>Letzte Kampagnen</h3>
                        <p>Entwürfe, geplante und versendete Newsletter.</p>
                    </div>
                </div>
                <?php if (empty($campaigns)): ?>
                    <div class="empty-state">
                        <p class="nl-empty-icon">Keine Daten</p>
                        <p><strong>Keine Kampagnen vorhanden</strong></p>
                        <p class="nl-empty-text">Lege die erste Kampagne auf Basis eines Templates an.</p>
                    </div>
                <?php else: ?>
                    <ul class="nl-compact-list">
                        <?php foreach ($campaigns as $campaign): ?>
                            <li>
                                <span>
                                    <strong><?php echo htmlspecialchars((string) $campaign['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <small><?php echo htmlspecialchars((string) ($campaign['template_name'] ?? 'Ohne Template'), ENT_QUOTES, 'UTF-8'); ?></small>
                                </span>
                                <span class="nl-soft-badge nl-soft-badge--<?php echo htmlspecialchars((string) ($campaign['status'] ?? 'draft'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($campaign['status'] ?? 'draft'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="nl-note-card">
                <span class="nl-note-card__eyebrow">Praxis-Tipp</span>
                <span class="nl-note-card__title">Erst Segment, dann Kampagne</span>
                <p class="nl-note-card__text">Wenn du Templates mit klaren Segmenten kombinierst, bleibt dein Versand relevanter – und dein Öffnungsrate-Ich dankt es dir später.</p>
            </div>
        </div>
    </div>
</div>
