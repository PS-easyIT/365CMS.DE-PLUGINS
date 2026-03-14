# CMS WordPress Importer – Hooks-Referenz

Der Importer registriert aktuell **keine eigenen öffentlichen Erweiterungs-Hooks** für Drittplugins. Die Integration erfolgt über reguläre 365CMS-Plugin-Hooks zur Admin-Seite und zu AJAX-Endpunkten.

## Verwendete 365CMS-Hooks

### `cms_init`
Initialisiert die Import-Tabellen beim regulären Plugin-Start.

```php
CMS\Hooks::addAction('cms_init', [$plugin, 'init'], 10);
```

### `plugin_activated`
Legt Import-Tabellen beim Aktivieren des Plugins an.

```php
CMS\Hooks::addAction('plugin_activated', [$plugin, 'on_activation'], 10);
```

### `cms_admin_menu`
Registriert das Admin-Menü des Importers.

```php
CMS\Hooks::addAction('cms_admin_menu', [$plugin, 'register_admin_pages'], 20);
```

### `admin_ajax_cms_importer_upload`
Verarbeitet den kombinierten Upload-/Import-AJAX-Request.

### `admin_ajax_cms_importer_folder_import`
Importiert eine vorhandene XML- oder JSON-Datei aus einer bekannten Import-Quelle.

### `admin_ajax_cms_importer_preview`
Erstellt eine Dry-Run-Vorschau für eine hochgeladene oder bereits vorhandene XML-/JSON-Datei ohne Schreibzugriff auf die Datenbank.

### `admin_ajax_cms_importer_scan_folder`
Liefert die XML-/JSON-Dateien aus `uploads/import/`, `wp_import_files/` und `wp_import/`.

### `admin_ajax_cms_importer_download_report`
Stellt den Markdown-Bericht für unbekannte Metadaten zum Download bereit.

## Hinweis

Wenn künftig eigene Erweiterungspunkte für Mapping, Validierung oder Nachbearbeitung benötigt werden, sollten diese explizit in `CMS_Importer_Service` ergänzt und hier dokumentiert werden.
