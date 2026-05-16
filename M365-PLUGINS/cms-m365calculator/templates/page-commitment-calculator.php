<?php
/**
 * Public Template: Annual vs. Monthly Commitment Rechner.
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
$isSelected = static fn(string $left, string $right): string => $left === $right ? ' selected' : '';
$input = is_array($input ?? null) ? $input : CMS_M365CALCULATOR_Commitment_Calculator::default_input();
$result = is_array($result ?? null) ? $result : CMS_M365CALCULATOR_Commitment_Calculator::evaluate($input);
$planOptions = CMS_M365CALCULATOR_Commitment_Calculator::plan_options();
$models = is_array($result['models'] ?? null) ? $result['models'] : [];
$chart = is_array($result['chart'] ?? null) ? $result['chart'] : [];
$recommendation = is_array($result['recommendation'] ?? null) ? $result['recommendation'] : [];
$plan = is_array($result['plan'] ?? null) ? $result['plan'] : [];
$channel = is_array($result['channel'] ?? null) ? $result['channel'] : [];
$tone = (string) ($recommendation['tone'] ?? 'warning');
$noteClass = $tone === 'success' ? 'phinit-note--success' : 'phinit-note--warning';

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'Annual vs. Monthly Commitment Rechner']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-commitment-page" id="m365-commitment-calculator">
    <header class="m365calc-hero">
        <p class="phinit-overline">Commitment Rechner</p>
        <section class="m365calc-hero__content" aria-labelledby="m365commitment-title">
            <section>
                <h1 id="m365commitment-title">Annual vs. Monthly Commitment Rechner</h1>
                <p class="phinit-prose">Vergleicht Monatslaufzeit, Jahresbindung mit monatlicher oder jährlicher Abrechnung und eine Split-Strategie aus stabilem Kern plus flexiblen Seats.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Lizenztools">
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenzmatrix">Lizenzmatrix öffnen</a>
                <a class="phinit-btn phinit-btn--secondary" href="/m365-add-on-konfigurator">Add-On-Konfigurator öffnen</a>
            </nav>
        </section>
    </header>

    <section class="m365calc-layout m365calc-layout--commitment">
        <section class="phinit-card" aria-labelledby="m365commitment-config-title">
            <header class="m365calc-section__head">
                <section>
                    <h2 id="m365commitment-config-title">Eingaben</h2>
                    <p>Trage Nutzerprofil, Wachstum und Vertragskanal ein, um Laufzeitmodelle und Split-Strategien sauber zu vergleichen.</p>
                </section>
            </header>

            <form method="GET" class="m365calc-form">
                <fieldset class="m365calc-fieldset">
                    <legend>Lizenz und Nutzerprofil</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365commitment-plan">
                            Lizenz / SKU
                            <select class="phinit-select" id="m365commitment-plan" name="plan">
                                <?php foreach ($planOptions as $slug => $label): ?>
                                <option value="<?php echo $esc($slug); ?>"<?php echo $isSelected((string) ($input['plan'] ?? ''), (string) $slug); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365commitment-users">
                            Start-Nutzerzahl
                            <input class="phinit-input" type="number" id="m365commitment-users" name="users" min="1" max="500000" value="<?php echo (int) ($input['users'] ?? 1); ?>">
                        </label>
                        <label class="phinit-field" for="m365commitment-volatile">
                            Variable Nutzer in %
                            <input class="phinit-input" type="number" id="m365commitment-volatile" name="volatile_percent" min="0" max="80" value="<?php echo (int) ($input['volatile_percent'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365commitment-growth">
                            Monatliches Wachstum / Rückgang in %
                            <input class="phinit-input" type="number" step="0.1" id="m365commitment-growth" name="monthly_growth_percent" min="-10" max="15" value="<?php echo $esc($input['monthly_growth_percent'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365commitment-core">
                            Stabiler Kern für Split-Strategie in %
                            <input class="phinit-input" type="number" id="m365commitment-core" name="stable_core_percent" min="0" max="100" value="<?php echo (int) ($input['stable_core_percent'] ?? 80); ?>">
                        </label>
                        <label class="phinit-field" for="m365commitment-horizon">
                            Betrachtungszeitraum
                            <select class="phinit-select" id="m365commitment-horizon" name="horizon_months">
                                <?php foreach ([12 => '12 Monate', 24 => '24 Monate', 36 => '36 Monate'] as $months => $label): ?>
                                <option value="<?php echo (int) $months; ?>"<?php echo $isSelected((string) ($input['horizon_months'] ?? 12), (string) $months); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365commitment-channel">
                            Vertrags-/Beschaffungskanal
                            <select class="phinit-select" id="m365commitment-channel" name="channel">
                                <?php foreach (['csp' => 'CSP / New Commerce', 'mca' => 'Microsoft Customer Agreement', 'ea' => 'Enterprise Agreement'] as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['channel'] ?? 'csp'), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </section>
                </fieldset>

                <section class="m365calc-actions">
                    <button type="submit" class="phinit-btn phinit-btn--primary">Commitment berechnen</button>
                    <a class="phinit-btn phinit-btn--secondary" href="/m365-jahresvertrag-vs-monatsvertrag">Zurücksetzen</a>
                </section>
            </form>
        </section>

        <aside class="m365calc-aside" aria-labelledby="m365commitment-recommendation-title">
            <article class="phinit-note <?php echo $esc($noteClass); ?>">
                <h2 id="m365commitment-recommendation-title"><?php echo $esc($recommendation['title'] ?? 'Empfehlung'); ?></h2>
                <p><?php echo $esc($recommendation['summary'] ?? ''); ?></p>
                <ul class="m365calc-note-list">
                    <li>Bestes Modell: <?php echo $esc($recommendation['best_model'] ?? ''); ?></li>
                    <li>Gesamtkosten im Zeitraum: <?php echo $money($recommendation['best_total'] ?? 0); ?></li>
                    <li>Ersparnis gegenüber Monatslaufzeit: <?php echo $esc($recommendation['best_savings_percent'] ?? 0); ?> %</li>
                </ul>
            </article>
            <article class="phinit-note phinit-note--info">
                <h2>Preis- und SKU-Hinweis</h2>
                <p><?php echo $esc($plan['source_note'] ?? 'Preis vor Bestellung in der aktuellen Preisliste prüfen.'); ?></p>
                <p>Jährlicher Referenzpreis: <?php echo $money($plan['annual_price_month'] ?? 0); ?> / User / Monat · Monatslaufzeit: <?php echo $money($plan['monthly_price_month'] ?? 0); ?> / User / Monat.</p>
            </article>
        </aside>
    </section>

    <section class="phinit-result m365calc-result-card" id="m365calc-result" tabindex="-1" data-m365calc-result aria-labelledby="m365commitment-result-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Auswertung</p>
                <h2 id="m365commitment-result-title">Kostenmodelle im Vergleich</h2>
                <p><?php echo $esc($plan['name'] ?? 'Microsoft 365'); ?> · <?php echo (int) ($input['users'] ?? 1); ?> Startnutzer · <?php echo (int) ($input['horizon_months'] ?? 12); ?> Monate</p>
            </section>
            <section class="m365calc-actions">
                <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Drucken / PDF speichern</button>
                <a class="phinit-btn phinit-btn--primary" href="/kontakt">Lizenzcheck anfragen</a>
            </section>
        </header>

        <section class="m365calc-summary-grid m365calc-commitment-model-grid" aria-label="Commitment-Modelle">
            <?php foreach ($models as $model): ?>
            <?php if (!is_array($model)) { continue; } ?>
            <article class="phinit-card m365calc-mini-card<?php echo (string) ($model['key'] ?? '') === (string) ($result['best_model'] ?? '') ? ' phinit-card--success' : ''; ?>">
                <span><?php echo $esc($model['term_label'] ?? ''); ?></span>
                <strong><?php echo $esc($model['label'] ?? ''); ?></strong>
                <p><?php echo $money($model['total'] ?? 0); ?> im Zeitraum</p>
                <ul class="m365calc-note-list">
                    <li>Ø Monat: <?php echo $money($model['average_monthly'] ?? 0); ?></li>
                    <li>Jahresäquivalent: <?php echo $money($model['yearly_equivalent'] ?? 0); ?></li>
                    <li>Overcommitment: <?php echo (int) ($model['unused_seat_months'] ?? 0); ?> Seat-Monate</li>
                    <li>Erste Rechnung: <?php echo $money($model['first_invoice'] ?? 0); ?></li>
                </ul>
            </article>
            <?php endforeach; ?>
        </section>

        <section class="phinit-card m365calc-roi-chart" aria-labelledby="m365commitment-chart-title">
            <h3 id="m365commitment-chart-title">Kostenbalken</h3>
            <section class="m365calc-chart">
                <?php foreach ($chart as $bar): ?>
                <?php if (!is_array($bar)) { continue; } ?>
                <article class="m365calc-chart__row">
                    <span><?php echo $esc($bar['label'] ?? ''); ?></span>
                    <section class="m365calc-chart__bars">
                        <span class="m365calc-chart__bar" style="--m365calc-chart-width: <?php echo (float) ($bar['width'] ?? 0); ?>%;"><?php echo $money($bar['value'] ?? 0); ?></span>
                    </section>
                </article>
                <?php endforeach; ?>
            </section>
        </section>
    </section>

    <section class="m365calc-result-grid" aria-label="Annahmen und Kanalhinweise">
        <article class="phinit-note phinit-note--warning">
            <h2>Annahmen</h2>
            <ul class="m365calc-note-list">
                <?php foreach (($result['assumption_notes'] ?? []) as $note): ?>
                <li><?php echo $esc($note); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
        <article class="phinit-note phinit-note--info">
            <h2><?php echo $esc($channel['label'] ?? 'Kanalhinweise'); ?></h2>
            <ul class="m365calc-note-list">
                <?php foreach (($channel['notes'] ?? []) as $note): ?>
                <li><?php echo $esc($note); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
    </section>

    <section class="phinit-note phinit-note--info m365calc-source-card" aria-labelledby="m365commitment-sources-title">
        <h2 id="m365commitment-sources-title">Quellenstand</h2>
        <p><?php echo $esc($result['disclaimer'] ?? 'Preis- und Vertragsdaten vor Bestellung prüfen.'); ?></p>
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
