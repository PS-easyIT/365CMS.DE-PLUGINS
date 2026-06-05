# CMS M365 Message Center – API

## `CMS_M365MessageCenter`

Plugin-Bootstrap. Lädt Abhängigkeiten, registriert Hooks und führt Aktivierungslogik aus.

## `CMS_M365MessageCenter_Installer`

- `install(): void` – Legt Tabellen und Default-Settings an.
- `maybe_install(): void` – Prüft DB-Version und installiert bei Bedarf.

## `CMS_M365MessageCenter_Repository`

- `settings(): array` – Lädt Einstellungen.
- `save_settings(array $settings): void` – Speichert Einstellungen per Prepared Statements.
- `replace_messages(array $messages): int` – Ersetzt lokalen Message-Cache durch normalisierte Graph-Daten.
- `public_messages(array $query): array` – Liefert gecachte Public-Meldungen inkl. Pagination.
- `public_message(string $graphId): ?array` – Liefert einen einzelnen gecachten Eintrag für die Detailseite.
- `categories(): array` – Distinct-Kategorien.
- `services(): array` – Distinct-Services aus JSON-Cache.
- `slug(string $value): string` – Slug-Sanitizer.
- `text(string $value, int $max = 255): string` – Text-Sanitizer.
- `public_sort(string $value): string` – Sortierfeld-Whitelist.

## `CMS_M365MessageCenter_Graph_Client`

- `fetch_messages(array $settings): array` – Holt Message-Center-Meldungen aus Microsoft Graph.
- Nutzt standardmäßig `Accept-Language: de-DE,de;q=0.9,en;q=0.7` für deutsche Message-Center-Inhalte.

Sicherheitsregeln:

- Token-Endpoint pro Cloud aus Allowlist.
- Graph-Endpoint pro Cloud aus Allowlist.
- HTTPS-only.
- Keine Redirects.
- Response-Limit.
- Keine Secret-Ausgabe.

## `CMS_M365MessageCenter_Frontend`

- Registriert Public-Routen.
- Rendert `templates/archive.php`.
- Liest ausschließlich aus `CMS_M365MessageCenter_Repository`.

## `CMS_M365MessageCenter_Refresh_Service`

- `refresh(array $context = []): array` – Gemeinsamer Graph-Abruf für Admin und Cron, inklusive Service-/Kategorie-Filter und Cache-Ersetzung.
- `run_cron(array $context = []): array` – Cron-Einstieg mit Tageswächter ab 12:00 Uhr und Statusspeicherung.

## `CMS_M365MessageCenter_Admin_Menu` / `CMS_M365MessageCenter_Admin_Pages`

- Admin-Menü und Seiten für Übersicht, Einstellungen und manuellen Refresh.
- POST-Aktionen sind CSRF-geschützt.
