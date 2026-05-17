# CMS M365 Tools – API

## `CMS_M365CALCULATOR_Catalog`

- `rules()` – lädt Regeln aus `shared_mailbox_rules.json`
- `license_matrix()` – lädt Lizenzprofile aus `mailbox_license_matrix.json`
- `scenarios()` – lädt auswählbare Szenarien
- `pricing()` – lädt Preisannahmen
- `copilot_eligibility_matrix()` – lädt Copilot-Basislizenz- und Chat-Eligibility
- `copilot_prerequisites()` – lädt technische Copilot-Voraussetzungen
- `copilot_upgrade_paths()` – lädt Upgrade- und Preisannahmen
- `copilot_pricing()` – lädt Copilot-Preis- und Enablement-Annahmen
- `copilot_readiness_rules()` – lädt ROI-Readiness-Gates und Sonderfälle
- `roi_assumptions()` – lädt ROI-Defaults, Szenarien, Ramp-up und Schwellenwerte
- `persona_roi_presets()` – lädt Rollenprofile für ROI-Defaults
- `plan_comparison_feature_matrix()` – lädt Feature-Statusregeln für den Lizenzvergleich
- `plan_comparison_badges()` – lädt Badge-Texte für Plan-Highlights
- `plan_comparison_notes()` – lädt globale und planbezogene Vergleichshinweise
- `readonly_suite_matrix()` – lädt die statische Vollpaket-Matrix für `/m365-lizenzmatrix`
- `readonly_addon_matrix()` – lädt die statische Add-on-Matrix für `/m365-addon-matrix`
- `commitment_pricing()` – lädt Preisannahmen für den Annual-vs-Monthly-Rechner
- `commitment_assumptions()` – lädt Defaults, Limits, Labels und Empfehlungsschwellen
- `commitment_channel_notes()` – lädt Kanal-, Quellen- und Vertragsnotizen für CSP, MCA und EA
- `archive_mailbox_plans()` – lädt Planwerte für Primärmailbox, Archiv, Auto-expanding Archive und Exchange Online Archiving
- `archive_mailbox_assumptions()` – lädt Defaults, Limits, Schwellenwerte und Warntexte für den Archive Mailbox Rechner
- `addon_configurator_addons()` – lädt Add-on-Katalog, Billing-Typen, Preise und Prerequisites
- `addon_overlap_rules()` – lädt Redundanz-, Auto-Add- und Upgrade-Regeln
- `consumption_modules()` – lädt Verbrauchs- und Spezialmodule wie Microsoft 365 Backup
- `sharepoint_storage_rules()` – lädt SharePoint-Tenant-Pool, Site-, Datei- und Sync-Limits
- `onedrive_quota_presets()` – lädt OneDrive-Quota-Presets und Betriebsleitplanken
- `exchange_storage_rules()` – lädt Exchange-Primärpostfach-, Archiv- und Auto-expanding-Grenzen
- `storage_growth_assumptions()` – lädt Defaults, Wachstums-, Puffer- und Statusannahmen für den Storage-Bedarfs-Rechner
- `microsoft_backup_baseline()` – lädt offizielle Microsoft-365-Backup-Baseline für Preis, Workloads, Retention, Restore und Billing
- `backup_providers()` – lädt Microsoft-Baseline und manuell gepflegte Providervergleichsdaten
- `backup_comparison_rules()` – lädt Defaults, Scoring-Gewichte, Empfehlungstexte und FAQ für den Backup-Kosten-Rechner
- `google_workspace_plans()` – lädt Google-Workspace-Pläne, Preise, Speicher- und Funktionsleitplanken
- `m365_target_plans()` – lädt Microsoft-365-Zielpläne, Preise, Segmente und Funktionsprofile für den TCO-Rechner
- `workspace_to_m365_mapping()` – lädt Planmapping, Anforderungsoptionen und Empfehlungskategorien für beide Richtungen
- `migration_defaults()` – lädt Migrations-, Schulungs-, Change-, Hypercare- und Parallelbetriebsannahmen
- `power_platform_products()` – lädt Produkt-, Preis- und Optionskatalog für den Power Platform Kosten-Kalkulator
- `power_platform_use_cases()` – lädt Use-Case-Regeln und Empfehlungskategorien
- `power_platform_connector_rules()` – lädt Standard-/Premium-/Custom-/On-Premises-Connectorregeln
- `power_platform_capacity_catalog()` – lädt Capacity-, Credit-, Request-, Storage-, Process-Mining- und PAYG-Annahmen
- `power_platform_governance_rules()` – lädt Dataverse-for-Teams- und Governance-Leitplanken
- `license_advisor_plans()` – lädt Basislizenz-Kataloge
- `license_advisor_addons()` – lädt Add-ons und Prerequisites
- `license_advisor_feature_matrix()` – lädt Featuredefinitionen
- `license_advisor_persona_presets()` – lädt Persona-Presets
- `license_advisor_commercial_rules()` – lädt Billing- und Quellenregeln
- `microsoft_price_events()` – lädt offizielle Preis-, Packaging-, SKU-, Renewal- und Produktlebenszyklus-Ereignisse
- `microsoft_price_changes()` – lädt SKU-bezogene Preisänderungen und Delta-Werte
- `microsoft_inventory_mapping()` – lädt SKU-, Segment-, Kanal- und Filter-Mapping
- `microsoft_price_forecast_rules()` – lädt Forecast-Szenarien für den Preis-Tracker
- `license_audit_checklist()` – lädt Audit-Kategorien und Prüfpunkte
- `license_audit_deeplinks()` – lädt Trigger und Spezialtool-Verweise
- `audit_pdf_template()` – lädt Struktur und Labels der Druckzusammenfassung

## `CMS_M365CALCULATOR_License_Audit_Checklist`

- `default_input()` – Default-Werte für Mandantengröße, Prüftiefe und Audit-Schwerpunkte
- `normalize_input(array $source)` – normalisiert GET-Parameter und Checkbox-Werte
- `tenant_size_options()` – liefert auswählbare Mandantengrößen
- `audit_depth_options()` – liefert Basis-, erweitertes und tiefgehendes Audit
- `context_options()` – liefert Schwerpunkte inklusive Deep-Link-Triggern
- `evaluate(array $input)` – kombiniert Auditkatalog, Priorisierung, Score, Deep Links, Quellen und Druckstruktur
- `load_license_audit_checklist()` – lädt `license_audit_checklist.json`
- `save_license_audit_progress_local(array $input)` – erzeugt den Browser-Speicherschlüssel für den aktuellen Audit-Rahmen
- `build_license_audit_summary(array $input, array $categories, array $deeplinks)` – ermittelt priorisierte Punkte, Gewichtung, Fokus und Trigger
- `score_license_audit_findings(array $summary)` – berechnet Auditdruck, Tonalität und Empfehlungstext
- `export_license_audit_pdf(array $template)` – normalisiert die Druckstruktur aus `audit_pdf_template.json`
- `render_license_audit_page()` – liefert den Template-Pfad für `/m365-lizenz-audit-checkliste`

## `CMS_M365CALCULATOR_License_Advisor`

- `default_input()` – Default-Werte für den Lizenzberater
- `normalize_input(array $source)` – normalisiert POST-Daten für bis zu fünf Gruppen
- `load_license_catalog()` – lädt Basislizenz-Katalog
- `load_addon_catalog()` – lädt Add-on-Katalog
- `load_persona_presets()` – lädt Nutzergruppen-Presets
- `evaluate(array $input)` – erzeugt Empfehlungen, Alternativen, Kosten und Warnungen
- `feature_options()` – liefert Feature-Auswahloptionen für das Template

## `CMS_M365CALCULATOR_License_Comparison`

- `default_filters()` – Default-Werte für GET-Filter, Sortierung und Vergleichsspalten
- `normalize_filters(array $source)` – normalisiert GET-Parameter ohne CSRF-Abhängigkeit
- `load_plan_comparison_matrix()` – lädt Feature-Statusregeln aus `plan_comparison_feature_matrix.json`
- `filter_plan_comparison(array $filters)` – filtert Pläne nach Familie, Suchbegriff und Feature-Pfaden
- `sort_plan_comparison(array $plans, string $sort)` – sortiert nach Preis, Name, Security- oder Copilot-Abdeckung
- `highlight_plan_differences(array $plans, array $features)` – markiert Unterschiede und günstigsten Plan im Side-by-side-Vergleich
- `build_plan_badges(array $plan, array $features)` – erzeugt Plan-Badges wie Desktop Apps, Copilot-Basis, Security-Pfad oder Telefonie per Add-on
- `feature_status(array $plan, string $featureKey, array $feature)` – liefert Status und Hinweis für eine Plan-/Feature-Kombination
- `evaluate(array $filters)` – kombiniert Filter, Planliste, ausgewählte Spalten, Feature-Gruppen, Hinweise und Quellen
- `render_plan_comparison_page()` – liefert den Template-Pfad für das Public Template

## `CMS_M365CALCULATOR_ReadOnly_Matrices`

- `suite_matrix()` – lädt und normalisiert die ReadOnly-Vollpaket-Matrix ohne Benutzereingaben
- `addon_matrix()` – lädt und normalisiert die ReadOnly-Add-on-Matrix ohne Benutzereingaben
- `render_suite_matrix_page()` – liefert den Template-Pfad für `/m365-lizenzmatrix`
- `render_addon_matrix_page()` – liefert den Template-Pfad für `/m365-addon-matrix`

## `CMS_M365CALCULATOR_Addon_Configurator`

- `default_input()` – Default-Werte für Basislizenz, Nutzerzahl, Laufzeit, Strategie, Add-ons und Sonderparameter
- `normalize_input(array $source)` – normalisiert GET-Parameter ohne POST-/CSRF-Abhängigkeit
- `load_addon_catalog()` – lädt `addon_configurator_addons.json`
- `resolve_addon_prerequisites(array $addon, array $plan, array $selectedAddons, array $addonIndex)` – prüft Basis-Tags, Vor-Add-ons und Feature-/Tag-Voraussetzungen
- `detect_redundant_addons(array $addon, array $plan)` – erkennt Doppelkäufe über Planfeatures und Plantags
- `compare_addons_vs_upgrade(array $input, array $basePlan, array $evaluatedAddons)` – bewertet Upgrade-Alternativen gegen den Add-on-Stapel
- `calculate_addon_bundle_total(array $input, array $evaluatedAddons, array $basePlan)` – berechnet Basis-, Add-on-, Monats- und Jahreskosten
- `calculate_consumption_modules(array $input)` – berechnet Verbrauchsblöcke separat vom User-SKU-Stapel
- `evaluate(array $input)` – kombiniert Add-on-Bewertung, Auto-Ergänzungen, Kosten, Upgrade-Hinweise, Warnungen und Quellen
- `plan_options()` – liefert Basisplan-Auswahloptionen
- `addon_options()` – liefert Add-ons nach Kategorie gruppiert
- `render_addon_configurator_page()` – liefert den Template-Pfad für das Public Template

## `CMS_M365CALCULATOR_Commitment_Calculator`

- `default_input()` – Default-Werte für Plan, Nutzerzahl, Volatilität, Wachstum, stabilen Kern, Zeitraum und Kanal
- `normalize_input(array $source)` – normalisiert GET-Parameter ohne POST-/CSRF-Abhängigkeit
- `plan_options()` – liefert auswählbare Lizenz-/SKU-Optionen aus `commitment_pricing.json`
- `evaluate(array $input)` – berechnet Monatslaufzeit, Jahreslaufzeit monatlich, Jahreslaufzeit jährlich und Split-Strategie inklusive Empfehlung
- `render_commitment_page()` – liefert den Template-Pfad für `/m365-jahresvertrag-vs-monatsvertrag`

## `CMS_M365CALCULATOR_Archive_Mailbox_Calculator`

- `default_input()` – Default-Werte für Basisplan, Mailbox-Typ, Mailboxanzahl, Speicher, Wachstum, Aufbewahrung und Archivziel
- `normalize_input(array $source)` – normalisiert GET-Parameter ohne POST-/CSRF-Abhängigkeit
- `plan_options()` – liefert auswählbare Basispläne aus `archive_mailbox_plans.json`
- `evaluate(array $input)` – bewertet Primärmailbox, Archiv, Auto-expanding Archive, Shared-/Resource-Mailbox-Sonderfälle, Hold, Purview-Hinweise und Add-on-Kosten
- `render_archive_mailbox_page()` – liefert den Template-Pfad für `/m365-archive-mailbox-rechner`

## `CMS_M365CALCULATOR_Copilot_Pilot_Calculator`

- `default_input()` – Default-Werte für Organisationsgröße, Knowledge-Worker-Anteil, Budget, Zielbild und Readiness-Faktoren
- `normalize_input(array $source)` – normalisiert GET-Parameter, Zahlen, Zielauswahl, Oversharing-Risiko und Readiness-Flags
- `goal_options()` – liefert auswählbare Pilotziele wie Produktivität, Use Cases, Governance, Management-Buy-in und ROI-Validierung
- `oversharing_options()` – liefert die Risikostufen niedrig, mittel und hoch
- `evaluate(array $input)` – kombiniert Pilotstufen, Readiness-Score, Empfehlung, Timeline, nächste Schritte, Preflight-Checkliste, FAQ, Messkriterien und Quellen
- `calculate_copilot_pilot_sizes(array $input, array $sizes)` – berechnet Stufenkosten, Pilotkosten, Champion-Bedarf und Budget-Fit
- `score_copilot_readiness(array $input, array $checklist)` – bewertet Lizenzbasis, Apps/Netzwerk, Datenhygiene, Champions, Feedback und Messbarkeit
- `score_copilot_pilot_recommendation(array $input, array $readiness, array $stages, array $sizes, array $checklist)` – leitet Kategorie, empfohlene Nutzerzahl, Dauer, Kosten und Begründung ab
- `build_copilot_rollout_timeline(int $durationWeeks, array $rollouts)` – wählt das passende 4-/6-/8-/12-Wochen-Template
- `render_copilot_pilot_page()` – liefert den Template-Pfad für `/copilot-pilot-rechner`

## `CMS_M365CALCULATOR_Backup_Cost_Calculator`

- `default_input()` – Default-Werte für Nutzer, Workload-Datenmengen, Schutzanteil, Wachstum, Retention, Restore-Tiefe, Trust Boundary und Provider
- `normalize_input(array $source)` – normalisiert GET-Parameter, Zahlenfelder, Workload-Auswahl und Provider-Auswahl
- `workload_options()` – liefert auswählbare Workloads Exchange, OneDrive, SharePoint und Teams-Dateien
- `provider_options()` – liefert auswählbare Provider aus `backup_providers.json`
- `evaluate(array $input)` – berechnet geschützte GB, Microsoft-Baseline, Providervergleich, KPIs, Empfehlung, FAQ, Quellen und Meta-Informationen
- `load_microsoft_backup_baseline()` – lädt die offizielle Microsoft-365-Backup-Baseline
- `load_backup_provider_catalog()` – lädt Microsoft- und Provider-Vergleichsdaten
- `normalize_backup_provider_pricing(array $provider, array $input, array $storage, array $weights)` – normalisiert Providerpreise auf Monats- und Jahreswerte
- `rank_backup_provider_results(array $providerResults)` – sortiert Provider nach Scoring und Kosten
- `compare_backup_scenarios(array $input, array $results, array $rules, array $microsoft, array $cheapest)` – leitet die Empfehlungskategorie ab
- `render_backup_cost_page()` – liefert den Template-Pfad für `/m365-backup-kostenrechner`

## `CMS_M365CALCULATOR_Storage_Needs_Calculator`

- `default_input()` – Default-Werte für Lizenzen, Nutzer, SharePoint, OneDrive, Exchange, Archiv, Wachstum, Puffer und Cleanup-Potenzial
- `normalize_input(array $source)` – normalisiert Anfrageparameter, Zahlenfelder, Postfachgröße, Archivmodell und Betrachtungszeitraum
- `evaluate(array $input)` – kombiniert SharePoint-, OneDrive-, Exchange- und Archivbewertung, Kostenannahme, Status, Leitplanken, Quellen und Optionen
- `calculate_storage_requirements(array $input, array $sharepointRules, array $onedriveRules, array $exchangeRules)` – berechnet Forecast, Kapazität, Überhang und Auslastung je Bereich
- `calculate_storage_overage_costs(array $input, array $requirements, array $sharepointRules)` – berechnet zusätzlichen SharePoint-Speicher in GB-Schritten und Monats-/Jahresannahme
- `calculate_exchange_archive_need(array $input, float $growthFactor, float $bufferFactor, array $exchangeRules)` – bewertet Archivnutzer, Archivmodell, Forecast, Kapazität und Überhang
- `build_storage_capacity_status(array $input, array $requirements, array $sharepointRules, array $onedriveRules, array $exchangeRules, array $growthRules)` – leitet Status, Score, Auffälligkeiten und nächste Schritte ab
- `load_sharepoint_storage_rules()` – lädt `sharepoint_storage_rules.json`
- `load_onedrive_quota_presets()` – lädt `onedrive_quota_presets.json`
- `load_exchange_storage_rules()` – lädt `exchange_storage_rules.json`
- `load_storage_growth_assumptions()` – lädt `storage_growth_assumptions.json`
- `render_storage_calculator_page()` – liefert den Template-Pfad für `/m365-storage-bedarfsrechner`

## `CMS_M365CALCULATOR_Workspace_M365_TCO_Calculator`

- `default_input()` – Default-Werte für Richtung, Nutzerzahl, Planwahl, Betrachtungszeitraum, Add-ons, Projektkosten, Anforderungen und Sonderfälle
- `normalize_input(array $source)` – normalisiert GET-Parameter, Zahlenfelder, Auswahlwerte und Checkbox-Werte
- `evaluate(array $input)` – kombiniert Planmapping, Plattform-TCO, Projektannahmen, Break-even, Timeline, Warnungen, nächste Schritte und Quellen
- `map_workspace_to_m365_plans(array $input, array $workspaceCatalog, array $m365Catalog, array $mappingCatalog)` – leitet Zielpläne für Google-Workspace-zu-M365 und M365-zu-Google ab
- `calculate_workspace_tco(array $input, array $plan, array $migration)` – berechnet Workspace-Lizenzen, Add-ons, Projektanteil, laufende Kosten und Gesamtkosten
- `calculate_m365_tco(array $input, array $plan, array $migration)` – berechnet Microsoft-365-Lizenzen, Add-ons, Projektanteil, laufende Kosten und Gesamtkosten
- `compare_workspace_m365_tco(array $input, array $workspace, array $m365, array $mappingCatalog, array $migration)` – leitet Gewinner, Delta, Prozentdifferenz, Break-even und Empfehlungskategorie ab
- `build_workspace_migration_assumptions(array $input, float $workspaceMonthly, float $m365Monthly, array $defaultsCatalog)` – berechnet Projekt-, Schulungs-, Change-, Hypercare- und Parallelbetriebskosten
- `load_google_workspace_plans()` – lädt `google_workspace_plans.json`
- `load_m365_target_plans()` – lädt `m365_target_plans.json`
- `load_workspace_to_m365_mapping()` – lädt `workspace_to_m365_mapping.json`
- `load_migration_defaults()` – lädt `migration_defaults.json`
- `render_workspace_tco_page()` – liefert den Template-Pfad für `/google-workspace-zu-m365-tco`

## `CMS_M365CALCULATOR_Power_Platform_Cost_Calculator`

- `default_input()` – Default-Werte für Use Case, Nutzer, Maker, Umgebungen, Connectoren, RPA, Website, Credits, Requests, Storage und Governance
- `normalize_input(array $source)` – normalisiert Anfrageparameter, Zahlenfelder, Auswahlwerte und Checkbox-Werte
- `validate_power_platform_input(array $source)` – normalisiert Eingaben und liefert fachliche Hinweise zu fehlenden Mengen
- `evaluate(array $input)` – kombiniert Use Case, Seeded-Rechte, Connector-Regeln, Dataverse-for-Teams-Fit, Kostenblöcke, Warnungen, Quellen und nächste Schritte
- `evaluate_power_platform_use_case(array $input)` – liefert den gewählten Use Case, Hauptpfad, Alternativpfad und Capacity-Einordnung
- `evaluate_power_platform_seeded_rights(array $input, array $useCase, array $connector)` – prüft, ob enthaltene M365-/Teams-Rechte plausibel ausreichen
- `evaluate_dataverse_for_teams_fit(array $input, array $governance)` – bewertet Dataverse-for-Teams-Grenzen und Upgrade-Treiber
- `calculate_power_platform_costs(array $input, array $productsCatalog, array $capacityCatalog, array $seeded, array $dataverseFit)` – berechnet Monats-, Jahres- und Zeitraumkosten
- `calculate_power_platform_capacity_costs(array $input, array $capacity, array $seeded)` – berechnet Dataverse Storage, Process Mining, Request-Add-ons und AI-Prüfpositionen
- `calculate_power_platform_credit_usage(array $input)` – berechnet Copilot-Credit-Verbrauch auf Monats- und Jahresbasis
- `build_power_platform_recommendation(array $input, array $selectedUseCase, array $connector, array $seeded, array $dataverseFit, array $costs, array $useCases)` – leitet Hauptempfehlung und Tonalität ab
- `load_power_platform_products()` – lädt `power_platform_products.json`
- `load_power_platform_use_cases()` – lädt `power_platform_use_cases.json`
- `load_power_platform_connector_rules()` – lädt `power_platform_connector_rules.json`
- `load_power_platform_capacity_catalog()` – lädt `power_platform_capacity_catalog.json`
- `load_power_platform_governance_rules()` – lädt `power_platform_governance_rules.json`
- `render_power_platform_page()` – liefert den Template-Pfad für `/power-platform-kosten-kalkulator`
- `export_power_platform_pdf(array $result)` – liefert eine druckfreundliche Exportstruktur

## `CMS_M365CALCULATOR_Settings`

- `apply_to_tools(array $tools)` – wendet Admin-Overrides auf Registry-Module an
- `module_settings()` – liest gespeicherte Modul-Overrides
- `settings_for_admin(array $tools)` – bereitet Admin-Formulardaten vor
- `save_module_settings(array $posted)` – speichert Admin-Overrides CSRF-geschützt über die Adminseite

## `CMS_M365CALCULATOR_Tool_Registry`

- `register(array $tool)` – registriert ein Rechner-Modul
- `tools()` – liefert alle Moduldefinitionen
- `ordered_tools()` – liefert nach Priorität sortierte Module
- `grouped_by_category()` – liefert Module nach Kategorie gruppiert
- `get(string $slug)` – liefert ein Modul nach Slug

## `CMS_M365CALCULATOR_Shared_Mailbox_Calculator`

- `default_input()` – Default-Werte für das Formular
- `normalize_input(array $source)` – normalisiert POST-Daten
- `evaluate(array $input)` – berechnet Empfehlung, Regeln, Warnungen, Kosten und nächste Schritte

## `CMS_M365CALCULATOR_Copilot_License_Checker`

- `default_input()` – Default-Werte für das Copilot-Formular
- `normalize_input(array $source)` – normalisiert POST-Daten
- `load_copilot_prerequisites()` – lädt technische Voraussetzungen
- `check_copilot_license_eligibility(array $input)` – prüft Basislizenz und Chat-Eligibility
- `check_copilot_technical_readiness(array $input)` – prüft Entra, Exchange Online, Apps, OneDrive, Teams, Privacy und Netzwerk
- `build_copilot_upgrade_path(array $input)` – ermittelt Zielbasis und Preisdelta
- `evaluate(array $input)` – kombiniert Lizenz, Technik, Chat-Hinweis, Upgrade-Pfad und nächste Schritte
- `plan_options()` – liefert Segment-/Planoptionen für das Template

## `CMS_M365CALCULATOR_Copilot_ROI_Calculator`

- `default_input()` – Default-Werte für den ROI-Rechner
- `normalize_input(array $source)` – normalisiert POST-Daten, Zahlen, Szenario und Readiness-Flags
- `persona_options()` – liefert Rollenprofile aus `persona_roi_presets.json`
- `validate_copilot_readiness(array $input)` – trennt harte Blocker von Readiness-Warnungen
- `calculate_copilot_roi(array $input, string $scenarioKey, array $scenario)` – berechnet Produktivitätswert, Kosten, ROI, Payback und Break-even
- `calculate_copilot_break_even_minutes(array $input, array $scenario)` – berechnet nötige Minuten Zeitgewinn pro Tag
- `calculate_copilot_payback_period(array $input, float $monthlyProductivity, float $monthlyCost, float $oneTimeCost)` – berechnet Payback in Monaten
- `build_copilot_roi_chart(array $input, string $scenarioKey, array $scenarioResult)` – erzeugt Chart-Daten für 12 Monate
- `evaluate(array $input)` – kombiniert Readiness, Szenarien, Chart, Status und nächste Schritte

## `CMS_M365CALCULATOR_Frontend`

- Registriert `/m365-tools`, `/m365-rechner`, `/m365-lizenz-audit-checkliste`, `/power-platform-kosten-kalkulator`, `/google-workspace-zu-m365-tco`, `/m365-storage-bedarfsrechner`, `/m365-backup-kostenrechner`, `/microsoft-preiserhoehung-tracker`, `/teams-phone-lizenzberater`, `/exchange-online-roi`, `/frontline-worker-lizenz-check`, `/copilot-pilot-rechner`, `/ai-pack-vs-copilot-pro`, `/m365-lizenzmatrix`, `/m365-addon-matrix`, `/m365-archive-mailbox-rechner`, `/m365-jahresvertrag-vs-monatsvertrag`, `/m365-add-on-konfigurator`, `/m365-lizenzvergleich`, `/m365-lizenzberater`, `/copilot-roi-rechner`, `/shared-mailbox-vs-lizenz` und `/copilot-lizenz-check`
- Bindet Assets nur auf Plugin-Routen ein
- Rendert Toolbox und Rechner-Template
