# CMS NetImport - Feature Suggestions

## 1) Background Import Queue + Resume Checkpoints
- **Feature name:** Background Import Queue + Resume Checkpoints
- **Short description:** Run large imports asynchronously with resumable checkpoints per batch, so interrupted jobs continue from the last confirmed progress marker.
- **Why it fits this plugin:** NetImport already handles multi-step imports and history tracking; resumable background execution would improve reliability for bigger CSV files without changing import semantics.
- **Rough effort:** High
- **Status:** `skipped`
- **Reason:** Erfordert neue Queue-/Worker-Infrastruktur, persistente Checkpoint-Storage-Logik und Lifecycle-Handling außerhalb des aktuell stabilen synchronen Run-APIs.
- **Source link(s):**
  - https://codewithrails.com/blog/rails-resumable-csv-import-continuable/
  - https://www.elysiate.com/blog/idempotent-csv-loads-into-postgresql-patterns-and-pitfalls

## 2) Advanced Schema Validation Profiles
- **Feature name:** Advanced Schema Validation Profiles
- **Short description:** Add configurable validation profiles (strict, balanced, permissive) with row-level and field-level error reporting before commit.
- **Why it fits this plugin:** The plugin already validates headers and core structure; profile-based validation would reduce bad data imports while keeping operators in control of acceptable error tolerance.
- **Rough effort:** Medium
- **Status:** `implemented`
- **Reason:** Implementiert mit Profil-Auswahl (`strict`/`balanced`/`permissive`) im Admin-Formular, zeilen-/feldbezogener Vorabprüfung und profilspezifischem Verhalten vor Commit (Abort, Skip oder nur Protokollierung).
- **Source link(s):**
  - https://blog.csvbox.io/row-level-errors-csv/
  - https://datatracker.ietf.org/doc/html/rfc4180

## 3) Import Mapping Templates for Header Variants
- **Feature name:** Import Mapping Templates for Header Variants
- **Short description:** Allow saved column mapping templates for known CSV variants, with automatic mapping suggestions and manual fallback.
- **Why it fits this plugin:** NetImport currently expects normalized header families; mapping templates would absorb external source variance without rewriting import logic for each new file schema.
- **Rough effort:** Medium
- **Status:** `skipped`
- **Reason:** Für eine vollständige, stabile Umsetzung wären persistente Template-Verwaltung (Storage + Admin-CRUD), automatische Vorschlagslogik je Dateifamilie und konfliktfreie Fallback-Regeln erforderlich; das würde Slugs/Hooks/API in dieser Runde übermäßig ausweiten.
- **Source link(s):**
  - https://blog.csvbox.io/row-level-errors-csv/
  - https://theyard.dev/blog/csv-data-validation-guide

## 4) Quarantine Mode for Uploaded Source Files
- **Feature name:** Quarantine Mode for Uploaded Source Files
- **Short description:** Introduce optional quarantine processing for uploaded CSV files (type checks, size checks, signature checks, and optional malware scan hooks) before they become import-eligible.
- **Why it fits this plugin:** The plugin already hardens path validation for local CSV files; a quarantine stage would strengthen security if file uploads are added or expanded later.
- **Rough effort:** Medium
- **Status:** `implemented`
- **Reason:** Quarantäne-Checks ergänzt (MIME, Dateisignatur, Null-Byte/Binary-Indikatoren) und optionaler externer Scan-Hook `netimport_quarantine_scan` integriert; Modus ist im Importformular schaltbar.
- **Source link(s):**
  - https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html
  - https://owasp.org/www-community/vulnerabilities/Unrestricted_File_Upload

## 5) CSV Formula Injection Guardrails
- **Feature name:** CSV Formula Injection Guardrails
- **Short description:** Add optional guardrails that detect and neutralize formula-like cell values for audit exports or correction-file exports intended for spreadsheet tools.
- **Why it fits this plugin:** NetImport stores and may export import diagnostics; formula-injection protection prevents operator workstations from spreadsheet-based payload execution.
- **Rough effort:** Low
- **Status:** `implemented`
- **Reason:** Optionaler Formula-Guard im Importlauf ergänzt: verdächtige Zellen werden erkannt, vor Verarbeitung neutralisiert (Apostroph-Präfix) und mit zeilen-/feldbezogenen Warnungen protokolliert.
- **Source link(s):**
  - https://owasp.org/www-community/attacks/CSV_Injection
  - https://owasp.org/www-project-web-security-testing-guide/latest/4-Web_Application_Security_Testing/07-Input_Validation_Testing/21-Testing_for_CSV_Injection
