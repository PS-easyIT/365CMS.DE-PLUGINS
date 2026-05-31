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

Diese Tabelle steuert die sichtbaren Bereiche der 365NETWORK-Hub-Landingpage typisiert über eigene Bereichs-Tabs im Adminbereich.

| Feld | Typ | Beschreibung |
|---|---|---|
| `id` | `INT UNSIGNED` | Primärschlüssel |
| `setting_key` | `VARCHAR(100)` | Eindeutiger Hub-Setting-Key mit Präfix `hub_` |
| `setting_val` | `TEXT` | Wert als String |
| `setting_type` | `ENUM` | `text`, `textarea`, `bool`, `int`, `color`, `select` |
| `section` | `VARCHAR(50)` | Bereich: `order`, `featured`, `hero`, `partnerband`, `stats`, `band`, `areas`, `next-events`, `spotlight`, `partner-columns`, `toolbox` |
| `label` | `VARCHAR(150)` | Admin-Label |
| `sort_order` | `INT` | Sortierung innerhalb der Section |
| `updated_at` | `DATETIME` | Änderungsdatum |

### Hub-Sections

| Section | Zweck |
|---|---|
| `order` | Sortierbare Reihenfolge der Public-Bereiche |
| `featured` | Sichtbarkeit, Darstellung, Label, Titel, Text, Button, Bild-URL, Breite, Farben und Rundung |
| `hero` | Sichtbarkeit, Label, H1, optionales Hero-Bild statt sichtbarer H1, Untertitel, CTA-Buttons, Ausrichtung, Farben und Rundung |
| `partnerband` | Sichtbarkeit, Label, Logo-/Partnerlimit und Farben des dunklen Partnerbands direkt unter dem Theme-Header |
| `stats` | Sichtbarkeit, Labels, Ziel-URLs, Layout, Farben und Rundung der Zähler-Kacheln |
| `band` | Sichtbarkeit des Teaser-Bands, Event-/Suchtexte, Such-URL, Suchparameter, Layout, Farben und Rundung |
| `areas` | Sichtbarkeit, Titel, Texte, Icons, URLs, Rasterlayout, Kartenstil, Farben und Rundung der Direkteinstieg-Kacheln |
| `next-events` | Sichtbarkeit, Titel, Beschreibung, Linkziel, Limit und Kartenfarben der nächsten Events |
| `spotlight` | Sichtbarkeit, Titel, Beschreibung, Limit, optionaler Autoplay-Schalter und Rotator-Farben |
| `partner-columns` | Sichtbarkeit, Titel, Alle-Links, Limits, Badge-Label und Farben für Companies-/Experts-Spalten |
| `toolbox` | Sichtbarkeit, Überschrift, Alle-Tools-Link, Anzeige-Limit, Layout, Farben und Rundung |

Ab `1.0.22` sind alle Public-Rundungen bewusst auf maximal `2px` begrenzt. Bestehende höhere Radiuswerte in älteren Settings werden beim Speichern und bei der Public-CSS-Ausgabe gekappt.

### Hub-Reihenfolge

Seit `1.0.18` liegen die Sortierwerte ebenfalls in `cms_network_hub_settings`:

- `hub_section_order`: kommagetrennte Reihenfolge der Public-Bereiche. Ab `1.0.21` ist der headerlose Preview-Default `partnerband,hero,areas,next-events,spotlight,partner-columns,toolbox,featured,stats,band`. Öffentlich wird `partnerband` immer direkt unter dem Theme-Header ausgegeben, sofern es aktiviert ist.
- `hub_area_card_order`: kommagetrennte Reihenfolge der Direkteinstieg-Karten. Ab `1.0.24` ist der Default `events,speakers,experts,companies`, damit öffentlich die Reihen `Events/Speaker` und `Experts/Firmen` entstehen; ältere unberührte Defaults werden migriert.
- `hub_hero_image_url`: optionale Bild-URL für den Hero; erlaubt sind HTTP(S)-URLs sowie interne Medienpfade (`/uploads/...`, `/media-file?...`).
- `hub_hero_image_mode`: steuert, ob das Hero-Bild die sichtbare H1 ersetzt (`replace-title`) oder zusätzlich mit Titel/Suche angezeigt wird (`with-title`).
- `hub_hero_image_width` / `hub_hero_image_height`: konfigurierbare Hero-Bildmaße in Pixeln, Default `250 × 200`.
- `hub_hero_layout`: ab `1.0.25` `center`, `image-right` oder `image-left` für zentrierte Ausgabe, Titel/Suche links mit Bild rechts oder Bild links mit Titel/Suche rechts.

### Preview-Hub-Settings ab `1.0.21`

Zusätzlich zu den älteren Hub-Bereichen wurden für das Preview-Layout folgende Key-Gruppen ergänzt:

- `hub_partnerband_*`: Partnerband-Sichtbarkeit, Label, Limit und Farben.
- `hub_next_events_*`: Nächste-Events-Überschrift, Beschreibung, Alle-Events-Link, Limit und Kartenfarben.
- `hub_spotlight_*`: Spotlight-Überschrift, Beschreibung, Limit, optionales Autoplay und Farben.
- `hub_partner_*`: Companies-/Experts-Spalten, Limits, Alle-Links, Badge-Label und Farben.

## Externe Tabellen

Nur lesend und defensiv genutzt:

- `cms_events`
- `cms_speakers`
- `cms_companies`
- `cms_experts`
- `cms_m365toolbox_links` bzw. `m365toolbox_links` für optionale Legacy-Toolbox-Hub-Links
- alternativ die `cms-m365tools`-Tool-Registry ohne zusätzliche 365NETWORK-Tabelle
