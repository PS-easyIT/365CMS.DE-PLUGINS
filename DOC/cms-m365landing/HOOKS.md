# CMS M365 Landing – Hooks

## Registrierte Hooks

| Hook | Callback | Zweck |
|---|---|---|
| `cms_init` | `CMS_M365Landing::init_plugin()` | Installer prüfen und Frontend initialisieren |
| `plugin_activated` | `CMS_M365Landing::on_activation()` | Tabellen und Seeds bei Aktivierung erstellen |
| `cms_admin_menu` | `CMS_M365Landing_Admin_Menu::register()` | Direkten Admin-Menüpunkt registrieren |
| `register_routes` | `CMS_M365Landing_Frontend::instance()` | Public Route registrieren |
| `head` | `CMS_M365Landing_Frontend::enqueue_styles()` | Public CSS laden |
| `head` | `CMS_M365Landing_Frontend::output_design_tokens()` | CSS-Variablen ausgeben |
| `body_class` | `CMS_M365Landing_Frontend::filter_body_class()` | M365-Designklassen setzen |
