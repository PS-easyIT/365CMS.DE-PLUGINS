# CMS M365 Tools – Dokumentations-Changelog

## 1.22.0 – 2026-05-17

- Power-Platform-Dokumentation um Microsoft Well-Architected-, Security-, ALM-, Performance-, Datenrichtlinien- und Operational-Excellence-Review erweitert.
- API-Dokumentation ergänzt `evaluate_power_platform_best_practices()` und den neuen Ergebnisblock `best_practices`.
- Modul-Dokumentation `modules/power-platform-cost-calculator.md` um neue Eingabefelder, Best-Practice-Score, Prüfpunkte und Quellen erweitert.
- Datenquellenbeschreibung für `power_platform_products.json` und `power_platform_governance_rules.json` auf Best-Practice-Optionen und Microsoft-Learn-Regeln erweitert.

## 1.21.0 – 2026-05-17

- Modul-Dokumentation `modules/power-platform-cost-calculator.md` für den neuen Power Platform Kosten-Kalkulator ergänzt.
- API-, Datenbank-, README- und Hooks-Dokumentation um Route `/power-platform-kosten-kalkulator`, neue Kataloge und Engine-Methoden erweitert.
- Dokumentiert Seeded-Rechte, Premium-/Capacity-/PAYG-Pfade, Connector-Regeln, Dataverse for Teams, Power Pages, Copilot Credits, Requests, Storage und Governance-Treiber.

## 1.20.0 – 2026-05-17

- Modul-Dokumentation `modules/workspace-m365-tco-calculator.md` für den neuen Google Workspace ↔ Microsoft 365 TCO-Rechner ergänzt.
- API-, Datenbank-, README- und Hooks-Dokumentation um Route `/google-workspace-zu-m365-tco`, neue Kataloge und Engine-Methoden erweitert.
- Dokumentiert Planmapping, 3-Jahres-TCO, Migration, Schulung, Change-Aufwand, Hypercare, Parallelbetrieb und Break-even in beide Richtungen.

## 1.19.0 – 2026-05-17

- Modul-Dokumentation `modules/storage-needs-calculator.md` für den neuen M365 Storage-Bedarfs-Rechner ergänzt.
- API-, Datenbank-, README- und Hooks-Dokumentation um Route `/m365-storage-bedarfsrechner`, neue Kataloge und Engine-Methoden erweitert.
- Dokumentiert die getrennte Bewertung von SharePoint-Pool, OneDrive-Quotas, Exchange-Postfächern, Archivbedarf, Wachstum und operativen Microsoft-Grenzen.

## 1.18.0 – 2026-05-17

- Modul-Dokumentation `modules/backup-cost-calculator.md` für den neuen M365 Backup-Kosten-Rechner ergänzt.
- API-, Datenbank-, README- und Hooks-Dokumentation um Route `/m365-backup-kostenrechner`, neue Kataloge und Engine-Methoden erweitert.
- Dokumentiert die Trennung zwischen offizieller Microsoft-Baseline und manuell gepflegten Providervergleichsdaten.

## 1.17.0 – 2026-05-17

- Modul-Dokumentation `modules/copilot-pilot-calculator.md` und API-Dokumentation um Governance-/Preflight-Checkliste, FAQ, aktualisierte Quellen und neue Ergebnisbausteine erweitert.
- Dokumentiert, dass die Public-Seite weiterhin nur berechnet und keine Daten serverseitig speichert.

## 1.16.0 – 2026-05-17

- Dokumentationspfad nach `DOC/cms-m365tools` umbenannt.
- README auf den neuen Plugin-Slug `cms-m365tools` aktualisiert.
- Einzeldokumentation für alle 17 registrierten Public-Module unter `modules/` ergänzt.
- Hinweis zur internen Legacy-Klassenstruktur ergänzt: Public-Slug ist `cms-m365tools`, bestehende PHP-Klassen behalten den bisherigen Präfix.

## 1.15.0 – 2026-05-17

- Detaildokument `LICENSE-AUDIT-CHECKLIST.md` für die Route `/m365-lizenz-audit-checkliste` ergänzt.
- Datenquellen `license_audit_checklist.json`, `license_audit_deeplinks.json` und `audit_pdf_template.json` dokumentiert.
- API-Dokumentation für `CMS_M365CALCULATOR_License_Audit_Checklist` ergänzt.
- README, Datenbank- und Hooks-Dokumentation auf die aktuellen Module bis `1.15.0` erweitert.

## 1.8.0 – 2026-05-16

- Archive Mailbox Rechner, Route `/m365-archive-mailbox-rechner` und GET-only-Design dokumentiert.
- Datenquellen `archive_mailbox_plans.json` und `archive_mailbox_assumptions.json` ergänzt.
- API-Dokumentation für `CMS_M365CALCULATOR_Archive_Mailbox_Calculator` ergänzt.
- Lizenzmatrix und Add-on-Matrix um Mailbox-, Archiv-, SharePoint-, OneDrive-, Intune-, Entra-, Defender- und Purview-Details dokumentiert.

## 1.7.0 – 2026-05-16

- Annual vs. Monthly Commitment Rechner, Route `/m365-jahresvertrag-vs-monatsvertrag` und GET-only-Design dokumentiert.
- Datenquellen `commitment_pricing.json`, `commitment_assumptions.json` und `commitment_channel_notes.json` ergänzt.
- API-Dokumentation für `CMS_M365CALCULATOR_Commitment_Calculator` ergänzt.
- Matrixbeschreibungen auf öffentliche Gesamtübersichten ohne technische ReadOnly-Begriffe angepasst.

## 1.6.0 – 2026-05-16

- ReadOnly Public Sites `/m365-lizenzmatrix` und `/m365-addon-matrix` dokumentiert.
- Datenquellen `readonly_suite_matrix.json` und `readonly_addon_matrix.json` ergänzt.
- API-Dokumentation für `CMS_M365CALCULATOR_ReadOnly_Matrices` ergänzt.
- CSRF-Hinweis für die rein lesenden GET-Matrixseiten ergänzt.

## 1.5.0 – 2026-05-16

- M365 Add-On-Konfigurator, neue Route `/m365-add-on-konfigurator` und Add-on-/Verbrauchskataloge dokumentiert.
- API-Dokumentation für `CMS_M365CALCULATOR_Addon_Configurator` ergänzt.
- GET-only-Hinweis für den Add-On-Konfigurator ohne CSRF-Token-Abhängigkeit ergänzt.

## 1.4.0 – 2026-05-16

- M365-Lizenzvergleich, neue Route `/m365-lizenzvergleich` und Vergleichskataloge dokumentiert.
- API-Dokumentation für `CMS_M365CALCULATOR_License_Comparison` ergänzt.
- PHINIT-Hinweis zum Plugin-eigenen 25px Abstand öffentlicher Seiten zum Theme-Header ergänzt.

## 1.3.0 – 2026-05-16

- Copilot ROI-Rechner, neue Route `/copilot-roi-rechner` und ROI-Kataloge dokumentiert.
- API-Dokumentation für `CMS_M365CALCULATOR_Copilot_ROI_Calculator` ergänzt.
- Datenquellen für Preisannahmen, Readiness-Regeln, Szenarien und Persona-Presets ergänzt.

## 1.2.0 – 2026-05-16

- M365-Lizenzberater, neue Route `/m365-lizenzberater` und JSON-Kataloge dokumentiert.
- Admin-Modulsteuerung und Tabelle `cms_m365calculator_module_settings` ergänzt.
- API-Dokumentation für `CMS_M365CALCULATOR_License_Advisor` und `CMS_M365CALCULATOR_Settings` ergänzt.

## 1.1.0 – 2026-05-16

- Copilot Lizenz-Pflicht-Checker dokumentiert.
- Neue Datenkataloge, API-Methoden und Route `/copilot-lizenz-check` ergänzt.

## 1.0.1 – 2026-05-16

- Dynamische Hub-Landingpage und Tool-Registry dokumentiert.
- Alternative Route `/m365-rechner` ergänzt.

## 1.0.0 – 2026-05-16

- Dokumentation für Initialversion angelegt.
- Routen, Hooks, API und Datenhaltung dokumentiert.
