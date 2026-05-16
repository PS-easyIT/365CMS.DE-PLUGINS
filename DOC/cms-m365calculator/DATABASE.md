# CMS M365 Calculator – Datenbank

Ab Version `1.2.0` legt das Plugin eine eigene Tabelle für Admin-Overrides der Module an.

## `cms_m365calculator_module_settings`

| Feld | Typ | Zweck |
|---|---|---|
| `id` | `INT UNSIGNED AUTO_INCREMENT` | Primärschlüssel |
| `module_key` | `VARCHAR(120)` | Modulschlüssel, eindeutig |
| `is_enabled` | `TINYINT(1)` | Modul auf Landingpage sichtbar |
| `status_override` | `VARCHAR(20)` | Optionaler Status `live`, `beta`, `soon` |
| `priority_override` | `INT UNSIGNED` | Optionale Sortierung |
| `title_override` | `VARCHAR(190)` | Optionaler öffentlicher Titel |
| `description_override` | `TEXT` | Optionale öffentliche Beschreibung |
| `updated_at` | `TIMESTAMP` | Änderungszeitpunkt |

Die Katalogdaten liegen als versionierte JSON-Dateien im Plugin-Verzeichnis:

- `data/shared_mailbox_rules.json`
- `data/mailbox_license_matrix.json`
- `data/shared_mailbox_scenarios.json`
- `data/pricing.json`
- `data/copilot_eligibility_matrix.json`
- `data/copilot_technical_prerequisites.json`
- `data/license_upgrade_paths.json`
- `data/license_advisor_plans.json`
- `data/license_advisor_addons.json`
- `data/license_advisor_feature_matrix.json`
- `data/license_advisor_persona_presets.json`
- `data/license_advisor_commercial_rules.json`
- `data/copilot_pricing.json`
- `data/copilot_readiness_rules.json`
- `data/roi_assumptions.json`
- `data/persona_roi_presets.json`
- `data/plan_comparison_feature_matrix.json`
- `data/plan_comparison_badges.json`
- `data/plan_comparison_notes.json`
- `data/readonly_suite_matrix.json`
- `data/readonly_addon_matrix.json`
- `data/archive_mailbox_plans.json`
- `data/archive_mailbox_assumptions.json`
- `data/commitment_pricing.json`
- `data/commitment_assumptions.json`
- `data/commitment_channel_notes.json`
- `data/addon_configurator_addons.json`
- `data/addon_overlap_rules.json`
- `data/consumption_modules.json`
