# CMS 365NETWORK – API

## `CMS_365NETWORK`

Hauptklasse und Bootstrap.

- `instance(): self`
- `init_plugin(): void`
- `on_activation(string $pluginSlug): void`
- `on_deactivation(string $pluginSlug): void`
- `version(): string`

Globale Core-Hook-Kompatibilitätsfunktionen:

- `hub_install(string $pluginSlug = 'cms-365network'): void`
- `hub_uninstall(string $pluginSlug = 'cms-365network'): void`
- `hub_admin_page(): void`

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

Die Landingpage kann optional Toolbox-Links laden. Voraussetzung ist ein aktiver Toolbox-Slug (`m365toolbox`, `cms-m365toolbox` oder `cms-m365tools`). Legacy-Installationen mit `m365toolbox_links` werden bevorzugt über aktive Datensätze mit `show_on_hub = 1` nach `sort_order` ausgegeben. Wenn keine Legacy-Hub-Links vorhanden sind und die aktuelle `cms-m365tools`-Registry geladen ist, werden aktive Registry-Tools (`live`/`beta`) als Public-Toolbox-Karten verwendet.
Ab `1.0.12` steuert `hub_toolbox_limit` aus `network_hub_settings`, wie viele Links maximal geladen werden.

## Hub-Reihenfolge und Medien

Ab `1.0.18` steuert `hub_section_order` die Reihenfolge der Public-Bereiche (`featured`, `hero`, `stats`, `band`, `areas`, `toolbox`). Deaktivierte Bereiche werden weiterhin nicht ausgegeben. `hub_area_card_order` sortiert zusätzlich die vier Direkteinstieg-Karten (`events`, `speakers`, `companies`, `experts`).

Bild-URL-Felder wie `hub_featured_image_url` können entweder manuell befüllt oder über die 365CMS-Mediathek gesetzt werden. Gespeichert werden absolute HTTP(S)-URLs oder interne Medienpfade (`/uploads/...`, `/media-file?...`).

Hub-Settings werden typisiert geladen: `bool` wird zu Boolean gecastet, `int` zu Integer, Textwerte bleiben Strings. Die Admin-Ausgabe gruppiert die Sections in eigene Tabs. Die Public-Ausgabe nutzt Aktivierung, Texte, URLs, Layout-Enums, Farben und Rundungen für Featured Card, Hero, Stats, Teaser-Band, Direkteinstieg und Toolbox.

Der optionale Analyse-Code wird zusätzlich über die Landingpage-Erkennung begrenzt und erscheint nicht auf anderen CMS-Routen.
