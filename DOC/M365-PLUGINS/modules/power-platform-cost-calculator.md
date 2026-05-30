# Power Platform Kosten-Kalkulator

## Modul

- Registry-Key: `power-platform-cost-calculator`
- Route: `/power-platform-kosten-kalkulator`
- Engine: `CMS_M365CALCULATOR_Power_Platform_Cost_Calculator`
- Template: `templates/page-power-platform-cost-calculator.php`
- Status: `live`
- Version: `1.27.0`

## Zweck

Bewertet Power-Platform-Szenarien über Apps, Flows, RPA, Bots/Agents, Power Pages, Dataverse, Governance, Security, ALM, Performance und Betrieb hinweg. Das Modul trennt enthaltene Microsoft-365-/Teams-Rechte von Premium-, PAYG-, Capacity- und Architekturpfaden und liefert eine Management-taugliche Empfehlung mit Kostenblöcken plus Well-Architected-Review.

## Datenquellen

- `data/power_platform_products.json`
- `data/power_platform_use_cases.json`
- `data/power_platform_connector_rules.json`
- `data/power_platform_capacity_catalog.json`
- `data/power_platform_governance_rules.json`

## Leitplanken

- Seeded Microsoft-365-/Teams-Rechte gelten nur für Standard-Connectoren und Microsoft-365-nahe Szenarien.
- Premium-, Custom- und On-Premises-Connectoren treiben Standalone-Power-Platform-Rechte.
- Power Apps per app ist ein Einstiegspfad für klar abgegrenzte App- oder Website-Szenarien.
- Power Apps Premium passt bei mehreren Apps, breitem Maker-Portfolio, produktivem Dataverse oder Premium-Connectoren.
- Power Automate Premium deckt User-basierte Premium-Flows, Custom Connectoren und attended RPA ab.
- Process und Hosted RPA werden als Capacity-Pfade für zentrale oder unbeaufsichtigte Automatisierung modelliert.
- Power Pages wird nach Website-Kapazität für angemeldete und anonyme Nutzer geplant.
- Dataverse for Teams ist ein Sonderpfad für Teams-nahe Lösungen mit Grenzen bei Datenmenge, AI, Desktop-Flows und Nutzung außerhalb Teams.
- Credits, Requests, Dataverse Storage und Process Mining werden als eigene Kostenarten ausgewiesen.
- Request-Kontingente, Tageslast und sehr hohe Fünf-Minuten-Lastspitzen werden gegen offizielle Power-Platform-Grenzen eingeordnet.
- Dataverse Database/File/Log, Suchindex, Umgebungskapazität sowie 85-/95-Prozent-Schwellen werden als Capacity-Planung berücksichtigt.
- Governance-Treiber wie Managed Environments, CMK, Customer Lockbox und vNet führen zu Architekturprüfung.
- Microsoft-Well-Architected-Leitplanken bewerten Security Baseline, Identität, Zugangsdaten, Datenrichtlinien, Managed Environments, ALM, Operations, Performance-Ziele und Datenlebenszyklus.
- Advanced Connector Policies werden als granularer Allowlist-Pfad bewertet; Custom- und HTTP-Pfade brauchen weiterhin zusätzliche Steuerung.
- ALM bewertet getrennte Umgebungen, Managed Solutions, Source Control, CI/CD, Stage-and-upgrade und Lösungsschichten.
- Performance bewertet numerische Ziele, produktionsnahe Tests, Monitoring, Datenmodell, Caching, Batch-Verarbeitung, Archivierung und Bereinigung.

## Eingaben

- Use Case, Nutzer, Maker, Umgebungen, Apps je Nutzer und Szenarioanzahl
- Betrachtungszeitraum und Nutzungsmuster
- Connector-Typ, Flow-Kontext, RPA-Modus, Bot-/Agent-Umfang
- Copilot Credits, Flow Runs und Requests
- Website-Zugriff, angemeldete und anonyme Website-Nutzer
- Dataverse Database/File/Log GB und Process-Mining-Volumen
- Teams-only, Dataverse for Teams, Nutzung außerhalb Teams
- AI Builder, Managed Environments, erweiterte Governance und Azure-Abrechnung
- Umgebungsstrategie, Datenrichtlinien, Identität/Rollen und Zugangsdaten
- ALM-/Deployment-Reife, Monitoring, Performance-Ziele und Datenlebenszyklus

## Ergebnis

- Empfehlungskategorie und Begründung
- Monats-, Jahres- und Zeitraumkosten
- größter Kostentreiber
- Seeded-Fit und Dataverse-for-Teams-Fit
- Kostenblöcke nach User-, App-, Bot-, Website-, Credit-, Request-, Storage- und Capacity-Anteilen
- Score mit erklärenden Gründen
- Best-Practice-Score für Security, ALM, Performance, Datenrichtlinien, Betrieb, Request-Last und Dataverse-Kapazitätsplanung
- Prüfpunkte mit Status, Bewertung, nächstem Schritt und Microsoft-Learn-Quelle
- Warnungen, nächste Schritte, Quellenstand und Microsoft-Learn-Quellen

## Empfehlungskategorien

- Seeded Microsoft-365-/Teams-Rechte reichen aus
- Günstiger Einstieg via Per App oder PAYG sinnvoll
- Per-User-Premium empfohlen
- Capacity-/Bot-/Website-Modell erforderlich
- Dataverse-/Governance-/Architektur-Review nötig
- Best-Practice-Reife gut
- Best-Practice-Reife prüfen
- Best-Practice-Reife kritisch

## Public-Verhalten

Die Route berechnet ausschließlich aus Anfrageparametern und speichert keine Eingaben serverseitig. Die Ergebnisansicht bleibt fachlich und zeigt keine technischen Prüfmechanismen an.

## Pflege

- Microsoft-Listenpreise regelmäßig gegen Learn-, Product-Terms- und Vertragsstände prüfen
- CSP-, EA-, MCA- oder Partnerkonditionen in den Katalogen ersetzen
- Request-, Storage-, Credit-, Dataverse-Capacity- und PAYG-Werte quartalsweise aktualisieren
- Globale Paketpreis- und Laufzeitdefaults aus den Adminbereichen `Paketpreise` und `Abopreise & Laufzeiten` bei der fachlichen Pflege berücksichtigen
- Dataverse-for-Teams- und Governance-Regeln bei Microsoft-Änderungen nachführen
- Copilot-Studio- und AI-Builder-Preismodelle getrennt beobachten
- Well-Architected-, Datenrichtlinien-, Managed-Environment-, ALM- und Performance-Quellen regelmäßig gegen Microsoft Learn prüfen
