
# Changelog – CMS M365 Tools

## 1.20.0 – 2026-05-17

- Neues Modul `workspace-m365-tco-calculator` unter `/google-workspace-zu-m365-tco` ergänzt.
- Neue JSON-Kataloge `google_workspace_plans.json`, `m365_target_plans.json`, `workspace_to_m365_mapping.json` und `migration_defaults.json` für Google-Planpreise, M365-Zielpläne, Mapping, Projektkosten, Hypercare, Parallelbetrieb und Migrationsleitplanken ergänzt.
- Engine `CMS_M365CALCULATOR_Workspace_M365_TCO_Calculator` mit Eingabe-Normalisierung, Auto-Mapping, 3-Jahres-TCO, Richtungslogik, Break-even, Delta-Auswertung, Warnungen, nächsten Schritten und Quellenstand implementiert.
- Public Template `page-workspace-m365-tco-calculator.php` im PHINIT-Layout mit Szenarioformular, TCO-KPIs, Plattformvergleich, Kostenentwicklung, Projektannahmen und Quellenblock ergänzt.
- Tool-Registry, Frontend-Route, Katalogloader, Update-Manifest, README, API-, Datenbank-, Hooks- und Modul-Dokumentation synchronisiert.
- Public-Constraint beibehalten: keine serverseitige Speicherung und keine öffentlichen Formular-Prüfhinweise.

## 1.19.0 – 2026-05-17

- Neues Modul `m365-storage-needs-calculator` unter `/m365-storage-bedarfsrechner` ergänzt.
- Neue JSON-Kataloge `sharepoint_storage_rules.json`, `onedrive_quota_presets.json`, `exchange_storage_rules.json` und `storage_growth_assumptions.json` für SharePoint-Pool, OneDrive-Quotas, Exchange-/Archivgrenzen, Wachstumsannahmen, Zusatzspeicher und Quellen ergänzt.
- Engine `CMS_M365CALCULATOR_Storage_Needs_Calculator` mit Eingabe-Normalisierung, SharePoint-/OneDrive-/Exchange-Trennung, Forecast, Puffer, Cleanup-Potenzial, Zusatzspeicherrechnung und Kapazitätsstatus implementiert.
- Public Template `page-storage-needs-calculator.php` im PHINIT-Layout mit Eingabe-Card, Status-Card, Bereichs-KPIs, Leitplanken-Tabelle, Detailwerten und Quellenstand ergänzt.
- Lizenz-Audit-Deep-Link `storage_review` auf den neuen Storage-Bedarfs-Rechner ergänzt.
- Public-Constraint beibehalten: keine serverseitige Speicherung und keine öffentlichen Formular-Prüfhinweise.

## 1.18.0 – 2026-05-17

- Neues Modul `m365-backup-cost-calculator` unter `/m365-backup-kostenrechner` ergänzt.
- Neue JSON-Kataloge `microsoft_backup_baseline.json`, `backup_providers.json` und `backup_comparison_rules.json` für offizielle Microsoft-Baseline, manuell gepflegte Providerdaten, Scoring und FAQ ergänzt.
- Engine `CMS_M365CALCULATOR_Backup_Cost_Calculator` mit Eingabe-Normalisierung, geschützter GB-Baseline, Provider-Normalisierung, Kostenranking, Workload-/Retention-/Restore-Bewertung und Empfehlung implementiert.
- Public Template `page-backup-cost-calculator.php` im PHINIT-Layout mit Baseline-KPI, Providervergleich, Detailkarten, FAQ und Quellenstand ergänzt.
- Lizenz-Audit-Deep-Link `backup_review` auf den neuen Backup-Kosten-Rechner umgestellt.
- Public-Constraint beibehalten: keine serverseitige Speicherung und keine öffentlichen Formular-Prüfhinweise.

## 1.17.0 – 2026-05-17

- Modul `copilot-pilot-calculator` unter `/copilot-pilot-rechner` fachlich erweitert.
- Microsoft-Quellenstand für Adoption, Lizenzierung, App-/Netzwerkanforderungen, Setup, Governance, Reporting, Feedback und Daten-/Compliance-Readiness auf `2026-05-17` aktualisiert.
- JSON-Kataloge `copilot_pilot_sizes.json`, `copilot_rollout_templates.json` und `copilot_readiness_checklist.json` um zusätzliche Quellen, Messkriterien, Governance-/Preflight-Punkte und FAQ-Bausteine ergänzt.
- Engine `CMS_M365CALCULATOR_Copilot_Pilot_Calculator` gibt Preflight-Checkliste und FAQ normalisiert an das Public Template aus.
- Public Template `page-copilot-pilot-calculator.php` um Governance-Checkliste vor Pilotstart und FAQ-Bereich ergänzt.
- Public-Constraint beibehalten: keine serverseitige Speicherung, keine öffentlichen Sicherheits-Hinweise und keine Abhängigkeit von Formular-Sicherheitsmeldungen.

## 1.16.0 – 2026-05-17

- Plugin auf `cms-m365tools` umbenannt.
- Plugin-Ordner und Hauptdatei auf `M365-PLUGINS/cms-m365tools/cms-m365tools.php` umgestellt.
- Öffentliche Plugin-URL, Update-Manifest, Plugin-Registry, Landingpage-Titel und Theme-Body-Klasse auf den neuen Slug erweitert.
- Interne Klassen und Legacy-Konstanten bleiben aus Kompatibilitätsgründen mit dem bisherigen Präfix lauffähig.
- Zentrale Dokumentation nach `DOC/cms-m365tools` verschoben und Einzeldokumente für alle 17 Public-Module unter `DOC/cms-m365tools/modules/` ergänzt.

## 1.15.0 – 2026-05-17

- Neues Modul `license-audit-checklist` unter `/m365-lizenz-audit-checkliste` ergänzt.
- Neue JSON-Kataloge `license_audit_checklist.json`, `license_audit_deeplinks.json` und `audit_pdf_template.json` für Auditpunkte, Tool-Deep-Links und druckfreundliche Zusammenfassungen ergänzt.
- Engine `CMS_M365CALCULATOR_License_Audit_Checklist` mit Eingabe-Normalisierung, Priorisierung, Audit-Druckstruktur, Deep-Link-Auswahl und Quellenstand implementiert.
- Public Template `page-license-audit-checklist.php` im PHINIT-Layout mit Rahmenauswahl, interaktiver Checkliste, Browser-Fortschritt, offener/erledigter Zusammenfassung, Detailrechnern und Quellenblock ergänzt.
- Gemeinsames Public-JavaScript und CSS um clientseitige Audit-Fortschrittsverwaltung, Fortschrittsbalken und druckfreundliche Summary erweitert.
- Plugin-Beschreibung, Tool-Registry, Frontend-Route, README und Update-Metadaten auf `1.15.0` angehoben.

## 1.14.0 – 2026-05-17

- Neues Modul `microsoft-price-tracker` unter `/microsoft-preiserhoehung-tracker` ergänzt.
- Neue JSON-Kataloge `microsoft_price_events.json`, `microsoft_price_changes.json`, `microsoft_inventory_mapping.json` und `microsoft_price_forecast_rules.json` für offizielle Microsoft-Preis-, Packaging-, SKU-, Renewal- und Forecast-Daten ergänzt.
- Engine `CMS_M365CALCULATOR_Microsoft_Price_Tracker` mit Filterlogik, Bestandsmapping, Renewal-Bewertung, Preisdelta, Forecast-Trennung und visuellen Chart-Zeilen implementiert.
- Public Template `page-microsoft-price-tracker.php` im PHINIT-Layout mit Filterformular, SKU-Bestand, Budgetwirkung, offiziellem Timeline-Table, Forecast-Bereich und Jahresvergleich-Chart ergänzt.
- Plugin-Beschreibung, Tool-Registry, Frontend-Route, README und Update-Metadaten auf `1.14.0` angehoben.

## 1.13.0 – 2026-05-16

- Neues Modul `teams-phone-advisor` unter `/teams-phone-lizenzberater` ergänzt.
- Neue JSON-Kataloge `teams_phone_base_eligibility.json`, `teams_pstn_model_rules.json`, `teams_country_availability.json`, `teams_voice_providers.json`, `teams_direct_routing_requirements.json` und `teams_phone_cost_assumptions.json` für Teams-Phone-Lizenzbasis, PSTN-Modelle, Länderannahmen, Provider, Direct-Routing-Voraussetzungen und Kostenannahmen ergänzt.
- Engine `CMS_M365CALCULATOR_Teams_Phone_Advisor` mit Normalisierung, Modell-Scoring, Readiness-Bewertung, Add-on-Bedarf, Kostenrahmen, Direct-Routing-Prüfung und Shared-Calling-Erkennung implementiert.
- Public Template `page-teams-phone-advisor.php` im PHINIT-Layout mit Lizenzbasis, PSTN-Modell, Betriebsanforderungen, Modellvergleich, Zusatzbausteinen, Voraussetzungen und Quellenstand ergänzt.
- Plugin-Beschreibung, Tool-Registry, Frontend-Route, README und Update-Metadaten auf `1.13.0` angehoben.

## 1.12.0 – 2026-05-16

- Neues Modul `exchange-online-roi` unter `/exchange-online-roi` ergänzt.
- Neue JSON-Kataloge `exchange_online_plans.json`, `onprem_exchange_cost_defaults.json` und `exchange_migration_velocity.json` für Cloud-Planpreise, On-Prem-Vollkosten, Refresh-Annahmen und Migrationsrichtwerte ergänzt.
- Engine `CMS_M365CALCULATOR_Exchange_Online_ROI_Calculator` mit Vollkostenrechnung, Exchange-Online-Zielkosten, 36-/60-Monats-Break-even, Migrationsfenster, Risikowert und Management-Fazit implementiert.
- Public Template `page-exchange-online-roi.php` im PHINIT-Layout mit Ist-/Soll-Kosten, Delta-Verlauf, Migrationspfad, Planvergleich und Quellenstand ergänzt.
- Plugin-Beschreibung, Tool-Registry, Frontend-Route, README und Update-Metadaten auf `1.12.0` angehoben.

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

- Neues Plugin als modulare Microsoft-365-Rechner-Toolbox angelegt.
- Erstes Modul `shared-mailbox-vs-lizenz` implementiert.
- Entscheidungslogik für Shared Mailbox ohne Lizenz, Zusatzlizenz, Grenzfall, User-Mailbox und Microsoft 365 Group ergänzt.
- PHINIT-konformes Public Template mit `phinit-*` Basis-Komponenten erstellt.
- JSON-Kataloge für Regeln, Szenarien, Lizenzmatrix und Preisannahmen ergänzt.
