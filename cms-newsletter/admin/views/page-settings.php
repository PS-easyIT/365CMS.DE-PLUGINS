<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<?php $tabs = ['general' => '⚙️ Allgemein', 'content' => '📝 Inhalte', 'compliance' => '🛡️ Compliance']; ?>

<div class="nl-admin-shell">
    <div class="admin-page-header">
        <div>
            <h2>⚙️ Newsletter-Einstellungen</h2>
            <p>Definiere Absender, Standardsegment, Double-Opt-In und Texte für die öffentliche Anmeldung.</p>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
    <div class="alert alert-<?php echo ($notice['type'] ?? '') === 'success' ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars((string) ($notice['message'] ?? '')); ?>
    </div>
    <?php endif; ?>

    <div class="nl-tabs">
        <?php foreach ($tabs as $key => $label): ?>
            <a href="?tab=<?php echo urlencode($key); ?>" class="nl-tab<?php echo $tab === $key ? ' active' : ''; ?>"><?php echo htmlspecialchars($label); ?></a>
        <?php endforeach; ?>
    </div>

    <div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;">
        <form method="post" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="save_settings">

            <?php if ($tab === 'general'): ?>
                <h3>⚙️ Allgemein</h3>
                <div class="nl-form-grid">
                    <div class="form-group">
                        <label class="form-label">Absender-Name</label>
                        <input type="text" name="sender_name" class="form-control" value="<?php echo htmlspecialchars((string) ($settings['sender_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Absender-E-Mail</label>
                        <input type="email" name="sender_email" class="form-control" value="<?php echo htmlspecialchars((string) ($settings['sender_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <div class="nl-form-grid">
                    <div class="form-group">
                        <label class="form-label">Reply-To-E-Mail</label>
                        <input type="email" name="reply_to_email" class="form-control" value="<?php echo htmlspecialchars((string) ($settings['reply_to_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Standard-Segment</label>
                        <input type="text" name="default_segment" class="form-control" value="<?php echo htmlspecialchars((string) ($settings['default_segment'] ?? 'general'), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
            <?php elseif ($tab === 'content'): ?>
                <h3>📝 Öffentliche Texte</h3>
                <div class="form-group">
                    <label class="form-label">Archiv-Titel</label>
                    <input type="text" name="archive_title" class="form-control" value="<?php echo htmlspecialchars((string) ($settings['archive_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Archiv-Beschreibung</label>
                    <textarea name="archive_description" rows="3" class="form-control"><?php echo htmlspecialchars((string) ($settings['archive_description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Intro-Text</label>
                    <textarea name="subscribe_intro" rows="4" class="form-control"><?php echo htmlspecialchars((string) ($settings['subscribe_intro'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Footer-Hinweis</label>
                    <textarea name="footer_note" rows="3" class="form-control"><?php echo htmlspecialchars((string) ($settings['footer_note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            <?php else: ?>
                <h3>🛡️ Compliance & Opt-In</h3>
                <label class="checkbox-label nl-checkbox-stack">
                    <input type="checkbox" name="require_double_opt_in" value="1" <?php echo !empty($settings['require_double_opt_in']) ? 'checked' : ''; ?>>
                    Double Opt-In für öffentliche Anmeldungen erzwingen
                </label>
                <div class="alert alert-success" style="margin-top:1rem;">
                    💡 Bei aktivem Double Opt-In landen neue Kontakte zunächst im Status <strong>pending</strong>. Das Plugin ist damit bereit für einen sauberen DSGVO-konformen Freigabeprozess.
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
        </form>
    </div>
</div>
