<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<div class="pr-admin-shell">
    <div class="admin-page-header">
        <div>
            <h2>Promos-Dashboard</h2>
            <p>Überblick zu aktiven Promo-Flächen, Klickzielen, Platzierungen und Reichweite.</p>
        </div>
        <div class="header-actions">
            <a href="/promos" class="btn btn-secondary" target="_blank" rel="noopener noreferrer">Übersicht öffnen</a>
            <a href="/admin/plugins/promos-dashboard/promos-items" class="btn btn-primary">Promo anlegen</a>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
    <div class="alert alert-<?php echo ($notice['type'] ?? '') === 'success' ? 'success' : 'error'; ?>"><?php echo htmlspecialchars((string) ($notice['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="dashboard-grid">
        <div class="stat-card"><div class="stat-icon">ALL</div><div class="stat-number"><?php echo number_format((int) ($stats['promos'] ?? 0)); ?></div><div class="stat-label">Promos</div></div>
        <div class="stat-card"><div class="stat-icon">ACT</div><div class="stat-number"><?php echo number_format((int) ($stats['active_promos'] ?? 0)); ?></div><div class="stat-label">Aktiv</div></div>
        <div class="stat-card"><div class="stat-icon">TOP</div><div class="stat-number"><?php echo number_format((int) ($stats['featured_promos'] ?? 0)); ?></div><div class="stat-label">Featured</div></div>
        <div class="stat-card"><div class="stat-icon">PLC</div><div class="stat-number"><?php echo number_format((int) ($stats['placements'] ?? 0)); ?></div><div class="stat-label">Platzierungen</div></div>
        <div class="stat-card"><div class="stat-icon">IMP</div><div class="stat-number"><?php echo number_format((int) ($stats['impressions'] ?? 0)); ?></div><div class="stat-label">Impressions</div></div>
        <div class="stat-card"><div class="stat-icon">CLK</div><div class="stat-number"><?php echo number_format((int) ($stats['clicks'] ?? 0)); ?></div><div class="stat-label">Klicks</div></div>
    </div>

    <div class="pr-card-grid">
        <div class="pr-info-card"><span class="pr-info-card__eyebrow">Archiv</span><span class="pr-info-card__value"><?php echo htmlspecialchars((string) ($settings['archive_title'] ?? 'Promotions & Highlights'), ENT_QUOTES, 'UTF-8'); ?></span><span class="pr-info-card__text"><?php echo htmlspecialchars((string) ($settings['archive_description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></div>
        <div class="pr-info-card"><span class="pr-info-card__eyebrow">Button-Standard</span><span class="pr-info-card__value"><?php echo htmlspecialchars((string) ($settings['default_button_label'] ?? 'Mehr erfahren'), ENT_QUOTES, 'UTF-8'); ?></span><span class="pr-info-card__text">Voreinstellung für neue Kampagnen und CTA-Kacheln.</span></div>
    </div>

    <div class="pr-feature-grid">
        <a href="/admin/plugins/promos-dashboard/promos-items" class="pr-feature-card"><span class="pr-feature-card__icon">PR</span><span><span class="pr-feature-card__eyebrow">Kampagnen</span><span class="pr-feature-card__title">Promos pflegen</span><span class="pr-feature-card__text">Teaser, Ziel-URLs, Priorität und Laufzeiten verwalten.</span></span></a>
        <a href="/admin/plugins/promos-dashboard/promos-placements" class="pr-feature-card"><span class="pr-feature-card__icon">PL</span><span><span class="pr-feature-card__eyebrow">Layout</span><span class="pr-feature-card__title">Platzierungen definieren</span><span class="pr-feature-card__text">Hero, Sidebar, CTA-Bereiche und andere Einbauorte strukturieren.</span></span></a>
        <a href="/admin/plugins/promos-dashboard/promos-settings" class="pr-feature-card"><span class="pr-feature-card__icon">CFG</span><span><span class="pr-feature-card__eyebrow">Standards</span><span class="pr-feature-card__title">Anzeige konfigurieren</span><span class="pr-feature-card__text">Archive, Button-Verhalten und Grundparameter festlegen.</span></span></a>
    </div>

    <div class="pr-panel-grid">
        <div class="admin-card">
            <div class="pr-panel-header">
                <div><h3>Letzte Promos</h3><p>Aktuelle Teaser und CTA-Elemente mit Performance-Basisdaten.</p></div>
                <a href="/admin/plugins/promos-dashboard/promos-items" class="btn btn-secondary btn-sm">Verwalten</a>
            </div>
            <?php if (empty($promos)): ?>
                <div class="empty-state"><p class="pr-empty-icon">Keine Daten</p><p><strong>Noch keine Promos vorhanden</strong></p><p class="pr-empty-text">Lege deine erste Promo mit Ziel-URL und Platzierung an.</p></div>
            <?php else: ?>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead><tr><th>Titel</th><th>Platzierung</th><th>Status</th><th>Leistung</th></tr></thead>
                        <tbody>
                        <?php foreach ($promos as $item): ?>
                            <tr>
                                <td><a href="/admin/plugins/promos-dashboard/promos-items?edit=<?php echo (int) $item['id']; ?>" class="pr-admin-link"><?php echo htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                                <td>
                                    <?php echo htmlspecialchars((string) ($item['placement_name'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (!empty($item['placement_theme_hook'])): ?>
                                        <div class="pr-table-meta"><?php echo htmlspecialchars(CMS_Promos_Repository::instance()->get_theme_hook_label((string) $item['placement_theme_hook']), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="pr-soft-badge pr-soft-badge--<?php echo htmlspecialchars((string) ($item['status'] ?? 'draft'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($item['status'] ?? 'draft'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo number_format((int) ($item['impression_count'] ?? 0)); ?> / <?php echo number_format((int) ($item['click_count'] ?? 0)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="pr-side-stack">
            <div class="admin-card">
                <div class="pr-panel-header"><div><h3>Platzierungen</h3><p>Alle registrierten Promo-Flächen auf einen Blick.</p></div></div>
                <?php if (empty($placements)): ?>
                    <div class="empty-state"><p class="pr-empty-icon">Keine Daten</p><p><strong>Keine Platzierungen vorhanden</strong></p></div>
                <?php else: ?>
                    <ul class="pr-compact-list">
                        <?php foreach ($placements as $placement): ?>
                            <li>
                                <span>
                                    <?php echo htmlspecialchars((string) $placement['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    <span class="pr-table-meta"><?php echo htmlspecialchars(CMS_Promos_Repository::instance()->get_theme_hook_label((string) ($placement['theme_hook'] ?? 'manual')), ENT_QUOTES, 'UTF-8'); ?></span>
                                </span>
                                <span class="status-badge <?php echo ($placement['status'] ?? 'active') === 'active' ? 'active' : 'inactive'; ?>"><?php echo (int) ($placement['promo_count'] ?? 0); ?> Promos</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="pr-note-card">
                <span class="pr-note-card__eyebrow">Auto-Einbindung</span>
                <span class="pr-note-card__title">Platzierungen direkt an Theme-Hooks koppeln</span>
                <p class="pr-note-card__text">Weise Flächen wie Hero, Home-Content oder Footer-Banner einem Hook zu. Aktive Promos erscheinen dann automatisch an dieser Stelle — ganz ohne Template-Gefrickel.</p>
            </div>
        </div>
    </div>
</div>
