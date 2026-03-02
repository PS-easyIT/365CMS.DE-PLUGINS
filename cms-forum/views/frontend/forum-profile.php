<?php
/**
 * CMS Forum – Benutzerprofil
 *
 * Verfügbare Variablen:
 *   $user      – User-Objekt (id, username, created_at)
 *   $meta      – UserMeta-Objekt
 *   $pageTitle – String
 *
 * @package CMS_Forum\Views
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS_Forum\Helpers\TimeHelper;
use CMS_Forum\Helpers\AvatarHelper;
?>

<div class="cmsforum">
    <div class="cmsforum-container">

        <!-- Breadcrumb -->
        <nav class="cmsforum-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo SITE_URL; ?>/">Startseite</a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <a href="<?php echo SITE_URL; ?>/forum">Forum</a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <span class="cmsforum-breadcrumb__current" aria-current="page"><?php echo htmlspecialchars($user->username); ?></span>
        </nav>

        <!-- Profil-Header -->
        <div class="cmsforum-profile">
            <div class="cmsforum-profile__header">
                <div class="cmsforum-profile__avatar">
                    <?php echo AvatarHelper::render($user->username, $meta->avatar_url ?? null, 96); ?>
                </div>
                <div class="cmsforum-profile__info">
                    <h1 class="cmsforum-profile__name"><?php echo htmlspecialchars($user->username); ?></h1>
                    <?php if (!empty($meta->rank_title)): ?>
                        <span class="cmsforum-profile__rank <?php echo htmlspecialchars($meta->rank_css_class ?? ''); ?>">
                            <?php echo htmlspecialchars($meta->rank_title ?? ''); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($meta->is_banned): ?>
                        <span class="cmsforum-badge cmsforum-badge--danger">Gesperrt</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statistiken -->
            <div class="cmsforum-profile__stats">
                <div class="cmsforum-profile__stat">
                    <span class="cmsforum-profile__stat-value"><?php echo (int)$meta->post_count; ?></span>
                    <span class="cmsforum-profile__stat-label">Beiträge</span>
                </div>
                <div class="cmsforum-profile__stat">
                    <span class="cmsforum-profile__stat-value"><?php echo (int)$meta->thread_count; ?></span>
                    <span class="cmsforum-profile__stat-label">Threads</span>
                </div>
                <div class="cmsforum-profile__stat">
                    <span class="cmsforum-profile__stat-value"><?php echo (int)$meta->likes_received; ?></span>
                    <span class="cmsforum-profile__stat-label">Likes erhalten</span>
                </div>
                <div class="cmsforum-profile__stat">
                    <span class="cmsforum-profile__stat-value"><?php echo TimeHelper::relative($user->created_at); ?></span>
                    <span class="cmsforum-profile__stat-label">Dabei seit</span>
                </div>
            </div>

            <!-- Signatur -->
            <?php if (!empty($meta->signature)): ?>
            <div class="cmsforum-profile__section">
                <h3 class="cmsforum-profile__section-title">✍️ Signatur</h3>
                <p class="cmsforum-profile__signature"><?php echo htmlspecialchars($meta->signature); ?></p>
            </div>
            <?php endif; ?>

            <!-- Ort / Website -->
            <div class="cmsforum-profile__details">
                <?php if (!empty($meta->location)): ?>
                    <div class="cmsforum-profile__detail">📍 <?php echo htmlspecialchars($meta->location); ?></div>
                <?php endif; ?>
                <?php if (!empty($meta->website)): ?>
                    <div class="cmsforum-profile__detail">
                        🌐 <a href="<?php echo htmlspecialchars($meta->website); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($meta->website); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /.cmsforum-container -->
</div><!-- /.cmsforum -->
