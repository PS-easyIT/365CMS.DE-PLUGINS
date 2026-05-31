# Feature Ideas for CMS M365 Adminsites

## 1) Tenant Service Health Snapshot
- **Feature name:** Tenant Service Health Snapshot
- **Short description:** Add an optional admin-only dashboard card that pulls current Microsoft 365 service health status (e.g., Exchange, Teams, SharePoint) via Microsoft Graph service communications API.
- **Why it fits this plugin:** The plugin is already a curated portal hub for Microsoft admin work. Service health context next to portal links reduces context switching for admins.
- **Rough effort (low/medium/high):** medium
- **Source link(s):**
  - [Microsoft Graph service communications API overview](https://learn.microsoft.com/en-us/graph/api/resources/service-communications-api-overview?view=graph-rest-1.0)
  - [Access service health and communications in Microsoft Graph](https://learn.microsoft.com/en-us/graph/service-communications-concept-overview)

## 2) Message Center Highlights Feed
- **Feature name:** Message Center Highlights Feed
- **Short description:** Add configurable “important announcements” snippets from Microsoft 365 Message Center to the admin page (filter by workload or severity).
- **Why it fits this plugin:** Portal users often need both the portal entry points and the latest service change communications in one place.
- **Rough effort (low/medium/high):** medium
- **Source link(s):**
  - [Microsoft Graph service communications API overview](https://learn.microsoft.com/en-us/graph/api/resources/service-communications-api-overview?view=graph-rest-1.0)
  - [Get serviceHealth (Graph)](https://learn.microsoft.com/en-us/graph/api/servicehealth-get?view=graph-rest-1.0)

## 3) Security Posture Quick Glance
- **Feature name:** Security Posture Quick Glance
- **Short description:** Show optional trend indicators for Microsoft Secure Score and top controls requiring action, with links to the relevant security portals.
- **Why it fits this plugin:** Security-related portals are a core category in this plugin; surfacing score trends improves prioritization before opening each portal.
- **Rough effort (low/medium/high):** medium
- **Source link(s):**
  - [List secureScores (Graph)](https://learn.microsoft.com/en-us/graph/api/security-list-securescores?view=graph-rest-1.0)
  - [Microsoft Graph security API overview](https://github.com/microsoftgraph/microsoft-graph-docs-contrib/blob/main/api-reference/v1.0/resources/security-api-overview.md)

## 4) Conditional Access What-If Shortcuts
- **Feature name:** Conditional Access What-If Shortcuts
- **Short description:** Add curated deep links and optional helper presets for common What-If simulation scenarios (identity, app, platform).
- **Why it fits this plugin:** The plugin focuses on fast navigation; Conditional Access troubleshooting is a frequent admin workflow that benefits from direct guided entry points.
- **Rough effort (low/medium/high):** low
- **Source link(s):**
  - [Conditional Access What If tool](https://learn.microsoft.com/en-us/entra/identity/conditional-access/what-if-tool)
  - [What If evaluation API (Graph)](https://learn.microsoft.com/en-us/graph/api/conditionalaccessroot-evaluate?view=graph-rest-1.0)

## 5) Usage Insights Companion Panel
- **Feature name:** Usage Insights Companion Panel
- **Short description:** Provide optional aggregated usage metrics (active users, app activity) with links to corresponding report endpoints and admin centers.
- **Why it fits this plugin:** It complements “where to go” (portal list) with lightweight “what changed” signals, making the page more action-oriented for administrators.
- **Rough effort (low/medium/high):** high
- **Source link(s):**
  - [getOffice365ActiveUserDetail (Graph)](https://learn.microsoft.com/en-us/graph/api/reportroot-getoffice365activeuserdetail?view=graph-rest-1.0)
  - [getM365AppUserDetail (Graph)](https://learn.microsoft.com/en-us/graph/api/reportroot-getm365appuserdetail?view=graph-rest-1.0)
