# M365 Backup-Kosten-Rechner

## Modul

- Registry-Key: `m365-backup-cost-calculator`
- Route: `/m365-backup-kostenrechner`
- Engine: `CMS_M365CALCULATOR_Backup_Cost_Calculator`
- Template: `templates/page-backup-cost-calculator.php`
- Status: `live`
- Version: `1.18.0`

## Zweck

Berechnet eine belastbare Microsoft-365-Backup-Baseline auf Basis geschützter Datenmenge und vergleicht diese mit manuell gepflegten Providerdaten. Der Microsoft-Teil bleibt quellenbasiert, während Drittanbieterwerte getrennt gepflegt und sichtbar als Vergleichsannahmen behandelt werden.

## Datenquellen

- `data/microsoft_backup_baseline.json`
- `data/backup_providers.json`
- `data/backup_comparison_rules.json`

## Microsoft-Baseline

- Pay-as-you-go-Modell mit 0,15 USD pro geschütztem GB und Monat
- Restore ohne separate Restore-Gebühr laut Microsoft Learn
- Workloads: Exchange Online, OneDrive und SharePoint
- Teams-Dateien werden teilweise über SharePoint und OneDrive betrachtet
- Retention: 1 Jahr
- Restore-Performance: je nach Szenario bis zu 1-3 TB pro Stunde für große Wiederherstellungen
- Daten bleiben laut Microsoft innerhalb der Microsoft-365-Datenvertrauensgrenze; begrenzte Metadaten werden für Billing nach Azure übergeben

## Providervergleich

Der Katalog enthält Microsoft 365 Backup als offizielle Baseline und manuell gepflegte Vergleichswerte für:

- Veeam Backup for Microsoft 365
- AvePoint Cloud Backup
- Backupify / Datto SaaS Protection
- Afi.ai Microsoft 365 Backup

Drittanbieterpreise, Retention und Feature-Tiefe sind Pflegewerte. Sie müssen vor Angeboten oder Beschaffung mit dem jeweiligen Anbieter abgeglichen werden.

## Eingaben

- Nutzer / Postfächer
- Exchange-, OneDrive-, SharePoint- und Teams-Dateimengen in GB
- gelöschte oder versionierte Daten in GB
- Schutzanteil in Prozent
- Wachstum in 12 Monaten
- gewünschte Aufbewahrung in Monaten
- Restore-Tiefe
- Trust-Boundary-Gewichtung
- gewünschtes Betriebsmodell
- Provider-Auswahl

## Ergebnis

- Microsoft-Baseline pro Monat und Jahr
- geschützte GB und Projektion mit Wachstum
- günstigster Anbieter
- stärkster Score
- Providervergleich nach Kosten, Workload-Abdeckung, Retention, Restore-Tiefe und Betriebsmodell
- Detailkarten mit Stärken und Grenzen pro Anbieter
- Empfehlungskategorie
- FAQ und Quellenstand

## Empfehlungskategorien

- Microsoft 365 Backup ausreichend und wirtschaftlich
- Microsoft 365 Backup plus Partnerlösung prüfen
- Drittanbieter wirtschaftlicher oder funktional vollständiger
- Hybrid-Modell sinnvoll
- Vergleichsdaten unvollständig – manuelle Prüfung nötig

## Public-Verhalten

Die Route berechnet ausschließlich aus Anfrageparametern und speichert keine Eingaben serverseitig. Die Ergebnisansicht enthält keine Besucherhinweise zu Formularprüfmechanismen.

## Pflege

- Microsoft-Baseline regelmäßig gegen Microsoft Learn prüfen
- Drittanbieterpreise und Feature-Tiefe quartalsweise manuell nachpflegen
- Teams- und Spezialworkloads bewusst separat kennzeichnen
- Scoring-Gewichte in `backup_comparison_rules.json` an Projektpraxis anpassen