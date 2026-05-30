# Datenbank – CMS M365 Linkcollection

## `{prefix}m365linkcollection_categories`

| Feld | Typ | Zweck |
|---|---|---|
| `id` | INT UNSIGNED | Primärschlüssel |
| `name` | VARCHAR(190) | Anzeigename |
| `slug` | VARCHAR(190) | URL-/Filter-Key |
| `description` | TEXT | Beschreibung |
| `sort_order` | INT UNSIGNED | Sortierung |
| `is_active` | TINYINT(1) | Public sichtbar |
| `created_at` / `updated_at` | TIMESTAMP | Zeitstempel |

## `{prefix}m365linkcollection_links`

| Feld | Typ | Zweck |
|---|---|---|
| `id` | INT UNSIGNED | Primärschlüssel |
| `category_id` | INT UNSIGNED | Kategorie-Referenz |
| `title` | VARCHAR(190) | Linktitel |
| `subtitle` | VARCHAR(190) | Schwerpunkt/Untertitel |
| `url` | VARCHAR(500) | Externe Ziel-URL |
| `description` | TEXT | Beschreibung |
| `image_url` | VARCHAR(500) | Bild-URL |
| `image_alt` | VARCHAR(190) | Alt-Text |
| `tags` | VARCHAR(500) | Such-/Admin-Tags |
| `company_id` | INT UNSIGNED | Optionale Company-Verknüpfung |
| `expert_id` | INT UNSIGNED | Optionale Expert-Verknüpfung |
| `speaker_id` | INT UNSIGNED | Optionale Speaker-Verknüpfung |
| `show_company_button` | TINYINT(1) | Company-Button auf Übersicht anzeigen |
| `show_expert_button` | TINYINT(1) | Expert-Button auf Übersicht anzeigen |
| `show_speaker_button` | TINYINT(1) | Speaker-Button auf Übersicht anzeigen |
| `status` | VARCHAR(20) | `active` oder `inactive` |
| `is_featured` | TINYINT(1) | Pool für PHINIT-Sidebar-Rotation |
| `sort_order` | INT UNSIGNED | Sortierung |
| `created_at` / `updated_at` | TIMESTAMP | Zeitstempel |

## `{prefix}m365linkcollection_settings`

Key/Value-Tabelle für Public-Design, Tabellenanzeige, Button-Labels und Widget-Optionen.
