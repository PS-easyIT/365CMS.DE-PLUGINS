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
            <span class="cmsforum-breadcrumb__current" aria-current="page">Neuer Thread</span>
        </nav>

        <div class="cmsforum-page-header">
            <h1 class="cmsforum-page-header__title">➕ Neuer Thread</h1>
            <p class="cmsforum-page-header__desc">in <?php echo htmlspecialchars($forum->name); ?></p>
        </div>

        <?php if ($error): ?>
            <div class="cmsforum-alert cmsforum-alert--error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="cmsforum-form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create_thread">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

            <!-- Titel -->
            <div class="cmsforum-form-group">
                <label for="thread-title" class="cmsforum-label">Titel <span class="cmsforum-required">*</span></label>
                <input type="text" id="thread-title" name="title" class="cmsforum-input"
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                       required minlength="3" maxlength="200" placeholder="Thread-Titel eingeben...">
            </div>

            <!-- Thread-Typ (nur für Moderatoren) -->
            <?php if (\CMS_Forum\Services\PermissionService::instance()->canModerate((int)$forum->id)): ?>
            <div class="cmsforum-form-group">
                <label class="cmsforum-label">Typ</label>
                <div class="cmsforum-radio-group">
                    <label class="cmsforum-radio"><input type="radio" name="type" value="normal" checked> Normal</label>
                    <label class="cmsforum-radio"><input type="radio" name="type" value="sticky"> 📌 Angepinnt</label>
                    <label class="cmsforum-radio"><input type="radio" name="type" value="announcement"> 📢 Ankündigung</label>
                </div>
            </div>
            <?php endif; ?>

            <!-- Inhalt -->
            <div class="cmsforum-form-group">
                <label for="thread-content" class="cmsforum-label">Beitrag <span class="cmsforum-required">*</span></label>
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
                              placeholder="Dein Beitrag..."><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Umfrage (optional) -->
            <details class="cmsforum-details">
                <summary class="cmsforum-details__summary">🗳️ Umfrage hinzufügen (optional)</summary>
                <div class="cmsforum-details__body">
                    <div class="cmsforum-form-group">
                        <label for="poll-question" class="cmsforum-label">Frage</label>
                        <input type="text" id="poll-question" name="poll_question" class="cmsforum-input" maxlength="255"
                               placeholder="Deine Umfrage-Frage...">
                    </div>
                    <div class="cmsforum-form-group" id="poll-options-container">
                        <label class="cmsforum-label">Optionen</label>
                        <div class="cmsforum-poll-options">
                            <input type="text" name="poll_options[]" class="cmsforum-input" maxlength="200" placeholder="Option 1">
                            <input type="text" name="poll_options[]" class="cmsforum-input" maxlength="200" placeholder="Option 2">
                        </div>
                        <button type="button" class="cmsforum-btn cmsforum-btn--secondary cmsforum-btn--sm" id="add-poll-option">➕ Option hinzufügen</button>
                    </div>
                    <div class="cmsforum-form-group">
                        <label class="cmsforum-checkbox">
                            <input type="checkbox" name="poll_multi" value="1">
                            Mehrfachauswahl erlauben
                        </label>
                    </div>
                </div>
            </details>

            <!-- Submit -->
            <div class="cmsforum-form-actions">
                <a href="<?php echo SITE_URL; ?>/forum/<?php echo htmlspecialchars($forum->slug); ?>" class="cmsforum-btn cmsforum-btn--secondary">↩️ Abbrechen</a>
                <button type="submit" class="cmsforum-btn cmsforum-btn--primary">📝 Thread erstellen</button>
            </div>
        </form>

    </div><!-- /.cmsforum-container -->
</div><!-- /.cmsforum -->

<script>
document.getElementById('add-poll-option')?.addEventListener('click', function() {
    const container = document.querySelector('.cmsforum-poll-options');
    const count = container.querySelectorAll('input').length;
    if (count >= 10) return;
    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'poll_options[]';
    input.className = 'cmsforum-input';
    input.maxLength = 200;
    input.placeholder = 'Option ' + (count + 1);
    container.appendChild(input);
});
</script>
