# API – CMS M365 Matrixen

## `CMS_M365MATRICES`

Bootstrap-Singleton des Plugins.

- `instance(): self`
- `init_plugin(): void`
- `on_activation(string $pluginSlug): void`

## `CMS_M365MATRICES_Source`

Lädt die gemeinsame Runtime aus `cms-m365tools`.

- `load_runtime(): bool`
- `tools_dir(): string`
- `tools_url(): string`

## `CMS_M365MATRICES_Installer`

Stellt die gemeinsamen Options-/Settings-Tabellen bereit.

- `maybe_install(): void`
- `create_tables(): void`

## `CMS_M365MATRICES_Frontend`

Registriert und rendert die Public-Matrixseiten.

- `register_routes(): void`
- `render_suite_matrix(): void`
- `render_addon_matrix(): void`
- `enqueue_public_styles(): void`
- `output_public_design_tokens(): void`

## `CMS_M365MATRICES_Admin_Menu`

Registriert den Adminpunkt.

- `register_menu(): void`

## `CMS_M365MATRICES_Admin_Pages`

Rendert und speichert Matrix-Adminoptionen.

- `render_dashboard(): void`
