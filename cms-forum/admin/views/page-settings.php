<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<div class="forum-admin-shell">

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>⚙️ Forum-Einstellungen</h2>
        <p>Globale Einstellungen für das Community-Forum</p>
    </div>
</div>

<?php if (isset($success) && $success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if (isset($error) && $error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="forum-card-grid">
    <div class="forum-info-card">
        <span class="forum-info-card__eyebrow">Forum-Name</span>
        <span class="forum-info-card__value"><?php echo htmlspecialchars(mb_strimwidth((string) $settings['forum_name'], 0, 14, '…')); ?></span>
        <span class="forum-info-card__text">Der globale Name deiner Community-Plattform.</span>
    </div>
    <div class="forum-info-card">
        <span class="forum-info-card__eyebrow">Threads pro Seite</span>
        <span class="forum-info-card__value"><?php echo (int)$settings['threads_per_page']; ?></span>
        <span class="forum-info-card__text">Listing-Größe für die Themenübersichten.</span>
    </div>
    <div class="forum-info-card">
        <span class="forum-info-card__eyebrow">Beiträge pro Seite</span>
        <span class="forum-info-card__value"><?php echo (int)$settings['posts_per_page']; ?></span>
        <span class="forum-info-card__text">Pagination-Größe in den Thread-Ansichten.</span>
    </div>
</div>

<form method="POST" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <input type="hidden" name="forum_action" value="save_settings">

    <!-- Allgemein -->
    <div class="admin-card">
        <div class="forum-panel-header">
            <div>
                <h3>📋 Allgemein</h3>
                <p>Grundaufbau, Seitengrößen und globale Darstellung des Forums.</p>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Forum-Name</label>
            <input type="text" name="forum_name" class="form-control" value="<?php echo htmlspecialchars($settings['forum_name']); ?>">
        </div>
        <div class="form-grid forum-card-grid" style="grid-template-columns:1fr 1fr;">
            <div class="form-group">
                <label class="form-label">Threads pro Seite</label>
                <input type="number" name="threads_per_page" class="form-control" value="<?php echo (int)$settings['threads_per_page']; ?>" min="5" max="100">
            </div>
            <div class="form-group">
                <label class="form-label">Beiträge pro Seite</label>
                <input type="number" name="posts_per_page" class="form-control" value="<?php echo (int)$settings['posts_per_page']; ?>" min="5" max="100">
            </div>
        </div>
    </div>

    <!-- Spam-Schutz -->
    <div class="admin-card">
        <div class="forum-panel-header">
            <div>
                <h3>🛡️ Spam-Schutz</h3>
                <p>Flood-Control und Moderationsfreigaben gegen Missbrauch.</p>
            </div>
        </div>
        <div class="form-grid forum-card-grid" style="grid-template-columns:1fr 1fr;">
            <div class="form-group">
                <label class="form-label">Flood-Intervall für Beiträge (Sek.)</label>
                <input type="number" name="flood_interval_post" class="form-control" value="<?php echo (int)$settings['flood_interval_post']; ?>" min="0">
                <small class="form-text">Zeitspanne zwischen zwei Beiträgen eines Benutzers</small>
            </div>
            <div class="form-group">
                <label class="form-label">Flood-Intervall für Threads (Sek.)</label>
                <input type="number" name="flood_interval_thread" class="form-control" value="<?php echo (int)$settings['flood_interval_thread']; ?>" min="0">
            </div>
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="require_post_approval" value="1" <?php echo $settings['require_post_approval'] === '1' ? 'checked' : ''; ?>>
                Beiträge müssen von Moderatoren freigeschaltet werden
            </label>
        </div>
    </div>

    <!-- Beiträge -->
    <div class="admin-card">
        <div class="forum-panel-header">
            <div>
                <h3>✏️ Beiträge</h3>
                <p>Grenzen, Bearbeitungsfenster und Content-Regeln definieren.</p>
            </div>
        </div>
        <div class="form-grid forum-card-grid" style="grid-template-columns:1fr 1fr 1fr;">
            <div class="form-group">
                <label class="form-label">Max. Titel-Länge</label>
                <input type="number" name="max_title_length" class="form-control" value="<?php echo (int)$settings['max_title_length']; ?>" min="10">
            </div>
            <div class="form-group">
                <label class="form-label">Min. Beitragslänge</label>
                <input type="number" name="min_post_length" class="form-control" value="<?php echo (int)$settings['min_post_length']; ?>" min="1">
            </div>
            <div class="form-group">
                <label class="form-label">Max. Beitragslänge</label>
                <input type="number" name="max_post_length" class="form-control" value="<?php echo (int)$settings['max_post_length']; ?>" min="100">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Bearbeitungszeit (Minuten, 0 = unbegrenzt)</label>
            <input type="number" name="members_can_edit_time" class="form-control" value="<?php echo (int)$settings['members_can_edit_time']; ?>" min="0" style="max-width:200px;">
        </div>
    </div>

    <!-- Features -->
    <div class="admin-card">
        <div class="forum-panel-header">
            <div>
                <h3>🧩 Features</h3>
                <p>Schalte Foren-Funktionen passend zur Community-Größe und Moderation frei.</p>
            </div>
        </div>
        <div class="forum-settings-features">
            <label class="checkbox-label"><input type="checkbox" name="enable_bbcode" value="1" <?php echo $settings['enable_bbcode'] === '1' ? 'checked' : ''; ?>> BBCode aktivieren</label>
            <label class="checkbox-label"><input type="checkbox" name="enable_polls" value="1" <?php echo $settings['enable_polls'] === '1' ? 'checked' : ''; ?>> Umfragen aktivieren</label>
            <label class="checkbox-label"><input type="checkbox" name="enable_attachments" value="1" <?php echo $settings['enable_attachments'] === '1' ? 'checked' : ''; ?>> Datei-Anhänge aktivieren</label>
            <label class="checkbox-label"><input type="checkbox" name="enable_likes" value="1" <?php echo $settings['enable_likes'] === '1' ? 'checked' : ''; ?>> Likes aktivieren</label>
            <label class="checkbox-label"><input type="checkbox" name="enable_signatures" value="1" <?php echo $settings['enable_signatures'] === '1' ? 'checked' : ''; ?>> Signaturen anzeigen</label>
            <label class="checkbox-label"><input type="checkbox" name="enable_dark_mode" value="1" <?php echo $settings['enable_dark_mode'] === '1' ? 'checked' : ''; ?>> Dark Mode erlauben</label>
            <label class="checkbox-label"><input type="checkbox" name="guest_can_read" value="1" <?php echo $settings['guest_can_read'] === '1' ? 'checked' : ''; ?>> Gäste können lesen</label>
            <label class="checkbox-label"><input type="checkbox" name="auto_subscribe_own" value="1" <?php echo $settings['auto_subscribe_own'] === '1' ? 'checked' : ''; ?>> Eigene Threads automatisch abonnieren</label>
        </div>
    </div>

    <!-- Datei-Upload -->
    <div class="admin-card">
        <div class="forum-panel-header">
            <div>
                <h3>📎 Datei-Upload</h3>
                <p>Anhangsgrößen und erlaubte Dateitypen für Beiträge steuern.</p>
            </div>
        </div>
        <div class="form-grid forum-card-grid" style="grid-template-columns:1fr 1fr;">
            <div class="form-group">
                <label class="form-label">Max. Dateigröße (Bytes)</label>
                <input type="number" name="max_attachment_size" class="form-control" value="<?php echo (int)$settings['max_attachment_size']; ?>" min="0">
                <small class="form-text">Standard: 5242880 (5 MB)</small>
            </div>
            <div class="form-group">
                <label class="form-label">Erlaubte Dateierweiterungen</label>
                <input type="text" name="allowed_extensions" class="form-control" value="<?php echo htmlspecialchars($settings['allowed_extensions']); ?>">
                <small class="form-text">Komma-getrennt, z. B. jpg,png,pdf,zip</small>
            </div>
        </div>
    </div>

    <!-- Design -->
    <div class="admin-card">
        <div class="forum-panel-header">
            <div>
                <h3>🎨 Design</h3>
                <p>Grundfarbe des Forums passend zum restlichen 365CMS-Design abstimmen.</p>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Primärfarbe</label>
            <div class="forum-inline-actions" style="align-items:center;">
                <input type="color" name="primary_color" value="<?php echo htmlspecialchars($settings['primary_color']); ?>" style="width:50px;height:40px;border:none;padding:0;cursor:pointer;">
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($settings['primary_color']); ?>" style="max-width:150px;" readonly>
            </div>
        </div>
    </div>

    <!-- Save -->
    <div class="admin-card form-actions-card">
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
            <span class="form-actions__hint">Änderungen werden sofort übernommen</span>
        </div>
    </div>
</form>

<!-- Wartungs-Aktionen -->
<div class="admin-card">
    <div class="forum-panel-header">
        <div>
            <h3>🔧 Wartung</h3>
            <p>Zählerstände und interne Kennzahlen bei Bedarf neu aufbauen.</p>
        </div>
    </div>
    <div class="forum-inline-actions">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="forum_action" value="recalculate_counters">
            <button type="submit" class="btn btn-secondary btn-sm">🔄 Zähler neu berechnen</button>
        </form>
    </div>
</div>

</div>
