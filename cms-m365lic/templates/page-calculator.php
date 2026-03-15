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
$currency = (string) ($settings['default_currency'] ?? 'USD');
$themeTitle = (string) ($viewContext['title'] ?? $settings['page_title'] ?? 'Microsoft 365 Lizenzberater');
$exportToken = class_exists('CMS\Security') ? \CMS\Security::instance()->generateToken('m365lic_export') : bin2hex(random_bytes(16));
$theme = \CMS\ThemeManager::instance();
$isEmbedded = !empty($viewContext['embedded']);
$introText = (string) ($viewContext['intro'] ?? $settings['page_intro'] ?? '');

$renderRequirementRow = static function (array $requirement, int $index) use ($featureDefinitions, $presets, $esc): void {
    ?>
    <article class="m365lic-requirement" data-index="<?php echo $index; ?>">
        <div class="m365lic-requirement__head">
            <h3>Bedarfsgruppe <?php echo $index + 1; ?></h3>
            <button type="button" class="m365lic-btn m365lic-btn--ghost m365lic-remove-row">Entfernen</button>
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

        <div class="m365lic-feature-grid">
            <?php foreach ($featureDefinitions as $featureKey => $feature): ?>
            <label class="m365lic-feature-toggle">
                <input type="checkbox" data-feature="<?php echo $esc($featureKey); ?>" name="requirements[<?php echo $index; ?>][features][<?php echo $esc($featureKey); ?>]" value="1" <?php echo in_array($featureKey, $requirement['features'] ?? [], true) ? 'checked' : ''; ?>>
                <span>
                    <strong><?php echo $esc((string) $feature['label']); ?></strong>
                    <small><?php echo $esc((string) $feature['description']); ?></small>
                </span>
            </label>
            <?php endforeach; ?>
        </div>
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

<main class="m365lic-main<?php echo $isEmbedded ? ' m365lic-main--embedded' : ''; ?>">
    <header class="m365lic-hero">
        <div class="m365lic-container">
            <span class="m365lic-eyebrow">Microsoft 365 · Lizenzplanung</span>
            <h1><?php echo $esc($themeTitle); ?></h1>
            <p><?php echo $esc($introText); ?></p>
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

            <div class="m365lic-layout">
                <section class="m365lic-card">
                    <div class="m365lic-card__head">
                        <div>
                            <h2>Bedarf erfassen</h2>
                            <p>Mehrere Benutzergruppen kombinieren, Copilot-Anforderungen berücksichtigen und je Bereich die gewünschte Laufzeit/Zahlungsart serverseitig auswerten.</p>
                        </div>
                    </div>

                    <form method="POST" id="m365licForm" class="m365lic-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $esc($csrfToken); ?>">
                        <input type="hidden" name="evaluation_csrf_token" value="<?php echo $esc($evaluationToken); ?>">
                        <input type="hidden" name="context_scope" value="<?php echo $esc((string) ($pricingContext['scope'] ?? 'public')); ?>">

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

                        <div class="m365lic-requirements" id="m365licRequirements">
                            <?php foreach ($requirements as $index => $requirement): ?>
                                <?php $renderRequirementRow($requirement, (int) $index); ?>
                            <?php endforeach; ?>
                        </div>

                        <div class="m365lic-actions">
                            <button type="button" class="m365lic-btn m365lic-btn--ghost" id="m365licAddRow">➕ Weitere Bedarfsgruppe</button>
                            <button type="submit" class="m365lic-btn m365lic-btn--primary">🧮 Auswerten</button>
                        </div>
                    </form>
                </section>

                <aside class="m365lic-aside">
                    <div class="m365lic-card">
                        <h2>Kontext</h2>
                        <ul class="m365lic-note-list">
                            <li><strong>Zugriff:</strong> <?php echo $esc((string) ($pricingContext['label'] ?? 'Öffentlich')); ?></li>
                            <li><strong>Preismodell:</strong> <?php echo $esc((string) ($viewContext['summary_label'] ?? 'Öffentliche Preise')); ?></li>
                            <li><strong>Laufzeit:</strong> <?php echo $esc((string) ($selectedBilling['label'] ?? '1 Jahr · jährliche Zahlung')); ?></li>
                            <li><strong>Währung:</strong> <?php echo $esc($currency); ?></li>
                            <li><strong>Presets:</strong> <?php echo (int) count($presets); ?></li>
                            <li><strong>Optionen:</strong> <?php echo (int) count($featureDefinitions); ?></li>
                        </ul>
                    </div>
                    <div class="m365lic-card">
                        <h2>Hinweise</h2>
                        <p><?php echo $esc((string) ($settings['legal_note'] ?? '')); ?></p>
                        <?php if (!empty($settings['upgrade_url'])): ?>
                        <a href="<?php echo $esc((string) $settings['upgrade_url']); ?>" class="m365lic-btn m365lic-btn--ghost">Mehr Volumen / Beratung</a>
                        <?php endif; ?>
                    </div>
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
                        <span class="m365lic-total-chip">Monat: <?php echo ($evaluation['grand_total'] ?? null) !== null ? $esc(number_format((float) $evaluation['grand_total'], 2, ',', '.')) . ' ' . $esc($currency) : 'teilweise offen'; ?></span>
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
                                        <td><?php echo ($item['unit_price'] ?? null) !== null ? $esc(number_format((float) $item['unit_price'], 2, ',', '.')) . ' ' . $esc($currency) : 'offen'; ?></td>
                                        <td><?php echo ($item['line_total'] ?? null) !== null ? $esc(number_format((float) $item['line_total'], 2, ',', '.')) . ' ' . $esc($currency) : 'offen'; ?></td>
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
                                <td><?php echo ($item['unit_price'] ?? null) !== null ? $esc(number_format((float) $item['unit_price'], 2, ',', '.')) . ' ' . $esc($currency) : 'offen'; ?></td>
                                <td><?php echo ($item['line_total'] ?? null) !== null ? $esc(number_format((float) $item['line_total'], 2, ',', '.')) . ' ' . $esc($currency) : 'offen'; ?></td>
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
