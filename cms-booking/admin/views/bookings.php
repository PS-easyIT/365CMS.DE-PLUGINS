<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<!-- Section-Nav -->
<nav class="lp-section-nav">
    <a href="/admin/plugins/booking/booking" class="lp-section-nav__item">
        <span class="lp-section-nav__icon">📊</span> Dashboard
    </a>
    <a href="/admin/plugins/booking/bookings" class="lp-section-nav__item active">
        <span class="lp-section-nav__icon">📋</span> Buchungen
    </a>
    <a href="/admin/plugins/booking/providers" class="lp-section-nav__item">
        <span class="lp-section-nav__icon">👥</span> Anbieter
    </a>
    <a href="/admin/plugins/booking/services" class="lp-section-nav__item">
        <span class="lp-section-nav__icon">🛠️</span> Leistungen
    </a>
    <a href="/admin/plugins/booking/settings" class="lp-section-nav__item">
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
    <form method="GET" class="booking-admin-filter-form">
        <input type="hidden" name="section" value="bookings">

        <div class="form-group booking-admin-filter-group booking-admin-filter-group--status">
            <label class="form-label">Status</label>
            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">Alle</option>
                <?php foreach ($statusLabels as $key => $label): ?>
                <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $status === $key ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group booking-admin-filter-group booking-admin-filter-group--provider">
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

        <div class="form-group booking-admin-filter-group booking-admin-filter-group--search">
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
                            <div class="booking-admin-muted"><?php echo htmlspecialchars($item['customer_email']); ?></div>
                            <?php if (!empty($item['customer_phone'])): ?>
                            <div class="booking-admin-muted booking-admin-muted--subtle"><?php echo htmlspecialchars($item['customer_phone']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['provider_name'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($item['service_title'] ?? '—'); ?></td>
                        <td>
                            <?php echo date('d.m.Y', strtotime($item['booking_date'])); ?>
                            <div class="booking-admin-muted">
                                <?php echo substr($item['start_time'], 0, 5); ?> – <?php echo substr($item['end_time'], 0, 5); ?>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge <?php echo CMS_Booking_Bookings::status_badge_class($item['status']); ?>">
                                <?php echo htmlspecialchars($statusLabels[$item['status']] ?? $item['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="booking-admin-actions">
                                <?php if ($item['status'] === 'pending'): ?>
                                <form method="POST" class="booking-admin-inline-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="booking_id" value="<?php echo (int) $item['id']; ?>">
                                    <input type="hidden" name="booking_action" value="confirm">
                                    <button type="submit" class="btn btn-sm btn-primary" title="Bestätigen">✅</button>
                                </form>
                                <?php endif; ?>

                                <?php if (in_array($item['status'], ['pending', 'confirmed'], true)): ?>
                                <form method="POST" class="booking-admin-inline-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="booking_id" value="<?php echo (int) $item['id']; ?>">
                                    <input type="hidden" name="booking_action" value="cancel">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Stornieren">❌</button>
                                </form>
                                <?php endif; ?>

                                <?php if ($item['status'] === 'confirmed'): ?>
                                <form method="POST" class="booking-admin-inline-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
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
        <div class="pagination booking-admin-pagination">
            <?php if ($page > 1): ?>
                <a href="?section=bookings&page=<?php echo (int) ($page - 1); ?>&status=<?php echo rawurlencode($status); ?>&q=<?php echo rawurlencode($search); ?>" class="btn btn-secondary btn-sm">← Zurück</a>
            <?php endif; ?>
            <span class="booking-admin-pagination-info">
                Seite <?php echo (int) $page; ?> von <?php echo (int) $pages; ?>
            </span>
            <?php if ($page < $pages): ?>
                <a href="?section=bookings&page=<?php echo (int) ($page + 1); ?>&status=<?php echo rawurlencode($status); ?>&q=<?php echo rawurlencode($search); ?>" class="btn btn-secondary btn-sm">Weiter →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
