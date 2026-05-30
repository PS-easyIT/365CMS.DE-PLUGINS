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

$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$text = static function (array $settings, string $key, string $default = ''): string {
    $value = trim(strip_tags((string) ($settings[$key] ?? '')));
    return $value !== '' ? $value : $default;
};
$enabled = static fn(array $settings, string $key, string $default = '1'): bool => (string) ($settings[$key] ?? $default) === '1';
$lines = static function (string $value): array {
    $items = array_filter(array_map(static fn(string $line): string => trim(strip_tags($line)), preg_split('/\R/', $value) ?: []));
    return array_values($items);
};

$showHero = $enabled($settings, 'show_hero', '1');
$showToc = $enabled($settings, 'show_toc', '1');
$showCategoryIntro = $enabled($settings, 'show_category_intro', '1');
$showImages = $enabled($settings, 'show_service_images', '1');
$showLinks = $enabled($settings, 'show_service_links', '1');
$showFeatures = $enabled($settings, 'show_feature_lists', '1');
$showUseCases = $enabled($settings, 'show_use_cases', '1');
$imageRight = (string) ($settings['card_image_position'] ?? 'left') === 'right';
?>
<main class="phinit-plugin azs-page<?php echo $imageRight ? ' azs-page--image-right' : ''; ?>" aria-labelledby="azs-page-title">
    <?php if ($showHero): ?>
    <header class="azs-hero">
        <p class="phinit-overline azs-overline"><?php echo $esc($text($settings, 'page_overline', 'Azure Überblick')); ?></p>
        <h1 id="azs-page-title"><?php echo $esc($text($settings, 'page_title', 'Microsoft Azure Services')); ?></h1>
        <p class="azs-hero__intro"><?php echo $esc($text($settings, 'page_intro', 'Übersicht der wichtigsten Microsoft Azure Services.')); ?></p>
    </header>
    <?php endif; ?>

    <?php if ($showToc && $categories !== []): ?>
    <nav class="azs-toc phinit-card" aria-labelledby="azs-toc-title">
        <h2 id="azs-toc-title"><?php echo $esc($text($settings, 'toc_title', 'Inhaltsverzeichnis')); ?></h2>
        <ul class="azs-toc__list" role="list">
            <?php foreach ($categories as $category): ?>
            <?php if (empty($servicesByCategory[(int) $category['id']])) { continue; } ?>
            <li><a href="#azs-cat-<?php echo $esc((string) $category['slug']); ?>"><?php echo $esc((string) $category['title']); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <?php endif; ?>

    <?php foreach ($categories as $category): ?>
    <?php $categoryServices = $servicesByCategory[(int) $category['id']] ?? []; ?>
    <?php if ($categoryServices === []) { continue; } ?>
    <section class="azs-category" id="azs-cat-<?php echo $esc((string) $category['slug']); ?>" aria-labelledby="azs-cat-<?php echo $esc((string) $category['slug']); ?>-title">
        <header class="azs-category__header">
            <p class="phinit-overline azs-overline"><?php echo $esc((string) ($category['overline'] ?? 'Azure Kategorie')); ?></p>
            <h2 id="azs-cat-<?php echo $esc((string) $category['slug']); ?>-title"><?php echo $esc((string) $category['title']); ?></h2>
            <?php if ($showCategoryIntro && (string) ($category['intro'] ?? '') !== ''): ?>
            <p><?php echo $esc((string) $category['intro']); ?></p>
            <?php endif; ?>
        </header>

        <div class="azs-service-list">
            <?php foreach ($categoryServices as $service): ?>
            <?php
            $imageUrl = (string) ($service['image_url'] ?? '');
            $hasImage = $showImages && $imageUrl !== '';
            $featureItems = $lines((string) ($service['features'] ?? ''));
            $useCaseItems = $lines((string) ($service['use_cases'] ?? ''));
            ?>
            <article class="phinit-card azs-service-card<?php echo $hasImage ? '' : ' azs-service-card--text-only'; ?>" id="azs-service-<?php echo $esc((string) $service['slug']); ?>">
                <?php if ($hasImage): ?>
                <figure class="azs-service-card__media">
                    <img src="<?php echo $esc($imageUrl); ?>" alt="<?php echo $esc((string) ($service['image_alt'] ?: $service['title'])); ?>" loading="lazy" width="640" height="360">
                </figure>
                <?php endif; ?>
                <div class="azs-service-card__body">
                    <header class="azs-service-card__header">
                        <h3><?php echo $esc((string) $service['title']); ?></h3>
                        <?php if ((string) ($service['subtitle'] ?? '') !== ''): ?>
                        <p><?php echo $esc((string) $service['subtitle']); ?></p>
                        <?php endif; ?>
                    </header>

                    <?php if ((string) ($service['content'] ?? '') !== ''): ?>
                    <p class="azs-service-card__text"><?php echo nl2br($esc((string) $service['content'])); ?></p>
                    <?php elseif ((string) ($service['summary'] ?? '') !== ''): ?>
                    <p class="azs-service-card__text"><?php echo nl2br($esc((string) $service['summary'])); ?></p>
                    <?php endif; ?>

                    <?php if ($showFeatures && $featureItems !== []): ?>
                    <section class="azs-mini-section" aria-label="Features von <?php echo $esc((string) $service['title']); ?>">
                        <h4>Wichtige Features</h4>
                        <ul>
                            <?php foreach ($featureItems as $item): ?>
                            <li><?php echo $esc($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                    <?php endif; ?>

                    <?php if ($showUseCases && $useCaseItems !== []): ?>
                    <section class="azs-mini-section" aria-label="Einsatzbereiche von <?php echo $esc((string) $service['title']); ?>">
                        <h4>Typische Einsatzbereiche</h4>
                        <ul>
                            <?php foreach ($useCaseItems as $item): ?>
                            <li><?php echo $esc($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                    <?php endif; ?>

                    <?php if ($showLinks && ((string) ($service['docs_url'] ?? '') !== '' || (string) ($service['pricing_url'] ?? '') !== '')): ?>
                    <footer class="azs-service-card__links">
                        <?php if ((string) ($service['docs_url'] ?? '') !== ''): ?>
                        <a class="phinit-btn phinit-btn--secondary" href="<?php echo $esc((string) $service['docs_url']); ?>" target="_blank" rel="noopener noreferrer">Dokumentation</a>
                        <?php endif; ?>
                        <?php if ((string) ($service['pricing_url'] ?? '') !== ''): ?>
                        <a class="phinit-btn phinit-btn--link" href="<?php echo $esc((string) $service['pricing_url']); ?>" target="_blank" rel="noopener noreferrer">Preise <span aria-hidden="true">→</span></a>
                        <?php endif; ?>
                    </footer>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
</main>
