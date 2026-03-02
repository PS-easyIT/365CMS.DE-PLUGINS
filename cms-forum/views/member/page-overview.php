<?php
/**
 * CMS Forum – Member Dashboard Widget
 *
 * Wird im Member-Dashboard als Widget angezeigt.
 * Verfügbare Variablen:
 *   $meta         – UserMeta-Objekt
 *   $recentPosts  – Array letzter Beiträge
 *   $unreadCount  – int: Anzahl ungelesener Threads
 *
 * @package CMS_Forum\Views
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS_Forum\Helpers\TimeHelper;
?>

<div class="cmsforum-widget">
    <div class="cmsforum-widget__header">
        <h3 class="cmsforum-widget__title">💬 Forum</h3>
        <a href="<?php echo SITE_URL; ?>/forum" class="cmsforum-widget__link">Zum Forum →</a>
    </div>

    <!-- Kurzstatistik -->
    <div class="cmsforum-widget__stats">
        <div class="cmsforum-widget__stat">
            <span class="cmsforum-widget__stat-value"><?php echo (int)$meta->post_count; ?></span>
            <span class="cmsforum-widget__stat-label">Beiträge</span>
        </div>
        <div class="cmsforum-widget__stat">
            <span class="cmsforum-widget__stat-value"><?php echo (int)$meta->thread_count; ?></span>
            <span class="cmsforum-widget__stat-label">Threads</span>
        </div>
        <div class="cmsforum-widget__stat">
            <span class="cmsforum-widget__stat-value"><?php echo (int)$meta->likes_received; ?></span>
            <span class="cmsforum-widget__stat-label">Likes</span>
        </div>
        <?php if ($unreadCount > 0): ?>
        <div class="cmsforum-widget__stat cmsforum-widget__stat--highlight">
            <span class="cmsforum-widget__stat-value"><?php echo $unreadCount; ?></span>
            <span class="cmsforum-widget__stat-label">Ungelesen</span>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($meta->rank_title)): ?>
        <div class="cmsforum-widget__rank">
            Rang: <strong><?php echo htmlspecialchars($meta->rank_title); ?></strong>
        </div>
    <?php endif; ?>

    <!-- Letzte Beiträge -->
    <?php if (!empty($recentPosts)): ?>
    <div class="cmsforum-widget__recent">
        <h4 class="cmsforum-widget__subtitle">Letzte Aktivität</h4>
        <ul class="cmsforum-widget__list">
            <?php foreach ($recentPosts as $post): ?>
            <li class="cmsforum-widget__list-item">
                <a href="<?php echo SITE_URL; ?>/forum/thread/<?php echo (int)$post->thread_id; ?>#post-<?php echo (int)$post->id; ?>">
                    <?php echo htmlspecialchars(mb_substr($post->thread_title ?? '', 0, 50)); ?>
                    <?php if (mb_strlen($post->thread_title ?? '') > 50): ?>…<?php endif; ?>
                </a>
                <span class="cmsforum-widget__time"><?php echo TimeHelper::relative($post->created_at); ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
