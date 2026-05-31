# Feature Suggestions for CMS M365 Linkcollection

## 1) Automated Broken-Link Health Checks
- **Feature name:** Automated Broken-Link Health Checks
- **Short description:** Add an optional background job that periodically checks stored links, flags non-200 responses, and highlights affected entries in admin.
- **Why it fits this plugin:** The plugin is a curated link directory; link integrity is core content quality and reduces stale entries.
- **Rough effort:** Medium
- **Source link(s):**
  - https://support.microsoft.com/en-gb/topic/improve-your-sharepoint-site-with-knowledge-agent-4c801323-68f8-4274-96bf-b04d78b8d62b
  - https://www.npmjs.com/package/linkinator

## 2) Structured Data for Directory SEO
- **Feature name:** Structured Data (ItemList + BreadcrumbList)
- **Short description:** Add optional JSON-LD output for `ItemList` and `BreadcrumbList` on the public archive page.
- **Why it fits this plugin:** The plugin renders a navigable list of links; structured data can improve search understanding of list hierarchy.
- **Rough effort:** Low
- **Source link(s):**
  - https://schema.org/BreadcrumbList
  - https://schema.org/ItemList

## 3) Link Preview Metadata Assistant
- **Feature name:** Link Preview Metadata Assistant
- **Short description:** Provide an admin-side helper that fetches target metadata (title/description/image hints) before saving a new link.
- **Why it fits this plugin:** It reduces manual curation effort and improves consistency of entry quality.
- **Rough effort:** Medium
- **Source link(s):**
  - https://ogp.me/
  - https://devblogs.microsoft.com/microsoft365dev/boost-your-microsoft-teams-app-experience-with-new-link-unfurling-capabilities/

## 4) Accessibility Validation Mode for Filters
- **Feature name:** Accessibility Validation Mode
- **Short description:** Add optional diagnostics for filter/search UX (live-region announcements, keyboard flow checks, and result-count status updates).
- **Why it fits this plugin:** The public page already offers filtering and tabular/card views; accessibility checks help keep interactions robust over time.
- **Rough effort:** Medium
- **Source link(s):**
  - https://www.w3.org/WAI/ARIA/apg/patterns/grid/
  - https://www.accessible-data-interfaces.com/accessible-data-tables-grid-systems/

## 5) Optional M365 File Preview Cards
- **Feature name:** Optional M365 File Preview Cards
- **Short description:** For selected Microsoft 365 links, optionally render embeddable preview cards using Microsoft Graph preview endpoints.
- **Why it fits this plugin:** Many curated resources are Microsoft ecosystem assets; native previews can improve trust and context without opening links immediately.
- **Rough effort:** High
- **Source link(s):**
  - https://learn.microsoft.com/en-us/graph/api/driveitem-preview?view=graph-rest-1.0
