<?php
/**
 * CMS Forum – Member Forum-Einstellungen
 *
 * Persönliche Forum-Einstellungen des Benutzers.
 * Verfügbare Variablen:
 *   $meta       – UserMeta-Objekt
 *   $csrfToken  – CSRF-Token
 *   $success    – string|null
 *   $error      – string|null
 *
 * @package CMS_Forum\Views
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="cmsforum-member-settings">
    <h2 class="cmsforum-member-settings__title">💬 Forum-Profil</h2>

    <?php if ($success): ?>
        <div class="cmsforum-alert cmsforum-alert--success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="cmsforum-alert cmsforum-alert--error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" class="cmsforum-form">
        <input type="hidden" name="action" value="save_forum_settings">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

        <!-- Signatur -->
        <div class="cmsforum-form-group">
            <label for="forum-signature" class="cmsforum-label">Signatur</label>
            <textarea id="forum-signature" name="signature" class="cmsforum-input" rows="3" maxlength="500"
                      placeholder="Deine Forum-Signatur..."><?php echo htmlspecialchars($meta->signature ?? ''); ?></textarea>
            <small class="cmsforum-hint">Wird unter deinen Beiträgen angezeigt. Max. 500 Zeichen.</small>
        </div>

        <!-- Ort -->
        <div class="cmsforum-form-group">
            <label for="forum-location" class="cmsforum-label">Ort</label>
            <input type="text" id="forum-location" name="location" class="cmsforum-input"
                   value="<?php echo htmlspecialchars($meta->location ?? ''); ?>" maxlength="100"
                   placeholder="z.B. Berlin, Deutschland">
        </div>

        <!-- Website -->
        <div class="cmsforum-form-group">
            <label for="forum-website" class="cmsforum-label">Website</label>
            <input type="url" id="forum-website" name="website" class="cmsforum-input"
                   value="<?php echo htmlspecialchars($meta->website ?? ''); ?>" maxlength="255"
                   placeholder="https://example.com">
        </div>

        <!-- Benachrichtigungen -->
        <div class="cmsforum-form-group">
            <h3 class="cmsforum-label">Benachrichtigungen</h3>
            <label class="cmsforum-checkbox">
                <input type="checkbox" name="notify_on_reply" value="1"
                       <?php echo ($meta->notify_on_reply ?? 1) ? 'checked' : ''; ?>>
                E-Mail-Benachrichtigung bei neuen Antworten auf abonnierte Threads
            </label>
            <label class="cmsforum-checkbox">
                <input type="checkbox" name="notify_on_mention" value="1"
                       <?php echo ($meta->notify_on_mention ?? 1) ? 'checked' : ''; ?>>
                E-Mail-Benachrichtigung bei Erwähnungen
            </label>
        </div>

        <div class="cmsforum-form-actions">
            <button type="submit" class="cmsforum-btn cmsforum-btn--primary">💾 Speichern</button>
        </div>
    </form>
</div>
