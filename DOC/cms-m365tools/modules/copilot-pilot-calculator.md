# Copilot Pilot-Phase-Rechner

## Modul

- Registry-Key: `copilot-pilot-calculator`
- Route: `/copilot-pilot-rechner`
- Engine: `CMS_M365CALCULATOR_Copilot_Pilot_Calculator`
- Template: `templates/page-copilot-pilot-calculator.php`
- Status: `live`

## Zweck

Empfiehlt Pilotgröße, Dauer, Budget, Champion-Bedarf, Readiness-Schritte und Rollout-Zeitplan für Microsoft 365 Copilot. Das Modul berücksichtigt bewusst Governance, Datenhygiene, Oversharing, Feedback und Messbarkeit – nicht nur Lizenzkosten.

## Datenquellen

- `data/copilot_pilot_sizes.json`
- `data/copilot_rollout_templates.json`
- `data/copilot_readiness_checklist.json`

## Ergebnis

- empfohlene Pilotgröße
- Laufzeit und Budgetrahmen
- Champion-Bedarf
- Readiness-Score
- Governance-/Preflight-Checkliste vor Pilotstart
- Rollout-Timeline
- Messkriterien und FAQ-Bausteine
- nächste Schritte

## Quellenstand

Stand der Quellenprüfung: `2026-05-17`.

Berücksichtigte Microsoft-Quellen:

- Microsoft 365 Copilot adoption guide and overview for IT admins
- Configure a secure and governed foundation for Microsoft 365 Copilot
- License options for Microsoft 365 Copilot
- Microsoft 365 app and network requirements for Microsoft 365 Copilot
- Set up Microsoft 365 Copilot and assign licenses
- Microsoft 365 Copilot reporting options for admins
- Welcome users, create organizational messages, and enable feedback for Microsoft 365 Copilot
- Microsoft 365 Copilot data and compliance readiness
- Microsoft Adoption Hub für Copilot-Ressourcen

## Public-Verhalten

Die Route arbeitet mit sharebaren Anfrageparametern und berechnet die Empfehlung direkt beim Aufruf. Es werden keine Eingaben serverseitig gespeichert. Die Public-Seite enthält keine Sicherheits- oder Formular-Meldungen für Besucher.

## Pflege

Pilotgrößen und Rollout-Templates an Erfahrungswerte aus Projekten anpassen. Readiness-Kriterien mit Copilot-Anforderungen synchron halten. FAQ- und Preflight-Bausteine werden in `copilot_readiness_checklist.json` gepflegt.
