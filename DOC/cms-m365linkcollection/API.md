# API – CMS M365 Linkcollection

## `CMS_M365LINKCOLLECTION`

- `instance(): self`
- `init_plugin(): void`
- `on_activation(string $plugin): void`

## `CMS_M365LINKCOLLECTION_Installer`

- `install(): void`
- `maybe_install(): void`

## `CMS_M365LINKCOLLECTION_Repository`

- `instance(): self`
- `categories(bool $activeOnly = false): array`
- `links(array $filters = [], int $limit = 100, int $offset = 0): array`
- `find(int $id): ?array`
- `save(array $data): int`
- `delete(int $id): void`
- `company_options(): array`
- `expert_options(): array`
- `related_buttons(array $link): array`
- `expert_slug(int $expertId): string`

## `CMS_M365LINKCOLLECTION_Settings`

- `defaults(): array`
- `all(): array`
- `get(string $key, ?string $default = null): string`
- `save(array $settings): void`
- `bool(string $key, bool $default = false): bool`
- `int(string $key, int $default, int $min, int $max): int`
- `color(string $key, string $default): string`
- `route(): string`

## `CMS_M365LINKCOLLECTION_Widget`

- `render_phinit_sidebar_widget(string $orderStyle = ''): void`

Rendert das PHINIT-Sidebar-Widget mit rotierenden Links. Der Parameter enthält optional den vom Theme berechneten `order`-Inline-Style.
