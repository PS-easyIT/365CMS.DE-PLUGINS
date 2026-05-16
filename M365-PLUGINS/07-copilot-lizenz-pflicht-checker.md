# Copilot Lizenz-Pflicht-Checker

- **Priorität:** hoch
- **Datenquelle:** Copilot-Eligibility-Matrix + Basisplan-Katalog + technische Prerequisite-Regeln
- **Aufwand:** niedrig
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/copilot-lizenz-check`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Der Checker ist die schnelle Antwort auf die Frage: „Kann ich auf meiner aktuellen Lizenzbasis überhaupt Microsoft 365 Copilot zuweisen?“ Er soll nicht nur `ja/nein` liefern, sondern sauber trennen zwischen:

1. lizenzseitig geeignet
2. lizenzseitig ungeeignet
3. lizenzseitig geeignet, aber technisch noch nicht ready
4. gemischter Tenant mit nur teilweise geeigneten Gruppen

## Verifizierte Microsoft-Leitplanken

### Microsoft 365 Copilot braucht eine berechtigte Basislizenz

- Microsoft 365 Copilot ist ein **Add-on**.
- Microsoft nennt berechtigte Basispfade über Microsoft 365-, Office 365-, Teams-, Exchange-, SharePoint-, OneDrive-, Planner-/Project- und Visio-Pläne.
- Government- und Education-Szenarien haben eigene Berechtigungslisten.

### Technische Grundvoraussetzungen gehören zum Checker dazu

- Nutzer brauchen ein **Microsoft Entra ID-Konto**.
- Copilot wird nur für **primäre Exchange-Online-Postfächer** unterstützt.
- Für Copilot in Apps sind Microsoft 365 Apps, OneDrive und weitere App-/Netzwerkvoraussetzungen relevant.

### Copilot Chat ist nicht gleich Copilot-Lizenz

- Copilot Chat kann mit berechtigter Microsoft-365-Subscription bereits verfügbar sein.
- Die volle Microsoft-365-Copilot-Lizenz ist aber der relevante Pfad für Work-based Chat und tiefe In-App-Erlebnisse.

### Spezialfälle müssen separat markiert werden

- Frontline-, Education- und Government-Szenarien brauchen eigene Branches im Regelwerk.
- Gruppenmailboxen, Shared Mailboxes und Archive Mailboxes sind kein Ersatz für das geforderte primäre Exchange-Online-Postfach.

## Ziel des Tools

Ein schneller Checker, der zeigt, ob die aktuell eingesetzten Lizenzen die Voraussetzungen für Microsoft 365 Copilot erfüllen und welche Upgrades oder Vorarbeiten fehlen.

## Gewünschte Ergebnis-Kategorien

1. `✅ Lizenzseitig geeignet`
2. `🟡 Geeignet, aber technische Voraussetzungen fehlen`
3. `🟠 Upgrade der Basislizenz nötig`
4. `🔵 Gemischter Tenant – nur Teilgruppen geeignet`
5. `🔴 Nicht geeignet / Spezialpfad prüfen`

## Kernfunktionen

- Auswahl aktueller M365-/O365-/Einzellizenzen
- Eligibility-Check pro Plan
- Hinweis auf fehlende Voraussetzungen
- Upgrade-Empfehlung mit Preisdelta
- Gruppenmodus für gemischte Tenants
- Trennung von `Copilot Chat vorhanden` vs. `volle Copilot-Lizenz möglich`

## Eingaben

### Lizenzdaten

- vorhandene Lizenz pro Nutzergruppe
- Anzahl User je Gruppe
- Tenant-Typ: Commercial, Government, Education
- gewünschte Copilot-Variante

### Technik- / Betriebsdaten

- primäres Exchange-Online-Postfach vorhanden ja/nein
- OneDrive aktiviert ja/nein
- Microsoft 365 Apps bereitgestellt ja/nein
- Teams-/App-/Privacy-Readiness optional

## Ausgaben

- `geeignet`, `bedingt geeignet`, `nicht geeignet`
- Liste fehlender Lizenz- oder Technikvoraussetzungen
- empfohlener Zielplan / Upgrade-Pfad
- Mehrkosten für Copilot-fähige Ausgangsbasis
- Hinweis, ob nur Copilot Chat statt vollem Copilot realistisch ist

## Entscheidungslogik

### 1. Basislizenz prüfen

- steht der gewählte Plan auf der Eligibility-Liste?
- gehört er zum richtigen Segment (Commercial / Gov / Edu)?

### 2. Technik-Check ergänzen

- primäres Exchange-Online-Postfach vorhanden?
- Apps / OneDrive / Entra vorhanden?

### 3. Ergebnis klassifizieren

- sofort lizenzierbar
- Upgrade nötig
- technisch noch nicht ready
- Spezialfall / manuelle Prüfung

## Benötigte Daten

### 1. `copilot_eligibility_matrix.json`

- berechtigte Basispläne
- Segmente / Clouds
- Hinweise pro Planfamilie

### 2. `copilot_technical_prerequisites.json`

- Entra ID
- Exchange Online primary mailbox
- OneDrive / Apps / Teams

### 3. `license_upgrade_paths.json`

- Ist-Lizenz
- empfohlene Zielbasis
- Preisdelta

## Verifizierte Weblinks / Quellenbasis

1. **License options for Microsoft 365 Copilot**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-licensing

2. **Microsoft 365 app and network requirements for Microsoft 365 Copilot**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-requirements

3. **Which Copilot is right for me or my organization?**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/which-copilot-for-your-organization

4. **Overview of Microsoft 365 Copilot Chat**  
	https://learn.microsoft.com/en-us/copilot/overview

5. **Microsoft 365 Copilot adoption guide and overview for IT admins**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-enablement-resources

## Tool-Flow

### Schritt 1 – Ist-Lizenzen erfassen

- Plan pro Nutzergruppe
- gewünschte Copilot-Zielgruppe

### Schritt 2 – Technik-Readiness prüfen

- Exchange Online
- OneDrive / Apps / Entra

### Schritt 3 – Ergebnis & Upgrade-Pfade

- Ampelstatus
- Zielplan
- Kostenhinweis

## UI/UX nach `cms-m365lic`

- kompakter Wizard mit 1 bis 2 Schritten
- Ampelstatus im Ergebnisblock
- Upgrade-Karten mit Preisdelta
- Zusatzbox `Nur Copilot Chat vs. voller Copilot`

## Ergebnislogik / Textbausteine

### `Lizenzseitig geeignet`

- Basisplan ist berechtigt
- technische Mindestvoraussetzungen sind erfüllt oder fast erfüllt

### `Upgrade nötig`

- Basisplan ist nicht berechtigt
- klarer Upgrade-Pfad vorhanden

### `Technik zuerst`

- Lizenz wäre geeignet
- aber Exchange Online / Apps / OneDrive fehlen noch

## Benötigte Bausteine / Funktionen

- `load_copilot_prerequisites()`
- `check_copilot_license_eligibility()`
- `check_copilot_technical_readiness()`
- `build_copilot_upgrade_path()`
- `render_copilot_checker_page()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if base_license_eligible === false => upgrade_required`
- `if primary_exchange_online_mailbox === false => not_ready`
- `if m365_apps_missing === true => limited_readiness_warning`
- `if user_has_only_chat_eligibility === true => show_chat_vs_full_copilot_notice`
- `if segment === 'education' || segment === 'government' => use_segment_specific_matrix`

## Admin / Pflege

- laufende Pflege der Eligibility-Regeln
- Versions- und Quellenstand anzeigen
- Preis- und Upgrade-Pfade pflegen

## SEO- und Content-Bausteine

### Fokus-Keywords

- `copilot lizenz check`
- `welche lizenz brauche ich für copilot`
- `microsoft 365 copilot voraussetzungen`
- `copilot basislizenz`

### Empfohlene FAQ-Blöcke

- Welche Lizenz braucht man für Microsoft 365 Copilot?
- Reicht meine bestehende M365-Lizenz?
- Was ist der Unterschied zwischen Copilot Chat und Microsoft 365 Copilot?
- Warum ist Exchange Online eine Voraussetzung?

## MVP

- Business-, Enterprise-, Frontline-, Gov- und Edu-Eligibility
- Technik-Readiness-Check
- Upgrade-Hinweise

## Phase 2

- Tenant-Mix-Szenarien
- Export für Einkauf / Management
- Cross-Link zum ROI-Rechner und Pilot-Rechner

## Akzeptanzkriterien

- Tool trennt Lizenz-Eligibility von technischer Readiness.
- Copilot Chat vs. voller Copilot wird sichtbar unterschieden.
- Gov-/Edu-Sonderfälle sind im Datenmodell vorgesehen.
- Ergebnis liefert einen konkreten Upgrade- oder Vorbereitungsweg.

## Offene Pflegepunkte

- Eligibility-Liste regelmäßig gegen Microsoft Learn aktualisieren.
- Produktnamen und Segmente normieren, damit keine Duplikate entstehen.
- Preise nicht live scrapen, sondern zentral pflegen.