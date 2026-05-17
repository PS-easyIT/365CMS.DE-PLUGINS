# Changelog

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
