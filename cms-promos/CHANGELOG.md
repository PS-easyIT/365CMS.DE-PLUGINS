# Changelog

## 3.0.1 - 2026-05-18

- CSRF-Token-Prüfung und Admin-Redirects für 365CMS v3.x.x gehärtet.
- Ziel-URLs, Richtext-Inhalte und Slugs strenger normalisiert und validiert.
- Public-Archiv semantisch modernisiert, Inline-Styles entfernt und Escaping vereinheitlicht.
- Admin-Oberfläche ruhiger gestaltet und Emoji-Deko durch klare Textlabels ersetzt.

## 3.0.0 - 2026-05-17

- Plugin-Version und Update-Metadaten auf 365CMS 3.0.0 aktualisiert.
- Admin-Redirect-Härtung aus dem bisherigen Unreleased-Zweig als stabiler 3.0.0-Stand dokumentiert.

## [Unreleased] - 2026-04-04

- Admin-Redirects nach schreibenden Aktionen wurden auf feste interne Dashboard-Routen begrenzt.
- Damit ist der Open-Redirect-Befund im Promo-Admin geschlossen.

## 1.1.0 - 2026-03-14

- Automatische Promo-Ausspielung über Theme-Hooks wie `after_header`, `home_content` und `before_footer` ergänzt
- Platzierungen um `theme_hook` und `hook_priority` erweitert
- Admin-UI zeigt Hook-Zuordnungen jetzt direkt in Platzierungen und Promo-Listen an

## 1.0.0 - 2026-03-14

- Erstes vollständiges Plugin-Setup für `cms-promos`
- Promo-, Platzierungs- und Einstellungsseiten im Admin ergänzt
- Datenbankstruktur für Promos, Platzierungen und Settings eingeführt
- Öffentliche Promo-Übersicht und Klick-Redirect-Tracking ergänzt
