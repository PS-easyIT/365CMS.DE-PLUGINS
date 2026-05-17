# CMS M365 Tools – Datenbank

Ab Version `1.2.0` legt das Plugin eine eigene Tabelle für Admin-Overrides der Module an.

## `cms_m365tools_module_settings`

Ab Version `1.16.0` nutzt das Plugin die Tabelle `cms_m365tools_module_settings`. Falls aus einer früheren Installation noch `cms_m365calculator_module_settings` vorhanden ist, kopiert der Installer vorhandene Modul-Overrides automatisch in die neue Tabelle.

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
- `data/microsoft_price_events.json`
- `data/microsoft_price_changes.json`
- `data/microsoft_inventory_mapping.json`
- `data/microsoft_price_forecast_rules.json`
- `data/teams_phone_base_eligibility.json`
- `data/teams_pstn_model_rules.json`
- `data/teams_country_availability.json`
- `data/teams_voice_providers.json`
- `data/teams_direct_routing_requirements.json`
- `data/teams_phone_cost_assumptions.json`
- `data/exchange_online_plans.json`
- `data/onprem_exchange_cost_defaults.json`
- `data/exchange_migration_velocity.json`
- `data/frontline_user_type_matrix.json`
- `data/frontline_plan_matrix.json`
- `data/frontline_industry_presets.json`
- `data/copilot_pilot_sizes.json`
- `data/copilot_rollout_templates.json`
- `data/copilot_readiness_checklist.json`
- `data/ai_product_catalog.json`
- `data/ai_use_case_matrix.json`
- `data/ai_dynamic_offers.json`
- `data/license_audit_checklist.json`
- `data/license_audit_deeplinks.json`
- `data/audit_pdf_template.json`
- `data/sharepoint_storage_rules.json`
- `data/onedrive_quota_presets.json`
- `data/exchange_storage_rules.json`
- `data/storage_growth_assumptions.json`
- `data/microsoft_backup_baseline.json`
- `data/backup_providers.json`
- `data/backup_comparison_rules.json`
- `data/google_workspace_plans.json`
- `data/m365_target_plans.json`
- `data/workspace_to_m365_mapping.json`
- `data/migration_defaults.json`

Die Lizenz-Audit-Checkliste speichert keine Daten serverseitig. Der interaktive Fortschritt wird im Browser des Besuchers verwaltet und nicht in einer Datenbank-Tabelle persistiert.
