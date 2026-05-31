# Feature Suggestions for CMS JobProfile Generator

## 1) Structured Interview Scorecards
- **Feature name:** Structured Interview Scorecards
- **Short description:** Add configurable scorecards with role-specific criteria, mandatory evidence notes, and weighted final scoring for applicant evaluations.
- **Why it fits this plugin:** The plugin already manages applications, workflows, and approvals; structured scorecards would improve decision quality while staying inside the current hiring flow.
- **Rough effort (low/medium/high):** Medium
- **Status:** skipped
- **Why:** Erfordert neue Datenmodelle/UI-Flows für Bewertungsbögen und Auswertungslogik pro Bewerbung; im aktuellen Scope zugunsten von höherem Nutzen pro Aufwand zurückgestellt.
- **Source link(s):**
  - [OPM – Structured Interviews](https://www.opm.gov/policy-data-oversight/assessment-and-selection/other-assessment-methods/structured-interviews)
  - [MSPB – The Federal Selection Interview](https://www.mspb.gov/studies/studies/The_Federal_Selection_Interview_Unrealized_Potential_253635.pdf)

## 2) Candidate Self-Scheduling for Interviews
- **Feature name:** Candidate Self-Scheduling
- **Short description:** Let candidates pick interview slots from recruiter/interviewer availability links, with optional rescheduling windows and reminder notifications.
- **Why it fits this plugin:** It reduces coordination overhead after application intake and fits naturally into the existing application status workflow.
- **Rough effort (low/medium/high):** High
- **Status:** skipped
- **Why:** Hoher Integrationsaufwand (Kalender-/Slot-Management, Konfliktlösung, E-Mail-Flows) und daher explizit nicht im low/medium-Umfang.
- **Source link(s):**
  - [Greenhouse – Candidate self-scheduling overview](https://support.greenhouse.io/hc/en-us/articles/4409534663579-Candidate-self-scheduling-overview)
  - [Calendly – Candidate self-scheduling benefits](https://calendly.com/blog/5-ways-self-scheduling-makes-the-interview-process-more-efficient)

## 3) JobPosting Quality and Compliance Monitor
- **Feature name:** JobPosting Schema Quality Monitor
- **Short description:** Add automated checks for required JobPosting structured-data fields, remote-job fields, and stale posting detection, then surface issues in admin health/audit pages.
- **Why it fits this plugin:** The plugin already generates public job pages and JSON-LD; proactive validation protects discoverability without changing public rendering behavior.
- **Rough effort (low/medium/high):** Medium
- **Status:** implemented
- **Why:** Als "JobPosting Quality Monitor" im Admin-Dashboard umgesetzt (automatische Checks auf Slug, Pflichtinhalte, Standortkonsistenz, Gehaltslücken, veraltete Stellen inkl. direkter Bearbeitungslinks).
- **Source link(s):**
  - [Google Search Central – JobPosting structured data](https://developers.google.com/search/docs/appearance/structured-data/job-posting)
  - [Google Search Central – Structured data intro](https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data)

## 4) Indexing API Sync for Job Lifecycle
- **Feature name:** Google Indexing API Sync
- **Short description:** Send URL update/delete notifications when jobs are published, updated, or removed to speed up index refresh for job postings.
- **Why it fits this plugin:** This plugin controls the job lifecycle and can trigger indexing calls at exactly the right lifecycle events.
- **Rough effort (low/medium/high):** Medium
- **Status:** skipped
- **Why:** Benötigt externe API-Anbindung inkl. Auth/Secrets-Management und robuste Retry-/Fehlerlogik; bewusst nicht ohne gesicherten Integrationsrahmen eingeführt.
- **Source link(s):**
  - [Google Search Central – Indexing API usage](https://developers.google.com/search/apis/indexing-api/v3/using-api)
  - [Google Search Central – JobPosting guidance (Indexing API recommendation)](https://developers.google.com/search/docs/appearance/structured-data/job-posting)

## 5) Hiring Funnel Bottleneck Alerts
- **Feature name:** Hiring Funnel Bottleneck Alerts
- **Short description:** Add stage-aging metrics and threshold alerts (for example, applications stuck in review for too long) in the admin dashboard.
- **Why it fits this plugin:** The plugin already stores workflow/audit events; turning these into actionable alerts is a direct extension of current data.
- **Rough effort (low/medium/high):** Medium
- **Status:** implemented
- **Why:** Als "Hiring Funnel Bottleneck Alerts" im Dashboard umgesetzt (Stage-Aging für `new`/`reviewing`, Schwellenwerte und priorisierte Alert-Liste pro Bewerbung/Job).
- **Source link(s):**
  - [AIHR – Recruitment dashboard fundamentals](https://www.aihr.com/blog/recruitment-dashboard/)
  - [Treegarden – Recruitment analytics dashboard guide](https://treegarden.io/blog/recruitment-analytics-dashboard-guide/)

## 6) Silver-Medalist Talent Pool Re-Engagement
- **Feature name:** Silver-Medalist Talent Pool
- **Short description:** Tag near-finalist candidates and add follow-up reminders/templates to re-engage them when similar roles open.
- **Why it fits this plugin:** The plugin already captures applicant history and statuses, making it practical to build a reusable internal candidate pool.
- **Rough effort (low/medium/high):** Medium
- **Status:** skipped
- **Why:** Vollständige Umsetzung benötigt neue Talent-Pool-Entitäten, Re-Engagement-Templates und Reminder-Workflows; für diesen Durchlauf zugunsten unmittelbarer Dashboard-Mehrwerte zurückgestellt.
- **Source link(s):**
  - [LinkedIn Talent – Recruiting Silver Medalists](https://www.linkedin.com/business/talent/blog/talent-acquisition/recruiting-silver-medalists)
  - [SHRM – Re-engage top candidates](https://www.shrm.org/topics-tools/news/talent-acquisition/how-to-rediscover-and-re-engage-top-candidates)
