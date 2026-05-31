# Feature Suggestions for `cms-m365tools`

These are research-backed feature ideas for future iterations. They are **not implemented** in this audit task.

## 1) Tenant Usage Import (Graph Reports)
- **Feature name:** Tenant Usage Import (Graph Reports)
- **Short description:** Add an optional admin import that reads Microsoft Graph usage report exports (CSV) and pre-fills calculator defaults (active users, license footprint, workload activity).
- **Why it fits this plugin:** Most calculators currently depend on manual input. Importing tenant usage data would reduce input errors and improve recommendation quality without changing public rendering behavior.
- **Rough effort:** medium
- **Source link(s):**
  - [Microsoft Graph: getOffice365ActiveUserDetail](https://learn.microsoft.com/en-us/graph/api/reportroot-getoffice365activeuserdetail?view=graph-rest-1.0)
  - [Microsoft Graph: getOffice365ActivationsUserCounts](https://learn.microsoft.com/en-us/graph/api/reportroot-getoffice365activationsusercounts?view=graph-rest-1.0)

## 2) Partner Center Price Sync Assistant
- **Feature name:** Partner Center Price Sync Assistant
- **Short description:** Provide an admin-only import assistant for Partner Center price sheet files to update internal baseline prices and detect changed SKUs.
- **Why it fits this plugin:** The plugin already includes a price tracker and price-driven calculators. Automated ingestion would keep assumptions current and reduce manual maintenance overhead.
- **Rough effort:** high
- **Source link(s):**
  - [Partner Center: Get a price sheet API](https://learn.microsoft.com/en-us/partner-center/developer/get-a-price-sheet)
  - [Partner Center: Pricing and offers](https://learn.microsoft.com/en-us/partner-center/pricing/pricing-and-offers)

## 3) Offer Matrix Validation Layer
- **Feature name:** Offer Matrix Validation Layer
- **Short description:** Add optional validation checks that compare selected SKU combinations against Partner Center offer matrix constraints (eligibility, transitions, limits).
- **Why it fits this plugin:** The license advisor, add-on configurator, and Teams/cost tools could surface earlier warnings when combinations are not transactable or require prerequisites.
- **Rough effort:** medium
- **Source link(s):**
  - [Partner Center: Get an offer matrix API](https://learn.microsoft.com/en-us/partner-center/developer/get-an-offer-matrix)

## 4) Copilot Adoption & Value Snapshot
- **Feature name:** Copilot Adoption & Value Snapshot
- **Short description:** Add a compact admin analytics panel that maps Copilot usage intensity, adoption score trend, and estimated business value to plugin recommendations.
- **Why it fits this plugin:** The plugin already contains Copilot-focused calculators. A shared KPI snapshot would improve continuity between readiness, pilot sizing, and ROI modules.
- **Rough effort:** medium
- **Source link(s):**
  - [AI adoption category in Adoption Score](https://learn.microsoft.com/en-us/microsoft-365/admin/adoption/ai-adoption-score?view=o365-worldwide)
  - [Copilot Business Impact report](https://learn.microsoft.com/en-us/viva/insights/advanced/analyst/templates/copilot-business-impact)

## 5) License Assignment Risk Signals
- **Feature name:** License Assignment Risk Signals
- **Short description:** Enrich recommendation outputs with risk signals for direct vs. group-based assignment patterns and inactive licensed users.
- **Why it fits this plugin:** Several modules optimize licensing decisions. Assignment-risk signals would add operational governance context without changing existing public API contracts.
- **Rough effort:** medium
- **Source link(s):**
  - [Microsoft Graph: user licenseDetails](https://learn.microsoft.com/en-us/graph/api/user-list-licensedetails?view=graph-rest-1.0)
