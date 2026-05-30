<?php
/**
 * Public Template: Archive Mailbox Rechner.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$fmtGb = static function (mixed $value): string {
    $number = (float) $value;
    if ($number >= 1000) {
        return number_format($number / 1000, 2, ',', '.') . ' TB';
    }

    return number_format($number, 1, ',', '.') . ' GB';
};
$money = static fn(mixed $value): string => number_format((float) $value, 2, ',', '.') . ' €';
$isSelected = static fn(string $left, string $right): string => $left === $right ? ' selected' : '';
$isChecked = static fn(bool $value): string => $value ? ' checked' : '';
$input = is_array($input ?? null) ? $input : CMS_M365CALCULATOR_Archive_Mailbox_Calculator::default_input();
$result = is_array($result ?? null) ? $result : CMS_M365CALCULATOR_Archive_Mailbox_Calculator::evaluate($input);
$capacity = is_array($result['capacity'] ?? null) ? $result['capacity'] : [];
$plan = is_array($result['plan'] ?? null) ? $result['plan'] : [];
$recommendation = is_array($result['recommendation'] ?? null) ? $result['recommendation'] : [];
$status = (string) ($result['status'] ?? 'ok');
$statusClass = $status === 'danger' ? 'phinit-note--danger' : ($status === 'warning' ? 'phinit-note--warning' : 'phinit-note--success');

if (class_exists('CMS\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'Archive Mailbox Rechner']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-archive-page" id="m365-archive-mailbox-rechner">
    <header class="m365calc-hero">
        <p class="phinit-overline">Exchange Online Archivierung</p>
        <section class="m365calc-hero__content" aria-labelledby="m365archive-title">
            <section>
                <h1 id="m365archive-title">Archive Mailbox Rechner</h1>
                <p class="phinit-prose">Prüft Primärmailbox, Archiv, Auto-expanding Archive, Shared-Mailbox-Sonderfälle und Hold-/Purview-Anforderungen gegen gepflegte Microsoft-365-Lizenzpfade.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Exchange- und Lizenztools">
                <a class="phinit-btn phinit-btn--secondary" href="/shared-mailbox-vs-lizenz">Shared-Mailbox Rechner</a>
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenzmatrix">Lizenzmatrix</a>
            </nav>
        </section>
    </header>

    <section class="m365calc-layout m365calc-layout--archive">
        <section class="phinit-card" aria-labelledby="m365archive-form-title">
            <header class="m365calc-section__head">
                <section>
                    <h2 id="m365archive-form-title">Archivbedarf eingeben</h2>
                    <p>Trage Plan, Mailboxtyp und Speicherwerte ein, um Archivkapazität, Lizenzpfad und Governance-Risiken einzuordnen.</p>
                </section>
            </header>

            <form method="GET" class="m365calc-form m365calc-archive-form">
                <fieldset class="m365calc-fieldset">
                    <legend>Mailbox & Lizenz</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365archive-base-plan">
                            Aktueller Plan
                            <select class="phinit-select" id="m365archive-base-plan" name="base_plan">
                                <?php foreach (($result['plan_options'] ?? []) as $slug => $label): ?>
                                <option value="<?php echo $esc($slug); ?>"<?php echo $isSelected((string) ($input['base_plan'] ?? ''), (string) $slug); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365archive-mailbox-type">
                            Mailbox-Typ
                            <select class="phinit-select" id="m365archive-mailbox-type" name="mailbox_type">
                                <?php foreach (['user' => 'User Mailbox', 'shared' => 'Shared Mailbox', 'resource' => 'Resource Mailbox'] as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['mailbox_type'] ?? 'user'), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365archive-mailbox-count">
                            Anzahl Mailboxen
                            <input class="phinit-input" type="number" id="m365archive-mailbox-count" name="mailbox_count" min="1" max="500000" value="<?php echo (int) ($input['mailbox_count'] ?? 1); ?>">
                        </label>
                        <label class="phinit-field" for="m365archive-retention-years">
                            Aufbewahrung in Jahren
                            <input class="phinit-input" type="number" id="m365archive-retention-years" name="retention_years" min="0" max="100" value="<?php echo (int) ($input['retention_years'] ?? 0); ?>">
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Speicher & Wachstum</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365archive-primary-gb">
                            Aktuelle Primärmailbox in GB
                            <input class="phinit-input" type="number" id="m365archive-primary-gb" name="current_primary_gb" min="0" max="100000" step="0.1" value="<?php echo $esc($input['current_primary_gb'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365archive-current-gb">
                            Aktuelles Archiv in GB
                            <input class="phinit-input" type="number" id="m365archive-current-gb" name="current_archive_gb" min="0" max="1500000" step="0.1" value="<?php echo $esc($input['current_archive_gb'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365archive-monthly-growth">
                            Wachstum pro Monat in GB
                            <input class="phinit-input" type="number" id="m365archive-monthly-growth" name="monthly_growth_gb" min="0" max="100000" step="0.1" value="<?php echo $esc($input['monthly_growth_gb'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365archive-daily-growth">
                            Archivwachstum pro Tag in GB
                            <input class="phinit-input" type="number" id="m365archive-daily-growth" name="daily_archive_growth_gb" min="0" max="1000" step="0.1" value="<?php echo $esc($input['daily_archive_growth_gb'] ?? 0); ?>">
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Zielbild</legend>
                    <section class="m365calc-choice-grid">
                        <?php foreach (['none' => 'Kein Archiv benötigt', 'standard' => 'Standardarchiv reicht', 'auto_expand' => 'Auto-expanding Archive prüfen'] as $key => $label): ?>
                        <label class="m365calc-choice">
                            <input type="radio" name="archive_goal" value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['archive_goal'] ?? 'auto_expand'), (string) $key) === ' selected' ? ' checked' : ''; ?>>
                            <span>
                                <strong><?php echo $esc($label); ?></strong>
                                <small><?php echo $key === 'auto_expand' ? 'Für Archive über Standardgröße oder langfristige Aufbewahrung.' : 'Für kleinere oder nicht archivpflichtige Szenarien.'; ?></small>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </section>
                    <section class="m365calc-choice-grid">
                        <input type="hidden" name="needs_hold" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="needs_hold" value="1"<?php echo $isChecked(!empty($input['needs_hold'])); ?>>
                            <span>
                                <strong>Litigation Hold / In-Place Hold nötig</strong>
                                <small>Prüft, ob Plan oder Archiv-Add-on für Hold- und Compliance-Szenarien nötig wird.</small>
                            </span>
                        </label>
                        <input type="hidden" name="needs_purview_premium" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="needs_purview_premium" value="1"<?php echo $isChecked(!empty($input['needs_purview_premium'])); ?>>
                            <span>
                                <strong>Purview Premium-Funktionen nötig</strong>
                                <small>Markiert eDiscovery Premium, Audit Premium, automatische Labels oder Insider Risk als separaten Lizenzpfad.</small>
                            </span>
                        </label>
                    </section>
                </fieldset>

                <section class="m365calc-actions">
                    <button type="submit" class="phinit-btn phinit-btn--primary">Archivbedarf prüfen</button>
                    <a class="phinit-btn phinit-btn--secondary" href="/m365-archive-mailbox-rechner">Zurücksetzen</a>
                </section>
            </form>
        </section>

        <aside class="m365calc-aside" aria-labelledby="m365archive-result-title">
            <article class="phinit-note <?php echo $esc($statusClass); ?>" id="m365calc-result" tabindex="-1" data-m365calc-result>
                <p class="phinit-overline">Empfehlung</p>
                <h2 id="m365archive-result-title"><?php echo $esc($recommendation['title'] ?? 'Archivplanung prüfen'); ?></h2>
                <p><?php echo $esc($recommendation['text'] ?? ''); ?></p>
            </article>

            <article class="phinit-result m365calc-result-card">
                <header>
                    <p class="phinit-overline">Planungswerte</p>
                    <h2><?php echo $esc($plan['short'] ?? $plan['name'] ?? 'Plan'); ?></h2>
                    <p><?php echo $esc($plan['note'] ?? ''); ?></p>
                </header>
                <section class="m365calc-price-stack" aria-label="Kapazitätswerte">
                    <article><span>Primär-Kapazität</span><strong><?php echo $fmtGb($capacity['primary_gb'] ?? 0); ?></strong></article>
                    <article><span>Primär 12 Monate</span><strong><?php echo $fmtGb($capacity['projected_primary_gb'] ?? 0); ?></strong></article>
                    <article><span>Archiv-Kapazität</span><strong><?php echo $fmtGb($capacity['archive_gb'] ?? 0); ?></strong></article>
                    <article><span>Archiv 12 Monate</span><strong><?php echo $fmtGb($capacity['projected_archive_gb'] ?? 0); ?></strong></article>
                    <article><span>Auto-expanding</span><strong><?php echo !empty($capacity['auto_expanding_included']) ? 'enthalten' : (!empty($capacity['auto_expanding_needed']) ? 'Add-on/Upgrade' : 'nicht nötig'); ?></strong></article>
                    <article><span>Zusatzkosten Add-on</span><strong><?php echo $money($result['estimated_addon_monthly'] ?? 0); ?>/Monat</strong></article>
                </section>
                <footer class="m365calc-actions">
                    <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Drucken / PDF speichern</button>
                </footer>
            </article>
        </aside>
    </section>

    <section class="m365calc-result-grid" aria-label="Archivhinweise">
        <article class="phinit-card m365calc-mini-card">
            <span>Auto-expanding Trigger</span>
            <strong><?php echo $fmtGb($capacity['auto_expanding_trigger_gb'] ?? 90); ?></strong>
            <p>Ab dieser Größenordnung wird die zusätzliche Archivkapazität relevant; Provisionierung kann bis zu <?php echo (int) ($capacity['provisioning_days'] ?? 30); ?> Tage dauern.</p>
        </article>
        <article class="phinit-card m365calc-mini-card">
            <span>Mailbox-Typ</span>
            <strong><?php echo $esc(['user' => 'User Mailbox', 'shared' => 'Shared Mailbox', 'resource' => 'Resource Mailbox'][(string) ($input['mailbox_type'] ?? 'user')] ?? 'User Mailbox'); ?></strong>
            <p>Shared- und Resource-Mailboxes haben eigene Lizenz- und Größenregeln, besonders ab 50 GB oder bei Compliance-Funktionen.</p>
        </article>
    </section>

    <section class="phinit-result m365calc-result-card" aria-labelledby="m365archive-actions-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Nächste Schritte</p>
                <h2 id="m365archive-actions-title">Was jetzt zu tun ist</h2>
                <p>Die Auswertung trennt Kapazitätsbedarf, Lizenzpfad und Governance-Risiken.</p>
            </section>
        </header>
        <section class="m365calc-result-grid">
            <article class="phinit-note phinit-note--info">
                <h3>Empfohlene Aktionen</h3>
                <?php if (empty($result['actions'])): ?>
                <p>Keine zwingenden Aktionen erkannt. Vor Rollout trotzdem Tenant, Segment und Microsoft-Lizenzbedingungen prüfen.</p>
                <?php else: ?>
                <ul class="m365calc-note-list">
                    <?php foreach (($result['actions'] ?? []) as $action): ?>
                    <li><?php echo $esc($action); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </article>
            <article class="phinit-note phinit-note--warning">
                <h3>Warnungen</h3>
                <ul class="m365calc-note-list">
                    <?php foreach (($result['warnings'] ?? []) as $warning): ?>
                    <li><?php echo $esc($warning); ?></li>
                    <?php endforeach; ?>
                </ul>
            </article>
        </section>
    </section>

    <section class="phinit-note phinit-note--info m365calc-source-card" aria-labelledby="m365archive-sources-title">
        <h2 id="m365archive-sources-title">Quellenstand</h2>
        <p>Die Berechnung ist eine lizenztechnische Vorprüfung. Für verbindliche Aussagen gelten Microsoft Product Terms, Service Descriptions und die konkrete Tenant-/Partner-Center-Verfügbarkeit.</p>
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
