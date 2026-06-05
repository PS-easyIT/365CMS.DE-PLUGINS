# API

## Hauptklasse

### `CMS_NetImport`
Plugin-Einstiegspunkt, lädt Abhängigkeiten und registriert Bootstrap-Hooks.

## Import-Service

### `CMS_NetImport_Importer::get_sources(): array`
Liefert die Importquellen inklusive Dateistatus, Zeilenzahl und Plugin-Bereitschaft. Bekannte Dateifamilien werden bevorzugt; alternativ werden anders benannte CSV-Dateien anhand passender Pflichtspalten erkannt.

### `CMS_NetImport_Importer::get_reset_targets(): array`
Liefert die verfügbaren Plugin-Reset-Ziele inklusive geschätzter Zeilenanzahl und Zielplugin-Status.

### `CMS_NetImport_Importer::reset_target_plugins(array $targetKeys): array`
Bereinigt Inhalts-, Meta- und Relationstabellen der ausgewählten Zielplugins transaktional. Zulässige Ziele: `companies`, `experts`, `speakers`, `events`.

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

### `CMS_NetImport_Admin::handle_plugin_reset()`
Prüft Admin-Rechte, CSRF und Rate-Limit und startet den Plugin-Daten-Reset für die ausgewählten Zielplugins.
