# Changelog

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
