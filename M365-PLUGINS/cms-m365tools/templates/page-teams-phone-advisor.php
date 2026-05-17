<?php
/**
 * Public Template: Teams Phone-Lizenz-Berater.
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
$input = is_array($input ?? null) ? $input : CMS_M365CALCULATOR_Teams_Phone_Advisor::default_input();
$result = is_array($result ?? null) ? $result : CMS_M365CALCULATOR_Teams_Phone_Advisor::evaluate($input);
$recommendation = is_array($result['recommendation'] ?? null) ? $result['recommendation'] : [];
$readiness = is_array($result['readiness'] ?? null) ? $result['readiness'] : [];
$costs = is_array($result['costs'] ?? null) ? $result['costs'] : [];
$addons = is_array($result['addons'] ?? null) ? $result['addons'] : [];
$country = is_array($result['country'] ?? null) ? $result['country'] : [];
$basePlan = is_array($result['base_plan'] ?? null) ? $result['base_plan'] : [];
$voiceProvider = is_array($result['voice_provider'] ?? null) ? $result['voice_provider'] : [];
$tone = (string) ($recommendation['tone'] ?? 'info');
$tone = in_array($tone, ['success', 'warning', 'danger', 'info'], true) ? $tone : 'info';
$score = max(0, min(100, (int) ($recommendation['score'] ?? 0)));
$readinessScore = max(0, min(100, (int) ($readiness['score'] ?? 0)));

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'Teams Phone-Lizenz-Berater']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-teams-phone-page" id="teams-phone-lizenzberater">
    <header class="m365calc-hero">
        <p class="phinit-overline">Microsoft Teams Phone</p>
        <section class="m365calc-hero__content" aria-labelledby="m365teamsphone-title">
            <section>
                <h1 id="m365teamsphone-title">Teams Phone-Lizenz-Berater</h1>
                <p class="phinit-prose">Vergleicht Calling Plan, Operator Connect, Direct Routing, Mischmodell und Sonderpfade für Teams Phone mit PSTN-Anbindung, Rufnummern und Zusatzbedarf.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Microsoft 365 Tools">
                <a class="phinit-btn phinit-btn--secondary" href="/m365-add-on-konfigurator">Add-ons konfigurieren</a>
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenzberater">Lizenzberater öffnen</a>
            </nav>
        </section>
    </header>

    <section class="m365calc-layout" aria-label="Teams Phone Beratung">
        <section class="phinit-card" aria-labelledby="m365teamsphone-form-title">
            <header class="m365calc-section__head">
                <section>
                    <h2 id="m365teamsphone-form-title">Telefonie-Szenario erfassen</h2>
                    <p>Beschreibe Lizenzbasis, Länder, Carrier-Wunsch, Betrieb und Sonderfälle. Die Auswertung trennt Teams Phone, PSTN-Modell und ergänzende Bausteine.</p>
                </section>
            </header>

            <form method="GET" class="m365calc-form m365calc-teams-phone-form">
                <fieldset class="m365calc-fieldset">
                    <legend>Lizenz- und Nutzerbasis</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365teamsphone-users">
                            Anzahl Nutzer
                            <input class="phinit-input" type="number" id="m365teamsphone-users" name="users" min="1" max="500000" value="<?php echo (int) ($input['users'] ?? 120); ?>">
                        </label>
                        <label class="phinit-field" for="m365teamsphone-user-type">
                            Hauptnutzergruppe
                            <select class="phinit-select" id="m365teamsphone-user-type" name="user_type">
                                <?php foreach ((array) ($result['user_type_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['user_type'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365teamsphone-base-license">
                            Vorhandene Basislizenz
                            <select class="phinit-select" id="m365teamsphone-base-license" name="base_license">
                                <?php foreach ((array) ($result['base_license_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['base_license'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365teamsphone-country">
                            Hauptland der Nutzerlizenz
                            <select class="phinit-select" id="m365teamsphone-country" name="countries">
                                <?php foreach ((array) ($result['country_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['countries'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </section>

                    <section class="m365calc-choice-grid" aria-label="Lizenzstatus">
                        <input type="hidden" name="teams_phone_existing" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="teams_phone_existing" value="1"<?php echo $isChecked(!empty($input['teams_phone_existing'])); ?>>
                            <span>
                                <strong>Teams Phone ist bereits lizenziert</strong>
                                <small>Die Nutzer haben die PBX-Funktionen bereits zugewiesen oder über eine passende Suite abgedeckt.</small>
                            </span>
                        </label>
                        <input type="hidden" name="external_pstn_required" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="external_pstn_required" value="1"<?php echo $isChecked(!empty($input['external_pstn_required'])); ?>>
                            <span>
                                <strong>Externe Telefonie ist erforderlich</strong>
                                <small>Die Nutzer sollen Telefonnummern außerhalb der Organisation anrufen oder erreichbar sein.</small>
                            </span>
                        </label>
                        <input type="hidden" name="many_countries" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="many_countries" value="1"<?php echo $isChecked(!empty($input['many_countries'])); ?>>
                            <span>
                                <strong>Mehrere Länder oder Regionen</strong>
                                <small>Standorte, Rufnummern oder Nutzerlizenzen liegen in unterschiedlichen Ländern.</small>
                            </span>
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>PSTN-Modell und Carrier</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365teamsphone-provider">
                            Anbieter-Ausgangspunkt
                            <select class="phinit-select" id="m365teamsphone-provider" name="voice_provider">
                                <?php foreach ((array) ($result['provider_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['voice_provider'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365teamsphone-volume">
                            Ausgehendes Gesprächsvolumen
                            <select class="phinit-select" id="m365teamsphone-volume" name="call_volume">
                                <?php foreach ((array) ($result['call_volume_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['call_volume'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </section>

                    <section class="m365calc-choice-grid">
                        <input type="hidden" name="microsoft_carrier_ok" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="microsoft_carrier_ok" value="1"<?php echo $isChecked(!empty($input['microsoft_carrier_ok'])); ?>>
                            <span>
                                <strong>Microsoft darf PSTN-Carrier sein</strong>
                                <small>Ein vollständig cloudbasierter Calling-Plan-Pfad ist grundsätzlich akzeptabel.</small>
                            </span>
                        </label>
                        <input type="hidden" name="operator_connect_partner" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="operator_connect_partner" value="1"<?php echo $isChecked(!empty($input['operator_connect_partner'])); ?>>
                            <span>
                                <strong>Operator-Connect-Partner ist verfügbar</strong>
                                <small>Ein teilnehmender Carrier kann Nummern, PSTN-Service und Betrieb übernehmen.</small>
                            </span>
                        </label>
                        <input type="hidden" name="keep_existing_carrier" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="keep_existing_carrier" value="1"<?php echo $isChecked(!empty($input['keep_existing_carrier'])); ?>>
                            <span>
                                <strong>Bestehender Carrier soll bleiben</strong>
                                <small>Verträge, Rufnummernblöcke oder Sondertarife sprechen gegen einen kompletten Wechsel.</small>
                            </span>
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Betrieb, Routing und Sonderfälle</legend>
                    <section class="m365calc-choice-grid">
                        <input type="hidden" name="teams_only" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="teams_only" value="1"<?php echo $isChecked(!empty($input['teams_only'])); ?>>
                            <span>
                                <strong>Betroffene Nutzer sind für TeamsOnly geplant</strong>
                                <small>Koexistenz und Sprachrouting werden auf Teams als primäre Arbeitsoberfläche ausgerichtet.</small>
                            </span>
                        </label>
                        <input type="hidden" name="sbc_available" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="sbc_available" value="1"<?php echo $isChecked(!empty($input['sbc_available'])); ?>>
                            <span>
                                <strong>Zertifizierter SBC oder DRaaS-Pfad vorhanden</strong>
                                <small>Direct Routing kann mit passender Infrastruktur oder gemanagtem Dienst umgesetzt werden.</small>
                            </span>
                        </label>
                        <input type="hidden" name="voice_network_knowhow" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="voice_network_knowhow" value="1"<?php echo $isChecked(!empty($input['voice_network_knowhow'])); ?>>
                            <span>
                                <strong>Voice- und Netzwerk-Know-how vorhanden</strong>
                                <small>Routing, DNS, Zertifikate, Firewall, Notruf und Providerkoordination können betrieben werden.</small>
                            </span>
                        </label>
                        <input type="hidden" name="managed_service_preferred" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="managed_service_preferred" value="1"<?php echo $isChecked(!empty($input['managed_service_preferred'])); ?>>
                            <span>
                                <strong>Gemanagter Betrieb bevorzugt</strong>
                                <small>Carrier oder Dienstleister sollen Infrastruktur, Support und Betrieb möglichst übernehmen.</small>
                            </span>
                        </label>
                        <input type="hidden" name="pbx_analog_contact_center" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="pbx_analog_contact_center" value="1"<?php echo $isChecked(!empty($input['pbx_analog_contact_center'])); ?>>
                            <span>
                                <strong>PBX, Analoggeräte oder Contact Center bleiben relevant</strong>
                                <small>Es gibt bestehende Telefonanlagen, Türsprechanlagen, Faxersatz, Paging oder Spezialintegration.</small>
                            </span>
                        </label>
                        <input type="hidden" name="special_routing_required" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="special_routing_required" value="1"<?php echo $isChecked(!empty($input['special_routing_required'])); ?>>
                            <span>
                                <strong>Spezialrouting wird benötigt</strong>
                                <small>Standort-, Kostenstellen-, Länderrouting oder komplexe Notruf-/Carrier-Regeln sind relevant.</small>
                            </span>
                        </label>
                        <input type="hidden" name="personal_number_required" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="personal_number_required" value="1"<?php echo $isChecked(!empty($input['personal_number_required'])); ?>>
                            <span>
                                <strong>Persönliche Rufnummer je Nutzer erforderlich</strong>
                                <small>Jeder Nutzer braucht eine eigene Durchwahl statt gemeinsamer Nummernführung.</small>
                            </span>
                        </label>
                        <input type="hidden" name="low_volume_without_did" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="low_volume_without_did" value="1"<?php echo $isChecked(!empty($input['low_volume_without_did'])); ?>>
                            <span>
                                <strong>Low-Volume-Nutzer ohne eigene Durchwahl möglich</strong>
                                <small>Ein Teil der Nutzer telefoniert selten und kann über zentrale Nummern geführt werden.</small>
                            </span>
                        </label>
                        <input type="hidden" name="resource_account_possible" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="resource_account_possible" value="1"<?php echo $isChecked(!empty($input['resource_account_possible'])); ?>>
                            <span>
                                <strong>Ressourcenkonto und Auto Attendant sind möglich</strong>
                                <small>Gemeinsame Rufnummernführung für Shared Calling kann fachlich abgebildet werden.</small>
                            </span>
                        </label>
                        <input type="hidden" name="audio_conferencing_required" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="audio_conferencing_required" value="1"<?php echo $isChecked(!empty($input['audio_conferencing_required'])); ?>>
                            <span>
                                <strong>Audio Conferencing wird benötigt</strong>
                                <small>Meeting-Einwahl per Telefon, Dial-out oder Konferenzbrücken sind Teil des Zielbilds.</small>
                            </span>
                        </label>
                    </section>
                </fieldset>

                <section class="m365calc-actions">
                    <button type="submit" class="phinit-btn phinit-btn--primary">Teams-Phone-Modell berechnen</button>
                    <a class="phinit-btn phinit-btn--secondary" href="/teams-phone-lizenzberater">Zurücksetzen</a>
                </section>
            </form>
        </section>

        <aside class="m365calc-aside" aria-labelledby="m365teamsphone-result-title">
            <article class="phinit-note phinit-note--<?php echo $esc($tone); ?>" id="m365calc-result" tabindex="-1" data-m365calc-result>
                <p class="phinit-overline">Empfohlenes Zielmodell</p>
                <h2 id="m365teamsphone-result-title"><?php echo $esc($recommendation['label'] ?? 'Empfehlung prüfen'); ?></h2>
                <p><?php echo $esc($recommendation['reason'] ?? 'Das Szenario wurde bewertet.'); ?></p>
                <section class="m365calc-score" aria-label="Modell-Fit">
                    <span>Modell-Fit</span>
                    <strong><?php echo (int) $score; ?>%</strong>
                    <div class="m365calc-score__bar"><span style="--m365calc-score-width: <?php echo max(0, min(100, (int) $score)); ?>%;"></span></div>
                </section>
            </article>

            <article class="phinit-result m365calc-result-card">
                <header>
                    <p class="phinit-overline">Budgetrahmen</p>
                    <h2><?php echo $esc($money($costs['monthly_low'] ?? 0)); ?> bis <?php echo $esc($money($costs['monthly_high'] ?? 0)); ?> monatlich</h2>
                    <p>Für <?php echo (int) ($input['users'] ?? 0); ?> Nutzer, <?php echo $esc($country['label'] ?? 'Land prüfen'); ?>, <?php echo $esc($recommendation['short'] ?? 'Modell'); ?>.</p>
                </header>
                <dl class="m365calc-kpi-list">
                    <div>
                        <dt>Pro Nutzer</dt>
                        <dd><?php echo $esc($money($costs['per_user_low'] ?? 0)); ?> bis <?php echo $esc($money($costs['per_user_high'] ?? 0)); ?></dd>
                    </div>
                    <div>
                        <dt>Jahr</dt>
                        <dd><?php echo $esc($money($costs['annual_low'] ?? 0)); ?> bis <?php echo $esc($money($costs['annual_high'] ?? 0)); ?></dd>
                    </div>
                    <div>
                        <dt>Einmalig</dt>
                        <dd><?php echo $esc($money($costs['setup_once'] ?? 0)); ?></dd>
                    </div>
                </dl>
                <footer class="m365calc-actions">
                    <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Drucken / PDF speichern</button>
                    <a class="phinit-btn phinit-btn--primary" href="/kontakt">Telefonie-Zielbild prüfen</a>
                </footer>
            </article>
        </aside>
    </section>

    <section class="m365calc-summary-grid" aria-label="Teams Phone Kennzahlen">
        <article class="phinit-card m365calc-mini-card">
            <span>Modell</span>
            <strong><?php echo $esc($recommendation['short'] ?? 'Prüfen'); ?></strong>
            <p><?php echo $esc($recommendation['best_for'] ?? ''); ?></p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Readiness</span>
            <strong><?php echo (int) $readinessScore; ?>%</strong>
            <p>Bewertet Teams-Basis, Teams Phone, PSTN-Pfad, Koexistenz und technische Voraussetzungen.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Betrieb</span>
            <strong><?php echo $esc($recommendation['operations'] ?? 'mittel'); ?></strong>
            <p>Schätzt Infrastruktur-, Carrier-, Support- und Betriebsaufwand.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Flexibilität</span>
            <strong><?php echo $esc($recommendation['flexibility'] ?? 'mittel'); ?></strong>
            <p>Bewertet Sonderrouting, Länder, Carrier-Wahl und Integrationsfreiheit.</p>
        </article>
    </section>

    <?php if (!empty($result['warnings'])): ?>
    <section class="phinit-note phinit-note--warning" aria-labelledby="m365teamsphone-warning-title">
        <h2 id="m365teamsphone-warning-title">Wichtige Planungsrisiken</h2>
        <ul class="m365calc-note-list">
            <?php foreach ((array) ($result['warnings'] ?? []) as $warning): ?>
            <li><?php echo $esc($warning); ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <section class="m365calc-result-grid" aria-label="Lizenz- und Zusatzbedarf">
        <article class="phinit-card m365calc-result-card">
            <header>
                <p class="phinit-overline">Lizenzbasis</p>
                <h2><?php echo $esc($basePlan['label'] ?? 'Basis prüfen'); ?></h2>
                <p><?php echo $esc($basePlan['note'] ?? 'Basislizenz und Teams-Phone-Ergänzung prüfen.'); ?></p>
            </header>
            <dl class="m365calc-kpi-list">
                <div>
                    <dt>Teams-Zugriff</dt>
                    <dd><?php echo !empty($basePlan['teams_included']) ? 'abgedeckt' : 'ergänzen'; ?></dd>
                </div>
                <div>
                    <dt>Teams Phone</dt>
                    <dd><?php echo !empty($basePlan['teams_phone_included']) || !empty($input['teams_phone_existing']) ? 'abgedeckt' : 'hinzufügen'; ?></dd>
                </div>
            </dl>
        </article>

        <article class="phinit-card m365calc-result-card">
            <header>
                <p class="phinit-overline">Zusatzbausteine</p>
                <h2>Add-ons und Sonderpfade</h2>
                <p>Diese Punkte sollten in Einkauf, Rollout und Betrieb separat berücksichtigt werden.</p>
            </header>
            <ul class="m365calc-note-list">
                <?php foreach ((array) ($addons['items'] ?? []) as $item): ?>
                <?php if (!is_array($item)) { continue; } ?>
                <li><strong><?php echo $esc($item['label'] ?? ''); ?>:</strong> <?php echo $esc($item['message'] ?? ''); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
    </section>

    <section class="phinit-result m365calc-result-card" aria-labelledby="m365teamsphone-model-table-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Modellvergleich</p>
                <h2 id="m365teamsphone-model-table-title">Calling Plan, Operator Connect und Direct Routing</h2>
                <p>Die Tabelle zeigt Fit, Betrieb, Flexibilität, typische Stärken und Grenzen der Modelle für dein Szenario.</p>
            </section>
        </header>
        <section class="phinit-table-wrap" aria-label="Teams Phone Modellvergleich">
            <table class="phinit-table">
                <thead>
                    <tr>
                        <th scope="col">Modell</th>
                        <th scope="col">Fit</th>
                        <th scope="col">Betrieb</th>
                        <th scope="col">Flexibilität</th>
                        <th scope="col">Geeignet für</th>
                        <th scope="col">Grenzen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ((array) ($result['comparison_rows'] ?? []) as $row): ?>
                    <?php if (!is_array($row)) { continue; } ?>
                    <tr>
                        <th scope="row"><?php echo $esc($row['label'] ?? ''); ?><?php echo !empty($row['active']) ? ' · Empfehlung' : ''; ?></th>
                        <td><?php echo (int) ($row['score'] ?? 0); ?>%</td>
                        <td><?php echo $esc($row['operations'] ?? ''); ?></td>
                        <td><?php echo $esc($row['flexibility'] ?? ''); ?></td>
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

    <section class="m365calc-result-grid" aria-label="Readiness und Anforderungen">
        <article class="phinit-note phinit-note--info">
            <h2>Readiness-Checks</h2>
            <ul class="m365calc-note-list">
                <?php foreach ((array) ($readiness['checks'] ?? []) as $check): ?>
                <?php if (!is_array($check)) { continue; } ?>
                <li><strong><?php echo $esc($check['label'] ?? ''); ?>:</strong> <?php echo $esc($check['status'] ?? 'prüfen'); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
        <article class="phinit-note phinit-note--info">
            <h2>Umsetzungsschritte</h2>
            <ol class="m365calc-note-list">
                <?php foreach ((array) ($result['next_steps'] ?? []) as $step): ?>
                <li><?php echo $esc($step); ?></li>
                <?php endforeach; ?>
            </ol>
        </article>
    </section>

    <section class="phinit-result m365calc-result-card" aria-labelledby="m365teamsphone-requirement-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Voraussetzungen</p>
                <h2 id="m365teamsphone-requirement-title">Was vor Bestellung und Portierung geklärt sein sollte</h2>
                <p><?php echo $esc($costs['note'] ?? ''); ?></p>
            </section>
        </header>
        <section class="phinit-table-wrap" aria-label="Voraussetzungen für Teams Phone">
            <table class="phinit-table">
                <thead>
                    <tr>
                        <th scope="col">Punkt</th>
                        <th scope="col">Status</th>
                        <th scope="col">Bewertung</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ((array) ($result['requirement_rows'] ?? []) as $row): ?>
                    <?php if (!is_array($row)) { continue; } ?>
                    <tr>
                        <th scope="row"><?php echo $esc($row['label'] ?? ''); ?></th>
                        <td><?php echo $esc($row['status'] ?? 'prüfen'); ?></td>
                        <td><?php echo $esc($row['message'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </section>

    <section class="phinit-note phinit-note--info m365calc-source-card" aria-labelledby="m365teamsphone-sources-title">
        <h2 id="m365teamsphone-sources-title">Quellenstand</h2>
        <p>Quellenprüfung: <?php echo $esc($result['meta']['source_checked'] ?? '2026-05-16'); ?>. <?php echo $esc($result['meta']['price_basis'] ?? 'Richtwerte vor Beschaffung prüfen.'); ?></p>
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
