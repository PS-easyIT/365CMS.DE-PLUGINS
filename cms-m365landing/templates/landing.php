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
$buttonLabelDefault = $value('card_button_label_default', 'Öffnen');
$layoutVariant = in_array($value('layout_variant', 'balanced'), ['balanced', 'compact', 'spotlight'], true) ? $value('layout_variant', 'balanced') : 'balanced';
$heroImageUrl = CMS_M365Landing_Repository::public_image_url($value('hero_image_url'));
$heroImageAlt = $value('hero_image_alt', $value('page_title', 'Microsoft 365 Hub'));
$hasAnyCards = !empty($cardsBySection['matrix']) || !empty($cardsBySection['areas']) || !empty($cardsBySection['tools']);

$renderCard = static function (array $card) use ($esc, $buttonLabelDefault, $isExternal): void {
    $url = CMS_M365Landing_Repository::public_url((string) ($card['url'] ?? ''));
    $imageUrl = CMS_M365Landing_Repository::public_image_url((string) ($card['image_url'] ?? ''));
    $icon = trim((string) ($card['icon'] ?? ''));
    $title = trim((string) ($card['title'] ?? ''));
    $subtitle = trim((string) ($card['subtitle'] ?? ''));
    $description = trim((string) ($card['description'] ?? ''));
    $buttonLabel = trim((string) ($card['button_label'] ?? ''));
    $buttonLabel = $buttonLabel !== '' ? $buttonLabel : $buttonLabelDefault;
    $featuredClass = (int) ($card['is_featured'] ?? 0) === 1 ? ' m365landing-card--featured' : '';
    ?>
    <article class="m365landing-card<?php echo $featuredClass; ?>">
        <div class="m365landing-card__visual" aria-hidden="<?php echo $imageUrl !== '' ? 'false' : 'true'; ?>">
            <?php if ($imageUrl !== ''): ?>
            <img src="<?php echo $esc($imageUrl); ?>" alt="<?php echo $esc((string) ($card['image_alt'] ?: $title)); ?>" loading="lazy">
            <?php else: ?>
            <span class="m365landing-card__icon"><?php echo $esc($icon !== '' ? $icon : '▦'); ?></span>
            <?php endif; ?>
        </div>
        <div class="m365landing-card__body">
            <?php if ($subtitle !== ''): ?>
            <p class="m365landing-card__subtitle"><?php echo $esc($subtitle); ?></p>
            <?php endif; ?>
            <h3><?php echo $esc($title); ?></h3>
            <?php if ($description !== ''): ?>
            <p class="m365landing-card__description"><?php echo nl2br($esc($description)); ?></p>
            <?php endif; ?>
        </div>
        <?php if ($url !== ''): ?>
        <div class="m365landing-card__footer">
            <a class="phinit-btn phinit-btn--secondary m365landing-card__button" href="<?php echo $esc($url); ?>"<?php echo $isExternal($url) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
                <?php echo $esc($buttonLabel); ?>
            </a>
        </div>
        <?php endif; ?>
    </article>
    <?php
};

$renderSection = static function (string $sectionKey, string $sectionClass, array $cards) use ($esc, $value, $renderCard): void {
    if ($cards === []) {
        return;
    }
    ?>
    <section class="m365landing-section <?php echo $esc($sectionClass); ?>" aria-labelledby="m365landing-<?php echo $esc($sectionKey); ?>-title">
        <div class="m365landing-section__head">
            <?php if ($value($sectionKey . '_section_overline') !== ''): ?>
            <p class="phinit-overline m365landing-overline"><?php echo $esc($value($sectionKey . '_section_overline')); ?></p>
            <?php endif; ?>
            <h2 id="m365landing-<?php echo $esc($sectionKey); ?>-title"><?php echo $esc($value($sectionKey . '_section_title')); ?></h2>
            <?php if ($value($sectionKey . '_section_intro') !== ''): ?>
            <p class="m365landing-section__intro"><?php echo $esc($value($sectionKey . '_section_intro')); ?></p>
            <?php endif; ?>
        </div>
        <div class="m365landing-grid" role="list">
            <?php foreach ($cards as $card): ?>
                <div role="listitem"><?php $renderCard($card); ?></div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
};
?>
<main class="phinit-plugin m365landing-page m365landing-layout--<?php echo $esc($layoutVariant); ?>" id="m365landing-page">
    <?php if ($enabled('show_hero')): ?>
    <header class="m365landing-hero" aria-labelledby="m365landing-title">
        <div class="m365landing-hero__inner">
            <div class="m365landing-hero__content">
                <?php if ($value('page_overline') !== ''): ?>
                <p class="phinit-overline m365landing-overline"><?php echo $esc($value('page_overline')); ?></p>
                <?php endif; ?>
                <h1 id="m365landing-title"><?php echo $esc($value('page_title', 'Microsoft 365 Hub')); ?></h1>
                <?php if ($value('page_intro') !== ''): ?>
                <p class="m365landing-hero__intro"><?php echo $esc($value('page_intro')); ?></p>
                <?php endif; ?>
                <?php if ($enabled('show_hero_actions')): ?>
                <nav class="m365landing-hero__actions" aria-label="M365 Landing Schnellzugriff">
                    <?php $primaryUrl = CMS_M365Landing_Repository::public_url($value('hero_primary_button_url')); ?>
                    <?php if ($primaryUrl !== ''): ?>
                    <a class="phinit-btn phinit-btn--primary" href="<?php echo $esc($primaryUrl); ?>"<?php echo $isExternal($primaryUrl) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo $esc($value('hero_primary_button_text', 'M365 Lizenzmatrix öffnen')); ?></a>
                    <?php endif; ?>
                    <?php $secondaryUrl = CMS_M365Landing_Repository::public_url($value('hero_secondary_button_url')); ?>
                    <?php if ($secondaryUrl !== ''): ?>
                    <a class="phinit-btn phinit-btn--secondary" href="<?php echo $esc($secondaryUrl); ?>"<?php echo $isExternal($secondaryUrl) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo $esc($value('hero_secondary_button_text', 'Add-on-Matrix öffnen')); ?></a>
                    <?php endif; ?>
                </nav>
                <?php endif; ?>
            </div>
            <?php if ($heroImageUrl !== ''): ?>
            <figure class="m365landing-hero__image">
                <img src="<?php echo $esc($heroImageUrl); ?>" alt="<?php echo $esc($heroImageAlt); ?>" loading="eager" decoding="async">
            </figure>
            <?php endif; ?>
        </div>
    </header>
    <?php endif; ?>

    <?php if ($enabled('show_matrix_section')): ?>
        <?php $renderSection('matrix', 'm365landing-section--matrix', $cardsBySection['matrix'] ?? []); ?>
    <?php endif; ?>

    <?php if ($enabled('show_separator')): ?>
    <div class="m365landing-soft-separator" role="presentation">
        <span><?php echo $esc($value('separator_label', 'Weitere Microsoft-365-Bereiche')); ?></span>
    </div>
    <?php endif; ?>

    <?php if ($enabled('show_areas_section')): ?>
        <?php $renderSection('areas', 'm365landing-section--areas', $cardsBySection['areas'] ?? []); ?>
    <?php endif; ?>

    <?php if ($enabled('show_tools_section')): ?>
        <?php $renderSection('tools', 'm365landing-section--tools', $cardsBySection['tools'] ?? []); ?>
    <?php endif; ?>

    <?php if (!$hasAnyCards): ?>
    <section class="m365landing-empty phinit-card">
        <p class="m365landing-empty__icon" aria-hidden="true">🧭</p>
        <h2><?php echo $esc($value('empty_state_title', 'Noch keine aktiven Karten vorhanden')); ?></h2>
        <p><?php echo $esc($value('empty_state_text', 'Aktiviere oder erstelle Karten im Adminbereich, damit die Landingpage Inhalte anzeigen kann.')); ?></p>
    </section>
    <?php endif; ?>
</main>
