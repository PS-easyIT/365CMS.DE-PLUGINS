# Hooks – CMS M365 Azure

## Registrierte Actions

| Hook | Callback | Zweck |
|---|---|---|
| `cms_init` | `CMS_M365Azure::init_plugin()` | Installation/Migration bei Initialisierung prüfen |
| `plugin_activated` | `CMS_M365Azure::on_activation()` | Tabellen und Seed-Daten beim Aktivieren anlegen |
| `cms_admin_menu` | `CMS_M365Azure_Admin_Menu::register()` | Admin-Menü registrieren |
| `register_routes` | `CMS_M365Azure_Frontend::instance()` | Public Route registrieren |
| `head` | `CMS_M365Azure_Frontend::enqueue_styles()` | Public CSS auf Azure-Route laden |
| `head` | `CMS_M365Azure_Frontend::output_design_tokens()` | Design-Tokens aus Admin-Settings ausgeben |

## Public Route

Die Route wird aus `route_slug` generiert. Standard:

- `GET /azure-services`

## Eigene Erweiterungen

Version `1.0.0` definiert noch keine zusätzlichen Actions oder Filter für Drittplugins. Erweiterungspunkte können später ergänzt werden, sobald externe Integrationen benötigt werden.
