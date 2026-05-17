<?php declare(strict_types=1); if (!defined('ABSPATH')) exit;
$e = fn(?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
?>

<?php include CMS_CONTACT_PLUGIN_DIR . 'admin/views/partial-section-nav.php'; ?>

<div class="contact-admin-shell">

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>⚙️ Kontakt-Einstellungen</h2>
        <p>Globale Konfiguration für das Kontaktformular-Plugin</p>
    </div>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo $e($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo $e($error); ?></div>
<?php endif; ?>

<div class="contact-card-grid">
    <div class="contact-mini-card">
        <span class="contact-mini-card__label">Mail-Versand</span>
        <span class="contact-mini-card__value"><?php echo !empty($settings['admin_email']) ? 'Aktiv' : 'Offen'; ?></span>
        <span class="contact-mini-card__text"><?php echo !empty($settings['admin_email']) ? $e($settings['admin_email']) : 'Globaler Empfänger noch nicht gesetzt.'; ?></span>
    </div>
    <div class="contact-mini-card">
        <span class="contact-mini-card__label">Template-Standard</span>
        <span class="contact-mini-card__value"><?php echo $e($templates[$settings['default_template'] ?? 'classic']['icon'] ?? '🧩'); ?></span>
        <span class="contact-mini-card__text"><?php echo $e($templates[$settings['default_template'] ?? 'classic']['name'] ?? 'Classic'); ?></span>
    </div>
    <div class="contact-mini-card">
        <span class="contact-mini-card__label">Bestätigungs-Mail</span>
        <span class="contact-mini-card__value"><?php echo !empty($settings['send_confirmation']) ? 'Ein' : 'Aus'; ?></span>
        <span class="contact-mini-card__text">Automatische Rückmeldung an Einsender.</span>
    </div>
    <div class="contact-mini-card">
        <span class="contact-mini-card__label">Datenschutz</span>
        <span class="contact-mini-card__value"><?php echo !empty($settings['require_privacy_consent']) ? 'Aktiv' : 'Aus'; ?></span>
        <span class="contact-mini-card__text"><?php echo $e($settings['privacy_policy_url'] ?? '/datenschutz'); ?></span>
    </div>
</div>

<!-- Tabs -->
<div class="contact-tab-bar" data-tab-scope data-tab-content-selector=".tab-content" data-tab-button-selector="[data-contact-tab-target]">
    <button class="contact-tab-btn<?php echo $tab === 'general' ? ' active' : ''; ?>" data-contact-tab-target="tab-general" type="button">📧 Allgemein</button>
    <button class="contact-tab-btn<?php echo $tab === 'design' ? ' active' : ''; ?>" data-contact-tab-target="tab-design" type="button">🎨 Design</button>
    <button class="contact-tab-btn<?php echo $tab === 'cleanup' ? ' active' : ''; ?>" data-contact-tab-target="tab-cleanup" type="button">🧹 Wartung</button>
</div>

<form method="POST" class="admin-form">
    <input type="hidden" name="settings_action" value="save_settings">
    <input type="hidden" name="csrf_token" value="<?php echo $e($csrfToken); ?>">

    <!-- Tab: Allgemein -->
    <div id="tab-general" class="tab-content<?php echo $tab === 'general' ? ' active' : ''; ?>">
        <div class="admin-card contact-tab-panel">
            <div class="contact-panel-header">
                <div>
                    <h3>📧 E-Mail-Einstellungen</h3>
                    <p>Globale Versanddaten als Fallback für alle Formulare definieren.</p>
                </div>
            </div>

            <div class="contact-settings-grid">
                <div class="form-group">
                    <label class="form-label" for="admin_email">Globaler Empfänger</label>
                    <input type="email" id="admin_email" name="admin_email" class="form-control"
                           value="<?php echo $e($settings['admin_email'] ?? ''); ?>"
                           placeholder="admin@example.com">
                    <small class="form-text">Wird verwendet, wenn ein Formular keinen eigenen Empfänger hat.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="from_name">Absendername</label>
                    <input type="text" id="from_name" name="from_name" class="form-control"
                           value="<?php echo $e($settings['from_name'] ?? ''); ?>"
                           placeholder="<?php echo $e(defined('SITE_NAME') ? SITE_NAME : 'CMS'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="from_email">Absender-E-Mail</label>
                    <input type="email" id="from_email" name="from_email" class="form-control"
                           value="<?php echo $e($settings['from_email'] ?? ''); ?>"
                           placeholder="noreply@example.com">
                </div>

                <div class="contact-info-card">
                    <span class="contact-info-card__eyebrow">Hinweis</span>
                    <strong class="contact-action-card__title">Fallback-Logik</strong>
                    <div class="contact-info-card__text">Wenn ein Formular keinen eigenen Empfänger hat, greift automatisch dieser globale Versand-Stack.</div>
                </div>
            </div>

            <div class="form-group">
                  <label class="checkbox-label contact-checkbox-inline">
                    <input type="checkbox" name="send_confirmation" value="1"
                           <?php echo !empty($settings['send_confirmation']) ? 'checked' : ''; ?>>
                    Bestätigungs-E-Mail an Absender senden
                </label>
            </div>

            <hr class="contact-section-divider">

            <div class="contact-panel-header">
                <div>
                    <h3>🛡️ Datenschutz</h3>
                    <p>Einwilligung und Verlinkung zur Datenschutzerklärung für alle Kontaktformulare.</p>
                </div>
            </div>

            <div class="contact-settings-grid">
                <div class="form-group">
                    <label class="form-label" for="privacy_policy_url">Datenschutz-URL</label>
                    <input type="text" id="privacy_policy_url" name="privacy_policy_url" class="form-control"
                           value="<?php echo $e($settings['privacy_policy_url'] ?? '/datenschutz'); ?>"
                           placeholder="/datenschutz oder https://example.com/datenschutz">
                    <small class="form-text">Interne oder absolute URL zur Datenschutzerklärung.</small>
                </div>

                <div class="contact-info-card">
                    <span class="contact-info-card__eyebrow">DSGVO-Hinweis</span>
                    <strong class="contact-action-card__title">Pflichtbestätigung im Formular</strong>
                    <div class="contact-info-card__text">Wenn aktiviert, kann das Formular nur mit gesetztem Haken und sichtbarem Datenschutzhinweis abgesendet werden.</div>
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label contact-checkbox-inline">
                    <input type="checkbox" name="require_privacy_consent" value="1"
                           <?php echo !empty($settings['require_privacy_consent']) ? 'checked' : ''; ?>>
                    Datenschutz-Einwilligung im Formular verpflichtend anzeigen
                </label>
            </div>
        </div>
    </div>

    <!-- Tab: Design -->
    <div id="tab-design" class="tab-content<?php echo $tab === 'design' ? ' active' : ''; ?>">
        <div class="admin-card contact-tab-panel">
            <h3>🎨 Standard-Design</h3>
            <p class="contact-muted-text contact-note-spacing">Diese Werte gelten als Fallback, wenn ein Formular keine eigenen Einstellungen hat.</p>

            <div class="contact-form-grid-3">
                <div class="form-group">
                    <label class="form-label" for="default_template">Standard-Template</label>
                    <select id="default_template" name="default_template" class="form-control">
                        <?php foreach ($templates as $key => $tpl): ?>
                        <option value="<?php echo $e($key); ?>"
                                <?php echo ($settings['default_template'] ?? 'classic') === $key ? 'selected' : ''; ?>>
                            <?php echo $tpl['icon'] . ' ' . $e($tpl['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="primary_color">Primärfarbe</label>
                      <div class="contact-inline-actions">
                        <input type="color" id="primary_color" name="primary_color"
                               value="<?php echo $e($settings['primary_color'] ?? '#3b82f6'); ?>"
                           class="contact-input-color">
                       <input type="text" class="form-control contact-input-color-text"
                               value="<?php echo $e($settings['primary_color'] ?? '#3b82f6'); ?>"
                               pattern="^#[0-9a-fA-F]{6}$">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="border_radius">Eckenradius</label>
                    <select id="border_radius" name="border_radius" class="form-control">
                        <option value="0" <?php echo ($settings['border_radius'] ?? '8') === '0' ? 'selected' : ''; ?>>Eckig (0px)</option>
                        <option value="4" <?php echo ($settings['border_radius'] ?? '8') === '4' ? 'selected' : ''; ?>>Leicht (4px)</option>
                        <option value="8" <?php echo ($settings['border_radius'] ?? '8') === '8' ? 'selected' : ''; ?>>Standard (8px)</option>
                        <option value="12" <?php echo ($settings['border_radius'] ?? '8') === '12' ? 'selected' : ''; ?>>Rund (12px)</option>
                    </select>
                </div>
            </div>

            <div class="contact-template-note contact-note-spacing">
                <span class="contact-template-note__eyebrow">Design-Tipp</span>
                <strong class="contact-action-card__title">Konsequente Defaults sparen Zeit</strong>
                <div class="contact-template-note__text">Wenn Farbe, Radius und Template hier sauber gesetzt sind, benötigen neue Formulare oft nur noch Titel, Slug und Empfänger.</div>
            </div>
        </div>
    </div>

    <!-- Speichern -->
    <div class="admin-card">
        <div class="contact-panel-header">
            <div>
                <h3>💾 Änderungen übernehmen</h3>
                <p>Globale Konfiguration wird direkt für neue Formulare und als Fallback für bestehende Formulare verwendet.</p>
            </div>
            <div class="contact-inline-actions">
            <span class="contact-muted-text">Änderungen werden sofort übernommen</span>
            <button type="submit" class="btn btn-primary">💾 Speichern</button>
            </div>
        </div>
    </div>
</form>

<!-- Tab: Wartung (separate Aktionen, nicht im Hauptformular) -->
<div id="tab-cleanup" class="tab-content<?php echo $tab === 'cleanup' ? ' active' : ''; ?>">
    <div class="admin-card contact-tab-panel">
        <h3>🧹 Wartung & Bereinigung</h3>

        <div class="contact-settings-grid">
            <div class="contact-info-card">
                <h4 class="contact-info-title">📭 Alte Nachrichten löschen</h4>
                <p class="contact-info-copy">Entfernt alle Nachrichten, die älter als der gewählte Zeitraum sind.</p>
                <form method="POST" class="contact-maintenance-form">
                    <input type="hidden" name="settings_action" value="cleanup_submissions">
                    <input type="hidden" name="csrf_token" value="<?php echo $e($csrfToken); ?>">
                    <select name="older_than_days" class="form-control contact-maintenance-select">
                        <option value="30">Älter als 30 Tage</option>
                        <option value="90" selected>Älter als 90 Tage</option>
                        <option value="180">Älter als 180 Tage</option>
                        <option value="365">Älter als 1 Jahr</option>
                    </select>
                    <button type="submit" class="btn btn-danger btn-sm">🗑️ Bereinigen</button>
                </form>
            </div>

            <div class="contact-info-card">
                <h4 class="contact-info-title">🚫 Spam löschen</h4>
                <p class="contact-info-copy">Entfernt alle als Spam markierten Nachrichten.</p>
                <form method="POST" class="contact-note-spacing">
                    <input type="hidden" name="settings_action" value="cleanup_spam">
                    <input type="hidden" name="csrf_token" value="<?php echo $e($csrfToken); ?>">
                    <button type="submit" class="btn btn-danger btn-sm">🚫 Spam leeren</button>
                </form>
            </div>
        </div>
    </div>
</div>

</div>

