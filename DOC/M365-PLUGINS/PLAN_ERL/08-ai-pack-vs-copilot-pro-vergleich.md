# AI Pack vs. Copilot Pro vergleichen

- **Priorität:** hoch
- **Datenquelle:** Produktkatalog + Entscheidungsbaum + dynamische Angebots-/Lizenzmatrix
- **Aufwand:** mittel
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/ai-pack-vs-copilot-pro`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Das Tool soll nicht einfach „mehr KI = besser“ sagen, sondern je nach Use Case unterscheiden, ob bereits enthaltene Copilot-Funktionen reichen oder ob ein dedizierter Copilot-/Agent-/Spezialpfad nötig ist.

Wichtig: Produktbezeichnungen im KI-Portfolio ändern sich schnell. Deshalb sollte das Tool **katalogbasiert** gebaut werden, damit volatile Begriffe wie `Copilot Pro`, `AI Pack`, `Sales-/Service-Angebote` oder neue Agent-Bundles ohne Code-Refactoring gepflegt werden können.

## Verifizierte Microsoft-Leitplanken

### Copilot Chat ist der niedrigste Einstiegspfad

- Copilot Chat ist für Microsoft-365-Organisationen verfügbar und web-grounded.
- Es bietet Enterprise Data Protection, IT-Kontrollen und Pay-as-you-go-Agents.
- Copilot Chat ist nicht automatisch work-grounded über das gesamte Organisationswissen wie die volle Microsoft-365-Copilot-Lizenz.

### Microsoft 365 Copilot ist der Work-Data-Pfad

- Die Microsoft-365-Copilot-Lizenz ergänzt Chat um Work-/Graph-Grundlage, In-App-Erlebnisse, Agents und weitergehende Admin-Funktionen.
- Dieser Pfad ist für Wissensarbeit auf Organisationsdaten gedacht.

### Microsoft Copilot (Consumer) ist kein Unternehmensstandard für sensible Daten

- Die Consumer-Variante ist für persönliche Aufgaben gedacht.
- Für sensible oder proprietäre Unternehmensdaten ist sie nicht der Standardpfad.

### Spezial-Copilots sind rollen- oder funktionsbezogen

- Security Copilot ist für Security-Teams gedacht.
- GitHub Copilot ist für Entwickler gedacht.
- Copilot Studio ist der Pfad für eigene Agents und Prozessautomatisierung.

### Dynamische Angebotsnamen dürfen nicht hart verdrahtet werden

- Lizenz-News und Packaging-Updates verändern Microsoft-KI-Angebote regelmäßig.
- Begriffe wie `AI Pack`, `Copilot Pro`, branchenspezifische Bundles oder Sales-/Service-Angebote gehören daher in einen pflegbaren Produktkatalog.

## Ziel des Tools

Ein Use-Case-basierter Entscheidungshelfer für enthaltene Copilot-Funktionen, Microsoft 365 Copilot, spezialisierte Copilot-Produkte und künftige AI-Bundles / AI-Packs.

## Gewünschte Ergebnis-Kategorien

1. `✅ Copilot Chat reicht aus`
2. `🟡 Microsoft 365 Copilot empfohlen`
3. `🔵 Spezial-Copilot für Rolle/Funktion empfohlen`
4. `🟠 Eigener Agent-/Copilot-Studio-Pfad sinnvoll`
5. `🔴 Dynamisches Spezialangebot manuell prüfen`

## Kernfunktionen

- Entscheidungsbaum nach Rolle, Datenzugriff, Prozessbezug und Datenschutzbedarf
- Produktvergleich nach Nutzen, Datenzugriff, Admin-Modell und Kostenrahmen
- klares `passt für dieses Szenario`-Fazit
- Cross-Sell in ROI-, Lizenz- und Pilottools
- Hinweis auf dynamische Angebote, die nur katalogbasiert gepflegt werden sollten

## Eingaben

### Rollen- und Use-Case-Daten

- Wissensarbeit
- Geschäftsführung
- Vertrieb
- Service / Support
- Security
- Entwicklung
- Prozess- / Automatisierungs-Team

### Datenquellen

- Web / öffentlich
- Microsoft 365 / Graph / Files / Mail / Meetings
- CRM / Ticketing / Fachsysteme
- Sicherheitsdaten / Alerts

### Zielbild

- persönlicher Assistent
- organisatorischer Wissensassistent
- rollenbezogener Spezialassistent
- eigener Agent / Workflow

## Ausgaben

- empfohlene KI-Variante
- Alternativen mit Vor- und Nachteilen
- Kostenrahmen pro User / Tenant / Verbrauchsmodell
- Hinweis auf notwendige Basislizenzen und Readiness
- Warnung bei dynamischen oder manuell zu pflegenden Spezialangeboten

## Entscheidungslogik

### 1. Datenzugriff prüfen

- nur Web / allgemeine Fragen => Copilot Chat oder Consumer-Pfad
- Organisationsdaten / Graph nötig => Microsoft 365 Copilot
- Fachrollen / Spezialplattformen => Spezial-Copilot oder Agent-Pfad

### 2. Rollenbezug prüfen

- Security => Security Copilot
- Development => GitHub Copilot
- Prozess-/Agentenbedarf => Copilot Studio
- klassische Wissensarbeit => Chat vs. M365 Copilot

### 3. Angebotsdynamik prüfen

- volatile Produktlabels wie `AI Pack` / `Copilot Pro` / Spezial-Bundles nur aus gepflegtem Produktkatalog empfehlen

## Benötigte Daten

### 1. `ai_product_catalog.json`

- Produktname
- Produkttyp
- Datenquelle (`web`, `work`, `specialized`, `agent`)
- Kostenmodell
- Zielrollen

### 2. `ai_use_case_matrix.json`

- Use Case
- benötigte Datenbasis
- empfohlene Produkttypen
- harte Ausschlüsse

### 3. `ai_dynamic_offers.json`

- volatile Produktnamen
- manuell zu prüfende Angebote
- Quellenstand

## Verifizierte Weblinks / Quellenbasis

1. **Which Copilot is right for me or my organization?**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/which-copilot-for-your-organization

2. **Overview of Microsoft 365 Copilot Chat**  
	https://learn.microsoft.com/en-us/copilot/overview

3. **License options for Microsoft 365 Copilot**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-licensing

4. **Microsoft Licensing News**  
	https://www.microsoft.com/en-us/licensing/news

5. **Power Platform licensing FAQs**  
	https://learn.microsoft.com/en-us/power-platform/admin/powerapps-flow-licensing-faq

## Tool-Flow

### Schritt 1 – Wer nutzt die KI?

- Rolle
- Datenquellen
- gewünschter Scope

### Schritt 2 – Welche Art Copilot wird gebraucht?

- web-grounded
- work-grounded
- role-specific
- agent-based

### Schritt 3 – Empfehlung und Alternativen

- Produktwahl
- Lizenz-/Kanalhinweis
- Deep-Link zu Lizenz- oder ROI-Tool

## UI/UX nach `cms-m365lic`

- Wizard mit klaren Erklärtexten
- Ergebniskarte `Beste Wahl` + 2 Alternativen
- Compare-Table mit farbigen Kategorien
- Zusatzbox `Produktnamen ändern sich – Katalogstand beachten`

## Ergebnislogik / Textbausteine

### `Copilot Chat reicht aus`

- Web-Fokus
- kein tiefer Graph-/Datei-/Mail-Zugriff nötig
- schneller Einstieg erwünscht

### `Microsoft 365 Copilot empfohlen`

- Arbeitsdaten, Meetings, E-Mails, Dokumente und In-App-Produktivität stehen im Mittelpunkt

### `Spezial-Copilot`

- klar abgegrenzte Fachrolle wie Security oder Entwicklung

### `Dynamisches Spezialangebot`

- Use Case fällt in ein Produktfeld, das katalog- und marktseitig aktuell gepflegt werden muss

## Benötigte Bausteine / Funktionen

- `load_ai_product_matrix()`
- `load_dynamic_ai_offer_catalog()`
- `evaluate_ai_use_case()`
- `build_ai_recommendation()`
- `render_ai_product_comparison_page()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if needs_org_data_grounding === true => prefer_m365_copilot`
- `if web_only_usecase === true && no_sensitive_data === true => copilot_chat_or_consumer_path`
- `if role === 'security' => security_copilot`
- `if role === 'developer' => github_copilot`
- `if needs_custom_agents === true => copilot_studio`
- `if product_label_is_dynamic === true => manual_catalog_validation_required`

## Admin / Pflege

- Pflege neuer KI-Produkte und Angebotsnamen
- Pflege der Use-Case-Regeln
- Preis- und Quellenaktualisierung
- Review von Packaging-/Pricing-News

## SEO- und Content-Bausteine

### Fokus-Keywords

- `copilot chat vs microsoft 365 copilot`
- `copilot pro oder m365 copilot`
- `welcher copilot ist richtig`
- `ai pack vs copilot`

### Empfohlene FAQ-Blöcke

- Wann reicht Copilot Chat aus?
- Wann brauche ich Microsoft 365 Copilot?
- Was ist der Unterschied zu spezialisierten Copilots?
- Warum sollte man Produktnamen nicht hart verdrahten?

## MVP

- 5 bis 7 Kernpfade
- Entscheidungsbaum
- Ergebnisvergleich

## Phase 2

- vertikale Branchenpfade
- PDF-Entscheidungsvorlage
- E-Mail-Lead-Capture
- Marktdaten-/Preisprofile je Region

## Akzeptanzkriterien

- Tool trennt sauber zwischen Chat-, Work-, Spezial- und Agent-Pfaden.
- Dynamische Produktnamen sind als pflegbarer Katalog modelliert.
- Spezialisierte Copilots werden rollenbasiert empfohlen.
- Ergebnis verweist bei Unschärfen auf manuelle Angebotsprüfung.

## Offene Pflegepunkte

- volatile KI-Angebote regelmäßig gegen Microsoft-Lizenz-News und Produktseiten prüfen.
- `Copilot Pro`/`AI Pack`-Abbildung bewusst katalogbasiert halten.
- Vertriebsnahe Spezialprodukte nur mit sauberer Quellenpflege freischalten.