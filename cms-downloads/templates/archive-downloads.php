<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>
<main class="dl-archive-main">
    <header class="dl-archive-header">
        <div>
            <h1><?php echo htmlspecialchars((string) ($settings['archive_title'] ?? 'Downloads')); ?></h1>
            <p class="dl-archive-summary"><?php echo htmlspecialchars((string) ($settings['archive_description'] ?? '')); ?></p>
        </div>
        <?php if (!empty($currentCategory)): ?>
            <div class="dl-category-chip">
                <?php echo htmlspecialchars((string) ($currentCategory['icon'] ?? '📁')); ?>
                <?php echo htmlspecialchars((string) ($currentCategory['name'] ?? 'Kategorie')); ?>
            </div>
        <?php endif; ?>
    </header>

    <?php if (($settings['show_category_overview'] ?? '1') === '1' && !empty($categories)): ?>
        <nav class="dl-filter-nav" aria-label="Download-Kategorien">
            <a href="<?php echo htmlspecialchars(SITE_URL . '/downloads'); ?>" class="dl-filter-link<?php echo empty($currentCategory) ? ' is-active' : ''; ?>">Alle</a>
            <?php foreach ($categories as $category): ?>
                <a href="<?php echo htmlspecialchars(SITE_URL . '/downloads/category/' . rawurlencode((string) $category['slug'])); ?>" class="dl-filter-link<?php echo (($currentCategory['slug'] ?? '') === ($category['slug'] ?? '')) ? ' is-active' : ''; ?>">
                    <?php echo htmlspecialchars((string) ($category['icon'] ?? '📁')); ?>
                    <?php echo htmlspecialchars((string) $category['name']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if (($settings['show_search'] ?? '1') === '1'): ?>
        <section class="dl-archive-search">
            <form role="search" method="get">
                <input type="search" name="q" value="<?php echo htmlspecialchars((string) $search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Downloads durchsuchen" aria-label="Downloads durchsuchen">
                <button type="submit" class="dl-download-button dl-search-button">🔍 Suchen</button>
            </form>
        </section>
    <?php endif; ?>

    <?php if (empty($downloads)): ?>
        <section class="dl-empty-state" role="status" aria-live="polite">
            <p class="dl-empty-icon" aria-hidden="true">📭</p>
            <p><strong>Keine Downloads gefunden</strong></p>
            <p class="dl-empty-body">Für diese Auswahl sind aktuell noch keine öffentlichen Dateien hinterlegt.</p>
        </section>
    <?php else: ?>
        <section class="dl-download-grid-section">
            <div class="dl-download-grid">
                <?php foreach ($downloads as $item): ?>
                    <?php $typeKey = (string) ($item['download_type'] ?? 'generic'); ?>
                    <?php $typeConfig = $typeTemplates[$typeKey] ?? $typeTemplates['generic']; ?>
                    <article class="dl-card">
                        <div class="dl-card-head">
                            <div>
                                <span class="dl-type-badge"><?php echo htmlspecialchars((string) ($typeConfig['icon'] ?? '⬇️')); ?> <?php echo htmlspecialchars((string) ($typeConfig['label'] ?? 'Download')); ?></span>
                                <h2 class="dl-card-title"><?php echo htmlspecialchars((string) $item['title']); ?></h2>
                            </div>
                            <?php if (!empty($item['category_name'])): ?>
                                <span class="dl-card-meta"><?php echo htmlspecialchars((string) ($item['category_icon'] ?? '📁')); ?> <?php echo htmlspecialchars((string) $item['category_name']); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($item['summary'])): ?>
                            <p class="dl-card-summary"><?php echo htmlspecialchars((string) $item['summary']); ?></p>
                        <?php endif; ?>

                        <div class="dl-card-meta">
                            <?php if (!empty($item['version_label'])): ?>
                                <span>🏷️ Version <?php echo htmlspecialchars((string) $item['version_label']); ?></span><br>
                            <?php endif; ?>
                            <?php if (!empty($item['file_ext'])): ?>
                                <span>📎 <?php echo htmlspecialchars(strtoupper((string) $item['file_ext'])); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['file_size'])): ?>
                                <span> · <?php echo number_format(((int) $item['file_size']) / 1048576, 2, ',', '.'); ?> MB</span>
                            <?php endif; ?>
                            <?php if (!empty($item['download_count'])): ?>
                                <span><br>📥 <?php echo number_format((int) $item['download_count']); ?> Downloads</span>
                            <?php endif; ?>
                        </div>

                        <footer class="dl-card-footer">
                            <span class="dl-card-meta"><?php echo htmlspecialchars((string) ($typeConfig['hint'] ?? '')); ?></span>
                            <a href="<?php echo htmlspecialchars(SITE_URL . '/downloads/file/' . rawurlencode((string) $item['slug'])); ?>" class="dl-download-button">
                                ⬇️ Jetzt laden
                            </a>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if (($totalPages ?? 1) > 1): ?>
            <nav class="dl-filter-nav" aria-label="Download-Seiten">
                <?php if (($currentPage ?? 1) > 1): ?>
                    <a class="dl-filter-link" href="?<?php echo htmlspecialchars(http_build_query(array_filter(['q' => $search !== '' ? $search : null, 'page' => ($currentPage - 1)]))); ?>">← Zurück</a>
                <?php endif; ?>
                <span class="dl-category-chip">Seite <?php echo (int) $currentPage; ?> von <?php echo (int) $totalPages; ?></span>
                <?php if (($currentPage ?? 1) < ($totalPages ?? 1)): ?>
                    <a class="dl-filter-link" href="?<?php echo htmlspecialchars(http_build_query(array_filter(['q' => $search !== '' ? $search : null, 'page' => ($currentPage + 1)]))); ?>">Weiter →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>
