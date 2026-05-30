# Archive Mailbox Rechner

## Modul

- Registry-Key: `m365-archive-mailbox`
- Route: `/m365-archive-mailbox-rechner`
- Engine: `CMS_M365CALCULATOR_Archive_Mailbox_Calculator`
- Template: `templates/page-archive-mailbox-calculator.php`
- Status: `live`

## Zweck

Bewertet Archivbedarf, Auto-expanding Archive, Shared-/Resource-Mailbox-Sonderfälle, Hold-/Purview-Kontexte und erwartetes Mailboxwachstum.

## Datenquellen

- `data/archive_mailbox_plans.json`
- `data/archive_mailbox_assumptions.json`

## Ergebnis

- Archiv-Eignung
- Plan- oder Add-on-Empfehlung
- Wachstumsprojektion
- Compliance- und Hold-Hinweise
- sharebare Parameter

## Pflege

Kapazitäten und Add-on-Logik in den Plankatalogen aktualisieren. Annahmen zu Wachstum und Provisionierung getrennt halten.
