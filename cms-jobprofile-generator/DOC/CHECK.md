# 🐞 QA & Bug-Tracking: Job Profile Generator

Diese Datei dient der systematischen Überprüfung, Analyse und Behebung von Fehlern, unvollständigen Funktionen und Platzhaltern (TODOs) im Plugin `cms-jobprofile-generator`.

> **Stand: 0.9.2-Release** – Alle Punkte bis einschließlich v0.9.2 abgearbeitet. Details im CHANGELOG.md.

## 🔍 1. Globale Code- & Platzhalter-Analyse
- [x] **TODO/FIXME Suche:** Kein einziger `TODO`, `FIXME`, `@todo` oder `Platzhalter` in PHP-Dateien gefunden. Nur in DOC-Dateien (legitim).
- [x] **Hardcoded URLs:** Alle Admin-Links nutzen serverlokale Pfade via `page_url()`-Helper. Entspricht CMS-Muster, kein externer `http://`-Link im PHP-Code.
- [x] **Debug-Reste:** Kein `var_dump()`, `print_r()`, `console.log()`, `die()` in produktivem PHP-Code gefunden.
- [x] **Dummy-Texte:** Kein `Lorem Ipsum`, keine statisch hardcodierten IDs vorhanden.

## 🛡️ 2. Security & RBAC (Rechte & Rollen)
- [x] **Der Rollen-Bug (Bypass Check):** `can_approve()` prüft Rollen direkt via DB-Fallback. Multi-Rollen korrekt erkannt.
- [x] **CSRF-Lücken:** Alle POST-Routen nutzen `verifyNonce()` / `verifyToken()`. Kein Auto-Save vorhanden (bewusstes Design).
- [x] **XSS-Schutz:** Alle Userausgaben via `htmlspecialchars()` / `CMS\Security::escapeOutput()`. SunEditor-HTML via `CMS\Security::sanitizeHtml()`.
- [x] **Member-Silo Check:** Alle 17 DB-Queries im Member-Controller enthalten `WHERE created_by = ?`.

## 🏢 3. Cross-Plugin Edge Cases (Was passiert, wenn...?)
- [x] **Plugin-Deaktivierung:** `isPluginActive('cms-companies')`-Guards an allen cms-companies-Integrationsstellen vorhanden.
- [x] **Orphaned Data (Verwaiste Daten):** **Fix 0.4.0:** `company_deleted`-Hook registriert. Setzt publizierte Jobs der gelöschten Firma auf `status='draft'` und `company_id=NULL`.
- [x] **Subscription Limit Bypass:** **Fix 0.4.0:** Zweiter DB-seitiger Limit-Check in `save_profile_post()` bei `$id === 0` vor INSERT. Race-Condition-Schutz implementiert.

## 🧬 4. Cascading Benefits (Vererbungs-Logik Härtetest)
- [x] **Firmen-Benefit gelöscht:** Gelöschte Benefits via `WHERE active = 1` ausgeschlossen. Ghost-IDs in `excluded_benefit_ids` werden still ignoriert.
- [x] **Abteilungs-Wechsel:** **Fix 0.4.0:** JS-Handler auf `#jpg-category`-Change zeigt gelben Warnhinweis. Navigation zum Benefits-Tab ohne Speichern via Toast abgefangen.
- [x] **Doppelte IDs:** `getResolvedBenefits()` merged Benefits via assoziatives Array (ID als Key). Duplikate technisch ausgeschlossen.
- [x] **Lock-State (Schloss-Icon):** Cascading-Logik im Controller sichert vererbte Benefits server-seitig ab.

## 📝 5. Wizard & UI/UX Schwachstellen
- [x] **Auto-Save Fehlerbehandlung:** Kein Auto-Save vorhanden. Wizard nutzt explizite Speichern-Buttons. Fehler werden als `alert-error` auf der gleichen Tab-Seite angezeigt. N/A.
- [x] **Drag & Drop Mobile:** **Fix 0.4.0:** `drag-drop.js` (`JPGDragDrop`) erhielt vollständigen `touchstart`/`touchmove`/`touchend`-Fallback mit visuellem Klon. iOS/Android unterstützt.
- [x] **Character Counter Bug:** JS `.length` und HTML `maxlength` nutzen UTF-16-Code-Units. Konsistent mit Backend. Edge-Case Surrogate-Pairs (≥ U+10000) = akzeptiertes Risiko.
- [x] **SunEditor Sanitization:** `CMS\Security::sanitizeHtml()` bereinigt IFrames und SVG-Tags (erwünschtes Verhalten).

## 📄 6. Bewerbungs-Management (Applicant Tracking)
- [x] **File-Upload Lücken:** `handle_cv_upload()` prüft MIME via `finfo_file()`. PHP-Dateien nicht hochladbar.
- [x] **Benachrichtigungs-Spam:** Route `POST /jobs/:slug/apply` mit Rate-Limiting: max. 3 / 300 Sekunden pro IP.
- [x] **DSGVO-Verstoß:** Bewerbungs-Modal enthält DSGVO-Pflicht-Checkbox mit `required`-Attribut.

## 🧹 7. Uninstaller & Cleanup Check
- [x] **Tabellen-Cleanup:** **Fix 0.4.0:** `uninstall.php` erstellt. `CMS_JPG_Installer::uninstall()` entfernt alle `jpg_*`-Tabellen via `DROP TABLE IF EXISTS`.
- [x] **Rechte + Abo-System:** **Fix 0.4.0:** `uninstall()` entfernt die Spalten `limit_job_profiles`, `feature_whitelabel_jobs` und `feature_custom_branding` aus `subscription_plans`.
- [x] **Neue Tabellen im Uninstaller:** `jpg_requirement_items` und `jpg_company_default_benefits` in `uninstall.php`-Cleanup aufgenommen. Keine Orphaned-Tables nach Deinstallation.

## 🆕 8. v0.6.0 – Neue Features (QA-Check)

- [x] **Firmen-Autofill:** `jpgFillCompanyData()` liest aus serverseitig generiertem `jpgCompanyData`-JSON. Kein AJAX-Request, kein XSS-Risiko (JSON via `json_encode()` ausgegeben). Autofill überschreibt Location nur wenn Feld leer ist OR Firma neu gewählt.
- [x] **Company Default Benefits – Vorauswahl-Logik:** Vorauswahl greift nur wenn `empty($benefitIds)` (neues Profil oder Tab noch nicht gespeichert). Explizite Nutzerbefehle bei vorhandenen Profilen bleiben unberührt.
- [x] **Company Default Benefits – Speicher-Security:** `handle_company_overview_post()` prüft CSRF-Token vor DELETE+INSERT. `company_id` kommt aus GET-Parameter, wird als `(int)` gecasted.
- [x] **RequirementItems CRUD:** `CMS_JPG_RequirementItems::save()` sanitiert `group_name` und `title` via `sanitize_text_field()`. `sort_order` wird als `(int)` gecasted.
- [x] **Anforderungs-Bausteine-Picker im Wizard:** `jpgAddReqItem()` übernimmt Daten aus `<select>`-Elementen ohne XSS-Risiko (Werte werden via `innerText` gesetzt, nicht via `innerHTML`).
- [x] **Textbaustein-Picker im Aufgaben-Tab:** `jpgAddTaskFromModule(content)` erstellt neues Task-Item als DOM-Knoten. `content` wird via `textContent` eingefügt, kein `innerHTML`-Injection-Risiko.
- [x] **Bibliotheken Tab-Index-Konsistenz:** `render_libraries()` und `handle_libraries_post()` nutzen denselben `'requirement-items'`-Key. Kein Tab-Drift möglich.
- [x] **Unternehmens-Übersicht Empty States:** View zeigt korrekte Hinweistexte wenn (a) `cms-companies` nicht aktiv, (b) keine Firmen vorhanden, (c) keine Benefits im Katalog.



---

## 🆕 9. v0.5.0 – 5-Tab-Wizard & Genehmigungen (QA-Check)

- [x] **Admin: Genehmigungen-Untermenü** — `jpg-approvals`-Seite und `page-approvals.php` existieren. Stats-Zeile (ausstehend, Stufen, Rollen), Tabelle mit Dot-Progress, Approve/Reject-Modal mit Notizfeld vorhanden.
- [x] **Member: 5-Tab-Wizard Edit** — `page-jobs-edit.php`: Tabs Basisdaten/Aufgaben/Anforderungen/Benefits/Workflow implementiert. Gesperrt-Modus bei `pending`/`approved` mit readonly-Feldern und Info-Banner.
- [x] **Member: 5-Tab-Wizard Create** — `page-jobs-create.php`: 5-Tab-Wizard mit Drag&Drop im Aufgaben-Tab und Workflow-Schritt-Visualisierung.
- [x] **Member: Genehmiger-Dashboard** — `render_approvals_inline()` im Member-Controller vorhanden. Menüeintrag erscheint nur für Admins und User mit ausstehenden Genehmigungen.
- [x] **save_profile_post() vollständig** — Speichert `requirements` via `save_requirements()` und `benefits` via `save_benefits()`. Felder `salary_min`, `salary_max`, `experience_level` werden gespeichert.
- [x] **Workflow-Submit-Route** — `POST /member/jobs/workflow/submit/:id` in `class-member-controller.php` registriert (Z. 98).

---

## 🆕 10. v0.9.0 – Phase 9: Team-Genehmiger, Privacy, Audit-Log (QA-Check)

- [x] **Team-Genehmiger DB** — `jpg_team_approvers`-Tabelle im Installer. `CMS_JPG_Workflow::get_team_approvers()` (Z. 588), `add_team_approver()`, `remove_team_approver()` implementiert.
- [x] **can_approve() mit Team-Check** — `can_approve()` berücksichtigt zusätzlich Team-Genehmiger via `jpg_team_approvers`-JOIN (Z. 286, 321).
- [x] **get_pending_for_user() UNION** — Rollen-basierte + Team-Genehmiger-basierte Abfrage per UNION (Z. 381, 415).
- [x] **Privacy-Filter** — `is_private`-Spalte in `jpg_profiles`. `get_list()` und `count()` in `CMS_JPG_Profiles` unterstützen `hide_private`-Argument.
- [x] **Audit-Log** — `jpg_admin_access_log`-Tabelle. `log_admin_access()` (Z. 676) und `get_audit_log()` (Z. 721) in `CMS_JPG_Workflow`. Einbindung in `render_company_overview()` und `render_generator()`.

---

## 🆕 11. v0.9.1 – Standalone-Route, Katalog-Seeding, Öff. Jobs-Seite (QA-Check)

- [x] **Standalone Approvals-Route** — `GET /member/jobs/approvals` und `POST /member/jobs/approvals` in `class-member-controller.php` (Z. 99–100) registriert. `render_approvals()` Standalone-Methode mit Auth-Check vorhanden (Z. 825).
- [x] **Bedingter Genehmigungsmenüeintrag** — `add_menu_item()` prüft `$isAdmin` OR `get_pending_for_user($currentUid)` (Z. 225–248). Eintrag erscheint nur bei berechtigt.
- [x] **seed_requirement_items()** — Aufruf in Installer bei `run()` (Z. 603). Methode ab Z. 1548: befüllt `jpg_requirement_items` mit 60+ Einträgen in 8 Kategorien.
- [x] **jpgToggleGroup() im Generator** — Onclick-Handler in Benefits-Tab von `page-generator.php` (Z. 556). JS-Funktion definiert (Z. 1001).
- [x] **jpgToggleGroup() in Bibliotheken** — Onclick-Handler in `admin/views/page-libraries.php` an 3 Stellen (Z. 130, 191, 271). JS-Funktion (Z. 599). Gleicher Mechanismus.
- [x] **Öffentliche Jobs-Seite /jobs** — `GET /jobs` Route in `class-frontend.php` (Z. 46) → `render_jobs_list()` (Z. 175). View `views/public/jobs-list.php` vorhanden. Filter: nur `status='published'` und `show_in_listing=1`.
- [x] **show_in_listing Toggle** — Spalte in `jpg_profiles`. Gespeichert in `save_profile_post()` (Z. 2002): `(int)isset($_POST['show_in_listing'])`. Toggle in Generator + Admin-Listenansicht vorhanden.
- [x] **DB-Fehler c.company_name behoben** — SQL-Abfragen nutzen `c.name AS company_name` statt `c.company_name`. Betraf 5 Stellen in `admin/class-admin-pages.php` (2×) und `class-member-controller.php` (3×).

---

## 🆕 12. v0.9.2 – Bug-Fixes, Accordion, Jobs-Seite-Konfiguration (QA-Check)

- [x] **fn(string|null $v) – Null-Safety** — Alle 12 `$esc`-Closures auf `fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES)` geändert. Grep-Prüfung: Keine verbleibenden `fn(string $v)`-Definitionen ohne `string|null`. ✅
- [x] **Duplikat-Formular entfernt** — `page-jobs-create.php` hat 467 Zeilen (Ziel: ≤468). Endet mit `</script>` des Wizard-JS-Blocks. Kein zweites `<form>`-Element vorhanden. ✅
- [x] **Departments-Accordion Admin** — `jpgAdminToggleDept()` in `page-company-overview.php` definiert (Z. 633). Onclick-Handler an Dept-Buttons (Z. 444, 546). Kein Page-Reload.
- [x] **Departments-Accordion Member** — `jpgMemberToggleDept()` in `page-company-settings.php` definiert (Z. 711). Onclick-Handler (Z. 503, 620). Benefits + Anforderungen als Checkboxen pro Panel.
- [x] **Jobs-Seite Tab (Member)** — Tab `'jobs-page'` in `$tabs`-Array (Z. 91). Formular mit 6 Feldern: `jobs_page_title`, `jobs_page_intro`, `jobs_page_contact_email`, `jobs_page_show_salary`, `jobs_page_enabled`, `jobs_page_url` (Z. 629–695).
- [x] **Jobs-Seite POST-Handler** — `elseif ($settingsTab === 'jobs-page')` in `render_settings_inline()` (Z. 1659). Validierung via `verify_token('member_company_departments')` vor Speichern (Z. 1661). CSRF-Schutz aktiv.
- [x] **jobs_page_* DB-Migration** — `maybe_add_columns()` in `class-installer.php` fügt 5 Spalten in `jpg_company_settings` ein (Z. 541–562): `jobs_page_title`, `jobs_page_intro`, `jobs_page_contact_email`, `jobs_page_show_salary`, `jobs_page_enabled`. `ALTER TABLE IF NOT EXISTS`-Pattern mit Error-Log.
- [x] **Jobs-Seite Tab (Admin)** — `page-company-overview.php` zeigt Jobs-Seite-Konfig-Formular mit Feld `jobs_page_title` (Z. 566). Daten aus `$companySettings`-Objekt via `get_company_settings()`.
