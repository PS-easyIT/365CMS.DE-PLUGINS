
# Changelog – CMS M365 Calculator

## 1.11.0 – 2026-05-16

- Neues Modul `frontline-worker-license-check` unter `/frontline-worker-lizenz-check` ergänzt.
- Neue JSON-Kataloge `frontline_user_type_matrix.json`, `frontline_plan_matrix.json` und `frontline_industry_presets.json` für Rollenprofile, Gerätemodelle, Planbewertung, Branchenpresets und Preisannahmen ergänzt.
- Engine `CMS_M365CALCULATOR_Frontline_Worker_Check` mit Normalisierung, Frontline-Fit, F1-/F3-/Enterprise-Scoring, Gerätehinweisen, Risikobewertung und Sparpotenzial implementiert.
- Public Template `page-frontline-worker-check.php` im PHINIT-Layout mit Rollenprofil, App-Bedarf, Planvergleich, Quellenstand und druckbarem Ergebnis ergänzt.
- Tool-Registry, Frontend-Route, Plugin-Beschreibung und Update-Metadaten auf `1.11.0` angehoben.

## 1.10.0 – 2026-05-16

- Neues Modul `copilot-pilot-calculator` unter `/copilot-pilot-rechner` ergänzt.
- Neue JSON-Kataloge `copilot_pilot_sizes.json`, `copilot_rollout_templates.json` und `copilot_readiness_checklist.json` für Pilotstufen, Rollout-Zeitpläne und Readiness-Faktoren ergänzt.
- Engine `CMS_M365CALCULATOR_Copilot_Pilot_Calculator` mit Normalisierung, Readiness-Scoring, Pilotgrößenempfehlung, Budgetprüfung, Champion-Bedarf, Timeline und nächsten Schritten implementiert.
- Public Template `page-copilot-pilot-calculator.php` im PHINIT-Layout ergänzt.
- Add-on-Matrix trennt die bisherigen kombinierten Security-/Identity-/Device-Inhalte in eigene Bereiche für Intune, Entra ID, Defender und Purview.
- Version und Update-Metadaten auf `1.10.0` angehoben.

## 1.9.0 – 2026-05-16

- Neues Modul `ai-pack-vs-copilot-pro` unter `/ai-pack-vs-copilot-pro` ergänzt.
- Neue JSON-Kataloge `ai_product_catalog.json`, `ai_use_case_matrix.json` und `ai_dynamic_offers.json` für Produktpfade, Use-Case-Scoring und volatile Angebotslabels ergänzt.
- Engine `CMS_M365CALCULATOR_AI_Product_Comparison` mit Normalisierung, Scoring, Alternativen, Vergleichstabelle, Angebotslabel-Einordnung und nächsten Schritten implementiert.
- Public Template `page-ai-product-comparison.php` im PHINIT-Layout ergänzt.
- Öffentliche Fachtexte der vorhandenen Rechner neutralisiert und nicht-mutierende Public-Formulare auf sharebare Anfrageparameter umgestellt.

## 1.8.0 – 2026-05-16

- Neues Modul `m365-archive-mailbox` als Archive Mailbox Rechner unter `/m365-archive-mailbox-rechner` ergänzt.
- Neue JSON-Kataloge `archive_mailbox_plans.json` und `archive_mailbox_assumptions.json` für Mailboxgrößen, Archivkapazitäten, Auto-expanding Archive, Shared-/Resource-Sonderfälle und Quellen ergänzt.
- Archive-Engine mit Anfrage-Normalisierung, 12-Monats-Projektion, Auto-expanding-Eignung, Hold-/Purview-Hinweisen und Add-on-Kostenschätzung implementiert.
- Lizenzmatrix um konkrete Mailbox-, Archiv-, Shared-Mailbox-, SharePoint-, OneDrive-, Defender-for-Office-365- und Purview-Level erweitert.
- Add-on-Matrix um Intune Plan 2, Intune Suite, Entra Suite, Entra ID Governance, Defender for Endpoint P1/P2, Defender for Identity, Defender for Cloud Apps und neue Purview-Paketgruppe erweitert.

## 1.7.0 – 2026-05-16

- Neues Modul `m365-commitment-calculator` als Annual vs. Monthly Commitment Rechner unter `/m365-jahresvertrag-vs-monatsvertrag` ergänzt.
- Neue JSON-Kataloge `commitment_pricing.json`, `commitment_assumptions.json` und `commitment_channel_notes.json` für Preisannahmen, Schwellenwerte und Kanalhinweise ergänzt.
- Commitment-Engine mit Anfrage-Normalisierung, Monatslaufzeit, Jahreslaufzeit, jährlicher Abrechnung und Split-Strategie implementiert.
- Matrixseiten sprachlich von technischen Labels bereinigt und als Gesamtübersicht ohne horizontales Scrollen optimiert.
- Add-on-Matrix um Exchange-, SharePoint- und OneDrive-Größen-/Limitdetails erweitert.

## 1.6.0 – 2026-05-16

- Neue Public Site `m365-lizenzmatrix` unter `/m365-lizenzmatrix` ergänzt.
- Neue Public Site `m365-addon-matrix` unter `/m365-addon-matrix` ergänzt.
- JSON-Kataloge `readonly_suite_matrix.json` und `readonly_addon_matrix.json` für statische Vollpaket- und Add-on-Matrizen ergänzt.
- Engine `CMS_M365CALCULATOR_ReadOnly_Matrices` ergänzt, die Kataloge lädt und normalisiert.
- Vollpaket-Matrix listet Business Basic, Business Standard, Business Premium, Microsoft 365 E3 und Microsoft 365 E5 nebeneinander.
- Add-on-Matrix gruppiert Exchange, SharePoint/OneDrive/Backup, Teams/Telefonie, Copilot/KI, Security/Identity/Devices und Power Platform untereinander.

## 1.5.0 – 2026-05-16

- Neues Modul `m365-add-on-konfigurator` als Add-On-Konfigurator unter `/m365-add-on-konfigurator` ergänzt.
- JSON-Kataloge `addon_configurator_addons.json`, `addon_overlap_rules.json` und `consumption_modules.json` ergänzt.
- Konfigurator-Engine `CMS_M365CALCULATOR_Addon_Configurator` mit Prerequisite-Prüfung, Redundanz-Erkennung, automatischen Zusatzpfaden, Upgrade-vs-Add-on-Vergleich und Verbrauchslogik implementiert.
- Public Template `page-addon-configurator.php` mit linkem Konfigurator, rechtem SKU-Summenblock, Status-Tags und Add-on-Tabelle ergänzt.
- Teams Phone, Calling Plans, Resource Accounts, Copilot, Power Platform Premium und Microsoft 365 Backup als Sonderlogiken abgebildet.

## 1.4.0 – 2026-05-16

- Neues Modul `m365-lizenzvergleich` als Lizenz-Vergleichstabelle unter `/m365-lizenzvergleich` ergänzt.
- JSON-Kataloge `plan_comparison_feature_matrix.json`, `plan_comparison_badges.json` und `plan_comparison_notes.json` ergänzt.
- Vergleichsengine `CMS_M365CALCULATOR_License_Comparison` mit GET-Filtern, Spaltenauswahl, Statusmodell, Badges, Sortierung und Differenzmarkierung implementiert.
- Public Template `page-license-comparison.php` mit PHINIT-Komponenten, Feature-Matrix, Plan-Karten und Quellenblock ergänzt.
- Öffentliche Pluginseiten erhalten einen Plugin-eigenen Abstand von 25px zum Theme-Header.

## 1.3.0 – 2026-05-16

- Neues Modul `copilot-roi` als Copilot ROI-Rechner unter `/copilot-roi-rechner` ergänzt.
- ROI-Kataloge `copilot_pricing.json`, `copilot_readiness_rules.json`, `roi_assumptions.json` und `persona_roi_presets.json` ergänzt.
- ROI-Engine mit Readiness-Gate, konservativem/realistischem/optimistischem Szenario, Break-even-Minuten, Payback, Jahres-ROI und ungenutzter Potenzialquote implementiert.
- Public Template `page-copilot-roi.php` mit PHINIT-Komponenten, KPI-Karten, Szenariovergleich, Readiness-Status und 12-Monats-Chart ergänzt.
- Tool-Registry und Frontend-Routing um das Live-Modul `copilot-roi` erweitert.

## 1.2.0 – 2026-05-16

- Neues Modul `m365lic` als M365-Lizenzberater unter `/m365-lizenzberater` ergänzt.
- Alte `cms-m365lic`-Paket-, Feature-, Persona- und Preisdaten in JSON-Kataloge für das modulare Plugin migriert.
- Lizenzberater-Engine für bis zu fünf Nutzergruppen, Basislizenz-Empfehlungen, Add-ons, Alternativen, Kosten und Warnhinweise ergänzt.
- Adminbereich um pro-Modul-Steuerung für Sichtbarkeit, Status, Priorität, Titel und Beschreibung erweitert.
- Installer-Tabelle `m365calculator_module_settings` ergänzt.
- Landingpage und öffentliche Rechnerseiten auf PHINIT-kompatible 1200px Contentbreite und klarere Kartenstruktur angepasst.

## 1.1.0 – 2026-05-16

- Neues Modul `copilot-lizenz-check` als Copilot Lizenz-Pflicht-Checker ergänzt.
- Copilot-Eligibility-Matrix, technische Prerequisite-Regeln und Upgrade-Pfade als JSON-Kataloge ergänzt.
- Checker-Logik trennt Basislizenz, Copilot Chat, technische Readiness, gemischte Tenants und Upgrade-Pfade.
- Public Template `page-copilot-license-check.php` mit PHINIT-Komponenten, Readiness-Status und Quellenblock ergänzt.
- Hub-Registry um das neue Live-Modul mit Inline-SVG-Icon erweitert.
- Gemeinsames Rechner-JavaScript für mehrere Module verallgemeinert.

## 1.0.1 – 2026-05-16

- Hub-Landingpage `templates/landing.php` für alle registrierten Rechner-Module ergänzt.
- Tool-Registry auf `register()` mit `key`, `title`, `description`, `icon`, `url`, `category` und `status` umgestellt.
- Shared-Mailbox-Modul als selbstregistrierter `live`-Eintrag eingebunden.
- Inline-SVG-Icon-Helper mit Start-Icons für Mailbox, Lizenz, Calculator, Security, Storage und ROI ergänzt.
- Grid-CSS nach `assets/css/style.css` ausgelagert und Landingpage-Assets selektiv geladen.

## 1.0.0 – 2026-05-16

- Neues Plugin `cms-m365calculator` als modulare Microsoft-365-Rechner-Toolbox angelegt.
- Erstes Modul `shared-mailbox-vs-lizenz` implementiert.
- Entscheidungslogik für Shared Mailbox ohne Lizenz, Zusatzlizenz, Grenzfall, User-Mailbox und Microsoft 365 Group ergänzt.
- PHINIT-konformes Public Template mit `phinit-*` Basis-Komponenten erstellt.
- JSON-Kataloge für Regeln, Szenarien, Lizenzmatrix und Preisannahmen ergänzt.
