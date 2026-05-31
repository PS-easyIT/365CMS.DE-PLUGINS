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
- `enqueue_scripts(): void`
- `output_dynamic_styles(): void`
- `output_analytics_head(): void`
- `output_analytics_body_end(): void`

Interne Fetch-Methoden nutzen nur geprüfte Tabellen und Plugin-Aktivstatus. Bei Fehlern werden leere Arrays zurückgegeben, damit die Landingpage stabil bleibt.

Ab `1.0.21` stellt `render_landing()` dem Template zusätzlich Preview-Hub-Daten bereit:

- `partner_companies`/`$partnerCompanies`: aktive Company-Partner aus `is_partner` oder `is_top_partner`.
- `partner_experts`/`$partnerExperts`: aktive Expertenprofile als defensiver Partner-Spalten-Fallback, da kein Expert-Partner-Flag existiert.
- `$hubSettings`: typisierte Settings für Hero, Partnerband, Areas, nächste Events, Spotlight, Partner-Spalten und Toolbox. Ein eigener Plugin-Header wird nicht gerendert, da Header und Navigation vom Theme kommen.

`enqueue_scripts()` lädt `assets/js/public.js` ausschließlich auf der 365NETWORK-Landingpage oder der 365NETWORK-Suche. Das Script steuert den Spotlight-Rotator anhand von `data-n365-spotlight`-Attributen; ab `1.0.22` werden keine Dot-/Pagination-Buttons mehr erzeugt, sichtbar bleiben nur die Pfeile links und rechts.

Die Landingpage kann optional Toolbox-Links laden. Voraussetzung ist ein aktiver Toolbox-Slug (`m365toolbox`, `cms-m365toolbox` oder `cms-m365tools`). Legacy-Installationen mit `m365toolbox_links` werden bevorzugt über aktive Datensätze mit `show_on_hub = 1` nach `sort_order` ausgegeben. Wenn keine Legacy-Hub-Links vorhanden sind und die aktuelle `cms-m365tools`-Registry geladen ist, werden aktive Registry-Tools (`live`/`beta`) als Public-Toolbox-Karten verwendet.
Ab `1.0.12` steuert `hub_toolbox_limit` aus `network_hub_settings`, wie viele Links maximal geladen werden.

## Hub-Reihenfolge und Medien

Ab `1.0.18` steuert `hub_section_order` die Reihenfolge der Public-Bereiche. Ab `1.0.21` unterstützt die Public-Ausgabe zusätzlich `partnerband`, `next-events`, `spotlight` und `partner-columns`; der Default folgt dem headerlosen Preview-Aufbau `partnerband`, `hero`, `areas`, `next-events`, `spotlight`, `partner-columns`, `toolbox`, `featured`, `stats`, `band`. Deaktivierte Bereiche werden weiterhin nicht ausgegeben. Das Partnerband wird öffentlich immer direkt unter dem Theme-Header priorisiert, sofern es aktiviert ist. `hub_area_card_order` sortiert zusätzlich die vier Direkteinstieg-Karten; ab `1.0.24` ist der Default `events`, `speakers`, `experts`, `companies`, damit das 2×2-Raster öffentlich `Events/Speaker` und `Experts/Firmen` zeigt.

Bild-URL-Felder wie `hub_featured_image_url` und `hub_hero_image_url` können entweder manuell befüllt oder über die 365CMS-Mediathek gesetzt werden. Gespeichert werden absolute HTTP(S)-URLs oder interne Medienpfade (`/uploads/...`, `/media-file?...`). Ist `hub_hero_image_url` gesetzt, rendert der Hero das Bild je nach `hub_hero_image_mode` entweder statt der sichtbaren H1 oder zusätzlich neben Titel/Suche. Bei ersetzter H1 bleibt die H1 als Screenreader-only Überschrift erhalten. Ab `1.0.25` steuern `hub_hero_image_width` und `hub_hero_image_height` die Bildmaße; der Default ist `250 × 200px`. `hub_hero_layout` bietet `center`, `image-right` und `image-left`.

Hub-Settings werden typisiert geladen: `bool` wird zu Boolean gecastet, `int` zu Integer, Textwerte bleiben Strings. Die Admin-Ausgabe gruppiert die Sections in eigene Tabs. Die Public-Ausgabe nutzt Aktivierung, Texte, URLs, Layout-Enums, Farben und Rundungen für Featured Card, Hero, Stats, Teaser-Band, Direkteinstieg, Partnerband, nächste Events, Spotlight, Partner-Spalten und Toolbox. Ab `1.0.22` werden Public-Radiuswerte im Admin und bei der CSS-Variablen-Ausgabe auf maximal `2px` begrenzt.

Der optionale Analyse-Code wird zusätzlich über die Landingpage-Erkennung begrenzt und erscheint nicht auf anderen CMS-Routen.
