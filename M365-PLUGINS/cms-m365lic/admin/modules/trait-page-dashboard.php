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
        $usage = self::repo()->get_usage_statistics(14);
        $usageSummary = is_array($usage['summary'] ?? null) ? $usage['summary'] : [];
        $usageTimeline = is_array($usage['timeline'] ?? null) ? $usage['timeline'] : [];
        $usagePeriods = is_array($usage['periods'] ?? null) ? $usage['periods'] : [];
        $usagePeriodOptions = is_array($usage['available_periods'] ?? null) ? $usage['available_periods'] : [7, 14, 30];
        $defaultUsagePeriod = (string) ($usage['default_period'] ?? 14);
        $usageTotals = is_array($usage['totals'] ?? null) ? $usage['totals'] : [];
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
                    <p class="m365lic-section-kicker">Nutzungs-Auswertung</p>
                    <h3>📈 Wie oft der Berater verwendet wurde</h3>
                    <p class="m365lic-help-text">Basis sind die bereits gespeicherten Auswertungs- und PDF-Hits aus dem Limit-Tracking.</p>
                </div>
                <div class="m365lic-toolbar__cluster">
                    <span class="m365lic-admin-badge">Gesamt Auswertungen: <?php echo number_format((int) ($usageTotals['evaluation_total'] ?? 0), 0, ',', '.'); ?></span>
                    <span class="m365lic-admin-badge">Gesamt PDF: <?php echo number_format((int) ($usageTotals['pdf_total'] ?? 0), 0, ',', '.'); ?></span>
                </div>
            </div>

            <div class="m365lic-usage-grid">
                <?php foreach (['public', 'member', 'group'] as $tierKey): ?>
                    <?php $tierStats = is_array($usageSummary[$tierKey] ?? null) ? $usageSummary[$tierKey] : []; ?>
                    <article class="m365lic-usage-card<?php echo $tierKey === 'member' ? ' m365lic-usage-card--accent' : ''; ?>">
                        <div class="m365lic-usage-card__head">
                            <div>
                                <p class="m365lic-section-kicker"><?php echo self::esc((string) ($tierStats['label'] ?? ucfirst($tierKey))); ?></p>
                                <h4><?php echo self::esc((string) ($tierStats['label'] ?? ucfirst($tierKey))); ?> Bereich</h4>
                            </div>
                            <span class="m365lic-admin-badge"><?php echo number_format((int) ($tierStats['evaluation_total'] ?? 0), 0, ',', '.'); ?> Auswertungen</span>
                        </div>

                        <div class="m365lic-usage-metrics">
                            <div class="m365lic-usage-metric">
                                <span>Heute</span>
                                <strong><?php echo number_format((int) ($tierStats['evaluation_today'] ?? 0), 0, ',', '.'); ?></strong>
                            </div>
                            <div class="m365lic-usage-metric">
                                <span>Letzte 30 Tage</span>
                                <strong><?php echo number_format((int) ($tierStats['evaluation_last_30_days'] ?? 0), 0, ',', '.'); ?></strong>
                            </div>
                            <div class="m365lic-usage-metric">
                                <span>PDF-Exports</span>
                                <strong><?php echo number_format((int) ($tierStats['pdf_total'] ?? 0), 0, ',', '.'); ?></strong>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="m365lic-usage-controls" data-m365lic-usage-controls>
                <div class="m365lic-usage-toggle-group" role="tablist" aria-label="Zeitraum wählen">
                    <?php foreach ($usagePeriodOptions as $periodOption): ?>
                        <?php $periodKey = (string) (int) $periodOption; ?>
                        <button
                            type="button"
                            class="m365lic-usage-toggle<?php echo $periodKey === $defaultUsagePeriod ? ' is-active' : ''; ?>"
                            data-usage-period-trigger="<?php echo self::esc($periodKey); ?>"
                            aria-pressed="<?php echo $periodKey === $defaultUsagePeriod ? 'true' : 'false'; ?>"
                        >
                            <?php echo (int) $periodOption; ?> Tage
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="m365lic-usage-toggle-group" role="tablist" aria-label="Metrik wählen">
                    <button type="button" class="m365lic-usage-toggle is-active" data-usage-mode-trigger="evaluation" aria-pressed="true">Auswertungen</button>
                    <button type="button" class="m365lic-usage-toggle" data-usage-mode-trigger="pdf_export" aria-pressed="false">PDF-Exporte</button>
                </div>
            </div>

            <div class="m365lic-usage-chart-card">
                <div class="m365lic-usage-chart-card__head">
                    <div>
                        <h4>📊 Public vs. Member als Balken</h4>
                        <p class="m365lic-help-text">Der Zeitraum und die Metrik lassen sich oben live umschalten.</p>
                    </div>
                    <div class="m365lic-usage-legend" aria-label="Legende">
                        <span class="m365lic-usage-legend__item"><span class="m365lic-usage-legend__swatch m365lic-usage-legend__swatch--public"></span>Public</span>
                        <span class="m365lic-usage-legend__item"><span class="m365lic-usage-legend__swatch m365lic-usage-legend__swatch--member"></span>Member</span>
                        <span class="m365lic-usage-legend__item"><span class="m365lic-usage-legend__swatch m365lic-usage-legend__swatch--group"></span>Spezial</span>
                    </div>
                </div>

                <?php foreach ($usagePeriodOptions as $periodOption): ?>
                    <?php
                    $periodKey = (string) (int) $periodOption;
                    $periodRows = is_array($usagePeriods[$periodKey] ?? null) ? $usagePeriods[$periodKey] : [];
                    ?>
                    <?php foreach (['evaluation' => 'Auswertungen', 'pdf_export' => 'PDF-Exporte'] as $modeKey => $modeLabel): ?>
                        <?php
                        $isActivePanel = $periodKey === $defaultUsagePeriod && $modeKey === 'evaluation';
                        $periodTotals = [
                            'public' => 0,
                            'member' => 0,
                            'group' => 0,
                            'total' => 0,
                        ];
                        $maxHits = 0;

                        foreach ($periodRows as $dayRow) {
                            $metricRow = is_array($dayRow[$modeKey] ?? null) ? $dayRow[$modeKey] : [];
                            $periodTotals['public'] += (int) ($metricRow['public'] ?? 0);
                            $periodTotals['member'] += (int) ($metricRow['member'] ?? 0);
                            $periodTotals['group'] += (int) ($metricRow['group'] ?? 0);
                            $periodTotals['total'] += (int) ($metricRow['total'] ?? 0);
                            $maxHits = max($maxHits, (int) ($metricRow['public'] ?? 0), (int) ($metricRow['member'] ?? 0), (int) ($metricRow['group'] ?? 0), (int) ($metricRow['total'] ?? 0));
                        }

                        if ($maxHits < 1) {
                            $maxHits = 1;
                        }
                        ?>
                        <section
                            class="m365lic-usage-panel<?php echo $isActivePanel ? ' is-active' : ''; ?>"
                            data-usage-period-panel="<?php echo self::esc($periodKey); ?>"
                            data-usage-mode-panel="<?php echo self::esc($modeKey); ?>"
                            <?php echo $isActivePanel ? '' : 'hidden'; ?>
                        >
                            <div class="m365lic-usage-period-summary">
                                <div class="m365lic-usage-period-summary__item">
                                    <span>Public</span>
                                    <strong><?php echo number_format((int) $periodTotals['public'], 0, ',', '.'); ?></strong>
                                </div>
                                <div class="m365lic-usage-period-summary__item">
                                    <span>Member</span>
                                    <strong><?php echo number_format((int) $periodTotals['member'], 0, ',', '.'); ?></strong>
                                </div>
                                <div class="m365lic-usage-period-summary__item">
                                    <span>Spezial</span>
                                    <strong><?php echo number_format((int) $periodTotals['group'], 0, ',', '.'); ?></strong>
                                </div>
                                <div class="m365lic-usage-period-summary__item m365lic-usage-period-summary__item--total">
                                    <span>Gesamt</span>
                                    <strong><?php echo number_format((int) $periodTotals['total'], 0, ',', '.'); ?></strong>
                                </div>
                            </div>

                            <?php if ($periodTotals['total'] === 0): ?>
                                <div class="empty-state m365lic-usage-empty">
                                    <p class="m365lic-empty-state__icon">📉</p>
                                    <p><strong>Keine <?php echo self::esc(mb_strtolower($modeLabel)); ?> im Zeitraum</strong></p>
                                    <p class="m365lic-empty-state__text">Für die letzten <?php echo (int) $periodOption; ?> Tage sind aktuell keine Einträge vorhanden.</p>
                                </div>
                            <?php else: ?>
                                <div class="m365lic-usage-chart" aria-label="<?php echo self::esc($modeLabel . ' der letzten ' . (int) $periodOption . ' Tage'); ?>">
                                    <?php foreach ($periodRows as $dayRow): ?>
                                        <?php $metricRow = is_array($dayRow[$modeKey] ?? null) ? $dayRow[$modeKey] : []; ?>
                                        <div class="m365lic-usage-chart__row">
                                            <div class="m365lic-usage-chart__label"><?php echo self::esc((string) ($dayRow['date_label'] ?? '')); ?></div>
                                            <div class="m365lic-usage-chart__bars">
                                                <?php foreach (['public', 'member', 'group'] as $barTier): ?>
                                                    <?php
                                                    $barValue = (int) ($metricRow[$barTier] ?? 0);
                                                    $barWidth = $barValue > 0 ? max(4, (int) round(($barValue / $maxHits) * 100)) : 0;
                                                    ?>
                                                    <div class="m365lic-usage-chart__bar-row">
                                                        <span class="m365lic-usage-chart__bar-name"><?php echo self::esc($usageSummary[$barTier]['label'] ?? ucfirst($barTier)); ?></span>
                                                        <div class="m365lic-usage-chart__track">
                                                            <span class="m365lic-usage-chart__bar m365lic-usage-chart__bar--<?php echo self::esc($barTier); ?>" style="width: <?php echo $barWidth; ?>%;"></span>
                                                        </div>
                                                        <strong class="m365lic-usage-chart__value"><?php echo number_format($barValue, 0, ',', '.'); ?></strong>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="m365lic-usage-table-wrap">
                                <table class="users-table m365lic-usage-table">
                                    <thead>
                                        <tr>
                                            <th>Tag</th>
                                            <th>Public</th>
                                            <th>Member</th>
                                            <th>Spezial</th>
                                            <th>Gesamt</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($periodRows as $day): ?>
                                            <?php $metricRow = is_array($day[$modeKey] ?? null) ? $day[$modeKey] : []; ?>
                                            <tr>
                                                <td><strong><?php echo self::esc((string) ($day['date_label'] ?? '')); ?></strong></td>
                                                <td><?php echo number_format((int) ($metricRow['public'] ?? 0), 0, ',', '.'); ?></td>
                                                <td><?php echo number_format((int) ($metricRow['member'] ?? 0), 0, ',', '.'); ?></td>
                                                <td><?php echo number_format((int) ($metricRow['group'] ?? 0), 0, ',', '.'); ?></td>
                                                <td><strong><?php echo number_format((int) ($metricRow['total'] ?? 0), 0, ',', '.'); ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>

            <p class="m365lic-admin-note m365lic-admin-note--compact">
                Die Zahlen zeigen das vorhandene Tracking seit Nutzung der Limits-Tabelle – also ohne Zeitmaschine, aber mit brauchbarem Realitätsbezug.
            </p>
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
                    <p class="m365lic-empty-state__icon">📭</p>
                    <p><strong>Keine Pakete gefunden</strong></p>
                    <p class="m365lic-empty-state__text">Bitte den Seed-Katalog in der Paketverwaltung neu aufbauen.</p>
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
                            <?php $effectivePrices = self::repo()->get_effective_price_map($package); ?>
                            <tr>
                                <td><?php echo self::esc((string) $package['name']); ?></td>
                                <td><?php echo self::esc((string) $package['kind']); ?></td>
                                <td><?php echo self::esc((string) (($package['pricing_basis'] ?? 'per_user') === 'flat_monthly' ? 'Fixpreis' : 'pro Benutzer')); ?></td>
                                <td><?php echo ($effectivePrices['public_price']['value'] ?? null) !== null ? self::esc(number_format((float) $effectivePrices['public_price']['value'], 2, ',', '.')) . ' €' : '—'; ?></td>
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
