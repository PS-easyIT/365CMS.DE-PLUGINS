# API – CMS M365 Matrixen

## `CMS_M365MATRICES`

Bootstrap-Singleton des Plugins.

- `instance(): self`
- `init_plugin(): void`
- `on_activation(string $pluginSlug): void`

## `CMS_M365MATRICES_Source`

Liefert lokale Template- und Asset-Pfade des Matrix-Plugins.

- `load_runtime(): bool`
- `template_path(string $template): string`
- `asset_file(string $asset): string`
- `asset_url(string $asset): string`

## `CMS_M365MATRICES_Settings`

Liest und speichert Matrix-Optionen in den gemeinsamen M365-Tools-Optionstabellen.

- `module_options(string $moduleKey, ?string $optionGroup = null): array`
- `save_module_options(string $moduleKey, string $optionGroup, array $options): void`
- `global_options(?string $optionGroup = null): array`
- `save_global_options(string $optionGroup, array $options): void`

## `CMS_M365MATRICES_ReadOnly_Matrices`

Lädt die lokalen Matrix-JSON-Dateien und normalisiert sie für die Public-Templates.

- `suite_matrix(): array`
- `addon_matrix(): array`
- `copilot_matrix(): array`

## `CMS_M365MATRICES_Installer`

Stellt die gemeinsamen Options-/Settings-Tabellen bereit.

- `maybe_install(): void`
- `create_tables(): void`

## `CMS_M365MATRICES_Frontend`

Registriert und rendert die Public-Matrixseiten.

- `register_routes(): void`
- `render_suite_matrix(): void`
- `render_addon_matrix(): void`
- `render_copilot_matrix(): void`
- `enqueue_public_styles(): void`
- `output_public_design_tokens(): void`

## `CMS_M365MATRICES_Admin_Menu`

Registriert den Adminpunkt.

- `register_menu(): void`

## `CMS_M365MATRICES_Admin_Pages`

Rendert und speichert Matrix-Adminoptionen.

- `render_dashboard(): void`
