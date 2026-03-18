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
$stepOneFeatureKeys = ['mail', 'teams', 'office_web', 'office_desktop', 'terminalserver', 'onedrive', 'sharepoint', 'frontline'];
$stepThreeFeatureKeys = array_values(array_filter(array_keys($featureDefinitions), static function (string $key) use ($featureDefinitions): bool {
    return empty($featureDefinitions[$key]['base']);
}));
$stepTwoFeatureKeys = array_values(array_filter(array_keys($featureDefinitions), static function (string $key) use ($stepOneFeatureKeys, $stepThreeFeatureKeys): bool {
    return !in_array($key, $stepOneFeatureKeys, true) && !in_array($key, $stepThreeFeatureKeys, true);
}));
$addonGroupClassMap = [
    'security-addon' => 'security',
    'security' => 'security',
    'identity' => 'identity',
    'addons' => 'productivity',
    'productivity' => 'productivity',
    'power-platform' => 'productivity',
    'copilot' => 'copilot',
];

$renderRequirementRow = static function (array $requirement, int $index) use ($featureDefinitions, $presets, $esc, $stepOneFeatureKeys, $stepTwoFeatureKeys, $stepThreeFeatureKeys, $addonGroupClassMap): void {
    ?>
    <article class="m365lic-requirement" data-index="<?php echo $index; ?>" data-step="1">
        <div class="m365lic-requirement__head">
            <div>
                <h3>Bedarfsgruppe <?php echo $index + 1; ?></h3>
                <div class="m365lic-requirement__meta">Erfasse den Bedarf wie in der Standard-Auswertung und vergleiche danach M365 direkt mit europäischen Alternativen.</div>
            </div>
            <button type="button" class="m365lic-btn m365lic-btn--ghost m365lic-remove-row">Entfernen</button>
        </div>

        <div class="m365lic-stepper" role="tablist" aria-label="EU-Bedarfserfassung">
            <button type="button" class="m365lic-stepper__item is-active" data-step-target="1">
                <span class="m365lic-stepper__num">1</span>
                <span>Quick Check</span>
            </button>
            <button type="button" class="m365lic-stepper__item" data-step-target="2">
                <span class="m365lic-stepper__num">2</span>
                <span>Advanced / Expertenoptionen</span>
            </button>
            <button type="button" class="m365lic-stepper__item" data-step-target="3">
                <span class="m365lic-stepper__num">3</span>
                <span>Add-ons & Security</span>
            </button>
        </div>

        <section class="m365lic-step-panel is-active m365lic-step-card" data-step-panel="1">
            <div class="m365lic-step-panel__head">
                <div>
                    <span class="m365lic-step-panel__eyebrow">Schritt 1</span>
                    <strong>Quick Check</strong>
                    <p>Erfasse Benutzergruppe, Einsatzmodell und die wichtigsten Basisfunktionen. Die europäischen Alternativen werden erst nach der Auswertung automatisch dazu vorgeschlagen.</p>
                </div>
                <span class="m365lic-pill">Pflichtschritt</span>
            </div>

            <div class="m365lic-form-grid m365lic-form-grid--3">
                <div class="m365lic-field">
                    <label for="req_label_<?php echo $index; ?>">Bezeichnung</label>
                    <input id="req_label_<?php echo $index; ?>" type="text" name="requirements[<?php echo $index; ?>][label]" value="<?php echo $esc((string) ($requirement['label'] ?? '')); ?>" placeholder="z. B. Vertrieb / Backoffice / Frontline" data-requirement-label>
                </div>
                <div class="m365lic-field">
                    <label for="req_qty_<?php echo $index; ?>">Anzahl Benutzer</label>
                    <input id="req_qty_<?php echo $index; ?>" type="number" min="1" name="requirements[<?php echo $index; ?>][quantity]" value="<?php echo (int) ($requirement['quantity'] ?? 1); ?>" data-requirement-quantity>
                </div>
                <div class="m365lic-field">
                    <label for="req_audience_<?php echo $index; ?>">Zielgruppe</label>
                    <select id="req_audience_<?php echo $index; ?>" name="requirements[<?php echo $index; ?>][audience]" data-requirement-audience>
                        <option value="knowledge" <?php echo ($requirement['audience'] ?? 'knowledge') === 'knowledge' ? 'selected' : ''; ?>>Knowledge Worker</option>
                        <option value="frontline" <?php echo ($requirement['audience'] ?? '') === 'frontline' ? 'selected' : ''; ?>>Frontline / Kiosk</option>
                    </select>
                </div>
            </div>

            <div class="m365lic-field">
                <label for="req_preset_<?php echo $index; ?>">Preset</label>
                <select id="req_preset_<?php echo $index; ?>" class="m365lic-preset-select" name="requirements[<?php echo $index; ?>][preset]" data-requirement-preset>
                    <option value="">— frei konfigurieren —</option>
                    <?php foreach ($presets as $presetKey => $preset): ?>
                    <option value="<?php echo $esc($presetKey); ?>" <?php echo ($requirement['preset'] ?? '') === $presetKey ? 'selected' : ''; ?>><?php echo $esc((string) $preset['label']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="m365lic-feature-grid m365lic-feature-grid--dense">
                <?php foreach ($stepOneFeatureKeys as $featureKey): ?>
                    <?php if (!isset($featureDefinitions[$featureKey])) { continue; } ?>
                    <?php $feature = $featureDefinitions[$featureKey]; ?>
                    <label class="m365lic-feature-toggle">
                        <input type="checkbox" data-feature="<?php echo $esc($featureKey); ?>" name="requirements[<?php echo $index; ?>][features][<?php echo $esc($featureKey); ?>]" value="1" <?php echo in_array($featureKey, $requirement['features'] ?? [], true) ? 'checked' : ''; ?>>
                        <span>
                            <strong><?php echo $esc((string) $feature['label']); ?></strong>
                            <small><?php echo $esc((string) $feature['description']); ?></small>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="m365lic-step-actions">
                <button type="button" class="m365lic-btn m365lic-btn--primary" data-step-next="2">Weiter zu Advanced / Expertenoptionen</button>
            </div>
        </section>

        <section class="m365lic-step-panel m365lic-step-card" data-step-panel="2">
            <div class="m365lic-step-panel__head">
                <div>
                    <span class="m365lic-step-panel__eyebrow">Schritt 2</span>
                    <strong>Advanced / Expertenoptionen</strong>
                    <p>Ergänze Collaboration-, Compliance- und Spezialanforderungen. Daraus wird später auch der passende EU-Stack abgeleitet.</p>
                </div>
                <span class="m365lic-pill">Optional</span>
            </div>

            <div class="m365lic-feature-grid m365lic-feature-grid--dense">
                <?php foreach ($stepTwoFeatureKeys as $featureKey): ?>
                    <?php if (!isset($featureDefinitions[$featureKey])) { continue; } ?>
                    <?php $feature = $featureDefinitions[$featureKey]; ?>
                    <label class="m365lic-feature-toggle">
                        <input type="checkbox" data-feature="<?php echo $esc($featureKey); ?>" name="requirements[<?php echo $index; ?>][features][<?php echo $esc($featureKey); ?>]" value="1" <?php echo in_array($featureKey, $requirement['features'] ?? [], true) ? 'checked' : ''; ?>>
                        <span>
                            <strong><?php echo $esc((string) $feature['label']); ?></strong>
                            <small><?php echo $esc((string) $feature['description']); ?></small>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="m365lic-step-actions m365lic-step-actions--split">
                <button type="button" class="m365lic-btn m365lic-btn--ghost" data-step-prev="1">Zurück</button>
                <button type="button" class="m365lic-btn m365lic-btn--primary" data-step-next="3">Weiter zu Add-ons & Security</button>
            </div>
        </section>

        <section class="m365lic-step-panel m365lic-step-card" data-step-panel="3">
            <div class="m365lic-step-panel__head">
                <div>
                    <span class="m365lic-step-panel__eyebrow">Schritt 3</span>
                    <strong>Add-ons & Security</strong>
                    <p>Wähle gezielte Zusatzanforderungen. In der Auswertung werden daraus sowohl Microsoft-Add-ons als auch passende europäische Sicherheits- und Projektmanagement-Alternativen abgeleitet.</p>
                </div>
                <span class="m365lic-pill">Optional</span>
            </div>

            <div class="m365lic-feature-grid m365lic-feature-grid--dense">
                <?php foreach ($stepThreeFeatureKeys as $featureKey): ?>
                    <?php if (!isset($featureDefinitions[$featureKey])) { continue; } ?>
                    <?php $feature = $featureDefinitions[$featureKey]; ?>
                    <?php $groupKey = (string) ($feature['group'] ?? 'productivity'); ?>
                    <?php $groupClass = $addonGroupClassMap[$groupKey] ?? 'productivity'; ?>
                    <label class="m365lic-feature-toggle m365lic-feature-toggle--addon m365lic-feature-toggle--<?php echo $esc($groupClass); ?>">
                        <input type="checkbox" data-feature="<?php echo $esc($featureKey); ?>" name="requirements[<?php echo $index; ?>][features][<?php echo $esc($featureKey); ?>]" value="1" <?php echo in_array($featureKey, $requirement['features'] ?? [], true) ? 'checked' : ''; ?>>
                        <span>
                            <b class="m365lic-feature-group"><?php echo $esc(ucfirst($groupClass)); ?></b>
                            <strong><?php echo $esc((string) $feature['label']); ?></strong>
                            <small><?php echo $esc((string) $feature['description']); ?></small>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="m365lic-step-actions m365lic-step-actions--split">
                <button type="button" class="m365lic-btn m365lic-btn--ghost" data-step-prev="2">Zurück</button>
                <button type="button" class="m365lic-btn m365lic-btn--ghost" data-step-next="1">Fertig</button>
            </div>
        </section>
    </article>
    <?php
};
?>
<?php $theme->getHeader(['title' => $themeTitle]); ?>

<script>
    window.cmsM365LicPresets = <?php echo json_encode($presets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>

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
                        <span class="m365lic-pill">Mehrere Bedarfsgruppen</span>
                    </div>
                </div>
                <div class="m365lic-hero-panel">
                    <div class="m365lic-hero-panel__kicker">So funktioniert’s</div>
                    <ul class="m365lic-hero-list">
                        <li>Bedarf wie in der Standard-Auswertung in 3 Schritten erfassen</li>
                        <li>M365-Empfehlung automatisch aus dem Katalog ermitteln</li>
                        <li>Erst nach dem Submit den passenden EU-Stack anzeigen</li>
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
                        <h2>EU-Vergleich vorbereiten</h2>
                        <p>Lege zunächst Laufzeit und Vergleichsmodus fest. Danach erfasst du den Bedarf je Benutzergruppe im bekannten 3-Schritt-Schema.</p>
                    </div>
                    <div class="m365lic-card-badge">Ergebnis erst nach Auswertung</div>
                </div>

                <nav class="m365lic-local-nav" aria-label="Public M365 Menü">
                    <a href="<?php echo $esc($publicBaseUrl); ?>" class="m365lic-local-nav__link">🧮 Auswertung</a>
                    <a href="<?php echo $esc($euPageUrl); ?>" class="m365lic-local-nav__link m365lic-local-nav__link--active" aria-current="page">🇪🇺 EU-Vergleich</a>
                </nav>

                <form method="POST" id="m365licForm" class="m365lic-form m365lic-eu-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $esc($csrfToken); ?>">
                    <input type="hidden" name="evaluation_csrf_token" value="<?php echo $esc($evaluationToken); ?>">
                    <input type="hidden" name="requirements_payload" id="m365licRequirementsPayload" value="">

                    <section class="m365lic-subcard m365lic-subcard--billing">
                        <div class="m365lic-subcard__head">
                            <div>
                                <h3>Grundmodell</h3>
                                <p>Der EU-Stack wird auf derselben Laufzeit wie die Microsoft-Auswertung gerechnet. Du entscheidest nur noch, ob eher ein Komplett-Workspace oder ein Best-of-Breed-Stack verglichen wird.</p>
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
                                <small class="m365lic-help-text">Vor der Auswertung werden keine Anbieterlisten gezeigt. Die passenden EU-Alternativen erscheinen erst im Ergebnisbereich.</small>
                            </div>
                        </div>
                    </section>

                    <section class="m365lic-subcard m365lic-subcard--requirements">
                        <div class="m365lic-subcard__head">
                            <div>
                                <h3>Bedarfsgruppen</h3>
                                <p>Erfasse mehrere Benutzergruppen im gleichen Schema wie die Standard-Auswertung. Daraus leiten wir links die M365-Empfehlung und rechts die passenden EU-Alternativen ab.</p>
                            </div>
                        </div>

                        <div class="m365lic-requirements" id="m365licRequirements">
                            <?php foreach ($requirements as $index => $requirement): ?>
                                <?php $renderRequirementRow($requirement, (int) $index); ?>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <div class="m365lic-actions">
                        <button type="button" class="m365lic-btn m365lic-btn--ghost" id="m365licAddRow">➕ Weitere Bedarfsgruppe</button>
                        <button type="submit" class="m365lic-btn m365lic-btn--primary">🇪🇺 Vergleich auswerten</button>
                    </div>
                    <small class="m365lic-help-text">Für eine stabile Auswertung werden maximal 25 Bedarfsgruppen pro Anfrage verarbeitet.</small>
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
                        <p>Links die automatisch ermittelte Microsoft-365-Empfehlung je Bedarfsgruppe, rechts der dazu passende europäische Alternativ-Stack.</p>
                    </div>
                    <div class="m365lic-totals">
                        <span class="m365lic-total-chip"><?php echo $esc((string) ($comparisonResult['alternative']['strategy_label'] ?? 'EU-Stack')); ?></span>
                        <span class="m365lic-total-chip"><?php echo $esc((string) ($selectedBilling['short_label'] ?? 'Jahr / jährlich')); ?></span>
                    </div>
                </div>

                <div class="m365lic-compare-grid">
                    <section class="m365lic-compare-panel">
                        <div class="m365lic-compare-panel__head">
                            <h3>M365-Auswertung</h3>
                            <p>Automatisch empfohlene Lizenzkombinationen aus dem Standard-Rechner.</p>
                        </div>

                        <div class="m365lic-result-rows">
                            <?php foreach (($comparisonResult['m365']['rows'] ?? []) as $row): ?>
                            <article class="m365lic-result-row">
                                <div class="m365lic-result-row__head">
                                    <div>
                                        <h3><?php echo $esc((string) ($row['label'] ?? 'Bedarfsgruppe')); ?></h3>
                                        <div class="m365lic-result-row__meta">
                                            <span class="m365lic-total-chip">Anzahl: <?php echo (int) ($row['quantity'] ?? 0); ?></span>
                                            <span class="m365lic-total-chip"><?php echo $esc((string) (($row['audience'] ?? 'knowledge') === 'frontline' ? 'Frontline / Kiosk' : 'Knowledge Worker')); ?></span>
                                            <span class="m365lic-total-chip">Summe: <?php echo $formatMoney($row['row_total'] ?? null); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <p class="m365lic-result-row__explanation"><?php echo $esc((string) ($row['explanation'] ?? '')); ?></p>
                                <?php if (!empty($row['items'])): ?>
                                <div class="users-table-container">
                                    <table class="m365lic-table m365lic-table--compare">
                                        <thead>
                                            <tr>
                                                <th>Lizenz</th>
                                                <th>Typ</th>
                                                <th>Preis</th>
                                                <th>Gesamt</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (($row['items'] ?? []) as $item): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $esc((string) ($item['name'] ?? '')); ?></strong>
                                                    <div class="m365lic-muted"><?php echo $esc((string) ($item['billing_cycle_label'] ?? '')); ?></div>
                                                </td>
                                                <td><?php echo $esc((string) ($item['type_label'] ?? '')); ?></td>
                                                <td><?php echo $formatMoney($item['unit_price'] ?? null); ?></td>
                                                <td><?php echo $formatMoney($item['line_total'] ?? null); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </article>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="m365lic-compare-panel">
                        <div class="m365lic-compare-panel__head">
                            <h3>EU-Alternativen</h3>
                            <p><?php echo $esc((string) ($comparisonResult['alternative']['strategy_label'] ?? 'EU-Stack')); ?> je Bedarfsgruppe auf derselben Laufzeitbasis.</p>
                        </div>

                        <div class="m365lic-result-rows">
                            <?php foreach (($comparisonResult['alternative']['rows'] ?? []) as $row): ?>
                            <article class="m365lic-result-row">
                                <div class="m365lic-result-row__head">
                                    <div>
                                        <h3><?php echo $esc((string) ($row['label'] ?? 'Bedarfsgruppe')); ?></h3>
                                        <div class="m365lic-result-row__meta">
                                            <span class="m365lic-total-chip">Anzahl: <?php echo (int) ($row['quantity'] ?? 0); ?></span>
                                            <span class="m365lic-total-chip">EU-Stack</span>
                                            <span class="m365lic-total-chip">Summe: <?php echo $formatMoney($row['row_total'] ?? null); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php if (!empty($row['items'])): ?>
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
                                            <?php foreach (($row['items'] ?? []) as $item): ?>
                                            <tr>
                                                <td><strong><?php echo $esc((string) ($item['category_label'] ?? '')); ?></strong></td>
                                                <td><?php echo $esc((string) ($item['provider'] ?? '')); ?></td>
                                                <td><?php echo $esc((string) ($item['focus'] ?? '')); ?></td>
                                                <td><?php echo $formatMoney($item['unit_price'] ?? null); ?></td>
                                                <td><?php echo $formatMoney($item['line_total'] ?? null); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </article>
                            <?php endforeach; ?>
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

                <?php if (!empty($comparisonResult['m365']['has_missing_prices'])): ?>
                <div class="m365lic-alert m365lic-alert--warning">
                    ⚠️ Für mindestens eine Microsoft-Empfehlung fehlt ein gepflegter Preis. Dadurch bleibt die M365-Gesamtsumme teilweise offen.
                </div>
                <?php endif; ?>

                <?php if (!empty($comparisonResult['alternative']['has_missing_prices'])): ?>
                <div class="m365lic-alert m365lic-alert--warning">
                    ⚠️ Für mindestens eine europäische Alternative fehlt für das gewählte Laufzeitmodell ein Preis. Dadurch bleibt die Alternativen-Gesamtsumme teilweise offen.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php $theme->getFooter(); ?>