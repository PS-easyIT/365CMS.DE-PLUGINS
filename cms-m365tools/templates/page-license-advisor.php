<?php
/**
 * Public Template: M365 Lizenzberater.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static function (mixed $value): string {
    return number_format((float) $value, 2, ',', '.') . ' €';
};
$isChecked = static fn(array $features, string $feature): string => in_array($feature, $features, true) ? ' checked' : '';
$lang = function_exists('cms_plugin_public_language') ? cms_plugin_public_language() : 'de';
$t = static fn(string $de, string $en): string => $lang === 'en' ? $en : $de;
$path = static fn(string $route): string => function_exists('cms_plugin_public_localized_path')
    ? cms_plugin_public_localized_path($route, $lang)
    : ($lang === 'en' ? '/en/' . ltrim($route, '/') : '/' . ltrim($route, '/'));
$statusTone = (string) ($result['status']['tone'] ?? 'info');
$statusClass = in_array($statusTone, ['success', 'warning', 'danger'], true) ? $statusTone : 'accent';
$groupRows = $input['groups'] ?? [];
while (count($groupRows) < 3) {
    $groupRows[] = ['label' => '', 'quantity' => 0, 'persona' => 'knowledge_worker', 'features' => []];
}

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => $t('M365 Lizenzberater', 'M365 License Advisor')]);
}
?>

<main class="phinit-plugin m365calc-page m365lic-advisor" id="m365-license-advisor">
    <header class="m365calc-hero">
        <p class="phinit-overline"><?php echo $esc($t('M365 Lizenzberater', 'M365 License Advisor')); ?></p>
        <section class="m365calc-hero__content" aria-labelledby="m365lic-title">
            <section>
                <h1 id="m365lic-title"><?php echo $esc($t('Microsoft 365 Lizenzberater', 'Microsoft 365 License Advisor')); ?></h1>
                <p class="phinit-prose"><?php echo $esc($t('Erfasst mehrere Nutzergruppen, prueft Basislizenzen, Add-ons und harte Sonderfaelle wie Copilot, Teams Phone und Power Platform.', 'Capture multiple user groups and validate base licenses, add-ons and hard edge cases like Copilot, Teams Phone and Power Platform.')); ?></p>
            </section>
            <nav class="m365calc-actions" aria-label="<?php echo $esc($t('Weitere Rechner', 'Related calculators')); ?>">
                <a class="phinit-btn phinit-btn--secondary" href="<?php echo $esc($path('/copilot-lizenz-check')); ?>"><?php echo $esc($t('Copilot pruefen', 'Check Copilot')); ?></a>
                <a class="phinit-btn phinit-btn--secondary" href="<?php echo $esc($path('/shared-mailbox-vs-lizenz')); ?>"><?php echo $esc($t('Shared Mailbox pruefen', 'Check shared mailbox')); ?></a>
            </nav>
        </section>
    </header>

    <?php if ($notice !== ''): ?>
    <aside class="phinit-note phinit-note--success" role="status"><?php echo $esc($notice); ?></aside>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <aside class="phinit-note phinit-note--danger" role="alert"><?php echo $esc($error); ?></aside>
    <?php endif; ?>

    <section class="m365calc-layout" aria-label="Lizenzberatung Eingabe und Hinweise">
        <section class="phinit-card" aria-labelledby="m365lic-form-title">
            <header class="m365calc-section__head">
                <section>
                    <h2 id="m365lic-form-title">Bedarf erfassen</h2>
                    <p>Bis zu fünf Gruppen sind möglich. Leere Gruppen werden ignoriert.</p>
                </section>
            </header>

            <form method="GET" class="m365calc-form">
                <fieldset class="m365calc-fieldset">
                    <legend>Unternehmen &amp; Vertragslogik</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--3">
                        <label class="phinit-field">
                            Mitarbeitende gesamt
                            <input class="phinit-input" type="number" min="1" max="500000" name="company_size" value="<?php echo (int) ($input['company_size'] ?? 50); ?>">
                        </label>
                        <label class="phinit-field">
                            Kundentyp
                            <select class="phinit-select" name="customer_type">
                                <?php foreach (['smb' => 'KMU', 'midmarket' => 'Midmarket', 'enterprise' => 'Enterprise', 'frontline' => 'Frontline-lastig'] as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo (string) ($input['customer_type'] ?? '') === $key ? ' selected' : ''; ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field">
                            Bezugslogik
                            <select class="phinit-select" name="channel">
                                <?php foreach (['unknown' => 'Unbekannt', 'csp' => 'CSP', 'direct' => 'Direkt', 'ea' => 'EA'] as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo (string) ($input['channel'] ?? '') === $key ? ' selected' : ''; ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field">
                            Laufzeit
                            <select class="phinit-select" name="billing_cycle">
                                <?php foreach (['annual' => 'Jährlich', 'annual_monthly' => 'Jährlich / monatliche Zahlung', 'monthly' => 'Monatlich flexibel', 'three_year' => '36 Monate prüfen'] as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo (string) ($input['billing_cycle'] ?? '') === $key ? ' selected' : ''; ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field">
                            PSTN-Anbieter
                            <select class="phinit-select" name="pstn_provider">
                                <?php foreach (['third_party' => 'Drittanbieter / Operator', 'microsoft' => 'Microsoft Calling Plan', 'none' => 'Keine PSTN-Telefonie'] as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo (string) ($input['pstn_provider'] ?? '') === $key ? ' selected' : ''; ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field">
                            SharePoint-Bedarf in TB
                            <input class="phinit-input" type="number" min="0" max="9999" step="0.1" name="tenant_storage_tb" value="<?php echo $esc((string) ($input['tenant_storage_tb'] ?? '1')); ?>">
                        </label>
                        <label class="phinit-field">
                            <?php echo $esc($t('Lizenzzuweisungsmodell', 'License assignment model')); ?>
                            <select class="phinit-select" name="assignment_model">
                                <?php foreach (['direct' => $t('Direkt je Nutzer', 'Direct per user'), 'mixed' => $t('Gemischt', 'Mixed'), 'group' => $t('Gruppenbasiert', 'Group-based')] as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo (string) ($input['assignment_model'] ?? 'mixed') === $key ? ' selected' : ''; ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field">
                            <?php echo $esc($t('Inaktive lizenzierte Nutzer (%)', 'Inactive licensed users (%)')); ?>
                            <input class="phinit-input" type="number" min="0" max="100" step="1" name="inactive_license_ratio" value="<?php echo (int) ($input['inactive_license_ratio'] ?? 5); ?>">
                        </label>
                    </section>
                </fieldset>

                <?php foreach (array_slice($groupRows, 0, 5) as $index => $group): ?>
                <?php $selectedFeatures = array_values(array_map('strval', $group['features'] ?? [])); ?>
                <fieldset class="m365calc-fieldset">
                    <legend>Gruppe <?php echo (int) $index + 1; ?></legend>
                    <section class="m365calc-form-grid m365calc-form-grid--3">
                        <label class="phinit-field">
                            Name
                            <input class="phinit-input" type="text" maxlength="80" name="group_label[<?php echo (int) $index; ?>]" value="<?php echo $esc($group['label'] ?? ''); ?>" placeholder="z. B. Vertrieb">
                        </label>
                        <label class="phinit-field">
                            Anzahl
                            <input class="phinit-input" type="number" min="0" max="500000" name="group_quantity[<?php echo (int) $index; ?>]" value="<?php echo (int) ($group['quantity'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field">
                            Persona
                            <select class="phinit-select" name="group_persona[<?php echo (int) $index; ?>]">
                                <?php foreach ($personaPresets as $key => $preset): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo (string) ($group['persona'] ?? '') === (string) $key ? ' selected' : ''; ?>><?php echo $esc($preset['label'] ?? $key); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </section>

                    <section class="m365calc-choice-grid" aria-label="Zusätzliche Anforderungen für Gruppe <?php echo (int) $index + 1; ?>">
                        <?php foreach ($featureOptions as $featureKey => $featureLabel): ?>
                        <?php if (in_array($featureKey, ['mail', 'teams', 'office_web', 'onedrive', 'sharepoint', 'frontline'], true)) { continue; } ?>
                        <label class="m365calc-choice">
                            <input type="checkbox" name="features[<?php echo (int) $index; ?>][]" value="<?php echo $esc($featureKey); ?>"<?php echo $isChecked($selectedFeatures, (string) $featureKey); ?>>
                            <span>
                                <strong><?php echo $esc($featureLabel); ?></strong>
                                <small>Zusatzbedarf für diese Nutzergruppe berücksichtigen.</small>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </section>
                </fieldset>
                <?php endforeach; ?>

                <section class="m365calc-actions">
                <button type="submit" class="phinit-btn phinit-btn--primary"><?php echo $esc($t('Empfehlung berechnen', 'Calculate recommendation')); ?></button>
                <button type="reset" class="phinit-btn phinit-btn--secondary" data-m365calc-reset><?php echo $esc($t('Zuruecksetzen', 'Reset')); ?></button>
                </section>
            </form>
        </section>

        <aside class="m365calc-aside" aria-label="Leitplanken">
            <article class="phinit-note phinit-note--info">
                <h2>Was geprüft wird</h2>
                <ul class="m365calc-note-list">
                    <li>Basislizenz je Nutzergruppe</li>
                    <li>Add-ons für Copilot, Phone, Power Platform und Security</li>
                    <li>Business-300-Grenze und New-Commerce-Laufzeit</li>
                    <li>SharePoint-Storage als tenantweite Kapazität</li>
                </ul>
            </article>
            <article class="phinit-note phinit-note--warning">
                <h2>Wichtig</h2>
                <p>Das Ergebnis ist eine technische Lizenzempfehlung. Einkaufskonditionen, Marktverfügbarkeit und Vertrag müssen vor Bestellung geprüft werden.</p>
            </article>
        </aside>
    </section>

    <?php if (is_array($result)): ?>
    <section class="phinit-result m365calc-result-card" id="m365calc-result" tabindex="-1" aria-labelledby="m365lic-result-title" data-m365calc-result>
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Ergebnis</p>
                <h2 id="m365lic-result-title"><?php echo $esc($result['status']['label'] ?? 'Lizenzempfehlung'); ?></h2>
                <p><?php echo $esc($result['billing']['note'] ?? ''); ?></p>
            </section>
            <section class="m365calc-actions">
                <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Drucken / PDF speichern</button>
                <a class="phinit-btn phinit-btn--primary" href="<?php echo $esc($path('/kontakt')); ?>"><?php echo $esc($t('Lizenz-Audit anfragen', 'Request license audit')); ?></a>
            </section>
        </header>

        <section class="m365calc-summary-grid" aria-label="Kostenübersicht">
            <article class="phinit-card m365calc-mini-card phinit-card--<?php echo $esc($statusClass); ?>">
                <span>Status</span>
                <strong><?php echo $esc($result['status']['label'] ?? ''); ?></strong>
            </article>
            <article class="phinit-card m365calc-mini-card">
                <span>Monatlich</span>
                <strong><?php echo $money($result['monthly_total'] ?? 0); ?></strong>
            </article>
            <article class="phinit-card m365calc-mini-card">
                <span>Jährlich</span>
                <strong><?php echo $money($result['annual_total'] ?? 0); ?></strong>
            </article>
            <article class="phinit-card m365calc-mini-card">
                <span>36 Monate</span>
                <strong><?php echo $money($result['three_year_total'] ?? 0); ?></strong>
            </article>
        </section>

        <?php if (!empty($result['risk_signals']['entries']) && is_array($result['risk_signals']['entries'])): ?>
        <aside class="phinit-note phinit-note--warning">
            <h3><?php echo $esc($t('License Assignment Risk Signals', 'License Assignment Risk Signals')); ?></h3>
            <p><?php echo $esc($t('Risikowert', 'Risk score')); ?>: <?php echo (int) ($result['risk_signals']['score'] ?? 0); ?>/100</p>
            <ul class="m365calc-note-list">
                <?php foreach ($result['risk_signals']['entries'] as $signal): ?>
                <?php if (!is_array($signal)) { continue; } ?>
                <li><?php echo $esc((string) ($signal['message'] ?? '')); ?></li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <?php endif; ?>

        <?php foreach ($result['rows'] ?? [] as $row): ?>
        <?php if (!is_array($row)) { continue; } ?>
        <article class="phinit-card m365calc-result-card">
            <header class="m365calc-result-heading">
                <section>
                    <h3><?php echo $esc($row['label'] ?? 'Nutzergruppe'); ?></h3>
                    <p><?php echo (int) ($row['quantity'] ?? 0); ?> Benutzer · <?php echo $esc($row['requirements']['persona_label'] ?? 'Persona'); ?></p>
                </section>
                <section class="m365calc-score">
                    <span>Fit Score</span>
                    <strong><?php echo (int) ($row['primary']['fit_score'] ?? 0); ?>%</strong>
                    <p><?php echo $esc($row['primary']['explanation'] ?? ''); ?></p>
                </section>
            </header>

            <section class="phinit-table-wrap" aria-label="Empfohlene Lizenzen">
                <table class="phinit-table">
                    <thead>
                        <tr>
                            <th>Lizenz</th>
                            <th>Typ</th>
                            <th>Anzahl</th>
                            <th class="phinit-num">Preis</th>
                            <th class="phinit-num">Summe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($row['primary']['items'] ?? [] as $item): ?>
                        <?php if (!is_array($item)) { continue; } ?>
                        <tr>
                            <td><?php echo $esc($item['name'] ?? ''); ?></td>
                            <td><?php echo $esc($item['type_label'] ?? ''); ?></td>
                            <td><?php echo (int) ($item['quantity'] ?? 0); ?></td>
                            <td class="phinit-num"><?php echo $money($item['unit_price'] ?? 0); ?></td>
                            <td class="phinit-num"><?php echo $money($item['line_total'] ?? 0); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <?php if (!empty($row['warnings'])): ?>
            <aside class="phinit-note phinit-note--warning">
                <strong>Hinweise</strong>
                <ul class="m365calc-note-list">
                    <?php foreach ($row['warnings'] as $warning): ?>
                    <li><?php echo $esc($warning); ?></li>
                    <?php endforeach; ?>
                </ul>
            </aside>
            <?php endif; ?>

            <?php if (!empty($row['alternatives'])): ?>
            <section aria-label="Alternativen">
                <h3>Alternativen</h3>
                <section class="m365calc-result-grid">
                    <?php foreach ($row['alternatives'] as $alternative): ?>
                    <?php if (!is_array($alternative)) { continue; } ?>
                    <article class="phinit-card m365calc-mini-card">
                        <span><?php echo (int) ($alternative['fit_score'] ?? 0); ?>% Fit</span>
                        <strong><?php echo $esc($alternative['base']['name'] ?? 'Alternative'); ?></strong>
                        <p><?php echo $money($alternative['monthly_total'] ?? 0); ?> monatlich</p>
                    </article>
                    <?php endforeach; ?>
                </section>
            </section>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>

        <?php if (!empty($result['warnings'])): ?>
        <aside class="phinit-note phinit-note--warning">
            <h3>Übergreifende Hinweise</h3>
            <ul class="m365calc-note-list">
                <?php foreach ($result['warnings'] as $warning): ?>
                <li><?php echo $esc($warning); ?></li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <?php endif; ?>

        <article class="phinit-note phinit-note--info m365calc-source-card">
            <h3>Quellenstand</h3>
            <p>Preise wurden aus dem alten m365lic-Katalog übernommen, wo sinnvoll, und mit den verifizierten Microsoft-Leitplanken kombiniert.</p>
            <details>
                <summary>Quellen anzeigen</summary>
                <ul class="m365calc-note-list">
                    <?php foreach ($result['sources'] ?? [] as $source): ?>
                    <li><a href="<?php echo $esc($source); ?>" target="_blank" rel="noopener noreferrer"><?php echo $esc($source); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        </article>
    </section>
    <?php endif; ?>
</main>

<?php
if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getFooter();
}
