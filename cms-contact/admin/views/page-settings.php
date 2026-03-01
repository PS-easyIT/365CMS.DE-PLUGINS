<?php declare(strict_types=1); if (!defined('ABSPATH')) exit;
$e = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>

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

<!-- Tabs -->
<div class="tabs" style="margin-bottom:0;display:flex;gap:.3rem;border-bottom:2px solid #e2e8f0;">
    <button class="tab-btn active" onclick="switchTab('tab-general', this)" type="button">📧 Allgemein</button>
    <button class="tab-btn" onclick="switchTab('tab-design', this)" type="button">🎨 Design</button>
    <button class="tab-btn" onclick="switchTab('tab-cleanup', this)" type="button">🧹 Wartung</button>
</div>

<form method="POST" class="admin-form">
    <input type="hidden" name="form_action" value="save_settings">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

    <!-- Tab: Allgemein -->
    <div id="tab-general" class="tab-content active">
        <div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;">
            <h3>📧 E-Mail-Einstellungen</h3>

            <div class="form-group">
                <label class="form-label" for="admin_email">Globaler Empfänger</label>
                <input type="email" id="admin_email" name="admin_email" class="form-control"
                       value="<?php echo $e($settings['admin_email'] ?? ''); ?>"
                       placeholder="admin@example.com" style="max-width:400px;">
                <small class="form-text">Wird verwendet, wenn ein Formular keinen eigenen Empfänger hat.</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="from_name">Absendername</label>
                <input type="text" id="from_name" name="from_name" class="form-control"
                       value="<?php echo $e($settings['from_name'] ?? ''); ?>"
                       placeholder="<?php echo $e(defined('SITE_NAME') ? SITE_NAME : 'CMS'); ?>"
                       style="max-width:350px;">
            </div>

            <div class="form-group">
                <label class="form-label" for="from_email">Absender-E-Mail</label>
                <input type="email" id="from_email" name="from_email" class="form-control"
                       value="<?php echo $e($settings['from_email'] ?? ''); ?>"
                       placeholder="noreply@example.com" style="max-width:400px;">
            </div>

            <div class="form-group">
                <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
                    <input type="checkbox" name="send_confirmation" value="1"
                           <?php echo !empty($settings['send_confirmation']) ? 'checked' : ''; ?>>
                    Bestätigungs-E-Mail an Absender senden
                </label>
            </div>
        </div>
    </div>

    <!-- Tab: Design -->
    <div id="tab-design" class="tab-content">
        <div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;">
            <h3>🎨 Standard-Design</h3>
            <p style="color:#64748b;font-size:.85rem;margin-bottom:1rem;">Diese Werte gelten als Fallback, wenn ein Formular keine eigenen Einstellungen hat.</p>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
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
                    <div style="display:flex;gap:.5rem;align-items:center;">
                        <input type="color" id="primary_color" name="primary_color"
                               value="<?php echo $e($settings['primary_color'] ?? '#3b82f6'); ?>"
                               style="width:44px;height:38px;border:2px solid #e2e8f0;border-radius:6px;cursor:pointer;">
                        <input type="text" class="form-control" style="max-width:110px;font-family:monospace;"
                               value="<?php echo $e($settings['primary_color'] ?? '#3b82f6'); ?>"
                               oninput="document.getElementById('primary_color').value=this.value"
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
        </div>
    </div>

    <!-- Speichern -->
    <div class="admin-card" style="margin-top:1rem;">
        <div style="display:flex;justify-content:flex-end;gap:.6rem;align-items:center;">
            <span style="color:#64748b;font-size:.85rem;">Änderungen werden sofort übernommen</span>
            <button type="submit" class="btn btn-primary">💾 Speichern</button>
        </div>
    </div>
</form>

<!-- Tab: Wartung (separate Aktionen, nicht im Hauptformular) -->
<div id="tab-cleanup" class="tab-content">
    <div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;">
        <h3>🧹 Wartung & Bereinigung</h3>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
            <div style="background:#f8fafc;padding:1.25rem;border-radius:8px;border:1px solid #e2e8f0;">
                <h4 style="margin:0 0 .5rem;">📭 Alte Nachrichten löschen</h4>
                <p style="font-size:.85rem;color:#64748b;">Entfernt alle Nachrichten, die älter als der gewählte Zeitraum sind.</p>
                <form method="POST" style="display:flex;gap:.5rem;align-items:flex-end;margin-top:.75rem;">
                    <input type="hidden" name="form_action" value="cleanup_submissions">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <select name="older_than_days" class="form-control" style="max-width:180px;">
                        <option value="30">Älter als 30 Tage</option>
                        <option value="90" selected>Älter als 90 Tage</option>
                        <option value="180">Älter als 180 Tage</option>
                        <option value="365">Älter als 1 Jahr</option>
                    </select>
                    <button type="submit" class="btn btn-danger btn-sm">🗑️ Bereinigen</button>
                </form>
            </div>

            <div style="background:#f8fafc;padding:1.25rem;border-radius:8px;border:1px solid #e2e8f0;">
                <h4 style="margin:0 0 .5rem;">🚫 Spam löschen</h4>
                <p style="font-size:.85rem;color:#64748b;">Entfernt alle als Spam markierten Nachrichten.</p>
                <form method="POST" style="margin-top:.75rem;">
                    <input type="hidden" name="form_action" value="cleanup_spam">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <button type="submit" class="btn btn-danger btn-sm">🚫 Spam leeren</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId)?.classList.add('active');
    btn.classList.add('active');
}
</script>
