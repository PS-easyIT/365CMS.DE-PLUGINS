# CMS Booking - Feature Suggestions (Research)

## 1) Smart Waitlist and Auto Backfill
- **Feature name:** Smart waitlist with automatic slot backfill
- **Short description:** When a booking is cancelled, automatically offer the slot to the next waitlisted user with a defined response timeout.
- **Why it fits this plugin:** `cms-booking` already has availability, cancellations, and status handling, so waitlist promotion is a natural extension without changing current booking flows.
- **Rough effort:** Medium
- **Status:** skipped
- **Reason:** Hoher Integrationsaufwand (neue Waitlist-Datenmodelle, Promotion-Workflows, Timeout/Retry-Handling) bei höherem Risiko für bestehende Buchungsflüsse.
- **Source link(s):**
  - https://schedulingkit.com/guides/waitlist-management-guide
  - https://bookcessful.com/en/guides/best-practices-for-booking-waitlists

## 2) Multi-Step Reminder Sequence with Confirmation Actions
- **Feature name:** Automated reminder cadence with confirm/cancel/reschedule actions
- **Short description:** Send reminders at configurable times (for example 48h/24h/1h) and let users confirm, cancel, or reschedule directly from the message.
- **Why it fits this plugin:** The plugin already sends booking emails; this adds no-show reduction and schedule reliability while reusing existing notification and status logic.
- **Rough effort:** Medium
- **Status:** skipped
- **Reason:** Für eine vollständige Umsetzung fehlen robuste Scheduler-/Queue-Bausteine und persistente Action-Links; wurde zugunsten schnellerer Kernverbesserungen zurückgestellt.
- **Source link(s):**
  - https://www.apptoto.com/best-practices/appointment-confirmation-software
  - https://schedulingkit.com/appointment-reminders

## 3) Recurring Appointments (Series + Single Occurrence Exceptions)
- **Feature name:** Recurring booking series with per-occurrence overrides
- **Short description:** Allow users/admins to create weekly or monthly recurring bookings while still editing/cancelling a single occurrence.
- **Why it fits this plugin:** Many booking domains need repeated sessions; the existing booking/services model can be extended with a series table and generated instances.
- **Rough effort:** High
- **Status:** skipped
- **Reason:** Aufwand/Komplexität (Serienmodell, Ausnahmeinstanzen, Konfliktlogik) ist deutlich höher als Medium und nicht als Low/Medium-Effort umsetzbar.
- **Source link(s):**
  - https://wimonder.dev/posts/adding-recurrence-to-your-application/
  - https://cal.com/docs/llms-full.txt

## 4) Timezone and DST-Safe Scheduling UX
- **Feature name:** Timezone-aware booking display and DST-safe slot generation
- **Short description:** Keep schedule storage in UTC, keep provider timezone metadata, and reliably render booking times in viewer timezone across DST transitions.
- **Why it fits this plugin:** `cms-booking` already stores date/time and timezone-related settings; hardening timezone handling improves reliability for international users.
- **Rough effort:** Medium
- **Status:** skipped
- **Reason:** Vollständige DST-/Viewer-Timezone-UX erfordert tiefere Umbauten in Slot-Generierung und Anzeige; nur punktuell verbessert über provider-timezone-basierte Lead-Time-Prüfung.
- **Source link(s):**
  - https://docs.thebookingkit.dev/core-concepts/timezone-handling/
  - https://dev.wix.com/docs/api-reference/business-solutions/bookings/about-time-zones?apiView=SDK

## 5) Configurable Lead Time and Daily Booking Limits
- **Feature name:** Lead-time rules and daily capacity caps
- **Short description:** Add rules like "minimum notice before booking" and "max meetings per day" per service/provider.
- **Why it fits this plugin:** The plugin already includes advance booking windows and per-service settings; this feature extends operational control without changing public API patterns.
- **Rough effort:** Low to Medium
- **Status:** implemented
- **Reason:** Vollständig mit hohem Nutzen umgesetzt: globale Min-Notice (Stunden), globales Tageslimit, service-spezifisches Tageslimit, Enforcements in Slots-API + Formular-Submit + verfügbare Datumsliste.
- **Source link(s):**
  - https://schedulingkit.com/features/buffer-time
  - https://www.plutio.com/features/scheduler-booking-buffers

## 6) Queue-Based Scheduled Notification Jobs
- **Feature name:** Queue/worker based scheduled notifications
- **Short description:** Move reminder and status notification scheduling to a resilient job queue/cron worker instead of request-time execution.
- **Why it fits this plugin:** Improves reliability and scalability for reminders and future automations while keeping frontend output unchanged.
- **Rough effort:** Medium
- **Status:** skipped
- **Reason:** Queue-/Worker-Einführung benötigt zusätzliche Infrastruktur (Jobs, Retry/Dead-Letter, Monitoring) und ist für den aktuellen Iterationsumfang zu groß.
- **Source link(s):**
  - https://aunimeda.com/blog/how-to-build-an-online-booking-system
  - https://wimonder.dev/posts/adding-recurrence-to-your-application/
