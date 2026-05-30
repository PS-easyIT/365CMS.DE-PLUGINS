# Annual vs. Monthly Commitment Rechner

- **Priorität:** mittel bis hoch
- **Datenquelle:** Price-List-/Annahmen-Datei + Channel-Regeln + Nutzerfluktuationsmodell
- **Aufwand:** niedrig bis mittel
- **SEO-Potenzial:** ★★
- **Empfohlener Slug:** `/m365-jahresvertrag-vs-monatsvertrag`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Der Rechner soll die praktische Beschaffungsfrage lösen: „Wann lohnt sich jährliche Bindung, wann monatliche Flexibilität?“ Er darf dabei **nicht** mit pauschalen Fantasieaufschlägen arbeiten, wenn echte Preislisten vorhanden sind.

## Verifizierte Microsoft-Leitplanken

### New Commerce kennt unterschiedliche Terms und Billing Plans

- Viele SKUs unterstützen **monatliche** (`P1M`) und **jährliche** (`P1Y`) Terms.
- Für einige SKUs existieren zusätzlich **dreijährige** Modelle.
- Nicht alle Produkte unterstützen alle Terms; manche Produkte gibt es nicht als Monatsvariante.

### Billing Plan und Term sind nicht dasselbe

- Ein Jahres-Commitment kann mit **monatlicher** oder **jährlicher** Abrechnung laufen.
- Der Preis eines Jahres-Terms bleibt über die Laufzeit konstant; Preisänderungen wirken in der Regel erst bei Renewal / Conversion.

### Kündigungsfenster ist begrenzt

- New-Commerce-Subscriptions können nur innerhalb der **ersten sieben Tage** des Terms storniert werden.
- Danach ist Flexibilität primär eine Frage des gewählten Terms, nicht guter Laune im Einkauf.

### Preisunterschiede sind SKU-, Markt- und Kanal-abhängig

- Microsoft veröffentlicht Preislisten marktbezogen.
- Es gibt Preisunterschiede nach Markt, Segment, Currency und Billing Frequency.
- Ein generischer Aufschlag wie `+20 %` für monatlich darf deshalb **nur als Fallback-Annahme**, nicht als offizielle Standardlogik verwendet werden.

### EA und MCA sind eigene Betrachtungsebenen

- EA adressiert typischerweise größere Organisationen mit planbaren Zahlungen und mehrjährigem Rahmen.
- MCA ist digital, vereinfacht die Kaufabwicklung und läuft nicht klassisch ab wie ein einzelner Subscription-Term.
- Der Rechner sollte daher CSP-/NCE-Vergleich und Vertragsrahmen-Hinweise trennen.

## Ziel des Tools

Vergleich zwischen jährlicher Bindung und monatlicher Flex-Variante inklusive Break-Even bei Fluktuation, Wachstum, Schrumpfung und Preisunterschieden aus echten Preislisten oder administrativ gepflegten Annahmen.

## Gewünschte Ergebnis-Kategorien

1. `✅ Annual Commitment klar wirtschaftlicher`
2. `🟡 Monthly Flex lohnt sich wegen Volatilität`
3. `🟠 Annual term mit monatlicher Abrechnung empfohlen`
4. `🔵 Split-Strategie empfohlen`
5. `🔴 Vertrags-/Channel-Prüfung nötig`

## Kernfunktionen

- Kostenvergleich für `monthly term`, `annual term monthly billed`, `annual term annual billed`
- Fluktuations- und Wachstums-Slider
- Break-Even-Anzeige
- Sensitivitätsrechnung für stabile, wachsende oder schrumpfende Teams
- Hinweisblock zu Kündigungsfenster, Renewal und Preisänderungen
- optionaler EA-/MCA-Kontextblock für Enterprise-Kunden

## Eingaben

- Produkt / SKU
- Markt / Währung / Channel optional
- Anzahl Nutzer Startwert
- erwartete monatliche Fluktuation
- erwartetes Wachstum oder Abbau
- Vertragszeitraum
- Preisdatenquelle: `echte Preisdatei` oder `Fallback-Annahme`

## Ausgaben

- Gesamtkosten je Modell
- Break-Even-Punkt
- Kostendifferenz bei stabiler vs. volatiler Belegschaft
- Opportunitätskosten ungenutzter Lizenzen
- Kurzempfehlung inkl. Risiko-/Flex-Hinweis

## Entscheidungslogik

### 1. Preisbasis bestimmen

- wenn Price-List-Daten vorhanden: diese verwenden
- sonst Fallback-Werte aus `assumptions.json`

### 2. Nutzerentwicklung modellieren

- konstantes Team
- Wachstum
- Schrumpfung
- hohe Fluktuation

### 3. Kosten je Term berechnen

- Monats-Term direkt pro Monat
- Jahres-Term mit monthly billing in 12 gleichen Raten
- Jahres-Term mit annual billing als gebundene Gesamtsumme

### 4. Break-Even ermitteln

- ab welcher Nutzerstabilität / Fluktuation kippt das Ergebnis?
- welcher Mix aus fix gebundenen und flexiblen Lizenzen wäre sinnvoll?

## Benötigte Daten

### 1. `commitment_pricing.json`

- SKU
- Markt
- Currency
- `P1M`-Preis
- `P1Y`-Preis
- Billing-Optionen

### 2. `assumptions.json`

- Fallback-Aufschläge
- Beispiel-Fluktuationen
- Default-Wachstumsraten

### 3. `channel_notes.json`

- CSP-/NCE-Hinweise
- EA-/MCA-Hinweise
- Kündigungsfenster

## Verifizierte Weblinks / Quellenbasis

1. **New commerce experience for license-based services**  
	https://learn.microsoft.com/en-us/partner-center/customers/new-commerce-license-based

2. **Pricing and offers**  
	https://learn.microsoft.com/en-us/partner-center/pricing/pricing-and-offers

3. **Microsoft-Kundenvereinbarung**  
	https://www.microsoft.com/licensing/how-to-buy/microsoft-customer-agreement

4. **Microsoft Customer Agreement documentation**  
	https://aka.ms/DocsMCA

5. **Enterprise Agreement**  
	https://www.microsoft.com/licensing/licensing-programs/enterprise

6. **Microsoft Licensing News**  
	https://www.microsoft.com/en-us/licensing/news

## Tool-Flow

### Schritt 1 – Produkt und Preisbasis wählen

- SKU / Markt / Channel
- echte Preisdatei oder Fallback

### Schritt 2 – Teamdynamik erfassen

- Nutzerzahl
- Fluktuation
- Wachstum / Abbau

### Schritt 3 – Modelle vergleichen

- annual vs. monthly
- Break-Even
- Split-Strategie falls sinnvoll

## UI/UX nach `cms-m365lic`

- kompakter Hero
- Slider + Eingabe-Card
- KPI-Karten für günstigstes Modell, Flexibilität und Break-Even
- Chart für 12 Monate und optional 36 Monate
- Hinweis-Card für `Kündigung nur in 7 Tagen`

## Ergebnislogik / Textbausteine

### `Annual lohnt sich`

- Nutzerzahl stabil
- Preisvorteil klar
- Flexibilität aktuell zweitrangig

### `Monthly lohnt sich`

- hohe Fluktuation oder unsichere Projektlage
- gebundene Lizenzen würden regelmäßig ungenutzt bleiben

### `Split-Strategie`

- stabiler Kernbestand annual
- volatile Randgruppe monthly

## Benötigte Bausteine / Funktionen

- `load_commitment_price_data()`
- `calculate_commitment_models()`
- `calculate_commitment_break_even()`
- `build_commitment_chart_series()`
- `build_split_commitment_recommendation()`
- `render_commitment_comparison_page()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if price_source === 'price_list' => ignore_generic_uplift_assumption`
- `if cancellation_window_expired === true => show_lock_in_notice`
- `if team_volatility > threshold => boost_monthly_flex_score`
- `if stable_core_users > threshold && volatile_users_present === true => suggest_split_strategy`
- `if sku_monthly_term_not_available === true => hide_monthly_option`

## Admin / Pflege

- Pflege echter Preislisten oder Fallback-Aufschläge
- Pflege von Channel-/Term-Erklärtexten
- Pflege von Standardbeispielen je Kundengröße
- monatlicher Review der Preislisten / Lizenz-News

## SEO- und Content-Bausteine

### Fokus-Keywords

- `m365 jährlich oder monatlich`
- `new commerce annual vs monthly`
- `microsoft 365 laufzeitrechner`
- `m365 commitment rechner`

### Empfohlene FAQ-Blöcke

- Was ist der Unterschied zwischen Term und Billing Plan?
- Wann lohnt sich ein Monatsmodell wirklich?
- Kann ich eine Jahreslizenz jederzeit kündigen?
- Wie wirken Preisänderungen im laufenden Term?

## MVP

- einzelne SKU berechnen
- 12-Monats-Vergleich
- Break-Even-Anzeige
- Split-Strategie-Hinweis

## Phase 2

- Mehrjahresvergleich
- CSV-/PDF-Export
- Integration in Lizenzberater und Preis-Tracker

## Akzeptanzkriterien

- Tool verwendet echte Price-List-Daten, wenn vorhanden.
- Generische Aufschläge werden klar als Annahmen markiert.
- Kündigungsfenster und Renewal-Logik werden sichtbar erklärt.
- Ergebnis kann stabile Kernbestände von flexiblen Randbeständen trennen.

## Offene Pflegepunkte

- pro SKU prüfen, ob Monats- und Jahres-Term tatsächlich verfügbar sind.
- Markt- und Währungsunterschiede nicht pauschalieren.
- EA-/MCA-/CSP-Logik in Textbausteinen sauber voneinander abgrenzen.