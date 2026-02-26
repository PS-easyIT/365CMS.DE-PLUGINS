CMS WordPress Importer
======================

Importiert WordPress WXR-Export-Dateien (.xml) in die 365 CMS Struktur
(cms_posts und cms_pages).

**Version:** 1.0.0
**Autor:** 365 Network
**Mindest-CMS-Version:** 0.26.0

---

## Was wird importiert?

- Beiträge (post) → cms_posts
- Seiten (page) → cms_pages
- Benutzerdefinierte Post-Types → cms_posts
- Kategorien & Tags (kommagetrennt im tags-Feld)
- SEO-Metadaten (Yoast SEO, Rank Math, SEOPress → meta_title / meta_description)
- Featured-Image-Referenz (WP Attachment-ID als Hinweis, kein automatischer Download)

## Was wird NICHT importiert?

- Medien/Anhänge (Bilddateien werden nicht heruntergeladen)
- Kommentare
- Benutzerkonten (Author-IDs werden per E-Mail aufgelöst, sonst author_id = 0)
- Navigationsmenüs
- Custom CSS, User-Requests u.ä. WP-interne Typen

## Unbekannte Meta-Felder

Alle Meta-Keys die nicht auf ein CMS-Feld gemappt werden können, werden:

1. In der Tabelle `cms_import_meta` gespeichert
2. Als Markdown-Datei unter `reports/` dokumentiert

## Tabellen

- `cms_import_log`  – Ein Eintrag pro Import-Run
- `cms_import_meta` – Alle nicht gemappten Meta-Felder

## Reports

Die Markdown-Berichte werden unter `plugins/cms-importer/reports/` gespeichert
und sind im Admin-Bereich unter "Import-Protokoll" zum Download verfügbar.

## Changelog

### 1.0.0 (2026-02-21)
- Erstveröffentlichung
- WXR-Parser für WordPress-Export-Format
- Import-Logik für Posts und Seiten
- SEO-Meta-Mapping (Yoast, Rank Math, SEOPress)
- Drag & Drop Upload-Interface
- Markdown-Bericht für unbekannte Meta-Felder
