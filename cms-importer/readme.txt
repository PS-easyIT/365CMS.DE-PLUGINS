CMS WordPress Importer
======================

Importiert WordPress-WXR-Export-Dateien (.xml) sowie Rank-Math-Settings (.json)
in die 365 CMS Struktur (cms_posts, cms_pages, cms_site_tables, cms_seo_meta, cms_settings und cms_redirect_rules).

**Version:** 1.6.0
**Autor:** 365 Network
**Mindest-CMS-Version:** 0.26.0

---

## Was wird importiert?

- Beiträge (post) → cms_posts
- Seiten (page) → cms_pages
- TablePress-Tabellen → cms_site_tables
- Benutzerdefinierte Post-Types → cms_posts
- Kategorien → cms_post_categories
- Tags + Relationen → cms_post_tags / cms_post_tag_rel
- SEO-Metadaten (Yoast SEO, Rank Math, SEOPress → Felder + cms_seo_meta)
- Globale Rank-Math-SEO-Defaults → `cms_settings`
- Featured Images via `_thumbnail_id` und Attachment-URL-Auflösung
- Inhaltsbilder per Original-URL mit lokaler Registrierung in `cms_media`
- WordPress-Tabellen-Shortcodes `[table id=...]` → `[site-table id="X"]`
- Rank-Math-Weiterleitungen aus `redirections` → `cms_redirect_rules`

## Was wird NICHT importiert?

- Benutzerkonten (Author-IDs werden per E-Mail aufgelöst, sonst author_id = 0)
- Navigationsmenüs
- Andere Rank-Math-JSON-Bereiche ohne 365CMS-Ziel (z. B. Analytics, Role Manager, App-Secrets)
- Komplexe Plugin-Daten ohne vorhandenes Mapping (werden als unbekannte Metadaten protokolliert)

## Unbekannte Meta-Felder

Alle Meta-Keys die nicht auf ein CMS-Feld gemappt werden können, werden:

1. In der Tabelle `cms_import_meta` gespeichert
2. Als Markdown-Datei unter `reports/` dokumentiert

## Tabellen

- `cms_import_log`   – Ein Eintrag pro Import-Run
- `cms_import_meta`  – Alle nicht gemappten Meta-Felder
- `cms_import_items` – Quell-/Ziel-Mapping für Posts, Pages, Tabellen und Folgeimporte
- `cms_redirect_rules` – Weiterleitungen aus Rank-Math-JSON und dem 365CMS-SEO-Modul
- `cms_settings` – globale SEO-Defaults aus Rank-Math-JSON

## Reports

Die Markdown-Berichte werden unter `plugins/cms-importer/reports/` gespeichert
und sind im Admin-Bereich unter "Import-Protokoll" zum Download verfügbar.

## Bild- und Tabellenmigration

- Attachment-Referenzen aus WordPress werden über `_thumbnail_id` gegen exportierte Attachments aufgelöst.
- Bilder aus Inhalt, SEO-Metadaten und Featured-Image-Referenzen können direkt von der Original-URL geladen werden.
- Importierte Seiten und Beiträge schreiben Bild-URLs auf lokale 365CMS-Dateien um, sofern der Download erfolgreich war.
- TablePress-Exporte werden in native 365CMS-Site-Tabellen überführt.
- Inhalte mit `[table id=...]` werden – sofern eine passende Tabelle importiert wurde – automatisch auf `[site-table id="X"]` umgestellt.

## Dry Run / Vorschau

- Vor jedem echten Import kann eine Vorschau ausgeführt werden.
- Die Vorschau zeigt, welche Elemente importiert oder übersprungen würden.
- Angezeigt werden Zieltyp, Ziel-Slug, Ziel-URL/Hinweis, erkannte Bildkandidaten, Tabellen-Shortcodes und offene Meta-Felder.
- Bei Rank-Math-Redirects werden zusätzlich Vergleichstyp, Status und HTTP-Code angezeigt.
- Bei Rank-Math-SEO-Settings zeigt die Vorschau das Settings-Bundle samt Feldanzahl und betroffenen SEO-Bereichen.
- Es werden keine Datenbank-Schreibzugriffe ausgeführt.

## Changelog

### 1.6.0 (2026-03-14)
- Rank-Math-Settings-JSON importiert jetzt sinnvolle globale SEO-Defaults zusätzlich zu Redirects
- Settings-only-JSONs werden in Vorschau und Import korrekt als gültige Importquelle erkannt
- Rank-Math-OG-Bild-Metadaten aus WXR können jetzt über Attachment-Referenzen aufgelöst werden

### 1.4.0 (2026-03-14)
- Rank-Math-Settings-JSON wird erkannt und importiert Redirects aus `redirections`
- Redirects landen in `cms_redirect_rules`, inklusive Dry-Run-Vorschau und JSON-Dateisupport im Upload/Ordnerscan

### 1.3.0 (2026-03-12)
- Dry-Run-/Preview-Funktion für Uploads und Import-Ordner ergänzt
- Vorschau zeigt Zielobjekte, Skip-Gründe, Bildkandidaten, Tabellen-Shortcodes und offene Meta-Felder vor dem echten Import
- Admin-JavaScript und UI für Vorschau-Rendering erweitert

### 1.2.0 (2026-03-12)
- Import für TablePress-Tabellen nach `cms_site_tables`
- Rich-SEO-Speicherung in `cms_seo_meta`
- `_thumbnail_id`-Auflösung über WordPress-Attachments
- Download und Registrierung von Originalbildern in `cms_media`
- Shortcode-Konvertierung von WordPress-Tabellen zu 365CMS-Site-Tables
- Quell-/Ziel-Mapping für Folgeimporte und sauberes Rewriting bereits importierter Tabellen

### 1.0.0 (2026-02-21)
- Erstveröffentlichung
- WXR-Parser für WordPress-Export-Format
- Import-Logik für Posts und Seiten
- SEO-Meta-Mapping (Yoast, Rank Math, SEOPress)
- Drag & Drop Upload-Interface
- Markdown-Bericht für unbekannte Meta-Felder
