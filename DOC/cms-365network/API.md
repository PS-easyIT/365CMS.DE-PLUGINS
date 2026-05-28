# CMS 365NETWORK – API

## `CMS_365NETWORK`

Hauptklasse und Bootstrap.

- `instance(): self`
- `init_plugin(): void`
- `on_activation(string $pluginSlug): void`
- `version(): string`

## `CMS_365NETWORK_Database`

Settings-Repository.

- `instance(): self`
- `create_tables(): void`
- `default_settings(): array`
- `default_hub_settings(): array`
- `get_settings(): array`
- `save_settings(array $settings): void`
- `get_hub_settings(): array`
- `get_hub_setting_rows(): array`
- `save_hub_settings(array $settings): void`

## `CMS_365NETWORK_Admin`

Admin-Menü, Admin-Routen und Einstellungsformular.

- `instance(): self`
- `register_admin_menu(): void`
- `register_routes($router): void`
- `add_menu_item(array $menuItems): array`
- `render_settings(): void`
- `save_settings(): void`

## `CMS_365NETWORK_Public`

Public-Routing, Domain-Erkennung, Asset-Ausgabe und Datenintegration.

- `instance(): self`
- `register_routes($router): void`
- `render_root_or_home(): void`
- `render_landing(): void`
- `enqueue_styles(): void`
- `output_dynamic_styles(): void`
- `output_analytics_head(): void`
- `output_analytics_body_end(): void`

Interne Fetch-Methoden nutzen nur geprüfte Tabellen und Plugin-Aktivstatus. Bei Fehlern werden leere Arrays zurückgegeben, damit die Landingpage stabil bleibt.

Die Landingpage kann optional Toolbox-Links aus `m365toolbox_links` laden. Voraussetzung ist ein aktiver Toolbox-Slug (`m365toolbox`, `cms-m365toolbox` oder `cms-m365tools`); danach werden bis zu 12 aktive Datensätze mit `show_on_hub = 1` nach `sort_order` ausgegeben.
Ab `1.0.12` steuert `hub_toolbox_limit` aus `network_hub_settings`, wie viele Links maximal geladen werden.

Hub-Settings werden typisiert geladen: `bool` wird zu Boolean gecastet, `int` zu Integer, Textwerte bleiben Strings. Die Admin-Ausgabe gruppiert die Sections in eigene Tabs. Die Public-Ausgabe nutzt Aktivierung, Texte, URLs, Layout-Enums, Farben und Rundungen für Featured Card, Hero, Stats, Teaser-Band, Direkteinstieg und Toolbox.

Der optionale Analyse-Code wird zusätzlich über die Landingpage-Erkennung begrenzt und erscheint nicht auf anderen CMS-Routen.
