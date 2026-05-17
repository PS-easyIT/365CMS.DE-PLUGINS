# Changelog – CMS Downloads

## [3.0.1] – 2026-05-17

### Geändert

- Download-Auslieferung setzt jetzt `X-Content-Type-Options: nosniff`, validiert MIME-Headerwerte defensiv und nutzt einen ASCII-Fallback für `Content-Disposition` plus UTF-8-`filename*`.
- Public-Archiv und externe Redirectseite wurden von dekorativen Emoji-Labels bereinigt und konsequenter im Attribut-Kontext escaped.
- Admin-Rest-Styles wurden aus Inline-Attributen in zentrale CSS-Klassen verschoben.

## [3.0.0] – 2026-05-17

### Sicherheitsfixes

- Admin-Redirects nach POST-Aktionen zeigen jetzt nur noch auf die feste interne Dashboard-Route.
- Der verbliebene Open-Redirect-Befund im Downloads-Admin wurde damit beseitigt.
- Download-Dateinamen werden vor dem `Content-Disposition`-Header auf header-sichere Zeichen begrenzt und zusätzlich als `filename*` ausgegeben.
- Upload-Ergebnisse werden per `realpath()` auf das Downloads-Verzeichnis begrenzt, bevor Pfad, URL und Dateigröße übernommen werden.
- Externe Redirect-Ziele werden auf öffentliche HTTP(S)-Hosts ohne Credentials und ohne Sonderports begrenzt; localhost, private/reservierte IPs und interne Domain-Suffixe werden blockiert.
- Statische Inline-Styles der externen Redirectseite wurden in `downloads-public.css` ausgelagert.

## [1.0.0] – 2026-03-14

### Hinzugefügt

- Neues Download-Plugin für öffentliche Dateien
- Kategorien mit Standard-Seed für PowerShell, Webprojekte, Dokumente, eBooks und Archive
- Download-Typen mit Template-/Preset-Logik
- Admin-Bereiche für Dashboard, Downloads, Kategorien und Einstellungen
- Datei-Upload über das 365CMS-Media-System
- Öffentliches Download-Archiv und Kategorie-Routen
- Direkter Download-Endpunkt mit Download-Zähler
