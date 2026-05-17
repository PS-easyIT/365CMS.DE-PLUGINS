# Teams Phone-Lizenz-Berater

## Modul

- Registry-Key: `teams-phone-advisor`
- Route: `/teams-phone-lizenzberater`
- Engine: `CMS_M365CALCULATOR_Teams_Phone_Advisor`
- Template: `templates/page-teams-phone-advisor.php`
- Status: `live`

## Zweck

Empfiehlt ein passendes Teams-Phone-Zielbild aus Calling Plan, Operator Connect, Direct Routing, Mischmodell oder Architektur-Review. Bewertet Lizenzbasis, PSTN-Modell, Länderannahmen und technische Voraussetzungen.

## Datenquellen

- `data/teams_phone_base_eligibility.json`
- `data/teams_pstn_model_rules.json`
- `data/teams_country_availability.json`
- `data/teams_voice_providers.json`
- `data/teams_direct_routing_requirements.json`
- `data/teams_phone_cost_assumptions.json`

## Ergebnis

- empfohlener PSTN-Pfad
- Add-on- und Lizenzbedarf
- Direct-Routing-Voraussetzungen
- Shared-Calling-Hinweise
- Kostenrahmen und nächste Schritte

## Pflege

Länder- und Providerannahmen regelmäßig prüfen. Direct-Routing-Anforderungen getrennt von Kostenannahmen pflegen.
