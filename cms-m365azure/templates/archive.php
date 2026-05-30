<?php
/**
 * Public Azure Services archive.
 *
 * @package CMS_M365Azure
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$normalizeText = static fn(string $value): string => str_replace(["\\r\\n", "\\n", "\\r"], ["\n", "\n", "\n"], $value);
$text = static function (array $settings, string $key, string $default = '') use ($normalizeText): string {
    $value = trim(strip_tags($normalizeText((string) ($settings[$key] ?? ''))));
    return $value !== '' ? $value : $default;
};
$enabled = static fn(array $settings, string $key, string $default = '1'): bool => (string) ($settings[$key] ?? $default) === '1';
$lines = static function (string $value) use ($normalizeText): array {
    $value = $normalizeText($value);
    $items = array_filter(array_map(static fn(string $line): string => trim(strip_tags($line)), preg_split('/\R/', $value) ?: []));
    return array_values($items);
};
$safeId = static function (string $value, string $fallback): string {
    $id = trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($value)), '-');
    return $id !== '' ? $id : $fallback;
};
$copyHtml = static function (string $value) use ($esc, $normalizeText): string {
    $value = trim(strip_tags($normalizeText($value)));
    return $value !== '' ? nl2br($esc($value)) : '—';
};
$listHtml = static function (array $items, string $emptyLabel) use ($esc): string {
    if ($items === []) {
        return '<span class="azs-cell-empty">' . $esc($emptyLabel) . '</span>';
    }

    $html = '<ul class="m365calc-note-list azs-cell-list">';
    foreach ($items as $item) {
        $html .= '<li>' . $esc($item) . '</li>';
    }
    $html .= '</ul>';

    return $html;
};
$serviceDescription = static function (array $service) use ($normalizeText): string {
    $content = trim(strip_tags($normalizeText((string) ($service['content'] ?? ''))));
    if ($content !== '') {
        return $content;
    }

    return trim(strip_tags($normalizeText((string) ($service['summary'] ?? ''))));
};
$serviceLinksHtml = static function (array $service, string $docsLabel, string $pricingLabel, string $emptyLabel) use ($esc): string {
    $docsUrl = trim((string) ($service['docs_url'] ?? ''));
    $pricingUrl = trim((string) ($service['pricing_url'] ?? ''));
    if ($docsUrl === '' && $pricingUrl === '') {
        return '<span class="azs-cell-empty">' . $esc($emptyLabel) . '</span>';
    }

    $html = '<span class="azs-link-list">';
    if ($docsUrl !== '') {
        $html .= '<a class="phinit-btn phinit-btn--link" href="' . $esc($docsUrl) . '" target="_blank" rel="noopener noreferrer">' . $esc($docsLabel) . ' <span aria-hidden="true">→</span></a>';
    }
    if ($pricingUrl !== '') {
        $html .= '<a class="phinit-btn phinit-btn--link" href="' . $esc($pricingUrl) . '" target="_blank" rel="noopener noreferrer">' . $esc($pricingLabel) . ' <span aria-hidden="true">→</span></a>';
    }
    $html .= '</span>';

    return $html;
};
$tableColumnWidths = static function (bool $showDescription, bool $showFeatures, bool $showUseCases, bool $showLinks): array {
    $base = ['service' => 28.0];
    if ($showDescription) {
        $base['description'] = 22.0;
    }
    if ($showFeatures) {
        $base['features'] = 20.0;
    }
    if ($showUseCases) {
        $base['use_cases'] = 20.0;
    }
    if ($showLinks) {
        $base['links'] = 10.0;
    }

    $sum = max(1.0, array_sum($base));
    foreach ($base as $key => $weight) {
        $base[$key] = round(($weight / $sum) * 100, 2);
    }

    return $base;
};

$showHero = $enabled($settings, 'show_hero', '1');
$showHeroActions = $enabled($settings, 'show_hero_actions', '1');
$showToc = $enabled($settings, 'show_toc', '1');
$tocNowrap = $enabled($settings, 'toc_nowrap', '1');
$showCategoryIntro = $enabled($settings, 'show_category_intro', '1');
$showImages = $enabled($settings, 'show_service_images', '1');
$showSubtitles = $enabled($settings, 'show_service_subtitles', '1');
$showDescription = $enabled($settings, 'show_description', '1');
$showLinks = $enabled($settings, 'show_service_links', '1');
$showFeatures = $enabled($settings, 'show_feature_lists', '1');
$showUseCases = $enabled($settings, 'show_use_cases', '1');
$showNotesSection = $enabled($settings, 'show_notes_section', '1');
$showInfoNote = $enabled($settings, 'show_info_note', '1');
$showSourcesCard = $enabled($settings, 'show_sources_card', '1');
$heroTitle = $text($settings, 'page_title', 'Microsoft Azure Services');
$heroOverline = $text($settings, 'page_overline', 'Azure Überblick');
$heroIntro = $text($settings, 'page_intro', 'Übersicht der wichtigsten Microsoft Azure Services.');
$tocTitle = $text($settings, 'toc_title', 'Inhaltsverzeichnis');
$tocColumns = in_array((string) ($settings['toc_columns'] ?? '3'), ['1', '2', '3', '4'], true) ? (string) $settings['toc_columns'] : '3';
$imagePosition = ((string) ($settings['card_image_position'] ?? 'left')) === 'right' ? 'right' : 'left';
$tableServiceLabel = $text($settings, 'table_service_label', 'Dienst');
$tableDescriptionLabel = $text($settings, 'table_description_label', 'Beschreibung');
$tableFeaturesLabel = $text($settings, 'table_features_label', 'Wichtige Hinweise');
$tableUseCasesLabel = $text($settings, 'table_use_cases_label', 'Typische Einsatzszenarien');
$tableLinksLabel = $text($settings, 'table_links_label', 'Links');
$docsLinkLabel = $text($settings, 'docs_link_label', 'Dokumentation');
$pricingLinkLabel = $text($settings, 'pricing_link_label', 'Preise');
$emptyLabel = $text($settings, 'empty_value_label', '—');
$tableColumns = $tableColumnWidths($showDescription, $showFeatures, $showUseCases, $showLinks);
$noteTitle = $text($settings, 'note_title', 'Hinweise zu Azure Services');
$noteItems = $lines((string) ($settings['note_items'] ?? ''));
$sourceTitle = $text($settings, 'source_title', 'Quellenstand');
$sourceIntro = $text($settings, 'source_intro', 'Die Quellenliste basiert auf den aktuell hinterlegten Dokumentations- und Preislinks der angezeigten Azure-Dienste.');
$sourceDetailsLabel = $text($settings, 'source_details_label', 'Quellen anzeigen');
$heroButtons = [
    ['secondary', $text($settings, 'hero_primary_button_text', 'M365 Lizenzmatrix öffnen'), $text($settings, 'hero_primary_button_url', '/m365-lizenzmatrix')],
    ['secondary', $text($settings, 'hero_secondary_button_text', 'M365 AddOn-Übersicht öffnen'), $text($settings, 'hero_secondary_button_url', '/m365-addon-matrix')],
    ['primary', $text($settings, 'hero_cta_button_text', 'Azure-Beratung anfragen'), $text($settings, 'hero_cta_button_url', '/kontakt')],
];
$mainClasses = 'phinit-plugin m365calc-page m365calc-comparison-page m365calc-readonly-page m365calc-matrix-page m365calc-matrix-header--accent m365calc-matrix-align--split m365calc-matrix-buttons--inline m365calc-matrix-button-style--default azs-page azs-image-position--' . $imagePosition;
$visibleCategories = [];
$sourceLinks = [];

foreach ($categories as $category) {
    $categoryServices = $servicesByCategory[(int) $category['id']] ?? [];
    if ($categoryServices === []) {
        continue;
    }

    $visibleCategories[] = $category;
    foreach ($categoryServices as $service) {
        foreach (['docs_url', 'pricing_url'] as $linkKey) {
            $link = trim((string) ($service[$linkKey] ?? ''));
            if ($link !== '') {
                $sourceLinks[$link] = $link;
            }
        }
    }
}
?>
<main class="<?php echo $esc($mainClasses); ?>" id="azure-services" aria-labelledby="azs-page-title">
    <?php if (!$showHero): ?>
    <h1 class="m365calc-visually-hidden" id="azs-page-title"><?php echo $esc($heroTitle); ?></h1>
    <?php endif; ?>

    <?php if ($showHero): ?>
    <header class="m365calc-hero">
        <p class="phinit-overline"><?php echo $esc($heroOverline); ?></p>
        <section class="m365calc-hero__content" aria-labelledby="azs-page-title">
            <section>
                <h1 id="azs-page-title"><?php echo $esc($heroTitle); ?></h1>
                <p class="phinit-prose"><?php echo $esc($heroIntro); ?></p>
            </section>
            <?php if ($showHeroActions): ?>
            <nav class="m365calc-actions" aria-label="Weitere Übersichten und Anfrage">
                <?php foreach ($heroButtons as [$variant, $label, $url]): ?>
                <?php if ($label !== '' && $url !== ''): ?>
                <a class="phinit-btn phinit-btn--<?php echo $variant === 'primary' ? 'primary m365calc-matrix-primary-action' : 'secondary m365calc-matrix-action'; ?>" href="<?php echo $esc($url); ?>"><?php echo $esc($label); ?></a>
                <?php endif; ?>
                <?php endforeach; ?>
            </nav>
            <?php endif; ?>
        </section>
    </header>
    <?php endif; ?>

    <?php if ($showToc && $visibleCategories !== []): ?>
    <nav class="phinit-result m365calc-result-card m365calc-addon-toc m365calc-addon-toc--cols-<?php echo $esc($tocColumns); ?><?php echo $tocNowrap ? ' m365calc-addon-toc--nowrap' : ''; ?> azs-toc" aria-labelledby="azs-toc-title">
        <h2 id="azs-toc-title"><?php echo $esc($tocTitle); ?></h2>
        <div class="m365calc-addon-toc__links">
            <?php foreach ($visibleCategories as $category): ?>
            <?php $categoryId = 'azs-cat-' . $safeId((string) $category['slug'], (string) $category['id']); ?>
            <a class="m365calc-addon-toc__link" href="#<?php echo $esc($categoryId); ?>">
                <span><?php echo $esc((string) $category['title']); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </nav>
    <?php endif; ?>

    <?php foreach ($visibleCategories as $category): ?>
    <?php
    $categoryServices = $servicesByCategory[(int) $category['id']] ?? [];
    $categoryId = 'azs-cat-' . $safeId((string) $category['slug'], (string) $category['id']);
    $categoryGallery = CMS_M365Azure_Repository::gallery_images_list($category['gallery_images'] ?? '');
    ?>
    <section class="phinit-result m365calc-result-card m365calc-readonly-area azs-category" id="<?php echo $esc($categoryId); ?>" aria-labelledby="<?php echo $esc($categoryId); ?>-title">
        <header class="m365calc-result-heading">
            <section class="azs-category-heading-copy">
                <p class="phinit-overline"><?php echo $esc((string) ($category['overline'] ?? 'Azure Kategorie')); ?></p>
                <h2 id="<?php echo $esc($categoryId); ?>-title"><?php echo $esc((string) $category['title']); ?></h2>
                <?php $categoryIntro = $normalizeText((string) ($category['intro'] ?? '')); ?>
                <?php if ($showCategoryIntro && trim(strip_tags($categoryIntro)) !== ''): ?>
                <p><?php echo $copyHtml($categoryIntro); ?></p>
                <?php endif; ?>
            </section>
            <?php if ($categoryGallery !== []): ?>
            <aside class="azs-category-gallery" aria-label="Bilder zu <?php echo $esc((string) $category['title']); ?>">
                <?php foreach ($categoryGallery as $imageIndex => $imageUrl): ?>
                <img class="azs-category-gallery__thumb" src="<?php echo $esc($imageUrl); ?>" alt="<?php echo $esc((string) $category['title'] . ' Bild ' . ((int) $imageIndex + 1)); ?>" loading="lazy" decoding="async" width="56" height="56">
                <?php endforeach; ?>
            </aside>
            <?php endif; ?>
        </header>

        <section class="phinit-table-wrap m365calc-compare-wrap m365calc-readonly-wrap azs-service-table-wrap" aria-label="Azure Services im Bereich <?php echo $esc((string) $category['title']); ?>">
            <table class="phinit-table m365calc-compare-table m365calc-readonly-table azs-service-table">
                <caption class="m365calc-visually-hidden">Azure Services im Bereich <?php echo $esc((string) $category['title']); ?></caption>
                <colgroup>
                    <?php foreach ($tableColumns as $columnKey => $columnWidth): ?>
                    <col class="azs-col-<?php echo $esc((string) $columnKey); ?>" style="width: <?php echo $esc((string) $columnWidth); ?>%;">
                    <?php endforeach; ?>
                </colgroup>
                <thead>
                    <tr>
                        <th scope="col"><?php echo $esc($tableServiceLabel); ?></th>
                        <?php if ($showDescription): ?>
                        <th scope="col"><?php echo $esc($tableDescriptionLabel); ?></th>
                        <?php endif; ?>
                        <?php if ($showFeatures): ?>
                        <th scope="col"><?php echo $esc($tableFeaturesLabel); ?></th>
                        <?php endif; ?>
                        <?php if ($showUseCases): ?>
                        <th scope="col"><?php echo $esc($tableUseCasesLabel); ?></th>
                        <?php endif; ?>
                        <?php if ($showLinks): ?>
                        <th scope="col"><?php echo $esc($tableLinksLabel); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categoryServices as $service): ?>
                    <?php
                    $imageUrl = trim((string) ($service['image_url'] ?? ''));
                    $hasImage = $showImages && $imageUrl !== '';
                    $description = $serviceDescription($service);
                    $featureItems = $showFeatures ? $lines((string) ($service['features'] ?? '')) : [];
                    $useCaseItems = $showUseCases ? $lines((string) ($service['use_cases'] ?? '')) : [];
                    $serviceId = 'azs-service-' . $safeId((string) $service['slug'], (string) $service['id']);
                    ?>
                    <tr id="<?php echo $esc($serviceId); ?>">
                        <th scope="row">
                            <span class="m365calc-status m365calc-status--note azs-service-title">
                                <?php if ($hasImage): ?>
                                <img class="azs-service-icon" src="<?php echo $esc($imageUrl); ?>" alt="<?php echo $esc((string) ($service['image_alt'] ?: $service['title'])); ?>" loading="lazy" width="96" height="54">
                                <?php endif; ?>
                                <span>
                                    <strong><?php echo $esc((string) $service['title']); ?></strong>
                                    <?php if ($showSubtitles && (string) ($service['subtitle'] ?? '') !== ''): ?>
                                    <small><?php echo $esc((string) $service['subtitle']); ?></small>
                                    <?php endif; ?>
                                </span>
                            </span>
                        </th>
                        <?php if ($showDescription): ?>
                        <td data-label="<?php echo $esc($tableDescriptionLabel); ?>">
                            <span class="m365calc-status m365calc-status--note azs-service-cell">
                                <strong><?php echo $esc($tableDescriptionLabel); ?></strong>
                                <span class="azs-cell-copy"><?php echo $copyHtml($description); ?></span>
                            </span>
                        </td>
                        <?php endif; ?>
                        <?php if ($showFeatures): ?>
                        <td data-label="<?php echo $esc($tableFeaturesLabel); ?>">
                            <span class="m365calc-status m365calc-status--note azs-service-cell">
                                <strong><?php echo $esc($tableFeaturesLabel); ?></strong>
                                <span class="azs-cell-copy"><?php echo $listHtml($featureItems, $emptyLabel); ?></span>
                            </span>
                        </td>
                        <?php endif; ?>
                        <?php if ($showUseCases): ?>
                        <td data-label="<?php echo $esc($tableUseCasesLabel); ?>">
                            <span class="m365calc-status m365calc-status--note azs-service-cell">
                                <strong><?php echo $esc($tableUseCasesLabel); ?></strong>
                                <span class="azs-cell-copy"><?php echo $listHtml($useCaseItems, $emptyLabel); ?></span>
                            </span>
                        </td>
                        <?php endif; ?>
                        <?php if ($showLinks): ?>
                        <td data-label="<?php echo $esc($tableLinksLabel); ?>">
                            <span class="m365calc-status m365calc-status--note azs-service-cell">
                                <strong><?php echo $esc($tableLinksLabel); ?></strong>
                                <span class="azs-cell-copy"><?php echo $serviceLinksHtml($service, $docsLinkLabel, $pricingLinkLabel, $emptyLabel); ?></span>
                            </span>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="azs-service-accordion" aria-label="Mobile Azure Services im Bereich <?php echo $esc((string) $category['title']); ?>">
            <?php foreach ($categoryServices as $service): ?>
            <?php
            $description = $serviceDescription($service);
            $featureItems = $showFeatures ? $lines((string) ($service['features'] ?? '')) : [];
            $useCaseItems = $showUseCases ? $lines((string) ($service['use_cases'] ?? '')) : [];
            $imageUrl = trim((string) ($service['image_url'] ?? ''));
            $hasImage = $showImages && $imageUrl !== '';
            ?>
            <details class="phinit-card azs-service-accordion__item">
                <summary>
                    <?php if ($hasImage): ?>
                    <img class="azs-service-icon" src="<?php echo $esc($imageUrl); ?>" alt="<?php echo $esc((string) ($service['image_alt'] ?: $service['title'])); ?>" loading="lazy" width="96" height="54">
                    <?php endif; ?>
                    <span>
                        <strong><?php echo $esc((string) $service['title']); ?></strong>
                        <?php if ($showSubtitles && (string) ($service['subtitle'] ?? '') !== ''): ?>
                        <small><?php echo $esc((string) $service['subtitle']); ?></small>
                        <?php endif; ?>
                    </span>
                </summary>
                <section class="azs-service-accordion__body" aria-label="Details zu <?php echo $esc((string) $service['title']); ?>">
                    <?php if ($showDescription): ?>
                    <article>
                        <h3><?php echo $esc($tableDescriptionLabel); ?></h3>
                        <p><?php echo $copyHtml($description); ?></p>
                    </article>
                    <?php endif; ?>
                    <?php if ($showFeatures): ?>
                    <article>
                        <h3><?php echo $esc($tableFeaturesLabel); ?></h3>
                        <?php echo $listHtml($featureItems, $emptyLabel); ?>
                    </article>
                    <?php endif; ?>
                    <?php if ($showUseCases): ?>
                    <article>
                        <h3><?php echo $esc($tableUseCasesLabel); ?></h3>
                        <?php echo $listHtml($useCaseItems, $emptyLabel); ?>
                    </article>
                    <?php endif; ?>
                    <?php if ($showLinks): ?>
                    <article>
                        <h3><?php echo $esc($tableLinksLabel); ?></h3>
                        <?php echo $serviceLinksHtml($service, $docsLinkLabel, $pricingLinkLabel, $emptyLabel); ?>
                    </article>
                    <?php endif; ?>
                </section>
            </details>
            <?php endforeach; ?>
        </section>
    </section>
    <?php endforeach; ?>

    <?php if ($showNotesSection && ($showInfoNote || $showSourcesCard)): ?>
    <section class="m365calc-result-grid" aria-label="Hinweise und Quellen">
        <?php if ($showInfoNote): ?>
        <article class="phinit-note phinit-note--warning">
            <h2><?php echo $esc($noteTitle); ?></h2>
            <?php echo $listHtml($noteItems, $emptyLabel); ?>
        </article>
        <?php endif; ?>
        <?php if ($showSourcesCard): ?>
        <article class="phinit-note phinit-note--info m365calc-source-card">
            <h2><?php echo $esc($sourceTitle); ?></h2>
            <p><?php echo $esc($sourceIntro); ?></p>
            <?php if ($sourceLinks !== []): ?>
            <details>
                <summary><?php echo $esc($sourceDetailsLabel); ?></summary>
                <ul class="m365calc-note-list">
                    <?php foreach ($sourceLinks as $sourceLink): ?>
                    <li><a href="<?php echo $esc($sourceLink); ?>" target="_blank" rel="noopener noreferrer"><?php echo $esc($sourceLink); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </details>
            <?php endif; ?>
        </article>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</main>
