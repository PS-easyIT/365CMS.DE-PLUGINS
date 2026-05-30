# Hooks – CMS M365 Linkcollection

| Hook | Callback | Zweck |
|---|---|---|
| `cms_init` | `CMS_M365LINKCOLLECTION::init_plugin()` | Installer prüfen und Frontend initialisieren |
| `plugin_activated` | `CMS_M365LINKCOLLECTION::on_activation()` | Tabellen und Startdaten bei Aktivierung anlegen |
| `cms_admin_menu` | `CMS_M365LINKCOLLECTION_Admin_Menu::register()` | Admin-Menü registrieren |
| `register_routes` | `CMS_M365LINKCOLLECTION_Frontend::instance()` | Public-Routen registrieren |
| `head` | `CMS_M365LINKCOLLECTION_Frontend::enqueue_public_styles()` | Public-/Widget-CSS einbinden |
| `head` | `CMS_M365LINKCOLLECTION_Frontend::output_public_design_tokens()` | Designvariablen aus Einstellungen ausgeben |
| `body_end` | `CMS_M365LINKCOLLECTION_Frontend::enqueue_public_scripts()` | Sidebar-Rotator-JS einbinden |
| `body_class` | `CMS_M365LINKCOLLECTION_Frontend::filter_body_class()` | Public Body-Class ergänzen |

## Theme-Integration

PHINIT ruft bei aktivem Plugin direkt `CMS_M365LINKCOLLECTION_Widget::render_phinit_sidebar_widget()` im Startseiten-Sidebar-Partial auf. Es werden keine Public-Ausgaben in `cms-companies` oder `cms-experts` injiziert.
