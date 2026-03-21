<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<?php
$showSidebar = ($settings['show_category_sidebar'] ?? '1') === '1' && !empty($categories);
$hasFilters = $search !== '' || $category !== '';
$resultsLabel = $hasFilters ? 'Gefilterte Ergebnisse' : 'Alle Knowledgebase-Einträge';
$visibleCategories = array_slice($categories, 0, 6);
?>

<main class="cms-kb-content cms-kb-content--archive">
    <header class="cms-kb-hero">
        <div class="cms-kb-hero__content">
            <div class="cms-kb-hero__topline">
                <div class="cms-kb-hero__headline">
                    <p class="cms-kb-hero__eyebrow">Wissen & Orientierung</p>
                    <h1><?php echo htmlspecialchars((string) ($settings['archive_title'] ?? 'Knowledgebase'), ENT_QUOTES, 'UTF-8'); ?></h1>
                </div>

                <div class="cms-kb-stats" aria-label="Knowledgebase-Statistiken">
                    <div class="cms-kb-stat">
                        <strong><?php echo number_format(count($entries)); ?></strong>
                        <span>Treffer</span>
                    </div>
                    <div class="cms-kb-stat">
                        <strong><?php echo number_format(count($categories)); ?></strong>
                        <span>Bereiche</span>
                    </div>
                    <div class="cms-kb-stat">
                        <strong><?php echo $hasFilters ? 'Aktiv' : 'Offen'; ?></strong>
                        <span>Filter</span>
                    </div>
                </div>
            </div>

            <div class="cms-kb-hero__subline">
                <?php if (!empty($settings['archive_intro'])): ?>
                    <p class="cms-kb-hero__intro"><?php echo htmlspecialchars((string) ($settings['archive_intro'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <?php if ($hasFilters): ?>
                    <div class="cms-kb-chip-row" aria-label="Aktive Filter">
                        <?php if ($search !== ''): ?>
                            <span class="cms-kb-chip">Suche: <?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                        <?php if ($category !== ''): ?>
                            <span class="cms-kb-chip">Kategorie: <?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (($settings['show_search'] ?? '1') === '1'): ?>
            <nav class="cms-kb-filters cms-kb-filters--inline" aria-label="Knowledgebase-Filter">
                <form method="get" role="search" class="cms-kb-filters__form" action="<?php echo htmlspecialchars(SITE_URL . '/kb', ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="cms-kb-filters__field cms-kb-filters__field--search">
                        <label for="kb-search-query">Begriff suchen</label>
                        <input id="kb-search-query" type="search" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Begriff suchen …">
                    </div>

                    <div class="cms-kb-filters__field cms-kb-filters__field--category">
                        <label for="kb-category-filter">Kategorie</label>
                        <select id="kb-category-filter" name="category">
                            <option value="">Alle Kategorien</option>
                            <?php foreach ($categories as $item): ?>
                                <?php $categoryValue = (string) ($item['category'] ?? ''); ?>
                                <option value="<?php echo htmlspecialchars($categoryValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $category === $categoryValue ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoryValue, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="cms-kb-filters__actions">
                        <button type="submit">Suchen</button>

                        <?php if ($search !== '' || $category !== ''): ?>
                            <a href="<?php echo htmlspecialchars(SITE_URL . '/kb', ENT_QUOTES, 'UTF-8'); ?>">Zurücksetzen</a>
                        <?php endif; ?>
                    </div>
                </form>
            </nav>
            <?php endif; ?>
        </div>
    </header>

    <div class="cms-kb-archive-layout<?php echo $showSidebar ? ' has-sidebar' : ''; ?>">
        <section class="cms-kb-listing" aria-labelledby="kb-results-heading">
            <div class="cms-kb-section-head">
                <div>
                    <p class="cms-kb-section-head__eyebrow">Archiv</p>
                    <h2 id="kb-results-heading"><?php echo htmlspecialchars($resultsLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
                </div>

                <?php if (!empty($visibleCategories)): ?>
                    <div class="cms-kb-chip-row" aria-label="Beliebte Kategorien">
                        <?php foreach ($visibleCategories as $item): ?>
                            <a class="cms-kb-chip" href="<?php echo htmlspecialchars(SITE_URL . '/kb?category=' . rawurlencode((string) $item['category']), ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars((string) $item['category'], ENT_QUOTES, 'UTF-8'); ?>
                                <span><?php echo (int) ($item['entry_count'] ?? 0); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($entries)): ?>
                <div class="cms-kb-empty-state" role="status" aria-live="polite">
                    <p class="cms-kb-empty-state__icon" aria-hidden="true">🔎</p>
                    <p class="cms-kb-empty-state__title">Keine Einträge gefunden</p>
                    <p class="cms-kb-empty-state__body">Versuche es mit einem anderen Suchbegriff oder entferne den Kategorie-Filter.</p>
                </div>
            <?php else: ?>
                <div class="cms-kb-entry-grid">
                    <?php foreach ($entries as $item): ?>
                        <article class="cms-kb-entry">
                            <header class="cms-kb-entry__header">
                                <p class="cms-kb-entry__meta">
                                    <?php echo htmlspecialchars((string) ($item['category'] ?? 'Allgemein'), ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                                <h2>
                                    <a href="<?php echo htmlspecialchars(SITE_URL . '/kb/' . rawurlencode((string) $item['slug']), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </h2>
                            </header>

                            <?php if (!empty($item['excerpt'])): ?>
                                <p class="cms-kb-entry__excerpt"><?php echo htmlspecialchars((string) $item['excerpt'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>

                            <?php if (($settings['show_keyword_badges'] ?? '1') === '1'): ?>
                                <p class="cms-kb-entry__keyword">
                                    <strong>Keyword</strong>
                                    <span><?php echo htmlspecialchars((string) $item['keyword'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </p>
                            <?php endif; ?>

                            <footer class="cms-kb-entry__footer">
                                <a class="cms-kb-entry__cta" href="<?php echo htmlspecialchars(SITE_URL . '/kb/' . rawurlencode((string) $item['slug']), ENT_QUOTES, 'UTF-8'); ?>">Artikel lesen</a>
                            </footer>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($showSidebar): ?>
            <aside class="cms-kb-sidebar">
                <section class="cms-kb-sidebar__section">
                    <h2>Bereiche</h2>
                    <ul class="cms-kb-sidebar__list">
                        <li><a href="<?php echo htmlspecialchars(SITE_URL . '/kb', ENT_QUOTES, 'UTF-8'); ?>">Alle Einträge</a></li>
                        <?php foreach ($categories as $item): ?>
                            <li>
                                <a href="<?php echo htmlspecialchars(SITE_URL . '/kb?category=' . rawurlencode((string) $item['category']), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars((string) $item['category'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                                <span><?php echo (int) ($item['entry_count'] ?? 0); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>

                <section class="cms-kb-sidebar__section">
                    <h2>Starter-Hinweis</h2>
                    <p>Die Knowledgebase ist dafür gedacht, Begriffe schnell zu erklären und Inhalte intelligent miteinander zu verbinden.</p>
                </section>
            </aside>
        <?php endif; ?>
    </div>
</main>
