# Tenant-Tenant-Migration-Kosten-Schätzer

- **Priorität:** mittel bis hoch
- **Datenquelle:** Microsoft-First-Party-Migrationsregeln + workloadbasierte Erfahrungswerte + Tool-/Dienstleistungsannahmen
- **Aufwand:** mittel
- **SEO-Potenzial:** ★★
- **Empfohlener Slug:** `/tenant-migration-kosten-schaetzer`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Das Tool schätzt Aufwand und Kosten einer Tenant-zu-Tenant-Migration nach M&A, Carve-out oder Reorganisation – und unterscheidet sauber zwischen Workloads, die Microsoft first-party unterstützt, und solchen, die zusätzliche Workstreams oder Dritttools erfordern.

## Verifizierte Microsoft-Leitplanken

### Cross-Tenant-Mailbox-Migration ist offiziell unterstützt – aber nur mit Zusatzlizenz

- Cross-tenant mailbox migration erfordert eine **Cross Tenant User Data Migration** Lizenz pro User als einmalige Gebühr.
- Ohne diese Lizenz schlägt die Migration fehl.

### Quelle und Ziel müssen technisch sauber vorbereitet sein

- Ziel-Tenant wird zuerst vorbereitet.
- Zieluser müssen als **MailUser** existieren.
- Relevante Attribute wie `ExchangeGUID`, `ArchiveGUID` (falls vorhanden) und `LegacyExchangeDN`/`x500` müssen korrekt gesetzt sein.

### Nicht alles migriert automatisch

- Microsoft nennt für Cross-Tenant-Mailbox-Migration nur benutzersichtbare Mailbox-Inhalte wie E-Mail, Kontakte, Kalender, Aufgaben und Notizen plus Recoverable Items.
- **Teams-Chat-Inhalte** werden nicht cross-tenant migriert.
- **Teams-Meeting-URLs** bleiben nicht gültig und müssen neu erstellt werden.
- **Mailbox-Signaturen** werden nicht migriert.
- **Microsoft 365 Groups** werden von dieser First-Party-Mailbox-Migration nicht unterstützt.

### Holds, Domains und Clouds sind harte Komplexitätstreiber

- Mailboxen auf Hold werden blockiert und migrieren nicht.
- Quell- und Zieltenant können nicht dieselbe Domain gleichzeitig nutzen.
- Cross-cloud tenant-to-tenant migration wird nicht unterstützt.

### Batch- und Timing-Regeln sind relevant

- Microsoft empfiehlt maximal **2.000 Mailboxen pro Batch**.
- Batches sollten idealerweise etwa **zwei Wochen vor Cutover** eingereicht werden.

### OneDrive hat einen eigenen First-Party-Pfad

- Cross-Tenant-OneDrive-Migration wird offiziell unterstützt.
- Es können bis zu **4.000 OneDrive-Konten** im Voraus geplant werden.
- OneDrive-Migration ist **one and done** – keine Delta-/Incremental-Passes.
- Ziel-OneDrive-Sites dürfen **nicht vorab erstellt** sein.
- OneDrive-Holds blockieren die Migration.
- Pro OneDrive gelten Grenzen von **5 TB** bzw. **1 Million Items**.

## Ziel des Tools

Schätzung des Aufwands und der Kosten einer Tenant-zu-Tenant-Migration nach M&A, Carve-out oder Reorganisation.

## Gewünschte Ergebnis-Kategorien

1. `✅ Größtenteils mit Microsoft-First-Party umsetzbar`
2. `🟡 Hybrid aus Microsoft + Zusatzworkstreams nötig`
3. `🟠 Dritttools / Spezialpartner stark empfohlen`
4. `🔵 Hohe Carve-out-Komplexität`
5. `🔴 Noch nicht migrationsreif`

## Kernfunktionen

- Erfassung von Mailboxen, OneDrive, SharePoint, Teams und Domains
- Aufwandsschätzung in Personentagen
- Schätzung von Tool-Lizenzkosten
- Migrationsdauer in Waves
- Risiko-Hinweise für Identität, Berechtigungen, Domain-Switch und Cutover
- Workload-Coverage-Matrix `first-party` vs. `zusätzlicher Workstream`

## Eingaben

### Organisations- / Projektbasis

- Anzahl User / Mailboxen
- Anzahl OneDrive-Konten
- Anzahl Sites / Teams / Kanäle
- Anzahl Domains und Tenants
- M&A / Carve-out / Konsolidierung

### Komplexitätstreiber

- Holds / Compliance / eDiscovery
- Hybrid vorhanden ja/nein
- Custom Domains zu verschieben ja/nein
- gewünschtes Migrationsfenster
- Toolpräferenz oder Toolklasse

## Ausgaben

- grobe Projektgröße
- geschätzte Dauer
- Toolkosten
- Personentage und empfohlene Teamgröße
- Risikostufe
- Workload-Coverage-Matrix mit Warnhinweisen

## Entscheidungslogik

### 1. Workloads klassifizieren

- `Mailbox / OneDrive` mit Microsoft-First-Party-Pfad
- `SharePoint / Teams / Gruppen / Chat / Berechtigungen` als Zusatz- oder Dritttool-Workstream

### 2. Identity- und Domain-Komplexität bewerten

- MailUser-Vorbereitung
- GUID-/x500-/Alias-Themen
- Domain Cutover / Coexistence

### 3. Wellen und Risiken ableiten

- Pilot
- Waves
- Cutover
- Nachlauf / Cleanup

## Bewertungsmodell

- `Workload-Faktor`
- `Identity-/Domain-Faktor`
- `Compliance-Faktor`
- `Zeitrahmen-Faktor`
- `Tool-/Partner-Faktor`

## Benötigte Daten

### 1. `tenant_migration_workload_matrix.json`

- Workload
- native Unterstützung
- Zusatzworkstream nötig ja/nein
- Risikohinweis

### 2. `tenant_migration_effort_defaults.json`

- PT je Workload
- Zuschläge bei Multi-Domain / Hold / Hybrid
- Wave-Defaults

### 3. `cross_tenant_license_catalog.json`

- Migration Add-on
- Ziel-Lizenzvoraussetzungen

## Verifizierte Weblinks / Quellenbasis

1. **Cross-tenant mailbox migration**  
	https://learn.microsoft.com/en-us/microsoft-365/migration/cross-tenant-mailbox-migration?view=o365-worldwide

2. **Cross-tenant OneDrive migration**  
	https://learn.microsoft.com/en-us/microsoft-365/migration/cross-tenant-onedrive-migration?view=o365-worldwide

3. **Microsoft 365 and Office 365 email migration performance and best practices**  
	https://learn.microsoft.com/en-us/exchange/mailbox-migration/office-365-migration-best-practices

4. **About shared mailboxes in Microsoft 365**  
	https://learn.microsoft.com/en-us/microsoft-365/admin/email/about-shared-mailboxes

## Tool-Flow

### Schritt 1 – Scope erfassen

- Workloads
- Userzahl
- Domains

### Schritt 2 – native vs. zusätzliche Pfade trennen

- First-Party machbar
- Dritttool / Zusatzprojekt nötig

### Schritt 3 – Aufwand und Risiko schätzen

- Personentage
- Dauer
- Risikofarbe

## UI/UX nach `cms-m365lic`

- mehrstufiger Assistent
- Ergebnis mit Projektsteckbrief
- KPI-Karten für `Tage`, `Kosten`, `Risiko`, `Dauer`
- Coverage-Tabelle `Microsoft nativ` vs. `Zusatzworkstream`

## Ergebnislogik / Textbausteine

### `Größtenteils first-party`

- Schwerpunkt auf Mailboxen und OneDrive
- geringe Sonderfälle

### `Hybrid / Zusatzworkstreams nötig`

- Teams, Gruppen, Berechtigungen oder Domainwechsel erhöhen Projekttiefe deutlich

### `Noch nicht migrationsreif`

- Holds, Identitätsmapping oder Zielobjekte sind nicht sauber vorbereitet

## Benötigte Bausteine / Funktionen

- `estimate_tenant_migration_scope()`
- `estimate_tenant_migration_effort()`
- `estimate_tenant_migration_tool_costs()`
- `build_tenant_migration_risk_score()`
- `build_workload_coverage_matrix()`
- `render_tenant_migration_page()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if cross_tenant_license_missing === true => not_ready`
- `if mailbox_hold_count > 0 => block_or_high_risk`
- `if same_domain_needed_in_both_tenants === true => domain_cutover_warning`
- `if teams_chat_migration_expected === true => show_not_supported_warning`
- `if target_onedrive_exists === true => first_party_onedrive_blocker`
- `if workload_mix_includes_groups_or_complex_permissions === true => partner_tool_bias`

## Admin / Pflege

- Pflege der Erfahrungswerte
- Pflege von Tool-Kategorien
- Pflege der Risikoregeln
- laufende Prüfung der First-Party-Supportgrenzen

## SEO- und Content-Bausteine

### Fokus-Keywords

- `tenant zu tenant migration kosten`
- `m365 carve out migration`
- `cross tenant mailbox migration kosten`
- `m365 tenant migration aufwand`

### Empfohlene FAQ-Blöcke

- Was kostet eine Tenant-zu-Tenant-Migration?
- Was kann Microsoft nativ migrieren – und was nicht?
- Warum sind Holds und Domains so kritisch?
- Wann brauche ich ein Dritttool oder einen Spezialpartner?

## MVP

- Mailbox, OneDrive, SharePoint, Teams als Scope-Felder
- Dauer + Aufwand + Toolkosten
- Coverage-Matrix mit Warnungen

## Phase 2

- separates Cutover-Playbook
- PDF-Projektsteckbrief
- qualifizierte Lead-Anfrage
- tieferes Domain-/Identity-Modul

## Akzeptanzkriterien

- Tool trennt native Microsoft-Pfade sauber von Zusatzworkstreams.
- harte Limitierungen wie Holds, Teams-Chat und M365 Groups werden transparent genannt.
- Aufwand, Toolkosten und Projektdienstleistung werden getrennt dargestellt.
- Ergebnis ist für M&A-/Carve-out-Gespräche verwendbar.

## Offene Pflegepunkte

- SharePoint-/Teams-Sonderpfade weiter vertiefen.
- Dritttoolklassen mit echten Marktprofilen ergänzen.
- neue Microsoft-Features für Cross-Tenant-Migration regelmäßig prüfen.