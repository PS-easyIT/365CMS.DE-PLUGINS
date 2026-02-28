# 🚀 Projektliste: Job Profile Generator

### ⚠️ Kritische Projekt-Vorgaben (Strict Rules)

1. **Cascading Benefit Logic:** Benefits werden über 3 Ebenen vererbt: **Firma** (aus `cms_company_meta`) -> **Abteilung** (aus `cms_job_categories`) -> **Job-Spezifisch** (aus `cms_job_profiles`). Die UI muss dies visuell trennen. Implementiere zudem eine Override-Logik (`excluded_benefits`), um geerbte Standard-Benefits für Sonderfälle (z.B. Werkstudenten) im Einzelfall zu deaktivieren.

2. **Dynamischer Genehmigungsprozess:** Beim Erstellen von Profilen (insbesondere aus dem Admin- und Memberbereich) ist der in den Settings definierte Workflow *strikt* einzuhalten. Diese Logik muss extrem flexibel programmiert sein, da es je nach Einstellung 1, 2, 4, 5 oder eine beliebige andere Anzahl an aufeinanderfolgenden Genehmigern geben kann.

3. **Robustes Role-Handling (Bug-Bypass):** *Achtung:* Verlasse dich beim Genehmigungsprozess und bei RBAC-Checks nicht blind auf Standard-Helper. Implementiere bei Bedarf robuste Fallbacks (z.B. direkte Datenbank-Abfragen auf `cms_users.role` oder `cms_roles.capabilities`), um sicherzustellen, dass Rechte und Genehmigungs-Stufen zu 100% greifen.

## Phase 1: Fundament, Architektur & Datenbank

* [x] **1.1 Plugin-Grundgerüst erstellen**
* [x] Datei `plugins/job-profile-generator/job-profile-generator.php` mit offiziellem 365CMS Plugin-Header anlegen.
* [x] Datei `plugins/job-profile-generator/includes/class-plugin.php` (Singleton) erstellen.
* [x] `plugins_loaded` Hook registrieren, um `JobProfileGenerator::instance()->init()` aufzurufen.


* [x] **1.2 Datenbank-Migrationen (Activation Hook)**
* [x] Tabelle `cms_job_profiles` anlegen (id, user_id, company_id, slug, title, content_json, status, views, created_at, updated_at).
* [x] Tabelle `cms_job_tasks` anlegen (Relational für Drag&Drop Aufgaben).
* [x] Tabelle `cms_job_skills` anlegen (Must-Have / Nice-to-Have).
* [x] Tabelle `cms_job_libraries` anlegen (für wiederverwendbare Skills, Benefits, Textbausteine).


* [x] **1.3 Asset-Management einrichten**
* [x] Struktur anlegen: `assets/css/`, `assets/js/`, `assets/icons/` (für SVG-Sprites).
* [x] `assets/css/admin.css` und `assets/css/public.css` anlegen (Ausschließlich CSS Custom Properties).
* [x] `assets/js/admin.js`, `wizard.js` und `drag-drop.js` anlegen (ES6+ Vanilla JS).
* [x] Hooks `wp_head` und `wp_footer` registrieren, um Assets im Admin-Backend nur auf `/admin/job-profile-*` Seiten zu laden.



## Phase 2: Core-Integration & Abo-System

* [x] **2.1 Subscription-Limits integrieren**
* [x] Activation-Hook: `ALTER TABLE cms_subscription_plans ADD COLUMN limit_job_profiles INT DEFAULT -1` ausführen (Offizieller 365CMS Weg).
* [x] Activation-Hook: Spalten `feature_whitelabel_jobs` und `feature_custom_branding` zu `cms_subscription_plans` hinzufügen.
* [x] Helper-Aufruf `update_resource_usage('job_profiles', $count, $userId)` in der Speicher-/Löschlogik implementieren.


* [x] **2.2 Zugriffskontrollen (RBAC)**
* [x] Admin-Routen-Schutz: `if (!CMS\Auth::instance()->isAdmin()) { exit; }` in allen Backend-Routen.
* [x] Plugin-Zugriff: `if (!user_can_access_plugin('job-profile-generator')) { ... }` implementieren.
* [x] UI-Warnung: `display_resource_limit_warning('job_profiles', 'Job-Profile')` im Dashboard einbauen.
* [x] UI-Hinweis: `display_upgrade_notice()` einblenden, wenn Premium-Features (z.B. Whitelabel) im aktuellen Plan gesperrt sind.



## Phase 3: Admin-Backend (Das 5x5 Menü)

### 3.1 Menüpunkt 1: Dashboard (`JobDashboardController.php`)

* [x] **Tab 1: Übersicht** – Rendern der KPI-Kacheln (Aktive Profile, Entwürfe, Gesamt-Views).
* [x] **Tab 2: Entwürfe** – Liste der Profile mit `status = 'draft'` (inkl. "Weiterbearbeiten" Action).
* [x] **Tab 3: Veröffentlicht** – Data-Table (Vanilla JS) mit veröffentlichten Profilen (Sortierung nach Datum, Views).
* [x] **Tab 4: Archiv** – Soft-Deletes (`status = 'trash'`) oder manuell deaktivierte Profile.
* [x] **Tab 5: Statistiken** – Balkendiagramm (HTML5 Canvas) der Aufrufe basierend auf `cms_analytics`.

### 3.2 Menüpunkt 2: Profil-Generator Wizard (`JobWizardController.php`)

* [x] **Auto-Save & Security** – `verifyNonce()` für `/api/job-profile/autosave` Endpunkt implementieren.
* [x] **Tab 1: Basisdaten** – Felder: Titel, Firma (Dropdown via `cms_companies`), Standort, Arbeitszeitmodell, Gehalt.
* [x] **Tab 2: Aufgaben** – Native HTML5 Drag&Drop Liste (`drag-drop.js`). Live-Character-Counter (Validierung: <50 rot, 80-150 grün, >200 gelb).
* [x] **Tab 3: Anforderungen** – Vanilla JS Skill-Tag-Input. Checkboxen für Must-Have vs. Nice-to-Have.
* [x] **Tab 4: Benefits** – Klickbares CSS-Grid mit SVG-Icons aus der `cms_job_libraries`. "Über uns" Bereich mit CMS-Core SunEditor.
* [x] **Tab 5: Review & Export** – Split-View UI: Zusammenfassung (links), Live-Preview (rechts). "Veröffentlichen"-Logik implementieren.

### 3.3 Menüpunkt 3: Bibliotheken (`JobLibraryController.php`)

* [x] **Tab 1: Textbausteine** – CRUD-Interface für vordefinierte Unternehmensbeschreibungen.
* [x] **Tab 2: Skill-Matrix** – CRUD-Interface für wiederverwendbare Fähigkeiten.
* [x] **Tab 3: Benefit-Katalog** – CRUD inkl. Datei-Upload für SVG-Icons (abgesichert durch Core-Upload-Validierung).
* [x] **Tab 4: Job-Kategorien** – CRUD für Abteilungen/Bereiche (IT, HR, Sales).
* [x] **Tab 5: Import/Export** – Funktion zum Herunterladen/Hochladen der Bibliotheksdaten als JSON.

### 3.4 Menüpunkt 4: Vorlagen & Design (`JobTemplateController.php`)

* [x] **Tab 1: PDF-Templates** – Auswahl zwischen mPDF-Layouts (z.B. Klassisch, Modern).
* [x] **Tab 2: Web-Templates** – Auswahl des Frontend-Grid-Layouts.
* [x] **Tab 3: Corporate Design** – Vanilla JS Farbpicker zur Definition von `--color-primary`, `--color-secondary`. (Gated via `feature_custom_branding`).
* [x] **Tab 4: Typografie** – Auswahl der Web-Safe-Fonts oder lokalen Fonts.
* [x] **Tab 5: E-Mail-Templates** – Platzhalter-System für zünftige Bewerbungs-Mails.

### 3.5 Menüpunkt 5: Einstellungen (`JobSettingsController.php`)

* [x] **Tab 1: Allgemein** – Standard-Währung (EUR/CHF), Standard-Sprache.
* [x] **Tab 2: Berechtigungen** – Zuweisung, welche `cms_roles` Profile erstellen/bearbeiten dürfen.
* [x] **Tab 3: Workflow** – Toggle für "Vier-Augen-Prinzip" (Entwürfe bedürfen Admin-Freigabe).
* [x] **Tab 4: Benachrichtigungen** – E-Mail-Empfänger für Benachrichtigungen bei neuen Profilen.
* [x] **Tab 5: System-Info** – Status-Checks (PDO-Support, mPDF-Schreibrechte im Upload-Ordner).

## Phase 4: Frontend & Ausgabe (Routing)

* [x] **4.1 Theme-Integrated Router (`/jobs/:slug`)**
* [x] Route in `CMS\Router` via `routes_registered` Hook einklinken.
* [x] Template `views/public/single-integrated.php` erstellen (nutzt Theme-Header/Footer).
* [x] Dynamische Befüllung der Meta-Tags via `CMS\Services\SEOService`.


* [x] **4.2 Whitelabel-Standalone Router (`/career/:slug`)**
* [x] Logik: Prüfe `user_has_feature('whitelabel_jobs')`.
* [x] Template `views/public/single-whitelabel.php` erstellen (Clean HTML5, kein CMS-Theme).


* [x] **4.3 Custom Branding Injection**
* [x] Logik: Prüfe `user_has_feature('custom_branding')`.
* [x] Dynamisches `<style>`-Tag mit DB-Farbwerten im `<head>` der Job-Ansicht injizieren.


* [x] **4.4 PDF-Export-Endpunkt**
* [x] Route `/api/jobs/:slug/pdf` anlegen (generiert PDF via mPDF on-the-fly, liefert Download-Header).



## Phase 5: Security, Tracking & Performance

* [x] **5.1 XSS & Input-Sanitization**
* [x] Alle Text-Inputs mit `CMS\Security::instance()->sanitize('text')` filtern.
* [x] Alle SunEditor-Inputs mit `CMS\Security::instance()->sanitizeHtml()` filtern.
* [x] Jede Frontend-Ausgabe mit `$security->escapeOutput()` sichern.


* [x] **5.2 CSRF & Rate-Limiting**
* [x] `$security->generateNonce('job_save')` in allen Forms einbauen.
* [x] `$security->verifyNonce()` im Controller prüfen.
* [x] Rate-Limiting für den PDF-Export (z.B. max. 5 pro Minute) via `checkRateLimit()` einbauen.


* [x] **5.3 Caching-Implementierung**
* [x] `CMS\CacheManager::instance()->remember()` um Frontend-DB-Abfragen für 30 Minuten zu cachen.
* [x] Cache-Invalidierung: `$cache->delete('job_profile_'.$slug)` beim Speichern im Backend aufrufen.


* [x] **5.4 DSGVO Page-View Tracking**
* [x] `CMS\Services\TrackingService->trackPageView()` im Public-Controller aufrufen.



## Phase 6: Cross-Plugin Integration (STRIKT NICHT-INVASIV)

*(Wichtig: Keine Modifikation an Dateien anderer Plugins. Alles läuft über Hooks und Prüfungen des 365CMS).*

* [x] **6.1 Single Source of Truth (cms-companies)**
* [x] *Prüfung:* `if (CMS\PluginManager::instance()->isPluginActive('cms-companies'))` vor Datenbankabfrage im Wizard einfügen.
* [x] Wizard-Dropdown dynamisch aus `cms_companies` befüllen, anstatt manueller Eingabe.


* [x] **6.2 Jobs auf Firmenprofil anzeigen (Reverse Relation)**
* [x] *Lösung ohne Template-Hack:* Hook `CMS\Hooks::addFilter('page_content', ...)` nutzen.
* [x] Logik: Prüfen, ob die aktuelle URL `/companies/` enthält. Wenn ja, Firmen-ID ermitteln, Jobs abfragen und HTML dynamisch per String-Concatenation an das Firmenprofil anhängen.


* [x] **6.3 "Lerne dein Team kennen" (cms-experts)**
* [x] *Prüfung:* `if (CMS\PluginManager::instance()->isPluginActive('cms-experts'))`.
* [x] Logik im Frontend-Job-Template: Über die `cms_company_experts` Tabelle aktive Experten ermitteln und deren `[cms_expert id="x"]` Shortcodes ausführen oder direkt rendern.


* [x] **6.4 Google for Jobs (JSON-LD)**
* [x] JSON-LD Array generieren und das `hiringOrganization` Objekt automatisch mit Daten (`name`, `logo_url`) aus der verknüpften `cms_companies` Tabelle befüllen.



## Phase 7: Dokumentation (`/DOC/`)

* [x] **7.1 README.md** – Plugin-Übersicht & Features.
* [x] **7.2 INSTALLATION.md** – Installations- und Update-Routine.
* [x] **7.3 ARCHITECTURE.md** – DB-Schema und MVC-Struktur.
* [x] **7.4 SUBSCRIPTION-INTEGRATION.md** – Erklärung der Whitelabel- und Limit-Features.
* [x] **7.5 HOOKS-API.md** – Eigene Actions/Filters dokumentieren.
* [x] **7.6 CROSS-PLUGIN-INTEGRATION.md** – Dokumentation, wie das Plugin updatesicher mit `cms-experts` und `cms-companies` kommuniziert.

---

# 🔄 Phase 8: Workflow & Member-Design-Fix (v0.2.0)

> **Ziel:** Member-Bereich nutzt jetzt das CMS-Member-Layout (Sidebar + Header) und unterstützt einen dynamisch konfigurierbaren n-stufigen Genehmigungsprozess.

## 8.1 Workflow-Engine (`class-workflow.php`)
- [x] `includes/class-workflow.php` erstellt – vollständige Workflow-Engine mit CRUD für Steps, Submit/Approve/Reject/Reset, RBAC-Checks, History-Tracking
- [x] `get_all_cms_roles()` statische Methode – direkte DB-Abfrage auf `cms_roles` (alle Rollen, nicht nur Defaults)
- [x] `can_approve()` – robuste Berechtigungsprüfung mit DB-Fallback (`cms_users.role`, `cms_roles.capabilities`)
- [x] `get_history($profileId)` – vollständiges Audit-Log mit Actor-Namen

## 8.2 Datenbank-Migrationen (DB-Version 3)
- [x] Tabelle `jpg_workflow_steps` anlegen (id, sort_order, step_name, approver_role, allow_self_approve, notification_email, active, created_at)
- [x] Tabelle `jpg_workflow_history` anlegen (id, profile_id, step_order, action ENUM, actor_id, note, created_at)
- [x] `maybe_add_columns()` – idempotente ALTER-TABLE-Migration: `workflow_status VARCHAR(50) DEFAULT 'none'`, `workflow_step SMALLINT DEFAULT 0`, `company_id INT` zu `jpg_profiles`
- [x] Plugin-Version auf `0.2.0`, DB-Version auf `3` gesetzt

## 8.3 Admin: Workflow-Editor (neuer Untermenüpunkt)
- [x] `admin/class-admin-menu.php` – 6. Untermenü `jpg-workflow` → „🔄 Workflow-Editor" ergänzt
- [x] `admin/class-admin-pages.php` – `render_workflow()` + `handle_workflow_post()` implementiert
- [x] `admin/views/page-workflow-editor.php` erstellt:
  - Schritt-Tabelle mit Edit/Delete
  - Ausstehende Genehmigungen mit Approve/Reject/Reset
  - Rollen-Übersicht (dynamisch aus DB)
  - Modals für Schritt anlegen/bearbeiten und Genehmigen/Ablehnen

## 8.4 Dynamisches Rollen-Handling
- [x] `admin/views/page-settings.php` – Permissions-Tab nutzt jetzt `CMS_JPG_Workflow::get_all_cms_roles()` statt hartcodiertem Array
- [x] Neue Permission `role_approve` für Workflow-Genehmigungen hinzugefügt
- [x] Workflow-Engine prüft `cms_users.role` direkt in DB (kein Blind-Trust auf Helper)

## 8.5 Member-Layout-Fix
- [x] `render_with_layout(string $viewFile, array $vars)` in `class-member-controller.php` – vollständiges HTML mit `renderMemberSidebar()`, CSS-Einbindung, Flash-Messages
- [x] Alle `render_*`-Methoden nutzen jetzt `render_with_layout()` statt `include`
- [x] `register_via_plugin_dashboard()` – Registrierung bei `PluginDashboardRegistry` (Hook: `member_dashboard_init`)
- [x] Inline-Render-Methoden: `render_list_inline()`, `render_create_inline()`, `render_edit_inline()`, `render_applications_inline()`
- [x] `get_dashboard_stats()` – öffentliche Methode für Registry stats_callback
- [x] Route `POST /member/jobs/workflow/submit/:id` → `handle_workflow_submit()`
- [x] `$baseUrl` wird an alle Views übergeben (kein hartcodiertes `/member/jobs`)

## 8.6 Member-Views: Workflow-Status-UI
- [x] `views/member/page-jobs-list.php` – Vollständige Neufassung:
  - `$baseUrl`-Variable für alle Links
  - Neue „Freigabe"-Spalte mit Workflow-Status-Badge (⏳/✅/❌)
  - Workflow-Status-Mapping: `none/pending/approved/rejected`
- [x] `views/member/page-jobs-edit.php` – Workflow-Panel ergänzt:
  - Aktueller Status mit Badge und Icon
  - Schritte-Fortschrittsanzeige (Kreise mit ✓/Schritt-Nr.)
  - Submit-Button „📤 Zur Genehmigung einreichen" (nur bei Status none/draft)
  - Ablehnungs-Hinweis + Erneut-Einreichen-Button (bei rejected)
  - Genehmigungs-/Prüfungs-Hinweis-Box (bei approved/pending)
  - Verlaufs-Timeline mit Aktionen, Actor-Namen, Notizen, Zeitstempel
  - `$wfCsrf`-Token für Workflow-Submit-Formular
- [x] `views/member/page-jobs-create.php` – Workflow-Info ergänzt:
  - Dynamische Beschreibungszeile mit Anzahl der konfigurierten Genehmigungsschritte
  - `$baseUrl` für Abbrechen-Link

# 🚀 Task-Liste: Member-Bereich Integration (Job Profile Generator)

## 1. Controller & Security (Data Siloing)
- [x] **Auth-Guard:** In jeder Controller-Methode prüfen, ob der User eingeloggt ist (`if (!CMS\Auth::instance()->isLoggedIn()) { redirect('/login'); }`).
- [x] **Daten-Kapselung (Strict Rule):** Bei jeder SQL-Query auf `cms_job_profiles` und `cms_job_applications` zwingend die Bedingung `WHERE user_id = ?` (ID des aktuellen Users) anfügen.
- [x] **CSRF-Generierung:** Für jedes Formular im Member-Bereich ein Token via `$controller->generateToken('member_job_save')` erstellen und an die View übergeben.
- [x] **CSRF-Validierung:** Bei jedem POST-Request das Token via `$controller->verifyToken()` prüfen.
- [x] **Input-Sanitization:** Alle POST-Daten strikt über `$controller->getPost('feldname', 'text/html/int')` abrufen.
- [x] **XSS-Schutz:** Alle dynamischen Template-Ausgaben (z.B. Job-Titel, Bewerber-Namen) zwingend mit `htmlspecialchars()` oder `$security->escapeOutput()` maskieren.

## 2. Navigation & Dashboard-Widgets (Hooks)
- [x] **Sidebar-Menü:** Hook `member_menu_items` implementieren, um den Menüpunkt "Stellenanzeigen" (unter der Kategorie `plugins`) hinzuzufügen.
- [x] **Dashboard-Statistik:** Hook `member_dashboard_stats` nutzen, um auf der Startseite `/member` eine Kachel mit der Metrik "Aktive Jobs" anzuzeigen.
- [x] **Dashboard-Widget:** Hook `member_dashboard_widgets` nutzen, um ein Listen-Widget ("Letzte Bewerbungseingänge") auf dem Haupt-Dashboard zu platzieren.

## 3. Abo-Limits & Feature-Gating
- [x] **Erstellen-Button blockieren:** Vor der Ausgabe des "Neuen Job anlegen"-Buttons prüfen: `if (user_can_create_resource('job_profiles'))`. Falls `false`, den Button verbergen und stattdessen `display_upgrade_notice()` rendern.
- [x] **Limit-Warnung anzeigen:** Oberhalb der Job-Listenansicht die Funktion `display_resource_limit_warning('job_profiles', 'Stellenanzeigen')` aufrufen, um den User visuell über seine Auslastung zu informieren.

## 4. Bewerber-Postfach (Applicant Tracking)
- [x] **Inbox-UI erstellen:** View für `/member/jobs/applications` anlegen (Listen- oder Kanban-Ansicht).
- [x] **Bewerbungen laden:** Nur Bewerbungen abfragen, die zu Jobs gehören, die dem aktuell eingeloggten User (`user_id`) zugewiesen sind.
- [x] **Status-Updates:** AJAX-Endpunkt für Statuswechsel (Neu -> In Prüfung -> Abgelehnt) bauen (abgesichert mit eigenem CSRF-Token `apply_status`).
- [x] **Sichere Dateidownloads:** Ein PHP-Download-Skript für Lebensläufe (PDFs) schreiben, das vor der Dateiausgabe prüft, ob der User der rechtmäßige Besitzer der verknüpften Stellenanzeige ist.

## 5. Benachrichtigungen (NotificationService)
- [x] **Privacy-Center UI:** Hook `member_notification_settings_sections` nutzen, um einen Toggle "E-Mail bei neuen Bewerbungen erhalten" im Benachrichtigungs-Menü einzufügen.
- [x] **Präferenzen speichern:** Hook `member_notification_preferences` nutzen, um den Wert des Toggles in den User-Metadaten zu sichern.
- [x] **Benachrichtigung triggern:** In der Public-Route (wenn sich jemand auf einen Job bewirbt) `CMS\Services\NotificationService::create()` aufrufen, um den Inserenten in-App (und ggf. per Mail) zu benachrichtigen.

## 6. DSGVO & Account-Löschung (Privacy Center)
- [x] **Datenexport (Art. 20 DSGVO):** Hook `cms_member_data_export_requested` implementieren. Das Skript muss alle erstellten Jobs und empfangenen Bewerberdaten des Users als sauberes JSON zurückgeben, damit es in das ZIP-Archiv des Users gepackt wird.
- [x] **Account-Löschung (Art. 17 DSGVO):** Hook `cms_member_account_deletion_requested` implementieren.
  - [x] Alle Job-Profile des Users auf `status = 'trash'` (oder Anonymisiert) setzen.
  - [x] Alle verknüpften PDF-Lebensläufe aus dem `/uploads/applications/` Verzeichnis physisch löschen, um Datenlecks zu verhindern.

# Public Sites & Frontend
## 1. Routing & Controller-Logik (class-export.php)
    [x] Public Route Registrierung: Hook routes_registered nutzen, um /jobs/:slug und /career/:slug (Whitelabel) zu definieren.
    [x] Slug-Validierung: Sicherstellen, dass nur Profile mit Status published öffentlich zugänglich sind.
    [x] Publish-Mode Switch: Logik implementieren, die basierend auf publish_mode das korrekte Template lädt.
    [x] Cache-Integration: CMS\CacheManager::instance()->remember() für die gesamte HTML-Antwort implementieren (TTL: 60 Min).
## 2. Template-Varianten & Design
    [x] Theme-Integrated View: Template erstellen, das sich nahtlos in das aktive Website-Theme einfügt.
    [x] Whitelabel View: Standalone-Template ohne Header/Footer bauen. CSS-Reset und Basis-Styling inkludieren.
    [x] Plugin-Design View: Dynamische CSS-Injektion: Lade Farben (--color-primary etc.) aus den Plugin-Settings und gib sie im <head> aus.
    [x] Responsive Design: Sicherstellen, dass alle Ansichten (insb. das Aufgaben-Grid und die Benefit-Icons) mobil optimiert sind.
## 3. Inhalts-Rendering (Cascading Logic)
    [x] Benefit-Auflösung: Die Methode getResolvedBenefits($jobId) aufrufen, um die finale Liste inkl. Overrides anzuzeigen.
    [x] Ansprechpartner: Falls das cms-experts Plugin aktiv ist, die Experten-Card des Hiring-Managers via Shortcode [cms_expert id="..."] einbinden.
    [x] Unternehmens-Widget: Firmen-Logo, Name und Kurz-Beschreibung direkt aus cms_companies ziehen (JOIN-Abfrage).
## 4. SEO & Metadaten
    [x] Strukturierte Daten: Generierung des JobPosting JSON-LD Schemas.
    [x] SEO-Metatags: Dynamische Befüllung von og:title, og:description und og:image via CMS\Services\SEOService.
    [x] Canonical-Links: Korrekte Verlinkung sicherstellen, um Duplicate Content zwischen /jobs/ und /career/ zu vermeiden.
## 5. Tracking & Sicherheit
    [x] Besucher-Statistik: Aufruf von CMS\Services\TrackingService->trackPageView() zur Erfassung anonymer Views für das Dashboard.
    [x] Bewerbungs-Modal: Einbindung des Bewerbungsformulars mit CSRF-Schutz (apply_job).
    [x] Spam-Schutz über das CMS

---

# v0.5.0 – UX-Upgrade & Genehmiger-Dashboard

## 1. Admin: Genehmigungen als eigenständiger Untermenüpunkt
- [x] **Submenu trennen:** `admin/class-admin-menu.php` — Eintrag `jpg-approvals` nach `jpg-workflow` eingefügt.
- [x] **render_approvals() + handle_approvals_post():** `admin/class-admin-pages.php` — render_workflow() von Pending-Logik bereinigt, neue Methoden angelegt.
- [x] **Admin View:** `admin/views/page-approvals.php` — Standalone-Seite mit Stats, Tabelle, Dot-Progress und Modal.
- [x] **Workflow-Editor bereinigt:** Pending-Block + Modal aus `page-workflow-editor.php` entfernt, Link-Hinweis auf neue Seite eingefügt.

## 2. Member: Create/Edit als 5-Tab-Wizard
- [x] **Edit-View vollständig überarbeitet:** `views/member/page-jobs-edit.php` — 5-Tab-Wizard: Basisdaten (+ salary_min/max, experience_level, description), Aufgaben, Anforderungen, Benefits, Workflow.
- [x] **Create-View vollständig überarbeitet:** `views/member/page-jobs-create.php` — gleicher 5-Tab-Wizard, alle Tabs leer.
- [x] **save_profile_post():** Speichert jetzt auch `requirements` (req_text[]/req_type[]) und `benefits` (benefit_ids[]).
- [x] **render_create_inline() / render_create():** Übergeben `$allBenefits`, `$tasks`, `$requirements`, `$benefitIds`.
- [x] **render_edit_inline():** Übergibt jetzt `$wfCsrf`.

## 3. Member: Genehmiger-Dashboard
- [x] **Register-Eintrag:** `member-job-approvals` wird in `register_via_plugin_dashboard()` für Admins und User mit pending-Items registriert.
- [x] **render_approvals_inline():** Verarbeitet POST (approve/reject/reset), lädt pending Profile für aktuellen User.
- [x] **Member View:** `views/member/page-approvals.php` — Tabelle mit Dot-Progress, Approve/Reject-Modal, Notizfeld.

---

# v0.6.0 – Generator-Erweiterungen, Anforderungs-Bibliothek & Unternehmens-Benefits

> **Ziel:** Generator-Wizard auf 6 Tabs erweitern, eigenständige Anforderungs-Bausteine-Bibliothek einführen, Firmen-Autofill im Basisdaten-Tab, Standard-Benefits pro Unternehmen konfigurierbar machen.

## 1. Neue Service-Klasse: CMS_JPG_RequirementItems

- [x] `includes/class-requirement-items.php` erstellt — Singleton mit `get_all()`, `get_grouped()`, `save()`, `delete()`.
- [x] `cms-jobprofile-generator.php` — Include für `class-requirement-items.php` zur Ladeliste hinzugefügt.

## 2. Datenbank-Erweiterungen

- [x] `class-installer.php` — Tabelle `jpg_requirement_items` (id, group_name, title, sort_order) angelegt.
- [x] `class-installer.php` — Tabelle `jpg_company_default_benefits` (id, company_id, benefit_id, UNIQUE KEY `uk_company_benefit`) angelegt.

## 3. Admin: Unternehmens-Übersicht (neuer Untermenüpunkt)

- [x] **Submenu:** `admin/class-admin-menu.php` — Eintrag `jpg-companies` (🏢 Unternehmens-Übersicht) vor `jpg-settings` eingefügt.
- [x] **render_company_overview() + handle_company_overview_post():** `admin/class-admin-pages.php` — Render lädt Firmen, Benefit-Katalog, Zuweisungsmap; POST-Handler: DELETE+INSERT für `jpg_company_default_benefits`.
- [x] **Admin View:** `admin/views/page-company-overview.php` — 2-spaltiges Layout: Firmenliste (links, 280 px, mit Benefit-Badge) + Benefit-Checkbox-Grid (rechts, nach Gruppen). Fehlerzustände (kein cms-companies aktiv, keine Benefits vorhanden) abgedeckt.

## 4. Generator-Wizard: 6 Tabs

- [x] **Tabs-Array:** `render_generator()` — `benefits` und `skills` als separate Tabs, Review auf Tab 6.
- [x] **handle_generator_post():** `save_benefits` speichert ausschließlich Benefits; neuer Case `save_skills` speichert ausschließlich Skills.
- [x] **Tab 1 (Basisdaten): Firmen-Autofill** — Companies-Query erweitert (city, zip, country, phone, website); `$companiesJson` als Inline-JS-Map; `jpgFillCompanyData(id)` füllt `#jpg-location` + Info-Box.
- [x] **Tab 2 (Aufgaben): Textbaustein-Picker** — `<details>`-Block mit Gruppenfilter. `jpgAddTaskFromModule(content)` erstellt vollständiges DnD-fähiges Task-Item.
- [x] **Tab 3 (Anforderungen): Anforderungs-Bausteine-Picker** — Zweiter Picker-Block (grüne `border-left`-Markierung) aus `$requirementItems`. Funktion `jpgAddReqItem()`.
- [x] **Tab 4 (Benefits): Company Default Benefits** — `$companyDefaultBenefitIds` aus `jpg_company_default_benefits` für aktuelle Firma; Vorauswahl nur bei leerem `$benefitIds`-Array (neues Profil). Hinweis-Banner wenn Defaults vorhanden. „Weiter zu Skills"-Button.
- [x] **Tab 5 (Skills):** Eigenständiges Formular (action `save_skills`), aus dem früheren kombinierten Benefits+Skills-Tab ausgelagert.
- [x] **Tab 6 (Review & Export):** Unverändert, Nummerierung angepasst.

## 5. Bibliotheken: Anforderungs-Liste

- [x] **Tabs-Array:** `render_libraries()` — `'requirement-items'` als zweitem Tab (6 Tabs gesamt), Data-Match ergänzt.
- [x] **handle_libraries_post():** Case `'requirement-items'` vor `'job-categories'` eingefügt — speichert/löscht via `CMS_JPG_RequirementItems`.
- [x] **View:** `admin/views/page-libraries.php` — Anforderungs-Liste-Tab mit Gruppenauflistung, Edit-Modal (`jpgLibEdit('ri',…)`), Lösch-Button, Formular (group_name, title, sort_order).
- [x] **JS:** `jpgLibEdit()` — `type === 'ri'`-Branch für Bearbeiten-Funktion ergänzt.

## 6. Unternehmens-Profil: Admin-Erweiterung & Member-Einstellungen

- [x] **Admin-Query erweitert:** `render_company_overview()` — SELECT um alle Firmenfelder (`logo_url`, `description`, `website`, `email`, `phone`, `industry`, `company_size`, `founded_year`, `employee_count`, `location_*`) ergänzt.
- [x] **Admin-View Tab-UI:** `admin/views/page-company-overview.php` — 2-Tab-Struktur (📋 Firmeninformationen / 🎁 Standard-Benefits) mit Firmenliste inkl. Logo-Vorschau in der linken Spalte.
- [x] **Admin: Firmeninfo bearbeiten:** `handle_company_info_post()` — UPDATE für `{prefix}companies` mit Sanitierung aller Felder; Nonce `jpg_company_info_save`.
- [x] **Member-Route:** `class-member-controller.php` — `GET/POST /member/jobs/settings` registriert.
- [x] **Member-Menüeintrag:** `register_via_plugin_dashboard()` — Sub-Item `member-job-settings` (⚙️ Einstellungen, priority 29) für alle eingeloggten Mitglieder registriert.
- [x] **Member-Controller:** `render_settings_inline()` + `render_settings()` — lädt Firmendaten per `WHERE user_id = $userId`, verarbeitet POST mit `verify_token('member_company_settings')` und UPDATE auf `{prefix}companies WHERE id = ? AND user_id = ?`.
- [x] **Member-View:** `views/member/page-company-settings.php` (neu) — 2-spaltiges Formular: Firmenidentität + Branche (links) / Beschreibung + Standort (rechts); Logo-Vorschau; Leer-Zustand wenn kein Firmenprofil vorhanden.

---

# v0.8.0 – Member-Einstellungen: Job-Statistiken & Admin-Mitglieds-Verknüpfung

> **Ziel:** Member-Firmeneinstellungen zeigen eigene Job-Statistiken an und bieten CTA-Links.
> Admin-Unternehmensübersicht zeigt verknüpfte CMS-User an und bietet Schnellzugriff.

## 1. Member: Job-Statistikleiste in Firmen-Einstellungen

- [x] **`render_settings_inline()`** — Lädt `$companyJobStats` (`published`, `total`, `draft`, `archived`) via `COUNT/SUM`-Query auf `jpg_profiles WHERE company_id = ? AND created_by = ?`.
- [x] **`views/member/page-company-settings.php`** — Statistik-Leiste (Flexbox) nach Tab-Navigation eingefügt:
  - Kacheln: ✅ aktiv (grün), 📝 Entwürf (amber), ggf. gesamt
  - ➕ Neue Stelle-Button rechts (Link zu `?action=create` oder `/member/jobs/create`)
  - Leerzustand-Text wenn noch keine Stellen vorhanden
- [x] **Action-Bars (Info- und Benefits-Tab)** — Jeweils zusätzlicher Button "➕ Neue Stelle erstellen" (`margin-left:auto`) hinzugefügt.
- [x] **`$isInlineMode`-Erkennung** — `$createJobUrl` wird abhängig von Inline- vs. Standalone-Modus korrekt gesetzt (`/member/plugin/member-jobs?action=create` vs. `/member/jobs/create`).

## 2. Admin: Verknüpfter User in Unternehmens-Übersicht

- [x] **`render_company_overview()`** — Companies-SELECT um `user_id` erweitert; separates Laden des Linked User über DB-Query auf `{prefix}users WHERE id = ?` nach `$selectedCompanyId`.
- [x] **`admin/views/page-company-overview.php` — Sidebar:**
  - Pro Firma mit `user_id > 0`: zusätzliches 👤-Badge (amber) mit `title`-Tooltip.
- [x] **`admin/views/page-company-overview.php` — Info-Tab:**
  - Unter dem Speichern-Footer: Info-Box (blau) mit Name, E-Mail des Linked User.
  - Button "👁️ User-Profil" → `/admin/users?id=X`.
  - Button "🔗 Member-Einst." → `/member/plugin/member-job-settings` (Vorschau-Link).

## 3. Noch offen (Phase 9)

* [x] **Eigene Genehmiger-Rollen für Teams:** Ermöglichen, dass ein Mandant (Member) einen anderen CMS-User seines Teams als `approver` für seine eigenen Jobs registriert; persistiert in `jpg_team_approvers (company_id, approver_user_id, created_by)`.
* [x] **Privacy-Filter im Admin-Dashboard:** Toggle-Schalter „Mandanten-Profile verbergen/anzeigen" im Admin-Dashboard (Standard: Hidden). `jpg_profiles.is_private TINYINT DEFAULT 0` als DB-Feld; Admin-Filter via GET-Parameter `?show_private=1`.
* [x] **Globales Audit-Log:** Tabelle `jpg_admin_access_log (id, admin_user_id, target_company_id, action, ip_addr, created_at)` – schreibt Einträge wann Admin welche Firma/Profil eingesehen hat (DSGVO-Compliance).


Hier sind ergänzende Features für das Admin- und Member-Dashboard:

### 1. Mandanten-Autonomie & Privacy

* **Isolierte Genehmigungs-Workflows:** Mandanten können eigene interne Genehmiger definieren (z. B. Abteilungsleiter), ohne dass der Gesamt-Admin eingreifen muss.
* **Admin-Blindheits-Modus:** Implementierung eines „Privacy-Flags“ in `cms_jpg_profiles`. Ein globaler Admin sieht diese Profile in seinem Dashboard standardmäßig *nicht*, es sei denn, er aktiviert explizit den Filter „Mandanten-Daten einblenden“.
* **Eigene Medien-Silos:** Strikte Trennung der Upload-Verzeichnisse für CVs und Firmenlogos nach `user_id` oder `company_id`, um unbefugten Zugriff durch andere Mandanten zu verhindern.

### 2. Member Dashboard (Für Firmen & Experten)

* **Bewerber-Analytics:** Eine grafische Übersicht (Balkendiagramm) über die Klickraten der eigenen Stellenprofile vs. tatsächliche Bewerbungseingänge.
* **Quick-Aktionen für Bewerber:** Direktes Versenden von Standard-Antworten (Einladung, Absage) direkt aus dem Dashboard via `NotificationService`.
* **Abo-Nutzungsanzeige:** Ein Widget, das genau anzeigt, wie viele der im Plan enthaltenen Stellenprofile bereits verbraucht sind.

### 3. Admin Dashboard (Für den System-Inhaber)

* **Mandanten-Übersicht:** Eine Statistik, welche Firmen die aktivsten Inserenten sind, um Upselling-Potenziale für höhere Abos zu identifizieren.
* **System-Health-Monitor:** Anzeige von verwaisten Daten (z. B. Profile von gelöschten Usern) mit einer 1-Klick-Bereinigung.
* **Globaler Template-Editor:** Master-Vorlagen für das Plugin-Design erstellen, die Mandanten als Basis für ihr eigenes Branding nutzen können.

### 4. Neue Tasks für deine `TASKS.md` (Mandanten-Fokus)

Füge diese Punkte in deine bestehenden Listen ein:

**In Phase 6 (Member-Bereich):**

* [x] **Mandanten-Datenkapselung:** Sicherstellen, dass die `user_id` in `jpg_profiles` als primärer Filter für alle Member-Ansichten dient (Mandanten sehen nur sich selbst).
* [x] **Eigene Genehmiger-Rollen:** Ermöglichen, dass ein Mandant einen anderen User seines Teams als `approver` für seine eigenen Jobs markiert.

**In Phase 10 (Admin-Backend):**

* [x] **Privacy-Filter:** Toggle-Schalter im Admin-Dashboard: „Mandanten-Profile verbergen/anzeigen". Standardmäßig auf `hidden`.
* [x] **Globales Audit-Log:** Aufzeichnung, welcher Admin wann Mandanten-Daten eingesehen hat (Compliance/DSGVO).
---

# v0.7.0 – Unternehmens-Übersicht im Member-Bereich & Admin-Alignment

> **Ziel:** Member-Bereich erhält vollständige Unternehmens-Übersicht (Firmeninfos + Standard-Benefits-Tab),
> Admin-Dashboard bekommt Schnellzugriff und die Admin-Unternehmens-Übersicht wird um Job-Statistiken erweitert.

## 1. Member: Firmen-Einstellungen – Benefits-Tab

- [x] **`views/member/page-company-settings.php` überarbeitet:**
  - Tab-Navigation (📋 Firmeninformationen | 🎁 Standard-Benefits) analog zur Admin-Ansicht
  - CSRF-Token aufgeteilt: `$csrfInfo` (info-Tab) und `$csrfBenefits` (benefits-Tab)
  - `$activeTab`-Variable steuert aktiven Tab; URL wird per `?tab=` übergeben
  - `$isInline`-Erkennung: Tab-URL ermittelt `/member/plugin/member-job-settings` (Inline) vs. `/member/jobs/settings` (Standalone)
  - Benefits-Tab: Checkbox-Grid mit Benefit-Badges; Zähler-Badge auf Tab-Button für bereits zugewiesene Benefits
  - Leerzustand wenn noch keine Benefits im Katalog vorhanden
  - Info-Tipp-Banner erklärt Nutzen der Standard-Benefits

## 2. Member-Controller: Benefits-Tab-Logik

- [x] **`render_settings_inline(object $user, bool $standaloneRoute = false)`:**
  - `?tab=`-GET-Parameter liest `info` oder `benefits` aus
  - POST-Handler für `settings_tab = 'benefits'`: lädt und speichert `jpg_company_default_benefits` (**mit user_id-JOIN-Sicherheitsprüfung** – nur eigene Firma darf geändert werden)
  - POST-Handler für `settings_tab = 'info'`: unveränderte Firmeninfo-Speicherung
  - Lädt `$allBenefits` aus `CMS_JPG_BenefitsCatalog::instance()->get_grouped()`
  - Lädt `$assignedBenefitIds` aus `jpg_company_default_benefits` mit JOIN auf `companies.user_id`
  - Separate CSRF-Tokens: `member_company_settings` und `member_company_benefits`
  - `$standaloneRoute`-Flag steuert `$baseUrl` (`/member/jobs` vs. `/member/plugin/member-jobs`)
- [x] **`render_settings()`:** Puffert Ausgabe von `render_settings_inline()` und rendert mit vollem Seitenlayout via `output_settings_with_layout()`
- [x] **`output_settings_with_layout(string $pageContent)`:** Eigenständige Layout-Methode für Standalone-Route mit Page-Header „⚙️ Firmen-Einstellungen"

## 3. Admin: Dashboard-Schnellzugriff

- [x] **`render_dashboard()`:** Lädt `$companiesCount` (aktive Unternehmen) für Dashboard-Kachel
- [x] **`admin/views/page-dashboard.php` – Schnellzugriff-Sektion:**
  - Neue Card „⚡ Schnellzugriff" nach den Stat-Cards eingefügt
  - Links zu: 🏢 Unternehmens-Übersicht (mit Unternehmensanzahl-Badge), 📝 Neues Profil, 🎁 Benefit-Katalog, ✅ Genehmigungen, 🔄 Workflow-Editor, ⚙️ Einstellungen

## 4. Admin: Unternehmens-Übersicht – Job-Statistiken & Alignment

- [x] **`render_company_overview()`:** Lädt `$jobCountsMap` (company_id → published/total) via GROUP-BY-Query auf `jpg_profiles`
- [x] **`admin/views/page-company-overview.php` – Sidebar erweitert:**
  - Pro Firma werden jetzt zwei Badges angezeigt: 📄 aktive Stellen (grün) + 🎁 Benefit-Anzahl (blau)
  - `$jobCountsMap`-Variable im Docblock dokumentiert
- [x] **Admin: Info-Tab – Aktionen erweitert:**
  - Statuszeile „📄 X aktive / Y gesamt" im Formular-Footer (nur wenn Stellen vorhanden)
  - Link zu „➕ Neue Stelle für Firma" (Wizard vorbelegt mit `company_preselect`)
- [x] **Admin: Benefits-Tab – Aktionen erweitert:**
  - Link zu „➕ Neue Stelle für Firma" im Formular-Footer

---

# v0.8.0 – Job-Statistiken im Member-Bereich & Admin: Verknüpfter CMS-User

> **Ziel:** Member-Einstellungsseite zeigt aktuelle Stellen-Statistiken der eigenen Firma (Aktiv/Entwürfe) sowie einen direkten CTA zum Erstellen einer neuen Stelle. Admin-Unternehmens-Übersicht zeigt, welcher CMS-User mit einer Firma verknüpft ist.

## 1. Member: Stellen-Statistiken & CTA

- [x] **`includes/class-member-controller.php` – `render_settings_inline()`:**
  - Lädt `$companyJobStats` (published / draft / archived / total) via COUNT/SUM-Query auf `jpg_profiles` mit `company_id = ? AND created_by = ?`
  - Data-Silo-Sicherheit: doppelter Filter auf `company_id` **und** `created_by` (member sieht nur eigene Jobs)
  - `$companyJobStats` wird ans View übergeben
- [x] **`views/member/page-company-settings.php` – Stats-Bar & CTAs:**
  - Neue Stats-Bar nach Tab-Navigation: Badges für ✅ Aktiv (grün) + 📝 Entwurf (amber); zeigt „Noch keine Stellenanzeigen" wenn leer
  - Button „➕ Neue Stelle" (blau, `margin-left:auto`) führt zu `/member/plugin/member-jobs?action=create` (Inline) oder `/member/jobs/create` (Standalone)
  - Info-Tab: Zusatz-CTA „➕ Neue Stelle erstellen" in den Form-Actions
  - Benefits-Tab: Zusatz-CTA „➕ Neue Stelle (Benefits vorauswählen)" in den Form-Actions

## 2. Admin: Verknüpfter CMS-User in Unternehmens-Übersicht

- [x] **`admin/class-admin-pages.php` – `render_company_overview()`:**
  - SELECT auf `companies` erweitert um `user_id`
  - Lädt `$linkedUser` (id, username, email, firstname, lastname) aus `{prefix}users WHERE id = ?` für die ausgewählte Firma (wenn `user_id` gesetzt)
- [x] **`admin/views/page-company-overview.php` – Sidebar & Info-Tab:**
  - Sidebar: 👤 Amber-Badge bei Firmen mit verknüpftem CMS-User (title = user_id)
  - Info-Tab: Info-Box „👤 Verknüpftes Mitglied" (blauer Hintergrund) unterhalb des Speichern-Buttons mit Name, E-Mail und Links zu User-Profil und Member-Einstellungen
  - `$linkedUser = $linkedUser ?? null` abgesicherter Init im View

## 3. TASKS.md

- [x] **Mandanten-Datenkapselung** als erledigt markiert (war in v0.6.0–v0.7.0 bereits implementiert)
- [x] **v0.8.0-Abschnitt** dokumentiert (dieser Abschnitt)

---

## Phase 9 – Abgeschlossen ✅

* [x] **Eigene Genehmiger-Rollen für Teams:** Neue Tabelle `jpg_team_approvers (id, company_id, approver_user_id, created_by, created_at)`. Member kann anderen CMS-User als internen Genehmiger registrieren. Integration in `can_approve()` in `class-workflow.php`.
* [x] **Privacy-Filter im Admin-Dashboard:** Spalte `is_private TINYINT(1) DEFAULT 0` in `jpg_profiles`. Admin-Filter per GET `?show_private=1` in `render_dashboard()` und `render_generator()`. Standard: private Profile werden ausgeblendet.
* [x] **Globales Audit-Log:** Tabelle `jpg_admin_access_log (id, admin_user_id, target_company_id, target_profile_id, action VARCHAR(50), ip_addr, created_at)`. Einträge schreiben in `render_company_overview()` und `render_generator()` bei Admin-Zugriff auf Mandanten-Daten (DSGVO-Compliance).

---

## v0.9.0 - Phase 9 abgeschlossen

### Neue Features

#### 9.1 Team-Genehmiger
- Neue DB-Tabelle jpg_team_approvers (id, company_id, approver_user_id, created_by, created_at)
- CMS_JPG_Workflow: get_team_approvers(), add_team_approver(), remove_team_approver()
- can_approve() prueft zusaetzlich user_is_team_approver_for_profile()
- get_pending_for_user() via UNION: Rollen-basiert + Team-Genehmiger-basiert
- Member-Bereich: neuer Tab "Team-Genehmiger" in Firmen-Einstellungen

#### 9.2 Privacy-Filter
- Neue DB-Spalte jpg_profiles.is_private TINYINT(1) DEFAULT 0
- Admin-Dashboard + Generator: Toggle "Private anzeigen / ausblenden" (?show_private=1)
- Checkbox "Privates Profil" im Generator-Grunddaten-Tab
- CMS_JPG_Profiles::get_list() und count() unterstuetzen hide_private-Argument

#### 9.3 Globales Audit-Log (DSGVO)
- Neue DB-Tabelle jpg_admin_access_log (id, admin_user_id, target_company_id, target_profile_id, action, ip_addr, created_at)
- CMS_JPG_Workflow::log_admin_access() schreibt Eintraege in render_company_overview() und render_generator()
- CMS_JPG_Workflow::get_audit_log() liefert lesbare Eintraege (mit Admin-Namen, Firmen-Namen, Profil-Titel)
- Admin-Einstellungen: neuer Tab "Audit-Log" mit Tabellen-Darstellung und Firmen-Filter

### Geaenderte Dateien
- includes/class-installer.php
- includes/class-workflow.php
- includes/class-profiles.php
- includes/class-member-controller.php
- admin/class-admin-pages.php
- admin/views/page-dashboard.php
- admin/views/page-generator.php
- admin/views/page-settings.php
- views/member/page-company-settings.php

---

## v0.9.1 - Abgeschlossen

### Neue Features

#### 10.1 Departments-Accordion (Member + Admin)
- page-company-settings.php: Departments-Tab mit Inline-Accordion (kein Page-Reload mehr)
- jpgMemberToggleDept() JS-Funktion fuer Ausklapp-Panels pro Abteilung
- Jedes Panel zeigt Benefits + Anforderungen (alle Bibliotheks-IDs als vorbefuellte Checkboxen)
- admin/views/page-company-overview.php: analog jpgAdminToggleDept() Accordion

#### 10.2 Jobs-Seite Konfiguration
- Neuer Tab "Jobs-Seite" in Member Firmen-Einstellungen (page-company-settings.php)
- Felder: Seitentitel, Intro-Text, Kontakt-E-Mail, Gehalt anzeigen (Toggle), Seite aktivieren, URL
- class-member-controller.php: 'jobs-page' als gueltiger $settingsTab + POST-Handler
- class-departments.php: get_company_settings() / save_company_settings() mit 6 Feldern
- includes/class-installer.php: maybe_add_columns() fuer neue company_settings-Spalten
- admin/views/page-company-overview.php: Jobs-Seite Tab mit vollstaendigem Konfig-Formular

### Geaenderte Dateien
- includes/class-member-controller.php
- includes/class-departments.php
- includes/class-installer.php
- admin/class-admin-pages.php
- admin/views/page-company-overview.php
- views/member/page-company-settings.php

---

## v0.9.2 - Bug-Fix-Release

### Behobene Fehler

#### BUG-01 TypeError: Argument #1 ($v) must be of type string, null given
- Ursache: fn(string $v) mit strict_types=1 verweigert null-Werte aus DB-Feldern
- Fix: Alle 12 Vorkommen auf fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES) geaendert
- Betroffene Dateien (12):
  - admin/views/page-approvals.php
  - admin/views/page-workflow-editor.php
  - views/member/page-approvals.php
  - views/member/page-company-settings.php
  - views/member/page-jobs-applications.php
  - views/member/page-jobs-create.php
  - views/member/page-jobs-edit.php ($esc, Zeile 21)
  - views/member/page-jobs-list.php
  - views/member/page-libraries.php
  - views/member/page-templates.php
  - views/public/jobs-list.php
  - includes/class-export.php

#### BUG-02 Doppeltes Formular in page-jobs-create.php
- Ursache: Altes einfaches Erstellungsformular (Zeilen 469-566) verblieb beim Einbau
  des 5-Tab-Wizards in v0.5.0. Beide Formulare wurden gleichzeitig gerendert.
- Fix: Duplikat entfernt, Datei endet nach dem Wizard-JS-Block (</script>)


# Projekt-Roadmap: Phasen 11 bis 14

## Phase 11: Mandanten-Isolation & Admin-Sperren
**Fokus:** Strikte Trennung und Schutz der Mandanten-Privatsphäre.

- [x] **11.1 Daten-Silos & Autonomie**
    - [x] **Mandanten-Silo:** Medien-Struktur `/uploads/jpg/user_{id}/` für isolierten Dokumentenzugriff.
    - [x] **Team-Genehmiger:** Mandanten können eigene interne Approver via `jpg_team_approvers` verwalten.
- [x] **11.2 Admin-Blindheits-Modus**
    - [x] **Standard-Sperre:** In `CMS_JPG_Profiles::get_list()` einen Filter integrieren, der Profile mit `is_private = 1` für Admins überspringt.
    - [x] **Einsicht-Toggle:** "Mandanten-Daten einblenden" Button im Admin-Dashboard (aktiviert `?show_private=1`).
- [x] **11.3 Compliance-Logging**
    - [x] **Globales Audit-Log:** Tabelle `jpg_admin_access_log` protokolliert jeden Admin-Zugriff auf Mandanten-Daten.
    - [x] **Audit-UI:** Admin-Tab zur Revision der Zugriffsprotokolle (Wer sah wann was?).

---

## Phase 12: Pro-Dashboard & Analytics
**Fokus:** Visualisierung und Kontingent-Kontrolle im Member-Bereich.

- [x] **12.1 Bewerber-Analytics**
    - [x] **Widget "Bewerber-Trend":** Grafik (Canvas) für Bewerbungen der letzten 7/30 Tage.
    - [x] **Conversion-Check:** Anzeige "Profil-Aufrufe vs. Bewerbungen" im Member-Dashboard.
- [x] **12.2 Abo-Kontingent**
    - [x] **Quota-Widget:** Radial-Progress Anzeige (Gebrauchte vs. verfügbare Jobs im Abo-Plan).
    - [x] **Limit-Checks:** Proaktive Warnung bei Erreichen von 90% des Ressourcen-Limits.

---

## Phase 13: Automatisierung & Kommunikation
**Fokus:** Effizienz im Bewerbungsprozess.

- [x] **13.1 Automatisierte Bewerber-Kommunikation**
    - [x] **Status-Mailer:** Automatischer Versand von Vorlagen-Mails bei Statusänderung (z. B. Einladung).
    - [x] **Template-Editor:** Mandanten können eigene E-Mail-Templates für Absagen/Zusagen hinterlegen.
- [x] **13.2 Bulk- & Quick-Actions**
    - [x] **Massen-Aktionen:** Bulk-Löschen und Status-Updates für die Stellenliste.
    - [x] **Dashboard-Buttons:** Direkte Kontakt-Optionen (Anruf/Mail) im Bewerber-Widget.

---

## Phase 14: System-Hygiene & Frontend-Erweiterung
**Fokus:** Wartbarkeit und UX-Feinschliff.

- [x] **14.1 Health-Monitor**
    - [x] **Zombie-Check:** Tool zum Aufspüren verwaister Dateien (CVs ohne DB-Eintrag).
    - [x] **Bereinigungs-Cron:** Automatisches Löschen abgelaufener Bewerberdaten nach X Tagen (DSGVO).
- [x] **14.2 UX-Optimierung**
    - [x] **1-Click Duplizierer:** Button zum Klonen bestehender Profile als Entwurf.
    - [x] **Inline-Slug-Editor:** URL-Slug direkt im Basisdaten-Tab editierbar machen.
- [x] **14.3 Frontend-Suche**
    - [x] **Job-Filter:** Öffentliche Filter für Kategorien, Remote-Optionen und Gehaltsspannen.
    - [x] **Member-PDF:** PDF-Export-Button für Inserenten direkt im Member-Bereich.

## Phase 15: ⚠️ Mandanten-Vorgaben (Tenant-Rules)
1. **Admin-Blindheit:** Der Gesamt-Admin sieht Mandantenprofile (`is_private = 1`) standardmäßig NICHT. Die SQL-Abfragen im Admin-Dashboard müssen diesen Filter strikt beachten, außer der Parameter `show_private=1` ist gesetzt.
2. **Interne Genehmigung:** Mandanten verwalten ihre Genehmiger (`jpg_team_approvers`) selbst. Ein Profil gilt als veröffentlicht, sobald die mandanteninternen Hürden genommen sind, ohne dass ein System-Admin eingreifen muss.
3. **Daten-Kapselung:** Jede Aktion im Member-Bereich muss die Integrität des Mandanten wahren (Filter auf `company_id` und `user_id`).
4. **Audit-Trail:** Jeder administrative Zugriff auf Mandantendaten muss revisionssicher in `jpg_admin_access_log` mit Zeitstempel und IP dokumentiert werden.

---

## v0.9.3 – Trait-Split & CSRF-Fix ✅ Abgeschlossen (2026-02-26)

### Admin-Controller-Split

- [x] **`admin/class-admin-pages.php`** (~2600 Zeilen) in 10 eigenständige Trait-Dateien unter `admin/modules/` aufgeteilt.
  - [x] `trait-page-dashboard.php` — Dashboard-Übersicht, KPI-Stats
  - [x] `trait-page-generator.php` — Generator-Wizard, Firmen-Autofill
  - [x] `trait-page-libraries.php` — Bibliotheken-Verwaltung (6 Tabs)
  - [x] `trait-page-design.php` — Templates & Corporate Design
  - [x] `trait-page-settings.php` — Einstellungen (5 Tabs)
  - [x] `trait-page-workflow.php` — Workflow-Schritte Editor
  - [x] `trait-page-approvals.php` — Admin-Genehmigungen (Dot-Progress)
  - [x] `trait-page-companies.php` — Unternehmens-Übersicht + Benefits
  - [x] `trait-page-subscription.php` — Abo-Rollen-Verwaltung
  - [x] `trait-page-users.php` — Plugin-User-Verwaltung
- [x] `class-admin-pages.php` Shell auf 137 Zeilen reduziert (10× `require_once` + 10× `use TraitName`)

### Member-Controller-Split

- [x] **`includes/class-member-controller.php`** (~2600 Zeilen) in 7 Trait-Dateien unter `includes/member/` aufgeteilt.
  - [x] `trait-member-hooks.php` — Hooks, Routes, Stats, Dashboard-Menü
  - [x] `trait-member-dsgvo.php` — Daten-Export (Art. 20), Account-Löschung
  - [x] `trait-member-jobs.php` — Stellenanzeigen: List, Create, Edit, Duplicate
  - [x] `trait-member-inline.php` — Plugin-Dashboard-Registrierung, Inline-Views
  - [x] `trait-member-applications.php` — Bewerbungs-Postfach, Ajax-Status, Download
  - [x] `trait-member-approvals.php` — Member-Genehmigungsbereich (Standalone + Inline)
  - [x] `trait-member-settings.php` — Firmen-Einstellungen, Abteilungen, Jobs-Seite
- [x] `class-member-controller.php` Shell auf 116 Zeilen reduziert (7× `require_once` + 7× `use TraitName`)

### CSRF-Bug-Fix

- [x] **`trait-page-subscription.php`:** `$nonce = self::nonce('jpg_subscription_save')` nach POST-Handler-Block verschoben (war vorher davor → überschrieb Token vor Verifikation)
- [x] **`trait-page-users.php`:** `$nonce = self::nonce('jpg_users_save')` nach POST-Handler-Block verschoben (gleiches Problem)
---

# 🔮 Zukünftige Features & Ideen

> Alle bisherigen 15 Phasen sind abgeschlossen. Die folgenden Features sind Ideen für kommende Versionen.  
> Priorität: 🔴 Hoch · 🟡 Mittel · 🟢 Niedrig · 🔵 Idee/Vision

---

## 🔴 Hohe Priorität

### 16. Rate-Limiting & Security-Hardening
- [ ] Rate-Limiting für API-Endpunkte und Bewerbungsformulare
- [ ] Brute-Force-Schutz für Genehmigungsprozesse
- [ ] Security-Audit aller AJAX-Endpunkte
- [ ] Content-Security-Policy für Whitelabel-Seiten

### 17. Erweiterte Analytics
- [ ] Bewerbungs-Funnel: Views → Bewerbungen → Einstellungen
- [ ] A/B-Testing: Verschiedene Stellenanzeigen-Versionen vergleichen
- [ ] Kanal-Tracking: Woher kommen die Bewerber?
- [ ] Zeitbasierte Reports: Bewerbungen pro Woche/Monat

### 18. Bewerbermanagement erweitern
- [ ] Kanban-Board für Bewerbungen (Pipeline: Eingang → Sichtung → Interview → Angebot → Einstellung)
- [ ] Bewertungs-Matrix für Bewerber (Skills-Match-Score)
- [ ] Team-Bewertung: Mehrere Personen bewerten Bewerber
- [ ] Automatische Absage-E-Mails mit Vorlage
- [ ] Interview-Kalender-Integration

---

## 🟡 Mittlere Priorität

### 20. Multi-Language-Support
- [ ] Stellenanzeigen in mehreren Sprachen (DE/EN)
- [ ] Automatische Übersetzung (optional via API)
- [ ] Sprachversion-Umschaltung auf Public-Seite

### 21. Erweiterte Vorlagen
- [ ] Template-Marketplace: Unternehmensübergreifende Templates
- [ ] Branchen-spezifische Starter-Kits (IT, Marketing, Sales, etc.)
- [ ] Stellenanzeigen-Import aus PDF/Word
- [ ] Vergleichs-Ansicht: Aktuelles Profil vs. Template

### 22. Performance & Skalierung
- [ ] Cache-Layer für Public-Stellenanzeigen (Redis/Memcached)
- [ ] Bulk-Operations: Mehrere Profile gleichzeitig status-ändern
- [ ] Archiv-Funktion: Abgeschlossene Suchen archivieren (nicht löschen)
- [ ] Lazy-Loading für lange Library-Listen

---

## 🟢 Niedrige Priorität

### 23. Jobboard-Integration
- [ ] Indeed/StepStone XML-Feed-Export
- [ ] Google Jobs Schema.org-Markup
- [ ] LinkedIn-Job-Posting via API
- [ ] XING-Job-Posting via API
- [ ] Multiposting: Eine Anzeige → mehrere Kanäle

### 24. Erweiterte Public-Seite
- [ ] Stellenanzeigen-Vergleich (2–3 nebeneinander)
- [ ] Gehaltsrechner / Salary-Bands anzeigen
- [ ] Benefits-Highlight auf der Firmenseite
- [ ] Mitarbeiter-Stimmen einbinden (Testimonials)
- [ ] Video-Job-Ads (YouTube/Vimeo-Embed)

### 25. Workflow-Erweiterungen
- [ ] Bedingte Genehmigungen (z.B. Gehalt > X → Extra-Stufe)
- [ ] Eskalation: Automatischer Reminder nach X Tagen
- [ ] Delegation: Genehmiger kann temporär vertreten werden
- [ ] Audit-Trail: Vollständige Änderungshistorie pro Profil

---

## 🔵 Ideen / Vision

### 26. Talent-Pool
- [ ] Interessenten-DB: Initiativbewerbungen sammeln
- [ ] Matching: Neue Stellenanzeigen → passende Talente benachrichtigen
- [ ] Talent-Tags: Skills, Interessen, Verfügbarkeit
- [ ] CRM-Light: Kontakthistorie pro Kandidat

### 27. Employer-Branding
- [ ] Kultur-Seite-Generator (Mission, Values, Team-Fotos)
- [ ] Employer-Branding-Score (Profil-Vollständigkeit)
- [ ] Social-Media-Grafik-Generator für Stellenanzeigen
- [ ] Karriere-Blog-Integration

### 28. Onboarding-Modul
- [ ] Automatischer Onboarding-Plan bei Einstellung
- [ ] Checkliste: Hardware, Zugänge, Schulungen
- [ ] Integration mit Organigramm (neuer Node bei Einstellung)