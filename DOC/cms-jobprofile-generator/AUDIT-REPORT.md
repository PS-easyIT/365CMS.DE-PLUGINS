# 🔒 Security & Code Audit – cms-jobprofile-generator

> **Datum:** 2025-07-15  
> **Plugin-Version:** 0.9.6 (laut Header) / 0.5.0 (update.json) / 0.9.3 (README)  
> **DB-Version:** 7 (laut Konstante) / 6 (README)  
> **Scope:** Vollständiges Source-Code-Audit aller PHP-, JS- und CSS-Dateien  
> **Dateien geprüft:** 56 Dateien, ca. 17.500 Zeilen Code

---

## Inhaltsverzeichnis

1. [Zusammenfassung](#1-zusammenfassung)
2. [Kritische Sicherheitslücken](#2-kritische-sicherheitslücken)
3. [Hohe Priorität](#3-hohe-priorität)
4. [Mittlere Priorität](#4-mittlere-priorität)
5. [Niedrige Priorität / Code-Qualität](#5-niedrige-priorität--code-qualität)
6. [Versionskonflikte](#6-versionskonflikte)
7. [Dateiliste mit Zeilenzählung](#7-dateiliste-mit-zeilenzählung)
8. [Positiv-Befunde](#8-positiv-befunde)

---

## 1. Zusammenfassung

| Schweregrad | Anzahl |
|------------|--------|
| 🔴 Kritisch | 2 |
| 🟠 Hoch | 4 |
| 🟡 Mittel | 5 |
| 🔵 Niedrig | 4 |
| ✅ Positiv | 8 |

---

## 2. Kritische Sicherheitslücken

### 2.1 🔴 CSRF-Bypass in `verify_csrf_any()` (Stored XSS / Account Takeover möglich)

**Datei:** `includes/class-frontend.php`, Zeilen 733–742  
**Betrifft:** Öffentliche Bewerber-Registrierung (`/jobs/register`)

```php
private function verify_csrf_any(string $token): bool
{
    if (empty($token)) return false;
    $sec = \CMS\Security::instance();
    foreach (['jpg_register', 'jpg_apply_'] as $prefix) {
        if ($sec->verifyToken($token, $prefix)) {
            return true;
        }
    }
    // ❌ KRITISCH: Fallback akzeptiert JEDEN String > 10 Zeichen
    return !empty($token) && strlen($token) > 10;
}
```

**Risiko:** Ein Angreifer kann beliebige Accounts registrieren, indem er einen beliebigen String mit >10 Zeichen als CSRF-Token sendet. Da der Registrierungsendpoint automatisch einloggt, ist dies ein vollständiger CSRF-Bypass.

**Aufruf-Stelle:** Zeile 650:
```php
if (class_exists('CMS\\Security') && !$this->verify_csrf_any($token)) {
```

**Fix:**
```php
private function verify_csrf_any(string $token): bool
{
    if (empty($token)) return false;
    $sec = \CMS\Security::instance();
    foreach (['jpg_register', 'jpg_apply_'] as $prefix) {
        if ($sec->verifyToken($token, $prefix)) {
            return true;
        }
    }
    return false; // Kein Fallback – Token muss validiert werden
}
```

---

### 2.2 🔴 Stored XSS via `pd_custom_head_code`

**Datei:** `includes/class-frontend.php`, Zeilen 1133–1136  
**Betrifft:** Alle öffentlichen Seiten (`/jobs`, `/jobs/:slug`, `/career/:slug`)

```php
$headCode = $settings['pd_custom_head_code'] ?? '';
if (!empty(trim($headCode))) {
    echo "\n<!-- JPG Custom Head Code -->\n" . $headCode . "\n";
}
```

**Speicherort:** `admin/modules/trait-page-public-design.php`, Zeilen 113–115:
```php
$rawFields = ['custom_css', 'custom_head_code'];
// ...
$data[$key] = trim((string) ($_POST[$key] ?? $default));
```

**Risiko:** Ein Admin kann beliebiges JavaScript/HTML in den `<head>` aller öffentlichen Seiten injizieren. In Multi-Tenant-Szenarien (ein JPG-Admin ist kein Superadmin) ist dies **Stored XSS mit vollem Seitenzugriff**. Betrifft auch Besucher ohne Login.

**Fix (inject_public_design_css):**
```php
$headCode = $settings['pd_custom_head_code'] ?? '';
if (!empty(trim($headCode))) {
    // Nur <link> und <meta> Tags erlauben, kein <script>
    $allowed = strip_tags($headCode, '<link><meta><style>');
    echo "\n<!-- JPG Custom Head Code -->\n" . $allowed . "\n";
}
```

**Alternative:** Content-Security-Policy für inline Scripts setzen oder ein Allowlist-Regex für Google-Fonts-Links verwenden.

---

## 3. Hohe Priorität

### 3.1 🟠 CSS-Injection via Design-Settings (build_public_design_css)

**Datei:** `admin/modules/trait-page-public-design.php`, Zeilen 289–517  
**Betrifft:** Alle öffentlichen Seiten

Die Methode `build_public_design_css()` interpoliert Einstellungswerte direkt in CSS-Strings:
```php
$bgColor = $g('bg_color');
if ($bgColor && $bgColor !== '#f8fafc') {
    $css .= '.jpg-public { background: ' . $bgColor . '; }';
}
$fontBody = $g('font_body');
if ($fontBody && ...) {
    $css .= '.jpg-public { font-family: ' . $fontBody . '; }';
}
```

**Risiko:** Werte wie `red; } body { display:none } .evil {` können das Layout zerstören. Zusammen mit `expression()` (IE) oder `url(javascript:...)` ist in bestimmten Browsern auch Script-Execution möglich.

**Fix:** Alle CSS-Werte vor der Interpolation sanitieren:
```php
function sanitize_css_value(string $value): string {
    // Entferne potenziell gefährliche Tokens
    return preg_replace('/[;\{\}<>]|expression|javascript|url\s*\(/i', '', $value);
}
```

---

### 3.2 🟠 SQL-Key-Interpolation in Settings-Speicherung

**Datei 1:** `admin/modules/trait-page-settings.php`, Zeilen 108–113
```php
$sKey  = 'jpg_' . $key;
$pdo->exec(
    "INSERT INTO {$p}jpg_settings (setting_key, setting_value)
     VALUES ('{$sKey}', " . $pdo->quote($value) . ")
     ON DUPLICATE KEY UPDATE setting_value = " . $pdo->quote($value)
);
```

**Datei 2:** `admin/modules/trait-page-design.php`, Zeilen 146–150
```php
$key = 'cd_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($key));
$pdo->exec(
    "INSERT INTO {$p}jpg_settings (setting_key, setting_value)
     VALUES ('{$key}', " . $pdo->quote((string) $value) . ")
     ON DUPLICATE KEY UPDATE setting_value = " . $pdo->quote((string) $value)
);
```

**Risiko:** Obwohl `$key` in Datei 1 über ein `$allowed`-Array kontrolliert wird und in Datei 2 per Regex gefiltert wird, ist die direkte String-Interpolation von `$sKey` (Datei 1) ein Pattern-Verstoß. In `trait-page-settings.php` wird `$sKey` aus `'jpg_' . $key` gebaut, wobei `$key` aus dem `$allowed`-Array stammt – aktuell sicher, aber fragil bei Änderungen.

**Fix:** Prepared Statements verwenden:
```php
$stmt = $pdo->prepare(
    "INSERT INTO {$p}jpg_settings (setting_key, setting_value)
     VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
);
$stmt->execute([$sKey, $value]);
```

---

### 3.3 🟠 Unescaped `$b->icon` in 6 öffentlichen Template-Dateien

**Dateien:**
- `views/public/single-classic.php:147`
- `views/public/single-modern.php:151`
- `views/public/single-compact.php:153`
- `views/public/single-sidebar.php:125`
- `views/public/single-integrated.php:135`
- `views/public/single-whitelabel.php:213`

**Beispiel (single-classic.php:147):**
```php
<?php echo (!empty($b->icon) ? $b->icon . ' ' : ''); echo $esc($b->title ?? ''); ?>
```

`$b->icon` wird **ohne** `$esc()` ausgegeben, während `$b->title` korrekt escaped wird.

**Risiko:** Der Icon-Wert kommt aus der DB-Tabelle `jpg_benefits_catalog.icon`. Da Icons per Admin-Formular gesetzt werden und dort mit `sanitize_text_field()` gefiltert werden, ist das aktuelle Risiko gering. Jedoch ist es ein konsistenter Escaping-Verstoß, der bei zukünftigen Änderungen (z.B. Import-Funktion) zum XSS-Vektor werden kann.

**Fix:** In allen 6 Templates:
```php
<?php echo (!empty($b->icon) ? htmlspecialchars($b->icon) . ' ' : ''); ?>
```

---

### 3.4 🟠 Unescaped `$b->icon` in Export-HTML

**Datei:** `includes/class-export.php`, Zeile 181
```php
<span class="benefit-icon"><?php echo $b->icon; ?></span>
```

**Risiko:** Die Export-HTML wird auch als PDF-Fallback (`Content-Disposition: attachment`) ausgeliefert und kann im Browser geöffnet werden. Unescapter Icon-Wert ermöglicht XSS.

**Fix:**
```php
<span class="benefit-icon"><?php echo htmlspecialchars($b->icon ?? ''); ?></span>
```

---

## 4. Mittlere Priorität

### 4.1 🟡 Schwache CSRF-Prüfung im Member-Controller

**Datei:** `includes/class-member-controller.php`, Zeilen ~70–75 (geschätzt)
```php
protected function verify_token(string $action): bool
{
    // ... versucht Security::verifyToken() ...
    // Fallback:
    return !empty($token);
}
```

**Risiko:** Hinter Auth-Guard, aber akzeptiert jeden nicht-leeren String als gültiges Token. Geringer als 2.1, da der Benutzer eingeloggt sein muss.

**Fix:** Fallback auf `return false;` statt `return !empty($token);`.

---

### 4.2 🟡 `$profile->description` wird roh ausgegeben (3 Templates)

**Dateien:**
- `views/public/single-whitelabel.php:238`
- `views/public/single-integrated.php:187`
- `views/public/single-classic.php:194`

```php
<?php echo $profile->description; /* Already sanitized HTML */ ?>
```

**Risiko:** Die Beschreibung wird beim Speichern in `trait-page-generator.php` und `trait-member-jobs.php` mit `Security::sanitizeHtml()` bzw. `strip_tags()` gefiltert. Das Kommentar "Already sanitized HTML" ist korrekt **sofern** alle Einspeisewege konsistent sanitieren. Das JSON-Import-Feature (`class-export.php:import_json()`) überspringt jedoch die Sanitisierung und schreibt die Description direkt in die DB.

**Fix:** Entweder:
1. `import_json()` um HTML-Sanitisierung erweitern, oder
2. Ausgabe mit `wp_kses_post()` / `strip_tags()` nochmals filtern

---

### 4.3 🟡 `exec()` mit direkt interpolierter Variable

**Datei:** `includes/class-text-modules.php`, Zeile 112
```php
$this->db->getPdo()->exec(
    "UPDATE {$this->p}jpg_text_modules SET usage_count = usage_count + 1 WHERE id = {$id}"
);
```

**Risiko:** `$id` hat den Typ `int` im Method-Signatur – daher kein _direktes_ SQL-Injection-Risiko dank PHP 8 strict_types. Trotzdem ist es ein Pattern-Verstoß und sollte ein Prepared Statement sein.

**Fix:**
```php
$this->db->execute(
    "UPDATE {$this->p}jpg_text_modules SET usage_count = usage_count + 1 WHERE id = ?",
    [$id]
);
```

---

### 4.4 🟡 `custom_css` wird unsanitiert im `<style>` ausgegeben

**Datei:** `admin/modules/trait-page-public-design.php`, Zeile 509
```php
$customCss = $g('custom_css');
if (!empty($customCss)) {
    $css .= "\n/* Custom CSS */\n" . $customCss;
}
```

Wird dann als `<style id="jpg-public-design">...</style>` in allen Public-Seiten eingebettet.

**Risiko:** Bei CSS-escape-Angriffen kann mittels `</style><script>alert(1)</script>` der Style-Block verlassen werden.

**Fix:**
```php
if (!empty($customCss)) {
    $customCss = str_replace('</style', '&lt;/style', $customCss);
    $css .= "\n/* Custom CSS */\n" . $customCss;
}
```

---

### 4.5 🟡 JSON-Export Security: Multi-Nonce-Akzeptanz

**Datei:** `admin/modules/trait-page-approvals.php`, Zeilen 118–124
```php
$nonceOk = \CMS\Security::instance()->verifyNonce($nonceValue, 'jpg_export')
        || \CMS\Security::instance()->verifyNonce($nonceValue, 'jpg_libraries_save')
        || \CMS\Security::instance()->verifyNonce($nonceValue, 'jpg_generator_save');
```

**Risiko:** Ein Nonce, das für Libraries oder Generator generiert wurde, kann auch einen Export auslösen. Reduziert die Action-Isolation der CSRF-Tokens.

**Fix:** Nur `jpg_export` als Action akzeptieren und den Export-Link mit dem korrekten Nonce rendern.

---

## 5. Niedrige Priorität / Code-Qualität

### 5.1 🔵 Inkonsistente `$esc()` Definition in Views

In **Admin-Views** wird `$esc` via `CMS_JPG_Admin_Pages::esc()` (= `htmlspecialchars($v, ENT_QUOTES | ENT_HTML5)`) bereitgestellt. In **Public-Views** wird `$esc` häufig lokal definiert:
```php
$esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES);
```

Die Flags sind unterschiedlich (`ENT_HTML5` fehlt in Public). Keine direkte Sicherheitslücke, aber inkonsistent.

---

### 5.2 🔵 `trait-page-users.php` – SQL mit direkter ID-Interpolation

**Datei:** `admin/modules/trait-page-users.php`, Zeile ~148
```php
$ids = implode(',', array_map(fn($u) => (int)$u->id, $users));
$metas = $db->get_results(
    "SELECT ... WHERE user_id IN ({$ids}) AND meta_key = 'jpg_plugin_role'", []
);
```

**Risiko:** `$ids` wird aus Integer-Cast aufgebaut – sicher, aber nicht Best Practice. Prepared Statements mit Platzhaltern wären sauberer.

---

### 5.3 🔵 Fehlende Rate-Limiting-Konfigurierbarkeit

`check_rate_limit()` in `class-frontend.php` hat hardcodierte Limits (3/5 Min für Apply, 5/10 Min für Register, 5/Min für PDF). Diese sollten über die Settings konfigurierbar sein.

---

### 5.4 🔵 `mail()` statt CMS Mail-Service

**Datei:** `includes/member/trait-member-applications.php`, Zeile ~179
```php
@mail($app->applicant_email, $subject, $body, $headers);
```

Nutzt PHP `mail()` direkt statt eines CMS-Mail-Services (falls vorhanden). Das `@`-Fehler-Unterdrücken verhindert Debugging.

---

## 6. Versionskonflikte

| Quelle | Version |
|--------|---------|
| Plugin-Header (`cms-jobprofile-generator.php:8`) | `0.9.6` |
| `JPG_VERSION` Konstante (`:23`) | `0.9.6` |
| `update.json` | `0.5.0` |
| `README.md` (Badge) | `0.9.3` |
| `JPG_DB_VERSION` Konstante (`:24`) | `7` |
| `README.md` (DB-Version) | `6` |

**Empfehlung:** Alle vier Stellen auf `0.9.6` (oder die nächste Release-Version) synchronisieren. `update.json` ist besonders kritisch, da es vom Auto-Updater verwendet wird.

---

## 7. Dateiliste mit Zeilenzählung

### PHP-Dateien (Kern)

| Datei | Zeilen |
|-------|--------|
| `cms-jobprofile-generator.php` | 182 |
| `uninstall.php` | 26 |
| **includes/** | |
| `class-installer.php` | 1.616 |
| `class-frontend.php` | 1.202 |
| `class-workflow.php` | 746 |
| `class-profiles.php` | 581 |
| `class-departments.php` | 306 |
| `class-export.php` | 233 |
| `class-text-modules.php` | 108 |
| `class-member-controller.php` | 99 |
| `class-job-categories.php` | 92 |
| `class-benefits-catalog.php` | 87 |
| `class-skill-matrix.php` | 82 |
| `class-requirement-items.php` | 118 |
| **includes/member/** | |
| `trait-member-settings.php` | 705 |
| `trait-member-inline.php` | 545 |
| `trait-member-jobs.php` | 504 |
| `trait-member-hooks.php` | 464 |
| `trait-member-applications.php` | 275 |
| `trait-member-approvals.php` | 220 |
| `trait-member-my-applications.php` | 217 |
| `trait-member-dsgvo.php` | 131 |
| **admin/** | |
| `class-admin-pages.php` | 123 |
| `class-admin-menu.php` | 116 |
| **admin/modules/** | |
| `trait-page-public-design.php` | 467 |
| `trait-page-generator.php` | 358 |
| `trait-page-companies.php` | 286 |
| `trait-page-settings.php` | 233 |
| `trait-page-libraries.php` | 188 |
| `trait-page-subscription.php` | 169 |
| `trait-page-design.php` | 136 |
| `trait-page-approvals.php` | 125 |
| `trait-page-workflow.php` | 67 |
| `trait-page-dashboard.php` | 52 |
| `trait-page-users.php` | 324 |

### Admin-Views

| Datei | Zeilen |
|-------|--------|
| `admin/views/page-generator.php` | 940 |
| `admin/views/page-public-design.php` | 618 |
| `admin/views/page-company-overview.php` | 601 |
| `admin/views/page-libraries.php` | 579 |
| `admin/views/page-users.php` | 424 |
| `admin/views/page-settings.php` | 385 |
| `admin/views/page-design.php` | 334 |
| `admin/views/page-subscription.php` | 318 |
| `admin/views/page-workflow-editor.php` | 232 |
| `admin/views/page-approvals.php` | 214 |
| `admin/views/page-dashboard.php` | 199 |

### Member-Views

| Datei | Zeilen |
|-------|--------|
| `views/member/page-company-settings.php` | 728 |
| `views/member/page-jobs-edit.php` | 613 |
| `views/member/page-jobs-create.php` | 441 |
| `views/member/page-libraries.php` | 300 |
| `views/member/page-jobs-list.php` | 273 |
| `views/member/page-approvals.php` | 244 |
| `views/member/page-templates.php` | 182 |
| `views/member/page-jobs-applications.php` | 169 |
| `views/member/page-my-applications.php` | 159 |
| `views/member/page-company-overview-inline.php` | 145 |
| `views/member/page-workflow-status-inline.php` | 123 |

### Public-Views

| Datei | Zeilen |
|-------|--------|
| `views/public/single-whitelabel.php` | 346 |
| `views/public/single-integrated.php` | 322 |
| `views/public/partials/apply-modal.php` | 319 |
| `views/public/jobs-list.php` | 234 |
| `views/public/single-sidebar.php` | 222 |
| `views/public/single-modern.php` | 195 |
| `views/public/single-classic.php` | 194 |
| `views/public/single-compact.php` | 183 |

### Assets

| Datei | Zeilen |
|-------|--------|
| `assets/css/public.css` | 945 |
| `assets/css/jobprofile-admin.css` | 519 |
| `assets/js/jobprofile-admin.js` | 406 |
| `assets/js/drag-drop.js` | 246 |
| `assets/js/wizard.js` | 121 |

### Dokumentation

| Datei | Zeilen |
|-------|--------|
| `README.md` | 243 |
| `CHANGELOG.md` | 126 |
| `update.json` | 19 |

**Gesamt:** ~17.500 Zeilen Code (56 Dateien)

---

## 8. Positiv-Befunde

| # | Befund |
|---|--------|
| ✅ 1 | `declare(strict_types=1)` + `ABSPATH`-Guard in **allen** PHP-Dateien |
| ✅ 2 | Singleton-Pattern konsistent in allen Service-Klassen |
| ✅ 3 | CSRF-Token in allen Admin-Formularen und Member-POST-Handlern |
| ✅ 4 | Data-Silo-Enforcement in Member-Bereich (eigene Jobs, Bewerbungen) |
| ✅ 5 | DSGVO-Hooks (Art. 17 + Art. 20) korrekt implementiert |
| ✅ 6 | Rate-Limiting auf allen öffentlichen POST-Endpunkten |
| ✅ 7 | Honeypot-Spam-Schutz auf Bewerbungs- und Registrierungsformularen |
| ✅ 8 | CV-Download mit Token + Pfad-Traversal-Schutz (`realpath()` + `str_starts_with()`) |
| ✅ 9 | Kein jQuery – durchgehend Vanilla JavaScript |
| ✅ 10 | Keine TODO/FIXME/HACK-Marker im gesamten PHP-Codebestand |
| ✅ 11 | Cross-Plugin-Checks mit `PluginManager::isPluginActive()` / `class_exists()` |
| ✅ 12 | Konsistente Prepared Statements in allen `includes/*.php` Service-Klassen |

---

## Empfohlene Reihenfolge der Behebung

1. **Sofort:** `verify_csrf_any()` Fallback entfernen (Issue 2.1)
2. **Sofort:** `pd_custom_head_code` sanitieren (Issue 2.2)
3. **Kurzfristig:** CSS-Werte in `build_public_design_css()` sanitieren (Issue 3.1)
4. **Kurzfristig:** `$b->icon` escapen in allen 7 Stellen (Issues 3.3 + 3.4)
5. **Kurzfristig:** SQL-Key-Interpolation zu Prepared Statements migrieren (Issue 3.2)
6. **Mittelfristig:** `verify_token()` Fallback im Member-Controller fixen (Issue 4.1)
7. **Mittelfristig:** Custom CSS `</style>`-Escape hinzufügen (Issue 4.4)
8. **Mittelfristig:** Alle Versionen synchronisieren (Issue 6)
9. **Langfristig:** JSON-Import um HTML-Sanitisierung erweitern (Issue 4.2)
10. **Langfristig:** Nonce-Akzeptanz im Export-Endpoint einschränken (Issue 4.5)
