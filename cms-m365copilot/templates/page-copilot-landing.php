<?php
/**
 * CMS M365 Copilot – public landing template.
 *
 * @package CMS_M365Copilot
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$siteUrl = defined('SITE_URL') ? (string) SITE_URL : '';
$safeUrl = static function (string $url): string {
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if ((str_starts_with($url, '/') || str_starts_with($url, '#')) && !str_starts_with($url, '//')) {
        return $url;
    }
    return filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : '';
};
$publicHref = static function (string $url) use ($safeUrl, $siteUrl): string {
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    if (function_exists('phinit_safe_public_url')) {
        $safe = (string) phinit_safe_public_url($url, $siteUrl, ['http', 'https']);
        if ($safe !== '') {
            return $safe;
        }

        if ($siteUrl !== '' && str_starts_with($url, '/')) {
            $safeAbsolute = (string) phinit_safe_public_url(rtrim($siteUrl, '/') . $url, $siteUrl, ['http', 'https']);
            if ($safeAbsolute !== '') {
                return $safeAbsolute;
            }
        }
    }

    return $safeUrl($url);
};
$imgUrl = static function (string $url) use ($siteUrl): string {
    if ($url === '') {
        return '';
    }

    if (function_exists('phinit_normalize_public_media_url')) {
        $normalized = (string) phinit_normalize_public_media_url($url, false, $siteUrl);
        if ($normalized !== '') {
            return $normalized;
        }
    }

    $fallback = CMS_M365Copilot_Settings::public_image_url($url);
    return $fallback !== '' ? $fallback : '';
};
$int = static fn(string $key, int $fallback): int => max(0, (int) ($settings[$key] ?? (string) $fallback));
$headerLayout = in_array((string) ($settings['header_layout'] ?? '1'), ['1', '2', '3'], true) ? (string) ($settings['header_layout'] ?? '1') : '1';
$serviceLayout = in_array((string) ($settings['service_layout'] ?? '1'), ['1', '2', '3'], true) ? (string) ($settings['service_layout'] ?? '1') : '1';
$serviceEnabled = (string) ($settings['service_enabled'] ?? '1') === '1';
$postsEnabled = (string) ($settings['posts_show'] ?? '1') === '1';
$serviceCardsEnabled = (string) ($settings['service_cards_show'] ?? '1') === '1';
$cards = [1, 2, 3];
$serviceInfoCards = [1, 2, 3];
$cardsAriaLabel = trim((string) ($settings['cards_section_aria_label'] ?? 'Copilot Bereiche'));
$serviceSectionLabel = trim((string) ($settings['service_section_label'] ?? '365CMS Service'));
$serviceAriaLabel = trim((string) ($settings['service_section_aria_label'] ?? 'Dienstleistungsband'));
$serviceCardsAriaLabel = trim((string) ($settings['service_cards_aria_label'] ?? 'Dienstleistungsinformationen'));
$serviceCardsSectionLabel = trim((string) ($settings['service_cards_section_label'] ?? 'Dienstleistungen'));
$serviceCardsTitle = trim((string) ($settings['service_cards_title'] ?? 'Unsere Dienstleistungsbausteine'));
$serviceCardsIntro = trim((string) ($settings['service_cards_intro'] ?? ''));
$postsSectionLabel = trim((string) ($settings['posts_section_label'] ?? 'Aktuelle Beiträge'));
$postsReadAriaPrefix = trim((string) ($settings['posts_read_aria_prefix'] ?? 'Beitrag lesen:'));

$cssVars = sprintf(
    '--m365cp-max-width:%dpx;--m365cp-pad-l:%dpx;--m365cp-pad-r:%dpx;--m365cp-pad-t:%dpx;--m365cp-pad-b:%dpx;--m365cp-gap:%dpx;--m365cp-header-offset:%dpx;--m365cp-footer-offset:%dpx;',
    max(720, min(1800, $int('layout_content_max_width', 1200))),
    min(96, $int('layout_padding_left', 24)),
    min(96, $int('layout_padding_right', 24)),
    min(160, $int('layout_padding_top', 32)),
    min(160, $int('layout_padding_bottom', 48)),
    min(160, $int('layout_section_gap', 36)),
    min(160, $int('layout_header_offset', 0)),
    min(160, $int('layout_footer_offset', 0))
);

$cardLinkLabel = trim((string) ($settings['cards_link_label'] ?? 'Mehr erfahren →'));
$postReadMoreLabel = trim((string) ($settings['posts_readmore_label'] ?? 'Weiter lesen →'));
$postsEmptyTitle = trim((string) ($settings['posts_empty_title'] ?? 'Keine Beiträge gefunden'));
$postsEmptyText = trim((string) ($settings['posts_empty_text'] ?? 'Bitte Kategorie oder Beitragsanzahl in den Einstellungen prüfen.'));
$headerImage = $imgUrl((string) ($settings['header_image_url'] ?? ''));
$headerCta = $publicHref((string) ($settings['header_cta_url'] ?? ''));
$serviceLogo = $imgUrl((string) ($settings['service_logo_url'] ?? ''));
$serviceCta = $publicHref((string) ($settings['service_cta_url'] ?? ''));
?>

<?php if (!empty($publicCssUrl)): ?>
<link rel="stylesheet" href="<?php echo $esc((string) $publicCssUrl); ?>">
<?php endif; ?>

<style>
.m365cp {
    <?php echo $esc($cssVars); ?>
}
</style>

<main class="m365cp phinit-plugin<?php echo $serviceEnabled ? ' m365cp--service-enabled' : ''; ?>">
    <div class="m365cp__container">
        <section class="landing-hero m365cp-header m365cp-header--layout-<?php echo $esc($headerLayout); ?>" aria-labelledby="m365cp-hero-title">
            <div class="container landing-hero__inner m365cp-header__inner">
                <?php if ($headerImage !== ''): ?>
            <div class="landing-hero__media m365cp-header__media">
                <img src="<?php echo $esc($headerImage); ?>" alt="<?php echo $esc((string) ($settings['header_image_alt'] ?? '')); ?>" class="landing-hero__img" loading="eager" decoding="async" fetchpriority="high" width="1200" height="675">
            </div>
            <?php endif; ?>
            <div class="landing-hero__content m365cp-header__content">
                <h1 class="landing-hero__title" id="m365cp-hero-title"><?php echo $esc((string) ($settings['header_title'] ?? '')); ?></h1>
                <p class="landing-hero__sub"><?php echo $esc((string) ($settings['header_intro'] ?? '')); ?></p>
                <?php if ($headerCta !== '' && trim((string) ($settings['header_cta_label'] ?? '')) !== ''): ?>
                <div class="landing-hero__ctas">
                    <a class="btn btn--landing-primary" href="<?php echo $esc($headerCta); ?>"><?php echo $esc((string) ($settings['header_cta_label'] ?? '')); ?></a>
                </div>
                <?php endif; ?>
            </div>
            </div>
        </section>

        <?php if ($serviceEnabled): ?>
            <section class="m365cp-service m365cp-service--layout-<?php echo $esc($serviceLayout); ?>" aria-label="<?php echo $esc($serviceAriaLabel !== '' ? $serviceAriaLabel : 'Dienstleistungsband'); ?>">
            <div class="m365cp-service__head section-header">
                    <span class="section-label"><?php echo $esc($serviceSectionLabel !== '' ? $serviceSectionLabel : '365CMS Service'); ?></span>
            </div>
            <div class="m365cp-service__inner">
            <?php if ($serviceLogo !== ''): ?>
            <div class="m365cp-service__logo">
                <img src="<?php echo $esc($serviceLogo); ?>" alt="<?php echo $esc((string) ($settings['service_logo_alt'] ?? '')); ?>" loading="eager" decoding="async" fetchpriority="high" width="240" height="64">
            </div>
            <?php endif; ?>
            <p class="m365cp-service__text"><?php echo $esc((string) ($settings['service_text'] ?? '')); ?></p>
            <?php if ($serviceCta !== '' && trim((string) ($settings['service_cta_label'] ?? '')) !== ''): ?>
            <a class="btn btn--landing-outline m365cp-service__cta" href="<?php echo $esc($serviceCta); ?>"><?php echo $esc((string) ($settings['service_cta_label'] ?? '')); ?></a>
            <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <section class="landing-features m365cp-cards" aria-label="<?php echo $esc($cardsAriaLabel !== '' ? $cardsAriaLabel : 'Copilot Bereiche'); ?>">
            <div class="container">
            <div class="landing-features__grid m365cp-cards__grid">
                <?php foreach ($cards as $i): ?>
                <?php $u = $publicHref((string) ($settings['card_' . $i . '_url'] ?? '')); ?>
                <article class="landing-feature-card m365cp-card" data-card-index="<?php echo (int) $i; ?>">
                    <?php $ci = $imgUrl((string) ($settings['card_' . $i . '_image_url'] ?? '')); ?>
                    <header class="landing-feature-card__header m365cp-card__header">
                        <span class="landing-feature-card__icon m365cp-card__icon" aria-hidden="true">
                            <?php if ($ci !== ''): ?>
                            <img src="<?php echo $esc($ci); ?>" alt="" loading="eager" decoding="async" width="24" height="24">
                            <?php elseif ($i === 1): ?>
                            🧩
                            <?php elseif ($i === 2): ?>
                            ℹ️
                            <?php else: ?>
                            🛡️
                            <?php endif; ?>
                        </span>
                        <h2 class="landing-feature-card__title m365cp-card__title"><?php echo $esc((string) ($settings['card_' . $i . '_title'] ?? '')); ?></h2>
                    </header>
                    <div class="landing-feature-card__body m365cp-card__body">
                        <p class="landing-feature-card__text"><?php echo $esc((string) ($settings['card_' . $i . '_text'] ?? '')); ?></p>
                    </div>
                    <?php if ($u !== ''): ?>
                    <a class="btn btn-primary landing-feature-card__link m365cp-card__link" href="<?php echo $esc($u); ?>"><?php echo $esc($cardLinkLabel !== '' ? $cardLinkLabel : 'Mehr erfahren →'); ?></a>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>
            </div>
        </section>

        <?php if ($serviceCardsEnabled): ?>
        <section class="m365cp-service-cards" aria-label="<?php echo $esc($serviceCardsAriaLabel !== '' ? $serviceCardsAriaLabel : 'Dienstleistungsinformationen'); ?>">
            <header class="m365cp-service-cards__head section-header">
                <span class="section-label"><?php echo $esc($serviceCardsSectionLabel !== '' ? $serviceCardsSectionLabel : 'Dienstleistungen'); ?></span>
            </header>
            <div class="m365cp-service-cards__intro">
                <?php if ($serviceCardsTitle !== ''): ?><h2><?php echo $esc($serviceCardsTitle); ?></h2><?php endif; ?>
                <?php if ($serviceCardsIntro !== ''): ?><p><?php echo $esc($serviceCardsIntro); ?></p><?php endif; ?>
            </div>
            <div class="m365cp-service-cards__grid" role="list">
                <?php foreach ($serviceInfoCards as $i): ?>
                <?php
                $infoTitle = trim((string) ($settings['service_info_' . $i . '_title'] ?? ''));
                $infoText = trim((string) ($settings['service_info_' . $i . '_text'] ?? ''));
                $infoUrl = $publicHref((string) ($settings['service_info_' . $i . '_url'] ?? ''));
                if ($infoTitle === '' && $infoText === '') {
                    continue;
                }
                ?>
                <?php if ($infoUrl !== ''): ?>
                <a class="m365cp-service-info-card m365cp-service-info-card--link" role="listitem" href="<?php echo $esc($infoUrl); ?>">
                    <span class="m365cp-service-info-card__icon" aria-hidden="true"><?php echo $i === 1 ? '🧭' : ($i === 2 ? '🚀' : '🛡️'); ?></span>
                    <?php if ($infoTitle !== ''): ?><h3><?php echo $esc($infoTitle); ?></h3><?php endif; ?>
                    <?php if ($infoText !== ''): ?><p><?php echo $esc($infoText); ?></p><?php endif; ?>
                    <span class="m365cp-service-info-card__more" aria-hidden="true">Mehr erfahren →</span>
                </a>
                <?php else: ?>
                <article class="m365cp-service-info-card m365cp-service-info-card--static" role="listitem">
                    <span class="m365cp-service-info-card__icon" aria-hidden="true"><?php echo $i === 1 ? '🧭' : ($i === 2 ? '🚀' : '🛡️'); ?></span>
                    <?php if ($infoTitle !== ''): ?><h3><?php echo $esc($infoTitle); ?></h3><?php endif; ?>
                    <?php if ($infoText !== ''): ?><p><?php echo $esc($infoText); ?></p><?php endif; ?>
                </article>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($postsEnabled): ?>
        <section class="m365cp-posts home-section home-section--grid">
            <header class="m365cp-posts__head section-header">
                    <span class="section-label"><?php echo $esc($postsSectionLabel !== '' ? $postsSectionLabel : 'Aktuelle Beiträge'); ?></span>
            </header>
            <div class="m365cp-posts__intro">
                <h2><?php echo $esc((string) ($settings['posts_title'] ?? '')); ?></h2>
                <p><?php echo $esc((string) ($settings['posts_intro'] ?? '')); ?></p>
            </div>
            <div class="posts-grid m365cp-posts__grid" role="list">
                <?php foreach ($posts as $p): ?>
                <?php
                $postUrl = '/blog/' . rawurlencode((string) ($p['slug'] ?? ''));
                if (function_exists('phinit_build_post_url')) {
                    $postUrl = (string) phinit_build_post_url($p);
                }
                $postHref = $publicHref($postUrl);
                if ($postHref === '') {
                    $postHref = $safeUrl($postUrl);
                }
                $postImage = (string) ($p['featured_image'] ?? '');
                if (function_exists('phinit_normalize_public_media_url')) {
                    $postImage = (string) phinit_normalize_public_media_url($postImage, false, $siteUrl);
                }
                $postDateRaw = (string) ($p['published_at'] ?? $p['created_at'] ?? '');
                $postDateIso = '';
                $postDateText = $postDateRaw;
                if ($postDateRaw !== '') {
                    try {
                        $postDate = new DateTimeImmutable($postDateRaw);
                        $postDateIso = $postDate->format('c');
                        $postDateText = function_exists('phinit_format_date')
                            ? (string) phinit_format_date($postDateRaw, 'long', function_exists('phinit_get_current_locale') ? phinit_get_current_locale() : 'de')
                            : $postDate->format('d.m.Y');
                    } catch (Throwable) {
                        $postDateIso = '';
                    }
                }
                $postExcerpt = trim((string) ($p['excerpt_plain'] ?? ''));
                if (mb_strlen($postExcerpt) > 170) {
                    $postExcerpt = rtrim(mb_substr($postExcerpt, 0, 169)) . '…';
                }
                $readTime = !empty($p['read_time']) ? (int) $p['read_time'] : 0;
                if ($readTime < 1 && $postExcerpt !== '') {
                    $readTime = function_exists('phinit_reading_time')
                        ? (int) phinit_reading_time($postExcerpt)
                        : max(1, (int) round(max(1, str_word_count($postExcerpt)) / 220));
                }
                $categorySlug = trim((string) ($p['category_slug'] ?? ''));
                $categoryHref = $categorySlug !== '' ? $publicHref('/category/' . rawurlencode($categorySlug)) : '';
                $imageRef = (string) ($p['featured_image'] ?? $postImage);
                ?>
                <article class="post-card" role="listitem">
                    <?php if ($postImage !== ''): ?>
                    <a class="post-card-thumb" href="<?php echo $esc($postHref); ?>" aria-hidden="true" tabindex="-1">
                        <img src="<?php echo $esc($postImage); ?>"
                             alt="<?php echo $esc((string) ($p['title'] ?? '')); ?>"
                             loading="lazy"
                             decoding="async"
                             <?php echo function_exists('phinit_image_dimension_attributes') ? phinit_image_dimension_attributes($imageRef, 320, 180) : 'width="320" height="180"'; ?>>
                        <?php if (!empty($p['category_name']) && $categoryHref !== ''): ?>
                        <span class="post-card-badge badge-teal"><?php echo $esc((string) ($p['category_name'] ?? '')); ?></span>
                        <?php endif; ?>
                    </a>
                    <?php else: ?>
                    <a class="post-card-thumb post-card-thumb--placeholder" href="<?php echo $esc($postHref); ?>" aria-hidden="true" tabindex="-1">
                        <span class="post-card-thumb__icon">📄</span>
                        <?php if (!empty($p['category_name']) && $categoryHref !== ''): ?>
                        <span class="post-card-badge badge-teal"><?php echo $esc((string) ($p['category_name'] ?? '')); ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>

                    <div class="post-card-body">
                        <h3 class="post-card-title"><a href="<?php echo $esc($postHref); ?>"><?php echo $esc((string) ($p['title'] ?? '')); ?></a></h3>
                        <?php if ($postDateText !== '' || $readTime > 0): ?>
                        <div class="post-card-top-meta">
                            <?php if ($postDateText !== ''): ?>
                            <?php if ($postDateIso !== ''): ?><time datetime="<?php echo $esc($postDateIso); ?>"><?php echo $esc($postDateText); ?></time><?php else: ?><span><?php echo $esc($postDateText); ?></span><?php endif; ?>
                            <?php endif; ?>
                            <?php if ($readTime > 0): ?><span class="post-card-top-meta__read"><?php echo $esc((string) $readTime . ' Min. Lesezeit'); ?></span><?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($postExcerpt !== ''): ?><p class="post-card-excerpt"><?php echo $esc($postExcerpt); ?></p><?php endif; ?>
                        <div class="post-card-meta">
                            <div class="post-card-meta__left">
                                <?php if (!empty($p['category_name']) && $categoryHref !== ''): ?>
                                <a class="cat" href="<?php echo $esc($categoryHref); ?>"><?php echo $esc((string) ($p['category_name'] ?? '')); ?></a>
                                <?php elseif (!empty($p['category_name'])): ?>
                                <span class="cat"><?php echo $esc((string) ($p['category_name'] ?? '')); ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo $esc($postHref); ?>" class="post-card-meta__more" aria-label="<?php echo $esc(($postsReadAriaPrefix !== '' ? $postsReadAriaPrefix : 'Beitrag lesen:') . ' ' . (string) ($p['title'] ?? '')); ?>"><span class="post-card-meta__more-label post-card-meta__more-label--desktop"><?php echo $esc($postReadMoreLabel !== '' ? $postReadMoreLabel : 'Weiter lesen →'); ?></span><span class="post-card-meta__more-label post-card-meta__more-label--mobile"><?php echo $esc($postReadMoreLabel !== '' ? $postReadMoreLabel : 'Weiter lesen →'); ?></span></a>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
                <?php if ($posts === []): ?>
                <article class="m365cp-empty">
                    <h3><?php echo $esc($postsEmptyTitle !== '' ? $postsEmptyTitle : 'Keine Beiträge gefunden'); ?></h3>
                    <p><?php echo $esc($postsEmptyText !== '' ? $postsEmptyText : 'Bitte Kategorie oder Beitragsanzahl in den Einstellungen prüfen.'); ?></p>
                </article>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</main>
