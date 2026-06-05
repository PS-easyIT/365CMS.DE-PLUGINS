<?php
/**
 * Public Template: Microsoft-Preiserhöhung-Tracker.
 *
 * @package CMS_M365PRICETRACKER
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$input = is_array($input ?? null) ? $input : CMS_M365PRICETRACKER_Microsoft_Price_Tracker::default_input();
$result = is_array($result ?? null) ? $result : CMS_M365PRICETRACKER_Microsoft_Price_Tracker::evaluate($input);
$recommendation = is_array($result['recommendation'] ?? null) ? $result['recommendation'] : [];
$impact = is_array($result['impact'] ?? null) ? $result['impact'] : [];
$renewal = is_array($result['renewal'] ?? null) ? $result['renewal'] : [];
$forecast = is_array($result['forecast'] ?? null) ? $result['forecast'] : [];
$inventory = is_array($result['inventory'] ?? null) ? $result['inventory'] : [];
$chartRows = is_array($result['chart_rows'] ?? null) ? $result['chart_rows'] : [];
$productFamilyOptions = is_array($result['product_family_options'] ?? null) ? $result['product_family_options'] : [];
$regionOptions = is_array($result['region_options'] ?? null) ? $result['region_options'] : [];
$segmentOptions = is_array($result['segment_options'] ?? null) ? $result['segment_options'] : [];
$eventTypeOptions = is_array($result['event_type_options'] ?? null) ? $result['event_type_options'] : [];
$channelOptions = is_array($result['channel_options'] ?? null) ? $result['channel_options'] : [];
$skuOptions = is_array($result['sku_options'] ?? null) ? $result['sku_options'] : [];
$forecastScenarioOptions = is_array($result['forecast_scenario_options'] ?? null) ? $result['forecast_scenario_options'] : [];
$tone = (string) ($recommendation['tone'] ?? 'info');
$tone = in_array($tone, ['success', 'warning', 'danger', 'info'], true) ? $tone : 'info';
$score = max(0, min(100, (int) ($recommendation['score'] ?? 0)));
$money = static fn(mixed $value): string => number_format((float) $value, 2, ',', '.') . ' €';
$isSelected = static fn(string $left, string $right): string => $left === $right ? ' selected' : '';
$priceHistoryData = CMS_M365PRICETRACKER_Microsoft_Price_Tracker::price_history_dataset();
$priceHistoryJson = json_encode($priceHistoryData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$priceHistoryJson = is_string($priceHistoryJson) ? $priceHistoryJson : '{}';
$settings = CMS_M365PRICETRACKER_Settings::all();
$contentMode = in_array((string) ($settings['content_mode'] ?? 'full'), ['full', 'compact', 'chart_only', 'custom'], true) ? (string) $settings['content_mode'] : 'full';
$layoutVariant = in_array((string) ($settings['layout_variant'] ?? 'sidebar'), ['sidebar', 'single', 'wide'], true) ? (string) $settings['layout_variant'] : 'sidebar';
$filterColumns = in_array((string) ($settings['filter_columns'] ?? '2'), ['1', '2', '3'], true) ? (string) $settings['filter_columns'] : '2';
$showSection = static function (string $key) use ($settings, $contentMode): bool {
    if ($contentMode === 'chart_only') {
        return in_array($key, ['hero', 'price_history', 'history_selector'], true) && (($settings['show_' . $key] ?? '1') === '1');
    }

    if ($contentMode === 'compact' && !in_array($key, ['hero', 'hero_cta', 'price_history', 'history_selector', 'filter_form', 'filter_fieldset', 'inventory_fieldset', 'forecast_fieldset', 'result_aside', 'summary_cards', 'info_card', 'link_card'], true)) {
        return false;
    }

    return (($settings['show_' . $key] ?? '1') === '1');
};
$showMainLayout = $showSection('filter_form') || $showSection('result_aside');
$showBottomCards = $showSection('next_steps') || $showSection('info_card') || $showSection('link_card');

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'Microsoft-Preiserhöhung-Tracker']);
}
?>

<main class="phinit-plugin m365price-tracker-page m365price-mode--<?php echo htmlspecialchars($contentMode, ENT_QUOTES, 'UTF-8'); ?> m365price-layout-variant--<?php echo htmlspecialchars($layoutVariant, ENT_QUOTES, 'UTF-8'); ?> m365price-filter-cols--<?php echo htmlspecialchars($filterColumns, ENT_QUOTES, 'UTF-8'); ?>" id="microsoft-preiserhoehung-tracker" data-m365price-tracker>
    <script type="application/json" data-m365price-tracker-data><?php echo $priceHistoryJson; ?></script>

    <?php if ($showSection('hero')): ?>
    <header class="m365price-hero">
        <p class="phinit-overline">Microsoft Preis- und Renewal-Timeline</p>
        <section class="m365price-hero__content" aria-labelledby="m365price-title">
            <section>
                <h1 id="m365price-title"><?php echo htmlspecialchars((string) ($settings['hero_title'] ?? 'Microsoft-Preiserhöhung-Tracker'), ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="phinit-prose"><?php echo htmlspecialchars((string) ($settings['hero_intro'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            </section>
            <?php if ($showSection('hero_cta') && (string) ($settings['hero_cta_url'] ?? '') !== ''): ?>
            <nav class="m365price-actions" aria-label="Aktionen">
                <a class="phinit-btn phinit-btn--secondary" href="<?php echo htmlspecialchars((string) ($settings['hero_cta_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($settings['hero_cta_label'] ?? 'Mehr erfahren'), ENT_QUOTES, 'UTF-8'); ?></a>
            </nav>
            <?php endif; ?>
        </section>
    </header>
    <?php endif; ?>

    <?php if ($showSection('price_history')): ?>
    <section class="phinit-result m365price-result-card m365price-price-history-section" aria-labelledby="m365price-history-title">
        <header class="m365price-result-heading">
            <section>
                <p class="phinit-overline">Preisverlauf</p>
                <h2 id="m365price-history-title">Preisentwicklung nach Lizenz</h2>
                <p>Chart.js wird auf dieser Seite zuerst geladen; bis zu fünf Preislinien aus dem gepflegten Zeitreihenkatalog lassen sich vergleichen.</p>
            </section>
        </header>
        <section class="m365price-chart-panel" aria-label="Preisverlauf als Linienchart">
            <canvas data-m365price-history-chart></canvas>
        </section>
        <?php if ($showSection('history_selector')): ?>
        <label class="phinit-field m365price-history-filter" for="m365price-history-license">
            Anzuzeigende Lizenzen <span class="m365price-filter-limit">max. 5</span>
            <select class="phinit-select" id="m365price-history-license" data-m365price-history-filter multiple size="8">
                <?php foreach ((array) ($priceHistoryData['licenses'] ?? []) as $license): ?>
                <?php if (!is_array($license)) { continue; } ?>
                <?php $licenseSlug = (string) ($license['slug'] ?? ''); ?>
                <option value="<?php echo htmlspecialchars($licenseSlug, ENT_QUOTES, 'UTF-8'); ?>"<?php echo in_array($licenseSlug, (array) ($priceHistoryData['default_slugs'] ?? []), true) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) ($license['name'] ?? $license['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <span class="m365price-chart-hint" data-m365price-history-hint hidden>max. 5 Lizenzen – bitte zuerst eine sichtbare Lizenz abwählen.</span>
            <span class="m365price-select-hint">Mehrfachauswahl per Strg/Klick oder Touch-Auswahl. Ohne Auswahl werden die Business-Lizenzen wiederhergestellt.</span>
        </label>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($showMainLayout): ?>
    <section class="m365price-layout" aria-label="Preis-Tracker Auswertung">
        <?php if ($showSection('filter_form')): ?>
        <section class="phinit-card" aria-labelledby="m365price-form-title">
            <header class="m365price-section-head">
                <section>
                    <h2 id="m365price-form-title">Filter, Renewal und Bestand erfassen</h2>
                    <p>Produktfamilie, Zeitraum, Renewal und bis zu drei SKU-Positionen auswählen.</p>
                </section>
            </header>

            <form method="GET" class="m365price-form">
                <?php if ($showSection('filter_fieldset')): ?>
                <fieldset class="m365price-fieldset">
                    <legend>Filter und Vertrag</legend>
                    <section class="m365price-form-grid m365price-form-grid--2">
                        <label class="phinit-field" for="m365price-family">Produktfamilie
                            <select class="phinit-select" id="m365price-family" name="product_family">
                                <?php foreach ($productFamilyOptions as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isSelected((string) ($input['product_family'] ?? ''), (string) $key); ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365price-event-type">Ereignistyp
                            <select class="phinit-select" id="m365price-event-type" name="event_type">
                                <?php foreach ($eventTypeOptions as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isSelected((string) ($input['event_type'] ?? ''), (string) $key); ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365price-region">Region
                            <select class="phinit-select" id="m365price-region" name="region">
                                <?php foreach ($regionOptions as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isSelected((string) ($input['region'] ?? ''), (string) $key); ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365price-segment">Segment
                            <select class="phinit-select" id="m365price-segment" name="segment">
                                <?php foreach ($segmentOptions as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isSelected((string) ($input['segment'] ?? ''), (string) $key); ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365price-start">Startjahr
                            <input class="phinit-input" type="number" id="m365price-start" name="analysis_start" min="2020" max="2035" value="<?php echo (int) ($input['analysis_start'] ?? 2023); ?>">
                        </label>
                        <label class="phinit-field" for="m365price-end">Endjahr
                            <input class="phinit-input" type="number" id="m365price-end" name="analysis_end" min="2020" max="2035" value="<?php echo (int) ($input['analysis_end'] ?? 2026); ?>">
                        </label>
                        <label class="phinit-field" for="m365price-renewal">Renewal-Datum
                            <input class="phinit-input" type="date" id="m365price-renewal" name="renewal_date" value="<?php echo htmlspecialchars((string) ($input['renewal_date'] ?? '2026-07-15'), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label class="phinit-field" for="m365price-channel">Beschaffungskanal
                            <select class="phinit-select" id="m365price-channel" name="channel">
                                <?php foreach ($channelOptions as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isSelected((string) ($input['channel'] ?? ''), (string) $key); ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </section>
                </fieldset>
                <?php endif; ?>

                <?php if ($showSection('inventory_fieldset')): ?>
                <fieldset class="m365price-fieldset">
                    <legend>Bestandspositionen</legend>
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                    <section class="m365price-form-grid m365price-form-grid--3" aria-label="Bestandsposition <?php echo (int) $i; ?>">
                        <label class="phinit-field" for="m365price-sku-<?php echo (int) $i; ?>">SKU <?php echo (int) $i; ?>
                            <select class="phinit-select" id="m365price-sku-<?php echo (int) $i; ?>" name="sku_<?php echo (int) $i; ?>">
                                <?php foreach ($skuOptions as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isSelected((string) ($input['sku_' . $i] ?? ''), (string) $key); ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365price-seats-<?php echo (int) $i; ?>">Menge
                            <input class="phinit-input" type="number" id="m365price-seats-<?php echo (int) $i; ?>" name="seats_<?php echo (int) $i; ?>" min="0" max="500000" value="<?php echo (int) ($input['seats_' . $i] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365price-price-<?php echo (int) $i; ?>">Monatspreis je Nutzer
                            <input class="phinit-input" type="number" step="0.01" id="m365price-price-<?php echo (int) $i; ?>" name="monthly_price_<?php echo (int) $i; ?>" min="0" value="<?php echo htmlspecialchars((string) ($input['monthly_price_' . $i] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </section>
                    <?php endfor; ?>
                </fieldset>
                <?php endif; ?>

                <?php if ($showSection('forecast_fieldset')): ?>
                <fieldset class="m365price-fieldset">
                    <legend>Chart und Forecast</legend>
                    <section class="m365price-form-grid m365price-form-grid--2">
                        <label class="phinit-field" for="m365price-chart-year">Historisches Vergleichsjahr
                            <input class="phinit-input" type="number" id="m365price-chart-year" name="chart_start_year" min="2020" max="2035" value="<?php echo (int) ($input['chart_start_year'] ?? 2024); ?>">
                        </label>
                        <label class="phinit-field" for="m365price-chart-amount">Betrag im Vergleichsjahr
                            <input class="phinit-input" type="number" step="0.01" id="m365price-chart-amount" name="chart_start_amount" min="0" value="<?php echo htmlspecialchars((string) ($input['chart_start_amount'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label class="phinit-field" for="m365price-current-amount">Aktueller Jahresbetrag optional
                            <input class="phinit-input" type="number" step="0.01" id="m365price-current-amount" name="current_amount" min="0" value="<?php echo htmlspecialchars((string) ($input['current_amount'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label class="phinit-field" for="m365price-forecast">Forecast-Szenario
                            <select class="phinit-select" id="m365price-forecast" name="forecast_scenario">
                                <?php foreach ($forecastScenarioOptions as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isSelected((string) ($input['forecast_scenario'] ?? ''), (string) $key); ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365price-forecast-months">Forecast-Monate
                            <input class="phinit-input" type="number" id="m365price-forecast-months" name="forecast_months" min="0" max="60" value="<?php echo (int) ($input['forecast_months'] ?? 12); ?>">
                        </label>
                    </section>
                </fieldset>
                <?php endif; ?>

                <section class="m365price-actions">
                    <button type="submit" class="phinit-btn phinit-btn--primary">Preisentwicklung berechnen</button>
                    <a class="phinit-btn phinit-btn--secondary" href="/microsoft-preiserhoehung-tracker">Zurücksetzen</a>
                </section>
            </form>
        </section>
        <?php endif; ?>

        <?php if ($showSection('result_aside')): ?>
        <aside class="m365price-aside" aria-labelledby="m365price-result-title">
            <article class="phinit-note phinit-note--<?php echo htmlspecialchars($tone, ENT_QUOTES, 'UTF-8'); ?>" id="m365price-result" tabindex="-1" data-m365price-result>
                <p class="phinit-overline">Budget-Einordnung</p>
                <h2 id="m365price-result-title"><?php echo htmlspecialchars((string) ($recommendation['label'] ?? 'Auswertung prüfen'), ENT_QUOTES, 'UTF-8'); ?></h2>
                <p><?php echo htmlspecialchars((string) ($recommendation['reason'] ?? 'Die Preisereignisse wurden bewertet.'), ENT_QUOTES, 'UTF-8'); ?></p>
                <section class="m365price-score" aria-label="Budget-Relevanz">
                    <span>Relevanz</span>
                    <strong><?php echo (int) $score; ?>%</strong>
                    <div class="m365price-score__bar"><span style="--m365price-score-width: <?php echo (int) $score; ?>%;"></span></div>
                </section>
            </article>

            <article class="phinit-result m365price-result-card">
                <header>
                    <p class="phinit-overline">Jahreswirkung</p>
                    <h2><?php echo htmlspecialchars($money($impact['positive_annual_delta'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> Mehrbudget</h2>
                    <p><?php echo htmlspecialchars((string) ($renewal['label'] ?? 'Renewal-Fenster prüfen'), ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars((string) ($renewal['renewal_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                </header>
                <dl class="m365price-kpi-list">
                    <div><dt>Aktueller Jahresbetrag</dt><dd><?php echo htmlspecialchars($money($inventory['current_amount'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    <div><dt>Monatsdelta</dt><dd><?php echo htmlspecialchars($money($impact['monthly_delta'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    <div><dt>Betroffene SKU-Zeilen</dt><dd><?php echo (int) ($impact['row_count'] ?? 0); ?></dd></div>
                </dl>
                <footer class="m365price-actions">
                    <a class="phinit-btn phinit-btn--primary" href="/kontakt">Budget prüfen lassen</a>
                    <button type="button" class="phinit-btn phinit-btn--secondary" data-m365price-print>Drucken / PDF speichern</button>
                </footer>
            </article>
        </aside>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($showSection('personal_tracker')): ?>
    <section class="phinit-result m365price-result-card m365price-personal-price-section" aria-labelledby="m365price-personal-title" data-m365price-personal-tracker>
        <header class="m365price-result-heading">
            <section>
                <p class="phinit-overline">Eigener Bestand</p>
                <h2 id="m365price-personal-title">Persönlicher Kosten-Tracker</h2>
                <p>Lokale Lizenzpositionen im Browser erfassen; es gibt keine Backend-Schreibzugriffe.</p>
            </section>
            <section class="m365price-actions">
                <button type="button" class="phinit-btn phinit-btn--secondary" data-m365price-personal-add aria-label="Lizenzzeile hinzufügen">+ Lizenz hinzufügen</button>
            </section>
        </header>
        <section class="m365price-personal-rows" data-m365price-personal-rows aria-label="Eigene Lizenzpositionen"></section>
        <section class="phinit-empty-state m365price-personal-empty" data-m365price-personal-empty>
            <h3>Noch keine eigenen Lizenzen</h3>
            <p>Nutze den Plus-Button, um Lizenz, Kaufdatum und Menge zu erfassen.</p>
        </section>
        <section class="m365price-actions">
            <button type="button" class="phinit-btn phinit-btn--primary" data-m365price-personal-evaluate>Auswerten</button>
        </section>
        <section class="m365price-personal-results" data-m365price-personal-results hidden>
            <section class="m365price-chart-panel" aria-label="Eigene Kostenentwicklung als Linienchart">
                <canvas data-m365price-personal-chart></canvas>
            </section>
            <section class="m365price-summary-grid" data-m365price-personal-summary aria-label="Gesamtauswertung"></section>
            <section class="phinit-table-wrap" aria-label="Kostenänderung je Lizenz">
                <table class="phinit-table m365price-personal-table">
                    <thead><tr><th scope="col">Lizenz</th><th scope="col">Kaufdatum</th><th scope="col">Menge</th><th scope="col">Preis damals</th><th scope="col">Aktueller Preis</th><th scope="col">Delta / Jahr</th><th scope="col">Delta %</th></tr></thead>
                    <tbody data-m365price-personal-list></tbody>
                </table>
            </section>
        </section>
    </section>
    <?php endif; ?>

    <?php if ($showSection('summary_cards')): ?>
    <section class="m365price-summary-grid" aria-label="Preis-Tracker Kennzahlen">
        <article class="phinit-card m365price-mini-card"><span>Offizielle Ereignisse</span><strong><?php echo count((array) ($result['events'] ?? [])); ?></strong><p>Gefilterte Microsoft-Ereignisse im gewählten Zeitraum.</p></article>
        <article class="phinit-card m365price-mini-card"><span>Renewal-Fenster</span><strong><?php echo (int) ($renewal['days_to_renewal'] ?? 0); ?> Tage</strong><p><?php echo htmlspecialchars((string) ($renewal['label'] ?? 'Renewal prüfen'), ENT_QUOTES, 'UTF-8'); ?>.</p></article>
        <article class="phinit-card m365price-mini-card"><span>Forecast</span><strong><?php echo htmlspecialchars((string) ($forecast['label'] ?? 'Kein Forecast'), ENT_QUOTES, 'UTF-8'); ?></strong><p><?php echo htmlspecialchars($money($forecast['forecast_delta'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> zusätzliche Planungsspanne.</p></article>
        <article class="phinit-card m365price-mini-card"><span>SKU-Treffer</span><strong><?php echo htmlspecialchars(implode(', ', (array) ($impact['affected_skus'] ?? [])), ENT_QUOTES, 'UTF-8'); ?></strong><p>Direkt gemappte Preiszeilen aus dem offiziellen Katalog.</p></article>
    </section>
    <?php endif; ?>

    <?php if ($showSection('year_chart')): ?>
    <section class="phinit-result m365price-result-card" aria-labelledby="m365price-chart-title">
        <header class="m365price-result-heading"><section><p class="phinit-overline">Visueller Jahresvergleich</p><h2 id="m365price-chart-title">Von Jahr <?php echo (int) ($input['chart_start_year'] ?? 2024); ?> bis heute</h2><p>Ausgangsbetrag, aktueller Jahreswert, Renewal-Wirkung und optionaler Forecast.</p></section></header>
        <section class="m365price-chart" aria-label="Jahresbeträge im Vergleich">
            <?php foreach ($chartRows as $row): ?>
            <?php if (!is_array($row)) { continue; } ?>
            <?php $barTone = (string) ($row['tone'] ?? 'neutral'); ?>
            <?php $barClass = $barTone === 'gain' ? 'm365price-chart__bar--gain' : ($barTone === 'cost' ? 'm365price-chart__bar--cost' : ''); ?>
            <section class="m365price-chart__row"><span><?php echo htmlspecialchars((string) ($row['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span><span><span class="m365price-chart__bar <?php echo htmlspecialchars($barClass, ENT_QUOTES, 'UTF-8'); ?>" style="--m365price-chart-width: <?php echo (int) ($row['width'] ?? 0); ?>%;"><?php echo htmlspecialchars($money($row['amount'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></span></span></section>
            <?php endforeach; ?>
        </section>
    </section>
    <?php endif; ?>

    <?php if ($showSection('impact_cards')): ?>
    <section class="m365price-result-grid" aria-label="Budgetwirkung und Forecast">
        <article class="phinit-card m365price-result-card">
            <header><p class="phinit-overline">Direkte Preiswirkung</p><h2><?php echo htmlspecialchars($money($impact['annual_delta'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> pro Jahr</h2><p>Positive und negative Preiszeilen werden separat ausgewiesen.</p></header>
            <dl class="m365price-kpi-list"><div><dt>Mehrkosten</dt><dd><?php echo htmlspecialchars($money($impact['positive_annual_delta'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></dd></div><div><dt>Senkungen</dt><dd><?php echo htmlspecialchars($money($impact['decrease_annual_delta'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></dd></div></dl>
        </article>
        <article class="phinit-note phinit-note--info"><p class="phinit-overline">Forecast getrennt</p><h2><?php echo htmlspecialchars((string) ($forecast['label'] ?? 'Forecast'), ENT_QUOTES, 'UTF-8'); ?> · <?php echo (int) ($forecast['months'] ?? 0); ?> Monate</h2><p><?php echo htmlspecialchars((string) ($result['meta']['forecast_text'] ?? 'Forecast ist eine Planungsschätzung.'), ENT_QUOTES, 'UTF-8'); ?></p><dl class="m365price-kpi-list"><div><dt>Forecast-Betrag</dt><dd><?php echo htmlspecialchars($money($forecast['forecast_amount'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></dd></div><div><dt>Unsicherheit</dt><dd><?php echo htmlspecialchars((string) ($forecast['uncertainty'] ?? 'mittel'), ENT_QUOTES, 'UTF-8'); ?></dd></div></dl></article>
    </section>
    <?php endif; ?>

    <?php if ($showSection('sku_table')): ?>
    <section class="phinit-result m365price-result-card" aria-labelledby="m365price-impact-title">
        <header class="m365price-result-heading"><section><p class="phinit-overline">SKU-Treffer</p><h2 id="m365price-impact-title">Kanonische Preiszeitreihe</h2><p>Direkte Treffer aus dem Paketpreiskatalog.</p></section></header>
        <section class="phinit-table-wrap" aria-label="Preisänderungen je SKU">
            <table class="phinit-table"><thead><tr><th scope="col">SKU</th><th scope="col">Ereignis</th><th scope="col">Menge</th><th scope="col">Alt</th><th scope="col">Neu</th><th scope="col">Jahresdelta</th><th scope="col">Status</th></tr></thead><tbody>
                <?php foreach ((array) ($impact['rows'] ?? []) as $row): ?>
                <?php if (!is_array($row)) { continue; } ?>
                <tr><th scope="row"><?php echo htmlspecialchars((string) ($row['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></th><td><?php echo htmlspecialchars((string) ($row['effective_at'] ?? $row['event_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo (int) ($row['seats'] ?? 0); ?></td><td><?php echo htmlspecialchars($money($row['current_price'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($money($row['new_price'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($money($row['delta_annual'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars((string) ($row['verification_status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                <?php endforeach; ?>
                <?php if (empty($impact['rows'])): ?><tr><td colspan="7">Für die gewählten Filter wurde keine direkte SKU-Preiszeile gefunden.</td></tr><?php endif; ?>
            </tbody></table>
        </section>
    </section>
    <?php endif; ?>

    <?php if ($showSection('events_table')): ?>
    <section class="phinit-result m365price-result-card" aria-labelledby="m365price-events-title">
        <header class="m365price-result-heading"><section><p class="phinit-overline">Offizielle Timeline</p><h2 id="m365price-events-title">Microsoft-Ereignisse im Zeitraum</h2><p>Pricing, Packaging, SKU-Split, Retirement und End-of-sale im gewählten Zeitraum.</p></section></header>
        <section class="phinit-table-wrap" aria-label="Offizielle Microsoft Ereignisse">
            <table class="phinit-table"><thead><tr><th scope="col">Datum</th><th scope="col">Ereignis</th><th scope="col">Typ</th><th scope="col">Einordnung</th></tr></thead><tbody>
                <?php foreach ((array) ($result['events'] ?? []) as $event): ?>
                <?php if (!is_array($event)) { continue; } ?>
                <?php $eventType = (string) ($event['event_type'] ?? ''); ?>
                <tr><td><time datetime="<?php echo htmlspecialchars((string) ($event['effective_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($event['effective_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></time></td><th scope="row"><a href="<?php echo htmlspecialchars((string) ($event['source_url'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars((string) ($event['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a></th><td><?php echo htmlspecialchars((string) ($eventTypeOptions[$eventType] ?? $eventType), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars((string) ($event['customer_rule'] ?? $event['summary'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                <?php endforeach; ?>
                <?php if (empty($result['events'])): ?><tr><td colspan="4">Keine offiziellen Ereignisse für die gewählten Filter im Zeitraum.</td></tr><?php endif; ?>
            </tbody></table>
        </section>
    </section>
    <?php endif; ?>

    <?php if ($showBottomCards): ?>
    <section class="m365price-result-grid" aria-label="Nächste Schritte">
        <?php if ($showSection('next_steps')): ?>
        <article class="phinit-note phinit-note--info"><h2>Nächste Schritte</h2><ol class="m365price-note-list"><?php foreach ((array) ($result['next_steps'] ?? []) as $step): ?><li><?php echo htmlspecialchars((string) $step, ENT_QUOTES, 'UTF-8'); ?></li><?php endforeach; ?></ol></article>
        <?php endif; ?>
        <?php if ($showSection('info_card')): ?>
        <article class="phinit-note phinit-note--info m365price-source-card"><h2><?php echo htmlspecialchars((string) ($settings['info_card_title'] ?? 'Quellenstand'), ENT_QUOTES, 'UTF-8'); ?></h2><p><?php echo htmlspecialchars((string) ($settings['info_card_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p><p>Quellenprüfung: <?php echo htmlspecialchars((string) ($result['meta']['source_checked'] ?? '2026-06-01'), ENT_QUOTES, 'UTF-8'); ?>. <?php echo htmlspecialchars((string) ($result['meta']['currency_note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p><details><summary>Quellen anzeigen</summary><ul class="m365price-note-list"><?php foreach ((array) ($result['sources'] ?? []) as $source): ?><li><a href="<?php echo htmlspecialchars((string) $source, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars((string) $source, ENT_QUOTES, 'UTF-8'); ?></a></li><?php endforeach; ?></ul></details></article>
        <?php endif; ?>
        <?php if ($showSection('link_card') && (string) ($settings['link_card_url'] ?? '') !== ''): ?>
        <article class="phinit-note phinit-note--info m365price-link-card"><h2><?php echo htmlspecialchars((string) ($settings['link_card_title'] ?? 'Budget- und Lizenzprüfung'), ENT_QUOTES, 'UTF-8'); ?></h2><p><?php echo htmlspecialchars((string) ($settings['link_card_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p><a class="phinit-btn phinit-btn--primary" href="<?php echo htmlspecialchars((string) ($settings['link_card_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($settings['link_card_label'] ?? 'Mehr erfahren'), ENT_QUOTES, 'UTF-8'); ?></a></article>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</main>

<?php
if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getFooter();
}
