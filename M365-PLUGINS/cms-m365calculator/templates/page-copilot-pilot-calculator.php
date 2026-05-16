<?php
/**
 * Public Template: Copilot Pilot-Phase-Rechner.
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
$input = is_array($input ?? null) ? $input : CMS_M365CALCULATOR_Copilot_Pilot_Calculator::default_input();
$result = is_array($result ?? null) ? $result : CMS_M365CALCULATOR_Copilot_Pilot_Calculator::evaluate($input);
$recommendation = is_array($result['recommendation'] ?? null) ? $result['recommendation'] : [];
$readiness = is_array($result['readiness'] ?? null) ? $result['readiness'] : [];
$timeline = is_array($result['timeline'] ?? null) ? $result['timeline'] : [];
$goalOptions = is_array($result['goal_options'] ?? null) ? $result['goal_options'] : CMS_M365CALCULATOR_Copilot_Pilot_Calculator::goal_options();
$oversharingOptions = is_array($result['oversharing_options'] ?? null) ? $result['oversharing_options'] : CMS_M365CALCULATOR_Copilot_Pilot_Calculator::oversharing_options();
$tone = (string) ($recommendation['tone'] ?? 'info');
$tone = in_array($tone, ['success', 'warning', 'danger', 'info'], true) ? $tone : 'info';

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'Copilot Pilot-Phase-Rechner']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-copilot-pilot-page" id="copilot-pilot-rechner">
    <header class="m365calc-hero">
        <p class="phinit-overline">Microsoft 365 Copilot</p>
        <section class="m365calc-hero__content" aria-labelledby="m365pilot-title">
            <section>
                <h1 id="m365pilot-title">Pilot-Phase-Rechner für Copilot</h1>
                <p class="phinit-prose">Plant Pilotgröße, Budgetrahmen, Champion-Bedarf, Readiness und Rollout-Zeitplan für Microsoft 365 Copilot.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Copilot Tools">
                <a class="phinit-btn phinit-btn--secondary" href="/copilot-lizenz-check">Copilot-Lizenz prüfen</a>
                <a class="phinit-btn phinit-btn--secondary" href="/copilot-roi-rechner">ROI berechnen</a>
                <a class="phinit-btn phinit-btn--secondary" href="/ai-pack-vs-copilot-pro">AI-Angebote vergleichen</a>
            </nav>
        </section>
    </header>

    <section class="m365calc-layout" aria-label="Copilot Pilotplanung">
        <section class="phinit-card" aria-labelledby="m365pilot-form-title">
            <header class="m365calc-section__head">
                <section>
                    <h2 id="m365pilot-form-title">Pilotdaten erfassen</h2>
                    <p>Bewerte Organisation, Budget, Zielbild und Readiness, um die passende Pilotphase zu dimensionieren.</p>
                </section>
            </header>

            <form method="GET" class="m365calc-form m365calc-pilot-form">
                <fieldset class="m365calc-fieldset">
                    <legend>Organisation und Budget</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365pilot-org-size">
                            Mitarbeitende gesamt
                            <input class="phinit-input" type="number" id="m365pilot-org-size" name="org_size" min="1" max="500000" value="<?php echo (int) ($input['org_size'] ?? 250); ?>">
                        </label>
                        <label class="phinit-field" for="m365pilot-knowledge-workers">
                            Anteil Knowledge Worker in Prozent
                            <input class="phinit-input" type="number" id="m365pilot-knowledge-workers" name="knowledge_worker_percent" min="1" max="100" value="<?php echo (int) ($input['knowledge_worker_percent'] ?? 60); ?>">
                        </label>
                        <label class="phinit-field" for="m365pilot-departments">
                            Einzubindende Bereiche
                            <input class="phinit-input" type="number" id="m365pilot-departments" name="departments" min="1" max="50" value="<?php echo (int) ($input['departments'] ?? 3); ?>">
                        </label>
                        <label class="phinit-field" for="m365pilot-budget">
                            Monatliches Pilotbudget
                            <input class="phinit-input" type="number" step="0.01" id="m365pilot-budget" name="budget_monthly" min="0" max="10000000" value="<?php echo $esc($input['budget_monthly'] ?? 1500); ?>">
                        </label>
                        <label class="phinit-field" for="m365pilot-price">
                            Copilot-Preisannahme je Nutzer/Monat
                            <input class="phinit-input" type="number" step="0.01" id="m365pilot-price" name="license_price_month" min="0" max="10000" value="<?php echo $esc($input['license_price_month'] ?? 28.10); ?>">
                        </label>
                        <label class="phinit-field" for="m365pilot-goal">
                            Hauptziel der Pilotphase
                            <select class="phinit-select" id="m365pilot-goal" name="pilot_goal">
                                <?php foreach ($goalOptions as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['pilot_goal'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Readiness und Governance</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365pilot-oversharing">
                            Oversharing-Risiko
                            <select class="phinit-select" id="m365pilot-oversharing" name="oversharing_risk">
                                <?php foreach ($oversharingOptions as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['oversharing_risk'] ?? 'medium'), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </section>

                    <section class="m365calc-choice-grid" aria-label="Readiness Faktoren">
                        <input type="hidden" name="eligible_license" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="eligible_license" value="1"<?php echo $isChecked(!empty($input['eligible_license'])); ?>>
                            <span>
                                <strong>Geeignete Basislizenzen vorhanden</strong>
                                <small>Die geplanten Teilnehmenden erfüllen die Lizenzgrundlage für Microsoft 365 Copilot.</small>
                            </span>
                        </label>
                        <input type="hidden" name="apps_network_ready" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="apps_network_ready" value="1"<?php echo $isChecked(!empty($input['apps_network_ready'])); ?>>
                            <span>
                                <strong>Apps, Mailboxen, OneDrive, Teams und Netzwerk bereit</strong>
                                <small>Die Arbeitsumgebung ist technisch vorbereitet und die Microsoft-365-Apps sind aktuell genug.</small>
                            </span>
                        </label>
                        <input type="hidden" name="data_governance_ready" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="data_governance_ready" value="1"<?php echo $isChecked(!empty($input['data_governance_ready'])); ?>>
                            <span>
                                <strong>Datenhygiene und Berechtigungen geprüft</strong>
                                <small>SharePoint, OneDrive, sensible Inhalte, Labels und Zugriffe sind für den Pilot vertretbar vorbereitet.</small>
                            </span>
                        </label>
                        <input type="hidden" name="champions_ready" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="champions_ready" value="1"<?php echo $isChecked(!empty($input['champions_ready'])); ?>>
                            <span>
                                <strong>Champions und Fachbereichsowner benannt</strong>
                                <small>Es gibt Multiplikatoren, die Fragen sammeln und gute Anwendungsfälle sichtbar machen.</small>
                            </span>
                        </label>
                        <input type="hidden" name="feedback_ready" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="feedback_ready" value="1"<?php echo $isChecked(!empty($input['feedback_ready'])); ?>>
                            <span>
                                <strong>Feedback- und Supportkanal aktiv</strong>
                                <small>Nutzende können Erfahrungen, Blocker und Verbesserungsideen strukturiert zurückmelden.</small>
                            </span>
                        </label>
                        <input type="hidden" name="success_metrics_ready" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="success_metrics_ready" value="1"<?php echo $isChecked(!empty($input['success_metrics_ready'])); ?>>
                            <span>
                                <strong>Messkriterien definiert</strong>
                                <small>Adoption, Nutzungsqualität, Zeitgewinn, Zufriedenheit und Rollout-Blocker werden bewertet.</small>
                            </span>
                        </label>
                    </section>
                </fieldset>

                <section class="m365calc-actions">
                    <button type="submit" class="phinit-btn phinit-btn--primary">Pilotphase berechnen</button>
                    <a class="phinit-btn phinit-btn--secondary" href="/copilot-pilot-rechner">Zurücksetzen</a>
                </section>
            </form>
        </section>

        <aside class="m365calc-aside" aria-labelledby="m365pilot-result-title">
            <article class="phinit-note phinit-note--<?php echo $esc($tone); ?>" id="m365calc-result" tabindex="-1" data-m365calc-result>
                <p class="phinit-overline"><?php echo $esc($recommendation['label'] ?? 'Empfehlung'); ?></p>
                <h2 id="m365pilot-result-title"><?php echo $esc($recommendation['stage_label'] ?? 'Pilotphase planen'); ?></h2>
                <p><?php echo $esc($recommendation['reason'] ?? 'Die Pilotphase wurde bewertet.'); ?></p>
                <section class="m365calc-score" aria-label="Readiness Score">
                    <span>Readiness Score</span>
                    <strong><?php echo (int) ($readiness['score'] ?? 0); ?>%</strong>
                    <div class="m365calc-score__bar"><span style="--m365calc-score-width: <?php echo (int) ($readiness['score'] ?? 0); ?>%;"></span></div>
                </section>
            </article>

            <article class="phinit-result m365calc-result-card">
                <header>
                    <p class="phinit-overline">Pilotumfang</p>
                    <h2><?php echo (int) ($recommendation['users'] ?? 0); ?> Nutzer</h2>
                    <p><?php echo (int) ($recommendation['duration_weeks'] ?? 0); ?> Wochen · <?php echo (int) ($recommendation['champions'] ?? 1); ?> Champions · <?php echo $esc($recommendation['representativeness'] ?? 'mittel'); ?> repräsentativ</p>
                </header>
                <dl class="m365calc-kpi-list">
                    <div>
                        <dt>Monatliche Lizenzannahme</dt>
                        <dd><?php echo $esc($money($recommendation['monthly_cost'] ?? 0)); ?></dd>
                    </div>
                    <div>
                        <dt>Pilotzeitraum</dt>
                        <dd><?php echo $esc($money($recommendation['pilot_cost'] ?? 0)); ?></dd>
                    </div>
                    <div>
                        <dt>Knowledge Worker</dt>
                        <dd><?php echo (int) ($recommendation['knowledge_workers'] ?? 0); ?></dd>
                    </div>
                </dl>
                <?php if (empty($recommendation['budget_fit'])): ?>
                <p class="m365calc-muted">Budgetlücke pro Monat: <?php echo $esc($money($recommendation['budget_gap'] ?? 0)); ?>. Prüfe Rollenmix, Laufzeit oder kleinere Startgruppe.</p>
                <?php endif; ?>
                <footer class="m365calc-actions">
                    <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Drucken / PDF speichern</button>
                    <a class="phinit-btn phinit-btn--primary" href="/kontakt">Pilot begleiten lassen</a>
                </footer>
            </article>
        </aside>
    </section>

    <?php if (!empty($readiness['missing']) || !empty($readiness['oversharing']['message'])): ?>
    <section class="phinit-note phinit-note--warning" aria-labelledby="m365pilot-readiness-title">
        <h2 id="m365pilot-readiness-title">Readiness-Einordnung</h2>
        <p><?php echo $esc($readiness['oversharing']['message'] ?? 'Daten- und Governance-Lage vor Pilotstart prüfen.'); ?></p>
        <?php if (!empty($readiness['missing'])): ?>
        <ul class="m365calc-note-list">
            <?php foreach ((array) ($readiness['missing'] ?? []) as $missing): ?>
            <?php if (!is_array($missing)) { continue; } ?>
            <li><strong><?php echo $esc($missing['label'] ?? 'Readiness-Punkt'); ?>:</strong> <?php echo $esc($missing['message'] ?? 'Vor Pilotstart prüfen.'); ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <section class="phinit-result m365calc-result-card" aria-labelledby="m365pilot-stage-table-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Pilotstufen</p>
                <h2 id="m365pilot-stage-table-title">Größen, Budget und Lernwert</h2>
                <p>Die Stufen helfen, zwischen schneller Validierung und belastbarer Rollout-Entscheidung zu unterscheiden.</p>
            </section>
        </header>
        <section class="phinit-table-wrap" aria-label="Copilot Pilotstufen">
            <table class="phinit-table">
                <thead>
                    <tr>
                        <th scope="col">Stufe</th>
                        <th scope="col">Nutzer</th>
                        <th scope="col">Dauer</th>
                        <th scope="col">Champions</th>
                        <th scope="col">Monatlich</th>
                        <th scope="col">Lernwert</th>
                        <th scope="col">Governance-Aufwand</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ((array) ($result['stages'] ?? []) as $stage): ?>
                    <?php if (!is_array($stage)) { continue; } ?>
                    <tr>
                        <th scope="row"><?php echo $esc($stage['label'] ?? 'Pilot'); ?></th>
                        <td><?php echo (int) ($stage['users'] ?? 0); ?></td>
                        <td><?php echo (int) ($stage['duration_weeks'] ?? 0); ?> Wochen</td>
                        <td><?php echo (int) ($stage['champions'] ?? 1); ?></td>
                        <td><?php echo $esc($money($stage['monthly_cost'] ?? 0)); ?></td>
                        <td><strong><?php echo (int) ($stage['learning_value'] ?? 0); ?>%</strong></td>
                        <td><strong><?php echo (int) ($stage['governance_load'] ?? 0); ?>%</strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </section>

    <section class="m365calc-result-grid" aria-label="Timeline und nächste Schritte">
        <article class="phinit-card m365calc-result-card">
            <header>
                <p class="phinit-overline">Zeitplan</p>
                <h2><?php echo $esc($timeline['label'] ?? 'Rollout-Zeitplan'); ?></h2>
                <p><?php echo $esc($timeline['best_for'] ?? 'Passender Ablauf für die empfohlene Pilotphase.'); ?></p>
            </header>
            <ol class="m365calc-note-list">
                <?php foreach ((array) ($timeline['phases'] ?? []) as $phase): ?>
                <?php if (!is_array($phase)) { continue; } ?>
                <li>
                    <strong><?php echo $esc($phase['week'] ?? 'Phase'); ?> · <?php echo $esc($phase['title'] ?? 'Schritt'); ?></strong>
                    <ul class="m365calc-note-list">
                        <?php foreach ((array) ($phase['tasks'] ?? []) as $task): ?>
                        <li><?php echo $esc($task); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endforeach; ?>
            </ol>
        </article>

        <article class="phinit-note phinit-note--info">
            <h2>Nächste Schritte</h2>
            <ol class="m365calc-note-list">
                <?php foreach ((array) ($result['next_steps'] ?? []) as $step): ?>
                <li><?php echo $esc($step); ?></li>
                <?php endforeach; ?>
            </ol>
        </article>
    </section>

    <section class="m365calc-result-grid" aria-label="Messkriterien und Quellen">
        <article class="phinit-card">
            <h2>Messkriterien</h2>
            <ul class="m365calc-note-list">
                <?php foreach ((array) ($result['success_metrics'] ?? []) as $metric): ?>
                <li><?php echo $esc($metric); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
        <article class="phinit-note phinit-note--info m365calc-source-card">
            <h2>Quellenstand</h2>
            <p>Quellenprüfung: <?php echo $esc($result['meta']['source_checked'] ?? '2026-05-16'); ?>. <?php echo $esc($result['meta']['price_basis'] ?? 'Preisannahmen vor Beschaffung prüfen.'); ?></p>
            <details>
                <summary>Quellen anzeigen</summary>
                <ul class="m365calc-note-list">
                    <?php foreach ((array) ($result['sources'] ?? []) as $source): ?>
                    <li><a href="<?php echo $esc($source); ?>" target="_blank" rel="noopener noreferrer"><?php echo $esc($source); ?></a></li>
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
