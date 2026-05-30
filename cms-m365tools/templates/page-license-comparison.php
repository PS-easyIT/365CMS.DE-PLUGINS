<?php
/**
 * Public Template: M365 Lizenzvergleich.
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
$isChecked = static fn(array $values, string $value): string => in_array($value, $values, true) ? ' checked' : '';
$isSelected = static fn(string $left, string $right): string => $left === $right ? ' selected' : '';
$statusLabel = static function (string $status, array $labels): string {
    return (string) ($labels[$status] ?? $status);
};
$selectedSlugs = array_values(array_map('strval', $filters['selected'] ?? []));
$featureFilters = array_values(array_map('strval', $filters['features'] ?? []));
$selectedPlans = is_array($result['selected_plans'] ?? null) ? $result['selected_plans'] : [];
$plans = is_array($result['plans'] ?? null) ? $result['plans'] : [];
$features = is_array($result['features'] ?? null) ? $result['features'] : [];
$groups = is_array($result['feature_groups'] ?? null) ? $result['feature_groups'] : [];
$statusLabels = is_array($result['status_short_labels'] ?? null) ? $result['status_short_labels'] : [];
$featureDifferences = is_array($result['highlights']['feature_differences'] ?? null) ? $result['highlights']['feature_differences'] : [];
$planNotes = is_array($result['plan_notes'] ?? null) ? $result['plan_notes'] : [];
$cheapestSlug = (string) ($result['highlights']['cheapest_slug'] ?? '');

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'M365 Lizenzvergleich']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-comparison-page" id="m365-license-comparison">
    <header class="m365calc-hero">
        <p class="phinit-overline">Lizenzvergleich</p>
        <section class="m365calc-hero__content" aria-labelledby="m365compare-title">
            <section>
                <h1 id="m365compare-title">Microsoft 365 Lizenzvergleich</h1>
                <p class="phinit-prose">Vergleicht Microsoft-365-Pläne nach Desktop Apps, Mail, Teams, SharePoint, Copilot-Fähigkeit, Security, Power Platform und Zusatzdiensten.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Lizenztools">
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenzberater">Lizenzberater öffnen</a>
                <a class="phinit-btn phinit-btn--secondary" href="/copilot-lizenz-check">Copilot prüfen</a>
            </nav>
        </section>
    </header>

    <section class="phinit-card" aria-labelledby="m365compare-filter-title">
        <header class="m365calc-section__head">
            <section>
                <h2 id="m365compare-filter-title">Vergleich filtern</h2>
                <p>Wähle Feature-Filter und Vergleichsspalten, um die Matrix auf deinen Lizenzfall einzugrenzen.</p>
            </section>
        </header>

        <form method="GET" class="m365calc-form m365calc-comparison-filter">
            <section class="m365calc-form-grid m365calc-form-grid--3">
                <label class="phinit-field" for="m365compare-q">
                    Stichwort
                    <input class="phinit-input" type="search" id="m365compare-q" name="q" value="<?php echo $esc($filters['q'] ?? ''); ?>" placeholder="z. B. Desktop, Copilot, Phone">
                </label>
                <label class="phinit-field" for="m365compare-family">
                    Lizenzfamilie
                    <select class="phinit-select" id="m365compare-family" name="family">
                        <?php foreach (($result['family_options'] ?? []) as $key => $label): ?>
                        <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($filters['family'] ?? 'all'), (string) $key); ?>><?php echo $esc($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="phinit-field" for="m365compare-sort">
                    Sortierung
                    <select class="phinit-select" id="m365compare-sort" name="sort">
                        <?php foreach (['price_asc' => 'Preis aufsteigend', 'price_desc' => 'Preis absteigend', 'name' => 'Name', 'security' => 'Security-Abdeckung', 'copilot' => 'Copilot-Abdeckung'] as $key => $label): ?>
                        <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($filters['sort'] ?? 'price_asc'), (string) $key); ?>><?php echo $esc($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </section>

            <fieldset class="m365calc-fieldset">
                <legend>Feature-Filter</legend>
                <section class="m365calc-choice-grid m365calc-choice-grid--compact">
                    <?php foreach (($result['feature_filter_options'] ?? []) as $key => $label): ?>
                    <label class="m365calc-choice">
                        <input type="checkbox" name="features[]" value="<?php echo $esc($key); ?>"<?php echo $isChecked($featureFilters, (string) $key); ?>>
                        <span>
                            <strong><?php echo $esc($label); ?></strong>
                            <small>Nur Pläne anzeigen, die diesen Pfad abdecken oder als Add-on-Pfad unterstützen.</small>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </section>
            </fieldset>

            <fieldset class="m365calc-fieldset">
                <legend>Vergleichsspalten auswählen</legend>
                <?php if (empty($plans)): ?>
                <section class="phinit-empty-state" role="status" aria-live="polite">
                    <h3>Keine Pläne gefunden</h3>
                    <p>Bitte Filter anpassen oder zurücksetzen.</p>
                </section>
                <?php else: ?>
                <section class="m365calc-plan-select-grid" aria-label="Auswählbare Vergleichsspalten">
                    <?php foreach ($plans as $plan): ?>
                    <?php $slug = (string) ($plan['slug'] ?? ''); ?>
                    <label class="m365calc-plan-select">
                        <input type="checkbox" name="selected[]" value="<?php echo $esc($slug); ?>"<?php echo $isChecked($selectedSlugs, $slug); ?>>
                        <span>
                            <strong><?php echo $esc($plan['name'] ?? ''); ?></strong>
                            <small><?php echo $money($plan['price_month'] ?? 0); ?> / User / Monat · <?php echo $esc($plan['family'] ?? ''); ?></small>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </section>
                <p class="m365calc-help-text">Bis zu sechs Spalten werden in der Funktionsmatrix angezeigt. Wenn keine Spalte ausgewählt ist, nutzt das Modul die ersten passenden Pläne.</p>
                <?php endif; ?>
            </fieldset>

            <section class="m365calc-actions">
                <button type="submit" class="phinit-btn phinit-btn--primary">Vergleich aktualisieren</button>
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenzvergleich">Filter zurücksetzen</a>
            </section>
        </form>
    </section>

    <section class="phinit-result m365calc-result-card" id="m365calc-result" tabindex="-1" aria-labelledby="m365compare-result-title" data-m365calc-result>
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Auswertung</p>
                <h2 id="m365compare-result-title">Vergleichsmatrix</h2>
                <p><?php echo (int) ($result['counts']['filtered'] ?? 0); ?> von <?php echo (int) ($result['counts']['all'] ?? 0); ?> Plänen passen zu den aktuellen Filtern. <?php echo (int) ($result['counts']['selected'] ?? 0); ?> Spalten sind ausgewählt.</p>
            </section>
            <section class="m365calc-actions">
                <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Drucken / PDF speichern</button>
                <a class="phinit-btn phinit-btn--primary" href="/kontakt">Lizenzcheck anfragen</a>
            </section>
        </header>

        <?php if (empty($selectedPlans)): ?>
        <section class="phinit-empty-state" role="status" aria-live="polite">
            <h3>Keine Vergleichsspalten verfügbar</h3>
            <p>Bitte wähle mindestens eine passende Lizenz aus oder setze die Filter zurück.</p>
        </section>
        <?php else: ?>
        <section class="m365calc-summary-grid" aria-label="Schnelleinschätzung">
            <?php foreach ($selectedPlans as $plan): ?>
            <?php $slug = (string) ($plan['slug'] ?? ''); ?>
            <article class="phinit-card m365calc-mini-card<?php echo $slug === $cheapestSlug ? ' phinit-card--success' : ''; ?>">
                <span><?php echo $slug === $cheapestSlug ? 'Günstigster Einstieg' : $esc($plan['family'] ?? 'Plan'); ?></span>
                <strong><?php echo $esc($plan['name'] ?? ''); ?></strong>
                <p><?php echo $money($plan['price_month'] ?? 0); ?> / User / Monat</p>
            </article>
            <?php endforeach; ?>
        </section>

        <section class="phinit-table-wrap m365calc-compare-wrap" aria-label="Feature-Vergleichstabelle">
            <table class="phinit-table m365calc-compare-table">
                <thead>
                    <tr>
                        <th scope="col">Funktion</th>
                        <?php foreach ($selectedPlans as $plan): ?>
                        <th scope="col">
                            <span class="m365calc-plan-heading"><?php echo $esc($plan['name'] ?? ''); ?></span>
                            <span><?php echo $money($plan['price_month'] ?? 0); ?></span>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group): ?>
                    <?php if (empty($group['features']) || !is_array($group['features'])) { continue; } ?>
                    <tr class="m365calc-compare-table__group">
                        <th scope="row" colspan="<?php echo (int) count($selectedPlans) + 1; ?>"><?php echo $esc($group['label'] ?? $group['key'] ?? 'Features'); ?></th>
                    </tr>
                    <?php foreach ($group['features'] as $featureKey => $feature): ?>
                    <?php if (!is_array($feature)) { continue; } ?>
                    <tr class="<?php echo !empty($featureDifferences[$featureKey]) ? 'm365calc-compare-table__diff' : ''; ?>">
                        <th scope="row">
                            <span class="m365calc-feature-title"><?php echo $esc($feature['label'] ?? $featureKey); ?></span>
                            <small><?php echo $esc($feature['description'] ?? ''); ?></small>
                        </th>
                        <?php foreach ($selectedPlans as $plan): ?>
                        <?php $status = CMS_M365CALCULATOR_License_Comparison::feature_status($plan, (string) $featureKey, $feature); ?>
                        <td>
                            <span class="m365calc-status m365calc-status--<?php echo $esc($status['status']); ?>">
                                <strong><?php echo $esc($statusLabel((string) $status['status'], $statusLabels)); ?></strong>
                                <small><?php echo $esc($status['note'] ?? ''); ?></small>
                            </span>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <?php endif; ?>
    </section>

    <?php if (!empty($plans)): ?>
    <section class="m365calc-section" aria-labelledby="m365compare-plan-list-title">
        <header class="m365calc-section__head">
            <section>
                <p class="phinit-overline">Gefilterte Pläne</p>
                <h2 id="m365compare-plan-list-title">Lizenzkarten</h2>
                <p>Die Karten zeigen Schnelllabels, Preisannahmen und wichtige Fußnoten je Lizenz.</p>
            </section>
        </header>
        <section class="m365calc-plan-grid" aria-label="Gefilterte Lizenzkarten">
            <?php foreach ($plans as $plan): ?>
            <?php $slug = (string) ($plan['slug'] ?? ''); ?>
            <article class="phinit-card m365calc-plan-card">
                <header>
                    <span class="m365calc-badge m365calc-badge--muted"><?php echo $esc($plan['family'] ?? ''); ?></span>
                    <h3><?php echo $esc($plan['name'] ?? ''); ?></h3>
                    <p><?php echo $money($plan['price_month'] ?? 0); ?> / User / Monat · max. <?php echo (int) ($plan['max_users'] ?? 0); ?> Benutzer</p>
                </header>

                <?php if (!empty($plan['_badges']) && is_array($plan['_badges'])): ?>
                <ul class="m365calc-badge-list" aria-label="Plan-Highlights">
                    <?php foreach ($plan['_badges'] as $badge): ?>
                    <?php if (!is_array($badge)) { continue; } ?>
                    <li><span class="m365calc-badge"><?php echo $esc($badge['label'] ?? ''); ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <?php if (!empty($planNotes[$slug]) && is_array($planNotes[$slug])): ?>
                <ul class="m365calc-note-list">
                    <?php foreach ($planNotes[$slug] as $note): ?>
                    <li><?php echo $esc($note); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </section>
    </section>
    <?php endif; ?>

    <section class="m365calc-result-grid" aria-label="Hinweise und Quellen">
        <article class="phinit-note phinit-note--warning">
            <h2>Wichtige Lizenzhinweise</h2>
            <ul class="m365calc-note-list">
                <?php foreach (($result['notes'] ?? []) as $note): ?>
                <li><?php echo $esc($note); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
        <article class="phinit-note phinit-note--info m365calc-source-card">
            <h2>Quellenstand</h2>
            <p>Die Matrix kombiniert Microsoft-Leitplanken mit dem migrierten m365lic-Preiskatalog. Preise und Verfügbarkeiten bitte vor Bestellung prüfen.</p>
            <details>
                <summary>Quellen anzeigen</summary>
                <ul class="m365calc-note-list">
                    <?php foreach (($result['sources'] ?? []) as $source): ?>
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
