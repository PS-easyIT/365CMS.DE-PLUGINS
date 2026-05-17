# Shared-Mailbox vs. Lizenz-Rechner

## Modul

- Registry-Key: `shared-mailbox`
- Route: `/shared-mailbox-vs-lizenz`
- Engine: `CMS_M365CALCULATOR_Shared_Mailbox_Calculator`
- Template: `templates/page-shared-mailbox.php`
- Status: `live`

## Zweck

Entscheidet, ob eine Mailbox als lizenzfreie Shared Mailbox betrieben werden kann oder eine Benutzer-/Archiv-/Compliance-Lizenz benötigt. Bewertet Größe, Loginbedarf, Archiv, Hold, Apps und technische Sonderfälle.

## Datenquellen

- `data/shared_mailbox_rules.json`
- `data/mailbox_license_matrix.json`
- `data/shared_mailbox_scenarios.json`
- `data/pricing.json`

## Ergebnis

- Lizenzentscheidung
- Kostenannahme
- Regel- und Szenariohinweise
- Upgrade- oder Add-on-Pfade
- sharebare Parameter

## Pflege

Regeln und Szenarien fachlich getrennt halten. Preise in `pricing.json` aktualisieren.
