# M365 Backup-Kosten-Rechner

> **Umsetzungsstand 17.05.2026:** Als Modul `m365-backup-cost-calculator` im Plugin `cms-m365tools` unter `/m365-backup-kostenrechner` umgesetzt. Version `1.18.0` enthält offizielle Microsoft-365-Backup-Baseline, manuell gepflegten Providervergleich, Kostenranking, Workload-/Retention-/Restore-Bewertung, FAQ und Quellenstand.

- **Priorität:** hoch
- **Datenquelle:** Microsoft-365-Backup-Baseline + optionaler Provider-Katalog mit manuell gepflegten Wettbewerbsdaten
- **Aufwand:** mittel
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/m365-backup-kostenrechner`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Das Tool soll zuerst eine **belastbare Microsoft-365-Backup-Baseline** berechnen und darauf optional einen Wettbewerbsvergleich setzen. So bleibt der Microsoft-Teil quellensicher, während Drittanbieter modular und pflegbar bleiben.

## Verifizierte Microsoft-Leitplanken

### Microsoft 365 Backup ist verbrauchsorientiert

- Microsoft 365 Backup ist ein **Pay-as-you-go**-Angebot.
- Die Abrechnung liegt laut Übersicht bei **$0.15 pro GB und Monat** für geschützte Daten.
- Restores sind laut Microsoft ohne separate Restore-Gebühr enthalten.

### First-Party-Abdeckung ist klar definiert

- Microsoft 365 Backup schützt OneDrive-Konten, SharePoint-Sites und Exchange-Benutzerpostfächer.
- Retention liegt bei **1 Jahr**.

### Wiederherstellung ist ein Werttreiber, nicht nur Storage

- Microsoft betont schnelle Backup- und Restore-Zeiten.
- Für große Wiederherstellungen werden RTO-/Performance-Erwartungen von bis zu **1–3 TB pro Stunde** genannt.

### Daten bleiben in der Microsoft-Trust-Boundary

- Microsoft 365 Backup hält Daten in der Microsoft-365-Datenvertrauensgrenze und respektiert geografische Datenresidenz.
- Für viele regulierte Kunden ist das ein Differenzierungsmerkmal gegenüber Fremdplattformen.

### Dritthersteller brauchen eigene Datenpflege

- Für Veeam, AvePoint, Backupify & Co. müssen Preise, Retention und Workload-Abdeckung manuell gepflegt werden.
- Das Tool sollte diese Anbieterdaten daher klar als `manuell gepflegte Vergleichsdaten` kennzeichnen.

## Ziel des Tools

Vergleich von Microsoft 365 Backup mit ausgewählten Drittanbietern – inklusive Kosten, Workloads, Restore-Tiefe, Datenresidenz und Betriebsmodell.

## Gewünschte Ergebnis-Kategorien

1. `✅ Microsoft 365 Backup ausreichend und wirtschaftlich`
2. `🟡 Microsoft 365 Backup + Partnerlösung prüfen`
3. `🔵 Drittanbieter wirtschaftlicher / funktional vollständiger`
4. `🟠 Hybrid-Modell sinnvoll`
5. `🔴 Vergleichsdaten unvollständig – manuelle Prüfung nötig`

## Kernfunktionen

- Auswahl von Workloads: Exchange, OneDrive, SharePoint, optional Teams / Zusatzworkloads
- Microsoft-365-Backup-Baseline berechnen
- Vergleich mehrerer Backup-Anbieter
- Kostenberechnung pro GB, User, Workload oder TB normalisieren
- Feature-Vergleich: Restore, Aufbewahrung, Granularität, RPO/RTO, Datenresidenz
- Empfehlung nach Budget und Schutzbedarf

## Eingaben

### Microsoft-Basisdaten

- Anzahl User / Postfächer / OneDrives / Sites
- geschützte Datenmenge in GB oder TB
- Workload-Auswahl

### Vergleichsdaten

- gewünschte Retention
- gewünschtes Betriebsmodell
- gewünschte Restore-Tiefe
- optionale Drittanbieter-Auswahl

## Ausgaben

- Microsoft-365-Backup-Basiskosten
- Kostenvergleich mehrerer Anbieter
- Feature-Matrix
- Anbieter-Ranking nach Preis, Schutzgrad oder Microsoft-Nähe
- Empfehlung für KMU vs. Enterprise

## Entscheidungslogik

### 1. Microsoft-Baseline rechnen

- protected GB × Microsoft-Preis
- betroffene Workloads markieren

### 2. Drittanbieter normalisieren

- per User => auf Gesamtmenge abbilden
- per TB => direkt umrechnen
- Zusatzkosten separat ausweisen

### 3. Schutzgrad vs. Preis bewerten

- Restore-Tiefe
- RPO/RTO
- Retention
- Datenresidenz / Trust Boundary

## Benötigte Daten

### 1. `microsoft_backup_baseline.json`

- Preis pro GB
- Workload-Abdeckung
- Retention
- Restore-Profile

### 2. `providers.json`

- Anbietername
- Preismodell
- Workload-Abdeckung
- Restore-Tiefe
- Retention
- Quellenstand

### 3. `backup_comparison_rules.json`

- Normalisierung per User / per TB / per Workload
- Gewichtung Preis vs. Schutzgrad

## Verifizierte Weblinks / Quellenbasis

1. **Overview of Microsoft 365 Backup**  
	https://learn.microsoft.com/en-us/microsoft-365/backup/backup-overview

2. **Microsoft 365 Backup – Graph APIs / reference root**  
	https://learn.microsoft.com/en-us/graph/api/resources/backuprestoreroot

3. **Microsoft Licensing News**  
	https://www.microsoft.com/en-us/licensing/news

> Drittanbieter-Quellen sollten im finalen Tool zusätzlich pro Anbieter gepflegt und mit Standdatum versehen werden.

## Tool-Flow

### Schritt 1 – Microsoft-Baseline erfassen

- Workloads
- Datenmenge
- Schutzumfang

### Schritt 2 – Vergleichsanbieter wählen

- Provider aktivieren
- Modell normalisieren

### Schritt 3 – Ergebnis bewerten

- Preis
- Restore
- Retention
- Betriebsmodell

## UI/UX nach `cms-m365lic`

- Vergleichstabelle im dunklen Tabellenstil
- Filterchips für Anbieter und Workloads
- KPI-Karten für `günstigster`, `vollständigster`, `Microsoft-nächster` Anbieter
- Microsoft-Baseline prominent voranstellen

## Ergebnislogik / Textbausteine

### `Microsoft 365 Backup reicht aus`

- Workloads passen
- Retention und Restore-Tiefe genügen
- Datenresidenz / Trust Boundary ist wichtig

### `Partnerlösung prüfen`

- zusätzliche Workloads, längere Retention oder Spezial-Workflows nötig

### `Hybrid-Modell`

- First-Party für Kernworkloads, Drittanbieter für Zusatzanforderungen

## Benötigte Bausteine / Funktionen

- `load_backup_provider_catalog()`
- `load_microsoft_backup_baseline()`
- `normalize_backup_provider_pricing()`
- `compare_backup_scenarios()`
- `rank_backup_provider_results()`
- `render_backup_cost_page()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if provider_data_source === 'manual' => show_manual_verification_badge`
- `if retention_requirement > microsoft_retention_limit => boost_partner_or_hybrid`
- `if data_trust_boundary_priority === high => boost_microsoft_score`
- `if workload_not_covered_by_microsoft_first_party === true => hybrid_or_partner_path`
- `if restore_speed_priority === high && provider_restore_depth_weak === true => downrank_provider`

## Admin / Pflege

- Pflege von Anbieterpreisen
- Pflege der Feature-Matrix
- Quellen- und Standprüfung quartalsweise
- Trennung `offizielle Microsoft-Baseline` vs. `manuell gepflegte Wettbewerbsdaten`

## SEO- und Content-Bausteine

### Fokus-Keywords

- `m365 backup kostenrechner`
- `microsoft 365 backup vs veeam`
- `m365 backup kosten pro gb`
- `sharepoint onedrive exchange backup`

### Empfohlene FAQ-Blöcke

- Was kostet Microsoft 365 Backup?
- Welche Workloads deckt Microsoft 365 Backup ab?
- Wann lohnt sich ein Drittanbieter mehr?
- Sind Restores bei Microsoft extra zu bezahlen?

## MVP

- Microsoft-Baseline
- 4 Hauptanbieter als manuelle Vergleichsdaten
- Kostenvergleich
- grundlegende Feature-Matrix

## Phase 2

- PDF-Vergleichsreport
- Branchenprofile
- Backup-Audit-CTA
- Multi-Geo-/Residency-Sonderpfade

## Akzeptanzkriterien

- Microsoft-Baseline basiert auf offizieller Quelle.
- Drittanbieter sind klar als manuell gepflegt markiert.
- Preis- und Schutzvergleich sind getrennt sichtbar.
- Ergebnis nennt klar, wann Microsoft allein reicht und wann nicht.

## Offene Pflegepunkte

- Drittanbieterpreise und -features regelmäßig nachpflegen.
- Teams-/Spezialworkloads im Vergleich separat kennzeichnen.
- API-/Lizenz-Referenzen bei Microsoft 365 Backup im Review aktuell halten.