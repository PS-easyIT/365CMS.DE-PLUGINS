<?php
/**
 * CMS Beratung frontend template.
 *
 * @var array<string,mixed> $page
 * @var array<string,string> $settings
 * @var array<string,string> $design
 * @var array<int,array<string,mixed>> $sections
 * @var array<int,array<string,mixed>> $anchors
 * @var array{success:bool,message:string} $formResult
 * @var string $csrfToken
 *
 * @package CMS_Beratung
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$renderer = CMS_Beratung_Renderer::class;
$customClass = trim((string) ($page['custom_css_class'] ?? ''));
$hero = is_array($page['hero'] ?? null) ? $page['hero'] : [];

$buttonClass = static function (string $style): string {
    return 'cms-beratung__btn cms-beratung__btn--' . preg_replace('/[^a-z0-9_-]/i', '', $style ?: 'primary');
};

$renderLinkButton = static function (string $label, string $url, string $style = 'primary') use ($renderer, $buttonClass): void {
    $label = trim($label);
    $url = trim($url);
    if ($label === '' || $url === '') {
        return;
    }
    $external = preg_match('#^https?://#i', $url) === 1;
    echo '<a class="' . $renderer::esc($buttonClass($style)) . '" href="' . $renderer::esc($url) . '"' . ($external ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . $renderer::esc($label) . '</a>';
};

$renderButton = static function (array $button) use ($renderLinkButton): void {
    $renderLinkButton((string) ($button['text'] ?? ''), (string) ($button['target'] ?? ''), (string) ($button['style'] ?? 'primary'));
};

$renderSectionActions = static function (array $section) use ($renderLinkButton): void {
    if (empty($section['button_1_text']) && empty($section['button_2_text'])) {
        return;
    }
    echo '<div class="cms-beratung-module__actions">';
    $renderLinkButton((string) ($section['button_1_text'] ?? ''), (string) ($section['button_1_target'] ?? ''), (string) ($section['button_1_style'] ?? 'primary'));
    $renderLinkButton((string) ($section['button_2_text'] ?? ''), (string) ($section['button_2_target'] ?? ''), (string) ($section['button_2_style'] ?? 'ghost'));
    echo '</div>';
};

$renderCardButton = static function (array $card) use ($renderLinkButton): void {
    $renderLinkButton((string) ($card['button_label'] ?? ''), (string) ($card['button_url'] ?? ''), 'link');
};

$renderCard = static function (array $card, int $index, string $sectionType = 'card_grid') use ($renderer, $renderCardButton): void {
    if (array_key_exists('enabled', $card) && empty($card['enabled'])) {
        return;
    }
    $type = $sectionType === 'steps' ? 'step' : (string) ($card['card_type'] ?? 'text');
    $classes = ['cms-beratung-card', 'cms-beratung-card--' . $type, 'cms-beratung-card--module-' . $sectionType];
    foreach (['featured' => 'is-featured', 'hover_enabled' => 'has-hover'] as $key => $class) {
        if (!empty($card[$key])) {
            $classes[] = $class;
        }
    }
    if (empty($card['border_enabled'])) { $classes[] = 'no-border'; }
    if (empty($card['shadow_enabled'])) { $classes[] = 'no-shadow'; }
    $style = '--card-bg:' . $renderer::esc((string) ($card['background_color'] ?? '#ffffff')) . ';--card-text:' . $renderer::esc((string) ($card['text_color'] ?? '#111827')) . ';--card-border:' . $renderer::esc((string) ($card['border_color'] ?? '#dbeafe')) . ';';
    echo '<article class="' . $renderer::esc(implode(' ', $classes)) . '" style="' . $style . '">';

    if ($sectionType === 'faq') {
        echo '</article>';
        return;
    }

    if ($sectionType === 'technology') {
        if (!empty($card['logo_url'])) {
            echo '<img class="cms-beratung-card__logo" src="' . $renderer::esc((string) $card['logo_url']) . '" alt="' . $renderer::esc((string) ($card['name'] ?? $card['title'] ?? 'Technologie')) . '" loading="lazy">';
        } elseif (!empty($card['icon'])) {
            echo '<div class="cms-beratung-card__icon" aria-hidden="true">' . $renderer::esc((string) $card['icon']) . '</div>';
        }
        echo '<h3>' . $renderer::esc((string) ($card['name'] ?? $card['title'] ?? 'Technologie')) . '</h3>';
        if (!empty($card['text'])) { echo '<p>' . $renderer::nl((string) $card['text']) . '</p>'; }
        echo '</article>';
        return;
    }

    if ($sectionType === 'trust') {
        if (!empty($card['metric'])) { echo '<strong class="cms-beratung-card__metric">' . $renderer::esc((string) $card['metric']) . '</strong>'; }
        if (!empty($card['icon'])) { echo '<div class="cms-beratung-card__icon" aria-hidden="true">' . $renderer::esc((string) $card['icon']) . '</div>'; }
        if (!empty($card['title'])) { echo '<h3>' . $renderer::esc((string) $card['title']) . '</h3>'; }
        if (!empty($card['text'])) { echo '<p>' . $renderer::nl((string) $card['text']) . '</p>'; }
        $renderCardButton($card);
        echo '</article>';
        return;
    }

    if ($sectionType === 'services' && !empty($card['category'])) {
        echo '<span class="cms-beratung-card__category">' . $renderer::esc((string) $card['category']) . '</span>';
    }

    if ($type === 'image_label') {
        if (!empty($card['image_url'])) {
            echo '<div class="cms-beratung-card__image" style="--card-image-height:' . (int) ($card['image_height'] ?? 230) . 'px;--card-image-fit:' . $renderer::esc((string) ($card['image_fit'] ?? 'cover')) . '"><img src="' . $renderer::esc((string) $card['image_url']) . '" alt="' . $renderer::esc((string) ($card['image_alt'] ?? $card['title'] ?? '')) . '" loading="lazy"></div>';
        } elseif (!empty($card['icon'])) {
            echo '<div class="cms-beratung-card__image is-placeholder" aria-hidden="true">' . $renderer::esc((string) $card['icon']) . '</div>';
        }
        if (!empty($card['label'])) {
            echo '<span class="cms-beratung-card__label cms-beratung-card__label--' . $renderer::esc((string) ($card['label_position'] ?? 'left')) . ' cms-beratung-card__label--' . $renderer::esc((string) ($card['label_style'] ?? 'filled')) . '">' . $renderer::esc((string) $card['label']) . '</span>';
        }
        echo '<div class="cms-beratung-card__body">';
        if (!empty($card['title'])) { echo '<h3>' . $renderer::esc((string) $card['title']) . '</h3>'; }
        if (!empty($card['text'])) { echo '<p>' . $renderer::nl((string) $card['text']) . '</p>'; }
        $renderCardButton($card);
        echo '</div>';
    } elseif ($type === 'offer_icon_tab') {
        if (!empty($card['tab_enabled'])) {
            echo '<div class="cms-beratung-card__tab" style="--icon-bg:' . $renderer::esc((string) ($card['icon_background'] ?? '#eff6ff')) . ';--icon-color:' . $renderer::esc((string) ($card['icon_color'] ?? '#2563eb')) . '" aria-hidden="true">' . $renderer::esc((string) ($card['icon'] ?? '💡')) . '</div>';
        }
        if (!empty($card['title'])) { echo '<h3>' . $renderer::esc((string) $card['title']) . '</h3>'; }
        if (!empty($card['text'])) { echo '<p>' . $renderer::nl((string) $card['text']) . '</p>'; }
        $renderCardButton($card);
    } elseif ($type === 'step') {
        $step = !empty($card['auto_number']) ? (string) ($index + 1) : (string) ($card['step_number'] ?? $index + 1);
        echo '<div class="cms-beratung-card__step"><span>' . $renderer::esc($step) . '</span>' . (!empty($card['icon']) ? '<i aria-hidden="true">' . $renderer::esc((string) $card['icon']) . '</i>' : '') . '</div>';
        if (!empty($card['connector'])) { echo '<span class="cms-beratung-card__connector" aria-hidden="true"></span>'; }
        if (!empty($card['title'])) { echo '<h3>' . $renderer::esc((string) $card['title']) . '</h3>'; }
        if (!empty($card['text'])) { echo '<p>' . $renderer::nl((string) $card['text']) . '</p>'; }
        $renderCardButton($card);
    } elseif ($type === 'problem_solution') {
        echo '<div class="cms-beratung-problem-solution"><div><span>Spalte 1</span><h3>' . $renderer::esc((string) ($card['problem_title'] ?? 'Problem')) . '</h3><p>' . $renderer::nl((string) ($card['problem_text'] ?? '')) . '</p></div><div><span>Spalte 2</span><h3>' . $renderer::esc((string) ($card['solution_title'] ?? 'Lösung')) . '</h3><p>' . $renderer::nl((string) ($card['solution_text'] ?? '')) . '</p></div></div>';
        if (!empty($card['extra_text']) || !empty($card['recommendation'])) {
            echo '<div class="cms-beratung-card__risk"><p>' . $renderer::nl((string) ($card['extra_text'] ?? $card['recommendation'] ?? '')) . '</p></div>';
        }
        $renderCardButton($card);
    } else {
        if (!empty($card['badge'])) { echo '<span class="cms-beratung-card__badge">' . $renderer::esc((string) $card['badge']) . '</span>'; }
        if (!empty($card['icon'])) { echo '<div class="cms-beratung-card__icon" aria-hidden="true">' . $renderer::esc((string) $card['icon']) . '</div>'; }
        if (!empty($card['title'])) { echo '<h3>' . $renderer::esc((string) $card['title']) . '</h3>'; }
        if (!empty($card['text'])) { echo '<p>' . $renderer::nl((string) $card['text']) . '</p>'; }
        if (!empty($card['extra_text'])) { echo '<p class="cms-beratung-card__extra">' . $renderer::nl((string) $card['extra_text']) . '</p>'; }
        $renderCardButton($card);
    }
    echo '</article>';
};

$renderCardsGrid = static function (array $section, array $cards, int $columns) use ($renderCard): void {
    echo '<div class="cms-beratung__cards cms-beratung__cards--' . $columns . ' cms-beratung__cards--design-' . CMS_Beratung_Renderer::esc((string) ($section['card_design'] ?? 'standard')) . '">';
    foreach ($cards as $index => $card) {
        if (is_array($card)) {
            $renderCard($card, $index, (string) ($section['type'] ?? 'card_grid'));
        }
    }
    echo '</div>';
};

$renderComparison = static function (array $section, array $cards, int $columns) use ($renderer): void {
    if (!empty($section['title_band_enabled']) && !empty($section['title_band_text'])) {
        echo '<div class="cms-beratung-comparison__band" style="--band-bg:' . $renderer::esc((string) ($section['title_band_background_color'] ?? '#1e3a8a')) . ';--band-text:' . $renderer::esc((string) ($section['title_band_text_color'] ?? '#ffffff')) . '">' . $renderer::esc((string) $section['title_band_text']) . '</div>';
    }
    echo '<div class="cms-beratung-comparison cms-beratung-comparison--' . $columns . ' ' . (!empty($section['border_enabled']) ? 'has-border' : '') . ' ' . (!empty($section['shadow_enabled']) ? 'has-shadow' : '') . '">';
    foreach ($cards as $card) {
        if (!is_array($card) || empty($card['enabled'])) { continue; }
        echo '<article class="cms-beratung-comparison__column">';
        if (!empty($card['icon'])) { echo '<div aria-hidden="true">' . $renderer::esc((string) $card['icon']) . '</div>'; }
        echo '<h3>' . $renderer::esc((string) ($card['title'] ?? $card['problem_title'] ?? 'Vergleich')) . '</h3>';
        echo '<p>' . $renderer::nl((string) ($card['text'] ?? $card['problem_text'] ?? '')) . '</p>';
        if (!empty($card['extra_text'])) { echo '<small>' . $renderer::nl((string) $card['extra_text']) . '</small>'; }
        echo '</article>';
    }
    echo '</div>';
};

$renderFaq = static function (array $section, array $cards, string $sectionId) use ($renderer): void {
    echo '<div class="cms-beratung-faq" data-allow-multiple="' . (!empty($section['faq_allow_multiple']) ? '1' : '0') . '" style="--faq-q-bg:' . $renderer::esc((string) ($section['faq_question_background_color'] ?? '#ffffff')) . ';--faq-q-text:' . $renderer::esc((string) ($section['faq_question_text_color'] ?? '#111827')) . ';--faq-a-bg:' . $renderer::esc((string) ($section['faq_answer_background_color'] ?? '#f8fafc')) . '">';
    $openBehavior = (string) ($section['faq_open_behavior'] ?? 'none');
    foreach ($cards as $index => $card) {
        if (!is_array($card) || empty($card['enabled'])) { continue; }
        $isOpen = ($openBehavior === 'first' && $index === 0) || ($openBehavior === 'custom' && !empty($card['default_open']));
        $buttonId = $sectionId . '-faq-button-' . $index;
        $panelId = $sectionId . '-faq-panel-' . $index;
        $icon = (string) ($section['faq_icon_style'] ?? 'plus');
        echo '<article class="cms-beratung-faq__item ' . ($isOpen ? 'is-open' : '') . '"><h3><button type="button" id="' . $renderer::esc($buttonId) . '" aria-controls="' . $renderer::esc($panelId) . '" aria-expanded="' . ($isOpen ? 'true' : 'false') . '"><span>' . $renderer::esc((string) ($card['question'] ?? $card['title'] ?? 'Frage')) . '</span><i data-icon-style="' . $renderer::esc($icon) . '" aria-hidden="true"></i></button></h3><div id="' . $renderer::esc($panelId) . '" role="region" aria-labelledby="' . $renderer::esc($buttonId) . '"' . ($isOpen ? '' : ' hidden') . '><p>' . $renderer::nl((string) ($card['answer'] ?? $card['text'] ?? '')) . '</p></div></article>';
    }
    echo '</div>';
};

$renderDivider = static function (array $section) use ($renderer, $renderSectionActions): void {
    $type = (string) ($section['divider_type'] ?? 'line');
    echo '<div class="cms-beratung-divider cms-beratung-divider--' . $renderer::esc($type) . ' cms-beratung-divider--mobile-' . $renderer::esc((string) ($section['divider_mobile_behavior'] ?? 'stack')) . '" style="--divider-bg:' . $renderer::esc((string) ($section['background_color'] ?? '#ffffff')) . ';--divider-text:' . $renderer::esc((string) ($section['text_color'] ?? '#111827')) . ';--divider-line:' . $renderer::esc((string) ($section['divider_line_color'] ?? '#dbeafe')) . ';--divider-width:' . (int) ($section['divider_width'] ?? 100) . '%;">';
    if ($type === 'spacer') { echo '<span aria-hidden="true"></span>'; }
    elseif ($type === 'wave') { echo '<svg viewBox="0 0 1440 90" aria-hidden="true" focusable="false"><path d="M0,48 C180,96 360,0 540,42 C720,84 900,84 1080,36 C1260,-12 1350,24 1440,54 L1440,90 L0,90 Z"></path></svg>'; }
    else {
        if (!empty($section['divider_icon'])) { echo '<span class="cms-beratung-divider__icon">' . $renderer::esc((string) $section['divider_icon']) . '</span>'; }
        if (!empty($section['divider_title'])) { echo '<h2>' . $renderer::esc((string) $section['divider_title']) . '</h2>'; }
        if (!empty($section['divider_subtitle'])) { echo '<p>' . $renderer::nl((string) $section['divider_subtitle']) . '</p>'; }
        if ($type === 'cta') { $renderSectionActions($section); }
    }
    echo '</div>';
};
?>
<main class="cms-beratung cms-beratung--<?php echo $renderer::esc((string) ($page['template'] ?? 'standard')); ?> <?php echo $renderer::esc($customClass); ?>" style="--beratung-primary: <?php echo $renderer::esc($design['primary_color']); ?>; --beratung-secondary: <?php echo $renderer::esc($design['secondary_color']); ?>; --beratung-accent: <?php echo $renderer::esc($design['accent_color']); ?>; --beratung-bg: <?php echo $renderer::esc($design['background_color']); ?>; --beratung-text: <?php echo $renderer::esc($design['text_color']); ?>; --beratung-heading: <?php echo $renderer::esc($design['heading_color']); ?>; --beratung-button: <?php echo $renderer::esc($design['button_color']); ?>; --beratung-button-text: <?php echo $renderer::esc($design['button_text_color']); ?>; --beratung-card-bg: <?php echo $renderer::esc($design['card_background_color']); ?>; --beratung-card-border: <?php echo $renderer::esc($design['card_border_color']); ?>; --beratung-radius: <?php echo $renderer::esc($design['border_radius']); ?>; --beratung-spacing: <?php echo $renderer::esc($design['spacing']); ?>; --beratung-width: <?php echo $renderer::esc($design['content_width']); ?>; --beratung-card-shadow: <?php echo $renderer::esc($design['card_shadow']); ?>;">
    <?php if (!empty($page['show_breadcrumb'])): ?>
        <nav class="cms-beratung__breadcrumb" aria-label="Breadcrumb"><a href="/">Startseite</a><span aria-hidden="true">/</span><span><?php echo $renderer::esc((string) ($page['public_title'] ?? 'Beratung')); ?></span></nav>
    <?php endif; ?>

    <?php if (!empty($hero['enabled'])): ?>
        <?php $heroTitle = trim((string) ($hero['title'] ?? '')) ?: (string) ($page['public_title'] ?? 'CMS Beratung'); ?>
        <header class="cms-beratung-hero cms-beratung-hero--image-<?php echo $renderer::esc((string) ($hero['image_position'] ?? 'left')); ?> cms-beratung-hero--mobile-<?php echo $renderer::esc((string) ($hero['mobile_order'] ?? 'image-first')); ?> <?php echo !empty($hero['image_flush']) ? 'is-flush' : ''; ?>" style="--hero-bg:<?php echo $renderer::esc((string) ($hero['background_color'] ?? '#f8fafc')); ?>;--hero-text:<?php echo $renderer::esc((string) ($hero['text_color'] ?? '#111827')); ?>;--hero-image-height:<?php echo (int) ($hero['image_height'] ?? 520); ?>px;--hero-image-width:<?php echo (int) ($hero['image_width'] ?? 46); ?>%;--hero-image-fit:<?php echo $renderer::esc((string) ($hero['image_fit'] ?? 'cover')); ?>;--hero-align:<?php echo $renderer::esc((string) ($hero['vertical_align'] ?? 'center')); ?>;">
            <div class="cms-beratung-hero__image"><?php if (!empty($hero['image_url'])): ?><img src="<?php echo $renderer::esc((string) $hero['image_url']); ?>" alt="<?php echo $renderer::esc((string) ($hero['image_alt'] ?? $heroTitle)); ?>" loading="eager"><?php else: ?><div class="cms-beratung-hero__placeholder" aria-hidden="true">365</div><?php endif; ?></div>
            <div class="cms-beratung-hero__content">
                <?php if (!empty($hero['badge_show']) && !empty($hero['badge_text'])): ?><span class="cms-beratung__kicker"><?php echo $renderer::esc((string) $hero['badge_text']); ?></span><?php endif; ?>
                <h1><?php echo $renderer::esc($heroTitle); ?></h1>
                <?php if (!empty($hero['subtitle'])): ?><p class="cms-beratung-hero__subtitle"><?php echo $renderer::esc((string) $hero['subtitle']); ?></p><?php endif; ?>
                <?php if (!empty($hero['description'])): ?><p class="cms-beratung-hero__description"><?php echo $renderer::esc((string) $hero['description']); ?></p><?php endif; ?>
                <div class="cms-beratung__hero-actions"><?php $renderButton(is_array($hero['button_1'] ?? null) ? $hero['button_1'] : []); ?><?php $renderButton(is_array($hero['button_2'] ?? null) ? $hero['button_2'] : []); ?><?php $renderButton(is_array($hero['button_3'] ?? null) ? $hero['button_3'] : []); ?></div>
                <?php if (!empty($hero['trust_text'])): ?><p class="cms-beratung-hero__trust"><?php echo $renderer::esc((string) $hero['trust_text']); ?></p><?php endif; ?>
            </div>
        </header>
    <?php endif; ?>

    <?php if (!empty($page['show_anchor_nav']) && $anchors !== []): ?>
        <nav class="cms-beratung__anchor-nav" aria-label="Anker Navigation" id="beratung-inhalte">
            <?php foreach ($anchors as $section): ?><a href="#<?php echo $renderer::esc((string) ($section['anchor_id'] ?? $section['id'] ?? 'bereich')); ?>"><?php echo $renderer::esc((string) ($section['title'] ?? $section['internal_name'] ?? 'Bereich')); ?></a><?php endforeach; ?>
            <a href="#kontakt">Kontakt</a>
        </nav>
    <?php endif; ?>

    <?php foreach ($sections as $section): ?>
        <?php if (empty($section['enabled'])) { continue; } ?>
        <?php $columns = max(1, min(4, (int) ($section['columns'] ?? 3))); $sectionId = (string) ($section['anchor_id'] ?? $section['id'] ?? 'bereich'); $sectionType = (string) ($section['type'] ?? 'card_grid'); $cards = is_array($section['cards'] ?? null) ? $section['cards'] : []; ?>
        <section class="cms-beratung__section cms-beratung__section--<?php echo $renderer::esc($sectionType); ?> cms-beratung__section--display-<?php echo $renderer::esc((string) ($section['display_style'] ?? 'cards')); ?> <?php echo !empty($section['equal_height']) ? 'has-equal-cards' : ''; ?>" id="<?php echo $renderer::esc($sectionId); ?>" style="--section-bg: <?php echo $renderer::esc((string) ($section['background_color'] ?? '#ffffff')); ?>; --section-text: <?php echo $renderer::esc((string) ($section['text_color'] ?? '#111827')); ?>; --section-pt: <?php echo (int) ($section['padding_top'] ?? 56); ?>px; --section-pb: <?php echo (int) ($section['padding_bottom'] ?? 56); ?>px; --section-width: <?php echo (int) ($section['max_width'] ?? 1200); ?>px; --section-align: <?php echo $renderer::esc((string) ($section['text_align'] ?? 'left')); ?>; <?php if (!empty($section['background_image_url'])): ?>--section-bg-image:url('<?php echo $renderer::esc((string) $section['background_image_url']); ?>');<?php endif; ?>">
            <div class="cms-beratung__section-inner">
                <?php if (!in_array($sectionType, ['divider'], true)): ?>
                    <div class="cms-beratung__section-head"><?php if (!empty($section['eyebrow'])): ?><span><?php echo $renderer::esc((string) $section['eyebrow']); ?></span><?php endif; ?><?php if (!empty($section['title'])): ?><h2><?php echo $renderer::esc((string) $section['title']); ?></h2><?php endif; ?><?php if (!empty($section['intro'])): ?><p><?php echo $renderer::esc((string) $section['intro']); ?></p><?php endif; ?></div>
                <?php endif; ?>

                <?php if ($sectionType === 'divider'): ?><?php $renderDivider($section); ?>
                <?php elseif ($sectionType === 'html' && !empty($section['html'])): ?><div class="cms-beratung__html"><?php echo $renderer::safe_html((string) $section['html']); ?></div>
                <?php elseif ($sectionType === 'comparison'): ?><?php $renderComparison($section, $cards, $columns); ?>
                <?php elseif ($sectionType === 'faq'): ?><?php $renderFaq($section, $cards, $sectionId); ?>
                <?php elseif ($sectionType === 'cta'): ?><div class="cms-beratung-cta cms-beratung-cta--<?php echo $renderer::esc((string) ($section['display_style'] ?? 'large')); ?>"><?php $renderSectionActions($section); ?></div>
                <?php elseif ($cards !== []): ?><?php $renderCardsGrid($section, $cards, $columns); ?>
                <?php endif; ?>

                <?php if (!empty($section['note_text'])): ?><p class="cms-beratung-module__note"><?php echo $renderer::esc((string) $section['note_text']); ?></p><?php endif; ?>
                <?php if (!in_array($sectionType, ['cta', 'divider'], true)) { $renderSectionActions($section); } ?>
            </div>
        </section>
    <?php endforeach; ?>

    <section class="cms-beratung__contact" id="kontakt" aria-labelledby="beratung-contact-title">
        <div><span class="cms-beratung__kicker">Kontakt</span><h2 id="beratung-contact-title">Beratungsanfrage senden</h2><p>Beschreiben Sie kurz Ihr Anliegen rund um Microsoft 365, Copilot, KI, Security, Compliance, SharePoint, Entra ID, Purview oder Defender.</p></div>
        <form method="post" class="cms-beratung-form">
            <input type="hidden" name="csrf_token" value="<?php echo $renderer::esc($csrfToken); ?>"><input type="hidden" name="beratung_form_action" value="submit_request"><label class="cms-beratung-form__hp">Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            <?php if ($formResult['message'] !== ''): ?><div class="cms-beratung-form__notice <?php echo $formResult['success'] ? 'is-success' : 'is-error'; ?>" role="status"><?php echo $renderer::esc($formResult['message']); ?></div><?php endif; ?>
            <div class="cms-beratung-form__grid"><label>Name*<input name="sender_name" required></label><label>E-Mail*<input name="sender_email" type="email" required></label><label>Telefon<input name="phone"></label><label>Unternehmen<input name="company"></label></div>
            <label>Thema<input name="topic" placeholder="z. B. Copilot Readiness, Security Workshop, Purview Compliance"></label><label>Nachricht*<textarea name="message" rows="5" required></textarea></label><label class="cms-beratung-form__check"><input type="checkbox" name="consent" value="1" required> <span><?php echo $renderer::esc((string) ($settings['privacy_text'] ?? 'Ich stimme der Verarbeitung meiner Angaben zu.')); ?></span></label>
            <?php if (($settings['sender_copy_enabled'] ?? '0') === '1'): ?><label class="cms-beratung-form__check"><input type="checkbox" name="copy_to_sender" value="1"> <span>Kopie an mich senden</span></label><?php endif; ?>
            <button class="cms-beratung__btn" type="submit">Anfrage senden</button>
        </form>
    </section>
</main>
