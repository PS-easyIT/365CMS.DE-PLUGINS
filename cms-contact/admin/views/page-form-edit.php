<?php declare(strict_types=1); if (!defined('ABSPATH')) exit;
$e = fn(?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
?>

<?php include CMS_CONTACT_PLUGIN_DIR . 'admin/views/partial-section-nav.php'; ?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>✏️ Formular bearbeiten: <?php echo $e($form['title']); ?></h2>
        <p>Einstellungen, Template und Optionen für dieses Kontaktformular</p>
    </div>
    <div class="header-actions">
        <a href="?section=forms&action=fields&id=<?php echo (int)$form['id']; ?>" class="btn btn-secondary btn-sm">📝 Felder bearbeiten</a>
        <a href="/contact/<?php echo $e($form['slug']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm">👁️ Vorschau</a>
        <a href="?section=forms" class="btn btn-secondary">↩️ Zurück</a>
    </div>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo $e($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo $e($error); ?></div>
<?php endif; ?>

<form method="POST" class="admin-form">
    <input type="hidden" name="form_action" value="update_form">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

    <!-- Tabs -->
    <div class="contact-inline-tabs" data-tab-scope data-tab-content-selector=".tab-content" data-tab-button-selector="[data-contact-tab-target]">
        <button class="contact-inline-tab-btn active" data-contact-tab-target="tab-general" type="button">⚙️ Allgemein</button>
        <button class="contact-inline-tab-btn" data-contact-tab-target="tab-template" type="button">🎨 Template</button>
        <button class="contact-inline-tab-btn" data-contact-tab-target="tab-email" type="button">📧 E-Mail</button>
        <button class="contact-inline-tab-btn" data-contact-tab-target="tab-security" type="button">🔒 Sicherheit</button>
        <button class="contact-inline-tab-btn" data-contact-tab-target="tab-advanced" type="button">🔧 Erweitert</button>
    </div>

    <!-- Tab: Allgemein -->
    <div id="tab-general" class="tab-content active">
        <div class="admin-card contact-tab-panel">
            <h3>⚙️ Allgemeine Einstellungen</h3>

            <div class="contact-form-grid-2-wide">
                <div class="form-group">
                    <label class="form-label" for="title">Titel <span class="contact-required">*</span></label>
                    <input type="text" id="title" name="title" class="form-control"
                           value="<?php echo $e($form['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="slug">URL-Slug <span class="contact-required">*</span></label>
                    <input type="text" id="slug" name="slug" class="form-control"
                           value="<?php echo $e($form['slug']); ?>"
                           pattern="[a-z0-9\-]+" required>
                    <small class="form-text">Erreichbar unter /contact/<?php echo $e($form['slug']); ?></small>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Beschreibung</label>
                <textarea id="description" name="description" class="form-control" rows="2"><?php echo $e($form['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="success_message">Erfolgsmeldung</label>
                <input type="text" id="success_message" name="success_message" class="form-control"
                       value="<?php echo $e($form['success_message'] ?? ''); ?>">
            </div>

            <div class="contact-form-grid-2-wide">
                <div class="form-group">
                    <label class="form-label" for="redirect_url">Weiterleitung nach Absenden</label>
                    <input type="url" id="redirect_url" name="redirect_url" class="form-control"
                           value="<?php echo $e($form['redirect_url'] ?? ''); ?>"
                           placeholder="Leer = auf derselben Seite bleiben">
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="active" <?php echo $form['status'] === 'active' ? 'selected' : ''; ?>>✅ Aktiv</option>
                        <option value="inactive" <?php echo $form['status'] === 'inactive' ? 'selected' : ''; ?>>⏸️ Inaktiv</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Template -->
    <div id="tab-template" class="tab-content">
        <div class="admin-card contact-tab-panel">
            <h3>🎨 Template wählen</h3>
            <div class="contact-template-grid">
                <?php foreach ($templates as $key => $tpl): ?>
                <label class="contact-template-card<?php echo $form['template'] === $key ? ' is-selected' : ''; ?>">
                    <input type="radio" name="template" value="<?php echo $e($key); ?>"
                           <?php echo $form['template'] === $key ? 'checked' : ''; ?>
                           class="contact-hidden-input">
                    <div class="contact-template-card__body">
                        <div class="contact-template-card__icon"><?php echo $tpl['icon']; ?></div>
                        <div class="contact-template-card__title"><?php echo $e($tpl['name']); ?></div>
                        <div class="contact-template-card__text"><?php echo $e($tpl['description']); ?></div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Tab: E-Mail -->
    <div id="tab-email" class="tab-content">
        <div class="admin-card contact-tab-panel">
            <h3>📧 E-Mail-Einstellungen</h3>

            <div class="contact-form-grid-2-wide">
                <div class="form-group">
                    <label class="form-label" for="recipient">Empfänger E-Mail</label>
                    <input type="email" id="recipient" name="recipient" class="form-control"
                           value="<?php echo $e($form['recipient'] ?? ''); ?>"
                           placeholder="Globale Einstellung wird verwendet">
                    <small class="form-text">Leer = globaler Empfänger</small>
                </div>
                <div class="form-group">
                    <label class="form-label" for="cc_recipients">CC-Empfänger</label>
                    <input type="text" id="cc_recipients" name="cc_recipients" class="form-control"
                           value="<?php echo $e($form['cc_recipients'] ?? ''); ?>"
                           placeholder="Mehrere Adressen mit Komma trennen">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="subject_prefix">Betreff-Präfix</label>
                <input type="text" id="subject_prefix" name="subject_prefix" class="form-control"
                       value="<?php echo $e($form['subject_prefix'] ?? '[Kontakt]'); ?>"
                       placeholder="[Kontakt]" class="contact-input-compact">
            </div>
        </div>
    </div>

    <!-- Tab: Sicherheit -->
    <div id="tab-security" class="tab-content">
        <div class="admin-card contact-tab-panel">
            <h3>🔒 Sicherheitseinstellungen</h3>

            <div class="form-group">
                <label class="checkbox-label contact-checkbox-inline">
                    <input type="checkbox" name="enable_honeypot" value="1"
                           <?php echo !empty($form['enable_honeypot']) ? 'checked' : ''; ?>>
                    🍯 Honeypot-Spamschutz aktivieren
                </label>
                <small class="form-text">Unsichtbares Feld, das Bots ausfüllen. Empfohlen!</small>
            </div>

            <div class="form-group">
                <label class="checkbox-label contact-checkbox-inline">
                    <input type="checkbox" name="enable_captcha" value="1"
                           <?php echo !empty($form['enable_captcha']) ? 'checked' : ''; ?>>
                    🤖 CAPTCHA aktivieren
                </label>
                <small class="form-text">Zusätzlicher Spamschutz (einfache Matheaufgabe).</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="rate_limit">Rate-Limit (pro Stunde/IP)</label>
                <input type="number" id="rate_limit" name="rate_limit" class="form-control"
                       value="<?php echo (int)($form['rate_limit'] ?? 3); ?>"
                       min="0" max="100" class="contact-input-number-sm">
                <small class="form-text">0 = kein Limit. Standard: 3 Nachrichten pro Stunde pro IP.</small>
            </div>
        </div>
    </div>

    <!-- Tab: Erweitert -->
    <div id="tab-advanced" class="tab-content">
        <div class="admin-card contact-tab-panel">
            <h3>🔧 Erweiterte Einstellungen</h3>

            <div class="form-group">
                <label class="form-label" for="custom_css">Benutzerdefiniertes CSS</label>
                <textarea id="custom_css" name="custom_css" class="form-control" rows="6"
                          class="contact-textarea-code"
                          placeholder="/* Eigene Styles für dieses Formular */"><?php echo $e($form['custom_css'] ?? ''); ?></textarea>
                <small class="form-text">Wird nur auf der Seite dieses Formulars geladen.</small>
            </div>
        </div>
    </div>

    <!-- Speichern -->
    <div class="admin-card contact-spacing-top">
        <div class="contact-form-actions">
            <span class="contact-form-actions__meta">Änderungen werden sofort übernommen</span>
            <a href="?section=forms" class="btn btn-secondary">Abbrechen</a>
            <button type="submit" class="btn btn-primary">💾 Speichern</button>
        </div>
    </div>
</form>
