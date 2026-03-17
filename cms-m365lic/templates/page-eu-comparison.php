<?php
/**
 * Public Template: EU-Vergleich für M365
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(?string $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$formatMoney = static function ($value) use ($esc): string {
    if ($value === null || $value === '') {
        return 'offen';
    }

    return $esc(number_format((float) $value, 2, ',', '.')) . ' €';
};
$theme = \CMS\ThemeManager::instance();
$themeTitle = (string) ($euPageTitle ?? 'EU-Vergleich · Microsoft 365 vs. europäische Anbieter');
$introText = (string) ($euPageIntro ?? '');
$publicBaseUrl = '/' . trim((string) ($settings['route_slug'] ?? 'm365-lizenzberater'), '/');
$euPageUrl = $publicBaseUrl . '/eu-vergleich';
$isMonthlyRuntime = (string) ($selectedBilling['key'] ?? 'annual_upfront') === 'monthly_flex';
?>
<?php $theme->getHeader(['title' => $themeTitle]); ?>

<style>
:root {
    --m365lic-primary: <?php echo $esc((string) ($settings['design_primary_color'] ?? '#2563eb')); ?>;
    --m365lic-primary-dark: <?php echo $esc((string) ($settings['design_primary_dark'] ?? '#1d4ed8')); ?>;
    --m365lic-hero-bg: <?php echo $esc((string) ($settings['design_accent_color'] ?? '#f3f7fd')); ?>;
    --m365lic-bg: <?php echo $esc((string) ($settings['design_page_background'] ?? '#f8fafc')); ?>;
    --m365lic-surface: <?php echo $esc((string) ($settings['design_surface_color'] ?? '#ffffff')); ?>;
    --m365lic-text: <?php echo $esc((string) ($settings['design_text_color'] ?? '#0f172a')); ?>;
    --m365lic-text-muted: <?php echo $esc((string) ($settings['design_text_muted_color'] ?? '#64748b')); ?>;
    --m365lic-radius: <?php echo (int) ($settings['design_border_radius'] ?? 14); ?>px;
}
</style>

<main class="m365lic-main m365lic-page m365lic-page--eu-compare">
    <header class="m365lic-hero">
        <div class="m365lic-container">
            <div class="m365lic-hero-grid">
                <div>
                    <span class="m365lic-eyebrow">EU-Alternativen · Microsoft 365 Vergleich</span>
                    <h1><?php echo $esc($themeTitle); ?></h1>
                    <p><?php echo $esc($introText); ?></p>
                    <div class="m365lic-hero-pills">
                        <span class="m365lic-pill">Öffentliche Preise</span>
                        <span class="m365lic-pill"><?php echo $esc((string) ($selectedBilling['short_label'] ?? 'Jahr / jährlich')); ?></span>
                        <span class="m365lic-pill">Europa-Fokus</span>
                    </div>
                </div>
                <div class="m365lic-hero-panel">
                    <div class="m365lic-hero-panel__kicker">Vergleichsmodi</div>
                    <ul class="m365lic-hero-list">
                        <li>All-in-One-Workspace als direkter Kernersatz</li>
                        <li>Best-of-Breed-Stack mit europäischen Spezialtools</li>
                        <li>Summenvergleich M365 vs. EU-Stack pro Nutzeranzahl</li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <section class="m365lic-section">
        <div class="m365lic-container">
            <?php if ($notice !== ''): ?>
            <div class="m365lic-alert m365lic-alert--success">✅ <?php echo $esc($notice); ?></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
            <div class="m365lic-alert m365lic-alert--error">❌ <?php echo $esc($error); ?></div>
            <?php endif; ?>

            <div class="m365lic-card m365lic-card--intro">
                <div class="m365lic-card__head">
                    <div>
                        <h2>Vergleich vorbereiten</h2>
                        <p>Wähle einen Referenzplan aus Microsoft 365 und stelle gegenüber, ob du lieber einen europäischen Komplettanbieter oder einen modularen EU-Stack dagegenhalten möchtest.</p>
                    </div>
                    <div class="m365lic-card-badge">Preise pro Nutzer / Monat</div>
                </div>

                <nav class="m365lic-local-nav" aria-label="Public M365 Menü">
                    <a href="<?php echo $esc($publicBaseUrl); ?>" class="m365lic-local-nav__link">🧮 Auswertung</a>
                    <a href="<?php echo $esc($euPageUrl); ?>" class="m365lic-local-nav__link m365lic-local-nav__link--active" aria-current="page">🇪🇺 EU-Vergleich</a>
                </nav>

                <form method="POST" class="m365lic-form m365lic-eu-form">
                    <section class="m365lic-subcard m365lic-subcard--billing">
                        <div class="m365lic-subcard__head">
                            <div>
                                <h3>Referenz & Modell</h3>
                                <p>Vergleiche den gewählten Microsoft-365-Plan mit europäischen Alternativen auf Basis derselben Nutzerzahl und Laufzeit.</p>
                            </div>
                        </div>

                        <div class="m365lic-form-grid m365lic-form-grid--2">
                            <div class="m365lic-field">
                                <label for="eu_m365_plan_slug">M365-Referenzplan</label>
                                <select id="eu_m365_plan_slug" name="m365_plan_slug">
                                    <?php foreach ($planProfiles as $planSlug => $planProfile): ?>
                                    <option value="<?php echo $esc($planSlug); ?>" <?php echo $selectedPlanSlug === $planSlug ? 'selected' : ''; ?>><?php echo $esc((string) ($planProfile['label'] ?? $planSlug)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="m365lic-help-text"><?php echo $esc((string) ($planProfile['description'] ?? '')); ?></small>
                            </div>
                            <div class="m365lic-field">
                                <label for="eu_quantity">Anzahl Nutzer</label>
                                <input id="eu_quantity" type="number" min="1" max="5000" name="quantity" value="<?php echo (int) $quantity; ?>">
                                <small class="m365lic-help-text">Alle Vergleichssummen werden mit dieser Nutzerzahl multipliziert.</small>
                            </div>
                        </div>

                        <div class="m365lic-form-grid m365lic-form-grid--2">
                            <div class="m365lic-field">
                                <label for="eu_billing_cycle">Laufzeit & Zahlung</label>
                                <select id="eu_billing_cycle" name="billing_cycle">
                                    <?php foreach ($billingOptions as $billingKey => $billingOption): ?>
                                    <option value="<?php echo $esc($billingKey); ?>" <?php echo (($selectedBilling['key'] ?? '') === $billingKey) ? 'selected' : ''; ?>><?php echo $esc((string) ($billingOption['label'] ?? $billingKey)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="m365lic-help-text"><?php echo $esc((string) ($selectedBilling['note'] ?? '')); ?></small>
                            </div>
                            <div class="m365lic-field">
                                <label for="eu_strategy">Vergleichsmodus</label>
                                <select id="eu_strategy" name="eu_strategy">
                                    <option value="best_of_breed" <?php echo $strategy === 'best_of_breed' ? 'selected' : ''; ?>>Best-of-Breed Stack</option>
                                    <option value="all_in_one" <?php echo $strategy === 'all_in_one' ? 'selected' : ''; ?>>All-in-One Workspace</option>
                                </select>
                                <small class="m365lic-help-text">Best-of-Breed kombiniert Core-Workspace mit optionalen europäischen Spezialtools.</small>
                            </div>
                        </div>
                    </section>

                    <section class="m365lic-subcard m365lic-subcard--requirements m365lic-subcard--eu-options">
                        <div class="m365lic-subcard__head">
                            <div>
                                <h3>Europäische Anbieter auswählen</h3>
                                <p>Der Core-Workspace ist immer aktiv. Weitere Kategorien kannst du für den modularen EU-Stack gezielt ein- oder ausschalten.</p>
                            </div>
                        </div>

                        <div class="m365lic-eu-category-grid">
                            <?php foreach ($euCategories as $categoryKey => $categoryMeta): ?>
                            <?php $offersForCategory = $euOffers[$categoryKey] ?? []; ?>
                            <article class="m365lic-eu-category-card<?php echo $categoryKey === 'core_workspace' ? ' m365lic-eu-category-card--core' : ''; ?>">
                                <div class="m365lic-eu-category-card__head">
                                    <div>
                                        <h4><?php echo $esc((string) ($categoryMeta['label'] ?? $categoryKey)); ?></h4>
                                        <p><?php echo $esc((string) ($categoryMeta['description'] ?? '')); ?></p>
                                    </div>
                                    <?php if ($categoryKey === 'core_workspace'): ?>
                                    <span class="m365lic-pill">Pflicht</span>
                                    <?php else: ?>
                                    <label class="m365lic-checkbox-inline">
                                        <input type="checkbox" name="eu_include[<?php echo $esc($categoryKey); ?>]" value="1" <?php echo !empty($includedCategories[$categoryKey]) ? 'checked' : ''; ?>>
                                        <span>Im Stack berücksichtigen</span>
                                    </label>
                                    <?php endif; ?>
                                </div>

                                <div class="m365lic-field">
                                    <label for="eu_selection_<?php echo $esc($categoryKey); ?>">Anbieter</label>
                                    <select id="eu_selection_<?php echo $esc($categoryKey); ?>" name="eu_selection[<?php echo $esc($categoryKey); ?>]">
                                        <?php foreach ($offersForCategory as $offer): ?>
                                        <option value="<?php echo $esc((string) ($offer['slug'] ?? '')); ?>" <?php echo (($selectedEuProviders[$categoryKey] ?? '') === ($offer['slug'] ?? '')) ? 'selected' : ''; ?>>
                                            <?php echo $esc((string) ($offer['provider'] ?? '')); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <ul class="m365lic-note-list m365lic-note-list--compact">
                                    <?php foreach ($offersForCategory as $offer): ?>
                                    <li>
                                        <strong><?php echo $esc((string) ($offer['provider'] ?? '')); ?></strong> ·
                                        Jahr: <?php echo $formatMoney($offer['annual_price'] ?? null); ?> ·
                                        Monat: <?php echo $formatMoney($offer['monthly_price'] ?? null); ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </article>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <div class="m365lic-actions">
                        <button type="submit" class="m365lic-btn m365lic-btn--primary">🇪🇺 Vergleich berechnen</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <?php if (is_array($comparisonResult ?? null)): ?>
    <section class="m365lic-section m365lic-section--soft">
        <div class="m365lic-container">
            <div class="m365lic-card">
                <div class="m365lic-card__head">
                    <div>
                        <h2>Vergleichsauswertung</h2>
                        <p>M365 links, europäischer Alternativ-Stack rechts – inklusive Gesamtkosten und Delta.</p>
                    </div>
                    <div class="m365lic-totals">
                        <span class="m365lic-total-chip"><?php echo $esc((string) ($comparisonResult['alternative']['strategy_label'] ?? 'EU-Stack')); ?></span>
                        <span class="m365lic-total-chip"><?php echo $esc((string) ($selectedBilling['short_label'] ?? 'Jahr / jährlich')); ?></span>
                        <span class="m365lic-total-chip"><?php echo (int) $quantity; ?> Nutzer</span>
                    </div>
                </div>

                <div class="m365lic-compare-grid">
                    <section class="m365lic-compare-panel">
                        <div class="m365lic-compare-panel__head">
                            <h3>M365</h3>
                            <p><?php echo $esc((string) ($comparisonResult['m365']['description'] ?? '')); ?></p>
                        </div>

                        <div class="users-table-container">
                            <table class="m365lic-table m365lic-table--compare">
                                <thead>
                                    <tr>
                                        <th>Plan</th>
                                        <th>Umfang</th>
                                        <th>Preis</th>
                                        <th>Gesamt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <strong><?php echo $esc((string) ($comparisonResult['m365']['name'] ?? 'Microsoft 365')); ?></strong>
                                            <div class="m365lic-muted"><?php echo $esc((string) ($comparisonResult['m365']['billing_cycle_label'] ?? '')); ?></div>
                                        </td>
                                        <td>
                                            <?php foreach (($comparisonResult['m365']['included_categories'] ?? []) as $includedCategory): ?>
                                            <div class="m365lic-muted"><?php echo $esc((string) $includedCategory); ?></div>
                                            <?php endforeach; ?>
                                        </td>
                                        <td><?php echo $formatMoney($comparisonResult['m365']['unit_price'] ?? null); ?></td>
                                        <td><?php echo $formatMoney($comparisonResult['m365']['line_total'] ?? null); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="m365lic-compare-panel">
                        <div class="m365lic-compare-panel__head">
                            <h3>Europäische Alternativen</h3>
                            <p><?php echo $esc((string) ($comparisonResult['alternative']['strategy_label'] ?? 'EU-Stack')); ?> für denselben Nutzerumfang.</p>
                        </div>

                        <div class="users-table-container">
                            <table class="m365lic-table m365lic-table--compare">
                                <thead>
                                    <tr>
                                        <th>Kategorie</th>
                                        <th>Anbieter</th>
                                        <th>Fokus</th>
                                        <th>Preis</th>
                                        <th>Gesamt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (($comparisonResult['alternative']['rows'] ?? []) as $row): ?>
                                    <tr>
                                        <td><strong><?php echo $esc((string) ($row['category_label'] ?? '')); ?></strong></td>
                                        <td><?php echo $esc((string) ($row['provider'] ?? '')); ?></td>
                                        <td><?php echo $esc((string) ($row['focus'] ?? '')); ?></td>
                                        <td><?php echo $formatMoney($row['unit_price'] ?? null); ?></td>
                                        <td><?php echo $formatMoney($row['line_total'] ?? null); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div class="m365lic-summary-grid m365lic-summary-grid--compare">
                    <div class="m365lic-summary-card">
                        <span>Gesamtkosten M365</span>
                        <strong><?php echo $formatMoney($comparisonResult['m365']['line_total'] ?? null); ?></strong>
                    </div>
                    <div class="m365lic-summary-card">
                        <span>Gesamtkosten Alternativen</span>
                        <strong><?php echo $formatMoney($comparisonResult['alternative']['line_total'] ?? null); ?></strong>
                    </div>
                    <div class="m365lic-summary-card">
                        <span>Delta Alternativen – M365</span>
                        <strong><?php echo $formatMoney($comparisonResult['delta'] ?? null); ?></strong>
                    </div>
                </div>

                <?php if (!empty($comparisonResult['alternative']['has_missing_prices'])): ?>
                <div class="m365lic-alert m365lic-alert--warning">
                    ⚠️ Für mindestens einen ausgewählten europäischen Anbieter fehlt für das gewählte Laufzeitmodell ein Preis. Die Alternativen-Gesamtsumme bleibt deshalb offen.
                </div>
                <?php endif; ?>

                <div class="m365lic-alert m365lic-alert--warning">
                    💡 <?php echo $isMonthlyRuntime ? 'Monatslaufzeit nutzt direkt die gepflegten Monatswerte der EU-Anbieter.' : 'Jahresmodelle nutzen direkt die gepflegten Jahreswerte der EU-Anbieter.'; ?>
                    Für den Best-of-Breed-Stack ist der Core-Workspace die Basis; zusätzliche Kategorien werden nur gezählt, wenn sie im Stack aktiv sind.
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php $theme->getFooter(); ?>