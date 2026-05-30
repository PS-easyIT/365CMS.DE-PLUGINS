# Frontline Worker Lizenz-Eignung-Check

## Modul

- Registry-Key: `frontline-worker-license-check`
- Route: `/frontline-worker-lizenz-check`
- Engine: `CMS_M365CALCULATOR_Frontline_Worker_Check`
- Template: `templates/page-frontline-worker-check.php`
- Status: `live`

## Zweck

Prüft, ob Nutzergruppen realistisch mit Microsoft 365 F1 oder F3 statt Business Premium, E3 oder E5 arbeiten können. Bewertet Rollenprofil, Gerätemodell, App-Bedarf und Zugriffsszenario.

## Datenquellen

- `data/frontline_user_type_matrix.json`
- `data/frontline_plan_matrix.json`
- `data/frontline_industry_presets.json`

## Ergebnis

- F1-/F3-/Enterprise-Eignung
- Mischmodell-Empfehlung
- Geräte- und App-Hinweise
- Sparpotenzial
- nächste Prüfschritte

## Pflege

Branchenpresets und Rollenprofile nach realen Kundenszenarien nachschärfen. Planmatrix bei Lizenzänderungen aktualisieren.
