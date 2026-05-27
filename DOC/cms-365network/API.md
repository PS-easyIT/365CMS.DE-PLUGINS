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
- `get_settings(): array`
- `save_settings(array $settings): void`

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

Der optionale Analyse-Code wird zusätzlich über die Landingpage-Erkennung begrenzt und erscheint nicht auf anderen CMS-Routen.
