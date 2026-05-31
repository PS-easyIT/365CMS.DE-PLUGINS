<?php
/**
 * Public Template: ReadOnly Microsoft Copilot Lizenzmatrix.
 *
 * @package CMS_M365MATRICES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$lang = function_exists('cms_plugin_public_language') ? cms_plugin_public_language() : 'de';
$t = static function (string $de, string $en, string $lang): string {
    return $lang === 'en' ? $en : $de;
};
$i18nOption = static function (array $options, string $key, string $defaultDe, string $defaultEn, string $lang): string {
    if (function_exists('cms_plugin_public_i18n_value')) {
        return trim((string) cms_plugin_public_i18n_value($options, $key, $lang, $lang === 'en' ? $defaultEn : $defaultDe));
    }

    return trim((string) ($options[$key] ?? ($lang === 'en' ? $defaultEn : $defaultDe)));
};
$money = static function (mixed $value): string {
    if ($value === null || $value === '') {
        return '—';
    }

    return number_format((float) $value, 2, ',', '.') . ' €';
};
$packagePrice = static function (array $package, bool $withPeriod = false) use ($esc, $money): string {
    $label = trim((string) ($package['price_label'] ?? ''));
    if ($label !== '') {
        return $esc($label);
    }

    $price = $money($package['price_month'] ?? null);
    if ($withPeriod && $price !== '—') {
        return $esc($price . ' / Monat');
    }

    return $esc($price);
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
        CMS_M365MATRICES_Settings::global_options('matrix-copilot')
    )
    : [];
if (class_exists('CMS_M365MATRICES_Settings')) {
    $moduleDesign = CMS_M365MATRICES_Settings::module_options('m365-copilot-matrix', 'design');
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
$matrixScopedValue = static function (string $scopedKey, string $globalKey, string $default) use ($matrixValue): string {
    $globalDefault = $matrixValue($globalKey, $default);

    return $matrixValue($scopedKey, $globalDefault);
};
$matrixScopedChoice = static function (string $scopedKey, string $globalKey, string $default, array $allowed) use ($matrixChoice, $matrixValue): string {
    $globalDefault = $matrixChoice($globalKey, $default, $allowed);
    $value = $matrixValue($scopedKey, $globalDefault);

    return in_array($value, $allowed, true) ? $value : $globalDefault;
};
$matrixScopedColor = static function (string $scopedKey, string $globalKey, string $default) use ($matrixColor, $matrixOptions): string {
    $globalDefault = $matrixColor($globalKey, $default);
    $value = (string) ($matrixOptions[$scopedKey] ?? $globalDefault);

    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $globalDefault;
};
$matrixScopedNumber = static function (string $scopedKey, string $globalKey, int $default, int $min, int $max) use ($matrixNumber, $matrixOptions): int {
    $globalDefault = $matrixNumber($globalKey, $default, $min, $max);
    $value = is_numeric($matrixOptions[$scopedKey] ?? null) ? (int) $matrixOptions[$scopedKey] : $globalDefault;

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
$matrix = is_array($matrix ?? null) ? $matrix : CMS_M365MATRICES_ReadOnly_Matrices::copilot_matrix();
$areas = is_array($matrix['areas'] ?? null) ? $matrix['areas'] : [];
$meta = is_array($matrix['meta'] ?? null) ? $matrix['meta'] : [];
$notes = is_array($matrix['notes'] ?? null) ? $matrix['notes'] : [];
$sources = is_array($matrix['sources'] ?? null) ? $matrix['sources'] : [];
$headerStyle = $matrixScopedChoice('matrix_copilot_header_style', 'matrix_header_style', 'plain', ['plain', 'surface', 'bordered', 'accent', 'inverted']);
$headerAlignment = $matrixScopedChoice('matrix_copilot_header_alignment', 'matrix_header_alignment', 'split', ['split', 'left', 'center']);
$buttonLayout = $matrixScopedChoice('matrix_copilot_button_layout', 'matrix_button_layout', 'inline', ['inline', 'stacked', 'right']);
$buttonStyle = $matrixScopedChoice('matrix_copilot_button_style', 'matrix_button_style', 'default', ['default', 'primary', 'secondary', 'minimal']);
$pageMaxWidth = $matrixScopedNumber('matrix_copilot_page_max_width', 'matrix_page_max_width', 1280, 760, 1800);
$outerPaddingX = $matrixScopedNumber('matrix_copilot_outer_padding_x', 'matrix_outer_padding_x', 0, 0, 96);
$outerPaddingTop = $matrixScopedNumber('matrix_copilot_outer_padding_top', 'matrix_outer_padding_top', 25, 0, 120);
$sectionGap = $matrixScopedNumber('matrix_copilot_section_gap', 'matrix_section_gap', 32, 12, 96);
$headerRadius = $matrixScopedNumber('matrix_copilot_header_radius', 'matrix_header_radius', 2, 0, 2);
$pageBackground = $matrixScopedColor('matrix_copilot_color_page_background', 'matrix_color_page_background', '#edf1f6');
$surfaceBackground = $matrixScopedColor('matrix_copilot_color_surface_background', 'matrix_color_surface_background', '#f8fafc');
$matrixText = $matrixScopedColor('matrix_copilot_color_text', 'matrix_color_text', '#1e293b');
$matrixMuted = $matrixScopedColor('matrix_copilot_color_muted', 'matrix_color_muted', '#64748b');
$headerBackground = $matrixScopedColor('matrix_copilot_color_header_background', 'matrix_color_header_background', '#f8fafc');
$headerText = $matrixScopedColor('matrix_copilot_color_header_text', 'matrix_color_header_text', '#1e293b');
$headerMuted = $matrixScopedColor('matrix_copilot_color_header_muted', 'matrix_color_header_muted', '#64748b');
$headerBorder = $matrixScopedColor('matrix_copilot_color_header_border', 'matrix_color_header_border', '#e2e8f0');
$primaryButtonBackground = $matrixScopedColor('matrix_copilot_color_primary_button_bg', 'matrix_color_primary_button_bg', '#2563eb');
$primaryButtonText = $matrixScopedColor('matrix_copilot_color_primary_button_text', 'matrix_color_primary_button_text', '#ffffff');
$secondaryButtonBackground = $matrixScopedColor('matrix_copilot_color_secondary_button_bg', 'matrix_color_secondary_button_bg', '#ffffff');
$secondaryButtonText = $matrixScopedColor('matrix_copilot_color_secondary_button_text', 'matrix_color_secondary_button_text', '#1e293b');
$showHero = $matrixEnabled('matrix_copilot_show_hero', $matrixValue('matrix_show_hero', '1'));
$showHeroButtons = $matrixEnabled('matrix_copilot_show_hero_buttons', $matrixValue('matrix_show_hero_buttons', '1'));
$showResultHeader = $matrixEnabled('matrix_copilot_show_result_header', $matrixValue('matrix_show_result_header', '1'));
$showPrintButton = $matrixEnabled('matrix_copilot_show_print_button', $matrixValue('matrix_show_print_button', '1'));
$showPrimaryCta = $matrixEnabled('matrix_copilot_show_primary_cta', $matrixValue('matrix_show_primary_cta', '1'));
$showAreaHeaders = $matrixEnabled('matrix_copilot_show_area_headers', $matrixValue('matrix_show_addon_area_headers', '1'));
$showPackageCards = $matrixEnabled('matrix_copilot_show_package_cards', $matrixValue('matrix_show_addon_package_cards', '1'));
$showNotes = $matrixEnabled('matrix_copilot_show_notes', $matrixValue('matrix_show_notes', '1'));
$showSources = $matrixEnabled('matrix_copilot_show_sources', $matrixValue('matrix_show_sources', '1'));
$heroTitle = $i18nOption($matrixOptions, 'matrix_copilot_title', 'Microsoft Copilot Lizenzmatrix', 'Microsoft Copilot License Matrix', $lang);
$heroOverline = $i18nOption($matrixOptions, 'matrix_copilot_overline', 'Copilot-Matrix', 'Copilot Matrix', $lang);
$heroIntro = $i18nOption($matrixOptions, 'matrix_copilot_intro', 'Umfangreiche Übersicht zu Microsoft Copilot, Microsoft 365 Copilot Chat, Microsoft 365 Copilot, Copilot Studio, Agents, App-Funktionen, Datenschutz und Nutzungskontingenten.', 'Comprehensive overview of Microsoft Copilot, Microsoft 365 Copilot Chat, Microsoft 365 Copilot, Copilot Studio, agents, app features, privacy, and usage quotas.', $lang);
$secondaryButtonLabel = $i18nOption($matrixOptions, 'matrix_copilot_secondary_button_label', 'Vollpaket-Matrix öffnen', 'Open full-suite matrix', $lang);
$secondaryButtonUrl = $safeUrl($matrixValue('matrix_copilot_secondary_button_url', function_exists('cms_plugin_public_localized_path') ? cms_plugin_public_localized_path('/m365-lizenzmatrix', $lang) : '/m365-lizenzmatrix'));
$toolButtonLabel = $i18nOption($matrixOptions, 'matrix_copilot_tool_button_label', 'Add-on-Matrix öffnen', 'Open add-on matrix', $lang);
$toolButtonUrl = $safeUrl($matrixValue('matrix_copilot_tool_button_url', function_exists('cms_plugin_public_localized_path') ? cms_plugin_public_localized_path('/m365-addon-matrix', $lang) : '/m365-addon-matrix'));
$resultOverline = $i18nOption($matrixOptions, 'matrix_copilot_result_overline', 'Matrix', 'Matrix', $lang);
$resultTitle = $i18nOption($matrixOptions, 'matrix_copilot_result_title', 'Gesamtübersicht der Copilot-Lizenzen und Agent-Optionen', 'Complete overview of Copilot licenses and agent options', $lang);
$resultIntro = $i18nOption($matrixOptions, 'matrix_copilot_result_intro', 'Vergleicht private Nutzung, Copilot Chat, Microsoft 365 Copilot Business, Microsoft 365 Copilot Enterprise sowie Copilot Studio für Teams und Standalone.', 'Compares private use, Copilot Chat, Microsoft 365 Copilot Business, Microsoft 365 Copilot Enterprise, and Copilot Studio for Teams and standalone.', $lang);
$areaOverline = $i18nOption($matrixOptions, 'matrix_copilot_area_overline', 'Copilot-Bereich', 'Copilot area', $lang);
$notesTitle = $i18nOption($matrixOptions, 'matrix_copilot_notes_title', 'Hinweise zur Copilot-Matrix', 'Notes on the Copilot matrix', $lang);
$sourcesTitle = $i18nOption($matrixOptions, 'matrix_copilot_sources_title', 'Quellenstand', 'Source status', $lang);
$printButtonLabel = $i18nOption($matrixOptions, 'matrix_copilot_print_button_label', 'Drucken / PDF speichern', 'Print / save as PDF', $lang);
$primaryButtonLabel = $i18nOption($matrixOptions, 'matrix_copilot_primary_button_label', 'Copilot-Lizenzcheck anfragen', 'Request Copilot license check', $lang);
$primaryButtonUrl = $safeUrl($matrixValue('matrix_copilot_primary_button_url', '/kontakt'));
$showToc = $matrixEnabled('matrix_copilot_show_toc', $matrixValue('matrix_toc_show', '1'));
$tocTitle = $i18nOption($matrixOptions, 'matrix_copilot_toc_title', 'Inhaltsverzeichnis', 'Table of contents', $lang);
$tocColumns = $matrixScopedChoice('matrix_copilot_toc_columns', 'matrix_toc_columns', '3', ['1', '2', '3']);
$tocFontSize = $matrixScopedNumber('matrix_copilot_toc_font_size', 'matrix_toc_font_size', 13, 11, 18);
$tocNoWrap = $matrixEnabled('matrix_copilot_toc_nowrap', $matrixValue('matrix_toc_nowrap', '1'));
$notesText = trim($matrixValue('matrix_copilot_notes_text', ''));
$sourcesIntro = $i18nOption($matrixOptions, 'matrix_copilot_sources_intro', (string) ($meta['price_basis'] ?? 'Preis- und Lizenzinformationen vor Bestellung prüfen.'), 'Verify pricing and licensing details before ordering.', $lang);
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
    $areaId = 'm365copilotarea-' . ($baseId !== '' ? $baseId : 'area-' . (int) $areaIndex);
    if (isset($usedAreaIds[$areaId])) {
        $areaId .= '-' . (int) $areaIndex;
    }

    $usedAreaIds[$areaId] = true;
    $areaNavIds[$areaIndex] = $areaId;
    $areaNavItems[] = [
        'id' => $areaId,
        'label' => (string) ($area['label'] ?? 'Copilot'),
    ];
}

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => $heroTitle]);
}
?>

<main class="phinit-plugin m365calc-page m365calc-comparison-page m365calc-readonly-page m365calc-matrix-page m365calc-matrix-header--<?php echo $esc($headerStyle); ?> m365calc-matrix-align--<?php echo $esc($headerAlignment); ?> m365calc-matrix-buttons--<?php echo $esc($buttonLayout); ?> m365calc-matrix-button-style--<?php echo $esc($buttonStyle); ?>" id="m365-copilot-matrix" style="--m365matrix-page-max-width: <?php echo (int) $pageMaxWidth; ?>px; --m365matrix-padding-x: <?php echo (int) $outerPaddingX; ?>px; --m365matrix-padding-top: <?php echo (int) $outerPaddingTop; ?>px; --m365matrix-section-gap: <?php echo (int) $sectionGap; ?>px; --m365matrix-page-bg: <?php echo $esc($pageBackground); ?>; --m365matrix-surface-bg: <?php echo $esc($surfaceBackground); ?>; --m365matrix-text: <?php echo $esc($matrixText); ?>; --m365matrix-muted: <?php echo $esc($matrixMuted); ?>; --m365matrix-header-bg: <?php echo $esc($headerBackground); ?>; --m365matrix-header-text: <?php echo $esc($headerText); ?>; --m365matrix-header-muted: <?php echo $esc($headerMuted); ?>; --m365matrix-header-border: <?php echo $esc($headerBorder); ?>; --m365matrix-header-radius: <?php echo (int) $headerRadius; ?>px; --m365matrix-primary-button-bg: <?php echo $esc($primaryButtonBackground); ?>; --m365matrix-primary-button-text: <?php echo $esc($primaryButtonText); ?>; --m365matrix-secondary-button-bg: <?php echo $esc($secondaryButtonBackground); ?>; --m365matrix-secondary-button-text: <?php echo $esc($secondaryButtonText); ?>; --m365matrix-toc-font-size: <?php echo (int) $tocFontSize; ?>px;">
    <?php if (!$showHero): ?>
    <h1 class="m365calc-visually-hidden"><?php echo $esc($heroTitle); ?></h1>
    <?php endif; ?>
    <?php if ($showHero): ?>
    <header class="m365calc-hero">
        <p class="phinit-overline"><?php echo $esc($heroOverline); ?></p>
        <section class="m365calc-hero__content" aria-labelledby="m365copilotmatrix-title">
            <section>
                <h1 id="m365copilotmatrix-title"><?php echo $esc($heroTitle); ?></h1>
                <p class="phinit-prose"><?php echo $esc($heroIntro); ?></p>
            </section>
            <?php if ($showHeroButtons && ($secondaryButtonUrl !== '' || $toolButtonUrl !== '')): ?>
            <nav class="m365calc-actions" aria-label="<?php echo $esc($t('Weitere Lizenztools', 'More license tools', $lang)); ?>">
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
    <nav class="phinit-result m365calc-result-card m365calc-addon-toc m365calc-addon-toc--cols-<?php echo $esc($tocColumns); ?><?php echo $tocNoWrap ? ' m365calc-addon-toc--nowrap' : ''; ?>" aria-labelledby="m365copilot-toc-title">
        <h2 id="m365copilot-toc-title"><?php echo $esc($tocTitle); ?></h2>
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
                <h2 id="m365copilotmatrix-result-title"><?php echo $esc($resultTitle); ?></h2>
                <p><?php echo $esc($resultIntro); ?></p>
            </section>
            <?php if ($showPrintButton): ?>
            <section class="m365calc-actions">
                <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print><?php echo $esc($printButtonLabel); ?></button>
            </section>
            <?php endif; ?>
        </header>
    </section>
    <?php else: ?>
    <h2 id="m365copilotmatrix-result-title" class="m365calc-visually-hidden"><?php echo $esc($resultTitle); ?></h2>
    <?php endif; ?>

    <?php foreach ($areas as $areaIndex => $area): ?>
    <?php if (!is_array($area)) { continue; } ?>
    <?php $packages = is_array($area['packages'] ?? null) ? $area['packages'] : []; ?>
    <?php $rows = is_array($area['rows'] ?? null) ? $area['rows'] : []; ?>
    <?php if ($packages === [] || $rows === []) { continue; } ?>
    <?php $areaId = (string) ($areaNavIds[$areaIndex] ?? ('m365copilotarea-' . (int) $areaIndex)); ?>
    <?php $showAreaContactCta = $areaIndex === 0 && $showPrimaryCta && $primaryButtonUrl !== ''; ?>
    <section class="phinit-result m365calc-result-card m365calc-readonly-area" id="<?php echo $esc($areaId); ?>" aria-labelledby="<?php echo $esc($areaId); ?>-title">
        <?php if ($showAreaHeaders): ?>
        <header class="m365calc-result-heading">
            <section>
                <p class="phinit-overline"><?php echo $esc($areaOverline); ?></p>
                <h2 id="<?php echo $esc($areaId); ?>-title"><?php echo $esc($area['label'] ?? 'Copilot'); ?></h2>
                <p><?php echo $esc($area['description'] ?? ''); ?></p>
            </section>
            <?php if ($showAreaContactCta): ?>
            <nav class="m365calc-package-actions" aria-label="<?php echo $esc($t('Kontakt zur Copilot-Lizenzberatung', 'Contact Copilot licensing advisory', $lang)); ?>">
                <a class="phinit-btn phinit-btn--secondary m365calc-package-check-action m365calc-contact-action" href="<?php echo $esc($primaryButtonUrl); ?>"><?php echo $esc($primaryButtonLabel); ?></a>
            </nav>
            <?php endif; ?>
        </header>
        <?php else: ?>
        <h2 id="<?php echo $esc($areaId); ?>-title" class="m365calc-visually-hidden"><?php echo $esc($area['label'] ?? 'Copilot'); ?></h2>
        <?php if ($showAreaContactCta): ?>
        <nav class="m365calc-package-actions m365calc-package-actions--standalone" aria-label="<?php echo $esc($t('Kontakt zur Copilot-Lizenzberatung', 'Contact Copilot licensing advisory', $lang)); ?>">
            <a class="phinit-btn phinit-btn--secondary m365calc-package-check-action m365calc-contact-action" href="<?php echo $esc($primaryButtonUrl); ?>"><?php echo $esc($primaryButtonLabel); ?></a>
        </nav>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($showPackageCards): ?>
        <section class="m365calc-summary-grid m365calc-readonly-package-grid" aria-label="<?php echo $esc($t('Copilot-Varianten in', 'Copilot variants in', $lang) . ' ' . (string) ($area['label'] ?? 'Copilot')); ?>">
            <?php foreach ($packages as $package): ?>
            <?php if (!is_array($package)) { continue; } ?>
            <article class="phinit-card m365calc-mini-card">
                <span><?php echo $esc($package['billing'] ?? 'Copilot'); ?></span>
                <strong><?php echo $esc($package['short'] ?? $package['name'] ?? ''); ?></strong>
                <p><?php echo $packagePrice($package, true); ?></p>
            </article>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>

        <section class="phinit-table-wrap m365calc-compare-wrap m365calc-readonly-wrap" aria-label="<?php echo $esc($t('Copilot-Matrix', 'Copilot matrix', $lang) . ' ' . (string) ($area['label'] ?? '')); ?>">
            <table class="phinit-table m365calc-compare-table m365calc-readonly-table">
                <thead>
                    <tr>
                        <th scope="col"><?php echo $esc($t('Vergleichspunkt', 'Comparison point', $lang)); ?></th>
                        <?php foreach ($packages as $package): ?>
                        <?php if (!is_array($package)) { continue; } ?>
                        <th scope="col">
                            <span class="m365calc-plan-heading"><?php echo $esc($package['short'] ?? $package['name'] ?? ''); ?></span>
                            <span><?php echo $packagePrice($package); ?></span>
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
    <section class="m365calc-result-grid" aria-label="<?php echo $esc($t('Hinweise und Quellen', 'Notes and sources', $lang)); ?>">
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
                <summary><?php echo $esc($t('Quellen anzeigen', 'Show sources', $lang)); ?></summary>
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
