# Feature Ideas for CMS M365 Azure

## 1) Live Azure Service Health Summary
- **Feature name:** Live Azure Service Health Summary
- **Short description:** Show optional health status badges per listed Azure service category by consuming Azure Service Health and Resource Health APIs on a scheduled sync.
- **Why it fits this plugin:** The plugin already curates Azure services and links; adding service health context strengthens its decision-support value without changing core content management.
- **Rough effort:** Medium
- **Status:** skipped
- **Reason:** Für eine belastbare Umsetzung werden Azure-Authentifizierung (Entra App/Secrets), Scheduler/Retry-Mechanik, API-Quota-Handling und persistente Health-Historie benötigt; das wurde in dieser Iteration bewusst nicht plugin-lokal erweitert, um bestehende Hooks/Slugs/API stabil zu halten.
- **Source link(s):**
  - https://learn.microsoft.com/en-us/azure/service-health/
  - https://learn.microsoft.com/en-us/rest/api/resourcehealth/

## 2) Advisor Recommendation Snapshot
- **Feature name:** Advisor Recommendation Snapshot
- **Short description:** Add a read-only summary panel that groups Azure Advisor recommendations by category (Cost, Security, Reliability, Performance, Operational Excellence).
- **Why it fits this plugin:** The plugin is used as an Azure overview hub; surfacing Advisor signals gives practical next actions directly beside service information.
- **Rough effort:** Medium
- **Status:** skipped
- **Reason:** Für den produktiven Snapshot sind Subscription-Scopes, Rechteprüfung, sichere Token-Beschaffung und regelmäßige Synchronisierung notwendig; ohne diese Infrastruktur wäre der Mehrwert inkonsistent.
- **Source link(s):**
  - https://learn.microsoft.com/en-us/rest/api/advisor/recommendations/list?view=rest-advisor-2025-01-01
  - https://learn.microsoft.com/en-us/azure/advisor/advisor-overview

## 3) Pricing Freshness and Delta Tracker
- **Feature name:** Pricing Freshness and Delta Tracker
- **Short description:** Optionally compare stored pricing links with periodic Azure Retail Prices API snapshots and show a non-intrusive "last checked" / "price changed" indicator in admin.
- **Why it fits this plugin:** This plugin maintains service and pricing references; administrators would benefit from quick detection of potentially outdated pricing context.
- **Rough effort:** Medium
- **Status:** skipped
- **Reason:** Eine vollständige, verlässliche Delta-Logik benötigt Snapshot-Speicherung, Diff-Strategie pro SKU/Region/Währung und Hintergrundjobs; ohne diese Persistenz wäre die Anzeige fehleranfällig.
- **Source link(s):**
  - https://learn.microsoft.com/en-us/rest/api/cost-management/retail-prices/azure-retail-prices
  - https://prices.azure.com/api/retail/prices

## 4) Governance Tag Coverage Insights
- **Feature name:** Governance Tag Coverage Insights
- **Short description:** Provide optional governance metrics (for example, missing required tags) based on Azure Resource Graph sample queries.
- **Why it fits this plugin:** The plugin already organizes Azure services by governance-relevant categories; lightweight governance insights complement that structure for platform teams.
- **Rough effort:** Medium
- **Status:** skipped
- **Reason:** Erfordert tenant-/subscription-spezifische Resource-Graph-Abfragen, konfigurierbare Pflicht-Tags und Berechtigungsmodell; wurde zurückgestellt, um in diesem Schritt keine neuen externen Abhängigkeiten einzuführen.
- **Source link(s):**
  - https://learn.microsoft.com/en-us/azure/azure-resource-manager/management/resource-graph-samples
  - https://learn.microsoft.com/en-us/azure/governance/resource-graph/overview

## 5) Well-Architected Improvement Checklist
- **Feature name:** Well-Architected Improvement Checklist
- **Short description:** Add an optional checklist template (not automated scoring) mapped to Well-Architected pillars for each service category to support architecture review workshops.
- **Why it fits this plugin:** The plugin is a curated catalog and can serve as a planning baseline; a guided checklist improves operational usefulness for cloud roadmap discussions.
- **Rough effort:** Low
- **Status:** implemented
- **Reason:** Vollständig plugin-lokal ergänzt: optionaler Public-Checklist-Block je Kategorie, steuerbar über Admin-Settings inklusive Pillar-Texten (Reliability, Security, Cost Optimization, Operational Excellence, Performance Efficiency).
- **Source link(s):**
  - https://learn.microsoft.com/en-us/azure/well-architected/design-guides/implementing-recommendations
  - https://learn.microsoft.com/en-us/assessments/azure-architecture-review/
