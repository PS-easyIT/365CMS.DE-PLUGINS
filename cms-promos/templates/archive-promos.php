<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<div class="promos-archive">
    <section class="promos-hero">
        <span class="promos-kicker">365CMS Promos</span>
        <h1 class="promos-title"><?php echo htmlspecialchars((string) ($settings['archive_title'] ?? 'Promotions & Highlights')); ?></h1>
        <p class="promos-lead"><?php echo htmlspecialchars((string) ($settings['archive_description'] ?? 'Zentrale Übersicht aktiver Kampagnen, CTA-Flächen und Teaser-Aktionen.')); ?></p>
        <?php if ($currentPlacement !== null): ?>
            <p class="promos-lead">Aktuelle Platzierung: <strong><?php echo htmlspecialchars((string) ($currentPlacement['name'] ?? '')); ?></strong></p>
        <?php endif; ?>
    </section>

    <?php if (!empty($placements)): ?>
        <section class="promos-placement-nav">
            <?php foreach ($placements as $placement): ?>
                <a class="promos-placement-link" href="/promos/placement/<?php echo rawurlencode((string) $placement['slug']); ?>">
                    <strong><?php echo htmlspecialchars((string) $placement['name']); ?></strong><br>
                    <span><?php echo htmlspecialchars((string) ($placement['description'] ?? '')); ?></span>
                </a>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <section class="promos-grid">
        <?php if (empty($promos)): ?>
            <article class="promos-card">
                <span class="promos-card__eyebrow">Noch leer</span>
                <h2 class="promos-card__title">Aktuell keine aktiven Promos</h2>
                <p>Sobald Kampagnen aktiviert und einer Platzierung zugeordnet wurden, erscheinen sie hier automatisch.</p>
            </article>
        <?php else: ?>
            <?php foreach ($promos as $promo): ?>
                <article class="promos-card">
                    <span class="promos-card__eyebrow"><?php echo htmlspecialchars((string) ($promo['placement_name'] ?? 'Promo')); ?></span>
                    <h2 class="promos-card__title"><?php echo htmlspecialchars((string) ($promo['title'] ?? '')); ?></h2>
                    <?php if (!empty($promo['teaser'])): ?><p><?php echo htmlspecialchars((string) $promo['teaser']); ?></p><?php endif; ?>
                    <?php if (!empty($promo['content_html'])): ?><div><?php echo $promo['content_html']; ?></div><?php endif; ?>
                    <?php if (!empty($promo['target_url'])): ?>
                        <p style="margin-top:1rem;">
                            <a class="promos-card__button" href="/promo/click/<?php echo rawurlencode((string) $promo['slug']); ?>"><?php echo htmlspecialchars((string) ($promo['button_label'] ?: ($settings['default_button_label'] ?? 'Mehr erfahren'))); ?></a>
                        </p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>
