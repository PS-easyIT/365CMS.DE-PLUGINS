<?php
/**
 * Public Template: Power Platform Kosten-Kalkulator.
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
    return $currency === 'EUR' ? $amount . ' €' : $amount . ' ' . $currency;
};
$isSelected = static fn(string $left, string $right): string => $left === $right ? ' selected' : '';
$isChecked = static fn(mixed $value): string => (int) $value === 1 ? ' checked' : '';
$toneClass = static function (string $tone): string {
    return match ($tone) {
        'success' => 'phinit-note--success',
        'warning' => 'phinit-note--warning',
        'danger' => 'phinit-note--danger',
        default => 'phinit-note--info',
    };
};

$input = is_array($input ?? null) ? $input : CMS_M365CALCULATOR_Power_Platform_Cost_Calculator::default_input();
$result = is_array($result ?? null) ? $result : CMS_M365CALCULATOR_Power_Platform_Cost_Calculator::evaluate($input);
$recommendation = is_array($result['recommendation'] ?? null) ? $result['recommendation'] : [];
$costs = is_array($result['costs'] ?? null) ? $result['costs'] : [];
$seeded = is_array($result['seeded'] ?? null) ? $result['seeded'] : [];
$dataverseFit = is_array($result['dataverse_fit'] ?? null) ? $result['dataverse_fit'] : [];
$options = is_array($result['options'] ?? null) ? $result['options'] : [];
$warnings = is_array($result['warnings'] ?? null) ? $result['warnings'] : [];
$nextSteps = is_array($result['next_steps'] ?? null) ? $result['next_steps'] : [];
$currency = (string) ($result['meta']['currency'] ?? 'USD');

if (class_exists('CMS\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'Power Platform Kosten-Kalkulator']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-power-platform-page" id="power-platform-kosten-kalkulator">
    <header class="m365calc-hero">
        <p class="phinit-overline">Power Platform</p>
        <section class="m365calc-hero__content" aria-labelledby="power-platform-title">
            <section>
                <h1 id="power-platform-title">Power Platform Kosten-Kalkulator</h1>
                <p class="phinit-prose">Bewertet Power Apps, Power Automate, Dataverse for Teams, Power Pages, Copilot Studio, Credits, Storage, Requests und Governance-Kostentreiber in einem gemeinsamen Modell.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Microsoft-365-Rechner">
                <a class="phinit-btn phinit-btn--secondary" href="/m365-addon-konfigurator">Add-on-Konfigurator</a>
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenzberater">Lizenzberater</a>
            </nav>
        </section>
    </header>

    <section class="m365calc-layout m365calc-layout--power-platform">
        <section class="phinit-card" aria-labelledby="power-platform-form-title">
            <header class="m365calc-section__head">
                <section>
                    <h2 id="power-platform-form-title">Szenario erfassen</h2>
                    <p>Trenne enthaltene M365-/Teams-Rechte, Premium-Pfade, Capacity-Modelle und Governance-Risiken sauber voneinander.</p>
                </section>
            </header>

            <form method="GET" class="m365calc-form m365calc-power-platform-form">
                <fieldset class="m365calc-fieldset">
                    <legend>Use Case und Umfang</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="pp-use-case">
                            Primärer Use Case
                            <select class="phinit-select" id="pp-use-case" name="use_case">
                                <?php foreach (($options['use_case'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['use_case'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="pp-users">
                            Betroffene Nutzer
                            <input class="phinit-input" type="number" id="pp-users" name="users" min="1" max="500000" value="<?php echo (int) ($input['users'] ?? 1); ?>">
                        </label>
                        <label class="phinit-field" for="pp-makers">
                            Maker / Admins
                            <input class="phinit-input" type="number" id="pp-makers" name="makers" min="0" max="500000" value="<?php echo (int) ($input['makers'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="pp-environments">
                            Umgebungen
                            <input class="phinit-input" type="number" id="pp-environments" name="environments" min="1" max="10000" value="<?php echo (int) ($input['environments'] ?? 1); ?>">
                        </label>
                        <label class="phinit-field" for="pp-apps-per-user">
                            Apps je Nutzer
                            <input class="phinit-input" type="number" id="pp-apps-per-user" name="apps_per_user" min="0" max="1000" value="<?php echo (int) ($input['apps_per_user'] ?? 1); ?>">
                        </label>
                        <label class="phinit-field" for="pp-scenarios">
                            Szenarien / Fachbereiche
                            <input class="phinit-input" type="number" id="pp-scenarios" name="scenario_count" min="1" max="1000" value="<?php echo (int) ($input['scenario_count'] ?? 1); ?>">
                        </label>
                        <label class="phinit-field" for="pp-months">
                            Betrachtungszeitraum
                            <select class="phinit-select" id="pp-months" name="months">
                                <?php foreach (($options['months'] ?? []) as $key => $label): ?>
                                <option value="<?php echo (int) $key; ?>"<?php echo (int) ($input['months'] ?? 12) === (int) $key ? ' selected' : ''; ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="pp-usage-pattern">
                            Nutzungsmuster
                            <select class="phinit-select" id="pp-usage-pattern" name="usage_pattern">
                                <?php foreach (($options['usage_pattern'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['usage_pattern'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Connectoren, Automation und AI</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="pp-connector-type">
                            Connector-Typ
                            <select class="phinit-select" id="pp-connector-type" name="connector_type">
                                <?php foreach (($options['connector_type'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['connector_type'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="pp-flow-context">
                            Flow-Kontext
                            <select class="phinit-select" id="pp-flow-context" name="flow_context">
                                <?php foreach (($options['flow_context'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['flow_context'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="pp-rpa-mode">
                            RPA-Modus
                            <select class="phinit-select" id="pp-rpa-mode" name="rpa_mode">
                                <?php foreach (($options['rpa_mode'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['rpa_mode'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="pp-rpa-bots">
                            Bots / Capacity-Einheiten
                            <input class="phinit-input" type="number" id="pp-rpa-bots" name="rpa_bots" min="1" max="100000" value="<?php echo (int) ($input['rpa_bots'] ?? 1); ?>">
                        </label>
                        <label class="phinit-field" for="pp-bot-scope">
                            Bot-/Agent-Umfang
                            <select class="phinit-select" id="pp-bot-scope" name="bot_scope">
                                <?php foreach (($options['bot_scope'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['bot_scope'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="pp-copilot-credits">
                            Copilot Credits pro Monat
                            <input class="phinit-input" type="number" id="pp-copilot-credits" name="monthly_copilot_credits" min="0" max="100000000" value="<?php echo (int) ($input['monthly_copilot_credits'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="pp-flow-runs">
                            Flow Runs pro Monat
                            <input class="phinit-input" type="number" id="pp-flow-runs" name="monthly_flow_runs" min="0" max="100000000" value="<?php echo (int) ($input['monthly_flow_runs'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="pp-api-requests">
                            Requests pro Tag gesamt
                            <input class="phinit-input" type="number" id="pp-api-requests" name="api_requests_per_day" min="0" max="1000000000" value="<?php echo (int) ($input['api_requests_per_day'] ?? 0); ?>">
                        </label>
                    </section>
                    <section class="m365calc-choice-grid m365calc-choice-grid--compact">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="ai_builder_required" value="1"<?php echo $isChecked($input['ai_builder_required'] ?? 0); ?>>
                            <span><strong>AI Builder erforderlich</strong><small>Dokumentenverarbeitung, Prognosen, Klassifikation oder ähnliche AI-Funktionen.</small></span>
                        </label>
                        <label class="m365calc-choice">
                            <input type="checkbox" name="azure_subscription" value="1"<?php echo $isChecked($input['azure_subscription'] ?? 0); ?>>
                            <span><strong>Azure-Abrechnung verfügbar</strong><small>Für PAYG-Modelle und schwankende Nutzung relevant.</small></span>
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Dataverse, Website und Governance</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="pp-website-access">
                            Website-Zugriff
                            <select class="phinit-select" id="pp-website-access" name="website_access">
                                <?php foreach (($options['website_access'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['website_access'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="pp-auth-site-users">
                            Angemeldete Website-Nutzer / Monat
                            <input class="phinit-input" type="number" id="pp-auth-site-users" name="authenticated_site_users" min="0" max="100000000" value="<?php echo (int) ($input['authenticated_site_users'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="pp-anon-site-users">
                            Anonyme Website-Besucher / Monat
                            <input class="phinit-input" type="number" id="pp-anon-site-users" name="anonymous_site_users" min="0" max="100000000" value="<?php echo (int) ($input['anonymous_site_users'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="pp-db-gb">
                            Dataverse Database GB zusätzlich
                            <input class="phinit-input" type="number" id="pp-db-gb" name="dataverse_db_gb" min="0" max="1000000" step="0.1" value="<?php echo $esc($input['dataverse_db_gb'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="pp-file-gb">
                            Dataverse File GB zusätzlich
                            <input class="phinit-input" type="number" id="pp-file-gb" name="dataverse_file_gb" min="0" max="1000000" step="0.1" value="<?php echo $esc($input['dataverse_file_gb'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="pp-log-gb">
                            Dataverse Log GB zusätzlich
                            <input class="phinit-input" type="number" id="pp-log-gb" name="dataverse_log_gb" min="0" max="1000000" step="0.1" value="<?php echo $esc($input['dataverse_log_gb'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="pp-process-mining">
                            Process Mining GB
                            <input class="phinit-input" type="number" id="pp-process-mining" name="process_mining_gb" min="0" max="1000000" step="0.1" value="<?php echo $esc($input['process_mining_gb'] ?? 0); ?>">
                        </label>
                    </section>
                    <section class="m365calc-choice-grid m365calc-choice-grid--compact">
                        <label class="m365calc-choice">
                            <input type="hidden" name="teams_only" value="0">
                            <input type="checkbox" name="teams_only" value="1"<?php echo $isChecked($input['teams_only'] ?? 0); ?>>
                            <span><strong>Teams-only geplant</strong><small>Apps, Flows und Daten bleiben im Teams-Kontext.</small></span>
                        </label>
                        <label class="m365calc-choice">
                            <input type="hidden" name="dataverse_for_teams" value="0">
                            <input type="checkbox" name="dataverse_for_teams" value="1"<?php echo $isChecked($input['dataverse_for_teams'] ?? 0); ?>>
                            <span><strong>Dataverse for Teams prüfen</strong><small>Sonderpfad für Teams-nahe Lösungen mit begrenztem Umfang.</small></span>
                        </label>
                        <label class="m365calc-choice">
                            <input type="checkbox" name="outside_teams_use" value="1"<?php echo $isChecked($input['outside_teams_use'] ?? 0); ?>>
                            <span><strong>Nutzung außerhalb Teams</strong><small>Relevant für Upgrade-Pfad und allgemeines Dataverse.</small></span>
                        </label>
                        <label class="m365calc-choice">
                            <input type="checkbox" name="managed_environment" value="1"<?php echo $isChecked($input['managed_environment'] ?? 0); ?>>
                            <span><strong>Managed Environments gewünscht</strong><small>Governance, Reporting und Betriebskontrollen berücksichtigen.</small></span>
                        </label>
                        <label class="m365calc-choice">
                            <input type="checkbox" name="advanced_governance" value="1"<?php echo $isChecked($input['advanced_governance'] ?? 0); ?>>
                            <span><strong>Erweiterte Governance</strong><small>CMK, Customer Lockbox, vNet oder vergleichbare Enterprise-Anforderungen.</small></span>
                        </label>
                    </section>
                </fieldset>

                <section class="m365calc-actions">
                    <button type="submit" class="phinit-btn phinit-btn--primary">Power-Platform-Kosten berechnen</button>
                    <a class="phinit-btn phinit-btn--secondary" href="/power-platform-kosten-kalkulator">Zurücksetzen</a>
                </section>
            </form>
        </section>

        <aside class="m365calc-aside" aria-labelledby="power-platform-result-title">
            <article class="phinit-result m365calc-result-card" id="m365calc-result" tabindex="-1" data-m365calc-result>
                <header>
                    <p class="phinit-overline">Empfehlung</p>
                    <h2 id="power-platform-result-title"><?php echo $esc($recommendation['label'] ?? 'Power Platform Pfad prüfen'); ?></h2>
                    <p><?php echo $esc($recommendation['text'] ?? ''); ?></p>
                </header>
                <section class="m365calc-price-stack" aria-label="Kostenkennzahlen">
                    <article><span>Monatlich</span><strong><?php echo $money($costs['monthly_total'] ?? 0, $currency); ?></strong></article>
                    <article><span>Jährlich</span><strong><?php echo $money($costs['annual_total'] ?? 0, $currency); ?></strong></article>
                    <article><span>Zeitraum</span><strong><?php echo $money($costs['period_total'] ?? 0, $currency); ?></strong></article>
                    <article><span>Größter Treiber</span><strong><?php echo $esc($costs['primary_cost_driver'] ?? 'Keine Zusatzkosten im Modell'); ?></strong></article>
                </section>
                <footer class="m365calc-actions">
                    <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Drucken / PDF speichern</button>
                </footer>
            </article>

            <article class="phinit-note <?php echo $esc($toneClass((string) ($recommendation['tone'] ?? 'info'))); ?>">
                <p class="phinit-overline">Einordnung</p>
                <h2><?php echo $esc($recommendation['use_case_text'] ?? ''); ?></h2>
                <p><?php echo $esc($seeded['summary'] ?? ''); ?></p>
            </article>
        </aside>
    </section>

    <section class="m365calc-summary-grid" aria-label="Power Platform Zusammenfassung">
        <article class="phinit-card m365calc-mini-card">
            <span>Seeded Fit</span>
            <strong><?php echo !empty($seeded['sufficient']) ? 'passt' : 'nicht ausreichend'; ?></strong>
            <p><?php echo $esc($seeded['summary'] ?? ''); ?></p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Dataverse for Teams</span>
            <strong><?php echo !empty($dataverseFit['fits']) ? 'passt' : 'Upgrade prüfen'; ?></strong>
            <p><?php echo $esc($dataverseFit['summary'] ?? ''); ?></p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Score</span>
            <strong><?php echo (int) ($result['score']['value'] ?? 0); ?></strong>
            <p>Positive Werte sprechen für einfache Pfade, negative Werte für Review- und Premium-Treiber.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Quellenstand</span>
            <strong><?php echo $esc($result['meta']['source_checked'] ?? '2026-05-17'); ?></strong>
            <p><?php echo $esc($result['meta']['price_basis'] ?? ''); ?></p>
        </article>
    </section>

    <section class="m365calc-result-grid" aria-label="Kosten und Hinweise">
        <article class="phinit-card">
            <header class="m365calc-section__head">
                <section>
                    <p class="phinit-overline">Kostenblöcke</p>
                    <h2>Monatliche Planung</h2>
                </section>
            </header>
            <section class="m365calc-table-wrap">
                <table class="phinit-table">
                    <thead>
                        <tr>
                            <th scope="col">Position</th>
                            <th scope="col">Menge</th>
                            <th scope="col">Einzelwert</th>
                            <th scope="col">Monatlich</th>
                            <th scope="col">Hinweis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($costs['items'] ?? []) as $item): ?>
                        <tr>
                            <td><?php echo $esc($item['label'] ?? ''); ?></td>
                            <td><?php echo $esc(number_format((float) ($item['quantity'] ?? 0), 2, ',', '.')); ?> <?php echo $esc($item['unit'] ?? ''); ?></td>
                            <td><?php echo $money($item['unit_price'] ?? 0, $currency); ?></td>
                            <td><?php echo $money($item['monthly'] ?? 0, $currency); ?></td>
                            <td><?php echo $esc($item['note'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </article>

        <article class="phinit-card">
            <header class="m365calc-section__head">
                <section>
                    <p class="phinit-overline">Prüfpunkte</p>
                    <h2>Warnungen und nächste Schritte</h2>
                </section>
            </header>
            <?php if ($warnings !== []): ?>
            <ul class="m365calc-list m365calc-list--warning">
                <?php foreach ($warnings as $warning): ?>
                <li><?php echo $esc($warning); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="phinit-prose">Keine harten Premium- oder Governance-Treiber im aktuellen Modell erkannt.</p>
            <?php endif; ?>
            <h3>Nächste Schritte</h3>
            <ol class="m365calc-list">
                <?php foreach ($nextSteps as $step): ?>
                <li><?php echo $esc($step); ?></li>
                <?php endforeach; ?>
            </ol>
        </article>
    </section>

    <section class="phinit-card m365calc-sources" aria-labelledby="power-platform-sources-title">
        <header class="m365calc-section__head">
            <section>
                <p class="phinit-overline">Quellen</p>
                <h2 id="power-platform-sources-title">Microsoft Learn und Preisannahmen</h2>
            </section>
        </header>
        <ul class="m365calc-list">
            <?php foreach (($result['sources'] ?? []) as $source): ?>
            <li><a href="<?php echo $esc($source); ?>" target="_blank" rel="noopener noreferrer"><?php echo $esc($source); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </section>
</main>

<?php
if (class_exists('CMS\ThemeManager')) {
    \CMS\ThemeManager::instance()->getFooter();
}
