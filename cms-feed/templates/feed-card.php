<?php
/**
 * Template: Feed-Card (einzelner Beitrag)
 *
 * Wiederverwendbare Karte für Grid-, Listen- und Magazin-Layout.
 * Kann vom Theme überschrieben werden: themes/{theme}/cms-feed/feed-card.php
 *
 * Variablen:
 *   $item          – Array mit Beitrags-Daten
 *   $settings      – Plugin-Einstellungen
 *   $isMagazineHero – (optional) true = große Hero-Card im Magazin-Layout
 *
 * @package CMS_Feed
 */

declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$showSource  = !empty($settings['show_source']);
$showDate    = !empty($settings['show_date']);
$showImage   = !empty($settings['show_image']);
$showExcerpt = !empty($settings['show_excerpt']);
$newTab      = !empty($settings['open_in_new_tab']);
$excerptLen  = (int)($settings['excerpt_length'] ?? 160);
$isHero      = !empty($isMagazineHero);

$link        = $item['link'] ?? '#';
$title       = $item['title'] ?? 'Ohne Titel';
$imageUrl    = $item['image_url'] ?? '';
$author      = $item['author'] ?? '';
$channelName = $item['channel_name'] ?? '';
$catName     = $item['category_name'] ?? '';
$pubDate     = $item['pub_date'] ?? '';
$description = $item['description'] ?? '';
$isFeatured  = (int)($item['is_featured'] ?? 0);

// Kurzzusammenfassung
$excerpt = strip_tags($description);
if (mb_strlen($excerpt) > $excerptLen) {
    $excerpt = mb_substr($excerpt, 0, $excerptLen) . '…';
}
?>
<article class="fd-card<?php echo $isHero ? ' fd-card--hero' : ''; ?><?php echo $isFeatured ? ' fd-card--featured' : ''; ?>">
    <?php if ($showImage && !empty($imageUrl)): ?>
    <a href="<?php echo htmlspecialchars($link); ?>"
       class="fd-card__image-link"
       <?php echo $newTab ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
        <img src="<?php echo htmlspecialchars($imageUrl); ?>"
             alt="<?php echo htmlspecialchars($title); ?>"
             class="fd-card__image"
             loading="lazy">
    </a>
    <?php endif; ?>

    <div class="fd-card__body">
        <?php if ($isFeatured): ?>
        <span class="fd-card__badge">⭐ Featured</span>
        <?php endif; ?>

        <h3 class="fd-card__title">
            <a href="<?php echo htmlspecialchars($link); ?>"
               <?php echo $newTab ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                <?php echo htmlspecialchars($title); ?>
            </a>
        </h3>

        <?php if ($showExcerpt && !empty($excerpt)): ?>
        <p class="fd-card__excerpt"><?php echo htmlspecialchars($excerpt); ?></p>
        <?php endif; ?>

        <div class="fd-card__meta">
            <?php if ($showSource && !empty($channelName)): ?>
            <span class="fd-card__source"><?php echo htmlspecialchars($channelName); ?></span>
            <?php endif; ?>

            <?php if ($showDate && !empty($pubDate)): ?>
            <time class="fd-card__date" datetime="<?php echo htmlspecialchars($pubDate); ?>">
                <?php echo date('d.m.Y', strtotime($pubDate)); ?>
            </time>
            <?php endif; ?>

            <?php if (!empty($author) && $showSource): ?>
            <span class="fd-card__author">von <?php echo htmlspecialchars($author); ?></span>
            <?php endif; ?>
        </div>
    </div>
</article>
