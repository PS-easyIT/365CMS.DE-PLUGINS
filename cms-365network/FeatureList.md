# CMS-365Network Feature Suggestions

## 1) Audience-targeted Hub Sections
- **Feature name:** Audience-targeted Hub Sections
- **Short description:** Allow optional audience rules (for example by role/group tags) so specific Hub blocks or cards are prioritized for matching user segments.
- **Why it fits this plugin:** The plugin already aggregates multiple content domains (events, speakers, companies, experts). Audience-aware prioritization would improve relevance without changing the public API.
- **Rough effort:** medium
- **Source link(s):**
  - https://support.microsoft.com/en-us/office/target-content-to-a-specific-audience-on-a-sharepoint-site-68113d1b-be99-4d4c-a61c-73b087f48a81
  - https://support.microsoft.com/en-us/office/use-the-highlighted-content-web-part-e34199b0-ff1a-47fb-8f4d-dbcaed329efd

## 2) Structured Data Output for Events
- **Feature name:** Structured Data Output for Events
- **Short description:** Add optional JSON-LD (`Event`) markup for landing and event teaser sections with safe defaults and validation-friendly fields.
- **Why it fits this plugin:** The plugin already renders event teasers and search results; structured data can improve discoverability in search engines without changing visual rendering.
- **Rough effort:** medium
- **Source link(s):**
  - https://developers.google.com/search/docs/appearance/structured-data/event
  - https://schema.org/Event

## 3) Search Facets and Result Group Filters
- **Feature name:** Search Facets and Result Group Filters
- **Short description:** Add non-breaking optional filters (type, location, date relevance) on the dedicated network search page.
- **Why it fits this plugin:** The plugin already has grouped multi-entity search; facets would make larger datasets easier to navigate while preserving current routes and output patterns.
- **Rough effort:** medium
- **Source link(s):**
  - https://a11y-guidelines.orange.com/en/articles/search-results-page/
  - https://a11y-examples.com/examples/search-input/

## 4) Spotlight Personalization Rules
- **Feature name:** Spotlight Personalization Rules
- **Short description:** Add configurable spotlight ranking signals (freshness, partner priority, section weighting) with deterministic fallbacks.
- **Why it fits this plugin:** The plugin already rotates spotlight items from multiple data sources; ranking rules would improve content quality and editorial control.
- **Rough effort:** low
- **Source link(s):**
  - https://support.microsoft.com/en-us/office/use-the-highlighted-content-web-part-e34199b0-ff1a-47fb-8f4d-dbcaed329efd

## 5) Keyboard-first Search Interaction Enhancements
- **Feature name:** Keyboard-first Search Interaction Enhancements
- **Short description:** Add optional keyboard navigation patterns and richer live status announcements for advanced search interactions.
- **Why it fits this plugin:** The plugin already has a dedicated search interface; keyboard-first improvements increase accessibility and quality for power users.
- **Rough effort:** low
- **Source link(s):**
  - https://webaim.org/techniques/keyboard/
  - https://www.w3.org/WAI/ARIA/apg/
