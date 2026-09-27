<?php
/**
 * CMS Forum – Neuen Thread erstellen
 *
 * Verfügbare Variablen:
 *   $forum      – Forum-Objekt
 *   $csrfToken  – CSRF-Token
 *   $error      – string|null
 *   $pageTitle  – String
 *
 * @package CMS_Forum\Views
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS_Forum\Helpers\PublicI18n;

$siteUrl = htmlspecialchars(rtrim((string) SITE_URL, '/'), ENT_QUOTES, 'UTF-8');
$forumUrl = $siteUrl . PublicI18n::forumPath();
$forumShowUrl = $siteUrl . PublicI18n::forumPath((string) $forum->slug);
?>

<div class="cmsforum">
    <div class="cmsforum-container">

        <!-- Breadcrumb -->
        <nav class="cmsforum-breadcrumb" aria-label="<?php echo htmlspecialchars(PublicI18n::t('breadcrumb.label', 'Breadcrumb'), ENT_QUOTES, 'UTF-8'); ?>">
            <a href="<?php echo $siteUrl; ?>/"><?php echo htmlspecialchars(PublicI18n::t('breadcrumb.homepage', 'Startseite'), ENT_QUOTES, 'UTF-8'); ?></a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <a href="<?php echo $forumUrl; ?>"><?php echo htmlspecialchars(PublicI18n::t('forum', 'Forum'), ENT_QUOTES, 'UTF-8'); ?></a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <a href="<?php echo $forumShowUrl; ?>"><?php echo htmlspecialchars((string) $forum->name, ENT_QUOTES, 'UTF-8'); ?></a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <span class="cmsforum-breadcrumb__current" aria-current="page"><?php echo htmlspecialchars(PublicI18n::t('action.new_thread', 'Neues Thema'), ENT_QUOTES, 'UTF-8'); ?></span>
        </nav>

        <div class="cmsforum-page-header">
            <h1 class="cmsforum-page-header__title"><?php echo htmlspecialchars(PublicI18n::t('action.new_thread', 'Neues Thema'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="cmsforum-page-header__desc"><?php echo htmlspecialchars(PublicI18n::t('thread.create_in_forum_short', 'in %s', (string) $forum->name), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <?php if ($error): ?>
            <div class="cmsforum-alert cmsforum-alert--error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="cmsforum-form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create_thread">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

            <!-- Titel -->
            <div class="cmsforum-form-group">
                <label for="thread-title" class="cmsforum-label"><?php echo htmlspecialchars(PublicI18n::t('thread.title', 'Titel'), ENT_QUOTES, 'UTF-8'); ?> <span class="cmsforum-required">*</span></label>
                  <input type="text" id="thread-title" name="title" class="cmsforum-input"
                      value="<?php echo htmlspecialchars((string) ($_POST['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                       required minlength="3" maxlength="200" placeholder="<?php echo htmlspecialchars(PublicI18n::t('thread.title_placeholder', 'Themen-Titel eingeben...'), ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="cmsforum-form-group cmsforum-similar-threads" id="cmsforum-similar-threads" hidden data-api-url="<?php echo htmlspecialchars((string) ($similarApiUrl ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-forum-id="<?php echo (int) $forum->id; ?>">
                <strong class="cmsforum-similar-threads__title"><?php echo htmlspecialchars(PublicI18n::t('thread.similar.title', 'Ähnliche Themen gefunden'), ENT_QUOTES, 'UTF-8'); ?></strong>
                <p class="cmsforum-similar-threads__hint"><?php echo htmlspecialchars(PublicI18n::t('thread.similar.hint', 'Prüfe bestehende Diskussionen, bevor du ein neues Thema erstellst.'), ENT_QUOTES, 'UTF-8'); ?></p>
                <ul class="cmsforum-similar-threads__list"></ul>
            </div>

            <!-- Thread-Typ (nur für Moderatoren) -->
            <?php if (\CMS_Forum\Services\PermissionService::instance()->canModerate((int)$forum->id)): ?>
            <div class="cmsforum-form-group">
                <label class="cmsforum-label"><?php echo htmlspecialchars(PublicI18n::t('thread.type', 'Typ'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="cmsforum-radio-group">
                    <label class="cmsforum-radio"><input type="radio" name="type" value="normal" checked> <?php echo htmlspecialchars(PublicI18n::t('thread.type.normal', 'Normal'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <label class="cmsforum-radio"><input type="radio" name="type" value="sticky"> <?php echo htmlspecialchars(PublicI18n::t('thread.type.sticky', 'Angepinnt'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <label class="cmsforum-radio"><input type="radio" name="type" value="announcement"> <?php echo htmlspecialchars(PublicI18n::t('thread.type.announcement', 'Ankündigung'), ENT_QUOTES, 'UTF-8'); ?></label>
                </div>
            </div>
            <?php endif; ?>

            <!-- Inhalt -->
            <div class="cmsforum-form-group">
                <label for="thread-content" class="cmsforum-label"><?php echo htmlspecialchars(PublicI18n::t('post', 'Beitrag'), ENT_QUOTES, 'UTF-8'); ?> <span class="cmsforum-required">*</span></label>
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
                    <textarea name="content" id="thread-content" class="cmsforum-editor__textarea"
                              rows="12" required minlength="3" maxlength="50000"
                              placeholder="<?php echo htmlspecialchars(PublicI18n::t('thread.content_placeholder', 'Dein Beitrag...'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($_POST['content'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            </div>

            <!-- Umfrage (optional) -->
            <details class="cmsforum-details">
                <summary class="cmsforum-details__summary">🗳️ <?php echo htmlspecialchars(PublicI18n::t('poll.create', 'Umfrage erstellen (optional)'), ENT_QUOTES, 'UTF-8'); ?></summary>
                <div class="cmsforum-details__body">
                    <div class="cmsforum-form-group">
                        <label for="poll-question" class="cmsforum-label"><?php echo htmlspecialchars(PublicI18n::t('poll.question', 'Frage'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="text" id="poll-question" name="poll_question" class="cmsforum-input" maxlength="255"
                               placeholder="<?php echo htmlspecialchars(PublicI18n::t('poll.question_placeholder', 'Deine Umfrage-Frage...'), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="cmsforum-form-group" id="poll-options-container">
                        <label class="cmsforum-label"><?php echo htmlspecialchars(PublicI18n::t('poll.options', 'Optionen'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <div class="cmsforum-poll-options">
                            <input type="text" name="poll_options[]" class="cmsforum-input" maxlength="200" placeholder="<?php echo htmlspecialchars(PublicI18n::t('poll.option_number', 'Option %d', 1), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="text" name="poll_options[]" class="cmsforum-input" maxlength="200" placeholder="<?php echo htmlspecialchars(PublicI18n::t('poll.option_number', 'Option %d', 2), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <button type="button" class="cmsforum-btn cmsforum-btn--secondary cmsforum-btn--sm" id="add-poll-option" data-option-prefix="<?php echo htmlspecialchars(PublicI18n::t('poll.option_prefix', 'Option'), ENT_QUOTES, 'UTF-8'); ?>">➕ <?php echo htmlspecialchars(PublicI18n::t('poll.add_option', 'Option hinzufügen'), ENT_QUOTES, 'UTF-8'); ?></button>
                    </div>
                    <div class="cmsforum-form-group">
                        <label class="cmsforum-checkbox">
                            <input type="checkbox" name="poll_multi" value="1">
                            <?php echo htmlspecialchars(PublicI18n::t('poll.multi_choice', 'Mehrfachauswahl'), ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                    </div>
                </div>
            </details>

            <!-- Submit -->
            <div class="cmsforum-form-actions">
                <a href="<?php echo $forumShowUrl; ?>" class="cmsforum-btn cmsforum-btn--secondary"><?php echo htmlspecialchars(PublicI18n::t('action.cancel', 'Abbrechen'), ENT_QUOTES, 'UTF-8'); ?></a>
                <button type="submit" class="cmsforum-btn cmsforum-btn--primary"><?php echo htmlspecialchars(PublicI18n::t('action.new_thread', 'Neues Thema'), ENT_QUOTES, 'UTF-8'); ?></button>
            </div>
        </form>

    </div><!-- /.cmsforum-container -->
</div><!-- /.cmsforum -->

<script src="<?php echo htmlspecialchars(CMS_FORUM_URL . 'assets/js/thread-create.js?v=' . CMS_FORUM_VERSION, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
