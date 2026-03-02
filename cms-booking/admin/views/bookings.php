<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<!-- Section-Nav -->
<nav class="lp-section-nav">
    <a href="/admin/plugins/booking-dashboard/booking-dashboard" class="lp-section-nav__item">
        <span class="lp-section-nav__icon">📊</span> Dashboard
    </a>
    <a href="/admin/plugins/booking-dashboard/booking-bookings" class="lp-section-nav__item active">
        <span class="lp-section-nav__icon">📋</span> Buchungen
    </a>
    <a href="/admin/plugins/booking-dashboard/booking-providers" class="lp-section-nav__item">
        <span class="lp-section-nav__icon">👥</span> Anbieter
    </a>
    <a href="/admin/plugins/booking-dashboard/booking-services" class="lp-section-nav__item">
        <span class="lp-section-nav__icon">🛠️</span> Leistungen
    </a>
    <a href="/admin/plugins/booking-dashboard/booking-settings" class="lp-section-nav__item">
        <span class="lp-section-nav__icon">⚙️</span> Einstellungen
    </a>
</nav>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>📋 Buchungen verwalten</h2>
        <p>Alle Buchungsanfragen und Termine in der Übersicht</p>
    </div>
</div>

<!-- Alerts -->
<?php if (!empty($success)): ?>
<div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Filter-Bar -->
<div class="admin-card">
    <form method="GET" style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end;">
        <input type="hidden" name="section" value="bookings">

        <div class="form-group" style="margin:0;min-width:180px;">
            <label class="form-label">Status</label>
            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">Alle</option>
                <?php foreach ($statusLabels as $key => $label): ?>
                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $status === $key ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin:0;min-width:200px;">
            <label class="form-label">Anbieter</label>
            <select name="provider_id" class="form-control" onchange="this.form.submit()">
                <option value="">Alle Anbieter</option>
                <?php foreach ($providers as $prov): ?>
                <option value="<?php echo (int) $prov['id']; ?>" <?php echo $providerId === (int) $prov['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($prov['display_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin:0;flex:1;min-width:200px;">
            <label class="form-label">Suche</label>
            <input type="text" name="q" class="form-control" placeholder="Name, E-Mail …"
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <button type="submit" class="btn btn-secondary btn-sm">🔍 Filtern</button>
    </form>
</div>

<!-- Tabelle -->
<div class="admin-card">
    <?php if (empty($items)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0;">📭</p>
            <p><strong>Keine Buchungen gefunden</strong></p>
            <p class="text-muted">Passen Sie die Filter an oder warten Sie auf neue Buchungen.</p>
        </div>
    <?php else: ?>
        <div class="users-table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Kunde</th>
                        <th>Anbieter</th>
                        <th>Leistung</th>
                        <th>Datum / Zeit</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo (int) $item['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($item['customer_name']); ?></strong>
                            <div style="color:#64748b;font-size:.8rem;"><?php echo htmlspecialchars($item['customer_email']); ?></div>
                            <?php if (!empty($item['customer_phone'])): ?>
                            <div style="color:#94a3b8;font-size:.79rem;"><?php echo htmlspecialchars($item['customer_phone']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['provider_name'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($item['service_title'] ?? '—'); ?></td>
                        <td>
                            <?php echo date('d.m.Y', strtotime($item['booking_date'])); ?>
                            <div style="color:#64748b;font-size:.8rem;">
                                <?php echo substr($item['start_time'], 0, 5); ?> – <?php echo substr($item['end_time'], 0, 5); ?>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge <?php echo CMS_Booking_Bookings::status_badge_class($item['status']); ?>">
                                <?php echo htmlspecialchars($statusLabels[$item['status']] ?? $item['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                                <?php if ($item['status'] === 'pending'): ?>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="booking_id" value="<?php echo (int) $item['id']; ?>">
                                    <input type="hidden" name="booking_action" value="confirm">
                                    <button type="submit" class="btn btn-sm btn-primary" title="Bestätigen">✅</button>
                                </form>
                                <?php endif; ?>

                                <?php if (in_array($item['status'], ['pending', 'confirmed'], true)): ?>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="booking_id" value="<?php echo (int) $item['id']; ?>">
                                    <input type="hidden" name="booking_action" value="cancel">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Stornieren">❌</button>
                                </form>
                                <?php endif; ?>

                                <?php if ($item['status'] === 'confirmed'): ?>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="booking_id" value="<?php echo (int) $item['id']; ?>">
                                    <input type="hidden" name="booking_action" value="complete">
                                    <button type="submit" class="btn btn-sm btn-secondary" title="Abschließen">✔️</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginierung -->
        <?php if ($pages > 1): ?>
        <div class="pagination" style="display:flex;gap:.5rem;justify-content:center;margin-top:1.5rem;">
            <?php if ($page > 1): ?>
                <a href="?section=bookings&page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status); ?>&q=<?php echo urlencode($search); ?>" class="btn btn-secondary btn-sm">← Zurück</a>
            <?php endif; ?>
            <span style="padding:.375rem .875rem;color:#64748b;font-size:.875rem;">
                Seite <?php echo $page; ?> von <?php echo $pages; ?>
            </span>
            <?php if ($page < $pages): ?>
                <a href="?section=bookings&page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status); ?>&q=<?php echo urlencode($search); ?>" class="btn btn-secondary btn-sm">Weiter →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
