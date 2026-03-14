# CMS Promos

`cms-promos` ergänzt 365CMS um ein Promo-Management für Hero-Banner, CTA-Elemente, Teaserflächen und klickbare Kampagnenboxen.

## Kernbereiche

- **Dashboard**: Überblick zu Reichweite, Klicks und Platzierungen
- **Promos**: Verwaltung einzelner Promo-Elemente
- **Platzierungen**: Definition der verfügbaren Promo-Slots inklusive automatischer Theme-Hook-Zuordnung
- **Einstellungen**: Standardtexte und Verhalten für neue Einträge

## Öffentliche Oberfläche

- `GET /promos`
- `GET /promos/placement/:slug`
- `GET /promo/click/:slug`

## Automatische Einbindung

Platzierungen können direkt an Theme-Hooks gekoppelt werden. Unterstützt werden aktuell:

- `body_start`
- `after_header`
- `home_content`
- `before_footer`

Aktive Promos einer solchen Platzierung werden automatisch im jeweiligen Hook gerendert und zählen dabei Impressions direkt an der Ausspielungsstelle.
