<?php
/**
 * Public Template: M365 Landing.
 *
 * @package CMS_M365Landing
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$publicLang = in_array((string) ($publicLang ?? 'de'), ['de', 'en'], true) ? (string) $publicLang : 'de';
$t = static function (string $de, string $en) use ($publicLang): string {
    return $publicLang === 'en' ? $en : $de;
};
$i18nValue = static function (string $key, string $fallback = '') use ($settings, $publicLang): string {
    if (function_exists('cms_plugin_public_i18n_value')) {
        return cms_plugin_public_i18n_value($settings, $key, $publicLang, $fallback);
    }

    $value = trim((string) ($settings[$key] ?? ''));
    return $value !== '' ? $value : $fallback;
};
$value = static function (string $key, string $default = '') use ($settings): string {
    $raw = trim((string) ($settings[$key] ?? ''));
    return $raw !== '' ? $raw : $default;
};
$enabled = static function (string $key, string $default = '1') use ($settings): bool {
    return (string) ($settings[$key] ?? $default) === '1';
};
$isExternal = static function (string $url): bool {
    return filter_var($url, FILTER_VALIDATE_URL) !== false && !str_starts_with($url, '/');
};
$resolveCardUrl = static function (array $card): string {
    $rawUrl = trim(strip_tags((string) ($card['url'] ?? '')));
    $url = CMS_M365Landing_Repository::public_url($rawUrl);
    if ($url !== '') {
        return $url;
    }

    if ($rawUrl !== '' && !str_contains($rawUrl, '://') && preg_match('#^[A-Za-z0-9/_?&=.%#+:;,@~-]+$#', $rawUrl) === 1) {
        return '/' . ltrim($rawUrl, '/');
    }

    $rawSlug = trim((string) ($card['slug'] ?? ''));
    if ($rawSlug === '') {
        return '';
    }

    return '/' . CMS_M365Landing_Repository::slug($rawSlug);
};
$buttonLabelDefault = $i18nValue('card_button_label_default', $t('Zum Bereich', 'Open section'));
$layoutVariant = in_array($value('layout_variant', 'balanced'), ['balanced', 'compact', 'spotlight'], true) ? $value('layout_variant', 'balanced') : 'balanced';
$heroImageUrl = CMS_M365Landing_Repository::public_image_url($value('hero_image_url'));
$heroImageAlt = $value('hero_image_alt', $value('page_title', 'Microsoft 365 Hub'));
$heroImageHeight = max(80, min(320, (int) $value('hero_image_height', '150')));
$imageDimensions = static function (string $url, int $fallbackWidth, int $fallbackHeight): array {
    if (function_exists('phinit_image_dimension_attributes')) {
        $attributes = phinit_image_dimension_attributes($url, $fallbackWidth, $fallbackHeight);
        if (is_string($attributes)
            && preg_match('/width="(\d+)"/', $attributes, $widthMatch) === 1
            && preg_match('/height="(\d+)"/', $attributes, $heightMatch) === 1
        ) {
            return [max(1, (int) $widthMatch[1]), max(1, (int) $heightMatch[1])];
        }
    }

    return [max(1, $fallbackWidth), max(1, $fallbackHeight)];
};
$latestPosts = is_array($latestPosts ?? null) ? $latestPosts : [];
$serviceHealthItems = is_array($serviceHealthItems ?? null) ? $serviceHealthItems : [];
$serviceHealthError = trim((string) ($serviceHealthError ?? ''));
$messageCenterItems = is_array($messageCenterItems ?? null) ? $messageCenterItems : [];
$messageCenterError = trim((string) ($messageCenterError ?? ''));
$isDomainLandingRequest = !empty($isDomainLandingRequest);
$showPostsJumpBadge = $isDomainLandingRequest && $latestPosts !== [];
$hasAnyCards = !empty($cardsBySection['matrix']) || !empty($cardsBySection['areas']) || !empty($cardsBySection['tools']);
$hasGraphPanels = $enabled('show_service_health_panel', '0') || $enabled('show_message_center_panel', '0');
$hasAnyContent = $hasAnyCards || $latestPosts !== [] || $hasGraphPanels;

$renderCard = static function (array $card, string $cardLayout = 'media', bool $hideTitle = false) use ($esc, $buttonLabelDefault, $isExternal, $resolveCardUrl, $imageDimensions, $t): void {
    $url = $resolveCardUrl($card);
    $imageUrl = CMS_M365Landing_Repository::public_image_url((string) ($card['image_url'] ?? ''));
    $icon = trim((string) ($card['icon'] ?? ''));
    $title = trim((string) ($card['title'] ?? ''));
    $imageAlt = trim((string) ($card['image_alt'] ?? ''));
    $subtitle = trim((string) ($card['subtitle'] ?? ''));
    $description = trim((string) ($card['description'] ?? ''));
    $buttonLabel = trim((string) ($card['button_label'] ?? ''));
    $buttonLabel = $buttonLabel !== '' ? $buttonLabel : $buttonLabelDefault;
    $featuredClass = (int) ($card['is_featured'] ?? 0) === 1 ? ' m365landing-card--featured' : '';
    $clickableClass = $url !== '' ? ' m365landing-card--clickable' : '';
    $cardLayout = in_array($cardLayout, ['media', 'stacked'], true) ? $cardLayout : 'media';
    $layoutClass = $cardLayout === 'stacked' ? ' m365landing-card--stacked-layout' : ' m365landing-card--media-layout';
    $titleHiddenClass = $hideTitle ? ' m365landing-card--title-hidden' : '';
    $ariaLabelTitle = $title !== '' ? $title : ($subtitle !== '' ? $subtitle : $t('M365 Bereich', 'M365 section'));
    $imageAltLabel = $imageAlt !== '' ? $imageAlt : $ariaLabelTitle;
    $showTitleWrap = !$hideTitle && ($title !== '' || ($cardLayout === 'stacked' && $subtitle !== ''));
    $tagName = $url !== '' ? 'a' : 'article';
    [$imageWidth, $imageHeight] = $imageDimensions($imageUrl, 640, 205);
    ?>
    <<?php echo $tagName; ?> class="m365landing-card<?php echo $featuredClass . $clickableClass . $layoutClass . $titleHiddenClass; ?>"<?php echo $url !== '' ? ' href="' . $esc($url) . '" aria-label="' . $esc($buttonLabel . ': ' . $ariaLabelTitle) . '"' . ($isExternal($url) ? ' target="_blank" rel="noopener noreferrer"' : '') : ''; ?>>
        <?php if (($cardLayout === 'media' || $hideTitle) && $subtitle !== ''): ?>
        <p class="m365landing-card__subtitle m365landing-card__subtitle--topline"><?php echo $esc($subtitle); ?></p>
        <?php endif; ?>
        <div class="m365landing-card__head">
            <div class="m365landing-card__visual" aria-hidden="<?php echo $imageUrl !== '' ? 'false' : 'true'; ?>">
                <?php if ($imageUrl !== ''): ?>
                <img src="<?php echo $esc($imageUrl); ?>" alt="<?php echo $esc($imageAltLabel); ?>" loading="lazy" decoding="async" width="<?php echo (int) $imageWidth; ?>" height="<?php echo (int) $imageHeight; ?>">
                <?php else: ?>
                <span class="m365landing-card__icon"><?php echo $esc($icon !== '' ? $icon : '▦'); ?></span>
                <?php endif; ?>
            </div>
            <?php if ($showTitleWrap): ?>
            <div class="m365landing-card__titlewrap">
                <?php if ($cardLayout === 'stacked' && $subtitle !== ''): ?>
                <p class="m365landing-card__subtitle m365landing-card__subtitle--topline"><?php echo $esc($subtitle); ?></p>
                <?php endif; ?>
                <?php if ($title !== ''): ?>
                <h3><?php echo $esc($title); ?></h3>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="m365landing-card__body">
            <?php if ($description !== ''): ?>
            <p class="m365landing-card__description"><?php echo nl2br($esc($description)); ?></p>
            <?php endif; ?>
        </div>
        <?php if ($url !== ''): ?>
        <div class="m365landing-card__footer">
            <span class="m365landing-card__cta"><?php echo $esc($buttonLabel); ?> <span aria-hidden="true">-&gt;</span></span>
        </div>
        <?php endif; ?>
    </<?php echo $tagName; ?>>
    <?php
};

$renderSection = static function (string $sectionKey, string $sectionClass, array $cards) use ($esc, $value, $enabled, $renderCard, $t): void {
    if ($cards === []) {
        return;
    }
    $sectionLabels = [
        'matrix' => $t('M365 Matrixen', 'M365 matrices'),
        'areas' => $t('Weitere M365 Bereiche', 'Additional M365 areas'),
        'tools' => $t('M365 Tools Sammlung', 'M365 tools collection'),
    ];
    $sectionOverline = $value($sectionKey . '_section_overline');
    $sectionTitle = $value($sectionKey . '_section_title');
    $sectionIntro = $value($sectionKey . '_section_intro');
    $sectionAria = $sectionTitle !== ''
        ? ' aria-labelledby="m365landing-' . $esc($sectionKey) . '-title"'
        : ' aria-label="' . $esc($sectionLabels[$sectionKey] ?? $t('M365 Landing Abschnitt', 'M365 landing section')) . '"';
    ?>
    <section class="m365landing-section <?php echo $esc($sectionClass); ?>"<?php echo $sectionAria; ?>>
        <?php if ($sectionOverline !== '' || $sectionTitle !== '' || $sectionIntro !== ''): ?>
        <div class="m365landing-section__head">
            <?php if ($sectionOverline !== ''): ?>
            <p class="phinit-overline m365landing-overline"><?php echo $esc($sectionOverline); ?></p>
            <?php endif; ?>
            <?php if ($sectionTitle !== ''): ?>
            <h2 id="m365landing-<?php echo $esc($sectionKey); ?>-title"><?php echo $esc($sectionTitle); ?></h2>
            <?php endif; ?>
            <?php if ($sectionIntro !== ''): ?>
            <p class="m365landing-section__intro"><?php echo $esc($sectionIntro); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php $cardCount = count($cards); ?>
        <?php $gridColumns = max(1, min(4, $cardCount)); ?>
        <?php $sectionCardLayout = in_array($value($sectionKey . '_card_layout', 'media'), ['media', 'stacked'], true) ? $value($sectionKey . '_card_layout', 'media') : 'media'; ?>
        <?php $sectionHideTitle = $enabled($sectionKey . '_card_hide_title', '0'); ?>
        <div class="m365landing-grid m365landing-grid--cols-<?php echo (int) $gridColumns; ?>" role="list" data-card-count="<?php echo (int) $cardCount; ?>">
            <?php foreach ($cards as $card): ?>
                <div role="listitem"><?php $renderCard($card, $sectionCardLayout, $sectionHideTitle); ?></div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
};

$renderPostCard = static function (array $post) use ($esc, $publicLang, $t): void {
    $currentLocale = $publicLang;
    $title = trim((string) ($post['title'] ?? ''));
    $title = $title !== '' ? $title : $t('Ohne Titel', 'Untitled');
    $url = CMS_M365Landing_Repository::main_site_url(CMS_M365Landing_Repository::public_url((string) ($post['permalink'] ?? '')));
    $url = $url !== '' ? $url : CMS_M365Landing_Repository::main_site_url('/blog/' . CMS_M365Landing_Repository::slug((string) ($post['slug'] ?? $title)));
    $categoryName = trim((string) ($post['category_name'] ?? ''));
    $categorySlug = trim((string) ($post['category_slug'] ?? ''));
    if ($categorySlug === '' && $categoryName !== '') {
        $categorySlug = function_exists('phinit_display_text') ? (string) phinit_display_text($categoryName) : CMS_M365Landing_Repository::slug($categoryName);
    }
    $categoryUrl = $categorySlug !== ''
        ? (function_exists('cms_get_archive_url')
            ? (string) cms_get_archive_url('category', $categorySlug, $currentLocale)
            : ($publicLang === 'en' ? '/en/category/' . rawurlencode($categorySlug) : '/kategorie/' . rawurlencode($categorySlug)))
        : '';
    $categoryUrl = $categoryUrl !== '' ? CMS_M365Landing_Repository::main_site_url($categoryUrl) : '';
    $dateRaw = trim((string) ($post['published_at'] ?? ($post['created_at'] ?? '')));
    $timestamp = $dateRaw !== '' ? strtotime($dateRaw) : false;
    $dateLabel = $dateRaw !== '' && function_exists('phinit_format_date')
        ? (string) phinit_format_date($dateRaw, 'long', $currentLocale)
        : ($timestamp !== false ? date('d.m.Y', $timestamp) : '');
    $dateIso = $timestamp !== false ? date('Y-m-d', $timestamp) : '';
    $excerpt = trim((string) ($post['excerpt_plain'] ?? ''));
    $excerpt = $excerpt !== '' ? $excerpt : CMS_M365Landing_Repository::excerpt_plain_text((string) ($post['excerpt'] ?? ''));
    $excerpt = $excerpt !== '' ? $excerpt : CMS_M365Landing_Repository::excerpt_plain_text((string) ($post['content'] ?? ''));
    if (function_exists('mb_strimwidth')) {
        $excerpt = mb_strimwidth($excerpt, 0, 180, '…', 'UTF-8');
    } else {
        $excerpt = strlen($excerpt) > 180 ? substr($excerpt, 0, 177) . '…' : $excerpt;
    }
    $readTime = (int) ($post['read_time'] ?? 0);
    $readTimeLabel = $readTime > 0
        ? (function_exists('phinit_t') ? (string) phinit_t('read_time_short', ['minutes' => $readTime], $currentLocale) : $readTime . ' Min.')
        : '';
    $readTimeAria = $readTime > 0
        ? (function_exists('phinit_t') ? (string) phinit_t('read_time_aria', ['minutes' => $readTime], $currentLocale) : $readTime . ' Minuten Lesezeit')
        : '';
    $continueLabel = function_exists('phinit_t') ? (string) phinit_t('continue_reading', [], $currentLocale) : $t('Weiter lesen →', 'Continue reading →');
    ?>
    <article class="post-card post-card--text-only" role="listitem">
        <div class="post-card-body">
            <h3 class="post-card-title"><a href="<?php echo $esc($url); ?>"><?php echo $esc($title); ?></a></h3>
            <?php if (($dateLabel !== '' && $dateIso !== '') || $readTimeLabel !== ''): ?>
            <div class="post-card-top-meta">
                <?php if ($dateLabel !== '' && $dateIso !== ''): ?>
                <time class="post-card-top-meta__date" datetime="<?php echo $esc($dateIso); ?>"><?php echo $esc($dateLabel); ?></time>
                <?php endif; ?>
                <?php if ($readTimeLabel !== ''): ?>
                <span class="post-card-top-meta__read" aria-label="<?php echo $esc($readTimeAria); ?>"><?php echo $esc($readTimeLabel); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if ($excerpt !== ''): ?>
            <p class="post-card-excerpt"><?php echo $esc($excerpt); ?></p>
            <?php endif; ?>
            <div class="post-card-meta">
                <div class="post-card-meta__left">
                    <?php if ($categoryName !== '' && $categoryUrl !== ''): ?>
                    <a class="cat" href="<?php echo $esc($categoryUrl); ?>"><?php echo $esc($categoryName); ?></a>
                    <?php elseif ($categoryName !== ''): ?>
                    <span class="cat"><?php echo $esc($categoryName); ?></span>
                    <?php endif; ?>
                </div>
                <a class="post-card-meta__more" href="<?php echo $esc($url); ?>" aria-label="<?php echo $esc($title); ?>">
                    <span class="post-card-meta__more-label post-card-meta__more-label--desktop"><?php echo $esc($continueLabel); ?></span>
                    <span class="post-card-meta__more-label post-card-meta__more-label--mobile"><?php echo $esc($continueLabel); ?></span>
                </a>
            </div>
        </div>
    </article>
    <?php
};
?>
<main class="phinit-plugin m365landing-page m365landing-layout--<?php echo $esc($layoutVariant); ?>" id="m365landing-page">
    <?php if ($enabled('show_hero')): ?>
    <header class="m365landing-hero" aria-labelledby="m365landing-title">
        <?php if ($showPostsJumpBadge): ?>
        <a class="m365landing-posts-jump-badge" href="#m365landing-latest-posts"><?php echo $esc($t('zu den letzten Beiträgen', 'jump to latest posts')); ?></a>
        <?php endif; ?>
        <div class="m365landing-hero__inner<?php echo $heroImageUrl !== '' ? ' m365landing-hero__inner--with-image' : ''; ?>">
            <?php if ($heroImageUrl !== ''): ?>
            <?php [$heroImageWidth, $heroImageHeightAttr] = $imageDimensions($heroImageUrl, 420, $heroImageHeight); ?>
            <figure class="m365landing-hero__image">
                <img src="<?php echo $esc($heroImageUrl); ?>" alt="<?php echo $esc($heroImageAlt); ?>" loading="eager" fetchpriority="high" decoding="async" width="<?php echo (int) $heroImageWidth; ?>" height="<?php echo (int) $heroImageHeightAttr; ?>">
            </figure>
            <?php endif; ?>
            <div class="m365landing-hero__content">
                <?php if ($value('page_overline') !== ''): ?>
                <p class="phinit-overline m365landing-overline"><?php echo $esc($value('page_overline')); ?></p>
                <?php endif; ?>
                <h1 id="m365landing-title"><?php echo $esc($value('page_title', 'Microsoft 365 Hub')); ?></h1>
                <?php if ($value('page_intro') !== ''): ?>
                <p class="m365landing-hero__intro"><?php echo $esc($value('page_intro')); ?></p>
                <?php endif; ?>
                <?php if ($enabled('show_hero_actions')): ?>
                <nav class="m365landing-hero__actions" aria-label="<?php echo $esc($t('M365 Landing Schnellzugriff', 'M365 landing quick access')); ?>">
                    <?php $primaryUrl = CMS_M365Landing_Repository::public_url($value('hero_primary_button_url', '/m365-lizenzmatrix')); ?>
                    <?php if ($primaryUrl !== ''): ?>
                    <a class="phinit-btn phinit-btn--primary" href="<?php echo $esc($primaryUrl); ?>"<?php echo $isExternal($primaryUrl) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo $esc($value('hero_primary_button_text', 'M365 Lizenzmatrix öffnen')); ?></a>
                    <?php endif; ?>
                    <?php $secondaryUrl = CMS_M365Landing_Repository::public_url($value('hero_secondary_button_url', '/m365-addon-matrix')); ?>
                    <?php if ($secondaryUrl !== ''): ?>
                    <a class="phinit-btn phinit-btn--secondary" href="<?php echo $esc($secondaryUrl); ?>"<?php echo $isExternal($secondaryUrl) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo $esc($value('hero_secondary_button_text', 'Add-on-Matrix öffnen')); ?></a>
                    <?php endif; ?>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <?php endif; ?>

    <?php if ($enabled('show_matrix_section')): ?>
        <?php $renderSection('matrix', 'm365landing-section--matrix', $cardsBySection['matrix'] ?? []); ?>
    <?php endif; ?>

    <?php if ($enabled('show_separator')): ?>
    <div class="m365landing-soft-separator" role="presentation">
        <span><?php echo $esc($i18nValue('separator_label', $t('Weitere Microsoft-365-Bereiche', 'Additional Microsoft 365 areas'))); ?></span>
    </div>
    <?php endif; ?>

    <?php if ($enabled('show_areas_section')): ?>
        <?php $renderSection('areas', 'm365landing-section--areas', $cardsBySection['areas'] ?? []); ?>
    <?php endif; ?>

    <?php if ($enabled('show_tools_section')): ?>
        <?php $renderSection('tools', 'm365landing-section--tools', $cardsBySection['tools'] ?? []); ?>
    <?php endif; ?>

    <?php if ($enabled('show_service_health_panel', '0')): ?>
        <?php
        $serviceOverline = $i18nValue('service_health_section_overline', $t('Live-Status', 'Live status'));
        $serviceTitle = $i18nValue('service_health_section_title', $t('Tenant Service Health', 'Tenant service health'));
        $serviceIntro = $i18nValue('service_health_section_intro', $t('Aktuelle Vorfälle und Advisories aus Microsoft 365 Services.', 'Current incidents and advisories from Microsoft 365 services.'));
        $serviceEmpty = $i18nValue('service_health_empty_text', $t('Der Service-Health-Feed ist aktuell nicht verfügbar.', 'The service health feed is currently unavailable.'));
        ?>
    <section class="m365landing-section m365landing-section--service-health" aria-labelledby="m365landing-service-health-title">
        <?php if ($serviceOverline !== '' || $serviceTitle !== '' || $serviceIntro !== ''): ?>
        <div class="m365landing-section__head">
            <?php if ($serviceOverline !== ''): ?>
            <p class="phinit-overline m365landing-overline"><?php echo $esc($serviceOverline); ?></p>
            <?php endif; ?>
            <?php if ($serviceTitle !== ''): ?>
            <h2 id="m365landing-service-health-title"><?php echo $esc($serviceTitle); ?></h2>
            <?php endif; ?>
            <?php if ($serviceIntro !== ''): ?>
            <p class="m365landing-section__intro"><?php echo $esc($serviceIntro); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ($serviceHealthItems !== []): ?>
        <div class="m365landing-status-list" role="list">
            <?php foreach ($serviceHealthItems as $item): ?>
                <?php
                $itemTitle = trim((string) ($item['title'] ?? ''));
                $itemTitle = $itemTitle !== '' ? $itemTitle : $t('Ohne Titel', 'Untitled');
                $itemService = trim((string) ($item['service'] ?? ''));
                $itemState = trim((string) ($item['status'] ?? ''));
                $itemClass = trim((string) ($item['classification'] ?? ''));
                ?>
            <article class="m365landing-status-card" role="listitem">
                <h3><?php echo $esc($itemTitle); ?></h3>
                <p class="m365landing-status-card__meta">
                    <?php if ($itemService !== ''): ?><span><?php echo $esc($itemService); ?></span><?php endif; ?>
                    <?php if ($itemState !== ''): ?><span><?php echo $esc($itemState); ?></span><?php endif; ?>
                    <?php if ($itemClass !== ''): ?><span><?php echo $esc($itemClass); ?></span><?php endif; ?>
                </p>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="m365landing-status-empty"><?php echo $esc($serviceEmpty); ?></p>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($enabled('show_message_center_panel', '0')): ?>
        <?php
        $messageOverline = $i18nValue('message_center_section_overline', $t('Änderungsankündigungen', 'Change announcements'));
        $messageTitle = $i18nValue('message_center_section_title', $t('Message Center Highlights', 'Message center highlights'));
        $messageIntro = $i18nValue('message_center_section_intro', $t('Wichtige angekündigte Änderungen mit Relevanz für Betrieb und Governance.', 'Important upcoming Microsoft 365 changes for operations and governance.'));
        $messageEmpty = $i18nValue('message_center_empty_text', $t('Der Message-Center-Feed ist aktuell nicht verfügbar.', 'The message center feed is currently unavailable.'));
        ?>
    <section class="m365landing-section m365landing-section--message-center" aria-labelledby="m365landing-message-center-title">
        <?php if ($messageOverline !== '' || $messageTitle !== '' || $messageIntro !== ''): ?>
        <div class="m365landing-section__head">
            <?php if ($messageOverline !== ''): ?>
            <p class="phinit-overline m365landing-overline"><?php echo $esc($messageOverline); ?></p>
            <?php endif; ?>
            <?php if ($messageTitle !== ''): ?>
            <h2 id="m365landing-message-center-title"><?php echo $esc($messageTitle); ?></h2>
            <?php endif; ?>
            <?php if ($messageIntro !== ''): ?>
            <p class="m365landing-section__intro"><?php echo $esc($messageIntro); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ($messageCenterItems !== []): ?>
        <div class="m365landing-status-list" role="list">
            <?php foreach ($messageCenterItems as $item): ?>
                <?php
                $itemTitle = trim((string) ($item['title'] ?? ''));
                $itemTitle = $itemTitle !== '' ? $itemTitle : $t('Ohne Titel', 'Untitled');
                $itemCategory = trim((string) ($item['category'] ?? ''));
                $itemServices = [];
                foreach ((array) ($item['services'] ?? []) as $service) {
                    $service = trim((string) $service);
                    if ($service !== '') {
                        $itemServices[] = $service;
                    }
                }
                ?>
            <article class="m365landing-status-card" role="listitem">
                <h3><?php echo $esc($itemTitle); ?></h3>
                <p class="m365landing-status-card__meta">
                    <?php if ($itemCategory !== ''): ?><span><?php echo $esc($itemCategory); ?></span><?php endif; ?>
                    <?php if ($itemServices !== []): ?><span><?php echo $esc(implode(' · ', $itemServices)); ?></span><?php endif; ?>
                </p>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="m365landing-status-empty"><?php echo $esc($messageEmpty); ?></p>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($latestPosts !== []): ?>
        <?php $postsOverline = $value('posts_section_overline'); ?>
        <?php $postsTitle = $value('posts_section_title'); ?>
        <?php $postsIntro = $value('posts_section_intro'); ?>
    <section id="m365landing-latest-posts" class="m365landing-section m365landing-section--posts home-section--grid"<?php echo $postsTitle !== '' ? ' aria-labelledby="m365landing-posts-title"' : ' aria-label="' . $esc($t('Aktuelle Beiträge', 'Latest posts')) . '"'; ?>>
        <?php if ($postsOverline !== '' || $postsTitle !== '' || $postsIntro !== ''): ?>
        <div class="m365landing-section__head">
            <?php if ($postsOverline !== ''): ?>
            <p class="phinit-overline m365landing-overline"><?php echo $esc($postsOverline); ?></p>
            <?php endif; ?>
            <?php if ($postsTitle !== ''): ?>
            <h2 id="m365landing-posts-title"><?php echo $esc($postsTitle); ?></h2>
            <?php endif; ?>
            <?php if ($postsIntro !== ''): ?>
            <p class="m365landing-section__intro"><?php echo $esc($postsIntro); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="posts-grid posts-grid--cols-3" role="list">
            <?php foreach ($latestPosts as $post): ?>
                <?php $renderPostCard($post); ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!$hasAnyContent): ?>
    <section class="m365landing-empty phinit-card">
        <h2><?php echo $esc($i18nValue('empty_state_title', $t('Noch keine aktiven Karten vorhanden', 'No active cards yet'))); ?></h2>
        <p><?php echo $esc($i18nValue('empty_state_text', $t('Aktiviere oder erstelle Karten im Adminbereich, damit die Landingpage Inhalte anzeigen kann.', 'Activate or create cards in the admin area so the landing page can show content.'))); ?></p>
    </section>
    <?php endif; ?>
</main>
