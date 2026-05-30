<?php
/**
 * Public Template: Storage-Bedarfs-Rechner.
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
    if ($number >= 1024) {
        return number_format($number / 1024, 2, ',', '.') . ' TB';
    }

    return number_format($number, 1, ',', '.') . ' GB';
};
$money = static function (mixed $value, string $currency = 'EUR'): string {
    $amount = number_format((float) $value, 2, ',', '.');
    return $currency === 'EUR' ? $amount . ' €' : $amount . ' ' . $currency;
};
$percent = static fn(mixed $value): string => number_format((float) $value * 100, 1, ',', '.') . ' %';
$isSelected = static fn(string $left, string $right): string => $left === $right ? ' selected' : '';
$input = is_array($input ?? null) ? $input : CMS_M365CALCULATOR_Storage_Needs_Calculator::default_input();
$result = is_array($result ?? null) ? $result : CMS_M365CALCULATOR_Storage_Needs_Calculator::evaluate($input);
$requirements = is_array($result['requirements'] ?? null) ? $result['requirements'] : [];
$recommendation = is_array($result['recommendation'] ?? null) ? $result['recommendation'] : [];
$costs = is_array($result['costs'] ?? null) ? $result['costs'] : [];
$tone = (string) ($recommendation['tone'] ?? 'info');
$tone = in_array($tone, ['success', 'warning', 'danger', 'info'], true) ? $tone : 'info';
$score = max(0, min(100, (int) ($recommendation['score'] ?? 0)));

if (class_exists('CMS\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'M365 Storage-Bedarfs-Rechner']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-storage-page" id="m365-storage-bedarfsrechner">
    <header class="m365calc-hero">
        <p class="phinit-overline">Storage-Bedarfsplanung</p>
        <section class="m365calc-hero__content" aria-labelledby="m365storage-title">
            <section>
                <h1 id="m365storage-title">M365 Storage-Bedarfs-Rechner</h1>
                <p class="phinit-prose">Berechnet SharePoint-Pool, OneDrive-Quotas, Exchange-Postfächer und Archivbedarf getrennt – inklusive Wachstum, Puffer, Cleanup-Potenzial und operativen Microsoft-Grenzen.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Speicher- und Lizenztools">
                <a class="phinit-btn phinit-btn--secondary" href="/m365-backup-kostenrechner">Backup-Kosten</a>
                <a class="phinit-btn phinit-btn--secondary" href="/m365-archive-mailbox-rechner">Archiv prüfen</a>
            </nav>
        </section>
    </header>

    <section class="m365calc-layout m365calc-layout--storage">
        <section class="phinit-card" aria-labelledby="m365storage-form-title">
            <header class="m365calc-section__head">
                <section>
                    <h2 id="m365storage-form-title">Storage-Szenario eingeben</h2>
                    <p>Trage aktuelle Datenmengen, Nutzerzahlen, Quotas und Wachstum ein. Die Auswertung trennt Dateispeicher, Benutzerdateien, Mailboxen und Archiv.</p>
                </section>
            </header>

            <form method="GET" class="m365calc-form m365calc-storage-form">
                <fieldset class="m365calc-fieldset">
                    <legend>Mandant und SharePoint</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365storage-qualified-licenses">
                            Qualifizierte Lizenzen
                            <input class="phinit-input" type="number" id="m365storage-qualified-licenses" name="qualified_licenses" min="1" max="500000" value="<?php echo (int) ($input['qualified_licenses'] ?? 1); ?>">
                        </label>
                        <label class="phinit-field" for="m365storage-users">
                            Nutzer gesamt
                            <input class="phinit-input" type="number" id="m365storage-users" name="users" min="1" max="500000" value="<?php echo (int) ($input['users'] ?? 1); ?>">
                        </label>
                        <label class="phinit-field" for="m365storage-sharepoint-current">
                            Aktueller SharePoint-Verbrauch in GB
                            <input class="phinit-input" type="number" id="m365storage-sharepoint-current" name="sharepoint_current_gb" min="0" max="50000000" step="0.1" value="<?php echo $esc($input['sharepoint_current_gb'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365storage-largest-site">
                            Größte Site in GB
                            <input class="phinit-input" type="number" id="m365storage-largest-site" name="sharepoint_largest_site_gb" min="0" max="50000000" step="0.1" value="<?php echo $esc($input['sharepoint_largest_site_gb'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365storage-sites">
                            Anzahl Sites
                            <input class="phinit-input" type="number" id="m365storage-sites" name="sharepoint_sites" min="0" max="2000000" value="<?php echo (int) ($input['sharepoint_sites'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365storage-extra-price">
                            Zusatzspeicherpreis je GB / Monat
                            <input class="phinit-input" type="number" id="m365storage-extra-price" name="extra_storage_price_month" min="0" max="1000" step="0.01" value="<?php echo $esc($input['extra_storage_price_month'] ?? 0); ?>">
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>OneDrive und Sync</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365storage-onedrive-users">
                            OneDrive-Nutzer
                            <input class="phinit-input" type="number" id="m365storage-onedrive-users" name="onedrive_users" min="1" max="500000" value="<?php echo (int) ($input['onedrive_users'] ?? 1); ?>">
                        </label>
                        <label class="phinit-field" for="m365storage-onedrive-avg">
                            Ø OneDrive-Bedarf je Nutzer in GB
                            <input class="phinit-input" type="number" id="m365storage-onedrive-avg" name="onedrive_avg_gb" min="0" max="50000000" step="0.1" value="<?php echo $esc($input['onedrive_avg_gb'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365storage-onedrive-quota">
                            OneDrive-Quota je Nutzer in GB
                            <input class="phinit-input" type="number" id="m365storage-onedrive-quota" name="onedrive_quota_gb" min="1" max="50000000" step="0.1" value="<?php echo $esc($input['onedrive_quota_gb'] ?? 1024); ?>">
                        </label>
                        <label class="phinit-field" for="m365storage-synced-items">
                            Synchronisierte Elemente
                            <input class="phinit-input" type="number" id="m365storage-synced-items" name="synced_items" min="0" max="50000000" value="<?php echo (int) ($input['synced_items'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365storage-largest-file">
                            Größte Einzeldatei in GB
                            <input class="phinit-input" type="number" id="m365storage-largest-file" name="largest_file_gb" min="0" max="50000000" step="0.1" value="<?php echo $esc($input['largest_file_gb'] ?? 0); ?>">
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Exchange und Archiv</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365storage-mailboxes">
                            Anzahl Mailboxen
                            <input class="phinit-input" type="number" id="m365storage-mailboxes" name="mailboxes" min="1" max="500000" value="<?php echo (int) ($input['mailboxes'] ?? 1); ?>">
                        </label>
                        <label class="phinit-field" for="m365storage-avg-mailbox">
                            Ø Mailboxgröße in GB
                            <input class="phinit-input" type="number" id="m365storage-avg-mailbox" name="avg_mailbox_gb" min="0" max="50000000" step="0.1" value="<?php echo $esc($input['avg_mailbox_gb'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365storage-largest-mailbox">
                            Größte Mailbox in GB
                            <input class="phinit-input" type="number" id="m365storage-largest-mailbox" name="largest_mailbox_gb" min="0" max="50000000" step="0.1" value="<?php echo $esc($input['largest_mailbox_gb'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365storage-mailbox-plan">
                            Primärpostfachgröße
                            <select class="phinit-select" id="m365storage-mailbox-plan" name="mailbox_plan_limit_gb">
                                <?php foreach (($result['mailbox_plan_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['mailbox_plan_limit_gb'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365storage-archive-users">
                            Archivierte Mailboxen in Prozent
                            <input class="phinit-input" type="number" id="m365storage-archive-users" name="archive_users_percent" min="0" max="100" value="<?php echo (int) ($input['archive_users_percent'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365storage-avg-archive">
                            Ø Archivgröße in GB
                            <input class="phinit-input" type="number" id="m365storage-avg-archive" name="avg_archive_gb" min="0" max="50000000" step="0.1" value="<?php echo $esc($input['avg_archive_gb'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365storage-archive-model">
                            Archivmodell
                            <select class="phinit-select" id="m365storage-archive-model" name="archive_model">
                                <?php foreach (($result['archive_model_options'] ?? []) as $key => $label): ?>
                                <option value="<?php echo $esc($key); ?>"<?php echo $isSelected((string) ($input['archive_model'] ?? ''), (string) $key); ?>><?php echo $esc($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Wachstum und Puffer</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--2">
                        <label class="phinit-field" for="m365storage-growth">
                            Jährliches Wachstum in Prozent
                            <input class="phinit-input" type="number" id="m365storage-growth" name="annual_growth_percent" min="0" max="500" step="0.1" value="<?php echo $esc($input['annual_growth_percent'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365storage-horizon">
                            Betrachtungszeitraum
                            <select class="phinit-select" id="m365storage-horizon" name="planning_months">
                                <?php foreach (($result['planning_month_options'] ?? [12, 24, 36]) as $months): ?>
                                <option value="<?php echo (int) $months; ?>"<?php echo $isSelected((string) ($input['planning_months'] ?? 12), (string) $months); ?>><?php echo (int) $months; ?> Monate</option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="phinit-field" for="m365storage-buffer">
                            Planungspuffer in Prozent
                            <input class="phinit-input" type="number" id="m365storage-buffer" name="buffer_percent" min="0" max="200" step="0.1" value="<?php echo $esc($input['buffer_percent'] ?? 0); ?>">
                        </label>
                        <label class="phinit-field" for="m365storage-cleanup">
                            Cleanup-Potenzial in Prozent
                            <input class="phinit-input" type="number" id="m365storage-cleanup" name="cleanup_potential_percent" min="0" max="90" step="0.1" value="<?php echo $esc($input['cleanup_potential_percent'] ?? 0); ?>">
                        </label>
                    </section>
                </fieldset>

                <section class="m365calc-actions">
                    <button type="submit" class="phinit-btn phinit-btn--primary">Storage-Bedarf berechnen</button>
                    <a class="phinit-btn phinit-btn--secondary" href="/m365-storage-bedarfsrechner">Zurücksetzen</a>
                </section>
            </form>
        </section>

        <aside class="m365calc-aside" aria-labelledby="m365storage-result-title">
            <article class="phinit-note phinit-note--<?php echo $esc($tone); ?>" id="m365calc-result" tabindex="-1" data-m365calc-result>
                <p class="phinit-overline">Kapazitätsstatus</p>
                <h2 id="m365storage-result-title"><?php echo $esc($recommendation['label'] ?? 'Storage-Bedarf prüfen'); ?></h2>
                <p><?php echo $esc($recommendation['summary'] ?? 'SharePoint, OneDrive, Exchange und Archiv wurden bewertet.'); ?></p>
                <section class="m365calc-score" aria-label="Storage Score">
                    <span>Planungsscore</span>
                    <strong><?php echo (int) $score; ?>%</strong>
                    <div class="m365calc-score__bar"><span style="--m365calc-score-width: <?php echo (int) $score; ?>%;"></span></div>
                </section>
            </article>

            <article class="phinit-result m365calc-result-card">
                <header>
                    <p class="phinit-overline">SharePoint-Zusatzspeicher</p>
                    <h2><?php echo $money($costs['monthly'] ?? 0, (string) ($costs['currency'] ?? 'EUR')); ?>/Monat</h2>
                    <p><?php echo $fmtGb($costs['needed_extra_gb'] ?? 0); ?> zusätzlich im Modell · <?php echo $money($costs['unit_price_month'] ?? 0, (string) ($costs['currency'] ?? 'EUR')); ?>/GB</p>
                </header>
                <section class="m365calc-price-stack" aria-label="Storage Kostenannahme">
                    <article><span>Pro Jahr</span><strong><?php echo $money($costs['yearly'] ?? 0, (string) ($costs['currency'] ?? 'EUR')); ?></strong></article>
                    <article><span>Schrittgröße</span><strong><?php echo $fmtGb($costs['increment_gb'] ?? 1); ?></strong></article>
                    <article><span>Quellenstand</span><strong><?php echo $esc($result['meta']['source_checked'] ?? '2026-05-17'); ?></strong></article>
                </section>
                <footer class="m365calc-actions">
                    <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Drucken / PDF speichern</button>
                </footer>
            </article>
        </aside>
    </section>

    <section class="m365calc-summary-grid" aria-label="Storage Kennzahlen">
        <?php foreach (($result['areas'] ?? []) as $area): ?>
        <?php if (!is_array($area)) { continue; } ?>
        <?php $bar = max(0, min(100, (int) round((float) ($area['ratio'] ?? 0) * 100))); ?>
        <article class="phinit-card m365calc-mini-card phinit-card--<?php echo $esc($area['tone'] ?? 'accent'); ?>">
            <span><?php echo $esc($area['label'] ?? 'Bereich'); ?></span>
            <strong><?php echo $fmtGb($area['forecast_gb'] ?? 0); ?></strong>
            <p>Kapazität: <?php echo $fmtGb($area['capacity_gb'] ?? 0); ?> · Überhang: <?php echo $fmtGb($area['overage_gb'] ?? 0); ?></p>
            <section class="m365calc-score" aria-label="Auslastung <?php echo $esc($area['label'] ?? 'Bereich'); ?>">
                <span><?php echo $percent($area['ratio'] ?? 0); ?></span>
                <div class="m365calc-score__bar"><span style="--m365calc-score-width: <?php echo (int) $bar; ?>%;"></span></div>
            </section>
        </article>
        <?php endforeach; ?>
    </section>

    <section class="m365calc-result-grid" aria-label="Empfehlungen und Leitplanken">
        <article class="phinit-result m365calc-result-card">
            <header class="m365calc-result-heading">
                <section>
                    <p class="phinit-overline">Nächste Schritte</p>
                    <h2>Maßnahmen priorisieren</h2>
                    <p>Die Reihenfolge trennt Sofortmaßnahmen, Plananpassungen und Betriebsoptimierung.</p>
                </section>
            </header>
            <ul class="m365calc-note-list">
                <?php foreach (($result['next_steps'] ?? []) as $step): ?>
                <li><?php echo $esc($step); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>

        <article class="phinit-note phinit-note--warning">
            <h2>Auffälligkeiten</h2>
            <?php if (empty($result['warnings'])): ?>
            <p>Keine kritischen Punkte im gewählten Modell.</p>
            <?php else: ?>
            <ul class="m365calc-note-list">
                <?php foreach (($result['warnings'] ?? []) as $warning): ?>
                <li><?php echo $esc($warning); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </article>
    </section>

    <section class="phinit-result m365calc-result-card" aria-labelledby="m365storage-limits-title">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Microsoft-Leitplanken</p>
                <h2 id="m365storage-limits-title">Grenzen, die in der Planung zählen</h2>
                <p>Die Tabelle zeigt die wichtigsten Kapazitäts- und Betriebswerte aus den gepflegten Microsoft-Quellen.</p>
            </section>
        </header>
        <section class="phinit-table-wrap m365calc-addon-table-wrap" aria-label="Storage Leitplanken Tabelle">
            <table class="phinit-table m365calc-addon-table">
                <thead>
                    <tr>
                        <th scope="col">Bereich</th>
                        <th scope="col">Wert</th>
                        <th scope="col">Planungsnotiz</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($result['limits'] ?? []) as $limit): ?>
                    <?php if (!is_array($limit)) { continue; } ?>
                    <tr>
                        <th scope="row"><?php echo $esc($limit['label'] ?? ''); ?></th>
                        <td><?php echo $esc($limit['value'] ?? ''); ?></td>
                        <td><?php echo $esc($limit['note'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </section>

    <section class="m365calc-result-grid" aria-label="Detailwerte">
        <article class="phinit-card m365calc-plan-card">
            <header>
                <p class="phinit-overline">SharePoint</p>
                <h2>Tenant-Pool und größte Site</h2>
            </header>
            <dl class="m365calc-kpi-list">
                <div><dt>Inklusive Kapazität</dt><dd><?php echo $fmtGb($requirements['sharepoint']['included_gb'] ?? 0); ?></dd></div>
                <div><dt>Forecast</dt><dd><?php echo $fmtGb($requirements['sharepoint']['forecast_gb'] ?? 0); ?></dd></div>
                <div><dt>Nach Cleanup</dt><dd><?php echo $fmtGb($requirements['sharepoint']['after_cleanup_gb'] ?? 0); ?></dd></div>
                <div><dt>Größte Site Forecast</dt><dd><?php echo $fmtGb($requirements['sharepoint']['largest_site_forecast_gb'] ?? 0); ?></dd></div>
            </dl>
        </article>
        <article class="phinit-card m365calc-plan-card">
            <header>
                <p class="phinit-overline">OneDrive</p>
                <h2>Benutzerspeicher und Quota</h2>
            </header>
            <dl class="m365calc-kpi-list">
                <div><dt>Nutzer</dt><dd><?php echo (int) ($requirements['onedrive']['users'] ?? 0); ?></dd></div>
                <div><dt>Ø Forecast je Nutzer</dt><dd><?php echo $fmtGb($requirements['onedrive']['forecast_avg_gb'] ?? 0); ?></dd></div>
                <div><dt>Gesamt Forecast</dt><dd><?php echo $fmtGb($requirements['onedrive']['forecast_total_gb'] ?? 0); ?></dd></div>
                <div><dt>Quota gesamt</dt><dd><?php echo $fmtGb($requirements['onedrive']['quota_total_gb'] ?? 0); ?></dd></div>
            </dl>
        </article>
        <article class="phinit-card m365calc-plan-card">
            <header>
                <p class="phinit-overline">Exchange</p>
                <h2>Mailbox und Archiv</h2>
            </header>
            <dl class="m365calc-kpi-list">
                <div><dt>Postfach Forecast</dt><dd><?php echo $fmtGb($requirements['exchange']['forecast_total_gb'] ?? 0); ?></dd></div>
                <div><dt>Primärkapazität</dt><dd><?php echo $fmtGb($requirements['exchange']['capacity_gb'] ?? 0); ?></dd></div>
                <div><dt>Archiv Forecast</dt><dd><?php echo $fmtGb($requirements['archive']['forecast_total_gb'] ?? 0); ?></dd></div>
                <div><dt>Archivkapazität</dt><dd><?php echo $fmtGb($requirements['archive']['capacity_gb'] ?? 0); ?></dd></div>
            </dl>
        </article>
    </section>

    <section class="phinit-note phinit-note--info m365calc-source-card" aria-labelledby="m365storage-sources-title">
        <h2 id="m365storage-sources-title">Quellenstand</h2>
        <p><?php echo $esc($result['meta']['price_basis'] ?? ''); ?></p>
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
