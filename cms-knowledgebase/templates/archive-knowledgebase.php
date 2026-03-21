<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<main class="cms-kb-content cms-kb-content--archive">
    <header class="cms-kb-header">
        <h1><?php echo htmlspecialchars((string) ($settings['archive_title'] ?? 'Knowledgebase'), ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php if (!empty($settings['archive_intro'])): ?>
            <p><?php echo htmlspecialchars((string) ($settings['archive_intro'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
    </header>

    <?php if (($settings['show_search'] ?? '1') === '1'): ?>
    <nav class="cms-kb-filters" aria-label="Knowledgebase-Filter">
        <form method="get" role="search" class="cms-kb-filters__form" action="<?php echo htmlspecialchars(SITE_URL . '/kb', ENT_QUOTES, 'UTF-8'); ?>">
            <label for="kb-search-query">Begriff suchen</label>
            <input id="kb-search-query" type="search" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Begriff suchen …">

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

            <button type="submit">Suchen</button>

            <?php if ($search !== '' || $category !== ''): ?>
                <a href="<?php echo htmlspecialchars(SITE_URL . '/kb', ENT_QUOTES, 'UTF-8'); ?>">Filter zurücksetzen</a>
            <?php endif; ?>
        </form>
    </nav>
    <?php endif; ?>

    <section class="cms-kb-listing">
        <?php if (empty($entries)): ?>
            <article class="cms-kb-entry cms-kb-entry--empty">
                <h2>Keine Einträge gefunden</h2>
                <p>Versuche es mit einem anderen Suchbegriff oder entferne den Kategorie-Filter.</p>
            </article>
        <?php else: ?>
            <?php foreach ($entries as $item): ?>
                <article class="cms-kb-entry">
                    <header class="cms-kb-entry__header">
                        <p class="cms-kb-entry__meta">
                            <?php if (!empty($item['category'])): ?>
                                <?php echo htmlspecialchars((string) $item['category'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php else: ?>
                                Allgemein
                            <?php endif; ?>
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
                        <strong>Keyword:</strong>
                        <?php echo htmlspecialchars((string) $item['keyword'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                    <?php endif; ?>

                    <p class="cms-kb-entry__link">
                        <a href="<?php echo htmlspecialchars(SITE_URL . '/kb/' . rawurlencode((string) $item['slug']), ENT_QUOTES, 'UTF-8'); ?>">Artikel lesen</a>
                    </p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <?php if (($settings['show_category_sidebar'] ?? '1') === '1' && !empty($categories)): ?>
        <aside class="cms-kb-sidebar">
            <h2>Kategorien</h2>
            <ul class="cms-kb-sidebar__list">
                <li><a href="<?php echo htmlspecialchars(SITE_URL . '/kb', ENT_QUOTES, 'UTF-8'); ?>">Alle Einträge</a></li>
                <?php foreach ($categories as $item): ?>
                    <li>
                        <a href="<?php echo htmlspecialchars(SITE_URL . '/kb?category=' . rawurlencode((string) $item['category']), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars((string) $item['category'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                        (<?php echo (int) ($item['entry_count'] ?? 0); ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>
    <?php endif; ?>
</main>
