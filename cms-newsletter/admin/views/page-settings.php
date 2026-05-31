<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<?php
$sectionTitles = [
    'general' => 'Allgemein',
    'content' => 'Inhalte',
    'compliance' => 'Compliance',
];
$activeSectionTitle = $sectionTitles[$tab] ?? 'Allgemein';
?>

<div class="nl-admin-shell">
    <div class="admin-page-header">
        <div>
            <h2>Newsletter-Einstellungen</h2>
            <p>Definiere Absender, Standardsegment, Double-Opt-In und Texte für die öffentliche Anmeldung.</p>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
    <div class="alert alert-<?php echo ($notice['type'] ?? '') === 'success' ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars((string) ($notice['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php endif; ?>

    <div class="alert alert-success nl-settings-note">
        Aktiver Bereich: <strong><?php echo htmlspecialchars($activeSectionTitle, ENT_QUOTES, 'UTF-8'); ?></strong>. Die Navigation erfolgt ueber das Sidebar-Submenu.
    </div>

    <div class="admin-card nl-tab-panel">
        <form method="post" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="save_settings">
            <input type="hidden" name="redirect_slug" value="<?php echo htmlspecialchars((string) ($activeSlug ?? 'newsletter-settings-general'), ENT_QUOTES, 'UTF-8'); ?>">

            <?php if ($tab === 'general'): ?>
                <h3>Allgemein</h3>
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
                <h3>Öffentliche Texte</h3>
                <div class="form-group">
                    <label class="form-label">Archiv-Titel</label>
                    <input type="text" name="archive_title" class="form-control" value="<?php echo htmlspecialchars((string) ($settings['archive_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Archive Title (EN)</label>
                    <input type="text" name="archive_title_en" class="form-control" value="<?php echo htmlspecialchars((string) ($settings['archive_title_en'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Archiv-Beschreibung</label>
                    <textarea name="archive_description" rows="3" class="form-control"><?php echo htmlspecialchars((string) ($settings['archive_description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Archive Description (EN)</label>
                    <textarea name="archive_description_en" rows="3" class="form-control"><?php echo htmlspecialchars((string) ($settings['archive_description_en'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Intro-Text</label>
                    <textarea name="subscribe_intro" rows="4" class="form-control"><?php echo htmlspecialchars((string) ($settings['subscribe_intro'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Intro Text (EN)</label>
                    <textarea name="subscribe_intro_en" rows="4" class="form-control"><?php echo htmlspecialchars((string) ($settings['subscribe_intro_en'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Footer-Hinweis</label>
                    <textarea name="footer_note" rows="3" class="form-control"><?php echo htmlspecialchars((string) ($settings['footer_note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Footer Note (EN)</label>
                    <textarea name="footer_note_en" rows="3" class="form-control"><?php echo htmlspecialchars((string) ($settings['footer_note_en'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            <?php else: ?>
                <h3>Compliance & Opt-In</h3>
                <label class="checkbox-label nl-checkbox-stack">
                    <input type="checkbox" name="require_double_opt_in" value="1" <?php echo !empty($settings['require_double_opt_in']) ? 'checked' : ''; ?>>
                    Double Opt-In für öffentliche Anmeldungen erzwingen
                </label>
                <div class="alert alert-success nl-settings-note">
                    Bei aktivem Double Opt-In landen neue Kontakte zunächst im Status <strong>pending</strong>. Das Plugin ist damit bereit für einen sauberen DSGVO-konformen Freigabeprozess.
                </div>
                <div class="alert alert-success nl-settings-note">
                    <strong>BIMI/SPF/DKIM/DMARC Check fuer <?php echo htmlspecialchars((string) ($diagnostics['domain'] ?? 'keine Domain'), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <ul class="newsletter-list">
                        <li>
                            <strong>SPF:</strong>
                            <?php echo !empty($diagnostics['spf']['ok']) ? 'OK' : 'Fehlt'; ?>
                            - <?php echo htmlspecialchars((string) ($diagnostics['spf']['detail'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </li>
                        <li>
                            <strong>DMARC:</strong>
                            <?php echo !empty($diagnostics['dmarc']['ok']) ? 'OK' : 'Fehlt'; ?>
                            - <?php echo htmlspecialchars((string) ($diagnostics['dmarc']['detail'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </li>
                        <li>
                            <strong>DKIM:</strong>
                            <?php echo !empty($diagnostics['dkim']['ok']) ? 'OK' : 'Hinweis'; ?>
                            - <?php echo htmlspecialchars((string) ($diagnostics['dkim']['detail'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </li>
                        <li>
                            <strong>BIMI:</strong>
                            <?php echo !empty($diagnostics['bimi']['ok']) ? 'OK' : 'Fehlt'; ?>
                            - <?php echo htmlspecialchars((string) ($diagnostics['bimi']['detail'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
        </form>
    </div>
</div>
