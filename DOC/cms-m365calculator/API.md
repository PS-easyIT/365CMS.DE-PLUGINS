# CMS M365 Calculator – API

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
- `license_advisor_plans()` – lädt Basislizenz-Kataloge
- `license_advisor_addons()` – lädt Add-ons und Prerequisites
- `license_advisor_feature_matrix()` – lädt Featuredefinitionen
- `license_advisor_persona_presets()` – lädt Persona-Presets
- `license_advisor_commercial_rules()` – lädt Billing- und Quellenregeln

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

- Registriert `/m365-tools`, `/m365-rechner`, `/m365-lizenzmatrix`, `/m365-addon-matrix`, `/m365-archive-mailbox-rechner`, `/m365-jahresvertrag-vs-monatsvertrag`, `/m365-add-on-konfigurator`, `/m365-lizenzvergleich`, `/m365-lizenzberater`, `/copilot-roi-rechner`, `/shared-mailbox-vs-lizenz` und `/copilot-lizenz-check`
- Bindet Assets nur auf Plugin-Routen ein
- Rendert Toolbox und Rechner-Template
