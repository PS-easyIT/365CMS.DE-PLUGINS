<?php
/**
 * Template: Bereich-Archiv (Einzelbereich)
 *
 * Zeigt Beiträge einer einzelnen Kategorie / eines Bereichs.
 * Kann vom Theme überschrieben werden: themes/{theme}/cms-feed/archive-category.php
 *
 * Eingebettet im CMS-Theme via ThemeManager.
 *
 * @package CMS_Feed
 */

declare(strict_types=1);
if (!defined('ABSPATH')) exit;

// $category, $items, $settings, $pagination, $search werden vom Public-Controller bereitgestellt

$archiveSlug        = $settings['archive_slug'] ?? 'feeds';
$archivePath        = $archivePath ?? '/' . trim((string) $archiveSlug, '/');
$publicCategoryPath = $publicCategoryPath ?? '/feed/' . rawurlencode((string) ($category['slug'] ?? ''));
$layout             = $category['layout'] ?? 'grid';
$newTab             = !empty($settings['open_in_new_tab']);

// Theme Header
\CMS\ThemeManager::instance()->getHeader(['title' => $category['name']]);
?>

<!-- Header -->
<header class="fd-header">
    <div class="fd-header__inner">
        <div class="fd-header__breadcrumb">
            <a href="<?php echo htmlspecialchars($archivePath); ?>">← Alle Feeds</a>
        </div>
        <h1 class="fd-header__title">
            <span class="fd-header__icon"><?php echo htmlspecialchars($category['icon']); ?></span>
            <?php echo htmlspecialchars($category['name']); ?>
        </h1>
        <?php if (!empty($category['description'])): ?>
        <p class="fd-header__desc"><?php echo htmlspecialchars($category['description']); ?></p>
        <?php endif; ?>

        <!-- Suche -->
        <form method="GET" action="<?php echo htmlspecialchars($publicCategoryPath); ?>" class="fd-search">
            <input type="text" name="q" class="fd-search__input"
                   value="<?php echo htmlspecialchars($search ?? ''); ?>"
                   placeholder="In <?php echo htmlspecialchars($category['name']); ?> suchen…">
            <button type="submit" class="fd-search__btn">🔍</button>
        </form>
    </div>
</header>

<main class="fd-main">

    <!-- Suchergebnis-Hinweis -->
    <?php if (!empty($search)): ?>
    <div class="fd-search-hint">
        Ergebnisse für „<strong><?php echo htmlspecialchars($search); ?></strong>"
        <a href="<?php echo htmlspecialchars($publicCategoryPath); ?>" style="margin-left:.5rem;">✕ Zurücksetzen</a>
    </div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
    <div class="fd-empty">
        <p class="fd-empty__icon"><?php echo htmlspecialchars($category['icon']); ?></p>
        <p class="fd-empty__text">
            <?php echo !empty($search) ? 'Keine Beiträge für diese Suche gefunden.' : 'Noch keine Beiträge in diesem Bereich.'; ?>
        </p>
    </div>
    <?php else: ?>

    <!-- Layout-Switch: grid / list / magazine -->
    <div class="fd-<?php echo htmlspecialchars($layout); ?>">
        <?php
        $isFirst = true;
        foreach ($items as $item):
            $isMagazineHero = ($layout === 'magazine' && $isFirst);
        ?>
        <?php if ($isMagazineHero): ?>
            <?php include CMS_Feed_Template_Loader::instance()->locate_template('feed-card.php'); ?>
        <?php else: ?>
            <?php include CMS_Feed_Template_Loader::instance()->locate_template('feed-card.php'); ?>
        <?php endif; ?>
        <?php
            $isFirst = false;
        endforeach;
        ?>
    </div>
    <?php endif; ?>

    <!-- Paginierung -->
    <?php if (($pages ?? 1) > 1): ?>
    <div class="fd-pagination">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&q=' . urlencode($search) : ''; ?>"
           class="fd-pagination__btn">← Zurück</a>
        <?php endif; ?>

        <span class="fd-pagination__info">
            Seite <?php echo $page; ?> von <?php echo $pages; ?>
        </span>

        <?php if ($page < $pages): ?>
        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&q=' . urlencode($search) : ''; ?>"
           class="fd-pagination__btn">Weiter →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</main>

<?php \CMS\ThemeManager::instance()->getFooter(); ?>
