<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<main class="cms-kb-content cms-kb-content--single">
    <article class="cms-kb-article">
        <nav class="cms-kb-breadcrumbs" aria-label="Breadcrumb">
            <a href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>">Start</a>
            <span>/</span>
            <a href="<?php echo htmlspecialchars(SITE_URL . '/kb', ENT_QUOTES, 'UTF-8'); ?>">Knowledgebase</a>
            <span>/</span>
            <span aria-current="page"><?php echo htmlspecialchars((string) $entry['title'], ENT_QUOTES, 'UTF-8'); ?></span>
        </nav>

        <header class="cms-kb-header cms-kb-header--single">
            <?php if (!empty($entry['category'])): ?>
                <p class="cms-kb-entry__meta"><?php echo htmlspecialchars((string) $entry['category'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <h1><?php echo htmlspecialchars((string) $entry['title'], ENT_QUOTES, 'UTF-8'); ?></h1>

            <?php if (!empty($entry['excerpt'])): ?>
                <p><?php echo htmlspecialchars((string) $entry['excerpt'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
        </header>

        <section class="cms-kb-body">
            <?php if (($settings['show_keyword_badges'] ?? '1') === '1'): ?>
            <p class="cms-kb-entry__keyword">
                <strong>Fokusbegriff:</strong>
                <?php echo htmlspecialchars((string) $entry['keyword'], ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <?php endif; ?>

            <?php if (($settings['show_keyword_badges'] ?? '1') === '1' && !empty($entry['synonyms'])): ?>
                <p class="cms-kb-entry__synonyms"><strong>Synonyme:</strong> vorhanden</p>
            <?php endif; ?>

            <div class="cms-kb-richtext">
                <?php echo (string) ($entry['content'] ?? ''); ?>
            </div>
        </section>

        <?php $showRelatedEntries = ($settings['show_related_entries'] ?? '1') === '1'; ?>
        <?php if (!empty($entry['tooltip_text']) || ($showRelatedEntries && !empty($relatedEntries))): ?>
            <aside class="cms-kb-sidebar">
                <?php if (!empty($entry['tooltip_text'])): ?>
                    <section class="cms-kb-sidebar__section">
                        <h2>Kurz erklärt</h2>
                        <p><?php echo htmlspecialchars((string) $entry['tooltip_text'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </section>
                <?php endif; ?>

                <?php if ($showRelatedEntries && !empty($relatedEntries)): ?>
                    <section class="cms-kb-sidebar__section">
                        <h2>Passende Einträge</h2>
                        <ul class="cms-kb-sidebar__list">
                            <?php foreach ($relatedEntries as $related): ?>
                                <li>
                                    <a href="<?php echo htmlspecialchars(SITE_URL . '/kb/' . rawurlencode((string) $related['slug']), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars((string) $related['title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
            </aside>
        <?php endif; ?>
    </article>
</main>
