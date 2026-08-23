<?php declare(strict_types=1); if (!defined('ABSPATH')) exit;
$e = fn($v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
?>

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
<div class="alert alert-error">❌ <?php echo $e($error); ?></div>
<?php endif; ?>

<form method="POST" class="admin-form">
    <input type="hidden" name="form_action" value="create_form">
    <input type="hidden" name="csrf_token" value="<?php echo $e($csrfToken); ?>">

    <div class="admin-card">
        <h3>📋 Grundeinstellungen</h3>

        <div class="contact-form-intro-grid">
            <div class="form-group">
                <label class="form-label" for="title">Titel <span class="contact-required">*</span></label>
                <input type="text" id="title" name="title" class="form-control"
                       value="<?php echo $e($_POST['title'] ?? ''); ?>"
                       placeholder="z. B. Kontakt, Anfrage, Bewerbung" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="slug">URL-Slug</label>
                <input type="text" id="slug" name="slug" class="form-control"
                       value="<?php echo $e($_POST['slug'] ?? ''); ?>"
                       placeholder="Wird automatisch generiert"
                       pattern="[a-z0-9\-]+" title="Nur Kleinbuchstaben, Zahlen und Bindestriche">
                <small class="form-text">Erreichbar unter /contact/{slug}</small>
            </div>
        </div>

        <div class="form-group">
            <span class="form-label">Beschreibung</span>
            <small class="form-text contact-editor-help">Formatierte Inhalte werden oberhalb des Formulars angezeigt.</small>
            <?php echo self::render_form_content_editor('description', $_POST['description'] ?? '', 'Formularbeschreibung'); ?>
        </div>

        <div class="contact-form-intro-grid">
            <div class="form-group">
                <label class="form-label" for="recipient">Empfänger E-Mail</label>
                <input type="email" id="recipient" name="recipient" class="form-control"
                       value="<?php echo $e($_POST['recipient'] ?? ''); ?>"
                       placeholder="Globale Einstellung wird verwendet">
                <small class="form-text">Leer = globaler Empfänger aus den Einstellungen</small>
            </div>
            <div class="form-group">
                <label class="form-label" for="success_message">Erfolgsmeldung</label>
                <input type="text" id="success_message" name="success_message" class="form-control"
                       value="<?php echo $e($_POST['success_message'] ?? 'Vielen Dank für Ihre Nachricht!'); ?>">
            </div>
        </div>

        <div class="form-group contact-footer-editor">
            <span class="form-label">Kontaktformular Footer-Beschreibung</span>
            <small class="form-text contact-editor-help">Formatierte Inhalte werden unter den Formularfeldern und dem Absende-Button angezeigt.</small>
            <?php echo self::render_form_content_editor('footer_description', $_POST['footer_description'] ?? '', 'Kontaktformular Footer-Beschreibung', 220); ?>
        </div>
    </div>

    <!-- Template-Auswahl -->
    <div class="admin-card">
        <h3>🎨 Template wählen</h3>
        <div class="contact-template-grid">
            <?php foreach ($templates as $key => $tpl): ?>
            <label class="contact-template-card">
                <input type="radio" name="template" value="<?php echo $e($key); ?>"
                       <?php echo ($key === ($_POST['template'] ?? 'classic')) ? 'checked' : ''; ?>
                       class="contact-template-card__input">
                <span class="contact-template-card__body">
                    <span class="contact-template-card__icon"><?php echo $tpl['icon']; ?></span>
                    <span class="contact-template-card__title"><?php echo $e($tpl['name']); ?></span>
                    <span class="contact-template-card__text"><?php echo $e($tpl['description']); ?></span>
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
