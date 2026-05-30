# Datenbank – CMS M365 Azure

Alle Tabellen verwenden den dynamischen 365CMS-Tabellenprefix aus `CMS\Database::prefix()`.

## `cms_m365azure_categories`

| Feld | Typ | Zweck |
|---|---|---|
| `id` | INT UNSIGNED PK | Kategorie-ID |
| `slug` | VARCHAR(120) UNIQUE | URL-/Anchor-Slug |
| `title` | VARCHAR(190) | Anzeigename |
| `overline` | VARCHAR(190) | kleine Zeile über dem Titel |
| `intro` | TEXT | Kategorieeinleitung |
| `sort_order` | INT UNSIGNED | Sortierung |
| `is_active` | TINYINT(1) | öffentliche Sichtbarkeit |
| `created_at` | TIMESTAMP | Anlagezeitpunkt |
| `updated_at` | TIMESTAMP | Änderungszeitpunkt |

## `cms_m365azure_services`

| Feld | Typ | Zweck |
|---|---|---|
| `id` | INT UNSIGNED PK | Service-ID |
| `category_id` | INT UNSIGNED | Verknüpfte Kategorie |
| `slug` | VARCHAR(150) UNIQUE | Anchor-/Service-Slug |
| `title` | VARCHAR(190) | Service-Name |
| `subtitle` | VARCHAR(255) | kurze Zusatzzeile |
| `summary` | TEXT | Kurzbeschreibung |
| `content` | MEDIUMTEXT | Haupttext |
| `image_url` | VARCHAR(500) | optionale Bild-URL |
| `image_alt` | VARCHAR(255) | Alt-Text |
| `features` | TEXT | zeilenbasierte Feature-Liste |
| `use_cases` | TEXT | zeilenbasierte Einsatzbereiche |
| `docs_url` | VARCHAR(500) | Dokumentationslink |
| `pricing_url` | VARCHAR(500) | Preislink |
| `sort_order` | INT UNSIGNED | Sortierung |
| `is_active` | TINYINT(1) | öffentliche Sichtbarkeit |
| `created_at` | TIMESTAMP | Anlagezeitpunkt |
| `updated_at` | TIMESTAMP | Änderungszeitpunkt |

## `cms_m365azure_settings`

| Feld | Typ | Zweck |
|---|---|---|
| `id` | INT UNSIGNED PK | interne ID |
| `setting_key` | VARCHAR(120) UNIQUE | Einstellungsschlüssel |
| `setting_value` | MEDIUMTEXT | Wert |
| `updated_at` | TIMESTAMP | Änderungszeitpunkt |

## Wichtige Settings

- `route_slug`
- `page_title`
- `page_overline`
- `page_intro`
- `seo_title`
- `seo_description`
- `show_hero`
- `show_toc`
- `show_category_intro`
- `show_service_images`
- `show_service_links`
- `show_feature_lists`
- `show_use_cases`
- `card_image_position`
- `layout_max_width`
- `card_image_width`
- `design_primary_color`
- `design_accent_color`
- `design_background_color`
- `design_surface_color`
- `design_border_radius`
