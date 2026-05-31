<?php
/**
 * CMS Forum – Thread anzeigen
 *
 * Verfügbare Variablen:
 *   $thread       – Thread-Objekt
 *   $forum        – Forum-Objekt
 *   $posts        – Array von Post-Objekten (mit content_html)
 *   $pagination   – Pagination-Objekt
 *   $poll         – Poll-Objekt|null
 *   $pollOptions  – Array von PollOption-Objekten
 *   $userVotes    – Array von Vote-IDs
 *   $isSubscribed – bool
 *   $attachments  – Array von Attachment-Objekten
 *   $error        – string|null
 *   $success      – string|null
 *   $pageTitle    – String
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
$userId = $isLoggedIn ? (int)$auth->currentUser()->id : 0;
$siteUrl = htmlspecialchars(rtrim((string) SITE_URL, '/'), ENT_QUOTES, 'UTF-8');
$likeCsrf = $isLoggedIn ? \CMS\Security::instance()->generateToken('forum_like') : '';
$subscribeCsrf = $isLoggedIn ? \CMS\Security::instance()->generateToken('forum_subscribe') : '';
$reportCsrf = $isLoggedIn ? \CMS\Security::instance()->generateToken('forum_report') : '';
$pollCsrf = $isLoggedIn ? \CMS\Security::instance()->generateToken('forum_poll_vote') : '';
$acceptCsrf = $isLoggedIn ? \CMS\Security::instance()->generateToken('forum_accept_answer') : '';
$isThreadOwner = $isLoggedIn && (int) $thread->user_id === $userId;
$canModerate = \CMS_Forum\Services\PermissionService::instance()->canModerate((int) $forum->id);

// Attachments nach Post-ID gruppieren
$attachmentsByPost = [];
foreach ($attachments as $att) {
    $attachmentsByPost[(int)$att->post_id][] = $att;
}
?>

<div class="cmsforum" data-api-base="<?php echo htmlspecialchars(PublicI18n::forumPath('api'), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="cmsforum-container">

        <!-- Breadcrumb -->
        <nav class="cmsforum-breadcrumb" aria-label="<?php echo htmlspecialchars(PublicI18n::t('breadcrumb.label', 'Breadcrumb'), ENT_QUOTES, 'UTF-8'); ?>">
            <a href="<?php echo $siteUrl; ?>/"><?php echo htmlspecialchars(PublicI18n::t('breadcrumb.homepage', 'Startseite'), ENT_QUOTES, 'UTF-8'); ?></a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <a href="<?php echo $siteUrl . PublicI18n::forumPath(); ?>"><?php echo htmlspecialchars(PublicI18n::t('forum', 'Forum'), ENT_QUOTES, 'UTF-8'); ?></a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <a href="<?php echo $siteUrl . PublicI18n::forumPath((string) $forum->slug); ?>"><?php echo htmlspecialchars((string) $forum->name, ENT_QUOTES, 'UTF-8'); ?></a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <span class="cmsforum-breadcrumb__current" aria-current="page"><?php echo htmlspecialchars((string) $thread->title, ENT_QUOTES, 'UTF-8'); ?></span>
        </nav>

        <!-- Thread Header -->
        <div class="cmsforum-thread-header">
            <div class="cmsforum-thread-header__info">
                <h1 class="cmsforum-thread-header__title">
                    <?php if ($thread->type === 'sticky'): ?>📌 <?php elseif ($thread->type === 'announcement'): ?>📢 <?php endif; ?>
                    <?php echo htmlspecialchars((string) $thread->title, ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ($thread->status === 'closed'): ?><span class="cmsforum-badge cmsforum-badge--closed">🔒 <?php echo htmlspecialchars(PublicI18n::t('thread.status.closed', 'Geschlossen'), ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                    <?php if (!empty($thread->accepted_post_id)): ?><span class="cmsforum-badge cmsforum-badge--accepted">✅ <?php echo htmlspecialchars(PublicI18n::t('thread.status.solved', 'Gelöst'), ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                </h1>
            </div>
            <div class="cmsforum-thread-header__actions">
                <?php if ($isLoggedIn): ?>
                        <button class="cmsforum-btn cmsforum-btn--secondary cmsforum-btn--sm js-subscribe"
                            data-action="subscribe" data-type="thread" data-item-id="<?php echo (int)$thread->id; ?>"
                            data-csrf="<?php echo htmlspecialchars($subscribeCsrf, ENT_QUOTES, 'UTF-8'); ?>"
                            aria-label="<?php echo $isSubscribed ? htmlspecialchars(PublicI18n::t('action.unsubscribe', 'Abo beenden'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(PublicI18n::t('action.subscribe', 'Abonnieren'), ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo $isSubscribed ? '🔔 ' . htmlspecialchars(PublicI18n::t('action.subscribed', 'Abonniert'), ENT_QUOTES, 'UTF-8') : '🔕 ' . htmlspecialchars(PublicI18n::t('action.subscribe', 'Abonnieren'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="cmsforum-alert cmsforum-alert--error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="cmsforum-alert cmsforum-alert--success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Umfrage -->
        <?php if ($poll && !empty($pollOptions)): ?>
        <div class="cmsforum-poll" id="poll-<?php echo (int)$poll->id; ?>">
            <h3 class="cmsforum-poll__question">🗳️ <?php echo htmlspecialchars($poll->question); ?></h3>
            <div class="cmsforum-poll__options">
                <?php
                $totalVotes = array_sum(array_map(fn($o) => (int)$o->vote_count, $pollOptions));
                $hasVoted = !empty($userVotes);
                ?>
                <?php foreach ($pollOptions as $opt): ?>
                <?php
                $pct = $totalVotes > 0 ? max(0, min(100, (int) round(((int)$opt->vote_count / $totalVotes) * 100))) : 0;
                $voted = in_array((int)$opt->id, $userVotes, true);
                ?>
                <div class="cmsforum-poll__option <?php echo $voted ? 'cmsforum-poll__option--voted' : ''; ?>">
                    <?php if (!$hasVoted && $isLoggedIn): ?>
                        <button class="cmsforum-poll__vote-btn js-poll-vote"
                                data-poll="<?php echo (int)$poll->id; ?>"
                            data-option="<?php echo (int)$opt->id; ?>"
                            data-csrf="<?php echo htmlspecialchars($pollCsrf, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($opt->option_text); ?>
                        </button>
                    <?php else: ?>
                        <div class="cmsforum-poll__result">
                            <div class="cmsforum-poll__bar" style="width:<?php echo $pct; ?>%;"></div>
                            <span class="cmsforum-poll__text"><?php echo htmlspecialchars($opt->option_text); ?></span>
                            <span class="cmsforum-poll__pct"><?php echo $pct; ?>%</span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="cmsforum-poll__footer">
                <span class="cmsforum-poll__total"><?php echo $totalVotes; ?> Stimme<?php echo $totalVotes !== 1 ? 'n' : ''; ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Beiträge -->
        <div class="cmsforum-posts">
            <?php foreach ($posts as $i => $post): ?>
            <article class="cmsforum-post" id="post-<?php echo (int)$post->id; ?>">
                <!-- Autor-Sidebar -->
                <aside class="cmsforum-post__author">
                    <div class="cmsforum-post__avatar">
                        <?php echo AvatarHelper::render($post->username ?? 'U', $post->avatar_url ?? null, 64); ?>
                    </div>
                    <div class="cmsforum-post__author-name">
                        <a href="<?php echo $siteUrl . PublicI18n::forumPath('user/' . (int) $post->user_id); ?>"><?php echo htmlspecialchars((string) ($post->username ?? PublicI18n::t('user.deleted', 'Gelöscht')), ENT_QUOTES, 'UTF-8'); ?></a>
                    </div>
                    <?php if (!empty($post->rank_title)): ?>
                        <span class="cmsforum-post__rank <?php echo htmlspecialchars($post->rank_css_class ?? ''); ?>">
                            <?php echo htmlspecialchars((string) $post->rank_title, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    <?php endif; ?>
                    <span class="cmsforum-post__count"><?php echo (int)$post->post_count; ?> Beiträge</span>
                </aside>

                <!-- Content -->
                <div class="cmsforum-post__content-wrap">
                    <header class="cmsforum-post__header">
                        <a href="#post-<?php echo (int)$post->id; ?>" class="cmsforum-post__date">
                            <?php echo TimeHelper::tag($post->created_at); ?>
                        </a>
                        <?php if ((int)$post->edit_count > 0): ?>
                            <span class="cmsforum-post__edited" title="Bearbeitet am <?php echo htmlspecialchars($post->edited_at ?? ''); ?>">
                                ✏️ <?php echo (int)$post->edit_count; ?>× bearbeitet
                            </span>
                        <?php endif; ?>
                    </header>

                    <div class="cmsforum-post__body">
                        <?php echo $post->content_html; ?>
                    </div>

                    <?php if ((int) ($thread->accepted_post_id ?? 0) === (int) $post->id): ?>
                    <div class="cmsforum-alert cmsforum-alert--success">
                        ✅ <?php echo htmlspecialchars(PublicI18n::t('thread.accepted_answer', 'Diese Antwort wurde als Lösung markiert.'), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Anhänge -->
                    <?php $postAttachments = $attachmentsByPost[(int)$post->id] ?? []; ?>
                    <?php if (!empty($postAttachments)): ?>
                    <div class="cmsforum-post__attachments">
                        <h4 class="cmsforum-post__attachments-title">📎 Anhänge</h4>
                        <?php foreach ($postAttachments as $att): ?>
                        <a href="<?php echo $siteUrl; ?>/uploads/forum/<?php echo htmlspecialchars(basename((string) ($att->file_path ?? $att->filename ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
                           class="cmsforum-attachment" target="_blank" rel="noopener noreferrer">
                            <?php echo htmlspecialchars((string) $att->original_name, ENT_QUOTES, 'UTF-8'); ?>
                            <span class="cmsforum-attachment__size">(<?php echo round((int)$att->file_size / 1024); ?> KB)</span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Signatur -->
                    <?php if (!empty($post->signature)): ?>
                    <div class="cmsforum-post__signature">
                        <?php echo htmlspecialchars($post->signature); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Aktionen -->
                    <footer class="cmsforum-post__footer">
                        <div class="cmsforum-post__actions">
                            <?php if ($isLoggedIn): ?>
                                <button class="cmsforum-post__action-btn js-like"
                                    data-action="like"
                                    data-post-id="<?php echo (int)$post->id; ?>"
                                    data-csrf="<?php echo htmlspecialchars($likeCsrf, ENT_QUOTES, 'UTF-8'); ?>"
                                        aria-label="Like">
                                    ❤️ <span class="js-like-count"><?php echo (int)$post->like_count; ?></span>
                                </button>
                            <?php else: ?>
                                <span class="cmsforum-post__action-btn cmsforum-post__action-btn--disabled">❤️ <?php echo (int)$post->like_count; ?></span>
                            <?php endif; ?>

                            <?php if ($isLoggedIn && (int)$post->user_id === $userId): ?>
                                <a href="<?php echo $siteUrl . PublicI18n::forumPath('post/' . (int) $post->id . '/edit'); ?>" class="cmsforum-post__action-btn"><?php echo htmlspecialchars(PublicI18n::t('action.edit', 'Bearbeiten'), ENT_QUOTES, 'UTF-8'); ?></a>
                            <?php endif; ?>

                            <?php if ($isLoggedIn && (int)$post->user_id !== $userId): ?>
                                <button class="cmsforum-post__action-btn js-report" data-action="report" data-post-id="<?php echo (int)$post->id; ?>"><?php echo htmlspecialchars(PublicI18n::t('action.report', 'Melden'), ENT_QUOTES, 'UTF-8'); ?></button>
                            <?php endif; ?>

                            <?php if ($thread->status === 'open' && $isLoggedIn): ?>
                                <a href="#reply-form" class="cmsforum-post__action-btn">↩️ <?php echo htmlspecialchars(PublicI18n::t('action.quote', 'Zitieren'), ENT_QUOTES, 'UTF-8'); ?></a>
                            <?php endif; ?>

                            <?php if (($isThreadOwner || $canModerate) && !$post->is_first_post): ?>
                                <?php if ((int) ($thread->accepted_post_id ?? 0) === (int) $post->id): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="unaccept_answer">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($acceptCsrf, ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="cmsforum-post__action-btn"><?php echo htmlspecialchars(PublicI18n::t('thread.unmark_solution', 'Lösung entfernen'), ENT_QUOTES, 'UTF-8'); ?></button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="accept_answer">
                                        <input type="hidden" name="post_id" value="<?php echo (int) $post->id; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($acceptCsrf, ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="cmsforum-post__action-btn"><?php echo htmlspecialchars(PublicI18n::t('thread.mark_as_solution', 'Als Lösung markieren'), ENT_QUOTES, 'UTF-8'); ?></button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </footer>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <!-- Paginierung -->
        <?php echo $pagination->render(rtrim((string) SITE_URL, '/') . PublicI18n::forumPath('thread/' . (int) $thread->id)); ?>

        <!-- Antwort-Formular -->
        <?php if ($thread->status === 'open' && $isLoggedIn): ?>
        <div class="cmsforum-reply" id="reply-form">
            <h3 class="cmsforum-reply__title">↩️ <?php echo htmlspecialchars(PublicI18n::t('action.reply', 'Antworten'), ENT_QUOTES, 'UTF-8'); ?></h3>
            <form method="POST" class="cmsforum-reply__form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) \CMS\Security::instance()->generateToken('forum_reply'), ENT_QUOTES, 'UTF-8'); ?>">

                <div class="cmsforum-editor">
                    <div class="cmsforum-editor__toolbar" id="bbcode-toolbar">
                        <button type="button" class="cmsforum-editor__btn" data-bbcode="b" title="Fett"><strong>B</strong></button>
                        <button type="button" class="cmsforum-editor__btn" data-bbcode="i" title="Kursiv"><em>I</em></button>
                        <button type="button" class="cmsforum-editor__btn" data-bbcode="u" title="Unterstrichen"><u>U</u></button>
                        <button type="button" class="cmsforum-editor__btn" data-bbcode="s" title="Durchgestrichen"><s>S</s></button>
                        <span class="cmsforum-editor__sep"></span>
                        <button type="button" class="cmsforum-editor__btn" data-bbcode="url" title="Link">🔗</button>
                        <button type="button" class="cmsforum-editor__btn" data-bbcode="img" title="Bild">🖼️</button>
                        <button type="button" class="cmsforum-editor__btn" data-bbcode="code" title="Code">⟨⟩</button>
                        <button type="button" class="cmsforum-editor__btn" data-bbcode="quote" title="Zitat">💬</button>
                        <button type="button" class="cmsforum-editor__btn" data-bbcode="list" title="Liste">📋</button>
                    </div>
                    <textarea name="content" id="reply-content" class="cmsforum-editor__textarea"
                              rows="8" placeholder="<?php echo htmlspecialchars(PublicI18n::t('thread.reply_placeholder', 'Deine Antwort...'), ENT_QUOTES, 'UTF-8'); ?>" required
                              minlength="3" maxlength="50000"></textarea>
                </div>

                <div class="cmsforum-reply__actions">
                    <button type="submit" class="cmsforum-btn cmsforum-btn--primary">💬 <?php echo htmlspecialchars(PublicI18n::t('thread.reply_submit', 'Antwort absenden'), ENT_QUOTES, 'UTF-8'); ?></button>
                </div>
            </form>
        </div>
        <?php elseif ($thread->status === 'closed'): ?>
            <div class="cmsforum-alert cmsforum-alert--info">🔒 <?php echo htmlspecialchars(PublicI18n::t('thread.closed_info', 'Dieses Thema ist geschlossen. Neue Antworten sind nicht möglich.'), ENT_QUOTES, 'UTF-8'); ?></div>
        <?php elseif (!$isLoggedIn): ?>
            <div class="cmsforum-alert cmsforum-alert--info">
                <a href="<?php echo $siteUrl . PublicI18n::loginPath(); ?>"><?php echo htmlspecialchars(PublicI18n::t('action.login', 'Anmelden'), ENT_QUOTES, 'UTF-8'); ?></a>, <?php echo htmlspecialchars(PublicI18n::t('thread.login_to_reply', 'um zu antworten.'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

    </div><!-- /.cmsforum-container -->
</div><!-- /.cmsforum -->

<!-- Report Modal -->
<div class="cmsforum-modal" id="reportModal" hidden role="dialog" aria-modal="true" aria-labelledby="reportModalTitle">
    <div class="cmsforum-modal__content">
        <form id="reportForm" method="POST" action="<?php echo $siteUrl . PublicI18n::forumPath('api/report'); ?>">
            <div class="cmsforum-modal__header">
                <h3 id="reportModalTitle">Beitrag melden</h3>
                <button class="cmsforum-modal__close" type="button" data-action="close-modal" aria-label="Schließen">&times;</button>
            </div>
            <div class="cmsforum-modal__body">
            <input type="hidden" id="reportPostId" name="post_id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($reportCsrf, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="cmsforum-form-group">
                <label class="cmsforum-label" for="reportReason">Grund</label>
                <select id="reportReason" name="reason" class="cmsforum-input">
                    <option value="spam">Spam</option>
                    <option value="offensive">Beleidigend</option>
                    <option value="off-topic">Off-Topic</option>
                    <option value="harassment">Belästigung</option>
                    <option value="other">Sonstiges</option>
                </select>
            </div>
            <div class="cmsforum-form-group">
                <label class="cmsforum-label" for="reportDetail">Details (optional)</label>
                <textarea id="reportDetail" name="detail" class="cmsforum-input" rows="3" maxlength="500"></textarea>
            </div>
            </div>
            <div class="cmsforum-modal__footer">
                <button type="button" class="cmsforum-btn cmsforum-btn--secondary" data-action="close-modal">Abbrechen</button>
                <button type="submit" class="cmsforum-btn cmsforum-btn--danger js-report-submit">Melden</button>
            </div>
        </form>
    </div>
</div>
