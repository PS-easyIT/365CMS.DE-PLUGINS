<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>📡 RSS-Feed-Aggregator</h2>
        <p>Feeds sammeln, Bereiche verwalten und als Digest versenden</p>
    </div>
    <div class="header-actions">
        <?php if ($tab === 'dashboard'): ?>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="fetch_now">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <button type="submit" class="btn btn-primary">🔄 Alle Feeds abrufen</button>
            </form>
        <?php elseif ($tab === 'channels'): ?>
            <button type="button" class="btn btn-primary" onclick="openModal('channelModal')">➕ Neuer Kanal</button>
        <?php elseif ($tab === 'categories'): ?>
            <button type="button" class="btn btn-primary" onclick="openModal('categoryModal')">➕ Neuer Bereich</button>
        <?php elseif ($tab === 'digests'): ?>
            <button type="button" class="btn btn-primary" onclick="openModal('digestModal')">➕ Neuer Digest</button>
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
            <span style="background:#fee2e2;color:#991b1b;padding:.1rem .4rem;border-radius:10px;font-size:.7rem;font-weight:700;margin-left:.2rem;"><?php echo $stats['channels_errors']; ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Content -->
<div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;">

<?php
// ══════════════════════════════════════════════════════════════════════
// TAB: Dashboard
// ══════════════════════════════════════════════════════════════════════
if ($tab === 'dashboard'):
?>
    <h3>📊 Übersicht</h3>

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
        <?php if (($stats['channels_errors'] ?? 0) > 0): ?>
        <div class="stat-card" style="border-left:3px solid #ef4444;">
            <div class="stat-icon">⚠️</div>
            <div class="stat-number" style="color:#ef4444;"><?php echo $stats['channels_errors']; ?></div>
            <div class="stat-label">Kanäle mit Fehlern</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Schnellzugriff -->
    <div style="margin-top:1.5rem;">
        <h4 style="font-size:.85rem;color:#475569;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin:0 0 .75rem;">⚡ Schnellzugriff</h4>
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
            <a href="?tab=channels" class="btn btn-secondary btn-sm">📡 Kanäle verwalten</a>
            <a href="?tab=categories" class="btn btn-secondary btn-sm">📁 Bereiche verwalten</a>
            <a href="?tab=settings" class="btn btn-secondary btn-sm">⚙️ Einstellungen</a>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="cleanup">
                <input type="hidden" name="cleanup_days" value="90">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <button type="submit" class="btn btn-secondary btn-sm">🧹 Alte Beiträge aufräumen</button>
            </form>
        </div>
    </div>

    <!-- Letzte Fehler -->
    <?php
    $errorChannels = array_filter($channels, fn($c) => !empty($c['last_error']));
    if (!empty($errorChannels)):
    ?>
    <div style="margin-top:1.5rem;">
        <h4 style="font-size:.85rem;color:#ef4444;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin:0 0 .75rem;">⚠️ Feed-Fehler</h4>
        <?php foreach ($errorChannels as $ch): ?>
        <div class="alert alert-error" style="font-size:.875rem;margin-bottom:.5rem;">
            <strong><?php echo htmlspecialchars($ch['name']); ?>:</strong>
            <?php echo htmlspecialchars($ch['last_error']); ?>
            <span style="color:#94a3b8;font-size:.75rem;margin-left:.5rem;">
                <?php echo $ch['last_fetched_at'] ? date('d.m.Y H:i', strtotime($ch['last_fetched_at'])) : 'Nie'; ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

<?php
// ══════════════════════════════════════════════════════════════════════
// TAB: Kanäle
// ══════════════════════════════════════════════════════════════════════
elseif ($tab === 'channels'):
?>
    <h3>📡 RSS-Kanäle</h3>

    <?php if (empty($channels)): ?>
    <div class="empty-state">
        <p style="font-size:2.5rem;margin:0;">📡</p>
        <p><strong>Noch keine Kanäle vorhanden</strong></p>
        <p style="color:#64748b;font-size:.875rem;">Erstelle den ersten RSS-Kanal über den Button oben rechts.</p>
    </div>
    <?php else: ?>
    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
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
                    <td>
                        <a href="javascript:void(0)" onclick="editChannel(<?php echo (int)$ch['id']; ?>)"
                           style="font-weight:600;color:var(--admin-primary);">
                            <?php echo htmlspecialchars($ch['name']); ?>
                        </a>
                        <div style="font-size:.75rem;color:#94a3b8;margin-top:.15rem;">
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
                            <span style="color:#94a3b8;">Nie</span>
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
                        <div style="display:flex;gap:.4rem;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="fetch_now">
                                <input type="hidden" name="channel_id" value="<?php echo (int)$ch['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <button type="submit" class="btn btn-sm btn-secondary" title="Jetzt abrufen">🔄</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="editChannel(<?php echo (int)$ch['id']; ?>)" title="Bearbeiten">✏️</button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="openDeleteModal(<?php echo (int)$ch['id']; ?>, '<?php echo htmlspecialchars($ch['name'], ENT_QUOTES); ?>', 'delete_channel')" title="Löschen">🗑️</button>
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
    <div class="alert" style="background:#dbeafe;color:#1e40af;border-left:4px solid #3b82f6;margin-bottom:1.25rem;">
        ℹ️ Jeder Bereich bekommt eine eigene öffentliche Seite unter <code>/<?php echo htmlspecialchars($settings['archive_slug'] ?? 'feeds'); ?>/{slug}</code>
    </div>

    <?php if (empty($categories)): ?>
    <div class="empty-state">
        <p style="font-size:2.5rem;margin:0;">📁</p>
        <p><strong>Noch keine Bereiche vorhanden</strong></p>
        <p style="color:#64748b;font-size:.875rem;">Erstelle den ersten Bereich, z.B. "Security" oder "Tech News".</p>
    </div>
    <?php else: ?>
    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
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
            ?>
                <tr>
                    <td style="font-size:1.5rem;"><?php echo htmlspecialchars($cat['icon']); ?></td>
                    <td>
                        <a href="javascript:void(0)" onclick="editCategory(<?php echo (int)$cat['id']; ?>)"
                           style="font-weight:600;color:var(--admin-primary);">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                        <?php if (!empty($cat['description'])): ?>
                        <div style="font-size:.75rem;color:#94a3b8;margin-top:.15rem;">
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
                        <div style="display:flex;gap:.4rem;">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="editCategory(<?php echo (int)$cat['id']; ?>)" title="Bearbeiten">✏️</button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="openDeleteModal(<?php echo (int)$cat['id']; ?>, '<?php echo htmlspecialchars($cat['name'], ENT_QUOTES); ?>', 'delete_category')" title="Löschen">🗑️</button>
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
// TAB: Beiträge
// ══════════════════════════════════════════════════════════════════════
elseif ($tab === 'items'):
    $itemPage   = max(1, (int)($_GET['page'] ?? 1));
    $perPage    = 25;
    $offset     = ($itemPage - 1) * $perPage;
    $itemFilter = ['include_hidden' => true];
    if (!empty($_GET['cat'])) $itemFilter['category_id'] = (int)$_GET['cat'];
    if (!empty($_GET['ch']))  $itemFilter['channel_id']  = (int)$_GET['ch'];
    if (!empty($_GET['q']))   $itemFilter['search']      = sanitize_text_field($_GET['q']);
    $totalItems = $db->count_items($itemFilter);
    $totalPages = (int)ceil($totalItems / $perPage);
    $feedItems  = $db->get_items($itemFilter, $offset, $perPage);
?>
    <h3>📰 Beiträge (<?php echo number_format($totalItems); ?>)</h3>

    <!-- Filter -->
    <form method="GET" style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.25rem;">
        <input type="hidden" name="tab" value="items">
        <select name="cat" class="form-control" style="max-width:200px;">
            <option value="">Alle Bereiche</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?php echo (int)$cat['id']; ?>" <?php echo ((int)($_GET['cat'] ?? 0)) === (int)$cat['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="q" class="form-control" style="max-width:250px;"
               value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
               placeholder="Suche...">
        <button type="submit" class="btn btn-secondary btn-sm">🔍 Filtern</button>
    </form>

    <?php if (empty($feedItems)): ?>
    <div class="empty-state">
        <p style="font-size:2.5rem;margin:0;">📰</p>
        <p><strong>Keine Beiträge gefunden</strong></p>
        <p style="color:#64748b;font-size:.875rem;">Feeds abrufen oder Filter anpassen.</p>
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
                    <td style="max-width:350px;">
                        <a href="<?php echo htmlspecialchars($item['link']); ?>" target="_blank"
                           style="font-weight:600;color:var(--admin-primary);text-decoration:none;">
                            <?php echo htmlspecialchars(mb_substr($item['title'], 0, 80)); ?>
                        </a>
                        <?php if ((int)$item['is_featured']): ?>
                            <span style="background:#fef3c7;color:#92400e;padding:.1rem .3rem;border-radius:4px;font-size:.65rem;font-weight:700;margin-left:.3rem;">⭐ Featured</span>
                        <?php endif; ?>
                        <?php if ((int)($item['is_hidden'] ?? 0)): ?>
                            <span style="background:#f1f5f9;color:#64748b;padding:.1rem .3rem;border-radius:4px;font-size:.65rem;font-weight:700;margin-left:.3rem;">👁️‍🗨️ Ausgeblendet</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.85rem;"><?php echo htmlspecialchars($item['channel_name'] ?? ''); ?></td>
                    <td style="font-size:.85rem;"><?php echo htmlspecialchars($item['category_name'] ?? ''); ?></td>
                    <td style="font-size:.85rem;"><?php echo date('d.m.Y H:i', strtotime($item['pub_date'])); ?></td>
                    <td>
                        <div style="display:flex;gap:.3rem;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle_featured">
                                <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <button type="submit" class="btn btn-sm btn-secondary" title="<?php echo (int)$item['is_featured'] ? 'Featured entfernen' : 'Als Featured markieren'; ?>">⭐</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle_hidden">
                                <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <button type="submit" class="btn btn-sm btn-secondary" title="Ausblenden">👁️</button>
                            </form>
                            <form method="POST" style="display:inline;">
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
    <div style="display:flex;gap:.5rem;justify-content:center;margin-top:1.5rem;">
        <?php if ($itemPage > 1): ?>
            <a href="?tab=items&page=<?php echo $itemPage - 1; ?>&cat=<?php echo (int)($_GET['cat'] ?? 0); ?>&q=<?php echo urlencode($_GET['q'] ?? ''); ?>" class="btn btn-secondary btn-sm">← Zurück</a>
        <?php endif; ?>
        <span style="padding:.375rem .875rem;color:#64748b;font-size:.875rem;">
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
    <div class="alert" style="background:#dbeafe;color:#1e40af;border-left:4px solid #3b82f6;margin-bottom:1.25rem;">
        ℹ️ Digests werden automatisch gemäß der gewählten Frequenz versendet. Du kannst auch manuell Test-Mails senden.
    </div>

    <?php if (empty($digests)): ?>
    <div class="empty-state">
        <p style="font-size:2.5rem;margin:0;">📧</p>
        <p><strong>Noch keine Digests konfiguriert</strong></p>
        <p style="color:#64748b;font-size:.875rem;">Erstelle einen Digest um Feed-Beiträge per E-Mail zu versenden.</p>
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
                    <td style="font-weight:600;"><?php echo htmlspecialchars($dg['name']); ?></td>
                    <td><?php echo htmlspecialchars($dg['email']); ?></td>
                    <td style="font-size:.85rem;">
                        <?php echo htmlspecialchars(implode(', ', $dgCatNames) ?: '–'); ?>
                    </td>
                    <td><?php echo $mailer->get_frequency_label((int)$dg['frequency']); ?></td>
                    <td>
                        <?php if ($dg['last_sent_at']): ?>
                            <?php echo date('d.m.Y H:i', strtotime($dg['last_sent_at'])); ?>
                        <?php else: ?>
                            <span style="color:#94a3b8;">Noch nie</span>
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
                        <div style="display:flex;gap:.3rem;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="test_digest">
                                <input type="hidden" name="digest_id" value="<?php echo (int)$dg['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <button type="submit" class="btn btn-sm btn-secondary" title="Test senden">📤</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="editDigest(<?php echo (int)$dg['id']; ?>)" title="Bearbeiten">✏️</button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="openDeleteModal(<?php echo (int)$dg['id']; ?>, '<?php echo htmlspecialchars($dg['name'], ENT_QUOTES); ?>', 'delete_digest')" title="Löschen">🗑️</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Digest E-Mail-Einstellungen -->
    <hr style="border:none;border-top:1px solid #f1f5f9;margin:1.5rem 0;">
    <h4 style="font-size:.95rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">📧 Digest-Grundeinstellungen</h4>

    <form method="POST" class="admin-form" style="max-width:500px;">
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
                   min="5" max="100" style="max-width:120px;">
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
    <!-- Sub-Tabs -->
    <div style="display:flex;gap:.3rem;margin-bottom:1.25rem;border-bottom:2px solid #e2e8f0;flex-wrap:wrap;">
        <button class="tab-btn <?php echo $settingsTab === 'general' ? 'active' : ''; ?>" onclick="switchTab('stab-general', this)" type="button">⚙️ Allgemein</button>
        <button class="tab-btn <?php echo $settingsTab === 'design' ? 'active' : ''; ?>" onclick="switchTab('stab-design', this)" type="button">🎨 Design</button>
        <button class="tab-btn <?php echo $settingsTab === 'system' ? 'active' : ''; ?>" onclick="switchTab('stab-system', this)" type="button">🖥️ System</button>
    </div>

    <!-- Allgemein -->
    <div id="stab-general" class="tab-content <?php echo $settingsTab === 'general' ? 'active' : ''; ?>">
        <h3>⚙️ Allgemeine Einstellungen</h3>

        <form method="POST" class="admin-form" style="max-width:600px;">
            <input type="hidden" name="action" value="save_settings">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

            <div class="form-group">
                <label class="form-label">Seitentitel <span style="color:#ef4444;">*</span></label>
                <input type="text" name="archive_title" class="form-control"
                       value="<?php echo htmlspecialchars($settings['archive_title'] ?? 'Feed-Übersicht'); ?>" required>
                <small class="form-text">Überschrift der öffentlichen Feed-Seite</small>
            </div>
            <div class="form-group">
                <label class="form-label">Beschreibung</label>
                <textarea name="archive_description" class="form-control" rows="2"><?php echo htmlspecialchars($settings['archive_description'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">URL-Slug <span style="color:#ef4444;">*</span></label>
                <input type="text" name="archive_slug" class="form-control"
                       value="<?php echo htmlspecialchars($settings['archive_slug'] ?? 'feeds'); ?>"
                       pattern="[a-z0-9\-]+" required style="max-width:250px;">
                <small class="form-text">Öffentliche URL: <code>/<?php echo htmlspecialchars($settings['archive_slug'] ?? 'feeds'); ?></code></small>
            </div>
            <div class="form-group">
                <label class="form-label">Einträge pro Seite</label>
                <input type="number" name="per_page" class="form-control"
                       value="<?php echo (int)($settings['per_page'] ?? 20); ?>"
                       min="4" max="100" style="max-width:120px;">
            </div>
            <div class="form-group">
                <label class="form-label">Zusammenfassung (Zeichen)</label>
                <input type="number" name="excerpt_length" class="form-control"
                       value="<?php echo (int)($settings['excerpt_length'] ?? 160); ?>"
                       min="50" max="500" style="max-width:120px;">
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
    </div>

    <!-- Design -->
    <div id="stab-design" class="tab-content <?php echo $settingsTab === 'design' ? 'active' : ''; ?>">
        <h3>🎨 Design-Einstellungen</h3>

        <form method="POST" class="admin-form" style="max-width:700px;">
            <input type="hidden" name="action" value="save_design">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
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
                    <div style="display:flex;gap:.625rem;align-items:center;">
                        <input type="color" name="<?php echo $key; ?>"
                               class="form-control" style="width:56px;height:40px;padding:.125rem;"
                               value="<?php echo htmlspecialchars($settings[$key] ?? $default); ?>">
                        <input type="text" name="<?php echo $key; ?>_text"
                               class="form-control"
                               value="<?php echo htmlspecialchars($settings[$key] ?? $default); ?>"
                               pattern="^#[0-9A-Fa-f]{6}$" maxlength="7"
                               style="max-width:110px;font-family:monospace;"
                               onchange="this.previousElementSibling.value=this.value">
                    </div>
                    <small class="form-text"><?php echo $hint; ?></small>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="form-group" style="max-width:340px;">
                <label class="form-label">Border-Radius (Karten)</label>
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <input type="range" name="border_radius" min="0" max="24" step="2"
                           value="<?php echo (int)($settings['border_radius'] ?? 10); ?>"
                           oninput="document.getElementById('radiusPreview').textContent=this.value+'px'"
                           style="flex:1;">
                    <span id="radiusPreview" style="font-weight:700;min-width:36px;text-align:right;color:#3b82f6;">
                        <?php echo (int)($settings['border_radius'] ?? 10); ?>px
                    </span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Spalten (Archiv-Grid)</label>
                <select name="grid_columns" class="form-control" style="max-width:220px;">
                    <?php foreach (['auto' => 'Automatisch (empfohlen)', '2' => '2 Spalten', '3' => '3 Spalten', '4' => '4 Spalten'] as $val => $lbl): ?>
                    <option value="<?php echo $val; ?>" <?php echo ($settings['grid_columns'] ?? 'auto') === $val ? 'selected' : ''; ?>>
                        <?php echo $lbl; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">💾 Design speichern</button>
        </form>
    </div>

    <!-- System -->
    <div id="stab-system" class="tab-content <?php echo $settingsTab === 'system' ? 'active' : ''; ?>">
        <h3>🖥️ System-Informationen</h3>

        <div class="info-grid">
            <div class="info-card">
                <h4>Plugin</h4>
                <ul class="info-list">
                    <li><strong>Version:</strong> <?php echo CMS_FEED_VERSION; ?></li>
                    <li><strong>Archiv-URL:</strong> /<?php echo htmlspecialchars($settings['archive_slug'] ?? 'feeds'); ?></li>
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
        </div>

        <div style="margin-top:1.25rem;">
            <form method="POST" style="display:inline-block;margin-right:.5rem;">
                <input type="hidden" name="action" value="cleanup">
                <input type="hidden" name="cleanup_days" value="90">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <button type="submit" class="btn btn-secondary">🧹 Beiträge älter 90 Tage entfernen</button>
            </form>
            <form method="POST" style="display:inline-block;">
                <input type="hidden" name="action" value="cleanup">
                <input type="hidden" name="cleanup_days" value="30">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <button type="submit" class="btn btn-danger">🧹 Beiträge älter 30 Tage entfernen</button>
            </form>
        </div>
    </div>

<?php endif; // end tab switch ?>

</div><!-- /.admin-card -->


<!-- ══════════════════════════════════════════════════════════════════════ -->
<!-- MODALS -->
<!-- ══════════════════════════════════════════════════════════════════════ -->

<!-- Kanal-Modal -->
<div id="channelModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:600px;">
        <div class="modal-header">
            <h3 id="channelModalTitle">📡 Neuer Kanal</h3>
            <button class="modal-close" onclick="closeModal('channelModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="channelForm" method="POST" class="admin-form">
                <input type="hidden" name="action" value="save_channel">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="channel_id" id="channel_id" value="">

                <div class="form-group">
                    <label class="form-label">Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="channel_name" id="channel_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Feed-URL <span style="color:#ef4444;">*</span></label>
                    <input type="url" name="feed_url" id="channel_feed_url" class="form-control" required
                           placeholder="https://example.com/rss.xml">
                </div>
                <div class="form-group">
                    <label class="form-label">Website-URL</label>
                    <input type="url" name="site_url" id="channel_site_url" class="form-control"
                           placeholder="https://example.com">
                </div>
                <div class="form-group">
                    <label class="form-label">Bereich <span style="color:#ef4444;">*</span></label>
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
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
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
            <button type="button" class="btn btn-secondary" onclick="closeModal('channelModal')">Abbrechen</button>
            <button type="submit" form="channelForm" class="btn btn-primary">💾 Speichern</button>
        </div>
    </div>
</div>

<!-- Bereich-Modal -->
<div id="categoryModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:600px;">
        <div class="modal-header">
            <h3 id="categoryModalTitle">📁 Neuer Bereich</h3>
            <button class="modal-close" onclick="closeModal('categoryModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="categoryForm" method="POST" class="admin-form">
                <input type="hidden" name="action" value="save_category">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="cat_id" id="cat_id" value="">

                <div style="display:grid;grid-template-columns:1fr auto;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label">Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="cat_name" id="cat_name" class="form-control" required
                               oninput="if(!document.getElementById('cat_id').value)document.getElementById('cat_slug').value=this.value.toLowerCase().replace(/[^a-z0-9]/g,'-').replace(/-+/g,'-').replace(/^-|-$/g,'')">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Icon</label>
                        <input type="text" name="cat_icon" id="cat_icon" class="form-control"
                               value="📰" style="max-width:60px;text-align:center;font-size:1.5rem;">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Slug <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="cat_slug" id="cat_slug" class="form-control" required
                           pattern="[a-z0-9\-]+" style="max-width:250px;">
                    <small class="form-text">URL: /<?php echo htmlspecialchars($settings['archive_slug'] ?? 'feeds'); ?>/<strong id="slugPreview">…</strong></small>
                </div>
                <div class="form-group">
                    <label class="form-label">Beschreibung</label>
                    <textarea name="cat_description" id="cat_description" class="form-control" rows="2"></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
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
            <button type="button" class="btn btn-secondary" onclick="closeModal('categoryModal')">Abbrechen</button>
            <button type="submit" form="categoryForm" class="btn btn-primary">💾 Speichern</button>
        </div>
    </div>
</div>

<!-- Digest-Modal -->
<div id="digestModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:600px;">
        <div class="modal-header">
            <h3 id="digestModalTitle">📧 Neuer Digest</h3>
            <button class="modal-close" onclick="closeModal('digestModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="digestForm" method="POST" class="admin-form">
                <input type="hidden" name="action" value="save_digest">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="digest_id" id="digest_id" value="">

                <div class="form-group">
                    <label class="form-label">Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="digest_name" id="digest_name" class="form-control" required
                           placeholder="z.B. Security Daily">
                </div>
                <div class="form-group">
                    <label class="form-label">E-Mail-Adresse <span style="color:#ef4444;">*</span></label>
                    <input type="email" name="digest_email" id="digest_email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Bereiche <span style="color:#ef4444;">*</span></label>
                    <?php foreach ($categories as $cat): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="digest_categories[]" value="<?php echo (int)$cat['id']; ?>"
                               class="digest-cat-checkbox">
                        <?php echo htmlspecialchars($cat['icon'] . ' ' . $cat['name']); ?>
                    </label>
                    <?php endforeach; ?>
                    <?php if (empty($categories)): ?>
                    <p style="color:#94a3b8;font-size:.875rem;">Erstelle zuerst Bereiche.</p>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Frequenz</label>
                    <select name="digest_frequency" id="digest_frequency" class="form-control" style="max-width:220px;">
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
            <button type="button" class="btn btn-secondary" onclick="closeModal('digestModal')">Abbrechen</button>
            <button type="submit" form="digestForm" class="btn btn-primary">💾 Speichern</button>
        </div>
    </div>
</div>

<!-- Lösch-Modal -->
<div id="deleteModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3>🗑️ Eintrag löschen</h3>
            <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Soll <strong id="deleteModalName"></strong> wirklich gelöscht werden?</p>
            <p style="color:#ef4444;font-size:.875rem;">⚠️ Diese Aktion kann nicht rückgängig gemacht werden.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Abbrechen</button>
            <form method="POST" id="deleteModalForm" style="display:inline;">
                <input type="hidden" name="action" id="deleteModalAction" value="">
                <input type="hidden" name="id" id="deleteModalId">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <button type="submit" class="btn btn-danger">🗑️ Endgültig löschen</button>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════ -->
<!-- JavaScript -->
<!-- ══════════════════════════════════════════════════════════════════════ -->
<script>
// Kanal-Daten für Edit-Modal (inline, kein extra AJAX nötig)
const channelsData = <?php echo json_encode(array_map(fn($c) => [
    'id'             => (int)$c['id'],
    'name'           => $c['name'],
    'feed_url'       => $c['feed_url'],
    'site_url'       => $c['site_url'] ?? '',
    'category_id'    => (int)$c['category_id'],
    'description'    => $c['description'] ?? '',
    'fetch_interval' => (int)$c['fetch_interval'],
    'max_items'      => (int)$c['max_items'],
    'is_active'      => (int)$c['is_active'],
], $channels)); ?>;

const categoriesData = <?php echo json_encode(array_map(fn($c) => [
    'id'             => (int)$c['id'],
    'name'           => $c['name'],
    'slug'           => $c['slug'],
    'description'    => $c['description'] ?? '',
    'icon'           => $c['icon'] ?? '📰',
    'is_public'      => (int)$c['is_public'],
    'sort_order'     => (int)$c['sort_order'],
    'layout'         => $c['layout'] ?? 'grid',
    'items_per_page' => (int)$c['items_per_page'],
], $categories)); ?>;

const digestsData = <?php echo json_encode(array_map(fn($d) => [
    'id'           => (int)$d['id'],
    'name'         => $d['name'],
    'email'        => $d['email'],
    'category_ids' => json_decode($d['category_ids'] ?? '[]', true) ?? [],
    'frequency'    => (int)$d['frequency'],
    'is_active'    => (int)$d['is_active'],
], $digests)); ?>;

function editChannel(id) {
    const ch = channelsData.find(c => c.id === id);
    if (!ch) return;
    document.getElementById('channelModalTitle').textContent = '📡 Kanal bearbeiten';
    document.getElementById('channel_id').value = ch.id;
    document.getElementById('channel_name').value = ch.name;
    document.getElementById('channel_feed_url').value = ch.feed_url;
    document.getElementById('channel_site_url').value = ch.site_url;
    document.getElementById('channel_category_id').value = ch.category_id;
    document.getElementById('channel_description').value = ch.description;
    document.getElementById('channel_fetch_interval').value = ch.fetch_interval;
    document.getElementById('channel_max_items').value = ch.max_items;
    document.getElementById('channel_is_active').checked = !!ch.is_active;
    openModal('channelModal');
}

function editCategory(id) {
    const cat = categoriesData.find(c => c.id === id);
    if (!cat) return;
    document.getElementById('categoryModalTitle').textContent = '📁 Bereich bearbeiten';
    document.getElementById('cat_id').value = cat.id;
    document.getElementById('cat_name').value = cat.name;
    document.getElementById('cat_slug').value = cat.slug;
    document.getElementById('cat_description').value = cat.description;
    document.getElementById('cat_icon').value = cat.icon;
    document.getElementById('cat_is_public').checked = !!cat.is_public;
    document.getElementById('cat_sort_order').value = cat.sort_order;
    document.getElementById('cat_layout').value = cat.layout;
    document.getElementById('cat_items_per_page').value = cat.items_per_page;
    openModal('categoryModal');
}

function editDigest(id) {
    const dg = digestsData.find(d => d.id === id);
    if (!dg) return;
    document.getElementById('digestModalTitle').textContent = '📧 Digest bearbeiten';
    document.getElementById('digest_id').value = dg.id;
    document.getElementById('digest_name').value = dg.name;
    document.getElementById('digest_email').value = dg.email;
    document.getElementById('digest_frequency').value = dg.frequency;
    document.getElementById('digest_is_active').checked = !!dg.is_active;
    // Checkboxen setzen
    document.querySelectorAll('.digest-cat-checkbox').forEach(cb => {
        cb.checked = dg.category_ids.includes(parseInt(cb.value));
    });
    openModal('digestModal');
}

function openDeleteModal(id, name, action) {
    document.getElementById('deleteModalId').value = id;
    document.getElementById('deleteModalName').textContent = name;
    document.getElementById('deleteModalAction').value = action;
    openModal('deleteModal');
}

// Slug-Preview aktualisieren
const slugInput = document.getElementById('cat_slug');
if (slugInput) {
    slugInput.addEventListener('input', function() {
        document.getElementById('slugPreview').textContent = this.value || '…';
    });
}

// Modal-Reset bei Öffnen eines neuen Eintrags
document.querySelectorAll('.modal').forEach(modal => {
    const observer = new MutationObserver(() => {
        if (modal.style.display === 'none') {
            // Form zurücksetzen wenn Modal geschlossen
            const form = modal.querySelector('form');
            if (form && !form.querySelector('[name="id"]')?.value) {
                // Nur resetten wenn kein Edit
            }
        }
    });
});

// Neuen Kanal Modal resetten
const channelModalBtn = document.querySelector('[onclick*="openModal(\'channelModal\')"]');
if (channelModalBtn) {
    channelModalBtn.addEventListener('click', () => {
        document.getElementById('channelModalTitle').textContent = '📡 Neuer Kanal';
        document.getElementById('channelForm').reset();
        document.getElementById('channel_id').value = '';
        document.getElementById('channel_is_active').checked = true;
    });
}
const categoryModalBtn = document.querySelector('[onclick*="openModal(\'categoryModal\')"]');
if (categoryModalBtn) {
    categoryModalBtn.addEventListener('click', () => {
        document.getElementById('categoryModalTitle').textContent = '📁 Neuer Bereich';
        document.getElementById('categoryForm').reset();
        document.getElementById('cat_id').value = '';
        document.getElementById('cat_is_public').checked = true;
        document.getElementById('cat_icon').value = '📰';
    });
}
const digestModalBtn = document.querySelector('[onclick*="openModal(\'digestModal\')"]');
if (digestModalBtn) {
    digestModalBtn.addEventListener('click', () => {
        document.getElementById('digestModalTitle').textContent = '📧 Neuer Digest';
        document.getElementById('digestForm').reset();
        document.getElementById('digest_id').value = '';
        document.getElementById('digest_is_active').checked = true;
    });
}
</script>
