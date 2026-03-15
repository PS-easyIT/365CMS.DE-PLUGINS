<?php
/**
 * CMS M365 License – Dashboard Page
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_M365LIC_Page_Dashboard_Trait
{
    public function render_dashboard_page(): void
    {
        $stats = self::repo()->get_statistics();
        $settings = self::repo()->get_settings();
        $packages = array_slice(self::repo()->get_packages(false), 0, 8);
        ?>
        <div class="admin-page-header">
            <div>
                <h2>📊 M365 Lizenzberater</h2>
                <p>Überblick über Paketkatalog, Bereichslogik, Spezialzugänge und Preisabdeckung.</p>
            </div>
            <div class="header-actions">
                <a href="?page=m365lic-packages" class="btn btn-primary">📦 Pakete verwalten</a>
            </div>
        </div>

        <div class="m365lic-stat-grid">
            <div class="m365lic-stat-card">
                <div class="m365lic-stat-card__value"><?php echo (int) $stats['packages_total']; ?></div>
                <div class="m365lic-stat-card__label">Pakete gesamt</div>
            </div>
            <div class="m365lic-stat-card">
                <div class="m365lic-stat-card__value"><?php echo (int) $stats['packages_active']; ?></div>
                <div class="m365lic-stat-card__label">Aktive Pakete</div>
            </div>
            <div class="m365lic-stat-card">
                <div class="m365lic-stat-card__value"><?php echo (int) $stats['packages_with_prices']; ?></div>
                <div class="m365lic-stat-card__label">Mit Preis</div>
            </div>
            <div class="m365lic-stat-card">
                <div class="m365lic-stat-card__value"><?php echo (int) $stats['packages_without_prices']; ?></div>
                <div class="m365lic-stat-card__label">Ohne Preis</div>
            </div>
            <div class="m365lic-stat-card">
                <div class="m365lic-stat-card__value"><?php echo (int) $stats['special_users_total']; ?></div>
                <div class="m365lic-stat-card__label">Spezial-User</div>
            </div>
            <div class="m365lic-stat-card">
                <div class="m365lic-stat-card__value"><?php echo (int) $stats['preset_total']; ?></div>
                <div class="m365lic-stat-card__label">Bedarfs-Presets</div>
            </div>
        </div>

        <div class="m365lic-admin-grid">
            <div class="admin-card">
                <h3>🧭 Bereiche & Default-Abrechnung</h3>
                <ul class="m365lic-admin-list">
                    <li><strong>Route:</strong> /<?php echo self::esc((string) ($settings['route_slug'] ?? 'm365-lizenzberater')); ?></li>
                    <li><strong>Public Default:</strong> <?php echo self::esc((string) ($settings['public_default_billing_cycle'] ?? 'annual_upfront')); ?></li>
                    <li><strong>Member Default:</strong> <?php echo self::esc((string) ($settings['member_default_billing_cycle'] ?? 'annual_monthly')); ?></li>
                    <li><strong>Spezial Default:</strong> <?php echo self::esc((string) ($settings['group_default_billing_cycle'] ?? 'annual_monthly')); ?></li>
                    <li><strong>Spezial-Label:</strong> <?php echo self::esc((string) ($settings['default_group_label'] ?? 'Partner / Spezialgruppe')); ?></li>
                </ul>
            </div>
            <div class="admin-card">
                <h3>🚦 Limits</h3>
                <ul class="m365lic-admin-list">
                    <li><strong>Public Tageslimit:</strong> <?php echo (int) ($settings['public_daily_limit'] ?? 2); ?></li>
                    <li><strong>Member Tageslimit:</strong> <?php echo (int) ($settings['member_daily_limit'] ?? 10); ?></li>
                    <li><strong>Spezial Tageslimit:</strong> <?php echo (int) ($settings['group_daily_limit'] ?? 25); ?></li>
                    <li><strong>Public PDF:</strong> <?php echo (int) ($settings['public_pdf_daily_limit'] ?? 2); ?></li>
                    <li><strong>Member PDF:</strong> <?php echo (int) ($settings['member_pdf_daily_limit'] ?? 10); ?></li>
                    <li><strong>Spezial PDF:</strong> <?php echo (int) ($settings['group_pdf_daily_limit'] ?? 25); ?></li>
                </ul>
            </div>
        </div>

        <div class="admin-card">
            <h3>📦 Erste aktive Pakete</h3>
            <?php if (empty($packages)): ?>
                <div class="empty-state">
                    <p style="font-size:2.5rem;margin:0;">📭</p>
                    <p><strong>Keine Pakete gefunden</strong></p>
                    <p style="color:#64748b;font-size:.875rem;">Bitte den Seed-Katalog in der Paketverwaltung neu aufbauen.</p>
                </div>
            <?php else: ?>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Typ</th>
                                <th>Abrechnung</th>
                                <th>Preis Public</th>
                                <th>Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($packages as $package): ?>
                            <tr>
                                <td><?php echo self::esc((string) $package['name']); ?></td>
                                <td><?php echo self::esc((string) $package['kind']); ?></td>
                                <td><?php echo self::esc((string) (($package['pricing_basis'] ?? 'per_user') === 'flat_monthly' ? 'Fixpreis' : 'pro Benutzer')); ?></td>
                                <td><?php echo $package['public_price'] !== null ? self::esc(number_format((float) $package['public_price'], 2, ',', '.')) . ' ' . self::esc((string) ($package['currency'] ?? 'USD')) : '—'; ?></td>
                                <td>
                                    <a href="?page=m365lic-packages&edit=<?php echo (int) $package['id']; ?>" class="btn btn-secondary btn-sm">✏️ Bearbeiten</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
