# 🔗 Cross-Plugin-Integration – CMS Job Profile Generator

> **Leitprinzip:** Null modifizierte Fremd-Dateien. Alles läuft über  
> 1. Prüfungen via `CMS\PluginManager`  
> 2. Hook-basierte Erweiterungen via `CMS\Hooks`  
> 3. Direct DB-Queries auf definierte Schnittstellentabellen  

---

## Inhalt

1. [cms-companies – Firmenprofil-Integration](#1-cms-companies)
2. [cms-experts – Team-Sektion](#2-cms-experts)
3. [Updatepfade & Kompatibilitätsstrategie](#3-updatepfade)
4. [Debugging-Hinweise](#4-debugging)

---

## 1. cms-companies

### 1a. Wizard-Firmen-Dropdown (Phase 6.1)

**Ziel:** Das Firmen-Dropdown im Profil-Generator (Tab „Basisdaten") wird dynamisch aus der `cms_companies`-Tabelle befüllt, sofern das Plugin aktiv ist.

**Implementierung:** `admin/class-admin-pages.php` → `render_generator()`

```php
$companies = [];
if (class_exists('CMS\PluginManager')
    && in_array('cms-companies', \CMS\PluginManager::instance()->getActivePlugins(), true)) {
    try {
        $db        = \CMS\Database::instance();
        $p         = $db->getPrefix();
        $companies = $db->get_results(
            "SELECT id, name FROM {$p}companies WHERE status = 'active' ORDER BY name ASC",
            []
        ) ?: [];
    } catch (\Throwable $e) {
        $companies = []; // Fallback: leere Dropdown-Liste
    }
}
```

**Verhalten ohne cms-companies:** Der `<select>` im Wizard zeigt nur den Platzhalter „Kein Plugin aktiv" und ermöglicht manuellen Text-Eintrag.

**Schnittstellentabelle:** `{prefix}companies` (readonly; kein Schreibzugriff)

---

### 1c. Firmen-Autofill im Generator-Wizard (v0.6.0)

**Ziel:** Sobald der User im Wizard-Tab „Basisdaten" eine Firma auswählt, werden Standortdaten (PLZ, Ort, Land) automatisch in das `#jpg-location`-Feld eingetragen. Zusätzlich erscheint eine Info-Box mit Telefon und Website.

**Implementierung:** `admin/class-admin-pages.php` → `render_generator()` liefert `$companiesJson` – eine JSON-Map aller aktiven Firmen:

```php
$companiesJson = json_encode(
    array_column(
        array_map(fn($c) => [
            'id'      => (int) $c->id,
            'name'    => $c->name,
            'city'    => $c->location_city ?? '',
            'zip'     => $c->location_zip  ?? '',
            'country' => $c->location_country ?? '',
            'phone'   => $c->phone   ?? '',
            'website' => $c->website ?? '',
        ], $companies),
        null, 'id'
    )
);
```

**Frontend-Logik** (`page-generator.php` – Inline-Script):

```javascript
var jpgCompanyData = <?php echo $companiesJson; ?>;

function jpgFillCompanyData(id) {
    var c = jpgCompanyData[id];
    if (!c) return;
    document.getElementById('jpg-location').value =
        [c.zip, c.city, c.country].filter(Boolean).join(' ');
    // Info-Box einblenden …
}
```

**Kein Schreibzugriff** auf `{prefix}companies`. Query: `SELECT id, name, location_city, location_zip, location_country, phone, website FROM companies WHERE status = 'active'`.

---

### 1d. Standard-Benefits pro Unternehmen (v0.6.0)

**Ziel:** Administratoren können im neuen Menüpunkt **🏢 Unternehmens-Übersicht** für jedes Unternehmen eine feste Auswahl von Standard-Benefits hinterlegen. Beim Anlegen eines neuen Profils für dieses Unternehmen werden diese Benefits im Generator-Wizard (Tab „Benefits") automatisch vorausgewählt.

**Implementierung:**
- Neue Tabelle `jpg_company_default_benefits` (Mapping `company_id ↔ benefit_id`)
- `render_company_overview()`: Lädt Firmen (readonly), Benefit-Katalog und Zuweisungsmap
- `handle_company_overview_post()`: Speichert via DELETE+INSERT (vollständiger Tausch)
- `render_generator()`: Lädt `$companyDefaultBenefitIds` für die aktuell zugewiesene Firma des Profils

**Vorauswahl-Logik im Wizard:**

```php
// Nur vorauswählen, wenn noch keine explizite Auswahl für dieses Profil existiert:
$checked = in_array($bidInt, $benefitIds)
    || (empty($benefitIds) && in_array($bidInt, $companyDefaultBenefitIds));
```

**Schnittstellentabellen:**

| Tabelle | Zugriff | Felder |
|---|---|---|
| `{prefix}companies` | Lesen | `id`, `name` |
| `{prefix}jpg_company_default_benefits` | Lesen + Schreiben | `company_id`, `benefit_id` |
| `{prefix}jpg_benefits` | Lesen | alle |

---

### 1b. Jobs auf Firmenprofil anzeigen (Phase 6.2)

**Ziel:** Auf `/companies/:slug`-Seiten wird am Ende des Seiteninhalts automatisch eine Karte „📌 Offene Stellen bei [Firmenname]" eingefügt.

**Implementierung:** `includes/class-frontend.php`  
- Registrierung: `register_hooks()` → `CMS\Hooks::addFilter('page_content', ...)`
- Logik: `inject_company_jobs(string $content): string`

**Ablauf:**

```
Seitenaufruf  →  page_content Filter  →  URL enthält /companies/  
    →  Slug aus URL extrahieren  →  SELECT company_id WHERE slug = ?  
    →  SELECT jpg_profiles WHERE company_id = ? AND status = 'published'  
    →  HTML-Karten-Grid generieren  →  $content . $html  
```

**Kein Eingriff in:** `cms-companies`-Templates, Views, Controller.

**Nur gelesen:** `{prefix}companies.id`, `.name`, `.slug`

---

## 2. cms-experts

### 2a. „Lerne dein Team kennen" im Job-Template (Phase 6.3)

**Ziel:** Im öffentlichen Job-Profil wird unterhalb der Benefits-Sektion ein Abschnitt mit Ansprechpartnern der Firma angezeigt.

**Implementierung:** `includes/class-frontend.php` → `load_company_experts()`

```php
private function load_company_experts(object $profile): array
{
    if (empty($profile->company_id)) return [];
    if (!class_exists('CMS\PluginManager')
        || !in_array('cms-experts', \CMS\PluginManager::instance()->getActivePlugins(), true)) {
        return [];
    }
    // JOIN auf cms_company_experts (Pivot-Tabelle von cms-experts)
    $db = \CMS\Database::instance();
    $p  = $db->getPrefix();
    return $db->get_results(
        "SELECT e.id, e.name, e.title AS job_title, e.avatar_url, e.bio_short
         FROM {$p}cms_experts e
         INNER JOIN {$p}cms_company_experts ce ON ce.expert_id = e.id
         WHERE ce.company_id = ? AND e.active = 1
         ORDER BY ce.sort_order ASC, e.name ASC
         LIMIT 8",
        [(int) $profile->company_id]
    ) ?: [];
}
```

**Template-Integration:** `views/public/single-integrated.php` liest die Variable `$experts` (aus `prepare_template_data()`) und rendert die Team-Kacheln.

**Genutzte Tabellen (nur lesend):**

| Tabelle | Felder |
|---|---|
| `{prefix}cms_experts` | `id`, `name`, `title`, `avatar_url`, `bio_short`, `active` |
| `{prefix}cms_company_experts` | `expert_id`, `company_id`, `sort_order` |

**Shortcode-Rendering:** Zum jetzigen Stand werden die Experten über direkte DB-Abfrage gerendert (nicht via `[cms_expert id="x"]` Shortcode), da der CMS-Shortcode-Parser zur Laufzeit nicht gesichert verfügbar ist. Bei Verfügbarkeit kann `do_shortcode()` alternativ genutzt werden.

---

## 1d. Firma = Mandant – Automatische Plugin-Rollen-Zuweisung

**Ziel:** Wenn ein User mit einer bestimmten Firma verknüpft ist (Mandant-Funktion), erhält er automatisch die Plugin-Rolle des zugehörigen Unternehmens, ohne dass ein Admin manuell zuweisen muss.

**Implementierung:** `admin/modules/trait-page-users.php` und `trait-page-subscription.php`

Beim Laden der Plugin-User-Seite (`jpg-users`) prüft der Controller, ob der eingeloggte User bereits einer Firma zugewiesen ist:

```php
// trait-page-users.php – render_users()
$userCompany = $db->get_row(
    "SELECT c.id, c.name FROM {$p}companies c
     JOIN {$p}company_members cm ON cm.company_id = c.id
     WHERE cm.user_id = :uid LIMIT 1",
    [':uid' => $userId]
);

if ($userCompany) {
    // Mandant erkannt: Plugin-Rolle der Firma automatisch zuweisen
    $this->assign_plugin_role($userId, $userCompany->id);
}
```

**Verhalten:**
- Ist ein User Mitglied einer Firma in `cms-companies`, wird er als „Mandant" dieser Firma behandelt
- Seine Plugin-Rollen-Einschränkungen gelten auf Unternehmensebene (Limits, Feature-Flags)
- Admins können in der Plugin-Subscription-Verwaltung dennoch überschreiben

**Guard:** Wie alle `cms-companies`-Integrationen nur aktiv wenn `cms-companies` aktiv ist:
```php
if (!in_array('cms-companies', \CMS\PluginManager::instance()->getActivePlugins(), true)) {
    return; // silent fallback
}
```

---

## 3. Updatepfade & Kompatibilitätsstrategie

### Warum kein direkter `require_once` / `class_exists`-Check auf Plugin-Klassen?

Anders als WordPress-Plugins sind 365CMS-Plugins **nicht zueinander autoloaded**.  
Der einzige stabile Vertrag ist `CMS\PluginManager::getActivePlugins()` + die gemeinsamen Datenbanktabellen.

### Kompatibilitäts-Matrix

| Szenario | Verhalten |
|---|---|
| `cms-companies` aktiv | Wizard-Dropdown befüllt; Company-Jobs auf Firmenprofil; Firmen-Autofill im Basisdaten-Tab; Standard-Benefits zuweisbar |
| `cms-companies` inaktiv | Manuelles Textfeld im Wizard; Firmeninhalt ohne Job-Block; Unternehmens-Übersicht-Menüpunkt inaktiv (Guard aktiv) |
| `cms-experts` aktiv | Team-Sektion im Job-Template sichtbar |
| `cms-experts` inaktiv | `$experts = []`; Template rendert keine Team-Sektion |
| Beide inaktiv | Vollständige Standalone-Funktion |

### Updates anderer Plugins

Da **keine** Dateien anderer Plugins modifiziert werden, können `cms-companies` und `cms-experts` unabhängig aktualisiert werden. Folgende Punkte überwachen:

- **Tabellenumbenennung:** Sollte `cms_companies` in `cms_company_profiles` o.ä. umbenannt werden, muss nur `render_generator()` und `inject_company_jobs()` angepasst werden.
- **Schema-Änderungen:** Nur selektierte Felder (`id`, `name`, `slug`, `status`) werden genutzt – Spaltenergänzungen brechen nichts.

---

## 4. Debugging

### Plugin-Aktivitätsprüfung fehlschlägt

```php
var_dump(\CMS\PluginManager::instance()->getActivePlugins());
// Erwartet: array mit 'cms-companies' oder 'cms-experts'
```

### Firmen-Jobs erscheinen nicht auf Firmenprofil

1. Route enthält `/companies/`?  
   → `var_dump($_SERVER['REQUEST_URI'])`
2. Slug wird korrekt extrahiert?  
   → Regex: `#/companies/([^/?#]+)#`
3. Firma in DB vorhanden und `status = 'active'`?
4. Jobs existieren mit `company_id = X AND status = 'published'`?

### Experten erscheinen nicht

1. `$profile->company_id > 0`?
2. Einträge in `cms_company_experts` für diese Firma?
3. `cms_experts.active = 1`?

---

*Erstellt: 2026-02-25 | Aktualisiert: 2026-02-26*
