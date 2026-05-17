# M365 Lizenzmatrix

## Modul

- Registry-Key: `m365-lizenzmatrix`
- Route: `/m365-lizenzmatrix`
- Engine: `CMS_M365CALCULATOR_ReadOnly_Matrices`
- Template: `templates/page-readonly-suite-matrix.php`
- Status: `live`

## Zweck

Statische Gesamtübersicht der Microsoft-365-Vollpakete. Die Matrix zeigt wichtige Planungswerte für Business-, Enterprise- und ausgewählte Spezialpläne ohne interaktive Eingabe.

## Datenquellen

- `data/readonly_suite_matrix.json`

## Ergebnis

- Paketvergleich nebeneinander
- Mailbox-, Archiv-, SharePoint-, OneDrive-, Intune-, Entra-, Defender- und Purview-Details
- Quellen- und Pflegehinweise

## Pflege

Neue Suite-Werte direkt im JSON-Katalog ergänzen. Die Engine normalisiert nur Anzeige- und Matrixstruktur.
