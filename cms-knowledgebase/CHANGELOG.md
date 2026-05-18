# Changelog

## [3.0.1] - 2026-05-17

- Öffentliche Such-, Kategorie-, Pagination- und Slug-Parameter werden begrenzt normalisiert.
- Admin-POSTs prüfen CSRF-Tokens fail-closed mit String-Cast und nutzen 303-Redirects.
- Sitemap-XML sendet zusätzlich `X-Content-Type-Options: nosniff`.
- Knowledgebase-Richtext entfernt beim Speichern und Rendern unsichere Attribute, Event-Handler und nicht vertrauenswürdige Link-Ziele.
- Öffentliche Empty-State-Dekoration wurde ruhiger und performancefreundlich ohne Emoji-UI gehalten.
- Plugin-Metadaten auf PHP 8.4 und Release `3.0.1` aktualisiert.

## [3.0.0] - 2026-05-17

- Admin-Redirects nach Schreibaktionen wurden auf feste interne Dashboard-Ziele begrenzt.
- Der Open-Redirect-Befund in `src/Admin/Pages.php` ist damit behoben.
- Öffentliche Tooltip-Elemente werden ohne statisches `innerHTML` per DOM-API aufgebaut.
- Auto-Link-Ausgaben behalten `noopener noreferrer`, wenn Links in neuen Tabs geöffnet werden.

## 1.0.0 - 2026-03-21

- Erstes Release von CMS Knowledgebase
- Admin-Dashboard, Eintragsverwaltung und Einstellungen
- Öffentliche Knowledgebase-Routen `/kb` und `/kb/{slug}`
- Auto-Linking mit Tooltip-Vorschau
- PSR-3-kompatibles Logging via CMS-Logger-Adapter
