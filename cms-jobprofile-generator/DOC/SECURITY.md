# Sicherheitskonzept – CMS Job Profile Generator

> Das Plugin implementiert die obligatorischen 365CMS-Sicherheitsstandards:  
> CSRF-Nonces, PDO Prepared Statements, striktes XSS-Escaping und Input-Sanitierung.

---

## Inhaltsverzeichnis

1. [CSRF-Schutz (Nonces)](#csrf-schutz-nonces)
2. [Input-Sanitierung](#input-sanitierung)
3. [Output-Escaping (XSS)](#output-escaping-xss)
4. [Datenbankzugriff (SQL-Injection)](#datenbankzugriff-sql-injection)
5. [Zugriffskontrollen (Auth)](#zugriffskontrollen-auth)
6. [Datei-Upload-Validierung](#datei-upload-validierung)
7. [Sicherheits-Checkliste](#sicherheits-checkliste)

---

## CSRF-Schutz (Nonces)

Jedes Formular und jede AJAX-POST-Anfrage enthält einen CSRF-Token.

### Nonce erzeugen (View)

```php
// In einer View-Datei:
$nonce = CMS_JPG_Admin_Pages::nonce('jpg_save_profile');
// Gibt intern CMS\Security::instance()->generateNonce('jpg_save_profile') zurück
```

```html
<form method="post">
    <input type="hidden" name="_jpg_nonce" value="<?php echo esc_attr($nonce); ?>">
    <!-- ... -->
</form>
```

### Nonce verifizieren (Controller)

```php
// In admin/class-admin-pages.php – POST-Handler
private function handle_save_profile(): void
{
    if (!\CMS\Security::instance()->verifyNonce(
        $_POST[self::NONCE_FIELD] ?? '',
        'jpg_save_profile'
    )) {
        $this->error = 'Sicherheitsüberprüfung fehlgeschlagen.';
        return;
    }
    // weitere Verarbeitung ...
}
```

### Nonce-Aktions-Tabelle

| Aktion | Nonce-String |
|---|---|
| Profil speichern (alle Tabs) | `jpg_save_profile` |
| Profil löschen | `jpg_delete_profile` |
| Profil veröffentlichen | `jpg_publish_profile` |
| Textbaustein CRUD | `jpg_lib_save` |
| Skill-Matrix CRUD | `jpg_lib_save` |
| Benefit-Katalog CRUD | `jpg_lib_save` |
| Job-Kategorien CRUD | `jpg_lib_save` |
| Anforderungs-Bausteine CRUD | `jpg_lib_save` |
| JSON-Import | `jpg_import` |
| Template CRUD | `jpg_template_save` |
| Einstellungen speichern | `jpg_settings_save` |
| AJAX HTML-Vorschau | `jpg_preview` |
| Unternehmens-Benefits speichern | `jpg_company_benefits_save` |
| Workflow Approve/Reject/Reset | `jpg_workflow_action` |
| Genehmigung Admin-Seite | `jpg_approvals_action` |

---

## Input-Sanitierung

Alle `$_POST`- und `$_GET`-Werte werden **vor** der Verarbeitung bereinigt.

### Sanitierungs-Regeln

```php
// Einzeiliger Text (kein HTML erlaubt)
$title = sanitize_text_field($_POST['title'] ?? '');

// Integer-Wert
$categoryId = (int)($_POST['category_id'] ?? 0);

// Dezimalzahl
$salaryMin = (float)($_POST['salary_min'] ?? 0.0);

// Aufzählungswert (Enum-Validation)
$status = in_array($_POST['status'] ?? '', ['draft','published','archived'], true)
    ? $_POST['status']
    : 'draft';

// URL
$url = filter_var($_POST['logo_url'] ?? '', FILTER_VALIDATE_URL) ?: '';

// Rich-Text (SunEditor-Output) – gefährliche Tags entfernen
$description = \CMS\Security::instance()->sanitizeHtml(
    $_POST['description'] ?? ''
);
```

### Erlaubte HTML-Tags für `sanitizeHtml()`

```
<p> <br> <strong> <em> <u> <s> <ul> <ol> <li>
<h2> <h3> <h4> <a href> <blockquote> <table> <tr> <td> <th>
```

### Mindest-Aufgaben-Validierung

```php
// Mindestens 3 Aufgaben sind Pflicht (enforced im Controller)
$tasks = (array)($_POST['tasks'] ?? []);
$tasks = array_filter($tasks, fn($t) => !empty(trim($t['description'] ?? '')));

if (count($tasks) < 3) {
    $this->error = 'Mindestens 3 Aufgaben sind erforderlich.';
    return;
}
```

---

## Output-Escaping (XSS)

**Grundregel:** Jede Variable, die aus der Datenbank oder vom Nutzer kommt, wird vor der Ausgabe escaped.

| Kontext | Funktion | Beispiel |
|---|---|---|
| HTML Text-Node | `htmlspecialchars($v)` | `<?php echo htmlspecialchars($title); ?>` |
| HTML-Attribut | `htmlspecialchars($v, ENT_QUOTES)` | `value="<?php echo htmlspecialchars($val, ENT_QUOTES); ?>"` |
| URL in `href`/`action` | `esc_url($v)` | `<a href="<?php echo esc_url($url); ?>">` |
| JavaScript-Inline-Daten | `esc_js($v)` | `var title = '<?php echo esc_js($title); ?>';` |
| CMS-Escape-Wrapper | `\CMS\Security::instance()->escapeOutput($v)` | Für komplexe Kontexte |
| Vertrauenswürdiges HTML | Nur nach `sanitizeHtml()` direkt ausgeben | SunEditor-Inhalt aus DB |

### Anti-Pattern (NIEMALS):

```php
// ❌ Direkte Ausgabe ohne Escaping
echo $_POST['title'];

// ❌ htmlentities() ohne korrektes Encoding
echo htmlentities($value); // fehlendes ENT_QUOTES + charset

// ✅ Korrekt
echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
```

---

## Datenbankzugriff (SQL-Injection)

Das Plugin verwendet **ausschließlich PDO Prepared Statements** über `CMS\Database::instance()`.

### Korrekte Abfragen

```php
$db = \CMS\Database::instance();
$prefix = $db->getPrefix();

// SELECT – einzelner Datensatz
$stmt = $db->prepare(
    "SELECT * FROM {$prefix}jpg_profiles WHERE id = :id AND status = :status LIMIT 1"
);
$stmt->execute([':id' => $id, ':status' => 'published']);
$profile = $stmt->fetch();

// INSERT
$db->insert($prefix . 'jpg_profiles', [
    'user_id'  => $userId,
    'title'    => $title,
    'slug'     => $slug,
    'status'   => 'draft',
]);

// UPDATE
$db->update(
    $prefix . 'jpg_profiles',
    ['status' => 'published', 'updated_at' => date('Y-m-d H:i:s')],
    ['id' => $id]
);

// DELETE
$db->delete($prefix . 'jpg_profiles', ['id' => $id]);
```

### Anti-Pattern (NIEMALS):

```php
// ❌ String-Interpolation mit Nutzerdaten
$db->query("SELECT * FROM profiles WHERE title = '$title'");

// ❌ Direktes Einsetzen von $_POST in SQL
$db->query("DELETE FROM profiles WHERE id = " . $_POST['id']);
```

---

## Zugriffskontrollen (Auth)

### Admin-Check (alle View-Dateien)

Jede Admin-View gibt Daten nur aus, wenn `CMS_JPG_Admin_Pages` die Anfrage bereits validiert hat. Der Controller prüft:

```php
// admin/class-admin-pages.php
if (!\CMS\Auth::instance()->isAdmin()) {
    wp_die('Zugriff verweigert.', 403);
}
```

### Eigentümer-Check

Bei profilspezifischen Aktionen (Bearbeiten, Löschen) wird geprüft, ob der angemeldete Nutzer Eigentümer des Profils ist oder Admin-Rechte hat:

```php
$profile = CMS_JPG_Profiles::instance()->get($profileId);
$currentUser = \CMS\Auth::instance()->getCurrentUser();

if ((int)$profile->user_id !== (int)$currentUser->id
    && !\CMS\Auth::instance()->isAdmin()
) {
    wp_die('Sie haben keine Berechtigung für dieses Profil.', 403);
}
```

---

## Datei-Upload-Validierung

Beim JSON-Import und beim SVG-Icon-Upload im Benefit-Katalog:

```php
// JSON-Import: Dateityp prüfen
$allowedMimes = ['application/json', 'text/plain'];
$finfo = new \finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($_FILES['import_file']['tmp_name']);

if (!in_array($mime, $allowedMimes, true)) {
    $this->error = 'Nur JSON-Dateien sind erlaubt.';
    return;
}

// Dateigröße begrenzen (max. 5 MB)
if ($_FILES['import_file']['size'] > 5 * 1024 * 1024) {
    $this->error = 'Datei zu groß (max. 5 MB).';
    return;
}

$json = file_get_contents($_FILES['import_file']['tmp_name']);
$data = json_decode($json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $this->error = 'Ungültiges JSON-Format.';
    return;
}
```

---

## Sicherheits-Checkliste

### Backend (Admin-Seiten)

- [x] `CMS\Auth::instance()->isAdmin()` in **jedem** Controller-Einstiegspunkt
- [x] CSRF-Nonce in **jedem** Formular und AJAX-Request
- [x] Alle `$_POST`-Werte sanitiert mit `sanitize_text_field()`, `(int)`, `(float)`, Enum-Check
- [x] SunEditor-HTML durch `sanitizeHtml()` gefiltert
- [x] Alle DB-Abfragen über Prepared Statements
- [x] Alle HTML-Ausgaben durch `htmlspecialchars()` oder `esc_url()` escapt
- [x] Datei-Uploads auf MIME-Typ und Größe geprüft
- [x] Kein `window.confirm()` – stattdessen eigenes Modal-Bestätigungssystem

### Frontend (Öffentliche Profilseiten)

- [x] Öffentliche Profile: Ausgabe über `\CMS\Security::instance()->escapeOutput()`
- [x] SunEditor-HTML: nur nach `sanitizeHtml()` ausgegeben
- [x] Kein direkter Zugriff auf `$_GET`-Parameter ohne Validierung
- [ ] Rate-Limiting für PDF-Export-Endpunkt (zukünftig, Phase 5.2)
- [ ] Auto-Save AJAX-Endpunkt mit separatem Rate-Limit (zukünftig, Phase 5.2)

---

## Verwandte Dokumentation

- [HOOKS-API.md](HOOKS-API.md) – Filter zum Modifizieren von Ausgabedaten
- [FRONTEND-ROUTING.md](FRONTEND-ROUTING.md) – Öffentliche Routen & Caching
