# API – CMS M365 Adminsites

## Klassen

### `CMS_M365ADMINSITES`

Bootstrap-Klasse des Plugins.

- `instance(): self`
- `init_plugin(): void`
- `on_activation(string $plugin): void`

### `CMS_M365ADMINSITES_Settings`

Einstellungen und Defaults.

- `defaults(): array`
- `all(): array`
- `get(string $key, ?string $default = null): string`
- `save(array $settings): void`
- `bool(string $key, bool $default = false): bool`
- `int(string $key, int $default, int $min, int $max): int`
- `color(string $key, string $default): string`
- `route(): string`
- `table_name(CMS\Database $db): string`

### `CMS_M365ADMINSITES_Repository`

Datenzugriff für Kategorien und Portale.

- `categories(bool $activeOnly = false): array`
- `sites(array $filters = [], int $limit = 100, int $offset = 0): array`
- `links(array $filters = [], int $limit = 100, int $offset = 0): array`
- `find(int $id): ?array`
- `save(array $data): int`
- `delete(int $id): void`
- `slugify(string $value): string`
- `public_media_url(string $url): string`

### `CMS_M365ADMINSITES_Installer`

Installations- und Seed-Logik.

- `install(): void`
- `maybe_install(): void`

### `CMS_M365ADMINSITES_Frontend`

Public-Routen, Assets und Rendering.

- `instance(): self`
- `filter_body_class(mixed $bodyClass): string`
- `enqueue_public_styles(): void`
- `output_public_design_tokens(): void`
- `enqueue_public_scripts(): void`

### `CMS_M365ADMINSITES_Widget`

PHINIT-kompatibles Sidebar-Widget.

- `render_phinit_sidebar_widget(string $orderStyle = ''): void`

### `CMS_M365ADMINSITES_Admin_Menu`

- `register(): void`

### `CMS_M365ADMINSITES_Admin_Pages`

- `render_dashboard(): void`
