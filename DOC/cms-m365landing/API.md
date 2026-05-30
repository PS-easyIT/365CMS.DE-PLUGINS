# CMS M365 Landing – API

## Klassen

### `CMS_M365Landing`

Plugin-Bootstrap, lädt Dependencies und registriert Hooks.

### `CMS_M365Landing_Installer`

Erstellt Tabellen und seedet Default-Settings sowie Default-Cards.

### `CMS_M365Landing_Repository`

Zentrale Datenzugriffsklasse für Settings und Cards.

Wichtige Methoden:

- `settings(): array`
- `save_settings(array $settings): void`
- `cards(?string $section = null, bool $activeOnly = false): array`
- `cards_by_section(bool $activeOnly = true): array`
- `card(int $id): ?array`
- `save_card(array $data): int`
- `delete_card(int $id): void`
- `stats(): array`

### `CMS_M365Landing_Frontend`

Registriert die Public Route, lädt Styles und rendert `templates/landing.php`.

### `CMS_M365Landing_Admin_Pages`

Rendert Dashboard, Kartenverwaltung, Einstellungen und Systemseite.
