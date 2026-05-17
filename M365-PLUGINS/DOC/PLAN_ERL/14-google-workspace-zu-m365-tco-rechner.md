# Google Workspace → M365 TCO-Rechner 
# M365 TCO-Rechner → Google Workspace

- **Priorität:** hoch
- **Datenquelle:** Google-Workspace-Plan-/Preiskatalog + Microsoft-365-Zielpläne + Migrations- und Change-Defaults
- **Aufwand:** mittel
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/google-workspace-zu-m365-tco`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Das Tool vergleicht die Total Cost of Ownership von Google Workspace und Microsoft 365 über 3 Jahre – nicht nur auf Basis der Lizenzpreise, sondern inklusive Migration, Schulung, Change-Aufwand und Übergangsphase oder anders herum.

## Verifizierte Microsoft- und Google-Leitplanken

### Google Workspace hat planabhängige Storage- und Funktionsmodelle

- Google Workspace zeigt planabhängige Speicherwerte wie z. B. `30 GB`, `2 TB`, `5 TB` pro Nutzer oder Enterprise-Sonderregeln.
- Business Starter, Business Standard und Business Plus gelten laut Google für Unternehmen mit bis zu **300 Nutzern**; Enterprise ist breiter ausgelegt.
- Google weist außerdem auf separat erhältliche Add-ons hin.

### Microsoft unterstützt eine native Google-Workspace-Migration für Mail-Daten

- Exchange Online unterstützt eine automatisierte **Google Workspace Migration** über das Exchange Admin Center.
- Als migrierbare Filter nennt Microsoft **Mail**, **Calendar**, **Contacts** und **Rules**.

### MRM-/Archiv-Policies können Migrationsprüfungen verfälschen

- Microsoft empfiehlt, MRM- und Archivierungsrichtlinien vor einer Datenmigration zu deaktivieren, weil sonst Items als `missing` erscheinen können, obwohl sie nur verschoben oder archiviert wurden.

### Preisvergleich allein reicht nicht

- Ein echter TCO-Vergleich muss Lizenzkosten, Migration, Schulung, Change Management, Admin-Mehraufwand und ggf. Übergangs-/Parallelbetrieb einrechnen.
- Storage-Modelle zwischen Google Workspace und Microsoft 365 sind strukturell unterschiedlich und dürfen nicht 1:1 gleichgesetzt werden.

## Ziel des Tools

Vergleich der Total Cost of Ownership von Google Workspace und Microsoft 365 über 3 Jahre inklusive Migration, Schulung und Lizenzdifferenz.

## Gewünschte Ergebnis-Kategorien

1. `✅ Microsoft 365 wirtschaftlich günstiger`
2. `🟡 Wirtschaftlich ähnlich – strategische Faktoren entscheiden`
3. `🔵 Google bleibt kurzfristig günstiger`
4. `🟠 Migration kostet kurzfristig mehr, rechnet sich aber später`
5. `🔴 Mapping / Anforderungen manuell prüfen`

## Kernfunktionen

- Auswahl aktueller Google-Workspace-Lizenz
- Mapping auf passende M365-Zielpläne
- TCO-Vergleich über 36 Monate
- Einbezug von Migrations-, Schulungs- und Change-Kosten
- Break-Even- und Delta-Darstellung
- Empfehlung nach Unternehmensgröße, Sicherheitsanspruch und Zielbild

## Eingaben

### Quellsystem

- Workspace-Plan
- Mitarbeiterzahl
- Zusatzfunktionen / Add-ons
- Google-Speicher-/Meeting-/AI-Niveau grob einschätzen

### Zielsystem

- Ziel-M365-Plan oder automatische Empfehlung
- Zusatzprodukte wie Teams Phone, Copilot, Security, Power Platform optional

### Einmalkosten

- Migrationskosten
- Schulungskosten pro User
- optionaler Admin-Mehraufwand in der Einführungsphase
- optionaler Parallelbetrieb / Hypercare

## Ausgaben

- 3-Jahres-TCO je Plattform
- einmalige vs. laufende Kosten
- Break-Even und Kostendifferenz
- Management-Empfehlung mit Kurzfazit
- Hinweis auf funktionsseitige Mehrwerte / Lücken

## Entscheidungslogik

### 1. Quellplan auf Zielplan mappen

- Business Starter / Standard / Plus / Enterprise
- Berücksichtigung von Security-, Storage- und KI-Anforderungen

### 2. Kostenblöcke trennen

- laufende Lizenzkosten
- einmalige Projektkosten
- optionale Change-/Support-Puffer

### 3. Ergebnis interpretieren

- nur Preisvorteil
- Preisvorteil + Funktionsgewinn
- Mehrkosten mit strategischem Mehrwert

## Bewertungsmodell

- `Lizenzkosten über 36 Monate`
- `Einmalkosten Migration`
- `Schulung / Adoption`
- `Admin-Mehrbedarf in Phase 1`
- `optionaler Parallelbetrieb`

## Benötigte Daten

### 1. `google_workspace_plans.json`

- Planname
- Listenpreis / Region
- Storage-Modell
- zentrale Features

### 2. `m365_target_plans.json`

- Planname
- Zielsegmente
- Preis
- relevante Mehrwerte

### 3. `workspace_to_m365_mapping.json`

- Standard-Mappings
- Abweichungen je Use Case
- Warnfälle

### 4. `migration_defaults.json`

- Projektkosten-Spannen
- Schulungsaufwand
- Change-Puffer

## Verifizierte Weblinks / Quellenbasis

1. **Google Workspace pricing**  
	https://workspace.google.com/pricing.html

2. **Perform an automated Google Workspace migration to Microsoft 365 or Office 365 in EAC**  
	https://learn.microsoft.com/en-us/exchange/mailbox-migration/automated-migration-neweac

3. **Microsoft 365 and Office 365 email migration performance and best practices**  
	https://learn.microsoft.com/en-us/exchange/mailbox-migration/office-365-migration-best-practices

4. **Microsoft 365 Business Plans**  
	https://aka.ms/M365BusinessPlans

5. **Microsoft 365 Enterprise Plans**  
	https://aka.ms/M365EnterprisePlans

## Tool-Flow

### Schritt 1 – Ist-Situation erfassen

- Google-Plan
- Nutzerzahl
- Zusatzbedarf

### Schritt 2 – M365-Zielbild bestimmen

- empfohlene Zielpläne
- Zusatzprodukte optional

### Schritt 3 – 3-Jahres-TCO vergleichen

- laufende Kosten
- Migrationskosten
- Break-Even

## UI/UX nach `cms-m365lic`

- Vergleichsansicht mit zwei Panels
- KPI-Karten für `3-Jahres-Kosten`, `Delta`, `Break-Even`, `Projektkosten`
- Chart für kumulierte Kostenentwicklung
- Zusatzbox `Lizenzpreis ≠ Gesamtwirtschaftlichkeit`

## Ergebnislogik / Textbausteine

### `Microsoft 365 günstiger`

- laufende und Gesamtkosten unter Google-Niveau
- oder nach definierter Zeit günstiger

### `Strategischer Umstieg trotz Mehrkosten`

- Microsoft-Kosten höher, aber klarer Mehrwert bei Security, Admin, Integration oder AI

### `Manuelle Prüfung nötig`

- ungewöhnlicher Google-Add-on-Mix oder sehr spezielle Enterprise-Anforderungen

## Benötigte Bausteine / Funktionen

- `map_workspace_to_m365_plans()`
- `calculate_workspace_tco()`
- `calculate_m365_tco()`
- `compare_workspace_m365_tco()`
- `build_workspace_migration_assumptions()`
- `render_workspace_tco_page()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if workspace_plan_segment === 'enterprise' && special_controls_required === true => prefer_enterprise_targets`
- `if migration_cost_share > threshold && monthly_delta_small === true => no_short_term_break_even`
- `if google_addons_unknown === true => manual_review`
- `if user_count > 300 && workspace_business_plan_selected === true => show_plan_scope_warning`

## Admin / Pflege

- Pflege beider Preiswelten
- Pflege typischer Migrationsannahmen
- Quellenstand für beide Hersteller
- regionale Preis- und Währungsvarianten pflegen

## SEO- und Content-Bausteine

### Fokus-Keywords

- `google workspace zu microsoft 365 kosten`
- `google workspace m365 tco`
- `google workspace migration kosten`
- `m365 statt google workspace`

### Empfohlene FAQ-Blöcke

- Wie migriert man von Google Workspace zu Microsoft 365?
- Was kostet die Umstellung über 3 Jahre wirklich?
- Welche Daten können nativ migriert werden?
- Wann rechnet sich Microsoft 365 gegenüber Google Workspace?

## MVP

- zentrale Workspace-Pläne
- 3-Jahres-TCO
- Migrations- und Schulungskosten
- Standard-Mapping auf M365

## Phase 2

- detaillierte Rollenprofile
- PDF-Business-Case
- Lead-CTA für Migrationsworkshop
- regionale Preisprofile und FX-Logik

## Akzeptanzkriterien

- Tool berücksichtigt laufende und einmalige Kosten getrennt.
- Google-/Microsoft-Planmapping ist transparent.
- Migration und Change-Aufwand sind sichtbar eingerechnet.
- Ergebnis liefert eine Management-taugliche Handlungsempfehlung.

## Offene Pflegepunkte

- Google-Preise und Promotions regelmäßig prüfen.
- M365-Zielmapping an neue KI-/Security-Bundles anpassen.
- Add-ons auf beiden Seiten sauber katalogisieren.

## Umsetzung 1.20.0 – 2026-05-17

- Modul `workspace-m365-tco-calculator` unter `/google-workspace-zu-m365-tco` implementiert.
- Engine `CMS_M365CALCULATOR_Workspace_M365_TCO_Calculator` ergänzt.
- Public Template `templates/page-workspace-m365-tco-calculator.php` ergänzt.
- Kataloge `google_workspace_plans.json`, `m365_target_plans.json`, `workspace_to_m365_mapping.json` und `migration_defaults.json` ergänzt.
- Route, Tool-Registry, Katalogloader, Update-Manifest, README, API-, Datenbank-, Hooks- und Modul-Dokumentation synchronisiert.
