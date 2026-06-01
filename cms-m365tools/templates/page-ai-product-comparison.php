<?php
/**
 * Public Template: AI Pack vs. Copilot Pro Vergleich.
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
$input = is_array($input ?? null) ? $input : CMS_M365CALCULATOR_AI_Product_Comparison::default_input();
$result = is_array($result ?? null) ? $result : CMS_M365CALCULATOR_AI_Product_Comparison::evaluate($input);
$best = is_array($result['best'] ?? null) ? $result['best'] : [];
$status = is_array($result['status'] ?? null) ? $result['status'] : [];
$statusTone = (string) ($status['tone'] ?? 'info');
$statusTone = in_array($statusTone, ['success', 'warning', 'danger', 'info'], true) ? $statusTone : 'info';
$offer = is_array($result['selected_offer'] ?? null) ? $result['selected_offer'] : [];
$roleOptions = is_array($result['role_options'] ?? null) ? $result['role_options'] : CMS_M365CALCULATOR_AI_Product_Comparison::role_options();
$dataOptions = is_array($result['data_scope_options'] ?? null) ? $result['data_scope_options'] : CMS_M365CALCULATOR_AI_Product_Comparison::data_scope_options();
$goalOptions = is_array($result['goal_options'] ?? null) ? $result['goal_options'] : CMS_M365CALCULATOR_AI_Product_Comparison::goal_options();
$offerOptions = is_array($result['dynamic_offer_options'] ?? null) ? $result['dynamic_offer_options'] : CMS_M365CALCULATOR_AI_Product_Comparison::dynamic_offer_options();
$pricingMatrix = is_array($result['pricing_matrix'] ?? null) ? $result['pricing_matrix'] : [];
$pricingTiers = array_values(array_filter((array) ($pricingMatrix['tiers'] ?? []), 'is_array'));
$pricingVendors = array_values(array_filter((array) ($pricingMatrix['vendors'] ?? []), 'is_array'));

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'AI Pack vs. Copilot Pro Vergleich']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-ai-page" id="ai-pack-vs-copilot-pro">
    <header class="m365calc-hero">
        <p class="phinit-overline">Copilot &amp; AI-Angebote</p>
        <section class="m365calc-hero__content" aria-labelledby="m365ai-title">
            <section>
                <h1 id="m365ai-title">AI Pack vs. Copilot Pro vergleichen</h1>
                <p class="phinit-prose">Ordnet Copilot- und AI-Angebote nach Rolle, Datenquelle, Zielbild und Governance-Bedarf ein.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Copilot Tools">
                <a class="phinit-btn phinit-btn--secondary" href="/copilot-lizenz-check">Copilot-Lizenz prüfen</a>
                <a class="phinit-btn phinit-btn--secondary" href="/copilot-roi-rechner">ROI berechnen</a>
            </nav>
        </section>
    </header>

    <section class="m365calc-layout" aria-label="AI Angebotsvergleich">
        <section class="phinit-card" aria-labelledby="m365ai-form-title">
            <header class="m365calc-section__head">
                <section>
                    <h2 id="m365ai-form-title">Szenario einordnen</h2>
                    <p>Wähle Use Case und Leitplanken – die Empfehlung erscheint direkt daneben.</p>
                </section>
            </header>

            <form method="GET" class="m365calc-form m365calc-ai-form">
                <fieldset class="m365calc-fieldset">
                    <legend>Use Case</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365ai-role">
                            Rolle / Zielgruppe
                            <select class="phinit-select" id="m365ai-role" name="role">
                                <?php foreach ($roleOptions as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['role'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365ai-data-scope">
                            Haupt-Datenquelle
                            <select class="phinit-select" id="m365ai-data-scope" name="data_scope">
                                <?php foreach ($dataOptions as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['data_scope'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365ai-goal">
                            Zielbild
                            <select class="phinit-select" id="m365ai-goal" name="goal">
                                <?php foreach ($goalOptions as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['goal'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365ai-offer">
                            Angebotslabel zur Einordnung
                            <select class="phinit-select" id="m365ai-offer" name="dynamic_offer">
                                <?php foreach ($offerOptions as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['dynamic_offer'] ?? 'none'), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365ai-sensitivity">
                            Datenklasse
                            <select class="phinit-select" id="m365ai-sensitivity" name="sensitivity">
                                <?php foreach (['public' => 'Öffentliche / unkritische Inhalte', 'internal' => 'Interne Unternehmensdaten', 'confidential' => 'Vertrauliche oder regulierte Daten'] as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['sensitivity'] ?? 'internal'), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365ai-users">
                            Geplante Nutzer
                            <input class="phinit-input" type="number" id="m365ai-users" name="users" min="1" max="500000" value="<?php echo (int) ($input['users'] ?? 25); ?>">
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Leitplanken</legend>
                    <section class="m365calc-choice-grid">
                        <input type="hidden" name="needs_custom_agents" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="needs_custom_agents" value="1"<?php echo $isChecked(!empty($input['needs_custom_agents'])); ?>>
                            <span>
                                <strong>Eigene Agents oder Workflows nötig</strong>
                                <small>Berücksichtigt Copilot Studio, Pay-as-you-go Agents, Konnektoren und Fachprozesse stärker.</small>
                            </span>
                        </label>
                        <input type="hidden" name="needs_admin_governance" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="needs_admin_governance" value="1"<?php echo $isChecked(!empty($input['needs_admin_governance'])); ?>>
                            <span>
                                <strong>Admin- und Governance-Steuerung wichtig</strong>
                                <small>Gewichtet Unternehmensschutz, Policies, Audit und tenantweite Steuerung höher.</small>
                            </span>
                        </label>
                        <input type="hidden" name="needs_in_app_experience" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="needs_in_app_experience" value="1"<?php echo $isChecked(!empty($input['needs_in_app_experience'])); ?>>
                            <span>
                                <strong>Erlebnis in Microsoft-365-Apps wichtig</strong>
                                <small>Bewertet Word, Excel, PowerPoint, Outlook, Teams und Meeting-Kontext stärker.</small>
                            </span>
                        </label>
                    </section>
                </fieldset>

                <section class="m365calc-actions">
                    <button type="submit" class="phinit-btn phinit-btn--primary">AI-Pfad vergleichen</button>
                    <a class="phinit-btn phinit-btn--secondary" href="/ai-pack-vs-copilot-pro">Zurücksetzen</a>
                </section>
            </form>
        </section>

        <aside class="m365calc-aside" aria-labelledby="m365ai-recommendation-title">
            <article class="phinit-note phinit-note--<?php echo $esc($statusTone); ?>" id="m365calc-result" tabindex="-1" data-m365calc-result>
                <p class="phinit-overline"><?php echo $esc($status['label'] ?? 'Empfehlung'); ?></p>
                <h2 id="m365ai-recommendation-title"><?php echo $esc($best['name'] ?? 'Produktpfad prüfen'); ?></h2>
                <p><?php echo $esc($status['summary'] ?? 'Das Szenario wurde bewertet.'); ?></p>
                <section class="m365calc-score" aria-label="Fit Score">
                    <span>Fit Score</span>
                    <strong><?php echo (int) ($best['score'] ?? 0); ?>%</strong>
                    <div class="m365calc-score__bar"><span style="--m365calc-score-width: <?php echo (int) ($best['score'] ?? 0); ?>%;"></span></div>
                </section>
            </article>

            <article class="phinit-result m365calc-result-card">
                <header>
                    <p class="phinit-overline">Kurzprofil</p>
                    <h2><?php echo $esc($best['short'] ?? $best['name'] ?? 'Empfehlung'); ?></h2>
                    <p><?php echo $esc($best['cost_model'] ?? 'Kostenmodell vor Bestellung prüfen.'); ?></p>
                </header>
                <ul class="m365calc-note-list">
                    <?php foreach (array_slice(array_map('strval', (array) ($best['reasons'] ?? [])), 0, 4) as $reason): ?>
                    <li><?php echo $esc($reason); ?></li>
                    <?php endforeach; ?>
                </ul>
                <footer class="m365calc-actions">
                    <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Drucken / PDF speichern</button>
                    <a class="phinit-btn phinit-btn--primary" href="/kontakt">AI-Beratung anfragen</a>
                </footer>
            </article>
        </aside>
    </section>

    <?php if (!empty($result['warnings'])): ?>
    <section class="phinit-note phinit-note--warning" aria-labelledby="m365ai-warning-title">
        <h2 id="m365ai-warning-title">Wichtige Einordnung</h2>
        <ul class="m365calc-note-list">
            <?php foreach (($result['warnings'] ?? []) as $warning): ?>
            <li><?php echo $esc($warning); ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <section class="m365calc-result-grid" aria-label="Alternativen">
        <?php foreach (($result['alternatives'] ?? []) as $alternative): ?>
        <?php if (!is_array($alternative)) { continue; } ?>
        <article class="phinit-card m365calc-mini-card">
            <span><?php echo (int) ($alternative['score'] ?? 0); ?>% · <?php echo $esc($alternative['fit_label'] ?? 'Alternative'); ?></span>
            <strong><?php echo $esc($alternative['name'] ?? 'Alternative'); ?></strong>
            <p><?php echo $esc($alternative['cost_model'] ?? 'Kostenmodell prüfen.'); ?></p>
        </article>
        <?php endforeach; ?>
    </section>

    <section class="phinit-result m365calc-result-card" aria-labelledby="m365ai-table-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Vergleich</p>
                <h2 id="m365ai-table-title">Produktpfade nebeneinander</h2>
                <p>Kompakter Blick auf Fit, Datenfokus, Kostenmodell und Grenzen je Pfad.</p>
            </section>
        </header>
        <section class="phinit-table-wrap" aria-label="AI Produktvergleich">
            <table class="phinit-table">
                <thead>
                    <tr>
                        <th scope="col">Produktpfad</th>
                        <th scope="col">Kategorie</th>
                        <th scope="col">Datenfokus</th>
                        <th scope="col">Kostenmodell</th>
                        <th scope="col">Fit</th>
                        <th scope="col">Grenzen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($result['comparison_rows'] ?? []) as $row): ?>
                    <?php if (!is_array($row)) { continue; } ?>
                    <tr>
                        <th scope="row"><?php echo $esc($row['name'] ?? ''); ?></th>
                        <td><?php echo $esc($row['category'] ?? ''); ?></td>
                        <td><?php echo $esc($row['data_grounding'] ?? ''); ?></td>
                        <td><?php echo $esc($row['cost_model'] ?? ''); ?></td>
                        <td><strong><?php echo (int) ($row['score'] ?? 0); ?>%</strong><br><small><?php echo $esc($row['fit_label'] ?? ''); ?></small></td>
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

    <?php if ($pricingTiers !== [] && $pricingVendors !== []): ?>
    <section class="phinit-result m365calc-result-card" aria-labelledby="m365ai-pricing-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Pricing Matrix</p>
                <h2 id="m365ai-pricing-title">Preis-Tiers im Direktvergleich</h2>
                <p>Gemeinsame Tiers über Anbieter hinweg; fehlende Werte bleiben als <strong>k. A.</strong> markiert.</p>
                <p><?php echo $esc((string) ($pricingMatrix['alignment_note'] ?? 'Vergleich ist preis-/tierbasiert und nicht namensbasiert.')); ?></p>
            </section>
        </header>

        <section class="phinit-table-wrap" aria-label="Normalisierte AI-Preismatrix">
            <table class="phinit-table m365calc-pricing-table">
                <thead>
                    <tr>
                        <th scope="col">Tier</th>
                        <?php foreach ($pricingVendors as $vendor): ?>
                        <th scope="col"><?php echo $esc($vendor['label'] ?? 'Anbieter'); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pricingTiers as $tier): ?>
                    <?php $tierKey = (string) ($tier['key'] ?? ''); ?>
                    <tr>
                        <th scope="row"><?php echo $esc($tier['label'] ?? $tierKey); ?></th>
                        <?php foreach ($pricingVendors as $vendor): ?>
                        <?php
                        $vendorLabel = (string) ($vendor['label'] ?? 'Anbieter');
                        $cells = is_array($vendor['cells'] ?? null) ? $vendor['cells'] : [];
                        $cell = is_array($cells[$tierKey] ?? null) ? $cells[$tierKey] : [];
                        $items = array_values(array_filter((array) ($cell['items'] ?? []), 'is_array'));
                        ?>
                        <td data-label="<?php echo $esc($vendorLabel); ?>">
                            <?php if ($items === []): ?>
                            <strong class="m365calc-pricing-value">k. A.</strong>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                <?php
                                $value = (string) ($item['price_display'] ?? 'k. A.');
                                $tierName = (string) ($item['tier_label'] ?? 'Tier');
                                $sourceUrl = (string) ($item['source_url'] ?? '');
                                $lastVerified = (string) ($item['last_verified'] ?? ($pricingMatrix['last_verified'] ?? 'k. A.'));
                                $billingNote = (string) ($item['billing_note'] ?? '');
                                $basisNote = (string) ($item['basis_note'] ?? '');
                                $currencyNote = (string) ($item['currency_note'] ?? '');
                                ?>
                                <article class="m365calc-pricing-item">
                                    <strong class="m365calc-pricing-value"><?php echo $esc($value); ?></strong>
                                    <small class="m365calc-pricing-tier"><?php echo $esc($tierName); ?></small>
                                    <small class="m365calc-pricing-meta">geprüft am <?php echo $esc($lastVerified !== '' ? $lastVerified : 'k. A.'); ?></small>
                                    <?php if ($currencyNote !== ''): ?>
                                    <small class="m365calc-pricing-meta"><?php echo $esc($currencyNote); ?></small>
                                    <?php endif; ?>
                                    <?php if ($basisNote !== ''): ?>
                                    <small class="m365calc-pricing-meta"><?php echo $esc($basisNote); ?></small>
                                    <?php endif; ?>
                                    <?php if ($billingNote !== ''): ?>
                                    <small class="m365calc-pricing-meta"><?php echo $esc($billingNote); ?></small>
                                    <?php endif; ?>
                                    <?php if ($sourceUrl !== ''): ?>
                                    <small class="m365calc-pricing-meta">
                                        <a href="<?php echo $esc($sourceUrl); ?>" target="_blank" rel="noopener noreferrer">Quelle</a>
                                    </small>
                                    <?php endif; ?>
                                </article>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="m365calc-result-grid" aria-label="Pricing Hinweise">
            <article class="phinit-note phinit-note--warning">
                <h3>Hinweis</h3>
                <p><?php echo $esc((string) ($pricingMatrix['disclaimer'] ?? 'Preise und Funktionsumfänge ändern sich häufig. Vor Kauf immer die Originalquelle prüfen.')); ?></p>
            </article>
            <article class="phinit-note phinit-note--info">
                <h3>Microsoft-Modell</h3>
                <p><?php echo $esc((string) ($pricingMatrix['microsoft_addon_note'] ?? 'Microsoft Copilot ist in Unternehmen oft als Add-on bzw. über PAYG-Modelle organisiert.')); ?></p>
            </article>
        </section>
    </section>
    <?php endif; ?>

    <section class="m365calc-result-grid" aria-label="Nächste Schritte und Angebotslabel">
        <article class="phinit-note phinit-note--info">
            <h2>Nächste Schritte</h2>
            <ol class="m365calc-note-list">
                <?php foreach (($result['next_steps'] ?? []) as $step): ?>
                <li><?php echo $esc($step); ?></li>
                <?php endforeach; ?>
            </ol>
        </article>
        <article class="phinit-note phinit-note--warning">
            <h2><?php echo $esc($offer['label'] ?? 'Angebotslabel'); ?></h2>
            <p><?php echo !empty($result['manual_review']) ? 'Das gewählte Label ist dynamisch und sollte vor der Angebotsphase aktualisiert werden.' : 'Die Bewertung nutzt die fachlichen Eingaben ohne zusätzliches Speziallabel.'; ?></p>
            <?php if (!empty($offer['notes']) && is_array($offer['notes'])): ?>
            <ul class="m365calc-note-list">
                <?php foreach ($offer['notes'] as $note): ?>
                <li><?php echo $esc($note); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </article>
    </section>

    <section class="phinit-note phinit-note--info m365calc-source-card" aria-labelledby="m365ai-sources-title">
        <h2 id="m365ai-sources-title">Quellenstand</h2>
        <p>Quellenprüfung: <?php echo $esc($result['meta']['source_checked'] ?? '2026-05-16'); ?>. <?php echo $esc($result['meta']['price_basis'] ?? 'Preise und Angebotsumfang vor Bestellung prüfen.'); ?></p>
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
