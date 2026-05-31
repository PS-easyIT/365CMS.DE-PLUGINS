# CMS Experts Feature Ideas (Research Only)

## 1) Verified Certification Badges
- **Feature name:** Verified certification badges
- **Short description:** Allow experts to attach externally verifiable certifications (for example via Credly-issued badge IDs) and show a "Verified" marker after background validation.
- **Why it fits this plugin:** Trust and profile quality are central for expert directories; verified credentials increase conversion and reduce fake-profile risk.
- **Rough effort:** Medium
- **Source link(s):**
  - https://www.credly.com/docs/web_service_api
  - https://docs.credly.com/browse/reference/get_v1-organizations-organization-id-badges-badge-id

## 2) Calendar-Based Availability Sync
- **Feature name:** Calendar-based availability sync
- **Short description:** Sync expert availability states from external calendars using free/busy APIs, then map results into `available` / `limited` / `booked`.
- **Why it fits this plugin:** The plugin already uses availability as a core field; automation would keep profile status accurate with less manual maintenance.
- **Rough effort:** Medium
- **Source link(s):**
  - https://developers.google.com/calendar/api/v3/reference/freebusy/query

## 3) Semantic Expert Matching
- **Feature name:** Semantic expert matching
- **Short description:** Add semantic search over profiles (skills, bio, projects) so users can search by intent ("M365 migration architect") instead of exact keywords.
- **Why it fits this plugin:** Expert discovery quality is the main product value; semantic retrieval improves matching for real-world natural-language queries.
- **Rough effort:** High
- **Source link(s):**
  - https://learn.microsoft.com/en-us/azure/search/vector-search-overview
  - https://learn.microsoft.com/en-us/azure/search/hybrid-search-how-to-query

## 4) Structured Profile SEO (Person/ProfilePage)
- **Feature name:** Structured profile SEO metadata
- **Short description:** Emit Schema.org `Person` (and optionally `ProfilePage`) JSON-LD on expert detail pages with safe social/profile links.
- **Why it fits this plugin:** Experts are person-entities; structured data can improve discoverability and authority signals in search.
- **Rough effort:** Low
- **Source link(s):**
  - https://schema.org/Person

## 5) Secure Portfolio Attachment Workflow
- **Feature name:** Secure portfolio attachment workflow
- **Short description:** Add optional document uploads (CV, certificates, case-study PDFs) with strict allowlists, signature checks, and private storage access links.
- **Why it fits this plugin:** Expert profiles benefit from proof documents, but this requires hardened upload handling to stay secure.
- **Rough effort:** High
- **Source link(s):**
  - https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html
  - https://owasp.org/www-community/vulnerabilities/Unrestricted_File_Upload
