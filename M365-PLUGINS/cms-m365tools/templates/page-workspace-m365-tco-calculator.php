<?php
/**
 * Public Template: Google Workspace / Microsoft 365 TCO-Rechner.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static function (mixed $value, string $currency = 'EUR'): string {
    $amount = number_format((float) $value, 2, ',', '.');
    return $currency === 'EUR' ? $amount . ' €' : $amount . ' ' . $currency;
};
$percent = static fn(mixed $value): string => number_format((float) $value, 1, ',', '.') . ' %';
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
$input = is_array($input ?? null) ? $input : CMS_M365CALCULATOR_Workspace_M365_TCO_Calculator::default_input();
$result = is_array($result ?? null) ? $result : CMS_M365CALCULATOR_Workspace_M365_TCO_Calculator::evaluate($input);
$workspace = is_array($result['workspace'] ?? null) ? $result['workspace'] : [];
$m365 = is_array($result['m365'] ?? null) ? $result['m365'] : [];
$comparison = is_array($result['comparison'] ?? null) ? $result['comparison'] : [];
$migration = is_array($result['migration'] ?? null) ? $result['migration'] : [];
$mapping = is_array($result['mapping'] ?? null) ? $result['mapping'] : [];
$currency = (string) ($result['meta']['currency'] ?? 'EUR');

if (class_exists('CMS\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'Google Workspace zu Microsoft 365 TCO-Rechner']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-workspace-tco-page" id="google-workspace-zu-m365-tco">
    <header class="m365calc-hero">
        <p class="phinit-overline">Plattform-TCO</p>
        <section class="m365calc-hero__content" aria-labelledby="workspace-tco-title">
            <section>
                <h1 id="workspace-tco-title">Google Workspace ↔ Microsoft 365 TCO-Rechner</h1>
                <p class="phinit-prose">Vergleicht Workspace und Microsoft 365 über den gewählten Zeitraum inklusive Lizenzkosten, Migration, Schulung, Change-Aufwand, Hypercare und Parallelbetrieb.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Lizenz- und Migrationsrechner">
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenzvergleich">Lizenzvergleich</a>
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenz-audit-checkliste">Audit-Checkliste</a>
            </nav>
        </section>
    </header>

    <section class="m365calc-layout m365calc-layout--tco">
        <section class="phinit-card" aria-labelledby="workspace-tco-form-title">
            <header class="m365calc-section__head">
                <section>
                    <h2 id="workspace-tco-form-title">Szenario erfassen</h2>
                    <p>Wähle Richtung, Pläne und Projektannahmen, um beide Plattformen wirtschaftlich gegenüberzustellen.</p>
                </section>
            </header>

            <form method="GET" class="m365calc-form m365calc-workspace-tco-form">
                <fieldset class="m365calc-fieldset">
                    <legend>Richtung und Planmapping</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="workspace-tco-direction">
                            Vergleichsrichtung
                            <select class="phinit-select" id="workspace-tco-direction" name="direction">
                                <?php foreach (($result['direction_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['direction'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="workspace-tco-users">
                            Nutzerzahl
                            <input class="phinit-input" type="number" id="workspace-tco-users" name="users" min="1" max="500000" value="<?php echo (int) ($input['users'] ?? 1); ?>">
                        </label>
                        <label class="phinit-field" for="workspace-tco-google-plan">
                            Google Workspace Plan
                            <select class="phinit-select" id="workspace-tco-google-plan" name="workspace_plan">
                                <?php foreach (($result['workspace_plan_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['workspace_plan'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="workspace-tco-m365-plan">
                            Microsoft 365 Plan
                            <select class="phinit-select" id="workspace-tco-m365-plan" name="m365_plan">
                                <?php foreach (($result['m365_plan_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['m365_plan'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="workspace-tco-months">
                            Betrachtungszeitraum
                            <select class="phinit-select" id="workspace-tco-months" name="analysis_months">
                                <?php foreach ([12, 24, 36, 48, 60] as $months): ?>
                                <option value="<?php echo (int) $months; ?>"<?php echo (int) ($input['analysis_months'] ?? 36) === $months ? ' selected' : ''; ?>><?php echo (int) $months; ?> Monate</option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="workspace-tco-google-addons">
                            Google Add-ons je Nutzer / Monat
                            <input class="phinit-input" type="number" id="workspace-tco-google-addons" name="google_addons_monthly_per_user" min="0" max="1000000" step="0.01" value="<?php echo $esc($input['google_addons_monthly_per_user'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="workspace-tco-m365-addons">
                            Microsoft Add-ons je Nutzer / Monat
                            <input class="phinit-input" type="number" id="workspace-tco-m365-addons" name="m365_addons_monthly_per_user" min="0" max="1000000" step="0.01" value="<?php echo $esc($input['m365_addons_monthly_per_user'] ?? 0); ?>">
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Projekt- und Change-Kosten</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="workspace-tco-migration-cost">
                            Migration je Nutzer
                            <input class="phinit-input" type="number" id="workspace-tco-migration-cost" name="migration_cost_per_user" min="0" max="1000000" step="0.01" value="<?php echo $esc($input['migration_cost_per_user'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="workspace-tco-training-cost">
                            Schulung je Nutzer
                            <input class="phinit-input" type="number" id="workspace-tco-training-cost" name="training_cost_per_user" min="0" max="1000000" step="0.01" value="<?php echo $esc($input['training_cost_per_user'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="workspace-tco-change-cost">
                            Change-Aufwand je Nutzer
                            <input class="phinit-input" type="number" id="workspace-tco-change-cost" name="change_cost_per_user" min="0" max="1000000" step="0.01" value="<?php echo $esc($input['change_cost_per_user'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="workspace-tco-base-cost">
                            Projektbasis einmalig
                            <input class="phinit-input" type="number" id="workspace-tco-base-cost" name="project_base_cost" min="0" max="1000000" step="0.01" value="<?php echo $esc($input['project_base_cost'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="workspace-tco-admin-extra">
                            Admin-Mehraufwand / Monat
                            <input class="phinit-input" type="number" id="workspace-tco-admin-extra" name="admin_extra_monthly" min="0" max="1000000" step="0.01" value="<?php echo $esc($input['admin_extra_monthly'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="workspace-tco-hypercare">
                            Hypercare in Monaten
                            <input class="phinit-input" type="number" id="workspace-tco-hypercare" name="hypercare_months" min="0" max="24" value="<?php echo (int) ($input['hypercare_months'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="workspace-tco-parallel-months">
                            Parallelbetrieb in Monaten
                            <input class="phinit-input" type="number" id="workspace-tco-parallel-months" name="parallel_months" min="0" max="24" value="<?php echo (int) ($input['parallel_months'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="workspace-tco-parallel-percent">
                            Parallelkosten-Anteil in Prozent
                            <input class="phinit-input" type="number" id="workspace-tco-parallel-percent" name="parallel_cost_percent" min="0" max="100" step="0.1" value="<?php echo $esc($input['parallel_cost_percent'] ?? 0); ?>">
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Anforderungen</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="workspace-tco-security">
                            Security- und Compliance-Niveau
                            <select class="phinit-select" id="workspace-tco-security" name="security_need">
                                <?php foreach (($result['security_need_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['security_need'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="workspace-tco-storage">
                            Speicherbedarf
                            <select class="phinit-select" id="workspace-tco-storage" name="storage_need">
                                <?php foreach (($result['storage_need_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['storage_need'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="workspace-tco-ai">
                            KI-Anforderung
                            <select class="phinit-select" id="workspace-tco-ai" name="ai_need">
                                <?php foreach (($result['ai_need_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['ai_need'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </section>
                    <section class="m365calc-choice-grid m365calc-choice-grid--compact">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="special_controls_required" value="1"<?php echo $isChecked($input['special_controls_required'] ?? 0); ?>>
                            <span>
                                <strong>Spezielle Enterprise-Kontrollen erforderlich</strong>
                                <small>Datenresidenz, Compliance, sehr hohe Admin- oder Sondervertragsanforderungen.</small>
                            </span>
                        </label>
                        <label class="m365calc-choice">
                            <input type="checkbox" name="google_addons_unknown" value="1"<?php echo $isChecked($input['google_addons_unknown'] ?? 0); ?>>
                            <span>
                                <strong>Google Add-ons noch nicht vollständig bekannt</strong>
                                <small>Voice, AppSheet, Chrome Enterprise, Meet-Hardware oder KI-Zusatzpakete separat bewerten.</small>
                            </span>
                        </label>
                    </section>
                </fieldset>

                <section class="m365calc-actions">
                    <button type="submit" class="phinit-btn phinit-btn--primary">TCO vergleichen</button>
                    <a class="phinit-btn phinit-btn--secondary" href="/google-workspace-zu-m365-tco">Zurücksetzen</a>
                </section>
            </form>
        </section>

        <aside class="m365calc-aside" aria-labelledby="workspace-tco-result-title">
            <article class="phinit-result m365calc-result-card" id="m365calc-result" tabindex="-1" data-m365calc-result>
                <header>
                    <p class="phinit-overline">Empfehlung</p>
                    <h2 id="workspace-tco-result-title"><?php echo $esc($comparison['label'] ?? 'TCO vergleichen'); ?></h2>
                    <p><?php echo $esc($comparison['text'] ?? ''); ?></p>
                </header>
                <section class="m365calc-price-stack" aria-label="TCO Kennzahlen">
                    <article><span>Google Workspace</span><strong><?php echo $money($workspace['total'] ?? 0, $currency); ?></strong></article>
                    <article><span>Microsoft 365</span><strong><?php echo $money($m365['total'] ?? 0, $currency); ?></strong></article>
                    <article><span>Delta</span><strong><?php echo $money($comparison['delta_abs'] ?? 0, $currency); ?></strong></article>
                    <article><span>Break-even</span><strong><?php echo isset($comparison['break_even_months']) && $comparison['break_even_months'] !== null ? (int) $comparison['break_even_months'] . ' Monate' : 'nicht im Modell'; ?></strong></article>
                </section>
                <footer class="m365calc-actions">
                    <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Drucken / PDF speichern</button>
                </footer>
            </article>

            <article class="phinit-note <?php echo $esc($toneClass((string) ($comparison['tone'] ?? 'info'))); ?>">
                <p class="phinit-overline">Mapping</p>
                <h2><?php echo $esc($mapping['workspace_name'] ?? 'Google Workspace'); ?> ↔ <?php echo $esc($mapping['m365_name'] ?? 'Microsoft 365'); ?></h2>
                <p><?php echo $esc($mapping['reason'] ?? ''); ?></p>
            </article>
        </aside>
    </section>

    <section class="m365calc-summary-grid" aria-label="TCO Zusammenfassung">
        <article class="phinit-card m365calc-mini-card">
            <span>Google monatlich</span>
            <strong><?php echo $money($workspace['monthly_total'] ?? 0, $currency); ?></strong>
            <p><?php echo $esc($workspace['plan_name'] ?? ''); ?></p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Microsoft monatlich</span>
            <strong><?php echo $money($m365['monthly_total'] ?? 0, $currency); ?></strong>
            <p><?php echo $esc($m365['plan_name'] ?? ''); ?></p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Projektkosten</span>
            <strong><?php echo $money($migration['total_project_cost'] ?? 0, $currency); ?></strong>
            <p><?php echo $esc($migration['complexity_label'] ?? 'Standardprojekt'); ?> · Faktor <?php echo number_format((float) ($migration['complexity_factor'] ?? 1), 2, ',', '.'); ?></p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Differenz</span>
            <strong><?php echo $percent($comparison['delta_percent'] ?? 0); ?></strong>
            <p>bezogen auf die günstigere Plattform im Modell.</p>
        </article>
    </section>

    <section class="m365calc-result-grid" aria-label="Plattformvergleich">
        <article class="phinit-card m365calc-plan-card">
            <header>
                <p class="phinit-overline">Google Workspace</p>
                <h2><?php echo $esc($workspace['plan_name'] ?? ''); ?></h2>
                <p><?php echo $esc($workspace['summary'] ?? ''); ?></p>
            </header>
            <section class="m365calc-price-stack" aria-label="Google Workspace Kosten">
                <article><span>Listenpreis je Nutzer</span><strong><?php echo $money($workspace['unit_price'] ?? 0, $currency); ?></strong></article>
                <article><span>Lizenzen / Monat</span><strong><?php echo $money($workspace['license_monthly'] ?? 0, $currency); ?></strong></article>
                <article><span>Add-ons / Monat</span><strong><?php echo $money($workspace['addons_monthly'] ?? 0, $currency); ?></strong></article>
                <article><span>Projektanteil</span><strong><?php echo $money($workspace['project_total'] ?? 0, $currency); ?></strong></article>
            </section>
        </article>

        <article class="phinit-card m365calc-plan-card">
            <header>
                <p class="phinit-overline">Microsoft 365</p>
                <h2><?php echo $esc($m365['plan_name'] ?? ''); ?></h2>
                <p><?php echo $esc($m365['summary'] ?? ''); ?></p>
            </header>
            <section class="m365calc-price-stack" aria-label="Microsoft 365 Kosten">
                <article><span>Listenpreis je Nutzer</span><strong><?php echo $money($m365['unit_price'] ?? 0, $currency); ?></strong></article>
                <article><span>Lizenzen / Monat</span><strong><?php echo $money($m365['license_monthly'] ?? 0, $currency); ?></strong></article>
                <article><span>Add-ons / Monat</span><strong><?php echo $money($m365['addons_monthly'] ?? 0, $currency); ?></strong></article>
                <article><span>Projektanteil</span><strong><?php echo $money($m365['project_total'] ?? 0, $currency); ?></strong></article>
            </section>
        </article>
    </section>

    <section class="phinit-result m365calc-result-card" aria-labelledby="workspace-tco-table-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Kostenentwicklung</p>
                <h2 id="workspace-tco-table-title">Kumulierte Kosten über den Zeitraum</h2>
                <p>Projektkosten werden im Zielpfad ab dem ersten Monat berücksichtigt.</p>
            </section>
        </header>
        <section class="phinit-table-wrap" aria-label="TCO Kostenentwicklung Tabelle">
            <table class="phinit-table">
                <thead>
                    <tr>
                        <th scope="col">Monat</th>
                        <th scope="col" class="phinit-num">Google Workspace</th>
                        <th scope="col" class="phinit-num">Microsoft 365</th>
                        <th scope="col" class="phinit-num">Delta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($result['timeline'] ?? []) as $row): ?>
                    <?php if (!is_array($row)) { continue; } ?>
                    <tr>
                        <th scope="row"><?php echo (int) ($row['month'] ?? 0); ?></th>
                        <td class="phinit-num"><?php echo $money($row['workspace_total'] ?? 0, $currency); ?></td>
                        <td class="phinit-num"><?php echo $money($row['m365_total'] ?? 0, $currency); ?></td>
                        <td class="phinit-num"><?php echo $money(abs((float) ($row['m365_total'] ?? 0) - (float) ($row['workspace_total'] ?? 0)), $currency); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </section>

    <section class="m365calc-result-grid" aria-label="Projektannahmen und Leitplanken">
        <article class="phinit-card m365calc-plan-card">
            <header>
                <p class="phinit-overline">Projektannahmen</p>
                <h2>Migration, Schulung und Parallelbetrieb</h2>
            </header>
            <section class="m365calc-price-stack" aria-label="Projektkosten Details">
                <article><span>Einmaliges Projekt</span><strong><?php echo $money($migration['one_time_project_cost'] ?? 0, $currency); ?></strong></article>
                <article><span>Hypercare</span><strong><?php echo $money($migration['admin_extra_cost'] ?? 0, $currency); ?></strong></article>
                <article><span>Parallelbetrieb</span><strong><?php echo $money($migration['parallel_cost'] ?? 0, $currency); ?></strong></article>
                <article><span>Gesamt</span><strong><?php echo $money($migration['total_project_cost'] ?? 0, $currency); ?></strong></article>
            </section>
        </article>

        <article class="phinit-card m365calc-plan-card">
            <header>
                <p class="phinit-overline">Migrationspfad</p>
                <h2>Microsoft-Workspace-Leitplanken</h2>
            </header>
            <ul class="m365calc-note-list">
                <?php foreach ((array) ($result['migration_facts']['native_google_workspace_filters'] ?? []) as $filter): ?>
                <li><?php echo $esc($filter); ?> kann im nativen Microsoft-Pfad als Filter berücksichtigt werden.</li>
                <?php endforeach; ?>
                <li>Standardannahme: <?php echo (int) ($result['migration_facts']['default_concurrent_migrations'] ?? 20); ?> parallele Migrationen und <?php echo (int) ($result['migration_facts']['default_concurrent_incremental_syncs'] ?? 10); ?> inkrementelle Synchronisierungen.</li>
                <li>Nach Konvertierung zur Mailbox bleiben <?php echo (int) ($result['migration_facts']['license_assignment_window_days'] ?? 30); ?> Tage für die Lizenzzuweisung.</li>
            </ul>
        </article>
    </section>

    <?php if (!empty($result['warnings'])): ?>
    <section class="phinit-note phinit-note--warning m365calc-source-card" aria-labelledby="workspace-tco-watch-title">
        <h2 id="workspace-tco-watch-title">Auffälligkeiten</h2>
        <ul class="m365calc-note-list">
            <?php foreach (($result['warnings'] ?? []) as $item): ?>
            <li><?php echo $esc($item); ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <section class="phinit-card m365calc-result-card" aria-labelledby="workspace-tco-next-title">
        <header class="m365calc-section__head">
            <section>
                <p class="phinit-overline">Nächste Schritte</p>
                <h2 id="workspace-tco-next-title">Vom Vergleich zum belastbaren Business Case</h2>
            </section>
        </header>
        <ul class="m365calc-note-list">
            <?php foreach (($result['next_steps'] ?? []) as $item): ?>
            <li><?php echo $esc($item); ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="phinit-note phinit-note--info m365calc-source-card" aria-labelledby="workspace-tco-sources-title">
        <h2 id="workspace-tco-sources-title">Quellenstand</h2>
        <p><?php echo $esc($result['meta']['price_basis'] ?? ''); ?></p>
        <p>Stand: <?php echo $esc($result['meta']['source_checked'] ?? '2026-05-17'); ?></p>
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
