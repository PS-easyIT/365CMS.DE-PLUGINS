<?php
/**
 * CMS Forum – Forum anzeigen (Thread-Liste)
 *
 * Verfügbare Variablen:
 *   $forum      – Forum-Objekt
 *   $threads    – Array von Thread-Objekten
 *   $subforums  – Array von Subforum-Objekten
 *   $pagination – Pagination-Objekt
 *   $pageTitle  – String
 *
 * @package CMS_Forum\Views
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS_Forum\Helpers\TimeHelper;
use CMS_Forum\Helpers\AvatarHelper;
use CMS_Forum\Helpers\PublicI18n;

$auth = \CMS\Auth::instance();
$isLoggedIn = $auth->isLoggedIn();
$siteUrl = rtrim((string) SITE_URL, '/');
?>

<div class="cmsforum">
    <div class="cmsforum-container">

        <!-- Breadcrumb -->
        <nav class="cmsforum-breadcrumb" aria-label="<?php echo htmlspecialchars(PublicI18n::t('breadcrumb.label', 'Breadcrumb'), ENT_QUOTES, 'UTF-8'); ?>">
            <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/"><?php echo htmlspecialchars(PublicI18n::t('breadcrumb.homepage', 'Startseite'), ENT_QUOTES, 'UTF-8'); ?></a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <a href="<?php echo htmlspecialchars($siteUrl . PublicI18n::forumPath(), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(PublicI18n::t('forum', 'Forum'), ENT_QUOTES, 'UTF-8'); ?></a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <span class="cmsforum-breadcrumb__current" aria-current="page"><?php echo htmlspecialchars($forum->name); ?></span>
        </nav>

        <!-- Header -->
        <div class="cmsforum-page-header">
            <div>
                <h1 class="cmsforum-page-header__title"><?php echo htmlspecialchars($forum->name); ?></h1>
                <?php if (!empty($forum->description)): ?>
                    <p class="cmsforum-page-header__desc"><?php echo htmlspecialchars($forum->description); ?></p>
                <?php endif; ?>
            </div>
            <div class="cmsforum-page-header__actions">
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo htmlspecialchars($siteUrl . PublicI18n::forumPath((string) $forum->slug . '/new-thread'), ENT_QUOTES, 'UTF-8'); ?>" class="cmsforum-btn cmsforum-btn--primary">➕ <?php echo htmlspecialchars(PublicI18n::t('action.new_thread', 'Neues Thema'), ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Subforen -->
        <?php if (!empty($subforums)): ?>
        <div class="cmsforum-subforums">
            <h3 class="cmsforum-subforums__title">📁 Subforen</h3>
            <div class="cmsforum-subforum-list">
                <?php foreach ($subforums as $sub): ?>
                <a href="<?php echo htmlspecialchars($siteUrl . PublicI18n::forumPath((string) $sub->slug), ENT_QUOTES, 'UTF-8'); ?>" class="cmsforum-subforum-link">
                    📁 <?php echo htmlspecialchars($sub->name); ?>
                    <span class="cmsforum-subforum-link__count">(<?php echo (int)$sub->thread_count; ?>)</span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Thread-Liste -->
        <?php if (empty($threads)): ?>
            <div class="cmsforum-empty">
                <p class="cmsforum-empty__icon">📭</p>
                <p class="cmsforum-empty__text"><?php echo htmlspecialchars(PublicI18n::t('empty.threads', 'Noch keine Themen in diesem Forum.'), ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo htmlspecialchars($siteUrl . PublicI18n::forumPath((string) $forum->slug . '/new-thread'), ENT_QUOTES, 'UTF-8'); ?>" class="cmsforum-btn cmsforum-btn--primary cmsforum-empty__cta">➕ <?php echo htmlspecialchars(PublicI18n::t('thread.create_first', 'Erstes Thema erstellen'), ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="cmsforum-thread-list">
                <?php foreach ($threads as $t): ?>
                <div class="cmsforum-thread-item <?php echo !empty($t->is_unread) ? 'cmsforum-thread-item--unread' : ''; ?> <?php echo $t->type !== 'normal' ? 'cmsforum-thread-item--' . $t->type : ''; ?>">
                    <div class="cmsforum-thread-item__icon">
                        <?php if ($t->type === 'announcement'): ?>
                            <span class="cmsforum-badge cmsforum-badge--announcement" title="Ankündigung">📢</span>
                        <?php elseif ($t->type === 'sticky'): ?>
                            <span class="cmsforum-badge cmsforum-badge--sticky" title="Angepinnt">📌</span>
                        <?php elseif ($t->status === 'closed'): ?>
                            <span class="cmsforum-badge cmsforum-badge--closed" title="Geschlossen">🔒</span>
                        <?php else: ?>
                            <?php echo AvatarHelper::render($t->username ?? 'U', null, 36); ?>
                        <?php endif; ?>
                    </div>
                    <div class="cmsforum-thread-item__body">
                        <h3 class="cmsforum-thread-item__title">
                            <a href="<?php echo htmlspecialchars($siteUrl . PublicI18n::forumPath('thread/' . (int) $t->id), ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($t->title); ?>
                            </a>
                            <?php if (!empty($t->accepted_post_id)): ?>
                                <span class="cmsforum-badge cmsforum-badge--accepted">✅ <?php echo htmlspecialchars(PublicI18n::t('thread.status.solved', 'Gelöst'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </h3>
                        <div class="cmsforum-thread-item__meta">
                            <a href="<?php echo htmlspecialchars($siteUrl . PublicI18n::forumPath('user/' . (int) $t->user_id), ENT_QUOTES, 'UTF-8'); ?>" class="cmsforum-thread-item__author"><?php echo htmlspecialchars($t->username ?? PublicI18n::t('user.deleted', 'Gelöscht')); ?></a>
                            <span class="cmsforum-thread-item__sep">·</span>
                            <?php echo TimeHelper::tag($t->created_at); ?>
                        </div>
                    </div>
                    <div class="cmsforum-thread-item__stats">
                        <span class="cmsforum-thread-item__stat" title="Antworten">💬 <?php echo (int)$t->reply_count; ?></span>
                        <span class="cmsforum-thread-item__stat" title="Aufrufe">👁️ <?php echo (int)$t->view_count; ?></span>
                    </div>
                    <?php if (!empty($t->last_post_at)): ?>
                    <div class="cmsforum-thread-item__last">
                        <span class="cmsforum-thread-item__last-time"><?php echo TimeHelper::relative($t->last_post_at); ?></span>
                        <?php if (!empty($t->last_poster)): ?>
                            <span class="cmsforum-thread-item__last-user"><?php echo htmlspecialchars(PublicI18n::t('thread.last_post_by', 'von %s', $t->last_poster), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginierung -->
            <?php echo $pagination->render($siteUrl . PublicI18n::forumPath((string) $forum->slug)); ?>
        <?php endif; ?>

    </div><!-- /.cmsforum-container -->
</div><!-- /.cmsforum -->
