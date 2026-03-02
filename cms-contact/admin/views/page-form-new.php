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

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
            <div class="form-group">
                <label class="form-label" for="title">Titel <span style="color:#ef4444;">*</span></label>
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

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
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
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
            <?php foreach ($templates as $key => $tpl): ?>
            <label class="contact-template-card" style="cursor:pointer;display:block;border:2px solid #e2e8f0;border-radius:10px;padding:1.25rem;text-align:center;transition:all .2s;">
                <input type="radio" name="template" value="<?php echo htmlspecialchars($key); ?>"
                       <?php echo ($key === ($_POST['template'] ?? 'classic')) ? 'checked' : ''; ?>
                       style="display:none;"
                       onchange="document.querySelectorAll('.contact-template-card').forEach(c=>c.style.borderColor='#e2e8f0');this.closest('label').style.borderColor='#3b82f6';">
                <div style="font-size:2rem;margin-bottom:.5rem;"><?php echo $tpl['icon']; ?></div>
                <div style="font-weight:600;font-size:.95rem;color:#1e293b;"><?php echo htmlspecialchars($tpl['name']); ?></div>
                <div style="font-size:.8rem;color:#64748b;margin-top:.25rem;"><?php echo htmlspecialchars($tpl['description']); ?></div>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="admin-card">
        <div style="display:flex;justify-content:flex-end;gap:.6rem;">
            <a href="?section=forms" class="btn btn-secondary">Abbrechen</a>
            <button type="submit" class="btn btn-primary">💾 Formular erstellen</button>
        </div>
    </div>
</form>

<script>
// Template-Auswahl visuell markieren
document.addEventListener('DOMContentLoaded', function() {
    const checked = document.querySelector('.contact-template-card input:checked');
    if (checked) checked.closest('label').style.borderColor = '#3b82f6';
});
</script>
