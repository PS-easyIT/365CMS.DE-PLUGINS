# M365-Lizenz-Berater – Killer-Tool

- **Priorität:** sehr hoch
- **Datenquelle:** zentrale Plan-/Add-on-Dateien + Regeldateien + offizielle Microsoft-Referenzen
- **Aufwand:** mittel bis hoch
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/m365-lizenzberater`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Der `M365-Lizenz-Berater` ist das zentrale Einstiegs-Tool der gesamten Toolbox. Er soll nicht nur „einen Plan nennen“, sondern strukturiert beantworten:

1. Welche Lizenzfamilie passt pro Nutzergruppe fachlich am besten?
2. Reicht eine Basislizenz aus oder sind Add-ons nötig?
3. Gibt es harte Ausschlussregeln für bestimmte Wünsche wie Copilot, Teams Phone oder Premium-Connectoren?
4. Ist eine gemischte Lizenzstrategie wirtschaftlicher als ein einheitliches Rollout?
5. Welche 1 bis 3 Alternativen sollte ein Kunde trotzdem sehen?

Das Tool ist damit gleichzeitig:

- Erstberater für Neukunden
- Vorqualifizierer für Angebotsanfragen
- Lead-Magnet für Audit und Lizenzbereinigung
- Hub für Deep-Links in Spezialtools wie Copilot, Add-ons, Backup, Teams Phone und Commitment-Rechner

## Verifizierte Microsoft-Leitplanken

Die folgenden Regeln sollten nicht „frei erfunden“, sondern als harte oder halbharte Produktlogik im Tool abgebildet werden.

### Copilot ist kein freischwebendes Add-on

- Microsoft 365 Copilot ist ein Add-on und setzt eine **berechtigte Basislizenz** voraus.
- Nutzer benötigen ein **Microsoft Entra ID-Konto**.
- Microsoft 365 Copilot wird nur für **primäre Postfächer auf Exchange Online** unterstützt.
- Bestimmte App-Erlebnisse benötigen zusätzlich OneDrive, Microsoft 365 Apps sowie passende Netzwerk-/Privacy-Voraussetzungen.

### Teams Phone braucht einen sauberen Lizenzpfad

- Benutzer mit eigener Rufnummer und eigener Telefonie benötigen einen Pfad, der **Teams + Phone System** abdeckt.
- Wenn Microsoft der PSTN-Anbieter ist, ist zusätzlich ein **Calling Plan** nötig.
- Für Voice-Anwendungen wie Auto Attendants und Call Queues gibt es **separate Resource-Account-Lizenzen**.

### Power Platform seeded rights sind begrenzt

- Die in Microsoft 365 enthaltenen Power-Apps-/Power-Automate-Rechte reichen **nicht** für alle Premium-Szenarien.
- Für **Premium-Connectoren**, **Custom Connectoren** und **On-Premises Data Gateways** ist in vielen Fällen eine eigene Power-Platform-Lizenz nötig.
- Dataverse-Funktionen in Microsoft-365-Lizenzen sind nur eingeschränkt und nicht mit einer vollwertigen Premium-Nutzung gleichzusetzen.

### SharePoint-/OneDrive-Kapazität ist tenantweit relevant

- SharePoint-Storage wird tenantweit berechnet und startet laut Servicebeschreibung mit **1 TB plus 10 GB pro qualifizierter Lizenz**.
- Wird der Tenant dauerhaft überzogen betrieben, droht ein **Read-only-Szenario**.
- Einzelne SharePoint-Sites können bis **25 TB** groß werden; das löst aber kein tenantweites Kapazitätsproblem.

### New Commerce / Vertragslogik muss mitgedacht werden

- Für viele SKUs existieren in New Commerce **monatliche**, **jährliche** und teilweise **dreijährige** Begriffe.
- Kündigungen in New Commerce sind nur innerhalb der **ersten sieben Tage** eines Terms möglich.
- Preisänderungen wirken in der Regel **erst bei Renewal, Upgrade oder Term Conversion**, nicht mitten in einer laufenden Bindung.

## Ziel des Tools

Das Tool soll für 1 bis n Nutzergruppen eine belastbare Microsoft-365-Empfehlung erzeugen, inklusive Basislizenz, nötiger Add-ons, Kostenrahmen, Alternativen und klarer Begründung, warum andere Wege ausscheiden.

## Gewünschte Ergebnis-Kategorien

Das Ergebnis sollte mindestens diese Zustände unterscheiden:

1. `✅ Basislizenz reicht aus`
2. `🟡 Basislizenz + Add-ons empfohlen`
3. `🟠 Mischmodell aus mehreren Lizenzfamilien empfohlen`
4. `🔵 Spezialtool / Detailprüfung nötig`
5. `🔴 Keine belastbare Auto-Empfehlung – Audit empfohlen`

## Kernfunktionen

- 3-Schritt-Wizard nach Vorbild von `cms-m365lic`
- mehrere Bedarfsgruppen in einer Anfrage
- Feature-Matching gegen Plan-, Add-on- und Prerequisite-Matrix
- harte Ausschlussregeln für nicht passende Lizenzpfade
- Kostenvergleich pro User, pro Monat, pro Jahr und optional pro 36 Monate
- Ergebnis mit Primär-Empfehlung, Alternativen und Begründung
- Deep-Links in Spezialtools: Copilot, Add-ons, Teams Phone, Commitment, Shared Mailbox, Backup
- PDF-Export und Beratungs-CTA
- optionale Member-/Partner-Variante mit individuellen EK/VK-Profilen

## Eingaben

### Unternehmens- und Kanal-Kontext

- Anzahl Mitarbeitende gesamt
- Anzahl Mitarbeitende je Nutzergruppe
- Kundentyp: KMU, Midmarket, Enterprise, Frontline-lastig
- Bezugslogik: CSP / Direkt / unbekannt
- gewünschte Vertragslogik: monatlich, jährlich, offen

### Nutzergruppen / Personas

- Knowledge Worker
- Frontline Worker
- Shared Device / Kiosk
- IT / Security
- Management
- Vertrieb / Außendienst
- Projekt / Power User

### Feature-Bedarf

- Exchange / Mailbox
- Teams / Meetings
- Office Desktop-Apps
- Geräteverwaltung / Intune
- Security / Defender
- Compliance / Purview
- Telefonie / Teams Phone
- Power Platform Premium
- Copilot
- Backup / Archiv / Storage-relevante Anforderungen

### Betriebsdetails

- Shared Computer / Terminalserver ja/nein
- mobiles Arbeiten wichtig ja/nein
- On-Prem-/Hybrid-Abhängigkeiten ja/nein
- Premium-Connectoren / individuelle Workflows ja/nein
- PSTN mit Microsoft oder Drittanbieter geplant

## Ausgaben

- empfohlene Basislizenz pro Nutzergruppe
- empfohlene Add-ons oder alternative Upgrade-Pfade
- Monats- und Jahreskosten je Gruppe und gesamt
- 1 bis 3 Alternativszenarien
- Begründung, warum Pläne passen oder ausscheiden
- Warnhinweise zu Copilot-Basislizenz, Teams Phone, Power Platform, Storage und Commitment
- CTA: `PDF exportieren`, `Lizenz-Audit anfragen`, `Angebot vorbereiten`

## Entscheidungslogik

### 1. Vorselektion nach Nutzerfamilie

- Frontline-nahe Szenarien werden zunächst gegen Frontline-/Shared-Device-Pfade geprüft.
- klassische Information-Worker-Szenarien werden gegen Business-/Enterprise-Pfade geprüft.
- stark regulierte oder komplexe Security-/Compliance-Szenarien werden bevorzugt gegen Enterprise-/Add-on-Pfade geprüft.

### 2. Harte Ausschlussregeln

- Copilot-Wunsch ohne berechtigte Basislizenz => Basisupgrade oder Ausschluss
- Teams Phone mit Microsoft-PSTN ohne passenden Calling-Plan-Pfad => unvollständiges Szenario
- Premium-Connectoren bei nur seeded M365-Rechten => Power-Platform-Premium-Hinweis
- Terminalserver-/Shared-Activation-Szenario ohne geeignete App-Lizenzlogik => Warnung / Ausschluss

### 3. Kosten- und Einfachheitsbewertung

- niedrigste Gesamtkosten
- geringste Überlizenzierung
- geringste Add-on-Komplexität
- bestmögliche Zukunftsfähigkeit für Wachstum / Copilot / Security

### 4. Ergebnisaufbereitung

- Primärempfehlung
- Alternativen
- auslösende Regeln
- Verweise auf Detailtools bei offenen Spezialfällen

## Bewertungsmodell

Der Score sollte transparent und pflegbar sein, zum Beispiel:

- `40 %` Feature-Abdeckung
- `20 %` kommerzielle Passung
- `15 %` Verwaltungsaufwand
- `15 %` Zukunftssicherheit
- `10 %` Überlizenzierungsrisiko

Harte Regeln übersteuern den Score. Ein schickes Ergebnis darf nie fachlich falsch sein – schöne Farben ersetzen keine Lizenzlogik. Kaffee übrigens auch nicht immer.

## Benötigte Daten

### 1. `plans.json`

- SKU / Produktname
- Lizenzfamilie
- Zielgruppe
- Monats-/Jahrespreis
- Vertragsverfügbarkeit (`P1M`, `P1Y`, optional `P3Y`)
- Kernfeatures

### 2. `addons.json`

- Add-on-Name
- Preis
- Prerequisites
- Redundanz-/Overlap-Infos
- kanal- oder segmentabhängige Hinweise

### 3. `feature_matrix.json`

- Feature-ID
- Status pro Plan: `enthalten`, `teilweise`, `Add-on`, `nicht enthalten`
- Relevanzgewicht

### 4. `persona_presets.json`

- Persona-Name
- Standardanforderungen
- empfohlene Basispfade
- Ausschlussmarker

### 5. `commercial_rules.json`

- Channel-/Term-Regeln
- Commitment-Optionen
- Kündigungsfenster-Hinweise
- Business-SKU-Grenzen / Offer-Matrix-Hinweise

## Verifizierte Weblinks / Quellenbasis

1. **License options for Microsoft 365 Copilot**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-licensing  
	Relevanz: berechtigte Basislizenzen, Add-on-Logik, Copilot Chat vs. Work Chat.

2. **Microsoft 365 app and network requirements for Microsoft 365 Copilot**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-requirements  
	Relevanz: Exchange-Online-Primärpostfach, Entra ID, OneDrive, Teams- und App-Voraussetzungen.

3. **Teams Phone licensing**  
	https://learn.microsoft.com/en-us/microsoftteams/teams-phone-licensing  
	Relevanz: Phone-System-Pfade, Calling Plan, Shared Calling, Resource Accounts.

4. **Microsoft Teams Phone Resource Account licenses**  
	https://learn.microsoft.com/en-us/microsoftteams/teams-add-on-licensing/virtual-user  
	Relevanz: separate Lizenzlogik für Auto Attendants / Call Queues.

5. **Power Platform licensing FAQs**  
	https://learn.microsoft.com/en-us/power-platform/admin/powerapps-flow-licensing-faq  
	Relevanz: Premium vs. seeded rights, Requests, Dataverse, Copilot Studio, Add-ons.

6. **Licensing overview for Microsoft Power Platform**  
	https://learn.microsoft.com/en-us/power-platform/admin/pricing-billing-skus  
	Relevanz: Dataverse mit M365-Lizenzen, Trial-/Developer-/Pay-as-you-go-Logik.

7. **SharePoint limits**  
	https://learn.microsoft.com/en-us/office365/servicedescriptions/sharepoint-online-service-description/sharepoint-online-limits  
	Relevanz: Tenant-Storage, 25-TB-Sites, Sync-Soft-Limits, Read-only-Risiko.

8. **Overview of OneDrive in Microsoft 365**  
	https://learn.microsoft.com/en-us/sharepoint/onedrive-overview  
	Relevanz: OneDrive-Verfügbarkeit, Files Restore, Storage-/Governance-Bezug.

9. **New commerce experience for license-based services**  
	https://learn.microsoft.com/en-us/partner-center/customers/new-commerce-license-based  
	Relevanz: Terms, Billing, Cancel-Fenster, Upgrades.

10. **Pricing and offers for Office 365, Dynamics CRM, Enterprise Mobility Suite, Azure, and more**  
	 https://learn.microsoft.com/en-us/partner-center/pricing/pricing-and-offers  
	 Relevanz: Preislisten, Preview-Logik, Add-on-Voraussetzungen, Markt-/Währungsunterschiede.

11. **Microsoft-Kundenvereinbarung**  
	 https://www.microsoft.com/licensing/how-to-buy/microsoft-customer-agreement  
	 Relevanz: digitale Vertragslogik, nicht ablaufende Vereinbarung, Kaufmodell.

12. **Enterprise Agreement**  
	 https://www.microsoft.com/licensing/licensing-programs/enterprise  
	 Relevanz: 500+-Kontext, Preisfixierung, Drei-Jahres-Perspektive, Enterprise-Rahmen.

13. **Microsoft 365 Business Plans**  
	 https://aka.ms/M365BusinessPlans  
	 Relevanz: offizielle Microsoft-Referenz für KMU-Planvergleiche; im Review manuell prüfen.

14. **Microsoft 365 Enterprise Plans**  
	 https://aka.ms/M365EnterprisePlans  
	 Relevanz: offizielle Microsoft-Referenz für Enterprise-Planvergleiche; im Review manuell prüfen.

## Tool-Flow

### Schritt 1 – Unternehmen & Nutzergruppen

- Größe des Kunden
- Nutzergruppen definieren
- Vertragspräferenz erfassen

### Schritt 2 – Funktionsbedarf

- Produktivität
- Security / Compliance
- Telefonie / Copilot / Power Platform
- Betriebsbesonderheiten

### Schritt 3 – Ergebnis & Alternativen

- Primärempfehlung
- Alternativen
- Kosten
- Warnungen und nächste Schritte

## UI/UX nach `cms-m365lic`

- dunkler Hero mit klarer Nutzenbotschaft
- 3-stufiger Wizard mit farblich getrennten Schritten
- KPI-Karten für Preis, Primärempfehlung, Alternativen, Risiko
- Ergebnis-Tabellen mit Feature-Details und Erklärtexten
- deutlich sichtbare Deep-Links zu Spezialrechnern
- CTA-Zone: PDF, Beratung, Rückruf, Audit

## Ergebnislogik / Textbausteine

### `Basislizenz reicht aus`

- Nutzerbedarf ist ohne Pflicht-Add-ons abgedeckt.
- keine Copilot-/Telefonie-/Premium-Connector-Ausnahme offen.

### `Basislizenz + Add-ons empfohlen`

- Basis passt grundsätzlich.
- einzelne Zusatzfunktionen sind klar zuordenbar.

### `Mischmodell empfohlen`

- eine einheitliche Lizenz für alle wäre zu teuer oder zu unpräzise.
- mindestens zwei Personas benötigen unterschiedliche Pfade.

### `Detailprüfung nötig`

- Hybrid / Spezialprodukte / unklare Channel-Lage / Governance-Sonderfall.

## Benötigte Bausteine / Funktionen

- `load_license_catalog()`
- `load_addon_catalog()`
- `load_persona_presets()`
- `validate_license_requirements()`
- `score_license_candidates()`
- `build_license_recommendation()`
- `calculate_license_totals()`
- `build_alternative_scenarios()`
- `render_license_advisor_page()`
- `export_license_advisor_pdf()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if copilot_requested && copilot_base_eligible === false => require_upgrade_or_exclude`
- `if teams_phone_requested && pstn_provider === 'microsoft' && calling_plan_missing === true => incomplete_path_warning`
- `if premium_connectors_required === true && only_seeded_power_platform_rights === true => require_power_platform_review`
- `if user_group_type === 'frontline' && desktop_apps_not_required === true => prioritize_frontline_path`
- `if addon_feature_already_included === true => mark_addon_redundant`
- `if tenant_storage_pressure === true => add_storage_governance_warning`

## Admin / Pflege

- Paketverwaltung mit Preisen, Features und Voraussetzungen
- Pflege der Persona-Presets
- Pflege von Ausschlussregeln und Score-Gewichten
- SEO-Titel, Intro, Disclaimer, CTA-Texte
- Quellenstand pro Preis- und Feature-Datensatz
- quartalsweiser Review der Microsoft-Referenzen

## SEO- und Content-Bausteine

### Fokus-Keywords

- `m365 lizenzberater`
- `microsoft 365 lizenz finden`
- `welche microsoft 365 lizenz brauche ich`
- `m365 e3 e5 business premium vergleich`
- `m365 lizenz kostenrechner`

### Empfohlene FAQ-Blöcke

- Welche Microsoft-365-Lizenz passt zu welchem Mitarbeitertyp?
- Wann reicht Business Premium statt E3?
- Wann brauche ich Add-ons statt eines Upgrades?
- Welche Voraussetzungen gelten für Copilot?
- Wann wird Teams Phone zum Zusatzprojekt?

## MVP

- 1 bis 5 Bedarfsgruppen
- Business-, Enterprise- und Frontline-Grundpfade
- wichtigste Add-ons und harte Ausschlussregeln
- Monats-/Jahrespreis
- PDF-Export

## Phase 2

- Partner-/Whitelabel-Reports
- individuelle Preisprofile je Kunde / Reseller
- API-Import aus Offer-Matrix / Preislisten
- Cross-Sell zu Backup, Copilot, Migration und Audit

## Akzeptanzkriterien

- Tool liefert nicht nur einen Plan, sondern eine begründete Empfehlung mit Alternativen.
- Copilot-, Teams-Phone- und Power-Platform-Sonderfälle werden sichtbar geprüft.
- Mischmodelle sind möglich und sauber vergleichbar.
- Ergebnis verweist bei Spezialfällen auf passende Detailtools statt blind zu raten.
- alle Regeln sind daten- und adminpflegbar, nicht hart im Template verdrahtet.

## Offene Pflegepunkte

- die offiziellen `aka.ms`-Vergleichsseiten sollten im Quartalsreview manuell gegengeprüft werden.
- Preis- und Verfügbarkeitsunterschiede nach Markt, Kanal und Segment müssen in Datenfiles modelliert werden.
- Frontline-, Shared-Activation- und einzelne Security-Details sollten vor Implementierung final gegen die aktuelle Microsoft-Produktmatrix abgeglichen werden.