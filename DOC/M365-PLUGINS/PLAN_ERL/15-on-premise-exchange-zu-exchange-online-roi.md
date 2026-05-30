# On-Premise Exchange → Exchange Online ROI

- **Priorität:** hoch
- **Datenquelle:** Exchange-Hybrid-/Migrationsleitfäden + Exchange-Online-Preis-/Limitdaten + On-Prem-Betriebsannahmen
- **Aufwand:** mittel
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/exchange-online-roi`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Ein ROI-Rechner für den Wechsel von lokalem Exchange zu Exchange Online mit Break-Even über 3 oder 5 Jahre – inklusive Betriebskosten, Hardware-Refresh, Migrationsprojekt und optionaler Hybrid-/Compliance-Kosten.

## Verifizierte Microsoft-Leitplanken

### Hybrid ist offizieller Standardpfad für Koexistenz oder Migration

- Microsoft beschreibt Hybrid Deployments als Standardpfad für **langfristige Koexistenz** oder **Migration in die Cloud**.
- Hybrid kann Zwischenstufe oder dauerhaftes Betriebsmodell sein.

### Nicht jede Migrationsmethode passt zu jeder Größe

- Cutover Migration kann laut Microsoft bis zu **2.000 Mailboxen** umfassen; empfohlen sind jedoch eher **150**.
- Staged Migration ist für eine gestaffelte Migration über Wochen oder Monate gedacht.
- Hybrid Deployment bleibt der typische Enterprise-/Koexistenzpfad.

### Migrationsdauer ist beeinflussbar, aber nicht beliebig planbar

- Microsoft veröffentlicht Richtwerte für Mailbox-Migrationsdauern.
- Für Onboarding aus On-Premises nennt Microsoft Richtwerte nach Mailbox-Größenprofilen.
- Queue-Zeiten, Netzwerk, Throttling und Quellsystem-Performance beeinflussen die echte Dauer erheblich.

### ROI ist nicht nur Lizenzpreis minus Server

- Ein valider Vergleich braucht CAPEX, OPEX, Backup, Storage, Strom, Wartung, Betriebspersonal, HA/DR und Projektkosten.
- Zusätzlich sollten qualitative Faktoren wie Verfügbarkeit, Security, Update-Aufwand und Archiv-/Compliance-Funktionen sichtbar werden.

## Ziel des Tools

Ein ROI-Rechner für den Wechsel von lokalem Exchange zu Exchange Online mit Break-Even über 3 oder 5 Jahre.

## Gewünschte Ergebnis-Kategorien

1. `✅ Schneller Break-Even`
2. `🟡 Break-Even mittelfristig`
3. `🟠 Kurzfristig teurer, strategisch sinnvoll`
4. `🔵 Hybrid/Teilumstieg prüfen`
5. `🔴 Ist-Daten zu unvollständig für belastbaren ROI`

## Kernfunktionen

- Erfassung bestehender Server-, Lizenz- und Betriebskosten
- Cloud-Zielkosten für Exchange Online
- Vergleich CAPEX vs. OPEX
- Break-Even-Darstellung über 3 und 5 Jahre
- Einbezug von Admin-Stunden, Strom, Wartung und Hardware-Erneuerung
- Qualitäts-/Risikohinweise zu Hybrid, Queue und Migrationsdauer

## Eingaben

### On-Prem-Basisdaten

- Anzahl Mailboxen
- Server-/Hardwarekosten
- Exchange-/Windows-Lizenzkosten
- Storage, Backup, Wartung
- Strom / Hosting / Housing

### Betriebskosten

- Admin-Aufwand pro Monat
- DR-/HA-Aufwand
- Monitoring / Zertifikate / Dritttools

### Migrationsdaten

- Migrationskosten einmalig
- gewünschte Migrationsmethode
- Pilot / Parallelbetrieb / Hybrid nötig ja/nein

## Ausgaben

- Ist-Kosten On-Prem über 3 / 5 Jahre
- Soll-Kosten Exchange Online
- Break-Even-Zeitpunkt
- Einsparpotenzial und Risiko-Hinweise
- qualitatives Zusatzfazit zu Betrieb, Resilienz und Modernisierung

## Entscheidungslogik

### 1. Ist-Kosten auf Vollkostenbasis erfassen

- laufender Betrieb
- Lizenzen
- Hardware-Refresh
- Betriebsnebenkosten

### 2. Zielkosten modellieren

- Exchange Online Lizenzen
- optionale Archive / Compliance / Zusatzprodukte
- Migrationsprojekt

### 3. Break-Even bewerten

- 36 Monate
- 60 Monate
- qualitativer Nutzen separat markieren

## Bewertungsmodell

- `laufende Betriebskosten`
- `periodische Erneuerungen / Refresh`
- `einmalige Migrationskosten`
- `Cloud-Zielkosten`
- `qualitative Nutzenpunkte`

## Benötigte Daten

### 1. `exchange_online_plans.json`

- Planpreise
- Archiv- / Limitpfade

### 2. `onprem_exchange_cost_defaults.json`

- Hardware-Lebenszyklen
- Backup- / Storage- / Strom-Annahmen
- Admin-Stundensätze

### 3. `exchange_migration_velocity.json`

- Richtwerte für Onboarding-Dauer
- Queue-/Throttling-Hinweise

## Verifizierte Weblinks / Quellenbasis

1. **Hybrid deployment procedures**  
	https://learn.microsoft.com/en-us/exchange/hybrid-deployment/hybrid-deployment

2. **Microsoft 365 and Office 365 email migration performance and best practices**  
	https://learn.microsoft.com/en-us/exchange/mailbox-migration/office-365-migration-best-practices

3. **Exchange Online limits**  
	https://learn.microsoft.com/en-us/office365/servicedescriptions/exchange-online-service-description/exchange-online-limits

4. **Exchange Online Archiving service description**  
	https://learn.microsoft.com/en-us/office365/servicedescriptions/exchange-online-archiving-service-description/exchange-online-archiving-service-description

5. **Microsoft 365 Enterprise Plans**  
	https://aka.ms/M365EnterprisePlans

## Tool-Flow

### Schritt 1 – On-Prem-Kosten aufnehmen

- Technik
- Betrieb
- Personal

### Schritt 2 – Exchange-Online-Zielkosten rechnen

- Lizenzbasis
- Zusatzfeatures
- Migrationsprojekt

### Schritt 3 – ROI und Break-Even ausgeben

- 3 Jahre
- 5 Jahre
- Management-Fazit

## UI/UX nach `cms-m365lic`

- Business-Hero mit ROI-Fokus
- Kostenblöcke `Ist`, `Soll`, `Delta`
- Verlaufschart für 3/5 Jahre
- Ergebnis mit Management-Fazit und Risikobox

## Ergebnislogik / Textbausteine

### `Schneller Break-Even`

- hohe On-Prem-Betriebskosten
- naher Hardware-Refresh
- geringe Sonderkomplexität

### `Strategisch sinnvoll trotz Mehrkosten`

- Cloud kurzfristig teurer
- dafür höhere Resilienz, weniger Betriebsrisiko, modernere Plattform

### `Hybrid prüfen`

- Koexistenz oder Spezialanforderungen machen Vollumstieg kurzfristig unpraktisch

## Benötigte Bausteine / Funktionen

- `calculate_exchange_onprem_costs()`
- `calculate_exchange_online_costs()`
- `calculate_exchange_migration_break_even()`
- `score_exchange_cloud_business_case()`
- `render_exchange_online_roi_page()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if hardware_refresh_due_within_12_months === true => boost_cloud_roi`
- `if mailbox_count_small && org_wants_fast_exit === true => cutover_candidate`
- `if mailbox_count_large || coexistence_needed === true => hybrid_candidate`
- `if data_quality_low === true => show_roi_uncertainty_warning`
- `if archive_or_compliance_needs_high === true => include_additional_cloud_costs`

## Admin / Pflege

- Pflege der Cloud-Preise
- Pflege gängiger Betriebskosten-Defaults
- Pflege der Disclaimer für Näherungswerte
- Pflege von Migrationsrichtwerten

## SEO- und Content-Bausteine

### Fokus-Keywords

- `exchange online roi`
- `exchange on prem zu exchange online kosten`
- `exchange migration break even`
- `exchange server cloud business case`

### Empfohlene FAQ-Blöcke

- Wann lohnt sich Exchange Online wirtschaftlich?
- Was kostet Exchange On-Prem wirklich über 5 Jahre?
- Welche Migrationsmethode passt zu welcher Größe?
- Warum sind Queue und Throttling für die Planung wichtig?

## MVP

- 3- und 5-Jahres-Rechnung
- Break-Even
- einfache Projektschätzung
- Migrationspfad-Hinweis

## Phase 2

- Hybrid-Szenarien detailliert
- Berücksichtigung von Archiving und Compliance
- Export als Cloud-Business-Case
- Variablen für Regional-/Hosting-Kosten

## Akzeptanzkriterien

- Tool rechnet CAPEX, OPEX und Migrationskosten getrennt.
- Hybrid-/Cutover-/Staged-Logik wird sichtbar erklärt.
- Richtwerte werden als Planungshilfe, nicht als Garantie dargestellt.
- Ergebnis enthält neben Kosten auch ein belastbares Management-Fazit.

## Offene Pflegepunkte

- Cloud- und Add-on-Preise zentral pflegen.
- On-Prem-Defaults später mit echten Kundenwerten schärfen.
- Hybrid-Sonderfälle mit weiteren Detailpfaden ergänzen.