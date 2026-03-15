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
        $billingOptions = CMS_M365LIC_Catalog::billing_options();
        $currency = (string) ($settings['default_currency'] ?? 'EUR');
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
            <div class="m365lic-stat-card m365lic-stat-card--accent">
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
                <p class="m365lic-section-kicker">Konfiguration</p>
                <h3>🧭 Bereiche & Default-Abrechnung</h3>
                <div class="m365lic-admin-panel">
                    <span class="m365lic-admin-badge">Währung: <?php echo self::esc($currency); ?> · Euro</span>
                    <div class="m365lic-metric-list">
                        <div class="m365lic-metric-row">
                            <div>
                                <strong>Route</strong>
                                <span>/<?php echo self::esc((string) ($settings['route_slug'] ?? 'm365-lizenzberater')); ?></span>
                            </div>
                            <span class="m365lic-admin-badge">Live</span>
                        </div>
                        <div class="m365lic-metric-row">
                            <div>
                                <strong>Public</strong>
                                <span><?php echo self::esc((string) (($billingOptions[(string) ($settings['public_default_billing_cycle'] ?? 'annual_upfront')]['label'] ?? '1 Jahr · jährliche Zahlung'))); ?></span>
                            </div>
                        </div>
                        <div class="m365lic-metric-row">
                            <div>
                                <strong>Member</strong>
                                <span><?php echo self::esc((string) (($billingOptions[(string) ($settings['member_default_billing_cycle'] ?? 'annual_monthly')]['label'] ?? '1 Jahr · monatliche Zahlung (+5%)'))); ?></span>
                            </div>
                        </div>
                        <div class="m365lic-metric-row">
                            <div>
                                <strong>Spezial</strong>
                                <span><?php echo self::esc((string) (($billingOptions[(string) ($settings['group_default_billing_cycle'] ?? 'annual_monthly')]['label'] ?? '1 Jahr · monatliche Zahlung (+5%)'))); ?></span>
                            </div>
                        </div>
                        <div class="m365lic-metric-row">
                            <div>
                                <strong>Spezial-Label</strong>
                                <span><?php echo self::esc((string) ($settings['default_group_label'] ?? 'Partner / Spezialgruppe')); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="admin-card">
                <p class="m365lic-section-kicker">Nutzung</p>
                <h3>🚦 Limits</h3>
                <div class="m365lic-metric-list">
                    <div class="m365lic-metric-row"><strong>Public Auswertungen/Tag</strong><span><?php echo (int) ($settings['public_daily_limit'] ?? 2); ?></span></div>
                    <div class="m365lic-metric-row"><strong>Member Auswertungen/Tag</strong><span><?php echo (int) ($settings['member_daily_limit'] ?? 10); ?></span></div>
                    <div class="m365lic-metric-row"><strong>Spezial Auswertungen/Tag</strong><span><?php echo (int) ($settings['group_daily_limit'] ?? 25); ?></span></div>
                    <div class="m365lic-metric-row"><strong>Public PDF/Tag</strong><span><?php echo (int) ($settings['public_pdf_daily_limit'] ?? 2); ?></span></div>
                    <div class="m365lic-metric-row"><strong>Member PDF/Tag</strong><span><?php echo (int) ($settings['member_pdf_daily_limit'] ?? 10); ?></span></div>
                    <div class="m365lic-metric-row"><strong>Spezial PDF/Tag</strong><span><?php echo (int) ($settings['group_pdf_daily_limit'] ?? 25); ?></span></div>
                </div>
            </div>
        </div>

        <div class="admin-card">
                <div class="m365lic-toolbar">
                    <div>
                        <p class="m365lic-section-kicker">Katalog</p>
                        <h3>📦 Erste aktive Pakete</h3>
                    </div>
                    <span class="m365lic-admin-badge">Alle Preisangaben in EUR</span>
                </div>
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
                                <td><?php echo $package['public_price'] !== null ? self::esc(number_format((float) $package['public_price'], 2, ',', '.')) . ' €' : '—'; ?></td>
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
