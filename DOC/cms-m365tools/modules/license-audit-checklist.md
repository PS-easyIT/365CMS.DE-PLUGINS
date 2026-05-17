# Lizenz-Audit-Checkliste

## Modul

- Registry-Key: `license-audit-checklist`
- Route: `/m365-lizenz-audit-checkliste`
- Engine: `CMS_M365CALCULATOR_License_Audit_Checklist`
- Template: `templates/page-license-audit-checklist.php`
- Status: `live`

## Zweck

Interaktive Microsoft-365-Auditliste für Lizenzbestand, Offboarding, Shared Mailboxes, Copilot, Frontline, Speicher, Backup und Renewal. Der Fortschritt wird im Browser gespeichert und kann über die Druckfunktion als PDF abgelegt werden.

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

## Pflege

Neue Auditpunkte werden im Checklisten-Katalog ergänzt. Spezialtool-Verweise werden über `license_audit_deeplinks.json` gesteuert.
