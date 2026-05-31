# Feature Ideas for CMS M365 Landing

## 1) Tenant Service Health Panel
- **Feature name:** Tenant Service Health Panel
- **Status:** ✅ umgesetzt (2026-05-31)
- **Umsetzungsgrund:** Sinnvolles Medium-Feature mit direktem Mehrwert auf der Landingpage; technisch stabil via optionaler Graph-Credentials in den Plugin-Einstellungen und rein additive Public-Ausgabe.
- **Short description:** Add an optional section that shows current Microsoft 365 service incidents and advisories (for example Exchange Online, Teams, SharePoint) via Microsoft Graph service communications APIs.
- **Why it fits this plugin:** The plugin is a central M365 entry page; surfacing service health makes it more actionable for admins and business users directly from the landing hub.
- **Rough effort:** medium
- **Source link(s):**
  - https://learn.microsoft.com/en-us/graph/api/resources/service-communications-api-overview?view=graph-rest-1.0
  - https://learn.microsoft.com/en-us/graph/service-communications-concept-overview
  - https://learn.microsoft.com/en-us/graph/api/serviceannouncement-list-issues?view=graph-rest-1.0

## 2) Message Center Highlights Card
- **Feature name:** Message Center Highlights Card
- **Status:** ✅ umgesetzt (2026-05-31)
- **Umsetzungsgrund:** Sinnvolles Medium-Feature als ergänzende Governance-Information; optionales Public-Panel mit Service-Filter und konfigurierbarer Anzahl von Highlights.
- **Short description:** Add a compact card block for important upcoming Microsoft 365 changes (Message Center posts), optionally filterable by service.
- **Why it fits this plugin:** The landing page already curates matrixes, areas, and tools; adding change announcements keeps content current and supports governance decisions.
- **Rough effort:** medium
- **Source link(s):**
  - https://learn.microsoft.com/en-us/graph/api/resources/service-communications-api-overview?view=graph-rest-1.0
  - https://learn.microsoft.com/en-us/graph/service-communications-concept-overview

## 3) Usage Insights Snapshot
- **Feature name:** Usage Insights Snapshot
- **Status:** ⏸️ offen
- **Zurückstellungsgrund:** Für eine saubere Vollumsetzung sind zusätzliche Reporting-Aufbereitung (Zeitreihen, KPI-Darstellung, ggf. Aggregation/Caching) und UI-Charts erforderlich; im aktuellen Scope nicht mehr Low/Medium ohne Risiko für Wartbarkeit.
- **Short description:** Add an optional analytics widget that visualizes active-user trends (D7/D30/D90/D180) from Microsoft 365 reporting endpoints.
- **Why it fits this plugin:** The plugin is used as a decision cockpit; usage trends provide context for licensing, adoption, and prioritization of linked tools/content.
- **Rough effort:** medium
- **Source link(s):**
  - https://learn.microsoft.com/en-us/graph/api/reportroot-getoffice365activeuserdetail?view=graph-rest-1.0
  - https://learn.microsoft.com/en-us/graph/api/reportroot-getoffice365activationsuserdetail?view=graph-rest-1.0

## 4) Auto-Refresh Content Triggers
- **Feature name:** Auto-Refresh Content Triggers
- **Status:** ⏸️ offen
- **Zurückstellungsgrund:** Weiterhin High-Effort (Webhook-Lifecycle, Subscription-Management, Absicherung, Retry/Backoff, Hintergrundjobs) und damit außerhalb des angefragten Low/Medium-Fokus.
- **Short description:** Add webhook-driven background refresh for selected landing cards/sections when subscribed Microsoft Graph resources change.
- **Why it fits this plugin:** It reduces manual admin updates and keeps card data fresher without changing public rendering behavior.
- **Rough effort:** high
- **Source link(s):**
  - https://learn.microsoft.com/en-us/graph/change-notifications-overview
  - https://learn.microsoft.com/en-us/graph/change-notifications-delivery-webhooks
  - https://learn.microsoft.com/en-us/graph/api/subscription-post-subscriptions?view=graph-rest-1.0

## 5) Unified M365 Search Block
- **Feature name:** Unified M365 Search Block
- **Status:** ⏸️ offen
- **Zurückstellungsgrund:** High-Effort durch Such-Indexing, API-Query-Design, Ranking/Performance und UX-Anforderungen; nicht sinnvoll als schneller Low/Medium-Umfang.
- **Short description:** Provide an optional search module that queries Microsoft Search API across SharePoint/OneDrive and connector-based external content.
- **Why it fits this plugin:** The landing page is an entry point; a unified search improves discoverability of M365 resources linked from this hub.
- **Rough effort:** high
- **Source link(s):**
  - https://learn.microsoft.com/en-us/graph/search-concept-overview
  - https://learn.microsoft.com/en-us/graph/api/search-query?view=graph-rest-1.0
  - https://learn.microsoft.com/en-us/graph/api/resources/search-api-overview?view=graph-rest-1.0
