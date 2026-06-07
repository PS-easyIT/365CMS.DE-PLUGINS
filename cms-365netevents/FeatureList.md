# CMS Events - Feature Ideas

## 1) RSVP + Waitlist Workflow
- **Feature name:** RSVP and waitlist management
- **Short description:** Add attendee RSVP states (going/maybe/cancelled) and automatic waitlist promotion when capacity frees up.
- **Why it fits this plugin:** The plugin already models capacity and registration URLs, so waitlist logic is a natural extension for event lifecycle management.
- **Rough effort (low/medium/high):** Medium
- **Source link(s):**
  - https://wordpress.org/plugins/events-manager/
  - https://co.wordpress.org/plugins/rsvp/

## 2) Reminder Notifications (Email + Optional Webhook)
- **Feature name:** Scheduled attendee reminders
- **Short description:** Send configurable reminders before event start (e.g., 24h and 1h), with optional webhook trigger for external notification systems.
- **Why it fits this plugin:** Improves attendance reliability for events already created in the plugin, without changing core public rendering.
- **Rough effort (low/medium/high):** Medium
- **Source link(s):**
  - https://wordpress.org/plugins/events-manager/
  - https://wordpress.org/plugins/evenzo-events-manager/

## 3) QR Check-in for In-Person Events
- **Feature name:** QR-based check-in
- **Short description:** Generate per-attendee QR tokens and provide an admin/mobile check-in endpoint with timestamped attendance logs.
- **Why it fits this plugin:** Complements existing event detail and capacity features with operational event-day tooling.
- **Rough effort (low/medium/high):** High
- **Source link(s):**
  - https://ticketpaygo.io/
  - https://wordpress.org/plugins/evenzo-events-manager/

## 4) Recurring Event Series
- **Feature name:** Recurring event support
- **Short description:** Allow weekly/monthly recurrence patterns with exception dates and generated child instances.
- **Why it fits this plugin:** Many event programs are recurring; this prevents repetitive manual creation and improves calendar completeness.
- **Rough effort (low/medium/high):** High
- **Source link(s):**
  - https://wordpress.org/plugins/events-manager/
  - https://correctics.com/help/generate-correct-ics-files/

## 5) Subscription Calendar Feed (webcal)
- **Feature name:** Live iCal/webcal subscription feed
- **Short description:** Add a feed URL for users to subscribe to continuously updated event calendars instead of one-off `.ics` downloads.
- **Why it fits this plugin:** The plugin already exports ICS per event; feed subscriptions are the next step for long-term engagement.
- **Rough effort (low/medium/high):** Medium
- **Source link(s):**
  - https://www.calen.events/blog/ics-file-calendar-integration-guide
  - https://www.kanzaki.com/docs/ical/vtimezone.html

## 6) Event Structured Data (Schema.org JSON-LD)
- **Feature name:** Event schema markup automation
- **Short description:** Emit valid `Event` JSON-LD on event detail pages (including attendance mode, status, offers if available).
- **Why it fits this plugin:** Improves discoverability in search engines for existing public event pages without changing visible UI.
- **Rough effort (low/medium/high):** Low to medium
- **Source link(s):**
  - https://developers.google.cn/search/docs/appearance/structured-data/event
  - https://schema.org/Event
