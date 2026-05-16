# EU Data Boundary Impact- / Aufpreis-Checker

- **Priorität:** hoch
- **Datenquelle:** Microsoft Learn EU Data Boundary + manuelle Regel- und Annahmedateien
- **Aufwand:** mittel bis hoch
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/eu-data-boundary-impact-checker`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Wichtige Korrektur zum bisherigen Dateinamen: Die offizielle Microsoft-Dokumentation beschreibt die **EU Data Boundary primär als Scope-, Residency-, Transfer- und Konfigurationsthema** – **nicht** als pauschalen Lizenzaufschlag.

Das Tool soll deshalb belastbar beantworten:

1. **Ist der Kunde / Tenant / Workload überhaupt in Scope der EU Data Boundary?**
2. **Welche Services sind in Scope, welche nicht, und welche Sonderregeln gelten?**
3. **Welche Datenübertragungen außerhalb der Boundary bleiben trotz EUDB weiterhin möglich oder nötig?**
4. **Wo entstehen echte Projekt-, Betriebs- oder Governance-Aufwände – und wo gerade kein offizieller Microsoft-Aufpreis dokumentiert ist?**
5. **Wann ist das Thema eher ein Architektur- / Compliance-Projekt als ein „Preisrechner“?**

Der Rechner ist damit fachlich eher ein:

- Scope-Checker
- Residency- und Transfer-Impact-Tool
- Governance- und Architektur-Advisor
- optionaler Projektkosten-Schätzer auf Basis **manueller** Annahmen

## Verifizierte Microsoft-Leitplanken

Die folgenden Regeln sollten im Tool als harte Leitplanken hinterlegt werden.

### Die EU Data Boundary ist ein definierter Scope – kein pauschaler Preisaufschlag

- Microsoft beschreibt die EU Data Boundary als **geografisch definierte Boundary**, innerhalb der Customer Data und personenbezogene Daten für bestimmte Enterprise-Online-Services gespeichert und verarbeitet werden.
- Die EUDB-Dokumentation betont ausdrücklich, dass sie den **aktuellen Stand** abbildet und fortlaufend aktualisiert wird.
- Die Dokumentation nennt **begrenzte Umstände**, in denen Daten weiterhin außerhalb der EUDB verarbeitet, übertragen oder remote zugänglich sein können.
- Es wird **kein allgemeiner Lizenzaufschlag** für „EU Data Boundary“ dokumentiert.

### Scope basiert auf Kunde, Dienst und Konfiguration

- Die EU Data Boundary umfasst EU- und EFTA-Länder.
- Für Microsoft 365 gilt: Kunden mit Sign-up-Location in EU/EFTA sind grundsätzlich in Scope.
- Für Dynamics 365 und Power Platform ist die Geo-/Tenant-Bereitstellung in Verbindung mit der Billing-Logik relevant.
- Für Azure müssen regionale Dienste in EU/EFTA-Regionen bereitgestellt werden; bei nichtregionalen Diensten gelten service-spezifische Konfigurationsregeln.

### Microsoft 365 Multi-Geo ist ein klarer Ausschluss-/Sonderfall

- Microsoft dokumentiert eindeutig: **Kunden mit Multi-Geo Capabilities in Microsoft 365 sind nicht in Scope der EU Data Boundary**, selbst wenn ihr Tenant in einem EU-/EFTA-Land geführt wird.
- Multi-Geo ist außerdem ein **lizenzierter Add-on-Pfad** mit eigenen Mindestlizenzierungsregeln und darf nicht mit EUDB verwechselt werden.

### Nicht alles ist in Scope

- **Preview- und Trial-Services** sind laut Microsoft nicht Teil der EUDB.
- **On-Premises-Software und Client-Anwendungen** sind nicht in Scope.
- **Consulting Services** als Professional Services sind nicht in Scope; Microsoft nennt hierfür US-basierte Datacenter als Speicherort.

### Weiterhin mögliche / notwendige Transfers sind Teil des Modells

- Microsoft dokumentiert fortlaufende oder verbleibende Transfers u. a. für:
	- Remote Access durch Microsoft-Personal in Ausnahmefällen
	- kundeninitiierte Transfers
	- DSR-/GDPR-Export- und Löschvorgänge
	- Security Operations / Threat Intelligence
	- Directory Data
	- Network Transit
	- Service Quality / Resiliency / Management
- Diese Transfers sind **kein Fehler des Kunden**, sondern Teil des dokumentierten Betriebsmodells.

### Azure ist häufig ein Konfigurations- statt Preis-Thema

- Viele Azure-Nonregional-Services sind inzwischen für EUDB konfigurierbar.
- Für manche Dienste ist die EUDB-Nutzung an konkrete regionale oder tenantweite Einstellungen gebunden.
- Besonders wichtig: Für `Azure Resource Manager` beschreibt Microsoft einen **tenantweiten EU Data Boundary-Setup-Pfad für neue Tenants**, der nicht für bestehende Tenants mit vorhandenen Subscriptions/Ressourcen gedacht ist und nicht rückgängig gemacht werden kann.

### Sonderfälle mit besonderem Compliance-Hinweis

- Bei `Anthropic models with Microsoft Generative AI Services` beschreibt Microsoft, dass Customer Data in den **USA verarbeitet** wird, während die Speicherung in der EUDB bleibt.
- Support- und Professional-Services-Daten wie Case Titles oder Eskalationsinhalte können außerhalb der EUDB gespeichert werden.

## Ziel des Tools

Hilft Kunden dabei, die EU Data Boundary **realistisch** zu bewerten – als Kombination aus:

- Scope-Prüfung
- Service-/Workload-Klassifikation
- Transfer- und Restrisiko-Einordnung
- Konfigurations- und Governance-Aufwand
- optionaler Projektkostenschätzung auf Basis **manueller Annahmen**, nicht angeblich offizieller Microsoft-Aufpreise

## Gewünschte Ergebnis-Kategorien

Der Checker sollte mindestens diese Zustände unterscheiden:

1. `✅ In Scope – kein offizieller EUDB-Lizenzaufschlag dokumentiert`
2. `🟢 In Scope – Konfigurations- und Governance-Aufgaben nötig`
3. `🟡 Teilweise in Scope – dokumentierte Resttransfers bleiben bestehen`
4. `🟠 Out of Scope oder Architekturänderung nötig`
5. `🔴 Kein seriöser Aufpreis berechenbar ohne manuelle Annahmen`

## Kernfunktionen

- Scope-Check nach Kunde, Region, Dienst und Sonderfällen
- Service-Matrix für Microsoft 365, Dynamics 365, Power Platform, Azure und angrenzende Dienste
- Kennzeichnung verbleibender Transfer-Szenarien
- Erkennung von Ausschlüssen wie `Multi-Geo`, Preview/Trial, Consulting Services
- Konfigurations-Check für Azure-Nonregional- und tenantweite Sonderpfade
- Optionaler Kostenblock nur auf Basis manueller Projekt-/Governance-Annahmen
- Management-taugliche Ergebniszusammenfassung mit Compliance-Hinweisen
- PDF-Export und CTA für Compliance-/Architektur-Workshop

## Eingaben

### Pflichtfelder

- Kundengeografie / Sign-up-Location
- betroffene Servicefamilien
	- Microsoft 365
	- Dynamics 365
	- Power Platform
	- Azure
	- GenAI / Security / Support-kontextsensitive Services
- gewünschte Betrachtung: `Scope`, `Impact`, `Kosten`, `alle`

### Service- und Architekturfelder

- Microsoft 365 Multi-Geo aktiv `ja/nein`
- Azure regionale oder nichtregionale Services im Einsatz
- Tenant / Environments in EU-/EFTA-Geo provisioniert `ja/nein`
- Preview- oder Trial-Dienste im Einsatz `ja/nein`
- On-Prem-/Client-App-Daten relevant `ja/nein`
- Third-Party-Apps / Connected Experiences / externe Connectoren im Einsatz `ja/nein`

### Betriebs- und Governance-Felder

- Support-/Consulting-Leistungen von Microsoft relevant `ja/nein`
- strenge Customer-Lockbox-/Support-Zustimmungsanforderung `ja/nein`
- Datenexporte / DSR-Prozesse / internationale Zusammenarbeit relevant `ja/nein`
- optionale manuelle Kostenschätzung aktivieren `ja/nein`

## Ausgaben

- In-Scope- / Out-of-Scope-Matrix pro Workload
- Liste dokumentierter Resttransfers und Restrisiken
- Konfigurations- und Architektur-To-dos
- eindeutige Aussage, ob ein offizieller Microsoft-Aufpreis dokumentiert ist oder nicht
- optionaler Projekt- / Betriebsaufwandsrahmen aus manuellen Annahmen
- CTA: `EUDB-Assessment anfragen`, `Architektur-Workshop`, `PDF exportieren`

## Entscheidungslogik

### Harte Ausschlussregeln

- `Microsoft 365 Multi-Geo == true` -> Microsoft-365-Workloads nicht in EUDB-Scope
- `service_state in ['preview', 'trial']` -> out of scope
- `workload_type in ['on-prem', 'client-app-only']` -> out of scope
- `consulting_services == true` -> Professional Services Consulting außerhalb Scope

### Regeln für `In Scope mit Konfigurationsaufwand`

- Tenant / Geo liegen grundsätzlich passend
- Workloads sind EUDB-fähig
- Azure- oder Tenant-Konfigurationen müssen noch sauber gesetzt werden
- Resttransfers bleiben, sind aber dokumentiert und akzeptiert

### Regeln für `Teilweise in Scope`

- Kern-Workloads liegen in Scope
- Zusatzdienste, Connected Experiences oder Supportpfade führen zu dokumentierten Resttransfers
- einzelne Servicefamilien sind nur mit Sonderregeln oder Konfigurationsgrenzen EUDB-konform

### Regeln für `Architektur-Review nötig`

- Multi-Geo, nichtregionale Azure-Komponenten, bestehende Tenant-Strukturen oder Drittintegrationen kollidieren mit dem gewünschten Zielbild
- Kunde erwartet „100 % EU-only ohne Ausnahmen“, obwohl Microsoft dokumentierte Resttransfers vorsieht
- gewähltes Ziel ist eher ein Re-Design / Re-Provisioning-Projekt als ein Lizenzthema

### Regeln für `Kostenmodus`

- Wenn keine offizielle Microsoft-Gebühr dokumentiert ist, muss das Tool klar sagen: `kein offizieller EUDB-Aufpreis dokumentiert`.
- Kosten dürfen nur dort ausgewiesen werden, wo **manuell gepflegte** Projekt- oder Governance-Module hinterlegt sind.

## Bewertungsmodell

Zusätzlich zum harten Regelwerk sollte das Tool einen Impact-Score erzeugen:

- `+3` EU-/EFTA-Kunde mit passenden Geos
- `+2` nur GA-/Paid-Services im Einsatz
- `+2` klare regionale Bereitstellung / Tenant-Konfiguration vorhanden
- `-4` Multi-Geo aktiv
- `-3` Preview-/Trial-Abhängigkeit
- `-3` viele Connected Experiences / externe Transfers
- `-2` starke Support-/Consulting-Abhängigkeit
- `-2` nichtregionale Azure-Dienste ohne geklärten Konfigurationspfad

## Benötigte Daten

### 1. Scope-Matrix `eudb_scope_matrix.json`

- `service_family`
- `is_in_scope`
- `scope_condition`
- `configuration_required`
- `notes`

### 2. Transfer-Muster `eudb_transfer_patterns.json`

- `transfer_type`
- `trigger`
- `applies_to`
- `is_customer_initiated`
- `is_microsoft_operational`
- `risk_note`

### 3. Ausschluss- und Sonderregeln `eudb_exclusion_rules.json`

- `multi_geo_exclusion`
- `preview_trial_exclusion`
- `consulting_services_exclusion`
- `onprem_exclusion`
- `anthropic_processing_warning`

### 4. Manuelle Kostenmodule `eudb_optional_cost_modules.json`

- `cost_module`
- `type` (`one_time`, `recurring`)
- `description`
- `default_amount`
- `is_official_microsoft_fee` = `false`

Beispielmodule:

- Tenant-/Geo-Assessment
- Re-Provisioning / Migration
- Azure-Nonregional-Konfiguration
- DLP-/Connector-Review
- Legal-/Compliance-Workshop
- Drittanbieter- / Connected-Experience-Review

## Verifizierte Weblinks / Quellenbasis

### A. Kernverständnis und Scope

1. **What is the EU Data Boundary?**  
	https://learn.microsoft.com/en-us/privacy/eudb/  
	Relevanz: Scope, Länder, Services, Konfigurationslogik, Multi-Geo-Hinweis.

2. **Continuing data transfers that apply to all EU Data Boundary Services**  
	https://learn.microsoft.com/en-us/privacy/eudb/eu-data-boundary-transfers-for-all-services  
	Relevanz: verbleibende Transfers, Support, Security Operations, Network Transit, DSR-Sonderfälle.

### B. Azure- und Sonderkonfigurationen

3. **Configuring Azure non-regional services for the EU Data Boundary**  
	https://learn.microsoft.com/en-us/privacy/eudb/eu-data-boundary-configure-azure-nonregional-services  
	Relevanz: Azure als Konfigurations- statt Preis-Thema, tenant-/service-spezifische Pfade.

### C. Gegenbeispiel / Ausschlusspfad

4. **Microsoft 365 Multi-Geo**  
	https://learn.microsoft.com/en-us/microsoft-365/enterprise/microsoft-365-multi-geo  
	Relevanz: Add-on-Modell mit eigener Lizenzlogik und explizitem EUDB-Out-of-Scope-Hinweis.

> Entscheidende Produktpositionierung: Das Tool darf **nicht** so tun, als gäbe es einen pauschalen, offiziell dokumentierten „EU Data Boundary Lizenz-Aufpreis“. Es ist primär ein **Impact- und Scope-Checker** mit optionalem manuellen Projektkosten-Modul.

## Tool-Flow

### Schritt 1 – Scope prüfen

- Kunde / Tenant / Region / Services prüfen
- Multi-Geo, Preview, On-Prem, Support-/Consulting-Sonderfälle erfassen

### Schritt 2 – Transfers & Konfiguration verstehen

- verbleibende Transfers einordnen
- Azure-/Tenant-Konfiguration prüfen
- Connected Experiences / Drittpfade markieren

### Schritt 3 – Ergebnis & optionaler Aufwand

- In-/Out-of-Scope-Matrix
- Governance-/Architektur-To-dos
- optionaler manueller Projektkostenrahmen

## UI/UX nach `cms-m365lic`

- Hero mit klarem Framing: `EU Data Boundary – Scope, Restrisiken und Projektaufwand statt Marketing-Mythos`
- Service-Karten für `Microsoft 365`, `Power Platform`, `Dynamics 365`, `Azure`
- KPI-Karten für:
	- Scope-Status
	- dokumentierte Resttransfers
	- Konfigurationsaufwand
	- offizieller Aufpreisstatus
- Ergebnisblöcke als `In Scope`, `Teilweise`, `Out of Scope`, `Manuelle Kostenmodule`

## Ergebnislogik / Textbausteine

### `In Scope – kein offizieller EUDB-Aufpreis dokumentiert`

- Kunde und Workloads liegen grundsätzlich passend
- Microsoft dokumentiert keinen pauschalen Lizenzaufschlag
- ggf. bleiben nur dokumentierte Resttransfers

### `In Scope – Konfiguration / Governance nötig`

- EUDB grundsätzlich erreichbar
- Tenant-, Geo- oder Azure-Konfiguration ist noch offen

### `Teilweise in Scope`

- zentrale Workloads passen
- einzelne Betriebs- oder Integrationspfade führen zu Resttransfers oder Ausnahmen

### `Out of Scope / Architekturänderung nötig`

- Multi-Geo, Preview/Trial, Consulting oder falsche Service-/Tenant-Konstellation

### `Kein seriöser Aufpreis berechenbar`

- keine offizielle Microsoft-Gebühr vorhanden
- nur manuelle Projekt-/Governance-Kosten darstellbar

## Benötigte Bausteine / Funktionen

- `load_eudb_scope_matrix()`
- `load_eudb_transfer_patterns()`
- `load_eudb_exclusion_rules()`
- `load_eudb_optional_cost_modules()`
- `validate_eudb_input()`
- `evaluate_eudb_scope()`
- `evaluate_eudb_transfer_impact()`
- `evaluate_eudb_architecture_gaps()`
- `calculate_eudb_project_costs()`
- `build_eudb_summary()`
- `render_eudb_page()`
- `export_eudb_pdf()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if customer_region not in ['EU', 'EFTA'] => out_of_scope`
- `if m365_multi_geo == true => m365_out_of_scope`
- `if service_state in ['preview', 'trial'] => out_of_scope`
- `if azure_nonregional_service == true and config_path_missing == true => configuration_gap`
- `if customer_initiated_external_transfer == true => residual_transfer_warning`
- `if consulting_services == true => professional_services_out_of_scope`
- `if anthropic_models_enabled == true => us_processing_warning`
- `if optional_cost_mode == true and official_fee_missing == true => manual_costs_only`

## Admin / Pflege

- Pflege der Scope-Matrix je Servicefamilie
- Pflege dokumentierter Transfermuster und Ausschlüsse
- Pflege aller manuellen Kostenmodule mit klarem `nicht offiziell`-Flag
- regelmäßige Review-Zyklen gegen Microsoft Learn, da die EUDB-Dokumentation fortlaufend angepasst wird

## SEO- und Content-Bausteine

### Fokus-Keywords

- `eu data boundary kosten`
- `eu data boundary microsoft 365`
- `eu data boundary multi geo`
- `eu data boundary aufpreis`
- `microsoft eu data boundary`
- `eu data boundary azure`

### Empfohlene FAQ-Blöcke

- Gibt es einen offiziellen EU Data Boundary Aufpreis?
- Welche Microsoft-Dienste sind in der EU Data Boundary?
- Ist Microsoft 365 Multi-Geo Teil der EU Data Boundary?
- Bleiben trotzdem Datenübertragungen außerhalb der EU möglich?
- Ist EUDB ein Lizenz- oder eher ein Architekturthema?
- Welche Projektkosten können trotzdem entstehen?

## MVP

- Scope-Checker für Kern-Servicefamilien
- Resttransfer- und Ausschlusslogik
- klare Aussage `kein pauschaler offizieller Aufpreis dokumentiert`
- optionale manuelle Projektkostenmodule

## Phase 2

- tiefere Azure-Servicepfade
- tenant- und workload-spezifische Fragebäume
- PDF-Entscheidungshilfe für Compliance / Einkauf / Architektur
- Verlinkung in Backup-, Security- und Data-Residency-Themen

## Akzeptanzkriterien

- Tool behauptet keinen pauschalen EUDB-Lizenzaufschlag ohne offizielle Quelle
- Tool trennt sauber zwischen `Scope`, `Resttransfers`, `Konfiguration` und `manuellen Projektkosten`
- Tool behandelt `Multi-Geo` explizit als Sonder- bzw. Ausschlusspfad
- Tool weist Preview/Trial, Consulting Services und On-Prem korrekt als Nicht-Scope aus
- alle manuellen Kosten sind klar als Annahmen gekennzeichnet

## Offene Pflegepunkte

- EUDB-Dokumentation ist laufend im Ausbau; Scope und Resttransfers regelmäßig neu prüfen
- Azure-Nonregional-Services nur serviceweise modellieren, nicht pauschal
- juristische / regulatorische Interpretation nicht mit technischer Microsoft-Dokumentation vermischen
- Produktname der Datei ist historisch „Aufpreis-Rechner“, fachlich sollte die Positionierung künftig eher `Impact-Checker` sein