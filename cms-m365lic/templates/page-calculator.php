<?php
/**
 * Public Template: M365 Lizenzberater
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(?string $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$currency = strtoupper((string) ($settings['default_currency'] ?? 'EUR'));
$formatMoney = static function ($value) use ($esc, $currency): string {
    if ($value === null || $value === '') {
        return 'offen';
    }

    return $esc(number_format((float) $value, 2, ',', '.')) . ' €';
};
$themeTitle = (string) ($viewContext['title'] ?? $settings['page_title'] ?? 'Microsoft 365 Lizenzberater');
$exportToken = class_exists('CMS\Security') ? \CMS\Security::instance()->generateToken('m365lic_export') : bin2hex(random_bytes(16));
$theme = \CMS\ThemeManager::instance();
$isEmbedded = !empty($viewContext['embedded']);
$introText = (string) ($viewContext['intro'] ?? $settings['page_intro'] ?? '');
$showHeroPanel = !empty($settings['show_hero_panel']);
$showHeroBadges = !empty($settings['show_hero_badges']);
$showContextSummary = !empty($settings['show_context_summary']);
$showAddonOverview = !empty($settings['show_addon_overview']);
$showLegalCard = !empty($settings['show_legal_card']);
$stickySidebar = !empty($settings['sticky_sidebar']);
$stepOneFeatureKeys = ['mail', 'teams', 'office_web', 'office_desktop', 'terminalserver', 'onedrive', 'sharepoint', 'frontline'];
$stepThreeFeatureKeys = array_values(array_filter(array_keys($featureDefinitions), static function (string $key) use ($featureDefinitions): bool {
    return empty($featureDefinitions[$key]['base']);
}));
$stepTwoFeatureKeys = array_values(array_filter(array_keys($featureDefinitions), static function (string $key) use ($stepOneFeatureKeys, $stepThreeFeatureKeys): bool {
    return !in_array($key, $stepOneFeatureKeys, true) && !in_array($key, $stepThreeFeatureKeys, true);
}));

$addonFeatureMeta = [];
foreach ($stepThreeFeatureKeys as $featureKey) {
    $relatedPackages = array_values(array_filter($packages, static function (array $package) use ($featureKey): bool {
        return !empty($package['is_active'])
            && ($package['kind'] ?? '') === 'addon'
            && in_array($featureKey, array_map('strval', $package['features'] ?? []), true);
    }));

    if ($relatedPackages === []) {
        continue;
    }

    usort($relatedPackages, static function (array $left, array $right): int {
        $leftPrice = $left['public_price'] ?? null;
        $rightPrice = $right['public_price'] ?? null;

        if ($leftPrice === null && $rightPrice === null) {
            return strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
        }
        if ($leftPrice === null) {
            return 1;
        }
        if ($rightPrice === null) {
            return -1;
        }

        return ((float) $leftPrice <=> (float) $rightPrice);
    });

    $addonFeatureMeta[$featureKey] = [
        'packages' => $relatedPackages,
        'min_price' => $relatedPackages[0]['public_price'] ?? null,
        'pricing_basis_label' => ((string) ($relatedPackages[0]['pricing_basis'] ?? 'per_user')) === 'flat_monthly' ? 'Fixpreis / Monat' : 'ab pro Benutzer',
        'source_note' => (string) ($relatedPackages[0]['source_note'] ?? ''),
    ];
}

$addonGroupClassMap = [
    'security-addon' => 'security',
    'security' => 'security',
    'identity' => 'identity',
    'addons' => 'productivity',
    'productivity' => 'productivity',
    'power-platform' => 'productivity',
    'copilot' => 'copilot',
];

$renderRequirementRow = static function (array $requirement, int $index) use ($featureDefinitions, $presets, $esc, $stepOneFeatureKeys, $stepTwoFeatureKeys, $stepThreeFeatureKeys, $addonFeatureMeta, $addonGroupClassMap, $formatMoney): void {
    ?>
    <article class="m365lic-requirement" data-index="<?php echo $index; ?>" data-step="1">
        <div class="m365lic-requirement__head">
            <div>
                <h3>Bedarfsgruppe <?php echo $index + 1; ?></h3>
                <div class="m365lic-requirement__meta">Schrittweise Bedarfserfassung für Basis, Plattform und Add-ons.</div>
            </div>
            <button type="button" class="m365lic-btn m365lic-btn--ghost m365lic-remove-row">Entfernen</button>
        </div>

        <div class="m365lic-stepper" role="tablist" aria-label="Bedarfserfassung">
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
                    <p>Erfasse Benutzergruppe, Einsatzmodell und die wichtigsten Plattformfunktionen für eine schnelle Erstempfehlung.</p>
                </div>
                <span class="m365lic-pill">Pflichtschritt</span>
            </div>

            <div class="m365lic-form-grid m365lic-form-grid--3">
                <div class="m365lic-field">
                    <label for="req_label_<?php echo $index; ?>">Bezeichnung</label>
                    <input id="req_label_<?php echo $index; ?>" type="text" name="requirements[<?php echo $index; ?>][label]" value="<?php echo $esc((string) ($requirement['label'] ?? '')); ?>" placeholder="z. B. Vertrieb / Backoffice / Frontline">
                </div>
                <div class="m365lic-field">
                    <label for="req_qty_<?php echo $index; ?>">Anzahl Benutzer</label>
                    <input id="req_qty_<?php echo $index; ?>" type="number" min="1" name="requirements[<?php echo $index; ?>][quantity]" value="<?php echo (int) ($requirement['quantity'] ?? 1); ?>">
                </div>
                <div class="m365lic-field">
                    <label for="req_audience_<?php echo $index; ?>">Zielgruppe</label>
                    <select id="req_audience_<?php echo $index; ?>" name="requirements[<?php echo $index; ?>][audience]">
                        <option value="knowledge" <?php echo ($requirement['audience'] ?? 'knowledge') === 'knowledge' ? 'selected' : ''; ?>>Knowledge Worker</option>
                        <option value="frontline" <?php echo ($requirement['audience'] ?? '') === 'frontline' ? 'selected' : ''; ?>>Frontline / Kiosk</option>
                    </select>
                </div>
            </div>

            <div class="m365lic-field">
                <label for="req_preset_<?php echo $index; ?>">Preset</label>
                <select id="req_preset_<?php echo $index; ?>" class="m365lic-preset-select" name="requirements[<?php echo $index; ?>][preset]">
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
                    <p>Ergänze Security, Copilot, Collaboration und weitere Zusatzdienste für anspruchsvollere oder spezialisierte Szenarien.</p>
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
                    <p>Wähle gezielte Erweiterungen wie Defender, Entra ID P2, Copilot, Telefonie oder Power Platform, wenn diese zusätzlich benötigt werden.</p>
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
                            <?php if (!empty($addonFeatureMeta[$featureKey])): ?>
                            <em class="m365lic-feature-hint">
                                <?php echo $esc(implode(' · ', array_slice(array_map(static fn(array $package): string => (string) ($package['name'] ?? ''), $addonFeatureMeta[$featureKey]['packages']), 0, 2))); ?>
                                <?php if (count($addonFeatureMeta[$featureKey]['packages']) > 2): ?>
                                    + weitere Optionen
                                <?php endif; ?>
                            </em>
                            <em class="m365lic-feature-price">
                                <?php echo ($addonFeatureMeta[$featureKey]['min_price'] ?? null) !== null ? $esc((string) ($addonFeatureMeta[$featureKey]['pricing_basis_label'] ?? 'ab')) . ' · ' . $formatMoney($addonFeatureMeta[$featureKey]['min_price']) : 'Preis auf Anfrage'; ?>
                            </em>
                            <?php if (($addonFeatureMeta[$featureKey]['source_note'] ?? '') !== ''): ?>
                            <em class="m365lic-feature-source"><?php echo $esc((string) $addonFeatureMeta[$featureKey]['source_note']); ?></em>
                            <?php endif; ?>
                            <?php endif; ?>
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
<?php if (!$isEmbedded): ?>
<?php $theme->getHeader(['title' => $themeTitle]); ?>
<?php endif; ?>

<script>
    window.cmsM365LicPresets = <?php echo json_encode($presets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>

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

<main class="m365lic-main m365lic-page<?php echo $isEmbedded ? ' m365lic-main--embedded' : ''; ?>">
    <header class="m365lic-hero">
        <div class="m365lic-container">
            <div class="m365lic-hero-grid">
                <div>
                    <span class="m365lic-eyebrow">Microsoft 365 · Lizenzplanung</span>
                    <h1><?php echo $esc($themeTitle); ?></h1>
                    <p><?php echo $esc($introText); ?></p>
                    <?php if ($showHeroBadges): ?>
                    <div class="m365lic-hero-pills">
                        <span class="m365lic-pill">Preise in Euro</span>
                        <span class="m365lic-pill"><?php echo $esc((string) ($pricingContext['label'] ?? 'Öffentlich')); ?></span>
                        <span class="m365lic-pill"><?php echo $esc((string) ($selectedBilling['short_label'] ?? 'Jahr / jährlich')); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($showHeroPanel): ?>
                <div class="m365lic-hero-panel">
                    <div class="m365lic-hero-panel__kicker">Tech-Checks</div>
                    <ul class="m365lic-hero-list">
                        <li>Copilot-Voraussetzungen</li>
                        <li>Terminalserver / Shared Activation</li>
                        <li>Add-ons wie Defender & Entra ID P2</li>
                        <li>Public-, Member- und Spezialpreise</li>
                    </ul>
                </div>
                <?php endif; ?>
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

            <div class="m365lic-layout m365lic-layout--stacked">
                <section class="m365lic-card m365lic-card--intro">
                    <div class="m365lic-card__head">
                        <div>
                            <h2>Bedarf erfassen</h2>
                            <p>Mehrere Benutzergruppen kombinieren, Terminalserver sauber berücksichtigen und im dritten Schritt gezielte Add-ons wie Defender oder Entra ID P2 ergänzen.</p>
                        </div>
                        <div class="m365lic-card-badge">EUR · Netto-Richtwerte</div>
                    </div>

                    <form method="POST" id="m365licForm" class="m365lic-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $esc($csrfToken); ?>">
                        <input type="hidden" name="evaluation_csrf_token" value="<?php echo $esc($evaluationToken); ?>">
                        <input type="hidden" name="context_scope" value="<?php echo $esc((string) ($pricingContext['scope'] ?? 'public')); ?>">
                        <input type="hidden" name="requirements_payload" id="m365licRequirementsPayload" value="">

                        <section class="m365lic-subcard m365lic-subcard--billing">
                            <div class="m365lic-subcard__head">
                                <div>
                                    <h3>Laufzeit & Preislogik</h3>
                                    <p>Lege zuerst fest, wie gerechnet werden soll. Danach erfasst du die Bedarfsgruppen Schritt für Schritt.</p>
                                </div>
                            </div>

                            <div class="m365lic-form-grid m365lic-form-grid--2 m365lic-billing-grid">
                                <div class="m365lic-field">
                                <label for="billing_cycle">Laufzeit & Zahlung</label>
                                <select id="billing_cycle" name="billing_cycle">
                                    <?php foreach ($billingOptions as $billingKey => $billingOption): ?>
                                    <option value="<?php echo $esc($billingKey); ?>" <?php echo (($selectedBilling['key'] ?? '') === $billingKey) ? 'selected' : ''; ?>><?php echo $esc((string) ($billingOption['label'] ?? $billingKey)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="m365lic-help-text"><?php echo $esc((string) ($selectedBilling['note'] ?? '')); ?></small>
                                </div>
                                <div class="m365lic-field m365lic-field--info">
                                <label>Preislogik</label>
                                <div class="m365lic-context-chip-wrap">
                                    <span class="m365lic-total-chip"><?php echo $esc((string) ($pricingContext['label'] ?? 'Öffentlich')); ?></span>
                                    <span class="m365lic-total-chip"><?php echo $esc((string) ($selectedBilling['short_label'] ?? 'Jahr / jährlich')); ?></span>
                                </div>
                                <small class="m365lic-help-text">Basispreise stammen aus dem Paketkatalog und werden pro Bereich mit dem gewählten Modell hochgerechnet.</small>
                                </div>
                            </div>
                        </section>

                        <section class="m365lic-subcard m365lic-subcard--requirements">
                            <div class="m365lic-subcard__head">
                                <div>
                                    <h3>Bedarfsgruppen</h3>
                                    <p>Erfasse je Bereich die wichtigsten Basisanforderungen, dann Spezialfunktionen und zum Schluss die gewünschten Add-ons.</p>
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
                            <button type="submit" class="m365lic-btn m365lic-btn--primary">🧮 Auswerten</button>
                        </div>
                        <small class="m365lic-help-text">Für eine stabile Auswertung werden maximal 25 Bedarfsgruppen pro Anfrage verarbeitet.</small>
                    </form>
                </section>

                <aside class="m365lic-aside m365lic-aside--stacked<?php echo $stickySidebar ? ' m365lic-aside--sticky-enabled' : ''; ?>">
                    <?php if ($showContextSummary): ?>
                    <div class="m365lic-card<?php echo $stickySidebar ? ' m365lic-card--sticky' : ''; ?>">
                        <h2>Kontext</h2>
                        <div class="m365lic-kpi-grid">
                            <div class="m365lic-kpi-card">
                                <span>Währung</span>
                                <strong>€ Euro</strong>
                            </div>
                            <div class="m365lic-kpi-card">
                                <span>Presets</span>
                                <strong><?php echo (int) count($presets); ?></strong>
                            </div>
                            <div class="m365lic-kpi-card">
                                <span>Optionen</span>
                                <strong><?php echo (int) count($featureDefinitions); ?></strong>
                            </div>
                            <div class="m365lic-kpi-card">
                                <span>Modell</span>
                                <strong><?php echo $esc((string) ($selectedBilling['short_label'] ?? 'Jahr / jährlich')); ?></strong>
                            </div>
                        </div>
                        <ul class="m365lic-note-list">
                            <li><strong>Zugriff:</strong> <?php echo $esc((string) ($pricingContext['label'] ?? 'Öffentlich')); ?></li>
                            <li><strong>Preismodell:</strong> <?php echo $esc((string) ($viewContext['summary_label'] ?? 'Öffentliche Preise')); ?></li>
                            <li><strong>Laufzeit:</strong> <?php echo $esc((string) ($selectedBilling['label'] ?? '1 Jahr · jährliche Zahlung')); ?></li>
                            <li><strong>Währung:</strong> Euro (EUR)</li>
                            <li><strong>Hinweis:</strong> Alle Werte werden direkt im Plugin als EUR-Basispreise geführt.</li>
                        </ul>
                    </div>
                    <?php endif; ?>
                    <?php if ($showLegalCard): ?>
                    <div class="m365lic-card">
                        <h2>Hinweise</h2>
                        <p><?php echo $esc((string) ($settings['legal_note'] ?? '')); ?></p>
                        <?php if (!empty($settings['upgrade_url'])): ?>
                        <a href="<?php echo $esc((string) $settings['upgrade_url']); ?>" class="m365lic-btn m365lic-btn--ghost">Mehr Volumen / Beratung</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($showAddonOverview): ?>
                    <div class="m365lic-card">
                        <h2>Add-on-Quick-Info</h2>
                        <ul class="m365lic-addon-overview" role="list">
                            <?php foreach ($stepThreeFeatureKeys as $featureKey): ?>
                                <?php if (!isset($featureDefinitions[$featureKey], $addonFeatureMeta[$featureKey])) { continue; } ?>
                                <?php $overviewGroupKey = (string) ($featureDefinitions[$featureKey]['group'] ?? 'productivity'); ?>
                                <?php $overviewGroupClass = $addonGroupClassMap[$overviewGroupKey] ?? 'productivity'; ?>
                                <li class="m365lic-addon-overview__item m365lic-addon-overview__item--<?php echo $esc($overviewGroupClass); ?>">
                                    <b class="m365lic-feature-group"><?php echo $esc(ucfirst($overviewGroupClass)); ?></b>
                                    <strong><?php echo $esc((string) ($featureDefinitions[$featureKey]['label'] ?? $featureKey)); ?></strong>
                                    <span><?php echo ($addonFeatureMeta[$featureKey]['min_price'] ?? null) !== null ? $formatMoney($addonFeatureMeta[$featureKey]['min_price']) : 'Preis offen'; ?></span>
                                    <small><?php echo $esc((string) ($featureDefinitions[$featureKey]['description'] ?? '')); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </aside>
            </div>
        </div>
    </section>

    <?php if (is_array($evaluation)): ?>
    <section class="m365lic-section m365lic-section--soft">
        <div class="m365lic-container">
            <div class="m365lic-card">
                <div class="m365lic-card__head">
                    <div>
                        <h2>Auswertung</h2>
                        <p>Zusammenfassung nach Bedarf und SKU.</p>
                    </div>
                    <div class="m365lic-totals">
                        <span class="m365lic-total-chip">SKUs: <?php echo (int) count($evaluation['totals'] ?? []); ?></span>
                        <span class="m365lic-total-chip"><?php echo $esc((string) (($evaluation['billing']['short_label'] ?? ($selectedBilling['short_label'] ?? 'Jahr / jährlich')))); ?></span>
                        <span class="m365lic-total-chip">Monat: <?php echo ($evaluation['grand_total'] ?? null) !== null ? $formatMoney($evaluation['grand_total']) : 'teilweise offen'; ?></span>
                    </div>
                </div>

                <div class="m365lic-summary-grid">
                    <div class="m365lic-summary-card">
                        <span>Monatssumme</span>
                        <strong><?php echo ($evaluation['grand_total'] ?? null) !== null ? $formatMoney($evaluation['grand_total']) : 'teilweise offen'; ?></strong>
                    </div>
                    <div class="m365lic-summary-card">
                        <span>Empfehlungen</span>
                        <strong><?php echo (int) count($evaluation['rows'] ?? []); ?> Gruppen</strong>
                    </div>
                    <div class="m365lic-summary-card">
                        <span>Abrechnungsmodell</span>
                        <strong><?php echo $esc((string) ($selectedBilling['label'] ?? '1 Jahr · jährliche Zahlung')); ?></strong>
                    </div>
                </div>

                <div class="m365lic-result-rows">
                    <?php foreach (($evaluation['rows'] ?? []) as $row): ?>
                    <article class="m365lic-result-row">
                        <div class="m365lic-result-row__head">
                            <h3><?php echo $esc((string) ($row['label'] ?? 'Bedarf')); ?></h3>
                            <span class="m365lic-total-chip">Anzahl: <?php echo (int) ($row['quantity'] ?? 0); ?></span>
                        </div>
                        <p class="m365lic-result-row__explanation"><?php echo $esc((string) ($row['explanation'] ?? '')); ?></p>
                        <?php if (!empty($row['items'])): ?>
                        <div class="users-table-container">
                            <table class="m365lic-table">
                                <thead>
                                    <tr>
                                        <th>Lizenz</th>
                                        <th>Typ</th>
                                        <th>Abrechnung</th>
                                        <th>Preis</th>
                                        <th>Monat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (($row['items'] ?? []) as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $esc((string) ($item['name'] ?? '')); ?></strong>
                                            <div class="m365lic-muted"><?php echo $esc((string) ($item['pricing_basis_label'] ?? 'pro Benutzer')); ?> · <?php echo $esc((string) ($item['billing_cycle_label'] ?? '')); ?></div>
                                            <?php if (!empty($settings['show_source_notes']) && !empty($item['source_note'])): ?>
                                            <div class="m365lic-muted"><?php echo $esc((string) $item['source_note']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $esc((string) ($item['type_label'] ?? '')); ?></td>
                                        <td><?php echo $esc((string) ($item['quantity_label'] ?? '')); ?></td>
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

                <div class="users-table-container">
                    <table class="m365lic-table">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Typ</th>
                                <th>Abrechnung</th>
                                <th>Einzelpreis</th>
                                <th>Gesamtsumme</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($evaluation['totals'] ?? []) as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $esc((string) ($item['name'] ?? '')); ?></strong>
                                    <div class="m365lic-muted"><?php echo $esc((string) ($item['pricing_basis_label'] ?? 'pro Benutzer')); ?> · <?php echo $esc((string) ($item['billing_cycle_label'] ?? '')); ?></div>
                                </td>
                                <td><?php echo $esc((string) ($item['type_label'] ?? '')); ?></td>
                                <td><?php echo $esc((string) ($item['quantity_label'] ?? '')); ?></td>
                                <td><?php echo $formatMoney($item['unit_price'] ?? null); ?></td>
                                <td><?php echo $formatMoney($item['line_total'] ?? null); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($evaluation['has_missing_prices']) && !empty($settings['show_missing_price_hint'])): ?>
                <div class="m365lic-alert m365lic-alert--warning">
                    ⚠️ Für mindestens ein empfohlenes Paket fehlt ein gepflegter Preis.
                    <?php if (!empty($evaluation['missing_price_packages']) && is_array($evaluation['missing_price_packages'])): ?>
                        Fehlend aktuell für: <?php echo $esc(implode(', ', array_map('strval', $evaluation['missing_price_packages']))); ?>.
                    <?php endif; ?>
                    Bitte den Paketkatalog prüfen.
                </div>
                <?php endif; ?>

                <form method="POST" action="/api/m365lic/export" class="m365lic-export-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $esc($exportToken); ?>">
                    <input type="hidden" name="context_scope" value="<?php echo $esc((string) ($pricingContext['scope'] ?? 'public')); ?>">
                    <input type="hidden" name="billing_cycle" value="<?php echo $esc((string) ($selectedBilling['key'] ?? 'annual_upfront')); ?>">
                    <input type="hidden" name="requirements_json" value="<?php echo $esc(json_encode($requirements, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>">
                    <button type="submit" class="m365lic-btn m365lic-btn--primary">📄 PDF exportieren</button>
                </form>
            </div>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php if (!$isEmbedded): ?>
<?php $theme->getFooter(); ?>
<?php endif; ?>
