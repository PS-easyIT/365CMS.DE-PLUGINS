# Datenbank – CMS M365 Adminsites

## Tabellen

Die Tabellen verwenden den CMS-Datenbankpräfix, üblicherweise `cms_`.

## `cms_m365adminsites_categories`

| Feld | Typ | Beschreibung |
|---|---|---|
| `id` | INT UNSIGNED PK | Kategorie-ID |
| `name` | VARCHAR(190) | Anzeigename |
| `slug` | VARCHAR(190) | URL-/Filter-Slug, eindeutig |
| `description` | TEXT | interne Beschreibung |
| `sort_order` | INT UNSIGNED | Sortierung |
| `is_active` | TINYINT(1) | Sichtbarkeit im Public-Filter |
| `created_at` | TIMESTAMP | Anlagezeitpunkt |
| `updated_at` | TIMESTAMP | Änderungszeitpunkt |

Indizes:

- `UNIQUE KEY idx_slug (slug)`
- `INDEX idx_active_order (is_active, sort_order)`

## `cms_m365adminsites_sites`

| Feld | Typ | Beschreibung |
|---|---|---|
| `id` | INT UNSIGNED PK | Portal-ID |
| `category_id` | INT UNSIGNED | Verknüpfte Kategorie |
| `title` | VARCHAR(190) | Portalname |
| `subtitle` | VARCHAR(190) | Bereich / Kurzfokus |
| `url` | VARCHAR(500) | Ziel-URL |
| `description` | TEXT | Such-/Kontextbeschreibung |
| `image_url` | VARCHAR(500) | optionales Bild |
| `image_alt` | VARCHAR(190) | Alt-Text |
| `tags` | VARCHAR(500) | Such- und Filterkontext |
| `status` | VARCHAR(20) | `active` oder `inactive` |
| `is_featured` | TINYINT(1) | bevorzugt im Widget |
| `sort_order` | INT UNSIGNED | Sortierung innerhalb Kategorien |
| `created_at` | TIMESTAMP | Anlagezeitpunkt |
| `updated_at` | TIMESTAMP | Änderungszeitpunkt |

Indizes:

- `INDEX idx_category (category_id)`
- `INDEX idx_status (status)`
- `INDEX idx_featured (is_featured, status)`
- `UNIQUE KEY idx_category_title_url (category_id, title, url(190))`

## `cms_m365adminsites_settings`

| Feld | Typ | Beschreibung |
|---|---|---|
| `id` | INT UNSIGNED PK | Setting-ID |
| `setting_key` | VARCHAR(120) | Setting-Schlüssel, eindeutig |
| `setting_value` | LONGTEXT | Wert |
| `updated_at` | TIMESTAMP | Änderungszeitpunkt |

Indizes:

- `UNIQUE KEY idx_setting_key (setting_key)`
