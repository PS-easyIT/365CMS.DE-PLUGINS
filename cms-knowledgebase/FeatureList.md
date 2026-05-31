# CMS Knowledgebase - Feature Suggestions (Research Only)

## 1) Zero-Result Search Insights Dashboard
- **Feature name:** Zero-Result Search Insights Dashboard
- **Short description:** Track knowledgebase searches that return no results (query, count, date range), then highlight top missing topics.
- **Why it fits this plugin:** The plugin already offers search/filter flows on `/kb` and `/glossar`; this adds a content-gap feedback loop without changing current rendering behavior.
- **Rough effort:** medium
- **Status:** skipped
- **Reason:** Benötigt neue Telemetrie-Tabellen plus Admin-Dashboard (nicht Public-only) und wäre im aktuellen Scope zu invasiv.
- **Source link(s):**
  - https://wpsi.io/docs/understanding-and-acting-on-zero-result-searches/
  - https://www.supportbench.com/portal-search-best-practices-knowledge-base-search-work/

## 2) "Was this article helpful?" Feedback Capture
- **Feature name:** Article Helpfulness Feedback
- **Short description:** Add optional thumbs-up/thumbs-down feedback per entry and store aggregate signals for editorial prioritization.
- **Why it fits this plugin:** This plugin manages curated glossary/knowledge articles; lightweight usefulness feedback helps keep entries accurate and actionable.
- **Rough effort:** medium
- **Status:** skipped
- **Reason:** Erfordert zusätzliche write-endpoints, Abuse-Schutz und Auswertungsoberfläche; nicht sinnvoll als reine Public-Ausgabeänderung.
- **Source link(s):**
  - https://www.atlassian.com/itsm/knowledge-management/self-service-success
  - https://support.atlassian.com/jira-service-management-cloud/docs/write-and-share-knowledge-base-articles/

## 3) Synonym Management Assistant
- **Feature name:** Synonym Suggestion Assistant
- **Short description:** Suggest synonym candidates from failed/rare search terms to improve matching quality in auto-linking and archive search.
- **Why it fits this plugin:** The plugin already supports synonyms per entry; this feature would improve discoverability using real usage signals.
- **Rough effort:** medium
- **Status:** skipped
- **Reason:** Baut auf Search-Analytics und Admin-Review-Workflow auf; ohne die vorherigen Grundlagen nicht robust umsetzbar.
- **Source link(s):**
  - https://wpsi.io/docs/understanding-and-acting-on-zero-result-searches/
  - https://www.supportbench.com/portal-search-best-practices-knowledge-base-search-work/

## 4) Optional Typo-Tolerant Search Mode
- **Feature name:** Typo-Tolerant Search Mode
- **Short description:** Introduce optional typo-tolerance levels (`default`, `strict`) for public search queries to reduce no-hit results from misspellings.
- **Why it fits this plugin:** The plugin serves glossary-style terminology where spelling variants are common; typo handling improves findability.
- **Rough effort:** medium
- **Status:** implemented
- **Reason:** Public-Filter um `search_mode` (`default`/`strict`) erweitert; `default` nutzt tolerantere Matching-Logik inkl. SOUNDEX-Tokens, `strict` bleibt enger.
- **Source link(s):**
  - https://algolia.com/doc/guides/managing-results/optimize-search-results/typo-tolerance
  - https://algolia.com/doc/api-reference/api-parameters/typoTolerance

## 5) Structured Data Export for Entries
- **Feature name:** Structured Data (FAQPage/DefinedTerm) Output
- **Short description:** Optionally emit machine-readable structured data for glossary-like entries to improve downstream indexing and AI retrieval.
- **Why it fits this plugin:** The plugin is glossary-centric; structured metadata aligns with its semantic content model and can be feature-flagged.
- **Rough effort:** low to medium
- **Status:** implemented
- **Reason:** Eintragsseiten emittieren jetzt JSON-LD (`DefinedTerm` + `FAQPage`) und respektieren ein optionales Feature-Flag (`enable_structured_data`).
- **Source link(s):**
  - https://schema.org/FAQPage
  - https://developers.google.cn/search/docs/appearance/structured-data/faqpage

## 6) Stale Content Audit Indicators
- **Feature name:** Stale Entry Audit Indicators
- **Short description:** Show "last reviewed" and "needs review" markers in admin lists based on age and update activity.
- **Why it fits this plugin:** Keeps glossary entries trustworthy over time and supports content governance in the existing admin workflow.
- **Rough effort:** low
- **Status:** skipped
- **Reason:** Rein administrativer Listen-Use-Case, keine Public-Output-Funktion; bewusst aus Scope genommen.
- **Source link(s):**
  - https://www.atlassian.com/itsm/knowledge-management/self-service-success
  - https://support.atlassian.com/jira-service-management-cloud/docs/write-and-share-knowledge-base-articles/
