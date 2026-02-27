---
applyTo: "*/admin/**/*.php,*/admin/views/**/*.php"
---

# 365CMS Plugin – Admin Einstellungsseiten

> Gilt für alle Plugin-Einstellungs-, Konfiguration- und Design-Seiten im Admin-Bereich.
> Ergänzt `admin-default.instructions.md` und `admin-design-layout.instructions.md`.

---

## 1. Einstellungsseiten-Grundstruktur

Einstellungsseiten bestehen immer aus:
1. Tab-Navigation (URL-basiert, `[prefix]-tabs`)
2. Einem `admin-card` pro Tab (direkt anschließend, oben-links kein Radius)
3. Einem Formular mit CSRF-Token
4. Einem Speichern-Button am Ende der `admin-card`

```php
<?php
// Daten im Trait/Controller laden, dann View einbinden:
$tab  = $_GET['tab'] ?? 'general';
$tabs = [
    'general'     => '⚙️ Allgemein',
    'design'      => '🎨 Design',
    'permissions' => '🔐 Berechtigungen',
];
// Notice/Error aus POST-Handler
?>

<div class="admin-page-header">
    <div>
        <h2>⚙️ Einstellungen</h2>
        <p>Plugin-Konfiguration und Berechtigungen</p>
    </div>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo htmlspecialchars($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Tab-Navigation -->
<div class="[prefix]-tabs">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="?tab=<?php echo esc_attr($key); ?>"
       class="[prefix]-tab<?php echo $tab === $key ? ' active' : ''; ?>">
        <?php echo esc_html($label); ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Content-Card (Radius oben-links = 0, da Tab direkt darüber) -->
<div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;max-width:700px;">

    <?php if ($tab === 'general'): ?>
        <!-- Tab-Inhalt (Formular) -->
    <?php elseif ($tab === 'design'): ?>
        <!-- Tab-Inhalt -->
    <?php endif; ?>

</div>
```

---

## 2. Formular-Muster (Einstellungsseite)

```php
<h3>⚙️ Allgemeine Einstellungen</h3>

<form method="POST" class="admin-form">
    <input type="hidden" name="action"     value="save_settings">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

    <div class="form-group">
        <label class="form-label" for="setting_title">
            Seitentitel <span style="color:#ef4444;">*</span>
        </label>
        <input type="text" id="setting_title" name="setting_title"
               class="form-control"
               value="<?php echo htmlspecialchars($settings['title'] ?? ''); ?>"
               maxlength="100" required>
        <small class="form-text">Wird als Überschrift der öffentlichen Archiv-Seite angezeigt.</small>
    </div>

    <div class="form-group">
        <label class="form-label" for="setting_desc">Beschreibung</label>
        <textarea id="setting_desc" name="setting_description"
                  class="form-control" rows="3"><?php
            echo htmlspecialchars($settings['description'] ?? '');
        ?></textarea>
        <small class="form-text">Optionale Unterzeile unter dem Titel.</small>
    </div>

    <div class="form-group">
        <label class="form-label">Einträge pro Seite</label>
        <input type="number" name="items_per_page" class="form-control"
               value="<?php echo (int)($settings['per_page'] ?? 12); ?>"
               min="4" max="100" step="4" style="max-width:120px;">
    </div>

    <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
</form>
```

---

## 3. Design-/Farb-Einstellungen (Farbwähler + Vorschau)

```php
<h3>🎨 Design-Einstellungen</h3>

<form method="POST" class="admin-form">
    <input type="hidden" name="action"     value="save_design">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

    <!-- Zweispaltiges Grid für Farbpaare -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">

        <div class="form-group">
            <label class="form-label">Primärfarbe</label>
            <div style="display:flex;gap:.625rem;align-items:center;">
                <input type="color" name="design_primary_color"
                       class="form-control" style="width:56px;height:40px;padding:.125rem;"
                       value="<?php echo htmlspecialchars($s['design_primary_color'] ?? '#0891b2'); ?>">
                <input type="text"  name="design_primary_color_text"
                       class="form-control"
                       value="<?php echo htmlspecialchars($s['design_primary_color'] ?? '#0891b2'); ?>"
                       pattern="^#[0-9A-Fa-f]{6}$" maxlength="7"
                       placeholder="#0891b2"
                       onchange="this.previousElementSibling.value=this.value"
                       style="max-width:110px;font-family:monospace;">
            </div>
            <small class="form-text">Buttons, Links, aktive Elemente</small>
        </div>

        <div class="form-group">
            <label class="form-label">Akzentfarbe (hell)</label>
            <div style="display:flex;gap:.625rem;align-items:center;">
                <input type="color" name="design_accent_color"
                       class="form-control" style="width:56px;height:40px;padding:.125rem;"
                       value="<?php echo htmlspecialchars($s['design_accent_color'] ?? '#e0f2fe'); ?>">
                <input type="text"  name="design_accent_color_text"
                       class="form-control"
                       value="<?php echo htmlspecialchars($s['design_accent_color'] ?? '#e0f2fe'); ?>"
                       pattern="^#[0-9A-Fa-f]{6}$" maxlength="7"
                       style="max-width:110px;font-family:monospace;"
                       onchange="this.previousElementSibling.value=this.value">
            </div>
            <small class="form-text">Hover-Hintergründe, Badge-Tints</small>
        </div>

    </div>

    <!-- Border-Radius -->
    <div class="form-group" style="max-width:340px;">
        <label class="form-label">Border-Radius (Karten)</label>
        <div style="display:flex;align-items:center;gap:.75rem;">
            <input type="range" name="design_border_radius"
                   min="0" max="24" step="2"
                   value="<?php echo (int)($s['design_border_radius'] ?? 12); ?>"
                   oninput="document.getElementById('radiusPreview').textContent=this.value+'px'"
                   style="flex:1;">
            <span id="radiusPreview" style="font-weight:700;min-width:36px;text-align:right;color:#3b82f6;">
                <?php echo (int)($s['design_border_radius'] ?? 12); ?>px
            </span>
        </div>
    </div>

    <!-- Grid-Spalten -->
    <div class="form-group">
        <label class="form-label">Spalten (Archiv-Grid)</label>
        <select name="design_grid_columns" class="form-control" style="max-width:220px;">
            <?php foreach (['auto'=>'Automatisch (empfohlen)','2'=>'2 Spalten','3'=>'3 Spalten','4'=>'4 Spalten'] as $val => $lbl): ?>
            <option value="<?php echo $val; ?>"
                    <?php echo ($s['design_grid_columns'] ?? 'auto') === $val ? 'selected' : ''; ?>>
                <?php echo $lbl; ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">💾 Design speichern</button>
</form>
```

---

## 4. Toggle / Checkbox-Einstellungen

```php
<!-- Feature-Toggles als echte <input type="checkbox"> mit .checkbox-label -->
<div class="form-group">
    <label class="form-label">Angezeigte Felder</label>

    <?php $features = [
        'design_show_industry'  => 'Branche anzeigen',
        'design_show_city'      => 'Stadtname anzeigen',
        'design_show_employees' => 'Mitarbeiterzahl anzeigen',
        'design_show_website'   => 'Website-Link anzeigen',
    ]; ?>

    <?php foreach ($features as $key => $label): ?>
    <label class="checkbox-label">
        <input type="checkbox" name="<?php echo $key; ?>" value="1"
               <?php echo !empty($s[$key]) ? 'checked' : ''; ?>>
        <?php echo $label; ?>
    </label>
    <?php endforeach; ?>

    <small class="form-text">Bestimmt welche Felder auf den öffentlichen Karten sichtbar sind.</small>
</div>
```

---

## 5. POST-Handler (im Trait/Controller, VOR dem View)

```php
// Im Trait: render_settings_page() oben, vor include der View
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'plugin_settings')) {
        $error = 'Sicherheitscheck fehlgeschlagen.';
    } else {

        switch ($_POST['action']) {

            case 'save_settings':
                $db->update_settings([
                    'archive_title'       => sanitize_text_field($_POST['setting_title']       ?? ''),
                    'archive_description' => sanitize_text_field($_POST['setting_description'] ?? ''),
                    'archive_per_page'    => max(4, min(100, (int)($_POST['items_per_page']    ?? 12))),
                ]);
                $notice = 'Einstellungen gespeichert.';
                break;

            case 'save_design':
                $primary = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['design_primary_color'] ?? '')
                    ? $_POST['design_primary_color']
                    : '#0891b2';
                $accent  = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['design_accent_color']  ?? '')
                    ? $_POST['design_accent_color']
                    : '#e0f2fe';
                $db->update_settings([
                    'design_primary_color'  => $primary,
                    'design_accent_color'   => $accent,
                    'design_border_radius'  => max(0, min(24, (int)($_POST['design_border_radius'] ?? 12))),
                    'design_grid_columns'   => in_array($_POST['design_grid_columns'] ?? '', ['auto','2','3','4'], true)
                                              ? $_POST['design_grid_columns']
                                              : 'auto',
                    'design_show_industry'  => !empty($_POST['design_show_industry'])  ? '1' : '0',
                    'design_show_city'      => !empty($_POST['design_show_city'])       ? '1' : '0',
                ]);
                $notice = 'Design gespeichert.';
                break;
        }
    }
}
// fehlt: $settings = $db->get_settings(); neu laden nach Save
```

---

## 6. Einstellungs-Gruppen mit Info-Box

```php
<!-- Info-Box / Hinweis innerhalb eines Einstellungs-Abschnitts -->
<div class="alert" style="background:#dbeafe;color:#1e40af;border-left:4px solid #3b82f6;margin-bottom:1.25rem;">
    ℹ️ Diese Einstellungen betreffen nur die öffentliche Ansicht. Design-Änderungen werden sofort aktiv.
</div>

<!-- Trennlinie zwischen Abschnitten innerhalb eines Tabs -->
<hr style="border:none;border-top:1px solid #f1f5f9;margin:1.5rem 0;">
<h4 style="font-size:.95rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">
    🔒 Erweiterte Einstellungen
</h4>
```

---

## 7. System-Info-Tab

```php
<?php if ($tab === 'system'): ?>
<h3>🖥️ System-Informationen</h3>
<div class="info-grid">
    <div class="info-card">
        <h4>Datenbank</h4>
        <ul class="info-list">
            <?php foreach ($db->get_table_names() as $table): ?>
            <li><?php echo htmlspecialchars($table); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div style="margin-top:1.25rem;">
    <form method="POST" style="display:inline-block;margin-right:.5rem;">
        <input type="hidden" name="action"     value="flush_cache">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        <button type="submit" class="btn btn-secondary">🔄 Cache leeren</button>
    </form>
</div>
<?php endif; ?>
```

---

## 8. Eingabe-Validierung (Einstellungswerte)

```php
// Farbwert
$color = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['color'] ?? '') ? $_POST['color'] : '#000000';

// Integer mit Min/Max-Clamp
$perPage = max(4, min(100, (int)($_POST['per_page'] ?? 12)));

// Enum-Wert (Whitelist)
$allowed = ['auto', '2', '3', '4'];
$cols    = in_array($_POST['grid_cols'] ?? '', $allowed, true) ? $_POST['grid_cols'] : 'auto';

// URL
$url = filter_var($_POST['url'] ?? '', FILTER_VALIDATE_URL) ?: '';

// Boolean-Checkbox
$showCity = !empty($_POST['show_city']) ? '1' : '0';

// Freitext (kurze Strings)
$title = sanitize_text_field($_POST['title'] ?? '');

// Freitext (längere Beschreibung, kein HTML)
$desc = strip_tags(trim($_POST['description'] ?? ''));
```

---

## 9. Berechtigungs-Tab (Rollen-Dropdowns)

```php
<?php if ($tab === 'permissions'): ?>
<h3>🔐 Berechtigungen</h3>

<div class="alert" style="background:#dbeafe;color:#1e40af;border-left:4px solid #3b82f6;margin-bottom:1.25rem;">
    ℹ️ Lege fest, welche CMS-Rollen welche Aktionen ausführen dürfen.
</div>

<form method="POST" class="admin-form">
    <input type="hidden" name="action"     value="save_permissions">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

    <?php
    $permDefs = [
        'role_create' => 'Einträge erstellen (Member-Bereich)',
        'role_edit'   => 'Eigene Einträge bearbeiten',
        'role_delete' => 'Einträge löschen',
        'role_publish'=> 'Direkt veröffentlichen (ohne Freigabe)',
    ];
    foreach ($permDefs as $key => $label):
    ?>
    <div class="form-group">
        <label class="form-label"><?php echo $label; ?></label>
        <select name="<?php echo $key; ?>" class="form-control" style="max-width:280px;">
            <?php foreach ($roleOptions as $val => $roleName): ?>
            <option value="<?php echo htmlspecialchars($val, ENT_QUOTES); ?>"
                    <?php echo ($settings[$key] ?? 'admin') === $val ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($roleName, ENT_QUOTES); ?>
                (<?php echo htmlspecialchars($val, ENT_QUOTES); ?>)
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary">💾 Berechtigungen speichern</button>
</form>
<?php endif; ?>
```

---

## 10. Checkliste Einstellungsseiten

- [ ] Tab-Navigation mit Plugin-Präfix-Klassen (`[prefix]-tabs`, `[prefix]-tab`)
- [ ] Content-Card mit `border-radius:0 10px 10px 10px;margin-top:0`
- [ ] Formulare mit `class="admin-form"`, CSRF-Token, eindeutigem `action`-Wert
- [ ] Speichern-Button: `btn btn-primary` mit Emoji-Präfix (`💾`)
- [ ] Farbfelder: `type="color"` + synchronisiertes Textfeld mit `pattern`-Validierung
- [ ] Integer-Inputs: `min` + `max` + serverseitige Clamp-Validierung
- [ ] Enum/Select-Werte: Whitelist-Prüfung im POST-Handler
- [ ] Boolean-Checkboxes: `!empty($_POST['key']) ? '1' : '0'`
- [ ] Nach erfolgreichem Speichern: Settings **neu laden** (`$settings = $db->get_settings()`)
- [ ] Info-Alerts für erklärungsbedürftige Abschnitte
- [ ] System-Info-Tab mit Plugin -Version und Infos aus der Datenbank
