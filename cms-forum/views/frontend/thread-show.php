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

$auth = \CMS\Auth::instance();
$isLoggedIn = $auth->isLoggedIn();
$userId = $isLoggedIn ? $auth->getUserId() : 0;

// Attachments nach Post-ID gruppieren
$attachmentsByPost = [];
foreach ($attachments as $att) {
    $attachmentsByPost[(int)$att->post_id][] = $att;
}
?>

<div class="cmsforum">
    <div class="cmsforum-container">

        <!-- Breadcrumb -->
        <nav class="cmsforum-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo SITE_URL; ?>/">Startseite</a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <a href="<?php echo SITE_URL; ?>/forum">Forum</a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <a href="<?php echo SITE_URL; ?>/forum/<?php echo htmlspecialchars($forum->slug); ?>"><?php echo htmlspecialchars($forum->name); ?></a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <span class="cmsforum-breadcrumb__current" aria-current="page"><?php echo htmlspecialchars($thread->title); ?></span>
        </nav>

        <!-- Thread Header -->
        <div class="cmsforum-thread-header">
            <div class="cmsforum-thread-header__info">
                <h1 class="cmsforum-thread-header__title">
                    <?php if ($thread->type === 'sticky'): ?>📌 <?php elseif ($thread->type === 'announcement'): ?>📢 <?php endif; ?>
                    <?php echo htmlspecialchars($thread->title); ?>
                    <?php if ($thread->status === 'closed'): ?><span class="cmsforum-badge cmsforum-badge--closed">🔒 Geschlossen</span><?php endif; ?>
                </h1>
            </div>
            <div class="cmsforum-thread-header__actions">
                <?php if ($isLoggedIn): ?>
                    <button class="cmsforum-btn cmsforum-btn--secondary cmsforum-btn--sm js-subscribe"
                            data-type="thread" data-id="<?php echo (int)$thread->id; ?>"
                            aria-label="<?php echo $isSubscribed ? 'Abbestellen' : 'Abonnieren'; ?>">
                        <?php echo $isSubscribed ? '🔔 Abonniert' : '🔕 Abonnieren'; ?>
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
                $pct = $totalVotes > 0 ? round(((int)$opt->vote_count / $totalVotes) * 100) : 0;
                $voted = in_array((int)$opt->id, $userVotes, true);
                ?>
                <div class="cmsforum-poll__option <?php echo $voted ? 'cmsforum-poll__option--voted' : ''; ?>">
                    <?php if (!$hasVoted && $isLoggedIn): ?>
                        <button class="cmsforum-poll__vote-btn js-poll-vote"
                                data-poll="<?php echo (int)$poll->id; ?>"
                                data-option="<?php echo (int)$opt->id; ?>">
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
                        <a href="<?php echo SITE_URL; ?>/forum/user/<?php echo (int)$post->user_id; ?>"><?php echo htmlspecialchars($post->username ?? 'Gelöscht'); ?></a>
                    </div>
                    <?php if (!empty($post->rank_title)): ?>
                        <span class="cmsforum-post__rank <?php echo htmlspecialchars($post->rank_css_class ?? ''); ?>">
                            <?php echo htmlspecialchars($post->rank_title); ?>
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

                    <!-- Anhänge -->
                    <?php $postAttachments = $attachmentsByPost[(int)$post->id] ?? []; ?>
                    <?php if (!empty($postAttachments)): ?>
                    <div class="cmsforum-post__attachments">
                        <h4 class="cmsforum-post__attachments-title">📎 Anhänge</h4>
                        <?php foreach ($postAttachments as $att): ?>
                        <a href="<?php echo SITE_URL; ?>/uploads/forum/<?php echo htmlspecialchars($att->file_path); ?>"
                           class="cmsforum-attachment" target="_blank" rel="noopener noreferrer">
                            <?php echo htmlspecialchars($att->original_name); ?>
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
                                        data-post="<?php echo (int)$post->id; ?>"
                                        aria-label="Like">
                                    ❤️ <span class="js-like-count"><?php echo (int)$post->like_count; ?></span>
                                </button>
                            <?php else: ?>
                                <span class="cmsforum-post__action-btn cmsforum-post__action-btn--disabled">❤️ <?php echo (int)$post->like_count; ?></span>
                            <?php endif; ?>

                            <?php if ($isLoggedIn && (int)$post->user_id === $userId): ?>
                                <a href="<?php echo SITE_URL; ?>/forum/post/<?php echo (int)$post->id; ?>/edit" class="cmsforum-post__action-btn">✏️ Bearbeiten</a>
                            <?php endif; ?>

                            <?php if ($isLoggedIn && (int)$post->user_id !== $userId): ?>
                                <button class="cmsforum-post__action-btn js-report" data-post="<?php echo (int)$post->id; ?>">🚩 Melden</button>
                            <?php endif; ?>

                            <?php if ($thread->status === 'open' && $isLoggedIn): ?>
                                <a href="#reply-form" class="cmsforum-post__action-btn">↩️ Zitieren</a>
                            <?php endif; ?>
                        </div>
                    </footer>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <!-- Paginierung -->
        <?php echo $pagination->render(SITE_URL . '/forum/thread/' . (int)$thread->id); ?>

        <!-- Antwort-Formular -->
        <?php if ($thread->status === 'open' && $isLoggedIn): ?>
        <div class="cmsforum-reply" id="reply-form">
            <h3 class="cmsforum-reply__title">↩️ Antworten</h3>
            <form method="POST" class="cmsforum-reply__form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="csrf_token" value="<?php echo \CMS\Security::instance()->generateToken('forum_reply'); ?>">

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
                              rows="8" placeholder="Deine Antwort..." required
                              minlength="3" maxlength="50000"></textarea>
                </div>

                <div class="cmsforum-reply__actions">
                    <button type="submit" class="cmsforum-btn cmsforum-btn--primary">💬 Antwort absenden</button>
                </div>
            </form>
        </div>
        <?php elseif ($thread->status === 'closed'): ?>
            <div class="cmsforum-alert cmsforum-alert--info">🔒 Dieser Thread ist geschlossen. Neue Antworten sind nicht möglich.</div>
        <?php elseif (!$isLoggedIn): ?>
            <div class="cmsforum-alert cmsforum-alert--info">
                <a href="<?php echo SITE_URL; ?>/login">Anmelden</a>, um zu antworten.
            </div>
        <?php endif; ?>

    </div><!-- /.cmsforum-container -->
</div><!-- /.cmsforum -->

<!-- Report Modal -->
<div class="cmsforum-modal" id="reportModal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="reportModalTitle">
    <div class="cmsforum-modal__content">
        <div class="cmsforum-modal__header">
            <h3 id="reportModalTitle">🚩 Beitrag melden</h3>
            <button class="cmsforum-modal__close" onclick="document.getElementById('reportModal').style.display='none'" aria-label="Schließen">&times;</button>
        </div>
        <div class="cmsforum-modal__body">
            <input type="hidden" id="report-post-id">
            <div class="cmsforum-form-group">
                <label class="cmsforum-label">Grund</label>
                <select id="report-reason" class="cmsforum-input">
                    <option value="spam">Spam</option>
                    <option value="offensive">Beleidigend</option>
                    <option value="off_topic">Off-Topic</option>
                    <option value="harassment">Belästigung</option>
                    <option value="other">Sonstiges</option>
                </select>
            </div>
            <div class="cmsforum-form-group">
                <label class="cmsforum-label">Details (optional)</label>
                <textarea id="report-detail" class="cmsforum-input" rows="3" maxlength="500"></textarea>
            </div>
        </div>
        <div class="cmsforum-modal__footer">
            <button type="button" class="cmsforum-btn cmsforum-btn--secondary" onclick="document.getElementById('reportModal').style.display='none'">Abbrechen</button>
            <button type="button" class="cmsforum-btn cmsforum-btn--danger js-report-submit">🚩 Melden</button>
        </div>
    </div>
</div>
