# CMS M365 Message Center – Hooks

## Registrierte Actions

| Hook | Callback | Zweck |
|---|---|---|
| `cms_init` | `CMS_M365MessageCenter::init_plugin()` | Tabellen prüfen und Frontend initialisieren |
| `plugin_activated` | `CMS_M365MessageCenter::on_activation()` | Tabellen bei Aktivierung anlegen |
| `cms_admin_menu` | `CMS_M365MessageCenter_Admin_Menu::register()` | Admin-Menü registrieren |
| `register_routes` | `CMS_M365MessageCenter_Frontend::instance()` | Public-Routen registrieren |
| `head` | `CMS_M365MessageCenter_Frontend::enqueue_styles()` | Public-CSS einbinden |
| `body_class` | `CMS_M365MessageCenter_Frontend::body_class()` | Public Body-Klasse ergänzen |
| `cms_cron_hourly` | `CMS_M365MessageCenter_Refresh_Service::run_cron()` | Täglich ab 12:00 Uhr einmal Message-Center-Cache aktualisieren |
| `cms_cron_m365messagecenter` | `CMS_M365MessageCenter_Refresh_Service::run_cron()` | Direkter generischer Cron-Hook für manuelle/gezielte Abrufe |

## Public-Routen

- `/m365-messagecenter`
- `/m365-messagecenter/{messageId}`
- konfigurierte Route aus Einstellung `route_slug`
- `/{route_slug}/{messageId}`
- `/en/{route_slug}`
- `/en/{route_slug}/{messageId}`

## Externe Events

Der Graph-Abruf wird bewusst nicht per Public-Hook ausgelöst. Refresh erfolgt adminseitig per CSRF-geschütztem POST oder serverseitig per `cron.php`.
