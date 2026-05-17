# Storage-Bedarfs-Rechner

- **Priorität:** hoch
- **Datenquelle:** Storage-Regeln + Preisdatei + SharePoint-/Exchange-Limits + Admin-Annahmen für OneDrive-Quotas
- **Aufwand:** niedrig bis mittel
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/m365-storage-bedarfsrechner`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Der Rechner soll den tatsächlichen Storage-Bedarf für OneDrive, SharePoint und Mailboxen berechnen und sauber trennen zwischen:

1. tenantweitem SharePoint-/Dateispeicher
2. OneDrive-/Benutzerdateien
3. Exchange-Postfach- und Archivbedarf
4. Wachstum, das man heute ignoriert und morgen teuer bereut

## Verifizierte Microsoft-Leitplanken

### SharePoint-Tenant-Storage ist zentraler Pool

- SharePoint startet laut Servicebeschreibung mit **1 TB + 10 GB pro qualifizierter Lizenz**.
- Zusätzlicher SharePoint-Speicher kann gekauft werden.
- Operiert ein Tenant dauerhaft über seinem Limit, besteht Risiko auf **Read-only**.

### Große Sites lösen kein Pool-Problem

- Eine einzelne Site Collection kann bis **25 TB** groß sein.
- Das hebt aber das tenantweite Pool-Limit nicht auf.

### Dateigröße und Sync-Limits sind relevante Praxisgrenzen

- Einzeldateien können bis **250 GB** groß sein.
- Für den Sync empfiehlt Microsoft aus Performance-Sicht nicht mehr als **300.000 Dateien** in einem synchronisierten Bereich.

### OneDrive ist nicht nur Speicher, sondern Betriebsmodell

- OneDrive bringt Funktionen wie Files Restore und Recycle Bin mit.
- Die konkrete per-User-Quota sollte im Tool als **adminpflegbare Tenant-Einstellung** modelliert werden, nicht blind hart codiert werden.

### Exchange hat eigene Mailbox- und Archivgrenzen

- User-Mailboxen liegen je nach Plan typischerweise bei **50 GB** oder **100 GB**.
- Archive können – je nach Plan/Add-on – bis **1,5 TB** mit Auto-Expanding erreichen.

## Ziel des Tools

Berechnung des tatsächlichen Storage-Bedarfs für OneDrive, SharePoint und Exchange inklusive Zusatzkosten, wenn Pool-, Site- oder Mailbox-Grenzen überschritten werden.

## Gewünschte Ergebnis-Kategorien

1. `✅ Ausreichend dimensioniert`
2. `🟡 Beobachten / Wachstum einplanen`
3. `🟠 Zusatzspeicher oder Archivstrategie nötig`
4. `🔴 Akutes Kapazitätsrisiko`
5. `🔵 Governance-/Cleanup-Potenzial zuerst nutzen`

## Kernfunktionen

- Eingabe von Nutzeranzahl, OneDrive-Bedarf, SharePoint-Sites und Mailboxgrößen
- getrennte Berechnung von inkludiertem und benötigtem Speicher
- Warnung bei knapper Planung
- Schätzung zusätzlicher Storage-Kosten
- Empfehlung zu Plananpassung, Archivierung, Cleanup oder Storage-Zukauf

## Eingaben

### SharePoint / Dateien

- Anzahl qualifizierter Lizenzen
- aktueller SharePoint-Verbrauch
- Anzahl SharePoint-Sites
- größte Site / geschätzter Site-Bedarf

### OneDrive

- Anzahl Nutzer
- durchschnittlicher OneDrive-Bedarf pro User
- konfigurierte Standard-Quota pro User

### Exchange

- durchschnittliche Mailbox-Größe
- maximale Mailbox-Größe
- Anteil mit Archiv / Hold / Langzeitbedarf

### Wachstum

- jährliches Wachstum in %
- optionaler Puffer
- Aufbewahrungsdauer / Cleanup-Rhythmus

## Ausgaben

- benötigter Gesamtspeicher nach Bereich
- inkludierter Speicher / Limits laut Plan oder Admin-Vorgabe
- Überhang in GB / TB
- Zusatzkosten und Handlungsempfehlung
- Warnung vor operativen Grenzen (Sync, Sitegröße, Mailboxgröße)

## Entscheidungslogik

### 1. Pools und Grenzen trennen

- SharePoint-Tenant-Pool
- OneDrive-Benutzerbedarf
- Exchange-Postfachbedarf
- Archivbedarf separat

### 2. Wachstum simulieren

- 12 Monate
- optional 24 / 36 Monate

### 3. Maßnahmen ableiten

- mehr Storage kaufen
- archivieren
- Cleanup / Governance verbessern
- Plan- oder Betriebsmodell anpassen

## Benötigte Daten

### 1. `sharepoint_storage_rules.json`

- Basisformel
- Zusatzspeicherpreis
- Warnschwellen

### 2. `onedrive_quota_presets.json`

- tenantseitige Default-Quotas
- Growth-Assumptions

### 3. `exchange_storage_rules.json`

- 50-GB-/100-GB-Pfade
- Archiv-/Hold-Hinweise

### 4. `storage_growth_assumptions.json`

- Wachstum je Datentyp
- Pufferlogik

## Verifizierte Weblinks / Quellenbasis

1. **SharePoint limits**  
	https://learn.microsoft.com/en-us/office365/servicedescriptions/sharepoint-online-service-description/sharepoint-online-limits

2. **Overview of OneDrive in Microsoft 365**  
	https://learn.microsoft.com/en-us/sharepoint/onedrive-overview

3. **Exchange Online limits**  
	https://learn.microsoft.com/en-us/office365/servicedescriptions/exchange-online-service-description/exchange-online-limits

4. **Exchange Online Archiving service description**  
	https://learn.microsoft.com/en-us/office365/servicedescriptions/exchange-online-archiving-service-description/exchange-online-archiving-service-description

5. **Learn about auto-expanding archiving**  
	https://learn.microsoft.com/en-us/microsoft-365/compliance/autoexpanding-archiving

## Tool-Flow

### Schritt 1 – Datenmengen erfassen

- Nutzer
- Sites
- Mailboxen

### Schritt 2 – Wachstum simulieren

- jährliche Zunahme
- Puffer

### Schritt 3 – Engpässe erkennen

- SharePoint-Pool
- OneDrive-Quotas
- Mailbox-/Archivgrenzen

## UI/UX nach `cms-m365lic`

- Eingabe-Card + Ergebnis-Card
- Kapazitätsbalken mit Ampelfarben
- KPI-Karten für `inkludiert`, `benötigt`, `Überhang`, `nächster Engpass`
- separate Mini-Karten für `SharePoint`, `OneDrive`, `Exchange`

## Ergebnislogik / Textbausteine

### `Ausreichend dimensioniert`

- Puffer vorhanden
- keine operative Grenze kritisch

### `Zusatzspeicher oder Archiv nötig`

- tenantweiter Pool oder Mailboxgrenzen werden voraussichtlich gerissen

### `Governance zuerst`

- hoher Anteil inaktiver / redundanter Daten
- Cleanup günstiger als bloßer Zukauf

## Benötigte Bausteine / Funktionen

- `calculate_storage_requirements()`
- `calculate_storage_overage_costs()`
- `calculate_exchange_archive_need()`
- `build_storage_capacity_status()`
- `render_storage_calculator_page()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if sharepoint_usage_ratio >= 0.8 => warning`
- `if sharepoint_usage_ratio >= 0.95 => critical`
- `if synced_item_count > 300000 => sync_performance_warning`
- `if mailbox_size_forecast > mailbox_plan_limit => archive_or_plan_change`
- `if growth_rate_high && cleanup_ratio_low => recommend_capacity_plan`

## Admin / Pflege

- Pflege von Speicherregeln und Preisen
- Pflege tenantseitiger OneDrive-Quotas
- Pflege von Warnschwellen
- Pflege der Wachstumsannahmen

## SEO- und Content-Bausteine

### Fokus-Keywords

- `m365 storage rechner`
- `sharepoint storage berechnen`
- `onedrive speicherbedarf`
- `exchange mailbox größe planen`

### Empfohlene FAQ-Blöcke

- Wie berechnet sich der SharePoint-Storage-Pool?
- Wann wird OneDrive zum Problem?
- Wann sollte ich archivieren statt Speicher kaufen?
- Was bedeutet Read-only bei SharePoint?

## MVP

- SharePoint, OneDrive, Exchange
- Zusatzkosten-Anzeige
- Wachstum und Warnlogik

## Phase 2

- Jahreswachstum simulieren
- Planvergleich mit Storage-Effekt
- PDF-Export
- CSV-Import aus Bestandsdaten

## Akzeptanzkriterien

- Tool trennt SharePoint-, OneDrive- und Exchange-Logik sauber.
- operative Grenzen wie Sync-Limits werden nicht verschwiegen.
- Wachstum wird sichtbar berücksichtigt.
- Ergebnis gibt konkrete Maßnahmen statt nur roter Zahlen aus.

## Offene Pflegepunkte

- OneDrive-Quotas tenantabhängig administrativ pflegen.
- Zusatzspeicherpreise zentral halten.
- Cleanup-/Archiv-Regeln mit Praxisdaten schärfen.

## Umsetzung 1.19.0 – 2026-05-17

- Modul `m365-storage-needs-calculator` unter `/m365-storage-bedarfsrechner` implementiert.
- Engine `CMS_M365CALCULATOR_Storage_Needs_Calculator` ergänzt.
- Public Template `templates/page-storage-needs-calculator.php` ergänzt.
- Kataloge `sharepoint_storage_rules.json`, `onedrive_quota_presets.json`, `exchange_storage_rules.json` und `storage_growth_assumptions.json` ergänzt.
- Route, Tool-Registry, Katalogloader, Update-Manifest, README, API-, Datenbank-, Hooks- und Modul-Dokumentation synchronisiert.