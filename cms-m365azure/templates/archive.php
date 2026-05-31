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
$lang = isset($lang) && $lang === 'en' ? 'en' : 'de';
$tr = static fn(string $de, string $en): string => $lang === 'en' ? $en : $de;
$text = static function (array $settings, string $key, string $default = '') use ($normalizeText, $lang): string {
    if ($lang === 'en') {
        $translated = trim(strip_tags($normalizeText((string) ($settings[$key . '_en'] ?? ''))));
        if ($translated !== '') {
            return $translated;
        }
    }
    $value = trim(strip_tags($normalizeText((string) ($settings[$key] ?? ''))));
    return $value !== '' ? $value : $default;
};
$rowText = static function (array $row, string $key, string $default = '') use ($normalizeText, $lang): string {
    if ($lang === 'en') {
        $translated = trim(strip_tags($normalizeText((string) ($row[$key . '_en'] ?? ''))));
        if ($translated !== '') {
            return $translated;
        }
    }

    $value = trim(strip_tags($normalizeText((string) ($row[$key] ?? ''))));
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
$serviceDescription = static function (array $service) use ($normalizeText, $rowText): string {
    $content = trim(strip_tags($normalizeText($rowText($service, 'content'))));
    if ($content !== '') {
        return $content;
    }

    return trim(strip_tags($normalizeText($rowText($service, 'summary'))));
};
$localizedInternalUrl = static function (string $url) use ($lang): string {
    $normalized = CMS_M365Azure_Repository::public_url($url);
    if ($normalized === '' || $lang !== 'en' || !str_starts_with($normalized, '/')) {
        return $normalized;
    }

    if (function_exists('cms_plugin_public_localized_path')) {
        return cms_plugin_public_localized_path($normalized, $lang);
    }

    return '/en' . ($normalized === '/' ? '' : $normalized);
};
$serviceLinksHtml = static function (array $service, string $docsLabel, string $pricingLabel, string $emptyLabel) use ($esc): string {
    $docsUrl = CMS_M365Azure_Repository::public_url((string) ($service['docs_url'] ?? ''));
    $pricingUrl = CMS_M365Azure_Repository::public_url((string) ($service['pricing_url'] ?? ''));
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
    $base = ['service' => 23.0];
    if ($showDescription) {
        $base['description'] = 23.0;
    }
    if ($showFeatures) {
        $base['features'] = 20.0;
    }
    if ($showUseCases) {
        $base['use_cases'] = 20.0;
    }
    if ($showLinks) {
        $base['links'] = 14.0;
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
$heroOverline = $text($settings, 'page_overline', $tr('Azure Überblick', 'Azure Overview'));
$heroIntro = $text($settings, 'page_intro', $tr('Übersicht der wichtigsten Microsoft Azure Services.', 'Overview of key Microsoft Azure services.'));
$tocTitle = $text($settings, 'toc_title', $tr('Inhaltsverzeichnis', 'Table of contents'));
$tocColumns = in_array((string) ($settings['toc_columns'] ?? '3'), ['1', '2', '3', '4'], true) ? (string) $settings['toc_columns'] : '3';
$imagePosition = ((string) ($settings['card_image_position'] ?? 'left')) === 'right' ? 'right' : 'left';
$tableServiceLabel = $text($settings, 'table_service_label', $tr('Dienst', 'Service'));
$tableDescriptionLabel = $text($settings, 'table_description_label', $tr('Beschreibung', 'Description'));
$tableFeaturesLabel = $text($settings, 'table_features_label', $tr('Wichtige Hinweise', 'Key notes'));
$tableUseCasesLabel = $text($settings, 'table_use_cases_label', $tr('Typische Einsatzszenarien', 'Typical use cases'));
$tableLinksLabel = $text($settings, 'table_links_label', 'Links');
$docsLinkLabel = $text($settings, 'docs_link_label', $tr('Dokumentation', 'Documentation'));
$pricingLinkLabel = $text($settings, 'pricing_link_label', $tr('Preise', 'Pricing'));
$emptyLabel = $text($settings, 'empty_value_label', '—');
$tableColumns = $tableColumnWidths($showDescription, $showFeatures, $showUseCases, $showLinks);
$noteTitle = $text($settings, 'note_title', $tr('Hinweise zu Azure Services', 'Notes on Azure services'));
$noteItems = $lines((string) ($settings['note_items'] ?? ''));
$sourceTitle = $text($settings, 'source_title', $tr('Quellenstand', 'Source status'));
$sourceIntro = $text($settings, 'source_intro', $tr('Die Quellenliste basiert auf den aktuell hinterlegten Dokumentations- und Preislinks der angezeigten Azure-Dienste.', 'The source list is based on the currently maintained documentation and pricing links of the displayed Azure services.'));
$sourceDetailsLabel = $text($settings, 'source_details_label', $tr('Quellen anzeigen', 'Show sources'));
$showWellArchitectedChecklist = $enabled($settings, 'show_well_architected_checklist', '1');
$wellArchitectedTitle = $text($settings, 'well_architected_title', $tr('Well-Architected Verbesserungs-Checkliste', 'Well-Architected improvement checklist'));
$wellArchitectedIntro = $text($settings, 'well_architected_intro', $tr('Diskussionsgrundlage für Architektur-Workshops je Kategorie.', 'Discussion baseline for architecture workshops per category.'));
$wellArchitectedPillars = [
    $text($settings, 'well_architected_pillar_reliability', $tr('Reliability: Betriebsrisiken, Wiederherstellung und Abhängigkeiten prüfen.', 'Reliability: review operational risks, recovery, and dependencies.')),
    $text($settings, 'well_architected_pillar_security', $tr('Security: Identitäten, Netzwerkzugriff, Secrets und Datenzugriffe härten.', 'Security: harden identities, network access, secrets, and data access.')),
    $text($settings, 'well_architected_pillar_cost', $tr('Cost Optimization: Kostenhebel, Abschaltregeln und Reserved Capacity bewerten.', 'Cost Optimization: evaluate cost levers, shutdown policies, and reserved capacity.')),
    $text($settings, 'well_architected_pillar_operational', $tr('Operational Excellence: Monitoring, Alerts, Runbooks und Verantwortlichkeiten festlegen.', 'Operational Excellence: define monitoring, alerts, runbooks, and ownership.')),
    $text($settings, 'well_architected_pillar_performance', $tr('Performance Efficiency: Skalierung, Lastprofile und Engpässe validieren.', 'Performance Efficiency: validate scaling, load profiles, and bottlenecks.')),
];
$wellArchitectedItems = array_values(array_filter(array_map(static fn(string $item): string => trim($item), $wellArchitectedPillars)));
$heroButtons = [
    ['secondary', $text($settings, 'hero_primary_button_text', $tr('M365 Lizenzmatrix öffnen', 'Open M365 license matrix')), $localizedInternalUrl($text($settings, 'hero_primary_button_url', '/m365-lizenzmatrix'))],
    ['secondary', $text($settings, 'hero_secondary_button_text', $tr('M365 AddOn-Übersicht öffnen', 'Open M365 add-on overview')), $localizedInternalUrl($text($settings, 'hero_secondary_button_url', '/m365-addon-matrix'))],
    ['primary', $text($settings, 'hero_cta_button_text', $tr('Azure-Beratung anfragen', 'Request Azure consulting')), $localizedInternalUrl($text($settings, 'hero_cta_button_url', '/kontakt'))],
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
                <span><?php echo $esc($rowText($category, 'title')); ?></span>
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
                <p class="phinit-overline"><?php echo $esc($rowText($category, 'overline', $tr('Azure Kategorie', 'Azure category'))); ?></p>
                <h2 id="<?php echo $esc($categoryId); ?>-title"><?php echo $esc($rowText($category, 'title')); ?></h2>
                <?php $categoryIntro = $normalizeText($rowText($category, 'intro')); ?>
                <?php if ($showCategoryIntro && trim(strip_tags($categoryIntro)) !== ''): ?>
                <p><?php echo $copyHtml($categoryIntro); ?></p>
                <?php endif; ?>
            </section>
            <?php if ($categoryGallery !== []): ?>
            <aside class="azs-category-gallery" aria-label="<?php echo $esc($tr('Bilder zu ', 'Images for ') . $rowText($category, 'title')); ?>">
                <?php foreach ($categoryGallery as $imageIndex => $imageUrl): ?>
                <img class="azs-category-gallery__thumb" src="<?php echo $esc($imageUrl); ?>" alt="<?php echo $esc($rowText($category, 'title') . ' ' . $tr('Bild', 'Image') . ' ' . ((int) $imageIndex + 1)); ?>" loading="lazy" decoding="async" width="56" height="56">
                <?php endforeach; ?>
            </aside>
            <?php endif; ?>
        </header>

        <section class="phinit-table-wrap m365calc-compare-wrap m365calc-readonly-wrap azs-service-table-wrap" aria-label="<?php echo $esc($tr('Azure Services im Bereich ', 'Azure services in ') . $rowText($category, 'title')); ?>">
            <table class="phinit-table m365calc-compare-table m365calc-readonly-table azs-service-table">
                <caption class="m365calc-visually-hidden"><?php echo $esc($tr('Azure Services im Bereich ', 'Azure services in ') . $rowText($category, 'title')); ?></caption>
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
                    $imageUrl = CMS_M365Azure_Repository::public_image_url((string) ($service['image_url'] ?? ''));
                    $hasImage = $showImages && $imageUrl !== '';
                    $description = $serviceDescription($service);
                    $featureItems = $showFeatures ? $lines($rowText($service, 'features')) : [];
                    $useCaseItems = $showUseCases ? $lines($rowText($service, 'use_cases')) : [];
                    $serviceId = 'azs-service-' . $safeId((string) $service['slug'], (string) $service['id']);
                    $serviceTitle = $rowText($service, 'title');
                    $serviceSubtitle = $rowText($service, 'subtitle');
                    $serviceImageAlt = $rowText($service, 'image_alt', $serviceTitle);
                    ?>
                    <tr id="<?php echo $esc($serviceId); ?>">
                        <th scope="row">
                            <span class="m365calc-status m365calc-status--note azs-service-title">
                                <?php if ($hasImage): ?>
                                <img class="azs-service-icon" src="<?php echo $esc($imageUrl); ?>" alt="<?php echo $esc($serviceImageAlt); ?>" loading="lazy" width="96" height="54">
                                <?php endif; ?>
                                <span>
                                    <strong><?php echo $esc($serviceTitle); ?></strong>
                                    <?php if ($showSubtitles && $serviceSubtitle !== ''): ?>
                                    <small><?php echo $esc($serviceSubtitle); ?></small>
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

        <section class="azs-service-accordion" aria-label="<?php echo $esc($tr('Mobile Azure Services im Bereich ', 'Mobile Azure services in ') . $rowText($category, 'title')); ?>">
            <?php foreach ($categoryServices as $service): ?>
            <?php
            $description = $serviceDescription($service);
            $featureItems = $showFeatures ? $lines($rowText($service, 'features')) : [];
            $useCaseItems = $showUseCases ? $lines($rowText($service, 'use_cases')) : [];
            $imageUrl = CMS_M365Azure_Repository::public_image_url((string) ($service['image_url'] ?? ''));
            $hasImage = $showImages && $imageUrl !== '';
            $serviceTitle = $rowText($service, 'title');
            $serviceSubtitle = $rowText($service, 'subtitle');
            $serviceImageAlt = $rowText($service, 'image_alt', $serviceTitle);
            ?>
            <details class="phinit-card azs-service-accordion__item">
                <summary>
                    <?php if ($hasImage): ?>
                    <img class="azs-service-icon" src="<?php echo $esc($imageUrl); ?>" alt="<?php echo $esc($serviceImageAlt); ?>" loading="lazy" width="96" height="54">
                    <?php endif; ?>
                    <span>
                        <strong><?php echo $esc($serviceTitle); ?></strong>
                        <?php if ($showSubtitles && $serviceSubtitle !== ''): ?>
                        <small><?php echo $esc($serviceSubtitle); ?></small>
                        <?php endif; ?>
                    </span>
                </summary>
                <section class="azs-service-accordion__body" aria-label="<?php echo $esc($tr('Details zu ', 'Details for ') . $serviceTitle); ?>">
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

        <?php if ($showWellArchitectedChecklist && $wellArchitectedItems !== []): ?>
        <article class="phinit-note phinit-note--info azs-well-architected">
            <h3><?php echo $esc($wellArchitectedTitle . ': ' . $rowText($category, 'title')); ?></h3>
            <?php if ($wellArchitectedIntro !== ''): ?>
            <p><?php echo $esc($wellArchitectedIntro); ?></p>
            <?php endif; ?>
            <?php echo $listHtml($wellArchitectedItems, $emptyLabel); ?>
        </article>
        <?php endif; ?>
    </section>
    <?php endforeach; ?>

    <?php if ($showNotesSection && ($showInfoNote || $showSourcesCard)): ?>
    <section class="m365calc-result-grid" aria-label="<?php echo $esc($tr('Hinweise und Quellen', 'Notes and sources')); ?>">
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
