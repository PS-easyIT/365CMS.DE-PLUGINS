# Add-On-Konfigurator

- **Priorität:** hoch
- **Datenquelle:** `plans.json` + `addons.json` + Prerequisite-/Overlap-Regeln + offizielle Microsoft-Referenzen
- **Aufwand:** mittel
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/m365-add-on-konfigurator`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Der Add-on-Konfigurator soll aus einer Basislizenz plus Wunschfunktionen ein belastbares Zielbild bauen und dabei vier typische Fehler vermeiden:

1. Add-on wird gewählt, obwohl es bereits enthalten ist
2. Add-on wird gewählt, obwohl eine Voraussetzung fehlt
3. Ein teurer Add-on-Stapel wäre durch ein Basis-Upgrade günstiger
4. Verbrauchsprodukte werden wie klassische Per-User-SKUs behandelt

## Verifizierte Microsoft-Leitplanken

### Add-ons haben echte Voraussetzungen

- Im Partner-Center-/New-Commerce-Kontext sind Add-ons als eigene Offer-Typen gekennzeichnet.
- Add-ons setzen voraus, dass mindestens ein kompatibles Basisangebot vorhanden ist.
- Diese Prerequisites müssen im Tool technisch geprüft werden.

### Copilot braucht eine berechtigte Basislizenz

- Microsoft 365 Copilot ist ein Add-on und verlangt eine berechtigte Basislizenz.
- Work-based Chat und tiefere Copilot-Erlebnisse sind nicht mit bloßem „ich will KI“ erledigt.

### Teams Phone ist Add-on- und Szenario-abhängig

- Teams Phone Standard ist ein eigener Lizenzpfad mit Prerequisites.
- Wenn Microsoft die PSTN-Anbindung liefern soll, braucht der Nutzer zusätzlich einen Calling Plan oder ein passendes Bundle.
- Resource Accounts dürfen nicht mit normalen Enduser-Lizenzen modelliert werden.

### Power Platform Premium ist von seeded M365-Rechten zu trennen

- Premium-Connectoren, Custom Connectoren und On-Prem-Gateways erfordern in vielen Fällen separate Power-Platform-Lizenzen.
- Das Tool darf also nicht automatisch `M365 vorhanden => Power Platform erledigt` annehmen.

### Backup ist Verbrauch, kein klassisches User-Add-on

- Microsoft 365 Backup wird als **Pay-as-you-go** auf Basis geschützter GB abgerechnet.
- Es ist daher logisch eher ein Verbrauchsmodul als ein normales Per-User-Add-on.

### Es gibt kostenlose Spezial-SKUs

- In neuen Preislisten tauchen auch **No-cost-SKUs** auf, z. B. für bestimmte Spezialfälle wie Teams Phone Resource Accounts.
- Diese sollten im Tool sichtbar, aber klar von normalen Nutzerlizenzen getrennt sein.

## Ziel des Tools

Ein Rechner, der aus Basislizenz plus gewünschten Zusatzleistungen den realistischen Endpreis bildet und gleichzeitig aufdeckt, welche Add-ons sinnvoll, redundant, nicht zulässig oder besser durch ein Upgrade zu lösen sind.

## Gewünschte Ergebnis-Kategorien

1. `✅ Add-on sinnvoll und kompatibel`
2. `🟡 Add-on möglich, aber Voraussetzung / Zusatzhinweis nötig`
3. `🟠 Upgrade statt Add-on wirtschaftlicher`
4. `🔴 Add-on redundant oder inkompatibel`
5. `🔵 Verbrauchs-/Spezialfall separat kalkulieren`

## Kernfunktionen

- Auswahl einer Basislizenz
- Auswahl mehrerer Add-ons
- Erkennung von Überschneidungen und Doppelkäufen
- Preisaufsummierung pro User, Gruppe und gesamt
- Prerequisite-Checks und Kompatibilitätsprüfung
- Gegenüberstellung `mit Basis enthalten` vs. `zusätzlich nötig`
- Upgrade-vs-Add-on-Vergleich
- Trennung zwischen Per-User-, Per-Tenant- und Verbrauchslogik

## Eingaben

### Basisdaten

- Basisplan
- Anzahl Nutzer
- Kanal / Segment optional
- Laufzeitmodell

### Wunsch-Add-ons

- Copilot
- Teams Phone / Calling Plan
- Teams Premium
- Defender / Security-Erweiterungen
- Entra / Identity-Erweiterungen
- Power BI / Project / Visio
- Power Platform Premium / Process / Copilot Studio
- Exchange Archiving
- Backup

### Zusatzparameter

- PSTN mit Microsoft oder Drittanbieter
- Premium-Connectoren ja/nein
- Resource-Accounts nötig ja/nein
- Verbrauchsannahmen für Backup / AI / Storage

## Ausgaben

- Gesamtkosten pro Monat / Jahr
- Liste tatsächlich nötiger Add-ons
- Warnungen zu Doppelkäufen und fehlenden Voraussetzungen
- Upgrade-Empfehlung, wenn günstiger oder sauberer
- separates Verbrauchsmodul für nicht per User kalkulierbare Themen

## Entscheidungslogik

### 1. Prerequisite-Prüfung

- Basislizenz vorhanden?
- passender Kanal / Segment?
- Spezialrolle wie Resource Account?

### 2. Redundanz- und Overlap-Prüfung

- gewünschtes Add-on bereits enthalten?
- ist dieselbe Funktion in anderem Add-on doppelt enthalten?
- erzeugt ein Basis-Upgrade weniger Komplexität?

### 3. Preislogik

- Per-User-Add-ons direkt multiplizieren
- Per-Tenant-/No-cost-SKUs getrennt ausweisen
- Verbrauchsmodelle separat kalkulieren

### 4. Empfehlung

- Add-on beibehalten
- Add-on ersetzen
- Basisplan upgraden
- Spezialfall separat behandeln

## Benötigte Daten

### 1. `addons.json`

- Add-on-Name
- Preis
- Abrechnungstyp (`per_user`, `per_tenant`, `consumption`, `no_cost`)
- Prerequisites
- Includes / Excludes

### 2. `addon_overlap_rules.json`

- redundante Kombinationen
- Mutual-Exclusion-Fälle
- Upgrade-Empfehlungen statt Add-on-Stapel

### 3. `plans.json`

- Basisplan-Familie
- enthaltene Features
- Channel-/Term-Verfügbarkeit

### 4. `consumption_modules.json`

- Backup-Preis pro GB
- Copilot-/AI-Hinweise
- tenantweite Verbrauchsannahmen

## Verifizierte Weblinks / Quellenbasis

1. **License options for Microsoft 365 Copilot**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-licensing

2. **Microsoft 365 app and network requirements for Microsoft 365 Copilot**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-requirements

3. **Teams Phone licensing**  
	https://learn.microsoft.com/en-us/microsoftteams/teams-phone-licensing

4. **Microsoft Teams Phone Resource Account licenses**  
	https://learn.microsoft.com/en-us/microsoftteams/teams-add-on-licensing/virtual-user

5. **Power Platform licensing FAQs**  
	https://learn.microsoft.com/en-us/power-platform/admin/powerapps-flow-licensing-faq

6. **Licensing overview for Microsoft Power Platform**  
	https://learn.microsoft.com/en-us/power-platform/admin/pricing-billing-skus

7. **Overview of Microsoft 365 Backup**  
	https://learn.microsoft.com/en-us/microsoft-365/backup/backup-overview

8. **Pricing and offers**  
	https://learn.microsoft.com/en-us/partner-center/pricing/pricing-and-offers

9. **New commerce experience for license-based services**  
	https://learn.microsoft.com/en-us/partner-center/customers/new-commerce-license-based

## Tool-Flow

### Schritt 1 – Basislizenz wählen

- Plan wählen
- Nutzerzahl und Term festlegen

### Schritt 2 – Zusatzbedarf aktivieren

- KI / Telefonie / Security / Platform / Backup
- Spezialparameter setzen

### Schritt 3 – Ergebnis prüfen

- echte Add-ons
- redundante Add-ons
- Upgrade-Alternative
- Verbrauchsblöcke separat

## UI/UX nach `cms-m365lic`

- linker Konfigurator, rechter Live-Preisblock
- farbige Tags für `enthalten`, `nötig`, `doppelt`, `nicht kompatibel`, `verbrauchsbasiert`
- Ergebnis-Sektion als SKU-Summenblock wie im Lizenzberater
- Umschalter `Add-ons behalten` vs. `Upgrade prüfen`

## Ergebnislogik / Textbausteine

### `Add-on sinnvoll`

- Funktion fehlt in der Basislizenz
- Prerequisite erfüllt
- kein besseres Upgrade vorhanden

### `Upgrade statt Add-on`

- mehrere Add-ons würden zusammen teurer oder komplexer
- Basis-Upgrade deckt denselben Bedarf sauberer ab

### `Verbrauch separat`

- Produkt ist kein klassischer Per-User-Zuschlag
- Ergebnis braucht Mengenannahmen oder Nutzungsschätzung

## Benötigte Bausteine / Funktionen

- `load_addon_catalog()`
- `resolve_addon_prerequisites()`
- `detect_redundant_addons()`
- `compare_addons_vs_upgrade()`
- `calculate_addon_bundle_total()`
- `calculate_consumption_modules()`
- `render_addon_configurator_page()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if addon_type === 'addon' && prerequisite_missing === true => incompatible`
- `if addon_name === 'copilot' && copilot_base_eligible === false => require_base_upgrade`
- `if teams_phone_requested === true && pstn_provider === 'microsoft' && calling_plan_missing === true => warn_or_add_calling_plan`
- `if resource_account === true => map_to_resource_account_sku`
- `if premium_connectors_required === true && only_seeded_rights === true => power_platform_premium_required`
- `if addon_feature_already_included === true => redundant`
- `if module_billing_type === 'consumption' => separate_cost_block`

## Admin / Pflege

- Pflege von Add-on-Preisen
- Pflege von Kompatibilitäts- und Redundanzregeln
- Pflege von Bundle-Empfehlungen
- Pflege von Verbrauchsmodulen und Standardannahmen
- quartalsweise Quellen- und Preisprüfung

## SEO- und Content-Bausteine

### Fokus-Keywords

- `m365 add on konfigurator`
- `microsoft 365 add ons vergleichen`
- `copilot add on voraussetzungen`
- `teams phone add on`
- `power platform premium connector lizenz`

### Empfohlene FAQ-Blöcke

- Welche Microsoft-365-Add-ons lohnen sich wirklich?
- Wann ist ein Add-on überflüssig?
- Wann ist ein Upgrade günstiger als mehrere Add-ons?
- Ist Microsoft 365 Backup ein normales Add-on?

## MVP

- Basislizenz + 10 bis 15 Haupt-Add-ons
- Preis + Warnhinweise
- Upgrade-vs-Add-on-Vergleich

## Phase 2

- Bundle-Vorschläge
- Vergleich zweier Add-on-Setups
- Export als Angebotsvorschlag
- Partner-Margen / EK-VK-Sicht

## Akzeptanzkriterien

- Tool erkennt Prerequisites und Redundanzen zuverlässig.
- Verbrauchsprodukte werden nicht fälschlich als Per-User-Lizenz kalkuliert.
- Teams Phone, Copilot und Power Platform werden mit ihren Sonderlogiken korrekt behandelt.
- Upgrade-Empfehlung erscheint dort, wo Add-ons wirtschaftlich keinen Sinn ergeben.

## Offene Pflegepunkte

- manche Produktseiten auf `microsoft.com` sind redirect-/rendering-anfällig; Preisdaten deshalb zentral pflegen.
- Upgrade-vs-Add-on-Regeln müssen je Kanal und Markt gepflegt werden.
- Security- und Compliance-Add-ons sollten vor Implementierung gegen aktuelle Produktbegriffe normalisiert werden.