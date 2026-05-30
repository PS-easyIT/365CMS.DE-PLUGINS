# Microsoft-Preiserhöhung-Tracker

## Modul

- Registry-Key: `microsoft-price-tracker`
- Route: `/microsoft-preiserhoehung-tracker`
- Engine: `CMS_M365CALCULATOR_Microsoft_Price_Tracker`
- Template: `templates/page-microsoft-price-tracker.php`
- Status: `live`

## Zweck

Bewertet offizielle Microsoft-Preis-, Packaging-, SKU-, Produktlebenszyklus- und Renewal-Ereignisse gegen einen angegebenen Bestand und zeigt die Budgetwirkung inklusive visuellem Jahresvergleich.

## Datenquellen

- `data/microsoft_price_events.json`
- `data/microsoft_price_changes.json`
- `data/microsoft_inventory_mapping.json`
- `data/microsoft_price_forecast_rules.json`

## Ergebnis

- gefilterte offizielle Ereignisse
- direkte SKU-Treffer
- Monats- und Jahresdelta
- Renewal-Einordnung
- Forecast getrennt von offiziellen Ereignissen
- Chart von historischem Betrag bis aktuellem Stand

## Pflege

Neue Microsoft-Ereignisse zuerst in `microsoft_price_events.json` pflegen, SKU-bezogene Preiszeilen anschließend in `microsoft_price_changes.json` ergänzen.
