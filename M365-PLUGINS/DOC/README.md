# CMS M365 Tools – Dokumentation

## Überblick

`cms-m365tools` ist eine modulare Microsoft-365-Rechner- und Tool-Box für 365CMS. Produktive Module sind der **All-Module-Best-Practice-Kompass**, der **Power Platform Kosten-Kalkulator mit Well-Architected-Review**, der **Google Workspace ↔ Microsoft 365 TCO-Rechner**, der **M365 Storage-Bedarfs-Rechner**, der **M365 Backup-Kosten-Rechner**, die **Lizenz-Audit-Checkliste**, der **Microsoft-Preiserhöhung-Tracker**, der **Teams Phone-Lizenz-Berater**, der **On-Premise Exchange zu Exchange Online ROI-Rechner**, der **Frontline Worker Lizenz-Eignung-Check**, der **Copilot Pilot-Phase-Rechner**, der **AI Pack vs. Copilot Pro Vergleich**, der **Archive Mailbox Rechner**, die **M365 Lizenzmatrix**, die **M365 Add-on-Matrix**, der **Annual vs. Monthly Commitment Rechner**, der **M365 Add-On-Konfigurator**, der **M365-Lizenzvergleich**, der **M365-Lizenz-Berater**, der **Copilot ROI-Rechner**, der **Shared-Mailbox vs. Lizenz-Rechner** und der **Copilot Lizenz-Pflicht-Checker**.

## Detaildokumente

| Datei | Zweck |
|---|---|
| `ALL-MODULE-BEST-PRACTICE-REVIEW.md` | Erneute Quellenprüfung, Domänen und konkrete Prüfpunkte für alle 21 Public-Module |
| `LICENSE-AUDIT-CHECKLIST.md` | Fachliche und technische Dokumentation der Route `/m365-lizenz-audit-checkliste` |
| `modules/*.md` | Einzeldokumentation je registriertem Public-Modul |

## Modul-Dokumentation

| Modul | Route | Datei |
|---|---|---|
| Lizenz-Audit-Checkliste | `/m365-lizenz-audit-checkliste` | `modules/license-audit-checklist.md` |
| Power Platform Kosten-Kalkulator | `/power-platform-kosten-kalkulator` | `modules/power-platform-cost-calculator.md` |
| Google Workspace ↔ Microsoft 365 TCO-Rechner | `/google-workspace-zu-m365-tco` | `modules/workspace-m365-tco-calculator.md` |
| M365 Storage-Bedarfs-Rechner | `/m365-storage-bedarfsrechner` | `modules/storage-needs-calculator.md` |
| M365 Backup-Kosten-Rechner | `/m365-backup-kostenrechner` | `modules/backup-cost-calculator.md` |
| Microsoft-Preiserhöhung-Tracker | `/microsoft-preiserhoehung-tracker` | `modules/microsoft-price-tracker.md` |
| Teams Phone-Lizenz-Berater | `/teams-phone-lizenzberater` | `modules/teams-phone-advisor.md` |
| On-Prem Exchange zu Exchange Online ROI | `/exchange-online-roi` | `modules/exchange-online-roi.md` |
| Frontline Worker Lizenz-Eignung-Check | `/frontline-worker-lizenz-check` | `modules/frontline-worker-license-check.md` |
| Copilot Pilot-Phase-Rechner | `/copilot-pilot-rechner` | `modules/copilot-pilot-calculator.md` |
| AI Pack vs. Copilot Pro Vergleich | `/ai-pack-vs-copilot-pro` | `modules/ai-product-comparison.md` |
| M365 Lizenzmatrix | `/m365-lizenzmatrix` | `modules/readonly-suite-matrix.md` |
| M365 Add-on-Matrix | `/m365-addon-matrix` | `modules/readonly-addon-matrix.md` |
| Archive Mailbox Rechner | `/m365-archive-mailbox-rechner` | `modules/archive-mailbox-calculator.md` |
| Annual vs. Monthly Commitment Rechner | `/m365-jahresvertrag-vs-monatsvertrag` | `modules/commitment-calculator.md` |
| M365 Add-On-Konfigurator | `/m365-add-on-konfigurator` | `modules/addon-configurator.md` |
| M365-Lizenzvergleich | `/m365-lizenzvergleich` | `modules/license-comparison.md` |
| M365-Lizenz-Berater | `/m365-lizenzberater` | `modules/license-advisor.md` |
| Copilot ROI-Rechner | `/copilot-roi-rechner` | `modules/copilot-roi.md` |
| Shared-Mailbox vs. Lizenz-Rechner | `/shared-mailbox-vs-lizenz` | `modules/shared-mailbox.md` |
| Copilot Lizenz-Pflicht-Checker | `/copilot-lizenz-check` | `modules/copilot-license-check.md` |

## Public Routes

| Route | Zweck |
|---|---|
| `/m365-tools` | Übersicht aller Rechner-Module |
| `/m365-rechner` | Alternative Hub-Route |
| `/m365-lizenz-audit-checkliste` | Interaktive Microsoft-365-Lizenz-Audit-Checkliste mit Browser-Fortschritt, Druckzusammenfassung und Deep Links |
| `/power-platform-kosten-kalkulator` | Power Apps, Power Automate, Dataverse for Teams, Power Pages, Copilot Studio, Credits, Requests, Storage, PAYG, Capacity sowie Security-, ALM-, Performance-, Datenrichtlinien- und Governance-Reife bewerten |
| `/google-workspace-zu-m365-tco` | Google Workspace und Microsoft 365 inklusive Lizenzkosten, Migration, Schulung, Change-Aufwand, Parallelbetrieb und Break-even vergleichen |
| `/m365-storage-bedarfsrechner` | SharePoint-Pool, OneDrive-Quotas, Exchange-Postfächer, Archivbedarf, Wachstum und Zusatzspeicherbedarf bewerten |
| `/m365-backup-kostenrechner` | Microsoft-365-Backup-Baseline und Providervergleich für Kosten, Workloads, Retention, Restore-Tiefe und Betriebsmodell |
| `/microsoft-preiserhoehung-tracker` | Microsoft-Preis-, Packaging-, SKU-, Renewal- und Forecast-Ereignisse mit Budgetwirkung bewerten |
| `/teams-phone-lizenzberater` | Teams Phone, PSTN-Modell, Operator Connect, Direct Routing und Calling Plan bewerten |
| `/exchange-online-roi` | Vollkosten-, Break-even- und Migrationspfad-Rechner für On-Prem Exchange zu Exchange Online |
| `/frontline-worker-lizenz-check` | Frontline Worker, F1/F3-Eignung, Mischmodell und Enterprise-Bedarf prüfen |
| `/copilot-pilot-rechner` | Copilot-Pilotgröße, Dauer, Budget, Champions und Readiness planen |
| `/ai-pack-vs-copilot-pro` | AI Pack, Copilot Pro, Copilot Chat, Microsoft 365 Copilot und Spezial-Copilots vergleichen |
| `/m365-lizenzmatrix` | Gesamtübersicht der Microsoft-365-Vollpakete ohne Filter oder Formular |
| `/m365-addon-matrix` | Gesamtübersicht aller Add-on-Bereiche mit Paketen nebeneinander |
| `/m365-archive-mailbox-rechner` | Archive Mailbox Rechner für Archivgröße, Auto-expanding Archive, Shared-Mailbox-Sonderfälle, Hold und Purview-Hinweise |
| `/m365-jahresvertrag-vs-monatsvertrag` | Annual vs. Monthly Commitment Rechner für Monatslaufzeit, Jahresbindung und Split-Strategie |
| `/m365-add-on-konfigurator` | Add-ons, Voraussetzungen, Redundanzen, Upgrade-Alternativen und Verbrauchsprodukte prüfen |
| `/m365-lizenzvergleich` | Filterbare Lizenz-Vergleichstabelle mit Feature-Status und Zusatzdiensten |
| `/m365-lizenzberater` | Lizenzberater mit Gruppen, Add-ons, Kosten und Alternativen |
| `/copilot-roi-rechner` | Copilot ROI, Break-even, Payback und Pilot-/Rollout-Empfehlung |
| `/shared-mailbox-vs-lizenz` | Shared-Mailbox-Entscheidung und Kostenabschätzung |
| `/copilot-lizenz-check` | Copilot-Basislizenz-, Chat- und Technik-Readiness-Check |

## Datenquellen

| Datei | Zweck |
|---|---|
| `copilot_eligibility_matrix.json` | Berechtigte Basispläne nach Segment und Chat-Eligibility |
| `copilot_technical_prerequisites.json` | Technische Mindest- und Readiness-Voraussetzungen |
| `license_upgrade_paths.json` | Pflegewerte für Zielpläne, Preisannahmen und Upgrade-Pfade |
| `license_advisor_plans.json` | Migrierte Basislizenz-Kataloge aus `cms-m365lic` |
| `license_advisor_addons.json` | Add-ons und Voraussetzungen für Copilot, Phone, Power Platform, Security und Storage |
| `license_advisor_feature_matrix.json` | Feature-Schlüssel, Labels und Bewertungsgewichte |
| `license_advisor_persona_presets.json` | Persona-Presets für Nutzergruppen |
| `license_advisor_commercial_rules.json` | Kommerzielle Leitplanken, Billing-Multiplikatoren und Quellen |
| `copilot_pricing.json` | Copilot-Preis- und Enablement-Pflegewerte |
| `copilot_readiness_rules.json` | ROI-Readiness-Gates, Blocker und Warnungen |
| `roi_assumptions.json` | Standardannahmen, Szenarien, Ramp-up-Kurven und Schwellenwerte |
| `persona_roi_presets.json` | Rollenprofile und Standardannahmen für ROI-Personas |
| `plan_comparison_feature_matrix.json` | Feature-Statusregeln für Lizenzvergleich, Desktop Apps, Zusatzdienste und Copilot-Pfade |
| `plan_comparison_badges.json` | Badge-Texte für Plan-Highlights und Add-on-Hinweise |
| `plan_comparison_notes.json` | Globale und planbezogene Hinweise für die Vergleichstabelle |
| `readonly_suite_matrix.json` | Statische Vollpaket-Matrix für `/m365-lizenzmatrix` |
| `readonly_addon_matrix.json` | Statische Add-on-Bereichsmatrix für `/m365-addon-matrix` |
| `archive_mailbox_plans.json` | Planwerte für Primärmailbox, Archiv, Auto-expanding Archive und Exchange Online Archiving |
| `archive_mailbox_assumptions.json` | Defaults, Limits, Schwellenwerte und Warntexte für den Archive Mailbox Rechner |
| `commitment_pricing.json` | Preisannahmen und Laufzeitfähigkeit für den Commitment Rechner |
| `commitment_assumptions.json` | Defaults, Limits, Labels und Empfehlungsschwellen für Commitment-Simulationen |
| `commitment_channel_notes.json` | Kanal-, Vertrags- und Quellenhinweise zu CSP, MCA und EA |
| `addon_configurator_addons.json` | Add-on-Katalog mit Preisen, Billing-Typen, Prerequisites und Redundanzmerkmalen |
| `addon_overlap_rules.json` | Overlap-, Auto-Add- und Upgrade-Empfehlungsregeln |
| `consumption_modules.json` | Verbrauchs- und Spezialmodule wie Microsoft 365 Backup und no-cost SKUs |
| `microsoft_price_events.json` | Offizielle Microsoft-Preis-, Packaging-, SKU-, Renewal- und Produktlebenszyklus-Ereignisse |
| `microsoft_price_changes.json` | SKU-bezogene Preisänderungen und Delta-Werte für den Preis-Tracker |
| `microsoft_inventory_mapping.json` | SKU-, Segment-, Kanal- und Filter-Mapping für Preis- und Bestandsauswertung |
| `microsoft_price_forecast_rules.json` | Forecast-Szenarien und Planungshinweise für Budgetrunden |
| `m365_best_practice_catalog.json` | Zentrale Zuordnung aller Module zu Review-Domänen, Domänenkontrollen und konkreten Modul-Prüfpunkten für Lizenzierung, Zugriff, Schutz, Servicegrenzen, Speicher, Netzwerk, Copilot, Power Platform und Migration |
| `teams_phone_base_eligibility.json` | Basislizenz-Eignung für Teams Phone und PSTN-Modelle |
| `teams_pstn_model_rules.json` | Bewertungsregeln für Calling Plan, Operator Connect, Direct Routing und Mischmodell |
| `teams_country_availability.json` | Länder- und Verfügbarkeitsannahmen für Teams-Telefonie |
| `teams_voice_providers.json` | Provider- und Architekturhinweise für Voice-Modelle |
| `teams_direct_routing_requirements.json` | Direct-Routing-Voraussetzungen wie SBC, DNS, Zertifikat und Domäne |
| `teams_phone_cost_assumptions.json` | Kostenannahmen für Teams Phone und PSTN-Bausteine |
| `exchange_online_plans.json` | Exchange-Online-Zielpläne, Preise und Kapazitätswerte |
| `onprem_exchange_cost_defaults.json` | On-Prem-Exchange-Kostenannahmen für ROI-Auswertung |
| `exchange_migration_velocity.json` | Migrationspfade, Durchsatzannahmen und Risikoindikatoren |
| `frontline_user_type_matrix.json` | Rollen- und Gerätemodelle für Frontline Worker |
| `frontline_plan_matrix.json` | F1-/F3-/Enterprise-Bewertung und Preisannahmen |
| `frontline_industry_presets.json` | Branchenpresets für Frontline-Auswertung |
| `copilot_pilot_sizes.json` | Pilotgrößen, Laufzeiten und Champion-Annahmen für Copilot |
| `copilot_rollout_templates.json` | Rollout-Templates und Zeitpläne für Copilot-Einführung |
| `copilot_readiness_checklist.json` | Readiness-Faktoren für Copilot-Pilot und Rollout |
| `ai_product_catalog.json` | Produktkatalog für AI Pack, Copilot Pro, Copilot Chat und Spezial-Copilots |
| `ai_use_case_matrix.json` | Use-Case-Scoring für AI-Produktvergleich |
| `ai_dynamic_offers.json` | Dynamische Angebotslabels und volatile Microsoft-KI-Angebote |
| `license_audit_checklist.json` | Audit-Kategorien, Prüfpunkte, Schweregrade, Quellen und Zusammenfassungstexte |
| `license_audit_deeplinks.json` | Triggerbasierte Deep Links zu Spezialrechnern |
| `audit_pdf_template.json` | Abschnitte und Labels für Druck-/PDF-Zusammenfassung |
| `sharepoint_storage_rules.json` | SharePoint-Tenant-Pool, Site-, Datei- und Sync-Limits für den Storage-Bedarfs-Rechner |
| `onedrive_quota_presets.json` | OneDrive-Quota-Presets, Restore-/Papierkorbfristen und Sync-Betriebsempfehlungen |
| `exchange_storage_rules.json` | Exchange-Primärpostfach-, Shared-/Resource-, Archiv- und Auto-expanding-Grenzen |
| `storage_growth_assumptions.json` | Defaults, Wachstum, Puffer, Cleanup-Potenzial, Statuslabels und Planungsgrenzen |
| `microsoft_backup_baseline.json` | Offizielle Microsoft-365-Backup-Baseline für Preis, Workloads, Retention, Restore-Performance, Trust Boundary und Billing |
| `backup_providers.json` | Manuell gepflegte Vergleichsdaten für Backup-Provider und Microsoft-Baseline |
| `backup_comparison_rules.json` | Defaults, Scoring-Gewichte, Empfehlungstexte und FAQ für den Backup-Kosten-Rechner |
| `google_workspace_plans.json` | Google-Workspace-Pläne, Preise, Storage- und Feature-Leitplanken für den TCO-Rechner |
| `m365_target_plans.json` | Microsoft-365-Zielpläne, Preise, Segmente und Funktionsprofile für den TCO-Rechner |
| `workspace_to_m365_mapping.json` | Planmapping, Anforderungsoptionen und Empfehlungskategorien für beide Richtungen |
| `migration_defaults.json` | Migrations-, Schulungs-, Change-, Hypercare-, Parallelbetriebs- und Quellenannahmen |
| `power_platform_products.json` | Produkt-, Preis-, Eingabe- und Best-Practice-Optionsannahmen für Power Apps, Power Automate, Power Pages, Copilot Studio, PAYG, Capacity, Security, ALM und Performance |
| `power_platform_use_cases.json` | Use-Case-Regeln und Empfehlungskategorien für Apps, Flows, RPA, Bots, Websites und Teams-nahe Lösungen |
| `power_platform_connector_rules.json` | Connector-Regeln für Standard, Premium, Custom und On-Premises-Pfade |
| `power_platform_capacity_catalog.json` | Capacity-, Credit-, Request-, Storage-, Process-Mining- und PAYG-Annahmen inklusive Request-Service-Protection und Dataverse-Kapazitätsschwellen |
| `power_platform_governance_rules.json` | Dataverse-for-Teams-, Governance- und Microsoft-Well-Architected-Regeln inklusive Managed Environments, CMK, Lockbox, vNet, Datenrichtlinien, Security, ALM, Operations, Performance, Request Limits und Dataverse Capacity |

## Lizenz-Audit-Checkliste

Die Route `/m365-lizenz-audit-checkliste` bietet eine interaktive Checkliste für Microsoft-365-Lizenzaudits. Sie deckt Identitäten, Lizenzzuweisungen, ehemalige Nutzer, Shared Mailboxes, Inactive Mailboxes, Copilot, Frontline Worker, SharePoint-/OneDrive-Speicher, Microsoft 365 Backup, privilegierte Rollen, Conditional Access mit Pilotplanung, Defender-Mail-Schutz, Nutzungsberichte, M365-Endpoints, Netzwerk-Basiswerte, Teams-Grenzen, Copilot-Datenzugriff, Copilot-Setup, Power-Platform-Requests, Dataverse-Kapazität und Renewal-/Beschaffungsthemen ab.

## All-Module-Best-Practice-Kompass

Der Hub `/m365-tools` lädt ab `1.23.0` den Katalog `m365_best_practice_catalog.json`. Der Katalog bündelt die zuletzt geprüften offiziellen Microsoft-Learn-Quellen und ordnet jedes Public-Modul den passenden Review-Domänen zu. Ab `1.26.0` enthält er zusätzlich Domänenkontrollen und konkrete Prüfpunkte je Modul. Dadurch ist bereits auf der Landingpage sichtbar, welche Module Lizenzkosten, Zugriff, Schutz, Servicegrenzen, Speicher/Backup, Netzwerk/Performance, Copilot/KI, Power Platform oder Migration/Betrieb berühren und welche fachlichen Punkte aktuell geprüft werden sollten.

Die erneute Quellenrunde berücksichtigt insbesondere Microsoft-365-Endpunkte und Netzwerkplanung, Entra Conditional Access Planning, Defender for Office 365 Deployment, Copilot Licensing/Requirements/Setup, Exchange-/SharePoint-/Teams-Grenzen, Microsoft 365 Backup sowie Power Platform API Request Limits und Dataverse Capacity. Die vollständige Modulabdeckung steht in `ALL-MODULE-BEST-PRACTICE-REVIEW.md`.

Ab `1.24.0` ist die Landingpage zusätzlich als ruhige Übersichtsseite aufgebaut: Intro-Zone, Kennzahlen, Kategorie-Schnellnavigation, Querschnittsreview und reduzierte Tool-Cards mit klarer Leseführung.

Der Fortschritt wird im Browser gespeichert. Die Zusammenfassung kann über die Druckfunktion als PDF abgelegt werden. Fachliche Details stehen in `LICENSE-AUDIT-CHECKLIST.md`.

## Admin-Steuerung

Der Adminbereich kann je Modul Sichtbarkeit, Status, Priorität, öffentlichen Titel und Beschreibung überschreiben. Die Werte werden in `cms_m365tools_module_settings` gespeichert und beim Rendern der Registry angewendet. Vorhandene Werte aus der früheren Tabelle `cms_m365calculator_module_settings` werden beim Installer-Lauf migriert. Ab `1.24.1` ist die Migration tolerant gegenüber älteren Tabellenständen und ergänzt fehlende Settings-Spalten automatisch.

Ab `1.25.0` bekommt jedes Registry-Modul genau einen eigenen Unterpunkt unter `M365 Tools`. In dieser Unterseite werden die weiteren Einstellungen per Tabs organisiert: Übersicht, Anzeige, Preise & Annahmen, Workflow sowie Daten & Regeln. Preis-, Workflow- und Datenwerte landen in `cms_m365tools_module_options`, sodass Preisanpassungen, Review-Zyklen, Owner, Quellenstand und interne Änderungsvermerke je Modul gepflegt werden können. Ab `1.26.0` ergänzt der Daten-Tab Quellenprofil, Endpoint-/Netzwerkpfad, Schutz-/Datenzugriff und Servicegrenzen-/Kapazitätsreview; Copilot- und Power-Platform-Module erhalten zusätzliche Spezialfelder.

## Designvorgaben

Das Plugin nutzt PHINIT-konforme Public-Komponenten und vermeidet statische Inline-Styles, große Gradients, Glassmorphism oder KI-Optik. Die Hub-Landingpage rendert Module ausschließlich aus der Tool-Registry. Öffentliche Pluginseiten setzen einen Plugin-eigenen Abstand zum Theme-Header über PHINIT-Tokens. Ab `1.24.0` sind Icons, Badges, Fortschrittsbalken, Chart-Balken, Tabellenlabels und Statuszustände bewusst zurückhaltender gestaltet: keine bunten Icon-Kacheln, keine übergroße Pill-Optik, keine vollflächig eingefärbten Statuskarten und reduzierte Schriftgewichte für eine erfahrene, gewachsene Website-Anmutung.
