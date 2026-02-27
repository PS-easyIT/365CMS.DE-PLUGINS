# CMS WordPress Importer – Dokumentation

**Plugin:** `cms-importer`  
**Version:** 1.0.0  
**Namespace:** `CMS_Importer`  
**Mindest-CMS-Version:** 365CMS 0.26.0+  
**PHP:** 8.1+

---

## Übersicht

Das **CMS WordPress Importer**-Plugin importiert WordPress-Export-Dateien (WXR-Format, `.xml`) in die 365CMS-Datenbankstruktur. Es verarbeitet Posts, Seiten, Taxonomien und SEO-Metadaten und protokolliert alle nicht gemappten Felder für spätere Analyse.

### Kernfunktionen

| Bereich | Funktion |
|---------|----------|
| **WXR-Parser** | WordPress XML-Export (WXR) verarbeiten |
| **Posts-Import** | `post` → `cms_posts` |
| **Seiten-Import** | `page` → `cms_pages` |
| **Custom Post Types** | Beliebige CPTs → `cms_posts` |
| **SEO-Mapping** | Yoast SEO, Rank Math, SEOPress → `meta_title` / `meta_description` |
| **Taxonomien** | Kategorien & Tags (kommagetrennt) |
| **Import-Log** | Vollständiges Protokoll in `cms_import_log` |
| **Meta-Report** | Alle ungenutzten Meta-Keys als Markdown-Bericht |
| **Admin-Oberfläche** | Drag & Drop Upload unter `/admin/importer` |

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
| [DATABASE.md](DATABASE.md) | Import-Tabellen |
| [HOOKS.md](HOOKS.md) | Actions & Filter |
| [MAPPING.md](MAPPING.md) | WXR → CMS Feld-Mapping |
| [CHANGELOG.md](CHANGELOG.md) | Versionshistorie |

---

## Was wird importiert?

| WXR-Element | CMS-Ziel | Hinweis |
|-------------|----------|---------|
| `post` (post_type) | `cms_posts` | Standard-Beiträge |
| `page` (post_type) | `cms_pages` | Standard-Seiten |
| Andere CPTs | `cms_posts` | Mit `post_type`-Feld |
| `category`, `tag` | `cms_posts.tags` | Kommagetrennt |
| Yoast `_yoast_wpseo_title` | `meta_title` | SEO-Mapping |
| Yoast `_yoast_wpseo_metadesc` | `meta_description` | SEO-Mapping |
| Rank Math `rank_math_title` | `meta_title` | SEO-Mapping |
| SEOPress `_seopress_titles_title` | `meta_title` | SEO-Mapping |

## Was wird NICHT importiert?

- Medien/Bilddateien (keine automatischen Downloads)
- Kommentare
- Benutzerkonten (Author-IDs werden per E-Mail aufgelöst)
- Navigationsmenüs
- WP-interne Meta-Typen (Custom CSS, User-Requests, …)

---

## Verwendung

### Admin-Interface
1. Admin-Backend → **Tools → WordPress Importer**
2. WXR-Datei per Drag & Drop hochladen
3. Import starten
4. Import-Protokoll ansehen
5. Meta-Report herunterladen (ungekannte Felder)

---

## Unbekannte Meta-Felder

Alle Meta-Keys die nicht auf ein CMS-Feld gemappt werden:

1. Werden in `cms_import_meta` gespeichert
2. Als Markdown-Report unter `plugins/cms-importer/reports/` generiert
3. Im Admin abrufbar unter "Import-Protokoll → Meta-Report"

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
