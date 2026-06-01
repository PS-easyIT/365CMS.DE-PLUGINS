# Changelog – CMS M365 Copilot

## 1.0.1 – 2026-06-01

- Styling-Auslieferung für Adminseite gehärtet: CSS wird seitengebunden über den globalen `head`-Hook geladen.
- Public-Rendering ergänzt um CSS-Fallback-Link direkt im Template-Renderpfad, falls Theme-Hooks kein Stylesheet ausgeben.
- Public-Request-Erkennung für Style-Ausgabe auf Locale-Pfade erweitert (z. B. `/de/...`, `/en/...`, `/{lang}-{REGION}/...`).

## 1.0.0 – 2026-06-01

- Initiale Plugin-Basis mit Bootstrap, Hook-Registrierung und Admin-Contract.
- Gruppierte Admin-Einstellungsseite mit allen Bereichen (Header, Dienstleistungsband, Cards, Beiträge, Layout/Spacing).
- Persistente Settings-Storage via `cms_m365copilot_settings` mit sinnvollen Defaults.
- Public-Landingpage mit 4 Sektionen und PHINIT-orientiertem Styling.
- Beitragsabfrage via bestehender CMS-Posts/Kategorien inkl. `cms_post_publication_where`.
