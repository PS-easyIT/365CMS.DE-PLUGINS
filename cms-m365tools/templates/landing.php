<?php
/**
 * Public Template: M365 Tools Landingpage.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$lang = function_exists('cms_plugin_public_language') ? cms_plugin_public_language() : 'de';
$t = static fn(string $de, string $en): string => $lang === 'en' ? $en : $de;
$path = static fn(string $route): string => function_exists('cms_plugin_public_localized_path')
    ? cms_plugin_public_localized_path($route, $lang)
    : ($lang === 'en' ? '/en/' . ltrim($route, '/') : '/' . ltrim($route, '/'));
$categoryId = static function (string $category): string {
    $normalized = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $category), '-'));
    return $normalized !== '' ? 'cat-' . $normalized : 'cat-tools';
};
$categoryMeta = static function (string $category): array {
    $key = strtolower(trim($category));

    return match ($key) {
        'lizenzen' => ['icon' => 'license', 'description' => 'Lizenzmodelle, Add-ons, Audits und Kostenpfade schnell einordnen.'],
        'copilot' => ['icon' => 'copilot', 'description' => 'Copilot-Eignung, Pilotierung, ROI und KI-Angebote strukturiert bewerten.'],
        'exchange' => ['icon' => 'mailbox', 'description' => 'Mailboxen, Archivierung und Exchange-Modernisierung sauber planen.'],
        'teams' => ['icon' => 'phone', 'description' => 'Telefonie, PSTN-Modelle und Teams-Phone-Optionen vergleichen.'],
        'speicher' => ['icon' => 'storage', 'description' => 'SharePoint, OneDrive, Exchange und Backup-Speicherbedarf greifbar machen.'],
        'power platform' => ['icon' => 'addons', 'description' => 'Power Apps, Automate, Dataverse, Credits und Governance realistisch kalkulieren.'],
        'migration' => ['icon' => 'roi', 'description' => 'Migrations- und TCO-Szenarien mit Kosten, Aufwand und Break-even bewerten.'],
        default => ['icon' => 'calculator', 'description' => 'Weitere Microsoft-365-Werkzeuge für konkrete Betriebs- und Planungsfragen.'],
    };
};
$safeUrl = static function (mixed $value): string {
    $url = trim((string) $value);
    if ($url === '') {
        return '';
    }

    if (preg_match('/^#[A-Za-z][A-Za-z0-9_-]*$/', $url) === 1) {
        return $url;
    }

    if (str_starts_with($url, '/') && !str_starts_with($url, '//') && !str_contains($url, "\0")) {
        return $url;
    }

    $parts = parse_url($url);
    $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';

    return in_array($scheme, ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : '';
};
$statusLabel = static fn(string $status): string => match ($status) {
    'beta' => 'Beta',
    'soon' => $lang === 'en' ? 'soon' : 'bald',
    default => '',
};
$reviewLabels = static function (array $domainKeys, array $domains): array {
    $labels = [];
    foreach ($domainKeys as $domainKey) {
        $domainKey = (string) $domainKey;
        if ($domainKey === '' || !isset($domains[$domainKey]) || !is_array($domains[$domainKey])) {
            continue;
        }
        $label = trim((string) ($domains[$domainKey]['label'] ?? ''));
        if ($label !== '') {
            $labels[] = $label;
        }
    }

    return array_values(array_unique($labels));
};
$reviewChecks = static function (string $toolKey, array $checkMap): array {
    $checks = isset($checkMap[$toolKey]) && is_array($checkMap[$toolKey]) ? $checkMap[$toolKey] : [];

    return array_values(array_filter(array_map(static fn(mixed $value): string => trim((string) $value), $checks)));
};
$reviewIconClass = static function (array $domain): string {
    $label = strtolower(trim((string) ($domain['label'] ?? '')));

    return match ($label) {
        'lizenz & kosten' => 'ti-receipt',
        'identität & zugriff' => 'ti-shield-lock',
        'schutz & compliance' => 'ti-lock',
        'servicegrenzen' => 'ti-adjustments',
        'speicher & backup' => 'ti-database',
        'netzwerk & performance' => 'ti-wifi',
        'copilot & ki' => 'ti-robot',
        'power platform betrieb' => 'ti-bolt',
        default => 'ti-compass',
    };
};
$landingOptions = is_array($landingOptions ?? null) ? $landingOptions : [];
$landingValue = static function (string $key, string $default) use ($landingOptions): string {
    $value = trim((string) ($landingOptions[$key] ?? ''));

    return $value !== '' ? $value : $default;
};
$landingChoice = static function (string $key, string $default, array $allowed) use ($landingOptions): string {
    $value = (string) ($landingOptions[$key] ?? $default);

    return in_array($value, $allowed, true) ? $value : $default;
};
$landingColor = static function (string $key, string $default) use ($landingOptions): string {
    $value = (string) ($landingOptions[$key] ?? $default);

    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $default;
};
$landingEnabled = static fn(string $key, string $default = '1'): bool => (string) ($landingOptions[$key] ?? $default) === '1';
$pageLayout = $landingChoice('landing_page_layout', 'wide', ['normal', 'wide', 'boxed', 'editorial', 'directory']);
$landingLayout = $landingChoice('landing_header_layout', 'split', ['split', 'stacked', 'compact', 'hero-card', 'editorial']);
$headerStyle = $landingChoice('landing_header_style', 'plain', ['plain', 'surface', 'bordered', 'accent', 'inverted']);
$headerAlignment = $landingChoice('landing_header_alignment', 'left', ['left', 'center', 'split']);
$buttonLayout = $landingChoice('landing_button_layout', 'inline', ['inline', 'stacked', 'right']);
$categoryLayout = $landingChoice('landing_category_layout', 'line', ['line', 'pills', 'cards', 'minimal']);
$toolLayout = $landingChoice('landing_tool_layout', 'grid', ['grid', 'compact-grid', 'list', 'directory', 'feature-first']);
$cardStyle = $landingChoice('landing_card_style', 'bordered', ['bordered', 'quiet', 'flat', 'accent']);
$density = $landingChoice('landing_density', 'comfortable', ['compact', 'comfortable', 'spacious']);
$toolButtonStyle = $landingChoice('landing_tool_button_style', 'link', ['link', 'primary', 'secondary', 'minimal']);
$toolButtonTargetMode = $landingChoice('landing_tool_button_target_mode', 'tool', ['tool', 'primary', 'secondary', 'custom']);
$cardRadius = max(0, min(2, (int) ($landingOptions['landing_card_radius'] ?? 2)));
$cardsMinWidth = max(220, min(520, (int) ($landingOptions['landing_cards_min_width'] ?? 320)));
$sectionGap = max(16, min(96, (int) ($landingOptions['landing_section_gap'] ?? 32)));
$landingPrimary = $landingColor('landing_color_primary', '#2563eb');
$landingAccent = $landingColor('landing_color_accent', '#0f766e');
$landingBackground = $landingColor('landing_color_background', '#ffffff');
$landingSurface = $landingColor('landing_color_surface', '#ffffff');
$landingSurfaceAlt = $landingColor('landing_color_surface_alt', '#f8fafc');
$landingHeaderBg = $landingColor('landing_color_header_background', '#f8fafc');
$landingHeaderText = $landingColor('landing_color_header_text', '#1e293b');
$landingHeaderMuted = $landingColor('landing_color_header_muted', '#64748b');
$landingHeaderBorder = $landingColor('landing_color_header_border', '#e2e8f0');
$landingPrimaryButtonBg = $landingColor('landing_color_button_primary_bg', '#2563eb');
$landingPrimaryButtonText = $landingColor('landing_color_button_primary_text', '#ffffff');
$landingSecondaryButtonBg = $landingColor('landing_color_button_secondary_bg', '#ffffff');
$landingSecondaryButtonText = $landingColor('landing_color_button_secondary_text', '#1e293b');
$landingText = $landingColor('landing_color_text', '#1e293b');
$landingMuted = $landingColor('landing_color_muted', '#64748b');
$landingBorder = $landingColor('landing_color_border', '#e2e8f0');
$showHeaderOverline = $landingEnabled('landing_show_header_overline');
$showHeaderTitle = $landingEnabled('landing_show_header_title');
$showHeaderIntro = $landingEnabled('landing_show_header_intro');
$showHeaderButtons = $landingEnabled('landing_show_header_buttons');
$showFacts = $landingEnabled('landing_show_facts');
$showFactModules = $landingEnabled('landing_show_fact_modules');
$showFactLive = $landingEnabled('landing_show_fact_live');
$showFactReviews = $landingEnabled('landing_show_fact_reviews');
$showCategoryNav = $landingEnabled('landing_show_category_nav');
$showCategoryOverline = $landingEnabled('landing_show_category_overline');
$showCategoryCounts = $landingEnabled('landing_show_category_counts');
$showReviewPanel = $landingEnabled('landing_show_review_panel');
$showReviewDomainSummaries = $landingEnabled('landing_show_review_domain_summaries');
$showIcons = $landingEnabled('landing_show_icons');
$linkCardTitles = $landingEnabled('landing_link_card_titles');
$showToolDescriptions = $landingEnabled('landing_show_tool_descriptions');
$showStatusLabels = $landingEnabled('landing_show_status_labels');
$showReviewChips = $landingEnabled('landing_show_review_chips');
$showModuleChecks = $landingEnabled('landing_show_module_checks');
$showToolButtons = $landingEnabled('landing_show_tool_buttons');
$showDisabledNote = $landingEnabled('landing_show_disabled_note');
$openButtonLabel = $landingValue('landing_open_button_label', '');
$hasCustomOpenButtonLabel = trim((string) ($landingOptions['landing_open_button_label'] ?? '')) !== '';
$toolCount = 0;
$liveCount = 0;
$categoryCounts = [];
foreach (($groupedTools ?? []) as $category => $tools) {
    $tools = is_array($tools) ? $tools : [];
    $categoryCounts[(string) $category] = count($tools);
    $toolCount += count($tools);

    foreach ($tools as $tool) {
        if (is_array($tool) && ($tool['status'] ?? '') === 'live') {
            $liveCount++;
        }
    }
}
$reviewDomainCount = is_array($bestPracticeDomains ?? null) ? count($bestPracticeDomains) : 0;

$landingTitle = $landingValue('landing_title', $t('M365 Tools', 'M365 Tools'));
$landingOverline = $landingValue('landing_overline', $t('Rechner & Tools', 'Calculators & Tools'));
$landingIntro = $landingValue('landing_intro', $t('Eine kuratierte Sammlung fuer Microsoft-365-Lizenzierung, Kosten, Speicher, Backup, Copilot, Telefonie, Migration und Betrieb.', 'A curated collection for Microsoft 365 licensing, cost, storage, backup, Copilot, telephony, migration and operations.'));
$landingPrimaryButtonLabel = $landingValue('landing_primary_button_label', $t('Alle ', 'Browse all ') . (string) $toolCount . $t(' Tools durchsuchen ↓', ' tools ↓'));
$landingPrimaryButtonUrl = $safeUrl($landingValue('landing_primary_button_url', '#direkteinstieg'));
$landingSecondaryButtonLabel = $landingValue('landing_secondary_button_label', $t('Kontakt aufnehmen', 'Contact us'));
$landingSecondaryButtonUrl = $safeUrl($landingValue('landing_secondary_button_url', $path('/kontakt')));
$primaryLabelNormalized = strtolower(trim($landingPrimaryButtonLabel));
if ($landingPrimaryButtonUrl === '' || $landingPrimaryButtonUrl === '/kontakt' || $landingPrimaryButtonUrl === '/en/kontakt' || $landingPrimaryButtonUrl === '#m365tools-explorer' || $primaryLabelNormalized === 'kontakt aufnehmen') {
    $landingPrimaryButtonLabel = $t('Alle ', 'Browse all ') . (string) $toolCount . $t(' Tools durchsuchen ↓', ' tools ↓');
    $landingPrimaryButtonUrl = '#direkteinstieg';
} elseif (str_contains($primaryLabelNormalized, 'tools durchsuchen') && !str_contains($landingPrimaryButtonLabel, '↓')) {
    $landingPrimaryButtonLabel .= ' ↓';
}
$landingToolButtonCustomUrl = $safeUrl($landingValue('landing_tool_button_custom_url', ''));
$landingReviewOverline = $landingValue('landing_review_overline', 'Querschnittsreview');
$landingReviewTitle = $landingValue('landing_review_title', (string) ($bestPracticeMeta['title'] ?? 'Microsoft 365 Best-Practice-Kompass'));
$landingReviewIntro = $landingValue('landing_review_intro', (string) ($bestPracticeMeta['summary'] ?? ''));

if (class_exists('CMS\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => $landingTitle]);
}
?>

<main class="phinit-plugin m365tools-page--<?php echo $esc($pageLayout); ?> m365tools-landing--<?php echo $esc($landingLayout); ?> m365tools-header--<?php echo $esc($headerStyle); ?> m365tools-header-align--<?php echo $esc($headerAlignment); ?> m365tools-buttons--<?php echo $esc($buttonLayout); ?> m365tools-category--<?php echo $esc($categoryLayout); ?> m365tools-tools--<?php echo $esc($toolLayout); ?> m365tools-cards--<?php echo $esc($cardStyle); ?> m365tools-tool-buttons--<?php echo $esc($toolButtonStyle); ?> m365tools-density--<?php echo $esc($density); ?>" id="m365tools-landing" style="--m365tools-card-radius: <?php echo (int) $cardRadius; ?>px; --m365tools-ui-radius: <?php echo (int) $cardRadius; ?>px; --m365tools-card-min: <?php echo (int) $cardsMinWidth; ?>px; --m365tools-section-gap: <?php echo (int) $sectionGap; ?>px; --m365tools-primary: var(--phinit-color-accent); --m365tools-accent: var(--phinit-color-accent); --m365tools-bg: var(--phinit-color-bg); --m365tools-surface: var(--phinit-color-surface); --m365tools-surface-alt: var(--phinit-color-surface); --m365tools-header-bg: var(--phinit-color-surface); --m365tools-header-text: var(--phinit-color-ink); --m365tools-header-muted: var(--phinit-color-ink-secondary); --m365tools-header-border: var(--phinit-color-border); --m365tools-button-primary-bg: var(--phinit-color-accent); --m365tools-button-primary-text: var(--phinit-color-accent-ink); --m365tools-button-secondary-bg: var(--phinit-color-surface); --m365tools-button-secondary-text: var(--phinit-color-ink); --m365tools-text: var(--phinit-color-ink); --m365tools-muted: var(--phinit-color-ink-secondary); --m365tools-border: var(--phinit-color-border);">
    <header class="m365tools-landing__header">
        <section class="m365tools-landing__intro" aria-labelledby="m365tools-title">
            <?php if ($showHeaderOverline): ?>
            <p class="phinit-overline"><?php echo $esc($landingOverline); ?></p>
            <?php endif; ?>
            <?php if ($showHeaderTitle): ?>
            <h1 id="m365tools-title"><?php echo $esc($landingTitle); ?></h1>
            <?php else: ?>
            <h1 id="m365tools-title" class="m365tools-visually-hidden"><?php echo $esc($landingTitle); ?></h1>
            <?php endif; ?>
            <?php if ($showHeaderIntro): ?>
            <p class="phinit-prose"><?php echo $esc($landingIntro); ?></p>
            <?php endif; ?>
            <?php if ($showHeaderButtons && ($landingPrimaryButtonUrl !== '' || $landingSecondaryButtonUrl !== '')): ?>
            <nav class="m365tools-landing__buttons" aria-label="<?php echo $esc($t('Landingpage Aktionen', 'Landing actions')); ?>">
                <?php if ($landingPrimaryButtonUrl !== ''): ?>
                <a class="phinit-btn phinit-btn--primary m365tools-btn m365tools-btn--primary" href="<?php echo $esc($landingPrimaryButtonUrl); ?>"<?php echo $landingPrimaryButtonUrl === '#direkteinstieg' ? ' data-m365tools-primary-search' : ''; ?>><?php echo $esc($landingPrimaryButtonLabel); ?></a>
                <?php endif; ?>
                <?php if ($landingSecondaryButtonUrl !== ''): ?>
                <a class="m365tools-contact-link" href="<?php echo $esc($landingSecondaryButtonUrl); ?>"><?php echo $esc($landingSecondaryButtonLabel); ?></a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        </section>
        <?php if ($showFacts && ($showFactModules || $showFactLive || $showFactReviews)): ?>
        <dl class="m365tools-landing__facts" aria-label="<?php echo $esc($t('Uebersicht Kennzahlen', 'Key metrics')); ?>">
            <?php if ($showFactModules): ?>
            <div>
                <dt><?php echo $esc($t('Module', 'Modules')); ?></dt>
                <dd><?php echo (int) $toolCount; ?></dd>
            </div>
            <?php endif; ?>
            <?php if ($showFactLive): ?>
            <div>
                <dt>Live</dt>
                <dd><?php echo (int) $liveCount; ?></dd>
            </div>
            <?php endif; ?>
            <?php if ($showFactReviews): ?>
            <div>
                <dt><?php echo $esc($t('Review-Bereiche', 'Review domains')); ?></dt>
                <dd><?php echo (int) $reviewDomainCount; ?></dd>
            </div>
            <?php endif; ?>
        </dl>
        <?php endif; ?>
    </header>

    <?php if ($showCategoryNav && !empty($categoryCounts)): ?>
    <span class="m365tools-anchor" id="m365tools-explorer" aria-hidden="true"></span>
    <section class="m365tools-finder" id="direkteinstieg" aria-labelledby="m365tools-finder-title" data-m365tools-finder>
        <div class="m365tools-finder__head">
            <section>
                <p class="phinit-overline"><?php echo $esc($t('Direkteinstieg', 'Quick access')); ?></p>
                <h2 id="m365tools-finder-title"><?php echo $esc($t('Alle Tools durchsuchen', 'Browse all tools')); ?></h2>
            </section>
            <p class="m365tools-finder__count" data-m365tools-result-count><?php echo (int) $toolCount; ?> <?php echo $esc($t('Tools sichtbar', 'tools visible')); ?></p>
        </div>
        <label class="m365tools-search" for="tool-search">
            <span class="m365tools-visually-hidden"><?php echo $esc($t('Tools suchen', 'Search tools')); ?></span>
            <span class="m365tools-search__control">
                <input id="tool-search" type="search" autocomplete="off" placeholder="<?php echo $esc($t('Nach Tool, Thema oder Kategorie suchen ...', 'Search by tool, topic, or category ...')); ?>" data-m365tools-search>
                <kbd class="m365tools-search__hint" aria-hidden="true">/</kbd>
            </span>
        </label>
        <div class="m365tools-tag-filter" role="radiogroup" aria-label="<?php echo $esc($t('Nach Kategorie filtern', 'Filter by category')); ?>" data-m365tools-chip-group>
            <button type="button" class="m365tools-tag-chip is-active" role="radio" aria-checked="true" data-m365tools-tag="all"><?php echo $esc($t('Alle', 'All')); ?></button>
            <?php foreach ($categoryCounts as $category => $count): ?>
            <button type="button" class="m365tools-tag-chip" role="radio" aria-checked="false" data-m365tools-tag="<?php echo $esc(strtolower((string) $category)); ?>">
                <?php echo $esc($category); ?>
                <?php if ($showCategoryCounts): ?>
                <span><?php echo (int) $count; ?></span>
                <?php endif; ?>
            </button>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <div class="m365tools-directory">
        <?php if ($showCategoryNav && !empty($categoryCounts)): ?>
        <nav class="m365tools-category-nav" aria-label="<?php echo $esc($t('Modulkategorien', 'Module categories')); ?>" data-m365tools-toc>
            <ol>
                <?php foreach ($categoryCounts as $category => $count): ?>
                <?php $sectionId = $categoryId((string) $category); ?>
                <li>
                    <a href="#<?php echo $esc($sectionId); ?>" data-m365tools-toc-link="<?php echo $esc($sectionId); ?>">
                        <span><?php echo $esc($category); ?></span>
                        <?php if ($showCategoryCounts): ?>
                        <small><?php echo (int) $count; ?></small>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php endif; ?>

        <div class="m365tools-directory__content" data-m365tools-list>

    <?php if ($showReviewPanel && !empty($bestPracticeDomains) && is_array($bestPracticeDomains)): ?>
    <section class="phinit-card m365tools-review-panel" aria-labelledby="m365tools-review-title">
        <header class="m365tools-section-head">
            <section>
                <p class="phinit-overline"><?php echo $esc($landingReviewOverline); ?></p>
                <h2 id="m365tools-review-title"><?php echo $esc($landingReviewTitle); ?></h2>
                <?php if ($landingReviewIntro !== ''): ?>
                <p class="phinit-prose"><?php echo $esc($landingReviewIntro); ?></p>
                <?php endif; ?>
            </section>
        </header>
        <ul class="m365tools-review-domain-grid" role="list">
            <?php foreach ($bestPracticeDomains as $domain): ?>
            <?php if (!is_array($domain)): ?>
            <?php continue; ?>
            <?php endif; ?>
            <li class="m365tools-review-domain">
                <strong><i class="ti <?php echo $esc($reviewIconClass($domain)); ?> m365tools-kompass-icon" aria-hidden="true"></i><span><?php echo $esc($domain['label'] ?? 'Review'); ?></span></strong>
                <?php if ($showReviewDomainSummaries): ?>
                <span><?php echo $esc($domain['summary'] ?? ''); ?></span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <?php if (empty($groupedTools)): ?>
    <section class="phinit-empty-state" role="status" aria-live="polite">
        <h2><?php echo $esc($t('Keine Rechner verfuegbar', 'No calculators available')); ?></h2>
        <p><?php echo $esc($t('Aktuell sind noch keine oeffentlichen Module registriert.', 'No public modules are currently registered.')); ?></p>
    </section>
    <?php endif; ?>

    <?php foreach ($groupedTools as $category => $tools): ?>
    <?php $sectionId = $categoryId((string) $category); ?>
    <?php $meta = $categoryMeta((string) $category); ?>
    <section aria-labelledby="<?php echo $esc($sectionId); ?>" data-m365tools-section data-m365tools-category="<?php echo $esc(strtolower((string) $category)); ?>">
        <header class="m365tools-section-head">
            <section class="m365tools-section-title">
                <span class="m365tools-section-icon" aria-hidden="true"><?php echo CMS_M365CALCULATOR_Icons::svg((string) ($meta['icon'] ?? 'calculator')); ?></span>
                <span>
                <?php if ($showCategoryOverline): ?>
                <p class="phinit-overline">Kategorie</p>
                <?php endif; ?>
                <h2 id="<?php echo $esc($sectionId); ?>"><?php echo $esc($category); ?></h2>
                <p class="m365tools-section-description"><?php echo $esc($meta['description'] ?? ''); ?></p>
                </span>
            </section>
            <?php if ($showCategoryCounts): ?>
            <span class="m365tools-section-count"><?php echo (int) ($categoryCounts[(string) $category] ?? count((array) $tools)); ?> Module</span>
            <?php endif; ?>
        </header>

        <?php if (empty($tools)): ?>
        <section class="phinit-empty-state" role="status" aria-live="polite">
            <h3>Keine Module in dieser Kategorie</h3>
            <p>Für diese Kategorie sind aktuell keine Rechner registriert.</p>
        </section>
        <?php else: ?>
        <ul class="phinit-tool-grid" role="list">
            <?php foreach ($tools as $tool): ?>
            <?php
            $status = (string) ($tool['status'] ?? 'soon');
            $url = $safeUrl($tool['url'] ?? '');
            $isLinked = $url !== '' && in_array($status, ['live', 'beta'], true);
            $buttonUrl = match ($toolButtonTargetMode) {
                'primary' => $landingPrimaryButtonUrl,
                'secondary' => $landingSecondaryButtonUrl,
                'custom' => $landingToolButtonCustomUrl,
                default => $url,
            };
            $buttonIsLinked = $buttonUrl !== '' && in_array($status, ['live', 'beta'], true);
            $label = $statusLabel($status);
            $toolKey = (string) ($tool['key'] ?? '');
            $domainKeys = isset($toolReviewMap[$toolKey]) && is_array($toolReviewMap[$toolKey]) ? $toolReviewMap[$toolKey] : [];
            $toolReviewLabels = $reviewLabels($domainKeys, is_array($bestPracticeDomains ?? null) ? $bestPracticeDomains : []);
            $toolReviewChecks = $reviewChecks($toolKey, is_array($toolCheckMap ?? null) ? $toolCheckMap : []);
            $toolTitle = (string) ($tool['title'] ?? '');
            $toolDescription = (string) ($tool['description'] ?? '');
            $toolText = strtolower(trim((string) $category . ' ' . $toolTitle . ' ' . $toolDescription . ' ' . implode(' ', $toolReviewLabels) . ' ' . implode(' ', $toolReviewChecks)));
            $isPopularTool = in_array($toolKey, ['license-audit-checklist', 'm365lic', 'm365-lizenzvergleich'], true);
            $isNewTool = in_array($toolKey, ['copilot-roi'], true);
            $cardButtonLabel = $openButtonLabel;
            if (!$hasCustomOpenButtonLabel) {
                $titleLower = strtolower($toolTitle);
                $cardButtonLabel = str_contains($titleLower, 'checkliste') ? 'Checkliste laden' : (str_contains($titleLower, 'rechner') ? 'Rechner starten' : 'Tool öffnen');
            }
            ?>
            <li data-m365tools-card data-m365tools-category="<?php echo $esc(strtolower((string) $category)); ?>" data-m365tools-search-value="<?php echo $esc($toolText); ?>">
                <article class="phinit-card phinit-card--accent<?php echo $status === 'soon' ? ' phinit-tool-card--disabled' : ''; ?>"<?php echo $buttonIsLinked ? ' data-m365tools-card-url="' . $esc($buttonUrl) . '"' : ''; ?><?php echo $status === 'soon' ? ' aria-disabled="true"' : ''; ?>>
                    <header class="phinit-tool-card__head">
                        <?php if ($showIcons): ?>
                        <span class="phinit-tool-card__icon" aria-hidden="true">
                            <?php echo CMS_M365CALCULATOR_Icons::svg((string) ($tool['icon'] ?? 'calculator')); ?>
                        </span>
                        <?php endif; ?>
                        <h3>
                            <?php if ($isLinked && $linkCardTitles): ?>
                            <a href="<?php echo $esc($url); ?>"><?php echo $esc($toolTitle); ?></a>
                            <?php else: ?>
                            <span><?php echo $esc($toolTitle); ?></span>
                            <?php endif; ?>
                            <?php if ($showStatusLabels && $label !== ''): ?>
                            <span class="phinit-status-label"><?php echo $esc($label); ?></span>
                            <?php endif; ?>
                        </h3>
                        <?php if ($isPopularTool || $isNewTool): ?>
                        <span class="m365tools-card-badges" aria-label="Tool-Hinweise">
                            <?php if ($isPopularTool): ?><span class="m365tools-badge m365tools-badge--popular">Beliebt</span><?php endif; ?>
                            <?php if ($isNewTool): ?><span class="m365tools-badge m365tools-badge--new">Neu</span><?php endif; ?>
                        </span>
                        <?php endif; ?>
                    </header>
                    <section class="phinit-tool-card__body">
                        <?php if ($showToolDescriptions): ?>
                        <p><?php echo $esc($toolDescription); ?></p>
                        <?php endif; ?>
                        <?php if ($showReviewChips && !empty($toolReviewLabels)): ?>
                        <ul class="m365tools-review-chip-list" role="list" aria-label="Review-Schwerpunkte">
                            <?php foreach (array_slice($toolReviewLabels, 0, 4) as $reviewLabel): ?>
                            <li><?php echo $esc($reviewLabel); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                        <?php if ($showModuleChecks && !empty($toolReviewChecks)): ?>
                        <ul class="m365tools-review-check-list" role="list" aria-label="Aktuelle Prüfpunkte">
                            <?php foreach (array_slice($toolReviewChecks, 0, 2) as $reviewCheck): ?>
                            <li><?php echo $esc($reviewCheck); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                        <?php if ($showToolButtons && $buttonIsLinked): ?>
                        <a href="<?php echo $esc($buttonUrl); ?>" class="phinit-btn phinit-btn--link m365tools-tool-button">
                            <?php echo $esc($cardButtonLabel); ?><?php if ($toolButtonStyle !== 'minimal'): ?> <span class="phinit-arrow" aria-hidden="true">→</span><?php endif; ?>
                        </a>
                        <?php elseif ($showDisabledNote && !$buttonIsLinked): ?>
                        <span class="phinit-tool-card__disabled-note" aria-disabled="true">Nicht verfügbar</span>
                        <?php endif; ?>
                    </section>
                </article>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>
    <?php endforeach; ?>
        </div>
    </div>

    <section class="phinit-empty-state m365tools-no-results" role="status" aria-live="polite" hidden data-m365tools-empty>
        <h2><?php echo $esc($t('Keine passenden Tools gefunden', 'No matching tools found')); ?></h2>
        <p><?php echo $esc($t('Bitte Suchbegriff anpassen oder einen anderen Kategorie-Chip waehlen.', 'Adjust your query or choose a different category chip.')); ?></p>
    </section>

    <button type="button" class="m365tools-back-to-top" aria-label="<?php echo $esc($t('Nach oben', 'Back to top')); ?>" data-m365tools-top>↑</button>
</main>

<?php
if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getFooter();
}
