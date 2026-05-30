# Hooks – CMS M365 Matrixen

## Registrierte Actions

| Hook | Callback | Zweck |
|---|---|---|
| `cms_init` | `CMS_M365MATRICES::init_plugin()` | Installation prüfen und Frontend initialisieren |
| `plugin_activated` | `CMS_M365MATRICES::on_activation()` | Tabellen bei Aktivierung sicherstellen |
| `cms_admin_menu` | `CMS_M365MATRICES_Admin_Menu::register_menu()` | Admin-Menü `M365 Matrixen` registrieren |
| `register_routes` | `CMS_M365MATRICES_Frontend::register_routes()` | Public-Routen für Lizenz- und Add-on-Matrix registrieren |
| `head` | `CMS_M365MATRICES_Frontend::enqueue_public_styles()` | Gemeinsame Public-CSS laden |
| `head` | `CMS_M365MATRICES_Frontend::output_public_design_tokens()` | Gemeinsame Design-Tokens ausgeben |

## Public-Routen

- `GET /m365-lizenzmatrix`
- `GET /m365-addon-matrix`
