# Power Platform Kosten-Kalkulator

## Modul

- Registry-Key: `power-platform-cost-calculator`
- Route: `/power-platform-kosten-kalkulator`
- Engine: `CMS_M365CALCULATOR_Power_Platform_Cost_Calculator`
- Template: `templates/page-power-platform-cost-calculator.php`
- Status: `live`
- Version: `1.21.0`

## Zweck

Bewertet Power-Platform-Szenarien über Apps, Flows, RPA, Bots/Agents, Power Pages, Dataverse und Governance hinweg. Das Modul trennt enthaltene Microsoft-365-/Teams-Rechte von Premium-, PAYG-, Capacity- und Architekturpfaden und liefert eine Management-taugliche Empfehlung mit Kostenblöcken.

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
- Governance-Treiber wie Managed Environments, CMK, Customer Lockbox und vNet führen zu Architekturprüfung.

## Eingaben

- Use Case, Nutzer, Maker, Umgebungen, Apps je Nutzer und Szenarioanzahl
- Betrachtungszeitraum und Nutzungsmuster
- Connector-Typ, Flow-Kontext, RPA-Modus, Bot-/Agent-Umfang
- Copilot Credits, Flow Runs und Requests
- Website-Zugriff, angemeldete und anonyme Website-Nutzer
- Dataverse Database/File/Log GB und Process-Mining-Volumen
- Teams-only, Dataverse for Teams, Nutzung außerhalb Teams
- AI Builder, Managed Environments, erweiterte Governance und Azure-Abrechnung

## Ergebnis

- Empfehlungskategorie und Begründung
- Monats-, Jahres- und Zeitraumkosten
- größter Kostentreiber
- Seeded-Fit und Dataverse-for-Teams-Fit
- Kostenblöcke nach User-, App-, Bot-, Website-, Credit-, Request-, Storage- und Capacity-Anteilen
- Score mit erklärenden Gründen
- Warnungen, nächste Schritte, Quellenstand und Microsoft-Learn-Quellen

## Empfehlungskategorien

- Seeded Microsoft-365-/Teams-Rechte reichen aus
- Günstiger Einstieg via Per App oder PAYG sinnvoll
- Per-User-Premium empfohlen
- Capacity-/Bot-/Website-Modell erforderlich
- Dataverse-/Governance-/Architektur-Review nötig

## Public-Verhalten

Die Route berechnet ausschließlich aus Anfrageparametern und speichert keine Eingaben serverseitig. Die Ergebnisansicht bleibt fachlich und zeigt keine technischen Prüfmechanismen an.

## Pflege

- Microsoft-Listenpreise regelmäßig gegen Learn-, Product-Terms- und Vertragsstände prüfen
- CSP-, EA-, MCA- oder Partnerkonditionen in den Katalogen ersetzen
- Request-, Storage-, Credit- und PAYG-Werte quartalsweise aktualisieren
- Dataverse-for-Teams- und Governance-Regeln bei Microsoft-Änderungen nachführen
- Copilot-Studio- und AI-Builder-Preismodelle getrennt beobachten
