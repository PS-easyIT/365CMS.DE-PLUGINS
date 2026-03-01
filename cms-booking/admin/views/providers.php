<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<!-- Section-Nav -->
<nav class="lp-section-nav">
    <a href="?section=dashboard" class="lp-section-nav__item">
        <span class="lp-section-nav__icon">📊</span> Dashboard
    </a>
    <a href="?section=bookings" class="lp-section-nav__item">
        <span class="lp-section-nav__icon">📋</span> Buchungen
    </a>
    <a href="?section=providers" class="lp-section-nav__item active">
        <span class="lp-section-nav__icon">👥</span> Anbieter
    </a>
    <a href="?section=services" class="lp-section-nav__item">
        <span class="lp-section-nav__icon">🛠️</span> Leistungen
    </a>
    <a href="?section=settings" class="lp-section-nav__item">
        <span class="lp-section-nav__icon">⚙️</span> Einstellungen
    </a>
</nav>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>👥 Anbieter verwalten</h2>
        <p>Buchbare Anbieter aus allen integrierten Plugins</p>
    </div>
</div>

<!-- Alerts -->
<?php if (!empty($success)): ?>
<div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Filter -->
<div class="admin-card">
    <form method="GET" style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end;">
        <input type="hidden" name="section" value="providers">

        <div class="form-group" style="margin:0;min-width:150px;">
            <label class="form-label">Status</label>
            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">Alle</option>
                <option value="active"   <?php echo $status === 'active'   ? 'selected' : ''; ?>>Aktiv</option>
                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inaktiv</option>
            </select>
        </div>

        <div class="form-group" style="margin:0;flex:1;min-width:200px;">
            <label class="form-label">Suche</label>
            <input type="text" name="q" class="form-control" placeholder="Name, E-Mail, Slug …"
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <button type="submit" class="btn btn-secondary btn-sm">🔍 Filtern</button>
    </form>
</div>

<!-- Tabelle -->
<div class="admin-card">
    <?php if (empty($items)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0;">👤</p>
            <p><strong>Keine Anbieter gefunden</strong></p>
            <p class="text-muted">Anbieter werden automatisch aus integrierten Plugins synchronisiert.</p>
        </div>
    <?php else: ?>
        <div class="users-table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Anbieter</th>
                        <th>Plugin</th>
                        <th>Slug</th>
                        <th>E-Mail</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($item['display_name']); ?></strong>
                            <?php if (!empty($item['bio'])): ?>
                            <div style="color:#64748b;font-size:.8rem;max-width:250px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?php echo htmlspecialchars(mb_substr(strip_tags($item['bio']), 0, 80)); ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $typeInfo = $types[$item['source_plugin']] ?? null;
                            $icon     = $typeInfo['icon'] ?? '📋';
                            $label    = $typeInfo['label'] ?? $item['source_plugin'];
                            ?>
                            <span title="<?php echo htmlspecialchars($item['source_plugin']); ?>">
                                <?php echo $icon; ?> <?php echo htmlspecialchars($label); ?>
                            </span>
                        </td>
                        <td><code style="font-size:.82rem;"><?php echo htmlspecialchars($item['slug']); ?></code></td>
                        <td><?php echo htmlspecialchars($item['email'] ?? '—'); ?></td>
                        <td>
                            <span class="status-badge <?php echo $item['status'] === 'active' ? 'active' : 'inactive'; ?>">
                                <?php echo $item['status'] === 'active' ? 'Aktiv' : 'Inaktiv'; ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                                <!-- Sync -->
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="provider_id" value="<?php echo (int) $item['id']; ?>">
                                    <input type="hidden" name="provider_action" value="sync">
                                    <button type="submit" class="btn btn-sm btn-secondary" title="Synchronisieren">🔄</button>
                                </form>

                                <!-- Status -->
                                <?php if ($item['status'] === 'active'): ?>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="provider_id" value="<?php echo (int) $item['id']; ?>">
                                    <input type="hidden" name="provider_action" value="deactivate">
                                    <button type="submit" class="btn btn-sm btn-secondary" title="Deaktivieren">⏸️</button>
                                </form>
                                <?php else: ?>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="provider_id" value="<?php echo (int) $item['id']; ?>">
                                    <input type="hidden" name="provider_action" value="activate">
                                    <button type="submit" class="btn btn-sm btn-primary" title="Aktivieren">▶️</button>
                                </form>
                                <?php endif; ?>

                                <!-- Booking-Seite öffnen -->
                                <a href="/booking/<?php echo htmlspecialchars($item['slug']); ?>"
                                   target="_blank" class="btn btn-sm btn-secondary" title="Buchungsseite">🔗</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
        <div class="pagination" style="display:flex;gap:.5rem;justify-content:center;margin-top:1.5rem;">
            <?php if ($page > 1): ?>
                <a href="?section=providers&page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status); ?>&q=<?php echo urlencode($search); ?>" class="btn btn-secondary btn-sm">← Zurück</a>
            <?php endif; ?>
            <span style="padding:.375rem .875rem;color:#64748b;font-size:.875rem;">
                Seite <?php echo $page; ?> von <?php echo $pages; ?>
            </span>
            <?php if ($page < $pages): ?>
                <a href="?section=providers&page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status); ?>&q=<?php echo urlencode($search); ?>" class="btn btn-secondary btn-sm">Weiter →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
