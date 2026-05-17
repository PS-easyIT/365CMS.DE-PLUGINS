# Annual vs. Monthly Commitment Rechner

## Modul

- Registry-Key: `m365-commitment-calculator`
- Route: `/m365-jahresvertrag-vs-monatsvertrag`
- Engine: `CMS_M365CALCULATOR_Commitment_Calculator`
- Template: `templates/page-commitment-calculator.php`
- Status: `live`

## Zweck

Vergleicht Monatslaufzeit, Jahreslaufzeit mit monatlicher Abrechnung und Jahreslaufzeit mit jährlicher Abrechnung. Unterstützt Split-Strategien aus stabilem Jahreskern und flexiblen monatlichen Nutzern.

## Datenquellen

- `data/commitment_pricing.json`
- `data/commitment_assumptions.json`
- `data/commitment_channel_notes.json`

## Ergebnis

- Kosten je Laufzeitmodell
- Overcommitment in Seat-Monaten
- erste Rechnung und Jahresäquivalent
- Split-Empfehlung
- channelbezogene Hinweise

## Pflege

Preisannahmen und Beschaffungskanal-Hinweise getrennt pflegen. Split-Logik bei neuen Vertragsmodellen prüfen.
