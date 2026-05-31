# CMS Contact - Feature Ideas

## 1) Privacy-first CAPTCHA providers (pluggable)
- **Feature name:** Privacy-first CAPTCHA providers (pluggable)
- **Short description:** Add optional adapters for privacy-focused anti-bot providers (e.g. Friendly Captcha / ALTCHA) in addition to the current built-in protections.
- **Why it fits this plugin:** `cms-contact` already has anti-spam controls (honeypot, captcha, rate limiting). A provider adapter extends this without changing existing public routes or form APIs.
- **Rough effort:** Medium
- **Source link(s):**
  - [ALTCHA - privacy-first CAPTCHA](https://altcha.org/)
  - [Best CAPTCHA providers overview](https://www.geetest.com/en/article/best-captcha-providers)

## 2) Accessible error summary block (WCAG-friendly)
- **Feature name:** Accessible error summary block
- **Short description:** Show an optional top-level error summary linking to invalid fields, while keeping field-level errors via `aria-describedby` and `aria-invalid`.
- **Why it fits this plugin:** The plugin already renders inline field errors and ARIA attributes; an error summary improves accessibility for complex forms and aligns with WCAG guidance.
- **Rough effort:** Low
- **Source link(s):**
  - [W3C ARIA21 technique](https://www.w3.org/WAI/WCAG21/Techniques/aria/ARIA21)
  - [Mass.gov ARIA error communication guidance](https://www.mass.gov/info-details/coding-web-applications-using-advanced-aria-techniques)

## 3) Optional double opt-in workflow
- **Feature name:** Optional double opt-in workflow
- **Short description:** Add an optional verification step (token or code) before storing/processing submissions for selected forms or selected form purposes.
- **Why it fits this plugin:** Useful for high-risk or compliance-sensitive forms and can be opt-in per form without breaking existing contact workflows.
- **Rough effort:** High
- **Source link(s):**
  - [GDPR contact form requirements and DOI context](https://www.riddle.com/blog/use-cases/data-protection-in-contact-forms-requirements-tips-and-templates/)
  - [GDPR compliance implementation guide](https://www.contactformtoapi.com/gdpr-compliant-contact-form-api-integration-guide/)

## 4) Delivery webhooks with retry queue
- **Feature name:** Delivery webhooks with retry queue
- **Short description:** Allow optional outbound webhooks (CRM/helpdesk/automation) with signed payloads, retry/backoff, and delivery logs.
- **Why it fits this plugin:** Many contact form deployments forward submissions to external systems. Queue + retries avoids data loss and decouples external failures from form submit UX.
- **Rough effort:** Medium
- **Source link(s):**
  - [OWASP Logging Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html)
  - [OWASP Business Logic Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Business_Logic_Security_Cheat_Sheet.html)

## 5) Security events dashboard (admin-only)
- **Feature name:** Security events dashboard
- **Short description:** Add an admin-only view for rate-limit hits, CSRF failures, invalid status attempts, and antispam rejections with lightweight aggregation.
- **Why it fits this plugin:** The plugin performs multiple security checks already; surfacing trends helps operators detect abuse earlier.
- **Rough effort:** Medium
- **Source link(s):**
  - [OWASP Bot Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Bot_Management_and_Anti-Automation_Cheat_Sheet.html)
  - [OWASP Logging Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html)

## 6) Retention policy presets per form
- **Feature name:** Retention policy presets per form
- **Short description:** Define retention windows at form level (e.g. 30/90/180/365 days) with automated cleanup schedule and audit-friendly reporting.
- **Why it fits this plugin:** The plugin already supports cleanup actions globally. Per-form retention is a natural extension for compliance and operational control.
- **Rough effort:** Medium
- **Source link(s):**
  - [Contact form GDPR retention examples](https://trustyourwebsite.com/eu/en/guides/contact-form-gdpr)
  - [GDPR form compliance guidance](https://ivyforms.com/blog/how-to-create-gdpr-compliant-forms/)
