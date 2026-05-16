<?php
/**
 * Public Template: ReadOnly Microsoft 365 Vollpaket-Matrix.
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
$matrix = is_array($matrix ?? null) ? $matrix : CMS_M365CALCULATOR_ReadOnly_Matrices::suite_matrix();
$columns = is_array($matrix['columns'] ?? null) ? $matrix['columns'] : [];
$groups = is_array($matrix['groups'] ?? null) ? $matrix['groups'] : [];
$meta = is_array($matrix['meta'] ?? null) ? $matrix['meta'] : [];
$rows = [];
foreach ($groups as $group) {
    if (!is_array($group) || empty($group['rows']) || !is_array($group['rows'])) {
        continue;
    }

    foreach ($group['rows'] as $row) {
        if (is_array($row)) {
            $rows[] = $row;
        }
    }
}

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'Microsoft 365 Lizenzmatrix']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-comparison-page m365calc-readonly-page" id="m365-license-matrix">
    <header class="m365calc-hero">
        <p class="phinit-overline">Lizenzmatrix</p>
        <section class="m365calc-hero__content" aria-labelledby="m365matrix-title">
            <section>
                <h1 id="m365matrix-title">Microsoft 365 Lizenzmatrix – Vollpakete</h1>
                <p class="phinit-prose">Öffentliche Gesamtübersicht der Microsoft-365-Vollpakete Business Basic, Business Standard, Business Premium, Microsoft 365 E3 und Microsoft 365 E5.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Lizenztools">
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenzvergleich">Interaktiven Lizenzvergleich öffnen</a>
                <a class="phinit-btn phinit-btn--secondary" href="/m365-addon-matrix">Add-on-Matrix öffnen</a>
            </nav>
        </section>
    </header>

    <section class="phinit-result m365calc-result-card" id="m365calc-result" tabindex="-1" aria-labelledby="m365matrix-result-title" data-m365calc-result>
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline">Matrix</p>
                <h2 id="m365matrix-result-title">Gesamtübersicht der Microsoft-365-Vollpakete</h2>
                <p>Alle zentralen Paket-, App-, Security-, Compliance-, KI- und Beschaffungspunkte in einer Übersicht.</p>
            </section>
            <section class="m365calc-actions">
                <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Drucken / PDF speichern</button>
                <a class="phinit-btn phinit-btn--primary" href="/kontakt">Lizenzcheck anfragen</a>
            </section>
        </header>

        <?php if ($rows !== []): ?>
        <section class="phinit-table-wrap m365calc-compare-wrap m365calc-readonly-wrap" aria-label="Microsoft 365 Vollpaket-Matrix">
            <table class="phinit-table m365calc-compare-table m365calc-readonly-table">
                <thead>
                    <tr>
                        <th scope="col">Bereich</th>
                        <?php foreach ($columns as $column): ?>
                        <?php if (!is_array($column)) { continue; } ?>
                        <th scope="col">
                            <span class="m365calc-plan-heading"><?php echo $esc($column['short'] ?? $column['name'] ?? ''); ?></span>
                            <span><?php echo $money($column['price_month'] ?? null); ?></span>
                            <?php if ((string) ($column['max_users'] ?? '') !== ''): ?>
                            <small>max. <?php echo $esc($column['max_users']); ?></small>
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
                        <?php foreach ($columns as $column): ?>
                        <?php if (!is_array($column)) { continue; } ?>
                        <?php $slug = (string) ($column['slug'] ?? ''); ?>
                        <td data-label="<?php echo $esc($column['short'] ?? $column['name'] ?? ''); ?>"><?php echo $cellHtml(is_array($row['values'][$slug] ?? null) ? $row['values'][$slug] : []); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <?php endif; ?>
    </section>

    <section class="m365calc-result-grid" aria-label="Hinweise und Quellen">
        <article class="phinit-note phinit-note--warning">
            <h2>Hinweise zur Lizenzmatrix</h2>
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
