# CMS M365 Calculator – Hooks

## Actions

| Hook | Callback | Zweck |
|---|---|---|
| `cms_init` | `CMS_M365CALCULATOR::init_plugin()` | Initialisiert Frontend Controller |
| `plugin_activated` | `CMS_M365CALCULATOR::on_activation()` | Aktivierungsroutine ohne DB-Migration |
| `cms_admin_menu` | `CMS_M365CALCULATOR_Admin_Menu::register()` | Registriert Admin-Menü |
| `register_routes` | `CMS_M365CALCULATOR_Frontend::instance()` | Registriert Public Routes |
| `head` | `CMS_M365CALCULATOR_Frontend::enqueue_public_styles()` | Bindet CSS nur auf Plugin-Routen ein |
| `body_end` | `CMS_M365CALCULATOR_Frontend::enqueue_public_scripts()` | Bindet JavaScript nur auf Plugin-Routen ein |

## Filter

| Filter | Callback | Zweck |
|---|---|---|
| `body_class` | `CMS_M365CALCULATOR_Frontend::filter_body_class()` | Ergänzt Body-Klasse für Theme-Embedding |

## Registry-Filter

| Filter | Wert | Zweck |
|---|---|---|
| `m365calculator_tools` | `array<string,array<string,mixed>>` | Optionale Erweiterung der Tool-Registry durch weitere Module |
