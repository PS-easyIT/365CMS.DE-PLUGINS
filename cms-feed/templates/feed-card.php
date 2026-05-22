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

$sanitizeFeedUrl = static function (mixed $value): string {
    $url = trim((string) $value);
    if ($url === '' || strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)) {
        return '';
    }
    $parts = parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host']) || !in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
        return '';
    }
    if (!empty($parts['user']) || !empty($parts['pass'])) {
        return '';
    }
    $host = strtolower(trim((string) $parts['host'], '[]'));
    if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
        return '';
    }
    if (filter_var($host, FILTER_VALIDATE_IP) && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return '';
    }
    return $url;
};

$link        = $sanitizeFeedUrl($item['link'] ?? '') ?: '#';
$title       = $item['title'] ?? 'Ohne Titel';
$imageUrl    = $sanitizeFeedUrl($item['image_url'] ?? '');
$author      = $item['author'] ?? '';
$channelName = $item['channel_name'] ?? '';
$catName     = $item['category_name'] ?? '';
$pubDate     = $item['pub_date'] ?? '';
$description = $item['description'] ?? '';
$isFeatured  = (int)($item['is_featured'] ?? 0);

// Kurzzusammenfassung
$excerpt = strip_tags($description);
if (cms_feed_strlen($excerpt) > $excerptLen) {
    $excerpt = cms_feed_substr($excerpt, 0, $excerptLen) . '…';
}
?>
<article class="fd-card<?php echo $isHero ? ' fd-card--hero' : ''; ?><?php echo $isFeatured ? ' fd-card--featured' : ''; ?>">
    <?php if ($showImage && !empty($imageUrl)): ?>
    <a href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>"
       class="fd-card__image-link"
       <?php echo $newTab ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
           <img src="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>"
               alt="<?php echo htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8'); ?>"
             class="fd-card__image"
             loading="lazy">
    </a>
    <?php endif; ?>

    <div class="fd-card__body">
        <?php if ($isFeatured): ?>
        <span class="fd-card__badge">Featured</span>
        <?php endif; ?>

        <h3 class="fd-card__title">
            <a href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>"
               <?php echo $newTab ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                <?php echo htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </h3>

        <?php if ($showExcerpt && !empty($excerpt)): ?>
        <p class="fd-card__excerpt"><?php echo htmlspecialchars((string) $excerpt, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <div class="fd-card__meta">
            <?php if ($showSource && !empty($channelName)): ?>
            <span class="fd-card__source"><?php echo htmlspecialchars((string) $channelName, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>

            <?php if ($showDate && !empty($pubDate)): ?>
            <time class="fd-card__date" datetime="<?php echo htmlspecialchars((string) $pubDate, ENT_QUOTES, 'UTF-8'); ?>">
                <?php $pubTs = strtotime((string) $pubDate); echo $pubTs ? date('d.m.Y', $pubTs) : ''; ?>
            </time>
            <?php endif; ?>

            <?php if (!empty($author) && $showSource): ?>
            <span class="fd-card__author">von <?php echo htmlspecialchars((string) $author, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
        </div>
    </div>
</article>
