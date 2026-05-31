# Changelog

## 3.0.2 - 2026-05-31

- Admin-Menüeintrag wird in der Core-Sidebar mit `365CMS | ` vorangestellt, damit 365CMS-Plugins gemeinsam sortiert werden.

## 3.0.1 - 2026-05-18

- PHP-Anforderung auf 8.4 angehoben und Plugin-Version auf `3.0.1` aktualisiert.
- Admin-CSRF-Prüfung nutzt konsequent String-Casts und Redirects laufen mit HTTP 303.
- Öffentliches Subscribe-Rate-Limit schreibt atomar mit Datei-Lock und Größenlimit.
- Public-Inputs wurden um Längenbegrenzungen, Autocomplete und klarere Labels ergänzt.
- Newsletter-Template-HTML entfernt Event-/Style-Attribute und blockiert unsichere `href`-Schemata.
- Public- und Admin-UI optisch beruhigt: keine Emoji-Deko, weniger Insellook, PHINIT-nahe Tokens im Public-CSS.

## 3.0.0 - 2026-05-17

- Kompatibilität auf 365CMS 3.0.0 angehoben.
- Öffentliche Newsletter-Anmeldung auf CSRF-fail-closed umgestellt, wenn `CMS\\Security` nicht verfügbar ist.
- Public-Subscribe gegen Formular-Spam mit Honeypot und einfachem IP-basiertem Rate-Limit gehärtet.
- Abmelde-Token vor Verarbeitung normalisiert und begrenzt.
- Schwachen Hash-Fallback bei Token-Erzeugung durch SHA-256 ersetzt.
- Statische Inline-Styles aus dem Public-Template in `newsletter-public.css` verschoben.

## [Unreleased] - 2026-04-04

- Admin-Redirects zeigen nach POST-Aktionen jetzt ausschließlich auf feste interne Dashboard-Ziele.
- Der verbliebene Open-Redirect-Befund in `admin/class-admin-pages.php` ist damit behoben.

## 1.0.0 - 2026-03-14

- Erstes vollständiges Plugin-Setup für `cms-newsletter`
- Admin-Dashboard, Subscriber-, Template-, Kampagnen- und Einstellungsseiten ergänzt
- Datenbankstruktur für Subscriber, Templates, Kampagnen, Versand-Logs und Settings eingeführt
- Öffentliche Newsletter-Anmeldeseite und Abmelde-Route ergänzt
