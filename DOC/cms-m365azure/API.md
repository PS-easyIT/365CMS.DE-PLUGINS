# API – CMS M365 Azure

## `CMS_M365Azure`

Bootstrap-Singleton des Plugins.

- `instance(): self`
- `init_plugin(): void`
- `on_activation(string $plugin_slug): void`

## `CMS_M365Azure_Installer`

Legt Tabellen, Settings und Seed-Daten an.

- `maybe_install(): void`
- `install(): void`
- `create_tables(): void`
- `seed_defaults(): void`

## `CMS_M365Azure_Repository`

Zentrale Datenzugriffsschicht.

- `instance(): self`
- `settings(): array`
- `save_settings(array $settings): void`
- `categories(bool $activeOnly = false): array`
- `category(int $id): ?array`
- `save_category(array $data): int`
- `delete_category(int $id): void`
- `services(?int $categoryId = null, bool $activeOnly = false): array`
- `service(int $id): ?array`
- `save_service(array $data): int`
- `delete_service(int $id): void`
- `stats(): array`

### Sanitizer

- `CMS_M365Azure_Repository::text(string $value, int $maxLength = 255): string`
- `CMS_M365Azure_Repository::long_text(string $value): string`
- `CMS_M365Azure_Repository::url(string $value): string`
- `CMS_M365Azure_Repository::color(string $value, string $default): string`
- `CMS_M365Azure_Repository::slug(string $value): string`

## `CMS_M365Azure_Frontend`

Registriert Public Route, Assets und Ausgabe.

- `instance(): self`
- `enqueue_styles(): void`
- `output_design_tokens(): void`

## `CMS_M365Azure_Admin_Menu`

- `register(): void`

## `CMS_M365Azure_Admin_Pages`

Rendert Dashboard, Kategorien, Services, Einstellungen und Systemseite.

- `render_dispatch(): void`
