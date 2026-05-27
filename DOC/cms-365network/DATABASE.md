# CMS 365NETWORK – Datenbank

## Tabelle `cms_network365_settings`

> Präfix abhängig von der Installation, z. B. `cms_`.

| Feld | Typ | Beschreibung |
|---|---|---|
| `id` | `INT UNSIGNED` | Primärschlüssel |
| `setting_key` | `VARCHAR(191)` | Eindeutiger Einstellungsschlüssel |
| `setting_value` | `LONGTEXT` | Wert als String |
| `updated_at` | `TIMESTAMP` | Änderungsdatum |

## Wichtige Settings

| Key | Zweck |
|---|---|
| `landing_enabled` | Landingpage aktiv/inaktiv |
| `hub_domains` | Eine oder mehrere Zusatzdomains |
| `route_slug` | Interne Vorschau-Route |
| `landing_title`, `landing_subtitle` | Hero-Inhalte |
| `featured_*` | Featured Card |
| `layout_variant` | `grid-2x2`, `grid-4x1`, `auto` |
| `content_width`, `card_radius`, `section_gap` | Layoutparameter |
| `*_color` | Designfarben |
| `show_sidebar`, `preview_placement` | Sidebar-/Preview-Steuerung |
| `sidebar_events_count`, `random_*_count` | Anzahl dynamischer Vorschaukarten |
| `events_card_*`, `speakers_card_*`, `companies_card_*`, `experts_card_*` | Bereichskarten |
| `analytics_enabled` | Analyse-Code aktiv/inaktiv |
| `analytics_position` | Ausgabe in `head` oder `body_end` |
| `analytics_code` | Matomo-/SEO-Analyse-Snippet für die Plugin-Public-Site |

## Externe Tabellen

Nur lesend und defensiv genutzt:

- `cms_events`
- `cms_speakers`
- `cms_companies`
- `cms_experts`
