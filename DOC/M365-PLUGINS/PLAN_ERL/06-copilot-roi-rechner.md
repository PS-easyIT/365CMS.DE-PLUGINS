# Copilot ROI-Rechner

- **Priorität:** sehr hoch
- **Datenquelle:** Preis-/Annahmen-Datei + Copilot-Lizenz-/Voraussetzungsregeln
- **Aufwand:** niedrig bis mittel
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/copilot-roi-rechner`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Das Tool ist das stärkste SEO- und Lead-Instrument für das Copilot-Thema. Es soll nicht nur einen ROI ausspucken, sondern drei Fragen sauber beantworten:

1. Sind die ausgewählten Nutzer überhaupt Copilot-fähig?
2. Ab wie vielen Minuten Zeitgewinn pro Tag trägt sich die Investition?
3. Lohnt sich ein breiter Rollout oder eher ein Pilot für definierte Rollen?

## Verifizierte Microsoft-Leitplanken

### Copilot ist ein Add-on mit Basisvoraussetzungen

- Microsoft 365 Copilot verlangt eine berechtigte Basislizenz.
- Nutzer benötigen ein Microsoft-Entra-Konto.
- Copilot wird nur auf **primären Exchange-Online-Postfächern** unterstützt.

### App- und Betriebsbereitschaft beeinflussen die Realisierbarkeit

- Microsoft 365 Apps müssen bereitgestellt sein.
- Für bestimmte Erlebnisse sind OneDrive, aktuelle Apps und passende Privacy-/Netzwerk-Konfigurationen nötig.
- Teams-Meeting-Kontext nach dem Meeting setzt Transkription oder Aufzeichnung voraus.

### Teams Phone / Spezialfälle sind Zusatzlogik

- Für Copilot in Teams Phone über PSTN braucht es Teams Phone, Calling Plan und Copilot-Lizenz.
- VoIP-only ist lizenzseitig einfacher als PSTN.

### Copilot Chat ist nicht gleich Microsoft 365 Copilot

- Web-based Copilot Chat kann bei berechtigten Microsoft-365-Subscriptions ohne extra Copilot-Lizenz verfügbar sein.
- Work-based Chat und volle M365-Copilot-Erlebnisse benötigen die Copilot-Lizenz.

### ROI ist immer Annahmenmodell, keine Microsoft-Garantie

- Microsoft liefert Lizenz- und technische Voraussetzungen, aber keinen universellen Stundenersparniswert.
- Zeitgewinn, Adoptionsrate, Schulungsaufwand und Rollout-Reife müssen als Annahmen transparent modelliert werden.

## Ziel des Tools

Unternehmen geben Nutzerzahl, Stundenlohn, Adoptionsquote und erwartete Zeitersparnis an und erhalten einen verständlichen ROI, Break-Even in Minuten pro Tag und eine Empfehlung für Pilot vs. breiten Rollout.

## Gewünschte Ergebnis-Kategorien

1. `✅ Starker Business Case`
2. `🟡 Solider Pilot-Kandidat`
3. `🟠 Grenzfall – Adoption / Enablement entscheidet`
4. `🔴 Wirtschaftlich kritisch`
5. `🔵 Lizenz-/Readiness-Prüfung zuerst`

## Kernfunktionen

- ROI-Berechnung pro Monat und Jahr
- Break-Even auf Basis eingesparter Minuten pro Tag
- Szenarien `konservativ`, `realistisch`, `optimistisch`
- Chart für Kosten vs. Produktivitätsgewinn
- Rollout-Modus `Pilot` vs. `Broad Rollout`
- CTA zu Copilot-Pilot, Readiness-Check und Lizenzberatung

## Eingaben

### Wirtschaftliche Eingaben

- Anzahl Copilot-Nutzer
- Lizenzpreis pro Nutzer
- durchschnittlicher Vollkosten-/Stundenlohn
- Zeitersparnis pro Tag in Minuten
- Arbeitstage pro Monat
- Einführungs- und Schulungskosten

### Readiness-Eingaben

- berechtigte Basislizenz vorhanden ja/nein
- primäres Exchange-Online-Postfach vorhanden ja/nein
- OneDrive / Microsoft 365 Apps ausgerollt ja/nein
- Transkription / Recording in Teams verfügbar ja/nein

### Rollout-Eingaben

- Pilot oder Vollausbau
- Abteilungen / Personas
- erwartete Adoptionsrate
- Ramp-up-Dauer in Monaten

## Ausgaben

- monatlicher Produktivitätsgewinn
- Netto-ROI pro Monat / Jahr
- Break-Even in Minuten pro Tag
- Diagramm mit Gewinnzone
- Pilot-Empfehlung oder Rollout-Empfehlung
- Hinweisblock zu Lizenz- und Readiness-Lücken

## Entscheidungslogik

### 1. Lizenz- und Readiness-Gate

- ohne berechtigte Basislizenz => kein echter ROI, sondern Hinweis `Lizenzpfad zuerst`
- ohne primäres Exchange-Online-Postfach => kein valides Copilot-Szenario
- fehlende OneDrive-/App-/Teams-Voraussetzungen => ROI nur als Potenzial, nicht als sofortiger Nutzen

### 2. Nutzenmodell

- eingesparte Zeit pro Tag
- Adoptionsquote
- Ramp-up-Faktor in den ersten Monaten
- optionaler Sicherheitsabschlag für konservative Szenarien

### 3. Kostenmodell

- Lizenzkosten
- einmalige Enablement-Kosten
- laufende Enablement-/Change-Kosten optional

### 4. Empfehlung

- breiter Rollout
- Pilot für definierte Rollen
- Readiness zuerst schließen

## Bewertungsmodell

Empfohlene Kennzahlen:

- `Netto-ROI`
- `Payback in Monaten`
- `Break-Even-Minuten pro Tag`
- `lizenzierte, aber ungenutzte Potenzialquote`

## Benötigte Daten

### 1. `copilot_pricing.json`

- Listenpreis
- Kanal-/Marktvarianten optional

### 2. `copilot_readiness_rules.json`

- Basislizenz-Eignung
- Exchange-Online-Voraussetzung
- Teams-/OneDrive-/App-Voraussetzungen

### 3. `roi_assumptions.json`

- Standard-Arbeitstage
- Ramp-up-Kurven
- konservativer Sicherheitsabschlag

### 4. `persona_roi_presets.json`

- Management
- Vertrieb
- Projektleitung
- Backoffice
- IT / Security

## Verifizierte Weblinks / Quellenbasis

1. **License options for Microsoft 365 Copilot**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-licensing

2. **Microsoft 365 app and network requirements for Microsoft 365 Copilot**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-requirements

3. **Teams Phone licensing**  
	https://learn.microsoft.com/en-us/microsoftteams/teams-phone-licensing

4. **Microsoft Licensing News**  
	https://www.microsoft.com/en-us/licensing/news

## Tool-Flow

### Schritt 1 – Lizenz- und Readiness-Check

- Basislizenz
- Exchange Online
- Apps / OneDrive / Teams

### Schritt 2 – Nutzenannahmen

- Minuten pro Tag
- Stundenlohn
- Adoption / Ramp-up

### Schritt 3 – ROI auswerten

- konservativ / realistisch / optimistisch
- Pilot oder Vollausbau

## UI/UX nach `cms-m365lic`

- starker Hero mit Management-Nutzen
- große KPI-Karten für ROI, Break-Even und Jahresgewinn
- Chart direkt unter der Auswertung
- kurzer Erklärblock `So liest du das Ergebnis`
- zusätzlicher Readiness-Status vor dem ROI-Chart

## Ergebnislogik / Textbausteine

### `Starker Business Case`

- Break-Even schnell erreichbar
- Readiness weitgehend vorhanden
- Adoption realistisch

### `Pilot-Kandidat`

- ROI positiv, aber noch stark rollenabhängig
- einzelne Voraussetzungen oder Enablement-Lücken offen

### `Lizenz-/Readiness-Prüfung zuerst`

- Basisvoraussetzungen fehlen
- ROI wäre sonst nur Theorie-Folklore

## Benötigte Bausteine / Funktionen

- `validate_copilot_readiness()`
- `calculate_copilot_roi()`
- `calculate_copilot_break_even_minutes()`
- `calculate_copilot_payback_period()`
- `build_copilot_roi_chart()`
- `render_copilot_roi_page()`
- `export_copilot_roi_pdf()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if copilot_base_eligible === false => readiness_blocker`
- `if primary_exchange_online_mailbox === false => readiness_blocker`
- `if teams_meeting_usecase === true && transcription_disabled === true => show_limited_value_warning`
- `if adoption_rate < threshold => reduce_effective_time_savings`
- `if break_even_minutes <= planned_minutes_saved => positive_case`

## Admin / Pflege

- Pflege des Copilot-Preises
- Pflege der Standardannahmen
- Pflege von Rollenprofilen und Ramp-up-Kurven
- SEO-Texte und Conversion-CTAs

## SEO- und Content-Bausteine

### Fokus-Keywords

- `copilot roi rechner`
- `microsoft 365 copilot lohnt sich`
- `copilot break even`
- `copilot business case`
- `copilot pilot berechnen`

### Empfohlene FAQ-Blöcke

- Ab wie vielen Minuten Zeitgewinn lohnt sich Copilot?
- Welche Basislizenz brauche ich für Copilot?
- Warum ist Exchange Online für Copilot wichtig?
- Wann sollte ich mit einem Pilot statt Vollrollout starten?

## MVP

- Einzelrechnung mit Chart
- 3 Szenarien
- Readiness-Gate
- PDF-Export

## Phase 2

- Rollenprofile je Abteilung
- Vergleich `Copilot vs. kein Copilot` über 12 Monate
- E-Mail-Lead-Funnel vor PDF-Export
- Benchmarks aus echten Pilotprojekten

## Akzeptanzkriterien

- Tool trennt sauber zwischen wirtschaftlicher Berechnung und technischer / lizenzseitiger Readiness.
- Break-Even wird in Minuten pro Tag verständlich ausgewiesen.
- Pilot- und Vollrollout-Szenarien sind unterscheidbar.
- fehlende Voraussetzungen werden klar benannt.

## Offene Pflegepunkte

- Preis- und Basislizenzlisten regelmäßig gegen Microsoft Learn prüfen.
- ROI-Annahmen mit echten Pilotdaten verfeinern, sobald vorhanden.
- Copilot-Funktionsumfang entwickelt sich schnell; Textbausteine daher vierteljährlich reviewen.