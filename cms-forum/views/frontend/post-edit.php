<?php
/**
 * CMS Forum – Beitrag bearbeiten
 *
 * Verfügbare Variablen:
 *   $post       – Post-Objekt
 *   $thread     – Thread-Objekt
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
?>

<div class="cmsforum">
    <div class="cmsforum-container">

        <!-- Breadcrumb -->
        <nav class="cmsforum-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo SITE_URL; ?>/">Startseite</a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <a href="<?php echo SITE_URL; ?>/forum">Forum</a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <a href="<?php echo SITE_URL; ?>/forum/thread/<?php echo (int)$thread->id; ?>"><?php echo htmlspecialchars($thread->title); ?></a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <span class="cmsforum-breadcrumb__current" aria-current="page">Bearbeiten</span>
        </nav>

        <div class="cmsforum-page-header">
            <h1 class="cmsforum-page-header__title">✏️ Beitrag bearbeiten</h1>
        </div>

        <?php if ($error): ?>
            <div class="cmsforum-alert cmsforum-alert--error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="cmsforum-form">
            <input type="hidden" name="action" value="edit_post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="cmsforum-form-group">
                <label for="edit-content" class="cmsforum-label">Beitrag</label>
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
                    </div>
                    <textarea name="content" id="edit-content" class="cmsforum-editor__textarea"
                              rows="12" required minlength="3" maxlength="50000"><?php echo htmlspecialchars($post->content); ?></textarea>
                </div>
            </div>

            <div class="cmsforum-form-actions">
                <a href="<?php echo SITE_URL; ?>/forum/thread/<?php echo (int)$thread->id; ?>#post-<?php echo (int)$post->id; ?>" class="cmsforum-btn cmsforum-btn--secondary">↩️ Abbrechen</a>
                <button type="submit" class="cmsforum-btn cmsforum-btn--primary">💾 Änderungen speichern</button>
            </div>
        </form>

    </div><!-- /.cmsforum-container -->
</div><!-- /.cmsforum -->
