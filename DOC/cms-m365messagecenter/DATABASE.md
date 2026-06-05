# CMS M365 Message Center – Datenbank

## `{prefix}m365messagecenter_settings`

| Feld | Typ | Beschreibung |
|---|---|---|
| `id` | INT UNSIGNED | Primärschlüssel |
| `setting_key` | VARCHAR(120) | Eindeutiger Einstellungsschlüssel |
| `setting_value` | TEXT | Einstellungswert |
| `updated_at` | TIMESTAMP | Aktualisierung |

## `{prefix}m365messagecenter_messages`

| Feld | Typ | Beschreibung |
|---|---|---|
| `id` | INT UNSIGNED | Primärschlüssel |
| `graph_id` | VARCHAR(120) | ID aus Microsoft Graph, eindeutig |
| `title` | VARCHAR(500) | Meldungstitel |
| `category` | VARCHAR(120) | Message-Center-Kategorie |
| `severity` | VARCHAR(80) | Schweregrad, falls vorhanden |
| `services_json` | TEXT | Betroffene Services als JSON-Array |
| `tags_json` | TEXT | Tags als JSON-Array |
| `is_major_change` | TINYINT(1) | Major-Change-Flag |
| `action_required_at` | DATETIME NULL | Handlungsfrist |
| `start_at` | DATETIME NULL | Startzeitpunkt |
| `end_at` | DATETIME NULL | Endzeitpunkt |
| `last_modified_at` | DATETIME NULL | Letzte Änderung laut Graph |
| `body_excerpt` | TEXT | Gekürzter Klartextauszug |
| `body_content` | MEDIUMTEXT | Vollständiger Klartext-Body für Detailseiten |
| `external_url` | VARCHAR(600) | Optionaler externer Link |
| `raw_updated_at` | DATETIME NULL | Zeitpunkt der Cache-Aktualisierung |
| `created_at` | TIMESTAMP | Erstellung |
| `updated_at` | TIMESTAMP | Aktualisierung |

## Indizes

- `uq_m365messagecenter_graph_id` auf `graph_id`
- `idx_m365messagecenter_modified` auf `last_modified_at`
- `idx_m365messagecenter_action` auf `action_required_at`
- `idx_m365messagecenter_category` auf `category`
- `idx_m365messagecenter_major` auf `is_major_change`
