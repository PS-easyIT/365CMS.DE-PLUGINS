# Changelog

## 1.4.5 – 2026-03-17

- Neue Public-Site `EU-Vergleich` ergänzt, um Microsoft-365-Pläne mit europäischen All-in-One- und Best-of-Breed-Anbietern zu vergleichen
- Öffentliche Navigation um einen eigenen Menüpunkt `EU-Vergleich` neben der regulären Auswertung erweitert
- Vergleichslogik für M365 vs. europäische Anbieter mit Summen für `Gesamtkosten M365`, `Gesamtkosten Alternativen` und Preisdelta ergänzt

## 1.4.4 – 2026-03-17

- Alternativanbieter anhand der nachgereichten Korrekturliste ergänzt und fehlende Monats-/Jahrespreise für Dropbox, Slack, Zoho Mail und Zoho Projects nachgezogen
- Storage-Vergleich um Nextcloud-nahe Optionen erweitert, darunter `Hetzner Storage Share NX11` und `IONOS Managed Nextcloud 3 TB+`
- Plugin-Dokumentation und Seed-Daten wieder auf denselben Alternativen-Stand synchronisiert

## 1.4.3 – 2026-03-17

- Preisbasis der Microsoft-Produkte im Paketkatalog gemäß bereitgestellter Referenzliste aus dem Anhang aktualisiert
- Betroffen sind Exchange, SharePoint, OneDrive, Microsoft 365/Office 365, Teams, Copilot, Intune, Power Platform, Project, Entra und Defender
- Öffentliche Seed-Preise für die Auswertung damit an den aktuellen gewünschten Katalogstand angeglichen

## 1.4.2 – 2026-03-17

- Kuratierte Standardliste für Alternativanbieter je Bereich ergänzt: `Mail`, `Office & Produktivität`, `Storage & Dateien`, `Zusammenarbeit & Meetings`, `Projektmanagement`, `Identität & Sicherheit`
- Seed-Daten mit 3 bis 6 Vergleichseinträgen je Bereich auf Basis offiziell recherchierter Pricing-Seiten von Google, Zoho, Proton, Dropbox, Slack, Asana und MeisterTask ergänzt
- Leere Bestandsinstallationen werden bei der Plugin-Initialisierung automatisch mit der neuen Alternativen-Liste vorbefüllt, ohne manuell gepflegte Admin-Einträge zu überschreiben

## 1.4.1 – 2026-03-17

- Optionalen Alternativanbieter-Block unter der M365-Auswertung ergänzt; Aktivierung erfolgt direkt im Rechner per Checkbox neben der Laufzeit
- Neuen Admin-Tab `Alternativen` eingebaut, um Jahres- und Monatspreise je Kategorie/Anbieter separat zu pflegen
- Alternativpreise bewusst ohne prozentuale Billing-Aufschläge umgesetzt: Jahresmodelle nutzen den gepflegten Jahrespreis, Monatslaufzeit den expliziten Monatspreis

## 1.2.0 – 2026-03-15

- PDF-Export auf echten PDF-Download über den 365CMS-PDF-/Dompdf-Stack umgestellt; der frühere HTML-Fallback wird nicht mehr als Scheindownload ausgeliefert
- Public-Wizard von 2 auf 3 Schritte erweitert: `Quick Check`, `Advanced / Expertenoptionen` und `Add-ons & Security`
- Add-on-Katalog um `Microsoft Entra ID P1/P2`, `Defender for Business`, `Defender for Office 365 Plan 1/2` sowie `Defender for Endpoint Plan 1/2` ergänzt und mit EUR-Referenzwerten vorbelegt
- Ergebnis-Erklärungen für Terminalserver-/RDS-Anforderungen präzisiert, damit die Wahl einer Shared-Activation-fähigen Lizenz nachvollziehbar begründet wird
- Public-Frontend räumlicher, freundlicher und guideline-näher gestaltet, ohne die kompakte Bedarfsanalyse aufzugeben

## 1.1.1 – 2026-03-15

- Standardwährung, Seed-Katalog und Darstellung durchgängig auf Euro (EUR / €) vereinheitlicht
- Public-/Member-Frontend mit Hero-Pills, KPI-Karten, klarerer Ergebnishierarchie und besser lesbarer Preispräsentation verfeinert
- Admin-Dashboard, Spezial-User-Ansicht sowie Paket-/Settings-Formulare visuell gestrafft und auf EUR-only-Verwaltung ausgerichtet
- Public-Rechner um das Bedarfsmerkmal `Terminalserver / Shared Activation` erweitert, damit RDS-/Terminalserver-Szenarien gezielt auf passende Enterprise-/SCA-fähige SKUs gematcht werden

## 1.1.0 – 2026-03-15

- Spezial-User-Verwaltung im Admin ergänzt und Spezialseite nur noch für explizit zugewiesene 365CMS-Benutzer freigeschaltet
- Laufzeit-/Zahlungslogik mit drei Modellen eingebaut: jährlich, jährlich monatlich (+5%) und monatlich (+20%)
- Seed-Katalog vollständig mit Startpreisen befüllt und um `pricing_basis` für Benutzer- vs. Fixpreis-SKUs erweitert
- Paketverwaltung, Dashboard, PDF und Frontend auf neue Billing-Logik und Spezialzugriffe umgestellt

## 1.0.3 – 2026-03-15

- Öffentliche Rechnerseite fest auf den Public-Preiskontext verdrahtet; der Preis-Kontext ist im Public-Formular nicht mehr umschaltbar
- Geschützte Member- und Spezialbereiche über den 365CMS-Mitgliederbereich ergänzt (`/member/plugin/m365-license` und `/member/plugin/m365-license-special`)
- Auswertung und PDF-Export leiten den Preiskontext nun serverseitig aus dem Zugriffsbereich ab statt aus manipulierbaren Formularfeldern

## 1.0.2 – 2026-03-15

- Public-POST-CSRF-Flow an den globalen `form_guard` des Routers angepasst
- Seed-Preis-Synchronisierung für Bestandsinstallationen korrigiert, sodass neue Seed-Preise nicht mehr von alten `null`-Werten blockiert werden
- Startpreise für häufige Business-/Teams-Pakete ergänzt und fehlende Preiswarnung im Frontend präzisiert

## 1.0.1 – 2026-03-15

- Public-Rendering auf korrekte Theme-Header/Footer-Integration umgestellt
- Seed-Katalog um zusätzliche Microsoft-365-, Teams-, SharePoint-, OneDrive-, Visio- und Project-Pakete erweitert
- Enthaltene Services für bestehende Seed-Pakete ergänzt und Seed-Synchronisierung für Bestandsinstallationen verbessert

## 1.0.0 – 2026-03-15

- Erstes Release des CMS M365 License Plugins
- Seed-Katalog für Microsoft 365 Basislizenzen, Frontline, Copilot und Add-ons
- Adminbereich für Paketverwaltung und Konfiguration
- Öffentliche Bedarfsanalyse mit festem Public-Kontext
- PDF-Export und Tageslimit-Logik
