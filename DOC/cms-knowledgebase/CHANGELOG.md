# Changelog

## [3.0.4] - 2026-05-31

- Admin-Menüeintrag wird in der Core-Sidebar kurz als `365CMS | KB` angezeigt, damit 365CMS-Plugins gemeinsam sortiert werden.

## [3.0.1] - 2026-05-17

- Security/Performance-Pass für 365CMS v3.x.x und PHP 8.4.
- Öffentliche Filter (`q`, `category`, `page`, `per_page`) werden gekappt und normalisiert.
- Richtext-Speicherung und Rendering entfernen unsichere Attribute, Event-Handler und gefährliche Link-Ziele.
- XML-Sitemap, Admin-CSRF und interne Redirects wurden gehärtet.

## 1.0.0 - 2026-03-21

- Initiale Version von `cms-knowledgebase`
- Admin-CRUD für Einträge und Einstellungen
- Öffentliche KB-Seiten unter `/kb`
- Auto-Linking mit Tooltip-Vorschau
- Output-Buffer-Fallback für Themes ohne `content_render`
