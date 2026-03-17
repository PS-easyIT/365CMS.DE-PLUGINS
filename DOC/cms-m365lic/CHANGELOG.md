# Plugin-Dokumentations-Changelog

## 1.4.8

- Adminbereich um getrennte Pflegebereiche für normale Alternativen, `EU-Alternativen` und `Europäische KI- & Copilot-Alternativen` erweitert
- EU-Vergleich und optionale EU-Ausgabe in der Standard-Auswertung auf konfigurierbare Plugin-Settings statt auf starre Code-Listen umgestellt
- Neue EU-Kategorie `KI & Copilot` dokumentiert, damit Copilot-Bedarfe passende europäische Alternativen erhalten

## 1.4.7

- Neue Preissemantik dokumentiert: gepflegte Paketpreise gelten jetzt als Referenz-Monatspreis für `1 Jahr / monatlich (+5%)`
- Admin-Paketübersicht mit zusätzlichen Jahreswerten je Bereich dokumentiert
- Aus gelieferten Jahrespreislisten heruntergerechnete Spezialpreise für vorhandene Katalogprodukte dokumentiert

## 1.4.6

- EU-Vergleich auf denselben 3-Schritt-Wizard mit mehreren Bedarfsgruppen wie die Standard-Auswertung dokumentiert
- Öffentlichen CSRF-Flow für den EU-Vergleich dokumentiert, damit POST-Auswertungen nicht mehr am globalen `form_guard` scheitern
- Standard-Auswertung um getrennte normale/EU-Alternativen und ein Limit pro Kategorie/Bereich dokumentiert

## 1.4.5

- Neue Public-Dokumentation für die Seite `EU-Vergleich` ergänzt
- Vergleichsstrecke zwischen M365-Plänen und europäischen Alternativ-Stacks beschrieben

## 1.4.4

- `ALTERNATIVEN.md` um korrigierte Monats-/Jahrespreise für Dropbox, Slack, Zoho Mail und Zoho Projects ergänzt
- Storage-Kapitel um `Hetzner Storage Share NX11` und `IONOS Managed Nextcloud 3 TB+` als dokumentierte Nextcloud-/Storage-Alternativen erweitert

## 1.4.3

- Neue Datei `ALTERNATIVEN.md` mit allen im Plugin gepflegten Alternativanbietern und Preisen ergänzt
- README um einen Verweis auf die neue Alternativen-Dokumentation erweitert

## 1.2.0

- Echten PDF-Export über den 365CMS-PDF-Stack dokumentiert
- 3-Schritt-Wizard mit separatem Add-on-/Security-Schritt dokumentiert
- Neue Entra- und Defender-Erweiterungen im Seed-Katalog dokumentiert
- Präzisere Terminalserver-Begründung und aufgelockerte Public-UI dokumentiert

## 1.1.1

- EUR als einheitliche Währung für Seed-Katalog, Auswertung, PDF und Adminpflege dokumentiert
- Frontend- und Admin-Feinschliff für bessere Scannability und klarere Preisführung ergänzt
- Neues Bedarfsmerkmal für Terminalserver-/RDS-Anforderungen inklusive Matching auf passende Shared-Activation-SKUs dokumentiert

## 1.1.0

- Spezial-User-Zuweisung, Spezialseiten-Freigabe und Bereichs-Defaults dokumentiert
- Billing-Modelle mit Aufschlägen (+5% / +20%) für Laufzeit & Zahlung ergänzt
- Vollständig bepreister Seed-Katalog und neue Preisbasis (`pro Benutzer` vs. `Fixpreis`) dokumentiert

## 1.0.3

- Public-Rechner auf festen Public-Kontext ohne Preisumschalter umgestellt
- Member- und Spezialrechner als geschützte 365CMS-Login-Bereiche dokumentiert
- PDF-/Auswertungs-Kontext auf serverseitige Scope-Ableitung umgestellt

## 1.0.2

- CSRF-Handling für öffentliche POST-Auswertungen an den Core-Router angepasst
- Seed-Preis-Synchronisierung für Bestandsinstallationen korrigiert
- Startpreise für häufige Business-/Teams-SKUs ergänzt und fehlende Preiswarnung konkretisiert

## 1.0.0

- Initiale Plugin-Dokumentation für `cms-m365lic` erstellt
- README, DATABASE, HOOKS und API ergänzt
