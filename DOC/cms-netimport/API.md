# API

## Hauptklasse

### `CMS_NetImport`
Plugin-Einstiegspunkt, lädt Abhängigkeiten und registriert Bootstrap-Hooks.

## Import-Service

### `CMS_NetImport_Importer::get_sources(): array`
Liefert die vorbereiteten Importquellen inklusive Dateistatus, Zeilenzahl und Plugin-Bereitschaft.

### `CMS_NetImport_Importer::run_import(string $type, array $options = []): array`
Führt einen Einzel- oder Komplettimport aus.

#### Unterstützte Typen
- `full`
- `companies_example`
- `experts_mvps`
- `experts_example`
- `speakers`
- `events`

#### Rückgabe
Struktur mit:
- `created`
- `updated`
- `linked`
- `skipped`
- `errors`
- `warnings`
- `messages`

## Admin-Klasse

### `CMS_NetImport_Admin::render_page()`
Rendert die Import-Oberfläche im Admin.

### `CMS_NetImport_Admin::handle_run()`
Prüft CSRF/Admin-Rechte und startet den gewählten Import.
