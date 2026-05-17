# Lizenz-Vergleichstabelle

- **Priorität:** hoch
- **Datenquelle:** `plans.json` + `feature_matrix.json` + offizielle Microsoft-Referenzen
- **Aufwand:** niedrig bis mittel
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/m365-lizenzvergleich`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Die Vergleichstabelle ist die „große Übersicht“ für Besucher, die sich zuerst selbst orientieren wollen. Sie soll nicht nur Preise nebeneinanderstellen, sondern strukturiert zeigen:

1. Welche Lizenzfamilien es überhaupt gibt
2. welche Funktionen enthalten, add-on-pflichtig oder nicht verfügbar sind
3. welche Pläne Copilot-, Frontline-, Security- oder Teams-Phone-tauglich sind
4. wann ein Plan zwar günstig aussieht, aber durch Add-ons teurer wird

## Verifizierte Microsoft-Leitplanken

### Feature-Status muss sauber unterschieden werden

- Für viele Microsoft-365-Themen reicht kein simples `ja/nein`.
- Das Tool sollte mindestens zwischen `enthalten`, `teilweise`, `per Add-on`, `mit Voraussetzung`, `nicht enthalten` unterscheiden.

### Copilot-Readiness ist kein bloßes Icon

- Copilot setzt eine berechtigte Basislizenz voraus.
- Zusätzlich sind App-, OneDrive-, Entra- und Exchange-Online-Voraussetzungen relevant.
- Ein Plan darf also nur dann als `Copilot-ready` markiert werden, wenn die Basislizenz grundsätzlich geeignet ist.

### Teams Phone braucht Prerequisites

- Teams Phone ist in vielen Szenarien kein „einfach enthaltenes Häkchen“, sondern ein Pfad aus Teams + Phone System + ggf. PSTN-Modell.
- Die Tabelle muss daher zwischen `telefoniefähig per Add-on` und `vollständig enthalten` unterscheiden.

### Power Platform seeded vs. Premium

- In Microsoft 365 enthaltene Power-Platform-Rechte decken nicht automatisch Premium-Connectoren, Custom Connectoren und On-Prem-Gateways ab.
- In der Tabelle sollte deshalb ein Status wie `Basisrechte` vs. `Premium separat` vorgesehen werden.

### Small-Business-Szenarien haben Channel-Grenzen

- Im CSP-/Offer-Matrix-Kontext gibt es für viele Small-Business-SKUs 300er Maximalgrenzen auf SKU-Ebene.
- Diese Information gehört als Hinweis an Business-Pläne, nicht als stillschweigende Fußnote.

## Ziel des Tools

Eine interaktive, filterbare und teilbare Vergleichsansicht für alle relevanten Microsoft-365-Basispläne und ausgewählte Spezialpfade.

## Gewünschte Ergebnis-Kategorien

Die Tabelle sollte Highlights und Schnelllabels liefern, etwa:

1. `💸 günstigster Einstieg`
2. `🛡 stärkste Security-/Compliance-Abdeckung`
3. `🤖 Copilot-fähige Basis`
4. `👷 Frontline-geeignet`
5. `📞 Teams-Phone-fähig per Add-on oder inklusive`

## Kernfunktionen

- Side-by-side-Vergleich aller relevanten Basispläne
- Filter nach Preis, Zielgruppe, Security, Compliance, KI, Frontline, Telefonie
- Spalten auswählen und anpinnen
- Unterschiede zwischen ausgewählten Plänen visuell hervorheben
- Umschalter zwischen Monats- und Jahresansicht
- mobile Kartenansicht als Fallback
- Deep-Link auf vorausgewählte Pläne
- direkte Absprünge in Lizenzberater und Add-on-Konfigurator

## Eingaben

- Plan-Auswahl oder Vorauswahl aus URL
- Feature-Filter
- Preisbereich
- Zielgruppe / Unternehmensgröße
- Kanal-/Segment-Hinweise optional einblendbar

## Ausgaben

- sortierbare Vergleichstabelle
- Statuskennzeichnung je Feature
- Preis pro User / Monat und optional pro Jahr
- Marker für `Add-on nötig`, `Voraussetzung nötig`, `Segment prüfen`
- direkte Links zu Detailtools

## Entscheidungslogik

### Statusmodell pro Feature

- `enthalten`
- `teilweise / eingeschränkt`
- `Add-on nötig`
- `Voraussetzung nötig`
- `nicht enthalten`

### Highlighting-Logik

- niedrigster Listenpreis
- höchste Feature-Abdeckung im aktuell aktiven Filterset
- beste Passung für KMU / Enterprise / Frontline
- Warnflag bei versteckter Komplexität durch Add-ons

### Tabellengruppen

- Produktivität
- Security
- Compliance
- KI / Copilot
- Telefonie
- Power Platform
- Storage / Files / SharePoint

## Benötigte Daten

### 1. `plans.json`

- Produktname
- Lizenzfamilie
- Preis
- Zielgruppe
- Channel-/Segment-Hinweise

### 2. `feature_matrix.json`

- Feature-ID
- Anzeigegruppe
- Status pro Plan
- Tooltip
- Quellenstand

### 3. `comparison_badges.json`

- Regeln für `günstigster`, `Copilot-Basis`, `Frontline`, `beste Security`

### 4. `plan_notes.json`

- 300er-Grenze
- Add-on-Hinweise
- NCE-/Kanalhinweise
- Copilot-/Teams-Phone-Voraussetzungen

## Verifizierte Weblinks / Quellenbasis

1. **License options for Microsoft 365 Copilot**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-licensing

2. **Microsoft 365 app and network requirements for Microsoft 365 Copilot**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-requirements

3. **Teams Phone licensing**  
	https://learn.microsoft.com/en-us/microsoftteams/teams-phone-licensing

4. **Power Platform licensing FAQs**  
	https://learn.microsoft.com/en-us/power-platform/admin/powerapps-flow-licensing-faq

5. **Licensing overview for Microsoft Power Platform**  
	https://learn.microsoft.com/en-us/power-platform/admin/pricing-billing-skus

6. **SharePoint limits**  
	https://learn.microsoft.com/en-us/office365/servicedescriptions/sharepoint-online-service-description/sharepoint-online-limits

7. **Pricing and offers**  
	https://learn.microsoft.com/en-us/partner-center/pricing/pricing-and-offers

8. **Microsoft 365 Business Plans**  
	https://aka.ms/M365BusinessPlans

9. **Microsoft 365 Enterprise Plans**  
	https://aka.ms/M365EnterprisePlans

10. **Microsoft Licensing News**  
	 https://www.microsoft.com/en-us/licensing/news

## Tool-Flow

### Schritt 1 – Planfamilie wählen

- Business
- Enterprise
- Frontline
- alle anzeigen

### Schritt 2 – Feature-Filter setzen

- nur Copilot-relevante Pläne
- nur Security-starke Pläne
- nur Frontline / Telefonie / Power Platform

### Schritt 3 – Unterschiede auswerten

- Auswahl von 2 bis 4 Plänen
- automatische Highlighting-Karten
- Deep-Link in Beratungs- oder Spezialtool

## UI/UX nach `cms-m365lic`

- gleicher Hero- und Pill-Stil wie `cms-m365lic`
- Filterpanel als Card oberhalb der Tabelle
- Tabellenkopf im dunklen Theme-Stil
- Sticky-Spalten für Planname und Preis
- Ergebnis-Karten für `günstigster`, `vollständigster`, `beste Wahl für KMU`
- mobile Kartenansicht mit Akkordeons statt horizontalem Scroll-Horror

## Ergebnislogik / Textbausteine

### `Günstiger Einstieg`

- niedriger Listenpreis
- Basisfunktionen ausreichend
- keine starke Spezialanforderung aktiv

### `Starker Security-Pfad`

- hohe Abdeckung in Security-/Compliance-Filtern
- wenig Zusatzkomplexität

### `Achtung Add-on-Falle`

- Plan wirkt günstig
- verliert aber nach Add-ons den Kostenvorteil

## Benötigte Bausteine / Funktionen

- `load_plan_comparison_matrix()`
- `filter_plan_comparison()`
- `sort_plan_comparison()`
- `highlight_plan_differences()`
- `build_plan_badges()`
- `render_plan_comparison_page()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if feature_status === 'addon' => show_addon_badge_and_link`
- `if copilot_base_eligible === true => show_copilot_ready_badge`
- `if teams_phone_requires_prerequisite === true => show_prerequisite_hint`
- `if business_sku_limit_relevant === true => show_300_user_note`
- `if premium_connector_not_included === true => show_power_platform_warning`

## Admin / Pflege

- Pflege der Feature-Matrix
- Pflege kurzer Tooltips je Feature
- Preis-Updates quartalsweise oder per Price-List-Import
- Pflege von Highlight-Regeln und Badges
- Pflege von Fußnoten für Kanal- und Segmentunterschiede

## SEO- und Content-Bausteine

### Fokus-Keywords

- `m365 lizenzvergleich`
- `microsoft 365 business standard vs premium`
- `m365 e3 vs e5 vergleich`
- `microsoft 365 copilot basislizenz`
- `teams phone voraussetzungen`

### Empfohlene FAQ-Blöcke

- Welche Microsoft-365-Lizenzen kann ich direkt vergleichen?
- Was bedeutet „Add-on nötig“ in der Tabelle?
- Welche Pläne sind Copilot-fähig?
- Woran erkenne ich versteckte Zusatzkosten?

## MVP

- alle relevanten Basispläne
- Filter + Sortierung
- Statusmodell pro Feature
- Highlighting für ausgewählte Pläne

## Phase 2

- CSV-/PDF-Export
- Share-Link mit vorausgewählten Plänen
- Add-on-Einblendung pro Zeile
- Partner-Preisprofil statt Listenpreis

## Akzeptanzkriterien

- Tabelle unterscheidet sauber zwischen `enthalten`, `Add-on`, `Voraussetzung` und `nicht enthalten`.
- Copilot-, Telefonie- und Premium-Connector-Hinweise werden korrekt ausgewiesen.
- Business-/Enterprise-/Frontline-Kontexte sind filterbar.
- Die Tabelle bleibt auf Mobilgeräten nutzbar.

## Offene Pflegepunkte

- offizielle Microsoft-Vergleichsseiten per `aka.ms` regelmäßig manuell prüfen.
- Feature-Tiefe pro Plan nicht überfrachten; ggf. Clusterung statt Volltextwand.
- Channel- und Segmentunterschiede als Hinweise, nicht als globale Wahrheit darstellen.