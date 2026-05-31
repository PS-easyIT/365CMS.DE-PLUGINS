# CMS Promos - Feature Suggestions

## 1) A/B Promo Variant Testing
- **Feature name:** A/B Promo Variant Testing
- **Short description:** Allow one promo slot to rotate between two or more variants and report winner metrics (CTR, click count, conversion proxy) after a minimum sample size.
- **Why it fits this plugin:** The plugin already manages placements, priorities, and click/impression tracking; variant testing builds directly on existing promo entities and data.
- **Rough effort (low/medium/high):** High
- **Source link(s):**
  - [Google Optimize replacement guidance via GA4 experimentation concepts](https://support.google.com/analytics/answer/10917952)
  - [A/B testing campaign best practices (single-variable, significance)](https://www.bluecore.com/blog/email-marketing-a-b-testing/)

## 2) Frequency Capping Per Promo
- **Feature name:** Frequency Capping Per Promo
- **Short description:** Limit how often the same visitor sees a promo within a time window (for example per day/week) to reduce fatigue and wasted impressions.
- **Why it fits this plugin:** CMS Promos already records impressions and serves promos by placement; adding per-user cap logic would improve delivery quality without changing editorial workflows.
- **Rough effort (low/medium/high):** Medium
- **Source link(s):**
  - [Frequency capping basics and anti-fatigue rationale](https://perion.com/glossary/frequency-capping/)
  - [Frequency trend as performance signal](https://adlibrary.com/posts/how-to-analyze-ad-performance)

## 3) Built-in UTM URL Builder
- **Feature name:** Built-in UTM URL Builder
- **Short description:** Add a helper in promo edit forms that generates consistent UTM-tagged target URLs from a controlled naming scheme.
- **Why it fits this plugin:** Promos are click-driving assets; standardized campaign parameters would improve attribution quality for the tracked outgoing links.
- **Rough effort (low/medium/high):** Low
- **Source link(s):**
  - [GA4 campaign URL and UTM parameter guidance](https://support.google.com/analytics/answer/10917952)
  - [UTM consistency pitfalls and naming guidance](https://cutt.ly/resources/blog/how-to-use-utm-parameters-2026)

## 4) Outbound Click Event Export (GA4-friendly)
- **Feature name:** Outbound Click Event Export (GA4-friendly)
- **Short description:** Add optional structured event output/webhook payloads for promo clicks (link URL, placement, promo slug) so analytics pipelines can ingest data reliably.
- **Why it fits this plugin:** The plugin already tracks click/impression counters but lacks analytics interoperability beyond internal totals.
- **Rough effort (low/medium/high):** Medium
- **Source link(s):**
  - [GA4 outbound click measurement tutorial](https://support.google.com/analytics/answer/13566436?hl=en)
  - [GA4 outbound click limitations and custom dimension workflow](https://clickport.io/blog/track-outbound-links-ga4)

## 5) Audience-Segmented Promo Rules
- **Feature name:** Audience-Segmented Promo Rules
- **Short description:** Add rule-based targeting (e.g., route, referrer, device, or user segment) so different promo sets can be served to different audiences.
- **Why it fits this plugin:** Placements and priorities already exist; segmentation would improve relevance and CTR by making promo delivery context-aware.
- **Rough effort (low/medium/high):** High
- **Source link(s):**
  - [Personalization at scale and dynamic segmentation](https://www.bannerflow.com/blog/the-beginners-guide-to-personalization-at-scale)
  - [Audience segmentation and CTR impact](https://webengage.com/blog/boost-your-ctrs-unveiling-7-effective-strategies-for-audience-segmentation-in-bfsi/)
