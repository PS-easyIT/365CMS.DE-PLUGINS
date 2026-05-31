<?php
/**
 * Template: Feed-Archiv (Hauptseite)
 *
 * Zeigt alle öffentlichen Bereiche mit aktuellen Beiträgen.
 * Kann vom Theme überschrieben werden: themes/{theme}/cms-feed/archive-feed.php
 *
 * Eingebettet im CMS-Theme via ThemeManager.
 *
 * @package CMS_Feed
 */

declare(strict_types=1);
if (!defined('ABSPATH')) exit;

// $categories, $items, $settings, $pagination, $search werden vom Public-Controller bereitgestellt

$lang                  = ($lang ?? 'de') === 'en' ? 'en' : 'de';
$i18n                  = [
    'search_placeholder' => $lang === 'en' ? 'Search feeds…' : 'Feeds durchsuchen…',
    'search_button' => $lang === 'en' ? 'Search' : 'Suchen',
    'search_aria' => $lang === 'en' ? 'Search feeds' : 'Feeds durchsuchen',
    'all' => $lang === 'en' ? 'All' : 'Alle',
    'categories_aria' => $lang === 'en' ? 'Feed categories' : 'Feed-Bereiche',
    'results_for' => $lang === 'en' ? 'Results for' : 'Ergebnisse für',
    'reset' => $lang === 'en' ? 'Reset' : 'Zurücksetzen',
    'empty_search' => $lang === 'en' ? 'No posts found for this search.' : 'Keine Beiträge für diese Suche gefunden.',
    'empty_default' => $lang === 'en' ? 'No posts available yet.' : 'Noch keine Beiträge vorhanden.',
    'prev' => $lang === 'en' ? '← Previous' : '← Zurück',
    'next' => $lang === 'en' ? 'Next →' : 'Weiter →',
    'page_of' => $lang === 'en' ? 'Page %d of %d' : 'Seite %d von %d',
];
$archiveTitle          = $settings['archive_title'] ?? ($lang === 'en' ? 'Feed overview' : 'Feed-Übersicht');
$archiveDesc           = $settings['archive_description'] ?? '';
$slug                  = $settings['archive_slug'] ?? 'feeds';
$archivePath           = $archivePath ?? '/' . (preg_replace('/[^a-z0-9\-]/', '', strtolower(trim((string) $slug, '/'))) ?: 'feeds');
$publicCategoryBasePath = $publicCategoryBasePath ?? '/feed';
$newTab                = !empty($settings['open_in_new_tab']);

// Theme Header
\CMS\ThemeManager::instance()->getHeader(['title' => $archiveTitle]);
?>

<!-- Header -->
<header class="fd-header">
    <div class="fd-header__inner">
        <h1 class="fd-header__title"><?php echo htmlspecialchars((string) $archiveTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php if (!empty($archiveDesc)): ?>
        <p class="fd-header__desc"><?php echo htmlspecialchars((string) $archiveDesc, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <!-- Suche -->
        <form method="GET" action="<?php echo htmlspecialchars((string) $archivePath, ENT_QUOTES, 'UTF-8'); ?>" class="fd-search">
            <input type="text" name="q" class="fd-search__input"
                     value="<?php echo htmlspecialchars((string) ($search ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                   placeholder="<?php echo htmlspecialchars($i18n['search_placeholder'], ENT_QUOTES, 'UTF-8'); ?>">
                 <button type="submit" class="fd-search__btn" aria-label="<?php echo htmlspecialchars($i18n['search_aria'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($i18n['search_button'], ENT_QUOTES, 'UTF-8'); ?></button>
        </form>
    </div>
</header>

<main class="fd-main">

    <!-- Bereich-Navigation -->
    <?php if (!empty($categories) && count($categories) > 1): ?>
    <nav class="fd-cat-nav" aria-label="<?php echo htmlspecialchars($i18n['categories_aria'], ENT_QUOTES, 'UTF-8'); ?>">
        <a href="<?php echo htmlspecialchars((string) $archivePath, ENT_QUOTES, 'UTF-8'); ?>"
           class="fd-cat-nav__item fd-cat-nav__item--active">
            <?php echo htmlspecialchars($i18n['all'], ENT_QUOTES, 'UTF-8'); ?>
        </a>
        <?php foreach ($categories as $cat): ?>
        <a href="<?php echo htmlspecialchars(rtrim((string) $publicCategoryBasePath, '/') . '/' . rawurlencode((string) ($cat['slug'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
           class="fd-cat-nav__item">
            <span class="fd-cat-nav__icon"><?php echo htmlspecialchars((string) ($cat['icon'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
            <?php echo htmlspecialchars((string) ($cat['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <!-- Suchergebnis-Hinweis -->
    <?php if (!empty($search)): ?>
    <div class="fd-search-hint">
        <?php echo htmlspecialchars($i18n['results_for'], ENT_QUOTES, 'UTF-8'); ?> „<strong><?php echo htmlspecialchars((string) $search, ENT_QUOTES, 'UTF-8'); ?></strong>"
        <a href="<?php echo htmlspecialchars((string) $archivePath, ENT_QUOTES, 'UTF-8'); ?>" class="fd-search-hint__reset"><?php echo htmlspecialchars($i18n['reset'], ENT_QUOTES, 'UTF-8'); ?></a>
    </div>
    <?php endif; ?>

    <!-- Items -->
    <?php if (empty($items)): ?>
    <div class="fd-empty">
        <p class="fd-empty__icon">📭</p>
        <p class="fd-empty__text">
            <?php echo !empty($search) ? $i18n['empty_search'] : $i18n['empty_default']; ?>
        </p>
    </div>
    <?php else: ?>
    <div class="fd-grid">
        <?php foreach ($items as $item): ?>
        <?php CMS_Feed_Template_Loader::instance()->get_template_part('feed-card', '', ['item' => $item, 'settings' => $settings, 'lang' => $lang]); ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Paginierung -->
    <?php if (($pages ?? 1) > 1): ?>
    <div class="fd-pagination">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo max(1, (int) $page - 1); ?><?php echo !empty($search) ? '&q=' . rawurlencode((string) $search) : ''; ?>"
           class="fd-pagination__btn"><?php echo htmlspecialchars($i18n['prev'], ENT_QUOTES, 'UTF-8'); ?></a>
        <?php endif; ?>

        <span class="fd-pagination__info">
            <?php echo htmlspecialchars(sprintf($i18n['page_of'], (int) $page, (int) $pages), ENT_QUOTES, 'UTF-8'); ?>
        </span>

        <?php if ($page < $pages): ?>
        <a href="?page=<?php echo (int) $page + 1; ?><?php echo !empty($search) ? '&q=' . rawurlencode((string) $search) : ''; ?>"
           class="fd-pagination__btn"><?php echo htmlspecialchars($i18n['next'], ENT_QUOTES, 'UTF-8'); ?></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</main>

<?php \CMS\ThemeManager::instance()->getFooter(); ?>
