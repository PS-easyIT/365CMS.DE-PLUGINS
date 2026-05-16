<?php
/**
 * Public Template: M365 Add-On-Konfigurator.
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
    return $currency === 'USD' ? $amount . ' USD' : $amount . ' €';
};
$isChecked = static fn(array $values, string $value): string => in_array($value, $values, true) ? ' checked' : '';
$isSelected = static fn(string $left, string $right): string => $left === $right ? ' selected' : '';
$boolChecked = static fn(bool $value): string => $value ? ' checked' : '';
$input = is_array($input ?? null) ? $input : CMS_M365CALCULATOR_Addon_Configurator::default_input();
$result = is_array($result ?? null) ? $result : [];
$selectedAddons = array_values(array_map('strval', $input['addons'] ?? []));
$categories = is_array($result['categories'] ?? null) ? $result['categories'] : [];
$addonOptions = is_array($result['addon_options'] ?? null) ? $result['addon_options'] : [];
$evaluatedAddons = is_array($result['addons'] ?? null) ? $result['addons'] : [];
$totals = is_array($result['totals'] ?? null) ? $result['totals'] : [];
$basePlan = is_array($result['base_plan'] ?? null) ? $result['base_plan'] : [];
$upgrade = is_array($result['upgrade'] ?? null) ? $result['upgrade'] : null;
$consumption = is_array($result['consumption'] ?? null) ? $result['consumption'] : [];
$statusCounts = is_array($result['status_counts'] ?? null) ? $result['status_counts'] : [];

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'M365 Add-On-Konfigurator']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-addon-page" id="m365-addon-configurator">
    <header class="m365calc-hero">
        <p class="phinit-overline">Add-On-Konfigurator</p>
        <section class="m365calc-hero__content" aria-labelledby="m365addon-title">
            <section>
                <h1 id="m365addon-title">Microsoft 365 Add-On-Konfigurator</h1>
                <p class="phinit-prose">Basislizenz wählen, Zusatzbedarf markieren und sofort sehen, welche Add-ons wirklich nötig, redundant, inkompatibel oder besser als Upgrade zu lösen sind.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Lizenztools">
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenzvergleich">Lizenzvergleich öffnen</a>
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenzberater">Lizenzberater öffnen</a>
            </nav>
        </section>
    </header>

    <section class="m365calc-layout m365calc-layout--configurator">
        <section class="phinit-card" aria-labelledby="m365addon-config-title">
            <header class="m365calc-section__head">
                <section>
                    <h2 id="m365addon-config-title">Konfigurator</h2>
                    <p>Wähle Basislizenz, gewünschte Add-ons und Sonderparameter, um redundante oder fehlende Lizenzpfade zu erkennen.</p>
                </section>
            </header>

            <form method="GET" class="m365calc-form m365calc-addon-form">
                <fieldset class="m365calc-fieldset">
                    <legend>Basisdaten</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365addon-base-plan">
                            Basislizenz
                            <select class="phinit-select" id="m365addon-base-plan" name="base_plan">
                                <?php foreach (($result['plan_options'] ?? []) as $slug => $label): ?>
                                <option value="<?php echo $esc($slug); ?>"<?php echo $isSelected((string) ($input['base_plan'] ?? ''), (string) $slug); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365addon-users">
                            Anzahl Nutzer
                            <input class="phinit-input" type="number" id="m365addon-users" name="users" min="1" max="500000" value="<?php echo (int) ($input['users'] ?? 1); ?>">
                        </label>
                        <label class="phinit-field" for="m365addon-billing">
                            Laufzeitmodell
                            <select class="phinit-select" id="m365addon-billing" name="billing_model">
                                <?php foreach (($result['billing_options'] ?? []) as $key => $option): ?>
                                <?php if (!is_array($option)) { continue; } ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['billing_model'] ?? 'annual'), (string) $key); ?>><?php echo $esc($option['label'] ?? $key); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365addon-channel">
                            Segment / Kanal
                            <select class="phinit-select" id="m365addon-channel" name="channel">
                                <?php foreach (['commercial' => 'Commercial', 'education' => 'Education', 'nonprofit' => 'Nonprofit', 'government' => 'Government'] as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['channel'] ?? 'commercial'), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Wunsch-Add-ons</legend>
                    <?php foreach ($addonOptions as $category => $addons): ?>
                    <?php if (!is_array($addons)) { continue; } ?>
                    <section class="m365calc-addon-group" aria-labelledby="m365addon-group-<?php echo $esc($category); ?>">
                        <h3 id="m365addon-group-<?php echo $esc($category); ?>"><?php echo $esc($categories[$category] ?? $category); ?></h3>
                        <section class="m365calc-choice-grid m365calc-choice-grid--compact">
                            <?php foreach ($addons as $addon): ?>
                            <?php if (!is_array($addon)) { continue; } ?>
                            <?php $slug = (string) ($addon['slug'] ?? ''); ?>
                            <label class="m365calc-choice">
                                <input type="checkbox" name="addons[]" value="<?php echo $esc($slug); ?>"<?php echo $isChecked($selectedAddons, $slug); ?>>
                                <span>
                                    <strong><?php echo $esc($addon['name'] ?? ''); ?></strong>
                                    <small><?php echo $esc($addon['billing_type'] ?? 'per_user'); ?> · <?php echo $money($addon['price_month'] ?? 0); ?> / Monat</small>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </section>
                    </section>
                    <?php endforeach; ?>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Sonderparameter</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365addon-pstn">
                            PSTN-Anbieter für Teams Phone
                            <select class="phinit-select" id="m365addon-pstn" name="pstn_provider">
                                <?php foreach (['none' => 'Noch nicht festgelegt', 'microsoft' => 'Microsoft Calling Plan', 'operator_connect' => 'Operator Connect', 'direct_routing' => 'Direct Routing', 'teams_phone_mobile' => 'Teams Phone Mobile'] as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['pstn_provider'] ?? 'none'), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365addon-calling-plan">
                            Microsoft Calling Plan
                            <select class="phinit-select" id="m365addon-calling-plan" name="calling_plan">
                                <?php foreach (['none' => 'Kein Microsoft Calling Plan', 'domestic' => 'Domestic', 'international' => 'International', 'payg' => 'Pay-as-you-go'] as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['calling_plan'] ?? 'none'), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365addon-resource-accounts">
                            Resource Accounts
                            <input class="phinit-input" type="number" id="m365addon-resource-accounts" name="resource_accounts" min="0" max="1000" value="<?php echo (int) ($input['resource_accounts'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365addon-backup-gb">
                            Microsoft 365 Backup – geschützte GB
                            <input class="phinit-input" type="number" id="m365addon-backup-gb" name="backup_gb" min="0" max="9999999" value="<?php echo (int) ($input['backup_gb'] ?? 0); ?>">
                        </label>
                    </section>
                    <section class="m365calc-choice-grid">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="premium_connectors" value="1"<?php echo $boolChecked(!empty($input['premium_connectors'])); ?>>
                            <span>
                                <strong>Premium-/Custom-Connectoren nötig</strong>
                                <small>Prüft, ob Standalone-Power-Platform-Lizenzen statt seeded M365-Rechten nötig sind.</small>
                            </span>
                        </label>
                        <label class="m365calc-choice">
                            <input type="checkbox" name="dataverse_required" value="1"<?php echo $boolChecked(!empty($input['dataverse_required'])); ?>>
                            <span>
                                <strong>Dataverse für eigene Apps nötig</strong>
                                <small>Trennt begrenzte M365-Dataverse-Servicepläne von echten App-/Flow-Rechten.</small>
                            </span>
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Entscheidungsmodus</legend>
                    <section class="m365calc-choice-grid">
                        <label class="m365calc-choice">
                            <input type="radio" name="strategy" value="keep_addons"<?php echo $isSelected((string) ($input['strategy'] ?? 'upgrade_check'), 'keep_addons') === ' selected' ? ' checked' : ''; ?>>
                            <span>
                                <strong>Add-ons behalten</strong>
                                <small>Kalkuliert den gewählten Add-on-Stapel und markiert Probleme.</small>
                            </span>
                        </label>
                        <label class="m365calc-choice">
                            <input type="radio" name="strategy" value="upgrade_check"<?php echo $isSelected((string) ($input['strategy'] ?? 'upgrade_check'), 'upgrade_check') === ' selected' ? ' checked' : ''; ?>>
                            <span>
                                <strong>Upgrade prüfen</strong>
                                <small>Zeigt Upgrade-Alternativen, wenn ein Basiswechsel sauberer oder günstiger ist.</small>
                            </span>
                        </label>
                    </section>
                </fieldset>

                <section class="m365calc-actions">
                    <button type="submit" class="phinit-btn phinit-btn--primary">Add-ons prüfen</button>
                    <a class="phinit-btn phinit-btn--secondary" href="/m365-add-on-konfigurator">Zurücksetzen</a>
                </section>
            </form>
        </section>

        <aside class="m365calc-aside" aria-labelledby="m365addon-price-title">
            <article class="phinit-result m365calc-result-card" id="m365calc-result" tabindex="-1" data-m365calc-result>
                <header>
                    <p class="phinit-overline">Preisblock</p>
                    <h2 id="m365addon-price-title">SKU-Summe</h2>
                    <p><?php echo $esc($basePlan['name'] ?? 'Basislizenz'); ?> · <?php echo (int) ($input['users'] ?? 1); ?> Nutzer</p>
                </header>
                <section class="m365calc-price-stack" aria-label="Monatliche Kalkulation">
                    <article>
                        <span>Basis pro Monat</span>
                        <strong><?php echo $money($totals['base_monthly'] ?? 0); ?></strong>
                    </article>
                    <article>
                        <span>Add-ons pro Monat</span>
                        <strong><?php echo $money($totals['addon_monthly'] ?? 0); ?></strong>
                    </article>
                    <article class="m365calc-price-stack__total">
                        <span>Gesamt pro Monat</span>
                        <strong><?php echo $money($totals['total_monthly'] ?? 0); ?></strong>
                    </article>
                    <article>
                        <span>Gesamt pro Jahr</span>
                        <strong><?php echo $money($totals['total_yearly'] ?? 0); ?></strong>
                    </article>
                </section>
                <section class="m365calc-status-grid" aria-label="Status-Zusammenfassung">
                    <?php foreach (['ok' => 'Sinnvoll', 'warning' => 'Hinweis', 'blocked' => 'Blockiert', 'redundant' => 'Redundant', 'separate' => 'Separat'] as $key => $label): ?>
                    <span class="m365calc-status-pill m365calc-status-pill--<?php echo $esc($key); ?>"><?php echo $esc($label); ?>: <?php echo (int) ($statusCounts[$key] ?? 0); ?></span>
                    <?php endforeach; ?>
                </section>
                <footer class="m365calc-actions">
                    <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Drucken / PDF speichern</button>
                </footer>
            </article>

            <?php if ($upgrade !== null): ?>
            <article class="phinit-note <?php echo !empty($upgrade['is_cheaper']) ? 'phinit-note--success' : 'phinit-note--warning'; ?>">
                <h2>Upgrade prüfen</h2>
                <p><?php echo $esc($upgrade['reason'] ?? 'Upgrade statt Add-on-Stapel prüfen.'); ?></p>
                <ul class="m365calc-note-list">
                    <li>Zielplan: <?php echo $esc($upgrade['target_plan']['name'] ?? ''); ?></li>
                    <li>Aktueller Stapel: <?php echo $money($upgrade['current_monthly'] ?? 0); ?> / Monat</li>
                    <li>Upgrade-Szenario: <?php echo $money($upgrade['upgrade_monthly'] ?? 0); ?> / Monat</li>
                    <li>Differenz: <?php echo $money($upgrade['monthly_delta'] ?? 0); ?> / Monat</li>
                </ul>
            </article>
            <?php endif; ?>
        </aside>
    </section>

    <section class="phinit-result m365calc-result-card" aria-labelledby="m365addon-result-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Auswertung</p>
                <h2 id="m365addon-result-title">Add-on-Bewertung</h2>
                <p>Die Tabelle trennt echte Add-ons, fehlende Voraussetzungen, Redundanzen und Spezialfälle.</p>
            </section>
        </header>

        <section class="phinit-table-wrap m365calc-addon-table-wrap" aria-label="Add-on-Bewertungstabelle">
            <table class="phinit-table m365calc-addon-table">
                <thead>
                    <tr>
                        <th scope="col">Add-on</th>
                        <th scope="col">Status</th>
                        <th scope="col">Abrechnung</th>
                        <th scope="col" class="phinit-num">Monat</th>
                        <th scope="col">Hinweis</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($evaluatedAddons as $addon): ?>
                    <?php if (!is_array($addon)) { continue; } ?>
                    <tr>
                        <th scope="row"><?php echo $esc($addon['name'] ?? ''); ?></th>
                        <td><span class="m365calc-status-pill m365calc-status-pill--<?php echo $esc($addon['status'] ?? 'blocked'); ?>"><?php echo $esc($addon['status_label'] ?? ''); ?></span></td>
                        <td><?php echo $esc($addon['billing_type'] ?? 'per_user'); ?></td>
                        <td class="phinit-num"><?php echo $money($addon['monthly_total'] ?? 0); ?></td>
                        <td>
                            <span class="m365calc-table-note"><?php echo $esc($addon['source_note'] ?? ''); ?></span>
                            <?php if (!empty($addon['notes']) && is_array($addon['notes'])): ?>
                            <ul class="m365calc-note-list">
                                <?php foreach ($addon['notes'] as $note): ?>
                                <li><?php echo $esc($note); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </section>

    <section class="m365calc-result-grid" aria-label="Verbrauch und Hinweise">
        <article class="phinit-note phinit-note--info">
            <h2>Verbrauchsprodukte</h2>
            <?php if (empty($consumption)): ?>
            <p>Kein Verbrauchsmodul mit Mengenannahme aktiv. Microsoft 365 Backup und Pay-as-you-go-Telefonie werden nicht als klassische User-Add-ons behandelt.</p>
            <?php else: ?>
            <ul class="m365calc-note-list">
                <?php foreach ($consumption as $module): ?>
                <?php if (!is_array($module)) { continue; } ?>
                <li><?php echo $esc($module['name'] ?? ''); ?>: <?php echo $money($module['monthly_total'] ?? 0, (string) ($module['currency'] ?? 'EUR')); ?> / Monat bei <?php echo (int) ($module['quantity'] ?? 0); ?> <?php echo $esc($module['unit_label'] ?? 'Einheiten'); ?>.</li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </article>
        <article class="phinit-note phinit-note--warning">
            <h2>Warnungen & Pflegehinweise</h2>
            <ul class="m365calc-note-list">
                <?php foreach (($result['warnings'] ?? []) as $warning): ?>
                <li><?php echo $esc($warning); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
    </section>

    <section class="phinit-note phinit-note--info m365calc-source-card" aria-labelledby="m365addon-sources-title">
        <h2 id="m365addon-sources-title">Quellenstand</h2>
        <p>Die Konfiguration kombiniert gepflegte Preisannahmen mit Microsoft-Leitplanken. Vor Bestellung immer Partner Center, Markt, Segment und Verfügbarkeit prüfen.</p>
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
if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getFooter();
}
