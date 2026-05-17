<?php
/**
 * Public Template: Copilot ROI-Rechner.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static function (mixed $value, string $currency = 'EUR'): string {
    $suffix = $currency === 'EUR' ? ' €' : ' ' . $currency;
    return htmlspecialchars(number_format((float) $value, 2, ',', '.') . $suffix, ENT_QUOTES, 'UTF-8');
};
$number = static fn(mixed $value, int $decimals = 1): string => htmlspecialchars(number_format((float) $value, $decimals, ',', '.'), ENT_QUOTES, 'UTF-8');
$checked = static fn(array $input, string $key): string => !empty($input[$key]) ? ' checked' : '';
$selectedScenario = (string) ($input['scenario'] ?? 'realistic');
$scenarios = is_array($assumptions['scenarios'] ?? null) ? $assumptions['scenarios'] : [];
$currency = (string) ($pricing['meta']['currency'] ?? 'EUR');
$pricingNote = (string) ($pricing['meta']['price_basis'] ?? 'Preise vor Bestellung prüfen.');

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'Copilot ROI-Rechner']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-roi-page" id="copilot-roi-rechner">
    <section class="phinit-card m365calc-hero" aria-labelledby="m365calc-roi-title">
        <p class="m365calc-eyebrow">Microsoft 365 · Business Case</p>
        <header class="m365calc-hero__content">
            <section>
                <h1 id="m365calc-roi-title">Copilot ROI-Rechner</h1>
                <p>Berechnet Wirtschaftlichkeit, Break-even-Minuten und Pilot- oder Rollout-Empfehlung. Lizenz- und Readiness-Gates werden getrennt vom ROI-Modell bewertet.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Copilot Tools">
                <a class="phinit-btn phinit-btn--secondary" href="/copilot-lizenz-check">Lizenz prüfen</a>
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenzberater">Lizenzberater</a>
            </nav>
        </header>
    </section>

    <?php if ($notice !== ''): ?>
    <section class="phinit-note phinit-note--success" role="status" aria-live="polite"><?php echo $esc($notice); ?></section>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <section class="phinit-note phinit-note--danger" role="alert"><?php echo $esc($error); ?></section>
    <?php endif; ?>

    <section class="m365calc-layout" aria-label="Copilot ROI Eingabe">
        <section class="phinit-card" aria-labelledby="m365calc-roi-form-title">
            <header class="m365calc-section__head">
                <section>
                    <h2 id="m365calc-roi-form-title">Business Case erfassen</h2>
                    <p>Die Berechnung zeigt konservative, realistische und optimistische Szenarien. Das ausgewählte Szenario steuert Status, Chart und Empfehlung.</p>
                </section>
            </header>

            <form method="GET" class="m365calc-form" data-m365calc-form>
                <fieldset class="m365calc-fieldset">
                    <legend>Rollout und Zielgruppe</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--3">
                        <section class="phinit-field">
                            <label for="persona">Persona / Abteilung</label>
                            <select id="persona" name="persona" class="phinit-select" required>
                                <?php foreach ($personaOptions as $personaKey => $persona): ?>
                                <option value="<?php echo $esc($personaKey); ?>"<?php echo (string) ($input['persona'] ?? '') === (string) $personaKey ? ' selected' : ''; ?>><?php echo $esc($persona['label'] ?? $personaKey); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </section>
                        <section class="phinit-field">
                            <label for="rollout_mode">Rollout-Modus</label>
                            <select id="rollout_mode" name="rollout_mode" class="phinit-select" required>
                                <option value="pilot"<?php echo (string) ($input['rollout_mode'] ?? '') === 'pilot' ? ' selected' : ''; ?>>Pilot</option>
                                <option value="broad"<?php echo (string) ($input['rollout_mode'] ?? '') === 'broad' ? ' selected' : ''; ?>>Breiter Rollout</option>
                            </select>
                        </section>
                        <section class="phinit-field">
                            <label for="scenario">Bewertungsszenario</label>
                            <select id="scenario" name="scenario" class="phinit-select" required>
                                <?php foreach ($scenarios as $scenarioKey => $scenarioMeta): ?>
                                <option value="<?php echo $esc($scenarioKey); ?>"<?php echo $selectedScenario === (string) $scenarioKey ? ' selected' : ''; ?>><?php echo $esc($scenarioMeta['label'] ?? $scenarioKey); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </section>
                    </section>

                    <section class="m365calc-form-grid m365calc-form-grid--3">
                        <section class="phinit-field">
                            <label for="user_count">Copilot-Nutzer</label>
                            <input id="user_count" name="user_count" class="phinit-input" type="number" min="1" max="500000" value="<?php echo (int) ($input['user_count'] ?? 25); ?>" required>
                        </section>
                        <section class="phinit-field">
                            <label for="adoption_rate">Erwartete Adoption in Prozent</label>
                            <input id="adoption_rate" name="adoption_rate" class="phinit-input" type="number" min="0" max="100" step="0.1" value="<?php echo $esc((string) ($input['adoption_rate'] ?? 65)); ?>" required>
                        </section>
                        <section class="phinit-field">
                            <label for="ramp_up_months">Ramp-up in Monaten</label>
                            <input id="ramp_up_months" name="ramp_up_months" class="phinit-input" type="number" min="0" max="12" value="<?php echo (int) ($input['ramp_up_months'] ?? 3); ?>" required>
                        </section>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Wirtschaftliche Annahmen</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--3">
                        <section class="phinit-field">
                            <label for="license_price_monthly">Copilot-Lizenzpreis / User / Monat</label>
                            <input id="license_price_monthly" name="license_price_monthly" class="phinit-input" type="number" min="0" step="0.01" value="<?php echo $esc((string) ($input['license_price_monthly'] ?? 28.10)); ?>" required>
                        </section>
                        <section class="phinit-field">
                            <label for="hourly_cost">Vollkosten-Stundensatz</label>
                            <input id="hourly_cost" name="hourly_cost" class="phinit-input" type="number" min="1" step="0.01" value="<?php echo $esc((string) ($input['hourly_cost'] ?? 65)); ?>" required>
                        </section>
                        <section class="phinit-field">
                            <label for="minutes_saved_per_day">Zeitgewinn / Tag in Minuten</label>
                            <input id="minutes_saved_per_day" name="minutes_saved_per_day" class="phinit-input" type="number" min="0" max="240" step="0.1" value="<?php echo $esc((string) ($input['minutes_saved_per_day'] ?? 18)); ?>" required>
                        </section>
                    </section>

                    <section class="m365calc-form-grid m365calc-form-grid--3">
                        <section class="phinit-field">
                            <label for="workdays_per_month">Arbeitstage / Monat</label>
                            <input id="workdays_per_month" name="workdays_per_month" class="phinit-input" type="number" min="1" max="31" value="<?php echo (int) ($input['workdays_per_month'] ?? 20); ?>" required>
                        </section>
                        <section class="phinit-field">
                            <label for="one_time_enablement_cost">Einmalige Enablement-Kosten</label>
                            <input id="one_time_enablement_cost" name="one_time_enablement_cost" class="phinit-input" type="number" min="0" step="0.01" value="<?php echo $esc((string) ($input['one_time_enablement_cost'] ?? 2500)); ?>">
                        </section>
                        <section class="phinit-field">
                            <label for="monthly_enablement_cost">Laufende Enablement-Kosten / Monat</label>
                            <input id="monthly_enablement_cost" name="monthly_enablement_cost" class="phinit-input" type="number" min="0" step="0.01" value="<?php echo $esc((string) ($input['monthly_enablement_cost'] ?? 0)); ?>">
                        </section>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Lizenz- und Readiness-Gate</legend>
                    <section class="m365calc-choice-grid">
                        <?php foreach ([
                            'base_license_eligible' => ['Berechtigte Basislizenz vorhanden', 'Ohne geeignete Basislizenz kein belastbarer Copilot-ROI.'],
                            'has_entra_account' => ['Microsoft Entra-Konto vorhanden', 'Work-/School-Account ist Voraussetzung.'],
                            'has_primary_exchange_mailbox' => ['Primäres Exchange-Online-Postfach', 'Shared-, Group- und Archive-Mailboxes reichen nicht.'],
                            'm365_apps_deployed' => ['Microsoft 365 Apps ausgerollt', 'App-Erlebnisse brauchen aktuelle M365 Apps.'],
                            'onedrive_enabled' => ['OneDrive aktiviert', 'Datei- und OneDrive-Erlebnisse werden sonst begrenzt.'],
                            'privacy_controls_reviewed' => ['Privacy Controls geprüft', 'Connected Experiences dürfen Copilot nicht blockieren.'],
                            'network_ready' => ['Netzwerk bereit', 'M365-Endpunkte und WebSockets sind erreichbar.'],
                            'teams_meeting_usecase' => ['Teams-Meeting-Nutzen relevant', 'Meeting-Zusammenfassungen sind Teil des Business Case.'],
                            'teams_transcription_enabled' => ['Teams Transkription / Recording aktiv', 'Für Meeting- und Teams-Phone-Kontext erforderlich.'],
                            'teams_phone_pstn_usecase' => ['Teams Phone PSTN relevant', 'Erzeugt Zusatzhinweis zu Phone, Calling Plan und PSTN.'],
                        ] as $field => $meta): ?>
                        <input type="hidden" name="<?php echo $esc($field); ?>" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="<?php echo $esc($field); ?>" value="1"<?php echo $checked($input, (string) $field); ?>>
                            <span>
                                <strong><?php echo $esc($meta[0]); ?></strong>
                                <small><?php echo $esc($meta[1]); ?></small>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </section>
                </fieldset>

                <footer class="m365calc-actions">
                    <button type="submit" class="phinit-btn phinit-btn--primary">ROI berechnen</button>
                    <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-reset>Defaults wiederherstellen</button>
                </footer>
            </form>
        </section>

        <aside class="m365calc-aside" aria-label="ROI Leitplanken">
            <section class="phinit-card">
                <h2>So liest du das Modell</h2>
                <ul class="m365calc-note-list">
                    <li>ROI ist ein Annahmenmodell, keine Microsoft-Garantie.</li>
                    <li>Break-even zeigt die nötigen Minuten Zeitgewinn pro Tag.</li>
                    <li>Adoption reduziert den realisierten Nutzen.</li>
                    <li>Ramp-up verteilt den Nutzen im ersten Jahr schrittweise.</li>
                </ul>
            </section>
            <section class="phinit-note phinit-note--info">
                <h2>Preisannahme</h2>
                <p><?php echo $esc($pricingNote); ?></p>
                <p>Quellenstand: <?php echo $esc($pricing['meta']['source_checked_at'] ?? '2026-05-16'); ?></p>
            </section>
        </aside>
    </section>

    <?php if (is_array($result)): ?>
    <?php
    $statusTone = (string) ($result['status']['tone'] ?? 'info');
    $statusTone = in_array($statusTone, ['success', 'warning', 'danger', 'info'], true) ? $statusTone : 'info';
    $selected = is_array($result['selected'] ?? null) ? $result['selected'] : [];
    ?>
    <section class="m365calc-result-card" id="m365calc-result" data-m365calc-result aria-labelledby="m365calc-roi-result-title">
        <section class="phinit-note phinit-note--<?php echo $esc($statusTone); ?>">
            <header class="m365calc-result-heading">
                <section>
                    <p class="m365calc-eyebrow">Ergebnis</p>
                    <h2 id="m365calc-roi-result-title"><?php echo $esc($result['status']['label'] ?? 'Copilot ROI'); ?></h2>
                    <p><?php echo $esc($result['recommendation'][0] ?? 'Business Case und Readiness prüfen.'); ?></p>
                </section>
                <section class="m365calc-score" aria-label="Readiness Score">
                    <span>Readiness</span>
                    <strong><?php echo (int) ($result['readiness']['score'] ?? 0); ?>%</strong>
                    <div class="m365calc-score__bar"><span style="--m365calc-score-width: <?php echo (int) ($result['readiness']['score'] ?? 0); ?>%;"></span></div>
                </section>
            </header>
        </section>

        <section class="m365calc-summary-grid" aria-label="ROI Kennzahlen">
            <article class="phinit-result m365calc-mini-card">
                <span>Monatlicher Produktivitätswert</span>
                <strong><?php echo $money($selected['monthly_productivity_gain'] ?? 0, $currency); ?></strong>
            </article>
            <article class="phinit-result m365calc-mini-card">
                <span>Netto-ROI / Jahr</span>
                <strong><?php echo $money($selected['annual_net_roi'] ?? 0, $currency); ?></strong>
            </article>
            <article class="phinit-result m365calc-mini-card">
                <span>Break-even / Tag</span>
                <strong><?php echo $number($selected['break_even_minutes_per_day'] ?? 0); ?> Min.</strong>
            </article>
            <article class="phinit-result m365calc-mini-card">
                <span>Payback</span>
                <strong><?php echo $selected['payback_months'] === null ? 'nicht erreicht' : $number($selected['payback_months'], 1) . ' Monate'; ?></strong>
            </article>
        </section>

        <section class="m365calc-result-grid">
            <section class="phinit-card">
                <h3>Szenarien</h3>
                <section class="phinit-table-wrap">
                    <table class="phinit-table">
                        <thead>
                            <tr>
                                <th>Szenario</th>
                                <th class="phinit-num">Effektive Minuten</th>
                                <th class="phinit-num">Monatlicher Netto-Lauf</th>
                                <th class="phinit-num">ROI Jahr 1</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($result['scenarios'] ?? []) as $scenario): ?>
                            <?php if (!is_array($scenario)) { continue; } ?>
                            <tr>
                                <td><?php echo $esc($scenario['label'] ?? ''); ?></td>
                                <td class="phinit-num"><?php echo $number($scenario['effective_minutes_saved'] ?? 0); ?></td>
                                <td class="phinit-num"><?php echo $money($scenario['monthly_net_runrate'] ?? 0, $currency); ?></td>
                                <td class="phinit-num"><?php echo $number($scenario['roi_percent'] ?? 0); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            </section>

            <section class="phinit-card">
                <h3>Readiness-Status</h3>
                <?php if (!empty($result['readiness']['blockers'])): ?>
                <ul class="m365calc-note-list">
                    <?php foreach ($result['readiness']['blockers'] as $item): ?>
                    <li><strong><?php echo $esc($item['label'] ?? ''); ?>:</strong> <?php echo $esc($item['message'] ?? ''); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p>Keine harten Readiness-Blocker gemeldet.</p>
                <?php endif; ?>

                <?php if (!empty($result['readiness']['warnings'])): ?>
                <h4>Warnungen</h4>
                <ul class="m365calc-note-list">
                    <?php foreach ($result['readiness']['warnings'] as $item): ?>
                    <li><strong><?php echo $esc($item['label'] ?? ''); ?>:</strong> <?php echo $esc($item['message'] ?? ''); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </section>
        </section>

        <section class="phinit-card m365calc-roi-chart" aria-labelledby="m365calc-roi-chart-title">
            <header class="m365calc-section__head">
                <section>
                    <h3 id="m365calc-roi-chart-title">Kosten vs. Produktivitätsgewinn über 12 Monate</h3>
                    <p>Der Chart zeigt kumulierte Werte des ausgewählten Szenarios inklusive Ramp-up und Einmalkosten.</p>
                </section>
            </header>
            <section class="m365calc-chart" aria-label="ROI Chart">
                <?php foreach (($result['chart']['rows'] ?? []) as $row): ?>
                <?php if (!is_array($row)) { continue; } ?>
                <article class="m365calc-chart__row">
                    <span>Monat <?php echo (int) ($row['month'] ?? 0); ?></span>
                    <section class="m365calc-chart__bars" aria-label="Monat <?php echo (int) ($row['month'] ?? 0); ?> Werte">
                        <span class="m365calc-chart__bar m365calc-chart__bar--gain" style="--m365calc-chart-width: <?php echo $esc($row['gain_width'] ?? 0); ?>%;">Gewinn <?php echo $money($row['gain'] ?? 0, $currency); ?></span>
                        <span class="m365calc-chart__bar m365calc-chart__bar--cost" style="--m365calc-chart-width: <?php echo $esc($row['cost_width'] ?? 0); ?>%;">Kosten <?php echo $money($row['cost'] ?? 0, $currency); ?></span>
                        <span class="m365calc-chart__bar <?php echo !empty($row['net_positive']) ? 'm365calc-chart__bar--net-positive' : 'm365calc-chart__bar--net-negative'; ?>" style="--m365calc-chart-width: <?php echo $esc($row['net_width'] ?? 0); ?>%;">Netto <?php echo $money($row['net'] ?? 0, $currency); ?></span>
                    </section>
                </article>
                <?php endforeach; ?>
            </section>
        </section>

        <section class="m365calc-result-grid">
            <section class="phinit-card">
                <h3>So liest du das Ergebnis</h3>
                <ul class="m365calc-note-list">
                    <li>Ein positiver Jahres-ROI bedeutet: Produktivitätswert übersteigt Lizenz- und Enablement-Kosten im ersten Jahr.</li>
                    <li>Break-even-Minuten zeigen, wie viel Zeit pro adoptiertem Nutzer täglich realistisch freiwerden muss.</li>
                    <li>Payback bewertet, wann Einmalkosten aus dem monatlichen Netto-Lauf zurückverdient sind.</li>
                    <li>Unrealistische Adoption ist der häufigste Grund für „Papier-ROI“.</li>
                </ul>
            </section>

            <section class="phinit-card">
                <h3>Nächste Schritte</h3>
                <ol class="m365calc-note-list">
                    <?php foreach (($result['recommendation'] ?? []) as $step): ?>
                    <li><?php echo $esc($step); ?></li>
                    <?php endforeach; ?>
                </ol>
                <footer class="m365calc-actions">
                    <button type="button" class="phinit-btn phinit-btn--primary" data-m365calc-print>Druck/PDF erzeugen</button>
                    <a class="phinit-btn phinit-btn--secondary" href="/kontakt">Copilot-Pilot anfragen</a>
                </footer>
            </section>
        </section>
    </section>
    <?php endif; ?>

    <section class="phinit-card m365calc-source-card">
        <h2>Quellenstand & Annahmen</h2>
        <p>Quellenprüfung: 16.05.2026. Preise, Rabatte und Vertragskonditionen müssen vor Bestellung im jeweiligen Kundenvertrag geprüft werden.</p>
        <details>
            <summary>Microsoft-Quellen anzeigen</summary>
            <ul class="m365calc-note-list">
                <?php foreach (($pricing['sources'] ?? []) as $source): ?>
                <li><a href="<?php echo $esc($source); ?>" target="_blank" rel="noopener noreferrer"><?php echo $esc($source); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>
    </section>
</main>

<?php
if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getFooter();
}
