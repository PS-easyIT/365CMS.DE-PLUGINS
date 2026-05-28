# CMS 365NETWORK – Hooks

## Genutzte Core-Hooks

| Hook | Callback | Zweck |
|---|---|---|
| `cms_init` | `CMS_365NETWORK::init_plugin()` | Admin/Public Klassen initialisieren |
| `plugin_activated` | `CMS_365NETWORK::on_activation()` | Settings-Tabelle anlegen |
| `cms_admin_menu` | `CMS_365NETWORK_Admin::register_admin_menu()` | Admin-Menüeintrag registrieren |
| `admin_menu_items` | `CMS_365NETWORK_Admin::add_menu_item()` | Sidebar-Menü ergänzen |
| `register_routes` | `CMS_365NETWORK_Admin::register_routes()` | Admin-Routen registrieren |
| `register_routes` | `CMS_365NETWORK_Public::register_routes()` | Public-Routen registrieren |
| `head` | `CMS_365NETWORK_Public::enqueue_styles()` | Public CSS laden |
| `head` | `CMS_365NETWORK_Public::output_dynamic_styles()` | Konfigurations-CSS-Variablen ausgeben |
| `head` | `CMS_365NETWORK_Public::output_analytics_head()` | Optionalen Analyse-Code nur auf der Plugin-Public-Site im Head ausgeben |
| `body_end` | `CMS_365NETWORK_Public::output_analytics_body_end()` | Optionalen Analyse-Code nur auf der Plugin-Public-Site vor `</body>` ausgeben |

## Registrierte Routen

| Methode | Route | Zweck |
|---|---|---|
| `GET` | `/` | Auf konfigurierter Zusatzdomain Landingpage, sonst normale Startseite |
| `GET` | `/{route_slug}` | Interne Landingpage-Vorschau, Standard `/365network` |
| `GET` | `/admin/365network` | Einstellungen |
| `POST` | `/admin/365network/settings/save` | Einstellungen speichern |

## Admin-Tabs

| Tab | Zweck |
|---|---|
| `domain` | Landingpage aktivieren, Zusatzdomains und interne Route |
| `hub` | Featured Card, Hero, Stats, Band, Direkteinstieg und Toolbox aus `network_hub_settings` |
| `content` | Legacy-/Featured-Inhalte |
| `layout` | Layout- und Designvariablen |
| `sidebar` | Sidebar und dynamische Vorschauen |
| `cards` | Legacy-Bereichskarten-Fallbacks |
| `analytics` | Optionaler Analyse-Code |
