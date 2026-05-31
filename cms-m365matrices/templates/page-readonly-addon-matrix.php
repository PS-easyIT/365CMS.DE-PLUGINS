<?php
/**
 * Public Template: ReadOnly Microsoft 365 Add-on-Matrix.
 *
 * @package CMS_M365MATRICES
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
$matrixOptions = class_exists('CMS_M365MATRICES_Settings')
    ? array_merge(
        CMS_M365MATRICES_Settings::global_options('matrix-design'),
        CMS_M365MATRICES_Settings::global_options('matrix-toc'),
        CMS_M365MATRICES_Settings::global_options('matrix-addon')
    )
    : [];
if (class_exists('CMS_M365MATRICES_Settings')) {
    $moduleDesign = CMS_M365MATRICES_Settings::module_options('m365-addon-matrix', 'design');
    if ((string) ($moduleDesign['design_override_enabled'] ?? '0') === '1') {
        $matrixOptions = array_merge($matrixOptions, [
            'matrix_header_radius' => (string) ($moduleDesign['design_card_radius'] ?? '2'),
            'matrix_color_header_background' => (string) ($moduleDesign['design_color_header_background'] ?? '#f8fafc'),
            'matrix_color_header_text' => (string) ($moduleDesign['design_color_header_text'] ?? '#1e293b'),
            'matrix_color_header_muted' => (string) ($moduleDesign['design_color_header_muted'] ?? '#64748b'),
            'matrix_color_header_border' => (string) ($moduleDesign['design_color_header_border'] ?? '#e2e8f0'),
            'matrix_color_primary_button_bg' => (string) ($moduleDesign['design_color_button_primary_bg'] ?? '#2563eb'),
            'matrix_color_primary_button_text' => (string) ($moduleDesign['design_color_button_primary_text'] ?? '#ffffff'),
            'matrix_color_secondary_button_bg' => (string) ($moduleDesign['design_color_button_secondary_bg'] ?? '#ffffff'),
            'matrix_color_secondary_button_text' => (string) ($moduleDesign['design_color_button_secondary_text'] ?? '#1e293b'),
        ]);
    }
}
$matrixValue = static fn(string $key, string $default): string => (string) ($matrixOptions[$key] ?? $default);
$matrixEnabled = static function (string $key, string $default = '1') use ($matrixOptions): bool {
    $value = array_key_exists($key, $matrixOptions) ? (string) $matrixOptions[$key] : $default;
    $value = $value !== '' ? $value : $default;

    return $value === '1';
};
$matrixChoice = static function (string $key, string $default, array $allowed) use ($matrixOptions): string {
    $value = (string) ($matrixOptions[$key] ?? $default);

    return in_array($value, $allowed, true) ? $value : $default;
};
$matrixColor = static function (string $key, string $default) use ($matrixOptions): string {
    $value = (string) ($matrixOptions[$key] ?? $default);

    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $default;
};
$matrixNumber = static function (string $key, int $default, int $min, int $max) use ($matrixOptions): int {
    $value = is_numeric($matrixOptions[$key] ?? null) ? (int) $matrixOptions[$key] : $default;

    return max($min, min($max, $value));
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
$matrix = is_array($matrix ?? null) ? $matrix : CMS_M365MATRICES_ReadOnly_Matrices::addon_matrix();
$areas = is_array($matrix['areas'] ?? null) ? $matrix['areas'] : [];
$meta = is_array($matrix['meta'] ?? null) ? $matrix['meta'] : [];
$notes = is_array($matrix['notes'] ?? null) ? $matrix['notes'] : [];
$sources = is_array($matrix['sources'] ?? null) ? $matrix['sources'] : [];
$headerStyle = $matrixChoice('matrix_header_style', 'plain', ['plain', 'surface', 'bordered', 'accent', 'inverted']);
$headerAlignment = $matrixChoice('matrix_header_alignment', 'split', ['split', 'left', 'center']);
$buttonLayout = $matrixChoice('matrix_button_layout', 'inline', ['inline', 'stacked', 'right']);
$buttonStyle = $matrixChoice('matrix_button_style', 'default', ['default', 'primary', 'secondary', 'minimal']);
$pageMaxWidth = $matrixNumber('matrix_page_max_width', 1200, 760, 1800);
$outerPaddingX = $matrixNumber('matrix_outer_padding_x', 0, 0, 96);
$outerPaddingTop = $matrixNumber('matrix_outer_padding_top', 25, 0, 120);
$sectionGap = $matrixNumber('matrix_section_gap', 32, 12, 96);
$headerRadius = max(0, min(2, (int) $matrixValue('matrix_header_radius', '2')));
$pageBackground = $matrixColor('matrix_color_page_background', '#edf1f6');
$surfaceBackground = $matrixColor('matrix_color_surface_background', '#f8fafc');
$matrixText = $matrixColor('matrix_color_text', '#1e293b');
$matrixMuted = $matrixColor('matrix_color_muted', '#64748b');
$headerBackground = $matrixColor('matrix_color_header_background', '#f8fafc');
$headerText = $matrixColor('matrix_color_header_text', '#1e293b');
$headerMuted = $matrixColor('matrix_color_header_muted', '#64748b');
$headerBorder = $matrixColor('matrix_color_header_border', '#e2e8f0');
$primaryButtonBackground = $matrixColor('matrix_color_primary_button_bg', '#2563eb');
$primaryButtonText = $matrixColor('matrix_color_primary_button_text', '#ffffff');
$secondaryButtonBackground = $matrixColor('matrix_color_secondary_button_bg', '#ffffff');
$secondaryButtonText = $matrixColor('matrix_color_secondary_button_text', '#1e293b');
$showHero = $matrixEnabled('matrix_addon_show_hero', $matrixValue('matrix_show_hero', '1'));
$showHeroButtons = $matrixEnabled('matrix_addon_show_hero_buttons', $matrixValue('matrix_show_hero_buttons', '1'));
$showResultHeader = $matrixEnabled('matrix_addon_show_result_header', $matrixValue('matrix_show_result_header', '1'));
$showPrintButton = $matrixEnabled('matrix_addon_show_print_button', $matrixValue('matrix_show_print_button', '1'));
$showPrimaryCta = $matrixEnabled('matrix_addon_show_primary_cta', $matrixValue('matrix_show_primary_cta', '1'));
$showAreaHeaders = $matrixEnabled('matrix_addon_show_area_headers', $matrixValue('matrix_show_addon_area_headers', '1'));
$showPackageCards = $matrixEnabled('matrix_addon_show_package_cards', $matrixValue('matrix_show_addon_package_cards', '1'));
$showNotes = $matrixEnabled('matrix_addon_show_notes', $matrixValue('matrix_show_notes', '1'));
$showSources = $matrixEnabled('matrix_addon_show_sources', $matrixValue('matrix_show_sources', '1'));
$heroTitle = $matrixValue('matrix_addon_title', 'Microsoft 365 Add-on-Matrix');
$heroOverline = $matrixValue('matrix_addon_overline', 'Add-on-Matrix');
$heroIntro = $matrixValue('matrix_addon_intro', 'Öffentliche Übersicht aller Add-on-Bereiche: Exchange, SharePoint, OneDrive, Teams Phone, Copilot, Security, Power Platform und Spezialdienste.');
$secondaryButtonLabel = $matrixValue('matrix_addon_secondary_button_label', 'Vollpaket-Matrix öffnen');
$secondaryButtonUrl = $safeUrl($matrixValue('matrix_addon_secondary_button_url', '/m365-lizenzmatrix'));
$toolButtonLabel = $matrixValue('matrix_addon_tool_button_label', 'Add-On-Konfigurator öffnen');
$toolButtonUrl = $safeUrl($matrixValue('matrix_addon_tool_button_url', '/m365-add-on-konfigurator'));
$resultOverline = $matrixValue('matrix_addon_result_overline', 'Matrix');
$resultTitle = $matrixValue('matrix_addon_result_title', 'Gesamtübersicht der Microsoft-365-Add-ons');
$resultIntro = $matrixValue('matrix_addon_result_intro', 'Die wichtigsten Add-ons mit Größen, Voraussetzungen, Abgrenzungen und typischen Kaufgründen.');
$areaOverline = $matrixValue('matrix_addon_area_overline', 'Add-on-Bereich');
$notesTitle = $matrixValue('matrix_addon_notes_title', 'Hinweise zur Add-on-Übersicht');
$sourcesTitle = $matrixValue('matrix_addon_sources_title', 'Quellenstand');
$notesText = trim($matrixValue('matrix_addon_notes_text', ''));
$sourcesIntro = $matrixValue('matrix_addon_sources_intro', (string) ($meta['price_basis'] ?? 'Preis- und Lizenzinformationen vor Bestellung prüfen.'));
$printButtonLabel = $matrixValue('matrix_print_button_label', 'Drucken / PDF speichern');
$primaryButtonLabel = $matrixValue('matrix_addon_primary_button_label', 'Lizenzcheck anfragen');
$primaryButtonUrl = $safeUrl($matrixValue('matrix_addon_primary_button_url', '/kontakt'));
$showToc = $matrixEnabled('matrix_toc_show', '1');
$tocTitle = $matrixValue('matrix_toc_title', 'Inhaltsverzeichnis');
$tocColumns = $matrixChoice('matrix_toc_columns', '3', ['1', '2', '3']);
$tocFontSize = $matrixNumber('matrix_toc_font_size', 13, 11, 18);
$tocNoWrap = $matrixEnabled('matrix_toc_nowrap', '1');
$areaNavItems = [];
$areaNavIds = [];
$usedAreaIds = [];
foreach ($areas as $areaIndex => $area) {
    if (!is_array($area)) {
        continue;
    }

    $packages = is_array($area['packages'] ?? null) ? $area['packages'] : [];
    $rows = is_array($area['rows'] ?? null) ? $area['rows'] : [];
    if ($packages === [] || $rows === []) {
        continue;
    }

    $baseId = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower((string) ($area['key'] ?? 'area-' . (int) $areaIndex))) ?: 'area-' . (int) $areaIndex;
    $baseId = trim($baseId, '-');
    $areaId = 'm365addonarea-' . ($baseId !== '' ? $baseId : 'area-' . (int) $areaIndex);
    if (isset($usedAreaIds[$areaId])) {
        $areaId .= '-' . (int) $areaIndex;
    }

    $usedAreaIds[$areaId] = true;
    $areaNavIds[$areaIndex] = $areaId;
    $areaNavItems[] = [
        'id' => $areaId,
        'label' => (string) ($area['label'] ?? 'Add-ons'),
    ];
}

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => $heroTitle]);
}
?>

<main class="phinit-plugin m365calc-page m365calc-comparison-page m365calc-readonly-page m365calc-matrix-page m365calc-matrix-header--<?php echo $esc($headerStyle); ?> m365calc-matrix-align--<?php echo $esc($headerAlignment); ?> m365calc-matrix-buttons--<?php echo $esc($buttonLayout); ?> m365calc-matrix-button-style--<?php echo $esc($buttonStyle); ?>" id="m365-addon-matrix" style="--m365matrix-page-max-width: <?php echo (int) $pageMaxWidth; ?>px; --m365matrix-padding-x: <?php echo (int) $outerPaddingX; ?>px; --m365matrix-padding-top: <?php echo (int) $outerPaddingTop; ?>px; --m365matrix-section-gap: <?php echo (int) $sectionGap; ?>px; --m365matrix-page-bg: <?php echo $esc($pageBackground); ?>; --m365matrix-surface-bg: <?php echo $esc($surfaceBackground); ?>; --m365matrix-text: <?php echo $esc($matrixText); ?>; --m365matrix-muted: <?php echo $esc($matrixMuted); ?>; --m365matrix-header-bg: <?php echo $esc($headerBackground); ?>; --m365matrix-header-text: <?php echo $esc($headerText); ?>; --m365matrix-header-muted: <?php echo $esc($headerMuted); ?>; --m365matrix-header-border: <?php echo $esc($headerBorder); ?>; --m365matrix-header-radius: <?php echo (int) $headerRadius; ?>px; --m365matrix-primary-button-bg: <?php echo $esc($primaryButtonBackground); ?>; --m365matrix-primary-button-text: <?php echo $esc($primaryButtonText); ?>; --m365matrix-secondary-button-bg: <?php echo $esc($secondaryButtonBackground); ?>; --m365matrix-secondary-button-text: <?php echo $esc($secondaryButtonText); ?>; --m365matrix-toc-font-size: <?php echo (int) $tocFontSize; ?>px;">
    <?php if (!$showHero): ?>
    <h1 class="m365calc-visually-hidden"><?php echo $esc($heroTitle); ?></h1>
    <?php endif; ?>
    <?php if ($showHero): ?>
    <header class="m365calc-hero">
        <p class="phinit-overline"><?php echo $esc($heroOverline); ?></p>
        <section class="m365calc-hero__content" aria-labelledby="m365addonmatrix-title">
            <section>
                <h1 id="m365addonmatrix-title"><?php echo $esc($heroTitle); ?></h1>
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

    <?php if ($showToc && $areaNavItems !== []): ?>
    <nav class="phinit-result m365calc-result-card m365calc-addon-toc m365calc-addon-toc--cols-<?php echo $esc($tocColumns); ?><?php echo $tocNoWrap ? ' m365calc-addon-toc--nowrap' : ''; ?>" aria-labelledby="m365addon-toc-title">
        <h2 id="m365addon-toc-title"><?php echo $esc($tocTitle); ?></h2>
        <div class="m365calc-addon-toc__links">
            <?php foreach ($areaNavItems as $item): ?>
            <a class="m365calc-addon-toc__link" href="#<?php echo $esc($item['id']); ?>">
                <span><?php echo $esc($item['label']); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </nav>
    <?php endif; ?>

    <?php if ($showResultHeader): ?>
    <section class="phinit-result m365calc-result-card" id="m365calc-result" tabindex="-1" aria-label="<?php echo $esc($resultTitle); ?>" data-m365calc-result>
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline"><?php echo $esc($resultOverline); ?></p>
                <h2 id="m365addonmatrix-result-title"><?php echo $esc($resultTitle); ?></h2>
                <p><?php echo $esc($resultIntro); ?></p>
            </section>
            <?php if ($showPrintButton || ($showPrimaryCta && $primaryButtonUrl !== '')): ?>
            <section class="m365calc-actions">
                <?php if ($showPrintButton): ?>
                <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print><?php echo $esc($printButtonLabel); ?></button>
                <?php endif; ?>
                <?php if ($showPrimaryCta && $primaryButtonUrl !== ''): ?>
                <a class="phinit-btn phinit-btn--primary m365calc-matrix-primary-action m365calc-contact-action" href="<?php echo $esc($primaryButtonUrl); ?>"><?php echo $esc($primaryButtonLabel); ?></a>
                <?php endif; ?>
            </section>
            <?php endif; ?>
        </header>
    </section>
    <?php else: ?>
    <h2 id="m365addonmatrix-result-title" class="m365calc-visually-hidden"><?php echo $esc($resultTitle); ?></h2>
    <?php endif; ?>

    <?php foreach ($areas as $areaIndex => $area): ?>
    <?php if (!is_array($area)) { continue; } ?>
    <?php $packages = is_array($area['packages'] ?? null) ? $area['packages'] : []; ?>
    <?php $rows = is_array($area['rows'] ?? null) ? $area['rows'] : []; ?>
    <?php if ($packages === [] || $rows === []) { continue; } ?>
    <?php $areaId = (string) ($areaNavIds[$areaIndex] ?? ('m365addonarea-' . (int) $areaIndex)); ?>
    <section class="phinit-result m365calc-result-card m365calc-readonly-area" id="<?php echo $esc($areaId); ?>" aria-labelledby="<?php echo $esc($areaId); ?>-title">
        <?php if ($showAreaHeaders): ?>
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline"><?php echo $esc($areaOverline); ?></p>
                <h2 id="<?php echo $esc($areaId); ?>-title"><?php echo $esc($area['label'] ?? 'Add-ons'); ?></h2>
                <p><?php echo $esc($area['description'] ?? ''); ?></p>
            </section>
        </header>
        <?php else: ?>
        <h2 id="<?php echo $esc($areaId); ?>-title" class="m365calc-visually-hidden"><?php echo $esc($area['label'] ?? 'Add-ons'); ?></h2>
        <?php endif; ?>

        <?php if ($showPackageCards): ?>
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
        <?php endif; ?>

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

    <?php if ($showNotes || $showSources): ?>
    <section class="m365calc-result-grid" aria-label="Hinweise und Quellen">
        <?php if ($showNotes): ?>
        <article class="phinit-note phinit-note--warning">
            <h2><?php echo $esc($notesTitle); ?></h2>
            <?php if ($notesText !== ''): ?>
            <p><?php echo $esc($notesText); ?></p>
            <?php endif; ?>
            <ul class="m365calc-note-list">
                <?php foreach ($notes as $note): ?>
                <li><?php echo $esc($note); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
        <?php endif; ?>
        <?php if ($showSources): ?>
        <article class="phinit-note phinit-note--info m365calc-source-card">
            <h2><?php echo $esc($sourcesTitle); ?></h2>
            <p><?php echo $esc($sourcesIntro); ?></p>
            <details>
                <summary>Quellen anzeigen</summary>
                <ul class="m365calc-note-list">
                    <?php foreach ($sources as $source): ?>
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
