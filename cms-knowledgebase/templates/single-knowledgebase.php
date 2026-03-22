<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<?php
$synonymItems = array_values(array_filter(array_map(
    static fn(string $item): string => trim($item),
    preg_split('/[\r\n,]+/', (string) ($entry['synonyms'] ?? '')) ?: []
)));
$showRelatedEntries = ($settings['show_related_entries'] ?? '1') === '1';
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
$showSidebar = $showKeywordBadges || !empty($entry['tooltip_text']);
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

                <?php if ($showKeywordBadges): ?>
                    <div class="cms-kb-chip-row" aria-label="Begriffsinfos">
                        <span class="cms-kb-chip">Fokusbegriff: <?php echo htmlspecialchars((string) $entry['keyword'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php foreach (array_slice($synonymItems, 0, 3) as $synonym): ?>
                            <span class="cms-kb-chip cms-kb-chip--muted"><?php echo htmlspecialchars($synonym, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="cms-kb-stats" aria-label="Artikel-Highlights">
                <div class="cms-kb-stat">
                    <strong><?php echo number_format(count($synonymItems) + 1); ?></strong>
                    <span>Begriffe</span>
                </div>
                <div class="cms-kb-stat">
                    <strong><?php echo $showRelatedEntries ? number_format(count($relatedPosts ?? [])) : '—'; ?></strong>
                    <span>Verwandt</span>
                </div>
                <div class="cms-kb-stat">
                    <strong><?php echo htmlspecialchars((string) ($entry['category'] ?? 'Allgemein'), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span>Bereich</span>
                </div>
            </div>
        </header>

        <div class="cms-kb-single-layout<?php echo $showSidebar ? ' has-sidebar' : ''; ?>">
            <section class="cms-kb-body">
                <?php if ($showKeywordBadges): ?>
                    <div class="cms-kb-inline-note">
                        <strong>Fokusbegriff:</strong>
                        <span><?php echo htmlspecialchars((string) $entry['keyword'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php if ($synonymItems !== []): ?>
                            <span class="cms-kb-inline-note__sep">•</span>
                            <span><?php echo number_format(count($synonymItems)); ?> Synonyme hinterlegt</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="cms-kb-richtext">
                    <?php echo $renderedContent; ?>
                </div>

                <?php if ($showRelatedEntries && !empty($relatedPosts)): ?>
                    <section class="cms-kb-related-block cms-kb-sidebar__section" aria-labelledby="cms-kb-related-heading">
                        <h2 id="cms-kb-related-heading">Verwandte Artikel</h2>
                        <div class="cms-kb-related-posts">
                            <?php foreach ($relatedPosts as $related): ?>
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
                                    <?php if (!empty($related['relevance_signals']) && is_array($related['relevance_signals'])): ?>
                                        <div class="cms-kb-chip-row" aria-label="Relevanzsignale">
                                            <?php foreach (array_slice($related['relevance_signals'], 0, 3) as $signal): ?>
                                                <span class="cms-kb-chip cms-kb-chip--muted"><?php echo htmlspecialchars((string) $signal, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($related['excerpt'])): ?>
                                        <p class="cms-kb-related-post__excerpt"><?php echo htmlspecialchars((string) $related['excerpt'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endif; ?>
                                    <a class="cms-kb-related-post__cta" href="<?php echo htmlspecialchars((string) ($related['url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">Beitrag lesen</a>
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
