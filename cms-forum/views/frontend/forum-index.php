<?php
/**
 * CMS Forum – Forum-Übersicht
 *
 * Zeigt alle Kategorien mit ihren Foren.
 *
 * Verfügbare Variablen:
 *   $categories  – Array der aktiven Kategorien
 *   $grouped     – Array: [category_id => [Forum-Objekte]]
 *   $stats       – Object: total_threads, total_posts, total_users
 *   $pageTitle   – String
 *
 * @package CMS_Forum\Views
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS_Forum\Helpers\TimeHelper;
?>

<div class="cmsforum">
    <div class="cmsforum-container">

        <!-- Breadcrumb -->
        <nav class="cmsforum-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo SITE_URL; ?>/">Startseite</a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <span class="cmsforum-breadcrumb__current" aria-current="page">Forum</span>
        </nav>

        <!-- Header -->
        <div class="cmsforum-page-header">
            <h1 class="cmsforum-page-header__title">💬 Forum</h1>
            <div class="cmsforum-page-header__actions">
                <a href="<?php echo SITE_URL; ?>/forum/search" class="cmsforum-btn cmsforum-btn--secondary">🔍 Suche</a>
            </div>
        </div>

        <!-- Statistik-Leiste -->
        <div class="cmsforum-stats-bar">
            <span class="cmsforum-stats-bar__item">📝 <?php echo number_format((int)$stats->total_threads); ?> Threads</span>
            <span class="cmsforum-stats-bar__item">💬 <?php echo number_format((int)$stats->total_posts); ?> Beiträge</span>
            <span class="cmsforum-stats-bar__item">👥 <?php echo number_format((int)$stats->total_users); ?> Benutzer</span>
        </div>

        <!-- Kategorien -->
        <?php if (empty($categories)): ?>
            <div class="cmsforum-empty">
                <p class="cmsforum-empty__icon">📭</p>
                <p class="cmsforum-empty__text">Noch keine Foren vorhanden.</p>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $cat): ?>
                <?php $catForums = $grouped[(int)$cat->id] ?? []; ?>
                <div class="cmsforum-category">
                    <div class="cmsforum-category__header">
                        <h2 class="cmsforum-category__title"><?php echo htmlspecialchars($cat->name); ?></h2>
                        <?php if (!empty($cat->description)): ?>
                            <p class="cmsforum-category__desc"><?php echo htmlspecialchars($cat->description); ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($catForums)): ?>
                        <div class="cmsforum-category__empty">Keine Foren in dieser Kategorie.</div>
                    <?php else: ?>
                        <div class="cmsforum-forum-list">
                            <?php foreach ($catForums as $forum): ?>
                            <a href="<?php echo SITE_URL; ?>/forum/<?php echo htmlspecialchars($forum->slug); ?>" class="cmsforum-forum-card">
                                <div class="cmsforum-forum-card__icon">
                                    <svg class="cmsforum-icon" width="24" height="24"><use href="<?php echo CMS_FORUM_URL; ?>assets/icons/icons.svg#icon-forum"></use></svg>
                                </div>
                                <div class="cmsforum-forum-card__body">
                                    <h3 class="cmsforum-forum-card__title"><?php echo htmlspecialchars($forum->name); ?></h3>
                                    <?php if (!empty($forum->description)): ?>
                                        <p class="cmsforum-forum-card__desc"><?php echo htmlspecialchars($forum->description); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="cmsforum-forum-card__stats">
                                    <span class="cmsforum-forum-card__stat"><?php echo (int)$forum->thread_count; ?> Threads</span>
                                    <span class="cmsforum-forum-card__stat"><?php echo (int)$forum->post_count; ?> Beiträge</span>
                                </div>
                                <?php if (!empty($forum->last_post_at)): ?>
                                <div class="cmsforum-forum-card__last">
                                    <?php echo TimeHelper::relative($forum->last_post_at); ?>
                                </div>
                                <?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div><!-- /.cmsforum-container -->
</div><!-- /.cmsforum -->
