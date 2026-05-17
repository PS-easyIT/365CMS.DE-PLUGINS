<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<?php
$queueStats = CMS_Feed_Database::instance()->get_queue_stats();
$healthSummary = CMS_Feed_Database::instance()->get_channel_health_summary();
$attentionChannels = CMS_Feed_Database::instance()->get_attention_channels(6);
?>

<div class="feed-admin-shell-wrap">

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>📡 RSS-Feed-Aggregator</h2>
        <p>Feeds sammeln, Bereiche verwalten und als Digest versenden</p>
    </div>
    <div class="header-actions">
        <?php if ($tab === 'dashboard'): ?>
            <form method="POST" class="feed-inline-form">
                <input type="hidden" name="action" value="fetch_now">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <button type="submit" class="btn btn-primary">🔄 Alle Feeds abrufen</button>
            </form>
        <?php elseif ($tab === 'channels'): ?>
            <button type="button" class="btn btn-primary" id="openChannelModalBtn">➕ Neuer Kanal</button>
        <?php elseif ($tab === 'categories'): ?>
            <button type="button" class="btn btn-primary" id="openCategoryModalBtn">➕ Neuer Bereich</button>
        <?php elseif ($tab === 'digests'): ?>
            <button type="button" class="btn btn-primary" id="openDigestModalBtn">➕ Neuer Digest</button>
        <?php endif; ?>
    </div>
</div>

<!-- Alerts -->
<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo htmlspecialchars($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Tab-Navigation -->
<div class="feed-tabs">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="?tab=<?php echo htmlspecialchars($key); ?>"
       class="feed-tab<?php echo $tab === $key ? ' active' : ''; ?>">
        <?php echo htmlspecialchars($label); ?>
        <?php if ($key === 'channels' && ($stats['channels_errors'] ?? 0) > 0): ?>
            <span class="feed-tab-badge"><?php echo $stats['channels_errors']; ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Content -->
<div class="admin-card feed-admin-shell feed-admin-shell--tabbed">
<div class="feed-admin-view">

<?php
// ══════════════════════════════════════════════════════════════════════
// TAB: Dashboard
// ══════════════════════════════════════════════════════════════════════
if ($tab === 'dashboard'):
?>
    <div class="feed-panel-header">
        <div>
            <h3>📊 Übersicht</h3>
            <p>Feeds, Bereiche, Warteschlange und E-Mail-Digests auf einen Blick.</p>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="stat-icon">📁</div>
            <div class="stat-number"><?php echo number_format($stats['categories'] ?? 0); ?></div>
            <div class="stat-label">Bereiche</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📡</div>
            <div class="stat-number"><?php echo number_format($stats['channels'] ?? 0); ?></div>
            <div class="stat-label">Kanäle (<?php echo $stats['channels_active'] ?? 0; ?> aktiv)</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📰</div>
            <div class="stat-number"><?php echo number_format($stats['items'] ?? 0); ?></div>
            <div class="stat-label">Beiträge gesamt</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🆕</div>
            <div class="stat-number"><?php echo number_format($stats['items_today'] ?? 0); ?></div>
            <div class="stat-label">Heute neu</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📧</div>
            <div class="stat-number"><?php echo number_format($stats['digests'] ?? 0); ?></div>
            <div class="stat-label">Aktive Digests</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-number"><?php echo number_format($stats['member_subscriptions'] ?? 0); ?></div>
            <div class="stat-label">Member-Feed-Abos</div>
        </div>
        <?php if (($stats['channels_errors'] ?? 0) > 0): ?>
        <div class="stat-card feed-stat-card--danger">
            <div class="stat-icon">⚠️</div>
            <div class="stat-number feed-stat-number--danger"><?php echo $stats['channels_errors']; ?></div>
            <div class="stat-label">Kanäle mit Fehlern</div>
        </div>
        <?php endif; ?>
        <?php if ($queueStats['pending'] > 0 || $queueStats['processing'] > 0): ?>
        <div class="stat-card feed-stat-card--info">
            <div class="stat-icon">⏳</div>
            <div class="stat-number feed-stat-number--info"><?php echo $queueStats['pending'] + $queueStats['processing']; ?></div>
            <div class="stat-label">In Warteschlange</div>
        </div>
        <?php endif; ?>
    </div>

    <div class="feed-summary-grid">
        <div class="feed-info-card">
            <span class="feed-info-card__eyebrow">Abrufstatus</span>
            <span class="feed-info-card__value"><?php echo ($stats['channels_errors'] ?? 0) > 0 ? 'Achtung' : 'Stabil'; ?></span>
            <span class="feed-info-card__text"><?php echo ($stats['channels_errors'] ?? 0) > 0 ? number_format((int) $stats['channels_errors']) . ' Kanäle benötigen Aufmerksamkeit.' : 'Aktuell keine bekannten Feed-Fehler.'; ?></span>
        </div>
        <div class="feed-info-card">
            <span class="feed-info-card__eyebrow">Queue</span>
            <span class="feed-info-card__value"><?php echo number_format((int) ($queueStats['pending'] + $queueStats['processing'])); ?></span>
            <span class="feed-info-card__text">Feeds in Warteschlange oder aktuell in Verarbeitung.</span>
        </div>
        <div class="feed-info-card">
            <span class="feed-info-card__eyebrow">Überfällige Abrufe</span>
            <span class="feed-info-card__value"><?php echo number_format((int) ($healthSummary['overdue'] ?? 0)); ?></span>
            <span class="feed-info-card__text">Aktive Kanäle ohne frischen Abruf innerhalb des erwartbaren Zeitfensters.</span>
        </div>
        <div class="feed-info-card">
            <span class="feed-info-card__eyebrow">Nie gelaufen</span>
            <span class="feed-info-card__value"><?php echo number_format((int) ($healthSummary['never_fetched'] ?? 0)); ?></span>
            <span class="feed-info-card__text">Aktive Kanäle ohne ersten protokollierten Abruf.</span>
        </div>
        <div class="feed-info-card">
            <span class="feed-info-card__eyebrow">Heute neu</span>
            <span class="feed-info-card__value"><?php echo number_format((int) ($stats['items_today'] ?? 0)); ?></span>
            <span class="feed-info-card__text">Neu importierte Beiträge innerhalb der letzten 24 Stunden.</span>
        </div>
    </div>

    <div class="feed-action-grid">
        <a href="?tab=channels" class="feed-action-card">
            <span class="feed-action-card__icon">📡</span>
            <span>
                <span class="feed-action-card__eyebrow">Verwaltung</span>
                <span class="feed-action-card__title">Kanäle organisieren</span>
                <span class="feed-action-card__text">Quellen pflegen, Abrufintervalle setzen und Fehler schneller finden.</span>
            </span>
        </a>
        <a href="?tab=categories" class="feed-action-card">
            <span class="feed-action-card__icon">📁</span>
            <span>
                <span class="feed-action-card__eyebrow">Public-Struktur</span>
                <span class="feed-action-card__title">Bereiche ausbauen</span>
                <span class="feed-action-card__text">Öffentliche Feed-Hubs mit eigenem Slug, Layout und Darstellung anlegen.</span>
            </span>
        </a>
        <a href="?tab=digests" class="feed-action-card">
            <span class="feed-action-card__icon">📧</span>
            <span>
                <span class="feed-action-card__eyebrow">Kommunikation</span>
                <span class="feed-action-card__title">Digests steuern</span>
                <span class="feed-action-card__text">Teams, Verteiler und Postfächer automatisch mit Feed-Zusammenfassungen versorgen.</span>
            </span>
        </a>
    </div>

    <div class="feed-panel-grid">
    <div class="feed-admin-subcard">
        <h4 class="feed-section-title">⚡ Schnellzugriff</h4>
        <div class="feed-inline-actions">
            <a href="?tab=channels" class="btn btn-secondary btn-sm">📡 Kanäle verwalten</a>
            <a href="?tab=categories" class="btn btn-secondary btn-sm">📁 Bereiche verwalten</a>
            <a href="?tab=settings" class="btn btn-secondary btn-sm">⚙️ Einstellungen</a>
            <form method="POST" class="feed-inline-form">
                <input type="hidden" name="action" value="cleanup">
                <input type="hidden" name="cleanup_days" value="7">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <button type="submit" class="btn btn-secondary btn-sm">🧹 Alte Beiträge aufräumen</button>
            </form>
        </div>
    </div>

    <div class="feed-admin-subcard">
        <h4 class="feed-section-title">🩺 Technische Feed-Gesundheit</h4>
        <div class="info-grid">
            <div class="info-card">
                <h4>Aktive Kanäle</h4>
                <ul class="info-list">
                    <li><strong>Aktiv:</strong> <?php echo number_format((int) ($healthSummary['active'] ?? 0)); ?></li>
                    <li><strong>Mit Fehler:</strong> <?php echo number_format((int) ($healthSummary['with_errors'] ?? 0)); ?></li>
                    <li><strong>Überfällig:</strong> <?php echo number_format((int) ($healthSummary['overdue'] ?? 0)); ?></li>
                    <li><strong>Nie gelaufen:</strong> <?php echo number_format((int) ($healthSummary['never_fetched'] ?? 0)); ?></li>
                </ul>
            </div>
            <div class="info-card">
                <h4>Queue</h4>
                <ul class="info-list">
                    <li><strong>Pending:</strong> <?php echo number_format((int) ($queueStats['pending'] ?? 0)); ?></li>
                    <li><strong>Processing:</strong> <?php echo number_format((int) ($queueStats['processing'] ?? 0)); ?></li>
                    <li><strong>Failed:</strong> <?php echo number_format((int) ($queueStats['failed'] ?? 0)); ?></li>
                    <li><strong>Done:</strong> <?php echo number_format((int) ($queueStats['done'] ?? 0)); ?></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="feed-side-stack">
        <?php
        $errorChannels = array_filter($channels, fn($c) => !empty($c['last_error']));
        if (!empty($errorChannels)):
        ?>
        <div class="feed-admin-subcard feed-admin-subcard--danger">
            <h4 class="feed-section-title feed-section-title--danger">⚠️ Feed-Fehler</h4>
            <?php foreach ($errorChannels as $ch): ?>
            <div class="alert alert-error feed-alert-compact">
                <strong><?php echo htmlspecialchars($ch['name']); ?>:</strong>
                <?php echo htmlspecialchars($ch['last_error']); ?>
                <span class="feed-meta-inline">
                    <?php echo $ch['last_fetched_at'] ? date('d.m.Y H:i', strtotime($ch['last_fetched_at'])) : 'Nie'; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

            <?php if (!empty($attentionChannels)): ?>
            <div class="feed-admin-subcard">
                <h4 class="feed-section-title">🔎 Kanäle mit Aufmerksamkeit</h4>
                <?php foreach ($attentionChannels as $ch): ?>
                <?php
                    $hasError = !empty($ch['last_error']);
                    $neverFetched = empty($ch['last_fetched_at']);
                    $minutesSinceFetch = isset($ch['minutes_since_fetch']) ? (int) $ch['minutes_since_fetch'] : null;
                    $statusLabel = $hasError ? 'Fehler' : ($neverFetched ? 'Noch nie abgerufen' : 'Überfällig');
                    $metaText = $neverFetched
                        ? 'Noch kein Abruf protokolliert'
                        : ('Letzter Abruf vor ' . number_format(max(0, $minutesSinceFetch ?? 0)) . ' Min.');
                ?>
                <div class="alert <?php echo $hasError ? 'alert-error' : 'feed-admin-note feed-admin-note--soft'; ?> feed-alert-compact">
                    <strong><?php echo htmlspecialchars((string) ($ch['name'] ?? 'Kanal')); ?></strong>
                    <span class="feed-meta-inline"><?php echo htmlspecialchars((string) ($ch['category_name'] ?? 'Ohne Bereich')); ?> · <?php echo htmlspecialchars($statusLabel); ?></span>
                    <div class="feed-alert-detail">
                        <?php if ($hasError): ?>
                            <?php echo htmlspecialchars((string) $ch['last_error']); ?>
                        <?php else: ?>
                            <?php echo htmlspecialchars($metaText); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        <div class="feed-note-card">
            <span class="feed-note-card__eyebrow">Routing</span>
            <span class="feed-note-card__title">Öffentliche Feed-Struktur</span>
            <span class="feed-note-card__text">Der globale Feed bleibt auf <code>/feed</code>, die kuratierten Bereichsseiten laufen separat auf <code>/feed/{slug}</code> und das Archiv auf dem konfigurierten Archiv-Slug.</span>
        </div>
    </div>
    </div>

<?php
// ══════════════════════════════════════════════════════════════════════
// TAB: Kanäle
// ══════════════════════════════════════════════════════════════════════
elseif ($tab === 'channels'):
?>
    <h3>📡 RSS-Kanäle</h3>

    <?php if (empty($channels)): ?>
    <div class="empty-state">
        <p class="feed-empty-icon">📡</p>
        <p><strong>Noch keine Kanäle vorhanden</strong></p>
        <p class="feed-empty-text">Erstelle den ersten RSS-Kanal über den Button oben rechts.</p>
    </div>
    <?php else: ?>

    <!-- Bulk-Actions Bar (Kanäle) -->
    <div id="channelBulkBar" class="feed-bulk-bar feed-bulk-bar--info" hidden>
        <span class="feed-bulk-bar__count">
            <span id="channelBulkCount">0</span> ausgewählt
        </span>
        <form method="POST" id="channelBulkForm" class="feed-inline-actions">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <input type="hidden" name="action" id="channelBulkAction" value="">
            <div id="channelBulkIds"></div>
            <button type="button" class="btn btn-sm btn-primary" data-feed-bulk-channel-action="bulk_fetch_channels" title="Ausgewählte abrufen (max. 5 sofort, Rest per Cron)">🔄 Abrufen</button>
            <button type="button" class="btn btn-sm btn-secondary" data-feed-bulk-channel-action="bulk_activate_channels">✅ Aktivieren</button>
            <button type="button" class="btn btn-sm btn-secondary" data-feed-bulk-channel-action="bulk_deactivate_channels">⏸️ Deaktivieren</button>
            <button type="button" class="btn btn-sm btn-danger" data-feed-bulk-channel-action="bulk_delete_channels">🗑️ Löschen</button>
        </form>
    </div>

    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th class="feed-table-check"><input type="checkbox" id="channelSelectAll" title="Alle markieren"></th>
                    <th>Name</th>
                    <th>Bereich</th>
                    <th>Beiträge</th>
                    <th>Intervall</th>
                    <th>Letzter Abruf</th>
                    <th>Status</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($channels as $ch): ?>
                <tr>
                    <td><input type="checkbox" class="channel-checkbox" value="<?php echo (int)$ch['id']; ?>"></td>
                    <td>
                                <a href="#" data-feed-edit-channel="<?php echo (int)$ch['id']; ?>"
                           class="feed-table-link feed-table-link--plain">
                            <?php echo htmlspecialchars($ch['name']); ?>
                        </a>
                        <div class="feed-table-meta">
                            <?php echo htmlspecialchars($ch['feed_url']); ?>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($ch['category_name'] ?? '–'); ?></td>
                    <td><?php echo number_format((int)$ch['item_count']); ?></td>
                    <td><?php echo (int)$ch['fetch_interval']; ?> min</td>
                    <td>
                        <?php if ($ch['last_fetched_at']): ?>
                            <?php echo date('d.m.Y H:i', strtotime($ch['last_fetched_at'])); ?>
                        <?php else: ?>
                            <span class="feed-text-muted">Nie</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($ch['last_error'])): ?>
                            <span class="status-badge danger" title="<?php echo htmlspecialchars($ch['last_error']); ?>">❌ Fehler</span>
                        <?php elseif ((int)$ch['is_active']): ?>
                            <span class="status-badge active">✅ Aktiv</span>
                        <?php else: ?>
                            <span class="status-badge inactive">⏸️ Inaktiv</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="feed-table-actions">
                            <form method="POST" class="feed-inline-form">
                                <input type="hidden" name="action" value="fetch_now">
                                <input type="hidden" name="channel_id" value="<?php echo (int)$ch['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <button type="submit" class="btn btn-sm btn-secondary" title="Jetzt abrufen">🔄</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-secondary" data-feed-edit-channel="<?php echo (int)$ch['id']; ?>" title="Bearbeiten">✏️</button>
                            <button type="button" class="btn btn-sm btn-danger" data-feed-delete-id="<?php echo (int)$ch['id']; ?>" data-feed-delete-name="<?php echo htmlspecialchars($ch['name'], ENT_QUOTES); ?>" data-feed-delete-action="delete_channel" title="Löschen">🗑️</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

<?php
// ══════════════════════════════════════════════════════════════════════
// TAB: Bereiche
// ══════════════════════════════════════════════════════════════════════
elseif ($tab === 'categories'):
?>
    <h3>📁 Bereiche / Kategorien</h3>
    <div class="alert feed-admin-note feed-admin-note--info">
        ℹ️ Jeder öffentliche Bereich bekommt seine eigene Seite unter <code>/feed/{slug}</code>. Das Plugin-Archiv bleibt separat unter <code>/<?php echo htmlspecialchars(($settings['archive_slug'] ?? 'feeds') === 'feed' ? 'feeds' : ($settings['archive_slug'] ?? 'feeds')); ?></code> erreichbar.
    </div>

    <?php if (empty($categories)): ?>
    <div class="empty-state">
        <p class="feed-empty-icon">📁</p>
        <p><strong>Noch keine Bereiche vorhanden</strong></p>
        <p class="feed-empty-text">Erstelle den ersten Bereich, z.B. "Security" oder "Tech News".</p>
    </div>
    <?php else: ?>

    <!-- Bulk-Actions Bar (Bereiche) -->
    <div id="categoryBulkBar" class="feed-bulk-bar feed-bulk-bar--warn" hidden>
        <span class="feed-bulk-bar__count">
            <span id="categoryBulkCount">0</span> ausgewählt
        </span>
        <form method="POST" id="categoryBulkForm" class="feed-inline-actions">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <input type="hidden" name="action" id="categoryBulkAction" value="">
            <div id="categoryBulkIds"></div>
            <button type="button" class="btn btn-sm btn-danger" data-feed-bulk-category-action="bulk_delete_categories">🗑️ Ausgewählte löschen</button>
        </form>
    </div>

    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th class="feed-table-check"><input type="checkbox" id="categorySelectAll" title="Alle markieren"></th>
                    <th>Icon</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Layout</th>
                    <th>Kanäle</th>
                    <th>Öffentlich</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($categories as $cat):
                $catChannels = array_filter($channels, fn($c) => (int)$c['category_id'] === (int)$cat['id']);
                $categorySlug = trim((string) ($cat['slug'] ?? ''), '/');
                $publicViewUrl = SITE_URL . '/feed/' . rawurlencode($categorySlug);
            ?>
                <tr>
                    <td><input type="checkbox" class="category-checkbox" value="<?php echo (int)$cat['id']; ?>"></td>
                    <td class="feed-empty-icon"><?php echo htmlspecialchars($cat['icon']); ?></td>
                    <td>
                                <a href="#" data-feed-edit-category="<?php echo (int)$cat['id']; ?>"
                           class="feed-table-link feed-table-link--plain">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                        <?php if (!empty($cat['description'])): ?>
                        <div class="feed-table-meta">
                            <?php echo htmlspecialchars($cat['description']); ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td><code><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                    <td><?php echo htmlspecialchars(ucfirst($cat['layout'])); ?></td>
                    <td><?php echo count($catChannels); ?></td>
                    <td>
                        <?php if ((int)$cat['is_public']): ?>
                            <span class="status-badge active">✅ Ja</span>
                        <?php else: ?>
                            <span class="status-badge inactive">⏸️ Nein</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="feed-table-actions">
                            <?php if ((int)$cat['is_public'] && $categorySlug !== ''): ?>
                            <a href="<?php echo htmlspecialchars($publicViewUrl, ENT_QUOTES); ?>" class="btn btn-sm btn-secondary" target="_blank" rel="noopener noreferrer" title="Public View öffnen">🌐</a>
                            <?php else: ?>
                            <button type="button" class="btn btn-sm btn-secondary" disabled title="Nur für öffentliche Bereiche verfügbar">🌐</button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-secondary" data-feed-edit-category="<?php echo (int)$cat['id']; ?>" title="Bearbeiten">✏️</button>
                            <button type="button" class="btn btn-sm btn-danger" data-feed-delete-id="<?php echo (int)$cat['id']; ?>" data-feed-delete-name="<?php echo htmlspecialchars($cat['name'], ENT_QUOTES); ?>" data-feed-delete-action="delete_category" title="Löschen">🗑️</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

<?php
// ══════════════════════════════════════════════════════════════════════
// TAB: Katalog
// ══════════════════════════════════════════════════════════════════════
elseif ($tab === 'catalog'):
    $catalogInstance = CMS_Feed_Catalog::instance();
    $catalogOverview = $catalogInstance->get_categories_overview();
?>
    <h3>📚 Feed-Katalog – Kuratierte Vorlagen</h3>
    <p class="feed-admin-lead">
        Importiere professionell kuratierte Feed-Sammlungen komplett oder wähle gezielt nur die Quellen aus, die du wirklich brauchst.
        Bereits vorhandene Feed-URLs werden automatisch übersprungen.
    </p>

    <div class="feed-catalog-grid">
    <?php foreach ($catalogOverview as $catKey => $catInfo): ?>
        <?php $catalogFeeds = $catalogInstance->get_feeds($catKey); ?>
        <div class="feed-catalog-card">
            <div class="feed-catalog-card__header">
                <span class="feed-catalog-card__icon"><?php echo $catInfo['icon']; ?></span>
                <div>
                    <h4 class="feed-catalog-card__title"><?php echo htmlspecialchars($catInfo['name']); ?></h4>
                    <span class="feed-catalog-card__meta"><?php echo $catInfo['count']; ?> Feeds verfügbar</span>
                </div>
            </div>
            <p class="feed-catalog-card__description"><?php echo htmlspecialchars($catInfo['description']); ?></p>
            <div class="feed-inline-actions">
                <form method="POST" class="feed-inline-form" data-feed-confirm-message="<?php echo htmlspecialchars($catInfo['count'] . ' Feeds importieren und neuen Bereich „' . $catInfo['name'] . '“ anlegen?', ENT_QUOTES); ?>" data-feed-confirm-danger="0">
                    <input type="hidden" name="action" value="import_catalog">
                    <input type="hidden" name="catalog_key" value="<?php echo htmlspecialchars($catKey); ?>">
                    <input type="hidden" name="target_category_id" value="0">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <button type="submit" class="btn btn-primary btn-sm">
                        📥 Komplett importieren
                    </button>
                </form>
                <?php if (!empty($categories)): ?>
                <div class="feed-inline-stack">
                    <form method="POST" class="feed-inline-actions" data-feed-confirm-message="<?php echo htmlspecialchars('Feeds aus „' . $catInfo['name'] . '“ in bestehenden Bereich importieren?', ENT_QUOTES); ?>" data-feed-confirm-danger="0">
                        <input type="hidden" name="action" value="import_catalog">
                        <input type="hidden" name="catalog_key" value="<?php echo htmlspecialchars($catKey); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <select name="target_category_id" class="form-control feed-input-compact">
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['icon'] . ' ' . $cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm">
                            ➕ In Bereich
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <details class="feed-catalog-details">
                <summary>🧩 Auswahl importieren</summary>
                <form method="POST" class="feed-catalog-selection-form" data-feed-confirm-message="<?php echo htmlspecialchars('Ausgewählte Feeds aus ' . $catInfo['name'] . ' importieren?', ENT_QUOTES); ?>" data-feed-confirm-danger="0">
                    <input type="hidden" name="action" value="import_catalog">
                    <input type="hidden" name="catalog_key" value="<?php echo htmlspecialchars($catKey); ?>">
                    <input type="hidden" name="catalog_import_mode" value="selected">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

                    <div class="feed-catalog-toolbar">
                        <span><?php echo count($catalogFeeds); ?> Quellen verfügbar</span>
                        <div class="feed-catalog-toolbar__actions">
                            <button type="button" class="btn btn-secondary btn-sm" data-feed-toggle-catalog-selection="<?php echo htmlspecialchars($catKey, ENT_QUOTES); ?>" data-feed-toggle-catalog-state="1">Alle</button>
                            <button type="button" class="btn btn-secondary btn-sm" data-feed-toggle-catalog-selection="<?php echo htmlspecialchars($catKey, ENT_QUOTES); ?>" data-feed-toggle-catalog-state="0">Keine</button>
                        </div>
                    </div>

                    <?php if (!empty($categories)): ?>
                    <div class="form-group feed-form-group-compact">
                        <label class="form-label">Zielbereich</label>
                        <select name="target_category_id" class="form-control">
                            <option value="0">Neuen Bereich „<?php echo htmlspecialchars($catInfo['name']); ?>“ anlegen</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int) $cat['id']; ?>"><?php echo htmlspecialchars($cat['icon'] . ' ' . $cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="target_category_id" value="0">
                    <?php endif; ?>

                    <div class="feed-catalog-list" id="catalog-list-<?php echo htmlspecialchars($catKey); ?>">
                        <?php foreach ($catalogFeeds as $feedIndex => $feed): ?>
                        <label class="feed-catalog-item">
                            <input type="checkbox" name="catalog_feeds[]" value="<?php echo (int) $feedIndex; ?>">
                            <span>
                                <strong><?php echo htmlspecialchars((string) ($feed['name'] ?? 'Feed')); ?></strong>
                                <small><?php echo htmlspecialchars((string) ($feed['description'] ?? ''), ENT_QUOTES); ?></small>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="feed-catalog-selection-form__actions">
                        <button type="submit" class="btn btn-primary btn-sm">
                            ✅ Auswahl importieren
                        </button>
                    </div>
                </form>
            </details>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="feed-admin-note feed-admin-note--soft">
        <p>
            ℹ️ <strong>Hinweis:</strong> Der Import erstellt nur Kanäle. Feed-Beiträge werden beim nächsten automatischen Abruf
            oder über „🔄 Alle Feeds abrufen" auf dem Dashboard geladen. Bereits vorhandene Feed-URLs werden nicht doppelt angelegt.
        </p>
    </div>

<?php
// ══════════════════════════════════════════════════════════════════════
// TAB: Beiträge
// ══════════════════════════════════════════════════════════════════════
elseif ($tab === 'items'):
    $itemPage   = max(1, (int)($_GET['page'] ?? 1));
    $perPage    = 25;
    $offset     = ($itemPage - 1) * $perPage;
    $feedSearchQuery = sanitize_text_field((string) ($_GET['q'] ?? ''));
    $escapedFeedSearchQuery = htmlspecialchars($feedSearchQuery, ENT_QUOTES, 'UTF-8');
    $itemFilter = ['include_hidden' => true];
    if (!empty($_GET['cat'])) $itemFilter['category_id'] = (int)$_GET['cat'];
    if (!empty($_GET['ch']))  $itemFilter['channel_id']  = (int)$_GET['ch'];
    if ($feedSearchQuery !== '') $itemFilter['search'] = $feedSearchQuery;
    $totalItems = $db->count_items($itemFilter);
    $safeTotalItems = max(0, (int) $totalItems);
    $totalPages = (int)ceil($totalItems / $perPage);
    $feedItems  = $db->get_items($itemFilter, $offset, $perPage);
?>
    <h3>📰 Beiträge (<?php echo number_format($safeTotalItems); ?>)</h3>

    <!-- Filter -->
    <form method="GET" class="feed-filter-bar">
        <input type="hidden" name="tab" value="items">
        <select name="cat" class="form-control feed-input-narrow">
            <option value="">Alle Bereiche</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?php echo (int)$cat['id']; ?>" <?php echo ((int)($_GET['cat'] ?? 0)) === (int)$cat['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="q" class="form-control feed-input-medium"
             value="<?php echo $escapedFeedSearchQuery; ?>"
               placeholder="Suche...">
        <button type="submit" class="btn btn-secondary btn-sm">🔍 Filtern</button>
    </form>

    <?php if (empty($feedItems)): ?>
    <div class="empty-state">
        <p class="feed-empty-icon">📰</p>
        <p><strong>Keine Beiträge gefunden</strong></p>
        <p class="feed-empty-text">Feeds abrufen oder Filter anpassen.</p>
    </div>
    <?php else: ?>
    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Quelle</th>
                    <th>Bereich</th>
                    <th>Datum</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($feedItems as $item): ?>
                <tr>
                    <td class="feed-item-title-cell">
                                <a href="<?php echo htmlspecialchars($item['link']); ?>" target="_blank" rel="noopener noreferrer"
                           class="feed-table-link">
                            <?php echo htmlspecialchars(mb_substr($item['title'], 0, 80)); ?>
                        </a>
                        <?php if ((int)$item['is_featured']): ?>
                            <span class="feed-item-badge feed-item-badge--featured">⭐ Featured</span>
                        <?php endif; ?>
                        <?php if ((int)($item['is_hidden'] ?? 0)): ?>
                            <span class="feed-item-badge feed-item-badge--hidden">👁️‍🗨️ Ausgeblendet</span>
                        <?php endif; ?>
                    </td>
                    <td class="feed-item-meta-cell"><?php echo htmlspecialchars($item['channel_name'] ?? ''); ?></td>
                    <td class="feed-item-meta-cell"><?php echo htmlspecialchars($item['category_name'] ?? ''); ?></td>
                    <td class="feed-item-meta-cell"><?php echo date('d.m.Y H:i', strtotime($item['pub_date'])); ?></td>
                    <td>
                        <div class="feed-table-actions feed-table-actions--tight">
                            <form method="POST" class="feed-inline-form">
                                <input type="hidden" name="action" value="toggle_featured">
                                <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <button type="submit" class="btn btn-sm btn-secondary" title="<?php echo (int)$item['is_featured'] ? 'Featured entfernen' : 'Als Featured markieren'; ?>">⭐</button>
                            </form>
                            <form method="POST" class="feed-inline-form">
                                <input type="hidden" name="action" value="toggle_hidden">
                                <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <button type="submit" class="btn btn-sm btn-secondary" title="Ausblenden">👁️</button>
                            </form>
                            <form method="POST" class="feed-inline-form" data-feed-confirm-message="Diesen Beitrag wirklich löschen?">
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" title="Löschen">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="feed-pagination">
        <?php if ($itemPage > 1): ?>
            <a href="?tab=items&page=<?php echo $itemPage - 1; ?>&cat=<?php echo (int)($_GET['cat'] ?? 0); ?>&q=<?php echo urlencode($_GET['q'] ?? ''); ?>" class="btn btn-secondary btn-sm">← Zurück</a>
        <?php endif; ?>
        <span class="feed-pagination__meta">
            Seite <?php echo $itemPage; ?> von <?php echo $totalPages; ?>
        </span>
        <?php if ($itemPage < $totalPages): ?>
            <a href="?tab=items&page=<?php echo $itemPage + 1; ?>&cat=<?php echo (int)($_GET['cat'] ?? 0); ?>&q=<?php echo urlencode($_GET['q'] ?? ''); ?>" class="btn btn-secondary btn-sm">Weiter →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

<?php
// ══════════════════════════════════════════════════════════════════════
// TAB: E-Mail-Digests
// ══════════════════════════════════════════════════════════════════════
elseif ($tab === 'digests'):
    $mailer = CMS_Feed_Email_Digest::instance();
?>
    <h3>📧 E-Mail-Digests</h3>
    <div class="alert feed-admin-note feed-admin-note--info">
        ℹ️ Digests werden automatisch gemäß der gewählten Frequenz versendet. Du kannst auch manuell Test-Mails senden.
    </div>
    <div class="alert feed-admin-note feed-admin-note--accent">
        👤 Mitglieder verwalten ihre persönlichen Feed-Abos direkt im <strong>Memberbereich unter „Feed-Abos“</strong>.
        Hier im Admin bleiben die globalen/manuellen Digest-Empfänger für Teams, Postfächer oder Verteilerlisten.
    </div>

    <?php if (empty($digests)): ?>
    <div class="empty-state">
        <p class="feed-empty-icon">📧</p>
        <p><strong>Noch keine Digests konfiguriert</strong></p>
        <p class="feed-empty-text">Erstelle einen Digest um Feed-Beiträge per E-Mail zu versenden.</p>
    </div>
    <?php else: ?>
    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>E-Mail</th>
                    <th>Bereiche</th>
                    <th>Frequenz</th>
                    <th>Letzter Versand</th>
                    <th>Status</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($digests as $dg):
                $dgCats = json_decode($dg['category_ids'] ?? '[]', true) ?: [];
                $dgCatNames = [];
                foreach ($dgCats as $catId) {
                    foreach ($categories as $cat) {
                        if ((int)$cat['id'] === (int)$catId) {
                            $dgCatNames[] = $cat['name'];
                        }
                    }
                }
            ?>
                <tr>
                    <td class="feed-digest-name"><?php echo htmlspecialchars($dg['name']); ?></td>
                    <td><?php echo htmlspecialchars($dg['email']); ?></td>
                    <td class="feed-item-meta-cell">
                        <?php echo htmlspecialchars(implode(', ', $dgCatNames) ?: '–'); ?>
                    </td>
                    <td><?php echo $mailer->get_frequency_label((int)$dg['frequency']); ?></td>
                    <td>
                        <?php if ($dg['last_sent_at']): ?>
                            <?php echo date('d.m.Y H:i', strtotime($dg['last_sent_at'])); ?>
                        <?php else: ?>
                            <span class="feed-text-muted">Noch nie</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int)$dg['is_active']): ?>
                            <span class="status-badge active">✅ Aktiv</span>
                        <?php else: ?>
                            <span class="status-badge inactive">⏸️ Inaktiv</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="feed-table-actions feed-table-actions--tight">
                            <form method="POST" class="feed-inline-form">
                                <input type="hidden" name="action" value="test_digest">
                                <input type="hidden" name="digest_id" value="<?php echo (int)$dg['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <button type="submit" class="btn btn-sm btn-secondary" title="Test senden">📤</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-secondary" data-feed-edit-digest="<?php echo (int)$dg['id']; ?>" title="Bearbeiten">✏️</button>
                            <button type="button" class="btn btn-sm btn-danger" data-feed-delete-id="<?php echo (int)$dg['id']; ?>" data-feed-delete-name="<?php echo htmlspecialchars($dg['name'], ENT_QUOTES); ?>" data-feed-delete-action="delete_digest" title="Löschen">🗑️</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Digest E-Mail-Einstellungen -->
    <hr class="feed-divider">
    <h4 class="feed-section-title--compact">📧 Digest-Grundeinstellungen</h4>

    <form method="POST" class="admin-form feed-settings-form feed-settings-form--narrow">
        <input type="hidden" name="action" value="save_digest_settings">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

        <div class="form-group">
            <label class="form-label">Absendername</label>
            <input type="text" name="digest_from_name" class="form-control"
                   value="<?php echo htmlspecialchars($settings['digest_from_name'] ?? '365 CMS Feed Digest'); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Absender-E-Mail</label>
            <input type="email" name="digest_from_email" class="form-control"
                   value="<?php echo htmlspecialchars($settings['digest_from_email'] ?? ''); ?>"
                   placeholder="noreply@example.com">
        </div>
        <div class="form-group">
            <label class="form-label">Betreff-Vorlage</label>
            <input type="text" name="digest_subject" class="form-control"
                   value="<?php echo htmlspecialchars($settings['digest_subject'] ?? 'Dein Feed-Digest – {date}'); ?>">
            <small class="form-text">Platzhalter: <code>{date}</code> für das aktuelle Datum</small>
        </div>
        <div class="form-group">
            <label class="form-label">Max. Beiträge pro Digest</label>
            <input type="number" name="digest_max_items" class="form-control"
                   value="<?php echo (int)($settings['digest_max_items'] ?? 20); ?>"
                   min="5" max="100" class="feed-input-xs">
        </div>

        <button type="submit" class="btn btn-primary">💾 Digest-Einstellungen speichern</button>
    </form>

<?php
// ══════════════════════════════════════════════════════════════════════
// TAB: Einstellungen
// ══════════════════════════════════════════════════════════════════════
elseif ($tab === 'settings'):
    $settingsTab = $settingsSubTab ?? ($_GET['stab'] ?? 'general');
?>
    <div class="feed-settings-overview">
        <div class="feed-info-card">
            <span class="feed-info-card__eyebrow">Archiv-Slug</span>
            <span class="feed-info-card__value">/<?php echo htmlspecialchars(($settings['archive_slug'] ?? 'feeds') === 'feed' ? 'feeds' : ($settings['archive_slug'] ?? 'feeds')); ?></span>
            <span class="feed-info-card__text">Öffentliches Archiv für alle kuratierten Feed-Inhalte.</span>
        </div>
        <div class="feed-info-card">
            <span class="feed-info-card__eyebrow">Einträge pro Seite</span>
            <span class="feed-info-card__value"><?php echo (int) ($settings['per_page'] ?? 20); ?></span>
            <span class="feed-info-card__text">Standardgröße des öffentlichen Feed-Grids.</span>
        </div>
        <div class="feed-info-card">
            <span class="feed-info-card__eyebrow">Digest-Limit</span>
            <span class="feed-info-card__value"><?php echo (int) ($settings['digest_max_items'] ?? 20); ?></span>
            <span class="feed-info-card__text">Maximale Anzahl Beiträge pro Digest-Mail.</span>
        </div>
    </div>

    <!-- Sub-Tabs -->
    <div class="feed-settings-tabs">
        <button class="tab-btn <?php echo $settingsTab === 'general' ? 'active' : ''; ?>" data-feed-tab-target="stab-general" type="button">⚙️ Allgemein</button>
        <button class="tab-btn <?php echo $settingsTab === 'design' ? 'active' : ''; ?>" data-feed-tab-target="stab-design" type="button">🎨 Design</button>
        <button class="tab-btn <?php echo $settingsTab === 'system' ? 'active' : ''; ?>" data-feed-tab-target="stab-system" type="button">🖥️ System</button>
    </div>

    <!-- Allgemein -->
    <div id="stab-general" class="tab-content <?php echo $settingsTab === 'general' ? 'active' : ''; ?>">
        <div class="feed-settings-layout">
        <form method="POST" class="admin-form feed-settings-form admin-card feed-settings-panel">
            <input type="hidden" name="action" value="save_settings">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

            <div class="feed-panel-header">
                <div>
                    <h3>⚙️ Allgemeine Einstellungen</h3>
                    <p>Archiv-Titel, Slug, Seitengröße und sichtbare Metadaten zentral steuern.</p>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Seitentitel <span class="feed-required">*</span></label>
                <input type="text" name="archive_title" class="form-control"
                       value="<?php echo htmlspecialchars($settings['archive_title'] ?? 'Feed-Übersicht'); ?>" required>
                <small class="form-text">Überschrift der öffentlichen Feed-Seite</small>
            </div>
            <div class="form-group">
                <label class="form-label">Beschreibung</label>
                <textarea name="archive_description" class="form-control" rows="2"><?php echo htmlspecialchars($settings['archive_description'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                  <label class="form-label">URL-Slug <span class="feed-required">*</span></label>
                <input type="text" name="archive_slug" class="form-control"
                       value="<?php echo htmlspecialchars($settings['archive_slug'] ?? 'feeds'); ?>"
                      pattern="[a-z0-9\-]+" required>
                  <small class="form-text">Archiv-URL: <code>/<?php echo htmlspecialchars(($settings['archive_slug'] ?? 'feeds') === 'feed' ? 'feeds' : ($settings['archive_slug'] ?? 'feeds')); ?></code> · Öffentliche Bereichsseiten bleiben fest unter <code>/feed/{bereich-slug}</code>.</small>
            </div>
            <div class="form-group">
                <label class="form-label">Einträge pro Seite</label>
                <input type="number" name="per_page" class="form-control"
                       value="<?php echo (int)($settings['per_page'] ?? 20); ?>"
                       min="4" max="100" class="feed-input-xs">
            </div>
            <div class="form-group">
                <label class="form-label">Zusammenfassung (Zeichen)</label>
                <input type="number" name="excerpt_length" class="form-control"
                       value="<?php echo (int)($settings['excerpt_length'] ?? 160); ?>"
                       min="50" max="500" class="feed-input-xs">
            </div>

            <div class="form-group">
                <label class="form-label">Angezeigte Felder</label>
                <?php
                $toggles = [
                    'show_source'     => 'Quellname anzeigen',
                    'show_date'       => 'Datum anzeigen',
                    'show_image'      => 'Beitragsbild anzeigen',
                    'show_excerpt'    => 'Zusammenfassung anzeigen',
                    'open_in_new_tab' => 'Links in neuem Tab öffnen',
                ];
                foreach ($toggles as $key => $label):
                ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="<?php echo $key; ?>" value="1"
                           <?php echo !empty($settings[$key]) ? 'checked' : ''; ?>>
                    <?php echo $label; ?>
                </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
        </form>
        <div class="feed-side-stack">
            <div class="feed-note-card">
                <span class="feed-note-card__eyebrow">Hinweis</span>
                <span class="feed-note-card__title">Archiv und Bereichsseiten getrennt halten</span>
                <span class="feed-note-card__text">Das Plugin-Archiv und die öffentlichen Bereichsseiten sind bewusst getrennt, damit globale Feed-Routen nicht mit kuratierten Hubs kollidieren.</span>
            </div>
        </div>
        </div>
    </div>

    <!-- Design -->
    <div id="stab-design" class="tab-content <?php echo $settingsTab === 'design' ? 'active' : ''; ?>">
        <div class="feed-settings-layout">
        <form method="POST" class="admin-form feed-settings-form feed-settings-form--wide admin-card feed-settings-panel">
            <input type="hidden" name="action" value="save_design">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

            <div class="feed-panel-header">
                <div>
                    <h3>🎨 Design-Einstellungen</h3>
                    <p>Farben, Radius und Grid-Dichte passend zum 365CMS-Frontend konfigurieren.</p>
                </div>
            </div>

            <div class="feed-settings-color-grid">
                <?php
                $colorFields = [
                    'color_primary'     => ['Primärfarbe', '#0891b2', 'Buttons, Links, Akzente'],
                    'color_accent'      => ['Akzentfarbe (hell)', '#e0f2fe', 'Hover-Hintergründe, Badges'],
                    'color_hdr_from'    => ['Header-Gradient Start', '#0c4a6e', 'Linke/obere Farbe'],
                    'color_hdr_to'      => ['Header-Gradient Ende', '#0891b2', 'Rechte/untere Farbe'],
                    'color_hdr_title'   => ['Header-Textfarbe', '#ffffff', 'Titel im Header'],
                    'color_card_bg'     => ['Card-Hintergrund', '#ffffff', 'Hintergrundfarbe der Karten'],
                    'color_card_border' => ['Card-Rand', '#e2e8f0', 'Rahmenfarbe der Karten'],
                ];
                foreach ($colorFields as $key => [$label, $default, $hint]):
                ?>
                <div class="form-group">
                    <label class="form-label"><?php echo $label; ?></label>
                          <div class="feed-color-row">
                           <input type="color" name="<?php echo $key; ?>"
                               class="form-control feed-color-input"
                               value="<?php echo htmlspecialchars($settings[$key] ?? $default); ?>">
                        <input type="text" name="<?php echo $key; ?>_text"
                               class="form-control feed-input-xs feed-input-mono"
                               value="<?php echo htmlspecialchars($settings[$key] ?? $default); ?>"
                               pattern="^#[0-9A-Fa-f]{6}$" maxlength="7"
                               >
                    </div>
                    <small class="form-text"><?php echo $hint; ?></small>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="form-group feed-input-wide">
                <label class="form-label">Border-Radius (Karten)</label>
                <div class="feed-range-row">
                    <input type="range" name="border_radius" min="0" max="24" step="2"
                           value="<?php echo (int)($settings['border_radius'] ?? 10); ?>"
                           class="feed-range-row__input">
                    <span id="radiusPreview" class="feed-range-row__value">
                        <?php echo (int)($settings['border_radius'] ?? 10); ?>px
                    </span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Spalten (Archiv-Grid)</label>
                <select name="grid_columns" class="form-control feed-input-narrow">
                    <?php foreach (['auto' => 'Automatisch (empfohlen)', '2' => '2 Spalten', '3' => '3 Spalten', '4' => '4 Spalten'] as $val => $lbl): ?>
                    <option value="<?php echo $val; ?>" <?php echo ($settings['grid_columns'] ?? 'auto') === $val ? 'selected' : ''; ?>>
                        <?php echo $lbl; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">💾 Design speichern</button>
        </form>
        <div class="feed-side-stack">
            <div class="feed-note-card">
                <span class="feed-note-card__eyebrow">Design-Tipp</span>
                <span class="feed-note-card__title">Weniger Farben, mehr Lesbarkeit</span>
                <span class="feed-note-card__text">Mit einer klaren Primärfarbe, ruhigen Card-Hintergründen und moderatem Radius wirkt der Feed-Bereich deutlich näher am Standard-365CMS.</span>
            </div>
        </div>
        </div>
    </div>

    <!-- System -->
    <div id="stab-system" class="tab-content <?php echo $settingsTab === 'system' ? 'active' : ''; ?>">
        <div class="feed-info-grid">
        <div class="admin-card feed-settings-panel">
        <div class="feed-panel-header">
            <div>
                <h3>🖥️ System-Informationen</h3>
                <p>Plugin-Version, Routing und Datenbank-Struktur im Blick behalten.</p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <h4>Plugin</h4>
                <ul class="info-list">
                    <li><strong>Version:</strong> <?php echo CMS_FEED_VERSION; ?></li>
                    <li><strong>Archiv-URL:</strong> /<?php echo htmlspecialchars(($settings['archive_slug'] ?? 'feeds') === 'feed' ? 'feeds' : ($settings['archive_slug'] ?? 'feeds')); ?></li>
                    <li><strong>Public-Feeds:</strong> /feed/{slug}</li>
                </ul>
            </div>
            <div class="info-card">
                <h4>Datenbank-Tabellen</h4>
                <ul class="info-list">
                    <?php foreach ($db->get_table_names() as $table): ?>
                    <li><?php echo htmlspecialchars($table); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="info-card">
                <h4>Feed-Gesundheit</h4>
                <ul class="info-list">
                    <li><strong>Aktive Kanäle:</strong> <?php echo number_format((int) ($healthSummary['active'] ?? 0)); ?></li>
                    <li><strong>Mit Fehler:</strong> <?php echo number_format((int) ($healthSummary['with_errors'] ?? 0)); ?></li>
                    <li><strong>Überfällig:</strong> <?php echo number_format((int) ($healthSummary['overdue'] ?? 0)); ?></li>
                    <li><strong>Nie gelaufen:</strong> <?php echo number_format((int) ($healthSummary['never_fetched'] ?? 0)); ?></li>
                </ul>
            </div>
            <div class="info-card">
                <h4>Queue-Zustand</h4>
                <ul class="info-list">
                    <li><strong>Pending:</strong> <?php echo number_format((int) ($queueStats['pending'] ?? 0)); ?></li>
                    <li><strong>Processing:</strong> <?php echo number_format((int) ($queueStats['processing'] ?? 0)); ?></li>
                    <li><strong>Failed:</strong> <?php echo number_format((int) ($queueStats['failed'] ?? 0)); ?></li>
                    <li><strong>Done:</strong> <?php echo number_format((int) ($queueStats['done'] ?? 0)); ?></li>
                </ul>
            </div>
        </div>

        <div class="feed-inline-actions">
            <form method="POST" class="feed-inline-form-block">
                <input type="hidden" name="action" value="cleanup">
                <input type="hidden" name="cleanup_days" value="7">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <button type="submit" class="btn btn-secondary">🧹 Beiträge älter 7 Tage entfernen</button>
            </form>
            <form method="POST" class="feed-inline-form-block">
                <input type="hidden" name="action" value="cleanup">
                <input type="hidden" name="cleanup_days" value="30">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <button type="submit" class="btn btn-danger">🧹 Beiträge älter 30 Tage entfernen</button>
            </form>
        </div>
        </div>
        <div class="feed-side-stack">
            <div class="feed-note-card">
                <span class="feed-note-card__eyebrow">Wartung</span>
                <span class="feed-note-card__title">Regelmäßig aufräumen</span>
                <span class="feed-note-card__text">Gerade bei vielen Aggregator-Quellen lohnt sich eine Routine für Cleanup und Queue-Kontrolle, damit Admin und Frontend schnell bleiben.</span>
            </div>
        </div>
        </div>
    </div>

<?php endif; // end tab switch ?>

</div>

</div><!-- /.admin-card -->


<!-- ══════════════════════════════════════════════════════════════════════ -->
<!-- MODALS -->
<!-- ══════════════════════════════════════════════════════════════════════ -->

<!-- Kanal-Modal -->
<div id="channelModal" class="modal feed-modal">
    <div class="modal-content feed-modal-content--wide">
        <div class="modal-header">
            <h3 id="channelModalTitle">📡 Neuer Kanal</h3>
            <button class="modal-close" type="button" data-feed-close-modal="channelModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="channelForm" method="POST" class="admin-form">
                <input type="hidden" name="action" value="save_channel">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="channel_id" id="channel_id" value="">

                <div class="form-group">
                    <label class="form-label">Name <span class="feed-required">*</span></label>
                    <input type="text" name="channel_name" id="channel_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Feed-URL <span class="feed-required">*</span></label>
                    <input type="url" name="feed_url" id="channel_feed_url" class="form-control" required
                           placeholder="https://example.com/rss.xml">
                </div>
                <div class="form-group">
                    <label class="form-label">Website-URL</label>
                    <input type="url" name="site_url" id="channel_site_url" class="form-control"
                           placeholder="https://example.com">
                </div>
                <div class="form-group">
                    <label class="form-label">Bereich <span class="feed-required">*</span></label>
                    <select name="category_id" id="channel_category_id" class="form-control" required>
                        <option value="">– Bereich wählen –</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Beschreibung</label>
                    <textarea name="channel_description" id="channel_description" class="form-control" rows="2"></textarea>
                </div>
                <div class="feed-form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Abruf-Intervall (Minuten)</label>
                        <input type="number" name="fetch_interval" id="channel_fetch_interval" class="form-control"
                               value="60" min="5" max="1440">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Max. Beiträge</label>
                        <input type="number" name="max_items" id="channel_max_items" class="form-control"
                               value="50" min="10" max="500">
                    </div>
                </div>
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" id="channel_is_active" value="1" checked>
                    Kanal aktiv
                </label>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-feed-close-modal="channelModal">Abbrechen</button>
            <button type="submit" form="channelForm" class="btn btn-primary">💾 Speichern</button>
        </div>
    </div>
</div>

<!-- Bereich-Modal -->
<div id="categoryModal" class="modal feed-modal">
    <div class="modal-content feed-modal-content--wide">
        <div class="modal-header">
            <h3 id="categoryModalTitle">📁 Neuer Bereich</h3>
            <button class="modal-close" type="button" data-feed-close-modal="categoryModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="categoryForm" method="POST" class="admin-form">
                <input type="hidden" name="action" value="save_category">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="cat_id" id="cat_id" value="">

                <div class="feed-form-grid-auto-icon">
                    <div class="form-group">
                        <label class="form-label">Name <span class="feed-required">*</span></label>
                        <input type="text" name="cat_name" id="cat_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Icon</label>
                        <input type="text" name="cat_icon" id="cat_icon" class="form-control"
                               value="📰" class="feed-input-icon">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Slug <span class="feed-required">*</span></label>
                    <input type="text" name="cat_slug" id="cat_slug" class="form-control" required
                           pattern="[a-z0-9\-]+" class="feed-input-slug">
                          <small class="form-text">URL: /feed/<strong id="slugPreview">…</strong></small>
                </div>
                <div class="form-group">
                    <label class="form-label">Beschreibung</label>
                    <textarea name="cat_description" id="cat_description" class="form-control" rows="2"></textarea>
                </div>
                <div class="feed-form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Layout</label>
                        <select name="cat_layout" id="cat_layout" class="form-control">
                            <option value="grid">Grid</option>
                            <option value="list">Liste</option>
                            <option value="magazine">Magazin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Einträge/Seite</label>
                        <input type="number" name="cat_items_per_page" id="cat_items_per_page" class="form-control"
                               value="20" min="4" max="100">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sortierung</label>
                        <input type="number" name="cat_sort_order" id="cat_sort_order" class="form-control"
                               value="0" min="0" max="999">
                    </div>
                </div>
                <label class="checkbox-label">
                    <input type="checkbox" name="cat_is_public" id="cat_is_public" value="1" checked>
                    Öffentlich sichtbar
                </label>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-feed-close-modal="categoryModal">Abbrechen</button>
            <button type="submit" form="categoryForm" class="btn btn-primary">💾 Speichern</button>
        </div>
    </div>
</div>

<!-- Digest-Modal -->
<div id="digestModal" class="modal feed-modal">
    <div class="modal-content feed-modal-content--wide">
        <div class="modal-header">
            <h3 id="digestModalTitle">📧 Neuer Digest</h3>
            <button class="modal-close" type="button" data-feed-close-modal="digestModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="digestForm" method="POST" class="admin-form">
                <input type="hidden" name="action" value="save_digest">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="digest_id" id="digest_id" value="">

                <div class="form-group">
                    <label class="form-label">Name <span class="feed-required">*</span></label>
                    <input type="text" name="digest_name" id="digest_name" class="form-control" required
                           placeholder="z.B. Security Daily">
                </div>
                <div class="form-group">
                    <label class="form-label">E-Mail-Adresse <span class="feed-required">*</span></label>
                    <input type="email" name="digest_email" id="digest_email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Bereiche <span class="feed-required">*</span></label>
                    <?php foreach ($categories as $cat): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="digest_categories[]" value="<?php echo (int)$cat['id']; ?>"
                               class="digest-cat-checkbox">
                        <?php echo htmlspecialchars($cat['icon'] . ' ' . $cat['name']); ?>
                    </label>
                    <?php endforeach; ?>
                    <?php if (empty($categories)): ?>
                    <p class="feed-empty-text">Erstelle zuerst Bereiche.</p>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Frequenz</label>
                    <select name="digest_frequency" id="digest_frequency" class="form-control feed-input-select-sm">
                        <option value="1">1× täglich</option>
                        <option value="2">2× täglich</option>
                        <option value="3">3× täglich</option>
                        <option value="4">4× täglich</option>
                    </select>
                </div>
                <label class="checkbox-label">
                    <input type="checkbox" name="digest_is_active" id="digest_is_active" value="1" checked>
                    Digest aktiv
                </label>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-feed-close-modal="digestModal">Abbrechen</button>
            <button type="submit" form="digestForm" class="btn btn-primary">💾 Speichern</button>
        </div>
    </div>
</div>

<!-- Lösch-Modal -->
<div id="deleteModal" class="modal feed-modal">
    <div class="modal-content feed-modal-content--compact">
        <div class="modal-header">
            <h3>🗑️ Eintrag löschen</h3>
            <button class="modal-close" type="button" data-feed-close-modal="deleteModal">&times;</button>
        </div>
        <div class="modal-body">
            <p>Soll <strong id="deleteModalName"></strong> wirklich gelöscht werden?</p>
            <p class="feed-warning-text">⚠️ Diese Aktion kann nicht rückgängig gemacht werden.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-feed-close-modal="deleteModal">Abbrechen</button>
            <form method="POST" id="deleteModalForm" class="feed-inline-form">
                <input type="hidden" name="action" id="deleteModalAction" value="">
                <input type="hidden" name="id" id="deleteModalId">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <button type="submit" class="btn btn-danger">🗑️ Endgültig löschen</button>
            </form>
        </div>
    </div>
</div>

<textarea id="feed-channels-data" class="feed-data-payload" hidden><?php echo htmlspecialchars(json_encode(array_map(fn($c) => [
    'id'             => (int)$c['id'],
    'name'           => $c['name'],
    'feed_url'       => $c['feed_url'],
    'site_url'       => $c['site_url'] ?? '',
    'category_id'    => (int)$c['category_id'],
    'description'    => $c['description'] ?? '',
    'fetch_interval' => (int)$c['fetch_interval'],
    'max_items'      => (int)$c['max_items'],
    'is_active'      => (int)$c['is_active'],
], $channels)), ENT_QUOTES); ?></textarea>

<textarea id="feed-categories-data" class="feed-data-payload" hidden><?php echo htmlspecialchars(json_encode(array_map(fn($c) => [
    'id'             => (int)$c['id'],
    'name'           => $c['name'],
    'slug'           => $c['slug'],
    'description'    => $c['description'] ?? '',
    'icon'           => $c['icon'] ?? '📰',
    'is_public'      => (int)$c['is_public'],
    'sort_order'     => (int)$c['sort_order'],
    'layout'         => $c['layout'] ?? 'grid',
    'items_per_page' => (int)$c['items_per_page'],
], $categories)), ENT_QUOTES); ?></textarea>

<textarea id="feed-digests-data" class="feed-data-payload" hidden><?php echo htmlspecialchars(json_encode(array_map(fn($d) => [
    'id'           => (int)$d['id'],
    'name'         => $d['name'],
    'email'        => $d['email'],
    'category_ids' => json_decode($d['category_ids'] ?? '[]', true) ?? [],
    'frequency'    => (int)$d['frequency'],
    'is_active'    => (int)$d['is_active'],
], $digests)), ENT_QUOTES); ?></textarea>



</div>
