# CMS M365 Landing – Datenbank

## `cms_m365landing_settings`

| Feld | Typ | Zweck |
|---|---|---|
| `id` | INT UNSIGNED | Primärschlüssel |
| `setting_key` | VARCHAR(120) | Eindeutiger Einstellungsschlüssel |
| `setting_value` | TEXT | Wert |
| `updated_at` | TIMESTAMP | Aktualisierungszeitpunkt |

## `cms_m365landing_cards`

| Feld | Typ | Zweck |
|---|---|---|
| `id` | INT UNSIGNED | Primärschlüssel |
| `section` | VARCHAR(40) | `matrix`, `areas` oder `tools` |
| `slug` | VARCHAR(120) | Eindeutiger Card-Slug |
| `title` | VARCHAR(190) | Card-Titel |
| `subtitle` | VARCHAR(255) | Kurzzeile |
| `description` | TEXT | Beschreibung |
| `icon` | VARCHAR(80) | Icon-/Emoji-Fallback |
| `image_url` | VARCHAR(500) | Öffentliches Bild aus Mediathek/URL |
| `image_alt` | VARCHAR(255) | Alt-Text |
| `url` | VARCHAR(500) | Ziel-Link |
| `button_label` | VARCHAR(120) | Button-Text |
| `sort_order` | INT | Sortierung |
| `is_featured` | TINYINT(1) | Hervorhebung |
| `is_active` | TINYINT(1) | Öffentlich sichtbar |
| `created_at` | TIMESTAMP | Erstellzeitpunkt |
| `updated_at` | TIMESTAMP | Aktualisierungszeitpunkt |
