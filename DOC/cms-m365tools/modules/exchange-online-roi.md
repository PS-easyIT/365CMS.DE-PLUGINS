# On-Prem Exchange zu Exchange Online ROI

## Modul

- Registry-Key: `exchange-online-roi`
- Route: `/exchange-online-roi`
- Engine: `CMS_M365CALCULATOR_Exchange_Online_ROI_Calculator`
- Template: `templates/page-exchange-online-roi.php`
- Status: `live`

## Zweck

Berechnet Vollkosten, Break-even und Migrationspfad für den Wechsel von lokalem Exchange zu Exchange Online. Berücksichtigt Infrastruktur-, Betriebs-, Migrations- und Zielplan-Kosten.

## Datenquellen

- `data/exchange_online_plans.json`
- `data/onprem_exchange_cost_defaults.json`
- `data/exchange_migration_velocity.json`

## Ergebnis

- monatliche und mehrjährige Kosten
- Break-even-Monat
- 36-/60-Monats-Delta
- Migrationspfad und Risikowert
- Management-Fazit

## Pflege

Planpreise und On-Prem-Kostenannahmen getrennt halten. Migrationsgeschwindigkeiten bei neuen Erfahrungswerten aktualisieren.
