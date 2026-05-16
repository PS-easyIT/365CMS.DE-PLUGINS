# Lizenz-Audit-Checkliste

- **Priorität:** mittel bis hoch
- **Datenquelle:** strukturierte Audit-Module + offizielle Admin-/Lizenz-/Mailbox-/Storage-Regeln + LocalStorage/PDF
- **Aufwand:** niedrig bis mittel
- **SEO-Potenzial:** ★★
- **Empfohlener Slug:** `/m365-lizenz-audit-checkliste`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Eine interaktive Schritt-für-Schritt-Checkliste für das M365-Lizenzaudit über Entra ID und das Admin Center – inklusive Speicherstand im Browser, PDF-Zusammenfassung und Deep Links in die spezialisierten Rechner aus diesem Ordner.

## Verifizierte Microsoft-Leitplanken

### Lizenzzuweisungen müssen direkt oder gruppenbasiert sauber geprüft werden

- Im Microsoft 365 Admin Center werden Lizenzen entweder direkt oder gruppenbasiert zugewiesen.
- Fehlerquellen sind u. a. fehlende Lizenzen, ungültige Usage Location oder Konflikte in Serviceplänen.

### Inaktive und ehemalige Nutzer brauchen einen definierten Pfad

- Beim Entfernen von Lizenzen gelten Datenfolgen für Exchange und OneDrive.
- Ehemalige Mitarbeitende können – je nach Fall – über Shared Mailbox, Inactive Mailbox oder vollständiges Löschen behandelt werden.

### Shared Mailbox, Archiv und Hold sind typische Audit-Hebel

- Shared Mailboxes sind bis 50 GB ohne Lizenz möglich, darüber hinaus oder bei Archiv/Hold greifen Zusatzregeln.
- Archivierungs- und Hold-Anforderungen beeinflussen den optimalen Lizenzpfad deutlich.

### Copilot, Frontline, Storage und Backup gehören ins moderne Audit dazu

- Copilot braucht berechtigte Basislizenzen und technische Voraussetzungen.
- Frontline-Lizenzen sind für bestimmte Nutzertypen gedacht, nicht als generischer Billigpfad.
- SharePoint-Storage folgt der Formel `1 TB + 10 GB pro Lizenz`.
- Microsoft 365 Backup deckt OneDrive, SharePoint und Exchange in einem eigenen Kostenmodell ab.

## Ziel des Tools

Eine interaktive Schritt-für-Schritt-Checkliste für das M365-Lizenzaudit über Entra ID und das Admin Center – inklusive Speicherstand im Browser und PDF-Zusammenfassung.

## Gewünschte Ergebnis-Kategorien

1. `✅ Basis-Audit abgeschlossen`
2. `🟡 Erweiterte Maßnahmen offen`
3. `🔵 Tiefgehendes Audit empfohlen`
4. `🟠 Einsparpotenzial identifiziert`
5. `🔴 Compliance-/Lizenzrisiken gefunden`

## Kernfunktionen

- interaktive Audit-Schritte mit Häkchen
- Fortschrittsanzeige und LocalStorage-Speicherung
- Abschlusszusammenfassung
- PDF-Export der offenen und erledigten Punkte
- CTA für professionelles Lizenz-Audit
- Deep Links in passende Spezialtools

## Inhalte / Schritte

### 1. Identitäten & aktive Konten

- aktive vs. ungenutzte Konten prüfen
- Lösch- / Restore-Fristen prüfen
- gruppenbasierte Lizenzierung erkennen

### 2. Lizenzzuweisungen & Überschneidungen

- direkte vs. gruppenbasierte Zuweisungen prüfen
- Errors & Issues prüfen
- Add-ons und Doppelkäufe identifizieren

### 3. Mailbox- und Aufbewahrungspfade

- Shared-Mailbox-Potenziale prüfen
- Inactive-Mailbox-Fälle prüfen
- Archivierungs- und Hold-Lage prüfen

### 4. Nutzersegmentierung

- Frontline-Kandidaten identifizieren
- High-End-Pläne auf Unterauslastung prüfen
- Copilot-Zielgruppen und Voraussetzungen prüfen

### 5. Infrastrukturkostenhebel

- SharePoint-Storage-Lage prüfen
- Backup-Abdeckung prüfen
- Vertrags- / Bezugsmodell grob prüfen

## Ausgaben

- Fortschrittsstatus
- offene Maßnahmenliste
- PDF-Audit-Report
- Empfehlungsstufe: Basis, erweitert, tiefgehend
- Linkliste zu Spezialrechnern

## Entscheidungslogik

### 1. Audit-Module gruppieren

- Identität
- Lizenzen
- Mail / Retention
- Segmentierung
- Storage / Backup / Einkauf

### 2. Funde priorisieren

- Einsparung
- Risiko
- Compliance
- Umsetzungsaufwand

### 3. Bericht bauen

- erledigt
- offen
- kritisch
- an Spezialtool delegieren

## Bewertungsmodell

- `Quick Wins`
- `monetäres Potenzial`
- `Compliance-Risiko`
- `Umsetzungsaufwand`

## Benötigte Daten

### 1. `license_audit_checklist.json`

- Kategorien
- Fragen
- Hinweise
- Schweregrad

### 2. `license_audit_deeplinks.json`

- verknüpfte Spezialtools
- Trigger-Regeln für Verweise

### 3. `audit_pdf_template.json`

- Titel
- Zusammenfassungsblöcke
- CTA-Elemente

## Verifizierte Weblinks / Quellenbasis

1. **Assign or unassign licenses for users in the Microsoft 365 admin center**  
	https://learn.microsoft.com/en-us/microsoft-365/admin/manage/assign-licenses-to-users

2. **Delete a user from your organization**  
	https://learn.microsoft.com/en-us/microsoft-365/admin/add-users/delete-a-user

3. **About shared mailboxes in Microsoft 365**  
	https://learn.microsoft.com/en-us/microsoft-365/admin/email/about-shared-mailboxes

4. **Create and manage inactive mailboxes**  
	https://learn.microsoft.com/en-us/purview/create-and-manage-inactive-mailboxes

5. **License options for Microsoft 365 Copilot**  
	https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-licensing

6. **SharePoint limits**  
	https://learn.microsoft.com/en-us/office365/servicedescriptions/sharepoint-online-service-description/sharepoint-online-limits

7. **Overview of Microsoft 365 Backup**  
	https://learn.microsoft.com/en-us/microsoft-365/backup/backup-overview

8. **Understand frontline worker user types and licensing**  
	https://learn.microsoft.com/en-us/microsoft-365/frontline/flw-licensing-options?view=o365-worldwide

## Tool-Flow

### Schritt 1 – Audit starten

- Unternehmen / Tenant-Kontext
- Audit-Tiefe wählen

### Schritt 2 – Module durchgehen

- Häkchen setzen
- Notizen ergänzen
- Fortschritt lokal speichern

### Schritt 3 – Ergebnis zusammenfassen

- offene Punkte
- kritische Risiken
- empfohlene Spezialtools

## UI/UX nach `cms-m365lic`

- Hero + Fortschritts-KPI
- gruppierte Checklisten-Cards
- sticky Fortschrittsleiste
- PDF-CTA am Ende
- Ergebnisbox `Nächster bester Schritt`

## Ergebnislogik / Textbausteine

### `Basis-Audit abgeschlossen`

- Identitäten und Hauptlizenzpfade geprüft

### `Erweitertes Audit empfohlen`

- Hinweise auf Shared-Mailbox-, Copilot-, Storage- oder Backup-Hebel entdeckt

### `Tiefgehendes Audit nötig`

- mehrere Compliance-, Retention- oder Vertragsfragen offen

## Benötigte Bausteine / Funktionen

- `load_license_audit_checklist()`
- `save_license_audit_progress_local()`
- `build_license_audit_summary()`
- `score_license_audit_findings()`
- `render_license_audit_page()`
- `export_license_audit_pdf()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if inactive_users_found === true => suggest_tool('18-inaktive-user-sparpotenzial-rechner')`
- `if frontline_candidates_found === true => suggest_tool('19-frontline-worker-lizenz-eignung-check')`
- `if copilot_interest_found === true => suggest_tool('07-copilot-lizenz-pflicht-checker')`
- `if sharepoint_storage_risk_found === true => suggest_tool('13-sharepoint-storage-limit-rechner')`
- `if backup_gap_found === true => suggest_tool('12-m365-backup-kosten-rechner')`

## Admin / Pflege

- Pflege der Audit-Schritte
- Pflege der PDF-Vorlage
- Pflege der CTA-Texte
- Pflege der Deep-Link-Logik zu Spezialtools

## SEO- und Content-Bausteine

### Fokus-Keywords

- `m365 lizenz audit checkliste`
- `microsoft 365 lizenzaudit`
- `m365 lizenzkosten prüfen`
- `m365 ungenutzte lizenzen finden`

### Empfohlene FAQ-Blöcke

- Wie führt man ein Microsoft-365-Lizenzaudit durch?
- Welche Quick Wins gibt es bei Lizenzen?
- Welche Risiken entstehen beim falschen Offboarding?
- Welche Bereiche sollte ein modernes Lizenzaudit abdecken?

## MVP

- interaktive Checkliste
- LocalStorage
- PDF-Zusammenfassung
- Deep Links zu Spezialtools

## Phase 2

- optionale E-Mail-Zusendung
- Auditscore
- rollenbasierte Varianten für IT / Einkauf / Management
- CSV-Import aus Inventardaten

## Akzeptanzkriterien

- Tool deckt Identitäten, Zuweisungen, Mail/Retention, Segmentierung und Infrastrukturhebel ab.
- Fortschritt bleibt lokal gespeichert.
- Abschlussbericht liefert klare nächste Schritte.
- Spezialrechner werden kontextabhängig vorgeschlagen.

## Offene Pflegepunkte

- Checklistenmodule regelmäßig an neue Lizenz- und Produktänderungen anpassen.
- Auditscore in Phase 2 kalibrieren.
- PDF-Vorlage auf Management- und Technik-Sicht zuschneiden.