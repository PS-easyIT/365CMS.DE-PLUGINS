# Power Platform Kosten-Kalkulator

- **Priorität:** hoch
- **Datenquelle:** Microsoft Learn / Power Platform Licensing Guide + eigene Katalogdateien + `pricing.json`
- **Aufwand:** mittel bis hoch
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/power-platform-kosten-kalkulator`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Das Tool soll **nicht nur einen einzelnen Power-Apps-Preis ausspucken**, sondern belastbar beantworten:

1. **Reichen die in Microsoft 365 enthaltenen Rechte aus oder ist eine Standalone-Lizenz nötig?**
2. **Ist `Power Apps per app`, `Power Apps Premium`, `Power Automate Premium`, `Process`, `Hosted RPA`, `Power Pages` oder `Copilot Studio` der richtige Pfad?**
3. **Wann kippt ein Teams-/Citizen-Development-Szenario von `Dataverse for Teams` in ein echtes Dataverse-/Premium-Modell?**
4. **Welche Zusatzkosten entstehen durch Kapazität, Credits, Requests, Storage oder Bot-/Website-Modelle?**
5. **Wo drohen typische Fehlkäufe – etwa zu viele Per-User-Lizenzen, zu wenig Credits oder ein Dataverse-for-Teams-Szenario, das fachlich längst entwachsen ist?**

Der Rechner ist damit gleichzeitig:

- Produktberater
- Lizenzpfad-Entscheider
- Kostenrechner
- Governance-Frühwarnsystem
- Lead-Magnet für Power-Platform-Review, Governance und Lizenz-Audit

## Verifizierte Microsoft-Leitplanken

Die folgenden Regeln sollten als **harte oder stark gewichtete Leitplanken** im Tool hinterlegt werden.

### Microsoft-365-Seeded-Rechte sind begrenzt

- Power Apps und Power Automate, die in Microsoft-365-Lizenzen enthalten sind, reichen für **Standard-Connectoren** und Microsoft-365-nahe Szenarien.
- **Premium-, Custom- und On-Premises-Connectoren** sowie Premium-Gateways brauchen eine passende **Standalone-Lizenz**.
- Das in einigen Microsoft-365-Kontexten sichtbare `Dataverse`-Service-Plan bedeutet **nicht**, dass beliebige Custom Apps oder allgemeine Dataverse-Nutzung ohne Premium-Lizenz erlaubt sind.

### Power Apps: Per App, Premium und Pay-as-you-go sauber trennen

- `Power Apps Premium` ist laut Microsoft **per User** lizenziert und erlaubt unbegrenzt Custom Apps sowie unbegrenzten Zugriff auf Power-Pages-Websites.
- Microsoft nennt im FAQ einen Listenpreis von **`20 USD/User/Monat`**, bzw. **`12 USD/User/Monat`** bei `2.000+` neuen User-Lizenzen.
- `Power Apps per app` ist der **Szenario-Einstiegspfad** für **eine App oder eine Power-Pages-Website pro User in einer Umgebung** und wird mit **`5 USD/User/App/Monat`** beschrieben.
- `Per app` ist **stackable** und wird **an Umgebungen** zugewiesen, nicht klassisch im M365 Admin Center pro Nutzer.
- `Pay-as-you-go` ist für **schwankende oder schwer planbare Nutzung** gedacht und rechnet über eine **Azure Subscription / Billing Policy** ab.

### Power Automate: User-Lizenz vs. Capacity-Lizenz

- `Power Automate Premium` ist laut Microsoft die empfohlene **User-Lizenz** für volle Cloud- und Desktop-Automatisierung inklusive Premium-/Custom-Connectoren.
- Im FAQ wird `Power Automate Premium` mit **`15 USD/User/Monat`** genannt.
- `Power Apps`-Lizenzen enthalten Power-Automate-Rechte **nur im Kontext der App**; losgelöste Premium-Flows brauchen eine eigene Power-Automate- oder Power-Apps-Premium-Lizenz.
- `Power Automate Process` ist ein **Capacity-Modell** für unbeaufsichtigte Bots oder flow-zentrierte Business-Prozesse und wird im FAQ mit **`150 USD/Bot/Monat`** beschrieben.
- `Power Automate Hosted RPA` ist ein Add-on mit **`215 USD/Bot/Monat`** und braucht eine qualifizierende Basislizenz.
- Für unbeaufsichtigte Microsoft-365-/Office-365-Automation können zusätzliche Produktthemen greifen; Microsoft verweist hier ausdrücklich auf die **Product Terms**.

### Process Mining, AI Builder und Credits sind eigene Kostentreiber

- `Power Automate Process Mining` wird im FAQ mit **`5.000 USD pro 100 GB/Monat`** beschrieben und setzt `Power Automate Premium` voraus.
- `Power BI`-Lizenzen sind für Process Mining **nicht automatisch enthalten**; Microsoft nennt sie als potenziell separaten Bedarf.
- AI Builder läuft kreditbasiert: in Apps/Flows zuerst über **AI Builder Credits**, danach – falls vorhanden – über **Copilot Credits**.
- In `Copilot Studio` und Agent-Flows werden AI-Builder-Funktionen **immer** über **Copilot Credits** abgerechnet.
- Credits werden **monatlich zurückgesetzt** und **rollen nicht über**.
- Seit den dokumentierten Änderungen ab November 2025 sind AI-Builder-Add-ons für neue Kunden **nicht mehr der primäre Neukundenpfad**; neue Kunden sollen Copilot Credits nutzen.

### Copilot Studio ist tenant- und nachrichtenbasiert

- `Microsoft Copilot Studio` ist laut FAQ **tenant-basiert** und enthält **25.000 Messages pro Monat**.
- Für Authoring gibt es zusätzlich eine **User License** mit **`0 USD/User/Monat`** als Authoring-Recht.
- `Copilot Studio for Teams` kann über ausgewählte Microsoft-365-/Office-365-Lizenzen verfügbar sein, ist aber **Teams-zentriert** und nicht gleichbedeutend mit voller Copilot-Studio-Nutzung.
- Für `Copilot Studio for Teams` gilt laut FAQ eine **Service-Limitierung von 10 Sessions pro User / 24 Stunden** tenantweit über alle Bots.

### Power Pages ist kein Per-User-Plan, sondern ein Kapazitätsmodell

- `Power Pages` rechnet nach **unique authenticated** oder **unique anonymous users pro Website und Monat**.
- Authenticated-User-Kapazität startet im FAQ bei **100 Usern / `200 USD`** pro Pack.
- Anonymous-User-Kapazität startet bei **500 Usern / `75 USD`** pro Pack.
- Bei Subscription-Modellen gelten Mindestzuweisungen pro Umgebung: **25** für authenticated und **200** für anonymous.
- Interne Nutzer mit `Power Apps per app`, `Power Apps per user/Premium` oder `Dynamics 365 Enterprise` können je nach Szenario bereits Nutzungsrechte für Power Pages mitbringen.

### Dataverse for Teams ist nützlich, aber bewusst limitiert

- `Dataverse for Teams` ist für Teams-Szenarien gedacht und wird **in Teams** erstellt.
- Pro Team gibt es **ein** Dataverse-for-Teams-Environment.
- Pro Environment gelten laut Microsoft **ca. 2 GB / 1.000.000 Datensätze** als Grenze.
- Es gibt **keine zusätzliche Kapazität zum Nachkaufen**; bei Wachstum ist der vorgesehene Pfad das **Upgrade zu Dataverse**.
- **AI Builder** und **Desktop Flows** werden in Dataverse for Teams **nicht** unterstützt.
- Premium-Connectoren in einem Dataverse-for-Teams-Environment sind möglich, aber nur wenn **alle betroffenen User passend lizenziert** sind.
- Für Standalone-Nutzung außerhalb Teams oder allgemeinen Dataverse-API-Zugriff ist ein **Upgrade zu Dataverse** nötig.

### Kapazität und Governance sind eigene Module

- Microsoft nennt als allgemeine Add-ons u. a.:
  - `Power Platform Requests Add-on` mit **10.000 zusätzlichen Daily API Requests** für **`50 USD/Monat`**
  - `Dataverse Database Capacity` **`40 USD/GB/Monat`**
  - `Dataverse File Capacity` **`2 USD/GB/Monat`**
  - `Dataverse Log Capacity` **`10 USD/GB/Monat`**
- `Managed Environments` sind als Entitlement in vielen Premium-/Capacity-Angeboten enthalten und sollten im Tool als Governance-Mehrwert ausgewiesen werden.
- Fortgeschrittene Controls wie `CMK`, `Customer Lockbox` oder `vNet` sind **kein Standard-Bestandteil jedes Szenarios** und benötigen ggf. zusätzliche Compliance-/Security-Voraussetzungen.

## Ziel des Tools

Hilft bei der Auswahl des wirtschaftlich und lizenzrechtlich passenden Power-Platform-Pfads über **Apps, Flows, Bots, Websites, Credits und Kapazität** hinweg – inklusive klarer Abgrenzung zwischen:

- enthaltenen Microsoft-365-Rechten
- kostengünstigem Einstieg
- Premium-Per-User-Pfaden
- Capacity-/Bot-/Website-Modellen
- Architektur- und Governance-Sonderfällen

## Gewünschte Ergebnis-Kategorien

Der Rechner sollte mindestens diese Hauptzustände unterscheiden:

1. `✅ Seeded Microsoft-365-/Teams-Rechte reichen aus`
2. `🟢 Günstiger Einstieg via Per App oder Pay-as-you-go sinnvoll`
3. `🟡 Per-User-Premium empfohlen`
4. `🟠 Capacity-/Bot-/Website-Modell erforderlich`
5. `🔴 Dataverse-/Governance-/Architektur-Review nötig`

Optional darf das Tool mehrere Teil-Ergebnisse kombinieren, z. B.:

- `Power Apps Premium + Power Automate Premium`
- `Copilot Studio + Copilot Credits`
- `Power Pages + Authenticated Capacity`
- `Dataverse for Teams heute okay, Upgrade-Pfad in 6 Monaten nötig`

## Kernfunktionen

- Wizard für die Haupt-Use-Cases: App, Flow, Bot/Agent, Website, Teams-Extension, Citizen-Development, RPA
- saubere Trennung zwischen `M365 seeded`, `Per App`, `Per User`, `Capacity`, `Credit-based`
- Connector-Check für `Standard`, `Premium`, `Custom`, `On-Premises`
- Dataverse-for-Teams-Fit-Check inklusive Upgrade-Hinweis
- Kostenrechner pro User, Bot, Website, Environment und Credits
- Hinweis auf Zusatzbedarf wie Requests, Dataverse-Storage, Process Mining, Power BI
- Erkennung typischer Fehlkäufe und Überlizenzierung
- Ausgabe einer klaren Architektur- und Lizenzempfehlung mit Begründung
- PDF-Export und CTA für Governance-/Lizenz-Review

## Eingaben

### Pflichtfelder

- Haupt-Use-Case
  - interne App
  - Workflow / Cloud Flow
  - Desktop-Automation / RPA
  - Chatbot / Agent
  - Website / Portal
  - Teams-nahe Lösung
- Anzahl Maker
- Anzahl Endnutzer
- Anzahl Umgebungen
- geplanter Go-live-Zeitraum

### Fachliche Entscheidungsfelder

- Standard- oder Premium-Connectoren?
- Custom Connector nötig `ja/nein`
- On-Premises Gateway nötig `ja/nein`
- läuft der Flow nur **im Kontext einer App** `ja/nein`
- Anzahl Apps / Szenarien pro User
- Teams-only oder Nutzung auch außerhalb Teams
- interne oder externe Nutzer
- Website mit Authenticated oder Anonymous Zugriff
- unattended / attended / hosted RPA nötig
- AI Builder / Prompting / Dokumentenverarbeitung nötig

### Betriebs- und Volumenfelder

- geschätzte API-Last / Requests
- erwarteter Dataverse-Datenbestand
- erwarteter File-Storage-Bedarf
- Process-Mining-Datenvolumen
- erwartete Copilot-/AI-Builder-Nutzung pro Monat
- stark schwankende Nutzung `ja/nein`

### Governance-Felder

- Managed Environment vorgesehen `ja/nein`
- DLP-/Tenant-Governance relevant `ja/nein`
- CMK / Customer Lockbox / vNet relevant `ja/nein`
- Azure Subscription für PAYG vorhanden `ja/nein`

## Ausgaben

- empfohlener Hauptpfad pro Use-Case
- Monats- und Jahreskosten als Bandbreite
- Aufschlüsselung nach User-, Capacity-, Credit- und Storage-Kosten
- Upgrade-/Downgrade-Alternative
- Liste der auslösenden Microsoft-Regeln
- Warnhinweise zu `Dataverse for Teams`, Credits, Premium-Connectoren und RPA
- Hinweise auf angrenzende Lizenzthemen wie `Power BI`, `M365 Premium`, `Dynamics 365`
- CTA: `Power-Platform-Lizenz-Review`, `Governance-Workshop`, `PDF exportieren`

## Entscheidungslogik

### Harte Ausschlussregeln für „Seeded Rechte reichen“

- Premium Connector nötig
- Custom Connector nötig
- On-Premises Data Gateway nötig
- Custom App / Flow läuft losgelöst vom Microsoft-365-Kontext
- allgemeine Dataverse-Nutzung außerhalb des begrenzten Service-Kontexts
- Copilot-Studio- oder AI-Builder-Szenario außerhalb der enthaltenen Teams-Rechte

### Regeln für `Power Apps per app`

- ein klar abgegrenztes Geschäftsszenario
- eine App oder eine Website pro User in einer Umgebung
- Premium-Funktionen werden benötigt, aber kein pauschales Unlimited-Modell
- User benötigen kein breites Maker-/App-Portfolio

### Regeln für `Power Apps Premium`

- mehrere Apps pro User
- mehrere Szenarien / Umgebungen
- breite Nutzung von Premium-, Custom- oder On-Premises-Connectoren
- Dataverse-basierte App-Landschaft statt Einzel-App

### Regeln für `Power Automate Premium`

- Premium- oder Custom-Connectoren in Flows
- Flows laufen **nicht nur** im Kontext einer Power-App
- User bauen oder betreiben selbstständig Cloud- und Desktop-Automationen
- attended RPA nötig

### Regeln für `Process` / `Hosted RPA`

- unbeaufsichtigte Desktop-Flows
- zentral betriebene Prozessautomatisierung
- Flows oder Bots sollen unabhängig vom User-Lizenzmix funktionieren
- gehostete Maschinen / VM-Bereitstellung gewünscht
- Parallelität oder hohe Tagesaktionenzahl nötig

### Regeln für `Power Pages`

- externe oder interne Web-Oberfläche mit Website-Charakter
- Abrechnung nach unique authenticated / anonymous users sinnvoller als klassische App-Lizenzen
- interne Authenticated-User ggf. bereits über Power Apps / D365 abgedeckt

### Regeln für `Dataverse for Teams`

- Teams-zentrischer Use-Case
- geringe bis mittlere Datenmengen
- keine AI Builder-/Desktop-Flow-Anforderungen
- keine allgemeine Nutzung außerhalb Teams
- keine harte Enterprise-ALM-/Audit-/Governance-Anforderung über das Teams-Modell hinaus

### Regeln für `Upgrade auf Dataverse`

- Dataverse-for-Teams-Kapazitätsgrenze erreicht
- Nutzung außerhalb Teams nötig
- AI Builder nötig
- Desktop Flows nötig
- Enterprise-ALM, stärkeres Security-/Audit-Modell oder breitere API-Nutzung nötig

## Bewertungsmodell

Neben den harten Regeln sollte das Tool mit einem verständlichen Score arbeiten:

- `+3` reines M365-/Teams-Erweiterungsszenario mit Standard-Connectoren
- `+3` klarer Einzel-Use-Case für `per app`
- `+2` schwankende Nutzung spricht für `PAYG`
- `+2` viele Apps / viele Maker sprechen für `per user`
- `+3` unattended / hosted / zentrale Automationsplattform spricht für `capacity`
- `-4` Premium-/Custom-/On-Premises-Connectoren gegen `seeded`
- `-3` AI Builder / Credits / Process Mining erhöhen Komplexität
- `-3` Dataverse-for-Teams-Limit oder Outside-Teams-Nutzung
- `-2` Governance-/ALM-Anforderungen ohne Premium-Setup

Der Score soll **nicht** die harte Regelbasis ersetzen, sondern Grenzfälle erklären.

## Benötigte Daten

### 1. Produktkatalog `power_platform_products.json`

- `sku`
- `family`
- `license_type` (`seeded`, `user`, `capacity`, `credit`, `website`)
- `billing_unit`
- `list_price_monthly`
- `currency`
- `includes_dataverse`
- `includes_premium_connectors`
- `includes_custom_connectors`
- `includes_onprem_gateway`
- `notes`

### 2. Use-Case-Regeln `power_platform_use_cases.json`

- `use_case_slug`
- `recommended_path`
- `secondary_path`
- `requires_dataverse`
- `allows_dataverse_for_teams`
- `supports_seeded_rights`
- `needs_capacity_model`

### 3. Connector- und Trigger-Matrix `power_platform_connector_rules.json`

- `connector_type`
- `seeded_allowed`
- `per_app_allowed`
- `per_user_required`
- `capacity_compatible`
- `notes`

### 4. Capacity-/Credit-Katalog `power_platform_capacity_catalog.json`

- Requests add-on
- Dataverse DB/File/Log capacity
- AI Builder credits / Copilot Credits
- Process Mining capacity
- Hosted RPA add-on
- Authenticated / Anonymous Power Pages packs

### 5. Governance-Regeln `power_platform_governance_rules.json`

- `managed_environment_entitlement`
- `cmk_prerequisites`
- `lockbox_prerequisites`
- `vnet_prerequisites`
- `dlp_considerations`

## Verifizierte Weblinks / Quellenbasis

### A. Zentrale Licensing-Basis

1. **Power Platform licensing FAQs**  
	https://learn.microsoft.com/en-us/power-platform/admin/powerapps-flow-licensing-faq  
	Relevanz: Gesamtüberblick für Power Apps, Power Automate, Power Pages, Copilot Studio, Dataverse for Teams, AI Builder, Add-ons.

2. **Licensing overview for Microsoft Power Platform**  
	https://learn.microsoft.com/en-us/power-platform/admin/pricing-billing-skus  
	Relevanz: offizieller Überblick zu Offers, Seeded-Rechten, Trial-/Developer-/PAYG-Modellen und Dataverse-Einordnung.

3. **Microsoft Power Platform Licensing Guide**  
	https://go.microsoft.com/fwlink/?linkid=2085130  
	Relevanz: kanalübergreifende Referenz für Pflege und Feinabgleich.

### B. Power Apps / Per App / PAYG

4. **About Power Apps per app plans**  
	https://learn.microsoft.com/en-us/power-platform/admin/about-powerapps-perapp  
	Relevanz: Environment-Zuweisung, Consumption-Modell, Stackability, Sharing-Logik.

5. **Pay-as-you-go plan**  
	https://learn.microsoft.com/en-us/power-platform/admin/pay-as-you-go-overview  
	Relevanz: Azure-Billing-Policy, variable Nutzung, PAYG-Umgebungen.

### C. Power Automate / Capacity / RPA

6. **Types of Power Automate licenses**  
	https://learn.microsoft.com/en-us/power-platform/admin/power-automate-licensing/types  
	Relevanz: User vs. Capacity, Process, Hosted Process, Connector-Entitlements, Daily Action Limits.

7. **Capacity add-ons**  
	https://learn.microsoft.com/en-us/power-platform/admin/capacity-add-on  
	Relevanz: Environment-Zuweisung von App Passes, Capacity und Add-ons.

### D. Dataverse for Teams / Upgrade-Pfade

8. **About the Microsoft Dataverse for Teams environment**  
	https://learn.microsoft.com/en-us/power-platform/admin/about-teams-environment  
	Relevanz: Teams-only-Modell, 2-GB-Limit, 1:1-Team-Umgebung, Upgrade-Regeln, Governance.

### E. AI Builder / Credits

9. **Overview of licensing for AI Builder**  
	https://learn.microsoft.com/en-us/ai-builder/administer-licensing  
	Relevanz: Copilot Credits, AI Builder Credits, Monthly Reset, Consumption-Logik, Neukundenpfad.

> Empfehlung: Preise **nicht live scrapen**, sondern in `pricing.json` oder `power_platform_products.json` pflegen und regelmäßig gegen Microsoft Learn / Pricing-Seiten spiegeln.

## Tool-Flow

### Schritt 1 – Use Case & Nutzertypen

- Was wird gebaut: App, Flow, Bot, Website oder RPA?
- Wie viele Maker und Endnutzer gibt es?
- Teams-only oder breitere Plattformnutzung?

### Schritt 2 – Datenquellen & Premium-Bedarf

- Standard vs. Premium vs. Custom Connector
- On-Premises / Dataverse / API-Zugriffe
- AI Builder / Credits / Process Mining / RPA

### Schritt 3 – Modell & Kosten

- per app vs. per user vs. capacity vs. credits
- Dataverse-for-Teams-Grenzen und Upgrade-Pfad
- Monats-/Jahreskosten, Alternativen und Warnhinweise

## UI/UX nach `cms-m365lic`

- dunkler Hero mit dem Versprechen: `Welche Power-Platform-Lizenz ist wirklich nötig – und wo verbrennst du gerade Budget?`
- Wizard in 3 Schritten
- KPI-Karten für:
  - Hauptpfad
  - monatliche Kosten
  - größte Kostentreiber
  - Governance-/Upgrade-Risiko
- Ergebnis-Stack mit separaten Karten für `Apps`, `Flows`, `Bots`, `Websites`, `Credits`
- Compare-Block für `seeded` vs. `per app` vs. `per user` vs. `capacity`
- erklärender Infoblock `Wichtige Microsoft-Leitplanken`

## Ergebnislogik / Textbausteine

### `Seeded Rechte reichen aus`

- Szenario bleibt im Microsoft-365-/Teams-Kontext
- nur Standard-Connectoren
- kein allgemeines Dataverse-/Premium-/RPA-Thema

### `Per App / PAYG sinnvoll`

- klar abgegrenztes Geschäftsszenario
- begrenzte Nutzerbasis oder unvorhersehbare Nutzung
- kein unbegrenztes App-Portfolio pro User

### `Per-User-Premium empfohlen`

- mehrere Apps / Flows / Maker je User
- Premium- und Custom-Connectoren zentraler Bestandteil
- Dataverse / Governance / Mobility breiter relevant

### `Capacity-/Bot-/Website-Modell nötig`

- unattended oder hosted RPA
- Website-/Portal-Abrechnung
- hoher Process-Mining- oder Credit-Bedarf

### `Architektur-Review nötig`

- Dataverse for Teams wird fachlich überschritten
- Credits / Governance / Storage / Connectoren greifen gleichzeitig
- Risiko für spätere Umbauten oder Compliance-Probleme

## Benötigte Bausteine / Funktionen

- `load_power_platform_products()`
- `load_power_platform_use_cases()`
- `load_power_platform_connector_rules()`
- `load_power_platform_capacity_catalog()`
- `load_power_platform_governance_rules()`
- `validate_power_platform_input()`
- `evaluate_power_platform_use_case()`
- `evaluate_power_platform_seeded_rights()`
- `evaluate_dataverse_for_teams_fit()`
- `calculate_power_platform_costs()`
- `calculate_power_platform_capacity_costs()`
- `calculate_power_platform_credit_usage()`
- `build_power_platform_recommendation()`
- `render_power_platform_page()`
- `export_power_platform_pdf()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if connector_type in ['premium', 'custom', 'onprem'] => seeded_not_sufficient`
- `if app_count_per_user <= 1 and scenario_count == 1 => candidate_per_app`
- `if usage_pattern == 'unpredictable' and azure_subscription == true => candidate_payg`
- `if flow_context != 'in_app' => power_automate_license_required`
- `if unattended_rpa == true => process_or_hosted_rpa`
- `if hosted_machine_required == true => hosted_rpa_addon_required`
- `if website_required == true => power_pages_capacity_model`
- `if chatbot_scope == 'teams_only' and premium_connectors == false => candidate_copilot_studio_for_teams`
- `if dataverse_for_teams == true and (needs_ai_builder or needs_desktop_flows or outside_teams_use) => upgrade_to_dataverse`
- `if ai_builder_required == true and credits_available == false => copilot_or_ai_builder_capacity_needed`
- `if process_mining_analysis_extended == true => flag_power_bi_review`

## Admin / Pflege

- Pflege aller Produktpreise und Währungen
- Pflege der Credit- und Capacity-Tabellen
- Pflege der Connector-Klassifikation `standard/premium/custom/onprem`
- Pflege des Upgrade-Pfads `Dataverse for Teams -> Dataverse`
- Review der Microsoft-Learn-Quellen mindestens quartalsweise

## SEO- und Content-Bausteine

### Fokus-Keywords

- `power platform kosten`
- `power apps premium oder per app`
- `power automate premium kosten`
- `dataverse for teams limit`
- `power pages lizenz`
- `copilot studio lizenz`
- `ai builder credits`

### Empfohlene FAQ-Blöcke

- Reicht Power Apps in Microsoft 365 aus?
- Wann brauche ich Premium-Connectoren?
- Was ist der Unterschied zwischen Per App und Premium?
- Wann brauche ich Power Automate Process statt Premium?
- Was kostet Power Pages wirklich?
- Wann ist Dataverse for Teams zu klein?
- Wie funktionieren Copilot Credits und AI Builder Credits?

## MVP

- App-/Flow-/Bot-/Website-Wizard
- Seeded-vs.-Paid-Entscheidung
- Kostenmodell für `per app`, `per user`, `process`, `hosted RPA`, `Power Pages`
- Dataverse-for-Teams-Fit-Check
- PDF-Export

## Phase 2

- Tenant-weite Portfolio-Analyse
- CSV-Import von Lizenzbeständen und Environments
- Simulation mehrerer Ausbaupfade über 12 bis 36 Monate
- Governance-Modul mit DLP-/Managed-Environment-Empfehlungen
- Partner-/Member-Sicht mit EK-/VK-Kalkulation

## Akzeptanzkriterien

- Tool trennt sauber zwischen `enthaltenen Rechten` und `Premium-/Capacity-Bedarf`
- Tool erkennt Premium-/Custom-/On-Premises-Connectoren als harte Lizenztreiber
- Tool behandelt `Dataverse for Teams` als **echten Sonderpfad** mit Limitierungen und Upgrade-Regeln
- Tool weist Credits, Storage und Requests als eigene Kostenarten aus
- Tool markiert `Power BI` nur dort, wo Microsoft es als angrenzenden Bedarf nennt
- alle Preis- und Regelannahmen sind adminseitig pflegbar

## Offene Pflegepunkte

- Produktpreise und Angebotsnamen ändern sich regelmäßig; deshalb katalogbasiert pflegen
- `Copilot Studio`-Preise und Credit-Pakete nicht hart verdrahten, sondern separat verwalten
- `Power BI` bewusst nur als angrenzendes Thema modellieren, nicht als Kernbestandteil der Power-Platform-Logik
- Governance-Features wie `CMK`, `Lockbox`, `vNet` nur im erweiterten Modus bewerten, da sie nicht für jedes Projekt relevant sind

## Umsetzung 1.21.0 – 2026-05-17

- Live-Modul `power-platform-cost-calculator` unter `/power-platform-kosten-kalkulator` ergänzt.
- Engine `CMS_M365CALCULATOR_Power_Platform_Cost_Calculator` mit Normalisierung, Seeded-Fit, Connector-Regeln, Dataverse-for-Teams-Fit, Kostenblöcken, Credit-Auswertung, Capacity-Kosten, Empfehlung, Warnungen und nächsten Schritten umgesetzt.
- Public Template `templates/page-power-platform-cost-calculator.php` im PHINIT-Layout ergänzt.
- Kataloge `power_platform_products.json`, `power_platform_use_cases.json`, `power_platform_connector_rules.json`, `power_platform_capacity_catalog.json` und `power_platform_governance_rules.json` ergänzt.
- Route, Tool-Registry, Katalogloader, Update-Manifest, README und zentrale Dokumentation synchronisiert.
- Public-Seite speichert keine Eingaben serverseitig und zeigt keine technischen Prüfmechanismen an.