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
$imgUrl = static function (string $url): string {
    $u = CMS_M365Copilot_Settings::public_image_url($url);
    return $u !== '' ? $u : '';
};
$int = static fn(string $key, int $fallback): int => max(0, (int) ($settings[$key] ?? (string) $fallback));
$headerLayout = in_array((string) ($settings['header_layout'] ?? '1'), ['1', '2', '3'], true) ? (string) ($settings['header_layout'] ?? '1') : '1';
$serviceLayout = in_array((string) ($settings['service_layout'] ?? '1'), ['1', '2', '3'], true) ? (string) ($settings['service_layout'] ?? '1') : '1';
$serviceEnabled = (string) ($settings['service_enabled'] ?? '1') === '1';
$postsEnabled = (string) ($settings['posts_show'] ?? '1') === '1';
$cards = [1, 2, 3];

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
?>

<style>
.m365cp {
    <?php echo $esc($cssVars); ?>
}
</style>

<main class="m365cp">
    <div class="m365cp__container">
        <section class="m365cp-header m365cp-header--layout-<?php echo $esc($headerLayout); ?>">
            <?php if ($imgUrl((string) ($settings['header_image_url'] ?? '')) !== ''): ?>
            <div class="m365cp-header__media">
                <img src="<?php echo $esc($imgUrl((string) ($settings['header_image_url'] ?? ''))); ?>" alt="<?php echo $esc((string) ($settings['header_image_alt'] ?? '')); ?>" loading="eager" decoding="async">
            </div>
            <?php endif; ?>
            <div class="m365cp-header__content">
                <h1><?php echo $esc((string) ($settings['header_title'] ?? '')); ?></h1>
                <p><?php echo $esc((string) ($settings['header_intro'] ?? '')); ?></p>
                <?php $headerCta = $safeUrl((string) ($settings['header_cta_url'] ?? '')); ?>
                <?php if ($headerCta !== '' && trim((string) ($settings['header_cta_label'] ?? '')) !== ''): ?>
                <a class="m365cp-btn m365cp-btn--primary" href="<?php echo $esc($headerCta); ?>"><?php echo $esc((string) ($settings['header_cta_label'] ?? '')); ?></a>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($serviceEnabled): ?>
        <section class="m365cp-service m365cp-service--layout-<?php echo $esc($serviceLayout); ?>">
            <?php $logo = $imgUrl((string) ($settings['service_logo_url'] ?? '')); ?>
            <?php if ($logo !== ''): ?>
            <div class="m365cp-service__logo">
                <img src="<?php echo $esc($logo); ?>" alt="<?php echo $esc((string) ($settings['service_logo_alt'] ?? '')); ?>" loading="lazy" decoding="async">
            </div>
            <?php endif; ?>
            <div class="m365cp-service__text"><?php echo $esc((string) ($settings['service_text'] ?? '')); ?></div>
            <?php $serviceCta = $safeUrl((string) ($settings['service_cta_url'] ?? '')); ?>
            <?php if ($serviceCta !== '' && trim((string) ($settings['service_cta_label'] ?? '')) !== ''): ?>
            <a class="m365cp-btn m365cp-btn--ghost" href="<?php echo $esc($serviceCta); ?>"><?php echo $esc((string) ($settings['service_cta_label'] ?? '')); ?></a>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <section class="m365cp-cards">
            <div class="m365cp-cards__grid">
                <?php foreach ($cards as $i): ?>
                <?php $u = $safeUrl((string) ($settings['card_' . $i . '_url'] ?? '')); ?>
                <article class="m365cp-card">
                    <?php $ci = $imgUrl((string) ($settings['card_' . $i . '_image_url'] ?? '')); ?>
                    <?php if ($ci !== ''): ?>
                    <div class="m365cp-card__icon"><img src="<?php echo $esc($ci); ?>" alt="<?php echo $esc((string) ($settings['card_' . $i . '_image_alt'] ?? '')); ?>" loading="lazy" decoding="async"></div>
                    <?php endif; ?>
                    <h2><?php echo $esc((string) ($settings['card_' . $i . '_title'] ?? '')); ?></h2>
                    <p><?php echo $esc((string) ($settings['card_' . $i . '_text'] ?? '')); ?></p>
                    <?php if ($u !== ''): ?>
                    <a class="m365cp-card__link" href="<?php echo $esc($u); ?>"><?php echo $esc($cardLinkLabel !== '' ? $cardLinkLabel : 'Mehr erfahren →'); ?></a>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if ($postsEnabled): ?>
        <section class="m365cp-posts">
            <header class="m365cp-posts__head">
                <h2><?php echo $esc((string) ($settings['posts_title'] ?? '')); ?></h2>
                <p><?php echo $esc((string) ($settings['posts_intro'] ?? '')); ?></p>
            </header>
            <div class="posts-grid posts-grid--cols-3">
                <?php foreach ($posts as $p): ?>
                <?php
                $postUrl = '/blog/' . rawurlencode((string) ($p['slug'] ?? ''));
                if (function_exists('phinit_build_post_url')) {
                    $postUrl = (string) phinit_build_post_url($p);
                }
                $postImage = (string) ($p['featured_image'] ?? '');
                if (function_exists('phinit_normalize_public_media_url')) {
                    $postImage = (string) phinit_normalize_public_media_url($postImage, false, (string) (defined('SITE_URL') ? SITE_URL : ''));
                }
                ?>
                <article class="post-card">
                    <?php if ($postImage !== ''): ?>
                    <a href="<?php echo $esc($postUrl); ?>" class="post-card-thumb">
                        <img src="<?php echo $esc($postImage); ?>" alt="<?php echo $esc((string) ($p['title'] ?? '')); ?>" loading="lazy" decoding="async">
                        <?php if (!empty($p['category_name'])): ?>
                        <span class="post-card-badge"><?php echo $esc((string) ($p['category_name'] ?? '')); ?></span>
                        <?php endif; ?>
                    </a>
                    <?php else: ?>
                    <div class="post-card-thumb post-card-thumb--placeholder"><span class="post-card-thumb__icon">📄</span></div>
                    <?php endif; ?>
                    <div class="post-card-body">
                        <h3 class="post-card-title"><a href="<?php echo $esc($postUrl); ?>"><?php echo $esc((string) ($p['title'] ?? '')); ?></a></h3>
                        <div class="post-card-meta">
                            <span class="post-card-meta__left">📅 <?php echo $esc((string) ($p['published_at'] ?? $p['created_at'] ?? '')); ?></span>
                            <a href="<?php echo $esc($postUrl); ?>" class="post-card-meta__more"><?php echo $esc($postReadMoreLabel !== '' ? $postReadMoreLabel : 'Weiter lesen →'); ?></a>
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
