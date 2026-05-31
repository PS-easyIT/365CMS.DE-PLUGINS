# Feature Ideas for CMS Marketplace Plugin

## 1) Package Signature Verification
- **Feature name:** Package Signature Verification
- **Short description:** Allow marketplace entries to include a detached signature and signer certificate fingerprint; verify ZIP integrity and signer trust before publish.
- **Why it fits this plugin:** The plugin already manages ZIP delivery and SHA-256 checksums, so signature verification is a natural supply-chain hardening step for downloadable packages.
- **Rough effort (low/medium/high):** High
- **Source link(s):**
  - https://plugins.jetbrains.com/docs/intellij/plugin-signing.html
  - https://docs.upbound.io/manuals/marketplace/security-features/

## 2) SBOM Attachment and Display
- **Feature name:** SBOM Attachment and Display
- **Short description:** Support uploading and exposing SPDX/CycloneDX SBOM files per release, with a small viewer in admin and optional public metadata links.
- **Why it fits this plugin:** The plugin already publishes release metadata (`manifest.json`, `update.json`); SBOM references extend this metadata without changing existing consumer APIs.
- **Rough effort (low/medium/high):** Medium
- **Source link(s):**
  - https://docs.upbound.io/manuals/marketplace/security-features/
  - https://www.cybeats.com/blog/prevent-bad-actors-from-hacking-your-sbom

## 3) Automated Vulnerability Status for Published Versions
- **Feature name:** Automated Vulnerability Status
- **Short description:** Add optional background checks against known advisories and store a simple risk status per published version for admin triage.
- **Why it fits this plugin:** This plugin is a release registry; surfacing basic vulnerability status directly in release management helps operators decide what should stay published.
- **Rough effort (low/medium/high):** High
- **Source link(s):**
  - https://docs.github.com/code-security/dependabot/dependabot-alerts/about-dependabot-alerts
  - https://docs.jfrog.com/security/docs/features-and-capabilities

## 4) Public Security Disclosure Workflow
- **Feature name:** Security Disclosure Workflow
- **Short description:** Add a dedicated security-report intake path per entry (separate from general purchase/contact links) with admin-only review states.
- **Why it fits this plugin:** The plugin already stores entry metadata and submitter fields; a disclosure workflow strengthens incident handling for listed packages.
- **Rough effort (low/medium/high):** Medium
- **Source link(s):**
  - https://make.wordpress.org/plugins/handbook/performing-reviews/security-and-guideline-violation-reports/
  - https://developer.wordpress.org/plugins/wordpress.org/detailed-plugin-guidelines/

## 5) Policy-based Publish Guardrails
- **Feature name:** Policy-based Publish Guardrails
- **Short description:** Introduce configurable publish rules (for example: required docs URL, minimum package age, required checksum/signature fields) that block publish until criteria pass.
- **Why it fits this plugin:** It already has a draft/published workflow and centralized save/publish actions, so policy gates can be added without changing public routes or feed formats.
- **Rough effort (low/medium/high):** Medium
- **Source link(s):**
  - https://docs.jfrog.com/security/docs/features-and-capabilities
  - https://make.wordpress.org/plugins/handbook/performing-reviews/review-checklist/
