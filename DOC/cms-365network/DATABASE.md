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

## Tabelle `cms_network_hub_settings`

> Ab `1.0.12`. Präfix abhängig von der Installation, z. B. `cms_`.

Diese Tabelle steuert die sichtbaren Bereiche der 365NETWORK-Hub-Landingpage typisiert über den Admin-Tab `Hub`.

| Feld | Typ | Beschreibung |
|---|---|---|
| `id` | `INT UNSIGNED` | Primärschlüssel |
| `setting_key` | `VARCHAR(100)` | Eindeutiger Hub-Setting-Key mit Präfix `hub_` |
| `setting_val` | `TEXT` | Wert als String |
| `setting_type` | `ENUM` | `text`, `textarea`, `bool`, `int`, `color`, `select` |
| `section` | `VARCHAR(50)` | Bereich: `hero`, `stats`, `band`, `areas`, `toolbox` |
| `label` | `VARCHAR(150)` | Admin-Label |
| `sort_order` | `INT` | Sortierung innerhalb der Section |
| `updated_at` | `DATETIME` | Änderungsdatum |

### Hub-Sections

| Section | Zweck |
|---|---|
| `featured` | Sichtbarkeit, Darstellung, Label, Titel, Text, Button und optionale Bild-URL |
| `hero` | Sichtbarkeit, Label, H1, Untertitel und CTA-Buttons |
| `stats` | Sichtbarkeit und Ziel-URLs der Zähler-Kacheln |
| `band` | Sichtbarkeit des Teaser-Bands, Event-/Suchkachel, Such-URL und Suchparameter |
| `areas` | Sichtbarkeit, Titel, Texte, Icons und URLs der vier Direkteinstieg-Kacheln |
| `toolbox` | Sichtbarkeit, Überschrift, Alle-Tools-Link und Anzeige-Limit |

## Externe Tabellen

Nur lesend und defensiv genutzt:

- `cms_events`
- `cms_speakers`
- `cms_companies`
- `cms_experts`
- `cms_m365toolbox_links` bzw. `m365toolbox_links` für optionale Toolbox-Hub-Links
