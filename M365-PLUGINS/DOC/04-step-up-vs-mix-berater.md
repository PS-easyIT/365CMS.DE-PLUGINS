# Step-Up vs. Mix-Berater

- **Priorität:** hoch
- **Datenquelle:** `plans.json`, `addons.json`, Persona-Logik, Channel-/Term-Regeln
- **Aufwand:** mittel
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/m365-step-up-vs-mix`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Das Tool beantwortet eine der teuersten Microsoft-365-Fragen überhaupt: „Alle höher lizenzieren oder gezielt mischen?“ Es soll nicht nur `E3 vs. E5` rechnen, sondern transparent zeigen:

1. wann ein globales Step-Up sinnvoll ist
2. wann ein Mischmodell aus Basislizenz + Add-ons wirtschaftlicher ist
3. wie viel Einfachheit das Voll-Upgrade bringt
4. wo Mischmodelle Governance- oder Betriebsaufwand erhöhen

## Verifizierte Microsoft-Leitplanken

### New Commerce erlaubt Upgrades, aber mit Regeln

- New Commerce unterstützt Upgrades von einer Lizenz auf eine berechtigte Ziel-Lizenz.
- Kündigungen sind nur im frühen Fenster des Terms möglich.
- Preisänderungen betreffen laufende Subscriptions in der Regel erst bei Renewal oder Conversion.

### Copilot und Teams Phone sind gute „Entscheidungstreiber“

- Copilot verlangt eine berechtigte Basislizenz und kann Step-up- oder Mix-Entscheidungen kippen.
- Teams Phone ist häufig ein Add-on-Pfad und nicht automatisch Teil jeder Ziel-SKU.

### Power Platform und Premium-Connectoren verzerren Billigvergleiche

- Ein günstiger Basistarif verliert schnell an Attraktivität, wenn Premium-Connectoren oder Dataverse-Premium-Nutzung dazukommen.
- Deshalb muss der Rechner Add-on-Folgekosten sauber berücksichtigen.

### Small-Business- und Segmentgrenzen können Mixmodelle begrenzen

- Im Price-/Offer-Matrix-Kontext gelten für manche Small-Business-SKUs 300er Maxima.
- Ein Mixmodell darf deshalb nicht nur technisch, sondern auch kanal- und segmentseitig plausibel sein.

## Ziel des Tools

Vergleich von mindestens zwei Strategien:

- **Szenario A:** globales Step-Up
- **Szenario B:** Mix aus Basislizenz + gezielten Add-ons / Nutzergruppen

Optional:

- **Szenario C:** teilweises Step-Up für nur einzelne Personas

## Gewünschte Ergebnis-Kategorien

1. `✅ Globales Step-Up empfohlen`
2. `🟡 Mischmodell wirtschaftlicher`
3. `🟠 Teilweises Step-Up empfohlen`
4. `🔵 Kosten fast gleich – Einfachheit entscheidet`
5. `🔴 Detailprüfung nötig`

## Kernfunktionen

- Szenario A: globales Step-Up
- Szenario B: Basismix + Add-ons nach Bedarf
- optional Szenario C: selektives Step-Up
- Kostenvergleich und Feature-Abdeckung
- Delta-Ansicht pro Monat, Jahr und 36 Monate
- Governance- / Admin-Aufwand als zweiter Score neben Preis
- Empfehlung nach Budget, Security-Level und Zielgruppenmix

## Eingaben

- Ausgangsplan oder heutiger Bestand
- Ziel-Feature-Mix
- Anzahl Nutzer je Persona
- Pflichtfunktionen wie Defender, Purview, Intune, Copilot, Teams Phone, Premium Connectoren
- Vertragslogik / Laufzeitmodell
- Kanalhinweise: CSP / Enterprise / offen

## Ausgaben

- Szenario-Vergleich A/B/C
- Kostenunterschied absolut und prozentual
- Funktionslücken und Überlizenzierung
- Komplexitäts-Score je Szenario
- Handlungsempfehlung mit Begründung

## Entscheidungslogik

### 1. Ziel-Funktionsset definieren

- Welche Funktionen müssen wirklich für alle Nutzer gelten?
- Welche nur für Teilgruppen?

### 2. Globales Step-Up rechnen

- alle Nutzer auf gemeinsame Ziel-SKU
- Add-ons nur, wenn auch dort noch nötig

### 3. Mischmodell rechnen

- Personas aufteilen
- je Persona kleinste passende Basislizenz wählen
- Add-ons je Persona ergänzen

### 4. Verwaltungs- und Risikoaufschlag bewerten

- mehr SKUs = mehr Verwaltungsaufwand
- mehr Add-ons = höhere Fehleranfälligkeit
- weniger Standardisierung = höherer Betriebsaufwand

## Bewertungsmodell

Empfohlene Gewichtung:

- `50 %` Kosten
- `20 %` Feature-Vollständigkeit
- `15 %` Betriebs-/Admin-Einfachheit
- `10 %` Zukunftsfähigkeit
- `5 %` Beschaffungs-/Kanalrisiko

## Benötigte Daten

### 1. `plans.json`

- Basispreise
- Funktionsabdeckung
- Zielgruppenfit

### 2. `addons.json`

- Add-on-Kosten
- Prerequisites
- Overlap-Regeln

### 3. `persona_presets.json`

- Persona-Defaults
- Minimalfeatures je Rolle
- Prioritäten

### 4. `scenario_weights.json`

- Gewichtung Preis vs. Einfachheit
- Ampellogik

## Verifizierte Weblinks / Quellenbasis

1. **New commerce experience for license-based services**  
	https://learn.microsoft.com/en-us/partner-center/customers/new-commerce-license-based

2. **Pricing and offers**  
	https://learn.microsoft.com/en-us/partner-center/pricing/pricing-and-offers

3. **License options for Microsoft 365 Copilot**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-licensing

4. **Microsoft 365 app and network requirements for Microsoft 365 Copilot**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-requirements

5. **Teams Phone licensing**  
	https://learn.microsoft.com/en-us/microsoftteams/teams-phone-licensing

6. **Power Platform licensing FAQs**  
	https://learn.microsoft.com/en-us/power-platform/admin/powerapps-flow-licensing-faq

7. **Licensing overview for Microsoft Power Platform**  
	https://learn.microsoft.com/en-us/power-platform/admin/pricing-billing-skus

8. **Microsoft 365 Business Plans**  
	https://aka.ms/M365BusinessPlans

9. **Microsoft 365 Enterprise Plans**  
	https://aka.ms/M365EnterprisePlans

## Tool-Flow

### Schritt 1 – Ausgangslage erfassen

- heutige Lizenzbasis
- Rollen / Personas
- Pflichtfeatures

### Schritt 2 – Strategien rechnen

- globales Step-Up
- Mischmodell
- optional selektives Step-Up

### Schritt 3 – Entscheidung visualisieren

- Kostenvergleich
- Komplexitätsvergleich
- Empfehlung und nächste Schritte

## UI/UX nach `cms-m365lic`

- 2- oder 3-Spalten-Vergleich mit KPI-Karten je Szenario
- Delta-Banner in Gold/Teal
- Vergleichstabelle mit `enthalten`, `nur im Mix nötig`, `nur im Step-Up wirtschaftlich`
- Ampel für `Kosten`, `Einfachheit`, `Governance`

## Ergebnislogik / Textbausteine

### `Globales Step-Up empfohlen`

- Mehrkosten sind gering oder durch Einfachheit gerechtfertigt.
- viele Spezialfunktionen werden breit benötigt.

### `Mischmodell empfohlen`

- klare Rollentrennung
- hohe Überlizenzierung im Step-Up-Szenario
- Add-on-Komplexität beherrschbar

### `Teilweises Step-Up`

- nur einzelne Rollen brauchen Premium-Funktionen
- restliche Nutzer würden sonst überlizenziert

## Benötigte Bausteine / Funktionen

- `build_stepup_scenario()`
- `build_mix_scenario()`
- `build_partial_stepup_scenario()`
- `compare_license_scenarios()`
- `calculate_scenario_delta()`
- `score_scenario_complexity()`
- `render_stepup_vs_mix_page()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if premium_feature_needed_for_all_users === true => boost_stepup_score`
- `if only_subset_needs_premium_features === true => boost_mix_score`
- `if copilot_base_incompatibility_exists === true => exclude_invalid_scenarios`
- `if power_platform_premium_needed_for_small_subset === true => avoid_global_upgrade`
- `if sku_count_in_mix > defined_threshold => add_admin_complexity_penalty`

## Admin / Pflege

- Pflege von Persona-Defaults
- Pflege der Bewertungsgewichte
- Preis- und Add-on-Updates
- Pflege von Kanal- und Segmenthinweisen

## SEO- und Content-Bausteine

### Fokus-Keywords

- `e3 vs e5 rechner`
- `business premium vs e3`
- `m365 step up oder add on`
- `microsoft 365 mischmodell`
- `copilot upgrade oder add on`

### Empfohlene FAQ-Blöcke

- Wann lohnt sich ein globales Step-Up?
- Wann ist ein Mischmodell günstiger?
- Wie viel Verwaltungsaufwand erzeugen mehrere SKUs?
- Welche Rolle spielen Copilot und Teams Phone in der Entscheidung?

## MVP

- `E3 vs. E5`
- `Business Standard vs. Business Premium`
- Mix-Kalkulation für 2 bis 4 Gruppen
- Kosten- und Komplexitätsscore

## Phase 2

- Szenario-Export
- Charts für 12/36 Monate
- Einbindung von Partner-Margen
- Bestandsimport aus CSV / Tenant-Audit

## Akzeptanzkriterien

- Tool vergleicht mindestens zwei valide Strategien transparent.
- Preis und Komplexität werden getrennt bewertet.
- Copilot-, Teams-Phone- und Premium-Connector-Sonderfälle werden berücksichtigt.
- Ergebnis zeigt klar, warum eine Strategie gewinnt.

## Offene Pflegepunkte

- Step-up-/Conversion-Regeln je Kanal und SKU sauber pflegen.
- manche Add-on-Kombinationen brauchen manuelle Ausnahmeregeln statt reinen Score.
- `aka.ms`-Vergleichsseiten und Produktnamen regelmäßig normalisieren.