# Copilot Lizenz-Pflicht-Checker

## Modul

- Registry-Key: `copilot-license-check`
- Route: `/copilot-lizenz-check`
- Engine: `CMS_M365CALCULATOR_Copilot_License_Checker`
- Template: `templates/page-copilot-license-check.php`
- Status: `live`

## Zweck

Prüft, ob eine vorhandene Basislizenz für Copilot geeignet ist und welche Upgrade- oder Zusatzpfade für Copilot Chat, Microsoft 365 Copilot und technische Readiness erforderlich sind.

## Datenquellen

- `data/copilot_eligibility_matrix.json`
- `data/copilot_technical_prerequisites.json`
- `data/license_upgrade_paths.json`

## Ergebnis

- Copilot-Fähigkeitsstatus
- erforderlicher Upgrade-Pfad
- technische Voraussetzungen
- Lizenz- und Readiness-Hinweise
- nächste Schritte

## Pflege

Eligibility Matrix und technische Voraussetzungen synchron mit Microsoft-Produktänderungen halten. Upgrade-Pfade separat pflegen.
