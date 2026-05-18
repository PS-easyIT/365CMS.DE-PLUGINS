<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<div class="pr-admin-shell">
    <div class="admin-page-header">
        <div>
            <h2>Promo-Einstellungen</h2>
            <p>Lege Standardtexte, Archivdarstellung und Zielverhalten für neue Promo-Elemente fest.</p>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
    <div class="alert alert-<?php echo ($notice['type'] ?? '') === 'success' ? 'success' : 'error'; ?>"><?php echo htmlspecialchars((string) ($notice['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="pr-card-grid">
        <div class="pr-info-card"><span class="pr-info-card__eyebrow">Archiv-Titel</span><span class="pr-info-card__value"><?php echo htmlspecialchars((string) ($settings['archive_title'] ?? 'Promotions & Highlights'), ENT_QUOTES, 'UTF-8'); ?></span><span class="pr-info-card__text">Öffentliche Überschrift der Promo-Übersicht.</span></div>
        <div class="pr-info-card"><span class="pr-info-card__eyebrow">Button-Standard</span><span class="pr-info-card__value"><?php echo htmlspecialchars((string) ($settings['default_button_label'] ?? 'Mehr erfahren'), ENT_QUOTES, 'UTF-8'); ?></span><span class="pr-info-card__text">Voreinstellung für neue CTAs.</span></div>
        <div class="pr-info-card"><span class="pr-info-card__eyebrow">Zielverhalten</span><span class="pr-info-card__value"><?php echo ($settings['default_target_behavior'] ?? 'same_tab') === 'new_tab' ? 'Neuer Tab' : 'Gleicher Tab'; ?></span><span class="pr-info-card__text">Standard beim Öffnen von Ziel-Links.</span></div>
    </div>

    <div class="admin-card">
        <div class="pr-panel-header"><div><h3>Grundeinstellungen</h3><p>Die Default-Werte für neue Kampagnen-Elemente und die öffentliche Übersicht.</p></div></div>
        <form method="post" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="save_settings">

            <div class="form-group"><label class="form-label">Archiv-Titel</label><input type="text" name="archive_title" class="form-control" value="<?php echo htmlspecialchars((string) ($settings['archive_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
            <div class="form-group"><label class="form-label">Archiv-Beschreibung</label><textarea name="archive_description" class="form-control" rows="3"><?php echo htmlspecialchars((string) ($settings['archive_description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
            <div class="pr-form-grid">
                <div class="form-group"><label class="form-label">Button-Standard</label><input type="text" name="default_button_label" class="form-control" value="<?php echo htmlspecialchars((string) ($settings['default_button_label'] ?? 'Mehr erfahren'), ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="form-group"><label class="form-label">Zielverhalten</label><select name="default_target_behavior" class="form-control"><option value="same_tab" <?php echo (($settings['default_target_behavior'] ?? 'same_tab') === 'same_tab') ? 'selected' : ''; ?>>Im gleichen Tab öffnen</option><option value="new_tab" <?php echo (($settings['default_target_behavior'] ?? '') === 'new_tab') ? 'selected' : ''; ?>>In neuem Tab öffnen</option></select></div>
            </div>

            <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
        </form>
    </div>
</div>
