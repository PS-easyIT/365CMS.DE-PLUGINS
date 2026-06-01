<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>
<?php
$dlLabel = static function (string $key) use ($publicLang): string {
    $labels = [
        'search_placeholder' => ['de' => 'Downloads durchsuchen', 'en' => 'Search downloads'],
        'search_button' => ['de' => 'Suchen', 'en' => 'Search'],
        'all_categories' => ['de' => 'Alle', 'en' => 'All'],
        'category_label' => ['de' => 'Kategorie', 'en' => 'Category'],
        'empty_title' => ['de' => 'Keine Downloads gefunden', 'en' => 'No downloads found'],
        'empty_body' => ['de' => 'Für diese Auswahl sind aktuell noch keine öffentlichen Dateien hinterlegt.', 'en' => 'There are currently no public files available for this selection.'],
        'download_now' => ['de' => 'Jetzt laden', 'en' => 'Download now'],
        'version' => ['de' => 'Version', 'en' => 'Version'],
        'downloads_count' => ['de' => 'Downloads', 'en' => 'Downloads'],
    ];
    $lang = $publicLang ?? 'de';
    return (string) ($labels[$key][$lang] ?? $labels[$key]['de'] ?? $key);
};
$dlPath = static function (string $path) use ($publicLang): string {
    if (function_exists('cms_plugin_public_localized_path')) {
        return cms_plugin_public_localized_path($path, $publicLang ?? 'de');
    }
    return '/' . trim($path, '/');
};
?>
<main class="dl-archive-main">
    <header class="dl-archive-header">
        <div>
            <h1><?php echo htmlspecialchars((string) ($archiveTitle ?? ($settings['archive_title'] ?? 'Downloads')), ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="dl-archive-summary"><?php echo htmlspecialchars((string) ($archiveDescription ?? ($settings['archive_description'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <?php if (!empty($currentCategory)): ?>
            <div class="dl-category-chip">
                <?php echo htmlspecialchars($dlLabel('category_label'), ENT_QUOTES, 'UTF-8'); ?>: <?php echo htmlspecialchars((string) ($currentCategory['name'] ?? 'Kategorie'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
    </header>

    <?php if (($settings['show_category_overview'] ?? '1') === '1' && !empty($categories)): ?>
        <nav class="dl-filter-nav" aria-label="Download-Kategorien">
            <a href="<?php echo htmlspecialchars(rtrim((string) SITE_URL, '/') . $dlPath('downloads'), ENT_QUOTES, 'UTF-8'); ?>" class="dl-filter-link<?php echo empty($currentCategory) ? ' is-active' : ''; ?>"><?php echo htmlspecialchars($dlLabel('all_categories'), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php foreach ($categories as $category): ?>
                <a href="<?php echo htmlspecialchars(rtrim((string) SITE_URL, '/') . $dlPath('downloads/category/' . rawurlencode((string) $category['slug'])), ENT_QUOTES, 'UTF-8'); ?>" class="dl-filter-link<?php echo (($currentCategory['slug'] ?? '') === ($category['slug'] ?? '')) ? ' is-active' : ''; ?>">
                    <?php echo htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if (($settings['show_search'] ?? '1') === '1'): ?>
        <section class="dl-archive-search">
            <form role="search" method="get">
                <input type="search" name="q" value="<?php echo htmlspecialchars((string) $search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars($dlLabel('search_placeholder'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($dlLabel('search_placeholder'), ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="dl-download-button dl-search-button"><?php echo htmlspecialchars($dlLabel('search_button'), ENT_QUOTES, 'UTF-8'); ?></button>
            </form>
        </section>
    <?php endif; ?>

    <?php if (empty($downloads)): ?>
        <section class="dl-empty-state" role="status" aria-live="polite">
            <p><strong><?php echo htmlspecialchars($dlLabel('empty_title'), ENT_QUOTES, 'UTF-8'); ?></strong></p>
            <p class="dl-empty-body"><?php echo htmlspecialchars($dlLabel('empty_body'), ENT_QUOTES, 'UTF-8'); ?></p>
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
                                <span class="dl-type-badge"><?php echo htmlspecialchars((string) ($typeConfig['label'] ?? 'Download'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <h2 class="dl-card-title"><?php echo htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            </div>
                            <?php if (!empty($item['category_name'])): ?>
                                <span class="dl-card-meta"><?php echo htmlspecialchars((string) $item['category_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($item['summary'])): ?>
                            <p class="dl-card-summary"><?php echo htmlspecialchars((string) $item['summary'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>

                        <div class="dl-card-meta">
                            <?php if (!empty($item['version_label'])): ?>
                                <span><?php echo htmlspecialchars($dlLabel('version'), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars((string) $item['version_label'], ENT_QUOTES, 'UTF-8'); ?></span><br>
                            <?php endif; ?>
                            <?php if (!empty($item['file_ext'])): ?>
                                <span><?php echo htmlspecialchars(strtoupper((string) $item['file_ext']), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['file_size'])): ?>
                                <span> · <?php echo number_format(((int) $item['file_size']) / 1048576, 2, ',', '.'); ?> MB</span>
                            <?php endif; ?>
                            <?php if (!empty($item['download_count'])): ?>
                                <span><br><?php echo number_format((int) $item['download_count']); ?> <?php echo htmlspecialchars($dlLabel('downloads_count'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </div>

                        <footer class="dl-card-footer">
                            <span class="dl-card-meta"><?php echo htmlspecialchars((string) ($typeConfig['hint'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            <a href="<?php echo htmlspecialchars(rtrim((string) SITE_URL, '/') . $dlPath('downloads/file/' . rawurlencode((string) $item['slug'])), ENT_QUOTES, 'UTF-8'); ?>" class="dl-download-button">
                                <?php echo htmlspecialchars($dlLabel('download_now'), ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>
