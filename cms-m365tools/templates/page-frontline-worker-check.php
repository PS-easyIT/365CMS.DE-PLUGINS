<?php
/**
 * Public Template: Frontline Worker Lizenz-Eignung-Check.
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
$input = is_array($input ?? null) ? $input : CMS_M365CALCULATOR_Frontline_Worker_Check::default_input();
$result = is_array($result ?? null) ? $result : CMS_M365CALCULATOR_Frontline_Worker_Check::evaluate($input);
$fit = is_array($result['fit'] ?? null) ? $result['fit'] : [];
$recommendation = is_array($result['recommendation'] ?? null) ? $result['recommendation'] : [];
$savings = is_array($result['savings'] ?? null) ? $result['savings'] : [];
$tone = (string) ($recommendation['tone'] ?? 'info');
$tone = in_array($tone, ['success', 'warning', 'danger', 'info'], true) ? $tone : 'info';

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'Frontline Worker Lizenz-Eignung-Check']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-frontline-page" id="frontline-worker-lizenz-check">
    <header class="m365calc-hero">
        <p class="phinit-overline">Microsoft 365 Frontline</p>
        <section class="m365calc-hero__content" aria-labelledby="m365frontline-title">
            <section>
                <h1 id="m365frontline-title">Frontline Worker Lizenz-Eignung-Check</h1>
                <p class="phinit-prose">Prüft, ob mobile, schichtbasierte oder deskless Nutzergruppen realistisch mit F1 oder F3 statt Business Premium, E3 oder E5 arbeiten können.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Lizenztools">
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenzberater">Lizenzberater öffnen</a>
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenzvergleich">Lizenzen vergleichen</a>
            </nav>
        </section>
    </header>

    <section class="m365calc-layout" aria-label="Frontline Lizenzprüfung">
        <section class="phinit-card" aria-labelledby="m365frontline-form-title">
            <header class="m365calc-section__head">
                <section>
                    <h2 id="m365frontline-form-title">Arbeitsrealität beschreiben</h2>
                    <p>Wähle Branche, Rolle, Gerät und Funktionsbedarf. Der Check bewertet Frontline-Fit, Downgrade-Risiken und Sparpotenzial.</p>
                </section>
            </header>

            <form method="GET" class="m365calc-form m365calc-frontline-form">
                <fieldset class="m365calc-fieldset">
                    <legend>Rollenprofil</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365frontline-preset">
                            Branchenbeispiel
                            <select class="phinit-select" id="m365frontline-preset" name="preset">
                                <?php foreach (($result['preset_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['preset'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365frontline-role">
                            Nutzergruppe / Rolle
                            <select class="phinit-select" id="m365frontline-role" name="role">
                                <?php foreach (($result['role_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['role'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365frontline-users">
                            Anzahl Nutzer in der Gruppe
                            <input class="phinit-input" type="number" id="m365frontline-users" name="users" min="1" max="500000" value="<?php echo (int) ($input['users'] ?? 100); ?>">
                        </label>
                        <label class="phinit-field" for="m365frontline-baseline">
                            Bisheriger Vergleichsplan
                            <select class="phinit-select" id="m365frontline-baseline" name="baseline_plan">
                                <?php foreach (($result['baseline_plan_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['baseline_plan'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </section>

                    <section class="m365calc-choice-grid" aria-label="Arbeitsweise">
                        <input type="hidden" name="direct_customer_contact" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="direct_customer_contact" value="1"<?php echo $isChecked(!empty($input['direct_customer_contact'])); ?>>
                            <span>
                                <strong>Direkter Kunden-, Patienten- oder Produktionskontakt</strong>
                                <small>Die Arbeit findet nah an Kundschaft, Öffentlichkeit, Fertigung, Pflege oder Serviceprozessen statt.</small>
                            </span>
                        </label>
                        <input type="hidden" name="deskless_mobile" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="deskless_mobile" value="1"<?php echo $isChecked(!empty($input['deskless_mobile'])); ?>>
                            <span>
                                <strong>Deskless oder überwiegend mobil</strong>
                                <small>Kein klassischer Büroarbeitsplatz mit dauerhafter Desktop-Office-Nutzung.</small>
                            </span>
                        </label>
                        <input type="hidden" name="shift_based" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="shift_based" value="1"<?php echo $isChecked(!empty($input['shift_based'])); ?>>
                            <span>
                                <strong>Schichtbetrieb oder wechselnde Einsatzorte</strong>
                                <small>Schichtplanung, Aufgabenübergabe oder Standortwechsel sind Teil des Alltags.</small>
                            </span>
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Gerät und Zugriff</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365frontline-device">
                            Gerätetyp
                            <select class="phinit-select" id="m365frontline-device" name="device_model">
                                <?php foreach (($result['device_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['device_model'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </section>
                    <section class="m365calc-choice-grid">
                        <input type="hidden" name="identity_device_controls_ready" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="identity_device_controls_ready" value="1"<?php echo $isChecked(!empty($input['identity_device_controls_ready'])); ?>>
                            <span>
                                <strong>Identität, Geräteverwaltung und Zugriffskontrollen sind geplant</strong>
                                <small>Entra ID, Geräte-Compliance, App-Schutz und einfache Anmeldung/Abmeldung sind berücksichtigt.</small>
                            </span>
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>App- und Funktionsbedarf</legend>
                    <section class="m365calc-choice-grid">
                        <input type="hidden" name="teams_chat_shifts_tasks" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="teams_chat_shifts_tasks" value="1"<?php echo $isChecked(!empty($input['teams_chat_shifts_tasks'])); ?>>
                            <span>
                                <strong>Teams, Chat, Shifts, Planner oder Lists</strong>
                                <small>Kommunikation, Schichtplanung, Aufgaben und einfache Listen stehen im Vordergrund.</small>
                            </span>
                        </label>
                        <input type="hidden" name="mail_required" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="mail_required" value="1"<?php echo $isChecked(!empty($input['mail_required'])); ?>>
                            <span>
                                <strong>Mailzugriff wird benötigt</strong>
                                <small>Regelmäßige persönliche Mailkommunikation ist Teil der Rolle.</small>
                            </span>
                        </label>
                        <input type="hidden" name="sharepoint_onedrive_viva" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="sharepoint_onedrive_viva" value="1"<?php echo $isChecked(!empty($input['sharepoint_onedrive_viva'])); ?>>
                            <span>
                                <strong>SharePoint, OneDrive, Viva oder Wissensinhalte</strong>
                                <small>Die Gruppe braucht Informationen, Training, News oder einfache Dateien.</small>
                            </span>
                        </label>
                        <input type="hidden" name="power_platform_required" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="power_platform_required" value="1"<?php echo $isChecked(!empty($input['power_platform_required'])); ?>>
                            <span>
                                <strong>Power Apps oder Power Automate benötigt</strong>
                                <small>Digitale Formulare, Workflows oder einfache Prozess-Apps sind relevant.</small>
                            </span>
                        </label>
                        <input type="hidden" name="desktop_apps_required" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="desktop_apps_required" value="1"<?php echo $isChecked(!empty($input['desktop_apps_required'])); ?>>
                            <span>
                                <strong>Desktop-Apps oder starke Dokumentarbeit erforderlich</strong>
                                <small>Word, Excel, PowerPoint oder Outlook am Desktop sind für die Rolle regelmäßig nötig.</small>
                            </span>
                        </label>
                        <input type="hidden" name="high_security_compliance" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="high_security_compliance" value="1"<?php echo $isChecked(!empty($input['high_security_compliance'])); ?>>
                            <span>
                                <strong>Erhöhter Schutz- oder Compliance-Bedarf</strong>
                                <small>Zusätzliche Sicherheits-, Audit-, Datenschutz- oder Branchenanforderungen sind relevant.</small>
                            </span>
                        </label>
                    </section>
                </fieldset>

                <section class="m365calc-actions">
                    <button type="submit" class="phinit-btn phinit-btn--primary">Frontline-Fit berechnen</button>
                    <a class="phinit-btn phinit-btn--secondary" href="/frontline-worker-lizenz-check">Zurücksetzen</a>
                </section>
            </form>
        </section>

        <aside class="m365calc-aside" aria-labelledby="m365frontline-result-title">
            <article class="phinit-note phinit-note--<?php echo $esc($tone); ?>" id="m365calc-result" tabindex="-1" data-m365calc-result>
                <p class="phinit-overline"><?php echo $esc($recommendation['user_type_label'] ?? 'Einordnung'); ?></p>
                <h2 id="m365frontline-result-title"><?php echo $esc($recommendation['label'] ?? 'Empfehlung prüfen'); ?></h2>
                <p><?php echo $esc($recommendation['reason'] ?? 'Die Nutzergruppe wurde bewertet.'); ?></p>
                <section class="m365calc-score" aria-label="Fit Score">
                    <span>Fit Score</span>
                    <strong><?php echo (int) ($recommendation['score'] ?? 0); ?>%</strong>
                    <div class="m365calc-score__bar"><span style="--m365calc-score-width: <?php echo (int) ($recommendation['score'] ?? 0); ?>%;"></span></div>
                </section>
            </article>

            <article class="phinit-result m365calc-result-card">
                <header>
                    <p class="phinit-overline">Empfohlener Zielplan</p>
                    <h2><?php echo $esc($savings['target_plan'] ?? 'Plan prüfen'); ?></h2>
                    <p>Verglichen mit <?php echo $esc($savings['baseline_plan'] ?? 'dem bisherigen Plan'); ?> für <?php echo (int) ($input['users'] ?? 0); ?> Nutzer.</p>
                </header>
                <dl class="m365calc-kpi-list">
                    <div>
                        <dt>Ersparnis pro Nutzer</dt>
                        <dd><?php echo $esc($money($savings['per_user'] ?? 0)); ?></dd>
                    </div>
                    <div>
                        <dt>Monatliches Potenzial</dt>
                        <dd><?php echo $esc($money($savings['monthly'] ?? 0)); ?></dd>
                    </div>
                    <div>
                        <dt>Jährliches Potenzial</dt>
                        <dd><?php echo $esc($money($savings['annual'] ?? 0)); ?></dd>
                    </div>
                </dl>
                <footer class="m365calc-actions">
                    <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Drucken / PDF speichern</button>
                    <a class="phinit-btn phinit-btn--primary" href="/kontakt">Lizenzmodell prüfen lassen</a>
                </footer>
            </article>
        </aside>
    </section>

    <section class="m365calc-summary-grid" aria-label="Scoring Details">
        <article class="phinit-card m365calc-mini-card">
            <span>Frontline-Fit</span>
            <strong><?php echo (int) ($fit['frontline_score'] ?? 0); ?>%</strong>
            <p>Bewertet Mobilität, Schichtarbeit, Deskless-Anteil und Rollenprofil.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>F1-Fit</span>
            <strong><?php echo (int) ($fit['f1_score'] ?? 0); ?>%</strong>
            <p>Passt besonders bei leichter mobiler Nutzung ohne hohe App-Komplexität.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>F3-Fit</span>
            <strong><?php echo (int) ($fit['f3_score'] ?? 0); ?>%</strong>
            <p>Passt bei erweitertem Frontline-Bedarf mit Apps, Prozessen oder Mail.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Risiko</span>
            <strong><?php echo (int) ($fit['risk_score'] ?? 0); ?>%</strong>
            <p>Bewertet Geräte-, Zugriffs- und Downgrade-Risiken.</p>
        </article>
    </section>

    <?php if (!empty($result['warnings'])): ?>
    <section class="phinit-note phinit-note--warning" aria-labelledby="m365frontline-warning-title">
        <h2 id="m365frontline-warning-title">Wichtige Hinweise</h2>
        <ul class="m365calc-note-list">
            <?php foreach ((array) ($result['warnings'] ?? []) as $warning): ?>
            <li><?php echo $esc($warning); ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <section class="m365calc-result-grid" aria-label="Praxis-Hinweise und Branchenbeispiele">
        <article class="phinit-note phinit-note--info">
            <h2>Warum F1/F3 hier passt – oder eben nicht</h2>
            <ul class="m365calc-note-list">
                <?php foreach ((array) ($result['guidance'] ?? []) as $guidance): ?>
                <li><?php echo $esc($guidance); ?></li>
                <?php endforeach; ?>
                <?php foreach ((array) ($fit['rule_messages'] ?? []) as $message): ?>
                <li><?php echo $esc($message); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
        <article class="phinit-card">
            <h2>Branchenbeispiele</h2>
            <section class="m365calc-plan-grid" aria-label="Frontline Branchenbeispiele">
                <?php foreach ((array) ($result['preset_cards'] ?? []) as $card): ?>
                <?php if (!is_array($card)) { continue; } ?>
                <article class="m365calc-mini-card">
                    <span><?php echo $esc($card['key'] ?? ''); ?></span>
                    <strong><?php echo $esc($card['label'] ?? ''); ?></strong>
                    <p><?php echo $esc($card['summary'] ?? ''); ?></p>
                </article>
                <?php endforeach; ?>
            </section>
        </article>
    </section>

    <section class="phinit-result m365calc-result-card" aria-labelledby="m365frontline-plan-table-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Planvergleich</p>
                <h2 id="m365frontline-plan-table-title">F1, F3 und Alternativen</h2>
                <p>Die Tabelle zeigt Kostenannahmen, typische Einsatzbereiche und Grenzen je Plan für die angegebene Nutzergruppe.</p>
            </section>
        </header>
        <section class="phinit-table-wrap" aria-label="Frontline Planvergleich">
            <table class="phinit-table">
                <thead>
                    <tr>
                        <th scope="col">Plan</th>
                        <th scope="col">Kategorie</th>
                        <th scope="col">Monat pro Nutzer</th>
                        <th scope="col">Monat Gruppe</th>
                        <th scope="col">Geeignet für</th>
                        <th scope="col">Grenzen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ((array) ($result['plan_rows'] ?? []) as $row): ?>
                    <?php if (!is_array($row)) { continue; } ?>
                    <tr>
                        <th scope="row"><?php echo $esc($row['label'] ?? ''); ?></th>
                        <td><?php echo $esc($row['category'] ?? ''); ?></td>
                        <td><?php echo $esc($money($row['price_month'] ?? 0)); ?></td>
                        <td><?php echo $esc($money($row['group_month'] ?? 0)); ?></td>
                        <td><?php echo $esc($row['best_for'] ?? ''); ?></td>
                        <td>
                            <ul class="m365calc-note-list">
                                <?php foreach ((array) ($row['limits'] ?? []) as $limit): ?>
                                <li><?php echo $esc($limit); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </section>

    <section class="phinit-note phinit-note--info m365calc-source-card" aria-labelledby="m365frontline-sources-title">
        <h2 id="m365frontline-sources-title">Quellenstand</h2>
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
