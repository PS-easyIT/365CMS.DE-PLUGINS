# M365-Lizenzvergleich

## Modul

- Registry-Key: `m365-lizenzvergleich`
- Route: `/m365-lizenzvergleich`
- Engine: `CMS_M365CALCULATOR_License_Comparison`
- Template: `templates/page-license-comparison.php`
- Status: `live`

## Zweck

Filterbare Vergleichstabelle für Microsoft-365-Lizenzen mit Desktop-Apps, Exchange, SharePoint, Teams, Security, Compliance, Power Platform und Copilot-relevanten Merkmalen.

## Datenquellen

- `data/plan_comparison_feature_matrix.json`
- `data/plan_comparison_badges.json`
- `data/plan_comparison_notes.json`

## Ergebnis

- Planvergleich mit auswählbaren Spalten
- Feature-Status und Badges
- Differenzmarkierungen
- Notizen und Quellenstand

## Pflege

Feature-Matrix bei Produktänderungen aktualisieren. Badges und Notizen separat pflegen, damit Vergleichslogik stabil bleibt.
