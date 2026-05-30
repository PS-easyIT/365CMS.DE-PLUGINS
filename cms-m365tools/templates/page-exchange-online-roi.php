<?php
/**
 * Public Template: On-Premise Exchange zu Exchange Online ROI.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$isSelected = static fn(string $left, string $right): string => $left === $right ? ' selected' : '';
$isChecked = static fn(bool $value): string => $value ? ' checked' : '';
$money = static fn(mixed $value): string => number_format((float) $value, 2, ',', '.') . ' €';
$number = static fn(mixed $value): string => number_format((float) $value, 0, ',', '.');
$input = is_array($input ?? null) ? $input : CMS_M365CALCULATOR_Exchange_Online_ROI_Calculator::default_input();
$result = is_array($result ?? null) ? $result : CMS_M365CALCULATOR_Exchange_Online_ROI_Calculator::evaluate($input);
$onprem = is_array($result['onprem'] ?? null) ? $result['onprem'] : [];
$cloud = is_array($result['cloud'] ?? null) ? $result['cloud'] : [];
$breakEven = is_array($result['break_even'] ?? null) ? $result['break_even'] : [];
$migration = is_array($result['migration'] ?? null) ? $result['migration'] : [];
$score = is_array($result['score'] ?? null) ? $result['score'] : [];
$recommendation = is_array($result['recommendation'] ?? null) ? $result['recommendation'] : [];
$tone = (string) ($recommendation['tone'] ?? 'info');
$tone = in_array($tone, ['success', 'warning', 'danger', 'info'], true) ? $tone : 'info';
$breakEvenText = is_int($breakEven['month'] ?? null) ? ((int) $breakEven['month'] . ' Monate') : 'nicht innerhalb von 60 Monaten';

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'Exchange Online ROI-Rechner']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-exchange-roi-page" id="exchange-online-roi">
    <header class="m365calc-hero">
        <p class="phinit-overline">Exchange Migration Business Case</p>
        <section class="m365calc-hero__content" aria-labelledby="exchange-roi-title">
            <section>
                <h1 id="exchange-roi-title">On-Premise Exchange zu Exchange Online ROI</h1>
                <p class="phinit-prose">Berechne Vollkosten, Break-even und Migrationspfad für den Wechsel von lokalem Exchange zu Exchange Online über 3 und 5 Jahre.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Exchange Tools">
                <a class="phinit-btn phinit-btn--secondary" href="/m365-archive-mailbox-rechner">Archive Mailbox prüfen</a>
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenzvergleich">Lizenzen vergleichen</a>
            </nav>
        </section>
    </header>

    <section class="m365calc-layout" aria-label="Exchange ROI Rechner">
        <section class="phinit-card" aria-labelledby="exchange-roi-form-title">
            <header class="m365calc-section__head">
                <section>
                    <h2 id="exchange-roi-form-title">Ist-Kosten und Zielbild erfassen</h2>
                    <p>Trage Mailbox-Anzahl, Betriebskosten, Refresh-Druck, Projektkosten und geplanten Cloud-Zielplan ein.</p>
                </section>
            </header>

            <form method="GET" class="m365calc-form m365calc-exchange-roi-form">
                <fieldset class="m365calc-fieldset">
                    <legend>Exchange-Umgebung</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--3">
                        <label class="phinit-field" for="exchange-mailboxes">
                            Anzahl Mailboxen
                            <input class="phinit-input" type="number" id="exchange-mailboxes" name="mailboxes" min="1" max="500000" value="<?php echo (int) ($input['mailboxes'] ?? 250); ?>">
                        </label>
                        <label class="phinit-field" for="exchange-average-mailbox">
                            Ø Mailboxgröße in GB
                            <input class="phinit-input" type="number" id="exchange-average-mailbox" name="average_mailbox_gb" min="0.1" max="1000" step="0.1" value="<?php echo $esc($input['average_mailbox_gb'] ?? 18); ?>">
                        </label>
                        <label class="phinit-field" for="exchange-servers">
                            Exchange-Server
                            <input class="phinit-input" type="number" id="exchange-servers" name="servers" min="0" max="500" value="<?php echo (int) ($input['servers'] ?? 2); ?>">
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>CAPEX und Refresh</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--3">
                        <label class="phinit-field" for="exchange-hardware-cost">
                            Server-/Hardwarekosten
                            <input class="phinit-input" type="number" id="exchange-hardware-cost" name="server_hardware_cost" min="0" step="100" value="<?php echo $esc($input['server_hardware_cost'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="exchange-license-cost">
                            Exchange-/Windows-Lizenzen
                            <input class="phinit-input" type="number" id="exchange-license-cost" name="exchange_windows_license_cost" min="0" step="100" value="<?php echo $esc($input['exchange_windows_license_cost'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="exchange-refresh-window">
                            Nächster Hardware-Refresh
                            <select class="phinit-select" id="exchange-refresh-window" name="hardware_refresh_due_months">
                                <?php foreach (($result['refresh_window_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['hardware_refresh_due_months'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Laufende On-Prem-Kosten pro Monat</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--3">
                        <label class="phinit-field" for="exchange-storage-backup">
                            Storage, Backup, Wartung
                            <input class="phinit-input" type="number" id="exchange-storage-backup" name="storage_backup_monthly" min="0" step="10" value="<?php echo $esc($input['storage_backup_monthly'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="exchange-power-hosting">
                            Strom, Hosting, Housing
                            <input class="phinit-input" type="number" id="exchange-power-hosting" name="power_hosting_monthly" min="0" step="10" value="<?php echo $esc($input['power_hosting_monthly'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="exchange-maintenance">
                            Wartungsverträge
                            <input class="phinit-input" type="number" id="exchange-maintenance" name="maintenance_monthly" min="0" step="10" value="<?php echo $esc($input['maintenance_monthly'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="exchange-admin-hours">
                            Admin-Stunden pro Monat
                            <input class="phinit-input" type="number" id="exchange-admin-hours" name="admin_hours_monthly" min="0" step="0.5" value="<?php echo $esc($input['admin_hours_monthly'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="exchange-admin-rate">
                            Interner/Externer Stundensatz
                            <input class="phinit-input" type="number" id="exchange-admin-rate" name="admin_hourly_rate" min="0" step="5" value="<?php echo $esc($input['admin_hourly_rate'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="exchange-ha-dr">
                            HA, DR, Monitoring
                            <input class="phinit-input" type="number" id="exchange-ha-dr" name="ha_dr_monthly" min="0" step="10" value="<?php echo $esc($input['ha_dr_monthly'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="exchange-monitoring-tools">
                            Dritttools pro Monat
                            <input class="phinit-input" type="number" id="exchange-monitoring-tools" name="monitoring_tools_monthly" min="0" step="10" value="<?php echo $esc($input['monitoring_tools_monthly'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="exchange-certificates">
                            Zertifikate pro Jahr
                            <input class="phinit-input" type="number" id="exchange-certificates" name="certificates_annual" min="0" step="10" value="<?php echo $esc($input['certificates_annual'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="exchange-backup-reduction">
                            Cloud Backup-/Toolkosten
                            <input class="phinit-input" type="number" id="exchange-backup-reduction" name="backup_reduction_monthly" min="0" step="10" value="<?php echo $esc($input['backup_reduction_monthly'] ?? 0); ?>">
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Cloud-Zielbild und Migration</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--3">
                        <label class="phinit-field" for="exchange-cloud-plan">
                            Zielplan
                            <select class="phinit-select" id="exchange-cloud-plan" name="cloud_plan">
                                <?php foreach (($result['cloud_plan_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['cloud_plan'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="exchange-migration-method">
                            Geplanter Migrationspfad
                            <select class="phinit-select" id="exchange-migration-method" name="migration_method">
                                <?php foreach (($result['migration_method_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['migration_method'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="exchange-data-quality">
                            Datenqualität
                            <select class="phinit-select" id="exchange-data-quality" name="data_quality">
                                <?php foreach (($result['data_quality_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['data_quality'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="exchange-migration-cost">
                            Migrationskosten einmalig
                            <input class="phinit-input" type="number" id="exchange-migration-cost" name="migration_cost" min="0" step="100" value="<?php echo $esc($input['migration_cost'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="exchange-coexistence-months">
                            Parallelbetrieb in Monaten
                            <input class="phinit-input" type="number" id="exchange-coexistence-months" name="coexistence_months" min="0" max="120" value="<?php echo (int) ($input['coexistence_months'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="exchange-analysis-years">
                            Betrachtungszeitraum
                            <select class="phinit-select" id="exchange-analysis-years" name="analysis_years">
                                <option value="3"<?php echo $isSelected((string) ($input['analysis_years'] ?? ''), '3'); ?>>3 Jahre</option>
                                <option value="5"<?php echo $isSelected((string) ($input['analysis_years'] ?? ''), '5'); ?>>5 Jahre</option>
                            </select>
                        </label>
                    </section>

                    <section class="m365calc-choice-grid" aria-label="Sonderfaktoren">
                        <input type="hidden" name="archive_compliance_required" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="archive_compliance_required" value="1"<?php echo $isChecked(!empty($input['archive_compliance_required'])); ?>>
                            <span>
                                <strong>Archiv, Hold oder Compliance relevant</strong>
                                <small>Archivierung, Aufbewahrung, eDiscovery oder größere Mailboxen sind Teil des Zielbilds.</small>
                            </span>
                        </label>
                        <input type="hidden" name="hybrid_required" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="hybrid_required" value="1"<?php echo $isChecked(!empty($input['hybrid_required'])); ?>>
                            <span>
                                <strong>Koexistenz oder Hybridbetrieb nötig</strong>
                                <small>On-Prem und Exchange Online müssen während der Migration gemeinsam betrieben werden.</small>
                            </span>
                        </label>
                        <input type="hidden" name="public_folders_or_legacy_apps" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="public_folders_or_legacy_apps" value="1"<?php echo $isChecked(!empty($input['public_folders_or_legacy_apps'])); ?>>
                            <span>
                                <strong>Public Folder, Relays oder Legacy-Apps vorhanden</strong>
                                <small>Abhängigkeiten sollten vor dem Vollumstieg separat bewertet werden.</small>
                            </span>
                        </label>
                        <input type="hidden" name="org_wants_fast_exit" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="org_wants_fast_exit" value="1"<?php echo $isChecked(!empty($input['org_wants_fast_exit'])); ?>>
                            <span>
                                <strong>Schneller Vollausstieg gewünscht</strong>
                                <small>Die Organisation bevorzugt ein kurzes Projekt statt längerem Parallelbetrieb.</small>
                            </span>
                        </label>
                    </section>
                </fieldset>

                <section class="m365calc-actions">
                    <button type="submit" class="phinit-btn phinit-btn--primary">ROI berechnen</button>
                    <a class="phinit-btn phinit-btn--secondary" href="/exchange-online-roi">Zurücksetzen</a>
                </section>
            </form>
        </section>

        <aside class="m365calc-aside" aria-labelledby="exchange-roi-result-title">
            <article class="phinit-note phinit-note--<?php echo $esc($tone); ?>" id="m365calc-result" tabindex="-1" data-m365calc-result>
                <p class="phinit-overline">Management-Fazit</p>
                <h2 id="exchange-roi-result-title"><?php echo $esc($recommendation['label'] ?? 'ROI prüfen'); ?></h2>
                <p><?php echo $esc($recommendation['summary'] ?? 'Der Exchange-Online-Business-Case wurde berechnet.'); ?></p>
                <section class="m365calc-score" aria-label="Business Case Score">
                    <span>Business Case Score</span>
                    <strong><?php echo (int) ($recommendation['score'] ?? 0); ?>%</strong>
                    <div class="m365calc-score__bar"><span style="--m365calc-score-width: <?php echo (int) ($recommendation['score'] ?? 0); ?>%;"></span></div>
                </section>
            </article>

            <article class="phinit-result m365calc-result-card">
                <header>
                    <p class="phinit-overline">Break-even</p>
                    <h2><?php echo $esc($breakEvenText); ?></h2>
                    <p>Berechnet mit Zielplan <?php echo $esc($cloud['plan_label'] ?? 'Exchange Online'); ?> und <?php echo (int) ($input['mailboxes'] ?? 0); ?> Mailboxen.</p>
                </header>
                <dl class="m365calc-kpi-list">
                    <div>
                        <dt>On-Prem 3 Jahre</dt>
                        <dd><?php echo $esc($money($onprem['cost_36'] ?? 0)); ?></dd>
                    </div>
                    <div>
                        <dt>Cloud 3 Jahre</dt>
                        <dd><?php echo $esc($money($cloud['cost_36'] ?? 0)); ?></dd>
                    </div>
                    <div>
                        <dt>Delta 3 Jahre</dt>
                        <dd><?php echo $esc($money($breakEven['savings_36'] ?? 0)); ?></dd>
                    </div>
                    <div>
                        <dt>Delta 5 Jahre</dt>
                        <dd><?php echo $esc($money($breakEven['savings_60'] ?? 0)); ?></dd>
                    </div>
                </dl>
                <footer class="m365calc-actions">
                    <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Drucken / PDF speichern</button>
                    <a class="phinit-btn phinit-btn--primary" href="/kontakt">Business Case prüfen lassen</a>
                </footer>
            </article>
        </aside>
    </section>

    <section class="m365calc-summary-grid" aria-label="ROI Kennzahlen">
        <article class="phinit-card m365calc-mini-card">
            <span>On-Prem pro Monat</span>
            <strong><?php echo $esc($money($onprem['monthly_run'] ?? 0)); ?></strong>
            <p><?php echo $esc($money($onprem['cost_per_mailbox_month'] ?? 0)); ?> pro Mailbox und Monat.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Cloud pro Monat</span>
            <strong><?php echo $esc($money($cloud['ongoing_monthly'] ?? 0)); ?></strong>
            <p><?php echo $esc($money($cloud['cost_per_mailbox_month'] ?? 0)); ?> pro Mailbox und Monat.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Migrationsfenster</span>
            <strong><?php echo (int) ($migration['min_days'] ?? 0); ?>–<?php echo (int) ($migration['max_days'] ?? 0); ?> Tage</strong>
            <p><?php echo $esc($migration['profile_label'] ?? 'Mailboxprofil'); ?>, ca. <?php echo (int) ($migration['batches'] ?? 0); ?> Batches.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Risikowert</span>
            <strong><?php echo (int) ($score['risk_score'] ?? 0); ?>%</strong>
            <p>Datenqualität: <?php echo $esc($score['confidence'] ?? 'mittel'); ?>.</p>
        </article>
    </section>

    <section class="m365calc-result-grid" aria-label="Kostenblöcke">
        <article class="phinit-card">
            <h2>Ist-Kosten On-Prem</h2>
            <dl class="m365calc-kpi-list">
                <?php foreach ((array) ($onprem['components'] ?? []) as $label => $value): ?>
                <div>
                    <dt><?php echo $esc($label); ?></dt>
                    <dd><?php echo $esc($money($value)); ?></dd>
                </div>
                <?php endforeach; ?>
                <div>
                    <dt>Refresh-Kosten</dt>
                    <dd><?php echo $esc($money($onprem['refresh_cost'] ?? 0)); ?></dd>
                </div>
            </dl>
        </article>
        <article class="phinit-card">
            <h2>Soll-Kosten Exchange Online</h2>
            <dl class="m365calc-kpi-list">
                <?php foreach ((array) ($cloud['components'] ?? []) as $label => $value): ?>
                <div>
                    <dt><?php echo $esc($label); ?></dt>
                    <dd><?php echo $esc($money($value)); ?></dd>
                </div>
                <?php endforeach; ?>
                <div>
                    <dt>Einmalig und Übergang</dt>
                    <dd><?php echo $esc($money($cloud['one_time_and_transition'] ?? 0)); ?></dd>
                </div>
            </dl>
        </article>
    </section>

    <section class="phinit-result m365calc-result-card" aria-labelledby="exchange-roi-chart-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Verlauf</p>
                <h2 id="exchange-roi-chart-title">3-/5-Jahres-Vergleich</h2>
                <p>Positive Delta-Werte zeigen rechnerisches Einsparpotenzial gegenüber weiterem On-Prem-Betrieb.</p>
            </section>
        </header>
        <section class="phinit-table-wrap" aria-label="ROI Verlaufstabelle">
            <table class="phinit-table">
                <thead>
                    <tr>
                        <th scope="col">Monat</th>
                        <th scope="col">On-Prem kumuliert</th>
                        <th scope="col">Cloud kumuliert</th>
                        <th scope="col">Delta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ((array) ($result['chart_rows'] ?? []) as $row): ?>
                    <?php if (!is_array($row)) { continue; } ?>
                    <tr>
                        <th scope="row"><?php echo (int) ($row['month'] ?? 0); ?></th>
                        <td><?php echo $esc($money($row['onprem'] ?? 0)); ?></td>
                        <td><?php echo $esc($money($row['cloud'] ?? 0)); ?></td>
                        <td><?php echo $esc($money($row['savings'] ?? 0)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </section>

    <section class="m365calc-result-grid" aria-label="Migrationspfad und Management-Fazit">
        <article class="phinit-note phinit-note--info">
            <h2>Migrationspfad</h2>
            <p>Ausgewählt: <?php echo $esc($migration['method_label'] ?? 'Migration'); ?>. Fachlich vorgeschlagen: <?php echo $esc($migration['suggested_method_label'] ?? 'prüfen'); ?>.</p>
            <ul class="m365calc-note-list">
                <li><?php echo $esc($migration['method_guidance'] ?? 'Migrationspfad auf Basis von Größe, Koexistenz und Abhängigkeiten prüfen.'); ?></li>
                <li>Planung mit ca. <?php echo (int) ($migration['batch_size'] ?? 0); ?> Mailboxen je Batch und <?php echo (int) ($migration['batches'] ?? 0); ?> Batches.</li>
                <li>Richtwerte sind Planungshilfen und ersetzen keinen Pilotmove.</li>
            </ul>
        </article>
        <article class="phinit-note phinit-note--<?php echo $esc($tone); ?>">
            <h2>Management-Zusammenfassung</h2>
            <ul class="m365calc-note-list">
                <?php foreach ((array) ($result['management_summary'] ?? []) as $item): ?>
                <li><?php echo $esc($item); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
    </section>

    <?php if (!empty($result['warnings'])): ?>
    <section class="phinit-note phinit-note--warning" aria-labelledby="exchange-roi-warning-title">
        <h2 id="exchange-roi-warning-title">Planungsrisiken</h2>
        <ul class="m365calc-note-list">
            <?php foreach ((array) ($result['warnings'] ?? []) as $warning): ?>
            <li><?php echo $esc($warning); ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <section class="phinit-result m365calc-result-card" aria-labelledby="exchange-roi-plan-table-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Zielpläne</p>
                <h2 id="exchange-roi-plan-table-title">Exchange Online und M365 Alternativen</h2>
                <p>Die Preisannahmen sind Näherungswerte und sollten vor Beschaffung mit aktuellem Vertrag, Laufzeit und Tenant-Segment abgeglichen werden.</p>
            </section>
        </header>
        <section class="phinit-table-wrap" aria-label="Exchange Online Planvergleich">
            <table class="phinit-table">
                <thead>
                    <tr>
                        <th scope="col">Plan</th>
                        <th scope="col">Monat pro Nutzer</th>
                        <th scope="col">Monat Gruppe</th>
                        <th scope="col">Mailbox</th>
                        <th scope="col">Archiv</th>
                        <th scope="col">Geeignet für</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ((array) ($result['plan_rows'] ?? []) as $row): ?>
                    <?php if (!is_array($row)) { continue; } ?>
                    <tr>
                        <th scope="row"><?php echo $esc($row['label'] ?? ''); ?></th>
                        <td><?php echo $esc($money($row['price_month'] ?? 0)); ?></td>
                        <td><?php echo $esc($money($row['group_month'] ?? 0)); ?></td>
                        <td><?php echo (int) ($row['mailbox_gb'] ?? 0); ?> GB</td>
                        <td><?php echo (int) ($row['archive_gb'] ?? 0) >= 1000 ? 'bis 1,5 TB' : ((int) ($row['archive_gb'] ?? 0) . ' GB'); ?></td>
                        <td><?php echo $esc($row['best_for'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </section>

    <section class="phinit-note phinit-note--info m365calc-source-card" aria-labelledby="exchange-roi-sources-title">
        <h2 id="exchange-roi-sources-title">Quellenstand</h2>
        <p>Quellenprüfung: <?php echo $esc($result['meta']['source_checked'] ?? '2026-05-16'); ?>. <?php echo $esc($result['meta']['price_basis'] ?? 'Preisannahmen vor Beschaffung prüfen.'); ?></p>
        <details>
            <summary>Quellen anzeigen</summary>
            <ul class="m365calc-note-list">
                <?php foreach ((array) ($result['sources'] ?? []) as $source): ?>
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
