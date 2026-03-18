<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<?php include CMS_CONTACT_PLUGIN_DIR . 'admin/views/partial-section-nav.php'; ?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>➕ Neues Kontaktformular</h2>
        <p>Erstelle ein neues Kontaktformular mit individuellem Template</p>
    </div>
    <div class="header-actions">
        <a href="?section=forms" class="btn btn-secondary">↩️ Zurück</a>
    </div>
</div>

<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST" class="admin-form">
    <input type="hidden" name="form_action" value="create_form">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

    <div class="admin-card">
        <h3>📋 Grundeinstellungen</h3>

        <div class="contact-form-intro-grid">
            <div class="form-group">
                <label class="form-label" for="title">Titel <span class="contact-required">*</span></label>
                <input type="text" id="title" name="title" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                       placeholder="z. B. Kontakt, Anfrage, Bewerbung" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="slug">URL-Slug</label>
                <input type="text" id="slug" name="slug" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>"
                       placeholder="Wird automatisch generiert"
                       pattern="[a-z0-9\-]+" title="Nur Kleinbuchstaben, Zahlen und Bindestriche">
                <small class="form-text">Erreichbar unter /contact/{slug}</small>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Beschreibung</label>
            <textarea id="description" name="description" class="form-control" rows="2"
                      placeholder="Optionale Beschreibung, die über dem Formular angezeigt wird"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>

        <div class="contact-form-intro-grid">
            <div class="form-group">
                <label class="form-label" for="recipient">Empfänger E-Mail</label>
                <input type="email" id="recipient" name="recipient" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['recipient'] ?? ''); ?>"
                       placeholder="Globale Einstellung wird verwendet">
                <small class="form-text">Leer = globaler Empfänger aus den Einstellungen</small>
            </div>
            <div class="form-group">
                <label class="form-label" for="success_message">Erfolgsmeldung</label>
                <input type="text" id="success_message" name="success_message" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['success_message'] ?? 'Vielen Dank für Ihre Nachricht!'); ?>">
            </div>
        </div>
    </div>

    <!-- Template-Auswahl -->
    <div class="admin-card">
        <h3>🎨 Template wählen</h3>
        <div class="contact-template-grid">
            <?php foreach ($templates as $key => $tpl): ?>
            <label class="contact-template-card">
                <input type="radio" name="template" value="<?php echo htmlspecialchars($key); ?>"
                       <?php echo ($key === ($_POST['template'] ?? 'classic')) ? 'checked' : ''; ?>
                       class="contact-template-card__input">
                <span class="contact-template-card__body">
                    <span class="contact-template-card__icon"><?php echo $tpl['icon']; ?></span>
                    <span class="contact-template-card__title"><?php echo htmlspecialchars($tpl['name']); ?></span>
                    <span class="contact-template-card__text"><?php echo htmlspecialchars($tpl['description']); ?></span>
                </span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="admin-card">
        <div class="contact-form-actions">
            <a href="?section=forms" class="btn btn-secondary">Abbrechen</a>
            <button type="submit" class="btn btn-primary">💾 Formular erstellen</button>
        </div>
    </div>
</form>
