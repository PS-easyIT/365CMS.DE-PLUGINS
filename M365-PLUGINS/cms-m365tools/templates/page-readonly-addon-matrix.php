<?php
/**
 * Public Template: ReadOnly Microsoft 365 Add-on-Matrix.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static function (mixed $value): string {
    if ($value === null || $value === '') {
        return '—';
    }

    return number_format((float) $value, 2, ',', '.') . ' €';
};
$cellHtml = static function (array $cell) use ($esc): string {
    $status = preg_replace('/[^a-z0-9_-]+/i', '', (string) ($cell['status'] ?? 'note')) ?: 'note';
    $html = '<span class="m365calc-status m365calc-status--' . $esc($status) . '">';
    $html .= '<strong>' . $esc($cell['label'] ?? '—') . '</strong>';
    if ((string) ($cell['note'] ?? '') !== '') {
        $html .= '<small>' . $esc($cell['note']) . '</small>';
    }
    $html .= '</span>';

    return $html;
};
$matrix = is_array($matrix ?? null) ? $matrix : CMS_M365CALCULATOR_ReadOnly_Matrices::addon_matrix();
$areas = is_array($matrix['areas'] ?? null) ? $matrix['areas'] : [];
$meta = is_array($matrix['meta'] ?? null) ? $matrix['meta'] : [];

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'Microsoft 365 Add-on-Matrix']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-comparison-page m365calc-readonly-page" id="m365-addon-matrix">
    <header class="m365calc-hero">
        <p class="phinit-overline">Add-on-Matrix</p>
        <section class="m365calc-hero__content" aria-labelledby="m365addonmatrix-title">
            <section>
                <h1 id="m365addonmatrix-title">Microsoft 365 Add-on-Matrix</h1>
                <p class="phinit-prose">Öffentliche Übersicht aller Add-on-Bereiche: Exchange, SharePoint, OneDrive, Teams Phone, Copilot, Security, Power Platform und Spezialdienste.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Lizenztools">
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenzmatrix">Vollpaket-Matrix öffnen</a>
                <a class="phinit-btn phinit-btn--secondary" href="/m365-add-on-konfigurator">Add-On-Konfigurator öffnen</a>
            </nav>
        </section>
    </header>

    <section class="phinit-result m365calc-result-card" id="m365calc-result" tabindex="-1" aria-labelledby="m365addonmatrix-result-title" data-m365calc-result>
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Matrix</p>
                <h2 id="m365addonmatrix-result-title">Gesamtübersicht der Microsoft-365-Add-ons</h2>
                <p>Die wichtigsten Add-ons mit Größen, Voraussetzungen, Abgrenzungen und typischen Kaufgründen.</p>
            </section>
            <section class="m365calc-actions">
                <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Drucken / PDF speichern</button>
                <a class="phinit-btn phinit-btn--primary" href="/kontakt">Lizenzcheck anfragen</a>
            </section>
        </header>
    </section>

    <?php foreach ($areas as $area): ?>
    <?php if (!is_array($area)) { continue; } ?>
    <?php $packages = is_array($area['packages'] ?? null) ? $area['packages'] : []; ?>
    <?php $rows = is_array($area['rows'] ?? null) ? $area['rows'] : []; ?>
    <section class="phinit-result m365calc-result-card m365calc-readonly-area" aria-labelledby="m365addonarea-<?php echo $esc($area['key'] ?? 'area'); ?>">
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Add-on-Bereich</p>
                <h2 id="m365addonarea-<?php echo $esc($area['key'] ?? 'area'); ?>"><?php echo $esc($area['label'] ?? 'Add-ons'); ?></h2>
                <p><?php echo $esc($area['description'] ?? ''); ?></p>
            </section>
        </header>

        <section class="m365calc-summary-grid m365calc-readonly-package-grid" aria-label="Pakete in <?php echo $esc($area['label'] ?? 'Add-ons'); ?>">
            <?php foreach ($packages as $package): ?>
            <?php if (!is_array($package)) { continue; } ?>
            <article class="phinit-card m365calc-mini-card">
                <span><?php echo $esc($package['billing'] ?? 'Add-on'); ?></span>
                <strong><?php echo $esc($package['short'] ?? $package['name'] ?? ''); ?></strong>
                <p><?php echo $money($package['price_month'] ?? null); ?> / Monat</p>
            </article>
            <?php endforeach; ?>
        </section>

        <section class="phinit-table-wrap m365calc-compare-wrap m365calc-readonly-wrap" aria-label="Add-on-Matrix <?php echo $esc($area['label'] ?? ''); ?>">
            <table class="phinit-table m365calc-compare-table m365calc-readonly-table">
                <thead>
                    <tr>
                        <th scope="col">Vergleichspunkt</th>
                        <?php foreach ($packages as $package): ?>
                        <?php if (!is_array($package)) { continue; } ?>
                        <th scope="col">
                            <span class="m365calc-plan-heading"><?php echo $esc($package['short'] ?? $package['name'] ?? ''); ?></span>
                            <span><?php echo $money($package['price_month'] ?? null); ?></span>
                            <?php if ((string) ($package['billing'] ?? '') !== ''): ?>
                            <small><?php echo $esc($package['billing']); ?></small>
                            <?php endif; ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                    <?php if (!is_array($row)) { continue; } ?>
                    <tr>
                        <th scope="row">
                            <span class="m365calc-feature-title"><?php echo $esc($row['label'] ?? ''); ?></span>
                            <?php if ((string) ($row['description'] ?? '') !== ''): ?>
                            <small><?php echo $esc($row['description']); ?></small>
                            <?php endif; ?>
                        </th>
                        <?php foreach ($packages as $package): ?>
                        <?php if (!is_array($package)) { continue; } ?>
                        <?php $slug = (string) ($package['slug'] ?? ''); ?>
                        <td data-label="<?php echo $esc($package['short'] ?? $package['name'] ?? ''); ?>"><?php echo $cellHtml(is_array($row['values'][$slug] ?? null) ? $row['values'][$slug] : []); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </section>
    <?php endforeach; ?>

    <section class="m365calc-result-grid" aria-label="Hinweise und Quellen">
        <article class="phinit-note phinit-note--warning">
            <h2>Hinweise zur Add-on-Übersicht</h2>
            <ul class="m365calc-note-list">
                <?php foreach (($matrix['notes'] ?? []) as $note): ?>
                <li><?php echo $esc($note); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
        <article class="phinit-note phinit-note--info m365calc-source-card">
            <h2>Quellenstand</h2>
            <p><?php echo $esc($meta['price_basis'] ?? 'Preis- und Lizenzinformationen vor Bestellung prüfen.'); ?></p>
            <details>
                <summary>Quellen anzeigen</summary>
                <ul class="m365calc-note-list">
                    <?php foreach (($matrix['sources'] ?? []) as $source): ?>
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
