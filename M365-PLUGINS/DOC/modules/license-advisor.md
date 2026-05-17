# M365-Lizenz-Berater

## Modul

- Registry-Key: `m365lic`
- Route: `/m365-lizenzberater`
- Engine: `CMS_M365CALCULATOR_License_Advisor`
- Template: `templates/page-license-advisor.php`
- Status: `live`

## Zweck

Berät bis zu fünf Nutzergruppen anhand von Persona-Presets, Basislizenzbedarf, Add-ons, Kostenmodell und Business-/Enterprise-Grenzen. Migriert fachlich die Datenbasis des früheren M365-Lizenzberaters in die Toolbox.

## Datenquellen

- `data/license_advisor_plans.json`
- `data/license_advisor_addons.json`
- `data/license_advisor_feature_matrix.json`
- `data/license_advisor_persona_presets.json`
- `data/license_advisor_commercial_rules.json`

## Ergebnis

- Basislizenz je Nutzergruppe
- Add-on-Empfehlungen
- Monats-, Jahres- und 36-Monats-Kosten
- Grenzwert- und Sonderfallhinweise
- SharePoint-Speicher- und Copilot-Fähigkeitscheck

## Pflege

Planpreise, Add-ons und Persona-Presets getrennt pflegen. Commercial Rules bestimmen Business-300-Grenzen und Sonderfälle.
