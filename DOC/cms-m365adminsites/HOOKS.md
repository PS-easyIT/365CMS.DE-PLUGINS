# Hooks – CMS M365 Adminsites

## Registrierte Actions

| Hook | Callback | Zweck |
|---|---|---|
| `cms_init` | `CMS_M365ADMINSITES::init_plugin()` | Installer prüfen und Public-Frontend initialisieren |
| `plugin_activated` | `CMS_M365ADMINSITES::on_activation()` | Tabellen und Startdaten beim Aktivieren erstellen |
| `cms_admin_menu` | `CMS_M365ADMINSITES_Admin_Menu::register()` | Admin-Menüpunkt registrieren |
| `register_routes` | `CMS_M365ADMINSITES_Frontend::instance()` | Public-Routen registrieren |
| `head` | `CMS_M365ADMINSITES_Frontend::enqueue_public_styles()` | Public-CSS laden |
| `head` | `CMS_M365ADMINSITES_Frontend::output_public_design_tokens()` | CSS-Variablen aus Einstellungen ausgeben |
| `body_end` | `CMS_M365ADMINSITES_Frontend::enqueue_public_scripts()` | Public-JavaScript laden |

## Registrierte Filter

| Filter | Callback | Zweck |
|---|---|---|
| `body_class` | `CMS_M365ADMINSITES_Frontend::filter_body_class()` | Body-Klasse `m365adminsites-theme-embed` für Theme-Spacing setzen |

## Widget

PHINIT kann das Widget direkt rendern über:

`CMS_M365ADMINSITES_Widget::render_phinit_sidebar_widget()`

Das Plugin injiziert keine Public-Ausgaben in andere Plugins.
