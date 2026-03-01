<?php declare(strict_types=1); if (!defined('ABSPATH')) exit;
$e = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>✏️ Formular bearbeiten: <?php echo $e($form['title']); ?></h2>
        <p>Einstellungen, Template und Optionen für dieses Kontaktformular</p>
    </div>
    <div class="header-actions">
        <a href="?page=contact-forms&action=fields&id=<?php echo (int)$form['id']; ?>" class="btn btn-secondary btn-sm">📝 Felder bearbeiten</a>
        <a href="/contact/<?php echo $e($form['slug']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm">👁️ Vorschau</a>
        <a href="?page=contact-forms" class="btn btn-secondary">↩️ Zurück</a>
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
    <div class="tabs" style="margin-bottom:0;display:flex;gap:.3rem;border-bottom:2px solid #e2e8f0;flex-wrap:wrap;">
        <button class="tab-btn active" onclick="switchTab('tab-general', this)" type="button">⚙️ Allgemein</button>
        <button class="tab-btn" onclick="switchTab('tab-template', this)" type="button">🎨 Template</button>
        <button class="tab-btn" onclick="switchTab('tab-email', this)" type="button">📧 E-Mail</button>
        <button class="tab-btn" onclick="switchTab('tab-security', this)" type="button">🔒 Sicherheit</button>
        <button class="tab-btn" onclick="switchTab('tab-advanced', this)" type="button">🔧 Erweitert</button>
    </div>

    <!-- Tab: Allgemein -->
    <div id="tab-general" class="tab-content active">
        <div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;">
            <h3>⚙️ Allgemeine Einstellungen</h3>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="title">Titel <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="title" name="title" class="form-control"
                           value="<?php echo $e($form['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="slug">URL-Slug <span style="color:#ef4444;">*</span></label>
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

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
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
        <div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;">
            <h3>🎨 Template wählen</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
                <?php foreach ($templates as $key => $tpl): ?>
                <label class="contact-template-card" style="cursor:pointer;display:block;border:2px solid <?php echo $form['template'] === $key ? '#3b82f6' : '#e2e8f0'; ?>;border-radius:10px;padding:1.25rem;text-align:center;transition:all .2s;">
                    <input type="radio" name="template" value="<?php echo $e($key); ?>"
                           <?php echo $form['template'] === $key ? 'checked' : ''; ?>
                           style="display:none;"
                           onchange="document.querySelectorAll('.contact-template-card').forEach(c=>c.style.borderColor='#e2e8f0');this.closest('label').style.borderColor='#3b82f6';">
                    <div style="font-size:2rem;margin-bottom:.5rem;"><?php echo $tpl['icon']; ?></div>
                    <div style="font-weight:600;color:#1e293b;"><?php echo $e($tpl['name']); ?></div>
                    <div style="font-size:.8rem;color:#64748b;margin-top:.25rem;"><?php echo $e($tpl['description']); ?></div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Tab: E-Mail -->
    <div id="tab-email" class="tab-content">
        <div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;">
            <h3>📧 E-Mail-Einstellungen</h3>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
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
                       placeholder="[Kontakt]" style="max-width:300px;">
            </div>
        </div>
    </div>

    <!-- Tab: Sicherheit -->
    <div id="tab-security" class="tab-content">
        <div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;">
            <h3>🔒 Sicherheitseinstellungen</h3>

            <div class="form-group">
                <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
                    <input type="checkbox" name="enable_honeypot" value="1"
                           <?php echo !empty($form['enable_honeypot']) ? 'checked' : ''; ?>>
                    🍯 Honeypot-Spamschutz aktivieren
                </label>
                <small class="form-text">Unsichtbares Feld, das Bots ausfüllen. Empfohlen!</small>
            </div>

            <div class="form-group">
                <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
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
                       min="0" max="100" style="max-width:120px;">
                <small class="form-text">0 = kein Limit. Standard: 3 Nachrichten pro Stunde pro IP.</small>
            </div>
        </div>
    </div>

    <!-- Tab: Erweitert -->
    <div id="tab-advanced" class="tab-content">
        <div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;">
            <h3>🔧 Erweiterte Einstellungen</h3>

            <div class="form-group">
                <label class="form-label" for="custom_css">Benutzerdefiniertes CSS</label>
                <textarea id="custom_css" name="custom_css" class="form-control" rows="6"
                          style="font-family:monospace;font-size:.85rem;"
                          placeholder="/* Eigene Styles für dieses Formular */"><?php echo $e($form['custom_css'] ?? ''); ?></textarea>
                <small class="form-text">Wird nur auf der Seite dieses Formulars geladen.</small>
            </div>
        </div>
    </div>

    <!-- Speichern -->
    <div class="admin-card" style="margin-top:1rem;">
        <div style="display:flex;justify-content:flex-end;gap:.6rem;align-items:center;">
            <span style="color:#64748b;font-size:.85rem;">Änderungen werden sofort übernommen</span>
            <a href="?page=contact-forms" class="btn btn-secondary">Abbrechen</a>
            <button type="submit" class="btn btn-primary">💾 Speichern</button>
        </div>
    </div>
</form>
