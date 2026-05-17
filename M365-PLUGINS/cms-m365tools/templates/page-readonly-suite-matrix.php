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
$matrixOptions = class_exists('CMS_M365CALCULATOR_Settings')
    ? array_merge(
        CMS_M365CALCULATOR_Settings::global_options('matrix-design'),
        CMS_M365CALCULATOR_Settings::global_options('matrix-suite')
    )
    : [];
$matrixValue = static fn(string $key, string $default): string => (string) ($matrixOptions[$key] ?? $default);
$matrixEnabled = static fn(string $key, string $default = '1'): bool => (string) ($matrixOptions[$key] ?? $default) === '1';
$matrixChoice = static function (string $key, string $default, array $allowed) use ($matrixOptions): string {
    $value = (string) ($matrixOptions[$key] ?? $default);

    return in_array($value, $allowed, true) ? $value : $default;
};
$matrixColor = static function (string $key, string $default) use ($matrixOptions): string {
    $value = (string) ($matrixOptions[$key] ?? $default);

    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $default;
};
$safeUrl = static function (string $url): string {
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    if (str_starts_with($url, '/') && !str_starts_with($url, '//') && !str_contains($url, "\0")) {
        return $url;
    }

    $scheme = strtolower((string) (parse_url($url, PHP_URL_SCHEME) ?: ''));
    if (in_array($scheme, ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false) {
        return $url;
    }

    return '';
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

$headerStyle = $matrixChoice('matrix_header_style', 'plain', ['plain', 'surface', 'bordered', 'accent', 'inverted']);
$headerAlignment = $matrixChoice('matrix_header_alignment', 'split', ['split', 'left', 'center']);
$buttonLayout = $matrixChoice('matrix_button_layout', 'inline', ['inline', 'stacked', 'right']);
$buttonStyle = $matrixChoice('matrix_button_style', 'default', ['default', 'primary', 'secondary', 'minimal']);
$headerRadius = max(0, min(24, (int) $matrixValue('matrix_header_radius', '8')));
$headerBackground = $matrixColor('matrix_color_header_background', '#f8fafc');
$headerText = $matrixColor('matrix_color_header_text', '#1e293b');
$headerMuted = $matrixColor('matrix_color_header_muted', '#64748b');
$headerBorder = $matrixColor('matrix_color_header_border', '#e2e8f0');
$primaryButtonBackground = $matrixColor('matrix_color_primary_button_bg', '#2563eb');
$primaryButtonText = $matrixColor('matrix_color_primary_button_text', '#ffffff');
$secondaryButtonBackground = $matrixColor('matrix_color_secondary_button_bg', '#ffffff');
$secondaryButtonText = $matrixColor('matrix_color_secondary_button_text', '#1e293b');
$showHero = $matrixEnabled('matrix_suite_show_hero');
$showHeroButtons = $matrixEnabled('matrix_suite_show_hero_buttons');
$showResultHeader = $matrixEnabled('matrix_suite_show_result_header');
$showPrintButton = $matrixEnabled('matrix_suite_show_print_button');
$showPrimaryCta = $matrixEnabled('matrix_suite_show_primary_cta');
$showNotes = $matrixEnabled('matrix_suite_show_notes');
$showSources = $matrixEnabled('matrix_suite_show_sources');
$heroTitle = $matrixValue('matrix_suite_title', 'Microsoft 365 Lizenzmatrix – Vollpakete');
$heroOverline = $matrixValue('matrix_suite_overline', 'Lizenzmatrix');
$heroIntro = $matrixValue('matrix_suite_intro', 'Öffentliche Gesamtübersicht der Microsoft-365-Vollpakete Business Basic, Business Standard, Business Premium, Microsoft 365 E3 und Microsoft 365 E5.');
$secondaryButtonLabel = $matrixValue('matrix_suite_secondary_button_label', 'Interaktiven Lizenzvergleich öffnen');
$secondaryButtonUrl = $safeUrl($matrixValue('matrix_suite_secondary_button_url', '/m365-lizenzvergleich'));
$toolButtonLabel = $matrixValue('matrix_suite_tool_button_label', 'Add-on-Matrix öffnen');
$toolButtonUrl = $safeUrl($matrixValue('matrix_suite_tool_button_url', '/m365-addon-matrix'));
$resultOverline = $matrixValue('matrix_suite_result_overline', 'Matrix');
$resultTitle = $matrixValue('matrix_suite_result_title', 'Gesamtübersicht der Microsoft-365-Vollpakete');
$resultIntro = $matrixValue('matrix_suite_result_intro', 'Alle zentralen Paket-, App-, Security-, Compliance-, KI- und Beschaffungspunkte in einer Übersicht.');
$primaryButtonLabel = $matrixValue('matrix_suite_primary_button_label', 'Lizenzcheck anfragen');
$primaryButtonUrl = $safeUrl($matrixValue('matrix_suite_primary_button_url', '/kontakt'));

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'Microsoft 365 Lizenzmatrix']);
}
?>

<main class="phinit-plugin m365calc-page m365calc-comparison-page m365calc-readonly-page m365calc-matrix-page m365calc-matrix-header--<?php echo $esc($headerStyle); ?> m365calc-matrix-align--<?php echo $esc($headerAlignment); ?> m365calc-matrix-buttons--<?php echo $esc($buttonLayout); ?> m365calc-matrix-button-style--<?php echo $esc($buttonStyle); ?>" id="m365-license-matrix" style="--m365matrix-header-bg: <?php echo $esc($headerBackground); ?>; --m365matrix-header-text: <?php echo $esc($headerText); ?>; --m365matrix-header-muted: <?php echo $esc($headerMuted); ?>; --m365matrix-header-border: <?php echo $esc($headerBorder); ?>; --m365matrix-header-radius: <?php echo (int) $headerRadius; ?>px; --m365matrix-primary-button-bg: <?php echo $esc($primaryButtonBackground); ?>; --m365matrix-primary-button-text: <?php echo $esc($primaryButtonText); ?>; --m365matrix-secondary-button-bg: <?php echo $esc($secondaryButtonBackground); ?>; --m365matrix-secondary-button-text: <?php echo $esc($secondaryButtonText); ?>;">
    <?php if (!$showHero): ?>
    <h1 class="m365calc-visually-hidden"><?php echo $esc($heroTitle); ?></h1>
    <?php endif; ?>
    <?php if ($showHero): ?>
    <header class="m365calc-hero">
        <p class="phinit-overline"><?php echo $esc($heroOverline); ?></p>
        <section class="m365calc-hero__content" aria-labelledby="m365matrix-title">
            <section>
                <h1 id="m365matrix-title"><?php echo $esc($heroTitle); ?></h1>
                <p class="phinit-prose"><?php echo $esc($heroIntro); ?></p>
            </section>
            <?php if ($showHeroButtons && ($secondaryButtonUrl !== '' || $toolButtonUrl !== '')): ?>
            <nav class="m365calc-actions" aria-label="Weitere Lizenztools">
                <?php if ($secondaryButtonUrl !== ''): ?>
                <a class="phinit-btn phinit-btn--secondary m365calc-matrix-action" href="<?php echo $esc($secondaryButtonUrl); ?>"><?php echo $esc($secondaryButtonLabel); ?></a>
                <?php endif; ?>
                <?php if ($toolButtonUrl !== ''): ?>
                <a class="phinit-btn phinit-btn--secondary m365calc-matrix-action" href="<?php echo $esc($toolButtonUrl); ?>"><?php echo $esc($toolButtonLabel); ?></a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        </section>
    </header>
    <?php endif; ?>

    <section class="phinit-result m365calc-result-card" id="m365calc-result" tabindex="-1" aria-label="<?php echo $esc($resultTitle); ?>" data-m365calc-result>
        <?php if ($showResultHeader): ?>
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline"><?php echo $esc($resultOverline); ?></p>
                <h2 id="m365matrix-result-title"><?php echo $esc($resultTitle); ?></h2>
                <p><?php echo $esc($resultIntro); ?></p>
            </section>
            <?php if ($showPrintButton || ($showPrimaryCta && $primaryButtonUrl !== '')): ?>
            <section class="m365calc-actions">
                <?php if ($showPrintButton): ?>
                <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Drucken / PDF speichern</button>
                <?php endif; ?>
                <?php if ($showPrimaryCta && $primaryButtonUrl !== ''): ?>
                <a class="phinit-btn phinit-btn--primary m365calc-matrix-primary-action" href="<?php echo $esc($primaryButtonUrl); ?>"><?php echo $esc($primaryButtonLabel); ?></a>
                <?php endif; ?>
            </section>
            <?php endif; ?>
        </header>
        <?php else: ?>
        <h2 id="m365matrix-result-title" class="m365calc-visually-hidden"><?php echo $esc($resultTitle); ?></h2>
        <?php endif; ?>

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

    <?php if ($showNotes || $showSources): ?>
    <section class="m365calc-result-grid" aria-label="Hinweise und Quellen">
        <?php if ($showNotes): ?>
        <article class="phinit-note phinit-note--warning">
            <h2>Hinweise zur Lizenzmatrix</h2>
            <ul class="m365calc-note-list">
                <?php foreach (($matrix['notes'] ?? []) as $note): ?>
                <li><?php echo $esc($note); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
        <?php endif; ?>
        <?php if ($showSources): ?>
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
        <?php endif; ?>
    </section>
    <?php endif; ?>
</main>

<?php
if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getFooter();
}
