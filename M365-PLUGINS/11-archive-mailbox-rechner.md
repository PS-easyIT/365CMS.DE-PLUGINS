# Archive Mailbox Rechner

- **Priorität:** mittel bis hoch
- **Datenquelle:** Exchange-Limits + Archiving-Servicebeschreibung + Auto-Expanding-Regeln + Preisdatei
- **Aufwand:** niedrig bis mittel
- **SEO-Potenzial:** ★★
- **Empfohlener Slug:** `/archive-mailbox-rechner`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Das Tool soll beantworten, für wie viele Nutzer eine Archive Mailbox sinnvoll oder nötig ist, welche Zusatzkosten daraus entstehen und wann statt Archivierung eher Cleanup oder ein Planwechsel besser ist.

## Verifizierte Microsoft-Leitplanken

### Archivierung ist je nach Basisplan enthalten oder Add-on

- Exchange Online Archiving für Exchange Online ist als Add-on u. a. für Exchange Online Plan 1, Business Basic, Business Standard, Office 365 E1 und Microsoft 365 F3 relevant.
- In höheren Plänen wie Exchange Online Plan 2, Microsoft 365 Business Premium, Microsoft 365 E3/E5 ist Archivierung bereits enthalten.

### Archivgröße folgt einem Stufenmodell

- Jeder Archiving-Subscriber startet mit **100 GB** Archivspeicher.
- Mit Auto-Expanding kann das Archiv bis **1,5 TB** wachsen.

### Auto-Expanding ist nicht für wilden Archiv-Müllkippen-Betrieb gedacht

- Auto-Expanding ist für einzelne Benutzer oder Shared Mailboxes gedacht.
- Nutzung als Sammelarchiv für mehrere Personen via Journaling, Transportregeln oder Auto-Forwarding ist laut Microsoft nicht zulässig.
- Die Growth Rate darf **1 GB pro Tag** nicht überschreiten.

### Hold / Compliance beeinflussen Archiventscheidungen

- In-Place Hold / Litigation Hold greifen auf primäres und Archiv-Postfach.
- Auto-Expanding und Archive haben Auswirkungen auf Recoverable Items, Suche und Aufbewahrung.

### Mailboxgröße ist ein guter, aber nicht der einzige Trigger

- User-Mailboxen liegen je nach Plan bei **50 GB** oder **100 GB**.
- Eine hohe Größe allein ist noch kein Automatismus; Wachstum, Retention, Aufbewahrungspflichten und Suchbedarf zählen mit.

## Ziel des Tools

Unternehmen sollen berechnen können, für wie viele Nutzer eine Archive Mailbox sinnvoll oder nötig ist und welche Zusatzkosten daraus entstehen.

## Gewünschte Ergebnis-Kategorien

1. `✅ Kein Archiv nötig`
2. `🟡 Archiv-Add-on sinnvoll`
3. `🔵 Archiv ist bereits enthalten – aktivieren statt zukaufen`
4. `🟠 Auto-Expanding / Compliance-Pfad nötig`
5. `🔴 Cleanup oder Planwechsel prüfen`

## Kernfunktionen

- Analyse der durchschnittlichen Mailbox-Größe
- Einbezug von Aufbewahrungsdauer und Wachstum
- Ermittlung potenzieller Archiv-Kandidaten
- Kostenberechnung für Exchange Online Archiving
- Hinweise zu Hold-, Retention- und Auto-Expanding-Szenarien

## Eingaben

### Basisdaten

- Anzahl Mailboxen
- aktueller Plan / Basislizenz
- durchschnittliche und maximale Mailbox-Größe

### Archiv-/Compliance-Bedarf

- jährliches Wachstum
- Aufbewahrungsdauer
- Hold / eDiscovery / Retention nötig ja/nein
- Shared-Mailbox-Fälle enthalten ja/nein

## Ausgaben

- geschätzte Anzahl Archiv-Kandidaten
- Zusatzkosten pro Monat / Jahr
- Handlungsoptionen: Archiv, Cleanup, Planwechsel
- Hinweis, ob Archiv bereits im Plan enthalten ist
- Auto-Expanding-/Compliance-Hinweise

## Entscheidungslogik

### 1. Planfähigkeit prüfen

- Archiv bereits enthalten?
- Archiv nur via Add-on möglich?
- technischer / Hybrid-Sonderfall?

### 2. Kandidaten bewerten

- große Postfächer
- hohe Wachstumsrate
- lange Aufbewahrung
- Compliance-Fälle

### 3. Maßnahme ableiten

- nur Archiv aktivieren
- Add-on kaufen
- auf höheren Plan wechseln
- zuerst Cleanup / Datenhygiene

## Benötigte Daten

### 1. `exchange_archiving_matrix.json`

- Pläne mit Archiv inklusive
- Add-on-fähige Basispläne
- Auto-Expanding-Fähigkeit

### 2. `archive_candidate_rules.json`

- Schwellenwerte
- Wachstumsregeln
- Compliance-Trigger

### 3. `pricing.json`

- Archiving Add-on
- relevante Plan-Upgrades

## Verifizierte Weblinks / Quellenbasis

1. **Exchange Online limits**  
	https://learn.microsoft.com/en-us/office365/servicedescriptions/exchange-online-service-description/exchange-online-limits

2. **Exchange Online Archiving service description**  
	https://learn.microsoft.com/en-us/office365/servicedescriptions/exchange-online-archiving-service-description/exchange-online-archiving-service-description

3. **Learn about auto-expanding archiving**  
	https://learn.microsoft.com/en-us/microsoft-365/compliance/autoexpanding-archiving

4. **Microsoft 365 Business Plans**  
	https://aka.ms/M365BusinessPlans

5. **Microsoft 365 Enterprise Plans**  
	https://aka.ms/M365EnterprisePlans

## Tool-Flow

### Schritt 1 – Ist-Situation erfassen

- Plan
- Größen
- Wachstum

### Schritt 2 – Archivkandidaten ableiten

- Größe
- Compliance
- Shared-Mailbox-Fälle

### Schritt 3 – Empfehlung ausgeben

- Add-on
- inklusive Funktion aktivieren
- Upgrade
- Cleanup

## UI/UX nach `cms-m365lic`

- kompakte Eingabeoberfläche
- Ergebnisblock mit Kandidatenzahl und Kosten
- kurze Hinweiskarten zu Retention, Hold und Auto-Expanding
- Badge `bereits enthalten` vs. `Add-on nötig`

## Ergebnislogik / Textbausteine

### `Archiv bereits enthalten`

- aktueller Plan deckt Archivierung bereits ab
- technischer Rollout statt Zukauf

### `Archiv-Add-on sinnvoll`

- Basisplan geeignet
- Archiv nicht enthalten
- Bedarf klar vorhanden

### `Cleanup zuerst`

- Größe allein kommt vor allem aus Altlasten
- Archiv wäre nur Symptombehandlung

## Benötigte Bausteine / Funktionen

- `calculate_archive_mailbox_candidates()`
- `calculate_archive_mailbox_costs()`
- `evaluate_autoexpanding_need()`
- `build_archive_recommendation()`
- `render_archive_mailbox_page()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if archive_included === true => prefer_enablement_over_purchase`
- `if mailbox_growth_rate_high === true && compliance_needed === true => autoexpanding_path`
- `if use_case_is_multi_entity_archive === true => reject`
- `if growth_rate_gb_per_day > 1 => autoexpanding_warning`
- `if mailbox_size_forecast_below_threshold && cleanup_potential_high === true => cleanup_first`

## Admin / Pflege

- Preis- und Grenzwertpflege
- Textpflege für Compliance-Hinweise
- Pflege der Add-on-/Inklusiv-Matrix

## SEO- und Content-Bausteine

### Fokus-Keywords

- `archive mailbox rechner`
- `exchange online archiving lohnt sich`
- `auto expanding archive 1.5 tb`
- `archive mailbox add on`

### Empfohlene FAQ-Blöcke

- Wann brauche ich Exchange Online Archiving?
- Welche Pläne enthalten Archivierung bereits?
- Wie funktioniert Auto-Expanding Archiving?
- Darf ich ein Archiv für mehrere Nutzer zweckentfremden?

## MVP

- Kandidatenzahl
- Kosten
- Handlungsempfehlung
- Inklusive-vs-Add-on-Hinweis

## Phase 2

- Segmentierung nach Benutzergruppen
- Export als Management-Zusammenfassung
- CSV-Import aus Mailbox-Statistiken

## Akzeptanzkriterien

- Tool trennt `bereits enthalten`, `Add-on nötig` und `Upgrade sinnvoll` sauber.
- Auto-Expanding- und Compliance-Hinweise werden korrekt berücksichtigt.
- Archiv-Missbrauch als Sammelspeicher wird ausgeschlossen.
- Ergebnis nennt konkrete nächste Schritte.

## Offene Pflegepunkte

- Planmatrix regelmäßig gegen Service Descriptions prüfen.
- Preis- und Produktnamen konsistent halten.
- Hybrid-Sonderfälle ggf. in Phase 2 vertiefen.