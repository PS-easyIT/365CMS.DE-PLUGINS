<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<div class="dl-admin-shell">

<div class="admin-page-header">
    <div>
        <h2>⚙️ Download-Einstellungen</h2>
        <p>Steuere Titel, Archivdarstellung und Suchverhalten des öffentlichen Download-Bereichs.</p>
    </div>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-<?php echo ($notice['type'] ?? '') === 'success' ? 'success' : 'error'; ?>">
    <?php echo htmlspecialchars((string) ($notice['message'] ?? '')); ?>
</div>
<?php endif; ?>

<div class="dl-card-grid">
    <div class="dl-info-card">
        <span class="dl-info-card__eyebrow">Archivname</span>
        <span class="dl-info-card__value"><?php echo htmlspecialchars((string) mb_strimwidth((string) ($settings['archive_title'] ?? 'Downloads'), 0, 14, '…')); ?></span>
        <span class="dl-info-card__text">Titel des öffentlichen Download-Archivs.</span>
    </div>
    <div class="dl-info-card">
        <span class="dl-info-card__eyebrow">Suche</span>
        <span class="dl-info-card__value"><?php echo !empty($settings['show_search']) ? 'Ein' : 'Aus'; ?></span>
        <span class="dl-info-card__text">Steuert die Freitextsuche im öffentlichen Archiv.</span>
    </div>
    <div class="dl-info-card">
        <span class="dl-info-card__eyebrow">Kategorien im Archiv</span>
        <span class="dl-info-card__value"><?php echo !empty($settings['show_category_overview']) ? 'Sichtbar' : 'Versteckt'; ?></span>
        <span class="dl-info-card__text">Schaltet die Kategorie-Übersicht für Besucher ein oder aus.</span>
    </div>
</div>

<div class="dl-settings-grid">
    <div class="admin-card">
        <div class="dl-panel-header">
            <div>
                <h3>⚙️ Allgemein</h3>
                <p>Lege fest, wie dein öffentliches Download-Archiv im Standard aussieht.</p>
            </div>
        </div>
        <form method="post" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="save_settings">

            <div class="form-group">
                <label class="form-label">Archiv-Titel</label>
                <input type="text" name="archive_title" class="form-control" value="<?php echo htmlspecialchars((string) ($settings['archive_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Archiv-Beschreibung</label>
                <textarea name="archive_description" class="form-control" rows="4"><?php echo htmlspecialchars((string) ($settings['archive_description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="dl-admin-form-grid">
                <div class="form-group">
                    <label class="form-label">Downloads pro Seite</label>
                    <input type="number" name="downloads_per_page" class="form-control" min="6" max="120" value="<?php echo (int) ($settings['downloads_per_page'] ?? 24); ?>">
                </div>
                <div class="form-group">
                    <label class="checkbox-label dl-checkbox-stack"><input type="checkbox" name="show_search" value="1" <?php echo !empty($settings['show_search']) ? 'checked' : ''; ?>> Suchfeld im Archiv anzeigen</label>
                    <label class="checkbox-label dl-checkbox-stack"><input type="checkbox" name="show_category_overview" value="1" <?php echo !empty($settings['show_category_overview']) ? 'checked' : ''; ?>> Kategorien-Übersicht im Archiv anzeigen</label>
                    <label class="checkbox-label dl-checkbox-stack"><input type="checkbox" name="show_external_notice" value="1" <?php echo !empty($settings['show_external_notice']) ? 'checked' : ''; ?>> Zwischenseite vor externen Downloads anzeigen</label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Erlaubte Domains für externe Downloads</label>
                <textarea name="external_allowed_domains" class="form-control" rows="5" placeholder="downloads.example.com&#10;cdn.example.org"><?php echo htmlspecialchars((string) ($settings['external_allowed_domains'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                <small class="form-text">Eine Domain pro Zeile oder komma-separiert. Leer = alle gültigen externen Hosts erlauben. Subdomains eines erlaubten Hosts sind ebenfalls zulässig.</small>
            </div>

            <div class="alert alert-success">
                💡 Die PowerShell-, Webprojekt-, Dokument- und eBook-Typen kommen als vordefinierte Download-Templates direkt aus dem Plugin und stehen bei jedem Download-Eintrag zur Auswahl bereit. Externe Ziele kannst du hier zusätzlich auf definierte Domains eingrenzen.
            </div>

            <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
        </form>
    </div>

    <div class="dl-side-stack">
        <div class="dl-note-card">
            <span class="dl-note-card__eyebrow">Frontend-Vorschau</span>
            <span class="dl-note-card__title">Was Besucher sehen</span>
            <span class="dl-note-card__text">Titel, Beschreibung, Suche und Kategorie-Übersicht bilden gemeinsam den ersten Eindruck deines Download-Hubs.</span>
        </div>

        <div class="admin-card">
            <div class="dl-panel-header">
                <div>
                    <h3>🧭 Empfehlung</h3>
                    <p>Praxisnahe Standardeinstellungen für ein aufgeräumtes Archiv.</p>
                </div>
            </div>
            <div class="dl-badge-stack">
                <span class="dl-soft-badge">24 pro Seite</span>
                <span class="dl-soft-badge">Suche aktiv</span>
                <span class="dl-soft-badge">Kategorien sichtbar</span>
            </div>
            <p class="dl-admin-muted dl-admin-muted--spaced">Gerade bei gemischten Inhalten wie Skripten, Webprojekten, Dokumenten und eBooks wirkt das Archiv damit deutlich strukturierter.</p>
        </div>
    </div>
</div>

</div>
