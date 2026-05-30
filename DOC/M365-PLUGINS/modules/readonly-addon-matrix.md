# M365 Add-on-Matrix

## Modul

- Registry-Key: `m365-addon-matrix`
- Route: `/m365-addon-matrix`
- Engine: `CMS_M365CALCULATOR_ReadOnly_Matrices`
- Template: `templates/page-readonly-addon-matrix.php`
- Status: `live`

## Zweck

Statische Gesamtübersicht wichtiger Microsoft-365-Add-ons nach Bereichen wie Exchange, SharePoint/OneDrive, Teams, Copilot, Intune, Entra ID, Defender, Purview und Power Platform.

## Datenquellen

- `data/readonly_addon_matrix.json`

## Ergebnis

- Add-on-Gruppen mit Paketen nebeneinander
- Voraussetzungen und Funktionshinweise
- Quellen- und Pflegehinweise

## Pflege

Add-ons nach Fachbereich gruppieren und volatile Produktnamen im Katalog pflegen.

Ab `1.29.3` werden Headertexte, Buttontexte, Buttonziele, CTA, Bereichsheader, Paketkarten, Hinweis-/Quellenbereiche und Design der Public-Seite im Admin-Unterpunkt `Matrixen` gepflegt. Der Tab `Add-on-Matrix` enthält die bereichsspezifischen Inhalte; der Tab `Design` liefert gemeinsame Defaults für Contentheader, Buttons, Farben und Bereiche außerhalb der eigentlichen Tabellen.
