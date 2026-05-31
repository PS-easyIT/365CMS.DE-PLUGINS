# CMS Companies – Dokumentation

**Plugin:** `cms-companies`  
**Version:** 3.0.11
**Namespace:** `CMS_Companies`  
**Aktueller Laufzeitstand:** 365CMS 3.0+  
**Audit-/Dokustand:** PHINIT-Preview-Detailseiten-Pass am 2026-05-31  
**PHP:** 8.1+

---

## Übersicht

Das **CMS Companies**-Plugin verwaltet Firmen-Profile innerhalb des 365CMS-Ökosystems. Es stellt die zentrale Datenbasis für Unternehmen bereit, auf die andere Plugins (Events, Experts, Speakers, Organigramm) zurückgreifen.

## 3.0.x-Status

- Die Dokumentation ist auf den **Audit- und Zielstand für 365CMS 3.x** angehoben.
- Änderungen erfolgen ausschließlich im Plugin; **der 365CMS-Core bleibt unberührt**.
- Version 3.0.1 gruppiert Archiv-Header und Suche sauber und lässt Company-Cards ohne feste Höhe wachsen.
- Version 3.0.2 stellt die öffentliche Übersicht wie `cms-events` auf Filter + responsives Card-Grid um, mit Company-spezifischen Badges und Meta-Informationen.
- Version 3.0.6 begrenzt die Public-Übersicht intern auf `1160px` und legt den Hintergrund bündig bis Theme-Header/-Footer.
- Version 3.0.8 finalisiert den einheitlichen Companies-basierten Public-Suchbereich.
- Version 3.0.9 überträgt die bündige Full-Bleed-Hintergrund-Shell auf die Public-Detailseite; Breadcrumb und Detail-Grid bleiben auf maximal `1160px` begrenzt.
- Version 3.0.10 baut die Public-Detailseite nach `company-detail-vorschau.html` um: Navy/Amber-Hero, Preview-Kartenlayout, übersetzte Labels, lokaler YAML-Fallback, verwandte Unternehmen und detail-only `single.css`.

### Kernfunktionen

| Bereich | Funktion |
|---------|----------|
| **Firmenprofil** | Name, Branche, Größe, Standort, Kontakt, Logo, Website |
| **Partner-Status** | Partner / Top-Partner / Sponsor Badges |
| **Experten-Zuordnung** | M2M-Relation zu `cms-experts` via `cms_company_experts` |
| **Meta-Daten** | Flexibles Key-Value-System via `cms_company_meta` |
| **Admin-Backend** | Vollständige CRUD-Oberfläche unter `/admin/companies` |
| **Member-Dashboard** | Eigenes Firmenprofil im Member-Bereich verwalten |
| **Shortcode** | `[cms_companies]` – Grid-Ansicht |
| **Öffentliche Routen** | `/companies`, `/companies/{id}` Legacy-Redirect, `/company/{slug-id}` |

---

## Dateistruktur

```
cms-companies/
├── cms-companies.php               # Singleton-Hauptklasse
├── README.md
├── update.json
├── includes/
│   ├── class-database.php          # Tabellenanlage & DB-Queries
│   ├── class-admin.php             # Admin-Hook-Registrierung
│   ├── class-member-dashboard.php  # Member-Frontend
│   ├── class-meta-boxes.php        # Meta-Felder-Rendering
│   ├── class-post-type.php         # Routing / Post-Type
│   ├── class-shortcode.php         # [cms_companies] Shortcode
│   └── class-template-loader.php   # Frontend-Template-Dispatcher
├── templates/
│   ├── archive-company.php
│   ├── company-card.php
│   └── single-company.php
└── assets/
    ├── css/
    └── js/
```

---

## Weitere Dokumente

| Dokument | Inhalt |
|----------|--------|
| [DATABASE.md](DATABASE.md) | Alle Tabellen, Spalten, Indizes, Relationen |
| [HOOKS.md](HOOKS.md) | Actions & Filter mit Signaturen |
| [API.md](API.md) | Programmatische Zugriffs-Referenz |
| [CHANGELOG.md](CHANGELOG.md) | Versionshistorie |
| [SECURITY.md](SECURITY.md) | Sicherheitskonzept |

---

## Schnellstart

### Admin-Interface
```
/admin/companies        → Übersicht aller Firmen
/admin/companies?new    → Neue Firma anlegen
/admin/companies?edit=N → Firma N bearbeiten
```

### Frontend
```
/companies              → Alle aktiven Firmen (Karten-Grid)
/companies/{id}         → Firmen-Detailseite
```

### Shortcode
```html
[cms_companies]
[cms_companies limit="10" industry="IT"]
[cms_companies partner_only="true"]
```

### PHP-Zugriff

```php
$db = CMS\Database::instance();

// Alle aktiven Firmen
$stmt = $db->prepare("SELECT * FROM {$db->prefix()}companies WHERE status = 'active' ORDER BY name");
$stmt->execute();
$companies = $stmt->fetchAll();

// Firma nach ID
$stmt = $db->prepare("SELECT * FROM {$db->prefix()}companies WHERE id = ?");
$stmt->execute([$id]);
$company = $stmt->fetch();

// Experten einer Firma
$stmt = $db->prepare("
    SELECT e.* FROM {$db->prefix()}experts e
    INNER JOIN {$db->prefix()}company_experts ce ON e.id = ce.expert_id
    WHERE ce.company_id = ? AND ce.is_current = 1
");
$stmt->execute([$company_id]);
$experts = $stmt->fetchAll();
```

---

## Cross-Plugin-Integration

| Plugin | Richtung | Beschreibung |
|--------|----------|--------------|
| `cms-experts` | ← | Experten werden über `cms_company_experts` Firmen zugeordnet |
| `cms-speakers` | ← | Sprecher referenzieren Firmen via `company_id` |
| `cms-events` | ← | Events können Firmen als Veranstalter referenzieren |
| `cms-organigramm` | ← | Firmen dienen als Root-Nodes im Organigramm |

**Guard-Pattern:**
```php
if (CMS\PluginManager::instance()->isPluginActive('cms-companies')) {
    // Firmen-spezifische Logik
}
```

---

## Konfiguration

| Option-Key | Standard | Beschreibung |
|------------|----------|--------------|
| `companies_per_page` | `12` | Einträge pro Seite im Grid |
| `show_partner_badge` | `true` | Partner-Badges anzeigen |
| `default_sort` | `name` | Standard-Sortierung: `name`, `created_at`, `industry` |
| `enable_search` | `true` | Such-Filter im Frontend aktivieren |

Gespeichert in `cms_company_plugin_settings`.
