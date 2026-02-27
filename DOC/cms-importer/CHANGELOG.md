# CMS WordPress Importer – Changelog

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
