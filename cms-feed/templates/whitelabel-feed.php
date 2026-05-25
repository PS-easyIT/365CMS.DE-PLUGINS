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

$archiveTitle           = $settings['archive_title'] ?? 'Feed-Übersicht';
$archiveDesc            = $settings['archive_description'] ?? '';
$slug                   = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim((string) ($settings['archive_slug'] ?? 'feeds'), '/'))) ?: 'feeds';
$slug                   = $slug === 'feed' ? 'feeds' : $slug;
$archivePath            = $archivePath ?? '/' . $slug;
$publicCategoryBasePath = $publicCategoryBasePath ?? '/feed';
$newTab                 = !empty($settings['open_in_new_tab']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((string) $archiveTitle, ENT_QUOTES, 'UTF-8'); ?> – <?php echo htmlspecialchars((string) SITE_NAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <?php \CMS\Hooks::doAction('head'); ?>
</head>
<body class="fd-body">

<?php \CMS\Hooks::doAction('body_start'); ?>

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
                   placeholder="Feeds durchsuchen…">
            <button type="submit" class="fd-search__btn" aria-label="Feeds durchsuchen">Suchen</button>
        </form>
    </div>
</header>

<main class="fd-main">

    <!-- Bereich-Navigation -->
    <?php if (!empty($categories) && count($categories) > 1): ?>
    <nav class="fd-cat-nav" aria-label="Feed-Bereiche">
        <a href="<?php echo htmlspecialchars((string) $archivePath, ENT_QUOTES, 'UTF-8'); ?>"
           class="fd-cat-nav__item fd-cat-nav__item--active">
            Alle
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
        Ergebnisse für „<strong><?php echo htmlspecialchars((string) $search, ENT_QUOTES, 'UTF-8'); ?></strong>"
        <a href="<?php echo htmlspecialchars((string) $archivePath, ENT_QUOTES, 'UTF-8'); ?>" class="fd-search-hint__reset">Zurücksetzen</a>
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
        <?php CMS_Feed_Template_Loader::instance()->get_template_part('feed-card', '', ['item' => $item, 'settings' => $settings]); ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Paginierung -->
    <?php if (($pages ?? 1) > 1): ?>
    <div class="fd-pagination">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo max(1, (int) $page - 1); ?><?php echo !empty($search) ? '&q=' . rawurlencode((string) $search) : ''; ?>"
           class="fd-pagination__btn">← Zurück</a>
        <?php endif; ?>

        <span class="fd-pagination__info">
            Seite <?php echo (int) $page; ?> von <?php echo (int) $pages; ?>
        </span>

        <?php if ($page < $pages): ?>
        <a href="?page=<?php echo (int) $page + 1; ?><?php echo !empty($search) ? '&q=' . rawurlencode((string) $search) : ''; ?>"
           class="fd-pagination__btn">Weiter →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</main>

<?php \CMS\Hooks::doAction('body_end'); ?>
</body>
</html>
