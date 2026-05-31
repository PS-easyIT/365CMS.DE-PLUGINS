# Feature Ideas for CMS Downloads

## Feature name
Time-limited signed download links

### Short description
Generate temporary, cryptographically signed download URLs that expire after a configurable time window.

### Why it fits this plugin
The plugin already serves downloads through a controlled endpoint, so adding expiring signatures is a natural extension for private or premium file delivery.

### Rough effort (low/medium/high)
Medium

### Source link(s)
- https://codeboxr.com/creating-signed-urls-in-wordpress-using-nonces-and-custom-hmac/
- https://deliciousbrains.com/protecting-your-wordpress-media-private-files/

---

## Feature name
Configurable download rate limiting

### Short description
Limit repeated download requests per IP or user over a short period and return a standard throttling response when limits are exceeded.

### Why it fits this plugin
The plugin exposes public file routes and can benefit from basic abuse protection against scraping and automated mass downloads.

### Rough effort (low/medium/high)
Medium

### Source link(s)
- https://wphealthkit.com/blog/wordpress-rest-api-rate-limiting

---

## Feature name
Advanced download analytics export

### Short description
Provide richer analytics (time range, category/type segmentation, top files) and CSV export for reporting.

### Why it fits this plugin
The plugin already stores download counters; structured analytics and export are a direct functional upgrade for content owners.

### Rough effort (low/medium/high)
Low to medium

### Source link(s)
- https://www.wpdownloadmanager.com/doc/core-features-download-tracking-statistics/
- https://download-monitor.com/

---

## Feature name
Versioned file releases per download item

### Short description
Allow multiple file versions for one download entry and serve the latest as default while keeping older versions accessible in history.

### Why it fits this plugin
The plugin already tracks metadata (version label, type, status), so adding release history would improve lifecycle management without changing the public route model.

### Rough effort (low/medium/high)
Medium to high

### Source link(s)
- https://download-monitor.com/

---

## Feature name
Optional malware scan integration on upload

### Short description
Introduce an optional post-upload scanning step (for example ClamAV or external scanner API) before a file becomes publicly downloadable.

### Why it fits this plugin
This plugin accepts file uploads from admins; malware scanning adds another defense layer beyond extension and MIME checks.

### Rough effort (low/medium/high)
High

### Source link(s)
- https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html
- https://owasp.org/www-project-web-security-testing-guide/latest/4-Web_Application_Security_Testing/10-Business_Logic_Testing/09-Test_Upload_of_Malicious_Files

---

## Feature name
Short-lived one-click external redirect tokens

### Short description
When external downloads are used, protect the "continue" step with a short-lived token so direct replay of the bypass URL is reduced.

### Why it fits this plugin
The plugin already includes an external redirect notice flow; tokenizing the confirm step strengthens this flow without changing frontend architecture.

### Rough effort (low/medium/high)
Low to medium

### Source link(s)
- https://codeboxr.com/creating-signed-urls-in-wordpress-using-nonces-and-custom-hmac/
- https://saucecode.au/articles/secure-file-downloads-on-wordpress-proxy-streaming-vs-direct-links
