# CMS Forum – Feature Suggestions

## 1) Accepted Answer / Solved Threads
- **Feature name:** Accepted answer for question-like threads
- **Short description:** Let thread authors (and moderators) mark one reply as the accepted solution, then show a solved state in thread lists.
- **Why it fits this plugin:** This plugin already has threads, moderation, and reports; a solved marker helps support-oriented discussions and reduces repeated questions.
- **Rough effort (low/medium/high):** Medium
- **Source link(s):** [Discourse Solved plugin overview](https://meta.discourse.org/t/discourse-solved/30155?tl=en), [discourse-solved repository](https://github.com/discourse/discourse-solved)

## 2) Trust-Level-Based Anti-Spam Workflow
- **Feature name:** Progressive trust levels with stricter rules for new users
- **Short description:** Introduce staged user trust levels with tighter limits/approvals for new accounts and relaxed limits for trusted members.
- **Why it fits this plugin:** The plugin already has bans, moderation, and permissions. Trust levels would reduce spam load while keeping normal members productive.
- **Rough effort (low/medium/high):** High
- **Source link(s):** [Understanding Discourse Trust Levels](https://blog.discourse.org/2018/06/understanding-discourse-trust-levels/), [Discourse spam prevention guidance](https://meta.discourse.org/t/tips-for-preventing-spam/264020)

## 3) Duplicate-Thread Prevention While Composing
- **Feature name:** Similar thread suggestions during thread creation
- **Short description:** While users type a new thread title/content, show likely duplicate threads and suggest continuing existing discussions.
- **Why it fits this plugin:** The plugin already has search and full thread data, so this feature can reuse existing query logic to prevent duplicate topics.
- **Rough effort (low/medium/high):** Medium
- **Source link(s):** [Discourse similar topics discussion](https://meta.discourse.org/t/top-search-vs-similar-topics/241120), [Composer similar-topics improvements](https://github.com/discourse/discourse/pull/34435)

## 4) Expanded Moderation Queue for Unapproved Content
- **Feature name:** First-class moderation queue for post/topic approval
- **Short description:** Add a dedicated queue UI for approving/rejecting pending topics/posts with optional moderator notes and user notifications.
- **Why it fits this plugin:** The plugin already stores `is_approved` and has moderation/report pages, so a queue is a natural extension and improves moderation throughput.
- **Rough effort (low/medium/high):** Medium
- **Source link(s):** [phpBB moderation queue overview](https://www.phpbb.com/support/docs/en/3.0/ug/moderatorguide/moderator_queue/), [phpBB MCP workflow reference](https://deepwiki.com/phpbb/phpbb/4.2-moderation-control-panel-(mcp))

## 5) Per-Forum Slow Mode / Flood Limits
- **Feature name:** Configurable slow mode per forum or role
- **Short description:** Add configurable minimum delay between posts/replies per forum and optionally per role/trust level.
- **Why it fits this plugin:** The plugin already has flood-control logic and forum-level permissions, so this is a low-risk extension that can reduce spam bursts and heated reply storms.
- **Rough effort (low/medium/high):** Medium
- **Source link(s):** [XenForo flood check concept](https://xenforo.com/community/threads/bypass-flood-check.52260/), [Flood permission granularity example](https://github.com/Xon/XenForo2-PostFloodPerms)
