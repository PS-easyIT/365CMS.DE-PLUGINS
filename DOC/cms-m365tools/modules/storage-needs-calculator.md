# M365 Storage-Bedarfs-Rechner

## Modul

- Registry-Key: `m365-storage-needs-calculator`
- Route: `/m365-storage-bedarfsrechner`
- Engine: `CMS_M365CALCULATOR_Storage_Needs_Calculator`
- Template: `templates/page-storage-needs-calculator.php`
- Status: `live`
- Version: `1.19.0`

## Zweck

Berechnet den Microsoft-365-Storage-Bedarf getrennt nach SharePoint-Dateispeicher, OneDrive-Benutzerspeicher, Exchange-Postfächern und Exchange-Archiv. Das Modul macht sichtbar, ob Wachstum, Puffer, Cleanup-Potenzial oder Zusatzspeicher den nächsten Engpass bestimmen.

## Datenquellen

- `data/sharepoint_storage_rules.json`
- `data/onedrive_quota_presets.json`
- `data/exchange_storage_rules.json`
- `data/storage_growth_assumptions.json`

## Microsoft-Leitplanken

- SharePoint-Tenant-Pool: 1 TB plus 10 GB je qualifizierter Lizenz
- einzelne SharePoint-Site: bis 25 TB modelliert
- Zusatzspeicher: in 1-GB-Schritten modelliert
- Einzeldatei in SharePoint/OneDrive: bis 250 GB
- Sync-Performance: 300.000 Elemente als wichtiger Richtwert, 1.000.000 als Preview-Grenze je Sync-Instanz
- OneDrive: Standardannahme 1 TB je Nutzer, in geeigneten Umgebungen bis 5 TB je Nutzer
- Exchange-Primärpostfach: 50 GB oder 100 GB je nach Planpfad
- Exchange-Archiv: 50 GB, 100 GB oder Auto-expanding bis 1,5 TB

## Eingaben

- qualifizierte Lizenzen und Nutzerzahl
- aktueller SharePoint-Verbrauch, größte Site und Anzahl Sites
- OneDrive-Nutzer, durchschnittlicher OneDrive-Bedarf und Quota je Nutzer
- synchronisierte Elemente und größte Einzeldatei
- Mailboxanzahl, durchschnittliche und größte Mailboxgröße
- Primärpostfachgröße, Archivanteil, durchschnittliche Archivgröße und Archivmodell
- jährliches Wachstum, Betrachtungszeitraum, Planungspuffer und Cleanup-Potenzial
- Zusatzspeicherpreis je GB und Monat als Pflege-/Planungswert

## Ergebnis

- Kapazitätsstatus mit Score und Empfehlungskategorie
- Zusatzspeicherbedarf für SharePoint in GB-Schritten mit Monats- und Jahresannahme
- Bereichs-KPIs für SharePoint, OneDrive, Exchange und Archiv
- Microsoft-Leitplanken-Tabelle für Pool, Site, Sync, Datei, OneDrive und Archiv
- Detailwerte zu Forecast, Kapazität, Cleanup-Wirkung und größtem Engpass
- Quellenstand und verlinkte Microsoft-Quellen

## Empfehlungskategorien

- Ausreichend dimensioniert
- Beobachten und Wachstum einplanen
- Zusatzspeicher oder Archivstrategie nötig
- Akutes Kapazitätsrisiko
- Governance- und Cleanup-Potenzial zuerst nutzen

## Public-Verhalten

Die Route berechnet ausschließlich aus Anfrageparametern und speichert keine Eingaben serverseitig. Die Ergebnisansicht enthält keine Besucherhinweise zu Formularprüfmechanismen.

## Pflege

- Microsoft-Grenzen regelmäßig gegen Microsoft Learn prüfen
- Zusatzspeicherpreis je Tenant, Vertrag oder Partnerangebot nachpflegen
- OneDrive-Quota-Presets mit realer Tenant-Einstellung abgleichen
- Cleanup-Potenzial und Wachstumsraten mit Bestandsdaten schärfen
- Sync-Richtwerte bei Microsoft-Änderungen aktualisieren
