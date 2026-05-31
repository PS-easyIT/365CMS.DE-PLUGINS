# CMS Feed - Feature Suggestions

## 1) Rule-based Feed Automation
- **Feature name:** Rule-based Feed Automation
- **Short description:** Add configurable IF/THEN rules to auto-tag, auto-hide, auto-highlight, or auto-route incoming items based on title/content/source patterns.
- **Why it fits this plugin:** `cms-feed` already centralizes ingestion and admin management, so rules would reduce manual moderation work in larger feed sets.
- **Rough effort:** medium
- **Status:** ⏳ Backlog – zurückgestellt.
- **Grund:** Hoher Nutzen, aber aktuell nicht umgesetzt, um API/Hooks/Slugs stabil zu halten und zuerst risikoarme Quick-Wins mit direktem Admin-Mehrwert zu liefern.
- **Source link(s):**
  - [Inoreader: Save time with automations](https://www.inoreader.com/blog/2026/01/save-time-with-automations.html)
  - [Inoreader: Streamline content discovery with filters and rules](https://www.inoreader.com/blog/2023/06/streamline-content-discovery-with-filters-and-rules.html)

## 2) Duplicate Story Detection (Near-Dedup)
- **Feature name:** Duplicate Story Detection
- **Short description:** Detect near-duplicate articles across multiple feeds and group or suppress them to reduce noise.
- **Why it fits this plugin:** Feed catalogs often include overlapping sources; dedup would improve signal quality without changing source coverage.
- **Rough effort:** high
- **Status:** ❌ Nicht umgesetzt.
- **Grund:** High-Effort-Feature (Clustering/Fingerprints) außerhalb des angeforderten low/medium-Scopes.
- **Source link(s):**
  - [News Aggregator System Design (dedup with clustering concepts)](https://crackingwalnuts.com/post/news-aggregator-system-design)
  - [RSSMonster (semantic clustering and dedup ideas)](https://github.com/pietheinstrengholt/rssmonster/)

## 3) Personalization/Priority Profiles
- **Feature name:** Personalized Priority Profiles
- **Short description:** Support profile-based ranking preferences (freshness-first, source-trust-first, topic-first) for admin teams or member feeds.
- **Why it fits this plugin:** The plugin already has digest and member subscription concepts; profile-based ranking complements existing subscription behavior.
- **Rough effort:** high
- **Status:** ❌ Nicht umgesetzt.
- **Grund:** Erfordert größere Eingriffe in Ranking-Logik und Subscription-Modelle (high effort).
- **Source link(s):**
  - [NeuReed (personalized scoring and semantic relevance)](https://github.com/madpin/Neureed)
  - [Feedly AI feed refinement](https://docs.feedly.com/article/549-refining-feedly-ai-feeds)

## 4) OPML Import/Export for Channel Portability
- **Feature name:** OPML Import/Export
- **Short description:** Add bulk source import/export via OPML to migrate channel lists from/to other RSS tools.
- **Why it fits this plugin:** Administrators can bootstrap or back up channel sets faster than manual entry.
- **Rough effort:** low
- **Status:** ✅ Vollständig umgesetzt.
- **Grund:** High-Value/Low-Effort: schneller Channel-Transfer zwischen Tools; als sicherer OPML-Import/Export in den Admin-Systembereich integriert.
- **Source link(s):**
  - [Example aggregator project with OPML support](https://github.com/tony-stark-eth/news-aggregator)
  - [NeuReed (OPML import/export mention)](https://github.com/madpin/Neureed)

## 5) Advanced Noise Controls (Mute/Exclude Lists)
- **Feature name:** Advanced Noise Controls
- **Short description:** Add reusable exclude lists for keywords, authors, domains, and topic terms to remove low-value content before display/digesting.
- **Why it fits this plugin:** Improves content quality in busy categories and lowers manual cleanup in admin tabs.
- **Rough effort:** medium
- **Status:** ✅ Vollständig umgesetzt.
- **Grund:** High-Value/Medium-Effort: wiederverwendbare Exclude-Listen (Keywords/Autoren/Domains) reduzieren Feed-Rauschen direkt beim Import.
- **Source link(s):**
  - [Feedly AI and Mute Filters](https://feedly.com/new-features/posts/feedly-ai-and-mute-filters)
  - [Inoreader filters overview](https://www.inoreader.com/blog/2023/06/streamline-content-discovery-with-filters-and-rules.html)

## 6) Source Health Scoring & Adaptive Polling
- **Feature name:** Source Health Scoring
- **Short description:** Introduce quality/health scores per channel and optionally adapt fetch cadence based on reliability and change frequency.
- **Why it fits this plugin:** `cms-feed` already tracks fetch errors and intervals; health scoring would make scheduling smarter and reduce wasted fetches.
- **Rough effort:** medium
- **Status:** ⏳ Teilweise bereits vorhanden, nicht erweitert.
- **Grund:** Basis-Gesundheitsmetriken/Overdue-Logik existieren bereits; adaptive Polling-Strategie wurde in diesem Schritt nicht verändert, um bestehendes Abrufverhalten stabil zu halten.
- **Source link(s):**
  - [News Aggregator System Design (adaptive polling concept)](https://crackingwalnuts.com/post/news-aggregator-system-design)
  - [RSSMonster (importance/quality-based ranking ideas)](https://github.com/pietheinstrengholt/rssmonster/)

## Zusätzliche Pflichtanforderung (Public i18n)
- **Status:** ✅ Umgesetzt.
- **Grund:** `/en`-Public-Variante sowie DE-Default wurden über den Shared-Helper `shared/public/plugin-public-i18n.php` integriert; Public-Texte sind bilingual mit bestehendem i18n-Fallbackpfad (`*_en` → DE).
