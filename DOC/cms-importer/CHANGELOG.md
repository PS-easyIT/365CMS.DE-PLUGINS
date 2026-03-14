# CMS WordPress Importer – Changelog

## [1.6.0] – 2026-03-14

### Hinzugefügt

- **Rank-Math-SEO-Defaults:** Importiert sinnvolle globale SEO-Standards aus Rank-Math-JSON nach `cms_settings`, z. B. Homepage-Meta, Robots-Defaults, Social-Defaults, Breadcrumbs, Schema- und Sitemap-Optionen
- **Settings-only-JSON:** Vorschau und Import akzeptieren jetzt auch Rank-Math-Dateien ohne Redirects, sofern importierbare SEO-Settings enthalten sind
- **Preview-Details:** Dry Run zeigt SEO-Settings-Bundles samt Feldanzahl und betroffenen SEO-Bereichen

### Geändert

- **Rank-Math-Metadaten:** `rank_math_og_content_image` kann jetzt über serialisierte Attachment-Referenzen oder direkte URLs als OG-Bild aufgelöst werden

## [1.4.0] – 2026-03-14

### Hinzugefügt

- **Rank-Math-JSON-Import:** Erkennt Rank-Math-Settings-Dateien (`.json`) und importiert daraus ausschließlich die Einträge aus `redirections`
- **Redirect-Zielsystem:** Schreibt exakte Redirect-Regeln nach `cms_redirect_rules` inklusive Status, Ziel-URL, HTTP-Code und Hit-Zähler
- **Dry Run für Redirects:** Vorschau zeigt Redirect-Ziel, Vergleichstyp, Status und Update/Create-Hinweise vor dem echten Import
- **Dateiquellen erweitert:** Upload, Ordner-Scan und Plugin-Importquellen akzeptieren jetzt XML und JSON

### Hinweise

- Aktuell werden nur Rank-Math-Quellregeln mit Vergleichstyp `exact` übernommen; komplexere Vergleichstypen werden bewusst übersprungen

## [1.3.0] – 2026-03-12

### Hinzugefügt

- **Dry Run / Vorschau:** Simuliert Upload- und Ordnerimporte ohne Schreibzugriff
- **Vorschau-Panel:** Zeigt Zieltyp, Slug, Zielhinweis/-URL, Skip-Gründe und Detailinfos pro Element
- **Preview-Analyse:** Bildkandidaten, unbekannte Meta-Felder und Tabellen-Shortcode-Auflösung direkt vor dem Import sichtbar
- **Admin-Workflow:** Eigene Vorschau-Aktionen für Uploads und XML-Dateien aus bekannten Import-Quellen

## [1.2.0] – 2026-03-12

### Hinzugefügt

- **TablePress-Migration:** `tablepress_table` → `cms_site_tables`
- **SEO-Persistenz:** Strukturierte SEO-Daten zusätzlich in `cms_seo_meta`
- **Bild-Import:** Download von Originalbildern, lokale Registrierung und URL-Umschreibung
- **Shortcode-Mapping:** WordPress-Tabellen-Shortcodes werden auf `site-table` umgestellt
- **Import-Mapping:** Persistentes Quell-/Ziel-Mapping in `cms_import_items`

## [1.0.0] – 2026-02-21

### Hinzugefügt

- **WXR-Parser:** WordPress XML-Export-Format (WXR) via DOM/SimpleXML
- **Import-Logik:** Posts (`post`) → `cms_posts`, Seiten (`page`) → `cms_pages`
- **Custom Post Types:** Beliebige CPTs werden als Posts importiert
- **Taxonomien:** Kategorien & Tags als kommagetrennte Strings im `tags`-Feld
- **SEO-Mapping:** Automatisches Mapping von Yoast SEO, Rank Math und SEOPress auf `meta_title`/`meta_description`
- **Featured Image:** WP Attachment-ID als Hinweis (kein automatischer Bild-Download)
- **Author-Auflösung:** Autor-Zuordnung per E-Mail-Adresse; fallback `author_id = 0`
- **Datenbank:** `cms_import_log` und `cms_import_meta` Tabellen
- **Meta-Report:** Markdown-Bericht für alle nicht gemappten Meta-Keys automatisch generiert
- **Admin-Oberfläche:** Drag & Drop Upload-Interface (`admin/page.php`)
- **Import-Protokoll:** Vollständige Log-Übersicht mit Download-Link für Meta-Reports (`admin/log.php`)
- **Admin-Menü:** Integration via `cms_admin_menu`-Hook
- **Sicherheit:** Nur Admins, CSRF-Schutz, Dateityp-Validierung (nur `.xml`)
