# Frontline Worker Lizenz-Eignung-Check

- **Priorität:** hoch
- **Datenquelle:** Frontline-User-Typen + Geräte-/Schicht-Use-Cases + Lizenz-/Planmatrix
- **Aufwand:** niedrig bis mittel
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/frontline-worker-lizenz-check`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Das Tool prüft, welche Nutzergruppen realistisch mit `F1` oder `F3` statt `E3`, `E5` oder `Business Premium` arbeiten können – mit Fokus auf mobile, schichtbasierte und deskless Einsatzszenarien.

## Verifizierte Microsoft-Leitplanken

### Frontline Worker sind ein eigener Nutzertyp

- Microsoft beschreibt Frontline Worker als häufig mobile Mitarbeitende, die primär mit Kunden, Öffentlichkeit, Produktion oder Serviceprozessen interagieren.
- Beispiele sind Retail Associates, Healthcare-Personal, Fertigungsmitarbeitende, Field Service und ähnliche Rollen.

### F1 und F3 sind Frontline-Lizenzen – aber Enterprise bleibt möglich

- Microsoft 365 for frontline workers bezieht sich auf **F1** und **F3**.
- Für Frontline-Szenarien können aber auch **Enterprise-Lizenzen** genutzt werden, wenn der Bedarf höher ist.

### F3 und F1 sind nicht identisch

- Microsoft weist explizit darauf hin, dass einige Funktionen in **F3**, aber nicht in **F1** enthalten sind, z. B. **Power Apps** und **Power Automate**.

### Shared Devices und BYOD sind typische Frontline-Modelle

- Shared Devices und BYOD sind laut Microsoft die häufigsten Frontline-Deployment-Modelle.
- Kiosk-Geräte werden nicht empfohlen, weil userbasierte Security- und Audit-Funktionen fehlen können.

### Sicherheit und Identität bleiben Pflicht

- Frontline-Nutzer benötigen eine Identität in Microsoft Entra ID.
- Shared Device Mode, Conditional Access, MFA bzw. alternative Authentifizierungsmodelle sind zentrale Planungsfaktoren.

## Ziel des Tools

Prüfen, welche Nutzergruppen realistisch mit `F1` oder `F3` statt `E3` oder `Business Premium` arbeiten können – mit Fokus auf Industrie, Retail, Logistik, Pflege und Service.

## Gewünschte Ergebnis-Kategorien

1. `✅ F1 geeignet`
2. `🟡 F3 geeignet`
3. `🔵 Mischmodell sinnvoll`
4. `🟠 Enterprise-Lizenz weiter nötig`
5. `🔴 Manuelle Detailprüfung erforderlich`

## Kernfunktionen

- Fragebogen zu Arbeitsweise, Gerätetyp, Schichtbetrieb und App-Bedarf
- Fit-Score für `F1`, `F3`, `Mischmodell` oder `nicht geeignet`
- Einsparpotenzial pro Nutzergruppe
- Praxis-Hinweise zu Grenzen und Stolpersteinen
- Hinweise zu Shared Device, BYOD und Authentifizierung

## Eingaben

### Rollenprofil

- Nutzergruppe / Branche
- direkter Kundenkontakt ja/nein
- deskless / mobile Arbeit ja/nein
- Schichtbetrieb ja/nein

### Gerätetyp

- Shared Device
- privates Smartphone (BYOD)
- Firmen-Smartphone
- dediziertes Gerät
- Kiosk / Terminal

### App- und Funktionsbedarf

- Teams / Chat / Shifts / Planner / Lists
- Mailzugriff ja/nein
- SharePoint / OneDrive / Viva
- Power Apps / Power Automate
- Bedarf an Desktop-Apps ja/nein
- erhöhter Security-/Compliance-Bedarf ja/nein

## Ausgaben

- Fit-Score
- empfohlener Plan
- Einsparpotenzial
- Warnung bei fehlenden Funktionen oder riskantem Downgrade

## Entscheidungslogik

### 1. User-Typ bestimmen

- echter Frontline User
- Mischrolle
- klassischer Information Worker

### 2. Gerät und Nutzungsform bewerten

- Shared Device / BYOD / dediziert
- mobile Nutzung vs. Desktop-Arbeit

### 3. Lizenz fitten

- F1 für sehr leichtgewichtige mobile Szenarien
- F3 für erweiterte Frontline-Bedarfe
- Enterprise bei dokumentenzentrierter Vollnutzung

## Bewertungsmodell

- `Mobilitätsgrad`
- `Schicht-/Deskless-Fit`
- `App-Komplexität`
- `Security-/Governance-Bedarf`
- `Desktop-Abhängigkeit`

## Benötigte Daten

### 1. `frontline_user_type_matrix.json`

- Branchenrollen
- typische Geräte
- Ausschlussfaktoren

### 2. `frontline_plan_matrix.json`

- F1
- F3
- Enterprise-Alternativen
- Unterschiede bei Power Apps / Power Automate / Desktop-Nutzung

### 3. `frontline_industry_presets.json`

- Retail
- Manufacturing
- Healthcare
- Field Service

## Verifizierte Weblinks / Quellenbasis

1. **Microsoft 365 for frontline workers**  
	https://learn.microsoft.com/en-us/microsoft-365/frontline/

2. **Get started with Microsoft 365 for frontline workers**  
	https://learn.microsoft.com/en-us/microsoft-365/frontline/flw-overview?view=o365-worldwide

3. **Overview of device management for frontline workers**  
	https://learn.microsoft.com/en-us/microsoft-365/frontline/flw-devices?view=o365-worldwide

4. **Understand frontline worker user types and licensing**  
	https://learn.microsoft.com/en-us/microsoft-365/frontline/flw-licensing-options?view=o365-worldwide

5. **Modern work plan comparison**  
	https://go.microsoft.com/fwlink/p/?linkid=2139145

## Tool-Flow

### Schritt 1 – Arbeitsrealität abfragen

- Branche
- Rolle
- Gerät

### Schritt 2 – Funktionsbedarf bestimmen

- Apps
- Automatisierung
- Security

### Schritt 3 – Lizenzempfehlung ausgeben

- F1 / F3 / Enterprise
- Sparpotenzial
- Risiken

## UI/UX nach `cms-m365lic`

- kurzer Wizard mit verständlichen Praxisfragen
- Ergebnis mit Ampel und Sparpotenzial
- Branchenbeispiele als kleine Karten
- Zusatzbox `Warum F1/F3 hier passt – oder eben nicht`

## Ergebnislogik / Textbausteine

### `F1 geeignet`

- mobile, einfachere Nutzung
- geringe App-Komplexität
- keine starke Desktop-Abhängigkeit

### `F3 geeignet`

- Frontline-Szenario mit erweitertem App- und Prozessbedarf

### `Enterprise weiter nötig`

- dokumentenzentrierte Vollnutzung
- hohe Desktop- oder Spezialanforderungen

## Benötigte Bausteine / Funktionen

- `score_frontline_worker_fit()`
- `calculate_frontline_savings()`
- `build_frontline_recommendation()`
- `render_frontline_check_page()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if desktop_apps_required === true => reduce_frontline_score`
- `if role_is_mobile_shift_based === true => increase_frontline_score`
- `if power_apps_or_power_automate_needed === true => prefer_f3_over_f1`
- `if kiosk_only_model === true => show_security_warning`
- `if org_requires_shared_device_mode_or_byd_policies === true => include_device_guidance`

## Admin / Pflege

- Pflege der Presets und Grenzfälle
- Preispflege für Vergleichspläne
- Pflege der App-/Feature-Matrix

## SEO- und Content-Bausteine

### Fokus-Keywords

- `frontline worker lizenz check`
- `f1 oder f3`
- `microsoft frontline worker lizenz`
- `deskless worker microsoft 365`

### Empfohlene FAQ-Blöcke

- Für wen ist F1 oder F3 gedacht?
- Wann braucht ein Frontline User doch E3 oder Business Premium?
- Welche Geräte-Modelle sind typisch für Frontline?
- Warum ist Kiosk oft keine gute Standardlösung?

## MVP

- F1, F3, Enterprise-Vergleich
- Fit-Score
- Sparpotenzial
- Device-/Use-Case-Hinweise

## Phase 2

- branchenspezifische Fragebögen
- Export als Rollout-Empfehlung
- Policy-/Device-Empfehlungen je Nutzergruppe

## Akzeptanzkriterien

- Tool trennt echte Frontline-Profile von klassischen Information Workern.
- F1/F3-Unterschiede werden sichtbar erklärt.
- Device- und Schicht-Szenarien beeinflussen die Empfehlung nachvollziehbar.
- Downgrade-Risiken werden klar benannt.

## Offene Pflegepunkte

- Planmatrix regelmäßig gegen aktuelle Vergleichsdokumente prüfen.
- Branchenspezifische Presets mit Praxisfällen anreichern.
- Security-/Compliance-Abstufung künftig noch feiner modellieren.