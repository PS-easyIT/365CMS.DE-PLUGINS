# CSP vs. EA vs. MCA Vergleich

- **Priorität:** hoch
- **Datenquelle:** Microsoft-Vertrags-/Channel-Regeln + NCE/CSP-Merkmale + qualitative Bewertungsmatrix
- **Aufwand:** mittel
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/csp-vs-ea-vs-mca`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Das Tool vergleicht die Vertrags- und Einkaufskanäle `CSP/NCE`, `EA` und `MCA` nach Kosten, Flexibilität, Zahlungsmodell und operativem Nutzen – und erklärt dabei sauber, dass diese Modelle nicht immer exakt auf derselben Ebene liegen.

## Verifizierte Microsoft-Leitplanken

### CSP/NCE ist heute stark durch New Commerce geprägt

- New Commerce für license-based services bietet monatliche, jährliche oder teilweise dreijährige Laufzeiten.
- Für viele Angebote gibt es monatliche, jährliche oder Upfront-Abrechnung.
- Add-ons sind flexibler geworden und müssen nicht mehr zwangsläufig an denselben Basistransaktionspfad gebunden sein.
- Kündigungen bzw. Reduzierungen sind im New-Commerce-Modell nur in den ersten **sieben Tagen** der Laufzeit möglich.

### MCA ist das digitale Vertragsmodell mit vereinfachter Kaufabwicklung

- Die Microsoft Customer Agreement ist eine digitale Vereinbarung.
- Sie ist kurz, einfacher aufgebaut und **läuft nicht ab**.
- Sie wird automatisch aktualisiert, wenn Produkte hinzugefügt werden.

### EA bleibt der große Enterprise-Pfad

- Das Enterprise Agreement richtet sich an Organisationen mit **500 oder mehr Nutzern oder Geräten**.
- Microsoft nennt als Vorteile bessere Preise/Nachlässe, Preisfixierung und Zahlungsverteilung über **drei Jahre**.
- EA adressiert Cloud- und On-Premises-Szenarien unter einem Vertrag.

### Kanal, Vertragsvehikel und Betreuungsmodell sind nicht identisch

- In der Praxis wird `CSP vs. MCA vs. EA` oft als Kanalvergleich verstanden.
- Technisch ist `MCA` eher ein Vertragsvehikel, während `CSP/NCE` häufig das partnergeführte Einkaufs- und Betriebsmodell darstellt.
- Das Tool muss diese Ebenen verständlich entflechten, statt Äpfel mit Birnen und Birnen mit Einkaufswagen zu vergleichen.

## Ziel des Tools

Vergleich der Vertrags- und Einkaufskanäle `CSP`, `EA` und `MCA` nach Kosten, Flexibilität, Zahlungsmodell und operativem Nutzen.

## Gewünschte Ergebnis-Kategorien

1. `✅ CSP/NCE empfohlen`
2. `🟡 MCA Direct sinnvoll`
3. `🔵 EA empfohlen`
4. `🟠 Misch- oder Sondermodell prüfen`
5. `🔴 Vergleich nur qualitativ möglich`

## Kernfunktionen

- Kanalvergleich nach Seat-Anzahl und Vertragslogik
- Schwellenwert-Betrachtung
- Cashflow-Sicht auf monatlich vs. jährlich
- Bewertung von Support, Beratung und Flexibilität
- Empfehlung nach Unternehmensgröße und Beschaffungsmodell

## Eingaben

### Organisationsdaten

- Anzahl Nutzer / Seats
- geplantes Wachstum
- Stabilität der Nutzerzahlen

### Präferenzen

- Flexibilität
- Preis / Rabatt
- Support / Beratung
- Cashflow / monatlich vs. gebündelt
- Wunsch nach Partnerbegleitung

### Governance / Beschaffung

- zentrale Beschaffung ja/nein
- Wunsch nach direkter Microsoft-Beziehung ja/nein
- On-Prem- / Hybrid-Bedarf ja/nein

## Ausgaben

- empfohlener Bezugskanal
- Kosten- und Cashflow-Vergleich
- Vor- und Nachteile je Modell
- Schwellenwert, ab wann ein Modell sinnvoller wird
- Management-Kurzfazit in Klartext

## Entscheidungslogik

### 1. Größen- und Stabilitätsprüfung

- < 500 Seats => EA oft kein Standardpfad
- stabile große Bestände => EA attraktiver
- volatile Nutzerzahlen => CSP/NCE attraktiver

### 2. Vertragslogik bewerten

- digital, direkt, nicht ablaufend => MCA
- partnergeführt, flexibel, operativ nah => CSP/NCE
- enterpriseweit, 3-Jahres-Logik => EA

### 3. Cashflow und Betriebsnutzen trennen

- monatliche Flexibilität
- Preisfixierung
- Support- / Beratungsmodell

## Bewertungsmodell

- `Flexibilität`
- `Cashflow`
- `Preisvorteil / Rabattpotenzial`
- `Betreuungsmodell`
- `Enterprise-Fit`

## Benötigte Daten

### 1. `channel_rules.json`

- Seat-Schwellen
- Laufzeitmodelle
- Kündigungslogiken

### 2. `channel_pricing_profiles.json`

- Preisprofile / Spannen
- Cashflow-Profile
- Rabatt-Unsicherheiten

### 3. `channel_qualitative_matrix.json`

- Supportmodell
- Partnerwert
- Governance-/Procurement-Fit

## Verifizierte Weblinks / Quellenbasis

1. **New commerce experience for license-based services**  
	https://learn.microsoft.com/en-us/partner-center/customers/new-commerce-license-based

2. **Microsoft Customer Agreement**  
	https://www.microsoft.com/licensing/how-to-buy/microsoft-customer-agreement

3. **Enterprise Agreement**  
	https://www.microsoft.com/licensing/licensing-programs/enterprise

4. **Microsoft Licensing News**  
	https://www.microsoft.com/en-us/licensing/news

## Tool-Flow

### Schritt 1 – Einkaufsrealität erfassen

- Seats
- Wachstum
- Procurement-Präferenzen

### Schritt 2 – Modelle vergleichen

- CSP/NCE
- MCA Direct
- EA

### Schritt 3 – Empfehlung begründen

- Cashflow
- Flexibilität
- Support
- Enterprise-Fit

## UI/UX nach `cms-m365lic`

- Vergleichs-KPI-Karten je Modell
- gewichtete Bewertungsmatrix
- Ergebnis-Fazit in Management-Sprache
- Zusatzhinweis `Vertragsmodell ≠ Betreuungsmodell`

## Ergebnislogik / Textbausteine

### `CSP/NCE empfohlen`

- Flexibilität und Partnernähe wichtig
- Nutzerzahlen ändern sich häufiger

### `MCA sinnvoll`

- direkte, digitale Vertragslogik gewünscht
- Vereinfachung und Kontinuität stehen im Fokus

### `EA empfohlen`

- große stabile Organisation
- 3-Jahres-Planung und Preisfixierung relevant

## Benötigte Bausteine / Funktionen

- `compare_license_channels()`
- `score_license_channel_fit()`
- `build_channel_cashflow_view()`
- `explain_channel_model_differences()`
- `render_channel_comparison_page()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if seat_count >= 500 && demand_stable === true => increase_ea_score`
- `if flexibility_priority === high => increase_csp_score`
- `if wants_digital_direct_agreement === true => increase_mca_score`
- `if cancelability_is_critical === true => explain_nce_7_day_rule`
- `if onprem_and_cloud_under_one_agreement_needed === true => boost_ea_score`

## Admin / Pflege

- Pflege aktueller Vertragslogiken
- Pflege qualitativer Bewertungsregeln
- Quellen- und Disclaimer-Management
- regelmäßiger Review der NCE-Regeln

## SEO- und Content-Bausteine

### Fokus-Keywords

- `csp vs ea vs mca`
- `microsoft customer agreement oder enterprise agreement`
- `csp new commerce vergleich`
- `welcher microsoft bezugskanal`

### Empfohlene FAQ-Blöcke

- Was ist der Unterschied zwischen CSP, MCA und EA?
- Ab wann lohnt sich ein Enterprise Agreement?
- Wie flexibel ist New Commerce wirklich?
- Warum ist MCA kein 1:1-Ersatz für einen Kanalvergleich?

## MVP

- CSP/NCE, EA, MCA mit Standardregeln
- Ergebnisempfehlung + Cashflow-Vergleich
- Klarstellung der Modellunterschiede

## Phase 2

- Branchen- und Größenprofile
- Export als Entscheidungsvorlage
- Einbindung in Lizenzberater
- regionale Beschaffungsprofile

## Akzeptanzkriterien

- Tool erklärt den Strukturunterschied zwischen Kanal, Vertrag und Betreuung.
- NCE-Flexibilität und 7-Tage-Kündigungsregel werden korrekt abgebildet.
- EA-Schwelle und 3-Jahres-Logik sind sichtbar.
- Ergebnis ist verständlich für Management und Einkauf.

## Offene Pflegepunkte

- Preis- und Rabattlogiken kanalneutral modellieren.
- Sonderfälle je Land / Segment beobachten.
- MCA/CSP-Begriffe im UI besonders sauber formulieren, um Missverständnisse zu vermeiden.