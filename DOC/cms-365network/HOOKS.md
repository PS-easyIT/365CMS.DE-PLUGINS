# CMS 365NETWORK – Hooks

## Genutzte Core-Hooks

| Hook | Callback | Zweck |
|---|---|---|
| `cms_init` | `CMS_365NETWORK::init_plugin()` | Admin/Public Klassen initialisieren |
| `plugin_activated` | `CMS_365NETWORK::on_activation()` | Settings-Tabelle anlegen |
| `plugin_deactivated` | `CMS_365NETWORK::on_deactivation()` | Deaktivierung ohne Datenlöschung signalisieren |
| `plugin_activate` | `hub_install()` | Kompatibler Core-Hook via `cms_register_hook()`, falls verfügbar |
| `plugin_deactivate` | `hub_uninstall()` | Kompatibler Core-Hook via `cms_register_hook()`, ohne Tabellen zu löschen |
| `cms_admin_menu` | `CMS_365NETWORK_Admin::register_admin_menu()` | Admin-Menüeintrag registrieren |
| `admin_menu_items` | `CMS_365NETWORK_Admin::add_menu_item()` | Sidebar-Menü ergänzen |
| `register_routes` | `CMS_365NETWORK_Admin::register_routes()` | Admin-Routen registrieren |
| `register_routes` | `CMS_365NETWORK_Public::register_routes()` | Public-Routen registrieren |
| `head` | `CMS_365NETWORK_Public::enqueue_styles()` | Public CSS laden |
| `head` | `CMS_365NETWORK_Public::output_dynamic_styles()` | Konfigurations-CSS-Variablen ausgeben |
| `head` | `CMS_365NETWORK_Public::output_analytics_head()` | Optionalen Analyse-Code nur auf der Plugin-Public-Site im Head ausgeben |
| `body_end` | `CMS_365NETWORK_Public::enqueue_scripts()` | Public-JavaScript für Landingpage-/Suchroute laden, aktuell Spotlight-Rotator |
| `body_end` | `CMS_365NETWORK_Public::output_analytics_body_end()` | Optionalen Analyse-Code nur auf der Plugin-Public-Site vor `</body>` ausgeben |

## Registrierte Routen

| Methode | Route | Zweck |
|---|---|---|
| `GET` | `/` | Auf konfigurierter Zusatzdomain Landingpage, sonst normale Startseite |
| `GET` | `/{route_slug}` | Interne Landingpage-Vorschau, Standard `/365network` |
| `GET` | `/{route_slug}/search` | Eigene 365NETWORK-Suche, Standard `/365network/search` |
| `GET` | `/admin/365network` | Einstellungen |
| `POST` | `/admin/365network/settings/save` | Einstellungen speichern |

## Admin-Tabs

| Tab | Zweck |
|---|---|
| `domain` | Landingpage aktivieren, Zusatzdomains und interne Route |
| `hub-featured` | Featured Card aus `network_hub_settings` |
| `hub-hero` | Hero-Texte, CTA, Layout und Design |
| `hub-stats` | Kennzahlen-Labels, Zielseiten, Layout und Design |
| `hub-band` | Event-Teaser, Suche, Texte, Layout und Design |
| `hub-areas` | Direkteinstieg-Kacheln, Raster, Kartenstil und Design |
| `hub-partnerband` | Partnerband direkt unter dem Theme-Header mit Limit und Farben |
| `hub-next-events` | Nächste Events mit Titel, Linkziel, Limit und Farben |
| `hub-spotlight` | Fokus-Rotator mit Limit, optionalem Autoplay und Farben |
| `hub-partner-columns` | Companies-/Experts-Spalten mit Limits, Links, Badge und Farben |
| `hub-toolbox` | Toolbox-Sektion, Limit, Layout und Design |
| `layout` | Übergreifende Seitenlayout- und Designvariablen |
| `sidebar` | Sidebar und dynamische Vorschauen |
| `analytics` | Optionaler Analyse-Code |
