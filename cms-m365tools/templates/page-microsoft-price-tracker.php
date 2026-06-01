<?php
/**
 * Public Template: Microsoft-Preiserhöhung-Tracker.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$input = is_array($input ?? null) ? $input : CMS_M365CALCULATOR_Microsoft_Price_Tracker::default_input();
$result = is_array($result ?? null) ? $result : CMS_M365CALCULATOR_Microsoft_Price_Tracker::evaluate($input);
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
$priceHistoryData = CMS_M365CALCULATOR_Microsoft_Price_Tracker::price_history_dataset();
$priceHistoryJson = json_encode($priceHistoryData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$priceHistoryJson = is_string($priceHistoryJson) ? $priceHistoryJson : '{}';

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'Microsoft-Preiserhöhung-Tracker']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-price-tracker-page" id="microsoft-preiserhoehung-tracker" data-m365calc-price-tracker>
    <script type="application/json" data-m365calc-price-tracker-data><?php echo $priceHistoryJson; ?></script>
    <header class="m365calc-hero">
        <p class="phinit-overline">Microsoft Preis- und Renewal-Timeline</p>
        <section class="m365calc-hero__content" aria-labelledby="m365price-title">
            <section>
                <h1 id="m365price-title">Microsoft-Preiserhöhung-Tracker</h1>
                <p class="phinit-prose">Verfolge offizielle Microsoft-Preis-, Packaging-, SKU- und Renewal-Ereignisse, berechne Budgetwirkung für deinen Bestand und vergleiche einen historischen Betrag mit dem aktuellen Stand.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Microsoft 365 Tools">
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenzberater">Lizenzberater öffnen</a>
                <a class="phinit-btn phinit-btn--secondary" href="/m365-tools">Toolbox anzeigen</a>
            </nav>
        </section>
    </header>

    <section class="phinit-result m365calc-result-card m365calc-price-history-section" aria-labelledby="m365price-history-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Preisverlauf</p>
                <h2 id="m365price-history-title">Section A – Preisentwicklung nach Lizenz</h2>
                <p>Der Linienchart nutzt den bestehenden kanonischen Preiszeitreihenkatalog. Alle anzeigen bleibt Standard; Business Standard, Business Premium, E3 und E5 starten sichtbar.</p>
            </section>
        </header>
        <section class="m365calc-chart-panel" aria-label="Preisverlauf als Linienchart">
            <canvas data-m365calc-price-history-chart></canvas>
            <p class="m365calc-chart-fallback" data-m365calc-price-chart-status hidden>Chart.js konnte nicht geladen werden. Die Preiszeitreihen bleiben in den Tabellen unten verfügbar.</p>
        </section>
        <section class="m365calc-chart-legend" data-m365calc-price-history-legend aria-label="Sichtbare Lizenzlinien"></section>
        <p class="m365calc-chart-hint" data-m365calc-price-history-hint hidden>max. 5 Lizenzen – bitte zuerst eine sichtbare Lizenz abwählen.</p>
        <label class="phinit-field m365calc-price-history-filter" for="m365price-history-license">
            Ansicht wechseln
            <select class="phinit-select" id="m365price-history-license" data-m365calc-price-history-filter>
                <option value="">Alle anzeigen</option>
                <?php foreach ((array) ($priceHistoryData['licenses'] ?? []) as $license): ?>
                <?php if (!is_array($license)) { continue; } ?>
                <option value="<?php echo htmlspecialchars((string) ($license['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($license['name'] ?? $license['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </section>

    <hr class="m365calc-section-divider" aria-hidden="true">

    <section class="phinit-result m365calc-result-card m365calc-personal-price-section" aria-labelledby="m365price-personal-title" data-m365calc-personal-price-tracker>
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Eigener Bestand</p>
                <h2 id="m365price-personal-title">Section B – Persönlicher Kosten-Tracker</h2>
                <p>Füge deine Lizenzen lokal im Browser hinzu. Kaufdatum und Menge werden nur in `localStorage` gespeichert, es gibt keine Backend-Schreibzugriffe.</p>
            </section>
            <section class="m365calc-actions">
                <button type="button" class="phinit-btn phinit-btn--secondary m365calc-icon-btn" data-m365calc-personal-add aria-label="Lizenzzeile hinzufügen">+ Lizenz hinzufügen</button>
            </section>
        </header>

        <section class="m365calc-personal-rows" data-m365calc-personal-rows aria-label="Eigene Lizenzpositionen"></section>
        <section class="phinit-empty-state m365calc-personal-empty" data-m365calc-personal-empty>
            <h3>Noch keine eigenen Lizenzen</h3>
            <p>Nutze den Plus-Button, um Lizenz, Kaufdatum und Menge zu erfassen.</p>
        </section>
        <section class="m365calc-actions">
            <button type="button" class="phinit-btn phinit-btn--primary" data-m365calc-personal-evaluate>Auswerten</button>
        </section>

        <section class="m365calc-personal-results" data-m365calc-personal-results hidden>
            <section class="m365calc-chart-panel" aria-label="Eigene Kostenentwicklung als Linienchart">
                <canvas data-m365calc-personal-chart></canvas>
            </section>
            <section class="m365calc-summary-grid" data-m365calc-personal-summary aria-label="Gesamtauswertung"></section>
            <section class="phinit-table-wrap" aria-label="Kostenänderung je Lizenz">
                <table class="phinit-table m365calc-personal-table">
                    <thead>
                        <tr>
                            <th scope="col">Lizenz</th>
                            <th scope="col">Kaufdatum</th>
                            <th scope="col">Menge</th>
                            <th scope="col">Preis damals</th>
                            <th scope="col">Aktueller Preis</th>
                            <th scope="col">Delta / Jahr</th>
                            <th scope="col">Delta %</th>
                        </tr>
                    </thead>
                    <tbody data-m365calc-personal-list></tbody>
                </table>
            </section>
        </section>
    </section>

    <section class="m365calc-layout" aria-label="Preis-Tracker Auswertung">
        <section class="phinit-card" aria-labelledby="m365price-form-title">
            <header class="m365calc-section__head">
                <section>
                    <h2 id="m365price-form-title">Filter, Renewal und Bestand erfassen</h2>
                    <p>Wähle Produktfamilie, Zeitraum, Vertragssicht und bis zu drei Bestandspositionen. Die Auswertung trennt offizielle Ereignisse, direkte SKU-Treffer und Forecast.</p>
                </section>
            </header>

            <form method="GET" class="m365calc-form m365calc-price-form">
                <fieldset class="m365calc-fieldset">
                    <legend>Filter und Vertrag</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365price-family">
                            Produktfamilie
                            <select class="phinit-select" id="m365price-family" name="product_family">
                                <?php foreach ($productFamilyOptions as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isSelected((string) ($input['product_family'] ?? ''), (string) $key); ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365price-event-type">
                            Ereignistyp
                            <select class="phinit-select" id="m365price-event-type" name="event_type">
                                <?php foreach ($eventTypeOptions as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isSelected((string) ($input['event_type'] ?? ''), (string) $key); ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365price-region">
                            Region
                            <select class="phinit-select" id="m365price-region" name="region">
                                <?php foreach ($regionOptions as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isSelected((string) ($input['region'] ?? ''), (string) $key); ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365price-segment">
                            Segment
                            <select class="phinit-select" id="m365price-segment" name="segment">
                                <?php foreach ($segmentOptions as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isSelected((string) ($input['segment'] ?? ''), (string) $key); ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365price-start">
                            Startjahr
                            <input class="phinit-input" type="number" id="m365price-start" name="analysis_start" min="2020" max="2035" value="<?php echo (int) ($input['analysis_start'] ?? 2023); ?>">
                        </label>
                        <label class="phinit-field" for="m365price-end">
                            Endjahr
                            <input class="phinit-input" type="number" id="m365price-end" name="analysis_end" min="2020" max="2035" value="<?php echo (int) ($input['analysis_end'] ?? 2026); ?>">
                        </label>
                        <label class="phinit-field" for="m365price-renewal">
                            Renewal-Datum
                            <input class="phinit-input" type="date" id="m365price-renewal" name="renewal_date" value="<?php echo htmlspecialchars((string) ($input['renewal_date'] ?? '2026-07-15'), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label class="phinit-field" for="m365price-channel">
                            Beschaffungskanal
                            <select class="phinit-select" id="m365price-channel" name="channel">
                                <?php foreach ($channelOptions as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isSelected((string) ($input['channel'] ?? ''), (string) $key); ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Bestandspositionen</legend>
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                    <section class="m365calc-form-grid m365calc-form-grid--3" aria-label="Bestandsposition <?php echo (int) $i; ?>">
                        <label class="phinit-field" for="m365price-sku-<?php echo (int) $i; ?>">
                            SKU <?php echo (int) $i; ?>
                            <select class="phinit-select" id="m365price-sku-<?php echo (int) $i; ?>" name="sku_<?php echo (int) $i; ?>">
                                <?php foreach ($skuOptions as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isSelected((string) ($input['sku_' . $i] ?? ''), (string) $key); ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365price-seats-<?php echo (int) $i; ?>">
                            Menge
                            <input class="phinit-input" type="number" id="m365price-seats-<?php echo (int) $i; ?>" name="seats_<?php echo (int) $i; ?>" min="0" max="500000" value="<?php echo (int) ($input['seats_' . $i] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365price-price-<?php echo (int) $i; ?>">
                            Monatspreis je Nutzer
                            <input class="phinit-input" type="number" step="0.01" id="m365price-price-<?php echo (int) $i; ?>" name="monthly_price_<?php echo (int) $i; ?>" min="0" value="<?php echo htmlspecialchars((string) ($input['monthly_price_' . $i] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </section>
                    <?php endfor; ?>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Chart und Forecast</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365price-chart-year">
                            Historisches Vergleichsjahr
                            <input class="phinit-input" type="number" id="m365price-chart-year" name="chart_start_year" min="2020" max="2035" value="<?php echo (int) ($input['chart_start_year'] ?? 2024); ?>">
                        </label>
                        <label class="phinit-field" for="m365price-chart-amount">
                            Betrag im Vergleichsjahr
                            <input class="phinit-input" type="number" step="0.01" id="m365price-chart-amount" name="chart_start_amount" min="0" value="<?php echo htmlspecialchars((string) ($input['chart_start_amount'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label class="phinit-field" for="m365price-current-amount">
                            Aktueller Jahresbetrag optional
                            <input class="phinit-input" type="number" step="0.01" id="m365price-current-amount" name="current_amount" min="0" value="<?php echo htmlspecialchars((string) ($input['current_amount'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label class="phinit-field" for="m365price-forecast">
                            Forecast-Szenario
                            <select class="phinit-select" id="m365price-forecast" name="forecast_scenario">
                                <?php foreach ($forecastScenarioOptions as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isSelected((string) ($input['forecast_scenario'] ?? ''), (string) $key); ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365price-forecast-months">
                            Forecast-Monate
                            <input class="phinit-input" type="number" id="m365price-forecast-months" name="forecast_months" min="0" max="60" value="<?php echo (int) ($input['forecast_months'] ?? 12); ?>">
                        </label>
                    </section>
                </fieldset>

                <section class="m365calc-actions">
                    <button type="submit" class="phinit-btn phinit-btn--primary">Preisentwicklung berechnen</button>
                    <a class="phinit-btn phinit-btn--secondary" href="/microsoft-preiserhoehung-tracker">Zurücksetzen</a>
                </section>
            </form>
        </section>

        <aside class="m365calc-aside" aria-labelledby="m365price-result-title">
            <article class="phinit-note phinit-note--<?php echo htmlspecialchars($tone, ENT_QUOTES, 'UTF-8'); ?>" id="m365calc-result" tabindex="-1" data-m365calc-result>
                <p class="phinit-overline">Budget-Einordnung</p>
                <h2 id="m365price-result-title"><?php echo htmlspecialchars((string) ($recommendation['label'] ?? 'Auswertung prüfen'), ENT_QUOTES, 'UTF-8'); ?></h2>
                <p><?php echo htmlspecialchars((string) ($recommendation['reason'] ?? 'Die Preisereignisse wurden bewertet.'), ENT_QUOTES, 'UTF-8'); ?></p>
                <section class="m365calc-score" aria-label="Budget-Relevanz">
                    <span>Relevanz</span>
                    <strong><?php echo (int) $score; ?>%</strong>
                    <div class="m365calc-score__bar"><span style="--m365calc-score-width: <?php echo (int) $score; ?>%;"></span></div>
                </section>
            </article>

            <article class="phinit-result m365calc-result-card">
                <header>
                    <p class="phinit-overline">Jahreswirkung</p>
                    <h2><?php echo htmlspecialchars($money($impact['positive_annual_delta'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> Mehrbudget</h2>
                    <p><?php echo htmlspecialchars((string) ($renewal['label'] ?? 'Renewal-Fenster prüfen'), ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars((string) ($renewal['renewal_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                </header>
                <dl class="m365calc-kpi-list">
                    <div>
                        <dt>Aktueller Jahresbetrag</dt>
                        <dd><?php echo htmlspecialchars($money($inventory['current_amount'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <div>
                        <dt>Monatsdelta</dt>
                        <dd><?php echo htmlspecialchars($money($impact['monthly_delta'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <div>
                        <dt>Betroffene SKU-Zeilen</dt>
                        <dd><?php echo (int) ($impact['row_count'] ?? 0); ?></dd>
                    </div>
                </dl>
                <footer class="m365calc-actions">
                    <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Drucken / PDF speichern</button>
                    <a class="phinit-btn phinit-btn--primary" href="/kontakt">Budget prüfen lassen</a>
                </footer>
            </article>
        </aside>
    </section>

    <section class="m365calc-summary-grid" aria-label="Preis-Tracker Kennzahlen">
        <article class="phinit-card m365calc-mini-card">
            <span>Offizielle Ereignisse</span>
            <strong><?php echo count((array) ($result['events'] ?? [])); ?></strong>
            <p>Gefilterte Microsoft-Ereignisse im gewählten Zeitraum.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Renewal-Fenster</span>
            <strong><?php echo (int) ($renewal['days_to_renewal'] ?? 0); ?> Tage</strong>
            <p><?php echo htmlspecialchars((string) ($renewal['label'] ?? 'Renewal prüfen'), ENT_QUOTES, 'UTF-8'); ?>.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Forecast</span>
            <strong><?php echo htmlspecialchars((string) ($forecast['label'] ?? 'Kein Forecast'), ENT_QUOTES, 'UTF-8'); ?></strong>
            <p><?php echo htmlspecialchars($money($forecast['forecast_delta'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> zusätzliche Planungsspanne.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>SKU-Treffer</span>
            <strong><?php echo htmlspecialchars(implode(', ', (array) ($impact['affected_skus'] ?? [])), ENT_QUOTES, 'UTF-8'); ?></strong>
            <p>Direkt gemappte Preiszeilen aus dem offiziellen Katalog.</p>
        </article>
    </section>

    <section class="phinit-result m365calc-result-card m365calc-roi-chart" aria-labelledby="m365price-chart-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Visueller Jahresvergleich</p>
                <h2 id="m365price-chart-title">Von Jahr <?php echo (int) ($input['chart_start_year'] ?? 2024); ?> bis heute</h2>
                <p>Der Chart vergleicht den angegebenen Ausgangsbetrag mit dem aktuellen Jahreswert, der Renewal-Wirkung und optionalem Forecast.</p>
            </section>
        </header>
        <section class="m365calc-chart" aria-label="Jahresbeträge im Vergleich">
            <?php foreach ($chartRows as $row): ?>
            <?php if (!is_array($row)) { continue; } ?>
            <?php $barTone = (string) ($row['tone'] ?? 'neutral'); ?>
            <?php $barClass = $barTone === 'gain' ? 'm365calc-chart__bar--gain' : ($barTone === 'cost' ? 'm365calc-chart__bar--cost' : ''); ?>
            <section class="m365calc-chart__row">
                <span><?php echo htmlspecialchars((string) ($row['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="m365calc-chart__bars">
                    <span class="m365calc-chart__bar <?php echo htmlspecialchars($barClass, ENT_QUOTES, 'UTF-8'); ?>" style="--m365calc-chart-width: <?php echo (int) ($row['width'] ?? 0); ?>%;"><?php echo htmlspecialchars($money($row['amount'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></span>
                </span>
            </section>
            <?php endforeach; ?>
        </section>
    </section>

    <section class="m365calc-result-grid" aria-label="Budgetwirkung und Forecast">
        <article class="phinit-card m365calc-result-card">
            <header>
                <p class="phinit-overline">Direkte Preiswirkung</p>
                <h2><?php echo htmlspecialchars($money($impact['annual_delta'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> pro Jahr</h2>
                <p>Positive und negative Preiszeilen werden separat ausgewiesen, damit Senkungen und Erhöhungen nicht vermischt werden.</p>
            </header>
            <dl class="m365calc-kpi-list">
                <div>
                    <dt>Mehrkosten</dt>
                    <dd><?php echo htmlspecialchars($money($impact['positive_annual_delta'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div>
                    <dt>Senkungen</dt>
                    <dd><?php echo htmlspecialchars($money($impact['decrease_annual_delta'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
            </dl>
        </article>
        <article class="phinit-note phinit-note--info">
            <p class="phinit-overline">Forecast getrennt</p>
            <h2><?php echo htmlspecialchars((string) ($forecast['label'] ?? 'Forecast'), ENT_QUOTES, 'UTF-8'); ?> · <?php echo (int) ($forecast['months'] ?? 0); ?> Monate</h2>
            <p><?php echo htmlspecialchars((string) ($result['meta']['forecast_text'] ?? 'Forecast ist eine Planungsschätzung.'), ENT_QUOTES, 'UTF-8'); ?></p>
            <dl class="m365calc-kpi-list">
                <div>
                    <dt>Forecast-Betrag</dt>
                    <dd><?php echo htmlspecialchars($money($forecast['forecast_amount'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div>
                    <dt>Unsicherheit</dt>
                    <dd><?php echo htmlspecialchars((string) ($forecast['uncertainty'] ?? 'mittel'), ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
            </dl>
        </article>
    </section>

    <section class="phinit-result m365calc-result-card" aria-labelledby="m365price-impact-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">SKU-Treffer</p>
                <h2 id="m365price-impact-title">Kanonische Preiszeitreihe</h2>
                <p>Diese Tabelle nutzt den zentralen Paketpreiskatalog mit Mai-2026-Baseline, historischen Ankern und bestätigten Future-Rows.</p>
            </section>
        </header>
        <section class="phinit-table-wrap" aria-label="Preisänderungen je SKU">
            <table class="phinit-table">
                <thead>
                    <tr>
                        <th scope="col">SKU</th>
                        <th scope="col">Ereignis</th>
                        <th scope="col">Menge</th>
                        <th scope="col">Alt</th>
                        <th scope="col">Neu</th>
                        <th scope="col">Jahresdelta</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ((array) ($impact['rows'] ?? []) as $row): ?>
                    <?php if (!is_array($row)) { continue; } ?>
                    <tr>
                        <th scope="row"><?php echo htmlspecialchars((string) ($row['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></th>
                        <td><?php echo htmlspecialchars((string) ($row['effective_at'] ?? $row['event_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int) ($row['seats'] ?? 0); ?></td>
                        <td><?php echo htmlspecialchars($money($row['current_price'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($money($row['new_price'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($money($row['delta_annual'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) ($row['verification_status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($impact['rows'])): ?>
                    <tr>
                        <td colspan="7">Für die gewählten Filter wurde keine direkte SKU-Preiszeile gefunden.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </section>

    <section class="phinit-result m365calc-result-card" aria-labelledby="m365price-events-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Offizielle Timeline</p>
                <h2 id="m365price-events-title">Microsoft-Ereignisse im Zeitraum</h2>
                <p>Pricing, Packaging, SKU-Split, Retirement und End-of-sale werden als unterschiedliche Ereignistypen geführt.</p>
            </section>
        </header>
        <section class="phinit-table-wrap" aria-label="Offizielle Microsoft Ereignisse">
            <table class="phinit-table">
                <thead>
                    <tr>
                        <th scope="col">Datum</th>
                        <th scope="col">Ereignis</th>
                        <th scope="col">Typ</th>
                        <th scope="col">Einordnung</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ((array) ($result['events'] ?? []) as $event): ?>
                    <?php if (!is_array($event)) { continue; } ?>
                    <?php $eventType = (string) ($event['event_type'] ?? ''); ?>
                    <tr>
                        <td><time datetime="<?php echo htmlspecialchars((string) ($event['effective_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($event['effective_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></time></td>
                        <th scope="row"><a href="<?php echo htmlspecialchars((string) ($event['source_url'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars((string) ($event['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a></th>
                        <td><?php echo htmlspecialchars((string) ($eventTypeOptions[$eventType] ?? $eventType), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) ($event['customer_rule'] ?? $event['summary'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($result['events'])): ?>
                    <tr>
                        <td colspan="4">Keine offiziellen Ereignisse für die gewählten Filter im Zeitraum.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </section>

    <section class="m365calc-result-grid" aria-label="Nächste Schritte">
        <article class="phinit-note phinit-note--info">
            <h2>Nächste Schritte</h2>
            <ol class="m365calc-note-list">
                <?php foreach ((array) ($result['next_steps'] ?? []) as $step): ?>
                <li><?php echo htmlspecialchars((string) $step, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ol>
        </article>
        <article class="phinit-note phinit-note--info m365calc-source-card">
            <h2>Quellenstand</h2>
            <p>Quellenprüfung: <?php echo htmlspecialchars((string) ($result['meta']['source_checked'] ?? '2026-05-17'), ENT_QUOTES, 'UTF-8'); ?>. <?php echo htmlspecialchars((string) ($result['meta']['currency_note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <details>
                <summary>Quellen anzeigen</summary>
                <ul class="m365calc-note-list">
                    <?php foreach ((array) ($result['sources'] ?? []) as $source): ?>
                    <li><a href="<?php echo htmlspecialchars((string) $source, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars((string) $source, ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        </article>
    </section>
</main>

<?php
if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getFooter();
}
