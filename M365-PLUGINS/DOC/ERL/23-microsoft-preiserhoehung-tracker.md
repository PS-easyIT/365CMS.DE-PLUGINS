# Microsoft-Preiserhöhung-Tracker

- **Priorität:** hoch
- **Datenquelle:** Microsoft Licensing News + manuell gepflegte Historie + Vertrags-/Renewal-Annahmen
- **Aufwand:** mittel
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/microsoft-preiserhoehung-tracker`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Das Tool soll **nicht nur eine lose Timeline** anzeigen, sondern belastbar beantworten:

1. **Welche offiziellen Preis-, Packaging-, SKU- oder Lizenzstruktur-Änderungen hat Microsoft dokumentiert?**
2. **Welche Änderungen betreffen den Nutzer konkret – global, EEA, Government, Nonprofit oder nur bestimmte Produktfamilien?**
3. **Ab wann greifen die Änderungen wirklich: Veröffentlichung, Effective Date oder erst bei Renewal?**
4. **Wie hoch ist der Budgeteffekt auf den aktuellen Bestand?**
5. **Welche Aussagen sind offiziell dokumentiert – und was ist nur Forecast / Budgetschätzung?**

Der Tracker ist damit gleichzeitig:

- Preis-Historie
- Renewal-Frühwarnsystem
- Packaging-/SKU-Watchlist
- Budget-Impact-Rechner
- Content-Hub für Newsletter, Alerts und Beratungs-CTAs

## Verifizierte Microsoft-Leitplanken

Die folgenden Regeln sollten im Tool klar voneinander getrennt modelliert werden.

### `Licensing News` ist das offizielle Ereignis-Hub

- Die Seite `https://www.microsoft.com/en-us/licensing/news` ist das zentrale offizielle Hub für Lizenzierungs-News.
- Dort erscheinen **nicht nur Preissteigerungen**, sondern auch:
	- Packaging-Updates
	- Teams-/No-Teams-SKU-Änderungen
	- End-of-sale-Mitteilungen
	- Portfolio-/Retirement-Änderungen
	- Vertrags- und Channel-Hinweise

### Veröffentlichung, Inkrafttreten und Renewal sind nicht dasselbe

- Ein News-Artikel hat ein **Publikationsdatum**.
- Viele Änderungen haben zusätzlich ein **Effective Date**.
- Für Bestandskunden gilt häufig: **aktuelle Preise bleiben bis zum Renewal bestehen**.
- Gerade bei Microsoft 365 muss das Tool deshalb zwischen `published_at`, `effective_at` und `applies_on_renewal` unterscheiden.

### Die M365-Updates 2025/2026 sind nicht nur „Preiserhöhung“

- Microsoft hat 2025 eine globale Strukturänderung für `with Teams` / `no Teams` dokumentiert.
- Laut offizieller News vom **01.11.2025** wurden:
	- Teams-enthaltende Enterprise-Suiten weltweit wieder breiter verfügbar,
	- `no Teams`-Suiten preislich abgesenkt,
	- Teams-Standalone-SKUs preislich angepasst.
- Das ist also fachlich ein **Lizenzstruktur- und Packaging-Ereignis**, nicht nur ein pauschaler Preisaufschlag.

### Die 2026-M365-Anpassung kombiniert Preis- und Packaging-Änderungen

- Die offizielle News zu den Microsoft-365-Commercial-Suites nennt:
	- **Preisupdate wirksam ab 01.07.2026**
	- **Packaging-Rollout ab Juni 2026**
	- **30 Tage Message-Center-Vorlauf** vor Rollout ins Tenant
	- **Bestandskunden bleiben bis Renewal auf altem Preis**
- Microsoft nennt zudem explizit, dass **Standalone Teams SKUs und Copilot SKUs** nicht Teil dieses konkreten 2026-Updates sind.

### Regionen, Segmente und Vertragswelten müssen getrennt werden

- Commercial, Government und Nonprofit folgen nicht immer exakt derselben Darstellung.
- EEA-/No-Teams-Sonderwelten und globale Lizenzstrukturen müssen separat modelliert werden.
- Preislisten sind laut Microsoft **USD-Listenpreise** und können nach Land / Währung abweichen.

### Forecast ist nie eine offizielle Microsoft-Aussage

- Das Tool darf Prognosen bauen, aber **nur als Schätzung**.
- Forecasts müssen klar getrennt sein von:
	- offiziellen Microsoft-News
	- dokumentierten Stichtagen
	- explizit kommunizierten Preis- oder Packaging-Tabellen

## Ziel des Tools

Zeigt offizielle Microsoft-Lizenzierungsereignisse als belastbare Timeline und rechnet – wenn der Nutzer seinen Bestand kennt – den **voraussichtlichen Budgeteffekt** auf Monats-, Jahres- und Renewal-Basis aus.

## Gewünschte Ergebnis-Kategorien

Der Tracker sollte mindestens diese Zustände liefern:

1. `✅ Kein aktuelles offizielles Preisereignis für den gewählten Bestand`
2. `🟢 Offizielle Struktur-/Packaging-Änderung ohne direkten Mehrpreis`
3. `🟡 Preisänderung dokumentiert, greift aber erst zum Renewal`
4. `🟠 Materieller Budgeteffekt auf den aktuellen Bestand`
5. `🔴 Forecast / Planungsschätzung – manuell validieren`

## Kernfunktionen

- Timeline aller dokumentierten Microsoft-Lizenzierungsereignisse
- Filter nach Jahr, Region, Segment, Produktfamilie und Event-Typ
- saubere Unterscheidung zwischen `price increase`, `price decrease`, `packaging`, `sku split`, `end of sale`, `retirement`
- Renewal-Logik anhand eines angegebenen Vertrags- oder Verlängerungsdatums
- Delta-Rechner für vorhandene Bestände
- Forecast-Modul für Budgetrunden mit sauberem `offiziell` vs. `geschätzt`
- Alert-/Newsletter-CTA und PDF-Export

## Eingaben

### Pflichtfelder

- Produktfamilie / Fokusbereich
	- Microsoft 365
	- Office 365
	- Teams
	- Power Platform
	- Windows / EMS / Entra
	- Fabric / Power BI
- Region / Segment
	- global
	- EEA
	- Government
	- Nonprofit
- Analysejahr oder Zeitraum

### Bestandsfelder

- verwendete SKUs
- Anzahl pro SKU
- aktueller Preis bekannt `ja/nein`
- Renewal-Datum
- Vertragsmodell / Kanal optional

### Forecast-Felder

- Prognosehorizont in Monaten
- konservativ / neutral / aggressiv
- Preisbasis manuell bestätigen `ja/nein`

## Ausgaben

- chronologische Ereignisliste
- Einordnung nach Event-Typ
- dokumentierte Prozent- und Preisänderungen
- Budgeteffekt auf Monats-, Jahres- und Renewal-Sicht
- Kennzeichnung `offizieller Fakt` vs. `Forecast`
- Handlungshinweise für Budgetrunde, Erneuerung oder SKU-Review

## Entscheidungslogik

### Harte Regeln für Event-Daten

- Nur offizielle Microsoft-Links werden als `verifiziert` markiert.
- Preisänderungen ohne Stichtag oder Quelle werden **nicht** als offizielles Event übernommen.
- Forecast-Ereignisse werden nie in derselben Darstellungsebene wie echte Microsoft-News gemischt.

### Regeln für den Bestands-Impact

- Wenn `renewal_date < effective_date`, greift der neue Preis ggf. erst später.
- Wenn Microsoft explizit kommuniziert `existing customers remain on current pricing until renewal`, muss der Tracker genau diese Renewal-Logik anwenden.
- Wenn ein Event nur `with Teams` oder `no Teams` betrifft, dürfen nicht alle Suites pauschal betroffen markiert werden.

### Regeln für Forecasts

- Forecast nur auf Basis dokumentierter Historie und manuell gepflegter Annahmen.
- Kein Forecast ohne klar sichtbaren Disclaimer.
- Forecast darf Packaging-/Retirement-Signale einbeziehen, aber nicht als „Microsoft hat angekündigt“ formuliert werden, wenn es nur ein internes Modell ist.

## Bewertungsmodell

Neben den harten Regeln sollte das Tool Ereignisse gewichten:

- `+3` offiziell dokumentierte Preisänderung mit Prozent- und Stichtagsangabe
- `+2` Renewal-relevante Änderung auf Kern-SKUs des Kunden
- `+2` Packaging-Update mit neuem enthaltenen Leistungsumfang
- `+1` Event betrifft viele User / große SKU-Menge
- `-2` nur FAQ-/Kontextartikel ohne neue Preistabelle
- `-3` reine Forecast-Annahme

Zusätzlich kann ein `budget_risk_score` gebildet werden aus:

- Umfang des betroffenen Bestands
- Nähe des Renewal-Datums
- Höhe des Deltas
- Kritikalität der betroffenen Produktfamilie

## Benötigte Daten

### 1. Ereignisdatei `microsoft_price_events.json`

- `event_id`
- `title`
- `event_type`
- `published_at`
- `effective_at`
- `applies_on_renewal`
- `region_scope`
- `segment_scope`
- `product_family`
- `source_url`
- `source_label`
- `summary`

### 2. Preisdetails `microsoft_price_changes.json`

- `sku`
- `current_price`
- `new_price`
- `currency`
- `percent_change`
- `event_id`
- `with_teams_variant`
- `no_teams_variant`

### 3. Bestandsmapping `microsoft_inventory_mapping.json`

- Zuordnung interner SKU-Bezeichnungen zu offiziellen Microsoft-Namen
- Segment-/Regionsmapping
- Renewal-Logik je Vertragswelt

### 4. Forecast-Regeln `microsoft_price_forecast_rules.json`

- Gewichtung historischer Ereignisse
- Annahmen für Folgejahre
- Unsicherheitsstufen
- Anzeige-Disclaimer

## Verifizierte Weblinks / Quellenbasis

### A. Offizielles News-Hub

1. **Microsoft Licensing News**  
	https://www.microsoft.com/en-us/licensing/news  
	Relevanz: offizielles Hub für Preis-, Packaging-, End-of-sale- und Lizenzstruktur-News.

### B. Konkrete M365-Ereignisse

2. **Microsoft 365 Pricing and Packaging Updates**  
	https://www.microsoft.com/en-us/licensing/news/2026-M365-Packaging-Pricing-Updates  
	Relevanz: dokumentierte Preis- und Packaging-Anpassungen mit Stichtag `01.07.2026`.

3. **Packaging and Pricing Updates for Microsoft 365 Commercial Suites Public FAQs**  
	https://www.microsoft.com/en-us/licensing/news/2026-M365-Packaging-Pricing-Updates-FAQ  
	Relevanz: Renewal-, Scope- und FAQ-Kontext zum 2026-Update.

4. **Updates to Microsoft 365 licensing worldwide**  
	https://www.microsoft.com/en-us/licensing/news/Microsoft365-Teams-2025  
	Relevanz: weltweite `with Teams` / `no Teams`-Lizenzstruktur und Preislogik ab `01.11.2025`.

> Empfehlung: Das Tool sollte zusätzliche Ereignisse aus dem News-Hub später ebenfalls als Event-Typen unterstützen, z. B. `end of sale`, `sku retirement` oder `pricing consistency update`.

## Tool-Flow

### Schritt 1 – Filter & Zeitraum

- Produktfamilie, Segment, Region und Zeitraum wählen
- optional den eigenen Bestand aktivieren

### Schritt 2 – Ereignisse & Relevanz

- offizielle News-Timeline anzeigen
- relevante Events markieren
- Preisänderung vs. Packaging vs. Strukturänderung trennen

### Schritt 3 – Impact & Forecast

- Budgeteffekt auf Bestand rechnen
- Renewal-Effekt markieren
- Forecast separat als Schätzung anzeigen

## UI/UX nach `cms-m365lic`

- Hero mit Budget- und Renewal-Fokus: `Welche Microsoft-Lizenzänderungen treffen dich wirklich – und wann?`
- KPI-Karten für:
	- letztes offizielles Event
	- nächster relevanter Stichtag
	- Budgeteffekt auf Bestand
	- Forecast-Unsicherheit
- Timeline-Komponente als zentrales Element
- Filterpanel im Kartenstil
- Detailkarten pro Event mit Tabs: `Was ist passiert?`, `Ab wann?`, `Wer ist betroffen?`, `Was bedeutet das für mich?`

## Ergebnislogik / Textbausteine

### `Kein direkt relevanter Effekt`

- kein Event für gewählte SKUs
- oder Event betrifft andere Region / anderes Segment

### `Packaging-/Struktur-Änderung beobachten`

- offizielles Event vorhanden
- aber aktuell kein direkter Preisanstieg auf den Bestand

### `Zum Renewal budgetieren`

- offizielles Event dokumentiert
- Bestandskunde bleibt bis Renewal auf altem Preis
- Renewal-Datum liegt im Wirkzeitraum

### `Budgetrisiko akut`

- Event betrifft Kern-SKUs
- Renewal bald
- Delta finanziell relevant

### `Forecast – nur Schätzung`

- keine offizielle Microsoft-Aussage
- nur Planungshilfe auf Basis historischer Muster

## Benötigte Bausteine / Funktionen

- `load_microsoft_price_history()`
- `load_microsoft_price_events()`
- `filter_microsoft_price_history()`
- `map_inventory_to_price_events()`
- `calculate_price_change_impact()`
- `calculate_renewal_price_impact()`
- `build_price_change_forecast()`
- `render_price_tracker_page()`
- `export_price_tracker_pdf()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if source_domain != 'microsoft.com' => event_status = 'unverified'`
- `if event_type == 'forecast' => never_mix_with_official_timeline`
- `if applies_on_renewal == true and renewal_date > effective_at => impact_mode = 'renewal'`
- `if region_scope not contains selected_region => event_irrelevant`
- `if sku_variant == 'no_teams' => only_compare_against_no_teams_inventory`
- `if current_inventory_missing == true => show_market_view_without_budget_delta`

## Admin / Pflege

- manuelle Pflege neuer Events aus `Licensing News`
- Pflege der Produktnamen und SKU-Mappings
- Prüfung, ob Events Preis-, Packaging- oder Strukturthemen sind
- Pflege der Forecast-Regeln und Disclaimer
- regelmäßiger Abgleich der Eventtabellen mit Microsoft-Quellen

## SEO- und Content-Bausteine

### Fokus-Keywords

- `microsoft preiserhöhung`
- `m365 preiserhöhung 2026`
- `microsoft lizenzpreise`
- `microsoft teams no teams preise`
- `m365 renewal preise`
- `microsoft licensing news`

### Empfohlene FAQ-Blöcke

- Wann erhöht Microsoft die Preise für Microsoft 365?
- Gelten neue Preise sofort oder erst zum Renewal?
- Betrifft die Änderung auch Bestandskunden?
- Was ist der Unterschied zwischen Preisupdate und Packaging-Update?
- Was bedeuten `with Teams` und `no Teams` preislich?
- Wie belastbar sind Forecasts für kommende Budgetjahre?

## MVP

- offizielle Timeline
- Filter nach Jahr / Region / Produktfamilie
- Bestands-Impact-Rechner
- einfache Forecast-Ansicht mit starkem Disclaimer

## Phase 2

- E-Mail-Alert / Change-Watch
- CSV-Import von Lizenzbeständen
- PDF für Budgetrunden / Renewals
- Verknüpfung zu Spezialtools in der Toolbox

## Akzeptanzkriterien

- Tool trennt sauber zwischen offiziellen Microsoft-News und eigener Prognose
- Tool berücksichtigt `effective date`, `renewal impact` und Segment-/Regionslogik
- Tool behandelt Packaging- und Strukturereignisse nicht fälschlich als reine Preiserhöhungen
- Budgeteffekte werden nur aus sichtbaren Preis- und Bestandsdaten berechnet
- alle Events sind quellenbasiert und im Admin pflegbar

## Offene Pflegepunkte

- News-Artikel können Preis-, Packaging- und Channel-Logik mischen; Event-Typen deshalb sauber normalisieren
- USD-Listenpreise nicht als globale Endkundenwahrheit darstellen
- Forecast nur als Planungshilfe, niemals als angeblich angekündigte Microsoft-Maßnahme formulieren
- Historie bewusst offen für weitere Event-Klassen wie `end of sale`, `retirement`, `pricing consistency update`