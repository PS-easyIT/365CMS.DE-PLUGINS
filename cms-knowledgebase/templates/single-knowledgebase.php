<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<?php
$synonymItems = array_values(array_filter(array_map(
    static fn(string $item): string => trim($item),
    preg_split('/[\r\n,]+/', (string) ($entry['synonyms'] ?? '')) ?: []
)));
$showRelatedEntries = ($settings['show_related_entries'] ?? '1') === '1';
$relatedPostsLimit = max(3, min(6, (int) ($settings['related_posts_limit'] ?? 4)));
$showKeywordBadges = ($settings['show_keyword_badges'] ?? '1') === '1';
$renderedContent = (string) ($entry['content'] ?? '');
$cmsPrefix = \CMS\Database::instance()->prefix();
$renderedContent = str_replace(
    ['{{cms_prefix}}', '{cms_prefix}', '[cms_prefix]', '%cms_prefix%', '{{table_prefix}}', '{table_prefix}'],
    $cmsPrefix,
    $renderedContent
);
$renderedContent = preg_replace(
    '/<h([2-4])>\s*Metadaten\s*<\/h\1>\s*<ul>.*?<\/ul>/isu',
    '',
    $renderedContent,
    1
) ?? $renderedContent;
if (class_exists('\\CMS\\Services\\SiteTableService')) {
    $renderedContent = \CMS\Services\SiteTableService::getInstance()->replaceShortcodes($renderedContent);
}
$showSidebar = $showKeywordBadges || !empty($entry['tooltip_text']);
$formatRelatedPostDate = static function (?string $dateValue, string $locale = 'de'): string {
    $rawValue = trim((string) $dateValue);
    if ($rawValue === '') {
        return '';
    }

    if (function_exists('phinit_format_date')) {
        try {
            return (string) phinit_format_date($rawValue, 'numeric', $locale);
        } catch (\Throwable) {
        }
    }

    $timestamp = strtotime($rawValue);
    if ($timestamp === false) {
        return '';
    }

    return $locale === 'en' ? date('m/d/Y', $timestamp) : date('d.m.Y', $timestamp);
};
$estimateRelatedReadTime = static function (array $post): int {
    $contentSource = trim((string) ($post['content'] ?? ''));
    if ($contentSource === '') {
        $contentSource = trim((string) ($post['excerpt'] ?? ''));
    }

    if ($contentSource === '') {
        return 0;
    }

    if (function_exists('phinit_reading_time')) {
        try {
            return max(1, (int) phinit_reading_time($contentSource));
        } catch (\Throwable) {
        }
    }

    $wordCount = str_word_count(strip_tags($contentSource));
    if ($wordCount <= 0) {
        return 0;
    }

    return max(1, (int) ceil($wordCount / 220));
};
?>

<main class="cms-kb-content cms-kb-content--single">
    <article class="cms-kb-article">
        <nav class="cms-kb-breadcrumbs" aria-label="Breadcrumb">
            <ol class="cms-kb-breadcrumbs__list">
                <li><a href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>">Start</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="<?php echo htmlspecialchars(SITE_URL . '/kb', ENT_QUOTES, 'UTF-8'); ?>">Knowledgebase</a></li>
                <li aria-hidden="true">/</li>
                <li><span aria-current="page"><?php echo htmlspecialchars((string) $entry['title'], ENT_QUOTES, 'UTF-8'); ?></span></li>
            </ol>
        </nav>

        <header class="cms-kb-hero cms-kb-hero--single">
            <div class="cms-kb-hero__content">
                <?php if (!empty($entry['category'])): ?>
                    <p class="cms-kb-entry__meta"><?php echo htmlspecialchars((string) $entry['category'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <h1><?php echo htmlspecialchars((string) $entry['title'], ENT_QUOTES, 'UTF-8'); ?></h1>

                <?php if (!empty($entry['excerpt'])): ?>
                    <p class="cms-kb-hero__intro"><?php echo htmlspecialchars((string) $entry['excerpt'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
            </div>
        </header>

        <div class="cms-kb-single-layout<?php echo $showSidebar ? ' has-sidebar' : ''; ?>">
            <section class="cms-kb-body">
                <div class="cms-kb-richtext">
                    <?php echo $renderedContent; ?>
                </div>

                <?php if ($showRelatedEntries && !empty($relatedPosts)): ?>
                    <section class="cms-kb-related-block" aria-labelledby="cms-kb-related-heading">
                        <h2 id="cms-kb-related-heading" class="cms-kb-related-block__title">Verwandte Artikel</h2>
                        <div class="cms-kb-related-posts">
                            <?php foreach (array_slice($relatedPosts, 0, $relatedPostsLimit) as $related): ?>
                                <?php
                                $relatedDateRaw = trim((string) ($related['published_at'] ?? $related['created_at'] ?? ''));
                                $relatedDateLabel = $formatRelatedPostDate($relatedDateRaw, $contentLocale ?? 'de');
                                $relatedDateIso = '';
                                if ($relatedDateRaw !== '') {
                                    $relatedTimestamp = strtotime($relatedDateRaw);
                                    if ($relatedTimestamp !== false) {
                                        $relatedDateIso = date(DATE_ATOM, $relatedTimestamp);
                                    }
                                }
                                $relatedReadTime = $estimateRelatedReadTime($related);
                                ?>
                                <article class="cms-kb-related-post">
                                    <p class="cms-kb-related-post__meta">
                                        <?php if (!empty($related['category_name'])): ?>
                                            <span><?php echo htmlspecialchars((string) $related['category_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php else: ?>
                                            <span>365CMS Beitrag</span>
                                        <?php endif; ?>
                                    </p>
                                    <h3 class="cms-kb-related-post__title">
                                        <a href="<?php echo htmlspecialchars((string) ($related['url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars((string) ($related['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </h3>
                                    <?php if ($relatedDateLabel !== '' || $relatedReadTime > 0): ?>
                                        <div class="cms-kb-related-post__details" aria-label="Metainformationen zum Eintrag">
                                            <?php if ($relatedDateLabel !== ''): ?>
                                                <time class="cms-kb-related-post__detail"<?php echo $relatedDateIso !== '' ? ' datetime="' . htmlspecialchars($relatedDateIso, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>><?php echo htmlspecialchars($relatedDateLabel, ENT_QUOTES, 'UTF-8'); ?></time>
                                            <?php endif; ?>
                                            <?php if ($relatedReadTime > 0): ?>
                                                <span class="cms-kb-related-post__detail"><?php echo htmlspecialchars(($contentLocale ?? 'de') === 'en' ? $relatedReadTime . ' min read' : $relatedReadTime . ' Min. Lesezeit', ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <a class="cms-kb-related-post__cta" href="<?php echo htmlspecialchars((string) ($related['url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><span aria-hidden="true">… </span>zum Eintrag</a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </section>

            <?php if ($showSidebar): ?>
            <aside class="cms-kb-sidebar">
                <?php if ($showKeywordBadges): ?>
                    <section class="cms-kb-sidebar__section">
                        <h2>Begriffsdetails</h2>
                        <ul class="cms-kb-sidebar__facts">
                            <li><strong>Keyword</strong><span><?php echo htmlspecialchars((string) $entry['keyword'], ENT_QUOTES, 'UTF-8'); ?></span></li>
                            <li><strong>Bereich</strong><span><?php echo htmlspecialchars((string) ($entry['category'] ?? 'Allgemein'), ENT_QUOTES, 'UTF-8'); ?></span></li>
                            <?php if ($synonymItems !== []): ?>
                                <li><strong>Synonyme</strong><span><?php echo htmlspecialchars(implode(', ', array_slice($synonymItems, 0, 4)), ENT_QUOTES, 'UTF-8'); ?></span></li>
                            <?php endif; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <?php if (!empty($entry['tooltip_text'])): ?>
                    <section class="cms-kb-sidebar__section">
                        <h2>Kurz erklärt</h2>
                        <p><?php echo htmlspecialchars((string) $entry['tooltip_text'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </section>
                <?php endif; ?>
            </aside>
            <?php endif; ?>
        </div>

        <footer class="cms-kb-article__footer">
            <a class="cms-kb-entry__cta" href="<?php echo htmlspecialchars(SITE_URL . '/kb', ENT_QUOTES, 'UTF-8'); ?>">← Zurück zur Übersicht</a>
        </footer>
    </article>
</main>
