# Feature Suggestions for CMS Companies

## 1) Verified Company Claim Workflow
- **Feature name:** Verified Company Claim Workflow
- **Short description:** Add a claim/ownership process where company representatives can claim an existing profile and complete verification steps (email/domain/doc checks) before getting a visible verified badge.
- **Why it fits this plugin:** The plugin already supports company management and admin approvals; ownership verification would improve trust and data quality without changing the core listing model.
- **Rough effort (low/medium/high):** High
- **Source link(s):**
  - [How To Create An Online Directory For 'Claim Your Profile' Functionality](https://turnkeydirectories.com/create-online-directory-claim-profile-functionality/)

## 2) Organization + LocalBusiness Structured Data
- **Feature name:** Organization + LocalBusiness Structured Data
- **Short description:** Generate JSON-LD (`Organization`/`LocalBusiness` or more specific subtypes) for company detail pages to improve machine readability and SEO eligibility.
- **Why it fits this plugin:** The plugin already has rich company profile data (name, contact, location, website), which maps directly to structured data fields.
- **Rough effort (low/medium/high):** Medium
- **Source link(s):**
  - [Google Search Central: Local Business structured data](https://developers.google.com/search/docs/appearance/structured-data/local-business)
  - [Schema.org LocalBusiness](https://schema.org/LocalBusiness)

## 3) Advanced Faceted Search (Server-Side)
- **Feature name:** Advanced Faceted Search (Server-Side)
- **Short description:** Add faceted filtering with precomputed facet counts and optional search-engine integration (for larger datasets) while keeping current URL/filter behavior.
- **Why it fits this plugin:** The archive already exposes filters (industry/city/partner/search); faceted search would scale better and improve findability as listings grow.
- **Rough effort (low/medium/high):** Medium to High
- **Source link(s):**
  - [Meilisearch: Faceted Search](https://www.meilisearch.com/products/faceted-search)
  - [Algolia: Faceted Search Overview](https://www.algolia.com/blog/ux/faceted-search-an-overview)

## 4) Address Geocoding + Radius Search
- **Feature name:** Address Geocoding + Radius Search
- **Short description:** Enrich companies with coordinates and allow “near me” / radius filters, ideally using a self-hosted geocoding stack for privacy and policy control.
- **Why it fits this plugin:** Company profiles already contain city/ZIP/country fields; geocoding is a natural extension that enables geographic discovery.
- **Rough effort (low/medium/high):** High
- **Source link(s):**
  - [OpenStreetMap Nominatim usage policy note (via GIS StackExchange)](https://gis.stackexchange.com/questions/120570/how-to-implement-place-auto-complete-using-nominatim)
  - [Photon (OpenStreetMap-based geocoder)](https://github.com/komoot/photon)
  - [Pelias](https://pelias.io/)

