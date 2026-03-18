<?php
/**
 * Template: Feed-Archiv (Hauptseite)
 *
 * Zeigt alle öffentlichen Bereiche mit aktuellen Beiträgen.
 * Kann vom Theme überschrieben werden: themes/{theme}/cms-feed/archive-feed.php
 *
 * @package CMS_Feed
 */

declare(strict_types=1);
if (!defined('ABSPATH')) exit;

// $categories, $items, $settings, $pagination, $search werden vom Public-Controller bereitgestellt

$archiveTitle = $settings['archive_title'] ?? 'Feed-Übersicht';
$archiveDesc  = $settings['archive_description'] ?? '';
$slug         = $settings['archive_slug'] ?? 'feeds';
$newTab       = !empty($settings['open_in_new_tab']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($archiveTitle); ?> – <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <?php \CMS\Hooks::doAction('head'); ?>
</head>
<body class="fd-body">

<?php \CMS\Hooks::doAction('body_start'); ?>

<!-- Header -->
<header class="fd-header">
    <div class="fd-header__inner">
        <h1 class="fd-header__title"><?php echo htmlspecialchars($archiveTitle); ?></h1>
        <?php if (!empty($archiveDesc)): ?>
        <p class="fd-header__desc"><?php echo htmlspecialchars($archiveDesc); ?></p>
        <?php endif; ?>

        <!-- Suche -->
        <form method="GET" action="/<?php echo htmlspecialchars($slug); ?>" class="fd-search">
            <input type="text" name="q" class="fd-search__input"
                   value="<?php echo htmlspecialchars($search ?? ''); ?>"
                   placeholder="Feeds durchsuchen…">
            <button type="submit" class="fd-search__btn">🔍</button>
        </form>
    </div>
</header>

<main class="fd-main">

    <!-- Bereich-Navigation -->
    <?php if (!empty($categories) && count($categories) > 1): ?>
    <nav class="fd-cat-nav" aria-label="Feed-Bereiche">
        <a href="/<?php echo htmlspecialchars($slug); ?>"
           class="fd-cat-nav__item fd-cat-nav__item--active">
            Alle
        </a>
        <?php foreach ($categories as $cat): ?>
        <a href="/<?php echo htmlspecialchars($slug . '/' . $cat['slug']); ?>"
           class="fd-cat-nav__item">
            <span class="fd-cat-nav__icon"><?php echo htmlspecialchars($cat['icon']); ?></span>
            <?php echo htmlspecialchars($cat['name']); ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <!-- Suchergebnis-Hinweis -->
    <?php if (!empty($search)): ?>
    <div class="fd-search-hint">
        Ergebnisse für „<strong><?php echo htmlspecialchars($search); ?></strong>"
        <a href="/<?php echo htmlspecialchars($slug); ?>" class="fd-search-hint__reset">✕ Zurücksetzen</a>
    </div>
    <?php endif; ?>

    <!-- Items -->
    <?php if (empty($items)): ?>
    <div class="fd-empty">
        <p class="fd-empty__icon">📭</p>
        <p class="fd-empty__text">
            <?php echo !empty($search) ? 'Keine Beiträge für diese Suche gefunden.' : 'Noch keine Beiträge vorhanden.'; ?>
        </p>
    </div>
    <?php else: ?>
    <div class="fd-grid">
        <?php foreach ($items as $item): ?>
        <?php include CMS_Feed_Template_Loader::instance()->locate_template('feed-card.php'); ?>
        <?php endforeach; ?>
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

<?php \CMS\Hooks::doAction('body_end'); ?>
</body>
</html>
