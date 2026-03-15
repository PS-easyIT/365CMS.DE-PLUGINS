# API / Klassen

## `CMS_M365LIC`

Bootstrap-Klasse des Plugins. Lädt Abhängigkeiten und registriert Lifecycle- sowie Routing-Hooks.

## `CMS_M365LIC_Catalog`

Statische Definitionen für:

- Feature-Katalog
- Presets
- Default-Settings
- Seed-Pakete

## `CMS_M365LIC_Repository`

Persistenz und DB-Zugriff für Pakete, Settings und Tageslimits.

### Zentrale Methoden

- `get_settings()`
- `save_settings(array $settings)`
- `get_packages(bool $includeInactive = true)`
- `save_package(array $data)`
- `reset_catalog_to_defaults()`
- `resolve_pricing_context(?string $tier, ?string $groupKey)`
- `enforce_daily_limit(string $action, string $tier)`

## `CMS_M365LIC_Calculator`

Rechenlogik für Basislizenz + Add-on-Empfehlungen.

### Zentrale Methode

- `evaluate(array $requirements, array $packages, string $pricingTier)`

## `CMS_M365LIC_Frontend`

Registriert Public-Routen und rendert Calculator + PDF-Export.

## `CMS_M365LIC_Pdf_Export`

Erzeugt HTML für den PDF-Export und streamt über Dompdf, sofern verfügbar.
