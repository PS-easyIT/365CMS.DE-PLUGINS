# Feature Backlog Suggestions (Research-Based)

## 1) RFC 8058 One-Click Unsubscribe Headers
- **Feature name:** RFC 8058 One-Click Unsubscribe support
- **Short description:** Add `List-Unsubscribe` and `List-Unsubscribe-Post` headers for outbound marketing emails and support automated POST-based unsubscribes.
- **Why it fits this plugin:** The plugin already manages subscribers and unsubscribe tokens; this extends compliance and deliverability for larger send volumes.
- **Rough effort:** medium
- **Status:** implemented
- **Reason:** POST-basierter One-Click-Unsubscribe-Endpunkt (`/newsletter/unsubscribe/:token` und `/en/...`) wurde ergänzt; zusätzlich gibt es eine interne Header-API (`get_rfc8058_unsubscribe_headers`) mit `List-Unsubscribe` und `List-Unsubscribe-Post`.
- **Source link(s):**
  - https://www.rfc-editor.org/rfc/rfc8058
  - https://support.google.com/mail/answer/81126

## 2) Preference Center (Frequency + Topic Opt-Down)
- **Feature name:** Subscriber preference center
- **Short description:** Let subscribers keep receiving emails but reduce frequency or select topic categories instead of fully unsubscribing.
- **Why it fits this plugin:** Existing segment support can power preference-based sends and reduce full-list churn.
- **Rough effort:** medium
- **Status:** skipped
- **Reason:** Vollständige Umsetzung benötigt neue persistente Datenstruktur (Frequenz/Topics), zusätzliche Admin-Workflows und Versandlogik; in diesem Durchlauf zugunsten stabiler, abgeschlossener Compliance-/i18n-Features zurückgestellt.
- **Source link(s):**
  - https://www.twilio.com/en-us/blog/insights/the-power-of-an-email-preference-center
  - https://www.litmus.com/blog/email-preferences-center-best-practices

## 3) Subject-Line A/B Testing for Campaigns
- **Feature name:** Subject line split testing
- **Short description:** Send two subject variants to a test subset, pick winner by open rate, then deliver winner to remaining recipients.
- **Why it fits this plugin:** Campaign and template modules already exist; this adds measurable optimization without changing core workflow.
- **Rough effort:** high
- **Status:** skipped
- **Reason:** High-Effort-Feature; erfordert erweitertes Versand-Orchestrierungsmodell, Ergebnisauswertung und automatisches Winner-Rollout.
- **Source link(s):**
  - https://www.salesforce.com/marketing/email/a-b-testing/

## 4) AMP Email Variant with HTML Fallback
- **Feature name:** Dynamic AMP campaign content
- **Short description:** Optionally generate a `text/x-amp-html` MIME part while preserving `text/html` and plain-text fallback.
- **Why it fits this plugin:** Template management can be extended to support interactive newsletters for compatible inboxes.
- **Rough effort:** high
- **Status:** skipped
- **Reason:** High-Effort-Feature mit MIME-/Template-Pipeline-Erweiterung; außerhalb des geforderten low/medium-Scopes.
- **Source link(s):**
  - https://developers.google.com/workspace/gmail/ampemail/testing-dynamic-email
  - https://developers.google.com/workspace/gmail/ampemail/register

## 5) Deliverability Guardrails Dashboard
- **Feature name:** Deliverability hygiene automation
- **Short description:** Add suppression policies for persistent bounces, complaints, and stale inactive subscribers with controlled re-engagement rules.
- **Why it fits this plugin:** The plugin already stores send/subscriber states, making hygiene automation a natural extension.
- **Rough effort:** medium
- **Status:** skipped
- **Reason:** Für eine vollständige, belastbare Automatisierung fehlen im Plugin derzeit Complaint-/Bounce-Ereignisquellen und Re-Engagement-Scheduler; deshalb bewusst nicht halb implementiert.
- **Source link(s):**
  - https://support.google.com/mail/answer/81126
  - https://debounce.com/blog/what-is-suppression-list/

## 6) BIMI Readiness Assistant
- **Feature name:** BIMI/SPF/DKIM/DMARC readiness checks
- **Short description:** Add diagnostics to verify sender-domain authentication prerequisites and BIMI DNS setup guidance.
- **Why it fits this plugin:** Better trust and inbox visibility aligns with newsletter deliverability goals.
- **Rough effort:** medium
- **Status:** implemented
- **Reason:** Compliance-Bereich der Einstellungen enthält nun Sender-Domain-Diagnosen für SPF/DMARC/BIMI (DNS-TXT-Checks) plus DKIM-Hinweis zur selector-basierten Prüfung.
- **Source link(s):**
  - https://datatracker.ietf.org/doc/draft-brand-indicators-for-message-identification/
  - https://support.google.com/mail/answer/81126
