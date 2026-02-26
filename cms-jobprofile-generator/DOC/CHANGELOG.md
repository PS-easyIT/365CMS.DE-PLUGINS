# 📋 Changelog – CMS Job Profile Generator

> Alle nennenswerten Änderungen am Plugin werden in dieser Datei dokumentiert.
> Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).
> Versionierung folgt [Semantic Versioning](https://semver.org/lang/de/).

---

## [0.9.2] – 2026-02

### Hinzugefügt
- **Departments-Accordion:** Firmen-Einstellungen zeigen Abteilungen als Inline-Accordion (kein Page-Reload). `jpgMemberToggleDept()` / `jpgAdminToggleDept()` in Member- und Admin-View.
- **Jobs-Seite Konfiguration:** Neuer Tab mit Feldern Seitentitel, Intro-Text, Kontakt-E-Mail, Gehalt anzeigen, Seite aktivieren, URL. Gespeichert via `save_company_settings()`.
- **Admin: Jobs-Seite Tab** in `page-company-overview.php` mit vollem Konfig-Formular.

### Behoben
- **TypeError null → string:** `fn(string $v)` mit `strict_types=1` warf `TypeError` bei null-Werten aus DB-Feldern. 12 Closures in Views und `class-export.php` auf `fn(string|null $v)` geändert.
- **Doppeltes Formular:** Altes einfaches Erstellungsformular in `page-jobs-create.php` existierte parallel zum 5-Tab-Wizard. Duplikat entfernt (Zeilen 469–566 der alten Datei).

### Geändert
- Version: `0.9.1` → `0.9.2`

---

## [0.9.1] – 2026-07

### Hinzugefügt
- Standalone-Route `/member/jobs/approvals` + `render_approvals()` Methode
- Anforderungs-Katalog `seed_requirement_items()` bei Erstinstallation (60+ Einträge, 8 Kategorien)
- Einklappbare Gruppen im Generator (Benefits-Tab) + Bibliotheks-Seiten via `jpgToggleGroup()`
- Öffentliche Stellenanzeigen-Seite: Route `/jobs`, `render_jobs_list()`, `views/public/jobs-list.php`
- `show_in_listing`-Toggle: Stellen gezielt aus öffentlicher Listing-Ansicht ausblenden

### Behoben
- DB-Fehler `c.company_name` (korrekt: `c.name AS company_name`) in 5 SQL-Stellen
- 404 bei „Neue Stelle erstellen" (`$createUrl` fehlte in `render_list()`)
- `Headers already sent` bei PDF-Export (ob_start im Bootstrap + ob_end_clean in Handlern)

### Geändert
- DB-Schema v5 → v6: `maybe_add_columns()` für `show_in_listing`, `company_settings`-Spalten, DB_VERSION = 6

---

## [0.6.0] – 2026-02-26

### Hinzugefügt

- **`includes/class-requirement-items.php` – `CMS_JPG_RequirementItems`:** Neue Service-Klasse (Singleton) für eine eigene „Anforderungs-Liste" als Bibliotheks-Typ. Methoden: `get_all()`, `get_grouped()`, `save(array $data, int $id = 0)`, `delete(int $id)`. Einträge bestehen aus `group_name`, `title` und `sort_order`.

- **DB-Tabellen (zwei neue):**
  - `jpg_requirement_items` (id, group_name VARCHAR(100), title VARCHAR(255), sort_order SMALLINT) – persistiert die Anforderungs-Bausteine.
  - `jpg_company_default_benefits` (id, company_id INT UNSIGNED, benefit_id INT UNSIGNED, UNIQUE KEY `uk_company_benefit`) – speichert die Standard-Benefits pro Unternehmen.

- **Admin: Untermenü „🏢 Unternehmens-Übersicht" (`jpg-companies`):** Neue Seite zum Zuweisen von Standard-Benefits an Unternehmen aus dem `cms-companies`-Plugin. Zweispaltiges Layout: Firmenliste links, Benefit-Checkbox-Grid rechts. `admin/class-admin-menu.php` und `admin/class-admin-pages.php` ergänzt.

- **Admin: `admin/views/page-company-overview.php`** (neu): Vollständige View mit Firmenliste (Badge mit Anzahl zugewiesener Benefits), Benefit-Checkbox-Grid nach Gruppen und POST-Formular (DELETE+INSERT-Muster).

- **Admin: `CMS_JPG_Admin_Pages::render_company_overview()` + `handle_company_overview_post()`:** Render-Methode lädt Firmen, Benefit-Katalog und Zuweisungsmap. POST-Handler löscht bestehende Einträge und fügt neu gewählte Benefits via `INSERT IGNORE` ein.

- **Generator-Wizard: 6 Tabs** (vorher 5):
  - Tab 4 **Benefits** und Tab 5 **Skills** sind jetzt getrennte Formulare (je eigene `action`-Konstante: `save_benefits` / `save_skills`).
  - Tab 2 **Aufgaben**: Einklappbarer Textbaustein-Picker (`<details>`). Bausteine werden per `jpgAddTaskFromModule(content)` als vollständige DnD-Aufgaben eingefügt.
  - Tab 3 **Anforderungen**: Zweiter Picker-Block für Anforderungs-Bausteine aus `$requirementItems` (grüne `border-left`-Markierung); neue JS-Funktion `jpgAddReqItem()`.
  - Tab 1 **Basisdaten**: Firmen-Dropdown löst `jpgFillCompanyData(id)` aus – füllt `#jpg-location` (PLZ + Ort + Land) automatisch und zeigt Info-Box mit Telefon/Website.

- **Generator: Company Default Benefits:** Beim Laden eines neuen Profils werden die Standard-Benefits des gewählten Unternehmens (`$companyDefaultBenefitIds`) im Benefits-Tab vorausgewählt. Bereits gespeicherte explizite Benefit-Auswahl bleibt bei vorhandenen Profilen unberührt.

- **Generator: `$companiesJson`:** JSON-Map `company_id → {name, city, zip, country, phone, website}` wird als Inline-JS-Variable ausgegeben – kein separater AJAX-Request für den Autofill.

- **Bibliotheken: Tab „Anforderungs-Liste"** (neuer Tab 2, 6 Tabs gesamt): Listet gruppierte Anforderungs-Bausteine, Bearbeitungs-Modal (`jpgLibEdit('ri',…)`) und Lösch-Buttons. Formular speichert `group_name`, `title`, `sort_order`.

### Geändert

- **`cms-jobprofile-generator.php`:** Include für `class-requirement-items.php` zur Klassen-Ladeliste hinzugefügt.
- **`class-installer.php`:** Zwei neue `CREATE TABLE IF NOT EXISTS`-Blöcke in `create_tables()` vor `maybe_add_columns()` ergänzt.
- **`admin/class-admin-pages.php` – `render_generator()`:** Companies-Query erweitert (`location_city`, `location_zip`, `location_country`, `phone`, `website`), Tabs-Array auf 6 Einträge aktualisiert, `$requirementItems`, `$companiesJson` und `$companyDefaultBenefitIds` als View-Variablen übergeben.
- **`admin/class-admin-pages.php` – `handle_generator_post()`:** Case `save_benefits` speichert ausschließlich Benefits; neuer Case `save_skills` speichert ausschließlich Skills.
- **`admin/class-admin-pages.php` – `render_libraries()`:** Tabs-Array auf 6 Einträge, Data-Match um `'requirement-items'` ergänzt.
- **`admin/class-admin-pages.php` – `handle_libraries_post()`:** Neuer Case `'requirement-items'` vor `'job-categories'` eingefügt.
- **`admin/views/page-generator.php`:** Tab-Icons-Array auf 6 Einträge, Docblock aktualisiert.
- **`admin/views/page-libraries.php`:** Docblock (\"5 Tabs\" → \"6 Tabs\"), `jpgLibEdit()`-JS um `type === 'ri'`-Branch erweitert.
- **Plugin-Version:** `0.5.0` → `0.6.0`.

---

## [0.5.0] – 2026-02-26

### Hinzugefügt

- **Admin: Untermenü „✅ Genehmigungen" (`jpg-approvals`):** Ausstehende Genehmigungen aus dem Workflow-Editor ausgelagert als eigener Unterpunkt. `admin/class-admin-menu.php` ergänzt.
- **Admin: `CMS_JPG_Admin_Pages::render_approvals()` + `handle_approvals_post()`:** Eigenständige Render- und POST-Handler-Methoden für den neuen Genehmigungen-Bereich.
- **Admin: `admin/views/page-approvals.php`:** Vollständige Seite mit Stats-Zeile (ausstehend, Workflow-Stufen, Rollen), Tabelle mit Dot-Progress-Visualisierung und Approve/Reject/Reset-Modal inkl. Notizfeld.
- **Member: 5-Tab-Wizard Stellenanzeige bearbeiten** (`views/member/page-jobs-edit.php`):
  - Tab 1 (Basisdaten): Ergänzt um `salary_min`, `salary_max`, `experience_level` und ausführliche `description`-Textarea. Gesperrt-Modus bei `pending`/`approved`.
  - Tab 4 (Benefits): Neu — Checkbox-Grid aus `$allBenefits` mit Gruppen-Überschriften.
  - Tab 5 (Workflow): vollständige Schritt-Visualisierung (Entwurf → Schritte → Veröffentlicht), Einreichungs-Formulare je nach Status, Verlaufsliste.
- **Member: 5-Tab-Wizard Stellenanzeige anlegen** (`views/member/page-jobs-create.php`): Komplette Neuerstellung mit gleichem Tabbing, Drag&Drop-Aufgaben, Anforderungen, Benefits, Workflow-Stepper.
- **Member: Genehmiger-Eintrag `member-job-approvals`:** Wird in `register_via_plugin_dashboard()` registriert wenn der User Admin ist oder pending-Items hat.
- **Member: `render_approvals_inline()`:** Lädt gefilterte pending-Profile via `CMS_JPG_Workflow::get_pending_for_user()`, verarbeitet Approve/Reject/Reset-POST.
- **Member: `views/member/page-approvals.php`:** Tabelle ausstehender Genehmigungen mit Dot-Progress, Approve/Reject-Modal, Notizfeld und Verlaufshinweis.

### Geändert

- **Admin: `render_workflow()`:** Lädt keine pending-Profile mehr. Link-Hinweis auf neue Genehmigungen-Seite statt altem Pending-Block.
- **Admin: `handle_workflow_post()`:** Approve/Reject/Reset-Fälle in `handle_approvals_post()` ausgelagert.
- **Controller: `save_profile_post()`:** Speichert nun `requirements` (req_text[]/req_type[]) via `CMS_JPG_Profiles::save_requirements()` und `benefits` (benefit_ids[]) via `CMS_JPG_Profiles::save_benefits()`.
- **Controller: `render_create_inline()` / `render_create()`:** Übergeben `$allBenefits`, `$tasks=[]`, `$requirements=[]`, `$benefitIds=[]` an die Create-View.
- **Controller: `render_edit_inline()`:** Übergibt jetzt `$wfCsrf` an die Edit-View.
- **Plugin-Version:** `0.4.0` → `0.5.0`.

---

## [0.4.0] – 2026-02-25

### Hinzugefügt

- **`uninstall.php`** (Plugin-Root): Wird vom CMS beim Deinstallieren aufgerufen. Lädt `CMS_JPG_Installer::uninstall()` und führt vollständigen Datenbank-Cleanup durch.

- **`CMS_JPG_Installer::uninstall()` (static):** Entfernt restlos alle Plugin-Daten:
  - `SET FOREIGN_KEY_CHECKS = 0` → `DROP TABLE IF EXISTS` für alle 17 `jpg_*`-Tabellen → `SET FOREIGN_KEY_CHECKS = 1`
  - Entfernt die Spalten `limit_job_profiles`, `feature_whitelabel_jobs` und `feature_custom_branding` aus `subscription_plans` via `information_schema`-Check (nur wenn vorhanden).

- **Hook `plugin_uninstalled`:** `on_uninstall(string $plugin)` im Haupt-Bootstrap ruft `CMS_JPG_Installer::uninstall()` auf, wenn der Slug des deinstallierten Plugins übereinstimmt.

- **Hook `company_deleted`:** `on_company_deleted(int $companyId)` im Haupt-Bootstrap setzt alle `published`-Profile der gelöschten Firma auf `status='draft'` und `company_id=NULL`. Verhindert Orphaned-Data-Probleme bei Firma-Löschung im cms-companies-Plugin.

- **Mobile Touch-Support für `drag-drop.js`:** `JPGDragDrop`-Modul enthält jetzt vollständigen Touch-Fallback (`touchstart` / `touchmove` / `touchend`) für iOS und Android. Visueller Klon-Knoten während des Drags, `document.elementFromPoint()`-basierte Einsortierungs-Logik, `{ passive: false }` Event-Options für korrekte `preventDefault()`-Behandlung.

- **Kategorie-Wechsel-Warnung in `page-generator.php`:** JS-Handler auf `#jpg-category`-Änderung im Wizard-Tab 1 fügt einen gelben Warnhinweis ein und blockiert die Navigation zu Tab 4 (Benefits) via Toast-Nachricht, solange Basisdaten nicht gespeichert wurden. Verhindert, dass veraltete Kategorie-Benefits angezeigt werden.

### Geändert

- **Plugin-Version:** `0.3.0` → `0.4.0` (in `JPG_VERSION`-Konstante und `$this->version`).
- **`$this->version` in Haupt-Bootstrap:** Wird nun dynamisch aus der `JPG_VERSION`-Konstante gesetzt statt als statisches String-Literal `'0.2.0'`.

### Sicherheit / Bugfixes

- **Race-Condition-Fix in `save_profile_post()`:** Zweiter DB-seitiger `user_can_create_resource('job_profiles')`-Check unmittelbar vor dem `INSERT`-Statement bei neuen Profilen (`$id === 0`). Verhindert, dass parallele gleichzeitige POST-Requests den Subscription-Limit umgehen.

### Internes

- **QA-Pass abgeschlossen:** Alle Punkte in `DOC/CHECK.md` analysiert und abgearbeitet. Keine Debug-Reste, keine Dummy-Daten, alle Security-Checks verifiziert.

---

## [0.3.0] – 2026-02-25

### Hinzugefügt

- **Bewerbungs-Route `POST /jobs/:slug/apply`** mit vollständigem Handler `handle_apply()`:
  - Rate-Limiting: max. 3 Bewerbungen pro IP / 5 Minuten (`check_rate_limit('apply_job', 3, 300)`)
  - Honeypot-Feld `_hp_name` als Spam-Schutz
  - CSRF-Validierung (`jpg_apply_{slug}`)
  - Eingaben sanitiert via `sanitize_text()` + `sanitize_html()`
  - CV-Upload mit MIME-Validierung (PDF / DOCX / DOC), max. 5 MB, Token-Dateinamen
  - DB-Insert in `jpg_applications`
  - JSON-Response `{success: bool, message/error: string}`

- **NotificationService-Trigger (Task 5.3):** `trigger_application_notifications()` prüft User-Präferenz `jpg_notify_applications` und ruft `CMS\Services\NotificationService::create()` auf (in-App + optional E-Mail).

- **Bewerbungs-Modal** in allen öffentlichen Job-Views:
  - `single-integrated.php` + `single-whitelabel.php`: Vollständiges AJAX-Modal mit Name, E-Mail, Telefon, Anschreiben-Textarea und CV-File-Input
  - DSGVO-Hinweistext, CSRF-Hidden-Input, Honeypot, Overlay/Escape-to-close, Spinner-Zustand beim Absenden
  - JS-Callbacks: Erfolgs- und Fehler-Banner inline im Modal

- **Cascading Benefit Logic (Strict Rule #1) – `getResolvedBenefits(int $profileId)`** in `class-profiles.php`:
  - Ebene 1 – Firma: `{prefix}company_meta` mit `meta_key = 'jpg_default_benefit_ids'` (JSON)
  - Ebene 2 – Kategorie: neue Tabelle `jpg_category_benefits` (category_id, benefit_id, sort_order)
  - Ebene 3 – Job: `jpg_profile_benefits` (bisherige job-spezifische Zuweisung)
  - Deduplizierung mit Quellen-Tracking (job > category > company)
  - Override-Logik: `excluded_benefit_ids` (JSON-Spalte in `jpg_profiles`) deaktiviert geerbte Benefits (z.B. für Werkstudenten)
  - Jedes Rückgabe-Objekt enthält `source` = `'company'` | `'category'` | `'job'`
  - Hilfsmethoden: `save_category_benefits()`, `save_excluded_benefits()`

- **DSGVO Art. 20 – Datenexport-Hook** (`cms_member_data_export_requested`):
  - `handle_data_export(array $exportData, int $userId)` exportiert alle Job-Profile, Tasks, Requirements und anonymisierte Bewerbungen als strukturiertes JSON unter `$exportData['job_profiles']`

- **DSGVO Art. 17 – Account-Löschungs-Hook** (`cms_member_account_deletion_requested`):
  - `handle_account_deletion(int $userId)` setzt alle Profile auf `status='trash'`, löscht CV-Dateien physisch via `@unlink()` und anonymisiert Bewerbungs-PII (Name, E-Mail, Telefon, Anschreiben, CV-Pfade)

- **og: Meta-Tags in `set_seo_meta()`**: Zusätzlich zu title/description/canonical werden jetzt `og:type`, `og:title`, `og:description`, `og:url`, `og:site_name` gesetzt (via `SEOService::setOpenGraph()` oder Fallback `addMeta()`)

- **DB-Migration:**
  - Neue Tabelle `jpg_category_benefits`
  - Neue Spalte `excluded_benefit_ids TEXT` in `jpg_profiles` (via `maybe_add_columns()`)

### Geändert

- `prepare_template_data()` in `class-frontend.php`: Verwendet jetzt `getResolvedBenefits()` statt `get_benefit_ids()` → Views erhalten vollständige Benefit-Objekte mit `source`-Eigenschaft
- `single-integrated.php` + `single-whitelabel.php`: Benefit-Listenelemente erhalten CSS-Klasse `jpg-benefit-{source}` (company / category / job) für visuelle Trennung nach Herkunft
- `applyCsrf`-Token wird in `prepare_template_data()` angelegt und an beide öffentlichen Views übergeben

### Internes

- Plugin-Version: `0.2.0` → `0.3.0`

---

## [0.2.0] – 2026-02-25

### Hinzugefügt

- **Dynamische Workflow-Engine (`includes/class-workflow.php`):**
  - Vollständige n-stufige Genehmigungslogik: `submit_for_approval()`, `approve_step()`, `reject_step()`, `reset_workflow()`
  - CRUD für Workflow-Schritte: `get_steps()`, `save_step()`, `delete_step()`, `reorder_steps()`
  - RBAC-Prüfung: `can_approve()` mit direktem DB-Fallback auf `cms_users.role` + `cms_roles.capabilities`
  - Audit-Log: `get_history()` mit Actor-Namen und Notizen
  - `static get_all_cms_roles()` – liest alle Rollen direkt aus `cms_roles` Tabelle (kein Hardcoding)
  - Status-Display-Helpers: `status_label()`, `status_badge_class()`

- **Datenbank DB-Version 3:**
  - Neue Tabelle `jpg_workflow_steps` (id, sort_order, step_name, approver_role, allow_self_approve, notification_email, active, created_at)
  - Neue Tabelle `jpg_workflow_history` (id, profile_id, step_order, action ENUM, actor_id, note, created_at)
  - Idempotente ALTER-TABLE-Migration (`maybe_add_columns()`): `workflow_status`, `workflow_step`, `company_id` in `jpg_profiles`

- **Admin-Workflow-Editor (neuer Untermenüpunkt „🔄 Workflow-Editor"):**
  - Schritt-Konfiguration (Name, Genehmiger-Rolle, Self-Approve-Option) via Modal
  - Tabelle ausstehender Genehmigungen mit Approve/Reject/Reset-Aktionen inkl. Notizfeld
  - Rollen-Übersicht zeigt alle DB-Rollen (dynamisch)

- **Member-Bereich – Layout-Fix:**
  - Alle Member-Views (`list`, `create`, `edit`, `applications`) rendern jetzt innerhalb des CMS-Member-Designs (Sidebar + Header) via `render_with_layout()`
  - Doppelte Registrierung via `PluginDashboardRegistry` (Hook `member_dashboard_init`) für `/member/plugin/member-jobs`-Route
  - `$baseUrl` wird an alle Views übergeben – kein hartcodiertes `/member/jobs` mehr

- **Member-Views – Workflow-Status-UI:**
  - `page-jobs-list.php`: neue „Freigabe"-Spalte mit farbigen Status-Badges (⏳/✅/❌)
  - `page-jobs-edit.php`: vollständiges Workflow-Panel (Status-Badge, Schritte-Progress, Submit-Button, History-Timeline)
  - `page-jobs-create.php`: Hinweis auf Anzahl konfigurierter Genehmigungsschritte

- **Neue Route:** `POST /member/jobs/workflow/submit/:id` → `handle_workflow_submit()` mit CSRF-Validierung

### Geändert

- `admin/views/page-settings.php` – Permissions-Tab: `$roleOptions` kommt jetzt aus `CMS_JPG_Workflow::get_all_cms_roles()` (war hartkodiertes Array `['admin','editor','author']`)
- `admin/views/page-settings.php` – neue Permission `role_approve` für Workflow-Genehmigungen
- Plugin-Version: `0.1.0` → `0.2.0`; DB-Version: `2` → `3`

---

## [0.1.0] – 2026-02-25

### Behoben

- **JSON-Export-Bug (kritisch):** `render_generator()` fing `?_jpg_export=json` nicht ab und lieferte HTML statt JSON zurück, was im Browser zu `JSON.parse: unexpected character at line 1 column 5` führte. Ursachen und Fixes:
  1. Früh-Abfang in `render_generator()` via `$_GET['_jpg_export'] === 'json'` hinzugefügt
  2. `ajax_export_json()` liest Nonce jetzt aus `$_GET` oder `$_POST` (Download-Links sind GET-Requests)
  3. Nonce-Verifikation akzeptiert sowohl `jpg_export` als auch `jpg_libraries_save` Token
  4. `page-libraries.php`: separater `$exportNonce` mit Action `jpg_export` erzeugt

### Hinzugefügt

- **Phase 6.1 (cms-companies):** Wizard-Dropdown wird dynamisch aus `cms_companies` befüllt; Plugin-Check vor SQL-Abfrage (`CMS\PluginManager::getActivePlugins()`) – als erledigt markiert
- **Phase 6.2 (Reverse Relation):** `inject_company_jobs()` via `page_content`-Filter hängt Job-Grid an Firmenprofilseiten an – als erledigt markiert
- **Phase 6.3 (cms-experts):** `load_company_experts()` lädt Team-Experten der verknüpften Firma für die Job-Detailseite – als erledigt markiert
- **Phase 7.6 – Dokumentation:** `DOC/CROSS-PLUGIN-INTEGRATION.md` erstellt mit vollständiger Beschreibung aller Cross-Plugin-Schnittstellen (cms-companies, cms-experts), Kompatibilitätsmatrix und Debugging-Hinweisen
- **Member-Bereich Integration (komplett):**
  - `includes/class-member-controller.php` – neuer Singleton-Controller mit:
    - Auth-Guard + Data-Silo (alle SQL-Queries mit `WHERE created_by = user_id`)
    - CSRF-Generierung/-Validierung für alle Formulare und AJAX-Requests
    - Input-Sanitization via `getPost()` Helper
    - Hook `member_menu_items` → Sidebar-Menüpunkt „📄 Stellenanzeigen"
    - Hook `member_dashboard_stats` → KPI-Kachel „Aktive Jobs"
    - Hook `member_dashboard_widgets` → Widget „Letzte Bewerbungseingänge"
    - Hook `member_notification_settings_sections` + `member_notification_preferences` → E-Mail-Toggle für Bewerbungsbenachrichtigungen
    - Routen: `/member/jobs`, `/member/jobs/create`, `/member/jobs/edit/:id`, `/member/jobs/applications`, `/member/jobs/applications/status`, `/member/jobs/download/:token`
    - `download_file()` – sicherer CV-Download mit Path-Traversal-Schutz und MIME-Validierung
    - Abo-Limit-Prüfung vor Job-Erstellung
  - `views/member/page-jobs-list.php` – Übersichtstabelle eigener Profile mit Aktionen
  - `views/member/page-jobs-create.php` – Formular zum Erstellen neuer Profile
  - `views/member/page-jobs-edit.php` – Vollständiges Edit-Formular mit Drag&Drop-Tasks
  - `views/member/page-jobs-applications.php` – Bewerbungs-Postfach mit AJAX-Statuswechsel und Cover-Letter-Modal
- **Datenbank DB-Version 2:** Neue Tabelle `cms_jpg_applications` (id, job_id, applicant_name, applicant_email, cover_letter, cv_file_path, cv_file_token, status, created_at, updated_at)
- **Plugin-Version 0.1.0** (war 0.0.1)

---

## [0.0.1] – 2025-07-17

### Hinzugefügt

- **CHANGELOG.md** erstellt; bisheriger Codestand als Pre-Release-Baseline erfasst
- **Phase 1.3** – Fehlende Asset-Dateien ergänzt:
  - `assets/css/public.css` (öffentliches Frontend-Stylesheet)
  - `assets/js/wizard.js` (Wizard-Stepper-Logik)
  - `assets/js/drag-drop.js` (HTML5 DnD Standalone-Modul)
  - `assets/icons/` Verzeichnis mit Platzhalter-Datei
- **Phase 2.1** – Subscription-Limits:
  - `subscription_check()` Helper in Hauptklasse integriert
  - `update_resource_usage()` bei Profil-Speichern/Löschen aufgerufen
- **Phase 2.2** – RBAC erweitert:
  - `user_can_access_plugin()` Prüfung in Admin-Pages eingebaut
  - `display_resource_limit_warning()` im Dashboard ergänzt
  - `display_upgrade_notice()` bei gesperrten Premium-Features
- **Phase 4** – Frontend-Routing Grundgerüst:
  - `views/public/single-integrated.php` Template erstellt
  - `views/public/single-whitelabel.php` Template erstellt
  - Route-Registrierung via `routes_registered` Hook
  - SEO-Meta-Tags via `SEOService` vorbereitet
  - PDF-Export-Stub (`/api/jobs/:slug/pdf`)
- **Phase 5** – Security & Performance ergänzt:
  - Rate-Limiting-Helper für PDF-Export implementiert
  - Cache-Layer mit `CacheManager::remember()` für Frontend-Abfragen
  - DSGVO-konformes Tracking via `TrackingService`
- **Phase 6** – Cross-Plugin Integration:
  - `cms-companies` Integration (Firmen-Dropdown im Wizard)
  - `cms-experts` Integration („Lerne dein Team kennen")
  - Google for Jobs JSON-LD mit `hiringOrganization` aus `cms_companies`
- **TASKS.md** – Erledigte Checkboxen markiert
- **DOC-Dateien** aktualisiert (README, INSTALLATION, etc.)