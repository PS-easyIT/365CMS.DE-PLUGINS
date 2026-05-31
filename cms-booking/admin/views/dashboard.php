<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>📅 Booking Dashboard</h2>
        <p>Gesamtübersicht aller Buchungen und Anbieter</p>
    </div>
</div>

<!-- Stat Cards -->
<div class="dashboard-grid">
    <div class="stat-card">
        <h3>Buchungen gesamt</h3>
        <div class="stat-number"><?php echo (int) $stats['total']; ?></div>
        <div class="stat-label">Alle Buchungen</div>
    </div>
    <div class="stat-card">
        <h3>Offene Anfragen</h3>
        <div class="stat-number"><?php echo (int) $stats['pending']; ?></div>
        <div class="stat-label">Warten auf Bestätigung</div>
    </div>
    <div class="stat-card">
        <h3>Bestätigt</h3>
        <div class="stat-number"><?php echo (int) $stats['confirmed']; ?></div>
        <div class="stat-label">Anstehende Termine</div>
    </div>
    <div class="stat-card">
        <h3>Heute</h3>
        <div class="stat-number"><?php echo (int) $stats['today']; ?></div>
        <div class="stat-label">Termine heute</div>
    </div>
    <div class="stat-card">
        <h3>Diese Woche</h3>
        <div class="stat-number"><?php echo (int) $stats['week']; ?></div>
        <div class="stat-label">Nächste 7 Tage</div>
    </div>
    <div class="stat-card">
        <h3>Anbieter</h3>
        <div class="stat-number"><?php echo (int) $providers; ?></div>
        <div class="stat-label">Aktiv registriert</div>
    </div>
</div>

<!-- Registrierte Provider-Typen -->
<?php if (!empty($types)): ?>
<div class="admin-card">
    <h3>🔌 Registrierte Integrationen</h3>
    <div style="display:flex;flex-wrap:wrap;gap:1rem;margin-top:1rem;">
        <?php foreach ($types as $slug => $type): ?>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1rem 1.25rem;min-width:200px;">
            <div style="font-size:1.5rem;margin-bottom:.25rem;"><?php echo htmlspecialchars((string) ($type['icon'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
            <strong><?php echo htmlspecialchars((string) ($type['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
            <div style="color:#64748b;font-size:.85rem;"><?php echo htmlspecialchars($slug); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Letzte Buchungen -->
<div class="admin-card">
    <h3>📋 Letzte Buchungen</h3>
    <?php if (empty($recent)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0;">📭</p>
            <p><strong>Noch keine Buchungen vorhanden</strong></p>
            <p class="text-muted">Sobald Besucher Termine buchen, erscheinen diese hier.</p>
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
                        <th>Datum</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $item): ?>
                    <tr>
                        <td><?php echo (int) $item['id']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($item['customer_name']); ?>
                            <div style="color:#64748b;font-size:.8rem;"><?php echo htmlspecialchars($item['customer_email']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($item['provider_name'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($item['service_title'] ?? '—'); ?></td>
                        <td>
                            <?php echo htmlspecialchars((string) date('d.m.Y', strtotime((string) $item['booking_date'])), ENT_QUOTES, 'UTF-8'); ?>
                            <div style="color:#64748b;font-size:.8rem;">
                                <?php echo htmlspecialchars((string) substr((string) $item['start_time'], 0, 5), ENT_QUOTES, 'UTF-8'); ?> – <?php echo htmlspecialchars((string) substr((string) $item['end_time'], 0, 5), ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge <?php echo htmlspecialchars(CMS_Booking_Bookings::status_badge_class((string) $item['status']), ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars(CMS_Booking_Bookings::status_labels()[$item['status']] ?? $item['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:1rem;">
            <a href="<?php echo htmlspecialchars(CMS_Booking_Admin_Pages::admin_url('bookings'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary btn-sm">📋 Alle Buchungen ansehen</a>
        </div>
    <?php endif; ?>
</div>
