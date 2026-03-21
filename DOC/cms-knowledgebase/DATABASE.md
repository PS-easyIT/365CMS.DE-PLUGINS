# Datenbankschema

## Tabellen

### `cms_kb_entries`

| Feld | Typ | Beschreibung |
|---|---|---|
| `id` | INT UNSIGNED | Primärschlüssel |
| `title` | VARCHAR(255) | Titel der KB-Seite |
| `keyword` | VARCHAR(190) | Hauptbegriff für Auto-Linking |
| `slug` | VARCHAR(190) | Öffentlicher Slug unter `/kb/{slug}` |
| `excerpt` | TEXT | Kurzbeschreibung |
| `content` | LONGTEXT | Inhalt der KB-Seite |
| `tooltip_text` | TEXT | Kurztext für Tooltip |
| `synonyms` | TEXT | Zusätzliche Begriffe, zeilen- oder kommasepariert |
| `category` | VARCHAR(120) | Optionale Kategorie |
| `priority` | INT | Sortierung / Priorisierung |
| `is_active` | TINYINT(1) | Aktiv/Inaktiv |
| `is_case_sensitive` | TINYINT(1) | Groß-/Kleinschreibung beachten |
| `is_whole_word` | TINYINT(1) | Nur ganze Wörter matchen |
| `max_links_per_page` | INT | Limit pro Begriff und Seite |
| `created_at` | TIMESTAMP | Erstellung |
| `updated_at` | TIMESTAMP | Letzte Änderung |

### `cms_kb_settings`

| Feld | Typ | Beschreibung |
|---|---|---|
| `id` | INT UNSIGNED | Primärschlüssel |
| `setting_key` | VARCHAR(100) | Setting-Schlüssel |
| `setting_value` | LONGTEXT | Setting-Wert |
| `updated_at` | TIMESTAMP | Letzte Änderung |
