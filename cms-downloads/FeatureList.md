# Feature Ideas for CMS Downloads

## 1) Time-limited signed download links
- **Status:** skipped
- **Reason:** Requires dedicated admin workflow for signed URL generation and expiry management; deferred to avoid half-built premium-delivery UX in this iteration.
- **Feature name:** Time-limited signed download links
- **Short description:** Generate temporary, cryptographically signed download URLs that expire after a configurable time window.
- **Why it fits this plugin:** The plugin already serves downloads through a controlled endpoint, so adding expiring signatures is a natural extension for private or premium file delivery.
- **Rough effort:** medium
- **Source link(s):**
  - https://codeboxr.com/creating-signed-urls-in-wordpress-using-nonces-and-custom-hmac/
  - https://deliciousbrains.com/protecting-your-wordpress-media-private-files/

## 2) Configurable download rate limiting
- **Status:** implemented
- **Implementation:** IP-based rate limiting with configurable max requests and time window; enforced on `/downloads/file/:slug` with HTTP 429 response.
- **Feature name:** Configurable download rate limiting
- **Short description:** Limit repeated download requests per IP or user over a short period and return a standard throttling response when limits are exceeded.
- **Why it fits this plugin:** The plugin exposes public file routes and can benefit from basic abuse protection against scraping and automated mass downloads.
- **Rough effort:** medium
- **Source link(s):**
  - https://wphealthkit.com/blog/wordpress-rest-api-rate-limiting

## 3) Advanced download analytics export
- **Status:** implemented
- **Implementation:** Dashboard analytics panel (top downloads, type/category breakdown) plus CSV export from admin dashboard.
- **Feature name:** Advanced download analytics export
- **Short description:** Provide richer analytics (time range, category/type segmentation, top files) and CSV export for reporting.
- **Why it fits this plugin:** The plugin already stores download counters; structured analytics and export are a direct functional upgrade for content owners.
- **Rough effort:** low to medium
- **Source link(s):**
  - https://www.wpdownloadmanager.com/doc/core-features-download-tracking-statistics/
  - https://download-monitor.com/

## 4) Versioned file releases per download item
- **Status:** skipped
- **Reason:** Medium-to-high effort (version history schema, default/latest routing, admin UI); out of scope for low/medium-effort selection.
- **Feature name:** Versioned file releases per download item
- **Short description:** Allow multiple file versions for one download entry and serve the latest as default while keeping older versions accessible in history.
- **Why it fits this plugin:** The plugin already tracks metadata (version label, type, status), so adding release history would improve lifecycle management without changing the public route model.
- **Rough effort:** medium to high
- **Source link(s):**
  - https://download-monitor.com/

## 5) Optional malware scan integration on upload
- **Status:** skipped
- **Reason:** High effort and external scanner dependency; existing upload validation retained.
- **Feature name:** Optional malware scan integration on upload
- **Short description:** Introduce an optional post-upload scanning step (for example ClamAV or external scanner API) before a file becomes publicly downloadable.
- **Why it fits this plugin:** This plugin accepts file uploads from admins; malware scanning adds another defense layer beyond extension and MIME checks.
- **Rough effort:** high
- **Source link(s):**
  - https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html
  - https://owasp.org/www-project-web-security-testing-guide/latest/4-Web_Application_Security_Testing/10-Business_Logic_Testing/09-Test_Upload_of_Malicious_Files

## 6) Short-lived one-click external redirect tokens
- **Status:** implemented
- **Implementation:** HMAC-signed continue tokens (15 min TTL) for external download confirmation step; bilingual public routes via shared i18n helpers.
- **Feature name:** Short-lived one-click external redirect tokens
- **Short description:** When external downloads are used, protect the "continue" step with a short-lived token so direct replay of the bypass URL is reduced.
- **Why it fits this plugin:** The plugin already includes an external redirect notice flow; tokenizing the confirm step strengthens this flow without changing frontend architecture.
- **Rough effort:** low to medium
- **Source link(s):**
  - https://codeboxr.com/creating-signed-urls-in-wordpress-using-nonces-and-custom-hmac/
  - https://saucecode.au/articles/secure-file-downloads-on-wordpress-proxy-streaming-vs-direct-links
