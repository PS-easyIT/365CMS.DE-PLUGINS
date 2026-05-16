# CMS M365 Calculator – Dokumentation

## Überblick

`cms-m365calculator` ist eine modulare Microsoft-365-Rechner-Toolbox für 365CMS. Produktive Module sind der **Archive Mailbox Rechner**, die **M365 Lizenzmatrix**, die **M365 Add-on-Matrix**, der **Annual vs. Monthly Commitment Rechner**, der **M365 Add-On-Konfigurator**, der **M365-Lizenzvergleich**, der **M365-Lizenz-Berater**, der **Copilot ROI-Rechner**, der **Shared-Mailbox vs. Lizenz-Rechner** und der **Copilot Lizenz-Pflicht-Checker**.

## Public Routes

| Route | Zweck |
|---|---|
| `/m365-tools` | Übersicht aller Rechner-Module |
| `/m365-rechner` | Alternative Hub-Route |
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

## Admin-Steuerung

Der Adminbereich kann je Modul Sichtbarkeit, Status, Priorität, öffentlichen Titel und Beschreibung überschreiben. Die Werte werden in `cms_m365calculator_module_settings` gespeichert und beim Rendern der Registry angewendet.

## Designvorgaben

Das Plugin nutzt PHINIT-konforme Public-Komponenten und vermeidet statische Inline-Styles, große Gradients, Glassmorphism oder KI-Optik. Die Hub-Landingpage rendert Module ausschließlich aus der Tool-Registry. Öffentliche Pluginseiten setzen einen Plugin-eigenen Abstand von 25px zum Theme-Header.
