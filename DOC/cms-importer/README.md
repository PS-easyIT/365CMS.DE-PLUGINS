# CMS WordPress Importer – Dokumentation

**Plugin:** `cms-importer`  
**Version:** 1.6.0  
**Namespace:** `CMS_Importer`  
**Mindest-CMS-Version:** 365CMS 0.26.0+  
**PHP:** 8.1+

---

## Übersicht

Das **CMS WordPress Importer**-Plugin importiert WordPress-Export-Dateien (WXR-Format, `.xml`) sowie Rank-Math-Settings-Dateien (`.json`) in die 365CMS-Datenbankstruktur. Es verarbeitet Beiträge, Seiten, TablePress-Tabellen, Taxonomien, SEO-Metadaten, Featured Images, Inhaltsbilder sowie sinnvolle Rank-Math-SEO-Defaults und Weiterleitungen und protokolliert alle nicht gemappten Felder für spätere Analyse.

### Kernfunktionen

| Bereich | Funktion |
|---------|----------|
| **WXR-Parser** | WordPress XML-Export (WXR) verarbeiten |
| **Posts-Import** | `post` → `cms_posts` |
| **Seiten-Import** | `page` → `cms_pages` |
| **Tabellen-Import** | `tablepress_table` → `cms_site_tables` |
| **Custom Post Types** | Beliebige CPTs → `cms_posts` |
| **SEO-Mapping** | Yoast SEO, Rank Math, SEOPress → Felder + `cms_seo_meta` |
| **SEO-Defaults** | Rank Math JSON → globale `cms_settings`-SEO-Schlüssel |
| **Taxonomien** | Kategorien, Tags und Tag-Relationen |
| **Bilder** | Download von Original-URLs inkl. URL-Umschreibung im Content |
| **Redirects** | Rank Math `redirections` → `cms_redirect_rules` |
| **Shortcode-Migration** | `[table id=...]` → `[site-table id="X"]` |
| **Import-Log** | Vollständiges Protokoll in `cms_import_log` |
| **Meta-Report** | Alle ungenutzten Meta-Keys als Markdown-Bericht |
| **Import-Mapping** | Persistentes Quell-/Ziel-Mapping für Folgeimporte |
| **Dry Run** | Vorschau ohne Schreibzugriff mit Ziel-/Skip-Analyse |
| **Admin-Oberfläche** | Upload + Import aus `uploads/import/`, `wp_import_files/` oder `wp_import/` |

---

## Dateistruktur

```
cms-importer/
├── cms-importer.php
├── readme.txt
├── update.json
├── admin/
│   ├── page.php          # Import-Oberfläche (Drag & Drop)
│   └── log.php           # Import-Protokoll-Ansicht
├── includes/
│   ├── class-xml-parser.php   # WXR-Parser (DOM/SimpleXML)
│   ├── class-importer.php     # Import-Logik & Mapping
│   └── class-admin.php        # Admin-Hook-Registrierung
├── assets/
│   ├── css/
│   └── js/
└── reports/
    └── EXAMPLE_meta-report.md
```

---

## Weitere Dokumente

| Dokument | Inhalt |
|----------|--------|
| [DATABASE.md](DATABASE.md) | Import-Tabellen und Zieltabellen |
| [HOOKS.md](HOOKS.md) | Registrierte Admin-/AJAX-Hooks |
| [CHANGELOG.md](CHANGELOG.md) | Versionshistorie |

---

## Was wird importiert?

| WXR-Element | CMS-Ziel | Hinweis |
|-------------|----------|---------|
| `post` (post_type) | `cms_posts` | Standard-Beiträge |
| `page` (post_type) | `cms_pages` | Standard-Seiten |
| `tablepress_table` | `cms_site_tables` | Native 365CMS-Site-Tabellen |
| Andere CPTs | `cms_posts` | Mit `post_type`-Feld |
| `category` | `cms_post_categories` | Erste Kategorie als `category_id` |
| `tag` | `cms_post_tags` / `cms_post_tag_rel` | Native Tag-Relationen |
| Yoast / Rank Math / SEOPress | `meta_title`, `meta_description`, `cms_seo_meta` | SEO-Mapping |
| `_thumbnail_id` + Attachments | `featured_image`, `cms_media` | Attachment-Auflösung + Download |
| `[table id=...]` | `[site-table id="X"]` | Mapping über `cms_import_items` |
| Rank Math SEO-Defaults (JSON) | `cms_settings` | Nur sinnvoll mapbare globale SEO-Optionen |
| Rank Math `redirections` (JSON) | `cms_redirect_rules` | Nur exakte Quellpfade werden übernommen |

## Was wird NICHT importiert?

- Kommentare
- Benutzerkonten (Author-IDs werden per E-Mail aufgelöst)
- Navigationsmenüs
- WP-interne Meta-Typen (Custom CSS, User-Requests, …)
- Plugin-/Theme-Sonderdaten ohne festes Mapping (werden im Meta-Report dokumentiert)
- Rank-Math-Bereiche ohne 365CMS-Ziel wie Analytics, Role-Manager oder App-Secrets

---

## Verwendung

### Admin-Interface
1. Admin-Backend → **Plugins → WP Importer**
2. WXR-Datei hochladen oder vorhandene XML-/JSON-Datei aus einer Import-Quelle auswählen
3. Optional Bilddownload und Tabellen-Shortcode-Konvertierung aktiv lassen
4. Optional zuerst **Dry Run** ausführen und Zielobjekte prüfen
5. Import starten
6. Import-Protokoll und ggf. Meta-Bericht herunterladen

### Dry Run

Die Vorschau verwendet dieselbe Ziel- und Duplikatlogik wie der echte Import, schreibt aber nichts in die Datenbank. Sichtbar sind dabei unter anderem:

- Zieltyp, Ziel-Slug und Ziel-URL bzw. Ziel-Hinweis
- Import-/Skip-Entscheidung pro Element
- erkannte Bildkandidaten und Featured-Image-Referenzen
- auflösbare WordPress-Tabellen-Shortcodes
- Anzahl unbekannter Meta-Felder pro Eintrag
- SEO-Settings-Bundles inklusive Anzahl und Bezeichner der Ziel-Settings

---

## Unbekannte Meta-Felder

Alle Meta-Keys die nicht auf ein CMS-Feld gemappt werden:

1. Werden in `cms_import_meta` gespeichert
2. Als Markdown-Report unter `plugins/cms-importer/reports/` generiert
3. Im Admin abrufbar unter "Import-Protokoll → Meta-Report"

---

## Hinweise zur Migration

- TablePress-Tabellen sollten idealerweise vor oder zusammen mit Seiten importiert werden, damit Shortcodes direkt sauber umgeschrieben werden können.
- Bereits importierte Tabellen werden über `cms_import_items` wiedergefunden, sodass Folgeimporte auf bestehende Site-Table-IDs auflösen können.
- Bild-URLs bleiben erhalten, wenn ein Download fehlschlägt; erfolgreiche Downloads werden auf lokale 365CMS-Dateien umgebogen.
- Rank-Math-Weiterleitungen werden derzeit nur für Vergleichstyp `exact` importiert, weil 365CMS intern mit exakten Quellpfaden arbeitet.
- Rank-Math-JSON importiert nur Settings mit echtem 365CMS-Ziel, z. B. Homepage-Meta, Robots-Defaults, Social-Defaults, Breadcrumb-/Sitemap-Optionen und Schema-Grundeinstellungen.

### Report-Format

```markdown
# Import Meta-Report
**Datei:** wordpress-export.xml  
**Datum:** 2026-02-21

## Unbekannte Meta-Keys (42)

| Meta-Key | Anzahl | Beispielwert |
|----------|--------|--------------|
| `_custom_field_1` | 15 | "Wert 1" |
| `_edit_lock` | 30 | "1705000000:1" |
```
