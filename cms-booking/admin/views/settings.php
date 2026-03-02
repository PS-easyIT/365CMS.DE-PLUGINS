<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<!-- Section-Nav -->
<nav class="lp-section-nav">
    <a href="/admin/plugins/booking-dashboard/booking-dashboard" class="lp-section-nav__item">
        <span class="lp-section-nav__icon">📊</span> Dashboard
    </a>
    <a href="/admin/plugins/booking-dashboard/booking-bookings" class="lp-section-nav__item">
        <span class="lp-section-nav__icon">📋</span> Buchungen
    </a>
    <a href="/admin/plugins/booking-dashboard/booking-providers" class="lp-section-nav__item">
        <span class="lp-section-nav__icon">👥</span> Anbieter
    </a>
    <a href="/admin/plugins/booking-dashboard/booking-services" class="lp-section-nav__item">
        <span class="lp-section-nav__icon">🛠️</span> Leistungen
    </a>
    <a href="/admin/plugins/booking-dashboard/booking-settings" class="lp-section-nav__item active">
        <span class="lp-section-nav__icon">⚙️</span> Einstellungen
    </a>
</nav>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>⚙️ Buchungssystem – Einstellungen</h2>
        <p>Globale Konfiguration für das Buchungssystem</p>
    </div>
</div>

<!-- Alerts -->
<?php if (!empty($success)): ?>
<div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php
$s = function (string $key, string $default = '') use ($settings): string {
    return htmlspecialchars($settings[$key] ?? $default);
};
?>

<form method="POST" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="settings_action" value="save">

    <!-- E-Mail -->
    <div class="admin-card">
        <h3>📧 E-Mail-Einstellungen</h3>

        <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
            <div class="form-group">
                <label class="form-label">Admin-E-Mail</label>
                <input type="email" name="admin_email" class="form-control"
                       value="<?php echo $s('admin_email'); ?>"
                       placeholder="admin@example.com">
                <small class="form-text">Empfänger für Admin-Benachrichtigungen</small>
            </div>
            <div class="form-group">
                <label class="form-label">Absendername</label>
                <input type="text" name="from_name" class="form-control"
                       value="<?php echo $s('from_name', 'Buchungssystem'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Absender-E-Mail</label>
                <input type="email" name="from_email" class="form-control"
                       value="<?php echo $s('from_email'); ?>"
                       placeholder="noreply@example.com">
            </div>
        </div>
    </div>

    <!-- Standards -->
    <div class="admin-card">
        <h3>📐 Standard-Werte</h3>

        <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:1.25rem;">
            <div class="form-group">
                <label class="form-label">Standard-Dauer (Min.)</label>
                <input type="number" name="default_duration" class="form-control" min="5" step="5"
                       value="<?php echo $s('default_duration', '60'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Standard-Puffer (Min.)</label>
                <input type="number" name="default_buffer" class="form-control" min="0" step="5"
                       value="<?php echo $s('default_buffer', '15'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Zeitzone</label>
                <select name="default_timezone" class="form-control">
                    <?php
                    $tzOptions = [
                        'Europe/Berlin'  => 'Europa/Berlin (MEZ)',
                        'Europe/Zurich'  => 'Europa/Zürich',
                        'Europe/Vienna'  => 'Europa/Wien',
                        'Europe/London'  => 'Europa/London (GMT)',
                        'UTC'            => 'UTC',
                    ];
                    $currentTz = $settings['default_timezone'] ?? 'Europe/Berlin';
                    foreach ($tzOptions as $tz => $label): ?>
                        <option value="<?php echo $tz; ?>" <?php echo $currentTz === $tz ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Währung</label>
                <select name="default_currency" class="form-control">
                    <?php
                    $currencies = ['EUR' => '€ Euro', 'CHF' => 'CHF Schweizer Franken', 'USD' => '$ US-Dollar', 'GBP' => '£ Britisches Pfund'];
                    $currentCur = $settings['default_currency'] ?? 'EUR';
                    foreach ($currencies as $code => $label): ?>
                        <option value="<?php echo $code; ?>" <?php echo $currentCur === $code ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Buchungsregeln -->
    <div class="admin-card">
        <h3>📏 Buchungsregeln</h3>

        <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
            <div class="form-group">
                <label class="form-label">Min. Vorlaufzeit (Tage)</label>
                <input type="number" name="booking_advance_min" class="form-control" min="0"
                       value="<?php echo $s('booking_advance_min', '1'); ?>">
                <small class="form-text">Wie viele Tage im Voraus mind. gebucht werden muss</small>
            </div>
            <div class="form-group">
                <label class="form-label">Max. Vorlaufzeit (Tage)</label>
                <input type="number" name="booking_advance_max" class="form-control" min="1"
                       value="<?php echo $s('booking_advance_max', '90'); ?>">
                <small class="form-text">Buchungshorizont in die Zukunft</small>
            </div>
            <div class="form-group">
                <label class="form-label">Stornierungsfrist (Std.)</label>
                <input type="number" name="cancellation_hours" class="form-control" min="0"
                       value="<?php echo $s('cancellation_hours', '24'); ?>">
                <small class="form-text">Stunden vor Termin, bis wann storniert werden kann</small>
            </div>
        </div>

        <div class="form-group" style="margin-top:1rem;">
            <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
                <input type="checkbox" name="auto_confirm" value="1"
                       <?php echo !empty($settings['auto_confirm']) && $settings['auto_confirm'] !== '0' ? 'checked' : ''; ?>>
                <span>Automatische Bestätigung</span>
            </label>
            <small class="form-text" style="margin-left:1.5rem;">Buchungen werden sofort bestätigt, ohne manuelle Freigabe</small>
        </div>
    </div>

    <!-- Benachrichtigungen -->
    <div class="admin-card">
        <h3>🔔 Benachrichtigungen</h3>

        <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
            <div class="form-group">
                <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
                    <input type="checkbox" name="send_reminders" value="1"
                           <?php echo !empty($settings['send_reminders']) && $settings['send_reminders'] !== '0' ? 'checked' : ''; ?>>
                    <span>Erinnerungen versenden</span>
                </label>
                <small class="form-text" style="margin-left:1.5rem;">Automatische Erinnerung an Kunden vor dem Termin</small>
            </div>
            <div class="form-group">
                <label class="form-label">Erinnerung (Stunden vorher)</label>
                <input type="number" name="reminder_hours" class="form-control" min="1"
                       value="<?php echo $s('reminder_hours', '24'); ?>">
            </div>
        </div>
    </div>

    <!-- Darstellung -->
    <div class="admin-card">
        <h3>🎨 Darstellung</h3>

        <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
            <div class="form-group">
                <label class="form-label">Primärfarbe</label>
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <input type="color" name="primary_color"
                           value="<?php echo $s('primary_color', '#3b82f6'); ?>"
                           style="width:48px;height:36px;border:none;cursor:pointer;">
                    <input type="text" class="form-control" style="max-width:140px;" readonly
                           value="<?php echo $s('primary_color', '#3b82f6'); ?>"
                           id="colorPreview">
                </div>
                <small class="form-text">Wird für Buttons und Akzente auf den Buchungsseiten verwendet</small>
            </div>
        </div>
    </div>

    <!-- Save -->
    <div class="admin-card form-actions-card">
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
            <span class="form-actions__hint">Änderungen werden sofort wirksam</span>
        </div>
    </div>
</form>

<script>
document.querySelector('input[name="primary_color"]').addEventListener('input', function() {
    document.getElementById('colorPreview').value = this.value;
});
</script>
