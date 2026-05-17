# Lizenz-Audit-Checkliste

## Modul

- Registry-Key: `license-audit-checklist`
- Route: `/m365-lizenz-audit-checkliste`
- Engine: `CMS_M365CALCULATOR_License_Audit_Checklist`
- Template: `templates/page-license-audit-checklist.php`
- Status: `live`
- Version: `1.26.0`

## Zweck

Interaktive Microsoft-365-Auditliste für Lizenzbestand, Offboarding, Shared Mailboxes, Copilot, Frontline, Speicher, Backup, Zugriff, Schutz, Netzwerk, Performance und Renewal. Der Fortschritt wird im Browser gespeichert und kann über die Druckfunktion als PDF abgelegt werden.

## Datenquellen

- `data/license_audit_checklist.json`
- `data/license_audit_deeplinks.json`
- `data/audit_pdf_template.json`

## Ergebnis

- Fortschritt und Auditdruck
- offene und erledigte Prüfpunkte
- priorisierte Schwerpunkte
- Deep Links zu Spezialrechnern
- Quellenstand

## Querschnittsprüfung ab 1.26.0

Die Checkliste enthält zusätzlich die Kategorie `Zugriff, Schutz, Netzwerk und Betrieb` mit Prüfpunkten für:

- privilegierte Rollen, geringstes Recht, Just-in-Time-Zugriff und wiederkehrende Reviews
- Conditional Access mit Pilotgruppe, Report-only-Phase, Notfallkonten, Adminrollen, Standort-/Gerätesignalen und veralteten Anmeldewegen
- Defender-Mail-Schutz, Standard-/Strict-Protection, Safe Links, Safe Attachments, SPF, DKIM, DMARC und Meldeprozess für Benutzer
- Microsoft-365-Nutzungsberichte für Lizenz-, Speicher-, Teams-, Copilot- und Mailbox-Auswertung
- Microsoft-365-Endpoints, Netzwerkpfad, lokale Namensauflösung, lokalen Internetausstieg und Performance-Basiswerte
- SharePoint-, OneDrive-, Sync- und Teams-Grenzen vor Migration, Wachstum oder Telefonieplanung
- Copilot-Datenzugriff, WSS-Verbindungen, Apps, OneDrive, Teams-Meetingdaten, Datenschutzrahmen, Datenfreigaben und Pilotmetriken
- Power-Platform-Requests, Flow-Profile, nicht-interaktive Identitäten, Dataverse Database/File/Log, Suchindex und Umgebungskapazität
- Backup-Schutzumfang und Wiederherstellungsziele je Workload

## Pflege

Neue Auditpunkte werden im Checklisten-Katalog ergänzt. Spezialtool-Verweise werden über `license_audit_deeplinks.json` gesteuert.
