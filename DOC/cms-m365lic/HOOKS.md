# Hooks

## Registrierte Actions

| Hook | Callback | Zweck |
|---|---|---|
| `cms_init` | `CMS_M365LIC::init_plugin()` | Installer/Migrationen ausführen |
| `plugin_activated` | `CMS_M365LIC::on_activation()` | Tabellen und Seed-Katalog anlegen |
| `plugin_uninstalled` | `CMS_M365LIC::on_uninstall()` | Plugin-Tabellen löschen |
| `cms_admin_menu` | `CMS_M365LIC_Admin_Menu::register()` | Admin-Menü registrieren |
| `register_routes` | `CMS_M365LIC_Frontend::instance()` | Public-Routen registrieren |
| `dsgvo_export_data` | `CMS_M365LIC::export_user_data()` | DSGVO-Kompatibilität |
| `dsgvo_delete_data` | `CMS_M365LIC::delete_user_data()` | Gehashte User-Limits löschen |

## Verwendete CMS-Hooks im Frontend

- `head`
- `body_start`
- `after_header`
- `before_footer`
- `body_end`
