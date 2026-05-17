# M365 Add-On-Konfigurator

## Modul

- Registry-Key: `m365-add-on-konfigurator`
- Route: `/m365-add-on-konfigurator`
- Engine: `CMS_M365CALCULATOR_Addon_Configurator`
- Template: `templates/page-addon-configurator.php`
- Status: `live`

## Zweck

Prüft gewünschte Add-ons gegen Basislizenz, Voraussetzungen, Redundanzen, Upgrade-Alternativen und Verbrauchsprodukte. Ziel ist eine saubere Add-on- und Upgrade-Entscheidung.

## Datenquellen

- `data/addon_configurator_addons.json`
- `data/addon_overlap_rules.json`
- `data/consumption_modules.json`
- `data/license_advisor_plans.json`
- `data/license_advisor_commercial_rules.json`

## Ergebnis

- mögliche und blockierte Add-ons
- Prerequisite-Hinweise
- Redundanzwarnungen
- Upgrade-vs-Add-on-Vergleich
- Verbrauchs- und Sonderpfade

## Pflege

Add-on-Katalog und Overlap-Regeln synchron halten. Verbrauchsprodukte nicht als normale User-SKU behandeln.
