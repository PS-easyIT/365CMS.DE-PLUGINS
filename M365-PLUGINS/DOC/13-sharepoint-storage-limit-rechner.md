# SharePoint-Storage-Limit-Rechner

- **Priorität:** mittel bis hoch
- **Datenquelle:** SharePoint-Limits + Storage-Preisdatei + Governance-Hinweise
- **Aufwand:** niedrig
- **SEO-Potenzial:** ★★
- **Empfohlener Slug:** `/sharepoint-storage-limit-rechner`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Der Rechner ist der schnelle „Reality Check“ für den SharePoint-Tenant-Pool nach der Formel `1 TB + 10 GB pro Lizenz` inklusive Frühwarnung, Wachstumssimulation und Handlungsempfehlung.

## Verifizierte Microsoft-Leitplanken

### Der Tenant-Pool folgt einer klaren Basisformel

- Laut SharePoint-Servicebeschreibung besteht der Storage-Pool aus **1 TB Basis** plus **10 GB pro qualifizierter Lizenz**.
- Zusätzlicher SharePoint-Speicher kann gekauft werden.

### Überlimit ist nicht nur theoretisch unschön

- Wenn der Tenant dauerhaft über dem Storage-Limit arbeitet, kann die Umgebung in einen **Read-only-Modus** geraten.

### Große Sites und viele Sites sind eigene Themen

- Maximal **25 TB pro Site Collection**.
- Bis zu **2 Millionen Sites** pro Organisation.
- Ein Poolproblem wird nicht dadurch gelöst, dass einzelne Sites groß sein dürfen.

### Praxisgrenzen gehören in den Hinweisblock

- Sync-Soft-Limit: rund **300.000 Dateien** pro synchronisiertem Bereich.
- Einzeldatei-Upload: bis **250 GB**.

## Ziel des Tools

Schneller Rechner für den SharePoint-Storage-Pool inklusive Headroom, Wachstumsprognose und Zukauf-/Governance-Empfehlung.

## Gewünschte Ergebnis-Kategorien

1. `✅ Pool gesund`
2. `🟡 Frühwarnung`
3. `🟠 Zukauf oder Cleanup bald nötig`
4. `🔴 Akutes Kapazitätsrisiko`
5. `🔵 Design-/Governance-Problem statt reines Speicherproblem`

## Kernfunktionen

- Auswahl der qualifizierten Lizenzanzahl
- Berechnung des verfügbaren Pools
- Eingabe des aktuellen oder geplanten Bedarfs
- Warnung bei knapper Auslegung
- Empfehlung zu Storage-Zukauf oder Governance-Maßnahmen
- optionale Wachstumssimulation

## Eingaben

- Anzahl berechtigter Lizenzen
- aktueller Speicherverbrauch
- erwartetes Wachstum pro Jahr
- optional: größte Site / hoher Datei-Sync-Bedarf

## Ausgaben

- verfügbarer SharePoint-Pool
- Restkapazität
- Warnstatus
- geschätzte Zeit bis zur roten Zone
- Empfehlung für Zusatzspeicher oder Cleanup

## Entscheidungslogik

### 1. Pool berechnen

- `1 TB + 10 GB × Lizenzanzahl`

### 2. Auslastung bewerten

- unter 80 % = okay
- 80–95 % = Beobachten
- >95 % = kritisch

### 3. Maßnahmen ableiten

- Zukauf
- Cleanup / Lifecycle
- Site-/Sync-Governance

## Benötigte Daten

### 1. `sharepoint_pool_rules.json`

- Basisformel
- Grenzwerte
- Zusatzspeicherpreis

### 2. `governance_hints.json`

- Cleanup-Hinweise
- Lifecycle-Hinweise
- Sync-Warnungen

## Verifizierte Weblinks / Quellenbasis

1. **SharePoint limits**  
	https://learn.microsoft.com/en-us/office365/servicedescriptions/sharepoint-online-service-description/sharepoint-online-limits

2. **Overview of OneDrive in Microsoft 365**  
	https://learn.microsoft.com/en-us/sharepoint/onedrive-overview

## Tool-Flow

### Schritt 1 – Lizenzanzahl und Ist-Verbrauch

- qualifizierte Lizenzen
- aktueller Verbrauch

### Schritt 2 – Wachstum projizieren

- jährliches Wachstum
- Puffer berücksichtigen

### Schritt 3 – Maßnahme ableiten

- genug Headroom
- Storage zukaufen
- Cleanup / Governance

## UI/UX nach `cms-m365lic`

- sehr kompakt: Hero, Eingabe-Card, Balkenanzeige, Ergebnis-KPI
- knappe Handlungsempfehlungen statt langer Texte
- Zusatzhinweise für `Read-only-Risiko`, `Sync-Limit`, `25-TB-Site`

## Ergebnislogik / Textbausteine

### `Pool gesund`

- komfortable Reserve
- kein unmittelbarer Handlungsdruck

### `Frühwarnung`

- Wachstum beobachten
- Zukauf oder Cleanup einplanen

### `Designproblem`

- nicht nur mehr Speicher nötig, sondern Lifecycle-/Informationsarchitektur prüfen

## Benötigte Bausteine / Funktionen

- `calculate_sharepoint_storage_pool()`
- `calculate_sharepoint_storage_headroom()`
- `calculate_sharepoint_growth_forecast()`
- `render_sharepoint_storage_page()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if usage_ratio >= 0.8 => warning`
- `if usage_ratio >= 0.95 => critical`
- `if largest_site_tb > 20 => show_site_design_warning`
- `if synced_items_estimate > 300000 => show_sync_warning`

## Admin / Pflege

- Pflege des Storage-Zukaufspreises
- Pflege kurzer Handlungshinweise
- quartalsweiser Review der SharePoint-Limits

## SEO- und Content-Bausteine

### Fokus-Keywords

- `sharepoint storage limit rechner`
- `1 tb + 10 gb pro lizenz`
- `sharepoint speicher berechnen`
- `sharepoint read only speicher`

### Empfohlene FAQ-Blöcke

- Wie berechnet Microsoft den SharePoint-Storage-Pool?
- Was passiert bei zu wenig SharePoint-Speicher?
- Wann reicht Cleanup statt Zukauf?
- Welche Praxisgrenzen sollte man zusätzlich beachten?

## MVP

- Pool-Rechnung
- Warnlogik
- Kostenhinweis
- Wachstumssimulation

## Phase 2

- Gruppen- oder Standortvergleich
- Integration in Storage-Bedarfs-Rechner
- Headroom-Prognose in Monaten

## Akzeptanzkriterien

- Formel und Warnstufen sind nachvollziehbar.
- Read-only-Risiko wird sichtbar gemacht.
- operative Praxisgrenzen werden eingeblendet.
- Tool bleibt bewusst kompakt und schnell nutzbar.

## Offene Pflegepunkte

- Zusatzspeicherpreise zentral pflegen.
- Governance-Hinweise an echte Kundenprobleme anpassen.
- Querverlinkung zum großen Storage-Bedarfs-Rechner in Phase 2 sauber ausbauen.