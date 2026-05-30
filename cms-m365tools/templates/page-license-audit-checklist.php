<?php
/**
 * Public Template: Lizenz-Audit-Checkliste.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$input = is_array($input ?? null) ? $input : CMS_M365CALCULATOR_License_Audit_Checklist::default_input();
$result = is_array($result ?? null) ? $result : CMS_M365CALCULATOR_License_Audit_Checklist::evaluate($input);
$categories = is_array($result['categories'] ?? null) ? $result['categories'] : [];
$summary = is_array($result['summary'] ?? null) ? $result['summary'] : [];
$score = is_array($result['score'] ?? null) ? $result['score'] : [];
$deepLinks = is_array($result['deep_links'] ?? null) ? $result['deep_links'] : [];
$tenantSizeOptions = is_array($result['tenant_size_options'] ?? null) ? $result['tenant_size_options'] : [];
$auditDepthOptions = is_array($result['audit_depth_options'] ?? null) ? $result['audit_depth_options'] : [];
$contextOptions = is_array($result['context_options'] ?? null) ? $result['context_options'] : [];
$pdfTemplate = is_array($result['pdf_template'] ?? null) ? $result['pdf_template'] : [];
$pdfLabels = is_array($pdfTemplate['labels'] ?? null) ? $pdfTemplate['labels'] : [];
$tone = (string) ($score['tone'] ?? 'info');
$tone = in_array($tone, ['success', 'warning', 'danger', 'info'], true) ? $tone : 'info';
$pressure = max(0, min(100, (int) ($score['pressure'] ?? 0)));
$storageKey = (string) ($result['meta']['storage_key'] ?? 'm365calc-license-audit');
$isSelected = static fn(string $left, string $right): string => $left === $right ? ' selected' : '';
$isChecked = static fn(bool $value): string => $value ? ' checked' : '';
$severityTone = static function (string $severity): string {
    return match ($severity) {
        'critical' => 'danger',
        'compliance', 'risk' => 'warning',
        'savings' => 'success',
        default => 'info',
    };
};

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'M365 Lizenz-Audit-Checkliste']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-license-audit-page" id="m365-lizenz-audit-checkliste" data-m365calc-audit data-m365calc-audit-key="<?php echo htmlspecialchars($storageKey, ENT_QUOTES, 'UTF-8'); ?>">
    <header class="m365calc-hero">
        <p class="phinit-overline">Microsoft 365 Lizenz-Audit</p>
        <section class="m365calc-hero__content" aria-labelledby="m365audit-title">
            <section>
                <h1 id="m365audit-title">Lizenz-Audit-Checkliste</h1>
                <p class="phinit-prose">Interaktive Schritt-für-Schritt-Liste für Lizenzbestand, Gruppenlizenzierung, Offboarding, Shared Mailboxes, Copilot, Frontline, Speicher, Backup und Renewal.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Microsoft 365 Tools">
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenzberater">Lizenzberater öffnen</a>
                <a class="phinit-btn phinit-btn--secondary" href="/m365-tools">Toolbox anzeigen</a>
            </nav>
        </section>
    </header>

    <section class="m365calc-layout m365calc-layout--audit" aria-label="Lizenz-Audit Arbeitsbereich">
        <section class="phinit-card" aria-labelledby="m365audit-context-title">
            <header class="m365calc-section__head">
                <section>
                    <h2 id="m365audit-context-title">Audit-Rahmen festlegen</h2>
                    <p>Wähle Mandantengröße, Prüftiefe und aktuelle Schwerpunkte. Die Liste priorisiert passende Themen und Detailrechner.</p>
                </section>
            </header>

            <form method="GET" class="m365calc-form m365calc-audit-context-form">
                <section class="m365calc-form-grid m365calc-form-grid--2">
                    <label class="phinit-field" for="m365audit-tenant-size">
                        Mandantengröße
                        <select class="phinit-select" id="m365audit-tenant-size" name="tenant_size">
                            <?php foreach ($tenantSizeOptions as $key => $label): ?>
                            <option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isSelected((string) ($input['tenant_size'] ?? ''), (string) $key); ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="phinit-field" for="m365audit-depth">
                        Prüftiefe
                        <select class="phinit-select" id="m365audit-depth" name="audit_depth">
                            <?php foreach ($auditDepthOptions as $key => $label): ?>
                            <option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isSelected((string) ($input['audit_depth'] ?? ''), (string) $key); ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </section>

                <fieldset class="m365calc-fieldset">
                    <legend>Schwerpunkte</legend>
                    <section class="m365calc-choice-grid">
                        <?php foreach ($contextOptions as $key => $option): ?>
                        <?php if (!is_array($option)) { continue; } ?>
                        <label class="m365calc-choice" for="m365audit-context-<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="checkbox" id="m365audit-context-<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>" value="1"<?php echo $isChecked(!empty($input[$key])); ?>>
                            <span>
                                <strong><?php echo htmlspecialchars((string) ($option['label'] ?? $key), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?php echo htmlspecialchars((string) ($option['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </section>
                </fieldset>

                <section class="m365calc-actions">
                    <button type="submit" class="phinit-btn phinit-btn--primary">Audit priorisieren</button>
                    <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenz-audit-checkliste">Rahmen zurücksetzen</a>
                </section>
            </form>
        </section>

        <aside class="m365calc-aside" aria-labelledby="m365audit-summary-title">
            <article class="phinit-note phinit-note--<?php echo htmlspecialchars($tone, ENT_QUOTES, 'UTF-8'); ?>" id="m365calc-result" tabindex="-1" data-m365calc-result>
                <p class="phinit-overline">Audit-Einordnung</p>
                <h2 id="m365audit-summary-title"><?php echo htmlspecialchars((string) ($score['label'] ?? 'Audit starten'), ENT_QUOTES, 'UTF-8'); ?></h2>
                <p><?php echo htmlspecialchars((string) ($score['reason'] ?? 'Die Checkliste wurde vorbereitet.'), ENT_QUOTES, 'UTF-8'); ?></p>
                <section class="m365calc-score" aria-label="Audit-Priorität">
                    <span>Priorität</span>
                    <strong><?php echo (int) $pressure; ?>%</strong>
                    <div class="m365calc-score__bar"><span style="--m365calc-score-width: <?php echo (int) $pressure; ?>%;"></span></div>
                </section>
            </article>

            <article class="phinit-result m365calc-result-card m365calc-audit-progress-card">
                <header>
                    <p class="phinit-overline"><?php echo htmlspecialchars((string) ($pdfLabels['progress'] ?? 'Audit-Fortschritt'), ENT_QUOTES, 'UTF-8'); ?></p>
                    <h2><span data-m365calc-audit-percent>0</span>% abgeschlossen</h2>
                    <p><span data-m365calc-audit-done>0</span> von <span data-m365calc-audit-total><?php echo (int) ($summary['total_items'] ?? 0); ?></span> Prüfpunkten erledigt.</p>
                </header>
                <div class="m365calc-audit-progress" aria-hidden="true"><span data-m365calc-audit-bar style="--m365calc-audit-progress: 0%;"></span></div>
                <dl class="m365calc-kpi-list">
                    <div>
                        <dt>Fokus</dt>
                        <dd><?php echo htmlspecialchars((string) ($summary['focus_label'] ?? 'Lizenzbestand'), ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <div>
                        <dt>Priorisierte Punkte</dt>
                        <dd><?php echo (int) ($summary['priority_count'] ?? 0); ?></dd>
                    </div>
                    <div>
                        <dt>Prüftiefe</dt>
                        <dd><?php echo htmlspecialchars((string) ($summary['depth_label'] ?? 'Audit'), ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                </dl>
                <footer class="m365calc-actions">
                    <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print><?php echo htmlspecialchars((string) ($pdfLabels['print_button'] ?? 'Drucken / PDF speichern'), ENT_QUOTES, 'UTF-8'); ?></button>
                    <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-audit-reset><?php echo htmlspecialchars((string) ($pdfLabels['reset_progress'] ?? 'Fortschritt zurücksetzen'), ENT_QUOTES, 'UTF-8'); ?></button>
                </footer>
            </article>
        </aside>
    </section>

    <section class="m365calc-summary-grid" aria-label="Audit-Kennzahlen">
        <article class="phinit-card m365calc-mini-card">
            <span>Mandant</span>
            <strong><?php echo htmlspecialchars((string) ($summary['tenant_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
            <p>Rahmen für Gewichtung und Detailtiefe.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Kategorien</span>
            <strong><?php echo count($categories); ?></strong>
            <p>Identität, Lizenzierung, Mail, Segmentierung, Speicher und Beschaffung.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Prüfpunkte</span>
            <strong><?php echo (int) ($summary['total_items'] ?? 0); ?></strong>
            <p>Interaktiv abhaken und später als Browser-Druck sichern.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Quellenstand</span>
            <strong><?php echo htmlspecialchars((string) ($result['meta']['source_checked'] ?? '2026-05-17'), ENT_QUOTES, 'UTF-8'); ?></strong>
            <p>Microsoft Learn Seiten fachlich verdichtet.</p>
        </article>
    </section>

    <section class="phinit-result m365calc-result-card" aria-labelledby="m365audit-checklist-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Interaktive Checkliste</p>
                <h2 id="m365audit-checklist-title">Prüfpunkte Schritt für Schritt abhaken</h2>
                <p>Der Fortschritt bleibt im Browser erhalten. Für die Übergabe kannst du die Zusammenfassung drucken oder als PDF speichern.</p>
            </section>
        </header>

        <section class="m365calc-audit-category-grid" aria-label="Lizenz-Audit Kategorien">
            <?php foreach ($categories as $categoryKey => $category): ?>
            <?php if (!is_array($category)) { continue; } ?>
            <article class="phinit-card m365calc-audit-category" data-m365calc-audit-category="<?php echo htmlspecialchars((string) $categoryKey, ENT_QUOTES, 'UTF-8'); ?>">
                <header>
                    <p class="phinit-overline"><?php echo htmlspecialchars((string) $categoryKey, ENT_QUOTES, 'UTF-8'); ?></p>
                    <h3><?php echo htmlspecialchars((string) ($category['label'] ?? $categoryKey), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p><?php echo htmlspecialchars((string) ($category['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                </header>
                <section class="m365calc-audit-list">
                    <?php foreach ((array) ($category['items'] ?? []) as $item): ?>
                    <?php if (!is_array($item)) { continue; } ?>
                    <?php $severity = (string) ($item['severity'] ?? 'info'); ?>
                    <?php $itemTone = $severityTone($severity); ?>
                    <label class="m365calc-audit-item m365calc-audit-item--<?php echo htmlspecialchars($itemTone, ENT_QUOTES, 'UTF-8'); ?>" for="m365audit-item-<?php echo htmlspecialchars((string) ($item['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="checkbox" id="m365audit-item-<?php echo htmlspecialchars((string) ($item['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-m365calc-audit-item data-m365calc-audit-id="<?php echo htmlspecialchars((string) ($item['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-m365calc-audit-category-label="<?php echo htmlspecialchars((string) ($category['label'] ?? $categoryKey), ENT_QUOTES, 'UTF-8'); ?>" data-m365calc-audit-summary-open="<?php echo htmlspecialchars((string) ($item['summary_if_open'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-m365calc-audit-summary-done="<?php echo htmlspecialchars((string) ($item['summary_if_done'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <span>
                            <span class="m365calc-audit-item__head">
                                <strong><?php echo htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <em><?php echo htmlspecialchars((string) $severity, ENT_QUOTES, 'UTF-8'); ?></em>
                            </span>
                            <small><?php echo htmlspecialchars((string) ($item['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                            <?php if (!empty($item['source_url'])): ?>
                            <a href="<?php echo htmlspecialchars((string) $item['source_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Microsoft-Quelle öffnen</a>
                            <?php endif; ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </section>
            </article>
            <?php endforeach; ?>
        </section>
    </section>

    <section class="m365calc-result-grid" aria-label="Audit-Zusammenfassung">
        <article class="phinit-note phinit-note--warning m365calc-audit-summary-list">
            <p class="phinit-overline"><?php echo htmlspecialchars((string) ($pdfLabels['open_items'] ?? 'Offene Punkte'), ENT_QUOTES, 'UTF-8'); ?></p>
            <h2>Noch zu prüfen</h2>
            <ul class="m365calc-note-list" data-m365calc-audit-open-list>
                <?php foreach (array_slice((array) ($summary['priority_items'] ?? []), 0, 6) as $item): ?>
                <?php if (!is_array($item)) { continue; } ?>
                <li><?php echo htmlspecialchars((string) ($item['summary_if_open'] ?? $item['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
        <article class="phinit-note phinit-note--success m365calc-audit-summary-list">
            <p class="phinit-overline"><?php echo htmlspecialchars((string) ($pdfLabels['done_items'] ?? 'Erledigte Punkte'), ENT_QUOTES, 'UTF-8'); ?></p>
            <h2>Bereits bestätigt</h2>
            <ul class="m365calc-note-list" data-m365calc-audit-done-list>
                <li>Noch keine Punkte bestätigt.</li>
            </ul>
        </article>
    </section>

    <section class="phinit-result m365calc-result-card" aria-labelledby="m365audit-links-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Empfohlene Detailrechner</p>
                <h2 id="m365audit-links-title">Spezialfragen direkt vertiefen</h2>
                <p>Die Links orientieren sich an deinen Schwerpunkten und an den gewichteten Auditpunkten.</p>
            </section>
        </header>
        <section class="m365calc-tool-grid m365calc-audit-link-grid">
            <?php foreach ($deepLinks as $link): ?>
            <?php if (!is_array($link)) { continue; } ?>
            <article class="phinit-card m365calc-tool-card">
                <header class="m365calc-tool-card__head">
                    <section>
                        <p class="phinit-overline"><?php echo htmlspecialchars((string) ($link['category'] ?? 'Tool'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <h3><?php echo htmlspecialchars((string) ($link['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
                    </section>
                    <?php if ((int) ($link['weight'] ?? 0) > 0): ?>
                    <span class="m365calc-badge m365calc-badge--success">Priorisiert</span>
                    <?php endif; ?>
                </header>
                <p><?php echo htmlspecialchars((string) ($link['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                <a class="phinit-btn phinit-btn--secondary" href="<?php echo htmlspecialchars((string) ($link['url'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>">Tool öffnen</a>
            </article>
            <?php endforeach; ?>
        </section>
    </section>

    <section class="m365calc-result-grid m365calc-audit-print" aria-label="Druckzusammenfassung">
        <article class="phinit-result m365calc-result-card">
            <p class="phinit-overline">Druck- und PDF-Zusammenfassung</p>
            <h2><?php echo htmlspecialchars((string) ($summary['focus_label'] ?? 'Lizenzbestand'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p>Diese Zusammenfassung enthält Fortschritt, offene Punkte, erledigte Punkte und empfohlene Detailrechner. Verwende den Drucken-Button, um die Ansicht als PDF abzulegen.</p>
            <dl class="m365calc-kpi-list">
                <div>
                    <dt>Fortschritt</dt>
                    <dd><span data-m365calc-audit-print-percent>0</span>%</dd>
                </div>
                <div>
                    <dt>Erledigt</dt>
                    <dd><span data-m365calc-audit-print-done>0</span> / <?php echo (int) ($summary['total_items'] ?? 0); ?></dd>
                </div>
            </dl>
        </article>
        <article class="phinit-note phinit-note--info m365calc-source-card">
            <h2>Quellenstand</h2>
            <p>Quellenprüfung: <?php echo htmlspecialchars((string) ($result['meta']['source_checked'] ?? '2026-05-17'), ENT_QUOTES, 'UTF-8'); ?>. Microsoft-Learn-Inhalte wurden als Auditregeln verdichtet.</p>
            <details>
                <summary>Quellen anzeigen</summary>
                <ul class="m365calc-note-list">
                    <?php foreach ((array) ($result['sources'] ?? []) as $source): ?>
                    <li><a href="<?php echo htmlspecialchars((string) $source, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars((string) $source, ENT_QUOTES, 'UTF-8'); ?></a></li>
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
