# API – CMS M365 Price Tracker

## `CMS_M365PRICETRACKER_Frontend`

- `instance(): self` – Singleton und Routenregistrierung.
- `enqueue_public_styles(): void` – gibt CSS-Assets und Admin-Designvariablen für die Tracker-Route aus.
- `enqueue_public_scripts(): void` – gibt Chart.js und Tracker-JS aus.
- `filter_body_class(mixed $bodyClass): string` – ergänzt Body-Klassen.

## `CMS_M365PRICETRACKER_Admin_Menu`

- `register(): void` – registriert den Admin-Menüpunkt `M365 Preise`.
- `register_routes(mixed $router = null): void` – registriert die Admin-Route.

## `CMS_M365PRICETRACKER_Admin_Pages`

- `render_dashboard(): void` – rendert Status, Datenpaket und Public-Link.

## `CMS_M365PRICETRACKER_Settings`

- `defaults(): array` – Standardwerte für Layout, Farben, Bereiche und Karten.
- `all(): array` – lädt gespeicherte Settings plus Defaults.
- `save(array $settings): void` – speichert validierte Settings.
- `sanitize_from_post(array $posted): array` – validiert Admin-POST-Werte.
- `css_vars(): array` – liefert CSS Custom Properties für das Frontend.

## `CMS_M365PRICETRACKER_Installer`

- `install(): void` – legt die Settings-Tabelle an.
- `maybe_install(): void` – idempotente Tabellenprüfung bei `cms_init`.
- `ensure_for_admin_save(): void` – stellt die Tabelle vor Speichern sicher.

## `CMS_M365PRICETRACKER_Microsoft_Price_Tracker`

- `default_input(): array` – Standardwerte für Filter und Bestand.
- `normalize_input(array $source): array` – validiert GET-Parameter.
- `evaluate(array $input): array` – berechnet Ereignisse, Impact, Renewal, Forecast und Tabellenzeilen.
- `price_history_dataset(): array` – liefert die Chart.js-Zeitreihendaten.
- `render_price_tracker_page(): string` – liefert den Template-Pfad.

## `CMS_M365PRICETRACKER_Catalog`

- `microsoft_price_skus(): array`
- `microsoft_price_events(): array`
- `microsoft_price_changes(): array`
- `microsoft_inventory_mapping(): array`
- `microsoft_price_forecast_rules(): array`
