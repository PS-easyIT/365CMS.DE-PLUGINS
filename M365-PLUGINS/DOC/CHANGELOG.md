# CMS M365 Tools – Dokumentations-Changelog

## 1.29.2 – 2026-05-17

- Landingpage-Designer-Dokumentation um weitere Ausblendoptionen für Header, Kennzahlen, Kategorie-Köpfe, Review-Beschreibungen, Modultitel-Links, Tool-Buttons und inaktive Modulhinweise ergänzt.
- Button-Dokumentation erweitert: Primär-/Sekundärbutton mit eigenem Ziel sowie Modulbox-Button-Zielmodi.
- Header-Design-Dokumentation um Header-Stil, Ausrichtung, Button-Layout sowie Header- und Button-Farben ergänzt.

## 1.29.1 – 2026-05-17

- Dokumentiert, dass der `Landingpage Designer` ein eigener Admin-Untermenüpunkt ist.
- Designer-Dokumentation um Tabs für Contentheader, Layouts & Boxen, Farben und Sichtbarkeit erweitert.
- Neue Landingpage-Optionen für Seitenbreite, Header-Varianten, Kategorie-Navigation, Modulbox-Layouts, Kartenstil, Dichte, Abschnittsabstände und Farbpalette dokumentiert.
- Public-Anwendung der Designerwerte über CSS-Variablen, Layoutklassen und Sichtbarkeits-Toggles beschrieben.

## 1.29.0 – 2026-05-17

- Admin-Dokumentation um volle Contentbreite, 25px Außenabstand und neutraleres Layout ergänzt.
- Neue globale Tabs `Dienstleister & Kontakt` sowie `Landingpage Designer` dokumentiert.
- Dokumentiert, dass Toolseiten den zentralen Dienstleister-/Kontaktformular-Hinweis vor dem Footer ausgeben.
- Landingpage-Dokumentation um steuerbare Headertexte, Layouts, Boxen, Rundungen und sichtbare Bereiche erweitert.

## 1.28.1 – 2026-05-17

- Adminlayout der Paketpreise dokumentiert: Public-, Member- und Spezialpreise stehen je Paket nebeneinander.
- Hinweis ergänzt, dass der Paketpreisbereich die volle Admincontent-Breite nutzt und der Plugin-Adminbereich 25px oberen Abstand erhält.

## 1.28.0 – 2026-05-17

- Dokumentiert, dass `cms-m365tools` Paketpreise bevorzugt aus dem aktiven `cms-m365lic` Seed-Katalog übernimmt.
- Admin-Dokumentation um dynamische Public-, Member- und Spezialpreisfelder für Basislizenzen und Add-ons ergänzt.
- API-Dokumentation um `m365_package_price_catalog()` und die daraus abgeleiteten Pricing-/Commitment-Kataloge erweitert.
- Admin-Menü-Dokumentation auf kurze Modul-Labels zur Sidebar-Optimierung aktualisiert.

## 1.27.0 – 2026-05-17

- Admin-Dokumentation um zentrale Plugin-Einstellungen, globale Paketpreise sowie Abopreise & Laufzeiten als eigene Unterpunkte ergänzt.
- README, API und Datenbankdokumentation beschreiben die logisch sortierte Admin-Menüstruktur und die Wiederverwendung von `cms_m365tools_module_options` für globale Defaults.
- Best-Practice-Dokumentation um zusätzliche Quellen zu Microsoft-365-Endpoint-Webservice, Endpoint-Change-Management, Copilot-App-/Netzwerkanforderungen, Lizenzzuweisung, Gruppenlizenzierung und Microsoft 365 Backup aktualisiert.
- Lizenz-Audit-Beschreibung um Endpoint-Änderungsprozess und globale Preis-/Laufzeitpflege nachgezogen.

## 1.26.0 – 2026-05-17

- Erneute All-Module-Best-Practice-Prüfung dokumentiert und neue Datei `ALL-MODULE-BEST-PRACTICE-REVIEW.md` für alle 21 Module ergänzt.
- README auf erweiterte Quellenlage zu M365-Endpunkten, Netzwerkplanung, Conditional Access, Defender for Office 365, Copilot Setup, Power-Platform-Requests und Dataverse-Kapazität aktualisiert.
- Datenquellenbeschreibung für `m365_best_practice_catalog.json`, `license_audit_checklist.json`, `power_platform_capacity_catalog.json` und `power_platform_governance_rules.json` nachgezogen.
- Admin-Dokumentation um neue Review-Felder für Quellenprofil, Endpoint-/Netzwerkpfad, Schutz-/Datenzugriff und Servicegrenzen-/Kapazität ergänzt.

## 1.25.0 – 2026-05-17

- Admin-Modulsettings dokumentiert: eigener Unterpunkt je Public-Modul und Tabs für Übersicht, Anzeige, Preise & Annahmen, Workflow sowie Daten & Regeln.
- Neue Tabelle `cms_m365tools_module_options` in der Datenbankdokumentation ergänzt.
- API- und Hooks-Dokumentation um Admin-Konfiguration, Moduloptionen und dynamische Submenüs erweitert.

## 1.24.1 – 2026-05-17

- Installer-Härtung für Legacy-Modulsettings dokumentiert.
- Datenbankdokumentation ergänzt, dass fehlende Spalten in bestehenden Settings-Tabellen automatisch nachgezogen werden.
- README-Hinweis zur spaltenbewussten Migration alter `cms_m365calculator_module_settings`-Tabellen aktualisiert.

## 1.24.0 – 2026-05-17

- Public-Design-Refresh der `cms-m365tools`-Seiten dokumentiert.
- README um die neue Landingpage-Struktur mit Kennzahlen, Kategorie-Schnellnavigation und ruhiger Tool-Card-Anmutung ergänzt.
- Designvorgaben auf die überarbeitete PHINIT-Oberfläche mit reduzierter Pill-Optik, ruhigeren Statuskanten, dezenten Fortschritts-/Chart-Elementen und besserer Scanbarkeit aktualisiert.

## 1.23.0 – 2026-05-17

- Dokumentation für den All-Module-Best-Practice-Kompass ergänzt.
- Neue Datenquelle `m365_best_practice_catalog.json` in README, API und Datenquellenübersicht dokumentiert.
- Lizenz-Audit-Dokumentation um neue Querschnittsprüfpunkte für privilegierte Rollen, Conditional Access, Mail-Schutz, Nutzungsberichte, Netzwerk-Basiswerte, Servicegrenzen, Copilot-Datenzugriff und Wiederherstellungsziele erweitert.
- Dokumentiert, dass der Hub je Modul Review-Domänen aus offiziellen Microsoft-Learn-Quellen anzeigt und weiterhin keine serverseitige Speicherung für Public-Auswertungen benötigt.

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
