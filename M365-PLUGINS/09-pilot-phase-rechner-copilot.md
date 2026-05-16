# Pilot-Phase-Rechner für Copilot

- **Priorität:** mittel bis hoch
- **Datenquelle:** Adoptions-/Enablement-Regeln + Pilot-Defaults + Preis-/Readiness-Daten
- **Aufwand:** niedrig bis mittel
- **SEO-Potenzial:** ★★
- **Empfohlener Slug:** `/copilot-pilot-rechner`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Das Tool soll zeigen, welche Pilotgröße für Microsoft 365 Copilot sinnvoll ist, was 5, 20, 50 oder 100 User kosten und wie hoch der zu erwartende Lern-, Governance- und Rollout-Nutzen ist.

Es soll außerdem beantworten:

1. Ist der Tenant grundsätzlich pilotbereit?
2. Wie viele Personen müssen mindestens teilnehmen, damit der Pilot repräsentativ ist?
3. Wie viel Oversharing-/Governance-Arbeit sollte vor dem Pilot erledigt werden?
4. Wann reicht ein Mini-Pilot und wann braucht es einen funktionsübergreifenden Pilot?

## Verifizierte Microsoft-Leitplanken

### Microsoft empfiehlt eine Readiness-Prüfung vor dem Rollout

- Microsoft empfiehlt, vor dem Copilot-Deployment eine Readiness-/Optimization-Betrachtung durchzuführen.
- Adoption-Ressourcen, Success Kits und Szenariobibliotheken stehen offiziell bereit.

### Copilot-Pilot ist mehr als nur Lizenzen zuweisen

- Microsoft nennt als Pfad: Organisation vorbereiten, Lizenz wählen, Apps/Netzwerk bereit machen, Copilot einrichten, Nutzer begrüßen und Feedback aktivieren.

### Datenhygiene und Oversharing sind Pilot-relevant

- Vor oder parallel zum Pilot sollten Oversharing-Risiken, Berechtigungen und Guardrails adressiert werden.
- Microsoft verweist hierzu auf SharePoint Advanced Management und Purview-Schutzmaßnahmen.

### Governance beeinflusst Pilotgröße

- Ein Pilot ohne Governance und Success-Kriterien liefert schnell nur „coole Demos“, aber keine belastbaren Entscheidungen.

## Ziel des Tools

Empfiehlt je Unternehmensgröße und Readiness-Grad eine Pilotgröße, Pilotdauer, Champion-Anzahl, Budgetrahmen und eine grobe Rollout-Roadmap.

## Gewünschte Ergebnis-Kategorien

1. `✅ Micro-Pilot ausreichend`
2. `🟡 Repräsentativer Abteilungs-Pilot empfohlen`
3. `🔵 Funktionsübergreifender Pilot empfohlen`
4. `🟠 Governance / Datenhygiene zuerst`
5. `🔴 Noch nicht pilotreif`

## Kernfunktionen

- Pilotgrößen-Vorschläge in Stufen
- Kostenberechnung pro Stufe
- Lern-/Feedback-Wert pro Pilotstufe
- grober Rollout-Zeitplan
- Empfehlung nach Unternehmensgröße und Readiness
- Governance-Checkliste vor Pilotstart

## Eingaben

### Organisationsdaten

- Gesamtanzahl Mitarbeitende
- Knowledge-Worker-Anteil
- betroffene Abteilungen
- Budgetrahmen

### Pilotziele

- Produktivität messen
- Use Cases identifizieren
- Governance prüfen
- Management-Buy-in erzeugen
- ROI validieren

### Readiness-Daten

- Lizenzbasis vorhanden ja/nein
- Apps / Netzwerk bereit ja/nein
- Datenhygiene / Oversharing-Risiko niedrig / mittel / hoch
- Schulungs- / Champion-Setup vorhanden ja/nein

## Ausgaben

- empfohlene Pilotgröße
- Kosten je Pilotstufe
- Rollout-Fahrplan für 4 bis 12 Wochen
- Hinweise zu Champion-Usern, Feedback und Success-Kriterien
- Readiness-Blocker, falls vorhanden

## Entscheidungslogik

### 1. Pilotreife prüfen

- Lizenzbasis vorhanden?
- Apps/Netzwerk vorbereitet?
- Oversharing / Governance vertretbar?

### 2. Pilotgröße bestimmen

- KMU: eher kompakte, aber repräsentative Gruppen
- Midmarket: funktionsübergreifender Pilot
- Enterprise: mindestens mehrere Rollen / Bereiche

### 3. Nutzen vs. Aufwand bewerten

- zu kleiner Pilot = wenig belastbare Ergebnisse
- zu großer Pilot = hoher Aufwand vor klarer Governance

## Benötigte Daten

### 1. `copilot_pilot_sizes.json`

- Stufen `5`, `20`, `50`, `100`, `250+`
- Einsatzszenarien
- Minimalvoraussetzungen

### 2. `copilot_rollout_templates.json`

- 4-, 6-, 8-, 12-Wochen-Templates
- Champion- und Feedback-Phasen

### 3. `copilot_readiness_checklist.json`

- Lizenz
- Apps / Netzwerk
- Governance
- Datenhygiene

## Verifizierte Weblinks / Quellenbasis

1. **Microsoft 365 Copilot adoption guide and overview for IT admins**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-enablement-resources

2. **Configure a secure and governed foundation for Microsoft 365 Copilot**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/configure-secure-governed-data-foundation-microsoft-365-copilot

3. **License options for Microsoft 365 Copilot**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-licensing

4. **Microsoft 365 app and network requirements for Microsoft 365 Copilot**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-requirements

## Tool-Flow

### Schritt 1 – Pilotziel und Reifegrad erfassen

- Unternehmensgröße
- Pilotziel
- Lizenz-/Governance-Status

### Schritt 2 – Pilotgröße rechnen

- Stufenvergleich
- Champion-Bedarf
- Kosten

### Schritt 3 – Rollout-Plan ableiten

- Pilotdauer
- Feedback-Schleifen
- Go/No-Go-Kriterien

## UI/UX nach `cms-m365lic`

- KPI-Karten für Pilotgröße, Kosten, Dauer und Reifegrad
- Vergleichskarten je Stufe
- Zeitleiste für Pilot- und Rollout-Plan
- Governance-Hinweisbox für Oversharing / Guardrails

## Ergebnislogik / Textbausteine

### `Micro-Pilot`

- kleines Team
- klares Zielbild
- niedrige Komplexität

### `Abteilungs-Pilot`

- mehrere Rollen in einem Bereich
- ausgewogene Testgruppe

### `Governance zuerst`

- Datenzugriffe / Oversharing / Schutzmechanismen noch zu unreif

## Benötigte Bausteine / Funktionen

- `calculate_copilot_pilot_sizes()`
- `score_copilot_pilot_recommendation()`
- `build_copilot_rollout_timeline()`
- `score_copilot_readiness()`
- `render_copilot_pilot_page()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if readiness_score < threshold => governance_first`
- `if org_size_small && goals_limited === true => micro_pilot`
- `if departments > 1 => cross_functional_pilot`
- `if champion_ratio_too_low === true => warning`
- `if budget_low && representativeness_high_needed === true => optimize_for_role_mix_not_size`

## Admin / Pflege

- Pflege der Pilot-Defaults
- Pflege von Zeitplan-Texten und Tipps
- Pflege der Readiness-Checkliste

## SEO- und Content-Bausteine

### Fokus-Keywords

- `copilot pilot rechner`
- `microsoft 365 copilot pilot`
- `copilot rollout planen`
- `copilot pilotgröße`

### Empfohlene FAQ-Blöcke

- Wie viele Nutzer sollte ein Copilot-Pilot haben?
- Was muss vor dem Pilot vorbereitet werden?
- Welche Success-Kriterien sollte ein Copilot-Pilot haben?
- Wann ist ein Tenant noch nicht pilotreif?

## MVP

- feste Pilotgrößen
- Kostenvergleich
- Standard-Rollout-Timeline
- einfacher Readiness-Score

## Phase 2

- abteilungsbezogene Piloten
- Export als Pilot-Steckbrief
- KPI-Vorlagen für Adoption-Messung
- Success-Metrics pro Persona

## Akzeptanzkriterien

- Tool berücksichtigt Readiness und Governance, nicht nur Lizenzkosten.
- Pilotgröße wird nachvollziehbar aus Ziel und Unternehmensgröße abgeleitet.
- Ergebnis enthält Champion-/Feedback- und Timeline-Hinweise.
- Oversharing-/Guardrail-Themen werden sichtbar adressiert.

## Offene Pflegepunkte

- Pilot-Defaults mit echten Projektwerten schärfen, sobald Erfahrungsdaten vorliegen.
- Readiness-Fragen mit künftigen Microsoft-Assessment-Feldern harmonisieren.
- Adoption-Ressourcen im Quartalsreview gegen neue Microsoft-Kits prüfen.