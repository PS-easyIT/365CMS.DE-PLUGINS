<?php
/**
 * Public Template: M365 Backup-Kosten-Rechner.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static function (mixed $value, string $currency = 'USD'): string {
    $amount = number_format((float) $value, 2, ',', '.');
    return $currency === 'EUR' ? $amount . ' €' : $amount . ' USD';
};
$fmtGb = static function (mixed $value): string {
    $number = (float) $value;
    if ($number >= 1024) {
        return number_format($number / 1024, 2, ',', '.') . ' TB';
    }

    return number_format($number, 1, ',', '.') . ' GB';
};
$isSelected = static fn(string $left, string $right): string => $left === $right ? ' selected' : '';
$isChecked = static fn(array $values, string $value): string => in_array($value, $values, true) ? ' checked' : '';
$input = is_array($input ?? null) ? $input : CMS_M365CALCULATOR_Backup_Cost_Calculator::default_input();
$result = is_array($result ?? null) ? $result : CMS_M365CALCULATOR_Backup_Cost_Calculator::evaluate($input);
$storage = is_array($result['storage'] ?? null) ? $result['storage'] : [];
$microsoft = is_array($result['microsoft'] ?? null) ? $result['microsoft'] : [];
$recommendation = is_array($result['recommendation'] ?? null) ? $result['recommendation'] : [];
$selectedWorkloads = array_values(array_map('strval', $input['workloads'] ?? []));
$selectedProviders = array_values(array_map('strval', $input['providers'] ?? []));

if (class_exists('CMS\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'M365 Backup-Kosten-Rechner']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-backup-page" id="m365-backup-kostenrechner">
    <header class="m365calc-hero">
        <p class="phinit-overline">Backup-Kosten</p>
        <section class="m365calc-hero__content" aria-labelledby="m365backup-title">
            <section>
                <h1 id="m365backup-title">M365 Backup-Kosten-Rechner</h1>
                <p class="phinit-prose">Berechnet die offizielle Microsoft-365-Backup-Baseline pro geschütztem GB und vergleicht sie mit manuell gepflegten Providerwerten für Kosten, Workloads, Restore-Tiefe, Retention und Betriebsmodell.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Speicher- und Lizenztools">
                <a class="phinit-btn phinit-btn--secondary" href="/m365-addon-matrix">Add-on-Matrix</a>
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenz-audit-checkliste">Audit-Checkliste</a>
            </nav>
        </section>
    </header>

    <section class="m365calc-layout m365calc-layout--backup">
        <section class="phinit-card" aria-labelledby="m365backup-form-title">
            <header class="m365calc-section__head">
                <section>
                    <h2 id="m365backup-form-title">Backup-Szenario eingeben</h2>
                    <p>Trage Datenmenge, Schutzanteil und gewünschte Aufbewahrung ein, um Microsoft-Baseline und Providervergleich zu berechnen.</p>
                </section>
            </header>

            <form method="GET" class="m365calc-form m365calc-backup-form">
                <fieldset class="m365calc-fieldset">
                    <legend>Datenmenge</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365backup-users">
                            Nutzer / Postfächer
                            <input class="phinit-input" type="number" id="m365backup-users" name="users" min="1" max="500000" value="<?php echo (int) ($input['users'] ?? 1); ?>">
                        </label>
                        <label class="phinit-field" for="m365backup-exchange">
                            Exchange-Daten in GB
                            <input class="phinit-input" type="number" id="m365backup-exchange" name="exchange_gb" min="0" max="5000000" step="0.1" value="<?php echo $esc($input['exchange_gb'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365backup-onedrive">
                            OneDrive-Daten in GB
                            <input class="phinit-input" type="number" id="m365backup-onedrive" name="onedrive_gb" min="0" max="5000000" step="0.1" value="<?php echo $esc($input['onedrive_gb'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365backup-sharepoint">
                            SharePoint-Daten in GB
                            <input class="phinit-input" type="number" id="m365backup-sharepoint" name="sharepoint_gb" min="0" max="5000000" step="0.1" value="<?php echo $esc($input['sharepoint_gb'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365backup-teams">
                            Teams-Dateien in GB
                            <input class="phinit-input" type="number" id="m365backup-teams" name="teams_gb" min="0" max="5000000" step="0.1" value="<?php echo $esc($input['teams_gb'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365backup-deleted">
                            Gelöschte / versionierte Daten in GB
                            <input class="phinit-input" type="number" id="m365backup-deleted" name="deleted_versioned_gb" min="0" max="5000000" step="0.1" value="<?php echo $esc($input['deleted_versioned_gb'] ?? 0); ?>">
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Schutzumfang</legend>
                    <section class="m365calc-choice-grid">
                        <?php foreach (($result['workload_options'] ?? []) as $key => $label): ?>
                        <label class="m365calc-choice">
                            <input type="checkbox" name="workloads[]" value="<?php echo $esc($key); ?>"<?php echo $isChecked($selectedWorkloads, (string) $key); ?>>
                            <span>
                                <strong><?php echo $esc($label); ?></strong>
                                <small><?php echo $key === 'teams' ? 'Teams-Dateien werden im Microsoft-Modell teilweise über SharePoint und OneDrive betrachtet.' : 'Datenmenge wird in die geschützte GB-Baseline einbezogen.'; ?></small>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </section>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365backup-protection-percent">
                            Schutzanteil in Prozent
                            <input class="phinit-input" type="number" id="m365backup-protection-percent" name="protection_percent" min="1" max="100" value="<?php echo (int) ($input['protection_percent'] ?? 100); ?>">
                        </label>
                        <label class="phinit-field" for="m365backup-growth">
                            Wachstum in 12 Monaten in Prozent
                            <input class="phinit-input" type="number" id="m365backup-growth" name="growth_percent" min="0" max="500" step="0.1" value="<?php echo $esc($input['growth_percent'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365backup-retention">
                            Gewünschte Aufbewahrung in Monaten
                            <input class="phinit-input" type="number" id="m365backup-retention" name="retention_months" min="1" max="240" value="<?php echo (int) ($input['retention_months'] ?? 12); ?>">
                        </label>
                        <label class="phinit-field" for="m365backup-restore-depth">
                            Restore-Tiefe
                            <select class="phinit-select" id="m365backup-restore-depth" name="restore_depth">
                                <?php foreach (($result['restore_depth_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['restore_depth'] ?? 'advanced'), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365backup-trust">
                            Trust-Boundary-Gewichtung
                            <select class="phinit-select" id="m365backup-trust" name="trust_priority">
                                <?php foreach (($result['trust_priority_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['trust_priority'] ?? 'high'), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365backup-ops">
                            Betriebsmodell
                            <select class="phinit-select" id="m365backup-ops" name="operational_preference">
                                <?php foreach (($result['operational_preference_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['operational_preference'] ?? 'first_party'), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Vergleichsanbieter</legend>
                    <section class="m365calc-choice-grid m365calc-choice-grid--compact">
                        <?php foreach (($result['provider_options'] ?? []) as $key => $label): ?>
                        <label class="m365calc-choice">
                            <input type="checkbox" name="providers[]" value="<?php echo $esc($key); ?>"<?php echo $isChecked($selectedProviders, (string) $key); ?>>
                            <span>
                                <strong><?php echo $esc($label); ?></strong>
                                <small><?php echo $key === 'microsoft-365-backup' ? 'Offizielle Microsoft-Baseline.' : 'Manuell gepflegter Vergleichswert.'; ?></small>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </section>
                </fieldset>

                <section class="m365calc-actions">
                    <button type="submit" class="phinit-btn phinit-btn--primary">Backup-Kosten berechnen</button>
                    <a class="phinit-btn phinit-btn--secondary" href="/m365-backup-kostenrechner">Zurücksetzen</a>
                </section>
            </form>
        </section>

        <aside class="m365calc-aside" aria-labelledby="m365backup-result-title">
            <article class="phinit-result m365calc-result-card" id="m365calc-result" tabindex="-1" data-m365calc-result>
                <header>
                    <p class="phinit-overline">Microsoft-Baseline</p>
                    <h2 id="m365backup-result-title"><?php echo $money($microsoft['monthly'] ?? 0, (string) ($microsoft['currency'] ?? 'USD')); ?>/Monat</h2>
                    <p><?php echo $fmtGb($storage['protected_gb'] ?? 0); ?> geschützt · <?php echo (int) ($input['protection_percent'] ?? 100); ?> Prozent Schutzanteil</p>
                </header>
                <section class="m365calc-price-stack" aria-label="Microsoft Backup Kosten">
                    <article><span>Listenpreis</span><strong><?php echo $money($microsoft['unit_price'] ?? 0, (string) ($microsoft['currency'] ?? 'USD')); ?>/GB</strong></article>
                    <article><span>Pro Jahr</span><strong><?php echo $money($microsoft['yearly'] ?? 0, (string) ($microsoft['currency'] ?? 'USD')); ?></strong></article>
                    <article><span>Mit Wachstum</span><strong><?php echo $money($microsoft['projected_monthly'] ?? 0, (string) ($microsoft['currency'] ?? 'USD')); ?>/Monat</strong></article>
                    <article><span>Quellenstand</span><strong><?php echo $esc($result['meta']['source_checked'] ?? '2026-05-17'); ?></strong></article>
                </section>
                <footer class="m365calc-actions">
                    <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Drucken / PDF speichern</button>
                </footer>
            </article>

            <article class="phinit-note phinit-note--info">
                <p class="phinit-overline">Empfehlung</p>
                <h2><?php echo $esc($recommendation['label'] ?? 'Backup-Szenario prüfen'); ?></h2>
                <p><?php echo $esc($recommendation['text'] ?? ''); ?></p>
            </article>
        </aside>
    </section>

    <section class="m365calc-summary-grid" aria-label="Backup Kennzahlen">
        <article class="phinit-card m365calc-mini-card">
            <span>Rohdaten</span>
            <strong><?php echo $fmtGb($storage['raw_gb'] ?? 0); ?></strong>
            <p>Ausgewählte Workloads plus gelöschte oder versionierte Daten.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Geschützt</span>
            <strong><?php echo $fmtGb($storage['protected_gb'] ?? 0); ?></strong>
            <p>Berechnete Datenmenge nach Schutzanteil.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Günstigster Anbieter</span>
            <strong><?php echo $esc($result['kpis']['cheapest']['short'] ?? 'n/a'); ?></strong>
            <p><?php echo $money($result['kpis']['cheapest']['monthly'] ?? 0, (string) ($result['kpis']['cheapest']['currency'] ?? 'USD')); ?>/Monat.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Stärkster Score</span>
            <strong><?php echo $esc($result['kpis']['strongest']['short'] ?? 'n/a'); ?></strong>
            <p><?php echo number_format((float) ($result['kpis']['strongest']['score'] ?? 0), 1, ',', '.'); ?> Punkte im Modell.</p>
        </article>
    </section>

    <section class="phinit-result m365calc-result-card" aria-labelledby="m365backup-table-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Providervergleich</p>
                <h2 id="m365backup-table-title">Kosten, Abdeckung und Schutzgrad</h2>
                <p>Microsoft-Werte sind offiziell gepflegt; Drittanbieterwerte sind manuelle Vergleichsannahmen.</p>
            </section>
        </header>
        <section class="phinit-table-wrap m365calc-addon-table-wrap" aria-label="Backup Provider Vergleichstabelle">
            <table class="phinit-table m365calc-addon-table">
                <thead>
                    <tr>
                        <th scope="col">Anbieter</th>
                        <th scope="col">Quelle</th>
                        <th scope="col" class="phinit-num">Monat</th>
                        <th scope="col" class="phinit-num">Jahr</th>
                        <th scope="col">Workloads</th>
                        <th scope="col">Retention</th>
                        <th scope="col">Restore</th>
                        <th scope="col">Betrieb</th>
                        <th scope="col" class="phinit-num">Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($result['providers'] ?? []) as $provider): ?>
                    <?php if (!is_array($provider)) { continue; } ?>
                    <tr>
                        <th scope="row"><?php echo $esc($provider['name'] ?? ''); ?></th>
                        <td><?php echo (string) ($provider['data_source'] ?? '') === 'official_microsoft' ? 'offiziell' : 'manuell gepflegt'; ?></td>
                        <td class="phinit-num"><?php echo $money($provider['monthly'] ?? 0, (string) ($provider['currency'] ?? 'USD')); ?></td>
                        <td class="phinit-num"><?php echo $money($provider['yearly'] ?? 0, (string) ($provider['currency'] ?? 'USD')); ?></td>
                        <td><?php echo $esc($provider['coverage']['label'] ?? ''); ?></td>
                        <td><?php echo $esc($provider['retention']['label'] ?? ''); ?></td>
                        <td><?php echo $esc($provider['restore']['label'] ?? ''); ?></td>
                        <td><?php echo $esc($provider['trust']['label'] ?? ''); ?></td>
                        <td class="phinit-num"><?php echo number_format((float) ($provider['score'] ?? 0), 1, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </section>

    <section class="m365calc-result-grid" aria-label="Backup Details">
        <?php foreach (($result['providers'] ?? []) as $provider): ?>
        <?php if (!is_array($provider)) { continue; } ?>
        <article class="phinit-card m365calc-plan-card">
            <header>
                <p class="phinit-overline"><?php echo $esc($provider['short'] ?? 'Provider'); ?></p>
                <h2><?php echo $esc($provider['name'] ?? ''); ?></h2>
                <p><?php echo $esc($provider['source_note'] ?? ''); ?></p>
            </header>
            <section class="m365calc-result-grid">
                <section>
                    <h3>Stärken</h3>
                    <ul class="m365calc-note-list">
                        <?php foreach (($provider['strengths'] ?? []) as $item): ?>
                        <li><?php echo $esc($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <section>
                    <h3>Grenzen</h3>
                    <ul class="m365calc-note-list">
                        <?php foreach (($provider['limits'] ?? []) as $item): ?>
                        <li><?php echo $esc($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            </section>
        </article>
        <?php endforeach; ?>
    </section>

    <?php if (!empty($result['faq'])): ?>
    <section class="phinit-card m365calc-result-card" aria-labelledby="m365backup-faq-title">
        <header class="m365calc-section__head">
            <section>
                <p class="phinit-overline">FAQ</p>
                <h2 id="m365backup-faq-title">Häufige Fragen zu M365 Backup-Kosten</h2>
            </section>
        </header>
        <section class="m365calc-faq-list">
            <?php foreach (($result['faq'] ?? []) as $item): ?>
            <?php if (!is_array($item)) { continue; } ?>
            <details>
                <summary><?php echo $esc($item['question'] ?? ''); ?></summary>
                <p><?php echo $esc($item['answer'] ?? ''); ?></p>
            </details>
            <?php endforeach; ?>
        </section>
    </section>
    <?php endif; ?>

    <section class="phinit-note phinit-note--info m365calc-source-card" aria-labelledby="m365backup-sources-title">
        <h2 id="m365backup-sources-title">Quellenstand</h2>
        <p><?php echo $esc($result['meta']['price_basis'] ?? ''); ?></p>
        <p><?php echo $esc($result['meta']['provider_policy'] ?? ''); ?></p>
        <details>
            <summary>Quellen anzeigen</summary>
            <ul class="m365calc-note-list">
                <?php foreach (($result['sources'] ?? []) as $source): ?>
                <li><a href="<?php echo $esc($source); ?>" target="_blank" rel="noopener noreferrer"><?php echo $esc($source); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>
    </section>
</main>

<?php
if (class_exists('CMS\ThemeManager')) {
    \CMS\ThemeManager::instance()->getFooter();
}