# Feature Ideas for `cms-m365matrices`

## 1) Matrix Change Log (Data Freshness Timeline)
- **Feature name:** Matrix Change Log
- **Short description:** Add a read-only timeline that shows when pricing or feature entries were last updated per matrix area (suite, add-on, copilot).
- **Why it fits this plugin:** This plugin is a comparison source; transparency on update recency increases trust without changing matrix logic.
- **Rough effort:** medium
- **Source link(s):**
  - [Microsoft Copilot Studio licensing updates (Microsoft Learn)](https://learn.microsoft.com/en-us/microsoft-copilot-studio/billing-licensing)
  - [M365 licensing comparison tools with “what changed” pattern](https://www.aguidetocloud.com/licensing/)

## 2) Export Snapshot (CSV / JSON)
- **Feature name:** Export Snapshot
- **Short description:** Provide admin-side export of currently normalized matrix data into CSV/JSON for audit and offline review.
- **Why it fits this plugin:** Matrix data is already normalized in PHP; export is a natural extension for governance and reporting.
- **Rough effort:** low
- **Source link(s):**
  - [Export Microsoft 365 license cost report](https://mscloudexplorers.com/export-microsoft-365-license-cost-report/)
  - [M365 licensing audit workflow reference](https://sbd.org.uk/blog/m365-licensing-audit)

## 3) Source Health Checks
- **Feature name:** Source Health Checks
- **Short description:** Add a background/admin check that validates configured source links (HTTP status and redirect safety) and flags stale or broken references.
- **Why it fits this plugin:** The plugin exposes many source URLs; automated validation improves data quality and credibility.
- **Rough effort:** medium
- **Source link(s):**
  - [Feature Matrix style reference dataset](https://m365maps.com/matrix.htm)
  - [Copilot Studio billing rates and management](https://learn.microsoft.com/en-us/microsoft-copilot-studio/requirements-messages-management)

## 4) Differential View (Plan A vs Plan B)
- **Feature name:** Differential View
- **Short description:** Add an optional focused comparison mode that highlights only differing cells between selected packages.
- **Why it fits this plugin:** Large matrices are hard to scan; differential focus helps users make faster package decisions while reusing existing data.
- **Rough effort:** medium
- **Source link(s):**
  - [Microsoft 365 plan navigator (comparison UX pattern)](https://medhacloud.com/tools/m365-license-comparison)
  - [M365 Maps comparison matrix reference](https://m365maps.com/matrix.htm)

## 5) Cost Scenario Presets
- **Feature name:** Cost Scenario Presets
- **Short description:** Add optional predefined seat-mix scenarios (e.g., SMB, enterprise mixed licensing) that estimate monthly cost ranges from matrix pricing fields.
- **Why it fits this plugin:** The matrix already stores monthly prices; scenario overlays would provide practical planning value.
- **Rough effort:** high
- **Source link(s):**
  - [Microsoft 365 licensing matrix + cost calculator example](https://medhacloud.com/tools/m365-license-comparison)
  - [Copilot credits and consumption model](https://learn.microsoft.com/en-us/microsoft-copilot-studio/billing-licensing)
